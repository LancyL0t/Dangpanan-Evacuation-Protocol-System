// assets/js/forgot_password.js

function showAlert(msg, isError = true) {
    const errBox = document.getElementById('alertError');
    const sucBox = document.getElementById('alertSuccess');
    
    if (isError) {
        errBox.textContent = msg;
        errBox.style.display = 'block';
        sucBox.style.display = 'none';
    } else {
        sucBox.textContent = msg;
        sucBox.style.display = 'block';
        errBox.style.display = 'none';
        
        setTimeout(() => { sucBox.style.display = 'none'; }, 5000);
    }
}

function clearAlerts() {
    document.getElementById('alertError').style.display = 'none';
    document.getElementById('alertSuccess').style.display = 'none';
}

function showStep(stepNum) {
    document.querySelectorAll('.step-container').forEach(el => el.classList.remove('active'));
    document.getElementById('step-' + stepNum).classList.add('active');
}

// ── STEP 1: Send OTP ──────────────────────────────────────────
async function sendResetOtp() {
    const email = document.getElementById('resetEmail').value.trim();
    if (!email) {
        showAlert('Please enter your email address.');
        return;
    }

    const btn = document.getElementById('btnSendOtp');
    btn.innerHTML = 'SENDING...';
    btn.disabled = true;
    clearAlerts();

    try {
        const response = await fetch('index.php?route=forgot_send_otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email })
        });
        const result = await response.json();

        if (result.success) {
            // Read masked phone from server instead of computing it
            const masked = result.maskedPhone || 'your registered number';
            document.getElementById('lblPhoneMask').textContent = masked;
            showAlert('OTP sent successfully!', false);
            showStep(2);
        } else {
            showAlert(result.message || 'Failed to send OTP.');
        }
    } catch (e) {
        showAlert('Network error occurred.');
    } finally {
        btn.innerHTML = '<i data-lucide="send" style="width:16px;height:16px;margin-right:6px;vertical-align:-3px;"></i> SEND VERIFICATION CODE';
        btn.disabled = false;
        if(window.lucide) lucide.createIcons();
    }
}

// ── STEP 2: Verify OTP ──────────────────────────────────────────
async function verifyResetOtp() {
    const code = document.getElementById('resetOtp').value.trim();
    if (code.length !== 6) {
        showAlert('Please enter the 6-digit code.');
        return;
    }

    const btn = document.getElementById('btnVerifyOtp');
    btn.innerHTML = 'VERIFYING...';
    btn.disabled = true;
    clearAlerts();

    try {
        const response = await fetch('index.php?route=forgot_verify_otp', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: code })
        });
        const result = await response.json();

        if (result.success) {
            showAlert(result.message, false);
            showStep(3);
        } else {
            showAlert(result.message || 'Invalid code.');
            // Go back to step 1 if locked or expired
            if (result.locked || result.expired) {
                setTimeout(() => { showStep(1); }, 2000);
            }
        }
    } catch (e) {
        showAlert('Network error occurred.');
    } finally {
        btn.innerHTML = '<i data-lucide="shield-check" style="width:16px;height:16px;margin-right:6px;vertical-align:-3px;"></i> VERIFY CODE';
        btn.disabled = false;
        if(window.lucide) lucide.createIcons();
    }
}

// ── STEP 3: Save New Password ────────────────────────────────────
async function saveNewPassword() {
    const pass1 = document.getElementById('newPassword').value;
    const pass2 = document.getElementById('confirmPassword').value;

    if (pass1.length < 8) {
        showAlert('Password must be at least 8 characters long.');
        return;
    }
    if (pass1 !== pass2) {
        showAlert('Passwords do not match.');
        return;
    }

    const btn = document.getElementById('btnSavePassword');
    btn.innerHTML = 'SAVING...';
    btn.disabled = true;
    clearAlerts();

    try {
        const response = await fetch('index.php?route=reset_password_submit', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password: pass1 })
        });
        const result = await response.json();

        if (result.success) {
            showAlert(result.message, false);
            document.getElementById('step-3').innerHTML = `
                <div style="text-align:center; padding: 20px 0;">
                    <i data-lucide="check-circle" style="color:#10b981; width:48px; height:48px; margin-bottom:10px;"></i>
                    <h3 style="color:#1e293b; margin:0 0 10px 0;">Password Reset Complete</h3>
                    <p style="color:#64748b; font-size: 0.9rem;">You can now log in with your new password.</p>
                </div>
            `;
            if(window.lucide) lucide.createIcons();
            setTimeout(() => {
                window.location.href = 'index.php?route=login';
            }, 3000);
        } else {
            showAlert(result.message || 'Failed to reset password.');
        }
    } catch (e) {
        showAlert('Network error occurred.');
    } finally {
        if(btn) {
            btn.innerHTML = '<i data-lucide="key" style="width:16px;height:16px;margin-right:6px;vertical-align:-3px;"></i> RESET PASSWORD';
            btn.disabled = false;
            if(window.lucide) lucide.createIcons();
        }
    }
}
