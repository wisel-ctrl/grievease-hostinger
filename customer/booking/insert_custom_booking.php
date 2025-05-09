<?php
header('Content-Type: application/json');

// Database connection
require_once '../../db_connect.php';
require_once 'sms_notification.php';

// For debugging
error_log("Request received in insert_custom_booking.php");

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Debug: Log all POST data and files
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

// Handle form data - we're now expecting regular POST data, not JSON
$customerId = isset($_POST['customerId']) ? $_POST['customerId'] : null;
$branchId = isset($_POST['branchId']) ? $_POST['branchId'] : null;
$casket = isset($_POST['casket']) ? $_POST['casket'] : null;
$packageTotal = isset($_POST['packageTotal']) ? floatval($_POST['packageTotal']) : 0;
$flowerArrangement = isset($_POST['flowerArrangement']) ? $_POST['flowerArrangement'] : null;
$additionalServices = isset($_POST['additionalServices']) ? $_POST['additionalServices'] : null;
$notes = isset($_POST['notes']) ? $_POST['notes'] : null;

// Deceased information
$deceasedFirstName = isset($_POST['deceasedFirstName']) ? $_POST['deceasedFirstName'] : null;
$deceasedMiddleName = isset($_POST['deceasedMiddleName']) ? $_POST['deceasedMiddleName'] : null;
$deceasedLastName = isset($_POST['deceasedLastName']) ? $_POST['deceasedLastName'] : null;
$deceasedSuffix = isset($_POST['deceasedSuffix']) ? $_POST['deceasedSuffix'] : null;
$dateOfBirth = isset($_POST['dateOfBirth']) ? $_POST['dateOfBirth'] : null;
$dateOfDeath = isset($_POST['dateOfDeath']) ? $_POST['dateOfDeath'] : null;
$dateOfBurial = isset($_POST['dateOfBurial']) ? $_POST['dateOfBurial'] : null;
$deceasedAddress = isset($_POST['deceasedAddress']) ? $_POST['deceasedAddress'] : null;

// File handling
$deathCertificateFile = isset($_FILES['deathCertificate']) ? $_FILES['deathCertificate'] : null;
$paymentReceiptFile = isset($_FILES['paymentReceipt']) ? $_FILES['paymentReceipt'] : null;
$referenceNumber = isset($_POST['referenceNumber']) ? $_POST['referenceNumber'] : null;
$cremationSelected = isset($_POST['cremationSelected']) && $_POST['cremationSelected'] === 'yes' ? 'yes' : 'no';

// Validate required fields
if (!$customerId || !$branchId || !$casket || !$deceasedFirstName || !$deceasedLastName) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

// File handling logic
$deathCertPath = null;
$paymentReceiptPath = null;

// Define upload directory - use absolute path
$uploadDir = __DIR__ . '/uploads/'; // Adjust path as needed based on your file structure

// Make sure the upload directory exists
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        error_log("Failed to create upload directory: " . $uploadDir);
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Failed to create upload directory']);
        exit;
    }
}

// Handle death certificate upload
if ($deathCertificateFile && $deathCertificateFile['error'] === UPLOAD_ERR_OK) {
    // Generate unique filename to prevent conflicts
    $extension = pathinfo($deathCertificateFile['name'], PATHINFO_EXTENSION);
    $filename = uniqid('deathcert_', true) . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($deathCertificateFile['tmp_name'], $targetPath)) {
        // Store relative path in database
        $deathCertPath = 'uploads/' . $filename;
    } else {
        error_log("Failed to move death certificate upload: " . print_r($deathCertificateFile, true));
        $deathCertPath = null;
    }
}

// Handle payment receipt upload
if ($paymentReceiptFile && $paymentReceiptFile['error'] === UPLOAD_ERR_OK) {
    // Generate unique filename to prevent conflicts
    $extension = pathinfo($paymentReceiptFile['name'], PATHINFO_EXTENSION);
    $filename = uniqid('payment_', true) . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($paymentReceiptFile['tmp_name'], $targetPath)) {
        // Store relative path in database
        $paymentReceiptPath = 'uploads/' . $filename;
    } else {
        error_log("Failed to move payment receipt upload: " . print_r($paymentReceiptFile, true));
        $paymentReceiptPath = null;
    }
}

try {
    
    // Set Philippine timezone and get current datetime
    date_default_timezone_set('Asia/Manila');
    $bookingDate = date('Y-m-d H:i:s');
    
    // Prepare the SQL statement
     $stmt = $conn->prepare("
        INSERT INTO booking_tb (
            customerID, 
            deceased_fname, 
            deceased_midname, 
            deceased_lname, 
            deceased_suffix, 
            deceased_birth, 
            deceased_dodeath, 
            deceased_dateOfBurial, 
            deceased_address, 
            branch_id, 
            booking_notes, 
            casket_id, 
            initial_price, 
            with_cremate, 
            deathcert_url, 
            payment_url, 
            reference_code, 
            flower_design, 
            inclusion,
            booking_date
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    // Bind parameters - added bookingDate at the end
    $stmt->bind_param(
        'issssssssisidsssssss',
        $customerId,
        $deceasedFirstName,
        $deceasedMiddleName,
        $deceasedLastName,
        $deceasedSuffix,
        $dateOfBirth,
        $dateOfDeath,
        $dateOfBurial,
        $deceasedAddress,
        $branchId,
        $notes,
        $casket,
        $packageTotal,
        $cremationSelected,
        $deathCertPath,
        $paymentReceiptPath,
        $referenceNumber,
        $flowerArrangement,
        $additionalServices,
        $bookingDate
    );

    // Execute the statement
    if ($stmt->execute()) {
        $bookingId = $conn->insert_id;
        
        
        // Prepare booking details for SMS
        $bookingDetails = [
            'deceased_fname' => $deceasedFirstName,
            'deceased_lname' => $deceasedLastName,
            'service_id' => 'Custom Package', // Since this is a custom booking
            'branch_id' => $branchId,
            'initial_price' => $packageTotal
        ];
        
        // Send SMS notification to admin
        $smsResults = sendAdminSMSNotification($conn, $bookingDetails, $bookingId);
        
        echo json_encode([
            'status' => 'success', 
            'message' => 'Booking created successfully',
            'bookingId' => $bookingId,
            'sms_results' => $smsResults
        ]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

// Close connection
$conn->close();
?>