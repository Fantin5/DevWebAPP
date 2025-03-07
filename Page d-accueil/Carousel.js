const carrouselImages = document.querySelector('.carrousel-images');
const images = document.querySelectorAll('.carrousel-images img');
const prevButton = document.querySelector('.carrousel-button.prev');
const nextButton = document.querySelector('.carrousel-button.next');
const indicators = document.querySelectorAll('.carrousel-indicator');

let currentIndex = 0;
const totalImages = images.length;

// Fonction pour mettre à jour le carrousel
function updateCarrousel() {
    carrouselImages.style.transform = `translateX(${-currentIndex * 100}%)`;
    indicators.forEach((indicator, index) => {
        indicator.classList.toggle('active', index === currentIndex);
    });
}

// Bouton suivant
nextButton.addEventListener('click', () => {
    currentIndex = (currentIndex + 1) % totalImages;
    updateCarrousel();
});

// Bouton précédent
prevButton.addEventListener('click', () => {
    currentIndex = (currentIndex - 1 + totalImages) % totalImages;
    updateCarrousel();
});

// Indicateurs de position
indicators.forEach((indicator, index) => {
    indicator.addEventListener('click', () => {
        currentIndex = index;
        updateCarrousel();
    });
});

// Défilement automatique
setInterval(() => {
    currentIndex = (currentIndex + 1) % totalImages;
    updateCarrousel();
}, 3000); // Change d'image toutes les 3 secondes

