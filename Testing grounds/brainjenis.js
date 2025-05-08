document.addEventListener('DOMContentLoaded', function() {
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
                aspectRatio: 4 / 3, // Ratio 4:3 au lieu de 16:9
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
            height: 600 // Changé pour correspondre au ratio 4:3
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
        const textDateRegex = new RegExp(`^(0?[1-9]|[12][0-9]|3[01])\\s+(${monthNames.join('|')})\\s+\\d{4}$`, 'i');
        
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
    
    // Validation du formulaire
    document.getElementById('activity-form').addEventListener('submit', function(e) {
        const titre = document.getElementById('titre').value.trim();
        const description = document.getElementById('description').value.trim();
        const imageUploaded = croppedData.value || imagePreview.src !== '#';
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
        
        if (!imageUploaded) {
            isValid = false;
            errorMessage += 'Une image est requise.\n';
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
});