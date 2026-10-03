<?php
// api/crypto.php — proxy CoinGecko avec cache (protège du 429)
header('Content-Type: application/json');

define('CG_URL', 'https://api.coingecko.com/api/v3/coins/markets');
define('CACHE_FILE', __DIR__ . '/cache_crypto.json');
define('CACHE_TTL', 300); // 5 minutes : largement suffisant, 1 req/5min max

function sendJson($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// Cache encore valide ?
if (is_file(CACHE_FILE) && (time() - filemtime(CACHE_FILE)) < CACHE_TTL) {
    sendJson(json_decode(file_get_contents(CACHE_FILE), true));
}

$params = http_build_query([
    'vs_currency' => 'eur',
    'ids'          => 'bitcoin,solana,sui,usd-coin',
    'sparkline'    => 'false',
    'price_change_percentage' => '24h',
]);

$ch = curl_init(CG_URL . '?' . $params);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => [
        'Accept: application/json',
        // Si tu as une clé gratuite (pro.coingecko.com) :
        // 'x-cg-demo-api-key: TA_CLE_ICI',
    ],
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200 && $response !== false) {
    $data = json_decode($response, true);
    if (is_array($data) && $data !== []) {
        file_put_contents(CACHE_FILE, $response, LOCK_EX); // cache OK
        sendJson($data);
    }
}

// Échec : servir le cache périmé plutôt qu'une erreur
if (is_file(CACHE_FILE)) {
    sendJson(json_decode(file_get_contents(CACHE_FILE), true));
}
sendJson(['error' => 'CoinGecko indisponible', 'code' => $httpCode], 503);