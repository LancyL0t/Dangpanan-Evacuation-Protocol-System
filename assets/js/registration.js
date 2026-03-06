/**
 * Dangpanan/assets/js/registration.js
 * Multi-step registration with session-based OTP phone verification.
 */

// ── Form submission ──────────────────────────────────────────────────────────
document.getElementById('regForm').onsubmit = function(e) {
    if (document.getElementById('phoneVerifiedFlag').value !== '1') {
        e.preventDefault();
        alert('Please verify your phone number before completing registration.');
        return false;
    }
    const btn = document.getElementById('submitBtn');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = 'Submitting… <i data-lucide="loader-2"></i>';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
    return true;
};

// ── Step navigation ──────────────────────────────────────────────────────────
function changeStep(step) {
    document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
    const target = document.getElementById('step' + step);
    if (target) target.classList.add('active');

    document.querySelectorAll('.step').forEach((el, idx) => {
        el.classList.remove('active', 'completed');
        if (idx + 1 < step)   el.classList.add('completed');
        if (idx + 1 === step) el.classList.add('active');
    });

    // Sync phone number display on step 4
    if (step === 4) {
        const phone = document.getElementById('phone_number').value.trim();
        document.getElementById('otpPhoneDisplay').textContent = phone || '—';
    }

    document.querySelector('.registration-container')
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ── Step 1 validation ────────────────────────────────────────────────────────
function validateStep1() {
    clearErrors();
    const firstName = document.querySelector('input[name="first_name"]');
    const lastName  = document.querySelector('input[name="last_name"]');
    const email     = document.querySelector('input[name="email"]');
    const phone     = document.getElementById('phone_number');
    const dob       = document.querySelector('input[name="date_of_birth"]');
    const pass      = document.getElementById('password');
    const confirm   = document.getElementById('confirm_password');
    let ok = true;

    if (!firstName.value.trim()) { showErr(firstName, 'First name is required.'); ok = false; }
    if (!lastName.value.trim())  { showErr(lastName,  'Last name is required.');  ok = false; }

    const emailVal = email.value.trim();
    if (!emailVal) { showErr(email, 'Email is required.'); ok = false; }
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) { showErr(email, 'Enter a valid email address.'); ok = false; }

    const phoneVal = phone.value.replace(/\s/g, '');
    if (!phoneVal) { showErr(phone, 'Phone number is required.'); ok = false; }
    else if (!/^(09|\+639)\d{9}$/.test(phoneVal)) { showErr(phone, 'Enter a valid PH mobile number (e.g. 09XX XXX XXXX).'); ok = false; }

    if (!dob.value) { showErr(dob, 'Date of birth is required.'); ok = false; }

    if (!pass.value) { showErr(pass, 'Password is required.'); ok = false; }
    else if (pass.value.length < 8) { showErr(pass, 'Password must be at least 8 characters.'); ok = false; }

    if (!confirm.value) { showErr(confirm, 'Please confirm your password.'); ok = false; }
    else if (pass.value !== confirm.value) { showErr(confirm, 'Passwords do not match.'); ok = false; }

    if (ok) changeStep(2);
}

// ── Step 2 validation ────────────────────────────────────────────────────────
function validateStep2() {
    clearErrors();
    const address  = document.querySelector('textarea[name="address"]');
    const barangay = document.querySelector('select[name="barangay"]');
    const ecName   = document.querySelector('input[name="emergency_contact_name"]');
    const ecPhone  = document.querySelector('input[name="emergency_contact_phone"]');
    let ok = true;

    if (!address.value.trim())  { showErr(address,  'Address is required.'); ok = false; }
    if (!barangay.value)        { showErr(barangay, 'Please select a barangay.'); ok = false; }
    if (!ecName.value.trim())   { showErr(ecName,   'Emergency contact name is required.'); ok = false; }
    if (!ecPhone.value.trim())  { showErr(ecPhone,  'Emergency contact phone is required.'); ok = false; }

    if (ok) changeStep(3);
}

