<?php
// script de prospection avec template 
// Envoie automatique de mail en format html
// TROUVER DES PROSPECT QUI UTILISE GA =
// site:.fr inurl:nantes OR "loire-atlantique" intext:"Google Analytics" OR "GA4"
// site:.fr inurl:nantes OR "44" intext:"UA-" OR "G-"

$recipients = [
    ['name' => 'Gael l', 'email' => 'g.leberruyer@gmail.com'], //via gmail important;!
    ['name' => 'l\équipe LPC', 'email' => 'contact@lpcinformatique.com'],
    ['name' => 'Séverine', 'email' => 'atelierdbdb@gmail.com'],
    ['name' => 'Benoit', 'email' => 'contact@agenciz.com'], //pour test!
    ['name' => 'l\équipe Almeria', 'email' => 'contact@almeria.fr'],
    ['name' => 'Otiwa', 'email' => 'contact@otiwia.com']
];

// Sujet et corps du mail + template
$subject = "Si on sortait vos données des mains de Google ?";
$body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibreAnalytics : Vos données vous appartiennent</title>
    <style>
        body, html {
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #9d86ff;
        }
        .content {
            font-size: 16px;
            margin-bottom: 20px;
        }
        ul {
            margin: 15px 0;
            padding-left: 20px;
        }
        li {
            margin-bottom: 8px;
        }
        img {
            margin: 20px auto;
            display: flex;
            border-radius: 12px;
            width: 400px;
            height: auto;
        }
        .cta-button {
            display: inline-block;
            background-color: #9d86ff;
            color: white !important;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            font-size: 16px;
        }
        .footer {
            text-align: center;
            font-size: 14px;
            color: #777;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
        }
        @media screen and (max-width: 600px) {
            .container {
                width: 100%;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">LibreAnalytics</div>
            <p style="color: #777; font-size: 14px;">L'analytics souverain, sans GAFAM.</p>
        </div>

        <div class="content">
            <p>Bonjour [Prénom],</p>

            <p>Je suis Gaël, le créateur de <a href="https://gael-berru.com/LibreAnalytics?utm_source=mailprospection4" style="color: #9d86ff; text-decoration: none;">LibreAnalytics</a>. Je m’adresse aux fondateurs et indépendants qui en ont assez d’utiliser Google Analytics, et qui s’inquiètent (à juste titre) de savoir où atterrissent leurs données clients.</p>

            <p>C’est exactement pour ça que j’ai construit LibreAnalytics.</p>

            <img src="https://gael-berru.com/img/demo_dashboard.gif">

            <p><strong>L’idée est simple</strong> : un analytics souverain, simple et transparent. Vos données restent en Europe et ne sont pas revendu ou exploiter de mon coté.Le projet est Open source, RGPD natif, et sans la complexité inutile de GA4.</p>

            <p><strong>Je ne vous demande pas de me croire, je vous propose de tester.</strong></p>

            <p>Je suis en phase de développement final et je cherche justement des bêta-testeurs éclairés comme vous. En échange de votre retour honnête, je vous offre :</p>

            <ul>
                <li><strong>Installation en 2 minutes</strong> (une seule ligne de code à placer).</li>
                <li><strong>Votre premier dashboard gratuit</strong> et accées à la V1 de l'api.</li>
                <li><strong>Zéro engagement, zéro CB</strong>. Juste un outil qui fonctionne.</li>
            </ul>

            <p style="text-align: center;">
                <a href="https://gael-berru.com/LibreAnalytics?utm_source=mailprospection2" class="cta-button">Essayer LibreAnalytics gratuitement</a>
            </p>

            <p>Si vous avez besoin de plus de sites par la suite, ce sera 9€/mois. Mais pour l’instant, je veux juste que vous l’essayiez et que vous me disiez ce que vous en pensez.</p>

            <p><strong>Pas de discours marketing, pas de pression.</strong> Si ça vous plaît, tant mieux. Si ça ne vous plaît pas, dites-le-moi aussi, ça m’aide à améliorer l’outil.</p>

            <p>À vous de jouer [Prénom],</p>

            <p>
                Gaël<br>
                Créateur de <a href="https://gael-berru.com/LibreAnalytics?utm_source=mailprospection2" style="color: #9d86ff; text-decoration: none;">LibreAnalytics</a><br>
                <span style="color: #777; font-size: 14px;">PS : Si vous testez le dashboard cette semaine, j’aimerais beaucoup avoir votre avis en direct. Une simple réponse à cet email suffit.</span>
            </p>
        </div>

        <div class="footer">
            <p>© 2026 LibreAnalytics – Une alternative <strong>souveraine</strong>, <strong>open source</strong> et <strong>RGPD-friendly</strong>.</p>
            <p><a href="https://gael-berru.com/LibreAnalytics?utm_source=mailprospection2" style="color: #9d86ff; text-decoration: none;">Visitez mon site</a> | <a href="mailto:contact@gael-berru.com" style="color: #9d86ff; text-decoration: none;">Contactez-moi</a></p>
        </div>
    </div>
</body>
</html>
HTML;

// En-têtes pour l'email
$headers = "From: LibreAnalytics <contact@gael-berru.com>\r\n";
$headers .= "Reply-To: contact@gael-berru.com\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";

// Boucle pour envoyer les email 
foreach ($recipients as $recipient) {
    $personalizedBody = str_replace('[Prénom]', $recipient['name'], $body);
    $sent = mail($recipient['email'], $subject, $personalizedBody, $headers);
    if ($sent) {
        echo "✅ {$recipient['email']}<br>";
    } else {
        echo "❌ {$recipient['email']}<br>";
    }
    sleep(5); // Évite d’être blacklisté en espaçant les envois
}
