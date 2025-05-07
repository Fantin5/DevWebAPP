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
        $stars .= '★';
    }
    
    // Demi-étoile si nécessaire
    if ($halfStar) {
        $stars .= '★';
    }
    
    // Étoiles vides
    for ($i = 0; $i < $emptyStars; $i++) {
        $stars .= '☆';
    }
    
    return $stars . ' ' . number_format($rating, 1);
}

// Fonction pour déterminer la classe CSS du tag
function getTagClass($tag) {
    $tagClasses = [
        'art' => 'primary',
        'cuisine' => 'primary',
        'bien_etre' => 'primary',
        'creativite' => 'primary',
        'sport' => 'primary',
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
    <link rel="stylesheet" href="Accueil.css" />
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

    <!-- 3 partie barre de recherche-->

    <div class="container-barre-de-recherche">
      <i class="fa-solid fa-magnifying-glass"></i>
      <!-- On regroupe dans un div la barre de recherche et le fa-bars -->
      <div class="demi-container-recherche">
        <input
          type="search"
          placeholder="Rechercher"
          class="barre-de-recherche"
          id="search-input"
        />
        <i class="fa-solid fa-bars"></i>
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

    <!-- 6 paragraphe derniere chance  -->
    <p class="derniere-chance">Dernière Chance !</p>

    <!-- Bouton pour créer une activité -->
    <div class="create-activity-button-container">
      <a href="./jenis.html" class="create-activity-button">
        <i class="fa-solid fa-plus"></i> Créer une Activité
      </a>
    </div>

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
              
              // Image
              if ($row["image_url"]) {
                  echo '<img src="' . htmlspecialchars($row["image_url"]) . '" alt="' . htmlspecialchars($row["titre"]) . '" />';
              } else {
                  echo '<img src="/api/placeholder/400/320" alt="placeholder" />';
              }
              
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
              echo '<button>Rejoindre</button>';
              echo '</div>';
              
              echo '</div>';
          }
      } else {
          echo '<p class="no-activities">Aucune activité disponible pour le moment.</p>';
      }
      ?>
    </section>

    <!-- 7 footer -->
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
  </body>
  <script src="Carousel.js"></script>
  <script src="search.js"></script>
</html>

<?php
$conn->close();
?>