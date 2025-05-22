<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Update funeral bookings
    $funeralQuery = "UPDATE booking_tb SET is_read = 1 WHERE status = 'Pending'";
    $conn->query($funeralQuery);

    // Update lifeplan bookings
    $lifeplanQuery = "UPDATE lifeplan_booking_tb SET is_read = 1 WHERE booking_status = 'pending'";
    $conn->query($lifeplanQuery);

    // Update ID validations
    $idValidationQuery = "UPDATE valid_id_tb SET is_read = 1 WHERE is_validated = 'no'";
    $conn->query($idValidationQuery);

    echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error marking notifications as read']);
}
?> 