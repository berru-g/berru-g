<?php
session_start();
require_once '../includes/config.php'; 
require_once '../includes/auth.php'; 
// pas encore mep
if (!Auth::isLoggedIn()) {
    // Redirige UNIQUEMENT si pas connecté
    header('Location: login.php');
    exit;
}

// Initialise les variables
$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Récupère l'utilisateur actuel
    $user_id = $_SESSION['user_id'];
    $user = get_user_by_id($user_id); // À implémenter si pas déjà fait

    // Vérifie le mot de passe actuel
    if (!password_verify($current_password, $user['password'])) {
        $error = "Le mot de passe actuel est incorrect.";
    }
    // Vérifie que les nouveaux mots de passe correspondent
    elseif ($new_password !== $confirm_password) {
        $error = "Les nouveaux mots de passe ne correspondent pas.";
    }
    // Vérifie la complexité du mot de passe
    elseif (strlen($new_password) < 8) {
        $error = "Le mot de passe doit contenir au moins 8 caractères.";
    }
    // Tout est bon, on met à jour
    else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $pdo = get_db_connection(); // Ta fonction de connexion à la DB
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);
        $success = "Votre mot de passe a été mis à jour avec succès !";
    }
}
?>

<!-- HTML du formulaire -->
<!DOCTYPE html>
<html>
<head>
    <title>Changer de mot de passe - LibreAnalytics</title>
    <link rel="stylesheet" href="../assets/login.css"> <!-- Ton CSS existant -->
</head>
<body>

    <div class="container">
        <h1>Changer de mot de passe</h1>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="current_password">Mot de passe actuel :</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-group">
                <label for="new_password">Nouveau mot de passe :</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirmer le nouveau mot de passe :</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="login-button">Mettre à jour</button>
        </form>
    </div>

</body>
</html>
