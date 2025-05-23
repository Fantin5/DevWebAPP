<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Connexion-Inscription/login_form.php");
    exit();
}

// Database configurations
$user_servername = "localhost";
$user_username = "root";
$user_password = "";
$user_dbname = "user_db";

$activity_servername = "localhost";
$activity_username = "root";
$activity_password = "";
$activity_dbname = "activity";

// Create connections
$user_conn = new mysqli($user_servername, $user_username, $user_password, $user_dbname);
$activity_conn = new mysqli($activity_servername, $activity_username, $activity_password, $activity_dbname);

// Check connections
if ($user_conn->connect_error || $activity_conn->connect_error) {
    die("Connection failed");
}

$user_id = $_SESSION['user_id'];
$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_tags = isset($_POST['selected_tags']) ? $_POST['selected_tags'] : [];
    
    // Start transaction
    $user_conn->begin_transaction();
    
    try {
        // First, remove all existing tag subscriptions for this user
        $delete_sql = "DELETE FROM user_newsletter_tags WHERE user_id = ?";
        $delete_stmt = $user_conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $user_id);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        // Insert new tag subscriptions
        if (!empty($selected_tags)) {
            $insert_sql = "INSERT INTO user_newsletter_tags (user_id, tag_id) VALUES (?, ?)";
            $insert_stmt = $user_conn->prepare($insert_sql);
            
            foreach ($selected_tags as $tag_id) {
                $insert_stmt->bind_param("ii", $user_id, $tag_id);
                $insert_stmt->execute();
            }
            $insert_stmt->close();
        }
        
        $user_conn->commit();
        $message = "Vos préférences de tags ont été mises à jour avec succès!";
        $message_type = "success";
    } catch (Exception $e) {
        $user_conn->rollback();
        $message = "Erreur lors de la mise à jour de vos préférences.";
        $message_type = "error";
    }
}

// Get all available tags (excluding payment tags that are auto-assigned)
$tags_sql = "SELECT id, name, display_name FROM tag_definitions WHERE name NOT IN ('gratuit', 'payant') ORDER BY display_name";
$tags_result = $activity_conn->query($tags_sql);

// Get user's current tag subscriptions
$user_tags_sql = "SELECT tag_id FROM user_newsletter_tags WHERE user_id = ?";
$user_tags_stmt = $user_conn->prepare($user_tags_sql);
$user_tags_stmt->bind_param("i", $user_id);
$user_tags_stmt->execute();
$user_tags_result = $user_tags_stmt->get_result();

$user_selected_tags = [];
while ($row = $user_tags_result->fetch_assoc()) {
    $user_selected_tags[] = $row['tag_id'];
}
$user_tags_stmt->close();

// Check if user is subscribed to newsletter
$newsletter_check_sql = "SELECT newsletter_subscribed FROM user_form WHERE id = ?";
$newsletter_stmt = $user_conn->prepare($newsletter_check_sql);
$newsletter_stmt->bind_param("i", $user_id);
$newsletter_stmt->execute();
$newsletter_result = $newsletter_stmt->get_result();
$user_info = $newsletter_result->fetch_assoc();
$newsletter_stmt->close();

