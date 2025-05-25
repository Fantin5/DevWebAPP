<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../Connexion-Inscription/login_form.php');
    exit();
}

require_once 'tag_setup.php';

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "activity";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
$user_conn = new mysqli($servername, $username, $password, "user_db");

// Check connection
if ($conn->connect_error) {
    die("Échec de la connexion à la base de données: " . $conn->connect_error);
}

// Initialize TagManager
$tagManager = new TagManager($conn);

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle review update
if (isset($_POST['update_review'])) {
    $review_id = $_POST['review_id'];
    $new_rating = $_POST['rating'];
    $new_comment = $_POST['comment'];
    
    $update_sql = "UPDATE evaluations SET note = ?, commentaire = ?, updated_at = NOW() WHERE id = ? AND utilisateur_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("isii", $new_rating, $new_comment, $review_id, $user_id);
    
    if ($update_stmt->execute()) {
        $message = "Votre avis a été mis à jour avec succès !";
        $message_type = "success";
    } else {
        $message = "Erreur lors de la mise à jour de votre avis.";
        $message_type = "error";
    }
    $update_stmt->close();
}

// Handle review deletion
if (isset($_POST['delete_review'])) {
    $review_id = $_POST['review_id'];
    
    $delete_sql = "DELETE FROM evaluations WHERE id = ? AND utilisateur_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $review_id, $user_id);
    
    if ($delete_stmt->execute()) {
        $message = "Votre avis a été supprimé avec succès.";
        $message_type = "success";
    } else {
        $message = "Erreur lors de la suppression de votre avis.";
        $message_type = "error";
    }
    $delete_stmt->close();
}

// Get user's reviews with activity information
$reviews_sql = "SELECT e.*, a.titre as activity_title, a.image_url, a.date_ou_periode
                FROM evaluations e 
                JOIN activites a ON e.activite_id = a.id
                WHERE e.utilisateur_id = ? 
                ORDER BY e.date_evaluation DESC";

$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $user_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

$user_reviews = [];
while ($row = $reviews_result->fetch_assoc()) {
    // Get tags for this activity
    $tagsResult = $tagManager->getActivityTags($row['activite_id']);
    $tags = [];
    while ($tagRow = $tagsResult->fetch_assoc()) {
        $tags[] = $tagRow;
    }
    $row['activity_tags'] = $tags;
    $user_reviews[] = $row;
}

