<?php
// Démarrer la session
session_start();

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

// Définir le titre de la page
$page_title = "Accueil - Synapse";

// Récupérer les activités depuis la base de données
$sql = "SELECT a.*, 
        (SELECT GROUP_CONCAT(nom_tag) FROM tags WHERE activite_id = a.id) AS tags,
        DATEDIFF(STR_TO_DATE(SUBSTRING_INDEX(date_ou_periode, ' - ', -1), '%d/%m/%Y'), NOW()) as days_remaining
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
        'randonnee' => 'accent',
        'jardinage' => 'primary',
        'meditation' => 'secondary',
        'artisanat' => 'accent'
    ];
    
    return isset($tagClasses[$tag]) ? $tagClasses[$tag] : '';
}

// Function to check if an activity is ending soon (7 days or less)
function isEndingSoon($activity) {
    // If days_remaining is numerical and between 0 and 7
    if (isset($activity['days_remaining']) && is_numeric($activity['days_remaining']) && $activity['days_remaining'] >= 0 && $activity['days_remaining'] <= 7) {
        return true;
    }
    
    // For activities with date format "DD/MM/YYYY - DD/MM/YYYY"
    if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})\s*-\s*(\d{1,2})\/(\d{1,2})\/(\d{4})/', $activity['date_ou_periode'], $matches)) {
        $endDay = $matches[4];
        $endMonth = $matches[5];
        $endYear = $matches[6];
        
        $endDate = new DateTime("$endYear-$endMonth-$endDay");
        $now = new DateTime();
        $diff = $now->diff($endDate);
        
        // If end date is in the future and within 7 days
        if (!$diff->invert && $diff->days <= 7) {
            return true;
        }
    }
    
    return false;
}

// Function to get days remaining for display
function getDaysRemaining($activity) {
    if (isset($activity['days_remaining']) && is_numeric($activity['days_remaining'])) {
        return $activity['days_remaining'];
    }
    
    if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})\s*-\s*(\d{1,2})\/(\d{1,2})\/(\d{4})/', $activity['date_ou_periode'], $matches)) {
        $endDay = $matches[4];
        $endMonth = $matches[5];
        $endYear = $matches[6];
        
        $endDate = new DateTime("$endYear-$endMonth-$endDay");
        $now = new DateTime();
        $diff = $now->diff($endDate);
        
        if (!$diff->invert) {
            return $diff->days;
        }
    }
    
    return null;
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="main.css" />
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

<!-- Floating leaf animation elements -->
<div class="floating-leaf leaf-1"></div>
<div class="floating-leaf leaf-2"></div>
<div class="floating-leaf leaf-3"></div>
<div class="floating-leaf leaf-4"></div>

<!-- Enhanced Carousel Section -->
<div class="carrousel">
  <!-- Images container -->
  <div class="carrousel-images">
    <div class="carrousel-slide">
      <img src="./images/359834.jpg" alt="Louvre" />
      <div class="carrousel-caption">
        <h3>Musée du Louvre</h3>
        <p>Plus grand musée du monde, abritant la Joconde, la Vénus de Milo et des milliers d'œuvres d'art de l'Antiquité au XIXᵉ siècle.</p>
      </div>
    </div>
    <div class="carrousel-slide">
      <img src="./images/notre-dame-de-paris-cathedral-paris-france.webp" alt="Notre-Dame" />
      <div class="carrousel-caption">
        <h3>Cathédrale Notre-Dame</h3>
        <p>Chef-d'œuvre de l'architecture gothique, admirez ses vitraux, ses gargouilles et montez dans les tours pour approcher les fameux clochers.</p>
      </div>
    </div>
    <div class="carrousel-slide">
      <img src="./images/sports.jpg" alt="Jardin du Luxembourg" />
      <div class="carrousel-caption">
        <h3>Marathon dans les Buttes-Chaumont</h3>
        <p>Repoussez vos limites avec des activités de plein air excitantes et des aventures</p>
      </div>
    </div>
    <div class="carrousel-slide">
      <img src="./images/art.jpg" alt="Art Workshops" />
      <div class="carrousel-caption">
        <h3>Ateliers d'Art</h3>
        <p>Exprimez votre créativité dans nos studios d'art professionnels</p>
      </div>
    </div>
    <div class="carrousel-slide">
      <img src="./images/yoga.jpg" alt="Wellness Retreats" />
      <div class="carrousel-caption">
        <h3>Retraites Bien-être</h3>
        <p>Trouvez votre paix intérieure avec des sessions de méditation et de yoga</p>
      </div>
    </div>
  </div>
  
  <!-- Navigation buttons -->
  <button class="carrousel-button prev">
    <i class="fa fa-chevron-left" aria-hidden="true"></i>
  </button>
  <button class="carrousel-button next">
    <i class="fa fa-chevron-right" aria-hidden="true"></i>
  </button>
  
  <!-- Position indicators -->
  <div class="carrousel-indicators">
    <div class="carrousel-indicator active"></div>
    <div class="carrousel-indicator"></div>
    <div class="carrousel-indicator"></div>
    <div class="carrousel-indicator"></div>
    <div class="carrousel-indicator"></div>
  </div>
  
  <!-- Progress bar for auto-sliding -->
  <div class="carrousel-progress"></div>
