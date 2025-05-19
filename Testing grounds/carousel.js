/* Fixed Carousel Script - Corrected Timing Issues */
document.addEventListener('DOMContentLoaded', function () {
    // Wait a bit to ensure all elements are fully loaded
    setTimeout(() => {
        initializeCarousel();
    }, 200);

    function initializeCarousel() {
        const carousel = document.querySelector('.carrousel');
        const carouselImages = document.querySelector('.carrousel-images');
        const slides = document.querySelectorAll('.carrousel-slide');
        const indicators = document.querySelectorAll('.carrousel-indicator');
        const prevButton = document.querySelector('.carrousel-button.prev');
        const nextButton = document.querySelector('.carrousel-button.next');
        const progressBar = document.querySelector('.carrousel-progress');

        // Variables
        let currentIndex = 0;
        const totalSlides = slides.length;
        let autoplayInterval;
        const autoplayDuration = 7000; // 7 seconds for each slide
        let isTransitioning = false;

        // First activate the initial slide properly
        activateSlide(0);

        // Function to show a specific slide
        function showSlide(index) {
            if (isTransitioning) return;
            isTransitioning = true;

            // Reset progress bar immediately
            resetProgressBar();

            // Handle edge cases
            if (index < 0) {
                index = totalSlides - 1;
            } else if (index >= totalSlides) {
                index = 0;
            }

            // Update current index
            currentIndex = index;
            
            // Deactivate all slides first
            slides.forEach((slide) => {
                slide.classList.remove('active');
            });
            
            // Update indicators
            indicators.forEach((indicator, i) => {
                indicator.classList.toggle('active', i === currentIndex);
            });

            // Update the transform to move to the correct slide
            const translateValue = -currentIndex * 100;
            carouselImages.style.transform = `translateX(${translateValue}%)`;
            
            // Activate current slide with a slight delay to let the transform happen first
            setTimeout(() => {
                slides[currentIndex].classList.add('active');
                startProgressBar();
                isTransitioning = false;
            }, 100);
        }

        // Initialize the active slide
        function activateSlide(index) {
            // Set active class on the slide
            slides[index].classList.add('active');
            // Update indicators
            indicators[index].classList.add('active');
            // Start progress bar
            startProgressBar();
        }

        // Progress bar animation
        function startProgressBar() {
            // Make sure it's reset first
            progressBar.style.transition = 'none';
            progressBar.style.width = '0%';
            
            // Force browser reflow
            void progressBar.offsetWidth;
            
            // Now start the animation
            progressBar.style.transition = `width ${autoplayDuration}ms linear`;
            progressBar.style.width = '100%';
        }

        // Reset progress bar
        function resetProgressBar() {
            progressBar.style.transition = 'none';
            progressBar.style.width = '0%';
        }

        // Navigate to next slide
        function nextSlide() {
            showSlide(currentIndex + 1);
        }

        // Navigate to previous slide
        function prevSlide() {
            showSlide(currentIndex - 1);
        }

        // Start the autoplay
        function startAutoplay() {
            stopAutoplay(); // Clear any existing interval
            autoplayInterval = setInterval(nextSlide, autoplayDuration);
        }

        // Stop the autoplay
        function stopAutoplay() {
            if (autoplayInterval) {
                clearInterval(autoplayInterval);
                autoplayInterval = null;
            }
        }

        // Event listeners for navigation buttons
        prevButton.addEventListener('click', function() {
            prevSlide();
            stopAutoplay();
            startAutoplay();
        });

        nextButton.addEventListener('click', function() {
            nextSlide();
            stopAutoplay();
            startAutoplay();
        });

        // Event listeners for indicators
        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', function() {
                showSlide(index);
                stopAutoplay();
                startAutoplay();
            });
        });

        // Pause on hover
        carousel.addEventListener('mouseenter', stopAutoplay);
        carousel.addEventListener('mouseleave', function() {
            startAutoplay();
        });

        // Touch events for swipe
        let touchStartX = 0;
        let touchEndX = 0;

        carousel.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoplay();
        });

        carousel.addEventListener('touchend', function(e) {
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

        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
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

        // Start autoplay 
        startAutoplay();
    }
});