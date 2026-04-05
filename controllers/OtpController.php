<?php
// controllers/OtpController.php
// Session-based OTP verification for phone number during registration.
// In production, replace the "send" logic with a real SMS gateway (Semaphore, Twilio, etc.)

require_once 'config/SessionManager.php';
require_once 'models/UserModel.php';

class OtpController {

    private const OTP_TTL     = 300;  // 5 minutes in seconds
    private const MAX_ATTEMPTS = 5;   // lock out after this many wrong guesses
    
    private $pdo;

    public function __construct($pdo = null) {
        $this->pdo = $pdo;
    }

    // ── POST index.php?route=otp_send ────────────────────────────────────────
    // controllers/OtpController.php

public function send() {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        exit;
    }

    $body  = json_decode(file_get_contents('php://input'), true);
    $phone = trim($body['phone'] ?? '');

    // 1. Format Phone Number (PhilSMS requires international format like 639171234567)
    $formattedPhone = preg_replace('/\D/', '', $phone); // Remove non-numeric characters
    if (strpos($formattedPhone, '09') === 0) {
        $formattedPhone = '63' . substr($formattedPhone, 1);
    }

    if (!preg_match('/^639\d{9}$/', $formattedPhone)) {
        echo json_encode(['success' => false, 'message' => 'Invalid PH phone number format.']);
        exit;
    }

    // 2. Generate 6-digit code and store in session
    $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    SessionManager::start();
    $_SESSION['otp'] = [
        'code'      => $code,
        'phone'     => $phone,
        'expires'   => time() + self::OTP_TTL,
        'attempts'  => 0,
        'verified'  => false,
    ];

    // 3. PhilSMS API Implementation
    // Replace with your actual token from the dashboard
    $apiToken = '1823|CDbi6fDLpBbPjU4jOkslVWiyo7l4ZzPLJZhiEpyn0603366d '; 
    $senderId = 'PhilSMS'; // Must be your approved Sender ID
    $message  = "Your DANGPANAN verification code is: {$code}. Valid for 5 minutes.";

    // Payload exactly as required by PhilSMS v3 Send Outbound SMS
    $payload = [
        'recipient' => $formattedPhone,
        'sender_id' => $senderId,
        'type'      => 'plain', // Required: 'plain' or 'unicode'
        'message'   => $message,
    ];

    $ch = curl_init('https://dashboard.philsms.com/api/v3/sms/send');
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => [
            "Authorization: Bearer " . $apiToken,
            "Content-Type: application/json",
            "Accept: application/json",
        ],
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resData = json_decode($response, true);

