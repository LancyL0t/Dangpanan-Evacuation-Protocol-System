<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANGPANAN | Secure Login</title>
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="assets/css/login.css">
        <script src="assets/js/portal.js" defer></script>
</head>
<body class="auth-bg">
    <div class="login-card">
        <div class="auth-icon">
            <img src="assets/img/LOGO.png" alt="Dangpanan Shield" class="auth-logo">
        </div>
        
        <h2 class="auth-title">DANGPANAN</h2>
        <p class="subtitle">SECURE ACCESS PORTAL</p>
        
        <?php if (isset($_GET['error']) && $_GET['error'] == '1'): ?>
            <div style="background-color: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; border-radius: 6px; padding: 10px; margin-bottom: 20px; font-size: 0.9rem; text-align: center;">
                Invalid email or password. Please try again.
            </div>
        <?php endif; ?>

        <?php 
            $attemptedEmail = $_SESSION['login_attempt_email'] ?? ''; 
            $urlEncodedEmail = urlencode($attemptedEmail);
        ?>
        <form action="index.php?route=authenticate" method="POST">
            <input type="email" name="email" class="input-field" placeholder="Email Address" value="<?php echo htmlspecialchars($attemptedEmail); ?>" required>
            <input type="password" name="password" class="input-field" placeholder="Password" required>
            <div style="text-align: right; margin-bottom: 20px;">
                <a href="index.php?route=forgot_password<?php echo $attemptedEmail ? '&e=' . $urlEncodedEmail : ''; ?>" style="font-size: 0.8rem; color: var(--primary-red); text-decoration: none; font-weight: 500;">Forgot Password?</a>
            </div>
            <button type="submit" class="btn-auth">AUTHENTICATE SYSTEM</button>
        </form>

        <div class="auth-footer">
            <a href="index.php?route=register" class="signup-link">
                Don't have an account? <span class="highlight">Sign Up Protocol</span>
            </a>
            <div class="back-link-container">
                <a href="index.php?route=home" class="signup-link">← Back to System Home</a>
            </div>
        </div>
    </div>
</body>
</html>