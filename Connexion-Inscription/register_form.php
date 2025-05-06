
<!-- http://localhost/DevWebAPP/Connexion-Inscription/register_form.php -->
<!-- http://localhost/phpmyadmin -->

<?php

@include 'config.php';

if(isset($_POST['submit'])){

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
       $insert = "INSERT INTO user_form(name, first_name, age, phone_nb, email, password, user_type)
                  VALUES('$name', '$first_name', '$age', '$phone_nb', '$email', '$pass', '$user_type')";
       mysqli_query($conn, $insert);
       header('Location: ../Page d-Accueil/Accueil.html');
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
    <!-- <link rel="stylesheet" href="Connexion.css"> -->
    <link rel="stylesheet" href="../TEMPLATE/teteaupied.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <title>Inscription</title>

</head>
<body>
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
          <input type="text" name="first_name" required placeholder="Entrez votre Prénom">
          <input type="number" name="age" min="1" required placeholder="Entrez votre Age">
          <input type="tel" name="phone_nb" pattern="[0-9]{10}" required placeholder="Entrez votre numéro de téléphone">
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
    </main>
    
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

    <script src="Connexion.js"></script>
</body>
</html>