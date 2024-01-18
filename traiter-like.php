<?php
session_start();
require_once "config.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Nettoyer et valider les données reçues
    $id_publication = filter_input(INPUT_POST, 'id_publication', FILTER_SANITIZE_NUMBER_INT);
    $id_Users = isset($_SESSION['Users_id']) ? $_SESSION['Users_id'] : null;

    if ($id_publication && $id_Users) {
        // Connexion à la base de données
        $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
        if ($mysqli->connect_error) {
            die("Erreur de connexion à la base de données : " . $mysqli->connect_error);
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
        } catch (mysqli_sql_exception $exception) {
            $mysqli->rollback();
            // Gérer l'exception ici (log, message à l'utilisateur, etc.)
        }

        $mysqli->close();
    } else {
        // Gérer le cas où les données nécessaires ne sont pas fournies ou ne sont pas valides
        echo "Données requises manquantes ou invalides.";
    }

    // Redirigez l'utilisateur vers la page précédente
    header("Location: " . $_SERVER['HTTP_REFERER']);
} else {
    // Gérer le cas où la méthode de requête n'est pas POST
    http_response_code(405); // Méthode non autorisée
}

// Envoi d'une réponse au client
echo json_encode(['newLikeCount' => $nouveauNombreDeLikes]);

exit();
?>
