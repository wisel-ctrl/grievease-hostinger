<?php
require_once '../db_connect.php';

if (isset($_GET['employee_ids'])) {
    $employeeIds = explode(',', $_GET['employee_ids']);
    $placeholders = implode(',', array_fill(0, count($employeeIds), '?'));
    
    $query = "SELECT employeeID, base_salary FROM employee_tb WHERE employeeID IN ($placeholders)";
    $stmt = $conn->prepare($query);
    
    // Bind parameters dynamically
    $types = str_repeat('i', count($employeeIds));
    $stmt->bind_param($types, ...$employeeIds);
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $salaries = [];
    while ($row = $result->fetch_assoc()) {
        $salaries[$row['employeeID']] = $row['base_salary'];
    }
    
    header('Content-Type: application/json');
    echo json_encode($salaries);
} else {
    echo json_encode([]);
}
?>