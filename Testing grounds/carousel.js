/* Enhanced Carousel Script */
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
    const autoplayDuration = 7000; // 7 seconds for each slide

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

        // Update class for slides - remove all active first
        slides.forEach((slide) => {
            slide.classList.remove('active');
        });
        
        // Add active class with a small delay to trigger animations
        setTimeout(() => {
            slides[currentIndex].classList.add('active');
        }, 50);

        // Update indicators
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === currentIndex);
        });

        resetProgressBar();
    }

    // Initialize carousel
    function initCarousel() {
        // Set initial positions for slides
        slides.forEach((slide, i) => {
            slide.style.left = `${i * 100}%`;
        });
        
        // Set first slide as active
        slides[0].classList.add('active');

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

    // Navigate to next slide with enhanced transitions
    function nextSlide() {
        showSlide(currentIndex + 1);
    }

    // Navigate to previous slide with enhanced transitions
    function prevSlide() {
        showSlide(currentIndex - 1);
    }

    // Start autoplay with enhanced transitions
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

    // Add hover effect to carousel
    carousel.addEventListener('mouseenter', function() {
        carousel.style.transform = 'scale(1.01)';
        stopAutoplay();
    });
    
    carousel.addEventListener('mouseleave', function() {
        carousel.style.transform = 'scale(1)';
        startAutoplay();
    });

    // Event listeners with hover effects
    nextButton.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-50%) scale(1.1)';
    });
    
    nextButton.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(-50%)';
    });
    
    nextButton.addEventListener('click', function () {
        this.style.transform = 'translateY(-50%) scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'translateY(-50%) scale(1.1)';
        }, 100);
        nextSlide();
        stopAutoplay();
        startAutoplay();
    });

    prevButton.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-50%) scale(1.1)';
    });
    
    prevButton.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(-50%)';
    });
    
    prevButton.addEventListener('click', function () {
        this.style.transform = 'translateY(-50%) scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'translateY(-50%) scale(1.1)';
        }, 100);
        prevSlide();
        stopAutoplay();
        startAutoplay();
    });

    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', function () {
            this.style.transform = 'scale(1)';
            setTimeout(() => {
                this.style.transform = 'scale(1.3)';
            }, 100);
            showSlide(index);
            stopAutoplay();
            startAutoplay();
        });
    });

    // Handle swipe gestures for mobile with enhanced transitions
    let touchStartX = 0;
    let touchEndX = 0;
    let touchStartTime = 0;
    let touchEndTime = 0;

    carousel.addEventListener('touchstart', function (e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartTime = new Date().getTime();
        stopAutoplay();
    });

    carousel.addEventListener('touchend', function (e) {
        touchEndX = e.changedTouches[0].screenX;
        touchEndTime = new Date().getTime();
        handleSwipe();
        startAutoplay();
    });

    function handleSwipe() {
        const swipeThreshold = 50; // Minimum distance required for a swipe
        const timeThreshold = 300; // Maximum time for a swipe to be considered a swipe (ms)
        const swipeDistance = touchEndX - touchStartX;
        const swipeTime = touchEndTime - touchStartTime;

        // Only consider it a swipe if it was done quickly
        if (swipeTime < timeThreshold && Math.abs(swipeDistance) >= swipeThreshold) {
            if (swipeDistance < 0) {
                nextSlide();
            } else {
                prevSlide();
            }
        }
    }

    // Add keyboard navigation with enhanced transitions
    document.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowLeft') {
            prevButton.style.transform = 'translateY(-50%) scale(0.95)';
            setTimeout(() => {
                prevButton.style.transform = 'translateY(-50%)';
            }, 100);
            prevSlide();
            stopAutoplay();
            startAutoplay();
        } else if (e.key === 'ArrowRight') {
            nextButton.style.transform = 'translateY(-50%) scale(0.95)';
            setTimeout(() => {
                nextButton.style.transform = 'translateY(-50%)';
            }, 100);
            nextSlide();
            stopAutoplay();
            startAutoplay();
        }
    });

    // Initialize carousel with enhanced animations
    initCarousel();
});