    // PhilSMS returns {"status": "success", "data": "..."} on success
    if ($httpCode === 200 && isset($resData['status']) && $resData['status'] === 'success') {
        echo json_encode([
            'success' => true,
            'message' => 'Code sent successfully.',
            'ttl'     => self::OTP_TTL
        ]);
    } else {
        $errorMsg = $resData['message'] ?? 'Failed to send SMS. Please check your balance or API token.';
        echo json_encode(['success' => false, 'message' => $errorMsg]);
    }
    exit;
}

    // ── POST index.php?route=otp_verify ──────────────────────────────────────
    public function verify() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        SessionManager::start();

        $body  = json_decode(file_get_contents('php://input'), true);
        $input = trim($body['code'] ?? '');

        // Session guard
        if (empty($_SESSION['otp'])) {
            echo json_encode(['success' => false, 'message' => 'No active verification request. Please request a new code.']);
            exit;
        }

        $otp = &$_SESSION['otp'];

        // Already verified
        if (!empty($otp['verified'])) {
            echo json_encode(['success' => true, 'message' => 'Already verified.']);
            exit;
        }

        // Expired
        if (time() > $otp['expires']) {
            unset($_SESSION['otp']);
            echo json_encode(['success' => false, 'expired' => true, 'message' => 'Verification code has expired. Please request a new one.']);
            exit;
        }

        // Too many attempts
        if ($otp['attempts'] >= self::MAX_ATTEMPTS) {
            unset($_SESSION['otp']);
            echo json_encode(['success' => false, 'locked' => true, 'message' => 'Too many incorrect attempts. Please request a new code.']);
            exit;
        }

        $otp['attempts']++;

        if ($input !== $otp['code']) {
            $remaining = self::MAX_ATTEMPTS - $otp['attempts'];
            echo json_encode([
                'success'   => false,
                'message'   => "Incorrect code. {$remaining} attempt(s) remaining.",
                'remaining' => $remaining,
            ]);
            exit;
        }

        // ✓ Correct
        $otp['verified'] = true;
        echo json_encode(['success' => true, 'message' => 'Phone number verified!']);
        exit;
    }

    // ── POST index.php?route=forgot_send_otp ────────────────────────────────
    public function sendPasswordReset() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $body  = json_decode(file_get_contents('php://input'), true);
        $email = trim($body['email'] ?? '');

        // 1. Verify user exists first by email
        if (!$this->pdo) {
            echo json_encode(['success' => false, 'message' => 'Database connection missing.']);
            exit;
        }
        
        $userModel = new UserModel($this->pdo);
        $user = $userModel->getUserByEmail($email);
        
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'This email address is not registered.']);
            exit;
        }

        $phone = $user->getPhoneNumber();
        if (empty($phone)) {
            echo json_encode(['success' => false, 'message' => 'No mobile number associated with this account.']);
            exit;
        }

        // 2. Format Phone Number (PhilSMS format)
        $formattedPhone = preg_replace('/\D/', '', $phone);
        if (strpos($formattedPhone, '09') === 0) {
            $formattedPhone = '63' . substr($formattedPhone, 1);
        }

        if (!preg_match('/^639\d{9}$/', $formattedPhone)) {
            echo json_encode(['success' => false, 'message' => 'Invalid PH phone number format tied to your account.']);
            exit;
        }

        // Create Masked Phone for Frontend Display securely
        $maskedPhone = '';
        if (strlen($phone) >= 10) {
            $maskedPhone = substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 7) . substr($phone, -3);
        } else {
            $maskedPhone = 'your registered number'; // Fallback
        }

        // 3. Generate 6-digit code and store in a distinct password reset session
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        SessionManager::start();
        $_SESSION['pwd_reset_otp'] = [
            'code'      => $code,
            'email'     => $email,
            'phone'     => $phone,       
            'user_id'   => $user->getId(),
            'expires'   => time() + self::OTP_TTL,
            'attempts'  => 0,
            'verified'  => false,
        ];

        // 4. PhilSMS API Implementation
        $apiToken = '1823|CDbi6fDLpBbPjU4jOkslVWiyo7l4ZzPLJZhiEpyn0603366d '; 
        $senderId = 'PhilSMS'; 
        $message  = "Your DANGPANAN Password Reset code is: {$code}. Valid for 5 minutes.";

        $payload = [
            'recipient' => $formattedPhone,
            'sender_id' => $senderId,
            'type'      => 'plain', 
            'message'   => $message,
        ];

        $ch = curl_init('https://dashboard.philsms.com/api/v3/sms/send');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer " . $apiToken,
                "Content-Type: application/json",
                "Accept: application/json",
            ],
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $resData = json_decode($response, true);

        if ($httpCode === 200 && isset($resData['status']) && $resData['status'] === 'success') {
            echo json_encode([
                'success'     => true,
                'message'     => 'Password reset code sent successfully.',
                'maskedPhone' => $maskedPhone,
                'ttl'         => self::OTP_TTL
            ]);
        } else {
            $errorMsg = $resData['message'] ?? 'Failed to send SMS.';
            echo json_encode(['success' => false, 'message' => $errorMsg]);
        }
        exit;
    }

    // ── POST index.php?route=forgot_verify_otp ──────────────────────────────
    public function verifyPasswordReset() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        SessionManager::start();

        $body  = json_decode(file_get_contents('php://input'), true);
        $input = trim($body['code'] ?? '');

        if (empty($_SESSION['pwd_reset_otp'])) {
            echo json_encode(['success' => false, 'message' => 'No active password reset request. Please request a new code.']);
            exit;
        }

        $otp = &$_SESSION['pwd_reset_otp'];

        if (!empty($otp['verified'])) {
            echo json_encode(['success' => true, 'message' => 'Code already verified.']);
            exit;
        }

        if (time() > $otp['expires']) {
            unset($_SESSION['pwd_reset_otp']);
            echo json_encode(['success' => false, 'expired' => true, 'message' => 'Reset code has expired.']);
            exit;
        }

        if ($otp['attempts'] >= self::MAX_ATTEMPTS) {
            unset($_SESSION['pwd_reset_otp']);
            echo json_encode(['success' => false, 'locked' => true, 'message' => 'Too many incorrect attempts. Start over.']);
            exit;
        }

        $otp['attempts']++;

        if ($input !== $otp['code']) {
            $remaining = self::MAX_ATTEMPTS - $otp['attempts'];
            echo json_encode([
                'success'   => false,
                'message'   => "Incorrect code. {$remaining} attempt(s) remaining.",
                'remaining' => $remaining,
            ]);
            exit;
        }

        // ✓ Correct
        $otp['verified'] = true;
        echo json_encode(['success' => true, 'message' => 'Code verified! You can now reset your password.']);
        exit;
    }
}
