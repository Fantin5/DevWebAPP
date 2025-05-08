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

// Vérifier que l'ID est présent dans l'URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("ID d'activité invalide.");
}

$activite_id = intval($_GET['id']);

// Récupérer l'activité
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
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($activite['titre']) ?></title>
    <link rel="stylesheet" href="Accueil.css">
    <link rel="stylesheet" href="../TEMPLATE/teteaupied.css">
</head>
<body>
    <div class="container" style="max-width: 800px; margin: 50px auto; padding: 20px;">
        <h1><?= htmlspecialchars($activite['titre']) ?></h1>

        <?php if (!empty($activite['image'])): ?>
            <img src="<?= htmlspecialchars($activite['image']) ?>" alt="Image activité" style="max-width: 100%; border-radius: 10px; margin-bottom: 20px;">
        <?php endif; ?>

        <p><strong>Description :</strong><br><?= nl2br(htmlspecialchars($activite['description'])) ?></p>
        <p><strong>Lieu :</strong> <?= htmlspecialchars($activite['lieu']) ?></p>
        <p><strong>Date :</strong> <?= htmlspecialchars($activite['date_activite']) ?></p>
        <p><strong>Créée le :</strong> <?= htmlspecialchars($activite['date_creation']) ?></p>
        <p><strong>Note :</strong> <?= htmlspecialchars($activite['note']) ?>/5</p>
        <p><strong>Tags :</strong> <?= htmlspecialchars($activite['tags']) ?></p>

        <br>
        <a href="main.php" style="display: inline-block; margin-top: 20px; background-color: #45cf91; padding: 10px 20px; color: black; border-radius: 5px; text-decoration: none;">← Retour à la liste</a>
    </div>
</body>
</html>
