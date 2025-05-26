<?php
// admin_mentionslegales.php
include 'adminVerify.php'; // Vérification de la session admin + connexion à la base de données

// Création des tables si elles n'existent pas
function createTablesIfNotExist($conn) {
    // Désactiver temporairement les contraintes de clé étrangère
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 0");
    
    // Table principale des sections
    $createSectionsTable = "
        CREATE TABLE IF NOT EXISTS `mentions_legales_sections` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `section_key` varchar(100) NOT NULL,
            `title` varchar(255) NOT NULL,
            `content` text NOT NULL,
            `order_position` int(11) NOT NULL DEFAULT 0,
            `is_required` tinyint(1) NOT NULL DEFAULT 0,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `template_type` varchar(50) DEFAULT 'custom',
            `last_updated` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            INDEX `idx_order_position` (`order_position`),
            INDEX `idx_is_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    // Table d'historique
    $createHistoryTable = "
        CREATE TABLE IF NOT EXISTS `mentions_legales_history` (
            `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `section_id` int(10) UNSIGNED NOT NULL,
            `section_key` varchar(100) NOT NULL,
            `title` varchar(255) NOT NULL,
            `content` text NOT NULL,
            `action` enum('create','update','delete','activate','deactivate','duplicate','reorder') NOT NULL,
            `admin_id` int(11) NOT NULL,
            `admin_info` varchar(255) NOT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `user_agent` varchar(500) DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_section_id` (`section_id`),
            KEY `idx_created_at` (`created_at`),
            KEY `idx_admin_id` (`admin_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    // Exécuter les requêtes une par une avec gestion d'erreur
    $tables = [
        'mentions_legales_sections' => $createSectionsTable,
        'mentions_legales_history' => $createHistoryTable
    ];
    
    foreach ($tables as $tableName => $query) {
        $result = @mysqli_query($conn, $query);
        if (!$result) {
            $error = mysqli_error($conn);
            if (strpos($error, 'already exists') === false && 
                strpos($error, 'Duplicate key name') === false &&
                strpos($error, 'Duplicate entry') === false) {
                error_log("Erreur création table $tableName: " . $error);
            }
        }
    }
    
    // Vérifier et ajouter l'index unique sur section_key si nécessaire
    $checkUniqueIndex = "
        SELECT COUNT(*) as index_exists 
        FROM information_schema.STATISTICS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mentions_legales_sections' 
        AND INDEX_NAME = 'unique_section_key'";
    
    $result = @mysqli_query($conn, $checkUniqueIndex);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row['index_exists'] == 0) {
            $addUniqueIndex = "ALTER TABLE `mentions_legales_sections` ADD UNIQUE KEY `unique_section_key` (`section_key`)";
            @mysqli_query($conn, $addUniqueIndex);
        }
    }
    
    // Réactiver les contraintes de clé étrangère
    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS = 1");
    
    // Vérifier et ajouter la contrainte de clé étrangère si elle n'existe pas
    $checkConstraint = "
        SELECT COUNT(*) as constraint_exists 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'mentions_legales_history' 
        AND CONSTRAINT_NAME = 'fk_history_section'";
    
    $result = @mysqli_query($conn, $checkConstraint);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        if ($row['constraint_exists'] == 0) {
            $addForeignKey = "
                ALTER TABLE `mentions_legales_history` 
                ADD CONSTRAINT `fk_history_section` 
                FOREIGN KEY (`section_id`) REFERENCES `mentions_legales_sections` (`id`) 
                ON DELETE CASCADE";
            
            @mysqli_query($conn, $addForeignKey);
        }
    }
}

// Appeler la fonction de création des tables
createTablesIfNotExist($conn);

// Initialiser les sections par défaut si la table est vide
$checkSections = mysqli_query($conn, "SELECT COUNT(*) as count FROM mentions_legales_sections");
$sectionCount = mysqli_fetch_assoc($checkSections)['count'];

