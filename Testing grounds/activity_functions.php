<?php
/**
 * Consolidated Activity Management Functions
 * This file consolidates the functionality from check_user_status.php, 
 * process_registration.php, and cleanup_cart.php into existing workflow
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
 * Check user status for an activity (ownership, registration, login)
 * @param int $activity_id
 * @param int $user_id (optional, uses session if not provided)
 * @return array Status information
 */
function checkUserActivityStatus($activity_id, $user_id = null) {
    $response = [
        'logged_in' => false,
        'is_owner' => false,
        'is_registered' => false,
        'can_purchase' => false
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

    // Get activity details
    $activity_sql = "SELECT description FROM activites WHERE id = ?";
    $activity_stmt = $conn->prepare($activity_sql);
    $activity_stmt->bind_param("i", $activity_id);
    $activity_stmt->execute();
    $activity_result = $activity_stmt->get_result();
    
    if ($activity_result->num_rows > 0) {
        $activity = $activity_result->fetch_assoc();
        
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
    
    // User can purchase if logged in, not owner, and not already registered
    $response['can_purchase'] = $response['logged_in'] && !$response['is_owner'] && !$response['is_registered'];
    
    return $response;
}

/**
 * Register user for an activity (only for FREE activities)
 * @param int $activity_id
 * @param int $user_id (optional, uses session if not provided)
 * @return array Result of registration attempt
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
 * Unregister user from an activity
 * @param int $activity_id
 * @param int $user_id (optional, uses session if not provided)
 * @return array Result of unregistration attempt
 */
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

/**
 * Clean up cart by removing activities user can't purchase
 * @param array $cart_items Array of cart items with 'id' and 'title'
 * @param int $user_id (optional, uses session if not provided)
 * @return array Items to remove and reasons
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
        
        if ($status['is_registered']) {
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
 * Validate if user can add activity to cart
 * @param int $activity_id
 * @param int $user_id (optional, uses session if not provided)
 * @return array Validation result
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
    
    return ['success' => true, 'message' => 'L\'activité peut être ajoutée au panier.'];
}

/**
 * Validate entire cart before proceeding to payment
 * @param array $cart_items Array of cart items
 * @param int $user_id (optional, uses session if not provided)
 * @return array Validation result
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

    foreach ($cart_items as $cart_item) {
        if (!isset($cart_item['id']) || !is_numeric($cart_item['id'])) {
            continue;
        }
        
        $activity_id = intval($cart_item['id']);
        $activity_title = isset($cart_item['title']) ? $cart_item['title'] : 'Activité inconnue';
        
        $status = checkUserActivityStatus($activity_id, $user_id);
        
        if ($status['is_registered']) {
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

    if (!empty($invalid_items)) {
        $reasons = array_map(function($item) {
            return $item['title'] . ': ' . $item['reason'];
        }, $invalid_items);
        
        return [
            'success' => false,
            'message' => 'Certaines activités ne peuvent pas être achetées: ' . implode(', ', $reasons),
            'invalid_items' => $invalid_items
        ];
    }

    return ['success' => true, 'message' => 'Le panier est valide pour le paiement.'];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($_GET['action'])) {
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
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        exit;
    }
}
?>