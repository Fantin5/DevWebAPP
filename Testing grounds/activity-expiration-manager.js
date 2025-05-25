/**
 * Enhanced Frontend Integration for Activity Expiration System
 * This script provides comprehensive client-side functionality for managing
 * activity expiration status, real-time updates, and user interactions
 */

class ActivityExpirationManager {
    constructor() {
        this.updateInterval = 60000; // Update every minute
        this.intervalId = null;
        this.currentFilters = {};
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.startPeriodicUpdates();
        this.setupRealTimeNotifications();
    }

    setupEventListeners() {
        // Enhanced cart functionality with expiration checks
        document.addEventListener('click', (e) => {
            if (e.target.closest('.add-to-cart-button')) {
                e.preventDefault();
                this.handleAddToCart(e.target.closest('.add-to-cart-button'));
            }

            if (e.target.closest('.signup-button')) {
                e.preventDefault();
                this.handleDirectRegistration(e.target.closest('.signup-button'));
            }
        });

        // Enhanced activity card clicks with expiration awareness
        document.addEventListener('click', (e) => {
            const activityCard = e.target.closest('.activity-card, .featured-card');
            if (activityCard && !e.target.closest('button') && !e.target.closest('a')) {
                this.handleActivityCardClick(activityCard);
            }
        });

        // Real-time filter updates
        document.addEventListener('change', (e) => {
            if (e.target.matches('.expiration-filter')) {
                this.handleFilterChange(e.target);
            }
        });
    }

    async handleAddToCart(button) {
        const activityId = button.getAttribute('data-id');
        const activityData = this.extractActivityData(button);

        try {
            // First validate with server
            const validation = await this.validateCartAddition(activityId);
            
            if (!validation.success) {
                this.showNotification(validation.message, validation.redirect ? 'error' : 'warning');
                
                if (validation.redirect) {
                    setTimeout(() => {
                        window.location.href = validation.redirect;
                    }, 2000);
                }
                return;
            }

            // Add to cart if validation passes
            this.addToLocalCart(activityData);
            this.animateButton(button, 'success');
            this.showNotification('Activité ajoutée au panier !', 'success');
            
        } catch (error) {
            console.error('Error adding to cart:', error);
            this.showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
        }
    }

