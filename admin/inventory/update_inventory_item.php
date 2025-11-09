<?php
include '../../db_connect.php';


session_start();

if ($conn->connect_error) {
    http_response_code(500);
    echo "Error: Database connection failed: " . $conn->connect_error;
    exit;
}

// Get form data
// === AUTH & SESSION CHECK ===
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    http_response_code(403);
    echo "Error: Unauthorized access";
    exit;
}

$inventoryId = isset($_POST['inventory_id']) ? (int)$_POST['inventory_id'] : 0;
$itemName = isset($_POST['item_name']) ? trim($_POST['item_name']) : '';
$categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$newQuantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$sellingPrice = isset($_POST['selling_price']) ? floatval($_POST['selling_price']) : 0;
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

// Validate data
if ($inventoryId <= 0 || empty($itemName) || $categoryId <= 0) {
    http_response_code(400);
    echo "Error: Invalid input data";
    exit;
}

if (empty($itemName) || strlen($itemName) < 2) {
    http_response_code(400);
    echo "Error: Item name must be at least 2 characters";
    exit;
}

if ($categoryId <= 0) {
    http_response_code(400);
    echo "Error: Please select a valid category";
    exit;
}

// Validate data
if ($newQuantity < 0) {
    http_response_code(400);
    exit;
}

if ($price < 0) {
    http_response_code(400);
    echo "Error: Unit Price cannot be negative";
    exit;
}

if ($sellingPrice < 0) {
    http_response_code(400);
    echo "Error: Selling Price cannot be negative";
    exit;
}

// CRITICAL: SELLING PRICE MUST BE > UNIT PRICE
if ($sellingPrice <= $price && $sellingPrice > 0) {
    http_response_code(400);
    echo "Error: Selling Price must be greater than Unit Price (₱" . number_format($price, 2) . ")";
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get current quantity and branch_id
    $stmt = $conn->prepare("SELECT quantity, branch_id FROM inventory_tb WHERE inventory_id = ? AND status = 1");
    $stmt->bind_param("i", $inventoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("Item not found");
    }
    $currentItem = $result->fetch_assoc();
    $stmt->close();

    $oldQuantity = (int)$currentItem['quantity'];
    $quantityChange = $newQuantity - $oldQuantity;
    $branchID = (int)$currentItem['branch_id'];

    // Determine activity type
    $activityType = 'Adjusted';
    if ($newQuantity <= 0) {
        $activityType = 'Depleted';
    } elseif ($quantityChange > 0) {
        $activityType = 'Restocked';
    } elseif ($quantityChange < 0) {
        $activityType = 'Removed';
    }

    // Update inventory item
    // Update inventory item
    $stmt = $conn->prepare("UPDATE inventory_tb 
            SET item_name = ?, 
                quantity = ?, 
                price = ?,
                selling_price = ?,
                category_id = ?,
                updated_at = NOW()
            WHERE inventory_id = ?");
    $stmt->bind_param("siddii", $itemName, $newQuantity, $price, $sellingPrice, $categoryId, $inventoryId);
    $stmt->execute();
    $stmt->close();

    // Log the inventory change
    // Log the inventory change
    $stmt = $conn->prepare("INSERT INTO inventory_logs 
            (inventory_id, branch_id, old_quantity, new_quantity, quantity_change, 
             activity_type, activity_date, user_id)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)");
    $stmt->bind_param("iiiiisi", $inventoryId, $branchID, $oldQuantity, $newQuantity, 
                      $quantityChange, $activityType, $userId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to log activity: " . $stmt->error);
    }
    $stmt->close();

    // Commit transaction
    // Commit transaction
    $conn->commit();
    http_response_code(200);
    echo "success";

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>