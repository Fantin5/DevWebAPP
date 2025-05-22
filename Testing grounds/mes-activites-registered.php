<?php
session_start();

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "activity";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Now that we have the connection, require tag setup and initialize TagManager
require_once 'tag_setup.php';
$tagManager = new TagManager($conn);
$tagDefinitions = $tagManager->getAllTags();

// Verify user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Connexion-Inscription/login_form.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Récupérer les définitions de tags depuis la base de données
$tagClasses = ['primary', 'secondary', 'accent']; // Classes CSS alternées
$i = 0;
foreach ($tagDefinitions as $name => $definition) {
    $tagDefinitions[$name]['class'] = $tagClasses[$i % count($tagClasses)];
    $i++;
}

// Variables pour les messages
$successMessage = '';
$errorMessage = '';

// Updated activity query
$sql = "SELECT a.*, 
        GROUP_CONCAT(td.name) AS tags,
        GROUP_CONCAT(td.display_name SEPARATOR '|') AS tag_display_names,
        aa.date_achat
        FROM activites a 
        LEFT JOIN activity_tags at ON a.id = at.activity_id
        LEFT JOIN tag_definitions td ON at.tag_definition_id = td.id
        JOIN activites_achats aa ON a.id = aa.activite_id
        WHERE aa.user_id = ?
        GROUP BY a.id
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

// Function to determine the CSS class for tags using database definitions
function getTagClass($tag) {
    global $tagDefinitions;
    
    // Use the assigned class from tag_definitions
    return isset($tagDefinitions[$tag]) ? $tagDefinitions[$tag]['class'] : 'primary';
}

