<?php
// ─── One-time Admin Setup Script ─────────────────────────────────────────────
// Run this ONCE to create the initial admin account, then DELETE this file.
// Access: http://cscfc-payment-site.test/api/admin/setup.php?key=CHANGE_THIS_KEY
// ─────────────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

header('Content-Type: text/html; charset=utf-8');

// ── Change this setup key before deploying ────────────────────────────────────
$SETUP_KEY = 'cscFCKey';

// ── Credentials to create ─────────────────────────────────────────────────────
$NEW_USERNAME = 'admin';       // admin username
$NEW_PASSWORD = 'admin@123'; // admin password — change this!

if (($_GET['key'] ?? '') !== $SETUP_KEY) {
    http_response_code(403);
    die('<p>Forbidden. Provide the correct setup key as ?key= in the URL.</p>');
}

$db   = getDB();
$hash = password_hash($NEW_PASSWORD, PASSWORD_DEFAULT);

// Check if admin already exists
$exists = $db->prepare("SELECT id FROM admins WHERE username = ? LIMIT 1");
$exists->execute([$NEW_USERNAME]);

if ($exists->fetch()) {
    // Update existing
    $db->prepare("UPDATE admins SET password_hash = ? WHERE username = ?")
       ->execute([$hash, $NEW_USERNAME]);
    echo "<p>Admin password updated for <strong>{$NEW_USERNAME}</strong>.</p>";
} else {
    // Insert new
    $db->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)")
       ->execute([$NEW_USERNAME, $hash]);
    echo "<p>Admin account created: <strong>{$NEW_USERNAME}</strong>.</p>";
}

echo "<p style='color:red'><strong>DELETE THIS FILE NOW.</strong> (api/admin/setup.php)</p>";
