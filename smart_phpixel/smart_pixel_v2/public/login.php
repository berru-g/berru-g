<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// DÉBUT DU FIX - JUSTE CES 3 LIGNES
session_start();
if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
    $alreadyLoggedIn = true;
} else {
    $alreadyLoggedIn = false;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Auth::login($_POST['email'], $_POST['password'])) {
        header('Location: dashboard.php');
        exit();
    } else {
        $error = 'Email ou mot de passe incorrect';
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Connexion - Smart Pixel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/login.css">
</head>

<body>
    <!--test de session
    <?php if ($alreadyLoggedIn): ?>
        <div style="background: #9d86ff; padding: 10px; margin: 10px 0;">
            <strong>Session Active :</strong>  <strong><?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?></strong><br>
            <a href="dashboard.php">→ Aller au Dashboard</a> |
            <a href="logout.php">→ Se déconnecter</a>
        </div>
    <?php endif; ?>
    fin de test -->
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <a href="../../index.php"><div class="login-logo">
                    <div class="login-logo-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                        </svg></div>
                    <h1>Smart Pixel</h1>
                </div></a>
                <p class="login-subtitle">solution analytics souveraine</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="email"></label>
                    <input type="email" name="email" id="email" placeholder="votre@email.com" required>
                </div>

                <div class="form-group">
                    <label for="password"></label>
                    <input type="password" name="password" id="password" placeholder="Votre mot de passe" required>
                </div>

                <button type="submit" class="login-button">
                    <span>Se connecter</span>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M5 12h14M12 5l7 7-7 7" />
                    </svg>
                </button>
            </form>

            <div class="register-link">
                <p>Pas de compte ? <a href="index.php">S'inscrire</a></p>
            </div>
        </div>
    </div>
</body>

</html>