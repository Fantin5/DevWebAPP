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

// Variables pour les messages
$successMessage = '';
$errorMessage = '';

// Vérifier si un ID d'activité est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Rediriger vers la page mes activités si aucun ID valide n'est fourni
    header("Location: mes-activites.php");
    exit();
}

$activity_id = $_GET['id'];

// Liste des tags disponibles
$available_tags = [
    'art', 'cuisine', 'bien_etre', 'creativite', 'sport', 
    'exterieur', 'interieur', 'gratuit', 'ecologie', 
    'randonnee', 'jardinage', 'meditation', 'artisanat'
];

// Traitement du formulaire lors de la soumission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $date_ou_periode = trim($_POST['date_ou_periode']);
    $prix = isset($_POST['prix']) ? floatval($_POST['prix']) : 0;
    $selected_tags = isset($_POST['tags']) ? $_POST['tags'] : [];
    
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
            $sql_update = "UPDATE activites SET titre = ?, description = ?, date_ou_periode = ?, prix = ?, image_url = ? WHERE id = ?";
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("sssdsi", $titre, $description, $date_ou_periode, $prix, $image_url, $activity_id);
        } else {
            // Si aucune nouvelle image n'a été uploadée, garder l'ancienne image
            $sql_update = "UPDATE activites SET titre = ?, description = ?, date_ou_periode = ?, prix = ? WHERE id = ?";
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("sssdi", $titre, $description, $date_ou_periode, $prix, $activity_id);
        }
        
        if ($stmt->execute()) {
            // Supprimer les anciens tags
            $sql_delete_tags = "DELETE FROM tags WHERE activite_id = ?";
            $stmt_tags_delete = $conn->prepare($sql_delete_tags);
            $stmt_tags_delete->bind_param("i", $activity_id);
            $stmt_tags_delete->execute();
            $stmt_tags_delete->close();
            
            // Ajouter les nouveaux tags
            if (!empty($selected_tags)) {
                $sql_add_tag = "INSERT INTO tags (activite_id, nom_tag) VALUES (?, ?)";
                $stmt_tags = $conn->prepare($sql_add_tag);
                
                foreach ($selected_tags as $tag) {
                    $stmt_tags->bind_param("is", $activity_id, $tag);
                    $stmt_tags->execute();
                }
                
                $stmt_tags->close();
            }
            
            $successMessage = "L'activité a été mise à jour avec succès.";
            
            // Redirection immédiate, sans délai et sans message
            header("Location: mes-activites.php");
            exit();
        } else {
            $errorMessage = "Erreur lors de la mise à jour de l'activité: " . $conn->error;
        }
        
        $stmt->close();
    }
}

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
    // Rediriger vers la page mes activités si l'activité n'existe pas
    header("Location: mes-activites.php");
    exit();
}

$activity = $result->fetch_assoc();
$stmt->close();

// Récupérer les tags actuels de l'activité
$current_tags = [];
if (!empty($activity['tags'])) {
    $current_tags = explode(',', $activity['tags']);
}

