<?php
@include 'config.php';
session_start();

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$message_type = '';

// Verify the email
if(isset($_GET['email']) && isset($_GET['token'])){
    $email = mysqli_real_escape_string($conn, $_GET['email']);
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    
    // Check if the token is valid
    $select = "SELECT * FROM user_form WHERE email = '$email' AND verification_token = '$token' AND verification_expires > " . time();
    $result = mysqli_query($conn, $select);
    
    if(mysqli_num_rows($result) > 0){
        // Update the user's account to verified status
        $update = "UPDATE user_form SET email_verified = 1, verification_token = NULL, verification_expires = NULL WHERE email = '$email'";
        
        if(mysqli_query($conn, $update)){
            $message = "Votre email a été vérifié avec succès! Vous pouvez maintenant vous connecter.";
            $message_type = 'success';
        } else {
            $message = "Une erreur s'est produite lors de la vérification de votre email. Veuillez réessayer.";
            $message_type = 'error';
        }
    } else {
        $message = "Lien de vérification invalide ou expiré.";
        $message_type = 'error';
    }
} else {
    // If no email or token is provided, redirect to the login page
    header('Location: login_form.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Vérification de l'email | Synapse</title>
   
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
      
      .verification-container {
         max-width: 500px;
         margin: 0 auto;
         padding: 30px;
         text-align: center;
      }
      
      .verification-title {
         font-size: 1.5em;
         margin-bottom: 20px;
         color: #828977;
      }
   </style>
</head>
<body>
   <?php include '../TEMPLATE/Nouveauhead.php'; ?>
    
   <main class="login-page-main">
      <div class="verification-container">
         <h2 class="verification-title">Vérification de l'email</h2>
         
         <?php if($message_type == 'success'): ?>
            <p class="success-message"><?php echo $message; ?></p>
            <a href="login_form.php" class="button" style="display: inline-block; margin-top: 20px; text-decoration: none;">Se connecter</a>
         <?php else: ?>
            <p class="error-message"><?php echo $message; ?></p>
            <a href="login_form.php" class="button" style="display: inline-block; margin-top: 20px; text-decoration: none;">Retour à la connexion</a>
         <?php endif; ?>
      </div>
   </main>

   <?php include '../TEMPLATE/footer.php'; ?>
</body>
</html>