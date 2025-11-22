<?php
// add_inventory.php

require_once '../../db_connect.php';

$response = array('success' => false, 'message' => '', 'added_count' => 0);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $itemName   = $_POST['itemName'] ?? '';
        $category_id = intval($_POST['category_id'] ?? 0);
        $branches   = $_POST['branches'] ?? []; // Now an array
        $quantity    = intval($_POST['quantity'] ?? 0);
        $price       = floatval($_POST['price'] ?? 0.00);
        $selling_price = floatval($_POST['sellingPrice'] ?? 0.00);

        // Validate inputs
        if (empty($itemName) || $category_id <= 0 || empty($branches) || $quantity <= 0 || $selling_price <= 0) {
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

        $addedCount = 0;
        $errors = [];

        // Process each selected branch
        foreach ($branches as $branch_id) {
            $branch_id = intval($branch_id);
            
            if ($branch_id <= 0) {
                continue; // Skip invalid branch IDs
            }

            // Check if item already exists in this branch
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
                // Update quantity for existing item
                $newQuantity = $row['quantity'] + $quantity;
                $updateStmt = $conn->prepare("
                    UPDATE inventory_tb 
                    SET quantity = ?, price = ?, selling_price = ?, inventory_img = COALESCE(?, inventory_img), status = 1
                    WHERE inventory_id = ?
                ");
                $updateStmt->bind_param("iddsi", $newQuantity, $price, $selling_price, $imagePath, $row['inventory_id']);

                if ($updateStmt->execute()) {
                    $addedCount++;
                } else {
                    $errors[] = "Failed to update item in branch $branch_id: " . $updateStmt->error;
                }

                $updateStmt->close();
            } else {
                // Insert new item for this branch
                $insertStmt = $conn->prepare("
                    INSERT INTO inventory_tb 
                    (item_name, category_id, quantity, price, selling_price, branch_id, inventory_img, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
                ");
                $insertStmt->bind_param("siiddis", $itemName, $category_id, $quantity, $price, $selling_price, $branch_id, $imagePath);

                if ($insertStmt->execute()) {
                    $addedCount++;
                } else {
                    $errors[] = "Failed to add item to branch $branch_id: " . $insertStmt->error;
                }

                $insertStmt->close();
            }

            $checkStmt->close();
        }

        if ($addedCount > 0) {
            $response['success'] = true;
            $response['added_count'] = $addedCount;
            $response['message'] = "Item added to $addedCount branch(es) successfully.";
            
            if (!empty($errors)) {
                $response['message'] .= " Some errors occurred: " . implode(', ', $errors);
            }
        } else {
            throw new Exception("Failed to add item to any branch: " . implode(', ', $errors));
        }
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