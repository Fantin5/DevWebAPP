<?php 
// Connexion à la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "activity";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Erreur de connexion : " . $conn->connect_error);
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID d'activité invalide.");
}

$activite_id = intval($_GET['id']);

$sql = "SELECT a.*, 
        (SELECT GROUP_CONCAT(nom_tag SEPARATOR ', ') FROM tags WHERE activite_id = a.id) AS tags 
        FROM activites a 
        WHERE a.id = $activite_id";

$result = $conn->query($sql);
if ($result->num_rows == 0) {
    die("Aucune activité trouvée.");
}

$activite = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
  />
  <link rel="stylesheet" href="./activite.css" />
  <title><?= htmlspecialchars($activite['titre']) ?></title>
</head>
<body>
  <header class="header">
    <img class="logo" src="../Connexion-Inscription/logo-transparent-pdf.png" alt="Site logo" />
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

  <?php if (!empty($activite['image'])): ?>
    <img class="bandeau" src="<?= htmlspecialchars($activite['image']) ?>" alt="Activité" />
  <?php endif; ?>

  <main>
    <section>
      <h1><?= htmlspecialchars($activite['categorie']) ?></h1>

      <div class="activity-card">
        <?php if (!empty($activite['image'])): ?>
          <img src="<?= htmlspecialchars($activite['image']) ?>" alt="<?= htmlspecialchars($activite['titre']) ?>" class="activity-image">
        <?php endif; ?>

        <div class="top-bar">
          <div class="stars"><?= str_repeat('⭐', intval($activite['note'])) ?></div>

          <div class="button-group">
            <button class="heart-button">
              <i class="fa-regular fa-heart"></i>
            </button>
            <button class="participer" onclick="ajouterAuPanier('<?= htmlspecialchars($activite['titre']) ?>', '<?= htmlspecialchars($activite['prix']) ?> €')">Participer</button>
          </div>
        </div>

        <p><strong><?= htmlspecialchars($activite['titre']) ?></strong></p>
        <p><strong><?= htmlspecialchars(date('l d F Y à H\hi', strtotime($activite['date_activite']))) ?></strong></p>
        <p><strong>À <?= htmlspecialchars($activite['lieu']) ?></strong></p>
        <p><?= htmlspecialchars($activite['places_utilisees']) ?> personnes participent - <?= htmlspecialchars($activite['places_max'] - $activite['places_utilisees']) ?> places restantes</p>
        <p><strong>Prix :</strong> <?= htmlspecialchars($activite['prix']) ?> €</p>

        <p style="margin-top: 10px;"><strong>Organisateur(s) :</strong> <?= htmlspecialchars($activite['organisateur']) ?></p>
        <p>Contact :</p>
        <p><?= htmlspecialchars($activite['email']) ?></p>
        <p><?= htmlspecialchars($activite['telephone']) ?></p>

        <?php if (!empty($activite['tags'])): ?>
          <p><strong>Tags :</strong> <?= htmlspecialchars($activite['tags']) ?></p>
        <?php endif; ?>
      </div>
    </section>
  </main>

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

  <script>
    const heartButton = document.querySelector('.heart-button');
    const heartIcon = heartButton.querySelector('i');

    heartButton.addEventListener('click', () => {
      const activityTitle = "<?= addslashes($activite['titre']) ?>";
      let favoris = JSON.parse(localStorage.getItem('favoris')) || [];

      if (heartIcon.classList.contains('fa-regular')) {
        heartIcon.classList.remove('fa-regular');
        heartIcon.classList.add('fa-solid');
        if (!favoris.includes(activityTitle)) {
          favoris.push(activityTitle);
          localStorage.setItem('favoris', JSON.stringify(favoris));
        }
      } else {
        heartIcon.classList.remove('fa-solid');
        heartIcon.classList.add('fa-regular');
        favoris = favoris.filter(item => item !== activityTitle);
        localStorage.setItem('favoris', JSON.stringify(favoris));
      }
    });

    function ajouterAuPanier(nom, prix) {
      alert("Ajouté au panier : " + nom + " pour " + prix);
    }
  </script>
</body>
</html>
