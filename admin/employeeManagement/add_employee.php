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
    $email = sanitizeInput($_POST['employeeEmail']);
    $branch_id = sanitizeInput($_POST['branch']);
    $base_salary = sanitizeInput($_POST['employeeSalary']);

    // Validate inputs (add more robust validation as needed)
    $errors = [];

    if (empty($fname) || empty($lname)) {
        $errors[] = "First name and last name are required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (!preg_match("/^(\+63|0)\d{10}$/", $phone_number)) {
        $errors[] = "Invalid phone number format.";
    }

    // If no errors, proceed with database insertion
    if (empty($errors)) {
        try {
            // Use the connection from the included file
            // The $conn variable should be available from db_connect.php

            // Prepare SQL statement
            $sql = "INSERT INTO employee_tb (
                fname, mname, lname, suffix, gender, bday, 
                position, phone_number, email, branch_id, base_salary
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            )";

            // Prepare and bind parameters
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "sssssssssid", 
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
                $base_salary
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