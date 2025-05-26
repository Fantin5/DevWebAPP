<?php
    // Vérification de la session (rejeter si non admin)
    include 'adminVerify.php';
    
?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Panel</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .nav-button { 
            display: inline-block; 
            padding: 8px 15px; 
            margin-bottom: 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .nav-button:hover { background-color: #45a049; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Salle de contrôle</h1>
    <a href="../Testing grounds/main.php" class="nav-button">Retour à la page principale</a>
    
    <table>
        <tbody>
            <tr>
                <td><a href="admin_users.php">Gestion des utilisateurs</a></td>
            </tr>
            <tr>
                <td><a href="admin_activites.php">Gestion des activités</a></td>
            </tr>
          
            <tr>
                <td><a href="admin_faq.php">Gestion de la FAQ</a></td>
            </tr>
            <tr>
                <td><a href="admin_cgu.php">Gestion des CGU</a></td>
            </tr>
            <tr>
                <td><a href="admin_mentionslegales.php">Gestion des Mentions Légales</a></td>
            </tr>
        </tbody>
    </table>
</body>
</html>