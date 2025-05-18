<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Rediriger vers la page de connexion
    header('Location: ../Connexion-Inscription/login_form.php');
    exit();
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

    <div class="page-wrapper">
      <div class="form-container">
        <div class="leaf-decoration leaf-top-left"></div>
        <div class="leaf-decoration leaf-bottom-right"></div>

        <h1>Créer une nouvelle activité</h1>
        <p class="subtitle">Partagez votre passion avec la communauté</p>

        <form
          action="fentjenis.php"
          method="POST"
          enctype="multipart/form-data"
          id="activity-form"
          class="nature-form"
        >
          <div class="form-group">
            <label for="titre"
              >Titre de l'activité <span class="required">*</span></label
            >
            <input
              type="text"
              id="titre"
              name="titre"
              placeholder="Ex: Atelier de peinture à l'huile"
              required
            />
          </div>

          <div class="form-group">
            <label for="description"
              >Description <span class="required">*</span></label
            >
            <textarea
              id="description"
              name="description"
              rows="5"
              placeholder="Décrivez votre activité en détail..."
              required
            ></textarea>
          </div>

          <div class="form-columns">
            <div class="form-column">
              <div class="form-group">
                <label>Prix <span class="required">*</span></label>
                <div class="price-options">
                  <div class="radio-option">
                    <input
                      type="radio"
                      id="gratuit"
                      name="type_prix"
                      value="gratuit"
                      checked
                    />
                    <label for="gratuit">Gratuit</label>
                  </div>

                  <div class="radio-option">
                    <input
                      type="radio"
                      id="payant"
                      name="type_prix"
                      value="payant"
                    />
                    <label for="payant">Payant</label>
                    <div id="prix-container" class="hidden">
                      <input
                        type="number"
                        id="prix"
                        name="prix"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                      />
                      <span class="currency">€</span>
                    </div>
                  </div>
                </div>
              </div>

              <div class="form-group">
                <label for="date_ou_periode">Date ou période</label>
                <input
                  type="text"
                  id="date_ou_periode"
                  name="date_ou_periode"
                  placeholder="Ex: Tous les samedis / 15 juin 2025 / etc."
                  required
                />
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
                    <input
                      type="checkbox"
                      id="interieur"
                      name="tags[]"
                      value="interieur"
                    />
                    <label for="interieur">Intérieur</label>
                  </div>

                  <div class="tag-option">
                    <input
                      type="checkbox"
                      id="exterieur"
                      name="tags[]"
                      value="exterieur"
                    />
                    <label for="exterieur">Extérieur</label>
                  </div>

                  <div class="tag-option">
                    <input type="checkbox" id="art" name="tags[]" value="art" />
                    <label for="art">Art</label>
                  </div>

                  <div class="tag-option">
                    <input
                      type="checkbox"
                      id="cuisine"
                      name="tags[]"
                      value="cuisine"
                    />
                    <label for="cuisine">Cuisine</label>
                  </div>

                  <div class="tag-option">
                    <input
                      type="checkbox"
                      id="sport"
                      name="tags[]"
                      value="sport"
                    />
                    <label for="sport">Sport</label>
                  </div>

                  <div class="tag-option">
                    <input
                      type="checkbox"
                      id="bien_etre"
                      name="tags[]"
                      value="bien_etre"
                    />
                    <label for="bien_etre">Bien-être</label>
                  </div>

                  <div class="tag-option">
                    <input
                      type="checkbox"
                      id="creativite"
                      name="tags[]"
                      value="creativite"
                    />
                    <label for="creativite">Créativité</label>
                  </div>

                  <!-- Nouveaux tags -->
                  <div class="tag-option">
                    <input
                      type="checkbox"
                      id="ecologie"
                      name="tags[]"
                      value="ecologie"
                    />
                    <label for="ecologie">Écologie</label>
                  </div>

                  <div class="tag-option">
                    <input
                      type="checkbox"
                      id="randonnee"
                      name="tags[]"
                      value="randonnee"
                    />
                    <label for="randonnee">Randonnée</label>
                  </div>

                  <div class="tag-option">
                    <input
                      type="checkbox"
                      id="jardinage"
                      name="tags[]"
                      value="jardinage"
                    />
                    <label for="jardinage">Jardinage</label>
                  </div>

                  <div class="tag-option">
                    <input
                      type="checkbox"
                      id="meditation"
                      name="tags[]"
                      value="meditation"
                    />
                    <label for="meditation">Méditation</label>
                  </div>

                  <div class="tag-option">
                    <input
                      type="checkbox"
                      id="artisanat"
                      name="tags[]"
                      value="artisanat"
                    />
                    <label for="artisanat">Artisanat</label>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="form-group">
            <label for="image"
              >Image de l'activité <span class="required">*</span></label
            >
            <p class="image-hint">
              Format recommandé: 4:3 (sera utilisé comme vignette)
            </p>
            <div class="image-upload-container">
              <div class="upload-zone" id="upload-zone">
                <i class="fa-solid fa-leaf"></i>
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <p>Glissez et déposez une image ici<br />ou</p>
                <button
                  type="button"
                  id="browse-button"
                  class="button-secondary"
                >
                  Parcourir
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
                <div class="preview-wrapper">
                  <img id="image-preview" src="#" alt="Aperçu de l'image" />
                </div>
                <div class="crop-controls">
                  <button
                    type="button"
                    id="crop-button"
                    class="button-secondary"
                  >
                    <i class="fa-solid fa-crop"></i> Recadrer l'image
                  </button>
                  <button
                    type="button"
                    id="change-image"
                    class="button-outline"
                  >
                    <i class="fa-solid fa-arrow-rotate-left"></i> Changer
                    l'image
                  </button>
                </div>
              </div>

              <!-- Conteneur pour l'affichage de l'image recadrée -->
              <div id="cropped-container" class="hidden">
                <div class="preview-wrapper">
                  <img id="cropped-preview" src="#" alt="Image recadrée" />
                </div>
                <button
                  type="button"
                  id="recrop-button"
                  class="button-secondary"
                >
                  <i class="fa-solid fa-crop"></i> Recadrer à nouveau
                </button>
              </div>

              <!-- Champ caché pour stocker l'image recadrée -->
              <input type="hidden" id="cropped-data" name="cropped_image" />
            </div>
          </div>

          <div class="submit-container">
            <button type="submit" class="submit-button">
              <i class="fa-solid fa-plus"></i> Créer l'activité
            </button>
          </div>
        </form>
      </div>
    </div>

    <?php
    // Inclure le footer
    include '../TEMPLATE/footer.php';
    ?>

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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js"></script>
    <script src="brainjenis.js"></script>
  </body>
</html>