<?php
session_start();
require_once '../../db_connect.php';

// Session timeout check
$session_timeout = 1800;
if (!isset($_SESSION['last_activity']) || (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../Landing_Page/login.php?timeout=1");
    exit();
}
$_SESSION['last_activity'] = time();

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's branch location
$userQuery = $conn->query("SELECT branch_loc FROM users WHERE id = $user_id");
$userData = $userQuery->fetch_assoc();
$branch_loc = $userData['branch_loc'];

// Validate message
if (!isset($data['message']) || empty(trim($data['message']))) {
    http_response_code(400);
    echo json_encode(['error' => 'Message cannot be empty']);
    exit;
}

// CHAT ROOM ID MANAGEMENT - KEY CHANGE
// Always use user-based chat room ID (format: user_123)
$chatRoomId = 'user_' . $user_id;

// Generate unique chat ID for the message
$chatId = uniqid('msg_');

$messageType = isset($data['messageType']) ? $data['messageType'] : 'text';
$attachmentUrl = isset($data['attachmentUrl']) ? $data['attachmentUrl'] : null;
$isAutomatedReply = isset($data['automated']) && $data['automated'] === true;

$sender = $isAutomatedReply ? 'bot' : $user_id;
$status = $isAutomatedReply ? 'read' : 'sent';

if ($isAutomatedReply) {
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM chat_messages WHERE chatRoomId = ? AND sender = 'bot'");
    $checkStmt->bind_param("s", $chatRoomId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Automated reply already sent']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();
}

$conn->begin_transaction();

try {
    // Insert message
    $messageStmt = $conn->prepare("INSERT INTO chat_messages (chatId, sender, message, timestamp, status, chatRoomId, messageType, attachmentUrl) 
                           VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)");
    
    $messageStmt->bind_param("sssssss", $chatId, $sender, $data['message'], $status, $chatRoomId, $messageType, $attachmentUrl);
    $messageStmt->execute();
    
    // Determine recipients
    $recipients = [];
    
    if ($isAutomatedReply) {
        $recipients[] = $user_id;
    } else {
        // Add admins
        $adminQuery = $conn->query("SELECT id FROM users WHERE user_type = 1");
        while ($row = $adminQuery->fetch_assoc()) {
            $recipients[] = $row['id'];
        }
        
        // Add branch employees
        $branchQuery = $conn->query("SELECT id FROM users WHERE user_type = 2 AND branch_loc = '$branch_loc'");
        while ($row = $branchQuery->fetch_assoc()) {
            $recipients[] = $row['id'];
        }
    }
    
    // Insert recipients
    if (!empty($recipients)) {
        $recipientStmt = $conn->prepare("INSERT INTO chat_recipients (chatId, userId, status) VALUES (?, ?, ?)");
        
        foreach ($recipients as $recipient) {
            $recipientStatus = $isAutomatedReply ? 'read' : 'sent';
            $recipientStmt->bind_param("sss", $chatId, $recipient, $recipientStatus);
            $recipientStmt->execute();
        }
        
        $recipientStmt->close();
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $isAutomatedReply ? 'Automated reply sent' : 'Message sent',
        'data' => [
            'chatId' => $chatId,
            'chatRoomId' => $chatRoomId,
            'sender' => $sender,
            'message' => $data['message'],
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message', 'details' => $e->getMessage()]);
}

$messageStmt->close();
$conn->close();

?>