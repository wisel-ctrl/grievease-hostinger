<?php
//get_admin_messages.php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

try {
    session_start();

    // Set timezone to Philippines
    date_default_timezone_set('Asia/Manila');

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
                // Use Philippine timezone directly without CONVERT_TZ
                $filter_condition = " AND DATE(CONVERT_TZ(cm.timestamp, '+00:00', '+08:00')) = DATE(CONVERT_TZ(NOW(), '+00:00', '+08:00'))";
                break;
            case 'week':
                // Adjust for week considering Philippine timezone
                $filter_condition = " AND YEARWEEK(CONVERT_TZ(cm.timestamp, '+00:00', '+08:00'), 1) = YEARWEEK(CURDATE(), 1)";
                break;
            case 'month':
                // Adjust for month considering Philippine timezone
                $filter_condition = " AND MONTH(CONVERT_TZ(cm.timestamp, '+00:00', '+08:00')) = MONTH(CURDATE()) 
                                      AND YEAR(CONVERT_TZ(cm.timestamp, '+00:00', '+08:00')) = YEAR(CURDATE())";
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
        CONVERT_TZ(cm.timestamp, '+00:00', '+08:00') as timestamp,
        cm.status,
        cm.messageType,
        cm.attachmentUrl,
        (
            SELECT CONCAT(u.first_name, ' ', u.last_name)
            FROM chat_recipients cr2
            JOIN users u ON cr2.userId = u.id
            WHERE cr2.chatId IN (
                SELECT chatId 
                FROM chat_messages 
                WHERE chatRoomId = cm.chatRoomId
            )
            AND u.user_type = 3
            LIMIT 1
        ) AS sender_name,
        (
            SELECT u.email
            FROM chat_recipients cr2
            JOIN users u ON cr2.userId = u.id
            WHERE cr2.chatId IN (
                SELECT chatId 
                FROM chat_messages 
                WHERE chatRoomId = cm.chatRoomId
            )
            AND u.user_type = 3
            LIMIT 1
        ) AS sender_email,
        (
            SELECT CONCAT('../../profile_picture/', u.profile_picture)
            FROM chat_recipients cr2
            JOIN users u ON cr2.userId = u.id
            WHERE cr2.chatId IN (
                SELECT chatId 
                FROM chat_messages 
                WHERE chatRoomId = cm.chatRoomId
            )
            AND u.user_type = 3
            LIMIT 1
        ) AS sender_profile_picture,
        (
            SELECT u.first_name
            FROM chat_recipients cr2
            JOIN users u ON cr2.userId = u.id
            WHERE cr2.chatId IN (
                SELECT chatId 
                FROM chat_messages 
                WHERE chatRoomId = cm.chatRoomId
            )
            AND u.user_type = 3
            LIMIT 1
        ) AS sender_first_name,
        (
            SELECT u.last_name
            FROM chat_recipients cr2
            JOIN users u ON cr2.userId = u.id
            WHERE cr2.chatId IN (
                SELECT chatId 
                FROM chat_messages 
                WHERE chatRoomId = cm.chatRoomId
            )
            AND u.user_type = 3
            LIMIT 1
        ) AS sender_last_name,
        (
            SELECT COUNT(*) 
            FROM chat_messages cm2
            JOIN chat_recipients cr2 ON cm2.chatId = cr2.chatId
            WHERE cm2.chatRoomId = cm.chatRoomId 
            AND cr2.userId = '$admin_id' 
            AND cm2.sender != '$admin_id'
            AND cm2.status = 'sent'
        ) AS unread_count
    FROM chat_messages cm
    JOIN chat_recipients cr ON cm.chatId = cr.chatId
    JOIN users u ON cr.userId = u.id
    WHERE cr.userId = '$admin_id'
    AND cm.chatId IN (
        SELECT chatId 
        FROM chat_recipients 
        WHERE userId = '$admin_id'
    )
    AND (
        cm.timestamp = (
            SELECT MAX(timestamp) 
            FROM chat_messages 
            WHERE chatRoomId = cm.chatRoomId
        )
        OR (
            SELECT COUNT(*) 
            FROM chat_messages 
            WHERE chatRoomId = cm.chatRoomId
        ) = 2
        AND cm.sender != '$admin_id'
        AND cm.timestamp = (
            SELECT MIN(timestamp) 
            FROM chat_messages 
            WHERE chatRoomId = cm.chatRoomId
        )
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
        // Format the timestamp for display in Philippine time
        $row['timestamp'] = date('Y-m-d H:i:s', strtotime($row['timestamp']));
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