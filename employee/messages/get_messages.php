<?php
//messages/get_messages.php
session_start();
header('Content-Type: application/json');

// Add this at the top of your get_messages.php file right after session_start()
error_log("Employee ID: " . $_SESSION['user_id'] . ", User Type: " . $_SESSION['user_type']);

// Check if user is logged in as employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Database connection
require_once '../../db_connect.php'; // Adjust path as needed

// Get employee ID from session
$employee_id = $_SESSION['user_id'];

// Prepare filter conditions
$filter_condition = "";
$search_condition = "";

// Handle filter parameter
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    
    switch ($filter) {
        case 'unread':
            $filter_condition = " AND (cm.status = 'sent' AND (cm.receiver2 = '$employee_id' OR cm.receiver3 = '$employee_id'))";
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

// Get the most recent message for each chat room where employee is a participant
// Replace your existing query in get_messages.php with this:
$query = "
    SELECT 
        cm.chatRoomId,
        cm.sender,
        cm.receiver,
        cm.receiver2,
        cm.receiver3,
        cm.message,
        cm.timestamp,
        cm.status,
        CONCAT(u.first_name, ' ', u.last_name) AS sender_name,
        u.email AS sender_email,
        (
            SELECT COUNT(*) 
            FROM chat_messages 
            WHERE chatRoomId = cm.chatRoomId 
            AND (receiver2 = '$employee_id' OR receiver3 = '$employee_id')
            AND status = 'sent'
        ) AS unread_count
    FROM chat_messages cm
    JOIN users u ON cm.sender = u.id
    WHERE cm.sender != 'bot'
    AND (
        cm.receiver2 = '$employee_id' OR 
        cm.receiver3 = '$employee_id'
    )
    GROUP BY cm.chatRoomId
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
    if ($row['sender'] == $employee_id) {
        $row['sender_name'] = 'You (Employee)';
        $row['sender_email'] = $_SESSION['email'] ?? 'employee@grievease.com'; // Use session email if available
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