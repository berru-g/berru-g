-- database.sql
CREATE DATABASE IF NOT EXISTS scroll3d_edit;
USE scroll3d_edit;

-- Table des utilisateurs
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    avatar_url VARCHAR(255) DEFAULT '/img/default-avatar.png',
    website VARCHAR(255),
    bio TEXT,
    subscription_type ENUM('free', 'pro') DEFAULT 'free',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des projets
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    model_data JSON NOT NULL, -- Stocke les keyframes, positions, etc.
    is_public BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des likes
CREATE TABLE project_likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (project_id, user_id),
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des commentaires
CREATE TABLE project_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    project_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des sessions
CREATE TABLE user_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    session_token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des abonnements (pour les utilisateurs Pro)
ALTER TABLE users ADD COLUMN points INT DEFAULT 200;

CREATE TABLE point_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    points_amount INT,
    amount_eur DECIMAL(10,2),
    payment_intent_id VARCHAR(255),
    status ENUM('pending', 'completed', 'failed'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE point_packs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    points_amount INT,
    price_eur DECIMAL(10,2),
    is_active BOOLEAN DEFAULT true
);

-- Packs initiaux
INSERT INTO point_packs (name, points_amount, price_eur) VALUES
('Pack Starter', 100, 4.90),
('Pack Pro', 500, 19.90),
('Pack Expert', 1500, 49.90);

-- Ajout de la colonne last_login pour suivre la dernière connexion des utilisateurs
ALTER TABLE users ADD COLUMN last_login DATE NULL;

-- || BETA TESTERS PACKS CADEAUX ||
-- || Ajouter des points directement aux utilisateurs beta testers
-- UPDATE users SET points = 500 WHERE id IN (2, 3, 4, 5);
-- || Ou pour des valeurs différentes par utilisateur
-- UPDATE users SET points = 1000 WHERE id = 2;   -- Super beta tester
-- UPDATE users SET points = 750 WHERE id = 3;    -- Beta tester actif
-- UPDATE users SET points = 500 WHERE id = 4;    -- Beta tester régulier
-- UPDATE users SET points = 300 WHERE id = 5;    -- Nouveau beta tester

-- || 1. Ajouter les points à l'utilisateur avec id = 2
-- UPDATE users SET points = points + 500 WHERE id = 2;
-- || 2. Enregistrer la transaction
-- INSERT INTO point_transactions (user_id, points_amount, amount_eur, status, payment_intent_id, created_at) 
-- VALUES (2, 500, 19.90, 'completed', 'pi_test_beta_tester_1', NOW() - INTERVAL 7 DAY);

-- FIN - BETA TESTERS PACKS CADEAUX

-- FORMULAIRE DE CONTACT
CREATE TABLE IF NOT EXISTS feedback (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    type ENUM('bug', 'feature', 'improvement', 'uiux', 'other') NOT NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'reviewed', 'in_progress', 'completed') DEFAULT 'new',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
