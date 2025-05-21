<?php
// Start a session
session_start();

// Include necessary files
include_once '../Connexion-Inscription/config.php';
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
        
        <!-- Modern two-column layout for CGU content -->
        <div class="cgu-container">
            <!-- Enhanced sidebar navigation -->
            <div class="cgu-sidebar">
                <h2>Sommaire</h2>
                <ul class="cgu-nav">
                    <li><a href="#section1"><span>1.</span> Objet des CGU</a></li>
                    <li><a href="#section2"><span>2.</span> Définitions</a></li>
                    <li><a href="#section3"><span>3.</span> Accès à la plateforme</a></li>
                    <li><a href="#section4"><span>4.</span> Création d'un compte</a></li>
                    <li><a href="#section5"><span>5.</span> Utilisation</a></li>
                    <li><a href="#section6"><span>6.</span> Propriété intellectuelle</a></li>
                    <li><a href="#section7"><span>7.</span> Confidentialité</a></li>
                    <li><a href="#section8"><span>8.</span> Responsabilité</a></li>
                </ul>
            </div>
            
            <!-- Enhanced content area -->
            <div class="cgu-content">
                <section id="section1" class="cgu-section">
                    <h2>1. Objet des CGU</h2>
                    <p>Les présentes Conditions Générales d'Utilisation régissent l'accès et l'utilisation de la plateforme Synapse, une solution en ligne permettant aux utilisateurs de trouver des terrains de sport et de se connecter avec d'autres joueurs. En utilisant le site ou l'application, vous acceptez de respecter ces conditions.</p>
                </section>
                
                <div class="section-separator"></div>
                
                <section id="section2" class="cgu-section">
                    <h2>2. Définitions</h2>
                    <ul>
                        <li>Utilisateur : Toute personne inscrite ou non inscrite accédant à la plateforme.</li>
                        <li>Plateforme : Le site web Synapse.</li>
                        <li>Contenu : Toutes les informations, données, textes, graphiques, vidéos et autres éléments disponibles sur la plateforme.</li>
                    </ul>
                </section>
                
                <div class="section-separator"></div>
                
                <section id="section3" class="cgu-section">
                    <h2>3. Accès à la plateforme</h2>
                    <ul>
                        <li>L'accès est ouvert à toute personne disposant d'une connexion Internet.</li>
                        <li>Certaines fonctionnalités nécessitent la création d'un compte utilisateur.</li>
                    </ul>
                </section>
                
                <div class="section-separator"></div>
                
                <section id="section4" class="cgu-section">
                    <h2>4. Création d'un compte utilisateur</h2>
                    <p>Pour utiliser pleinement la plateforme, vous devez :</p>
                    <ul>
                        <li>Remplir le formulaire d'inscription en fournissant des informations exactes et à jour.</li>
                        <li>Accepter les présentes CGU.</li>
                        <li>Conserver la confidentialité de vos identifiants.</li>
                    </ul>
                </section>
                
                <div class="section-separator"></div>
                
                <section id="section5" class="cgu-section">
                    <h2>5. Utilisation de la plateforme</h2>
                    <p>En utilisant Synapse, vous vous engagez à :</p>
                    <ul>
                        <li>Respecter les lois et réglementations en vigueur.</li>
                        <li>Ne pas utiliser la plateforme à des fins frauduleuses ou illégales.</li>
                        <li>Fournir des informations véridiques concernant votre profil et vos activités.</li>
                    </ul>
                </section>
                
                <div class="section-separator"></div>
                
                <section id="section6" class="cgu-section">
                    <h2>6. Propriété intellectuelle</h2>
                    <p>Tous les contenus de la plateforme, y compris les textes, logos, images, et codes informatiques, sont protégés par les lois sur la propriété intellectuelle. Toute reproduction, distribution ou modification non autorisée est interdite.</p>
                </section>
                
                <div class="section-separator"></div>
                
                <section id="section7" class="cgu-section">
                    <h2>7. Confidentialité et données personnelles</h2>
                    <p>Synapse s'engage à protéger vos données et à ne pas les vendre à des tiers sans consentement.</p>
                </section>
                
                <div class="section-separator"></div>
                
                <section id="section8" class="cgu-section">
                    <h2>8. Responsabilité de Synapse</h2>
                    <p>Synapse met tout en œuvre pour assurer un fonctionnement optimal, mais ne peut garantir une disponibilité sans interruption ni l'absence d'erreurs. Synapse décline toute responsabilité en cas de :</p>
                    <ul>
                        <li>Mauvaise utilisation par les utilisateurs.</li>
                        <li>Pannes temporaires ou maintenance technique.</li>
                    </ul>
                </section>
            </div>
        </div>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>
    
    <!-- Add the JavaScript file for interactivity -->
    <script src="cgu.js"></script>
</body>
</html>