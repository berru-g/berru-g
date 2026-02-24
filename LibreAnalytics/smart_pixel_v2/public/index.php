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

    // echo "DEBUG: Tentative inscription pour $email<br>";

    $userId = Auth::register($email, $password);

    // echo "DEBUG: Auth::register a retourné: " . ($userId ? "ID $userId" : "FALSE") . "<br>";
    // echo "DEBUG: Session user_id: " . ($_SESSION['user_id'] ?? 'VIDE') . "<br>";

    if ($userId) {
        // Si URL fournie, créer le site automatiquement
        if (!empty($site_url)) {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
            $tracking_code = 'SP_' . bin2hex(random_bytes(4));
            $public_key = bin2hex(random_bytes(32));
            $site_name = parse_url($site_url, PHP_URL_HOST) ?: 'Mon site';

            // echo "DEBUG: Création site pour user_id: $userId<br>";

            $stmt = $pdo->prepare("INSERT INTO user_sites (user_id, site_name, domain, tracking_code, public_key) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $site_name, $site_url, $tracking_code, $public_key]);
        }

        // echo "DEBUG: Redirection vers dashboard.php<br>";
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