<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in and has employee privileges
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Debug: Log received data
    error_log('POST data: ' . print_r($_POST, true));
    error_log('FILES data: ' . print_r($_FILES, true));
    
    // Validate required fields
    $requiredFields = [
        'clientFirstName', 'clientLastName', 'clientPhone',
        'deceasedFirstName', 'deceasedLastName', 'dateOfDeath',
        'paymentMethod', 'totalPrice', 'amountPaid',
        'service_id', 'branch_id', 'sold_by'
    ];
    
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $missingFields[] = $field;
        }
    }
    
    if (!empty($missingFields)) {
        throw new Exception("Missing required fields: " . implode(', ', $missingFields));
    }
    
    // Prepare data for insertion
    $customerID = null; // Traditional services may not have a customer account
    $fname = $_POST['clientFirstName'];
    $mname = isset($_POST['clientMiddleName']) ? $_POST['clientMiddleName'] : null;
    $lname = $_POST['clientLastName'];
    $suffix = isset($_POST['clientSuffix']) ? $_POST['clientSuffix'] : null;
    $phone = $_POST['clientPhone'];
    $email = isset($_POST['clientEmail']) ? $_POST['clientEmail'] : null;
    
    $fname_deceased = $_POST['deceasedFirstName'];
    $mname_deceased = isset($_POST['deceasedMiddleName']) ? $_POST['deceasedMiddleName'] : null;
    $lname_deceased = $_POST['deceasedLastName'];
    $suffix_deceased = isset($_POST['traditionalDeceasedSuffix']) ? $_POST['traditionalDeceasedSuffix'] : null;
    $date_of_birth = !empty($_POST['dateOfBirth']) ? $_POST['dateOfBirth'] : null;
    $date_of_death = $_POST['dateOfDeath'];
    $date_of_burial = !empty($_POST['dateOfBurial']) ? $_POST['dateOfBurial'] : null;
    
    $sold_by = intval($_POST['sold_by']);
    $branch_id = intval($_POST['branch_id']);
    $service_id = intval($_POST['service_id']);
    $payment_method = $_POST['paymentMethod'];
    $initial_price = floatval($_POST['totalPrice']);
    $amount_paid = floatval($_POST['amountPaid']);
    
    // Calculate balance
    $balance = max(0, $initial_price - $amount_paid);
    
    // Determine payment status
    $payment_status = ($balance <= 0) ? 'Fully Paid' : 'With Balance';
    
    // Process address
    $deceased_address = '';
    if (isset($_POST['deceasedStreet']) && !empty($_POST['deceasedStreet'])) {
        $addressParts = [
            $_POST['deceasedStreet'],
            isset($_POST['deceasedBarangay']) ? $_POST['deceasedBarangay'] : '',
            isset($_POST['deceasedCity']) ? $_POST['deceasedCity'] : '',
            isset($_POST['deceasedProvince']) ? $_POST['deceasedProvince'] : '',
            isset($_POST['deceasedRegion']) ? $_POST['deceasedRegion'] : '',
            isset($_POST['deceasedZip']) ? $_POST['deceasedZip'] : ''
        ];
        $deceased_address = implode(', ', array_filter($addressParts));
    }
    
    // Process death certificate file
    $death_cert_image = null;
    if (isset($_FILES['deathCertificate']) && $_FILES['deathCertificate']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['deathCertificate'];
        $death_cert_image = file_get_contents($file['tmp_name']);
    }
    
    // Check if cremation is selected
    $with_cremate = isset($_POST['withCremation']) && 
                   ($_POST['withCremation'] === 'on' || $_POST['withCremation'] === 'yes' || 
                    $_POST['withCremation'] === '1' || $_POST['withCremation'] === 'true') ? 'yes' : 'no';
    
    // Start transaction
    $conn->begin_transaction();
    
    // Insert into sales_tb
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
    $status = 'Pending';
    $discounted_price = $initial_price; // discounted_price same as initial for now
    
    $stmt->bind_param(
        "isssssssssssssiiiisdddssss",
        $customerID, $fname, $mname, $lname, $suffix, $phone, $email,
        $fname_deceased, $mname_deceased, $lname_deceased, $suffix_deceased,
        $date_of_birth, $date_of_death, $date_of_burial, $sold_by, $branch_id,
        $service_id, $payment_method, $initial_price, $discounted_price,
        $amount_paid, $balance, $status, $payment_status, $death_cert_image,
        $deceased_address, $with_cremate
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
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }
    $response['message'] = $e->getMessage();
    error_log('Error in traditional_checkout.php: ' . $e->getMessage());
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit();