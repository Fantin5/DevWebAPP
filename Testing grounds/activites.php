<?php
ob_start(); // Start output buffering to prevent header issues

// Configuration de la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "activity";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Échec de la connexion: " . $conn->connect_error);

// Build query based on filters
$where_clauses = [];
$params = [];
$types = "";

// Search filter
if (!empty($_GET['search'])) {
    $search = '%' . trim($_GET['search']) . '%';
    $where_clauses[] = "(a.titre LIKE ? OR a.description LIKE ?)";
    $params[] = $search;
    $params[] = $search;
    $types .= "ss";
}

// Category filter
if (!empty($_GET['category'])) {
  $categories = explode(',', trim($_GET['category']));
  $categoryWhereClauses = [];
  
  foreach ($categories as $category) {
      $categoryWhereClauses[] = "EXISTS (SELECT 1 FROM tags t WHERE t.activite_id = a.id AND t.nom_tag = ?)";
      $params[] = $category;
      $types .= "s";
  }
  
  if (!empty($categoryWhereClauses)) {
      $where_clauses[] = "(" . implode(" OR ", $categoryWhereClauses) . ")";
  }
}

// Location filter (indoor/outdoor)
if (!empty($_GET['location'])) {
    $location = trim($_GET['location']);
    $where_clauses[] = "EXISTS (SELECT 1 FROM tags t WHERE t.activite_id = a.id AND t.nom_tag = ?)";
    $params[] = $location;
    $types .= "s";
}

// Price filter (free/paid)
if (!empty($_GET['price'])) {
    $price = trim($_GET['price']);
    $where_clauses[] = $price === 'gratuit' ? "a.prix = 0" : "a.prix > 0";
}

// Price range filters
if (isset($_GET['price_min']) && $_GET['price_min'] !== '') {
    $where_clauses[] = "a.prix >= ?";
    $params[] = floatval($_GET['price_min']);
    $types .= "d";
}

if (isset($_GET['price_max']) && $_GET['price_max'] !== '') {
    $where_clauses[] = "a.prix <= ?";
    $params[] = floatval($_GET['price_max']);
    $types .= "d";
}

// Build the SQL query
$sql = "SELECT a.*, 
        (SELECT GROUP_CONCAT(nom_tag) FROM tags WHERE activite_id = a.id) AS tags,
        DATEDIFF(STR_TO_DATE(SUBSTRING_INDEX(date_ou_periode, ' - ', -1), '%d/%m/%Y'), NOW()) as days_remaining
        FROM activites a";

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY date_creation DESC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Helper functions
function getStars($rating) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    
    $stars = '';
    for ($i = 0; $i < $fullStars; $i++) $stars .= '<i class="fa-solid fa-star"></i>';
    if ($halfStar) $stars .= '<i class="fa-solid fa-star-half-stroke"></i>';
    for ($i = 0; $i < $emptyStars; $i++) $stars .= '<i class="fa-regular fa-star"></i>';
    
    return '<span class="stars">' . $stars . '</span> <span class="rating-value">' . number_format($rating, 1) . '</span>';
}

function getTagClass($tag) {
    $tagClasses = [
        'art' => 'primary', 'cuisine' => 'secondary', 'bien_etre' => 'accent',
        'creativite' => 'primary', 'sport' => 'secondary', 'exterieur' => 'accent',
        'interieur' => 'secondary', 'gratuit' => 'accent', 'ecologie' => 'primary',
        'randonnee' => 'accent', 'jardinage' => 'primary', 'meditation' => 'secondary',
        'artisanat' => 'accent'
    ];
    return isset($tagClasses[$tag]) ? $tagClasses[$tag] : '';
}

function isEndingSoon($activity) {
    if (isset($activity['days_remaining']) && is_numeric($activity['days_remaining']) && 
        $activity['days_remaining'] >= 0 && $activity['days_remaining'] <= 7) {
        return true;
    }
    
    if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})\s*-\s*(\d{1,2})\/(\d{1,2})\/(\d{4})/', 
                    $activity['date_ou_periode'], $matches)) {
        $endDate = new DateTime("{$matches[6]}-{$matches[5]}-{$matches[4]}");
        $now = new DateTime();
        $diff = $now->diff($endDate);
        if (!$diff->invert && $diff->days <= 7) return true;
    }
    
    return false;
}