if ($sectionCount == 0) {
    $defaultSections = [
        ['editeur', 'Informations sur l\'éditeur', '<p>Le site <strong>Synapse</strong> est édité par :</p><ul><li><strong>Société :</strong> Synapse SAS</li><li><strong>Siège social :</strong> 10 rue de Vanves, 92130 Issy-les-Moulineaux</li><li><strong>SIRET :</strong> 123 456 789 00110</li><li><strong>Email :</strong> contact@synapse.com</li><li><strong>Téléphone :</strong> +33 (0)1 23 45 67 89</li></ul>', 1, 1],
        ['hebergement', 'Hébergement', '<p>Le site est hébergé par :</p><ul><li><strong>Hébergeur :</strong> OVHcloud</li><li><strong>Adresse :</strong> 2 Rue Kellermann, 59100 Roubaix, France</li><li><strong>Site web :</strong> <a href="https://www.ovhcloud.com" target="_blank">www.ovhcloud.com</a></li></ul>', 2, 1],
        ['propriete', 'Propriété intellectuelle', '<p>L\'ensemble des contenus présents sur ce site (textes, images, logos, vidéos, etc.) sont protégés par le droit d\'auteur et la propriété intellectuelle. Toute reproduction, représentation, modification, publication, transmission, dénaturation de tout ou partie des éléments du site, par quelque procédé que ce soit, est interdite sans l\'autorisation écrite préalable de Synapse.</p>', 3, 1],
        ['donnees', 'Protection des données personnelles', '<p>Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés, les informations personnelles collectées font l\'objet d\'un traitement informatique destiné à la gestion des comptes utilisateurs et des services proposés.</p><p>Vous disposez d\'un droit d\'accès, de rectification, de portabilité et d\'effacement de vos données personnelles. Pour exercer ces droits, contactez-nous à l\'adresse : dpo@synapse.com</p>', 4, 1],
        ['responsabilite', 'Limitation de responsabilité', '<p>Synapse met tout en œuvre pour offrir aux utilisateurs des informations et des outils disponibles et vérifiés, mais ne saurait être tenu responsable des erreurs, d\'une absence de disponibilité des informations et/ou de la présence de virus sur son site.</p>', 5, 1],
        ['liens', 'Liens hypertextes', '<p>Le site peut contenir des liens vers d\'autres sites internet. Synapse n\'exerce aucun contrôle sur ces sites et décline toute responsabilité quant à leur contenu ou leur politique de confidentialité.</p>', 6, 0]
    ];
    
    foreach ($defaultSections as $section) {
        $stmt = mysqli_prepare($conn, "INSERT INTO mentions_legales_sections (section_key, title, content, order_position, is_required) VALUES (?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssii', $section[0], $section[1], $section[2], $section[3], $section[4]);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

// Récupération des informations admin
$authorInfo = '';
$authorId = 0;
if (isset($_SESSION['user_id'])) {
    $authorId = intval($_SESSION['user_id']);
    $stmt = mysqli_prepare($conn, "SELECT CONCAT(CAST(id AS CHAR), ' - ', first_name, ' ', name) FROM user_form WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $authorId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $authorInfo);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
}

// Fonction pour générer le fichier mentions-légales.php
function generateMentionsLegalesFile($conn) {
    $sections = [];
    $result = mysqli_query($conn, "SELECT * FROM mentions_legales_sections WHERE is_active = 1 ORDER BY order_position ASC, id ASC");
    while ($row = mysqli_fetch_assoc($result)) {
        $sections[] = $row;
    }
    
    $phpContent = '<?php
// Start a session
session_start();

// Include necessary files
include_once \'../Connexion-Inscription/config.php\';
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
            font-family: \'Open Sans\', Arial, sans-serif;
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
            font-family: \'Playfair Display\', serif;
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
    <?php include \'../TEMPLATE/Nouveauhead.php\'; ?>
    
    <div class="mentions-container">
        <div class="mentions-title">
            <h1>Mentions Légales</h1>
        </div>
        
        <div class="mentions-layout">
            <!-- Navigation sidebar -->
            <div class="mentions-nav">
                <h2>Sommaire</h2>
                <ul class="nav-list">';
    
    foreach ($sections as $section) {
        $icon = match($section['section_key']) {
            'editeur' => 'fas fa-building',
            'hebergement' => 'fas fa-server',
            'propriete' => 'fas fa-copyright',
            'donnees' => 'fas fa-shield-alt',
            'responsabilite' => 'fas fa-exclamation-circle',
            'liens' => 'fas fa-link',
            default => 'fas fa-file-text'
        };
        
        $phpContent .= '
                    <li><a href="#' . htmlspecialchars($section['section_key']) . '"><i class="' . $icon . '"></i> ' . htmlspecialchars($section['title']) . '</a></li>';
    }
    
    $phpContent .= '
                </ul>
            </div>
            
            <!-- Content area with cards -->
            <div class="mentions-content">';
    
    $delay = 0.1;
    foreach ($sections as $section) {
        $icon = match($section['section_key']) {
            'editeur' => 'fas fa-building',
            'hebergement' => 'fas fa-server',
            'propriete' => 'fas fa-copyright',
            'donnees' => 'fas fa-shield-alt',
            'responsabilite' => 'fas fa-exclamation-circle',
            'liens' => 'fas fa-link',
            default => 'fas fa-file-text'
        };
        
        $phpContent .= '
                <div id="' . htmlspecialchars($section['section_key']) . '" class="mentions-card" style="animation-delay: ' . $delay . 's;">
                    <h2><i class="' . $icon . '"></i> ' . htmlspecialchars($section['title']) . '</h2>
                    ' . $section['content'] . '
                </div>';
        
        $delay += 0.1;
    }
    
    $phpContent .= '
                
                <div class="mentions-card copyright-card" style="animation-delay: ' . $delay . 's;">
                    <p>© ' . date('Y') . ' Synapse - Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </div>

    <?php include \'../TEMPLATE/footer.php\'; ?>
    
    <script>
        document.addEventListener(\'DOMContentLoaded\', function() {
            // Get all navigation items
            const navLinks = document.querySelectorAll(\'.nav-list a\');
            
            // Add click handler for smooth scrolling
            navLinks.forEach(link => {
                link.addEventListener(\'click\', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all links
                    navLinks.forEach(item => item.classList.remove(\'active\'));
                    
                    // Add active class to clicked link
                    this.classList.add(\'active\');
                    
                    // Get the target section
                    const targetId = this.getAttribute(\'href\').substring(1);
                    const targetElement = document.getElementById(targetId);
                    
                    if (targetElement) {
                        // Calculate position accounting for fixed header
                        const headerOffset = 120;
                        const targetPosition = targetElement.getBoundingClientRect().top + 
                                              window.pageYOffset - headerOffset;
                        
                        // Smooth scroll to the target
                        window.scrollTo({
                            top: targetPosition,
                            behavior: \'smooth\'
                        });
                    }
                });
            });
            
            // Add scroll spy functionality
            window.addEventListener(\'scroll\', function() {
                const cards = document.querySelectorAll(\'.mentions-card\');
                
                // Determine which card is currently visible
                let currentCardId = \'\';
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
                        link.classList.remove(\'active\');
                        if (link.getAttribute(\'href\') === \'#\' + currentCardId) {
                            link.classList.add(\'active\');
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
                        entry.target.style.animationPlayState = \'running\';
                    }
                });
            }, observerOptions);
            
            document.querySelectorAll(\'.mentions-card\').forEach(card => {
                card.style.animationPlayState = \'paused\';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>';
    
    // Écrire le fichier
    $filePath = '../Mentions-légales/Mentions-légales.php';
    
    // Créer le dossier s'il n'existe pas
    $directory = dirname($filePath);
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    
    return file_put_contents($filePath, $phpContent) !== false;
}

// Gestion spéciale pour le nettoyage d'historique (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clean_history') {
    $days = intval($_POST['days'] ?? 30);
    $stmt = mysqli_prepare($conn, "DELETE FROM mentions_legales_history WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    mysqli_stmt_bind_param($stmt, 'i', $days);
    
    if (mysqli_stmt_execute($stmt)) {
        $deletedRows = mysqli_affected_rows($conn);
        mysqli_stmt_close($stmt);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'deleted' => $deletedRows]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => mysqli_error($conn)]);
    }
    exit;
}

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
    
    switch ($action) {
        case 'add_section':
            $sectionKey = trim($_POST['section_key'] ?? '');
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $orderPosition = intval($_POST['order_position'] ?? 0);
            $isRequired = isset($_POST['is_required']) ? 1 : 0;
            
            if ($sectionKey && $title && $content) {
                $stmt = mysqli_prepare($conn, "INSERT INTO mentions_legales_sections (section_key, title, content, order_position, is_required) VALUES (?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, 'sssii', $sectionKey, $title, $content, $orderPosition, $isRequired);
                
                if (mysqli_stmt_execute($stmt)) {
                    $newId = mysqli_insert_id($conn);
                    mysqli_stmt_close($stmt);
                    
                    // Historique
                    $histStmt = mysqli_prepare($conn, "INSERT INTO mentions_legales_history (section_id, section_key, title, content, action, admin_id, admin_info, ip_address, user_agent) VALUES (?, ?, ?, ?, 'create', ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($histStmt, 'isssssss', $newId, $sectionKey, $title, $content, $authorId, $authorInfo, $ipAddress, $userAgent);
                    mysqli_stmt_execute($histStmt);
                    mysqli_stmt_close($histStmt);
                    
                    // Générer le fichier
                    generateMentionsLegalesFile($conn);
                }
            }
            break;
            
        case 'update_section':
            $id = intval($_POST['id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $content = trim($_POST['content'] ?? '');
            $isRequired = isset($_POST['is_required']) ? 1 : 0;
            
            if ($id && $title && $content) {
                // Récupérer les anciennes données ET la position actuelle
                $oldStmt = mysqli_prepare($conn, "SELECT section_key, order_position FROM mentions_legales_sections WHERE id = ?");
                mysqli_stmt_bind_param($oldStmt, 'i', $id);
                mysqli_stmt_execute($oldStmt);
                mysqli_stmt_bind_result($oldStmt, $sectionKey, $currentPosition);
                mysqli_stmt_fetch($oldStmt);
                mysqli_stmt_close($oldStmt);
                
                // Utiliser la position fournie OU garder la position actuelle
                $orderPosition = isset($_POST['order_position']) && $_POST['order_position'] !== '' 
                    ? intval($_POST['order_position']) 
                    : $currentPosition;
                
                $stmt = mysqli_prepare($conn, "UPDATE mentions_legales_sections SET title = ?, content = ?, order_position = ?, is_required = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'ssiii', $title, $content, $orderPosition, $isRequired, $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    
                    // Historique
                    $histStmt = mysqli_prepare($conn, "INSERT INTO mentions_legales_history (section_id, section_key, title, content, action, admin_id, admin_info, ip_address, user_agent) VALUES (?, ?, ?, ?, 'update', ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($histStmt, 'isssssss', $id, $sectionKey, $title, $content, $authorId, $authorInfo, $ipAddress, $userAgent);
                    mysqli_stmt_execute($histStmt);
                    mysqli_stmt_close($histStmt);
                    
                    // Générer le fichier
                    generateMentionsLegalesFile($conn);
                }
            }
            break;
            
        case 'delete_section':
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                // Récupérer les données avant suppression
                $oldStmt = mysqli_prepare($conn, "SELECT section_key, title, content FROM mentions_legales_sections WHERE id = ?");
                mysqli_stmt_bind_param($oldStmt, 'i', $id);
                mysqli_stmt_execute($oldStmt);
                mysqli_stmt_bind_result($oldStmt, $sectionKey, $oldTitle, $oldContent);
                mysqli_stmt_fetch($oldStmt);
                mysqli_stmt_close($oldStmt);
                
                $stmt = mysqli_prepare($conn, "DELETE FROM mentions_legales_sections WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $id);
                
                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    
                    // Historique
                    $histStmt = mysqli_prepare($conn, "INSERT INTO mentions_legales_history (section_id, section_key, title, content, action, admin_id, admin_info, ip_address, user_agent) VALUES (?, ?, ?, ?, 'delete', ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($histStmt, 'isssssss', $id, $sectionKey, $oldTitle, $oldContent, $authorId, $authorInfo, $ipAddress, $userAgent);
                    mysqli_stmt_execute($histStmt);
                    mysqli_stmt_close($histStmt);
                    
                    // Générer le fichier
                    generateMentionsLegalesFile($conn);
                }
            }
            break;
            
        case 'toggle_active':
            $id = intval($_POST['id'] ?? 0);
            $isActive = intval($_POST['is_active'] ?? 0);
            if ($id) {
                $stmt = mysqli_prepare($conn, "UPDATE mentions_legales_sections SET is_active = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'ii', $isActive, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                // Historique
                $actionType = $isActive ? 'activate' : 'deactivate';
                $histStmt = mysqli_prepare($conn, "INSERT INTO mentions_legales_history (section_id, section_key, title, content, action, admin_id, admin_info, ip_address, user_agent) VALUES (?, '', '', '', ?, ?, ?, ?, ?)");
                mysqli_stmt_bind_param($histStmt, 'isssss', $id, $actionType, $authorId, $authorInfo, $ipAddress, $userAgent);
                mysqli_stmt_execute($histStmt);
                mysqli_stmt_close($histStmt);
                
                // Générer le fichier
                generateMentionsLegalesFile($conn);
            }
            break;
            
        case 'reorder_sections':
            $orders = $_POST['orders'] ?? [];
            foreach ($orders as $id => $position) {
                $id = intval($id);
                $position = intval($position);
                $stmt = mysqli_prepare($conn, "UPDATE mentions_legales_sections SET order_position = ? WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'ii', $position, $id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                
                // Ajouter à l'historique pour le repositionnement
                $histStmt = mysqli_prepare($conn, "INSERT INTO mentions_legales_history (section_id, section_key, title, content, action, admin_id, admin_info, ip_address, user_agent) VALUES (?, '', 'Position changée', 'Nouvelle position: $position', 'reorder', ?, ?, ?, ?)");
                mysqli_stmt_bind_param($histStmt, 'issss', $id, $authorId, $authorInfo, $ipAddress, $userAgent);
                mysqli_stmt_execute($histStmt);
                mysqli_stmt_close($histStmt);
            }
            // Générer le fichier
            generateMentionsLegalesFile($conn);
            break;
            
        case 'duplicate_section':
            $id = intval($_POST['id'] ?? 0);
            if ($id) {
                // Récupérer la section existante
                $stmt = mysqli_prepare($conn, "SELECT * FROM mentions_legales_sections WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $section = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                
                if ($section) {
                    // Trouver la prochaine position disponible
                    $maxPosResult = mysqli_query($conn, "SELECT MAX(order_position) as max_pos FROM mentions_legales_sections");
                    $maxPos = mysqli_fetch_assoc($maxPosResult)['max_pos'] + 1;
                    
                    // Créer la nouvelle section
                    $newKey = $section['section_key'] . '_copie_' . time();
                    $newTitle = $section['title'] . ' (Copie)';
                    
                    $insertStmt = mysqli_prepare($conn, "INSERT INTO mentions_legales_sections (section_key, title, content, order_position, is_required, is_active) VALUES (?, ?, ?, ?, 0, 1)");
                    mysqli_stmt_bind_param($insertStmt, 'sssi', $newKey, $newTitle, $section['content'], $maxPos);
                    mysqli_stmt_execute($insertStmt);
                    $newSectionId = mysqli_insert_id($conn);
                    mysqli_stmt_close($insertStmt);
                    
                    // Ajouter à l'historique
                    $histStmt = mysqli_prepare($conn, "INSERT INTO mentions_legales_history (section_id, section_key, title, content, action, admin_id, admin_info, ip_address, user_agent) VALUES (?, ?, ?, ?, 'duplicate', ?, ?, ?, ?)");
                    mysqli_stmt_bind_param($histStmt, 'isssssss', $newSectionId, $newKey, $newTitle, $section['content'], $authorId, $authorInfo, $ipAddress, $userAgent);
                    mysqli_stmt_execute($histStmt);
                    mysqli_stmt_close($histStmt);
                    
                    // Générer le fichier
                    generateMentionsLegalesFile($conn);
                }
            }
            break;
    }
    
    header('Location: admin_mentionslegales.php?success=1');
    exit;
}

// Récupération des sections
$sections = [];
$result = mysqli_query($conn, "SELECT * FROM mentions_legales_sections ORDER BY order_position ASC, id ASC");
while ($row = mysqli_fetch_assoc($result)) {
    $sections[] = $row;
}

// Gestion AJAX pour l'historique d'une section
if (isset($_GET['ajax_history'])) {
    $sectionId = intval($_GET['ajax_history']);
    $histStmt = mysqli_prepare($conn, "SELECT * FROM mentions_legales_history WHERE section_id = ? ORDER BY created_at DESC");
    mysqli_stmt_bind_param($histStmt, 'i', $sectionId);
    mysqli_stmt_execute($histStmt);
    $result = mysqli_stmt_get_result($histStmt);
    
    $history = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($history);
    exit;
}

// Gestion AJAX pour l'historique global
if (isset($_GET['ajax_global_history'])) {
    $histStmt = mysqli_prepare($conn, "SELECT h.*, s.title as current_section_title FROM mentions_legales_history h LEFT JOIN mentions_legales_sections s ON h.section_id = s.id ORDER BY h.created_at DESC LIMIT 100");
    mysqli_stmt_execute($histStmt);
    $result = mysqli_stmt_get_result($histStmt);
    
    $history = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $history[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($history);
    exit;
}

// Export en JSON
if (isset($_GET['export']) && $_GET['export'] === 'json') {
    $exportData = [];
    foreach ($sections as $section) {
        $exportData[] = [
            'key' => $section['section_key'],
            'title' => $section['title'],
            'content' => $section['content'],
            'order' => $section['order_position'],
            'required' => $section['is_required'],
            'active' => $section['is_active']
        ];
    }
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="mentions_legales_' . date('Y-m-d') . '.json"');
    echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Génération du fichier à chaque chargement pour s'assurer qu'il est à jour
generateMentionsLegalesFile($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Mentions Légales</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:rgb(255, 255, 255) ;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 300;
        }
        
        .header p {
            opacity: 0.8;
            font-size: 1.1em;
        }
        
        .nav-buttons {
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #1e7e34;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .main-content {
            padding: 30px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            opacity: 0.9;
            font-size: 0.9em;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 30px;
            border-bottom: 2px solid #dee2e6;
        }
        
        .tab {
            padding: 15px 25px;
            background: none;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            color: #6c757d;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .tab.active {
            color: #007bff;
        }
        
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: #007bff;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
        }
        
        .form-section h3 {
            margin-bottom: 20px;
            color: #2c3e50;
            font-size: 1.4em;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        
        .section-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 15px;
            margin-bottom: 20px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .section-card:hover {
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .section-header {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            padding: 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: move;
        }
        
        .section-title {
            font-size: 1.3em;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .section-meta {
            display: flex;
            gap: 15px;
            align-items: center;
            font-size: 0.9em;
            color: #6c757d;
        }
        
        .section-content {
            padding: 25px;
        }
        
        .section-actions {
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-primary {
            background: #cce5ff;
            color: #004085;
        }
        
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 24px;
        }
        
        .slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .slider {
            background-color: #007bff;
        }
        
        input:checked + .slider:before {
            transform: translateX(26px);
        }
        
        .history-container {
            display: none;
            margin-top: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        
        .history-item {
            padding: 15px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .history-item:last-child {
            border-bottom: none;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .edit-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 80%;
            max-width: 900px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: #000;
        }
        
        .editor-toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px 8px 0 0;
            flex-wrap: wrap;
        }
        
        .editor-btn {
            padding: 5px 10px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.2s ease;
        }
        
        .editor-btn:hover {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .editor-area {
            min-height: 200px;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 0 0 8px 8px;
            background: white;
            border-top: none;
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 10px;
            }
            
            .header {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .main-content {
                padding: 20px;
            }
            
            .stats-row {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .section-header {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .section-actions {
                flex-direction: column;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-balance-scale"></i> Administration des Mentions Légales</h1>
            <p>Système de gestion complet avec génération automatique du fichier PHP</p>
        </div>
        
        <div class="nav-buttons">
            <a href="admin.php" class="btn btn-primary">
                <i class="fas fa-tachometer-alt"></i> Tableau de bord
            </a>
            <a href="../Testing grounds/main.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Site utilisateur
            </a>
            <a href="../Mentions-légales/Mentions-légales.php" class="btn btn-info">
                <i class="fas fa-eye"></i> Voir les mentions légales
            </a>
            <a href="?export=json" class="btn btn-warning">
                <i class="fas fa-download"></i> Exporter JSON
            </a>
            <button onclick="generateFile()" class="btn btn-success">
                <i class="fas fa-sync"></i> Régénérer le fichier PHP
            </button>
            <button onclick="showGlobalHistory()" class="btn btn-info">
                <i class="fas fa-history"></i> Historique global
            </button>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> Action réalisée avec succès ! Le fichier PHP a été mis à jour automatiquement.
            </div>
        <?php endif; ?>
        
        <div class="main-content">
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-number"><?= count($sections) ?></div>
                    <div class="stat-label">Sections totales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= count(array_filter($sections, fn($s) => $s['is_active'] == 1)) ?></div>
                    <div class="stat-label">Sections actives</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= count(array_filter($sections, fn($s) => $s['is_required'] == 1)) ?></div>
                    <div class="stat-label">Sections obligatoires</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= date('d/m/Y') ?></div>
                    <div class="stat-label">Dernière génération</div>
                </div>
            </div>
            
            <div class="tabs">
                <button class="tab active" onclick="showTab('manage')">
                    <i class="fas fa-cogs"></i> Gestion des sections
                </button>
                <button class="tab" onclick="showTab('add')">
                    <i class="fas fa-plus"></i> Ajouter une section
                </button>
                <button class="tab" onclick="showTab('settings')">
                    <i class="fas fa-sliders-h"></i> Paramètres
                </button>
            </div>
            
            <!-- Tab Gestion des sections -->
            <div id="manage-tab" class="tab-content active">
                <div class="form-section">
                    <h3><i class="fas fa-info-circle"></i> Comment utiliser ce système</h3>
                    <div style="background: white; padding: 20px; border-radius: 10px; line-height: 1.6;">
                        <p><strong>🎯 Objectif :</strong> Ce système vous permet de gérer facilement toutes les sections de vos mentions légales.</p>
                        <br>
                        <p><strong>✨ Fonctionnalités principales :</strong></p>
                        <ul style="margin-left: 20px; margin-top: 10px;">
                            <li><strong>Édition en temps réel</strong> - Modifiez le contenu et voyez les changements immédiatement</li>
                            <li><strong>Réorganisation par glisser-déposer</strong> - Changez l'ordre des sections facilement</li>
                            <li><strong>Génération automatique</strong> - Le fichier PHP est mis à jour automatiquement</li>
                            <li><strong>Historique complet</strong> - Toutes les modifications sont enregistrées</li>
                            <li><strong>Sections obligatoires</strong> - Certaines sections ne peuvent pas être supprimées</li>
                        </ul>
                        <br>
                        <p><strong>🔄 Mise à jour automatique :</strong> Chaque modification génère automatiquement le fichier <code>../Mentions-légales/Mentions-légales.php</code> que vos utilisateurs voient sur le site.</p>
                    </div>
                </div>
                
                <div id="sections-container">
                    <?php foreach ($sections as $section): ?>
                        <div class="section-card" data-id="<?= $section['id'] ?>">
                            <div class="section-header">
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <span style="cursor: grab; padding: 5px; color: #6c757d;">
                                        <i class="fas fa-grip-vertical"></i>
                                    </span>
                                    <div>
                                        <h3 class="section-title"><?= htmlspecialchars($section['title']) ?></h3>
                                        <div class="section-meta">
                                            <span><i class="fas fa-key"></i> <?= htmlspecialchars($section['section_key']) ?></span>
                                            <span>Position: <?= $section['order_position'] ?></span>
                                            <?php if ($section['is_required']): ?>
                                                <span class="badge badge-danger">Obligatoire</span>
                                            <?php endif; ?>
                                            <span class="badge <?= $section['is_active'] ? 'badge-success' : 'badge-warning' ?>">
                                                <?= $section['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="toggle-switch">
                                    <input type="checkbox" id="toggle-<?= $section['id'] ?>" 
                                           <?= $section['is_active'] ? 'checked' : '' ?>
                                           onchange="toggleSection(<?= $section['id'] ?>, this.checked)">
                                    <span class="slider"></span>
                                </div>
                            </div>
                            
                            <div class="section-content">
                                <div class="section-preview">
                                    <?= $section['content'] ?>
                                </div>
                            </div>
                            
                            <div class="section-actions">
                                <button onclick="editSection(<?= $section['id'] ?>)" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                                <button onclick="toggleHistory(<?= $section['id'] ?>)" class="btn btn-info">
                                    <i class="fas fa-history"></i> Historique
                                </button>
                                <button onclick="duplicateSection(<?= $section['id'] ?>)" class="btn btn-warning">
                                    <i class="fas fa-copy"></i> Dupliquer
                                </button>
                                <?php if (!$section['is_required']): ?>
                                    <button onclick="deleteSection(<?= $section['id'] ?>)" class="btn btn-danger">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div id="history-<?= $section['id'] ?>" class="history-container">
                                <h4><i class="fas fa-history"></i> Historique des modifications</h4>
                                <div class="history-content">
                                    <!-- L'historique sera chargé ici via AJAX -->
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Tab Ajouter une section -->
            <div id="add-tab" class="tab-content">
                <div class="form-section">
                    <h3><i class="fas fa-plus-circle"></i> Ajouter une nouvelle section</h3>
                    <form method="POST" id="add-section-form">
                        <input type="hidden" name="action" value="add_section">
                        
                        <div class="form-group">
                            <label for="section_key">Clé de section (unique) *</label>
                            <input type="text" name="section_key" id="section_key" class="form-control" 
                                   pattern="[a-z0-9_]+" title="Lettres minuscules, chiffres et underscores uniquement" required>
                            <small style="color: #6c757d;">Utilisée pour identifier la section (ex: editeur, hebergement)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="title">Titre de la section *</label>
                            <input type="text" name="title" id="title" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="order_position">Position d'affichage</label>
                            <input type="number" name="order_position" id="order_position" class="form-control" 
                                   value="<?= count($sections) + 1 ?>" min="1">
                        </div>
                        
                        <div class="form-group">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="is_required" id="is_required">
                                <label for="is_required">Section obligatoire (ne peut pas être supprimée)</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="content">Contenu *</label>
                            <div class="editor-toolbar">
                                <button type="button" class="editor-btn" onclick="execCommand('bold')">
                                    <i class="fas fa-bold"></i>
                                </button>
                                <button type="button" class="editor-btn" onclick="execCommand('italic')">
                                    <i class="fas fa-italic"></i>
                                </button>
                                <button type="button" class="editor-btn" onclick="execCommand('underline')">
                                    <i class="fas fa-underline"></i>
                                </button>
                                <button type="button" class="editor-btn" onclick="execCommand('insertUnorderedList')">
                                    <i class="fas fa-list-ul"></i>
                                </button>
                                <button type="button" class="editor-btn" onclick="execCommand('insertOrderedList')">
                                    <i class="fas fa-list-ol"></i>
                                </button>
                                <button type="button" class="editor-btn" onclick="execCommand('createLink')">
                                    <i class="fas fa-link"></i>
                                </button>
                            </div>
                            <div id="content-editor" class="editor-area" contenteditable="true">
                                <p>Saisissez le contenu de votre section ici...</p>
                            </div>
                            <textarea name="content" id="content" style="display: none;" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Ajouter la section
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Tab Paramètres -->
            <div id="settings-tab" class="tab-content">
                <div class="form-section">
                    <h3><i class="fas fa-sliders-h"></i> Paramètres et maintenance</h3>
                    
                    <div style="display: grid; gap: 30px;">
                        <div style="background: white; padding: 20px; border-radius: 10px;">
                            <h4><i class="fas fa-broom"></i> Nettoyage de l'historique</h4>
                            <p>Supprimer les entrées d'historique anciennes pour optimiser la base de données.</p>
                            <div style="display: flex; gap: 10px; align-items: end; margin-top: 15px;">
                                <div>
                                    <label for="days">Supprimer les entrées plus anciennes que :</label>
                                    <input type="number" id="days" value="30" min="1" class="form-control" style="width: 120px;">
                                </div>
                                <span>jours</span>
                                <button onclick="cleanHistory()" class="btn btn-warning">
                                    <i class="fas fa-broom"></i> Nettoyer
                                </button>
                            </div>
                        </div>
                        
                        <div style="background: white; padding: 20px; border-radius: 10px;">
                            <h4><i class="fas fa-sync"></i> Régénération du fichier</h4>
                            <p>Le fichier PHP est normalement généré automatiquement. Utilisez cette option si vous rencontrez des problèmes.</p>
                            <button onclick="generateFile()" class="btn btn-success">
                                <i class="fas fa-sync"></i> Régénérer maintenant
                            </button>
                        </div>
                        
                        <div style="background: white; padding: 20px; border-radius: 10px;">
                            <h4><i class="fas fa-sort-numeric-down"></i> Réorganisation automatique</h4>
                            <p>Réorganise automatiquement les positions des sections pour éviter les doublons.</p>
                            <button onclick="resetOrder()" class="btn btn-secondary">
                                <i class="fas fa-sort-numeric-down"></i> Réorganiser les positions
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal d'édition -->
    <div id="edit-modal" class="edit-modal">
        <div class="modal-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><i class="fas fa-edit"></i> Modifier la section</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="edit-form" method="POST">
                <input type="hidden" name="action" value="update_section">
                <input type="hidden" name="id" id="edit-id">
                
                <div class="form-group">
                    <label for="edit-title">Titre de la section *</label>
                    <input type="text" name="title" id="edit-title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit-order">Position d'affichage</label>
                    <input type="number" name="order_position" id="edit-order" class="form-control" min="1">
                </div>
                
                <div class="form-group">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input type="checkbox" name="is_required" id="edit-required">
                        <label for="edit-required">Section obligatoire</label>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit-content">Contenu *</label>
                    <div class="editor-toolbar">
                        <button type="button" class="editor-btn" onclick="execEditCommand('bold')">
                            <i class="fas fa-bold"></i>
                        </button>
                        <button type="button" class="editor-btn" onclick="execEditCommand('italic')">
                            <i class="fas fa-italic"></i>
                        </button>
                        <button type="button" class="editor-btn" onclick="execEditCommand('underline')">
                            <i class="fas fa-underline"></i>
                        </button>
                        <button type="button" class="editor-btn" onclick="execEditCommand('insertUnorderedList')">
                            <i class="fas fa-list-ul"></i>
                        </button>
                        <button type="button" class="editor-btn" onclick="execEditCommand('insertOrderedList')">
                            <i class="fas fa-list-ol"></i>
                        </button>
                        <button type="button" class="editor-btn" onclick="execEditCommand('createLink')">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                    <div id="edit-content-editor" class="editor-area" contenteditable="true"></div>
                    <textarea name="content" id="edit-content" style="display: none;" required></textarea>
                </div>
                
                <div style="display: flex; gap: 10px; justify-content: flex-end;">
                    <button type="button" onclick="closeEditModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Annuler
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Sauvegarder
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal d'historique global -->
    <div id="global-history-modal" class="edit-modal">
        <div class="modal-content" style="max-width: 1200px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><i class="fas fa-history"></i> Historique global de toutes les modifications</h2>
                <span class="close" onclick="closeGlobalHistoryModal()">&times;</span>
            </div>
            <div id="global-history-content">
                <div style="text-align: center; padding: 20px;">
                    <i class="fas fa-spinner fa-spin"></i> Chargement de l'historique...
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Variables globales
        let sortable = null;
        
        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            initSortable();
            setupFormValidation();
        });
        
        // Gestion des onglets
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.getElementById(tabName + '-tab').classList.add('active');
            event.target.classList.add('active');
        }
        
        // Initialiser le drag & drop
        function initSortable() {
            const container = document.getElementById('sections-container');
            if (container) {
                sortable = Sortable.create(container, {
                    handle: '.fas.fa-grip-vertical',
                    animation: 150,
                    onEnd: function(evt) {
                        updateOrder();
                    }
                });
            }
        }
        
        // Mettre à jour l'ordre des sections
        function updateOrder() {
            const cards = document.querySelectorAll('.section-card');
            const orders = {};
            
            cards.forEach((card, index) => {
                const id = card.getAttribute('data-id');
                orders[id] = index + 1;
            });
            
            const formData = new FormData();
            formData.append('action', 'reorder_sections');
            
            Object.keys(orders).forEach(id => {
                formData.append(`orders[${id}]`, orders[id]);
            });
            
            fetch('admin_mentionslegales.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                showNotification('Ordre mis à jour');
                setTimeout(() => location.reload(), 1000);
            });
        }
        
        // Éditeur de texte riche
        function execCommand(command, value = null) {
            if (command === 'createLink') {
                value = prompt('Entrez l\'URL:');
                if (!value) return;
            }
            
            document.execCommand(command, false, value);
            syncEditor();
        }
        
        function execEditCommand(command, value = null) {
            if (command === 'createLink') {
                value = prompt('Entrez l\'URL:');
                if (!value) return;
            }
            
            document.execCommand(command, false, value);
            syncEditEditor();
        }
        
        function syncEditor() {
            const editor = document.getElementById('content-editor');
            const textarea = document.getElementById('content');
            if (editor && textarea) {
                textarea.value = editor.innerHTML;
            }
        }
        
        function syncEditEditor() {
            const editor = document.getElementById('edit-content-editor');
            const textarea = document.getElementById('edit-content');
            if (editor && textarea) {
                textarea.value = editor.innerHTML;
            }
        }
        
        // Soumission du formulaire d'ajout
        document.getElementById('add-section-form').addEventListener('submit', function(e) {
            syncEditor();
        });
        
        // Soumission du formulaire d'édition
        document.getElementById('edit-form').addEventListener('submit', function(e) {
            syncEditEditor();
        });
        
        // Configuration de la validation des formulaires
        function setupFormValidation() {
            document.getElementById('section_key').addEventListener('input', function(e) {
                this.value = this.value.toLowerCase()
                                      .replace(/\s+/g, '_')
                                      .replace(/[^a-z0-9_]/g, '');
            });
            
            document.getElementById('content-editor').addEventListener('input', syncEditor);
            document.getElementById('edit-content-editor').addEventListener('input', syncEditEditor);
        }
        
        // Activation/désactivation des sections
        function toggleSection(sectionId, isActive) {
            const formData = new FormData();
            formData.append('action', 'toggle_active');
            formData.append('id', sectionId);
            formData.append('is_active', isActive ? 1 : 0);
            
            fetch('admin_mentionslegales.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                showNotification(isActive ? 'Section activée' : 'Section désactivée');
                setTimeout(() => location.reload(), 1000);
            });
        }
        
        // Édition de section
        function editSection(sectionId) {
            // Récupérer les données de la section
            const sectionCard = document.querySelector(`[data-id="${sectionId}"]`);
            const title = sectionCard.querySelector('.section-title').textContent;
            const content = sectionCard.querySelector('.section-preview').innerHTML;
            
            // Récupérer la position actuelle depuis l'affichage
            const positionText = sectionCard.querySelector('.section-meta').textContent;
            const positionMatch = positionText.match(/Position:\s*(\d+)/);
            const currentPosition = positionMatch ? positionMatch[1] : 1;
            
            // Remplir le formulaire d'édition
            document.getElementById('edit-id').value = sectionId;
            document.getElementById('edit-title').value = title;
            document.getElementById('edit-order').value = currentPosition;
            document.getElementById('edit-content-editor').innerHTML = content;
            syncEditEditor();
            
            // Afficher la modal
            document.getElementById('edit-modal').style.display = 'block';
        }
        
        function closeEditModal() {
            document.getElementById('edit-modal').style.display = 'none';
        }
        
        // Historique global
        function showGlobalHistory() {
            document.getElementById('global-history-modal').style.display = 'block';
            
            fetch('admin_mentionslegales.php?ajax_global_history=1')
                .then(response => response.json())
                .then(history => {
                    let historyHtml = '<div style="max-height: 600px; overflow-y: auto;">';
                    
                    if (history.length === 0) {
                        historyHtml += '<p style="text-align: center; color: #6c757d;">Aucun historique disponible</p>';
                    } else {
                        historyHtml += '<table style="width: 100%; border-collapse: collapse;">';
                        historyHtml += '<thead><tr style="background: #f8f9fa;"><th style="padding: 10px; border: 1px solid #dee2e6;">Date</th><th style="padding: 10px; border: 1px solid #dee2e6;">Action</th><th style="padding: 10px; border: 1px solid #dee2e6;">Section</th><th style="padding: 10px; border: 1px solid #dee2e6;">Titre</th><th style="padding: 10px; border: 1px solid #dee2e6;">Administrateur</th></tr></thead>';
                        historyHtml += '<tbody>';
                        
                        history.forEach(item => {
                            const actionBadge = getActionBadge(item.action);
                            const sectionTitle = item.current_section_title || item.title || 'Section supprimée';
                            
                            historyHtml += `
                                <tr>
                                    <td style="padding: 10px; border: 1px solid #dee2e6; font-size: 0.9em;">${item.created_at}</td>
                                    <td style="padding: 10px; border: 1px solid #dee2e6;">${actionBadge}</td>
                                    <td style="padding: 10px; border: 1px solid #dee2e6;"><strong>${item.section_key || 'N/A'}</strong></td>
                                    <td style="padding: 10px; border: 1px solid #dee2e6;">${sectionTitle}</td>
                                    <td style="padding: 10px; border: 1px solid #dee2e6; font-size: 0.9em;">${item.admin_info}</td>
                                </tr>
                            `;
                        });
                        
                        historyHtml += '</tbody></table>';
                    }
                    
                    historyHtml += '</div>';
                    document.getElementById('global-history-content').innerHTML = historyHtml;
                });
        }
        
        function getActionBadge(action) {
            const badges = {
                'create': '<span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">✅ Création</span>',
                'update': '<span style="background: #cce5ff; color: #004085; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">✏️ Modification</span>',
                'delete': '<span style="background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">🗑️ Suppression</span>',
                'duplicate': '<span style="background: #fff3cd; color: #856404; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">📋 Duplication</span>',
                'activate': '<span style="background: #d1ecf1; color: #0c5460; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">🔛 Activation</span>',
                'deactivate': '<span style="background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">🔴 Désactivation</span>',
                'reorder': '<span style="background: #e2e3e5; color: #383d41; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">🔄 Repositionnement</span>'
            };
            
            return badges[action] || `<span style="background: #f8f9fa; color: #6c757d; padding: 4px 8px; border-radius: 4px; font-size: 0.8em;">${action}</span>`;
        }
        
        function closeGlobalHistoryModal() {
            document.getElementById('global-history-modal').style.display = 'none';
        }
        
        // Suppression de section
        function deleteSection(sectionId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette section ? Cette action est irréversible.')) {
                const formData = new FormData();
                formData.append('action', 'delete_section');
                formData.append('id', sectionId);
                
                fetch('admin_mentionslegales.php', {
                    method: 'POST',
                    body: formData
                }).then(() => {
                    showNotification('Section supprimée');
                    setTimeout(() => location.reload(), 1000);
                });
            }
        }
        
        // Duplication de section
        function duplicateSection(sectionId) {
            const formData = new FormData();
            formData.append('action', 'duplicate_section');
            formData.append('id', sectionId);
            
            fetch('admin_mentionslegales.php', {
                method: 'POST',
                body: formData
            }).then(() => {
                showNotification('Section dupliquée');
                setTimeout(() => location.reload(), 1000);
            });
        }
        
        // Historique des sections
        function toggleHistory(sectionId) {
            const historyContainer = document.getElementById(`history-${sectionId}`);
            
            if (historyContainer.style.display === 'block') {
                historyContainer.style.display = 'none';
                return;
            }
            
            fetch(`admin_mentionslegales.php?ajax_history=${sectionId}`)
                .then(response => response.json())
                .then(history => {
                    let historyHtml = '';
                    history.forEach(item => {
                        historyHtml += `
                            <div class="history-item">
                                <div>
                                    <strong>${item.action}</strong> - ${item.admin_info}
                                    <br><small>${item.created_at}</small>
                                </div>
                            </div>
                        `;
                    });
                    
                    historyContainer.querySelector('.history-content').innerHTML = historyHtml;
                    historyContainer.style.display = 'block';
                });
        }
        
        // Génération du fichier
        function generateFile() {
            showNotification('Génération du fichier en cours...');
            fetch('admin_mentionslegales.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=generate_file'
            }).then(() => {
                showNotification('Fichier PHP généré avec succès', 'success');
                setTimeout(() => location.reload(), 1000);
            });
        }
        
        // Nettoyage d'historique
        function cleanHistory() {
            const days = document.getElementById('days').value;
            if (!confirm(`Êtes-vous sûr de vouloir supprimer l'historique de plus de ${days} jours ?`)) {
                return;
            }
            
            showNotification('Nettoyage en cours...', 'info');
            
            const formData = new FormData();
            formData.append('action', 'clean_history');
            formData.append('days', days);
            
            fetch('admin_mentionslegales.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(`✅ ${data.deleted} entrées supprimées de l'historique`, 'success');
                } else {
                    showNotification('❌ Erreur lors du nettoyage: ' + data.error, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('❌ Erreur lors du nettoyage de l\'historique', 'error');
            });
        }
        
        // Réorganisation des positions
        function resetOrder() {
            if (confirm('Réorganiser toutes les sections par position croissante ?')) {
                const cards = document.querySelectorAll('.section-card');
                const orders = {};
                
                cards.forEach((card, index) => {
                    const id = card.getAttribute('data-id');
                    orders[id] = index + 1;
                });
                
                const formData = new FormData();
                formData.append('action', 'reorder_sections');
                
                Object.keys(orders).forEach(id => {
                    formData.append(`orders[${id}]`, orders[id]);
                });
                
                fetch('admin_mentionslegales.php', {
                    method: 'POST',
                    body: formData
                }).then(() => {
                    showNotification('Positions réorganisées');
                    setTimeout(() => location.reload(), 1000);
                });
            }
        }
        
        // Notifications
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : '#007bff'};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                z-index: 1001;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            `;
            notification.innerHTML = `<i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'}"></i> ${message}`;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 4000);
        }
        
        // Fermer les modals en cliquant à l'extérieur
        window.onclick = function(event) {
            const editModal = document.getElementById('edit-modal');
            const globalHistoryModal = document.getElementById('global-history-modal');
            
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === globalHistoryModal) {
                closeGlobalHistoryModal();
            }
        }
    </script>
</body>
</html>