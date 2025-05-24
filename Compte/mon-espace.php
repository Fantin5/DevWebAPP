<?php
// Start session
session_start();

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../Connexion-Inscription/login_form.php');
    exit();
}

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Échec de la connexion à la base de données: " . $conn->connect_error);
}

// Get user ID from session
$user_id = $_SESSION['user_id'];


// NOW ADD THE REQUIRE STATEMENT HERE (AFTER $user_id IS DEFINED)
require_once '../includes/newsletter_functions.php';

// Variable for messages
$message = '';
$message_type = '';

// Connect to activity database to count user activities
$conn_activity = new mysqli($servername, $username, $password, "activity");
if ($conn_activity->connect_error) {
    die("Échec de la connexion à la base de données activity: " . $conn_activity->connect_error);
}

// Count user activities
$activity_count = 0;
$sql_activities = "SELECT description FROM activites WHERE description LIKE '%<!--CREATOR:%'";
$result_activities = $conn_activity->query($sql_activities);

if ($result_activities && $result_activities->num_rows > 0) {
    while($row_activity = $result_activities->fetch_assoc()) {
        // Vérifier si la description contient le motif CREATOR
        if (isset($row_activity["description"]) && preg_match('/<!--CREATOR:(.*?)-->/', $row_activity["description"], $matches)) {
            // Extraire et décoder les données du créateur
            $creator_info = json_decode(base64_decode($matches[1]), true);
            if (isset($creator_info['user_id']) && $creator_info['user_id'] == $user_id) {
                $activity_count++;
            }
        }
    }
}

// Count user registered activities (purchased)
$registered_activity_count = 0;
$sql_registered = "SELECT COUNT(*) as count FROM activites_achats WHERE user_id = ?";
$stmt_registered = $conn_activity->prepare($sql_registered);
$stmt_registered->bind_param("i", $user_id);
$stmt_registered->execute();
$result_registered = $stmt_registered->get_result();

if ($result_registered && $result_registered->num_rows > 0) {
    $row_registered = $result_registered->fetch_assoc();
    $registered_activity_count = $row_registered['count'];
}

$stmt_registered->close();
$conn_activity->close();

