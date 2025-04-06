<?php
//messages/send_reply.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Database connection
require_once '../../db_connect.php'; // Adjust path as needed

// Get employee ID from session
$employee_id = $_SESSION['user_id'];

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

// Determine if this user should be receiver2 or receiver3
// First check if there are any messages in this chat room with receiver2 or receiver3
$check_query = "
    SELECT receiver2, receiver3 
    FROM chat_messages 
    WHERE chatRoomId = '$chatRoomId' 
    AND (receiver2 IS NOT NULL OR receiver3 IS NOT NULL)
    LIMIT 1
";

$check_result = $conn->query($check_query);

$receiver_field = "receiver"; // Default
$other_receiver_field = null;
$other_receiver_value = null;

if ($check_result && $check_result->num_rows > 0) {
    $row = $check_result->fetch_assoc();
    
    // If this employee was previously receiver2, use receiver2
    if ($row['receiver2'] == $employee_id) {
        $receiver_field = "receiver2";
        $other_receiver_field = "receiver3";
        $other_receiver_value = $row['receiver3'];
    } 
    // If this employee was previously receiver3, use receiver3
    elseif ($row['receiver3'] == $employee_id) {
        $receiver_field = "receiver3";
        $other_receiver_field = "receiver2";
        $other_receiver_value = $row['receiver2'];
    }
    // If neither, but receiver2 is set, use receiver3
    elseif ($row['receiver2'] !== null) {
        $receiver_field = "receiver3";
        $other_receiver_field = "receiver2";
        $other_receiver_value = $row['receiver2'];
    }
    // If neither, but receiver3 is set, use receiver2
    elseif ($row['receiver3'] !== null) {
        $receiver_field = "receiver2";
        $other_receiver_field = "receiver3";
        $other_receiver_value = $row['receiver3'];
    }
    // Otherwise, use receiver2
    else {
        $receiver_field = "receiver2";
    }
} else {
    // No previous messages with receiver2/3, so use receiver2
    $receiver_field = "receiver2";
}

// Prepare the SQL query with the correct receiver field
if ($other_receiver_field && $other_receiver_value) {
    $query = "
        INSERT INTO chat_messages (chatId, sender, receiver, $receiver_field, $other_receiver_field, message, status, chatRoomId)
        VALUES ('$chatId', '$employee_id', '$receiverId', '$employee_id', '$other_receiver_value', '$message', 'sent', '$chatRoomId')
    ";
} else {
    $query = "
        INSERT INTO chat_messages (chatId, sender, receiver, $receiver_field, message, status, chatRoomId)
        VALUES ('$chatId', '$employee_id', '$receiverId', '$employee_id', '$message', 'sent', '$chatRoomId')
    ";
}

if ($conn->query($query) === TRUE) {
    // Get the newly inserted message
    $new_message_query = "
        SELECT 
            chatId,
            sender,
            receiver,
            receiver2,
            receiver3,
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