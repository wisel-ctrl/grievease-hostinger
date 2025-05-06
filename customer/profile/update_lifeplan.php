<?php
require_once '../../db_connect.php';

// Start session and check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Validate required fields
    $requiredFields = ['booking_id', 'benefeciary_fname', 'benefeciary_lname', 'benefeciary_birth'];
    foreach ($requiredFields as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $bookingId = (int)$_POST['booking_id'];
    $userId = (int)$_SESSION['user_id'];

    // Prepare the update query
    $query = "UPDATE lifeplan_booking_tb SET 
                benefeciary_fname = ?,
                benefeciary_mname = ?,
                benefeciary_lname = ?,
                benefeciary_suffix = ?,
                benefeciary_birth = ?,
                benefeciary_address = ?,
                phone = ?,
                relationship_to_client = ?,
                with_cremate = ?,
                booking_status = 'pending',
                modified_at = NOW()
              WHERE lpbooking_id = ? AND customer_id = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Get and sanitize form data
    $fname = trim($_POST['benefeciary_fname']);
    $mname = trim($_POST['benefeciary_mname'] ?? '');
    $lname = trim($_POST['benefeciary_lname']);
    $suffix = trim($_POST['benefeciary_suffix'] ?? '');
    $birth = $_POST['benefeciary_birth'];
    $address = trim($_POST['benefeciary_address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $relationship = trim($_POST['relationship_to_client'] ?? '');
    $withCremate = isset($_POST['with_cremate']) ? 'yes' : 'no';

    // Bind parameters
    $bindResult = $stmt->bind_param(
        "sssssssssii",
        $fname, $mname, $lname, $suffix, $birth, $address,
        $phone, $relationship, $withCremate, $bookingId, $userId
    );

    if (!$bindResult) {
        throw new Exception("Bind failed: " . $stmt->error);
    }

    // Execute query
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception("No records updated. Booking may not exist or data is unchanged.");
    }

    echo json_encode([
        'success' => true,
        'message' => 'Life plan updated successfully. Status reset to pending for review.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
?>