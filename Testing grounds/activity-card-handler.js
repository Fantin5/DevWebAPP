document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner toutes les cartes d'activités (cards, featured-cards, et slider-cards)
    const activityCards = document.querySelectorAll('.card, .featured-card, .slider-card');
    
    // Ajouter un gestionnaire d'événements pour chaque carte
    activityCards.forEach(card => {
        // Obtenir l'ID de l'activité
        const activityId = card.getAttribute('data-id');
        
        if (activityId) {
            // Rendre toute la carte cliquable
            card.style.cursor = 'pointer';
            
            // Ajouter un indicateur visuel au survol
            card.addEventListener('mouseenter', function() {
                card.style.boxShadow = '0 15px 30px rgba(0, 0, 0, 0.2)';
            });
            
            card.addEventListener('mouseleave', function() {
                card.style.boxShadow = '';
            });
            
            // Ajouter l'événement de clic
            card.addEventListener('click', function(e) {
                // Ne pas rediriger si l'utilisateur a cliqué sur le bouton "Ajouter au panier"
                if (e.target.closest('.add-to-cart-button') || e.target.closest('.panier-item-remove')) {
                    e.stopPropagation(); // Arrêter la propagation de l'événement
                    return;
                }
                
                // Rediriger vers la page détaillée de l'activité
                window.location.href = 'activite.php?id=' + activityId;
            });
        }
    });
    
    // Ajout d'un gestionnaire d'événements spécifique pour les boutons d'ajout au panier
    document.querySelectorAll('.add-to-cart-button').forEach(button => {
        button.addEventListener('click', function(e) {
            // Empêcher la propagation vers la carte parent
            e.stopPropagation();
        });
    });
});