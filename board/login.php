<?php
session_start();

// Charge la config
$config = require __DIR__ . '/config.php';

// Initialise le compteur de tentatives si inexistant
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
}

// Vérifie si l'utilisateur est bloqué
$block_duration = 1800; // 30 minutes en secondes
if ($_SESSION['login_attempts'] >= 3 && (time() - $_SESSION['last_attempt_time']) < $block_duration) {
    $remaining_time = $block_duration - (time() - $_SESSION['last_attempt_time']);
    $error = "Trop de tentatives. Réessayez dans ".gmdate("i\m s\s", $remaining_time);
} 
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Réinitialise le compteur si le délai est expiré
    if ((time() - $_SESSION['last_attempt_time']) > $block_duration) {
        $_SESSION['login_attempts'] = 0;
    }

    if (password_verify($_POST['password'], $config['admin_password_hash'])) {
        // Réussite : réinitialise le compteur
        $_SESSION['login_attempts'] = 0;
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php');
        exit();
    } else {
        // Échec : incrémente le compteur
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        $remaining_attempts = 3 - $_SESSION['login_attempts'];
        
        $error = "Mot de passe incorrect. ";
        $error .= ($remaining_attempts > 0) 
            ? "Il vous reste $remaining_attempts tentative(s)." 
            : "Accès bloqué pour 30 minutes.";
        
        sleep(2); // Ralentit les attaques brute-force
    }
}
?>
<!--// à cacher via la lib PHP dotenv ou à la main dans O2switch... 
// Configuration > Variables d'environnement > variable "ADMIN_PASSWORD=mdp"
// $mdp_admin = getenv('ADMIN_PASSWORD');
// + hash
// sleep(2); // Ralentit les attaques brute-force. Penser à limiter les tentatives avec session ou blocage IP après X essais-->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin</title>
    <link rel="shortcut icon" href="../logobdd.png" />
    <link rel="apple-touch-icon" href="../logobdd.png" />
    <meta name="description"
        content="Interface admin pour gérer la base de données client de son site.">
    <meta name="keywords"
        content="interface admin, outils de gestion de base de donnée, formulaire et interface administrateur,">
    <meta name="author" content="Gael Berru.">
    <meta name="robots" content="noai">

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #ab9ff2;
            --secondary: #5086eb;
            --danger: #f56545;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: #AB9FF2;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='100%25' height='100%25' viewBox='0 0 1600 800'%3E%3Cg %3E%3Cpath fill='%239c9af1' d='M486 705.8c-109.3-21.8-223.4-32.2-335.3-19.4C99.5 692.1 49 703 0 719.8V800h843.8c-115.9-33.2-230.8-68.1-347.6-92.2C492.8 707.1 489.4 706.5 486 705.8z'/%3E%3Cpath fill='%238c95ef' d='M1600 0H0v719.8c49-16.8 99.5-27.8 150.7-33.5c111.9-12.7 226-2.4 335.3 19.4c3.4 0.7 6.8 1.4 10.2 2c116.8 24 231.7 59 347.6 92.2H1600V0z'/%3E%3Cpath fill='%237a90ee' d='M478.4 581c3.2 0.8 6.4 1.7 9.5 2.5c196.2 52.5 388.7 133.5 593.5 176.6c174.2 36.6 349.5 29.2 518.6-10.2V0H0v574.9c52.3-17.6 106.5-27.7 161.1-30.9C268.4 537.4 375.7 554.2 478.4 581z'/%3E%3Cpath fill='%23678bec' d='M0 0v429.4c55.6-18.4 113.5-27.3 171.4-27.7c102.8-0.8 203.2 22.7 299.3 54.5c3 1 5.9 2 8.9 3c183.6 62 365.7 146.1 562.4 192.1c186.7 43.7 376.3 34.4 557.9-12.6V0H0z'/%3E%3Cpath fill='%235086EB' d='M181.8 259.4c98.2 6 191.9 35.2 281.3 72.1c2.8 1.1 5.5 2.3 8.3 3.4c171 71.6 342.7 158.5 531.3 207.7c198.8 51.8 403.4 40.8 597.3-14.8V0H0v283.2C59 263.6 120.6 255.7 181.8 259.4z'/%3E%3Cpath fill='%238d94ce' d='M1600 0H0v136.3c62.3-20.9 127.7-27.5 192.2-19.2c93.6 12.1 180.5 47.7 263.3 89.6c2.6 1.3 5.1 2.6 7.7 3.9c158.4 81.1 319.7 170.9 500.3 223.2c210.5 61 430.8 49 636.6-16.6V0z'/%3E%3Cpath fill='%23b2a3b1' d='M454.9 86.3C600.7 177 751.6 269.3 924.1 325c208.6 67.4 431.3 60.8 637.9-5.3c12.8-4.1 25.4-8.4 38.1-12.9V0H288.1c56 21.3 108.7 50.6 159.7 82C450.2 83.4 452.5 84.9 454.9 86.3z'/%3E%3Cpath fill='%23ceb492' d='M1600 0H498c118.1 85.8 243.5 164.5 386.8 216.2c191.8 69.2 400 74.7 595 21.1c40.8-11.2 81.1-25.2 120.3-41.7V0z'/%3E%3Cpath fill='%23e5c470' d='M1397.5 154.8c47.2-10.6 93.6-25.3 138.6-43.8c21.7-8.9 43-18.8 63.9-29.5V0H643.4c62.9 41.7 129.7 78.2 202.1 107.4C1020.4 178.1 1214.2 196.1 1397.5 154.8z'/%3E%3Cpath fill='%23FAD646' d='M1315.3 72.4c75.3-12.6 148.9-37.1 216.8-72.4h-723C966.8 71 1144.7 101 1315.3 72.4z'/%3E%3C/g%3E%3C/svg%3E");
            background-attachment: fixed;
            background-size: cover;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 2rem;
            flex-direction: column;
        }
        
        .login-container {
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        
        h1 {
            color: var(--dark);
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }
        
        .logo {
            font-size: 3rem;
            color: var(--light);
            margin-bottom: 2rem;
            display: block;
        }
        
        form {
            background: white;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            width: 100%;
        }
        
        h2 {
            color: var(--dark);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        
        input {
            width: 100%;
            padding: 12px 15px;
            margin: 0.5rem 0 1rem;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border 0.3s;
        }
        
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(67, 97, 238, 0.2);
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }
        
        button:hover {
            background: var(--secondary);
            transform: translateY(-1px);
        }
        
        .error {
            color: var(--danger);
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        
        .footer {
            margin-top: 3rem;
            text-align: right;
            width: 100%;
            max-width: 400px;
        }
        
        .footer a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color:var(--light);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: var(--primary);
        }
        
        @media (max-width: 480px) {
            form {
                padding: 1.5rem;
            }
            
            h1 {
                font-size: 1.8rem;
            }
            
            .logo {
                font-size: 2.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>Interface Admin</h1>
        <i class="fas fa-shield-alt logo"></i>
        
        <form method="POST">
            <h2>Connexion</h2>
            <input type="password" name="password" placeholder="Mot de passe admin" required />
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <button type="submit">Se connecter</button>
        </form>
    </div>
    
    <div class="footer">
        <a href="https://gael-berru.netlify.app#formulaire" rel="noopener" target="_blank">
            <span>Interface développée par berru-g</span>
            <i class="fas fa-headset"></i>
        </a>
    </div>
</body>
</html>
