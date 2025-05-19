<?php
// Recherche de l'utilisateur connecté (id de session)
include 'adminVerify.php';

// Requête pour les admins
$admin = "SELECT * FROM user_form WHERE u_type = 1";
$result_admin = $conn->query($admin);

// Requête pour les utilisateurs lambdas
$users = "SELECT * FROM user_form WHERE u_type = 0";
$result_users = $conn->query($users);

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Document</title>
    </head>
    <body>
        <table border="1">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Type</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Affichage des admins -->
                <tr>
                    <th colspan="4">Admins</th>
                </tr>
                <?php while ($admin = $result_admin->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $admin['id']; ?></td>
                        <td><?php echo $admin['name']; ?></td>
                        <td><?php echo $admin['email']; ?></td>
                        <td>Admin</td>
                    </tr>
                <?php } ?>

                <!-- Affichage des utilisateurs lambdas -->
                <tr>
                    <th colspan="4">Utilisateurs Lambdas</th>
                </tr>
                <?php while ($user = $result_users->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['name']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td>Utilisateur</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </body>
    </html>
