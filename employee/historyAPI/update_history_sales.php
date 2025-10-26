<?php
// update_history_sales.php
session_start();

// Define error log file path
$errorLogFile = '../../error_log.txt';

// Custom error logging function
function logError($message) {
    global $errorLogFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] ERROR: $message" . PHP_EOL;
    file_put_contents($errorLogFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Custom debug logging function
function logDebug($message) {
    global $errorLogFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] DEBUG: $message" . PHP_EOL;
    file_put_contents($errorLogFile, $logMessage, FILE_APPEND | LOCK_EX);
}

// Create error log file if it doesn't exist and set permissions
if (!file_exists($errorLogFile)) {
    file_put_contents($errorLogFile, "Error Log Created: " . date('Y-m-d H:i:s') . PHP_EOL);
    chmod($errorLogFile, 0644);
}

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id'])) {
    logError("Unauthorized access attempt - User not logged in");
    header("Location: ../../Landing_Page/login.php");
    exit();
}

if ($_SESSION['user_type'] != 2) {
    logError("Unauthorized access attempt - User type invalid. User ID: " . $_SESSION['user_id'] . ", User Type: " . $_SESSION['user_type']);
    header("Location: ../../Landing_Page/login.php");
    exit();
}

// Database connection
require_once '../../db_connect.php';

