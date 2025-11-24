<?php
// get_employee_address_names.php - ONLY FOR EDIT MODAL
require_once '../../../db_connect.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode([]);
    exit;
}

$employeeId = intval($_GET['id']);

$sql = "SELECT region_name, province_name, municipality_name, barangay_name, street_address, zip_code 
        FROM employee_tb 
        WHERE EmployeeID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Store temp values in modal dataset for chaining
    echo json_encode([
        'region_name' => $row['region_name'] ?? '',
        'province_name' => $row['province_name'] ?? '',
        'municipality_name' => $row['municipality_name'] ?? '',
        'barangay_name' => $row['barangay_name'] ?? '',
        'street_address' => $row['street_address'] ?? '',
        'zip_code' => $row['zip_code'] ?? ''
    ]);
} else {
    echo json_encode([]);
}

$stmt->close();
$conn->close();
?>