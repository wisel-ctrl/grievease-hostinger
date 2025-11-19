<?php
// Start output buffering at the very top
ob_start();

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Then start session
session_start();

// Set headers after output buffering
header('Content-Type: application/json');

try {
    // Check if user is logged in as admin
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
        throw new Exception('Unauthorized access');
    }

    // Database connection
    require_once '../../db_connect.php';

    // Get admin ID from session
    $admin_id = $_SESSION['user_id'];

    // Validate chatRoomId
    if (!isset($_GET['chatRoomId']) || empty($_GET['chatRoomId'])) {
        throw new Exception('Chat room ID is required');
    }

    $chatRoomId = $conn->real_escape_string($_GET['chatRoomId']);

    // Get user info (the customer) by finding other participants in the chat room
    $user_query = "
        SELECT 
            u.id,
            CONCAT(u.first_name, ' ', u.last_name) AS name,
            u.email,
            CONCAT('../../profile_picture/', u.profile_picture) AS profile_picture,
            u.first_name,
            u.last_name
        FROM users u
        JOIN (
            SELECT DISTINCT cr.userId
            FROM chat_messages cm
            JOIN chat_recipients cr ON cm.chatId = cr.chatId
            WHERE cm.chatRoomId = '$chatRoomId'
            AND cr.userId != '$admin_id'
        ) other_users ON u.id = other_users.userId
        WHERE u.user_type = 3
    ";

    $user_result = $conn->query($user_query);

    if ($user_result === false || $user_result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit();
    }

    $userInfo = $user_result->fetch_assoc();

    // Get all messages in the conversation with timezone conversion
    $messages_query = "
        SELECT 
            cm.chatId,
            cm.sender,
            cm.message,
            CONVERT_TZ(cm.timestamp, '+00:00', '+08:00') as timestamp,
            cm.status,
            cm.messageType,
            cm.attachmentUrl,
            cm.chatRoomId,
            u.user_type as sender_type
        FROM chat_messages cm
        JOIN chat_recipients cr ON cm.chatId = cr.chatId
        JOIN users u ON cm.sender = u.id
        WHERE cm.chatRoomId = '$chatRoomId'
        AND cr.userId = '$admin_id'
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

    // Mark all messages as read where admin is the recipient
    $update_query = "
        UPDATE chat_messages cm
        JOIN chat_recipients cr ON cm.chatId = cr.chatId
        SET cm.status = 'read'
        WHERE cm.chatRoomId = '$chatRoomId'
        AND cr.userId = '$admin_id'
        AND cm.sender != '$admin_id'
        AND cm.status = 'sent'
    ";

    $conn->query($update_query);

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'userInfo' => $userInfo,
        'messages' => $messages
    ]);

} catch (Exception $e) {
    // Clean any output that might have been sent
    ob_end_clean();

    // Return proper JSON error
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>