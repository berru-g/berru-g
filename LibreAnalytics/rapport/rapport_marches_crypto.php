<?php
// Rapport hebdo marchés (crypto + actions + flux + rendez-vous)
// mdp gmail a renouvellé https://myaccount.google.com/apppasswords
//
// Usage manuel   : php rapport_marches.php   (CLI)  ou  ouverture dans le navigateur
// Cron y: 0 15 * * 5/usr/bin/php /home/u667977963/domains/gael-berru.com/public_html/LibreAnalytics/smart_pixel_v2/campain/rapport_marches.php
//
// Sources de données :
//   - Crypto      : API publique CoinGecko (gratuite, sans clé, quota ~10-30 req/min)
//   - Actions     : endpoint chart de Yahoo Finance (non officiel — dégradation douce si bloqué)
//   - Flux actu   : RSS (Cointelegraph / CoinDesk / Cryptoast)
//   - Rendez-vous : tableau manuel, à éditer chaque semaine

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ============================================================
// 0. AUTOLOAD PHPMailer — chemin auto-détecté (plus besoin de le régler à la main)
//    (le bug d'hier soir était ici : "_DIR_." au lieu de "__DIR__ . '/'")
// ============================================================
$autoloadCandidates = [
    __DIR__ . '/../../../vendor/autoload.php', // script 2 niveaux sous la racine
    __DIR__ . '/../../vendor/autoload.php',   // script 1 niveau sous la racine
    __DIR__ . '/../vendor/autoload.php',      // script dans un sous-dossier du projet
    __DIR__ . '/vendor/autoload.php',         // vendor à côté du script
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
    die("❌ Composer autoload introuvable. Installe PHPMailer : composer require phpmailer/phpmailer\n"
        . "   (chemins testés : " . implode(' | ', $autoloadCandidates) . ")\n");
}

// ============================================================
// 1. CONFIG — tout s'édite ici
// ============================================================
$user = [
    'id'    => 6,                              // id
    'email' => 'g.leberruyer@gmail.com',        // destinataire
];

/*  
$smtp = [
    'host'     => 'smtp.gmail.com',
    'username' => 'g.leberruyer@gmail.com',
    'password' => '**** **** **** ****',
    'port'     => 587,
    'secure'   => PHPMailer::ENCRYPTION_STARTTLS,
    'debug'    => false, // passer à true en cas de problème d'envoi (affiche le dialogue SMTP)
];
*/
$smtp = [
    'host'     => 'smtp.hostinger.com',
    'username' => 'contact@gael-berru.com',      
    'password' => '**** **** **** ****', 
    'port'     => 587,                                
    'secure'   => PHPMailer::ENCRYPTION_STARTTLS,
    'debug'    => false,
];


// Cryptos suivies (clé = id CoinGecko)
$cryptos = [
    'bitcoin'  => ['label' => 'Bitcoin',  'symbol' => 'BTC', 'decimals' => 0, 'color' => '#ff86e9'],
    'ethereum' => ['label' => 'Ethereum', 'symbol' => 'ETH', 'decimals' => 0, 'color' => '#fbbf24'],
    'solana'   => ['label' => 'Solana',   'symbol' => 'SOL', 'decimals' => 2, 'color' => '#9d86ff'],
    'sui'      => ['label' => 'Sui',      'symbol' => 'SUI', 'decimals' => 4, 'color' => '#4ecdc4'],
];

// Actions suivies (ticker Yahoo Finance => nom lisible)
$stocks = [
    'PLTR' => 'Palantir Technologies',
    'ASTS' => 'AST SpaceMobile',
    'AGLT' => 'Agility Robotics',
];

// Rendez-vous de la semaine à venir — ÉDITION MANUELLE chaque semaine
$rdvBadges = ['high' => '🔴 fort impact', 'mid' => '🟠 à surveiller', 'low' => '⚪ info'];
$rendezvous = [
    ['date' => 'Lun 09 nov', 'event' => 'Résultats T3 de AST SpaceMobile (ASTS)', 'badge' => 'high'],
    ['date' => 'Dim 01 déc', 'event' => 'Unlock mensuel SUI (~13 M SUI, ~0,3 % de la supply)', 'badge' => 'mid'],
    // Exemples à rééditer : earnings PLTR (début février), FOMC, CPI, unlocks, ...
];

