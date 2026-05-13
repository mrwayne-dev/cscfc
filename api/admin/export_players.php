<?php
/**
 * Admin-only CSV export of the player roster.
 *
 * Streams text/csv with a timestamped filename. Reuses the same paid-total
 * aggregation as get_players.php so balances and statuses match the admin UI.
 */

require_once __DIR__ . '/../cors.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../helpers.php';

require_method('GET');

session_name(SESSION_NAME);
session_start();
if (empty($_SESSION['admin_id'])) json_err('Unauthorized.', 401);

$db = getDB();

$rows = $db->query("
    SELECT
        pl.id,
        pl.name,
        pl.full_name,
        pl.matric_number,
        pl.email,
        pl.target_amount,
        pl.created_at,
        COALESCE(SUM(CASE WHEN py.payment_status = 'success' THEN py.amount ELSE 0 END), 0) AS amount_paid
    FROM players pl
    LEFT JOIN payments py ON py.player_id = pl.id
    GROUP BY pl.id
    ORDER BY pl.name ASC
")->fetchAll();

// Override the JSON Content-Type set by cors.php
header_remove('Content-Type');
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="cscfc_players_' . date('Ymd_His') . '.csv"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');

// BOM so Excel opens UTF-8 correctly
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [
    '#',
    'Display Name',
    'Full Name',
    'Matric Number',
    'Email',
    'Amount Paid (NGN)',
    'Balance (NGN)',
    'Status',
    'Created At',
]);

$i = 0;
foreach ($rows as $row) {
    $i++;
    $paid      = (int) $row['amount_paid'];
    $target    = (int) $row['target_amount'];
    $remaining = max(0, $target - $paid);

    fputcsv($out, [
        $i,
        $row['name'],
        $row['full_name']     ?? '',
        $row['matric_number'] ?? '',
        $row['email']         ?? '',
        $paid,
        $remaining,
        player_status($paid, $target),
        $row['created_at'],
    ]);
}

fclose($out);
exit;
