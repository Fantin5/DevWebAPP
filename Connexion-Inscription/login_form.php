<?php

@include 'config.php';

session_start();

if(isset($_POST['submit'])){

   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $pass = md5($_POST['password']);
   $cpass = md5($_POST['cpassword']);
   $user_type = $_POST['user_type'];

   $select = " SELECT * FROM user_form WHERE email = '$email' && password = '$pass' ";

   $result = mysqli_query($conn, $select);

   if(mysqli_num_rows($result) > 0){

      $row = mysqli_fetch_array($result);

      if($row['user_type'] == 'admin'){

         $_SESSION['admin_name'] = $row['name'];
         header('location:../Page d-accueil/Accueil.html');

      }elseif($row['user_type'] == 'user'){

         $_SESSION['user_name'] = $row['name'];
         header('location:../Page d-accueil/Accueil.html');

      }
     
   }else{
      $error[] = 'incorrect email or password!';
   }

};
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="Connexion.css">
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
   <title>Connexion</title>

</head>
<body>
   <!-- 1 partie header -->
   <div class="header">
        <img class="logo" src="logo-transparent-pdf.png" >
        <p>
            Devenez Prestataire
        </p>
        <p>
            Concept
        </p>

<!-- On crée un container pour les icones pour pouvoir les espacer plus facilement du reste -->
        <div class="container-icones"> 
            <i class="fa-regular fa-heart"></i>
            <a href="../Connexion-Inscription/Connexion.html" class="connexion-profil">
                 <i class="fa-solid fa-user"></i> 
            </a>
        </div>
    </div>
    <!-- 7 footer --> 
      <!-- on met des divs pour ne pas etre géné par les margins initiaux (de p par exemple)on pourras tjr modifier plus tard si necessaire -->
      <div class="footer">
        <div class="footer1">FAQ</div>
        <div class="footer2">CGU</div>
        <div class="footer3">Mentions Légales</div>
        <div class="footer4">06 01 02 03 04 </div>
        <i class="fa-solid fa-phone"></i>
        <div class="footer5">synapse@gmail.com</div>
        <i class="fa-regular fa-envelope"></i>
        <div class="footer6">synapse.off</div>
        <i class="fa-brands fa-facebook-f"></i>
        <div class="footer7">synapse.off</div>
        <i class="fa-brands fa-instagram"></i>
        <div class="footer8">Lundi-Vendredi: 9h à 20h</div>
        <div class="footer9">Samedi : 10h à 16h</div>
      </div>
   
<div class="form-container">

   <form action="" method="post">
      <h3>Connectez-Vous</h3>
      <?php
      if(isset($error)){
         foreach($error as $error){
            echo '<span class="error-msg">'.$error.'</span>';
         };
      };
      ?>
      <input type="email" name="email" required placeholder="Entrez votre email">
      <input type="password" name="password" required placeholder="Entrez votre mot de passe">
      <input type="submit" name="submit" value="Connectez-Vous" class="form-btn">
      <p>Pas de Compte? <a href="register_form.php">Créez un Compte</a></p>
   </form>

</div>

</body>
</html>