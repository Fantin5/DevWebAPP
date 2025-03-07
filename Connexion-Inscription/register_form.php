
<!-- http://localhost/ProjetWebDev%20copy2/Connexion-Inscription/register_form.php -->
<!-- http://localhost/phpmyadmin -->

<?php

@include 'config.php';

if(isset($_POST['submit'])){

   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $pass = md5($_POST['password']);
   $cpass = md5($_POST['cpassword']);
   $user_type = $_POST['user_type'];

   $select = " SELECT * FROM user_form WHERE email = '$email' && password = '$pass' ";

   $result = mysqli_query($conn, $select);

   if(mysqli_num_rows($result) > 0){

      $error[] = 'Email déjà utilisé!';

   }else{

      if($pass != $cpass){
         $error[] = 'Les mots de passe ne correspondent pas!';
      }else{
         $insert = "INSERT INTO user_form(name, email, password, user_type) VALUES('$name','$email','$pass','$user_type')";
         mysqli_query($conn, $insert);
         header('Page d-accueil/Accueil.html');
      }
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
    <title>Inscription</title>

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
      <h3>Créez un Compte</h3>
      <?php
      if(isset($error)){
         foreach($error as $error){
            echo '<span class="error-msg">'.$error.'</span>';
         };
      };
      ?>
      <input type="text" name="name" required placeholder="Entrez Votre Nom">
      <input type="email" name="email" required placeholder="Entrez Votre Email">
      <input type="password" name="password" required placeholder="Entrez Votre Mot de Passe">
      <input type="password" name="cpassword" required placeholder="Confirmez Votre Mot de Passe">
      <select name="user_type">
         <option value="user">Client</option>
         <option value="admin">Prestataire</option>

      </select>
      <input type="submit" name="submit" value="Créez un Compte" class="form-btn">
      <p>Vous avez déja un Compte? <a href="login_form.php">Connectez-Vous</a></p>
   </form>

</div>

<input type="radio">
<p class="conditions">J'accepte les conditions générales d'uttilisation</p>

<i class="fa-regular fa-square-plus"id="uploadIcon" font></i>
<img class="photo-profil" id="selectedImage" style="display: none; width: 200px;" />
<input type="file" id="imageInput" style="display: none;" accept="image/*" />


<script src="Connexion.js"></script>
</body>
</html>