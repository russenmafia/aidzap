-- =============================================================================
-- Referral System Migration
-- Adds tables and columns for the referral program
-- =============================================================================

-- Add ref_code column to users table if it doesn't exist
SET @dbname = DATABASE();
SET @tablename = "users";
SET @columnname = "ref_code";
SET @preparedStatement = (SELECT IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE 
    (TABLE_NAME = @tablename) AND 
    (TABLE_SCHEMA = @dbname) AND 
    (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  CONCAT("ALTER TABLE ", @tablename, " ADD ", @columnname, " VARCHAR(32) DEFAULT NULL, ADD UNIQUE KEY `uq_ref_code` (`ref_code`)")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =============================================================================
-- REFERRALS
-- Referral program tracking
-- =============================================================================
CREATE TABLE IF NOT EXISTS `referrals` (
    `id`              INT UNSIGNED          NOT NULL AUTO_INCREMENT,
    `user_id`         INT UNSIGNED          NOT NULL,                -- Referrer (who gets commission)
    `referred_by`     INT UNSIGNED          NOT NULL,                -- Referred user (new signup)
    `level`           TINYINT(1)            NOT NULL,                -- 1, 2, or 3
    `ref_code`        VARCHAR(32)           NOT NULL,
    `commission`      DECIMAL(20, 8)        NOT NULL DEFAULT 0,
    `created_at`      DATETIME              NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id`       (`user_id`),
    INDEX `idx_referred_by`   (`referred_by`),
    INDEX `idx_level`         (`level`),
    INDEX `idx_ref_code`      (`ref_code`),
    CONSTRAINT `fk_referrals_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_referrals_referred`
        FOREIGN KEY (`referred_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- REFERRAL EARNINGS
-- Commission bookings for referrals
-- =============================================================================
CREATE TABLE IF NOT EXISTS `referral_earnings` (
    `id`              INT UNSIGNED          NOT NULL AUTO_INCREMENT,
    `user_id`         INT UNSIGNED          NOT NULL,                -- Referrer (who gets commission)
    `referred_by`     INT UNSIGNED          NOT NULL,                -- Referred user
    `level`           TINYINT(1)            NOT NULL,                -- 1, 2, or 3
    `type`            ENUM('earnings','spend','signup')
                                            NOT NULL,                -- Commission type
    `base_amount`     DECIMAL(20, 8)        NOT NULL,                -- Base: admin margin or ad spend
    `percentage`      DECIMAL(5, 4)         NOT NULL,                -- Commission percentage
    `commission`      DECIMAL(20, 8)        NOT NULL,                -- Calculated commission
    `reference_id`    VARCHAR(255)          DEFAULT NULL,            -- FK to the related earning/payment
    `created_at`      DATETIME              NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id`       (`user_id`),
    INDEX `idx_referred_by`   (`referred_by`),
    INDEX `idx_type`          (`type`),
    INDEX `idx_created_at`    (`created_at`),
    CONSTRAINT `fk_referral_earnings_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_referral_earnings_referred`
        FOREIGN KEY (`referred_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- REFERRAL SETTINGS
-- Referral program configuration
-- =============================================================================
CREATE TABLE IF NOT EXISTS `referral_settings` (
    `id`                INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `level1_pct`        DECIMAL(5, 2)       NOT NULL DEFAULT 5,
    `level2_pct`        DECIMAL(5, 2)       NOT NULL DEFAULT 3,
    `level3_pct`        DECIMAL(5, 2)       NOT NULL DEFAULT 1,
    `signup_bonus`      DECIMAL(20, 8)      NOT NULL DEFAULT 0,
    `on_earnings`       TINYINT(1)          NOT NULL DEFAULT 1,
    `on_spend`          TINYINT(1)          NOT NULL DEFAULT 1,
    `enabled`           TINYINT(1)          NOT NULL DEFAULT 1,
    `social_messages`   JSON                DEFAULT NULL,            -- Predefined social messages
    `updated_at`        DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default referral settings if not exists
INSERT IGNORE INTO `referral_settings` 
    (`id`, `level1_pct`, `level2_pct`, `level3_pct`, `signup_bonus`, `on_earnings`, `on_spend`, `enabled`, `social_messages`)
VALUES 
    (1, 5.00, 3.00, 1.00, 0.00000000, 1, 1, 1, JSON_ARRAY(
        JSON_OBJECT(
            'title', 'Professional Post',
            'text', 'Earn crypto with aidzap.com – privacy-first ad network 🔒\n\nPublish ads on your site or advertise with crypto. No KYC, no tracking.\n\nJoin free: {ref_link}\n\n#crypto #bitcoin #privacy'
        ),
        JSON_OBJECT(
            'title', 'Simple Share',
            'text', 'Check out aidzap.com - earn Bitcoin with ads!\n\n{ref_link}'
        ),
        JSON_OBJECT(
            'title', 'Detailed Post',
            'text', '🚀 Monetize your traffic!\n\naidzap.com is a privacy-first advertising platform where you can:\n✅ Earn BTC from ad placements\n✅ Post ads anonymously\n✅ No KYC required\n✅ Instant payouts\n\nStart earning today:\n{ref_link}'
        )
    ));
