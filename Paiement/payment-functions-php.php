<?php
session_start();

// Déterminer l'action à effectuer en fonction du paramètre dans l'URL
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Action pour vérifier si l'utilisateur a des informations de paiement enregistrées
if ($action === 'check_payment_info') {
    checkPaymentInfo();
} 
// Action pour récupérer toutes les cartes de l'utilisateur
elseif ($action === 'get_user_cards') {
    getUserCards();
}
// Action pour traiter un paiement direct avec une carte spécifique
elseif ($action === 'process_payment') {
    processDirectPayment();
} 
// Action pour supprimer une carte
elseif ($action === 'delete_card') {
    deleteCard();
}
// Action pour définir une carte par défaut
elseif ($action === 'set_default_card') {
    setDefaultCard();
}
// Action pour ajouter une nouvelle carte
elseif ($action === 'add_card') {
    addCard();
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

    // Si la table n'existe pas, la créer avec la nouvelle structure
    if (!$tableExists) {
        $sql = "CREATE TABLE payment_info (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            card_name VARCHAR(100) DEFAULT 'Ma carte',
            card_last_four VARCHAR(4) NOT NULL,
            expiry_date VARCHAR(5) NOT NULL,
            is_default TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES user_form(id)
        )";
        
        if (!$conn->query($sql)) {
            echo json_encode(['has_payment_info' => false, 'error' => 'Erreur lors de la création de la table']);
            $conn->close();
            exit;
        }
    }

    // Compter le nombre de cartes de l'utilisateur
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM payment_info WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];

    $stmt->close();
    $conn->close();

    echo json_encode(['has_payment_info' => $count > 0, 'card_count' => $count]);
}

/**
 * Récupère toutes les cartes enregistrées de l'utilisateur
 */
function getUserCards() {
    // Vérifier si l'utilisateur est connecté
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $conn = new mysqli('localhost', 'root', '', 'user_db');
    
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, card_name, card_last_four, expiry_date, is_default FROM payment_info WHERE user_id = ? ORDER BY is_default DESC, created_at ASC");
    $cards = [];
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $cards[] = $row;
        }
        
        $stmt->close();
    }
    
    $conn->close();
    echo json_encode(['success' => true, 'cards' => $cards]);
}

/**
 * Traite un paiement direct avec une carte spécifique
 */
/**
 * Traite un paiement direct avec une carte spécifique
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

    $user_id = $_SESSION['user_id'];
    $card_id = isset($data['card_id']) ? intval($data['card_id']) : null;

    // CORRECTION: Vérifier qu'un card_id est fourni (obligatoire pour le paiement)
    if (!$card_id) {
        echo json_encode(['success' => false, 'message' => 'Aucune carte de paiement sélectionnée']);
        exit;
    }

    // Vérifier que la carte appartient à l'utilisateur
    $user_conn = new mysqli('localhost', 'root', '', 'user_db');
    if ($user_conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }

    $stmt = $user_conn->prepare("SELECT id FROM payment_info WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $card_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Carte non trouvée ou non autorisée']);
        $stmt->close();
        $user_conn->close();
        exit;
    }

    $stmt->close();
    $user_conn->close();

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
    $processed_activities = [];

    foreach ($activity_ids as $activity_id) {
        // Vérifier si l'utilisateur est déjà inscrit à cette activité
        $check_stmt = $conn->prepare("SELECT id FROM activites_achats WHERE user_id = ? AND activite_id = ?");
        $check_stmt->bind_param("ii", $user_id, $activity_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        // Seulement ajouter si l'utilisateur n'est pas déjà inscrit
        if ($check_result->num_rows == 0) {
            $stmt->bind_param("ii", $user_id, $activity_id);
            if ($stmt->execute()) {
                $processed_activities[] = $activity_id;
            } else {
                $success = false;
                break;
            }
        }
        
        $check_stmt->close();
    }

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => $success,
        'message' => $success ? 'Paiement traité avec succès' : 'Erreur lors du traitement du paiement',
        'processed_activities' => $processed_activities,
        'card_used' => $card_id
    ]);
}

/**
 * Supprime une carte de paiement
 */
