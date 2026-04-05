<?php
/**
 * views/partials/nav_portal.php
 * UPDATED: Role-Based Logo Redirect + Admin Navigation
 */
$userRole = $_SESSION['role'] ?? 'Citizen';

// Logo link: Admin → admin_landing, everyone else → home
$logoHref = ($userRole === 'Admin') ? 'index.php?route=admin_landing' : 'index.php';
?>
<header class="top-nav">
    <div class="nav-wrapper">
        <a href="<?= $logoHref ?>" class="logo">
            <img src="assets/img/LOGO.png" 
                 alt="Dangpanan Shield" 
                 style="width: 36px; height: 36px; object-fit: contain;">
            <div class="logo-text">DANG<span>PANAN</span></div>
        </a>

        <nav class="main-menu" id="portalNavMenu">
            <?php if ($userRole === 'Admin'): ?>
                <a href="index.php?route=admin_dashboard" class="nav-link">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Admin Panel</span>
                </a>
            <?php else: ?>
                <a href="index.php?route=evacuee_portal" class="nav-link">
                    <i data-lucide="users"></i>
                    <span>Evacuee</span>
                </a>
                <a href="index.php?route=host_portal" class="nav-link">
                    <i data-lucide="home"></i>
                    <span>Host</span>
                </a>
                <a href="index.php?route=maps" class="nav-link">
                    <i data-lucide="map"></i>
                    <span>Maps</span>
                </a>
                <a href="index.php?route=alerts" class="nav-link">
                    <i data-lucide="bell"></i>
                    <span>Alerts</span>
                </a>
                <a href="index.php?route=messages" class="nav-link" style="position:relative;">
                    <i data-lucide="message-circle"></i>
                    <span>Messages</span>
                    <span class="nav-chat-badge" id="navChatBadge" style="display:none;"></span>
                </a>
            <?php endif; ?>
        </nav>

        <div class="nav-controls">
            <div class="user-profile-wrapper desktop-only">
                <div class="user-pill">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)); ?>
                    </div>
                    <span class="user-name"><?php echo htmlspecialchars(explode(' ', $_SESSION['name'] ?? 'User')[0]); ?></span>
                    <i data-lucide="chevron-down" class="chevron-icon"></i>
                </div>
                <div class="dropdown-content" id="userMenuContent">
                    <a href="index.php?route=profile">
                        <i data-lucide="user"></i> My Profile
                    </a>
                    <a href="index.php?route=settings">
                        <i data-lucide="settings"></i> Settings
                    </a>
                    <hr>
                    <a href="index.php?route=logout" class="logout-link">
                        <i data-lucide="log-out"></i> Sign Out
                    </a>
                </div>
            </div>

            <button class="hamburger-btn" id="drawerTrigger" aria-label="Open Menu">
                <i data-lucide="menu"></i>
            </button>
        </div>
    </div>
</header>

<div class="drawer-overlay" id="drawerOverlay"></div>

<aside class="mobile-drawer" id="mobileDrawer">
    <div class="drawer-header">
        <div class="drawer-user-info">
            <div class="user-avatar large">
                <?php echo strtoupper(substr($_SESSION['name'] ?? 'U', 0, 1)); ?>
            </div>
            <div class="user-details">
                <span class="user-name-drawer"><?php echo htmlspecialchars(explode(' ', $_SESSION['name'] ?? 'User')[0]); ?></span>
                <span class="user-role-drawer"><?php echo $userRole; ?> | ID: #<?php echo $_SESSION['user_id'] ?? '000'; ?></span>
            </div>
        </div>
        <button class="close-drawer-btn" id="drawerClose" aria-label="Close Menu">
            <i data-lucide="x"></i>
        </button>
    </div>

    <nav class="drawer-nav">
        <span class="drawer-label">MENU</span>
        
        <?php if ($userRole === 'Admin'): ?>
            <a href="index.php?route=admin_dashboard">
                <i data-lucide="layout-dashboard"></i> Admin Dashboard
            </a>
        <?php else: ?>
            <a href="index.php?route=evacuee_portal">
                <i data-lucide="users"></i> Evacuee Portal
            </a>
            <a href="index.php?route=host_portal">
                <i data-lucide="home"></i> Host Management
            </a>
            <a href="index.php?route=maps">
                <i data-lucide="map"></i> Live Maps
            </a>
            <a href="index.php?route=alerts">
                <i data-lucide="bell"></i> Emergency Alerts
            </a>
            <a href="index.php?route=messages">
                <i data-lucide="message-circle"></i> Messages
            </a>
        <?php endif; ?>

        <div class="drawer-divider"></div>

        <span class="drawer-label">ACCOUNT</span>
        <a href="index.php?route=profile">
            <i data-lucide="user"></i> My Profile
        </a>
        <a href="index.php?route=settings">
            <i data-lucide="settings"></i> Settings
        </a>
        
        <div class="drawer-footer">
            <a href="index.php?route=logout" class="drawer-logout">
                <i data-lucide="log-out"></i> Sign Out
            </a>
        </div>
    </nav>
</aside>