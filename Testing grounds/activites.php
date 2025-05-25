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

// Initialize search parameters
$where_clauses = [];
$params = [];
$types = "";

// Handle single tag parameter (from main page clicks)
if (isset($_GET['tag']) && !empty($_GET['tag'])) {
    $singleTag = $_GET['tag'];
    $where_clauses[] = "EXISTS (
        SELECT 1 FROM activity_tags at2 
        JOIN tag_definitions td2 ON at2.tag_definition_id = td2.id 
        WHERE at2.activity_id = a.id AND td2.name = ?
    )";
    $params[] = $singleTag;
    $types .= "s";
}

// Handle multiple tag filters
if (isset($_GET['tags']) && is_array($_GET['tags'])) {
    $tagPlaceholders = str_repeat('?,', count($_GET['tags']));
    $tagPlaceholders = rtrim($tagPlaceholders, ',');
    
    $where_clauses[] = "EXISTS (
        SELECT 1 FROM activity_tags at2 
        JOIN tag_definitions td2 ON at2.tag_definition_id = td2.id 
        WHERE at2.activity_id = a.id AND td2.name IN ($tagPlaceholders)
    )";
    
    foreach ($_GET['tags'] as $tag) {
        $params[] = $tag;
        $types .= "s";
    }
}

// Handle search query
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchTerm = '%' . $_GET['search'] . '%';
    $where_clauses[] = "(a.titre LIKE ? OR a.description LIKE ?)";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Handle price range filter
if (!empty($_GET['min_price'])) {
    $where_clauses[] = "a.prix >= ?";
    $params[] = floatval($_GET['min_price']);
    $types .= "d";
}

if (!empty($_GET['max_price'])) {
    $where_clauses[] = "a.prix <= ?";
    $params[] = floatval($_GET['max_price']);
    $types .= "d";
}

// Handle price type filter (gratuit/payant)
if (isset($_GET['price_type']) && !empty($_GET['price_type'])) {
    if ($_GET['price_type'] === 'gratuit') {
        $where_clauses[] = "a.prix = 0";
    } elseif ($_GET['price_type'] === 'payant') {
        $where_clauses[] = "a.prix > 0";
    }
}

// Add output buffering to prevent header issues
ob_start();

// Build the main query
$sql = "SELECT DISTINCT a.*, 
        GROUP_CONCAT(DISTINCT td.name) AS tags,
        GROUP_CONCAT(DISTINCT td.display_name SEPARATOR '|') AS tag_display_names,
        DATEDIFF(STR_TO_DATE(SUBSTRING_INDEX(date_ou_periode, ' - ', -1), '%d/%m/%Y'), NOW()) as days_remaining
        FROM activites a 
        LEFT JOIN activity_tags at ON a.id = at.activity_id
        LEFT JOIN tag_definitions td ON at.tag_definition_id = td.id";

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " GROUP BY a.id ORDER BY a.date_creation DESC";

// Prepare and execute the query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

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
    global $tagDefinitions;
    return isset($tagDefinitions[$tag]) ? $tagDefinitions[$tag]['class'] : 'primary';
}

