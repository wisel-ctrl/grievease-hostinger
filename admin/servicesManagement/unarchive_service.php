<?php
// unarchive_service.php
session_start();

// Include database connection
include '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_POST['service_id']) || empty($_POST['service_id'])) {
    echo json_encode(['success' => false, 'message' => 'Service ID is required']);
    exit;
}

$serviceId = intval($_POST['service_id']);

try {
    // Update service status to Active
    $sql = "UPDATE services_tb SET status = 'Active' WHERE service_id = $serviceId";
    
    if ($conn->query($sql)) {
        echo json_encode(['success' => true, 'message' => 'Service unarchived successfully']);
    } else {
        throw new Exception("Update failed: " . $conn->error);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>