    async handleDirectRegistration(button) {
        const activityId = button.getAttribute('data-id');
        
        try {
            this.setButtonLoading(button, true);
            
            const result = await this.registerForActivity(activityId);
            
            if (result.success) {
                this.showNotification(result.message, 'success');
                this.removeFromCartIfExists(activityId);
                
                // Update UI to reflect registration
                setTimeout(() => {
                    this.updateActivityStatus(activityId, 'registered');
                }, 1000);
                
            } else {
                this.showNotification(result.message, result.redirect ? 'error' : 'warning');
                
                if (result.redirect) {
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 2000);
                }
            }
            
        } catch (error) {
            console.error('Error registering for activity:', error);
            this.showNotification('Une erreur est survenue lors de l\'inscription', 'error');
        } finally {
            this.setButtonLoading(button, false);
        }
    }

    async handleActivityCardClick(card) {
        const activityId = card.getAttribute('data-id');
        
        if (!activityId) return;

        // Check expiration status before navigation
        try {
            const status = await this.getActivityStatus(activityId);
            
            if (status.is_expired) {
                // Show expiration notice but still allow viewing
                this.showNotification('Cette activité a expiré', 'info');
            }
            
            // Navigate to activity detail
            window.location.href = `activite.php?id=${activityId}`;
            
        } catch (error) {
            // If status check fails, still navigate
            window.location.href = `activite.php?id=${activityId}`;
        }
    }

    async validateCartAddition(activityId) {
        const response = await fetch('activity_functions.php?action=validate_cart_addition', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                activity_id: activityId
            })
        });
        
        return await response.json();
    }

    async registerForActivity(activityId) {
        const response = await fetch('activity_functions.php?action=register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                activity_id: activityId
            })
        });
        
        return await response.json();
    }

    async getActivityStatus(activityId) {
        const response = await fetch(`activity_functions.php?action=get_expiration_status&activity_id=${activityId}`);
        return await response.json();
    }

    addToLocalCart(activityData) {
        const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
        const existingIndex = cart.findIndex(item => item.id === activityData.id);
        
        if (existingIndex === -1) {
            cart.push(activityData);
            localStorage.setItem('synapse-cart', JSON.stringify(cart));
            this.updateCartCount();
        }
    }

    removeFromCartIfExists(activityId) {
        const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
        const filteredCart = cart.filter(item => item.id !== activityId);
        
        if (filteredCart.length !== cart.length) {
            localStorage.setItem('synapse-cart', JSON.stringify(filteredCart));
            this.updateCartCount();
        }
    }

    updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
        const cartCount = document.getElementById('panier-count');
        if (cartCount) {
            cartCount.textContent = cart.length;
        }
    }

    extractActivityData(button) {
        return {
            id: button.getAttribute('data-id'),
            titre: button.getAttribute('data-title'),
            prix: parseFloat(button.getAttribute('data-price')) || 0,
            image: button.getAttribute('data-image'),
            periode: button.getAttribute('data-period'),
            tags: (button.getAttribute('data-tags') || '').split(',').filter(tag => tag.trim())
        };
    }

    setButtonLoading(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Chargement...</span>';
        } else {
            button.disabled = false;
            // Restore original content based on button type
            if (button.classList.contains('signup-button')) {
                button.innerHTML = '<i class="fa-solid fa-user-plus"></i> <span>S\'inscrire</span>';
            }
        }
    }

    animateButton(button, type) {
        button.classList.add(type === 'success' ? 'success-animation' : 'error-animation');
        
        setTimeout(() => {
            button.classList.remove('success-animation', 'error-animation');
        }, 1000);
    }

    updateActivityStatus(activityId, status) {
        const activityCards = document.querySelectorAll(`[data-id="${activityId}"]`);
        
        activityCards.forEach(card => {
            const button = card.querySelector('.signup-button, .add-to-cart-button');
            
            if (status === 'registered') {
                if (button) {
                    button.outerHTML = `
                        <div class="registration-badge">
                            <i class="fa-solid fa-check-circle"></i> 
                            <span>Inscrit</span>
                        </div>
                    `;
                }
            } else if (status === 'expired') {
                if (button) {
                    button.disabled = true;
                    button.innerHTML = '<i class="fa-solid fa-clock"></i> <span>Expiré</span>';
                    button.classList.add('expired-button');
                }
                
                // Add expired badge
                const imageContainer = card.querySelector('.image-container, .card-image');
                if (imageContainer && !imageContainer.querySelector('.expired-badge')) {
                    const expiredBadge = document.createElement('div');
                    expiredBadge.className = 'expired-badge';
                    expiredBadge.innerHTML = '<i class="fa-solid fa-clock"></i> Expiré';
                    imageContainer.appendChild(expiredBadge);
                }
            }
        });
    }

    startPeriodicUpdates() {
        // Update expiration status every minute
        this.intervalId = setInterval(() => {
            this.updateExpirationStatus();
        }, this.updateInterval);
        
        // Initial update
        this.updateExpirationStatus();
    }

    async updateExpirationStatus() {
        const activityCards = document.querySelectorAll('.activity-card, .featured-card');
        const activityIds = Array.from(activityCards).map(card => card.getAttribute('data-id')).filter(Boolean);
        
        if (activityIds.length === 0) return;

        try {
            // Check multiple activities at once
            const promises = activityIds.map(id => this.getActivityStatus(id));
            const results = await Promise.all(promises);
            
            results.forEach((result, index) => {
                if (result.success) {
                    const activityId = activityIds[index];
                    this.updateActivityCardStatus(activityId, result);
                }
            });
            
        } catch (error) {
            console.error('Error updating expiration status:', error);
        }
    }

    updateActivityCardStatus(activityId, statusData) {
        const cards = document.querySelectorAll(`[data-id="${activityId}"]`);
        
        cards.forEach(card => {
            // Update expiration badges
            this.updateExpirationBadges(card, statusData);
            
            // Update button states
            this.updateButtonStates(card, statusData);
            
            // Update countdown displays
            this.updateCountdownDisplay(card, statusData);
        });
    }

    updateExpirationBadges(card, statusData) {
        const imageContainer = card.querySelector('.image-container, .card-image');
        if (!imageContainer) return;

        // Remove existing expiration badges
        const existingBadges = imageContainer.querySelectorAll('.expired-badge, .expiring-badge');
        existingBadges.forEach(badge => badge.remove());

        if (statusData.is_expired) {
            const expiredBadge = document.createElement('div');
            expiredBadge.className = 'expired-badge';
            expiredBadge.innerHTML = '<i class="fa-solid fa-clock"></i> Expiré';
            imageContainer.appendChild(expiredBadge);
        } else if (statusData.is_expiring_soon) {
            const expiringBadge = document.createElement('div');
            expiringBadge.className = 'expiring-badge';
            
            if (statusData.days_until_expiration === 0) {
                expiringBadge.innerHTML = '<i class="fa-solid fa-exclamation-triangle"></i> Dernier jour !';
            } else if (statusData.days_until_expiration === 1) {
                expiringBadge.innerHTML = '<i class="fa-solid fa-exclamation-triangle"></i> Termine demain !';
            } else {
                expiringBadge.innerHTML = `<i class="fa-solid fa-exclamation-triangle"></i> Plus que ${statusData.days_until_expiration} jours !`;
            }
            
            imageContainer.appendChild(expiringBadge);
        }
    }

    updateButtonStates(card, statusData) {
        const buttons = card.querySelectorAll('.signup-button, .add-to-cart-button');
        
        buttons.forEach(button => {
            if (statusData.is_expired) {
                button.disabled = true;
                button.classList.add('expired-button');
                button.innerHTML = '<i class="fa-solid fa-clock"></i> <span>Expiré</span>';
            }
        });
    }

    updateCountdownDisplay(card, statusData) {
        const countdownElement = card.querySelector('.countdown-display');
        
        if (countdownElement && statusData.days_until_expiration !== null) {
            if (statusData.days_until_expiration > 0) {
                countdownElement.textContent = statusData.expiration_message;
                countdownElement.style.display = 'block';
            } else {
                countdownElement.style.display = 'none';
            }
        }
    }

    setupRealTimeNotifications() {
        // Listen for browser visibility change to update when user returns
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.updateExpirationStatus();
            }
        });

        // Listen for focus events
        window.addEventListener('focus', () => {
            this.updateExpirationStatus();
        });
    }

    async cleanupCart() {
        const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
        
        if (cart.length === 0) return;

        try {
            const response = await fetch('activity_functions.php?action=cleanup_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_items: cart
                })
            });
            
            const result = await response.json();
            
            if (result.success && result.items_to_remove.length > 0) {
                const cleanedCart = cart.filter(item => !result.items_to_remove.includes(parseInt(item.id)));
                localStorage.setItem('synapse-cart', JSON.stringify(cleanedCart));
                this.updateCartCount();
                
                if (result.removed_reasons.length > 0) {
                    const messages = result.removed_reasons.map(reason => 
                        `${reason.title}: ${reason.reason}`
                    ).join('\n');
                    
                    this.showNotification(
                        `Articles retirés du panier:\n${messages}`, 
                        'info'
                    );
                }
            }
            
        } catch (error) {
            console.error('Error cleaning up cart:', error);
        }
    }

    handleFilterChange(filterElement) {
        const filterType = filterElement.getAttribute('data-filter-type');
        const filterValue = filterElement.value;
        
        this.currentFilters[filterType] = filterValue;
        this.applyFilters();
    }

    async applyFilters() {
        const activitiesContainer = document.getElementById('activities-grid') || 
                                  document.querySelector('.activities-grid');
        
        if (!activitiesContainer) return;

        try {
            const response = await fetch('activity_functions.php?action=get_activities_with_expiration', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    filters: this.currentFilters
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.renderActivities(activitiesContainer, result.activities);
            }
            
        } catch (error) {
            console.error('Error applying filters:', error);
        }
    }

    renderActivities(container, activities) {
        // This would be implemented based on your specific HTML structure
        // For now, just update existing cards visibility/content
        const allCards = container.querySelectorAll('.activity-card, .featured-card');
        
        allCards.forEach(card => {
            const activityId = card.getAttribute('data-id');
            const activity = activities.find(a => a.id === activityId);
            
            if (activity) {
                card.style.display = 'block';
                this.updateActivityCardStatus(activityId, activity);
            } else {
                card.style.display = 'none';
            }
        });
    }

    showNotification(message, type = 'success') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => {
            notification.remove();
        });

        // Create new notification
        const notification = document.createElement('div');
        notification.classList.add('notification', type);
        
        // Add appropriate icon
        let icon = 'fa-circle-check';
        if (type === 'info') {
            icon = 'fa-circle-info';
        } else if (type === 'error' || type === 'warning') {
            icon = 'fa-circle-exclamation';
        }
        
        notification.innerHTML = `<i class="fa-solid ${icon}"></i> ${message}`;
        
        // Add to document
        document.body.appendChild(notification);
        
        // Auto-hide notification
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 500);
        }, 4000);
    }

    // Cleanup method
    destroy() {
        if (this.intervalId) {
            clearInterval(this.intervalId);
        }
    }
}

