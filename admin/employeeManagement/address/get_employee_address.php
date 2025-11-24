<?php
require_once '../../../db_connect.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $employee_id = intval($_GET['id']);
    $sql = "SELECT * FROM employee_tb WHERE EmployeeID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode($row);
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>