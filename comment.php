<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comment Page</title>
    <script>
        function showMessage() {
            alert("Le message a été envoyé avec succès merci!");
        }
    </script>
        <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }

        input, textarea {
            width: 100%;
            padding: 8px;
            margin-bottom: 16px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        input[type="submit"] {
            background-color: #5a8cea;
            color: #fff;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #7a8cea;
        }

        @media (max-width: 400px) {
            form {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Traitement du formulaire lorsqu'il est soumis
    $email = $_POST["email"];
    $message = $_POST["message"];

    // Validation des données (ajoutez votre propre logique de validation si nécessaire)

    // Connexion à la base de données (remplacez ces valeurs par les vôtres)
    $servername = "votre_serveur";
    $username = "votre_nom_utilisateur";
    $password = "votre_mot_de_passe";
    $dbname = "votre_base_de_donnees";

    $conn = new mysqli($servername, $username, $password, $dbname);

    // Vérifier la connexion
    if ($conn->connect_error) {
        die("La connexion à la base de données a échoué: " . $conn->connect_error);
    }

    // Préparer et exécuter la requête SQL pour insérer le commentaire
    $sql = "INSERT INTO commentaires (email, message) VALUES ('$email', '$message')";
    if ($conn->query($sql) === TRUE) {
        // Afficher le message popup côté client
        echo '<script>showMessage();</script>';
    } else {
        echo "Erreur lors de l'insertion du commentaire: " . $conn->error;
    }

    // Fermer la connexion à la base de données
    $conn->close();
}
?>

<!-- Formulaire d'envoi de commentaire -->
<form action="" method="post">
    <label for="email">Votre email :</label>
    <input type="email" name="email" required><br>

    <label for="message">Votre commentaire :</label>
    <textarea name="message" rows="4" required></textarea><br>

    <input type="submit" value="Envoyer le commentaire">
</form>

</body>
</html>
