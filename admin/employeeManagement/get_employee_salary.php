<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

// Get parameters
$employeeId = isset($_GET['employeeId']) ? intval($_GET['employeeId']) : 0;
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';

if (!$employeeId || !$startDate || !$endDate) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    // Get base salary first
    $baseSalaryQuery = "SELECT base_salary FROM employee_tb WHERE EmployeeID = ?";
    $stmt = $conn->prepare($baseSalaryQuery);
    $stmt->bind_param("i", $employeeId);
    $stmt->execute();
    $baseSalaryResult = $stmt->get_result();
    $baseSalaryRow = $baseSalaryResult->fetch_assoc();
    $baseSalary = $baseSalaryRow ? floatval($baseSalaryRow['base_salary']) : 0;
    
    // Query to get service payments and service details
    $query = "
        SELECT 
            esp.payment_date,
            s.service_name,
            esp.income AS service_income,
            esp.income * (e.base_salary / 100) AS employee_share
        FROM 
            employee_service_payments esp
        JOIN 
            sales_tb st ON esp.sales_id = st.sales_id
        JOIN 
            services_tb s ON st.service_id = s.service_id
        JOIN 
            employee_tb e ON esp.employeeID = e.EmployeeID
        WHERE 
            esp.employeeID = ?
            AND esp.payment_date BETWEEN ? AND ?
        ORDER BY 
            esp.payment_date DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $employeeId, $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $services = [];
    $totalServices = 0;
    $totalEarnings = 0;
    
    while ($row = $result->fetch_assoc()) {
        $services[] = [
            'payment_date' => $row['payment_date'],
            'service_name' => $row['service_name'],
            'service_income' => $row['service_income'],
            'employee_share' => $row['employee_share']
        ];
        $totalServices++;
        $totalEarnings += $row['employee_share'];
    }
    
    echo json_encode([
        'success' => true,
        'base_salary' => $baseSalary,
        'total_services' => $totalServices,
        'total_earnings' => $totalEarnings,
        'services' => $services
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching salary data: ' . $e->getMessage()
    ]);
}
?>