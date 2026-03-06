<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DANGPANAN | Complete Your Profile</title>
    <link rel="stylesheet" href="assets/css/landing.css">
    <link rel="stylesheet" href="assets/css/registration.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="assets/js/registration.js" defer></script>
</head>
<body class="reg-bg-gradient">
    <div class="registration-container">
        <div class="auth-header">
            <div class="icon-box">
                <img src="assets/img/LOGO.png" alt="Dangpanan Shield"
                     style="width: 40px; height: 40px; object-fit: contain;">
            </div>
            <h1>Complete Your Profile</h1>
            <p>Secure verification for emergency services</p>
        </div>

        <!-- Server-side messages -->
        <?php if (isset($_GET['error'])): ?>
            <div class="reg-alert reg-alert-error">
                <?php
                    $errors = [
                        'email_taken'    => 'That email address is already registered.',
                        'db_error'       => 'A database error occurred. Please try again.',
                        'upload_failed'  => 'ID photo upload failed. Please try again.',
                        'invalid_file'   => 'Invalid file type. Please upload a JPG, PNG, or PDF.',
                        'otp_expired'    => 'Your verification code has expired. Please request a new one.',
                        'otp_invalid'    => 'Incorrect verification code. Please try again.',
                        'otp_required'   => 'Please verify your phone number before completing registration.',
                    ];
                    echo htmlspecialchars($errors[$_GET['error']] ?? 'An error occurred. Please try again.');
                ?>
            </div>
        <?php endif; ?>

        <!-- Stepper: 4 steps -->
        <div class="stepper">
            <div class="step active" id="s1"><i data-lucide="user"></i></div>
            <div class="step-line"></div>
            <div class="step" id="s2"><i data-lucide="map-pin"></i></div>
            <div class="step-line"></div>
            <div class="step" id="s3"><i data-lucide="credit-card"></i></div>
            <div class="step-line"></div>
            <div class="step" id="s4"><i data-lucide="phone"></i></div>
        </div>

        <form id="regForm" action="index.php?route=do_register" method="POST" enctype="multipart/form-data" class="glass-card">

            <!-- ── STEP 1: Personal Info ── -->
            <div class="form-step active" id="step1">
                <h2>Personal Information</h2>
                <p>Tell us about yourself and set up your login credentials.</p>

                <div class="name-grid">
                    <div class="name-field">
                        <label>First Name <span class="req">*</span></label>
                        <input type="text" name="first_name" placeholder="Juan" required autocomplete="given-name">
                    </div>
                    <div class="name-field name-field--mi">
                        <label>M.I.</label>
                        <input type="text" name="middle_initial" placeholder="D." maxlength="3" autocomplete="additional-name">
                    </div>
                    <div class="name-field">
                        <label>Last Name <span class="req">*</span></label>
                        <input type="text" name="last_name" placeholder="dela Cruz" required autocomplete="family-name">
                    </div>
                </div>

                <label>Email Address <span class="req">*</span></label>
                <input type="email" name="email" placeholder="juan@example.com" required autocomplete="email">

                <div class="password-grid">
                    <div class="password-field-wrapper">
                        <label>Password <span class="req">*</span></label>
                        <input type="password" name="password" id="password" required minlength="8" autocomplete="new-password">
                        <button type="button" class="eye-toggle" onclick="togglePassword('password', 'eye-icon-1')">
                            <i data-lucide="eye" id="eye-icon-1"></i>
                        </button>
                    </div>
                    <div class="password-field-wrapper">
                        <label>Confirm Password <span class="req">*</span></label>
                        <input type="password" name="confirm_password" id="confirm_password" required autocomplete="new-password">
                        <button type="button" class="eye-toggle" onclick="togglePassword('confirm_password', 'eye-icon-2')">
                            <i data-lucide="eye" id="eye-icon-2"></i>
                        </button>
                    </div>
                </div>

                <label>Phone Number <span class="req">*</span></label>
                <input type="tel" name="phone_number" id="phone_number" placeholder="09XX XXX XXXX" required>

                <label>Date of Birth <span class="req">*</span></label>
                <input type="date" name="date_of_birth" required>

                <div class="button-group">
                    <button type="button" class="btn-back" onclick="location.href='index.php?route=login'">
                        <i data-lucide="arrow-left"></i> Login
                    </button>
                    <button type="button" class="btn-continue" onclick="validateStep1()">
                        Continue <i data-lucide="chevron-right"></i>
                    </button>
                </div>
            </div>

            <!-- ── STEP 2: Address ── -->
            <div class="form-step" id="step2">
                <h2>Address Information</h2>
                <p>We need your address for shelter assignment during emergencies.</p>

                <label>Complete Address <span class="req">*</span></label>
                <textarea name="address" placeholder="House/Unit No., Street, Subdivision..." required rows="3" style="height:auto;"></textarea>

                <label>Barangay <span class="req">*</span></label>
                <select name="barangay" required>
                    <option value="">— Select Barangay —</option>
                    <?php
                    foreach (range(1, 41) as $n) echo "<option value=\"$n\">Barangay $n</option>";
                    $named = ['Alijis','Alangilan','Banago','Bata','Cabug','Estefania','Felisa','Granada',
                              'Handumanan','Mandalagan','Mansilingan','Montevista','Pahanocoy','Punta Taytay',
                              'Singcang-Airport','Sum-ag','Taculing','Tangub','Villamonte','Vista Alegre'];
                    foreach ($named as $name) echo "<option value=\"$name\">$name</option>";
                    ?>
                </select>

                <div class="emergency-section">
                    <h3>Emergency Contact</h3>
                    <label>Contact Person Name <span class="req">*</span></label>
                    <input type="text" name="emergency_contact_name" placeholder="Full name of contact person" required>
                    <label>Contact Phone Number <span class="req">*</span></label>
                    <input type="tel" name="emergency_contact_phone" placeholder="09XX XXX XXXX" required>
                </div>

                <div class="button-group">
                    <button type="button" class="btn-back" onclick="changeStep(1)"><i data-lucide="arrow-left"></i> Back</button>
                    <button type="button" class="btn-continue" onclick="validateStep2()">Continue <i data-lucide="chevron-right"></i></button>
                </div>
            </div>

            <!-- ── STEP 3: ID Verification ── -->
            <div class="form-step" id="step3">
                <h2>ID Verification</h2>
                <p>Upload a valid government-issued ID to complete your registration.</p>

                <label>ID Type <span class="req">*</span></label>
                <select name="government_id_type" required>
                    <option value="">— Select ID Type —</option>
                    <option value="philsys_id">PhilSys National ID</option>
                    <option value="drivers_license">Driver's License</option>
                    <option value="passport">Passport</option>
                    <option value="voters_id">Voter's ID</option>
                    <option value="postal_id">Postal ID</option>
                    <option value="sss_id">SSS ID</option>
                    <option value="philhealth_id">PhilHealth ID</option>
                    <option value="tin_id">TIN ID</option>
                </select>

                <label>ID Number <span class="req">*</span></label>
                <input type="text" name="government_id_number" placeholder="Enter your ID number" required>

                <label>Upload ID Photo <span class="req">*</span></label>
                <div class="upload-area" id="uploadArea" onclick="document.getElementById('file_id').click()">
                    <div id="uploadPrompt">
                        <i data-lucide="camera" style="width:36px;height:36px;color:#a1a1aa;display:block;margin:0 auto 0.5rem;"></i>
                        <p style="margin:0;color:#a1a1aa;font-size:0.9rem;">Tap to upload ID photo</p>
                        <p style="margin:.25rem 0 0;color:#71717a;font-size:0.75rem;">JPG, PNG — max 5MB</p>
                    </div>
                    <div id="uploadPreview" style="display:none;">
                        <img id="previewImg" src="" alt="ID Preview" style="max-width:100%;max-height:180px;border-radius:8px;object-fit:contain;">
                        <p id="previewName" style="margin:.5rem 0 0;font-size:.8rem;color:#a1a1aa;"></p>
                    </div>
                    <input type="file" id="file_id" name="gov_id_file" hidden accept=".jpg,.jpeg,.png,.pdf"
                           onchange="previewID(this)" required>
                </div>

                <div class="button-group">
                    <button type="button" class="btn-back" onclick="changeStep(2)"><i data-lucide="arrow-left"></i> Back</button>
                    <button type="button" class="btn-continue" onclick="validateStep3()">Continue <i data-lucide="chevron-right"></i></button>
                </div>
            </div>

            <!-- ── STEP 4: Phone OTP Verification ── -->
            <div class="form-step" id="step4">
                <h2>Phone Verification</h2>
                <p>We'll send a 6-digit code to your phone number to verify your identity.</p>

                <!-- Phone display -->
                <div class="otp-phone-display">
                    <i data-lucide="smartphone"></i>
                    <span id="otpPhoneDisplay">—</span>
                </div>

                <!-- Dev/demo notice — remove in production with real SMS -->
                <div class="otp-demo-notice" id="otpDemoNotice" style="display:none;">
                    <i data-lucide="info"></i>
                    <div>
                        <strong>Demo Mode</strong> — Your code is:
                        <span id="otpDemoCode" class="otp-demo-code">——</span>
                    </div>
                </div>

                <!-- Send OTP button (shown before code is sent) -->
                <div id="otpSendSection">
                    <button type="button" class="btn-send-otp" id="btnSendOtp" onclick="sendOtp()">
                        <i data-lucide="send"></i> Send Verification Code
                    </button>
                </div>

                <!-- OTP input (shown after code is sent) -->
                <div id="otpInputSection" style="display:none;">
                    <label>Enter 6-Digit Code <span class="req">*</span></label>
                    <div class="otp-boxes" id="otpBoxes">
                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                        <input type="text" class="otp-box" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    </div>
                    <!-- Hidden field that holds the assembled code for submission -->
                    <input type="hidden" name="otp_code" id="otpHiddenInput">

                    <!-- Countdown timer -->
                    <div class="otp-timer" id="otpTimerWrap">
                        Code expires in <span id="otpCountdown">05:00</span>
                    </div>

                    <div class="otp-resend" id="otpResendWrap" style="display:none;">
                        Didn't receive it?
                        <button type="button" class="btn-resend" onclick="sendOtp()">Resend code</button>
                    </div>

                    <button type="button" class="btn-continue" onclick="verifyOtp()" style="width:100%;margin-top:1rem;">
                        Verify Code <i data-lucide="shield-check"></i>
                    </button>
                </div>

                <!-- Verified state (shown after successful verify) -->
                <div id="otpVerifiedSection" style="display:none;">
                    <div class="otp-verified-badge">
                        <i data-lucide="check-circle-2"></i>
                        Phone number verified!
                    </div>
                </div>

                <!-- Hidden flag — set to "1" after successful OTP verify -->
                <input type="hidden" name="phone_verified" id="phoneVerifiedFlag" value="0">

                <div class="button-group" style="margin-top:1.5rem;">
                    <button type="button" class="btn-back" onclick="changeStep(3)"><i data-lucide="arrow-left"></i> Back</button>
                    <button type="submit" class="btn-complete" id="submitBtn" disabled>
                        Complete Registration <i data-lucide="check"></i>
                    </button>
                </div>
            </div>

        </form>
    </div>


</body>
</html>
