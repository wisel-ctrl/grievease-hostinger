<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once('../../db_connect.php');

// Check connection
if ($conn->connect_error) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $conn->connect_error
    ]);
    exit;
}

// Function to get employee payroll data
function getEmployeePayrollData($conn) {
    $query = "
        SELECT 
            e.employeeID,
            CONCAT_WS(' ', 
                COALESCE(e.fname, ''), 
                COALESCE(e.mname, ''), 
                COALESCE(e.lname, ''), 
                COALESCE(e.suffix, '')
            ) AS full_name,
            e.pay_structure,
            CASE 
                WHEN e.pay_structure IN ('monthly', 'both') THEN COALESCE(e.monthly_salary, 0)
                ELSE 0
            END AS monthly_salary,
            CASE 
                WHEN e.pay_structure IN ('commission', 'both') THEN COALESCE(e.base_salary, 0)
                ELSE 0
            END AS base_salary,
            COALESCE(SUM(esp.income), 0) AS commission_salary,
            (
                COALESCE(
                    CASE 
                        WHEN e.pay_structure IN ('monthly', 'both') THEN e.monthly_salary
                        ELSE 0
                    END, 0
                ) + COALESCE(SUM(esp.income), 0)
            ) AS total_salary
        FROM employee_tb e
        LEFT JOIN employee_service_payments esp 
            ON e.employeeID = esp.employeeID
           AND YEAR(esp.payment_date) = YEAR(CURDATE())
           AND MONTH(esp.payment_date) = MONTH(CURDATE())
        WHERE e.status = 'active'
        GROUP BY 
            e.employeeID,
            e.fname, e.mname, e.lname, e.suffix,
            e.pay_structure,
            e.monthly_salary,
            e.base_salary
    ";
    
    $result = $conn->query($query);
    $employees = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }
    
    return $employees;
}

// Function to get payroll summary
function getPayrollSummary($conn) {
    $query = "
        SELECT 
            COUNT(e.employeeID) AS total_employees,
            SUM(
                CASE 
                    WHEN e.pay_structure IN ('monthly', 'both') 
                        THEN COALESCE(e.monthly_salary, 0) 
                    ELSE 0
                END
            ) AS total_monthly_salary,
            SUM(COALESCE(esp_month.commission_salary, 0)) AS total_commission_salary,
            SUM(
                COALESCE(
                    CASE 
                        WHEN e.pay_structure IN ('monthly', 'both') 
                            THEN e.monthly_salary 
                        ELSE 0
                    END, 0
                ) 
                + COALESCE(esp_month.commission_salary, 0)
            ) AS total_salary
        FROM employee_tb e
        LEFT JOIN (
            SELECT 
                employeeID, 
                SUM(income) AS commission_salary
            FROM employee_service_payments
            WHERE YEAR(payment_date) = YEAR(CURDATE())
              AND MONTH(payment_date) = MONTH(CURDATE())
            GROUP BY employeeID
        ) esp_month ON e.employeeID = esp_month.employeeID
        WHERE e.status = 'active'
    ";
    
    $result = $conn->query($query);
    
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return [
        'total_employees' => 0,
        'total_monthly_salary' => 0,
        'total_commission_salary' => 0,
        'total_salary' => 0
    ];
}

try {
    // Get data from database
    $employees = getEmployeePayrollData($conn);
    $summary = getPayrollSummary($conn);
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'employees' => $employees,
        'summary' => $summary
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    $conn->close();
}
?>