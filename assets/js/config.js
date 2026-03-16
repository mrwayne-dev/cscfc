/* ─── App Configuration ─────────────────────────────────────────────────── */

const API_BASE        = 'http://cscfc-payment-site.test/api';
const PLAYER_TARGET   = 7000;
const MIN_INSTALLMENT = 3500;

// SPA base path — auto-detected by hostname.
// mrwayne-dev.github.io  →  '/cscfc/'
// local dev / custom domain  →  '/'
const BASE_PATH = window.location.hostname === 'mrwayne-dev.github.io' ? '/cscfc/' : '/';
