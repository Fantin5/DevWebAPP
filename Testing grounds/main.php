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
        'gratuit' => 'accent'
    ];
    
    return isset($tagClasses[$tag]) ? $tagClasses[$tag] : '';
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Accueil</title>
    <link rel="stylesheet" href="main.css" />
    <link rel="stylesheet" href="../TEMPLATE/teteaupied.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    />
  </head>
  <body>
    <header class="header">
      <img
        class="logo"
        src="../Connexion-Inscription/logo-transparent-pdf.png"
        alt="Site logo"
      />
      <nav class="nav-links">
        <ul>
          <li><a href="#">Devenez Prestataire</a></li>
          <li><a href="#">Concept</a></li>
        </ul>
      </nav>

      <div class="icon">
        <i class="fa-regular fa-heart" aria-label="Favoris"></i>
        <a href="panier.html" class="panier-link" aria-label="Panier">
          <i class="fa-solid fa-cart-shopping"></i>
          <span class="panier-count" id="panier-count">0</span>
        </a>
        <a
          href="../Connexion-Inscription/Connexion.html"
          class="connexion-profil"
          aria-label="Connexion"
        >
          <i class="fa-solid fa-user"></i>
        </a>
      </div>
    </header>

    <!-- 2 partie bandeau -->
    <img
      class="bandeau"
      src="WhatsApp Image 2025-02-04 à 14.55.09_a4664920.jpg"
    />
    <!-- Section d'accueil -->
<div class="welcome-section">
  <div class="welcome-content">
    <h1>Bienvenue sur <span>SYNAPSE</span></h1>
    <p>Un espace où partager des moments uniques et découvrir des activités exceptionnelles</p>
    <div class="welcome-buttons">
      <a href="#filter-section" class="welcome-btn primary">Découvrir les activités</a>
      <a href="./jenis.html" class="welcome-btn secondary">Proposer une activité</a>
    </div>
  </div>
</div>

<!-- Section de filtrage -->
<div id="filter-section" class="filter-section">
  <h2>Trouvez l'activité idéale</h2>
  
  <div class="filter-container">
    <div class="search-container">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" placeholder="Rechercher une activité..." id="search-input" class="search-input">
    </div>
    
    <div class="filters">
      <div class="filter-group">
        <label>Catégorie</label>
        <select id="category-filter" class="filter-select">
          <option value="">Toutes les catégories</option>
          <option value="art">Art</option>
          <option value="sport">Sport</option>
          <option value="bien_etre">Bien-être</option>
          <option value="cuisine">Cuisine</option>
          <option value="creativite">Créativité</option>
        </select>
      </div>
      
      <div class="filter-group">
        <label>Lieu</label>
        <select id="location-filter" class="filter-select">
          <option value="">Tous les lieux</option>
          <option value="exterieur">Extérieur</option>
          <option value="interieur">Intérieur</option>
        </select>
      </div>
      
      <div class="filter-group">
        <label>Prix</label>
        <select id="price-filter" class="filter-select">
          <option value="">Tous les prix</option>
          <option value="gratuit">Gratuit</option>
          <option value="payant">Payant</option>
        </select>
      </div>
      
      <button id="reset-filters" class="reset-button">
        <i class="fa-solid fa-rotate"></i> Réinitialiser
      </button>
    </div>
  </div>
</div>

    <!-- 4 Partie carousel -->
    <div class="carrousel">
      <!-- Conteneur des images -->
      <div class="carrousel-images">
        <img src="chef.png" alt="Image 1" />
        <img src="grotte.png" alt="Image 2" />
        <img src="sports.png" alt="Image 3" />
        <img src="tableau.png" alt="Image 4" />
        <img src="yoga.png" alt="Image 5" />
      </div>

      <!-- Boutons de navigation -->
      <button class="carrousel-button prev">&#10094;</button>
      <button class="carrousel-button next">&#10095;</button>

      <!-- Indicateurs de position -->
      <div class="carrousel-indicators">
        <div class="carrousel-indicator active"></div>
        <div class="carrousel-indicator"></div>
        <div class="carrousel-indicator"></div>
        <div class="carrousel-indicator"></div>
        <div class="carrousel-indicator"></div>
      </div>
    </div>

    <!-- 5 partie barre de separation -->
    <div class="barre-de-separation"></div>

