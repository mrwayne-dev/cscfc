<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

require_method('GET');

session_name(SESSION_NAME);
session_start();
if (empty($_SESSION['admin_id'])) json_err('Unauthorized.', 401);

$db = getDB();

$rows = $db->query("
    SELECT
        py.id,
        py.reference,
        py.amount,
        py.payment_type,
        py.payment_status AS status,
        py.paid_at,
        py.channel,
        py.created_at,
        pl.name  AS player_name,
        pl.email
    FROM payments py
    JOIN players pl ON pl.id = py.player_id
    ORDER BY py.created_at DESC
")->fetchAll();

$payments = [];
foreach ($rows as $row) {
    $payments[] = [
        'id'           => (int) $row['id'],
        'player_name'  => $row['player_name'],
        'email'        => $row['email'],
        'amount'       => (int) $row['amount'],
        'reference'    => $row['reference'],
        'payment_type' => $row['payment_type'],
        'status'       => $row['status'],
        'paid_at'      => $row['paid_at'],
        'channel'      => $row['channel'],
    ];
}

json_ok(['payments' => $payments]);
