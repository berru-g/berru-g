<?php
// script de prospection. 
// Envoie automatique de mail en format text
$recipients = [
    ['name' => 'Berru perso', 'email' => 'g.leberruyer@gmail.com'], //pour test!
    ['name' => 'Studio Koukaki', 'email' => 'contact@koukaki.com']
 
];

// Sujet et corps du mail
$subject = "Si on sortait vos données des mains de Google ?";
$body = "Bonjour [Prénom],

Je suis Gaël, le créateur de LibreAnalytics. Je m'adresse aux fondateurs et indépendants qui en ont assez d'utiliser Google Analytics sans vraiment le comprendre, et qui s'inquiètent (à juste titre) de savoir où atterrissent leurs données clients.

Je parie que vous êtes dans ce cas :

    Vous avez GA4 parce qu'il faut bien mesurer son trafic.

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

Pas de discours marketing, pas de pression. Si ça vous plaît, tant mieux. Si ça ne vous plaît pas, vous aurez essayé un outils 100% souverains sans revente de données.

À vous de jouer,

Gaël
Créateur de LibreAnalytics
PS : Si vous testez le dashboard cette semaine, j'aimerais beaucoup avoir votre avis en direct. Une simple réponse à cet email suffit.";

// En-têtes pour l'email
$headers = "From: LibreAnalytics <contact@gael-berru.com>\r\n";
$headers .= "Reply-To: contact@gael-berru.com\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

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

Je m’appelle Gaël. Je développe LibreAnalytics https://gael-berru.com/LibreAnalytics, un outil d’analytics 100% français, open source et RGPD, pour les PME et indés qui en ont marre de Google Analytics.

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
→ Essayer LibreAnalytics maintenant → https://gael-berru.com/LibreAnalytics?utm_source=mailprospection

Pourquoi j'offre le premier site /dashboard ?
Parcqu'il s'agit du prototype et je cherche des retours d'utilisateurs afin d'améliorer l'outils avant la mise en ligne officiel.
Essayez et faite moi un retour.

Pas de discours commercial, pas de pression. Juste un outil qui marche, pour des gens comme vous.

Si ça vous intéresse, répondez à cet email. Sinon, pas de souci.

Gaël
Créateur de LibreAnalytics
https://gael-berru.com
*/