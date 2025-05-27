<?php
header('Content-Type: application/json');
require_once '../../db_connect.php';

// Function to handle file upload
function handleFileUpload($file, $customsales_id, $conn) {
    $uploadDir = '../../customer/booking/uploads/';
    
    // Check if upload directory exists, create if not
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Get file info
    $fileName = basename($file['name']);
    $fileTmp = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Allowed file types
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
    
    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 5000000) { // 5MB max
                // Generate unique filename
                $newFileName = 'death_cert_' . $customsales_id . '_' . uniqid() . '.' . $fileExt;
                $fileDest = $uploadDir . $newFileName;
                
                if (move_uploaded_file($fileTmp, $fileDest)) {
                    return $newFileName;
                }
            }
        }
    }
    return false;
}

// Get the raw POST data
$data = $_POST;

// Validate required fields
if (empty($data['customsales_id'])) {
    echo json_encode(['success' => false, 'message' => 'Custom sales ID is required']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    $customsales_id = $data['customsales_id'];
    
    // Prepare the base update query
    $updateQuery = "UPDATE customsales_tb SET ";
    $params = [];
    $types = "";
    $updateFields = [];
    
    // Customer ID
    if (isset($data['customer_id'])) {
        $updateFields[] = "customer_id = ?";
        $params[] = $data['customer_id'];
        $types .= "i";
    }
    
    // Deceased information
    if (isset($data['editCustomDeceasedFirstName'])) {
        $updateFields[] = "fname_deceased = ?";
        $params[] = $data['editCustomDeceasedFirstName'];
        $types .= "s";
    }
    
    if (isset($data['editCustomDeceasedMiddleName'])) {
        $updateFields[] = "mname_deceased = ?";
        $params[] = $data['editCustomDeceasedMiddleName'];
        $types .= "s";
    }
    
    if (isset($data['editCustomDeceasedLastName'])) {
        $updateFields[] = "lname_deceased = ?";
        $params[] = $data['editCustomDeceasedLastName'];
        $types .= "s";
    }
    
    if (isset($data['editCustomDeceasedSuffix'])) {
        $updateFields[] = "suffix_deceased = ?";
        $params[] = $data['editCustomDeceasedSuffix'];
        $types .= "s";
    }
    
    // Dates
    if (isset($data['editCustomBirthDate'])) {
        $updateFields[] = "date_of_bearth = ?";
        $params[] = $data['editCustomBirthDate'];
        $types .= "s";
    }
    
    if (isset($data['editCustomDeathDate'])) {
        $updateFields[] = "date_of_death = ?";
        $params[] = $data['editCustomDeathDate'];
        $types .= "s";
    }
    
    if (isset($data['editCustomBurialDate'])) {
        $updateFields[] = "date_of_burial = ?";
        $params[] = $data['editCustomBurialDate'];
        $types .= "s";
    }
    
    // Service details
    if (isset($data['editCustomFlowerArrangements'])) {
        $updateFields[] = "flower_design = ?";
        $params[] = $data['editCustomFlowerArrangements'];
        $types .= "s";
    }
    
    if (isset($data['editCustomAdditionalServices'])) {
        // Convert textarea input (newline separated) to JSON array
        $inclusions = explode("\n", $data['editCustomAdditionalServices']);
        $inclusions = array_map('trim', $inclusions);
        $inclusions = array_filter($inclusions);
        $updateFields[] = "inclusion = ?";
        $params[] = json_encode($inclusions);
        $types .= "s";
    }
    
    if (isset($data['editCustomServicePrice'])) {
        $updateFields[] = "discounted_price = ?";
        $params[] = $data['editCustomServicePrice'];
        $types .= "d";
    }
    
    // Address handling
    if (isset($data['editCustomDeceasedAddress'])) {
        $updateFields[] = "deceased_address = ?";
        $params[] = $data['editCustomDeceasedAddress'];
        $types .= "s";
    }
    
    // Check if there are fields to update
    if (empty($updateFields)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }
    
    // Complete the update query
    $updateQuery .= implode(", ", $updateFields) . " WHERE customsales_id = ?";
    $params[] = $customsales_id;
    $types .= "i";
    
    // Prepare and execute the update statement
    $stmt = $conn->prepare($updateQuery);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    // Bind parameters
    $stmt->bind_param($types, ...$params);
    
    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    // Handle file upload if present
    if (isset($_FILES['editCustomDeathCert']) && $_FILES['editCustomDeathCert']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['editCustomDeathCert'];
        $newFileName = handleFileUpload($file, $customsales_id, $conn);
        
        if ($newFileName) {
            // Update the death certificate filename in the database
            $stmt = $conn->prepare("UPDATE customsales_tb SET death_cert_image = ? WHERE customsales_id = ?");
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            $stmt->bind_param("si", $newFileName, $customsales_id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Custom service updated successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn)) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
} finally {
    // Close statement if it exists
    if (isset($stmt)) {
        $stmt->close();
    }
}
?>