<?php
/**
 * Enhanced Review System for Activity Management
 * Handles all review-related operations with comprehensive validation
 */

// Error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'activity_functions.php';

class ReviewManager {
    private $conn;
    private $user_conn;
    private $expiration_manager;
    
    public function __construct() {
        $this->conn = new mysqli("localhost", "root", "", "activity");
        $this->user_conn = new mysqli("localhost", "root", "", "user_db");
        
        if ($this->conn->connect_error || $this->user_conn->connect_error) {
            throw new Exception("Database connection failed: " . $this->conn->connect_error . " " . $this->user_conn->connect_error);
        }
        
        // Initialize expiration manager for activity status checks
        $this->expiration_manager = new ActivityExpirationManager($this->conn);
    }
    
    /**
     * Submit a new review for an activity
     */
    public function submitReview($activity_id, $user_id, $rating, $comment) {
        // Comprehensive validation
        $validation = $this->validateReviewSubmission($activity_id, $user_id, $rating, $comment);
        if (!$validation['success']) {
            return $validation;
        }
        
        try {
            $this->conn->begin_transaction();
            
            // Insert review
            $sql = "INSERT INTO evaluations (activite_id, utilisateur_id, note, commentaire, date_evaluation, status) 
                    VALUES (?, ?, ?, ?, NOW(), 'approved')";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iiis", $activity_id, $user_id, $rating, $comment);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert review: " . $stmt->error);
            }
            
