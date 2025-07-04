<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../Connexion-Inscription/login_form.php');
    exit();
}

require_once 'activity_functions.php';
require_once 'tag_setup.php';

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "activity";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Échec de la connexion à la base de données: " . $conn->connect_error);
}

// Initialize TagManager
$tagManager = new TagManager($conn);

$user_id = $_SESSION['user_id'];

// Get activities that user has purchased but hasn't reviewed yet
$sql = "SELECT a.*, aa.date_achat
        FROM activites a 
        JOIN activites_achats aa ON a.id = aa.activite_id
        WHERE aa.user_id = ? 
        AND a.id NOT IN (
            SELECT activite_id FROM evaluations WHERE utilisateur_id = ?
        )
        ORDER BY aa.date_achat DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$activities_to_review = [];
while ($row = $result->fetch_assoc()) {
    // Get tags for this activity
    $tagsResult = $tagManager->getActivityTags($row['id']);
    $tags = [];
    while ($tagRow = $tagsResult->fetch_assoc()) {
        $tags[] = $tagRow;
    }
    $row['activity_tags'] = $tags;
    $activities_to_review[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activités à Évaluer | Synapse</title>
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

        .container {
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
            width: 120px;
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

        .activities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            animation: fadeIn 1s forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .activity-card {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s ease;
            position: relative;
            border-left: 4px solid var(--accent-color);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            animation: cardAppear 0.6s ease-out forwards;
            opacity: 0;
            transform: translateY(30px);
            cursor: pointer;
        }

        @keyframes cardAppear {
            to { opacity: 1; transform: translateY(0); }
        }

        .activity-card:nth-child(3n+1) { animation-delay: 0.1s; }
        .activity-card:nth-child(3n+2) { animation-delay: 0.2s; }
        .activity-card:nth-child(3n+3) { animation-delay: 0.3s; }

        .activity-card:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.15);
            border-left-color: var(--primary-color);
        }

        .activity-image-container {
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

        .activity-card:hover .activity-image {
            transform: scale(1.08);
        }

        .placeholder-image {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
        }

        .activity-content {
            padding: 25px;
        }

        .purchase-date {
            background: rgba(69, 161, 99, 0.15);
            color: var(--primary-color);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
        }

        .activity-title {
            font-size: 22px;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .activity-period {
            color: #6b7280;
            font-size: 14px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .activity-period i {
            color: var(--primary-color);
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

        .review-button {
            width: 100%;
            background: linear-gradient(135deg, var(--accent-color), #e67e22);
            color: var(--text-dark);
            padding: 16px;
            border: none;
            border-radius: 15px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.4s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 8px 25px rgba(233, 196, 106, 0.3);
            position: relative;
            overflow: hidden;
        }

        .review-button::before {
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
            transition: left 0.6s ease;
            z-index: 1;
        }

        .review-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(233, 196, 106, 0.4);
            background: linear-gradient(135deg, #e67e22, var(--accent-color));
        }

        .review-button:hover::before {
            left: 100%;
        }

        .review-button i, .review-button span {
            position: relative;
            z-index: 2;
        }

        .no-activities {
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

        .no-activities i {
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

        .no-activities h3 {
            font-size: 28px;
            margin-bottom: 15px;
            color: var(--text-dark);
            font-weight: 700;
        }

        .no-activities p {
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

        /* Review Modal */
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
            max-height: 90vh;
            overflow-y: auto;
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

        .modal-subtitle {
            color: #6b7280;
            font-size: 16px;
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
            gap: 15px;
            justify-content: center;
            margin-bottom: 25px;
            padding: 25px;
            background: rgba(69, 161, 99, 0.05);
            border-radius: 20px;
            flex-direction: row-reverse;
            backdrop-filter: blur(10px);
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            font-size: 40px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .star-rating label:hover,
        .star-rating label.active,
        .star-rating input:checked ~ label {
            color: #f1c40f;
            transform: scale(1.2);
            filter: drop-shadow(0 0 15px rgba(241, 196, 15, 0.6));
        }

        .form-textarea {
            width: 100%;
            min-height: 150px;
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

        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            box-shadow: 0 8px 20px rgba(69, 161, 99, 0.3);
        }

        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(69, 161, 99, 0.4);
        }

        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
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

        .close-modal {
            position: absolute;
            top: 20px;
            right: 25px;
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close-modal:hover {
            background: rgba(69, 161, 99, 0.1);
            color: var(--primary-color);
            transform: rotate(90deg);
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
            .activities-grid {
                grid-template-columns: 1fr;
            }
            
            .modal {
                width: 95%;
                padding: 25px;
                margin: 20px;
            }
            
            .modal-actions {
                flex-direction: column;
            }
            
            .star-rating label {
                font-size: 35px;
            }

            .page-title {
                font-size: 32px;
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

    <div class="container">
        <a href="../compte/mon-espace.php" class="back-button">
            <i class="fa-solid fa-arrow-left"></i> <span>Retour à mon espace</span>
        </a>
        
        <div class="page-header">
            <h1 class="page-title">Mes Avis & Évaluations</h1>
            <p class="page-subtitle">Gérez et partagez vos expériences sur les activités</p>
        </div>

        <!-- Navigation tabs -->
        <div class="activities-tabs">
            <a href="activites_a_evaluer_page.php" class="tab-button active">
                <i class="fa-solid fa-star"></i> Activités à évaluer
            </a>
            <a href="mes_avis_page.php" class="tab-button">
                <i class="fa-regular fa-comments"></i> Mes avis publiés
            </a>
        </div>

        <?php if (empty($activities_to_review)): ?>
        <div class="activities-grid">
            <div class="no-activities">
                <i class="fa-solid fa-clipboard-check"></i>
                <h3>Aucune activité à évaluer</h3>
                <p>Vous avez évalué toutes vos activités ou vous n'avez pas encore participé à des activités.</p>
                <a href="activites.php" class="cta-button">
                    <i class="fa-solid fa-compass"></i> <span>Découvrir des activités</span>
                </a>
            </div>
        </div>
        <?php else: ?>
        <div class="activities-grid">
            <?php foreach ($activities_to_review as $activity): ?>
            <div class="activity-card" data-id="<?php echo $activity['id']; ?>">
                <div class="activity-image-container">
                    <?php if (!empty($activity['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($activity['image_url']); ?>" alt="<?php echo htmlspecialchars($activity['titre']); ?>" class="activity-image">
                    <?php else: ?>
                        <div class="placeholder-image">
                            <i class="fa-solid fa-image"></i>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="activity-content">
                    <div class="purchase-date">
                        <i class="fa-solid fa-calendar-check"></i> 
                        Participé le <?php echo date('d/m/Y', strtotime($activity['date_achat'])); ?>
                    </div>
                    
                    <h3 class="activity-title"><?php echo htmlspecialchars($activity['titre']); ?></h3>
                    
                    <?php if (!empty($activity['date_ou_periode'])): ?>
                    <div class="activity-period">
                        <i class="fa-regular fa-calendar"></i>
                        <?php echo htmlspecialchars($activity['date_ou_periode']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($activity['activity_tags'])): ?>
                    <div class="activity-tags">
                        <?php 
                        $displayedTags = 0;
                        foreach ($activity['activity_tags'] as $tag): 
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
                    
                    <button class="review-button" onclick="event.stopPropagation(); openReviewModal(<?php echo $activity['id']; ?>, '<?php echo addslashes($activity['titre']); ?>')">
                        <i class="fa-solid fa-star"></i>
                        <span>Laisser un avis</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Review Modal -->
    <div id="review-modal" class="modal-overlay">
        <div class="modal">
            <button class="close-modal" onclick="closeReviewModal()">
                <i class="fa-solid fa-times"></i>
            </button>
            
            <div class="modal-header">
                <h3 class="modal-title">Évaluer l'activité</h3>
                <p class="modal-subtitle" id="activity-name-display"></p>
            </div>
            
            <form id="review-form">
                <input type="hidden" id="activity-id" name="activity_id">
                
                <div class="form-group">
                    <label class="form-label">Votre note</label>
                    <div class="star-rating" id="star-rating">
                        <input type="radio" name="rating" value="5" id="star5" required>
                        <label for="star5"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="4" id="star4">
                        <label for="star4"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="3" id="star3">
                        <label for="star3"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="2" id="star2">
                        <label for="star2"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="1" id="star1">
                        <label for="star1"><i class="fa-solid fa-star"></i></label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="comment">Votre commentaire</label>
                    <textarea 
                        name="comment" 
                        id="comment" 
                        class="form-textarea" 
                        placeholder="Partagez votre expérience avec cette activité... Qu'avez-vous aimé ? Que recommanderiez-vous aux autres participants ?"
                        required
                        minlength="10"
                        maxlength="1000"
                    ></textarea>
                    <small style="color: #6b7280; font-size: 12px;">Minimum 10 caractères, maximum 1000 caractères</small>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeReviewModal()">
                        <i class="fa-solid fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="btn-modal btn-submit" id="submit-btn">
                        <i class="fa-solid fa-paper-plane"></i> Publier mon avis
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>

    <script>
    let currentActivityId = null;
    
    // Star rating functionality
    function initStarRating() {
        const starRating = document.getElementById('star-rating');
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

    function openReviewModal(activityId, activityName) {
        currentActivityId = activityId;
        document.getElementById('activity-id').value = activityId;
        document.getElementById('activity-name-display').textContent = activityName;
        document.getElementById('review-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Reset form
        document.getElementById('review-form').reset();
        document.querySelectorAll('#star-rating label').forEach(star => {
            star.classList.remove('active');
        });
    }

    function closeReviewModal() {
        document.getElementById('review-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
        currentActivityId = null;
    }

    function showNotification(message, type = 'success') {
        // Create notification
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            padding: 18px 30px;
            border-radius: 15px;
            font-weight: 600;
            z-index: 10000;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideDown 0.5s ease;
        `;
        
        if (type === 'success') {
            notification.style.background = 'rgba(69, 161, 99, 0.95)';
            notification.style.color = 'white';
            notification.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${message}`;
        } else {
            notification.style.background = 'rgba(231, 76, 60, 0.95)';
            notification.style.color = 'white';
            notification.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> ${message}`;
        }
        
        // Add animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideDown {
                from {
                    transform: translateX(-50%) translateY(-100px);
                    opacity: 0;
                }
                to {
                    transform: translateX(-50%) translateY(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(notification);
        
        // Auto remove
        setTimeout(() => {
            notification.style.animation = 'slideDown 0.5s ease reverse';
            setTimeout(() => {
                notification.remove();
                style.remove();
            }, 500);
        }, 4000);
    }

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initStarRating();
        
        // Make activity cards clickable
        document.querySelectorAll('.activity-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // Don't redirect if clicking on review button
                if (e.target.closest('.review-button')) {
                    return;
                }
                
                const activityId = this.getAttribute('data-id');
                if (activityId) {
                    window.location.href = 'activite.php?id=' + activityId;
                }
            });
        });
        
        // Handle form submission
        document.getElementById('review-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const rating = formData.get('rating');
            const comment = formData.get('comment');
            
            if (!rating) {
                showNotification('Veuillez sélectionner une note.', 'error');
                return;
            }
            
            if (comment.length < 10) {
                showNotification('Le commentaire doit contenir au moins 10 caractères.', 'error');
                return;
            }
            
            // Disable submit button
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Publication...';
            
            // Submit review
            fetch('review_system.php?action=submit_review', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    activity_id: parseInt(currentActivityId),
                    rating: parseInt(rating),
                    comment: comment
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeReviewModal();
                    
                    // Reload page after 2 seconds to update the list
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showNotification(data.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Publier mon avis';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Une erreur est survenue lors de la publication de votre avis.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Publier mon avis';
            });
        });
        
        // Close modal when clicking outside
        document.getElementById('review-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReviewModal();
            }
        });
        
        // Handle ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('review-modal').style.display === 'flex') {
                closeReviewModal();
            }
        });

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
?>