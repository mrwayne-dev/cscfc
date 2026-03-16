<?php
require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';

require_method('GET');

$playerId = isset($_GET['player_id']) ? (int) $_GET['player_id'] : 0;
if ($playerId <= 0) {
    json_err('Missing or invalid player_id.');
}

$db = getDB();

$row = $db->prepare("
    SELECT
        pl.id,
        pl.name,
        pl.email,
        pl.target_amount,
        COALESCE(SUM(CASE WHEN py.payment_status = 'success' THEN py.amount ELSE 0 END), 0) AS amount_paid
    FROM players pl
    LEFT JOIN payments py ON py.player_id = pl.id
    WHERE pl.id = ?
    GROUP BY pl.id
");
$row->execute([$playerId]);
$player = $row->fetch();

if (!$player) {
    json_err('Player not found.', 404);
}

$paid      = (int) $player['amount_paid'];
$target    = (int) $player['target_amount'];
$remaining = max(0, $target - $paid);

json_ok([
    'player_id'         => (int) $player['id'],
    'name'              => $player['name'],
    'email'             => $player['email'],
    'target_amount'     => $target,
    'amount_paid'       => $paid,
    'remaining_balance' => $remaining,
    'status'            => player_status($paid, $target),
]);
