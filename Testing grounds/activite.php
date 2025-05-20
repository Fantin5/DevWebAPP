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

// Connect to user database
$user_conn = new mysqli($servername, $username, $password, "user_db");

// Vérifier la connexion
if ($conn->connect_error) {
    die("Échec de la connexion à la base de données: " . $conn->connect_error);
}

// Vérifier si un ID d'activité est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Rediriger vers la page principale si aucun ID valide n'est fourni
    header("Location: main.php");
    exit();
}

$activity_id = $_GET['id'];

// Récupérer les détails de l'activité
$sql = "SELECT a.*, 
        (SELECT GROUP_CONCAT(nom_tag) FROM tags WHERE activite_id = a.id) AS tags
        FROM activites a 
        WHERE a.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$result = $stmt->get_result();

// Vérifier si l'activité existe
if ($result->num_rows === 0) {
    // Rediriger vers la page principale si l'activité n'existe pas
    header("Location: main.php");
    exit();
}

$activity = $result->fetch_assoc();

// Extract creator information from description if it exists
$creator_data = null;
if (preg_match('/<!--CREATOR:([^-]+)-->/', $activity["description"], $matches)) {
    try {
        $encoded_data = $matches[1];
        $json_data = base64_decode($encoded_data);
        $creator_data = json_decode($json_data, true);
        
        // Remove the creator info from the description for display
        $activity["description"] = preg_replace('/<!--CREATOR:[^-]+-->/', '', $activity["description"]);
        
        // If we have user_id, try to get the latest information from database
        if (isset($creator_data['user_id'])) {
            $user_id = $creator_data['user_id'];
            $user_sql = "SELECT name, first_name, email, phone_nb FROM user_form WHERE id = ?";
            $user_stmt = $user_conn->prepare($user_sql);
            $user_stmt->bind_param("i", $user_id);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result->num_rows > 0) {
                $user_data = $user_result->fetch_assoc();
                $creator_data['name'] = $user_data['name'];
                $creator_data['first_name'] = $user_data['first_name'];
                $creator_data['email'] = $user_data['email'];
                $creator_data['phone_nb'] = $user_data['phone_nb'];
            }
        }
    } catch (Exception $e) {
        // If there's an error parsing, just continue without creator data
        $creator_data = null;
    }
}

// Fetch similar activities based on tags
$similar_activities = [];
if ($activity["tags"]) {
    $tags = explode(',', $activity["tags"]);
    $tagPlaceholders = implode(',', array_fill(0, count($tags), '?'));
    
    $similarSql = "SELECT a.*, 
                  (SELECT GROUP_CONCAT(nom_tag) FROM tags WHERE activite_id = a.id) AS tags
                  FROM activites a 
                  JOIN tags t ON a.id = t.activite_id
                  WHERE t.nom_tag IN ($tagPlaceholders) 
                  AND a.id != ?
                  GROUP BY a.id
                  ORDER BY COUNT(DISTINCT t.nom_tag) DESC
                  LIMIT 3";
    
    $similarStmt = $conn->prepare($similarSql);
    
    // Prepare parameters: all tags followed by the activity ID to exclude
    $params = $tags;
    $params[] = $activity_id;
    
    // Create types string (s for each tag, i for the activity ID)
    $types = str_repeat("s", count($tags)) . "i";
    
    $similarStmt->bind_param($types, ...$params);
    $similarStmt->execute();
    $similarResult = $similarStmt->get_result();
    
    while ($row = $similarResult->fetch_assoc()) {
        $similar_activities[] = $row;
    }
}

// Formater les tags
$tags = $activity["tags"] ? explode(',', $activity["tags"]) : [];

// Générer une note aléatoire pour la démonstration (mais stable pour une activité donnée)
$randomSeed = $activity_id * 13; // Use activity ID as seed for consistency
$randomRating = (($randomSeed % 21) + 30) / 10; // Between 3.0 and 5.0

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

// Formater le prix
$isPaid = $activity["prix"] > 0;
$priceText = $isPaid ? number_format($activity["prix"], 2) . " €" : "Gratuit";

// Formater le numéro de téléphone
function formatPhoneNumber($phone) {
    // Si le numéro commence par 0, on l'ajoute
    if (strlen($phone) == 9 && substr($phone, 0, 1) != '0') {
        $phone = '0' . $phone;
    }
    
    // Format XX XX XX XX XX
    if (strlen($phone) == 10) {
        return chunk_split($phone, 2, ' ');
    }
    
    // Si le format ne correspond pas, renvoyer tel quel
    return $phone;
}

