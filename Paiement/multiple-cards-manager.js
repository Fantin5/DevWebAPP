/**
 * Multiple Cards Manager
 * JavaScript utilities for managing multiple payment cards
 */

class PaymentCardsManager {
    constructor() {
        this.baseUrl = 'payment-functions-php.php';
        this.selectedCardId = null;
    }

    /**
     * Récupère toutes les cartes de l'utilisateur
     */
    async getUserCards() {
        try {
            const response = await fetch(`${this.baseUrl}?action=get_user_cards`);
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Erreur lors de la récupération des cartes:', error);
            return { success: false, message: 'Erreur de connexion' };
        }
    }

    /**
     * Ajoute une nouvelle carte
     */
    async addCard(cardData) {
        try {
            const response = await fetch(`${this.baseUrl}?action=add_card`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(cardData)
            });
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Erreur lors de l\'ajout de la carte:', error);
            return { success: false, message: 'Erreur de connexion' };
        }
    }

    /**
     * Supprime une carte
     */
    async deleteCard(cardId) {
        try {
            const response = await fetch(`${this.baseUrl}?action=delete_card`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ card_id: cardId })
            });
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Erreur lors de la suppression de la carte:', error);
            return { success: false, message: 'Erreur de connexion' };
        }
    }

    /**
     * Définit une carte comme carte par défaut
     */
    async setDefaultCard(cardId) {
        try {
            const response = await fetch(`${this.baseUrl}?action=set_default_card`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ card_id: cardId })
            });
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Erreur lors de la définition de la carte par défaut:', error);
            return { success: false, message: 'Erreur de connexion' };
        }
    }

    /**
     * Traite un paiement avec une carte spécifique
     */
    async processPayment(items, cardId = null) {
        try {
            const paymentData = {
                items: items,
                card_id: cardId
            };

            const response = await fetch(`${this.baseUrl}?action=process_payment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(paymentData)
            });
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Erreur lors du traitement du paiement:', error);
            return { success: false, message: 'Erreur de connexion' };
        }
    }

    /**
     * Génère le HTML pour afficher une carte
     */
    generateCardHTML(card) {
        const defaultBadge = card.is_default ? '<span class="default-badge">Par défaut</span>' : '';
        const defaultButton = !card.is_default ? 
            `<button class="card-action-btn default" onclick="paymentManager.setCardAsDefault(${card.id})" title="Définir par défaut">
                <i class="fa-solid fa-star"></i>
            </button>` : '';

        return `
            <div class="saved-card ${card.is_default ? 'default' : ''}" data-card-id="${card.id}">
                <div class="card-header">
                    <div class="card-info">
                        <h3>
                            ${this.escapeHtml(card.card_name)}
                            ${defaultBadge}
                        </h3>
                        <p><i class="fa-solid fa-credit-card"></i> Carte se terminant par ${card.card_last_four}</p>
                        <p><i class="fa-regular fa-calendar"></i> Expire le ${card.expiry_date}</p>
                    </div>
                    <div class="card-actions">
                        ${defaultButton}
                        <button class="card-action-btn delete" onclick="paymentManager.deleteCardWithConfirm(${card.id})" title="Supprimer">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Supprime une carte avec confirmation
     */
    async deleteCardWithConfirm(cardId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette carte ?')) {
            return;
        }

        const result = await this.deleteCard(cardId);
        if (result.success) {
            this.refreshCardsList();
        } else {
            alert(result.message || 'Erreur lors de la suppression de la carte');
        }
    }

    /**
     * Définit une carte comme carte par défaut avec feedback
     */
    async setCardAsDefault(cardId) {
        const result = await this.setDefaultCard(cardId);
        if (result.success) {
            this.refreshCardsList();
        } else {
            alert(result.message || 'Erreur lors de la définition de la carte par défaut');
        }
    }

    /**
     * Actualise la liste des cartes affichées
     */
    async refreshCardsList() {
        const cardsContainer = document.querySelector('.saved-cards-container');
        if (!cardsContainer) return;

        const result = await this.getUserCards();
        if (result.success && result.cards) {
            // Reconstituer le HTML des cartes
            let cardsHTML = '<h3 style="margin-bottom: 20px;">Mes cartes enregistrées</h3>';
            
            result.cards.forEach(card => {
                cardsHTML += this.generateCardHTML(card);
            });

            cardsHTML += `
                <button id="pay-with-selected-card" class="checkout-button" disabled>
                    <i class="fa-solid fa-check-circle"></i> Payer avec la carte sélectionnée
                </button>
                <button id="show-new-card-form" class="secondary-button">
                    <i class="fa-solid fa-plus-circle"></i> Ajouter une nouvelle carte
                </button>
            `;

            cardsContainer.innerHTML = cardsHTML;
            
            // Réattacher les événements
            this.attachCardEvents();
        }
    }

    /**
     * Attache les événements aux cartes
     */
    attachCardEvents() {
        const savedCards = document.querySelectorAll('.saved-card');
        const payButton = document.getElementById('pay-with-selected-card');

        savedCards.forEach(card => {
            card.addEventListener('click', (e) => {
                // Éviter de déclencher lors du clic sur les boutons d'action
                if (e.target.closest('.card-actions')) return;

                // Retirer la sélection des autres cartes
                savedCards.forEach(c => c.classList.remove('selected'));
                // Sélectionner la carte cliquée
                card.classList.add('selected');
                this.selectedCardId = parseInt(card.dataset.cardId);
                
                // Activer le bouton de paiement
                if (payButton) {
                    payButton.disabled = false;
                }
            });
        });

        // Attacher l'événement au bouton "Ajouter une nouvelle carte"
        const showNewCardBtn = document.getElementById('show-new-card-form');
        if (showNewCardBtn) {
            showNewCardBtn.addEventListener('click', () => {
                const newCardForm = document.getElementById('new-card-form');
                if (newCardForm) {
                    newCardForm.style.display = 'block';
                    showNewCardBtn.style.display = 'none';
                }
            });
        }
    }

    /**
     * Traite un paiement rapide avec la carte sélectionnée
     */
    async processQuickPayment(cartItems) {
        if (!this.selectedCardId) {
            alert('Veuillez sélectionner une carte');
            return false;
        }

        if (!confirm('Confirmez-vous le paiement avec cette carte ?')) {
            return false;
        }

        const result = await this.processPayment(cartItems, this.selectedCardId);
        
        if (result.success) {
            this.showTransactionSuccess();
            // Vider le panier
            localStorage.setItem('synapse-cart', JSON.stringify([]));
            // Rediriger après 3 secondes
            setTimeout(() => {
                window.location.href = "../Testing grounds/main.php";
            }, 3000);
            return true;
        } else {
            alert(result.message || "Erreur lors du traitement du paiement");
            return false;
        }
    }

    /**
     * Affiche un message de succès de transaction
     */
    showTransactionSuccess() {
        const existingMessage = document.querySelector('.transaction-success');
        if (existingMessage) return;

        const transactionMessage = document.createElement('div');
        transactionMessage.className = 'transaction-success';
        transactionMessage.textContent = "Transaction validée";
        transactionMessage.style.cssText = "color: #45cf91; font-weight: bold; font-size: 1.3em; margin: 20px 0; text-align:center;";
        
        const container = document.querySelector('.paiement-container');
        if (container) {
            container.appendChild(transactionMessage);
        }
    }

    /**
     * Valide les données d'une carte avant ajout
     */
    validateCardData(cardData) {
        const errors = [];

        // Validation du numéro de carte
        const cardNumber = cardData.card_number.replace(/\D/g, '');
        if (cardNumber.length !== 16) {
            errors.push('Le numéro de carte doit contenir 16 chiffres');
        }

        // Validation de la date d'expiration
        const expiryRegex = /^(0[1-9]|1[0-2])\/\d{2}$/;
        if (!expiryRegex.test(cardData.expiry_date)) {
            errors.push('La date d\'expiration doit être au format MM/AA');
        }

        // Validation du CVV
        if (cardData.cvv && cardData.cvv.replace(/\D/g, '').length !== 3) {
            errors.push('Le CVV doit contenir 3 chiffres');
        }

        // Validation du nom de la carte
        if (cardData.card_name && cardData.card_name.length > 100) {
            errors.push('Le nom de la carte ne peut pas dépasser 100 caractères');
        }

        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }

    /**
     * Ajoute une nouvelle carte avec validation
     */
    async addNewCard(formData) {
        const cardData = {
            card_name: formData.get('card_name') || 'Ma carte',
            card_number: formData.get('card_number'),
            expiry_date: formData.get('expiry_date'),
            cvv: formData.get('cvv'),
            set_as_default: formData.get('set_as_default') === '1'
        };

        // Valider les données
        const validation = this.validateCardData(cardData);
        if (!validation.isValid) {
            alert('Erreurs de validation:\n' + validation.errors.join('\n'));
            return false;
        }

        const result = await this.addCard(cardData);
        
        if (result.success) {
            // Actualiser la liste des cartes
            await this.refreshCardsList();
            
            // Masquer le formulaire d'ajout
            const newCardForm = document.getElementById('new-card-form');
            if (newCardForm) {
                newCardForm.style.display = 'none';
                // Réinitialiser le formulaire
                const form = newCardForm.querySelector('form');
                if (form) form.reset();
            }
            
            // Afficher le bouton "Ajouter une carte"
            const showNewCardBtn = document.getElementById('show-new-card-form');
            if (showNewCardBtn) {
                showNewCardBtn.style.display = 'block';
            }
            
            alert('Carte ajoutée avec succès !');
            return true;
        } else {
            alert(result.message || 'Erreur lors de l\'ajout de la carte');
            return false;
        }
    }

    /**
     * Initialise le gestionnaire de cartes
     */
    init() {
        // Attacher les événements aux cartes existantes
        this.attachCardEvents();

        // Gérer le paiement avec carte sélectionnée
        const payWithSelectedBtn = document.getElementById('pay-with-selected-card');
        if (payWithSelectedBtn) {
            payWithSelectedBtn.addEventListener('click', () => {
                // Récupérer les données du panier depuis le HTML ou localStorage
                const cartData = this.getCartData();
                if (cartData && cartData.length > 0) {
                    this.processQuickPayment(cartData);
                } else {
                    alert('Panier vide');
                }
            });
        }

        // Gérer l'ajout de nouvelles cartes
        const newCardForm = document.querySelector('#new-card-form form');
        if (newCardForm) {
            newCardForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                if (!confirm('Confirmez-vous l\'ajout de cette carte ?')) {
                    return;
                }

                const formData = new FormData(newCardForm);
                await this.addNewCard(formData);
            });
        }
    }

    /**
     * Récupère les données du panier
     */
    getCartData() {
        // Essayer de récupérer depuis localStorage
        try {
            const cartData = localStorage.getItem('synapse-cart');
            if (cartData) {
                return JSON.parse(cartData);
            }
        } catch (e) {
            console.warn('Erreur lors de la lecture du panier depuis localStorage:', e);
        }

        // Fallback: essayer de récupérer depuis les données de session/POST
        const panierInput = document.querySelector('input[name="panier_json"]');
        if (panierInput && panierInput.value) {
            try {
                return JSON.parse(panierInput.value);
            } catch (e) {
                console.warn('Erreur lors de la lecture du panier depuis le formulaire:', e);
            }
        }

        return [];
    }

    /**
     * Échappe les caractères HTML pour éviter les injections XSS
     */
    escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    /**
     * Formate un numéro de carte pour l'affichage
     */
    formatCardNumber(cardNumber) {
        return cardNumber.replace(/\D/g, '').replace(/(.{4})/g, '$1 ').trim();
    }

    /**
     * Formate une date d'expiration
     */
    formatExpiryDate(value) {
        const val = value.replace(/\D/g, '').slice(0, 4);
        if (val.length > 2) {
            return val.slice(0, 2) + '/' + val.slice(2);
        }
        return val;
    }
}

// Initialiser le gestionnaire de cartes quand le DOM est prêt
document.addEventListener('DOMContentLoaded', function() {
    // Créer une instance globale du gestionnaire
    window.paymentManager = new PaymentCardsManager();
    
    // Initialiser le gestionnaire
    paymentManager.init();
});

// Fonctions globales pour compatibilité avec l'ancien code
function deleteCard(cardId) {
    if (window.paymentManager) {
        window.paymentManager.deleteCardWithConfirm(cardId);
    }
}

function setDefaultCard(cardId) {
    if (window.paymentManager) {
        window.paymentManager.setCardAsDefault(cardId);
    }
}

// Export pour utilisation en module (optionnel)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PaymentCardsManager;
}