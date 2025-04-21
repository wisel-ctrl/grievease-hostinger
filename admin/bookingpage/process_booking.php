<?php
session_start();
require_once '../../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is admin
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    // Get all the form data
    $bookingId = $_POST['bookingId'] ?? null;
    $amountPaid = $_POST['amountPaid'] ?? null;
    $paymentMethod = $_POST['paymentMethod'] ?? null;
    
    // Customer information
    $firstName = $_POST['first_name'] ?? '';
    $middleName = $_POST['middle_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $suffix = $_POST['suffix'] ?? '';
    $email = $_POST['email'] ?? '';
    $phoneNumber = $_POST['phone_number'] ?? '';
    
    // Deceased information
    $deceasedFname = $_POST['deceased_fname'] ?? '';
    $deceasedMname = $_POST['deceased_mname'] ?? '';
    $deceasedLname = $_POST['deceased_lname'] ?? '';
    $deceasedSuffix = $_POST['deceased_suffix'] ?? '';
    $deceasedAddress = $_POST['deceased_address'] ?? '';
    
    // Service information
    $serviceId = $_POST['service_id'] ?? null;
    $branchId = $_POST['branch_id'] ?? null;
    $initialPrice = $_POST['initial_price'] ?? 0;
    $deathCertUrl = $_POST['deathcert_url'] ?? '';
    $withCremate = $_POST['with_cremate'] ?? 0;

    if ($bookingId && $amountPaid && $paymentMethod) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // 1. First, update the booking status and accepted_date only
            $updateBooking = $conn->prepare("UPDATE booking_tb SET 
                status = 'Accepted', 
                accepted_date = CURRENT_TIMESTAMP()
                WHERE booking_id = ?");
            
            $updateBooking->bind_param("i", $bookingId);
            $updateBooking->execute();

            if ($updateBooking->affected_rows > 0) {
                // 2. Get customerID from booking_tb
                $getCustomerId = $conn->prepare("SELECT customerID FROM booking_tb WHERE booking_id = ?");
                $getCustomerId->bind_param("i", $bookingId);
                $getCustomerId->execute();
                $customerResult = $getCustomerId->get_result();
                $customerData = $customerResult->fetch_assoc();
                $customerID = $customerData['customerID'] ?? null;
                
                if (!$customerID) {
                    throw new Exception("Customer ID not found");
                }
                
                // 3. Calculate balance and payment status
                $balance = $initialPrice - $amountPaid;
                $paymentStatus = ($balance <= 0) ? 'Fully Paid' : 'With Balance';
                
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
                    NULL, NULL, NULL,  -- These dates would need to be fetched from booking_tb if available
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    'Pending', ?, ?, ?, ?
                )");
                
                // Note: Using initial_price for both initial_price and discounted_price as requested
                $insertSales->bind_param(
                    "issssssssssiiiisddddsssi",
                    $customerID, $firstName, $middleName, $lastName, $suffix, $phoneNumber, $email,
                    $deceasedFname, $deceasedMname, $deceasedLname, $deceasedSuffix,
                    $_SESSION['user_id'], $branchId, $serviceId, $paymentMethod,
                    $initialPrice, $initialPrice, $amountPaid, $balance,
                    $paymentStatus, $deathCertUrl, $deceasedAddress, $withCremate
                );
                
                $insertSales->execute();
                
                if ($insertSales->affected_rows > 0) {
                    $conn->commit();
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Booking accepted and sales record created successfully'
                    ]);
                } else {
                    throw new Exception("Failed to create sales record");
                }
            } else {
                throw new Exception("No changes made or booking not found");
            }
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                'success' => false, 
                'message' => 'Database error: ' . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid request parameters']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>