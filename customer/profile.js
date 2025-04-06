document.addEventListener('DOMContentLoaded', function() {
    // Tab Switching
    const tabLinks = document.querySelectorAll('.profile-tab');
    const tabContents = document.querySelectorAll('.tab-content');
    
    // Hide all tabs except the first one
    tabContents.forEach((content, index) => {
        if (index > 0) content.style.display = 'none';
    });
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs
            tabLinks.forEach(tab => {
                tab.classList.remove('bg-yellow-600/10', 'text-yellow-600');
                tab.classList.add('hover:bg-gray-50', 'text-navy');
            });
            
            // Add active class to clicked tab
            this.classList.add('bg-yellow-600/10', 'text-yellow-600');
            this.classList.remove('hover:bg-gray-50', 'text-navy');
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.style.display = 'none';
            });
            
            // Show the selected tab content
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).style.display = 'block';
            
            // Update URL hash without scrolling
            history.pushState(null, null, '#' + tabId);
        });
    });
    
    // Check for hash in URL on page load
    const hash = window.location.hash.substring(1);
    if (hash) {
        // Find the tab with the matching data-tab attribute
        const tabToActivate = document.querySelector(`.profile-tab[data-tab="${hash}"]`);
        if (tabToActivate) {
            // Trigger a click on the tab to activate it
            tabToActivate.click();
        }
    }
    
    // FAQ Accordions
    const faqToggles = document.querySelectorAll('.faq-toggle');
    
    faqToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const content = this.nextElementSibling;
            const icon = this.querySelector('i');
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.style.transform = 'rotate(180deg)';
            } else {
                content.classList.add('hidden');
                icon.style.transform = 'rotate(0)';
            }
        });
    });

    // Mobile Menu Toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
    }

    // Edit Profile Modal
    const editProfileBtn = document.getElementById('edit-profile-btn');
    const editProfileModal = document.getElementById('edit-profile-modal');
    const closeEditProfileModal = document.getElementById('close-edit-profile-modal');
    const editProfileModalContent = editProfileModal.querySelector('.relative');

    if (editProfileBtn && editProfileModal) {
        editProfileBtn.addEventListener('click', function() {
            editProfileModal.classList.remove('hidden');
            // Add a small delay to make the animation work
            setTimeout(() => {
                editProfileModalContent.classList.remove('scale-95', 'opacity-0');
                editProfileModalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        });
    }

    if (closeEditProfileModal && editProfileModal) {
        closeEditProfileModal.addEventListener('click', function() {
            // Reverse animation
            editProfileModalContent.classList.remove('scale-100', 'opacity-100');
            editProfileModalContent.classList.add('scale-95', 'opacity-0');
            // Add a delay before hiding the modal to let the animation complete
            setTimeout(() => {
                editProfileModal.classList.add('hidden');
            }, 300);
        });
    }

    // Define the closeEditProfileModal function in global scope
    window.closeEditProfileModal = function() {
        if (editProfileModal) {
            // Reverse animation
            editProfileModalContent.classList.remove('scale-100', 'opacity-100');
            editProfileModalContent.classList.add('scale-95', 'opacity-0');
            // Add a delay before hiding the modal to let the animation complete
            setTimeout(() => {
                editProfileModal.classList.add('hidden');
            }, 300);
        }
    };

    // Add Payment Method Modal
    const addPaymentMethodBtn = document.getElementById('add-payment-method');
    const addPaymentMethodModal = document.getElementById('add-payment-method-modal');
    const closeAddPaymentMethodModal = document.getElementById('close-add-payment-method-modal');
    const addPaymentMethodModalContent = addPaymentMethodModal.querySelector('.relative');

    if (addPaymentMethodBtn && addPaymentMethodModal) {
        addPaymentMethodBtn.addEventListener('click', function() {
            addPaymentMethodModal.classList.remove('hidden');
            // Add a small delay to make the animation work
            setTimeout(() => {
                addPaymentMethodModalContent.classList.remove('scale-95', 'opacity-0');
                addPaymentMethodModalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        });
    }

    if (closeAddPaymentMethodModal && addPaymentMethodModal) {
        closeAddPaymentMethodModal.addEventListener('click', function() {
            // Reverse animation
            addPaymentMethodModalContent.classList.remove('scale-100', 'opacity-100');
            addPaymentMethodModalContent.classList.add('scale-95', 'opacity-0');
            // Add a delay before hiding the modal to let the animation complete
            setTimeout(() => {
                addPaymentMethodModal.classList.add('hidden');
            }, 300);
        });
    }

    // Define the closeAddPaymentModal function in global scope
    window.closeAddPaymentModal = function() {
        if (addPaymentMethodModal) {
            // Reverse animation
            addPaymentMethodModalContent.classList.remove('scale-100', 'opacity-100');
            addPaymentMethodModalContent.classList.add('scale-95', 'opacity-0');
            // Add a delay before hiding the modal to let the animation complete
            setTimeout(() => {
                addPaymentMethodModal.classList.add('hidden');
            }, 300);
        }
    };

    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === editProfileModal) {
            closeEditProfileModal();
        }
        if (event.target === addPaymentMethodModal) {
            closeAddPaymentModal();
        }
    });

    // Notification Toast
    function showNotification(message) {
        const notification = document.getElementById('notification');
        const notificationMessage = document.getElementById('notification-message');

        notificationMessage.textContent = message;
        notification.classList.remove('hidden');
        notification.classList.add('notification');

        setTimeout(() => {
            notification.classList.add('hidden');
        }, 5000);
    }

    // Expose showNotification to global scope
    window.showNotification = showNotification;

    // Chat functionality
    const chatForm = document.getElementById('chat-form');
    if (chatForm) {
        const chatInput = document.getElementById('chat-input');
        const chatMessages = document.getElementById('chat-messages');

        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (chatInput.value.trim() === '') return;
            
            // Create user message
            const userMessage = document.createElement('div');
            userMessage.className = 'flex mb-4 justify-end';
            userMessage.innerHTML = `
                <div class="bg-black text-white rounded-lg p-3 max-w-[80%]">
                    <p class="text-sm">${chatInput.value}</p>
                    <span class="text-xs text-gray-300 mt-1 block">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                </div>
                <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center ml-2 flex-shrink-0">
                    <span class="text-gray-700 text-sm font-bold">JD</span>
                </div>
            `;
            
            chatMessages.appendChild(userMessage);
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Clear input
            chatInput.value = '';
            
            // Simulate response (in a real app, this would be handled by the server)
            setTimeout(() => {
                const typingIndicator = document.createElement('div');
                typingIndicator.className = 'flex mb-4';
                typingIndicator.innerHTML = `
                    <div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center mr-2 flex-shrink-0">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div class="bg-gray-200 rounded-lg p-3">
                        <div class="flex space-x-1">
                            <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce"></div>
                            <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            <div class="w-2 h-2 bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.4s"></div>
                        </div>
                    </div>
                `;
                chatMessages.appendChild(typingIndicator);
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Remove typing indicator and add response after 2 seconds
                setTimeout(() => {
                    chatMessages.removeChild(typingIndicator);
                    
                    const supportMessage = document.createElement('div');
                    supportMessage.className = 'flex mb-4';
                    supportMessage.innerHTML = `
                        <div class="w-8 h-8 rounded-full bg-black text-white flex items-center justify-center mr-2 flex-shrink-0">
                            <i class="fas fa-headset"></i>
                        </div>
                        <div class="bg-gray-200 rounded-lg p-3 max-w-[80%]">
                            <p class="text-sm">Thank you for your message. A customer service representative will respond shortly.</p>
                            <span class="text-xs text-gray-500 mt-1 block">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                        </div>
                    `;
                    
                    chatMessages.appendChild(supportMessage);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }, 2000);
            }, 500);
        });
    }

    // Add ESC key listener to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            if (!editProfileModal.classList.contains('hidden')) {
                closeEditProfileModal();
            }
            if (!addPaymentMethodModal.classList.contains('hidden')) {
                closeAddPaymentModal();
            }
        }
    });
});