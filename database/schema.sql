-- =============================================================================
-- aidzap.com – Datenbankschema
-- MySQL 8.x / utf8mb4
-- =============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- USERS
-- Anonym: kein Name, keine Adresse, kein KYC
-- Identifikation nur über zufälligen username + gehashtes Passwort
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`            CHAR(36)        NOT NULL UNIQUE,               -- öffentliche ID (URL-safe)
    `username`        VARCHAR(32)     NOT NULL UNIQUE,               -- zufällig generiert oder gewählt
    `email`           VARCHAR(180)    DEFAULT NULL,                  -- optional, verschlüsselt gespeichert
    `password_hash`   VARCHAR(255)    NOT NULL,
    `role`            ENUM('advertiser','publisher','both','admin')
                                      NOT NULL DEFAULT 'both',
    `status`          ENUM('active','suspended','banned')
                                      NOT NULL DEFAULT 'active',
    `api_token`       CHAR(64)        DEFAULT NULL UNIQUE,           -- für API-Zugriff
    `ref_code`        VARCHAR(32)     DEFAULT NULL UNIQUE,           -- Referral code
    `timezone`        VARCHAR(64)     NOT NULL DEFAULT 'UTC',
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                      ON UPDATE CURRENT_TIMESTAMP,
    `last_login_at`   DATETIME        DEFAULT NULL,
    `last_login_ip`   VARBINARY(16)   DEFAULT NULL,                  -- binär gespeichert (IPv4+IPv6)
    PRIMARY KEY (`id`),
    INDEX `idx_uuid`        (`uuid`),
    INDEX `idx_status`      (`status`),
    INDEX `idx_role`        (`role`),
    INDEX `idx_ref_code`    (`ref_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- USER SESSIONS
