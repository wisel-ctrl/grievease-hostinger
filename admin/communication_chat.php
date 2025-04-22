<?php 
session_start();

require_once '../db_connect.php'; // Database connection

// Get user's first name from database
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name , last_name , email , birthdate FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$first_name = $row['first_name']; // We're confident user_id exists
$last_name = $row['last_name'];
$email = $row['email'];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  // Redirect to login page
  header("Location: ../Landing_Page/login.php");
  exit();
}

// Check for admin user type (user_type = 1)
if ($_SESSION['user_type'] != 1) {
  // Redirect to appropriate page based on user type
  switch ($_SESSION['user_type']) {
      case 2:
          header("Location: ../employee/index.php");
          break;
      case 3:
          header("Location: ../customer/index.php");
          break;
      default:
          // Invalid user_type
          session_destroy();
          header("Location: ../Landing_Page/login.php");
  }
  exit();
}

// Optional: Check for session timeout (30 minutes)
$session_timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
  // Session has expired
  session_unset();
  session_destroy();
  header("Location: ../Landing_Page/login.php?timeout=1");
  exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Prevent caching for authenticated pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Messages - FSMS</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="tailwind.js"></script>
</head>
<body class="flex bg-gray-50 communication-chat">

<?php include 'admin_sidebar.php'; ?>
  
  <!-- Main Content -->
  <div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
    <div class="flex justify-between items-center mb-6 bg-white p-4 rounded-lg shadow">
      <h1 class="text-2xl font-bold text-gray-800">Customer Messages</h1>
      <div class="flex gap-2">
        <div class="relative">
          <input type="text" id="customer-search" placeholder="Search customers..." class="px-4 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
          <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>
        <button id="refresh-messages" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md flex items-center gap-2">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
        <div class="relative">
          <button id="filter-dropdown-btn" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md flex items-center gap-2">
            <i class="fas fa-filter"></i> Filter
          </button>
          <div id="filter-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10">
            <div class="py-1">
              <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="filterMessages('all')">All Messages</button>
              <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="filterMessages('unread')">Unread Only</button>
              <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="filterMessages('today')">Today</button>
              <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="filterMessages('week')">This Week</button>
              <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="filterMessages('month')">This Month</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Customer Messages Interface -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
  <!-- Messages Header -->
  <div class="border-b border-sidebar-border bg-gray-50 p-4">
    <div class="flex justify-between items-center">
      <h2 class="text-lg font-semibold text-gray-800">Incoming Customer Messages</h2>
      <div class="text-sm text-gray-500">Showing <span id="message-count" class="font-medium">0</span> messages</div>
    </div>
  </div>
  
  <!-- Messages Content -->
  <div>
    <!-- Empty state - No messages -->
    <div id="empty-state" class="py-12 flex flex-col items-center justify-center text-gray-500">
      <div class="bg-gray-100 rounded-full p-4 mb-4">
        <i class="fas fa-inbox text-3xl"></i>
      </div>
      <h3 class="text-lg font-medium mb-1">No customer messages</h3>
      <p class="text-sm">Customer messages will appear here when received</p>
      <button id="load-messages-btn" class="mt-4 bg-[#008080] text-white px-4 py-2 rounded-md hover:bg-opacity-90 transition-colors">
        Load Messages
      </button>
    </div>
    
    <!-- Message list container as table -->
    <div id="message-list" class="hidden overflow-x-auto scrollbar-thin">
      <table class="w-full">
        <thead>
          <tr class="bg-gray-50 border-b border-sidebar-border">
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortMessages(0)">
              <div class="flex items-center">
                <i class="fas fa-user mr-1.5 text-sidebar-accent"></i> Customer
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortMessages(1)">
              <div class="flex items-center">
                <i class="fas fa-envelope mr-1.5 text-sidebar-accent"></i> Message
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortMessages(2)">
              <div class="flex items-center">
                <i class="fas fa-calendar-alt mr-1.5 text-sidebar-accent"></i> Date/Time
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortMessages(3)">
              <div class="flex items-center">
                <i class="fas fa-tag mr-1.5 text-sidebar-accent"></i> Status
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text">
              <div class="flex items-center">
                <i class="fas fa-cogs mr-1.5 text-sidebar-accent"></i> Actions
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <!-- Messages will be loaded here dynamically -->
        </tbody>
      </table>
    </div>
  </div>
