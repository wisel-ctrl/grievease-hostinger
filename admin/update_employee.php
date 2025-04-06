<?php
// update_employee.php
session_start();
include '../db_connect.php';

// Response array
$response = [
    'success' => false,
    'message' => ''
];

// Validate and sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input));
}

// Validate required fields
$requiredFields = [
    'employeeId', 'firstName', 'lastName', 'dateOfBirth', 
    'gender', 'employeeEmail', 'employeePhone', 
    'employeePosition', 'employeeSalary', 'branch'
];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $response['message'] = "Missing required field: $field";
        echo json_encode($response);
        exit();
    }
}

// Validate input data
$employeeId = filter_input(INPUT_POST, 'employeeId', FILTER_VALIDATE_INT);
$firstName = sanitizeInput($_POST['firstName']);
$lastName = sanitizeInput($_POST['lastName']);
$middleName = !empty($_POST['middleName']) ? sanitizeInput($_POST['middleName']) : null;
$suffix = !empty($_POST['suffix']) ? sanitizeInput($_POST['suffix']) : null;
$dateOfBirth = $_POST['dateOfBirth'];
$gender = in_array($_POST['gender'], ['Male', 'Female']) ? $_POST['gender'] : null;
$email = filter_input(INPUT_POST, 'employeeEmail', FILTER_VALIDATE_EMAIL);
$phoneNumber = $_POST['employeePhone'];
$position = sanitizeInput($_POST['employeePosition']);
$salary = filter_input(INPUT_POST, 'employeeSalary', FILTER_VALIDATE_FLOAT);
$branchId = filter_input(INPUT_POST, 'branch', FILTER_VALIDATE_INT);

// Validate data
if (!$employeeId || !$firstName || !$lastName || !$dateOfBirth || 
    !$gender || !$email || !$phoneNumber || !$position || 
    $salary === false || !$branchId) {
    $response['message'] = 'Invalid input data';
    echo json_encode($response);
    exit();
}

try {
    // Prepare SQL statement
    $sql = "UPDATE employee_tb SET 
            fname = ?, 
            lname = ?, 
            mname = ?, 
            suffix = ?, 
            bday = ?, 
            gender = ?, 
            email = ?, 
            phone_number = ?, 
            position = ?, 
            base_salary = ?, 
            branch_id = ?
            WHERE employeeID = ?";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $stmt->bind_param(
        "sssssssssdii", 
        $firstName, 
        $lastName, 
        $middleName, 
        $suffix, 
        $dateOfBirth, 
        $gender, 
        $email, 
        $phoneNumber, 
        $position, 
        $salary, 
        $branchId,
        $employeeId
    );
    
    // Execute the statement
    if ($stmt->execute()) {
        // Check if a row was actually updated
        if ($stmt->affected_rows > 0) {
            $response['success'] = true;
            $response['message'] = 'Employee updated successfully';
        } else {
            $response['message'] = 'No changes made or employee not found';
        }
    } else {
        $response['message'] = 'Database error: ' . $stmt->error;
    }
    
    // Close statement
    $stmt->close();
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
}

// Send JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit();