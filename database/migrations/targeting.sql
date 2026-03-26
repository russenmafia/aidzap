-- Targeting migration
-- Run once. Skips columns that already exist in the schema.

-- 1. Add device targeting column to campaigns (countries + languages already exist)
ALTER TABLE campaigns
    ADD COLUMN target_devices JSON NULL COMMENT 'e.g. ["desktop","mobile","tablet"] or null = all';

-- 2. Add language + device to impressions for reporting (country already exists)
ALTER TABLE impressions
    ADD COLUMN language VARCHAR(10) NULL AFTER country,
    ADD COLUMN device   VARCHAR(10) NULL AFTER language;

-- 3. Admin feature flags table
CREATE TABLE IF NOT EXISTS feature_flags (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    flag_key   VARCHAR(50) NOT NULL UNIQUE,
    is_active  TINYINT(1)  NOT NULL DEFAULT 0,
    updated_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO feature_flags (flag_key, is_active) VALUES
    ('targeting_geo',      0),
    ('targeting_language', 0),
    ('targeting_device',   0)
ON DUPLICATE KEY UPDATE flag_key = flag_key;
