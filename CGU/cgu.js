document.addEventListener('DOMContentLoaded', function() {
    // Get all navigation items
    const navLinks = document.querySelectorAll('.cgu-nav a');
    
    // Add click handler for smooth scrolling
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links
            navLinks.forEach(item => item.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Get the target section
            const targetId = this.getAttribute('href').substring(1);
            const targetSection = document.getElementById(targetId);
            
            if (targetSection) {
                // Calculate position accounting for fixed header
                const headerOffset = 120;
                const targetPosition = targetSection.getBoundingClientRect().top + 
                                      window.pageYOffset - headerOffset;
                
                // Smooth scroll to the target
                window.scrollTo({
                    top: targetPosition,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Add scroll spy functionality
    window.addEventListener('scroll', function() {
        const sections = document.querySelectorAll('.cgu-section');
        
        // Determine which section is currently visible
        let currentSectionId = '';
        const scrollPosition = window.scrollY + 150; // Adjust for header
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.offsetHeight;
            
            if (scrollPosition >= sectionTop && 
                scrollPosition < sectionTop + sectionHeight) {
                currentSectionId = section.id;
            }
        });
        
        // Update active state in navigation
        if (currentSectionId) {
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + currentSectionId) {
                    link.classList.add('active');
                }
            });
        }
    });
    
    // Set first nav item as active by default
    if (navLinks.length > 0) {
        navLinks[0].classList.add('active');
    }
    
    // Add animation to sections
    const sections = document.querySelectorAll('.cgu-section');
    sections.forEach((section, index) => {
        section.classList.add('fade-in');
        section.style.animationDelay = (index * 0.15) + 's';
    });
});