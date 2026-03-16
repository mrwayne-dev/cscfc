<?php
require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../helpers.php';

require_method('GET');

session_name(SESSION_NAME);
session_start();

if (empty($_SESSION['admin_id'])) {
    json_err('Unauthorized.', 401);
}

json_ok(['admin' => $_SESSION['admin_user'] ?? 'admin']);
