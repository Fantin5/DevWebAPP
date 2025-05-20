// faq.js - Adds interactivity to the FAQ page
document.addEventListener('DOMContentLoaded', function() {
    // Get all FAQ items
    const faqItems = document.querySelectorAll('.faq-item');
    
    // Add number spans to questions and set up click events
    faqItems.forEach((item, index) => {
        const question = item.querySelector('.question');
        const questionText = question.textContent;
        
        // Remove the numbering from the text and add it as a styled span
        const cleanText = questionText.replace(/^\d+\.\s*/, '');
        const number = index + 1;
        
        question.innerHTML = `<span>${number}.</span> ${cleanText}`;
        
        // Add click event to toggle accordion
        question.addEventListener('click', () => {
            // Close all other items
            faqItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });
            
            // Toggle current item
            item.classList.toggle('active');
            
            // Add smooth scrolling if item is not visible after opening
            if (item.classList.contains('active')) {
                const itemRect = item.getBoundingClientRect();
                const isVisible = (
                    itemRect.top >= 0 &&
                    itemRect.bottom <= window.innerHeight
                );
                
                if (!isVisible) {
                    const offset = 150; // Adjust scroll position to account for fixed header
                    const targetPosition = itemRect.top + window.pageYOffset - offset;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });
    
    // Open the first FAQ item by default
    if (faqItems.length > 0) {
        faqItems[0].classList.add('active');
    }
    
    // Add icons to form labels
    const subjectLabel = document.querySelector('label[for="subject"]');
    if (subjectLabel) {
        subjectLabel.innerHTML = '<i class="fas fa-tag"></i> ' + subjectLabel.textContent;
    }
    
    const messageLabel = document.querySelector('label[for="message"]');
    if (messageLabel) {
        messageLabel.innerHTML = '<i class="fas fa-comment-alt"></i> ' + messageLabel.textContent;
    }
    
    // Add icon to submit button
    const submitBtn = document.querySelector('.submit-btn');
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> ' + submitBtn.textContent;
    }
    
    // Add icon to login link
    const loginLink = document.querySelector('.login-link');
    if (loginLink) {
        loginLink.innerHTML = '<i class="fas fa-sign-in-alt"></i> ' + loginLink.textContent;
    }
    
    // Add smooth fade-in animation to the page
    document.body.classList.add('loaded');
});