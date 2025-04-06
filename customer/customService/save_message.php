<?php
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

$sender = $_SESSION['user_id']; // Get sender ID from session

// Fetch the branch_loc of the sender
$senderQuery = $conn->query("SELECT branch_loc FROM users WHERE id = $sender");
$senderData = $senderQuery->fetch_assoc();
$branch_loc = $senderData['branch_loc'];

// Fetch all admin users (user_type = 1)
$adminQuery = $conn->query("SELECT id FROM users WHERE user_type = 1");
$admins = [];
while ($row = $adminQuery->fetch_assoc()) {
    $admins[] = $row['id'];
}

// If no admins found, return an error
if (empty($admins)) {
    http_response_code(400);
    echo json_encode(['error' => 'No admin users found']);
    exit;
}

// Fetch receiver2 and receiver3 based on branch_loc
$receiverQuery = $conn->query("SELECT id FROM users WHERE user_type = 2 AND branch_loc = '$branch_loc'");
$receivers = [];
while ($row = $receiverQuery->fetch_assoc()) {
    $receivers[] = $row['id'];
}

// Ensure there are exactly two receivers
if (count($receivers) < 2) {
    http_response_code(400);
    echo json_encode(['error' => 'Not enough receivers found']);
    exit;
}

$receiver2 = $receivers[0];
$receiver3 = $receivers[1];

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
$status = 'sent';

// Detect if it's an automated reply
$isAutomatedReply = isset($data['automated']) && $data['automated'] === true;

// Set sender and receiver based on message type
if ($isAutomatedReply) {
    // For automated replies, set sender to 'bot' and receiver to the logged-in user
    $sender = 'bot'; // Fixed sender for automated replies
    $receiver = $_SESSION['user_id']; // The logged-in user is the receiver
} else {
    // For normal messages, the logged-in user is the sender, and the first admin is the receiver
    $receiver = $admins[0]; // Assume first admin receives the message
}

// Prepare statement to insert message
$stmt = $conn->prepare("INSERT INTO chat_messages (chatId, sender, receiver, receiver2, receiver3, message, timestamp, status, chatRoomId, messageType, attachmentUrl) 
                       VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)");

// Insert the message
$stmt->bind_param("ssssssssss", 
    $chatId, 
    $sender, 
    $receiver, 
    $receiver2, 
    $receiver3, 
    $data['message'], 
    $status, 
    $chatRoomId, 
    $messageType, 
    $attachmentUrl
);

// Execute the insert statement
if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => $isAutomatedReply ? 'Automated reply sent successfully' : 'Message sent successfully',
        'data' => [
            'chatId' => $chatId,
            'sender' => $sender,  // Now correctly assigned
            'receiver' => $receiver, // Now correctly assigned
            'receiver2' => $receiver2,
            'receiver3' => $receiver3,
            'message' => $data['message'],
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => $status,
            'chatRoomId' => $chatRoomId,
            'messageType' => $messageType
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to send message', 'details' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>