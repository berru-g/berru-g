<?php
$generatedHash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['password'])) {
    $password = $_POST['password'];
    $generatedHash = password_hash($password, PASSWORD_DEFAULT);
}
?>

<!DOCTYPE html>
<html lang="fr">
<!-- 
    ============================================
       Developed by : https://github.com/berru-g/
       Project : Generateur de Hash
       Version : 1.0.2  | 10/06/2025
       Licence : The MIT License (MIT)
       Copyright (c) 2025 Berru
    ============================================
-->
<head>
    <meta charset="UTF-8">
    <title>Générateur de Hash</title>
    <style>
        :root {
            --primary: #ab9ff2;
            --background: #f4f4f4;
            --text: #333;
            --border: #ddd;
            --success: #3ad38b;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--background);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        h1 {
            text-align: center;
            color: var(--primary);
            margin-bottom: 1.5rem;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        input[type="password"] {
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        button {
            background-color: var(--primary);
            color: white;
            padding: 0.75rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        button:hover {
            background-color: #8e7de1;
        }

        .result {
            margin-top: 1.5rem;
            padding: 1rem;
            background: #f9f9f9;
            border: 1px solid var(--border);
            border-radius: 8px;
            word-break: break-all;
            color: var(--text);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Générer un Hash</h1>
        <form method="POST">
            <input type="password" name="password" placeholder="Entrez un mot de passe" required>
            <button type="submit">Générer le Hash</button>
        </form>

        <?php if ($generatedHash): ?>
            <div class="result">
                <strong>Hash généré :</strong><br>
                <?= htmlspecialchars($generatedHash) ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