</div>

      <!-- Message Detail Modal -->
      <div id="message-detail-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[80vh] flex flex-col">
        <!-- Modal Header -->
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
          <div>
            <h3 class="text-lg font-semibold" id="modal-customer-name">Customer Name</h3>
            <p class="text-sm text-gray-500" id="modal-message-date">Date</p>
          </div>
          <button id="close-modal" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times text-xl"></i>
          </button>
        </div>
        
        <!-- Modal Body - Conversation -->
        <div class="p-4 overflow-y-auto flex-grow" id="modal-conversation">
          <!-- Conversation messages will be loaded here -->
        </div>
        
        <!-- Modal Footer - Reply Form -->
        <div class="p-4 border-t border-gray-200">
          <div class="flex gap-2 mb-2">
            <button class="text-gray-500 hover:text-gray-700" title="Attach File">
              <i class="fas fa-paperclip"></i>
            </button>
            <button class="text-gray-500 hover:text-gray-700" title="Quick Reply Template">
              <i class="fas fa-reply-all"></i>
            </button>
            <button class="text-gray-500 hover:text-gray-700" title="Format Text">
              <i class="fas fa-font"></i>
            </button>
          </div>
          <div class="flex gap-2">
            <textarea id="reply-input" class="flex-1 p-2.5 border border-gray-300 rounded text-sm" placeholder="Type your reply..."></textarea>
            <button class="bg-[#008080] text-white px-4 py-2 rounded-md hover:bg-opacity-90" id="send-reply">
              <i class="fas fa-paper-plane mr-2"></i> Send
            </button>
          </div>
        </div>
      </div>
    </div>


    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // DOM Elements
        const mainContent = document.getElementById('main-content');
        const loadMessagesBtn = document.getElementById('load-messages-btn');
        const emptyState = document.getElementById('empty-state');
        const messageList = document.getElementById('message-list');
        const messageCount = document.getElementById('message-count');
        const messageDetailModal = document.getElementById('message-detail-modal');
        const closeModalBtn = document.getElementById('close-modal');
        const filterDropdownBtn = document.getElementById('filter-dropdown-btn');
        const filterDropdown = document.getElementById('filter-dropdown');
        const customerSearch = document.getElementById('customer-search');
        const refreshBtn = document.getElementById('refresh-messages');
        const replyInput = document.getElementById('reply-input');
        const sendReplyBtn = document.getElementById('send-reply');
        const modalCustomerName = document.getElementById('modal-customer-name');
        const modalMessageDate = document.getElementById('modal-message-date');
        const modalConversation = document.getElementById('modal-conversation');
        const messagesUnreadBadge = document.getElementById('messages-badge');
        
        // Current conversation state
        let currentChatRoomId = null;
        let currentReceiverId = null;
        let currentFilter = 'all';
        let currentSearch = '';
        
        
        // Toggle filter dropdown
        filterDropdownBtn.addEventListener('click', function() {
            filterDropdown.classList.toggle('hidden');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!filterDropdownBtn.contains(event.target) && !filterDropdown.contains(event.target)) {
                filterDropdown.classList.add('hidden');
            }
        });
        
        // Load Messages Button
        if (loadMessagesBtn) {
            loadMessagesBtn.addEventListener('click', function() {
                loadAllMessages();
            });
        }
        
        // Close Modal
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', function() {
                messageDetailModal.classList.add('hidden');
            });
        }
        
        // Close Modal when clicking outside
        messageDetailModal.addEventListener('click', function(e) {
            if (e.target === messageDetailModal) {
                messageDetailModal.classList.add('hidden');
            }
        });
        
        // Send Reply Button
        sendReplyBtn.addEventListener('click', function() {
            sendReply();
        });
        
        // Handle Enter key in reply input
        replyInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendReply();
            }
        });
        
        // Filter Messages
        window.filterMessages = function(filter) {
            currentFilter = filter;
            loadAllMessages();
            filterDropdown.classList.add('hidden');
        };
        
        // Setup search functionality
        if (customerSearch) {
            customerSearch.addEventListener('input', function() {
                currentSearch = this.value;
                // Debounce search to avoid too many requests
                clearTimeout(this.searchTimeout);
                this.searchTimeout = setTimeout(() => {
                    loadAllMessages();
                }, 500);
            });
        }
        
        // Add refresh functionality
        refreshBtn.addEventListener('click', function() {
            loadAllMessages();
            // Also refresh current conversation if open
            if (currentChatRoomId) {
                loadConversation(currentChatRoomId);
            }
        });
        
        // Function to load all messages
        function loadAllMessages() {
            // Show loading state
            messageList.innerHTML = '<div class="p-6 text-center text-gray-500">Loading messages...</div>';
            
            // Build query params
            const params = new URLSearchParams();
            params.append('filter', currentFilter);
            if (currentSearch) {
                params.append('search', currentSearch);
            }
            
            // Fetch messages from server
            fetch(`messages/get_admin_messages.php?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayMessages(data.conversations);
                        updateMessageCount(data.count);
                        updateUnreadBadge(data.conversations);
                        
                        // Toggle empty state
                        if (data.count > 0) {
                            emptyState.style.display = 'none';
                            messageList.classList.remove('hidden');
                        } else {
                            emptyState.style.display = 'flex';
                            messageList.classList.add('hidden');
                        }
                    } else {
                        showError('Failed to load messages: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    showError('Error loading messages: ' + error.message);
                });
        }
        
        // Function to display messages in the list
        function displayMessages(conversations) {
    const messageList = document.getElementById('message-list');
    const tableBody = messageList.querySelector('tbody');
    tableBody.innerHTML = '';
    
    conversations.forEach(conversation => {
      const messageDate = new Date(conversation.timestamp);
      const formattedDate = formatDateTime(messageDate);
      const isUnread = conversation.status === 'sent' && conversation.receiver === '<?php echo $_SESSION["user_id"]; ?>';
      
      const row = document.createElement('tr');
      row.className = `border-b border-sidebar-border hover:bg-sidebar-hover transition-colors ${isUnread ? 'bg-blue-50' : ''}`;
      row.dataset.chatRoomId = conversation.chatRoomId;
      row.dataset.receiverId = conversation.sender;
      row.style.cursor = 'pointer';
      
      row.innerHTML = `
        <td class="p-4 text-sm">
          <div class="flex items-center">
            <div class="w-10 h-10 rounded-full bg-yellow-600 flex items-center justify-center text-white mr-3">
              ${conversation.sender_profile_picture ? 
                `<img src="${conversation.sender_profile_picture}" alt="${conversation.sender_name}" class="w-10 h-10 rounded-full object-cover">` : 
                `<span>${conversation.sender_name.charAt(0).toUpperCase()}</span>`}
            </div>
            <div>
              <div class="font-medium text-gray-900">${conversation.sender_name.charAt(0).toUpperCase() + conversation.sender_name.slice(1).toLowerCase()}</div>
              <div class="text-xs text-gray-500">${conversation.sender_email}</div>
            </div>
          </div>
        </td>
        <td class="p-4 text-sm text-gray-600">
          <div class="max-w-xs truncate">${conversation.message}</div>
        </td>
        <td class="p-4 text-sm text-gray-600">${formattedDate}</td>
        <td class="p-4 text-sm">
          <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
            ${isUnread ? 'bg-green-100 text-green-600 border border-green-200' : 'bg-gray-100 text-gray-600 border border-gray-200'}">
            <i class="fas ${isUnread ? 'fa-envelope' : 'fa-envelope-open'} mr-1"></i>
            ${isUnread ? 'Unread' : 'Read'}
            ${conversation.unread_count > 0 ? 
              `<span class="ml-1 bg-teal-500 text-white px-1.5 py-0.5 rounded-full text-xs">${conversation.unread_count}</span>` : ''}
          </span>
        </td>
        <td class="p-4 text-sm">
          <div class="flex space-x-2">
            <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" 
                    title="View & Reply" onclick="openConversation('${conversation.chatRoomId}', '${conversation.sender}')">
              <i class="fas fa-reply"></i>
            </button>
            <button class="p-2 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition-all tooltip"
                    title="Mark as ${isUnread ? 'Read' : 'Unread'}">
              <i class="fas ${isUnread ? 'fa-envelope-open' : 'fa-envelope'}"></i>
            </button>
          </div>
        </td>
      `;
      
      row.addEventListener('click', function(e) {
        // Don't trigger row click if they clicked a button
        if (!e.target.closest('button')) {
          openConversation(this.dataset.chatRoomId, this.dataset.receiverId);
        }
      });
      
      tableBody.appendChild(row);
    });
  }
  
  // Add sorting function
  window.sortMessages = function(columnIndex) {
    // Implement sorting logic here if needed
    console.log("Sorting by column index:", columnIndex);
    // This would require modifying your data structure and re-rendering
    // For now just reload with current filter
    loadAllMessages();
  };
        
        // Function to open conversation modal
        function openConversation(chatRoomId, receiverId) {
            currentChatRoomId = chatRoomId;
            currentReceiverId = receiverId;
            
            // Show modal and loading state
            messageDetailModal.classList.remove('hidden');
            modalConversation.innerHTML = '<div class="p-6 text-center text-gray-500">Loading conversation...</div>';
            
            // Load conversation messages
            loadConversation(chatRoomId);
        }

        function capitalizeWords(str) {
          return str.replace(/\b\w/g, char => char.toUpperCase());
        }
        
        // Function to load conversation messages
        function loadConversation(chatRoomId) {
            fetch(`messages/get_conversation.php?chatRoomId=${chatRoomId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update modal header with user info
                        modalCustomerName.textContent = capitalizeWords(data.userInfo.name);
                        modalMessageDate.textContent = data.userInfo.email;
                        
                        // Display messages
                        displayConversation(data.messages);
                        
                        // Mark this conversation as read in the list
                        const conversationElement = document.querySelector(`.message-item[data-chat-room-id="${chatRoomId}"]`);
                        if (conversationElement) {
                            conversationElement.classList.remove('message-new');
                            conversationElement.classList.add('message-read');
                            
                            // Update unread indicators
                            const unreadDot = conversationElement.querySelector('.bg-teal-500.rounded-full');
                            if (unreadDot) unreadDot.remove();
                            
                            const unreadBadge = conversationElement.querySelector('.bg-teal-500.text-white');
                            if (unreadBadge) unreadBadge.remove();
                        }
                        
                        // After loading conversation, refresh message list to update unread counts
                        loadAllMessages();
                    } else {
                        showError('Failed to load conversation: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    showError('Error loading conversation: ' + error.message);
                });
        }
        
        // Function to display conversation messages
        function displayConversation(messages) {
            modalConversation.innerHTML = '';
            const adminId = '<?php echo $_SESSION["user_id"]; ?>';
            
            messages.forEach(message => {
                const isAdmin = message.sender === adminId;
                const messageDate = new Date(message.timestamp);
                const formattedTime = formatTime(messageDate);
                
                const messageElement = document.createElement('div');
                messageElement.className = `mb-4 ${isAdmin ? 'pl-12' : 'pr-12'}`;
                
                messageElement.innerHTML = `
                    <div class="flex ${isAdmin ? 'justify-end' : 'justify-start'}">
                        <div class="rounded-lg py-2 px-4 max-w-[75%] ${isAdmin ? 'bg-[#008080] text-white' : 'bg-gray-100 text-gray-800'}">
                            <div class="text-sm whitespace-pre-wrap">${message.message}</div>
                            <div class="text-xs mt-1 opacity-70 ${isAdmin ? 'text-gray-200' : 'text-gray-500'}">${formattedTime}</div>
                        </div>
                    </div>
                `;
                
                modalConversation.appendChild(messageElement);
            });
            
            // Scroll to the bottom of the conversation
            modalConversation.scrollTop = modalConversation.scrollHeight;
        }
        
        // Function to send a reply
        function sendReply() {
            const message = replyInput.value.trim();
            
            if (!message || !currentChatRoomId || !currentReceiverId) {
                return;
            }
            
            // Disable button while sending
            sendReplyBtn.disabled = true;
            sendReplyBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            const requestData = {
                chatRoomId: currentChatRoomId,
                receiverId: currentReceiverId,
                message: message
            };
            
            fetch('messages/send_reply.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(requestData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear input
                    replyInput.value = '';
                    
                    // Add the new message to the conversation
                    const newMessage = data.data;
                    const messages = [newMessage];
                    displayConversation(messages);
                    
                    // Refresh conversation to show all messages in proper order
                    loadConversation(currentChatRoomId);
                } else {
                    showError('Failed to send reply: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                showError('Error sending reply: ' + error.message);
            })
            .finally(() => {
                // Re-enable button
                sendReplyBtn.disabled = false;
                sendReplyBtn.innerHTML = '<i class="fas fa-paper-plane mr-2"></i> Send';
            });
        }
        
        // Function to update message count
        function updateMessageCount(count) {
            if (messageCount) {
                messageCount.textContent = count;
            }
        }
        
        // Function to update unread badge count
        function updateUnreadBadge(conversations) {
            if (messagesUnreadBadge) {
                const unreadCount = conversations.reduce((count, conv) => {
                    return count + (parseInt(conv.unread_count) || 0);
                }, 0);
                
                messagesUnreadBadge.textContent = unreadCount;
                messagesUnreadBadge.style.display = unreadCount > 0 ? 'flex' : 'none';
            }
        }
        
        // Helper function to show error
        function showError(message) {
            console.error(message);
            alert(message);
        }
        
        // Helper function to format date and time
        function formatDateTime(date) {
            const now = new Date();
            const yesterday = new Date(now);
            yesterday.setDate(now.getDate() - 1);
            
            if (date.toDateString() === now.toDateString()) {
                return `Today, ${formatTime(date)}`;
            } else if (date.toDateString() === yesterday.toDateString()) {
                return `Yesterday, ${formatTime(date)}`;
            } else {
                return `${date.toLocaleDateString()}, ${formatTime(date)}`;
            }
        }
        
        // Helper function to format time
        function formatTime(date) {
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        
        // Initial load
        loadAllMessages();
        
        // Set up periodic refresh (every 30 seconds)
        setInterval(loadAllMessages, 30000);
    });
    </script>
</body>
</html>