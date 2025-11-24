<?php
// Include database connection
require_once '../../db_connect.php';
// Include address database connection for fetching names
require_once '../../addressDB.php';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    // Collect and sanitize form data
    $fname = sanitizeInput($_POST['firstName']);
    $mname = !empty($_POST['middleName']) ? sanitizeInput($_POST['middleName']) : NULL;
    $lname = sanitizeInput($_POST['lastName']);
    $suffix = !empty($_POST['suffix']) ? sanitizeInput($_POST['suffix']) : NULL;
    $gender = sanitizeInput($_POST['gender']);
    $bday = sanitizeInput($_POST['dateOfBirth']);
    $position = sanitizeInput($_POST['employeePosition']);
    $phone_number = sanitizeInput($_POST['employeePhone']);
    $pay_structure = sanitizeInput($_POST['paymentStructure']);
    $email = sanitizeInput($_POST['employeeEmail']);
    $branch_id = sanitizeInput($_POST['branch']);
    $base_salary = !empty($_POST['commissionSalary']) ? sanitizeInput($_POST['commissionSalary']) : NULL;
    $monthly_salary = !empty($_POST['monthlySalary']) ? sanitizeInput($_POST['monthlySalary']) : NULL;
    
    // Address IDs (for fetching names)
    $region_id = !empty($_POST['region']) ? intval($_POST['region']) : NULL;
    $province_id = !empty($_POST['province']) ? intval($_POST['province']) : NULL;
    $municipality_id = !empty($_POST['municipality']) ? intval($_POST['municipality']) : NULL;
    $barangay_id = !empty($_POST['barangay']) ? intval($_POST['barangay']) : NULL;
    $street_address = !empty($_POST['street_address']) ? sanitizeInput($_POST['street_address']) : NULL;
    $zip_code = !empty($_POST['zip_code']) ? sanitizeInput($_POST['zip_code']) : NULL;

    // Validate inputs
    $errors = [];

    // Required fields validation
    if (empty($fname) || empty($lname)) {
        $errors[] = "First name and last name are required.";
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Phone number validation
    if (!preg_match("/^(\+63|0)\d{10}$/", $phone_number)) {
        $errors[] = "Invalid phone number format.";
    }

    // Age validation - must be at least 18 years old
    if (!empty($bday)) {
        $birthDate = new DateTime($bday);
        $today = new DateTime();
        $age = $today->diff($birthDate)->y;
        
        if ($age < 18) {
            $errors[] = "Employee must be at least 18 years old.";
        }
    }

    // Salary validation - monthly salary must be <= 100000
    if (!empty($monthly_salary)) {
        $monthly_salary_float = floatval($monthly_salary);
        if ($monthly_salary_float > 100000) {
            $errors[] = "Monthly salary must be less than or equal to ₱100,000.";
        }
        
        // Additional validation for negative salary
        if ($monthly_salary_float < 0) {
            $errors[] = "Monthly salary cannot be negative.";
        }
    }

    // Commission salary validation
    if (!empty($base_salary)) {
        $base_salary_float = floatval($base_salary);
        if ($base_salary_float < 0) {
            $errors[] = "Commission salary cannot be negative.";
        }
    }

    // Payment structure validation
    if ($pay_structure === 'monthly' || $pay_structure === 'both') {
        if (empty($monthly_salary)) {
            $errors[] = "Monthly salary is required for the selected payment structure.";
        }
    }
    
    if ($pay_structure === 'commission' || $pay_structure === 'both') {
        if (empty($base_salary)) {
            $errors[] = "Commission salary is required for the selected payment structure.";
        }
    }

    // Address validation
    if (empty($region_id) || empty($province_id) || empty($municipality_id) || empty($barangay_id)) {
        $errors[] = "Complete address information is required (Region, Province, Municipality, and Barangay).";
    }

    // Street address validation
    if (empty($street_address)) {
        $errors[] = "Street address is required.";
    }
    
    // Zip code validation
    if (empty($zip_code)) {
        $errors[] = "Zip code is required.";
    } elseif (!preg_match("/^\d{4}$/", $zip_code)) {
        $errors[] = "Zip code must be a 4-digit number.";
    }

    // Validate that at least one salary is provided
    if (empty($monthly_salary) && empty($base_salary)) {
        $errors[] = "Either monthly salary or commission salary must be provided.";
    }

    // Validate position is not empty
    if (empty($position)) {
        $errors[] = "Employee position is required.";
    }

    // Validate gender is not empty
    if (empty($gender)) {
        $errors[] = "Gender is required.";
    }

    // Validate branch is selected
    if (empty($branch_id)) {
        $errors[] = "Branch location is required.";
    }

    // Validate date of birth is provided
    if (empty($bday)) {
        $errors[] = "Date of birth is required.";
    }

    // If no errors, proceed with database insertion
    if (empty($errors)) {
            try {
                // Check if email already exists
                $email_check_sql = "SELECT EmployeeID FROM employee_tb WHERE email = ?";
                $email_stmt = $conn->prepare($email_check_sql);
                $email_stmt->bind_param("s", $email);
                $email_stmt->execute();
                $email_stmt->store_result();
                
                if ($email_stmt->num_rows > 0) {
                    $errors[] = "Email address already exists.";
                    $email_stmt->close();
                    
                    $response = [
                        'status' => 'error',
                        'errors' => $errors
                    ];
                    echo json_encode($response);
                    exit;
                }
                $email_stmt->close();
    
                // Check if phone number already exists
                $phone_check_sql = "SELECT EmployeeID FROM employee_tb WHERE phone_number = ?";
                $phone_stmt = $conn->prepare($phone_check_sql);
                $phone_stmt->bind_param("s", $phone_number);
                $phone_stmt->execute();
                $phone_stmt->store_result();
                
                if ($phone_stmt->num_rows > 0) {
                    $errors[] = "Phone number already exists.";
                    $phone_stmt->close();
                    
                    $response = [
                        'status' => 'error',
                        'errors' => $errors
                    ];
                    echo json_encode($response);
                    exit;
                }
                $phone_stmt->close();
    
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
                $sql = "INSERT INTO employee_tb (
                    fname, mname, lname, suffix, gender, bday, 
                    position, phone_number, email, branch_id, base_salary, pay_structure, monthly_salary,
                    region_name, province_name, municipality_name, barangay_name, street_address, zip_code
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
                )";
    
                // Prepare and bind parameters
                $stmt = $conn->prepare($sql);
                
                // Convert salary values to appropriate types
                $base_salary_value = !empty($base_salary) ? floatval($base_salary) : NULL;
                $monthly_salary_value = !empty($monthly_salary) ? floatval($monthly_salary) : NULL;
                
                $stmt->bind_param(
                    "sssssssssidsdssssss", 
                    $fname, 
                    $mname, 
                    $lname, 
                    $suffix, 
                    $gender, 
                    $bday, 
                    $position, 
                    $phone_number, 
                    $email, 
                    $branch_id, 
                    $base_salary_value,
                    $pay_structure,
                    $monthly_salary_value,
                    $region_name,
                    $province_name,
                    $municipality_name,
                    $barangay_name,
                    $street_address,
                    $zip_code
                );
    
                // Execute the statement
                if ($stmt->execute()) {
                    // Successful insertion
                    $response = [
                        'status' => 'success',
                        'message' => 'Employee account created successfully!',
                        'employeeId' => $stmt->insert_id
                    ];
                    echo json_encode($response);
                } else {
                    // Insertion failed
                    $response = [
                        'status' => 'error',
                        'message' => 'Failed to create employee account: ' . $stmt->error
                    ];
                    echo json_encode($response);
                }
    
                // Close statement
                $stmt->close();
    
            } catch (Exception $e) {
                // Handle any unexpected errors
                $response = [
                    'status' => 'error',
                    'message' => 'An unexpected error occurred: ' . $e->getMessage()
                ];
                echo json_encode($response);
            }
    } else {
        // Validation errors
        $response = [
            'status' => 'error',
            'errors' => $errors
        ];
        echo json_encode($response);
    }
} else {
    // Invalid request method
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method Not Allowed'
    ]);
}
?>