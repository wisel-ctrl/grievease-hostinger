<?php
header('Content-Type: application/json');

require_once '../../db_connect.php';

// Initialize response
$response = ['exists' => false];

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the input data
    $input = file_get_contents('php://input');
    parse_str($input, $data);
    
    $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
    
    // Check email uniqueness
    if (isset($data['email'])) {
        $email = trim($data['email']);
        
        // Basic email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['exists' => true, 'message' => 'Invalid email format']);
            exit;
        }
        
        // Check if email exists (excluding current user)
        $query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $response['exists'] = $result->num_rows > 0;
    }
    // Check phone number uniqueness
    elseif (isset($data['phone_number'])) {
        $phone_number = trim($data['phone_number']);
        
        // Basic phone number validation (Philippine format)
        if (!preg_match('/^\+63[0-9]{10}$|^[0-9]{11}$/', $phone_number)) {
            echo json_encode(['exists' => true, 'message' => 'Invalid phone number format']);
            exit;
        }
        
        // Check if phone number exists (excluding current user)
        $query = "SELECT id FROM users WHERE phone_number = ? AND id != ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $phone_number, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $response['exists'] = $result->num_rows > 0;
    }
}

echo json_encode($response);
?>