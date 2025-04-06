<?php
include '../../db_connect.php';

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Get form data
$inventoryId = isset($_POST['inventory_id']) ? (int)$_POST['inventory_id'] : 0;
$itemName = isset($_POST['item_name']) ? $_POST['item_name'] : '';
$categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
$price = isset($_POST['price']) ? str_replace(',', '', $_POST['price']) : 0;

// Validate data
if ($inventoryId <= 0) {
  echo "Invalid inventory ID";
  exit;
}

// Update the database
$sql = "UPDATE inventory_tb 
        SET item_name = ?, 
            quantity = ?, 
            price = ?,
            category_id = ?,
            updated_at = NOW()
        WHERE inventory_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sidii", $itemName, $quantity, $price, $categoryId, $inventoryId);

if ($stmt->execute()) {
  echo "success";
} else {
  echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>