<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
// Ensure $isLoggedIn reflects the session state
$isLoggedIn = isset($_SESSION['user_id']) ? 'true' : 'false';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANGPANAN | Disaster Coordination Protocol</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/landing.js" defer></script>
</head>
<body>
    <header>
        <div class="logo">
            <img src="assets/img/LOGO.png" 
                 alt="Dangpanan Shield" 
                 style="width: 40px; height: 40px; object-fit: contain;">
            <span>DANG<span style="color:var(--red-alert)">PANAN</span></span>
        </div>

        <nav class="nav-menu desktop-only">
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="user-session-info">
                    <span>Welcome, <strong><?php echo htmlspecialchars(explode(' ', $_SESSION['name'])[0]); ?></strong></span>
                    <button class="btn-secure" onclick="confirmLogout(event)">Sign Out</button>
                </div>
            <?php else: ?>
                <button class="btn-secure" onclick="location.href='index.php?route=login'">Secure Login</button>
            <?php endif; ?>
        </nav>

        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-profile-wrapper mobile-tablet-only" onclick="toggleUserDropdown()">
                <div class="user-pill">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <span class="user-name-pill">Welcome, <strong><?php echo htmlspecialchars(explode(' ', $_SESSION['name'])[0]); ?></strong></span>
                    <i data-lucide="chevron-down" class="chevron-icon"></i>
                </div>
                
                <div class="dropdown-content" id="userDropdown">
                    <a href="index.php?route=logout" class="logout-link" onclick="confirmLogout(event)">
                        <i data-lucide="log-out"></i>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <button class="btn-secure mobile-tablet-only" onclick="location.href='index.php?route=login'">Login</button>
        <?php endif; ?>
    </header>

    <main>
        <section class="hero">
            <div class="status-badge"><span class="pulse-dot"></span> SYSTEM STATUS: ACTIVE ALERT</div>
            <h1>EVACUATION<br><span style="color:var(--red-alert)">PROTOCOL</span></h1>
            <p>Advanced coordination platform for rapid disaster response and shelter management in Bacolod City.</p>
        </section>

        <div class="grid-container">
            <div class="card evacuee-card">
                <div class="icon-wrapper">
                    <i data-lucide="home" style="width: 50px; height: 50px;"></i>
                </div>
                <h2 class="card-title">EVACUEE</h2>
                <p class="card-text">Find immediate shelter, view real-time safe zones, and request emergency assistance.</p>
                <button class="btn-action" onclick="checkAuth('evacuee_portal', <?php echo $isLoggedIn; ?>)">Access Portal</button>
            </div>

            <div class="card host-card">
                <div class="icon-wrapper">
                    <i data-lucide="clipboard-list" style="width: 50px; height: 50px;"></i>
                </div>
                <h2 class="card-title">HOST</h2>
                <p class="card-text">Register available space, manage shelter capacity, and coordinate with local authorities.</p>
                <button class="btn-action" onclick="checkAuth('host_portal', <?php echo $isLoggedIn; ?>)">Manage Shelter</button>
            </div>
        </div>
    </main>
    
    <?php require 'views/partials/footer.php'; ?>

    <div id="authModal" class="modal-overlay">
        <div class="modal-content">
            <h3>Authentication Required</h3>
            <p>To access the evacuation network or host a shelter, you must first authenticate your identity.</p>
            <div class="modal-actions">
                <button class="btn-modal-login" onclick="location.href='index.php?route=login'">LOG IN</button>
                <button class="btn-modal-reg" onclick="location.href='index.php?route=register'">REGISTER</button>
            </div>
           <button class="btn-cancel" onclick="closeAuthModal()">Cancel</button>
        </div>
    </div>

</body>
</html>