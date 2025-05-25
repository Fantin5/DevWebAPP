<?php
/**
 * Enhanced Activity Management Functions with Expiration System
 * This file consolidates activity management and adds comprehensive expiration handling
 */

// Only start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
function getActivityDBConnection() {
    return new mysqli("localhost", "root", "", "activity");
}

function getUserDBConnection() {
    return new mysqli("localhost", "root", "", "user_db");
}

/**
 * Enhanced Activity Expiration System
 */
class ActivityExpirationManager {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Parse date from various formats and return DateTime object
     */
    public function parseActivityDate($date_ou_periode) {
        if (empty($date_ou_periode)) {
            return null;
        }
        
        $date_string = trim($date_ou_periode);
        
        // Pattern 1: Date range (DD/MM/YYYY - DD/MM/YYYY)
        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})\s*-\s*(\d{1,2})\/(\d{1,2})\/(\d{4})/', $date_string, $matches)) {
            try {
                $end_date = new DateTime();
                $end_date->setDate($matches[6], $matches[5], $matches[4]);
                return $end_date;
            } catch (Exception $e) {
                return null;
            }
        }
        
        // Pattern 2: Single date (DD/MM/YYYY or DD MMM YYYY)
        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4})/', $date_string, $matches)) {
            try {
                $date = new DateTime();
                $date->setDate($matches[3], $matches[2], $matches[1]);
                return $date;
            } catch (Exception $e) {
                return null;
            }
        }
        
        // Pattern 3: French month format (DD MMM YYYY)
        $french_months = [
            'janvier' => 1, 'février' => 2, 'mars' => 3, 'avril' => 4,
            'mai' => 5, 'juin' => 6, 'juillet' => 7, 'août' => 8,
            'septembre' => 9, 'octobre' => 10, 'novembre' => 11, 'décembre' => 12
        ];
        
        foreach ($french_months as $french => $month_num) {
            if (stripos($date_string, $french) !== false) {
                if (preg_match('/(\d{1,2})\s*' . preg_quote($french, '/') . '\s*(\d{4})/i', $date_string, $matches)) {
                    try {
                        $date = new DateTime();
                        $date->setDate($matches[2], $month_num, $matches[1]);
                        return $date;
                    } catch (Exception $e) {
                        return null;
                    }
                }
            }
        }
        
        // Pattern 4: Recurring with end date (jusqu'au DD/MM/YYYY)
        if (preg_match('/jusqu[\'\\\\]*au\s*(\d{1,2})[\s\/]*(\w+)[\s\/]*(\d{4})/i', $date_string, $matches)) {
            $month = $matches[2];
            
            // Check if it's a French month name
            foreach ($french_months as $french => $month_num) {
                if (stripos($month, $french) !== false) {
                    try {
                        $date = new DateTime();
                        $date->setDate($matches[3], $month_num, $matches[1]);
                        return $date;
                    } catch (Exception $e) {
                        return null;
                    }
                }
            }
            
            // If it's a numeric month
            if (is_numeric($month)) {
                try {
                    $date = new DateTime();
                    $date->setDate($matches[3], $month, $matches[1]);
                    return $date;
                } catch (Exception $e) {
                    return null;
                }
            }
        }
        
        return null; // No expiration date found
    }
    
    /**
     * Check if an activity is expired
     */
    public function isActivityExpired($date_ou_periode) {
        $end_date = $this->parseActivityDate($date_ou_periode);
        
        if (!$end_date) {
            return false; // No expiration date means never expires
        }
        
        $now = new DateTime();
        return $now > $end_date;
    }
    
    /**
     * Check if an activity is expiring soon (within 7 days)
     */
    public function isActivityExpiringSoon($date_ou_periode) {
        $end_date = $this->parseActivityDate($date_ou_periode);
        
        if (!$end_date) {
            return false;
        }
        
        $now = new DateTime();
        $diff = $now->diff($end_date);
        
        return !$diff->invert && $diff->days <= 7;
    }
    
    /**
     * Get days remaining until expiration
     */
    public function getDaysUntilExpiration($date_ou_periode) {
        $end_date = $this->parseActivityDate($date_ou_periode);
        
        if (!$end_date) {
            return null;
        }
        
        $now = new DateTime();
        $diff = $now->diff($end_date);
        
        if ($diff->invert) {
            return -$diff->days; // Negative means expired
        }
        
        return $diff->days;
    }
    
    /**
     * Check if activity allows reviews (expired or ongoing with duration)
     */
    public function canLeaveReview($date_ou_periode, $user_id, $activity_id) {
        // First check if user has purchased the activity
        if (!$this->hasUserPurchasedActivity($user_id, $activity_id)) {
            return false;
        }
        
        // If activity is expired, always allow reviews
        if ($this->isActivityExpired($date_ou_periode)) {
            return true;
        }
        
        // Check if it's a duration-based activity (contains date range or "jusqu'au")
        if (preg_match('/(\d{1,2}\/\d{1,2}\/\d{4})\s*-\s*(\d{1,2}\/\d{1,2}\/\d{4})/', $date_ou_periode) ||
            stripos($date_ou_periode, 'jusqu') !== false) {
            return true; // Duration-based activities allow reviews during the period
        }
        
        return false;
    }
    
    /**
     * Check if user has purchased an activity
     */
    private function hasUserPurchasedActivity($user_id, $activity_id) {
        $stmt = $this->conn->prepare("SELECT id FROM activites_achats WHERE user_id = ? AND activite_id = ?");
        $stmt->bind_param("ii", $user_id, $activity_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $purchased = $result->num_rows > 0;
        $stmt->close();
        return $purchased;
    }
}

