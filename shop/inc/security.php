<?php
require_once __DIR__ . '/config.php';

/**
 * Génère un token CSRF
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie un token CSRF
 */
function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Nettoie une entrée utilisateur
 */
function sanitizeInput($input, $type = 'string') {
    if ($input === null) return null;

    switch ($type) {
        case 'int':
            return filter_var($input, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        case 'float':
            return filter_var($input, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]);
        case 'email':
            return filter_var($input, FILTER_SANITIZE_EMAIL);
        case 'url':
            return filter_var($input, FILTER_SANITIZE_URL);
        case 'bool':
            return filter_var($input, FILTER_VALIDATE_BOOLEAN);
        default:
            return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Vérifie qu'une adresse Solana est valide
 */
function isValidSolanaAddress($address) {
    return preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address);
}

/**
 * Vérifie qu'un hash de transaction Solana est valide
 */
function isValidTxHash($hash) {
    return preg_match('/^[1-9A-HJ-NP-Za-km-z]{64,88}$/', $hash);
}

/**
 * Envoie un header de sécurité
 */
function sendSecurityHeaders() {
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com; style-src 'self' 'unsafe-inline' https://unpkg.com; img-src 'self' data:; font-src 'self' https://unpkg.com; connect-src 'self' https://api.coingecko.com https://api.mainnet-beta.solana.com; frame-ancestors 'none'; form-action 'self';");
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

/**
 * Limite le nombre de requêtes par IP (rate limiting)
 */
function checkRateLimit($key, $maxRequests = 10, $timeWindow = 60) {
    $file = __DIR__ . '/../cache/rate_limits.json';
    $now = time();

    if (!file_exists($file)) {
        file_put_contents($file, json_encode([]));
    }

    $data = json_decode(file_get_contents($file), true);
    if (!isset($data[$key])) {
        $data[$key] = ['count' => 0, 'last_request' => 0];
    }

    if ($now - $data[$key]['last_request'] > $timeWindow) {
        $data[$key] = ['count' => 1, 'last_request' => $now];
    } else {
        $data[$key]['count']++;
        if ($data[$key]['count'] > $maxRequests) {
            error_log("Rate limit exceeded for key: $key");
            return false;
        }
    }

    file_put_contents($file, json_encode($data));
    return true;
}
?>