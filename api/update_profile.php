<?php
/**
 * Public profile-update endpoint.
 *
 * Lets a player self-register their full name, matric number, and email
 * without going through the payment flow. Same trust model as
 * initiate-payment.php — no auth, identity is asserted by the caller picking
 * their own player_id from the dropdown.
 */

require_once __DIR__ . '/cors.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/mail/mailer.php';

require_method('POST');

$body         = get_json_body();
$playerId     = isset($body['player_id']) ? (int) $body['player_id'] : 0;
$fullName     = trim($body['full_name']     ?? '');
$matricNumber = trim($body['matric_number'] ?? '');
$email        = trim($body['email']         ?? '');

if ($playerId <= 0)                                  json_err('Invalid player_id.');
if ($fullName === '')                                json_err('Full name is required.');
if ($matricNumber === '')                            json_err('Matric number is required.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))      json_err('A valid email address is required.');
if (mb_strlen($fullName)     > 150)                  json_err('Full name is too long.');
if (mb_strlen($matricNumber) > 50)                   json_err('Matric number is too long.');

$db = getDB();

// Player must exist; pull current paid total so the email's CTA reflects state
$check = $db->prepare("
    SELECT
        pl.id,
        pl.name,
        pl.target_amount,
        COALESCE(SUM(CASE WHEN py.payment_status='success' THEN py.amount ELSE 0 END), 0) AS amount_paid
    FROM players pl
    LEFT JOIN payments py ON py.player_id = pl.id
    WHERE pl.id = ?
    GROUP BY pl.id
");
$check->execute([$playerId]);
$player = $check->fetch();
if (!$player) {
    json_err('Player not found.', 404);
}
$displayName     = $player['name'];
$hasOutstanding  = (int) $player['amount_paid'] < (int) $player['target_amount'];

// Matric must be unique across other players
$dupMatric = $db->prepare("SELECT id FROM players WHERE matric_number = ? AND id <> ? LIMIT 1");
$dupMatric->execute([$matricNumber, $playerId]);
if ($dupMatric->fetch()) {
    json_err('This matric number is already registered to another player.');
}

// Email must be unique across other players
$dupEmail = $db->prepare("SELECT id FROM players WHERE email = ? AND id <> ? LIMIT 1");
$dupEmail->execute([$email, $playerId]);
if ($dupEmail->fetch()) {
    json_err('This email is already registered to another player.');
}

$upd = $db->prepare("
    UPDATE players
       SET full_name = ?, matric_number = ?, email = ?
     WHERE id = ?
");
$upd->execute([$fullName, $matricNumber, $email, $playerId]);

// Confirmation email — best-effort, never block the response
$mailed = sendProfileSavedEmail($email, $displayName, $fullName, $matricNumber, $hasOutstanding);

json_ok([
    'message'      => $mailed
        ? 'Profile saved. A confirmation email is on its way to ' . $email . '.'
        : 'Profile saved. (We could not deliver a confirmation email right now, but your details are stored.)',
    'email_sent'   => $mailed,
    'player'       => [
        'id'            => $playerId,
        'full_name'     => $fullName,
        'matric_number' => $matricNumber,
        'email'         => $email,
    ],
]);
