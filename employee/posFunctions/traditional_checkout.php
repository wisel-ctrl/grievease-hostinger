<?php
session_start();

// Set error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/order_processing_error.log');
date_default_timezone_set('Asia/Manila');

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Include database connection
include '../../db_connect.php';

// Initialize response array
$response = ['success' => false, 'message' => '', 'order_id' => ''];

// Log received data for debugging
$logData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'post_data' => $_POST,
    'files_data' => isset($_FILES) ? array_map(function($file) {
        return [
            'name' => $file['name'],
            'type' => $file['type'],
            'size' => $file['size'],
            'error' => $file['error']
        ];
    }, $_FILES) : null,
    'session_data' => $_SESSION
];

file_put_contents(__DIR__ . '/order_processing_debug.log', print_r($logData, true), FILE_APPEND);

try {
    // Validate required fields
    $requiredFields = [
        'clientFirstName', 'clientLastName', 'clientPhone',
        'deceasedFirstName', 'deceasedLastName', 'dateOfDeath',
        'deceasedRegion', 'deceasedProvince', 'deceasedCity', 
        'deceasedBarangay', 'deceasedStreet', 'deceasedZip',
        'paymentMethod', 'totalPrice', 'amountPaid', 'service_id',
        'branch_id', 'sold_by'
    ];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Please fill in all required fields.");
        }
    }

    // Sanitize and validate input data
    $clientFirstName = htmlspecialchars(trim($_POST['clientFirstName']));
    $clientMiddleName = isset($_POST['clientMiddleName']) ? htmlspecialchars(trim($_POST['clientMiddleName'])) : null;
    $clientLastName = htmlspecialchars(trim($_POST['clientLastName']));
    $clientSuffix = isset($_POST['clientSuffix']) ? htmlspecialchars(trim($_POST['clientSuffix'])) : null;
    $clientPhone = htmlspecialchars(trim($_POST['clientPhone']));
    $clientEmail = isset($_POST['clientEmail']) ? filter_var(trim($_POST['clientEmail']), FILTER_SANITIZE_EMAIL) : null;

    $deceasedFirstName = htmlspecialchars(trim($_POST['deceasedFirstName']));
    $deceasedMiddleName = isset($_POST['deceasedMiddleName']) ? htmlspecialchars(trim($_POST['deceasedMiddleName'])) : null;
    $deceasedLastName = htmlspecialchars(trim($_POST['deceasedLastName']));
    $deceasedSuffix = isset($_POST['traditionalDeceasedSuffix']) ? htmlspecialchars(trim($_POST['traditionalDeceasedSuffix'])) : null;
    $dateOfBirth = !empty($_POST['dateOfBirth']) ? $_POST['dateOfBirth'] : null;
    $dateOfDeath = $_POST['dateOfDeath'];
    $dateOfBurial = !empty($_POST['dateOfBurial']) ? $_POST['dateOfBurial'] : null;

    // Date Validations
    $currentDate = date('Y-m-d');
    
    // Validate date of birth - cannot be in the future (only if provided)
    if (!empty($dateOfBirth)) {
        if ($dateOfBirth > $currentDate) {
            throw new Exception("Date of birth cannot be in the future.");
        }
        
        // Validate date of birth cannot be after date of death (only if both provided)
        if (!empty($dateOfDeath) && $dateOfBirth > $dateOfDeath) {
            throw new Exception("Date of birth cannot be after date of death.");
        }
    }
    
    // Validate date of death - cannot be in the future (required field)
    if (!empty($dateOfDeath)) {
        if ($dateOfDeath > $currentDate) {
            throw new Exception("Date of death cannot be in the future.");
        }
        
        // Validate date of death must be after date of birth (only if date of birth provided)
        if (!empty($dateOfBirth) && $dateOfDeath < $dateOfBirth) {
            throw new Exception("Date of death cannot be before date of birth.");
        }
    }
    
    // Validate date of burial - cannot be before date of death (only if both provided)
    if (!empty($dateOfBurial) && !empty($dateOfDeath)) {
        if ($dateOfBurial < $dateOfDeath) {
            throw new Exception("Date of burial cannot be before date of death.");
        }
        
        // Optional: Validate date of burial cannot be too far in the future (e.g., within 1 year)
        $maxBurialDate = date('Y-m-d', strtotime('+1 year'));
        if ($dateOfBurial > $maxBurialDate) {
            throw new Exception("Date of burial cannot be more than 1 year in the future.");
        }
    }

    // Address components
    $deceasedRegion = $_POST['deceasedRegion'];
    $deceasedProvince = $_POST['deceasedProvince'];
    $deceasedCity = $_POST['deceasedCity'];
    $deceasedBarangay = $_POST['deceasedBarangay'];
    $deceasedStreet = htmlspecialchars(trim($_POST['deceasedStreet']));
    $deceasedZip = htmlspecialchars(trim($_POST['deceasedZip']));
    $deceasedAddress1 = htmlspecialchars(trim($_POST['deceased_address']));

    // Payment information
    $paymentMethod = htmlspecialchars(trim($_POST['paymentMethod']));
    $totalPrice = floatval($_POST['totalPrice']);
    $amountPaid = floatval($_POST['amountPaid']);
    $initial_price = floatval($_POST['service_price']);
    $withCremation = isset($_POST['withCremation']) ? 'yes' : 'no';
    
    // Discount information
    $seniorPwdDiscount = isset($_POST['senior_pwd_discount']) && $_POST['senior_pwd_discount'] === 'yes' ? 'yes' : 'no';

    $use_chapel = $_POST['withChapel'] ?? 'No'; // Default to 'No' if not set

    // Service and branch info
    $serviceId = intval($_POST['service_id']);
    $branchId = intval($_POST['branch_id']);
    $soldBy = intval($_POST['sold_by']);
    $defaultTime= date('Y-m-d H:i:s');

    // Log sanitized data for verification
    $sanitizedData = [
        'client_data' => [
            'first_name' => $clientFirstName,
            'middle_name' => $clientMiddleName,
            'last_name' => $clientLastName,
            'suffix' => $clientSuffix,
            'phone' => $clientPhone,
            'email' => $clientEmail
        ],
        'deceased_data' => [
            'first_name' => $deceasedFirstName,
            'middle_name' => $deceasedMiddleName,
            'last_name' => $deceasedLastName,
            'suffix' => $deceasedSuffix,
            'date_of_birth' => $dateOfBirth,
            'date_of_death' => $dateOfDeath,
            'date_of_burial' => $dateOfBurial
        ],
        'address' => [
            'region' => $deceasedRegion,
            'province' => $deceasedProvince,
            'city' => $deceasedCity,
            'barangay' => $deceasedBarangay,
            'street' => $deceasedStreet,
            'zip' => $deceasedZip
        ],
        'payment' => [
            'method' => $paymentMethod,
            'total_price' => $totalPrice,
            'amount_paid' => $amountPaid,
            'with_cremation' => $withCremation,
            'senior_pwd_discount' => $seniorPwdDiscount
        ],
        'service_info' => [
            'service_id' => $serviceId,
            'branch_id' => $branchId,
            'sold_by' => $soldBy
        ]
    ];

    file_put_contents(__DIR__ . '/order_processing_debug.log', "\nSanitized Data:\n" . print_r($sanitizedData, true), FILE_APPEND);

    // Validate prices
    if ($totalPrice <= 0 || $amountPaid <= 0) {
        throw new Exception("Invalid price values. Total: $totalPrice, Paid: $amountPaid");
    }

    // Validate service exists and is active
    $serviceQuery = "SELECT * FROM services_tb WHERE service_id = ? AND status = 'Active'";
    $stmt = $conn->prepare($serviceQuery);
    $stmt->bind_param("i", $serviceId);
    $stmt->execute();
    $serviceResult = $stmt->get_result();

    if ($serviceResult->num_rows === 0) {
        throw new Exception("Selected service is not available. Service ID: $serviceId");
    }

    $service = $serviceResult->fetch_assoc();

    // Validate minimum payment
    $minimumPayment = $service['selling_price'] * 0.05;
    if ($amountPaid < $minimumPayment) {
        throw new Exception("Initial payment must be at least 5% of the total price. Required: $minimumPayment, Provided: $amountPaid");
    }

    // Handle death certificate file upload
    $deathCertificatePath = null;
    if (isset($_FILES['deathCertificate']) && $_FILES['deathCertificate']['error'] === UPLOAD_ERR_OK) {
        $deathCertificate = $_FILES['deathCertificate'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!in_array($deathCertificate['type'], $allowedTypes)) {
            throw new Exception("Invalid file type for death certificate. Allowed: JPG, JPEG, PNG, PDF");
        }
        
        // Validate file size (max 5MB)
        if ($deathCertificate['size'] > 5 * 1024 * 1024) {
            throw new Exception("Death certificate file size too large. Maximum size is 5MB.");
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = '../../customer/booking/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $timestamp = date('Ymd_His');
        $randomNumber = mt_rand(1000, 9999);
        $fileExtension = pathinfo($deathCertificate['name'], PATHINFO_EXTENSION);
        $newFilename = 'death_cert_' . $timestamp . '_' . $randomNumber . '.' . $fileExtension;
        $uploadPath = $uploadDir . $newFilename;
        $deathcertDBpath = 'uploads/' . $newFilename;
        
        // Move uploaded file
        if (move_uploaded_file($deathCertificate['tmp_name'], $uploadPath)) {
            $deathCertificatePath = $deathcertDBpath;
            file_put_contents(__DIR__ . '/order_processing_debug.log', "\nDeath certificate uploaded successfully: " . $deathCertificatePath, FILE_APPEND);
        } else {
            throw new Exception("Failed to upload death certificate file.");
        }
    } else {
        $uploadError = isset($_FILES['deathCertificate']) ? $_FILES['deathCertificate']['error'] : 'No file uploaded';
        file_put_contents(__DIR__ . '/order_processing_debug.log', "\nDeath certificate upload status: $uploadError", FILE_APPEND);
    }

    // Handle discount ID image upload
    $discountIdImgPath = null;
    if (isset($_FILES['discount_id_img']) && $_FILES['discount_id_img']['error'] === UPLOAD_ERR_OK && $seniorPwdDiscount === 'yes') {
        $discountIdImg = $_FILES['discount_id_img'];
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!in_array($discountIdImg['type'], $allowedTypes)) {
            throw new Exception("Invalid file type for discount ID. Allowed: JPG, JPEG, PNG, PDF");
        }
        
        // Validate file size (max 5MB)
        if ($discountIdImg['size'] > 5 * 1024 * 1024) {
            throw new Exception("Discount ID file size too large. Maximum size is 5MB.");
        }
        
        // Create upload directory if it doesn't exist
        $uploadDir = '../../admin/uploads/valid_ids/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $timestamp = date('Ymd_His');
        $randomNumber = mt_rand(1000, 9999);
        $fileExtension = pathinfo($discountIdImg['name'], PATHINFO_EXTENSION);
        $newFilename = 'discount_id_' . $timestamp . '_' . $randomNumber . '.' . $fileExtension;
        $uploadPath = $uploadDir . $newFilename;
        $discountDBpath = 'uploads/valid_ids/' . $newFilename;
        
        // Move uploaded file
        if (move_uploaded_file($discountIdImg['tmp_name'], $uploadPath)) {
            $discountIdImgPath = $discountDBpath;
            file_put_contents(__DIR__ . '/order_processing_debug.log', "\nDiscount ID image uploaded successfully: " . $discountIdImgPath, FILE_APPEND);
        } else {
            throw new Exception("Failed to upload discount ID image.");
        }
    }

    // Calculate balance and payment status
    $balance = max(0, $totalPrice - $amountPaid);
    $paymentStatus = ($balance <= 0) ? 'Fully Paid' : 'With Balance';
    
    // Prepare address string
    $deceasedAddress = implode(', ', array_filter([
        $deceasedStreet,
        $deceasedBarangay,
        $deceasedCity,
        $deceasedProvince,
        $deceasedRegion,
        $deceasedZip
    ]));

    file_put_contents(__DIR__ . '/order_processing_debug.log', "\nDeceased Address: $deceasedAddress", FILE_APPEND);

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into sales_tb (updated with new columns)
        $stmt = $conn->prepare("
            INSERT INTO sales_tb (
                customerID, fname, mname, lname, suffix, phone, email,
                fname_deceased, mname_deceased, lname_deceased, suffix_deceased,
                date_of_birth, date_of_death, date_of_burial, sold_by, branch_id,
                service_id, payment_method, initial_price, discounted_price,
                amount_paid, balance, status, payment_status, death_cert_image,
                deceased_address, with_cremate, get_timestamp, senior_pwd_discount, discount_id_img, use_chapel
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?
            )
        ");
        
        if (!$stmt) {
            $error = $conn->error;
            file_put_contents(__DIR__ . '/order_processing_error.log', "\nPrepare failed: $error", FILE_APPEND);
            throw new Exception("Prepare failed: $error");
        }
        
        // Bind parameters
        $customerID = null; // Traditional services may not have a customer account
        $status = 'Pending';
        $discountedPrice = $initial_price; // discounted_price same as initial for now
        
        $stmt->bind_param(
            "isssssssssssssiiisddddsssssssss",
            $customerID, $clientFirstName, $clientMiddleName, $clientLastName, $clientSuffix, $clientPhone, $clientEmail,
            $deceasedFirstName, $deceasedMiddleName, $deceasedLastName, $deceasedSuffix,
            $dateOfBirth, $dateOfDeath, $dateOfBurial, $soldBy, $branchId,
            $serviceId, $paymentMethod, $discountedPrice, $totalPrice,
            $amountPaid, $balance, $status, $paymentStatus, $deathCertificatePath,
            $deceasedAddress1, $withCremation, $defaultTime, $seniorPwdDiscount, $discountIdImgPath, $use_chapel
        );
        
        // Execute the statement
        if ($stmt->execute()) {
            $sales_id = $conn->insert_id;
            $conn->commit();
            $response = [
                'success' => true,
                'message' => 'Order placed successfully',
                'order_id' => 'SALE-' . str_pad($sales_id, 6, '0', STR_PAD_LEFT)
            ];
            file_put_contents(__DIR__ . '/order_processing_debug.log', "\nOrder successfully created. Sales ID: $sales_id", FILE_APPEND);
        } else {
            $error = $stmt->error;
            file_put_contents(__DIR__ . '/order_processing_error.log', "\nFailed to insert order: $error", FILE_APPEND);
            throw new Exception("Failed to insert order: $error");
        }
        
        $stmt->close();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        file_put_contents(__DIR__ . '/order_processing_error.log', "\nTransaction rolled back: " . $e->getMessage(), FILE_APPEND);
        throw $e;
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    file_put_contents(__DIR__ . '/order_processing_error.log', "\nError: " . $e->getMessage(), FILE_APPEND);
}

// Close database connection
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>