<?php
// Admin Landing Page - Requires Admin role
require_once 'config/SessionManager.php';
SessionManager::start();
SessionManager::requireAdmin();

$adminName = $_SESSION['name'] ?? 'Administrator';
$adminFirstName = explode(' ', $adminName)[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANGPANAN | Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/admin_landing.css">
</head>
<body>

    <!-- Header -->
    <header>
        <a href="index.php?route=admin_landing" class="logo">
            <div class="logo-icon"><i data-lucide="shield"></i></div>
            <span class="logo-name">DANG<span>PANAN</span></span>
        </a>
        <div class="nav-right">
            <span class="badge-admin">
                <i data-lucide="shield-check"></i>
                Admin
            </span>
            <a href="index.php?route=logout" class="btn-secure">
                <i data-lucide="log-out"></i> Sign Out
            </a>
        </div>
    </header>

    <!-- Main -->
    <main>

        <!-- Hero -->
        <div class="hero">
            <div class="status-badge">
                <span class="pulse-dot"></span>
                Admin Session Active
            </div>

            <h1>
                Welcome,
                <span class="accent"><?= htmlspecialchars($adminFirstName) ?></span>
            </h1>

            <p>
                You are logged in as <strong style="color:#fff;font-weight:600;">System Administrator</strong>.
                Manage users, shelters, alerts, and evacuation operations.
            </p>
        </div>

        <!-- Cards -->
        <div class="grid-container">

            <!-- Dashboard — full width -->
            <div class="card card-full">
                <div class="card-left">
                    <div class="icon-wrapper icon-red">
                        <i data-lucide="layout-dashboard"></i>
                    </div>
                    <div>
                        <div class="card-title">Admin Dashboard</div>
                        <div class="card-text">Full system overview — users, shelters, alerts, and live operations.</div>
                    </div>
                </div>
                <a href="index.php?route=admin_dashboard" class="btn-action btn-primary">
                    Go to Dashboard <i data-lucide="arrow-right" style="width:16px;height:16px;"></i>
                </a>
            </div>

            <!-- Quick links -->
            <a href="index.php?route=admin_dashboard#users" class="card card-sm" style="text-decoration:none;color:inherit;">
                <div class="icon-wrapper icon-neutral"><i data-lucide="users"></i></div>
                <div class="card-title">Manage Users</div>
                <div class="card-text">View, edit, and moderate registered accounts.</div>
            </a>

            <a href="index.php?route=admin_dashboard#shelters" class="card card-sm" style="text-decoration:none;color:inherit;">
                <div class="icon-wrapper icon-neutral"><i data-lucide="home"></i></div>
                <div class="card-title">Shelters</div>
                <div class="card-text">Monitor capacity and shelter status in real time.</div>
            </a>

            <a href="index.php?route=admin_dashboard#alerts" class="card card-sm" style="text-decoration:none;color:inherit;">
                <div class="icon-wrapper icon-neutral"><i data-lucide="megaphone"></i></div>
                <div class="card-title">Alerts</div>
                <div class="card-text">Broadcast emergency alerts and notifications.</div>
            </a>

            <a href="index.php?route=settings" class="card card-sm" style="text-decoration:none;color:inherit;">
                <div class="icon-wrapper icon-neutral"><i data-lucide="settings"></i></div>
                <div class="card-title">Settings</div>
                <div class="card-text">Configure system preferences and admin options.</div>
            </a>

        </div>

    </main>

    <footer>
        DANGPANAN Disaster Coordination System &nbsp;·&nbsp; Admin Portal
    </footer>

    <script src="assets/js/admin_landing.js" defer></script>
</body>
</html>