<!-- Section dernière chance -->
<section class="activities-section last-chance-section">
  <div class="section-header with-accent">
    <h2>Dernière Chance !</h2>
    <p>Ne manquez pas ces activités qui se terminent bientôt</p>
  </div>
</section>

    <!-- Bouton pour créer une activité -->
    <div class="create-activity-button-container">
      <a href="./jenis.html" class="create-activity-button">
        <i class="fa-solid fa-plus"></i> Créer une Activité
      </a>
    </div>
  
<!-- Section activités gratuites -->
<section class="activities-section free-activities-section">
  <div class="section-header">
    <h2>Activités Gratuites</h2>
    <p>Découvrez notre sélection d'activités sans débourser un centime</p>
  </div>
  
 <!-- Update the free activities slider section in main.php -->
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
              
              echo '<div class="featured-card">'; // Change slider-card to featured-card
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
                
                echo '<div class="featured-card">';
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
    <!-- Activities Section -->
    <section class="activities" id="activities-container">
      <?php 
      if ($result->num_rows > 0) {
          // Afficher chaque activité
          while($row = $result->fetch_assoc()) {
              // Générer une note aléatoire pour la démonstration (à remplacer par un système réel de notation)
              $randomRating = rand(30, 50) / 10; // Note entre 3.0 et 5.0 
              
              // Liste des tags
              $tagList = $row["tags"] ? explode(',', $row["tags"]) : [];
              
              // Type de prix
              $isPaid = $row["prix"] > 0;
              
              echo '<div class="card">';
              echo '<div class="content">';
              
              // Image avec conteneur de taille fixe
              echo '<div class="image-container">';
              if ($row["image_url"]) {
                  echo '<img src="' . htmlspecialchars($row["image_url"]) . '" alt="' . htmlspecialchars($row["titre"]) . '" />';
              } else {
                  echo '<img src="/api/placeholder/400/320" alt="placeholder" />';
              }
              echo '</div>';
              
              echo '<div class="tag">';
              
              // Affichage des tags
              $displayedTags = 0;
              foreach ($tagList as $tag) {
                  if ($displayedTags < 2) { // Limiter à 2 tags visibles
                      $tagClass = getTagClass($tag);
                      echo '<span class="tags ' . $tagClass . '">' . ucfirst(str_replace('_', ' ', $tag)) . '</span>';
                      $displayedTags++;
                  }
              }
              
              // Afficher le statut gratuit/payant
              if ($isPaid) {
                  echo '<span class="tags">Payant</span>';
              } else {
                  echo '<span class="tags accent">Gratuit</span>';
              }
              
              echo '</div></div>';
              
              echo '<div class="info">';
              echo '<h3>' . htmlspecialchars($row["titre"]) . '</h3>';
              
              // Date ou période
              if ($row["date_ou_periode"]) {
                  echo '<p class="period"><i class="fa-regular fa-calendar"></i> ' . htmlspecialchars($row["date_ou_periode"]) . '</p>';
              }
              
              echo '</div>';
              
              echo '<div class="actions">';
              echo '<div class="rating">' . getStars($randomRating) . '</div>';
              
              // Bouton "Ajouter au panier" à la place de "Rejoindre"
              echo '<button class="add-to-cart-button" data-id="' . $row['id'] . '" 
                    data-title="' . htmlspecialchars($row['titre']) . '" 
                    data-price="' . $row['prix'] . '" 
                    data-image="' . htmlspecialchars($row['image_url'] ? $row['image_url'] : '/api/placeholder/400/320') . '" 
                    data-period="' . htmlspecialchars($row['date_ou_periode']) . '" 
                    data-tags="' . htmlspecialchars($row['tags']) . '">
                    <i class="fa-solid fa-cart-shopping"></i> Ajouter au panier
                    </button>';
              
              echo '</div>';
              
              echo '</div>';
          }
      } else {
          echo '<p class="no-activities">Aucune activité disponible pour le moment.</p>';
      }
      ?>
    </section>

