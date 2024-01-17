<?php
session_start();
require_once "config.php";

if (isset($_POST['comment'], $_POST['id_publication'], $_POST['contenu'], $_SESSION['Users_id'])) {
    $id_publication = $_POST['id_publication'];
    $id_Users = $_SESSION['Users_id'];
    $contenu = filter_input(INPUT_POST, "contenu", FILTER_SANITIZE_STRING);

    // Connexion à la base de données
    $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($mysqli->connect_error) {
        die("Erreur de connexion à la base de données : " . $mysqli->connect_error);
    }

    // Ajoutez le commentaire
    $queryComment = "INSERT INTO Commentaires (id_publication, id_Users, contenu) VALUES (?, ?, ?)";
    $stmtComment = $mysqli->prepare($queryComment);
    $stmtComment->bind_param("iis", $id_publication, $id_Users, $contenu);
    $stmtComment->execute();
    $stmtComment->close();
    $mysqli->close();
}

// Redirigez l'utilisateur vers la page précédente
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>
