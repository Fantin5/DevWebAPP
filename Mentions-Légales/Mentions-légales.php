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
    <title>Synapse - Mentions Légales</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Playfair+Display:wght@400;600&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Base styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Header fix - prevent conflicts with Nouveauhead.css */
        header.header, 
        .header {
            background: #4f7259 !important;
            opacity: 1 !important;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3) !important;
            backdrop-filter: blur(20px) !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            z-index: 999 !important;
        }

        /* Ensure all header elements are visible */
        header *, .header * {
            opacity: 1 !important;
        }

        :root {
            --primary: #828977;
            --secondary: #E4D8C8;
            --text: #4a4a4a;
            --white: #ffffff;
            --light-gray: #f5f5f5;
            --border: #e0e0e0;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        body {
            background-color: var(--secondary);
            font-family: 'Open Sans', Arial, sans-serif;
            color: var(--text);
            line-height: 1.7;
        }

        /* Page container */
        .mentions-container {
            max-width: 1200px;
            margin: 120px auto 60px;
            padding: 0 20px;
        }

        /* Title styles - Updated for a more modern look */
        .mentions-title {
            text-align: center;
            margin-bottom: 50px;
            position: relative;
        }

        .mentions-title h1 {
            font-family: 'Playfair Display', serif;
            font-size: 38px;
            font-weight: 600;
            color: var(--primary);
            display: inline-block;
            padding: 10px 30px;
            background-color: var(--white);
            border-radius: 50px;
            box-shadow: var(--shadow);
            border-bottom: 3px solid var(--primary);
        }

        /* Content layout - Modified to cards instead of sections */
        .mentions-layout {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 30px;
        }

        /* Navigation sidebar - Updated style */
        .mentions-nav {
            background-color: var(--white);
            border-radius: 15px;
            box-shadow: var(--shadow);
            padding: 25px;
            position: sticky;
            top: 120px;
            height: fit-content;
            border-left: 5px solid var(--primary);
        }

        .mentions-nav h2 {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border);
            text-align: center;
        }

        .nav-list {
            list-style-type: none;
        }

        .nav-list li {
            margin-bottom: 15px;
        }

        .nav-list a {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: var(--text);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .nav-list a i {
            margin-right: 10px;
            color: var(--primary);
        }

        .nav-list a:hover,
        .nav-list a.active {
            background-color: rgba(130, 137, 119, 0.1);
            color: var(--primary);
            transform: translateX(5px);
        }

        /* Content area - Card-based approach */
        .mentions-content {
            display: flex;
            flex-direction: column;
            gap: 25px;
        }

        .mentions-card {
            background-color: var(--white);
            border-radius: 15px;
            box-shadow: var(--shadow);
            padding: 30px;
            opacity: 0;
            animation: fadeUp 0.8s forwards;
            position: relative;
            overflow: hidden;
        }

        .mentions-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background-color: var(--primary);
        }

        .mentions-card h2 {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .mentions-card h2 i {
            margin-right: 15px;
            background-color: rgba(130, 137, 119, 0.1);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .mentions-card p {
            margin-bottom: 20px;
            font-size: 16px;
        }

        /* Clean list styling */
        .mentions-card ul {
            padding-left: 0;
            margin-bottom: 20px;
            list-style-type: none;
        }

        .mentions-card li {
            position: relative;
            padding-left: 25px;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .mentions-card li::before {
            content: "→";
            position: absolute;
            left: 0;
            color: var(--primary);
            font-weight: bold;
        }

        /* Copyright section */
        .copyright-card {
            text-align: center;
            padding: 20px;
            font-weight: 500;
        }

        /* Animations */
        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive styles */
        @media (max-width: 900px) {
            .mentions-layout {
                grid-template-columns: 1fr;
            }
            
            .mentions-nav {
                position: static;
                margin-bottom: 30px;
            }
            
            .nav-list {
                display: flex;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .nav-list li {
                margin-bottom: 0;
                flex-grow: 1;
            }
            
            .nav-list a {
                padding: 10px;
                font-size: 14px;
                flex-direction: column;
                text-align: center;
            }
            
            .nav-list a i {
                margin-right: 0;
                margin-bottom: 8px;
                font-size: 18px;
            }
        }

        @media (max-width: 600px) {
            .mentions-container {
                padding: 0 15px;
                margin-top: 100px;
            }
            
            .mentions-title h1 {
                font-size: 28px;
                padding: 8px 20px;
            }
            
            .mentions-card {
                padding: 20px;
            }
            
            .mentions-card h2 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <?php include '../TEMPLATE/Nouveauhead.php'; ?>
    
    <div class="mentions-container">
        <div class="mentions-title">
            <h1>Mentions Légales</h1>
        </div>
        
        <div class="mentions-layout">
            <!-- Navigation sidebar -->
            <div class="mentions-nav">
                <h2>Sommaire</h2>
                <ul class="nav-list">
                    <li><a href="#editeur"><i class="fas fa-building"></i> Informations sur l&#039;éditeur</a></li>
                    <li><a href="#hebergement"><i class="fas fa-server"></i> Hébergement</a></li>
                    <li><a href="#propriete"><i class="fas fa-copyright"></i> Propriété intellectuelle</a></li>
                    <li><a href="#donnees"><i class="fas fa-shield-alt"></i> Protection des données personnelles</a></li>
                    <li><a href="#responsabilite"><i class="fas fa-exclamation-circle"></i> Limitation de responsabilité</a></li>
                    <li><a href="#liens"><i class="fas fa-link"></i> Liens hypertextes</a></li>
                </ul>
            </div>
            
            <!-- Content area with cards -->
            <div class="mentions-content">
                <div id="editeur" class="mentions-card" style="animation-delay: 0.1s;">
                    <h2><i class="fas fa-building"></i> Informations sur l&#039;éditeur</h2>
                    <p>Le site <strong>Synapse</strong> est édité par :</p><ul><li><strong>Société :</strong> Synapse SAS</li><li><strong>Siège social :</strong> 10 rue de Vanves, 92130 Issy-les-Moulineaux</li><li><strong>SIRET :</strong> 123 456 789 00110</li><li><strong>Email :</strong> contact@synapse.com</li><li><strong>Téléphone :</strong> +33 (0)1 23 45 67 89</li></ul>
                </div>
                <div id="hebergement" class="mentions-card" style="animation-delay: 0.2s;">
                    <h2><i class="fas fa-server"></i> Hébergement</h2>
                    <p>Le site est hébergé par :</p><ul><li><strong>Hébergeur :</strong> OVHcloud</li><li><strong>Adresse :</strong> 2 Rue Kellermann, 59100 Roubaix, France</li><li><strong>Site web :</strong> <a href="https://www.ovhcloud.com" target="_blank">www.ovhcloud.com</a></li></ul>
                </div>
                <div id="propriete" class="mentions-card" style="animation-delay: 0.3s;">
                    <h2><i class="fas fa-copyright"></i> Propriété intellectuelle</h2>
                    <p>L'ensemble des contenus présents sur ce site (textes, images, logos, vidéos, etc.) sont protégés par le droit d'auteur et la propriété intellectuelle. Toute reproduction, représentation, modification, publication, transmission, dénaturation de tout ou partie des éléments du site, par quelque procédé que ce soit, est interdite sans l'autorisation écrite préalable de Synapse.</p>
                </div>
                <div id="donnees" class="mentions-card" style="animation-delay: 0.4s;">
                    <h2><i class="fas fa-shield-alt"></i> Protection des données personnelles</h2>
                    <p>Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés, les informations personnelles collectées font l'objet d'un traitement informatique destiné à la gestion des comptes utilisateurs et des services proposés.</p><p>Vous disposez d'un droit d'accès, de rectification, de portabilité et d'effacement de vos données personnelles. Pour exercer ces droits, contactez-nous à l'adresse : dpo@synapse.com</p>
                </div>
                <div id="responsabilite" class="mentions-card" style="animation-delay: 0.5s;">
                    <h2><i class="fas fa-exclamation-circle"></i> Limitation de responsabilité</h2>
                    <p>Synapse met tout en œuvre pour offrir aux utilisateurs des informations et des outils disponibles et vérifiés, mais ne saurait être tenu responsable des erreurs, d'une absence de disponibilité des informations et/ou de la présence de virus sur son site.</p>
                </div>
                <div id="liens" class="mentions-card" style="animation-delay: 0.6s;">
                    <h2><i class="fas fa-link"></i> Liens hypertextes</h2>
                    <p>Le site peut contenir des liens vers d'autres sites internet. Synapse n'exerce aucun contrôle sur ces sites et décline toute responsabilité quant à leur contenu ou leur politique de confidentialité.</p>
                </div>
                
                <div class="mentions-card copyright-card" style="animation-delay: 0.7s;">
                    <p>© 2025 Synapse - Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get all navigation items
            const navLinks = document.querySelectorAll('.nav-list a');
            
            // Add click handler for smooth scrolling
            navLinks.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all links
                    navLinks.forEach(item => item.classList.remove('active'));
                    
                    // Add active class to clicked link
                    this.classList.add('active');
                    
                    // Get the target section
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);
                    
                    if (targetElement) {
                        // Calculate position accounting for fixed header
                        const headerOffset = 120;
                        const targetPosition = targetElement.getBoundingClientRect().top + 
                                              window.pageYOffset - headerOffset;
                        
                        // Smooth scroll to the target
                        window.scrollTo({
                            top: targetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });
            
            // Add scroll spy functionality
            window.addEventListener('scroll', function() {
                const cards = document.querySelectorAll('.mentions-card');
                
                // Determine which card is currently visible
                let currentCardId = '';
                const scrollPosition = window.scrollY + 150; // Adjust for header
                
                cards.forEach(card => {
                    if (!card.id) return; // Skip cards without ID
                    
                    const cardTop = card.offsetTop;
                    const cardHeight = card.offsetHeight;
                    
                    if (scrollPosition >= cardTop && 
                        scrollPosition < cardTop + cardHeight) {
                        currentCardId = card.id;
                    }
                });
                
                // Update active state in navigation
                if (currentCardId) {
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                        if (link.getAttribute('href') === '#' + currentCardId) {
                            link.classList.add('active');
                        }
                    });
                }
            });
            
            // Animate cards on scroll
            const observerOptions = {
                threshold: 0.1
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.animationPlayState = 'running';
                    }
                });
            }, observerOptions);
            
            document.querySelectorAll('.mentions-card').forEach(card => {
                card.style.animationPlayState = 'paused';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>