            $review_id = $this->conn->insert_id;
            $stmt->close();
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'message' => 'Votre avis a été publié avec succès.',
                'review_id' => $review_id
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Review submission error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Erreur lors de la publication de votre avis. Veuillez réessayer.'
            ];
        }
    }
    
    /**
     * Validate review submission
     */
    private function validateReviewSubmission($activity_id, $user_id, $rating, $comment) {
        // Check if user is logged in
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return ['success' => false, 'message' => 'Vous devez être connecté pour laisser un avis.'];
        }
        
        // Validate input parameters
        if (!is_numeric($activity_id) || !is_numeric($user_id) || !is_numeric($rating)) {
            return ['success' => false, 'message' => 'Paramètres invalides.'];
        }
        
        // Validate rating range
        if ($rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'La note doit être comprise entre 1 et 5.'];
        }
        
        // Validate comment length
        if (empty($comment) || strlen(trim($comment)) < 10) {
            return ['success' => false, 'message' => 'Le commentaire doit contenir au moins 10 caractères.'];
        }
        
        if (strlen($comment) > 1000) {
            return ['success' => false, 'message' => 'Le commentaire ne peut pas dépasser 1000 caractères.'];
        }
        
        // Check if activity exists
        $activity_sql = "SELECT id, date_ou_periode, description FROM activites WHERE id = ?";
        $activity_stmt = $this->conn->prepare($activity_sql);
        $activity_stmt->bind_param("i", $activity_id);
        $activity_stmt->execute();
        $activity_result = $activity_stmt->get_result();
        
        if ($activity_result->num_rows === 0) {
            $activity_stmt->close();
            return ['success' => false, 'message' => 'Activité introuvable.'];
        }
        
        $activity = $activity_result->fetch_assoc();
        $activity_stmt->close();
        
        // Check if user is the owner of the activity
        if (preg_match('/<!--CREATOR:([^-]+)-->/', $activity['description'], $matches)) {
            try {
                $creator_data = json_decode(base64_decode($matches[1]), true);
                if ($creator_data && isset($creator_data['user_id']) && $creator_data['user_id'] == $user_id) {
                    return ['success' => false, 'message' => 'Vous ne pouvez pas évaluer votre propre activité.'];
                }
            } catch (Exception $e) {
                // Continue if parsing fails
            }
        }
        
        // Check if user has purchased/registered for the activity
        $purchase_sql = "SELECT id, date_achat FROM activites_achats WHERE activite_id = ? AND user_id = ?";
        $purchase_stmt = $this->conn->prepare($purchase_sql);
        $purchase_stmt->bind_param("ii", $activity_id, $user_id);
        $purchase_stmt->execute();
        $purchase_result = $purchase_stmt->get_result();
        
        if ($purchase_result->num_rows === 0) {
            $purchase_stmt->close();
            return ['success' => false, 'message' => 'Vous devez être inscrit à cette activité pour pouvoir laisser un avis.'];
        }
        
        $purchase_stmt->close();
        
        // Check if user has already reviewed this activity
        $existing_review_sql = "SELECT id FROM evaluations WHERE activite_id = ? AND utilisateur_id = ?";
        $existing_stmt = $this->conn->prepare($existing_review_sql);
        $existing_stmt->bind_param("ii", $activity_id, $user_id);
        $existing_stmt->execute();
        $existing_result = $existing_stmt->get_result();
        
        if ($existing_result->num_rows > 0) {
            $existing_stmt->close();
            return ['success' => false, 'message' => 'Vous avez déjà laissé un avis pour cette activité.'];
        }
        
        $existing_stmt->close();
        
        // Reviews are always allowed regardless of expiration status
        
        return ['success' => true];
    }
    
    /**
     * Get reviews for an activity
     */
    public function getActivityReviews($activity_id, $limit = 10, $offset = 0) {
        $sql = "SELECT e.*, u.first_name, u.name, 
                CASE 
                    WHEN e.updated_at IS NOT NULL AND e.updated_at != e.date_evaluation 
                    THEN 1 
                    ELSE 0 
                END as is_edited
                FROM evaluations e 
                LEFT JOIN user_db.user_form u ON e.utilisateur_id = u.id 
                WHERE e.activite_id = ? AND e.status = 'approved'
                ORDER BY e.date_evaluation DESC 
                LIMIT ? OFFSET ?";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $activity_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            // Format user name
            $row['user_display_name'] = trim($row['first_name'] . ' ' . $row['name']);
            if (empty($row['user_display_name'])) {
                $row['user_display_name'] = 'Utilisateur anonyme';
            }
            
            // Format dates
            $row['date_formatted'] = date('d/m/Y', strtotime($row['date_evaluation']));
            if ($row['is_edited']) {
                $row['updated_formatted'] = date('d/m/Y à H:i', strtotime($row['updated_at']));
            }
            
            $reviews[] = $row;
        }
        
        $stmt->close();
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM evaluations 
                     WHERE activite_id = ? AND status = 'approved'";
        $count_stmt = $this->conn->prepare($count_sql);
        $count_stmt->bind_param("i", $activity_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total = $count_result->fetch_assoc()['total'];
        $count_stmt->close();
        
        return [
            'success' => true,
            'reviews' => $reviews,
            'total' => (int)$total,
            'has_more' => ($offset + $limit) < $total
        ];
    }
    
    /**
     * Get activity rating statistics
     */
    public function getActivityRating($activity_id) {
        $sql = "SELECT 
                AVG(note) as average_rating,
                COUNT(*) as total_reviews,
                SUM(CASE WHEN note = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN note = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN note = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN note = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN note = 1 THEN 1 ELSE 0 END) as one_star
                FROM evaluations 
                WHERE activite_id = ? AND status = 'approved'";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $activity_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        return [
            'success' => true,
            'average_rating' => $data['average_rating'] ? round($data['average_rating'], 1) : 0,
            'total_reviews' => (int)$data['total_reviews'],
            'rating_breakdown' => [
                '5' => (int)$data['five_star'],
                '4' => (int)$data['four_star'],
                '3' => (int)$data['three_star'],
                '2' => (int)$data['two_star'],
                '1' => (int)$data['one_star']
            ]
        ];
    }
    
    /**
     * Update an existing review
     */
    public function updateReview($review_id, $user_id, $rating, $comment) {
        // Validate ownership
        $ownership_sql = "SELECT e.*, a.titre FROM evaluations e 
                         JOIN activites a ON e.activite_id = a.id 
                         WHERE e.id = ? AND e.utilisateur_id = ?";
        $ownership_stmt = $this->conn->prepare($ownership_sql);
        $ownership_stmt->bind_param("ii", $review_id, $user_id);
        $ownership_stmt->execute();
        $ownership_result = $ownership_stmt->get_result();
        
        if ($ownership_result->num_rows === 0) {
            $ownership_stmt->close();
            return ['success' => false, 'message' => 'Avis introuvable ou vous n\'êtes pas autorisé à le modifier.'];
        }
        
        $review_data = $ownership_result->fetch_assoc();
        $ownership_stmt->close();
        
        // Validate input
        if (!is_numeric($rating) || $rating < 1 || $rating > 5) {
            return ['success' => false, 'message' => 'La note doit être comprise entre 1 et 5.'];
        }
        
        if (empty($comment) || strlen(trim($comment)) < 10) {
            return ['success' => false, 'message' => 'Le commentaire doit contenir au moins 10 caractères.'];
        }
        
        if (strlen($comment) > 1000) {
            return ['success' => false, 'message' => 'Le commentaire ne peut pas dépasser 1000 caractères.'];
        }
        
        try {
            $update_sql = "UPDATE evaluations 
                          SET note = ?, commentaire = ?, updated_at = NOW() 
                          WHERE id = ? AND utilisateur_id = ?";
            $update_stmt = $this->conn->prepare($update_sql);
            $update_stmt->bind_param("isii", $rating, $comment, $review_id, $user_id);
            
            if ($update_stmt->execute() && $update_stmt->affected_rows > 0) {
                $update_stmt->close();
                return [
                    'success' => true,
                    'message' => 'Votre avis a été mis à jour avec succès.'
                ];
            } else {
                $update_stmt->close();
                return ['success' => false, 'message' => 'Aucune modification détectée ou erreur lors de la mise à jour.'];
            }
            
        } catch (Exception $e) {
            error_log("Review update error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour de votre avis.'];
        }
    }
    
    /**
     * Delete a review
     */
    public function deleteReview($review_id, $user_id) {
        // Validate ownership
        $ownership_sql = "SELECT id FROM evaluations WHERE id = ? AND utilisateur_id = ?";
        $ownership_stmt = $this->conn->prepare($ownership_sql);
        $ownership_stmt->bind_param("ii", $review_id, $user_id);
        $ownership_stmt->execute();
        $ownership_result = $ownership_stmt->get_result();
        
        if ($ownership_result->num_rows === 0) {
            $ownership_stmt->close();
            return ['success' => false, 'message' => 'Avis introuvable ou vous n\'êtes pas autorisé à le supprimer.'];
        }
        
        $ownership_stmt->close();
        
        try {
            $delete_sql = "DELETE FROM evaluations WHERE id = ? AND utilisateur_id = ?";
            $delete_stmt = $this->conn->prepare($delete_sql);
            $delete_stmt->bind_param("ii", $review_id, $user_id);
            
            if ($delete_stmt->execute() && $delete_stmt->affected_rows > 0) {
                $delete_stmt->close();
                return [
                    'success' => true,
                    'message' => 'Votre avis a été supprimé avec succès.'
                ];
            } else {
                $delete_stmt->close();
                return ['success' => false, 'message' => 'Erreur lors de la suppression de votre avis.'];
            }
            
        } catch (Exception $e) {
            error_log("Review deletion error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression de votre avis.'];
        }
    }
    
    /**
     * Get user's reviews
     */
    public function getUserReviews($user_id, $limit = 20, $offset = 0) {
        $sql = "SELECT e.*, a.titre as activity_title, a.image_url, a.date_ou_periode,
                GROUP_CONCAT(DISTINCT td.display_name SEPARATOR ', ') as tags,
                CASE 
                    WHEN e.updated_at IS NOT NULL AND e.updated_at != e.date_evaluation 
                    THEN 1 
                    ELSE 0 
                END as is_edited
                FROM evaluations e 
                JOIN activites a ON e.activite_id = a.id
                LEFT JOIN activity_tags at ON a.id = at.activity_id
                LEFT JOIN tag_definitions td ON at.tag_definition_id = td.id
                WHERE e.utilisateur_id = ? 
                GROUP BY e.id
                ORDER BY e.date_evaluation DESC
                LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("iii", $user_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $row['date_formatted'] = date('d/m/Y', strtotime($row['date_evaluation']));
            if ($row['is_edited']) {
                $row['updated_formatted'] = date('d/m/Y à H:i', strtotime($row['updated_at']));
            }
            $reviews[] = $row;
        }
        
        $stmt->close();
        
        // Get total count
        $count_sql = "SELECT COUNT(*) as total FROM evaluations WHERE utilisateur_id = ?";
        $count_stmt = $this->conn->prepare($count_sql);
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $total = $count_result->fetch_assoc()['total'];
        $count_stmt->close();
        
        return [
            'success' => true,
            'reviews' => $reviews,
            'total' => (int)$total,
            'has_more' => ($offset + $limit) < $total
        ];
    }
    
    /**
     * Get activities user can review
     */
    public function getUserReviewableActivities($user_id) {
        $sql = "SELECT a.*, aa.date_achat,
                GROUP_CONCAT(DISTINCT td.display_name SEPARATOR ', ') as tags
                FROM activites a 
                JOIN activites_achats aa ON a.id = aa.activite_id
                LEFT JOIN activity_tags at ON a.id = at.activity_id
                LEFT JOIN tag_definitions td ON at.tag_definition_id = td.id
                WHERE aa.user_id = ? 
                AND a.id NOT IN (
                    SELECT activite_id FROM evaluations WHERE utilisateur_id = ?
                )
                GROUP BY a.id
                ORDER BY aa.date_achat DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $activities = [];
        while ($row = $result->fetch_assoc()) {
            // All activities can be reviewed regardless of expiration
            $row['purchase_date_formatted'] = date('d/m/Y', strtotime($row['date_achat']));
            $activities[] = $row;
        }
        
        $stmt->close();
        
        return [
            'success' => true,
            'activities' => $activities,
            'total' => count($activities)
        ];
    }
    
    /**
     * Check if user can review an activity
     */
    public function canUserReview($activity_id, $user_id) {
        // Check if user has purchased the activity
        $purchase_sql = "SELECT aa.*, a.date_ou_periode, a.description 
                        FROM activites_achats aa
                        JOIN activites a ON aa.activite_id = a.id
                        WHERE aa.activite_id = ? AND aa.user_id = ?";
        $purchase_stmt = $this->conn->prepare($purchase_sql);
        $purchase_stmt->bind_param("ii", $activity_id, $user_id);
        $purchase_stmt->execute();
        $purchase_result = $purchase_stmt->get_result();
        
        if ($purchase_result->num_rows === 0) {
            $purchase_stmt->close();
            return ['can_review' => false, 'reason' => 'not_registered'];
        }
        
        $activity_data = $purchase_result->fetch_assoc();
        $purchase_stmt->close();
        
        // Check if user has already reviewed
        $review_sql = "SELECT id FROM evaluations WHERE activite_id = ? AND utilisateur_id = ?";
        $review_stmt = $this->conn->prepare($review_sql);
        $review_stmt->bind_param("ii", $activity_id, $user_id);
        $review_stmt->execute();
        $review_result = $review_stmt->get_result();
        
        if ($review_result->num_rows > 0) {
            $review_stmt->close();
            return ['can_review' => false, 'reason' => 'already_reviewed'];
        }
        
        $review_stmt->close();
        
        // Check if user is the owner
        if (preg_match('/<!--CREATOR:([^-]+)-->/', $activity_data['description'], $matches)) {
            try {
                $creator_data = json_decode(base64_decode($matches[1]), true);
                if ($creator_data && isset($creator_data['user_id']) && $creator_data['user_id'] == $user_id) {
                    return ['can_review' => false, 'reason' => 'owner'];
                }
            } catch (Exception $e) {
                // Continue if parsing fails
            }
        }
        
        // Reviews are always allowed regardless of expiration status
        return ['can_review' => true, 'reason' => null];
    }
    
    /**
     * Get review statistics for admin dashboard
     */
    public function getReviewStatistics() {
        $sql = "SELECT 
                COUNT(*) as total_reviews,
                AVG(note) as average_rating,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_reviews,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_reviews,
                COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_reviews,
                COUNT(CASE WHEN date_evaluation >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as reviews_this_week,
                COUNT(CASE WHEN date_evaluation >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as reviews_this_month
                FROM evaluations";
        
        $result = $this->conn->query($sql);
        $stats = $result->fetch_assoc();
        
        return [
            'success' => true,
            'statistics' => [
                'total_reviews' => (int)$stats['total_reviews'],
                'average_rating' => $stats['average_rating'] ? round($stats['average_rating'], 2) : 0,
                'pending_reviews' => (int)$stats['pending_reviews'],
                'approved_reviews' => (int)$stats['approved_reviews'],
                'rejected_reviews' => (int)$stats['rejected_reviews'],
                'reviews_this_week' => (int)$stats['reviews_this_week'],
                'reviews_this_month' => (int)$stats['reviews_this_month']
            ]
        ];
    }
    
    public function __destruct() {
        if ($this->conn) {
            $this->conn->close();
        }
        if ($this->user_conn) {
            $this->user_conn->close();
        }
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        $reviewManager = new ReviewManager();
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate JSON input
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input');
        }
        
        switch ($_GET['action']) {
            case 'submit_review':
                if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
                    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté pour laisser un avis.']);
                    break;
                }
                
                // Log received data for debugging
                error_log('Received review data: ' . print_r($input, true));
                
                if (!isset($input['activity_id']) || !isset($input['rating']) || !isset($input['comment'])) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Données manquantes.',
                        'debug' => [
                            'has_activity_id' => isset($input['activity_id']),
                            'has_rating' => isset($input['rating']),
                            'has_comment' => isset($input['comment']),
                            'received_data' => $input
                        ]
                    ]);
                    break;
                }
                
                $result = $reviewManager->submitReview(
                    (int)$input['activity_id'],
                    (int)$_SESSION['user_id'],
                    (int)$input['rating'],
                    trim($input['comment'])
                );
                
                echo json_encode($result);
                break;
                
            case 'get_reviews':
                if (!isset($input['activity_id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID d\'activité manquant.']);
                    break;
                }
                
                $result = $reviewManager->getActivityReviews(
                    (int)$input['activity_id'],
                    $input['limit'] ?? 10,
                    $input['offset'] ?? 0
                );
                
                echo json_encode($result);
                break;
                
            case 'get_rating':
                if (!isset($input['activity_id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID d\'activité manquant.']);
                    break;
                }
                
                $result = $reviewManager->getActivityRating((int)$input['activity_id']);
                echo json_encode($result);
                break;
                
            case 'update_review':
                if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
                    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
                    break;
                }
                
                if (!isset($input['review_id']) || !isset($input['rating']) || !isset($input['comment'])) {
                    echo json_encode(['success' => false, 'message' => 'Données manquantes.']);
                    break;
                }
                
                $result = $reviewManager->updateReview(
                    (int)$input['review_id'],
                    (int)$_SESSION['user_id'],
                    (int)$input['rating'],
                    trim($input['comment'])
                );
                
                echo json_encode($result);
                break;
                
            case 'delete_review':
                if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
                    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
                    break;
                }
                
                if (!isset($input['review_id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID d\'avis manquant.']);
                    break;
                }
                
                $result = $reviewManager->deleteReview(
                    (int)$input['review_id'],
                    (int)$_SESSION['user_id']
                );
                
                echo json_encode($result);
                break;
                
            case 'get_user_reviews':
                if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
                    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
                    break;
                }
                
                $result = $reviewManager->getUserReviews(
                    (int)$_SESSION['user_id'],
                    $input['limit'] ?? 20,
                    $input['offset'] ?? 0
                );
                
                echo json_encode($result);
                break;
                
            case 'get_reviewable_activities':
                if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
                    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
                    break;
                }
                
                $result = $reviewManager->getUserReviewableActivities((int)$_SESSION['user_id']);
                echo json_encode($result);
                break;
                
            case 'can_user_review':
                if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
                    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
                    break;
                }
                
                if (!isset($input['activity_id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID d\'activité manquant.']);
                    break;
                }
                
                $result = $reviewManager->canUserReview(
                    (int)$input['activity_id'],
                    (int)$_SESSION['user_id']
                );
                
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            case 'get_user_reviews_count':
                if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
                    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
                    break;
                }
                
                $count_sql = "SELECT COUNT(*) as count FROM evaluations WHERE utilisateur_id = ?";
                $count_stmt = $reviewManager->conn->prepare($count_sql);
                $count_stmt->bind_param("i", $_SESSION['user_id']);
                $count_stmt->execute();
                $count_result = $count_stmt->get_result();
                $count = $count_result->fetch_assoc()['count'];
                $count_stmt->close();
                
                echo json_encode(['success' => true, 'count' => (int)$count]);
                break;
                
            case 'get_review_statistics':
                // This could be restricted to admin users
                $result = $reviewManager->getReviewStatistics();
                echo json_encode($result);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Action non valide.']);
        }
        
    } catch (Exception $e) {
        error_log("Review system error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Une erreur système est survenue. Veuillez réessayer.'
        ]);
    }
    
    exit;
}

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        $reviewManager = new ReviewManager();
        
        switch ($_GET['action']) {
            case 'get_activity_rating':
                if (!isset($_GET['activity_id']) || !is_numeric($_GET['activity_id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID d\'activité invalide.']);
                    break;
                }
                
                $result = $reviewManager->getActivityRating((int)$_GET['activity_id']);
                echo json_encode($result);
                break;
                
            case 'get_activity_reviews':
                if (!isset($_GET['activity_id']) || !is_numeric($_GET['activity_id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID d\'activité invalide.']);
                    break;
                }
                
                $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 10;
                $offset = isset($_GET['offset']) && is_numeric($_GET['offset']) ? (int)$_GET['offset'] : 0;
                
                $result = $reviewManager->getActivityReviews((int)$_GET['activity_id'], $limit, $offset);
                echo json_encode($result);
                break;
                
            case 'check_review_permission':
                if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
                    echo json_encode(['success' => false, 'message' => 'Vous devez être connecté.']);
                    break;
                }
                
                if (!isset($_GET['activity_id']) || !is_numeric($_GET['activity_id'])) {
                    echo json_encode(['success' => false, 'message' => 'ID d\'activité invalide.']);
                    break;
                }
                
                $result = $reviewManager->canUserReview(
                    (int)$_GET['activity_id'],
                    (int)$_SESSION['user_id']
                );
                
                echo json_encode(['success' => true, 'data' => $result]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Action non valide.']);
        }
        
    } catch (Exception $e) {
        error_log("Review system GET error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Une erreur système est survenue. Veuillez réessayer.'
        ]);
    }
    
    exit;
}
?>