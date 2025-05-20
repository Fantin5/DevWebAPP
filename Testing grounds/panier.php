<?php
  session_start();
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Panier | Synapse</title>
    <link rel="stylesheet" href="stylepanier.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    />
  </head>
  <body>
    <?php
    // Inclure le header
    include '../TEMPLATE/Nouveauhead.php';
    ?>

    <div class="page-wrapper">
      <!-- Floating elements animation -->
      <div class="floating-element cart-float-1"></div>
      <div class="floating-element leaf-float-1"></div>
      <div class="floating-element coin-float-1"></div>
      <div class="floating-element cart-float-2"></div>
      <div class="floating-element coin-float-2"></div>
      <div class="floating-element cart-float-1"></div>
<div class="floating-element leaf-float-1"></div>
<div class="floating-element coin-float-1"></div>
<div class="floating-element cart-float-2"></div>
<div class="floating-element coin-float-2"></div>
<div class="floating-element cart-float-1"></div>
<div class="floating-element leaf-float-1"></div>
<div class="floating-element coin-float-1"></div>
<div class="floating-element cart-float-2"></div>
<div class="floating-element coin-float-2"></div>
<div class="floating-element cart-float-1"></div>
<div class="floating-element leaf-float-1"></div>
<div class="floating-element coin-float-1"></div>
<div class="floating-element cart-float-2"></div>
<div class="floating-element coin-float-2"></div>

      <div class="panier-container">
        <h1 class="panier-title">Votre Panier</h1>

        <!-- Contenu du panier (vide par défaut) -->
        <div id="panier-content">
          <!-- Le contenu sera injecté par JavaScript -->
        </div>
      </div>
    </div>

    <!-- Notification pour les actions du panier -->
    <div id="notification" class="notification">
      <i class="fa-solid fa-circle-check"></i>
      <span id="notification-message">Article supprimé du panier</span>
    </div>

    <?php
    // Inclure le footer
    include '../TEMPLATE/footer.php';
    ?>

    <script src="panier.js"></script>
  </body>
</html>