function getDaysRemaining($activity) {
    if (isset($activity['days_remaining']) && is_numeric($activity['days_remaining'])) {
        return $activity['days_remaining'];
    }
    
    if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})\s*-\s*(\d{1,2})\/(\d{1,2})\/(\d{4})/', 
                    $activity['date_ou_periode'], $matches)) {
        $endDate = new DateTime("{$matches[6]}-{$matches[5]}-{$matches[4]}");
        $now = new DateTime();
        $diff = $now->diff($endDate);
        if (!$diff->invert) return $diff->days;
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

// Check if this is an AJAX request and return only the necessary HTML
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    ob_clean(); // Clear output buffer
    header('Content-Type: application/json');
    
    // Generate activities HTML
    $activitiesHtml = '';
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $randomRating = (($row['id'] * 7) % 21 + 30) / 10;
            $isEnding = isEndingSoon($row);
            $daysRemaining = $isEnding ? getDaysRemaining($row) : null;
            $tagList = $row["tags"] ? explode(',', $row["tags"]) : [];
            $isPaid = $row["prix"] > 0;
            
            $activitiesHtml .= '<div class="card" data-id="' . $row['id'] . '">';
            $activitiesHtml .= '<div class="content">';
            
            // Image container
            $activitiesHtml .= '<div class="image-container">';
            $activitiesHtml .= '<img src="' . htmlspecialchars($row["image_url"] ?: 'nature-placeholder.jpg') . '" alt="' . htmlspecialchars($row["titre"]) . '" />';
            $activitiesHtml .= '</div>';
            
            // Price tag
            if ($isPaid) {
                $activitiesHtml .= '<div class="price-tag">';
                $activitiesHtml .= '<i class="fa-solid fa-euro-sign"></i> ' . number_format($row["prix"], 2) . ' €';
                $activitiesHtml .= '</div>';
            } else {
                $activitiesHtml .= '<div class="price-tag free">';
                $activitiesHtml .= '<i class="fa-solid fa-gift"></i> Gratuit';
                $activitiesHtml .= '</div>';
            }
            
            // Last chance badge
            if ($isEnding) {
                $activitiesHtml .= '<div class="last-chance-badge"><i class="fa-solid fa-clock"></i> ';
                if ($daysRemaining == 0) {
                    $activitiesHtml .= 'Dernier jour !';
                } else if ($daysRemaining == 1) {
                    $activitiesHtml .= 'Termine demain !';
                } else {
                    $activitiesHtml .= 'Plus que ' . $daysRemaining . ' jours !';
                }
                $activitiesHtml .= '</div>';
            }
            
            // Tags
            $activitiesHtml .= '<div class="tag">';
            $displayedTags = 0;
            foreach ($tagList as $tag) {
                if ($displayedTags < 2) {
                    $tagClass = getTagClass($tag);
                    $activitiesHtml .= '<span class="tags ' . $tagClass . '" data-tag="' . $tag . '">' . ucfirst(str_replace('_', ' ', $tag)) . '</span>';
                    $displayedTags++;
                }
            }
            $activitiesHtml .= '</div></div>';
            
            // Info
            $activitiesHtml .= '<div class="info">';
            $activitiesHtml .= '<h3>' . htmlspecialchars($row["titre"]) . '</h3>';
            
            if ($row["date_ou_periode"]) {
                $activitiesHtml .= '<p class="period"><i class="fa-regular fa-calendar"></i> ' . htmlspecialchars($row["date_ou_periode"]) . '</p>';
            }
            
            $activitiesHtml .= '</div>';
            
            // Actions
            $activitiesHtml .= '<div class="actions">';
            $activitiesHtml .= '<div class="rating">' . getStars($randomRating) . '</div>';
            
            $activitiesHtml .= '<button class="add-to-cart-button" data-id="' . $row['id'] . '" 
                    data-title="' . htmlspecialchars($row['titre']) . '" 
                    data-price="' . $row['prix'] . '" 
                    data-image="' . htmlspecialchars($row['image_url'] ?: 'nature-placeholder.jpg') . '" 
                    data-period="' . htmlspecialchars($row['date_ou_periode']) . '" 
                    data-tags="' . htmlspecialchars($row['tags']) . '">
                    <i class="fa-solid fa-cart-shopping"></i> Ajouter
                </button>';
            
            $activitiesHtml .= '</div>';
            $activitiesHtml .= '</div>';
        }
    } else {
        $activitiesHtml .= '<div class="no-results">';
        $activitiesHtml .= '<i class="fa-solid fa-filter-circle-xmark"></i>';
        $activitiesHtml .= '<h3>Aucune activité ne correspond à vos critères</h3>';
        $activitiesHtml .= '<p>Essayez d\'ajuster vos filtres pour trouver des activités qui vous conviennent.</p>';
        $activitiesHtml .= '</div>';
    }
    
    echo json_encode(['html' => $activitiesHtml]);
    exit;
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
        /* Fix for the circle under footer */
        .particle {
            position: fixed !important;
            z-index: -5 !important; /* Lower z-index to keep behind content */
        }
        
        .floating-leaf {
            z-index: -5 !important; /* Lower z-index for floating leaves */
        }
        
        .leaf-animation-container {
            pointer-events: none; /* Allow clicking through leaves */
            z-index: -4;
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        body {
            position: relative;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        main, .page-wrapper {
            flex: 1;
            position: relative;
            z-index: 1; /* Higher than background elements */
        }
        
        footer {
            position: relative;
            z-index: 10;
        }
        
        /* Enhanced Page styles */
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
            animation: fadeIn 1s ease-out;
            z-index: 2;
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
            width: 0;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border-radius: 2px;
            animation: expandWidth 1.5s forwards 0.5s;
        }
        
        @keyframes expandWidth {
            to { width: 80px; }
        }
        
        .page-subtitle {
            font-size: 18px;
            color: #555;
            max-width: 800px;
            margin: 25px auto 0;
            opacity: 0;
            animation: fadeIn 1s forwards 0.8s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Enhanced Filter section */
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
            animation: slideUp 1s forwards;
            z-index: 2;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .filter-section:hover {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
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
        
        /* Search & filter elements */
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
        
        /* Filter pills */
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
        
        /* Filter form elements */
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
        
        /* Price inputs */
        .price-inputs {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 10px;
        }
        
        .price-input-group {
            flex: 1;
            position: relative;
        }
        
        .price-input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid rgba(130, 137, 119, 0.3);
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.7);
        }
        
        .price-input:focus {
            outline: none;
            border-color: var(--primary-color);
            background-color: white;
            box-shadow: 0 0 0 3px rgba(69, 161, 99, 0.2);
        }
        
        .price-input-group::after {
            content: '€';
            position: absolute;
            right: 15px;
            bottom: 12px;
            color: #666;
            font-weight: 500;
        }
        
        .price-between {
            font-size: 18px;
            color: #666;
            margin-top: 25px;
        }
        
        .price-apply-button,
        .reset-button {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s ease;
            display: block;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .price-apply-button::before,
        .reset-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
            transform: skewX(-25deg);
            transition: left 0.5s ease;
        }
        
        .price-apply-button:hover,
        .reset-button:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(39, 94, 62, 0.2);
        }
        
        .price-apply-button:hover::before,
        .reset-button:hover::before {
            left: 100%;
        }
        
        .reset-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .reset-button i {
            transition: transform 0.3s ease;
        }
        
        .reset-button:hover i {
            transform: rotate(180deg);
        }
        
        /* Active filter tags */
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
            animation: tagPop 0.5s ease-out;
        }
        
        @keyframes tagPop {
            0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .active-filter-tag:hover {
            background-color: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }
        
        .active-filter-tag i.fa-xmark {
            font-size: 12px;
            margin-left: 5px;
        }
        
        .active-filter-tag.price {
            background-color: var(--secondary-color);
        }
        
        .active-filter-tag.location {
            background-color: var(--accent-color);
            color: #333;
        }
        
        /* Activities grid */
        .activities-container {
            width: 95%;
            max-width: 1200px;
            margin: 40px auto 60px;
            position: relative;
            z-index: 2;
        }
        
        .activities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            position: relative;
            animation: fadeIn 1s forwards;
        }
        
        /* Card styling */
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
            animation: cardAppear 0.6s ease-out forwards;
            opacity: 0;
            transform: translateY(30px);
        }
        
        @keyframes cardAppear {
            to { opacity: 1; transform: translateY(0); }
        }
        
        .card:nth-child(3n+1) { animation-delay: 0.1s; }
        .card:nth-child(3n+2) { animation-delay: 0.2s; }
        .card:nth-child(3n+3) { animation-delay: 0.3s; }
        
        .card:hover {
            transform: translateY(-15px) rotateY(5deg);
            box-shadow: -10px 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .image-container {
            height: 220px;
            min-height: 220px;
            overflow: hidden;
            position: relative;
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
        
        /* Price tag and Last chance badge */
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
        
        .card:hover .price-tag {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }
        
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
            animation: pulseBadge 2s infinite;
        }
        
        @keyframes pulseBadge {
            0% { transform: scale(1); box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3); }
            50% { transform: scale(1.05); box-shadow: 0 5px 20px rgba(231, 76, 60, 0.5); }
            100% { transform: scale(1); box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3); }
        }
        
        /* Tags and content */
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
            cursor: pointer;
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
        
        .actions {
            margin-top: auto;
            padding: 20px 25px;
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(248, 249, 250, 0.6);
        }
        
        .rating {
            color: #f1c40f;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 5px;
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
            background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
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
        
        /* No results message */
        .no-results {
            text-align: center;
            padding: 60px 30px;
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            grid-column: 1/-1;
            animation: fadeIn 0.8s ease;
        }
        
        .no-results i {
            font-size: 64px;
            color: #828977;
            margin-bottom: 25px;
            opacity: 0.5;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }
        
        .loading-overlay.active {
            opacity: 1;
            pointer-events: all;
        }
        
        .synapse-loader {
            width: 100px;
            height: 100px;
            position: relative;
            animation: float 2s infinite ease-in-out alternate;
        }
        
        .synapse-loader svg {
            width: 100%;
            height: 100%;
            filter: drop-shadow(0 5px 15px rgba(69, 161, 99, 0.3));
            transition: all 0.3s ease;
        }
        
        .synapse-loader svg path {
            fill: var(--primary-color);
            opacity: 0.9;
        }
        
        @keyframes float {
            0% { transform: translateY(-10px) rotate(-5deg); }
            100% { transform: translateY(10px) rotate(5deg); }
        }
        
        .loading-text {
            margin-top: 20px;
            font-size: 26px;
            color: var(--primary-color);
            font-weight: 700;
            letter-spacing: 3px;
            position: relative;
            text-shadow: 0 2px 10px rgba(69, 161, 99, 0.2);
            animation: pulse 2s infinite alternate;
        }
        
        @keyframes pulse {
            0% { opacity: 0.7; transform: scale(0.98); }
            100% { opacity: 1; transform: scale(1.02); }
        }
        
        /* Scroll to top button */
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
        
        .scroll-top-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }
        
        /* Notification */
        .notification {
            position: fixed;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 25px;
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
        }
        
        @keyframes notificationFadeIn {
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }
        
        .notification.success { background-color: rgba(69, 161, 99, 0.9); color: white; }
        .notification.info { background-color: rgba(52, 152, 219, 0.9); color: white; }
        .notification.error { background-color: rgba(231, 76, 60, 0.9); color: white; }
        
        /* Category checkboxes */
        .category-checkboxes {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 5px;
        }
        
        .category-checkbox {
            display: flex;
            align-items: center;
            background: white;
            padding: 8px 15px;
            border-radius: 30px;
            transition: all 0.3s;
            border: 1px solid rgba(130, 137, 119, 0.2);
            cursor: pointer;
            box-shadow: 0 3px 5px rgba(0,0,0,0.05);
            will-change: transform;
            transform: translateZ(0);
        }
        
        .category-checkbox:hover {
            background-color: rgba(69, 161, 99, 0.1);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.08);
        }
        
        .category-checkbox input {
            display: none;
        }
        
        .category-checkbox label {
            margin: 0;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        .category-checkbox input:checked + label {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .category-checkbox input:checked + label::before {
            content: '•';
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .selected-categories {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
        }
        
        .selected-category {
            background-color: var(--primary-color);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            animation: tagPop 0.5s ease-out;
            box-shadow: 0 3px 8px rgba(69, 161, 99, 0.2);
        }
        
        .selected-category i {
            font-size: 10px;
            transition: transform 0.2s ease;
        }
        
        .selected-category:hover i {
            transform: scale(1.2);
        }
        
        /* Media queries */
        @media (max-width: 992px) {
            .filters { flex-direction: column; }
            .filter-group { min-width: 100%; }
        }
        
        @media (max-width: 768px) {
            .activities-grid { grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
            .page-title { font-size: 32px; }
            .filter-section { padding: 20px; }
            .category-checkboxes { gap: 6px; }
            .category-checkbox { padding: 6px 12px; }
        }
        
        @media (max-width: 576px) {
            .search-input, .filter-select { padding: 12px 15px; font-size: 14px; }
            .search-input { padding-left: 40px; }
            .filter-pills { gap: 5px; }
            .filter-pill { padding: 8px 15px; font-size: 13px; }
            .category-checkbox label { font-size: 12px; }
        }
    </style>
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ob_end_flush(); ?>

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

        <!-- Filter Section -->
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
                        <label>Catégories</label>
                        <div class="category-checkboxes">
                            <?php
                            foreach($availableTags as $tag) {
                                if ($tag !== 'interieur' && $tag !== 'exterieur' && $tag !== 'gratuit') {
                                    $checked = isset($_GET['category']) && (strpos($_GET['category'], $tag) !== false) ? ' checked' : '';
                                    echo '<div class="category-checkbox">
                                        <input type="checkbox" id="cat_' . htmlspecialchars($tag) . '" name="category[]" value="' . htmlspecialchars($tag) . '"' . $checked . '>
                                        <label for="cat_' . htmlspecialchars($tag) . '">' . ucfirst(str_replace('_', ' ', $tag)) . '</label>
                                    </div>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- Price Inputs -->
                    <div class="filter-group">
                        <label>Fourchette de prix</label>
                        <div class="price-inputs">
                            <div class="price-input-group">
                                <input type="number" min="0" max="999" placeholder="Min" class="price-input" id="price-min" value="<?php echo isset($_GET['price_min']) ? htmlspecialchars($_GET['price_min']) : ''; ?>">
                            </div>
                            <div class="price-between">à</div>
                            <div class="price-input-group">
                                <input type="number" min="0" max="999" placeholder="Max" class="price-input" id="price-max" value="<?php echo isset($_GET['price_max']) ? htmlspecialchars($_GET['price_max']) : ''; ?>">
                            </div>
                        </div>
                        <button id="apply-price" class="price-apply-button">Appliquer</button>
                    </div>
                    
                    <div class="filter-group">
                        <button id="reset-filters" class="reset-button">
                            <i class="fa-solid fa-rotate"></i> Réinitialiser
                        </button>
                    </div>
                </div>
                
                <!-- Active Filters -->
                <?php if(isset($_GET['search']) || isset($_GET['category']) || isset($_GET['location']) || isset($_GET['price']) || isset($_GET['price_min']) || isset($_GET['price_max'])): ?>
                <div class="active-filters">
                    <?php if(isset($_GET['search']) && !empty($_GET['search'])): ?>
                    <div class="active-filter-tag">
                        <i class="fa-solid fa-magnifying-glass"></i> 
                        <?php echo htmlspecialchars($_GET['search']); ?>
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_GET['category']) && !empty($_GET['category'])): 
                    $categories = explode(',', $_GET['category']);
                    foreach($categories as $category): ?>
                    <div class="active-filter-tag category" data-category="<?php echo htmlspecialchars($category); ?>">
                        <i class="fa-solid fa-tag"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $category)); ?>
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <?php endforeach; endif; ?>
                    
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
                    
                    <?php if((isset($_GET['price_min']) && $_GET['price_min'] !== '') || (isset($_GET['price_max']) && $_GET['price_max'] !== '')): ?>
                    <div class="active-filter-tag price">
                        <i class="fa-solid fa-euro-sign"></i>
                        <?php 
                        $min = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? $_GET['price_min'] . '€' : '0€';
                        $max = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? $_GET['price_max'] . '€' : '∞';
                        echo $min . ' à ' . $max;
                        ?>
                        <i class="fa-solid fa-xmark"></i>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Activities Display -->
        <div class="activities-container">
            <div class="activities-grid" id="activities-grid">
                <?php 
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        $randomRating = (($row['id'] * 7) % 21 + 30) / 10;
                        $isEnding = isEndingSoon($row);
                        $daysRemaining = $isEnding ? getDaysRemaining($row) : null;
                        $tagList = $row["tags"] ? explode(',', $row["tags"]) : [];
                        $isPaid = $row["prix"] > 0;
                        
                        echo '<div class="card" data-id="' . $row['id'] . '">';
                        echo '<div class="content">';
                        
                        // Image
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
                        
                        // Last chance badge
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
                        
                        // Info section
                        echo '<div class="info">';
                        echo '<h3>' . htmlspecialchars($row["titre"]) . '</h3>';
                        
                        if ($row["date_ou_periode"]) {
                            echo '<p class="period"><i class="fa-regular fa-calendar"></i> ' . htmlspecialchars($row["date_ou_periode"]) . '</p>';
                        }
                        
                        echo '</div>';
                        
                        // Actions
                        echo '<div class="actions">';
                        echo '<div class="rating">' . getStars($randomRating) . '</div>';
                        
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
                } else {
                    echo '<div class="no-results">';
                    echo '<i class="fa-solid fa-filter-circle-xmark"></i>';
                    echo '<h3>Aucune activité ne correspond à vos critères</h3>';
                    echo '<p>Essayez d\'ajuster vos filtres pour trouver des activités qui vous conviennent.</p>';
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

    <!-- Loading overlay -->
    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-animation">
            <div class="synapse-loader">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                    <!-- Natural leaf SVG -->
                    <path d="M50,5c0,0-40,25-40,55c0,10,5,20,15,25c5-15,15-25,25-25c-15-10-20-30,0-55" />
                    <path d="M50,5c0,0,40,25,40,55c0,10-5,20-15,25c-5-15-15-25-25-25c15-10,20-30,0-55" />
                </svg>
            </div>
            <div class="loading-text">SYNAPSE</div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize the cart if it doesn't exist
        if (!localStorage.getItem('synapse-cart')) {
            localStorage.setItem('synapse-cart', JSON.stringify([]));
        }
        
        // Update cart count
        updateCartCount();
        
        // Create a filter state object
        let filterState = {
            search: document.getElementById('search-input').value || '',
            category: '',
            priceMin: document.getElementById('price-min').value || '',
            priceMax: document.getElementById('price-max').value || '',
            isPaid: '',
            location: ''
        };
        
        // Initialize category filters from URL
        if (window.location.search.includes('category=')) {
            const urlParams = new URLSearchParams(window.location.search);
            const categories = urlParams.get('category').split(',');
            filterState.category = categories.join(',');
            
            // Check the corresponding checkboxes
            categories.forEach(cat => {
                const checkbox = document.querySelector(`input[id="cat_${cat}"]`);
                if (checkbox) checkbox.checked = true;
            });
        }
        
        // Initialize isPaid and location from active pills
        document.querySelectorAll('.filter-pill[data-filter="price"].active').forEach(pill => {
            filterState.isPaid = pill.getAttribute('data-value') || '';
        });
        
        document.querySelectorAll('.filter-pill[data-filter="location"].active').forEach(pill => {
            filterState.location = pill.getAttribute('data-value') || '';
        });
        
        // Search input with debounce
        const searchInput = document.getElementById('search-input');
        let searchTimeout;
        
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    filterState.search = this.value.trim();
                    ajaxFilter();
                }, 300);
            });
            
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    clearTimeout(searchTimeout);
                    filterState.search = this.value.trim();
                    ajaxFilter();
                }
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
                
                // Clear price min/max
                document.getElementById('price-min').value = '';
                document.getElementById('price-max').value = '';
                filterState.priceMin = '';
                filterState.priceMax = '';
                
                ajaxFilter();
            });
        });
        
        // Category filters
        document.querySelectorAll('.category-checkbox input').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateCategoryFilter();
            });
        });
        
        function updateCategoryFilter() {
            // Get all checked category checkboxes
            const checkedCategories = Array.from(
                document.querySelectorAll('.category-checkbox input:checked')
            ).map(cb => cb.value);
            
            // Update filter state
            filterState.category = checkedCategories.join(',');
            
            // Update selected categories display
            updateSelectedCategoriesDisplay(checkedCategories);
            
            // Apply filters
            ajaxFilter();
        }
        
        function updateSelectedCategoriesDisplay(categories) {
            // Get or create container
            let container = document.querySelector('.selected-categories');
            if (!container) {
                container = document.createElement('div');
                container.className = 'selected-categories';
                document.querySelector('.category-checkboxes').after(container);
            }
            
            // Clear container
            container.innerHTML = '';
            
            // Add tag for each selected category
            categories.forEach(category => {
                const tag = document.createElement('div');
                tag.className = 'selected-category';
                tag.innerHTML = `
                    ${ucfirst(category.replace('_', ' '))}
                    <i class="fa-solid fa-xmark" data-category="${category}"></i>
                `;
                container.appendChild(tag);
                
                // Add click handler
                tag.querySelector('i').addEventListener('click', function(e) {
                    e.stopPropagation();
                    // Uncheck the corresponding checkbox
                    document.querySelector(`input[value="${this.dataset.category}"]`).checked = false;
                    updateCategoryFilter();
                });
            });
            
            // Show/hide based on categories
            if (categories.length > 0) {
                container.style.display = 'flex';
            } else {
                container.style.display = 'none';
            }
        }
        
        // Helper function for capitalizing first letter
        function ucfirst(string) {
            return string.charAt(0).toUpperCase() + string.slice(1);
        }
        
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
        
        // Price range filter
        const priceMin = document.getElementById('price-min');
        const priceMax = document.getElementById('price-max');
        const applyPriceButton = document.getElementById('apply-price');
        
        if (applyPriceButton) {
            applyPriceButton.addEventListener('click', function() {
                filterState.priceMin = priceMin.value;
                filterState.priceMax = priceMax.value;
                
                // Clear price pill filters
                document.querySelectorAll('.filter-pill[data-filter="price"]').forEach(p => {
                    p.classList.remove('active');
                });
                document.querySelector('.filter-pill[data-filter="price"][data-value=""]').classList.add('active');
                filterState.isPaid = '';
                
                ajaxFilter();
            });
        }
        
        // Reset button
        const resetButton = document.getElementById('reset-filters');
        if (resetButton) {
            resetButton.addEventListener('click', function() {
                // Reset all filter inputs
                if (searchInput) searchInput.value = '';
                if (priceMin) priceMin.value = '';
                if (priceMax) priceMax.value = '';
                
                // Reset filter pills
                document.querySelectorAll('.filter-pill').forEach(pill => {
                    pill.classList.remove('active');
                });
                document.querySelectorAll('.filter-pill[data-value=""]').forEach(pill => {
                    pill.classList.add('active');
                });
                
                // Reset category checkboxes
                document.querySelectorAll('.category-checkbox input').forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                // Clear selected categories display
                const selectedCategoriesContainer = document.querySelector('.selected-categories');
                if (selectedCategoriesContainer) {
                    selectedCategoriesContainer.innerHTML = '';
                    selectedCategoriesContainer.style.display = 'none';
                }
                
                // Reset filter state
                filterState = {
                    search: '',
                    category: '',
                    priceMin: '',
                    priceMax: '',
                    isPaid: '',
                    location: ''
                };
                
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
                    ajaxFilter();
                } else if (this.classList.contains('category')) {
                    // Get the category to remove
                    const categoryToRemove = this.getAttribute('data-category');
                    
                    // Update checkboxes if they exist
                    const checkbox = document.querySelector(`.category-checkbox input[value="${categoryToRemove}"]`);
                    if (checkbox) {
                        checkbox.checked = false;
                        updateCategoryFilter();
                    } else {
                        // If direct URL navigation, update filter state by removing this category
                        const categories = filterState.category.split(',');
                        const filteredCategories = categories.filter(cat => cat !== categoryToRemove);
                        filterState.category = filteredCategories.join(',');
                        ajaxFilter();
                    }
                } else if (this.querySelector('i.fa-location-dot')) {
                    // Reset location pills
                    document.querySelectorAll('.filter-pill[data-filter="location"]').forEach(p => {
                        p.classList.remove('active');
                    });
                    document.querySelector('.filter-pill[data-filter="location"][data-value=""]').classList.add('active');
                    filterState.location = '';
                    ajaxFilter();
                } else if (this.querySelector('i.fa-euro-sign')) {
                    // Check if this is price range or price type
                    const text = this.textContent.trim();
                    if (text.includes('à')) {
                        // It's a price range
                        if (priceMin) priceMin.value = '';
                        if (priceMax) priceMax.value = '';
                        filterState.priceMin = '';
                        filterState.priceMax = '';
                    } else {
                        // It's a price type
                        document.querySelectorAll('.filter-pill[data-filter="price"]').forEach(p => {
                            p.classList.remove('active');
                        });
                        document.querySelector('.filter-pill[data-filter="price"][data-value=""]').classList.add('active');
                        filterState.isPaid = '';
                    }
                    ajaxFilter();
                }
            });
        });
        
        // AJAX filter function
        function ajaxFilter() {
            // Show loading overlay
            const loadingOverlay = document.getElementById('loading-overlay');
            loadingOverlay.classList.add('active');
            
            // Create URL with filter parameters
            let url = 'activites.php';
            const params = [];
            
            if (filterState.search) params.push(`search=${encodeURIComponent(filterState.search)}`);
            if (filterState.category) params.push(`category=${encodeURIComponent(filterState.category)}`);
            if (filterState.isPaid) params.push(`price=${encodeURIComponent(filterState.isPaid)}`);
            if (filterState.location) params.push(`location=${encodeURIComponent(filterState.location)}`);
            if (filterState.priceMin) params.push(`price_min=${encodeURIComponent(filterState.priceMin)}`);
            if (filterState.priceMax) params.push(`price_max=${encodeURIComponent(filterState.priceMax)}`);
            
            if (params.length > 0) {
                url += '?' + params.join('&');
            }
            
            // Update URL without refreshing page
            window.history.pushState({ path: url }, '', url);
            
            // Set the AJAX header
            const headers = new Headers();
            headers.append('X-Requested-With', 'XMLHttpRequest');
            
            // Use fetch to get updated content
            fetch(url, { headers })
                .then(response => response.json())
                .then(data => {
                    // Update the activities grid
                    const activitiesGrid = document.getElementById('activities-grid');
                    if (activitiesGrid && data.html) {
                        activitiesGrid.innerHTML = data.html;
                        
                        // Add animation to new cards
                        const cards = activitiesGrid.querySelectorAll('.card');
                        cards.forEach((card, index) => {
                            card.style.animationDelay = `${0.1 + (index % 3) * 0.1}s`;
                        });
                        
                        // Initialize event listeners for new elements
                        initializeCardListeners();
                    }
                    
                    // Hide loading after a minimum display time
                    setTimeout(() => {
                        loadingOverlay.classList.remove('active');
                    }, 500);
                })
                .catch(error => {
                    console.error('Error:', error);
                    loadingOverlay.classList.remove('active');
                    showNotification('Une erreur est survenue lors du filtrage des activités.', 'error');
                });
        }
        
        // Initialize card event listeners
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
                            window.location.href = 'activites.php?price=' + encodeURIComponent(tagName);
                        } else if (tagName === 'interieur' || tagName === 'exterieur') {
                            window.location.href = 'activites.php?location=' + encodeURIComponent(tagName);
                        } else {
                            window.location.href = 'activites.php?category=' + encodeURIComponent(tagName);
                        }
                    }
                });
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
                });
            });
        }
        
        // Initialize all listeners
        initializeCardListeners();
        
        // Initialize selected categories display on page load
        if (filterState.category) {
            updateSelectedCategoriesDisplay(filterState.category.split(','));
        }
        
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
        
        // Update cart count
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
            const cartCount = document.getElementById('panier-count');
            if (cartCount) {
                cartCount.textContent = cart.length;
            }
        }
        
        // Show notification
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
        
        // Create animated particles
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