/**
 * Enhanced user status check with expiration awareness
 */
function checkUserActivityStatus($activity_id, $user_id = null) {
    $response = [
        'logged_in' => false,
        'is_owner' => false,
        'is_registered' => false,
        'can_purchase' => false,
        'is_expired' => false,
        'is_expiring_soon' => false,
        'days_until_expiration' => null,
        'can_review' => false
    ];

    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return $response;
    }

    $response['logged_in'] = true;
    $user_id = $user_id ?? $_SESSION['user_id'];

    $conn = getActivityDBConnection();
    if ($conn->connect_error) {
        return $response;
    }

    // Get activity details including date
    $activity_sql = "SELECT description, date_ou_periode FROM activites WHERE id = ?";
    $activity_stmt = $conn->prepare($activity_sql);
    $activity_stmt->bind_param("i", $activity_id);
    $activity_stmt->execute();
    $activity_result = $activity_stmt->get_result();
    
    if ($activity_result->num_rows > 0) {
        $activity = $activity_result->fetch_assoc();
        
        // Initialize expiration manager
        $expiration_manager = new ActivityExpirationManager($conn);
        
        // Check expiration status
        $response['is_expired'] = $expiration_manager->isActivityExpired($activity['date_ou_periode']);
        $response['is_expiring_soon'] = $expiration_manager->isActivityExpiringSoon($activity['date_ou_periode']);
        $response['days_until_expiration'] = $expiration_manager->getDaysUntilExpiration($activity['date_ou_periode']);
        
        // Check if user can leave reviews
        $response['can_review'] = $expiration_manager->canLeaveReview($activity['date_ou_periode'], $user_id, $activity_id);
        
        // Check if user is the owner of this activity
        if (preg_match('/<!--CREATOR:([^-]+)-->/', $activity["description"], $matches)) {
            try {
                $encoded_data = $matches[1];
                $json_data = base64_decode($encoded_data);
                $creator_data = json_decode($json_data, true);
                
                if (isset($creator_data['user_id']) && $creator_data['user_id'] == $user_id) {
                    $response['is_owner'] = true;
                }
            } catch (Exception $e) {
                // Continue without ownership check if parsing fails
            }
        }
        
        // Check if user is registered for this activity
        $registration_sql = "SELECT id FROM activites_achats WHERE user_id = ? AND activite_id = ?";
        $registration_stmt = $conn->prepare($registration_sql);
        $registration_stmt->bind_param("ii", $user_id, $activity_id);
        $registration_stmt->execute();
        $registration_result = $registration_stmt->get_result();
        
        if ($registration_result->num_rows > 0) {
            $response['is_registered'] = true;
        }
        
        $registration_stmt->close();
    }
    
    $activity_stmt->close();
    $conn->close();
    
    // User can purchase if logged in, not owner, not already registered, and not expired
    $response['can_purchase'] = $response['logged_in'] && 
                               !$response['is_owner'] && 
                               !$response['is_registered'] && 
                               !$response['is_expired'];
    
    return $response;
}

/**
 * Enhanced registration with expiration check
 */
