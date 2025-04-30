<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

$bookingId = $_GET['booking_id'] ?? 0;

$query = "SELECT b.*, s.service_name, s.selling_price, br.branch_name
          FROM booking_tb b
          JOIN services_tb s ON b.service_id = s.service_id
          JOIN branch_tb br ON b.branch_id = br.branch_id
          WHERE b.booking_id = ? AND b.status = 'Accepted'";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        ...$booking
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Accepted booking not found'
    ]);
}

$stmt->close();
$conn->close();
?>