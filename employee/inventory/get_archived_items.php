<?php
// get_archived_items.php
require_once '../../db_connect.php';

// Check if branch_id is provided
$branchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;
if ($branchId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid branch ID']);
    exit();
}

// Fetch archived inventory items
$query = "SELECT i.inventory_id, i.item_name, ic.category_name as category, 
          i.quantity, i.price, i.total_value
          FROM inventory_tb i
          JOIN inventory_category ic ON i.category_id = ic.category_id
          WHERE i.branch_id = ? AND i.status = 0
          ORDER BY i.item_name ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $branchId);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode(['success' => true, 'items' => $items]);
?>