// Flux RSS d'actualité (le premier qui répond gagne)
$feeds = [
    'https://cointelegraph.com/rss',
    'https://www.coindesk.com/arc/outboundfeeds/rss/',
    'https://cryptoast.fr/feed/',
];

// ============================================================
// 2. Préparation (nom, logs)
// ============================================================
$emailParts = explode('@', $user['email']);
$name = $emailParts[0]; // prénom = partie avant le "@"

$logDir = __DIR__ . "/../logs/";
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . 'rapport_marches.log';
function logLine(string $logFile, string $msg): void {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}

// Helper GET avec User-Agent (Yahoo bloque les curl "nus")
function httpGet(string $url): array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (compatible; RapportMarches/1.0)',
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [(int)$code, $body];
}

$endDate   = date('Y-m-d');
$startDate = date('Y-m-d', strtotime('-7 days'));

// ============================================================
// 3. CRYPTO — un seul appel CoinGecko (prix + 7j + sparkline 7j)
//    Avec 2 tentatives (le quota gratuit renvoie parfois 429)
// ============================================================
$cgIds = implode(',', array_keys($cryptos));
$cgUrl = "https://api.coingecko.com/api/v3/coins/markets?vs_currency=usd&ids={$cgIds}&sparkline=true&price_change_percentage=7d";

echo "⏳ Appel API : $cgUrl<br>";
$cgCode = 0;
$cgBody = '';
for ($attempt = 1; $attempt <= 3; $attempt++) {
    [$cgCode, $cgBody] = httpGet($cgUrl);
    if ($cgCode === 200) {
        break;
    }
    logLine($logFile, "CoinGecko tentative $attempt : HTTP $cgCode, nouvelle tentative dans 10 s");
    sleep(10);
}
$cg = json_decode((string)$cgBody, true);
if ($cgCode !== 200 || !is_array($cg) || empty($cg)) {
    logLine($logFile, "Erreur API CoinGecko (HTTP $cgCode)");
    die("❌ Erreur API CoinGecko (HTTP $cgCode). Quota gratuit dépassé ? Réessaie dans une minute.");
}

$coins = [];
foreach ($cg as $c) {
    $id = $c['id'] ?? null;
    if (!isset($cryptos[$id])) {
        continue;
    }
    $coins[$id] = [
        'label'    => $cryptos[$id]['label'],
        'symbol'   => $cryptos[$id]['symbol'],
        'decimals' => $cryptos[$id]['decimals'],
        'color'    => $cryptos[$id]['color'],
        'price'    => (float)($c['current_price'] ?? 0),
        'chg7d'    => (float)($c['price_change_percentage_7d_in_currency'] ?? 0),
        'spark'    => $c['sparkline_in_7d']['price'] ?? [],
    ];
}

// Market cap mondiale + dominance BTC (2e appel, léger)
$globalTxt = '';
[$gCode, $gBody] = httpGet('https://api.coingecko.com/api/v3/global');
$g = json_decode((string)$gBody, true);
if ($gCode === 200 && isset($g['data'])) {
    $mcap   = (float)($g['data']['total_market_cap']['usd'] ?? 0);
    $mcapCh = (float)($g['data']['market_cap_change_percentage_24h_usd'] ?? 0);
    $btcDom = (float)($g['data']['market_cap_percentage']['btc'] ?? 0);
    $globalTxt = sprintf(
        'Cap. crypto mondiale : <strong>%s</strong> (%+.1f%% / 24h) — Dominance BTC : <strong>%.1f%%</strong>',
        $mcap >= 1e12 ? number_format($mcap / 1e12, 2, ',', ' ') . ' T$'
                      : number_format($mcap / 1e9, 0, ',', ' ') . ' Md$',
        $mcapCh,
        $btcDom
    );
}

