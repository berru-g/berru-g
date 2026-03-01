<?php
require_once '../includes/config.php'; // Ta config DB
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);

// Fonction pour appeler l'API et récupérer les stats
function getSiteStats($siteId, $apiKey, $startDate, $endDate) {
    $apiUrl = "https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php";
    $url = "$apiUrl?site_id=$siteId&api_key=$apiKey&start_date=$startDate&end_date=$endDate";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

// 1. Email après 7 jours
$stmt = $pdo->prepare("
    SELECT
        u.id, u.email, u.created_at, u.api_key,
        us.tracking_code, us.id AS site_id
    FROM users u
    JOIN user_sites us ON u.id = us.user_id
    WHERE DATEDIFF(NOW(), u.created_at) = 7
    AND u.email_sent_7d = FALSE
    AND u.unsubscribed = FALSE
");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $userId = $row['id'];
    $email = $row['email'];
    $siteId = $row['tracking_code']; // ou $row['site_id'] selon ta structure
    $apiKey = $row['api_key'];
    $createdAt = $row['created_at'];
    $startDate = date('Y-m-d', strtotime($createdAt));
    $endDate = date('Y-m-d', strtotime('+7 days', strtotime($createdAt)));

    // Récupérer les stats via l'API
    $stats = getSiteStats($siteId, $apiKey, $startDate, $endDate);
    $totalVisits = $stats['meta']['total_visits'] ?? 0;
    $totalUniqueVisitors = $stats['meta']['total_unique_visitors'] ?? 0;

    // Email personnalisé avec les données
    $subject = "📈 Tes 7 premiers jours sur LibreAnalytics : $totalVisits visites !";
    $message = "
        Bonjour,\n\n
        Voici un récapitulatif de ton trafic depuis ton inscription (du $startDate au $endDate) :\n
        - **Visites totales** : $totalVisits\n
        - **Visiteurs uniques** : $totalUniqueVisitors\n\n
        👉 [Voir ton dashboard](https://gael-berru.com/LibreAnalytics/dashboard.php)\n
        PS : Besoin d’aide pour analyser ces données ? Réponds à cet email !
    ";
    $headers = "From: contact@gael-berru.com\r\n";
    mail($email, $subject, $message, $headers);

    // Marquer comme envoyé
    $pdo->exec("UPDATE users SET email_sent_7d = TRUE WHERE id = $userId");
}

// 2. Email après 14 jours
$stmt = $pdo->prepare("
    SELECT
        u.id, u.email, u.created_at, u.api_key,
        us.tracking_code, us.id AS site_id
    FROM users u
    JOIN user_sites us ON u.id = us.user_id
    WHERE DATEDIFF(NOW(), u.created_at) = 14
    AND u.email_sent_14d = FALSE
    AND u.unsubscribed = FALSE
");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $userId = $row['id'];
    $email = $row['email'];
    $siteId = $row['tracking_code'];
    $apiKey = $row['api_key'];
    $createdAt = $row['created_at'];
    $startDate = date('Y-m-d', strtotime($createdAt));
    $endDate = date('Y-m-d', strtotime('+14 days', strtotime($createdAt)));

    // Récupérer les stats via l'API
    $stats = getSiteStats($siteId, $apiKey, $startDate, $endDate);
    $totalVisits = $stats['meta']['total_visits'] ?? 0;
    $totalUniqueVisitors = $stats['meta']['total_unique_visitors'] ?? 0;

    // Email personnalisé avec les données
    $subject = "📊 Bilan de tes 2 semaines sur LibreAnalytics : $totalVisits visites";
    $message = "
        Bonjour,\n\n
        Voici ton bilan après 14 jours (du $startDate au $endDate) :\n
        - **Visites totales** : $totalVisits\n
        - **Visiteurs uniques** : $totalUniqueVisitors\n\n
        💡 **Conseil** : Utilise les paramètres UTM pour tracer tes campagnes marketing !\n
        👉 [Découvrir comment](https://gael-berru.com/LibreAnalytics/docs#utm)\n
        👉 [Passer en Pro pour plus de stats](https://gael-berru.com/LibreAnalytics/upgrade)
    ";
    $headers = "From: contact@gael-berru.com\r\n";
    mail($email, $subject, $message, $headers);

    // Marquer comme envoyé
    $pdo->exec("UPDATE users SET email_sent_14d = TRUE WHERE id = $userId");
}
?>
