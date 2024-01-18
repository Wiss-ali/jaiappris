<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['id']) && isset($_SESSION['Users_id'])) {
    $publicationId = $_POST['id'];
    $userId = $_SESSION['Users_id']; // L'ID de l'utilisateur actuel

    // Connexion à la base de données
    $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($mysqli->connect_error) {
        die("Erreur de connexion à la base de données : " . $mysqli->connect_error);
    }

    // Vérifiez que la publication appartient à l'utilisateur
    $stmt = $mysqli->prepare("SELECT id_Users FROM Publications WHERE id = ?");
    $stmt->bind_param("i", $publicationId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['id_Users'] == $userId) {
            // L'utilisateur a le droit de supprimer cette publication
            $stmt = $mysqli->prepare("DELETE FROM Publications WHERE id = ?");
            $stmt->bind_param("i", $publicationId);
            $stmt->execute();
            echo json_encode(['success' => 'Publication supprimée.']);
        } else {
            echo json_encode(['error' => 'Action non autorisée.']);
        }
    } else {
        echo json_encode(['error' => 'Publication non trouvée.']);
    }

    $stmt->close();
    $mysqli->close();
}
?>
