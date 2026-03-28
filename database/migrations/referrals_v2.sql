-- =============================================================================
-- Referral System v2 Migration
-- Adds missing columns to referral_settings and users
-- Fixes column name mismatches from v1 migration
-- =============================================================================

-- Add missing columns to referral_settings
ALTER TABLE `referral_settings`
    ADD COLUMN IF NOT EXISTS `is_active`              TINYINT(1)     NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `signup_bonus_amount`     DECIMAL(20,8)  NOT NULL DEFAULT 0.00000000,
    ADD COLUMN IF NOT EXISTS `signup_bonus_active`     TINYINT(1)     NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `ai_banner_enabled`       TINYINT(1)     NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS `ai_banner_price`         DECIMAL(20,8)  NOT NULL DEFAULT 0.00000100,
    ADD COLUMN IF NOT EXISTS `impression_interval_min` INT UNSIGNED   NOT NULL DEFAULT 60,
    ADD COLUMN IF NOT EXISTS `ref_multiplier_max`      DECIMAL(6,4)   NOT NULL DEFAULT 2.0000;

-- Sync is_active from enabled column (in case enabled column has data)
UPDATE `referral_settings` SET `is_active` = `enabled` WHERE `is_active` = 1;

-- Add missing columns to users table
ALTER TABLE `users`
    ADD COLUMN IF NOT EXISTS `referred_by`    INT UNSIGNED  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `ref_multiplier` DECIMAL(6,4)  NOT NULL DEFAULT 1.0000;

-- Add index for referred_by
ALTER TABLE `users` ADD INDEX IF NOT EXISTS `idx_referred_by` (`referred_by`);

-- Ensure referral_earnings has correct columns (fix from v1 if column names differ)
ALTER TABLE `referral_earnings`
    ADD COLUMN IF NOT EXISTS `from_user_id`  INT UNSIGNED  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `pct`           DECIMAL(8,4)  NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS `currency`      VARCHAR(10)   NOT NULL DEFAULT 'BTC';

-- Update defaults if referral_settings row is missing
INSERT IGNORE INTO `referral_settings`
    (`id`, `level1_pct`, `level2_pct`, `level3_pct`, `signup_bonus`, `on_earnings`, `on_spend`,
     `enabled`, `is_active`, `signup_bonus_amount`, `ai_banner_enabled`, `ai_banner_price`,
     `impression_interval_min`)
VALUES
    (1, 5.00, 3.00, 1.00, 0.00000000, 1, 1, 1, 1, 0.00000000, 1, 0.00000100, 60);
