<?php
// controllers/OtpController.php
// Session-based OTP verification for phone number during registration.
// In production, replace the "send" logic with a real SMS gateway (Semaphore, Twilio, etc.)

require_once 'config/SessionManager.php';

class OtpController {

    private const OTP_TTL     = 300;  // 5 minutes in seconds
    private const MAX_ATTEMPTS = 5;   // lock out after this many wrong guesses

    // ── POST index.php?route=otp_send ────────────────────────────────────────
    public function send() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            exit;
        }

        $body  = json_decode(file_get_contents('php://input'), true);
        $phone = trim($body['phone'] ?? '');

        // Basic PH mobile number validation
        if (!preg_match('/^(09|\+639)\d{9}$/', preg_replace('/\s+/', '', $phone))) {
            echo json_encode(['success' => false, 'message' => 'Invalid phone number format.']);
            exit;
        }

        // Generate 6-digit code
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Store in session with expiry timestamp & attempt counter
        SessionManager::start();
        $_SESSION['otp'] = [
            'code'      => $code,
            'phone'     => $phone,
            'expires'   => time() + self::OTP_TTL,
            'attempts'  => 0,
            'verified'  => false,
        ];

        // ── SMS SEND LOGIC ──────────────────────────────────────────────────
        // Replace this block with your SMS gateway call, e.g.:
        //   Semaphore: https://api.semaphore.co/api/v4/messages
        //   Twilio:    $twilio->messages->create(...)
        //
        // Example Semaphore (uncomment & add your API key):
        // $apiKey  = 'YOUR_SEMAPHORE_API_KEY';
        // $message = "Your DANGPANAN verification code is: {$code}. Valid for 5 minutes.";
        // $ch = curl_init('https://api.semaphore.co/api/v4/messages');
        // curl_setopt_array($ch, [
        //     CURLOPT_POST           => true,
        //     CURLOPT_RETURNTRANSFER => true,
        //     CURLOPT_POSTFIELDS     => http_build_query([
        //         'apikey'      => $apiKey,
        //         'number'      => $phone,
        //         'message'     => $message,
        //         'sendername'  => 'DANGPANAN',
        //     ]),
        // ]);
        // $result = curl_exec($ch);
        // curl_close($ch);
        // ─────────────────────────────────────────────────────────────────────

        // Demo mode: return the code in the response so the UI can display it.
        // REMOVE the 'demo_code' field once you have a real SMS gateway.
        echo json_encode([
            'success'   => true,
            'message'   => 'Code sent successfully.',
            'ttl'       => self::OTP_TTL,
            'demo_code' => $code,   // ← DELETE THIS LINE in production
        ]);
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
}
