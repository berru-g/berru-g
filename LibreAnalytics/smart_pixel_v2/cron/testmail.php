<?php
$to = 'g.leberruyer@gmail.com';  // Un email que tu contrôles
$subject = 'Test envoi email';
$message = 'Ceci est un test sendmail. Si ne fonctionne pas, install phpmailer!';
$headers = 'From: contact@gael-berru.com' . "\r\n";
if (mail($to, $subject, $message, $headers)) {
    echo "Email envoyé avec succès !";
} else {
    echo "Échec de l'envoi.";
}
?>