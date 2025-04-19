<?php
// update_status.php - Endpoint to update message status (delivered/read)

// Include database connection
require_once '../../db_connect.php';

// Set headers for AJAX requests
header('Content-Type: application/json');

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($data['chatId']) || !isset($data['status']) || !isset($data['userId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields (chatId, status, userId)']);
    exit;
}

// Validate status value
if (!in_array($data['status'], ['delivered', 'read'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status. Must be "delivered" or "read"']);
    exit;
}

// Prepare statement to update status for specific recipient
$stmt = $conn->prepare("UPDATE chat_recipients SET status = ? WHERE chatId = ? AND userId = ?");
$stmt->bind_param("sss", $data['status'], $data['chatId'], $data['userId']);

// Execute the statement
if ($stmt->execute()) {
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);
} else {
    // Return error response
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to update status',
        'details' => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>