<?php
require_once '../../db_connect.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log debug information
function debug_log($message, $data = null) {
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if ($data !== null) {
        $log .= " - Data: " . print_r($data, true);
    }
    error_log($log . "\n", 3, "employee_debug.log");
}

if (isset($_GET['service_id'])) {
    $serviceId = $_GET['service_id'];
    debug_log("Received service_id", $serviceId);
    
    // Get branch_id from the service
    $branchId = null;
    $branchQuery = "SELECT branch_id FROM sales_tb WHERE sales_id = ?";
    debug_log("Branch query", $branchQuery);
    
    $branchStmt = $conn->prepare($branchQuery);
    if (!$branchStmt) {
        debug_log("Prepare failed", $conn->error);
        echo json_encode(['error' => 'Database prepare error: ' . $conn->error]);
        exit;
    }
    
    $branchStmt->bind_param("i", $serviceId);
    if (!$branchStmt->execute()) {
        debug_log("Execute failed", $branchStmt->error);
        echo json_encode(['error' => 'Database execute error: ' . $branchStmt->error]);
        exit;
    }
    
    $branchResult = $branchStmt->get_result();
    debug_log("Branch query result rows", $branchResult->num_rows);
    
    if ($branchResult->num_rows > 0) {
        $branchData = $branchResult->fetch_assoc();
        $branchId = $branchData['branch_id'];
        debug_log("Found branch_id", $branchId);
    } else {
        debug_log("No branch found for service_id", $serviceId);
    }
    
    // Get employees for each position type
    $embalmers = getAllEmployeesByPosition($conn, 'Embalmer');
    $drivers = $branchId ? getEmployeesByPosition($conn, $branchId, 'Driver') : [];
    $personnel = $branchId ? getEmployeesByPosition($conn, $branchId, 'Personnel') : [];
    
    debug_log("Employee counts", [
        'embalmers' => count($embalmers),
        'drivers' => count($drivers),
        'personnel' => count($personnel)
    ]);
    
    $response = [
        'embalmers' => $embalmers,
        'drivers' => $drivers,
        'personnel' => $personnel
    ];
    
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    debug_log("No service_id provided in request");
    echo json_encode(['error' => 'No service ID provided']);
}

// Get employees by position without branch restriction
function getAllEmployeesByPosition($conn, $position) {
    $query = "SELECT employeeID, fname, mname, lname 
              FROM employee_tb 
              WHERE position = ? 
              ORDER BY lname, fname";
    debug_log("getAllEmployeesByPosition query", ['position' => $position, 'query' => $query]);
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        debug_log("Prepare failed in getAllEmployeesByPosition", $conn->error);
        return [];
    }
    
    $stmt->bind_param("s", $position);
    if (!$stmt->execute()) {
        debug_log("Execute failed in getAllEmployeesByPosition", $stmt->error);
        return [];
    }
    
    $result = $stmt->get_result();
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    debug_log("getAllEmployeesByPosition results", ['position' => $position, 'count' => count($employees)]);
    return $employees;
}

// Get employees by position with branch restriction
function getEmployeesByPosition($conn, $branchId, $position) {
    $query = "SELECT employeeID, fname, mname, lname 
              FROM employee_tb 
              WHERE branch_id = ? AND position = ? 
              ORDER BY lname, fname";
    debug_log("getEmployeesByPosition query", [
        'branchId' => $branchId,
        'position' => $position,
        'query' => $query
    ]);
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        debug_log("Prepare failed in getEmployeesByPosition", $conn->error);
        return [];
    }
    
    $stmt->bind_param("is", $branchId, $position);
    if (!$stmt->execute()) {
        debug_log("Execute failed in getEmployeesByPosition", $stmt->error);
        return [];
    }
    
    $result = $stmt->get_result();
    $employees = [];
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
    
    debug_log("getEmployeesByPosition results", [
        'branchId' => $branchId,
        'position' => $position,
        'count' => count($employees)
    ]);
    return $employees;
}
?> 