<?php
session_start();

// Déterminer l'action à effectuer en fonction du paramètre dans l'URL
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Action pour vérifier si l'utilisateur a des informations de paiement enregistrées
if ($action === 'check_payment_info') {
    checkPaymentInfo();
} 
// Action pour traiter un paiement direct
elseif ($action === 'process_payment') {
    processDirectPayment();
} 
// Si aucune action n'est spécifiée, retourner une erreur
else {
    echo json_encode(['success' => false, 'message' => 'Action non spécifiée']);
}

/**
 * Vérifie si l'utilisateur connecté a des informations de paiement enregistrées
 */
function checkPaymentInfo() {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode(['has_payment_info' => false]);
        exit;
    }

    // Récupérer l'ID de l'utilisateur
    $user_id = $_SESSION['user_id'];

    // Connexion à la base de données
    $conn = new mysqli('localhost', 'root', '', 'user_db');
    if ($conn->connect_error) {
        echo json_encode(['has_payment_info' => false, 'error' => 'Erreur de connexion à la base de données']);
        exit;
    }

    // Vérifier si la table payment_info existe
    $tableExists = false;
    $result = $conn->query("SHOW TABLES LIKE 'payment_info'");
    if ($result->num_rows > 0) {
        $tableExists = true;
    }

    // Si la table n'existe pas, la créer
    if (!$tableExists) {
        $sql = "CREATE TABLE payment_info (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            card_last_four VARCHAR(4) NOT NULL,
            expiry_date VARCHAR(5) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user_form(id)
        )";
        
        if (!$conn->query($sql)) {
            echo json_encode(['has_payment_info' => false, 'error' => 'Erreur lors de la création de la table']);
            $conn->close();
            exit;
        }
    }

    // Vérifier si l'utilisateur a déjà des informations de paiement
    $stmt = $conn->prepare("SELECT id FROM payment_info WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $hasPaymentInfo = $result->num_rows > 0;

    $stmt->close();
    $conn->close();

    echo json_encode(['has_payment_info' => $hasPaymentInfo]);
}

/**
 * Traite un paiement direct lorsque l'utilisateur a déjà des informations de paiement enregistrées
 */
function processDirectPayment() {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
        exit;
    }

    // Récupérer les données JSON envoyées
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if (!isset($data['items']) || !is_array($data['items'])) {
        echo json_encode(['success' => false, 'message' => 'Données invalides']);
        exit;
    }

    // Récupérer l'ID de l'utilisateur
    $user_id = $_SESSION['user_id'];

    // Traitement du paiement "direct" (simulé)
    // Dans un environnement de production, vous intégreriez ici un système de paiement réel

    // Récupérer les ID des activités
    $activity_ids = array_map(function($item) {
        return $item['id'];
    }, $data['items']);

    // Connexion à la base de données d'activités
    $conn = new mysqli('localhost', 'root', '', 'activity');
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }

    // Vérifier si la table des achats existe, sinon la créer
    $conn->query("CREATE TABLE IF NOT EXISTS activites_achats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        activite_id INT NOT NULL,
        date_achat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (activite_id) REFERENCES activites(id)
    )");

    // Enregistrer les achats dans la base de données
    $stmt = $conn->prepare("INSERT INTO activites_achats (user_id, activite_id) VALUES (?, ?)");
    $success = true;

    foreach ($activity_ids as $activity_id) {
        // Vérifier si l'utilisateur est déjà inscrit à cette activité
        $check_stmt = $conn->prepare("SELECT id FROM activites_achats WHERE user_id = ? AND activite_id = ?");
        $check_stmt->bind_param("ii", $user_id, $activity_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        // Seulement ajouter si l'utilisateur n'est pas déjà inscrit
        if ($check_result->num_rows == 0) {
            $stmt->bind_param("ii", $user_id, $activity_id);
            if (!$stmt->execute()) {
                $success = false;
                break;
            }
        }
        
        $check_stmt->close();
    }

    $stmt->close();
    $conn->close();

    // Répondre avec le résultat
    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Paiement traité avec succès' : 'Erreur lors du traitement du paiement'
    ]);
}
?>