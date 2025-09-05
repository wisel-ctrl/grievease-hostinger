<?php
// Database connection
include '../../db_connect.php';

// Set default timezone
date_default_timezone_set('Asia/Manila');

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Get form data
    $fname = $_POST['clientFirstName'] ?? '';
    $mname = $_POST['clientMiddleName'] ?? '';
    $lname = $_POST['clientLastName'] ?? '';
    $suffix = $_POST['clientSuffix'] ?? '';
    $phone = $_POST['clientPhone'] ?? '';
    $email = $_POST['clientEmail'] ?? '';
    
    $fname_deceased = $_POST['deceasedFirstName'] ?? '';
    $mname_deceased = $_POST['deceasedMiddleName'] ?? '';
    $lname_deceased = $_POST['deceasedLastName'] ?? '';
    $suffix_deceased = $_POST['deceasedSuffix'] ?? '';
    $date_of_birth = !empty($_POST['dateOfBirth']) ? $_POST['dateOfBirth'] : null;
    $date_of_death = $_POST['dateOfDeath'] ?? '';
    $date_of_burial = !empty($_POST['dateOfBurial']) ? $_POST['dateOfBurial'] : null;
    $deceased_address = $_POST['deceasedAddress'] ?? '';
    
    $branch_id = $_POST['branch_id'] ?? 0;
    $service_id = $_POST['service_id'] ?? 0;
    $payment_method = $_POST['paymentMethod'] ?? '';
    $initial_price = $_POST['service_price'] ?? 0;
    $discounted_price = $_POST['totalPrice'] ?? 0;
    $amount_paid = $_POST['amountPaid'] ?? 0;
    $balance = $_POST['balance'] ?? 0;
    $payment_status = $_POST['payment_status'] ?? 'With Balance';
    
    // Get the cremation checkbox value
    $with_cremate = isset($_POST['withCremation']) && $_POST['withCremation'] === 'on' ? 'yes' : 'no';
    
    $sold_by = $_POST['sold_by'] ?? 1; // Default to admin ID 1
    $status = $_POST['status'] ?? 'Pending';
    
    // Handle file upload
    $death_cert_image = null;
    if (isset($_FILES['deathCertificate']) && $_FILES['deathCertificate']['error'] === UPLOAD_ERR_OK) {
        $death_cert_image = file_get_contents($_FILES['deathCertificate']['tmp_name']);
    }

    // Prepare SQL statement - including with_cremate
    $stmt = $conn->prepare("INSERT INTO sales_tb (
        fname, mname, lname, suffix, phone, email,
        fname_deceased, mname_deceased, lname_deceased, suffix_deceased,
        date_of_birth, date_of_death, date_of_burial, deceased_address,
        branch_id, service_id, payment_method, initial_price, discounted_price, 
        amount_paid, balance, status, payment_status, death_cert_image, sold_by,
        with_cremate
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Bind parameters - added with_cremate
    $stmt->bind_param(
        "ssssssssssssssiisddddsssis",
        $fname, $mname, $lname, $suffix, $phone, $email,
        $fname_deceased, $mname_deceased, $lname_deceased, $suffix_deceased,
        $date_of_birth, $date_of_death, $date_of_burial, $deceased_address,
        $branch_id, $service_id, $payment_method, $initial_price, $discounted_price, 
        $amount_paid, $balance, $status, $payment_status, $death_cert_image, $sold_by,
        $with_cremate
    );

    // Execute the statement
    if ($stmt->execute()) {
        $orderId = $conn->insert_id;
        $response['success'] = true;
        $response['orderId'] = 'SALE-' . str_pad($orderId, 6, '0', STR_PAD_LEFT);
    } else {
        $response['message'] = 'Database error: ' . $stmt->error;
    }

    $stmt->close();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Close database connection
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>