<?php
require_once '../includes/config.php';
// requete à cron pour automatiser l'envoie du rapport tout les vendredi à 17h :
// 0 17 * * 5 /usr/bin/php /chemin/absolu/vers/cron/send_sequential_email_V2.php

$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Fonction pour générer un graphique de trafic (visites/visiteurs uniques)
function generateSVGGraph($stats, $userId) {
    $svg = '<svg width="600" height="300" viewBox="0 0 600 300" xmlns="http://www.w3.org/2000/svg">
        <rect width="100%" height="100%" fill="#fff" />
        <!-- Axes -->
        <line x1="40" y1="20" x2="40" y2="260" stroke="#ccc" />
        <line x1="40" y1="260" x2="560" y2="260" stroke="#ccc" />
        <!-- Légende -->
        <text x="200" y="20" text-anchor="middle" font-size="14">Trafic Hebdomadaire</text>
        <!-- Courbe des visites (simplifiée) -->
        <polyline
            fill="none"
            stroke="#9d86ff"
            stroke-width="2"
            points="';
    $points = [];
    $maxVisits = max(array_column($stats['daily'], 'visits'));
    foreach ($stats['daily'] as $date => $day) {
        $x = 40 + (strtotime($date) - strtotime($stats['start_date'])) * (520 / 6);
        $y = 260 - ($day['visits'] / $maxVisits) * 220;
        $points[] = "$x,$y";
    }
    $svg .= implode(' ', $points) . '"
        />
        <!-- Autres éléments SVG... -->
    </svg>';
    file_put_contents(__DIR__ . "/../tmp/graphs/graph_$userId.svg", $svg);
    return "https://gael-berru.com/LibreAnalytics/tmp/graphs/graph_$userId.svg";
}


// Fonction pour récupérer les stats de la semaine
function getWeeklyStats($siteId, $apiKey) {
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime('-7 days'));

    $apiUrl = "https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php";
    $url = "$apiUrl?site_id=$siteId&api_key=$apiKey&start_date=$startDate&end_date=$endDate";

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    if (!$data || !isset($data['data'])) {
        return null;
    }

    // Formatage des données pour le graphique
    $formattedStats = [
        'total_visits' => $data['meta']['total_visits'] ?? 0,
        'total_unique_visitors' => $data['meta']['total_unique_visitors'] ?? 0,
        'daily' => []
    ];

    foreach ($data['data'] as $day) {
        $formattedStats['daily'][$day['date']] = [
            'visits' => $day['visits'],
            'unique_visitors' => $day['unique_visitors']
        ];
    }

    return $formattedStats;
}

// Récupérer les utilisateurs éligibles (inscrits depuis au moins 7 jours et non désabonnés) WHERE u.unsubscribed = FALSE
$stmt = $pdo->prepare("
    SELECT u.id, u.email, u.api_key, us.tracking_code as site_id
    FROM users u
    JOIN user_sites us ON u.id = us.user_id
    
    AND DATEDIFF(NOW(), u.created_at) >= 7  -- Inscrit depuis au moins 7 jours
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $userId = $user['id'];
    $email = $user['email'];
    $siteId = $user['site_id'];
    $apiKey = $user['api_key'];

    // Récupérer les stats de la semaine
    $stats = getWeeklyStats($siteId, $apiKey);
    if (!$stats) {
        file_put_contents('../logs/email_errors.txt', date('Y-m-d H:i:s') . " - Impossible de récupérer les stats pour $email\n", FILE_APPEND);
        continue;
    }

    // Générer le graphique
    $graphPath = generateSVGGraph($stats, $userId);
    if (!file_exists($graphPath)) {
        file_put_contents('../logs/email_errors.txt', date('Y-m-d H:i:s') . " - Échec de la génération du graphique pour $email\n", FILE_APPEND);
        continue;
    }

    // Lien absolu vers le graphique (accessible publiquement)
    $graphUrl = "https://gael-berru.com/LibreAnalytics/tmp/graphs/" . basename($graphPath);

    // Template d'email avec graphique intégré
    $subject = "📊 Ton rapport hebdomadaire LibreAnalytics - " . $stats['total_visits'] . " visites cette semaine !";
    $message = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport Hebdomadaire LibreAnalytics</title>
    <style>
        body { font-family: 'Segoe UI', Roboto, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
        .container { background-color: #fff; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); border: 1px solid #eaeaea; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { color: #9d86ff; font-size: 24px; font-weight: 600; margin-bottom: 10px; }
        .tagline { color: #666; font-size: 16px; }
        .content { margin: 20px 0; }
        .highlight { color: #9d86ff; font-weight: 600; }
        .stats-box { background-color: #f5f5f5; border: 1px solid #eaeaea; border-radius: 4px; padding: 15px; margin: 15px 0; text-align: center; }
        .button { display: inline-block; background-color: #9d86ff; color: #fff !important; text-decoration: none; padding: 12px 24px; border-radius: 4px; font-weight: 600; margin: 20px 0; text-align: center; }
        .footer { margin-top: 30px; text-align: center; color: #999; font-size: 14px; }
        .footer a { color: #9d86ff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">LibreAnalytics</div>
            <div class="tagline">Ton rapport hebdomadaire</div>
        </div>

        <div class="content">
            <p>Bonjour,</p>

            <p>Voici ton <span class="highlight">rapport hebdomadaire</span> du <strong>{$startDate}</strong> au <strong>{$endDate}</strong> :</p>

            <div class="stats-box">
                <p style="margin: 5px 0; font-size: 18px; font-weight: 600;">📊 Tes stats</p>
                <p style="margin: 10px 0; font-size: 16px;"><strong>{$stats['total_visits']}</strong> visites totales</p>
                <p style="margin: 10px 0; font-size: 16px;"><strong>{$stats['total_unique_visitors']}</strong> visiteurs uniques</p>
            </div>

            <p style="text-align: center;">
                <img src="$graphUrl" alt="Graphique de trafic hebdomadaire" style="max-width: 100%; border-radius: 4px; margin: 10px 0;">
            </p>

            <p style="text-align: center;">
                <a href="https://gael-berru.com/LibreAnalytics/dashboard.php" class="button">Accéder à mon dashboard</a>
            </p>

            <p>Besoin d’aide pour analyser ces données ou optimiser ton site ? <strong>Réponds simplement à cet email</strong> !</p>
        </div>

        <div class="footer">
            <p>© 2026 LibreAnalytics – Une alternative <strong>100% française</strong>, <strong>open source</strong> et <strong>RGPD-friendly</strong> à Google Analytics.</p>
            <p><a href="https://gael-berru.com">Visite notre site</a> | <a href="https://gael-berru.com/LibreAnalytics/docs">Documentation</a></p>
        </div>
    </div>
</body>
</html>
HTML;

// En-têtes pour l'email HTML
$headers = "From: \"L'équipe LibreAnalytics\" <contact@gael-berru.com>\r\n";
$headers .= "Reply-To: contact@gael-berru.com\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

// Envoi de l'email
$mailSent = mail($email, $subject, $message, $headers);
if (!$mailSent) {
    file_put_contents('../logs/email_errors.txt', date('Y-m-d H:i:s') . " - Échec de l'envoi à $email\n", FILE_APPEND);
} else {
    file_put_contents('../logs/email_sent.txt', date('Y-m-d H:i:s') . " - Email envoyé à $email (visites: {$stats['total_visits']})\n", FILE_APPEND);
}
}
?>
