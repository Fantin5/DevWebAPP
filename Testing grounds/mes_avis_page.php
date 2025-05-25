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
$dbname = "activity";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
$user_conn = new mysqli($servername, $username, $password, "user_db");

// Check connection
if ($conn->connect_error) {
    die("Échec de la connexion à la base de données: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle review update
if (isset($_POST['update_review'])) {
    $review_id = $_POST['review_id'];
    $new_rating = $_POST['rating'];
    $new_comment = $_POST['comment'];
    
    $update_sql = "UPDATE evaluations SET note = ?, commentaire = ?, updated_at = NOW() WHERE id = ? AND utilisateur_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("isii", $new_rating, $new_comment, $review_id, $user_id);
    
    if ($update_stmt->execute()) {
        $message = "Votre avis a été mis à jour avec succès !";
        $message_type = "success";
    } else {
        $message = "Erreur lors de la mise à jour de votre avis.";
        $message_type = "error";
    }
    $update_stmt->close();
}

// Handle review deletion
if (isset($_POST['delete_review'])) {
    $review_id = $_POST['review_id'];
    
    $delete_sql = "DELETE FROM evaluations WHERE id = ? AND utilisateur_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("ii", $review_id, $user_id);
    
    if ($delete_stmt->execute()) {
        $message = "Votre avis a été supprimé avec succès.";
        $message_type = "success";
    } else {
        $message = "Erreur lors de la suppression de votre avis.";
        $message_type = "error";
    }
    $delete_stmt->close();
}

// Get user's reviews with activity information
$reviews_sql = "SELECT e.*, a.titre as activity_title, a.image_url, a.date_ou_periode,
                GROUP_CONCAT(DISTINCT td.display_name SEPARATOR ', ') as tags
                FROM evaluations e 
                JOIN activites a ON e.activite_id = a.id
                LEFT JOIN activity_tags at ON a.id = at.activity_id
                LEFT JOIN tag_definitions td ON at.tag_definition_id = td.id
                WHERE e.utilisateur_id = ? 
                GROUP BY e.id
                ORDER BY e.date_evaluation DESC";

$reviews_stmt = $conn->prepare($reviews_sql);
$reviews_stmt->bind_param("i", $user_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

$user_reviews = [];
while ($row = $reviews_result->fetch_assoc()) {
    $user_reviews[] = $row;
}

$reviews_stmt->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Avis | Synapse</title>
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
        }
        
        body {
            background: linear-gradient(135deg, rgba(69, 161, 99, 0.05) 0%, rgba(233, 196, 106, 0.05) 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }
        
        .reviews-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
        }
        
        .page-title {
            color: var(--primary-color);
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 18px;
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: var(--primary-color);
            color: white;
            padding: 12px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 30px;
            box-shadow: 0 8px 20px rgba(69, 161, 99, 0.3);
        }
        
        .back-button:hover {
            background: #3abd7a;
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(69, 161, 99, 0.4);
        }
        
        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 30px;
        }
        
        .review-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: all 0.3s ease;
            border-left: 5px solid var(--primary-color);
            position: relative;
        }
        
        .review-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .review-card-header {
            position: relative;
            height: 200px;
            overflow: hidden;
        }
        
        .activity-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .review-card:hover .activity-image {
            transform: scale(1.05);
        }
        
        .activity-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
            color: white;
            padding: 20px;
        }
        
        .activity-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .activity-date {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .review-content {
            padding: 25px;
        }
        
        .review-rating {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .stars {
            display: flex;
            gap: 3px;
            color: #f1c40f;
            font-size: 18px;
        }
        
        .review-date {
            color: var(--text-secondary);
            font-size: 12px;
            margin-left: auto;
        }
        
        .review-comment {
            background: rgba(69, 161, 99, 0.05);
            border-left: 3px solid var(--primary-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            line-height: 1.6;
            color: var(--text-primary);
        }
        
        .review-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-edit {
            background: var(--accent-color);
            color: white;
        }
        
        .btn-edit:hover {
            background: #e67e22;
            transform: translateY(-2px);
        }
        
        .btn-delete {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-delete:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .activity-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .tag {
            background: rgba(69, 161, 99, 0.1);
            color: var(--primary-color);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .no-reviews {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-secondary);
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .no-reviews i {
            font-size: 64px;
            color: var(--primary-color);
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        .no-reviews h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        
        .no-reviews p {
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        .cta-button {
            background: var(--primary-color);
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(69, 161, 99, 0.3);
        }
        
        .cta-button:hover {
            background: #3abd7a;
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(69, 161, 99, 0.4);
        }
        
        /* Modal styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(5px);
        }
        
        .modal {
            background: white;
            border-radius: 20px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
            position: relative;
        }
        
        .modal-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .modal-title {
            color: var(--primary-color);
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .star-rating {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-bottom: 20px;
            flex-direction: row-reverse;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            font-size: 32px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .star-rating label:hover,
        .star-rating label.active,
        .star-rating input:checked ~ label {
            color: #f1c40f;
            transform: scale(1.1);
        }
        
        .form-textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border: 2px solid rgba(69, 161, 99, 0.1);
            border-radius: 12px;
            font-family: inherit;
            font-size: 16px;
            resize: vertical;
            transition: border-color 0.3s ease;
        }
        
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(69, 161, 99, 0.2);
        }
        
        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
        }
        
        .btn-modal {
            padding: 12px 24px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-save {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-save:hover {
            background: #3abd7a;
        }
        
        .btn-cancel {
            background: #f1f1f1;
            color: var(--text-secondary);
        }
        
        .btn-cancel:hover {
            background: #e1e1e1;
        }
        
        .message {
            padding: 18px 24px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideIn 0.5s ease-out;
        }
        
        .message.success {
            background: rgba(69, 161, 99, 0.1);
            border-left: 5px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .message.error {
            background: rgba(231, 76, 60, 0.1);
            border-left: 5px solid var(--danger-color);
            color: var(--danger-color);
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @media (max-width: 768px) {
            .reviews-grid {
                grid-template-columns: 1fr;
            }
            
            .review-actions {
                flex-direction: column;
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

    <div class="reviews-container">
        <a href="mon-espace.php" class="back-button">
            <i class="fa-solid fa-arrow-left"></i> Retour à mon espace
        </a>
        
        <div class="page-header">
            <h1 class="page-title">Mes Avis & Évaluations</h1>
            <p class="page-subtitle">Gérez vos avis sur les activités auxquelles vous avez participé</p>
        </div>

        <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
            <?php echo $message; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($user_reviews)): ?>
        <div class="no-reviews">
            <i class="fa-regular fa-comments"></i>
            <h3>Aucun avis pour le moment</h3>
            <p>Vous n'avez pas encore laissé d'avis sur les activités. Participez à des activités et partagez vos expériences !</p>
            <a href="activites-a-evaluer.php" class="cta-button">
                <i class="fa-solid fa-star"></i> Découvrir les activités à évaluer
            </a>
        </div>
        <?php else: ?>
        <div class="reviews-grid">
            <?php foreach ($user_reviews as $review): ?>
            <div class="review-card">
                <div class="review-card-header">
                    <?php if (!empty($review['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($review['image_url']); ?>" alt="<?php echo htmlspecialchars($review['activity_title']); ?>" class="activity-image">
                    <?php else: ?>
                        <div class="activity-image" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px;">
                            <i class="fa-solid fa-image"></i>
                        </div>
                    <?php endif; ?>
                    
                    <div class="activity-overlay">
                        <div class="activity-title"><?php echo htmlspecialchars($review['activity_title']); ?></div>
                        <?php if (!empty($review['date_ou_periode'])): ?>
                        <div class="activity-date">
                            <i class="fa-regular fa-calendar"></i> <?php echo htmlspecialchars($review['date_ou_periode']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="review-content">
                    <div class="review-rating">
                        <div class="stars">
                            <?php
                            $rating = intval($review['note']);
                            for ($i = 1; $i <= 5; $i++) {
                                if ($i <= $rating) {
                                    echo '<i class="fa-solid fa-star"></i>';
                                } else {
                                    echo '<i class="fa-regular fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <div class="review-date">
                            <i class="fa-regular fa-clock"></i> 
                            <?php echo date('d/m/Y', strtotime($review['date_evaluation'])); ?>
                        </div>
                    </div>
                    
                    <div class="review-comment">
                        <?php echo nl2br(htmlspecialchars($review['commentaire'])); ?>
                    </div>
                    
                    <?php if (!empty($review['tags'])): ?>
                    <div class="activity-tags">
                        <?php 
                        $tags = explode(', ', $review['tags']);
                        foreach ($tags as $tag): ?>
                            <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="review-actions">
                        <button class="btn btn-edit" onclick="editReview(<?php echo $review['id']; ?>, <?php echo $review['note']; ?>, '<?php echo addslashes($review['commentaire']); ?>')">
                            <i class="fa-solid fa-pen"></i> Modifier
                        </button>
                        <button class="btn btn-delete" onclick="deleteReview(<?php echo $review['id']; ?>, '<?php echo addslashes($review['activity_title']); ?>')">
                            <i class="fa-solid fa-trash"></i> Supprimer
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Edit Review Modal -->
    <div id="edit-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Modifier mon avis</h3>
            </div>
            
            <form method="post" id="edit-form">
                <input type="hidden" name="review_id" id="edit-review-id">
                <input type="hidden" name="update_review" value="1">
                
                <div class="form-group">
                    <label class="form-label">Note</label>
                    <div class="star-rating" id="edit-star-rating">
                        <input type="radio" name="rating" value="5" id="edit-star5">
                        <label for="edit-star5"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="4" id="edit-star4">
                        <label for="edit-star4"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="3" id="edit-star3">
                        <label for="edit-star3"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="2" id="edit-star2">
                        <label for="edit-star2"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="1" id="edit-star1">
                        <label for="edit-star1"><i class="fa-solid fa-star"></i></label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="edit-comment">Commentaire</label>
                    <textarea name="comment" id="edit-comment" class="form-textarea" placeholder="Partagez votre expérience..." required></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeEditModal()">Annuler</button>
                    <button type="submit" class="btn-modal btn-save">Sauvegarder</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="modal-overlay">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" style="color: var(--danger-color);">Confirmer la suppression</h3>
            </div>
            
            <p style="text-align: center; margin-bottom: 25px; color: var(--text-secondary);">
                Êtes-vous sûr de vouloir supprimer votre avis sur <strong id="delete-activity-name"></strong> ? Cette action est irréversible.
            </p>
            
            <form method="post" id="delete-form">
                <input type="hidden" name="review_id" id="delete-review-id">
                <input type="hidden" name="delete_review" value="1">
                
                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeDeleteModal()">Annuler</button>
                    <button type="submit" class="btn-modal" style="background: var(--danger-color); color: white;">Supprimer</button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>

    <script>
    // Star rating functionality
    function initStarRating() {
        const starRating = document.getElementById('edit-star-rating');
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

    function editReview(reviewId, rating, comment) {
        document.getElementById('edit-review-id').value = reviewId;
        document.getElementById('edit-comment').value = comment;
        document.getElementById('edit-star' + rating).checked = true;
        
        // Highlight the selected stars
        const stars = document.querySelectorAll('#edit-star-rating label');
        stars.forEach((star, index) => {
            if (index >= stars.length - rating) {
                star.classList.add('active');
            } else {
                star.classList.remove('active');
            }
        });
        
        document.getElementById('edit-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeEditModal() {
        document.getElementById('edit-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    function deleteReview(reviewId, activityName) {
        document.getElementById('delete-review-id').value = reviewId;
        document.getElementById('delete-activity-name').textContent = activityName;
        document.getElementById('delete-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function closeDeleteModal() {
        document.getElementById('delete-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initStarRating();
        
        // Close modals when clicking outside
        document.querySelectorAll('.modal-overlay').forEach(overlay => {
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    overlay.style.display = 'none';
                    document.body.style.overflow = 'auto';
                }
            });
        });
        
        // Auto-hide success/error messages
        const message = document.querySelector('.message');
        if (message) {
            setTimeout(() => {
                message.style.opacity = '0';
                message.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    message.style.display = 'none';
                }, 500);
            }, 5000);
        }
    });
    </script>
</body>
</html>

<?php
$conn->close();
$user_conn->close();
?>