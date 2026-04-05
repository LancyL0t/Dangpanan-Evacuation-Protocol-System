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
                    <label class="profile-field-label"><i data-lucide="bell-ring"></i> Emergency Contact</label>
                    <p class="profile-field-value <?php echo !empty($userData['emergency_contact']) ? 'real' : 'missing'; ?>">
                        <?php echo !empty($userData['emergency_contact']) ? htmlspecialchars($userData['emergency_contact']) : 'Not provided'; ?>
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

        <?php if ($role !== 'Admin'): ?>
        <!-- Shelter History -->
        <section class="sidebar-panel profile-info-section">
            <h3 class="history-section-title">
                <i data-lucide="history"></i> Shelter History
            </h3>
            
            <?php if (!empty($shelterHistory)): ?>
                <div class="shelter-feed" style="margin-top: 1rem;">
                    <?php foreach ($shelterHistory as $history): 
                        $ci = new DateTime($history['checked_in_at']);
                        $coStr = $history['checked_out_at'] ? (new DateTime($history['checked_out_at']))->format('M j, Y, h:i A') : '—';
                        
                        // Calculate duration if checked out
                        $durationStr = '';
                        if ($history['checked_out_at']) {
                            $co = new DateTime($history['checked_out_at']);
                            $diff = $ci->diff($co);
                            if ($diff->d > 0) $durationStr = $diff->d . 'd ' . $diff->h . 'h';
                            elseif ($diff->h > 0) $durationStr = $diff->h . 'h ' . $diff->i . 'm';
                            else $durationStr = $diff->i . 'm';
                        }
                        
                        $hostName = $history['host_first'] . ' ' . $history['host_last'];
                        $initials = strtoupper(substr($history['shelter_name'], 0, 2));
                    ?>
                    <div class="list-item history-card" style="display: flex; flex-direction: column; gap: 12px; position: relative; background: var(--bg-secondary); border: 1px solid var(--border-light); border-radius: var(--radius-lg); padding: 1.25rem; box-shadow: var(--shadow-sm);">
                        
                        <!-- Top Row: Shelter Info & Status -->
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
                            <div class="user-row" style="margin-bottom: 0; display: flex; gap: 1rem; align-items: center;">
                                <div class="avatar" style="background: var(--primary-red-light); color: var(--primary-red); width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 1rem; flex-shrink: 0;">
                                    <?php echo $initials; ?>
                                </div>
                                <div class="u-info">
                                    <h4 style="margin: 0; font-size: 1rem; color: var(--text-primary); font-weight: 700;"><?php echo htmlspecialchars($history['shelter_name']); ?></h4>
                                    <p class="evacuee-meta" style="margin: 4px 0 0; font-size: 0.75rem; color: var(--text-muted); display: flex; gap: 10px; align-items: center;">
                                        <span><i data-lucide="user" style="width:12px; height:12px; vertical-align: -2px;"></i> Host: <?php echo htmlspecialchars($hostName); ?></span>
                                        <span><i data-lucide="map-pin" style="width:12px; height:12px; vertical-align: -2px;"></i> <?php echo htmlspecialchars($history['location']); ?></span>
                                    </p>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <span class="status-pill <?php echo $history['status']; ?>" style="font-size: 0.65rem; padding: 4px 10px; border-radius: 99px;">
                                    <?php echo strtoupper(str_replace('_', ' ', $history['status'])); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Middle Row: Timeline -->
                        <div style="background: var(--bg-tertiary); border-radius: 8px; padding: 10px 14px; display: flex; justify-content: space-between; align-items: center; border: 1px solid var(--border-light);">
                            <div style="flex: 1;">
                                <div style="font-size: 0.65rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 2px;">Check-In</div>
                                <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-primary);"><?php echo $ci->format('M j, Y'); ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);"><?php echo $ci->format('h:i A'); ?></div>
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 0 15px;">
                                <div style="height: 1px; background: #cbd5e1; width: 40px; position: relative; display: flex; align-items: center; justify-content: center;">
                                    <i data-lucide="arrow-right" style="width:14px; height:14px; color: #94a3b8; background: var(--bg-tertiary); padding: 0 4px;"></i>
                                </div>
                                <?php if ($durationStr): ?>
                                    <span style="font-size: 0.65rem; font-weight: 700; color: #2563eb; background: #eff6ff; padding: 2px 8px; border-radius: 6px; margin-top: 6px;">
                                        <?php echo $durationStr; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="flex: 1; text-align: right;">
                                <div style="font-size: 0.65rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 2px;">Check-Out</div>
                                <div style="font-size: 0.8rem; font-weight: 600; color: var(--text-primary);">
                                    <?php echo $history['checked_out_at'] ? (new DateTime($history['checked_out_at']))->format('M j, Y') : '—'; ?>
                                </div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">
                                    <?php echo $history['checked_out_at'] ? (new DateTime($history['checked_out_at']))->format('h:i A') : ''; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Bottom Row: My Rating -->
                        <div style="display: flex; align-items: center; gap: 12px; padding-top: 4px; border-top: 1px dashed var(--border-light); margin-top: 2px;">
                            <div style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted);">My Rating:</div>
                            <?php if ($history['rating']): ?>
                                <div class="stars-display" style="gap: 4px;">
                                    <?php for ($i=1; $i<=5; $i++): 
                                        if ($i <= $history['rating']): ?>
                                            <i data-lucide="star" style="width: 16px; height: 16px; color: #f59e0b; fill: #f59e0b;"></i>
                                        <?php else: ?>
                                            <i data-lucide="star" style="width: 16px; height: 16px; color: #e2e8f0;"></i>
                                        <?php endif; 
                                    endfor; ?>
                                </div>
                                <?php if (!empty($history['review_text'])): ?>
                                    <div style="flex: 1; font-size: 0.8rem; color: #475569; font-style: italic; background: #fffbeb; padding: 6px 12px; border-radius: 6px; border: 1px solid #fef3c7; border-left: 3px solid #f59e0b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($history['review_text']); ?>">
                                        "<?php echo htmlspecialchars($history['review_text']); ?>"
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size: 0.75rem; color: #94a3b8; font-style: italic;">No rating provided</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="history-empty">
                    <i data-lucide="clock"></i>
                    <h4>No Shelter History</h4>
                    <p>You haven't checked into any shelters yet.</p>
                </div>
            <?php endif; ?>
        </section>
        <?php endif; ?>

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