function registerUserForActivity($activity_id, $user_id = null) {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return ['success' => false, 'message' => 'Vous devez être connecté pour vous inscrire.'];
    }

    $user_id = $user_id ?? $_SESSION['user_id'];
    $status = checkUserActivityStatus($activity_id, $user_id);
    
    if (!$status['logged_in']) {
        return ['success' => false, 'message' => 'Vous devez être connecté pour vous inscrire.'];
    }
    
    if ($status['is_owner']) {
        return ['success' => false, 'message' => 'Vous ne pouvez pas vous inscrire à votre propre activité.'];
    }
    
    if ($status['is_registered']) {
        return ['success' => false, 'message' => 'Vous êtes déjà inscrit à cette activité.'];
    }
    
    if ($status['is_expired']) {
        return ['success' => false, 'message' => 'Cette activité a expiré. Les inscriptions ne sont plus possibles.'];
    }

    $conn = getActivityDBConnection();
    if ($conn->connect_error) {
        return ['success' => false, 'message' => 'Erreur de connexion à la base de données.'];
    }

    // Validate activity exists and check if it's free
    $activity_check_sql = "SELECT id, prix FROM activites WHERE id = ?";
    $activity_stmt = $conn->prepare($activity_check_sql);
    $activity_stmt->bind_param("i", $activity_id);
    $activity_stmt->execute();
    $activity_result = $activity_stmt->get_result();

    if ($activity_result->num_rows === 0) {
        $conn->close();
        return ['success' => false, 'message' => 'Activité introuvable.'];
    }

    $activity_data = $activity_result->fetch_assoc();
    
    // Only allow direct registration for FREE activities
    if ($activity_data['prix'] > 0) {
        $conn->close();
        return [
            'success' => false, 
            'message' => 'Cette activité est payante. Veuillez passer par le panier pour finaliser votre achat.',
            'requires_payment' => true
        ];
    }

    // Register user for the FREE activity
    $register_sql = "INSERT INTO activites_achats (user_id, activite_id, date_achat) VALUES (?, ?, NOW())";
    $register_stmt = $conn->prepare($register_sql);
    $register_stmt->bind_param("ii", $user_id, $activity_id);
    
    if ($register_stmt->execute()) {
        $register_stmt->close();
        $conn->close();
        return [
            'success' => true, 
            'message' => 'Inscription réussie ! Vous êtes maintenant inscrit à cette activité gratuite.'
        ];
    } else {
        $register_stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Erreur lors de l\'inscription. Veuillez réessayer.'];
    }
}

/**
 * Enhanced cart validation with expiration check
 */
function validateCartAddition($activity_id, $user_id = null) {
    $status = checkUserActivityStatus($activity_id, $user_id);
    
    if (!$status['logged_in']) {
        return [
            'success' => false, 
            'message' => 'Vous devez être connecté pour ajouter des activités au panier.',
            'redirect' => '../Connexion-Inscription/login_form.php'
        ];
    }
    
    if ($status['is_owner']) {
        return [
            'success' => false, 
            'message' => 'Vous ne pouvez pas ajouter votre propre activité au panier.'
        ];
    }
    
    if ($status['is_registered']) {
        return [
            'success' => false, 
            'message' => 'Vous êtes déjà inscrit à cette activité.'
        ];
    }
    
    if ($status['is_expired']) {
        return [
            'success' => false, 
            'message' => 'Cette activité a expiré. Elle ne peut plus être ajoutée au panier.'
        ];
    }
    
    return ['success' => true, 'message' => 'L\'activité peut être ajoutée au panier.'];
}

/**
 * Enhanced cart validation for payment with expiration check
 */
