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

// Récupérer toutes les activités avec leurs tags
$sql = "SELECT a.*, 
        GROUP_CONCAT(td.name) AS tags,
        GROUP_CONCAT(td.display_name SEPARATOR '|') AS tag_display_names,
        DATEDIFF(STR_TO_DATE(SUBSTRING_INDEX(date_ou_periode, ' - ', -1), '%d/%m/%Y'), NOW()) as days_remaining
        FROM activites a 
        LEFT JOIN activity_tags at ON a.id = at.activity_id
        LEFT JOIN tag_definitions td ON at.tag_definition_id = td.id
        GROUP BY a.id
        ORDER BY a.date_creation DESC";
        
$result = $conn->query($sql);

// Récupérer tous les tag_definitions pour la fonction getTagClass
$tagDefinitions = [];
$tagClasses = [
    'primary', 'secondary', 'accent' // Classes CSS alternées pour différents tags
];

$tagDefinitionsSql = "SELECT * FROM tag_definitions";
$tagDefinitionsResult = $conn->query($tagDefinitionsSql);
if ($tagDefinitionsResult && $tagDefinitionsResult->num_rows > 0) {
    $i = 0;
    while($tagRow = $tagDefinitionsResult->fetch_assoc()) {
        $tagDefinitions[$tagRow['name']] = [
            'display_name' => $tagRow['display_name'],
            'class' => $tagClasses[$i % count($tagClasses)] // Assigner une classe cycliquement
        ];
        $i++;
    }
}

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
    global $tagDefinitions;
    
    // Use the assigned class from tag_definitions
    return isset($tagDefinitions[$tag]) ? $tagDefinitions[$tag]['class'] : 'primary';
}