// Enhanced Cart Management
class CartManager {
    constructor() {
        this.init();
    }

    init() {
        this.cleanupExpiredItems();
        this.setupEventListeners();
    }

    async cleanupExpiredItems() {
        const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
        
        if (cart.length === 0) return;

        try {
            const response = await fetch('activity_functions.php?action=cleanup_cart', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_items: cart
                })
            });
            
            const result = await response.json();
            
            if (result.success && result.items_to_remove.length > 0) {
                const cleanedCart = cart.filter(item => !result.items_to_remove.includes(parseInt(item.id)));
                localStorage.setItem('synapse-cart', JSON.stringify(cleanedCart));
                
                // Show notification about removed items
                if (result.removed_reasons.length > 0) {
                    const message = `Articles retirés du panier: ${result.removed_reasons.length} activité(s)`;
                    this.showNotification(message, 'info');
                }
            }
            
        } catch (error) {
            console.error('Error cleaning up cart:', error);
        }
    }

    setupEventListeners() {
        document.addEventListener('click', (e) => {
            if (e.target.closest('.checkout-button')) {
                e.preventDefault();
                this.handleCheckout();
            }
        });
    }

    async handleCheckout() {
        const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
        
        if (cart.length === 0) {
            this.showNotification('Votre panier est vide', 'warning');
            return;
        }

        try {
            const response = await fetch('activity_functions.php?action=validate_cart_payment', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    cart_items: cart
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Redirect to payment
                this.redirectToPayment(cart);
            } else {
                this.showNotification(result.message, 'error');
                
                if (result.redirect) {
                    setTimeout(() => {
                        window.location.href = result.redirect;
                    }, 2000);
                }
            }
            
        } catch (error) {
            console.error('Error validating cart:', error);
            this.showNotification('Une erreur est survenue', 'error');
        }
    }

    redirectToPayment(cart) {
        const form = document.createElement('form');
        form.method = 'post';
        form.action = '../Paiement/paiement.php';
        form.style.display = 'none';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'panier_json';
        input.value = JSON.stringify(cart);
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }

    showNotification(message, type) {
        const notification = document.createElement('div');
        notification.classList.add('notification', type);
        notification.innerHTML = `<i class="fa-solid fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 500);
        }, 4000);
    }
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Initialize managers
    const expirationManager = new ActivityExpirationManager();
    const cartManager = new CartManager();
    
    // Initialize cart count
    expirationManager.updateCartCount();
    
    // Cleanup expired items from cart
    expirationManager.cleanupCart();
    
    // Store managers globally for debugging
    window.activityManagers = {
        expiration: expirationManager,
        cart: cartManager
    };
});

// CSS Styles for new elements (add to your CSS file)
const additionalStyles = `
.expired-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: rgba(231, 76, 60, 0.9);
    color: white;
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    z-index: 10;
}

.expiring-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: rgba(255, 193, 7, 0.9);
    color: #333;
    padding: 0.5rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    backdrop-filter: blur(8px);
    display: flex;
    align-items: center;
    gap: 0.5rem;
    z-index: 10;
    animation: pulse 2s infinite;
}

.expired-button {
    background: #6c757d !important;
    cursor: not-allowed !important;
    opacity: 0.6;
}

.registration-badge {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    text-align: center;
    font-weight: bold;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.success-animation {
    animation: successPulse 0.6s ease;
}

.error-animation {
    animation: errorShake 0.6s ease;
}

@keyframes successPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); background-color: #28a745; }
    100% { transform: scale(1); }
}

@keyframes errorShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.7; }
}

.countdown-display {
    font-size: 0.9rem;
    color: #dc3545;
    font-weight: 600;
    margin-top: 0.5rem;
    padding: 0.25rem 0.5rem;
    background: rgba(220, 53, 69, 0.1);
    border-radius: 12px;
    display: none;
}
`;

// Inject additional styles
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);