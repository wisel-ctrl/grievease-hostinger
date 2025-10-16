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

// Function to calculate monthly salary based on included half-months
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
        $emp_start = $range_start;
    }
    
    $calculated_salary = 0;
    
    // Create period for each month in the range
    $period_start = clone $range_start;
    $period_start->modify('first day of this month');
    $period_end = clone $range_end;
    $period_end->modify('last day of this month');
    
    $interval = DateInterval::createFromDateString('1 month');
    $period = new DatePeriod($period_start, $interval, $period_end);
    
    foreach ($period as $dt) {
        $current_month = $dt->format('Y-m');
        $month_start = DateTime::createFromFormat('Y-m-d', $dt->format('Y-m-01'));
        $month_end = DateTime::createFromFormat('Y-m-d', $dt->format('Y-m-t'));
        
        error_log("Processing month: $current_month");
        
        // Adjust for actual range boundaries
        $effective_month_start = ($month_start < $range_start) ? $range_start : $month_start;
        $effective_month_end = ($month_end > $range_end) ? $range_end : $month_end;
        
        // Skip if employee wasn't employed yet this month
        if ($emp_start > $month_end) {
            error_log("Employee started after this month, skipping");
            continue;
        }
        
        // Calculate days in each half for this specific month
        $first_half_start = DateTime::createFromFormat('Y-m-d', $dt->format('Y-m-01'));
        $first_half_end = DateTime::createFromFormat('Y-m-d', $dt->format('Y-m-15'));
        $second_half_start = DateTime::createFromFormat('Y-m-d', $dt->format('Y-m-16'));
        $second_half_end = DateTime::createFromFormat('Y-m-d', $dt->format('Y-m-t'));
        
        $first_half_included = false;
        $second_half_included = false;
        
        // Check if effective range includes any days from first half
        if ($effective_month_start <= $first_half_end && $effective_month_end >= $first_half_start) {
            $first_half_included = true;
            error_log("First half included for month $current_month");
        }
        
        // Check if effective range includes any days from second half
        if ($effective_month_start <= $second_half_end && $effective_month_end >= $second_half_start) {
            $second_half_included = true;
            error_log("Second half included for month $current_month");
        }
        
        // Add salary for included halves (considering employee start date)
        $month_salary = 0;
        
        if ($first_half_included && $emp_start <= $first_half_end) {
            $month_salary += $monthly_salary / 2;
            error_log("Added first half salary: " . ($monthly_salary / 2));
        }
        
        if ($second_half_included && $emp_start <= $second_half_end) {
            $month_salary += $monthly_salary / 2;
            error_log("Added second half salary: " . ($monthly_salary / 2));
        }
        
        $calculated_salary += $month_salary;
        error_log("Month $current_month total: $month_salary");
    }
    
    error_log("Final calculated salary: $calculated_salary (Base: $monthly_salary)");
    
    return $calculated_salary;
}

// Function to get employee payroll data with proper salary calculation
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
            // Calculate monthly salary based on included half-months
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
        }
    } else {
        error_log("No employees found for branch $branch_id");
    }
    
    return $employees;
}

// Function to get payroll summary
function getPayrollSummary($conn, $branch_id, $start_date = null, $end_date = null) {
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
    $employees = getEmployeePayrollData($conn, $branch_id, $start_date, $end_date);
    $summary = getPayrollSummary($conn, $branch_id, $start_date, $end_date);
    
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