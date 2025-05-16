<?php
// Configuration de la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "activity";

// Créer une connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Échec de la connexion à la base de données: " . $conn->connect_error);
}

// Variables pour les messages
$successMessage = '';
$errorMessage = '';

// Traitement de la suppression d'activité
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Supprimer d'abord les tags associés
    $sql_delete_tags = "DELETE FROM tags WHERE activite_id = ?";
    $stmt_tags = $conn->prepare($sql_delete_tags);
    $stmt_tags->bind_param("i", $id);
    $stmt_tags->execute();
    $stmt_tags->close();
    
    // Puis supprimer l'activité
    $sql_delete = "DELETE FROM activites WHERE id = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $successMessage = "L'activité a été supprimée avec succès.";
    } else {
        $errorMessage = "Erreur lors de la suppression de l'activité: " . $conn->error;
    }
    $stmt->close();
}

// Récupérer les activités depuis la base de données
$sql = "SELECT a.*, 
        (SELECT GROUP_CONCAT(nom_tag) FROM tags WHERE activite_id = a.id) AS tags
        FROM activites a 
        ORDER BY date_creation DESC";
        
$result = $conn->query($sql);

// Fonction pour obtenir les étoiles formatées basées sur la note
function getStars($rating) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    
    $stars = '';
    
    // Étoiles pleines
    for ($i = 0; $i < $fullStars; $i++) {
        $stars .= '<i class="fa-solid fa-star"></i>';
    }
    
    // Demi-étoile si nécessaire
    if ($halfStar) {
        $stars .= '<i class="fa-solid fa-star-half-stroke"></i>';
    }
    
    // Étoiles vides
    for ($i = 0; $i < $emptyStars; $i++) {
        $stars .= '<i class="fa-regular fa-star"></i>';
    }
    
    return '<span class="stars">' . $stars . '</span> <span class="rating-value">' . number_format($rating, 1) . '</span>';
}

