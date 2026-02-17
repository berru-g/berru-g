<?php
// Includes communs
require_once __DIR__ . '/../includes/config.php';

// Démarrer la session pour CAPTCHA et autres
session_start();

// Timestamp de chargement
$load_time = time();

// Générer CAPTCHA si pas déjà (ou régénérer en cas d'erreur)
if (!isset($_SESSION['captcha_code']) || isset($error)) {
    $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 6; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    $_SESSION['captcha_code'] = $code;
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(strip_tags($_POST['name'] ?? ''));
    $email = trim(strip_tags($_POST['email'] ?? ''));
    $subject = trim(strip_tags($_POST['subject'] ?? ''));
    $message = trim(strip_tags($_POST['message'] ?? ''));
    $type = $_POST['type'] ?? 'other';
    $honeypot = $_POST['website'] ?? '';
    $submitted_load_time = intval($_POST['load_time'] ?? 0);
    $captcha_input = $_POST['captcha'] ?? '';

    // Détection bot : temps < 4 secondes
    $elapsed_time = time() - $submitted_load_time;
    if ($elapsed_time < 4) {
        header('Location: ../404/');
        exit;
    }

    if (!empty($honeypot)) {
        $error = "Requête invalide.";
    } elseif (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = "Tous les champs sont requis.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide.";
    } elseif (strtoupper($captcha_input) !== $_SESSION['captcha_code']) {
        $error = "Code CAPTCHA incorrect.";
        // Régénérer CAPTCHA pour prochaine tentative
        unset($_SESSION['captcha_code']);
    } else {
        try {
            // Insertion en DB
            $stmt = $pdo->prepare("INSERT INTO contacts (name, email, subject, message, type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message, $type]);
            $success = true;
            // Nettoyer session CAPTCHA après succès
            unset($_SESSION['captcha_code']);
        } catch (PDOException $e) {
            $error = "Erreur lors de l'envoi : " . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Smart Pixel v2</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { text-align: center; color: #007bff; }
        form { display: flex; flex-direction: column; }
        label { margin-top: 10px; font-weight: bold; }
        input, textarea, select { margin-top: 5px; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        button { margin-top: 20px; padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .message { padding: 10px; margin-bottom: 20px; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .honeypot, .load-time { display: none; }
        .links { margin-top: 20px; }
        .links a { margin-right: 15px; color: #007bff; text-decoration: none; }
        .links a:hover { text-decoration: underline; }
        .captcha-img { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Contactez-nous</h1>
        <p>Pour toute question sur Smart Pixel, un bug, une suggestion ou du support, remplissez ce formulaire. Vos données restent privées et conformes RGPD.</p>

        <?php if (isset($error)): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($success) && $success): ?>
            <div class="message success">
                Message envoyé avec succès !
                <div class="links">
                    <p>Que voulez-vous faire ensuite ?</p>
                    <a href="../../index.php">Retour à la home</a>
                    <a href="../public/dashboard.php">Accéder au dashboard</a>
                    <a href="../../doc/">Consulter la documentation</a>
                </div>
            </div>
        <?php else: ?>
            <form method="POST">
                <label for="name">Nom :</label>
                <input type="text" id="name" name="name" required>

                <label for="email">Email :</label>
                <input type="email" id="email" name="email" required>

                <label for="type">Type de requête :</label>
                <select id="type" name="type">
                    <option value="bug">Signaler un bug</option>
                    <option value="feature">Suggestion de fonctionnalité</option>
                    <option value="support">Support technique</option>
                    <option value="other">Autre</option>
                </select>

                <label for="subject">Sujet :</label>
                <input type="text" id="subject" name="subject" required>

                <label for="message">Message :</label>
                <textarea id="message" name="message" rows="5" required></textarea>

                <!-- CAPTCHA maison -->
                <label for="captcha">Vérification (recopiez le code) :</label>
                <img src="captcha.php" alt="CAPTCHA" class="captcha-img">
                <input type="text" id="captcha" name="captcha" required>

                <!-- Honeypot anti-spam -->
                <div class="honeypot">
                    <label for="website">Site web (ne pas remplir) :</label>
                    <input type="text" id="website" name="website">
                </div>

                <!-- Timestamp pour détection bot -->
                <input type="hidden" name="load_time" value="<?= $load_time ?>">

                <button type="submit">Envoyer</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>