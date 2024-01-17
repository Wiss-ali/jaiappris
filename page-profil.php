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

// Récupération des publications de l'utilisateur
$publications = [];
$queryPublications = "SELECT Publications.*, Users.pseudo FROM Publications LEFT JOIN Users ON Publications.id_Users = Users.id WHERE Publications.id_Users = ? ORDER BY Publications.date_publication DESC";
$stmtPublications = $mysqli->prepare($queryPublications);
$stmtPublications->bind_param("i", $userId);
$stmtPublications->execute();
$resultPublications = $stmtPublications->get_result();
while ($publication = $resultPublications->fetch_assoc()) {
    $publications[] = $publication;
}
$stmtPublications->close();


foreach ($publications as &$publication) {
    $postId = $publication['id'];

    // Récupération du nombre de likes pour le post
    $queryLikes = "SELECT COUNT(*) as likesCount FROM Likes WHERE id_publication = ?";
    $stmtLikes = $mysqli->prepare($queryLikes);
    $stmtLikes->bind_param("i", $postId);
    $stmtLikes->execute();
    $resultLikes = $stmtLikes->get_result();
    $likes = $resultLikes->fetch_assoc();
    $publication['likes'] = $likes['likesCount'];
    $stmtLikes->close();

    // Récupération des commentaires pour le post
    $publication['commentaires'] = [];
    $queryCommentaires = "SELECT Commentaires.*, Users.pseudo FROM Commentaires LEFT JOIN Users ON Commentaires.id_Users = Users.id WHERE id_publication = ? ORDER BY date_commentaire DESC";
    $stmtCommentaires = $mysqli->prepare($queryCommentaires);
    $stmtCommentaires->bind_param("i", $postId);
    $stmtCommentaires->execute();
    $resultCommentaires = $stmtCommentaires->get_result();
    while ($commentaire = $resultCommentaires->fetch_assoc()) {
        $publication['commentaires'][] = $commentaire;
    }
    $stmtCommentaires->close();
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

        <!-- Bouton pour afficher le formulaire de publication -->
        <button id="btnAjouterPost">Ajouter un nouveau post</button>

          <!-- Popup formulaire pour ajouter un nouveau post -->
        <div id="popupForm" style="display:none;">

            <h2>Ajouter un nouveau post</h2>

            <form method="post" action="traiter-publication.php" enctype="multipart/form-data">
                <textarea name="contenu" placeholder="Votre post ici..." required></textarea><br>
                <input type="file" name="image" accept="image/*"><br>
                <button type="button" onclick="togglePopup()">Annuler</button>
                <button type="submit" name="submit">Ajouter</button>
            </form>
        </div>

            <!-- Affichage des publications de l'utilisateur -->
        <h2>Vos publications</h2>
        
        <div id="publications">
            <?php foreach ($publications as $publication): ?>
                <div class="publication">
                    <p>Posté par: <?php echo htmlspecialchars($publication['pseudo']); ?></p>
                    <p>Date: <?php echo htmlspecialchars($publication['date_publication']); ?></p>
                    <p><?php echo nl2br(htmlspecialchars($publication['contenu'])); ?></p>
                    <?php if ($publication['chemin_image']): ?>
                        <img src="<?php echo htmlspecialchars($publication['chemin_image']); ?>" alt="Image du post" style="width: 100px; height: auto;">
                    <?php endif; ?>
            
                    <!-- Section pour les likes -->
                    <p>Likes: <?php echo htmlspecialchars($publication['likes']); ?></p>
            
                    <!-- Bouton pour liker la publication (doit être intégré avec votre logique de traitement) -->
                    <form method="post" action="traiter-like.php">
                        <input type="hidden" name="id_publication" value="<?php echo $publication['id']; ?>">
                        <button type="submit" name="like">Like</button>
                    </form>

                    <!-- Section pour les commentaires -->
                    <h3>Commentaires:</h3>
                    <?php foreach ($publication['commentaires'] as $commentaire): ?>
                        <div class="commentaire">
                            <p><?php echo htmlspecialchars($commentaire['pseudo']); ?> (<?php echo htmlspecialchars($commentaire['date_commentaire']); ?>): <?php echo nl2br(htmlspecialchars($commentaire['contenu'])); ?></p>
                        </div>
                    <?php endforeach; ?>
            
                    <!-- Formulaire pour ajouter un commentaire -->
                    <form method="post" action="traiter-commentaire.php">
                        <input type="hidden" name="id_publication" value="<?php echo $publication['id']; ?>">
                        <textarea name="contenu" placeholder="Ajouter un commentaire..." required></textarea><br>
                        <button type="submit" name="comment">Commenter</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <script>
            // Fonction pour afficher/cacher le formulaire de publication
            document.getElementById('btnAjouterPost').onclick = function() {
                togglePopup();
            };

            function togglePopup() {
                var popup = document.getElementById('popupForm');
                popup.style.display = popup.style.display === 'none' ? 'block' : 'none';
            }
        </script>


    <?php else: ?>
        <p>Profil non trouvé.</p>
    <?php endif; ?>
</body>
</html>
