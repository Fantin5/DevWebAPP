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

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $titre = $conn->real_escape_string($_POST['titre']);
    $description = $conn->real_escape_string($_POST['description']);
    $date_ou_periode = isset($_POST['date_ou_periode']) ? $conn->real_escape_string($_POST['date_ou_periode']) : '';
    
    // Gestion du prix
    $prix = 0;
    if (isset($_POST['type_prix']) && $_POST['type_prix'] == 'payant' && isset($_POST['prix'])) {
        $prix = floatval($_POST['prix']);
    }
    
    // Gestion de l'image
    $image_url = '';
    
    // Si une image recadrée a été fournie
    if (isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])) {
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
        
        // Gestion des tags
        if (isset($_POST['tags']) && is_array($_POST['tags'])) {
            foreach ($_POST['tags'] as $tag) {
                $tag = $conn->real_escape_string($tag);
                $sql_tag = "INSERT INTO tags (activite_id, nom_tag) VALUES ($activity_id, '$tag')";
                $conn->query($sql_tag);
            }
        }
        
        // Ajouter automatiquement le tag 'gratuit' si l'activité est gratuite
        if ($prix == 0) {
            $sql_gratuit = "INSERT INTO tags (activite_id, nom_tag) VALUES ($activity_id, 'gratuit')";
            $conn->query($sql_gratuit);
        }
        
        // Rediriger vers la page d'accueil avec un message de succès
        header("Location: main.php?success=1");
        exit();
    } else {
        echo "Erreur: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
?>