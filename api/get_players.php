<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_method('GET');

$db = getDB();

$rows = $db->query("
    SELECT
        pl.id,
        pl.name,
        pl.email,
        pl.target_amount,
        COALESCE(SUM(CASE WHEN py.payment_status = 'success' THEN py.amount ELSE 0 END), 0) AS amount_paid
    FROM players pl
    LEFT JOIN payments py ON py.player_id = pl.id
    GROUP BY pl.id
    ORDER BY pl.name ASC
")->fetchAll();

$players = [];
foreach ($rows as $row) {
    $paid      = (int) $row['amount_paid'];
    $target    = (int) $row['target_amount'];
    $remaining = max(0, $target - $paid);
    $players[] = [
        'id'                => (int) $row['id'],
        'name'              => $row['name'],
        'email'             => $row['email'],
        'target_amount'     => $target,
        'amount_paid'       => $paid,
        'remaining_balance' => $remaining,
        'status'            => player_status($paid, $target),
    ];
}

json_ok(['players' => $players]);
