CREATE DATABASE IF NOT EXISTS `u667977963_devis` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;  /* Meilleur support Unicode */

USE `u667977963_devis`;

CREATE TABLE IF NOT EXISTS `u667977963_devis` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero` VARCHAR(20) NOT NULL,
  `date_devis` DATE NOT NULL,
  `client_nom` VARCHAR(100) NOT NULL,
  `client_email` VARCHAR(255) NOT NULL,
  `total` DECIMAL(10,2) NOT NULL,
  `developpement_vitrine` BOOLEAN DEFAULT FALSE,
  `formulaire_simple` BOOLEAN DEFAULT FALSE,
  `formulaire_complexe` BOOLEAN DEFAULT FALSE,
  `optimisation_seo` BOOLEAN DEFAULT FALSE,
  `systeme_paiement` BOOLEAN DEFAULT FALSE,
  `interface_admin` BOOLEAN DEFAULT FALSE,
  `nom_domaine` BOOLEAN DEFAULT FALSE,
  `hebergement` BOOLEAN DEFAULT FALSE,
  `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
