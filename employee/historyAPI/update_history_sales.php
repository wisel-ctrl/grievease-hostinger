<?php
// update_history_sales.php
session_start();

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../Landing_Page/login.php");
    exit();
}

if ($_SESSION['user_type'] != 2) {
    header("Location: ../../Landing_Page/login.php");
    exit();
}

// Database connection
require_once '../../db_connect.php';

// Prepare the response array
$response = array('success' => false, 'message' => '');

try {
    // Validate required fields
    if (empty($_POST['sales_id'])) {
        throw new Exception('Service ID is required');
    }

    // Handle file uploads
    $deathCertPath = null;
    $discountIdPath = null;

    // Upload death certificate if changed
    if (isset($_POST['death_cert_changed']) && $_POST['death_cert_changed'] === '1') {
        if (isset($_FILES['death_certificate']) && $_FILES['death_certificate']['error'] === UPLOAD_ERR_OK) {
            $deathCertPath = uploadDeathCertificate($_FILES['death_certificate']);
        } else {
            // If file was removed but flag is set, set to empty string to remove existing file
            $deathCertPath = '';
        }
    }

    // Upload discount ID image if changed and exists
    if (isset($_POST['discount_id_changed']) && $_POST['discount_id_changed'] === '1') {
        if (isset($_FILES['discount_id_image']) && $_FILES['discount_id_image']['error'] === UPLOAD_ERR_OK) {
            $discountIdPath = uploadDiscountIdImage($_FILES['discount_id_image']);
        } else {
            // If file was removed but flag is set, set to empty string to remove existing file
            $discountIdPath = '';
        }
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
    $types = "issssssssssssssisi";
    
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

    $stmt = $conn->prepare($query);

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

    // Bind parameters
    $stmt->bind_param($types, ...$bindParams);

    // Execute the query
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Service updated successfully';
        
        // If files were uploaded, you might want to delete old files here
        // to avoid cluttering the server with unused files
    } else {
        throw new Exception('Failed to update service: ' . $stmt->error);
    }

    $stmt->close();
} catch (Exception $e) {
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
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $timestamp = time();
    $randomNumber = mt_rand(1000, 9999);
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Invalid file type for death certificate. Allowed: JPG, PNG, PDF');
    }
    
    // Validate file size (10MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('Death certificate file size too large. Maximum 10MB allowed.');
    }
    
    $newFilename = 'death_cert_' . $timestamp . '_' . $randomNumber . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFilename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return 'uploads/' . $newFilename;
    } else {
        throw new Exception('Failed to upload death certificate');
    }
}

function uploadDiscountIdImage($file) {
    $uploadDir = '../../customer/booking/uploads/valid_ids/';
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate unique filename
    $timestamp = time();
    $randomNumber = mt_rand(1000, 9999);
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Validate file type
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        throw new Exception('Invalid file type for discount ID. Allowed: JPG, PNG, PDF');
    }
    
    // Validate file size (10MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        throw new Exception('Discount ID file size too large. Maximum 10MB allowed.');
    }
    
    $newFilename = 'discount_id_' . $timestamp . '_' . $randomNumber . '.' . $fileExtension;
    $uploadPath = $uploadDir . $newFilename;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return 'uploads/valid_ids/' . $newFilename;
    } else {
        throw new Exception('Failed to upload discount ID');
    }
}
?>