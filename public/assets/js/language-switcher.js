// Language Switcher Modal Script

document.addEventListener('DOMContentLoaded', function() {
    // Get the modal elements
    const modal = document.getElementById('language-modal');
    const btns = document.querySelectorAll('#language-selector-btn');
    const closeBtn = document.querySelector('.language-close');
    const languageOptions = document.querySelectorAll('.language-option a');
    
    // Open the modal when any language selector button is clicked
    if (btns.length > 0) {
        btns.forEach(btn => {
            btn.addEventListener('click', function() {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
            });
        });
    }
    
    // Close the modal when the X is clicked
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        });
    }
    
    // Close the modal when clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    });
    
    // Handle language selection
    languageOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            // Extract language code from the URL
            const href = this.getAttribute('href');
            const languageCode = href.substring(href.lastIndexOf('/') + 1);
            const currentLangText = document.querySelector('.js-current-language-text');
            
            // Update the button text (optional, since page will reload)
            if (currentLangText) {
                const languageNames = {
                    'en': 'English',
                    'fr': 'Français',
                    'ar': 'العربية',
                    'de': 'Deutsch'
                };
                currentLangText.textContent = languageNames[languageCode] || languageNames['en'];
            }
            
            // Handle RTL/LTR switching for Arabic
            if (languageCode === 'ar') {
                document.documentElement.setAttribute('dir', 'rtl');
            } else {
                document.documentElement.setAttribute('dir', 'ltr');
            }
            
            // Close the modal (the page will reload due to the href link)
            modal.style.display = 'none';
            document.body.style.overflow = '';
            
            // No need to prevent default - let the link work normally to change language
        });
    });
});
