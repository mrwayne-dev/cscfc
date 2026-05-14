<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/mail/mailer.php';

require_method('POST');

$body         = get_json_body();
$playerId     = isset($body['player_id']) ? (int) $body['player_id'] : 0;
$email        = trim($body['email']  ?? '');
$amount       = isset($body['amount']) ? (int) $body['amount'] : 0;
$type         = ($body['payment_type'] ?? 'installment') === 'full' ? 'full' : 'installment';
$fullName     = trim($body['full_name']     ?? '');
$matricNumber = trim($body['matric_number'] ?? '');

// Basic validation
if ($playerId <= 0) json_err('Invalid player_id.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_err('Invalid email address.');
if ($amount < MIN_INSTALLMENT) json_err('Amount must be at least ₦' . number_format(MIN_INSTALLMENT) . '.');
if ($fullName === '') json_err('Full name is required.');
if ($matricNumber === '') json_err('Matric number is required.');
if (mb_strlen($fullName)     > 150) json_err('Full name is too long.');
if (mb_strlen($matricNumber) > 50)  json_err('Matric number is too long.');

$db = getDB();

// Fetch player + current paid total (existing profile fields captured here so
// we can detect first-time-or-changed profile and trigger a confirmation email)
$stmt = $db->prepare("
    SELECT
        pl.id,
        pl.name,
        pl.full_name,
        pl.matric_number,
        pl.email,
        pl.target_amount,
        COALESCE(SUM(CASE WHEN py.payment_status = 'success' THEN py.amount ELSE 0 END), 0) AS amount_paid
    FROM players pl
    LEFT JOIN payments py ON py.player_id = pl.id
    WHERE pl.id = ?
    GROUP BY pl.id
");
$stmt->execute([$playerId]);
$player = $stmt->fetch();

if (!$player) json_err('Player not found.', 404);

$remaining = max(0, (int)$player['target_amount'] - (int)$player['amount_paid']);

if ($remaining <= 0) {
    // Fully paid — but they may still want to update their details. Caller's
    // UI uses the `code` field to redirect them to /register instead of showing
    // a dead-end error.
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'code'    => 'already_paid',
        'message' => "You're already fully paid up — nothing more to contribute. If you need to update your details, use the Register page.",
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($amount > $remaining) json_err('Amount exceeds remaining balance of ₦' . number_format($remaining) . '.');

// Reject submissions whose matric/email is already attached to a different player
$dupMatric = $db->prepare("SELECT id FROM players WHERE matric_number = ? AND id <> ? LIMIT 1");
$dupMatric->execute([$matricNumber, $playerId]);
if ($dupMatric->fetch()) json_err('This matric number is already registered to another player.');

$dupEmail = $db->prepare("SELECT id FROM players WHERE email = ? AND id <> ? LIMIT 1");
$dupEmail->execute([$email, $playerId]);
if ($dupEmail->fetch()) json_err('This email is already registered to another player.');

// Generate unique reference
$reference = 'CSCFC-' . $playerId . '-' . strtoupper(substr(uniqid('', true), -8));

// Insert pending payment
$ins = $db->prepare("
    INSERT INTO payments (player_id, email, amount, reference, payment_type, payment_status)
    VALUES (?, ?, ?, ?, ?, 'pending')
");
$ins->execute([$playerId, $email, $amount, $reference, $type]);

// Call Paystack initialize
$paystackPayload = json_encode([
    'email'        => $email,
    'amount'       => $amount * 100,           // Paystack requires kobo
    'reference'    => $reference,
    'callback_url' => APP_URL . '/receipt',
    'metadata'     => [
        'player_id'   => $playerId,
        'player_name' => $player['name'],
    ],
]);

$ch = curl_init('https://api.paystack.co/transaction/initialize');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $paystackPayload,
    CURLOPT_HTTPHEADER     => [
        'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
        'Content-Type: application/json',
    ],
    CURLOPT_TIMEOUT        => 30,
    // Local dev only — remove before deploying to production
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);
$response = curl_exec($ch);
$curlError = curl_error($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false || $httpCode !== 200) {
    error_log('Paystack cURL error: ' . $curlError . ' | HTTP ' . $httpCode);
    // Mark payment as failed so reference is freed on retry
    $db->prepare("UPDATE payments SET payment_status='failed' WHERE reference=?")->execute([$reference]);
    json_err('Could not connect to payment gateway. Please try again.');
}

$result = json_decode($response, true);
if (!($result['status'] ?? false)) {
    $db->prepare("UPDATE payments SET payment_status='failed' WHERE reference=?")->execute([$reference]);
    json_err($result['message'] ?? 'Payment gateway error.');
}

// Detect whether the profile is actually changing — used below so we don't
// spam a confirmation email on every installment payment.
$profileChanged = (
    ((string) ($player['full_name']     ?? '')) !== $fullName     ||
    ((string) ($player['matric_number'] ?? '')) !== $matricNumber ||
    ((string) ($player['email']         ?? '')) !== $email
);

// Store the player's profile fields on their record (first-time capture / update)
$db->prepare("UPDATE players SET full_name = ?, matric_number = ?, email = ? WHERE id = ?")
   ->execute([$fullName, $matricNumber, $email, $playerId]);

// Fire the profile-saved email when the captured details actually changed.
// Best-effort: a mailer failure must not block the Paystack redirect.
// NOTE: this runs before Paystack confirms the payment, so the CTA must
// reflect the player's CURRENT balance — not an optimistic post-payment one.
$profileEmailSent = false;
if ($profileChanged) {
    $hasOutstanding   = (int) $player['amount_paid'] < (int) $player['target_amount'];
    $profileEmailSent = sendProfileSavedEmail($email, $player['name'], $fullName, $matricNumber, $hasOutstanding);
}

json_ok([
    'authorization_url'  => $result['data']['authorization_url'],
    'profile_updated'    => $profileChanged,
    'profile_email_sent' => $profileEmailSent,
]);