-- Server-seitige Sessions (sicherer als nur Cookie)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user_sessions` (
    `id`          CHAR(64)        NOT NULL,                          -- Session-Token
    `user_id`     INT UNSIGNED    NOT NULL,
    `ip`          VARBINARY(16)   DEFAULT NULL,
    `user_agent`  VARCHAR(255)    DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at`  DATETIME        NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    CONSTRAINT `fk_sessions_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- CRYPTO WALLETS
-- Publisher & Advertiser hinterlegen Auszahlungsadressen
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `crypto_wallets` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED    NOT NULL,
    `currency`    VARCHAR(10)     NOT NULL,                          -- BTC, ETH, LTC, USDT ...
    `address`     VARCHAR(255)    NOT NULL,
    `label`       VARCHAR(64)     DEFAULT NULL,
    `is_default`  TINYINT(1)      NOT NULL DEFAULT 0,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_currency_address` (`user_id`, `currency`, `address`),
    CONSTRAINT `fk_wallets_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- ADVERTISER BALANCES
-- Guthaben pro User & Währung
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `balances` (
    `id`          INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED        NOT NULL,
    `currency`    VARCHAR(10)         NOT NULL,
    `amount`      DECIMAL(20, 8)      NOT NULL DEFAULT 0.00000000,
    `updated_at`  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                      ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_currency` (`user_id`, `currency`),
    CONSTRAINT `fk_balances_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- PAYMENTS
-- Einzahlungen (Advertiser) & Auszahlungen (Publisher)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payments` (
    `id`              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `uuid`            CHAR(36)            NOT NULL UNIQUE,
    `user_id`         INT UNSIGNED        NOT NULL,
    `type`            ENUM('deposit','withdrawal','refund','earning')
                                          NOT NULL,
    `currency`        VARCHAR(10)         NOT NULL,
    `amount`          DECIMAL(20, 8)      NOT NULL,
    `fee`             DECIMAL(20, 8)      NOT NULL DEFAULT 0.00000000,
    `status`          ENUM('pending','confirming','completed','failed','cancelled')
                                          NOT NULL DEFAULT 'pending',
    `provider`        VARCHAR(32)         DEFAULT NULL,              -- nowpayments, manual ...
    `provider_ref`    VARCHAR(255)        DEFAULT NULL,              -- externe TX-ID
    `tx_hash`         VARCHAR(255)        DEFAULT NULL,              -- Blockchain TX
    `wallet_address`  VARCHAR(255)        DEFAULT NULL,
    `confirmations`   SMALLINT UNSIGNED   NOT NULL DEFAULT 0,
    `meta`            JSON                DEFAULT NULL,              -- provider-spezifische Daten
    `created_at`      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id`   (`user_id`),
    INDEX `idx_status`    (`status`),
    INDEX `idx_type`      (`type`),
    CONSTRAINT `fk_payments_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- AD CATEGORIES
-- Für Targeting: Crypto, Finance, Gaming, Tech ...
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ad_categories` (
    `id`          SMALLINT UNSIGNED   NOT NULL AUTO_INCREMENT,
    `slug`        VARCHAR(64)         NOT NULL UNIQUE,
    `name`        VARCHAR(128)        NOT NULL,
    `description` TEXT                DEFAULT NULL,
    `is_active`   TINYINT(1)          NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `ad_categories` (`slug`, `name`) VALUES
    ('crypto',       'Cryptocurrency'),
    ('defi',         'DeFi / Web3'),
    ('nft',          'NFT & Collectibles'),
    ('gambling',     'Gambling / Betting'),
    ('finance',      'Finance & Trading'),
    ('gaming',       'Gaming'),
    ('tech',         'Technology'),
    ('vpn-privacy',  'VPN & Privacy'),
    ('adult',        'Adult (18+)'),
    ('other',        'Other');

-- -----------------------------------------------------------------------------
-- CAMPAIGNS (Advertiser)
-- Eine Kampagne = Budget + Zeitraum + Targeting-Einstellungen
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `campaigns` (
    `id`              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `uuid`            CHAR(36)            NOT NULL UNIQUE,
    `user_id`         INT UNSIGNED        NOT NULL,
    `name`            VARCHAR(255)        NOT NULL,
    `status`          ENUM('draft','pending_review','active','paused','completed','rejected')
                                          NOT NULL DEFAULT 'draft',
    `pricing_model`   ENUM('cpd','cpm','cpa')
                                          NOT NULL DEFAULT 'cpd',
    -- CPD: Preis pro Tag für Netzwerk-Anteil
    -- CPM: Preis pro 1000 Impressionen
    -- CPA: Preis pro Conversion
    `daily_budget`    DECIMAL(20, 8)      NOT NULL DEFAULT 0.00000000,
    `total_budget`    DECIMAL(20, 8)      NOT NULL DEFAULT 0.00000000,
    `spent`           DECIMAL(20, 8)      NOT NULL DEFAULT 0.00000000,
    `currency`        VARCHAR(10)         NOT NULL DEFAULT 'BTC',
    `bid_amount`      DECIMAL(20, 8)      NOT NULL DEFAULT 0.00000000,
                                                                     -- CPM: Preis/1000, CPD: Tagesrate
    `target_url`      VARCHAR(2048)       NOT NULL,
    `target_countries`JSON               DEFAULT NULL,               -- NULL = alle Länder
    `target_categories`JSON              DEFAULT NULL,               -- NULL = alle Kategorien
    `target_languages` JSON              DEFAULT NULL,
    `starts_at`       DATETIME            DEFAULT NULL,
    `ends_at`         DATETIME            DEFAULT NULL,
    `approved_at`     DATETIME            DEFAULT NULL,
    `approved_by`     INT UNSIGNED        DEFAULT NULL,
    `reject_reason`   TEXT                DEFAULT NULL,
    `created_at`      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                                          ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id`   (`user_id`),
    INDEX `idx_status`    (`status`),
    INDEX `idx_dates`     (`starts_at`, `ends_at`),
    CONSTRAINT `fk_campaigns_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- AD BANNERS (Creatives)
-- HTML/CSS-only Banner – kein JavaScript, kein Tracking
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ad_banners` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`        CHAR(36)        NOT NULL UNIQUE,
    `campaign_id` INT UNSIGNED    NOT NULL,
    `user_id`     INT UNSIGNED    NOT NULL,
    `name`        VARCHAR(255)    NOT NULL,
    `size`        ENUM(
                    '728x90',   -- Leaderboard
                    '300x250',  -- Medium Rectangle
                    '160x600',  -- Wide Skyscraper
                    '320x50',   -- Mobile Banner
                    '468x60',   -- Full Banner
                    '250x250',  -- Square
                    '300x600'   -- Half Page
                  ) NOT NULL DEFAULT '300x250',
    `html`        TEXT            NOT NULL,                          -- reines HTML/CSS
    `status`      ENUM('pending_review','active','rejected','paused')
                                  NOT NULL DEFAULT 'pending_review',
    `reject_reason` TEXT          DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                  ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_campaign_id` (`campaign_id`),
    INDEX `idx_status`      (`status`),
    CONSTRAINT `fk_banners_campaign`
        FOREIGN KEY (`campaign_id`) REFERENCES `campaigns`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_banners_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- AD UNITS (Publisher)
-- Werbeflächen die Publisher auf ihren Seiten einbinden
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ad_units` (
    `id`              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `uuid`            CHAR(36)        NOT NULL UNIQUE,
    `user_id`         INT UNSIGNED    NOT NULL,
    `name`            VARCHAR(255)    NOT NULL,
    `website_url`     VARCHAR(2048)   NOT NULL,
    `category_id`     SMALLINT UNSIGNED DEFAULT NULL,
    `size`            ENUM(
                        '728x90','300x250','160x600',
                        '320x50','468x60','250x250','300x600'
                      ) NOT NULL DEFAULT '300x250',
    `status`          ENUM('pending_review','active','rejected','paused')
                                      NOT NULL DEFAULT 'pending_review',
    `floor_price`     DECIMAL(20,8)   NOT NULL DEFAULT 0.00000000,   -- Mindest-CPM
    `allowed_categories` JSON         DEFAULT NULL,                  -- NULL = alle
    `blocked_categories` JSON         DEFAULT NULL,
    `fallback_html`   TEXT            DEFAULT NULL,                  -- wenn keine Ad passt
    `created_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                      ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id`   (`user_id`),
    INDEX `idx_status`    (`status`),
    CONSTRAINT `fk_units_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_units_category`
        FOREIGN KEY (`category_id`) REFERENCES `ad_categories`(`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- IMPRESSIONS
-- Jede Ad-Auslieferung – partitioniert nach Monat für Performance
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `impressions` (
    `id`          BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `banner_id`   INT UNSIGNED        NOT NULL,
    `unit_id`     INT UNSIGNED        NOT NULL,
    `campaign_id` INT UNSIGNED        NOT NULL,
    `ip_hash`     CHAR(64)            NOT NULL,                      -- SHA256 der IP (kein Tracking)
    `country`     CHAR(2)             DEFAULT NULL,
    `referer`     VARCHAR(2048)       DEFAULT NULL,
    `user_agent_hash` CHAR(64)        DEFAULT NULL,
    `fraud_score` DECIMAL(3,2)        NOT NULL DEFAULT 0.00,         -- 0.00–1.00
    `is_fraud`    TINYINT(1)          NOT NULL DEFAULT 0,
    `cost`        DECIMAL(20,8)       NOT NULL DEFAULT 0.00000000,
    `created_at`  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`, `created_at`),
    INDEX `idx_banner_id`   (`banner_id`),
    INDEX `idx_unit_id`     (`unit_id`),
    INDEX `idx_campaign_id` (`campaign_id`),
    INDEX `idx_created_at`  (`created_at`),
    INDEX `idx_fraud`       (`is_fraud`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  PARTITION BY RANGE (YEAR(`created_at`) * 100 + MONTH(`created_at`)) (
    PARTITION p202501 VALUES LESS THAN (202502),
    PARTITION p202502 VALUES LESS THAN (202503),
    PARTITION p202503 VALUES LESS THAN (202504),
    PARTITION p202504 VALUES LESS THAN (202505),
    PARTITION p202505 VALUES LESS THAN (202506),
    PARTITION p202506 VALUES LESS THAN (202507),
    PARTITION p202507 VALUES LESS THAN (202508),
    PARTITION p202508 VALUES LESS THAN (202509),
    PARTITION p202509 VALUES LESS THAN (202510),
    PARTITION p202510 VALUES LESS THAN (202511),
    PARTITION p202511 VALUES LESS THAN (202512),
    PARTITION p202512 VALUES LESS THAN (202601),
    PARTITION p202601 VALUES LESS THAN (202602),
    PARTITION p202602 VALUES LESS THAN (202603),
    PARTITION p202603 VALUES LESS THAN (202604),
    PARTITION p202604 VALUES LESS THAN (202605),
    PARTITION p_future VALUES LESS THAN MAXVALUE
);

-- -----------------------------------------------------------------------------
-- CLICKS
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clicks` (
    `id`          BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `impression_id` BIGINT UNSIGNED   DEFAULT NULL,
    `banner_id`   INT UNSIGNED        NOT NULL,
    `unit_id`     INT UNSIGNED        NOT NULL,
    `campaign_id` INT UNSIGNED        NOT NULL,
    `ip_hash`     CHAR(64)            NOT NULL,
    `country`     CHAR(2)             DEFAULT NULL,
    `referer`     VARCHAR(2048)       DEFAULT NULL,
    `fraud_score` DECIMAL(3,2)        NOT NULL DEFAULT 0.00,
    `is_fraud`    TINYINT(1)          NOT NULL DEFAULT 0,
    `cost`        DECIMAL(20,8)       NOT NULL DEFAULT 0.00000000,
    `created_at`  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_banner_id`   (`banner_id`),
    INDEX `idx_campaign_id` (`campaign_id`),
    INDEX `idx_created_at`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- DAILY STATS (aggregiert für schnelle Dashboard-Abfragen)
-- Wird täglich per Cron befüllt
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `daily_stats` (
    `id`              INT UNSIGNED        NOT NULL AUTO_INCREMENT,
    `date`            DATE                NOT NULL,
    `campaign_id`     INT UNSIGNED        DEFAULT NULL,
    `unit_id`         INT UNSIGNED        DEFAULT NULL,
    `banner_id`       INT UNSIGNED        DEFAULT NULL,
    `country`         CHAR(2)             DEFAULT NULL,
    `impressions`     INT UNSIGNED        NOT NULL DEFAULT 0,
    `clicks`          INT UNSIGNED        NOT NULL DEFAULT 0,
    `fraud_impressions` INT UNSIGNED      NOT NULL DEFAULT 0,
    `fraud_clicks`    INT UNSIGNED        NOT NULL DEFAULT 0,
    `spend`           DECIMAL(20,8)       NOT NULL DEFAULT 0.00000000,
    `earnings`        DECIMAL(20,8)       NOT NULL DEFAULT 0.00000000,
    `ctr`             DECIMAL(6,4)        NOT NULL DEFAULT 0.0000,   -- Click-Through-Rate
    `ecpm`            DECIMAL(20,8)       NOT NULL DEFAULT 0.00000000,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_daily` (`date`, `campaign_id`, `unit_id`, `banner_id`, `country`),
    INDEX `idx_date`        (`date`),
    INDEX `idx_campaign_id` (`campaign_id`),
    INDEX `idx_unit_id`     (`unit_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- FRAUD LOGS (KI-gestützte Bot-Erkennung)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fraud_logs` (
    `id`          BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `type`        ENUM('impression','click')  NOT NULL,
    `ref_id`      BIGINT UNSIGNED     NOT NULL,                      -- impression_id oder click_id
    `ip_hash`     CHAR(64)            NOT NULL,
    `score`       DECIMAL(3,2)        NOT NULL,
    `signals`     JSON                NOT NULL,                      -- welche Regeln angeschlagen haben
    -- Beispiel signals: {"high_frequency": true, "datacenter_ip": true, "no_referer": false}
    `action`      ENUM('allow','flag','block') NOT NULL DEFAULT 'allow',
    `created_at`  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ip_hash`     (`ip_hash`),
    INDEX `idx_score`       (`score`),
    INDEX `idx_created_at`  (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- IP BLACKLIST
-- Geblockte IPs (Bots, Fraud, Datacenter-Ranges)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `ip_blacklist` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `ip_hash`     CHAR(64)        NOT NULL UNIQUE,
    `reason`      VARCHAR(255)    DEFAULT NULL,
    `auto_banned` TINYINT(1)      NOT NULL DEFAULT 1,
    `expires_at`  DATETIME        DEFAULT NULL,                      -- NULL = permanent
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_ip_hash` (`ip_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- EARNINGS (Publisher-Guthaben-Buchungen)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `earnings` (
    `id`          BIGINT UNSIGNED     NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED        NOT NULL,
    `unit_id`     INT UNSIGNED        NOT NULL,
    `date`        DATE                NOT NULL,
    `currency`    VARCHAR(10)         NOT NULL DEFAULT 'BTC',
    `amount`      DECIMAL(20,8)       NOT NULL DEFAULT 0.00000000,
    `impressions` INT UNSIGNED        NOT NULL DEFAULT 0,
    `clicks`      INT UNSIGNED        NOT NULL DEFAULT 0,
    `status`      ENUM('pending','confirmed','paid') NOT NULL DEFAULT 'pending',
    `paid_via`    INT UNSIGNED        DEFAULT NULL,                  -- payments.id
    `created_at`  DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_user_unit_date` (`user_id`, `unit_id`, `date`),
    INDEX `idx_user_id`   (`user_id`),
    INDEX `idx_status`    (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- NOTIFICATIONS
-- System-Benachrichtigungen für User
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
    `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    `user_id`     INT UNSIGNED    NOT NULL,
    `type`        VARCHAR(64)     NOT NULL,                          -- payment_received, campaign_approved ...
    `title`       VARCHAR(255)    NOT NULL,
    `message`     TEXT            NOT NULL,
    `data`        JSON            DEFAULT NULL,
    `read_at`     DATETIME        DEFAULT NULL,
    `created_at`  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id`   (`user_id`),
    INDEX `idx_read_at`   (`read_at`),
    CONSTRAINT `fk_notifications_user`
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- Insert default referral settings
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

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- Übersicht der Tabellen:
-- users                – Konten (anonym, kein KYC)
-- user_sessions        – Server-seitige Session-Verwaltung
-- crypto_wallets       – Auszahlungsadressen der User
-- balances             – Guthaben pro User & Währung
-- payments             – Ein-/Auszahlungen & Transaktionen
-- ad_categories        – Kategorien für Targeting
-- campaigns            – Werbekampagnen (Advertiser)
-- ad_banners           – HTML/CSS Banner-Creatives
-- ad_units             – Werbeflächen (Publisher)
-- impressions          – Jede Ad-Auslieferung (partitioniert)
-- clicks               – Klick-Tracking
-- daily_stats          – Aggregierte Tagesstatistiken
-- fraud_logs           – Bot/Fraud-Erkennungs-Protokoll
-- ip_blacklist         – Gesperrte IPs
-- earnings             – Publisher-Verdienst-Buchungen
-- notifications        – System-Benachrichtigungen
-- =============================================================================
