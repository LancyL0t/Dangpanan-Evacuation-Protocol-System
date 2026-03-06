<?php
/**
 * views/auth/profile.php
 * VIEW ONLY — no logic, no inline CSS, no inline JS.
 * All data is prepared by UserController::profileDashboard()
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANGPANAN | User Profile</title>
    <link rel="stylesheet" href="assets/css/profile.css">
    <link rel="stylesheet" href="assets/css/nav.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/nav.js" defer></script>
</head>
<body class="light-portal">
<?php require 'views/partials/nav_portal.php'; ?>

<main class="portal-container">
    <header class="portal-header">
        <div>
            <h1 class="page-title">User Profile</h1>
            <p class="user-id">
                Account ID: <span>#<?php echo $userData['user_id']; ?></span>
                &nbsp;•&nbsp;<?php echo htmlspecialchars($role); ?>
            </p>
        </div>
    </header>

    <div class="portal-layout">
    <!-- ═══════════════ MAIN COLUMN ═══════════════════════════════════════ -->
    <div class="main-content">

        <!-- Profile Header -->
        <section class="sidebar-panel profile-header-section">
            <div class="profile-header-content">
                <div class="user-avatar profile-avatar-large"><?php echo $initials; ?></div>
                <div class="profile-user-details">
                    <h2><?php echo htmlspecialchars($userData['name']); ?></h2>
                    <span class="live-tag profile-role-badge <?php echo $badgeClass; ?>">
                        <i data-lucide="<?php echo $badgeIcon; ?>"></i>
                        <?php echo htmlspecialchars($role); ?>
                        <?php if ($isHost && $hostStatusLabel): ?>
                            &nbsp;— <?php echo htmlspecialchars($hostStatusLabel); ?>
                        <?php endif; ?>
                    </span>
                    <p class="metric-detail profile-member-since">
                        <i data-lucide="calendar"></i>
                        Member since <?php echo $memberSince; ?>
                    </p>
                </div>
            </div>

            <?php if ($role !== 'Admin'): ?>
            <!-- ── STATUS HERO ──────────────────────────────────────────── -->
            <?php if ($isHost): ?>
            <div class="status-hero sh-host"
                 style="--sh-border:<?php echo $hm['border']; ?>;--sh-bg:<?php echo $hm['bg']; ?>;--sh-color:<?php echo $hm['color']; ?>;--sh-glow:rgba(16,185,129,0.12);">
                <div class="sh-corner">
                    <?php if ($hm['pulse']): ?>
                        <div class="pulse-dot" style="--pd-color:<?php echo $hm['pulse']; ?>;"></div>
                    <?php endif; ?>
                    Host Mode
                </div>
                <div class="sh-inner">
                    <div class="sh-icon"><i data-lucide="<?php echo $hm['icon']; ?>"></i></div>
                    <div class="sh-text">
                        <div class="sh-label"><?php echo htmlspecialchars($hostStatusLabel); ?></div>
                        <div class="sh-sub"><?php echo $hostSubText; ?></div>
                        <?php if ($hostShelterInfo): ?>
                        <div class="sh-pills">
                            <span class="sh-pill"><i data-lucide="home"></i><?php echo htmlspecialchars($hostShelterInfo['shelter_name']); ?></span>
                            <span class="sh-pill"><i data-lucide="users"></i><?php echo "{$curr}/{$max} capacity"; ?></span>
                            <span class="sh-pill"><i data-lucide="<?php echo $hostShelterInfo['is_active'] ? 'circle-check' : 'circle-x'; ?>"></i><?php echo $hostShelterInfo['is_active'] ? 'Active' : 'Inactive'; ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($max > 0): ?>
                <div class="hbar-wrap">
                    <div class="hbar-labels"><span>Shelter Capacity</span><span><?php echo $pct; ?>% filled</span></div>
                    <div class="hbar"><div class="hbar-fill" style="width:<?php echo $pct; ?>%;"></div></div>
                </div>
                <?php endif; ?>
            </div>

            <?php else: // EVACUEE HERO ?>
            <div class="status-hero sh-evacuee"
                 style="--sh-border:<?php echo $em['border']; ?>;--sh-bg:<?php echo $em['bg']; ?>;--sh-color:<?php echo $em['color']; ?>;--sh-glow:<?php echo $em['glow']; ?>;">
                <div class="sh-corner">
                    <?php if ($em['pulse']): ?>
                        <div class="pulse-dot" style="--pd-color:<?php echo $em['pulse']; ?>;"></div>
                    <?php endif; ?>
                    Safety Status
                </div>
                <div class="sh-inner">
                    <div class="sh-icon"><i data-lucide="<?php echo $em['icon']; ?>"></i></div>
                    <div class="sh-text">
                        <div class="sh-label"><?php echo htmlspecialchars($em['label']); ?></div>
                        <div class="sh-sub"><?php echo htmlspecialchars($em['sub']); ?></div>
                        <?php if ($evacueeStatus === 'checked_in' && $shelterInfo): ?>
                        <div class="sh-pills">
                            <span class="sh-pill"><i data-lucide="home"></i><?php echo htmlspecialchars($shelterInfo['shelter_name']); ?></span>
                            <span class="sh-pill"><i data-lucide="calendar-check"></i>Checked in <?php echo date('M j, Y', strtotime($checkinDate)); ?></span>
                        </div>
                        <?php elseif ($evacueeStatus === 'approved' && $shelterInfo): ?>
                        <div class="sh-pills">
                            <span class="sh-pill"><i data-lucide="home"></i><?php echo htmlspecialchars($shelterInfo['shelter_name']); ?></span>
                            <span class="sh-pill"><i data-lucide="key"></i>Approval Code Ready</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($evacueeStatus === 'checked_in' && $shelterInfo): ?>
                <div class="detail-row">
                    <i data-lucide="map-pin"></i>
                    <div>
                        <div class="dr-label">Current Shelter Location</div>
                        <div class="dr-val"><?php echo htmlspecialchars($shelterInfo['location'] ?? $shelterInfo['shelter_name']); ?></div>
                    </div>
                </div>
                <div class="detail-row">
                    <i data-lucide="clock"></i>
                    <div>
                        <div class="dr-label">Check-In Date &amp; Time</div>
                        <div class="dr-val"><?php echo date('F j, Y \a\t g:i A', strtotime($checkinDate)); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; // host/evacuee ?>
            <?php endif; // !Admin ?>
        </section>

        <!-- Personal Information -->
        <section class="sidebar-panel profile-info-section">
            <h3 class="profile-section-title">Personal Information</h3>
            <div class="profile-info-grid">
                <div class="profile-field-group">
                    <label class="profile-field-label"><i data-lucide="user"></i> Full Name</label>
                    <p class="profile-field-value real"><?php echo htmlspecialchars($userData['name'] ?? '—'); ?></p>
                </div>
                <div class="profile-field-group">
                    <label class="profile-field-label"><i data-lucide="mail"></i> Email Address</label>
                    <p class="profile-field-value real"><?php echo htmlspecialchars($userData['email'] ?? '—'); ?></p>
                </div>
                <div class="profile-field-group">
                    <label class="profile-field-label"><i data-lucide="phone"></i> Phone Number</label>
                    <p class="profile-field-value <?php echo !empty($userData['phone']) ? 'real' : 'missing'; ?>">
                        <?php echo !empty($userData['phone']) ? htmlspecialchars($userData['phone']) : 'Not provided'; ?>
                    </p>
                </div>
                <div class="profile-field-group">
                    <label class="profile-field-label"><i data-lucide="shield-check"></i> Account Role</label>
                    <p class="profile-field-value real">
                        <?php echo htmlspecialchars($role); ?>
                        <?php if ($isHost && $hostStatusLabel): ?>
                        <span class="role-sub-label">(<?php echo htmlspecialchars($hostStatusLabel); ?>)</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="profile-field-group">
                    <label class="profile-field-label"><i data-lucide="calendar"></i> Member Since</label>
                    <p class="profile-field-value real"><?php echo $memberSince; ?></p>
                </div>
                <div class="profile-field-group">
                    <label class="profile-field-label"><i data-lucide="badge-check"></i> Account Verified</label>
                    <p class="profile-field-value <?php echo $isVerified ? 'verified' : 'unverified'; ?>">
                        <?php echo $isVerified ? '✓ Verified' : '✗ Not Verified'; ?>
                    </p>
                </div>
            </div>
        </section>

        <?php if ($isHost && $hostShelterInfo): ?>
        <!-- Shelter Information (host only) -->
        <section class="sidebar-panel profile-info-section">
            <h3 class="profile-section-title">
                <i data-lucide="home"></i> My Shelter
            </h3>
            <div class="profile-info-grid">
                <div class="profile-field-group">
                    <label class="profile-field-label"><i data-lucide="tag"></i> Shelter Name</label>
                    <p class="profile-field-value real"><?php echo htmlspecialchars($hostShelterInfo['shelter_name']); ?></p>
                </div>
                <div class="profile-field-group">
                    <label class="profile-field-label"><i data-lucide="users"></i> Occupancy</label>
                    <p class="profile-field-value real"><?php echo $hostShelterInfo['current_capacity']; ?> / <?php echo $hostShelterInfo['max_capacity']; ?> people</p>
                </div>
                <div class="profile-field-group profile-field-full">
                    <label class="profile-field-label"><i data-lucide="map-pin"></i> Location</label>
                    <p class="profile-field-value real"><?php echo htmlspecialchars($hostShelterInfo['location'] ?? '—'); ?></p>
                </div>
                <div class="profile-field-group">
                    <label class="profile-field-label"><i data-lucide="activity"></i> Shelter Status</label>
                    <p class="profile-field-value <?php echo $hostShelterInfo['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo $hostShelterInfo['is_active'] ? '● Active' : '○ Inactive'; ?>
                    </p>
                </div>
                <div class="profile-field-group">
                    <label class="profile-field-label"><i data-lucide="link"></i> Quick Link</label>
                    <p class="profile-field-value">
                        <a href="index.php?route=host_portal" class="profile-link">
                            <i data-lucide="arrow-right"></i>Host Portal
                        </a>
                    </p>
                </div>
            </div>
        </section>
        <?php endif; ?>

    </div><!-- /main-content -->

    <!-- ═══════════════ SIDEBAR ═══════════════════════════════════════════ -->
    <aside class="sidebar">

        <!-- Safety / Host Status Chip -->
        <?php if ($role !== 'Admin'): ?>
        <div class="sc-card" style="--sc-border:<?php echo $sm['border']; ?>;--sc-bg:<?php echo $sm['bg']; ?>;--sc-color:<?php echo $sm['color']; ?>;border-color:<?php echo $sm['border']; ?>;background:<?php echo $sm['bg']; ?>;">
            <div class="sc-head">
                <i data-lucide="<?php echo $si; ?>"></i>
                <span class="sc-title"><?php echo $isHost ? 'Host Status' : 'Safety Status'; ?></span>
            </div>
            <div class="sc-val">
                <?php if ($sm['pulse']): ?>
                    <div class="pulse-dot" style="--pd-color:<?php echo $sm['pulse']; ?>;"></div>
                <?php endif; ?>
                <?php echo htmlspecialchars($sv); ?>
            </div>
            <div class="sc-sub"><?php echo htmlspecialchars($ss); ?></div>
        </div>
        <?php endif; ?>

        <!-- Verification Status -->
        <div class="sidebar-panel">
            <h3 class="panel-title">Verification Status</h3>
            <div class="verification-status-item">
                <span><i data-lucide="<?php echo $isVerified ? 'badge-check' : 'badge-x'; ?>" class="<?php echo $isVerified ? 'icon-verified' : 'icon-unverified'; ?>"></i> Government ID</span>
                <strong class="<?php echo $isVerified ? 'text-verified' : 'text-unverified'; ?>"><?php echo $isVerified ? 'Verified' : 'Pending'; ?></strong>
            </div>
            <div class="verification-status-item">
                <span><i data-lucide="mail-check" class="icon-verified"></i> Email</span>
                <strong class="text-verified">Registered</strong>
            </div>
            <div class="verification-status-item">
                <span><i data-lucide="phone" class="<?php echo !empty($userData['phone']) ? 'icon-verified' : 'icon-warning'; ?>"></i> Phone</span>
                <strong class="<?php echo !empty($userData['phone']) ? 'text-verified' : 'text-warning'; ?>"><?php echo !empty($userData['phone']) ? 'On File' : 'Not Set'; ?></strong>
            </div>
            <p class="metric-detail verification-note">Verification ensures priority access during emergencies.</p>
        </div>

        <!-- Account Activity -->
        <div class="sidebar-panel">
            <h3 class="panel-title">Account Activity</h3>
            <div class="activity-item">
                <span><i data-lucide="clock"></i> Account Created</span>
                <strong><?php echo $memberSince; ?></strong>
            </div>
            <div class="activity-item">
                <span><i data-lucide="user-check"></i> Account Role</span>
                <strong><?php echo htmlspecialchars($role); ?></strong>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="sidebar-panel">
            <h3 class="panel-title">Quick Actions</h3>
            <?php if ($isHost): ?>
            <a href="index.php?route=host_portal" class="quick-action-link">
                <button class="quick-action-btn primary"><i data-lucide="home"></i> Host Portal</button>
            </a>
            <?php elseif ($isEvacuee): ?>
            <a href="index.php?route=evacuee_portal" class="quick-action-link">
                <button class="quick-action-btn primary"><i data-lucide="map-pin"></i> Find Shelter</button>
            </a>
            <?php endif; ?>
            <a href="index.php?route=settings" class="quick-action-link">
                <button class="quick-action-btn secondary"><i data-lucide="settings"></i> Account Settings</button>
            </a>
            <a href="index.php?route=alerts" class="quick-action-link">
                <button class="quick-action-btn secondary"><i data-lucide="bell"></i> View Alerts</button>
            </a>
        </div>

    </aside>
    </div><!-- /portal-layout -->
</main>


</body>
</html>
