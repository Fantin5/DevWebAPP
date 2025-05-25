<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../Connexion-Inscription/login_form.php');
    exit();
}

require_once 'activity_functions.php';

// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "activity";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Échec de la connexion à la base de données: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Get activities that user has purchased but hasn't reviewed yet
$sql = "SELECT a.*, 
        GROUP_CONCAT(DISTINCT td.display_name SEPARATOR ', ') as tags,
        aa.date_achat
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

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$activities_to_review = [];
while ($row = $result->fetch_assoc()) {
    $activities_to_review[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activités à Évaluer | Synapse</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="../TEMPLATE/Nouveauhead.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary-color: #45a163;
            --secondary-color: #828977;
            --accent-color: #ff9f67;
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
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
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
        
        .activities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }
        
        .activity-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
            border-left: 5px solid var(--accent-color);
        }
        
        .activity-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .activity-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .activity-card:hover .activity-image {
            transform: scale(1.05);
        }
        
        .placeholder-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
        }
        
        .activity-content {
            padding: 25px;
        }
        
        .activity-title {
            font-size: 20px;
            font-weight: bold;
            color: var(--text-primary);
            margin-bottom: 10px;
        }
        
        .activity-period {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .purchase-date {
            background: rgba(69, 161, 99, 0.1);
            color: var(--primary-color);
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
            display: inline-block;
        }
        
        .activity-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }
        
        .tag {
            background: rgba(69, 161, 99, 0.1);
            color: var(--primary-color);
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .review-button {
            width: 100%;
            background: linear-gradient(135deg, var(--accent-color), #e67e22);
            color: white;
            padding: 15px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 8px 20px rgba(255, 159, 103, 0.3);
        }
        
        .review-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(255, 159, 103, 0.4);
            background: linear-gradient(135deg, #e67e22, var(--accent-color));
        }
        
        .no-activities {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-secondary);
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .no-activities i {
            font-size: 64px;
            color: var(--accent-color);
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        .no-activities h3 {
            font-size: 24px;
            margin-bottom: 10px;
            color: var(--text-primary);
        }
        
        .no-activities p {
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
        
        /* Review Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            backdrop-filter: blur(10px);
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal {
            background: white;
            border-radius: 25px;
            padding: 40px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.4s ease;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(69, 161, 99, 0.1);
        }
        
        .modal-title {
            color: var(--primary-color);
            font-size: 28px;
            margin-bottom: 10px;
            font-weight: 700;
        }
        
        .modal-subtitle {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 12px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 16px;
        }
        
        .star-rating {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 25px;
            padding: 20px;
            background: rgba(69, 161, 99, 0.05);
            border-radius: 15px;
            flex-direction: row-reverse;
        }
        
        .star-rating input {
            display: none;
        }
        
        .star-rating label {
            font-size: 40px;
            color: #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .star-rating label:hover,
        .star-rating label.active,
        .star-rating input:checked ~ label {
            color: #f1c40f;
            transform: scale(1.2);
            filter: drop-shadow(0 0 10px rgba(241, 196, 15, 0.5));
        }
        
        .form-textarea {
            width: 100%;
            min-height: 150px;
            padding: 20px;
            border: 2px solid rgba(69, 161, 99, 0.1);
            border-radius: 15px;
            font-family: inherit;
            font-size: 16px;
            resize: vertical;
            transition: all 0.3s ease;
            background: rgba(69, 161, 99, 0.02);
        }
        
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(69, 161, 99, 0.2);
            background: white;
        }
        
        .modal-actions {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin-top: 30px;
        }
        
        .btn-modal {
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, var(--primary-color), #3abd7a);
            color: white;
            box-shadow: 0 8px 20px rgba(69, 161, 99, 0.3);
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(69, 161, 99, 0.4);
        }
        
        .btn-submit:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .btn-cancel {
            background: #f1f1f1;
            color: var(--text-secondary);
        }
        
        .btn-cancel:hover {
            background: #e1e1e1;
            transform: translateY(-2px);
        }
        
        .close-modal {
            position: absolute;
            top: 20px;
            right: 25px;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .close-modal:hover {
            background: rgba(69, 161, 99, 0.1);
            color: var(--primary-color);
            transform: rotate(90deg);
        }
        
        @media (max-width: 768px) {
            .activities-grid {
                grid-template-columns: 1fr;
            }
            
            .modal {
                width: 95%;
                padding: 25px;
                margin: 20px;
            }
            
            .modal-actions {
                flex-direction: column;
            }
            
            .star-rating label {
                font-size: 35px;
            }
        }
    </style>
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>

    <div class="container">
        <a href="mes-avis.php" class="back-button">
            <i class="fa-solid fa-arrow-left"></i> Retour à mes avis
        </a>
        
        <div class="page-header">
            <h1 class="page-title">Activités à Évaluer</h1>
            <p class="page-subtitle">Partagez votre expérience sur les activités auxquelles vous avez participé</p>
        </div>

        <?php if (empty($activities_to_review)): ?>
        <div class="no-activities">
            <i class="fa-solid fa-clipboard-check"></i>
            <h3>Aucune activité à évaluer</h3>
            <p>Vous avez évalué toutes vos activités ou vous n'avez pas encore participé à des activités.</p>
            <a href="activites.php" class="cta-button">
                <i class="fa-solid fa-compass"></i> Découvrir des activités
            </a>
        </div>
        <?php else: ?>
        <div class="activities-grid">
            <?php foreach ($activities_to_review as $activity): ?>
            <div class="activity-card">
                <?php if (!empty($activity['image_url'])): ?>
                    <img src="<?php echo htmlspecialchars($activity['image_url']); ?>" alt="<?php echo htmlspecialchars($activity['titre']); ?>" class="activity-image">
                <?php else: ?>
                    <div class="placeholder-image">
                        <i class="fa-solid fa-image"></i>
                    </div>
                <?php endif; ?>
                
                <div class="activity-content">
                    <div class="purchase-date">
                        <i class="fa-solid fa-calendar-check"></i> 
                        Participé le <?php echo date('d/m/Y', strtotime($activity['date_achat'])); ?>
                    </div>
                    
                    <h3 class="activity-title"><?php echo htmlspecialchars($activity['titre']); ?></h3>
                    
                    <?php if (!empty($activity['date_ou_periode'])): ?>
                    <div class="activity-period">
                        <i class="fa-regular fa-calendar"></i>
                        <?php echo htmlspecialchars($activity['date_ou_periode']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($activity['tags'])): ?>
                    <div class="activity-tags">
                        <?php 
                        $tags = explode(', ', $activity['tags']);
                        foreach ($tags as $tag): ?>
                            <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <button class="review-button" onclick="openReviewModal(<?php echo $activity['id']; ?>, '<?php echo addslashes($activity['titre']); ?>')">
                        <i class="fa-solid fa-star"></i>
                        Laisser un avis
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Review Modal -->
    <div id="review-modal" class="modal-overlay">
        <div class="modal">
            <button class="close-modal" onclick="closeReviewModal()">
                <i class="fa-solid fa-times"></i>
            </button>
            
            <div class="modal-header">
                <h3 class="modal-title">Évaluer l'activité</h3>
                <p class="modal-subtitle" id="activity-name-display"></p>
            </div>
            
            <form id="review-form">
                <input type="hidden" id="activity-id" name="activity_id">
                
                <div class="form-group">
                    <label class="form-label">Votre note</label>
                    <div class="star-rating" id="star-rating">
                        <input type="radio" name="rating" value="5" id="star5" required>
                        <label for="star5"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="4" id="star4">
                        <label for="star4"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="3" id="star3">
                        <label for="star3"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="2" id="star2">
                        <label for="star2"><i class="fa-solid fa-star"></i></label>
                        <input type="radio" name="rating" value="1" id="star1">
                        <label for="star1"><i class="fa-solid fa-star"></i></label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="comment">Votre commentaire</label>
                    <textarea 
                        name="comment" 
                        id="comment" 
                        class="form-textarea" 
                        placeholder="Partagez votre expérience avec cette activité... Qu'avez-vous aimé ? Que recommanderiez-vous aux autres participants ?"
                        required
                        minlength="10"
                        maxlength="1000"
                    ></textarea>
                    <small style="color: var(--text-secondary); font-size: 12px;">Minimum 10 caractères, maximum 1000 caractères</small>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-cancel" onclick="closeReviewModal()">
                        <i class="fa-solid fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="btn-modal btn-submit" id="submit-btn">
                        <i class="fa-solid fa-paper-plane"></i> Publier mon avis
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>

    <script>
    let currentActivityId = null;
    
    // Star rating functionality
    function initStarRating() {
        const starRating = document.getElementById('star-rating');
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

    function openReviewModal(activityId, activityName) {
        currentActivityId = activityId;
        document.getElementById('activity-id').value = activityId;
        document.getElementById('activity-name-display').textContent = activityName;
        document.getElementById('review-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Reset form
        document.getElementById('review-form').reset();
        document.querySelectorAll('#star-rating label').forEach(star => {
            star.classList.remove('active');
        });
    }

    function closeReviewModal() {
        document.getElementById('review-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
        currentActivityId = null;
    }

    function showNotification(message, type = 'success') {
        // Create notification
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            padding: 18px 30px;
            border-radius: 15px;
            font-weight: 600;
            z-index: 10000;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideDown 0.5s ease;
        `;
        
        if (type === 'success') {
            notification.style.background = 'rgba(69, 161, 99, 0.95)';
            notification.style.color = 'white';
            notification.innerHTML = `<i class="fa-solid fa-circle-check"></i> ${message}`;
        } else {
            notification.style.background = 'rgba(231, 76, 60, 0.95)';
            notification.style.color = 'white';
            notification.innerHTML = `<i class="fa-solid fa-circle-exclamation"></i> ${message}`;
        }
        
        // Add animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideDown {
                from {
                    transform: translateX(-50%) translateY(-100px);
                    opacity: 0;
                }
                to {
                    transform: translateX(-50%) translateY(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(notification);
        
        // Auto remove
        setTimeout(() => {
            notification.style.animation = 'slideDown 0.5s ease reverse';
            setTimeout(() => {
                notification.remove();
                style.remove();
            }, 500);
        }, 4000);
    }

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        initStarRating();
        
        // Handle form submission
        document.getElementById('review-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const rating = formData.get('rating');
            const comment = formData.get('comment');
            
            if (!rating) {
                showNotification('Veuillez sélectionner une note.', 'error');
                return;
            }
            
            if (comment.length < 10) {
                showNotification('Le commentaire doit contenir au moins 10 caractères.', 'error');
                return;
            }
            
            // Disable submit button
            const submitBtn = document.getElementById('submit-btn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Publication...';
            
            // Submit review
            fetch('review_system.php?action=submit_review', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    activity_id: parseInt(currentActivityId),
                    rating: parseInt(rating),
                    comment: comment
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closeReviewModal();
                    
                    // Reload page after 2 seconds to update the list
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showNotification(data.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Publier mon avis';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Une erreur est survenue lors de la publication de votre avis.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i> Publier mon avis';
            });
        });
        
        // Close modal when clicking outside
        document.getElementById('review-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReviewModal();
            }
        });
        
        // Handle ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && document.getElementById('review-modal').style.display === 'flex') {
                closeReviewModal();
            }
        });
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>