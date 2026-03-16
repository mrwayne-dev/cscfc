<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_method('GET');

$reference = trim($_GET['reference'] ?? '');
if ($reference === '') json_err('Missing reference parameter.');

$db = getDB();

// Check if already marked success
$stmt = $db->prepare("
    SELECT
        py.*,
        pl.name AS player_name,
        pl.target_amount,
        COALESCE(SUM(CASE WHEN py2.payment_status='success' THEN py2.amount ELSE 0 END), 0) AS total_paid
    FROM payments py
    JOIN players pl ON pl.id = py.player_id
    LEFT JOIN payments py2 ON py2.player_id = pl.id AND py2.payment_status='success'
    WHERE py.reference = ?
    GROUP BY py.id
");
$stmt->execute([$reference]);
$row = $stmt->fetch();

if (!$row) json_err('Payment not found.', 404);

if ($row['payment_status'] === 'success') {
    $remaining = max(0, (int)$row['target_amount'] - (int)$row['total_paid']);
    json_ok([
        'player_name'       => $row['player_name'],
        'email'             => $row['email'],
        'amount'            => (int)$row['amount'],
        'reference'         => $row['reference'],
        'paid_at'           => $row['paid_at'],
        'payment_status'    => 'success',
        'remaining_balance' => $remaining,
    ]);
}

// Query Paystack
$ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . PAYSTACK_SECRET_KEY],
    CURLOPT_TIMEOUT        => 30,
    // Local dev only — remove before deploying to production
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);
$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);
if (!($result['status'] ?? false)) {
    json_err('Could not verify payment with Paystack.');
}

$data = $result['data'];

if ($data['status'] !== 'success') {
    json_err('Payment has not been completed yet.');
}

// Update DB
$db->prepare("
    UPDATE payments
    SET payment_status = 'success',
        paid_at        = NOW(),
        channel        = ?
    WHERE reference = ?
")->execute([$data['channel'] ?? null, $reference]);

// Re-fetch totals
$stmt->execute([$reference]);
$row = $stmt->fetch();
$remaining = max(0, (int)$row['target_amount'] - (int)$row['total_paid']);

json_ok([
    'player_name'       => $row['player_name'],
    'email'             => $row['email'],
    'amount'            => (int)$row['amount'],
    'reference'         => $row['reference'],
    'paid_at'           => $row['paid_at'],
    'payment_status'    => 'success',
    'remaining_balance' => $remaining,
]);
