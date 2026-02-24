<?php
// Exemple : pages.php (ou /deep/trap.php, etc.)
// C'est la page que tu sers quand tu détectes un bot probable

// PAS de 404 ici !
http_response_code(200);           // ← important
header('Content-Type: text/html; charset=utf-8');

// Optionnel : un petit hint pour les humains qui tombent dessus par erreur
header('X-Bot-Trap: yes');         // pour tes logs/debug

session_start(); // si tu veux tracker le depth ou autre

$depth = isset($_GET['d']) ? max(0, (int)$_GET['d']) : 0;
$seed  = bin2hex(random_bytes(4)); // pour varier les URLs

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ressource profonde #<?= $depth + 1 ?></title>
    <meta name="robots" content="noindex, nofollow">           <!-- évite l'indexation Google -->
    <style>
        body { font-family: system-ui, sans-serif; max-width: 900px; margin: 3rem auto; line-height: 1.6; }
        h1 { color: #444; }
        ul { columns: 3; }
        .hint { font-size: 0.9em; color: #777; margin-top: 4rem; }
    </style>
</head>
<body>
    <h1>Section avancée – Niveau <?= $depth + 1 ?></h1>
    <p>Cette page fait partie de la documentation étendue de LibreAnalytics v2.</p>
    <p>Liens vers les sous-sections :</p>

    <div class="hint">
        <?php if ($depth > 4): ?>
            <p style="color:#e74c3c;">Vous semblez explorer très profondément… êtes-vous sûr que c'est utile ?</p>
        <?php endif; ?>
        <small>Si vous n'êtes pas un robot d'indexation, <a href="../../index.php?utm_source=deep_link_4bot">retournez à l'accueil</a>.</small>
    </div>

    <ul>
        <?php for ($i = 0; $i < 30; $i++): ?>   <!-- 30 liens = bots voraces adorent -->
            <li>
                <a href="?d=<?= $depth + 1 ?>&s=<?= $seed . $i ?>">
                    Documentation partie <?= $depth * 30 + $i + 1 ?> – Paramètres avancés
                </a>
            </li>
        <?php endfor; ?>
    </ul>

    
</body>
</html>