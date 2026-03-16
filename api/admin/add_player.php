<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

require_method('POST');

session_name(SESSION_NAME);
session_start();
if (empty($_SESSION['admin_id'])) json_err('Unauthorized.', 401);

$body  = get_json_body();
$name  = trim($body['name']  ?? '');
$email = trim($body['email'] ?? '');

if ($name === '') json_err('Player name is required.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_err('A valid email address is required.');

$db = getDB();

// Check for duplicate email
$check = $db->prepare("SELECT id FROM players WHERE email = ? LIMIT 1");
$check->execute([$email]);
if ($check->fetch()) {
    json_err('A player with this email already exists.');
}

$ins = $db->prepare("INSERT INTO players (name, email, target_amount) VALUES (?, ?, ?)");
$ins->execute([$name, $email, PLAYER_TARGET]);
$newId = (int) $db->lastInsertId();

json_ok([
    'player' => [
        'id'                => $newId,
        'name'              => $name,
        'email'             => $email,
        'target_amount'     => PLAYER_TARGET,
        'amount_paid'       => 0,
        'remaining_balance' => PLAYER_TARGET,
        'status'            => 'unpaid',
    ],
]);
