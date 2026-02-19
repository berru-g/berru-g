<?php
// public/api.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// 1. Récupérer les paramètres
$trackingCode = $_GET['tracking_code'] ?? null;
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$apiKey = $_GET['api_key'] ?? null;

// 2. Vérifier les paramètres obligatoires
if (!$trackingCode || !$apiKey) {
    http_response_code(400);
    echo json_encode(['error' => 'Les paramètres tracking_code et api_key sont requis.']);
    exit;
}

// 3. Vérifier la validité de l'API key et du tracking_code
$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
$stmt = $pdo->prepare("
    SELECT u.id as user_id, us.id as site_id
    FROM users u
    JOIN user_sites us ON u.id = us.user_id
    WHERE u.api_key = ? AND us.tracking_code = ?
");
$stmt->execute([$apiKey, $trackingCode]);
$access = $stmt->fetch();

if (!$access) {
    http_response_code(403);
    echo json_encode(['error' => 'Clé API ou code de tracking invalide.']);
    exit;
}

// 4. Récupérer les données de tracking
$stmt = $pdo->prepare("
    SELECT
        DATE(timestamp) as date,
        COUNT(*) as visits,
        COUNT(DISTINCT ip_address) as unique_visitors,
        COUNT(DISTINCT session_id) as sessions,
        COUNT(CASE WHEN source != 'direct' THEN 1 END) as referred_visits,
        COUNT(CASE WHEN campaign != '' THEN 1 END) as campaign_visits,
        JSON_OBJECTAGG(country, COUNT(*)) as countries,
        JSON_OBJECTAGG(city, COUNT(*)) as cities
    FROM smart_pixel_tracking
    WHERE site_id = :site_id
    AND DATE(timestamp) BETWEEN :start_date AND :end_date
    GROUP BY DATE(timestamp)
    ORDER BY date ASC
");
$stmt->execute([
    'site_id' => $access['site_id'],
    'start_date' => $startDate,
    'end_date' => $endDate
]);

// 5. Formater les résultats
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($results as &$row) {
    $row['countries'] = json_decode($row['countries'], true);
    $row['cities'] = json_decode($row['cities'], true);
}

// 6. Retourner les données
echo json_encode([
    'success' => true,
    'data' => $results,
    'meta' => [
        'tracking_code' => $trackingCode,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'total_visits' => array_sum(array_column($results, 'visits')),
        'total_unique_visitors' => array_sum(array_column($results, 'unique_visitors'))
    ]
]);
?>
