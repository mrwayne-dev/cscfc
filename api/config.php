<?php
// ─── CSCFC Payment Site — Configuration ──────────────────────────────────────
// All environment-specific values live here.
// ⚠ This file contains credentials — never commit to a public repository.

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'uvammbciwx_cscfc');
define('DB_USER', 'uvammbciwx_michael');
define('DB_PASS', 'Michael@01');

// ── Paystack ──────────────────────────────────────────────────────────────────
// ⚠ Swap to LIVE keys when ready: Paystack Dashboard → Settings → API Keys
define('PAYSTACK_SECRET_KEY', 'sk_test_c732602c15653865ed644ba13178fdffc3be8efb');
define('PAYSTACK_PUBLIC_KEY', 'pk_test_9279a59547afd88cecee52072f251cbedabb04eb');

// ── App URLs (no trailing slash) ──────────────────────────────────────────────
// APP_URL  = frontend origin path (used in CORS, Paystack callback, email links)
// API_URL  = this API's own base (informational / internal use)
define('APP_URL', 'https://mrwayne-dev.github.io/cscfc');
define('API_URL', 'https://growthmining.org/api');

// ── Campaign settings ─────────────────────────────────────────────────────────
define('PLAYER_TARGET',   7000);   // per-player target in Naira
define('MIN_INSTALLMENT', 3500);   // minimum installment in Naira
define('TOTAL_PLAYERS',   25);     // expected roster size

// ── Email / SMTP (Spaceship mail) ─────────────────────────────────────────────
define('MAIL_HOST',      'mail.spacemail.com');
define('MAIL_PORT',      465);                  // 465 = SSL, 587 = STARTTLS
define('MAIL_USER',      'support@lymora.tech');
define('MAIL_PASS',      'Aleruchi@06');
define('MAIL_FROM',      'support@lymora.tech');
define('MAIL_FROM_NAME', 'CSCFC Equipment Fund');

// ── Misc ──────────────────────────────────────────────────────────────────────
define('LOGO_URL',         APP_URL . '/assets/cscfc.png');
define('SESSION_NAME',    'cscfc_admin');
define('SESSION_LIFETIME', 28800);   // 8 hours
