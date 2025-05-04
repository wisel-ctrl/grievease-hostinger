<?php
require_once '../../db_connect.php'; // Database connection

// Set the timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

$conn->query("SET time_zone = '+08:00'");

// Get the POST data
$lifeplanId = isset($_POST['lifeplanId']) ? $_POST['lifeplanId'] : null;
$reason = isset($_POST['reason']) ? $_POST['reason'] : null;

// Validate input
if (!$lifeplanId || !$reason) {
    $response = [
        'success' => false,
        'error' => 'Missing required parameters'
    ];
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

try {
    // Prepare the update query
    $query = "UPDATE lifeplan_booking_tb 
              SET booking_status = 'decline', 
                  decline_reason = ?, 
                  acceptdecline_date = NOW()
              WHERE lpbooking_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $reason, $lifeplanId);
    
    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'LifePlan booking declined successfully'
        ];
    } else {
        $response = [
            'success' => false,
            'error' => 'Failed to update LifePlan booking'
        ];
    }
    
    $stmt->close();
} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ];
}

header('Content-Type: application/json');
echo json_encode($response);
?>