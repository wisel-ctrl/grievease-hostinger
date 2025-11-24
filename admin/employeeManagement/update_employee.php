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
    'employeePosition', 'editPaymentStructure', 'branch',
    'region', 'province', 'municipality', 'barangay',
    'street_address', 'zip_code'
];

// Add zip code validation
if (empty($zip_code)) {
    $response['message'] = "Zip code is required.";
    echo json_encode($response);
    exit();
} elseif (!preg_match("/^\d{4}$/", $zip_code)) {
    $response['message'] = "Zip code must be a 4-digit number.";
    echo json_encode($response);
    exit();
}

// Add street address validation
if (empty($street_address)) {
    $response['message'] = "Street address is required.";
    echo json_encode($response);
    exit();
}

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

// Address IDs (for fetching names)
$region_id = filter_input(INPUT_POST, 'region', FILTER_VALIDATE_INT);
$province_id = filter_input(INPUT_POST, 'province', FILTER_VALIDATE_INT);
$municipality_id = filter_input(INPUT_POST, 'municipality', FILTER_VALIDATE_INT);
$barangay_id = filter_input(INPUT_POST, 'barangay', FILTER_VALIDATE_INT);
$street_address = !empty($_POST['street_address']) ? sanitizeInput($_POST['street_address']) : null;
$zip_code = !empty($_POST['zip_code']) ? sanitizeInput($_POST['zip_code']) : null;

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

// Zip code validation (if provided)
if (!empty($zip_code) && !preg_match("/^\d{4}$/", $zip_code)) {
    $response['message'] = "Zip code must be a 4-digit number.";
    echo json_encode($response);
    exit();
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
    !$paymentStructure || !$branchId || !$region_id || 
    !$province_id || !$municipality_id || !$barangay_id ||
    !$street_address || !$zip_code) {
    $response['message'] = 'Invalid input data';
    echo json_encode($response);
    exit();
}

try {
    // Fetch address names from address database
    $region_name = '';
    $province_name = '';
    $municipality_name = '';
    $barangay_name = '';

    if ($region_id) {
        $region_sql = "SELECT region_name FROM table_region WHERE region_id = ?";
        $region_stmt = $addressDB->prepare($region_sql);
        $region_stmt->bind_param("i", $region_id);
        $region_stmt->execute();
        $region_result = $region_stmt->get_result();
        if ($region_row = $region_result->fetch_assoc()) {
            $region_name = $region_row['region_name'];
        }
        $region_stmt->close();
    }

    if ($province_id) {
        $province_sql = "SELECT province_name FROM table_province WHERE province_id = ?";
        $province_stmt = $addressDB->prepare($province_sql);
        $province_stmt->bind_param("i", $province_id);
        $province_stmt->execute();
        $province_result = $province_stmt->get_result();
        if ($province_row = $province_result->fetch_assoc()) {
            $province_name = $province_row['province_name'];
        }
        $province_stmt->close();
    }

    if ($municipality_id) {
        $municipality_sql = "SELECT municipality_name FROM table_municipality WHERE municipality_id = ?";
        $municipality_stmt = $addressDB->prepare($municipality_sql);
        $municipality_stmt->bind_param("i", $municipality_id);
        $municipality_stmt->execute();
        $municipality_result = $municipality_stmt->get_result();
        if ($municipality_row = $municipality_result->fetch_assoc()) {
            $municipality_name = $municipality_row['municipality_name'];
        }
        $municipality_stmt->close();
    }

    if ($barangay_id) {
        $barangay_sql = "SELECT barangay_name FROM table_barangay WHERE barangay_id = ?";
        $barangay_stmt = $addressDB->prepare($barangay_sql);
        $barangay_stmt->bind_param("i", $barangay_id);
        $barangay_stmt->execute();
        $barangay_result = $barangay_stmt->get_result();
        if ($barangay_row = $barangay_result->fetch_assoc()) {
            $barangay_name = $barangay_row['barangay_name'];
        }
        $barangay_stmt->close();
    }

    // Prepare SQL statement with address names
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
            branch_id = ?,
            region_name = ?,
            province_name = ?,
            municipality_name = ?,
            barangay_name = ?,
            street_address = ?,
            zip_code = ?
            WHERE employeeID = ?";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters - note the change in type definition string
    $stmt->bind_param(
        "ssssssssssddissssssi", 
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
        $region_name,
        $province_name,
        $municipality_name,
        $barangay_name,
        $street_address,
        $zip_code,
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