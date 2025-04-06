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

// Generate a unique chat ID
$chatId = uniqid('chat_', true);

// Insert the message
$query = "
    INSERT INTO chat_messages (chatId, sender, receiver, message, status, chatRoomId)
    VALUES ('$chatId', '$admin_id', '$receiverId', '$message', 'sent', '$chatRoomId')
";

if ($conn->query($query) === TRUE) {
    // Get the newly inserted message
    $new_message_query = "
        SELECT 
            chatId,
            sender,
            receiver,
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
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Error sending reply: ' . $conn->error
    ]);
}

$conn->close();
?>