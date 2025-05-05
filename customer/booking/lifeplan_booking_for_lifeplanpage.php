<?php
// lifeplan_booking_for_lifeplanpage.php
session_start();
require_once '../../db_connect.php';

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

// Set response header to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'You must be logged in to make a booking.',
        'redirect' => '../Landing_Page/login.php'
    ]);
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
        $relationship_with_beneficiary = $_POST['relationshipWithBeneficiary'];
        
        // Process address
        $beneficiary_address = $_POST['deceasedAddress'] ?? '';
        
        $phone = $_POST['contactNumber'];
        $with_cremate = isset($_POST['cremationOption']) && $_POST['cremationOption'] ? 'yes' : 'no';
        $reference_code = $_POST['referenceNumber'];
        
        // Process payment receipt
        $paymentPath = '';
        
        // Create uploads directory if it doesn't exist
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
        
        // Insert into database
        $stmt = $conn->prepare("
            INSERT INTO lifeplan_booking_tb (
                service_id, branch_id, customer_id, payment_duration, package_price,
                benefeciary_fname, benefeciary_mname, benefeciary_lname, benefeciary_suffix,
                benefeciary_birth, benefeciary_address, phone, with_cremate, 
                booking_status, reference_code, payment_url, relationship_to_client, initial_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)
        ");
        $bookingDate = date('Y-m-d H:i:s');
        $stmt->bind_param(
            "iiiidssssssssssss", 
            $service_id, $branch_id, $customer_id, $payment_duration, $package_price,
            $beneficiary_fname, $beneficiary_mname, $beneficiary_lname, $beneficiary_suffix,
            $beneficiary_birth, $beneficiary_address, $phone, $with_cremate,
            $reference_code, $paymentPath, $relationship_with_beneficiary, $bookingDate
        );
        
        if ($stmt->execute()) {
            // Success
            $_SESSION['booking_success'] = true;
            $_SESSION['booking_message'] = 'Lifeplan booking submitted successfully!';
            $_SESSION['booking_id'] = $stmt->insert_id;
            
            echo json_encode([
                'success' => true,
                'message' => 'Lifeplan booking submitted successfully!',
                'booking_id' => $stmt->insert_id
            ]);
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        // Error
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    // Invalid request method
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

$conn->close();
?>