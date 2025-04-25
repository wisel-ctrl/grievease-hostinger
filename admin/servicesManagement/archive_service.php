<?php
// Include your database connection
require_once '../../db_connect.php'; // Update this to your actual connection file

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the service ID and branch ID from the POST data
    $serviceId = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
    $branchId = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
    
    // Validate inputs
    if ($serviceId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid service ID']);
        exit;
    }
    
    try {
        // Prepare the update query to change status to 'Inactive'
        $query = "UPDATE services_tb SET status = 'Inactive' WHERE service_id = ? AND branch_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $serviceId, $branchId);
        
        // Execute the query
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Service not found or already archived']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $stmt->error]);
        }
        
        // Close statement
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Exception: ' . $e->getMessage()]);
    }
    
    // Close connection
    $conn->close();
} else {
    // If not POST request
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}
?>