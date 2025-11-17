<?php
session_start();
header('Content-Type: application/json');

// -------------------------------------------------
// 1. AUTH CHECK
// -------------------------------------------------
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

require_once '../../db_connect.php';
$admin_id = $_SESSION['user_id'];

// -------------------------------------------------
// 2. GET + VALIDATE INPUT
// -------------------------------------------------
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['chatRoomId']) || empty($data['receiverId']) || !isset($data['message'])) {
    echo json_encode(['success' => false, 'error' => 'Missing fields']);
    exit();
}

$chatRoomId = $conn->real_escape_string($data['chatRoomId']);
$receiverId = $conn->real_escape_string($data['receiverId']);
$message    = trim($data['message']);

if ($message === '') {
    echo json_encode(['success' => false, 'error' => 'Empty message']);
    exit();
}

// -------------------------------------------------
// 3. GENERATE GUARANTEED UNIQUE chatId
// -------------------------------------------------
function generateUniqueChatId($conn) {
    do {
        $chatId = 'chat_' . bin2hex(random_bytes(16)) . '_' . substr(microtime(true) * 10000, -6);
        $exists = $conn->query("SELECT 1 FROM chat_messages WHERE chatId = '$chatId' LIMIT 1");
    } while ($exists && $exists->num_rows > 0);
    return $chatId;
}

$chatId = generateUniqueChatId($conn);

// -------------------------------------------------
// 4. SEND MESSAGE (transaction + INSERT IGNORE)
// -------------------------------------------------
$conn->autocommit(false);

try {
    $msg = $conn->real_escape_string($message);

    // Insert the actual message
    $sql = "INSERT INTO chat_messages 
            (chatId, sender, message, status, chatRoomId, messageType, timestamp)
            VALUES ('$chatId', '$admin_id', '$msg', 'read', '$chatRoomId', 'text', NOW())";
    
    if (!$conn->query($sql)) {
        throw new Exception("Message insert failed: " . $conn->error);
    }

    // Recipients – ignore duplicates (safe even if row already exists)
    $conn->query("INSERT IGNORE INTO chat_recipients (chatId, userId, status) VALUES ('$chatId', '$admin_id', 'read')");
    $conn->query("INSERT IGNORE INTO chat_recipients (chatId, userId, status) VALUES ('$chatId', '$receiverId', 'sent')");

    $conn->commit();
    $conn->autocommit(true);

    // Return the new message
    $res = $conn->query("SELECT chatId, sender, message, timestamp, status, messageType, attachmentUrl 
                         FROM chat_messages WHERE chatId = '$chatId'");
    $newMsg = $res->fetch_assoc();

    // Convert to Philippine time
    $newMsg['timestamp'] = date('Y-m-d H:i:s', strtotime($newMsg['timestamp'] . ' +8 hours'));

    echo json_encode([
        'success' => true,
        'data'    => $newMsg
    ]);

} catch (Exception $e) {
    $conn->rollback();
    $conn->autocommit(true);
    
    // Log real error for you (check error_log or var/log/php-errors.log)
    error_log("send_reply.php ERROR: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'error'   => 'Failed to send reply. Please try again.'
    ]);
}

$conn->close();
?>