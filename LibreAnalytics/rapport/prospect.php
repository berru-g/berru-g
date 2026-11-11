<?php
// prospection_dashboards.php — Envoi de mails de prospection DASHBOARD, format texte, par petites vagues
// Même maison que prospection.php / rapport_marches.php : config en haut, logs dans ../logs/,
// auto-détection du vendor, dégradation douce, debug SMTP commutable.
//
// Usage : éditer $recipients ci-dessous, puis : php prospection_dashboards.php  (ou ouverture navigateur)
// Cron déconseillé : la prospection se relance à la main, vague par vague.
//
// ⚠️ Prudence envoi : rester sous ~50-100 envois/jour au total, espacer les envois
//    (delay ci-dessous), sinon l'hébergeur/Gmail bloque le compte quelques heures.
//
// 🆕 vs prospection.php : personnalisation par destinataire ([Douleur] + [Observation] + [Secteur]),
//    historique séparé (prospection_dashboards_deja_contactes.csv) pour ne pas mélanger
//    les campagnes LibreAnalytics et Dashboard — un même prospect peut donc recevoir
//    les deux campagnes à des moments différents, sans double envoi d'une même campagne.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ============================================================
// 0. AUTOLOAD PHPMailer — chemin auto-détecté
// ============================================================
$autoloadCandidates = [
    __DIR__ . '/../../vendor/autoload.php',   // position d'origine du script
    __DIR__ . '/../../../vendor/autoload.php', // script 2 niveaux sous la racine
    __DIR__ . '/../vendor/autoload.php',       // script dans un sous-dossier du projet
    __DIR__ . '/vendor/autoload.php',          // vendor à côté du script
];
$autoloadFound = false;
foreach ($autoloadCandidates as $autoloadFile) {
    if (file_exists($autoloadFile)) {
        require_once $autoloadFile;
        $autoloadFound = true;
        break;
    }
}
if (!$autoloadFound || !class_exists(PHPMailer::class)) {
    http_response_code(500);
    die("❌ Composer autoload introuvable. Installe PHPMailer : composer require phpmailer/phpmailer\n"
      . "   (chemins testés : " . implode(' | ', $autoloadCandidates) . ")\n");
}

// ============================================================
// 1. CONFIG SMTP (Hostinger actif, Gmail en secours commenté)

$smtp = [
    'host'     => 'smtp.hostinger.com',
    'port'     => 587,                                // STARTTLS (465/SSL fonctionne aussi)
    'username' => 'contact@gael-berru.com',          // adresse complète de la boîte Hostinger
    'password' => getenv('SMTP_PASSWORD') ?: '****', // mot de passe de la boîte (pas d'app-password)
    'from'     => 'contact@gael-berru.com',
    'fromName' => 'Gaël Berru',                       // sobre : pas de marque, un humain
    'replyTo'  => 'contact@gael-berru.com',
    'debug'    => false,
];

/*
$smtp = [
    'host'     => 'smtp.gmail.com',
    'port'     => 587,
    'username' => 'g.leberruyer@gmail.com',
    'password' => getenv('SMTP_PASSWORD') ?: '****', // ⚠️ à remplacer ou définir via SMTP_PASSWORD
    'from'     => 'g.leberruyer@gmail.com',           // envoi via Gmail => From = adresse Gmail (sinon SPF/DKIM cassés => spam)
    'fromName' => 'Gaël Berru',
    'replyTo'  => 'contact@gael-berru.com',            // les réponses arrivent sur ton domaine
    'debug'    => false, // passer à true en cas de problème d'envoi
];
*/

// ============================================================
// 1bis. CONFIG CAMPAGNE DASHBOARD

$campaign = [
    'subject'   => "Et si vos données tenaient sur un seul écran ?",
    'maxPerRun' => 20,   // limite d'envois par exécution (anti-blocage SMTP)
    'delayMin'  => 20,   // pause minimale entre 2 envois, en secondes
    'delayMax'  => 45,   // pause maximale (tirage aléatoire entre min et max)
    'optOut'    => true, // pied de mail désinscription B2B (obligatoire, prospection propre)
    'demoUrl'   => 'https://gael-berru.com/demo-dashboard', // ⚠️ TODO : URL de ta démo manipulable
];

// Textes de repli si un champ perso est vide — jamais de mail avec un crochet [xxx] visible
$douleurGenerique = "Vos ventes, votre stock et votre compta vivent probablement dans "
    . "trois fichiers Excel qui ne se parlent pas — et quelqu'un les recolle à la main chaque semaine.";

