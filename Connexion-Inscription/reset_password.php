<?php
@include 'config.php';
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$message_type = '';
$valid_token = false;

// If user is already logged in, redirect to main page
if(isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true){
    header('Location: ../Testing grounds/main.php');
    exit();
}

// Check if token and email are provided in URL
if(isset($_GET['email']) && isset($_GET['token'])){
    $email = mysqli_real_escape_string($conn, $_GET['email']);
    $token = mysqli_real_escape_string($conn, $_GET['token']);
    
    // Verify the token from database
    $select = "SELECT * FROM user_form WHERE email = '$email' AND reset_token = '$token' AND reset_expires > " . time();
    $result = mysqli_query($conn, $select);
    
    if(mysqli_num_rows($result) > 0){
        $valid_token = true;
    } else {
        $message = "Lien de réinitialisation invalide ou expiré.";
        $message_type = 'error';
    }
}

// Process form submission for password reset
if(isset($_POST['reset_submit'])){
    $password = $_POST['password'];
    $cpassword = $_POST['cpassword'];
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $token = mysqli_real_escape_string($conn, $_POST['token']);
    
    // Validate password
    if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[#?!@$%^&*-]).{8,}$/', $password)) {
        $message = 'Le mot de passe doit contenir une majuscule, une minuscule, un chiffre, un caractère spécial et au moins 8 caractères.';
        $message_type = 'error';
    } elseif ($password != $cpassword) {
        $message = 'Les mots de passe ne correspondent pas!';
        $message_type = 'error';
    } else {
        // Verify token validity one more time
        $select = "SELECT * FROM user_form WHERE email = '$email' AND reset_token = '$token' AND reset_expires > " . time();
        $result = mysqli_query($conn, $select);
        
        if(mysqli_num_rows($result) > 0){
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password in database and invalidate token
            $update = "UPDATE user_form SET password = '$hashed_password', reset_token = NULL, reset_expires = NULL WHERE email = '$email'";
            if(mysqli_query($conn, $update)){
                $message = 'Mot de passe réinitialisé avec succès!';
                $message_type = 'success';
            } else {
                $message = 'Erreur lors de la réinitialisation du mot de passe. Veuillez réessayer.';
                $message_type = 'error';
            }
        } else {
            $message = 'Lien de réinitialisation invalide ou expiré.';
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
   <title>Réinitialisation du mot de passe | Synapse</title>
   
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
      
      .reset-password-container {
         max-width: 500px;
         margin: 0 auto;
         padding: 30px;
         text-align: center;
      }
      
      .reset-password-title {
         font-size: 1.5em;
         margin-bottom: 20px;
         color: #828977;
      }
      
      .reset-password-description {
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
      <div class="reset-password-container">
         <h2 class="reset-password-title">Réinitialisation du mot de passe</h2>
         
         <?php if(!empty($message)): ?>
            <p class="<?php echo $message_type; ?>-message"><?php echo $message; ?></p>
            <?php if($message_type == 'success'): ?>
               <a href="login_form.php" class="button" style="display: inline-block; margin-top: 20px; text-decoration: none;">Se connecter</a>
            <?php endif; ?>
         <?php endif; ?>
         
         <?php if($valid_token && $message_type != 'success'): ?>
            <p class="reset-password-description">Veuillez saisir votre nouveau mot de passe.</p>
            
            <form action="" method="post">
               <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email']); ?>">
               <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
               
               <div class="formulaire">
                  <div class="column column--gap">
                     <div class="curved-container">
                        <div class="row centered">
                           <div class="column">
                              <span class="label">Nouveau mot de passe</span>
                              <input id="reset-password" type="password" name="password" required class="input-zone">
                           </div>
                           <div style="display:inline-block">
                              <i id="toggle-reset-password" class="fa-regular fa-eye fa-xl" style="color: #828977"></i>
                           </div>
                        </div>
                     </div>
                     
                     <div class="curved-container">
                        <div class="row centered">
                           <div class="column">
                              <span class="label">Confirmer le mot de passe</span>
                              <input id="reset-confirm" type="password" name="cpassword" required class="input-zone">
                           </div>
                           <div style="display:inline-block">
                              <i id="toggle-reset-confirm" class="fa-regular fa-eye fa-xl" style="color: #828977"></i>
                           </div>
                        </div>
                     </div>
                     
                     <p id="password-validation-message" class="password-rules">
                        Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.
                     </p>
                     <p id="confirm-validation-message" class="password-rules"></p>
                  </div>
                  
                  <input type="submit" name="reset_submit" value="Réinitialiser le mot de passe" class="button">
               </div>
            </form>
         <?php elseif(!$valid_token && $message_type != 'success'): ?>
            <p class="error-message">Lien de réinitialisation invalide ou expiré.</p>
         <?php endif; ?>
         
         <a href="login_form.php" class="back-to-login">Retour à la connexion</a>
      </div>
   </main>

   <?php include '../TEMPLATE/footer.php'; ?>
   
   <script>
      document.addEventListener("DOMContentLoaded", function () {
          // Password visibility toggle
          const toggleConfigs = [
              { toggleId: "toggle-reset-password", inputId: "reset-password" },
              { toggleId: "toggle-reset-confirm", inputId: "reset-confirm" },
          ];

          toggleConfigs.forEach(({ toggleId, inputId }) => {
              const toggle = document.getElementById(toggleId);
              const input = document.getElementById(inputId);
              if (toggle && input) {
                  toggle.addEventListener("click", function () {
                      const isPassword = input.type === "password";
                      input.type = isPassword ? "text" : "password";
                      this.classList.toggle("fa-eye");
                      this.classList.toggle("fa-eye-slash");
                  });
              }
          });

          // Password validation
          const passwordInput = document.getElementById("reset-password");
          const confirmInput = document.getElementById("reset-confirm");
          const passwordValidationMessage = document.getElementById("password-validation-message");
          const confirmValidationMessage = document.getElementById("confirm-validation-message");
          
          if (passwordInput && confirmInput) {
              passwordInput.addEventListener('input', validatePasswordFormat);
              passwordInput.addEventListener('blur', validatePasswordFormat);
              confirmInput.addEventListener('input', validateForm);
              confirmInput.addEventListener('blur', validateForm);
          }

          function validateForm() {
              if (!confirmInput) return false;
              
              const password = passwordInput.value.trim();
              const confirmPassword = confirmInput.value.trim();
          
              if (password && confirmPassword && password !== confirmPassword) {
                  confirmValidationMessage.textContent = "Les mots de passe ne correspondent pas.";
                  confirmValidationMessage.style.color = "#e74c3c";
                  confirmInput.style.borderColor = "#e74c3c";
                  return false;
              } else if (confirmPassword && password === confirmPassword) {
                  confirmValidationMessage.textContent = "✓ Les mots de passe correspondent";
                  confirmValidationMessage.style.color = "#2ecc71";
                  confirmInput.style.borderColor = "#2ecc71";
                  return true;
              } else {
                  confirmValidationMessage.textContent = "";
                  confirmInput.style.borderColor = "";
                  return false;
              }
          }

          function validatePasswordFormat() {
              if (!passwordInput || !passwordValidationMessage) return false;
              
              const passwordValue = passwordInput.value.trim();
              let isValid = false;
          
              const passwordRegex = /^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/;
          
              if (!passwordValue) {
                  passwordValidationMessage.textContent = "Ce champ est requis.";
                  passwordValidationMessage.style.color = "#e74c3c";
                  passwordInput.style.borderColor = "#e74c3c";
                  isValid = false;
              } else if (passwordRegex.test(passwordValue)) {
                  passwordValidationMessage.textContent = "✓ Mot de passe valide";
                  passwordValidationMessage.style.color = "#2ecc71";
                  passwordInput.style.borderColor = "#2ecc71";
                  isValid = true;
              } else {
                  passwordValidationMessage.textContent = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule, un chiffre et un caractère spécial.";
                  passwordValidationMessage.style.color = "#e74c3c";
                  passwordInput.style.borderColor = "#e74c3c";
                  isValid = false;
              }
          
              // Always revalidate the confirmation after the format
              if (confirmInput && confirmValidationMessage) {
                  validateForm();
              }
          
              return isValid;
          }
      });
   </script>
</body>
</html>