<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Database connection
require_once '../../db_connect.php'; // Adjust path as needed

// Get admin ID from session
$admin_id = $_SESSION['user_id'];

// Prepare filter conditions
$filter_condition = "";
$search_condition = "";

// Handle filter parameter
if (isset($_GET['filter'])) {
    $filter = $_GET['filter'];
    
    switch ($filter) {
        case 'unread':
            $filter_condition = " AND (cm.status = 'sent' AND cm.receiver = '$admin_id')";
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
        cm.receiver,
        cm.message,
        cm.timestamp,
        cm.status,
        CONCAT(u.first_name, ' ', u.last_name) AS sender_name,
        u.email AS sender_email,
        NULL AS sender_profile_picture,
        (
            SELECT COUNT(*) 
            FROM chat_messages 
            WHERE chatRoomId = cm.chatRoomId 
            AND receiver = '$admin_id' 
            AND status = 'sent'
        ) AS unread_count
    FROM chat_messages cm
    INNER JOIN (
        SELECT chatRoomId, MAX(timestamp) as max_timestamp
        FROM chat_messages
        WHERE sender = '$admin_id' OR receiver = '$admin_id'
        GROUP BY chatRoomId
    ) latest ON cm.chatRoomId = latest.chatRoomId AND cm.timestamp = latest.max_timestamp
    LEFT JOIN users u ON (
        CASE 
            WHEN cm.sender = '$admin_id' THEN cm.receiver
            ELSE cm.sender
        END = u.id
    )
    WHERE u.user_type = 3 $filter_condition $search_condition
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

$conn->close();
?>