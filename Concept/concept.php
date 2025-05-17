<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Concept</title>
    <link rel="stylesheet" href="../TEMPLATE/teteaupied.css" />
    <link rel="stylesheet" href="concept.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    />
  </head>
  <body>
    <?php
    // Inclure le header
    include '../TEMPLATE/Nouveauhead.php';
    ?>

    <!-- CONTENU PRINCIPAL -->
    <main style="padding: 40px; padding-bottom: 120px">
      <img class="bandeau" src="concept-banner.jpg" alt="Concept" />
      <h1
        style="
          color: #828977;
          font-size: 50px;
          text-align: center;
          margin-bottom: 30px;
        "
      >
        Concept
      </h1>


      <div style="display: flex; gap: 20px; margin-bottom: 30px">
        <div style="flex: 1">
          <p>
            Bienvenue sur SYNAPSE, la plateforme qui met en relation des
            particuliers passionnés avec des curieux en quête d’expériences
            authentiques.
          </p>
          <p>
            Que vous soyez amateur de cuisine, adepte de randonnées insolites,
            artisan dans l’âme ou expert en bricolage, proposez vos activités et
            partagez votre savoir-faire avec une communauté enthousiaste.
          </p>
          <p>
            Vous cherchez une activité originale à faire près de chez vous ?
            Explorez notre sélection d’ateliers et de sorties uniques : une
            expérience inoubliable avec un hôte passionné.
          </p>
        </div>
        <img
          src="activite1.jpg"
          alt="Activité 1"
          style="width: 40%; object-fit: cover; border-radius: 10px"
        />
      </div>

      <div style="display: flex; gap: 20px">
        <img
          src="activite2.jpg"
          alt="Activité 2"
          style="width: 40%; object-fit: cover; border-radius: 10px"
        />
        <div style="flex: 1">
          <p>
            Proposez une activité en partageant votre passion avec la communauté 
            en créant une annonce détaillée qui met en avant votre savoir-faire 
            votre expérience et ce que vous souhaitez transmettre aux participants 
            afin de leur offrir un moment unique et enrichissant
          </p>
          <p>
            Trouvez une expérience en parcourant les nombreuses offres disponibles 
            sur la plateforme en fonction de vos envies de vos centres d’intérêt et 
            de votre localisation en découvrant des activités variées proposées par 
            des passionnés qui souhaitent partager leur univers et leur expertise.
          </p>
          <p>
            Vivez un moment unique en rencontrant des hôtes inspirants qui vous 
            feront découvrir leur passion à travers des ateliers des initiations 
            ou des expériences immersives dans un cadre convivial et bienveillant 
            pour apprendre échanger et profiter d’un instant hors du quotidien.
          </p>
        </div>
      </div>
    </main>

    <!-- FOOTER -->
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
  </body>
</html>
