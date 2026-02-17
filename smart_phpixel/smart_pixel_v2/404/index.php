<?php
// 404.php
http_response_code(404);  // Vrai 404 pour les humains + SEO + bons bots
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petit incident · Smart Pixel v2</title>
    
    <!-- Lien vers ton CSS existant -->
    <link rel="stylesheet" href="../assets/dashboard.css">
    
    <!-- Si tu as Inter via Google Fonts ou local, ajoute ici -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Styles spécifiques 404 – on reste dans l'univers dashboard */
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
        }
        
        .error-content {
            max-width: 580px;
            background: var(--bg-color);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 2.5rem 2rem;
            box-shadow: var(--shadow);
        }
        
        .error-icon {
            font-size: 5.5rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            opacity: 0.85;
        }
        
        h1 {
            font-size: 2.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-color);
        }
        
        .subtitle {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 2.5rem;
            line-height: 1.5;
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            justify-content: center;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
            padding: 0.9rem 1.8rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        
        .btn-primary:hover {
            background: #8a74e8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(157, 134, 255, 0.25);
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--primary-color);
            color: var(--primary-color);
            padding: 0.9rem 1.8rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .btn-outline:hover {
            background: var(--primary-light);
        }
        
        @media (max-width: 640px) {
            .action-buttons {
                flex-direction: column;
            }
            .btn-primary, .btn-outline {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <div class="error-page">
        <div class="error-content">
            <div class="error-icon">⚙️</div>
            
            <h1>Petit glitch de navigation</h1>
            
            <p class="subtitle">
                Il semble qu'un petit décalage ait eu lieu dans le flux de données.<br>
                Rien de grave – nos pixels sont toujours bien alignés, promis.
            </p>
            
            <div class="action-buttons">
                <a href="../public/dashboard.php" class="btn-primary">Retour à l'accueil</a>
                <a href="../public/dashboard.php" class="btn-primary">Accéder au dashboard</a>
                <a href="../../doc/" class="btn-outline">Consulter la documentation</a>
            </div>
            
            <p style="margin-top: 2.5rem; font-size: 0.95rem; color: var(--text-secondary);">
                Si le problème persiste, n’hésite pas à nous contacter via le formulaire.<br>
                <small>(code interne : SP-404-<?= date('ymd') ?>)</small>
            </p>
        </div>
    </div>

</body>
</html>