<?php
// messages/get_messages_details.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

require_once '../../db_connect.php';

$employee_id = $_SESSION['user_id'];

if (!isset($_GET['chatRoomId']) || empty($_GET['chatRoomId'])) {
    echo json_encode(['success' => false, 'error' => 'Chat room ID is required']);
    exit();
}

$chatRoomId = $conn->real_escape_string($_GET['chatRoomId']);

// Get the customer details from the first message where sender is not the employee
$user_query = "
    SELECT 
        u.id,
        CONCAT(u.first_name, ' ', u.last_name) AS name,
        u.email
    FROM chat_messages cm
    JOIN users u ON cm.sender = u.id
    WHERE cm.chatRoomId = '$chatRoomId'
    AND cm.sender != '$employee_id'
    ORDER BY cm.timestamp ASC
    LIMIT 1
";

$user_result = $conn->query($user_query);

if ($user_result === false || $user_result->num_rows === 0) {
    // If no messages from customer, try getting recipient info as fallback
    $user_query = "
        SELECT 
            u.id,
            CONCAT(u.first_name, ' ', u.last_name) AS name,
            u.email
        FROM chat_recipients cr
        JOIN users u ON cr.userId = u.id
        WHERE cr.chatRoomId = '$chatRoomId'
        AND cr.userId != '$employee_id'
        LIMIT 1
    ";
    
    $user_result = $conn->query($user_query);
    
    if ($user_result === false || $user_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Customer not found']);
        exit();
    }
}

$userInfo = $user_result->fetch_assoc();

// Get all messages in the conversation
$messages_query = "
    SELECT 
        cm.chatId,
        cm.sender,
        cm.message,
        cm.timestamp,
        cm.messageType,
        cm.attachmentUrl,
        cr.status AS recipient_status
    FROM chat_messages cm
    JOIN chat_recipients cr ON cm.chatId = cr.chatId
    WHERE cm.chatRoomId = '$chatRoomId'
    AND cr.userId = '$employee_id'
    ORDER BY cm.timestamp ASC
";

$messages_result = $conn->query($messages_query);

if ($messages_result === false) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit();
}

$messages = [];
while ($row = $messages_result->fetch_assoc()) {
    $messages[] = $row;
}

// Mark all messages as read
$update_query = "
    UPDATE chat_recipients
    SET status = 'read'
    WHERE chatId IN (SELECT chatId FROM chat_messages WHERE chatRoomId = '$chatRoomId')
    AND userId = '$employee_id'
    AND status IN ('sent', 'delivered')
";

$conn->query($update_query);

echo json_encode([
    'success' => true,
    'userInfo' => $userInfo,
    'messages' => $messages
]);

$conn->close();
?>