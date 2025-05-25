document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart if it doesn't exist
    if (!localStorage.getItem('synapse-cart')) {
        localStorage.setItem('synapse-cart', JSON.stringify([]));
    }
    
    // Update cart counter
    updateCartCount();
    
    // Cart counter update function
    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('synapse-cart')) || [];
        const cartCount = document.getElementById('panier-count');
        if (cartCount) {
            cartCount.textContent = cart.length;
        }
    }
    
    // Mobile menu toggle functionality
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mobileNavOverlay = document.querySelector('.mobile-nav-overlay');
    const body = document.body;
    
    if (mobileMenuToggle && mobileNavOverlay) {
        mobileMenuToggle.addEventListener('click', function() {
            mobileMenuToggle.classList.toggle('active');
            mobileNavOverlay.classList.toggle('active');
            body.classList.toggle('mobile-menu-open');
        });
        
        // Close mobile menu when clicking on overlay
        mobileNavOverlay.addEventListener('click', function(e) {
            if (e.target === mobileNavOverlay) {
                closeMobileMenu();
            }
        });
        
        // Close mobile menu when clicking on a link
        const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');
        mobileNavLinks.forEach(link => {
            link.addEventListener('click', function() {
                closeMobileMenu();
            });
        });
        
        function closeMobileMenu() {
            mobileMenuToggle.classList.remove('active');
            mobileNavOverlay.classList.remove('active');
            body.classList.remove('mobile-menu-open');
        }
        
        // Close mobile menu on window resize if window becomes large
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeMobileMenu();
            }
        });
    }
    
    // Profile dropdown functionality
    const profileDropdown = document.querySelector('.profile-dropdown');
    const profileButton = document.querySelector('.profile-button');
    
    if (profileButton && profileDropdown) {
        profileButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            profileDropdown.classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!profileDropdown.contains(e.target)) {
                profileDropdown.classList.remove('show');
            }
        });
        
        // Prevent dropdown from closing when clicking inside
        const dropdownContent = document.querySelector('.dropdown-content');
        if (dropdownContent) {
            dropdownContent.addEventListener('click', function(e) {
                if (e.target.tagName !== 'A') {
                    e.stopPropagation();
                }
            });
        }
    }
    
    // Header scroll effect
    let lastScrollTop = 0;
    const header = document.querySelector('header');
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // Scrolling down - hide header
            header.classList.add('header-hidden');
        } else {
            // Scrolling up - show header
            header.classList.remove('header-hidden');
        }
        
        // Add shadow when scrolled
        if (scrollTop > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
        
        lastScrollTop = scrollTop;
    });
    
    // Touch gestures for mobile
    let touchStartY = 0;
    let touchEndY = 0;
    
    document.addEventListener('touchstart', function(e) {
        touchStartY = e.changedTouches[0].screenY;
    });
    
    document.addEventListener('touchend', function(e) {
        touchEndY = e.changedTouches[0].screenY;
        handleSwipe();
    });
    
    function handleSwipe() {
        const swipeThreshold = 100;
        const diff = touchStartY - touchEndY;
        
        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                // Swipe up - hide header
                header.classList.add('header-hidden');
            } else {
                // Swipe down - show header
                header.classList.remove('header-hidden');
            }
        }
    }
    
    // Smooth transitions for navigation links
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        link.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Add active state to current page navigation
    const currentPath = window.location.pathname;
    navLinks.forEach(link => {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href').split('/').pop())) {
            link.classList.add('active');
        }
    });
});
// cvq