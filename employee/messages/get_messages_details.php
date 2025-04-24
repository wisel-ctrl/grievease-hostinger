<?php
// messages/get_messages_details.php

// Set timezone to Philippines first
date_default_timezone_set('Asia/Manila');

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

// Get the other participant's details (customer or admin)
$user_query = "
    SELECT 
        u.id,
        CONCAT(u.first_name, ' ', u.last_name) AS name,
        u.email,
        u.user_type
    FROM chat_messages cm
    JOIN users u ON cm.sender = u.id
    WHERE cm.chatRoomId = '$chatRoomId'
    AND cm.sender != '$employee_id'
    ORDER BY cm.timestamp ASC
    LIMIT 1
";

$user_result = $conn->query($user_query);

// Fallback: If no messages from others, check recipients
if ($user_result === false || $user_result->num_rows === 0) {
    $user_query = "
        SELECT 
            u.id,
            CONCAT(u.first_name, ' ', u.last_name) AS name,
            u.email,
            u.user_type
        FROM chat_recipients cr
        JOIN users u ON cr.userId = u.id
        WHERE cr.chatRoomId = '$chatRoomId'
        AND cr.userId != '$employee_id'
        LIMIT 1
    ";
    $user_result = $conn->query($user_query);
}

if ($user_result === false || $user_result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'No participant found']);
    exit();
}

$userInfo = $user_result->fetch_assoc();
// Label admins explicitly
if ($userInfo['user_type'] == 1) {
    $userInfo['name'] = '[Admin] ' . $userInfo['name'];
}

// Get all messages in the conversation with timezone conversion
$messages_query = "
    SELECT 
        cm.chatId,
        cm.sender,
        cm.message,
        CONVERT_TZ(cm.timestamp, '+00:00', '+08:00') as timestamp,
        cm.messageType,
        cm.attachmentUrl,
        cr.status AS recipient_status,
        CASE
            WHEN u.user_type = 1 THEN CONCAT('[Admin] ', u.first_name, ' ', u.last_name)
            WHEN cm.sender = '$employee_id' THEN 'You (Employee)'
            ELSE CONCAT(u.first_name, ' ', u.last_name)
        END AS sender_name
    FROM chat_messages cm
    LEFT JOIN chat_recipients cr ON cm.chatId = cr.chatId AND cr.userId = '$employee_id'
    JOIN users u ON cm.sender = u.id
    WHERE cm.chatRoomId = '$chatRoomId'
    AND (cr.userId = '$employee_id' OR u.user_type = 1)  -- Include admin messages
    ORDER BY cm.timestamp ASC
";

$messages_result = $conn->query($messages_query);

if ($messages_result === false) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit();
}

$messages = [];
while ($row = $messages_result->fetch_assoc()) {
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

// Mark messages as read (only those where employee is recipient)
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