<?php
// save_message.php - Endpoint to save messages to the database
// save_message.php - Endpoint to save messages to the database
session_start();

// Include database connection
require_once '../../db_connect.php';

// Check if session has expired
$session_timeout = 1800; // 30 minutes timeout
if (!isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../Landing_Page/login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time(); // Update session timestamp

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Allow only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id']; // Get user ID from session

// Fetch the branch_loc of the user
$userQuery = $conn->query("SELECT branch_loc FROM users WHERE id = $user_id");
$userData = $userQuery->fetch_assoc();
$branch_loc = $userData['branch_loc'];

// Validate message field
if (!isset($data['message']) || empty(trim($data['message']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Message cannot be empty']);
    exit;
}

// Generate unique chat ID if not provided
$chatId = isset($data['chatId']) ? $data['chatId'] : uniqid('chat_');
$chatRoomId = isset($data['chatRoomId']) ? $data['chatRoomId'] : $chatId;
$messageType = isset($data['messageType']) ? $data['messageType'] : 'text';
$attachmentUrl = isset($data['attachmentUrl']) ? $data['attachmentUrl'] : null;

// Detect if it's an automated reply
$isAutomatedReply = isset($data['automated']) && $data['automated'] === true;

// Set the sender and status based on whether it's an automated reply or not
$sender = $isAutomatedReply ? 'bot' : $user_id;
$status = $isAutomatedReply ? 'read' : 'sent'; // Use 'read' for bot messages, 'sent' for user messages

// Check if this is an automated reply and if we should send it
if ($isAutomatedReply) {
    // Check if there are already automated replies in this chat room
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM chat_messages WHERE chatRoomId = ? AND sender = 'bot'");
    $checkStmt->bind_param("s", $chatRoomId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    
    // If there are already automated replies, don't send another one
    if ($row['count'] > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Automated reply already sent for this conversation'
        ]);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();
}

// Begin transaction
$conn->begin_transaction();

try {
    // Insert the message into chat_messages table
    $messageStmt = $conn->prepare("INSERT INTO chat_messages (chatId, sender, message, timestamp, status, chatRoomId, messageType, attachmentUrl) 
                           VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)");
    
    $messageStmt->bind_param("sssssss", 
        $chatId, 
        $sender, 
        $data['message'], 
        $status, // This will be 'read' for bot messages, 'sent' for user messages
        $chatRoomId, 
        $messageType, 
        $attachmentUrl
    );
    
    // Execute the message insert
    $messageStmt->execute();
    
    // Determine recipients based on message type
    $recipients = [];
    
    if ($isAutomatedReply) {
        // For automated replies, the recipient is the logged-in user
        $recipients[] = $user_id;
    } else {
        // Add all admin users (user_type = 1) as recipients
        $adminQuery = $conn->query("SELECT id FROM users WHERE user_type = 1");
        while ($row = $adminQuery->fetch_assoc()) {
            $recipients[] = $row['id'];
        }
        
        // Add all branch employees (user_type = 2) with matching branch_loc
        $branchQuery = $conn->query("SELECT id FROM users WHERE user_type = 2 AND branch_loc = '$branch_loc'");
        while ($row = $branchQuery->fetch_assoc()) {
            $recipients[] = $row['id'];
        }
    }
    
    // Insert into chat_recipients table for each recipient
    if (!empty($recipients)) {
        $recipientStmt = $conn->prepare("INSERT INTO chat_recipients (chatId, userId, status) VALUES (?, ?, ?)");
        
        foreach ($recipients as $recipient) {
            // Use 'read' status for bot messages, 'sent' for user messages
            $recipientStatus = $isAutomatedReply ? 'read' : 'sent';
            $recipientStmt->bind_param("sss", $chatId, $recipient, $recipientStatus);
            $recipientStmt->execute();
        }
        
        $recipientStmt->close();
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $isAutomatedReply ? 'Automated reply sent successfully' : 'Message sent successfully',
        'data' => [
            'chatId' => $chatId,
            'sender' => $sender,
            'recipients' => $recipients,
            'message' => $data['message'],
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => $status,
            'chatRoomId' => $chatRoomId,
            'messageType' => $messageType
        ]
    ]);
    
} catch (Exception $e) {
    // Roll back transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message', 'details' => $e->getMessage()]);
}

$messageStmt->close();
$conn->close();
?>