<?php
/**
 * globe.php
 * Globe 3D des visiteurs – utilise le tracking_code et l'api_key publics
 * pour authentifier la requête, puis lit les villes dans smart_pixel_tracking
 * exactement comme le fait dashboard.php (ligne 84).
 *
 * URL d'accès : globe.php?site_id=VOTRE_TRACKING_CODE&api_key=VOTRE_API_KEY
 */

// ============================================================
// 1. CONNEXION À LA BASE (identique à dashboard.php et api.php)
// ============================================================
$configCandidates = [
    __DIR__ . '/config.php',
    __DIR__ . '/../config.php',
    __DIR__ . '/../includes/config.php',
];
foreach ($configCandidates as $c) {
    if (file_exists($c)) {
        require_once $c;
        break;
    }
}

if (!isset($pdo)) {
    // Si $pdo n'est pas déjà défini, on le crée comme dans api.php
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    } catch (PDOException $e) {
        http_response_code(500);
        die('Erreur de connexion à la base : ' . $e->getMessage());
    }
}
// ============================================================
// 1bis. IDENTIFIANTS (à remplacer par vos valeurs)
// ============================================================
define('DEFAULT_TRACKING_CODE', 'SP_79747769'); // ex: "e5f8a2b1"
define('DEFAULT_API_KEY',       'da4ca75b23f50634547bcd158927eb18add7d35fd2ad4ab7005e225443af1550');
// ============================================================
// 2. AUTHENTIFICATION
// ============================================================
$trackingCode = $_GET['site_id'] ?? DEFAULT_TRACKING_CODE;
$apiKey       = $_GET['api_key'] ?? DEFAULT_API_KEY;

if (!$trackingCode || !$apiKey) {
    http_response_code(400);
    die('Paramètres site_id (tracking_code) et api_key requis.');
}

