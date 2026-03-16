<?php
// ─── Diagnostic endpoint — DELETE THIS FILE after debugging ──────────────────
// Visit: https://growthmining.org/api/ping.php
// Shows whether config loads, DB connects, and what PHP version is running.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$r = ['php' => PHP_VERSION];

// 1. Does config.php exist?
$r['config_exists'] = file_exists(__DIR__ . '/config.php');

// 2. Does config.php load without errors?
if ($r['config_exists']) {
    try {
        require_once __DIR__ . '/config.php';
        $r['config_loaded'] = true;
        $r['app_url']       = defined('APP_URL') ? APP_URL : 'NOT DEFINED';
        $r['db_host']       = defined('DB_HOST') ? DB_HOST : 'NOT DEFINED';
        $r['db_name']       = defined('DB_NAME') ? DB_NAME : 'NOT DEFINED';
    } catch (Throwable $e) {
        $r['config_error'] = $e->getMessage();
    }
}

// 3. Does the DB connect?
if (!empty($r['config_loaded'])) {
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        // Quick schema check
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $r['db_connected'] = true;
        $r['db_tables']    = $tables;
    } catch (Throwable $e) {
        $r['db_connected'] = false;
        $r['db_error']     = $e->getMessage();
    }
}

echo json_encode($r, JSON_PRETTY_PRINT);