// Destinataires — chaque ligne porte SA douleur et SON observation (1 ligne max, sobre, factuel)
// 'douleur'      : la phrase qui décrit LEUR problème, dans leur vocabulaire métier
// 'observation'  : ce que tu as réellement vu/enquier sur eux (crédibilité + preuve que ce n'est pas du mass-mail)
// 'secteur'      : leur métier, utilisé pour t'adresser à eux ("chez les e-commerçants…")
$recipients = [
    [
        'name'        => 'Camille',
        'email'       => 'contact@boutique-exemple.fr',
        'secteur'     => 'boutiques en ligne',
        'douleur'     => "Entre vos commandes Shopify, votre outil de stock et votre comptabilité, je parie qu'un fichier Excel fait l'intermédiaire tous les lundis.",
        'observation' => "Je suis passé par votre boutique ce matin (commande en retrait en boutique, bien vu), et je me suis permis de vous écrire.",
    ],
    [
        'name'        => 'Marc',
        'email'       => 'null',
        'secteur'     => 'PME du BTP',
        'douleur'     => "Chants, devis, heures d'équipes : quand les chiffres arrivent de 3 fichiers différents, le pilotage se fait au ressenti.",
        'observation' => "Votre site affiche vos réalisations chantier par chantier — d'où ma question un peu directe, ci-dessous.",
    ],
    // ['name' => '…', 'email' => '…', 'secteur' => '…', 'douleur' => '…', 'observation' => '…'],
];

// Corps du mail — tokens : [Prénom] [Secteur] [Douleur] [Observation] [URL_DEMO]
$body = <<<'TXT'
Bonjour [Prénom],

[Observation]

Je suis Gaël Berru, développeur indépendant à Nantes. Je construis des tableaux de bord sur mesure pour les indépendants et PME — en particulier chez les [Secteur] — qui en ont assez de piloter leur activité à coups de copier-coller.

[Douleur]

Ce que je fais, concrètement :

    - Une seule page qui réunit jusqu'à 5 sources de données (Excel, Google Sheets, API, Shopify/WooCommerce, base de données).

    - Vous choisissez les indicateurs qui comptent pour vous, avant que je commence.

    - Mise à jour automatique, quotidienne ou hebdomadaire. Hébergé en France, sans abonnement ni licence.

    - Prix fixe : 900 €. Livré en 3 semaines, documentation et 1 heure de visio de reprise en main incluses.

Pour être transparent sur les limites : pas de maintenance continue, pas de nouvelles sources après la livraison (devis séparé), et le code vous appartient.

Je ne vous demande pas de me croire sur parole — une démo manipulable est en ligne, avec des données anonymes, sans inscription :

👉 [URL_DEMO]

Si ça vous parle, deux options simples :

    1. Manipulez la démo et répondez-moi si le principe vous convient.

    2. Envoyez-moi 2 ou 3 de vos fichiers : je vous montre gratuitement à quoi ressemblerait VOTRE page.

Sans réponse de votre part, je ne relancerai pas.

Gaël Berru
gael-berru.com
TXT;

// Ligne de désinscription (B2B : obligatoire, reste sobre)
$optOutLine = "\n\n--\nVous recevez ce message dans le cadre d'une prospection B2B. "
            . "Pour ne plus être contacté, répondez simplement « STOP » à cet email.";

// ============================================================
// 2. PRÉPARATION (logs + historique des déjà-contactés — fichiers DÉDIÉS à cette campagne)
// ============================================================
$logDir = __DIR__ . '/../logs/';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile   = $logDir . 'prospection_dashboards.log';
$sentFile  = $logDir . 'prospection_dashboards_deja_contactes.csv'; // email;date — évite tout double envoi

function logLine(string $logFile, string $msg): void {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}

