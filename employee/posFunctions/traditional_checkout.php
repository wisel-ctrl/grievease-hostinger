<?php
session_start();

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Include database connection
include '../../db_connect.php';

// Initialize response array
$response = ['success' => false, 'message' => '', 'order_id' => ''];

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

    // Payment information
    $paymentMethod = htmlspecialchars(trim($_POST['paymentMethod']));
    $totalPrice = floatval($_POST['totalPrice']);
    $amountPaid = floatval($_POST['amountPaid']);
    $withCremation = isset($_POST['withCremation']) ? 'yes' : 'no';

    // Service and branch info
    $serviceId = intval($_POST['service_id']);
    $branchId = intval($_POST['branch_id']);
    $soldBy = intval($_POST['sold_by']);

    // Validate prices
    if ($totalPrice <= 0 || $amountPaid <= 0) {
        throw new Exception("Invalid price values.");
    }

    // Validate service exists and is active
    $serviceQuery = "SELECT * FROM services_tb WHERE service_id = ? AND status = 'Active'";
    $stmt = $conn->prepare($serviceQuery);
    $stmt->bind_param("i", $serviceId);
    $stmt->execute();
    $serviceResult = $stmt->get_result();

    if ($serviceResult->num_rows === 0) {
        throw new Exception("Selected service is not available.");
    }

    $service = $serviceResult->fetch_assoc();

    // Validate minimum payment
    $minimumPayment = $service['selling_price'] * 0.5;
    if ($amountPaid < $minimumPayment) {
        throw new Exception("Initial payment must be at least 50% of the total price.");
    }

    // Handle file upload (death certificate)
    $deathCertificateImage = null;
    if (isset($_FILES['deathCertificate']) && $_FILES['deathCertificate']['error'] === UPLOAD_ERR_OK) {
        $deathCertificateImage = file_get_contents($_FILES['deathCertificate']['tmp_name']);
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
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        // Bind parameters
        $customerID = null; // Traditional services may not have a customer account
        $status = 'Pending';
        $discountedPrice = $totalPrice; // discounted_price same as initial for now
        
        $stmt->bind_param(
            "isssssssssssssiiiisdddssss",
            $customerID, $clientFirstName, $clientMiddleName, $clientLastName, $clientSuffix, $clientPhone, $clientEmail,
            $deceasedFirstName, $deceasedMiddleName, $deceasedLastName, $deceasedSuffix,
            $dateOfBirth, $dateOfDeath, $dateOfBurial, $soldBy, $branchId,
            $serviceId, $paymentMethod, $totalPrice, $discountedPrice,
            $amountPaid, $balance, $status, $paymentStatus, $deathCertificateImage,
            $deceasedAddress, $withCremation
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
        } else {
            throw new Exception("Failed to insert order: " . $stmt->error);
        }
        
        $stmt->close();

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

// Close database connection
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>