// ── Step 3 validation ────────────────────────────────────────────────────────
function validateStep3() {
    clearErrors();
    const idType   = document.querySelector('select[name="government_id_type"]');
    const idNumber = document.querySelector('input[name="government_id_number"]');
    const fileInput = document.getElementById('file_id');
    let ok = true;

    if (!idType.value)          { showErr(idType,   'Please select an ID type.'); ok = false; }
    if (!idNumber.value.trim()) { showErr(idNumber, 'Please enter your ID number.'); ok = false; }
    if (!fileInput.files || !fileInput.files[0]) {
        showErr('uploadArea', 'Please upload a photo of your government ID.'); ok = false;
    }

    if (ok) changeStep(4);
}

// ══════════════════════════════════════════════════════════════════════════════
// OTP LOGIC
// ══════════════════════════════════════════════════════════════════════════════

let otpCountdownTimer = null;

// Send OTP via AJAX to index.php?route=otp_send
async function sendOtp() {
    const phone = document.getElementById('phone_number').value.trim();
    const btn   = document.getElementById('btnSendOtp');

    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-2" class="spin-icon"></i> Sending…';
    if (typeof lucide !== 'undefined') lucide.createIcons();

    try {
        const res  = await fetch('index.php?route=otp_send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone }),
        });
        const data = await res.json();

        if (data.success) {
            // Show input section, hide send button section
            document.getElementById('otpSendSection').style.display = 'none';
            document.getElementById('otpInputSection').style.display = 'block';

            // Demo notice: shows the generated code on screen
            if (data.demo_code) {
                const notice = document.getElementById('otpDemoNotice');
                notice.style.display = 'flex';
                document.getElementById('otpDemoCode').textContent = data.demo_code;
            }

            // Start countdown
            startCountdown(data.ttl || 300);

            // Focus first box
            document.querySelector('.otp-box')?.focus();

        } else {
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="send"></i> Send Verification Code';
            if (typeof lucide !== 'undefined') lucide.createIcons();
            showOtpError(data.message || 'Failed to send code. Please try again.');
        }
    } catch (err) {
        btn.disabled = false;
        btn.innerHTML = '<i data-lucide="send"></i> Send Verification Code';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        showOtpError('Network error. Please check your connection and try again.');
    }
}

// Verify entered OTP code
async function verifyOtp() {
    clearOtpErrors();
    const code = getOtpCode();

    if (code.length !== 6 || !/^\d{6}$/.test(code)) {
        showOtpError('Please enter the complete 6-digit code.');
        return;
    }

    document.getElementById('otpHiddenInput').value = code;

    try {
        const res  = await fetch('index.php?route=otp_verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code }),
        });
        const data = await res.json();

        if (data.success) {
            // Mark verified
            document.getElementById('phoneVerifiedFlag').value = '1';
            stopCountdown();

            // Show verified state
            document.getElementById('otpInputSection').style.display  = 'none';
            document.getElementById('otpDemoNotice').style.display    = 'none';
            document.getElementById('otpVerifiedSection').style.display = 'block';

            // Enable submit button
            const submit = document.getElementById('submitBtn');
            submit.disabled = false;
            submit.classList.add('btn-complete--ready');

        } else {
            showOtpError(data.message || 'Incorrect code. Please try again.');

            if (data.expired || data.locked) {
                // Reset to "send" state
                stopCountdown();
                document.getElementById('otpInputSection').style.display = 'none';
                document.getElementById('otpDemoNotice').style.display   = 'none';
                document.getElementById('otpSendSection').style.display  = 'block';
                const btn = document.getElementById('btnSendOtp');
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="send"></i> Send New Code';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } else {
                // Shake boxes to indicate error
                const boxes = document.getElementById('otpBoxes');
                boxes.classList.add('shake');
                setTimeout(() => boxes.classList.remove('shake'), 500);
            }
        }
    } catch (err) {
        showOtpError('Network error. Please try again.');
    }
}

// Assemble code from individual boxes
function getOtpCode() {
    return Array.from(document.querySelectorAll('.otp-box'))
        .map(b => b.value)
        .join('');
}