// Generate random reviews for demo purposes
function generateRandomReviews($activity_id) {
    $reviewsCount = rand(3, 6);
    $reviews = [];
    
    $names = [
        'Sophie Martin', 'Thomas Bernard', 'Emma Dubois', 'Lucas Petit', 
        'Camille Moreau', 'Jules Leroy', 'Léa Richard', 'Hugo Simon', 
        'Chloé Laurent', 'Antoine Girard', 'Manon Robert', 'Maxime Michel'
    ];
    
    $comments = [
        'Superbe activité, je recommande vivement !',
        'J\'ai passé un excellent moment, à refaire.',
        'Très belle découverte, merci pour cette expérience.',
        'Le cadre est magnifique et l\'activité bien organisée.',
        'Bon rapport qualité-prix, activité enrichissante.',
        'Parfait pour se détendre et profiter de la nature.',
        'Une expérience unique et mémorable.',
        'Très bien organisé, rien à redire.',
        'Idéal pour une sortie en famille ou entre amis.',
        'Animation de qualité, je n\'ai pas vu le temps passer.',
        'Je n\'étais pas sûr(e) au début, mais j\'ai finalement adoré.',
        'Le guide était passionné et a su nous captiver.',
        'Enrichissant et divertissant à la fois.'
    ];
    
    $commentVariations = [
        'Un peu cher pour ce que c\'est, mais agréable tout de même.',
        'Quelques petits points à améliorer mais globalement satisfaisant.',
        'Le temps était moyen mais l\'activité reste intéressante.',
        'Pourrait être plus accessible, mais l\'expérience vaut le détour.',
        'La préparation avant l\'activité pourrait être plus claire.',
        'Un peu court à mon goût, j\'en aurais voulu plus !'
    ];
    
    for ($i = 0; $i < $reviewsCount; $i++) {
        $seed = ($activity_id * 7 + $i * 13) % 1000; // Deterministic but looks random
        $rating = (($seed % 21) + 30) / 10; // Between 3.0 and 5.0
        
        // Determine comment based on rating
        $commentText = '';
        if ($rating >= 4.5) {
            $commentText = $comments[($seed + $i) % count($comments)];
        } else if ($rating >= 3.5) {
            $commentText = $comments[($seed + $i * 2) % count($comments)];
            if (rand(0, 1) == 1) {
                $commentText .= ' ' . $commentVariations[$i % count($commentVariations)];
            }
        } else {
            $commentText = $commentVariations[$i % count($commentVariations)];
        }
        
        $date = date('Y-m-d', strtotime('-' . ($i + 1) * 7 . ' days'));
        
        $reviews[] = [
            'name' => $names[($seed + $i) % count($names)],
            'rating' => $rating,
            'comment' => $commentText,
            'date' => $date
        ];
    }
    
    return $reviews;
}

$reviews = generateRandomReviews($activity_id);

// Calculate the average review rating
$totalRating = 0;
$reviewCount = count($reviews);
foreach ($reviews as $review) {
    $totalRating += $review['rating'];
}

$averageRating = $reviewCount > 0 ? $totalRating / $reviewCount : 0;

// Get the main image dimensions
$imageDimensions = [1200, 800]; // Default dimensions
if ($activity["image_url"] && file_exists($activity["image_url"])) {
    $imgData = @getimagesize($activity["image_url"]);
    if ($imgData) {
        $imageDimensions = [$imgData[0], $imgData[1]];
    }
}

