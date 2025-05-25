<?php
session_start();
include_once '../Connexion-Inscription/config.php';


// Connexion à la BDD
$conn = new mysqli('localhost', 'root', '', 'user_db');
if ($conn->connect_error) {
    die("Erreur de connexion: " . $conn->connect_error);
}

// Récupérer uniquement les règles actives (=non supprimées --> is_deleted = 0)
$sql = "SELECT id, title, content FROM cgu_rules WHERE is_deleted = 0 ORDER BY id ASC";
$result = $conn->query($sql);

// Initialiser un tableau pour stocker les règles CGU
$rules = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rules[] = $row;
    }
} else {
    // Si aucune règle n'est trouvée, on affiche un message et on initialise le tableau à vide
    $rules = [];
    echo "Aucune règle CGU trouvée.";
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Synapse CGU</title>
    <link rel="stylesheet" href="cgu.css">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>

    
    <div class="page-container">
        <div class="cgu-page-title">
            <h1>Synapse CGU</h1>
        </div>

        <!-- Conteneur pour les 2 colonnes (une avec les infos et une qui est la sidebar) -->
        <div class="cgu-container">
            <!-- Navigation sidebar -->
            <div class="cgu-sidebar">
                <h2>Sommaire</h2>
                <ul class="cgu-nav">
                    <?php foreach ($rules as $index => $rule): ?>
                        <li>
                            <a href="#section<?php echo $rule['id']; ?>">
                                <span><?php echo $index + 1; ?>.</span> <?php echo htmlspecialchars($rule['title']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <!-- Contenu et informations -->
            <div class="cgu-content">
                <?php foreach ($rules as $index => $rule): ?>
                    <section id="section<?php echo $rule['id']; ?>" class="cgu-section">
                        <h2><?php echo ($index + 1) . '. ' . htmlspecialchars($rule['title']); ?></h2>
                        <?php
                        // Affichage du contenu, en assumant que c'est du HTML prêt à l'emploi
                        // Si tu stockes du texte brut, adapte en ajoutant nl2br ou autre
                        echo $rule['content'];
                        ?>
                    </section>
                    <?php if ($index !== count($rules) - 1): ?>
                        <div class="section-separator"></div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>
    
    <!-- js pour interactivité avec la sidebar -->
    <script src="cgu.js"></script>
</body>
</html>