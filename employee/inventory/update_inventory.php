<?php
// update_inventory.php
require_once '../../db_connect.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Get form data
$inventoryId = $_POST['editInventoryId'] ?? null;
$itemName = $_POST['editItemName'] ?? null;
$categoryId = $_POST['editCategoryId'] ?? null;
$quantity = $_POST['editQuantity'] ?? null;
$unitPrice = $_POST['editUnitPrice'] ?? null;

// Validate required fields
if (!$inventoryId || !$itemName || !$categoryId || !$quantity || !$unitPrice) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}

// Calculate total value
$totalValue = $quantity * $unitPrice;

// Handle file upload
$imagePath = null;
if (isset($_FILES['editItemImage']) && $_FILES['editItemImage']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../../admin/uploads/inventory/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = uniqid() . '_' . basename($_FILES['editItemImage']['name']);
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['editItemImage']['tmp_name'], $targetPath)) {
        $imagePath = 'uploads/inventory/' . $fileName;
    }
}

// Update inventory item
try {
    // If new image was uploaded, update the image path too
    if ($imagePath) {
        $query = "UPDATE inventory_tb 
                  SET item_name = ?, category_id = ?, quantity = ?, price = ?, 
                      total_value = ?, inventory_img = ?
                  WHERE inventory_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("siiddsi", $itemName, $categoryId, $quantity, $unitPrice, $totalValue, $imagePath, $inventoryId);
    } else {
        $query = "UPDATE inventory_tb 
                  SET item_name = ?, category_id = ?, quantity = ?, price = ?, 
                      total_value = ?
                  WHERE inventory_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("siiddi", $itemName, $categoryId, $quantity, $unitPrice, $totalValue, $inventoryId);
    }
    
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Inventory item updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No changes made to the inventory item']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error updating inventory item: ' . $e->getMessage()]);
}
?>