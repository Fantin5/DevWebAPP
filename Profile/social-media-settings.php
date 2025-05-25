<?php
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

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle social media update
if (isset($_POST['update_social'])) {
    $instagram_url = $_POST['instagram_url'];
    $facebook_url = $_POST['facebook_url'];
    $twitter_url = $_POST['twitter_url'];
    
    $sql = "UPDATE user_form SET instagram_url = ?, facebook_url = ?, twitter_url = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $instagram_url, $facebook_url, $twitter_url, $user_id);
    
    if ($stmt->execute()) {
        $message = "Vos réseaux sociaux ont été mis à jour avec succès !";
        $message_type = "success";
    } else {
        $message = "Erreur lors de la mise à jour: " . $conn->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Get user information
$sql = "SELECT instagram_url, facebook_url, twitter_url, first_name, name FROM user_form WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer mes réseaux sociaux | Synapse</title>
    <link rel="stylesheet" href="../Testing grounds/main.css">
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
        }
        
        body {
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-title {
            color: var(--secondary-color);
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 18px;
        }
        
        .social-card {
            background: var(--card-background);
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: var(--box-shadow);
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 30px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 16px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: #fafbfc;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(69, 161, 99, 0.1);
            outline: none;
            background-color: white;
        }
        
        .social-preview {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 10px;
            text-align: center;
        }
        
        .preview-title {
            margin-bottom: 20px;
            color: var(--text-primary);
            font-weight: 600;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background-color: var(--secondary-color);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }
        
        .back-button:hover {
            background-color: #6d7564;
            transform: translateY(-2px);
        }
        
        .save-button {
            background: linear-gradient(135deg, var(--primary-color) 0%, #3abd7a 100%);
            color: white;
            font-weight: bold;
            font-size: 16px;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 20px;
        }
        
        .save-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(69, 161, 99, 0.3);
        }
        
        .message {
            padding: 18px 24px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .message.success {
            background-color: rgba(69, 161, 99, 0.12);
            border-left: 5px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .message.error {
            background-color: rgba(231, 76, 60, 0.12);
            border-left: 5px solid var(--danger-color);
            color: var(--danger-color);
        }
    </style>
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>

    <div class="container">
        <a href="../Compte/mon-espace.php" class="back-button">
            <i class="fa-solid fa-arrow-left"></i> Retour à Mon Espace
        </a>
        
        <div class="page-header">
            <h1 class="page-title">Gérer mes réseaux sociaux</h1>
            <p class="page-subtitle">Partagez vos profils pour que les participants puissent vous suivre</p>
        </div>
        
        <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>
        
        <div class="social-card">
            <form method="post">
                <div class="form-group">
                    <label class="form-label" for="instagram_url">
                        <i class="fa-brands fa-instagram" style="color: #e4405f; margin-right: 8px;"></i>
                        Instagram
                    </label>
                    <input type="url" class="form-control" id="instagram_url" name="instagram_url" 
                           value="<?php echo htmlspecialchars($user['instagram_url'] ?? ''); ?>" 
                           placeholder="https://instagram.com/votre_profil">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="facebook_url">
                        <i class="fa-brands fa-facebook" style="color: #1877f2; margin-right: 8px;"></i>
                        Facebook
                    </label>
                    <input type="url" class="form-control" id="facebook_url" name="facebook_url" 
                           value="<?php echo htmlspecialchars($user['facebook_url'] ?? ''); ?>" 
                           placeholder="https://facebook.com/votre_profil">
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="twitter_url">
                        <i class="fa-brands fa-x-twitter" style="color: #1da1f2; margin-right: 8px;"></i>
                        X (Twitter)
                    </label>
                    <input type="url" class="form-control" id="twitter_url" name="twitter_url" 
                           value="<?php echo htmlspecialchars($user['twitter_url'] ?? ''); ?>" 
                           placeholder="https://x.com/votre_profil">
                </div>
                
                <button type="submit" name="update_social" class="save-button">
                    <i class="fa-solid fa-save"></i> Enregistrer mes réseaux sociaux
                </button>
            </form>
            
            <div class="social-preview">
                <h3 class="preview-title">Aperçu de vos réseaux sociaux</h3>
                <div class="social-links-preview">
                    <?php if (!empty($user['instagram_url'])): ?>
                    <a href="<?php echo htmlspecialchars($user['instagram_url']); ?>" target="_blank" class="social-preview-link">
                        <i class="fa-brands fa-instagram"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($user['facebook_url'])): ?>
                    <a href="<?php echo htmlspecialchars($user['facebook_url']); ?>" target="_blank" class="social-preview-link">
                        <i class="fa-brands fa-facebook"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($user['twitter_url'])): ?>
                    <a href="<?php echo htmlspecialchars($user['twitter_url']); ?>" target="_blank" class="social-preview-link">
                        <i class="fa-brands fa-x-twitter"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (empty($user['instagram_url']) && empty($user['facebook_url']) && empty($user['twitter_url'])): ?>
                    <p class="no-social-message">Aucun réseau social configuré</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>
</body>
</html>
