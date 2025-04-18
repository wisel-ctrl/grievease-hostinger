<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get booking ID from request
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if ($booking_id <= 0) {
    echo json_encode(['error' => 'Invalid booking ID']);
    exit();
}

// Get booking details
$query = "SELECT b.*, s.service_name, br.branch_name 
          FROM booking_tb b
          LEFT JOIN services_tb s ON b.service_id = s.service_id
          LEFT JOIN branch_tb br ON b.branch_id = br.branch_id
          WHERE b.booking_id = ? AND b.customerID = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Booking not found or unauthorized access']);
    exit();
}

$booking = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Return the booking data as JSON
header('Content-Type: application/json');
echo json_encode($booking);
?>