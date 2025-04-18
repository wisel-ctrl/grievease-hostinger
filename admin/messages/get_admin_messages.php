<?php

ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    session_start();


// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Database connection
require_once '../../db_connect.php'; // Adjust path as needed

// Get admin ID from session
$admin_id = $_SESSION['user_id'];

$debug_query = "
    SELECT COUNT(*) as chat_count
    FROM chat_recipients cr
    WHERE cr.userId = '$admin_id'
";
$debug_result = $conn->query($debug_query);
$debug_row = $debug_result->fetch_assoc();
error_log("Admin chat count: " . $debug_row['chat_count']);

// Debug query
$debug_rooms = "
    SELECT DISTINCT cm.chatRoomId
    FROM chat_messages cm
    JOIN chat_recipients cr ON cm.chatId = cr.chatId
    WHERE cr.userId = '$admin_id'
";
$room_result = $conn->query($debug_rooms);
while ($room = $room_result->fetch_assoc()) {
    error_log("Found chatroom: " . $room['chatRoomId']);
}

// Prepare filter conditions
$filter_condition = "";
$search_condition = "";

// Handle filter parameter
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    
    switch ($filter) {
        case 'unread':
            $filter_condition = " AND (cm.status = 'sent' AND cr.userId = '$admin_id')";
            break;
        case 'today':
            $filter_condition = " AND DATE(cm.timestamp) = CURDATE()";
            break;
        case 'week':
            $filter_condition = " AND YEARWEEK(cm.timestamp, 1) = YEARWEEK(CURDATE(), 1)";
            break;
        case 'month':
            $filter_condition = " AND MONTH(cm.timestamp) = MONTH(CURDATE()) AND YEAR(cm.timestamp) = YEAR(CURDATE())";
            break;
        default:
            // 'all' or invalid filter - no additional condition
            break;
    }
}

// Handle search parameter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $search_condition = " AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%')";
}

// Get the most recent message for each chat room where admin is a participant
$query = "
    SELECT 
        cm.chatRoomId,
        cm.sender,
        cm.message,
        cm.timestamp,
        cm.status,
        cm.messageType,
        cm.attachmentUrl,
        CONCAT(u.first_name, ' ', u.last_name) AS sender_name,
        u.email AS sender_email,
        (
            SELECT COUNT(*) 
            FROM chat_messages cm2
            JOIN chat_recipients cr2 ON cm2.chatId = cr2.chatId
            WHERE cm2.chatRoomId = cm.chatRoomId 
            AND cr2.userId = '$admin_id' 
            AND cm2.sender != '$admin_id'
            AND cr2.status = 'sent'
        ) AS unread_count
    FROM chat_messages cm
    JOIN chat_recipients cr ON cm.chatId = cr.chatId
    JOIN users u ON cm.sender = u.id
    WHERE cr.userId = '$admin_id'
    AND cm.chatId IN (
        SELECT chatId 
        FROM chat_recipients 
        WHERE userId = '$admin_id'
    )
    AND cm.timestamp = (
        SELECT MAX(timestamp) 
        FROM chat_messages 
        WHERE chatRoomId = cm.chatRoomId
    )
    $filter_condition $search_condition
    ORDER BY cm.timestamp DESC
";

$result = $conn->query($query);

if ($result === false) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit();
}

$conversations = [];
while ($row = $result->fetch_assoc()) {
    // Ensure the sender name is properly formatted
    if ($row['sender'] == $admin_id) {
        $row['sender_name'] = 'You (Admin)';
        $row['sender_email'] = 'admin@grievease.com'; // Replace with actual admin email if needed
    }
    
    $conversations[] = $row;
}

echo json_encode([
    'success' => true,
    'count' => count($conversations),
    'conversations' => $conversations
]);
} catch (Exception $e) {
    // Clear any output that might have been sent before the error
    ob_clean();
    
    // Output error as JSON
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();

ob_end_flush();
?>