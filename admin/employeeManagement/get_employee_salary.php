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
    $formattedStartDate = $startDateTime->format('Y-m-d 00:00:00');
    $formattedEndDate = $endDateTime->format('Y-m-d 23:59:59');
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    exit;
}

try {
    // Query with UNION for commission + monthly salaries
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
            AND esp.payment_date >= e.date_created
            AND (
                e.pay_structure = 'commission'
                OR e.pay_structure = 'both'
                OR e.pay_structure = 'monthly'
            )

        UNION ALL

        SELECT
            DATE_FORMAT(dates.generated_date, '%Y-%m-%d') AS payment_date,
            'Monthly Salary' AS service_name,
            e.monthly_salary AS service_income
        FROM
            employee_tb e
        JOIN (
            SELECT LAST_DAY(DATE_ADD(?, INTERVAL seq MONTH)) AS generated_date
            FROM (
                SELECT ? AS start_date, ? AS end_date
            ) r
            JOIN seq_0_to_120 seqs 
                ON seqs.seq <= TIMESTAMPDIFF(MONTH, r.start_date, r.end_date)
        ) dates
            ON 1=1
        WHERE 
            e.EmployeeID = ?
            AND e.pay_structure IN ('monthly','both')
            AND dates.generated_date BETWEEN ? AND ?
            AND dates.generated_date >= e.date_created

        ORDER BY payment_date DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "issssssis", 
        $employeeId, 
        $formattedStartDate, 
        $formattedEndDate,
        $formattedStartDate, // for DATE_ADD start
        $formattedStartDate, // for range subquery start
        $formattedEndDate,   // for range subquery end
        $employeeId, 
        $formattedStartDate, 
        $formattedEndDate
    );

    $stmt->execute();
    $result = $stmt->get_result();

    $services = [];
    $totalServices = 0;
    $totalEarnings = 0;

    while ($row = $result->fetch_assoc()) {
        $services[] = [
            'payment_date'   => $row['payment_date'],
            'service_name'   => $row['service_name'],
            'service_income' => (float)$row['service_income'],
            'employee_share' => (float)$row['service_income']
        ];
        $totalServices++;
        $totalEarnings += (float)$row['service_income'];
    }

    echo json_encode([
        'success'        => true,
        'total_services' => $totalServices,
        'total_earnings' => $totalEarnings,
        'services'       => $services
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching data: ' . $e->getMessage()
    ]);
}
?>
