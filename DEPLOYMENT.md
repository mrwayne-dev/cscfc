# CSCFC Payment Site — Deployment Guide

## Architecture overview

```
┌─────────────────────────────┐      ┌──────────────────────────────┐
│   GitHub Pages (Frontend)   │      │   Spaceship Server (Backend) │
│                             │      │                              │
│  index.html  (SPA shell)    │      │  api/  (PHP endpoints)       │
│  pages/      (fragments)    │ ───▶ │  MySQL database              │
│  assets/     (CSS/JS/img)   │      │  PHPMailer (email)           │
│  admin/      (admin panel)  │      │  Paystack webhooks           │
│  404.html    (SPA redirect) │      │                              │
└─────────────────────────────┘      └──────────────────────────────┘
```

The frontend is a static SPA served by GitHub Pages.
The backend is a PHP/MySQL API hosted on your Spaceship server.
They communicate via `fetch()` calls across domains (CORS is pre-configured).

---

## Before you start — checklist

- [ ] GitHub account with a repo created for this project
- [ ] Spaceship hosting account with PHP 8.0+ and MySQL
- [ ] Paystack account with **live** API keys (Settings → API Keys)
- [ ] A domain name (or you can use the GitHub Pages subdomain + Spaceship subdomain)
- [ ] FTP/SFTP credentials for your Spaceship server, or access to cPanel File Manager

---

---

## PART A — Backend on Spaceship

### A1. Add `api/config.php` to `.gitignore`

Your config file contains database passwords and API keys — it must never be
committed to a public GitHub repo.

Create or open `.gitignore` in the project root and add:

```
api/config.php
api/admin/setup.php
```

Then copy the current `api/config.php` somewhere safe (you'll re-upload it
manually to the server later).

---

### A2. Decide on your API URL

Your PHP files need to be publicly reachable. Two common options:

**Option A — Subdomain** *(recommended — cleanest)*
> Point `api.yourdomain.com` at the Spaceship server and upload `api/` to its
> document root (`public_html` for that subdomain).
> Your API base URL will be: `https://api.yourdomain.com`

**Option B — Subdirectory**
> Upload `api/` into the `public_html` of your main domain.
> Your API base URL will be: `https://yourdomain.com/api`

Pick one and note the URL — you'll use it in A5 and B4.

---

### A3. Upload the `api/` folder

Using **cPanel File Manager** or an FTP client (FileZilla, Cyberduck, etc.):

