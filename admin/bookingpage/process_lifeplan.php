<?php
session_start();
require_once '../../db_connect.php';

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
    $fullMessage = "GrievEase Life Plan Update: ";
    if ($bookingStatus === 'Accepted') {
        $fullMessage .= "Your life plan has been accepted. " . $message;
    } else {
        $fullMessage .= "Your life plan has been declined. " . $message;
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

// Process the accept lifeplan action
$response = acceptLifeplan($conn);
echo json_encode($response);

function acceptLifeplan($conn) {
    // Validate required fields - based exactly on the form fields
    $requiredFields = [
        'lifeplanId', 'customerId', 'branchId', 'first_name', 'last_name', 
        'email', 'phone_number', 'beneficiary_fname', 'beneficiary_lname',
        'relationship_with_client', 'service_id', 'package_price', 
        'payment_duration', 'amountPaid', 'paymentMethod', 'with_cremate',
        'beneficiary_address'
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
    $phoneNumber = $_POST['phone_number'];
    
    // Calculate balance
    $balance = $packagePrice - $amountPaid;
    $paymentStatus = $balance <= 0 ? 'paid' : 'ongoing';
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // 1. Update the lifeplan booking status to 'Confirmed'
        $updateBookingQuery = "UPDATE lifeplan_booking_tb 
                      SET booking_status = 'accepted', 
                          acceptdecline_date = CONVERT_TZ(NOW(), 'SYSTEM', '+08:00'),
                          amount_paid = ?
                      WHERE lpbooking_id = ?";
        $stmt = $conn->prepare($updateBookingQuery);
        $stmt->bind_param("di", $amountPaid, $lifeplanId);
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
            balance,
            comaker_fname,
            comaker_mname,
            comaker_lname,
            comaker_suffix,
            comaker_address,
            comaker_work,
            comaker_idtype,
            comaker_idnumber,
            comaker_idimg
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?, ?, ? 
        )";
        
        $stmt = $conn->prepare($insertQuery);
        
        // Set up variables from form fields directly
        $initialDate = isset($_POST['initial_date']) ? date('Y-m-d', strtotime($_POST['initial_date'])) : date('Y-m-d');
        $endDate = isset($_POST['end_date']) ? date('Y-m-d', strtotime($_POST['end_date'])) : date('Y-m-d', strtotime("+$paymentDuration years"));
        $withCremate = $_POST['with_cremate'] === 'yes' ? 'yes' : 'no';
        $middleName = $_POST['middle_name'] ?? '';
        $suffix = $_POST['suffix'] ?? '';
        $beneficiaryMname = $_POST['beneficiary_mname'] ?? '';
        $beneficiarySuffix = $_POST['beneficiary_suffix'] ?? '';
        $beneficiaryBirth = !empty($_POST['beneficiary_birth']) ? date('Y-m-d', strtotime($_POST['beneficiary_birth'])) : null;
        $beneficiaryAddress = $_POST['beneficiary_address'] ?? '';

        //comaker information
        $comaker_fname     = isset($_POST['comaker_fname']) ? trim($_POST['comaker_fname']) : '';
        $comaker_mname     = isset($_POST['comaker_mname']) ? trim($_POST['comaker_mname']) : '';
        $comaker_lname     = isset($_POST['comaker_lname']) ? trim($_POST['comaker_lname']) : '';
        $comaker_suffix    = isset($_POST['comaker_suffix']) ? trim($_POST['comaker_suffix']) : '';
        $comaker_address   = isset($_POST['comaker_address']) ? trim($_POST['comaker_address']) : '';
        $comaker_work      = isset($_POST['comaker_work']) ? trim($_POST['comaker_work']) : '';
        $comaker_idtype    = isset($_POST['comaker_idtype']) ? trim($_POST['comaker_idtype']) : '';
        $comaker_idnumber  = isset($_POST['comaker_idnumber']) ? trim($_POST['comaker_idnumber']) : '';
        $comaker_idimg     = isset($_POST['comaker_idimg']) ? trim($_POST['comaker_idimg']) : '';

         $sourcePath = str_replace("../customer/booking", "../../customer/booking", $comaker_idimg);

        // Step 2: Destination directory
        $destinationDir = "../uploads/comaker_IDs/";

        // Make sure the directory exists
        if (!file_exists($destinationDir)) {
            mkdir($destinationDir, 0777, true);
        }

        // Step 3: Create new filename
        $timestamp = time();
        $extension = pathinfo($sourcePath, PATHINFO_EXTENSION); // jpg, png, etc.
        $safeFname = preg_replace('/[^A-Za-z0-9]/', '', $comaker_fname); // remove spaces/special chars
        $safeLname = preg_replace('/[^A-Za-z0-9]/', '', $comaker_lname);

        $newFilename = "valid_id_" . $safeFname . "_" . $safeLname . "_" . $timestamp . "." . $extension;

        // Step 4: Full destination path
        $destinationPath = $destinationDir . $newFilename;

        // Step 5: Copy the file
        if (file_exists($sourcePath)) {
            if (copy($sourcePath, $destinationPath)) {
                // Relative path for database
                $filepath_comakerimg = "uploads/comaker_IDs/" . $newFilename;
                echo "File copied successfully!<br>";
                echo "New file path: " . $filepath_comakerimg;
            } else {
                echo "Error copying file.";
            }
        } else {
            echo "Source file not found: " . $sourcePath;
        }
        
        $stmt->bind_param(
            "iiissssssssssssssssddidssdsssssssss", 
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
            $_POST['beneficiary_address'],
            $_POST['relationship_with_client'],
            $initialDate,
            $endDate,
            $_POST['with_cremate'],
            $packagePrice,
            $packagePrice,
            $paymentDuration,
            $amountPaid,
            $paymentMethod,
            $paymentStatus,
            $balance,
            $comaker_fname,
            $comaker_mname,
            $comaker_lname,
            $comaker_suffix,
            $comaker_address,
            $comaker_work,
            $comaker_idtype,
            $comaker_idnumber,
            $destinationPath
        );
        
        $stmt->execute();
        $newLifeplanId = $conn->insert_id;
        
        // Send SMS notification
        if (!empty($phoneNumber)) {
            $message = "Amount paid: ₱" . number_format($amountPaid, 2) . ". Balance: ₱" . number_format($balance, 2);
            $smsResponse = sendSMS($phoneNumber, $message, 'Accepted');
            // You might want to log $smsResponse for debugging
        }
        
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