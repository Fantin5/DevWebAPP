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
    <!-- Responsive Header -->
    <header class="header">
      <!-- Mobile Menu Toggle -->
      <button class="mobile-menu-toggle" aria-label="Menu mobile">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
      </button>

      <!-- Left section: Logo -->
      <div class="header-left">
        <a href="../Testing grounds/main.php" class="logo-container">
          <img
            class="logo"
            src="../Connexion-Inscription/logo-transparent-pdf.png"
            alt="Logo Synapse"
          />
        </a>
      </div>

      <!-- Center section: Navigation -->
      <nav class="nav-links">
        <ul class="nav-menu">
          <li class="nav-item">
            <a href="../Testing grounds/main.php" class="nav-link" title="Accueil">
              <i class="fa-solid fa-home nav-icon"></i>
              <span class="nav-text">Accueil</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="../Concept/concept.php" class="nav-link" title="Notre concept">
              <i class="fa-solid fa-lightbulb nav-icon"></i>
              <span class="nav-text">Concept</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="../Testing grounds/activites.php" class="nav-link" title="Découvrir les activités">
              <i class="fa-solid fa-search nav-icon"></i>
              <span class="nav-text">Découvrir</span>
            </a>
          </li>
          <li class="nav-item special">
            <a href="../Testing grounds/jenis.php" class="nav-link create-activity" title="Créer une activité">
              <i class="fa-solid fa-plus nav-icon"></i>
              <span class="nav-text">Créer</span>
              <div class="glow-effect"></div>
            </a>
          </li>
        </ul>
      </nav>
    
      <!-- Right section: User Actions -->
      <div class="header-right">
        <?php include '../TEMPLATE/admin_button.php'; ?>
        
        <!-- Cart -->
        <div class="cart-container">
          <a href="../Testing grounds/panier.php" class="cart-link" aria-label="Panier">
            <i class="fa-solid fa-cart-shopping"></i>
            <span class="cart-count" id="panier-count">0</span>
          </a>
        </div>

        <?php if($logged_in): ?>
        <!-- Profile Dropdown -->
        <div class="profile-dropdown">
          <button class="profile-button" aria-label="Menu profil">
            <div class="profile-avatar">
              <i class="fa-solid fa-user"></i>
            </div>
            <div class="profile-info">
              <span class="profile-name"><?= htmlspecialchars($user_name) ?></span>
              <span class="profile-role">Membre</span>
            </div>
            <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
          </button>
          <div class="dropdown-content">
            <div class="dropdown-header">
              <div class="user-avatar-large">
                <i class="fa-solid fa-user"></i>
              </div>
              <div class="user-details">
                <strong><?= htmlspecialchars($user_name) ?></strong>
                <span>Explorateur de nature</span>
              </div>
            </div>
            <a href="../Compte/mon-espace.php" class="dropdown-link">
              <i class="fa-solid fa-gear"></i> 
              <span>Mon profil</span>
            </a>
            <a href="../Testing grounds/mes-activites.php" class="dropdown-link">
              <i class="fa-solid fa-calendar-days"></i> 
              <span>Mes activités</span>
            </a>
            <a href="../Testing grounds/mes-activites-registered.php" class="dropdown-link">
              <i class="fa-solid fa-clipboard-list"></i> 
              <span>Activités inscrites</span>
            </a>
            <div class="dropdown-divider"></div>
            <a href="../Connexion-Inscription/logout.php" class="dropdown-link logout">
              <i class="fa-solid fa-right-from-bracket"></i> 
              <span>Déconnexion</span>
            </a>
          </div>
        </div>
        <?php else: ?>
        <!-- Login Link -->
        <a href="../Connexion-Inscription/login_form.php" class="login-btn" aria-label="Connexion">
          <i class="fa-solid fa-user"></i>
          <span class="login-text">Connexion</span>
        </a>
        <?php endif; ?>
      </div>

      <!-- Mobile Navigation Overlay -->
      <div class="mobile-nav-overlay">
        <nav class="mobile-nav">
          <ul class="mobile-nav-menu">
            <li><a href="../Testing grounds/main.php" class="mobile-nav-link">
              <i class="fa-solid fa-home"></i> Accueil
            </a></li>
            <li><a href="../Concept/concept.php" class="mobile-nav-link">
              <i class="fa-solid fa-lightbulb"></i> Concept
            </a></li>
            <li><a href="../Testing grounds/activites.php" class="mobile-nav-link">
              <i class="fa-solid fa-search"></i> Découvrir
            </a></li>
            <li><a href="../Testing grounds/jenis.php" class="mobile-nav-link special">
              <i class="fa-solid fa-plus"></i> Créer une activité
            </a></li>
          </ul>
        </nav>
      </div>
    </header>
    
    <script src="../TEMPLATE/Nouveauhead.js"></script>
    <!-- cvq -->