function validateCartForPayment($cart_items, $user_id = null) {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return [
            'success' => false, 
            'message' => 'Vous devez être connecté pour procéder au paiement.',
            'redirect' => '../Connexion-Inscription/login_form.php'
        ];
    }

    if (!is_array($cart_items) || empty($cart_items)) {
        return [
            'success' => false, 
            'message' => 'Votre panier est vide.'
        ];
    }

    $user_id = $user_id ?? $_SESSION['user_id'];
    $invalid_items = [];
    $expired_items = [];

    foreach ($cart_items as $cart_item) {
        if (!isset($cart_item['id']) || !is_numeric($cart_item['id'])) {
            continue;
        }
        
        $activity_id = intval($cart_item['id']);
        $activity_title = isset($cart_item['title']) ? $cart_item['title'] : 'Activité inconnue';
        
        $status = checkUserActivityStatus($activity_id, $user_id);
        
        if ($status['is_expired']) {
            $expired_items[] = [
                'title' => $activity_title,
                'reason' => 'Activité expirée'
            ];
        } elseif ($status['is_registered']) {
            $invalid_items[] = [
                'title' => $activity_title,
                'reason' => 'Vous êtes déjà inscrit'
            ];
        } elseif ($status['is_owner']) {
            $invalid_items[] = [
                'title' => $activity_title,
                'reason' => 'Vous êtes l\'organisateur'
            ];
        }
    }

    $all_invalid = array_merge($invalid_items, $expired_items);
    
    if (!empty($all_invalid)) {
        $reasons = array_map(function($item) {
            return $item['title'] . ': ' . $item['reason'];
        }, $all_invalid);
        
        return [
            'success' => false,
            'message' => 'Certaines activités ne peuvent pas être achetées: ' . implode(', ', $reasons),
            'invalid_items' => $invalid_items,
            'expired_items' => $expired_items
        ];
    }

    return ['success' => true, 'message' => 'Le panier est valide pour le paiement.'];
}

/**
 * Enhanced cleanup function with expiration awareness
 */
function cleanupUserCart($cart_items, $user_id = null) {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return ['success' => true, 'items_to_remove' => [], 'removed_reasons' => []];
    }

    if (!is_array($cart_items) || empty($cart_items)) {
        return ['success' => true, 'items_to_remove' => [], 'removed_reasons' => []];
    }

    $user_id = $user_id ?? $_SESSION['user_id'];
    $items_to_remove = [];
    $removed_reasons = [];

    foreach ($cart_items as $cart_item) {
        if (!isset($cart_item['id']) || !is_numeric($cart_item['id'])) {
            continue;
        }
        
        $activity_id = intval($cart_item['id']);
        $activity_title = isset($cart_item['title']) ? $cart_item['title'] : 'Activité inconnue';
        
        $status = checkUserActivityStatus($activity_id, $user_id);
        
        if ($status['is_expired']) {
            $items_to_remove[] = $activity_id;
            $removed_reasons[] = [
                'title' => $activity_title,
                'reason' => 'Activité expirée'
            ];
        } elseif ($status['is_registered']) {
            $items_to_remove[] = $activity_id;
            $removed_reasons[] = [
                'title' => $activity_title,
                'reason' => 'Vous êtes déjà inscrit'
            ];
        } elseif ($status['is_owner']) {
            $items_to_remove[] = $activity_id;
            $removed_reasons[] = [
                'title' => $activity_title,
                'reason' => 'Vous êtes l\'organisateur'
            ];
        }
    }

    return [
        'success' => true,
        'items_to_remove' => $items_to_remove,
        'removed_reasons' => $removed_reasons
    ];
}

/**
 * Get activities with expiration status
 */
function getActivitiesWithExpiration($filters = []) {
    $conn = getActivityDBConnection();
    if ($conn->connect_error) {
        return ['success' => false, 'message' => 'Erreur de connexion à la base de données.'];
    }

    $expiration_manager = new ActivityExpirationManager($conn);
    
    $where_clauses = [];
    $params = [];
    $types = "";

    // Apply filters
    if (isset($filters['location']) && !empty($filters['location'])) {
        $locationTerm = '%' . $filters['location'] . '%';
        $where_clauses[] = "location LIKE ?";
        $params[] = $locationTerm;
        $types .= "s";
    }

    if (isset($filters['expired_only']) && $filters['expired_only']) {
        // We'll filter expired activities in PHP since expiration logic is complex
    }

    if (isset($filters['active_only']) && $filters['active_only']) {
        // We'll filter active activities in PHP since expiration logic is complex
    }

    if (isset($filters['search']) && !empty($filters['search'])) {
        $searchTerm = '%' . $filters['search'] . '%';
        $where_clauses[] = "(titre LIKE ? OR description LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "ss";
    }

    // Build the query
    $sql = "SELECT a.*, 
            GROUP_CONCAT(DISTINCT td.name) AS tags,
            GROUP_CONCAT(DISTINCT td.display_name SEPARATOR '|') AS tag_display_names
            FROM activites a 
            LEFT JOIN activity_tags at ON a.id = at.activity_id
            LEFT JOIN tag_definitions td ON at.tag_definition_id = td.id";

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }

    $sql .= " GROUP BY a.id ORDER BY a.date_creation DESC";

    // Execute query
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    $activities = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Add expiration information
            $row['is_expired'] = $expiration_manager->isActivityExpired($row['date_ou_periode']);
            $row['is_expiring_soon'] = $expiration_manager->isActivityExpiringSoon($row['date_ou_periode']);
            $row['days_until_expiration'] = $expiration_manager->getDaysUntilExpiration($row['date_ou_periode']);
            
            // Apply PHP-based filters
            if (isset($filters['expired_only']) && $filters['expired_only'] && !$row['is_expired']) {
                continue;
            }
            if (isset($filters['active_only']) && $filters['active_only'] && $row['is_expired']) {
                continue;
            }
            
            $activities[] = $row;
        }
    }

    $conn->close();
    return ['success' => true, 'activities' => $activities];
}

