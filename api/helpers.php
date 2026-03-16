<?php

/**
 * Respond with a JSON success payload and exit.
 */
function json_ok(array $data): never
{
    echo json_encode(array_merge(['status' => 'success'], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Respond with a JSON error payload and exit.
 */
function json_err(string $message, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Decode the raw JSON request body or bail with 400.
 */
function get_json_body(): array
{
    $raw  = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_err('Invalid or missing JSON body.');
    }
    return $data;
}

/**
 * Abort with 405 if the request method does not match.
 */
function require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        http_response_code(405);
        echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
        exit;
    }
}

/**
 * Derive player payment status string.
 */
function player_status(int $amountPaid, int $target): string
{
    if ($amountPaid >= $target) {
        return 'fully_paid';
    }
    if ($amountPaid > 0) {
        return 'partial';
    }
    return 'unpaid';
}

/**
 * Build a reusable player-stats SQL fragment (used in multiple endpoints).
 * Returns associative array with keys: amount_paid, remaining_balance, status.
 */
function player_stats_sql(): string
{
    return "COALESCE(SUM(CASE WHEN p.payment_status = 'success' THEN p.amount ELSE 0 END), 0)";
}
