<?php
// Add session_start() at the top if not already present
session_start();

// Include the newsletter functions
require_once '../includes/newsletter_functions.php';

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

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if an image was provided (either cropped or direct upload)
    $has_image = false;
    
    if (isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])) {
        $has_image = true;
    } elseif (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $has_image = true;
    }
    
    if (!$has_image) {
        // Redirect back to the form with an error message
        header("Location: jenis.php?error=image_required");
        exit();
    }
    
    // Récupérer les données du formulaire
    $titre = $conn->real_escape_string($_POST['titre']);
    $description = $conn->real_escape_string($_POST['description']);
    $date_ou_periode = isset($_POST['date_ou_periode']) ? $conn->real_escape_string($_POST['date_ou_periode']) : '';
    
    // Get creator information from session
    $creator_info = "";
    if(isset($_SESSION['user_id'])) {
        // Create a hidden JSON with creator info at the beginning of the description
        $creator_data = [
            'user_id' => $_SESSION['user_id'],
            'name' => isset($_SESSION['user_name']) ? $_SESSION['user_name'] : '',
            'first_name' => isset($_SESSION['user_first_name']) ? $_SESSION['user_first_name'] : '',
            'email' => isset($_SESSION['user_email']) ? $_SESSION['user_email'] : ''
        ];
        
        // Convert to JSON and encode to hide it
        $creator_info = "<!--CREATOR:" . base64_encode(json_encode($creator_data)) . "-->";
    }
    
    // Add creator info to the beginning of the description (hidden in HTML comment)
    $description = $creator_info . $description;
    
    // Gestion du prix
    $prix = 0;
    $payment_tag = 'gratuit'; // Default to free activity
    if (isset($_POST['type_prix']) && $_POST['type_prix'] == 'payant' && isset($_POST['prix'])) {
        $prix = floatval($_POST['prix']);
        if ($prix > 0) {
            $payment_tag = 'payant'; // Set to paid activity
        }
    }
    
    // Rest of your code for image handling
    $image_url = '';
    
    // Si une image recadrée a été fournie
    if (isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])) {
        // Existing image cropping code
        $cropped_image = $_POST['cropped_image'];
        
        // Extraire les données binaires de l'image base64
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
        // Existing direct upload code
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
    
    // Insérer les données dans la base de données
    $sql = "INSERT INTO activites (titre, description, image_url, prix, date_ou_periode) 
            VALUES ('$titre', '$description', '$image_url', $prix, '$date_ou_periode')";
    
    if ($conn->query($sql) === TRUE) {
        $activity_id = $conn->insert_id;
        
        // Create an array with all tags, including the payment tag
        $allTags = isset($_POST['tags']) && is_array($_POST['tags']) ? $_POST['tags'] : [];
        
        // Add the payment tag (gratuit or payant)
        $allTags[] = $payment_tag;
        
        // Update tag processing
        foreach ($allTags as $tag_name) {
            $tag_name = $conn->real_escape_string($tag_name);
            
            // Verify that the tag exists in tag_definitions
            $checkTagSQL = "SELECT id FROM tag_definitions WHERE name = ?";
            $stmt = $conn->prepare($checkTagSQL);
            $stmt->bind_param("s", $tag_name);
            $stmt->execute();
            $tagResult = $stmt->get_result();
            
            if ($tagResult && $tagResult->num_rows > 0) {
                $tagRow = $tagResult->fetch_assoc();
                $tag_definition_id = $tagRow['id'];
                
                // Insert into activity_tags
                $insertTagSQL = "INSERT INTO activity_tags (activity_id, tag_definition_id) VALUES (?, ?)";
                $stmt = $conn->prepare($insertTagSQL);
                $stmt->bind_param("ii", $activity_id, $tag_definition_id);
                $stmt->execute();
            }
            $stmt->close();
        }
        
        // Send notification to subscribed users about the new activity
        sendActivityNotification($titre, $activity_id);
        
        // Rediriger vers la page d'accueil avec un message de succès
        header("Location: main.php?success=1");
        exit();
    } else {
        echo "Erreur: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>