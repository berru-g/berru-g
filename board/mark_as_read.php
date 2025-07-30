<?php
session_start();
require __DIR__ . '/db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in'])) {
    die(json_encode(['success' => false, 'error' => 'Non autorisé']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'error' => 'Méthode non autorisée']));
}

try {
    $dsn = "mysql:host={$config['host']};dbname={$config['db']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['user'], $config['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $stmt = $pdo->prepare("UPDATE contacts SET is_read = 1 WHERE id = ?");
    $stmt->execute([$_POST['id']]);

    // Récupère le nouveau compte de messages non lus
    $unread = $pdo->query("SELECT COUNT(*) FROM contacts WHERE is_read = 0")->fetchColumn();

    echo json_encode([
        'success' => true,
        'unread' => $unread
    ]);

} catch (PDOException $e) {
    error_log('Erreur DB: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur de base de données'
    ]);
}