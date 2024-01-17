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

// Récupération de tous les posts
$posts = [];
$queryPosts = "SELECT Publications.*, Users.pseudo FROM Publications LEFT JOIN Users ON Publications.id_Users = Users.id ORDER BY Publications.date_publication DESC";
$resultPosts = $mysqli->query($queryPosts);
while ($post = $resultPosts->fetch_assoc()) {
    // Pour chaque post, récupérez les likes et les commentaires
    $postId = $post['id'];

    // Récupération des likes pour le post
    $queryLikes = "SELECT COUNT(*) as likesCount FROM Likes WHERE id_publication = $postId";
    $resultLikes = $mysqli->query($queryLikes);
    $likes = $resultLikes->fetch_assoc();
    $post['likes'] = $likes['likesCount'];

    // Récupération des commentaires pour le post
    $post['commentaires'] = [];
    $queryCommentaires = "SELECT Commentaires.*, Users.pseudo FROM Commentaires LEFT JOIN Users ON Commentaires.id_Users = Users.id WHERE id_publication = $postId ORDER BY Commentaires.date_commentaire";
    $resultCommentaires = $mysqli->query($queryCommentaires);
    while ($commentaire = $resultCommentaires->fetch_assoc()) {
        $post['commentaires'][] = $commentaire;
    }

    $posts[] = $post;
}

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page d'accueil</title>
</head>
<body>
    <h1>Posts récents</h1>
    <div id="publications">
    <?php foreach ($posts as $publication): ?>
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

<a href="deconnexion.php">Se déconnecter</a>

</body>
</html>
