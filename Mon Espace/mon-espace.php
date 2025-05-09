<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Mon espace</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
  <link rel="stylesheet" href="./teteaupied.css" />
  <link rel="stylesheet" href="./style.css" />
</head>
<body>

  <!-- Header -->
  <header class="header">
    <a href="../Page d-accueil/Accueil.html">
      <img class="logo" src="C:\Users\arthu\Desktop\mon-espace-site\logo-transparent-png.png" alt="Site logo" />
    </a>    
    <nav class="nav-links">
      <ul>
        <li><a href="#">Devenez Prestataire</a></li>
        <li><a href="#">Concept</a></li>
      </ul>
    </nav>
    <div class="icon">
      <i class="fa-regular fa-heart" aria-label="Favoris"></i>
      <a href="../Connexion-Inscription/Connexion.html" class="connexion-profil" aria-label="Connexion">
        <i class="fa-solid fa-user"></i>
      </a>
    </div>
  </header>

  <!-- Image avec titre -->
  <div class="header-image">
    <h1>Mon espace</h1>
  </div>

  <!-- Informations personnelles -->
  <section class="profil">
    <img src="C:\Users\arthu\Desktop\mon-espace-site\IMG-20250118-WA0014.jpg" alt="Photo de profil" class="profil-pic">
    <div class="infos">
      <h2 id="nom">Arthur EDOU</h2>
      <p id="tel">Tel: 0682390863</p>
      <p id="email">Mail: arthuredou@gmail.com</p>
      <p id="citation" class="citation">Bonjour !</p>      
      <button id="edit-btn">Modifier le profil</button>
      <button id="save-btn" style="display: none;">Enregistrer</button>
    </div>
  <div class="menu-profil">
    <p>Messagerie</p>
    <p>Mes activités</p>
    <p>Confidentialité</p>
  </div>
</section>

  <!-- Activités à venir -->
  <section class="activites">
    <div class="titre-activite">
    <div class="cartes">
      <div class="carte">
        <img src="C:\Users\arthu\Desktop\mon-espace-site\louvre.jpeg" alt="Activité">
        <h3>Culture</h3>
        <p>Visite du Louvre</p>
        <p>Mercredi 7 mai 2025</p>
        <p>9h - 12h</p>
        <p>Musée du Louvre</p>
      </div>
      <div class="cartes">
        <div class="carte">
          <img src="C:\Users\arthu\Desktop\mon-espace-site\sport-dessin.jpg" alt="Activité">
          <h3>Sport</h3>
          <p>Entrainement de Football</p>
          <p>Jeudi 8 mai 2025</p>
          <p>15h - 18h</p>
          <p>Stade Suzanne Lenglenne</p>
        </div>
    </div>
    <div class="carte" id="carte-theatre">
      <img src="C:\Users\arthu\Documents\GitHub\DevWebAPP\Mon Espace\theatre.jpg" alt="Activité">
      <h3>Culture</h3>
      <p>Pièce de théâtre</p>
      <p>Jeudi 12 juin 2025</p>
      <p>20h - 22h</p>
      <p>Théâtre des Mathurins, Paris 75008</p>
    </div>
  </section>


  <!-- Footer -->
  <footer class="footer">
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
  <script src="C:\Users\arthu\Documents\GitHub\DevWebAPP\Mon Espace\mon-espace.js"></script>

</body>
</html>
