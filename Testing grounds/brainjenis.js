document.addEventListener('DOMContentLoaded', function() {
    // Initialize all features
    initializePriceToggle();
    initializeImageUpload();
    initializeDynamicDateHints();
    initializeFormValidation();
    initializeTagAnimations();

    // Enhanced Price Toggle with Icon Color Change
    function initializePriceToggle() {
        const gratuitRadio = document.getElementById('gratuit');
        const payantRadio = document.getElementById('payant');
        const prixContainer = document.getElementById('prix-container');
        const prixInput = document.getElementById('prix');

        function togglePriceContainer() {
            if (payantRadio.checked) {
                prixContainer.classList.remove('hidden');
                prixContainer.style.animation = 'slideDown 0.3s ease-out';
                
                // Focus on price input after animation
                setTimeout(() => {
                    prixInput.focus();
                }, 100);
            } else {
                prixContainer.classList.add('hidden');
                prixInput.value = '';
            }
        }

        gratuitRadio.addEventListener('change', togglePriceContainer);
        payantRadio.addEventListener('change', togglePriceContainer);

        // Add visual feedback for price input
        prixInput.addEventListener('input', function() {
            if (this.value && parseFloat(this.value) > 0) {
                this.style.borderColor = 'var(--sage-green)';
                this.style.boxShadow = '0 0 0 4px rgba(135, 169, 107, 0.2)';
            } else {
                this.style.borderColor = 'rgba(135, 169, 107, 0.3)';
                this.style.boxShadow = 'none';
            }
        });
    }

    // Smart Dynamic Date Hints System
    function initializeDynamicDateHints() {
        const dateInput = document.getElementById('date_ou_periode');
        const hintElement = document.getElementById('date-hint');
        const hintText = document.getElementById('hint-text');
        const hintIcon = hintElement.querySelector('i');

        let hintTimeout;

        dateInput.addEventListener('input', function() {
            // Clear previous timeout
            clearTimeout(hintTimeout);
            
            // Wait for user to stop typing before updating hint
            hintTimeout = setTimeout(() => {
                updateDateHint(this.value.toLowerCase().trim());
            }, 500);
        });

        dateInput.addEventListener('focus', function() {
            hintElement.style.transform = 'scale(1.02)';
            hintElement.style.boxShadow = '0 4px 15px rgba(135, 169, 107, 0.2)';
        });

        dateInput.addEventListener('blur', function() {
            hintElement.style.transform = 'scale(1)';
            hintElement.style.boxShadow = 'none';
        });

        function updateDateHint(value) {
            // Reset classes
            hintElement.className = 'field-hint';
            
            if (!value) {
                // Default hint
                hintIcon.className = 'fas fa-lightbulb';
                hintText.textContent = 'Formats: date précise (15/06/2025), récurrence (Tous les samedis jusqu\'au 20/12/2025), ou période (01/06/2025 - 15/06/2025)';
                return;
            }

            // Check for recurrence patterns
            const recurrenceKeywords = ['tous les', 'chaque', 'chaque semaine', 'chaque mois', 'quotidien', 'hebdomadaire', 'mensuel'];
            const isRecurrence = recurrenceKeywords.some(keyword => value.includes(keyword));
            
            // Check for days of the week
            const weekdays = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
            const containsWeekday = weekdays.some(day => value.includes(day));
            
            // Check if it looks like a date
            const datePattern = /\d{1,2}[\/\-\.]\d{1,2}[\/\-\.]\d{2,4}/;
            const hasDate = datePattern.test(value);
            
            // Check for period indicators
            const periodIndicators = ['-', 'au', 'jusqu', 'pendant', 'durant'];
            const hasPeriodIndicator = periodIndicators.some(indicator => value.includes(indicator));

            if (isRecurrence || containsWeekday) {
                // User is writing a recurrence
                if (!value.includes('(date non spécifiée)') && !hasDate && !hasPeriodIndicator) {
                    hintElement.classList.add('suggestion');
                    hintIcon.className = 'fas fa-exclamation-triangle';
                    hintText.textContent = '💡 Si vous connaissez la fin: "jusqu\'au [Date]" (ex: "jusqu\'au 20/12/2025"). Sinon ajoutez "(date non spécifiée)"';
                } else if (value.includes('jusqu') || value.includes('pendant')) {
                    hintElement.classList.add('suggestion');
                    hintIcon.className = 'fas fa-calendar-check';
                    hintText.textContent = '✨ Parfait ! Vous précisez une période pour votre récurrence.';
                } else {
                    hintIcon.className = 'fas fa-repeat';
                    hintText.textContent = '🔄 Récurrence détectée. Vous pouvez ajouter une fin: "jusqu\'au 20 mai 2025" ou "(date non spécifiée)"';
                }
            } else if (hasDate) {
                // User is writing a date
                if (value.includes('-') || value.includes('au')) {
                    hintIcon.className = 'fas fa-calendar-week';
                    hintText.textContent = '📅 Super ! Vous définissez une période avec date de début et fin.';
                } else {
                    hintIcon.className = 'fas fa-calendar-day';
                    hintText.textContent = '📅 Date précise détectée. Format correct !';
                }
            } else if (value.length > 3) {
                // User is typing something else, give suggestions
                const suggestions = [];
                
                if (value.includes('printemps') || value.includes('été') || value.includes('automne') || value.includes('hiver')) {
                    hintIcon.className = 'fas fa-seedling';
                    hintText.textContent = '🌸 Période saisonnière ! Vous pouvez ajouter une année: "Printemps 2025"';
                } else {
                    hintElement.classList.add('suggestion');
                    hintIcon.className = 'fas fa-question-circle';
                    hintText.textContent = '🤔 Essayez: "15/06/2025", "Tous les samedis", "01/06/2025 - 15/06/2025", ou "Printemps 2025"';
                }
            }
        }
    }

    // Enhanced Image Upload with Drag & Drop
    function initializeImageUpload() {
        const uploadZone = document.getElementById('upload-zone');
        const imageInput = document.getElementById('image-input');
        const browseButton = document.getElementById('browse-button');
        const previewContainer = document.getElementById('preview-container');
        const croppedContainer = document.getElementById('cropped-container');
        const imagePreview = document.getElementById('image-preview');
        const croppedPreview = document.getElementById('cropped-preview');
        const croppedData = document.getElementById('cropped-data');
        const cropButton = document.getElementById('crop-button');
        const changeImageButton = document.getElementById('change-image');
        const recropButton = document.getElementById('recrop-button');
        const cropModal = document.getElementById('crop-modal');
        const cropperImage = document.getElementById('cropper-image');
        const applyCropButton = document.getElementById('apply-crop');
        const cancelCropButton = document.getElementById('cancel-crop');
        const closeModal = document.querySelector('.close-modal');

        let cropper = null;
        let currentImageFile = null;

        // Enhanced drag and drop with visual feedback
        uploadZone.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.style.borderColor = 'var(--sage-green)';
            this.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
            this.style.transform = 'scale(1.02)';
            this.classList.add('drag-over');
        });

        uploadZone.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.style.borderColor = 'rgba(135, 169, 107, 0.4)';
            this.style.backgroundColor = '';
            this.style.transform = 'scale(1)';
            this.classList.remove('drag-over');
        });

        uploadZone.addEventListener('drop', function(e) {
            e.preventDefault();
            this.style.borderColor = 'rgba(135, 169, 107, 0.4)';
            this.style.backgroundColor = '';
            this.style.transform = 'scale(1)';
            this.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleImageSelection(files[0]);
            }
        });

        // Click handlers
        uploadZone.addEventListener('click', () => imageInput.click());
        browseButton.addEventListener('click', (e) => {
            e.stopPropagation();
            imageInput.click();
        });

        imageInput.addEventListener('change', function() {
            if (this.files.length > 0) {
                handleImageSelection(this.files[0]);
            }
        });

        function handleImageSelection(file) {
            if (!file.type.startsWith('image/')) {
                showNotification('Veuillez sélectionner un fichier image valide.', 'error');
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                showNotification('La taille du fichier ne doit pas dépasser 5MB.', 'error');
                return;
            }

            currentImageFile = file;
            const reader = new FileReader();
            
            reader.onload = function(e) {
                imagePreview.src = e.target.result;
                uploadZone.classList.add('hidden');
                previewContainer.classList.remove('hidden');
                croppedContainer.classList.add('hidden');
                croppedData.value = '';
                
                showNotification('Image chargée avec succès ! 📸', 'success');
            };
            
            reader.readAsDataURL(file);
        }

        // Crop functionality
        cropButton.addEventListener('click', function() {
            cropperImage.src = imagePreview.src;
            cropModal.classList.remove('hidden');
            
            setTimeout(() => {
                if (cropper) {
                    cropper.destroy();
                }
                
                cropper = new Cropper(cropperImage, {
                    aspectRatio: 4/3,
                    viewMode: 2,
                    dragMode: 'move',
                    autoCropArea: 0.8,
                    responsive: true,
                    cropBoxResizable: true,
                    cropBoxMovable: true,
                    background: false,
                    guides: true,
                    center: true,
                    highlight: true
                });
            }, 100);
        });

        applyCropButton.addEventListener('click', function() {
            if (cropper) {
                const canvas = cropper.getCroppedCanvas({
                    width: 800,
                    height: 600,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                });

                const croppedImageData = canvas.toDataURL('image/jpeg', 0.9);
                croppedPreview.src = croppedImageData;
                croppedData.value = croppedImageData;

                previewContainer.classList.add('hidden');
                croppedContainer.classList.remove('hidden');
                cropModal.classList.add('hidden');
                
                showNotification('Image recadrée avec succès ! ✂️', 'success');

                cropper.destroy();
                cropper = null;
            }
        });

        // Modal close handlers
        cancelCropButton.addEventListener('click', closeCropModal);
        closeModal.addEventListener('click', closeCropModal);

        function closeCropModal() {
            cropModal.classList.add('hidden');
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }

        changeImageButton.addEventListener('click', function() {
            uploadZone.classList.remove('hidden');
            previewContainer.classList.add('hidden');
            croppedContainer.classList.add('hidden');
            imageInput.value = '';
            croppedData.value = '';
            currentImageFile = null;
        });

        recropButton.addEventListener('click', function() {
            if (currentImageFile) {
                cropButton.click();
            }
        });

        cropModal.addEventListener('click', function(e) {
            if (e.target === cropModal) {
                closeCropModal();
            }
        });
    }

    // Enhanced Form Validation
    function initializeFormValidation() {
        const form = document.getElementById('activity-form');
        const requiredFields = form.querySelectorAll('[required]');

        form.addEventListener('submit', function(e) {
            let isValid = true;
            let firstInvalidField = null;

            // Check required fields
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    markFieldAsInvalid(field);
                    isValid = false;
                    if (!firstInvalidField) {
                        firstInvalidField = field;
                    }
                } else {
                    markFieldAsValid(field);
                }
            });

            // Check image requirement
            const croppedImage = document.getElementById('cropped-data').value;
            const imageInput = document.getElementById('image-input');
            const uploadZone = document.getElementById('upload-zone');

            if (!croppedImage && !imageInput.files.length) {
                uploadZone.style.borderColor = 'var(--coral)';
                uploadZone.style.backgroundColor = 'rgba(255, 107, 138, 0.1)';
                showNotification('Une image est requise pour faire pousser votre activité ! 📸', 'error');
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = uploadZone;
                }
            }

            // Check tags requirement
            const checkedTags = form.querySelectorAll('input[name="tags[]"]:checked');
            if (checkedTags.length === 0) {
                const tagsSection = document.querySelector('.tags-section');
                tagsSection.style.borderColor = 'var(--coral)';
                tagsSection.style.backgroundColor = 'rgba(255, 107, 138, 0.05)';
                showNotification('Choisissez au moins un écosystème pour votre activité ! 🏷️', 'error');
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = tagsSection;
                }
            }

            if (!isValid) {
                e.preventDefault();
                if (firstInvalidField) {
                    firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (firstInvalidField.focus) {
                        setTimeout(() => firstInvalidField.focus(), 500);
                    }
                }
                return false;
            }

            // Show loading state
            const submitButton = form.querySelector('.btn-submit');
            const originalContent = submitButton.innerHTML;
            submitButton.innerHTML = '<div class="btn-content"><i class="fas fa-spinner fa-spin"></i><span>🌱 Plantation en cours...</span></div>';
            submitButton.disabled = true;

            return true;
        });

        function markFieldAsInvalid(field) {
            field.style.borderColor = 'var(--coral)';
            field.style.boxShadow = '0 0 0 4px rgba(255, 107, 138, 0.2)';
            field.style.backgroundColor = 'rgba(255, 107, 138, 0.05)';
            
            field.style.animation = 'shake 0.5s ease-in-out';
            setTimeout(() => {
                field.style.animation = '';
            }, 500);
        }

        function markFieldAsValid(field) {
            field.style.borderColor = 'var(--sage-green)';
            field.style.boxShadow = '0 0 0 4px rgba(135, 169, 107, 0.2)';
            field.style.backgroundColor = 'rgba(135, 169, 107, 0.05)';
        }

        // Real-time validation
        requiredFields.forEach(field => {
            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    markFieldAsValid(this);
                } else {
                    this.style.borderColor = 'rgba(135, 169, 107, 0.3)';
                    this.style.boxShadow = 'none';
                    this.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
                }
            });
        });

        // Tag selection validation
        const tagCheckboxes = form.querySelectorAll('input[name="tags[]"]');
        tagCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const checkedTags = form.querySelectorAll('input[name="tags[]"]:checked');
                const tagsSection = document.querySelector('.tags-section');
                
                if (checkedTags.length > 0) {
                    tagsSection.style.borderColor = '';
                    tagsSection.style.backgroundColor = '';
                }
            });
        });
    }