</div>

<!-- Enhanced Welcome Section -->
<div class="welcome-section">
  <div class="welcome-content">
    <h1>Bienvenue sur <span>SYNAPSE</span></h1>
    <p>Un espace où partager des moments uniques et découvrir des activités exceptionnelles</p>
    <div class="welcome-buttons">
      <a href="activites.php" class="welcome-btn primary">Découvrir les activités</a>
      <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
      <a href="./jenis.php" class="welcome-btn secondary">
        <i class="fa-solid fa-plus"></i> Créer une activité
      </a>
      <?php else: ?>
      <a href="../Connexion-Inscription/login_form.php" class="welcome-btn secondary">
        <i class="fa-solid fa-user"></i> Se connecter
      </a>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Section dernière chance avec countdown -->
<section class="activities-section last-chance-section">
  <div class="section-header with-accent">
    <h2>Dernière Chance !</h2>
    <p>Ne manquez pas ces activités qui se terminent bientôt</p>
  </div>
  
  <div class="countdown-timer">
    <div class="countdown-unit">
      <div class="countdown-number" id="countdown-days">07</div>
      <div class="countdown-label">Jours</div>
    </div>
    <div class="countdown-unit">
      <div class="countdown-number" id="countdown-hours">23</div>
      <div class="countdown-label">Heures</div>
    </div>
    <div class="countdown-unit">
      <div class="countdown-number" id="countdown-minutes">59</div>
      <div class="countdown-label">Minutes</div>
    </div>
    <div class="countdown-unit">
      <div class="countdown-number" id="countdown-seconds">59</div>
      <div class="countdown-label">Secondes</div>
    </div>
  </div>
  
  <div class="activities-grid" id="last-chance-grid">
    <?php
    // Réinitialiser le pointeur du résultat
    if ($result->num_rows > 0) {
        $result->data_seek(0);
        $lastChanceCount = 0;
        
        while($row = $result->fetch_assoc()) {
            // Vérifier si l'activité se termine bientôt
            if (isEndingSoon($row) && $lastChanceCount < 4) {
                $lastChanceCount++;
                $randomRating = rand(35, 50) / 10;
                $tagList = $row["tags"] ? explode(',', $row["tags"]) : [];
                $isPaid = $row["prix"] > 0;
                $daysRemaining = getDaysRemaining($row);
                
                echo '<div class="featured-card" data-id="' . $row['id'] . '">'; // Added data-id attribute
                echo '<div class="content">';
                
                echo '<div class="image-container">';
                if ($row["image_url"]) {
                    echo '<img src="' . htmlspecialchars($row["image_url"]) . '" alt="' . htmlspecialchars($row["titre"]) . '" />';
                } else {
                    echo '<img src="nature-placeholder.jpg" alt="placeholder" />';
                }
                echo '</div>';
                
                // Show days remaining
                echo '<div class="last-chance-badge"><i class="fa-solid fa-clock"></i> ';
                if ($daysRemaining == 0) {
                    echo 'Dernier jour !';
                } else if ($daysRemaining == 1) {
                    echo 'Termine demain !';
                } else {
                    echo 'Plus que ' . $daysRemaining . ' jours !';
                }
                echo '</div>';
                
                echo '<div class="tag">';
                $displayedTags = 0;
                foreach ($tagList as $tag) {
                    if ($displayedTags < 2) {
                        $tagClass = getTagClass($tag);
                        echo '<span class="tags ' . $tagClass . '">' . ucfirst(str_replace('_', ' ', $tag)) . '</span>';
                        $displayedTags++;
                    }
                }
                
                if ($isPaid) {
                    echo '<span class="tags">Payant</span>';
                } else {
                    echo '<span class="tags accent">Gratuit</span>';
                }
                
                echo '</div></div>';
                
                echo '<div class="info">';
                echo '<h3>' . htmlspecialchars($row["titre"]) . '</h3>';
                
                if ($row["date_ou_periode"]) {
                    echo '<p class="period"><i class="fa-regular fa-calendar"></i> ' . htmlspecialchars($row["date_ou_periode"]) . '</p>';
                }
                
                echo '<div class="featured-rating">' . getStars($randomRating) . '</div>';
                
                echo '</div>';
                
                echo '<div class="actions">';
                echo '<button class="add-to-cart-button full-width" data-id="' . $row['id'] . '" 
                    data-title="' . htmlspecialchars($row['titre']) . '" 
                    data-price="' . $row['prix'] . '" 
                    data-image="' . htmlspecialchars($row['image_url'] ? $row['image_url'] : 'nature-placeholder.jpg') . '" 
                    data-period="' . htmlspecialchars($row['date_ou_periode']) . '" 
                    data-tags="' . htmlspecialchars($row['tags']) . '">
                    <i class="fa-solid fa-cart-shopping"></i> Ajouter au panier
                    </button>';
                echo '</div>';
                
                echo '</div>';
            }
        }
        
        if ($lastChanceCount == 0) {
            echo '<div class="no-activities" style="grid-column: 1/-1; text-align: center; padding: 50px; background-color: rgba(255,255,255,0.7); border-radius: 15px;">';
            echo '<i class="fa-regular fa-calendar-check" style="font-size: 48px; color: #828977; margin-bottom: 20px;"></i>';
            echo '<h3>Aucune activité en fin de période actuellement</h3>';
            echo '<p>Toutes nos activités sont encore disponibles pour un bon moment !</p>';
            echo '</div>';
        }
    }
    ?>
  </div>
