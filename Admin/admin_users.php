<?php
// Recherche de l'utilisateur connecté (id de session)
include 'adminVerify.php';

// Handle delete action
if(isset($_GET['delete']) && !empty($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Vérifier que l'utilisateur ne se supprime pas lui-même
    if($id == $_SESSION['user_id']) {
        $error = "Vous ne pouvez pas supprimer votre propre compte.";
    } else {
        // Vérifier si l'utilisateur à supprimer est un super admin
        $check_query = "SELECT user_type FROM user_form WHERE id = $id";
        $result = mysqli_query($conn, $check_query);
        $user = mysqli_fetch_assoc($result);
        
        // Si l'utilisateur est un super admin et que la personne connectée n'est pas super admin
        if($user['user_type'] == 1 && $_SESSION['user_type'] != 1) {
            $error = "Vous n'avez pas les droits pour supprimer un super administrateur.";
        } else {
            // Gérer les contraintes de clé étrangère - supprimer d'abord les messages
            // Supprimer les messages envoyés par l'utilisateur
            mysqli_query($conn, "DELETE FROM messages WHERE sender_id = $id");
            
            // Supprimer les messages reçus par l'utilisateur
            mysqli_query($conn, "DELETE FROM messages WHERE receiver_id = $id");
            
            // Supprimer les conversations impliquant l'utilisateur
            mysqli_query($conn, "DELETE FROM conversations WHERE user1_id = $id OR user2_id = $id");
            
            // Supprimer les infos de paiement
            mysqli_query($conn, "DELETE FROM payment_info WHERE user_id = $id");
            
            // Enfin, supprimer l'utilisateur
            $delete_query = "DELETE FROM user_form WHERE id = $id";
            if(mysqli_query($conn, $delete_query)) {
                header("Location: admin_users.php?success=deleted");
                exit();
            } else {
                $error = "Erreur lors de la suppression: " . mysqli_error($conn);
            }
        }
    }
}

// Handle change user type action (only for super admin)
if(isset($_GET['make_admin']) && !empty($_GET['make_admin']) && $_SESSION['user_type'] == 1) {
    $id = (int)$_GET['make_admin'];
    
    // Mettre à jour le type d'utilisateur à 2 (admin normal)
    $update_query = "UPDATE user_form SET user_type = 2 WHERE id = $id";
    if(mysqli_query($conn, $update_query)) {
        header("Location: admin_users.php?success=admin_added");
        exit();
    } else {
        $error = "Erreur lors de la mise à jour: " . mysqli_error($conn);
    }
}

// Handle remove admin action (only for super admin)
if(isset($_GET['remove_admin']) && !empty($_GET['remove_admin']) && $_SESSION['user_type'] == 1) {
    $id = (int)$_GET['remove_admin'];
    
    // Ne pas permettre de rétrograder un super admin
    $check_query = "SELECT user_type FROM user_form WHERE id = $id";
    $result = mysqli_query($conn, $check_query);
    $user = mysqli_fetch_assoc($result);
    
    if($user['user_type'] == 1) {
        $error = "Vous ne pouvez pas rétrograder un super admin.";
    } else {
        // Mettre à jour le type d'utilisateur à 0 (utilisateur normal)
        $update_query = "UPDATE user_form SET user_type = 0 WHERE id = $id";
        if(mysqli_query($conn, $update_query)) {
            header("Location: admin_users.php?success=admin_removed");
            exit();
        } else {
            $error = "Erreur lors de la mise à jour: " . mysqli_error($conn);
        }
    }
}

// Requête pour les super admins
$super_admin = "SELECT * FROM user_form WHERE user_type = 1";
$result_super_admin = $conn->query($super_admin);

// Requête pour les admins normaux
$admin = "SELECT * FROM user_form WHERE user_type = 2";
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
        </div>
        
        <?php if(isset($_GET['success'])): ?>
            <?php if($_GET['success'] == 'deleted'): ?>
                <p class="success">L'utilisateur a été supprimé avec succès.</p>
            <?php elseif($_GET['success'] == 'admin_added'): ?>
                <p class="success">L'utilisateur a été promu administrateur avec succès.</p>
            <?php elseif($_GET['success'] == 'admin_removed'): ?>
                <p class="success">Les droits d'administrateur ont été retirés avec succès.</p>
            <?php endif; ?>
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
                <!-- Affichage des super admins -->
                <tr>
                    <th colspan="7" class="section-header">Super Administrateurs <span class="user-count"><?php echo $result_super_admin->num_rows; ?></span></th>
                </tr>
                <?php if ($result_super_admin->num_rows == 0): ?>
                    <tr>
                        <td colspan="7">Aucun super administrateur trouvé</td>
                    </tr>
                <?php else: ?>
                    <?php while ($admin = $result_super_admin->fetch_assoc()): ?>
                        <tr <?php if($admin['id'] == $_SESSION['user_id']) echo 'class="you-row"'; ?>>
                            <td><?php echo $admin['id']; ?></td>
                            <td><?php echo htmlspecialchars($admin['name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td><?php echo htmlspecialchars($admin['phone_nb']); ?></td>
                            <td><?php echo isset($admin['created_at']) ? $admin['created_at'] : 'N/A'; ?></td>
                            <td>
                                <?php if($admin['id'] != $_SESSION['user_id'] && $_SESSION['user_type'] == 1): ?>
                                    <a href="admin_users.php?delete=<?php echo $admin['id']; ?>" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet administrateur?')">Supprimer</a>
                                <?php else: ?>
                                    <span style="color: #999;"><?php echo ($admin['id'] == $_SESSION['user_id']) ? '(Votre compte)' : '(Super Admin)'; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>

                <!-- Affichage des admins normaux -->
                <tr>
                    <th colspan="7" class="section-header">Administrateurs <span class="user-count"><?php echo $result_admin->num_rows; ?></span></th>
                </tr>
                <?php if ($result_admin->num_rows == 0): ?>
                    <tr>
                        <td colspan="7">Aucun administrateur trouvé</td>
                    </tr>
                <?php else: ?>
                    <?php while ($admin = $result_admin->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $admin['id']; ?></td>
                            <td><?php echo htmlspecialchars($admin['name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($admin['email']); ?></td>
                            <td><?php echo htmlspecialchars($admin['phone_nb']); ?></td>
                            <td><?php echo isset($admin['created_at']) ? $admin['created_at'] : 'N/A'; ?></td>
                            <td>
                                <a href="admin_users.php?delete=<?php echo $admin['id']; ?>" 
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet administrateur?')">Supprimer</a>
                                <?php if($_SESSION['user_type'] == 1): ?>
                                    <a href="admin_users.php?remove_admin=<?php echo $admin['id']; ?>" 
                                       onclick="return confirm('Êtes-vous sûr de vouloir retirer les droits d\'administrateur?')">Retirer admin</a>
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
                                <a href="admin_users.php?delete=<?php echo $user['id']; ?>" 
                                   onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur?')">Supprimer</a>
                                <?php if($_SESSION['user_type'] == 1): ?>
                                    <a href="admin_users.php?make_admin=<?php echo $user['id']; ?>"
                                       onclick="return confirm('Êtes-vous sûr de vouloir promouvoir cet utilisateur en administrateur?')">Promouvoir admin</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
</html>