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

$db = getDB();

// Players with an outstanding balance AND an email — these get reminders
$rows = $db->query("
    SELECT
        pl.id,
        pl.name,
        pl.email,
        pl.target_amount,
        COALESCE(SUM(CASE WHEN py.payment_status='success' THEN py.amount ELSE 0 END), 0) AS amount_paid
    FROM players pl
    LEFT JOIN payments py ON py.player_id = pl.id
    WHERE pl.email IS NOT NULL AND pl.email <> ''
    GROUP BY pl.id
    HAVING amount_paid < pl.target_amount
")->fetchAll();

// Players with an outstanding balance AND no email — silently skipped today,
// surfaced here so the admin UI can warn before sending
$skippedRow = $db->query("
    SELECT COUNT(*) AS c FROM (
        SELECT pl.id
          FROM players pl
          LEFT JOIN payments py ON py.player_id = pl.id
         WHERE pl.email IS NULL OR pl.email = ''
         GROUP BY pl.id
        HAVING COALESCE(SUM(CASE WHEN py.payment_status='success' THEN py.amount ELSE 0 END), 0) < pl.target_amount
    ) AS t
")->fetch();
$skipped = (int) ($skippedRow['c'] ?? 0);

$sent   = 0;
$failed = 0;

foreach ($rows as $row) {
    $paid      = (int) $row['amount_paid'];
    $remaining = (int) $row['target_amount'] - $paid;
    $ok = sendReminderEmail($row['email'], $row['name'], $paid, $remaining);
    $ok ? $sent++ : $failed++;
}

$message = "Reminders sent to {$sent} player(s).";
if ($failed  > 0) $message .= " {$failed} failed.";
if ($skipped > 0) $message .= " {$skipped} skipped (no email on file).";

json_ok([
    'message' => $message,
    'sent'    => $sent,
    'failed'  => $failed,
    'skipped' => $skipped,
]);
