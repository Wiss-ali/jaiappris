<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['Users_id'])) {
    header('Location: page-connexion.php');
    exit();
}

$serveur = "127.0.0.1:3306";
$nom_utilisateur = "u559440517_wissemdb";
$mot_de_passe = "Wisshafa69-";
$nom_base_de_donnees = "u559440517_jaiappris";

$mysqli = new mysqli($serveur, $nom_utilisateur, $mot_de_passe, $nom_base_de_donnees);

if ($mysqli->connect_error) {
    die("Erreur de connexion à la base de données : " . $mysqli->connect_error);
}

$user = null;

// Vérifiez si le formulaire de mise à jour a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérez les données du formulaire
    $nom = $_POST["nom"];
    $prenom = $_POST["prenom"];
    $email = $_POST["email"];
    $pseudo = $_POST["pseudo"];
    
    // Mettez à jour les informations de l'utilisateur dans la base de données
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

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
}

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
