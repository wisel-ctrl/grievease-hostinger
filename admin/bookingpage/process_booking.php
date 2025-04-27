<?php
date_default_timezone_set('Asia/Manila');
session_start();
require_once '../../db_connect.php';

header('Content-Type: application/json');

// Simplified error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check admin privileges
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if action is specified
if (!isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'No action specified']);
    exit;
}

$conn->query("SET time_zone = '+08:00'");

// Handle different actions
switch ($_POST['action']) {
    case 'acceptBooking':
        handleAcceptBooking($conn);
        break;
    case 'declineBooking':
        handleDeclineBooking($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

function handleAcceptBooking($conn) {
    // Basic validation for required fields
    $required = ['bookingId', 'amountPaid', 'paymentMethod'];
    $missing = array_diff($required, array_keys($_POST));

    if (!empty($missing)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields',
            'missing_fields' => $missing
        ]);
        exit;
    }

    // Validate and sanitize input data
    $bookingId = intval($_POST['bookingId']);
    $amountPaid = floatval($_POST['amountPaid']);
    $paymentMethod = htmlspecialchars(trim($_POST['paymentMethod']), ENT_QUOTES, 'UTF-8');

    // Sanitize other inputs using htmlspecialchars
    $sanitizeText = fn($value) => htmlspecialchars(trim($value ?? ''), ENT_QUOTES, 'UTF-8');
    $sanitizeEmail = fn($value) => filter_var(trim($value ?? ''), FILTER_SANITIZE_EMAIL);
    $sanitizeDate = fn($value) => !empty($value) ? date('Y-m-d', strtotime($value)) : null;

    $firstName = $sanitizeText($_POST['first_name']);
    $middleName = $sanitizeText($_POST['middle_name']);
    $lastName = $sanitizeText($_POST['last_name']);
    $suffix = $sanitizeText($_POST['suffix']);
    $email = $sanitizeEmail($_POST['email']);
    $phoneNumber = $sanitizeText($_POST['phone_number']);

    $deceasedFname = $sanitizeText($_POST['deceased_fname']);
    $deceasedMname = $sanitizeText($_POST['deceased_mname']);
    $deceasedLname = $sanitizeText($_POST['deceased_lname']);
    $deceasedSuffix = $sanitizeText($_POST['deceased_suffix']);
    $deceasedAddress = $sanitizeText($_POST['deceased_address']);

    // Sanitize date fields
    $dateOfBirth = $sanitizeDate($_POST['deceased_birth']);
    $dateOfDeath = $sanitizeDate($_POST['deceased_dodeath']);
    $dateOfBurial = $sanitizeDate($_POST['deceased_dateOfBurial']);

    $serviceId = intval($_POST['service_id'] ?? 0);
    $branchId = intval($_POST['branch_id'] ?? 0);
    $initialPrice = floatval($_POST['initial_price'] ?? 0);
    $deathCertUrl = filter_var($_POST['deathcert_url'] ?? '', FILTER_SANITIZE_URL);
    $withCremate = isset($_POST['with_cremate']) ? 'yes' : 'no';

    // Database operations
    $conn->begin_transaction();

    try {
        // Update booking status with PH time
        $stmt = $conn->prepare("UPDATE booking_tb 
                SET status='Accepted', 
                    accepted_date = CONVERT_TZ(NOW(), 'SYSTEM', '+08:00') 
                WHERE booking_id=?");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Booking not found");
        }

        // Rest of your existing code...
        // Get customer ID
        $stmt = $conn->prepare("SELECT customerID FROM booking_tb WHERE booking_id=?");
        $stmt->bind_param("i", $bookingId);
        $stmt->execute();
        $result = $stmt->get_result();
        $customerID = $result->fetch_assoc()['customerID'] ?? null;
        
        if (!$customerID) {
            throw new Exception("Customer not found");
        }

        // Calculate payment status
        $balance = $initialPrice - $amountPaid;
        $paymentStatus = ($balance <= 0) ? 'Fully Paid' : 'With Balance';
        $status = "Pending";

        // Insert sales record with date fields
        $stmt = $conn->prepare("INSERT INTO sales_tb (
            customerID, fname, mname, lname, suffix, phone, email,
            fname_deceased, mname_deceased, lname_deceased, suffix_deceased,
            date_of_birth, date_of_death, date_of_burial,
            sold_by, branch_id, service_id, payment_method,
            initial_price, discounted_price, amount_paid, balance,
            status, payment_status, death_cert_image, deceased_address, with_cremate
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->bind_param(
            "isssssssssssssiiisddddsssss",
            $customerID, $firstName, $middleName, $lastName, $suffix, $phoneNumber, $email,
            $deceasedFname, $deceasedMname, $deceasedLname, $deceasedSuffix,
            $dateOfBirth, $dateOfDeath, $dateOfBurial,
            $_SESSION['user_id'], $branchId, $serviceId, $paymentMethod,
            $initialPrice, $initialPrice, $amountPaid, $balance,
            $status, $paymentStatus, $deathCertUrl, $deceasedAddress, $withCremate
        );
        
        $stmt->execute();
        $salesId = $conn->insert_id;
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Booking accepted successfully',
            'sales_id' => $salesId,
            'payment_status' => $paymentStatus,
            'balance' => $balance
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Operation failed',
            'error' => $e->getMessage()
        ]);
    }
}

function handleDeclineBooking($conn) {
    // Validate required fields for decline
    $required = ['bookingId', 'reason'];
    $missing = array_diff($required, array_keys($_POST));

    if (!empty($missing)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields for decline',
            'missing_fields' => $missing
        ]);
        exit;
    }

    $bookingId = intval($_POST['bookingId']);
    $reason = htmlspecialchars(trim($_POST['reason']), ENT_QUOTES, 'UTF-8');

    // Database operations
    $conn->begin_transaction();

    try {
        // Update booking status to Declined and add reason with PH time
        $stmt = $conn->prepare("UPDATE booking_tb 
                               SET status = 'Declined', 
                                   reason_for_decline = ?, 
                                   decline_date = CONVERT_TZ(NOW(), 'SYSTEM', '+08:00') 
                               WHERE booking_id = ? AND status = 'Pending'");
        $stmt->bind_param("si", $reason, $bookingId);
        $stmt->execute();
        
        if ($stmt->affected_rows === 0) {
            throw new Exception("Booking not found, already processed, or not in Pending status");
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Booking declined successfully',
            'booking_id' => $bookingId,
            'decline_date' => date('Y-m-d H:i:s') // Return current server time for reference
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'Failed to decline booking',
            'error' => $e->getMessage()
        ]);
    }
}