// Determine if image is landscape or portrait
$isLandscape = $imageDimensions[0] >= $imageDimensions[1];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($activity["titre"]); ?> | Synapse</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="../TEMPLATE/Nouveauhead.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        /* Enhanced activity detail page styles */
        body {
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(206, 190, 168, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 90% 80%, rgba(69, 161, 99, 0.15) 0%, transparent 50%),
                linear-gradient(to bottom, rgba(228, 216, 200, 0.9), rgba(228, 216, 200, 0.9));
            position: relative;
            overflow-x: hidden;
        }
        
        /* Floating leaf animation elements */
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
        
        /* Enhanced activity container */
        .activity-wrapper {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 20px;
            position: relative;
            z-index: 2;
        }
        
        .activity-container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            position: relative;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.7);
            transform: translateY(0);
            transition: all 0.4s ease-out;
        }
        
        .activity-container:hover {
            transform: translateY(-10px);
            box-shadow: 0 35px 60px rgba(0, 0, 0, 0.2);
        }
        
        /* Enhanced back button */
        .back-button-container {
            margin-bottom: 25px;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background-color: white;
            color: #666;
            padding: 14px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(200, 200, 200, 0.3);
            z-index: 10;
        }
        
        .back-button::before {
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
        
        .back-button:hover {
            background-color: #f5f5f5;
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.15);
            color: var(--primary-color);
        }
        
        .back-button:hover::before {
            left: 100%;
        }
        
        .back-button i {
            font-size: 16px;
            transition: transform 0.3s ease;
        }
        
        .back-button:hover i {
            transform: translateX(-5px);
        }
        
        /* Enhanced activity header */
        .activity-header {
            position: relative;
            height: 500px;
            overflow: hidden;
        }
        
        .activity-header-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 6s ease;
            transform-origin: center;
        }
        
        .activity-container:hover .activity-header-img {
            transform: scale(1.05);
        }
        
        .activity-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center, rgba(0, 0, 0, 0.2) 0%, rgba(0, 0, 0, 0.4) 70%);
            z-index: 1;
            opacity: 0.7;
            transition: opacity 0.4s ease;
        }
        
        .activity-container:hover .activity-header::before {
            opacity: 0.5;
        }
        
        .activity-header-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, 
                rgba(0, 0, 0, 0.9) 0%, 
                rgba(0, 0, 0, 0.7) 40%, 
                rgba(0, 0, 0, 0.4) 70%, 
                rgba(0, 0, 0, 0) 100%);
            padding: 80px 50px 50px;
            color: white;
            z-index: 2;
        }
        
        .activity-title {
            font-size: 42px;
            margin: 0 0 20px 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            font-weight: 700;
            font-family: var(--font-accent);
            transform: translateY(0);
            transition: transform 0.4s ease;
        }
        
        .activity-container:hover .activity-title {
            transform: translateY(-5px);
        }
        
        .activity-meta {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .activity-rating {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #f1c40f;
            font-size: 18px;
        }
        
        .stars {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        
        .stars i {
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.5));
        }
        
        .activity-period {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            background-color: rgba(255, 255, 255, 0.15);
            padding: 8px 16px;
            border-radius: 30px;
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
        }
        
        .activity-container:hover .activity-period {
            background-color: rgba(255, 255, 255, 0.25);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .activity-period i {
            color: var(--accent-color);
        }
        
        /* Enhanced activity tags */
        .activity-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 5px;
        }
        
        .activity-tag {
            background-color: rgba(130, 137, 119, 0.7);
            color: white;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(5px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
            cursor: pointer;
        }
        
        .activity-tag:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }
        
        .activity-tag i {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .activity-tag.primary {
            background-color: rgba(60, 140, 92, 0.8);
        }
        
        .activity-tag.secondary {
            background-color: rgba(148, 107, 45, 0.8);
        }
        
        .activity-tag.accent {
            background-color: rgba(233, 196, 106, 0.8);
            color: #333;
        }
        
        /* Enhanced main content layout */
        .main-content-wrapper {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            padding: 0;
        }
        
        .activity-content {
            padding: 40px;
            display: flex;
            flex-direction: column;
            background-color: white;
            border-radius: 0 0 0 30px;
        }
        
        .activity-sidebar {
            padding: 40px;
            background-color: #f9f9f9;
            border-radius: 0 0 30px 0;
            position: relative;
        }
        
        .activity-sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background: linear-gradient(135deg, rgba(60, 140, 92, 0.05) 0%, rgba(60, 140, 92, 0) 70%);
            z-index: -1;
            border-radius: 0 0 30px 0;
        }
        
        /* Enhanced section styling */
        .activity-section {
            margin-bottom: 40px;
        }
        
        .activity-section h2 {
            color: var(--text-dark);
            font-size: 28px;
            margin-bottom: 20px;
            position: relative;
            padding-bottom: 15px;
            font-family: var(--font-accent);
        }
        
        .activity-section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-light));
            border-radius: 2px;
            transition: width 0.3s ease;
        }
        
        .activity-section:hover h2::after {
            width: 80px;
        }
        
        .activity-description {
            font-size: 16px;
            line-height: 1.8;
            color: #444;
            white-space: pre-wrap;
        }
        
        /* Enhanced price section */
        .price-section {
            margin-bottom: 30px;
        }
        
        .price-section h3 {
            font-size: 20px;
            color: #444;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .price-section h3 i {
            color: var(--primary-color);
        }
        
        .price-amount {
            font-size: 36px;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .price-amount.free {
            color: var(--accent-color);
        }
        
        .price-amount i {
            font-size: 24px;
            opacity: 0.8;
        }
        
        .price-note {
            color: #666;
            font-size: 14px;
            font-style: italic;
            margin-top: 10px;
        }
        
        /* Enhanced signup button */
        .signup-button {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            font-weight: bold;
            font-size: 16px;
            padding: 16px 30px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.4s ease;
            box-shadow: 0 12px 25px rgba(39, 94, 62, 0.3);
            width: 100%;
            position: relative;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .signup-button::before {
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
        
        .signup-button:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(39, 94, 62, 0.4);
        }
        
        .signup-button:hover::before {
            left: 100%;
        }
        
        .signup-button:active {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(39, 94, 62, 0.3);
        }
        
        .signup-button i {
            font-size: 20px;
            z-index: 2;
            transition: transform 0.3s ease;
        }
        
        .signup-button:hover i {
            transform: translateY(-3px);
        }
        
        .signup-button span {
            z-index: 2;
        }
        
        .add-to-cart-button {
            background: linear-gradient(135deg, var(--secondary-color) 0%, var(--secondary-dark) 100%);
            color: white;
            font-weight: bold;
            font-size: 16px;
            padding: 16px 30px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            transition: all 0.4s ease;
            box-shadow: 0 12px 25px rgba(148, 107, 45, 0.3);
            width: 100%;
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
            z-index: 1;
        }
        
        .add-to-cart-button:hover {
            background: linear-gradient(135deg, var(--secondary-dark) 0%, var(--secondary-color) 100%);
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(148, 107, 45, 0.4);
        }
        
        .add-to-cart-button:hover::before {
            left: 100%;
        }
        
        .add-to-cart-button i {
            font-size: 20px;
            z-index: 2;
            transition: transform 0.3s ease;
        }
        
        .add-to-cart-button:hover i {
            transform: translateY(-3px);
        }
        
        .add-to-cart-button span {
            z-index: 2;
        }
        
        /* Enhanced creator info */
        .creator-info {
            background-color: white;
            border-radius: 20px;
            padding: 25px;
            margin-top: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(230, 230, 230, 0.5);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .creator-info::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-light));
        }
        
        .creator-info:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .creator-info h3 {
            color: #444;
            font-size: 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .creator-info h3 i {
            color: var(--primary-color);
            font-size: 22px;
        }
        
        .creator-details {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .creator-detail {
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            padding: 10px 15px;
            border-radius: 10px;
        }
        
        .creator-detail:hover {
            background-color: rgba(69, 161, 99, 0.05);
            transform: translateX(5px);
        }
        
        .creator-detail i {
            width: 20px;
            color: var(--primary-color);
            font-size: 18px;
        }
        
        .creator-detail span {
            color: #444;
            font-size: 16px;
        }
        
        /* Enhanced activity creation date */
        .activity-created {
            font-size: 14px;
            color: #999;
            margin-top: 30px;
            text-align: right;
            font-style: italic;
        }
        
        /* Enhanced reviews section */
        .reviews-section {
            margin-top: 60px;
            padding-top: 40px;
            border-top: 1px solid #eee;
        }
        
        .reviews-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .reviews-header h2 {
            padding-bottom: 0;
            margin-bottom: 0;
        }
        
        .reviews-header h2::after {
            display: none;
        }
        
        .reviews-average {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .reviews-average-number {
            font-size: 48px;
            font-weight: bold;
            color: var(--primary-dark);
            line-height: 1;
        }
        
        .reviews-average-stars {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .reviews-count {
            color: #666;
            font-size: 14px;
        }
        
        .reviews-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
        }
        
        .review-item {
            background-color: #f9f9f9;
            border-radius: 15px;
            padding: 25px;
            transition: all 0.3s ease;
            border: 1px solid rgba(230, 230, 230, 0.5);
        }
        
        .review-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .reviewer-name {
            font-weight: bold;
            color: #444;
            font-size: 18px;
        }
        
        .review-date {
            color: #999;
            font-size: 14px;
        }
        
        .review-rating {
            margin-bottom: 15px;
        }
        
        .review-rating .stars i {
            color: #f1c40f;
            font-size: 16px;
        }
        
        .review-content {
            color: #555;
            line-height: 1.6;
        }
        
        /* New Similar Activities section */
        .similar-activities {
            margin-top: 60px;
            padding-top: 40px;
            border-top: 1px solid #eee;
        }
        
        .similar-activities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .similar-card {
            background-color: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s ease;
            display: flex;
            flex-direction: column;
            height: 100%;
            border: 1px solid rgba(230, 230, 230, 0.5);
            cursor: pointer;
        }
        
        .similar-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .similar-image {
            height: 180px;
            position: relative;
        }
        
        .similar-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.7s ease;
        }
        
        .similar-card:hover .similar-image img {
            transform: scale(1.1);
        }
        
        .similar-tag {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: rgba(60, 140, 92, 0.9);
            color: white;
            padding: 5px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(5px);
        }
        
        .similar-tag.free {
            background-color: rgba(233, 196, 106, 0.9);
            color: #333;
        }
        
        .similar-content {
            padding: 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .similar-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            transition: color 0.3s ease;
            font-family: var(--font-accent);
        }
        
        .similar-card:hover .similar-title {
            color: var(--primary-color);
        }
        
        .similar-period {
            color: #666;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
        }
        
        .similar-period i {
            color: var(--primary-color);
        }
        
        .similar-rating {
            margin-top: auto;
            color: #f1c40f;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        /* New notification */
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
        
        /* Add particle background */
        .particle {
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 0 15px 5px rgba(255, 255, 255, 0.6);
            animation: particleFloat 18s infinite linear;
            pointer-events: none;
            filter: blur(0.5px);
            z-index: 1;
        }
        
        @keyframes particleFloat {
            0% {
                transform: translateY(0) translateX(0) scale(1);
                opacity: 0;
            }
            10% {
                opacity: 0.8;
            }
            90% {
                opacity: 0.6;
            }
            100% {
                transform: translateY(-100vh) translateX(20vw) scale(0.5);
                opacity: 0;
            }
        }
        
        /* Enhanced image gallery */
        .image-gallery {
            position: relative;
            overflow: hidden;
            border-radius: 20px;
            margin-top: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .gallery-main {
            position: relative;
            height: 400px;
            overflow: hidden;
        }
        
        .gallery-main img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .image-gallery:hover .gallery-main img {
            transform: scale(1.05);
        }
        
        .gallery-thumbs {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .gallery-thumb {
            height: 80px;
            flex: 1;
            border-radius: 10px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.7;
            border: 2px solid transparent;
        }
        
        .gallery-thumb.active {
            opacity: 1;
            border-color: var(--primary-color);
            transform: translateY(-3px);
        }
        
        .gallery-thumb:hover {
            opacity: 1;
            transform: translateY(-3px);
        }
        
        .gallery-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* Enhanced location map */
        .location-map {
            margin-top: 30px;
            background-color: #f0f0f0;
            border-radius: 20px;
            overflow: hidden;
            height: 300px;
            position: relative;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .location-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #666;
            background-color: #f0f0f0;
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="%23aaa" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 80px;
            padding-top: 100px;
        }
        
        /* Enhanced social sharing buttons */
        .social-sharing {
            margin-top: 30px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }
        
        .social-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .social-button:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2);
        }
        
        .social-button.facebook {
            background-color: #1877f2;
        }
        
        .social-button.twitter {
            background-color: #1da1f2;
        }
        
        .social-button.whatsapp {
            background-color: #25d366;
        }
        
        .social-button.email {
            background-color: #ea4335;
        }
        
        /* Enhanced activity status */
        .activity-status {
            background-color: rgba(233, 196, 106, 0.2);
            padding: 15px 20px;
            border-radius: 15px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid var(--accent-color);
        }
        
        .activity-status i {
            font-size: 24px;
            color: var(--accent-color);
        }
        
        .status-text {
            font-size: 15px;
            color: #555;
        }
        
        .status-text strong {
            color: #333;
        }
        
        /* Enhanced mobile responsiveness */
        @media (max-width: 1200px) {
            .activity-header {
                height: 400px;
            }
        }
        
        @media (max-width: 992px) {
            .main-content-wrapper {
                grid-template-columns: 1fr;
            }
            
            .activity-content {
                border-radius: 0;
            }
            
            .activity-sidebar {
                border-radius: 0 0 30px 30px;
            }
            
            .activity-header {
                height: 350px;
            }
            
            .activity-title {
                font-size: 36px;
            }
            
            .activity-meta {
                gap: 15px;
            }
            
            .similar-activities-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .activity-header {
                height: 280px;
            }
            
            .activity-header-overlay {
                padding: 60px 30px 30px;
            }
            
            .activity-title {
                font-size: 28px;
            }
            
            .activity-content, 
            .activity-sidebar {
                padding: 30px;
            }
            
            .reviews-average-number {
                font-size: 36px;
            }
            
            .review-item {
                padding: 20px;
            }
            
            .similar-activities-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 576px) {
            .activity-header {
                height: 240px;
            }
            
            .activity-header-overlay {
                padding: 50px 20px 20px;
            }
            
            .activity-title {
                font-size: 24px;
                margin-bottom: 15px;
            }
            
            .activity-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .activity-content, 
            .activity-sidebar {
                padding: 20px;
            }
            
            .activity-section h2 {
                font-size: 22px;
            }
            
            .price-amount {
                font-size: 28px;
            }
            
            .reviews-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>

    <!-- Floating leaf animation elements -->
    <div class="leaf-animation-container">
        <div class="floating-leaf leaf-1"></div>
        <div class="floating-leaf leaf-3"></div>
        <div class="floating-leaf leaf-5"></div>
        <div class="floating-leaf leaf-7"></div>
    </div>

    <div class="activity-wrapper">
        <div class="back-button-container">
            <a href="activites.php" class="back-button">
                <i class="fa-solid fa-arrow-left"></i> Retour aux activités
            </a>
        </div>
        
        <div class="activity-container">
            <div class="activity-header">
                <?php if ($activity["image_url"]): ?>
                    <img src="<?php echo htmlspecialchars($activity["image_url"]); ?>" alt="<?php echo htmlspecialchars($activity["titre"]); ?>" class="activity-header-img">
                <?php else: ?>
                    <img src="nature-placeholder.jpg" alt="Image par défaut" class="activity-header-img">
                <?php endif; ?>
                
                <div class="activity-header-overlay">
                    <h1 class="activity-title"><?php echo htmlspecialchars($activity["titre"]); ?></h1>
                    
                    <div class="activity-meta">
                        <div class="activity-rating">
                            <?php echo getStars($randomRating); ?>
                        </div>
                        
                        <?php if ($activity["date_ou_periode"]): ?>
                            <div class="activity-period">
                                <i class="fa-regular fa-calendar"></i>
                                <?php echo htmlspecialchars($activity["date_ou_periode"]); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="activity-tags">
                        <?php foreach ($tags as $tag): ?>
                            <div class="activity-tag <?php echo getTagClass($tag); ?>">
                                <i class="fa-solid fa-tag"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $tag)); ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ($isPaid): ?>
                            <div class="activity-tag">
                                <i class="fa-solid fa-euro-sign"></i> Payant
                            </div>
                        <?php else: ?>
                            <div class="activity-tag accent">
                                <i class="fa-solid fa-gift"></i> Gratuit
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="main-content-wrapper">
                <div class="activity-content">
                    <div class="activity-section">
                        <h2>Description</h2>
                        <div class="activity-description">
                            <?php echo nl2br(htmlspecialchars($activity["description"])); ?>
                        </div>
                    </div>
                    
                    <?php if ($creator_data): ?>
                    <div class="creator-info">
                        <h3><i class="fa-solid fa-user-circle"></i> Créé par</h3>
                        <div class="creator-details">
                            <?php if (!empty($creator_data['first_name']) || !empty($creator_data['name'])): ?>
                            <div class="creator-detail">
                                <i class="fa-solid fa-user"></i>
                                <span><?php echo htmlspecialchars($creator_data['first_name'] . ' ' . $creator_data['name']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($creator_data['email'])): ?>
                            <div class="creator-detail">
                                <i class="fa-solid fa-envelope"></i>
                                <span><?php echo htmlspecialchars($creator_data['email']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($creator_data['phone_nb'])): ?>
                            <div class="creator-detail">
                                <i class="fa-solid fa-phone"></i>
                                <span><?php echo htmlspecialchars(formatPhoneNumber($creator_data['phone_nb'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Reviews Section -->
                    <div class="reviews-section">
                        <div class="reviews-header">
                            <h2>Avis des participants</h2>
                            <div class="reviews-average">
                                <div class="reviews-average-number"><?php echo number_format($averageRating, 1); ?></div>
                                <div class="reviews-average-stars">
                                    <?php echo getStars($averageRating); ?>
                                    <div class="reviews-count"><?php echo $reviewCount; ?> avis</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="reviews-list">
                            <?php foreach ($reviews as $review): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <div class="reviewer-name"><?php echo htmlspecialchars($review['name']); ?></div>
                                    <div class="review-date"><?php echo date('d/m/Y', strtotime($review['date'])); ?></div>
                                </div>
                                <div class="review-rating">
                                    <?php echo getStars($review['rating']); ?>
                                </div>
                                <div class="review-content">
                                    <?php echo htmlspecialchars($review['comment']); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Similar Activities -->
                    <?php if (!empty($similar_activities)): ?>
                    <div class="similar-activities">
                        <h2>Activités similaires</h2>
                        <div class="similar-activities-grid">
                            <?php foreach ($similar_activities as $similar): 
                                $similarRating = (($similar['id'] * 7) % 21 + 30) / 10; // Rating between 3.0 and 5.0
                                $isPaid = $similar["prix"] > 0;
                            ?>
                            <div class="similar-card" data-id="<?php echo $similar['id']; ?>">
                                <div class="similar-image">
                                    <?php if ($similar["image_url"]): ?>
                                        <img src="<?php echo htmlspecialchars($similar["image_url"]); ?>" alt="<?php echo htmlspecialchars($similar["titre"]); ?>">
                                    <?php else: ?>
                                        <img src="nature-placeholder.jpg" alt="Image par défaut">
                                    <?php endif; ?>
                                    
                                    <?php if ($isPaid): ?>
                                        <div class="similar-tag"><?php echo number_format($similar["prix"], 2); ?> €</div>
                                    <?php else: ?>
                                        <div class="similar-tag free">Gratuit</div>
                                    <?php endif; ?>
                                </div>
                                <div class="similar-content">
                                    <div class="similar-title"><?php echo htmlspecialchars($similar["titre"]); ?></div>
                                    
                                    <?php if ($similar["date_ou_periode"]): ?>
                                        <div class="similar-period">
                                            <i class="fa-regular fa-calendar"></i>
                                            <?php echo htmlspecialchars($similar["date_ou_periode"]); ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="similar-rating">
                                        <?php echo getStars($similarRating); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($activity["date_creation"])): ?>
                        <p class="activity-created">
                            Activité créée le <?php echo date("d/m/Y", strtotime($activity["date_creation"])); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="activity-sidebar">
                    <div class="price-section">
                        <h3><i class="fa-solid fa-tag"></i> Prix</h3>
                        <div class="price-amount <?php echo $isPaid ? '' : 'free'; ?>">
                            <?php if ($isPaid): ?>
                                <i class="fa-solid fa-euro-sign"></i>
                            <?php else: ?>
                                <i class="fa-solid fa-gift"></i>
                            <?php endif; ?>
                            <?php echo $priceText; ?>
                        </div>
                        
                        <?php if ($isPaid): ?>
                            <p class="price-note">Réservation obligatoire, paiement en ligne sécurisé.</p>
                        <?php else: ?>
                            <p class="price-note">Activité gratuite, inscription recommandée pour garantir votre place.</p>
                        <?php endif; ?>
                    </div>
                    
                    <button class="signup-button" id="signup-button" data-id="<?php echo $activity['id']; ?>">
                        <i class="fa-solid fa-user-plus"></i> <span>S'inscrire à cette activité</span>
                    </button>
                    
                    <button class="add-to-cart-button" id="add-to-cart-button" data-id="<?php echo $activity['id']; ?>" 
                            data-title="<?php echo htmlspecialchars($activity['titre']); ?>" 
                            data-price="<?php echo $activity['prix']; ?>" 
                            data-image="<?php echo htmlspecialchars($activity['image_url'] ? $activity['image_url'] : 'nature-placeholder.jpg'); ?>" 
                            data-period="<?php echo htmlspecialchars($activity['date_ou_periode']); ?>" 
                            data-tags="<?php echo htmlspecialchars($activity['tags']); ?>">
                        <i class="fa-solid fa-cart-shopping"></i> <span>Ajouter au panier</span>
                    </button>
                    
                    <?php if ($activity["date_ou_periode"]): ?>
                        <div class="activity-status">
                            <i class="fa-solid fa-calendar-check"></i>
                            <p class="status-text">
                                <strong>Statut :</strong> Inscriptions ouvertes pour <?php echo htmlspecialchars($activity["date_ou_periode"]); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Location Map Placeholder -->
                    <div class="location-map">
                        <div class="location-placeholder">
                            Localisation non disponible
                        </div>
                    </div>
                    
                    <!-- Social Sharing -->
                    <div class="social-sharing">
                        <div class="social-button facebook">
                            <i class="fa-brands fa-facebook-f"></i>
                        </div>
                        <div class="social-button twitter">
                            <i class="fa-brands fa-twitter"></i>
                        </div>
                        <div class="social-button whatsapp">
                            <i class="fa-brands fa-whatsapp"></i>
                        </div>
                        <div class="social-button email">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Create ambient particles
            createParticles();
            
            // Initialize the cart if it doesn't exist
            if (!localStorage.getItem('synapse-cart')) {
                localStorage.setItem('synapse-cart', JSON.stringify([]));
            }
            
            // Update cart count
            updateCartCount();
            
            // Sign-up button functionality
            const signupButton = document.getElementById('signup-button');
            if (signupButton) {
                signupButton.addEventListener('click', function() {
                    showNotification('Fonctionnalité d\'inscription à venir. Cette activité sera développée ultérieurement.', 'info');
                });
            }
            
            // Add to cart button functionality
            const addToCartButton = document.getElementById('add-to-cart-button');
            if (addToCartButton) {
                addToCartButton.addEventListener('click', function() {
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
            }
            
            // Social sharing buttons functionality
            document.querySelectorAll('.social-button').forEach(button => {
                button.addEventListener('click', function() {
                    const url = encodeURIComponent(window.location.href);
                    const title = encodeURIComponent(document.title);
                    let shareUrl = '';
                    
                    if (this.classList.contains('facebook')) {
                        shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
                    } else if (this.classList.contains('twitter')) {
                        shareUrl = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
                    } else if (this.classList.contains('whatsapp')) {
                        shareUrl = `https://api.whatsapp.com/send?text=${title} ${url}`;
                    } else if (this.classList.contains('email')) {
                        shareUrl = `mailto:?subject=${title}&body=Check out this activity: ${url}`;
                    }
                    
                    if (shareUrl) {
                        window.open(shareUrl, '_blank');
                    }
                });
            });
            
            // Make activity tags clickable - redirect to activites.php with filter
            document.querySelectorAll('.activity-tag').forEach(tag => {
                tag.addEventListener('click', function() {
                    const tagText = this.textContent.trim();
                    let filterUrl = 'activites.php?';
                    
                    if (tagText === 'Gratuit') {
                        filterUrl += 'price=gratuit';
                    } else if (tagText === 'Payant') {
                        filterUrl += 'price=payant';
                    } else if (tagText === 'Intérieur') {
                        filterUrl += 'location=interieur';
                    } else if (tagText === 'Extérieur') {
                        filterUrl += 'location=exterieur';
                    } else {
                        // Convert tag name to match database tag format
                        const tagName = tagText.toLowerCase().replace(' ', '_');
                        filterUrl += 'category=' + tagName;
                    }
                    
                    window.location.href = filterUrl;
                });
            });
            
            // Make similar activity cards clickable
            document.querySelectorAll('.similar-card').forEach(card => {
                card.addEventListener('click', function() {
                    const activityId = this.getAttribute('data-id');
                    if (activityId) {
                        window.location.href = 'activite.php?id=' + activityId;
                    }
                });
            });
            
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
            
            // Function to create ambient particles
            function createParticles() {
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
// Close database connections
$stmt->close();
$conn->close();
if (isset($user_stmt)) {
    $user_stmt->close();
}
$user_conn->close();

if (isset($similarStmt)) {
    $similarStmt->close();
}
?>