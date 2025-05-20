<?php
// Fonction pour vérifier si l'utilisateur est connecté
function is_logged_in() {
    // Démarrer la session si elle n'est pas déjà démarrée
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Fonction pour rediriger vers la page de connexion si non connecté
function require_login() {
    if (!is_logged_in()) {
        header("Location: ../Connexion-Inscription/login_form.php");
        exit();
    }
}

// Fonction pour rediriger vers la page d'accueil si déjà connecté
function redirect_if_logged_in() {
    if (is_logged_in()) {
        header("Location: ../Testing grounds/main.php");
        exit();
    }
}
?>