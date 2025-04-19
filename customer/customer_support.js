(function() {
    // Global variables
    let currentChatRoomId = null;
    let currentUser = null;
    const supportAgent = 'support_agent'; // Support agent ID
    let selectedBranch = null; // To store the selected branch

    // Function to fetch branches from database
    async function fetchBranches() {
        try {
            const response = await fetch('customService/get_branches.php');
            const data = await response.json();
            
            if (data.success) {
                return data.branches;
            } else {
                console.error('Error fetching branches:', data.message);
                return [];
            }
        } catch (error) {
            console.error('Error fetching branches:', error);
            return [];
        }
    }

    // Function to save branch selection to database
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
                    user_id: userId
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
                    
                    // Update status to 'read' for messages from support agent
                    if (msg.sender === 'support_agent' || msg.sender === 'bot') {
                        updateMessageStatus(msg.chatId, 'read', currentUser);
                    }
                });
                
                // Auto-scroll to the bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        } catch (error) {
            console.error('Error loading chat history:', error);
        }
    }

    // Function to save message to database
    async function saveMessage(sender, message, chatRoomId, messageType = 'text', attachmentUrl = null, automated = false) {
        try {
            const response = await fetch('customService/save_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    sender,
                    message,
                    chatRoomId,
                    messageType,
                    attachmentUrl,
                    automated: automated,
                    branch: selectedBranch
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
    async function updateMessageStatus(chatId, status, userId) {
        try {
            const response = await fetch('customService/update_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    chatId,
                    status,
                    userId
                })
            });
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error updating message status:', error);
            return { success: false, error };
        }
    }

    // Function to show branch selection modal
    async function showBranchModal() {
        const branchModal = document.getElementById('branch-modal');
        const branchOptions = document.getElementById('branch-options');
        
        // Show loading state
        branchOptions.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading branches...</div>';
        branchModal.classList.remove('hidden');
        
        // Fetch branches from database
        const branches = await fetchBranches();
        
        if (branches.length > 0) {
            // Clear loading state and add branch buttons
            branchOptions.innerHTML = '';
            
            branches.forEach(branch => {
                const button = document.createElement('button');
                button.className = 'w-full py-2 px-4 bg-gold text-black rounded hover:bg-yellow-600 transition branch-button';
                button.dataset.branchId = branch.id;
                button.textContent = branch.name;
                
                button.addEventListener('click', async () => {
                    selectedBranch = branch.id;
                    localStorage.setItem('selectedBranch_' + currentUser, selectedBranch);
                    hideBranchModal();
                    
                    // Save branch selection to database
                    await saveBranchSelection(selectedBranch);
                    
                    // Initialize chat after branch selection
                    initChat();
                });
                
                branchOptions.appendChild(button);
            });
        } else {
            // Show error if no branches loaded
            branchOptions.innerHTML = '<div class="text-red-500 text-sm py-2">Failed to load branches. Please try again later.</div>';
        }
    }

    // Function to hide branch selection modal
    function hideBranchModal() {
        document.getElementById('branch-modal').classList.add('hidden');
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
        
        // Save user message to database
        const messageResponse = await saveMessage(
            currentUser,          // sender
            message,              // message
            currentChatRoomId,    // chatRoomId
            'text',               // messageType
            null,                 // attachmentUrl
            false                 // automated
        );
        
        console.log('Message sent response:', messageResponse);
        
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
            
            // Use bot as sender for automated replies
            const botSender = 'bot';
            
            // Save agent response to database
            await saveMessage(
                botSender,           // sender (bot for automated messages)
                responseMessage,      // message
                currentChatRoomId,    // chatRoomId
                'text',               // messageType
                null,                 // attachmentUrl
                true                  // automated
            );
        }, 1000);
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
            
            // Get branch name for welcome message
            let branchName = 'your selected branch';
            try {
                const response = await fetch(`customService/get_branch_name.php?branch_id=${selectedBranch}`);
                const data = await response.json();
                if (data.success) {
                    branchName = data.branch_name;
                }
            } catch (error) {
                console.error('Error fetching branch name:', error);
            }
            
            // Only show chatbot type indicator in the initial welcome message
            const welcomeMessage = `Hello, welcome to GrievEase ${branchName} customer support. How can I assist you today?`;
            
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
            
            // Use bot as sender for automated welcome message
            const botSender = 'bot';
            
            // Save welcome message to database
            saveMessage(
                botSender,
                welcomeMessage,
                currentChatRoomId,
                'text',  // messageType
                null,    // attachmentUrl
                true     // Mark as automated
            );
        }
    }

    // Function to open chat
    function openChat() {
        document.getElementById('overlay').classList.remove('hidden');
        document.getElementById('chat-window').classList.remove('hidden');
        document.getElementById('chat-window').classList.add('block');
        document.body.style.overflow = 'hidden';
        document.getElementById('chat-input').focus();
        
        // Initialize chat when opened - this will check branch first
        initChat();
    }

    // Function to close chat
    function closeChatWindow() {
        document.getElementById('overlay').classList.add('hidden');
        document.getElementById('chat-window').classList.add('hidden');
        document.getElementById('chat-window').classList.remove('block');
        document.body.style.overflow = 'auto';
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