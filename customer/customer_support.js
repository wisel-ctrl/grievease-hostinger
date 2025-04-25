document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let currentChatRoomId = null;
    let currentUser = null;
    const supportAgent = 'support_agent'; // Support agent ID
    let selectedBranch = null; // To store the selected branch

    // Elements
    const csButton = document.getElementById('cs-button');
    const chatWindow = document.getElementById('chat-window');
    const minimizeChat = document.getElementById('minimize-chat');
    const closeChat = document.getElementById('close-chat');
    const overlay = document.getElementById('overlay');
    const chatInput = document.getElementById('chat-input');
    const sendMessage = document.getElementById('send-message');
    const chatMessages = document.getElementById('chat-messages');
    const branchModal = document.getElementById('branch-modal');
    const branchOptions = document.getElementById('branch-options');
    
    // Check if it's a mobile device
    function isMobile() {
        return window.innerWidth < 640; // sm breakpoint in Tailwind
    }
    
    // Function to handle UI adjustments for mobile
    function handleMobileUI() {
        if (isMobile()) {
            // For mobile: when chat is open, hide the main button
            if (!chatWindow.classList.contains('hidden')) {
                csButton.classList.add('hidden');
            } else {
                csButton.classList.remove('hidden');
            }
        } else {
            // For desktop: always show the button
            csButton.classList.remove('hidden');
        }
    }
    
    // Toggle chat window
    function toggleChatWindow() {
        chatWindow.classList.toggle('hidden');
        if (!chatWindow.classList.contains('hidden')) {
            chatInput.focus();
            
            // On mobile, show fullscreen and overlay
            if (isMobile()) {
                overlay.classList.remove('hidden');
                csButton.classList.add('hidden');
            }
            
            // Initialize chat when opened
            initChat();
        } else {
            overlay.classList.add('hidden');
            csButton.classList.remove('hidden');
        }
    }
    
    // Minimize chat
    function minimizeChatWindow() {
        chatWindow.classList.add('hidden');
        overlay.classList.add('hidden');
        csButton.classList.remove('hidden');
    }
    
    // Close chat
    function closeChatWindow() {
        chatWindow.classList.add('hidden');
        overlay.classList.add('hidden');
        csButton.classList.remove('hidden');
    }
    
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
                chatMessages.innerHTML = '';
                
                // Add messages to chat window
                data.messages.forEach(msg => {
                    const isUserMessage = msg.sender === currentUser;
                    const messageTime = new Date(msg.timestamp).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    
                    const messageHtml = isUserMessage 
                        ? `<div class="flex items-end justify-end">
                            <div class="bg-yellow-600 text-white rounded-lg p-2 max-w-[80%]">
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
        // Show loading state
        branchOptions.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading branches...</div>';
        branchModal.classList.remove('hidden');
        
        // Fetch branches from database
        const branches = await fetchBranches();
        
        if (branches.length > 0) {
            // Clear loading state and add branch buttons
            branchOptions.innerHTML = '';
            
            branches.forEach(branch => {
                const branchDiv = document.createElement('div');
                branchDiv.className = 'p-3 border rounded-lg hover:bg-gray-100 cursor-pointer transition';
                branchDiv.innerHTML = `
                    <p class="font-medium">${branch.name}</p>
                    <p class="text-sm text-gray-600">${branch.address}</p>
                `;
                
                branchDiv.addEventListener('click', async function() {
                    selectedBranch = branch.id;
                    localStorage.setItem('selectedBranch_' + currentUser, selectedBranch);
                    branchModal.classList.add('hidden');
                    
                    // Save branch selection to database
                    await saveBranchSelection(selectedBranch);
                    
                    // Initialize chat after branch selection
                    initChat();
                });
                
                branchOptions.appendChild(branchDiv);
            });
        } else {
            // Show error if no branches loaded
            branchOptions.innerHTML = '<div class="text-red-500 text-sm py-2">Failed to load branches. Please try again later.</div>';
        }
    }

    // Enhanced sendChatMessage function
    async function sendChatMessage() {
        const message = chatInput.value.trim();
        if (!message) return;
        
        const currentTime = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        
        // Add user message to UI
        const userMessageHtml = `
            <div class="flex items-end justify-end">
                <div class="bg-yellow-600 text-white rounded-lg p-2 max-w-[80%]">
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
        currentUser = document.getElementById('user-id').value;
        
        if (!currentUser) {
            console.error("No user ID found in session");
            return;
        }

        // Always use the same chat room ID based on user ID
        currentChatRoomId = 'user_' + currentUser;
        
        // Check if branch is already set in database
        const userBranch = await getUserBranch();
        
        // Show branch modal if needed
        if (userBranch === 'unknown') {
            showBranchModal();
            return;
        } else {
            selectedBranch = userBranch;
            localStorage.setItem('selectedBranch_' + currentUser, selectedBranch);
        }
        
        // Load chat history for this chat room
        await loadChatHistory(currentChatRoomId);
        
        // Check if this is a new chat (no messages yet)
        if (chatMessages.children.length === 0) {
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
            
            // Show welcome message
            const welcomeMessage = `Hello, welcome to GrievEase ${branchName} customer support. How can I assist you today?`;
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
            chatMessages.innerHTML = welcomeHtml;
            
            // Save welcome message to database
            await saveMessage(
                'bot',
                welcomeMessage,
                currentChatRoomId,
                'text',
                null,
                true
            );
        }
    }

    // Event listeners
    csButton.addEventListener('click', toggleChatWindow);
    overlay.addEventListener('click', closeChatWindow);
    minimizeChat.addEventListener('click', minimizeChatWindow);
    closeChat.addEventListener('click', closeChatWindow);
    sendMessage.addEventListener('click', sendChatMessage);
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendChatMessage();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', handleMobileUI);
    
    // Initialize UI
    handleMobileUI();
});