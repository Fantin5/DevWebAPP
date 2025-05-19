<?php
    session_start();
    // Configuration de la base de données
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "user_db";


    // Connexion à la base de données
    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if (!$conn) {
        die("Connection failed: " . mysqli_connect_error());
    }
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../Testing grounds/main.php");
        exit();
    }
    // Récupération de l'ID de l'utilisateur connecté
    $user_id = $_SESSION['user_id'];

    // Requête pour récupérer les informations de l'utilisateur
    $query = "SELECT * FROM user_form WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_type'] = $row['u_type'];
    } else {
        echo "Utilisateur non trouvé.";
    }

    // Vérification de la session (rejeter si non admin)
    if ($_SESSION['user_type'] != 1) {
        header("Location: ../Testing grounds/main.php");
        exit();
    }

?>