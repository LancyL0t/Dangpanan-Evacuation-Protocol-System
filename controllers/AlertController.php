<?php
require_once 'controllers/BaseController.php';
require_once 'models/AlertModel.php';

class AlertController extends BaseController {
    private $db;
    private $alertModel;

    public function __construct($db) {
        $this->db = $db;
        $this->alertModel = new AlertModel($db);
    }

    private function json($data, $code = 200) {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($data);
        exit();
    }



    public function getAll() {
        try { $this->json(['success'=>true,'alerts'=>$this->alertModel->getAll()]); }
        catch(Exception $e) { $this->json(['success'=>false,'message'=>$e->getMessage()],500); }
    }

    public function getActive() {
        try { $this->json(['success'=>true,'alerts'=>$this->alertModel->getAll(true),'counts'=>$this->alertModel->getCounts()]); }
        catch(Exception $e) { $this->json(['success'=>false,'message'=>$e->getMessage()],500); }
    }

    public function create() {
        if($_SERVER['REQUEST_METHOD']!=='POST'){$this->json(['success'=>false,'message'=>'Method not allowed'],405);}
        $data = json_decode(file_get_contents("php://input"),true);
        if(empty($data['title'])||empty($data['type'])){$this->json(['success'=>false,'message'=>'Title and type required']);}
        try {
            if($this->alertModel->create($data)){
                $this->logAction($this->db, 'Created alert: '.$data['title']);
                $this->json(['success'=>true,'message'=>'Alert created']);
            } else { $this->json(['success'=>false,'message'=>'Failed to create']); }
        } catch(Exception $e){$this->json(['success'=>false,'message'=>$e->getMessage()]);}
    }

    public function update() {
        if($_SERVER['REQUEST_METHOD']!=='POST'){$this->json(['success'=>false,'message'=>'Method not allowed'],405);}
        $data = json_decode(file_get_contents("php://input"),true);
        if(empty($data['alert_id'])){$this->json(['success'=>false,'message'=>'Alert ID required']);}
        try {
            if($this->alertModel->update($data['alert_id'],$data)){
                $this->logAction($this->db, 'Updated alert ID: '.$data['alert_id']);
                $this->json(['success'=>true,'message'=>'Alert updated']);
            } else { $this->json(['success'=>false,'message'=>'Failed to update']); }
        } catch(Exception $e){$this->json(['success'=>false,'message'=>$e->getMessage()]);}
    }

    public function delete() {
        if($_SERVER['REQUEST_METHOD']!=='POST'){$this->json(['success'=>false,'message'=>'Method not allowed'],405);}
        $data = json_decode(file_get_contents("php://input"),true);
        if(empty($data['alert_id'])){$this->json(['success'=>false,'message'=>'Alert ID required']);}
        try {
            if($this->alertModel->delete($data['alert_id'])){
                $this->logAction($this->db, 'Deleted alert ID: '.$data['alert_id']);
                $this->json(['success'=>true,'message'=>'Alert deleted']);
            } else { $this->json(['success'=>false,'message'=>'Failed to delete']); }
        } catch(Exception $e){$this->json(['success'=>false,'message'=>$e->getMessage()]);}
    }

