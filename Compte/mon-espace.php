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
        .user-space-container {
            width: 90%;
            max-width: 1000px;
            margin: 40px auto;
        }
        
        .page-title {
            text-align: center;
            color: #828977;
            margin-bottom: 30px;
            font-size: 32px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.15);
        }
        
        .dashboard-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .dashboard-card-icon {
            width: 50px;
            height: 50px;
            background-color: rgba(130, 137, 119, 0.1);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 24px;
            color: #828977;
        }
        
        .dashboard-card-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .user-info-section {
            margin-bottom: 20px;
        }
        
        .user-info-name {
            font-size: 20px;
            margin: 0 0 5px 0;
            color: #333;
        }
        
        .user-info-email {
            font-size: 15px;
            margin: 0;
            color: #666;
        }
        
        .user-info-item {
            margin: 15px 0;
        }
        
        .user-info-label {
            font-size: 13px;
            color: #666;
            margin-bottom: 5px;
            display: block;
        }
        
        .user-info-value {
            font-size: 16px;
            color: #333;
            padding: 8px 12px;
            background-color: #f9f9f9;
            border-radius: 6px;
        }
        
        .newsletter-status {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        .status-active {
            background-color: #45a163;
        }
        
        .status-inactive {
            background-color: #e74c3c;
        }
        
        .newsletter-status-text {
            font-size: 15px;
            color: #333;
        }
        
        .newsletter-button {
            width: 100%;
            padding: 12px;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .subscribe-button {
            background-color: var(--primary-color);
            color: #111;
        }
        
        .subscribe-button:hover {
            background-color: #3abd7a;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(69, 161, 99, 0.3);
        }
        
        .unsubscribe-button {
            background-color: #f1f1f1;
            color: #666;
        }
        
        .unsubscribe-button:hover {
            background-color: #e1e1e1;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        
        .activities-count {
            font-size: 24px;
            color: #828977;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .activity-link {
            display: block;
            margin-top: 15px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .activity-link:hover {
            color: #3abd7a;
            text-decoration: underline;
        }
        
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .message i {
            font-size: 24px;
        }
        
        .message-content {
            flex: 1;
        }
        
        .message.success {
            background-color: rgba(69, 161, 99, 0.1);
            border-left: 4px solid var(--primary-color);
        }
        
        .message.success i {
            color: var(--primary-color);
        }
        
        .message.error {
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 4px solid #e74c3c;
        }
        
        .message.error i {
            color: #e74c3c;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>

    <div class="user-space-container">
        <h1 class="page-title">Mon Espace</h1>
        
        <?php if (isset($message)): ?>
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
                
                <a href="#" class="activity-link">
                    <i class="fa-solid fa-pen"></i> Modifier mon profil
                </a>
            </div>
            
            <!-- Newsletter Card -->
            <div class="dashboard-card">
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
                
                <p>
                    <?php echo $user['newsletter_subscribed'] 
                        ? 'Recevez régulièrement des informations sur nos nouvelles activités. Vous pouvez vous désabonner à tout moment.' 
                        : 'Abonnez-vous pour recevoir les informations sur nos nouvelles activités et offres exclusives.'; ?>
                </p>
                
                <form method="post">
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
            </div>
            
            <!-- My Activities Card -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <h2 class="dashboard-card-title">Mes Activités</h2>
                </div>
                
                <div class="activities-count">0</div>
                <p>Activités créées par vous</p>
                
                <a href="mes-activites.php" class="activity-link">
                    <i class="fa-solid fa-list"></i> Voir mes activités
                </a>
                
                <a href="jenis.php" class="activity-link">
                    <i class="fa-solid fa-plus"></i> Créer une nouvelle activité
                </a>
            </div>
            
            <!-- My Cart Card -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <h2 class="dashboard-card-title">Mon Panier</h2>
                </div>
                
                <div class="activities-count">
                    <span id="cart-count">0</span>
                </div>
                <p>Activités dans votre panier</p>
                
                <a href="panier.php" class="activity-link">
                    <i class="fa-solid fa-basket-shopping"></i> Voir mon panier
                </a>
            </div>
        </div>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>

    <script>
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
            
            // Fade out message after 5 seconds
            const message = document.querySelector('.message');
            if (message) {
                setTimeout(function() {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.5s ease';
                }, 5000);
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>