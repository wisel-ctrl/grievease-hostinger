<?php
// messages/send_reply.php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

require_once '../../db_connect.php';

$employee_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['chatRoomId']) || !isset($data['receiverId']) || !isset($data['message'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit();
}

$chatRoomId = $conn->real_escape_string($data['chatRoomId']);
$receiverId = $conn->real_escape_string($data['receiverId']);
$message = $conn->real_escape_string($data['message']);
$chatId = uniqid('chat_', true);

// Start transaction
$conn->begin_transaction();

try {
    // Insert into chat_messages
    $query = "INSERT INTO chat_messages (chatId, sender, message, chatRoomId, messageType, status) 
              VALUES (?, ?, ?, ?, 'text', 'sent')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $chatId, $employee_id, $message, $chatRoomId);
    $stmt->execute();
    
    // Insert into chat_recipients for the sender (status = 'read')
    $query = "INSERT INTO chat_recipients (chatId, userId, status) VALUES (?, ?, 'read')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $chatId, $employee_id);
    $stmt->execute();
    
    // Insert into chat_recipients for the receiver (status = 'sent')
    $query = "INSERT INTO chat_recipients (chatId, userId, status) VALUES (?, ?, 'sent')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $chatId, $receiverId);
    $stmt->execute();

    // Get admin user_id
$admin_query = "SELECT id FROM users WHERE user_type = 1 LIMIT 1";
$admin_result = $conn->query($admin_query);

if ($admin_result && $admin_result->num_rows > 0) {
    $admin = $admin_result->fetch_assoc();
    $adminId = $admin['id'];

    // Insert message for admin (status = 'sent')
    $query = "INSERT INTO chat_recipients (chatId, userId, status) VALUES (?, ?, 'sent')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $chatId, $adminId);
    $stmt->execute();
}

    
    $conn->commit();
    
    // Get the newly inserted message
    $new_message_query = "
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
        WHERE cm.chatId = '$chatId'
        AND cr.userId = '$employee_id'
    ";
    
    $result = $conn->query($new_message_query);
    $new_message = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Reply sent successfully',
        'data' => $new_message
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'error' => 'Error sending reply: ' . $e->getMessage()
    ]);
}

$conn->close();
?>