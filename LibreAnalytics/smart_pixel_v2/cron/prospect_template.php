<?php
// script de prospection avec template html/css !
// Envoie automatique de mail 
// TROUVER DES PROSPECT QUI UTILISE GA =
// site:.fr inurl:nantes OR "loire-atlantique" intext:"Google Analytics" OR "GA4"
// site:.fr inurl:nantes OR "44" intext:"UA-" OR "G-"

$recipients = [
    ['name' => 'Berru perso', 'email' => 'g.leberruyer@gmail.com'], //pour test!
    ['name' => 'Studio Koukaki', 'email' => 'contact@koukaki.com'],
    ['name' => 'Thomas Deschamps', 'email' => 'thomas@thomas-deschamps.fr'],
    ['name' => 'La Boutique Bio de Nantes', 'email' => 'contact@laboutiquebio-nantes.fr'],
    ['name' => 'Menuiserie Le Goff', 'email' => 'contact@menuisier-legoff.fr'],
    ['name' => 'Le Blog du Hacker', 'email' => 'redac@leblogduhacker.fr'],
    ['name' => 'Aurélie Vache', 'email' => 'aurelie@vache-seo.fr'],
    ['name' => 'Pixel & Co', 'email' => 'contact@pixel-et-co.fr'],
    ['name' => 'Julien Chaumond', 'email' => 'julien@julienchaumond.com'],
    ['name' => 'La Petite Épicerie', 'email' => 'contact@lapetiteepicerie.fr'],
    ['name' => 'Marie Dubois', 'email' => 'marie@mariedubois.dev'],
    ['name' => 'RGPD Facile', 'email' => 'contact@rgpd-facile.fr'],
    ['name' => 'Web & Cie', 'email' => 'contact@web-et-cie.fr'],
    ['name' => 'Paul Martin', 'email' => 'paul@paulmartin.dev'],
    ['name' => 'Électricien Dubois', 'email' => 'contact@electricien-dubois.fr'],
    ['name' => 'Geek & Tech', 'email' => 'redac@geek-and-tech.fr'],
    ['name' => 'Sophie Lambert', 'email' => 'sophie@sophielambert-seo.fr'],
    ['name' => 'Créalys', 'email' => 'contact@crealys.fr'],
    ['name' => 'Nicolas F.', 'email' => 'nicolas@nicolasf.dev'],
    ['name' => 'Le Comptoir Végétal', 'email' => 'contact@lecomptoirvegetal.fr'],
    ['name' => 'Clara D.', 'email' => 'clara@clarad.dev']
];

