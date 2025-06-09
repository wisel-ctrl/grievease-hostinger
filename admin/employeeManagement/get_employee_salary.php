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

// Validate and format dates
try {
    $startDateTime = new DateTime($startDate);
    $endDateTime = new DateTime($endDate);
    $formattedStartDate = $startDateTime->format('Y-m-d') . ' 00:00:00';
    $formattedEndDate = $endDateTime->format('Y-m-d') . ' 23:59:59';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

try {
    // Query to get service payments and service details
    $query = "
                SELECT 
            esp.payment_date,
            IFNULL(s.service_name, 'Customize Package') AS service_name,
            esp.income AS service_income
        FROM 
            employee_service_payments esp
        LEFT JOIN 
            sales_tb st ON esp.sales_id = st.sales_id
        LEFT JOIN 
            services_tb s ON st.service_id = s.service_id
        JOIN 
            employee_tb e ON esp.employeeID = e.EmployeeID
        WHERE 
            esp.employeeID = ?
            AND esp.payment_date BETWEEN ? AND ?
        ORDER BY 
            esp.payment_date DESC;
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $employeeId, $formattedStartDate, $formattedEndDate);
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
            'employee_share' => $row['service_income'] // Same as income
        ];
        $totalServices++;
        $totalEarnings += $row['service_income']; // Summing the income directly
    }
    
    echo json_encode([
        'success' => true,
        'total_services' => $totalServices,
        'total_earnings' => $totalEarnings,
        'services' => $services
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching data: ' . $e->getMessage()
    ]);
}
?>