function unregisterUserFromActivity($activity_id, $user_id = null) {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return ['success' => false, 'message' => 'Vous devez être connecté.'];
    }

    $user_id = $user_id ?? $_SESSION['user_id'];
    $conn = getActivityDBConnection();
    
    if ($conn->connect_error) {
        return ['success' => false, 'message' => 'Erreur de connexion à la base de données.'];
    }

    $unregister_sql = "DELETE FROM activites_achats WHERE user_id = ? AND activite_id = ?";
    $unregister_stmt = $conn->prepare($unregister_sql);
    $unregister_stmt->bind_param("ii", $user_id, $activity_id);
    
    if ($unregister_stmt->execute()) {
        if ($unregister_stmt->affected_rows > 0) {
            $unregister_stmt->close();
            $conn->close();
            return ['success' => true, 'message' => 'Désinscription réussie.'];
        } else {
            $unregister_stmt->close();
            $conn->close();
            return ['success' => false, 'message' => 'Vous n\'étiez pas inscrit à cette activité.'];
        }
    } else {
        $unregister_stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Erreur lors de la désinscription.'];
    }
}

// FIXED AJAX HANDLING - Only handle specific actions that belong to this file
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($_GET['action'])) {
        // Define actions that this file should handle
        $activity_actions = [
            'check_status',
            'register', 
            'unregister',
            'cleanup_cart',
            'validate_cart_addition',
            'validate_cart_payment',
            'get_activities_with_expiration'
        ];
        
        // Only handle actions that belong to this file
        if (in_array($_GET['action'], $activity_actions)) {
            header('Content-Type: application/json');
            
            switch ($_GET['action']) {
                case 'check_status':
                    if (isset($input['activity_id'])) {
                        echo json_encode(checkUserActivityStatus($input['activity_id']));
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Activity ID required']);
                    }
                    break;
                    
                case 'register':
                    if (isset($input['activity_id'])) {
                        echo json_encode(registerUserForActivity($input['activity_id']));
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Activity ID required']);
                    }
                    break;
                    
                case 'unregister':
                    if (isset($input['activity_id'])) {
                        echo json_encode(unregisterUserFromActivity($input['activity_id']));
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Activity ID required']);
                    }
                    break;
                    
                case 'cleanup_cart':
                    if (isset($input['cart_items'])) {
                        echo json_encode(cleanupUserCart($input['cart_items']));
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Cart items required']);
                    }
                    break;
                    
                case 'validate_cart_addition':
                    if (isset($input['activity_id'])) {
                        echo json_encode(validateCartAddition($input['activity_id']));
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Activity ID required']);
                    }
                    break;

                case 'validate_cart_payment':
                    if (isset($input['cart_items'])) {
                        echo json_encode(validateCartForPayment($input['cart_items']));
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Cart items required']);
                    }
                    break;
                    
                case 'get_activities_with_expiration':
                    $filters = $input['filters'] ?? [];
                    echo json_encode(getActivitiesWithExpiration($filters));
                    break;
            }
            exit;
        }
        // If action is not in our list, let other files handle it (don't exit)
    }
}