// Historique des adresses déjà contactées par CETTE campagne (les autres campagnes ont leur CSV)
$dejaContactes = [];
if (file_exists($sentFile)) {
    foreach (file($sentFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $parts = str_getcsv($line, ';');
        if (!empty($parts[0])) {
            $dejaContactes[strtolower(trim($parts[0]))] = true;
        }
    }
}

if ($campaign['optOut']) {
    $body .= $optOutLine;
}

// ------------------------------------------------------------
// 2bis. PERSONNALISATION — remplace les tokens, nettoie les trous
// ------------------------------------------------------------
function personnaliser(string $body, array $recipient, string $douleurGenerique, string $demoUrl): string {
    $name        = trim($recipient['name'] ?? '');
    $secteur     = trim($recipient['secteur'] ?? '');
    $douleur     = trim($recipient['douleur'] ?? '');
    $observation = trim($recipient['observation'] ?? '');

    $out = $body;

    // [Observation] vide => la ligne entière disparaît (pas de blanc disgracieux)
    if ($observation === '') {
        $out = preg_replace('/^\s*\[Observation\]\s*\n/m', '', $out);
        $observation = '';
    }
    $out = str_replace('[Observation]', $observation, $out);

    // Champs avec valeurs de repli : jamais de token visible chez le destinataire
    $out = str_replace('[Douleur]',   $douleur !== '' ? $douleur : $douleurGenerique, $out);
    $out = str_replace('[Secteur]',   $secteur !== '' ? $secteur : 'entreprises de la région', $out);
    $out = str_replace('[URL_DEMO]', $demoUrl, $out);

    // [Prénom]
    if ($name !== '') {
        $out = str_replace('[Prénom]', $name, $out);
    } else {
        $out = str_replace('Bonjour [Prénom],', 'Bonjour,', $out);
    }

    // Nettoyage : 3 sauts de ligne ou plus => 2 (l'observation supprimée laisse un trou sinon)
    $out = preg_replace("/\n{3,}/", "\n\n", $out);

    return $out;
}

// ============================================================
// 3. ENVOI — PHPMailer SMTP, connexion partagée, pauses aléatoires
// ============================================================
$sentCount  = 0;
$skipCount  = 0;
$failCount  = 0;
$restants   = 0;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host       = $smtp['host'];
$mail->SMTPAuth   = true;
$mail->Username   = $smtp['username'];
$mail->Password   = $smtp['password'];
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = $smtp['port'];
$mail->CharSet    = 'UTF-8';
$mail->Encoding   = 'quoted-printable'; // accents sûrs en texte brut
$mail->SMTPDebug  = $smtp['debug'] ? 2 : 0;
$mail->Debugoutput = function ($str, $level) { echo "$str<br>\n"; };
$mail->SMTPKeepAlive = true; // une seule connexion SMTP pour toute la vague

echo "📤 Vague de prospection DASHBOARD : " . count($recipients) . " destinataire(s) dans la liste, "
   . "plafond {$campaign['maxPerRun']} envois.<br>";

foreach ($recipients as $recipient) {
    $email = strtolower(trim($recipient['email'] ?? ''));
    $name  = trim($recipient['name'] ?? '');

    // Adresse invalide -> on ignore et on trace
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "⚠️ Adresse invalide ignorée : " . htmlspecialchars($recipient['email'] ?? '(vide)') . "<br>";
        logLine($logFile, "Adresse invalide ignorée : $email");
        continue;
    }

    // Déjà contacté lors d'une vague précédente -> on ne renvoie jamais
    if (isset($dejaContactes[$email])) {
        echo "↩️ Déjà contacté, ignoré : $email<br>";
        $skipCount++;
        continue;
    }

    // Plafond de la vague atteint -> on s'arrête proprement
    if ($sentCount >= $campaign['maxPerRun']) {
        $restants++;
        continue;
    }

    // Personnalisation de CE destinataire (tokens + valeurs de repli + nettoyage)
    $personalizedBody = personnaliser($body, $recipient, $douleurGenerique, $campaign['demoUrl']);

    try {
        $mail->clearAddresses();
        $mail->clearReplyTos();
        $mail->setFrom($smtp['from'], $smtp['fromName']);
        $mail->addAddress($email, $name);
        $mail->addReplyTo($smtp['replyTo'], 'Gaël Berru');
        $mail->isHTML(false); // format texte, comme voulu
        $mail->Subject = $campaign['subject'];
        $mail->Body    = $personalizedBody;

        $mail->send();

        $sentCount++;
        $dejaContactes[$email] = true;
        file_put_contents($sentFile, $email . ';' . date('Y-m-d H:i:s') . "\n", FILE_APPEND);
        echo "✅ Envoyé à $email<br>";
        logLine($logFile, "Envoyé à $email");
    } catch (Exception $e) {
        $failCount++;
        echo "❌ Échec pour $email : {$mail->ErrorInfo}<br>";
        logLine($logFile, "Échec pour $email : {$mail->ErrorInfo}");
    }

    // Pause aléatoire entre 2 envois (sauf après le dernier)
    if ($sentCount + $failCount < count($recipients) - $restants) {
        $pause = random_int($campaign['delayMin'], $campaign['delayMax']);
        echo "⏳ Pause {$pause}s avant le prochain envoi...<br>";
        flush();
        sleep($pause);
    }
}

$mail->smtpClose();

// ============================================================
// 4. BILAN DE LA VAGUE
// ============================================================
$bilan = "Bilan vague DASHBOARD : $sentCount envoyé(s), $failCount échec(s), $skipCount déjà contacté(s)"
       . ($restants > 0 ? ", $restants en attente (relancer le script pour la suite)" : '');
echo "<br><strong>$bilan</strong><br>";
logLine($logFile, $bilan);

if ($restants > 0) {
    echo "ℹ️ Le plafond de {$campaign['maxPerRun']} envois par exécution protège ta boîte d'envoi. "
       . "Relance le script plus tard pour envoyer la suite (les déjà-contactés sont ignorés automatiquement).<br>";
}