<?php
session_start();
require_once "config.php";

if (!isset($_SESSION['Users_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non autorisé']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$requestId = isset($input['requestId']) ? intval($input['requestId']) : null;

if ($requestId) {
    $stmt = $mysqli->prepare("DELETE FROM Relation WHERE id = ?");
    $stmt->bind_param("i", $requestId);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Demande rejetée']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors du rejet']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'ID de requête manquant']);
}
$mysqli->close();
?>
