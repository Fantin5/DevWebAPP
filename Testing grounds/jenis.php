<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion
    header('Location: ../Connexion-Inscription/login_form.php');
    exit();
}

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

// Récupérer les tags depuis la base de données
$sql = "SELECT * FROM tag_definitions WHERE name NOT IN ('gratuit', 'payant') ORDER BY display_name ASC";
$result = $conn->query($sql);
$tags = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $tags[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Créer une Activité | Synapse</title>
    <link rel="stylesheet" href="stylejenis.css" />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css"
    />
  </head>
  <body>
    <?php
    // Inclure le header
    include '../TEMPLATE/Nouveauhead.php';
    ?>

    <!-- Nature Background Elements -->
    <div class="nature-bg">
      <div class="bg-leaf bg-leaf-1"></div>
      <div class="bg-leaf bg-leaf-2"></div>
      <div class="bg-leaf bg-leaf-3"></div>
      <div class="bg-wave"></div>
    </div>

    <div class="page-wrapper">
      <!-- Floating Nature Elements -->
      <div class="floating-elements">
        <div class="floating-leaf leaf-1">🍃</div>
        <div class="floating-leaf leaf-2">🌿</div>
        <div class="floating-leaf leaf-3">🍀</div>
        <div class="floating-particle particle-1"></div>
        <div class="floating-particle particle-2"></div>
        <div class="floating-particle particle-3"></div>
      </div>

      <div class="form-container">
        <!-- Form Header with Nature Theme -->
        <div class="form-header">
          <div class="header-icon">
            <i class="fas fa-seedling"></i>
          </div>
          <h1>Faire Pousser une Nouvelle Activité</h1>
          <p class="subtitle">Plantez votre idée et regardez-la s'épanouir dans notre communauté 🌱</p>
        </div>

        <?php
        // Messages d'erreur avec style amélioré
        if (isset($_GET['error'])) {
            $error_messages = [
                'image_required' => 'Une image est requise pour faire pousser votre activité 📸',
                'creation_failed' => 'Oups ! Quelque chose n\'a pas poussé comme prévu. Réessayez 🌱'
            ];
            $message = $error_messages[$_GET['error']] ?? 'Une erreur s\'est produite';
            echo '<div class="error-message"><i class="fas fa-exclamation-triangle"></i>' . $message . '</div>';
        }
        ?>

        <form
          action="fentjenis.php"
          method="POST"
          enctype="multipart/form-data"
          id="activity-form"
          class="nature-form"
        >
          <!-- Section: Basic Info -->
          <div class="form-section">
            <div class="section-header">
              <i class="fas fa-leaf section-icon"></i>
              <h3>Les Racines de votre Activité</h3>
            </div>
            
            <div class="form-group">
              <label for="titre">
                <i class="fas fa-tag"></i>
                Nom de votre activité <span class="required">*</span>
              </label>
              <input
                type="text"
                id="titre"
                name="titre"
                placeholder="Ex: Atelier jardinage urbain sous les étoiles"
                required
              />
            </div>

            <div class="form-group">
              <label for="description">
                <i class="fas fa-feather-alt"></i>
                Racontez votre histoire <span class="required">*</span>
              </label>
              <textarea
                id="description"
                name="description"
                rows="5"
                placeholder="Décrivez l'expérience que vous voulez partager... Qu'est-ce qui rend cette activité spéciale ?"
                required
              ></textarea>
            </div>

            <div class="form-group">
              <label for="date_ou_periode">
                <i class="fas fa-calendar-alt"></i>
                Quand cette magie opère-t-elle ? <span class="required">*</span>
              </label>
              <input
                type="text"
                id="date_ou_periode"
                name="date_ou_periode"
                placeholder="Ex: Tous les samedis au coucher du soleil / 15 juin 2025"
                required
              />
              <div class="field-hint" id="date-hint">
                <i class="fas fa-lightbulb"></i>
                <span id="hint-text">Formats acceptés: date précise (15/06/2025), récurrence (Tous les samedis), ou période (01/06/2025 - 15/06/2025)</span>
              </div>
            </div>
          </div>

          <!-- Section: Tags (The Cool Part!) -->
          <div class="form-section tags-section">
            <div class="section-header">
              <i class="fas fa-tags section-icon"></i>
              <h3>Dans quel Écosystème ?</h3>
              <p class="section-subtitle">Aidez les autres à découvrir votre activité</p>
            </div>
            
            <div class="tags-grid">
              <?php 
              // Dynamic tag icons - works with any tag!
              function getTagIcon($tagName) {
                $tag_icons = [
                  'interieur' => 'fa-home',
                  'exterieur' => 'fa-tree', 
                  'art' => 'fa-palette',
                  'cuisine' => 'fa-utensils',
                  'sport' => 'fa-running',
                  'bien_etre' => 'fa-spa',
                  'creativite' => 'fa-lightbulb',
                  'ecologie' => 'fa-leaf',
                  'randonnee' => 'fa-hiking',
                  'jardinage' => 'fa-seedling',
                  'meditation' => 'fa-om',
                  'artisanat' => 'fa-hammer',
                  'compétition' => 'fa-trophy'
                ];
                
                // If we have a specific icon, use it
                if (isset($tag_icons[$tagName])) {
                  return $tag_icons[$tagName];
                }
                
                // Smart fallback based on keywords in tag name
                $name = strtolower($tagName);
                
                if (strpos($name, 'sport') !== false || strpos($name, 'course') !== false || strpos($name, 'fitness') !== false) return 'fa-running';
                if (strpos($name, 'art') !== false || strpos($name, 'peinture') !== false || strpos($name, 'dessin') !== false) return 'fa-palette';
                if (strpos($name, 'cuisine') !== false || strpos($name, 'culinaire') !== false || strpos($name, 'recette') !== false) return 'fa-utensils';
                if (strpos($name, 'nature') !== false || strpos($name, 'plante') !== false || strpos($name, 'jardin') !== false) return 'fa-seedling';
                if (strpos($name, 'maison') !== false || strpos($name, 'intérieur') !== false || strpos($name, 'indoor') !== false) return 'fa-home';
                if (strpos($name, 'extérieur') !== false || strpos($name, 'outdoor') !== false || strpos($name, 'plein') !== false) return 'fa-tree';
                if (strpos($name, 'bien') !== false || strpos($name, 'relax') !== false || strpos($name, 'zen') !== false) return 'fa-spa';
                if (strpos($name, 'créa') !== false || strpos($name, 'innovation') !== false || strpos($name, 'idée') !== false) return 'fa-lightbulb';
                if (strpos($name, 'marche') !== false || strpos($name, 'randonnée') !== false || strpos($name, 'trek') !== false) return 'fa-hiking';
                if (strpos($name, 'méditation') !== false || strpos($name, 'yoga') !== false || strpos($name, 'spirituel') !== false) return 'fa-om';
                if (strpos($name, 'craft') !== false || strpos($name, 'manuel') !== false || strpos($name, 'fabrication') !== false) return 'fa-hammer';
                if (strpos($name, 'compétition') !== false || strpos($name, 'concours') !== false || strpos($name, 'tournoi') !== false) return 'fa-trophy';
                if (strpos($name, 'musique') !== false || strpos($name, 'son') !== false || strpos($name, 'audio') !== false) return 'fa-music';
                if (strpos($name, 'photo') !== false || strpos($name, 'image') !== false || strpos($name, 'vidéo') !== false) return 'fa-camera';
                if (strpos($name, 'lecture') !== false || strpos($name, 'livre') !== false || strpos($name, 'écriture') !== false) return 'fa-book';
                if (strpos($name, 'jeu') !== false || strpos($name, 'game') !== false || strpos($name, 'ludique') !== false) return 'fa-gamepad';
                if (strpos($name, 'tech') !== false || strpos($name, 'digital') !== false || strpos($name, 'informatique') !== false) return 'fa-laptop';
                if (strpos($name, 'enfant') !== false || strpos($name, 'famille') !== false || strpos($name, 'kids') !== false) return 'fa-child';
                if (strpos($name, 'eau') !== false || strpos($name, 'natation') !== false || strpos($name, 'aqua') !== false) return 'fa-swimmer';
                if (strpos($name, 'formation') !== false || strpos($name, 'éducation') !== false || strpos($name, 'apprendre') !== false) return 'fa-graduation-cap';
                
                // Final fallback: beautiful default icons based on first letter or random
                $defaultIcons = ['fa-star', 'fa-heart', 'fa-gem', 'fa-fire', 'fa-magic', 'fa-bolt', 'fa-crown', 'fa-diamond'];
                $charCode = ord(strtoupper($name[0]));
                return $defaultIcons[$charCode % count($defaultIcons)];
              }
              
              $colors = ['green', 'blue', 'orange', 'purple', 'teal', 'pink'];
              $color_index = 0;
              
              foreach ($tags as $tag): 
                $icon = getTagIcon($tag['name']);
                $color = $colors[$color_index % count($colors)];
                $color_index++;
              ?>
              <div class="tag-card <?php echo $color; ?>" data-tag="<?php echo htmlspecialchars($tag['name']); ?>">
                <input
                  type="checkbox"
                  id="<?php echo htmlspecialchars($tag['name']); ?>"
                  name="tags[]"
                  value="<?php echo htmlspecialchars($tag['name']); ?>"
                />
                <label for="<?php echo htmlspecialchars($tag['name']); ?>">
                  <div class="tag-icon">
                    <i class="fas <?php echo $icon; ?>"></i>
                  </div>
                  <div class="tag-name"><?php echo htmlspecialchars($tag['display_name']); ?></div>
                  <div class="tag-hover-effect"></div>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="tags-hint">
              <i class="fas fa-info-circle"></i>
              Sélectionnez au moins un tag pour que votre activité trouve son public
            </div>
          </div>

          <!-- Section: Price & Image -->
          <div class="form-columns">
            <div class="form-column">
              <div class="form-section">
                <div class="section-header">
                  <i class="fas fa-coins section-icon"></i>
                  <h3>Partage</h3>
                </div>
                
                <div class="price-toggle">
                  <input type="radio" id="gratuit" name="type_prix" value="gratuit" checked />
                  <input type="radio" id="payant" name="type_prix" value="payant" />
                  
                  <div class="toggle-container">
                    <label for="gratuit" class="toggle-option">
                      <i class="fas fa-gift"></i>
                      <span>Gratuit</span>
                      <small>Pour le plaisir de partager</small>
                    </label>
                    
                    <label for="payant" class="toggle-option">
                      <i class="fas fa-euro-sign"></i>
                      <span>Payant</span>
                      <small>Contribution demandée</small>
                    </label>
                  </div>
                  
                  <div id="prix-container" class="price-input-container hidden">
                    <input
                      type="number"
                      id="prix"
                      name="prix"
                      step="0.01"
                      min="0"
                      placeholder="Prix en €"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div class="form-column">
              <div class="form-section">
                <div class="section-header">
                  <i class="fas fa-camera section-icon"></i>
                  <h3>Une Image qui Inspire</h3>
                </div>
                
                <div class="image-upload-area">
                  <div class="upload-zone" id="upload-zone">
                    <div class="upload-icon">
                      <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <h4>Glissez votre image ici</h4>
                    <p>ou cliquez pour parcourir</p>
                    <button type="button" id="browse-button" class="btn-browse">
                      <i class="fas fa-folder-open"></i> Parcourir
                    </button>
                    <input
                      type="file"
                      id="image-input"
                      name="image"
                      accept="image/*"
                      style="display: none"
                    />
                  </div>

                  <div id="preview-container" class="hidden">
                    <div class="image-preview">
                      <img id="image-preview" src="#" alt="Aperçu" />
                    </div>
                    <div class="image-controls">
                      <button type="button" id="crop-button" class="btn-secondary">
                        <i class="fas fa-crop-alt"></i> Recadrer
                      </button>
                      <button type="button" id="change-image" class="btn-outline">
                        <i class="fas fa-redo"></i> Changer
                      </button>
                    </div>
                  </div>

                  <div id="cropped-container" class="hidden">
                    <div class="image-preview">
                      <img id="cropped-preview" src="#" alt="Image finale" />
                    </div>
                    <button type="button" id="recrop-button" class="btn-secondary">
                      <i class="fas fa-crop-alt"></i> Recadrer à nouveau
                    </button>
                  </div>

                  <input type="hidden" id="cropped-data" name="cropped_image" />
                </div>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="submit-section">
            <button type="submit" class="btn-submit">
              <div class="btn-content">
                <i class="fas fa-seedling"></i>
                <span>Faire Pousser l'Activité</span>
              </div>
              <div class="btn-shine"></div>
            </button>
          </div>
        </form>
      </div>
    </div>

    <?php include '../TEMPLATE/footer.php'; ?>

    <!-- Crop Modal -->
    <div id="crop-modal" class="modal hidden">
      <div class="modal-content">
        <div class="modal-header">
          <h2><i class="fas fa-crop-alt"></i> Recadrer votre image</h2>
          <span class="close-modal">&times;</span>
        </div>
        <div class="cropper-container">
          <img id="cropper-image" src="#" alt="Image à recadrer" />
        </div>
        <div class="modal-actions">
          <button id="apply-crop" class="btn-primary">
            <i class="fas fa-check"></i> Appliquer
          </button>
          <button id="cancel-crop" class="btn-outline">
            <i class="fas fa-times"></i> Annuler
          </button>
        </div>
      </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script src="brainjenis.js"></script>
    
    <?php $conn->close(); ?>
  </body>
</html>
<!-- cvq -->