<?php
session_start();
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée');
}

$json = file_get_contents($_FILES['json_file']['tmp_name']);
$data = json_decode($json, true);

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass']);
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO contacts (fullname, email, message, ...) 
                          VALUES (:fullname, :email, :message, ...)");

    foreach ($data['data'] as $row) {
        $stmt->execute($row);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'imported' => count($data['data'])]);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}