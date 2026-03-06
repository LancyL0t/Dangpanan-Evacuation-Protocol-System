<?php
// config/auth_guard.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Protects a page from unauthorized access.
 */
function protect_page() {
    if (!isset($_SESSION['user_id'])) {
        // Redirect to login, not register, if unauthorized
        header("Location: index.php?route=login&error=unauthorized");
        exit();
    }
}
?>