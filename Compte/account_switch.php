<?php
// Start session
session_start();

// Include the database configuration file with the correct path
include '../Connexion-Inscription/config.php';    // Adjust this path if needed

// Check if connection is established
if (!isset($conn) || $conn->connect_error) {
    $_SESSION['switch_error'] = 'Erreur de connexion à la base de données';
    header('Location: ../Connexion-Inscription/login_form.php');
    exit();
}

// Redirect if not receiving data from form
if (!isset($_POST['switch_user_id']) || !isset($_POST['email'])) {
    header('Location: ../Connexion-Inscription/login_form.php');
    exit();
}

// Use prepared statements instead of mysqli_real_escape_string
$user_id = $_POST['switch_user_id'];
$email = $_POST['email'];

// Verify if the user exists and is verified
$select = "SELECT * FROM user_form WHERE id = ? AND email = ? AND email_verified = 1";
$stmt = $conn->prepare($select);
$stmt->bind_param("is", $user_id, $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_array();
    
    // Set the session variables
    $_SESSION['user_id'] = $row['id'];
    $_SESSION['user_name'] = $row['name'];
    $_SESSION['user_first_name'] = $row['first_name']; 
    $_SESSION['user_email'] = $row['email'];
    $_SESSION['user_type'] = $row['user_type'];
    $_SESSION['logged_in'] = true;
    
    // Redirect to dashboard
    header('Location: mon-espace.php');
    exit();
} else {
    // If user not found, redirect to login
    $_SESSION['switch_error'] = 'Impossible de changer de compte. Veuillez vous connecter manuellement.';
    header('Location: ../Connexion-Inscription/login_form.php');
    exit();
}
?>