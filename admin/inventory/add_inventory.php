<?php
// add_inventory.php

// Include database connection
require_once '../../db_connect.php';

// Initialize response array
$response = array('success' => false, 'message' => '');

try {
    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get form data
        $itemName = $_POST['itemName'] ?? '';
        $category_id = $_POST['category_id'] ?? 0;
        $branch_id = $_POST['branch_id'] ?? 0;
        $quantity = $_POST['quantity'] ?? 0;
        $price = $_POST['unitPrice'] ?? 0.00;
        
        // Validate required fields
        if (empty($itemName) || $category_id <= 0 || $branch_id <= 0 || $quantity <= 0) {
            throw new Exception("Please fill in all required fields with valid values.");
        }
        
        // Process image if uploaded
        $imagePath = null;
        if (isset($_FILES['itemImage']) && $_FILES['itemImage']['error'] == 0) {
            // Validate file is an image
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['itemImage']['tmp_name']);
            
            if (strpos($mime, 'image/') !== 0) {
                throw new Exception("Uploaded file is not a valid image.");
            }
            
            // Create uploads directory if it doesn't exist
            $uploadDir = '../uploads/inventory/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Generate unique filename
            $fileExtension = pathinfo($_FILES['itemImage']['name'], PATHINFO_EXTENSION);
            $uniqueFilename = uniqid('inventory_') . '.' . $fileExtension;
            $uploadPath = $uploadDir . $uniqueFilename;
            
            // Move uploaded file
            if (!move_uploaded_file($_FILES['itemImage']['tmp_name'], $uploadPath)) {
                throw new Exception("Failed to move uploaded file.");
            }
            
            // Store relative path for database
            $imagePath = 'uploads/inventory/' . $uniqueFilename;
        }
        
        // Prepare SQL statement (note: total_value is calculated by the STORED GENERATE column)
        $stmt = $conn->prepare("INSERT INTO inventory_tb 
                        (item_name, category_id, quantity, price, branch_id, inventory_img, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 1)");
        
        // Bind parameters
        $stmt->bind_param("siidis", $itemName, $category_id, $quantity, $price, $branch_id, $imagePath);
        
        // Execute query
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Inventory item added successfully.";
            $response['inventory_id'] = $conn->insert_id;
            $response['image_path'] = $imagePath;
        } else {
            throw new Exception("Failed to add inventory item: " . $stmt->error);
        }
        
        // Close statement
        $stmt->close();
    } else {
        throw new Exception("Invalid request method.");
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // If file was uploaded but query failed, remove the file
    if (isset($uploadPath) && file_exists($uploadPath)) {
        unlink($uploadPath);
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

// Close connection
$conn->close();
?>