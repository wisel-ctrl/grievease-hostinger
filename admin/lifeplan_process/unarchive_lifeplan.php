<?php
require_once '../../db_connect.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Validate input
    if (!isset($data['lifeplan_id']) || empty($data['lifeplan_id'])) {
        echo json_encode(['success' => false, 'message' => 'LifePlan ID is required']);
        exit;
    }
    
    $lifeplanId = $data['lifeplan_id'];
    
    try {
        // Prepare the update statement
        $stmt = $conn->prepare("UPDATE lifeplan_tb SET archived = 'show' WHERE lifeplan_id = ?");
        $stmt->bind_param("i", $lifeplanId);
        
        // Execute the statement
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'LifePlan unarchived successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to unarchive LifePlan']);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>