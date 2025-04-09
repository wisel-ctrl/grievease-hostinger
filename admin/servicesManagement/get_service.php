<?php
// Include database connection
include '../../db_connect.php';

header('Content-Type: application/json');

// Check if service_id is provided
if (!isset($_GET['service_id']) || empty($_GET['service_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Service ID is required'
    ]);
    exit;
}

$service_id = $_GET['service_id'];

// Prepare and execute the query
$sql = "SELECT s.*, b.branch_name 
        FROM services_tb s
        LEFT JOIN branch_tb b ON s.branch_id = b.branch_id
        WHERE s.service_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $service = $result->fetch_assoc();
    
    // Get casket and urn names if applicable
    if ($service['casket_id']) {
        $casket_sql = "SELECT item_name FROM inventory_tb WHERE inventory_id = ?";
        $casket_stmt = $conn->prepare($casket_sql);
        $casket_stmt->bind_param("i", $service['casket_id']);
        $casket_stmt->execute();
        $casket_result = $casket_stmt->get_result();
        if ($casket_result->num_rows > 0) {
            $casket = $casket_result->fetch_assoc();
            $service['casket_name'] = $casket['item_name'];
        }
    }
    
    if ($service['urn_id']) {
        $urn_sql = "SELECT item_name FROM inventory_tb WHERE inventory_id = ?";
        $urn_stmt = $conn->prepare($urn_sql);
        $urn_stmt->bind_param("i", $service['urn_id']);
        $urn_stmt->execute();
        $urn_result = $urn_stmt->get_result();
        if ($urn_result->num_rows > 0) {
            $urn = $urn_result->fetch_assoc();
            $service['urn_name'] = $urn['item_name'];
        }
    }
    
    // Get category name
    $cat_sql = "SELECT service_category_name FROM service_category WHERE service_categoryID = ?";
    $cat_stmt = $conn->prepare($cat_sql);
    $cat_stmt->bind_param("i", $service['service_categoryID']);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    if ($cat_result->num_rows > 0) {
        $category = $cat_result->fetch_assoc();
        $service['category_name'] = $category['service_category_name'];
    }
    
    echo json_encode([
        'status' => 'success',
        'service' => $service
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Service not found'
    ]);
}

$stmt->close();
$conn->close();
?>