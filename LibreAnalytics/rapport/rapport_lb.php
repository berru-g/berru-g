<?php
// Rapport hebdo LibreAnalytics par id, api, SP. berru-g 11/26
//
// Usage manuel  : runcron
// Usage auto via Cron : 0 17 * * 4 - /usr/bin/php /home/u667977963/domains/gael-berru.com/public_html/LibreAnalytics/smart_pixel_v2/campain/rapport.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ============================================================
// 0. AUTOLOAD PHPMailer — chemin auto-détecté (plus besoin de le régler à la main)
// ============================================================
$autoloadCandidates = [
    __DIR__ . '/../../vendor/autoload.php',   // position d'origine du script
    __DIR__ . '/../../../vendor/autoload.php', // script 2 niveaux sous la racine
    __DIR__ . '/../vendor/autoload.php',       // script dans un sous-dossier du projet
    __DIR__ . '/vendor/autoload.php',          // vendor à côté du script
];
$autoloadFound = false;
foreach ($autoloadCandidates as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
        $autoloadFound = true;
        break;
    }
}
if (!$autoloadFound || !class_exists(PHPMailer::class)) {
    http_response_code(500);
    die("❌ Composer autoload introuvable. Installe PHPMailer : composer require phpmailer/phpemailer\n"
        . "   (chemins testés : " . implode(' | ', $autoloadCandidates) . ")\n");
}

// ============================================================
// 1. CONFIG
// ============================================================
$user = [
    'id'      => 6,
    'email'   => 'contact@gael-berru.com',
    'api_key' => 'da4ca75b23f50634547bcd158927eb18add7d35fd2ad4ab7005e225443af1550',
    'site_id' => 'SP_7f9505cc',
];

// Config SMTP — à externaliser dans un .env en prod

$smtp = [
    'host'     => 'smtp.hostinger.com',
    'port'     => 587,                                // STARTTLS (465/SSL fonctionne aussi)
    'username' => 'contact@gael-berru.com',          // adresse complète de la boîte Hostinger
    'password' => getenv('SMTP_PASSWORD') ?: '**** **** **** ****', // mot de passe de la boîte (pas d'app-password)
    'from'     => 'contact@gael-berru.com',
    'fromName' => 'Gaël — LibreAnalytics',
    'replyTo'  => 'contact@gael-berru.com',
    'debug'    => false,
];
/*
$smtp = [
    'host'     => 'smtp.gmail.com',
    'port'     => 587,
    'username' => 'g.leberruyer@gmail.com',
    'password' => getenv('SMTP_PASSWORD') ?: '**** **** **** ****', // ⚠️ à remplacer ou définir via SMTP_PASSWORD
    'from'     => 'g.leberruyer@gmail.com',
    'fromName' => 'LibreAnalytics',
    'replyTo'  => 'contact@gael-berru.com',
    'debug'    => false, // passer à true en cas de problème d'envoi (affiche le dialogue SMTP)
];
*/
// ============================================================
// 2. PRÉPARATION (nom, logs)
// ============================================================
$emailParts = explode('@', $user['email']);
$name = $emailParts[0];

$logDir = __DIR__ . '/../logs/';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . 'rapport_libreanalytics.log';

function logLine(string $logFile, string $msg): void {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}

// Helper GET avec User-Agent
function httpGet(string $url): array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; LibreAnalyticsBot/1.0)',
    ]);
    $body    = curl_exec($ch);
    $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);
    return [(int)$code, $body, $curlErr];
}

// ============================================================
// 3. APPEL API — avec 3 tentatives (réseau/502 ponctuels)
// ============================================================
$endDate   = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-90 days'));

$apiUrl = "https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/api.php"
        . "?site_id={$user['site_id']}"
        . "&api_key={$user['api_key']}"
        . "&start_date={$startDate}"
        . "&end_date={$endDate}";

echo "⏳ Appel API (site_id={$user['site_id']}, du $startDate au $endDate)...<br>";

$httpCode = 0;
$response = false;
$curlErr  = '';
for ($attempt = 1; $attempt <= 3; $attempt++) {
    [$httpCode, $response, $curlErr] = httpGet($apiUrl);
    if ($response !== false && $httpCode === 200) {
        break;
    }
    logLine($logFile, "API tentative $attempt : HTTP $httpCode" . ($curlErr ? " ($curlErr)" : '') . ", nouvelle tentative dans 10 s");
    sleep(10);
}

if ($response === false) {
    logLine($logFile, "cURL error : $curlErr");
    die("❌ Erreur réseau cURL : $curlErr");
}

echo "Code HTTP : $httpCode<br>";

$data = json_decode((string)$response, true);
if ($httpCode !== 200 || json_last_error() !== JSON_ERROR_NONE || !$data || !isset($data['data'])) {
    logLine($logFile, "Réponse API invalide (HTTP $httpCode) : " . substr((string)$response, 0, 300));
    die("❌ Erreur API ou JSON invalide. Réponse brute : <pre>" . htmlspecialchars(substr((string)$response, 0, 500)) . "</pre>");
}

$totalVisits          = (int)($data['meta']['total_visits'] ?? 0);
$totalUniqueVisitors  = (int)($data['meta']['total_unique_visitors'] ?? 0);
echo "Stats récupérées : $totalVisits visites, $totalUniqueVisitors visiteurs uniques.<br>";

