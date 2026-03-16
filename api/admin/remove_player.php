<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

require_method('POST');

session_name(SESSION_NAME);
session_start();
if (empty($_SESSION['admin_id'])) json_err('Unauthorized.', 401);

$body     = get_json_body();
$playerId = isset($body['player_id']) ? (int) $body['player_id'] : 0;

if ($playerId <= 0) json_err('Invalid player_id.');

$db  = getDB();
$del = $db->prepare("DELETE FROM players WHERE id = ?");
$del->execute([$playerId]);

if ($del->rowCount() === 0) {
    json_err('Player not found.', 404);
}

json_ok(['message' => 'Player removed successfully.']);
