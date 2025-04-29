<?php
require_once '../../db_connect.php';

$bookingId = $_GET['booking_id'] ?? 0;

$query = "SELECT b.*, s.service_name, s.selling_price, br.branch_name 
          FROM booking_tb b
          JOIN services_tb s ON b.service_id = s.service_id
          JOIN branch_tb br ON b.branch_id = br.branch_id
          WHERE b.booking_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $booking = $result->fetch_assoc();
    
    // Convert dates to proper format
    $booking['deceased_birth'] = $booking['deceased_birth'] ? date('Y-m-d', strtotime($booking['deceased_birth'])) : null;
    $booking['deceased_dodeath'] = $booking['deceased_dodeath'] ? date('Y-m-d', strtotime($booking['deceased_dodeath'])) : null;
    $booking['deceased_dateOfBurial'] = $booking['deceased_dateOfBurial'] ? date('Y-m-d', strtotime($booking['deceased_dateOfBurial'])) : null;
    
    // Convert with_cremate to boolean
    $booking['with_cremate'] = ($booking['with_cremate'] == 'yes') ? 1 : 0;
    
    echo json_encode(['success' => true, ...$booking]);
} else {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
}

$stmt->close();
$conn->close();
?>