// Handle profile update
if (isset($_POST['update_profile'])) {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['name']; 
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $birthday = $_POST['birthday'];
    
    // Format birthday from DD/MM/YYYY to YYYY-MM-DD for database
    if (!empty($birthday)) {
        $birthday_parts = explode('/', $birthday);
        if (count($birthday_parts) === 3) {
            $birthday = $birthday_parts[2] . '-' . $birthday_parts[1] . '-' . $birthday_parts[0];
        }
    }
    
    // Update user information
    $sql = "UPDATE user_form SET name = ?, email = ?, first_name = ?, birthday = ?, phone_nb = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $last_name, $email, $first_name, $birthday, $phone, $user_id);
    
    if ($stmt->execute()) {
        $message = "Votre profil a été mis à jour avec succès !";
        $message_type = "success";
        
        // Update session variables if needed
        $_SESSION['name'] = $last_name;
        $_SESSION['first_name'] = $first_name;
        $_SESSION['email'] = $email;
    } else {
        $message = "Erreur lors de la mise à jour de votre profil: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Handle newsletter subscription/unsubscription directly in this page
if (isset($_POST['newsletter_action'])) {
    $action = $_POST['newsletter_action'];
    $new_status = ($action === 'subscribe') ? 1 : 0;
    
    $sql = "UPDATE user_form SET newsletter_subscribed = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $new_status, $user_id);
    
    if ($stmt->execute()) {
        $message = ($action === 'subscribe') 
            ? "Vous êtes maintenant abonné à notre newsletter!"
            : "Vous vous êtes désabonné de notre newsletter.";
        $message_type = "success";
    } else {
        $message = "Erreur lors de la mise à jour de votre abonnement: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Handle account deletion
if (isset($_POST['delete_account']) && $_POST['delete_account'] === 'confirm') {
    // First delete related records in the messages table
    $sql_delete_messages = "DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?";
    $stmt_messages = $conn->prepare($sql_delete_messages);
    $stmt_messages->bind_param("ii", $user_id, $user_id);
    $stmt_messages->execute();
    $stmt_messages->close();
    
    // Then delete related records in the conversations table
    $sql_delete_conversations = "DELETE FROM conversations WHERE user1_id = ? OR user2_id = ?";
    $stmt_conversations = $conn->prepare($sql_delete_conversations);
    $stmt_conversations->bind_param("ii", $user_id, $user_id);
    $stmt_conversations->execute();
    $stmt_conversations->close();
    
    // Finally delete the user from the database
    $sql = "DELETE FROM user_form WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        // Destroy session
        session_destroy();
        
        // Redirect to a confirmation page or home page
        header('Location: ../Testing grounds/main.php?message=Compte supprimé avec succès');
        exit();
    } else {
        $message = "Erreur lors de la suppression de votre compte: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Get user information including newsletter subscription status
$sql = "SELECT * FROM user_form WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Utilisateur non trouvé.");
}

$user = $result->fetch_assoc();
$stmt->close();

// Format date for display
$formatted_birthday = "";
if (!empty($user['birthday']) && $user['birthday'] != '0000-00-00') {
    $birthday = new DateTime($user['birthday']);
    $formatted_birthday = $birthday->format('d/m/Y');
}

// Format phone number
function formatPhoneNumber($phone) {
    // If the number is empty, return "Non renseigné"
    if (empty($phone)) {
        return "Non renseigné";
    }
    
    // Format the phone number to string
    $phone = (string)$phone;
    
    // If the number starts with 0, keep it
    if (strlen($phone) == 9 && substr($phone, 0, 1) != '0') {
        $phone = '0' . $phone;
    }
    
    // Format XX XX XX XX XX
    if (strlen($phone) == 10) {
        return chunk_split($phone, 2, ' ');
    }
    
    // If the format doesn't match, return as is
    return $phone;
}

// Function to get user's selected tags (simplified version)
function getUserSelectedTagsSimple($userId) {
    $userConn = new mysqli("localhost", "root", "", "user_db");
    $activityConn = new mysqli("localhost", "root", "", "activity");
    
    if ($userConn->connect_error || $activityConn->connect_error) {
        return [];
    }
    
    try {
        $query = "SELECT unt.tag_id, td.display_name 
                  FROM user_newsletter_tags unt 
                  LEFT JOIN activity.tag_definitions td ON unt.tag_id = td.id 
                  WHERE unt.user_id = ?";
        
        $stmt = $userConn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $tags = [];
        while ($row = $result->fetch_assoc()) {
            if ($row['display_name']) {
                $tags[] = $row['display_name'];
            }
        }
        
        $stmt->close();
        $userConn->close();
        $activityConn->close();
        
        return $tags;
    } catch (Exception $e) {
        error_log("Error in getUserSelectedTagsSimple: " . $e->getMessage());
        return [];
    }
}

// Get user's selected tags for display (only if subscribed to newsletter)
$user_selected_tags = [];
$tag_count = 0;

if ($user['newsletter_subscribed'] == 1) {
    $user_selected_tags = getUserSelectedTagsSimple($user_id);
    $tag_count = count($user_selected_tags);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace | Synapse</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="../TEMPLATE/Nouveauhead.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #45a163;
            --secondary-color: #828977;
            --accent-color: #ff9f67;
            --danger-color: #e74c3c;
            --background-color: #f8f9fa;
            --card-background: #ffffff;
            --text-primary: #333333;
            --text-secondary: #666666;
            --border-radius: 15px;
            --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --transition-speed: 0.3s;
        }
        
        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .user-space-container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .page-title {
            text-align: center;
            color: var(--secondary-color);
            margin-bottom: 40px;
            font-size: 36px;
            font-weight: 700;
            position: relative;
        }
        
        .page-title:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: var(--primary-color);
            border-radius: 2px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .dashboard-card {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--box-shadow);
            transition: transform var(--transition-speed), box-shadow var(--transition-speed);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-card:hover {
            transform: translateY(-7px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
        }
        
        .dashboard-card:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }
        
        .dashboard-card:hover:before {
            width: 7px;
        }
        
        .dashboard-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .dashboard-card-icon {
            width: 56px;
            height: 56px;
            background-color: rgba(69, 161, 99, 0.12);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 18px;
            font-size: 24px;
            color: var(--primary-color);
            transition: all var(--transition-speed);
        }
        
        .dashboard-card:hover .dashboard-card-icon {
            background-color: var(--primary-color);
            color: white;
            transform: scale(1.05);
        }
        
        .dashboard-card-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
        }
        
        .user-info-section {
            margin-bottom: 25px;
        }
        
        .user-info-name {
            font-size: 22px;
            margin: 0 0 8px 0;
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .user-info-email {
            font-size: 16px;
            margin: 0;
            color: var(--text-secondary);
        }
        
        .user-info-item {
            margin: 18px 0;
        }
        
        .user-info-label {
            font-size: 14px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            display: block;
            font-weight: 500;
        }
        
        .user-info-value {
            font-size: 16px;
            color: var(--text-primary);
            padding: 12px 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 3px solid var(--primary-color);
        }
        
        .newsletter-status {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .status-indicator {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            margin-right: 12px;
            transition: transform 0.3s ease;
        }
        
        .status-active {
            background-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(69, 161, 99, 0.2);
        }
        
        .status-inactive {
            background-color: var(--danger-color);
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.2);
        }
        
        .newsletter-status-text {
            font-size: 16px;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .newsletter-button, .action-button {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            transition: all var(--transition-speed);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        .subscribe-button {
            background-color: var(--primary-color);
            color: white;
        }
        
        .subscribe-button:hover {
            background-color: #3abd7a;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(69, 161, 99, 0.3);
        }
        
        .unsubscribe-button {
            background-color: #f1f1f1;
            color: var(--text-secondary);
        }
        
        .unsubscribe-button:hover {
            background-color: #e1e1e1;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }
        
        .activities-count {
            font-size: 32px;
            color: var(--primary-color);
            font-weight: bold;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .activity-link {
            display: block;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
            padding: 10px 15px;
            border-radius: 8px;
            background-color: rgba(69, 161, 99, 0.08);
        }
        
        .activity-link:hover {
            color: #1e8746;
            background-color: rgba(69, 161, 99, 0.15);
            transform: translateX(5px);
        }
        
        .activity-link i {
            margin-right: 8px;
            transition: transform 0.2s;
        }
        
        .activity-link:hover i {
            transform: translateX(3px);
        }
        
        .message {
            padding: 18px 24px;
            border-radius: 12px;
            margin-bottom: 35px;
            display: flex;
            align-items: center;
            gap: 18px;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            0% {
                transform: translateY(-20px);
                opacity: 0;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .message i {
            font-size: 24px;
        }
        
        .message-content {
            flex: 1;
            font-size: 16px;
        }
        
        .message.success {
            background-color: rgba(69, 161, 99, 0.12);
            border-left: 5px solid var(--primary-color);
        }
        
        .message.success i {
            color: var(--primary-color);
        }
        
        .message.error {
            background-color: rgba(231, 76, 60, 0.12);
            border-left: 5px solid var(--danger-color);
        }
        
        .message.error i {
            color: var(--danger-color);
        }
        
        /* Delete Account Card */
        .delete-account-card {
            background-color: rgba(231, 76, 60, 0.05);
            border-radius: var(--border-radius);
            padding: 30px;
            margin-top: 40px;
            box-shadow: var(--box-shadow);
            border-left: 5px solid var(--danger-color);
        }
        
        .delete-account-title {
            color: var(--danger-color);
            font-size: 22px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .delete-account-warning {
            margin-bottom: 20px;
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        .delete-button {
            background-color: var(--danger-color);
            color: white;
        }
        
        .delete-button:hover {
            background-color: #c0392b;
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(231, 76, 60, 0.3);
        }
        
        /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }
        
        .modal {
            background: white;
            width: 90%;
            max-width: 500px;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            animation: modalIn 0.3s ease-out;
        }
        
        @keyframes modalIn {
            0% {
                transform: scale(0.8);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
        
        .modal-header {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .modal-title {
            color: var(--danger-color);
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .modal-icon {
            font-size: 50px;
            color: var(--danger-color);
            margin-bottom: 20px;
        }
        
        .modal-content {
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        .modal-actions {
            display: flex;
            justify-content: space-between;
            gap: 15px;
        }
        
        .modal-button {
            flex: 1;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .modal-cancel {
            background: #f1f1f1;
            color: var(--text-secondary);
        }
        
        .modal-cancel:hover {
            background: #e1e1e1;
        }
        
        .modal-confirm {
            background: var(--danger-color);
            color: white;
        }
        
        .modal-confirm:hover {
            background: #c0392b;
        }
        
        /* Additional styling for better visual appeal */
        .center-text {
            text-align: center;
        }
        
        .card-decoration {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-color);
            opacity: 0.1;
        }
        
        .card-decoration:before {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-color);
            opacity: 0.05;
        }
        
        /* Styles pour le formulaire de modification de profil */
        .edit-profile-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(5px);
        }
        
        .edit-profile-form {
            background: white;
            width: 90%;
            max-width: 600px;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
        }
        
        .form-title {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(69, 161, 99, 0.2);
            outline: none;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            margin-top: 30px;
        }
        
        .form-button {
            flex: 1;
            padding: 14px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }
        
        .form-cancel {
            background: #f1f1f1;
            color: var(--text-secondary);
        }
        
        .form-cancel:hover {
            background: #e1e1e1;
        }
        
        .form-submit {
            background: var(--primary-color);
            color: white;
        }
        
        .form-submit:hover {
            background: #3abd7a;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .user-space-container {
                padding: 15px;
            }
            
            .page-title {
                font-size: 28px;
            }
            
            .dashboard-card {
                padding: 25px;
            }
            
            .modal, .edit-profile-form {
                width: 95%;
                padding: 20px;
            }
            
            .form-actions {
                flex-direction: column;
            }
        }
        /* Account Switcher Styles */
.saved-account-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 10px;
    border-left: 3px solid var(--secondary-color);
    transition: all 0.3s ease;
}

.saved-account-item:hover {
    background-color: #f1f1f1;
    transform: translateX(3px);
}

.saved-account-item.current-account {
    border-left-color: var(--primary-color);
    background-color: rgba(69, 161, 99, 0.08);
}

.saved-account-info {
    flex: 1;
}

.account-name {
    font-weight: 600;
    color: var(--text-primary);
}

.account-email {
    font-size: 0.9em;
    color: var(--text-secondary);
}

.switch-button {
    padding: 6px 12px;
    background-color: var(--primary-color);
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.9em;
    margin: 0 10px;
    transition: all 0.2s;
}

.switch-button:hover {
    background-color: #3abd7a;
    transform: translateY(-2px);
}

.remove-account-button {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: none;
    background-color: #f1f1f1;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s;
}

.remove-account-button:hover {
    background-color: #e74c3c;
    color: white;
}

.current-label {
    font-size: 0.8em;
    background-color: var(--primary-color);
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    margin-right: 10px;
}

.switch-toggle {
    position: relative;
    display: flex;
    align-items: center;
    cursor: pointer;
}

.switch-toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: relative;
    display: inline-block;
    width: 50px;
    height: 24px;
    background-color: #ccc;
    border-radius: 34px;
    transition: .4s;
    margin-right: 10px;
}

.slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    border-radius: 50%;
    transition: .4s;
}

input:checked + .slider {
    background-color: var(--primary-color);
}

input:checked + .slider:before {
    transform: translateX(26px);
}

.toggle-label {
    color: var(--text-primary);
    font-weight: 500;
}
    </style>
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>

    <div class="user-space-container">
        <h1 class="page-title">Mon Espace</h1>
        
        <?php if (isset($message) && !empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
            <div class="message-content">
                <?php echo $message; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="dashboard-grid">
            <!-- User Profile Card -->
            <div class="dashboard-card">
                <div class="card-decoration"></div>
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <h2 class="dashboard-card-title">Mon Profil</h2>
                </div>
                
                <div class="user-info-section">
                    <h3 class="user-info-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['name']); ?></h3>
                    <p class="user-info-email"><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                
                <div class="user-info-item">
                    <span class="user-info-label">Date de naissance</span>
                    <div class="user-info-value">
                        <?php echo !empty($formatted_birthday) ? htmlspecialchars($formatted_birthday) : 'Non renseigné'; ?>
                    </div>
                </div>
                
                <div class="user-info-item">
                    <span class="user-info-label">Téléphone</span>
                    <div class="user-info-value">
                        <?php echo htmlspecialchars(formatPhoneNumber($user['phone_nb'])); ?>
                    </div>
                </div>
                
                <button id="edit-profile-btn" class="activity-link">
                    <i class="fa-solid fa-pen"></i> Modifier mon profil
                </button>
            </div>
            
      <div class="dashboard-card">
    <div class="card-decoration"></div>
    <div class="dashboard-card-header">
        <div class="dashboard-card-icon">
            <i class="fa-solid fa-envelope"></i>
        </div>
        <h2 class="dashboard-card-title">Newsletter</h2>
    </div>
    
    <div class="newsletter-status">
        <div class="status-indicator <?php echo $user['newsletter_subscribed'] ? 'status-active' : 'status-inactive'; ?>"></div>
        <div class="newsletter-status-text">
            <?php echo $user['newsletter_subscribed'] 
                ? 'Vous êtes abonné à notre newsletter' 
                : 'Vous n\'êtes pas abonné à notre newsletter'; ?>
        </div>
    </div>
    
    <?php if ($user['newsletter_subscribed']): ?>
        <div class="user-info-item">
            <span class="user-info-label">Préférences de tags</span>
            <div class="user-info-value">
                <?php if ($tag_count > 0): ?>
                    <strong><?php echo $tag_count; ?> tag(s) sélectionné(s):</strong><br>
                    <small><?php echo implode(', ', array_slice($user_selected_tags, 0, 3)); ?><?php echo $tag_count > 3 ? '...' : ''; ?></small>
                <?php else: ?>
                    <span style="color: #ff9f67;">⚠️ Aucun tag sélectionné - vous recevrez toutes les notifications</span>
                <?php endif; ?>
            </div>
        </div>
        
        <p style="font-size: 14px; color: #666; margin-bottom: 15px;">
            <?php if ($tag_count > 0): ?>
                Vous recevez des notifications uniquement pour les activités correspondant à vos préférences.
            <?php else: ?>
                Sans préférences définies, vous recevez toutes les notifications d'activités.
            <?php endif; ?>
            <br><strong>Note:</strong> Vous ne recevrez jamais de notifications pour vos propres activités.
        </p>
    <?php else: ?>
        <p>Abonnez-vous pour recevoir les informations sur nos nouvelles activités et offres exclusives.</p>
    <?php endif; ?>
    
    <form method="post" style="margin-bottom: 15px;">
        <?php if ($user['newsletter_subscribed']): ?>
        <input type="hidden" name="newsletter_action" value="unsubscribe">
        <button type="submit" class="newsletter-button unsubscribe-button">
            <i class="fa-solid fa-bell-slash"></i> Se désabonner
        </button>
        <?php else: ?>
        <input type="hidden" name="newsletter_action" value="subscribe">
        <button type="submit" class="newsletter-button subscribe-button">
            <i class="fa-solid fa-bell"></i> S'abonner
        </button>
        <?php endif; ?>
    </form>
    
    <?php if ($user['newsletter_subscribed']): ?>
    <!-- Tag preferences section -->
    <div class="newsletter-preferences">
        <a href="../newsletter_tags.php" class="activity-link" style="margin-bottom: 10px;">
            <i class="fa-solid fa-tags"></i> 🏷️ Gérer mes préférences de tags
        </a>
        <p style="font-size: 12px; color: #666; margin: 0;">
            Choisissez les types d'activités qui vous intéressent pour recevoir des notifications personnalisées.
        </p>
    </div>
    <?php endif; ?>
</div>
            <!-- My Activities Card -->
            <div class="dashboard-card">
                <div class="card-decoration"></div>
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <h2 class="dashboard-card-title">Mes Activités</h2>
                </div>
                
                <div class="activities-count"><?php echo $activity_count; ?></div>
                <p class="center-text">Activités créées par vous</p>
                
                <a href="../Testing grounds/mes-activites.php" class="activity-link">
                    <i class="fa-solid fa-list"></i> Voir mes activités
                </a>
                
                <a href="../Testing grounds/jenis.php" class="activity-link">
                    <i class="fa-solid fa-plus"></i> Créer une nouvelle activité
                </a>
            </div>
            
            <!-- My Registered Activities Card -->
            <div class="dashboard-card">
                <div class="card-decoration"></div>
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <h2 class="dashboard-card-title">Mes Inscriptions</h2>
                </div>
                
                <div class="activities-count"><?php echo $registered_activity_count; ?></div>
                <p class="center-text">Activités auxquelles vous êtes inscrit</p>
                
                <a href="../Testing grounds/mes-activites-registered.php" class="activity-link">
                    <i class="fa-solid fa-list"></i> Voir mes inscriptions
                </a>
                
                <a href="../Testing grounds/activites.php" class="activity-link">
                    <i class="fa-solid fa-compass"></i> Explorer d'autres activités
                </a>
            </div>
                <div class="dashboard-card">
            <div class="card-decoration"></div>
            <div class="dashboard-card-header">
                <div class="dashboard-card-icon">
                    <i class="fa-solid fa-star"></i>
                </div>
                <h2 class="dashboard-card-title">Mes Avis</h2>
            </div>
            
            <div class="activities-count" id="reviews-count">0</div>
            <p class="center-text">Avis laissés par vous</p>
            
            <a href="../Testing grounds/mes_avis_page.php" class="activity-link">
                <i class="fa-solid fa-comments"></i> Voir mes avis
            </a>
            
            <a href="../Testing grounds/activites_a_evaluer_page.php" class="activity-link">
                <i class="fa-solid fa-pen-to-square"></i> Activités à évaluer
            </a>
        </div>
                
            <!-- My Cart Card -->
            <div class="dashboard-card">
                <div class="card-decoration"></div>
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <h2 class="dashboard-card-title">Mon Panier</h2>
                </div>
                
                <div class="activities-count">
                    <span id="cart-count">0</span>
                </div>
                <p class="center-text">Activités dans votre panier</p>
                
                <a href="../Testing grounds/panier.php" class="activity-link">
                    <i class="fa-solid fa-basket-shopping"></i> Voir mon panier
                </a>
            </div>
        </div>
      <!--message card -->
<div class="dashboard-card">
    <div class="card-decoration"></div>
    <div class="dashboard-card-header">
        <div class="dashboard-card-icon">
            <i class="fa-solid fa-comments"></i>
        </div>
        <h2 class="dashboard-card-title">Messagerie</h2>
    </div>
    <div class="activities-count">
        <span id="conversations-count">0</span>
    </div>
    <p class="center-text">Conversations actives</p>
    
    <a href="../Messagerie/messagerie.php" class="activity-link">
        <i class="fa-solid fa-envelope"></i> Accéder à ma messagerie
    </a>
    
    <a href="../Messagerie/messagerie.php?new=1" class="activity-link">
        <i class="fa-solid fa-plus"></i> Nouvelle conversation
    </a>
</div>
<!-- Account Switcher Card -->
<div class="dashboard-card">
    <div class="card-decoration"></div>
    <div class="dashboard-card-header">
        <div class="dashboard-card-icon">
            <i class="fa-solid fa-users-gear"></i>
        </div>
        <h2 class="dashboard-card-title">Changer de compte</h2>
    </div>
    
    <div id="saved-accounts-container">
        <p class="center-text">Comptes enregistrés</p>
        <div id="saved-accounts-list" class="user-info-item">
            <!-- Saved accounts will be populated here via JavaScript -->
            <p class="center-text" id="no-accounts-message">Aucun compte enregistré</p>
        </div>
    </div>

    <div class="user-info-item">
        <label class="switch-toggle">
            <input type="checkbox" id="remember-account-toggle">
            <span class="slider"></span>
            <span class="toggle-label">Mémoriser ce compte</span>
        </label>
    </div>
    
    <a href="../Connexion-Inscription/login_form.php" class="activity-link">
        <i class="fa-solid fa-user-plus"></i> Se connecter à un autre compte
    </a>
</div>
        <!-- Delete Account Section -->
        <div class="delete-account-card">
            <h2 class="delete-account-title">
                <i class="fa-solid fa-triangle-exclamation"></i> Supprimer mon compte
            </h2>
            <p class="delete-account-warning">
                Attention, cette action est irréversible. Toutes vos données personnelles seront supprimées définitivement de notre base de données. Vous perdrez l'accès à votre compte et à toutes vos activités.
            </p>
            <button id="open-delete-modal" class="action-button delete-button">
                <i class="fa-solid fa-trash"></i> Supprimer mon compte
            </button>
        </div>
    </div>
    
    <!-- Edit Profile Modal -->
    <div id="edit-profile-modal" class="edit-profile-modal">
        <div class="edit-profile-form">
            <h3 class="form-title">Modifier mon profil</h3>
            <form method="post" action="">
                <div class="form-group">
                    <label class="form-label" for="first_name">Prénom</label>
                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="name">Nom</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="phone">Téléphone</label>
                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone_nb']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="birthday">Date de naissance (JJ/MM/AAAA)</label>
                    <input type="text" class="form-control" id="birthday" name="birthday" value="<?php echo $formatted_birthday; ?>" placeholder="JJ/MM/AAAA">
                </div>
                
                <div class="form-actions">
                    <button type="button" class="form-button form-cancel" id="cancel-edit-profile">Annuler</button>
                    <button type="submit" class="form-button form-submit" name="update_profile">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Account Confirmation Modal -->
    <div id="delete-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <div class="modal-icon">
                    <i class="fa-solid fa-trash"></i>
                </div>
                <h3 class="modal-title">Confirmation de suppression</h3>
            </div>
            <div class="modal-content">
                <p>Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible et toutes vos données seront définitivement effacées.</p>
            </div>
            <div class="modal-actions">
                <button id="cancel-delete" class="modal-button modal-cancel">Annuler</button>
                <form method="post" style="flex: 1;">
                    <input type="hidden" name="delete_account" value="confirm">
                    <button type="submit" class="modal-button modal-confirm">Supprimer définitivement</button>
                </form>
            </div>
        </div>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>

    <script>
// Complete JS with all functionality for mon-espace.php
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart if it doesn't exist
    if (!localStorage.getItem('synapse-cart')) {
        localStorage.setItem('synapse-cart', JSON.stringify([]));
    }
    
    // Update cart count
    const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
    
    // Update in navigation
    const cartCount = document.getElementById('panier-count');
    if (cartCount) {
        cartCount.textContent = cart.length;
    }
    
    // Update in dashboard
    const dashboardCartCount = document.getElementById('cart-count');
    if (dashboardCartCount) {
        dashboardCartCount.textContent = cart.length;
    }
    
    // Edit profile modal functionality
    const editProfileModal = document.getElementById('edit-profile-modal');
    const openEditProfileBtn = document.getElementById('edit-profile-btn');
    const cancelEditProfileBtn = document.getElementById('cancel-edit-profile');
    
    if (openEditProfileBtn) {
        openEditProfileBtn.addEventListener('click', function() {
            editProfileModal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        });
    }
    
    if (cancelEditProfileBtn) {
        cancelEditProfileBtn.addEventListener('click', function() {
            editProfileModal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Re-enable scrolling
        });
    }
    
    // Close modal when clicking outside
    if (editProfileModal) {
        editProfileModal.addEventListener('click', function(event) {
            if (event.target === editProfileModal) {
                editProfileModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }
    
    // Format birthday input
    const birthdayInput = document.getElementById('birthday');
    if (birthdayInput) {
        birthdayInput.addEventListener('blur', function() {
            // If input is empty, do nothing
            if (!this.value) return;
            
            // Try to format the date (assuming input is DD/MM/YYYY)
            const parts = this.value.split(/[\/\-\.]/);
            
            if (parts.length === 3) {
                let day = parts[0].padStart(2, '0');
                let month = parts[1].padStart(2, '0');
                let year = parts[2];
                
                // If year is only 2 digits, assume 21st century
                if (year.length === 2) {
                    year = '20' + year;
                }
                
                this.value = `${day}/${month}/${year}`;
            }
            // Update reviews count
function updateReviewsCount() {
    fetch('review_system.php?action=get_user_reviews_count', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const countElement = document.getElementById('reviews-count');
            if (countElement) {
                countElement.textContent = data.count;
            }
        }
    })
    .catch(error => {
        console.error('Error fetching reviews count:', error);
    });
}

// Call it once on page load
updateReviewsCount();
        });
    }
    
    // Delete account modal functionality
    const deleteModal = document.getElementById('delete-modal');
    const openModalBtn = document.getElementById('open-delete-modal');
    const cancelDeleteBtn = document.getElementById('cancel-delete');
    
    if (openModalBtn) {
        openModalBtn.addEventListener('click', function() {
            deleteModal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        });
    }
    
    if (cancelDeleteBtn) {
        cancelDeleteBtn.addEventListener('click', function() {
            deleteModal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Re-enable scrolling
        });
    }
    
    // Close modal when clicking outside
    if (deleteModal) {
        deleteModal.addEventListener('click', function(event) {
            if (event.target === deleteModal) {
                deleteModal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }
    
    // Fade out message after 5 seconds
    const message = document.querySelector('.message');
    if (message) {
        setTimeout(function() {
            message.style.opacity = '0';
            message.style.transition = 'opacity 0.5s ease';
            setTimeout(function() {
                message.style.display = 'none';
            }, 500);
        }, 5000);
    }
    
    // Add animations for dashboard cards
    const cards = document.querySelectorAll('.dashboard-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease, box-shadow 0.3s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, 100 * index);
    });
    
    // Account Switcher Functionality
    const savedAccountsList = document.getElementById('saved-accounts-list');
    const noAccountsMessage = document.getElementById('no-accounts-message');
    const rememberAccountToggle = document.getElementById('remember-account-toggle');
    
    if (savedAccountsList && noAccountsMessage && rememberAccountToggle) {
        // Load saved accounts from localStorage
        function loadSavedAccounts() {
            const savedAccounts = JSON.parse(localStorage.getItem('synapse-saved-accounts')) || [];
            const currentUserId = <?php echo $user_id; ?>;
            
            if (savedAccounts.length === 0) {
                noAccountsMessage.style.display = 'block';
                return;
            }
            
            noAccountsMessage.style.display = 'none';
            savedAccountsList.innerHTML = '';
            
            savedAccounts.forEach(account => {
                const accountElement = document.createElement('div');
                accountElement.className = 'saved-account-item';
                
                const isCurrentAccount = (account.id == currentUserId);
                
                accountElement.innerHTML = `
                    <div class="saved-account-info ${isCurrentAccount ? 'current-account' : ''}">
                        <div class="account-name">${account.first_name} ${account.name}</div>
                        <div class="account-email">${account.email}</div>
                    </div>
                    ${isCurrentAccount ? 
                        '<span class="current-label">Actuel</span>' : 
                        '<button class="switch-button" data-id="' + account.id + '" data-email="' + account.email + '">Connecter</button>'}
                    <button class="remove-account-button" data-id="${account.id}">
                        <i class="fa-solid fa-times"></i>
                    </button>
                `;
                
                savedAccountsList.appendChild(accountElement);
            });
            
            // Add event listeners to switch and remove buttons
            document.querySelectorAll('.switch-button').forEach(button => {
                button.addEventListener('click', function() {
                    switchToAccount(this.dataset.id, this.dataset.email);
                });
            });
            
            document.querySelectorAll('.remove-account-button').forEach(button => {
                button.addEventListener('click', function() {
                    removeAccount(this.dataset.id);
                });
            });
            
            // Check if current account is saved
            const currentAccountSaved = savedAccounts.some(account => account.id == currentUserId);
            rememberAccountToggle.checked = currentAccountSaved;
        }
        
        // Switch to another account
        function switchToAccount(userId, email) {
            // Create a form to post to login procedure
            const form = document.createElement('form');
            form.method = 'post';
            // Use the correct path to account_switch.php
            form.action = '../Compte/account_switch.php';
            form.style.display = 'none';
            
            const userIdInput = document.createElement('input');
            userIdInput.type = 'hidden';
            userIdInput.name = 'switch_user_id';
            userIdInput.value = userId;
            
            const emailInput = document.createElement('input');
            emailInput.type = 'hidden';
            emailInput.name = 'email';
            emailInput.value = email;
            
            form.appendChild(userIdInput);
            form.appendChild(emailInput);
            document.body.appendChild(form);
            form.submit();
        }
        
        // Remove account from saved list
        function removeAccount(userId) {
            let savedAccounts = JSON.parse(localStorage.getItem('synapse-saved-accounts')) || [];
            savedAccounts = savedAccounts.filter(account => account.id != userId);
            localStorage.setItem('synapse-saved-accounts', JSON.stringify(savedAccounts));
            
            loadSavedAccounts();
        }
        
        // Save current account to localStorage
        function saveCurrentAccount(save) {
            const currentUser = {
                id: <?php echo $user_id; ?>,
                name: "<?php echo addslashes($user['name']); ?>",
                first_name: "<?php echo addslashes($user['first_name']); ?>",
                email: "<?php echo addslashes($user['email']); ?>"
            };
            
            let savedAccounts = JSON.parse(localStorage.getItem('synapse-saved-accounts')) || [];
            
            if (save) {
                // Check if account already exists
                const existingIndex = savedAccounts.findIndex(account => account.id == currentUser.id);
                
                if (existingIndex === -1) {
                    savedAccounts.push(currentUser);
                } else {
                    // Update existing account info
                    savedAccounts[existingIndex] = currentUser;
                }
            } else {
                // Remove account from saved list
                savedAccounts = savedAccounts.filter(account => account.id != currentUser.id);
            }
            
            localStorage.setItem('synapse-saved-accounts', JSON.stringify(savedAccounts));
            loadSavedAccounts();
        }
        
        // Toggle account saving
        rememberAccountToggle.addEventListener('change', function() {
            saveCurrentAccount(this.checked);
        });
        
        // Initial load of saved accounts
        loadSavedAccounts();
    }
});

// Update conversation count
function updateConversationCount() {
    fetch('../Messagerie/message_api.php?action=get_conversation_count')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const countElement = document.getElementById('conversations-count');
            if (countElement) {
                countElement.textContent = data.count;
            }
        }
    })
    .catch(error => {
        console.error('Error fetching conversation count:', error);
    });
}

// Call it once on page load
updateConversationCount();
    </script>
</body>
</html>

<?php
$conn->close();
?>
<!-- cvq -->