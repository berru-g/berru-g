<?php
// prospection.php — Envoi de mails de prospection en format texte, par petites vagues
// Même maison que rapport.php / rapport_marches.php : config en haut, logs dans ../logs/,
// auto-détection du vendor, dégradation douce, debug SMTP commutable.
//
// Usage : éditer $recipients ci-dessous, puis : php prospection.php  (ou ouverture navigateur)
// Cron déconseillé : la prospection se relance à la main, vague par vague.
//
// ⚠️ Prudence Gmail (compte gratuit) : rester sous ~50-100 envois/jour au total,
//    espacer les envois (delay ci-dessous), sinon Gmail bloque le compte quelques heures.

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
// 1. CONFIG GMAIL OU HOSTINGER
/*
$smtp = [
    'host'     => 'smtp.hostinger.com',
    'port'     => 587,                                // STARTTLS (465/SSL fonctionne aussi)
    'username' => 'contact@gael-berru.com',          // adresse complète de la boîte Hostinger
    'password' => getenv('SMTP_PASSWORD') ?: '****', // mot de passe de la boîte (pas d'app-password)
    'from'     => 'contact@gael-berru.com',
    'fromName' => 'Gaël — LibreAnalytics',
    'replyTo'  => 'contact@gael-berru.com',
    'debug'    => false,
];
*/
// ============================================================
$smtp = [
    'host'     => 'smtp.gmail.com',
    'port'     => 587,
    'username' => 'g.leberruyer@gmail.com',
    'password' => getenv('SMTP_PASSWORD') ?: '****', // ⚠️ à remplacer ou définir via SMTP_PASSWORD
    'from'     => 'g.leberruyer@gmail.com',           // envoi via Gmail => From = adresse Gmail (sinon SPF/DKIM cassés => spam)
    'fromName' => 'Gaël — LibreAnalytics',
    'replyTo'  => 'contact@gael-berru.com',           // les réponses arrivent sur ton domaine
    'debug'    => false, // passer à true en cas de problème d'envoi
];

$campaign = [
    'subject'   => "Si on sortait vos données des mains de Google ?",
    'maxPerRun' => 20,   // limite d'envois par exécution (anti-blocage Gmail)
    'delayMin'  => 20,   // pause minimale entre 2 envois, en secondes
    'delayMax'  => 45,   // pause maximale (tirage aléatoire entre min et max)
    'optOut'    => true, // ajoute une ligne de désinscription en pied de mail (proso B2B propre)
];

// Destinataires — via Gmail important : vérifier chaque adresse avant d'ajouter
$recipients = [
    ['name' => 'prospect 1', 'email' => 'exemple_1@gmail.com'],
    ['name' => 'prospect 2', 'email' => 'contact@deux.ex'],
    // ['name' => '...', 'email' => '...'],
];

// Corps du mail — [Prénom] est remplacé par $recipient['name'] pour chacun
$body = <<<'TXT'
Bonjour [Prénom],

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
PS : Si vous testez le dashboard cette semaine, j'aimerais beaucoup avoir votre avis en direct. Une simple réponse à cet email suffit.
TXT;

// Ligne de désinscription (optionnelle, encodée en dur ici pour rester sobre)
$optOutLine = "\n\n--\nVous recevez ce message dans le cadre d'une prospection B2B. "
            . "Pour ne plus être contacté, répondez simplement « STOP » à cet email.";

// ============================================================
// 2. PRÉPARATION (logs + historique des déjà-contactés)
// ============================================================
$logDir = __DIR__ . '/../logs/';
if (!file_exists($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile   = $logDir . 'prospection.log';
$sentFile  = $logDir . 'prospection_deja_contactes.csv'; // email;date — évite tout double envoi

function logLine(string $logFile, string $msg): void {
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - $msg\n", FILE_APPEND);
}

// Historique des adresses déjà contactées (quel que soit le run)
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

echo "📤 Vague de prospection : " . count($recipients) . " destinataire(s) dans la liste, "
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

    // Personnalisation ([Prénom] manquant => "Bonjour" seul)
    $personalizedBody = str_replace('[Prénom]', $name, $body);
    if ($name === '') {
        $personalizedBody = str_replace('Bonjour ,', 'Bonjour,', $personalizedBody);
    }

    try {
        $mail->clearAddresses();
        $mail->clearReplyTos();
        $mail->setFrom($smtp['from'], $smtp['fromName']);
        $mail->addAddress($email, $name);
        $mail->addReplyTo($smtp['replyTo'], 'LibreAnalytics');
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
$bilan = "Bilan vague : $sentCount envoyé(s), $failCount échec(s), $skipCount déjà contacté(s)"
       . ($restants > 0 ? ", $restants en attente (relancer le script pour la suite)" : '');
echo "<br><strong>$bilan</strong><br>";
logLine($logFile, $bilan);

if ($restants > 0) {
    echo "ℹ️ Le plafond de {$campaign['maxPerRun']} envois par exécution a protégé ton compte Gmail. "
       . "Relance le script plus tard pour envoyer la suite (les déjà-contactés sont ignorés automatiquement).<br>";
}