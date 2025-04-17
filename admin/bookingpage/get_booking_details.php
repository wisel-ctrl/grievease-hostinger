<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
  echo json_encode(['error' => 'No ID provided']);
  exit;
}

$booking_id = (int)$_GET['id'];

$query = "SELECT b.*, CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', 
            COALESCE(u.suffix, '')) AS customer_name, 
            s.service_name, u.email FROM booking_tb b 
            JOIN users u ON b.customerID = u.id 
            JOIN services_tb s ON b.service_id = s.service_id 
            WHERE b.booking_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
  echo json_encode($result->fetch_assoc());
} else {
  echo json_encode(['error' => 'Booking not found']);
}
?>