-- db/keywords.sql
CREATE TABLE keywords (
    id INT PRIMARY KEY AUTO_INCREMENT,
    keyword VARCHAR(100) NOT NULL,
    category ENUM('pain', 'solution', 'competitor', 'rgpd') NOT NULL,
    weight INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insérer tes mots-clés
INSERT INTO keywords (keyword, category, weight) VALUES
-- Douleurs (très important)
('GA4 trop complexe', 'pain', 3),
('je galère avec google analytics', 'pain', 3),
('analytics compliqué', 'pain', 2),
('RGPD google analytics', 'pain', 3),
('google analytics illégal', 'pain', 3),
('performance google analytics', 'pain', 2),
-- Solutions recherchées
('alternative analytics', 'solution', 2),
('analytics français', 'solution', 2),
('analytics rgpd', 'solution', 3),
('analytics simple', 'solution', 2),
('no gafam', 'solution', 3),
('souveraineté données', 'solution', 3),
-- Concurrents (si ils en parlent, c'est bon signe)
('matomo', 'competitor', 1),
('plausible', 'competitor', 1),
('fathom', 'competitor', 1),
('simple analytics', 'competitor', 1),
('pirsch', 'competitor', 1),
-- RGPD/données
('données france', 'rgpd', 2),
('hébergement france', 'rgpd', 2),
('vie privée', 'rgpd', 2),
('cookies', 'rgpd', 1),
('consentement', 'rgpd', 1);

-- Table pour logguer les interactions
CREATE TABLE interactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    platform VARCHAR(50) NOT NULL,
    platform_id VARCHAR(100) NOT NULL,
    title VARCHAR(500),
    should_respond BOOLEAN DEFAULT FALSE,
    score INT DEFAULT 0,
    found_keywords TEXT,
    categories TEXT,
    responded BOOLEAN DEFAULT FALSE,
    comment_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_platform_id (platform, platform_id),
    INDEX idx_responded (responded),
    INDEX idx_created (created_at)
);

-- Table pour les réponses générées (pour améliorer avec le temps)
CREATE TABLE responses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    trigger_keywords TEXT,
    response_text TEXT,
    success_rate FLOAT DEFAULT 0,
    times_used INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);