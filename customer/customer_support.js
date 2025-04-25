document.addEventListener('DOMContentLoaded', function() {
    // Global variables
    let currentChatRoomId = null;
    let currentUser = null;
    const supportAgent = 'support_agent';
    let selectedBranch = null;
    let isMinimized = false;

    // DOM Elements
    const csButton = document.getElementById('cs-button');
    const chatWindow = document.getElementById('chat-window');
    const chatInput = document.getElementById('chat-input');
    const sendButton = document.getElementById('send-message');
    const chatMessages = document.getElementById('chat-messages');
    const closeButton = document.getElementById('close-chat');
    const minimizeButton = document.getElementById('minimize-chat');
    const overlay = document.getElementById('overlay');
    const branchModal = document.getElementById('branch-modal');
    const branchOptions = document.getElementById('branch-options');
    const userId = document.getElementById('user-id').value;

    // Check if mobile device
    function isMobile() {
        return window.innerWidth < 640;
    }

    // Handle responsive layout
    function handleResponsiveLayout() {
        if (chatWindow.classList.contains('hidden')) return;
        
        if (isMobile()) {
            // Mobile view
            chatWindow.classList.add('bottom-20', 'right-4');
            chatWindow.classList.remove('bottom-0', 'right-0');
            chatWindow.style.height = 'calc(100% - 120px)';
            chatWindow.style.width = 'calc(100% - 32px)';
            
            // Hide main button when chat is open
            if (!chatWindow.classList.contains('hidden')) {
                csButton.classList.add('hidden');
            }
        } else {
            // Desktop view
            chatWindow.classList.remove('bottom-20', 'right-4');
            chatWindow.style.height = '';
            chatWindow.style.width = '';
            csButton.classList.remove('hidden');
        }
    }

    // Toggle chat window
    function toggleChatWindow() {
        const isHidden = chatWindow.classList.contains('hidden');
        
        if (isHidden) {
            // Show chat window
            chatWindow.classList.remove('hidden');
            overlay.classList.remove('hidden');
            isMinimized = false;
            chatInput.focus();
            
            // Initialize chat when opened
            initChat();
        } else {
            // Hide chat window
            chatWindow.classList.add('hidden');
            overlay.classList.add('hidden');
        }
        
        handleResponsiveLayout();
    }

    // Minimize chat window
    function minimizeChatWindow() {
        if (!isMinimized) {
            const headerHeight = chatWindow.querySelector('.bg-black').offsetHeight;
            chatWindow.style.height = `${headerHeight}px`;
            chatMessages.style.display = 'none';
            chatWindow.querySelector('.p-3.border-t').style.display = 'none';
            isMinimized = true;
        } else {
            chatWindow.style.height = '';
            chatMessages.style.display = '';
            chatWindow.querySelector('.p-3.border-t').style.display = '';
            isMinimized = false;
            handleResponsiveLayout();
        }
    }

    // Close chat window
    function closeChatWindow() {
        chatWindow.classList.add('hidden');
        overlay.classList.add('hidden');
        csButton.classList.remove('hidden');
    }

    // Add message to chat UI
    function addMessage(content, type = 'user', senderName = null) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'flex items-start ' + (type === 'user' ? 'justify-end' : '');
        
        const messageBubble = document.createElement('div');
        messageBubble.className = type === 'user' 
            ? 'bg-yellow-600 text-white rounded-lg p-2 max-w-[80%]'
            : 'bg-gray-200 rounded-lg p-2 max-w-[80%]';
        
        const messageText = document.createElement('p');
        messageText.className = 'text-sm';
        
        // Add sender name if provided (for system/bot messages)
        if (senderName) {
            const senderElement = document.createElement('p');
            senderElement.className = 'text-xs text-gray-500 mb-1';
            senderElement.textContent = `[${senderName}]`;
            messageBubble.appendChild(senderElement);
        }
        
        messageText.textContent = content;
        messageBubble.appendChild(messageText);
        
        // Add timestamp
        const timeElement = document.createElement('span');
        timeElement.className = type === 'user' ? 'text-xs text-gray-800 mt-1' : 'text-xs text-gray-500 mt-1';
        timeElement.textContent = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
        messageBubble.appendChild(timeElement);
        
        messageDiv.appendChild(messageBubble);
        chatMessages.appendChild(messageDiv);
        
        // Scroll to bottom
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Fetch available branches
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

    // Render branch options
    async function renderBranchOptions() {
        branchOptions.innerHTML = '<div class="text-center py-4"><i class="fas fa-spinner fa-spin"></i> Loading branches...</div>';
        branchModal.classList.remove('hidden');
        
        const branches = await fetchBranches();
        
        if (branches.length > 0) {
            branchOptions.innerHTML = '';
            
            branches.forEach(branch => {
                const branchElement = document.createElement('div');
                branchElement.className = 'p-3 border rounded-lg hover:bg-gray-100 cursor-pointer transition';
                branchElement.innerHTML = `
                    <p class="font-medium">${branch.name}</p>
                    ${branch.address ? `<p class="text-sm text-gray-600">${branch.address}</p>` : ''}
                `;
                
                branchElement.addEventListener('click', async () => {
                    selectedBranch = branch.id;
                    localStorage.setItem('selectedBranch_' + currentUser, selectedBranch);
                    branchModal.classList.add('hidden');
                    
                    // Save branch selection to database
                    await saveBranchSelection(selectedBranch);
                    
                    // Add system message
                    addMessage(`You're now connected to ${branch.name}. An agent will be with you shortly.`, 'system');
                    
                    // Initialize chat after branch selection
                    initChat();
                });
                
                branchOptions.appendChild(branchElement);
            });
        } else {
            branchOptions.innerHTML = '<div class="text-red-500 text-sm py-2">Failed to load branches. Please try again later.</div>';
        }
    }

    // Save branch selection to database
    async function saveBranchSelection(branch) {
        try {
            const response = await fetch('customService/save_branch.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    branch: branch,
                    user_id: currentUser
                })
            });
            
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error saving branch selection:', error);
            return { success: false, error };
        }
    }

    // Get user's branch from database
    async function getUserBranch() {
        try {
            const response = await fetch(`customService/get_user_branch.php?user_id=${currentUser}`);
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

    // Load chat history
    async function loadChatHistory(chatRoomId) {
        try {
            const response = await fetch(`customService/get_messages.php?chatRoomId=${chatRoomId}`);
            const data = await response.json();
            
            if (data.success && data.messages.length > 0) {
                chatMessages.innerHTML = '';
                
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
                                ${msg.sender !== 'support_agent' && msg.sender !== 'bot' ? '' : `<p class="text-xs text-gray-500 mb-1">[${msg.sender === 'bot' ? 'Customer Support Bot' : 'Support Agent'}]</p>`}
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
                
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        } catch (error) {
            console.error('Error loading chat history:', error);
        }
    }

    // Save message to database
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

    // Update message status
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

    // Send chat message
    async function sendChatMessage() {
        const message = chatInput.value.trim();
        if (!message) return;
        
        // Add user message to UI
        addMessage(message, 'user');
        chatInput.value = '';
        
        // Save user message to database
        const messageResponse = await saveMessage(
            currentUser,
            message,
            currentChatRoomId,
            'text',
            null,
            false
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
            addMessage(responseMessage, 'system', 'Customer Support Bot');
            
            // Save agent response to database
            await saveMessage(
                'bot',
                responseMessage,
                currentChatRoomId,
                'text',
                null,
                true
            );
        }, 1000);
    }

    // Initialize chat
    async function initChat() {
        currentUser = document.getElementById('user-id').value;
        
        if (!currentUser) {
            console.error("No user ID found in session");
            return;
        }

        currentChatRoomId = 'user_' + currentUser;
        
        // Check if branch is already set in database
        const userBranch = await getUserBranch();
        
        // Show branch modal if needed
        if (userBranch === 'unknown') {
            await renderBranchOptions();
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
            addMessage(welcomeMessage, 'system', 'Customer Support Bot');
            
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
    sendButton.addEventListener('click', sendChatMessage);
    closeButton.addEventListener('click', closeChatWindow);
    minimizeButton.addEventListener('click', minimizeChatWindow);
    overlay.addEventListener('click', closeChatWindow);
    
    // Handle pressing Enter in input
    chatInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendChatMessage();
        }
    });
    
    // Handle window resize for responsive layout
    window.addEventListener('resize', handleResponsiveLayout);
    
    // Initialize UI
    handleResponsiveLayout();
});