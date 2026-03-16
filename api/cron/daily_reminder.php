<?php
// ─── Daily Reminder Cron Job ──────────────────────────────────────────────────
// Schedule with crontab: 0 9 * * * php /path/to/api/cron/daily_reminder.php
// Sends reminders to all players with an outstanding balance.
// ─────────────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../mail/mailer.php';

$db = getDB();

$rows = $db->query("
    SELECT
        pl.name,
        pl.email,
        pl.target_amount,
        COALESCE(SUM(CASE WHEN py.payment_status='success' THEN py.amount ELSE 0 END), 0) AS amount_paid
    FROM players pl
    LEFT JOIN payments py ON py.player_id = pl.id
    WHERE pl.email IS NOT NULL
    GROUP BY pl.id
    HAVING amount_paid < pl.target_amount
")->fetchAll();

$sent = 0; $failed = 0;

foreach ($rows as $row) {
    $paid      = (int) $row['amount_paid'];
    $remaining = (int) $row['target_amount'] - $paid;
    $ok = sendReminderEmail($row['email'], $row['name'], $paid, $remaining);
    $ok ? $sent++ : $failed++;
    echo ($ok ? '[OK]' : '[FAIL]') . ' ' . $row['name'] . ' <' . $row['email'] . ">\n";
}

echo "\nDone. Sent: {$sent}, Failed: {$failed}\n";
