<?php
// Désactive l'affichage des erreurs (mais les loggue)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__.'/php_errors.log');

// 1. Charge la config
$env = parse_ini_file(__DIR__.'/config-secret.php');
if (!$env) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Config manquante']);
    exit;
}

try {
    // 2. Connexion DB
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4",
        $env['DB_USER'],
        $env['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 3. Prépare les données (adapté à ta structure DB)
    $data = [
        'numero' => $_POST['numero'],
        'date_devis' => DateTime::createFromFormat('d/m/Y', $_POST['date'])->format('Y-m-d'),
        'client_nom' => $_POST['client_nom'],
        'client_email' => $_POST['client_email'],
        'total' => (float)str_replace(['€', ' '], '', $_POST['total']),
        'developpement_vitrine' => isset($_POST['developpement_vitrine']) ? 1 : 0,
        'formulaire_simple' => isset($_POST['formulaire_simple']) ? 1 : 0,
        'formulaire_complexe' => isset($_POST['formulaire_complexe']) ? 1 : 0,
        'optimisation_seo' => isset($_POST['optimisation_seo']) ? 1 : 0,
        'systeme_paiement' => isset($_POST['systeme_paiement']) ? 1 : 0,
        'interface_admin' => isset($_POST['interface_admin']) ? 1 : 0,
        'nom_domaine' => isset($_POST['nom_domaine']) ? 1 : 0,
        'hebergement' => isset($_POST['hebergement']) ? 1 : 0
    ];

    // 4. Requête SQL EXACTEMENT adaptée à ta table
    $sql = "INSERT INTO devis (
        numero, date_devis, client_nom, client_email, total,
        developpement_vitrine, formulaire_simple, formulaire_complexe,
        optimisation_seo, systeme_paiement, interface_admin,
        nom_domaine, hebergement
    ) VALUES (
        :numero, :date_devis, :client_nom, :client_email, :total,
        :developpement_vitrine, :formulaire_simple, :formulaire_complexe,
        :optimisation_seo, :systeme_paiement, :interface_admin,
        :nom_domaine, :hebergement
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($data);

    // 5. Envoi d'email (version simplifiée)
    $message = "Nouveau devis #{$data['numero']}\n\n";
    $message .= "Client: {$data['client_nom']}\n";
    $message .= "Email: {$data['client_email']}\n";
    $message .= "Total: {$_POST['total']}\n\n";
    
    $services = [
        'developpement_vitrine' => "Site vitrine",
        'formulaire_simple' => "Formulaire simple",
        'formulaire_complexe' => "Formulaire complexe",
        'optimisation_seo' => "Optimisation SEO",
        'systeme_paiement' => "Système de paiement",
        'interface_admin' => "Interface admin",
        'nom_domaine' => "Nom de domaine",
        'hebergement' => "Hébergement"
    ];
    
    foreach ($services as $key => $label) {
        if ($data[$key]) $message .= "- $label\n";
    }

    $headers = "From: noreply@gael-berru.com\r\n" .
               "Reply-To: {$data['client_email']}\r\n" .
               "Content-Type: text/plain; charset=utf-8";

    mail("g.leberruyer@gmail.com", "Devis #{$data['numero']}", $message, $headers);

    // 6. Réponse JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => '../index.html']);

} catch (PDOException $e) {
    error_log("Erreur DB: ".$e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur base de données']);
} catch (Exception $e) {
    error_log("Erreur générale: ".$e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur du serveur']);
}
?>