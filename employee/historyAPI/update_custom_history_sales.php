<?php
header('Content-Type: application/json');
require_once '../includes/dbh.inc.php';

// Function to handle file upload
function handleFileUpload($file, $customsales_id, $pdo) {
    $uploadDir = '../uploads/death_certificates/';
    
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
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Check if JSON decoding was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
if (empty($data['customsales_id'])) {
    echo json_encode(['success' => false, 'message' => 'Custom sales ID is required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $customsales_id = $data['customsales_id'];
    
    // Prepare the base update query
    $updateQuery = "UPDATE customsales_tb SET ";
    $params = [];
    $updateFields = [];
    
    // Customer ID
    if (isset($data['customer_id'])) {
        $updateFields[] = "customer_id = ?";
        $params[] = $data['customer_id'];
    }
    
    // Deceased information
    if (isset($data['editCustomDeceasedFirstName'])) {
        $updateFields[] = "fname_deceased = ?";
        $params[] = $data['editCustomDeceasedFirstName'];
    }
    
    if (isset($data['editCustomDeceasedMiddleName'])) {
        $updateFields[] = "mname_deceased = ?";
        $params[] = $data['editCustomDeceasedMiddleName'];
    }
    
    if (isset($data['editCustomDeceasedLastName'])) {
        $updateFields[] = "lname_deceased = ?";
        $params[] = $data['editCustomDeceasedLastName'];
    }
    
    if (isset($data['editCustomDeceasedSuffix'])) {
        $updateFields[] = "suffix_deceased = ?";
        $params[] = $data['editCustomDeceasedSuffix'];
    }
    
    // Dates
    if (isset($data['editCustomBirthDate'])) {
        $updateFields[] = "date_of_bearth = ?";
        $params[] = $data['editCustomBirthDate'];
    }
    
    if (isset($data['editCustomDeathDate'])) {
        $updateFields[] = "date_of_death = ?";
        $params[] = $data['editCustomDeathDate'];
    }
    
    if (isset($data['editCustomBurialDate'])) {
        $updateFields[] = "date_of_burial = ?";
        $params[] = $data['editCustomBurialDate'];
    }
    
    // Service details
    if (isset($data['editCustomFlowerArrangements'])) {
        $updateFields[] = "flower_design = ?";
        $params[] = $data['editCustomFlowerArrangements'];
    }
    
    if (isset($data['editCustomAdditionalServices'])) {
        // Convert textarea input (newline separated) to JSON array
        $inclusions = explode("\n", $data['editCustomAdditionalServices']);
        $inclusions = array_map('trim', $inclusions);
        $inclusions = array_filter($inclusions);
        $updateFields[] = "inclusion = ?";
        $params[] = json_encode($inclusions);
    }
    
    if (isset($data['editCustomServicePrice'])) {
        $updateFields[] = "discounted_price = ?";
        $params[] = $data['editCustomServicePrice'];
    }
    
    // Address handling
    if (isset($data['editCustomRegion']) || isset($data['editCustomProvince']) || 
        isset($data['editCustomCity']) || isset($data['editCustomBarangay']) || 
        isset($data['editCustomStreetAddress'])) {
        
        $address = [
            'region' => $data['editCustomRegion'] ?? '',
            'province' => $data['editCustomProvince'] ?? '',
            'city' => $data['editCustomCity'] ?? '',
            'barangay' => $data['editCustomBarangay'] ?? '',
            'street' => $data['editCustomStreetAddress'] ?? ''
        ];
        
        $updateFields[] = "deceased_address = ?";
        $params[] = json_encode($address);
    }
    
    // Check if there are fields to update
    if (empty($updateFields)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }
    
    // Complete the update query
    $updateQuery .= implode(", ", $updateFields) . " WHERE customsales_id = ?";
    $params[] = $customsales_id;
    
    // Prepare and execute the update statement
    $stmt = $pdo->prepare($updateQuery);
    $stmt->execute($params);
    
    // Handle file upload if present
    if (!empty($_FILES['editCustomDeathCert'])) {
        $file = $_FILES['editCustomDeathCert'];
        $newFileName = handleFileUpload($file, $customsales_id, $pdo);
        
        if ($newFileName) {
            // Update the death certificate filename in the database
            $stmt = $pdo->prepare("UPDATE customsales_tb SET death_cert_image = ? WHERE customsales_id = ?");
            $stmt->execute([$newFileName, $customsales_id]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Custom service updated successfully']);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>