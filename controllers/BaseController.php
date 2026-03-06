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


}