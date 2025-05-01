<?php
header('Content-Type: application/json');

// Database connection
require_once '../../db_connect.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Get the raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

try {
    // Prepare the SQL statement
    $stmt = $conn->prepare("
        INSERT INTO booking_tb (
            customerID, 
            deceased_fname, 
            deceased_mname, 
            deceased_lname, 
            deceased_suffix, 
            deceased_birth, 
            deceased_dodeath, 
            deceased_dateOfBurial, 
            deceased_address, 
            branch_id, 
            booking_notes, 
            casket_id, 
            initial_price, 
            with_cremate, 
            deathcert_url, 
            payment_url, 
            reference_code, 
            flower_design, 
            inclusion
        ) VALUES (
            :customerId, 
            :firstName, 
            :middleName, 
            :lastName, 
            :suffix, 
            :dateOfBirth, 
            :dateOfDeath, 
            :dateOfBurial, 
            :address, 
            :branchId, 
            :notes, 
            :casket, 
            :packageTotal, 
            :cremationSelected, 
            :deathCertificate, 
            :paymentReceipt, 
            :referenceNumber, 
            :flowerArrangement, 
            :additionalServices
        )
    ");

    // Bind parameters
    $stmt->bindParam(':customerId', $data['customerId']);
    $stmt->bindParam(':firstName', $data['deceasedInfo']['firstName']);
    $stmt->bindParam(':middleName', $data['deceasedInfo']['middleName']);
    $stmt->bindParam(':lastName', $data['deceasedInfo']['lastName']);
    $stmt->bindParam(':suffix', $data['deceasedInfo']['suffix']);
    $stmt->bindParam(':dateOfBirth', $data['deceasedInfo']['dateOfBirth']);
    $stmt->bindParam(':dateOfDeath', $data['deceasedInfo']['dateOfDeath']);
    $stmt->bindParam(':dateOfBurial', $data['deceasedInfo']['dateOfBurial']);
    $stmt->bindParam(':address', $data['deceasedInfo']['address']);
    $stmt->bindParam(':branchId', $data['branchId']);
    $stmt->bindParam(':notes', $data['notes']);
    $stmt->bindParam(':casket', $data['casket']);
    $stmt->bindParam(':packageTotal', $data['packageTotal']);
    $stmt->bindParam(':cremationSelected', $data['cremationSelected'] ? 'yes' : 'no');
    $stmt->bindParam(':deathCertificate', $data['documents']['deathCertificate']);
    $stmt->bindParam(':paymentReceipt', $data['documents']['paymentReceipt']);
    $stmt->bindParam(':referenceNumber', $data['documents']['referenceNumber']);
    $stmt->bindParam(':flowerArrangement', $data['flowerArrangement']);
    $stmt->bindParam(':additionalServices', $data['additionalServices']);

    // Execute the statement
    if ($stmt->execute()) {
        $bookingId = $conn->lastInsertId();
        echo json_encode([
            'status' => 'success', 
            'message' => 'Booking created successfully',
            'bookingId' => $bookingId
        ]);
    } else {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to create booking'
        ]);
    }
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

// Close connection
$conn = null;
?>