document.addEventListener('DOMContentLoaded', function() {
    // Récupérer le contenu du panier du localStorage
    let cartItems = JSON.parse(localStorage.getItem('synapse-cart')) || [];
    
    // Mettre à jour le compteur du panier
    updateCartCount();
    
    // Afficher le contenu du panier
    displayCart();
    
    // Fonction pour mettre à jour le compteur du panier
    function updateCartCount() {
        const cartCount = document.getElementById('panier-count');
        if (cartCount) {
            cartCount.textContent = cartItems.length;
        }
    }
    
    // Fonction pour afficher le contenu du panier
    function displayCart() {
        const panierContent = document.getElementById('panier-content');

        // Si le panier est vide
        if (cartItems.length === 0) {
            panierContent.innerHTML = `
                <div class="panier-empty">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <p>Votre panier est vide</p>
                    <a href="main.php" class="continuer-achats">
                        <i class="fa-solid fa-arrow-left"></i> Continuer mes achats
                    </a>
                </div>
            `;
            return;
        }

        // Calculer le total
        let total = 0;
        cartItems.forEach(item => {
            if (item.prix > 0) {
                total += parseFloat(item.prix);
            }
        });

        // Afficher les éléments du panier
        let cartHTML = `
            <div class="panier-items">
        `;

        cartItems.forEach((item, index) => {
            // Formater les tags
            const tagsHTML = item.tags.map(tag => {
                let tagClass = '';
                if (['exterieur', 'gratuit', 'ecologie', 'randonnee'].includes(tag)) {
                    tagClass = 'accent';
                } else if (['interieur', 'bien_etre', 'meditation'].includes(tag)) {
                    tagClass = 'secondary';
                }
                return `<span class="panier-item-tag ${tagClass}">${formatTagName(tag)}</span>`;
            }).join('');

            // Formater le prix
            const priceText = item.prix > 0 ? `${item.prix.toFixed(2)} €` : 'Gratuit';

            cartHTML += `
                <div class="panier-item" data-id="${item.id}">
                    <img src="${item.image}" alt="${item.titre}" class="panier-item-image">
                    <div class="panier-item-details">
                        <h3 class="panier-item-title">${item.titre}</h3>
                        ${item.periode ? `<p class="panier-item-period"><i class="fa-regular fa-calendar"></i> ${item.periode}</p>` : ''}
                        <div class="panier-item-tags">
                            ${tagsHTML}
                        </div>
                        <p class="panier-item-price">${priceText}</p>
                    </div>
                    <div class="panier-item-actions">
                        <button class="panier-item-buy" data-index="${index}">
                            <i class="fa-solid fa-credit-card"></i> Acheter maintenant
                        </button>
                        <button class="panier-item-remove" data-index="${index}">
                            <i class="fa-solid fa-trash"></i> Supprimer
                        </button>
                    </div>
                </div> 
            `;
        });

        cartHTML += `
            </div>
            <div class="panier-summary">
                <h3>Récapitulatif de votre panier</h3>
                <div class="summary-row">
                    <span>Nombre d'activités</span>
                    <span>${cartItems.length}</span>
                </div>
                <div class="summary-row">
                    <span>Activités gratuites</span>
                    <span>${cartItems.filter(item => item.prix <= 0).length}</span>
                </div>
                <div class="summary-row">
                    <span>Activités payantes</span>
                    <span>${cartItems.filter(item => item.prix > 0).length}</span>
                </div>
                <div class="summary-total">
                    <span>Total</span>
                    <span>${total.toFixed(2)} €</span>
                </div>
                <button class="checkout-button">
                    <i class="fa-solid fa-lock"></i> Procéder au paiement
                </button>
            </div>
        `;

        panierContent.innerHTML = cartHTML;

        // Animation des éléments du panier (staggered animation)
        const panierItems = document.querySelectorAll('.panier-item');
        panierItems.forEach((item, index) => {
            item.style.animationDelay = `${0.1 + index * 0.1}s`;
        });

        // Ajouter les événements pour supprimer des éléments du panier
        document.querySelectorAll('.panier-item-remove').forEach(button => {
            button.addEventListener('click', function () {
                const index = parseInt(this.getAttribute('data-index'));
                const itemElement = this.closest('.panier-item');
                
                // Animation de suppression
                itemElement.style.transform = 'translateX(100%)';
                itemElement.style.opacity = '0';
                itemElement.style.transition = 'all 0.5s ease';
                
                // Attendre la fin de l'animation avant de supprimer l'élément
                setTimeout(() => {
                    removeFromCart(index);
                    showNotification('Article supprimé du panier', 'error');
                }, 500);
            });
        });

        // Ajouter les événements pour les boutons "Acheter individuellement"
        document.querySelectorAll('.panier-item-buy').forEach(button => {
            button.addEventListener('click', function () {
                // Animation du bouton
                this.classList.add('buying');
                
                // Vérifier si l'utilisateur est connecté
                fetch('../Connexion-Inscription/auth_check.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.logged_in) {
                            // Afficher une notification si l'utilisateur est connecté
                            showNotification('Préparation de votre commande...', 'info');
                            setTimeout(() => {
                                showNotification('Fonctionnalité de paiement à venir.', 'info');
                            }, 2000);
                        } else {
                            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
                            showNotification('Connexion requise pour l\'achat', 'error');
                            setTimeout(() => {
                                window.location.href = '../Connexion-Inscription/login_form.php';
                            }, 1500);
                        }
                    })
                    .catch(error => {
                        console.error('Error checking login status:', error);
                        showNotification('Une erreur est survenue', 'error');
                    });
            });
        });

        // Événement pour le bouton de paiement
        const checkoutButton = document.querySelector('.checkout-button');
        if (checkoutButton) {
            checkoutButton.addEventListener('click', function () {
                // Animation du bouton
                this.classList.add('processing');
                this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Traitement en cours...';
                
                // Vérifier si l'utilisateur est connecté
                fetch('../Connexion-Inscription/auth_check.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.logged_in) {
                            // Afficher une notification si l'utilisateur est connecté
                            setTimeout(() => {
                                showNotification('Préparation de votre commande...', 'info');
                                
                                setTimeout(() => {
                                    showNotification('Fonctionnalité de paiement à venir.', 'info');
                                    this.innerHTML = '<i class="fa-solid fa-lock"></i> Procéder au paiement';
                                    this.classList.remove('processing');
                                }, 2000);
                            }, 1000);
                        } else {
                            // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
                            showNotification('Connexion requise pour l\'achat', 'error');
                            setTimeout(() => {
                                window.location.href = '../Connexion-Inscription/login_form.php';
                            }, 1500);
                        }
                    })
                    .catch(error => {
                        console.error('Error checking login status:', error);
                        showNotification('Une erreur est survenue', 'error');
                        this.innerHTML = '<i class="fa-solid fa-lock"></i> Procéder au paiement';
                        this.classList.remove('processing');
                    });
            });
        }
    }
    
    // Fonction pour supprimer un élément du panier
    function removeFromCart(index) {
        if (index >= 0 && index < cartItems.length) {
            cartItems.splice(index, 1);
            localStorage.setItem('synapse-cart', JSON.stringify(cartItems));
            updateCartCount();
            displayCart();
        }
    }
    
    // Fonction pour afficher une notification
    function showNotification(message, type = 'success') {
        const notification = document.getElementById('notification');
        const notificationMessage = document.getElementById('notification-message');
        
        // Définir le message et le type
        notificationMessage.textContent = message;
        notification.className = 'notification';
        notification.classList.add(type);
        
        // Mettre à jour l'icône en fonction du type
        const icon = notification.querySelector('i');
        if (icon) {
            icon.className = 'fa-solid';
            if (type === 'success') {
                icon.classList.add('fa-circle-check');
            } else if (type === 'error') {
                icon.classList.add('fa-circle-exclamation');
            } else if (type === 'info') {
                icon.classList.add('fa-circle-info');
            }
        }
        
        // Afficher la notification
        setTimeout(() => {
            notification.classList.add('visible');
            
            // Cacher la notification après 3 secondes
            setTimeout(() => {
                notification.classList.remove('visible');
            }, 3000);
        }, 100);
    }
    
    // Fonction pour formater le nom du tag (remplacer les underscores par des espaces et mettre en majuscule la première lettre)
    function formatTagName(tag) {
        return tag.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    // Fonction pour animer les éléments flottants
    function animateFloatingElements() {
        const elements = document.querySelectorAll('.floating-element');
        
        elements.forEach(element => {
            // Position aléatoire sur l'axe X
            const randomX = Math.floor(Math.random() * window.innerWidth);
            
            // Position aléatoire pour l'animation (vitesse différente)
            const randomDuration = 15 + Math.floor(Math.random() * 15); // Entre 15s et 30s
            
            // Appliquer les styles
            element.style.left = `${randomX}px`;
            element.style.bottom = '-30px';
            element.style.animationDuration = `${randomDuration}s`;
        });
    }
    
    // Initialiser l'animation des éléments flottants au chargement
    animateFloatingElements();
    
    // Réinitialiser les animations au redimensionnement de la fenêtre
    window.addEventListener('resize', animateFloatingElements);
});