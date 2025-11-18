<?php 
session_start();

include 'faviconLogo.php'; 

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
  <title>GrievEase - Chats</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="tailwind.js"></script>
</head>
<body class="flex bg-gray-50 communication-chat">

<?php include 'admin_sidebar.php'; ?>
  
  <!-- Main Content -->
  <div id="main-content" class="p-4 md:p-6 bg-gray-50 min-h-screen transition-all duration-300 lg:ml-64 w-full lg:w-[calc(100%-16rem)] main-content">
    <!-- Mobile Header with Menu Toggle -->
    <div class="lg:hidden flex items-center justify-between mb-4 bg-white p-3 rounded-lg shadow">
      <button id="mobile-menu-toggle" class="p-2 rounded-md text-gray-600 hover:bg-gray-100">
        <i class="fas fa-bars text-xl"></i>
      </button>
      <h1 class="text-xl font-bold text-gray-800">Customer Messages</h1>
      <div class="w-10"></div> <!-- Spacer for balance -->
    </div>

    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4 mb-6 bg-white p-4 rounded-lg shadow">
      <h1 class="text-xl lg:text-2xl font-bold text-gray-800">Customer Messages</h1>
      <div class="flex flex-col sm:flex-row gap-2 w-full lg:w-auto">
        <div class="relative flex-1 sm:flex-none">
          <input type="text" id="customer-search" placeholder="Search customers..." class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
          <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>
        <div class="flex gap-2">
          <button id="refresh-messages" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md flex items-center gap-2 flex-1 sm:flex-none justify-center">
            <i class="fas fa-sync-alt"></i> 
            <span class="hidden sm:inline">Refresh</span>
          </button>
          <div class="relative">
            <button id="filter-dropdown-btn" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-md flex items-center gap-2">
              <i class="fas fa-filter"></i> 
              <span class="hidden sm:inline">Filter</span>
            </button>
            <div id="filter-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-20">
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
    </div>

    <!-- Customer Messages Interface -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <!-- Messages Header -->
      <div class="border-b border-gray-200 bg-gray-50 p-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
          <h2 class="text-lg font-semibold text-gray-800">Incoming Customer Messages</h2>
          <div class="text-sm text-gray-500">Showing <span id="message-count" class="font-medium">0</span> messages</div>
        </div>
      </div>
      
      <!-- Messages Content -->
      <div class="divide-y divide-gray-200">
        <!-- Empty state - No messages -->
        <div id="empty-state" class="py-8 sm:py-12 flex flex-col items-center justify-center text-gray-500">
          <div class="bg-gray-100 rounded-full p-3 sm:p-4 mb-3 sm:mb-4">
            <i class="fas fa-inbox text-2xl sm:text-3xl"></i>
          </div>
          <h3 class="text-base sm:text-lg font-medium mb-1 text-center">No customer messages</h3>
          <p class="text-xs sm:text-sm text-center">Customer messages will appear here when received</p>
          <button id="load-messages-btn" class="mt-4 bg-sidebar-accent text-white px-4 py-2 rounded-md hover:bg-opacity-90 transition-colors">
            Load Messages
          </button>
        </div>
        
        <!-- Message list container -->
        <div id="message-list" class="hidden">
          <!-- Messages will be loaded here dynamically -->
        </div>
      </div>
    </div>
  </div>

  <!-- Message Detail Modal -->
  <div id="message-detail-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <!-- Modal Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
    
    <!-- Modal Content -->
    <div class="relative bg-white rounded-xl shadow-card w-full h-full md:h-auto md:max-h-[90vh] md:mx-4 md:max-w-4xl z-10 transform transition-all duration-300 overflow-hidden flex flex-col">
      <!-- Close Button -->
      <button type="button" id="close-modal" class="absolute top-3 right-3 md:top-4 md:right-4 z-10 bg-black bg-opacity-50 md:bg-transparent text-white hover:text-sidebar-accent transition-colors p-2 rounded-full">
        <i class="fas fa-times text-lg"></i>
      </button>
      
      <!-- Modal Header -->
      <div class="px-4 md:px-6 py-4 md:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
        <div>
          <h3 class="text-lg md:text-xl font-bold text-white flex items-center" id="modal-customer-name">Customer Name</h3>
          <p class="text-xs md:text-sm text-white text-opacity-80 mt-1" id="modal-message-date">Date</p>
        </div>
      </div>
      
      <!-- Modal Body - Conversation -->
      <div class="px-4 md:px-6 py-4 md:py-5 overflow-y-auto flex-grow" id="modal-conversation">
        <!-- Conversation messages will be loaded here -->
      </div>
      
      <!-- Modal Footer - Reply Form -->
      <div class="px-4 md:px-6 py-3 md:py-4 flex flex-col gap-3 border-t border-gray-200 bg-white">
        
        <div class="flex gap-2 md:gap-4">
          <textarea id="reply-input" class="flex-1 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200 resize-none min-h-[60px] md:min-h-[80px]" placeholder="Type your reply..."></textarea>
          <button class="px-4 md:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center self-end h-fit" id="send-reply">
            <i class="fas fa-paper-plane md:mr-2"></i>
            <span class="hidden md:inline">Send</span>
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
      const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
      
      // Current conversation state
      let currentChatRoomId = null;
      let currentReceiverId = null;
      let currentFilter = 'all';
      let currentSearch = '';
      
      // Mobile menu toggle functionality
      if (mobileMenuToggle) {
        mobileMenuToggle.addEventListener('click', function() {
          const sidebar = document.querySelector('.sidebar');
          if (sidebar) {
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('mobile-sidebar');
          }
        });
      }

      // Close sidebar when clicking on main content on mobile
      mainContent.addEventListener('click', function() {
        if (window.innerWidth < 1024) {
          const sidebar = document.querySelector('.sidebar');
          if (sidebar && !sidebar.classList.contains('hidden')) {
            sidebar.classList.add('hidden');
          }
        }
      });
      
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
          document.body.style.overflow = 'auto';
        });
      }
      
      // Close Modal when clicking outside
      messageDetailModal.addEventListener('click', function(e) {
        if (e.target === messageDetailModal) {
          messageDetailModal.classList.add('hidden');
          document.body.style.overflow = 'auto';
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
        messageList.classList.remove('hidden');
        emptyState.style.display = 'none';
        
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
          messageItem.className = `message-item p-3 md:p-4 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0 ${isUnread ? 'bg-blue-50 message-new' : 'message-read'}`;
          messageItem.dataset.chatRoomId = conversation.chatRoomId;
          messageItem.dataset.receiverId = conversation.sender;
          
          messageItem.innerHTML = `
            <div class="flex items-start">
              <div class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-yellow-600 flex items-center justify-center text-white mr-3 flex-shrink-0">
                ${conversation.sender_profile_picture ? 
                  `<img src="${conversation.sender_profile_picture}" alt="${conversation.sender_name}" class="w-8 h-8 md:w-10 md:h-10 rounded-full object-cover">` : 
                  `<span class="text-xs md:text-sm">${conversation.sender_name?.charAt(0).toUpperCase() ?? ''}</span>`}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex justify-between items-start">
                  <h4 class="text-sm font-medium text-gray-900 truncate">
                    ${conversation.sender_name?.charAt(0)?.toUpperCase() + conversation.sender_name?.slice(1)?.toLowerCase() ?? 'Unknown'}
                  </h4>
                  <span class="text-xs text-gray-500 whitespace-nowrap ml-2">${formattedDate}</span>
                </div>
                <p class="text-sm text-gray-600 truncate mt-1">${conversation.message}</p>
                <div class="flex items-center mt-2">
                  <span class="text-xs text-gray-500 truncate">${conversation.sender_email}</span>
                  ${isUnread ? '<span class="ml-2 w-2 h-2 bg-teal-500 rounded-full flex-shrink-0"></span>' : ''}
                  ${conversation.unread_count > 0 ? 
                    `<span class="ml-auto bg-teal-500 text-white text-xs px-2 py-0.5 rounded-full flex-shrink-0">${conversation.unread_count}</span>` : ''}
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
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
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
                conversationElement.classList.remove('message-new', 'bg-blue-50');
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
          messageElement.className = `mb-4 ${isAdmin ? 'pl-4 md:pl-12' : 'pr-4 md:pr-12'}`;
          
          messageElement.innerHTML = `
            <div class="flex ${isAdmin ? 'justify-end' : 'justify-start'}">
              <div class="rounded-lg py-2 px-3 md:px-4 max-w-[85%] md:max-w-[75%] ${isAdmin ? 'bg-sidebar-accent text-white' : 'bg-gray-100 text-gray-800'}">
                <div class="text-sm whitespace-pre-wrap break-words">${message.message}</div>
                <div class="text-xs mt-1 opacity-70 ${isAdmin ? 'text-gray-200' : 'text-gray-500'} text-right">${formattedTime}</div>
              </div>
            </div>
          `;
          
          modalConversation.appendChild(messageElement);
        });
        
        // Scroll to the bottom of the conversation
        modalConversation.scrollTop = modalConversation.scrollHeight;
      }
      
      let isSending = false;

      // Function to send a reply
      function sendReply() {
        // Prevent double submission
        if (isSending) return;
        isSending = true;

        const message = replyInput.value.trim();

        // Basic validation
        if (!message) {
          showError("Please type a message before sending.");
          isSending = false;
          return;
        }

        if (!currentChatRoomId || !currentReceiverId) {
          showError("Conversation not properly loaded.");
          isSending = false;
          return;
        }

        // UI: Show sending state
        sendReplyBtn.disabled = true;
        sendReplyBtn.innerHTML = '<i class="fas fa-spinner fa-spin md:mr-2"></i><span class="hidden md:inline">Sending...</span>';

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
        .then(response => {
          if (!response.ok) {
            throw new Error(`Server error: ${response.status}`);
          }
          return response.json();
        })
        .then(data => {
          if (data.success) {
            // Success: Clear input and update UI
            replyInput.value = '';
            
            // Optional: Instantly show the message (optimistic UI)
            const newMessage = data.data;
            if (newMessage) {
              const tempMessages = [{
                sender: '<?php echo $_SESSION["user_id"]; ?>',
                message: newMessage.message,
                timestamp: newMessage.timestamp || new Date().toISOString(),
                status: 'read'
              }];
              displayConversation(tempMessages); // Append instantly
            }

            // Reload full conversation to ensure order & sync
            loadConversation(currentChatRoomId);

          } else {
            throw new Error(data.error || 'Unknown error from server');
          }
        })
        .catch(error => {
          console.error('Send reply error:', error);
          showError('Failed to send message: ' + error.message);
        })
        .finally(() => {
          // Always runs: success or failure
          isSending = false;
          sendReplyBtn.disabled = false;
          sendReplyBtn.innerHTML = '<i class="fas fa-paper-plane md:mr-2"></i><span class="hidden md:inline">Send</span>';
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
        // Use a more mobile-friendly notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 max-w-[90vw]';
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
          document.body.removeChild(notification);
        }, 4000);
      }
      
      // Helper function to format date and time
      function formatDateTime(date) {
        const now = new Date();
        const yesterday = new Date(now);
        yesterday.setDate(now.getDate() - 1);
        
        if (date.toDateString() === now.toDateString()) {
          return formatTime(date);
        } else if (date.toDateString() === yesterday.toDateString()) {
          return `Yesterday, ${formatTime(date)}`;
        } else {
          return `${date.toLocaleDateString()}`;
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

      // Handle window resize
      window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
          const sidebar = document.querySelector('.sidebar');
          if (sidebar && sidebar.classList.contains('hidden')) {
            sidebar.classList.remove('hidden');
          }
        }
      });
    });

    // Add this function to validate search input
    function validateSearchInput(inputElement) {
      if (!inputElement) return;
      
      inputElement.addEventListener('input', function() {
        let value = this.value;
        
        // Don't allow consecutive spaces
        if (/\s{2,}/.test(value)) {
          this.value = value.replace(/\s{2,}/g, ' ');
          return;
        }
        
        // Don't allow space as first character
        if (value.startsWith(' ')) {
          this.value = value.substring(1);
          return;
        }
        
        // Only allow space after at least 2 characters
        if (value.length < 2 && value.includes(' ')) {
          this.value = value.replace(/\s/g, '');
          return;
        }
      });
      
      // Prevent paste of content with invalid spacing
      inputElement.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        
        // Clean the pasted text
        let cleanedText = pastedText;
        
        // Remove consecutive spaces
        cleanedText = cleanedText.replace(/\s{2,}/g, ' ');
        
        // Remove leading space
        if (cleanedText.startsWith(' ')) {
          cleanedText = cleanedText.substring(1);
        }
        
        // Remove spaces before 2 characters
        if (cleanedText.length < 2 && cleanedText.includes(' ')) {
          cleanedText = cleanedText.replace(/\s/g, '');
        }
        
        document.execCommand('insertText', false, cleanedText);
      });
    }

    // Apply validation to the customer search input in your file
    document.addEventListener('DOMContentLoaded', function() {
      const customerSearch = document.getElementById('customer-search');
      validateSearchInput(customerSearch);
    });
  </script>

  <style>
    /* Additional responsive styles */
    @media (max-width: 768px) {
      .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        z-index: 40;
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out;
      }
      
      .sidebar.mobile-sidebar {
        transform: translateX(0);
      }
      
      #main-content {
        margin-left: 0 !important;
        width: 100% !important;
      }
    }
    
    /* Improve scrollbar for mobile */
    @media (max-width: 640px) {
      #modal-conversation {
        scrollbar-width: none;
        -ms-overflow-style: none;
      }
      
      #modal-conversation::-webkit-scrollbar {
        display: none;
      }
    }
    
    /* Better touch targets for mobile */
    @media (max-width: 768px) {
      .message-item {
        min-height: 70px;
      }
      
      button, [role="button"] {
        min-height: 44px;
      }
    }
  </style>
</body>
</html>