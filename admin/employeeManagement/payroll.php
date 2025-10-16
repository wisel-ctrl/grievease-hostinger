<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once('../../db_connect.php');

// Get branch_id from request
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

// Get date range from request
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : null;

// Debug output
error_log("Payroll API Called - Branch: $branch_id, Start: $start_date, End: $end_date");

// Validate branch_id
if ($branch_id !== 1 && $branch_id !== 2) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid branch ID. Please provide 1 for Paete or 2 for Pila.'
    ]);
    exit;
}

// Function to calculate proration factor
function calculateProrationFactor($start_date, $end_date) {
    if (!$start_date || !$end_date) {
        return 1.0; // Full month if no date range
    }
    
    // Convert to DateTime objects
    $start = DateTime::createFromFormat('Y-m-d', $start_date);
    $end = DateTime::createFromFormat('Y-m-d', $end_date);
    
    if (!$start || !$end) {
        return 1.0;
    }
    
    // Calculate days in range (inclusive)
    $interval = $start->diff($end);
    $days_in_range = $interval->days + 1;
    
    // Get days in the month of start date
    $days_in_month = $start->format('t');
    
    $proration_factor = $days_in_range / $days_in_month;
    
    error_log("Proration - Days in range: $days_in_range, Days in month: $days_in_month, Factor: $proration_factor");
    
    return $proration_factor;
}

// Function to get employee payroll data with prorated salaries
function getEmployeePayrollData($conn, $branch_id, $start_date = null, $end_date = null) {
    // Calculate proration factor
    $proration_factor = calculateProrationFactor($start_date, $end_date);
    
    // Default to current month if no date range provided
    $query_start_date = $start_date;
    $query_end_date = $end_date;
    
    if (!$start_date || !$end_date) {
        $query_start_date = date('Y-m-01');
        $query_end_date = date('Y-m-t');
        $proration_factor = 1.0; // Full month
    }
    
    // Ensure dates have proper time components for commission query
    $commission_start_date = $query_start_date . ' 00:00:00';
    $commission_end_date = $query_end_date . ' 23:59:59';
    
    error_log("Query Dates - Start: $commission_start_date, End: $commission_end_date, Proration: $proration_factor");
    
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
                WHEN e.pay_structure IN ('monthly', 'both') THEN 
                    COALESCE(e.monthly_salary, 0) * $proration_factor
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
                        WHEN e.pay_structure IN ('monthly', 'both') THEN 
                            e.monthly_salary * $proration_factor
                        ELSE 0
                    END, 0
                ) + COALESCE(SUM(esp.income), 0)
            ) AS total_salary
        FROM employee_tb e
        LEFT JOIN employee_service_payments esp 
            ON e.employeeID = esp.employeeID
           AND esp.payment_date BETWEEN '$commission_start_date' AND '$commission_end_date'
        WHERE e.status = 'active'
        AND e.branch_id = $branch_id
        GROUP BY 
            e.employeeID,
            e.fname, e.mname, e.lname, e.suffix,
            e.pay_structure,
            e.monthly_salary,
            e.base_salary
    ";
    
    error_log("SQL Query: " . $query);
    
    $result = $conn->query($query);
    $employees = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $employees[] = $row;
            error_log("Employee: " . $row['full_name'] . 
                     " - Monthly: " . $row['monthly_salary'] . 
                     " - Commission: " . $row['commission_salary'] .
                     " - Total: " . $row['total_salary']);
        }
    } else {
        error_log("No employees found for branch $branch_id");
    }
    
    return $employees;
}

// Function to get payroll summary with prorated salaries
function getPayrollSummary($conn, $branch_id, $start_date = null, $end_date = null) {
    // Calculate proration factor
    $proration_factor = calculateProrationFactor($start_date, $end_date);
    
    // Default to current month if no date range provided
    $query_start_date = $start_date;
    $query_end_date = $end_date;
    
    if (!$start_date || !$end_date) {
        $query_start_date = date('Y-m-01');
        $query_end_date = date('Y-m-t');
        $proration_factor = 1.0; // Full month
    }
    
    $commission_start_date = $query_start_date . ' 00:00:00';
    $commission_end_date = $query_end_date . ' 23:59:59';
    
    $query = "
        SELECT 
            COUNT(e.employeeID) AS total_employees,
            SUM(
                CASE 
                    WHEN e.pay_structure IN ('monthly', 'both') 
                        THEN COALESCE(e.monthly_salary, 0) * $proration_factor
                    ELSE 0
                END
            ) AS total_monthly_salary,
            SUM(COALESCE(esp_month.commission_salary, 0)) AS total_commission_salary,
            SUM(
                COALESCE(
                    CASE 
                        WHEN e.pay_structure IN ('monthly', 'both') 
                            THEN e.monthly_salary * $proration_factor
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
            WHERE payment_date BETWEEN '$commission_start_date' AND '$commission_end_date'
            GROUP BY employeeID
        ) esp_month ON e.employeeID = esp_month.employeeID
        WHERE e.status = 'active'
        AND e.branch_id = $branch_id
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
    // Get data from database with branch filter and date range
    $employees = getEmployeePayrollData($conn, $branch_id, $start_date, $end_date);
    $summary = getPayrollSummary($conn, $branch_id, $start_date, $end_date);
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'branch_id' => $branch_id,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'proration_factor' => calculateProrationFactor($start_date, $end_date),
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