<?php
session_start();
require_once "config.php";

header('Content-Type: application/json'); // Définir le type de contenu de la réponse comme JSON

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Nettoyer et valider les données reçues
    $id_publication = filter_input(INPUT_POST, 'id_publication', FILTER_SANITIZE_NUMBER_INT);
    $id_Users = isset($_SESSION['Users_id']) ? $_SESSION['Users_id'] : null;

    if ($id_publication && $id_Users) {
        // Connexion à la base de données
        $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($mysqli->connect_error) {
            // En cas d'erreur de connexion, envoyez la réponse appropriée
            echo json_encode(['error' => 'Erreur de connexion à la base de données']);
            exit();
        }

        // Démarrez la transaction
        $mysqli->begin_transaction();

        try {
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
            } else {
                // Si l'utilisateur a déjà aimé la publication, retirez le like
                $queryRemoveLike = "DELETE FROM Likes WHERE id_publication = ? AND id_Users = ?";
                $stmtRemoveLike = $mysqli->prepare($queryRemoveLike);
                $stmtRemoveLike->bind_param("ii", $id_publication, $id_Users);
                $stmtRemoveLike->execute();
                $stmtRemoveLike->close();
            }
            $stmtCheckLike->close();

            // Validez la transaction
            $mysqli->commit();

            // Calcul du nouveau nombre de likes pour la publication
            $queryCountLikes = "SELECT COUNT(*) as likesCount FROM Likes WHERE id_publication = ?";
            $stmtCountLikes = $mysqli->prepare($queryCountLikes);
            $stmtCountLikes->bind_param("i", $id_publication);
            $stmtCountLikes->execute();
            $resultCountLikes = $stmtCountLikes->get_result();
            $likesData = $resultCountLikes->fetch_assoc();
            $nouveauNombreDeLikes = $likesData['likesCount'];
            $stmtCountLikes->close();

            $mysqli->close();

            // Envoi d'une réponse au client avec le nouveau nombre de likes
            echo json_encode(['newLikeCount' => $nouveauNombreDeLikes]);
        } catch (mysqli_sql_exception $exception) {
            $mysqli->rollback();
            echo json_encode(['error' => 'Une erreur s\'est produite lors de la mise à jour des likes.']);
        }
    } else {
        // Gérer le cas où les données nécessaires ne sont pas fournies ou ne sont pas valides
        echo json_encode(['error' => 'Données requises manquantes ou invalides.']);
    }
} else {
    // Gérer le cas où la méthode de requête n'est pas POST
    http_response_code(405); // Méthode non autorisée
    echo json_encode(['error' => 'Method Not Allowed']);
}
?>
