document.addEventListener('DOMContentLoaded', function() {
    // Sélectionner l'élément dropdown et le bouton de profil
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
});