<?php
@include 'config.php';
session_start();

// Import the necessary PHPMailer classes at the top level
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$message_type = '';

// If user is already logged in, redirect to main page
if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true){
    header('Location: ../Testing grounds/main.php');
    exit();
}

// Process form submission
if(isset($_POST['submit'])){
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Check if email exists in database
    $select = "SELECT * FROM user_form WHERE email = '$email'";
    $result = mysqli_query($conn, $select);
    
    if(mysqli_num_rows($result) > 0){
        // Define reset_link before using it
        $token = bin2hex(random_bytes(32));
        $expires = time() + 3600; // Token expires in 1 hour
        
        // Build the reset link - use absolute URLs
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?email=" . urlencode($email) . "&token=" . $token;
        
        // Store token in database
        $update = "UPDATE user_form SET reset_token = '$token', reset_expires = '$expires' WHERE email = '$email'";
        mysqli_query($conn, $update);
        
        // Send email using PHPMailer
        require '../includes/PHPMailer/Exception.php';
        require '../includes/PHPMailer/PHPMailer.php';
        require '../includes/PHPMailer/SMTP.php';
        
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Change to your SMTP host
            $mail->SMTPAuth = true;
            $mail->Username = 'synapsentreprise@gmail.com'; // Your email
            $mail->Password = 'zasd rssc mbsy rnag'; // Your password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8'; // Ensure proper encoding
            
            // Recipients
            $mail->setFrom('synapsentreprise@gmail.com', 'Synapse');
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Réinitialisation de votre mot de passe Synapse';
            
            // Email body with properly formatted link
            $email_body = '
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #828977; color: white; padding: 10px; text-align: center; }
                    .content { padding: 20px; background-color: #f9f9f9; }
                    .button { display: inline-block; padding: 10px 20px; background-color: #828977; color: white; text-decoration: none; border-radius: 5px; }
                    .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #666; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="header">
                        <h2>Réinitialisation de mot de passe</h2>
                    </div>
                    <div class="content">
                        <p>Bonjour,</p>
                        <p>Vous avez demandé une réinitialisation de votre mot de passe sur Synapse.</p>
                        <p>Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe. Ce lien expirera dans 1 heure.</p>
                        <p><a href="' . $reset_link . '" style="display: inline-block; padding: 10px 20px; background-color: #828977; color: white; text-decoration: none; border-radius: 5px;">Réinitialiser mon mot de passe</a></p>
                        <p>Si le bouton ne fonctionne pas, vous pouvez également copier et coller ce lien dans votre navigateur:</p>
                        <p>' . $reset_link . '</p>
                        <p>Si vous n\'avez pas demandé cette réinitialisation, veuillez ignorer cet email.</p>
                    </div>
                    <div class="footer">
                        <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
                    </div>
                </div>
            </body>
            </html>';
            
            $mail->Body = $email_body;
            $mail->AltBody = 'Bonjour, vous avez demandé une réinitialisation de votre mot de passe sur Synapse. Voici votre lien de réinitialisation : ' . $reset_link;
            
            $mail->send();
            $message = "Un email de récupération a été envoyé à $email.";
            $message_type = 'success';
            
        } catch (Exception $e) {
            $message = "Erreur lors de l'envoi de l'email: " . $mail->ErrorInfo;
            $message_type = 'error';
        }
    } else {
        $message = "Aucun compte trouvé avec cet email.";
        $message_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Mot de passe oublié | Synapse</title>
   
   <!-- Inclure les styles globaux -->
   <link rel="stylesheet" href="../TEMPLATE/Nouveauhead.css">
   
   <!-- Inclure Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
   
   <!-- Styles spécifiques à la page de connexion -->
   <link rel="stylesheet" href="Connexion.css">
   
   <style>
      .success-message {
         color: #45a163;
         text-align: center;
         margin-bottom: 20px;
      }
      
      .error-message {
         color: #e74c3c;
         text-align: center;
         margin-bottom: 20px;
      }
      
      .forgot-password-container {
         max-width: 500px;
         margin: 0 auto;
         padding: 30px;
         text-align: center;
      }
      
      .forgot-password-title {
         font-size: 1.5em;
         margin-bottom: 20px;
         color: #828977;
      }
      
      .forgot-password-description {
         margin-bottom: 30px;
         color: #828977;
      }
      
      .back-to-login {
         display: block;
         margin-top: 20px;
         color: #828977;
         text-decoration: none;
      }
      
      .back-to-login:hover {
         text-decoration: underline;
      }
   </style>
</head>
<body>
   <?php include '../TEMPLATE/Nouveauhead.php'; ?>
    
   <main class="login-page-main">
      <div class="forgot-password-container">
         <h2 class="forgot-password-title">Récupération du mot de passe</h2>
         <p class="forgot-password-description">Entrez votre adresse email pour recevoir un lien de réinitialisation de mot de passe.</p>
         
         <?php if(!empty($message)): ?>
            <p class="<?php echo $message_type; ?>-message"><?php echo $message; ?></p>
         <?php endif; ?>
         
         <form action="" method="post">
            <div class="formulaire">
               <div class="column column--gap">
                  <div class="curved-container">
                     <span class="label">E-mail</span>
                     <input type="email" name="email" required class="input-zone">
                  </div>
               </div>
               <input type="submit" name="submit" value="Envoyer le lien" class="button">
            </div>
         </form>
         
         <a href="login_form.php" class="back-to-login">Retour à la connexion</a>
      </div>
   </main>

   <?php include '../TEMPLATE/footer.php'; ?>
</body>
</html>