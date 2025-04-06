<?php
require_once '../db_connect.php';

if (isset($_GET['id'])) {
    $employeeId = $_GET['id'];
    
    $sql = "SELECT * FROM Employee_tb WHERE EmployeeID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        echo json_encode($employee);
    } else {
        echo json_encode(['error' => 'Employee not found']);
    }
} else {
    echo json_encode(['error' => 'No employee ID provided']);
}
?>