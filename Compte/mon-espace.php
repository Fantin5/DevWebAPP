<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: ../Connexion-Inscription/login_form.php");
    exit();
}

// Inclure la configuration de la base de données
include '../Connexion-Inscription/config.php';

// Récupérer les informations complètes de l'utilisateur
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM user_form WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Si l'utilisateur n'existe pas, déconnecter et rediriger
    session_destroy();
    header("Location: ../Connexion-Inscription/login_form.php");
    exit();
}

$user_data = $result->fetch_assoc();

// Définir le titre de la page
$page_title = "Mon Espace - Synapse";

// Inclure le header
include '../TEMPLATE/Nouveauhead.php';
?>

<div class="profile-container">
    <div class="profile-header">
        <h1>Mon Profil</h1>
    </div>
    
    <div class="profile-content">
        <div class="profile-section">
            <h2>Informations personnelles</h2>
            <div class="profile-info">
                <div class="info-group">
                    <label>Nom :</label>
                    <p><?= htmlspecialchars($user_data['name']) ?></p>
                </div>
                <div class="info-group">
                    <label>Prénom :</label>
                    <p><?= htmlspecialchars($user_data['first_name']) ?></p>
                </div>
                <div class="info-group">
                    <label>Email :</label>
                    <p><?= htmlspecialchars($user_data['email']) ?></p>
                </div>
                <div class="info-group">
                    <label>Date de naissance :</label>
                    <p><?= htmlspecialchars($user_data['birthday']) ?></p>
                </div>
                <div class="info-group">
                    <label>Téléphone :</label>
                    <p><?= htmlspecialchars($user_data['phone_nb']) ?></p>
                </div>
            </div>
        </div>
        
        <div class="profile-actions">
            <a href="edit-profile.php" class="profile-btn primary">
                <i class="fa-solid fa-pen"></i> Modifier mon profil
            </a>
            <a href="../Connexion-Inscription/logout.php" class="profile-btn secondary">
                <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
            </a>
        </div>
    </div>
</div>

<style>
    .profile-container {
        width: 90%;
        max-width: 800px;
        margin: 40px auto;
        background-color: white;
        border-radius: 15px;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        overflow: hidden;
    }
    
    .profile-header {
        background-color: #828977;
        padding: 25px;
        color: #E4D8C8;
        text-align: center;
    }
    
    .profile-header h1 {
        margin: 0;
        font-size: 28px;
        font-weight: 600;
    }
    
    .profile-content {
        padding: 30px;
    }
    
    .profile-section {
        margin-bottom: 30px;
    }
    
    .profile-section h2 {
        color: #828977;
        font-size: 20px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #E4D8C8;
    }
    
    .profile-info {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .info-group {
        margin-bottom: 15px;
    }
    
    .info-group label {
        display: block;
        color: #666;
        font-size: 14px;
        margin-bottom: 5px;
    }
    
    .info-group p {
        color: #333;
        font-size: 16px;
        font-weight: 500;
        margin: 0;
    }
    
    .profile-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 30px;
    }
    
    .profile-btn {
        padding: 12px 25px;
        border-radius: 30px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }
    
    .profile-btn.primary {
        background-color: #45a163;
        color: #111;
    }
    
    .profile-btn.primary:hover {
        background-color: #3d8d57;
        transform: translateY(-2px);
    }
    
    .profile-btn.secondary {
        background-color: #E4D8C8;
        color: #828977;
        border: 1px solid #828977;
    }
    
    .profile-btn.secondary:hover {
        background-color: #d8cbb8;
        transform: translateY(-2px);
    }
    
    @media (max-width: 768px) {
        .profile-info {
            grid-template-columns: 1fr;
        }
        
        .profile-actions {
            flex-direction: column;
        }
        
        .profile-btn {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<?php
// Inclure le footer
include '../TEMPLATE/footer.php';
?>