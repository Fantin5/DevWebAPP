<?php
// Start a session
session_start();

// Import PHPMailer classes at the top level
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include necessary files
include_once '../Connexion-Inscription/config.php';

// Check if user is logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$is_logged_in = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Email processing
$message_status = '';
$message_text = '';

// Process the contact form if submitted and user is logged in
if (isset($_POST['submit_contact']) && $is_logged_in) {
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $user_id = $_SESSION['user_id'];
    $user_email = $_SESSION['user_email'];
    $user_name = $_SESSION['user_name'];
    $user_first_name = $_SESSION['user_first_name'];
    
    // Validate inputs
    if (empty($subject) || empty($message)) {
        $message_status = 'error';
        $message_text = 'Veuillez remplir tous les champs.';
    } else {
        // Save the question to the database
        $insert_query = "INSERT INTO faq_questions (user_id, user_name, user_first_name, user_email, subject, question, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("isssss", $user_id, $user_name, $user_first_name, $user_email, $subject, $message);
        
        if ($stmt->execute()) {
            $message_status = 'success';
            $message_text = 'Votre question a été soumise avec succès ! Elle sera examinée par notre équipe.';
            
            // Envoyer un e-mail de notification aux administrateurs
            require '../includes/PHPMailer/Exception.php';
            require '../includes/PHPMailer/PHPMailer.php';
            require '../includes/PHPMailer/SMTP.php';
            
            $mail = new PHPMailer(true);
            
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'synapsentreprise@gmail.com'; 
                $mail->Password = 'zasd rssc mbsy rnag';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->CharSet = 'UTF-8';
                
                // Recipients
                $mail->setFrom($user_email, $user_first_name . ' ' . $user_name);
                $mail->addAddress('synapsentreprise@gmail.com', 'Synapse');
                $mail->addReplyTo($user_email, $user_first_name . ' ' . $user_name);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Nouvelle question FAQ: ' . $subject;
                
                $mail->Body = '
                <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #828977; color: white; padding: 10px; text-align: center; }
                        .content { padding: 20px; background-color: #f9f9f9; }
                        .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="container">
                        <div class="header">
                            <h2>Nouvelle question FAQ reçue</h2>
                        </div>
                        <div class="content">
                            <p><strong>De:</strong> ' . $user_first_name . ' ' . $user_name . ' (' . $user_email . ')</p>
                            <p><strong>Sujet:</strong> ' . $subject . '</p>
                            <p><strong>Question:</strong></p>
                            <p>' . nl2br($message) . '</p>
                            <p><a href="' . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/Admin/admin_faq.php" . '" style="display: inline-block; padding: 10px 20px; background-color: #828977; color: white; text-decoration: none; border-radius: 5px;">Gérer les questions FAQ</a></p>
                        </div>
                        <div class="footer">
                            <p>Ce message a été envoyé automatiquement. Merci de ne pas y répondre.</p>
                        </div>
                    </div>
                </body>
                </html>';
                
                $mail->AltBody = "Nouvelle question FAQ\n\n" .
                               "De: " . $user_first_name . " " . $user_name . " (" . $user_email . ")\n\n" .
                               "Sujet: " . $subject . "\n\n" .
                               "Question:\n" . $message . "\n\n" .
                               "Connectez-vous à l'interface d'administration pour la gérer.";
                
                $mail->send();
            } catch (Exception $e) {
                // Ne rien faire, l'email de notification n'est pas critique
            }
        } else {
            $message_status = 'error';
            $message_text = "Une erreur s'est produite. Veuillez réessayer.";
        }
    }
}

// Fetch approved public FAQ questions
$faq_query = "SELECT q.*, 
             DATE_FORMAT(q.updated_at, '%d/%m/%Y') as formatted_date 
             FROM faq_questions q 
             WHERE q.status = 'approved' AND q.public = 1 
             ORDER BY q.id ASC";
