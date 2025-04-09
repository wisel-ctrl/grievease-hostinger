<?php
// Include database connection
include '../../db_connect.php';

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to log errors
function logError($message) {
    $logFile = '../error_log.txt';
    file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log the incoming data
    logError("Received POST data: " . print_r($_POST, true));
    logError("Received FILES data: " . print_r($_FILES, true));
    
    // Initialize response array
    $response = [
        'status' => 'error',
        'message' => 'Unknown error occurred'
    ];
    
    try {
        // Extract form data
        $serviceName = $_POST['serviceName'] ?? '';
        $capitalPrice = floatval($_POST['capitalPrice'] ?? 0);
        $sellingPrice = floatval($_POST['sellingPrice'] ?? 0);
        $serviceCategory = intval($_POST['serviceCategory'] ?? 0);
        $casketType = !empty($_POST['casketType']) ? intval($_POST['casketType']) : null;
        $urnType = !empty($_POST['urnType']) ? intval($_POST['urnType']) : null;
        $serviceStatus = 'Active'; // Default to active
        $description = $_POST['serviceDescription'] ?? null;
        
        // Get branch_id from radio button selection
        $branchId = !empty($_POST['branch_id']) ? intval($_POST['branch_id']) : null;
        
        // Handle flower designs - could be single value or array
        if (isset($_POST['flowerDesign'])) {
            if (is_array($_POST['flowerDesign'])) {
                $flowerDesignStr = implode(', ', $_POST['flowerDesign']);
            } else {
                $flowerDesignStr = $_POST['flowerDesign'];
            }
        } elseif (isset($_POST['flowerDesign[]'])) {
            if (is_array($_POST['flowerDesign[]'])) {
                $flowerDesignStr = implode(', ', $_POST['flowerDesign[]']);
            } else {
                $flowerDesignStr = $_POST['flowerDesign[]'];
            }
        } else {
            $flowerDesignStr = null;
        }
        
        // Handle essential services - could be single value or array
        if (isset($_POST['essentialServices'])) {
            if (is_array($_POST['essentialServices'])) {
                $inclusionsStr = implode(', ', $_POST['essentialServices']);
            } else {
                $inclusionsStr = $_POST['essentialServices'];
            }
        } elseif (isset($_POST['essentialServices[]'])) {
            if (is_array($_POST['essentialServices[]'])) {
                $inclusionsStr = implode(', ', $_POST['essentialServices[]']);
            } else {
                $inclusionsStr = $_POST['essentialServices[]'];
            }
        } else {
            $inclusionsStr = null;
        }
        
        // Handle image upload
        $imageUrl = null;
        if (isset($_FILES['serviceImage']) && $_FILES['serviceImage']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/services/';
            
            // Create directory if it doesn't exist
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = pathinfo($_FILES['serviceImage']['name'], PATHINFO_EXTENSION);
            $newFileName = 'service_' . time() . rand(1000, 9999) . '.' . $fileExtension;
            $targetFile = $uploadDir . $newFileName;
            
            if (move_uploaded_file($_FILES['serviceImage']['tmp_name'], $targetFile)) {
                $imageUrl = 'uploads/services/' . $newFileName;
                logError("Image uploaded successfully to: $imageUrl");
            } else {
                logError("Failed to upload image. Upload error: " . $_FILES['serviceImage']['error']);
            }
        }
        
        // Build SQL query - use null for empty values
        $sql = "INSERT INTO services_tb (
                service_name, 
                description,
                service_categoryID, 
                casket_id, 
                urn_id, 
                flower_design, 
                inclusions, 
                capital_price, 
                selling_price, 
                image_url, 
                branch_id,
                status
            ) VALUES (
                '$serviceName', 
                " . ($description ? "'$description'" : "NULL") . ",
                $serviceCategory, 
                " . ($casketType ? $casketType : "NULL") . ", 
                " . ($urnType ? $urnType : "NULL") . ", 
                " . ($flowerDesignStr ? "'$flowerDesignStr'" : "NULL") . ", 
                " . ($inclusionsStr ? "'$inclusionsStr'" : "NULL") . ", 
                $capitalPrice, 
                $sellingPrice, 
                " . ($imageUrl ? "'$imageUrl'" : "NULL") . ", 
                " . ($branchId ? $branchId : "NULL") . ",
                'Active'
            )";
        
        logError("SQL Query: $sql");
        
        // Execute query
        if ($conn->query($sql)) {
            $serviceId = $conn->insert_id;
            $response = [
                'status' => 'success',
                'message' => 'Service added successfully',
                'service_id' => $serviceId
            ];
            logError("Service added successfully with ID: $serviceId");
        } else {
            throw new Exception("Database error: " . $conn->error);
        }
        
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
        logError("Error: " . $errorMessage);
        $response = [
            'status' => 'error',
            'message' => $errorMessage
        ];
    }
    
    // Always return JSON for POST requests
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>