<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANGPANAN | Reset Password</title>
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .step-container { display: none; }
        .step-container.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .help-text { color: #94a3b8; font-size: 0.85rem; margin-bottom: 15px; }
        .error-msg { color: #ef4444; font-size: 0.85rem; margin-bottom: 10px; display: none; background: #fee2e2; padding: 8px; border-radius: 6px; }
        .success-msg { color: #10b981; font-size: 0.85rem; margin-bottom: 10px; display: none; background: #d1fae5; padding: 8px; border-radius: 6px; }
    </style>
</head>
<body class="auth-bg">
    <div class="login-card">
        <div class="auth-icon">
            <img src="assets/img/LOGO.png" alt="Dangpanan Shield" class="auth-logo">
        </div>
        
        <h2 class="auth-title">PASSWORD RESET</h2>
        <p class="subtitle">SECURE RECOVERY PORTAL</p>
        
        <div id="alertError" class="error-msg"></div>
        <div id="alertSuccess" class="success-msg"></div>

        <?php $prefilledEmail = $_GET['e'] ?? ''; ?>

        <!-- Step 1: Phone Input -->
        <div id="step-1" class="step-container active">
            <p class="help-text">Enter your registered email address to receive a secure SMS verification code to your phone.</p>
            <input type="email" id="resetEmail" class="input-field" placeholder="Email Address" value="<?php echo htmlspecialchars($prefilledEmail); ?>" required>
            <button type="button" class="btn-auth" id="btnSendOtp" onclick="sendResetOtp()">
                <i data-lucide="send" style="width:16px;height:16px;margin-right:6px;vertical-align:-3px;"></i> SEND VERIFICATION CODE
            </button>
        </div>

        <!-- Step 2: OTP Input -->
        <div id="step-2" class="step-container">
            <p class="help-text">We sent a 6-digit code to <strong id="lblPhoneMask">your associated phone number</strong>. It expires in 5 minutes.</p>
            <input type="text" id="resetOtp" class="input-field" placeholder="6-digit OTP Code" maxlength="6" style="letter-spacing: 4px; font-weight: bold; text-align: center;" required>
            <button type="button" class="btn-auth" id="btnVerifyOtp" onclick="verifyResetOtp()">
                <i data-lucide="shield-check" style="width:16px;height:16px;margin-right:6px;vertical-align:-3px;"></i> VERIFY CODE
            </button>
        </div>

        <!-- Step 3: New Password -->
        <div id="step-3" class="step-container">
            <p class="help-text">Identity verified. Please set your new secure password.</p>
            <input type="password" id="newPassword" class="input-field" placeholder="New Password (min 8 chars)" required>
            <input type="password" id="confirmPassword" class="input-field" placeholder="Confirm New Password" required>
            <button type="button" class="btn-auth" id="btnSavePassword" onclick="saveNewPassword()">
                <i data-lucide="key" style="width:16px;height:16px;margin-right:6px;vertical-align:-3px;"></i> RESET PASSWORD
            </button>
        </div>

        <div class="auth-footer" style="margin-top: 25px;">
            <div class="back-link-container">
                <a href="index.php?route=login" class="signup-link">← Return to Login</a>
            </div>
        </div>
    </div>
    
    <script>lucide.createIcons();</script>
    <script src="assets/js/forgot_password.js"></script>
</body>
</html>