// Function to determine the CSS class for tags
function getTagClass($tag) {
    $tagClasses = [
        'art' => 'primary',
        'cuisine' => 'secondary',
        'bien_etre' => 'accent',
        'creativite' => 'primary',
        'sport' => 'secondary',
        'exterieur' => 'accent',
        'interieur' => 'secondary',
        'gratuit' => 'accent',
        'ecologie' => 'primary',
        'randonnee' => 'secondary',
        'jardinage' => 'accent',
        'meditation' => 'primary',
        'artisanat' => 'secondary'
    ];
    
    return isset($tagClasses[$tag]) ? $tagClasses[$tag] : '';
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Mes Activités | Synapse</title>
    <link rel="stylesheet" href="main.css" />
    <link rel="stylesheet" href="../TEMPLATE/teteaupied.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    />
    <style>
      .activities-page-title {
        text-align: center;
        margin: 40px 0;
        color: #828977;
      }
      
      .page-container {
        width: 90%;
        max-width: 1200px;
        margin: 30px auto 60px;
      }
      
      .action-buttons {
        text-align: center;
        margin-bottom: 40px;
      }
      
      .create-button {
        background-color: var(--primary-color);
        color: #111;
        padding: 14px 30px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: bold;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: all 0.3s;
        box-shadow: 0 4px 15px rgba(69, 161, 99, 0.3);
      }
      
      .create-button:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(69, 161, 99, 0.4);
        background-color: #3abd7a;
      }
      
      .activities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
      }
      
      .no-activities {
        text-align: center;
        padding: 40px;
        background: white;
        border-radius: 15px;
        box-shadow: var(--shadow-md);
        grid-column: 1 / -1;
      }
      
      .no-activities i {
        font-size: 48px;
        color: #828977;
        margin-bottom: 20px;
      }
      
      .no-activities p {
        color: #666;
        font-size: 18px;
        margin-bottom: 0;
      }
      
      .activity-card {
        background-color: white;
        border-radius: var(--border-radius-md);
        overflow: hidden;
        box-shadow: var(--shadow-md);
        display: flex;
        flex-direction: column;
        transition: transform 0.3s, box-shadow 0.3s;
        position: relative;
      }
      
      .activity-card:hover {
        transform: translateY(-10px);
        box-shadow: var(--shadow-lg);
      }
      
      .card-content {
        position: relative;
        display: flex;
        flex-direction: column;
      }
      
      .image-container {
        height: 200px;
        overflow: hidden;
        position: relative;
      }
      
      .activity-card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s;
      }
      
      .activity-card:hover img {
        transform: scale(1.1);
      }
      
      .tag-container {
        position: absolute;
        bottom: 15px;
        left: 15px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        max-width: 90%;
      }
      
      .tag {
        background-color: #828977;
        color: white;
        padding: 6px 14px;
        border-radius: 30px;
        font-size: 12px;
        font-weight: 600;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
      }
      
      .tag.primary {
        background-color: var(--primary-color);
        color: #111;
      }
      
      .tag.secondary {
        background-color: var(--secondary-color);
        color: white;
      }
      
      .tag.accent {
        background-color: var(--accent-color);
        color: #111;
      }
      
      .card-info {
        flex-grow: 1;
        padding: 20px;
        display: flex;
        flex-direction: column;
      }
      
      .card-title {
        font-size: 18px;
        font-weight: 700;
        margin: 0 0 15px 0;
        color: #333;
        line-height: 1.4;
      }
      
      .period {
        color: #666;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 15px;
      }
      
      .period i {
        color: #828977;
      }
      
      .price {
        font-weight: bold;
        margin-top: auto;
        color: #333;
      }
      
      .price.free {
        color: var(--primary-color);
      }
      
      .card-actions {
        padding: 15px 20px;
        border-top: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        background-color: #f9f9f9;
      }
      
      .card-button {
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        border: none;
      }
      
      .edit-button {
        background-color: #f1c40f;
        color: #333;
      }
      
      .edit-button:hover {
        background-color: #e2b607;
        transform: translateY(-2px);
      }
      
      .delete-button {
        background-color: #e74c3c;
        color: white;
      }
      
      .delete-button:hover {
        background-color: #c0392b;
        transform: translateY(-2px);
      }
      
      .action-links {
        display: flex;
        gap: 10px;
      }
      
      .rating {
        color: #f1c40f;
        display: flex;
        align-items: center;
        gap: 5px;
      }
      
      .stars {
        display: flex;
        align-items: center;
        gap: 2px;
      }
      
      .rating-value {
        color: #666;
        font-weight: 600;
        margin-left: 5px;
      }
      
      .notification {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        padding: 15px 25px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        z-index: 1000;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        opacity: 1;
        transition: opacity 0.5s;
      }
      
      .notification.success {
        background-color: var(--primary-color);
        color: #111;
      }
      
      .notification.error {
        background-color: #e74c3c;
        color: white;
      }
      
      .confirm-dialog {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1001;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
      }
      
      .confirm-dialog.show {
        opacity: 1;
        visibility: visible;
      }
      
      .confirm-content {
        background-color: white;
        border-radius: 12px;
        padding: 30px;
        max-width: 450px;
        width: 90%;
        text-align: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        transform: translateY(20px);
        transition: transform 0.3s;
      }
      
      .confirm-dialog.show .confirm-content {
        transform: translateY(0);
      }
      
      .confirm-content h3 {
        margin-top: 0;
        color: #333;
      }
      
      .confirm-content p {
        color: #666;
        margin-bottom: 25px;
      }
      
      .confirm-buttons {
        display: flex;
        justify-content: center;
        gap: 15px;
      }
      
      .confirm-cancel {
        padding: 10px 20px;
        background-color: #f1f1f1;
        color: #666;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
      }
      
      .confirm-cancel:hover {
        background-color: #e1e1e1;
      }
      
      .confirm-delete {
        padding: 10px 20px;
        background-color: #e74c3c;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
      }
      
      .confirm-delete:hover {
        background-color: #c0392b;
      }
      
      /* Style pour le menu déroulant du profil */
      .profile-dropdown {
        position: relative;
        display: inline-block;
      }
      
      .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: white;
        min-width: 200px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.2);
        z-index: 1;
        border-radius: 8px;
        overflow: hidden;
      }
      
      .dropdown-content a {
        color: #333;
        padding: 12px 16px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s;
      }
      
      .dropdown-content a:hover {
        background-color: #f5f5f5;
      }
      
      .profile-dropdown:hover .dropdown-content {
        display: block;
      }
      
      @media (max-width: 768px) {
        .activities-grid {
          grid-template-columns: 1fr;
        }
        
        .card-actions {
          flex-direction: column;
          gap: 10px;
        }
        
        .action-links {
          width: 100%;
          justify-content: space-between;
        }
        
        .card-button {
          padding: 10px;
          flex: 1;
          justify-content: center;
        }
      }
    </style>
  </head>
  <body>
    <header class="header">
      <a href="./main.php">
        <img
          class="logo"
          src="../Connexion-Inscription/logo-transparent-pdf.png"
          alt="Logo Synapse"
        />
      </a>
      <nav class="nav-links">
        <ul>
          <li><a href="#">Devenez Prestataire</a></li>
          <li><a href="../Concept/concept.html">Concept</a></li>
        </ul>
      </nav>

      <div class="icon">
        <i class="fa-regular fa-heart" aria-label="Favoris"></i>
        <a href="panier.html" class="panier-link" aria-label="Panier">
          <i class="fa-solid fa-cart-shopping"></i>
          <span class="panier-count" id="panier-count">0</span>
        </a>
        <div class="profile-dropdown">
          <a href="#" class="connexion-profil" aria-label="Profil">
            <i class="fa-solid fa-user"></i>
          </a>
          <div class="dropdown-content">
            <a href="../Connexion-Inscription/Connexion.html"><i class="fa-solid fa-right-to-bracket"></i> Connexion</a>
            <a href="mes-activites.php" class="active"><i class="fa-solid fa-calendar-days"></i> Mes activités</a>
            <a href="#"><i class="fa-solid fa-gear"></i> Paramètres</a>
          </div>
        </div>
      </div>
    </header>

    <h1 class="activities-page-title">Mes Activités Organisées</h1>

    <div class="page-container">
      <?php if ($successMessage): ?>
        <div class="notification success">
          <i class="fa-solid fa-circle-check"></i> <?php echo $successMessage; ?>
        </div>
      <?php endif; ?>
      
      <?php if ($errorMessage): ?>
        <div class="notification error">
          <i class="fa-solid fa-circle-exclamation"></i> <?php echo $errorMessage; ?>
        </div>
      <?php endif; ?>
      
      <div class="action-buttons">
        <a href="./jenis.php" class="create-button">
          <i class="fa-solid fa-plus"></i> Créer une nouvelle activité
        </a>
      </div>

      <div class="activities-grid">
        <?php 
        if ($result->num_rows > 0) {
            // Afficher chaque activité
            while($row = $result->fetch_assoc()) {
                // Générer une note aléatoire pour la démonstration
                $randomRating = rand(30, 50) / 10; // Note entre 3.0 et 5.0 
                
                // Liste des tags
                $tagList = $row["tags"] ? explode(',', $row["tags"]) : [];
                
                // Type de prix
                $isPaid = $row["prix"] > 0;
                $priceText = $isPaid ? number_format($row["prix"], 2) . " €" : "Gratuit";
                
                echo '<div class="activity-card" data-id="' . $row['id'] . '">';
                echo '<div class="card-content">';
                
                // Image avec conteneur de taille fixe
                echo '<div class="image-container">';
                if ($row["image_url"]) {
                    echo '<img src="' . htmlspecialchars($row["image_url"]) . '" alt="' . htmlspecialchars($row["titre"]) . '" />';
                } else {
                    echo '<img src="nature-placeholder.jpg" alt="placeholder" />';
                }
                echo '</div>';
                
                echo '<div class="tag-container">';
                
                // Affichage des tags (limité à 2)
                $displayedTags = 0;
                foreach ($tagList as $tag) {
                    if ($displayedTags < 2) {
                        $tagClass = getTagClass($tag);
                        echo '<span class="tag ' . $tagClass . '">' . ucfirst(str_replace('_', ' ', $tag)) . '</span>';
                        $displayedTags++;
                    }
                }
                
                // Afficher le statut gratuit/payant
                if ($isPaid) {
                    echo '<span class="tag">Payant</span>';
                } else {
                    echo '<span class="tag accent">Gratuit</span>';
                }
                
                echo '</div></div>';
                
                echo '<div class="card-info">';
                echo '<h3 class="card-title">' . htmlspecialchars($row["titre"]) . '</h3>';
                
                // Date ou période
                if ($row["date_ou_periode"]) {
                    echo '<p class="period"><i class="fa-regular fa-calendar"></i> ' . htmlspecialchars($row["date_ou_periode"]) . '</p>';
                }
                
                echo '<p class="price ' . ($isPaid ? '' : 'free') . '">' . $priceText . '</p>';
                
                echo '</div>';
                
                echo '<div class="card-actions">';
                echo '<div class="rating">' . getStars($randomRating) . '</div>';
                
                echo '<div class="action-links">';
                echo '<a href="modifier-activite.php?id=' . $row['id'] . '" class="card-button edit-button"><i class="fa-solid fa-pen"></i> Modifier</a>';
                echo '<button class="card-button delete-button" data-id="' . $row['id'] . '"><i class="fa-solid fa-trash"></i> Supprimer</button>';
                echo '</div>';
                
                echo '</div>';
                
                echo '</div>';
            }
        } else {
            echo '<div class="no-activities">';
            echo '<i class="fa-solid fa-calendar-xmark"></i>';
            echo '<p>Vous n\'avez pas encore créé d\'activités.</p>';
            echo '</div>';
        }
        ?>
      </div>
    </div>

    <!-- Confirmation dialog for deletion -->
    <div class="confirm-dialog" id="confirm-dialog">
      <div class="confirm-content">
        <h3>Confirmer la suppression</h3>
        <p>Êtes-vous sûr de vouloir supprimer cette activité ? Cette action est irréversible.</p>
        <div class="confirm-buttons">
          <button class="confirm-cancel" id="cancel-delete">Annuler</button>
          <button class="confirm-delete" id="confirm-delete">Supprimer</button>
        </div>
      </div>
    </div>

    <footer class="footer">
      <ul>
        <li><a href="#">FAQ</a></li>
        <li><a href="#">CGU</a></li>
        <li><a href="#">Mentions Légales</a></li>
      </ul>

      <ul>
        <li><i class="fa-solid fa-phone"></i> 06 01 02 03 04</li>
        <li><i class="fa-regular fa-envelope"></i> synapse@gmail.com</li>
      </ul>
      <ul>
        <li><i class="fa-brands fa-facebook-f"></i> synapse.off</li>
        <li><i class="fa-brands fa-instagram"></i> synapse.off</li>
      </ul>

      <ul>
        <li>Lundi - Vendredi : 9h à 20h</li>
        <li>Samedi : 10h à 16h</li>
      </ul>
    </footer>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Initialiser le panier s'il n'existe pas déjà
        if (!localStorage.getItem('synapse-cart')) {
          localStorage.setItem('synapse-cart', JSON.stringify([]));
        }
        
        // Mettre à jour le compteur du panier
        updateCartCount();
        
        // Gérer le popup de notification
        const notification = document.querySelector('.notification');
        if (notification) {
          setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
              notification.remove();
            }, 500);
          }, 5000);
        }
        
        // Gestion de la confirmation de suppression
        const confirmDialog = document.getElementById('confirm-dialog');
        const cancelDelete = document.getElementById('cancel-delete');
        const confirmDelete = document.getElementById('confirm-delete');
        let activityToDelete = null;
        
        // Afficher la boîte de dialogue de confirmation lors du clic sur le bouton de suppression
        document.querySelectorAll('.delete-button').forEach(button => {
          button.addEventListener('click', function() {
            activityToDelete = this.getAttribute('data-id');
            confirmDialog.classList.add('show');
          });
        });
        
        // Fermer la boîte de dialogue sans supprimer
        cancelDelete.addEventListener('click', function() {
          confirmDialog.classList.remove('show');
          activityToDelete = null;
        });
        
        // Confirmer la suppression et rediriger
        confirmDelete.addEventListener('click', function() {
          if (activityToDelete) {
            window.location.href = 'mes-activites.php?delete=' + activityToDelete;
          }
        });
        
        // Fonction pour mettre à jour le compteur du panier
        function updateCartCount() {
          const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
          const cartCount = document.getElementById('panier-count');
          if (cartCount) {
            cartCount.textContent = cart.length;
          }
        }
        
        // Rendre les cartes cliquables pour naviguer vers les détails
        document.querySelectorAll('.activity-card').forEach(card => {
          card.addEventListener('click', function(e) {
            // Ne pas rediriger si l'utilisateur a cliqué sur un bouton d'action
            if (e.target.closest('.card-button') || e.target.closest('.action-links')) {
              return;
            }
            
            const activityId = this.getAttribute('data-id');
            if (activityId) {
              window.location.href = 'activite.php?id=' + activityId;
            }
          });
          
          // Style au survol pour indiquer que c'est cliquable
          card.addEventListener('mouseenter', function() {
            this.style.cursor = 'pointer';
          });
        });
      });
    </script>
  </body>
</html>

<?php
$conn->close();
?>