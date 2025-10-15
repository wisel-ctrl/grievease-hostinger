<?php
header('Content-Type: application/json');
require_once '../../db_connect.php';

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    $data = $_POST;
}


if (empty($data['sales_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sales ID is required']);
    exit;
}

try {
    // Step 1: Get current record data including existing file paths
    $getCurrentDataSql = "SELECT amount_paid, death_cert_image, discount_id_img FROM sales_tb WHERE sales_id = ?";
    $getStmt = $conn->prepare($getCurrentDataSql);
    if (!$getStmt) {
        throw new Exception("Prepare failed (get current data): " . $conn->error);
    }

    $getStmt->bind_param("i", $data['sales_id']);
    $getStmt->execute();
    $getResult = $getStmt->get_result();

    if ($getResult->num_rows === 0) {
        throw new Exception("No record found for the given sales_id");
    }

    $row = $getResult->fetch_assoc();
    $amount_paid = (float)$row['amount_paid'];
    $current_death_cert_image = $row['death_cert_image'];
    $current_discount_id_img = $row['discount_id_img'];
    $getStmt->close();

    // Step 2: Handle file uploads
    $death_cert_image = $current_death_cert_image;
    $discount_id_img = $current_discount_id_img;

    // Handle death certificate upload
    if (isset($_FILES['deathCertificate']) && $_FILES['deathCertificate']['error'] === UPLOAD_ERR_OK) {
        $deathCertFile = $_FILES['deathCertificate'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $fileType = mime_content_type($deathCertFile['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Invalid file type for death certificate. Only JPG, PNG, and PDF are allowed.");
        }
        
        // Validate file size (10MB max)
        if ($deathCertFile['size'] > 10 * 1024 * 1024) {
            throw new Exception("Death certificate file is too large. Maximum size is 10MB.");
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($deathCertFile['name'], PATHINFO_EXTENSION);
        $newFilename = uniqid() . '_' . time() . '.' . $fileExtension;
        $uploadPath = '../../customer/booking/uploads/death_cert_' . $newFilename;
        
        // Move uploaded file
        if (move_uploaded_file($deathCertFile['tmp_name'], $uploadPath)) {
            $death_cert_image = 'uploads/death_cert_' . $newFilename;
        } else {
            throw new Exception("Failed to upload death certificate file.");
        }
    }
    
    // Handle discount ID upload
    if (isset($_FILES['discountIdFile']) && $_FILES['discountIdFile']['error'] === UPLOAD_ERR_OK) {
        $discountIdFile = $_FILES['discountIdFile'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        $fileType = mime_content_type($discountIdFile['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception("Invalid file type for discount ID. Only JPG, PNG, and PDF are allowed.");
        }
        
        // Validate file size (10MB max)
        if ($discountIdFile['size'] > 10 * 1024 * 1024) {
            throw new Exception("Discount ID file is too large. Maximum size is 10MB.");
        }
        
        // Generate unique filename
        $fileExtension = pathinfo($discountIdFile['name'], PATHINFO_EXTENSION);
        $newFilename = uniqid() . '_' . time() . '.' . $fileExtension;
        $uploadPath = '../uploads/valid_ids/discount_id_' . $newFilename;
        
        // Move uploaded file
        if (move_uploaded_file($discountIdFile['tmp_name'], $uploadPath)) {
            $discount_id_img = 'uploads/valid_ids/discount_id_' . $newFilename;
        } else {
            throw new Exception("Failed to upload discount ID file.");
        }
    }

    // Step 3: Compute balance
    $service_price = (float)$data['service_price'];
    $balance = $service_price - $amount_paid;

    // Step 4: Update sales record with balance and file paths included
    $sql = "UPDATE sales_tb SET
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
                branch_id = ?,
                service_id = ?,
                discounted_price = ?,
                balance = ?,
                death_cert_image = ?,
                discount_id_img = ?
            WHERE sales_id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed (update): " . $conn->error);
    }

    $stmt->bind_param("issssssssssssssiiddssi",
        $data['customer_id'],
        $data['firstName'],
        $data['middleName'],
        $data['lastName'],
        $data['nameSuffix'],
        $data['phone'],
        $data['email'],
        $data['deceasedFirstName'],
        $data['deceasedMiddleName'],
        $data['deceasedLastName'],
        $data['deceasedSuffix'],
        $data['birthDate'],
        $data['deathDate'],
        $data['burialDate'],
        $data['deceasedAddress'],
        $data['branch'],
        $data['service_id'],
        $data['service_price'],
        $balance,
        $death_cert_image,
        $discount_id_img,
        $data['sales_id']
    );

    $executed = $stmt->execute();

    if (!$executed) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Record updated successfully',
            'amount_paid' => $amount_paid,
            'balance' => $balance,
            'death_cert_image' => $death_cert_image,
            'discount_id_img' => $discount_id_img
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No records were updated. Please check if the data was different.',
            'amount_paid' => $amount_paid,
            'balance' => $balance
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>