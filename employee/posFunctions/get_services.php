<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
require_once '../../dp_connect.php';

// Verify connection
if (!$conn) {
    die(json_encode(['error' => 'Database connection failed']));
}

// Get the service ID from the request
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

if ($service_id <= 0) {
    die(json_encode(['error' => 'Invalid service ID']));
}

try {
    $query = "
        SELECT s.*, b.branch_name, sc.service_category_name 
        FROM services_tb s
        LEFT JOIN branch_tb b ON s.branch_id = b.branch_id
        LEFT JOIN service_category sc ON s.service_categoryID = sc.service_categoryID
        WHERE s.service_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        die(json_encode(['error' => 'Prepare failed: ' . $conn->error]));
    }
    
    $stmt->bind_param("i", $service_id);
    
    if (!$stmt->execute()) {
        die(json_encode(['error' => 'Execute failed: ' . $stmt->error]));
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $service = $result->fetch_assoc();
        echo json_encode($service);
    } else {
        echo json_encode(['error' => 'Service not found']);
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode(['error' => 'Server error occurred']);
}

$conn->close();
?>