// ============================================================
// 4. GRAPHIQUE — perf normalisée (%) sur 7 jours, 4 séries
//    Sparkline CoinGecko = 168 points horaires -> 1 point / 4h
// ============================================================
$step = 4;
$endTsMs = (int)(microtime(true) * 1000);
$chartRows = [];
foreach ($coins as $id => $c) {
    $spark = $c['spark'];
    if (count($spark) < 2) {
        continue;
    }
    $base = (float)reset($spark);
    if ($base <= 0) {
        continue;
    }
    $numPts = intdiv(count($spark) - 1, $step) + 1;
    $n = 0;
    for ($i = 0; $i < count($spark); $i += $step) {
        $ts = $endTsMs - ($numPts - 1 - $n) * $step * 3600 * 1000;
        $chartRows[$ts] = $chartRows[$ts] ?? ['t' => $ts];
        $chartRows[$ts][$id] = round(((float)$spark[$i] - $base) / $base * 100, 2);
        $n++;
    }
}
ksort($chartRows);
$chartDataJson = json_encode(array_values($chartRows), JSON_UNESCAPED_SLASHES);
$seriesConf = [];
foreach ($coins as $id => $c) {
    $seriesConf[] = ['field' => $id, 'name' => $c['symbol'], 'color' => $c['color']];
}
$seriesJson = json_encode($seriesConf, JSON_UNESCAPED_SLASHES);

// ============================================================
// 5. ACTIONS — Yahoo Finance (endpoint chart, non officiel)
// ============================================================
$stocksData = [];
foreach ($stocks as $ticker => $label) {
    [$code, $body] = httpGet("https://query1.finance.yahoo.com/v8/finance/chart/{$ticker}?range=5d&interval=1d");
    $d = json_decode((string)$body, true);
    $meta = $d['chart']['result'][0]['meta'] ?? null;
    if ($code === 200 && $meta) {
        $price = (float)($meta['regularMarketPrice'] ?? 0);
        $prev  = (float)($meta['chartPreviousClose'] ?? $meta['previousClose'] ?? 0);
        $chg   = ($prev > 0) ? round(($price / $prev - 1) * 100, 2) : null;
        $stocksData[$ticker] = ['label' => $label, 'price' => $price, 'chg5d' => $chg];
    } else {
        $stocksData[$ticker] = ['label' => $label, 'price' => null, 'chg5d' => null];
        logLine($logFile, "Yahoo indisponible pour $ticker (HTTP $code)");
    }
    usleep(300000); // on reste gentil avec Yahoo
}

// ============================================================
// 6. FLUX D'ACTUALITÉ — RSS (top 5)
// ============================================================
$news = [];
foreach ($feeds as $feed) {
    [$code, $body] = httpGet($feed);
    if ($code !== 200 || empty($body)) {
        continue;
    }
    $xml = @simplexml_load_string($body);
    if ($xml === false) {
        continue;
    }
    $items = $xml->xpath('//item') ?: [];
    $i = 0;
    foreach ($items as $item) {
        if ($i >= 5) {
            break;
        }
        $title = trim((string)($item->title ?? ''));
        $link  = trim((string)($item->link ?? ''));
        $date  = isset($item->pubDate) ? date('d/m H\hi', strtotime((string)$item->pubDate)) : '';
        if ($title !== '') {
            $news[] = ['title' => $title, 'link' => $link, 'date' => $date];
            $i++;
        }
    }
    if ($news) {
        break;
    }
}

// ============================================================
// 7. CONSTRUCTION DES BLOCS HTML
// ============================================================
function chip(?float $pct): string {
    if ($pct === null) {
        return '<span class="chip">n/d</span>';
    }
    $cls = $pct >= 0 ? 'up' : 'down';
    $sign = $pct >= 0 ? '+' : '';
    return '<span class="chip ' . $cls . '">' . $sign . number_format($pct, 2, ',', ' ') . '%</span>';
}

// Cartes crypto (grille 2x2, compatible clients mail via <table>)
$cryptoRows = '';
$cells = [];
foreach ($coins as $c) {
    $priceTxt = number_format($c['price'], $c['decimals'], ',', ' ') . ' $';
    $cells[] = '<td style="width:50%;padding:6px;">'
        . '<div class="card">'
        . '<div class="coin-name"><strong>' . $c['symbol'] . '</strong> <span class="muted">' . $c['label'] . '</span></div>'
        . '<div class="coin-price">' . $priceTxt . '</div>'
        . '<div>' . chip($c['chg7d']) . ' <span class="muted">/ 7 jours</span></div>'
        . '</div>'
        . '</td>';
}
for ($i = 0; $i < count($cells); $i += 2) {
    $cryptoRows .= '<tr>' . ($cells[$i] ?? '') . ($cells[$i + 1] ?? '<td></td>') . '</tr>';
}

