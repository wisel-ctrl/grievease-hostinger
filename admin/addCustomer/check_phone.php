<?php
// Include database connection
require_once '../../db_connect.php';

// Check if request is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize phone number
    $phone = filter_input(INPUT_POST, 'phoneNumber', FILTER_SANITIZE_STRING);
    
    // Validate phone number
    if (empty($phone)) {
        echo json_encode(['available' => false, 'message' => 'Phone number is required']);
        exit;
    }
    
    // Check if phone number already exists in the database
    $checkPhoneQuery = "SELECT id FROM users WHERE phone_number = ?";
    $stmt = $conn->prepare($checkPhoneQuery);
    $stmt->bind_param("s", $phone);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Phone number already exists
        echo json_encode(['available' => false, 'message' => 'Phone number already in use']);
    } else {
        // Phone number is available
        echo json_encode(['available' => true, 'message' => 'Phone number is available']);
    }
    
    $stmt->close();
} else {
    // Not a POST request
    echo json_encode(['available' => false, 'message' => 'Invalid request method']);
}

// Close connection
$conn->close();
?>