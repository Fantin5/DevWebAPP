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
    <!-- Enhanced Header with floating elements -->
    <header class="header">
      <!-- Animated background elements -->
      <div class="header-bg-elements">
        <div class="floating-leaf leaf-1"><i class="fa-solid fa-leaf"></i></div>
        <div class="floating-leaf leaf-2"><i class="fa-solid fa-seedling"></i></div>
        <div class="floating-leaf leaf-3"><i class="fa-solid fa-spa"></i></div>
        <div class="floating-particle particle-1"></div>
        <div class="floating-particle particle-2"></div>
        <div class="floating-particle particle-3"></div>
      </div>

      <!-- Left section: Logo with enhanced styling -->
      <div class="header-left">
        <a href="../Testing grounds/main.php" class="logo-container">
          <div class="logo-background">
            <img
              class="logo"
              src="../Connexion-Inscription/logo-transparent-pdf.png"
              alt="Logo Synapse"
            />
          </div>
        </a>
      </div>

      <!-- Center section: Enhanced Navigation -->
<!-- Center section: Compact Navigation -->
<nav class="nav-links">
        <ul>
          <li class="nav-item">
            <a href="../Testing grounds/main.php" class="nav-link" title="Accueil">
              <i class="fa-solid fa-home nav-icon"></i>
              <span>Accueil</span>
              <div class="nav-ripple"></div>
            </a>
          </li>
          <li class="nav-item">
            <a href="../Concept/concept.php" class="nav-link" title="Notre concept">
              <i class="fa-solid fa-lightbulb nav-icon"></i>
              <span>Concept</span>
              <div class="nav-ripple"></div>
            </a>
          </li>
          <li class="nav-item">
            <a href="../Testing grounds/activites.php" class="nav-link" title="Découvrir les activités">
              <i class="fa-solid fa-search nav-icon"></i>
              <span>Découvrir</span>
              <div class="nav-ripple"></div>
            </a>
          </li>
          <li class="nav-item special">
            <a href="../Testing grounds/jenis.php" class="nav-link create-activity" title="Créer une activité">
              <i class="fa-solid fa-plus nav-icon"></i>
              <span>Créer</span>
              <div class="nav-ripple"></div>
              <div class="glow-effect"></div>
            </a>
          </li>
        </ul>
      </nav>
    
      <!-- Right section: Enhanced Icons -->
      <div class="header-right">
        <?php include '../TEMPLATE/admin_button.php'; ?>
        
        <!-- Enhanced Cart -->
        <div class="cart-container">
          <a href="../Testing grounds/panier.php" class="panier-link" aria-label="Panier">
            <div class="cart-icon-wrapper">
              <i class="fa-solid fa-cart-shopping"></i>
              <div class="cart-pulse"></div>
            </div>
            <span class="panier-count" id="panier-count">0</span>
          </a>
        </div>

        <?php if($logged_in): ?>
        <!-- Enhanced Profile Dropdown -->
        <div class="profile-dropdown enhanced">
          <a href="#" class="connexion-profil enhanced-profile" aria-label="Profil">
            <div class="profile-avatar">
              <i class="fa-solid fa-user"></i>
              <div class="profile-status"></div>
            </div>
            <div class="profile-info">
              <span class="profile-name"><?= htmlspecialchars($user_name) ?></span>
              <span class="profile-role">Membre</span>
            </div>
            <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
          </a>
          <div class="dropdown-content enhanced-dropdown">
            <div class="dropdown-header">
              <div class="user-avatar-large">
                <i class="fa-solid fa-user"></i>
              </div>
              <div class="user-details">
                <strong><?= htmlspecialchars($user_name) ?></strong>
                <span>Explorateur de nature</span>
              </div>
            </div>
            <div class="dropdown-divider"></div>
            <a href="../Compte/mon-espace.php" class="dropdown-link">
              <i class="fa-solid fa-gear"></i> 
              <span>Mon profil</span>
              <i class="fa-solid fa-arrow-right link-arrow"></i>
            </a>
            <a href="../Testing grounds/mes-activites.php" class="dropdown-link">
              <i class="fa-solid fa-calendar-days"></i> 
              <span>Mes activités</span>
              <i class="fa-solid fa-arrow-right link-arrow"></i>
            </a>
            <a href="../Testing grounds/mes-activites-registered.php" class="dropdown-link">
              <i class="fa-solid fa-clipboard-list"></i> 
              <span>Activités inscrites</span>
              <i class="fa-solid fa-arrow-right link-arrow"></i>
            </a>
            <div class="dropdown-divider"></div>
            <a href="../Connexion-Inscription/logout.php" class="dropdown-link logout">
              <i class="fa-solid fa-right-from-bracket"></i> 
              <span>Déconnexion</span>
              <i class="fa-solid fa-arrow-right link-arrow"></i>
            </a>
          </div>
        </div>
        <?php else: ?>
        <!-- Enhanced Login Link -->
        <a href="../Connexion-Inscription/login_form.php" class="connexion-profil login-btn" aria-label="Connexion">
          <div class="login-icon-wrapper">
            <i class="fa-solid fa-user"></i>
            <div class="login-pulse"></div>
          </div>
          <span class="login-text">Connexion</span>
        </a>
        <?php endif; ?>
      </div>
    </header>
    
    <script src="../TEMPLATE/Nouveauhead.js"></script>
    <!-- cvq -->