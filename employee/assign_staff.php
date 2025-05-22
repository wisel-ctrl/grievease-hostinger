<?php
require_once '../db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $serviceId = $_POST['service_id'] ?? null;
    $assignedStaff = json_decode($_POST['assigned_staff'] ?? '[]', true);
    $notes = $_POST['notes'] ?? '';
    
    if (!$serviceId || empty($assignedStaff)) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // First, remove existing assignments for this service
        $deleteQuery = "DELETE FROM service_staff_tb WHERE service_id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("i", $serviceId);
        $deleteStmt->execute();
        
        // Insert new assignments
        $insertQuery = "INSERT INTO service_staff_tb (service_id, employee_id, notes) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        
        foreach ($assignedStaff as $employeeId) {
            $insertStmt->bind_param("iis", $serviceId, $employeeId, $notes);
            $insertStmt->execute();
        }
        
        // Update service status to indicate staff has been assigned
        $updateQuery = "UPDATE service_tb SET status = 'Assigned' WHERE service_id = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("i", $serviceId);
        $updateStmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 