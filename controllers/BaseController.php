<?php
// Dangpanan/controllers/BaseController.php
class BaseController {
    public function protect_page() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['user_id'])) {
            header("Location: index.php?route=login&error=unauthorized");
            exit();
        }
    }

    /**
     * Log an administrative action to the system logs.
     */
    protected function logAction($db, $action) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $uid = $_SESSION['user_id'] ?? null;
        try {
            $stmt = $db->prepare("INSERT INTO system_logs (user_id, action) VALUES (?,?)");
            $stmt->execute([$uid, $action]);
        } catch(Exception $e) {
            // Silently fail if logging fails
        }
    }


}