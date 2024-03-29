<?php
// Démarrage de la session
session_start();

// Configuration du rapport d'erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['Users_id'])) {
    header('Location: page-connexion.php');
    exit();
}

require_once "config.php"; // Inclure le fichier de configuration de la base de données

$userId = $_SESSION['Users_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $contenu = filter_input(INPUT_POST, "contenu", FILTER_SANITIZE_STRING);
    $date_publication = date("Y-m-d H:i:s"); // Date et heure actuelle

    // Gestion de l'upload de l'image
    $chemin_image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // Vérification de la taille et du type du fichier
        if ($_FILES['image']['size'] <= 5000000 && in_array($_FILES['image']['type'], ['image/jpeg', 'image/png', 'image/gif'])) {
            $folder = "uploads/"; // Assurez-vous que ce dossier existe et est accessible en écriture
            $fileName = uniqid() . "-" . basename($_FILES['image']['name']); // Sécurisation du nom de fichier
            $file = $folder . $fileName;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $file)) {
                $chemin_image = $file;
            } else {
                echo "Erreur de chargement de l'image.";
                exit; // Arrêter le script en cas d'erreur d'upload
            }
        } else {
            echo "Fichier non valide ou trop grand.";
            exit; // Arrêter le script en cas de fichier non valide
        }
    }

    // Connexion à la base de données et insertion de la publication
    $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($mysqli->connect_error) {
        die("Erreur de connexion à la base de données : " . $mysqli->connect_error);
    }

    $stmt = $mysqli->prepare("INSERT INTO Publications (id_Users, contenu, date_publication, chemin_image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $userId, $contenu, $date_publication, $chemin_image);
    $stmt->execute();
    $stmt->close();
    $mysqli->close();

    // Redirection vers la page de profil après la publication
    header("Location: page-profil.php");
    exit();
}
?>
