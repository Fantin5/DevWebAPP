<?php
// Configuration de la base de données
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "activity";

// Créer une connexion
$conn = new mysqli($servername, $username, $password, $dbname);

// Vérifier la connexion
if ($conn->connect_error) {
    die("Échec de la connexion à la base de données: " . $conn->connect_error);
}

// Vérifier si un ID d'activité est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Rediriger vers la page principale si aucun ID valide n'est fourni
    header("Location: main.php");
    exit();
}

$activity_id = $_GET['id'];

// Récupérer les détails de l'activité
$sql = "SELECT a.*, 
        (SELECT GROUP_CONCAT(nom_tag) FROM tags WHERE activite_id = a.id) AS tags
        FROM activites a 
        WHERE a.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$result = $stmt->get_result();

// Vérifier si l'activité existe
if ($result->num_rows === 0) {
    // Rediriger vers la page principale si l'activité n'existe pas
    header("Location: main.php");
    exit();
}

$activity = $result->fetch_assoc();

// Formater les tags
$tags = $activity["tags"] ? explode(',', $activity["tags"]) : [];

// Générer une note aléatoire pour la démonstration (à remplacer par un vrai système de notation)
$randomRating = rand(30, 50) / 10; // Note entre 3.0 et 5.0

// Fonction pour obtenir les étoiles formatées basées sur la note
function getStars($rating) {
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
    
    $stars = '';
    
    // Étoiles pleines
    for ($i = 0; $i < $fullStars; $i++) {
        $stars .= '<i class="fa-solid fa-star"></i>';
    }
    
    // Demi-étoile si nécessaire
    if ($halfStar) {
        $stars .= '<i class="fa-solid fa-star-half-stroke"></i>';
    }
    
    // Étoiles vides
    for ($i = 0; $i < $emptyStars; $i++) {
        $stars .= '<i class="fa-regular fa-star"></i>';
    }
    
    return '<span class="stars">' . $stars . '</span> <span class="rating-value">' . number_format($rating, 1) . '</span>';
}

// Function to determine the CSS class for tags
function getTagClass($tag) {
    $tagClasses = [
        'art' => 'primary',
        'cuisine' => 'secondary',
        'bien_etre' => 'accent',
        'creativite' => 'primary',
        'sport' => 'secondary',
        'exterieur' => 'accent',
        'interieur' => 'secondary',
        'gratuit' => 'accent',
        'ecologie' => 'primary',
        'randonnee' => 'secondary',
        'jardinage' => 'accent',
        'meditation' => 'primary',
        'artisanat' => 'secondary'
    ];
    
    return isset($tagClasses[$tag]) ? $tagClasses[$tag] : '';
}

