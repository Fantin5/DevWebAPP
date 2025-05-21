<?php
// Vérification de la session (rejeter si non admin)
include 'adminVerify.php';

// Configuration de la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_db";

// Connexion à la base de données
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Message de status
$status_message = '';
$status_type = '';

// Traitement des actions
if(isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = (int)$_GET['id'];
    
    if($action === 'approve' && isset($_POST['answer'])) {
        $answer = mysqli_real_escape_string($conn, $_POST['answer']);
        $make_public = isset($_POST['make_public']) ? 1 : 0;
        
        // Mettre à jour la question avec la réponse et le statut
        $update_query = "UPDATE faq_questions SET 
                        answer = ?, 
                        admin_id = ?, 
                        admin_name = ?,
                        admin_first_name = ?,
                        admin_type = ?,
                        status = 'approved',
                        public = ?,
                        updated_at = NOW() 
                        WHERE id = ?";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ssssiis", $answer, $_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_first_name'], $_SESSION['user_type'], $make_public, $id);
        
        if($stmt->execute()) {
            $status_message = "La question a été approuvée et répondue avec succès.";
            $status_type = "success";
            
            // Rediriger pour éviter la re-soumission du formulaire
            header("Location: admin_faq.php?success=approved");
            exit();
        } else {
            $status_message = "Erreur lors de la mise à jour: " . $conn->error;
            $status_type = "error";
        }
    } 
    elseif($action === 'reject') {
        // Mettre à jour le statut à rejeté
        $update_query = "UPDATE faq_questions SET 
                        status = 'rejected',
                        admin_id = ?,
                        updated_at = NOW() 
                        WHERE id = ?";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ii", $_SESSION['user_id'], $id);
        
        if($stmt->execute()) {
            $status_message = "La question a été rejetée avec succès.";
            $status_type = "success";
            
            // Rediriger pour éviter la re-soumission
            header("Location: admin_faq.php?success=rejected");
            exit();
        } else {
            $status_message = "Erreur lors de la mise à jour: " . $conn->error;
            $status_type = "error";
        }
    }
    elseif($action === 'delete') {
        // Supprimer la question
        $delete_query = "DELETE FROM faq_questions WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $id);
        
        if($stmt->execute()) {
            $status_message = "La question a été supprimée avec succès.";
            $status_type = "success";
            
            // Rediriger pour éviter la re-soumission
            header("Location: admin_faq.php?success=deleted");
            exit();
        } else {
            $status_message = "Erreur lors de la suppression: " . $conn->error;
            $status_type = "error";
        }
    }
    elseif($action === 'toggle_public' && isset($_GET['public'])) {
        $public = (int)$_GET['public'];
        
        // Mettre à jour la visibilité
        $update_query = "UPDATE faq_questions SET 
                        public = ?,
                        updated_at = NOW() 
                        WHERE id = ?";
        
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("ii", $public, $id);
        
        if($stmt->execute()) {
            $status_message = $public ? "La question est maintenant publique." : "La question est maintenant privée.";
            $status_type = "success";
            
            // Rediriger pour éviter la re-soumission
            header("Location: admin_faq.php?success=visibility_updated");
            exit();
        } else {
            $status_message = "Erreur lors de la mise à jour: " . $conn->error;
            $status_type = "error";
        }
    }
}

// Récupérer les messages en fonction du statut filtré
$status_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

$query_condition = "";
if($status_filter === 'pending') {
    $query_condition = "WHERE q.status = 'pending'";
} elseif($status_filter === 'approved') {
    $query_condition = "WHERE q.status = 'approved'";
} elseif($status_filter === 'rejected') {
    $query_condition = "WHERE q.status = 'rejected'";
}

// Récupérer les questions
$questions_query = "SELECT q.*, 
                   DATE_FORMAT(q.created_at, '%d/%m/%Y à %H:%i') as formatted_created, 
                   DATE_FORMAT(q.updated_at, '%d/%m/%Y à %H:%i') as formatted_updated,
                   a.name as admin_name, a.first_name as admin_first_name 
                   FROM faq_questions q 
                   LEFT JOIN user_form a ON q.admin_id = a.id 
                   $query_condition 
                   ORDER BY q.created_at DESC";

$questions_result = $conn->query($questions_query);

