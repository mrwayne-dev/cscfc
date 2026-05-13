<?php
// ─── CSCFC Mailer ────────────────────────────────────────────────────────────

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Render an HTML email template by replacing {{key}} placeholders.
 */
function renderTemplate(string $name, array $vars): string
{
    $path = __DIR__ . '/templates/' . $name . '.html';
    if (!file_exists($path)) {
        throw new RuntimeException("Mail template not found: {$name}");
    }
    $html = file_get_contents($path);
    foreach ($vars as $key => $value) {
        $html = str_replace('{{' . $key . '}}', (string) $value, $html);
    }
    return $html;
}

/**
 * Send an HTML email via SMTP.
 */
function sendMail(string $to, string $toName, string $subject, string $html): bool
{
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = (MAIL_PORT === 465)
            ? PHPMailer::ENCRYPTION_SMTPS    // SSL on port 465
            : PHPMailer::ENCRYPTION_STARTTLS; // STARTTLS on port 587
        $mail->Port       = MAIL_PORT;

        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to, $toName);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $html;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('PHPMailer error (' . $to . '): ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send a payment receipt email.
 *
 * @param array $data ['amount'=>int, 'reference'=>string, 'paid_at'=>string, 'remaining'=>int]
 */
function sendReceiptEmail(string $to, string $playerName, array $data): bool
{
    $remaining  = (int) ($data['remaining'] ?? 0);
    $paidAt     = $data['paid_at'] ?? date('Y-m-d H:i:s');

    $remainingText = $remaining > 0
        ? 'You still have &#8358;' . number_format($remaining) . ' remaining. You can make another payment anytime.'
        : "You're fully paid up &#8212; great work! &#9917;";

    $vars = [
        'logo_url'       => LOGO_URL,
        'player_name'    => $playerName,
        'amount'         => '&#8358;' . number_format((int)$data['amount']),
        'reference'      => $data['reference'],
        'paid_at'        => date('d M Y, g:i A', strtotime($paidAt)),
        'remaining_text' => $remainingText,
        'receipt_url'    => APP_URL . '/receipt.html?reference=' . rawurlencode($data['reference']),
        'app_url'        => APP_URL,
        'year'           => date('Y'),
    ];

    $html = renderTemplate('receipt', $vars);
    return sendMail($to, $playerName, 'Payment Received &#8212; CSCFC Equipment Fund', $html);
}

/**
 * Send a profile-saved confirmation email.
 * Triggered whenever a player's full_name/matric_number/email is recorded
 * (via the Register page, the first payment, or an admin add/edit).
 *
 * @param string $to            recipient email
 * @param string $displayName   short roster name (for greeting)
 * @param string $fullName      legal full name
 * @param string $matricNumber  matric number
 * @param bool   $hasOutstanding true if the player still owes part of the target
 */
function sendProfileSavedEmail(
    string $to,
    string $displayName,
    string $fullName,
    string $matricNumber,
    bool $hasOutstanding = true
): bool {
    $vars = [
        'logo_url'        => LOGO_URL,
        'player_name'     => $displayName,
        'full_name'       => htmlspecialchars($fullName,     ENT_QUOTES, 'UTF-8'),
        'matric_number'   => htmlspecialchars($matricNumber, ENT_QUOTES, 'UTF-8'),
        'email'           => htmlspecialchars($to,           ENT_QUOTES, 'UTF-8'),
        'cta_heading'     => $hasOutstanding ? 'Ready to contribute?' : "You're fully paid up",
        'cta_body'        => $hasOutstanding
            ? 'Head over to the payment portal whenever you&rsquo;re ready &mdash; every contribution brings the squad closer to the full kit.'
            : 'You&rsquo;ve already cleared your share &mdash; thanks for backing the team. You can still visit the leaderboard to see how everyone else is doing.',
        'cta_button_text' => $hasOutstanding ? 'Make a Payment' : 'View Leaderboard',
        'payment_url'     => $hasOutstanding ? APP_URL . '/pay'         : APP_URL . '/leaderboard',
        'app_url'         => APP_URL,
        'year'            => date('Y'),
    ];

    $html = renderTemplate('profile_saved', $vars);
    return sendMail($to, $displayName, 'Your details are saved &#8212; CSCFC Equipment Fund', $html);
}

/**
 * Send a payment reminder email.
 */
function sendReminderEmail(string $to, string $playerName, int $amountPaid, int $remaining): bool
{
    $vars = [
        'logo_url'          => LOGO_URL,
        'player_name'       => $playerName,
        'amount_paid'       => '&#8358;' . number_format($amountPaid),
        'remaining_balance' => '&#8358;' . number_format($remaining),
        'payment_url'       => APP_URL,
        'app_url'           => APP_URL,
        'year'              => date('Y'),
    ];

    $html = renderTemplate('reminder', $vars);
    return sendMail($to, $playerName, 'Payment Reminder &#8212; CSCFC Equipment Fund', $html);
}
