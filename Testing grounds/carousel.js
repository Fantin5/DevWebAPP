/* Fixed Carousel Script - Ensures Proper Timing & Transitions */
document.addEventListener('DOMContentLoaded', function() {
    // Get carousel elements
    const carousel = document.querySelector('.carrousel');
    if (!carousel) return; // Exit if no carousel is found
    
    const carouselImages = document.querySelector('.carrousel-images');
    const slides = document.querySelectorAll('.carrousel-slide');
    const indicators = document.querySelectorAll('.carrousel-indicator');
    const prevButton = document.querySelector('.carrousel-button.prev');
    const nextButton = document.querySelector('.carrousel-button.next');
    const progressBar = document.querySelector('.carrousel-progress');
    
    // Set variables
    let currentIndex = 0;
    const totalSlides = slides.length;
    let autoplayTimer;
    const slideDuration = 7000; // 7 seconds per slide
    
    // Initialize carousel
    function initCarousel() {
        // Make first slide active
        activateSlide(0);
        
        // Start autoplay
        startAutoplay();
        
        // Add event listeners
        setupEventListeners();
    }
    
    // Function to activate a slide
    function activateSlide(index) {
        // Update all slides
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === index);
        });
        
        // Update indicators
        indicators.forEach((indicator, i) => {
            indicator.classList.toggle('active', i === index);
        });
        
        // Move to the right position
        carouselImages.style.transform = `translateX(-${index * 100}%)`;
        
        // Update current index
        currentIndex = index;
        
        // Reset and start progress bar
        resetProgressBar();
    }
    
    // Function to go to the next slide
    function nextSlide() {
        let newIndex = currentIndex + 1;
        if (newIndex >= totalSlides) {
            newIndex = 0;
        }
        activateSlide(newIndex);
    }
    
    // Function to go to the previous slide
    function prevSlide() {
        let newIndex = currentIndex - 1;
        if (newIndex < 0) {
            newIndex = totalSlides - 1;
        }
        activateSlide(newIndex);
    }
    
    // Start autoplay function
    function startAutoplay() {
        // Clear any existing timer
        clearInterval(autoplayTimer);
        
        // Start progress bar animation
        startProgressBar();
        
        // Set new timer
        autoplayTimer = setInterval(function() {
            nextSlide();
        }, slideDuration);
    }
    
    // Stop autoplay function
    function stopAutoplay() {
        clearInterval(autoplayTimer);
        stopProgressBar();
    }
    
    // Start progress bar animation
    function startProgressBar() {
        if (!progressBar) return;
        
        // Reset to 0 first
        progressBar.style.transition = 'none';
        progressBar.style.width = '0%';
        
        // Force reflow
        void progressBar.offsetWidth;
        
        // Start animation
        progressBar.style.transition = `width ${slideDuration}ms linear`;
        progressBar.style.width = '100%';
    }
    
    // Reset progress bar
    function resetProgressBar() {
        if (!progressBar) return;
        
        // Stop current animation
        stopProgressBar();
        
        // Start new animation
        startProgressBar();
    }
    
    // Stop progress bar animation
    function stopProgressBar() {
        if (!progressBar) return;
        progressBar.style.transition = 'none';
        progressBar.style.width = '0%';
    }
    
    // Set up event listeners
    function setupEventListeners() {
        // Navigation buttons
        if (prevButton) {
            prevButton.addEventListener('click', function() {
                prevSlide();
                stopAutoplay();
                startAutoplay();
            });
        }
        
        if (nextButton) {
            nextButton.addEventListener('click', function() {
                nextSlide();
                stopAutoplay();
                startAutoplay();
            });
        }
        
        // Indicators
        indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', function() {
                activateSlide(index);
                stopAutoplay();
                startAutoplay();
            });
        });
        
        // Pause on hover
        carousel.addEventListener('mouseenter', stopAutoplay);
        carousel.addEventListener('mouseleave', startAutoplay);
        
        // Handle swipe gestures for mobile
        let touchStartX = 0;
        
        carousel.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoplay();
        });
        
        carousel.addEventListener('touchend', function(e) {
            const touchEndX = e.changedTouches[0].screenX;
            const diff = touchEndX - touchStartX;
            
            // If the swipe distance is more than 50px, change slide
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    prevSlide(); // Swipe right goes to previous
                } else {
                    nextSlide(); // Swipe left goes to next
                }
            }
            
            startAutoplay();
        });
        
        // Add keyboard navigation
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
    }
    
    // Initialize the carousel
    initCarousel();
});