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
        };
        
        reader.readAsDataURL(file);
    }
    
    // Bouton pour changer l'image
    changeImageButton.addEventListener('click', function() {
        imageInput.value = '';
        previewContainer.classList.add('hidden');
        croppedContainer.classList.add('hidden');
        uploadZone.classList.remove('hidden');
    });
    
    // Bouton pour recadrer l'image
    cropButton.addEventListener('click', function() {
        cropperImage.src = imagePreview.src;
        cropModal.classList.remove('hidden');
        
        // Initialiser Cropper.js
        if (cropper) {
            cropper.destroy();
        }
        
        setTimeout(() => {
            cropper = new Cropper(cropperImage, {
                aspectRatio: 16 / 9, // Ratio pour les thumbnails
                viewMode: 1,
                guides: true,
                autoCropArea: 0.8
            });
        }, 100);
    });
    
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
            height: 450
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
        cropButton.click();
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
    
    // Validation du formulaire
    document.getElementById('activity-form').addEventListener('submit', function(e) {
        const titre = document.getElementById('titre').value.trim();
        const description = document.getElementById('description').value.trim();
        const imageUploaded = croppedData.value || imagePreview.src !== '#';
        
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
        
        if (!isValid) {
            e.preventDefault();
            alert('Erreur de validation:\n' + errorMessage);
        }
        
    });
});