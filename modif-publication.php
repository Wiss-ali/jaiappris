<?php
session_start();

// Configuration du rapport d'erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once "config.php";

// Vérifie si l'utilisateur est connecté, sinon redirige vers la page de connexion
if (!isset($_SESSION['Users_id'])) {
    header('Location: page-connexion.php');
    exit();
}

$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_error) {
    die("Erreur de connexion à la base de données : " . $mysqli->connect_error);
}

// Vérifiez que l'ID de publication est défini et nettoyez-le
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $publicationId = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
} else {
    echo "ID de publication non valide.";
    exit;
}

// Récupérez les détails de la publication à partir de la base de données
$query = "SELECT contenu, chemin_image, id_Users FROM Publications WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $publicationId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Publication non trouvée.";
    exit;
}

$publication = $result->fetch_assoc();

// Vérifiez si l'utilisateur actuel est l'auteur de la publication
if ($publication['id_Users'] != $_SESSION['Users_id']) {
    echo "Vous n'avez pas la permission de modifier cette publication.";
    exit;
}

// Vérifie si le formulaire de mise à jour a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Nettoyer et filtrer les données saisies par l'utilisateur
    $contenu = filter_input(INPUT_POST, "contenu", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Préparation de la requête pour mettre à jour le contenu de la publication
    $stmt = $mysqli->prepare("UPDATE Publications SET contenu = ? WHERE id = ?");
    $stmt->bind_param("si", $contenu, $publicationId);
    $stmt->execute();
    $stmt->close();
    
    // Traiter la suppression de l'image
    if (isset($_POST['delete_image']) && $_POST['delete_image'] == 'yes') {
        if (!empty($publication['chemin_image']) && file_exists($publication['chemin_image'])) {
            unlink($publication['chemin_image']); // Supprimer l'image du serveur
        }
        $updateQuery = "UPDATE Publications SET chemin_image = NULL WHERE id = ?";
        $updateStmt = $mysqli->prepare($updateQuery);
        $updateStmt->bind_param("i", $publicationId);
        $updateStmt->execute();
        $updateStmt->close();
    }
    
    // Traiter le téléchargement de la nouvelle image
    if (isset($_FILES['image']['name']) && $_FILES['image']['size'] > 0) {
        $targetDir = "uploads/"; // Assurez-vous que ce répertoire existe et est accessible en écriture
        $targetFile = $targetDir . basename($_FILES['image']['name']);
        
        // Vérifiez et déplacez le fichier téléchargé
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            if (!empty($publication['chemin_image']) && file_exists($publication['chemin_image'])) {
                unlink($publication['chemin_image']); // Supprimer l'ancienne image du serveur
            }
            $updateQuery = "UPDATE Publications SET chemin_image = ? WHERE id = ?";
            $updateStmt = $mysqli->prepare($updateQuery);
            $updateStmt->bind_param("si", $targetFile, $publicationId);
            $updateStmt->execute();
            $updateStmt->close();
        }
    }
    
    // Rediriger vers la page de profil ou la page de la publication
    header("Location: page-profil.php");
    exit;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier Publication</title>
    <link rel="stylesheet" href="reglagemenu.css">
</head>
<body>

    <div class="header">
        <!-- Contenu de l'en-tête ici -->
        <h1>modification</h1>
    </div>

    <div class="sidebar left">
        <!-- Contenu de la sidebar gauche -->
        <p>Menu gauche</p>
    </div>

<div class="content">
    <h1>Modifier la Publication</h1>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $publicationId; ?>" enctype="multipart/form-data">
        <label for="contenu">Contenu:</label>
        <textarea name="contenu" required><?php echo htmlspecialchars($publication['contenu']); ?></textarea><br>

        <!-- Champ pour télécharger une nouvelle image -->
        <label for="image">Image:</label>
        <input type="file" name="image" id="image"><br>

        <!-- Option pour supprimer l'image existante -->
        <?php if (!empty($publication['chemin_image'])): ?>
            <label>
                <input type="checkbox" name="delete_image" value="yes"> Supprimer l'image existante
            </label><br>
            <img src="<?php echo htmlspecialchars($publication['chemin_image']); ?>" alt="Image actuelle" style="max-width: 200px;"><br>
        <?php endif; ?>

        <button type="submit">Mettre à jour la publication</button>
    </form>

    <a href="page-profil.php">Retour au profil</a>
</div>
    
    <div class="sidebar right">
        <!-- Contenu de la sidebar droite -->
        <p>Menu droite</p>
    </div>
</body>
</html>
