<?php
/**
 * views/auth/settings.php
 * VIEW ONLY — no logic, no inline CSS, no inline JS.
 * All data and POST handling is done by UserController::settingsDashboard()
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANGPANAN | Settings</title>
    <link rel="stylesheet" href="assets/css/nav.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/portal.js" defer></script>
    <script src="assets/js/nav.js" defer></script>
    <link rel="stylesheet" href="assets/css/settings.css">
</head>
<body>
    <?php require 'views/partials/nav_portal.php'; ?>

    <div class="page-wrap">

        <!-- Hero -->
        <div class="settings-hero">
            <div class="hero-icon-box"><i data-lucide="settings" stroke="white"></i></div>
            <div class="hero-text">
                <h1>Account Settings</h1>
                <p>Manage your profile, password, and account security</p>
            </div>
            <span class="hero-badge">⚙️ SETTINGS</span>
        </div>

        <!-- Tabs -->
        <nav class="tabs-bar">
            <button class="tab-btn <?= $activeTab === 'profile'  ? 'active' : '' ?>" data-tab="profile">
                <i data-lucide="user"></i> Profile
            </button>
            <button class="tab-btn <?= $activeTab === 'security' ? 'active' : '' ?>" data-tab="security">
                <i data-lucide="lock"></i> Security
            </button>
            <button class="tab-btn" data-tab="account">
                <i data-lucide="info"></i> Account Info
            </button>
        </nav>

        <!-- ═══════════════════════════════════════
             TAB 1 — PROFILE
        ════════════════════════════════════════ -->
        <div class="tab-pane <?= $activeTab === 'profile' ? 'active' : '' ?>" id="tab-profile">

            <?php if ($profileSuccess): ?>
                <div class="alert-msg success">
                    <i data-lucide="check-circle"></i>
                    <?= htmlspecialchars($profileSuccess) ?>
                </div>
            <?php endif; ?>
            <?php if ($profileError): ?>
                <div class="alert-msg error">
                    <i data-lucide="alert-circle"></i>
                    <?= htmlspecialchars($profileError) ?>
                </div>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-hd">
                    <i data-lucide="user-circle"></i> Personal Information
                </div>
                <p class="panel-desc">
                    Update your name, email address, and phone number.
                    Your email is also used to log in.
                </p>

                <form method="POST" action="index.php?route=settings" id="profileForm" novalidate>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="first_name">
                                First Name <span class="required">*</span>
                            </label>
                            <input
                                type="text" id="first_name" name="first_name"
                                class="form-input <?= (!empty($profileError) && empty($_POST['first_name'] ?? '')) ? 'invalid' : '' ?>"
                                value="<?= htmlspecialchars($_POST['first_name'] ?? $userObj->getFirstName()) ?>"
                                maxlength="50" required placeholder="Juan">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="last_name">Last Name</label>
                            <input
                                type="text" id="last_name" name="last_name"
                                class="form-input"
                                value="<?= htmlspecialchars($_POST['last_name'] ?? $userObj->getLastName()) ?>"
                                maxlength="50" placeholder="dela Cruz">
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top:.85rem;">
                        <div class="form-group">
                            <label class="form-label" for="email">
                                Email Address <span class="required">*</span>
                            </label>
                            <input
                                type="email" id="email" name="email"
                                class="form-input"
                                value="<?= htmlspecialchars($_POST['email'] ?? $userObj->getEmail()) ?>"
                                required placeholder="you@email.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input
                                type="tel" id="phone" name="phone"
                                class="form-input"
                                value="<?= htmlspecialchars($_POST['phone'] ?? $userObj->getPhoneNumber()) ?>"
                                placeholder="+63 9XX XXX XXXX" maxlength="15">
                        </div>
                    </div>
                    
                    <div class="form-grid" style="margin-top:.85rem;">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label class="form-label" for="emergency_contact">Emergency Contact <span class="required">*</span></label>
                            <input
                                type="tel" id="emergency_contact" name="emergency_contact"
                                class="form-input"
                                value="<?= htmlspecialchars($_POST['emergency_contact'] ?? $userObj->getEmergencyContact()) ?>"
                                placeholder="+63 9XX XXX XXXX" maxlength="15" required>
                            <span class="form-hint">This number will receive a "Safe Check-in" SMS automatically when you arrive at a shelter.</span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn-cancel">
                            <i data-lucide="x"></i> Reset
                        </button>
                        <button type="submit" class="btn-save" id="saveProfileBtn">
                            <i data-lucide="save"></i> Save Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ═══════════════════════════════════════
             TAB 2 — SECURITY (Password Change)
        ════════════════════════════════════════ -->
        <div class="tab-pane <?= $activeTab === 'security' ? 'active' : '' ?>" id="tab-security">

            <?php if ($passwordSuccess): ?>
                <div class="alert-msg success">
                    <i data-lucide="check-circle"></i>
                    <?= htmlspecialchars($passwordSuccess) ?>
                </div>
            <?php endif; ?>
            <?php if ($passwordError): ?>
                <div class="alert-msg error">
                    <i data-lucide="alert-circle"></i>
                    <?= htmlspecialchars($passwordError) ?>
                </div>
            <?php endif; ?>

            <div class="panel">
                <div class="panel-hd">
                    <i data-lucide="key-round"></i> Change Password
                </div>
                <p class="panel-desc">
                    To change your password, enter your current password first.
                    Your new password must be at least 8 characters long.
                </p>

                <form method="POST" action="index.php?route=settings" id="passwordForm" novalidate>
                    <input type="hidden" name="action" value="change_password">

                    <div class="form-group" style="max-width:420px;">
                        <label class="form-label" for="current_password">
                            Current Password <span class="required">*</span>
                        </label>
                        <div class="pw-wrap">
                            <input type="password" id="current_password" name="current_password"
                                class="form-input <?= (!empty($passwordError) && str_contains($passwordError, 'Current')) ? 'invalid' : '' ?>"
                                placeholder="Enter current password" required autocomplete="current-password">
                            <button type="button" class="pw-toggle" onclick="togglePw('current_password', this)" aria-label="Toggle visibility">
                                <i data-lucide="eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-grid" style="margin-top:.85rem;">
                        <div class="form-group">
                            <label class="form-label" for="new_password">
                                New Password <span class="required">*</span>
                            </label>
                            <div class="pw-wrap">
                                <input type="password" id="new_password" name="new_password"
                                    class="form-input"
                                    placeholder="Minimum 8 characters"
                                    required minlength="8" autocomplete="new-password"
                                    oninput="checkStrength(this.value)">
                                <button type="button" class="pw-toggle" onclick="togglePw('new_password', this)" aria-label="Toggle visibility">
                                    <i data-lucide="eye"></i>
                                </button>
                            </div>
                            <div class="strength-bar-track">
                                <div class="strength-bar-fill" id="strengthFill"></div>
                            </div>
                            <p class="strength-label" id="strengthLabel">Enter a new password</p>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">
                                Confirm New Password <span class="required">*</span>
                            </label>
                            <div class="pw-wrap">
                                <input type="password" id="confirm_password" name="confirm_password"
                                    class="form-input"
                                    placeholder="Re-enter new password"
                                    required autocomplete="new-password"
                                    oninput="checkMatch()">
                                <button type="button" class="pw-toggle" onclick="togglePw('confirm_password', this)" aria-label="Toggle visibility">
                                    <i data-lucide="eye"></i>
                                </button>
                            </div>
                            <p class="form-hint" id="matchLabel">&nbsp;</p>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="reset" class="btn-cancel" onclick="resetPasswordForm()">
                            <i data-lucide="x"></i> Clear
                        </button>
                        <button type="submit" class="btn-save" id="savePwBtn">
                            <i data-lucide="shield-check"></i> Update Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Danger Zone -->
            <div class="panel panel-danger">
                <div class="panel-hd">
                    <i data-lucide="triangle-alert"></i> Danger Zone
                </div>
                <div class="danger-row">
                    <div class="danger-row-text">
                        <p>Deactivate Account</p>
                        <p>Temporarily disable your account. Reactivate by logging back in.</p>
                    </div>
                    <button type="button" class="btn-danger-outline">Deactivate</button>
                </div>
                <div class="danger-row">
                    <div class="danger-row-text">
                        <p>Delete Account Permanently</p>
                        <p>Remove all your data from DANGPANAN. This action cannot be undone.</p>
                    </div>
                    <button type="button" class="btn-danger-solid">Delete Account</button>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════
             TAB 3 — ACCOUNT INFO (read-only)
        ════════════════════════════════════════ -->
        <div class="tab-pane" id="tab-account">
            <div class="panel">
                <div class="panel-hd">
                    <i data-lucide="id-card"></i> Account Details
                </div>
                <div class="info-row">
                    <span class="info-label">User ID</span>
                    <span class="info-value">#<?= htmlspecialchars($userObj->getId()) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?= htmlspecialchars($userObj->getFullName() ?: '—') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email Address</span>
                    <span class="info-value"><?= htmlspecialchars($userObj->getEmail()) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone Number</span>
                    <span class="info-value"><?= htmlspecialchars($userObj->getPhoneNumber() ?: '—') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Role</span>
                    <span class="info-value">
                        <span class="info-badge badge-<?= strtolower($userObj->getRole()) ?>">
                            <?= htmlspecialchars($userObj->getRole()) ?>
                        </span>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Verification Status</span>
                    <span class="info-value">
                        <span class="info-badge <?= $userObj->getIsVerified() ? 'badge-verified' : 'badge-unverified' ?>">
                            <?= $userObj->getIsVerified() ? '✓ Verified' : 'Unverified' ?>
                        </span>
                    </span>
                </div>
                <?php if ($userObj->getRole() !== 'Admin'): ?>
                <div class="info-row">
                    <span class="info-label">Host Status</span>
                    <span class="info-value">
                        <?php
                        $hs = $userObj->getHostStatus();
                        $hsLabel = match($hs) {
                            'active_host'  => '🟢 Active Host',
                            'relinquished' => '⚪ Relinquished',
                            default        => '— None',
                        };
                        echo htmlspecialchars($hsLabel);
                        ?>
                    </span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Account Created</span>
                    <span class="info-value">
                        <?php
                        $ca = $userObj->getCreatedAt();
                        echo $ca ? date('F j, Y', strtotime($ca)) : '—';
                        ?>
                    </span>
                </div>
            </div>
        </div>

    </div><!-- /page-wrap -->

    <script src="assets/js/settings.js" defer></script>
</body>
</html>
