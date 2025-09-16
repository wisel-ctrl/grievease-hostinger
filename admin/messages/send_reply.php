<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Database connection
require_once '../../db_connect.php'; // Adjust path as needed

// Get admin ID from session
$admin_id = $_SESSION['user_id'];

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['chatRoomId']) || !isset($data['receiverId']) || !isset($data['message'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$chatRoomId = $conn->real_escape_string($data['chatRoomId']);
$receiverId = $conn->real_escape_string($data['receiverId']);
$message = $conn->real_escape_string($data['message']);

// Start transaction
$conn->begin_transaction();

try {
    // Generate a unique chat ID with retry mechanism
    $maxRetries = 5;
    $chatId = null;
    
    for ($i = 0; $i < $maxRetries; $i++) {
        $chatId = uniqid('chat_', true) . '_' . mt_rand(1000, 9999);
        
        // Check if this chatId already exists
        $check_query = "SELECT chatId FROM chat_messages WHERE chatId = '$chatId' LIMIT 1";
        $check_result = $conn->query($check_query);
        
        if ($check_result->num_rows == 0) {
            break; // Unique ID found
        }
        
        if ($i == $maxRetries - 1) {
            throw new Exception('Unable to generate unique chat ID after ' . $maxRetries . ' attempts');
        }
        
        usleep(1000); // Wait 1ms before retry
    }
    
    // Insert the message into chat_messages
    $message_query = "
        INSERT INTO chat_messages (chatId, sender, message, status, chatRoomId, messageType)
        VALUES ('$chatId', '$admin_id', '$message', 'read', '$chatRoomId', 'text')
    ";
    
    if (!$conn->query($message_query)) {
        throw new Exception('Failed to insert message: ' . $conn->error);
    }
    
    // Add entries to chat_recepients for both sender and receiver
    $recipient_query_sender = "
        INSERT INTO chat_recipients (chatId, userId, status)
        VALUES ('$chatId', '$admin_id', 'read')
    ";
    
    $recipient_query_receiver = "
        INSERT INTO chat_recipients (chatId, userId, status)
        VALUES ('$chatId', '$receiverId', 'read')
    ";
    
    $conn->query($recipient_query_sender);
    $conn->query($recipient_query_receiver);
    
    // Commit transaction
    $conn->commit();
    
    // Get the newly inserted message
    $new_message_query = "
        SELECT 
            chatId,
            sender,
            message,
            timestamp,
            status,
            messageType,
            attachmentUrl
        FROM chat_messages
        WHERE chatId = '$chatId'
    ";
    
    $result = $conn->query($new_message_query);
    $new_message = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Reply sent successfully',
        'data' => $new_message
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'error' => 'Error sending reply: ' . $e->getMessage()
    ]);
}

$conn->close();
?>