    public function getAllRequests() {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, u.first_name, u.last_name, u.email, u.phone_number,
                       s.shelter_name, s.location as shelter_location
                FROM requests r
                JOIN users u ON r.user_id = u.user_id
                JOIN shelter s ON r.shelter_id = s.shelter_id
                ORDER BY r.created_at DESC
            ");
            $stmt->execute();
            $this->json(['success'=>true,'requests'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch(Exception $e){$this->json(['success'=>false,'message'=>$e->getMessage()],500);}
    }

    public function forceApproveRequest() {
        if($_SERVER['REQUEST_METHOD']!=='POST'){$this->json(['success'=>false,'message'=>'Method not allowed'],405);}
        $data = json_decode(file_get_contents("php://input"),true);
        if(empty($data['request_id'])){$this->json(['success'=>false,'message'=>'Request ID required']);}
        try {
            require_once 'models/RequestModel.php';
            $rm = new RequestModel($this->db);
            if($rm->approveRequest($data['request_id'])){
                $this->logAction($this->db, 'Admin force-approved request ID: '.$data['request_id']);
                $this->json(['success'=>true,'message'=>'Request approved']);
            } else { $this->json(['success'=>false,'message'=>'Failed']); }
        } catch(Exception $e){$this->json(['success'=>false,'message'=>$e->getMessage()]);}
    }

    public function forceDeclineRequest() {
        if($_SERVER['REQUEST_METHOD']!=='POST'){$this->json(['success'=>false,'message'=>'Method not allowed'],405);}
        $data = json_decode(file_get_contents("php://input"),true);
        if(empty($data['request_id'])){$this->json(['success'=>false,'message'=>'Request ID required']);}
        try {
            require_once 'models/RequestModel.php';
            $rm = new RequestModel($this->db);
            if($rm->declineRequest($data['request_id'],'Admin override')){
                $this->logAction($this->db, 'Admin force-declined request ID: '.$data['request_id']);
                $this->json(['success'=>true,'message'=>'Request declined']);
            } else { $this->json(['success'=>false,'message'=>'Failed']); }
        } catch(Exception $e){$this->json(['success'=>false,'message'=>$e->getMessage()]);}
    }

    public function getAllOccupants() {
        try {
            $stmt = $this->db->prepare("
                SELECT o.*, u.first_name, u.last_name, u.email, u.phone_number, s.shelter_name
                FROM occupants o
                JOIN users u ON o.user_id = u.user_id
                JOIN shelter s ON o.shelter_id = s.shelter_id
                WHERE o.status = 'active'
                ORDER BY o.checked_in_at DESC
            ");
            $stmt->execute();
            $this->json(['success'=>true,'occupants'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch(Exception $e){$this->json(['success'=>false,'message'=>$e->getMessage()],500);}
    }

    public function forceCheckout() {
        if($_SERVER['REQUEST_METHOD']!=='POST'){$this->json(['success'=>false,'message'=>'Method not allowed'],405);}
        $data = json_decode(file_get_contents("php://input"),true);
        if(empty($data['occupant_id'])){$this->json(['success'=>false,'message'=>'Occupant ID required']);}
        try {
            $stmt = $this->db->prepare("UPDATE occupants SET status='checked_out', checked_out_at=NOW() WHERE occupant_id=?");
            $stmt->execute([$data['occupant_id']]);
            // Also update shelter capacity
            $occ = $this->db->prepare("SELECT shelter_id, group_size, request_id FROM occupants WHERE occupant_id=?");
            $occ->execute([$data['occupant_id']]);
            $o = $occ->fetch(PDO::FETCH_ASSOC);
            if($o){
                $this->db->prepare("UPDATE shelter SET current_capacity=GREATEST(0,current_capacity-?) WHERE shelter_id=?")->execute([$o['group_size'],$o['shelter_id']]);
                $this->db->prepare("UPDATE requests SET status='completed' WHERE id=?")->execute([$o['request_id']]);
            }
            $this->logAction($this->db, 'Admin force-checked-out occupant ID: '.$data['occupant_id']);
            $this->json(['success'=>true,'message'=>'Occupant checked out']);
        } catch(Exception $e){$this->json(['success'=>false,'message'=>$e->getMessage()]);}
    }

    public function getLogs() {
        try {
            $stmt = $this->db->prepare("
                SELECT l.*, CONCAT(u.first_name,' ',u.last_name) as user_name
                FROM system_logs l
                LEFT JOIN users u ON l.user_id = u.user_id
                ORDER BY l.created_at DESC LIMIT 300
            ");
            $stmt->execute();
            $this->json(['success'=>true,'logs'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
        } catch(Exception $e){$this->json(['success'=>false,'message'=>$e->getMessage()],500);}
    }

    public function exportCSV() {
        $type = $_GET['export_type'] ?? 'users';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="dangpanan_'.$type.'_'.date('Ymd').'.csv"');
        $out = fopen('php://output','w');
        try {
            if($type==='users'){
                fputcsv($out,['ID','Name','Email','Phone','Role','Host Status','Created']);
                $stmt = $this->db->query("SELECT user_id, CONCAT(first_name,' ',last_name), email, phone_number, role, host_status, created_at FROM users ORDER BY created_at DESC");
                while($r=$stmt->fetch(PDO::FETCH_NUM)) fputcsv($out,$r);
            } elseif($type==='shelters'){
                fputcsv($out,['ID','Name','Location','Max Capacity','Current Occupancy','Status']);
                $stmt = $this->db->query("SELECT shelter_id, shelter_name, location, max_capacity, current_capacity, IF(is_active,'Active','Inactive') FROM shelter");
                while($r=$stmt->fetch(PDO::FETCH_NUM)) fputcsv($out,$r);
            } elseif($type==='requests'){
                fputcsv($out,['ID','Evacuee','Shelter','Group Size','Status','Created']);
                $stmt = $this->db->query("SELECT r.id, CONCAT(u.first_name,' ',u.last_name), s.shelter_name, r.group_size, r.status, r.created_at FROM requests r JOIN users u ON r.user_id=u.user_id JOIN shelter s ON r.shelter_id=s.shelter_id ORDER BY r.created_at DESC");
                while($r=$stmt->fetch(PDO::FETCH_NUM)) fputcsv($out,$r);
            }
        } catch(Exception $e){}
        fclose($out); exit();
    }

    /**
     * showAlertsPage — loads data and renders the alerts view.
     * Previously the auth + DB logic was embedded in views/shelter/alerts.php.
     */
    public function showAlertsPage() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        $alerts      = $this->alertModel->getAll(true);
        $counts      = $this->alertModel->getCounts();
        $totalActive = array_sum($counts);

        // Shelter summary for sidebar (moved from inline query in view)
        $sStmt = $this->db->query("SELECT COUNT(*) as c, SUM(max_capacity-current_capacity) as avail FROM shelter WHERE is_active=1");
        $sData = $sStmt->fetch(\PDO::FETCH_ASSOC);

        require 'views/shelter/alerts.php';
    }

    /**
     * adminDashboard — prepares data and renders the admin dashboard view.
     * Previously the logic was embedded in views/auth/admin_dashboard.php.
     */
    public function adminDashboard() {
        if (session_status() === PHP_SESSION_NONE) session_start();

        require_once 'models/UserModel.php';
        require_once 'models/ShelterModel.php';

        $userModel    = new UserModel($this->db);
        $shelterModel = new ShelterModel($this->db);

        $totalUsers   = $userModel->getTotalCount();
        $evacueeCount = $userModel->getCountByRole('Citizen');
        $shelterStats = $shelterModel->getStats();
        $alertCounts  = $this->alertModel->getCounts();
        $totalAlerts  = array_sum($alertCounts);

        $pendStmt     = $this->db->query("SELECT COUNT(*) FROM requests WHERE status='pending'");
        $pendingCount = (int)$pendStmt->fetchColumn();

        $occStmt        = $this->db->query("SELECT COALESCE(SUM(group_size),0) FROM occupants WHERE status='active'");
        $totalOccupants = (int)$occStmt->fetchColumn();

        $utilization = $shelterStats['total_capacity'] > 0
            ? round(($shelterStats['total_occupancy'] / $shelterStats['total_capacity']) * 100) : 0;

        // Shelter capacity bars data (moved from inline query in view)
        $shelterBarsStmt = $this->db->query("SELECT shelter_name, current_capacity, max_capacity FROM shelter WHERE is_active=1 ORDER BY current_capacity DESC LIMIT 8");
        $shelterBars     = $shelterBarsStmt->fetchAll(\PDO::FETCH_ASSOC);

        require 'views/auth/admin_dashboard.php';
    }

    /**
     * getShelterStatsJson — returns latest capacity data for all active shelters as JSON.
     * Used for real-time dashboard updates without page refreshes.
     */
    public function getShelterStatsJson() {
        try {
            $stmt = $this->db->query("SELECT shelter_id, shelter_name, current_capacity, max_capacity FROM shelter WHERE is_active=1 ORDER BY current_capacity DESC");
            $this->json(['success'=>true, 'shelters'=>$stmt->fetchAll(\PDO::FETCH_ASSOC)]);
        } catch(Exception $e){$this->json(['success'=>false,'message'=>$e->getMessage()], 500);}
    }

    /**
     * repairCapacities — syncs shelter.current_capacity with the actual sum of active occupants.
     * Fixes data discrepancies between tables.
     */
    public function repairCapacities() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
            $this->json(['success'=>false, 'message'=>'Unauthorized'], 403);
        }

        try {
            $this->db->beginTransaction();
            // Reset all to 0 first
            $this->db->query("UPDATE shelter SET current_capacity = 0");
            
            // Calculate and Update from active occupants
            $stmt = $this->db->query("
                SELECT shelter_id, SUM(group_size) as total 
                FROM occupants 
                WHERE status = 'active' 
                GROUP BY shelter_id
            ");
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $update = $this->db->prepare("UPDATE shelter SET current_capacity = ? WHERE shelter_id = ?");
            foreach ($results as $r) {
                $update->execute([$r['total'], $r['shelter_id']]);
            }

            $this->db->commit();
            $this->logAction($this->db, 'Admin repaired shelter capacities via sync utility.');
            $this->json(['success'=>true, 'message'=>'Database capacity counts synchronized successfully.']);
        } catch(Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->json(['success'=>false, 'message'=>$e->getMessage()], 500);
        }
    }
}
