<?php
//employee_chat.php
session_start();

include 'faviconLogo.php'; 

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check for employee user type (user_type = 2)
if ($_SESSION['user_type'] != 2) {
    // Redirect to appropriate page based on user type
    switch ($_SESSION['user_type']) {
        case 1: // Admin
            header("Location: ../admin/admin_index.php");
            break;
        case 3: // Customer
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

// Database connection
require_once '../db_connect.php';
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


// Function to get all messages for the logged-in employee using chat_recipients
function getEmployeeMessages($conn, $employeeId, $filter = 'all') {
  $query = "SELECT cm.*, 
            u_sender.first_name AS sender_first_name, 
            u_sender.last_name AS sender_last_name,
            u_sender.email AS sender_email,
            cr.status AS recipient_status,
            (SELECT COUNT(*) FROM chat_recipients 
             WHERE chatId = cm.chatId 
             AND userId = ? 
             AND status IN ('sent', 'delivered')) AS unread_count
            FROM chat_messages cm
            JOIN chat_recipients cr ON cm.chatId = cr.chatId
            LEFT JOIN users u_sender ON cm.sender = u_sender.id
            WHERE cr.userId = ?";

  // Add filter conditions
  switch ($filter) {
      case 'unread':
          $query .= " AND cr.status IN ('sent', 'delivered') ";
          break;
      case 'today':
          $query .= " AND DATE(cm.timestamp) = CURDATE() ";
          break;
      case 'week':
          $query .= " AND YEARWEEK(cm.timestamp) = YEARWEEK(CURDATE()) ";
          break;
      case 'month':
          $query .= " AND MONTH(cm.timestamp) = MONTH(CURDATE()) AND YEAR(cm.timestamp) = YEAR(CURDATE()) ";
          break;
  }

  $query .= " GROUP BY cm.chatId ORDER BY cm.timestamp DESC";

  $stmt = $conn->prepare($query);
  $stmt->bind_param("ss", $employeeId, $employeeId);
  $stmt->execute();
  $result = $stmt->get_result();

  $messages = [];
  while ($row = $result->fetch_assoc()) {
      $messages[] = $row;
  }

  return $messages;
}

// Function to mark message as read in chat_recipients
function markMessageAsRead($conn, $chatId, $userId) {
  $query = "UPDATE chat_recipients SET status = 'read' WHERE chatId = ? AND userId = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ss", $chatId, $userId);
  return $stmt->execute();
}

// Function to insert or update recipient
function insertOrUpdateRecipient($conn, $chatId, $userId, $status) {
    // Check if recipient already exists
    $check_query = "SELECT id FROM chat_recipients WHERE chatId = ? AND userId = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ss", $chatId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $update_query = "UPDATE chat_recipients SET status = ? WHERE chatId = ? AND userId = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sss", $status, $chatId, $userId);
        return $stmt->execute();
    } else {
        // Insert new record
        $insert_query = "INSERT INTO chat_recipients (chatId, userId, status) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sss", $chatId, $userId, $status);
        return $stmt->execute();
    }
}

// Function to send a reply with chat_recipients with retry mechanism
function sendReplyWithRetry($conn, $chatRoomId, $senderId, $receiverId, $message, $maxRetries = 3) {
    $retries = 0;
    
    while ($retries < $maxRetries) {
        try {
            $chatId = generateChatId($conn);
            
            // Start transaction
            $conn->begin_transaction();
            
            // Insert into chat_messages
            $query = "INSERT INTO chat_messages (chatId, sender, message, chatRoomId, messageType, status) 
                      VALUES (?, ?, ?, ?, 'text', 'sent')";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssss", $chatId, $senderId, $message, $chatRoomId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to insert into chat_messages: " . $stmt->error);
            }
            
            // Insert/Update chat_recipients for the sender
            if (!insertOrUpdateRecipient($conn, $chatId, $senderId, 'read')) {
                throw new Exception("Failed to insert/update sender recipient");
            }
            
            // Insert/Update chat_recipients for the receiver
            if (!insertOrUpdateRecipient($conn, $chatId, $receiverId, 'sent')) {
                throw new Exception("Failed to insert/update receiver recipient");
            }

            // Get admin user_id
            $admin_query = "SELECT id FROM users WHERE user_type = 1 LIMIT 1";
            $admin_result = $conn->query($admin_query);

            if ($admin_result && $admin_result->num_rows > 0) {
                $admin = $admin_result->fetch_assoc();
                $adminId = $admin['id'];

                // Insert/Update chat_recipients for admin
                if (!insertOrUpdateRecipient($conn, $chatId, $adminId, 'sent')) {
                    throw new Exception("Failed to insert/update admin recipient");
                }
            }
            
            $conn->commit();
            return ['success' => true, 'chatId' => $chatId];
            
        } catch (Exception $e) {
            $conn->rollback();
            
            // Check if it's a duplicate key error
            if (strpos($e->getMessage(), 'Duplicate entry') !== false && $retries < $maxRetries - 1) {
                $retries++;
                continue; // Retry with new ID
            } else {
                return ['success' => false, 'error' => $e->getMessage()];
            }
        }
    }
    
    return ['success' => false, 'error' => 'Max retries exceeded'];
}

// Function to get conversation history with chat_recipients
function getConversationHistory($conn, $chatRoomId, $currentUserId) {
  $query = "SELECT cm.*, 
            u_sender.first_name AS sender_first_name, 
            u_sender.last_name AS sender_last_name,
            cr.status AS recipient_status
            FROM chat_messages cm
            JOIN chat_recipients cr ON cm.chatId = cr.chatId
            LEFT JOIN users u_sender ON cm.sender = u_sender.id
            WHERE cm.chatRoomId = ?
            AND cr.userId = ?
            ORDER BY cm.timestamp ASC";

  $stmt = $conn->prepare($query);
  $stmt->bind_param("ss", $chatRoomId, $currentUserId);
  $stmt->execute();
  $result = $stmt->get_result();

  $messages = [];
  while ($row = $result->fetch_assoc()) {
      $messages[] = $row;
  }
  
  // Mark messages as read
  $query = "UPDATE chat_recipients SET status = 'read' 
            WHERE chatId IN (SELECT chatId FROM chat_messages WHERE chatRoomId = ?)
            AND userId = ? AND status IN ('sent', 'delivered')";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("ss", $chatRoomId, $currentUserId);
  $stmt->execute();

  return $messages;
}

// Function to search customers by name or email with chat_recipients
function searchCustomers($conn, $searchTerm, $employeeId) {
  $searchTerm = "%$searchTerm%";
  $query = "SELECT cm.*, 
            u_sender.first_name AS sender_first_name, 
            u_sender.last_name AS sender_last_name,
            u_sender.email AS sender_email,
            cr.status AS recipient_status
            FROM chat_messages cm
            JOIN chat_recipients cr ON cm.chatId = cr.chatId
            LEFT JOIN users u_sender ON cm.sender = u_sender.id
            WHERE cr.userId = ?
            AND (u_sender.first_name LIKE ? OR u_sender.last_name LIKE ? OR u_sender.email LIKE ?)
            GROUP BY cm.chatId
            ORDER BY cm.timestamp DESC";

  $stmt = $conn->prepare($query);
  $stmt->bind_param("ssss", $employeeId, $searchTerm, $searchTerm, $searchTerm);
  $stmt->execute();
  $result = $stmt->get_result();

  $messages = [];
  while ($row = $result->fetch_assoc()) {
      $messages[] = $row;
  }

  return $messages;
}

