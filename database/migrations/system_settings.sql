-- =============================================================================
-- System Settings Migration
-- Adds key-value site settings and banner format management tables
-- =============================================================================

CREATE TABLE IF NOT EXISTS `site_settings` (
    `key`        VARCHAR(100) NOT NULL,
    `value`      TEXT         DEFAULT NULL,
    `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `site_settings` (`key`, `value`) VALUES
('site_name',           'aidzap.com'),
('site_url',            'https://aidzap.com'),
('site_email',          'noreply@aidzap.com'),
('support_email',       'support@aidzap.com'),
('ga_enabled',          '0'),
('ga_id',               ''),
('smtp_enabled',        '0'),
('smtp_host',           ''),
('smtp_port',           '587'),
('smtp_user',           ''),
('smtp_pass',           ''),
('smtp_from_email',     ''),
('smtp_from_name',      'aidzap.com'),
('smtp_encryption',     'tls'),
('double_optin',        '0'),
('maintenance_mode',    '0'),
('maintenance_notice',  'We are back soon.'),
('newsletter_enabled',  '0');

CREATE TABLE IF NOT EXISTS `banner_formats` (
    `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `width`       INT UNSIGNED NOT NULL,
    `height`      INT UNSIGNED NOT NULL,
    `size_key`    VARCHAR(20)  NOT NULL UNIQUE,
    `is_active`   TINYINT(1)   NOT NULL DEFAULT 1,
    `sort_order`  INT          NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `banner_formats` (`name`, `width`, `height`, `size_key`, `sort_order`) VALUES
('Medium Rectangle', 300, 250, '300x250', 1),
('Full Banner',      468,  60, '468x60',  2),
('Leaderboard',      728,  90, '728x90',  3),
('Wide Skyscraper',  160, 600, '160x600', 4),
('Half Page',        300, 600, '300x600', 5),
('Large Rectangle',  336, 280, '336x280', 6),
('Mobile Banner',    320,  50, '320x50',  7),
('Mobile Rectangle', 300,  50, '300x50',  8);