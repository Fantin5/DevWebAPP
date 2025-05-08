<?php

@include 'config.php';

session_start();

// Partie de connexion
if(isset($_POST['login_submit'])){
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

   $select = " SELECT * FROM user_form WHERE email = '$email' && password = '$pass' ";

   $result = mysqli_query($conn, $select);

   if(mysqli_num_rows($result) > 0){

      $row = mysqli_fetch_array($result);
      header('location:../Page d-accueil/Accueil.html');
     
   }else{
      $error[] = 'incorrect email or password!';
   }

};

// Partie d'inscription
if(isset($_POST['register_submit'])){

   $name = mysqli_real_escape_string($conn, $_POST['name']);
   $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
   $age = mysqli_real_escape_string($conn, $_POST['age']);
   $phone_nb = mysqli_real_escape_string($conn, $_POST['phone_nb']);
   $email = mysqli_real_escape_string($conn, $_POST['email']);
   $user_type = $_POST['user_type'];

   $select = " SELECT * FROM user_form WHERE email = '$email' ";

   $result = mysqli_query($conn, $select);

   if ($_POST['password'] != $_POST['cpassword']) {
    $error[] = 'Les mots de passe ne correspondent pas!';
 } else {
    // Vérifie si l'email existe déjà
    $select = "SELECT * FROM user_form WHERE email = '$email'";
    $result = mysqli_query($conn, $select);

    if (mysqli_num_rows($result) > 0) {
       $error[] = 'Email déjà utilisé!';
    } else {
       // Hachage du mot de passe
       $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);
       // Insertion en base
       $insert = "INSERT INTO user_form(name, first_name, age, phone_nb, email, password)
                  VALUES('$name', '$first_name', '$age', '$phone_nb', '$email', '$pass')";
       mysqli_query($conn, $insert);
       header('Location: ../Connexion-Inscription/login_form.php');
       exit();
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
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
   <style>
      * {
  box-sizing: border-box;
  box-shadow: 0 0 0 0;
}
html {
  font-size: 16px;
}

body {
    margin: 0;
    padding: 0;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: right;
    background: #E4D8C8;
    font-family: sans-serif;
    color: #828977;
  }
main {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: 100px;
    gap: 50px;
    width: 100%;
    max-width: 1200px;
  }
  a {
    color: #828977;
    font-size: 0.8em;
  }
  .switch {
    position: relative;
    display: flex;
    background-color: #828977;
    color: #E4D8C8;
    height : 52.4px;
    width: 300px;
    border-radius: 10px;
    justify-content: center;
    align-items: stretch;
    text-align: center;
    font-size: 1.2em;
    cursor: default;
  }
  .left-curved-container {
    width: 50%; /* Ajuste la largeur pour éviter le dépassement */
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    background-color: #828977;
    border-top-left-radius: 10px;
    border-bottom-left-radius: 10px;
    border-top-right-radius: 0%;   
    border-bottom-right-radius: 0%;
  }
  .right-curved-container {
    width: 50%; /* Ajuste la largeur pour éviter le dépassement */
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    background-color: #828977;
    border-top-right-radius: 10px;
    border-bottom-right-radius: 10px;
    border-top-left-radius: 0%;   
    border-bottom-left-radius: 0%;
  }
  /* Conteneur rempli */
  .filled {
      background-color: transparent;
      color: #E4D8C8;
      border: 3.5px solid #828977;
      cursor: pointer;
  }
  /* Conteneur vide avec bord coloré */
  .empty {
      background-color: #E4D8C8;
      color: #828977;
      border: 3.5px solid #828977;
      cursor: default;
  }
  .box {
    width: 48%; /* Ajuste la largeur pour éviter le dépassement */
    padding: 20px;
    text-align: center;
  }

  .wrapper {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 20px;
  }
  .formulaire {
    display: flex;
    flex-direction: column;
    gap: 40px;
    flex-wrap: wrap;
    max-width: 1000px;
    justify-content: center;
    align-items: center;
  }
  .button {
    position: relative;
    display: flex;
    background-color: #828977;
    color: #E4D8C8;
    height : 52.4px;
    width: 300px;
    border-radius: 10px;
    justify-content: center;
    align-items: center;
    text-align: center;
    font-size: 1.2em;
    cursor: pointer;
    margin: 0 auto;
  }
  .wrapper-row {
    display: inline-block; /* Adapte sa taille au contenu */
    border: 2px solid gray;
    padding: 10px;
    align-items: stretch;
  }
  .wrapper-col {
    display: flex;
    gap: 20px;
    border: 2px solid gray;
    padding: 10px;
    width: max-content;
  }
  .column {
    display: flex;
    flex-direction: column;
    width: 300px;
  }
  .column--gap {
    gap: 20px;
  }
  .row {
      display: flex;
      flex-direction: row;
      gap: 20px;
  }
  .row--full {
    /* S'adapte au conteneur (parent) */
    width: 100%;
    justify-content: space-between;
  }
  .row--auto {
    /* S'adapte au contenu (enfants) */
    width: max-content;
    gap: 20px;
  }

  .curved-container {
      flex: 1;
    display: flex;
    flex-direction: column;
    height: 52.4px;
    width: 100%;
    max-width: 620px;
    padding: 2px;
    border: 3.5px solid #828977;
    border-radius: 10px;

    background-color: transparent;
    color: #828977;
    text-align: left;
  }

  .label {
    font-size: 0.9em;
    margin-bottom: 4px;
  }
  .input-zone {
    flex: 1;
    width: 100%;
    border: none;
    outline: none;
    font-size: 1em;
    background: transparent;
    color: #828977;
  }
  

  .input-with-icon {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .input-with-icon .input-zone {
    flex: 1;
  }

  .fa-eye {
    font-size: 1.2em;
    cursor: pointer;
  }

  .password-rules {
    font-size: 0.6em;
    color: #828977;
  }
  
   </style>
   <link rel="stylesheet" href="../TEMPLATE/teteaupied.css">
   <title>Connexion</title>

</head>
<body>
   <!-- 1 partie header -->
   <header>
      <img
        class="logo"
        src="../Connexion-Inscription/logo-transparent-pdf.png"
        alt="Site logo"
      />
      <!-- hello  -->
      <nav class="nav-links">
        <ul>
          <li><a href="#">Devenez Prestataire</a></li>
          <li><a href="#">Concept</a></li>
        </ul>
      </nav>

      <div class="icon">
        <i class="fa-regular fa-heart" aria-label="Favoris"></i>
        <a
          href="../Connexion-Inscription/Connexion.html"
          class="connexion-profil"
          aria-label="Connexion"
        >
          <i class="fa-solid fa-user"></i>
        </a>
      </div>
   </header>
    
   <main>
      <div class ="switch">
         <div class="left-curved-container empty" id="box1">Se connecter</div>
         <div class="right-curved-container filled" id="box2">Créer un compte</div>
      </div>

      <!-- Affichage "Se connecter" -->
      <div class="form-container">
         <form action="" method="post">
            <?php
            if(isset($error)){
               foreach($error as $error){
                  echo '<span class="error-msg">'.$error.'</span>';
               };
            };
            ?>
            <div id="login-section" class="formulaire">
               <div class="column column--gap">
                  <div class="curved-container">
                     <span class="label">E-mail</span>
                     <input type="email" name="email" required class="input-zone">
                  </div>
                  <div class="curved-container">
                     <div class="row">
                        <div class="column">
                           <span class="label">Mot de passe</span>
                           <input type="password" name="password" required class="input-zone">
                        </div>
                        <i class="fa-light fa-eye" id="eye-closed"></i>
                     </div>
                  </div>
                  <a href="">Mot de passe oublié</a>
               </div>
               <input type="submit" name="login_submit" value="Se connecter" class="button">
            </div>
         </form>
      </div>

      <!-- Affichage "Créer un compte" -->
      <div class="form-container">
         <form action="" method="post">
            <?php
            if(isset($error)){
               foreach($error as $error){
                  echo '<span class="error-msg">'.$error.'</span>';
               };
            };
            ?>
            <div id="register-section" class="formulaire">
               <div class="row row--auto">
                  <div class="column column--gap">
                     <div class="row">
                        <div class="curved-container">
                              <span class="label">Nom</span>
                              <input type="text" name="name" required class="input-zone">
                        </div>
                        <div class="curved-container">
                              <span class="label">Prénom</span>
                              <input type="text" name="first_name" required class="input-zone">
                        </div>
                     </div>
                     <div class="row">
                        <div class="curved-container">
                              <span class="label">Age</span>
                              <input type="number" name="age" min="1" required class="input-zone">
                        </div>
                        <div class="curved-container">
                              <span class="label">Tel</span>
                              <input type="tel" name="phone_nb" pattern="[0-9]{10}" required class="input-zone">
                        </div>
                     </div>
                     
                     <div class="curved-container">
                        <span class="label">E-mail</span>
                        <input type="email" name="email" required class="input-zone">
                     </div>
                  </div>
               
                  <div class="column column--gap">
                     <div class="curved-container">
                        <div class="row">
                        <div class="column">
                           <span class="label">Mot de passe</span>
                           <input type="password" name="password" required class="input-zone">
                        </div>
                        <i class="fa-light fa-eye"></i>
                        </div>
                     </div>
                  
                     <div class="curved-container">
                        <div class="row">
                        <div class="column">
                           <span class="label">Confirmation du mot de passe</span>
                           <input type="password" name="cpassword" required class="input-zone">
                        </div>
                        <i class="fa-light fa-eye"></i>
                        </div>
                     </div>
                  
                     <div class="password-rules" style="text-align: left;">
                        <p>Caractéristiques du mot de passe :</p>
                        <ul>
                        <li>8 caractères minimum</li>
                        <li>Au moins une majuscule</li>
                        <li>Au moins un chiffre</li>
                        <li>Au moins un caractère spécial (@#%$&*)</li>
                        </ul>
                     </div>
                  </div>
               </div>
               <input type="submit" name="register_submit" value="Créer un compte" class="button">
            </div>
         </form>
      </div>
   </main>

   <!-- 7 footer --> 
   <footer>
      <ul>
      <li><a href="#">FAQ</a></li>
      <li><a href="#">CGU</a></li>
      <li><a href="#">Mentions Légales</a></li>
      </ul>

      <ul>
      <li><i class="fa-solid fa-phone"></i> 06 01 02 03 04</li>
      <li><i class="fa-regular fa-envelope"></i> synapse@gmail.com</li>
      </ul>
      <ul>
      <li><i class="fa-brands fa-facebook-f"></i> synapse.off</li>
      <li><i class="fa-brands fa-instagram"></i> synapse.off</li>
      </ul>

      <ul>
      <li>Lundi - Vendredi : 9h à 20h</li>
      <li>Samedi : 10h à 16h</li>
      </ul>
   </footer>

   <script src="switch.js"></script>
</body>
</html>