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
                if (['exterieur', 'gratuit'].includes(tag)) {
                    tagClass = 'accent';
                } else if (['interieur'].includes(tag)) {
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
                <h3>Récapitulatif</h3>
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
                <button class="checkout-button">Procéder au paiement</button>
            </div>
        `;
        
        panierContent.innerHTML = cartHTML;
        
        // Ajouter les événements pour supprimer des éléments du panier
        document.querySelectorAll('.panier-item-remove').forEach(button => {
            button.addEventListener('click', function() {
                const index = parseInt(this.getAttribute('data-index'));
                removeFromCart(index);
            });
        });
        
        // Événement pour le bouton de paiement (pour l'instant, juste une alerte)
        const paiementForm = document.createElement("form");
        paiementForm.action = "../Paiement/paiement.php";
        paiementForm.method = "post";
        paiementForm.style.display = "inline";

        const panierInput = document.createElement("input");
        panierInput.type = "hidden";
        panierInput.name = "panier_json";
        panierInput.value = JSON.stringify(cartItems);

        const boutonPaiement = document.createElement("button");
        boutonPaiement.type = "submit";
        boutonPaiement.className = "checkout-button";
        boutonPaiement.textContent = "Procéder au paiement";

        paiementForm.appendChild(panierInput);
        paiementForm.appendChild(boutonPaiement);

        // Ajoute le formulaire à la place du bouton
        const summaryDiv = panierContent.querySelector('.panier-summary');
        if (summaryDiv) {

            summaryDiv.appendChild(paiementForm);
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
    
    // Fonction pour formater le nom du tag (remplacer les underscores par des espaces et mettre en majuscule la première lettre)
    function formatTagName(tag) {
        return tag.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }
});