<?php
require_once '../../db_connect.php';

$bookingId = $_GET['booking_id'] ?? 0;

$query = "SELECT * FROM booking_tb WHERE booking_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    echo json_encode(['success' => true, ...$booking]);
} else {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
}

$stmt->close();
$conn->close();

?>