<?php
// get_messages.php - Endpoint to retrieve chat messages
session_start();

// Include database connection
require_once '../../db_connect.php';

// Set headers for AJAX requests
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'User not authenticated']);
    exit;
}

// Check if this is a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get chatRoomId from query parameter
$chatRoomId = isset($_GET['chatRoomId']) ? $_GET['chatRoomId'] : null;

// Validate chatRoomId
if (!$chatRoomId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing chatRoomId parameter']);
    exit;
}

// Prepare statement to retrieve messages
$stmt = $conn->prepare("
    SELECT m.*, GROUP_CONCAT(r.userId) as recipients
    FROM chat_messages m
    LEFT JOIN chat_recipients r ON m.chatId = r.chatId
    WHERE m.chatRoomId = ?
    GROUP BY m.chatId
    ORDER BY m.timestamp ASC
");

$stmt->bind_param("s", $chatRoomId);
$stmt->execute();

// Get result
$result = $stmt->get_result();
$messages = [];

// Fetch all messages
while ($row = $result->fetch_assoc()) {
    // Convert recipients string to array
    if ($row['recipients']) {
        $row['recipients'] = explode(',', $row['recipients']);
    } else {
        $row['recipients'] = [];
    }
    
    $messages[] = $row;
}

// Return messages
echo json_encode([
    'success' => true,
    'messages' => $messages
]);

$stmt->close();
$conn->close();
?>