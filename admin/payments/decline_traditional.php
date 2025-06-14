<?php
require_once '../../db_connect.php';

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Get payment ID and reason from query parameters
$payment_id = isset($_GET['payment_id']) ? $_GET['payment_id'] : null;
$sales_id = isset($_GET['sales_id']) ? $_GET['sales_id'] : null;
$reason = isset($_GET['reason']) ? $_GET['reason'] : null;

// Validate required parameters
if (!$payment_id || !$sales_id || !$reason) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

try {
    // Prepare the update statement
    $stmt = $conn->prepare("UPDATE installment_request_tb 
                           SET acceptdecline_date = NOW(), 
                               decline_reason = ?,
                               status = 'declined'
                           WHERE payment_id = ? AND sales_id = ?");
    
    // Bind parameters and execute
    $stmt->bind_param("sss", $reason, $payment_id, $sales_id);
    $stmt->execute();

    // Check if any rows were affected
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true]);
    } else {
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['error' => 'Payment not found or no changes made']);
    }
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
?>