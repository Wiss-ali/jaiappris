<?php
// Démarrage de la session
session_start();

// Configuration du rapport d'erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure le fichier de configuration de la base de données
require_once "config.php";

// Vérifie si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Nettoyer et filtrer les données saisies par l'utilisateur
    $username = filter_input(INPUT_POST, "pseudo", FILTER_SANITIZE_STRING);
    $password = filter_input(INPUT_POST, "mot_de_passe", FILTER_SANITIZE_STRING);

    // Connexion à la base de données
    $mysqli = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if ($mysqli->connect_error) {
        die("Erreur de connexion à la base de données: " . $mysqli->connect_error);
    }

    // Préparation de la requête pour récupérer le mot de passe de l'utilisateur
    $query = "SELECT id, mot_de_passe FROM Users WHERE pseudo = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $userId = $row['id'];
        $hashed_password = $row['mot_de_passe'];

        // Vérification du mot de passe
        if (password_verify($password, $hashed_password)) {
            // Régénération de l'ID de session pour prévenir la fixation de session
            session_regenerate_id();
            // Stockage de l'ID de l'utilisateur et du pseudo dans la session
            $_SESSION["Users_id"] = $userId;
            $_SESSION["pseudo"] = $username;
            // Redirection vers la page de profil
            header("Location: page-profil.php");
            exit();
        } else {
            $error_message = "Échec de la vérification du mot de passe.";
        }
    } else {
        $error_message = "Aucun utilisateur trouvé avec ce nom.";
    }

    // Fermeture de la requête préparée et de la connexion à la base de données
    $stmt->close();
    $mysqli->close();
}
?>

<!DOCTYPE html>
<html lang="fr-fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page de Connexion</title>
    <link rel="stylesheet" href="connexion.css">
    <link rel="icon" href="logo.wissico1.png" type="image/x-icon">
</head>
<body>
   <h2>Connexion</h2>
<?php
if (isset($error_message)) {
    echo "<p style='color: red;'>$error_message</p>";
}
?>
    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
    <label for="pseudo">pseudo :</label>
    <input type="text" id="pseudo" name="pseudo" required><br>
    <label for="mot_de_passe">Mot de passe :</label>
    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
    <button type="submit">Se Connecter</button>
    </form>
</body>

</html>
