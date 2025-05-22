document.addEventListener('DOMContentLoaded', function() {
    let tagDefinitions = {};
    
    async function initializePage() {
        try {
            const response = await fetch('get_tags.php');
            tagDefinitions = await response.json();
            initializeTagDisplay();
        } catch (error) {
            console.error('Error fetching tags:', error);
        }
    }

    function initializeTagDisplay() {
        const tagElements = document.querySelectorAll('.activity-tag');
        tagElements.forEach(element => {
            const tagName = element.dataset.tag;
            const tagInfo = tagDefinitions[tagName] || {
                display_name: formatTagName(tagName),
                class: 'primary'
            };
            element.textContent = tagInfo.display_name;
            element.classList.add(tagInfo.class);
        });
    }

    function formatTagName(tag) {
        return tag.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    }

    // Initialize the page
    initializePage();
});