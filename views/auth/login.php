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
        
        <form action="index.php?route=authenticate" method="POST">
            <input type="email" name="email" class="input-field" placeholder="Email Address" required>
            <input type="password" name="password" class="input-field" placeholder="Password" required>
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