<?php
@include 'config.php';
session_start();

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$message_type = '';

// Process resend verification request
if(isset($_GET['email'])){
    $email = mysqli_real_escape_string($conn, $_GET['email']);
    
    // Check if the email exists and if it's not already verified
    $select = "SELECT * FROM user_form WHERE email = '$email' AND email_verified = 0";
    $result = mysqli_query($conn, $select);
    
    if(mysqli_num_rows($result) > 0){
        $row = mysqli_fetch_array($result);
        $name = $row['name'];
        $first_name = $row['first_name'];
        
        // Generate a new verification token
        $verification_token = bin2hex(random_bytes(32));
        $verification_expires = time() + 86400; // 24 hours
        
        // Update the token in the database
        $update = "UPDATE user_form SET verification_token = '$verification_token', verification_expires = '$verification_expires' WHERE email = '$email'";
        
        if(mysqli_query($conn, $update)){
            // Create the verification link
            $verification_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify_email.php?email=" . urlencode($email) . "&token=" . $verification_token;
            
            // Send verification email
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
                $mail->setFrom('synapsentreprise@gmail.com', 'Synapse');
                $mail->addAddress($email, $first_name . ' ' . $name);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Vérification de votre compte Synapse';
                
                // Email content
                $mail->Body = '
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
                            <h2>Vérification de votre compte Synapse</h2>
                        </div>
                        <div class="content">
                            <p>Bonjour ' . $first_name . ',</p>
                            <p>Voici votre nouveau lien de vérification pour activer votre compte Synapse. Veuillez cliquer sur le bouton ci-dessous :</p>
                            <p><a href="' . $verification_link . '" style="display: inline-block; padding: 10px 20px; background-color: #828977; color: white; text-decoration: none; border-radius: 5px;">Vérifier mon email</a></p>
                            <p>Si le bouton ne fonctionne pas, vous pouvez également copier et coller ce lien dans votre navigateur :</p>
                            <p>' . $verification_link . '</p>
                            <p>Ce lien expirera dans 24 heures.</p>
                        </div>
                        <div class="footer">
                            <p>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</p>
                        </div>
                    </div>
                </body>
                </html>';
                
                $mail->AltBody = 'Bonjour ' . $first_name . ',

Voici votre nouveau lien de vérification pour activer votre compte Synapse :
' . $verification_link . '

Ce lien expirera dans 24 heures.';
                
                $mail->send();
                
                $message = "Un nouvel email de vérification a été envoyé à $email. Veuillez vérifier votre boîte de réception.";
                $message_type = 'success';
            } catch (Exception $e) {
                $message = "L'email de vérification n'a pas pu être envoyé. " . $mail->ErrorInfo;
                $message_type = 'error';
            }
        } else {
            $message = "Une erreur s'est produite. Veuillez réessayer.";
            $message_type = 'error';
        }
    } else {
        // Check if the email exists but is already verified
        $select = "SELECT * FROM user_form WHERE email = '$email' AND email_verified = 1";
        $result = mysqli_query($conn, $select);
        
        if(mysqli_num_rows($result) > 0) {
            $message = "Cet email a déjà été vérifié. Vous pouvez vous connecter.";
            $message_type = 'success';
        } else {
            $message = "Cet email n'est pas associé à un compte.";
            $message_type = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Renvoyer l'email de vérification | Synapse</title>
   
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
      
      .resend-container {
         max-width: 500px;
         margin: 0 auto;
         padding: 30px;
         text-align: center;
      }
      
      .resend-title {
         font-size: 1.5em;
         margin-bottom: 20px;
         color: #828977;
      }
      
      .resend-description {
         margin-bottom: 30px;
         color: #828977;
      }
   </style>
</head>
<body>
   <?php include '../TEMPLATE/Nouveauhead.php'; ?>
    
   <main class="login-page-main">
      <div class="resend-container">
         <h2 class="resend-title">Renvoyer l'email de vérification</h2>
         
         <?php if(!empty($message)): ?>
            <p class="<?php echo $message_type; ?>-message"><?php echo $message; ?></p>
         <?php endif; ?>
         
         <?php if(!isset($_GET['email']) || $message_type == 'error'): ?>
            <p class="resend-description">Entrez votre adresse email pour recevoir un nouveau lien de vérification.</p>
            
            <form action="" method="get">
               <div class="formulaire">
                  <div class="column column--gap">
                     <div class="curved-container">
                        <span class="label">E-mail</span>
                        <input type="email" name="email" required class="input-zone" value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>">
                     </div>
                  </div>
                  <input type="submit" value="Envoyer le lien" class="button">
               </div>
            </form>
         <?php endif; ?>
         
         <a href="login_form.php" class="back-to-login" style="display: block; margin-top: 20px; color: #828977; text-decoration: none;">Retour à la connexion</a>
      </div>
   </main>

   <?php include '../TEMPLATE/footer.php'; ?>
</body>
</html>
<!-- cvq -->