<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../mail/mailer.php';

require_method('POST');

session_name(SESSION_NAME);
session_start();
if (empty($_SESSION['admin_id'])) json_err('Unauthorized.', 401);

$body         = get_json_body();
$name         = trim($body['name']          ?? '');
$fullName     = trim($body['full_name']     ?? '');
$matricNumber = trim($body['matric_number'] ?? '');
$email        = trim($body['email']         ?? '');

if ($name === '') json_err('Player name is required.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_err('A valid email address is required.');
if (mb_strlen($name)         > 100) json_err('Display name is too long.');
if (mb_strlen($fullName)     > 150) json_err('Full name is too long.');
if (mb_strlen($matricNumber) > 50)  json_err('Matric number is too long.');

// Normalise blanks to NULL so the unique index doesn't fire on empty strings
$fullNameToStore     = $fullName     === '' ? null : $fullName;
$matricNumberToStore = $matricNumber === '' ? null : $matricNumber;

$db = getDB();

// Check for duplicate email
$check = $db->prepare("SELECT id FROM players WHERE email = ? LIMIT 1");
$check->execute([$email]);
if ($check->fetch()) {
    json_err('A player with this email already exists.');
}

// Check for duplicate matric (when provided)
if ($matricNumberToStore !== null) {
    $checkMatric = $db->prepare("SELECT id FROM players WHERE matric_number = ? LIMIT 1");
    $checkMatric->execute([$matricNumberToStore]);
    if ($checkMatric->fetch()) {
        json_err('A player with this matric number already exists.');
    }
}

$ins = $db->prepare("
    INSERT INTO players (name, full_name, matric_number, email, target_amount)
    VALUES (?, ?, ?, ?, ?)
");
$ins->execute([$name, $fullNameToStore, $matricNumberToStore, $email, PLAYER_TARGET]);
$newId = (int) $db->lastInsertId();

// Confirmation email — only if we have both full name and matric to include.
// New roster entries always have outstanding = full target.
$emailSent = false;
if ($fullNameToStore !== null && $matricNumberToStore !== null) {
    $emailSent = sendProfileSavedEmail($email, $name, $fullNameToStore, $matricNumberToStore, true);
}

json_ok([
    'email_sent' => $emailSent,
    'player' => [
        'id'                => $newId,
        'name'              => $name,
        'full_name'         => $fullNameToStore,
        'matric_number'     => $matricNumberToStore,
        'email'             => $email,
        'has_email'         => true,
        'target_amount'     => PLAYER_TARGET,
        'amount_paid'       => 0,
        'remaining_balance' => PLAYER_TARGET,
        'status'            => 'unpaid',
    ],
]);