// Handle GET requests for expiration data
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    // Define GET actions that this file should handle
    $activity_get_actions = ['get_expiration_status'];
    
    // Only handle GET actions that belong to this file
    if (in_array($_GET['action'], $activity_get_actions)) {
        header('Content-Type: application/json');
        
        switch ($_GET['action']) {
            case 'get_expiration_status':
                if (isset($_GET['activity_id'])) {
                    $conn = getActivityDBConnection();
                    $expiration_manager = new ActivityExpirationManager($conn);
                    
                    $activity_sql = "SELECT date_ou_periode FROM activites WHERE id = ?";
                    $stmt = $conn->prepare($activity_sql);
                    $stmt->bind_param("i", $_GET['activity_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $activity = $result->fetch_assoc();
                        echo json_encode([
                            'success' => true,
                            'is_expired' => $expiration_manager->isActivityExpired($activity['date_ou_periode']),
                            'is_expiring_soon' => $expiration_manager->isActivityExpiringSoon($activity['date_ou_periode']),
                            'days_until_expiration' => $expiration_manager->getDaysUntilExpiration($activity['date_ou_periode'])
                        ]);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Activity not found']);
                    }
                    
                    $stmt->close();
                    $conn->close();
                } else {
                    echo json_encode(['success' => false, 'message' => 'Activity ID required']);
                }
                break;
        }
        exit;
    }
}

/**
 * Additional utility functions
 */

/**
 * Get user's reviewable activities
 */
function getUserReviewableActivities($user_id = null) {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return ['success' => false, 'message' => 'User not logged in'];
    }

    $user_id = $user_id ?? $_SESSION['user_id'];
    $conn = getActivityDBConnection();
    
    if ($conn->connect_error) {
        return ['success' => false, 'message' => 'Database connection error'];
    }

    $expiration_manager = new ActivityExpirationManager($conn);
    
    // Get all activities the user has purchased
    $sql = "SELECT a.*, aa.date_achat,
            GROUP_CONCAT(DISTINCT td.name) AS tags,
            GROUP_CONCAT(DISTINCT td.display_name SEPARATOR '|') AS tag_display_names
            FROM activites a 
            JOIN activites_achats aa ON a.id = aa.activite_id
            LEFT JOIN activity_tags at ON a.id = at.activity_id
            LEFT JOIN tag_definitions td ON at.tag_definition_id = td.id
            WHERE aa.user_id = ?
            GROUP BY a.id
            ORDER BY aa.date_achat DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviewable_activities = [];
    
    while ($row = $result->fetch_assoc()) {
        if ($expiration_manager->canLeaveReview($row['date_ou_periode'], $user_id, $row['id'])) {
            // Check if user has already reviewed this activity
            $review_check_sql = "SELECT id FROM evaluations WHERE activite_id = ? AND utilisateur_id = ?";
            $review_stmt = $conn->prepare($review_check_sql);
            $review_stmt->bind_param("ii", $row['id'], $user_id);
            $review_stmt->execute();
            $review_result = $review_stmt->get_result();
            
            $row['already_reviewed'] = $review_result->num_rows > 0;
            $row['is_expired'] = $expiration_manager->isActivityExpired($row['date_ou_periode']);
            $row['days_until_expiration'] = $expiration_manager->getDaysUntilExpiration($row['date_ou_periode']);
            
            $reviewable_activities[] = $row;
            $review_stmt->close();
        }
    }
    
    $stmt->close();
    $conn->close();
    
    return ['success' => true, 'activities' => $reviewable_activities];
}

/**
 * Get activity reviews with user information
 */
function getActivityReviews($activity_id, $limit = 10, $offset = 0) {
    $conn = getActivityDBConnection();
    $user_conn = getUserDBConnection();
    
    if ($conn->connect_error || $user_conn->connect_error) {
        return ['success' => false, 'message' => 'Database connection error'];
    }
    
    $sql = "SELECT e.*, u.first_name, u.name 
            FROM evaluations e 
            LEFT JOIN user_db.user_form u ON e.utilisateur_id = u.id 
            WHERE e.activite_id = ? 
            ORDER BY e.date_evaluation DESC 
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $activity_id, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reviews = [];
    while ($row = $result->fetch_assoc()) {
        $reviews[] = $row;
    }
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM evaluations WHERE activite_id = ?";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param("i", $activity_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total = $count_result->fetch_assoc()['total'];
    
    $stmt->close();
    $count_stmt->close();
    $conn->close();
    $user_conn->close();
    
    return [
        'success' => true, 
        'reviews' => $reviews, 
        'total' => $total,
        'has_more' => ($offset + $limit) < $total
    ];
}

/**
 * Enhanced activity statistics with expiration data
 */