// Formater le prix
$isPaid = $activity["prix"] > 0;
$priceText = $isPaid ? number_format($activity["prix"], 2) . " €" : "Gratuit";

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($activity["titre"]); ?> | Synapse</title>
    <link rel="stylesheet" href="main.css">
    <link rel="stylesheet" href="../TEMPLATE/teteaupied.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Styles spécifiques à la page d'activité */
        .activity-container {
            width: 90%;
            max-width: 1200px;
            margin: 40px auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .activity-header {
            position: relative;
            height: 400px;
            overflow: hidden;
        }
        
        .activity-header img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .activity-header-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
            padding: 30px;
            color: white;
        }
        
        .activity-title {
            font-size: 36px;
            margin: 0 0 10px 0;
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
        }
        
        .activity-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .activity-rating {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #f1c40f;
            font-size: 18px;
        }
        
        .activity-period {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
        }
        
        .activity-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .activity-tag {
            background-color: #828977;
            color: white;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .activity-tag.primary {
            background-color: var(--primary-color);
            color: #111;
        }
        
        .activity-tag.secondary {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .activity-tag.accent {
            background-color: var(--accent-color);
            color: #111;
        }
        
        .activity-content {
            padding: 40px;
        }
        
        .activity-section {
            margin-bottom: 30px;
        }
        
        .activity-section h2 {
            color: #828977;
            font-size: 24px;
            margin-bottom: 15px;
            position: relative;
            padding-bottom: 10px;
        }
        
        .activity-section h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 3px;
            background-color: var(--primary-color);
            border-radius: 2px;
        }
        
        .activity-description {
            font-size: 16px;
            line-height: 1.8;
            color: #444;
            white-space: pre-wrap;
        }
        
        .activity-price-section {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background-color: #f9f9f9;
            padding: 25px 40px;
            border-top: 1px solid #eee;
        }
        
        .activity-price {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        
        .activity-price.free {
            color: var(--primary-color);
        }
        
        .signup-button {
            background-color: var(--primary-color);
            color: #111;
            font-weight: bold;
            font-size: 16px;
            padding: 12px 30px;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(69, 161, 99, 0.3);
        }
        
        .signup-button:hover {
            background-color: #3abd7a;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(69, 161, 99, 0.4);
        }
        
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #f5f5f5;
            color: #666;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        
        .back-button:hover {
            background-color: #eee;
            transform: translateY(-2px);
        }
        
        .activity-created {
            font-size: 14px;
            color: #999;
            margin-top: 20px;
            text-align: right;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .activity-header {
                height: 250px;
            }
            
            .activity-title {
                font-size: 24px;
            }
            
            .activity-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .activity-price-section {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="./main.php">
            <img class="logo" src="../Connexion-Inscription/logo-transparent-pdf.png" alt="Logo Synapse">
        </a>
        <nav class="nav-links">
            <ul>
                <li><a href="#">Devenez Prestataire</a></li>
                <li><a href="#">Concept</a></li>
            </ul>
        </nav>

        <div class="icon">
            <i class="fa-regular fa-heart" aria-label="Favoris"></i>
            <a href="panier.html" class="panier-link" aria-label="Panier">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="panier-count" id="panier-count">0</span>
            </a>
            <a href="../Connexion-Inscription/Connexion.html" class="connexion-profil" aria-label="Connexion">
                <i class="fa-solid fa-user"></i>
            </a>
        </div>
    </header>

    <div class="activity-container">
        <a href="main.php" class="back-button">
            <i class="fa-solid fa-arrow-left"></i> Retour aux activités
        </a>
        
        <div class="activity-header">
            <?php if ($activity["image_url"]): ?>
                <img src="<?php echo htmlspecialchars($activity["image_url"]); ?>" alt="<?php echo htmlspecialchars($activity["titre"]); ?>">
            <?php else: ?>
                <img src="nature-placeholder.jpg" alt="Image par défaut">
            <?php endif; ?>
            
            <div class="activity-header-overlay">
                <h1 class="activity-title"><?php echo htmlspecialchars($activity["titre"]); ?></h1>
                
                <div class="activity-meta">
                    <div class="activity-rating">
                        <?php echo getStars($randomRating); ?>
                    </div>
                    
                    <?php if ($activity["date_ou_periode"]): ?>
                        <div class="activity-period">
                            <i class="fa-regular fa-calendar"></i>
                            <?php echo htmlspecialchars($activity["date_ou_periode"]); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="activity-tags">
                    <?php foreach ($tags as $tag): ?>
                        <span class="activity-tag <?php echo getTagClass($tag); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $tag)); ?>
                        </span>
                    <?php endforeach; ?>
                    
                    <?php if ($isPaid): ?>
                        <span class="activity-tag">Payant</span>
                    <?php else: ?>
                        <span class="activity-tag accent">Gratuit</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="activity-content">
            <div class="activity-section">
                <h2>Description</h2>
                <div class="activity-description">
                    <?php echo nl2br(htmlspecialchars($activity["description"])); ?>
                </div>
            </div>
            
            <?php if (isset($activity["date_creation"])): ?>
                <p class="activity-created">
                    Activité créée le <?php echo date("d/m/Y", strtotime($activity["date_creation"])); ?>
                </p>
            <?php endif; ?>
        </div>
        
        <div class="activity-price-section">
            <div class="activity-price <?php echo $isPaid ? '' : 'free'; ?>">
                <?php echo $priceText; ?>
            </div>
            
            <button class="signup-button" id="signup-button" data-id="<?php echo $activity['id']; ?>">
                <i class="fa-solid fa-user-plus"></i> S'inscrire à cette activité
            </button>
        </div>
    </div>

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
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser le panier s'il n'existe pas déjà
            if (!localStorage.getItem('synapse-cart')) {
                localStorage.setItem('synapse-cart', JSON.stringify([]));
            }
            
            // Mettre à jour le compteur du panier
            updateCartCount();
            
            // Configurer le bouton d'inscription
            const signupButton = document.getElementById('signup-button');
            if (signupButton) {
                signupButton.addEventListener('click', function() {
                    alert('Fonctionnalité d\'inscription à venir. Cette activité sera développée ultérieurement.');
                });
            }
            
            // Fonction pour mettre à jour le compteur du panier
            function updateCartCount() {
                const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
                const cartCount = document.getElementById('panier-count');
                if (cartCount) {
                    cartCount.textContent = cart.length;
                }
            }
        });
    </script>
</body>
</html>

<?php
// Fermer la connexion à la base de données
$stmt->close();
$conn->close();
?>