$faq_result = $conn->query($faq_query);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synapse.FAQ</title>
    <link rel="stylesheet" href="faq.css">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .admin-badge {
            display: inline-block;
            font-size: 12px;
            background-color: #828977;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            margin-left: 10px;
        }
        
        .superadmin-badge {
            background-color: #5a6157;
            font-weight: bold;
        }
        
        .answer-info {
            font-size: 14px;
            color: #666;
            margin-top: 15px;
            font-style: italic;
            text-align: right;
        }
        
        .faq-item.user-question {
            border-left: 4px solid #828977;
        }
        
        .faq-item.user-question .question {
            background-color: #f5f8f5;
        }
    </style>
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>
    
    <!-- Page content container with title -->
    <div class="page-container">
        <div class="faq-page-title">
            <h1>Synapse FAQ</h1>
        </div>
        
        <!-- Contenu FAQ with accordion style -->
        <section class="faq">
            <!-- Static FAQ Items -->
            <div class="faq-item">
                <h2 class="question">Qu'est-ce que Synapse ?</h2>
                <p class="answer">Synapse est une plateforme qui met en relation des particuliers et des professionnels pour organiser, promouvoir et participer à des activités de tout type : sportives, culturelles, musicales, etc.</p>
            </div>
            
            <div class="faq-item">
                <h2 class="question">Qui peut créer une activité ?</h2>
                <p class="answer">Tout le monde peut créer une activité, que vous soyez un particulier passionné ou un professionnel. Il suffit de créer un compte et de remplir les informations nécessaires.</p>
            </div>
            
            <div class="faq-item">
                <h2 class="question">Est-ce que je peux limiter le nombre de participants ?</h2>
                <p class="answer">Oui, chaque activité peut avoir un nombre de places limité. Une fois la limite atteinte, les utilisateurs peuvent s'inscrire sur une liste d'attente.</p>
            </div>
            
            <div class="faq-item">
                <h2 class="question">Puis-je organiser une activité privée ?</h2>
                <p class="answer">Absolument. Lors de la création, vous pouvez choisir de rendre l'activité privée, accessible uniquement via invitation ou lien direct.</p>
            </div>
            
            <div class="faq-item">
                <h2 class="question">Comment trouver une activité qui m'intéresse ?</h2>
                <p class="answer">Vous pouvez utiliser notre moteur de recherche avec filtres (type d'activité, lieu, date, etc.) pour trouver rapidement ce qui vous correspond.</p>
            </div>
            
            <div class="faq-item">
                <h2 class="question">Est-il possible de se désinscrire d'une activité ?</h2>
                <p class="answer">Oui, vous pouvez vous désinscrire à tout moment depuis votre espace personnel, sauf si une politique d'annulation spécifique a été définie par l'organisateur.</p>
            </div>
            
            <div class="faq-item">
                <h2 class="question">L'inscription aux activités est-elle gratuite ?</h2>
                <p class="answer">Certaines activités sont gratuites, d'autres sont payantes selon les choix de l'organisateur. Toutes les informations sont indiquées avant l'inscription.</p>
            </div>
            
            <div class="faq-item">
                <h2 class="question">Comment puis-je contacter l'organisateur ?</h2>
                <p class="answer">Une fois inscrit(e), vous aurez accès à une messagerie ou aux coordonnées de l'organisateur pour toute question ou demande spécifique.</p>
            </div>
            
            <!-- Dynamic FAQ Items from user submissions -->
            <?php if($faq_result && $faq_result->num_rows > 0): ?>
                <?php while($faq = $faq_result->fetch_assoc()): ?>
                    <div class="faq-item user-question">
                        <h2 class="question"><?php echo htmlspecialchars($faq['subject']); ?></h2>
                        <div class="answer">
                            <p><?php echo nl2br(htmlspecialchars($faq['question'])); ?></p>
                            <hr style="margin: 15px 0; border: 0; border-top: 1px solid #eee;">
                            <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                            <div class="answer-info">
                                Répondu par : <?php echo htmlspecialchars($faq['admin_first_name'] . ' ' . $faq['admin_name']); ?>
                                <?php if($faq['admin_type'] == 1): ?>
                                    <span class="admin-badge superadmin-badge">Super Admin</span>
                                <?php else: ?>
                                    <span class="admin-badge">Admin</span>
                                <?php endif; ?>
                                <br>
                                <small>Le <?php echo $faq['formatted_date']; ?></small>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </section>
        
        <!-- Section de contact -->
        <?php if ($is_logged_in): ?>
            <section class="contact-section">
                <h2 class="contact-title">Vous avez d'autres questions ?</h2>
                <p class="contact-description">Nous vous répondrons par email et si votre question est pertinente, nous pourrons l'ajouter à notre base de données FAQ.</p>
                <?php if (!empty($message_text)): ?>
                    <div class="<?php echo $message_status; ?>-message">
                        <?php echo $message_text; ?>
                    </div>
                <?php endif; ?>
                <form class="contact-form" method="post" action="">
                    <div class="form-group">
                        <label for="subject">Sujet :</label>
                        <input type="text" id="subject" name="subject" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Votre message :</label>
                        <textarea id="message" name="message" class="form-control" required></textarea>
                    </div>
                    <button type="submit" name="submit_contact" class="submit-btn">Envoyer</button>
                </form>
            </section>
        <?php else: ?>
            <div class="login-message">
                <p>Vous avez d'autres questions ? Connectez-vous pour nous contacter directement.</p>
                <a href="../Connexion-Inscription/login_form.php" class="login-link">Se connecter</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>
    
    <!-- Add the JavaScript file for interactivity -->
    <script src="faq.js"></script>
</body>
</html>