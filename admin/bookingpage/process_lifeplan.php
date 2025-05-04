<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

if ($_SESSION['user_type'] != 1) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

// Process the accept lifeplan action
$response = acceptLifeplan($conn);
echo json_encode($response);

function acceptLifeplan($conn) {
    // Validate required fields - based exactly on the form fields
    $requiredFields = [
        'lifeplanId', 'customerId', 'branchId', 'first_name', 'last_name', 
        'email', 'phone_number', 'beneficiary_fname', 'beneficiary_lname',
        'relationship_with_client', 'service_id', 'package_price', 
        'payment_duration', 'amountPaid', 'paymentMethod'
    ];
    
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            return ['success' => false, 'error' => "Missing required field: $field"];
        }
    }
    
    $lifeplanId = (int)$_POST['lifeplanId'];
    $customerId = (int)$_POST['customerId'];
    $branchId = (int)$_POST['branchId'];
    $serviceId = (int)$_POST['service_id'];
    $packagePrice = (float)$_POST['package_price'];
    $amountPaid = (float)$_POST['amountPaid'];
    $paymentMethod = $conn->real_escape_string($_POST['paymentMethod']);
    $paymentDuration = (int)$_POST['payment_duration'];
    
    // Calculate balance
    $balance = $packagePrice - $amountPaid;
    $paymentStatus = $balance <= 0 ? 'Fully Paid' : 'With Balance';
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // 1. Update the lifeplan booking status to 'Confirmed'
        $updateBookingQuery = "UPDATE lifeplan_booking_tb SET booking_status = 'accepted' WHERE lpbooking_id = ?";
        $stmt = $conn->prepare($updateBookingQuery);
        $stmt->bind_param("i", $lifeplanId);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Failed to update lifeplan booking status");
        }
        
        // 2. Insert all data into single lifeplan_tb based on adjusted query fields
        $insertQuery = "INSERT INTO lifeplan_tb (
            customerID, 
            branch_id, 
            service_id, 
            fname, 
            mname, 
            lname, 
            suffix,
            email, 
            phone,
            benefeciary_fname, 
            benefeciary_mname, 
            benefeciary_lname, 
            benefeciary_suffix,
            benefeciary_dob, 
            benefeciary_address, 
            relationship_to_client, 
            initial_date,
            end_date,
            with_cremate,
            initial_price, 
            custom_price,
            payment_duration, 
            amount_paid, 
            payment_method, 
            payment_status, 
            balance
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ? 
        )";// 26 ?
        
        $stmt = $conn->prepare($insertQuery);
        
        // Set up variables from form fields directly
        $initialDate = isset($_POST['initial_date']) ? $_POST['initial_date'] : date('Y-m-d');
        $endDate = isset($_POST['end_date']) ? $_POST['end_date'] : date('Y-m-d', strtotime("+$paymentDuration years"));
        $withCremate = $_POST['with_cremate'] ?? 'no';
        $middleName = $_POST['middle_name'] ?? '';
        $suffix = $_POST['suffix'] ?? '';
        $beneficiaryMname = $_POST['beneficiary_mname'] ?? '';
        $beneficiarySuffix = $_POST['beneficiary_suffix'] ?? '';
        $beneficiaryBirth = !empty($_POST['beneficiary_birth']) ? $_POST['beneficiary_birth'] : null;
        $beneficiaryAddress = $_POST['beneficiary_address'] ?? '';
        
        $stmt->bind_param(
            "iiissssssssssssssssddidssd", 
            $customerId,
            $branchId,
            $serviceId,
            $_POST['first_name'],
            $middleName,
            $_POST['last_name'],
            $suffix,
            $_POST['email'],
            $_POST['phone_number'],
            $_POST['beneficiary_fname'],
            $beneficiaryMname,
            $_POST['beneficiary_lname'],
            $beneficiarySuffix,
            $beneficiaryBirth,
            $beneficiaryAddress,
            $_POST['relationship_with_client'],
            $initialDate,
            $endDate,
            $withCremate,
            $packagePrice,
            $packagePrice,
            $paymentDuration,
            $amountPaid,
            $paymentMethod,
            $paymentStatus,
            $balance
        );
        
        $stmt->execute();
        $newLifeplanId = $conn->insert_id;
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true, 
            'message' => 'LifePlan accepted and saved successfully', 
            'lifeplanId' => $newLifeplanId
        ];
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>