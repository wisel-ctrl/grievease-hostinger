<?php
session_start();
require_once '../../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Mark all booking notifications as read
    $booking_query = "UPDATE booking_tb SET is_read = TRUE WHERE customerID = ? AND is_read = FALSE";
    $booking_stmt = $conn->prepare($booking_query);
    $booking_stmt->bind_param("i", $user_id);
    $booking_stmt->execute();
    
    // Mark all lifeplan booking notifications as read
    $lifeplan_query = "UPDATE lifeplan_booking_tb SET is_read = TRUE WHERE customer_id = ? AND is_read = FALSE";
    $lifeplan_stmt = $conn->prepare($lifeplan_query);
    $lifeplan_stmt->bind_param("i", $user_id);
    $lifeplan_stmt->execute();
    
    // Mark ID validation notifications as read
    $id_query = "UPDATE valid_id_tb SET is_read = TRUE WHERE id = ? AND is_read = FALSE";
    $id_stmt = $conn->prepare($id_query);
    $id_stmt->bind_param("i", $user_id);
    $id_stmt->execute();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
?>