<!-- Section newsletter -->
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
  // Script pour le slider d'activités gratuites
  document.addEventListener('DOMContentLoaded', function() {
    const freeActivitiesSlider = document.getElementById('free-activities-slider');
    const prevButton = document.querySelector('.free-activities-section .slider-button.prev');
    const nextButton = document.querySelector('.free-activities-section .slider-button.next');
    
    if (freeActivitiesSlider && prevButton && nextButton) {
      let scrollAmount = 0;
      const cardWidth = 320; // Largeur approximative d'une carte + marge
      
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
    
    // Système de filtrage
    const searchInput = document.getElementById('search-input');
    const categoryFilter = document.getElementById('category-filter');
    const locationFilter = document.getElementById('location-filter');
    const priceFilter = document.getElementById('price-filter');
    const resetButton = document.getElementById('reset-filters');
    const activitiesContainer = document.getElementById('activities-container');
    const cards = activitiesContainer ? Array.from(activitiesContainer.querySelectorAll('.card')) : [];
    
// Replace the filterActivities function in your main.php script tag
function filterActivities() {
  const searchTerm = searchInput.value.toLowerCase();
  const category = categoryFilter.value.toLowerCase();
  const location = locationFilter.value.toLowerCase();
  const price = priceFilter.value.toLowerCase();
  
  // Get all cards from all sections
  const allCards = [
    ...Array.from(document.querySelectorAll('#activities-container .card')),
    ...Array.from(document.querySelectorAll('#best-activities-grid .featured-card')),
    ...Array.from(document.querySelectorAll('#free-activities-slider .slider-card'))
  ];
  
  let hasVisibleCards = false;
  
  allCards.forEach(card => {
    const title = card.querySelector('h3').textContent.toLowerCase();
    const tags = Array.from(card.querySelectorAll('.tags')).map(tag => tag.textContent.toLowerCase());
    const isPriceMatch = price === '' || 
                       (price === 'gratuit' && tags.includes('gratuit')) || 
                       (price === 'payant' && tags.some(tag => !tag.includes('gratuit')));
    
    const isCategoryMatch = category === '' || tags.some(tag => tag === category || tag.includes(category));
    const isLocationMatch = location === '' || tags.some(tag => tag === location || tag.includes(location));
    const isSearchMatch = searchTerm === '' || title.includes(searchTerm);
    
    const isVisible = isPriceMatch && isCategoryMatch && isLocationMatch && isSearchMatch;
    card.style.display = isVisible ? 'flex' : 'none';
    
    if (isVisible) hasVisibleCards = true;
  });
  
  // Show "no results" message if appropriate
  const containers = ['activities-container', 'best-activities-grid', 'free-activities-slider'];
  
  containers.forEach(containerId => {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    let noResultsMessage = container.querySelector('.no-results');
    
    if (!hasVisibleCards) {
      if (!noResultsMessage) {
        noResultsMessage = document.createElement('p');
        noResultsMessage.className = 'no-results';
        noResultsMessage.textContent = 'Aucune activité ne correspond à votre recherche.';
        container.appendChild(noResultsMessage);
      }
      noResultsMessage.style.display = 'block';
    } else if (noResultsMessage) {
      noResultsMessage.style.display = 'none';
    }
  });
}
    
    // Événements pour les filtres
    if (searchInput) searchInput.addEventListener('input', filterActivities);
    if (categoryFilter) categoryFilter.addEventListener('change', filterActivities);
    if (locationFilter) locationFilter.addEventListener('change', filterActivities);
    if (priceFilter) priceFilter.addEventListener('change', filterActivities);
    
    // Réinitialiser les filtres
    if (resetButton) {
      resetButton.addEventListener('click', function() {
        if (searchInput) searchInput.value = '';
        if (categoryFilter) categoryFilter.value = '';
        if (locationFilter) locationFilter.value = '';
        if (priceFilter) priceFilter.value = '';
        filterActivities();
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
  });
</script>
  </body>
  <script src="Carousel.js"></script>
  <script src="search.js"></script>
  
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser le panier s'il n'existe pas déjà
        if (!localStorage.getItem('synapse-cart')) {
            localStorage.setItem('synapse-cart', JSON.stringify([]));
        }
        
        // Mettre à jour le compteur du panier
        updateCartCount();
        
        // Ajouter des événements pour les boutons "Ajouter au panier"
        document.querySelectorAll('.add-to-cart-button').forEach(button => {
            button.addEventListener('click', function() {
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
                
                // Afficher une notification
                showNotification('Activité ajoutée au panier !', 'success');
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
            document.body.insertBefore(notification, document.body.firstChild);
            
            // Faire disparaître la notification après 3 secondes
            setTimeout(() => {
                notification.style.opacity = '0';
                setTimeout(() => {
                    notification.remove();
                }, 500);
            }, 3000);
        }
    });
  </script>
</html>

<?php
$conn->close();
?>