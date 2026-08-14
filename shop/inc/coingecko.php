<?php
require_once __DIR__ . '/config.php';

function getExchangeRates() {
    global $db;

    // Vérifier si les taux sont à jour (moins de 1h)
    $result = $db->query("SELECT * FROM exchange_rates WHERE updated_at > NOW() - INTERVAL 1 HOUR");
    if ($result->num_rows == 2) { // SOL et USDC
        $rates = [];
        while ($row = $result->fetch_assoc()) {
            $rates[$row['symbol']] = $row['price_usd'];
        }
        return $rates;
    }

    // Récupérer les taux depuis CoinGecko
    $url = COINGECKO_API . '/simple/price?ids=solana,usd-coin&vs_currencies=usd';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($data && isset($data['solana']['usd']) && isset($data['usd-coin']['usd'])) {
        // Mettre à jour la base de données
        $db->query("TRUNCATE TABLE exchange_rates");
        $db->insert('exchange_rates', ['symbol' => 'sol', 'price_usd' => $data['solana']['usd']]);
        $db->insert('exchange_rates', ['symbol' => 'usdc', 'price_usd' => $data['usd-coin']['usd']]);

        return [
            'sol' => $data['solana']['usd'],
            'usdc' => $data['usd-coin']['usd']
        ];
    }

    // Valeurs par défaut en cas d'erreur
    return [
        'sol' => 100,   // ~100$ par SOL (valeur approximative)
        'usdc' => 1.0   // 1 USDC = 1 USD
    ];
}

// Exemple d'utilisation :
// $rates = getExchangeRates();
// $solToUsd = $rates['sol'];
// $usdcToUsd = $rates['usdc'];
?>