// Function to get the display name for a tag
function getTagDisplayName($tag) {
    global $tagDefinitions;
    
    // Use the display name from tag_definitions
    return isset($tagDefinitions[$tag]) ? $tagDefinitions[$tag]['display_name'] : ucfirst(str_replace('_', ' ', $tag));
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

// Function to check if an activity has a specific tag
function hasTag($activity, $tagName) {
    if (empty($activity['tags'])) {
        return false;
    }
    
    $tagList = explode(',', $activity['tags']);
    return in_array($tagName, $tagList);
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

<!-- Main Content -->
<main>
    <!-- Floating leaf animation elements (reduced quantity) -->
    <div class="leaf-animation-container">
        <div class="floating-leaf leaf-1"></div>
        <div class="floating-leaf leaf-3"></div>
        <div class="floating-leaf leaf-5"></div>
        <div class="floating-leaf leaf-7"></div>
        <div class="floating-leaf leaf-9"></div>
        <div class="floating-leaf leaf-11"></div>
        <div class="floating-leaf leaf-13"></div>
        <div class="floating-leaf leaf-15"></div>
        <div class="floating-leaf leaf-17"></div>
    </div>

    <!-- Background nature elements -->
    <div class="nature-element nature-element-1"></div>
    <div class="nature-element nature-element-2"></div>

    <!-- Enhanced Carousel with Fancy Wrapper -->
    <div class="carrousel-wrapper fade-in-section">
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
                    <img src="./images/initial-lac-buttes-chaumont.jpg" alt="Buttes-Chaumont" />
                    <div class="carrousel-caption">
                        <h3>Marathon dans les Buttes-Chaumont</h3>
                        <p>Parcours de 10 km à travers sentiers boisés, ponts suspendus et belvédères, animé par l'énergie du quartier.</p>
                    </div>
                </div>
                <div class="carrousel-slide">
                    <img src="./images/paris-canal-saint-martin-hd.jpg" alt="saint martin" />
                    <div class="carrousel-caption">
                        <h3>Kayak sur le Canal Saint-Martin</h3>
                        <p>Descente en solo ou en duo, pagaie à la main sous les lampadaires, entre écluses et péniches</p>
                    </div>
                </div>
                <div class="carrousel-slide">
                    <img src="./images/1600px-RueDenoyez.jpeg" alt="Grafiti" />
                    <div class="carrousel-caption">
                        <h3>Atelier de graffiti à Belleville</h3>
                        <p>Initiez-vous aux techniques du street art avec un crew local, bombe en main, sur un mur d'expression libre.</p>
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
    </div>

    <!-- Enhanced Welcome Section -->
    <div class="welcome-section fade-in-section">
        <div class="welcome-content">
            <h1>Bienvenue sur <span>SYNAPSE</span></h1>
            <p>Un espace où partager des moments uniques et découvrir des activités exceptionnelles au cœur de la nature</p>
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

    <!-- Section divider -->
    <div class="section-divider">
        <div class="section-divider-icon">
            <i class="fa-solid fa-leaf"></i>
        </div>
    </div>

    <!-- Section dernière chance - Completely redesigned -->
    <section class="content-section last-chance-section fade-in-section">
        <div class="section-header with-accent">
            <h2>Dernière Chance</h2>
            <p>Ne manquez pas ces activités qui se terminent bientôt</p>
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
                        $tagDisplayNames = $row["tag_display_names"] ? explode('|', $row["tag_display_names"]) : [];
                        $daysRemaining = getDaysRemaining($row);
                        
                        echo '<div class="featured-card activity-card" data-id="' . $row['id'] . '">'; 
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
                        
                        // Find payment tag position
                        $paymentTagIndex = -1;
                        $paymentTag = '';
                        $normalTags = [];
                        $normalTagDisplayNames = [];
                        
                        for ($i = 0; $i < count($tagList); $i++) {
                            if ($tagList[$i] === 'gratuit' || $tagList[$i] === 'payant') {
                                $paymentTagIndex = $i;
                                $paymentTag = $tagList[$i];
                            } else {
                                $normalTags[] = $tagList[$i];
                                if (isset($tagDisplayNames[$i])) {
                                    $normalTagDisplayNames[] = $tagDisplayNames[$i];
                                }
                            }
                        }
                        
                        // Display up to 2, but prioritize non-payment tags
                        for ($i = 0; $i < min(count($normalTags), 2); $i++) {
                            $tag = $normalTags[$i];
                            $tagClass = getTagClass($tag);
                            $displayName = isset($normalTagDisplayNames[$i]) ? $normalTagDisplayNames[$i] : getTagDisplayName($tag);
                            
                            echo '<span class="tags ' . $tagClass . '" data-tag="' . htmlspecialchars($tag) . '">' . htmlspecialchars($displayName) . '</span>';
                        }
                        
                        // Add payment status tag
                        if ($paymentTag) {
                            $tagClass = getTagClass($paymentTag);
                            $displayName = isset($tagDisplayNames[$paymentTagIndex]) ? 
                                $tagDisplayNames[$paymentTagIndex] : 
                                getTagDisplayName($paymentTag);
                            
                            echo '<span class="tags ' . $tagClass . '" data-tag="' . htmlspecialchars($paymentTag) . '">' . htmlspecialchars($displayName) . '</span>';
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
                    echo '<div class="no-activities">';
                    echo '<i class="fa-regular fa-calendar-check"></i>';
                    echo '<h3>Aucune activité en fin de période actuellement</h3>';
                    echo '<p>Toutes nos activités sont encore disponibles pour un bon moment !</p>';
                    echo '</div>';
                }
            }
            ?>
        </div>
    </section>

    <!-- Section divider -->
    <div class="section-divider">
        <div class="section-divider-icon">
            <i class="fa-solid fa-gift"></i>
        </div>
    </div>

    <!-- Section activités gratuites avec slider amélioré -->
    <section class="content-section free-activities-section fade-in-section">
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
        // Check if the activity is free by price (not by tag) - this fixes the issue
        if ($row["prix"] <= 0 && $freeCount < 6) {
            $freeCount++;
            $randomRating = rand(30, 50) / 10;
            $tagList = $row["tags"] ? explode(',', $row["tags"]) : [];
            $tagDisplayNames = $row["tag_display_names"] ? explode('|', $row["tag_display_names"]) : [];
            
            echo '<div class="featured-card activity-card" data-id="' . $row['id'] . '">'; 
            echo '<div class="content">';
            
            echo '<div class="image-container">';
            if ($row["image_url"]) {
                echo '<img src="' . htmlspecialchars($row["image_url"]) . '" alt="' . htmlspecialchars($row["titre"]) . '" />';
            } else {
                echo '<img src="nature-placeholder.jpg" alt="placeholder" />';
            }
            echo '</div>';
            
            echo '<div class="tag">';
            
            // Find payment tag position and normal tags
            $paymentTagIndex = -1;
            $normalTags = [];
            $normalTagDisplayNames = [];
            
            for ($i = 0; $i < count($tagList); $i++) {
                if ($tagList[$i] === 'gratuit' || $tagList[$i] === 'payant') {
                    $paymentTagIndex = $i;
                } else {
                    $normalTags[] = $tagList[$i];
                    if (isset($tagDisplayNames[$i])) {
                        $normalTagDisplayNames[] = $tagDisplayNames[$i];
                    }
                }
            }
            
            // Display up to 2 non-payment tags
            for ($i = 0; $i < min(count($normalTags), 2); $i++) {
                $tag = $normalTags[$i];
                $tagClass = getTagClass($tag);
                $displayName = isset($normalTagDisplayNames[$i]) ? $normalTagDisplayNames[$i] : getTagDisplayName($tag);
                
                echo '<span class="tags ' . $tagClass . '" data-tag="' . htmlspecialchars($tag) . '">' . htmlspecialchars($displayName) . '</span>';
            }
            
            // Add the "gratuit" tag
            $tagClass = getTagClass('gratuit');
            $displayName = getTagDisplayName('gratuit');
            echo '<span class="tags ' . $tagClass . '" data-tag="gratuit">' . htmlspecialchars($displayName) . '</span>';
            
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
        }
    }
    
    if ($freeCount == 0) {
        echo '<div class="no-activities" style="min-width: 100%; text-align: center;">';
        echo '<i class="fa-regular fa-face-sad-tear"></i>';
        echo '<h3>Aucune activité gratuite disponible</h3>';
        echo '<p>Revenez bientôt pour découvrir de nouvelles opportunités gratuites !</p>';
        echo '</div>';
    }
}
            ?>
        </div>
        
        <div class="slider-controls">
            <button class="slider-button prev"><i class="fa-solid fa-chevron-left"></i></button>
            <button class="slider-button next"><i class="fa-solid fa-chevron-right"></i></button>
        </div>
    </section>

    <!-- Section divider -->
    <div class="section-divider">
        <div class="section-divider-icon">
            <i class="fa-solid fa-crown"></i>
        </div>
    </div>

    <!-- Completely redesigned "Meilleures Activités" section -->
    <section class="content-section best-rated-section fade-in-section">
        <div class="section-header">
            <h2>Meilleures Activités</h2>
            <p>Les activités les plus appréciées par notre communauté</p>
        </div>
        
        <div id="best-activities-grid">
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
                        $tagDisplayNames = $row["tag_display_names"] ? explode('|', $row["tag_display_names"]) : [];
                        
                        echo '<div class="featured-card activity-card" data-id="' . $row['id'] . '">'; 
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
                        
                        // Find payment tag position and normal tags
                        $paymentTagIndex = -1;
                        $paymentTag = '';
                        $normalTags = [];
                        $normalTagDisplayNames = [];
                        
                        for ($i = 0; $i < count($tagList); $i++) {
                            if ($tagList[$i] === 'gratuit' || $tagList[$i] === 'payant') {
                                $paymentTagIndex = $i;
                                $paymentTag = $tagList[$i];
                            } else {
                                $normalTags[] = $tagList[$i];
                                if (isset($tagDisplayNames[$i])) {
                                    $normalTagDisplayNames[] = $tagDisplayNames[$i];
                                }
                            }
                        }
                        
                        // Display up to 2 non-payment tags
                        for ($i = 0; $i < min(count($normalTags), 2); $i++) {
                            $tag = $normalTags[$i];
                            $tagClass = getTagClass($tag);
                            $displayName = isset($normalTagDisplayNames[$i]) ? $normalTagDisplayNames[$i] : getTagDisplayName($tag);
                            
                            echo '<span class="tags ' . $tagClass . '" data-tag="' . htmlspecialchars($tag) . '">' . htmlspecialchars($displayName) . '</span>';
                        }
                        
                        // Add payment status tag
                        if ($paymentTag) {
                            $tagClass = getTagClass($paymentTag);
                            $displayName = isset($tagDisplayNames[$paymentTagIndex]) ? 
                                $tagDisplayNames[$paymentTagIndex] : 
                                getTagDisplayName($paymentTag);
                            
                            echo '<span class="tags ' . $tagClass . '" data-tag="' . htmlspecialchars($paymentTag) . '">' . htmlspecialchars($displayName) . '</span>';
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

    <!-- Section Newsletter redesigned -->
    <section class="newsletter-section fade-in-section">
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

    <!-- Back to top button -->
    <div class="scroll-top-button" id="scroll-top">
        <i class="fa-solid fa-arrow-up"></i>
    </div>

</main>

<?php
// Inclure le footer
include '../TEMPLATE/footer.php';
?>

<script src="carousel.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize scroll animations
        const fadeElems = document.querySelectorAll('.fade-in-section');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                }
            });
        }, { threshold: 0.2 });
        
        fadeElems.forEach(el => observer.observe(el));
        
        // Create floating particles
        createParticles();
        
        // Scroll to top button logic
        const scrollTopButton = document.getElementById('scroll-top');
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                scrollTopButton.classList.add('visible');
            } else {
                scrollTopButton.classList.remove('visible');
            }
        });
        
        scrollTopButton.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
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

        // Make activity cards clickable - redirect to activity detail page
        document.querySelectorAll('.activity-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't navigate if clicking on add-to-cart button or tag
                if (e.target.closest('.add-to-cart-button') || e.target.closest('.tags')) {
                    return;
                }
                
                const activityId = this.getAttribute('data-id');
                if (activityId) {
                    window.location.href = 'activite.php?id=' + activityId;
                }
            });
            
            // Change cursor to pointer on hover to indicate it's clickable
            card.style.cursor = 'pointer';
        });

        // Make tags clickable - redirect to activities page with filter
        document.querySelectorAll('.tags').forEach(tag => {
            tag.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent the card click event
                
                const tagName = this.getAttribute('data-tag');
                if (tagName) {
                    // Redirect to activities page with the selected tag
                    window.location.href = 'activites.php?tag=' + encodeURIComponent(tagName);
                }
            });
            
            // Add hover styling
            tag.style.cursor = 'pointer';
            tag.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
                this.style.transition = 'transform 0.2s ease';
            });
            
            tag.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Make activity cards clickable - redirect to activity detail page
        document.querySelectorAll('.activity-card, .featured-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't navigate if clicking on add-to-cart button or tag
                if (e.target.closest('.add-to-cart-button') || 
                    e.target.closest('.tags') || 
                    e.target.closest('.full-width')) {
                    return;
                }
                
                const activityId = this.getAttribute('data-id');
                if (activityId) {
                    window.location.href = 'activite.php?id=' + activityId;
                }
            });
            
            // Add hover styling
            card.style.cursor = 'pointer';
        });
        
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
        
        // Function to create ambient particles
        function createParticles() {
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random size
                const size = Math.random() * 5 + 3;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Random position
                particle.style.left = `${Math.random() * 100}vw`;
                particle.style.top = `${Math.random() * 100}vh`;
                
                // Random animation duration
                const duration = Math.random() * 15 + 10;
                particle.style.animationDuration = `${duration}s`;
                
                // Random animation delay
                particle.style.animationDelay = `${Math.random() * 5}s`;
                
                // Random opacity
                particle.style.opacity = Math.random() * 0.5 + 0.1;
                
                // Random color tint
                const colors = [
                    'rgba(69, 161, 99, 0.6)',  // Green
                    'rgba(233, 196, 106, 0.6)', // Gold
                    'rgba(139, 109, 65, 0.6)',  // Brown
                    'rgba(255, 255, 255, 0.6)'  // White
                ];
                particle.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                
                document.body.appendChild(particle);
            }
        }
    });
</script>

<?php
$conn->close();
?>
<!-- cvq -->