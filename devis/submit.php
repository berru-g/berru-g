<?php
// Désactive l'affichage des erreurs (logs cachés)
error_reporting(0);
ini_set('display_errors', 0);

// 1. Charge la config DB depuis config-secret.php
$env = parse_ini_file(__DIR__.'/config-secret.php');

if (!$env) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Erreur: Fichier config-secret.php introuvable ou invalide'
    ]);
    exit;
}

try {
    // 2. Connexion DB
    $pdo = new PDO(
        "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4",
        $env['DB_USER'],
        $env['DB_PASS'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    // 3. Récupère les données du formulaire (noms exacts de ton HTML)
    $requiredFields = ['numero', 'date', 'client_nom', 'client_email', 'total'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Le champ $field est requis");
        }
    }

    $data = [
        'numero' => $_POST['numero'],
        'date' => $_POST['date'], // Format d/m/Y comme dans ton HTML
        'client_nom' => $_POST['client_nom'],
        'client_email' => $_POST['client_email'],
        'total' => $_POST['total'],
        'developpement_vitrine' => isset($_POST['developpement_vitrine']) ? 1 : 0,
        'formulaire_simple' => isset($_POST['formulaire_simple']) ? 1 : 0,
        'formulaire_complexe' => isset($_POST['formulaire_complexe']) ? 1 : 0,
        'optimisation_seo' => isset($_POST['optimisation_seo']) ? 1 : 0,
        'systeme_paiement' => isset($_POST['systeme_paiement']) ? 1 : 0,
        'interface_admin' => isset($_POST['interface_admin']) ? 1 : 0,
        'nom_domaine' => isset($_POST['nom_domaine']) ? 1 : 0,
        'hebergement' => isset($_POST['hebergement']) ? 1 : 0
    ];

    // 4. Insertion en DB
    $stmt = $pdo->prepare("
        INSERT INTO devis (
            numero, date, client_nom, client_email, total,
            developpement_vitrine, formulaire_simple, formulaire_complexe,
            optimisation_seo, systeme_paiement, interface_admin,
            nom_domaine, hebergement, date_creation
        ) VALUES (
            :numero, :date, :client_nom, :client_email, :total,
            :developpement_vitrine, :formulaire_simple, :formulaire_complexe,
            :optimisation_seo, :systeme_paiement, :interface_admin,
            :nom_domaine, :hebergement, NOW()
        )
    ");
    $stmt->execute($data);

    // 5. Envoi de l'email
    $services = [
        'developpement_vitrine' => "Site vitrine (550 €)",
        'formulaire_simple' => "Formulaire simple (50 €)",
        'formulaire_complexe' => "Formulaire + BDD (400 €)",
        'optimisation_seo' => "Optimisation SEO (100 €)",
        'systeme_paiement' => "Système de paiement (500 €)",
        'interface_admin' => "Interface admin (100 €/an)",
        'nom_domaine' => "Nom de domaine (10 €/an)",
        'hebergement' => "Hébergement (80 €/an)"
    ];

    $message = "Nouveau devis #{$data['numero']}\n\n";
    $message .= "Client: {$data['client_nom']}\n";
    $message .= "Email: {$data['client_email']}\n";
    $message .= "Date: {$data['date']}\n\n";
    $message .= "Services:\n";

    foreach ($services as $key => $label) {
        if ($data[$key] == 1) {
            $message .= "- $label\n";
        }
    }

    $message .= "\nTotal: {$data['total']}\n";

    $headers = "From: noreply@gael-berru.com\r\n" .
               "Reply-To: {$data['client_email']}\r\n" .
               "Content-Type: text/plain; charset=utf-8";

    mail("g.leberruyer@gmail.com", "Devis #{$data['numero']}", $message, $headers);

    // 6. Réponse JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'redirect' => 'merci.html']);
    exit;

} catch (Exception $e) {
    // Gestion centralisée des erreurs (renvoie toujours du JSON)
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erreur: ' . $e->getMessage()
    ]);
    exit;
}
?>