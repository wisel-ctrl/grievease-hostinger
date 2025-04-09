<?php
header('Content-Type: application/json');

include '../../db_connect.php';

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Get the employee ID from the POST data
$employeeId = isset($_POST['employeeId']) ? $_POST['employeeId'] : '';

if (empty($employeeId)) {
    echo json_encode(['success' => false, 'message' => 'No employee ID provided']);
    exit;
}

// Sanitize the input (consider using prepared statements for better security)
$employeeId = $conn->real_escape_string($employeeId);

// Update query
$sql = "UPDATE employee_tb SET status = 'fired' WHERE employeeID = '$employeeId'";

if ($conn->query($sql) === TRUE) {
    if ($conn->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Employee terminated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No employee found with that ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating record: ' . $conn->error]);
}

$conn->close();
?>