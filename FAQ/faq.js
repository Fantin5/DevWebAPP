// Enhanced faq.js - Simplified and more reliable search functionality
document.addEventListener('DOMContentLoaded', function() {
    // Get all FAQ items
    const faqItems = document.querySelectorAll('.faq-item');
    let allFaqData = []; // Store all FAQ data for search
    
    // Store original content for each FAQ item
    faqItems.forEach((item, index) => {
        const question = item.querySelector('.question');
        const answer = item.querySelector('.answer');
        const questionText = question.textContent;
        
        // Store original HTML content
        const originalQuestionHTML = question.innerHTML;
        const originalAnswerHTML = answer ? answer.innerHTML : '';
        
        // Store FAQ data for search functionality
        const faqData = {
            element: item,
            questionText: questionText.toLowerCase(),
            answerText: answer ? answer.textContent.toLowerCase() : '',
            originalIndex: index,
            originalQuestionHTML: originalQuestionHTML,
            originalAnswerHTML: originalAnswerHTML
        };
        allFaqData.push(faqData);
        
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
                    const offset = 150;
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
    
    // Search functionality
    const searchInput = document.getElementById('faq-search');
    
    if (searchInput) {
        // Real-time search as user types
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            
            if (searchTerm === '' || searchTerm.length < 2) {
                // Show all FAQ items when search is empty or too short
                showAllFaqItems();
                hideSearchResults();
                return;
            }
            
            // Perform search
            const results = searchFaqItems(searchTerm.toLowerCase());
            displaySearchResults(results, searchTerm);
        });
        
        // Clear search button functionality
        const clearSearchBtn = document.getElementById('clear-search');
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                showAllFaqItems();
                hideSearchResults();
                searchInput.focus();
            });
        }
    }
    
    // Search function
    function searchFaqItems(searchTerm) {
        const lowerSearchTerm = searchTerm.toLowerCase();
        return allFaqData.filter(faq => {
            return faq.questionText.includes(lowerSearchTerm) || 
                   faq.answerText.includes(lowerSearchTerm);
        });
    }
    
    // Display search results
    function displaySearchResults(results, searchTerm) {
        // Hide all FAQ items first
        faqItems.forEach(item => {
            item.style.display = 'none';
        });
        
        if (results.length === 0) {
            showNoResults(searchTerm);
            return;
        }
        
        // Show matching FAQ items without highlighting
        results.forEach((result, displayIndex) => {
            result.element.style.display = 'block';
            
            // Update the numbering for search results without highlighting
            const question = result.element.querySelector('.question');
            const cleanText = question.textContent.replace(/^\d+\.\s*/, '');
            question.innerHTML = `<span>${displayIndex + 1}.</span> ${cleanText}`;
        });
        
        hideNoResults();
        updateSearchCounter(results.length, allFaqData.length);
    }
    
    // Show all FAQ items (restore original state)
    function showAllFaqItems() {
        allFaqData.forEach((faq, index) => {
            faq.element.style.display = 'block';
            
            // Restore original numbering and content without highlighting
            const question = faq.element.querySelector('.question');
            const cleanText = question.textContent.replace(/^\d+\.\s*/, '');
            question.innerHTML = `<span>${index + 1}.</span> ${cleanText}`;
            
            // Restore original answer content
            const answer = faq.element.querySelector('.answer');
            if (answer) {
                answer.innerHTML = faq.originalAnswerHTML;
            }
        });
        
        hideSearchCounter();
    }
    
    // Show no results message
    function showNoResults(searchTerm) {
        const noResultsMessage = document.getElementById('no-results');
        if (noResultsMessage) {
            noResultsMessage.style.display = 'block';
            const searchTermSpan = noResultsMessage.querySelector('.search-term');
            if (searchTermSpan) {
                searchTermSpan.textContent = searchTerm;
            }
        }
        hideSearchCounter();
    }
    
    // Hide no results message
    function hideNoResults() {
        const noResultsMessage = document.getElementById('no-results');
        if (noResultsMessage) {
            noResultsMessage.style.display = 'none';
        }
    }
    
    // Hide search results section
    function hideSearchResults() {
        const searchResults = document.getElementById('search-results');
        if (searchResults) {
            searchResults.style.display = 'none';
        }
    }
    
    // Update search counter
    function updateSearchCounter(resultCount, totalCount) {
        const counter = document.getElementById('search-counter');
        if (counter) {
            counter.textContent = `Affichage de ${resultCount} sur ${totalCount} questions`;
            counter.style.display = 'block';
        }
    }
    
    // Hide search counter
    function hideSearchCounter() {
        const counter = document.getElementById('search-counter');
        if (counter) {
            counter.style.display = 'none';
        }
    }
    
    // Escape special regex characters
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    // Add icons to form labels (existing functionality)
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
// cvq