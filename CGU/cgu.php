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
            <h1>Synapse CGU</h1>
        </div>
        
        <!-- Contenu cgu with accordion style -->
        <section class="cgu">
            <div class="cgu-item">
                <h2 class="question">1. Objet des CGU</h2>
                <p class="answer">Les présentes Conditions Générales d’Utilisation régissent l’accès et l’utilisation de la plateforme Synapse, une solution en ligne permettant aux utilisateurs de trouver des terrains de sport et de se connecter avec d’autres joueurs. En utilisant le site ou l’application, vous acceptez de respecter ces conditions.</p>
            </div>
            
            <div class="cgu-item">
                <h2 class="question">2. Définitions</h2>
                <p class="answer">• Utilisateur : Toute personne inscrite ou non inscrite accédant à la plateforme.
                                  • Plateforme : Le site web Synapse.
                                  • Contenu : Toutes les informations, données, textes, graphiques, vidéos et autres éléments disponibles sur la plateforme.</p>
            </div>
            
            <div class="cgu-item">
                <h2 class="question">3. Accès à la plateforme</h2>
                <p class="answer">• L’accès est ouvert à toute personne disposant d’une connexion Internet.
                                  • Certaines fonctionnalités nécessitent la création d’un compte utilisateur.</p>
            </div>
            
            <div class="cgu-item">
                <h2 class="question">4. Création d’un compte utilisateur</h2>
                <p class="answer">Pour utiliser pleinement la plateforme, vous devez :
                                    • Remplir le formulaire d’inscription en fournissant des informations exactes et à jour.
                                    • Accepter les présentes CGU.
                                    • Conserver la confidentialité de vos identifiants.</p>
            </div>
            
            <div class="cgu-item">
                <h2 class="question">5. Utilisation de la plateforme</h2>
                <p class="answer">En utilisant FindYourCourt, vous vous engagez à :
                                    • Respecter les lois et réglementations en vigueur.
                                    • Ne pas utiliser la plateforme à des fins frauduleuses ou illégales.
                                    • Fournir des informations véridiques concernant votre profil et vos activités.</p>
            </div>
            
            <div class="cgu-item">
                <h2 class="question">6. Propriété intellectuelle</h2>
                <p class="answer">Tous les contenus de la plateforme, y compris les textes, logos, images, et codes informatiques, sont protégés par les lois sur la propriété intellectuelle. Toute reproduction, distribution ou modification non autorisée est interdite.</p>
            </div>
            
            <div class="cgu-item">
                <h2 class="question">7. Confidentialité et données personnelles</h2>
                <p class="answer">Synapse s’engage à protéger vos données et à ne pas les vendre à des tiers sans consentement.</p>
            </div>
            
            <div class="cgu-item">
                <h2 class="question">8. Responsabilité de Synapse</h2>
                <p class="answer">Synapse met tout en œuvre pour assurer un fonctionnement optimal, mais ne peut garantir une disponibilité sans interruption ni l’absence d’erreurs. Synapse décline toute responsabilité en cas de :
                                    • Mauvaise utilisation par les utilisateurs.
                                    • Pannes temporaires ou maintenance technique.</p>
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