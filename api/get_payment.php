<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_method('GET');

$reference = trim($_GET['reference'] ?? '');
if ($reference === '') {
    json_err('Missing reference parameter.');
}

$db = getDB();

$stmt = $db->prepare("
    SELECT
        py.id,
        py.reference,
        py.amount,
        py.payment_type,
        py.payment_status,
        py.paid_at,
        py.channel,
        pl.id   AS player_id,
        pl.name AS player_name,
        pl.email,
        pl.target_amount,
        COALESCE(SUM(CASE WHEN py2.payment_status = 'success' THEN py2.amount ELSE 0 END), 0) AS total_paid
    FROM payments py
    JOIN players pl ON pl.id = py.player_id
    LEFT JOIN payments py2 ON py2.player_id = pl.id AND py2.payment_status = 'success'
    WHERE py.reference = ?
    GROUP BY py.id
");
$stmt->execute([$reference]);
$row = $stmt->fetch();

if (!$row) {
    json_err('Payment not found.', 404);
}

if ($row['payment_status'] !== 'success') {
    json_err('Payment has not been confirmed yet. It may still be processing.', 404);
}

$target    = (int) $row['target_amount'];
$totalPaid = (int) $row['total_paid'];
$remaining = max(0, $target - $totalPaid);

json_ok([
    'player_name'       => $row['player_name'],
    'email'             => $row['email'],
    'amount'            => (int) $row['amount'],
    'reference'         => $row['reference'],
    'paid_at'           => $row['paid_at'],
    'payment_type'      => $row['payment_type'],
    'payment_status'    => $row['payment_status'],
    'remaining_balance' => $remaining,
]);
