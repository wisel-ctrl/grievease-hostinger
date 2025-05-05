<?php
require_once '../../db_connect.php';

// Check if user is logged in and has admin privileges
session_start();
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 401 Unauthorized");
    exit();
}

if ($_SESSION['user_type'] != 1) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

// Fetch archived employees
$sql = "SELECT e.EmployeeID, e.fname, e.mname, e.lname, e.suffix, e.position, 
                b.branch_name
        FROM employee_tb e
        JOIN branch_tb b ON e.branch_id = b.branch_id
        WHERE e.status = 'fired'
        ORDER BY e.EmployeeID asc";

$result = $conn->query($sql);
$employees = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $employees[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($employees);
$conn->close();
?>