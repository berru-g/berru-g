<?php
// login.php
require_once 'config.php';

// Si déjà connecté, rediriger vers dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Validation CSRF
    if (!validateCSRFToken($csrf_token)) {
        $error = "Token de sécurité invalide.";
    } elseif (empty($email) || empty($password)) {
        $error = "Email et mot de passe requis.";
    } else {
        try {
            $pdo = getPDO();
            
            // Vérifier les tentatives de connexion (protection brute force)
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM login_attempts 
                WHERE ip_address = :ip AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            $stmt->execute([':ip' => $ip]);
            $attempts = $stmt->fetchColumn();
            
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $error = "Trop de tentatives. Réessayez dans 15 minutes.";
            } else {
                // Chercher l'utilisateur
                $stmt = $pdo->prepare("
                    SELECT id, email, password_hash, username 
                    FROM users 
                    WHERE email = :email AND is_active = 1 LIMIT 1
                ");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();
                
                if ($user && verifyPassword($password, $user['password_hash'])) {
                    // Connexion réussie
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['last_activity'] = time();
                    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
                    $_SESSION['ip_address'] = $ip;
                    
                    // Supprimer les tentatives échouées
                    $pdo->prepare("DELETE FROM login_attempts WHERE ip_address = :ip")->execute([':ip' => $ip]);
                    
                    // Redirection
                    header('Location: dashboard.php');
                    exit();
                } else {
                    // Échec de connexion - logger la tentative
                    $stmt = $pdo->prepare("
                        INSERT INTO login_attempts (ip_address, email_attempted, success) 
                        VALUES (:ip, :email, 0)
                    ");
                    $stmt->execute([':ip' => $ip, ':email' => $email]);
                    $error = "Email ou mot de passe incorrect.";
                }
            }
        } catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = "Erreur système. Veuillez réessayer.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .login-header {
            background: #4361ee;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .login-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            border: 1px solid #fcc;
        }
        
        .btn-login {
            background: #4361ee;
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-login:hover {
            background: #3f37c9;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 14px;
            color: #666;
        }
        
        .login-footer a {
            color: #4361ee;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Dashboard Analytics</h1>
            <p>Accès sécurisé</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form method="POST" class="login-form">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autocomplete="email" placeholder="admin@example.com">
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
            </div>
            
            <button type="submit" class="btn-login">Se connecter</button>
            
            <div class="login-footer">
                <p>Dashboard Analytics &copy; <?= date('Y') ?></p>
            </div>
        </form>
    </div>
</body>
</html>