<?php
session_start();
require_once '../../db_connect.php';

// Get branch_id from query parameter
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

// Prepare and execute query
$query = "SELECT inventory_id, item_name, price, branch_id, inventory_img FROM `inventory_tb` WHERE branch_id = ? AND status = 1 AND category_id = 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();

$caskets = [];
while ($row = $result->fetch_assoc()) {
    $caskets[] = $row;
}

header('Content-Type: application/json');
echo json_encode($caskets);

$stmt->close();
$conn->close();
?>