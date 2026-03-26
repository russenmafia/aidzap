-- Quality score + dynamic referral multiplier system
-- Run once after targeting.sql

-- ── 1. Ad Units: quality columns ─────────────────────────────────────────────
ALTER TABLE ad_units
    ADD COLUMN quality_level                    ENUM('bronze','silver','gold','platinum')
                                                NOT NULL DEFAULT 'bronze',
    ADD COLUMN revenue_share                    DECIMAL(4,2) NOT NULL DEFAULT 60.00
                                                COMMENT 'Effective publisher share %',
    ADD COLUMN quality_updated_at               TIMESTAMP NULL,
    ADD COLUMN quality_downgrade_pending_since  TIMESTAMP NULL,
    ADD COLUMN first_active_at                  TIMESTAMP NULL;

-- ── 2. Quality change audit trail ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS quality_history (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    unit_id     INT UNSIGNED    NOT NULL,
    old_level   VARCHAR(20)     NOT NULL DEFAULT '',
    new_level   VARCHAR(20)     NOT NULL DEFAULT '',
    old_share   DECIMAL(4,2)    NOT NULL DEFAULT 0.00,
    new_share   DECIMAL(4,2)    NOT NULL DEFAULT 0.00,
    ctr_30d     DECIMAL(8,6)    NOT NULL DEFAULT 0.000000,
    fraud_score DECIMAL(4,3)    NOT NULL DEFAULT 0.000,
    reason      VARCHAR(100)    NOT NULL DEFAULT '',
    changed_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_unit    (unit_id),
    INDEX idx_changed (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Admin-configurable quality settings (single-row config) ───────────────
CREATE TABLE IF NOT EXISTS quality_settings (
    id                  INT             AUTO_INCREMENT PRIMARY KEY,
    -- CTR thresholds (e.g. 0.0010 = 0.10%)
    bronze_max_ctr      DECIMAL(6,4)    NOT NULL DEFAULT 0.0010
                        COMMENT 'CTR floor to leave bronze',
    silver_max_ctr      DECIMAL(6,4)    NOT NULL DEFAULT 0.0030
                        COMMENT 'CTR floor for gold',
    gold_max_ctr        DECIMAL(6,4)    NOT NULL DEFAULT 0.0080
                        COMMENT 'CTR floor for platinum',
    -- Revenue shares per level (%)
    bronze_share        DECIMAL(4,2)    NOT NULL DEFAULT 60.00,
    silver_share        DECIMAL(4,2)    NOT NULL DEFAULT 70.00,
    gold_share          DECIMAL(4,2)    NOT NULL DEFAULT 80.00,
    platinum_share      DECIMAL(4,2)    NOT NULL DEFAULT 85.00,
    -- Referral multipliers (applied to configured level pcts)
    ref_multiplier_0    DECIMAL(4,2)    NOT NULL DEFAULT 0.00
                        COMMENT '0 active quality refs',
    ref_multiplier_1    DECIMAL(4,2)    NOT NULL DEFAULT 0.50
                        COMMENT '1 active quality ref',
    ref_multiplier_2    DECIMAL(4,2)    NOT NULL DEFAULT 1.00
                        COMMENT '2 active quality refs',
    ref_multiplier_3plus DECIMAL(4,2)  NOT NULL DEFAULT 1.50
                        COMMENT '3+ active quality refs',
    -- Minimum own quality level to earn referral income
    min_own_level       ENUM('bronze','silver','gold','platinum')
                        NOT NULL DEFAULT 'silver',
    -- Concentration cap: max % of referral earnings from single user (last 30d)
    concentration_cap_pct INT UNSIGNED  NOT NULL DEFAULT 50,
    -- Days before a downgrade takes effect (cooling period)
    cooling_period_days INT UNSIGNED    NOT NULL DEFAULT 14,
    -- Days a unit must have been active before upgrades are evaluated
    activity_window_days INT UNSIGNED   NOT NULL DEFAULT 30,
    -- Max average fraud score to allow upgrade
    max_fraud_score     DECIMAL(4,3)    NOT NULL DEFAULT 0.750,
    updated_at          TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the single settings row (idempotent)
INSERT INTO quality_settings (id) VALUES (1)
ON DUPLICATE KEY UPDATE id = id;

-- ── 4. Users: referral multiplier cache ──────────────────────────────────────
ALTER TABLE users
    ADD COLUMN ref_active_count         INT          NOT NULL DEFAULT 0
                                        COMMENT 'Silver+ active direct referrals',
    ADD COLUMN ref_multiplier           DECIMAL(4,2) NOT NULL DEFAULT 0.00
                                        COMMENT 'Current referral commission multiplier',
    ADD COLUMN ref_multiplier_updated_at TIMESTAMP   NULL;
