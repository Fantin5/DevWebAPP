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

// Handle account deletion
if (isset($_POST['delete_account']) && $_POST['delete_account'] === 'confirm') {
    // Delete the user from the database
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
            
            .modal {
                width: 95%;
                padding: 20px;
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
                
                <a href="#" class="activity-link">
                    <i class="fa-solid fa-pen"></i> Modifier mon profil
                </a>
            </div>
            
            <!-- Newsletter Card -->
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
                <div class="card-decoration"></div>
                <div class="dashboard-card-header">
                    <div class="dashboard-card-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <h2 class="dashboard-card-title">Mes Activités</h2>
                </div>
                
                <div class="activities-count">0</div>
                <p class="center-text">Activités créées par vous</p>
                
                <a href="../Testing grounds/mes-activites.php" class="activity-link">
                    <i class="fa-solid fa-list"></i> Voir mes activités
                </a>
                
                <a href="../Testing grounds/jenis.php" class="activity-link">
                    <i class="fa-solid fa-plus"></i> Créer une nouvelle activité
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
            
            // Delete account modal functionality
            const deleteModal = document.getElementById('delete-modal');
            const openModalBtn = document.getElementById('open-delete-modal');
            const cancelDeleteBtn = document.getElementById('cancel-delete');
            
            openModalBtn.addEventListener('click', function() {
                deleteModal.style.display = 'flex';
                document.body.style.overflow = 'hidden'; // Prevent scrolling
            });
            
            cancelDeleteBtn.addEventListener('click', function() {
                deleteModal.style.display = 'none';
                document.body.style.overflow = 'auto'; // Re-enable scrolling
            });
            
            // Close modal when clicking outside
            deleteModal.addEventListener('click', function(event) {
                if (event.target === deleteModal) {
                    deleteModal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
            
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