</section>

<!-- Section activités gratuites avec slider amélioré -->
<section class="activities-section free-activities-section">
  <div class="section-header">
    <h2>Activités Gratuites</h2>
    <p>Découvrez notre sélection d'activités sans débourser un centime</p>
  </div>
  
  <div class="activities-slider" id="free-activities-slider">
    <?php
    // Reset the result pointer
    if ($result->num_rows > 0) {
        $result->data_seek(0);
        $freeCount = 0;
        
        while($row = $result->fetch_assoc()) {
            if ($row["prix"] == 0 && $freeCount < 5) {
                $randomRating = rand(30, 50) / 10;
                $tagList = $row["tags"] ? explode(',', $row["tags"]) : [];
                
                echo '<div class="featured-card" data-id="' . $row['id'] . '">'; // Added data-id attribute
                echo '<div class="content">';
                
                echo '<div class="image-container">';
                if ($row["image_url"]) {
                    echo '<img src="' . htmlspecialchars($row["image_url"]) . '" alt="' . htmlspecialchars($row["titre"]) . '" />';
                } else {
                    echo '<img src="nature-placeholder.jpg" alt="placeholder" />';
                }
                echo '</div>';
                
                echo '<div class="tag">';
                $displayedTags = 0;
                foreach ($tagList as $tag) {
                    if ($displayedTags < 2) {
                        $tagClass = getTagClass($tag);
                        echo '<span class="tags ' . $tagClass . '">' . ucfirst(str_replace('_', ' ', $tag)) . '</span>';
                        $displayedTags++;
                    }
                }
                echo '<span class="tags accent">Gratuit</span>';
                echo '</div></div>';
                
                echo '<div class="info">';
                echo '<h3>' . htmlspecialchars($row["titre"]) . '</h3>';
                
                if ($row["date_ou_periode"]) {
                    echo '<p class="period"><i class="fa-regular fa-calendar"></i> ' . htmlspecialchars($row["date_ou_periode"]) . '</p>';
                }
                
                // Add rating to info section
                echo '<div class="featured-rating">' . getStars($randomRating) . '</div>';
                
                echo '</div>';
                
                echo '<div class="actions">';
                
                // Full width button
                echo '<button class="add-to-cart-button full-width" data-id="' . $row['id'] . '" 
                    data-title="' . htmlspecialchars($row['titre']) . '" 
                    data-price="' . $row['prix'] . '" 
                    data-image="' . htmlspecialchars($row['image_url'] ? $row['image_url'] : 'nature-placeholder.jpg') . '" 
                    data-period="' . htmlspecialchars($row['date_ou_periode']) . '" 
                    data-tags="' . htmlspecialchars($row['tags']) . '">
                    <i class="fa-solid fa-cart-shopping"></i> Ajouter au panier
                    </button>';
                
                echo '</div>';
                
                echo '</div>';
                
                $freeCount++;
            }
        }
        
        if ($freeCount == 0) {
            echo '<p class="no-activities">Aucune activité gratuite disponible pour le moment.</p>';
        }
    }
    ?>
  </div>
  
  <div class="slider-controls">
    <button class="slider-button prev"><i class="fa-solid fa-chevron-left"></i></button>
    <button class="slider-button next"><i class="fa-solid fa-chevron-right"></i></button>
  </div>
