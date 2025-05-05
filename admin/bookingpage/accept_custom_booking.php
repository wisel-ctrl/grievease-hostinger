<?php
require_once '../../db_connect.php';

// Start session and check if user is logged in and has admin privileges
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get all the form data
    $bookingId = isset($_POST['bookingId']) ? intval($_POST['bookingId']) : 0;
    $customerId = isset($_POST['customerId']) ? intval($_POST['customerId']) : 0;
    $branchId = isset($_POST['branchId']) ? intval($_POST['branchId']) : 0;
    $soldBy = $_SESSION['user_id']; // The admin who is processing the booking
    
    // Deceased information
    $fnameDeceased = isset($_POST['deceased_fname']) ? trim($_POST['deceased_fname']) : '';
    $mnameDeceased = isset($_POST['deceased_mname']) ? trim($_POST['deceased_mname']) : '';
    $lnameDeceased = isset($_POST['deceased_lname']) ? trim($_POST['deceased_lname']) : '';
    $suffixDeceased = isset($_POST['deceased_suffix']) ? trim($_POST['deceased_suffix']) : '';
    $dateOfBirth = isset($_POST['deceased_birth']) ? $_POST['deceased_birth'] : null;
    $dateOfDeath = isset($_POST['deceased_dodeath']) ? $_POST['deceased_dodeath'] : null;
    $dateOfBurial = isset($_POST['deceased_dateOfBurial']) ? $_POST['deceased_dateOfBurial'] : null;
    $deceasedAddress = isset($_POST['deceased_address']) ? trim($_POST['deceased_address']) : '';
    
    // Package components
    $casketId = isset($_POST['casket_id']) ? intval($_POST['casket_id']) : null;
    $flowerDesign = isset($_POST['flower_design']) ? trim($_POST['flower_design']) : '';
    $inclusion = isset($_POST['inclusions']) ? trim($_POST['inclusions']) : '';
    $withCremate = isset($_POST['with_cremate']) && $_POST['with_cremate'] === 'yes' ? 'yes' : 'no';
    
    // Payment information
    $paymentMethod = isset($_POST['paymentMethod']) ? trim($_POST['paymentMethod']) : '';
    $initialPrice = isset($_POST['initial_price']) ? floatval($_POST['initial_price']) : 0;
    $amountPaid = isset($_POST['amountPaid']) ? floatval($_POST['amountPaid']) : 0;
    
    // Death certificate image path
    $deathCertImage = isset($_POST['deathcert_url']) ? trim($_POST['deathcert_url']) : '';
    
    // Validate required fields
    if ($bookingId <= 0 || $customerId <= 0 || $branchId <= 0 || empty($fnameDeceased) || empty($lnameDeceased) || 
        empty($paymentMethod) || $initialPrice <= 0) {
        echo json_encode(['success' => false, 'error' => 'Required fields are missing or invalid']);
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert into customsales_tb
        $insertQuery = "INSERT INTO customsales_tb (
            customer_id, fname_deceased, mname_deceased, lname_deceased, suffix_deceased,
            date_of_bearth, date_of_death, date_of_burial, sold_by, branch_id,
            casket_id, flower_design, inclusion, payment_method, initial_price,
            amount_paid, death_cert_image, deceased_address, with_cremate
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param(
            "isssssssiiisssidsss",
            $customerId, $fnameDeceased, $mnameDeceased, $lnameDeceased, $suffixDeceased,
            $dateOfBirth, $dateOfDeath, $dateOfBurial, $soldBy, $branchId,
            $casketId, $flowerDesign, $inclusion, $paymentMethod, $initialPrice,
            $amountPaid, $deathCertImage, $deceasedAddress, $withCremate
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into customsales_tb: " . $stmt->error);
        }
        
        $customSalesId = $stmt->insert_id;
        $stmt->close();
        
        // Update the booking status to 'Accepted'
        $updateBookingQuery = "UPDATE booking_tb SET status = 'Accepted' WHERE booking_id = ?";
        $stmt = $conn->prepare($updateBookingQuery);
        $stmt->bind_param("i", $bookingId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update booking status: " . $stmt->error);
        }
        $stmt->close();
        
        // If everything is successful, commit the transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Custom booking accepted successfully',
            'customsales_id' => $customSalesId
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
} else {
    // Not a POST request
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
}
?>