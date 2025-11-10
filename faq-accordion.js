// FAQ Accordion Functionality
document.addEventListener('DOMContentLoaded', function() {
    // Initialize FAQ accordion on all FAQ sections
    initFaqAccordion();
});

function initFaqAccordion() {
    document.querySelectorAll('.faq-question').forEach(question => {
        // Only add event listener if not already added
        if (!question.hasAttribute('data-accordion-initialized')) {
            question.addEventListener('click', function() {
                const answer = this.nextElementSibling;
                const icon = this.querySelector('.fa-chevron-down');
                const isOpening = answer.classList.contains('hidden');
                
                // Close all other open answers first
                document.querySelectorAll('.faq-answer:not(.hidden)').forEach(openAnswer => {
                    if (openAnswer !== answer) {
                        openAnswer.classList.add('hidden');
                        const openIcon = openAnswer.previousElementSibling.querySelector('.fa-chevron-down');
                        if (openIcon) openIcon.classList.remove('rotate-180');
                    }
                });
                
                // Toggle current answer
                answer.classList.toggle('hidden');
                if (icon) {
                    icon.classList.toggle('rotate-180', !answer.classList.contains('hidden'));
                }
            });
            
            // Mark as initialized to prevent duplicate event listeners
            question.setAttribute('data-accordion-initialized', 'true');
        }
    });
}

// Make the function available globally
window.initFaqAccordion = initFaqAccordion;
