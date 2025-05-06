<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in and has employee privileges
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Validate required fields
    $requiredFields = [
        'clientFirstName', 'clientLastName', 'clientPhone',
        'deceasedFirstName', 'deceasedLastName', 'dateOfDeath',
        'paymentMethod', 'totalPrice', 'amountPaid',
        'service_id', 'branch_id'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Prepare data for insertion
    $customerID = null; // Traditional services may not have a customer account
    $fname = $_POST['clientFirstName'];
    $mname = $_POST['clientMiddleName'] ?? null;
    $lname = $_POST['clientLastName'];
    $suffix = $_POST['clientSuffix'] ?? null;
    $phone = $_POST['clientPhone'];
    $email = $_POST['clientEmail'] ?? null;
    
    $fname_deceased = $_POST['deceasedFirstName'];
    $mname_deceased = $_POST['deceasedMiddleName'] ?? null;
    $lname_deceased = $_POST['deceasedLastName'];
    $suffix_deceased = $_POST['traditionalDeceasedSuffix'] ?? null;
    $date_of_birth = !empty($_POST['dateOfBirth']) ? $_POST['dateOfBirth'] : null;
    $date_of_death = $_POST['dateOfDeath'];
    $date_of_burial = !empty($_POST['dateOfBurial']) ? $_POST['dateOfBurial'] : null;
    
    $sold_by = $_POST['sold_by'];
    $branch_id = $_POST['branch_id'];
    $service_id = $_POST['service_id'];
    $payment_method = $_POST['paymentMethod'];
    $initial_price = $_POST['totalPrice'];
    $amount_paid = $_POST['amountPaid'];
    
    // Calculate balance
    $balance = max(0, $initial_price - $amount_paid);
    
    // Determine payment status
    $payment_status = ($balance <= 0) ? 'Fully Paid' : 'With Balance';
    
    // Process address
    $deceased_address = '';
    if (!empty($_POST['deceasedStreet'])) {
        $addressParts = [
            $_POST['deceasedStreet'],
            $_POST['deceasedBarangay'] ?? '',
            $_POST['deceasedCity'] ?? '',
            $_POST['deceasedProvince'] ?? '',
            $_POST['deceasedRegion'] ?? '',
            $_POST['deceasedZip'] ?? ''
        ];
        $deceased_address = implode(', ', array_filter($addressParts));
    }
    
    // Process death certificate file
    $death_cert_image = null;
    if (isset($_FILES['deathCertificate'])) {
        $file = $_FILES['deathCertificate'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $death_cert_image = file_get_contents($file['tmp_name']);
        }
    }
    
    // Check if cremation is selected
    $with_cremate = isset($_POST['withCremation']) && $_POST['withCremation'] === 'on' ? 'yes' : 'no';
    
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
    
    // Bind parameters
    $stmt->bind_param(
        "isssssssssssssiiiisddddsssss",
        $customerID, $fname, $mname, $lname, $suffix, $phone, $email,
        $fname_deceased, $mname_deceased, $lname_deceased, $suffix_deceased,
        $date_of_birth, $date_of_death, $date_of_burial, $sold_by, $branch_id,
        $service_id, $payment_method, $initial_price, $initial_price, // discounted_price same as initial for now
        $amount_paid, $balance, 'Pending', $payment_status, $death_cert_image,
        $deceased_address, $with_cremate
    );
    
    // Execute the statement
    if ($stmt->execute()) {
        $sales_id = $conn->insert_id;
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
    $response['message'] = $e->getMessage();
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>