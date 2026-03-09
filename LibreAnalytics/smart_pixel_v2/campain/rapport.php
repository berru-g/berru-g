<?php
// rapport: envoie manuel a chaque id et son SP_ , l'api key étant propre à l'id mais commune aux différents code SP_ de l'id
$user = [
    'id' => 0, // id
    'email' => 'tonmzil@mail.con', // Remplace par l'email du destinataire 
    'api_key' => 'api key ici',       // Remplace par la clé API réelle
    'site_id' => 'SP_codela'       // Remplace par le site_id réel
];

// 2. Extraire le nom avant @ dans l'email
$emailParts = explode('@', $user['email']);
$name = $emailParts[0]; // Prénom = partie avant le "@"

// 3. Créer les dossiers nécessaires (pour les logs)
$logDir = __DIR__ . "/../logs/";
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}

// 4. Appeler l'API pour récupérer les stats
$endDate = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-90 days'));
$apiUrl = "https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php?site_id={$user['site_id']}&api_key={$user['api_key']}&start_date=$startDate&end_date=$endDate";

echo "⏳ Appel API : $apiUrl<br>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Code HTTP : $httpCode<br>";
echo "Réponse API : <pre>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>";

$data = json_decode($response, true);
if ($httpCode !== 200 || json_last_error() !== JSON_ERROR_NONE || !$data || !isset($data['data'])) {
    die("❌ Erreur API ou JSON invalide. Vérifie l'URL et les paramètres.");
}

$totalVisits = $data['meta']['total_visits'] ?? 0;
$totalUniqueVisitors = $data['meta']['total_unique_visitors'] ?? 0;
echo "Stats récupérées : $totalVisits visites, $totalUniqueVisitors visiteurs uniques.<br>";

// 5. Préparer les données pour AmCharts (format JSON)
$chartData = [];
foreach ($data['data'] as $day) {
    $chartData[] = [
        'date' => $day['date'],
        'visits' => $day['visites'] ?? $day['visits'] ?? 0,
        'unique' => $day['unique_visitors'] ?? $day['uniqueVisitors'] ?? 0
    ];
}
$chartDataJson = json_encode($chartData);

// 6. Envoyer l'email avec un graphique AmCharts intégré
$subject = "📆 Ton rapport trimestriel - $totalVisits visites, $name";
$message = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rapport trimestriel</title>
    <!-- Intégration d'AmCharts via CDN -->
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
        .container { background-color: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { color: #9d86ff; font-size: 24px; font-weight: bold; }
        .stats { background-color: #f5f5f5; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; }
        .chart-container { width: 100%; height: 300px; margin: 20px 0; }
        .button { display: inline-block; background-color: #9d86ff; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; margin: 20px 0; }.footer {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 14px;
        }
        .footer a {
            color: #9d86ff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">LibreAnalytics</div>:
            <p>L'analytics souverains et indépendant</p>
        </div>
        <p>Bonjour $name,</p>
        <p>Voici ton rapport trimestriel du <strong>$startDate</strong> au <strong>$endDate</strong> :</p>
        <div class="stats">
            <p>Tu as reçu <strong>$totalVisits</strong> visites,</p>
            <p>dont <strong>$totalUniqueVisitors</strong> visiteurs uniques.</p>
        </div>
       <div class="chart-container">
            <div id="chartdiv" style="width: 210px; height: 297px;"></div>
        </div>
        <p style="text-align: center;">
            <a href="https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/dashboard.php?utm_source=Rapport" class="button">Accéder à mon dashboard</a>
        </p>
       
        <div class="footer">
            <p>© 2026 LibreAnalytics MVP V.1.0.7 – Une alternative <strong>100% française</strong>, <strong>open source</strong> et <strong>RGPD-friendly</strong> à Google Analytics.</p>
            <p><a href="https://gael-berru.com/LibreAnalytics?utm_source=Rapport">Visite notre site</a> | <a href="https://gael-berru.com/LibreAnalytics/doc?utm_source=Rapport">Documentation</a></p>
        </div>
    </div>

    <!-- Script pour générer le graphique AmCharts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Données du graphique (passées depuis PHP)
        var chartData = $chartDataJson;

        // Créer le graphique
        var root = am5.Root.new("chartdiv");

        root.setThemes([
            am5themes_Animated.new(root)
        ]);

        var chart = root.container.children.push(
            am5xy.XYChart.new(root, {
                panX: false,
                panY: false,
                wheelX: "panX",
                wheelY: "zoomX",
                layout: root.verticalLayout
            })
        );

        // Axes
        var xAxis = chart.xAxes.push(
            am5xy.DateAxis.new(root, {
                baseInterval: { timeUnit: "day", count: 1 },
                renderer: am5xy.AxisRendererX.new(root, {})
            })
        );

        var yAxis = chart.yAxes.push(
            am5xy.ValueAxis.new(root, {
                renderer: am5xy.AxisRendererY.new(root, {})
            })
        );

        // Série pour les visites
        var series = chart.series.push(
            am5xy.LineSeries.new(root, {
                name: "Visites",
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: "visits",
                valueXField: "date",
                tooltip: am5.Tooltip.new(root, {
                    labelText: "{valueY} visites le {valueX}"
                })
            })
        );

        // Série pour les visiteurs uniques
        var seriesUnique = chart.series.push(
            am5xy.LineSeries.new(root, {
                name: "Visiteurs uniques",
                xAxis: xAxis,
                yAxis: yAxis,
                valueYField: "unique",
                valueXField: "date",
                tooltip: am5.Tooltip.new(root, {
                    labelText: "{valueY} visiteurs uniques le {valueX}"
                })
            })
        );

        // Ajouter les données
        series.data.setAll(chartData);
        seriesUnique.data.setAll(chartData);

        // Légende
        var legend = chart.children.push(
            am5.Legend.new(root, {
                centerX: am5.p50,
                x: am5.p50,
                layout: root.horizontalLayout
            })
        );
        legend.data.setAll([series, seriesUnique]);

        // Animer le graphique
        series.appear(1000);
        seriesUnique.appear(1000);
    });
    </script>
</body>
</html>
HTML;

// 7. En-têtes pour l'email
$headers = "From: LibreAnalytics <contact@gael-berru.com>\r\n";
$headers .= "Reply-To: contact@gael-berru.com\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

// 8. Envoyer l'email
$mailSent = mail($user['email'], $subject, $message, $headers);

if ($mailSent) {
    echo "✅ Email envoyé avec succès à {$user['email']} ! Vérifie ta boîte de réception (et les spams).";
    file_put_contents($logDir . "/email_test.log", date('Y-m-d H:i:s') . " - Email envoyé à {$user['email']}\n", FILE_APPEND);
} else {
    echo "❌ Échec de l'envoi de l'email. Vérifie la configuration de ton serveur.";
    file_put_contents($logDir . "/email_test.log", date('Y-m-d H:i:s') . " - Échec envoi à {$user['email']}\n", FILE_APPEND);
}
