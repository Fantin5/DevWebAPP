<?php
// Start session at the very beginning - no whitespace or output before this
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

require_once 'tag_setup.php';

// Variables pour les messages
$successMessage = '';
$errorMessage = '';

// Get current user ID
$user_id = $_SESSION['user_id'];

// Vérifier si un ID d'activité est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Rediriger vers la page mes activités si aucun ID valide n'est fourni
    header("Location: mes-activites.php");
    exit();
}

$activity_id = $_GET['id'];

$tagManager = new TagManager($conn);
$tagDefinitions = $tagManager->getAllTags();

// Récupérer les détails de l'activité avant modification
$sql = "SELECT a.*, 
        GROUP_CONCAT(td.name) AS tags
        FROM activites a 
        LEFT JOIN activity_tags at ON a.id = at.activity_id
        LEFT JOIN tag_definitions td ON at.tag_definition_id = td.id
        WHERE a.id = ?
        GROUP BY a.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    // Rediriger vers la page mes activités si l'activité n'existe pas
    header("Location: mes-activites.php");
    exit();
}

$activity = $result->fetch_assoc();
$stmt->close();

// Extraire l'information du créateur depuis la description
$creator_info = '';
if (preg_match('/<!--CREATOR:(.*?)-->/', $activity['description'], $matches)) {
    $creator_info = $matches[0]; // Capture the entire creator comment
}

// Traitement du formulaire lors de la soumission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $date_ou_periode = trim($_POST['date_ou_periode']);
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    $prix = isset($_POST['prix']) ? floatval($_POST['prix']) : 0;
    $selected_tags = isset($_POST['tags']) ? $_POST['tags'] : [];
    
    // Add creator information back to the description if it existed
    if (!empty($creator_info)) {
        $description = $creator_info . $description;
    }
    
    // Gestion de l'image
    $image_url = $activity['image_url']; // Garder l'image actuelle par défaut
    
    // Si une image recadrée a été fournie
    if (isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])) {
        // Extraire les données binaires de l'image base64
        $cropped_image = $_POST['cropped_image'];
        $image_parts = explode(";base64,", $cropped_image);
        $image_base64 = isset($image_parts[1]) ? $image_parts[1] : $cropped_image;
        $image_data = base64_decode($image_base64);
        
        // Générer un nom de fichier unique
        $filename = 'activite_' . time() . '_' . uniqid() . '.jpg';
        $upload_path = 'uploads/images/' . $filename;
        
        // Créer le répertoire s'il n'existe pas
        if (!file_exists('uploads/images/')) {
            mkdir('uploads/images/', 0777, true);
        }
        
        // Sauvegarder l'image
        if (file_put_contents($upload_path, $image_data)) {
            $image_url = $upload_path;
        }
    } 
    // Si un fichier a été uploadé directement sans recadrage
    elseif (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $new_filename = 'activite_' . time() . '_' . uniqid() . '.' . $filetype;
            $upload_path = 'uploads/images/' . $new_filename;
            
            if (!file_exists('uploads/images/')) {
                mkdir('uploads/images/', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = $upload_path;
            }
        }
    }
    
    // Validation simple
    if (empty($titre)) {
        $errorMessage = "Le titre est obligatoire.";
    } else {
        // Mise à jour de l'activité dans la base de données
        if ($image_url) {
            // Si une nouvelle image a été uploadée
            $sql_update = "UPDATE activites SET titre = ?, description = ?, date_ou_periode = ?, location = ?, prix = ?, image_url = ? WHERE id = ?";
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("ssssdsi", $titre, $description, $date_ou_periode, $location, $prix, $image_url, $activity_id);
        } else {
            // Si aucune nouvelle image n'a été uploadée, garder l'ancienne image
            $sql_update = "UPDATE activites SET titre = ?, description = ?, date_ou_periode = ?, location = ?, prix = ? WHERE id = ?";
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("ssssdi", $titre, $description, $date_ou_periode, $location, $prix, $activity_id);
        }

        if ($stmt->execute()) {
            $tagManager->updateActivityTags($activity_id, $selected_tags);
            $successMessage = "L'activité a été mise à jour avec succès.";
            header("Location: mes-activites.php");
            exit();
        } else {
            $errorMessage = "Erreur lors de la mise à jour de l'activité: " . $conn->error;
        }
        
        $stmt->close();
    }
}

// Récupérer les tags actuels de l'activité
$current_tags = [];
if (!empty($activity['tags'])) {
    $current_tags = explode(',', $activity['tags']);
}

