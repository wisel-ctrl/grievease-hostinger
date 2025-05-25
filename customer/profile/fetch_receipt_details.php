<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

$bookingId = $_GET['booking_id'] ?? 0;

// First, check if it's a custom package (service_id IS NULL)
$query = "SELECT b.*, br.branch_name,
                 u.first_name as accepter_first, u.last_name as accepter_last, 
                 u.middle_name as accepter_middle, u.suffix as accepter_suffix,
                 u.email as accepter_email, u.phone_number as accepter_phone
          FROM booking_tb b
          JOIN branch_tb br ON b.branch_id = br.branch_id
          LEFT JOIN users u ON b.accepter_decliner = u.id
          WHERE b.booking_id = ? AND b.status = 'Accepted' AND b.service_id IS NULL";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // This is a custom package
    $booking = $result->fetch_assoc();
    $booking['service_name'] = 'Customize Package';
    $booking['selling_price'] = $booking['initial_price']; // Changed from total_amount to initial_price
    
    echo json_encode([
        'success' => true,
        ...$booking
    ]);
    $stmt->close();
    $conn->close();
    exit();
}

// If not a custom package, check for regular service
$query = "SELECT b.*, s.service_name, s.selling_price, br.branch_name,
                 u.first_name as accepter_first, u.last_name as accepter_last, 
                 u.middle_name as accepter_middle, u.suffix as accepter_suffix,
                 u.email as accepter_email, u.phone_number as accepter_phone
          FROM booking_tb b
          JOIN services_tb s ON b.service_id = s.service_id
          JOIN branch_tb br ON b.branch_id = br.branch_id
          LEFT JOIN users u ON b.accepter_decliner = u.id
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