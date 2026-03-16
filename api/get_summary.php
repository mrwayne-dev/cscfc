<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_method('GET');

$db = getDB();

// Total collected from successful payments
$collected = (int) $db->query("
    SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payment_status = 'success'
")->fetchColumn();

// Total number of successful transactions
$paymentCount = (int) $db->query("
    SELECT COUNT(*) FROM payments WHERE payment_status = 'success'
")->fetchColumn();

// Player count
$playerCount = (int) $db->query("SELECT COUNT(*) FROM players")->fetchColumn();

// Per-player paid amounts
$playerRows = $db->query("
    SELECT
        pl.target_amount,
        COALESCE(SUM(CASE WHEN py.payment_status = 'success' THEN py.amount ELSE 0 END), 0) AS amount_paid
    FROM players pl
    LEFT JOIN payments py ON py.player_id = pl.id
    GROUP BY pl.id
")->fetchAll();

$fullyPaid = 0; $partial = 0; $unpaid = 0;
foreach ($playerRows as $row) {
    $status = player_status((int)$row['amount_paid'], (int)$row['target_amount']);
    if ($status === 'fully_paid') $fullyPaid++;
    elseif ($status === 'partial') $partial++;
    else $unpaid++;
}

$target  = PLAYER_TARGET * TOTAL_PLAYERS;
$percent = $target > 0 ? (int) round(($collected / $target) * 100) : 0;

json_ok([
    'total_collected' => $collected,
    'target'          => $target,
    'player_count'    => $playerCount,
    'payment_count'   => $paymentCount,
    'fully_paid'      => $fullyPaid,
    'partial'         => $partial,
    'unpaid'          => $unpaid,
    'percent'         => min(100, $percent),
]);
