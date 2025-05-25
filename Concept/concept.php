<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Concept</title>
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
    <main class="concept-container">
      <!-- Hero Banner -->
      <img class="bandeau" src="concept-banner.jpg" alt="Concept Synapse" />

      <h1 class="concept-title">Notre Concept</h1>

      <!-- Hero Section -->
      <section class="hero-section">
        <p class="hero-text">
          Bienvenue sur <strong>SYNAPSE</strong>, la plateforme qui révolutionne les
          rencontres humaines à travers le partage de passions. Nous connectons des
          particuliers passionnés avec des curieux en quête d'expériences authentiques
          et enrichissantes.
        </p>
        <div class="hero-icons">
          <div class="hero-icon">
            <i class="fa-solid fa-users"></i>
          </div>
          <div class="hero-icon">
            <i class="fa-solid fa-heart"></i>
          </div>
          <div class="hero-icon">
            <i class="fa-solid fa-star"></i>
          </div>
        </div>
      </section>

      <!-- Stats Section -->
      <section class="stats-section">
        <div class="stat-card">
          <i class="fa-solid fa-calendar-days stat-icon"></i>
          <span class="stat-number">500+</span>
          <span class="stat-label">Activités créées</span>
        </div>
        <div class="stat-card">
          <i class="fa-solid fa-users stat-icon"></i>
          <span class="stat-number">1000+</span>
          <span class="stat-label">Membres actifs</span>
        </div>
        <div class="stat-card">
          <i class="fa-solid fa-smile stat-icon"></i>
          <span class="stat-number">95%</span>
          <span class="stat-label">Satisfaction</span>
        </div>
        <div class="stat-card">
          <i class="fa-solid fa-map-marker-alt stat-icon"></i>
          <span class="stat-number">50+</span>
          <span class="stat-label">Villes couvertes</span>
        </div>
      </section>

      <!-- Feature Section 1: Proposer -->
      <section class="feature-section">
        <div class="feature-content">
          <h2 class="feature-title">
            <i
              class="fa-solid fa-plus-circle"
              style="color: #45a163; margin-right: 15px"
            ></i>
            Proposez vos Activités
          </h2>
          <p class="feature-text">
            Que vous soyez amateur de cuisine, adepte de randonnées insolites, artisan
            dans l'âme ou expert en bricolage, partagez votre passion avec une
            communauté enthousiaste. Créez des expériences uniques et transmettez votre
            savoir-faire dans un cadre convivial.
          </p>
          <p class="feature-text">
            Proposez une activité en partageant votre passion avec la communauté en
            créant une annonce détaillée qui met en avant votre savoir-faire, votre
            expérience et ce que vous souhaitez transmettre aux participants.
          </p>
        </div>
        <div class="feature-image">
          <img src="activite1.jpg" alt="Proposer une activité" />
        </div>
      </section>

      <!-- Feature Section 2: Découvrir -->
      <section class="feature-section reverse">
        <div class="feature-content">
          <h2 class="feature-title">
            <i
              class="fa-solid fa-search"
              style="color: #45a163; margin-right: 15px"
            ></i>
            Découvrez & Participez
          </h2>
          <p class="feature-text">
            Vous cherchez une activité originale à faire près de chez vous ? Explorez
            notre sélection d'ateliers et de sorties uniques pour vivre une expérience
            inoubliable avec un hôte passionné.
          </p>
          <p class="feature-text">
            Trouvez une expérience en parcourant les nombreuses offres disponibles sur
            la plateforme en fonction de vos envies, de vos centres d'intérêt et de
            votre localisation en découvrant des activités variées proposées par des
            passionnés.
          </p>
        </div>
        <div class="feature-image">
          <img src="activite2.jpg" alt="Découvrir des activités" />
        </div>
      </section>

      <!-- Feature Section 3: Vivre -->
      <section class="feature-section">
        <div class="feature-content">
          <h2 class="feature-title">
            <i
              class="fa-solid fa-magic"
              style="color: #45a163; margin-right: 15px"
            ></i>
            Vivez des Moments Uniques
          </h2>
          <p class="feature-text">
            Rencontrez des hôtes inspirants qui vous feront découvrir leur passion à
            travers des ateliers, des initiations ou des expériences immersives dans un
            cadre convivial et bienveillant.
          </p>
          <p class="feature-text">
            Chaque activité est une opportunité d'apprendre, d'échanger et de profiter
            d'un instant hors du quotidien. Créez des liens authentiques et repartez
            avec de nouveaux savoir-faire et des souvenirs mémorables.
          </p>
        </div>
        <div class="feature-image">
          <img src="concept-banner.jpg" alt="Moments uniques" />
        </div>
      </section>

      <!-- Call to Action -->
      <section class="cta-section">
        <h2 class="cta-title">Rejoignez l'Aventure Synapse</h2>
        <p class="cta-text">
          Que vous souhaitiez partager votre passion ou découvrir de nouvelles
          expériences, Synapse est votre porte d'entrée vers une communauté bienveillante
          et enrichissante.
        </p>
        <div class="cta-buttons">
          <a href="../Testing grounds/jenis.php" class="cta-button">
            <i class="fa-solid fa-plus"></i> Créer une Activité
          </a>
          <a href="../Testing grounds/main.php" class="cta-button">
            <i class="fa-solid fa-compass"></i> Explorer les Activités
          </a>
        </div>
      </section>
    </main>

    <?php
    // Inclure le footer
    include '../TEMPLATE/footer.php';
    ?>
  </body>
</html>
