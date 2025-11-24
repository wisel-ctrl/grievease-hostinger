<?php
require_once '../../db_connect.php';

if (isset($_GET['sales_id'])) {
    $salesId = $_GET['sales_id'];
    
    // Get branch_id and use_chapel
    $branchId = null;
    $useChapel = "No"; // Default value
    $branchQuery = "SELECT branch_id, use_chapel FROM sales_tb WHERE sales_id = ?";
    $branchStmt = $conn->prepare($branchQuery);
    $branchStmt->bind_param("i", $salesId);
    $branchStmt->execute();
    $branchResult = $branchStmt->get_result();
    
    if ($branchResult->num_rows > 0) {
        $branchData = $branchResult->fetch_assoc();
        $branchId = $branchData['branch_id'];
        $useChapel = $branchData['use_chapel'] ?? "No"; // Get use_chapel value
    }
    
    // Now get employees for each position type
    $response = [
        'embalmers' => getAllEmployeesByPosition($conn, 'Embalmer'),
        'drivers' => $branchId ? getEmployeesByPosition($conn, $branchId, 'Driver') : [],
        'personnel' => $branchId ? getEmployeesByPosition($conn, $branchId, 'Personnel') : [],
        'use_chapel' => $useChapel // Add use_chapel to response
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    echo json_encode(['error' => 'No sales ID provided']);
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

// Original function for branch-specific employees
function getEmployeesByPosition($conn, $branchId, $position) {
    $query = "SELECT employeeID, fname, mname, lname 
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