function getActivityStatistics() {
    $conn = getActivityDBConnection();
    if ($conn->connect_error) {
        return ['success' => false, 'message' => 'Database connection error'];
    }
    
    $expiration_manager = new ActivityExpirationManager($conn);
    
    // Get all activities
    $sql = "SELECT id, date_ou_periode FROM activites";
    $result = $conn->query($sql);
    
    $stats = [
        'total_activities' => 0,
        'active_activities' => 0,
        'expired_activities' => 0,
        'expiring_soon_activities' => 0
    ];
    
    while ($row = $result->fetch_assoc()) {
        $stats['total_activities']++;
        
        if ($expiration_manager->isActivityExpired($row['date_ou_periode'])) {
            $stats['expired_activities']++;
        } else {
            $stats['active_activities']++;
            if ($expiration_manager->isActivityExpiringSoon($row['date_ou_periode'])) {
                $stats['expiring_soon_activities']++;
            }
        }
    }
    
    $conn->close();
    return ['success' => true, 'statistics' => $stats];
}

/**
 * Bulk update activity expiration status (for maintenance)
 */
function updateAllActivityExpirationStatus() {
    $conn = getActivityDBConnection();
    if ($conn->connect_error) {
        return ['success' => false, 'message' => 'Database connection error'];
    }
    
    $expiration_manager = new ActivityExpirationManager($conn);
    
    // This could be used to add a cached expiration status column if needed
    // For now, we calculate expiration status on-the-fly
    
    $conn->close();
    return ['success' => true, 'message' => 'Expiration status calculation is handled dynamically'];
}

/**
 * Clean up expired activities from carts (maintenance function)
 */
function cleanupExpiredActivitiesFromAllCarts() {
    // This would be called by a cron job or maintenance script
    // For now, cleanup happens when users access their cart
    return ['success' => true, 'message' => 'Cart cleanup happens automatically when users access their cart'];
}

/**
 * Enhanced search with expiration filters
 */
function searchActivitiesWithFilters($search_params) {
    $filters = [];
    
    if (isset($search_params['search'])) {
        $filters['search'] = $search_params['search'];
    }
    
    if (isset($search_params['show_expired']) && !$search_params['show_expired']) {
        $filters['active_only'] = true;
    }
    
    if (isset($search_params['expired_only']) && $search_params['expired_only']) {
        $filters['expired_only'] = true;
    }
    
    return getActivitiesWithExpiration($filters);
}

/**
 * Get upcoming expirations (for admin dashboard)
 */
function getUpcomingExpirations($days_ahead = 7) {
    $conn = getActivityDBConnection();
    if ($conn->connect_error) {
        return ['success' => false, 'message' => 'Database connection error'];
    }
    
    $expiration_manager = new ActivityExpirationManager($conn);
    
    $sql = "SELECT * FROM activites ORDER BY date_creation DESC";
    $result = $conn->query($sql);
    
    $upcoming_expirations = [];
    
    while ($row = $result->fetch_assoc()) {
        $days_until = $expiration_manager->getDaysUntilExpiration($row['date_ou_periode']);
        
        if ($days_until !== null && $days_until >= 0 && $days_until <= $days_ahead) {
            $row['days_until_expiration'] = $days_until;
            $upcoming_expirations[] = $row;
        }
    }
    
    $conn->close();
    
    // Sort by days until expiration
    usort($upcoming_expirations, function($a, $b) {
        return $a['days_until_expiration'] - $b['days_until_expiration'];
    });
    
    return ['success' => true, 'activities' => $upcoming_expirations];
}

/**
 * Helper function to format expiration message
 */
function getExpirationMessage($date_ou_periode) {
    $conn = getActivityDBConnection();
    $expiration_manager = new ActivityExpirationManager($conn);
    
    $days_until = $expiration_manager->getDaysUntilExpiration($date_ou_periode);
    
    if ($days_until === null) {
        return "Aucune date d'expiration";
    }
    
    if ($days_until < 0) {
        $days_expired = abs($days_until);
        if ($days_expired == 1) {
            return "Expiré depuis 1 jour";
        } else {
            return "Expiré depuis {$days_expired} jours";
        }
    }
    
    if ($days_until == 0) {
        return "Expire aujourd'hui";
    }
    
    if ($days_until == 1) {
        return "Expire demain";
    }
    
    if ($days_until <= 7) {
        return "Expire dans {$days_until} jours";
    }
    
    return "Expire dans {$days_until} jours";
}

?>