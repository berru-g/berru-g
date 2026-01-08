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

-- Table utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table tentatives de connexion (protection brute force)
CREATE TABLE login_attempts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(45),
    email_attempted VARCHAR(255),
    success BOOLEAN,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Créer un admin (mot de passe: admin123)
INSERT INTO users (username, email, password_hash) 
VALUES ('admin', 'admin@dashboard.com', '$2y$12$Tzhz7XKyF4q8JcdLREftLuh.8pn0dsam0dFDR3.SduXQ/ZMDYRwYi');

-- Génère avec: echo password_hash('mdp123', PASSWORD_BCRYPT, ['cost' => 12]);