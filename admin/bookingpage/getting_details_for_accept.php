<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
  echo json_encode(['error' => 'No ID provided']);
  exit;
}

$booking_id = (int)$_GET['id'];

$query = "SELECT 
            b.*, 
            u.first_name, 
            u.middle_name, 
            u.last_name, 
            u.suffix,
            s.service_name, 
            u.phone_number,
            u.email 
          FROM booking_tb b 
          JOIN users u ON b.customerID = u.id 
          JOIN services_tb s ON b.service_id = s.service_id 
          WHERE b.booking_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  $booking = $result->fetch_assoc();
  
  // Optionally create a full_name field if needed
  $booking['full_name'] = trim(sprintf(
    '%s %s %s %s',
    $booking['first_name'],
    $booking['middle_name'] ? $booking['middle_name'] : '',
    $booking['last_name'],
    $booking['suffix'] ? $booking['suffix'] : ''
  ));
  
  echo json_encode($booking);
} else {
  echo json_encode(['error' => 'Booking not found']);
}
?>