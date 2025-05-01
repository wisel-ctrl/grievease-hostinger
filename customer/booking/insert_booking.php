<?php
header('Content-Type: application/json');

// Database connection
require_once '../../db_connect.php';

// For debugging
error_log("Request received in insert_booking.php");

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Debug: Log all POST data and files
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

// Handle form data
$serviceId = isset($_POST['serviceId']) ? $_POST['serviceId'] : null;
$packageName = isset($_POST['packageName']) ? $_POST['packageName'] : null;
$packagePrice = isset($_POST['packagePrice']) ? floatval($_POST['packagePrice']) : 0;
$packageTotal = isset($_POST['packageTotal']) ? floatval($_POST['packageTotal']) : 0;

// Deceased information - using the deceased_ prefix as sent from JS
$deceasedFirstName = isset($_POST['deceased_firstName']) ? $_POST['deceased_firstName'] : null;
$deceasedMiddleName = isset($_POST['deceased_middleName']) ? $_POST['deceased_middleName'] : null;
$deceasedLastName = isset($_POST['deceased_lastName']) ? $_POST['deceased_lastName'] : null;
$deceasedSuffix = isset($_POST['deceased_suffix']) ? $_POST['deceased_suffix'] : null;
$dateOfBirth = isset($_POST['deceased_dateOfBirth']) ? $_POST['deceased_dateOfBirth'] : null;
$dateOfDeath = isset($_POST['deceased_dateOfDeath']) ? $_POST['deceased_dateOfDeath'] : null;
$dateOfBurial = isset($_POST['deceased_dateOfBurial']) ? $_POST['deceased_dateOfBurial'] : null;
$deceasedAddress = isset($_POST['deceased_address']) ? $_POST['deceased_address'] : null;

// Additional information
$cremationSelected = isset($_POST['cremationSelected']) && $_POST['cremationSelected'] === 'true' ? 'yes' : 'no';
$referenceNumber = isset($_POST['referenceNumber']) ? $_POST['referenceNumber'] : null;

// Validate required fields
if (!$serviceId || !$packageName || !$deceasedFirstName || !$deceasedLastName) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// File handling logic
$uploadDir = 'uploads/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Process death certificate
$deathCertPath = '';
if (isset($_FILES['deathCertificate']) && $_FILES['deathCertificate']['error'] === UPLOAD_ERR_OK) {
    $deathCertExt = pathinfo($_FILES['deathCertificate']['name'], PATHINFO_EXTENSION);
    $deathCertName = 'death_cert_' . time() . '.' . $deathCertExt;
    $deathCertPath = $uploadDir . $deathCertName;
    move_uploaded_file($_FILES['deathCertificate']['tmp_name'], $deathCertPath);
}

// Process payment receipt
$paymentPath = '';
if (isset($_FILES['paymentReceipt']) && $_FILES['paymentReceipt']['error'] === UPLOAD_ERR_OK) {
    $paymentExt = pathinfo($_FILES['paymentReceipt']['name'], PATHINFO_EXTENSION);
    $paymentName = 'payment_' . time() . '.' . $paymentExt;
    $paymentPath = $uploadDir . $paymentName;
    move_uploaded_file($_FILES['paymentReceipt']['tmp_name'], $paymentPath);
}

try {
    // Get customer ID (assuming it's passed or retrieved from session)
    $customerId = isset($_POST['customerId']) ? $_POST['customerId'] : 1; // Default or from session
    
    // Get branch ID (assuming it's passed or retrieved from elsewhere)
    $branchId = isset($_POST['branchId']) ? $_POST['branchId'] : 1; // Default or from configuration
    
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
            initial_price, 
            with_cremate, 
            deathcert_url, 
            payment_url, 
            reference_code,
            service_id,
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    // Bind parameters
    $stmt->bind_param(
        'issssssssidssssi',
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
        $packageTotal,
        $cremationSelected,
        $deathCertPath,
        $paymentPath,
        $referenceNumber,
        $serviceId
    );

    // Execute the statement
    if ($stmt->execute()) {
        $bookingId = $conn->insert_id;
        echo json_encode([
            'success' => true, 
            'message' => 'Booking created successfully',
            'bookingId' => $bookingId
        ]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

// Close connection
$conn->close();
?>