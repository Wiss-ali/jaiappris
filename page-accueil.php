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
$queryPosts = "SELECT Publications.*, Users.pseudo, Users.id as user_id FROM Publications LEFT JOIN Users ON Publications.id_Users = Users.id ORDER BY Publications.date_publication DESC";
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <h1>Posts récents</h1>
    <div id="publications">
    <?php foreach ($posts as $publication): ?>
        <div class="publication">
            <!-- Lien vers le profil de l'utilisateur -->
            <p>Posté par: <a href="page-profil.php?user_id=<?php echo $publication['user_id']; ?>">
                <?php echo htmlspecialchars($publication['pseudo']); ?>
            </a></p>
            <p>Date: <?php echo htmlspecialchars($publication['date_publication']); ?></p>
            <p><?php echo nl2br(htmlspecialchars($publication['contenu'])); ?></p>
            <?php if ($publication['chemin_image']): ?>
                <img src="<?php echo htmlspecialchars($publication['chemin_image']); ?>" alt="Image du post" style="width: 100px; height: auto;">
            <?php endif; ?>
            
            <!-- Section pour les likes -->
            <p>Likes: <span class="like-count" data-publication-id="<?php echo $publication['id']; ?>">
                <?php echo $publication['likes']; ?></span></p>
    
            <!-- Bouton pour liker la publication -->
            <form method="post" action="traiter-like.php" class="like-form" data-publication-id="<?php echo $publication['id']; ?>">
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
                // Ajout du script AJAX pour le traitement des likes
            $(document).ready(function() {
                $('.like-form').submit(function(e) {
                 e.preventDefault();

                 var form = $(this);
                 var publicationId = form.data('publication-id');  // Récupère l'ID de la publication
                 var url = form.attr('action');

                 $.ajax({
                     type: "POST",
                     url: url,
                     data: form.serialize(),
                     success: function(data) {
                        console.log("Réponse reçue : ", data);
                        var newLikeCount = data.newLikeCount;
            
                        // Ciblez le compteur de likes pour cette publication spécifique
                        $('.like-count[data-publication-id="' + publicationId + '"]').text(newLikeCount);
            
                        // Vous pouvez également changer l'apparence du bouton like
                        form.find('[name="like"]').toggleClass('liked');
                }
            });
        });
    });
        </script>


    <a href="deconnexion.php">Se déconnecter</a>

</body>
</html>
