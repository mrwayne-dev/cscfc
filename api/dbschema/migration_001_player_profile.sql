-- ─── Migration 001 — Player profile fields ──────────────────────────────────
-- Adds `full_name` and `matric_number` to the `players` table.
-- Apply ONCE on the live MariaDB instance via phpMyAdmin BEFORE deploying the
-- new api.zip (otherwise the PHP endpoints will 500 on a missing column).
--
-- Safe to re-run: each statement is guarded with information_schema checks so
-- repeated execution is a no-op.
-- ─────────────────────────────────────────────────────────────────────────────

-- Add full_name column if absent
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'players'
    AND COLUMN_NAME  = 'full_name'
);
SET @stmt := IF(@col_exists = 0,
  'ALTER TABLE `players` ADD COLUMN `full_name` VARCHAR(150) NULL DEFAULT NULL AFTER `name`',
  'SELECT 1');
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- Add matric_number column if absent
SET @col_exists := (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'players'
    AND COLUMN_NAME  = 'matric_number'
);
SET @stmt := IF(@col_exists = 0,
  'ALTER TABLE `players` ADD COLUMN `matric_number` VARCHAR(50) NULL DEFAULT NULL AFTER `full_name`',
  'SELECT 1');
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;

-- Add unique index on matric_number if absent (multiple NULLs allowed in InnoDB)
SET @idx_exists := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'players'
    AND INDEX_NAME   = 'uq_matric_number'
);
SET @stmt := IF(@idx_exists = 0,
  'ALTER TABLE `players` ADD UNIQUE KEY `uq_matric_number` (`matric_number`)',
  'SELECT 1');
PREPARE s FROM @stmt; EXECUTE s; DEALLOCATE PREPARE s;
