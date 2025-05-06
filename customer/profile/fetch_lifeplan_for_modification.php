<?php
require_once '../../db_connect.php';
session_start();

if (isset($_GET['booking_id'])) {
    $bookingId = $_GET['booking_id'];
    
    $query = "SELECT lb.*, s.service_name, s.selling_price as package_price, br.branch_name 
              FROM lifeplan_booking_tb lb
              LEFT JOIN services_tb s ON lb.service_id = s.service_id
              JOIN branch_tb br ON lb.branch_id = br.branch_id
              WHERE lb.lpbooking_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $booking = $result->fetch_assoc();
        
        // Format dates for input fields
        if ($booking['benefeciary_birth']) {
            $booking['benefeciary_birth'] = date('Y-m-d', strtotime($booking['benefeciary_birth']));
        }
        
        $response = [
            'success' => true,
            'data' => $booking
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Life plan booking not found'
        ];
    }
    $stmt->close();
} else {
    $response = [
        'success' => false,
        'message' => 'Booking ID not provided'
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
?>