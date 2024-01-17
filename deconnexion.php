<?php
// deconnexion.php
session_start(); // Démarrage de la session

// Détruire toutes les variables de session
$_SESSION = array();

// Détruire la session elle-même
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Détruire le fichier de session sur le serveur
session_destroy();

// Rediriger l'utilisateur vers la page de connexion ou la page d'accueil
header("Location: page-connexion.php");
exit;
?>