// Function to get the display name for a tag using database definitions
function getTagDisplayName($tag, $tagDisplayNames = null, $index = null) {
    global $tagDefinitions;
    
    // First try to get from the display names array (from SQL query)
    if ($tagDisplayNames && $index !== null && isset($tagDisplayNames[$index])) {
        return $tagDisplayNames[$index];
    }
    
    // Otherwise use the tag definitions
    return isset($tagDefinitions[$tag]) ? $tagDefinitions[$tag]['display_name'] : ucfirst(str_replace('_', ' ', $tag));
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
      /* Nature-inspired variables */
      :root {
          --primary-color: #3c8c5c;
          --primary-light: #61b980;
          --primary-dark: #275e3e;
          --secondary-color: #946b2d;
          --secondary-light: #c89e52;
          --accent-color: #e9c46a;
          --text-dark: #2d5a3d;
          --bg-gradient: linear-gradient(135deg, #f8fff9 0%, #f0f7f2 100%);
      }

      /* Enhanced body background */
      body { 
          font-family: Arial, sans-serif; 
          position: relative;
          overflow-x: hidden;
          min-height: 100vh;
          background: var(--bg-gradient);
          background-image: 
              radial-gradient(circle at 20% 20%, rgba(69, 161, 99, 0.1) 0%, transparent 50%),
              radial-gradient(circle at 80% 80%, rgba(233, 196, 106, 0.1) 0%, transparent 50%);
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
      
      /* Modern Activities Grid Styling */
      .activities-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
          gap: 1.5rem;
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
          background: rgba(255, 255, 255, 0.9);
          backdrop-filter: blur(10px);
          border: 1px solid rgba(255, 255, 255, 0.8);
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
      
      /* Modern Activity Card styling */
      .activity-card {
          background: rgba(255, 255, 255, 0.9);
          backdrop-filter: blur(10px);
          border: 1px solid rgba(255, 255, 255, 0.8);
          transition: all 0.4s ease;
          border-radius: 16px;
          box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
          overflow: hidden;
          cursor: pointer;
          animation: cardAppear 0.6s ease-out forwards;
          opacity: 0;
          transform: translateY(30px);
      }
      
      @keyframes cardAppear {
          to { opacity: 1; transform: translateY(0); }
      }
      
      .activity-card:nth-child(3n+1) { animation-delay: 0.1s; }
      .activity-card:nth-child(3n+2) { animation-delay: 0.2s; }
      .activity-card:nth-child(3n+3) { animation-delay: 0.3s; }
      
      .activity-card:hover {
          transform: translateY(-8px) scale(1.02);
          box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
      }
      
      .card-image {
          position: relative;
          height: 200px;
          overflow: hidden;
          border-radius: 16px 16px 0 0;
      }
      
      .card-image img {
          width: 100%;
          height: 100%;
          object-fit: cover;
          transition: transform 0.3s ease;
      }
      
      .activity-card:hover .card-image img {
          transform: scale(1.05);
      }
      
      /* Price badge and urgency badge */
      .price-badge {
          position: absolute;
          top: 1rem;
          right: 1rem;
          background: rgba(45, 90, 61, 0.9);
          color: white;
          padding: 0.5rem 0.75rem;
          border-radius: 20px;
          font-size: 0.85rem;
          font-weight: 600;
          backdrop-filter: blur(8px);
          display: flex;
          align-items: center;
          gap: 0.5rem;
      }
      
      .price-badge.free {
          background: rgba(69, 161, 99, 0.9);
      }
      
      .purchased-badge {
          position: absolute;
          top: 1rem;
          left: 1rem;
          background: rgba(69, 161, 99, 0.9);
          color: white;
          padding: 0.5rem 0.75rem;
          border-radius: 20px;
          font-size: 0.8rem;
          font-weight: 600;
          backdrop-filter: blur(8px);
          display: flex;
          align-items: center;
          gap: 0.5rem;
      }
      
      /* Card content */
      .card-content {
          padding: 1.5rem;
      }
      
      .card-content h3 {
          font-size: 1.25rem;
          font-weight: 700;
          color: #1f2937;
          margin-bottom: 0.5rem;
          line-height: 1.3;
          display: -webkit-box;
          -webkit-line-clamp: 2;
          -webkit-box-orient: vertical;
          overflow: hidden;
      }
      
      .activity-period {
          color: #6b7280;
          font-size: 0.9rem;
          margin-bottom: 1rem;
          display: flex;
          align-items: center;
          gap: 0.5rem;
      }
      
      .activity-period i {
          color: var(--primary-color);
      }
      
      .activity-tags {
          display: flex;
          flex-wrap: wrap;
          gap: 0.5rem;
          margin-bottom: 1rem;
      }
      
      .activity-tag {
          background: var(--primary-light);
          color: white;
          padding: 0.35rem 0.75rem;
          border-radius: 25px;
          font-size: 0.85rem;
          font-weight: 500;
          transition: all 0.3s ease;
          cursor: pointer;
      }
      
      .activity-tag:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 12px rgba(69, 161, 99, 0.2);
      }
      
      .activity-tag.accent {
          background: var(--accent-color);
          color: var(--text-dark);
      }
      
      .activity-tag.secondary {
          background: var(--secondary-color);
          color: white;
      }
      
      .activity-tag.primary {
          background: var(--primary-color);
          color: white;
      }
      
      .card-footer {
          display: flex;
          justify-content: space-between;
          align-items: center;
          padding: 1rem 1.5rem;
          border-top: 1px solid #f3f4f6;
          background: #f9f9f9;
      }
      
      .activity-rating {
          display: flex;
          align-items: center;
          gap: 0.25rem;
          color: #fbbf24;
          font-size: 0.9rem;
      }
      
      .rating-value {
          color: #6b7280;
          font-size: 0.85rem;
          margin-left: 0.25rem;
      }
      
      .purchased-date {
          color: #6b7280;
          font-size: 0.85rem;
          display: flex;
          align-items: center;
          gap: 0.5rem;
          margin-bottom: 1rem;
      }
      
      .purchased-date i {
          color: var(--primary-color);
      }
      
      .view-button {
          background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
          color: white;
          padding: 0.75rem 1.5rem;
          border-radius: 8px;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 8px;
          font-weight: 600;
          font-size: 0.9rem;
          cursor: pointer;
          transition: all 0.3s ease;
          border: none;
          text-decoration: none;
      }
      
      .view-button:hover {
          background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
          transform: translateY(-2px);
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
          flex-wrap: wrap;
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
          position: relative;
          overflow: hidden;
      }
      
      .tab-button::before {
          content: '';
          position: absolute;
          top: 0;
          left: -100%;
          width: 100%;
          height: 100%;
          background: linear-gradient(90deg, 
              rgba(255, 255, 255, 0) 0%, 
              rgba(69, 161, 99, 0.1) 50%, 
              rgba(255, 255, 255, 0) 100%);
          transform: skewX(-25deg);
          transition: left 0.5s ease;
          z-index: -1;
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
      
      .tab-button:hover::before {
          left: 100%;
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
              max-width: 300px;
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
        <a href="activites.php" class="tab-button">
          <i class="fa-solid fa-compass"></i> Explorer les activités
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
                $tagDisplayNames = $row["tag_display_names"] ? explode('|', $row["tag_display_names"]) : [];
                
                // Type de prix
                $isPaid = $row["prix"] > 0;
                
                echo '<div class="activity-card" data-id="' . $row['id'] . '">';
                
                // Card image
                echo '<div class="card-image">';
                if ($row["image_url"]) {
                    echo '<img src="' . htmlspecialchars($row["image_url"]) . '" alt="' . htmlspecialchars($row["titre"]) . '" />';
                } else {
                    echo '<img src="nature-placeholder.jpg" alt="placeholder" />';
                }
                
                // Price badge
                if ($isPaid) {
                    echo '<div class="price-badge">';
                    echo '<i class="fa-solid fa-euro-sign"></i> ' . number_format($row["prix"], 2) . ' €';
                    echo '</div>';
                } else {
                    echo '<div class="price-badge free">';
                    echo '<i class="fa-solid fa-gift"></i> Gratuit';
                    echo '</div>';
                }
                
                // Purchased badge
                echo '<div class="purchased-badge">';
                echo '<i class="fa-solid fa-check-circle"></i> Inscrit';
                echo '</div>';
                
                echo '</div>';
                
                // Card content
                echo '<div class="card-content">';
                echo '<h3>' . htmlspecialchars($row["titre"]) . '</h3>';
                
                if ($row["date_ou_periode"]) {
                    echo '<p class="activity-period"><i class="fa-regular fa-calendar"></i> ' . htmlspecialchars($row["date_ou_periode"]) . '</p>';
                }
                
                // Date d'achat
                echo '<div class="purchased-date">';
                echo '<i class="fa-solid fa-shopping-cart"></i> Inscrit le ' . date('d/m/Y', strtotime($row["date_achat"]));
                echo '</div>';
                
                // Tags - show up to 3 tags with display names
                echo '<div class="activity-tags">';
                $displayedTags = 0;
                foreach ($tagList as $index => $tag) {
                    if ($displayedTags < 3 && $tag !== 'gratuit' && $tag !== 'payant') {
                        $tagClass = getTagClass($tag);
                        $displayName = getTagDisplayName($tag, $tagDisplayNames, $index);
                        echo '<span class="activity-tag ' . $tagClass . '" data-tag="' . htmlspecialchars($tag) . '">' . htmlspecialchars($displayName) . '</span>';
                        $displayedTags++;
                    }
                }
                echo '</div>';
                
                echo '</div>';
                
                // Card footer 
                echo '<div class="card-footer">';
                echo '<div class="activity-rating">' . getStars($randomRating) . '</div>';
                echo '<a href="activite.php?id=' . $row['id'] . '" class="view-button"><i class="fa-solid fa-eye"></i> Voir l\'activité</a>';
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
            echo '<a href="activites.php" class="create-button" style="margin-top: 20px;">';
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
        document.querySelectorAll('.activity-card').forEach(card => {
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
$stmt->close();
$conn->close();
?>
<!-- cvq -->