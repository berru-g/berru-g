<?php
// login.php
require_once 'auth.php';

if (Auth::isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (Auth::login($email, $password)) {
        header('Location: index.php');
        exit;
    } else {
        $error = 'Email ou mot de passe incorrect';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - 3D Scroll Animator</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--dark);">
        <div style="background: var(--grey-light); padding: 2rem; border-radius: 12px; border: 1px solid var(--border); width: 100%; max-width: 400px;">
            <h2 style="text-align: center; color: var(--primary); margin-bottom: 2rem;">Connexion</h2>
            
            <?php if ($error): ?>
                <div style="background: var(--error); color: white; padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--rose);">Email</label>
                    <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" style="width: 100%; padding: 0.75rem; border-radius: 6px; border: 1px solid var(--border); background: var(--grey); color: white;">
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <label style="display: block; margin-bottom: 0.5rem; color: var(--rose);">Mot de passe</label>
                    <input type="password" name="password" required style="width: 100%; padding: 0.75rem; border-radius: 6px; border: 1px solid var(--border); background: var(--grey); color: white;">
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Se connecter</button>
            </form>
            
            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="register.php" style="color: var(--primary);">Créer un compte</a>
            </div>
        </div>
    </div>
</body>
</html>