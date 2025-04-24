<?php
// get_messages.php - Endpoint to retrieve chat messages

// Set timezone to Philippines first
date_default_timezone_set('Asia/Manila');

// Start session after timezone is set
session_start();

// Include database connection
require_once '../../db_connect.php';

// Set headers for AJAX requests
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

// Check if this is a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// IMPORTANT: We need to use the same chat room ID format as in save_message.php
// Always use user-based chat room ID (format: user_123)
$chatRoomId = 'user_' . $user_id;

// Prepare statement to retrieve messages with timezone conversion
$stmt = $conn->prepare("
    SELECT 
        m.chatId,
        m.sender,
        m.message,
        CONVERT_TZ(m.timestamp, '+00:00', '+08:00') as timestamp,
        m.status,
        m.messageType,
        m.attachmentUrl,
        m.chatRoomId,
        GROUP_CONCAT(r.userId) as recipients
    FROM chat_messages m
    LEFT JOIN chat_recipients r ON m.chatId = r.chatId
    WHERE m.chatRoomId = ?
    GROUP BY m.chatId
    ORDER BY m.timestamp ASC
");

$stmt->bind_param("s", $chatRoomId);
$stmt->execute();

// Get result
$result = $stmt->get_result();
$messages = [];

// Fetch all messages
while ($row = $result->fetch_assoc()) {
    // Convert recipients string to array
    if ($row['recipients']) {
        $row['recipients'] = explode(',', $row['recipients']);
    } else {
        $row['recipients'] = [];
    }
    
    // Validate and format timestamp
    if (!empty($row['timestamp'])) {
        $timestamp = strtotime($row['timestamp']);
        if ($timestamp === false) {
            // Invalid timestamp, use current time instead
            $row['timestamp'] = date('Y-m-d H:i:s');
        } else {
            // Format the timestamp for display
            $row['timestamp'] = date('Y-m-d H:i:s', $timestamp);
        }
    } else {
        // Empty timestamp, use current time
        $row['timestamp'] = date('Y-m-d H:i:s');
    }
    
    $messages[] = $row;
}

// Return messages
echo json_encode([
    'success' => true,
    'messages' => $messages
]);

$stmt->close();
$conn->close();
?>