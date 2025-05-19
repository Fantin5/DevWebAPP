<?php
  session_start();
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Panier | Synapse</title>
    <link rel="stylesheet" href="Accueil.css" />
  
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    />
    <style>
      .panier-container {
        width: 90%;
        max-width: 1200px;
        margin: 40px auto;
      }

      .panier-title {
        text-align: center;
        color: #828977;
        margin-bottom: 30px;
      }

      .panier-empty {
        text-align: center;
        background-color: white;
        padding: 40px;
        border-radius: 12px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
      }

      .panier-empty i {
        font-size: 48px;
        color: #828977;
        margin-bottom: 20px;
      }

      .panier-empty p {
        color: #666;
        font-size: 18px;
        margin-bottom: 25px;
      }

      .continuer-achats {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 12px 25px;
        background-color: #828977;
        color: white;
        text-decoration: none;
        border-radius: 30px;
        font-weight: bold;
        font-size: 16px;
        transition: all 0.3s;
      }

      .continuer-achats:hover {
        background-color: #6d7364;
        transform: translateY(-3px);
      }

      .panier-items {
        margin-bottom: 30px;
      }

      .panier-item {
        display: flex;
        background-color: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        transition: transform 0.3s, box-shadow 0.3s;
      }

      .panier-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      }

      .panier-item-image {
        width: 200px;
        height: 150px;
        object-fit: cover;
      }

      .panier-item-details {
        flex: 1;
        padding: 20px;
        display: flex;
        flex-direction: column;
      }

      .panier-item-title {
        margin: 0 0 10px 0;
        font-size: 20px;
        color: #333;
      }

      .panier-item-period {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #666;
        font-size: 14px;
        margin-bottom: 10px;
      }

      .panier-item-tags {
        display: flex;
        gap: 8px;
        margin-bottom: 15px;
      }

      .panier-item-tag {
        background-color: #828977;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
      }

      .panier-item-tag.accent {
        background-color: #45cf91;
        color: #111;
      }

      .panier-item-tag.secondary {
        background-color: #647381;
      }

      .panier-item-price {
        margin-top: auto;
        font-weight: bold;
        font-size: 18px;
        color: #333;
      }

      .panier-item-actions {
        display: flex;
        align-items: center;
        padding: 20px;
        border-left: 1px solid #eee;
      }

      .panier-item-remove {
        background-color: #e74c3c;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 5px;
      }

      .panier-item-remove:hover {
        background-color: #c0392b;
      }

      .panier-summary {
        background-color: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      }

      .panier-summary h3 {
        margin-top: 0;
        margin-bottom: 20px;
        color: #333;
        font-size: 20px;
      }

      .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        color: #666;
        font-size: 16px;
      }

      .summary-total {
        display: flex;
        justify-content: space-between;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        font-weight: bold;
        font-size: 18px;
        color: #333;
      }

      .checkout-button {
        display: block;
        width: 100%;
        background-color: #45cf91;
        color: #111;
        border: none;
        padding: 15px;
        text-align: center;
        border-radius: 8px;
        font-weight: bold;
        font-size: 16px;
        margin-top: 20px;
        cursor: pointer;
        transition: all 0.3s;
      }

      .checkout-button:hover {
        background-color: #3abd7a;
      }

      @media (max-width: 768px) {
        .panier-item {
          flex-direction: column;
        }

        .panier-item-image {
          width: 100%;
          height: 180px;
        }

        .panier-item-actions {
          border-left: none;
          border-top: 1px solid #eee;
          justify-content: flex-end;
        }
      }
    </style>
  </head>
  <body>
    <?php
    // Inclure le header
    include '../TEMPLATE/Nouveauhead.php';
    ?>

    <div class="panier-container">
      <h1 class="panier-title">Votre Panier</h1>

      <!-- Contenu du panier (vide par défaut) -->
      <div id="panier-content">
        <!-- Le contenu sera injecté par JavaScript -->
      </div>
    </div>

    <?php
    // Inclure le footer
    include '../TEMPLATE/footer.php';
    ?>


    <style>
      /* Styles pour l'icône du panier et le compteur */
      .panier-link {
        position: relative;
        display: inline-block;
        color: #e4d8c8;
        text-decoration: none;
        transition: color 0.2s;
      }

      .panier-link:hover {
        color: #fff;
      }

      .panier-count {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #45cf91;
        color: #111;
        font-size: 12px;
        font-weight: bold;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
      }

      /* Styles pour le bouton d'ajout au panier */
      .add-to-cart-button {
        background-color: #45cf91 !important;
        color: #111 !important;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 15px;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
        border: none;
      }

      .add-to-cart-button:hover {
        background-color: #3abd7a !important;
        transform: translateY(-2px);
      }

      /* Notification pour l'ajout au panier */
      .notification {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 15px 25px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        opacity: 1;
        transition: opacity 0.5s;
      }

      .notification.success {
        background-color: #45cf91;
        color: #111;
      }

      .notification.info {
        background-color: #3498db;
        color: white;
      }

      .notification.error {
        background-color: #e74c3c;
        color: white;
      }

      .notification i {
        font-size: 18px;
      }

      /* Animation pour les boutons d'ajout au panier */
      @keyframes shake {
        0% {
          transform: rotate(0deg);
        }
        25% {
          transform: rotate(-5deg);
        }
        50% {
          transform: rotate(0deg);
        }
        75% {
          transform: rotate(5deg);
        }
        100% {
          transform: rotate(0deg);
        }
      }

      .add-to-cart-button:active i {
        animation: shake 0.3s ease-in-out;
      }

      /* Styles spécifiques aux boutons d'ajout au panier dans les cartes */
      .card .actions {
        justify-content: space-between;
      }

      .card .add-to-cart-button {
        padding: 8px 15px;
        font-size: 14px;
      }

      /* Media queries pour le responsive */
      @media (max-width: 768px) {
        .card .actions {
          flex-direction: column;
          gap: 10px;
          align-items: stretch;
        }

        .card .add-to-cart-button {
          width: 100%;
          justify-content: center;
        }
      }
    </style>

    <script src="panier.js"></script>
  </body>
</html>
