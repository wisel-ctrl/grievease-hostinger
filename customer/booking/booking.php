<?php
//booking.php
session_start();
require_once '../../db_connect.php'; // Make sure this file contains your database connection
require_once 'sms_notification.php';

// Set timezone to Philippines
date_default_timezone_set('Asia/Manila');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to make a booking']);
        exit;
    }

    // Validate required fields
    $requiredFields = [
        'deceasedFirstName', 'deceasedLastName', 'dateOfDeath',
        'referenceNumber',
        'packagePrice', 'service_id', 'branch_id'
    ];

    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
            exit;
        }
    }

    // Process file uploads
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Process death certificate
    $deathCertPath = '';
    if (isset($_FILES['deathCertificate']) && $_FILES['deathCertificate']['error'] === UPLOAD_ERR_OK) {
        $deathCertExt = pathinfo($_FILES['deathCertificate']['name'], PATHINFO_EXTENSION);
        $deathCertName = 'death_cert_' . time() . '.' . $deathCertExt;
        $deathCertPath = $uploadDir . $deathCertName;
        move_uploaded_file($_FILES['deathCertificate']['tmp_name'], $deathCertPath);
    }

    // Process GCash receipt
    $paymentPath = '';
    if (isset($_FILES['gcashReceipt']) && $_FILES['gcashReceipt']['error'] === UPLOAD_ERR_OK) {
        $paymentExt = pathinfo($_FILES['gcashReceipt']['name'], PATHINFO_EXTENSION);
        $paymentName = 'payment_' . time() . '.' . $paymentExt;
        $paymentPath = $uploadDir . $paymentName;
        move_uploaded_file($_FILES['gcashReceipt']['tmp_name'], $paymentPath);
    }

    // Prepare data for database
    $customerID = $_SESSION['user_id'];
    $deceased_fname = $_POST['deceasedFirstName'];
    $deceased_midname = $_POST['deceasedMiddleName'] ?? '';
    $deceased_lname = $_POST['deceasedLastName'];
    $deceased_suffix = $_POST['deceasedSuffix'] ?? '';
    
    $deceased_address = '';
        if (!empty($_POST['deceasedAddress'])) {
            $addressData = json_decode($_POST['deceasedAddress'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $deceased_address = implode(', ', array_filter([
                    $addressData['street'] ?? '',
                    $addressData['barangay'] ?? '',
                    $addressData['city'] ?? '',
                    $addressData['province'] ?? '',
                    $addressData['region'] ?? ''
                ]));
            }
        }
    $deceased_birth = !empty($_POST['dateOfBirth']) ? $_POST['dateOfBirth'] : null;
    $deceased_dodeath = $_POST['dateOfDeath'];
    $deceased_dateOfBurial = !empty($_POST['dateOfBurial']) ? $_POST['dateOfBurial'] : null;
    $service_id = $_POST['service_id'];
    $branch_id = $_POST['branch_id'];
    $with_cremate = isset($_POST['with_cremate']) && $_POST['with_cremate'] === 'yes' ? 'yes' : 'no';
    $initial_price = $_POST['packagePrice'];
    $reference_code = $_POST['referenceNumber'];
    $booking_date = date('Y-m-d H:i:s'); // Current date/time in PH timezone

    try {
        
        // Generate receipt number
        $receipt_number = 'RCPT-' . mt_rand(100000, 999999); // Generates RCPT- followed by 6 random digits
        
        // Insert into database
        $stmt = $conn->prepare("
            INSERT INTO booking_tb (
                customerID, deceased_fname, deceased_midname, deceased_lname, deceased_suffix,
                deceased_address, deceased_birth, deceased_dodeath, deceased_dateOfBurial,
                service_id, with_cremate, branch_id, initial_price, deathcert_url,
                payment_url, reference_code, booking_date, receipt_number
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )
        ");


        $stmt->bind_param(
            "issssssssisdssssss",
            $customerID, $deceased_fname, $deceased_midname, $deceased_lname, $deceased_suffix,
            $deceased_address, $deceased_birth, $deceased_dodeath, $deceased_dateOfBurial,
            $service_id, $with_cremate, $branch_id, $initial_price, $deathCertPath,
            $paymentPath, $reference_code, $booking_date, $receipt_number
        );


        if ($stmt->execute()) {

            $bookingId = $conn->insert_id;

            // Prepare booking details for SMS
            $bookingDetails = [
                'deceased_fname' => $deceased_fname,
                'deceased_lname' => $deceased_lname,
                'service_id' => $service_id,
                'branch_id' => $branch_id,
                'initial_price' => $initial_price
            ];

            // Send SMS notification to admin
            $smsResults = sendAdminSMSNotification($conn, $bookingDetails, $bookingId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Booking successfully submitted',
                'booking_id' => $bookingId,
                'sms_results' => $smsResults
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>