<?php
//session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Vérifie que l'utilisateur est connecté
if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé.']);
    exit;
}

// Vérifie que site_id est fourni
if (!isset($_POST['site_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID de site manquant.']);
    exit;
}

$site_id = $_POST['site_id'];
$user_id = $_SESSION['user_id'];

// Vérifie que le site appartient bien à l'utilisateur
$pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
$stmt = $pdo->prepare("SELECT id FROM user_sites WHERE id = ? AND user_id = ?");
$stmt->execute([$site_id, $user_id]);
$site = $stmt->fetch();

if (!$site) {
    echo json_encode(['success' => false, 'message' => 'Ce site ne vous appartient pas ou n\'existe pas.']);
    exit;
}

// Supprime les données de tracking associées
try {
    $pdo->beginTransaction();

    // Supprime les données de tracking
    $stmt = $pdo->prepare("DELETE FROM smart_pixel_tracking WHERE site_id = ?");
    $stmt->execute([$site_id]);

    // Supprime le site
    $stmt = $pdo->prepare("DELETE FROM user_sites WHERE id = ? AND user_id = ?");
    $stmt->execute([$site_id, $user_id]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Site supprimé avec succès.']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression : ' . $e->getMessage()]);
}
?>
