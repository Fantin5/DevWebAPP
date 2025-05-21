<?php
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
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


// Vérification de la connexion de l'utilisateur

    // Liste des pages admin
    $admin_pages = [
        'admin.php',
        'admin_users.php',
        'admin_activites.php',
        'admin_avis.php',
        'admin_faq.php',
        'admin_cgu.php',
        'admin_mentionslegales.php',
        'admin_messages.php',
        'adminVerify.php'
    ];
    // Obtenir la page actuelle (uniquement le nom du fichier)
    $current_page = basename($_SERVER['PHP_SELF']);
    // Pour visualiser la page actuelle si besoin de debug : echo $current_page;
    // Si l'utilisateur n'est pas connecté
    if (!isset($_SESSION['user_id'])) {
        // Si l'utilisateur essaye d'accéder à une page admin
        if (in_array($current_page, $admin_pages)) {
            // Rediriger vers la page d'accueil
            header("Location: ../Testing grounds/main.php");
            exit();
        }

    // Si l'utilisateur est connecté
    } else {

// Vérification de la session (admin ou non)

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
            $_SESSION['user_type'] = $row['user_type'];
        } else {
            echo "Utilisateur non trouvé.";
            exit();
        }

        // Vérification de la session (rejeter si ni super admin ni admin)
        if ($_SESSION['user_type'] != 1 && $_SESSION['user_type'] != 2) {
            // Si l'utilisateur essaye d'accéder à une page admin
            if (in_array($current_page, $admin_pages)) {
                // Rediriger vers la page d'accueil
                header("Location: ../Testing grounds/main.php");
                exit();
            }
        }
    }

?>