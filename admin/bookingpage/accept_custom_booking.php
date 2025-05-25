<?php
require_once '../../db_connect.php';

// Start session and check if user is logged in and has admin privileges
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

date_default_timezone_set('Asia/Manila');

// SMS Function
function sendSMS($phoneNumber, $message, $bookingStatus) {
    $apiKey = '024cb8782cdb71b2925fb933f6f8635f';
    $senderName = 'GrievEase';
    
    // Sanitize phone number (remove any non-digit characters)
    $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
    
    // Check if phone number starts with 0, if not prepend 0
    if (substr($phoneNumber, 0, 1) !== '0') {
        $phoneNumber = '0' . $phoneNumber;
    }
    
    // Prepare the message based on status
    $fullMessage = "GrievEase Booking Update: ";
    if ($bookingStatus === 'Accepted') {
        $fullMessage .= "Your custom booking has been accepted. " . $message;
    } else {
        $fullMessage .= "Your custom booking has been declined. " . $message;
    }
    
    $parameters = [
        'apikey' => $apiKey,
        'number' => $phoneNumber,
        'message' => $fullMessage,
        'sendername' => $senderName
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.semaphore.co/api/v4/messages');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($parameters));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
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
    $flowerDesign = isset($_POST['flower_id']) ? trim($_POST['flower_id']) : '';
    $inclusion = isset($_POST['inclusions']) ? trim($_POST['inclusions']) : '';
    $withCremate = isset($_POST['with_cremate']) && $_POST['with_cremate'] === 'yes' ? 'yes' : 'no';
    
    // Payment information
    $paymentMethod = isset($_POST['paymentMethod']) ? trim($_POST['paymentMethod']) : '';
    $initialPrice = isset($_POST['initial_price']) ? floatval($_POST['initial_price']) : 0;
    $amountPaid = isset($_POST['amountPaid']) ? floatval($_POST['amountPaid']) : 0;
    
    // Death certificate image path
    $deathCertImage = isset($_POST['deathcert_url']) ? trim($_POST['deathcert_url']) : '';
    
    // Validate required fields
    $errors = [];

    if ($bookingId <= 0) $errors[] = 'bookingId';
    if ($customerId <= 0) $errors[] = 'customerId';
    if ($branchId <= 0) $errors[] = 'branchId';
    if (empty($fnameDeceased)) $errors[] = 'fnameDeceased';
    if (empty($lnameDeceased)) $errors[] = 'lnameDeceased';
    if (empty($paymentMethod)) $errors[] = 'paymentMethod';
    if ($initialPrice <= 0) $errors[] = 'initialPrice';

    if (!empty($errors)) {
        echo json_encode([
            'success' => false,
            'error' => 'Missing or invalid fields: ' . implode(', ', $errors)
        ]);
        exit();
    }
    
    // Generate receipt number (same format as first code)
    $receipt_number = 'RCPT-' . mt_rand(100000, 999999);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert into customsales_tb (without receipt_number)
        $insertQuery = "INSERT INTO customsales_tb (
            customer_id, fname_deceased, mname_deceased, lname_deceased, suffix_deceased,
            date_of_bearth, date_of_death, date_of_burial, sold_by, branch_id,
            casket_id, flower_design, inclusion, payment_method, initial_price, discounted_price,
            amount_paid, death_cert_image, deceased_address, with_cremate, get_timestamp
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insertQuery);
        $acceptDate = date('Y-m-d H:i:s');
        $stmt->bind_param(
            "isssssssiiisssdddssss",
            $customerId, $fnameDeceased, $mnameDeceased, $lnameDeceased, $suffixDeceased,
            $dateOfBirth, $dateOfDeath, $dateOfBurial, $soldBy, $branchId,
            $casketId, $flowerDesign, $inclusion, $paymentMethod, $initialPrice, $initialPrice,
            $amountPaid, $deathCertImage, $deceasedAddress, $withCremate, $acceptDate
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert into customsales_tb: " . $stmt->error);
        }
        
        $customSalesId = $stmt->insert_id;
        $stmt->close();
        
        // Update the booking status to 'Accepted' with receipt_number (like in first code)
        $updateBookingQuery = "UPDATE booking_tb SET 
            status = 'Accepted', 
            accepter_decliner = ?, 
            amount_paid = ?,
            receipt_number = ?,
            accepted_date = CONVERT_TZ(NOW(), 'SYSTEM', '+08:00')
            WHERE booking_id = ?";
        $stmt = $conn->prepare($updateBookingQuery);
        $stmt->bind_param("idsi", $soldBy, $amountPaid, $receipt_number, $bookingId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update booking status: " . $stmt->error);
        }
        $stmt->close();
        
        // Get customer phone number for SMS
        $stmt = $conn->prepare("SELECT phone_number FROM users WHERE id = ?");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $customer = $result->fetch_assoc();
        $stmt->close();
        
        if ($customer && !empty($customer['phone_number'])) {
            $message = "Receipt #: $receipt_number. Your custom package has been accepted. Amount paid: ₱" . number_format($amountPaid, 2);
            $smsResponse = sendSMS($customer['phone_number'], $message, 'Accepted');
            // You might want to log $smsResponse for debugging
        }
        
        // If everything is successful, commit the transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Custom booking accepted successfully',
            'customsales_id' => $customSalesId,
            'receipt_number' => $receipt_number
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