// Prepare the response array
$response = array('success' => false, 'message' => '');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Validate required fields
    if (empty($_POST['sales_id'])) {
        throw new Exception('Service ID is required');
    }

    // Debug: Check what files are received
    logDebug("Files received: " . print_r($_FILES, true));
    logDebug("POST data: " . print_r($_POST, true));

    // Handle file uploads
    $deathCertPath = null;
    $discountIdPath = null;

    // Upload death certificate if changed
    if (isset($_POST['death_cert_changed']) && $_POST['death_cert_changed'] === '1') {
        if (isset($_FILES['death_certificate']) && $_FILES['death_certificate']['error'] === UPLOAD_ERR_OK) {
            $deathCertPath = uploadDeathCertificate($_FILES['death_certificate']);
            logDebug("Death certificate uploaded to: " . $deathCertPath);
        } else {
            // If file was removed but flag is set, set to empty string to remove existing file
            $deathCertPath = '';
            logDebug("Death certificate removed");
        }
    } else {
        logDebug("Death certificate not changed");
    }

    // Upload discount ID image if changed and exists
    if (isset($_POST['discount_id_changed']) && $_POST['discount_id_changed'] === '1') {
        if (isset($_FILES['discount_id_image']) && $_FILES['discount_id_image']['error'] === UPLOAD_ERR_OK) {
            $discountIdPath = uploadDiscountIdImage($_FILES['discount_id_image']);
            logDebug("Discount ID uploaded to: " . $discountIdPath);
        } else {
            // If file was removed but flag is set, set to empty string to remove existing file
            $discountIdPath = '';
            logDebug("Discount ID removed");
        }
    } else {
        logDebug("Discount ID not changed");
    }

    // Prepare the SQL query
    $query = "UPDATE sales_tb SET
                customerID = ?,
                fname = ?,
                mname = ?,
                lname = ?,
                suffix = ?,
                phone = ?,
                email = ?,
                fname_deceased = ?,
                mname_deceased = ?,
                lname_deceased = ?,
                suffix_deceased = ?,
                date_of_birth = ?,
                date_of_death = ?,
                date_of_burial = ?,
                deceased_address = ?,
                service_id = ?,
                discounted_price = ?";

    // Add file fields to query if they are set
    $params = array();
    $types = "issssssssssssssid";
    
    if ($deathCertPath !== null) {
        $query .= ", death_cert_image = ?";
        $params[] = $deathCertPath;
        $types .= "s";
    }
    
    if ($discountIdPath !== null) {
        $query .= ", discount_id_img = ?";
        $params[] = $discountIdPath;
        $types .= "s";
    }

    $query .= " WHERE sales_id = ?";

    logDebug("SQL Query: " . $query);
    logDebug("Parameter types: " . $types);

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    // Handle null customerID (when no customer is selected)
    $customerID = !empty($_POST['customer_id']) ? $_POST['customer_id'] : NULL;

    // Get the deceased address
    $deceasedAddress = $_POST['deceased_address'];

    // Build parameters array
    $bindParams = array(
        $customerID,
        $_POST['firstName'],
        $_POST['middleName'],
        $_POST['lastName'],
        $_POST['nameSuffix'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['deceasedFirstName'],
        $_POST['deceasedMiddleName'],
        $_POST['deceasedLastName'],
        $_POST['deceasedSuffix'],
        $_POST['birthDate'],
        $_POST['deathDate'],
        $_POST['burialDate'],
        $deceasedAddress,
        $_POST['service_id'],
        $_POST['service_price']
    );

    // Add file paths to parameters if they exist
    if ($deathCertPath !== null) {
        $bindParams[] = $deathCertPath;
    }
    if ($discountIdPath !== null) {
        $bindParams[] = $discountIdPath;
    }

    // Add sales_id as the last parameter
    $bindParams[] = $_POST['sales_id'];
    $types .= "i";

    logDebug("Bind parameters: " . print_r($bindParams, true));

    // Bind parameters
    $stmt->bind_param($types, ...$bindParams);

    // Execute the query
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Service updated successfully';
        logDebug("Update successful for sales_id: " . $_POST['sales_id']);
    } else {
        throw new Exception('Failed to update service: ' . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
    logError("Exception caught: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

// Close database connection
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);

// File upload functions
function uploadDeathCertificate($file) {
    $uploadDir = '../../customer/booking/uploads/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            logError("Failed to create death certificate upload directory: " . $uploadDir);
            throw new Exception('Failed to create upload directory');
        }
    }
    
    // Generate unique filename
    $timestamp = time();
    $randomNumber = mt_rand(1000, 9999);
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        logError("Invalid death certificate file type: " . $fileExtension . " for file: " . $file['name']);
        throw new Exception('Invalid file type for death certificate. Allowed: JPG, PNG, PDF');
    }
    
    // Validate file size (10MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        logError("Death certificate file too large: " . $file['size'] . " bytes for file: " . $file['name']);
        throw new Exception('Death certificate file size too large. Maximum 10MB allowed.');
    }
    
    $newFilename = 'death_cert_' . $timestamp . '_' . $randomNumber . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFilename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        logDebug("Death certificate successfully uploaded: " . $uploadPath);
        return 'uploads/' . $newFilename;
    } else {
        $errorMsg = 'Failed to upload death certificate. Upload error: ' . $file['error'];
        logError($errorMsg . " for file: " . $file['name']);
        throw new Exception($errorMsg);
    }
}

function uploadDiscountIdImage($file) {
    $uploadDir = '../../admin/uploads/valid_ids/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            logError("Failed to create discount ID upload directory: " . $uploadDir);
            throw new Exception('Failed to create upload directory');
        }
    }
    
    // Generate unique filename
    $timestamp = time();
    $randomNumber = mt_rand(1000, 9999);
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        logError("Invalid discount ID file type: " . $fileExtension . " for file: " . $file['name']);
        throw new Exception('Invalid file type for discount ID. Allowed: JPG, PNG, PDF');
    }
    
    // Validate file size (10MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        logError("Discount ID file too large: " . $file['size'] . " bytes for file: " . $file['name']);
        throw new Exception('Discount ID file size too large. Maximum 10MB allowed.');
    }
    
    $newFilename = 'discount_id_' . $timestamp . '_' . $randomNumber . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFilename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        logDebug("Discount ID successfully uploaded: " . $uploadPath);
        return 'uploads/valid_ids/' . $newFilename;
    } else {
        $errorMsg = 'Failed to upload discount ID. Upload error: ' . $file['error'];
        logError($errorMsg . " for file: " . $file['name']);
        throw new Exception($errorMsg);
    }
}
?>