// Function to determine the CSS class for tags using database definitions (consistent with other pages)
function getTagClass($tag) {
    global $tagDefinitions;
    
    // Use the assigned class from tag_definitions
    return isset($tagDefinitions[$tag]) ? $tagDefinitions[$tag]['class'] : 'primary';
}

// Function to get the display name for a tag using database definitions (consistent with other pages)
function getTagDisplayName($tag) {
    global $tagDefinitions;
    
    // Use the display name from tag_definitions
    return isset($tagDefinitions[$tag]) ? $tagDefinitions[$tag]['display_name'] : ucfirst(str_replace('_', ' ', $tag));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une activité | Synapse</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="../TEMPLATE/Nouveauhead.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #87a96b 0%, #5a7c4d 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Nature Background Elements */
        .nature-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            z-index: 1;
            overflow: hidden;
        }

        .bg-leaf {
            position: absolute;
            width: 120px;
            height: 120px;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="%2387a96b" d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z"/></svg>') no-repeat center;
            background-size: contain;
            opacity: 0.1;
            animation: float 20s ease-in-out infinite;
        }

        .bg-leaf-1 {
            top: 10%;
            left: 5%;
            animation-delay: 0s;
        }

        .bg-leaf-2 {
            top: 60%;
            right: 10%;
            animation-delay: -7s;
            transform: rotate(45deg);
        }

        .bg-leaf-3 {
            bottom: 20%;
            left: 15%;
            animation-delay: -14s;
            transform: rotate(-30deg);
        }

        .bg-wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 200px;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 120"><path fill="%23ffffff" fill-opacity="0.1" d="M0,64L48,69.3C96,75,192,85,288,80C384,75,480,53,576,48C672,43,768,53,864,64C960,75,1056,85,1152,85.3C1248,85,1344,75,1392,69.3L1440,64L1440,120L1392,120C1344,120,1248,120,1152,120C1056,120,960,120,864,120C768,120,672,120,576,120C480,120,384,120,288,120C192,120,96,120,48,120L0,120Z"></path></svg>') repeat-x;
            background-size: 1200px 200px;
            animation: wave 15s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(10deg); }
            66% { transform: translateY(10px) rotate(-5deg); }
        }

        @keyframes wave {
            0%, 100% { transform: translateX(0px); }
            50% { transform: translateX(-50px); }
        }

        /* Floating Nature Elements */
        .floating-elements {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            pointer-events: none;
            z-index: 2;
            overflow: hidden;
        }

        .floating-leaf {
            position: absolute;
            font-size: 2rem;
            animation: floatUp 15s linear infinite;
            opacity: 0.3;
        }

        .floating-particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: floatUp 12s linear infinite;
        }

        .leaf-1 { left: 10%; animation-delay: 0s; }
        .leaf-2 { left: 30%; animation-delay: -5s; }
        .leaf-3 { left: 60%; animation-delay: -10s; }
        .particle-1 { left: 20%; animation-delay: -2s; }
        .particle-2 { left: 70%; animation-delay: -8s; }
        .particle-3 { left: 85%; animation-delay: -12s; }

        @keyframes floatUp {
            0% {
                transform: translateY(100vh) translateX(0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.6;
            }
            90% {
                opacity: 0.3;
            }
            100% {
                transform: translateY(-10vh) translateX(20px) rotate(360deg);
                opacity: 0;
            }
        }

        .page-wrapper {
            position: relative;
            z-index: 10;
            min-height: 100vh;
            padding: 2rem 0;
        }

        .form-container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 25px;
            padding: 0;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(20px);
            border: 2px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
            position: relative;
        }

        .form-header {
            background: linear-gradient(135deg, rgba(135, 169, 107, 0.9) 0%, rgba(90, 124, 77, 0.9) 100%);
            color: white;
            padding: 3rem 2rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .form-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60"><circle cx="30" cy="30" r="2" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            animation: sparkle 20s linear infinite;
            pointer-events: none;
        }

        @keyframes sparkle {
            0% { transform: translateX(0) translateY(0); }
            100% { transform: translateX(-60px) translateY(-60px); }
        }

        .header-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: inline-block;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .form-header h1 {
            font-size: 2.5rem;
            margin: 0 0 1rem 0;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }

        .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .nature-form {
            padding: 3rem 2rem 2rem;
        }

        .form-section {
            margin-bottom: 3rem;
            background: rgba(245, 248, 250, 0.8);
            border-radius: 20px;
            padding: 2rem;
            position: relative;
            border: 1px solid rgba(135, 169, 107, 0.2);
            transition: all 0.3s ease;
        }

        .form-section:hover {
            background: rgba(245, 248, 250, 1);
            box-shadow: 0 10px 30px rgba(135, 169, 107, 0.15);
            transform: translateY(-2px);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(135, 169, 107, 0.2);
        }

        .section-icon {
            font-size: 1.5rem;
            color: #87a96b;
            background: rgba(135, 169, 107, 0.1);
            padding: 0.75rem;
            border-radius: 12px;
        }

        .section-header h3 {
            color: #2c3e50;
            font-size: 1.4rem;
            margin: 0;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 2rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: #2c3e50;
            font-size: 1rem;
            position: relative;
        }

        .form-group label i {
            margin-right: 0.5rem;
            color: #87a96b;
        }

        .required {
            color: #e74c3c;
            margin-left: 0.25rem;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid rgba(135, 169, 107, 0.3);
            border-radius: 15px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #87a96b;
            background: white;
            box-shadow: 0 0 0 4px rgba(135, 169, 107, 0.2);
            transform: translateY(-2px);
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
            font-family: inherit;
        }

        .field-hint {
            background: rgba(135, 169, 107, 0.1);
            border: 1px solid rgba(135, 169, 107, 0.2);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 0.75rem;
            font-size: 0.9rem;
            color: #5a7c4d;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            transition: all 0.3s ease;
        }

        .field-hint i {
            color: #87a96b;
            margin-top: 0.1rem;
            flex-shrink: 0;
        }

        .field-hint.suggestion {
            background: rgba(255, 193, 7, 0.1);
            border-color: rgba(255, 193, 7, 0.3);
            color: #8a6d3b;
        }

        .field-hint.suggestion i {
            color: #f39c12;
        }

        /* Tags Section */
        .tags-section {
            background: linear-gradient(135deg, rgba(135, 169, 107, 0.05) 0%, rgba(233, 196, 106, 0.05) 100%);
            border: 2px dashed rgba(135, 169, 107, 0.3);
        }

        .tags-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .tag-card {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 1.25rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .tag-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(135, 169, 107, 0.2), transparent);
            transition: left 0.5s ease;
        }

        .tag-card:hover::before {
            left: 100%;
        }

        .tag-card input[type="checkbox"] {
            display: none;
        }

        .tag-card label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .tag-icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #87a96b 0%, #5a7c4d 100%);
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(135, 169, 107, 0.3);
        }

        .tag-name {
            font-weight: 600;
            color: #2c3e50;
            text-align: center;
            font-size: 0.95rem;
        }

        .tag-hover-effect {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(135, 169, 107, 0.1) 0%, rgba(233, 196, 106, 0.1) 100%);
            border-radius: 15px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .tag-card:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 15px 40px rgba(135, 169, 107, 0.25);
            border-color: rgba(135, 169, 107, 0.4);
        }

        .tag-card:hover .tag-hover-effect {
            opacity: 1;
        }

        .tag-card:hover .tag-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 12px 25px rgba(135, 169, 107, 0.4);
        }

        .tag-card input[type="checkbox"]:checked + label .tag-card,
        .tag-card.selected {
            background: linear-gradient(135deg, rgba(135, 169, 107, 0.2) 0%, rgba(233, 196, 106, 0.2) 100%);
            border-color: #87a96b;
            box-shadow: 0 10px 30px rgba(135, 169, 107, 0.3);
        }

        .tag-card.selected .tag-icon {
            background: linear-gradient(135deg, #e9c46a 0%, #f4a261 100%);
            transform: scale(1.1);
        }

        /* Image Upload Section */
        .image-upload-area {
            background: rgba(248, 250, 252, 0.8);
            border-radius: 20px;
            padding: 2rem;
            border: 2px dashed rgba(135, 169, 107, 0.3);
            transition: all 0.3s ease;
        }

        .image-upload-area:hover {
            border-color: #87a96b;
            background: rgba(248, 250, 252, 1);
        }

        .upload-zone {
            text-align: center;
            padding: 3rem 2rem;
            border: 2px dashed rgba(135, 169, 107, 0.4);
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.5);
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .upload-zone::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(135, 169, 107, 0.1), transparent);
            transition: left 0.5s ease;
        }

        .upload-zone:hover::before {
            left: 100%;
        }

        .upload-zone:hover {
            border-color: #87a96b;
            background: rgba(255, 255, 255, 0.8);
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(135, 169, 107, 0.2);
        }

        .upload-icon {
            font-size: 4rem;
            color: #87a96b;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .upload-zone h4 {
            color: #2c3e50;
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .upload-zone p {
            color: #7f8c8d;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 1;
        }

        .btn-browse {
            background: linear-gradient(135deg, #87a96b 0%, #5a7c4d 100%);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 8px 20px rgba(135, 169, 107, 0.3);
            position: relative;
            z-index: 1;
        }

        .btn-browse:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(135, 169, 107, 0.4);
        }

        .image-preview,
        .image-controls {
            margin-top: 1.5rem;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .image-controls {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-secondary,
        .btn-outline {
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: white;
            border: none;
            box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(108, 117, 125, 0.4);
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.9);
            color: #6c757d;
            border: 2px solid rgba(108, 117, 125, 0.3);
        }

        .btn-outline:hover {
            background: rgba(108, 117, 125, 0.1);
            border-color: #6c757d;
            transform: translateY(-2px);
        }

        /* Current Image Display */
        .current-image-display {
            margin-bottom: 2rem;
            text-align: center;
        }

        .current-image-container {
            display: inline-block;
            position: relative;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            margin-bottom: 1rem;
        }

        .current-image-container img {
            max-width: 100%;
            max-height: 200px;
            display: block;
        }

        .current-image-label {
            color: #5a7c4d;
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 1rem;
        }

        /* Submit Section */
        .submit-section {
            text-align: center;
            padding: 2rem 0 1rem;
            border-top: 2px solid rgba(135, 169, 107, 0.2);
            margin-top: 3rem;
        }

        .btn-submit {
            background: linear-gradient(135deg, #87a96b 0%, #5a7c4d 100%);
            color: white;
            font-size: 1.2rem;
            font-weight: 700;
            padding: 1.25rem 3rem;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 15px 35px rgba(135, 169, 107, 0.4);
            position: relative;
            overflow: hidden;
            min-width: 250px;
        }

        .btn-content {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .btn-shine {
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.6s ease;
            z-index: 1;
        }

        .btn-submit:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 20px 40px rgba(135, 169, 107, 0.5);
        }

        .btn-submit:hover .btn-shine {
            left: 100%;
        }

        .btn-submit:active {
            transform: translateY(-2px) scale(1.02);
        }

        /* Form Actions */
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 2px solid rgba(135, 169, 107, 0.2);
        }

        .cancel-button {
            background: rgba(248, 249, 250, 0.9);
            color: #6c757d;
            padding: 1rem 2rem;
            border: 2px solid rgba(108, 117, 125, 0.3);
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .cancel-button:hover {
            background: rgba(108, 117, 125, 0.1);
            border-color: #6c757d;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(108, 117, 125, 0.2);
        }

        .submit-button {
            background: linear-gradient(135deg, #87a96b 0%, #5a7c4d 100%);
            color: white;
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 10px 25px rgba(135, 169, 107, 0.4);
            font-size: 1.1rem;
        }

        .submit-button:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 30px rgba(135, 169, 107, 0.5);
        }

        /* Error/Success Messages */
        .error-message,
        .success-message {
            padding: 1.25rem 2rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            font-weight: 600;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .error-message {
            background: linear-gradient(135deg, rgba(231, 76, 60, 0.1) 0%, rgba(192, 57, 43, 0.1) 100%);
            color: #c0392b;
            border: 2px solid rgba(231, 76, 60, 0.3);
        }

        .success-message {
            background: linear-gradient(135deg, rgba(135, 169, 107, 0.1) 0%, rgba(90, 124, 77, 0.1) 100%);
            color: #5a7c4d;
            border: 2px solid rgba(135, 169, 107, 0.3);
        }

        /* Creator Info Notice */
        .creator-info-notice {
            background: linear-gradient(135deg, rgba(52, 152, 219, 0.1) 0%, rgba(41, 128, 185, 0.1) 100%);
            border-left: 4px solid #3498db;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            color: #2980b9;
            font-size: 0.95rem;
            border-radius: 0 12px 12px 0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .creator-info-notice i {
            color: #3498db;
            font-size: 1.2rem;
        }

        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            backdrop-filter: blur(10px);
        }

        .modal-content {
            background-color: white;
            border-radius: 25px;
            max-width: 90%;
            width: 900px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .close-modal {
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 28px;
            cursor: pointer;
            color: #777;
            z-index: 1;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-modal:hover {
            background: rgba(135, 169, 107, 0.1);
            color: #87a96b;
            transform: rotate(90deg);
        }

        .crop-header {
            display: flex;
            align-items: center;
            padding: 2rem 3rem;
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 600;
            border-bottom: 2px solid rgba(135, 169, 107, 0.2);
            gap: 1rem;
            background: linear-gradient(135deg, rgba(135, 169, 107, 0.1) 0%, rgba(233, 196, 106, 0.1) 100%);
        }

        .crop-header i {
            color: #87a96b;
            font-size: 1.8rem;
        }

        .modal-subtitle {
            color: #7f8c8d;
            margin: 1.5rem 3rem;
            font-size: 1rem;
        }

        .cropper-container {
            height: 400px;
            margin: 0 3rem;
            background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAIGNIUk0AAHolAACAgwAA+f8AAIDpAAB1MAAA6mAAADqYAAAXb5JfxUYAAAA7SURBVHjaYvz//z8DJYCJgUIw8AawIHNOnDiBUyE+NbgASPP///+pZwALSIAcF+BSg2EALzZcwMjA8B8A2NwSe+zc/dUAAAAASUVORK5CYII=') repeat;
            border-radius: 15px;
            overflow: hidden;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            padding: 2rem 3rem;
            border-top: 2px solid rgba(135, 169, 107, 0.2);
            background: rgba(248, 250, 252, 0.5);
        }

        .apply-button,
        .cancel-button-modal {
            padding: 1rem 2.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 140px;
            justify-content: center;
            font-size: 1rem;
        }

        .apply-button {
            background: linear-gradient(135deg, #87a96b 0%, #5a7c4d 100%);
            color: white;
            box-shadow: 0 8px 20px rgba(135, 169, 107, 0.3);
        }

        .apply-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(135, 169, 107, 0.4);
        }

        .cancel-button-modal {
            background: rgba(248, 249, 250, 0.9);
            color: #6c757d;
            border: 2px solid rgba(108, 117, 125, 0.3);
        }

        .cancel-button-modal:hover {
            background: rgba(108, 117, 125, 0.1);
            transform: translateY(-2px);
        }

        /* Cropper.js customization */
        .cropper-view-box {
            outline: 2px solid #87a96b;
        }

        .cropper-point {
            background-color: #87a96b;
        }

        .cropper-line {
            background-color: #87a96b;
        }

        .cropper-dashed {
            border-color: #87a96b;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-container {
                margin: 1rem;
                border-radius: 20px;
            }

            .form-header {
                padding: 2rem 1.5rem 1.5rem;
            }

            .form-header h1 {
                font-size: 2rem;
            }

            .nature-form {
                padding: 2rem 1.5rem 1.5rem;
            }

            .form-section {
                padding: 1.5rem;
            }

            .tags-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 0.75rem;
            }

            .form-actions {
                flex-direction: column-reverse;
                gap: 1rem;
            }

            .cancel-button,
            .submit-button {
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                width: 95%;
                margin: 1rem;
            }

            .crop-header,
            .modal-subtitle,
            .modal-buttons {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }

            .cropper-container {
                margin: 0 1.5rem;
                height: 300px;
            }
        }

        .hidden {
            display: none !important;
        }
    </style>
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>

    <!-- Nature Background Elements -->
    <div class="nature-bg">
        <div class="bg-leaf bg-leaf-1"></div>
        <div class="bg-leaf bg-leaf-2"></div>
        <div class="bg-leaf bg-leaf-3"></div>
        <div class="bg-wave"></div>
    </div>

    <div class="page-wrapper">
        <!-- Floating Nature Elements -->
        <div class="floating-elements">
            <div class="floating-leaf leaf-1">🍃</div>
            <div class="floating-leaf leaf-2">🌿</div>
            <div class="floating-leaf leaf-3">🍀</div>
            <div class="floating-particle particle-1"></div>
            <div class="floating-particle particle-2"></div>
            <div class="floating-particle particle-3"></div>
        </div>

        <div class="form-container">
            <!-- Form Header with Nature Theme -->
            <div class="form-header">
                <div class="header-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <h1>Faire Évoluer Votre Activité</h1>
                <p class="subtitle">Cultivez les changements et laissez votre activité s'épanouir 🌱</p>
            </div>

            <?php
            // Messages d'erreur avec style amélioré
            if (!empty($errorMessage)) {
                echo '<div class="error-message"><i class="fas fa-exclamation-triangle"></i>' . $errorMessage . '</div>';
            }
            if (!empty($successMessage)) {
                echo '<div class="success-message"><i class="fas fa-check-circle"></i>' . $successMessage . '</div>';
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>" method="POST" enctype="multipart/form-data" id="activity-form" class="nature-form">
                <!-- Section: Basic Info -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-leaf section-icon"></i>
                        <h3>Les Racines de votre Activité</h3>
                    </div>
                    
                    <div class="form-group">
                        <label for="titre">
                            <i class="fas fa-tag"></i>
                            Nom de votre activité <span class="required">*</span>
                        </label>
                        <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($activity['titre']); ?>" placeholder="Ex: Atelier jardinage urbain sous les étoiles" required />
                    </div>

                    <div class="form-group">
                        <label for="description">
                            <i class="fas fa-feather-alt"></i>
                            Racontez votre histoire <span class="required">*</span>
                        </label>
                        
                        <?php if (!empty($creator_info)): ?>
                        <div class="creator-info-notice">
                            <i class="fa-solid fa-info-circle"></i>
                            Les informations du créateur seront automatiquement préservées.
                        </div>
                        <?php endif; ?>
                        
                        <textarea id="description" name="description" rows="5" placeholder="Décrivez l'expérience que vous voulez partager... Qu'est-ce qui rend cette activité spéciale ?" required><?php echo htmlspecialchars(preg_replace('/<!--CREATOR:.*?-->/', '', $activity['description'])); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="date_ou_periode">
                            <i class="fas fa-calendar-alt"></i>
                            Quand cette magie opère-t-elle ? <span class="required">*</span>
                        </label>
                        <input type="text" id="date_ou_periode" name="date_ou_periode" value="<?php echo htmlspecialchars($activity['date_ou_periode']); ?>" placeholder="Ex: Tous les samedis au coucher du soleil / 15 juin 2025" required />
                        <div class="field-hint" id="date-hint">
                            <i class="fas fa-lightbulb"></i>
                            <span id="hint-text">Formats acceptés: date précise (15/06/2025), récurrence (Tous les samedis), ou période (01/06/2025 - 15/06/2025)</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location">
                            <i class="fas fa-map-marker-alt"></i>
                            Où se déroule cette aventure ?
                        </label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($activity['location'] ?? ''); ?>" placeholder="Ex: Parc de Belleville, Paris 20ème / 123 Rue de la Nature, Lyon" />
                        <div class="field-hint">
                            <i class="fas fa-info-circle"></i>
                            <span>Adresse complète ou lieu-dit pour que les participants puissent vous trouver</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="prix">
                            <i class="fas fa-euro-sign"></i>
                            Prix (en €)
                        </label>
                        <input type="number" id="prix" name="prix" value="<?php echo htmlspecialchars($activity['prix']); ?>" min="0" step="0.01" placeholder="0.00" />
                        <div class="field-hint">
                            <i class="fas fa-info-circle"></i>
                            <span>Laissez à 0 pour une activité gratuite</span>
                        </div>
                    </div>
                </div>

                <!-- Section: Tags (The Cool Part!) -->
                <div class="form-section tags-section">
                    <div class="section-header">
                        <i class="fas fa-tags section-icon"></i>
                        <h3>Dans quel Écosystème ?</h3>
                        <p class="section-subtitle">Aidez les autres à découvrir votre activité</p>
                    </div>
                    
                    <div class="tags-grid">
                        <?php 
                        // Dynamic tag icons - works with any tag!
                        function getTagIcon($tagName) {
                            $tag_icons = [
                                'interieur' => 'fa-home',
                                'exterieur' => 'fa-tree', 
                                'art' => 'fa-palette',
                                'cuisine' => 'fa-utensils',
                                'sport' => 'fa-running',
                                'bien_etre' => 'fa-spa',
                                'creativite' => 'fa-lightbulb',
                                'ecologie' => 'fa-leaf',
                                'randonnee' => 'fa-hiking',
                                'jardinage' => 'fa-seedling',
                                'meditation' => 'fa-om',
                                'artisanat' => 'fa-hammer',
                                'compétition' => 'fa-trophy'
                            ];
                            
                            // If we have a specific icon, use it
                            if (isset($tag_icons[$tagName])) {
                                return $tag_icons[$tagName];
                            }
                            
                            // Smart fallback based on keywords in tag name
                            $name = strtolower($tagName);
                            
                            if (strpos($name, 'sport') !== false || strpos($name, 'course') !== false || strpos($name, 'fitness') !== false) return 'fa-running';
                            if (strpos($name, 'art') !== false || strpos($name, 'peinture') !== false || strpos($name, 'dessin') !== false) return 'fa-palette';
                            if (strpos($name, 'cuisine') !== false || strpos($name, 'culinaire') !== false || strpos($name, 'recette') !== false) return 'fa-utensils';
                            if (strpos($name, 'nature') !== false || strpos($name, 'plante') !== false || strpos($name, 'jardin') !== false) return 'fa-seedling';
                            if (strpos($name, 'maison') !== false || strpos($name, 'intérieur') !== false || strpos($name, 'indoor') !== false) return 'fa-home';
                            if (strpos($name, 'extérieur') !== false || strpos($name, 'outdoor') !== false || strpos($name, 'plein') !== false) return 'fa-tree';
                            if (strpos($name, 'bien') !== false || strpos($name, 'relax') !== false || strpos($name, 'zen') !== false) return 'fa-spa';
                            if (strpos($name, 'créa') !== false || strpos($name, 'innovation') !== false || strpos($name, 'idée') !== false) return 'fa-lightbulb';
                            if (strpos($name, 'marche') !== false || strpos($name, 'randonnée') !== false || strpos($name, 'trek') !== false) return 'fa-hiking';
                            if (strpos($name, 'méditation') !== false || strpos($name, 'yoga') !== false || strpos($name, 'spirituel') !== false) return 'fa-om';
                            if (strpos($name, 'craft') !== false || strpos($name, 'manuel') !== false || strpos($name, 'fabrication') !== false) return 'fa-hammer';
                            if (strpos($name, 'compétition') !== false || strpos($name, 'concours') !== false || strpos($name, 'tournoi') !== false) return 'fa-trophy';
                            if (strpos($name, 'musique') !== false || strpos($name, 'son') !== false || strpos($name, 'audio') !== false) return 'fa-music';
                            if (strpos($name, 'photo') !== false || strpos($name, 'image') !== false || strpos($name, 'vidéo') !== false) return 'fa-camera';
                            if (strpos($name, 'lecture') !== false || strpos($name, 'livre') !== false || strpos($name, 'écriture') !== false) return 'fa-book';
                            if (strpos($name, 'jeu') !== false || strpos($name, 'game') !== false || strpos($name, 'ludique') !== false) return 'fa-gamepad';
                            if (strpos($name, 'tech') !== false || strpos($name, 'digital') !== false || strpos($name, 'informatique') !== false) return 'fa-laptop';
                            if (strpos($name, 'enfant') !== false || strpos($name, 'famille') !== false || strpos($name, 'kids') !== false) return 'fa-child';
                            if (strpos($name, 'eau') !== false || strpos($name, 'natation') !== false || strpos($name, 'aqua') !== false) return 'fa-swimmer';
                            if (strpos($name, 'formation') !== false || strpos($name, 'éducation') !== false || strpos($name, 'apprendre') !== false) return 'fa-graduation-cap';
                            
                            // Final fallback: beautiful default icons based on first letter or random
                            $defaultIcons = ['fa-star', 'fa-heart', 'fa-gem', 'fa-fire', 'fa-magic', 'fa-bolt', 'fa-crown', 'fa-diamond'];
                            $charCode = ord(strtoupper($name[0]));
                            return $defaultIcons[$charCode % count($defaultIcons)];
                        }
                        
                        $colors = ['green', 'blue', 'orange', 'purple', 'teal', 'pink'];
                        $color_index = 0;
                        
                        foreach ($tagDefinitions as $tag_name => $tag_data): 
                            if ($tag_name === 'gratuit' || $tag_name === 'payant') continue; // Skip payment tags
                            
                            $icon = getTagIcon($tag_name);
                            $color = $colors[$color_index % count($colors)];
                            $color_index++;
                            $is_checked = in_array($tag_name, $current_tags);
                        ?>
                        <div class="tag-card <?php echo $color; ?> <?php echo $is_checked ? 'selected' : ''; ?>" data-tag="<?php echo htmlspecialchars($tag_name); ?>">
                            <input type="checkbox" id="<?php echo htmlspecialchars($tag_name); ?>" name="tags[]" value="<?php echo htmlspecialchars($tag_name); ?>" <?php echo $is_checked ? 'checked' : ''; ?>>
                            <label for="<?php echo htmlspecialchars($tag_name); ?>">
                                <div class="tag-icon">
                                    <i class="fas <?php echo $icon; ?>"></i>
                                </div>
                                <div class="tag-name"><?php echo htmlspecialchars($tag_data['display_name']); ?></div>
                                <div class="tag-hover-effect"></div>
                            </label>
                        </div>
                        <?php 
                        endforeach; 
                        ?>
                    </div>
                    <div class="tags-hint">
                        <i class="fas fa-info-circle"></i>
                        Sélectionnez les tags qui correspondent le mieux à votre activité
                    </div>
                </div>

                <!-- Section: Image -->
                <div class="form-section">
                    <div class="section-header">
                        <i class="fas fa-camera section-icon"></i>
                        <h3>Une Image qui Inspire</h3>
                    </div>
                    
                    <?php if ($activity["image_url"]): ?>
                    <div class="current-image-display">
                        <div class="current-image-label">Image actuelle :</div>
                        <div class="current-image-container">
                            <img src="<?php echo htmlspecialchars($activity["image_url"]); ?>" alt="<?php echo htmlspecialchars($activity["titre"]); ?>">
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="image-upload-area">
                        <div class="upload-zone" id="upload-zone">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h4>Glissez votre nouvelle image ici</h4>
                            <p>ou cliquez pour parcourir</p>
                            <button type="button" id="browse-button" class="btn-browse">
                                <i class="fas fa-folder-open"></i> Parcourir
                            </button>
                            <input type="file" id="image-input" name="image" accept="image/*" style="display: none" />
                        </div>

                        <div id="preview-container" class="hidden">
                            <div class="image-preview">
                                <img id="image-preview" src="#" alt="Aperçu" />
                            </div>
                            <div class="image-controls">
                                <button type="button" id="crop-button" class="btn-secondary">
                                    <i class="fas fa-crop-alt"></i> Recadrer
                                </button>
                                <button type="button" id="change-image" class="btn-outline">
                                    <i class="fas fa-redo"></i> Changer
                                </button>
                            </div>
                        </div>

                        <div id="cropped-container" class="hidden">
                            <div class="image-preview">
                                <img id="cropped-preview" src="#" alt="Image finale" />
                            </div>
                            <button type="button" id="recrop-button" class="btn-secondary">
                                <i class="fas fa-crop-alt"></i> Recadrer à nouveau
                            </button>
                        </div>

                        <input type="hidden" id="cropped-data" name="cropped_image" />
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="mes-activites.php" class="cancel-button">
                        <i class="fa-solid fa-times"></i> Annuler
                    </a>
                    
                    <button type="submit" class="submit-button">
                        <i class="fa-solid fa-save"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>

    <!-- Crop Modal -->
    <div id="crop-modal" class="modal hidden">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="crop-header">
                <i class="fas fa-crop-alt"></i> Recadrer votre image
            </div>
            <p class="modal-subtitle">
                Ajustez le cadre pour obtenir une vignette optimale (format 4:3)
            </p>
            <div class="cropper-container">
                <img id="cropper-image" src="#" alt="Image à recadrer" />
            </div>
            <div class="modal-buttons">
                <button id="apply-crop" class="apply-button">
                    <i class="fas fa-check"></i> Appliquer
                </button>
                <button id="cancel-crop" class="cancel-button-modal">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script src="brainjenis.js"></script>
    
    <script>
        // Initialize cart count if needed
        function updateCartCount() {
            try {
                const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
                const cartCount = document.getElementById('panier-count');
                if (cartCount) {
                    cartCount.textContent = cart.length;
                }
            } catch (error) {
                console.error('Error updating cart count:', error);
            }
        }
        
        if (!localStorage.getItem('synapse-cart')) {
            localStorage.setItem('synapse-cart', JSON.stringify([]));
        }
        updateCartCount();
        
        // Initialize tag selection
        document.addEventListener('DOMContentLoaded', function() {
            const tagCards = document.querySelectorAll('.tag-card');
            
            tagCards.forEach(card => {
                const checkbox = card.querySelector('input[type="checkbox"]');
                
                // Set initial state
                if (checkbox.checked) {
                    card.classList.add('selected');
                }
                
                // Simple click handler
                card.addEventListener('click', function() {
                    checkbox.checked = !checkbox.checked;
                    checkbox.dispatchEvent(new Event('change'));
                });

                // Simple color change on selection
                checkbox.addEventListener('change', function() {
                    if (this.checked) {
                        card.classList.add('selected');
                    } else {
                        card.classList.remove('selected');
                    }
                });
            });
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
<!-- cvq -->