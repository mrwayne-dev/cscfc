<?php
/**
 * Admin-only endpoint to edit an existing player's profile
 * (display name, full name, matric number, email).
 */

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
$playerId     = isset($body['player_id']) ? (int) $body['player_id'] : 0;
$name         = trim($body['name']          ?? '');
$fullName     = trim($body['full_name']     ?? '');
$matricNumber = trim($body['matric_number'] ?? '');
$email        = trim($body['email']         ?? '');

if ($playerId <= 0)                             json_err('Invalid player_id.');
if ($name === '')                               json_err('Display name is required.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) json_err('A valid email address is required.');
if (mb_strlen($name)         > 100)             json_err('Display name is too long.');
if (mb_strlen($fullName)     > 150)             json_err('Full name is too long.');
if (mb_strlen($matricNumber) > 50)              json_err('Matric number is too long.');

$fullNameToStore     = $fullName     === '' ? null : $fullName;
$matricNumberToStore = $matricNumber === '' ? null : $matricNumber;

$db = getDB();

// Pull existing row so we can detect changes and decide whether to email
$check = $db->prepare("
    SELECT
        pl.id,
        pl.name,
        pl.full_name,
        pl.matric_number,
        pl.email,
        pl.target_amount,
        COALESCE(SUM(CASE WHEN py.payment_status='success' THEN py.amount ELSE 0 END), 0) AS amount_paid
    FROM players pl
    LEFT JOIN payments py ON py.player_id = pl.id
    WHERE pl.id = ?
    GROUP BY pl.id
");
$check->execute([$playerId]);
$existing = $check->fetch();
if (!$existing) {
    json_err('Player not found.', 404);
}

$dupEmail = $db->prepare("SELECT id FROM players WHERE email = ? AND id <> ? LIMIT 1");
$dupEmail->execute([$email, $playerId]);
if ($dupEmail->fetch()) {
    json_err('Another player already uses this email.');
}

if ($matricNumberToStore !== null) {
    $dupMatric = $db->prepare("SELECT id FROM players WHERE matric_number = ? AND id <> ? LIMIT 1");
    $dupMatric->execute([$matricNumberToStore, $playerId]);
    if ($dupMatric->fetch()) {
        json_err('Another player already uses this matric number.');
    }
}

$profileChanged = (
    ((string) ($existing['full_name']     ?? '')) !== (string) ($fullNameToStore     ?? '') ||
    ((string) ($existing['matric_number'] ?? '')) !== (string) ($matricNumberToStore ?? '') ||
    ((string) ($existing['email']         ?? '')) !== $email
);

$upd = $db->prepare("
    UPDATE players
       SET name = ?, full_name = ?, matric_number = ?, email = ?
     WHERE id = ?
");
$upd->execute([$name, $fullNameToStore, $matricNumberToStore, $email, $playerId]);

// Confirmation email — only when something the player would care about changed
// AND we have both full name and matric to include meaningfully.
$emailSent = false;
if ($profileChanged && $fullNameToStore !== null && $matricNumberToStore !== null) {
    $hasOutstanding = (int) $existing['amount_paid'] < (int) $existing['target_amount'];
    $emailSent = sendProfileSavedEmail($email, $name, $fullNameToStore, $matricNumberToStore, $hasOutstanding);
}

json_ok([
    'message'    => $profileChanged
        ? ($emailSent ? 'Player updated. Confirmation email sent.' : 'Player updated.')
        : 'Player updated (no changes detected).',
    'email_sent' => $emailSent,
    'player'     => [
        'id'            => $playerId,
        'name'          => $name,
        'full_name'     => $fullNameToStore,
        'matric_number' => $matricNumberToStore,
        'email'         => $email,
    ],
]);
