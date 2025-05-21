<?php
session_start();
// Check if user is logged in, redirect if not
if(!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../Connexion-Inscription/login_form.php');
    exit();
}

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

// Get current user ID
$user_id = $_SESSION['user_id'];

// Récupérer les activités achetées par l'utilisateur
$sql = "SELECT a.*, 
        (SELECT GROUP_CONCAT(nom_tag) FROM tags WHERE activite_id = a.id) AS tags,
        aa.date_achat
        FROM activites a 
        JOIN activites_achats aa ON a.id = aa.activite_id
        WHERE aa.user_id = ?
        ORDER BY aa.date_achat DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

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
    <title>Mes Activités Inscrites | Synapse</title>
    <link rel="stylesheet" href="main.css" />
    <link rel="stylesheet" href="../TEMPLATE/teteaupied.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    />
    <style>
      /* Common styles - same as before */
      body { 
          font-family: Arial, sans-serif; 
          position: relative;
          overflow-x: hidden;
          min-height: 100vh;
          background: linear-gradient(135deg, rgba(228, 216, 200, 0.8) 0%, rgba(215, 225, 210, 0.8) 100%);
      }

      .activities-page-title {
          text-align: center;
          margin: 40px 0;
          color: var(--text-dark);
          font-size: 42px;
          position: relative;
          display: inline-block;
          font-family: 'Georgia', serif;
          width: 100%;
      }
      
      .activities-page-title::after {
          content: '';
          position: absolute;
          bottom: -10px;
          left: 50%;
          transform: translateX(-50%);
          width: 80px;
          height: 3px;
          background: linear-gradient(to right, var(--primary-color), var(--accent-color));
          border-radius: 2px;
      }
      
      .page-container {
          width: 90%;
          max-width: 1200px;
          margin: 30px auto 60px;
          position: relative;
          z-index: 2;
      }
      
      .action-buttons {
          text-align: center;
          margin-bottom: 40px;
      }
      
      .create-button {
          background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
          color: white;
          padding: 16px 30px;
          border-radius: 50px;
          text-decoration: none;
          font-weight: bold;
          display: inline-flex;
          align-items: center;
          gap: 12px;
          transition: all 0.4s ease;
          box-shadow: 0 12px 25px rgba(39, 94, 62, 0.3);
          position: relative;
          overflow: hidden;
      }
      
      .create-button::before {
          content: '';
          position: absolute;
          top: 0;
          left: -100%;
          width: 100%;
          height: 100%;
          background: linear-gradient(90deg, 
              rgba(255, 255, 255, 0) 0%, 
              rgba(255, 255, 255, 0.2) 50%, 
              rgba(255, 255, 255, 0) 100%);
          transform: skewX(-25deg);
          transition: left 0.7s ease;
          z-index: 1;
      }
      
      .create-button:hover {
          background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
          transform: translateY(-5px);
          box-shadow: 0 15px 35px rgba(39, 94, 62, 0.4);
      }
      
      .create-button:hover::before {
          left: 100%;
      }
      
      .create-button i, .create-button span {
          position: relative;
          z-index: 2;
      }
      
      /* Activities Grid Styling */
      .activities-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
          gap: 30px;
          position: relative;
          animation: fadeIn 1s forwards;
      }
      
      @keyframes fadeIn {
          from { opacity: 0; transform: translateY(20px); }
          to { opacity: 1; transform: translateY(0); }
      }
      
      /* No activities message */
      .no-activities {
          text-align: center;
          padding: 60px 30px;
          background-color: rgba(255, 255, 255, 0.7);
          border-radius: 20px;
          box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
          grid-column: 1/-1;
          animation: fadeIn 0.8s ease;
      }
      
      .no-activities i {
          font-size: 64px;
          color: #828977;
          margin-bottom: 25px;
          opacity: 0.5;
      }
      
      .no-activities h3 {
          font-size: 24px;
          color: #444;
          margin-bottom: 10px;
      }
      
      .no-activities p {
          color: #666;
          font-size: 18px;
      }
      
      /* Updated Card styling */
      .card {
          background-color: rgba(255, 255, 255, 0.9);
          backdrop-filter: blur(10px);
          border-radius: 20px;
          overflow: hidden;
          box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
          display: flex;
          flex-direction: column;
          transition: all 0.4s ease;
          border: 1px solid rgba(255, 255, 255, 0.7);
          position: relative;
          z-index: 1;
          animation: cardAppear 0.6s ease-out forwards;
          opacity: 0;
          transform: translateY(30px);
          /* No fixed height to allow content to determine size */
      }
      
      @keyframes cardAppear {
          to { opacity: 1; transform: translateY(0); }
      }
      
      .card:nth-child(3n+1) { animation-delay: 0.1s; }
      .card:nth-child(3n+2) { animation-delay: 0.2s; }
      .card:nth-child(3n+3) { animation-delay: 0.3s; }
      
      .card:hover {
          transform: translateY(-10px);
          box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
      }
      
      .image-container {
          height: 200px; /* Slightly reduced height */
          overflow: hidden;
          position: relative;
      }
      
      .card img {
          width: 100%;
          height: 100%;
          object-fit: cover;
          transition: transform 0.7s ease;
      }
      
      .card:hover img {
          transform: scale(1.1);
      }
      
      /* Price tag and tags */
      .price-tag {
          position: absolute;
          top: 15px;
          right: 15px;
          background: linear-gradient(135deg, rgba(148, 107, 45, 0.9) 0%, rgba(97, 70, 30, 0.9) 100%);
          color: white;
          padding: 6px 12px;
          border-radius: 30px;
          font-size: 14px;
          font-weight: 700;
          display: flex;
          align-items: center;
          gap: 6px;
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
          z-index: 10;
      }
      
      .price-tag.free {
          background: linear-gradient(135deg, rgba(69, 161, 99, 0.9) 0%, rgba(39, 94, 62, 0.9) 100%);
      }
      
      .tag {
          position: absolute;
          bottom: 15px;
          left: 15px;
          display: flex;
          gap: 8px;
          flex-wrap: wrap;
          z-index: 2;
          max-width: calc(100% - 30px);
      }
      
      .tags {
          background-color: rgba(60, 140, 92, 0.9);
          color: white;
          padding: 6px 12px;
          border-radius: 50px;
          font-size: 12px;
          font-weight: 600;
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
      }
      
      .tags.accent {
          background-color: rgba(233, 196, 106, 0.9);
          color: var(--text-dark);
      }
      
      .tags.secondary {
          background-color: rgba(148, 107, 45, 0.9);
          color: white;
      }
      
      /* Info section - reduced padding */
      .info {
          flex-grow: 1;
          display: flex;
          flex-direction: column;
          padding: 15px 20px;
          position: relative;
          background-color: white;
      }
      
      .card h3 {
          font-size: 18px;
          line-height: 1.4;
          margin: 0 0 10px 0;
          color: var(--text-dark);
          font-weight: 700;
          min-height: unset; /* Remove fixed height */
          display: -webkit-box;
          -webkit-line-clamp: 2;
          -webkit-box-orient: vertical;
          overflow: hidden;
      }
      
      .period {
          color: #666;
          margin: 0 0 5px 0;
          font-size: 14px;
          display: flex;
          align-items: center;
          gap: 8px;
      }
      
      /* Actions section */
      .actions {
          padding: 15px 20px;
          border-top: 1px solid #eee;
          background-color: #f9f9f9;
      }
      
      .rating {
          color: #f1c40f;
          font-size: 16px;
          display: flex;
          align-items: center;
          gap: 5px;
          margin-bottom: 10px;
      }
      
      .purchased-date {
          color: #666;
          font-size: 14px;
          margin-top: 5px;
          display: flex;
          align-items: center;
          gap: 8px;
      }
      
      .purchased-date i {
          color: #45cf91;
      }
      
      .view-button {
          background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
          color: white;
          padding: 10px 0;
          border-radius: 50px;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          font-weight: 600;
          font-size: 14px;
          cursor: pointer;
          transition: all 0.3s ease;
          border: none;
          width: 100%;
          margin-top: 10px;
      }
      
      .view-button:hover {
          background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
          transform: translateY(-3px);
          box-shadow: 0 8px 15px rgba(69, 161, 99, 0.3);
      }
      
      /* Notification and confirm dialog */
      .notification {
          position: fixed;
          top: 30px;
          left: 50%;
          transform: translateX(-50%);
          padding: 18px 30px;
          border-radius: 15px;
          display: flex;
          align-items: center;
          gap: 12px;
          font-weight: 600;
          z-index: 1000;
          box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
          opacity: 0;
          animation: notificationFadeIn 0.5s forwards;
          backdrop-filter: blur(10px);
          border: 1px solid rgba(255, 255, 255, 0.2);
      }
      
      @keyframes notificationFadeIn {
          to { opacity: 1; transform: translateX(-50%) translateY(0); }
      }
      
      .notification.success {
          background-color: rgba(69, 161, 99, 0.9);
          color: white;
      }
      
      .notification.error {
          background-color: rgba(231, 76, 60, 0.9);
          color: white;
      }
      
      /* Navigation tabs */
      .activities-tabs {
          display: flex;
          justify-content: center;
          gap: 20px;
          margin-bottom: 40px;
      }
      
      .tab-button {
          padding: 15px 30px;
          background-color: rgba(255, 255, 255, 0.7);
          border-radius: 50px;
          cursor: pointer;
          font-weight: 600;
          transition: all 0.3s ease;
          color: #666;
          display: flex;
          align-items: center;
          gap: 10px;
          box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
          border: none;
          text-decoration: none;
      }
      
      .tab-button.active {
          background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
          color: white;
          box-shadow: 0 10px 25px rgba(69, 161, 99, 0.3);
      }
      
      .tab-button:hover:not(.active) {
          background-color: rgba(255, 255, 255, 0.9);
          transform: translateY(-3px);
          box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
      }
      
      /* Responsive */
      @media (max-width: 992px) {
          .activities-grid {
              grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
          }
          
          .activities-tabs {
              flex-direction: column;
              align-items: center;
              gap: 15px;
          }
          
          .tab-button {
              width: 100%;
              justify-content: center;
          }
      }
      
      @media (max-width: 768px) {
          .activities-page-title {
              font-size: 32px;
          }
      }
      
      @media (max-width: 576px) {
          .activities-grid {
              grid-template-columns: 1fr;
          }
      }

      /* Particle animation */
      .particle {
          position: absolute;
          width: 8px;
          height: 8px;
          border-radius: 50%;
          background-color: rgba(255, 255, 255, 0.6);
          box-shadow: 0 0 10px 2px rgba(255, 255, 255, 0.2);
          pointer-events: none;
          z-index: 0;
          animation: particleFloat 15s linear infinite;
      }

      @keyframes particleFloat {
          0% {
              transform: translateY(0) translateX(0);
              opacity: 0;
          }
          10% {
              opacity: 0.5;
          }
          90% {
              opacity: 0.3;
          }
          100% {
              transform: translateY(-100vh) translateX(20vw);
              opacity: 0;
          }
      }
    </style>
  </head>
  <body>
    <?php
    // Inclure le header
    include '../TEMPLATE/Nouveauhead.php';
    ?>

    <h1 class="activities-page-title">Mes Activités Inscrites</h1>

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
      
      <div class="activities-tabs">
        <a href="mes-activites.php" class="tab-button">
          <i class="fa-solid fa-pencil"></i> Activités créées
        </a>
        <a href="mes-activites-registered.php" class="tab-button active">
          <i class="fa-solid fa-calendar-check"></i> Activités inscrites
        </a>
      </div>

      <div class="activities-grid">
        <?php 
        $purchasedActivityCount = 0;
        
        if ($result->num_rows > 0) {
            // Parcourir toutes les activités achetées
            while($row = $result->fetch_assoc()) {
                $purchasedActivityCount++;
                
                // Générer une note aléatoire pour la démonstration
                $randomRating = (($row['id'] * 7) % 21 + 30) / 10; // Note entre 3.0 et 5.0
                
                // Liste des tags
                $tagList = $row["tags"] ? explode(',', $row["tags"]) : [];
                
                // Type de prix
                $isPaid = $row["prix"] > 0;
                
                echo '<div class="card" data-id="' . $row['id'] . '">';
                echo '<div class="content">';
                
                // Image container
                echo '<div class="image-container">';
                if ($row["image_url"]) {
                    echo '<img src="' . htmlspecialchars($row["image_url"]) . '" alt="' . htmlspecialchars($row["titre"]) . '" />';
                } else {
                    echo '<img src="nature-placeholder.jpg" alt="placeholder" />';
                }
                echo '</div>';
                
                // Price tag
                if ($isPaid) {
                    echo '<div class="price-tag">';
                    echo '<i class="fa-solid fa-euro-sign"></i> ' . number_format($row["prix"], 2) . ' €';
                    echo '</div>';
                } else {
                    echo '<div class="price-tag free">';
                    echo '<i class="fa-solid fa-gift"></i> Gratuit';
                    echo '</div>';
                }
                
                // Tags
                echo '<div class="tag">';
                $displayedTags = 0;
                foreach ($tagList as $tag) {
                    if ($displayedTags < 2) {
                        $tagClass = getTagClass($tag);
                        echo '<span class="tags ' . $tagClass . '" data-tag="' . htmlspecialchars($tag) . '">' . ucfirst(str_replace('_', ' ', $tag)) . '</span>';
                        $displayedTags++;
                    }
                }
                echo '</div></div>';
                
                // Info
                echo '<div class="info">';
                echo '<h3>' . htmlspecialchars($row["titre"]) . '</h3>';
                
                if ($row["date_ou_periode"]) {
                    echo '<p class="period"><i class="fa-regular fa-calendar"></i> ' . htmlspecialchars($row["date_ou_periode"]) . '</p>';
                }
                
                echo '</div>';
                
                // Actions 
                echo '<div class="actions">';
                echo '<div class="rating">' . getStars($randomRating) . '</div>';
                
                // Date d'achat
                echo '<div class="purchased-date">';
                echo '<i class="fa-solid fa-check-circle"></i> Inscrit le ' . date('d/m/Y', strtotime($row["date_achat"]));
                echo '</div>';
                
                echo '<button class="view-button" onclick="window.location.href=\'activite.php?id=' . $row['id'] . '\'"><i class="fa-solid fa-eye"></i> Voir l\'activité</button>';
                
                echo '</div>';
                echo '</div>';
            }
        }
        
        // Afficher un message si aucune activité n'a été trouvée
        if ($purchasedActivityCount == 0) {
            echo '<div class="no-activities">';
            echo '<i class="fa-solid fa-calendar-xmark"></i>';
            echo '<h3>Vous n\'êtes inscrit à aucune activité</h3>';
            echo '<p>Explorez notre catalogue d\'activités et inscrivez-vous à celles qui vous intéressent.</p>';
            echo '<a href="main.php" class="create-button" style="margin-top: 20px;">';
            echo '<i class="fa-solid fa-compass"></i> <span>Explorer les activités</span>';
            echo '</a>';
            echo '</div>';
        }
        ?>
      </div>
    </div>

    <?php
    // Inclure le footer
    include '../TEMPLATE/footer.php';
    ?>

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
        
        // Rendre les cartes cliquables pour naviguer vers les détails
        document.querySelectorAll('.card').forEach(card => {
          card.addEventListener('click', function(e) {
            // Ne pas rediriger si l'utilisateur a cliqué sur un bouton d'action
            if (e.target.closest('.view-button')) {
              return;
            }
            
            const activityId = this.getAttribute('data-id');
            if (activityId) {
              window.location.href = 'activite.php?id=' + activityId;
            }
          });
        });
        
        // Fonction pour mettre à jour le compteur du panier
        function updateCartCount() {
          const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
          const cartCount = document.getElementById('panier-count');
          if (cartCount) {
            cartCount.textContent = cart.length;
          }
        }
        
        // Create animated particles for background effect
        for (let i = 0; i < 10; i++) {
            const particle = document.createElement('div');
            particle.classList.add('particle');
            
            // Random size and position
            const size = Math.random() * 5 + 3;
            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            particle.style.left = `${Math.random() * 100}vw`;
            particle.style.top = `${Math.random() * 100}vh`;
            
            // Random animation
            particle.style.animationDuration = `${Math.random() * 15 + 10}s`;
            particle.style.animationDelay = `${Math.random() * 5}s`;
            
            // Add to body
            document.body.appendChild(particle);
        }
      });
    </script>
  </body>
</html>

<?php
$conn->close();
?>