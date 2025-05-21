<?php
// Recherche de l'utilisateur connecté (id de session)
include 'adminVerify.php';

// Configuration de la base de données est déjà incluse dans adminVerify.php
// $conn est disponible via adminVerify.php

// Handle delete action
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Vérifier que l'utilisateur ne se supprime pas lui-même
    if($id == $_SESSION['user_id']) {
        $error = "Vous ne pouvez pas supprimer votre propre compte.";
    } else {
        $delete_query = "DELETE FROM user_form WHERE id = $id";
        if(mysqli_query($conn, $delete_query)) {
            header("Location: admin_users.php?success=deleted");
            exit();
        } else {
            $error = "Erreur lors de la suppression: " . mysqli_error($conn);
        }
    }
}

// Requête pour les admins
$admin = "SELECT * FROM user_form WHERE user_type = 1";
$result_admin = $conn->query($admin);

// Requête pour les utilisateurs lambdas
$users = "SELECT * FROM user_form WHERE user_type = 0";
$result_users = $conn->query($users);
?>
<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gestion des Utilisateurs</title>
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
            table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
            th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
            th { background-color: #f2f2f2; }
            .success { color: green; margin-bottom: 15px; padding: 10px; background-color: #dff0d8; border-radius: 4px; }
            .error { color: red; margin-bottom: 15px; padding: 10px; background-color: #f2dede; border-radius: 4px; }
            .action-links a { margin-right: 10px; text-decoration: none; }
            .section-header { 
                background-color: #333; 
                color: white; 
                padding: 10px; 
                font-weight: bold; 
                text-align: center; 
            }
            .you-row { background-color: #fffde7; }
            .user-count { 
                display: inline-block;
                background-color: #4CAF50;
                color: white;
                border-radius: 50%;
                width: 24px;
                height: 24px;
                text-align: center;
                line-height: 24px;
                margin-left: 8px;
            }
        </style>
    </head>
    <body>
        <h1>Gestion des Utilisateurs</h1>
        
        <div class="nav-buttons">
            <a href="admin.php">Tableau de bord</a>
            <a href="../Testing grounds/main.php">Site utilisateur</a>
            <a href="create_user.php">Ajouter un utilisateur</a>
        </div>
        
        <?php if(isset($_GET['success']) && $_GET['success'] == 'deleted'): ?>
            <p class="success">L'utilisateur a été supprimé avec succès.</p>
        <?php endif; ?>
        
        <?php if(isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Téléphone</th>
                    <th>Date d'inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Affichage des admins -->
                <tr>
                    <th colspan="7" class="section-header">Administrateurs <span class="user-count"><?php echo $result_admin->num_rows; ?></span></th>
                </tr>
                <?php if ($result_admin->num_rows == 0): ?>
                    <tr>
                        <td colspan="7">Aucun administrateur trouvé</td>
                    </tr>
                <?php else: ?>
                    <?php while ($admin = $result_admin->fetch_assoc()): ?>
                        <tr <?php if($admin['id'] == $_SESSION['user_id']) echo 'class="you-row"'; ?>>
                            <td><?php echo $admin['id']; ?></td>
                            <td><?php echo htmlspecialchars($admin['name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td><?php echo htmlspecialchars($admin['phone_nb']); ?></td>
                            <td><?php echo isset($admin['created_at']) ? $admin['created_at'] : 'N/A'; ?></td>
                            <td>
                                <a href="edit_user.php?id=<?php echo $admin['id']; ?>">Modifier</a>
                                <?php if($admin['id'] != $_SESSION['user_id']): ?>
                                    <a href="admin_users.php?delete=<?php echo $admin['id']; ?>" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet administrateur?')">Supprimer</a>
                                <?php else: ?>
                                    <span style="color: #999;">(Votre compte)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>

                <!-- Affichage des utilisateurs lambdas -->
                <tr>
                    <th colspan="7" class="section-header">Utilisateurs <span class="user-count"><?php echo $result_users->num_rows; ?></span></th>
                </tr>
                <?php if ($result_users->num_rows == 0): ?>
                    <tr>
                        <td colspan="7">Aucun utilisateur trouvé</td>
                    </tr>
                <?php else: ?>
                    <?php while ($user = $result_users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone_nb']); ?></td>
                            <td><?php echo isset($user['created_at']) ? $user['created_at'] : 'N/A'; ?></td>
                            <td>
                                <a href="edit_user.php?id=<?php echo $user['id']; ?>">Modifier</a>
                                <a href="admin_users.php?delete=<?php echo $user['id']; ?>" 
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur?')">Supprimer</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
</html>