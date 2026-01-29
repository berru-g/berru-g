<?php
// repair_passwords.php - À placer à la racine
require_once '../includes/config.php';

echo "<h3>Réparation des mots de passe</h3>";

$pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);

// 1. Voir tous les utilisateurs
$stmt = $pdo->query("SELECT id, email, password_hash FROM users");
$users = $stmt->fetchAll();

foreach ($users as $user) {
    echo "<p>Utilisateur #{$user['id']} : {$user['email']}</p>";
    
    // Vérifier le hash
    if (password_verify('password123', $user['password_hash'])) {
        echo "<span style='color:green'>✓ Fonctionne avec 'password123'</span><br>";
    } elseif (password_verify('admin123', $user['password_hash'])) {
        echo "<span style='color:green'>✓ Fonctionne avec 'admin123'</span><br>";
    } else {
        echo "<span style='color:red'>✗ Hash invalide</span><br>";
        
        // Réparer ce mot de passe
        $newHash = password_hash('password123', PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $update->execute([$newHash, $user['id']]);
        echo "<span style='color:orange'>→ Réparé avec mot de passe: 'password123'</span><br>";
    }
    echo "<hr>";
}

echo "<h3>Pour tester maintenant :</h3>";
echo "<p>Email: admin@example.com | Mot de passe: <strong>password123</strong></p>";
echo "<p>OU utilise l'email de n'importe quel utilisateur avec le mot de passe: <strong>password123</strong></p>";
?>