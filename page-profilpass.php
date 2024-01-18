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
$currentUser = $_SESSION['Users_id']; // Récupération de l'ID de l'utilisateur à partir de la session
$profileUserId = isset($_GET['user_id']) ? filter_var($_GET['user_id'], FILTER_SANITIZE_NUMBER_INT) : $currentUser;
$isOwnProfile = ($currentUser == $profileUserId);

// Vérifie si le formulaire de mise à jour du profil a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Nettoyer et filtrer les données saisies par l'utilisateur
    $nom = filter_input(INPUT_POST, "nom", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $prenom = filter_input(INPUT_POST, "prenom", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_input(INPUT_POST, "email", FILTER_SANITIZE_EMAIL);
    $pseudo = filter_input(INPUT_POST, "pseudo", FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    // Préparation de la requête pour mettre à jour le profil de l'utilisateur
    $stmt = $mysqli->prepare("UPDATE Users SET nom = ?, prenom = ?, email = ?, pseudo = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $nom, $prenom, $email, $pseudo, $profileUserId);
    $stmt->execute();
    $stmt->close();
}

// Récupération des informations de l'utilisateur
$query = "SELECT nom, prenom, email, pseudo FROM Users WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $profileUserId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
}

// Récupération des publications de l'utilisateur
$publications = [];
$queryPublications = "SELECT Publications.*, Users.pseudo FROM Publications LEFT JOIN Users ON Publications.id_Users = Users.id WHERE Publications.id_Users = ? ORDER BY Publications.date_publication DESC";
$stmtPublications = $mysqli->prepare($queryPublications);
$stmtPublications->bind_param("i", $profileUserId); // Utilisation de $profileUserId pour obtenir les publications de l'utilisateur spécifique
$stmtPublications->execute();
$resultPublications = $stmtPublications->get_result();

while ($publication = $resultPublications->fetch_assoc()) {
    // Récupération du nombre de likes pour le post
    $queryLikes = "SELECT COUNT(*) as likesCount FROM Likes WHERE id_publication = ?";
    $stmtLikes = $mysqli->prepare($queryLikes);
    $stmtLikes->bind_param("i", $publication['id']);
    $stmtLikes->execute();
    $resultLikes = $stmtLikes->get_result();
    $likes = $resultLikes->fetch_assoc();
    $publication['likes'] = $likes['likesCount'];
    $stmtLikes->close();

    // Récupération des commentaires pour le post
    $publication['commentaires'] = [];
    $queryCommentaires = "SELECT Commentaires.*, Users.pseudo FROM Commentaires LEFT JOIN Users ON Commentaires.id_Users = Users.id WHERE id_publication = ? ORDER BY date_commentaire DESC";
    $stmtCommentaires = $mysqli->prepare($queryCommentaires);
    $stmtCommentaires->bind_param("i", $publication['id']);
    $stmtCommentaires->execute();
    $resultCommentaires = $stmtCommentaires->get_result();
    while ($commentaire = $resultCommentaires->fetch_assoc()) {
        $publication['commentaires'][] = $commentaire;
    }
    $stmtCommentaires->close();

    $publications[] = $publication; // Ajoute la publication enrichie avec les likes et les commentaires au tableau
}
$stmtPublications->close();

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil de l'Utilisateur</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="reglagemenu.css"> 
</head>
<body>
    <h1>Profil de l'Utilisateur</h1>
    <?php if ($user): ?>
        <!-- Affichage et modification des informations du profil -->
        <?php if ($isOwnProfile): ?>
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
        <?php endif; ?>

        <!-- Affichage des publications de l'utilisateur -->
        <h2>Publications</h2>
        <div id="publications">
            <?php foreach ($publications as $publication): ?>
                <div class="publication" data-publication-id="<?php echo $publication['id']; ?>">
                    <p>Posté par: <?php echo htmlspecialchars($publication['pseudo']); ?></p>
                    <p>Date: <?php echo htmlspecialchars($publication['date_publication']); ?></p>
                    <p><?php echo nl2br(htmlspecialchars($publication['contenu'])); ?></p>
                    <?php if ($publication['chemin_image']): ?>
                        <img src="<?php echo htmlspecialchars($publication['chemin_image']); ?>" alt="Image du post" style="width: 100px; height: auto;">
                    <?php endif; ?>

                    <?php if ($publication['id_Users'] == $_SESSION['Users_id']): ?>
                    <!-- Logo de réglage et menu déroulant pour les publications de l'utilisateur -->
                    <div class="settings-menu">
                         <img src="setting.png" class="settings-icon">
                       <div class="settings-dropdown" style="display:none;">
                         <a href="modifier.php?id=<?php echo $publication['id']; ?>">Modifier</a>
                         <a href="#" class="delete-post" data-publication-id="<?php echo $publication['id']; ?>">Supprimer</a>
                      </div>
                    </div>
                    <?php endif; ?>
            
                    <!-- Section pour les likes -->
                    <p>Likes: <span class="like-count" data-publication-id="<?php echo $publication['id']; ?>">
                        <?php echo $publication['likes']; ?></span>
                    </p>
    
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
            $(document).ready(function() {
        // Fonction pour afficher/cacher le formulaire de publication
        function togglePopup() {
            var popup = document.getElementById('popupForm');
            popup.style.display = popup.style.display === 'none' ? 'block' : 'none';
        }

        // Attacher l'événement onclick au bouton pour afficher/cacher le formulaire de publication
        $('#btnAjouterPost').click(function() {
            togglePopup();
        });

        // Écouteur d'événements pour le formulaire de like
        $(document).on('submit', '.like-form', function(e) {
            e.preventDefault(); // Empêcher la soumission traditionnelle du formulaire

            var form = $(this);
            var publicationId = form.data('publication-id');  // Récupère l'ID de la publication
            var url = form.attr('action');

            // Faire une requête AJAX pour traiter le like
            $.ajax({
                type: "POST",
                url: url,
                data: form.serialize(), // Sérialiser les données du formulaire
                success: function(response) {
                    console.log("Réponse reçue : ", response);
                    if (response.newLikeCount !== undefined) {
                        // Mettre à jour le compteur de likes pour cette publication spécifique
                        $('.like-count[data-publication-id="' + publicationId + '"]').text(response.newLikeCount);
        
                        // Vous pouvez également changer l'apparence du bouton like
                        form.find('[name="like"]').toggleClass('liked');
                    } else if (response.error) {
                        console.error("Erreur : ", response.error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Erreur AJAX : ", error);
                }
            });
        });

        // Ajout des autres scripts si nécessaire...
    });
        </script>
        <script>
        $(document).ready(function() {
          $('.settings-icon').click(function() {
           $(this).next('.settings-dropdown').toggle();
        });

    $('.delete-post').click(function(e) {
        e.preventDefault();
        var publicationId = $(this).data('publication-id');
        if (confirm('Êtes-vous sûr de vouloir supprimer cette publication ?')) {
            $.ajax({
                url: 'supprimer-publication.php',
                type: 'POST',
                data: { id: publicationId },
                success: function(response) {
                    $('.publication[data-publication-id="' + publicationId + '"]').remove();
                }
            });
        }
    });

    $(window).click(function(e) {
        if (!$(e.target).hasClass('settings-icon') && !$(e.target).parents('.settings-menu').length) {
            $('.settings-dropdown').hide();
        }
    });
});
        </script>

        <a href="deconnexion.php">Se déconnecter</a>
    <?php else: ?>
        <p>Profil non trouvé.</p>
    <?php endif; ?>
</body>
</html>

