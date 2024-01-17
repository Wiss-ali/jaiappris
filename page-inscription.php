<?php
// Ajoutez la logique de traitement de formulaire ici si la requête est de type POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Connexion à la base de données
    $servername = "127.0.0.1:3306";
    $username = "u559440517_wissemdb";
    $password = "12jaiappris03";
    $dbname = "u559440517_jaiappris";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        // Définit le mode d'erreur de PDO à exception
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Hachage du mot de passe
        $hashed_password = password_hash($_POST['mot_de_passe'], PASSWORD_DEFAULT);

        // Préparation de la requête d'insertion
        $stmt = $conn->prepare("INSERT INTO Users (prenom, nom, pseudo, email, mot_de_passe) VALUES (:prenom, :nom, :pseudo, :email, :mot_de_passe)");
        
        // Liaison des paramètres
        $stmt->bindParam(':prenom', $_POST['prenom']);
        $stmt->bindParam(':nom', $_POST['nom']);
        $stmt->bindParam(':pseudo', $_POST['pseudo']);
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':mot_de_passe', $hashed_password);
        
        // Exécution de la requête
        $stmt->execute();

        echo "Nouvel enregistrement créé avec succès";
    } catch(PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }

    $conn = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        .form-group {
            margin-bottom: 15px;
        }

        label, input {
            display: block;
            width: 100%;
        }

        input[type="text"], input[type="email"], input[type="password"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
<h2>Formulaire d'inscription</h2>
    <form method="post" action="inscription.php">
        <div class="form-group">
            <label for="prenom">Prénom:</label>
            <input type="text" id="prenom" name="prenom">
        </div>
        <div class="form-group">
            <label for="nom">Nom:</label>
            <input type="text" id="nom" name="nom">
        </div>
        <div class="form-group">
            <label for="pseudo">Pseudo:</label>
            <input type="text" id="pseudo" name="pseudo">
        </div>
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email">
        </div>
        <div class="form-group">
            <label for="mot_de_passe">Mot de passe:</label>
            <input type="mot_de_passe" id="mot_de_passe" name="mot_de_passe">
        </div>
        <div class="form-group">
            <input type="submit" value="S'inscrire">
        </div>
    </form>
</body>
</html>