// Tableau actions
$stocksRows = '';
foreach ($stocksData as $ticker => $s) {
    $priceTxt = $s['price'] !== null ? number_format($s['price'], 2, ',', ' ') . ' $' : '—';
    $stocksRows .= '<tr>'
        . '<td><strong>' . $ticker . '</strong><br><span class="muted">' . $s['label'] . '</span></td>'
        . '<td style="text-align:right;">' . $priceTxt . '</td>'
        . '<td style="text-align:right;">' . chip($s['chg5d']) . ' <span class="muted">/ ~5j</span></td>'
        . '</tr>';
}

// Flux
$newsItems = '';
if ($news) {
    foreach ($news as $n) {
        $t = htmlspecialchars($n['title'], ENT_QUOTES, 'UTF-8');
        $newsItems .= '<li><a href="' . $n['link'] . '">' . $t . '</a> <span class="muted">— ' . $n['date'] . '</span></li>';
    }
} else {
    $newsItems = '<li>Flux indisponible cette semaine.</li>';
}

// Rendez-vous
$rdvRows = '';
foreach ($rendezvous as $r) {
    $badgeKey = $r['badge'] ?? 'low';
    $badgeTxt = $rdvBadges[$badgeKey] ?? $rdvBadges['low'];
    $rdvRows .= '<tr>'
        . '<td style="white-space:nowrap;"><strong>' . $r['date'] . '</strong></td>'
        . '<td style="white-space:nowrap;">' . $badgeTxt . '</td>'
        . '<td>' . htmlspecialchars($r['event'], ENT_QUOTES, 'UTF-8') . '</td>'
        . '</tr>';
}

$btcChgTxt = sprintf('%+.1f%%', $coins['bitcoin']['chg7d'] ?? 0);
$subject = "📈 Ton récap marchés — BTC {$btcChgTxt}, $name";

