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
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&family=Hedvig+Letters+Serif:opsz@12..144&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <style>
    /* Base Typography */
    body {
      font-family: 'Hedvig Letters Serif', serif;
      background-color: #F9F6F0; /* cream color from config */
    }
    
    /* Message status indicators */
    .message-new {
      border-left: 3px solid #CA8A04; /* sidebar accent color from config */
    }
    
    .message-read {
      border-left: 3px solid transparent;
    }
    
    /* Header Styles */
    h1 {
      font-family: 'Cinzel', serif;
      font-size: 1.5rem; /* 24px */
      font-weight: 700;
      color: #334155; /* slate-800 from config */
    }
    
    h2 {
      font-family: 'Cinzel', serif;
      font-size: 1.25rem; /* 20px */
      font-weight: 600;
      color: #334155; /* slate-800 from config */
    }
    
    h3 {
      font-family: 'Cinzel', serif;
      font-size: 1.125rem; /* 18px */
      font-weight: 600;
      color: #334155; /* slate-800 from config */
    }
    
    h5 {
      font-family: 'Cinzel', serif;
      font-size: 0.875rem; /* 14px */
      font-weight: 500;
      color: #CA8A04; /* sidebar accent color from config */
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }
    
    /* Text Colors */
    .text-sidebar-accent {
      color: #CA8A04; /* sidebar accent color from config */
    }
    
    .text-sidebar-text {
      color: #334155; /* slate-700 from config */
    }
    
    /* Button Styles */
    button {
      font-family: 'Hedvig Letters Serif', serif;
      font-size: 0.875rem; /* 14px */
      transition: all 0.3s ease;
    }
    
    /* Custom Button Styling */
    .btn-primary {
      background-color: #CA8A04; /* sidebar-accent from config */
      color: white;
      padding: 0.5rem 1rem;
      border-radius: 0.375rem;
      transition: all 0.2s ease;
    }
    
    .btn-primary:hover {
      background-color: #B7791F; /* darkgold from config */
    }
    
    .btn-secondary {
      background-color: #F1F5F9; /* secondary from config */
      color: #334155; /* secondary-foreground from config */
      padding: 0.5rem 1rem;
      border-radius: 0.375rem;
      transition: all 0.2s ease;
    }
    
    .btn-secondary:hover {
      background-color: #E2E8F0; /* sidebar-border from config */
    }
    
    /* Input Fields */
    input, textarea {
      font-family: 'Hedvig Letters Serif', serif;
      font-size: 0.875rem; /* 14px */
      border: 1px solid #D3D8E1; /* input-border from config */
      border-radius: 0.375rem; /* 6px */
      padding: 0.5rem 0.75rem;
      transition: all 0.2s ease;
    }
    
    input:focus, textarea:focus {
      outline: none;
      border-color: #CA8A04; /* sidebar-accent from config */
      box-shadow: 0 0 0 2px rgba(202, 138, 4, 0.2);
    }
    
    /* Icons */
    .fas {
      color: #64748B; /* slate-500 */
      transition: color 0.3s ease;
    }
    
    /* Hover States */
    button:hover .fas {
      color: #334155; /* slate-800 */
    }
    
    /* Message Bubbles */
    .admin-message {
      background-color: #CA8A04; /* sidebar accent */
      color: white;
    }
    
    .customer-message {
      background-color: #F1F5F9; /* secondary from config */
      color: #334155; /* secondary-foreground from config */
    }
    
    /* Timestamp Text */
    .message-time {
      font-size: 0.75rem; /* 12px */
      color: #64748B; /* slate-500 */
    }
    
    /* Badges */
    .badge {
      font-size: 0.75rem; /* 12px */
      background-color: #CA8A04; /* sidebar accent */
      color: white;
      padding: 0.25rem 0.5rem;
      border-radius: 9999px;
    }
    
    /* Card Styling */
    .card {
      background-color: white;
      border-radius: 0.5rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
      transition: all 0.3s ease;
    }
    
    .card-hover:hover {
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
      transform: translateY(-2px);
    }
    
    /* Ensure sidebar maintains styling */
    #sidebar {
      background-color: white !important;
      z-index: 50 !important;
      font-family: 'Hedvig Letters Serif', serif;
      border-right: 1px solid #E2E8F0; /* sidebar-border from config */
    }
    
    /* Mobile Responsiveness */
    @media (max-width: 768px) {
      #sidebar.translate-x-0 {
        background-color: white !important;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
      }
      
      h1 {
        font-size: 1.25rem; /* 20px */
      }
      
      h2 {
        font-size: 1.125rem; /* 18px */
      }
      
      .main-content {
        padding: 1rem !important;
      }
    }
    
    /* Custom scrollbar to match sidebar */
    .scrollbar-thin::-webkit-scrollbar {
      width: 4px;
      height: 4px;
    }
    .scrollbar-thin::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.05);
    }
    .scrollbar-thin::-webkit-scrollbar-thumb {
      background: rgba(202, 138, 4, 0.6);
      border-radius: 4px;
    }
    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
      background: rgba(202, 138, 4, 0.9);
    }
    
    /* Enhanced Conversation UI */
    .message-bubble {
      max-width: 75%;
      padding: 0.75rem 1rem;
      border-radius: 1rem;
      position: relative;
      margin-bottom: 1rem;
    }
    
    .message-bubble-admin {
      background-color: #CA8A04; /* sidebar-accent */
      color: white;
      border-top-right-radius: 0;
      margin-left: auto;
    }
    
    .message-bubble-customer {
      background-color: #F1F5F9; /* secondary */
      color: #334155; /* secondary-foreground */
      border-top-left-radius: 0;
    }
    
    /* Message list item styling */
    .message-item {
      transition: all 0.2s ease;
    }
    
    .message-item:hover {
      background-color: #F0F4F8; /* navy from config */
    }
    
    /* Enhanced input styling */
    .enhanced-input {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 1px solid #D3D8E1; /* input-border */
      border-radius: 0.5rem;
      transition: all 0.2s ease;
    }
    
    .enhanced-input:focus {
      outline: none;
      border-color: #CA8A04; /* sidebar-accent */
      box-shadow: 0 0 0 2px rgba(202, 138, 4, 0.2);
    }
    
    /* Avatar styling */
    .avatar {
      width: 2.5rem;
      height: 2.5rem;
      border-radius: 9999px;
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: #CA8A04; /* sidebar-accent */
      color: white;
      font-weight: 600;
    }
    
    body.communication-chat #sidebar {
      background-color: white !important;
      z-index: 50 !important;
    }
    
    .main-content {
      z-index: 40; /* Lower than sidebar's 50 */
    }
    
    /* Modal enhancements */
    .modal-card {
      background-color: white;
      border-radius: 0.75rem;
      overflow: hidden;
      box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
      max-width: 48rem;
      width: 90%;
      max-height: 80vh;
      display: flex;
      flex-direction: column;
    }
    
    .modal-header {
      padding: 1rem 1.5rem;
      border-bottom: 1px solid #E2E8F0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .modal-body {
      flex-grow: 1;
      overflow-y: auto;
      padding: 1.5rem;
    }
    
    .modal-footer {
      padding: 1rem 1.5rem;
      border-top: 1px solid #E2E8F0;
    }
  </style>
  
  <script>
    // Include Tailwind Config
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            'playfair': ['"Playfair Display"', 'serif'],
            'alexbrush': ['"Alex Brush"', 'cursive'],
            'inter': ['Inter', 'sans-serif'],
            'cinzel': ['Cinzel', 'serif'],
            'hedvig': ['Hedvig Letters Serif', 'serif']
          },
          colors: {
            'yellow': {
              600: '#CA8A04',
            },
            'navy': '#F0F4F8',
            'cream': '#F9F6F0',
            'dark': '#4A5568',
            'gold': '#D69E2E',
            'darkgold': '#B7791F',
            'primary': '#F8FAFC',
            'primary-foreground': '#334155',
            'secondary': '#F1F5F9',
            'secondary-foreground': '#334155',
            'border': '#E4E9F0',
            'input-border': '#D3D8E1',
            'error': '#E53E3E',
            'success': '#38A169',
            'sidebar-bg': '#FFFFFF',
            'sidebar-hover': '#F1F5F9',
            'sidebar-text': '#334155',
            'sidebar-accent': '#CA8A04',
            'sidebar-border': '#E2E8F0',
          },
          boxShadow: {
            'input': '0 1px 2px rgba(0, 0, 0, 0.05)',
            'card': '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
            'sidebar': '0 0 15px rgba(0, 0, 0, 0.05)'
          }
        }
      }
    }
  </script>
