<?php
session_start();
require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['Users_id'])) {
    // Nettoyer et valider les données reçues
    $id_publication = filter_input(INPUT_POST, 'id_publication', FILTER_SANITIZE_NUMBER_INT);
    $contenu = filter_input(INPUT_POST, "contenu", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $id_Users = $_SESSION['Users_id'];

    if ($id_publication && $contenu) {
        // Connexion à la base de données
        $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($mysqli->connect_error) {
            die("Erreur de connexion à la base de données : " . $mysqli->connect_error);
        }

        // Préparez la requête pour éviter les injections SQL
        $queryComment = "INSERT INTO Commentaires (id_publication, id_Users, contenu) VALUES (?, ?, ?)";
        if ($stmtComment = $mysqli->prepare($queryComment)) {
            // Lier les paramètres et exécuter la requête
            $stmtComment->bind_param("iis", $id_publication, $id_Users, $contenu);
            $stmtComment->execute();
            $stmtComment->close();
        } else {
            // Gérer l'erreur si la requête ne peut pas être préparée
            echo "Erreur lors de la préparation de la requête : " . $mysqli->error;
        }

        $mysqli->close();
    } else {
        // Gérer le cas où certaines données requises ne sont pas fournies ou ne sont pas valides
        echo "Données requises manquantes ou invalides.";
    }

    // Redirigez l'utilisateur vers la page précédente
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    // Gérer le cas où la méthode de requête n'est pas POST ou l'utilisateur n'est pas connecté
    http_response_code(405); // Méthode non autorisée ou utilisateur non connecté
}
exit();
?>