// Countdown timer
function startCountdown(seconds) {
    stopCountdown();
    let remaining = seconds;
    const el = document.getElementById('otpCountdown');
    const timerWrap  = document.getElementById('otpTimerWrap');
    const resendWrap = document.getElementById('otpResendWrap');
    timerWrap.style.display  = 'block';
    resendWrap.style.display = 'none';

    function tick() {
        const m = String(Math.floor(remaining / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        el.textContent = `${m}:${s}`;

        if (remaining <= 30) el.style.color = '#ef4444';

        if (remaining <= 0) {
            stopCountdown();
            timerWrap.style.display  = 'none';
            resendWrap.style.display = 'block';
            return;
        }
        remaining--;
    }
    tick();
    otpCountdownTimer = setInterval(tick, 1000);
}

function stopCountdown() {
    if (otpCountdownTimer) { clearInterval(otpCountdownTimer); otpCountdownTimer = null; }
}

// OTP box keyboard UX — auto-advance and backspace handling
document.addEventListener('DOMContentLoaded', () => {
    const boxes = document.querySelectorAll('.otp-box');

    boxes.forEach((box, idx) => {
        box.addEventListener('input', (e) => {
            const val = e.target.value.replace(/\D/g, '');
            e.target.value = val;
            if (val && idx < boxes.length - 1) boxes[idx + 1].focus();
        });

        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !box.value && idx > 0) {
                boxes[idx - 1].focus();
                boxes[idx - 1].value = '';
            }
        });

        box.addEventListener('paste', (e) => {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData)
                .getData('text').replace(/\D/g, '').slice(0, 6);
            boxes.forEach((b, i) => { b.value = pasted[i] || ''; });
            const nextEmpty = Array.from(boxes).findIndex(b => !b.value);
            (nextEmpty === -1 ? boxes[5] : boxes[nextEmpty]).focus();
        });
    });
});

// OTP error helpers
function showOtpError(msg) {
    let el = document.getElementById('otpErrorMsg');
    if (!el) {
        el = document.createElement('p');
        el.id = 'otpErrorMsg';
        el.className = 'field-error-msg otp-error';
        document.getElementById('otpInputSection').insertBefore(
            el,
            document.querySelector('.otp-boxes')?.nextSibling
        );
    }
    el.textContent = msg;
}
function clearOtpErrors() {
    const el = document.getElementById('otpErrorMsg');
    if (el) el.remove();
}

// ══════════════════════════════════════════════════════════════════════════════
// SHARED HELPERS
// ══════════════════════════════════════════════════════════════════════════════

function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    if (!input || !icon) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    icon.setAttribute('data-lucide', input.type === 'password' ? 'eye' : 'eye-off');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function previewID(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    if (file.size > 5 * 1024 * 1024) {
        showErr('uploadArea', 'File too large — max 5MB.'); input.value = ''; return;
    }
    if (!['image/jpeg','image/png','application/pdf'].includes(file.type)) {
        showErr('uploadArea', 'Invalid type — JPG, PNG or PDF only.'); input.value = ''; return;
    }
    document.getElementById('uploadPrompt').style.display = 'none';
    const preview = document.getElementById('uploadPreview');
    preview.style.display = 'block';
    document.getElementById('previewName').textContent = file.name;
    if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = e => { document.getElementById('previewImg').src = e.target.result; };
        reader.readAsDataURL(file);
    } else {
        document.getElementById('previewImg').alt = '';
        document.getElementById('previewName').textContent = '📄 ' + file.name;
    }
}

function showErr(fieldOrId, message) {
    const field = typeof fieldOrId === 'string'
        ? document.getElementById(fieldOrId)
        : fieldOrId;
    if (!field) return;
    field.classList.add('input-error');
    const err = document.createElement('span');
    err.className = 'field-error-msg';
    err.textContent = message;
    field.parentNode.insertBefore(err, field.nextSibling);
}

function clearErrors() {
    document.querySelectorAll('.input-error').forEach(el => el.classList.remove('input-error'));
    document.querySelectorAll('.field-error-msg').forEach(el => el.remove());
}

document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