1. Connect to your Spaceship server.
2. Navigate to `public_html/` (or the subdomain's document root if using Option A).
3. Upload the entire `api/` folder.

Your server tree should look like:

```
public_html/
└── api/
    ├── config.php          ← you will edit this next
    ├── cors.php
    ├── db.php
    ├── helpers.php
    ├── get_players.php
    ├── get_summary.php
    ├── get_user_balance.php
    ├── get_payment.php
    ├── initiate-payment.php
    ├── verify_payment.php
    ├── webhook.php
    ├── admin/
    ├── mail/
    ├── dbschema/
    ├── cron/
    └── vendor/             ← Composer packages (PHPMailer)
```

> **Note:** Upload the `vendor/` folder too — it contains PHPMailer.
> If `vendor/` is large and slow to upload, run `composer install` via SSH instead:
> ```bash
> cd public_html/api
> composer install --no-dev
> ```

---

### A4. Create the MySQL database

1. Log into **cPanel** → **MySQL Databases**.
2. Create a new database, e.g. `cscfc_db`.
3. Create a new database user, e.g. `cscfc_user`, with a strong password.
4. Add the user to the database — grant **All Privileges**.
5. Note the host (usually `localhost`), database name, username, and password.

---

### A5. Import the database schema

1. In cPanel → **phpMyAdmin**, select your new database.
2. Click the **Import** tab.
3. Choose the file `api/dbschema/dbschema.sql` from your local machine.
4. Click **Go**.

You should see three tables created: `players`, `payments`, `admins`.

---

### A6. Configure `api/config.php` for production

Open `api/config.php` on the server (via cPanel File Manager → Edit,
or upload a locally edited copy) and update every value:

```php
// ── Database ──────────────────────────────────────────────────────────────
define('DB_HOST', 'localhost');           // usually 'localhost' on Spaceship
define('DB_NAME', 'cscfc_db');           // database name from A4
define('DB_USER', 'cscfc_user');         // database user from A4
define('DB_PASS', 'your_db_password');   // database password from A4

// ── Paystack ──────────────────────────────────────────────────────────────
// ⚠ Use LIVE keys for production — not test keys
define('PAYSTACK_SECRET_KEY', 'sk_live_xxxxxxxxxxxxxxxxxxxx');
define('PAYSTACK_PUBLIC_KEY', 'pk_live_xxxxxxxxxxxxxxxxxxxx');

// ── App URLs (no trailing slash) ──────────────────────────────────────────
define('APP_URL', 'https://yourdomain.com');        // your GitHub Pages URL
define('API_URL', 'https://api.yourdomain.com');    // your API URL from A2

// ── Campaign settings ─────────────────────────────────────────────────────
define('PLAYER_TARGET',   7000);
define('MIN_INSTALLMENT', 3500);
define('TOTAL_PLAYERS',   25);

// ── Email / SMTP (Spaceship mail) ─────────────────────────────────────────
define('MAIL_HOST',      'mail.spacemail.com');
define('MAIL_PORT',      465);
define('MAIL_USER',      'your@yourdomain.com');     // your Spaceship email
define('MAIL_PASS',      'your_email_password');
define('MAIL_FROM',      'your@yourdomain.com');
define('MAIL_FROM_NAME', 'CSCFC Equipment Fund');

// ── Misc ──────────────────────────────────────────────────────────────────
define('LOGO_URL',        APP_URL . '/assets/cscfc.png');
define('SESSION_NAME',    'cscfc_admin');
define('SESSION_LIFETIME', 28800);
```

---

### A7. Remove the local-dev SSL bypass

On your local machine, open `api/initiate-payment.php`.

Find and **remove** these two lines before uploading (or edit them on the server):

```php
// Local dev only — remove before deploying to production
CURLOPT_SSL_VERIFYPEER => false,
CURLOPT_SSL_VERIFYHOST => false,
```

The Spaceship server has valid SSL certificates — Paystack's HTTPS will work
without bypassing verification.

Do the same in `api/verify_payment.php` if those lines exist there.

---

### A8. Create the admin account (one-time setup)

1. Open your browser and visit:
   ```
   https://api.yourdomain.com/admin/setup.php?key=YOUR_SETUP_KEY
   ```
   Replace `YOUR_SETUP_KEY` with the key you set in `setup.php`.

2. You should see a success message: *"Admin account created successfully."*

3. **Immediately delete `setup.php`** from the server — it's a security risk if
   left accessible.
   In cPanel File Manager: navigate to `api/admin/` → right-click `setup.php` → Delete.

4. Test login at: `https://yourdomain.com/admin/login.html`

---

### A9. Register the Paystack webhook

Paystack needs to notify your server when a payment is confirmed.

1. Log into your [Paystack Dashboard](https://dashboard.paystack.com).
2. Go to **Settings → API Keys & Webhooks**.
3. Under **Webhook URL**, enter:
   ```
   https://api.yourdomain.com/webhook.php
   ```
4. Save. Paystack will now POST to this URL on every `charge.success` event.

---

### A10. Set up the daily reminder cron job

This sends reminder emails to players with outstanding balances.

1. In cPanel → **Cron Jobs**.
2. Set the schedule to **Once a day** (e.g. 9:00 AM):
   ```
   0 9 * * *
   ```
3. Set the command to:
   ```bash
   php /home/yourusername/public_html/api/cron/daily_reminder.php
   ```
   > Replace `/home/yourusername/` with your actual home directory path
   > (visible at the top of cPanel File Manager).

4. Click **Add New Cron Job**.

---

---

## PART B — Frontend on GitHub Pages

### B1. Initialise the Git repository

In your project root, open a terminal:

```bash
git init
git add .
git commit -m "Initial commit — CSCFC payment site"
```

> Make sure `api/config.php` and `api/admin/setup.php` are listed in `.gitignore`
> before running `git add .` (see A1).

---

### B2. Create a GitHub repository and push

1. Go to [github.com](https://github.com) → **New repository**.
2. Name it — e.g. `cscfc-payment-site` (or just `cscfc` for a cleaner URL).
3. Set it to **Public** (required for free GitHub Pages).
4. Do **not** initialise with a README — you already have files.
5. Copy the remote URL shown, then in your terminal:

```bash
git remote add origin https://github.com/yourusername/cscfc-payment-site.git
git branch -M main
git push -u origin main
```

---

### B3. Enable GitHub Pages

1. On GitHub, open your repository → **Settings** → **Pages** (left sidebar).
2. Under **Source**, select:
   - Branch: `main`
   - Folder: `/ (root)`
3. Click **Save**.

GitHub will give you a URL like:
```
https://yourusername.github.io/cscfc-payment-site/
```

---

### B4. Update `assets/js/config.js` with production values

Open `assets/js/config.js` and update:

```js
// Point to your live Spaceship API
const API_BASE = 'https://api.yourdomain.com';

// If using a GitHub Project site (not a custom domain), set the repo name:
const BASE_PATH = '/cscfc-payment-site/';  // must match your repo name exactly
```

Also open `404.html` and change:
```js
var SEGMENT_COUNT = 1;  // 1 for project sites (username.github.io/repo-name)
```

Commit and push:
```bash
git add assets/js/config.js 404.html
git commit -m "Configure production API URL and GitHub Pages base path"
git push
```

---

### B5. (Optional) Set up a custom domain

A custom domain (`yourdomain.com`) removes the `/repo-name/` path prefix and
gives you clean URLs like `yourdomain.com/leaderboard`.

**In Spaceship DNS:**

Add a CNAME record:
| Type | Name | Value |
|------|------|-------|
| CNAME | `www` | `yourusername.github.io` |

Or for an apex domain, add these A records:
| Type | Name | Value |
|------|------|-------|
| A | `@` | `185.199.108.153` |
| A | `@` | `185.199.109.153` |
| A | `@` | `185.199.110.153` |
| A | `@` | `185.199.111.153` |

**In GitHub Pages settings:**

1. Repo → Settings → Pages → **Custom domain** → enter `yourdomain.com`
2. Check **Enforce HTTPS** (wait a few minutes for the certificate to provision).

**After setting a custom domain**, update `config.js` back to:
```js
const BASE_PATH = '/';   // root — no repo prefix needed
```

And in `404.html`:
```js
var SEGMENT_COUNT = 0;   // 0 for custom domain or user/org site
```

---

---

## PART C — Final verification

Work through these checks after both deployments are live.

### Checklist

- [ ] **Homepage loads** — visit `https://yourdomain.com/`
      Player dropdown is populated, progress bar shows data

- [ ] **Direct URL visit works** — visit `https://yourdomain.com/leaderboard` and
      press Refresh — should not break (tests the 404 redirect)

- [ ] **SPA navigation** — click "View Leaderboard" from the home page,
      then click "Make a Payment" to go back — no full page reload

- [ ] **Payment form** — select a player, enter email, choose amount, click
      "Proceed to Pay" — should redirect to Paystack checkout

- [ ] **Receipt page** — after a test payment on Paystack, confirm
      `yourdomain.com/receipt?reference=xxx` shows payment details

- [ ] **Admin login** — visit `https://yourdomain.com/admin/login.html`,
      log in with the credentials set in setup.php

- [ ] **Admin dashboard** — stat cards load, players list populates,
      payments tab shows transaction history

- [ ] **Send reminders** — click the "Send Reminders" button in the admin panel,
      confirm the success message and check that emails arrive

- [ ] **Webhook** — make a Paystack test payment, then check the admin Payments
      tab to confirm `payment_status` updated to `success`

---

## Updating the site after launch

### Frontend changes (GitHub Pages)

```bash
# Make your changes locally, then:
git add .
git commit -m "describe what changed"
git push
```

GitHub Pages redeploys automatically within ~60 seconds.

### Backend changes (Spaceship)

Edit files directly in cPanel File Manager, or upload changed files via FTP.
`api/config.php` is never in Git — always edit it directly on the server.

---

## Security reminders

| Item | Status |
|------|--------|
| `api/config.php` in `.gitignore` | Must be done before first push |
| `api/admin/setup.php` deleted from server | Must be done after A8 |
| Paystack live keys in `config.php` | Live keys only on server, never in Git |
| SSL bypass removed from `initiate-payment.php` | Must be done before upload |
| Admin password changed from `admin123` | Must be done via setup.php |
| HTTPS enforced on GitHub Pages | Enable in Pages settings |

---

## Troubleshooting

**"Could not load players" on the payment form**
→ Check that `API_BASE` in `config.js` points to the correct Spaceship URL.
→ Check `APP_URL` in `api/config.php` matches your GitHub Pages domain exactly — CORS rejects mismatches.

**Refresh breaks on `/leaderboard` or `/receipt`**
→ Confirm `404.html` is in the repo root (not inside a subfolder).
→ Check `SEGMENT_COUNT` in `404.html` matches your deployment type (0 = custom domain, 1 = project site).
→ Check `BASE_PATH` in `config.js` matches your repo name (or is `/` for custom domain).

**Paystack redirects back but receipt shows "Payment Not Found"**
→ The webhook may not have fired yet. `verify_payment.php` polls Paystack directly as a fallback — this usually self-resolves within a few seconds.
→ Check that the webhook URL in Paystack dashboard matches `https://api.yourdomain.com/webhook.php` exactly.

**Admin panel shows "Unauthorized" immediately**
→ PHP sessions require the same domain for cookies. If the admin panel is on GitHub Pages and the API is on Spaceship, make sure `SESSION_NAME` is set and `cors.php` sends `Access-Control-Allow-Credentials: true` (it does by default).
→ Try clearing cookies and logging in again.

**Emails not sending**
→ Verify SMTP credentials in `config.php` (`MAIL_HOST`, `MAIL_USER`, `MAIL_PASS`).
→ In cPanel → **Email Accounts**, confirm the sending address exists.
→ Port 465 with SSL is correct for Spaceship's `mail.spacemail.com`.
