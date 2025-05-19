<?php
// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
$logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$user_name = $logged_in ? $_SESSION['user_first_name'] : '';
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= isset($page_title) ? $page_title : 'Synapse' ?></title>
    <link rel="stylesheet" href="../TEMPLATE/Nouveauhead.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    />
  </head>
  <body>

    <!-- Header -->
    <header class="header">
      <a href="../Testing grounds/main.php">
        <img
          class="logo"
          src="../Connexion-Inscription/logo-transparent-pdf.png"
          alt="Logo Synapse"
        />
      </a>
<nav class="nav-links">
  <ul>
    <li><a href="../Concept/concept.php">Concept</a></li>
    <li><a href="../Testing grounds/jenis.php"><i class="fa-solid fa-plus"></i> Créer une activité</a></li>
  </ul>
</nav>
    
      <div class="icon">
        <i class="fa-regular fa-heart" aria-label="Favoris"></i>
        <a href="../Testing grounds/panier.php" class="panier-link" aria-label="Panier">
          <i class="fa-solid fa-cart-shopping"></i>
          <span class="panier-count" id="panier-count">0</span>
        </a>
        <?php if($logged_in): ?>
        <!-- Menu déroulant pour l'utilisateur connecté -->
        <div class="profile-dropdown">
          <a href="#" class="connexion-profil" aria-label="Profil">
            <i class="fa-solid fa-user"></i>
            <span class="profile-name"><?= htmlspecialchars($user_name) ?></span>
          </a>
          <div class="dropdown-content">
            <a href="../Compte/mon-espace.php"><i class="fa-solid fa-gear"></i> Mon profil</a>
            <a href="../Testing grounds/mes-activites.php"><i class="fa-solid fa-calendar-days"></i> Mes activités</a>
            <div class="dropdown-divider"></div>
            <a href="../Connexion-Inscription/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
          </div>
        </div>
        <?php else: ?>
        <!-- Lien simple pour la connexion -->
        <a href="../Connexion-Inscription/login_form.php" class="connexion-profil" aria-label="Connexion">
          <i class="fa-solid fa-user"></i>
        </a>
        <?php endif; ?>
      </div>
    </header>
    <!-- lien -->
    <script src="Nouveauhead.js"></script>