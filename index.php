<?php
require_once 'config/db.php';
require_once 'config/SessionManager.php';
require_once 'controllers/UserController.php';
require_once 'controllers/ShelterController.php';
require_once 'controllers/AlertController.php';
require_once 'controllers/OtpController.php';

SessionManager::start();

$route = $_GET['route'] ?? 'home';

$userController    = new UserController($pdo);
$shelterController = new ShelterController($pdo);
$alertController   = new AlertController($pdo);
$otpController     = new OtpController();

switch ($route) {
    case 'home':
    // If an admin is already logged in, send them to their dashboard
        if (SessionManager::isAdmin()) {
            header("Location: index.php?route=admin_landing");
            exit();
        }
        include 'views/index.php'; 
        break;

    case 'login':          
        include 'views/auth/login.php'; 
        break;
    case 'authenticate':   $userController->handleLogin(); break;
    case 'register':       include 'views/auth/register.php'; break;
    case 'do_register':    $userController->handleRegistration(); break;
    case 'logout':         $userController->handleLogout(); break;
    case 'otp_send':       $otpController->send(); break;
    case 'otp_verify':     $otpController->verify(); break;

    case 'admin_landing':
    case 'admin':
        SessionManager::requireAdmin();
        include 'views/auth/admin_landing.php';
        break;

    case 'admin_dashboard':
        SessionManager::requireAdmin();
        $alertController->adminDashboard();
        break;

    // User CRUD
    case 'admin-get-users':    $userController->getAllUsers(); break;
    case 'admin-create-user':  $userController->createUser(); break;
    case 'admin-update-user':  $userController->updateUser(); break;
    case 'admin-delete-user':  $userController->deleteUser(); break;
    case 'admin-verify-user':  $userController->verifyUser(); break;

    // Shelter CRUD
    case 'admin-get-shelters':    $shelterController->getAllShelters(); break;
    case 'admin-create-shelter':  $shelterController->createShelter(); break;
    case 'admin-update-shelter':  $shelterController->updateShelterAdmin(); break;
    case 'admin-delete-shelter':  $shelterController->deleteShelter(); break;

    // Alert CRUD
    case 'admin-get-alerts':     $alertController->getAll(); break;
    case 'admin-create-alert':   $alertController->create(); break;
    case 'admin-update-alert':   $alertController->update(); break;
    case 'admin-delete-alert':   $alertController->delete(); break;
    case 'get-active-alerts':    $alertController->getActive(); break;

    // Requests admin
    case 'admin-get-requests':        $alertController->getAllRequests(); break;
    case 'admin-force-approve':       $alertController->forceApproveRequest(); break;
    case 'admin-force-decline':       $alertController->forceDeclineRequest(); break;

    // Occupants admin
    case 'admin-get-occupants':   $alertController->getAllOccupants(); break;
    case 'admin-force-checkout':  $alertController->forceCheckout(); break;

    // Logs & Export
    case 'admin-get-logs':  $alertController->getLogs(); break;
    case 'admin-export':    $alertController->exportCSV(); break;

    // Evacuee portal
    case 'evacuee_portal': $shelterController->evacueeDashboard(); break;

    case 'save_shelter_setup':    $shelterController->saveShelterSetup(); break;
    case 'host_portal':           $shelterController->hostDashboard(); break;
    case 'approve_request':       $shelterController->approveRequest(); break;
    case 'decline_request':       $shelterController->declineRequest(); break;
    case 'update_shelter_settings': $shelterController->updateShelterSettings(); break;
    case 'update_shelter_stock':  $shelterController->updateShelterStock(); break;
    case 'toggle_shelter_status': $shelterController->toggleShelterStatus(); break;

    case 'profile':
        SessionManager::requireLogin();
        $userController->profileDashboard();
        break;
    case 'settings':
        SessionManager::requireLogin();
        $userController->settingsDashboard();
        break;
    case 'alerts':
        SessionManager::requireLogin();
        $alertController->showAlertsPage();
        break;
    case 'maps':
        SessionManager::requireLogin();
        include 'views/shelter/map.php';
        break;

    case 'submit_request':     $shelterController->submitRequest(); break;
    case 'check_updates':      $shelterController->checkUpdates(); break;
    case 'get_user_requests':  $shelterController->getUserRequests(); break;
    case 'get_request_details':  $shelterController->getRequestDetails(); break;
    case 'get_request_progress': $shelterController->getRequestProgress(); break;
    case 'cancel_request':     $shelterController->cancelRequest(); break;

    case 'scanner':
        SessionManager::requireLogin();
        header('Location: index.php?route=host_portal');
        exit;

    case 'shelter-setup':
        SessionManager::requireLogin();
        include 'views/shelter/shelter_setup.php';
        break;

    case 'manual_checkin':        $shelterController->manualCheckIn(); break;
    case 'verify_approval_code':  $shelterController->verifyApprovalCode(); break;
    case 'process_checkin':       $shelterController->processCheckIn(); break;
    case 'get_occupants':         $shelterController->getOccupants(); break;
    case 'remove_occupant':       $shelterController->removeOccupant(); break;
    case 'evacuee_checkout':      $shelterController->evacueeCheckOut(); break;

    case 'relinquish-host-status': $userController->relinquishHostStatus(); break;
    case 'get-host-status':        $userController->getHostStatus(); break;
    case 'restore-host-status':    $userController->restoreHostStatus(); break;

    default:if (SessionManager::isAdmin()) {
            header("Location: index.php?route=admin_landing");
            exit();
        }
        include 'views/index.php'; 
        break;
}
