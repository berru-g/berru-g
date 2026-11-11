-- =============================================================
-- Berru Shop — base de données MySQL (utf8mb4)
-- Boutique /shop/ — créations électroniques de Berru
-- Fiches produits synchronisées avec Stripe (compte "mr", mode live)
-- Prix TTC en centimes d'euro (TVA française incluse)
-- =============================================================

CREATE DATABASE IF NOT EXISTS berru_shop
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE berru_shop;

-- -------------------------------------------------------------
-- Table : produits (1 fiche par création électronique)
-- Les colonnes stripe_* font le lien avec les objets Stripe réels.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS products (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug                   VARCHAR(128) NOT NULL UNIQUE,
    name                   VARCHAR(191) NOT NULL,
    short_desc             VARCHAR(255) NOT NULL,
    description            TEXT NOT NULL,
    image                  VARCHAR(500) DEFAULT NULL,
    price_cents            INT UNSIGNED NOT NULL,           -- TTC, centimes
    currency               CHAR(3) NOT NULL DEFAULT 'EUR',
    stock                  INT UNSIGNED NOT NULL DEFAULT 0, -- 0 = sur commande
    stripe_product_id      VARCHAR(64) NOT NULL,
    stripe_price_id        VARCHAR(64) NOT NULL,
    stripe_payment_link    VARCHAR(255) NOT NULL,           -- lien buy.stripe.com
    active                 TINYINT(1) NOT NULL DEFAULT 1,
    sort_order             INT UNSIGNED NOT NULL DEFAULT 0,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_active (active)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Table : commandes (remplies par webhook.php à chaque paiement)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS orders (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    stripe_session_id      VARCHAR(64) DEFAULT NULL,
    stripe_payment_intent  VARCHAR(64) DEFAULT NULL,
    stripe_customer_email  VARCHAR(191) DEFAULT NULL,
    product_id             INT UNSIGNED NOT NULL,
    quantity               INT UNSIGNED NOT NULL DEFAULT 1,
    amount_total           INT UNSIGNED NOT NULL,          -- TTC, centimes
    currency               CHAR(3) NOT NULL DEFAULT 'eur',
    payment_status         VARCHAR(32) NOT NULL DEFAULT 'pending', -- pending|paid|failed|refunded
    shipping_address       TEXT DEFAULT NULL,
    created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_session (stripe_session_id),
    FOREIGN KEY fk_orders_product (product_id)
        REFERENCES products(id) ON DELETE RESTRICT,
    INDEX idx_status (payment_status)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
-- Données : les 4 fiches produits (IDs Stripe LIVE réels, 11 nov 2026)
-- -------------------------------------------------------------
INSERT INTO products
    (slug, name, short_desc, description, image, price_cents, stock, stripe_product_id, stripe_price_id, stripe_payment_link, active, sort_order)
VALUES
('nfc-badge',
 'NFC Badge — badge médical reprogrammable',
 'Badge NFC open-source (NT3H2111), broche scarabée, reprogrammable.',
 'Badge NFC open-source basé sur la puce NT3H2111, reprogrammable. Stocke un lien vers un dossier médical ou toute URL de votre choix. Broche scarabée, circuit et PCB conçus 100 % maison. Projet open source : plans et code disponibles sur GitHub.',
 'https://github.com/berru-g/NFC_Badge/raw/main/img/Scarabe-nfc-broche.png',
 2500, 10,
 'prod_VbiXlGLIFIDvlN', 'price_1UaV6dAuPK0H8L5sLuam8H0a',
 'https://buy.stripe.com/6oU3cu93P5t7bmP0XT8og00',
 1, 1),

('rc-motor',
 'RC Motor — kit radio commandé 2 km',
 'Émetteur + récepteur de A à Z, brushless 2600 Kv, portée 2 km.',
 'Système radio commandé émetteur + récepteur conçu de A à Z, pour avion, bateau ou voiture télécommandée. Prévu pour un moteur brushless 2600 Kv et 3 servos 9g. Capte jusqu''à 2 km, poussée de 1 kg. Open source : schémas et firmware sur GitHub.',
 'https://github.com/berru-g/rc-plane/raw/main/img/pcb-final.png',
 9000, 5,
 'prod_VbiXvFXPYTrCdh', 'price_1UaV6nAuPK0H8L5s8KSspRxF',
 'https://buy.stripe.com/fZu00i6VH08N1Mf8ql8og01',
 1, 2),

('midi-usb-c',
 'MIDI USB-C — contrôleur DAW Arduino',
 'Contrôleur USB MIDI low-cost, plug & play sur Ableton, Arena…',
 'Microcontrôleur USB MIDI low-cost, plug & play sur tout logiciel DAW (Ableton, Arena ou tout autre logiciel de création musicale ou vidéo). Circuit électronique et PCB conçus maison, boîtier cuivre.',
 './img/make&play.jpg',
 5900, 8,
 'prod_VbiY5JkSKLJaQf', 'price_1UaV6uAuPK0H8L5sPctx3GRD',
 'https://buy.stripe.com/5kQ00i3Jv08N3Unayt8og02',
 1, 3),

('shield-arduino-lego',
 'Shield Arduino UNO LEGO',
 'Shield éducatif UNO adaptable au format LEGO.',
 'Shield Arduino UNO adaptable au format LEGO. Shield éducatif pour vos projets robotique, DAW et pour laisser les kids exprimer leur créativité. Compatible briques LEGO standard.',
 './img/shield-arduino-uno-lego.jpg',
 1900, 12,
 'prod_VbiYLPVfNDKBMr', 'price_1UaV6wAuPK0H8L5sVDzGdzPf',
 'https://buy.stripe.com/3cI28qbbX1cRez1gWR8og03',
 1, 4);