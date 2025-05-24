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

// Handle add tag action
if(isset($_POST['add_tag'])) {
    $tag_name = trim(mysqli_real_escape_string($conn, $_POST['tag_name']));
    $display_name = trim(mysqli_real_escape_string($conn, $_POST['display_name']));
    
    if(!empty($tag_name) && !empty($display_name)) {
        $insert_query = "INSERT INTO tag_definitions (name, display_name) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("ss", $tag_name, $display_name);
        
        if($stmt->execute()) {
            header("Location: admin_activites.php?success=tag_added");
            exit();
        }
    }
}

// Handle delete action with proper foreign key constraint handling
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Start transaction to ensure data integrity
    $conn->begin_transaction();
    
    try {
        // Step 1: Delete related purchase records from activites_achats
        $delete_purchases_query = "DELETE FROM activites_achats WHERE activite_id = ?";
        $stmt1 = $conn->prepare($delete_purchases_query);
        $stmt1->bind_param("i", $id);
        $stmt1->execute();
        
        // Step 2: Delete related activity tags from activity_tags
        $delete_tags_query = "DELETE FROM activity_tags WHERE activity_id = ?";
        $stmt2 = $conn->prepare($delete_tags_query);
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        
        // Step 3: Delete related evaluations (if any)
        $delete_evaluations_query = "DELETE FROM evaluations WHERE activite_id = ?";
        $stmt3 = $conn->prepare($delete_evaluations_query);
        $stmt3->bind_param("i", $id);
        $stmt3->execute();
        
        // Step 4: Finally delete the activity itself
        $delete_activity_query = "DELETE FROM activites WHERE id = ?";
        $stmt4 = $conn->prepare($delete_activity_query);
        $stmt4->bind_param("i", $id);
        $stmt4->execute();
        
        // If we get here, all deletions were successful
        $conn->commit();
        
        header("Location: admin_activites.php?success=deleted");
        exit();
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        $error = "Erreur lors de la suppression: " . $e->getMessage();
    }
}

// Récupérer toutes les activités
$activites_query = "SELECT * FROM activites ORDER BY date_creation DESC";
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
        .purchase-info {
            background-color: #fff3cd;
            padding: 5px;
            border-radius: 4px;
            font-size: 0.9em;
            color: #856404;
        }
        .activity-stats {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <h1>Gestion des Activités</h1>
    
    <div class="nav-buttons">
        <a href="admin.php">Tableau de bord</a>
        <a href="../Testing grounds/main.php">Site utilisateur</a>
    </div>
    
    <?php if(isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
        <p class="success">L'activité et toutes ses données associées ont été supprimées avec succès.</p>
    <?php endif; ?>
    
    <?php if(isset($_GET['success']) && $_GET['success'] == 'tag_added'): ?>
        <p class="success">Le tag a été ajouté avec succès.</p>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <div class="add-tag-form" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
        <h3>Ajouter un nouveau tag</h3>
        <form method="POST" action="" class="tag-form">
            <div style="display: flex; gap: 10px; align-items: flex-end;">
                <div>
                    <label for="tag_name">Nom technique (sans espaces)</label>
                    <input type="text" id="tag_name" name="tag_name" required 
                           pattern="[a-z0-9_]+" style="padding: 5px; margin-top: 5px;"
                           title="Lettres minuscules, chiffres et underscore uniquement">
                </div>
                <div>
                    <label for="display_name">Nom d'affichage</label>
                    <input type="text" id="display_name" name="display_name" required 
                           style="padding: 5px; margin-top: 5px;">
                </div>
                <button type="submit" name="add_tag" style="padding: 8px 15px; background: #45a163; color: white; border: none; border-radius: 4px; cursor: pointer;">
                    Ajouter le tag
                </button>
            </div>
        </form>
    </div>

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
                <th>Créateur</th>
                <th>Données associées</th>
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
                
                // Get associated data counts
                $purchase_count_query = "SELECT COUNT(*) as count FROM activites_achats WHERE activite_id = " . $activite['id'];
                $purchase_result = $conn->query($purchase_count_query);
                $purchase_count = $purchase_result ? $purchase_result->fetch_assoc()['count'] : 0;
                
                $tags_count_query = "SELECT COUNT(*) as count FROM activity_tags WHERE activity_id = " . $activite['id'];
                $tags_result = $conn->query($tags_count_query);
                $tags_count = $tags_result ? $tags_result->fetch_assoc()['count'] : 0;
                
                $evaluations_count_query = "SELECT COUNT(*) as count FROM evaluations WHERE activite_id = " . $activite['id'];
                $evaluations_result = $conn->query($evaluations_count_query);
                $evaluations_count = $evaluations_result ? $evaluations_result->fetch_assoc()['count'] : 0;
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
                    <td>
                        <div class="activity-stats">
                            <?php if($purchase_count > 0): ?>
                                <div class="purchase-info">
                                    📦 <?php echo $purchase_count; ?> achat(s)
                                </div>
                            <?php endif; ?>
                            <div>🏷️ <?php echo $tags_count; ?> tag(s)</div>
                            <?php if($evaluations_count > 0): ?>
                                <div>⭐ <?php echo $evaluations_count; ?> évaluation(s)</div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="action-links">
                        <a href="admin_activites.php?delete=<?php echo $activite['id']; ?>" 
                           onclick="return confirm('⚠️ ATTENTION ⚠️\n\nCette action va supprimer :\n- L\'activité\n- <?php echo $purchase_count; ?> achat(s) associé(s)\n- <?php echo $tags_count; ?> tag(s) associé(s)\n- <?php echo $evaluations_count; ?> évaluation(s) associée(s)\n\nCette action est IRRÉVERSIBLE.\n\nÊtes-vous absolument sûr de vouloir continuer ?')"
                           style="background-color: #dc3545; color: white; padding: 5px 10px; border-radius: 3px; text-decoration: none;">
                            Supprimer
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
            <?php if (mysqli_num_rows($result) == 0): ?>
                <tr>
                    <td colspan="10">Aucune activité trouvée</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script>
    // Add this JavaScript to automatically format the tag name
    document.getElementById('tag_name').addEventListener('input', function(e) {
        this.value = this.value.toLowerCase()
                              .replace(/\s+/g, '_')
                              .replace(/[^a-z0-9_]/g, '');
    });

    // Auto-update display name if empty
    document.getElementById('tag_name').addEventListener('change', function(e) {
        const displayInput = document.getElementById('display_name');
        if(displayInput.value === '') {
            displayInput.value = this.value.replace(/_/g, ' ')
                                   .replace(/\b\w/g, l => l.toUpperCase());
        }
    });
    </script>
</body>
</html>