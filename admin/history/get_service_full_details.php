<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['sales_id'])) {
    echo json_encode(['success' => false, 'message' => 'No service ID provided']);
    exit;
}

$salesId = (int)$_GET['sales_id'];

try {
    // Fetch basic service info from sales_tb and join with branch_tb and services_tb
    $serviceQuery = "SELECT s.*, b.branch_name, sv.service_name 
                    FROM sales_tb s
                    LEFT JOIN branch_tb b ON s.branch_id = b.branch_id
                    LEFT JOIN services_tb sv ON s.service_id = sv.service_id
                    WHERE s.sales_id = ?";
    $stmt = $conn->prepare($serviceQuery);
    $stmt->bind_param("i", $salesId);
    $stmt->execute();
    $serviceResult = $stmt->get_result();
    
    if ($serviceResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Service not found']);
        exit;
    }
    
    $serviceData = $serviceResult->fetch_assoc();
    $response = ['success' => true, ...$serviceData];
    
    // Fetch initial staff assignments (service_stage = 'initial')
    $initialStaff = [
        'embalmers' => [],
        'drivers' => [],
        'personnel' => [],
        'date' => null,
        'notes' => null
    ];
    
    $initialQuery = "SELECT esp.*, e.fname, e.mname, e.lname, e.suffix, e.position
                    FROM employee_service_payments esp
                    JOIN employee_tb e ON esp.employeeID = e.EmployeeID
                    WHERE esp.sales_id = ? AND esp.service_stage = 'initial'";
    $stmt = $conn->prepare($initialQuery);
    $stmt->bind_param("i", $salesId);
    $stmt->execute();
    $initialResult = $stmt->get_result();
    
    while ($row = $initialResult->fetch_assoc()) {
        $name = trim($row['fname'] . ' ' . ($row['mname'] ? $row['mname'] . ' ' : '') . $row['lname'] . ($row['suffix'] ? ' ' . $row['suffix'] : ''));
        
        if (stripos($row['position'], 'embalm') !== false) {
            $initialStaff['embalmers'][] = $name;
        } elseif (stripos($row['position'], 'driver') !== false) {
            $initialStaff['drivers'][] = $name;
        } else {
            $initialStaff['personnel'][] = $name;
        }
        
        if (!$initialStaff['date'] && $row['payment_date']) {
            $initialStaff['date'] = date('Y-m-d', strtotime($row['payment_date']));
        }
        
        if (!$initialStaff['notes'] && $row['notes']) {
            $initialStaff['notes'] = $row['notes'];
        }
    }
    
    $response['initial_staff'] = $initialStaff;
    
    // Fetch burial staff assignments (service_stage = 'completion')
    $burialStaff = [
        'drivers' => [],
        'personnel' => [],
        'date' => null,
        'notes' => null
    ];
    
    $burialQuery = "SELECT esp.*, e.fname, e.mname, e.lname, e.suffix, e.position
                   FROM employee_service_payments esp
                   JOIN employee_tb e ON esp.employeeID = e.EmployeeID
                   WHERE esp.sales_id = ? AND esp.service_stage = 'completion'";
    $stmt = $conn->prepare($burialQuery);
    $stmt->bind_param("i", $salesId);
    $stmt->execute();
    $burialResult = $stmt->get_result();
    
    while ($row = $burialResult->fetch_assoc()) {
        $name = trim($row['fname'] . ' ' . ($row['mname'] ? $row['mname'] . ' ' : '') . $row['lname'] . ($row['suffix'] ? ' ' . $row['suffix'] : ''));
        
        if (stripos($row['position'], 'driver') !== false) {
            $burialStaff['drivers'][] = $name;
        } else {
            $burialStaff['personnel'][] = $name;
        }

        if (!$burialStaff['date'] && $row['payment_date']) {
            $burialStaff['date'] = date('Y-m-d', strtotime($row['payment_date']));
        }
        
        if (!$burialStaff['notes'] && $row['notes']) {
            $burialStaff['notes'] = $row['notes'];
        }
    }
    
    $response['burial_staff'] = $burialStaff;
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}