// ============================================================
// 8. EMAIL HTML (style LibreAnalytics + AmCharts)
// ============================================================
$message = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rapport marchés hebdo</title>
    <!-- Intégration d'AmCharts via CDN -->
    <script src="https://cdn.amcharts.com/lib/5/index.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/xy.js"></script>
    <script src="https://cdn.amcharts.com/lib/5/themes/Animated.js"></script>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
        .container { background-color: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { color: #58a6ff; font-size: 24px; font-weight: bold; }
        h2 { font-size: 18px; color: #333; border-bottom: 2px solid #9d86ff; padding-bottom: 6px; margin-top: 28px; }
        .stats { background-color: #f5f5f5; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; }
        .card { background-color: #f5f5f5; border-radius: 8px; padding: 12px; text-align: center; }
        .coin-name { font-size: 14px; }
        .coin-price { font-size: 18px; font-weight: bold; margin: 4px 0; }
        .muted { color: #999; font-size: 12px; }
        .chip { font-weight: bold; font-size: 14px; }
        .chip.up { color: #8bfa8b; }
        .chip.down { color: #f0635a; }
        table.data { width: 100%; border-collapse: collapse; margin: 10px 0; }
        table.data td { padding: 8px 6px; border-bottom: 1px solid #eee; font-size: 14px; vertical-align: top; }
        ul.news { padding-left: 18px; font-size: 14px; line-height: 1.7; }
        ul.news a { color: #9d86ff; text-decoration: none; }
        .chart-container { width: 100%; height: 300px; margin: 20px 0; }
        .chart-note { font-size: 11px; color: #999; text-align: center; margin-top: 6px; }
        .footer { margin-top: 30px; text-align: center; color: #999; font-size: 12px; }
        .footer a { color: #9d86ff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">🧮 Rapport marchés</div>
            <p>Ton briefing hebdo — crypto, actions et flux d'actualité</p>
        </div>

        <p>Salut $name,</p>
        <p>Voici les news du <strong>$startDate</strong> au <strong>$endDate</strong> :</p>

        <div class="stats">
            <p>$globalTxt</p>
        </div>

        <h2>🪙 Marché crypto (7 jours)</h2>
        <table class="data" style="border:none;">$cryptoRows</table>
        <div class="chart-container">
            <div id="chartdiv" style="width: 100%; height: 300px;"></div>
        </div>
        <p class="chart-note">Performance normalisée des 7 derniers jours (base = prix du $startDate).
        Le graphique s'affiche en version navigateur ; les chiffres ci-dessus restent lisibles partout.</p>

        <h2>📈 Actions tech US</h2>
        <table class="data">$stocksRows</table>
        <p class="chart-note">Source : Yahoo Finance (variation sur ~5 jours de bourse).</p>

        <h2>📰 Flux d'actualité</h2>
        <ul class="news">
            $newsItems
        </ul>

        <h2>📅 Rendez-vous de la semaine prochaine</h2>
        <table class="data">$rdvRows</table>

        <div class="footer">
            <p>© 2026 Rapport Marchés V.1.0.0 — by <a href="https://gael-berru.com/?utm_source=rapport">gael-berru.com</a>.</p>
            <p>Données API : CoinGecko · Yahoo Finance · Cointelegraph / CoinDesk / Cryptoast.</p>
            <p>Ce rapport est informatif et ne constitue pas un conseil en investissement.</p>
        </div>
    </div>

    <!-- Graphique AmCharts : comparaison des performances 7 jours -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var chartData = $chartDataJson;
        var seriesConf = $seriesJson;

        var root = am5.Root.new("chartdiv");
        root.setThemes([am5themes_Animated.new(root)]);

        var chart = root.container.children.push(
            am5xy.XYChart.new(root, { layout: root.verticalLayout })
        );

        var xAxis = chart.xAxes.push(
            am5xy.DateAxis.new(root, {
                baseInterval: { timeUnit: "hour", count: 4 },
                renderer: am5xy.AxisRendererX.new(root, {})
            })
        );

        var yAxis = chart.yAxes.push(
            am5xy.ValueAxis.new(root, {
                renderer: am5xy.AxisRendererY.new(root, {}),
                numberFormat: "#.##'%'"
            })
        );

        seriesConf.forEach(function (conf) {
            var series = chart.series.push(
                am5xy.LineSeries.new(root, {
                    name: conf.name,
                    xAxis: xAxis,
                    yAxis: yAxis,
                    valueYField: conf.field,
                    valueXField: "t",
                    tooltip: am5.Tooltip.new(root, {
                        labelText: "{name} : {valueY}%"
                    })
                })
            );
            series.strokes.template.set("stroke", conf.color);
            series.data.setAll(chartData);
            series.appear(1000);
        });

        var legend = chart.children.push(
            am5.Legend.new(root, {
                centerX: am5.p50,
                x: am5.p50,
                layout: root.horizontalLayout
            })
        );
        legend.data.setAll(chart.series.values);
    });
    </script>
</body>
</html>
HTML;

// ============================================================
// 9. Envoi + logs
// ============================================================
$mail = new PHPMailer(true);

try {
    // Configuration SMTP
    $mail->isSMTP();
    $mail->Host       = $smtp['host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp['username'];
    $mail->Password   = $smtp['password'];
    $mail->SMTPSecure = $smtp['secure'];
    $mail->Port       = $smtp['port'];
    $mail->CharSet    = 'UTF-8';
    $mail->SMTPDebug  = $smtp['debug'] ? 2 : 0;      // debug SMTP : passer $smtp['debug'] à true si besoin
    $mail->Debugoutput = function ($str, $level) { echo "$str<br>\n"; };

    // Expéditeur et destinataire
    $mail->setFrom($smtp['username'], 'Rapport Marchés'); 
    $mail->addAddress($user['email'], $name);
    $mail->addReplyTo('contact@gael-berru.com', 'Gael');

    // Contenu
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $message;
    $mail->AltBody = strip_tags($message); // alternative en texte brut

    $mail->send();
    echo "✅ Rapport envoyé avec succès à {$user['email']} !";
    logLine($logFile, "Rapport envoyé à {$user['email']} (BTC {$btcChgTxt})");
} catch (Exception $e) {
    echo "❌ Échec de l'envoi : {$mail->ErrorInfo}";
    logLine($logFile, "Échec envoi rapport : {$mail->ErrorInfo}");
}