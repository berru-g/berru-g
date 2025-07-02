<?php
// 1. Inclure le fichier de config (à placer en dehors de la racine web si possible)
require_once __DIR__.'/../includes/config.php'; // Chemin à adapter

// 2. Headers pour sécurité et format JSON
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// 3. Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Méthode non autorisée']));
}

// 4. Récupérer et valider les données
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die(json_encode(['error' => 'Données JSON invalides']));
}

// Validation minimale
$requiredFields = ['numero', 'date', 'client', 'services', 'total'];
foreach ($requiredFields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        die(json_encode(['error' => "Champ manquant: $field"]));
    }
}

// 5. Connexion sécurisée à la BDD
try {
    $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);

    // 6. Insertion en BDD avec requête préparée
    $stmt = $pdo->prepare("INSERT INTO devis 
                          (numero, date_devis, client_nom, client_email, total, details) 
                          VALUES (:numero, :date, :nom, :email, :total, :details)");
    
    $stmt->execute([
        ':numero' => htmlspecialchars($input['numero'], ENT_QUOTES),
        ':date' => htmlspecialchars($input['date'], ENT_QUOTES),
        ':nom' => htmlspecialchars($input['client']['nom'], ENT_QUOTES),
        ':email' => filter_var($input['client']['email'], FILTER_SANITIZE_EMAIL),
        ':total' => htmlspecialchars($input['total'], ENT_QUOTES),
        ':details' => json_encode($input['services']) // Sérialisation sécurisée
    ]);

    // 7. Envoi d'email sécurisé
    $to = ADMIN_EMAIL;
    $subject = "Nouveau devis de " . htmlspecialchars($input['client']['nom'], ENT_QUOTES);
    
    $message = "Nouveau devis reçu:\n\n";
    $message .= "Numéro: " . htmlspecialchars($input['numero'], ENT_QUOTES) . "\n";
    $message .= "Date: " . htmlspecialchars($input['date'], ENT_QUOTES) . "\n";
    $message .= "Client: " . htmlspecialchars($input['client']['nom'], ENT_QUOTES) . "\n";
    $message .= "Email: " . filter_var($input['client']['email'], FILTER_SANITIZE_EMAIL) . "\n\n";
    $message .= "Services:\n";
    
    foreach ($input['services'] as $service) {
        $message .= "- " . htmlspecialchars($service['label'], ENT_QUOTES) . ": " 
                  . htmlspecialchars($service['price'], ENT_QUOTES) . "€\n";
    }
    
    $message .= "\nTotal: " . htmlspecialchars($input['total'], ENT_QUOTES) . "\n";
    
    // En-têtes email sécurisés
    $headers = [
        'From' => 'noreply@' . $_SERVER['HTTP_HOST'],
        'Reply-To' => filter_var($input['client']['email'], FILTER_SANITIZE_EMAIL),
        'X-Mailer' => 'PHP/' . phpversion(),
        'Content-Type' => 'text/plain; charset=utf-8'
    ];
    
    // Construction des en-têtes
    $headersStr = '';
    foreach ($headers as $key => $value) {
        $headersStr .= "$key: $value\r\n";
    }
    
    // Envoi avec filtre anti-injection
    mail($to, $subject, $message, $headersStr);

    // 8. Réponse succès
    echo json_encode([
        'success' => true,
        'message' => 'Devis enregistré et email envoyé'
    ]);

} catch (PDOException $e) {
    // Ne pas exposer l'erreur SQL en production
    error_log("Erreur PDO: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données']);
} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Une erreur est survenue']);
}
?>