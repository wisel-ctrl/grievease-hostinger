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

// Function to generate unique chat ID
function generateChatId($conn) {
    do {
        $chatId = 'chat_' . uniqid() . '_' . mt_rand(1000, 9999) . '_' . time();
        
        // Check if this ID already exists in chat_messages
        $check_query = "SELECT chatId FROM chat_messages WHERE chatId = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $chatId);
        $stmt->execute();
        $result = $stmt->get_result();
        
    } while ($result->num_rows > 0);
    
    return $chatId;
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

// Function to send reply with retry mechanism
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

// Send the reply with retry mechanism
$result = sendReplyWithRetry($conn, $chatRoomId, $employee_id, $receiverId, $message);

if ($result['success']) {
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
        WHERE cm.chatId = ?
        AND cr.userId = ?
    ";
    
    $stmt = $conn->prepare($new_message_query);
    $stmt->bind_param("ss", $result['chatId'], $employee_id);
    $stmt->execute();
    $result_msg = $stmt->get_result();
    $new_message = $result_msg->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Reply sent successfully',
        'data' => $new_message
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $result['error']
    ]);
}

$conn->close();
?>