<?php
// controllers/UserController.php

require_once 'controllers/BaseController.php'; 
require_once 'models/UserModel.php';
require_once 'config/SessionManager.php';

class UserController extends BaseController {
    private $userModel;

    public function __construct($db) {
        $this->userModel = new UserModel($db); 
    }

    public function handleRegistration() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }

        // ── OTP verification check ───────────────────────────────────────────
        $otpVerified = !empty($_SESSION['otp']['verified']) && $_SESSION['otp']['verified'] === true;
        if (!$otpVerified) {
            $this->redirect('register', 'otp_required');
        }
        // Clear the OTP session once we're past it
        unset($_SESSION['otp']);

        $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

        if ($this->userModel->emailExists($email)) {
            $this->redirect('register', 'email_taken');
        }

        // ── Handle ID file upload ────────────────────────────────────────────
        $govIdUrl = 'pending';

        if (isset($_FILES['gov_id_file']) && $_FILES['gov_id_file']['error'] === UPLOAD_ERR_OK) {
            $file     = $_FILES['gov_id_file'];
            $allowed  = ['image/jpeg', 'image/png', 'application/pdf'];
            $maxSize  = 5 * 1024 * 1024; // 5 MB

            if (!in_array($file['type'], $allowed) || $file['size'] > $maxSize) {
                $this->redirect('register', 'invalid_file');
            }

            // Build a unique filename to avoid collisions
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $safeName = time() . '_' . bin2hex(random_bytes(6)) . '.' . strtolower($ext);

            // Resolve upload directory relative to project root (index.php location)
            $uploadDir = __DIR__ . '/../uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $uploadDir . $safeName)) {
                $govIdUrl = 'uploads/' . $safeName;
            } else {
                $this->redirect('register', 'upload_failed');
            }
        }

        // ── Build user data ──────────────────────────────────────────────────
        $firstName     = trim($_POST['first_name']     ?? '');
        $middleInitial = trim($_POST['middle_initial'] ?? '');
        $lastName      = trim($_POST['last_name']      ?? '');

        $userData = [
            'first_name'     => $firstName,
            'middle_initial' => $middleInitial,
            'last_name'      => $lastName,
            'email'          => $email,
            'password'       => $_POST['password'],
            'phone_number'   => trim($_POST['phone_number'] ?? ''),
            'role'           => 'Citizen',
            'gov_id_url'     => $govIdUrl,
        ];

        if ($this->userModel->register($userData)) {
            $user = $this->userModel->getUserByEmail($email);

            SessionManager::set('user_id', $user->getId());
            // Store full name in session for greeting
            SessionManager::set('name', trim($firstName . ' ' . $lastName));
            SessionManager::set('role', $user->getRole());

            $this->redirect('home', 'registered', true);
        } else {
            $this->redirect('register', 'db_error');
        }
    }

    public function handleLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        $user = $this->userModel->getUserByEmail($_POST['email']);

        if ($user && password_verify($_POST['password'], $user->getPasswordHash())) { 
            SessionManager::set('user_id', $user->getId());
            SessionManager::set('name', $user->getFirstName());
            SessionManager::set('role', $user->getRole());
            
            if ($user->getRole() === 'Admin') {
                $this->redirect('admin_landing');
            } else {
                // Both Host and Citizen roles go to the shared landing page
                $this->redirect('home', 'success', true);
            }
        } else {
            $this->redirect('login', '1');
        }
    }

    public function handleLogout() {
        SessionManager::destroy();
        $this->redirect('home', 'success', true);
    }

    // ============ ADMIN CRUD METHODS ============

    /**
     * Get all users (for admin dashboard)
     */
    public function getAllUsers() {
        header('Content-Type: application/json');
        
        try {
            $users = $this->userModel->getAll();
            echo json_encode(['success' => true, 'users' => $users]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * Create a new user (admin)
     */
    public function createUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit();
        }

        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate required fields
        if (empty($data['email']) || empty($data['password']) || empty($data['first_name'])) {
            echo json_encode(['success' => false, 'message' => 'Required fields missing']);
            exit();
        }

        // Check if email already exists
        if ($this->userModel->emailExists($data['email'])) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit();
        }

        try {
            $userData = [
                'first_name'     => $data['first_name'],
                'middle_initial' => $data['middle_initial'] ?? '',
                'last_name'      => $data['last_name'] ?? '',
                'email'          => $data['email'],
                'password'       => $data['password'],
                'phone_number'   => $data['phone_number'] ?? '',
                'role'           => $data['role'] ?? 'Citizen',
                'gov_id_url'     => 'pending'
            ];

            if ($this->userModel->register($userData)) {
                echo json_encode(['success' => true, 'message' => 'User created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create user']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * Update an existing user (admin)
     */
    public function updateUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit();
        }

        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            exit();
        }

        try {
            if ($this->userModel->update($data['user_id'], $data)) {
                echo json_encode(['success' => true, 'message' => 'User updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update user']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * Toggle is_verified for a user (admin)
     */
    public function verifyUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit();
        }

        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            exit();
        }

        try {
            if ($this->userModel->toggleVerify($data['user_id'])) {
                // Return the new state
                $user = $this->userModel->getById($data['user_id']);
                $newState = $user ? (int)$user->getIsVerified() : null;
                echo json_encode(['success' => true, 'message' => 'Verification status updated', 'is_verified' => $newState]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update verification']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * Delete a user (admin)
     */
    public function deleteUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit();
        }

        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            exit();
        }

        try {
            if ($this->userModel->delete($data['user_id'])) {
                echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete user']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ============ HOST STATUS MANAGEMENT ============

    /**
     * Relinquish host status
     * Allows a host to give up their hosting status and become an evacuee
     */
    public function relinquishHostStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit();
        }

        header('Content-Type: application/json');

        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $userId = $_SESSION['user_id'];

        try {
            // Check current host status
            $currentStatus = $this->userModel->getHostStatus($userId);
            
            if ($currentStatus !== 'active_host') {
                echo json_encode([
                    'success' => false, 
                    'message' => 'You are not currently an active host'
                ]);
                exit();
            }

            // Relinquish host status (this also deactivates the shelter)
            if ($this->userModel->relinquishHostStatus($userId)) {
                    // FORCE update the session so the Portal knows you are no longer a host
                    $_SESSION['host_status'] = 'relinquished'; 
                    SessionManager::set('host_status', 'relinquished');

                    echo json_encode([
                        'success' => true, 
                        'message' => 'Status updated',
                        'redirect' => 'index.php?route=evacuee_portal' 
                    ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to relinquish host status'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Get host status for current user
     */
    public function getHostStatus() {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $userId = $_SESSION['user_id'];

        try {
            $status = $this->userModel->getHostStatus($userId);
            $canRequest = $this->userModel->canRequestShelter($userId);

            echo json_encode([
                'success' => true,
                'host_status' => $status,
                'can_request_shelter' => $canRequest,
                'is_active_host' => ($status === 'active_host')
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false, 
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Restore host status (for future enhancement)
     */
public function restoreHostStatus() {
    header('Content-Type: application/json');
    $userId = $_SESSION['user_id'] ?? null;

    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    // Attempt the database update
    if ($this->userModel->restoreHostStatus($userId)) {
        // CRITICAL: Update the session status immediately
        $_SESSION['host_status'] = 'active_host';
        
        echo json_encode([
            'success' => true, 
            'message' => 'Host status restored successfully',
            'redirect' => 'index.php?route=host_portal'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update record']);
    }
    exit;
}
    
    /**
     * Profile page — fetch all real status data and render the view
     */
    public function profileDashboard() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.php?route=login');
            exit();
        }

        $userId = $_SESSION['user_id'];

        // --- Load user record from DB for fresh data ---
        // $this->userModel already has $db injected; use it directly
        $userObj = $this->userModel->getById($userId);

        $userData = [
            'user_id'     => $userObj ? $userObj->getId()            : $userId,
            'name'        => $userObj ? $userObj->getFirstName()      : ($_SESSION['name'] ?? ''),
            'email'       => $userObj ? $userObj->getEmail()          : ($_SESSION['email'] ?? ''),
            'phone'       => $userObj ? $userObj->getPhoneNumber()    : '',
            'role'        => $userObj ? $userObj->getRole()           : ($_SESSION['role'] ?? 'Citizen'),
            'host_status' => $userObj ? $userObj->getHostStatus()     : 'none',
            'created_at'  => $userObj ? $userObj->getCreatedAt()      : null,
            'is_verified' => $userObj ? $userObj->getIsVerified()     : 0,
        ];

        // Use the PDO connection stored on UserModel (via reflection trick isn't clean;
        // instead we re-fetch from the db.php global — UserModel stores it as $this->db)
        // We need the raw PDO for extra queries.  Access it via a helper method:
        $db = $this->userModel->getDb();

        // --- EVACUEE STATUS RESOLUTION ---
        $evacueeStatus   = 'none';      // none | pending | approved | checked_in | completed
        $shelterInfo     = null;        // array with shelter details when checked in
        $checkinDate     = null;

        if ($userData['role'] !== 'Admin') {
            // 1. Is the user currently checked in as an occupant?
            $occStmt = $db->prepare("
                SELECT o.occupant_id, o.checked_in_at, o.group_size,
                       s.shelter_name, s.location, s.shelter_id
                FROM occupants o
                JOIN shelter s ON o.shelter_id = s.shelter_id
                WHERE o.user_id = ? AND o.status = 'active'
                LIMIT 1
            ");
            $occStmt->execute([$userId]);
            $occupant = $occStmt->fetch(PDO::FETCH_ASSOC);

            if ($occupant) {
                $evacueeStatus = 'checked_in';
                $shelterInfo   = $occupant;
                $checkinDate   = $occupant['checked_in_at'];
            } else {
                // 2. Check latest non-completed request
                $reqStmt = $db->prepare("
                    SELECT r.id, r.status, r.approval_code, r.created_at, r.approved_at,
                           s.shelter_name, s.location
                    FROM requests r
                    JOIN shelter s ON r.shelter_id = s.shelter_id
                    WHERE r.user_id = ?
                    ORDER BY r.created_at DESC
                    LIMIT 1
                ");
                $reqStmt->execute([$userId]);
                $latestReq = $reqStmt->fetch(PDO::FETCH_ASSOC);

                if ($latestReq) {
                    $evacueeStatus = $latestReq['status']; // pending|approved|checked_in|completed|declined
                    if ($latestReq['status'] === 'approved') {
                        $shelterInfo = $latestReq;
                    }
                }
            }
        }

        // --- HOST STATUS RESOLUTION ---
        $hostStatusLabel = null;
        $hostShelterInfo = null;
        $occupantCount   = 0;

        if ($userData['host_status'] === 'active_host') {
            $shelterStmt = $db->prepare("
                SELECT s.shelter_id, s.shelter_name, s.location,
                       s.current_capacity, s.max_capacity, s.is_active
                FROM shelter s
                WHERE s.host_id = ?
                LIMIT 1
            ");
            $shelterStmt->execute([$userId]);
            $hostShelterInfo = $shelterStmt->fetch(PDO::FETCH_ASSOC);

            if ($hostShelterInfo) {
                $countStmt = $db->prepare("
                    SELECT COUNT(*) as cnt, SUM(group_size) as total_people
                    FROM occupants
                    WHERE shelter_id = ? AND status = 'active'
                ");
                $countStmt->execute([$hostShelterInfo['shelter_id']]);
                $countRow      = $countStmt->fetch(PDO::FETCH_ASSOC);
                $occupantCount = (int)($countRow['total_people'] ?? 0);

                $hostStatusLabel = $occupantCount > 0 ? 'Actively Hosting' : 'Available to Host';
            }
        }

        // ── View display helpers (moved from profile.php to controller) ─────
        $role        = $userData['role']        ?? 'Citizen';
        $hostStatus  = $userData['host_status'] ?? 'none';
        $isVerified  = $userData['is_verified'] ?? false;
        $memberSince = $userData['created_at']
            ? date('F j, Y', strtotime($userData['created_at']))
            : 'N/A';

        $isHost    = ($hostStatus === 'active_host');
        $isEvacuee = !$isHost && ($role !== 'Admin');

        // Evacuee status meta map
        $evacStatusMap = [
            'checked_in' => ['label'=>'SAFE','sub'=>'Currently Staying at Shelter','color'=>'#065f46','bg'=>'#d1fae5','border'=>'#10b981','icon'=>'shield-check','pulse'=>'#10b981','glow'=>'rgba(16,185,129,0.2)'],
            'approved'   => ['label'=>'Approved','sub'=>'Waiting for Check-In','color'=>'#1e40af','bg'=>'#dbeafe','border'=>'#3b82f6','icon'=>'check-circle','pulse'=>'#3b82f6','glow'=>'rgba(59,130,246,0.15)'],
            'pending'    => ['label'=>'Awaiting Approval','sub'=>'Request is under review','color'=>'#92400e','bg'=>'#fef3c7','border'=>'#f59e0b','icon'=>'clock','pulse'=>'#f59e0b','glow'=>'rgba(245,158,11,0.15)'],
        ];
        $evacMetaDefault = ['label'=>'No Active Shelter','sub'=>'Submit a request to find shelter','color'=>'#64748b','bg'=>'#f8fafc','border'=>'#e2e8f0','icon'=>'map-pin','pulse'=>null,'glow'=>'transparent'];
        $em = $isEvacuee ? ($evacStatusMap[$evacueeStatus] ?? $evacMetaDefault) : $evacMetaDefault;

        // Host status meta
        $hm = ($hostStatusLabel === 'Actively Hosting')
            ? ['color'=>'#065f46','bg'=>'#d1fae5','border'=>'#10b981','icon'=>'users','pulse'=>'#10b981']
            : ['color'=>'#1e40af','bg'=>'#dbeafe','border'=>'#3b82f6','icon'=>'door-open','pulse'=>null];

        // Host capacity display vars
        $max  = $hostShelterInfo['max_capacity']     ?? 0;
        $curr = $hostShelterInfo['current_capacity'] ?? 0;
        $pct  = $max > 0 ? min(100, round(($curr / $max) * 100)) : 0;
        $hostSubText = ($hostStatusLabel === 'Actively Hosting')
            ? "Currently hosting {$occupantCount} " . ($occupantCount === 1 ? 'person' : 'people')
            : 'Shelter open and accepting evacuees';

        // Avatar initials
        $nameParts = explode(' ', trim($userData['name'] ?? 'U'));
        $initials  = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));

        // Role badge CSS class and icon
        $badgeClass = match($role){ 'Admin'=>'rb-admin', 'Host'=>'rb-host', default=>'rb-evacuee' };
        $badgeIcon  = match($role){ 'Admin'=>'shield', 'Host'=>'home', default=>'user' };

        // Sidebar status chip variables
        if ($isHost) {
            $sm = $hm;
            $sv = $hostStatusLabel ?? 'Available to Host';
            $ss = $occupantCount > 0 ? "Hosting {$occupantCount} " . ($occupantCount === 1 ? 'person' : 'people') : 'No evacuees currently';
            $si = $hm['icon'];
        } else {
            $sm = $em;
            $sv = $em['label'];
            $ss = $em['sub'];
            $si = $em['icon'];
        }

        require 'views/auth/profile.php';
    }

    /**
     * settings — handles both GET display and POST form submissions.
     * Replaces the logic that was previously embedded in views/auth/settings.php.
     */
    public function settingsDashboard() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $userId    = $_SESSION['user_id'];
        $userObj   = $this->userModel->getById($userId);

        if (!$userObj) {
            header('Location: index.php?route=logout');
            exit();
        }

        $db = $this->userModel->getDb();

        $profileSuccess = $profileError = '';
        $passwordSuccess = $passwordError = '';

        // ── POST: Update Profile ─────────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
            $firstName = trim($_POST['first_name'] ?? '');
            $lastName  = trim($_POST['last_name']  ?? '');
            $email     = trim($_POST['email']      ?? '');
            $phone     = trim($_POST['phone']      ?? '');

            if (empty($firstName)) {
                $profileError = 'First name is required.';
            } elseif (strlen($firstName) > 50) {
                $profileError = 'First name cannot exceed 50 characters.';
            } elseif (strlen($lastName) > 50) {
                $profileError = 'Last name cannot exceed 50 characters.';
            } elseif (empty($email)) {
                $profileError = 'Email address is required.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $profileError = 'Please enter a valid email address.';
            } elseif (!empty($phone) && !preg_match('/^[\d\s\+\-\(\)]{7,15}$/', $phone)) {
                $profileError = 'Please enter a valid phone number.';
            } else {
                $stmt = $db->prepare('SELECT user_id FROM users WHERE email = ? AND user_id != ?');
                $stmt->execute([$email, $userId]);
                if ($stmt->fetch()) {
                    $profileError = 'This email address is already in use by another account.';
                } else {
                    $update = $db->prepare('UPDATE users SET first_name=?, last_name=?, email=?, phone_number=? WHERE user_id=?');
                    if ($update->execute([$firstName, $lastName, $email, $phone, $userId])) {
                        $_SESSION['name'] = $firstName . ($lastName ? ' ' . $lastName : '');
                        $profileSuccess   = 'Profile updated successfully.';
                        $userObj          = $this->userModel->getById($userId);
                    } else {
                        $profileError = 'Failed to update profile. Please try again.';
                    }
                }
            }
        }

        // ── POST: Change Password ────────────────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
            $currentPw = $_POST['current_password']  ?? '';
            $newPw     = $_POST['new_password']       ?? '';
            $confirmPw = $_POST['confirm_password']   ?? '';

            if (empty($currentPw)) {
                $passwordError = 'Please enter your current password.';
            } elseif (!password_verify($currentPw, $userObj->getPasswordHash())) {
                $passwordError = 'Current password is incorrect.';
            } elseif (empty($newPw)) {
                $passwordError = 'New password cannot be empty.';
            } elseif (strlen($newPw) < 8) {
                $passwordError = 'New password must be at least 8 characters long.';
            } elseif ($newPw !== $confirmPw) {
                $passwordError = 'New password and confirmation do not match.';
            } elseif ($currentPw === $newPw) {
                $passwordError = 'New password must be different from your current password.';
            } else {
                $hash   = password_hash($newPw, PASSWORD_DEFAULT);
                $update = $db->prepare('UPDATE users SET password_hash=? WHERE user_id=?');
                if ($update->execute([$hash, $userId])) {
                    $passwordSuccess = 'Password changed successfully.';
                } else {
                    $passwordError = 'Failed to update password. Please try again.';
                }
            }
        }

        $activeTab = 'profile';
        if (!empty($passwordError) || !empty($passwordSuccess)) {
            $activeTab = 'security';
        }

        require 'views/auth/settings.php';
    }

    private function redirect($route, $param = null, $success = false) {
        $url = "index.php?route={$route}";
        if ($param) {
            $key = $success ? 'success' : 'error';
            $url .= "&{$key}={$param}";
        }
        header("Location: {$url}");
        exit();
    }
}