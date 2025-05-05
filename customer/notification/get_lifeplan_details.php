<?php
require_once '../../db_connect.php';

if (!isset($_GET['lpbooking_id'])) {
    echo json_encode(['error' => 'No life plan booking ID provided']);
    exit;
}

$lpbooking_id = intval($_GET['lpbooking_id']);

// Get life plan booking details
$query = "SELECT lb.*, s.service_name, b.branch_name 
          FROM lifeplan_booking_tb lb
          LEFT JOIN services_tb s ON lb.service_id = s.service_id
          LEFT JOIN branch_tb b ON lb.branch_id = b.branch_id
          WHERE lb.lpbooking_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $lpbooking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Life plan booking not found']);
    exit;
}

$data = $result->fetch_assoc();

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($data);

$stmt->close();
$conn->close();
?>