// API Endpoints
if (isset($_GET['action'])) {
  header('Content-Type: application/json');
  
  switch ($_GET['action']) {
      case 'getMessages':
          $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
          $messages = getEmployeeMessages($conn, $_SESSION['user_id'], $filter);
          echo json_encode(['success' => true, 'messages' => $messages, 'count' => count($messages)]);
          break;
          
      case 'getConversation':
          if (isset($_GET['chatRoomId'])) {
              $conversation = getConversationHistory($conn, $_GET['chatRoomId'], $_SESSION['user_id']);
              echo json_encode(['success' => true, 'conversation' => $conversation]);
          } else {
              echo json_encode(['success' => false, 'error' => 'Chat room ID not provided']);
          }
          break;
          
      case 'markAsRead':
          if (isset($_GET['chatId'])) {
              $success = markMessageAsRead($conn, $_GET['chatId'], $_SESSION['user_id']);
              echo json_encode(['success' => $success]);
          } else {
              echo json_encode(['success' => false, 'error' => 'Chat ID not provided']);
          }
          break;
          
      case 'sendReply':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                if (isset($data['chatRoomId']) && isset($data['receiverId']) && isset($data['message'])) {
                    $result = sendReplyWithRetry($conn, $data['chatRoomId'], $_SESSION['user_id'], $data['receiverId'], $data['message']);
                    echo json_encode($result);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Required parameters missing']);
                }
            }
            break;
          
      case 'searchCustomers':
          if (isset($_GET['searchTerm'])) {
              $results = searchCustomers($conn, $_GET['searchTerm'], $_SESSION['user_id']);
              echo json_encode(['success' => true, 'messages' => $results, 'count' => count($results)]);
          } else {
              echo json_encode(['success' => false, 'error' => 'Search term not provided']);
          }
          break;
          
      default:
          echo json_encode(['success' => false, 'error' => 'Invalid action']);
  }
  
  exit;
}
?>

  <script>
    // Employee Messaging JavaScript

