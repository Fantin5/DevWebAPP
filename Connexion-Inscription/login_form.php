<?php

@include 'config.php';

session_start();

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
      if (password_verify($password, $row['password'])) {
         // Stocker les informations utilisateur en session
         $_SESSION['user_id'] = $row['id'];
         $_SESSION['user_name'] = $row['name'];
         $_SESSION['user_first_name'] = $row['first_name']; 
         $_SESSION['user_email'] = $row['email'];
         $_SESSION['user_type'] = $row['user_type'];
         $_SESSION['logged_in'] = true;
         
         header('Location: ../Testing grounds/main.php');
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
       // Hachage du mot de passe
       $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
       // Insertion en base
       $insert = "INSERT INTO user_form(name, first_name, birthday, phone_nb, email, password)
                  VALUES('$name', '$first_name', '$birthday', '$phone_nb', '$email', '$pass')";
       mysqli_query($conn, $insert);
       
       // Auto-login après inscription réussie
       $_SESSION['user_id'] = mysqli_insert_id($conn);
       $_SESSION['user_name'] = $name;
       $_SESSION['user_first_name'] = $first_name;
       $_SESSION['user_email'] = $email;
       $_SESSION['logged_in'] = true;
       
       header('Location: ../Testing grounds/main.php');
       exit();
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
</head>
<body>
   <?php include '../TEMPLATE/Nouveauhead.php'; ?>
    
   <main class="login-page-main">
      <div class="switch">
         <div class="left-curved-container empty" id="box1">Se connecter</div>
         <div class="right-curved-container filled" id="box2">Créer un compte</div>
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
                  <a href="" class="forgot-password">Mot de passe oublié</a>
               </div>
               <input type="submit" name="login_submit" value="Se connecter" class="button">
            </div>
         </form>
      </div>

      <!-- Affichage "Créer un compte" -->
      <div id="register-section" class="form-container">
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