function deleteCard() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
        exit;
    }

    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if (!isset($data['card_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de carte manquant']);
        exit;
    }

    $card_id = intval($data['card_id']);
    $user_id = $_SESSION['user_id'];

    $conn = new mysqli('localhost', 'root', '', 'user_db');
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }

    // Vérifier que la carte appartient à l'utilisateur et récupérer si elle est par défaut
    $stmt = $conn->prepare("SELECT is_default FROM payment_info WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $card_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Carte non trouvée']);
        $conn->close();
        exit;
    }

    $card_data = $result->fetch_assoc();
    $was_default = $card_data['is_default'];

    // Supprimer la carte
    $stmt = $conn->prepare("DELETE FROM payment_info WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $card_id, $user_id);
    $success = $stmt->execute();

    // Si la carte supprimée était par défaut, définir une autre carte comme défaut
    if ($success && $was_default) {
        $stmt = $conn->prepare("UPDATE payment_info SET is_default = 1 WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }

    $stmt->close();
    $conn->close();

    echo json_encode(['success' => $success]);
}

/**
 * Définit une carte comme carte par défaut
 */
function setDefaultCard() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
        exit;
    }

    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if (!isset($data['card_id'])) {
        echo json_encode(['success' => false, 'message' => 'ID de carte manquant']);
        exit;
    }

    $card_id = intval($data['card_id']);
    $user_id = $_SESSION['user_id'];

    $conn = new mysqli('localhost', 'root', '', 'user_db');
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }

    // Vérifier que la carte appartient à l'utilisateur
    $stmt = $conn->prepare("SELECT id FROM payment_info WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $card_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo json_encode(['success' => false, 'message' => 'Carte non trouvée']);
        $conn->close();
        exit;
    }

    // Retirer le statut par défaut de toutes les cartes de l'utilisateur
    $conn->query("UPDATE payment_info SET is_default = 0 WHERE user_id = $user_id");

    // Définir la carte sélectionnée comme défaut
    $stmt = $conn->prepare("UPDATE payment_info SET is_default = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $card_id, $user_id);
    $success = $stmt->execute();

    $stmt->close();
    $conn->close();

    echo json_encode(['success' => $success]);
}

/**
 * Ajoute une nouvelle carte de paiement
 */
function addCard() {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
        exit;
    }

    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    if (!isset($data['card_number']) || !isset($data['expiry_date'])) {
        echo json_encode(['success' => false, 'message' => 'Données de carte manquantes']);
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $card_name = isset($data['card_name']) && !empty($data['card_name']) ? $data['card_name'] : 'Ma carte';
    $card_number = substr(str_replace(' ', '', $data['card_number']), -4);
    $expiry_date = $data['expiry_date'];
    $set_as_default = isset($data['set_as_default']) ? $data['set_as_default'] : false;

    $conn = new mysqli('localhost', 'root', '', 'user_db');
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }

    // Créer la table si elle n'existe pas
    $conn->query("CREATE TABLE IF NOT EXISTS payment_info (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        card_name VARCHAR(100) DEFAULT 'Ma carte',
        card_last_four VARCHAR(4) NOT NULL,
        expiry_date VARCHAR(5) NOT NULL,
        is_default TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES user_form(id)
    )");

    // Vérifier s'il s'agit de la première carte (sera par défaut) ou si explicitement demandé
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM payment_info WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $is_default = ($count == 0 || $set_as_default) ? 1 : 0;

    // Si on définit cette carte comme défaut, retirer le statut des autres
    if ($is_default && $count > 0) {
        $conn->query("UPDATE payment_info SET is_default = 0 WHERE user_id = $user_id");
    }

    // Insérer la nouvelle carte
    $stmt = $conn->prepare("INSERT INTO payment_info (user_id, card_name, card_last_four, expiry_date, is_default) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $user_id, $card_name, $card_number, $expiry_date, $is_default);
    
    $success = $stmt->execute();
    $card_id = $success ? $conn->insert_id : null;

    $stmt->close();
    $conn->close();

    echo json_encode([
        'success' => $success,
        'card_id' => $card_id,
        'message' => $success ? 'Carte ajoutée avec succès' : 'Erreur lors de l\'ajout de la carte'
    ]);
}
?>
<!-- cvq -->