<?php
require_once '../db_connect.php';

if (isset($_GET['service_id'])) {
    $serviceId = $_GET['service_id'];
    
    // Get branch_id from the service
    $branchId = null;
    $branchQuery = "SELECT branch_id FROM sales_tb WHERE sales_id = ?";
    $branchStmt = $conn->prepare($branchQuery);
    $branchStmt->bind_param("i", $serviceId);
    $branchStmt->execute();
    $branchResult = $branchStmt->get_result();
    
    if ($branchResult->num_rows > 0) {
        $branchData = $branchResult->fetch_assoc();
        $branchId = $branchData['branch_id'];
    }
    
    // Get employees for each position type
    $response = [
        'embalmers' => getAllEmployeesByPosition($conn, 'Embalmer'), // Get all embalmers
        'drivers' => $branchId ? getEmployeesByPosition($conn, $branchId, 'Driver') : [],
        'personnel' => $branchId ? getEmployeesByPosition($conn, $branchId, 'Personnel') : []
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    echo json_encode(['error' => 'No service ID provided']);
}

// Get employees by position without branch restriction
function getAllEmployeesByPosition($conn, $position) {
    $query = "SELECT employeeID, fname, mname, lname 
              FROM employee_tb 
              WHERE position = ? 
              ORDER BY lname, fname";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $position);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    return $employees;
}

// Get employees by position with branch restriction
function getEmployeesByPosition($conn, $branchId, $position) {
    $query = "SELECT employee_id, fname, mname, lname 
              FROM employee_tb 
              WHERE branch_id = ? AND position = ? 
              ORDER BY lname, fname";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("is", $branchId, $position);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    return $employees;
}
?> 