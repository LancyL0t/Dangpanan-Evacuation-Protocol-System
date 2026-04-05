<?php
// controllers/ReportController.php

require_once 'pdflibrary/fpdf.php';
require_once 'models/UserModel.php';
require_once 'models/ShelterModel.php';
require_once 'models/RequestModel.php';
require_once 'models/OccupantModel.php';
require_once 'models/AlertModel.php';

class ReportController {
    private $db;
    private $userModel;
    private $shelterModel;
    private $requestModel;
    private $occupantModel;
    private $alertModel;

    public function __construct($db) {
        $this->db = $db;
        $this->userModel = new UserModel($db);
        $this->shelterModel = new ShelterModel($db);
        $this->requestModel = new RequestModel($db);
        $this->occupantModel = new OccupantModel($db);
        $this->alertModel = new AlertModel($db);
    }

    /**
     * Generate Host Certification (Landscape A4)
     */
    public function generateHostCertification($user_id) {
        $currentUserId = SessionManager::getUserId();
        $currentUserRole = SessionManager::getUserRole();

        if (!SessionManager::isAdmin()) {
            if ($currentUserId != $user_id || $currentUserRole !== 'Host') {
                die("Unauthorized access.");
            }
        }

        $user = $this->userModel->getById($user_id);
        if (!$user) die("User not found.");
        if (!$user->getIsVerified()) {
            die("User is not verified yet. Certification is only available for verified hosts.");
        }

        $pdf = new FPDF('L', 'mm', 'A4');
        $pdf->AddPage();
        require 'reports/host_certification.php';
        $pdf->Output('I', 'Host_Certification_' . $user_id . '.pdf');
    }

    /**
     * Generate Evacuation Pass (100mm x 150mm)
     */
    public function generateEvacuationPass($request_id) {
        $user_id = SessionManager::getUserId();
        if (!$user_id) die("Unauthorized access.");

        $request = SessionManager::isAdmin() 
            ? $this->requestModel->getRequestById($request_id)
            : $this->requestModel->getRequestWithDetails($request_id, $user_id);

        if (!$request) die("Request not found or unauthorized.");

        $pdf = new FPDF('P', 'mm', array(100, 150));
        $pdf->AddPage();
        require 'reports/evacuation_pass.php';
        $pdf->Output('I', 'Evacuation_Pass_' . $request['approval_code'] . '.pdf');
    }

    /**
     * Generate Users Report
     */
    public function generateUsersReport() {
        SessionManager::requireAdmin();
        $users = $this->userModel->getAll();
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        require 'reports/user_list.php';
        $pdf->Output('I', 'DANGPANAN_Users_' . date('Ymd') . '.pdf');
    }

    /**
     * Generate Alerts Report
     */
    public function generateAlertsReport() {
        SessionManager::requireAdmin();
        $alerts = $this->alertModel->getAll();
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        require 'reports/alert_list.php';
        $pdf->Output('I', 'DANGPANAN_Alerts_' . date('Ymd') . '.pdf');
    }

    /**
     * Generate Verification Report
     */
    public function generateVerificationReport() {
        SessionManager::requireAdmin();
        $stmt = $this->db->query("SELECT * FROM users WHERE role='Host' AND is_verified=0 ORDER BY created_at DESC");
        $queue = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        require 'reports/verification_queue.php';
        $pdf->Output('I', 'DANGPANAN_Verification_' . date('Ymd') . '.pdf');
    }

    /**
     * Generate Requests Report
     */
    public function generateRequestsReport() {
        SessionManager::requireAdmin();
        $stmt = $this->db->query("SELECT r.*, u.first_name, u.last_name, s.shelter_name FROM requests r JOIN users u ON r.user_id=u.user_id JOIN shelter s ON r.shelter_id=s.shelter_id ORDER BY r.created_at DESC");
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        require 'reports/request_list.php';
        $pdf->Output('I', 'DANGPANAN_Requests_' . date('Ymd') . '.pdf');
    }

    /**
     * Generate Occupants Report
     */
    public function generateOccupantsReport($shelter_id = null) {
        SessionManager::requireAdmin();
        $query = "SELECT o.*, u.first_name, u.last_name, s.shelter_name 
                  FROM occupants o 
                  JOIN users u ON o.user_id = u.user_id 
                  JOIN shelter s ON o.shelter_id = s.shelter_id 
                  WHERE o.status = 'active'";
        $params = [];

        if ($shelter_id) {
            $query .= " AND o.shelter_id = ?";
            $params[] = $shelter_id;
        }

        $query .= " ORDER BY s.shelter_name ASC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $occupants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        require 'reports/occupant_list.php';
        
        $filename = $shelter_id ? "Shelter_Occupants_{$shelter_id}_" : "DANGPANAN_Occupants_";
        $pdf->Output('I', $filename . date('Ymd') . '.pdf');
    }

    /**
     * Generate Logs Report
     */
    public function generateLogsReport() {
        SessionManager::requireAdmin();
        $stmt = $this->db->query("SELECT l.*, CONCAT(u.first_name,' ',u.last_name) as user_name FROM system_logs l LEFT JOIN users u ON l.user_id=u.user_id ORDER BY l.created_at DESC LIMIT 500");
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        require 'reports/system_logs.php';
        $pdf->Output('I', 'DANGPANAN_Logs_' . date('Ymd') . '.pdf');
    }

    /**
     * Generate Shelters Report
     */
    public function generateSheltersReport() {
        SessionManager::requireAdmin();
        $stmt = $this->db->query("SELECT s.*, u.first_name, u.last_name FROM shelter s LEFT JOIN users u ON s.host_id = u.user_id ORDER BY s.shelter_name ASC");
        $shelters = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        require 'reports/shelter_list.php';
        $pdf->Output('I', 'DANGPANAN_Shelters_' . date('Ymd') . '.pdf');
    }

    /**
     * Generate System Summary
     */
    public function generateSystemSummary() {
        SessionManager::requireAdmin();
        
        $stats = [
            'totalUsers'    => (int)$this->db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'totalShelters' => (int)$this->db->query("SELECT COUNT(*) FROM shelter")->fetchColumn(),
            'totalAlerts'   => (int)$this->db->query("SELECT COUNT(*) FROM alerts WHERE is_active=1")->fetchColumn()
        ];
        $totalOccupants = (int)$this->db->query("SELECT COALESCE(SUM(group_size),0) FROM occupants WHERE status='active'")->fetchColumn();
        $totalCapacity  = (int)$this->db->query("SELECT SUM(max_capacity) FROM shelter WHERE is_active=1")->fetchColumn();
        $db = $this->db;

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        require 'reports/system_summary.php';
        $pdf->Output('I', 'DANGPANAN_System_Summary_' . date('Ymd') . '.pdf');
    }
}
