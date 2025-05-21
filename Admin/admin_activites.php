<?php
// Recherche de l'utilisateur connecté (id de session)
include 'adminVerify.php';

// Configuration de la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "activity";

// Connexion à la base de données
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Connexion à la base de données des utilisateurs
$user_conn = mysqli_connect($servername, $username, $password, "user_db");
if (!$user_conn) {
    die("Connection to user database failed: " . mysqli_connect_error());
}

// Handle delete action
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $delete_query = "DELETE FROM activites WHERE id = $id";
    if(mysqli_query($conn, $delete_query)) {
        header("Location: admin_activites.php?success=deleted");
        exit();
    } else {
        $error = "Erreur lors de la suppression: " . mysqli_error($conn);
    }
}

// Récupérer toutes les activités
$activites_query = "SELECT * FROM activites";
$result = $conn->query($activites_query);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Activités</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .nav-buttons { margin-bottom: 20px; }
        .nav-buttons a { 
            display: inline-block; 
            padding: 8px 15px; 
            margin-right: 10px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .nav-buttons a:hover { background-color: #45a049; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
        th { background-color: #f2f2f2; }
        .image-url { max-width: 300px; word-break: break-all; }
        .description { max-width: 200px; max-height: 100px; overflow: auto; }
        .action-links a { margin-right: 10px; }
        .success { color: green; margin-bottom: 15px; padding: 10px; background-color: #dff0d8; border-radius: 4px; }
        .error { color: red; margin-bottom: 15px; padding: 10px; background-color: #f2dede; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Gestion des Activités</h1>
    
    <div class="nav-buttons">
        <a href="admin.php">Tableau de bord</a>
        <a href="../Testing grounds/main.php">Site utilisateur</a>
    </div>
    
    <?php if(isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
        <p class="success">L'activité a été supprimée avec succès.</p>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Titre</th>
                <th>Description</th>
                <th>Prix</th>
                <th>Image URL</th>
                <th>Date</th>
                <th>Date de création</th>
                <th>ID Utilisateur</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($activite = $result->fetch_assoc()): 
                // Extract creator information from activity description
                $creator_id = null;
                $creator_name = "Inconnu";
                $creator_first_name = "";
                
                if(preg_match('/<!--CREATOR:([^-]+)-->/', $activite['description'], $matches)) {
                    try {
                        $encoded_data = $matches[1];
                        // Try to decode the data - this could be direct JSON or base64 encoded JSON
                        $creator_data = json_decode($encoded_data, true);
                        
                        if (!$creator_data && function_exists('base64_decode')) {
                            // If direct JSON decode failed, try base64 decode first
                            $json_data = base64_decode($encoded_data);
                            $creator_data = json_decode($json_data, true);
                        }
                        
                        if ($creator_data && isset($creator_data['user_id'])) {
                            $creator_id = $creator_data['user_id'];
                            
                            // Query the user database to get the latest user data
                            $user_query = "SELECT name, first_name FROM user_form WHERE id = " . (int)$creator_id;
                            $user_result = $user_conn->query($user_query);
                            
                            if ($user_result && $user_result->num_rows > 0) {
                                $user_data = $user_result->fetch_assoc();
                                $creator_name = $user_data['name'];
                                $creator_first_name = $user_data['first_name'];
                            } else if (isset($creator_data['name'])) {
                                // Fallback to the data in the comment if database query fails
                                $creator_name = $creator_data['name'];
                                $creator_first_name = $creator_data['first_name'] ?? '';
                            }
                        }
                    } catch (Exception $e) {
                        // If there's an error parsing, just continue with default values
                    }
                }
                
                // Clean description for display (remove the creator info)
                $clean_description = preg_replace('/<!--CREATOR:[^-]+-->/', '', $activite['description']);
            ?>
                <tr>
                    <td><?php echo $activite['id']; ?></td>
                    <td><?php echo htmlspecialchars($activite['titre']); ?></td>
                    <td class="description"><?php echo htmlspecialchars($clean_description); ?></td>
                    <td><?php echo number_format($activite['prix'], 2, ',', ' '); ?> €</td>
                    <td class="image-url">
                        <?php if(!empty($activite['image_url'])): ?>
                            <a href="<?php echo htmlspecialchars($activite['image_url']); ?>" target="_blank">
                                <?php echo htmlspecialchars($activite['image_url']); ?>
                            </a>
                        <?php else: ?>
                            Aucune image
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($activite['date_ou_periode']); ?></td>
                    <td><?php echo $activite['date_creation']; ?></td>
                    <td>
                        <?php if($creator_id): ?>
                            ID: <?php echo $creator_id; ?><br>
                            Nom: <?php echo htmlspecialchars($creator_first_name . ' ' . $creator_name); ?>
                        <?php else: ?>
                            Non spécifié
                        <?php endif; ?>
                    </td>
                    <td class="action-links">
                        <a href="admin_activites.php?delete=<?php echo $activite['id']; ?>" 
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette activité?')">Supprimer</a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($result) == 0): ?>
                <tr>
                    <td colspan="9">Aucune activité trouvée</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>