function getTagDisplayName($tag, $tagDisplayNames = null, $index = null) {
    global $tagDefinitions;
    
    if ($tagDisplayNames && $index !== null && isset($tagDisplayNames[$index])) {
        return $tagDisplayNames[$index];
    }
    
    return isset($tagDefinitions[$tag]) ? $tagDefinitions[$tag]['display_name'] : ucfirst(str_replace('_', ' ', $tag));
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

// Check if this is an AJAX request
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    
    ob_clean();
    header('Content-Type: application/json');
    
    $activitiesHtml = '';
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $randomRating = (($row['id'] * 7) % 21 + 30) / 10;
            $isEnding = isEndingSoon($row);
            $daysRemaining = $isEnding ? getDaysRemaining($row) : null;
            $tagList = $row["tags"] ? explode(',', $row["tags"]) : [];
            $tagDisplayNames = $row["tag_display_names"] ? explode('|', $row["tag_display_names"]) : [];
            $isPaid = $row["prix"] > 0;
            
            $activitiesHtml .= '<div class="activity-card" 
                data-id="' . $row['id'] . '"
                data-price="' . $row['prix'] . '"
                data-rating="' . $randomRating . '"
                data-date-creation="' . $row['date_creation'] . '">';
            
            $activitiesHtml .= '<div class="card-image">';
            $activitiesHtml .= '<img src="' . htmlspecialchars($row["image_url"] ?: 'nature-placeholder.jpg') . '" alt="' . htmlspecialchars($row["titre"]) . '" />';
            
            if ($isPaid) {
                $activitiesHtml .= '<div class="price-badge">' . number_format($row["prix"], 2) . ' €</div>';
            } else {
                $activitiesHtml .= '<div class="price-badge free">Gratuit</div>';
            }
            
            if ($isEnding) {
                $activitiesHtml .= '<div class="urgency-badge">';
                if ($daysRemaining == 0) {
                    $activitiesHtml .= 'Dernier jour !';
                } else if ($daysRemaining == 1) {
                    $activitiesHtml .= 'Termine demain !';
                } else {
                    $activitiesHtml .= 'Plus que ' . $daysRemaining . ' jours !';
                }
                $activitiesHtml .= '</div>';
            }
            $activitiesHtml .= '</div>';
            
            $activitiesHtml .= '<div class="card-content">';
            $activitiesHtml .= '<h3>' . htmlspecialchars($row["titre"]) . '</h3>';
            
            if ($row["date_ou_periode"]) {
                $activitiesHtml .= '<p class="activity-period">' . htmlspecialchars($row["date_ou_periode"]) . '</p>';
            }
            
            $activitiesHtml .= '<div class="activity-tags">';
            $displayedTags = 0;
            foreach ($tagList as $index => $tag) {
                if ($displayedTags < 3 && $tag !== 'gratuit' && $tag !== 'payant') {
                    $displayName = getTagDisplayName($tag, $tagDisplayNames, $index);
                    $activitiesHtml .= '<span class="activity-tag">' . htmlspecialchars($displayName) . '</span>';
                    $displayedTags++;
                }
            }
            $activitiesHtml .= '</div>';
            
            $activitiesHtml .= '<div class="card-footer">';
            $activitiesHtml .= '<div class="activity-rating">' . getStars($randomRating) . '</div>';
            $activitiesHtml .= '<button class="add-to-cart-btn" data-id="' . $row['id'] . '" 
                    data-title="' . htmlspecialchars($row['titre']) . '" 
                    data-price="' . $row['prix'] . '" 
                    data-image="' . htmlspecialchars($row['image_url'] ?: 'nature-placeholder.jpg') . '" 
                    data-period="' . htmlspecialchars($row['date_ou_periode']) . '" 
                    data-tags="' . htmlspecialchars($row['tags']) . '">
                    Ajouter
                </button>';
            $activitiesHtml .= '</div>';
            $activitiesHtml .= '</div>';
            $activitiesHtml .= '</div>';
        }
    } else {
        $activitiesHtml .= '<div class="no-results">
            <i class="fa-solid fa-search"></i>
            <h3>Aucune activité trouvée</h3>
            <p>Essayez d\'ajuster vos filtres pour trouver des activités qui vous conviennent.</p>
        </div>';
    }
    
    echo json_encode(['html' => $activitiesHtml, 'count' => $result->num_rows]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Recherche d'activités | Synapse</title>
    <link rel="stylesheet" href="main.css" />
    <link rel="stylesheet" href="../TEMPLATE/teteaupied.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Nature-inspired variables */
        :root {
            --primary-color: #3c8c5c;
            --primary-light: #61b980;
            --primary-dark: #275e3e;
            --secondary-color: #946b2d;
            --secondary-light: #c89e52;
            --accent-color: #e9c46a;
            --bg-gradient: linear-gradient(135deg, #f8fff9 0%, #f0f7f2 100%);
        }

        /* Enhanced body background */
        body {
            background: var(--bg-gradient);
            position: relative;
            overflow-x: hidden;
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(69, 161, 99, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(233, 196, 106, 0.1) 0%, transparent 50%);
        }

        /* Enhanced leaf animation styles */
        .leaf-animation-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        }

        .floating-leaf {
            position: absolute;
            width: 80px;
            height: 80px;
            background-size: contain;
            background-repeat: no-repeat;
            opacity: 0.9;
            pointer-events: none;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));
            z-index: 1;
        }

        .leaf-1 {
            top: 10%;
            left: 5%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%2344b174" d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z"/></svg>');
            animation: floatLeaf1 20s infinite linear;
            transform-origin: center;
        }

        .leaf-2 {
            top: 70%;
            right: 10%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%233d8c5e" d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z"/></svg>');
            animation: floatLeaf2 25s infinite linear;
            transform-origin: center;
        }

        .leaf-3 {
            bottom: 20%;
            left: 15%;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%23e9a23c" d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z"/></svg>');
            animation: floatLeaf3 22s infinite linear;
            transform-origin: center;
        }

        .leaf-4 {
            top: 40%;
            right: 25%;
            width: 70px;
            height: 70px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%2361b980" d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z"/></svg>');
            animation: floatLeaf4 28s infinite linear;
            transform-origin: center;
        }

        .leaf-5 {
            top: 85%;
            left: 35%;
            width: 65px;
            height: 65px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%23d4943e" d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z"/></svg>');
            animation: floatLeaf5 24s infinite linear;
            transform-origin: center;
        }

        @keyframes floatLeaf1 {
            0% { transform: translateY(-100%) translateX(-100%) rotate(0deg) scale(1); opacity: 0; }
            10% { opacity: 0.9; }
            50% { transform: translateY(50vh) translateX(50vw) rotate(360deg) scale(1.2); }
            90% { opacity: 0.9; }
            100% { transform: translateY(100vh) translateX(100vw) rotate(720deg) scale(1); opacity: 0; }
        }

        @keyframes floatLeaf2 {
            0% { transform: translateY(-100%) translateX(100%) rotate(180deg) scale(1); opacity: 0; }
            10% { opacity: 0.9; }
            50% { transform: translateY(50vh) translateX(-50vw) rotate(-180deg) scale(1.1); }
            90% { opacity: 0.9; }
            100% { transform: translateY(100vh) translateX(-100vw) rotate(-540deg) scale(1); opacity: 0; }
        }

        @keyframes floatLeaf3 {
            0% { transform: translateY(100%) translateX(-50%) rotate(-90deg) scale(1); opacity: 0; }
            10% { opacity: 0.9; }
            50% { transform: translateY(-50vh) translateX(25vw) rotate(180deg) scale(1.2); }
            90% { opacity: 0.9; }
            100% { transform: translateY(-100vh) translateX(50vw) rotate(450deg) scale(1); opacity: 0; }
        }

        @keyframes floatLeaf4 {
            0% { transform: translateY(-50vh) translateX(50vw) rotate(45deg) scale(1); opacity: 0; }
            10% { opacity: 0.9; }
            50% { transform: translateY(75vh) translateX(-25vw) rotate(360deg) scale(1.15); }
            90% { opacity: 0.9; }
            100% { transform: translateY(150vh) translateX(-50vw) rotate(675deg) scale(1); opacity: 0; }
        }

        @keyframes floatLeaf5 {
            0% { transform: translateY(0) translateX(100vw) rotate(-145deg) scale(1); opacity: 0; }
            10% { opacity: 0.9; }
            50% { transform: translateY(-50vh) translateX(-50vw) rotate(45deg) scale(1.1); }
            90% { opacity: 0.9; }
            100% { transform: translateY(-100vh) translateX(-100vw) rotate(215deg) scale(1); opacity: 0; }
        }

        /* Add decorative vines */
        .vine-decoration {
            position: fixed;
            pointer-events: none;
            z-index: 1;
        }

        .vine-top-left {
            top: 0;
            left: 0;
            width: 200px;
            height: 300px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%233c8c5c" d="M6.5,6C7.47,6 8.37,6.5 9.11,7.38C9.66,6.93 10.35,6.5 11,6.5C11.65,6.5 12.34,6.93 12.89,7.38C13.63,6.5 14.53,6 15.5,6C18,6 20,9.36 20,13.5C20,17.64 18,21 15.5,21C14.53,21 13.63,20.5 12.89,19.62C12.34,20.07 11.65,20.5 11,20.5C10.35,20.5 9.66,20.07 9.11,19.62C8.37,20.5 7.47,21 6.5,21C4,21 2,17.64 2,13.5C2,9.36 4,6 6.5,6Z"/></svg>');
            opacity: 0.1;
            transform: rotate(-45deg) scale(2);
        }

        .vine-bottom-right {
            bottom: 0;
            right: 0;
            width: 250px;
            height: 350px;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%233c8c5c" d="M6.5,6C7.47,6 8.37,6.5 9.11,7.38C9.66,6.93 10.35,6.5 11,6.5C11.65,6.5 12.34,6.93 12.89,7.38C13.63,6.5 14.53,6 15.5,6C18,6 20,9.36 20,13.5C20,17.64 18,21 15.5,21C14.53,21 13.63,20.5 12.89,19.62C12.34,20.07 11.65,20.5 11,20.5C10.35,20.5 9.66,20.07 9.11,19.62C8.37,20.5 7.47,21 6.5,21C4,21 2,17.64 2,13.5C2,9.36 4,6 6.5,6Z"/></svg>');
            opacity: 0.1;
            transform: rotate(135deg) scale(2.5);
        }

        /* Enhanced container styling */
        .search-container {
            position: relative;
            z-index: 1;
            backdrop-filter: blur(10px);
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* Enhanced filters section */
        .filters-section {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
        }

        .filters-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .search-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .search-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d5a3d;
            margin-bottom: 0.5rem;
        }

        .search-header p {
            font-size: 1.1rem;
            color: #6b7280;
            max-width: 600px;
            margin: 0 auto;
        }

        .search-bar {
            position: relative;
            margin-bottom: 2rem;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .search-input:focus {
            outline: none;
            border-color: #45a163;
            background: white;
            box-shadow: 0 0 0 3px rgba(69, 161, 99, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.1rem;
        }

        .filter-group {
            margin-bottom: 1.5rem;
        }

        .filter-label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.75rem;
            font-size: 0.95rem;
        }

        .filter-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .filter-pill {
            padding: 0.5rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.8);
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            backdrop-filter: blur(5px);
        }

        .filter-pill:hover {
            border-color: #45a163;
            color: #45a163;
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 8px 20px rgba(69, 161, 99, 0.15);
        }

        .filter-pill.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border-color: transparent;
            color: white;
        }

        .price-range {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 0.75rem;
        }

        .price-input {
            flex: 1;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.3s ease;
        }

        .price-input:focus {
            outline: none;
            border-color: #45a163;
        }

        .price-separator {
            color: #6b7280;
            font-weight: 500;
        }

        .filter-button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .filter-button:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .results-count {
            color: #6b7280;
            font-size: 0.95rem;
        }

        .sort-dropdown {
            padding: 0.5rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            color: #374151;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .activities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        /* Enhanced activity cards */
        .activity-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            transition: all 0.4s ease;
            border-radius: 16px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            cursor: pointer;
        }

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
        }

        .price-badge.free {
            background: rgba(69, 161, 99, 0.9);
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

        .urgency-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: rgba(239, 68, 68, 0.9);
            color: white;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            backdrop-filter: blur(8px);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }

        .card-content {
            padding: 1.5rem;
        }

        .card-content h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.5rem;
            line-height: 1.3;
        }

        .activity-period {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activity-period::before {
            content: '📅';
            font-size: 0.8rem;
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

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid #f3f4f6;
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

        .add-to-cart-btn {
            background: #45a163;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .add-to-cart-btn:hover {
            background: #369953;
            transform: translateY(-1px);
        }

        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            grid-column: 1 / -1;
        }

        .no-results i {
            font-size: 4rem;
            color: #d1d5db;
            margin-bottom: 1rem;
        }

        .no-results h3 {
            font-size: 1.5rem;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .no-results p {
            color: #6b7280;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
        }

        .loading-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid #e5e7eb;
            border-top: 4px solid #45a163;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .notification {
            position: fixed;
            top: 2rem;
            right: 2rem;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 1000;
            transform: translateX(100%);
            transition: transform 0.3s ease;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            background: #10b981;
        }

        .notification.error {
            background: #ef4444;
        }

        .notification.info {
            background: #3b82f6;
        }

        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .active-filter {
            background: #45a163;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .remove-filter {
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s ease;
        }

        .remove-filter:hover {
            opacity: 1;
        }

        .clear-filters-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .clear-filters-btn:hover {
            background: #dc2626;
        }

        @media (max-width: 1200px) {
            .activities-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 1rem;
            }
        }

        @media (max-width: 768px) {
            .search-container {
                padding: 1rem;
            }

            .search-header h1 {
                font-size: 1.8rem;
            }

            .search-header p {
                font-size: 1rem;
                padding: 0 1rem;
            }

            .filters-section {
                padding: 1rem;
            }

            .price-range {
                flex-wrap: wrap;
                gap: 0.5rem;
            }

            .price-input {
                flex: 1 1 calc(50% - 1rem);
                min-width: 100px;
            }

            .filter-button {
                width: 100%;
                margin-top: 0.5rem;
            }

            .activities-grid {
                grid-template-columns: 1fr;
                max-width: 500px;
                margin: 0 auto;
            }
        }

        @media (max-width: 480px) {
            .card-footer {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .add-to-cart-btn {
                width: 100%;
            }

            .filter-pills {
                gap: 0.25rem;
            }

            .filter-pill {
                padding: 0.35rem 0.6rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ob_end_flush(); ?>

    <div class="leaf-animation-container">
        <div class="floating-leaf leaf-1"></div>
        <div class="floating-leaf leaf-2"></div>
        <div class="floating-leaf leaf-3"></div>
        <div class="floating-leaf leaf-4"></div>
        <div class="floating-leaf leaf-5"></div>
    </div>

    <div class="vine-decoration vine-top-left"></div>
    <div class="vine-decoration vine-bottom-right"></div>

    <div class="search-container">
        <div class="search-header">
            <h1>Recherche d'activités</h1>
            <p>Trouvez l'activité parfaite parmi notre sélection d'expériences uniques</p>
        </div>

        <div class="filters-section">
            <div class="search-bar">
                <i class="fa-solid fa-search search-icon"></i>
                <input type="text" class="search-input" id="search-input" 
                       placeholder="Rechercher une activité..." 
                       value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
            </div>

            <div class="filter-group">
                <label class="filter-label">Catégories</label>
                <div class="filter-pills" id="category-pills">
                    <?php foreach ($tagDefinitions as $tagName => $tagInfo): ?>
                        <?php if (!in_array($tagName, ['gratuit', 'payant'])): ?>
                            <div class="filter-pill category-pill 
                                <?php echo (isset($_GET['tag']) && $_GET['tag'] === $tagName) || 
                                          (isset($_GET['tags']) && in_array($tagName, $_GET['tags'])) ? 'active' : ''; ?>"
                                 data-tag="<?php echo htmlspecialchars($tagName); ?>">
                                <i class="fa-solid fa-tag"></i>
                                <?php echo htmlspecialchars($tagInfo['display_name']); ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="filter-group">
                <label class="filter-label">Type de prix</label>
                <div class="filter-pills">
                    <div class="filter-pill price-type-pill <?php echo (isset($_GET['price_type']) && $_GET['price_type'] === 'gratuit') ? 'active' : ''; ?>" 
                         data-type="gratuit">
                        <i class="fa-solid fa-gift"></i>
                        Gratuit
                    </div>
                    <div class="filter-pill price-type-pill <?php echo (isset($_GET['price_type']) && $_GET['price_type'] === 'payant') ? 'active' : ''; ?>" 
                         data-type="payant">
                        <i class="fa-solid fa-euro-sign"></i>
                        Payant
                    </div>
                    <div class="filter-pill price-type-pill <?php echo (!isset($_GET['price_type']) || empty($_GET['price_type'])) ? 'active' : ''; ?>" 
                         data-type="">
                        <i class="fa-solid fa-list"></i>
                        Tous
                    </div>
                </div>
                <div class="price-range">
                    <input type="number" class="price-input" id="min-price" placeholder="Prix min" 
                           value="<?php echo htmlspecialchars($_GET['min_price'] ?? ''); ?>">
                    <span class="price-separator">à</span>
                    <input type="number" class="price-input" id="max-price" placeholder="Prix max" 
                           value="<?php echo htmlspecialchars($_GET['max_price'] ?? ''); ?>">
                    <button type="button" id="apply-price" class="filter-button">Appliquer</button>
                </div>
            </div>

            <div class="active-filters" id="active-filters"></div>
        </div>

        <div class="results-header">
            <div class="results-count" id="results-count">
                <?php echo $result->num_rows; ?> activité(s) trouvée(s)
            </div>
            <select class="sort-dropdown" id="sort-dropdown">
                <option value="date">Plus récentes</option>
                <option value="price_asc">Prix croissant</option>
                <option value="price_desc">Prix décroissant</option>
                <option value="rating">Mieux notées</option>
            </select>
        </div>

        <div class="activities-grid" id="activities-grid">
            <?php 
            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $randomRating = (($row['id'] * 7) % 21 + 30) / 10;
                    $isEnding = isEndingSoon($row);
                    $daysRemaining = $isEnding ? getDaysRemaining($row) : null;
                    $tagList = $row["tags"] ? explode(',', $row["tags"]) : [];
                    $tagDisplayNames = $row["tag_display_names"] ? explode('|', $row["tag_display_names"]) : [];
                    $isPaid = $row["prix"] > 0;
                    
                    echo '<div class="activity-card" 
                        data-id="' . $row['id'] . '"
                        data-price="' . $row['prix'] . '"
                        data-rating="' . $randomRating . '"
                        data-date-creation="' . $row['date_creation'] . '">';
                    
                    echo '<div class="card-image">';
                    echo '<img src="' . htmlspecialchars($row["image_url"] ?: 'nature-placeholder.jpg') . '" alt="' . htmlspecialchars($row["titre"]) . '" />';
                    
                    if ($isPaid) {
                        echo '<div class="price-badge">' . number_format($row["prix"], 2) . ' €</div>';
                    } else {
                        echo '<div class="price-badge free">Gratuit</div>';
                    }
                    
                    if ($isEnding) {
                        echo '<div class="urgency-badge">';
                        if ($daysRemaining == 0) {
                            echo 'Dernier jour !';
                        } else if ($daysRemaining == 1) {
                            echo 'Termine demain !';
                        } else {
                            echo 'Plus que ' . $daysRemaining . ' jours !';
                        }
                        echo '</div>';
                    }
                    echo '</div>';
                    
                    echo '<div class="card-content">';
                    echo '<h3>' . htmlspecialchars($row["titre"]) . '</h3>';
                    
                    if ($row["date_ou_periode"]) {
                        echo '<p class="activity-period">' . htmlspecialchars($row["date_ou_periode"]) . '</p>';
                    }
                    
                    echo '<div class="activity-tags">';
                    $displayedTags = 0;
                    foreach ($tagList as $index => $tag) {
                        if ($displayedTags < 3 && $tag !== 'gratuit' && $tag !== 'payant') {
                            $displayName = getTagDisplayName($tag, $tagDisplayNames, $index);
                            echo '<span class="activity-tag">' . htmlspecialchars($displayName) . '</span>';
                            $displayedTags++;
                        }
                    }
                    echo '</div>';
                    
                    echo '<div class="card-footer">';
                    echo '<div class="activity-rating">' . getStars($randomRating) . '</div>';
                    echo '<button class="add-to-cart-btn" data-id="' . $row['id'] . '" 
                        data-title="' . htmlspecialchars($row['titre']) . '" 
                        data-price="' . $row['prix'] . '" 
                        data-image="' . htmlspecialchars($row['image_url'] ?: 'nature-placeholder.jpg') . '" 
                        data-period="' . htmlspecialchars($row['date_ou_periode']) . '" 
                        data-tags="' . htmlspecialchars($row['tags']) . '">
                        Ajouter
                        </button>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="no-results">
                    <i class="fa-solid fa-search"></i>
                    <h3>Aucune activité trouvée</h3>
                    <p>Essayez d\'ajuster vos filtres pour trouver des activités qui vous conviennent.</p>
                </div>';
            }
            ?>
        </div>
    </div>

    <div class="loading-overlay" id="loading-overlay">
        <div class="loading-spinner"></div>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize cart
        if (!localStorage.getItem('synapse-cart')) {
            localStorage.setItem('synapse-cart', JSON.stringify([]));
        }
        updateCartCount();

        // Filter state
        let filterState = {
            search: document.getElementById('search-input').value || '',
            categories: getActiveCategoriesFromURL(),
            priceType: getUrlParam('price_type') || '',
            minPrice: document.getElementById('min-price').value || '',
            maxPrice: document.getElementById('max-price').value || ''
        };

        // Update active filters display
        updateActiveFiltersDisplay();

        // Search input with debounce
        const searchInput = document.getElementById('search-input');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                filterState.search = this.value.trim();
                applyFilters();
            }, 300);
        });

        // Category pills
        document.querySelectorAll('.category-pill').forEach(pill => {
            pill.addEventListener('click', function() {
                const tag = this.getAttribute('data-tag');
                
                if (this.classList.contains('active')) {
                    // Remove from active categories
                    filterState.categories = filterState.categories.filter(cat => cat !== tag);
                    this.classList.remove('active');
                } else {
                    // Add to active categories
                    filterState.categories.push(tag);
                    this.classList.add('active');
                }
                
                applyFilters();
            });
        });

        // Price type pills
        document.querySelectorAll('.price-type-pill').forEach(pill => {
            pill.addEventListener('click', function() {
                document.querySelectorAll('.price-type-pill').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                
                filterState.priceType = this.getAttribute('data-type');
                applyFilters();
            });
        });

        // Price range
        document.getElementById('apply-price').addEventListener('click', function() {
            const minPrice = document.getElementById('min-price').value;
            const maxPrice = document.getElementById('max-price').value;
            
            // Validate price inputs
            if ((minPrice && isNaN(minPrice)) || (maxPrice && isNaN(maxPrice))) {
                showNotification('Veuillez entrer des prix valides', 'error');
                return;
            }
            
            if (minPrice && maxPrice && parseFloat(minPrice) > parseFloat(maxPrice)) {
                showNotification('Le prix minimum ne peut pas être supérieur au prix maximum', 'error');
                return;
            }
            
            filterState.minPrice = minPrice;
            filterState.maxPrice = maxPrice;
            applyFilters();
        });

        // Activity cards click
        document.addEventListener('click', function(e) {
            if (e.target.closest('.activity-card') && !e.target.closest('.add-to-cart-btn')) {
                const card = e.target.closest('.activity-card');
                const activityId = card.getAttribute('data-id');
                if (activityId) {
                    window.location.href = 'activite.php?id=' + activityId;
                }
            }
        });

        // Add to cart buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.add-to-cart-btn')) {
                e.stopPropagation();
                const button = e.target.closest('.add-to-cart-btn');
                
                const item = {
                    id: button.getAttribute('data-id'),
                    titre: button.getAttribute('data-title'),
                    prix: parseFloat(button.getAttribute('data-price')),
                    image: button.getAttribute('data-image'),
                    periode: button.getAttribute('data-period'),
                    tags: button.getAttribute('data-tags') ? button.getAttribute('data-tags').split(',') : []
                };
                
                addToCart(item);
            }
        });

        function getActiveCategoriesFromURL() {
            const singleTag = getUrlParam('tag');
            const multipleTags = getUrlParam('tags');
            
            if (singleTag) {
                return [singleTag];
            } else if (multipleTags) {
                return multipleTags.split(',');
            }
            return [];
        }

        function getUrlParam(param) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(param);
        }

        // Replace the existing filter handlers with this improved version
        function applyFilters() {
            const loadingOverlay = document.getElementById('loading-overlay');
            loadingOverlay.classList.add('active');

            // Build URL parameters
            const params = new URLSearchParams();

            // Only add non-empty filters
            if (filterState.search.trim()) {
                params.append('search', filterState.search.trim());
            }
            
            if (filterState.categories.length > 0) {
                filterState.categories.forEach(cat => params.append('tags[]', cat));
            }
            
            if (filterState.priceType) {
                params.append('price_type', filterState.priceType);
            }
            
            // Only add price range if both values are valid numbers
            const minPrice = parseFloat(filterState.minPrice);
            const maxPrice = parseFloat(filterState.maxPrice);
            
            if (!isNaN(minPrice) && minPrice >= 0) {
                params.append('min_price', minPrice);
            }
            
            if (!isNaN(maxPrice) && maxPrice >= 0) {
                params.append('max_price', maxPrice);
            }

            // Build URL
            let url = 'activites.php';
            if (params.toString()) {
                url += '?' + params.toString();
            }

            // Update URL without page reload
            window.history.pushState({ path: url }, '', url);

            // AJAX request with error handling
            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                document.getElementById('activities-grid').innerHTML = data.html;
                document.getElementById('results-count').textContent = data.count + ' activité(s) trouvée(s)';
                updateActiveFiltersDisplay();
                
                setTimeout(() => {
                    loadingOverlay.classList.remove('active');
                }, 300);
            })
            .catch(error => {
                console.error('Error:', error);
                loadingOverlay.classList.remove('active');
                showNotification('Erreur lors du filtrage', 'error');
            });
        }

        // Improve filter removal functionality
        function removeFilter(type, value) {
            switch (type) {
                case 'search':
                    filterState.search = '';
                    document.getElementById('search-input').value = '';
                    break;
                    
                case 'category':
                    filterState.categories = filterState.categories.filter(cat => cat !== value);
                    const categoryPill = document.querySelector(`.category-pill[data-tag="${value}"]`);
                    if (categoryPill) {
                        categoryPill.classList.remove('active');
                    }
                    break;
                    
                case 'price_type':
                    filterState.priceType = '';
                    document.querySelectorAll('.price-type-pill').forEach(p => p.classList.remove('active'));
                    document.querySelector('.price-type-pill[data-type=""]').classList.add('active');
                    break;
                    
                case 'price_range':
                    filterState.minPrice = '';
                    filterState.maxPrice = '';
                    document.getElementById('min-price').value = '';
                    document.getElementById('max-price').value = '';
                    break;
            }
            applyFilters();
        }

        function updateActiveFiltersDisplay() {
            const container = document.getElementById('active-filters');
            container.innerHTML = '';

            const filters = [];

            if (filterState.search) {
                filters.push({ type: 'search', text: `"${filterState.search}"`, icon: 'fa-search' });
            }

            if (filterState.categories.length > 0) {
                filterState.categories.forEach(cat => {
                    const displayName = getTagDisplayName(cat);
                    filters.push({ type: 'category', text: displayName, data: cat, icon: 'fa-tag' });
                });
            }

            if (filterState.priceType) {
                const priceText = filterState.priceType === 'gratuit' ? 'Gratuit' : 'Payant';
                filters.push({ type: 'price_type', text: priceText, icon: 'fa-euro-sign' });
            }

            if (filterState.minPrice || filterState.maxPrice) {
                const min = filterState.minPrice || '0';
                const max = filterState.maxPrice || '∞';
                filters.push({ type: 'price_range', text: `${min}€ - ${max}€`, icon: 'fa-euro-sign' });
            }

            filters.forEach(filter => {
                const filterElement = document.createElement('div');
                filterElement.className = 'active-filter';
                filterElement.innerHTML = `
                    <i class="fa-solid ${filter.icon}"></i>
                    ${filter.text}
                    <i class="fa-solid fa-times remove-filter" data-type="${filter.type}" data-value="${filter.data || ''}"></i>
                `;
                container.appendChild(filterElement);
            });

            if (filters.length > 0) {
                const clearButton = document.createElement('button');
                clearButton.className = 'clear-filters-btn';
                clearButton.innerHTML = '<i class="fa-solid fa-times"></i> Tout effacer';
                clearButton.onclick = clearAllFilters;
                container.appendChild(clearButton);
            }

            // Add event listeners for remove buttons
            container.querySelectorAll('.remove-filter').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const type = this.getAttribute('data-type');
                    const value = this.getAttribute('data-value');
                    removeFilter(type, value);
                });
            });
        }

        function clearAllFilters() {
            filterState = {
                search: '',
                categories: [],
                priceType: '',
                minPrice: '',
                maxPrice: ''
            };

            document.getElementById('search-input').value = '';
            document.getElementById('min-price').value = '';
            document.getElementById('max-price').value = '';
            
            document.querySelectorAll('.filter-pill').forEach(pill => pill.classList.remove('active'));
            document.querySelector('.price-type-pill[data-type=""]').classList.add('active');

            applyFilters();
        }

        function getTagDisplayName(tag) {
            const tagDefinitions = <?php echo json_encode($tagDefinitions); ?>;
            return tagDefinitions[tag] ? tagDefinitions[tag].display_name : tag;
        }

        function addToCart(item) {
            const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
            const existingItemIndex = cart.findIndex(cartItem => cartItem.id === item.id);
            
            if (existingItemIndex === -1) {
                cart.push(item);
                localStorage.setItem('synapse-cart', JSON.stringify(cart));
                updateCartCount();
                showNotification('Activité ajoutée au panier !', 'success');
            } else {
                showNotification('Cette activité est déjà dans votre panier.', 'info');
            }
        }

        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
            const cartCount = document.getElementById('panier-count');
            if (cartCount) {
                cartCount.textContent = cart.length;
            }
        }

        function showNotification(message, type = 'success') {
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(n => n.remove());

            const notification = document.createElement('div');
            notification.classList.add('notification', type);
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => notification.classList.add('show'), 100);
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Add sort dropdown handler
        document.getElementById('sort-dropdown').addEventListener('change', function() {
            const activities = Array.from(document.querySelectorAll('.activity-card'));
            const container = document.getElementById('activities-grid');
            
            activities.sort((a, b) => {
                switch(this.value) {
                    case 'date':
                        // Sort by date_creation (newest first)
                        const dateA = new Date(a.dataset.dateCreation || 0);
                        const dateB = new Date(b.dataset.dateCreation || 0);
                        return dateB - dateA;
                    
                    case 'price_asc':
                        // Sort by price (lowest first) 
                        return parseFloat(a.dataset.price || 0) - parseFloat(b.dataset.price || 0);
                        
                    case 'price_desc':
                        // Sort by price (highest first)
                        return parseFloat(b.dataset.price || 0) - parseFloat(a.dataset.price || 0);
                        
                    case 'rating':
                        // Sort by rating (highest first)
                        return parseFloat(b.dataset.rating || 0) - parseFloat(a.dataset.rating || 0);
                }
            });
            
            // Clear and re-append sorted activities
            container.innerHTML = '';
            activities.forEach(activity => container.appendChild(activity));
        });
    });
    </script>
</body>
</html>
<!-- cvq -->