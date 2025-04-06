<?php
// Include database connection
require_once '../../db_connect.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data and sanitize inputs
    $firstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
    $middleName = filter_input(INPUT_POST, 'middleName', FILTER_SANITIZE_STRING);
    $lastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING);
    $suffix = filter_input(INPUT_POST, 'suffix', FILTER_SANITIZE_STRING);
    $birthdate = filter_input(INPUT_POST, 'birthdate', FILTER_SANITIZE_STRING);
    $phone = filter_input(INPUT_POST, 'customerPhone', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'customerEmail', FILTER_SANITIZE_EMAIL);
    $password = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    $userType = 3; // As specified in requirements
    $isVerified = 1; // As specified in requirements
    $branchLoc = filter_input(INPUT_POST, 'branchLocation', FILTER_SANITIZE_STRING);
    
    // Validate required fields
    if (empty($firstName) || empty($lastName) || empty($birthdate) || empty($phone) || empty($email) || empty($password) || empty($branchLoc)) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }
    
    // Check if email already exists
    $checkEmailQuery = "SELECT id FROM users WHERE email = ?";
    $checkStmt = $conn->prepare($checkEmailQuery);
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email already exists']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();
    
    // Hash the password for security
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Get current timestamp for created_at
    $createdAt = date('Y-m-d H:i:s');
    
    // Prepare SQL statement to insert user
    $sql = "INSERT INTO users (first_name, last_name, middle_name, suffix, birthdate, phone_number, email, password, created_at, user_type, is_verified, branch_loc) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssiis", 
        $firstName, 
        $lastName, 
        $middleName, 
        $suffix, 
        $birthdate, 
        $phone, 
        $email, 
        $hashedPassword, 
        $createdAt, 
        $userType, 
        $isVerified, 
        $branchLoc
    );
    
    // Execute query and check result
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Customer account created successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
    }
    
    $stmt->close();
} else {
    // If not a POST request
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

// Close connection
$conn->close();
?>