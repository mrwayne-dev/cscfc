<?php
// ─── CSCFC Payment Site — Configuration ──────────────────────────────────────
// All environment-specific values live here.
// Fill in every placeholder before deploying.

// ── Database ──────────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');       // your database host
define('DB_NAME', 'cscfc_db');        // your database name
define('DB_USER', 'root');            // your database username
define('DB_PASS', '');                // your database password

// ── Paystack ──────────────────────────────────────────────────────────────────
define('PAYSTACK_SECRET_KEY', 'sk_test_c732602c15653865ed644ba13178fdffc3be8efb');   // Paystack dashboard → Settings → API Keys
define('PAYSTACK_PUBLIC_KEY', 'pk_test_9279a59547afd88cecee52072f251cbedabb04eb');   // Paystack dashboard → Settings → API Keys

// ── App URLs (no trailing slash) ──────────────────────────────────────────────
define('APP_URL', 'http://cscfc-payment-site.test');
define('API_URL', 'http://cscfc-payment-site.test/api');

// ── Campaign settings ─────────────────────────────────────────────────────────
define('PLAYER_TARGET',   7000);   // per-player target in Naira
define('MIN_INSTALLMENT', 3500);   // minimum installment in Naira
define('TOTAL_PLAYERS',   25);     // expected roster size

// ── Email / SMTP ──────────────────────────────────────────────────────────────
define('MAIL_HOST',      'mail.spacemail.com');        // SMTP server
define('MAIL_PORT',      465);                     // 587=STARTTLS, 465=SSL
define('MAIL_USER',      'support@lymora.tech');        // SMTP username
define('MAIL_PASS',      'Aleruchi@06');     // SMTP password / app password
define('MAIL_FROM',      'support@lymora.tech');        // From address
define('MAIL_FROM_NAME', 'CSCFC Equipment Fund');  // From name

// ── Misc ──────────────────────────────────────────────────────────────────────
define('LOGO_URL',        APP_URL . '/assets/cscfc.png');
define('SESSION_NAME',    'cscfc_admin');
define('SESSION_LIFETIME', 28800);   // 8 hours