$is_subscribed = $user_info['newsletter_subscribed'] == 1;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préférences Newsletter - Tags | Synapse</title>
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
            background-color: var(--background-color);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }
        
        /* Simple header */
        .simple-header {
            background-color: var(--secondary-color);
            padding: 20px 0;
            text-align: center;
            color: white;
        }
        
        .simple-header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .container {
            width: 90%;
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            background: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .page-title {
            color: var(--secondary-color);
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert.success {
            background-color: rgba(69, 161, 99, 0.12);
            border-left: 4px solid var(--primary-color);
            color: var(--primary-color);
        }
        
        .alert.error {
            background-color: rgba(231, 76, 60, 0.12);
            border-left: 4px solid var(--danger-color);
            color: var(--danger-color);
        }
        
        .alert.warning {
            background-color: rgba(255, 159, 103, 0.12);
            border-left: 4px solid var(--accent-color);
            color: #d68910;
        }
        
        .info-section {
            background: #e9ecef;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .info-section h3 {
            margin-top: 0;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tags-section {
            margin: 30px 0;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tags-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .tag-option {
            display: flex;
            align-items: center;
            padding: 18px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f9f9f9;
            position: relative;
        }
        
        .tag-option:hover {
            border-color: var(--primary-color);
            background: rgba(69, 161, 99, 0.05);
            transform: translateY(-2px);
        }
        
        .tag-option.selected {
            border-color: var(--primary-color);
            background: rgba(69, 161, 99, 0.1);
            box-shadow: 0 4px 12px rgba(69, 161, 99, 0.2);
        }
        
        .tag-option input[type="checkbox"] {
            margin-right: 12px;
            transform: scale(1.3);
            accent-color: var(--primary-color);
        }
        
        .tag-label {
            font-weight: 500;
            color: var(--text-primary);
            flex: 1;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 40px;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            min-width: 140px;
            justify-content: center;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #3abd7a;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(69, 161, 99, 0.3);
        }
        
        .btn-secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #6b7465;
            transform: translateY(-2px);
        }
        
        .selection-summary {
            background: var(--background-color);
            padding: 15px 20px;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            margin: 20px 0;
        }
        
        .selection-count {
            font-weight: 600;
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
                padding: 20px;
            }
            
            .tags-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="simple-header">
        <h1><i class="fa-solid fa-tags"></i> SYNAPSE - Préférences Newsletter</h1>
    </div>

    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fa-solid fa-tags"></i>
                Préférences Newsletter
            </h1>
            <p class="page-subtitle">Personnalisez vos notifications d'activités</p>
        </div>
        
        <?php if (!$is_subscribed): ?>
        <div class="alert warning">
            <i class="fa-solid fa-exclamation-triangle"></i>
            <div>
                <strong>Attention!</strong> Vous n'êtes pas encore abonné à notre newsletter. 
                <a href="Compte/mon-espace.php" style="color: #d68910; text-decoration: underline;">S'abonner maintenant</a>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
        <div class="alert <?php echo $message_type; ?>">
            <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
            <div><?php echo htmlspecialchars($message); ?></div>
        </div>
        <?php endif; ?>
        
        <div class="info-section">
            <h3><i class="fa-solid fa-info-circle"></i> Comment ça marche ?</h3>
            <ul style="margin: 10px 0 0 20px; line-height: 1.6;">
                <li><strong>Sélectionnez vos centres d'intérêt</strong> parmi les tags disponibles</li>
                <li><strong>Recevez des notifications personnalisées</strong> uniquement pour les activités correspondantes</li>
                <li><strong>Aucune notification pour vos propres activités</strong> - nous évitons le spam !</li>
                <li><strong>Sans sélection</strong> = vous recevez toutes les notifications</li>
            </ul>
        </div>
        
        <form method="POST" action="">
            <div class="tags-section">
                <h2 class="section-title">
                    <i class="fa-solid fa-heart"></i>
                    Choisissez vos centres d'intérêt
                </h2>
                
                <div class="selection-summary" id="selection-summary" style="display: none;">
                    <span class="selection-count" id="selection-count">0</span> tag(s) sélectionné(s)
                </div>
                
                <div class="tags-grid">
                    <?php while ($tag = $tags_result->fetch_assoc()): ?>
                        <div class="tag-option <?php echo in_array($tag['id'], $user_selected_tags) ? 'selected' : ''; ?>" 
                             onclick="toggleTag(this, <?php echo $tag['id']; ?>)">
                            <input type="checkbox" 
                                   name="selected_tags[]" 
                                   value="<?php echo $tag['id']; ?>"
                                   <?php echo in_array($tag['id'], $user_selected_tags) ? 'checked' : ''; ?>>
                            <span class="tag-label"><?php echo htmlspecialchars($tag['display_name']); ?></span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <div class="form-actions">
                <a href="Compte/mon-espace.php" class="btn btn-secondary">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour au profil
                </a>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-save"></i>
                    Sauvegarder mes préférences
                </button>
            </div>
        </form>
    </div>

    <script>
        function toggleTag(element, tagId) {
            const checkbox = element.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                element.classList.add('selected');
            } else {
                element.classList.remove('selected');
            }
            
            updateSelectionSummary();
        }
        
        function updateSelectionSummary() {
            const selectedTags = document.querySelectorAll('.tag-option input[type="checkbox"]:checked');
            const count = selectedTags.length;
            const summary = document.getElementById('selection-summary');
            const countElement = document.getElementById('selection-count');
            
            countElement.textContent = count;
            
            if (count > 0) {
                summary.style.display = 'block';
            } else {
                summary.style.display = 'none';
            }
        }
        
        // Prevent double-toggle when clicking directly on checkbox
        document.querySelectorAll('.tag-option input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('click', function(e) {
                e.stopPropagation();
                const element = this.closest('.tag-option');
                if (this.checked) {
                    element.classList.add('selected');
                } else {
                    element.classList.remove('selected');
                }
                updateSelectionSummary();
            });
        });
        
        // Initialize selection summary
        updateSelectionSummary();
        
        // Add smooth animations
        document.addEventListener('DOMContentLoaded', function() {
            const tagOptions = document.querySelectorAll('.tag-option');
            tagOptions.forEach((option, index) => {
                option.style.opacity = '0';
                option.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    option.style.transition = 'all 0.5s ease';
                    option.style.opacity = '1';
                    option.style.transform = 'translateY(0)';
                }, 100 * index);
            });
        });
    </script>
</body>
</html>

<?php
$user_conn->close();
$activity_conn->close();
?>