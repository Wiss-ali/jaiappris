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

// Ajout pour vérifier le statut de l'amitié
$isFriend = false;
$query = "SELECT * FROM Relation WHERE 
    (id_Users1 = ? AND id_Users2 = ? OR id_Users1 = ? AND id_Users2 = ?) 
    AND statut = 'accepted'";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("iiii", $currentUser, $profileUserId, $profileUserId, $currentUser);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $isFriend = true;
}
$stmt->close();

//Récupérer les demandes d'amis entrantes
$friendRequests = [];
$queryRequests = "SELECT r.id as request_id, r.id_Users1, u.pseudo FROM Relation r 
                  JOIN Users u ON u.id = r.id_Users1 
                  WHERE r.id_Users2 = ? AND r.statut = 'pending'";
$stmtRequests = $mysqli->prepare($queryRequests);
$stmtRequests->bind_param("i", $currentUser);
$stmtRequests->execute();
$resultRequests = $stmtRequests->get_result();
while ($row = $resultRequests->fetch_assoc()) {
    $friendRequests[] = $row;
}
$stmtRequests->close();


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
    <link rel="stylesheet" href="publication-commentaires.css">
</head>
<body>
    <div class="header">
        <!-- Contenu de l'en-tête ici, comme le titre, le menu, etc. -->
        <a href="page-accueil.php" style="color: white; margin-left: 20px;">Page d'accueil</a>
        <a href="deconnexion.php">Se déconnecter</a>
    </div>

    <div class="sidebar left">
        <!-- Contenu de la sidebar gauche -->
        <p>Menu gauche</p>
    </div>

    <div class="content">

    
    <div class="friend-requests">
        <h2>Demandes d'amis</h2>
        <?php foreach ($friendRequests as $request): ?>
            <div class="friend-request">
                <p>Demande de : <?php echo htmlspecialchars($request['pseudo']); ?></p>
                <button class="accept-friend-request" data-request-id="<?php echo $request['request_id']; ?>">Accepter</button>
                <button class="decline-friend-request" data-request-id="<?php echo $request['request_id']; ?>">Rejeter</button>
            </div>
        <?php endforeach; ?>
    </div>
    
<div>
    <h1>Profil</h1>
    <!-- Logo ami et menu déroulant -->
    <div class="friend-logo-container">
        <?php if ($isFriend): ?>
            <img src="user.png" alt="Amis" class="friend-logo">
        <?php else: ?>
            <img src="add-user.png" alt="Pas Amis" class="friend-logo">
        <?php endif; ?>
        <div class="friend-actions" style="display:none;">
            <?php if (!$isFriend): ?>
                <a href="#" class="action-friend add-friend" data-action="add" data-user-id="<?php echo $profileUserId; ?>">Ajouter en ami</a>
            <?php endif; ?>
            <a href="#" class="action-friend block-user" data-action="block" data-user-id="<?php echo $profileUserId; ?>">Bloquer</a>
            <?php if ($isFriend): ?>
                <a href="#" class="action-friend remove-friend" data-action="remove" data-user-id="<?php echo $profileUserId; ?>">Supprimer des amis</a>
            <?php endif; ?>
        </div>
    </div>
</div>


    <?php if ($user): ?>
        <!-- Affichage et modification des informations du profil -->
        <?php if ($isOwnProfile): ?>
            <div class="user-profile">
                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <label for="nom">Nom:</label>
                    <input type="text" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required><br>
                    <label for="prenom">Prénom:</label>
                    <input type="text" id="prenom" name="prenom" value="<?php echo htmlspecialchars($user['prenom']); ?>" required><br>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" autocomplete="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br>
                    <label for="pseudo">Pseudo:</label>
                    <input type="text" id="pseudo" name="pseudo" value="<?php echo htmlspecialchars($user['pseudo']); ?>" required><br>
                    <button type="submit">Mettre à jour le profil</button>
                </form>
                <button id="btnAjouterPost">Ajouter un nouveau post</button>
                <div id="popupForm" style="display:none;">
                    <h2>Ajouter un nouveau post</h2>
                    <form method="post" action="traiter-publication.php" enctype="multipart/form-data">
                        <textarea name="contenu" placeholder="Votre post ici..." required></textarea><br>
                        <input type="file" name="image" accept="image/*"><br>
                        <button type="button" onclick="togglePopup()">Annuler</button>
                        <button type="submit" name="submit">Ajouter</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        <h2>Publications</h2>
        <div id="publications">
            <?php foreach ($publications as $publication): ?>
                <div class="publication" data-publication-id="<?php echo $publication['id']; ?>">
                    <div class="publication-header">
                        <h3 class="poster"><?php echo htmlspecialchars($publication['pseudo']); ?></h3>
                        <span class="date"><?php echo htmlspecialchars($publication['date_publication']); ?></span>
                    </div>
                    <div class="publication-body">
                        <p><?php echo nl2br(htmlspecialchars($publication['contenu'])); ?></p>
                        <?php if ($publication['chemin_image']): ?>
                            <img src="<?php echo htmlspecialchars($publication['chemin_image']); ?>" alt="Image du post" class="publication-image">
                        <?php endif; ?>
                        <div class="publication-actions">
                            <?php if ($publication['id_Users'] == $_SESSION['Users_id']): ?>
                                <!-- Logo de réglage et menu déroulant pour les publications de l'utilisateur -->
                                <div class="settings-menu">
                                    <img src="setting.png" class="settings-icon">
                                    <div class="settings-dropdown" style="display:none;">
                                        <a href="modif-publication.php?id=<?php echo $publication['id']; ?>">Modifier</a>
                                        <a href="#" class="delete-post" data-publication-id="<?php echo $publication['id']; ?>">Supprimer</a>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <p>Likes: <span class="like-count" data-publication-id="<?php echo $publication['id']; ?>">
                                <?php echo $publication['likes']; ?></span>
                            </p>
                            <form method="post" action="traiter-like.php" class="like-form" data-publication-id="<?php echo $publication['id']; ?>">
                                <input type="hidden" name="id_publication" value="<?php echo $publication['id']; ?>">
                                <button type="submit" name="like">Like</button>
                            </form>
                        </div>
                    </div>
                    <div class="comment-section">
                        <h3>Commentaires:</h3>
                        <?php foreach ($publication['commentaires'] as $commentaire): ?>
                            <div class="commentaire">
                                <p><?php echo htmlspecialchars($commentaire['pseudo']); ?> (<?php echo htmlspecialchars($commentaire['date_commentaire']); ?>): <?php echo nl2br(htmlspecialchars($commentaire['contenu'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                        <form method="post" action="traiter-commentaire.php">
                            <input type="hidden" name="id_publication" value="<?php echo $publication['id']; ?>">
                            <textarea name="contenu" placeholder="Ajouter un commentaire..." required></textarea><br>
                            <button type="submit" name="comment">Commenter</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
</div>


        <div class="sidebar right">
        <!-- Contenu de la sidebar droite -->
             <p>Menu droite</p>
        </div>
<script src="profilfriends.js"></script>
    <?php else: ?>
        <p>Profil non trouvé.</p>
    <?php endif; ?>
</body>
</html>