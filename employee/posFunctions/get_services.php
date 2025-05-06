<?php
header('Content-Type: application/json');

// Database connection
require_once '../includes/config.php';

// Get the service ID from the request
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

if ($service_id <= 0) {
    echo json_encode(['error' => 'Invalid service ID']);
    exit;
}

try {
    // Prepare the query to get service details
    $stmt = $conn->prepare("
        SELECT s.*, b.branch_name, sc.service_category_name 
        FROM services_tb s
        LEFT JOIN branch_tb b ON s.branch_id = b.branch_id
        LEFT JOIN service_category sc ON s.service_categoryID = sc.service_categoryID
        WHERE s.service_id = ?
    ");
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $service = $result->fetch_assoc();
        echo json_encode($service);
    } else {
        echo json_encode(['error' => 'Service not found']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>