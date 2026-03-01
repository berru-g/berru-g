<?php
// public/index.php
require_once  '../includes/auth.php';
error_reporting(E_ALL);

if (Auth::isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $site_url = $_POST['site_url'] ?? '';

    $userId = Auth::register($email, $password);

    if ($userId) {
        // Si URL fournie, créer le site automatiquement
        if (!empty($site_url)) {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $tracking_code = 'SP_' . bin2hex(random_bytes(4));
            $public_key = bin2hex(random_bytes(32));
            $site_name = parse_url($site_url, PHP_URL_HOST) ?: 'Mon site';

            $stmt = $pdo->prepare("INSERT INTO user_sites (user_id, site_name, domain, tracking_code, public_key) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $site_name, $site_url, $tracking_code, $public_key]);

            // Initialisation des champs de tracking pour les emails différés
            //$pdo->exec("UPDATE users SET email_sent_7d = FALSE, email_sent_14d = FALSE WHERE id = $userId");
        }

        // Envoi de l'email de bienvenue (immédiat)
        // Envoi de l'email de bienvenue (immédiat)
        $to = $email;
        // Récupère la partie avant le "@" pour afficher un prénom personnalisé
        $emailParts = explode('@', $email);
        $pseudoPrenom = $emailParts[0]; // Prend la partie avant le "@"
        $pseudoPrenom = ucfirst($pseudoPrenom); // Met la première lettre en majuscule pour un rendu plus naturel
        $subject = "Bienvenue sur LibreAnalytics !";
        $message = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenue sur LibreAnalytics</title>
    <style>
        body {
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .container {
            background-color: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #eaeaea;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            color: #9d86ff;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
        }
        .tagline {
            color: #666;
            font-size: 16px;
        }
        .content {
            margin: 20px 0;
        }
        .highlight {
            color: #9d86ff;
            font-weight: 600;
        }
        .code-block {
            background-color: #f5f5f5;
            border: 1px solid #eaeaea;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            overflow-x: auto;
        }
        .button {
            display: inline-block;
            background-color: #9d86ff;
            color: #fff !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 4px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            color: #999;
            font-size: 14px;
        }
        .footer a {
            color: #9d86ff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">LibreAnalytics</div>
            <div class="tagline">Vous êtes désormais propriétaire de vos données.</div>
        </div>

        <div class="content">
            <p>Bonjour <strong><?php echo htmlspecialchars($pseudoPrenom); ?></strong></p>

            <p>Merci d’avoir rejoint <span class="highlight">LibreAnalytics</span> ! 🎉</p>

            <p>Tu es désormais <strong>responsable et propriétaire des données</strong> de ton site. Personne ne les exploite à part toi.</p>

            <p>Voici ton <strong>code de tracking</strong> à installer sur ton site :</p>

            <div class="code-block">
                &lt;script data-sp-id=&quot;$tracking_code&quot; src=&quot;https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/tracker.js&quot; async&gt;&lt;/script&gt;
            </div>

            <p>Une fois installé, tu pourras suivre ton trafic en temps réel depuis ton tableau de bord.</p>

            <p style="text-align: center;">
                <a href="https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/dashboard.php" class="button">Accéder à mon dashboard</a>
            </p>

            <p>Besoin d’aide pour l’installation ou des questions ? <strong>Réponds simplement à cet email</strong>, je suis là pour t’aider !</p>
            <p>Gael créateur de LibreAnalytics et de son Smart Pixel.</p>
        </div>

        <div class="footer">
            <p>© 2026 LibreAnalytics – Une alternative <strong>100% française</strong>, <strong>open source</strong> et <strong>RGPD-friendly</strong> à Google Analytics.</p>
            <p><a href="https://gael-berru.com">Visite notre site</a> | <a href="https://gael-berru.com/LibreAnalytics/doc/">Documentation</a></p>
        </div>
    </div>
</body>
</html>
HTML;

        // En-têtes pour l'email HTML
        //$headers = "From: L'équipe LibreAnalytics <contact@gael-berru.com>\r\n";
        $headers = "From: contact@gael-berru.com\r\n";
        $headers .= "Reply-To: contact@gael-berru.com\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        // Envoi de l'email
        $mailSent = mail($to, $subject, $message, $headers);

        // Optionnel : Log pour vérifier si l'email a été envoyé
        if (!$mailSent) {
            error_log("Échec de l'envoi de l'email de bienvenue à $email");
        }


        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Cet email existe déjà';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibreAnalytics - Inscription</title>
    <link rel="stylesheet" href="../assets/login.css">
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <h2>LibreAnalytics</h2>
                <p class="login-subtitle">Devenez propriétaire de vos données.</p>
            </div>
            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Mot de passe" required>
                    <input type="url" name="site_url" placeholder="URL de votre site" required>
                    <button type="submit" class="login-button">Créer mon compte gratuit</button>
                </div>
            </form>
            <div class="register-link">
                <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
            </div>
        </div>
    </div>
</body>

</html>