<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_method('POST');

$body     = get_json_body();
$playerId = isset($body['player_id']) ? (int) $body['player_id'] : 0;
$email    = trim($body['email']  ?? '');
$amount   = isset($body['amount']) ? (int) $body['amount'] : 0;
$type     = ($body['payment_type'] ?? 'installment') === 'full' ? 'full' : 'installment';

// Basic validation
if ($playerId <= 0) json_err('Invalid player_id.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_err('Invalid email address.');
if ($amount < MIN_INSTALLMENT) json_err('Amount must be at least ₦' . number_format(MIN_INSTALLMENT) . '.');

$db = getDB();

// Fetch player + current paid total
$stmt = $db->prepare("
    SELECT
        pl.id,
        pl.name,
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

if ($remaining <= 0) json_err('This player has already completed their payment.');
if ($amount > $remaining) json_err('Amount exceeds remaining balance of ₦' . number_format($remaining) . '.');

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

// Store the player's email on their record (first-time capture / update)
$db->prepare("UPDATE players SET email = ? WHERE id = ?")->execute([$email, $playerId]);

json_ok(['authorization_url' => $result['data']['authorization_url']]);
