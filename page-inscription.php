<?php
// Démarrage de la session
session_start();

// Configuration du rapport d'erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure le fichier de configuration de la base de données
require_once "config.php";

// Vérifie si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Nettoyer et filtrer les données saisies par l'utilisateur
    $usernameForm = filter_input(INPUT_POST, "pseudo", FILTER_SANITIZE_STRING);
    $passwordForm = filter_input(INPUT_POST, "mot_de_passe", FILTER_SANITIZE_STRING);
    $nom = filter_input(INPUT_POST, "nom", FILTER_SANITIZE_STRING);
    $prenom = filter_input(INPUT_POST, "prenom", FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);

    // Connexion à la base de données
    $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($mysqli->connect_error) {
        die("Erreur de connexion à la base de données: " . $mysqli->connect_error);
    }

    // Préparation de la requête pour insérer un nouvel utilisateur
    $requete = "INSERT INTO Users (pseudo, mot_de_passe, nom, prenom, email) VALUES (?, ?, ?, ?, ?)";
    $statement = $mysqli->prepare($requete);
    
    if ($statement) {
        // Hashage du mot de passe
        $hashed_password = password_hash($passwordForm, PASSWORD_DEFAULT);
        // Liaison des paramètres et exécution de la requête
        $statement->bind_param("sssss", $usernameForm, $hashed_password, $nom, $prenom, $email);
        $resultat = $statement->execute();

        if ($resultat) {
            // Redirection vers la page de connexion après inscription réussie
            header("Location: page-connexion.php");
            exit();
        } else {
            echo "Erreur lors de l'inscription: " . $statement->error;
        }
        $statement->close();
    } else {
        echo "Erreur de préparation de la requête: " . $mysqli->error;
    }

    // Fermeture de la connexion à la base de données
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