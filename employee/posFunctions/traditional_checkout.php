<?php
session_start();

// Set error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/order_processing_error.log');

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
            throw new Exception("Please fill in all required fields. Missing: $field");
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
    $dateOfBirth = isset($_POST['dateOfBirth']) ? $_POST['dateOfBirth'] : null;
    $dateOfDeath = $_POST['dateOfDeath'];
    $dateOfBurial = isset($_POST['dateOfBurial']) ? $_POST['dateOfBurial'] : null;

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

    // Service and branch info
    $serviceId = intval($_POST['service_id']);
    $branchId = intval($_POST['branch_id']);
    $soldBy = intval($_POST['sold_by']);

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
            'with_cremation' => $withCremation
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

    // Handle file upload (death certificate)
    $deathCertificateImage = null;
    if (isset($_FILES['deathCertificate']) && $_FILES['deathCertificate']['error'] === UPLOAD_ERR_OK) {
        $deathCertificateImage = file_get_contents($_FILES['deathCertificate']['tmp_name']);
        file_put_contents(__DIR__ . '/order_processing_debug.log', "\nDeath certificate uploaded. Size: " . $_FILES['deathCertificate']['size'] . " bytes", FILE_APPEND);
    } else {
        $uploadError = isset($_FILES['deathCertificate']) ? $_FILES['deathCertificate']['error'] : 'No file uploaded';
        file_put_contents(__DIR__ . '/order_processing_debug.log', "\nDeath certificate upload status: $uploadError", FILE_APPEND);
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
        // Insert into sales_tb (using structure from code 2)
        $stmt = $conn->prepare("
            INSERT INTO sales_tb (
                customerID, fname, mname, lname, suffix, phone, email,
                fname_deceased, mname_deceased, lname_deceased, suffix_deceased,
                date_of_birth, date_of_death, date_of_burial, sold_by, branch_id,
                service_id, payment_method, initial_price, discounted_price,
                amount_paid, balance, status, payment_status, death_cert_image,
                deceased_address, with_cremate
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?
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
            "isssssssssssssiiisddddsssss",
            $customerID, $clientFirstName, $clientMiddleName, $clientLastName, $clientSuffix, $clientPhone, $clientEmail,
            $deceasedFirstName, $deceasedMiddleName, $deceasedLastName, $deceasedSuffix,
            $dateOfBirth, $dateOfDeath, $dateOfBurial, $soldBy, $branchId,
            $serviceId, $paymentMethod, $discountedPrice, $totalPrice,
            $amountPaid, $balance, $status, $paymentStatus, $deathCertificateImage,
            $deceasedAddress1, $withCremation
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