</section>

<!-- Section meilleures activités -->
<section class="activities-section best-rated-section">
  <div class="section-header">
    <h2>Meilleures Activités</h2>
    <p>Les activités les plus appréciées par notre communauté</p>
  </div>
  
  <div class="activities-grid" id="best-activities-grid">
    <?php
    // Réinitialiser le pointeur du résultat
    if ($result->num_rows > 0) {
        $result->data_seek(0);
        
        // Créer un tableau pour stocker les activités et leurs notes
        $ratedActivities = [];
        
        while($row = $result->fetch_assoc()) {
            $randomRating = rand(30, 50) / 10;
            $ratedActivities[] = [
                'rating' => $randomRating,
                'data' => $row
            ];
        }
        
        // Trier les activités par note (décroissante)
        usort($ratedActivities, function($a, $b) {
            return $b['rating'] <=> $a['rating'];
        });
        
        // Afficher les 4 meilleures activités
        $count = 0;
        foreach ($ratedActivities as $activity) {
            if ($count < 4) {
                $row = $activity['data'];
                $randomRating = $activity['rating'];
                $tagList = $row["tags"] ? explode(',', $row["tags"]) : [];
                $isPaid = $row["prix"] > 0;
                
                echo '<div class="featured-card" data-id="' . $row['id'] . '">'; // Added data-id attribute
                echo '<div class="content">';
                
                echo '<div class="image-container">';
                if ($row["image_url"]) {
                    echo '<img src="' . htmlspecialchars($row["image_url"]) . '" alt="' . htmlspecialchars($row["titre"]) . '" />';
                } else {
                    echo '<img src="nature-placeholder.jpg" alt="placeholder" />';
                }
                echo '</div>';
                
                echo '<div class="featured-badge"><i class="fa-solid fa-medal"></i> Top Rated</div>';
                
                echo '<div class="tag">';
                $displayedTags = 0;
                foreach ($tagList as $tag) {
                    if ($displayedTags < 2) {
                        $tagClass = getTagClass($tag);
                        echo '<span class="tags ' . $tagClass . '">' . ucfirst(str_replace('_', ' ', $tag)) . '</span>';
                        $displayedTags++;
                    }
                }
                
                if ($isPaid) {
                    echo '<span class="tags">Payant</span>';
                } else {
                    echo '<span class="tags accent">Gratuit</span>';
                }
                
                echo '</div></div>';
                
                echo '<div class="info">';
                echo '<h3>' . htmlspecialchars($row["titre"]) . '</h3>';
                
                if ($row["date_ou_periode"]) {
                    echo '<p class="period"><i class="fa-regular fa-calendar"></i> ' . htmlspecialchars($row["date_ou_periode"]) . '</p>';
                }
                
                echo '<div class="featured-rating">' . getStars($randomRating) . '</div>';
                
                echo '</div>';
                
                echo '<div class="actions">';
                echo '<button class="add-to-cart-button full-width" data-id="' . $row['id'] . '" 
                    data-title="' . htmlspecialchars($row['titre']) . '" 
                    data-price="' . $row['prix'] . '" 
                    data-image="' . htmlspecialchars($row['image_url'] ? $row['image_url'] : 'nature-placeholder.jpg') . '" 
                    data-period="' . htmlspecialchars($row['date_ou_periode']) . '" 
                    data-tags="' . htmlspecialchars($row['tags']) . '">
                    <i class="fa-solid fa-cart-shopping"></i> Ajouter au panier
                    </button>';
                echo '</div>';
                
                echo '</div>';
                
                $count++;
            }
        }
    }
    ?>
  </div>
</section>

<!-- Section newsletter améliorée -->
<section class="newsletter-section">
  <div class="newsletter-container">
    <div class="newsletter-content">
      <i class="fa-solid fa-envelope-open-text"></i>
      <h2>Restez informé(e)</h2>
      <p>Recevez en avant-première nos nouvelles activités et offres exclusives</p>
      <form class="newsletter-form">
        <input type="email" placeholder="Votre adresse e-mail" required>
        <button type="submit">S'abonner</button>
      </form>
    </div>
  </div>
</section>

<?php
// Inclure le footer
include '../TEMPLATE/footer.php';
?>

