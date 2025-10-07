<?php
// add_inventory.php
session_start();
require_once '../../db_connect.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Check if user is logged in and is an employee
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User not logged in.");
    }
    
    if ($_SESSION['user_type'] != 2) {
        throw new Exception("Only employees can add inventory items.");
    }

    // Get employee's branch location
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT branch_loc FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Employee not found.");
    }
    
    $employee = $result->fetch_assoc();
    $branch_id = $employee['branch_loc'];
    
    // Log received data for debugging
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $itemName = trim($_POST['itemName'] ?? '');
        $category_id = intval($_POST['category_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 0);
        $price = floatval($_POST['unitPrice'] ?? 0.00);
        
        // Validate required fields
        if (empty($itemName)) {
            throw new Exception("Item name is required.");
        }
        if ($category_id <= 0) {
            throw new Exception("Please select a valid category.");
        }
        if ($quantity <= 0) {
            throw new Exception("Quantity must be greater than 0.");
        }
        if ($price <= 0) {
            throw new Exception("Price must be greater than 0.");
        }
        
        // Calculate total value
        $total_value = $quantity * $price;
        
        // Process image if uploaded
        $imagePath = null;
        if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] == UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = $_FILES['itemImage']['type'];
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Only JPG, PNG, and GIF images are allowed.");
            }
            
            $uploadDir = '../../admin/uploads/inventory/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileExtension = pathinfo($_FILES['itemImage']['name'], PATHINFO_EXTENSION);
            $uniqueFilename = uniqid('inv_') . '.' . $fileExtension;
            $uploadPath = $uploadDir . $uniqueFilename;
            
            if (!move_uploaded_file($_FILES['itemImage']['tmp_name'], $uploadPath)) {
                throw new Exception("Failed to upload image.");
            }
            
            $imagePath = 'uploads/inventory/' . $uniqueFilename;
        }
        
        // Check if item with same name and category already exists in this branch
        $checkStmt = $conn->prepare("SELECT inventory_id, quantity, price, total_value, inventory_img FROM inventory_tb 
                                   WHERE item_name = ? AND category_id = ? AND branch_id = ? AND status = 1");
        $checkStmt->bind_param("sii", $itemName, $category_id, $branch_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            // Item exists, update quantity instead of inserting
            $existingItem = $checkResult->fetch_assoc();
            $existing_id = $existingItem['id'];
            $existing_quantity = $existingItem['quantity'];
            $existing_price = $existingItem['price'];
            $existing_total_value = $existingItem['total_value'];
            $existing_image = $existingItem['inventory_img'];
            
            // Use new price if provided, otherwise keep existing price
            $new_price = ($price > 0) ? $price : $existing_price;
            
            // Calculate new quantity and total value
            $new_quantity = $existing_quantity + $quantity;
            $new_total_value = $new_quantity * $new_price;
            
            // Determine which image to use (prefer new image if uploaded, otherwise keep existing)
            $final_image = $imagePath ?: $existing_image;
            
            // Update the existing record
            $updateStmt = $conn->prepare("UPDATE inventory_tb 
                                        SET quantity = ?, price = ?, total_value = ?, inventory_img = ?
                                        WHERE inventory_id = ?");
            $updateStmt->bind_param("iddsi", $new_quantity, $new_price, $new_total_value, $final_image, $existing_id);
            
            if ($updateStmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Item already exists. Quantity updated successfully!";
                $response['inventory_id'] = $existing_id;
                $response['action'] = 'updated';
                
                // Clean up new uploaded image if we're using the existing one
                if ($imagePath && $final_image !== $imagePath) {
                    $fullImagePath = '../../admin/' . $imagePath;
                    if (file_exists($fullImagePath)) {
                        unlink($fullImagePath);
                    }
                }
            } else {
                throw new Exception("Database error while updating: " . $updateStmt->error);
            }
            
            $updateStmt->close();
        } else {
            // Item doesn't exist, insert new record
            $insertStmt = $conn->prepare("INSERT INTO inventory_tb 
                            (item_name, category_id, quantity, price, total_value, branch_id, inventory_img, status) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            
            $insertStmt->bind_param("siiddis", $itemName, $category_id, $quantity, $price, $total_value, $branch_id, $imagePath);
            
            if ($insertStmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Item added successfully!";
                $response['inventory_id'] = $conn->insert_id;
                $response['action'] = 'inserted';
            } else {
                throw new Exception("Database error: " . $insertStmt->error);
            }
            
            $insertStmt->close();
        }
        
        $checkStmt->close();
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Error in add_inventory.php: " . $e->getMessage());
    
    // Clean up uploaded file if there was an error
    if (isset($uploadPath) && file_exists($uploadPath)) {
        unlink($uploadPath);
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>