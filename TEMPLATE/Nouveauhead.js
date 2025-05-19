document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le panier s'il n'existe pas déjà
    if (!localStorage.getItem('synapse-cart')) {
        localStorage.setItem('synapse-cart', JSON.stringify([]));
    }
    
    // Mettre à jour le compteur du panier
    updateCartCount();
    
    // Fonction pour mettre à jour le compteur du panier
    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
        const cartCount = document.getElementById('panier-count');
        if (cartCount) {
            cartCount.textContent = cart.length;
        }
    }
    
    // Gestion du menu déroulant
    const profileDropdown = document.querySelector('.profile-dropdown');
    const profileButton = document.querySelector('.connexion-profil');
    
    if (profileButton && profileDropdown) {
        // Ajouter un écouteur d'événement de clic au bouton
        profileButton.addEventListener('click', function(e) {
            e.preventDefault(); // Empêcher la navigation si le href est '#'
            
            // Basculer la classe 'show' pour ouvrir/fermer le menu
            profileDropdown.classList.toggle('show');
            
            // Marquer l'événement comme traité pour éviter les conflits
            e.stopPropagation();
        });
        
        // Fermer le menu si on clique ailleurs sur la page
        document.addEventListener('click', function(e) {
            // Vérifier si le clic est en dehors du menu déroulant
            if (!profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('show');
            }
        });
        
        // Empêcher la fermeture du menu quand on clique sur son contenu
        const dropdownContent = document.querySelector('.dropdown-content');
        if (dropdownContent) {
            dropdownContent.addEventListener('click', function(e) {
                // Ne pas propager les clics dans le contenu du menu
                // (sauf si c'est un lien, qui devrait fonctionner normalement)
                if (e.target.tagName !== 'A') {
                    e.stopPropagation();
                }
            });
        }
    }
    
    // Change le style du header lors du défilement
    window.addEventListener('scroll', function() {
        const header = document.querySelector('header');
        if (window.scrollY > 50) {
            header.style.padding = "10px 35px"; // Légèrement plus compact
            header.style.boxShadow = "0 2px 10px rgba(0, 0, 0, 0.3)"; // Ombre plus prononcée
        } else {
            header.style.padding = "15px 35px"; // Revient à la normale
            header.style.boxShadow = "0 2px 8px rgba(0, 0, 0, 0.2)"; // Ombre normale
        }
    });
});