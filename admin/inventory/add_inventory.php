<?php
// add_inventory.php

require_once '../../db_connect.php';

$response = array('success' => false, 'message' => '');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $itemName   = $_POST['itemName'] ?? '';
        $category_id = intval($_POST['category_id'] ?? 0);
        $branch_id   = intval($_POST['branch_id'] ?? 0);
        $quantity    = intval($_POST['quantity'] ?? 0);
        $price       = floatval($_POST['price'] ?? 0.00);
        $selling_price = floatval($_POST['sellingPrice'] ?? 0.00);

        // Validate inputs
        if (empty($itemName) || $category_id <= 0 || $branch_id <= 0 || $quantity <= 0 || $selling_price <= 0) {
            throw new Exception("Please fill in all required fields with valid values.");
        }

        // Validate selling price > unit price
        if ($selling_price <= $price && $price > 0) {
            throw new Exception("Selling price must be greater than unit price.");
        }

        // Handle image (optional)
        $imagePath = null;
        if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] == 0) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['itemImage']['tmp_name']);
            if (strpos($mime, 'image/') !== 0) {
                throw new Exception("Uploaded file is not a valid image.");
            }

            $uploadDir = '../uploads/inventory/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileExtension = pathinfo($_FILES['itemImage']['name'], PATHINFO_EXTENSION);
            $uniqueFilename = uniqid('inventory_') . '.' . $fileExtension;
            $uploadPath = $uploadDir . $uniqueFilename;

            if (!move_uploaded_file($_FILES['itemImage']['tmp_name'], $uploadPath)) {
                throw new Exception("Failed to move uploaded file.");
            }

            $imagePath = 'uploads/inventory/' . $uniqueFilename;
        }

        // Check if item already exists
        $checkStmt = $conn->prepare("
            SELECT inventory_id, quantity 
            FROM inventory_tb 
            WHERE item_name = ? AND category_id = ? AND branch_id = ?
            LIMIT 1
        ");
        $checkStmt->bind_param("sii", $itemName, $category_id, $branch_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Update quantity
            $newQuantity = $row['quantity'] + $quantity;
            $updateStmt = $conn->prepare("
                UPDATE inventory_tb 
                SET quantity = ?, price = ?, selling_price = ?, inventory_img = COALESCE(?, inventory_img), status = 1
                WHERE inventory_id = ?
            ");
            $updateStmt->bind_param("iddsi", $newQuantity, $price, $selling_price, $imagePath, $row['inventory_id']);

            if ($updateStmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Inventory item updated successfully (quantity increased).";
                $response['inventory_id'] = $row['inventory_id'];
                $response['new_quantity'] = $newQuantity;
            } else {
                throw new Exception("Failed to update inventory item: " . $updateStmt->error);
            }

            $updateStmt->close();
        } else {
            // Insert new item
            $insertStmt = $conn->prepare("
                INSERT INTO inventory_tb 
                (item_name, category_id, quantity, price, selling_price, branch_id, inventory_img, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 1)
            ");
            $insertStmt->bind_param("siiddis", $itemName, $category_id, $quantity, $price, $selling_price, $branch_id, $imagePath);

            if ($insertStmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Inventory item added successfully.";
                $response['inventory_id'] = $conn->insert_id;
                $response['image_path'] = $imagePath;
            } else {
                throw new Exception("Failed to add inventory item: " . $insertStmt->error);
            }

            $insertStmt->close();
        }

        $checkStmt->close();
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();

    if (isset($uploadPath) && file_exists($uploadPath)) {
        unlink($uploadPath);
    }
}

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>