</head>
<body class="flex bg-cream communication-chat">

<?php include 'admin_sidebar.php'; ?>
  
  <!-- Main Content -->
  <div id="main-content" class="p-6 bg-cream min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
    <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-card">
      <h1 class="text-2xl font-bold text-sidebar-text font-cinzel">Customer Messages</h1>
      <div class="flex gap-3">
        <div class="relative">
          <input type="text" id="customer-search" placeholder="Search customers..." class="enhanced-input pl-10 pr-4 py-2">
          <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
        </div>
        <button id="refresh-messages" class="btn-secondary flex items-center gap-2">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
        <div class="relative">
          <button id="filter-dropdown-btn" class="btn-secondary flex items-center gap-2">
            <i class="fas fa-filter"></i> Filter
          </button>
          <div id="filter-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-card z-10">
            <div class="py-1">
              <button class="block w-full text-left px-4 py-2.5 text-sm text-sidebar-text hover:bg-sidebar-hover" onclick="filterMessages('all')">All Messages</button>
              <button class="block w-full text-left px-4 py-2.5 text-sm text-sidebar-text hover:bg-sidebar-hover" onclick="filterMessages('unread')">Unread Only</button>
              <button class="block w-full text-left px-4 py-2.5 text-sm text-sidebar-text hover:bg-sidebar-hover" onclick="filterMessages('today')">Today</button>
              <button class="block w-full text-left px-4 py-2.5 text-sm text-sidebar-text hover:bg-sidebar-hover" onclick="filterMessages('week')">This Week</button>
              <button class="block w-full text-left px-4 py-2.5 text-sm text-sidebar-text hover:bg-sidebar-hover" onclick="filterMessages('month')">This Month</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Customer Messages Interface -->
    <div class="bg-white rounded-lg shadow-card overflow-hidden">
      <!-- Messages Header -->
      <div class="border-b border-sidebar-border bg-primary p-4">
        <div class="flex justify-between items-center">
          <h2 class="text-lg font-semibold text-sidebar-text font-cinzel">Incoming Customer Messages</h2>
          <div class="text-sm text-dark"><span id="message-count" class="font-medium">0</span> messages</div>
        </div>
      </div>
      
      <!-- Messages Content -->
      <div class="divide-y divide-sidebar-border">
        <!-- Empty state - No messages -->
        <div id="empty-state" class="py-16 flex flex-col items-center justify-center text-dark">
          <div class="bg-navy rounded-full p-5 mb-5">
            <i class="fas fa-inbox text-4xl text-sidebar-accent"></i>
          </div>
          <h3 class="text-lg font-medium mb-2 font-cinzel">No customer messages</h3>
          <p class="text-sm text-dark mb-6">Customer messages will appear here when received</p>
          <button id="load-messages-btn" class="btn-primary flex items-center gap-2">
            <i class="fas fa-envelope text-white"></i> Load Messages
          </button>
        </div>
        
        <!-- Message list container -->
        <div id="message-list" class="hidden max-h-[70vh] overflow-y-auto scrollbar-thin">
          <!-- Messages will be loaded here dynamically -->
        </div>
      </div>
    </div>
  </div>

  <!-- Message Detail Modal -->
  <div id="message-detail-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
    <div class="modal-card">
      <!-- Modal Header -->
      <div class="modal-header">
        <div>
          <h3 class="text-lg font-semibold font-cinzel text-sidebar-text" id="modal-customer-name">Customer Name</h3>
          <p class="text-sm text-dark" id="modal-message-date">customer@email.com</p>
        </div>
        <button id="close-modal" class="text-dark hover:text-sidebar-accent transition duration-200">
          <i class="fas fa-times text-xl"></i>
        </button>
      </div>
      
      <!-- Modal Body - Conversation -->
      <div class="modal-body scrollbar-thin" id="modal-conversation">
        <!-- Conversation messages will be loaded here -->
      </div>
      
      <!-- Modal Footer - Reply Form -->
      <div class="modal-footer">
        <div class="flex gap-3 mb-3">
          <button class="text-dark hover:text-sidebar-accent" title="Attach File">
            <i class="fas fa-paperclip"></i>
          </button>
          <button class="text-dark hover:text-sidebar-accent" title="Quick Reply Template">
            <i class="fas fa-reply-all"></i>
          </button>
          <button class="text-dark hover:text-sidebar-accent" title="Format Text">
            <i class="fas fa-font"></i>
          </button>
        </div>
        <div class="flex gap-3">
          <textarea id="reply-input" class="enhanced-input" placeholder="Type your reply..."></textarea>
          <button class="btn-primary flex items-center" id="send-reply">
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
            messageList.innerHTML = '';
            
            conversations.forEach(conversation => {
                const messageDate = new Date(conversation.timestamp);
                const formattedDate = formatDateTime(messageDate);
                const isUnread = conversation.status === 'sent' && conversation.receiver === '<?php echo $_SESSION["user_id"]; ?>';
                
                const messageItem = document.createElement('div');
                messageItem.className = `message-item p-4 hover:bg-gray-50 cursor-pointer ${isUnread ? 'message-new' : 'message-read'}`;
                messageItem.dataset.chatRoomId = conversation.chatRoomId;
                messageItem.dataset.receiverId = conversation.sender;
                
                messageItem.innerHTML = `
                    <div class="flex items-start">
                        <div class="w-10 h-10 rounded-full bg-yellow-600 flex items-center justify-center text-white mr-3">
                            ${conversation.sender_profile_picture ? 
                                `<img src="${conversation.sender_profile_picture}" alt="${conversation.sender_name}" class="w-10 h-10 rounded-full object-cover">` : 
                                `<span>${conversation.sender_name.charAt(0).toUpperCase()}</span>`}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start">
                                <h4 class="text-sm font-medium text-gray-900 truncate">${conversation.sender_name}</h4>
                                <span class="text-xs text-gray-500">${formattedDate}</span>
                            </div>
                            <p class="text-sm text-gray-600 truncate">${conversation.message}</p>
                            <div class="flex items-center mt-1">
                                <span class="text-xs text-gray-500">${conversation.sender_email}</span>
                                ${isUnread ? '<span class="ml-2 w-2 h-2 bg-teal-500 rounded-full"></span>' : ''}
                                ${conversation.unread_count > 0 ? 
                                    `<span class="ml-auto bg-teal-500 text-white text-xs px-2 py-0.5 rounded-full">${conversation.unread_count}</span>` : ''}
                            </div>
                        </div>
                    </div>
                `;
                
                messageItem.addEventListener('click', function() {
                    openConversation(this.dataset.chatRoomId, this.dataset.receiverId);
                });
                
                messageList.appendChild(messageItem);
            });
        }
        
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
        
        // Function to load conversation messages
        function loadConversation(chatRoomId) {
            fetch(`messages/get_conversation.php?chatRoomId=${chatRoomId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update modal header with user info
                        modalCustomerName.textContent = data.userInfo.name;
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