// Simple Tag Selection
function initializeTagAnimations() {
    const tagCards = document.querySelectorAll('.tag-card');
    
    tagCards.forEach(card => {
        const checkbox = card.querySelector('input[type="checkbox"]');
        
        // Simple click handler
        card.addEventListener('click', function() {
            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change'));
        });

        // Simple color change on selection
        checkbox.addEventListener('change', function() {
            if (this.checked) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
    });
}

    // Enhanced Notification System
    function showNotification(message, type = 'success') {
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => {
            notification.style.animation = 'slideOut 0.3s ease-in forwards';
            setTimeout(() => notification.remove(), 300);
        });

        const notification = document.createElement('div');
        notification.classList.add('notification', type);
        
        const icons = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        const colors = {
            success: 'linear-gradient(135deg, var(--sage-green), var(--forest-green))',
            error: 'linear-gradient(135deg, var(--coral), #ff4757)',
            warning: 'linear-gradient(135deg, var(--sunset-orange), #f39c12)',
            info: 'linear-gradient(135deg, var(--sky-blue), #3498db)'
        };

        notification.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="fas ${icons[type]}"></i>
                <span>${message}</span>
            </div>
        `;

        Object.assign(notification.style, {
            position: 'fixed',
            top: '20px',
            right: '20px',
            padding: '15px 20px',
            borderRadius: '15px',
            color: 'white',
            fontWeight: '600',
            zIndex: '10000',
            maxWidth: '400px',
            background: colors[type],
            boxShadow: '0 8px 25px rgba(0, 0, 0, 0.2)',
            backdropFilter: 'blur(10px)',
            animation: 'slideIn 0.5s ease-out',
            border: '2px solid rgba(255, 255, 255, 0.2)',
            cursor: 'pointer'
        });

        document.body.appendChild(notification);

        // Auto remove
        const autoRemove = setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease-in forwards';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        }, 5000);

        // Click to remove
        notification.addEventListener('click', () => {
            clearTimeout(autoRemove);
            notification.style.animation = 'slideOut 0.3s ease-in forwards';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 300);
        });
    }

    // Add required CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        @keyframes iconBounce {
            0% { transform: scale(1); }
            50% { transform: scale(1.3) rotate(15deg); }
            100% { transform: scale(1) rotate(0deg); }
        }
        
        .animate-in {
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
            transform: translateY(20px);
        }
        
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: rippleEffect 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes rippleEffect {
            to {
                transform: scale(2);
                opacity: 0;
            }
        }
        
        .upload-zone.drag-over {
            border-style: solid !important;
            background: rgba(135, 169, 107, 0.1) !important;
        }
        
        .tag-card.selected {
            animation: selectedPulse 2s ease-in-out infinite;
        }
        
        @keyframes selectedPulse {
            0%, 100% { box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2); }
            50% { box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25); }
        }
    `;
    document.head.appendChild(style);
});