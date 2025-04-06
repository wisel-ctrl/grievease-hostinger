(function() {
    // Global variables
    let currentChatRoomId = null;
    let currentUser = null;
    const supportAgent = 'support_agent'; // Support agent ID
    let selectedBranch = null; // To store the selected branch

    // Function to save branch selection to database (updated)
    async function saveBranchSelection(branch) {
        try {
            // Make sure we have the current user ID from the hidden input
            const userId = document.getElementById('user-id').value;
            
            console.log("Saving branch:", branch, "for user:", userId); // Debug log
            
            const response = await fetch('customService/save_branch.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    branch: branch,
                    user_id: userId // Explicitly include user_id in the request
                })
            });
            
            const data = await response.json();
            console.log("Server response:", data); // Debug log
            return data;
        } catch (error) {
            console.error('Error saving branch selection:', error);
            return { success: false, error };
        }
    }

    // Function to get user's branch from database
    async function getUserBranch() {
        try {
            const userId = document.getElementById('user-id').value;
            const response = await fetch(`customService/get_user_branch.php?user_id=${userId}`);
            const data = await response.json();
            
            if (data.success) {
                return data.branch;
            } else {
                console.error('Error getting user branch:', data.message);
                return 'unknown';
            }
        } catch (error) {
            console.error('Error fetching user branch:', error);
            return 'unknown';
        }
    }

    // Function to load chat history
    async function loadChatHistory(chatRoomId) {
        try {
            const response = await fetch(`customService/get_messages.php?chatRoomId=${chatRoomId}`);
            const data = await response.json();
            
            if (data.success && data.messages.length > 0) {
                // Clear existing messages
                const chatMessages = document.getElementById('chat-messages');
                chatMessages.innerHTML = '';
                
                // Add messages to chat window
                data.messages.forEach(msg => {
                    const isUserMessage = msg.sender === currentUser;
                    const messageTime = new Date(msg.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    const messageHtml = isUserMessage 
                        ? `<div class="flex items-end justify-end">
                            <div class="bg-gold text-black rounded-lg p-2 max-w-[80%]">
                                <p class="text-sm">${msg.message}</p>
                                <span class="text-xs text-gray-800 mt-1">${messageTime}</span>
                            </div>
                        </div>`
                        : `<div class="flex items-start">
                            <div class="bg-gray-200 rounded-lg p-2 max-w-[80%]">
                                <p class="text-sm">${msg.message}</p>
                                <span class="text-xs text-gray-500 mt-1">${messageTime}</span>
                            </div>
                        </div>`;
                    
                    chatMessages.innerHTML += messageHtml;
                });
                
                // Auto-scroll to the bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        } catch (error) {
            console.error('Error loading chat history:', error);
        }
    }

    // Function to save message to database
    async function saveMessage(sender, receiver, message, chatRoomId, messageType = 'text', attachmentUrl = null, automated = false) {
        try {
            const response = await fetch('customService/save_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    sender,
                    receiver,
                    message,
                    chatRoomId,
                    messageType,
                    attachmentUrl,
                    automated: automated,
                    branch: selectedBranch // Include the selected branch with each message
                })
            });
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error saving message:', error);
            return { success: false, error };
        }
    }

    // Function to update message status
    async function updateMessageStatus(chatId, status) {
        try {
            const response = await fetch('customService/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    chatId,
                    status
                })
            });
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error updating message status:', error);
            return { success: false, error };
        }
    }

    // Enhanced sendChatMessage function
    async function sendChatMessage() {
        const chatInput = document.getElementById('chat-input');
        const message = chatInput.value.trim();
        if (!message) return;
        
        const currentTime = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        // Add user message to UI
        const chatMessages = document.getElementById('chat-messages');
        const userMessageHtml = `
            <div class="flex items-end justify-end">
                <div class="bg-gold text-black rounded-lg p-2 max-w-[80%]">
                    <p class="text-sm">${message}</p>
                    <span class="text-xs text-gray-800 mt-1">${currentTime}</span>
                </div>
            </div>
        `;
        chatMessages.innerHTML += userMessageHtml;
        
        // Clear input and scroll to bottom
        chatInput.value = '';
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        // Save user message to database (you need to add this line)
        await saveMessage(
            currentUser,          // sender
            'support_agent',      // receiver
            message,              // message
            currentChatRoomId,    // chatRoomId
            'text',               // messageType
            null,                 // attachmentUrl
            false                 // automated
        );
        
        // Simulate response from support agent
        setTimeout(async () => {
            // Determine response based on message content
            let responseMessage = "Thank you for your message. One of our customer service representatives will assist you shortly.";
            
            // Simple keyword detection for automated responses
            const lowerCaseMsg = message.toLowerCase();
            if (lowerCaseMsg.includes('hello') || lowerCaseMsg.includes('hi')) {
                responseMessage = "Hello! How can I help you today?";
            } else if (lowerCaseMsg.includes('hours') || lowerCaseMsg.includes('open')) {
                responseMessage = "Our support team is available Monday to Friday, 9 AM to 5 PM.";
            } else if (lowerCaseMsg.includes('contact') || lowerCaseMsg.includes('phone')) {
                responseMessage = "You can reach our support team at support@grievease.com or call us at (555) 123-4567.";
            } else if (lowerCaseMsg.includes('thank')) {
                responseMessage = "You're welcome! Is there anything else I can help you with?";
            }
            
            // Add agent response to UI
            const responseHtml = `
                <div class="flex items-start">
                    <div class="bg-gray-200 rounded-lg p-2 max-w-[80%]">
                        <p class="text-sm">${responseMessage}</p>
                        <span class="text-xs text-gray-500 mt-1">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                    </div>
                </div>
            `;
            chatMessages.innerHTML += responseHtml;
            chatMessages.scrollTop = chatMessages.scrollHeight;
            
            // Save agent response to database with correct parameters
            await saveMessage(
                'support_agent',      // sender
                currentUser,          // receiver
                responseMessage,      // message
                currentChatRoomId,    // chatRoomId
                'text',               // messageType
                null,                 // attachmentUrl
                true                  // automated - this flag ensures it will be saved as 'bot' in DB
            );
        }, 1000);
    }

    // Function to show branch selection modal
    function showBranchModal() {
        document.getElementById('branch-modal').classList.remove('hidden');
    }

    // Function to hide branch selection modal
    function hideBranchModal() {
        document.getElementById('branch-modal').classList.add('hidden');
    }

    // Initialize chat
    async function initChat() {
        // Get user ID from session
        currentUser = document.getElementById('user-id').value || 'visitor_' + Math.random().toString(36).substr(2, 9);
        
        // Check if branch is already set in database
        const userBranch = await getUserBranch();
        
        // Only show branch modal if branch is 'unknown'
        if (userBranch === 'unknown') {
            // If no branch is selected, show the branch selection modal
            showBranchModal();
            return; // Don't continue with chat initialization until branch is selected
        } else {
            // Use the branch from database
            selectedBranch = userBranch;
            localStorage.setItem('selectedBranch_' + currentUser, selectedBranch);
        }
        
        // Try to load existing chat room ID from localStorage
        const savedChatRoomId = localStorage.getItem('chatRoomId_' + currentUser);
        
        if (savedChatRoomId) {
            currentChatRoomId = savedChatRoomId;
            loadChatHistory(currentChatRoomId);
        } else {
            // Create new chat room ID if none exists
            currentChatRoomId = 'room_' + currentUser + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('chatRoomId_' + currentUser, currentChatRoomId);
            
            // Only show chatbot type indicator in the initial welcome message
            const welcomeMessage = `Hello, welcome to GrievEase ${selectedBranch} branch customer support. How can I assist you today?`;
            
            // Add welcome message to UI with chatbot type indicator
            const currentTime = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
            const welcomeHtml = `
                <div class="flex items-start">
                    <div class="bg-gray-200 rounded-lg p-2 max-w-[80%]">
                        <p class="text-xs text-gray-500 mb-1">[Customer Support Bot]</p>
                        <p class="text-sm">${welcomeMessage}</p>
                        <span class="text-xs text-gray-500 mt-1">${currentTime}</span>
                    </div>
                </div>
            `;
            document.getElementById('chat-messages').innerHTML = welcomeHtml;
            
            // Save welcome message to database
            saveMessage(
                'support_agent',
                currentUser,
                welcomeMessage,
                currentChatRoomId,
                'text',  // messageType
                null,    // attachmentUrl
                true     // Mark as automated
            );
        }
    }

    // Event listeners for DOM Content Loaded
    document.addEventListener('DOMContentLoaded', () => {
        // Get elements
        const overlay = document.getElementById('overlay');
        const csButton = document.getElementById('cs-button');
        const chatWindow = document.getElementById('chat-window');
        const minimizeChat = document.getElementById('minimize-chat');
        const closeChatButton = document.getElementById('close-chat');
        const chatInput = document.getElementById('chat-input');
        const sendMessage = document.getElementById('send-message');
        const branchPaete = document.getElementById('branch-paete');
        const branchPila = document.getElementById('branch-pila');

        // Branch selection event listeners
        branchPaete.addEventListener('click', async () => {
            selectedBranch = 'paete';
            localStorage.setItem('selectedBranch_' + currentUser, selectedBranch);
            hideBranchModal();
            
            // Save branch selection to database - no need to pass user_id
            await saveBranchSelection(selectedBranch);
            
            // Initialize chat after branch selection
            initChat();
        });

        branchPila.addEventListener('click', async () => {
            selectedBranch = 'pila';
            localStorage.setItem('selectedBranch_' + currentUser, selectedBranch);
            hideBranchModal();
            
            // Save branch selection to database - no need to pass user_id
            await saveBranchSelection(selectedBranch);
            
            // Initialize chat after branch selection
            initChat();
        });

        // Function to open chat
        function openChat() {
            overlay.classList.remove('hidden');
            chatWindow.classList.remove('hidden');
            chatWindow.classList.add('block');
            document.body.style.overflow = 'hidden';
            chatInput.focus();
            
            // Initialize chat when opened - this will check branch first
            initChat();
        }

        // Function to close chat
        function closeChatWindow() {
            overlay.classList.add('hidden');
            chatWindow.classList.add('hidden');
            chatWindow.classList.remove('block');
            document.body.style.overflow = 'auto';
        }

        // Toggle chat window on button click
        csButton.addEventListener('click', () => {
            if (chatWindow.classList.contains('hidden')) {
                openChat();
            } else {
                closeChatWindow();
            }
        });

        // Close chat when clicking overlay
        overlay.addEventListener('click', closeChatWindow);

        // Minimize chat
        minimizeChat.addEventListener('click', closeChatWindow);

        // Close chat
        closeChatButton.addEventListener('click', closeChatWindow);

        // Send message on button click
        sendMessage.addEventListener('click', sendChatMessage);

        // Send message on Enter key
        chatInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                sendChatMessage();
            }
        });
    });
})();