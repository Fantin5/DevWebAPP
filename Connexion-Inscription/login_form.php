<?php

@include 'config.php';

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();

// Display account switching errors
if(isset($_SESSION['switch_error'])) {
    $login_error[] = $_SESSION['switch_error'];
    unset($_SESSION['switch_error']); // Clear the error after displaying
}

// Partie de connexion
if(isset($_POST['login_submit'])){
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $password = $_POST['password'];

   // Sécuriser l'email contre injections SQL
   $stmt = $conn->prepare("SELECT * FROM user_form WHERE email = ?");
   $stmt->bind_param("s", $email);
   $stmt->execute();
   $result = $stmt->get_result();

   if(mysqli_num_rows($result) > 0){
      $row = mysqli_fetch_array($result);
      
      // Vérifier si l'email a été vérifié
      if ($row['email_verified'] == 0) {
         $login_error[] = 'Veuillez vérifier votre email avant de vous connecter. <a href="resend_verification.php?email=' . urlencode($email) . '">Renvoyer l\'email de vérification</a>';
      } elseif (password_verify($password, $row['password'])) {
         // Stocker les informations utilisateur en session
         $_SESSION['user_id'] = $row['id'];
         $_SESSION['user_name'] = $row['name'];
         $_SESSION['user_first_name'] = $row['first_name']; 
         $_SESSION['user_email'] = $row['email'];
         $_SESSION['user_type'] = $row['user_type'];
         $_SESSION['logged_in'] = true;
         
         // Check if user is admin and redirect accordingly
         if($_SESSION['user_type'] == 1 || $_SESSION['user_type'] == 2) {
            header('Location: ./../Admin/admin.php');
         } else {
            header('Location: ../Testing grounds/main.php');
         }
         exit();
      } else {
         $login_error[] = 'Mot de passe incorrect !';
      }
   } else {
      $login_error[] = 'Email non trouvé !';
   }
};

// Partie d'inscription
if(isset($_POST['register_submit'])){

   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
   $birthday = mysqli_real_escape_string($conn, $_POST['birthday']);
   $phone_nb = mysqli_real_escape_string($conn, $_POST['phone_nb']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);

   $select = " SELECT * FROM user_form WHERE email = '$email' ";
   $result = mysqli_query($conn, $select);

   if (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[#?!@$%^&*-]).{8,}$/', $_POST['password'])) {
      $register_error[] = 'Le mot de passe doit contenir une majuscule, une minuscule, un chiffre, un caractère spécial et au moins 8 caractères.';
   }
   if ($_POST['password'] != $_POST['cpassword']) {
    $register_error[] = 'Les mots de passe ne correspondent pas!';
   } else {
    // Vérifie si l'email existe déjà
    $select = "SELECT * FROM user_form WHERE email = '$email'";
    $result = mysqli_query($conn, $select);

    if (mysqli_num_rows($result) > 0) {
       $register_error[] = 'Email déjà utilisé!';
    } else {
       // Générer un token de vérification
       $verification_token = bin2hex(random_bytes(32));
       $verification_expires = time() + 86400; // 24 heures
       
// Hachage du mot de passe
$pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Date actuelle pour created_at
$created_at = date('Y-m-d H:i:s');

// Insertion en base avec le token de vérification et la date d'inscription
$insert = "INSERT INTO user_form(name, first_name, birthday, phone_nb, email, password, verification_token, verification_expires, email_verified, created_at)
           VALUES('$name', '$first_name', '$birthday', '$phone_nb', '$email', '$pass', '$verification_token', '$verification_expires', 0, '$created_at')";
       
       // Insertion en base avec le token de vérification
       $insert = "INSERT INTO user_form(name, first_name, birthday, phone_nb, email, password, verification_token, verification_expires, email_verified)
                  VALUES('$name', '$first_name', '$birthday', '$phone_nb', '$email', '$pass', '$verification_token', '$verification_expires', 0)";
       
       if (mysqli_query($conn, $insert)) {
          // Créer le lien de vérification
          $verification_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify_email.php?email=" . urlencode($email) . "&token=" . $verification_token;
          
          // Envoyer l'email de vérification
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
                         <h2>Bienvenue sur Synapse !</h2>
                     </div>
                     <div class="content">
                         <p>Bonjour ' . $first_name . ',</p>
                         <p>Merci de vous être inscrit sur Synapse. Pour activer votre compte, veuillez cliquer sur le bouton ci-dessous :</p>
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
             
Merci de vous être inscrit sur Synapse. Pour activer votre compte, veuillez cliquer sur le lien suivant :
' . $verification_link . '

Ce lien expirera dans 24 heures.';
             
             $mail->send();
             
             $register_success = "Inscription réussie ! Un email de vérification a été envoyé à $email. Veuillez vérifier votre boîte de réception pour activer votre compte.";
          } catch (Exception $e) {
             $register_error[] = "L'email de vérification n'a pas pu être envoyé. " . $mail->ErrorInfo;
          }
       } else {
          $register_error[] = "Erreur lors de l'enregistrement. Veuillez réessayer.";
       }
    }
 }

};
?>

