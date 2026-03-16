<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Paystack sends raw JSON — read it before any output
$rawBody   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

// Validate HMAC-SHA512 signature
$expected = hash_hmac('sha512', $rawBody, PAYSTACK_SECRET_KEY);
if (!hash_equals($expected, $signature)) {
    http_response_code(401);
    exit('Unauthorized');
}

$event = json_decode($rawBody, true);

// Only handle charge success
if (($event['event'] ?? '') !== 'charge.success') {
    http_response_code(200);
    exit;
}

$data      = $event['data'];
$reference = $data['reference'] ?? '';
$status    = $data['status']    ?? '';
$channel   = $data['channel']   ?? null;

if ($status !== 'success' || $reference === '') {
    http_response_code(200);
    exit;
}

$db = getDB();

// Update payment status
$upd = $db->prepare("
    UPDATE payments
    SET payment_status = 'success',
        paid_at        = NOW(),
        channel        = ?
    WHERE reference = ? AND payment_status = 'pending'
");
$upd->execute([$channel, $reference]);

if ($upd->rowCount() > 0) {
    // Fetch player info + new totals for email
    $row = $db->prepare("
        SELECT
            py.amount,
            py.reference,
            py.paid_at,
            py.email,
            pl.name  AS player_name,
            pl.target_amount,
            COALESCE(SUM(CASE WHEN py2.payment_status='success' THEN py2.amount ELSE 0 END), 0) AS total_paid
        FROM payments py
        JOIN players pl ON pl.id = py.player_id
        LEFT JOIN payments py2 ON py2.player_id = pl.id AND py2.payment_status='success'
        WHERE py.reference = ?
        GROUP BY py.id
    ");
    $row->execute([$reference]);
    $payment = $row->fetch();

    if ($payment) {
        $remaining = max(0, (int)$payment['target_amount'] - (int)$payment['total_paid']);

        // Send receipt email (fire-and-forget — don't let failure affect 200 response)
        try {
            require_once __DIR__ . '/mail/mailer.php';
            sendReceiptEmail(
                $payment['email'],
                $payment['player_name'],
                [
                    'amount'    => (int) $payment['amount'],
                    'reference' => $payment['reference'],
                    'paid_at'   => $payment['paid_at'],
                    'remaining' => $remaining,
                ]
            );
        } catch (Throwable $e) {
            error_log('Webhook email error: ' . $e->getMessage());
        }
    }
}

http_response_code(200);
echo 'OK';