// Messages de succès
if(isset($_GET['success'])) {
    $success_action = $_GET['success'];
    
    if($success_action === 'approved') {
        $status_message = "La question a été approuvée et répondue avec succès.";
        $status_type = "success";
    } elseif($success_action === 'rejected') {
        $status_message = "La question a été rejetée avec succès.";
        $status_type = "success";
    } elseif($success_action === 'deleted') {
        $status_message = "La question a été supprimée avec succès.";
        $status_type = "success";
    } elseif($success_action === 'visibility_updated') {
        $status_message = "La visibilité de la question a été mise à jour avec succès.";
        $status_type = "success";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration des FAQ</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            border-bottom: 2px solid #828977;
            padding-bottom: 10px;
        }
        .nav-buttons {
            margin-bottom: 20px;
        }
        .nav-buttons a {
            display: inline-block;
            padding: 8px 15px;
            margin-right: 10px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .nav-buttons a:hover {
            background-color: #45a049;
        }
        .filter-buttons {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .filter-button {
            padding: 8px 15px;
            background-color: #f0f0f0;
            color: #333;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .filter-button.active {
            background-color: #828977;
            color: white;
        }
        .question-list {
            margin-bottom: 30px;
        }
        .question-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 20px;
            transition: all 0.3s ease;
            position: relative;
        }
        .question-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .question-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .question-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        .question-meta {
            color: #777;
            font-size: 14px;
            display: flex;
            gap: 15px;
        }
        .question-content {
            margin-bottom: 20px;
            color: #555;
            line-height: 1.6;
        }
        .question-user {
            font-style: italic;
            color: #666;
            margin-top: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 10px;
        }
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        .status-approved {
            background-color: #d4edda;
            color: #155724;
        }
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-public {
            background-color: #cce5ff;
            color: #004085;
        }
        .status-private {
            background-color: #e2e3e5;
            color: #383d41;
        }
        .answer-form {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 120px;
            font-family: inherit;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-approve {
            background-color: #28a745;
            color: white;
        }
        .btn-reject {
            background-color: #dc3545;
            color: white;
        }
        .btn-delete {
            background-color: #6c757d;
            color: white;
        }
        .btn-public {
            background-color: #007bff;
            color: white;
        }
        .btn-private {
            background-color: #6c757d;
            color: white;
        }
        .answer-display {
            background-color: #f0f7f0;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #28a745;
        }
        .answer-meta {
            font-style: italic;
            color: #666;
            margin-top: 10px;
            text-align: right;
            font-size: 14px;
        }
        .admin-badge {
            display: inline-block;
            background-color: #828977;
            color: white;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 5px;
        }
        .superadmin-badge {
            background-color: #5a6157;
            font-weight: bold;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .checkbox-group input {
            margin-right: 8px;
        }
        .success-message, .error-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #777;
        }
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ccc;
        }
    </style>
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1>Gestion des Questions FAQ</h1>
        
        <div class="nav-buttons">
            <a href="admin.php">Tableau de bord</a>
            <a href="../Testing grounds/main.php">Site utilisateur</a>
            <a href="../FAQ/faq.php">Voir la FAQ</a>
        </div>
        
        <?php if(!empty($status_message)): ?>
            <div class="<?php echo $status_type; ?>-message">
                <?php echo $status_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="filter-buttons">
            <a href="admin_faq.php" class="filter-button <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                Toutes les questions
            </a>
            <a href="admin_faq.php?filter=pending" class="filter-button <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                En attente
            </a>
            <a href="admin_faq.php?filter=approved" class="filter-button <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                Approuvées
            </a>
            <a href="admin_faq.php?filter=rejected" class="filter-button <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">
                Rejetées
            </a>
        </div>
        
        <div class="question-list">
            <?php if($questions_result && $questions_result->num_rows > 0): ?>
                <?php while($question = $questions_result->fetch_assoc()): ?>
                    <div class="question-card">
                        <div class="question-header">
                            <div class="question-title">
                                <?php echo htmlspecialchars($question['subject']); ?>
                                
                                <?php if($question['status'] === 'pending'): ?>
                                    <span class="status-badge status-pending">En attente</span>
                                <?php elseif($question['status'] === 'approved'): ?>
                                    <span class="status-badge status-approved">Approuvée</span>
                                    <?php if($question['public'] == 1): ?>
                                        <span class="status-badge status-public">Publique</span>
                                    <?php else: ?>
                                        <span class="status-badge status-private">Privée</span>
                                    <?php endif; ?>
                                <?php elseif($question['status'] === 'rejected'): ?>
                                    <span class="status-badge status-rejected">Rejetée</span>
                                <?php endif; ?>
                            </div>
                            <div class="question-meta">
                                <span>ID: <?php echo $question['id']; ?></span>
                                <span>Créée le: <?php echo $question['formatted_created']; ?></span>
                                <?php if($question['updated_at']): ?>
                                    <span>Mise à jour: <?php echo $question['formatted_updated']; ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="question-content">
                            <?php echo nl2br(htmlspecialchars($question['question'])); ?>
                        </div>
                        
                        <div class="question-user">
                            De: <?php echo htmlspecialchars($question['user_first_name'] . ' ' . $question['user_name']); ?> (<?php echo htmlspecialchars($question['user_email']); ?>)
                        </div>
                        
                        <?php if($question['status'] === 'approved' && $question['answer']): ?>
                            <div class="answer-display">
                                <h3>Réponse:</h3>
                                <p><?php echo nl2br(htmlspecialchars($question['answer'])); ?></p>
                                <div class="answer-meta">
                                    Répondu par: <?php echo htmlspecialchars($question['admin_first_name'] . ' ' . $question['admin_name']); ?>
                                    <?php if($question['admin_type'] == 1): ?>
                                        <span class="admin-badge superadmin-badge">Super Admin</span>
                                    <?php else: ?>
                                        <span class="admin-badge">Admin</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="button-group">
                            <?php if($question['status'] === 'pending'): ?>
                                <a href="#answer-form-<?php echo $question['id']; ?>" class="btn btn-approve"
                                   onclick="document.getElementById('answer-form-<?php echo $question['id']; ?>').style.display = 'block';">
                                    <i class="fas fa-check"></i> Répondre
                                </a>
                                <a href="admin_faq.php?action=reject&id=<?php echo $question['id']; ?>" 
                                   class="btn btn-reject"
                                   onclick="return confirm('Êtes-vous sûr de vouloir rejeter cette question?')">
                                    <i class="fas fa-times"></i> Rejeter
                                </a>
                            <?php elseif($question['status'] === 'approved'): ?>
                                <?php if($question['public'] == 1): ?>
                                    <a href="admin_faq.php?action=toggle_public&id=<?php echo $question['id']; ?>&public=0" 
                                       class="btn btn-private"
                                       onclick="return confirm('Êtes-vous sûr de vouloir rendre cette question privée?')">
                                        <i class="fas fa-eye-slash"></i> Rendre privée
                                    </a>
                                <?php else: ?>
                                    <a href="admin_faq.php?action=toggle_public&id=<?php echo $question['id']; ?>&public=1" 
                                       class="btn btn-public"
                                       onclick="return confirm('Êtes-vous sûr de vouloir rendre cette question publique?')">
                                        <i class="fas fa-eye"></i> Rendre publique
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <a href="admin_faq.php?action=delete&id=<?php echo $question['id']; ?>" 
                               class="btn btn-delete"
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette question? Cette action est irréversible.')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </div>
                        
                        <?php if($question['status'] === 'pending'): ?>
                            <div id="answer-form-<?php echo $question['id']; ?>" class="answer-form" style="display: none;">
                                <form action="admin_faq.php?action=approve&id=<?php echo $question['id']; ?>" method="post">
                                    <div class="form-group">
                                        <label for="answer-<?php echo $question['id']; ?>">Votre réponse:</label>
                                        <textarea id="answer-<?php echo $question['id']; ?>" name="answer" required></textarea>
                                    </div>
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="make_public-<?php echo $question['id']; ?>" name="make_public" checked>
                                        <label for="make_public-<?php echo $question['id']; ?>">Rendre cette question publique sur la FAQ</label>
                                    </div>
                                    <div class="button-group">
                                        <button type="submit" class="btn btn-approve">
                                            <i class="fas fa-paper-plane"></i> Envoyer la réponse
                                        </button>
                                        <button type="button" class="btn btn-reject" onclick="document.getElementById('answer-form-<?php echo $question['id']; ?>').style.display = 'none';">
                                            <i class="fas fa-times"></i> Annuler
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Aucune question trouvée</h3>
                    <p>Il n'y a pas de questions correspondant à ce filtre pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>