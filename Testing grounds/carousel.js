document.addEventListener('DOMContentLoaded', function () {
    const carousel = document.querySelector('.carrousel');
    const carouselImages = document.querySelector('.carrousel-images');
    const slides = carouselImages.querySelectorAll('.carrousel-slide');
    const indicators = document.querySelectorAll('.carrousel-indicator');
    const prevButton = document.querySelector('.carrousel-button.prev');
    const nextButton = document.querySelector('.carrousel-button.next');
    const progressBar = document.querySelector('.carrousel-progress');

    let currentIndex = 0;
    const totalSlides = slides.length;
    let autoplayInterval;
    const autoplayDuration = 5000; // 5 seconds for each slide

    // Function to update active slide and progress bar
    function showSlide(index) {
        if (index < 0) {
            index = totalSlides - 1;
        } else if (index >= totalSlides) {
            index = 0;
        }

        currentIndex = index;
        const translateValue = -currentIndex * 100;
        carouselImages.style.transform = `translateX(${translateValue}%)`;

        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === currentIndex);
        });

        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === currentIndex);
        });

        resetProgressBar();
    }

    // Initialize carousel
    function initCarousel() {
        slides.forEach((slide, i) => {
            slide.style.left = `${i * 100}%`;
            if (i === 0) {
                slide.classList.add('active');
            }
        });

        startAutoplay();
    }

    // Start progress bar animation
    function startProgressBar() {
        progressBar.style.width = '0%';
        progressBar.style.transition = `width ${autoplayDuration}ms linear`;
        progressBar.style.width = '100%';
    }

    // Reset progress bar
    function resetProgressBar() {
        progressBar.style.transition = 'none';
        progressBar.style.width = '0%';
        void progressBar.offsetWidth; // Force browser reflow
        startProgressBar();
    }

    // Navigate to next slide
    function nextSlide() {
        showSlide(currentIndex + 1);
    }

    // Navigate to previous slide
    function prevSlide() {
        showSlide(currentIndex - 1);
    }

    // Start autoplay
    function startAutoplay() {
        stopAutoplay();
        autoplayInterval = setInterval(nextSlide, autoplayDuration);
        startProgressBar();
    }

    // Stop autoplay
    function stopAutoplay() {
        if (autoplayInterval) {
            clearInterval(autoplayInterval);
        }
    }

    // Event listeners
    nextButton.addEventListener('click', function () {
        nextSlide();
        stopAutoplay();
        startAutoplay();
    });

    prevButton.addEventListener('click', function () {
        prevSlide();
        stopAutoplay();
        startAutoplay();
    });

    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', function () {
            showSlide(index);
            stopAutoplay();
            startAutoplay();
        });
    });

    carousel.addEventListener('mouseenter', stopAutoplay);
    carousel.addEventListener('mouseleave', startAutoplay);

    // Handle swipe gestures for mobile
    let touchStartX = 0;
    let touchEndX = 0;

    carousel.addEventListener('touchstart', function (e) {
        touchStartX = e.changedTouches[0].screenX;
        stopAutoplay();
    });

    carousel.addEventListener('touchend', function (e) {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
        startAutoplay();
    });

    function handleSwipe() {
        const swipeThreshold = 50; // Minimum distance required for a swipe
        const swipeDistance = touchEndX - touchStartX;

        if (Math.abs(swipeDistance) >= swipeThreshold) {
            if (swipeDistance < 0) {
                nextSlide();
            } else {
                prevSlide();
            }
        }
    }


    .





    
    // Add keyboard navigation
    document.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft') {
            prevSlide();
            stopAutoplay();
            startAutoplay();
        } else if (e.key === 'ArrowRight') {
            nextSlide();
            stopAutoplay();
            startAutoplay();
        }
    });

    // Initialize carousel
    initCarousel();
});
/* Updated Carousel Caption Styling */
