CREATE TABLE rate_limits (
    id           BIGINT AUTO_INCREMENT PRIMARY KEY,
    key_hash     CHAR(64) NOT NULL,
    endpoint     VARCHAR(50) NOT NULL,
    hits         INT UNSIGNED DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_key_endpoint (key_hash, endpoint)
);