<!DOCTYPE html>
<html lang="fr">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Connexion | Synapse</title>
   
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
         margin: 10px 0;
         padding: 10px;
         background-color: #f0f8f0;
         border-radius: 5px;
      }
   </style>
</head>
<body>
   <?php include '../TEMPLATE/Nouveauhead.php'; ?>
    
   <main class="login-page-main">
      <div class="switch">
        <div class="left-curved-container filled" id="box1">Se connecter</div>
<div class="right-curved-container empty" id="box2">Créer un compte</div>
      </div>

      <!-- Affichage "Se connecter" -->
      <div id="login-section" class="form-container">
         <form action="" method="post">
            <?php
            if(isset($login_error)){
               foreach($login_error as $error){
                  echo '<span class="error-msg">'.$error.'</span>';
               }
            }
            ?>
            <div class="formulaire" >
               <div class="column column--gap">
                  <div class="curved-container">
                     <span class="label">E-mail</span>
                     <input type="email" name="email" required class="input-zone">
                  </div>
                  <div class="curved-container">
                     <div class="row centered">
                        <div class="column">
                           <span class="label">Mot de passe</span>
                           <input id="login-password" type="password" name="password" required class="input-zone">
                        </div>
                        <div style="display:inline-block">
                           <i id="toggle-login-password" class="fa-regular fa-eye fa-xl" style="color: #828977"></i>
                        </div>
                     </div>
                  </div>
                  <a href="forgot_password.php" class="forgot-password">Mot de passe oublié</a>
               </div>
               <input type="submit" name="login_submit" value="Se connecter" class="button">
            </div>
         </form>
      </div>

      <!-- Affichage "Créer un compte" -->
      <div id="register-section" class="form-container">
         <?php if(isset($register_success)): ?>
            <div class="success-message"><?php echo $register_success; ?></div>
         <?php endif; ?>
         
         <form action="" method="post" onsubmit="return validateForm()">
            <?php
            if(isset($register_error)){
               foreach($register_error as $error){
                   echo '<span class="error-msg">'.$error.'</span>';
               }
            }
            ?>
            <div class="formulaire">
               <div class="row row--auto">
                  <div class="column column--gap">
                     <div class="row row--full">
                        <div class="curved-container">
                              <span class="label">Nom</span>
                              <input type="text" name="name" required class="input-zone">
                        </div>
                        <div class="curved-container">
                              <span class="label">Prénom</span>
                              <input type="text" name="first_name" required class="input-zone">
                        </div>
                     </div>
                     <div class="row row--full">
                        <div class="curved-container">
                              <span class="label">Date de naissance</span>
                              <input type="date" name="birthday" required class="input-zone">
                        </div>
                        <div class="curved-container">
                              <span class="label">Tel</span>
                              <input type="tel" name="phone_nb" pattern="[0-9]{10}" title="Entrez un numéro à 10 chiffres." required class="input-zone">
                        </div>
                     </div>
                     
                     <div class="curved-container">
                        <span class="label">E-mail</span>
                        <input type="email" name="email" required class="input-zone">
                     </div>
                  </div>
               
                  <div class="column column--gap">
                     <div class="curved-container">
                        <div class="row centered">
                           <div class="column">
                              <span class="label">Mot de passe</span>
                              <input id="register-password" type="password" name="password" required class="input-zone">
                           </div>
                           <div style="display:inline-block">
                              <i id="toggle-register-password" class="fa-regular fa-eye fa-xl" style="color: #828977"></i>
                           </div>
                        </div>
                     </div>
                  
                     <div class="curved-container">
                        <div class="row centered">
                           <div class="column">
                              <span class="label">Confirmation du mot de passe</span>
                              <input id="register-confirm" type="password" name="cpassword" required class="input-zone">
                           </div>
                           <div style="display:inline-block">
                              <i id="toggle-register-confirm" class="fa-regular fa-eye fa-xl" style="color: #828977"></i>
                           </div>
                        </div>
                     </div>
                     <p id="password-validation-message" class="password-rules">
                     </p>
                     <p id="confirm-validation-message" class="password-rules">
                     </p>
                  </div>
               </div>
               <input type="submit" name="register_submit" value="Créer un compte" class="button">
            </div>
         </form>
      </div>
   </main>

   <?php include '../TEMPLATE/footer.php'; ?>
   
   <script src="switch.js"></script>
</body>
</html>
<!-- cvq -->