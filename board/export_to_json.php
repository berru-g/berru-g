<?php
session_start();
require_once './db_config.php'; // Adaptez le chemin

header('Content-Type: application/json');

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération de toutes les données
    $stmt = $pdo->query("SELECT * FROM contacts");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatage pour l'export
    $export = [
        'metadata' => [
            'export_date' => date('c'),
            'table' => 'contacts',
            'count' => count($data)
        ],
        'data' => $data
    ];

    echo json_encode($export, JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}