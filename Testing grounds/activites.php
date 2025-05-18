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
    <title>Toutes les activités | Synapse</title>
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
      
      .activities-container {
        width: 90%;
        max-width: 1200px;
        margin: 30px auto 60px;
      }
      
      .activities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 30px;
        margin-top: 40px;
      }
      
      .no-results {
        text-align: center;
        padding: 40px;
        background: white;
        border-radius: 15px;
        box-shadow: var(--shadow-md);
        grid-column: 1 / -1;
      }
      
      .no-results i {
        font-size: 48px;
        color: #828977;
        margin-bottom: 20px;
      }
      
      .no-results p {
        color: #666;
        font-size: 18px;
        margin-bottom: 0;
      }
    </style>
  </head>
  <body>
    <?php
    // Inclure le header
    include '../TEMPLATE/Nouveauhead.php';
    ?>

    <h1 class="activities-page-title">Toutes nos activités</h1>

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
              <option value="ecologie">Écologie</option>
              <option value="randonnee">Randonnée</option>
              <option value="jardinage">Jardinage</option>
              <option value="meditation">Méditation</option>
              <option value="artisanat">Artisanat</option>
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

    <!-- Affichage des activités -->
    <div class="activities-container">
      <div class="activities-grid" id="activities-grid">
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
                
                echo '<div class="card" data-id="' . $row['id'] . '">';
                echo '<div class="content">';
                
                // Image avec conteneur de taille fixe
                echo '<div class="image-container">';
                if ($row["image_url"]) {
                    echo '<img src="' . htmlspecialchars($row["image_url"]) . '" alt="' . htmlspecialchars($row["titre"]) . '" />';
                } else {
                    echo '<img src="nature-placeholder.jpg" alt="placeholder" />';
                }
                echo '</div>';
                
                echo '<div class="tag">';
                
                // Affichage des tags (limité à 2)
                $displayedTags = 0;
                foreach ($tagList as $tag) {
                    if ($displayedTags < 2) {
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
                
                // Bouton "Ajouter au panier"
                echo '<button class="add-to-cart-button" data-id="' . $row['id'] . '" 
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
        } else {
            echo '<div class="no-results">';
            echo '<i class="fa-solid fa-seedling"></i>';
            echo '<p>Aucune activité disponible pour le moment.</p>';
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
        
        // Système de filtrage
        const searchInput = document.getElementById('search-input');
        const categoryFilter = document.getElementById('category-filter');
        const locationFilter = document.getElementById('location-filter');
        const priceFilter = document.getElementById('price-filter');
        const resetButton = document.getElementById('reset-filters');
        const activitiesGrid = document.getElementById('activities-grid');
        const cards = activitiesGrid ? Array.from(activitiesGrid.querySelectorAll('.card')) : [];
        
        function filterActivities() {
          const searchTerm = searchInput.value.toLowerCase();
          const category = categoryFilter.value.toLowerCase();
          const location = locationFilter.value.toLowerCase();
          const price = priceFilter.value.toLowerCase();
          
          let hasVisibleCards = false;
          
          cards.forEach(card => {
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
          
          // Afficher un message si aucun résultat
          let noResultsMessage = activitiesGrid.querySelector('.no-results');
          
          if (!hasVisibleCards) {
            if (!noResultsMessage) {
              noResultsMessage = document.createElement('div');
              noResultsMessage.className = 'no-results';
              noResultsMessage.innerHTML = '<i class="fa-solid fa-filter-circle-xmark"></i><p>Aucune activité ne correspond à votre recherche.</p>';
              activitiesGrid.appendChild(noResultsMessage);
            }
            noResultsMessage.style.display = 'block';
          } else if (noResultsMessage) {
            noResultsMessage.style.display = 'none';
          }
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
        
        // Gestion des paramètres d'URL pour appliquer les filtres
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('category')) {
          const category = urlParams.get('category');
          if (categoryFilter) categoryFilter.value = category;
        }
        
        if (urlParams.has('location')) {
          const location = urlParams.get('location');
          if (locationFilter) locationFilter.value = location;
        }
        
        if (urlParams.has('price')) {
          const price = urlParams.get('price');
          if (priceFilter) priceFilter.value = price;
        }
        
        if (urlParams.has('search')) {
          const search = urlParams.get('search');
          if (searchInput) searchInput.value = search;
        }
        
        // Appliquer les filtres si des paramètres sont présents
        if (urlParams.has('category') || urlParams.has('location') || urlParams.has('price') || urlParams.has('search')) {
          filterActivities();
        }
        
        // Ajouter des événements pour les boutons "Ajouter au panier"
        document.querySelectorAll('.add-to-cart-button').forEach(button => {
          button.addEventListener('click', function(event) {
            event.stopPropagation(); // Empêche le clic sur la carte
            
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
        
        // Rendre les cartes cliquables pour aller aux détails
        document.querySelectorAll('.card').forEach(card => {
          card.addEventListener('click', function(e) {
            // Ne pas rediriger si l'utilisateur a cliqué sur le bouton "Ajouter au panier"
            if (e.target.closest('.add-to-cart-button')) {
              return;
            }
            
            const activityId = this.getAttribute('data-id');
            if (activityId) {
              window.location.href = 'activite.php?id=' + activityId;
            }
          });
          
          // Style au survol
          card.addEventListener('mouseenter', function() {
            this.style.cursor = 'pointer';
            this.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.2)';
          });
          
          card.addEventListener('mouseleave', function() {
            this.style.boxShadow = '';
          });
        });
        
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
          document.body.appendChild(notification);
          
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
    
  </body>
</html>

<?php
$conn->close();
?>