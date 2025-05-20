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

// Récupérer les activités depuis la base de données avec recherche et filtres
$where_clauses = [];
$params = [];
$types = "";

// Filtrage par recherche
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = '%' . trim($_GET['search']) . '%';
    $where_clauses[] = "(a.titre LIKE ? OR a.description LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

// Filtrage par catégorie
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $category = trim($_GET['category']);
    $where_clauses[] = "EXISTS (SELECT 1 FROM tags t WHERE t.activite_id = a.id AND t.nom_tag = ?)";
    $params[] = $category;
    $types .= "s";
}

// Filtrage par lieu (intérieur/extérieur)
if (isset($_GET['location']) && !empty($_GET['location'])) {
    $location = trim($_GET['location']);
    $where_clauses[] = "EXISTS (SELECT 1 FROM tags t WHERE t.activite_id = a.id AND t.nom_tag = ?)";
    $params[] = $location;
    $types .= "s";
}

// Filtrage par prix (gratuit/payant)
if (isset($_GET['price']) && !empty($_GET['price'])) {
    $price = trim($_GET['price']);
    if ($price === 'gratuit') {
        $where_clauses[] = "a.prix = 0";
    } elseif ($price === 'payant') {
        $where_clauses[] = "a.prix > 0";
    }
}

// Construction de la requête SQL
$sql = "SELECT a.*, 
        (SELECT GROUP_CONCAT(nom_tag) FROM tags WHERE activite_id = a.id) AS tags,
        DATEDIFF(STR_TO_DATE(SUBSTRING_INDEX(date_ou_periode, ' - ', -1), '%d/%m/%Y'), NOW()) as days_remaining
        FROM activites a";

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY date_creation DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

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

