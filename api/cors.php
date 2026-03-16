<?php
require_once __DIR__ . '/config.php';

$origin  = $_SERVER['HTTP_ORIGIN'] ?? '';

// Derive the bare origin from APP_URL (scheme + host only, no path).
// This handles project-site deployments where APP_URL contains a path
// (e.g. https://mrwayne-dev.github.io/cscfc) but the browser sends
// Origin: https://mrwayne-dev.github.io (no path).
$appOrigin = parse_url(APP_URL, PHP_URL_SCHEME) . '://' . parse_url(APP_URL, PHP_URL_HOST);
$allowed   = array_unique([APP_URL, $appOrigin, 'http://localhost', 'http://127.0.0.1']);

if (in_array($origin, $allowed, true)) {
    header('Access-Control-Allow-Origin: ' . $origin);
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
