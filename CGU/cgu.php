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
// halo

// Process the contact form if submitted and user is logged in
if (isset($_POST['submit_contact']) && $is_logged_in) {
    $subject = mysqli_real_escape_string($conn, $_POST['subject']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $user_email = $_SESSION['user_email'];
    $user_name = $_SESSION['user_name'];
    $user_first_name = $_SESSION['user_first_name'];
    
    // Validate inputs
    if (empty($subject) || empty($message)) {
        $message_status = 'error';
        $message_text = 'Veuillez remplir tous les champs.';
    } else {
        // Import PHPMailer files
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
            $mail->Subject = 'Contact depuis cgu: ' . $subject;
            
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
                        <h2>Message de contact depuis la cgu</h2>
                    </div>
                    <div class="content">
                        <p><strong>De:</strong> ' . $user_first_name . ' ' . $user_name . ' (' . $user_email . ')</p>
                        <p><strong>Sujet:</strong> ' . $subject . '</p>
                        <p><strong>Message:</strong></p>
                        <p>' . nl2br($message) . '</p>
                    </div>
                    <div class="footer">
                        <p>Ce message a été envoyé depuis le formulaire de contact de la cgu Synapse.</p>
                    </div>
                </div>
            </body>
            </html>';
            
            $mail->AltBody = "Message de: " . $user_first_name . " " . $user_name . " (" . $user_email . ")\n\n" .
                           "Sujet: " . $subject . "\n\n" .
                           "Message:\n" . $message;
            
            $mail->send();
            $message_status = 'success';
            $message_text = 'Votre message a été envoyé avec succès !';
        } catch (Exception $e) {
            $message_status = 'error';
            $message_text = "Le message n'a pas pu être envoyé. Erreur: " . $mail->ErrorInfo;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synapse.cgu</title>
    <link rel="stylesheet" href="cgu.css">
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>
    
    <!-- Page content container with title -->
    <div class="page-container">
        <div class="cgu-page-title">
            <h1>Synapse cgu</h1>
        </div>
        
        <!-- Contenu cgu with accordion style -->
        <section class="cgu">
            <div class="cgu-item">
                <h2 class="question">Qu'est-ce que Synapse ?</h2>
                <p class="answer">Synapse est une plateforme qui met en relation des particuliers et des professionnels pour organiser, promouvoir et participer à des activités de tout type : sportives, culturelles, musicales, etc.</p>
            </div>
            
            <div class="cgu-item">
                <h2 class="question">Qui peut créer une activité ?</h2>
                <p class="answer">Tout le monde peut créer une activité, que vous soyez un particulier passionné ou un professionnel. Il suffit de créer un compte et de remplir les informations nécessaires.</p>
            </div>
            
            <div class="cgu-item">
                <h2 class="question">Est-ce que je peux limiter le nombre de participants ?</h2>
                <p class="answer">Oui, chaque activité peut avoir un nombre de places limité. Une fois la limite atteinte, les utilisateurs peuvent s'inscrire sur une liste d'attente.</p>
            </div>
            
            <div class="cgu-item">
                <h2 class="question">Puis-je organiser une activité privée ?</h2>
                <p class="answer">Absolument. Lors de la création, vous pouvez choisir de rendre l'activité privée, accessible uniquement via invitation ou lien direct.</p>
            </div>
            
            <div class="cgu-item">
                <h2 class="question">Comment trouver une activité qui m'intéresse ?</h2>
                <p class="answer">Vous pouvez utiliser notre moteur de recherche avec filtres (type d'activité, lieu, date, etc.) pour trouver rapidement ce qui vous correspond.</p>
            </div>
            
            <div class="cgu-item">
                <h2 class="question">Est-il possible de se désinscrire d'une activité ?</h2>
                <p class="answer">Oui, vous pouvez vous désinscrire à tout moment depuis votre espace personnel, sauf si une politique d'annulation spécifique a été définie par l'organisateur.</p>
            </div>
            
            <div class="cgu-item">
                <h2 class="question">L'inscription aux activités est-elle gratuite ?</h2>
                <p class="answer">Certaines activités sont gratuites, d'autres sont payantes selon les choix de l'organisateur. Toutes les informations sont indiquées avant l'inscription.</p>
            </div>
            
            <div class="cgu-item">
                <h2 class="question">Comment puis-je contacter l'organisateur ?</h2>
                <p class="answer">Une fois inscrit(e), vous aurez accès à une messagerie ou aux coordonnées de l'organisateur pour toute question ou demande spécifique.</p>
            </div>
        </section>
        
        <!-- Section de contact -->
        <?php if ($is_logged_in): ?>
            <section class="contact-section">
                <h2 class="contact-title">Vous avez d'autres questions ?</h2>
                <p class="contact-description">Nous vous répondrons par email et si votre question est pertinente, nous pourrons l'ajouter à notre base de données cgu.</p>
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
    <script src="cgu.js"></script>
</body>
</html>