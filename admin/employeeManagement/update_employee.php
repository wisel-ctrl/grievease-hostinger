<?php
// update_employee.php
session_start();
include '../../db_connect.php';

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
    'employeePosition', 'editPaymentStructure', 'branch'
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
$paymentStructure = in_array($_POST['editPaymentStructure'], ['monthly', 'commission', 'both']) ? $_POST['editPaymentStructure'] : null;
$branchId = filter_input(INPUT_POST, 'branch', FILTER_VALIDATE_INT);

// Validate salary fields based on payment structure
$monthlySalary = null;
$commissionSalary = null;

if ($paymentStructure === 'monthly' || $paymentStructure === 'both') {
    if (!isset($_POST['monthlySalary']) || empty($_POST['monthlySalary'])) {
        $response['message'] = "Monthly salary is required for the selected payment structure";
        echo json_encode($response);
        exit();
    }
    $monthlySalary = filter_input(INPUT_POST, 'monthlySalary', FILTER_VALIDATE_FLOAT);
    if ($monthlySalary === false || $monthlySalary <= 0) {
        $response['message'] = "Invalid monthly salary amount";
        echo json_encode($response);
        exit();
    }
}

if ($paymentStructure === 'commission' || $paymentStructure === 'both') {
    if (!isset($_POST['commissionSalary']) || empty($_POST['commissionSalary'])) {
        $response['message'] = "Commission salary is required for the selected payment structure";
        echo json_encode($response);
        exit();
    }
    $commissionSalary = filter_input(INPUT_POST, 'commissionSalary', FILTER_VALIDATE_FLOAT);
    if ($commissionSalary === false || $commissionSalary <= 0) {
        $response['message'] = "Invalid commission salary amount";
        echo json_encode($response);
        exit();
    }
}

// Set base_salary based on payment structure
$baseSalary = null; // Default to NULL

if ($paymentStructure === 'monthly') {
    // For monthly-only, base_salary is NULL (as requested)
    $baseSalary = null;
} elseif ($paymentStructure === 'commission') {
    // For commission-only, store commission amount in base_salary
    $baseSalary = $commissionSalary;
} elseif ($paymentStructure === 'both') {
    // For "both" payment structure, store commission amount in base_salary
    $baseSalary = $commissionSalary;
}

// Validate data
if (!$employeeId || !$firstName || !$lastName || !$dateOfBirth || 
    !$gender || !$email || !$phoneNumber || !$position || 
    !$paymentStructure || !$branchId) {
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
            pay_structure = ?, 
            monthly_salary = ?, 
            base_salary = ?, 
            branch_id = ?
            WHERE employeeID = ?";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters - note the change in type definition string
    $stmt->bind_param(
        "ssssssssssddii", 
        $firstName, 
        $lastName, 
        $middleName, 
        $suffix, 
        $dateOfBirth, 
        $gender, 
        $email, 
        $phoneNumber, 
        $position, 
        $paymentStructure, 
        $monthlySalary, 
        $baseSalary, 
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
?>