<script src="carousel.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser le panier s'il n'existe pas déjà
        if (!localStorage.getItem('synapse-cart')) {
            localStorage.setItem('synapse-cart', JSON.stringify([]));
        }
        
        // Mettre à jour le compteur du panier
        updateCartCount();
        
        // Script pour le slider d'activités gratuites
        const freeActivitiesSlider = document.getElementById('free-activities-slider');
        const prevButton = document.querySelector('.free-activities-section .slider-button.prev');
        const nextButton = document.querySelector('.free-activities-section .slider-button.next');
        
        if (freeActivitiesSlider && prevButton && nextButton) {
            let scrollAmount = 0;
            const cardWidth = 345; // Largeur approximative d'une carte + marge
            
            prevButton.addEventListener('click', function() {
                scrollAmount -= cardWidth;
                if (scrollAmount < 0) scrollAmount = 0;
                freeActivitiesSlider.scrollTo({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            });
            
            nextButton.addEventListener('click', function() {
                scrollAmount += cardWidth;
                const maxScroll = freeActivitiesSlider.scrollWidth - freeActivitiesSlider.clientWidth;
                if (scrollAmount > maxScroll) scrollAmount = maxScroll;
                freeActivitiesSlider.scrollTo({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            });
        }
        
        // Formulaire de newsletter
        const newsletterForm = document.querySelector('.newsletter-form');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const email = this.querySelector('input[type="email"]').value;
                if (email) {
                    showNotification('Merci pour votre inscription à notre newsletter !', 'success');
                    this.reset();
                }
            });
        }
        
        // Ajouter des événements pour les boutons "Ajouter au panier"
        document.querySelectorAll('.add-to-cart-button').forEach(button => {
            button.addEventListener('click', function(event) {
                // Empêcher la propagation de l'événement pour éviter de naviguer vers la page détaillée
                event.stopPropagation();
                
                const id = this.getAttribute('data-id');
                const titre = this.getAttribute('data-title');
                const prix = parseFloat(this.getAttribute('data-price'));
                const image = this.getAttribute('data-image');
                const periode = this.getAttribute('data-period');
                const tagsStr = this.getAttribute('data-tags');
                const tags = tagsStr ? tagsStr.split(',') : [];
                
                // Ajouter l'activité au panier
                addToCart({
                    id: id,
                    titre: titre,
                    prix: prix,
                    image: image,
                    periode: periode,
                    tags: tags
                });
                
                // Animation du bouton
                this.classList.add('clicked');
                setTimeout(() => {
                    this.classList.remove('clicked');
                }, 300);
            });
        });
        
        // Fonction pour ajouter au panier
        function addToCart(item) {
            // Récupérer le panier actuel
            const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
            
            // Vérifier si l'article est déjà dans le panier
            const existingItemIndex = cart.findIndex(cartItem => cartItem.id === item.id);
            
            // Si l'article n'est pas déjà dans le panier, l'ajouter
            if (existingItemIndex === -1) {
                cart.push(item);
                localStorage.setItem('synapse-cart', JSON.stringify(cart));
                updateCartCount();
                showNotification('Activité ajoutée au panier !', 'success');
            } else {
                showNotification('Cette activité est déjà dans votre panier.', 'info');
            }
        }
        
        // Fonction pour mettre à jour le compteur du panier
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
            const cartCount = document.getElementById('panier-count');
            if (cartCount) {
                cartCount.textContent = cart.length;
            }
        }
        
        // Fonction pour afficher une notification
        function showNotification(message, type = 'success') {
            // Supprimer les notifications existantes
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notification => {
                notification.remove();
            });
            
            // Créer la notification
            const notification = document.createElement('div');
            notification.classList.add('notification', type);
            
            // Ajouter l'icône appropriée
            let icon = 'fa-circle-check';
            if (type === 'info') {
                icon = 'fa-circle-info';
            } else if (type === 'error') {
                icon = 'fa-circle-exclamation';
            }
            
            notification.innerHTML = `<i class="fa-solid ${icon}"></i> ${message}`;
            
            // Ajouter la notification au document
            document.body.appendChild(notification);
            
            // Faire disparaître la notification après 3 secondes
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 500);
            }, 3000);
        }
        
        // Countdown Timer pour la section "Dernière Chance"
        function updateCountdown() {
            const now = new Date();
            const endOfWeek = new Date();
            endOfWeek.setDate(now.getDate() + 7);
            endOfWeek.setHours(23, 59, 59, 999);
            
            const diff = endOfWeek - now;
            
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            document.getElementById('countdown-days').textContent = days.toString().padStart(2, '0');
            document.getElementById('countdown-hours').textContent = hours.toString().padStart(2, '0');
            document.getElementById('countdown-minutes').textContent = minutes.toString().padStart(2, '0');
            document.getElementById('countdown-seconds').textContent = seconds.toString().padStart(2, '0');
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
    });
</script>

<?php
$conn->close();
?>