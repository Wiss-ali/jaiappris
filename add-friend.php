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

if ($friendUserId && $friendUserId != $currentUser) {
    $stmt = $mysqli->prepare("INSERT INTO Relations (id_Users1, id_Users2, statut) VALUES (?, ?, 'pending')");
    $stmt->bind_param("ii", $currentUser, $friendUserId);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Demande d\'ami envoyée']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'envoi de la demande']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID d\'utilisateur non valide ou identique à l\'utilisateur actuel']);
}
$mysqli->close();
?>
