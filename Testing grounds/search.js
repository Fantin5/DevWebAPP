document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('search-input');
    const activitiesContainer = document.getElementById('activities-container');
    const activityCards = document.querySelectorAll('.card');
    
    // Fonction de recherche
    function searchActivities(searchTerm) {
        searchTerm = searchTerm.toLowerCase().trim();
        
        // Si le champ de recherche est vide, on affiche toutes les activités
        if (searchTerm === '') {
            activityCards.forEach(card => {
                card.style.display = 'flex';
            });
            return;
        }
        // hey
        
        // Sinon, on filtre les activités
        activityCards.forEach(card => {
            const title = card.querySelector('h3').textContent.toLowerCase();
            const tags = Array.from(card.querySelectorAll('.tag span')).map(tag => tag.textContent.toLowerCase());
            const period = card.querySelector('.period') ? card.querySelector('.period').textContent.toLowerCase() : '';
            
            // Si le titre, un tag ou la période contient le terme recherché, on affiche la carte
            if (title.includes(searchTerm) || tags.some(tag => tag.includes(searchTerm)) || period.includes(searchTerm)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Vérifier s'il y a des résultats
        const visibleCards = document.querySelectorAll('.card[style="display: flex;"]');
        if (visibleCards.length === 0) {
            // Si aucun résultat, on affiche un message
            if (!document.querySelector('.no-results')) {
                const noResults = document.createElement('p');
                noResults.classList.add('no-results');
                noResults.textContent = 'Aucune activité ne correspond à votre recherche.';
                activitiesContainer.appendChild(noResults);
            }
        } else {
            // Sinon, on supprime le message s'il existe
            const noResults = document.querySelector('.no-results');
            if (noResults) {
                noResults.remove();
            }
        }
    }
    
    // Événement de recherche
    searchInput.addEventListener('input', function() {
        searchActivities(this.value);
    });
    
    // Vérification des paramètres d'URL pour afficher un message de succès
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === '1') {
        // Créer une notification de succès
        const notification = document.createElement('div');
        notification.classList.add('notification', 'success');
        notification.innerHTML = '<i class="fa-solid fa-circle-check"></i> Votre activité a été créée avec succès !';
        
        // Ajouter la notification en haut de la page
        document.body.insertBefore(notification, document.body.firstChild);
        
        // Faire disparaître la notification après 5 secondes
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                notification.remove();
            }, 500);
        }, 5000);
        
        // Supprimer le paramètre de l'URL
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});