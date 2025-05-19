<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Panel</title>
</head>
<body>
    <h1>Salle de contrôle</h1>
    <?php
    session_start();
    // Configuration de la base de données
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "activity";

    if (!(isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1)) {
        header("Location: login.php");
        exit();
    }
    // Créer une connexion
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Connect to user database
    $user_conn = new mysqli($servername, $username, $password, "user_db");

    // Connect to user database
    $user_conn = new mysqli($servername, $username, $password, "user_db");
    $admin = "SELECT * FROM user_form WHERE user_type = 1";
    $result = $user_conn->query($admin);
    $users = "SELECT * FROM user_form WHERE user_type = 0";
    $result_users = $user_conn->query($users);
    $user = $result->fetch_assoc();
    

    ?>

    <table>
        <tbody>
            <tr>
                <td><a href="admin.php">Gestion des utilisateurs</a></td>
            </tr>
            <tr>
                <td><a href="admin_activites.php">Gestion des activités</a></td>
            </tr>
            <tr>
                <td><a href="admin_avis.php">Gestion des avis</a></td>
            </tr>
            <tr>
                <td><a href="admin_messages.php">Gestion des messages</a></td>
            </tr>
        </tbody>
    </table>
</body>
</html>