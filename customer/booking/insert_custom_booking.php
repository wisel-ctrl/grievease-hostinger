<?php
header('Content-Type: application/json');

// Database connection
require_once '../../db_connect.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Get the raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE || !$data) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
$requiredFields = [
    'customerId', 
    'deceasedInfo', 
    'branchId', 
    'casket', 
    'packageTotal',
    'documents'
];

foreach ($requiredFields as $field) {
    if (!isset($data[$field])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Missing required field: $field"]);
        exit;
    }
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
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new Exception($conn->error);
    }

    // Bind parameters
    $cremationSelected = isset($data['cremationSelected']) && $data['cremationSelected'] ? 'yes' : 'no';
    $flowerArrangement = $data['flowerArrangement'] ?? null;
    $additionalServices = $data['additionalServices'] ?? null;
    $notes = $data['notes'] ?? null;

    $stmt->bind_param(
        'issssssssisidssssss',
        $data['customerId'],
        $data['deceasedInfo']['firstName'],
        $data['deceasedInfo']['middleName'],
        $data['deceasedInfo']['lastName'],
        $data['deceasedInfo']['suffix'] ?? null,
        $data['deceasedInfo']['dateOfBirth'],
        $data['deceasedInfo']['dateOfDeath'],
        $data['deceasedInfo']['dateOfBurial'],
        $data['deceasedInfo']['address'],
        $data['branchId'],
        $notes,
        $data['casket'],
        $data['packageTotal'],
        $cremationSelected,
        $data['documents']['deathCertificate'],
        $data['documents']['paymentReceipt'],
        $data['documents']['referenceNumber'],
        $flowerArrangement,
        $additionalServices
    );

    // Execute the statement
    if ($stmt->execute()) {
        $bookingId = $conn->insert_id;
        echo json_encode([
            'status' => 'success', 
            'message' => 'Booking created successfully',
            'bookingId' => $bookingId
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// Close connection
$conn->close();
?>