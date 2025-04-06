
<?php
//messages/get_messages_details.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Database connection
require_once '../../db_connect.php'; // Adjust path as needed

// Get employee ID from session
$employee_id = $_SESSION['user_id'];

// Get chat room ID from request
if (!isset($_GET['chatRoomId']) || empty($_GET['chatRoomId'])) {
    echo json_encode(['success' => false, 'error' => 'Chat room ID is required']);
    exit();
}

$chatRoomId = $conn->real_escape_string($_GET['chatRoomId']);

// Get user info (the customer)
$user_query = "
    SELECT 
        u.id,
        CONCAT(u.first_name, ' ', u.last_name) AS name,
        u.email
    FROM users u
    INNER JOIN (
        SELECT 
            CASE 
                WHEN sender = '$employee_id' THEN 
                    CASE 
                        WHEN receiver IS NOT NULL THEN receiver
                        WHEN receiver2 IS NOT NULL THEN receiver2
                        WHEN receiver3 IS NOT NULL THEN receiver3
                    END
                ELSE sender
            END AS user_id
        FROM chat_messages
        WHERE chatRoomId = '$chatRoomId'
        LIMIT 1
    ) cm ON u.id = cm.user_id
    WHERE u.user_type = 3
";

$user_result = $conn->query($user_query);

if ($user_result === false || $user_result->num_rows === 0) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit();
}

$userInfo = $user_result->fetch_assoc();

// Get all messages in the conversation
$messages_query = "
    SELECT 
        chatId,
        sender,
        CASE 
            WHEN receiver2 = '$employee_id' THEN receiver2
            WHEN receiver3 = '$employee_id' THEN receiver3
            ELSE receiver
        END AS receiver,
        message,
        timestamp,
        status,
        messageType,
        attachmentUrl
    FROM chat_messages
    WHERE chatRoomId = '$chatRoomId'
    AND sender != 'bot'
    AND (
        sender = '$employee_id' 
        OR receiver = '$employee_id' 
        OR receiver2 = '$employee_id' 
        OR receiver3 = '$employee_id'
    )
    ORDER BY timestamp ASC
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

// Mark all messages as read where employee is the receiver
$update_query = "
    UPDATE chat_messages
    SET status = 'read'
    WHERE chatRoomId = '$chatRoomId'
    AND (receiver2 = '$employee_id' OR receiver3 = '$employee_id')
    AND status = 'sent'
";

$conn->query($update_query);

echo json_encode([
    'success' => true,
    'userInfo' => $userInfo,
    'messages' => $messages
]);

$conn->close();
?>