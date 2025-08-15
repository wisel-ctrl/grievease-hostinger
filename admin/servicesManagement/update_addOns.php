<?php
// update_addOns.php
require_once '../../db_connect.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id']) || !isset($input['name']) || !isset($input['price']) || !isset($input['branch_id']) || !isset($input['icon']) || !isset($input['status'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit;
}

$addonId = $input['id'];
$name = trim($input['name']);
$price = floatval($input['price']);
$branchId = $input['branch_id'];
$icon = trim($input['icon']);
$status = in_array($input['status'], ['active', 'inactive']) ? $input['status'] : 'active';

// Check if add-on exists
$checkQuery = "SELECT addOns_id FROM AddOnsService_tb WHERE addOns_id = ?";
$checkStmt = $conn->prepare($checkQuery);
$checkStmt->bind_param("i", $addonId);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Add-on not found']);
    $checkStmt->close();
    $conn->close();
    exit;
}

$checkStmt->close();

// Update add-on
$updateQuery = "
    UPDATE AddOnsService_tb 
    SET 
        addOns_name = ?,
        price = ?,
        branch_id = ?,
        icon = ?,
        status = ?,
        update_date = NOW()
    WHERE addOns_id = ?
";

$updateStmt = $conn->prepare($updateQuery);
$updateStmt->bind_param("sdisss", $name, $price, $branchId, $icon, $status, $addonId);

if ($updateStmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Add-on updated successfully',
        'data' => [
            'id' => $addonId,
            'name' => $name,
            'price' => $price,
            'branch_id' => $branchId,
            'icon' => $icon,
            'status' => $status
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $conn->error]);
}

$updateStmt->close();
$conn->close();
?>