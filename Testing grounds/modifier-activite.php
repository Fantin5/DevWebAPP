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

// Vérifier si un ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Rediriger vers la page des activités si aucun ID valide n'est fourni
    header("Location: mes-activites.php");
    exit();
}

$activity_id = $_GET['id'];
$errors = array();
$success = '';

// Récupérer les détails de l'activité existante
function getActivityDetails($conn, $id) {
    $sql = "SELECT a.*, 
            (SELECT GROUP_CONCAT(nom_tag) FROM tags WHERE activite_id = a.id) AS tags
            FROM activites a 
            WHERE a.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: mes-activites.php");
        exit();
    }
    
    $activity = $result->fetch_assoc();
    $stmt->close();
    
    return $activity;
}

// Récupérer les tags de l'activité
function getActivityTags($conn, $id) {
    $sql = "SELECT nom_tag FROM tags WHERE activite_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tags = array();
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row['nom_tag'];
    }
    
    $stmt->close();
    return $tags;
}

// Récupérer les détails de l'activité
$activity = getActivityDetails($conn, $activity_id);
$selectedTags = getActivityTags($conn, $activity_id);

// Traitement du formulaire de mise à jour
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $titre = $conn->real_escape_string($_POST['titre']);
    $description = $conn->real_escape_string($_POST['description']);
    $date_ou_periode = isset($_POST['date_ou_periode']) ? $conn->real_escape_string($_POST['date_ou_periode']) : '';
    
    // Gestion du prix
    $prix = 0;
    if (isset($_POST['type_prix']) && $_POST['type_prix'] == 'payant' && isset($_POST['prix'])) {
        $prix = floatval($_POST['prix']);
    }
    
    // Gestion de l'image
    $image_url = $activity['image_url']; // Garder l'image existante par défaut
    
    // Si une image recadrée a été fournie
    if (isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])) {
        $cropped_image = $_POST['cropped_image'];
        
        // Extraire les données binaires de l'image base64
        $image_parts = explode(";base64,", $cropped_image);
        $image_base64 = isset($image_parts[1]) ? $image_parts[1] : $cropped_image;
        $image_data = base64_decode($image_base64);
        
        // Générer un nom de fichier unique
        $filename = 'activite_' . time() . '_' . uniqid() . '.jpg';
        $upload_path = 'uploads/images/' . $filename;
        
        // Créer le répertoire s'il n'existe pas
        if (!file_exists('uploads/images/')) {
            mkdir('uploads/images/', 0777, true);
        }
        
        // Sauvegarder l'image
        if (file_put_contents($upload_path, $image_data)) {
            $image_url = $upload_path;
        }
    } 
    // Si un fichier a été uploadé directement sans recadrage
    elseif (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $new_filename = 'activite_' . time() . '_' . uniqid() . '.' . $filetype;
            $upload_path = 'uploads/images/' . $new_filename;
            
            if (!file_exists('uploads/images/')) {
                mkdir('uploads/images/', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = $upload_path;
            }
        }
    }
    
    // Validation
    if (empty($titre)) {
        $errors[] = "Le titre est requis.";
    }
    
    if (empty($description)) {
        $errors[] = "La description est requise.";
    }
    
    if (empty($date_ou_periode)) {
        $errors[] = "La date ou période est requise.";
    }
    
    // Si tout est valide, mettre à jour la base de données
    if (empty($errors)) {
        // Mettre à jour l'activité
        $sql = "UPDATE activites SET titre = ?, description = ?, image_url = ?, prix = ?, date_ou_periode = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdsi", $titre, $description, $image_url, $prix, $date_ou_periode, $activity_id);
        
        if ($stmt->execute()) {
            // Supprimer les anciens tags
            $sql_delete_tags = "DELETE FROM tags WHERE activite_id = ?";
            $stmt_delete = $conn->prepare($sql_delete_tags);
            $stmt_delete->bind_param("i", $activity_id);
            $stmt_delete->execute();
            $stmt_delete->close();
            
            // Ajouter les nouveaux tags
            if (isset($_POST['tags']) && is_array($_POST['tags'])) {
                foreach ($_POST['tags'] as $tag) {
                    $tag = $conn->real_escape_string($tag);
                    $sql_tag = "INSERT INTO tags (activite_id, nom_tag) VALUES (?, ?)";
                    $stmt_tag = $conn->prepare($sql_tag);
                    $stmt_tag->bind_param("is", $activity_id, $tag);
                    $stmt_tag->execute();
                    $stmt_tag->close();
                }
            }
            
            $success = "L'activité a été mise à jour avec succès.";
            
            // Mettre à jour les données de l'activité après mise à jour
            $activity = getActivityDetails($conn, $activity_id);
            $selectedTags = getActivityTags($conn, $activity_id);
        } else {
            $errors[] = "Erreur lors de la mise à jour: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Modifier l'activité | Synapse</title>
    <link rel="stylesheet" href="stylejenis.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
    <style>
        .form-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .back-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #828977;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .back-link:hover {
            transform: translateX(-3px);
        }
        
        .success-message {
            background-color: #45cf91;
            color: #111;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-message {
            background-color: #e74c3c;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .current-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="./main.php">
            <img class="logo" src="../Connexion-Inscription/logo-transparent-pdf.png" alt="Logo Synapse"/>
        </a>
        <nav class="nav-links">
            <ul>
                <li><a href="#">Devenez Prestataire</a></li>
                <li><a href="../Concept/concept.html">Concept</a></li>
            </ul>
        </nav>

        <div class="icon">
            <i class="fa-regular fa-heart" aria-label="Favoris"></i>
            <a href="panier.html" class="panier-link" aria-label="Panier">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="panier-count" id="panier-count">0</span>
            </a>
            <div class="profile-dropdown">
                <a href="#" class="connexion-profil" aria-label="Profil">
                    <i class="fa-solid fa-user"></i>
                </a>
                <div class="dropdown-content">
                    <a href="../Connexion-Inscription/Connexion.html"><i class="fa-solid fa-right-to-bracket"></i> Connexion</a>
                    <a href="mes-activites.php"><i class="fa-solid fa-calendar-days"></i> Mes activités</a>
                    <a href="#"><i class="fa-solid fa-gear"></i> Paramètres</a>
                </div>
            </div>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="form-container">
            <div class="leaf-decoration leaf-top-left"></div>
            <div class="leaf-decoration leaf-bottom-right"></div>

            <div class="form-header">
                <a href="mes-activites.php" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> Retour à mes activités
                </a>
                <h1>Modifier l'activité</h1>
            </div>

            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="modifier-activite.php?id=<?php echo $activity_id; ?>" method="POST" enctype="multipart/form-data" id="activity-form" class="nature-form">
                <div class="form-group">
                    <label for="titre">Titre de l'activité <span class="required">*</span></label>
                    <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($activity['titre']); ?>" required />
                </div>

                <div class="form-group">
                    <label for="description">Description <span class="required">*</span></label>
                    <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($activity['description']); ?></textarea>
                </div>

                <div class="form-columns">
                    <div class="form-column">
                        <div class="form-group">
                            <label>Prix <span class="required">*</span></label>
                            <div class="price-options">
                                <div class="radio-option">
                                    <input type="radio" id="gratuit" name="type_prix" value="gratuit" <?php echo ($activity['prix'] <= 0) ? 'checked' : ''; ?> />
                                    <label for="gratuit">Gratuit</label>
                                </div>

                                <div class="radio-option">
                                    <input type="radio" id="payant" name="type_prix" value="payant" <?php echo ($activity['prix'] > 0) ? 'checked' : ''; ?> />
                                    <label for="payant">Payant</label>
                                    <div id="prix-container" class="<?php echo ($activity['prix'] <= 0) ? 'hidden' : ''; ?>">
                                        <input type="number" id="prix" name="prix" step="0.01" min="0" value="<?php echo $activity['prix']; ?>" />
                                        <span class="currency">€</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="date_ou_periode">Date ou période</label>
                            <input type="text" id="date_ou_periode" name="date_ou_periode" 
                                value="<?php echo htmlspecialchars($activity['date_ou_periode']); ?>" required />
                            <p id="date-validation-message" class="validation-message"></p>
                            <p class="field-hint">
                                Formats acceptés: date simple (15/06/2025, 15 juin 2025) ou
                                période (01/06/2025 - 15/06/2025, Tous les lundis)
                            </p>
                        </div>
                    </div>

                    <div class="form-column">
                        <div class="form-group">
                            <label>Tags (sélectionnez au moins un)</label>
                            <div class="tags-container">
                                <div class="tag-option">
                                    <input type="checkbox" id="interieur" name="tags[]" value="interieur"
                                        <?php echo in_array('interieur', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="interieur">Intérieur</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="exterieur" name="tags[]" value="exterieur"
                                        <?php echo in_array('exterieur', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="exterieur">Extérieur</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="art" name="tags[]" value="art"
                                        <?php echo in_array('art', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="art">Art</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="cuisine" name="tags[]" value="cuisine"
                                        <?php echo in_array('cuisine', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="cuisine">Cuisine</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="sport" name="tags[]" value="sport"
                                        <?php echo in_array('sport', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="sport">Sport</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="bien_etre" name="tags[]" value="bien_etre"
                                        <?php echo in_array('bien_etre', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="bien_etre">Bien-être</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="creativite" name="tags[]" value="creativite"
                                        <?php echo in_array('creativite', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="creativite">Créativité</label>
                                </div>

                                <!-- Nouveaux tags -->
                                <div class="tag-option">
                                    <input type="checkbox" id="ecologie" name="tags[]" value="ecologie"
                                        <?php echo in_array('ecologie', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="ecologie">Écologie</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="randonnee" name="tags[]" value="randonnee"
                                        <?php echo in_array('randonnee', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="randonnee">Randonnée</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="jardinage" name="tags[]" value="jardinage"
                                        <?php echo in_array('jardinage', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="jardinage">Jardinage</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="meditation" name="tags[]" value="meditation"
                                        <?php echo in_array('meditation', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="meditation">Méditation</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="artisanat" name="tags[]" value="artisanat"
                                        <?php echo in_array('artisanat', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="artisanat">Artisanat</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="image">Image de l'activité</label>
                    <p class="image-hint">Format recommandé: 4:3 (sera utilisé comme vignette)</p>
                    
                    <?php if($activity['image_url']): ?>
                        <p>Image actuelle:</p>
                        <img src="<?php echo htmlspecialchars($activity['image_url']); ?>" alt="Image actuelle" class="current-image">
                    <?php endif; ?>
                    
                    <div class="image-upload-container">
                        <div class="upload-zone" id="upload-zone">
                            <i class="fa-solid fa-leaf"></i>
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <p>Glissez et déposez une nouvelle image ici<br />ou</p>
                            <button type="button" id="browse-button" class="button-secondary">
                                Parcourir
                            </button>
                            <input type="file" id="image-input" name="image" accept="image/*" style="display: none" />
                        </div>

                        <div id="preview-container" class="hidden">
                            <div class="preview-wrapper">
                                <img id="image-preview" src="#" alt="Aperçu de l'image" />
                            </div>
                            <div class="crop-controls">
                                <button type="button" id="crop-button" class="button-secondary">
                                    <i class="fa-solid fa-crop"></i> Recadrer l'image
                                </button>
                                <button type="button" id="change-image" class="button-outline">
                                    <i class="fa-solid fa-arrow-rotate-left"></i> Changer l'image
                                </button>
                            </div>
                        </div>

                        <!-- Conteneur pour l'affichage de l'image recadrée -->
                        <div id="cropped-container" class="hidden">
                            <div class="preview-wrapper">
                                <img id="cropped-preview" src="#" alt="Image recadrée" />
                            </div>
                            <button type="button" id="recrop-button" class="button-secondary">
                                <i class="fa-solid fa-crop"></i> Recadrer à nouveau
                            </button>
                        </div>

                        <!-- Champ caché pour stocker l'image recadrée -->
                        <input type="hidden" id="cropped-data" name="cropped_image" />
                    </div>
                </div>

                <div class="submit-container">
                    <button type="submit" class="submit-button">
                        <i class="fa-solid fa-save"></i> Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal pour le recadrage -->
    <div id="crop-modal" class="modal hidden">
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <h2>Recadrer l'image</h2>
            <p class="modal-subtitle">
                Ajustez le cadre pour obtenir une vignette optimale (format 4:3)
            </p>
            <div class="cropper-container">
                <img id="cropper-image" src="#" alt="Image à recadrer" />
            </div>
            <div class="modal-buttons">
                <button id="apply-crop" class="button-primary">
                    <i class="fa-solid fa-check"></i> Appliquer
                </button>
                <button id="cancel-crop" class="button-outline">
                    <i class="fa-solid fa-xmark"></i> Annuler
                </button>
            </div>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialiser le panier s'il n'existe pas déjà
            if (!localStorage.getItem('synapse-cart')) {
                localStorage.setItem('synapse-cart', JSON.stringify([]));
            }
            
            // Mettre à jour le compteur du panier
            updateCartCount();
            
            // Éléments du DOM
            const uploadZone = document.getElementById('upload-zone');
            const browseButton = document.getElementById('browse-button');
            const imageInput = document.getElementById('image-input');
            const previewContainer = document.getElementById('preview-container');
            const imagePreview = document.getElementById('image-preview');
            const cropButton = document.getElementById('crop-button');
            const changeImageButton = document.getElementById('change-image');
            const croppedContainer = document.getElementById('cropped-container');
            const croppedPreview = document.getElementById('cropped-preview');
            const croppedData = document.getElementById('cropped-data');
            const recropButton = document.getElementById('recrop-button');
            
            // Éléments du modal
            const cropModal = document.getElementById('crop-modal');
            const cropperImage = document.getElementById('cropper-image');
            const applyCropButton = document.getElementById('apply-crop');
            const cancelCropButton = document.getElementById('cancel-crop');
            const closeModal = document.querySelector('.close-modal');
            
            // Date input
            const dateOuPeriodeInput = document.getElementById('date_ou_periode');
            const dateValidationMessage = document.getElementById('date-validation-message');
            
            // Options de prix
            const gratuitRadio = document.getElementById('gratuit');
            const payantRadio = document.getElementById('payant');
            const prixContainer = document.getElementById('prix-container');
            
            let cropper; // Variable pour stocker l'instance de cropper
            
            // Gestion de l'upload par glisser-déposer
            uploadZone.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadZone.style.backgroundColor = 'rgba(130, 137, 119, 0.2)';
            });
            
            uploadZone.addEventListener('dragleave', function() {
                uploadZone.style.backgroundColor = '';
            });
            
            uploadZone.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadZone.style.backgroundColor = '';
                
                if (e.dataTransfer.files.length > 0) {
                    handleImageFile(e.dataTransfer.files[0]);
                }
            });
            
            // Bouton pour parcourir les fichiers
            browseButton.addEventListener('click', function() {
                imageInput.click();
            });
            
            // Sélection de fichier via l'input
            imageInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    handleImageFile(this.files[0]);
                }
            });
            
            // Traitement du fichier image
            function handleImageFile(file) {
                if (!file.type.match('image.*')) {
                    alert('Veuillez sélectionner une image valide.');
                    return;
                }
                
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    uploadZone.classList.add('hidden');
                    previewContainer.classList.remove('hidden');
                    croppedContainer.classList.add('hidden');
                    
                    // Ouvrir directement l'outil de recadrage dès que l'image est chargée
                    setTimeout(() => {
                        openCropTool();
                    }, 300);
                };
                
                reader.readAsDataURL(file);
            }
            
            // Fonction pour ouvrir l'outil de recadrage
            function openCropTool() {
                cropperImage.src = imagePreview.src;
                cropModal.classList.remove('hidden');
                
                // Initialiser Cropper.js
                if (cropper) {
                    cropper.destroy();
                }
                
                setTimeout(() => {
                    cropper = new Cropper(cropperImage, {
                        aspectRatio: 4 / 3,
                        viewMode: 1,
                        guides: true,
                        autoCropArea: 0.8,
                        background: true,
                        modal: true,
                        responsive: true,
                        zoomable: true
                    });
                }, 100);
            }
            
            // Bouton pour changer l'image
            changeImageButton.addEventListener('click', function() {
                imageInput.value = '';
                previewContainer.classList.add('hidden');
                croppedContainer.classList.add('hidden');
                uploadZone.classList.remove('hidden');
            });
            
            // Bouton pour recadrer l'image
            cropButton.addEventListener('click', openCropTool);
            
            // Fermer le modal
            function closeModalFunction() {
                cropModal.classList.add('hidden');
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            }
            
            closeModal.addEventListener('click', closeModalFunction);
            cancelCropButton.addEventListener('click', closeModalFunction);
            
            // Appliquer le recadrage
            applyCropButton.addEventListener('click', function() {
                const croppedCanvas = cropper.getCroppedCanvas({
                    width: 800,
                    height: 600
                });
                
                croppedPreview.src = croppedCanvas.toDataURL('image/jpeg');
                croppedData.value = croppedCanvas.toDataURL('image/jpeg');
                
                previewContainer.classList.add('hidden');
                croppedContainer.classList.remove('hidden');
                closeModalFunction();
            });
            
            // Recadrer à nouveau
            recropButton.addEventListener('click', function() {
                croppedContainer.classList.add('hidden');
                previewContainer.classList.remove('hidden');
                openCropTool();
            });
            
            // Gestion des options de prix
            gratuitRadio.addEventListener('change', function() {
                if (this.checked) {
                    prixContainer.classList.add('hidden');
                }
            });
            
            payantRadio.addEventListener('change', function() {
                if (this.checked) {
                    prixContainer.classList.remove('hidden');
                }
            });
            
            // Validation de la date
            dateOuPeriodeInput.addEventListener('input', validateDateFormat);
            dateOuPeriodeInput.addEventListener('blur', validateDateFormat);

            function validateDateFormat() {
                const value = dateOuPeriodeInput.value.trim();
                let isValid = false;
                let message = '';

                // Si le champ est vide
                if (!value) {
                    dateValidationMessage.textContent = 'Ce champ est requis';
                    dateValidationMessage.style.color = '#e74c3c';
                    dateOuPeriodeInput.style.borderColor = '#e74c3c';
                    return false;
                }

                // Vérifie format de date JJ/MM/AAAA
                const dateRegex = /^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[0-2])\/\d{4}$/;
                
                // Vérifie format de date avec texte (ex: 15 juin 2025)
                const monthNames = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
                const textDateRegex = new RegExp(`^(0?[1-9]|[12][0-9]|3[01])\\s+(${monthNames.join('|')})\\s+\\d{4}<?php
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

// Vérifier si un ID est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // Rediriger vers la page des activités si aucun ID valide n'est fourni
    header("Location: mes-activites.php");
    exit();
}

$activity_id = $_GET['id'];
$errors = array();
$success = '';

// Récupérer les détails de l'activité existante
function getActivityDetails($conn, $id) {
    $sql = "SELECT a.*, 
            (SELECT GROUP_CONCAT(nom_tag) FROM tags WHERE activite_id = a.id) AS tags
            FROM activites a 
            WHERE a.id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header("Location: mes-activites.php");
        exit();
    }
    
    $activity = $result->fetch_assoc();
    $stmt->close();
    
    return $activity;
}

// Récupérer les tags de l'activité
function getActivityTags($conn, $id) {
    $sql = "SELECT nom_tag FROM tags WHERE activite_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tags = array();
    while ($row = $result->fetch_assoc()) {
        $tags[] = $row['nom_tag'];
    }
    
    $stmt->close();
    return $tags;
}

// Récupérer les détails de l'activité
$activity = getActivityDetails($conn, $activity_id);
$selectedTags = getActivityTags($conn, $activity_id);

// Traitement du formulaire de mise à jour
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les données du formulaire
    $titre = $conn->real_escape_string($_POST['titre']);
    $description = $conn->real_escape_string($_POST['description']);
    $date_ou_periode = isset($_POST['date_ou_periode']) ? $conn->real_escape_string($_POST['date_ou_periode']) : '';
    
    // Gestion du prix
    $prix = 0;
    if (isset($_POST['type_prix']) && $_POST['type_prix'] == 'payant' && isset($_POST['prix'])) {
        $prix = floatval($_POST['prix']);
    }
    
    // Gestion de l'image
    $image_url = $activity['image_url']; // Garder l'image existante par défaut
    
    // Si une image recadrée a été fournie
    if (isset($_POST['cropped_image']) && !empty($_POST['cropped_image'])) {
        $cropped_image = $_POST['cropped_image'];
        
        // Extraire les données binaires de l'image base64
        $image_parts = explode(";base64,", $cropped_image);
        $image_base64 = isset($image_parts[1]) ? $image_parts[1] : $cropped_image;
        $image_data = base64_decode($image_base64);
        
        // Générer un nom de fichier unique
        $filename = 'activite_' . time() . '_' . uniqid() . '.jpg';
        $upload_path = 'uploads/images/' . $filename;
        
        // Créer le répertoire s'il n'existe pas
        if (!file_exists('uploads/images/')) {
            mkdir('uploads/images/', 0777, true);
        }
        
        // Sauvegarder l'image
        if (file_put_contents($upload_path, $image_data)) {
            $image_url = $upload_path;
        }
    } 
    // Si un fichier a été uploadé directement sans recadrage
    elseif (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['image']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $new_filename = 'activite_' . time() . '_' . uniqid() . '.' . $filetype;
            $upload_path = 'uploads/images/' . $new_filename;
            
            if (!file_exists('uploads/images/')) {
                mkdir('uploads/images/', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image_url = $upload_path;
            }
        }
    }
    
    // Validation
    if (empty($titre)) {
        $errors[] = "Le titre est requis.";
    }
    
    if (empty($description)) {
        $errors[] = "La description est requise.";
    }
    
    if (empty($date_ou_periode)) {
        $errors[] = "La date ou période est requise.";
    }
    
    // Si tout est valide, mettre à jour la base de données
    if (empty($errors)) {
        // Mettre à jour l'activité
        $sql = "UPDATE activites SET titre = ?, description = ?, image_url = ?, prix = ?, date_ou_periode = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssdsi", $titre, $description, $image_url, $prix, $date_ou_periode, $activity_id);
        
        if ($stmt->execute()) {
            // Supprimer les anciens tags
            $sql_delete_tags = "DELETE FROM tags WHERE activite_id = ?";
            $stmt_delete = $conn->prepare($sql_delete_tags);
            $stmt_delete->bind_param("i", $activity_id);
            $stmt_delete->execute();
            $stmt_delete->close();
            
            // Ajouter les nouveaux tags
            if (isset($_POST['tags']) && is_array($_POST['tags'])) {
                foreach ($_POST['tags'] as $tag) {
                    $tag = $conn->real_escape_string($tag);
                    $sql_tag = "INSERT INTO tags (activite_id, nom_tag) VALUES (?, ?)";
                    $stmt_tag = $conn->prepare($sql_tag);
                    $stmt_tag->bind_param("is", $activity_id, $tag);
                    $stmt_tag->execute();
                    $stmt_tag->close();
                }
            }
            
            $success = "L'activité a été mise à jour avec succès.";
            
            // Mettre à jour les données de l'activité après mise à jour
            $activity = getActivityDetails($conn, $activity_id);
            $selectedTags = getActivityTags($conn, $activity_id);
        } else {
            $errors[] = "Erreur lors de la mise à jour: " . $conn->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Modifier l'activité | Synapse</title>
    <link rel="stylesheet" href="stylejenis.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css" />
    <style>
        .form-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .back-link {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #828977;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        
        .back-link:hover {
            transform: translateX(-3px);
        }
        
        .success-message {
            background-color: #45cf91;
            color: #111;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .error-message {
            background-color: #e74c3c;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .current-image {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="./main.php">
            <img class="logo" src="../Connexion-Inscription/logo-transparent-pdf.png" alt="Logo Synapse"/>
        </a>
        <nav class="nav-links">
            <ul>
                <li><a href="#">Devenez Prestataire</a></li>
                <li><a href="../Concept/concept.html">Concept</a></li>
            </ul>
        </nav>

        <div class="icon">
            <i class="fa-regular fa-heart" aria-label="Favoris"></i>
            <a href="panier.html" class="panier-link" aria-label="Panier">
                <i class="fa-solid fa-cart-shopping"></i>
                <span class="panier-count" id="panier-count">0</span>
            </a>
            <div class="profile-dropdown">
                <a href="#" class="connexion-profil" aria-label="Profil">
                    <i class="fa-solid fa-user"></i>
                </a>
                <div class="dropdown-content">
                    <a href="../Connexion-Inscription/Connexion.html"><i class="fa-solid fa-right-to-bracket"></i> Connexion</a>
                    <a href="mes-activites.php"><i class="fa-solid fa-calendar-days"></i> Mes activités</a>
                    <a href="#"><i class="fa-solid fa-gear"></i> Paramètres</a>
                </div>
            </div>
        </div>
    </header>

    <div class="page-wrapper">
        <div class="form-container">
            <div class="leaf-decoration leaf-top-left"></div>
            <div class="leaf-decoration leaf-bottom-right"></div>

            <div class="form-header">
                <a href="mes-activites.php" class="back-link">
                    <i class="fa-solid fa-arrow-left"></i> Retour à mes activités
                </a>
                <h1>Modifier l'activité</h1>
            </div>

            <?php if (!empty($success)): ?>
                <div class="success-message">
                    <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form action="modifier-activite.php?id=<?php echo $activity_id; ?>" method="POST" enctype="multipart/form-data" id="activity-form" class="nature-form">
                <div class="form-group">
                    <label for="titre">Titre de l'activité <span class="required">*</span></label>
                    <input type="text" id="titre" name="titre" value="<?php echo htmlspecialchars($activity['titre']); ?>" required />
                </div>

                <div class="form-group">
                    <label for="description">Description <span class="required">*</span></label>
                    <textarea id="description" name="description" rows="5" required><?php echo htmlspecialchars($activity['description']); ?></textarea>
                </div>

                <div class="form-columns">
                    <div class="form-column">
                        <div class="form-group">
                            <label>Prix <span class="required">*</span></label>
                            <div class="price-options">
                                <div class="radio-option">
                                    <input type="radio" id="gratuit" name="type_prix" value="gratuit" <?php echo ($activity['prix'] <= 0) ? 'checked' : ''; ?> />
                                    <label for="gratuit">Gratuit</label>
                                </div>

                                <div class="radio-option">
                                    <input type="radio" id="payant" name="type_prix" value="payant" <?php echo ($activity['prix'] > 0) ? 'checked' : ''; ?> />
                                    <label for="payant">Payant</label>
                                    <div id="prix-container" class="<?php echo ($activity['prix'] <= 0) ? 'hidden' : ''; ?>">
                                        <input type="number" id="prix" name="prix" step="0.01" min="0" value="<?php echo $activity['prix']; ?>" />
                                        <span class="currency">€</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="date_ou_periode">Date ou période</label>
                            <input type="text" id="date_ou_periode" name="date_ou_periode" 
                                value="<?php echo htmlspecialchars($activity['date_ou_periode']); ?>" required />
                            <p id="date-validation-message" class="validation-message"></p>
                            <p class="field-hint">
                                Formats acceptés: date simple (15/06/2025, 15 juin 2025) ou
                                période (01/06/2025 - 15/06/2025, Tous les lundis)
                            </p>
                        </div>
                    </div>

                    <div class="form-column">
                        <div class="form-group">
                            <label>Tags (sélectionnez au moins un)</label>
                            <div class="tags-container">
                                <div class="tag-option">
                                    <input type="checkbox" id="interieur" name="tags[]" value="interieur"
                                        <?php echo in_array('interieur', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="interieur">Intérieur</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="exterieur" name="tags[]" value="exterieur"
                                        <?php echo in_array('exterieur', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="exterieur">Extérieur</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="art" name="tags[]" value="art"
                                        <?php echo in_array('art', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="art">Art</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="cuisine" name="tags[]" value="cuisine"
                                        <?php echo in_array('cuisine', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="cuisine">Cuisine</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="sport" name="tags[]" value="sport"
                                        <?php echo in_array('sport', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="sport">Sport</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="bien_etre" name="tags[]" value="bien_etre"
                                        <?php echo in_array('bien_etre', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="bien_etre">Bien-être</label>
                                </div>

                                <div class="tag-option">
                                    <input type="checkbox" id="creativite" name="tags[]" value="creativite"
                                        <?php echo in_array('creativite', $selectedTags) ? 'checked' : ''; ?> />
                                    <label for="creativite">Créativité</label>
                                </div>

                                <!-- Nouveaux tags -->
, 'i');
                
                // Vérifie format de période (ex: 01/06/2025 - 15/06/2025)
                const periodRegex = /^(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[0-2])\/\d{4}\s*-\s*(0?[1-9]|[12][0-9]|3[01])\/(0?[1-9]|1[0-2])\/\d{4}$/;
                
                // Vérifie "Tous les..." (ex: Tous les lundis)
                const recurringRegex = /^tous les (lundi|mardi|mercredi|jeudi|vendredi|samedi|dimanche)s?$/i;
                
                // Vérifie la validité
                if (dateRegex.test(value)) {
                    // Validation supplémentaire pour la date JJ/MM/AAAA
                    const [day, month, year] = value.split('/').map(Number);
                    isValid = validateDateValues(day, month, year);
                    
                    if (!isValid) {
                        message = 'Date invalide. Veuillez vérifier jour/mois/année';
                    }
                } else if (textDateRegex.test(value)) {
                    // Date avec texte (ex: 15 juin 2025)
                    const parts = value.split(' ');
                    const day = parseInt(parts[0], 10);
                    const monthIndex = monthNames.findIndex(m => 
                        m.toLowerCase() === parts[1].toLowerCase());
                    const year = parseInt(parts[2], 10);
                    
                    isValid = validateDateValues(day, monthIndex + 1, year);
                    
                    if (!isValid) {
                        message = 'Date invalide. Veuillez vérifier jour/mois/année';
                    }
                } else if (periodRegex.test(value)) {
                    // Période (ex: 01/06/2025 - 15/06/2025)
                    const dates = value.split('-').map(d => d.trim());
                    const [startDay, startMonth, startYear] = dates[0].split('/').map(Number);
                    const [endDay, endMonth, endYear] = dates[1].split('/').map(Number);
                    
                    const isStartValid = validateDateValues(startDay, startMonth, startYear);
                    const isEndValid = validateDateValues(endDay, endMonth, endYear);
                    
                    isValid = isStartValid && isEndValid;
                    
                    if (!isValid) {
                        message = 'Période invalide. Veuillez vérifier les dates';
                    } else {
                        // Vérifier que la date de fin est après la date de début
                        const startDate = new Date(startYear, startMonth - 1, startDay);
                        const endDate = new Date(endYear, endMonth - 1, endDay);
                        
                        if (endDate <= startDate) {
                            isValid = false;
                            message = 'La date de fin doit être après la date de début';
                        }
                    }
                } else if (recurringRegex.test(value)) {
                    // Format récurrent valide (ex: Tous les lundis)
                    isValid = true;
                } else {
                    message = 'Format non reconnu. Utilisez DD/MM/YYYY, "JJ mois AAAA", "DD/MM/YYYY - DD/MM/YYYY" ou "Tous les..."';
                }

                // Afficher le résultat de validation
                if (isValid) {
                    dateValidationMessage.textContent = '✓ Format valide';
                    dateValidationMessage.style.color = '#2ecc71';
                    dateOuPeriodeInput.style.borderColor = '#2ecc71';
                } else {
                    dateValidationMessage.textContent = message;
                    dateValidationMessage.style.color = '#e74c3c';
                    dateOuPeriodeInput.style.borderColor = '#e74c3c';
                }

                return isValid;
            }

            // Fonction pour vérifier si une date est valide
            function validateDateValues(day, month, year) {
                const date = new Date(year, month - 1, day);
                return date.getFullYear() === year &&
                      date.getMonth() === month - 1 &&
                      date.getDate() === day &&
                      year >= new Date().getFullYear(); // Date dans le futur
            }
            
            // Valider le format de la date au chargement de la page
            validateDateFormat();
            
            // Validation du formulaire
            document.getElementById('activity-form').addEventListener('submit', function(e) {
                const titre = document.getElementById('titre').value.trim();
                const description = document.getElementById('description').value.trim();
                const dateValue = dateOuPeriodeInput.value.trim();
                
                let isValid = true;
                let errorMessage = '';
                
                if (!titre) {
                    isValid = false;
                    errorMessage += 'Le titre est requis.\n';
                }
                
                if (!description) {
                    isValid = false;
                    errorMessage += 'La description est requise.\n';
                }
                
                if (payantRadio.checked && (!document.getElementById('prix').value || document.getElementById('prix').value <= 0)) {
                    isValid = false;
                    errorMessage += 'Veuillez entrer un prix valide.\n';
                }
                
                // Validation de la date avant soumission
                if (!dateValue) {
                    isValid = false;
                    errorMessage += 'La date ou période est requise.\n';
                } else {
                    // Valider le format de la date
                    const dateIsValid = validateDateFormat();
                    if (!dateIsValid) {
                        isValid = false;
                        errorMessage += 'Le format de date ou période est invalide.\n';
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Erreur de validation:\n' + errorMessage);
                }
            });
            
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
$conn->close();
?>