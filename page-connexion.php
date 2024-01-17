<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Définissez les informations de connexion à la base de données
$serveur = "127.0.0.1:3306";
$nom_utilisateur = "u559440517_wissemdb";
$mot_de_passe = "Wisshafa69-";
$nom_base_de_donnees = "u559440517_jaiappris";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["pseudo"];
    $password = $_POST["mot_de_passe"];

    $mysqli = new mysqli($serveur,  $nom_utilisateur, $mot_de_passe, $nom_base_de_donnees);

    if ($mysqli->connect_error) {
        die("Erreur de connexion à la base de données: " . $mysqli->connect_error);
    }

    $query = "SELECT mot_de_passe FROM Users WHERE pseudo = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $hashed_password = $row['mot_de_passe'];

    // Ajout pour le débogage
    echo "Mot de passe haché récupéré : " . $hashed_password . "<br>";
    echo "Mot de passe saisi : " . $password . "<br>";

    if (password_verify($password, $hashed_password)) {
        $_SESSION["pseudo"] = $username;
        header("Location: google.fr");
        exit();
    } else {
        echo "Échec de la vérification du mot de passe.<br>";
    }
} else {
    echo "Aucun utilisateur trouvé avec ce nom.<br>";
}

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
