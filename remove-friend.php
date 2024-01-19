<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['Users_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non autorisé']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$friendUserId = isset($input['userId']) ? intval($input['userId']) : null;
$currentUser = $_SESSION['Users_id'];

if ($friendUserId) {
    $stmt = $mysqli->prepare("DELETE FROM Relation WHERE (id_Users1 = ? AND id_Users2 = ?) OR (id_Users1 = ? AND id_Users2 = ?)");
    $stmt->bind_param("iiii", $currentUser, $friendUserId, $friendUserId, $currentUser);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Ami supprimé']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de la suppression de l\'ami']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID de l\'ami manquant']);
}
$mysqli->close();
?>
