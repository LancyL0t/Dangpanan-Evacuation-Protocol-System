<?php
// controllers/ShelterController.php

require_once 'models/ShelterModel.php';

class ShelterController {
    private $db;
    private $shelterModel;

    public function __construct($db_conn) {
        $this->db = $db_conn;
        $this->shelterModel = new ShelterModel($db_conn);
    }

    // ============ ADMIN CRUD METHODS ============

    /**
     * Get all shelters (for admin dashboard)
     */
    public function getAllShelters() {
        header('Content-Type: application/json');
        
        try {
            $shelters = $this->shelterModel->getAllForAdmin();
            echo json_encode(['success' => true, 'shelters' => $shelters]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * Create a new shelter (admin)
     */
    public function createShelter() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit();
        }

        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);

        // Validate required fields
        if (empty($data['shelter_name']) || empty($data['location'])) {
            echo json_encode(['success' => false, 'message' => 'Required fields missing']);
            exit();
        }

        try {
            $shelterData = [
                'host_id'        => $data['host_id'] ?? null,
                'shelter_name'   => $data['shelter_name'],
                'location'       => $data['location'],
                'latitude'       => $data['latitude'] ?? null,
                'longitude'      => $data['longitude'] ?? null,
                'contact_number' => $data['contact_number'] ?? '',
                'max_capacity'   => $data['max_capacity'] ?? 50,
                'amenities'      => isset($data['amenities']) ? json_encode($data['amenities']) : '[]',
                'supplies'       => isset($data['supplies']) ? json_encode($data['supplies']) : '{}'
            ];

            if ($this->shelterModel->create($shelterData)) {
                echo json_encode(['success' => true, 'message' => 'Shelter created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create shelter']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * Update an existing shelter (admin)
     */
    public function updateShelterAdmin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit();
        }

        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['shelter_id'])) {
            echo json_encode(['success' => false, 'message' => 'Shelter ID required']);
            exit();
        }

        try {
            if ($this->shelterModel->updateById($data['shelter_id'], $data)) {
                echo json_encode(['success' => true, 'message' => 'Shelter updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update shelter']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * Delete a shelter (admin)
     */
    public function deleteShelter() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit();
        }

        header('Content-Type: application/json');
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['shelter_id'])) {
            echo json_encode(['success' => false, 'message' => 'Shelter ID required']);
            exit();
        }

        try {
            if ($this->shelterModel->delete($data['shelter_id'])) {
                echo json_encode(['success' => true, 'message' => 'Shelter deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete shelter']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    // ============ EXISTING METHODS (Keep all existing functionality) ============

    public function submitRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            
            if (session_status() === PHP_SESSION_NONE) session_start();
            
            $user_id = $_SESSION['user_id'];
            
            // Check if user is an active host (server-side validation)
            require_once 'models/UserModel.php';
            $userModel = new UserModel($this->db);
            
            if (!$userModel->canRequestShelter($user_id)) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'You cannot request shelter while you are an active host. Please relinquish your host status first.'
                ]);
                exit();
            }
            
            // CHECK: Prevent if user is currently checked in to any shelter
            $checkOccupant = $this->db->prepare("
                SELECT occupant_id 
                FROM occupants 
                WHERE user_id = ? AND status = 'active'
            ");
            $checkOccupant->execute([$user_id]);
            
            if ($checkOccupant->fetch()) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'You are already checked in to a shelter. You cannot request another shelter while occupying one.'
                ]);
                exit();
            }
            
            // CHECK: Prevent if user has a checked_in request
            $checkRequest = $this->db->prepare("
                SELECT id 
                FROM requests 
                WHERE user_id = ? AND status = 'checked_in'
            ");
            $checkRequest->execute([$user_id]);
            
            if ($checkRequest->fetch()) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'You have already been checked in to a shelter. Please check out before requesting another shelter.'
                ]);
                exit();
            }
            
            $shelter_id = $data['shelter_id'];
            $group_size = $data['group_size'];
            $notes = $data['notes'];

            $sql = "INSERT INTO requests (user_id, shelter_id, group_size, notes, status, is_notified) 
                    VALUES (?, ?, ?, ?, 'pending', 0)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$user_id, $shelter_id, $group_size, $notes]);

            echo json_encode(['success' => true]);
            exit();
        }
    }

    public function checkUpdates() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user_id = $_SESSION['user_id'];

        $stmt = $this->db->prepare("SELECT r.id, s.shelter_name 
                                    FROM requests r 
                                    JOIN shelter s ON r.shelter_id = s.shelter_id 
                                    WHERE r.user_id = ? AND r.status = 'approved' AND r.is_notified = 0");
        $stmt->execute([$user_id]);
        $update = $stmt->fetch();

        if ($update) {
            $updateStmt = $this->db->prepare("UPDATE requests SET is_notified = 1 WHERE id = ?");
            $updateStmt->execute([$update['id']]);
            
            echo json_encode(['new_update' => true, 'facility' => $update['shelter_name']]);
        } else {
            echo json_encode(['new_update' => false]);
        }
        exit();
    }

    /**
     * Get user's active requests for progress tracking
     */
    public function getUserRequests() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }
        
        $user_id = $_SESSION['user_id'];
        
        try {
            require_once 'models/RequestModel.php';
            $requestModel = new RequestModel($this->db);
            
            $requests = $requestModel->getUserActiveRequests($user_id);
            
            echo json_encode([
                'success' => true,
                'requests' => $requests
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Get specific request details for navigation
     */
    public function getRequestDetails() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }
        
        $request_id = $_GET['request_id'] ?? null;
        
        if (!$request_id) {
            echo json_encode(['success' => false, 'message' => 'Request ID required']);
            exit();
        }
        
        try {
            require_once 'models/RequestModel.php';
            $requestModel = new RequestModel($this->db);
            
            $request = $requestModel->getRequestWithDetails($request_id, $_SESSION['user_id']);
            
            if ($request) {
                echo json_encode([
                    'success' => true,
                    'request' => $request
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Request not found'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

    /**
     * Cancel a shelter request
     */
    public function cancelRequest() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid method']);
            exit();
        }
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit();
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $request_id = $data['request_id'] ?? null;
        
        if (!$request_id) {
            echo json_encode(['success' => false, 'message' => 'Request ID required']);
            exit();
        }
        
        try {
            require_once 'models/RequestModel.php';
            $requestModel = new RequestModel($this->db);
            
            $result = $requestModel->cancelRequest($request_id, $_SESSION['user_id']);
            
            if ($result) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Request cancelled successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to cancel request'
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit();
    }

public function evacueeDashboard() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?route=login");
        exit();
    }

    require_once 'models/UserModel.php';
    require_once 'models/RequestModel.php';

    $userModel = new UserModel($this->db);
    $requestModel = new RequestModel($this->db);
    $userId = $_SESSION['user_id'];
    
    // Refresh the status from the database to ensure accuracy
    $hostStatus = $userModel->getHostStatus($userId);
    $canRequestShelter = $userModel->canRequestShelter($userId);
    
    // Update session to stay in sync
    $_SESSION['host_status'] = $hostStatus; 

    // PRE-FETCH DATA FOR THE VIEW
    $userActiveRequests = $requestModel->getUserActiveRequests($userId);
    
    // Find if there is an approved request for the sidebar code widget
    $approvedRequest = null;
    foreach ($userActiveRequests as $req) {
        if ($req['status'] === 'approved' && !empty($req['approval_code'])) {
            $approvedRequest = $req;
            break;
        }
    }

    // Detect if evacuee is currently checked in to a shelter
    $checkedInShelter = null;
    $checkedInStmt = $this->db->prepare("
        SELECT o.occupant_id, s.shelter_name, s.shelter_id
        FROM occupants o
        JOIN shelter s ON o.shelter_id = s.shelter_id
        WHERE o.user_id = ? AND o.status = 'active'
        LIMIT 1
    ");
    $checkedInStmt->execute([$userId]);
    $checkedInShelter = $checkedInStmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $this->db->prepare("SELECT * FROM shelter WHERE is_active = 1");
    $stmt->execute();
    $sheltersData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Pass variables to the view
    require 'views/shelter/evacuee_portal.php';
}

    public function saveShelterSetup() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit();
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $input = json_decode(file_get_contents("php://input"), true);
        $s1 = $input['step1'] ?? [];
        $s2 = $input['step2'] ?? [];
        $s3 = $input['step3'] ?? [];

        try {
            // Start transaction to ensure both shelter and user updates happen together
            $this->db->beginTransaction();

            $host_id = $_SESSION['user_id'];
            $name = $s1['shelter-name'];
            
            $location = $s1['street-address'] . ', ' . $s1['barangay'] . ', ' . $s1['city'] . ', ' . $s1['province'];
            
            $latitude = !empty($s1['latitude']) ? $s1['latitude'] : null;
            $longitude = !empty($s1['longitude']) ? $s1['longitude'] : null;

            $contact = $s1['contact-phone'];
            $capacity = $s2['max-capacity'];
            
            $amenities = isset($s2['amenities']) ? json_encode($s2['amenities']) : '';

            $supplies = [
                'water' => [
                    'qty' => $s3['water_qty'] ?? 0,
                    'unit' => $s3['water_unit'] ?? 'Gallons'
                ],
                'food' => [
                    'qty' => $s3['food_qty'] ?? 0,
                    'unit' => $s3['food_unit'] ?? 'Packs'
                ],
                'medical' => [
                    'qty' => $s3['meds_qty'] ?? 0,
                    'unit' => $s3['meds_unit'] ?? 'Kits'
                ]
            ];
            $suppliesJson = json_encode($supplies);

            $checkSql = "SELECT shelter_id FROM shelter WHERE host_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$host_id]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                // Update existing shelter
                $sql = "UPDATE shelter SET 
                        shelter_name = ?, 
                        location = ?, 
                        latitude = ?, 
                        longitude = ?,
                        contact_number = ?, 
                        max_capacity = ?,
                        amenities = ?,
                        supplies = ?
                        WHERE host_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$name, $location, $latitude, $longitude, $contact, $capacity, $amenities, $suppliesJson, $host_id]);
            } else {
                // Insert new shelter
                $sql = "INSERT INTO shelter (host_id, shelter_name, location, latitude, longitude, contact_number, max_capacity, current_capacity, amenities, supplies, is_active) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 1)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$host_id, $name, $location, $latitude, $longitude, $contact, $capacity, $amenities, $suppliesJson]);
            }

            // CRITICAL FIX: Update user's host_status and role to make them an active host
            $updateUserSql = "UPDATE users SET host_status = 'active_host', role = 'Host' WHERE user_id = ?";
            $updateUserStmt = $this->db->prepare($updateUserSql);
            $updateUserStmt->execute([$host_id]);

            // Update session variables to reflect the new status
            $_SESSION['host_status'] = 'active_host';
            $_SESSION['role'] = 'Host';

            // Commit transaction
            $this->db->commit();

            echo json_encode(['success' => true]);

        } catch (PDOException $e) {
            // Rollback transaction on error
            $this->db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit();
    }

    public function hostDashboard() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?route=login");
            exit();
        }

        $user_id = $_SESSION['user_id'];

        $stmt = $this->db->prepare("SELECT * FROM shelter WHERE host_id = ?");
        $stmt->execute([$user_id]);
        $shelter = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$shelter) {
            header("Location: index.php?route=shelter-setup");
            exit();
        }

        $pendingStmt = $this->db->prepare("
            SELECT r.*, u.first_name, u.last_name, u.email, u.phone_number 
            FROM requests r 
            JOIN users u ON r.user_id = u.user_id 
            WHERE r.shelter_id = ? AND r.status = 'pending'
            ORDER BY r.created_at DESC
        ");
        $pendingStmt->execute([$shelter['shelter_id']]);
        $pendingRequests = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
        $pendingCount = count($pendingRequests);

        $approvedStmt = $this->db->prepare("
            SELECT r.*, u.first_name, u.last_name, u.email, u.phone_number 
            FROM requests r 
            JOIN users u ON r.user_id = u.user_id 
            WHERE r.shelter_id = ? AND r.status = 'approved'
            ORDER BY r.created_at DESC
        ");
        $approvedStmt->execute([$shelter['shelter_id']]);
        $approvedRequests = $approvedStmt->fetchAll(PDO::FETCH_ASSOC);
        $approvedCount = count($approvedRequests);

        require 'views/shelter/host_portal.php';
    }

    public function approveRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit();
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $request_id = $data['request_id'] ?? null;

        if (!$request_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit();
        }

        try {
            require_once 'models/RequestModel.php';
            $requestModel = new RequestModel($this->db);
            
            $requestStmt = $this->db->prepare("SELECT * FROM requests WHERE id = ?");
            $requestStmt->execute([$request_id]);
            $request = $requestStmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                echo json_encode(['success' => false, 'message' => 'Request not found']);
                exit();
            }

            // Approve with code generation
            if ($requestModel->approveRequest($request_id)) {
                // Update shelter capacity
                if ($request['group_size'] > 0) {
                    $updateCapacity = $this->db->prepare("
                        UPDATE shelter 
                        SET current_capacity = current_capacity + ? 
                        WHERE shelter_id = ?
                    ");
                    $updateCapacity->execute([$request['group_size'], $request['shelter_id']]);

                    $checkFull = $this->db->prepare("
                        UPDATE shelter 
                        SET is_full = CASE 
                            WHEN current_capacity >= max_capacity THEN 1 
                            ELSE 0 
                        END 
                        WHERE shelter_id = ?
                    ");
                    $checkFull->execute([$request['shelter_id']]);
                }

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to approve request']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit();
    }

    public function declineRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit();
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $request_id = $data['request_id'] ?? null;
        $reason = $data['reason'] ?? 'No reason provided';

        if (!$request_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid request']);
            exit();
        }

        try {
            require_once 'models/RequestModel.php';
            $requestModel = new RequestModel($this->db);
            
            if ($requestModel->declineRequest($request_id, $reason)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to decline request']);
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit();
    }

    public function updateShelterSettings() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit();
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $host_id = $_SESSION['user_id'];

        try {
            $sql = "UPDATE shelter SET 
                    shelter_name = ?, 
                    max_capacity = ?,
                    contact_number = ?,
                    location = ?,
                    amenities = ?
                    WHERE host_id = ?";
            
            $amenities = json_encode($data['amenities'] ?? []);
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['shelter_name'],
                $data['max_capacity'],
                $data['contact_number'],
                $data['location'],
                $amenities,
                $host_id
            ]);

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit();
    }

    public function updateShelterStock() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit();
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $host_id = $_SESSION['user_id'];

        try {
            $supplies = json_encode($data['supplies'] ?? []);
            
            $stmt = $this->db->prepare("UPDATE shelter SET supplies = ? WHERE host_id = ?");
            $stmt->execute([$supplies, $host_id]);

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit();
    }

    public function toggleShelterStatus() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit();
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $shelter_id = $data['shelter_id'] ?? null;
        $is_active = $data['is_active'] ?? 0;

        if (!$shelter_id) {
            echo json_encode(['success' => false, 'message' => 'Invalid shelter']);
            exit();
        }

        try {
            $stmt = $this->db->prepare("UPDATE shelter SET is_active = ? WHERE shelter_id = ?");
            $stmt->execute([$is_active ? 1 : 0, $shelter_id]);

            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit();
    }

    public function manualCheckIn() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit();
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $checkinId = $data['checkin_id'] ?? null;

        if (!$checkinId) {
            echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            exit();
        }

        try {
            $stmt = $this->db->prepare("
                SELECT r.*, u.first_name, u.last_name 
                FROM requests r 
                JOIN users u ON r.user_id = u.user_id 
                WHERE r.id = ? AND r.status = 'approved'
            ");
            $stmt->execute([$checkinId]);
            $request = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$request) {
                echo json_encode(['success' => false, 'message' => 'Request not found or not approved']);
                exit();
            }

            $updateStmt = $this->db->prepare("UPDATE requests SET is_notified = 1 WHERE id = ?");
            $updateStmt->execute([$checkinId]);

            $name = $request['first_name'] . ' ' . $request['last_name'];
            
            echo json_encode([
                'success' => true, 
                'name' => $name,
                'message' => 'Check-in successful'
            ]);

        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit();
    }

    // =============================================
    // CHECK-IN & OCCUPANTS MANAGEMENT
    // =============================================

    /**
     * Verify approval code before check-in
     */
    public function verifyApprovalCode() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $approval_code = $data['approval_code'] ?? null;
        $shelter_id = $data['shelter_id'] ?? null;

        if (!$approval_code || !$shelter_id) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            exit();
        }

        try {
            require_once 'models/OccupantModel.php';
            $occupantModel = new OccupantModel($this->db);
            
            $result = $occupantModel->verifyApprovalCode($approval_code, $shelter_id);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * Process check-in with approval code
     */
    public function processCheckIn() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $approval_code = $data['approval_code'] ?? null;
        $shelter_id = $data['shelter_id'] ?? null;

        if (!$approval_code || !$shelter_id) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            exit();
        }

        try {
            require_once 'models/OccupantModel.php';
            $occupantModel = new OccupantModel($this->db);
            
            $result = $occupantModel->checkIn($approval_code, $shelter_id);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * Get all occupants for a shelter
     */
    public function getOccupants() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $shelter_id = $_GET['shelter_id'] ?? null;

        if (!$shelter_id) {
            echo json_encode(['success' => false, 'message' => 'Shelter ID required']);
            exit();
        }

        try {
            require_once 'models/OccupantModel.php';
            $occupantModel = new OccupantModel($this->db);
            
            $occupants = $occupantModel->getOccupantsByShelter($shelter_id);
            $count = $occupantModel->getOccupantCount($shelter_id);
            
            echo json_encode([
                'success' => true,
                'occupants' => $occupants,
                'count' => $count
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * Evacuee self-checkout
     */
    public function evacueeCheckOut() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit();
        }

        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        try {
            require_once 'models/OccupantModel.php';
            $occupantModel = new OccupantModel($this->db);
            $result = $occupantModel->checkOut($_SESSION['user_id']);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }

    /**
     * Remove an occupant
     */
    public function removeOccupant() {
        header('Content-Type: application/json');
        
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit();
        }

        $data = json_decode(file_get_contents("php://input"), true);
        $occupant_id = $data['occupant_id'] ?? null;
        $shelter_id = $data['shelter_id'] ?? null;

        if (!$occupant_id || !$shelter_id) {
            echo json_encode(['success' => false, 'message' => 'Missing parameters']);
            exit();
        }

        try {
            require_once 'models/OccupantModel.php';
            $occupantModel = new OccupantModel($this->db);
            
            $result = $occupantModel->removeOccupant($occupant_id, $shelter_id);
            echo json_encode($result);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit();
    }
}