// Function pour déterminer la classe CSS pour les tags
// Cette fonction est locale à ce fichier
function getTagStyleClass($tag) {
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
    <style>
        .page-container {
            width: 90%;
            max-width: 900px;
            margin: 40px auto 60px;
        }
        
        .page-title {
            text-align: center;
            margin: 30px 0;
            color: #828977;
        }
        
        .form-container {
            background-color: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .form-section h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }
        
        .form-group input[type="text"]:focus,
        .form-group input[type="number"]:focus,
        .form-group textarea:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(69, 161, 99, 0.2);
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .price-input {
            position: relative;
        }
        
        .price-input input {
            padding-left: 30px;
        }
        
        .price-input::before {
            content: "€";
            position: absolute;
            left: 12px;
            top: 12px;
            color: #777;
        }
        
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
        }
        
        .tag-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tag-item input[type="checkbox"] {
            display: none;
        }
        
        .tag-item label {
            background-color: #f1f1f1;
            color: #555;
            padding: 8px 15px;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tag-item input[type="checkbox"]:checked + label {
            background-color: var(--primary-color);
            color: #111;
        }
        
        .tag-item input[type="checkbox"]:checked + label.secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .tag-item input[type="checkbox"]:checked + label.accent {
            background-color: var(--accent-color);
            color: #111;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .cancel-button {
            background-color: #f1f1f1;
            color: #666;
            padding: 12px 25px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
        }
        
        .cancel-button:hover {
            background-color: #e1e1e1;
            transform: translateY(-2px);
        }
        
        .submit-button {
            background-color: var(--primary-color);
            color: #111;
            padding: 12px 30px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(69, 161, 99, 0.3);
        }
        
        .submit-button:hover {
            background-color: #3abd7a;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(69, 161, 99, 0.4);
        }
        
        .notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 25px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            z-index: 1000;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            opacity: 1;
            transition: opacity 0.5s;
        }
        
        .notification.success {
            background-color: var(--primary-color);
            color: #111;
        }
        
        .notification.error {
            background-color: #e74c3c;
            color: white;
        }
        
        /* Thumbnails display */
        .thumbnails-container {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            gap: 15px;
        }
        
        .image-container {
            width: 300px;
            height: 200px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        
        .image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .arrow-container {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #828977;
            margin: 0 10px;
        }
        
        #new-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
            color: #999;
        }
        
        #new-image-placeholder i {
            font-size: 32px;
            margin-bottom: 10px;
        }
        
        #new-image-placeholder p {
            margin: 0;
        }
        
        .upload-zone {
            border: 2px dashed #ddd;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background-color: #f9f9f9;
            transition: all 0.3s;
            cursor: pointer;
            margin-top: 20px;
        }
        
        .upload-zone:hover {
            border-color: var(--primary-color);
            background-color: #f0f9f3;
        }
        
        .upload-zone i {
            font-size: 40px;
            color: #ccc;
            margin-bottom: 15px;
            display: block;
        }
        
        .upload-zone p {
            color: #777;
            margin-bottom: 15px;
        }
        
        .button-secondary {
            background-color: #828977;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .button-secondary:hover {
            background-color: #6b7163;
        }
        
        .button-outline {
            background-color: transparent;
            color: #666;
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .button-outline:hover {
            background-color: #f5f5f5;
        }
        
        .hidden {
            display: none;
        }
        
        .crop-controls {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .file-input-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        
        .custom-file-button {
            display: inline-block;
            padding: 10px 15px;
            background-color: var(--primary-color);
            color: #111;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .custom-file-button:hover {
            background-color: #3abd7a;
        }
        
        /* Modal styling */
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
        }
        
        .modal-content {
            background-color: white;
            border-radius: 15px;
            max-width: 90%;
            width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .close-modal {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #777;
            z-index: 1;
        }
        
        .crop-header {
            display: flex;
            align-items: center;
            padding: 20px 30px;
            color: #333;
            font-size: 22px;
            font-weight: 600;
            border-bottom: 1px solid #eee;
            gap: 10px;
        }
        
        .crop-header i {
            color: #55b87c;
        }
        
        .modal-subtitle {
            color: #777;
            margin: 20px 30px;
            font-size: 15px;
        }
        
        .cropper-container {
            height: 400px;
            margin: 0 auto;
            background: url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAACXBIWXMAAAsTAAALEwEAmpwYAAAAIGNIUk0AAHolAACAgwAA+f8AAIDpAAB1MAAA6mAAADqYAAAXb5JfxUYAAAA7SURBVHjaYvz//z8DJYCJgUIw8AawIHNOnDiBUyE+NbgASPP///+pZwALSIAcF+BSg2EALzZcwMjA8B8A2NwSe+zc/dUAAAAASUVORK5CYII=') repeat;
        }
        
        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 20px 30px;
            border-top: 1px solid #eee;
        }
        
        .apply-button {
            background-color: #55b87c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 120px;
            justify-content: center;
        }
        
        .apply-button:hover {
            background-color: #45a76a;
            transform: translateY(-2px);
        }
        
        .cancel-button {
            background-color: #eee;
            color: #666;
            padding: 10px 20px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
            min-width: 120px;
            justify-content: center;
        }
        
        .cancel-button:hover {
            background-color: #ddd;
            transform: translateY(-2px);
        }
        
        /* Cropper.js customization */
        .cropper-view-box {
            outline: 1px solid #4285f4;
            outline-color: #55b87c;
        }
        
        .cropper-point {
            background-color: #55b87c;
        }
        
        .cropper-line {
            background-color: #55b87c;
        }
        
        .cropper-dashed {
            border-color: #55b87c;
        }
        
        .button-secondary {
            background-color: #828977;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .button-secondary:hover {
            background-color: #6b7163;
        }
        
        .button-outline {
            background-color: transparent;
            color: #666;
            padding: 8px 16px;
            border: 1px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .button-outline:hover {
            background-color: #f5f5f5;
        }
        
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>

    <h1 class="page-title">Modifier une activité</h1>

    <div class="page-container">
        <?php if (!empty($errorMessage)): ?>
            <div class="notification error">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-container">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["REQUEST_URI"]); ?>" enctype="multipart/form-data">
                <div class="form-section">
                    <h3>Informations générales</h3>
                    
                    <div class="form-group">
                        <label for="titre">Titre de l'activité *</label>
                        <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($activity['titre']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_ou_periode">Date ou période</label>
                        <input type="text" id="date_ou_periode" name="date_ou_periode" value="<?php echo htmlspecialchars($activity['date_ou_periode']); ?>" placeholder="Ex: 15 Juin 2024 ou Tous les samedis">
                    </div>
                    
                    <div class="form-group">
                        <label for="prix">Prix (en €)</label>
                        <div class="price-input">
                            <input type="number" id="prix" name="prix" value="<?php echo htmlspecialchars($activity['prix']); ?>" min="0" step="0.01">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Description</h3>
                    
                    <div class="form-group">
                        <label for="description">Description détaillée</label>
                        <textarea id="description" name="description" placeholder="Décrivez votre activité..."><?php echo htmlspecialchars($activity['description']); ?></textarea>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Image de l'activité</h3>
                    
                    <div class="form-group">
                        <div class="thumbnails-container">
                            <div class="image-container">
                                <?php if ($activity["image_url"]): ?>
                                    <img src="<?php echo htmlspecialchars($activity["image_url"]); ?>" alt="<?php echo htmlspecialchars($activity["titre"]); ?>">
                                <?php else: ?>
                                    <img src="nature-placeholder.jpg" alt="Image par défaut">
                                <?php endif; ?>
                            </div>
                            
                            <div class="arrow-container">
                                <i class="fa-solid fa-arrow-right"></i>
                            </div>
                            
                            <div class="image-container" id="new-image-container">
                                <div id="new-image-placeholder">
                                    <i class="fa-solid fa-image"></i>
                                    <p>Prévisualisation</p>
                                </div>
                                <img id="image-preview" src="#" alt="Aperçu" style="display: none;">
                            </div>
                        </div>
                        
                        <div class="upload-zone" id="upload-zone">
                            <i class="fa-solid fa-leaf"></i>
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p>Glissez et déposez une image ici<br />ou</p>
                            <button type="button" id="browse-button" class="button-secondary">
                                Parcourir
                            </button>
                            <input type="file" id="image-input" name="image" accept="image/*" style="display: none">
                        </div>
                        
                        <div id="preview-container" class="hidden">
                            <div class="crop-controls">
                                <button type="button" id="crop-button" class="button-secondary">
                                    <i class="fa-solid fa-crop"></i> Recadrer l'image
                                </button>
                                <button type="button" id="change-image" class="button-outline">
                                    <i class="fa-solid fa-arrow-rotate-left"></i> Changer l'image
                                </button>
                            </div>
                        </div>
                        
                        <div id="cropped-container" class="hidden">
                            <button type="button" id="recrop-button" class="button-secondary">
                                <i class="fa-solid fa-crop"></i> Recadrer à nouveau
                            </button>
                        </div>
                        
                        <!-- Champ caché pour stocker l'image recadrée -->
                        <input type="hidden" id="cropped-data" name="cropped_image">
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Tags</h3>
                    <p>Sélectionnez les tags qui décrivent le mieux votre activité :</p>
                    
                    <div class="tags-container">
                        <?php foreach ($available_tags as $tag): ?>
                            <div class="tag-item">
                                <input type="checkbox" id="tag-<?php echo $tag; ?>" name="tags[]" value="<?php echo $tag; ?>" <?php echo in_array($tag, $current_tags) ? 'checked' : ''; ?>>
                                <label for="tag-<?php echo $tag; ?>" class="<?php echo getTagStyleClass($tag); ?>">
                                    <i class="fa-solid fa-tag"></i> <?php echo ucfirst(str_replace('_', ' ', $tag)); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
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

    <!-- Modal pour le recadrage -->
    <div id="crop-modal" class="modal hidden">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div class="crop-header">
                <i class="fa-solid fa-crop"></i> Recadrer l'image
            </div>
            <p class="modal-subtitle">
                Ajustez le cadre pour obtenir une vignette optimale (format 4:3)
            </p>
            <div class="cropper-container">
                <img id="cropper-image" src="#" alt="Image à recadrer" />
            </div>
            <div class="modal-buttons">
                <button id="apply-crop" class="apply-button">
                    <i class="fa-solid fa-check"></i> Appliquer
                </button>
                <button id="cancel-crop" class="cancel-button">
                    <i class="fa-solid fa-xmark"></i> Annuler
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
// Complete rewrite of crop functionality for modifier-activite.php
document.addEventListener('DOMContentLoaded', function() {
    // ---------------
    // DOM ELEMENTS
    // ---------------
    // Main elements
    const uploadZone = document.getElementById('upload-zone');
    const imageInput = document.getElementById('image-input');
    const browseButton = document.getElementById('browse-button');
    const previewContainer = document.getElementById('preview-container');
    const cropButton = document.getElementById('crop-button');
    const changeImageButton = document.getElementById('change-image');
    const imagePreview = document.getElementById('image-preview');
    
    // Image containers
    const newImageContainer = document.getElementById('new-image-container');
    const newImagePlaceholder = document.getElementById('new-image-placeholder');
    
    // Cropped related elements
    const croppedContainer = document.getElementById('cropped-container');
    const recropButton = document.getElementById('recrop-button');
    const croppedData = document.getElementById('cropped-data');  // Hidden input
    
    // Modal elements
    const cropModal = document.getElementById('crop-modal');
    const cropperImage = document.getElementById('cropper-image');
    const applyCropButton = document.getElementById('apply-crop');
    const cancelCropButton = document.getElementById('cancel-crop');
    const closeModal = document.querySelector('.close-modal');
    
    // ---------------
    // CROPPER INSTANCE
    // ---------------
    let cropper = null;
    
    // ---------------
    // HELPER FUNCTIONS
    // ---------------
    
    // Show an element
    function show(element) {
        if (!element) return;
        if (element.classList.contains('hidden')) {
            element.classList.remove('hidden');
        }
        element.style.display = '';
    }
    
    // Hide an element
    function hide(element) {
        if (!element) return;
        if (!element.classList.contains('hidden')) {
            element.classList.add('hidden');
        }
    }
    
    // ---------------
    // MAIN FUNCTIONS
    // ---------------
    
    // Process selected file
    function processFile(file) {
        if (!file || !file.type.match('image.*')) {
            alert('Veuillez sélectionner une image valide.');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            // Set preview image source
            if (imagePreview) {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            }
            
            // Update UI
            if (newImagePlaceholder) newImagePlaceholder.style.display = 'none';
            hide(uploadZone);
            show(previewContainer);
            hide(croppedContainer);
            
            // Show cropper after a short delay to ensure image is loaded
            setTimeout(showCropper, 300);
        };
        reader.readAsDataURL(file);
    }
    
    // Display the cropper tool
    function showCropper() {
        if (!cropperImage || !imagePreview) return;
        
        // Set cropper image
        cropperImage.src = imagePreview.src;
        show(cropModal);
        
        // Destroy existing cropper if it exists
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
        
        // Create new cropper with a delay to ensure image is ready
        setTimeout(() => {
            try {
                cropper = new Cropper(cropperImage, {
                    aspectRatio: 4 / 3,
                    viewMode: 1,
                    guides: true,
                    autoCropArea: 0.8,
                    background: true,
                    responsive: true,
                    zoomable: true,
                    dragMode: 'move'
                });
                console.log('Cropper initialized successfully');
            } catch (error) {
                console.error('Failed to initialize cropper:', error);
                alert('Une erreur est survenue lors de l\'initialisation de l\'outil de recadrage.');
            }
        }, 200);
    }
    
    // Apply the crop
    function applyCrop() {
        if (!cropper) {
            console.error('No cropper instance found');
            return;
        }
        
        try {
            // Generate cropped canvas
            const croppedCanvas = cropper.getCroppedCanvas({
                width: 800,
                height: 600
            });
            
            // Update preview and store data
            if (imagePreview) {
                imagePreview.src = croppedCanvas.toDataURL('image/jpeg');
                imagePreview.style.display = 'block';
            }
            
            if (croppedData) {
                croppedData.value = croppedCanvas.toDataURL('image/jpeg');
            }
            
            // Update UI
            if (newImagePlaceholder) newImagePlaceholder.style.display = 'none';
            hide(previewContainer);
            show(croppedContainer);
            
            // Close modal
            closeCropModal();
            console.log('Crop applied successfully');
        } catch (error) {
            console.error('Error applying crop:', error);
            alert('Une erreur est survenue lors de l\'application du recadrage.');
        }
    }
    
    // Close crop modal
    function closeCropModal() {
        hide(cropModal);
        
        // Destroy cropper
        if (cropper) {
            try {
                cropper.destroy();
                cropper = null;
            } catch (error) {
                console.error('Error destroying cropper:', error);
            }
        }
    }
    
    // Reset image selection
    function resetImageSelection() {
        if (imageInput) imageInput.value = '';
        hide(previewContainer);
        hide(croppedContainer);
        show(uploadZone);
        
        if (imagePreview) imagePreview.style.display = 'none';
        if (newImagePlaceholder) newImagePlaceholder.style.display = 'flex';
    }
    
    // ---------------
    // EVENT LISTENERS
    // ---------------
    
    // File input change
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                processFile(this.files[0]);
            }
        });
    }
    
    // Browse button click
    if (browseButton && imageInput) {
        browseButton.addEventListener('click', function() {
            imageInput.click();
        });
    }
    
    // Drag and drop
    if (uploadZone) {
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.backgroundColor = 'rgba(130, 137, 119, 0.2)';
        });
        
        uploadZone.addEventListener('dragleave', function() {
            this.style.backgroundColor = '';
        });
        
        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.backgroundColor = '';
            
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                processFile(e.dataTransfer.files[0]);
            }
        });
    }
    
    // Crop button click
    if (cropButton) {
        cropButton.addEventListener('click', showCropper);
    }
    
    // Change image button click
    if (changeImageButton) {
        changeImageButton.addEventListener('click', resetImageSelection);
    }
    
    // Apply crop button click
    if (applyCropButton) {
        applyCropButton.addEventListener('click', applyCrop);
    }
    
    // Recrop button click
    if (recropButton) {
        recropButton.addEventListener('click', function() {
            hide(croppedContainer);
            show(previewContainer);
            showCropper();
        });
    }
    
    // Cancel crop button click
    if (cancelCropButton) {
        cancelCropButton.addEventListener('click', closeCropModal);
    }
    
    // Close modal click
    if (closeModal) {
        closeModal.addEventListener('click', closeCropModal);
    }
    
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
    
    // Log initialization success
    console.log('Crop functionality initialized');
});
    </script>
</body>
</html>

<?php
$conn->close();
?>
<!-- le code est horrible pardon mdrr -->