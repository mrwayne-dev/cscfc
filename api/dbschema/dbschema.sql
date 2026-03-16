-- ─── CSCFC Payment Site — Database Schema ───────────────────────────────────
-- Compatible with MySQL 5.7+ / phpMyAdmin
-- No MariaDB-specific syntax used
-- Run this once to set up the database tables
-- ─────────────────────────────────────────────────────────────────────────────

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Players
CREATE TABLE IF NOT EXISTS `players` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(100)  NOT NULL,
  `email`          VARCHAR(255)  NULL DEFAULT NULL,  -- populated on first payment
  `target_amount`  INT UNSIGNED  NOT NULL DEFAULT 7000,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment transactions
CREATE TABLE IF NOT EXISTS `payments` (
  `id`              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `player_id`       INT UNSIGNED  NOT NULL,
  `email`           VARCHAR(255)  NOT NULL,
  `amount`          INT UNSIGNED  NOT NULL,
  `reference`       VARCHAR(100)  NOT NULL,
  `payment_type`    ENUM("full","installment") NOT NULL DEFAULT "installment",
  `payment_status`  ENUM("pending","success","failed") NOT NULL DEFAULT "pending",
  `paid_at`         TIMESTAMP     NULL DEFAULT NULL,
  `channel`         VARCHAR(50)   NULL DEFAULT NULL,
  `created_at`      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reference` (`reference`),
  KEY `idx_player_id` (`player_id`),
  KEY `idx_status`    (`payment_status`),
  CONSTRAINT `fk_payment_player`
    FOREIGN KEY (`player_id`) REFERENCES `players` (`id`)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admin accounts
CREATE TABLE IF NOT EXISTS `admins` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(50)   NOT NULL,
  `password_hash` VARCHAR(255)  NOT NULL,
  `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Player seed data ────────────────────────────────────────────────────────
-- 25 registered players. Update the email column to each player's real address.
-- INSERT IGNORE is used so re-running the schema does not duplicate rows.
INSERT IGNORE INTO `players` (`name`, `target_amount`) VALUES
  ('Michael',    7000),
  ('Gabi',       7000),
  ('Rex',        7000),
  ('Diepiriye',  7000),
  ('Goodluck',   7000),
  ('Bobby',      7000),
  ('Kennedy',    7000),
  ('Owen',       7000),
  ('Bellingham', 7000),
  ('Champion',   7000),
  ('Destiny',    7000),
  ('Etienne',    7000),
  ('Fortune',    7000),
  ('Franklin',   7000),
  ('Genesis',    7000),
  ('Ibiso',      7000),
  ('Jobi',       7000),
  ('Mill',       7000),
  ('Miller',     7000),
  ('Raymond',    7000),
  ('Reuben',     7000),
  ('Royal',      7000),
  ('Samuel',     7000),
  ('Sultan',     7000),
  ('Winter',     7000);

-- Notes:
-- 1. Do NOT insert admin here. Use api/admin/setup.php to create the admin
--    account with a proper bcrypt password hash.
-- 2. Player status (fully_paid/partial/unpaid) is computed in PHP from
--    the payments table sum. It is not stored in the DB.
-- 3. Amounts are stored in Naira. The API multiplies x100 for Paystack (kobo).
-- 4. Player emails are NULL until a player makes their first payment.
--    The API captures their email on payment and writes it back to players.email.

SET FOREIGN_KEY_CHECKS = 1;
