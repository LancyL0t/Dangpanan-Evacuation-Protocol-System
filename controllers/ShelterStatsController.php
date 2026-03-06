<?php
require_once 'config/auth_guard.php';
protect_page();

class ShelterStatsController {
    private $db;
    private $user_id;

    public function __construct($db) {
        $this->db = $db;
        $this->user_id = $_SESSION['user_id'] ?? null;
    }

    public function getStats() {
        if (!$this->user_id) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        try {
            // Get shelter data
            $stmt = $this->db->prepare("
                SELECT * FROM shelters WHERE host_id = ? LIMIT 1
            ");
            $stmt->execute([$this->user_id]);
            $shelter = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$shelter) {
                return ['success' => false, 'error' => 'Shelter not found'];
            }

            // Get request counts
            $stmt = $this->db->prepare("
                SELECT status, COUNT(*) as count 
                FROM evacuation_requests 
                WHERE shelter_id = ? 
                GROUP BY status
            ");
            $stmt->execute([$shelter['id']]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $counts = ['pending' => 0, 'approved' => 0, 'checked_in' => 0, 'declined' => 0];
            foreach ($results as $row) {
                $counts[$row['status']] = (int)$row['count'];
            }

            return [
                'success' => true,
                'data' => [
                    'shelter' => $shelter,
                    'requests' => $counts
                ]
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

// Handle request
header('Content-Type: application/json');
$controller = new ShelterStatsController($db);
echo json_encode($controller->getStats());
?>