// Get all available tags for the filter dropdown
$tagsSql = "SELECT DISTINCT nom_tag FROM tags ORDER BY nom_tag";
$tagsResult = $conn->query($tagsSql);
$availableTags = [];
while ($tagRow = $tagsResult->fetch_assoc()) {
    $availableTags[] = $tagRow['nom_tag'];
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Découvrez nos activités | Synapse</title>
    <link rel="stylesheet" href="main.css" />
    <link rel="stylesheet" href="../TEMPLATE/teteaupied.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    
    <style>
      /* Enhanced Page-specific styles with nature theme */
      .page-wrapper {
        padding: 30px 20px 60px;
        min-height: 100vh;
        background: linear-gradient(135deg, rgba(228, 216, 200, 0.8) 0%, rgba(215, 225, 210, 0.8) 100%);
        position: relative;
      }
      
      .page-title-container {
        text-align: center;
        margin-bottom: 40px;
        position: relative;
      }
      
      .page-title {
        font-size: 42px;
        color: var(--text-dark);
        margin-bottom: 10px;
        font-family: 'Georgia', serif;
        position: relative;
        display: inline-block;
      }
      
      .page-title::after {
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
      
      .page-subtitle {
        font-size: 18px;
        color: #555;
        max-width: 800px;
        margin: 25px auto 0;
      }

      /* Enhanced Filter section - 3D glassmorphism design */
      .filter-section {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        padding: 30px;
        border-radius: 20px;
        margin-bottom: 40px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.7);
        max-width: 1200px;
        margin-left: auto;
        margin-right: auto;
        position: relative;
        overflow: hidden;
        transform: translateZ(0);
        transition: all 0.3s ease;
      }
      
      .filter-section:hover {
        transform: translateY(-5px) translateZ(0);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
      }
      
      .filter-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(60, 140, 92, 0.05) 0%, rgba(60, 140, 92, 0) 50%, rgba(60, 140, 92, 0.05) 100%);
        z-index: 0;
      }
      
      .filter-section h2 {
        color: var(--primary-color);
        margin-bottom: 25px;
        font-size: 28px;
        text-align: center;
        position: relative;
        font-family: var(--font-accent);
      }
      
      .filter-section h2::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 50px;
        height: 2px;
        background: linear-gradient(to right, var(--primary-color), var(--primary-light));
        border-radius: 2px;
      }
      
      .filter-container {
        position: relative;
        z-index: 1;
      }

      /* Enhanced search container with glow effect */
      .search-container {
        position: relative;
        margin-bottom: 25px;
      }
      
      .search-container i {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: #828977;
        font-size: 18px;
        transition: all 0.3s ease;
      }
      
      .search-input {
        width: 100%;
        padding: 18px 20px 18px 50px;
        border: 1px solid rgba(130, 137, 119, 0.3);
        border-radius: 50px;
        font-size: 16px;
        background-color: rgba(255, 255, 255, 0.7);
        transition: all 0.3s ease;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      }
      
      .search-input:focus {
        outline: none;
        border-color: var(--primary-color);
        background-color: white;
        box-shadow: 0 0 0 3px rgba(69, 161, 99, 0.2), 0 5px 15px rgba(0, 0, 0, 0.05);
      }
      
      .search-input:focus + i {
        color: var(--primary-color);
      }

      /* Enhanced filter groups with interactive elements */
      .filters {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
      }
      
      .filter-group {
        flex: 1;
        min-width: 200px;
        position: relative;
      }
      
      .filter-group label {
        display: block;
        margin-bottom: 10px;
        color: #555;
        font-weight: 500;
        transition: all 0.3s ease;
      }
      
      .filter-group:hover label {
        color: var(--primary-color);
      }
      
      .filter-select {
        width: 100%;
        padding: 16px 18px;
        border: 1px solid rgba(130, 137, 119, 0.3);
        border-radius: 12px;
        background-color: rgba(255, 255, 255, 0.7);
        font-size: 15px;
        color: #333;
        transition: all 0.3s ease;
        cursor: pointer;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        appearance: none;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="%23828977" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>');
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 16px;
      }
      
      .filter-select:focus {
        outline: none;
        border-color: var(--primary-color);
        background-color: white;
        box-shadow: 0 0 0 3px rgba(69, 161, 99, 0.2), 0 3px 10px rgba(0, 0, 0, 0.05);
      }
      
      /* Enhanced reset button with animation */
      .reset-button {
        background-color: white;
        border: 1px solid rgba(130, 137, 119, 0.3);
        color: #666;
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 15px 22px;
        border-radius: 12px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        position: relative;
        overflow: hidden;
        z-index: 1;
      }
      
      .reset-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, 
          rgba(60, 140, 92, 0) 0%, 
          rgba(60, 140, 92, 0.1) 50%, 
          rgba(60, 140, 92, 0) 100%);
        transform: skewX(-25deg);
        transition: left 0.5s ease;
        z-index: -1;
      }
      
      .reset-button:hover {
        background-color: #f8f8f8;
        border-color: rgba(130, 137, 119, 0.5);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
      }
      
      .reset-button:hover::before {
        left: 100%;
      }
      
      .reset-button i {
        color: var(--primary-color);
        font-size: 16px;
        transition: transform 0.3s ease;
      }
      
      .reset-button:hover i {
        transform: rotate(180deg);
      }
      
      /* Enhanced filter tags for visual filtering */
      .active-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 20px;
      }
      
      .active-filter-tag {
        background-color: var(--primary-color);
        color: white;
        padding: 8px 15px;
        border-radius: 30px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
      }
      
      .active-filter-tag:hover {
        background-color: var(--primary-dark);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
      }
      
      .active-filter-tag i {
        font-size: 12px;
      }
      
      .active-filter-tag.price {
        background-color: var(--secondary-color);
      }
      
      .active-filter-tag.location {
        background-color: var(--accent-color);
        color: #333;
      }
      
      .active-filter-tag.price:hover {
        background-color: var(--secondary-dark);
      }
      
      .active-filter-tag.location:hover {
        background-color: var(--accent-dark);
      }

      /* Enhanced activity grid with masonry layout */
      .activities-container {
        width: 95%;
        max-width: 1200px;
        margin: 40px auto 60px;
        position: relative;
      }
      
      .activities-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 30px;
        position: relative;
      }
      
      /* Enhanced card styling to match main.php */
      .card {
        background-color: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
        transition: all 0.5s cubic-bezier(0.215, 0.61, 0.355, 1);
        height: 100%;
        border: 1px solid rgba(255, 255, 255, 0.7);
        position: relative;
        z-index: 1;
        transform-style: preserve-3d;
        cursor: pointer;
      }
      
      .card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.3) 0%, rgba(255, 255, 255, 0) 50%, rgba(255, 255, 255, 0.3) 100%);
        z-index: -1;
        opacity: 0;
        transition: opacity 0.5s ease;
      }
      
      .card:hover {
        transform: translateY(-15px) rotateY(5deg);
        box-shadow: -10px 20px 40px rgba(0, 0, 0, 0.15);
      }
      
      .card:hover::before {
        opacity: 1;
      }
      
      /* Price tag on cards */
      .price-tag {
        position: absolute;
        top: 15px;
        right: 15px;
        background: linear-gradient(135deg, rgba(148, 107, 45, 0.9) 0%, rgba(97, 70, 30, 0.9) 100%);
        color: white;
        padding: 8px 15px;
        border-radius: 30px;
        font-size: 14px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        z-index: 10;
        transition: all 0.3s ease;
      }
      
      .price-tag.free {
        background: linear-gradient(135deg, rgba(69, 161, 99, 0.9) 0%, rgba(39, 94, 62, 0.9) 100%);
      }
      
      .price-tag i {
        font-size: 14px;
      }
      
      .card:hover .price-tag {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
      }
      
      /* Enhanced image container with zoom effect */
      .image-container {
        height: 220px;
        min-height: 220px;
        overflow: hidden;
        position: relative;
      }
      
      .image-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(180deg, 
            rgba(0, 0, 0, 0.2) 0%, 
            rgba(0, 0, 0, 0) 20%, 
            rgba(0, 0, 0, 0) 80%, 
            rgba(0, 0, 0, 0.3) 100%);
        z-index: 1;
      }
      
      .card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.7s cubic-bezier(0.22, 1, 0.36, 1);
      }
      
      .card:hover img {
        transform: scale(1.1);
      }
      
      /* Enhanced tag styling */
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
        padding: 8px 16px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 600;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(5px);
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.3);
        letter-spacing: 0.5px;
      }
      
      .tags:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
      }
      
      .tags.accent {
        background-color: rgba(233, 196, 106, 0.9);
        color: var(--text-dark);
      }
      
      .tags.secondary {
        background-color: rgba(148, 107, 45, 0.9);
        color: white;
      }
      
      .tags.primary {
        background-color: rgba(60, 140, 92, 0.9);
        color: white;
      }
      
      /* Enhanced info section */
      .info {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        padding: 25px;
        position: relative;
      }
      
      .card h3 {
        font-size: 20px;
        line-height: 1.4;
        margin: 0 0 15px 0;
        color: var(--text-dark);
        font-weight: 700;
        transition: color 0.3s ease;
        min-height: 60px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-family: var(--font-accent);
      }
      
      .card:hover h3 {
        color: var(--primary-color);
      }
      
      .period {
        color: #666;
        margin: 0 0 15px 0;
        font-size: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        background-color: rgba(248, 249, 250, 0.6);
        padding: 10px 18px;
        border-radius: 30px;
        width: fit-content;
        transition: all 0.3s ease;
      }
      
      .card:hover .period {
        background-color: rgba(69, 161, 99, 0.1);
        box-shadow: 0 3px 15px rgba(69, 161, 99, 0.1);
      }
      
      .period i {
        color: var(--primary-color);
      }
      
      /* Enhanced actions section */
      .actions {
        margin-top: auto;
        padding: 20px 25px;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: rgba(248, 249, 250, 0.6);
      }
      
      /* Enhanced rating style */
      .rating {
        color: #f1c40f;
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 5px;
      }
      
      .stars {
        display: flex;
        align-items: center;
        gap: 2px;
      }
      
      .stars i {
        filter: drop-shadow(0 2px 4px rgba(241, 196, 15, 0.4));
      }
      
      .card:hover .stars i {
        animation: starPulse 0.8s ease-in-out;
      }
      
      @keyframes starPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
      }
      
      .rating-value {
        color: #666;
        font-weight: 600;
        margin-left: 5px;
      }
      
      /* Enhanced add to cart button */
      .add-to-cart-button {
        background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
        color: white;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 20px;
        border-radius: 50px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.4s ease;
        border: none;
        box-shadow: 0 8px 20px rgba(39, 94, 62, 0.3);
        position: relative;
        overflow: hidden;
      }
      
      .add-to-cart-button::before {
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
      }
      
      .add-to-cart-button:hover {
        background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
        transform: translateY(-5px);
        box-shadow: 0 12px 25px rgba(39, 94, 62, 0.4);
      }
      
      .add-to-cart-button:hover::before {
        left: 100%;
      }
      
      .add-to-cart-button i {
        font-size: 16px;
        transition: transform 0.3s ease;
      }
      
      .add-to-cart-button:hover i {
        transform: translateY(-3px);
      }
      
      /* Enhanced "last chance" badge */
      .last-chance-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: linear-gradient(135deg, rgba(231, 76, 60, 0.9) 0%, rgba(192, 57, 43, 0.9) 100%);
        color: white;
        padding: 8px 15px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        z-index: 10;
        transition: all 0.3s ease;
        animation: pulseBadge 2s infinite;
      }
      
      .last-chance-badge i {
        font-size: 14px;
      }
      
      @keyframes pulseBadge {
        0% { transform: scale(1); box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3); }
        50% { transform: scale(1.05); box-shadow: 0 5px 20px rgba(231, 76, 60, 0.5); }
        100% { transform: scale(1); box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3); }
      }
      
      .card:hover .last-chance-badge {
        animation: none;
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(231, 76, 60, 0.4);
      }
      
      /* Price range slider */
      .price-range-container {
        margin-top: 25px;
        padding: 15px 0;
      }
      
      .price-range-container h3 {
        font-size: 16px;
        color: #555;
        margin-bottom: 15px;
        font-weight: 500;
      }
      
      .range-slider {
        width: 100%;
        position: relative;
        margin-bottom: 30px;
      }
      
      .price-values {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        color: #666;
        font-size: 14px;
      }
      
      .slider-container {
        position: relative;
        height: 6px;
        background: #ddd;
        border-radius: 5px;
        margin: 0 8px;
      }
      
      .slider-track {
        height: 100%;
        position: absolute;
        border-radius: 5px;
        background: linear-gradient(to right, var(--primary-color), var(--primary-light));
      }
      
      .slider-input {
        -webkit-appearance: none;
        appearance: none;
        width: 100%;
        position: absolute;
        background: transparent;
        top: 0;
        height: 6px;
        margin: 0;
        z-index: 10;
      }
      
      .slider-input::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: white;
        cursor: pointer;
        border: 2px solid var(--primary-color);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
      }
      
      .slider-input::-moz-range-thumb {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: white;
        cursor: pointer;
        border: 2px solid var(--primary-color);
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
      }
      
      .slider-input:active::-webkit-slider-thumb,
      .slider-input:hover::-webkit-slider-thumb {
        transform: scale(1.2);
        background: var(--primary-color);
        border-color: white;
      }
      
      .slider-input:active::-moz-range-thumb,
      .slider-input:hover::-moz-range-thumb {
        transform: scale(1.2);
        background: var(--primary-color);
        border-color: white;
      }
      
      @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
      }
      
      .no-results i {
        font-size: 64px;
        color: #828977;
        margin-bottom: 25px;
        opacity: 0.5;
      }
      
      .no-results h3 {
        font-size: 24px;
        margin-bottom: 15px;
        color: #444;
      }
      
      .no-results p {
        color: #666;
        font-size: 18px;
        margin-bottom: 0;
      }
      
      /* Enhanced notification */
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
        transform: translateX(-50%) translateY(-20px);
        animation: notificationFadeIn 0.5s forwards;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
      }
      
      @keyframes notificationFadeIn {
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
      }
      
      .notification.success {
        background-color: rgba(69, 161, 99, 0.9);
        color: white;
      }
      
      .notification.info {
        background-color: rgba(52, 152, 219, 0.9);
        color: white;
      }
      
      .notification.error {
        background-color: rgba(231, 76, 60, 0.9);
        color: white;
      }
      
      .notification i {
        font-size: 22px;
      }

      /* New Featured Activities Section at the top */
      .featured-activities {
        max-width: 1200px;
        margin: 0 auto 60px;
        padding: 0 20px;
      }
      
      .featured-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
      }
      
      .featured-title {
        text-align: center;
        margin-bottom: 30px;
        font-size: 28px;
        color: var(--primary-dark);
        position: relative;
        display: inline-block;
        left: 50%;
        transform: translateX(-50%);
        padding-bottom: 15px;
      }
      
      .featured-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        border-radius: 2px;
      }
      
      .featured-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: linear-gradient(135deg, rgba(241, 196, 15, 0.9) 0%, rgba(243, 156, 18, 0.9) 100%);
        color: white;
        padding: 8px 15px;
        border-radius: 30px;
        font-size: 13px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 5px 15px rgba(241, 196, 15, 0.3);
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        z-index: 10;
        transition: all 0.3s ease;
      }
      
      .featured-badge i {
        font-size: 14px;
      }
      
      .card:hover .featured-badge {
        transform: translateY(-5px) rotate(-5deg);
        box-shadow: 0 10px 25px rgba(241, 196, 15, 0.4);
      }

      /* New filter pills visual system */
      .filter-pills {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin: 20px 0;
      }
      
      .filter-pill {
        cursor: pointer;
        padding: 10px 20px;
        border-radius: 50px;
        background: white;
        color: #555;
        font-weight: 500;
        transition: all 0.3s ease;
        border: 1px solid rgba(130, 137, 119, 0.2);
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 8px;
      }
      
      .filter-pill:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
      }
      
      .filter-pill.active {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
      }
      
      .filter-pill i {
        font-size: 14px;
        opacity: 0.7;
      }
      
      /* Loading animation */
      .loading-animation {
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 40px;
      }
      
      .loading-leaf {
        width: 40px;
        height: 40px;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%233c8c5c" d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" /></svg>');
        background-size: contain;
        background-repeat: no-repeat;
        animation: loadingRotate 1.8s infinite linear;
      }
      
      @keyframes loadingRotate {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
      }
      
      /* Enhanced scroll to top button */
      .scroll-top-button {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background-color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        z-index: 900;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.4s ease;
        overflow: hidden;
        border: 1px solid rgba(69, 161, 99, 0.1);
      }
      
      .scroll-top-button.visible {
        opacity: 1;
        transform: translateY(0);
      }
      
      .scroll-top-button::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: radial-gradient(circle at center, rgba(69, 161, 99, 0.1) 0%, rgba(69, 161, 99, 0) 70%);
        opacity: 0;
        transition: opacity 0.3s ease;
      }
      
      .scroll-top-button:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
      }
      
      .scroll-top-button:hover::before {
        opacity: 1;
      }
      
      .scroll-top-button i {
        font-size: 24px;
        color: var(--primary-color);
        transition: all 0.3s ease;
      }
      
      .scroll-top-button:hover i {
        color: var(--primary-dark);
        transform: translateY(-3px);
      }

      /* Add animated leaf elements */
      .leaf-animation-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1;
        overflow: hidden;
      }
      
      .floating-leaf {
        position: absolute;
        width: 40px;
        height: 40px;
        background-size: contain;
        background-repeat: no-repeat;
        opacity: 0.6;
        pointer-events: none;
        z-index: 0;
        animation: floatLeaf 18s linear infinite;
      }
      
      .leaf-1 {
        top: 10%;
        left: 5%;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%233c8c5c" d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z"/></svg>');
        animation-duration: 20s;
        animation-delay: 2s;
      }
      
      .leaf-3 {
        bottom: 20%;
        left: 10%;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%23c89e52" d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z"/></svg>');
        transform: rotate(120deg);
        animation-duration: 18s;
        animation-delay: 3s;
      }
      
      .leaf-5 {
        top: 40%;
        left: 15%;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%23275e3e" d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z"/></svg>');
        transform: rotate(80deg);
        animation-duration: 21s;
        animation-delay: 0.5s;
      }
      
      .leaf-7 {
        top: 70%;
        right: 10%;
        background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%233c8c5c" d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" /></svg>');
        animation-duration: 24s;
        animation-delay: 3.5s;
        transform: rotate(240deg);
      }
      
      @keyframes floatLeaf {
        0% {
          transform: translateY(0) translateX(0) rotate(0deg);
          opacity: 0;
        }
        10% {
          opacity: 0.6;
        }
        90% {
          opacity: 0.4;
        }
        100% {
          transform: translateY(-100vh) translateX(20vw) rotate(360deg);
          opacity: 0;
        }
      }

      /* Responsive adjustments */
      @media (max-width: 1200px) {
        .featured-grid {
          grid-template-columns: repeat(2, 1fr);
        }
      }
      
      @media (max-width: 992px) {
        .filters {
          flex-direction: column;
          gap: 15px;
        }
        
        .filter-group {
          min-width: 100%;
        }
        
        .reset-button {
          align-self: center;
          margin-top: 20px;
        }
        
        .featured-grid {
          grid-template-columns: 1fr;
        }
      }
      
      @media (max-width: 768px) {
        .activities-grid {
          grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        }
        
        .page-title {
          font-size: 32px;
        }
        
        .filter-section {
          padding: 20px;
        }
        
        .filter-section h2 {
          font-size: 20px;
        }
      }
      
      @media (max-width: 576px) {
        .search-input, .filter-select, .reset-button {
          padding: 12px 15px;
          font-size: 14px;
        }
        
        .search-input {
          padding-left: 40px;
        }
        
        .filter-pills {
          gap: 5px;
        }
        
        .filter-pill {
          padding: 8px 15px;
          font-size: 13px;
        }
      }
    </style>
  </head>
  <body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>

    <div class="page-wrapper">
      <!-- Floating leaf animation elements -->
      <div class="leaf-animation-container">
        <div class="floating-leaf leaf-1"></div>
        <div class="floating-leaf leaf-3"></div>
        <div class="floating-leaf leaf-5"></div>
        <div class="floating-leaf leaf-7"></div>
      </div>
      
      <div class="page-title-container">
        <h1 class="page-title">Découvrez Nos Activités</h1>
        <p class="page-subtitle">Explorez notre sélection d'expériences uniques et trouvez l'activité parfaite pour vous.</p>
      </div>

      <!-- Featured Activities Section -->
      <div id="filter-section" class="filter-section">
        <h2>Filtrer les activités</h2>
        
        <div class="filter-container">
          <div class="search-container">
            <input type="search" placeholder="Rechercher une activité..." id="search-input" class="search-input" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <i class="fa-solid fa-magnifying-glass"></i>
          </div>
          
          <!-- Quick Filter Pills -->
          <div class="filter-pills">
            <div class="filter-pill<?php echo !isset($_GET['price']) || $_GET['price'] === '' ? ' active' : ''; ?>" data-filter="price" data-value="">
              <i class="fa-solid fa-tag"></i> Tous les prix
            </div>
            <div class="filter-pill<?php echo isset($_GET['price']) && $_GET['price'] === 'gratuit' ? ' active' : ''; ?>" data-filter="price" data-value="gratuit">
              <i class="fa-solid fa-gift"></i> Gratuit
            </div>
            <div class="filter-pill<?php echo isset($_GET['price']) && $_GET['price'] === 'payant' ? ' active' : ''; ?>" data-filter="price" data-value="payant">
              <i class="fa-solid fa-euro-sign"></i> Payant
            </div>
            <div class="filter-pill<?php echo !isset($_GET['location']) || $_GET['location'] === '' ? ' active' : ''; ?>" data-filter="location" data-value="">
              <i class="fa-solid fa-location-dot"></i> Tous les lieux
            </div>
            <div class="filter-pill<?php echo isset($_GET['location']) && $_GET['location'] === 'interieur' ? ' active' : ''; ?>" data-filter="location" data-value="interieur">
              <i class="fa-solid fa-house"></i> Intérieur
            </div>
            <div class="filter-pill<?php echo isset($_GET['location']) && $_GET['location'] === 'exterieur' ? ' active' : ''; ?>" data-filter="location" data-value="exterieur">
              <i class="fa-solid fa-tree"></i> Extérieur
            </div>
          </div>
          
          <div class="filters">
            <div class="filter-group">
              <label>Catégorie</label>
              <select id="category-filter" class="filter-select">
                <option value="">Toutes les catégories</option>
                <?php
                // Display available categories
                foreach($availableTags as $tag) {
                    // Skip location and price tags
                    if ($tag !== 'interieur' && $tag !== 'exterieur' && $tag !== 'gratuit') {
                        $selected = isset($_GET['category']) && $_GET['category'] === $tag ? ' selected' : '';
                        echo '<option value="' . htmlspecialchars($tag) . '"' . $selected . '>' . ucfirst(str_replace('_', ' ', $tag)) . '</option>';
                    }
                }
                ?>
              </select>
            </div>
            
            <!-- Price Range Slider -->
            <div class="price-range-container">
              <h3>Fourchette de prix</h3>
              <div class="range-slider">
                <div class="slider-container">
                  <div class="slider-track" id="price-track"></div>
                  <input type="range" min="0" max="100" value="0" class="slider-input" id="min-price-slider">
                  <input type="range" min="0" max="100" value="100" class="slider-input" id="max-price-slider">
                </div>
                <div class="price-values">
                  <span id="min-price-value">0 €</span>
                  <span id="max-price-value">100 €</span>
                </div>
              </div>
            </div>
            
            <button id="reset-filters" class="reset-button">
              <i class="fa-solid fa-rotate"></i> Réinitialiser
            </button>
          </div>
          
          <!-- Active Filters Visualization -->
          <?php if(isset($_GET['search']) || isset($_GET['category']) || isset($_GET['location']) || isset($_GET['price'])): ?>
          <div class="active-filters">
            <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
            <div class="active-filter-tag">
              <i class="fa-solid fa-magnifying-glass"></i> 
              <?php echo htmlspecialchars($_GET['search']); ?>
              <i class="fa-solid fa-xmark"></i>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['category']) && !empty($_GET['category'])): ?>
            <div class="active-filter-tag">
              <i class="fa-solid fa-tag"></i>
              <?php echo ucfirst(str_replace('_', ' ', $_GET['category'])); ?>
              <i class="fa-solid fa-xmark"></i>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['location']) && !empty($_GET['location'])): ?>
            <div class="active-filter-tag location">
              <i class="fa-solid fa-location-dot"></i>
              <?php echo $_GET['location'] === 'interieur' ? 'Intérieur' : 'Extérieur'; ?>
              <i class="fa-solid fa-xmark"></i>
            </div>
            <?php endif; ?>
            
            <?php if(isset($_GET['price']) && !empty($_GET['price'])): ?>
            <div class="active-filter-tag price">
              <i class="fa-solid fa-euro-sign"></i>
              <?php echo $_GET['price'] === 'gratuit' ? 'Gratuit' : 'Payant'; ?>
              <i class="fa-solid fa-xmark"></i>
            </div>
            <?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Activities Display Section -->
      <div class="activities-container">
        <div class="activities-grid" id="activities-grid">
          <?php 
          if ($result->num_rows > 0) {
              $displayedCount = 0;
              
              // Display each activity
              while($row = $result->fetch_assoc()) {
                  $displayedCount++;
                  
                  // Generate a fixed random rating for consistency (based on ID)
                  $randomRating = (($row['id'] * 7) % 21 + 30) / 10; // Rating between 3.0 and 5.0 
                  
                  // Check if activity is ending soon
                  $isEnding = isEndingSoon($row);
                  $daysRemaining = $isEnding ? getDaysRemaining($row) : null;
                  
                  // List of tags
                  $tagList = $row["tags"] ? explode(',', $row["tags"]) : [];
                  
                  // Type of price
                  $isPaid = $row["prix"] > 0;
                  
                  echo '<div class="card" data-id="' . $row['id'] . '">';
                  echo '<div class="content">';
                  
                  // Image container with fixed size
                  echo '<div class="image-container">';
                  if ($row["image_url"]) {
                      echo '<img src="' . htmlspecialchars($row["image_url"]) . '" alt="' . htmlspecialchars($row["titre"]) . '" />';
                  } else {
                      echo '<img src="nature-placeholder.jpg" alt="placeholder" />';
                  }
                  echo '</div>';
                  
                  // Last chance badge if applicable
                  if ($isEnding) {
                      echo '<div class="last-chance-badge"><i class="fa-solid fa-clock"></i> ';
                      if ($daysRemaining == 0) {
                          echo 'Dernier jour !';
                      } else if ($daysRemaining == 1) {
                          echo 'Termine demain !';
                      } else {
                          echo 'Plus que ' . $daysRemaining . ' jours !';
                      }
                      echo '</div>';
                  }
                  
                  echo '<div class="tag">';
                  
                  // Display tags (limited to 2)
                  $displayedTags = 0;
                  foreach ($tagList as $tag) {
                      if ($displayedTags < 2) {
                          $tagClass = getTagClass($tag);
                          echo '<span class="tags ' . $tagClass . '" data-tag="' . htmlspecialchars($tag) . '">' . ucfirst(str_replace('_', ' ', $tag)) . '</span>';
                          $displayedTags++;
                      }
                  }
                  
                  // Display price status
                  if ($isPaid) {
                      echo '<span class="tags" data-tag="payant">Payant</span>';
                  } else {
                      echo '<span class="tags accent" data-tag="gratuit">Gratuit</span>';
                  }
                  
                  echo '</div></div>';
                  
                  echo '<div class="info">';
                  echo '<h3>' . htmlspecialchars($row["titre"]) . '</h3>';
                  
                  // Date or period
                  if ($row["date_ou_periode"]) {
                      echo '<p class="period"><i class="fa-regular fa-calendar"></i> ' . htmlspecialchars($row["date_ou_periode"]) . '</p>';
                  }
                  
                  echo '</div>';
                  
                  echo '<div class="actions">';
                  echo '<div class="rating">' . getStars($randomRating) . '</div>';
                  
                  // Add to cart button
                  echo '<button class="add-to-cart-button" data-id="' . $row['id'] . '" 
                        data-title="' . htmlspecialchars($row['titre']) . '" 
                        data-price="' . $row['prix'] . '" 
                        data-image="' . htmlspecialchars($row['image_url'] ? $row['image_url'] : 'nature-placeholder.jpg') . '" 
                        data-period="' . htmlspecialchars($row['date_ou_periode']) . '" 
                        data-tags="' . htmlspecialchars($row['tags']) . '">
                        <i class="fa-solid fa-cart-shopping"></i> Ajouter
                        </button>';
                  
                  echo '</div>';
                  
                  echo '</div>';
              }
              
              if ($displayedCount === 0) {
                  echo '<div class="no-results">';
                  echo '<i class="fa-solid fa-filter-circle-xmark"></i>';
                  echo '<h3>Aucune activité ne correspond à vos critères</h3>';
                  echo '<p>Essayez d\'ajuster vos filtres pour trouver des activités qui vous conviennent.</p>';
                  echo '</div>';
              }
          } else {
              echo '<div class="no-results">';
              echo '<i class="fa-solid fa-seedling"></i>';
              echo '<h3>Aucune activité disponible pour le moment</h3>';
              echo '<p>Revenez plus tard, de nouvelles activités seront bientôt ajoutées.</p>';
              echo '</div>';
          }
          ?>
        </div>
      </div>

      <!-- Scroll to top button -->
      <div class="scroll-top-button" id="scroll-top">
        <i class="fa-solid fa-arrow-up"></i>
      </div>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>

    <!-- Loading overlay with Synapse logo -->
    <div class="loading-overlay" id="loading-overlay">
      <div class="synapse-loader">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
          <path d="M50,5C25.1,5,5,25.1,5,50s20.1,45,45,45s45-20.1,45-45S74.9,5,50,5z M50,85c-19.3,0-35-15.7-35-35s15.7-35,35-35s35,15.7,35,35S69.3,85,50,85z"/>
          <path d="M50,20c-16.5,0-30,13.5-30,30s13.5,30,30,30s30-13.5,30-30S66.5,20,50,20z M50,70c-11,0-20-9-20-20s9-20,20-20s20,9,20,20S61,70,50,70z"/>
          <path d="M50,35c-8.3,0-15,6.7-15,15s6.7,15,15,15s15-6.7,15-15S58.3,35,50,35z M50,60c-5.5,0-10-4.5-10-10s4.5-10,10-10s10,4.5,10,10S55.5,60,50,60z"/>
        </svg>
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Initialize scroll animations
        createAnimatedElements();
        
        // Initialiser le panier s'il n'existe pas déjà
        if (!localStorage.getItem('synapse-cart')) {
          localStorage.setItem('synapse-cart', JSON.stringify([]));
        }
        
        // Mettre à jour le compteur du panier
        updateCartCount();
        
        // Price range slider functionality
        const minPriceSlider = document.getElementById('min-price-slider');
        const maxPriceSlider = document.getElementById('max-price-slider');
        const minPriceValue = document.getElementById('min-price-value');
        const maxPriceValue = document.getElementById('max-price-value');
        const priceTrack = document.getElementById('price-track');
        
        // Initialize min/max price values
        let minPrice = 0;
        let maxPrice = 100;
        
        // Update the price range display
        function updatePriceRange() {
          minPriceValue.textContent = minPrice + ' €';
          maxPriceValue.textContent = maxPrice + ' €';
          
          // Update slider track appearance
          const minPercent = (minPrice / 100) * 100;
          const maxPercent = (maxPrice / 100) * 100;
          priceTrack.style.left = minPercent + '%';
          priceTrack.style.width = (maxPercent - minPercent) + '%';
        }
        
        // Initialize price range
        updatePriceRange();
        
        // Add event listeners to price sliders
        if (minPriceSlider) {
          minPriceSlider.addEventListener('input', function() {
            minPrice = parseInt(this.value);
            if (minPrice > maxPrice) {
              maxPriceSlider.value = minPrice;
              maxPrice = minPrice;
            }
            updatePriceRange();
          });
        }
        
        if (maxPriceSlider) {
          maxPriceSlider.addEventListener('input', function() {
            maxPrice = parseInt(this.value);
            if (maxPrice < minPrice) {
              minPriceSlider.value = maxPrice;
              minPrice = maxPrice;
            }
            updatePriceRange();
          });
        }
        
        // Create a filter state object
        let filterState = {
          search: document.getElementById('search-input').value || '',
          category: document.getElementById('category-filter').value || '',
          price: {
            min: minPrice,
            max: maxPrice
          },
          isPaid: document.querySelector('.filter-pill[data-filter="price"].active').getAttribute('data-value') || '',
          location: document.querySelector('.filter-pill[data-filter="location"].active').getAttribute('data-value') || ''
        };
        
        // AJAX filter functionality
        function ajaxFilter() {
          // Show loading overlay
          document.getElementById('loading-overlay').classList.add('active');
          
          // Create FormData for AJAX request
          const formData = new FormData();
          formData.append('search', filterState.search);
          formData.append('category', filterState.category);
          formData.append('price_min', filterState.price.min);
          formData.append('price_max', filterState.price.max);
          formData.append('price', filterState.isPaid);
          formData.append('location', filterState.location);
          formData.append('ajax', 1);
          
          // Use fetch API to get filtered results
          fetch('filter_activities.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            // Update the activities grid with new content
            const activitiesGrid = document.getElementById('activities-grid');
            activitiesGrid.innerHTML = data.html;
            
            // Add event listeners to new elements
            initializeCardListeners();
            
            // Update URL with filter parameters without refreshing page
            let url = 'activites.php';
            const params = [];
            
            if (filterState.search) params.push(`search=${encodeURIComponent(filterState.search)}`);
            if (filterState.category) params.push(`category=${encodeURIComponent(filterState.category)}`);
            if (filterState.isPaid) params.push(`price=${encodeURIComponent(filterState.isPaid)}`);
            if (filterState.location) params.push(`location=${encodeURIComponent(filterState.location)}`);
            if (filterState.price.min > 0 || filterState.price.max < 100) {
              params.push(`price_min=${filterState.price.min}`);
              params.push(`price_max=${filterState.price.max}`);
            }
            
            if (params.length > 0) {
              url += '?' + params.join('&');
            }
            
            window.history.pushState({ path: url }, '', url);
            
            // Hide loading overlay
            document.getElementById('loading-overlay').classList.remove('active');
          })
          .catch(error => {
            console.error('Error:', error);
            // Hide loading overlay
            document.getElementById('loading-overlay').classList.remove('active');
            showNotification('Une erreur est survenue lors du filtrage des activités.', 'error');
          });
        }
        
        // Initialize event listeners for filter elements
        function initializeFilterListeners() {
          // Search input
          const searchInput = document.getElementById('search-input');
          if (searchInput) {
            searchInput.addEventListener('input', function() {
              filterState.search = this.value.trim();
            });
            
            searchInput.addEventListener('keypress', function(e) {
              if (e.key === 'Enter') {
                ajaxFilter();
              }
            });
          }
          
          // Category filter
          const categoryFilter = document.getElementById('category-filter');
          if (categoryFilter) {
            categoryFilter.addEventListener('change', function() {
              filterState.category = this.value;
              ajaxFilter();
            });
          }
          
          // Price filter pills
          document.querySelectorAll('.filter-pill[data-filter="price"]').forEach(pill => {
            pill.addEventListener('click', function() {
              // Update active state
              document.querySelectorAll('.filter-pill[data-filter="price"]').forEach(p => {
                p.classList.remove('active');
              });
              this.classList.add('active');
              
              // Update filter state
              filterState.isPaid = this.getAttribute('data-value');
              ajaxFilter();
            });
          });
          
          // Location filter pills
          document.querySelectorAll('.filter-pill[data-filter="location"]').forEach(pill => {
            pill.addEventListener('click', function() {
              // Update active state
              document.querySelectorAll('.filter-pill[data-filter="location"]').forEach(p => {
                p.classList.remove('active');
              });
              this.classList.add('active');
              
              // Update filter state
              filterState.location = this.getAttribute('data-value');
              ajaxFilter();
            });
          });
          
          // Price range slider
          const priceRangeContainer = document.querySelector('.price-range-container');
          if (priceRangeContainer) {
            const minSlider = document.getElementById('min-price-slider');
            const maxSlider = document.getElementById('max-price-slider');
            
            // Add debounced event listeners to avoid too many AJAX calls
            let priceRangeTimeout;
            
            if (minSlider) {
              minSlider.addEventListener('change', function() {
                clearTimeout(priceRangeTimeout);
                priceRangeTimeout = setTimeout(function() {
                  filterState.price.min = parseInt(minSlider.value);
                  ajaxFilter();
                }, 300);
              });
            }
            
            if (maxSlider) {
              maxSlider.addEventListener('change', function() {
                clearTimeout(priceRangeTimeout);
                priceRangeTimeout = setTimeout(function() {
                  filterState.price.max = parseInt(maxSlider.value);
                  ajaxFilter();
                }, 300);
              });
            }
          }
          
          // Reset button
          const resetButton = document.getElementById('reset-filters');
          if (resetButton) {
            resetButton.addEventListener('click', function() {
              // Reset all filter inputs
              if (searchInput) searchInput.value = '';
              if (categoryFilter) categoryFilter.value = '';
              if (minPriceSlider) minPriceSlider.value = 0;
              if (maxPriceSlider) maxPriceSlider.value = 100;
              
              // Reset filter pills
              document.querySelectorAll('.filter-pill').forEach(pill => {
                pill.classList.remove('active');
              });
              document.querySelectorAll('.filter-pill[data-value=""]').forEach(pill => {
                pill.classList.add('active');
              });
              
              // Reset filter state
              filterState = {
                search: '',
                category: '',
                price: { min: 0, max: 100 },
                isPaid: '',
                location: ''
              };
              
              // Update price range display
              minPrice = 0;
              maxPrice = 100;
              updatePriceRange();
              
              // Apply filters
              ajaxFilter();
            });
          }
          
          // Active filter tags
          document.querySelectorAll('.active-filter-tag').forEach(tag => {
            tag.addEventListener('click', function() {
              // Identify which filter to remove
              if (this.querySelector('i.fa-magnifying-glass')) {
                if (searchInput) searchInput.value = '';
                filterState.search = '';
              } else if (this.querySelector('i.fa-tag')) {
                if (categoryFilter) categoryFilter.value = '';
                filterState.category = '';
              } else if (this.querySelector('i.fa-location-dot')) {
                document.querySelectorAll('.filter-pill[data-filter="location"]').forEach(p => {
                  p.classList.remove('active');
                });
                document.querySelector('.filter-pill[data-filter="location"][data-value=""]').classList.add('active');
                filterState.location = '';
              } else if (this.querySelector('i.fa-euro-sign')) {
                document.querySelectorAll('.filter-pill[data-filter="price"]').forEach(p => {
                  p.classList.remove('active');
                });
                document.querySelector('.filter-pill[data-filter="price"][data-value=""]').classList.add('active');
                filterState.isPaid = '';
              }
              
              // Apply filters
              ajaxFilter();
            });
          });
        }
        
        // Initialize event listeners for cards
        function initializeCardListeners() {
          // Make activity cards clickable
          document.querySelectorAll('.card').forEach(card => {
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
          });
          
          // Make tags clickable
          document.querySelectorAll('.tags').forEach(tag => {
            tag.addEventListener('click', function(e) {
              e.stopPropagation(); // Prevent the card click event
              
              const tagName = this.getAttribute('data-tag');
              if (tagName) {
                // Determine which filter to use based on tag type
                if (tagName === 'gratuit' || tagName === 'payant') {
                  document.querySelectorAll('.filter-pill[data-filter="price"]').forEach(p => {
                    p.classList.remove('active');
                  });
                  document.querySelector('.filter-pill[data-filter="price"][data-value="' + tagName + '"]').classList.add('active');
                  filterState.isPaid = tagName;
                } else if (tagName === 'interieur' || tagName === 'exterieur') {
                  document.querySelectorAll('.filter-pill[data-filter="location"]').forEach(p => {
                    p.classList.remove('active');
                  });
                  document.querySelector('.filter-pill[data-filter="location"][data-value="' + tagName + '"]').classList.add('active');
                  filterState.location = tagName;
                } else {
                  if (categoryFilter) categoryFilter.value = tagName;
                  filterState.category = tagName;
                }
                
                // Apply filters
                ajaxFilter();
              }
            });
            
            // Change cursor on hover
            tag.style.cursor = 'pointer';
          });
          
          // Add to cart functionality
          document.querySelectorAll('.add-to-cart-button').forEach(button => {
            button.addEventListener('click', function(event) {
              event.stopPropagation(); // Prevent card click event
              
              const id = this.getAttribute('data-id');
              const titre = this.getAttribute('data-title');
              const prix = parseFloat(this.getAttribute('data-price'));
              const image = this.getAttribute('data-image');
              const periode = this.getAttribute('data-period');
              const tagsStr = this.getAttribute('data-tags');
              const tags = tagsStr ? tagsStr.split(',') : [];
              
              // Add to cart
              addToCart({
                id: id,
                titre: titre,
                prix: prix,
                image: image,
                periode: periode,
                tags: tags
              });
              
              // Button animation
              this.classList.add('clicked');
              setTimeout(() => {
                this.classList.remove('clicked');
              }, 300);
            });
          });
        }
        
        // Initialize all event listeners
        initializeFilterListeners();
        initializeCardListeners();
        
        // Function to add to cart
        function addToCart(item) {
          // Get current cart
          const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
          
          // Check if item is already in cart
          const existingItemIndex = cart.findIndex(cartItem => cartItem.id === item.id);
          
          // If item not in cart, add it
          if (existingItemIndex === -1) {
            cart.push(item);
            localStorage.setItem('synapse-cart', JSON.stringify(cart));
            updateCartCount();
            showNotification('Activité ajoutée au panier !', 'success');
          } else {
            showNotification('Cette activité est déjà dans votre panier.', 'info');
          }
        }
        
        // Function to update cart count
        function updateCartCount() {
          const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
          const cartCount = document.getElementById('panier-count');
          if (cartCount) {
            cartCount.textContent = cart.length;
          }
        }
        
        // Function to show notification
        function showNotification(message, type = 'success') {
          // Remove existing notifications
          const existingNotifications = document.querySelectorAll('.notification');
          existingNotifications.forEach(notification => {
            notification.remove();
          });
          
          // Create notification
          const notification = document.createElement('div');
          notification.classList.add('notification', type);
          
          // Add icon
          let icon = 'fa-circle-check';
          if (type === 'info') {
            icon = 'fa-circle-info';
          } else if (type === 'error') {
            icon = 'fa-circle-exclamation';
          }
          
          notification.innerHTML = `<i class="fa-solid ${icon}"></i> ${message}`;
          
          // Add to document
          document.body.appendChild(notification);
          
          // Auto-hide notification
          setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
              notification.remove();
            }, 500);
          }, 3000);
        }
        
        // Scroll to top functionality
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
        
        // Function to create animated elements
        function createAnimatedElements() {
          // Create particles
          for (let i = 0; i < 15; i++) {
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
  </body>
</html>

<?php
$stmt->close();
$conn->close();
?>