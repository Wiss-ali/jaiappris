<?php
session_start();
require_once "config.php";

if (isset($_POST['like'], $_POST['id_publication'], $_SESSION['Users_id'])) {
    $id_publication = $_POST['id_publication'];
    $id_Users = $_SESSION['Users_id'];

    // Connexion à la base de données
    $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($mysqli->connect_error) {
        die("Erreur de connexion à la base de données : " . $mysqli->connect_error);
    }

    // Vérifiez si l'utilisateur a déjà aimé la publication
    $queryCheckLike = "SELECT id FROM Likes WHERE id_publication = ? AND id_Users = ?";
    $stmtCheckLike = $mysqli->prepare($queryCheckLike);
    $stmtCheckLike->bind_param("ii", $id_publication, $id_Users);
    $stmtCheckLike->execute();
    $resultCheckLike = $stmtCheckLike->get_result();
    if ($resultCheckLike->num_rows === 0) {
        // Si l'utilisateur n'a pas encore aimé la publication, ajoutez le like
        $queryLike = "INSERT INTO Likes (id_publication, id_Users) VALUES (?, ?)";
        $stmtLike = $mysqli->prepare($queryLike);
        $stmtLike->bind_param("ii", $id_publication, $id_Users);
        $stmtLike->execute();
        $stmtLike->close();
    }
    $stmtCheckLike->close();
    $mysqli->close();
}

// Redirigez l'utilisateur vers la page précédente
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>
