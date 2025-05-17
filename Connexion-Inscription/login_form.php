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
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="../TEMPLATE/teteaupied.css">

   <!-- Inclure Font Awesome -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
   <script src="https://kit.fontawesome.com/8e8b336406.js" crossorigin="anonymous"></script>

   <style>
      
* {
   box-sizing: border-box;
}

body {
   margin: 0;
   padding: 0;
   min-height: 100vh;
   display: flex;
   flex-direction: column;
   text-align: right;
   background: #E4D8C8;
   font-family: sans-serif;
   color: #828977;
}
main {
   margin: auto;
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
      align-items: start;
}
.row--full {
   /* S'adapte au conteneur (parent) */
   width: 100%;
   gap: 20px;
}
.row--auto {
   /* S'adapte au contenu (enfants) */
   width: max-content;
   gap: 40px;
}
.centered {
   align-items: center;
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
   cursor: pointer;
}

.password-rules {
   font-size: 0.62em;
   text-align: left;
}

.error-msg {
   color: #e74c3c;
   font-size: 0.9em;
   margin-top: 5px;
}
   </style>

   <title>Connexion</title>

</head>
<body>
   <?php
   // Inclure le header
   include '../TEMPLATE/Nouveauhead.php';
   ?>
    
   <main>
      <div class ="switch">
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
                  <a href="">Mot de passe oublié</a>
               </div>
               <input type="submit" name="login_submit" value="Se connecter" class="button">
            </div>
         </form>
      </div>

      <!-- Affichage "Créer un compte" -->
      <div  id="register-section" class="form-container">
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

   <?php
   // Inclure le footer
   include '../TEMPLATE/footer.php';
   ?>

   <script>
      function swapStyles() {
    let box1 = document.getElementById("box1");
    let box2 = document.getElementById("box2");
    let loginSection = document.getElementById("login-section");
    let registerSection = document.getElementById("register-section");

    // Échanger les classes pour changer la couleur
    box1.classList.toggle("filled");
    box1.classList.toggle("empty");
    box2.classList.toggle("filled");
    box2.classList.toggle("empty");

    // Vérifier quel bouton est actif et afficher la section correspondante
    if (box1.classList.contains("filled")) {
        loginSection.style.display = "none";
        registerSection.style.display = "flex";
        box1.style.pointerEvents = "auto";
        box2.style.pointerEvents = "none";

    } else {
        loginSection.style.display = "flex";
        registerSection.style.display = "none";
        box1.style.pointerEvents = "none";
        box2.style.pointerEvents = "auto";
    }
}




document.addEventListener("DOMContentLoaded", function () {
   // Masquer la section "Créer un compte" au chargement
    document.getElementById("register-section").style.display = "none";
    // Rendre le premier bouton non cliquable (par défaut actif)
    document.getElementById("box1").style.pointerEvents = "none";

    // Gestion des boutons de bascule
    document.getElementById("box1").addEventListener("click", swapStyles);
    document.getElementById("box2").addEventListener("click", swapStyles);

    // Affichage / masquage du mot de passe (connexion)
    const toggleConfigs = [
        { toggleId: "toggle-login-password", inputId: "login-password" },
        { toggleId: "toggle-register-password", inputId: "register-password" },
        { toggleId: "toggle-register-confirm", inputId: "register-confirm" },
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



    // Variables globales pour la validation du mot de passe
        // Variables pour les champs de mot de passe
    const passwordInput = document.getElementById("register-password");
    const confirmInput = document.getElementById("register-confirm");
        // Variables pour les messages de validation
    const passwordValidationMessage = document.getElementById("password-validation-message");
    const confirmValidationMessage = document.getElementById("confirm-validation-message");
    
    // Ajout d'écouteurs d'événements pour la validation du mot de passe
    passwordInput.addEventListener('input', validatePasswordFormat);
    passwordInput.addEventListener('blur', validatePasswordFormat);
    confirmInput.addEventListener('input', validateForm);
    confirmInput.addEventListener('blur', validateForm);

    // Validation du formulaire d'inscription
    function validateForm() {
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
            confirmInput.style.borderColor = ""; // ou un style neutre
            return false;
        }
    }


    function validatePasswordFormat() {
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
    
        // Toujours revalider la correspondance après le format
        validateForm();
    
        return isValid;
    }
});
   </script>
</body>
</html>