document.addEventListener('DOMContentLoaded', function() {
  // DOM Elements
  const messageList = document.getElementById('message-list');
  const emptyState = document.getElementById('empty-state');
  const messageCount = document.getElementById('message-count');
  const loadMessagesBtn = document.getElementById('load-messages-btn');
  const refreshBtn = document.getElementById('refresh-messages');
  const customerSearch = document.getElementById('customer-search');
  const filterDropdownBtn = document.getElementById('filter-dropdown-btn');
  const filterDropdown = document.getElementById('filter-dropdown');
  const messageDetailModal = document.getElementById('message-detail-modal');
  const closeModalBtn = document.getElementById('close-modal');
  const modalCustomerName = document.getElementById('modal-customer-name');
  const modalMessageDate = document.getElementById('modal-message-date');
  const modalConversation = document.getElementById('modal-conversation');
  const replyInput = document.getElementById('reply-input');
  const sendReplyBtn = document.getElementById('send-reply');
  
  // Current state
  let currentFilter = 'all';
  let currentChatRoomId = null;
  let currentReceiverId = null;
  
  loadMessages();
  
  // Refresh messages
  refreshBtn.addEventListener('click', function() {
    loadMessages();
  });
  
  // Search functionality
  customerSearch.addEventListener('input', debounce(function() {
    loadMessages();
  }, 500));
  
  // Filter dropdown toggle
  filterDropdownBtn.addEventListener('click', function() {
    filterDropdown.classList.toggle('hidden');
  });
  
  // Close dropdown when clicking outside
  document.addEventListener('click', function(event) {
    if (!event.target.closest('#filter-dropdown-btn') && !event.target.closest('#filter-dropdown')) {
      filterDropdown.classList.add('hidden');
    }
  });
  
  // Close modal
  closeModalBtn.addEventListener('click', function() {
    messageDetailModal.classList.add('hidden');
  });
  
  // Send reply
  sendReplyBtn.addEventListener('click', function() {
    sendReply();
  });
  
  // Allow pressing Enter to send message
  replyInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendReply();
    }
  });
  
  // Load messages function
  function loadMessages() {
    const searchTerm = customerSearch.value;
    
    // Show loading state
    messageList.innerHTML = `
      <div class="py-8 flex justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#008080]"></div>
      </div>
    `;
    messageList.classList.remove('hidden');
    emptyState.classList.add('hidden');
    
    // Fetch messages from server
    fetch(`messages/get_messages.php?filter=${currentFilter}${searchTerm ? '&search=' + encodeURIComponent(searchTerm) : ''}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update message count
          messageCount.textContent = data.count;
          
          // Check if there are any messages
          if (data.count > 0) {
            // Show message list, hide empty state
            messageList.classList.remove('hidden');
            emptyState.classList.add('hidden');
            
            // Render messages
            renderMessages(data.conversations);
          } else {
            // Show empty state, hide message list
            messageList.classList.add('hidden');
            emptyState.classList.remove('hidden');
          }
        } else {
          console.error('Error fetching messages:', data.error);
          showError('Failed to load messages');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showError('Network error. Please try again.');
      });
  }
  
  // Render messages function
  function renderMessages(conversations) {
    let html = '';
    
    conversations.forEach(conversation => {
        const isUnread = conversation.unread_count > 0;
        const date = new Date(conversation.timestamp);
        const formattedDate = formatDate(date);
        
        // Always use the customer_name instead of sender_name
        const displayName = capitalizeWords(conversation.customer_name || 'Customer');
                
        html += `
            <div class="conversation-item hover:bg-gray-50 p-3 sm:p-4 cursor-pointer transition-colors duration-200 ${isUnread ? 'bg-blue-50' : ''}" 
         data-chatroom="${conversation.chatRoomId}" 
         data-receiver="${conversation.receiver || conversation.sender}">
              <div class="flex items-start gap-2 sm:gap-3">
                <div class="flex-shrink-0 w-8 h-8 sm:w-10 sm:h-10 bg-gray-200 rounded-full flex items-center justify-center text-gray-600 text-sm sm:text-base">
                  <i class="fas fa-user"></i>
                </div>
                <div class="flex-grow min-w-0">
                  <div class="flex justify-between items-start gap-2 flex-wrap sm:flex-nowrap">
                    <h3 class="font-semibold text-gray-800 text-sm sm:text-base truncate ${isUnread ? 'font-bold' : ''}">${displayName}</h3>
                    <span class="text-xs text-gray-500 whitespace-nowrap">${formattedDate}</span>
                  </div>
                  <div class="flex justify-between items-center mt-1 gap-2">
                    <p class="text-xs sm:text-sm text-gray-600 truncate">${conversation.message}</p>
                    ${isUnread ? `<span class="inline-flex items-center justify-center w-5 h-5 text-xs font-semibold text-white bg-[#008080] rounded-full flex-shrink-0">${conversation.unread_count}</span>` : ''}
                  </div>
                </div>
              </div>
            </div>
        `;
    });
    
    messageList.innerHTML = html;
    
    // Add click event listeners to conversation items
    document.querySelectorAll('.conversation-item').forEach(item => {
        item.addEventListener('click', function() {
            const chatRoomId = this.dataset.chatroom;
            const receiverId = this.dataset.receiver;
            openConversation(chatRoomId, receiverId);
        });
    });
} 

function capitalizeWords(str) {
  return str.replace(/\b\w/g, char => char.toUpperCase());
}

  // Open conversation function
  function openConversation(chatRoomId, receiverId) {
    currentChatRoomId = chatRoomId;
    currentReceiverId = receiverId;
    
    // Show loading state in modal
    modalConversation.innerHTML = `
      <div class="py-8 flex justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-[#008080]"></div>
      </div>
    `;
    messageDetailModal.classList.remove('hidden');
    
    // Fixed: Changed fetch_customer_messages.php to get_messages_details.php
    fetch(`messages/get_messages_details.php?chatRoomId=${chatRoomId}`)
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update modal header
          modalCustomerName.textContent = capitalizeWords(data.userInfo.name);
          modalMessageDate.textContent = data.userInfo.email;
          
          // Render conversation
          renderConversation(data.messages, data.userInfo);
          
          // After loading the conversation, refresh the message list to update unread counts
          loadMessages();
        } else {
          console.error('Error fetching conversation:', data.error);
          modalConversation.innerHTML = `<p class="text-red-500">Error loading conversation</p>`;
        }
      })
      .catch(error => {
        console.error('Error:', error);
        modalConversation.innerHTML = `<p class="text-red-500">Network error. Please try again.</p>`;
      });
  }
  
  // Render conversation function
  function renderConversation(messages, userInfo) {
    let html = '';
    let currentDate = '';
    
    messages.forEach(message => {
      const isEmployee = message.sender === '<?php echo $_SESSION['user_id']; ?>';
      const date = new Date(message.timestamp);
      const messageDate = formatDateMDY(date);
      const messageTime = formatTime(date);
      
      // Add date separator if the date changes
      if (messageDate !== currentDate) {
        html += `
          <div class="flex justify-center my-4">
            <div class="text-xs bg-gray-100 text-gray-500 px-3 py-1 rounded-full">${messageDate}</div>
          </div>
        `;
        currentDate = messageDate;
      }
      
      // Add message
      html += `
        <div class="mb-4 ${isEmployee ? 'flex justify-end' : 'flex justify-start'}">
          <div class="${isEmployee ? 'max-w-xs sm:max-w-sm md:max-w-md lg:max-w-lg' : 'max-w-xs sm:max-w-sm md:max-w-md'} ${isEmployee ? 'bg-[#008080] text-white rounded-l-lg rounded-tr-lg' : 'bg-gray-100 text-gray-800 rounded-r-lg rounded-tl-lg'} px-3 sm:px-4 py-2 shadow-sm">
            <div class="text-sm mb-1 break-words">${message.message}</div>
            <div class="text-xs ${isEmployee ? 'text-teal-100' : 'text-gray-500'} text-right">${messageTime}</div>
          </div>
        </div>
      `;
    });
    
    modalConversation.innerHTML = html;
    
    // Scroll to bottom of conversation
    modalConversation.scrollTop = modalConversation.scrollHeight;
    
    // Clear and focus reply input
    replyInput.value = '';
    replyInput.focus();
  }
  
  // Send reply function
  function sendReply() {
    const message = replyInput.value.trim();
    
    if (!message) {
      return;
    }
    
    // Disable send button
    sendReplyBtn.disabled = true;
    
    // Prepare data
    const data = {
      chatRoomId: currentChatRoomId,
      receiverId: currentReceiverId,
      message: message
    };
    
    // Send request
    fetch('messages/send_reply.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Clear input
          replyInput.value = '';
          
          // Re-fetch the conversation to show the new message
          openConversation(currentChatRoomId, currentReceiverId);
        } else {
          console.error('Error sending reply:', data.error);
          showError('Failed to send message');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showError('Network error. Please try again.');
      })
      .finally(() => {
        // Re-enable send button
        sendReplyBtn.disabled = false;
      });
  }
  
  // Filter messages
  window.filterMessages = function(filter) {
    currentFilter = filter;
    filterDropdown.classList.add('hidden');
    loadMessages();
  };
  
  // Utility functions
  function formatDate(date) {
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);
    
    if (diffMins < 1) {
      return 'Just now';
    } else if (diffMins < 60) {
      return `${diffMins}m ago`;
    } else if (diffHours < 24) {
      return `${diffHours}h ago`;
    } else if (diffDays < 7) {
      return `${diffDays}d ago`;
    } else {
      return `${date.getMonth() + 1}/${date.getDate()}/${date.getFullYear()}`;
    }
  }
  
  function formatDateMDY(date) {
    return `${date.getMonth() + 1}/${date.getDate()}/${date.getFullYear()}`;
  }
  
  function formatTime(date) {
    let hours = date.getHours();
    const minutes = date.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    
    hours = hours % 12;
    hours = hours ? hours : 12; // the hour '0' should be '12'
    
    return `${hours}:${minutes < 10 ? '0' + minutes : minutes} ${ampm}`;
  }
  
  function debounce(func, wait) {
    let timeout;
    return function() {
      const context = this;
      const args = arguments;
      clearTimeout(timeout);
      timeout = setTimeout(() => {
        func.apply(context, args);
      }, wait);
    };
  }
  
  function showError(message) {
    // Show error toast
    const toast = document.createElement('div');
    toast.className = 'fixed bottom-4 right-4 bg-red-500 text-white px-4 sm:px-6 py-3 rounded shadow-lg text-sm sm:text-base';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    // Remove after 3 seconds
    setTimeout(() => {
      toast.remove();
    }, 3000);
  }
});
  </script>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Chats</title>
  <?php include 'faviconLogo.php'; ?>
  <!-- Include Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <style>
    /* Custom scrollbar styles */
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
    
    /* Hover and active states for sidebar links */
    .sidebar-link {
      position: relative;
      transition: all 0.3s ease;
    }
    
    .sidebar-link::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 3px;
      background-color: transparent;
      transition: all 0.3s ease;
    }
    
    .sidebar-link:hover::before,
    .sidebar-link.active::before {
      background-color: #CA8A04;
    }

    /* Gradient background for menu section headers */
    .menu-header {
      background: linear-gradient(to right, rgba(202, 138, 4, 0.1), transparent);
    }

    .main-content {
      margin-left: 16rem;
      width: calc(100% - 16rem);
      z-index: 1;
    }

    .sidebar {
      z-index: 10;
    }

    #sidebar {
      transition: width 0.3s ease, opacity 0.3s ease, visibility 0.3s ease;
    }

    #main-content {
      transition: margin-left 0.3s ease;
    }

    .w-0 {
      width: 0;
    }

    .opacity-0 {
      opacity: 0;
    }

    .invisible {
      visibility: hidden;
    }

    .w-\[calc\(100\%-16rem\)\] {
      width: calc(100% - 16rem);
    }

    .w-\[calc\(100\%-4rem\)\] {
      width: calc(100% - 4rem);
    }

    /* Responsive main content adjustments */
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
        width: 100%;
      }
    }

    /* Mobile modal responsiveness */
    @media (max-width: 640px) {
      #message-detail-modal {
        padding: 0 !important;
      }

      #message-detail-modal .bg-white {
        border-radius: 0;
        max-height: 100vh;
        max-width: 100%;
      }

      #modal-conversation {
        max-height: calc(100vh - 200px);
      }
    }

    /* Text wrapping for long messages */
    .break-words {
      word-break: break-word;
      word-wrap: break-word;
      overflow-wrap: break-word;
    }
  </style>
</head>
<body class="flex bg-gray-50">
  <!-- Modify the sidebar structure to include a dedicated space for the hamburger menu -->
<?php include 'employee_sidebar.php'; ?>

  <!-- Main Content -->
<div id="main-content" class="p-3 sm:p-6 bg-gray-100 min-h-screen transition-all duration-300 ml-64 main-content">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 bg-white p-3 sm:p-4 rounded-lg shadow gap-3">
      <h1 class="text-xl sm:text-2xl font-bold text-gray-800">Customer Messages</h1>
      <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
          <div class="relative flex-1 sm:flex-initial">
            <input type="text" id="customer-search" placeholder="Search customers..." class="w-full px-3 sm:px-4 py-2 pl-9 sm:pl-10 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent text-sm sm:text-base">
            <i class="fas fa-search absolute left-2 sm:left-3 top-2 sm:top-3 text-gray-400 text-sm"></i>
          </div>
          <div class="flex gap-2 w-full sm:w-auto">
            <button id="refresh-messages" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 sm:px-4 py-2 rounded-md flex items-center justify-center gap-2 text-sm sm:text-base flex-1 sm:flex-initial">
              <i class="fas fa-sync-alt"></i> <span class="hidden sm:inline">Refresh</span>
            </button>
            <div class="relative flex-1 sm:flex-initial">
              <button id="filter-dropdown-btn" class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 sm:px-4 py-2 rounded-md flex items-center justify-center gap-2 text-sm sm:text-base">
                <i class="fas fa-filter"></i> <span class="hidden sm:inline">Filter</span>
              </button>
              <div id="filter-dropdown" class="hidden absolute right-0 mt-2 w-40 sm:w-48 bg-white rounded-md shadow-lg z-10">
                <div class="py-1">
                  <button class="block w-full text-left px-3 sm:px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="filterMessages('all')">All Messages</button>
                  <button class="block w-full text-left px-3 sm:px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="filterMessages('unread')">Unread Only</button>
                  <button class="block w-full text-left px-3 sm:px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="filterMessages('today')">Today</button>
                  <button class="block w-full text-left px-3 sm:px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="filterMessages('week')">This Week</button>
                  <button class="block w-full text-left px-3 sm:px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="filterMessages('month')">This Month</button>
                </div>
              </div>
            </div>
          </div>
        </div>
    </div>

    <!-- Customer Messages Interface -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
      <!-- Messages Header -->
      <div class="border-b border-gray-200 bg-gray-50 p-3 sm:p-4">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
          <h2 class="text-base sm:text-lg font-semibold text-gray-800">Incoming Customer Messages</h2>
          <div class="text-xs sm:text-sm text-gray-500">Showing <span id="message-count" class="font-medium">0</span> messages</div>
        </div>
      </div>
      
      <!-- Messages Content -->
      <div class="divide-y divide-gray-200">
        <!-- Empty state - No messages -->
        <div id="empty-state" class="py-12 px-4 flex flex-col items-center justify-center text-gray-500">
          <div class="bg-gray-100 rounded-full p-4 mb-4">
            <i class="fas fa-inbox text-2xl sm:text-3xl"></i>
          </div>
          <h3 class="text-base sm:text-lg font-medium mb-1">No customer messages</h3>
          <p class="text-xs sm:text-sm text-center">Customer messages will appear here when received</p>
          <button id="load-messages-btn" class="mt-4 bg-[#008080] text-white px-4 py-2 rounded-md hover:bg-opacity-90 transition-colors text-sm sm:text-base">
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
  <div id="message-detail-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex justify-center items-center p-4">
      <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl sm:max-w-3xl md:max-w-4xl max-h-[90vh] sm:max-h-[85vh] flex flex-col">
        <!-- Modal Header -->
        <div class="p-3 sm:p-4 border-b border-gray-200 flex justify-between items-start sm:items-center gap-2">
          <div class="flex-1 min-w-0">
            <h3 class="text-base sm:text-lg font-semibold truncate" id="modal-customer-name">Customer Name</h3>
            <p class="text-xs sm:text-sm text-gray-500 truncate" id="modal-message-date">Date</p>
          </div>
          <button id="close-modal" class="text-gray-500 hover:text-gray-700 flex-shrink-0">
            <i class="fas fa-times text-lg sm:text-xl"></i>
          </button>
        </div>
        
        <!-- Modal Body - Conversation -->
        <div class="p-3 sm:p-4 overflow-y-auto flex-grow scrollbar-thin" id="modal-conversation">
          <!-- Conversation messages will be loaded here -->
        </div>
        
        <!-- Modal Footer - Reply Form -->
        <div class="p-3 sm:p-4 border-t border-gray-200">
          <div class="flex gap-2">
            <textarea id="reply-input" class="flex-1 p-2 sm:p-2.5 border border-gray-300 rounded text-sm resize-none" placeholder="Type your reply..." rows="2"></textarea>
            <button class="bg-[#008080] text-white px-3 sm:px-4 py-2 rounded-md hover:bg-opacity-90 flex-shrink-0 h-fit text-sm sm:text-base" id="send-reply">
              <i class="fas fa-paper-plane hidden sm:inline mr-2"></i> <span class="hidden sm:inline">Send</span><span class="sm:hidden">Send</span>
            </button>
          </div>
        </div>
      </div>
    </div>


  
  <script src="tailwind.js"></script>

</body>
</html>