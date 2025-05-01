<?php
// get_inventory_item.php
require_once '../../db_connect.php';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid inventory ID']);
    exit();
}

$inventoryId = $_GET['id'];

// Fetch inventory item details
$query = "SELECT i.inventory_id, i.item_name, i.category_id, i.quantity, i.price, 
                 i.inventory_img, ic.category_name
          FROM inventory_tb i
          JOIN inventory_category ic ON i.category_id = ic.category_id
          WHERE i.inventory_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $inventoryId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Inventory item not found']);
    exit();
}

$item = $result->fetch_assoc();

// Handle image path
if ($item['inventory_img']) {
    $item['inventory_img'] = '../../admin/' . $item['inventory_img']; // Adjust path as needed
}

echo json_encode(['success' => true, 'item' => $item]);
?>