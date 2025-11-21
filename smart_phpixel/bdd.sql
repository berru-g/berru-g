CREATE TABLE smart_pixel_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME,
    ip_address VARCHAR(45),
    user_agent TEXT,
    page_url TEXT,
    source VARCHAR(100),
    campaign VARCHAR(100),
    country VARCHAR(100),
    city VARCHAR(100),
    click_data JSON,
    viewport VARCHAR(50),
    session_id VARCHAR(100),
    INDEX idx_timestamp (timestamp),
    INDEX idx_source (source),
    INDEX idx_country (country)
);