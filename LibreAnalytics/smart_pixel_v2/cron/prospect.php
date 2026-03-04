<?php
// script de prospection. 
// Envoie automatique de mail 
$recipients = [
    ['name' => 'Berru perso', 'email' => 'g.leberruyer@gmail.com'], //pour test!
    ['name' => 'Berru pro', 'email' => 'contact@gael-berru.com'] //pour test!
    /*
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
    ['name' => 'Clara D.', 'email' => 'clara@clarad.dev']*/
];

// Sujet et corps de l'email (utilise le template ci-dessus)
$subject = "Vos données analytics appartiennent à Google. Pas à vous. On change ça ?";
$body = "Bonjour [Prénom],

Je m’appelle Gaël. Je développe [LibreAnalytics](https://gael-berru.com/LibreAnalytics), un outil d’analytics 100% français, open source et RGPD, pour les PME et indés qui en ont marre de Google Analytics.

Pourquoi je vous écris ?
- Parce que votre site [nom du site] utilise probablement GA4.
- Parce que GA4 envoie vos données aux USA (et que la CNIL commence à sévir).
- Parce que vous méritez un outil simple, souverain, et sans bullshit.

**Concrètement** :
- Installation en 2 min (1 ligne de code).
- 9€/mois, pas de contrat, pas de prise de tête.
- Vos données restent en France. Point.

**Je vous offre 1 mois gratuit** pour tester (sans CB, sans engagement).
→ [Essayer LibreAnalytics maintenant](https://gael-berru.com/LibreAnalytics/smart_pixel_v2/public/index.php?plan=pro)

Pas de discours commercial, pas de pression. Juste un outil qui marche, pour des gens comme vous.

Si ça vous intéresse, répondez à cet email. Sinon, pas de souci.

Gaël
Créateur de LibreAnalytics
[contact@gael-berru.com] | [https://gael-berru.com/LibreAnalytics?utm_source=mailprospect]";

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