$reviews_stmt->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Avis | Synapse</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="../TEMPLATE/Nouveauhead.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Nature-inspired variables */
        :root {
            --primary-color: #3c8c5c;
            --primary-light: #61b980;
            --primary-dark: #275e3e;
            --secondary-color: #946b2d;
            --secondary-light: #c89e52;
            --accent-color: #e9c46a;
            --text-dark: #2d5a3d;
            --danger-color: #e74c3c;
            --bg-gradient: linear-gradient(135deg, #f8fff9 0%, #f0f7f2 100%);
            --glass-bg: rgba(255, 255, 255, 0.9);
            --glass-border: rgba(255, 255, 255, 0.8);
        }

        /* Enhanced body background */
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow-x: hidden;
            min-height: 100vh;
            background: var(--bg-gradient);
            background-image: 
                radial-gradient(circle at 20% 20%, rgba(69, 161, 99, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(233, 196, 106, 0.1) 0%, transparent 50%);
        }

        .reviews-container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
            position: relative;
            z-index: 2;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }

        .page-title {
            color: var(--text-dark);
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 10px;
            font-family: 'Georgia', serif;
            position: relative;
            display: inline-block;
        }

        .page-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--accent-color));
            border-radius: 2px;
        }

        .page-subtitle {
            color: #6b7280;
            font-size: 18px;
            margin-top: 20px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 16px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.4s ease;
            margin-bottom: 30px;
            box-shadow: 0 12px 25px rgba(39, 94, 62, 0.3);
            position: relative;
            overflow: hidden;
        }

        .back-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 0) 0%, 
                rgba(255, 255, 255, 0.2) 50%, 
                rgba(255, 255, 255, 0) 100%);
            transform: skewX(-25deg);
            transition: left 0.7s ease;
            z-index: 1;
        }

        .back-button:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(39, 94, 62, 0.4);
        }

        .back-button:hover::before {
            left: 100%;
        }

        .back-button i, .back-button span {
            position: relative;
            z-index: 2;
        }

        /* Navigation tabs */
        .activities-tabs {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
            flex-wrap: wrap;
        }

        .tab-button {
            padding: 15px 30px;
            background-color: rgba(255, 255, 255, 0.7);
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #666;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: none;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .tab-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 0) 0%, 
                rgba(69, 161, 99, 0.1) 50%, 
                rgba(255, 255, 255, 0) 100%);
            transform: skewX(-25deg);
            transition: left 0.5s ease;
            z-index: -1;
        }

        .tab-button.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 10px 25px rgba(69, 161, 99, 0.3);
        }

        .tab-button:hover:not(.active) {
            background-color: rgba(255, 255, 255, 0.9);
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .tab-button:hover::before {
            left: 100%;
        }

        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 2rem;
            animation: fadeIn 1s forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .review-card {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s ease;
            border-left: 4px solid var(--primary-color);
            position: relative;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            animation: cardAppear 0.6s ease-out forwards;
            opacity: 0;
            transform: translateY(30px);
            cursor: pointer;
        }

        @keyframes cardAppear {
            to { opacity: 1; transform: translateY(0); }
        }

        .review-card:nth-child(3n+1) { animation-delay: 0.1s; }
        .review-card:nth-child(3n+2) { animation-delay: 0.2s; }
        .review-card:nth-child(3n+3) { animation-delay: 0.3s; }

        .review-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.15);
        }

        .review-card-header {
            position: relative;
            height: 220px;
            overflow: hidden;
        }

        .activity-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .review-card:hover .activity-image {
            transform: scale(1.08);
        }

        .activity-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.85));
            color: white;
            padding: 25px;
        }

        .activity-title {
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .activity-date {
            font-size: 14px;
            opacity: 0.9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .review-content {
            padding: 25px;
        }

        .review-rating {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(69, 161, 99, 0.05);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .stars {
            display: flex;
            gap: 5px;
            color: #f1c40f;
            font-size: 20px;
        }

        .review-date {
            color: #6b7280;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .review-comment {
            background: rgba(69, 161, 99, 0.08);
            border-left: 4px solid var(--primary-color);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            line-height: 1.7;
            color: var(--text-dark);
            font-size: 15px;
            backdrop-filter: blur(10px);
        }

        .review-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 0) 0%, 
                rgba(255, 255, 255, 0.3) 50%, 
                rgba(255, 255, 255, 0) 100%);
            transform: skewX(-25deg);
            transition: left 0.5s ease;
            z-index: 1;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn i, .btn span {
            position: relative;
            z-index: 2;
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--accent-color), #e67e22);
            color: var(--text-dark);
            box-shadow: 0 6px 15px rgba(233, 196, 106, 0.3);
        }

        .btn-edit:hover {
            background: linear-gradient(135deg, #e67e22, var(--accent-color));
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(233, 196, 106, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, var(--danger-color), #c0392b);
            color: white;
            box-shadow: 0 6px 15px rgba(231, 76, 60, 0.3);
        }

        .btn-delete:hover {
            background: linear-gradient(135deg, #c0392b, var(--danger-color));
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(231, 76, 60, 0.4);
        }

        /* FIXED TAG STYLES - No more overlapping! */
        .activity-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 15px 0 25px 0;
            padding: 0;
            width: 100%;
            min-height: 32px;
            position: relative;
            z-index: 1;
        }

        .tag {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            white-space: nowrap;
            margin: 0;
            line-height: 1.4;
            text-align: center;
            flex: 0 0 auto;
            max-width: 140px;
            overflow: hidden;
            text-overflow: ellipsis;
            position: relative;
            z-index: 2;
        }

        .tag.primary {
            background: rgba(69, 161, 99, 0.2);
            color: var(--primary-dark);
            border: 1px solid rgba(69, 161, 99, 0.3);
        }

        .tag.secondary {
            background: rgba(148, 107, 45, 0.2);
            color: var(--secondary-color);
            border: 1px solid rgba(148, 107, 45, 0.3);
        }

        .tag.accent {
            background: rgba(233, 196, 106, 0.2);
            color: #8b7000;
            border: 1px solid rgba(233, 196, 106, 0.3);
        }

        .tag:hover {
            transform: translateY(-2px) scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .tag.primary:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .tag.secondary:hover {
            background: var(--secondary-color);
            color: white;
            border-color: var(--secondary-color);
        }

        .tag.accent:hover {
            background: var(--accent-color);
            color: var(--text-dark);
            border-color: var(--accent-color);
        }

        .no-reviews {
            text-align: center;
            padding: 80px 40px;
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            grid-column: 1/-1;
            animation: fadeIn 0.8s ease;
        }

        .no-reviews i {
            font-size: 80px;
            color: var(--primary-color);
            margin-bottom: 25px;
            opacity: 0.7;
            animation: pulse 2s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 0.7; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.05); }
        }

        .no-reviews h3 {
            font-size: 28px;
            margin-bottom: 15px;
            color: var(--text-dark);
            font-weight: 700;
        }

        .no-reviews p {
            font-size: 18px;
            color: #6b7280;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .cta-button {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 16px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            transition: all 0.4s ease;
            box-shadow: 0 12px 25px rgba(39, 94, 62, 0.3);
            position: relative;
            overflow: hidden;
        }

        .cta-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 0) 0%, 
                rgba(255, 255, 255, 0.2) 50%, 
                rgba(255, 255, 255, 0) 100%);
            transform: skewX(-25deg);
            transition: left 0.7s ease;
            z-index: 1;
        }

        .cta-button:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-color) 100%);
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(39, 94, 62, 0.4);
        }

        .cta-button:hover::before {
            left: 100%;
        }

        .cta-button i, .cta-button span {
            position: relative;
            z-index: 2;
        }

        /* Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(15px);
            animation: fadeIn 0.3s ease;
        }

        .modal {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 25px;
            padding: 40px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: modalSlideUp 0.4s ease;
        }

        @keyframes modalSlideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(69, 161, 99, 0.1);
        }

        .modal-title {
            color: var(--text-dark);
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--text-dark);
            font-size: 16px;
        }

        .star-rating {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 25px;
            padding: 20px;
            background: rgba(69, 161, 99, 0.05);
            border-radius: 15px;
            flex-direction: row-reverse;
            backdrop-filter: blur(10px);
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 35px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .star-rating label:hover,
        .star-rating label.active,
        .star-rating input:checked ~ label {
            color: #f1c40f;
            transform: scale(1.15);
            filter: drop-shadow(0 0 12px rgba(241, 196, 15, 0.5));
        }

        .form-textarea {
            width: 100%;
            min-height: 140px;
            padding: 20px;
            border: 2px solid rgba(69, 161, 99, 0.1);
            border-radius: 15px;
            font-family: inherit;
            font-size: 16px;
            resize: vertical;
            transition: all 0.3s ease;
            background: rgba(69, 161, 99, 0.02);
            backdrop-filter: blur(10px);
        }

        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(69, 161, 99, 0.2);
            background: white;
        }

        .modal-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn-modal {
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }

        .btn-modal::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                rgba(255, 255, 255, 0) 0%, 
                rgba(255, 255, 255, 0.2) 50%, 
                rgba(255, 255, 255, 0) 100%);
            transform: skewX(-25deg);
            transition: left 0.5s ease;
            z-index: 1;
        }

        .btn-modal:hover::before {
            left: 100%;
        }

        .btn-modal i, .btn-modal span {
            position: relative;
            z-index: 2;
        }

        .btn-save {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: 0 8px 20px rgba(69, 161, 99, 0.3);
        }

        .btn-save:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(69, 161, 99, 0.4);
        }

        .btn-cancel {
            background: rgba(241, 241, 241, 0.9);
            backdrop-filter: blur(10px);
            color: #6b7280;
        }

        .btn-cancel:hover {
            background: rgba(225, 225, 225, 0.9);
            transform: translateY(-2px);
        }

        .message {
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.5s ease-out;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .message.success {
            background: rgba(69, 161, 99, 0.15);
            border-left: 5px solid var(--primary-color);
            color: var(--primary-color);
        }

        .message.error {
            background: rgba(231, 76, 60, 0.15);
            border-left: 5px solid var(--danger-color);
            color: var(--danger-color);
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Particle animation */
        .particle {
            position: absolute;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.6);
            box-shadow: 0 0 10px 2px rgba(255, 255, 255, 0.2);
            pointer-events: none;
            z-index: 0;
            animation: particleFloat 15s linear infinite;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 0.5;
            }
            90% {
                opacity: 0.3;
            }
            100% {
                transform: translateY(-100vh) translateX(20vw);
                opacity: 0;
            }
        }

        @media (max-width: 768px) {
            .reviews-grid {
                grid-template-columns: 1fr;
            }
            
            .review-actions {
                flex-direction: column;
            }
            
            .modal {
                width: 95%;
                padding: 25px;
            }

            .page-title {
                font-size: 32px;
            }

            .modal-actions {
                flex-direction: column;
            }

            .activities-tabs {
                flex-direction: column;
                align-items: center;
                gap: 15px;
            }

            .tab-button {
                width: 100%;
                max-width: 300px;
                justify-content: center;
            }

            .activity-tags {
                gap: 6px;
            }

            .tag {
                font-size: 11px;
                padding: 5px 10px;
                max-width: 120px;
            }
        }
    </style>
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>

    <div class="reviews-container">
        <a href="../compte/mon-espace.php" class="back-button">
            <i class="fa-solid fa-arrow-left"></i> <span>Retour à mon espace</span>
        </a>
        
        <div class="page-header">
            <h1 class="page-title">Mes Avis & Évaluations</h1>
            <p class="page-subtitle">Gérez et partagez vos expériences sur les activités</p>
        </div>

        <!-- Navigation tabs -->
        <div class="activities-tabs">
            <a href="activites_a_evaluer_page.php" class="tab-button">
                <i class="fa-solid fa-star"></i> Activités à évaluer
            </a>
            <a href="mes_avis_page.php" class="tab-button active">
                <i class="fa-regular fa-comments"></i> Mes avis publiés
            </a>
        </div>

        <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($user_reviews)): ?>
        <div class="reviews-grid">
            <div class="no-reviews">
                <i class="fa-regular fa-comments"></i>
                <h3>Aucun avis pour le moment</h3>
                <p>Vous n'avez pas encore laissé d'avis sur les activités. Participez à des activités et partagez vos expériences !</p>
                <a href="activites_a_evaluer_page.php" class="cta-button">
                    <i class="fa-solid fa-star"></i> <span>Découvrir les activités à évaluer</span>
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="reviews-grid">
            <?php foreach ($user_reviews as $review): ?>
            <div class="review-card" data-id="<?php echo $review['activite_id']; ?>">
                <div class="review-card-header">
                    <?php if (!empty($review['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($review['image_url']); ?>" alt="<?php echo htmlspecialchars($review['activity_title']); ?>" class="activity-image">
                    <?php else: ?>
                        <div class="activity-image" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white; font-size: 32px;">
                            <i class="fa-solid fa-image"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="activity-overlay">
                        <div class="activity-title"><?php echo htmlspecialchars($review['activity_title']); ?></div>
                        <?php if (!empty($review['date_ou_periode'])): ?>
                        <div class="activity-date">
                            <i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars($review['date_ou_periode']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="review-content">
                    <div class="review-rating">
                        <div class="stars">
                            <?php
                            $rating = intval($review['note']);
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="fa-solid fa-star"></i>';
                                } else {
                                    echo '<i class="fa-regular fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <div class="review-date">
                            <i class="fa-regular fa-clock"></i> 
                            <?php echo date('d/m/Y', strtotime($review['date_evaluation'])); ?>
                        </div>
                    </div>
                    
                    <div class="review-comment">
                        <?php echo nl2br(htmlspecialchars($review['commentaire'])); ?>
                    </div>
                    
                    <?php if (!empty($review['activity_tags'])): ?>
                    <div class="activity-tags">
                        <?php 
                        $displayedTags = 0;
                        foreach ($review['activity_tags'] as $tag): 
                            if ($displayedTags < 3 && !empty($tag['display_name'])): 
                                $displayedTags++;
                                $tagClass = $tagManager->getTagClass($tag['name']);
                        ?>
                            <span class="tag <?php echo htmlspecialchars($tagClass); ?>"><?php echo htmlspecialchars($tag['display_name']); ?></span>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="review-actions">
                        <button class="btn btn-edit" onclick="event.stopPropagation(); editReview(<?php echo $review['id']; ?>, <?php echo $review['note']; ?>, '<?php echo addslashes($review['commentaire']); ?>')">
                            <i class="fa-solid fa-pen"></i> <span>Modifier</span>
                        </button>
                        <button class="btn btn-delete" onclick="event.stopPropagation(); deleteReview(<?php echo $review['id']; ?>, '<?php echo addslashes($review['activity_title']); ?>')">
                            <i class="fa-solid fa-trash"></i> <span>Supprimer</span>
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Edit Review Modal -->
    <div id="edit-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Modifier mon avis</h3>
            </div>
            
            <form method="post" id="edit-form">
                <input type="hidden" name="review_id" id="edit-review-id">
                <input type="hidden" name="update_review" value="1">
                
                <div class="form-group">
                    <label class="form-label">Note</label>
                    <div class="star-rating" id="edit-star-rating">
                        <input type="radio" name="rating" value="5" id="edit-star5">
                        <label for="edit-star5"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="4" id="edit-star4">
                        <label for="edit-star4"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="3" id="edit-star3">
                        <label for="edit-star3"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="2" id="edit-star2">
                        <label for="edit-star2"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="1" id="edit-star1">
                        <label for="edit-star1"><i class="fa-solid fa-star"></i></label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit-comment">Commentaire</label>
                    <textarea name="comment" id="edit-comment" class="form-textarea" placeholder="Partagez votre expérience..." required></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeEditModal()">
                        <i class="fa-solid fa-times"></i> <span>Annuler</span>
                    </button>
                    <button type="submit" class="btn-modal btn-save">
                        <i class="fa-solid fa-save"></i> <span>Sauvegarder</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" style="color: var(--danger-color);">Confirmer la suppression</h3>
            </div>
            
            <p style="text-align: center; margin-bottom: 25px; color: #6b7280; font-size: 16px; line-height: 1.6;">
                Êtes-vous sûr de vouloir supprimer votre avis sur <strong id="delete-activity-name"></strong> ? Cette action est irréversible.
            </p>
            
            <form method="post" id="delete-form">
                <input type="hidden" name="review_id" id="delete-review-id">
                <input type="hidden" name="delete_review" value="1">
                
                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeDeleteModal()">
                        <i class="fa-solid fa-times"></i> <span>Annuler</span>
                    </button>
                    <button type="submit" class="btn-modal" style="background: linear-gradient(135deg, var(--danger-color), #c0392b); color: white; box-shadow: 0 8px 20px rgba(231, 76, 60, 0.3);">
                        <i class="fa-solid fa-trash"></i> <span>Supprimer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>

    <script>
    // Star rating functionality
    function initStarRating() {
        const starRating = document.getElementById('edit-star-rating');
        const stars = starRating.querySelectorAll('label');
        const inputs = starRating.querySelectorAll('input');
        
        stars.forEach((star, index) => {
            star.addEventListener('mouseover', function() {
                highlightStars(stars.length - index);
            });
            
            star.addEventListener('click', function() {
                const rating = stars.length - index;
                inputs[stars.length - index - 1].checked = true;
                highlightStars(rating, true);
            });
        });
        
        starRating.addEventListener('mouseleave', function() {
            const checkedInput = starRating.querySelector('input:checked');
            if (checkedInput) {
                const rating = parseInt(checkedInput.value);
                highlightStars(rating, true);
            } else {
                clearStars();
            }
        });
        
        function highlightStars(count, permanent = false) {
            stars.forEach((star, index) => {
                if (index >= stars.length - count) {
                    star.classList.add('active');
                } else {
                    star.classList.remove('active');
                }
            });
        }
        
        function clearStars() {
            stars.forEach(star => star.classList.remove('active'));
        }
    }

    function editReview(reviewId, rating, comment) {
        document.getElementById('edit-review-id').value = reviewId;
        document.getElementById('edit-comment').value = comment;
        document.getElementById('edit-star' + rating).checked = true;
        
        // Highlight the selected stars
        const stars = document.querySelectorAll('#edit-star-rating label');
        stars.forEach((star, index) => {
            if (index >= stars.length - rating) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
        
        document.getElementById('edit-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        document.getElementById('edit-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function deleteReview(reviewId, activityName) {
        document.getElementById('delete-review-id').value = reviewId;
        document.getElementById('delete-activity-name').textContent = activityName;
        document.getElementById('delete-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteModal() {
        document.getElementById('delete-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initStarRating();
        
        // Make review cards clickable
        document.querySelectorAll('.review-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't redirect if clicking on action buttons
                if (e.target.closest('.review-actions') || e.target.closest('.btn')) {
                    return;
                }
                
                const activityId = this.getAttribute('data-id');
                if (activityId) {
                    window.location.href = 'activite.php?id=' + activityId;
                }
            });
        });
        
        // Close modals when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    overlay.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        });
        
        // Auto-hide success/error messages
        const message = document.querySelector('.message');
        if (message) {
            setTimeout(() => {
                message.style.opacity = '0';
                message.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    message.style.display = 'none';
                }, 500);
            }, 5000);
        }

        // Create animated particles for background effect
        for (let i = 0; i < 12; i++) {
            const particle = document.createElement('div');
            particle.classList.add('particle');
            
            // Random size and position
            const size = Math.random() * 5 + 3;
            particle.style.width = `${size}px`;
            particle.style.height = `${size}px`;
            particle.style.left = `${Math.random() * 100}vw`;
            particle.style.top = `${Math.random() * 100}vh`;
            
            // Random animation
            particle.style.animationDuration = `${Math.random() * 15 + 10}s`;
            particle.style.animationDelay = `${Math.random() * 5}s`;
            
            // Add to body
            document.body.appendChild(particle);
        }
    });
    </script>
</body>
</html>

<?php
$conn->close();
$user_conn->close();
?>