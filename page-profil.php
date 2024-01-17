<?php
// Démarrage de la session
session_start();

// Configuration du rapport d'erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Vérifie si l'utilisateur est connecté, sinon redirige vers la page de connexion
if (!isset($_SESSION['Users_id'])) {
    header('Location: page-connexion.php');
    exit();
}

// Inclure le fichier de configuration de la base de données
require_once "config.php";

// Connexion à la base de données
$mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_error) {
    die("Erreur de connexion à la base de données : " . $mysqli->connect_error);
}

$user = null;
$userId = $_SESSION['Users_id']; // Récupération de l'ID de l'utilisateur à partir de la session

// Vérifie si le formulaire de mise à jour du profil a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Nettoyer et filtrer les données saisies par l'utilisateur
    $nom = filter_input(INPUT_POST, "nom", FILTER_SANITIZE_STRING);
    $prenom = filter_input(INPUT_POST, "prenom", FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
    $pseudo = filter_input(INPUT_POST, "pseudo", FILTER_SANITIZE_STRING);
    
    // Préparation de la requête pour mettre à jour le profil de l'utilisateur
    $stmt = $mysqli->prepare("UPDATE Users SET nom = ?, prenom = ?, email = ?, pseudo = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $nom, $prenom, $email, $pseudo, $userId);
    $stmt->execute();
    $stmt->close();
}

// Préparation de la requête pour récupérer les informations de l'utilisateur
$query = "SELECT nom, prenom, email, pseudo FROM Users WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

// Récupération des informations de l'utilisateur si disponibles
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
}

// Fermeture de la requête préparée et de la connexion à la base de données
$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil de l'Utilisateur</title>
</head>
<body>
    <h1>Profil de l'Utilisateur</h1>
    <?php if ($user): ?>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <label for="nom">Nom:</label>
            <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required><br>
            
            <label for="prenom">Prénom:</label>
            <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required><br>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br>
            
            <label for="pseudo">Pseudo:</label>
            <input type="text" id="pseudo" name="pseudo" value="<?php echo htmlspecialchars($user['pseudo']); ?>" required><br>
            
            <button type="submit">Mettre à jour le profil</button>
        </form>
    <?php else: ?>
        <p>Profil non trouvé.</p>
    <?php endif; ?>
</body>
</html>
