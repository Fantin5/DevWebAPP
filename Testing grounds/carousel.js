document.addEventListener('DOMContentLoaded', function() {
    const carousel = document.querySelector('.carrousel');
    const carouselImages = document.querySelector('.carrousel-images');
    const images = carouselImages.querySelectorAll('img');
    const indicators = document.querySelectorAll('.carrousel-indicator');
    const prevButton = document.querySelector('.carrousel-button.prev');
    const nextButton = document.querySelector('.carrousel-button.next');
    
    let currentIndex = 0;
    const totalImages = images.length;
    let autoplayInterval;
    
    // Fonction pour afficher une image
    function showImage(index) {
        // Mettre à jour l'index courant
        currentIndex = index;
        
        // Gérer les débordements
        if (currentIndex < 0) {
            currentIndex = totalImages - 1;
        } else if (currentIndex >= totalImages) {
            currentIndex = 0;
        }
        
        // Calculer la position de déplacement
        const translateValue = -currentIndex * 100;
        carouselImages.style.transform = `translateX(${translateValue}%)`;
        
        // Mettre à jour les indicateurs
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === currentIndex);
        });
    }
    
    // Fonction pour aller à l'image suivante
    function nextImage() {
        showImage(currentIndex + 1);
    }
    
    // Fonction pour aller à l'image précédente
    function prevImage() {
        showImage(currentIndex - 1);
    }
    
    // Fonction pour démarrer le défilement automatique
    function startAutoplay() {
        stopAutoplay(); // Arrêter l'autoplay existant avant d'en créer un nouveau
        autoplayInterval = setInterval(nextImage, 5000); // Changer d'image toutes les 5 secondes
    }
    
    // Fonction pour arrêter le défilement automatique
    function stopAutoplay() {
        if (autoplayInterval) {
            clearInterval(autoplayInterval);
        }
    }
    
    // Écouteurs d'événements pour les boutons
    nextButton.addEventListener('click', function() {
        nextImage();
        stopAutoplay();
        startAutoplay(); // Redémarrer l'autoplay après un clic manuel
    });
    
    prevButton.addEventListener('click', function() {
        prevImage();
        stopAutoplay();
        startAutoplay(); // Redémarrer l'autoplay après un clic manuel
    });
    
    // Écouteurs d'événements pour les indicateurs
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', function() {
            showImage(index);
            stopAutoplay();
            startAutoplay(); // Redémarrer l'autoplay après un clic manuel
        });
    });
    
    // Arrêter l'autoplay au survol du carrousel
    carousel.addEventListener('mouseenter', stopAutoplay);
    
    // Redémarrer l'autoplay quand la souris quitte le carrousel
    carousel.addEventListener('mouseleave', startAutoplay);
    
    // Démarrer l'autoplay au chargement de la page
    startAutoplay();
    
    // Gérer le swipe sur mobile
    let touchStartX = 0;
    let touchEndX = 0;
    
    carousel.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
        stopAutoplay();
    }, false);
    
    carousel.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
        startAutoplay();
    }, false);
    
    function handleSwipe() {
        if (touchEndX < touchStartX) {
            // Swipe vers la gauche, on va à la prochaine image
            nextImage();
        } else if (touchEndX > touchStartX) {
            // Swipe vers la droite, on va à l'image précédente
            prevImage();
        }
    }
});
// hey