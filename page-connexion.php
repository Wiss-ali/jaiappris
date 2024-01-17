<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $servername = "127.0.0.1:3306";
    $username = "u559440517_wissemdb";
    $password = "12jaiappris03";
    $dbname = "u559440517_jaiappris";

    try {
        $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Préparation de la requête
        $stmt = $conn->prepare("SELECT id, mot_de_passe FROM Users WHERE email = :email");
        $stmt->bindParam(':email', $_POST['email']);
        $stmt->execute();

        // Vérification de l'utilisateur
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch();
            if (password_verify($_POST['mot_de_passe'], $user['mot_de_passe'])) {
                // Le mot de passe est correct, démarrer une nouvelle session
                $_SESSION['user_id'] = $user['id'];
                echo "Connexion réussie";
                // Rediriger l'utilisateur vers une autre page (page d'accueil par exemple)
            } else {
                echo "Mot de passe incorrect";
            }
        } else {
            echo "Aucun utilisateur trouvé avec cet email";
        }
    } catch(PDOException $e) {
        echo "Erreur : " . $e->getMessage();
    }

    $conn = null;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Connexion</title>
    <style>
        /* Ajoutez vos styles ici. Exemple : */
        .form-group {
            margin-bottom: 10px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
        }
        
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        
        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        input[type="submit"]:hover {
            background-color: #45a049;
        }
        
        .container {
            width: 300px;
            margin: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Formulaire de connexion</h2>
        <form method="post" action="page-connexion.php">

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email">
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe:</label>
                <input type="mot_de_passe" id="mot_de_passe" name="mot_de_passe">
            </div>

            <input type="submit" value="Se connecter">

        </form>
    </div>
</body>
</html>
