CREATE DATABASE IF NOT EXISTS smartpixel_app;
USE smartpixel_app;

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

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- chaque hit = lié à un site
-- chaque site = lié à un user
CREATE TABLE sites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  domain VARCHAR(190) NOT NULL,
  public_key VARCHAR(64) NOT NULL UNIQUE,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE smart_pixel_tracking
ADD site_id INT NOT NULL AFTER id,
ADD INDEX idx_site_id (site_id);

-- CORRECTION : Ajouter les colonnes une par une
ALTER TABLE users ADD COLUMN api_key VARCHAR(64) UNIQUE;
ALTER TABLE users ADD COLUMN plan ENUM('free', 'pro', 'business') DEFAULT 'free';
ALTER TABLE users ADD COLUMN sites_limit INT DEFAULT 1;
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN settings JSON;

-- CORRECTION : user_sites existe déjà (c'est la table "sites")
-- Donc on renomme ou on adapte, voici l'option la plus simple :
RENAME TABLE sites TO user_sites;

ALTER TABLE user_sites 
ADD COLUMN site_name VARCHAR(255) NOT NULL AFTER user_id,
ADD COLUMN tracking_code VARCHAR(32) UNIQUE NOT NULL AFTER domain,
ADD COLUMN is_active BOOLEAN DEFAULT TRUE AFTER tracking_code;

-- Mettre à jour les noms de domaines existants comme site_name
UPDATE user_sites SET site_name = domain WHERE site_name = '';

-- Générer des tracking codes pour les sites existants
UPDATE user_sites SET tracking_code = CONCAT('SP_', UPPER(SUBSTRING(MD5(RAND()), 1, 8))) 
WHERE tracking_code IS NULL OR tracking_code = '';

-- CORRECTION : La colonne site_id existe déjà dans smart_pixel_tracking
-- On ajoute juste user_id et on adapte les index
ALTER TABLE smart_pixel_tracking 
ADD COLUMN user_id INT NULL AFTER site_id;

-- On supprime l'index en double si besoin
-- ALTER TABLE smart_pixel_tracking DROP INDEX idx_site_user; 

ALTER TABLE smart_pixel_tracking 
ADD INDEX idx_site_user (site_id, user_id);

-- CORRECTION : Table daily_stats
CREATE TABLE IF NOT EXISTS daily_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    date DATE NOT NULL,
    visits INT DEFAULT 0,
    unique_visitors INT DEFAULT 0,
    pageviews INT DEFAULT 0,
    UNIQUE KEY unique_site_date (site_id, date),
    FOREIGN KEY (site_id) REFERENCES user_sites(id) ON DELETE CASCADE
);

-- Table pour suivre les paiements
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status VARCHAR(50) NOT NULL,
    lemon_id VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_status (status)
);

-- Table pour l'historique des abonnements
CREATE TABLE IF NOT EXISTS subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan VARCHAR(50) NOT NULL,
    status VARCHAR(50) DEFAULT 'active',
    lemon_subscription_id VARCHAR(100),
    current_period_start DATE,
    current_period_end DATE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_status (user_id, status)
);

-- Mettre à jour les settings existants
UPDATE users SET settings = JSON_OBJECT() WHERE settings IS NULL;