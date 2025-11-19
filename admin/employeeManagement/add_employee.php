<?php
// Include database connection
require_once '../../db_connect.php';

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

    // Salary validation - monthly salary must be <= 10000
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

            // Prepare SQL statement
            $sql = "INSERT INTO employee_tb (
                fname, mname, lname, suffix, gender, bday, 
                position, phone_number, email, branch_id, base_salary, pay_structure, monthly_salary
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";

            // Prepare and bind parameters
            $stmt = $conn->prepare($sql);
            
            // Convert salary values to appropriate types
            $base_salary_value = !empty($base_salary) ? floatval($base_salary) : NULL;
            $monthly_salary_value = !empty($monthly_salary) ? floatval($monthly_salary) : NULL;
            
            $stmt->bind_param(
                "sssssssssidsd", 
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
                $monthly_salary_value
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