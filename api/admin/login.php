<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

require_method('POST');

// Start session
session_name(SESSION_NAME);
session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'secure'   => false,    // set true on HTTPS
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

$body     = get_json_body();
$username = trim($body['username'] ?? '');
$password = $body['password'] ?? '';

if ($username === '' || $password === '') {
    json_err('Username and password are required.');
}

$db   = getDB();
$stmt = $db->prepare("SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1");
$stmt->execute([$username]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password_hash'])) {
    json_err('Invalid username or password.', 401);
}

$_SESSION['admin_id']   = $admin['id'];
$_SESSION['admin_user'] = $admin['username'];

json_ok(['message' => 'Logged in successfully.']);