// ============================================================
// 4. PRÉPARATION DES DONNÉES POUR AMCHARTS
//    -> conversion date texte en timestamp ms (indispensable pour DateAxis)
// ============================================================
$chartData = [];
foreach ($data['data'] as $day) {
    if (empty($day['date'])) continue;
    $ts = strtotime($day['date']);
    if ($ts === false) continue;
    $chartData[] = [
        'date'   => $ts * 1000,                                              // timestamp en ms
        'visits' => (int)($day['visites'] ?? $day['visits'] ?? 0),
        'unique' => (int)($day['unique_visitors'] ?? $day['uniqueVisitors'] ?? 0),
    ];
}
$chartDataJson = json_encode($chartData, JSON_UNESCAPED_SLASHES);

// ============================================================
// 5. CONSTRUCTION DE L'EMAIL
// ============================================================
$subject = "📆 Ton rapport hebdo — $totalVisits visites, $name";

$message = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rapport hebdo</title>
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
        .button { display: inline-block; background-color: #9d86ff; color: white; text-decoration: none; padding: 10px 20px; border-radius: 8px; margin: 20px 0; }
        .footer { margin-top: 30px; text-align: center; color: #999; font-size: 14px; }
        .footer a { color: #9d86ff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">LibreAnalytics</div>
            <p>L'analytics souverain et indépendant</p>
        </div>
        <p>Salut $name,</p>
        <p>Voici ton rapport hebdo du <strong>$startDate</strong> au <strong>$endDate</strong> :</p>
        <div class="stats">
            <p>Tu as reçu <strong>$totalVisits</strong> visites,</p>
            <p>dont <strong>$totalUniqueVisitors</strong> visiteurs uniques.</p>
        </div>
        <div class="chart-container">
            <div id="chartdiv" style="width: 100%; height: 300px;"></div>
        </div>
        <p style="text-align: center;">
            <a href="https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/dashboard.php?utm_source=Rapport" class="button">Accéder à mon dashboard</a>
        </p>
        <div class="footer">
            <p>© 2026 LibreAnalytics MVP V.1.0.7 – Une alternative <strong>100% française</strong>, <strong>open source</strong> et <strong>RGPD-friendly</strong> à Google Analytics.</p>
            <p><a href="https://gael-berru.com/LibreAnalytics?utm_source=Rapport">Visite notre site</a> | <a href="https://gael-berru.com/LibreAnalytics/doc?utm_source=Rapport">Documentation</a></p>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var chartData = $chartDataJson;

        var root = am5.Root.new("chartdiv");
        root.setThemes([am5themes_Animated.new(root)]);

        var chart = root.container.children.push(
            am5xy.XYChart.new(root, {
                panX: false, panY: false,
                wheelX: "panX", wheelY: "zoomX",
                layout: root.verticalLayout
            })
        );

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

        function makeSeries(name, field, color) {
            var s = chart.series.push(
                am5xy.LineSeries.new(root, {
                    name: name, xAxis: xAxis, yAxis: yAxis,
                    valueYField: field, valueXField: "date",
                    tooltip: am5.Tooltip.new(root, {
                        labelText: "{name} : {valueY} le {valueX.formatDate('dd/MM')}"
                    })
                })
            );
            s.strokes.template.set("stroke", color);
            s.data.setAll(chartData);
            s.appear(1000);
            return s;
        }

        var seriesVisits = makeSeries("Visites", "visits", "#9d86ff");
        var seriesUnique = makeSeries("Visiteurs uniques", "unique", "#75f0a2");

        var legend = chart.children.push(
            am5.Legend.new(root, {
                centerX: am5.p50, x: am5.p50,
                layout: root.horizontalLayout
            })
        );
        legend.data.setAll([seriesVisits, seriesUnique]);
    });
    </script>
</body>
</html>
HTML;

// ============================================================
// 6. ENVOI VIA PHPMailer SMTP
// ============================================================
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $smtp['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp['username'];
    $mail->Password   = $smtp['password'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $smtp['port'];
    $mail->CharSet    = 'UTF-8';
    $mail->SMTPDebug  = $smtp['debug'] ? 2 : 0;      // debug SMTP : passer $smtp['debug'] à true si besoin
    $mail->Debugoutput = function ($str, $level) { echo "$str<br>\n"; };

    $mail->setFrom($smtp['from'], $smtp['fromName']);
    $mail->addAddress($user['email'], $name);
    $mail->addReplyTo($smtp['replyTo'], 'LibreAnalytics');

    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $message;
    $mail->AltBody = "Bonjour $name,\n\nRapport du $startDate au $endDate : "
                   . "$totalVisits visites, $totalUniqueVisitors visiteurs uniques.\n";

    $mail->send();
    echo "✅ Rapport envoyé avec succès à {$user['email']} !";
    logLine($logFile, "Rapport envoyé à {$user['email']} ($totalVisits visites, $totalUniqueVisitors uniques)");
} catch (Exception $e) {
    echo "❌ Échec de l'envoi : {$mail->ErrorInfo}";
    logLine($logFile, "Échec envoi à {$user['email']} : {$mail->ErrorInfo}");
}