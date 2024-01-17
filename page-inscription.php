<?php
session_start();

/*if (isset($_SESSION["nom_utilisateur"])) {
    header("Location: page_accueil.php");
    exit();
}*/

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérez les données du formulaire
    $usernameForm = $_POST["pseudo"];
    $passwordForm = $_POST["mot_de_passe"];
    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $email = $_POST["email"];

    // Validez et traitez les données

    // Connexion à la base de données
    $serveur = "127.0.0.1:3306";
    $nom_utilisateur = "u559440517_wissemdb";
    $mot_de_passe = "Wisshafa69-";
    $nom_base_de_donnees = "u559440517_jaiappris";

    $mysqli = new mysqli($serveur,  $nom_utilisateur, $mot_de_passe, $nom_base_de_donnees);

    if ($mysqli->connect_error) {
        die("Erreur de connexion à la base de données: " . $mysqli->connect_error);
    }

    // Insérez les données de l'utilisateur dans la table des utilisateurs
    $requete = "INSERT INTO Users (pseudo, mot_de_passe, nom, prenom, email) VALUES (?, ?, ?, ?, ?)";
    $statement = $mysqli->prepare($requete);
    
    if ($statement) {
        $hashed_password = password_hash($passwordForm, PASSWORD_DEFAULT);
        $statement->bind_param("sssss", $usernameForm, $hashed_password, $nom, $prenom, $email);
        $resultat = $statement->execute();

        if ($resultat) {
            header("Location: page-connexion.php");
            exit();
        } else {
            echo "Erreur lors de l'inscription: " . $statement->error;
        }

        $statement->close();
    } else {
        echo "Erreur de préparation de la requête: " . $mysqli->error;
    }

    $mysqli->close();
}
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
</head>
<body>

<h2>Inscription</h2>

<form method="post" action="page-inscription.php">
    <label for="pseudo">Nom d'Utilisateur:</label>
    <input type="text" id="pseudo" name="pseudo" required><br>

    <label for="mot_de_passe">Mot de Passe:</label>
    <input type="password" id="mot_de_passe" name="mot_de_passe" required><br>

    <label for="nom">Nom:</label>
    <input type="text" id="nom" name="nom" required><br>

    <label for="prenom">Prénom:</label>
    <input type="text" id="prenom" name="prenom" required><br>

    <label for="email">E-mail:</label>
    <input type="email" id="email" name="email" required><br>

    <button type="submit">S'Inscrire</button>
</form>

</body>
</html>