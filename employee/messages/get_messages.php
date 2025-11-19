<?php
// messages/get_messages.php - SIMPLEST VERSION
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

require_once '../../db_connect.php';

$employee_id = $_SESSION['user_id'];

// Get all unique chat rooms where employee has messages
$query = "
    SELECT DISTINCT cm.chatRoomId
    FROM chat_messages cm
    JOIN chat_recipients cr ON cm.chatId = cr.chatId
    WHERE cr.userId = '$employee_id'
    ORDER BY cm.timestamp DESC
";

$room_result = $conn->query($query);
$conversations = [];

if ($room_result && $room_result->num_rows > 0) {
    while ($room_row = $room_result->fetch_assoc()) {
        $chatRoomId = $room_row['chatRoomId'];
        
        // Get the latest message in this chat room
        $message_query = "
            SELECT cm.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as sender_name,
                   u.user_type as sender_type
            FROM chat_messages cm
            JOIN users u ON cm.sender = u.id
            WHERE cm.chatRoomId = '$chatRoomId'
            ORDER BY cm.timestamp DESC 
            LIMIT 1
        ";
        
        $message_result = $conn->query($message_query);
        $latest_message = $message_result->fetch_assoc();
        
        // Find the customer in this chat room
        $customer_query = "
            SELECT DISTINCT u.id, CONCAT(u.first_name, ' ', u.last_name) as name
            FROM chat_messages cm
            JOIN users u ON cm.sender = u.id
            WHERE cm.chatRoomId = '$chatRoomId'
            AND u.user_type = 3
            LIMIT 1
        ";
        
        $customer_result = $conn->query($customer_query);
        $customer = $customer_result->fetch_assoc();
        
        // If no customer found, use the first non-employee participant
        if (!$customer) {
            $fallback_query = "
                SELECT DISTINCT u.id, CONCAT(u.first_name, ' ', u.last_name) as name
                FROM chat_messages cm
                JOIN users u ON cm.sender = u.id
                WHERE cm.chatRoomId = '$chatRoomId'
                AND u.id != '$employee_id'
                LIMIT 1
            ";
            $fallback_result = $conn->query($fallback_query);
            $customer = $fallback_result->fetch_assoc();
        }
        
        // Get unread count
        $unread_query = "
            SELECT COUNT(*) as unread_count
            FROM chat_recipients cr
            JOIN chat_messages cm ON cr.chatId = cm.chatId
            WHERE cm.chatRoomId = '$chatRoomId'
            AND cr.userId = '$employee_id'
            AND cr.status IN ('sent', 'delivered')
        ";
        $unread_result = $conn->query($unread_query);
        $unread_count = $unread_result->fetch_assoc()['unread_count'];
        
        // Add message context
        $message_context = "";
        if ($latest_message['sender_type'] == 2) {
            $message_context = "You: ";
        } else if ($latest_message['sender_type'] == 1) {
            $message_context = "Admin: ";
        }
        
        $conversations[] = [
            'chatId' => $chatRoomId,
            'chatRoomId' => $chatRoomId,
            'sender' => $latest_message['sender'],
            'receiver' => $customer['id'],
            'message' => $message_context . $latest_message['message'],
            'timestamp' => $latest_message['timestamp'],
            'sender_name' => $latest_message['sender_name'],
            'customer_name' => $customer['name'],
            'customer_id' => $customer['id'],
            'unread_count' => $unread_count
        ];
    }
}

echo json_encode([
    'success' => true,
    'count' => count($conversations),
    'conversations' => $conversations
]);

$conn->close();
?>