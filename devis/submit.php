<?php
// Chargement des variables d'environnement MAJ1221
$env = parse_ini_file(__DIR__.'/config-secret.php');

// Configuration de la base de données
$pdo = new PDO(
    "mysql:host={$env['DB_HOST']};dbname={$env['DB_NAME']};charset=utf8mb4",
    $env['DB_USER'],
    $env['DB_PASS'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]
);

header('Content-Type: application/json');

try {
    // 1. Récupération des données EXACTEMENT comme dans ton formulaire
    $data = [
        'numero' => $_POST['numero'],
        'date' => DateTime::createFromFormat('d/m/Y', $_POST['date'])->format('Y-m-d'),
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

    // 2. Insertion en base (structure inchangée)
    $stmt = $pdo->prepare("INSERT INTO devis VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute(array_values($data));

    // 3. Préparation de l'email (adapté à tes noms de champs)
    $services = [
        'developpement_vitrine' => "Développement site vitrine (550 €)",
        'formulaire_simple' => "Formulaire simple (50 €)",
        'formulaire_complexe' => "Formulaire et bdd (400 €)",
        'optimisation_seo' => "Optimisation SEO (100 €)",
        'systeme_paiement' => "Système de paiement (500 €)",
        'interface_admin' => "Interface admin (100 €/an)",
        'nom_domaine' => "Nom de domaine (10 €/an)",
        'hebergement' => "Hébergement (80 €/an)"
    ];

    $selectedServices = [];
    foreach ($services as $key => $label) {
        if ($data[$key] == 1) {
            $selectedServices[] = "- " . $label;
        }
    }

    $message = "NOUVEAU DEVIS BERRU-DEV\n\n";
    $message .= "Numéro: {$data['numero']}\n";
    $message .= "Date: {$_POST['date']}\n";
    $message .= "Client: {$data['client_nom']}\n";
    $message .= "Email: {$data['client_email']}\n\n";
    $message .= "SERVICES:\n" . implode("\n", $selectedServices) . "\n\n";
    $message .= "TOTAL: {$_POST['total']}\n";
    $message .= "\n---\nCe message a été généré automatiquement";

    // 4. Envoi de l'email (configuration Hostinger)
    $headers = "From: noreply@gael-berru.com\r\n";
    $headers .= "Reply-To: {$data['client_email']}\r\n";
    $headers .= "Content-Type: text/plain; charset=utf-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    if (!mail("g.leberruyer@gmail.com", "Devis #{$data['numero']} - {$data['client_nom']}", $message, $headers)) {
        throw new Exception("Erreur lors de l'envoi du mail");
    }

    // 5. Réponse JSON
    echo json_encode([
        'success' => true,
        'message' => 'Devis envoyé avec succès',
        'redirect' => '../index.html'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>