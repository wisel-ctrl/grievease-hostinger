<?php
// lifeplan_booking.php
session_start();
require_once '../../db_connect.php';
require_once 'sms_notification.php';

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Process form data
        $service_id = $_POST['service_id'];
        $branch_id = $_POST['branch_id'];
        $customer_id = $_POST['customerID'];
        $payment_duration = 5; // Fixed 5-year payment plan
        $package_price = $_POST['packagePrice'];
        
        // Beneficiary information
        $beneficiary_fname = $_POST['holderFirstName'];
        $beneficiary_mname = $_POST['holderMiddleName'] ?? '';
        $beneficiary_lname = $_POST['holderLastName'];
        $beneficiary_suffix = $_POST['holderSuffix'] ?? '';
        $beneficiary_birth = $_POST['dateOfBirth'];
        $relationship_with_beneficiary = $_POST['relationshipWithBeneficiary'] ?? ''; // Added this line
        
        // Process address (concatenate into one string)
        $address = json_decode($_POST['holderAddress'], true);
        $beneficiary_address = implode(', ', array_filter([
            $address['street'] ?? '',
            $address['barangay'] ?? '',
            $address['city'] ?? '',
            $address['province'] ?? '',
            $address['region'] ?? ''
        ]));

        // Co-maker information
        $comaker_fname = $_POST['comakerFirstName'];
        $comaker_mname = $_POST['comakerMiddleName'] ?? '';
        $comaker_lname = $_POST['comakerLastName'];
        $comaker_suffix = $_POST['comakerSuffix'] ?? '';
        $comaker_work = $_POST['comakerOccupation'];
        $comaker_idtype = $_POST['comakerIdType'];
        $comaker_idnumber = $_POST['comakerIdNumber'];
        
        // Process co-maker address
        
        $comaker_address = $_POST['comakerAddress'];
        
        $phone = $_POST['contactNumber'];
        $with_cremate = isset($_POST['cremationOption']) ? 'yes' : 'no';
        $reference_code = $_POST['referenceNumber'];
        
        // Process payment receipt
        $paymentPath = '';
        
        // Create uploads directory if it doesn't exist (matching the original code)
        $uploadDir = 'uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        if (isset($_FILES['gcashReceipt']) && $_FILES['gcashReceipt']['error'] === UPLOAD_ERR_OK) {
            $paymentExt = pathinfo($_FILES['gcashReceipt']['name'], PATHINFO_EXTENSION);
            $paymentName = 'payment_' . time() . '.' . $paymentExt;
            $paymentPath = $uploadDir . $paymentName;
            move_uploaded_file($_FILES['gcashReceipt']['tmp_name'], $paymentPath);
        }

        // Process co-maker ID image
        if (isset($_FILES['comakerIdImage']) && $_FILES['comakerIdImage']['error'] === UPLOAD_ERR_OK) {
            $comakerIdExt = pathinfo($_FILES['comakerIdImage']['name'], PATHINFO_EXTENSION);
            $comakerIdName = 'comaker_id_' . time() . '_' . rand(1000, 9999) . '.' . $comakerIdExt;
            $comakerIdImgPath = $uploadDir . $comakerIdName;
            move_uploaded_file($_FILES['comakerIdImage']['tmp_name'], $comakerIdImgPath);
        }

        $bookingDate = date('Y-m-d H:i:s');
        
        // Insert into database
        $stmt = $conn->prepare("
            INSERT INTO lifeplan_booking_tb (
                service_id, branch_id, customer_id, payment_duration, package_price,
                benefeciary_fname, benefeciary_mname, benefeciary_lname, benefeciary_suffix,
                benefeciary_birth, benefeciary_address, phone, with_cremate, 
                booking_status, reference_code, payment_url, relationship_to_client,
                initial_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "iiiidssssssssssss", 
            $service_id, $branch_id, $customer_id, $payment_duration, $package_price,
            $beneficiary_fname, $beneficiary_mname, $beneficiary_lname, $beneficiary_suffix,
            $beneficiary_birth, $beneficiary_address, $phone, $with_cremate,
            $reference_code, $paymentPath, $relationship_with_beneficiary, $bookingDate
        );
        
        if ($stmt->execute()) {
            $bookingId = $stmt->insert_id;
            
            // Prepare booking details for SMS
            $bookingDetails = [
                'beneficiary_fname' => $beneficiary_fname,
                'beneficiary_lname' => $beneficiary_lname,
                'branch_id' => $branch_id,
                'package_price' => $package_price,
                'payment_duration' => $payment_duration
            ];
            
            // Send SMS notification to admin
            $smsResults = sendAdminLifeplanSMSNotification($conn, $bookingDetails, $bookingId);
            
            // Success
            $_SESSION['booking_success'] = true;
            $_SESSION['booking_message'] = 'Lifeplan booking submitted successfully!';
            $_SESSION['booking_id'] = $bookingId;
            
            echo json_encode([
                'success' => true,
                'message' => 'Lifeplan booking submitted successfully!',
                'booking_id' => $bookingId,
                'sms_results' => $smsResults
            ]);
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Error response
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    // Invalid request method
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

$conn->close();
?>