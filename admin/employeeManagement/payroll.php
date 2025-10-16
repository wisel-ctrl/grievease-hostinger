<?php
ini_set('display_errors', 1);  // show errors on screen (optional)
ini_set('log_errors', 1);      // enable logging
ini_set('error_log', __DIR__ . '/error_log.txt'); // set custom log location
error_reporting(E_ALL);  

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

// Function to calculate half-month salary based on date range and employee start date
function calculateMonthlySalary($monthly_salary, $start_date, $end_date, $employee_start_date) {
    if (!$start_date || !$end_date) {
        return $monthly_salary; // Full month if no date range
    }
    
    error_log("Calculating salary: Monthly: $monthly_salary, Range: $start_date to $end_date, Emp Start: $employee_start_date");
    
    // Convert to DateTime objects
    $range_start = DateTime::createFromFormat('Y-m-d', $start_date);
    $range_end = DateTime::createFromFormat('Y-m-d', $end_date);
    $emp_start = DateTime::createFromFormat('Y-m-d', $employee_start_date);
    
    if (!$range_start || !$range_end) {
        error_log("Invalid date range provided");
        return $monthly_salary;
    }
    
    if (!$emp_start) {
        error_log("Invalid employee start date: $employee_start_date");
        $emp_start = $range_start; // Use range start if invalid employee date
    }
    
    // Get the month and year from the date range
    $range_month = $range_start->format('m');
    $range_year = $range_start->format('Y');
    
    // Create dates for 1st-15th and 16th-end of month
    $first_half_start = DateTime::createFromFormat('Y-m-d', "$range_year-$range_month-01");
    $first_half_end = DateTime::createFromFormat('Y-m-d', "$range_year-$range_month-15");
    
    // Get last day of month for second half
    $last_day = $range_start->format('t');
    $second_half_start = DateTime::createFromFormat('Y-m-d', "$range_year-$range_month-16");
    $second_half_end = DateTime::createFromFormat('Y-m-d', "$range_year-$range_month-$last_day");
    
    $calculated_salary = 0;
    
    error_log("First half: " . $first_half_start->format('Y-m-d') . " to " . $first_half_end->format('Y-m-d'));
    error_log("Second half: " . $second_half_start->format('Y-m-d') . " to " . $second_half_end->format('Y-m-d'));
    error_log("Date range: " . $range_start->format('Y-m-d') . " to " . $range_end->format('Y-m-d'));
    error_log("Employee start: " . $emp_start->format('Y-m-d'));
    
    // Check first half (1st-15th)
    $first_half_included = false;
    $second_half_included = false;
    
    // Check if date range overlaps with first half
    if ($range_start <= $first_half_end && $range_end >= $first_half_start) {
        $first_half_included = true;
        error_log("Date range INCLUDES first half");
    }
    
    // Check if date range overlaps with second half
    if ($range_start <= $second_half_end && $range_end >= $second_half_start) {
        $second_half_included = true;
        error_log("Date range INCLUDES second half");
    }
    
    // Check if employee was employed during the included halves
    if ($first_half_included && $emp_start <= $first_half_end) {
        $calculated_salary += $monthly_salary / 2;
        error_log("First half salary ADDED: " . ($monthly_salary / 2));
    }
    
    if ($second_half_included && $emp_start <= $second_half_end) {
        $calculated_salary += $monthly_salary / 2;
        error_log("Second half salary ADDED: " . ($monthly_salary / 2));
    }
    
    error_log("Final calculated salary: $calculated_salary (Base: $monthly_salary)");
    
    return $calculated_salary;
}

// Function to get employee payroll data with half-month salary calculation
function getEmployeePayrollData($conn, $branch_id, $start_date = null, $end_date = null) {
    // Default to current month if no date range provided
    $query_start_date = $start_date;
    $query_end_date = $end_date;
    
    if (!$start_date || !$end_date) {
        $query_start_date = date('Y-m-01');
        $query_end_date = date('Y-m-t');
        error_log("No date range provided, using current month: $query_start_date to $query_end_date");
    } else {
        error_log("Using provided date range: $query_start_date to $query_end_date");
    }
    
    // Ensure dates have proper time components for commission query
    $commission_start_date = $query_start_date . ' 00:00:00';
    $commission_end_date = $query_end_date . ' 23:59:59';
    
    error_log("Commission query dates - Start: $commission_start_date, End: $commission_end_date");
    
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
            e.monthly_salary as base_monthly_salary,
            e.date_created,
            CASE 
                WHEN e.pay_structure IN ('commission', 'both') THEN COALESCE(e.base_salary, 0)
                ELSE 0
            END AS base_salary,
            COALESCE(SUM(esp.income), 0) AS commission_salary
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
            e.base_salary,
            e.date_created
    ";
    
    error_log("SQL Query: " . $query);
    
    $result = $conn->query($query);
    $employees = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Calculate monthly salary based on half-month system and employee start date
            $monthly_salary = 0;
            if ($row['pay_structure'] == 'monthly' || $row['pay_structure'] == 'both') {
                $monthly_salary = calculateMonthlySalary(
                    floatval($row['base_monthly_salary']),
                    $start_date,
                    $end_date,
                    $row['date_created']
                );
            }
            
            $total_salary = $monthly_salary + floatval($row['commission_salary']);
            
            $employee_data = [
                'employeeID' => $row['employeeID'],
                'full_name' => $row['full_name'],
                'pay_structure' => $row['pay_structure'],
                'monthly_salary' => $monthly_salary,
                'base_salary' => $row['base_salary'],
                'commission_salary' => $row['commission_salary'],
                'total_salary' => $total_salary,
                'date_created' => $row['date_created'],
                'base_monthly_salary' => $row['base_monthly_salary']
            ];
            
            $employees[] = $employee_data;
            
            error_log("=== EMPLOYEE FINAL ===");
            error_log("Employee: " . $row['full_name']);
            error_log("Base Monthly: " . $row['base_monthly_salary']);
            error_log("Calculated Monthly: " . $monthly_salary);
            error_log("Commission: " . $row['commission_salary']);
            error_log("Total: " . $total_salary);
            error_log("Start Date: " . $row['date_created']);
            error_log("=====================");
        }
    } else {
        error_log("No employees found for branch $branch_id");
    }
    
    return $employees;
}

// Function to get payroll summary with half-month salary calculation
function getPayrollSummary($conn, $branch_id, $start_date = null, $end_date = null) {
    // Get all employees first to calculate totals
    $employees = getEmployeePayrollData($conn, $branch_id, $start_date, $end_date);
    
    $total_employees = count($employees);
    $total_monthly_salary = 0;
    $total_commission_salary = 0;
    $total_salary = 0;
    
    foreach ($employees as $employee) {
        $total_monthly_salary += $employee['monthly_salary'];
        $total_commission_salary += $employee['commission_salary'];
        $total_salary += $employee['total_salary'];
    }
    
    return [
        'total_employees' => $total_employees,
        'total_monthly_salary' => $total_monthly_salary,
        'total_commission_salary' => $total_commission_salary,
        'total_salary' => $total_salary
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