$stmt = $pdo->prepare("
    SELECT us.id as site_id, us.site_name, us.domain
    FROM user_sites us
    JOIN users u ON us.user_id = u.id
    WHERE us.tracking_code = ? AND u.api_key = ?
");
$stmt->execute([$trackingCode, $apiKey]);
$site = $stmt->fetch();

if (!$site) {
    http_response_code(403);
    die('Site non trouvé ou clé API invalide.');
}

$siteId = (int) $site['site_id'];

// ============================================================
// 3. RÉCUPÉRATION DES DONNÉES DÉTAILLÉES
//    Même requête que dashboard.php ligne 84, filtrée par site_id.
//    On ne garde que les lignes avec city ET country non vides.
// ============================================================
$stmt = $pdo->prepare("
    SELECT country, city, page_url, timestamp
    FROM smart_pixel_tracking
    WHERE site_id = :site_id
      AND city IS NOT NULL AND city != ''
      AND country IS NOT NULL AND country != ''
    ORDER BY timestamp DESC
    LIMIT 500
");
$stmt->execute(['site_id' => $siteId]);
$rawVisits = $stmt->fetchAll();

// ============================================================
// 4. FALLBACK PAYS → COORDONNÉES (inspiré de getCountryCodeSimple)
//    Repris tel quel depuis dashboard.php lignes 93-99.
// ============================================================
function getCountryCodeSimple($countryName)
{
    $countryMap = [
        'france' => 'FR',
        'united states' => 'US',
        'germany' => 'DE',
        'united kingdom' => 'GB',
        'canada' => 'CA',
        'australia' => 'AU',
        'japan' => 'JP',
        'china' => 'CN',
        'brazil' => 'BR',
        'india' => 'IN',
        'italy' => 'IT',
        'spain' => 'ES',
        'netherlands' => 'NL',
        'belgium' => 'BE',
        'switzerland' => 'CH',
        'portugal' => 'PT',
        'russia' => 'RU',
        'mexico' => 'MX',
        'south korea' => 'KR',
        'singapore' => 'SG',
        'usa' => 'US',
        'uk' => 'GB',
    ];
    $normalized = strtolower(trim($countryName));
    return $countryMap[$normalized] ?? null;
}

$countryCoords = [
    'FR' => [46.2276, 2.2137],
    'US' => [37.0902, -95.7129],
    'DE' => [51.1657, 10.4515],
    'GB' => [55.3781, -3.4360],
    'CA' => [56.1304, -106.3468],
    'AU' => [-25.2744, 133.7751],
    'JP' => [36.2048, 138.2529],
    'CN' => [35.8617, 104.1954],
    'BR' => [-14.2350, -51.9253],
    'IN' => [20.5937, 78.9629],
    'IT' => [41.8719, 12.5674],
    'ES' => [40.4637, -3.7492],
    'NL' => [52.1326, 5.2913],
    'BE' => [50.5039, 4.4699],
    'CH' => [46.8182, 8.2275],
    'PT' => [39.3999, -8.2245],
    'RU' => [61.5240, 105.3188],
    'MX' => [23.6345, -102.5528],
    'KR' => [35.9078, 127.7669],
    'SG' => [1.3521, 103.8198],
];

// ============================================================
// 5. GÉOCODAGE AVEC CACHE FICHIER
// ============================================================
$cacheFile = __DIR__ . '/geocache.json';
$cache = file_exists($cacheFile)
    ? json_decode(file_get_contents($cacheFile), true) ?: []
    : [];

function geocodeCity($city, $country, &$cache, $cacheFile)
{
    $key = strtolower(trim($city)) . '|' . strtolower(trim($country));
    if (isset($cache[$key]))
        return $cache[$key];

    $q = urlencode("$city, $country");
    $url = "https://nominatim.openstreetmap.org/search?format=json&limit=1&q=$q";

    $ctx = stream_context_create([
        'http' => [
            'header' => "User-Agent: LibreAnalyticsGlobe/1.0\r\n",
            'timeout' => 4,
            'ignore_errors' => true,
        ]
    ]);

    $res = @file_get_contents($url, false, $ctx);
    if ($res) {
        $arr = json_decode($res, true);
        if (!empty($arr[0]['lat']) && !empty($arr[0]['lon'])) {
            $coord = ['lat' => (float) $arr[0]['lat'], 'lng' => (float) $arr[0]['lon']];
            $cache[$key] = $coord;
            file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
            return $coord;
        }
    }
    return null;
}

// ============================================================
// 6. CONSTRUCTION DES POINTS
// ============================================================
$points = [];

foreach ($rawVisits as $v) {
    $coord = null;
    $cacheKey = strtolower(trim($v['city'])) . '|' . strtolower(trim($v['country']));

    if (isset($cache[$cacheKey])) {
        $coord = $cache[$cacheKey];
    } else {
        $coord = geocodeCity($v['city'], $v['country'], $cache, $cacheFile);
    }

    // Fallback : centre du pays si Nominatim échoue
    if (!$coord) {
        $code = getCountryCodeSimple($v['country']);
        if ($code && isset($countryCoords[$code])) {
            $coord = ['lat' => $countryCoords[$code][0], 'lng' => $countryCoords[$code][1]];
        }
    }

    if ($coord) {
        $points[] = [
            'lat' => $coord['lat'],
            'lng' => $coord['lng'],
            'city' => $v['city'],
            'country' => $v['country'],
            'page_url' => $v['page_url'] ?? '',
            'timestamp' => $v['timestamp'] ?? '',
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibreAnalytics – Globe 3D – <?= htmlspecialchars($site['site_name']) ?></title>
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            background: #000;
        }

        #globeViz {
            width: 100vw;
            height: 100vh;
        }

        .globe-tooltip {
            background: rgba(8, 12, 24, 0.92);
            border: 1px solid #2a6fdb;
            border-radius: 8px;
            padding: 10px 14px;
            color: #d6e4ff;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            line-height: 1.6;
            box-shadow: 0 0 24px rgba(42, 111, 219, 0.5);
            pointer-events: none;
        }

        .globe-tooltip strong {
            color: #4da3ff;
        }

        .badge {
            position: fixed;
            top: 16px;
            left: 16px;
            z-index: 10;
            background: rgba(8, 12, 24, 0.85);
            border: 1px solid #2a6fdb;
            border-radius: 8px;
            padding: 10px 16px;
            color: #d6e4ff;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            line-height: 1.6;
        }

        .badge span {
            color: #4da3ff;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <div id="globeViz"></div>

    <div class="badge">
        <span><?= htmlspecialchars($site['site_name']) ?></span><br>
        <?= count($points) ?> visiteurs<br>
    </div>

    <script src="//cdn.jsdelivr.net/npm/globe.gl"></script>

    <script>
        const visits = <?= json_encode($points, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;

        console.log(visits.length + ' points à afficher');

        const globe = Globe()
            .globeImageUrl('//cdn.jsdelivr.net/npm/three-globe/example/img/earth-night.jpg')
            .bumpImageUrl('//cdn.jsdelivr.net/npm/three-globe/example/img/earth-topology.png')
            .backgroundImageUrl('//cdn.jsdelivr.net/npm/three-globe/example/img/night-sky.png')
            (document.getElementById('globeViz'));

        globe
            .showAtmosphere(true)
            .atmosphereColor('#2a6fdb')
            .atmosphereAltitude(0.25);

        globe
            .pointsData(visits)
            .pointLat('lat')
            .pointLng('lng')
            .pointColor(() => '#ccc1f9')
            .pointAltitude(0.015)
            .pointRadius(0.22)
            .pointsMerge(false)
            .pointLabel(d => `
        <div class="globe-tooltip">
            <strong>${d.city}</strong><br>
            ${d.country}<br>
            <span style="opacity:0.7">${d.page_url}</span><br>
            <span style="opacity:0.5">${d.timestamp}</span>
        </div>
    `)
            .onPointHover(p => { document.body.style.cursor = p ? 'pointer' : 'default'; });

        globe.camera().position.z = 320;
        globe.controls().autoRotate = true;
        globe.controls().autoRotateSpeed = 0.45;
        globe.controls().enableDamping = true;

        const resize = () => globe.width(window.innerWidth).height(window.innerHeight);
        window.addEventListener('resize', resize);
        resize();

        globe.onPointClick(p => {
            if (p && p.page_url) window.open(p.page_url, '_blank');
        });
    </script>

</body>

</html>