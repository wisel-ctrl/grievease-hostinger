<?php
session_start();
require_once '../../db_connect.php';

// Set JSON header
header('Content-Type: application/json');

// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is admin
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
        error_log("Unauthorized access attempt by user_id: " . ($_SESSION['user_id'] ?? 'none'));
        echo json_encode([
            'success' => false, 
            'message' => 'Unauthorized access',
            'error_code' => 'AUTH_001'
        ]);
        exit;
    }

    // Log received POST data for debugging
    error_log("Received POST data: " . print_r($_POST, true));

    // Get all the form data with validation
    // Get all the form data with validation
    $rawBookingId = $_POST['bookingId'] ?? null;
    $bookingId = filter_var($rawBookingId, FILTER_VALIDATE_INT);

    // Handle cases where bookingId has leading zeros
    if ($bookingId === false && $rawBookingId !== null) {
        $cleaned = preg_replace('/[^0-9]/', '', $rawBookingId);
        $bookingId = filter_var($cleaned, FILTER_VALIDATE_INT);
    }

    $amountPaid = filter_input(INPUT_POST, 'amountPaid', FILTER_VALIDATE_FLOAT);
    $paymentMethod = filter_input(INPUT_POST, 'paymentMethod', FILTER_SANITIZE_STRING);

    // Debug logging for the booking ID
    error_log("Booking ID Debug - Raw: '$rawBookingId', Processed: '$bookingId'");
    $amountPaid = filter_input(INPUT_POST, 'amountPaid', FILTER_VALIDATE_FLOAT);
    $paymentMethod = filter_input(INPUT_POST, 'paymentMethod', FILTER_SANITIZE_STRING);
    
    // Validate required fields
    if (!$bookingId || !$amountPaid || !$paymentMethod) {
        $missing = [];
        if (!$bookingId) $missing[] = 'bookingId';
        if (!$amountPaid) $missing[] = 'amountPaid';
        if (!$paymentMethod) $missing[] = 'paymentMethod';
        
        error_log("Missing required fields: " . implode(', ', $missing));
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid request parameters',
            'missing_fields' => $missing,
            'error_code' => 'VALID_001'
        ]);
        exit;
    }

    // Sanitize other inputs
    $firstName = filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_STRING) ?? '';
    $middleName = filter_input(INPUT_POST, 'middle_name', FILTER_SANITIZE_STRING) ?? '';
    $lastName = filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_STRING) ?? '';
    $suffix = filter_input(INPUT_POST, 'suffix', FILTER_SANITIZE_STRING) ?? '';
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '';
    $phoneNumber = filter_input(INPUT_POST, 'phone_number', FILTER_SANITIZE_STRING) ?? '';
    
    $deceasedFname = filter_input(INPUT_POST, 'deceased_fname', FILTER_SANITIZE_STRING) ?? '';
    $deceasedMname = filter_input(INPUT_POST, 'deceased_mname', FILTER_SANITIZE_STRING) ?? '';
    $deceasedLname = filter_input(INPUT_POST, 'deceased_lname', FILTER_SANITIZE_STRING) ?? '';
    $deceasedSuffix = filter_input(INPUT_POST, 'deceased_suffix', FILTER_SANITIZE_STRING) ?? '';
    $deceasedAddress = filter_input(INPUT_POST, 'deceased_address', FILTER_SANITIZE_STRING) ?? '';
    
    $serviceId = filter_input(INPUT_POST, 'service_id', FILTER_VALIDATE_INT) ?? null;
    $branchId = filter_input(INPUT_POST, 'branch_id', FILTER_VALIDATE_INT) ?? null;
    $initialPrice = filter_input(INPUT_POST, 'initial_price', FILTER_VALIDATE_FLOAT) ?? 0;
    $deathCertUrl = filter_input(INPUT_POST, 'deathcert_url', FILTER_SANITIZE_URL) ?? '';
    $withCremate = filter_input(INPUT_POST, 'with_cremate', FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

    // Start transaction
    $conn->begin_transaction();
    
    try {
        // 1. Update booking status
        $updateBooking = $conn->prepare("UPDATE booking_tb SET 
            status = 'Accepted', 
            accepted_date = CURRENT_TIMESTAMP()
            WHERE booking_id = ?");
        
        if (!$updateBooking) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $updateBooking->bind_param("i", $bookingId);
        $updateBooking->execute();

        if ($updateBooking->affected_rows === 0) {
            throw new Exception("No booking found with ID: $bookingId");
        }

        error_log("Booking $bookingId status updated to Accepted");

        // 2. Get customerID from booking_tb
        $getCustomerId = $conn->prepare("SELECT customerID FROM booking_tb WHERE booking_id = ?");
        if (!$getCustomerId) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $getCustomerId->bind_param("i", $bookingId);
        $getCustomerId->execute();
        $customerResult = $getCustomerId->get_result();
        $customerData = $customerResult->fetch_assoc();
        
        if (!$customerData) {
            throw new Exception("No customer data found for booking ID: $bookingId");
        }
        
        $customerID = $customerData['customerID'];
        error_log("Retrieved customer ID: $customerID");

        // 3. Calculate financials
        $balance = $initialPrice - $amountPaid;
        $paymentStatus = ($balance <= 0) ? 'Fully Paid' : 'With Balance';
        error_log("Calculated balance: $balance, Payment Status: $paymentStatus");

        // 4. Insert into sales_tb
        $insertSales = $conn->prepare("INSERT INTO sales_tb (
            customerID, fname, mname, lname, suffix, phone, email,
            fname_deceased, mname_deceased, lname_deceased, suffix_deceased,
            date_of_birth, date_of_death, date_of_burial,
            sold_by, branch_id, service_id, payment_method,
            initial_price, discounted_price, amount_paid, balance,
            status, payment_status, death_cert_image, deceased_address, with_cremate
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            NULL, NULL, NULL,
            ?, ?, ?, ?,
            ?, ?, ?, ?,
            'Pending', ?, ?, ?, ?
        )");
        
        if (!$insertSales) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $bindResult = $insertSales->bind_param(
            "issssssssssiiiisddddsssi",
            $customerID, $firstName, $middleName, $lastName, $suffix, $phoneNumber, $email,
            $deceasedFname, $deceasedMname, $deceasedLname, $deceasedSuffix,
            $_SESSION['user_id'], $branchId, $serviceId, $paymentMethod,
            $initialPrice, $initialPrice, $amountPaid, $balance,
            $paymentStatus, $deathCertUrl, $deceasedAddress, $withCremate
        );
        
        if (!$bindResult) {
            throw new Exception("Bind param failed: " . $insertSales->error);
        }
        
        $executeResult = $insertSales->execute();
        
        if (!$executeResult) {
            throw new Exception("Execute failed: " . $insertSales->error);
        }
        
        if ($insertSales->affected_rows === 0) {
            throw new Exception("No rows inserted into sales_tb");
        }
        
        $salesId = $conn->insert_id;
        $conn->commit();
        
        error_log("Successfully created sales record ID: $salesId");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Booking accepted and sales record created successfully',
            'sales_id' => $salesId,
            'customer_id' => $customerID,
            'payment_status' => $paymentStatus,
            'balance' => $balance
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("ERROR: " . $e->getMessage());
        
        echo json_encode([
            'success' => false, 
            'message' => 'Transaction failed',
            'error' => $e->getMessage(),
            'error_code' => 'DB_001',
            'mysql_error' => $conn->error ?? null
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid request method',
        'error_code' => 'REQ_001'
    ]);
}
?>