<?php
/**
 * globe.php
 * Globe 3D des visiteurs LibreAnalytics.
 * À placer à côté de dashboard.php et api.php.
 */

// ============================================================
// 1. CONNEXION À LA BASE
// Adaptez le chemin selon votre structure.
// Le dashboard utilise déjà $pdo : on reprend la même inclusion.
// ============================================================
$configCandidates = [
    __DIR__ . '/../includes/config.php',
];
foreach ($configCandidates as $c) {
    if (file_exists($c)) { require_once $c; break; }
}

if (!isset($pdo)) {
    http_response_code(500);
    die('Connexion PDO non disponible. Vérifiez l\'inclusion de config.php.');
}

// ============================================================
// 2. RÉCUPÉRATION DES VISITES (même logique que le dashboard)
// ============================================================
$siteId = $_GET['site_id'] ?? null;

$sql = "SELECT city, country, page_url, timestamp
        FROM smart_pixel_tracking
        WHERE city IS NOT NULL AND city != ''
          AND country IS NOT NULL AND country != ''
        " . ($siteId ? "AND site_id = :site_id" : "") . "
        ORDER BY timestamp DESC
        LIMIT 500";

$stmt = $pdo->prepare($sql);
if ($siteId) $stmt->bindValue(':site_id', $siteId);
$stmt->execute();
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
// 3. GÉOCODAGE AVEC CACHE FICHIER
// ============================================================
$cacheFile = __DIR__ . '/geocache.json';
$cache = file_exists($cacheFile)
    ? json_decode(file_get_contents($cacheFile), true) ?: []
    : [];

// Centroïdes pays (fallback si Nominatim échoue)
$countryFallback = [
    'france' => [46.2276, 2.2137], 'united states' => [37.0902, -95.7129],
    'germany' => [51.1657, 10.4515], 'united kingdom' => [55.3781, -3.4360],
    'canada' => [56.1304, -106.3468], 'australia' => [-25.2744, 133.7751],
    'japan' => [36.2048, 138.2529], 'china' => [35.8617, 104.1954],
    'brazil' => [-14.2350, -51.9253], 'india' => [20.5937, 78.9629],
    'italy' => [41.8719, 12.5674], 'spain' => [40.4637, -3.7492],
    'netherlands' => [52.1326, 5.2913], 'belgium' => [50.5039, 4.4699],
    'switzerland' => [46.8182, 8.2275], 'portugal' => [39.3999, -8.2245],
    'poland' => [51.9194, 19.1451], 'sweden' => [60.1282, 18.6435],
    'norway' => [60.4720, 8.4689], 'denmark' => [56.2639, 9.5018],
    'ireland' => [53.4129, -8.2439], 'mexico' => [23.6345, -102.5528],
    'argentina' => [-38.4161, -63.6167], 'russia' => [61.5240, 105.3188],
    'south korea' => [35.9078, 127.7669], 'south africa' => [-30.5595, 22.9375],
    'morocco' => [31.7917, -7.0926], 'algeria' => [28.0339, 1.6596],
    'tunisia' => [33.8869, 9.5375],
];

function geocodeCity($city, $country, &$cache, $cacheFile) {
    $key = strtolower(trim($city)) . '|' . strtolower(trim($country));
    if (isset($cache[$key])) return $cache[$key];

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
            $coord = ['lat' => (float)$arr[0]['lat'], 'lng' => (float)$arr[0]['lon']];
            $cache[$key] = $coord;
            file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT));
            return $coord;
        }
    }
    return null;
}

// ============================================================
// 4. CONSTRUCTION DES POINTS
// ============================================================
$points = [];
$newGeocodes = 0;

foreach ($visits as $v) {
    $coord = null;
    $cacheKey = strtolower(trim($v['city'])) . '|' . strtolower(trim($v['country']));

    if (!isset($cache[$cacheKey])) {
        $coord = geocodeCity($v['city'], $v['country'], $cache, $cacheFile);
        if ($coord) $newGeocodes++;
    } else {
        $coord = $cache[$cacheKey];
    }

    if (!$coord) {
        $fb = $countryFallback[strtolower(trim($v['country']))] ?? null;
        if ($fb) $coord = ['lat' => $fb[0], 'lng' => $fb[1]];
    }

    if ($coord) {
        $points[] = [
            'lat'       => $coord['lat'],
            'lng'       => $coord['lng'],
            'city'      => $v['city'],
            'country'   => $v['country'],
            'page_url'  => $v['page_url'] ?? '',
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
    <title>LibreAnalytics – Globe 3D</title>
    <style>
        html, body { margin: 0; padding: 0; height: 100%; overflow: hidden; background: #000; }
        #globeViz { width: 100vw; height: 100vh; }
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
        .globe-tooltip strong { color: #4da3ff; }
        .badge {
            position: fixed; top: 16px; left: 16px; z-index: 10;
            background: rgba(8, 12, 24, 0.85);
            border: 1px solid #2a6fdb;
            border-radius: 8px;
            padding: 10px 14px;
            color: #d6e4ff;
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 13px;
            line-height: 1.5;
        }
        .badge span { color: #4da3ff; font-weight: bold; }
    </style>
</head>
<body>

<div id="globeViz"></div>
<div class="badge">
    <span><?= count($points) ?></span> visiteurs géolocalisés<br>
    <span><?= count($visits) ?></span> visites au total<br>
    <?php if ($newGeocodes > 0): ?>
        <span><?= $newGeocodes ?></span> villes nouvellement géocodées
    <?php endif; ?>
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
    .pointColor(() => '#4da3ff')
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
    .onPointHover(p => {
        document.body.style.cursor = p ? 'pointer' : 'default';
    });

globe.camera().position.z = 320;
globe.controls().autoRotate = true;
globe.controls().autoRotateSpeed = 0.45;
globe.controls().enableDamping = true;

const resize = () => globe.width(window.innerWidth).height(window.innerHeight);
window.addEventListener('resize', resize);
resize();

// Clic pour ouvrir la page visitée
globe.onPointClick(p => {
    if (p && p.page_url) window.open(p.page_url, '_blank');
});
</script>

</body>
</html>