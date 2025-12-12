-- Table pour suivre les ouvertures de cases
CREATE TABLE advent_opens (
    id BIGSERIAL PRIMARY KEY,
    user_id UUID REFERENCES auth.users(id),
    user_email TEXT,
    day INTEGER NOT NULL CHECK (day >= 1 AND day <= 24),
    opened_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(user_id, day) -- Empêche d'ouvrir plusieurs fois la même case
);

-- Table pour collecter les emails (backup)
CREATE TABLE advent_subscribers (
    id BIGSERIAL PRIMARY KEY,
    email TEXT UNIQUE NOT NULL,
    subscribed_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    source TEXT DEFAULT 'website',
    last_opened_day INTEGER,
    total_opens INTEGER DEFAULT 0
);

-- Index pour optimiser les requêtes
CREATE INDEX idx_advent_opens_user ON advent_opens(user_id);
CREATE INDEX idx_advent_opens_day ON advent_opens(day);
CREATE INDEX idx_advent_opens_user_day ON advent_opens(user_id, day);

-- Active l'authentification par email
-- Va dans Authentication > Settings > Enable Email Auth
-- Coche "Enable magic link login"