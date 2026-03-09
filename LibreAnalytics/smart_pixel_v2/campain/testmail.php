<?php
$to = 'g.leberruyer@gmail.com'; 
$subject = '🟦 ENDPOINT';
$message = 'Accees aux curieux. Visite ton dashboard !';
$headers = 'From: contact@gael-berru.com' . "\r\n";
if (mail($to, $subject, $message, $headers)) {
    echo "✅";
} else {
    echo "🫡";
}
?>