// Sujet et corps de l'email (utilise le template ci-dessus)
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
        .highlight {
            background-color: #fffde7;
            padding: 10px;
            border-left: 4px solid #ffe386;
            margin: 15px 0;
            font-style: italic;
        }
        ul {
            margin: 15px 0;
            padding-left: 20px;
        }
        li {
            margin-bottom: 8px;
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

            <p>Je suis Gaël, le créateur de <a href="https://gael-berru.com/LibreAnalytics?utm_source=mailprospection4" style="color: #9d86ff; text-decoration: none;">LibreAnalytics</a>. Je m’adresse aux fondateurs et indépendants qui en ont assez d’utiliser Google Analytics sans vraiment le comprendre, et qui s’inquiètent (à juste titre) de savoir où atterrissent leurs données clients.</p>

            <div class="highlight">
                Je parie que vous êtes dans ce cas :
                <ul>
                    <li>Vous avez GA4 parce qu’"il faut bien mesurer son trafic".</li>
                    <li>Vous savez que les données partent aux USA (et la CNIL commence à taper du poing sur la table).</li>
                    <li>Mais vous n’avez pas le temps de chercher une alternative complexe ou hors de prix.</li>
                </ul>
            </div>

            <p>C’est exactement pour ça que j’ai construit LibreAnalytics.</p>

            <p><strong>L’idée est simple</strong> : un analytics souverain, simple et transparent. Vos données restent en France, hébergées chez un hébergeur de confiance. Open source, RGPD natif, et sans la complexité inutile de GA4.</p>

            <p><strong>Je ne vous demande pas de me croire, je vous propose de tester.</strong></p>

            <p>Je suis en phase de développement final et je cherche justement des bêta-testeurs éclairés comme vous. En échange de votre retour honnête, je vous offre :</p>

            <ul>
                <li><strong>Installation en 2 minutes</strong> (une seule ligne de code à placer).</li>
                <li><strong>Votre premier dashboard gratuit</strong>, complet, avec API accessible.</li>
                <li><strong>Zéro engagement, zéro CB</strong>. Juste un outil qui marche.</li>
            </ul>

            <p style="text-align: center;">
                <a href="https://gael-berru.com/LibreAnalytics?utm_source=mailprospection4" class="cta-button">Essayer LibreAnalytics gratuitement</a>
            </p>

            <p>Si vous avez besoin de plus de sites par la suite, ce sera 9€/mois. Mais pour l’instant, je veux juste que vous l’essayiez et que vous me disiez ce que vous en pensez.</p>

            <p><strong>Pas de discours marketing, pas de pression.</strong> Si ça vous plaît, tant mieux. Si ça ne vous plaît pas, dites-le-moi aussi, ça m’aide à améliorer l’outil.</p>

            <p>À vous de jouer,</p>

            <p>
                Gaël<br>
                Créateur de <a href="https://gael-berru.com/LibreAnalytics?utm_source=mailprospection4" style="color: #9d86ff; text-decoration: none;">LibreAnalytics</a><br>
                <span style="color: #777; font-size: 14px;">PS : Si vous testez le dashboard cette semaine, j’aimerais beaucoup avoir votre avis en direct. Une simple réponse à cet email suffit.</span>
            </p>
        </div>

        <div class="footer">
            <p>© 2026 LibreAnalytics – Une alternative <strong>100% française</strong>, <strong>open source</strong> et <strong>RGPD-friendly</strong>.</p>
            <p><a href="https://gael-berru.com/LibreAnalytics?utm_source=mailprospection4" style="color: #9d86ff; text-decoration: none;">Visitez notre site</a> | <a href="mailto:contact@gael-berru.com" style="color: #9d86ff; text-decoration: none;">Contactez-nous</a></p>
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

// Boucle pour envoyer les emails
foreach ($recipients as $recipient) {
    $personalizedBody = str_replace('[Prénom]', $recipient['name'], $body);
    $sent = mail($recipient['email'], $subject, $personalizedBody, $headers);
    if ($sent) {
        echo "Email envoyé à {$recipient['email']}<br>";
    } else {
        echo "Échec pour {$recipient['email']}<br>";
    }
    sleep(5); // Évite d’être blacklisté en espaçant les envois
}

// VERSIONS ALTERNATIVES : 
/*
 -- V1 : Si on sortait vos données des mains de Google ?

Bonjour [Prénom],

Je suis Gaël, le créateur de LibreAnalytics. Je m'adresse aux fondateurs et indépendants qui en ont assez d'utiliser Google Analytics sans vraiment le comprendre, et qui s'inquiètent (à juste titre) de savoir où atterrissent leurs données clients.

Je parie que vous êtes dans ce cas :

    Vous avez GA4 parce qu'"il faut bien mesurer son trafic".

    Vous savez que les données partent aux USA (et la CNIL commence à taper du poing sur la table).

    Mais vous n'avez pas le temps de chercher une alternative complexe ou hors de prix.

C'est exactement pour ça que j'ai construit LibreAnalytics.

L'idée est simple : un analytics souverain, simple et transparent. Vos données restent en France, hébergées chez un hébergeur de confiance. Open source, RGPD natif, et sans la complexité inutile de GA4.

Je ne vous demande pas de me croire, je vous propose de tester.

Je suis en phase de développement final et je cherche justement des beta-testeurs éclairés comme vous. En échange de votre retour honnête, je vous offre :

    ✅ Installation en 2 minutes (une seule ligne de code à placer).

    ✅ Votre premier dashboard gratuit. Complet, avec API accessible.

    ✅ Zéro engagement, zéro CB. Juste un outil qui marche.

→ 👉 [Cliquez ici pour installer LibreAnalytics gratuitement]

Si vous avez besoin de plus de sites par la suite, ce sera 9€/mois. Mais pour l'instant, je veux juste que vous l'essayiez et que vous me disiez ce que vous en pensez.

Pas de discours marketing, pas de pression. Si ça vous plaît, tant mieux. Si ça ne vous plaît pas ou si vous n'avez pas le temps, dites-le moi aussi, ça m'aide à améliorer l'outil.

À vous de jouer,

Gaël
Créateur de LibreAnalytics
PS : Si vous testez le dashboard cette semaine, j'aimerais beaucoup avoir votre avis en direct. Une simple réponse à cet email suffit.


 -- V2 : Vos données analytics appartiennent à Google. Pas à vous. On change ça ?

Bonjour [Prénom],

Je m’appelle Gaël. Je développe LibreAnalytics https://gael-berru.com/LibreAnalytics?utm_source=mailprospection4, un outil d’analytics 100% français, open source et RGPD, pour les PME et indés qui en ont marre de Google Analytics.

Pourquoi je vous écris ?
- Parce que votre site utilise probablement GA4.
- Parce que GA4 envoie vos données aux USA (et que la CNIL commence à sévir).
- Parce que vous méritez un outil simple, souverain, et sans bullshit.

Concrètement :
- Installation en 2 min (1 ligne de code).
- Premier site gratuit avec accées à votre dashboard et à l'api.
- Si un site ne vous suffit pas alors l'abonnement est de 9€/mois.
- Vos données restent en France. Point.

Je vous offre 1 dashboard gratuit pour tester (sans CB, sans engagement).
→ Essayer LibreAnalytics maintenant → https://gael-berru.com/LibreAnalytics?utm_source=mailprospection4?utm_source=mailprospection

Pourquoi j'offre le premier site /dashboard ?
Parcqu'il s'agit du prototype et je cherche des retours d'utilisateurs afin d'améliorer l'outils avant la mise en ligne officiel.
Essayez et faite moi un retour.

Pas de discours commercial, pas de pression. Juste un outil qui marche, pour des gens comme vous.

Si ça vous intéresse, répondez à cet email. Sinon, pas de souci.

Gaël
Créateur de LibreAnalytics
https://gael-berru.com
*/