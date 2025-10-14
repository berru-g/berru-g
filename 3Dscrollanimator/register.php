<?php
// register.php
require_once 'auth.php';

if (Auth::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit faire au moins 6 caractères';
    } else {
        if (Auth::register($username, $email, $password)) {
            header('Location: index.php');
            exit;
        } else {
            $error = 'Cet email ou nom d\'utilisateur est déjà utilisé';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - 3D Scroll Animator</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--dark);">
        <div style="background: var(--grey-light); padding: 2rem; border-radius: 12px; border: 1px solid var(--border); width: 100%; max-width: 400px;">
             <img src="../img/mascotte.png" style="width:120px; height: 120px; border-radius: 50%; display: flex;margin: 0 auto;">
            <h2 style="text-align: center; color: var(--primary); margin-bottom: 2rem;">Inscription</h2>
            
            <?php if ($error): ?>
                <div style="background: var(--error); color: white; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--rose);">Nom d'utilisateur</label>
                    <input type="text" name="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" style="width: 100%; padding: 0.75rem; border-radius: 6px; border: 1px solid var(--border); background: var(--grey); color: white;">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--rose);">Email</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" style="width: 100%; padding: 0.75rem; border-radius: 6px; border: 1px solid var(--border); background: var(--grey); color: white;">
                </div>
                
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--rose);">Mot de passe</label>
                    <input type="password" name="password" required style="width: 100%; padding: 0.75rem; border-radius: 6px; border: 1px solid var(--border); background: var(--grey); color: white;">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--rose);">Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" required style="width: 100%; padding: 0.75rem; border-radius: 6px; border: 1px solid var(--border); background: var(--grey); color: white;">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Créer mon compte</button>
            </form>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="login.php" style="color: var(--primary);">Déjà un compte ? Se connecter</a>
            </div>
        </div>
    </div>
</body>
</html>