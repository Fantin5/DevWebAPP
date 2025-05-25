document.addEventListener('DOMContentLoaded', function() {
    let cartItems = JSON.parse(localStorage.getItem('synapse-cart')) || [];
    let tagCache = {};
    
    // Centralize tag fetching
    async function getTagDefinitions() {
        try {
            const response = await fetch('get_tags.php');
            tagCache = await response.json();
            return tagCache;
        } catch (error) {
            console.error('Error fetching tags:', error);
            return {};
        }
    }

    function getTagInfo(tagName) {
        return tagCache[tagName] || {
            display_name: formatTagName(tagName),
            class: 'primary'
        };
    }

    // Function to format tag names
    function formatTagName(tag) {
        return tag.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    // Initialize cart with tags and cleanup
    async function initializeCart() {
        await getTagDefinitions();
        await cleanupCartForRegisteredActivities();
        updateCartCount();
        displayCart();
    }

    // Function to clean up cart using consolidated function
    async function cleanupCartForRegisteredActivities() {
        if (cartItems.length === 0) return;
        
        try {
            const response = await fetch('activity_functions.php?action=cleanup_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_items: cartItems.map(item => ({ id: item.id, title: item.titre }))
                })
            });
            
            const data = await response.json();
            
            if (data.success && data.items_to_remove.length > 0) {
                cartItems = cartItems.filter(item => !data.items_to_remove.includes(item.id));
                localStorage.setItem('synapse-cart', JSON.stringify(cartItems));
                
                if (data.removed_reasons.length > 0) {
                    const messages = data.removed_reasons.map(reason => 
                        `${reason.title}: ${reason.reason}`
                    ).join('\n');
                    
                    showNotification(
                        `Certaines activités ont été retirées de votre panier:\n${messages}`, 
                        'info'
                    );
                }
            }
        } catch (error) {
            console.error('Error cleaning up cart:', error);
        }
    }

    // Start initialization
    initializeCart();
    
    // Function to update cart count
    function updateCartCount() {
        const cartCount = document.getElementById('panier-count');
        if (cartCount) {
            cartCount.textContent = cartItems.length;
        }
    }
    
    // Function to display cart content
    function displayCart() {
        const panierContent = document.getElementById('panier-content');

        // If cart is empty
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

        // Calculate total
        let total = 0;
        cartItems.forEach(item => {
            if (item.prix > 0) {
                total += parseFloat(item.prix);
            }
        });

        // Display cart items
        let cartHTML = `<div class="panier-items">`;

        cartItems.forEach((item, index) => {
            // Format tags with database information
            const tagsHTML = item.tags.map(tag => {
                const tagInfo = getTagInfo(tag);
                return `<span class="panier-item-tag ${tagInfo.class}">${tagInfo.display_name}</span>`;
            }).join('');

            // Format price
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
                <button type="button" class="checkout-button" id="checkout-button">
                    <i class="fa-solid fa-lock"></i> Procéder au paiement
                </button>
            </div>
        `;

        panierContent.innerHTML = cartHTML;

        // Animation of cart items (staggered animation)
        const panierItems = document.querySelectorAll('.panier-item');
        panierItems.forEach((item, index) => {
            item.style.animationDelay = `${0.1 + index * 0.1}s`;
        });

        // Add events to remove items from cart
        document.querySelectorAll('.panier-item-remove').forEach(button => {
            button.addEventListener('click', function () {
                const index = parseInt(this.getAttribute('data-index'));
                const itemElement = this.closest('.panier-item');
                
                // Removal animation
                itemElement.style.transform = 'translateX(100%)';
                itemElement.style.opacity = '0';
                itemElement.style.transition = 'all 0.5s ease';
                
                // Wait for animation to finish before removing element
                setTimeout(() => {
                    removeFromCart(index);
                    showNotification('Article supprimé du panier', 'error');
                }, 500);
            });
        });

        // Add events for "Buy individually" buttons
        document.querySelectorAll('.panier-item-buy').forEach(button => {
            button.addEventListener('click', function () {
                this.classList.add('buying');
                const index = parseInt(this.getAttribute('data-index'));
                const item = cartItems[index];
                
                // Process direct registration using consolidated function
                processDirectRegistration(item, index);
            });
        });

        // Add event for main checkout button
        const checkoutButton = document.getElementById('checkout-button');
        if (checkoutButton) {
            checkoutButton.addEventListener('click', function() {
                validateAndProceedToPayment();
            });
        }
    }
    
    // Function to validate cart and proceed to payment
    async function validateAndProceedToPayment() {
        const checkoutButton = document.getElementById('checkout-button');
        
        // Show loading state
        if (checkoutButton) {
            checkoutButton.disabled = true;
            checkoutButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Vérification...';
        }

        try {
            const response = await fetch('activity_functions.php?action=validate_cart_payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_items: cartItems.map(item => ({ 
                        id: item.id, 
                        title: item.titre,
                        prix: item.prix 
                    }))
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Cart is valid, proceed to payment
                redirectToPayment(cartItems);
            } else {
                if (data.redirect) {
                    showNotification(data.message, 'error');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                } else {
                    showNotification(data.message || 'Impossible de procéder au paiement', 'error');
                    
                    // If there are invalid items, clean up the cart
                    if (data.invalid_items) {
                        await cleanupCartForRegisteredActivities();
                        displayCart();
                    }
                }
            }
        } catch (error) {
            console.error('Error validating cart:', error);
            showNotification('Une erreur est survenue lors de la validation du panier', 'error');
        } finally {
            // Reset button state
            if (checkoutButton) {
                checkoutButton.disabled = false;
                checkoutButton.innerHTML = '<i class="fa-solid fa-lock"></i> Procéder au paiement';
            }
        }
    }
    
    // Function to process direct registration for individual items
    function processDirectRegistration(item, itemIndex) {
        showNotification('Inscription en cours...', 'info');
        
        fetch('activity_functions.php?action=register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                activity_id: item.id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Inscription réussie ! Vous êtes maintenant inscrit à cette activité.', 'success');
                removeFromCart(itemIndex);
                setTimeout(() => {
                    displayCart();
                }, 1000);
            } else {
                if (data.redirect) {
                    showNotification(data.message, 'error');
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 2000);
                } else {
                    showNotification(data.message || 'Erreur lors de l\'inscription', 'error');
                }
            }
        })
        .catch(error => {
            console.error('Error processing registration:', error);
            showNotification('Une erreur est survenue lors de l\'inscription', 'error');
        })
        .finally(() => {
            const button = document.querySelector(`.panier-item-buy[data-index="${itemIndex}"]`);
            if (button) button.classList.remove('buying');
        });
    }
    
    // Function to redirect to payment page with specific cart
    function redirectToPayment(items) {
        // Create temporary form to send data
        const form = document.createElement('form');
        form.method = 'post';
        form.action = '../Paiement/paiement.php';
        form.style.display = 'none';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'panier_json';
        input.value = JSON.stringify(items);
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
    
    // Function to process direct payment (when user already has payment info)
    function processDirectPayment(items) {
        showNotification('Traitement de votre paiement...', 'info');
        
        fetch('../Paiement/payment_functions.php?action=process_payment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ items: items }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Paiement réussi !', 'success');
                
                // Clear entire cart after successful bulk payment
                cartItems = [];
                localStorage.setItem('synapse-cart', JSON.stringify(cartItems));
                updateCartCount();
                displayCart();
                
                setTimeout(() => {
                    window.location.href = '../Testing grounds/main.php';
                }, 2000);
            } else {
                showNotification(data.message || 'Erreur lors du paiement', 'error');
            }
        })
        .catch(error => {
            console.error('Error processing payment:', error);
            showNotification('Une erreur est survenue', 'error');
        });
    }
    
    // Function to remove item from cart
    function removeFromCart(index) {
        if (index >= 0 && index < cartItems.length) {
            cartItems.splice(index, 1);
            localStorage.setItem('synapse-cart', JSON.stringify(cartItems));
            updateCartCount();
            displayCart();
        }
    }
    
    // Function to show notification
    function showNotification(message, type = 'success') {
        const notification = document.getElementById('notification');
        const notificationMessage = document.getElementById('notification-message');
        
        // Set message and type
        notificationMessage.textContent = message;
        notification.className = 'notification';
        notification.classList.add(type);
        
        // Update icon based on type
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
        
        // Show notification
        setTimeout(() => {
            notification.classList.add('visible');
            
            // Hide notification after 4 seconds
            setTimeout(() => {
                notification.classList.remove('visible');
            }, 4000);
        }, 100);
    }

    // Function to animate floating elements
    function animateFloatingElements() {
        const elements = document.querySelectorAll('.floating-element');
        
        elements.forEach(element => {
            // Random position on X axis
            const randomX = Math.floor(Math.random() * window.innerWidth);
            
            // Random position for animation (different speed)
            const randomDuration = 15 + Math.floor(Math.random() * 15); // Between 15s and 30s
            
            // Apply styles
            element.style.left = `${randomX}px`;
            element.style.bottom = '-30px';
            element.style.animationDuration = `${randomDuration}s`;
        });
    }
    
    // Initialize floating elements animation on load
    animateFloatingElements();
    
    // Reset animations on window resize
    window.addEventListener('resize', animateFloatingElements);
});