<?php
include '../../db_connect.php';

header('Content-Type: application/json');

$branchId = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : null;

if (!$branchId) {
    echo json_encode(['error' => 'Invalid branch ID']);
    exit;
}

try {
    // Get current month and year
    $currentMonth = date('m');
    $currentYear = date('Y');
    
    // Get branch name
    $branchStmt = $conn->prepare("SELECT branch_name FROM branch_tb WHERE branch_id = ?");
    $branchStmt->bind_param("i", $branchId);
    $branchStmt->execute();
    $branchResult = $branchStmt->get_result();
    $branchName = $branchResult->fetch_assoc()['branch_name'];
    
    // Get services this month count
    $servicesQuery = "SELECT COUNT(*) as services_count FROM sales_tb 
                     WHERE branch_id = ? AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
    $stmt = $conn->prepare($servicesQuery);
    $stmt->bind_param("iii", $branchId, $currentMonth, $currentYear);
    $stmt->execute();
    $servicesResult = $stmt->get_result();
    $servicesCount = $servicesResult->fetch_assoc()['services_count'] ?? 0;
    
    // Get revenue this month
    $revenueQuery = "SELECT SUM(amount_paid) as total_revenue FROM sales_tb 
                    WHERE branch_id = ? AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
    $stmt = $conn->prepare($revenueQuery);
    $stmt->bind_param("iii", $branchId, $currentMonth, $currentYear);
    $stmt->execute();
    $revenueResult = $stmt->get_result();
    $totalRevenue = $revenueResult->fetch_assoc()['total_revenue'] ?? 0;
    $formattedRevenue = number_format($totalRevenue, 2);
    
    // Get pending services count this month
    $pendingQuery = "SELECT COUNT(*) as pending_count FROM sales_tb 
                    WHERE branch_id = ? AND status = 'Pending' AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
    $stmt = $conn->prepare($pendingQuery);
    $stmt->bind_param("iii", $branchId, $currentMonth, $currentYear);
    $stmt->execute();
    $pendingResult = $stmt->get_result();
    $pendingCount = $pendingResult->fetch_assoc()['pending_count'] ?? 0;
    
    // Get completed services count this month
    $completedQuery = "SELECT COUNT(*) as completed_count FROM sales_tb 
                      WHERE branch_id = ? AND status = 'Completed' AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
    $stmt = $conn->prepare($completedQuery);
    $stmt->bind_param("iii", $branchId, $currentMonth, $currentYear);
    $stmt->execute();
    $completedResult = $stmt->get_result();
    $completedCount = $completedResult->fetch_assoc()['completed_count'] ?? 0;
    
    // Get monthly revenue data for the last 12 months
    $monthlyRevenue = [];
    $currentDate = new DateTime();
    
    for ($i = 11; $i >= 0; $i--) {
        $date = clone $currentDate;
        $date->modify("-$i months");
        $month = $date->format('m');
        $year = $date->format('Y');
        
        $query = "SELECT SUM(amount_paid) as revenue FROM sales_tb 
                  WHERE branch_id = ? AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $branchId, $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        $monthlyRevenue[] = $data['revenue'] ?? 0;
    }
    
    // Get monthly projected income data
    $monthlyProjectedIncome = [];
    
    for ($i = 11; $i >= 0; $i--) {
        $date = clone $currentDate;
        $date->modify("-$i months");
        $month = $date->format('m');
        $year = $date->format('Y');
        
        $query = "SELECT SUM(discounted_price) as projected_income FROM sales_tb 
                  WHERE branch_id = ? AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $branchId, $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        $monthlyProjectedIncome[] = $data['projected_income'] ?? 0;
    }
    
    // Get service data for the branch
    $serviceData = [];
    $servicesQuery = "SELECT 
                        s.service_name,
                        COUNT(*) AS total_sales
                      FROM sales_tb sa
                      JOIN services_tb s ON sa.service_id = s.service_id
                      WHERE sa.branch_id = ?
                      GROUP BY s.service_name";
    
    $stmt = $conn->prepare($servicesQuery);
    $stmt->bind_param("i", $branchId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $services = [];
    $serviceSales = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = $row['service_name'];
        $serviceSales[] = $row['total_sales'];
    }
    
    // Get branch performance data
    $visible = "visible";
    $branchQuery = "SELECT 
                        b.branch_id,
                        b.branch_name,
                        COALESCE(s.service_count, 0) AS service_count,
                        COALESCE(s.revenue, 0) AS revenue,
                        COALESCE(e.expenses, 0) AS expenses,
                        COALESCE(s.capital_total, 0) AS capital_total,
                        (COALESCE(s.revenue, 0) - (COALESCE(s.capital_total, 0) + COALESCE(e.expenses, 0)) AS profit,
                        CASE 
                            WHEN COALESCE(s.revenue, 0) > 0 THEN 
                                (COALESCE(s.revenue, 0) - (COALESCE(s.capital_total, 0) + COALESCE(e.expenses, 0)) / COALESCE(s.revenue, 0) * 100
                            ELSE 0 
                        END AS margin
                    FROM 
                        branch_tb b
                    LEFT JOIN (
                        SELECT 
                            s.branch_id,
                            COUNT(DISTINCT s.sales_id) AS service_count,
                            SUM(s.amount_paid) AS revenue,
                            SUM(sv.capital_price) AS capital_total
                        FROM 
                            sales_tb s
                        LEFT JOIN 
                            services_tb sv ON s.service_id = sv.service_id
                        WHERE 
                            MONTH(s.get_timestamp) = ? AND YEAR(s.get_timestamp) = ?
                        GROUP BY 
                            s.branch_id
                    ) s ON b.branch_id = s.branch_id
                    LEFT JOIN (
                        SELECT 
                            branch_id,
                            SUM(price) AS expenses
                        FROM 
                            expense_tb
                        WHERE 
                            MONTH(date) = ? AND YEAR(date) = ? AND appearance = ?
                        GROUP BY 
                            branch_id
                    ) e ON b.branch_id = e.branch_id
                    WHERE 
                        b.branch_id = ?
                    ORDER BY 
                        b.branch_name ASC";
    
    $stmt = $conn->prepare($branchQuery);
    $stmt->bind_param("iiiisi", $currentMonth, $currentYear, $currentMonth, $currentYear, $visible, $branchId);
    $stmt->execute();
    $branchResult = $stmt->get_result();
    $branchPerformance = $branchResult->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'branchName' => $branchName,
        'servicesCount' => $servicesCount,
        'formattedRevenue' => $formattedRevenue,
        'pendingCount' => $pendingCount,
        'completedCount' => $completedCount,
        'monthlyRevenue' => $monthlyRevenue,
        'monthlyProjectedIncome' => $monthlyProjectedIncome,
        'services' => $services,
        'serviceData' => $serviceSales,
        'branchPerformance' => $branchPerformance
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>