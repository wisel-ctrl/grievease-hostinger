<?php
// messages/get_messages.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

require_once '../../db_connect.php';

$employee_id = $_SESSION['user_id'];
$filter_condition = "";
$search_condition = "";

// Handle filter parameter
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    
    switch ($filter) {
        case 'unread':
            $filter_condition = " AND cr.status IN ('sent', 'delivered')";
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
            break;
    }
}

// Handle search parameter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search = $conn->real_escape_string($_GET['search']);
    $search_condition = " AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%')";
}

$query = "
    SELECT 
        cm.chatId,
        cm.chatRoomId,
        cm.sender,
        cm.message,
        cm.timestamp,
        cm.messageType,
        cm.attachmentUrl,
        cr.status AS recipient_status,
        CONCAT(u.first_name, ' ', u.last_name) AS sender_name,
        u.email AS sender_email,
        (
            SELECT CONCAT(cust.first_name, ' ', cust.last_name)
            FROM users cust
            WHERE cust.id = (
                SELECT DISTINCT sender 
                FROM chat_messages 
                WHERE chatRoomId = cm.chatRoomId AND sender != '$employee_id'
                LIMIT 1
            )
        ) AS customer_name,
        (
            SELECT COUNT(*) 
            FROM chat_recipients 
            WHERE chatId IN (SELECT chatId FROM chat_messages WHERE chatRoomId = cm.chatRoomId)
            AND userId = '$employee_id'
            AND status IN ('sent', 'delivered')
        ) AS unread_count
    FROM chat_messages cm
    JOIN (
        SELECT chatRoomId, MAX(timestamp) as latest_timestamp
        FROM chat_messages
        GROUP BY chatRoomId
    ) latest ON cm.chatRoomId = latest.chatRoomId AND cm.timestamp = latest.latest_timestamp
    JOIN chat_recipients cr ON cm.chatId = cr.chatId
    JOIN users u ON cm.sender = u.id
    WHERE cr.userId = '$employee_id'
    $filter_condition
    $search_condition
    ORDER BY cm.timestamp DESC
";

$result = $conn->query($query);

if ($result === false) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
    exit();
}

$conversations = [];
while ($row = $result->fetch_assoc()) {
    if ($row['sender'] == $employee_id) {
        $row['sender_name'] = 'You (Employee)';
        $row['sender_email'] = $_SESSION['email'] ?? 'employee@grievease.com';
    }
    
    $conversations[] = $row;
}

echo json_encode([
    'success' => true,
    'count' => count($conversations),
    'conversations' => $conversations
]);

$conn->close();
?>