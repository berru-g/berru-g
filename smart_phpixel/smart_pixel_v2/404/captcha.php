<?php
session_start();

// Générer un code aléatoire si pas déjà fait
if (!isset($_SESSION['captcha_code'])) {
    $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 6; $i++) { // 6 caractères
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    $_SESSION['captcha_code'] = $code;
}

// Créer l'image
$width = 120;
$height = 40;
$image = imagecreatetruecolor($width, $height);

// Couleurs
$bg_color = imagecolorallocate($image, 255, 255, 255); // Blanc
$text_color = imagecolorallocate($image, 0, 0, 0); // Noir
$noise_color = imagecolorallocate($image, 100, 100, 100); // Gris pour bruit

// Fond
imagefilledrectangle($image, 0, 0, $width, $height, $bg_color);

// Ajouter du bruit (lignes et points)
for ($i = 0; $i < 50; $i++) {
    imagesetpixel($image, rand(0, $width), rand(0, $height), $noise_color);
}
for ($i = 0; $i < 5; $i++) {
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $noise_color);
}

// Ajouter le texte (avec font par défaut, ou chargez une police TTF si disponible)
imagestring($image, 5, 10, 10, $_SESSION['captcha_code'], $text_color);

// Output l'image
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
?>