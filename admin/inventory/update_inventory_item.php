<?php
include '../../db_connect.php';
session_start();

if ($conn->connect_error) {
    http_response_code(500);
    echo "Error: Database connection failed: " . $conn->connect_error;
    exit;
}

// === AUTH & SESSION CHECK ===
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    http_response_code(403);
    echo "Error: Unauthorized access";
    exit;
}

// === GET & VALIDATE FORM DATA ===
$inventoryId = isset($_POST['inventory_id']) ? (int)$_POST['inventory_id'] : 0;
$itemName = isset($_POST['item_name']) ? trim($_POST['item_name']) : '';
$categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$newQuantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;
$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
$sellingPrice = isset($_POST['selling_price']) ? floatval($_POST['selling_price']) : 0;
$userId = (int)$_SESSION['user_id'];

// === CLIENT + SERVER VALIDATION ===
if ($inventoryId <= 0) {
    http_response_code(400);
    echo "Error: Invalid inventory ID";
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

if ($newQuantity < 0) {
    http_response_code(400);
    echo "Error: Quantity cannot be negative";
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

// === START TRANSACTION ===
$conn->begin_transaction();

try {
    // Get current item data
    $stmt = $conn->prepare("SELECT quantity, branch_id FROM inventory_tb WHERE inventory_id = ? AND status = 1");
    $stmt->bind_param("i", $inventoryId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Item not found or already archived");
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

    // Update inventory
    $stmt = $conn->prepare("UPDATE inventory_tb 
            SET item_name = ?, 
                quantity = ?, 
                price = ?,
                selling_price = ?,
                category_id = ?,
                updated_at = NOW()
            WHERE inventory_id = ?");
    $stmt->bind_param("siddii", $itemName, $newQuantity, $price, $sellingPrice, $categoryId, $inventoryId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update inventory: " . $stmt->error);
    }
    $stmt->close();

    // Log the change
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

    // Commit
    $conn->commit();
    http_response_code(200);
    echo "success";

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    error_log("Inventory Update Error: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}

$conn->close();
?>