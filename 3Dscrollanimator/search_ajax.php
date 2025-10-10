<?php
// search_ajax.php - Pour les suggestions en temps réel
header('Content-Type: application/json');

if (isset($_GET['term']) && !empty($_GET['term'])) {
    $term = $_GET['term'] . '%';
    
    $stmt = $pdo->prepare("
        SELECT id, name 
        FROM products 
        WHERE name LIKE ? 
        LIMIT 5
    ");
    $stmt->execute([$term]);
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($suggestions);
    exit;
}
?>