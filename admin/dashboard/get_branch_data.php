<?php
require_once '../../db_connect.php'; // Your database connection file

$period = $_GET['period'] ?? 'month'; // Default to month if not specified

// Calculate date ranges based on period
$currentDate = new DateTime();
$currentYear = $currentDate->format('Y');
$currentMonth = $currentDate->format('m');
$currentWeek = $currentDate->format('W');

// Modify your SQL query to filter by the selected period
$branchQuery = "SELECT 
    b.branch_id,
    b.branch_name,
    COALESCE(s.service_count, 0) + COALESCE(c.custom_service_count, 0) AS service_count,
    COALESCE(s.revenue, 0) + COALESCE(c.custom_revenue, 0) AS revenue,
    COALESCE(e.expenses, 0) AS expenses,
    COALESCE(s.capital_total, 0) AS capital_total,
    (COALESCE(s.revenue, 0) + COALESCE(c.custom_revenue, 0) - (COALESCE(s.capital_total, 0) + COALESCE(e.expenses, 0))) AS profit,
    CASE 
        WHEN COALESCE(s.revenue, 0) + COALESCE(c.custom_revenue, 0) > 0 THEN 
            (COALESCE(s.revenue, 0) + COALESCE(c.custom_revenue, 0) - (COALESCE(s.capital_total, 0) + COALESCE(e.expenses, 0)) / (COALESCE(s.revenue, 0) + COALESCE(c.custom_revenue, 0)) * 100
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
    WHERE ";

// Add date filtering based on period
switch ($period) {
    case 'week':
        $branchQuery .= "YEARWEEK(s.get_timestamp, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'month':
        $branchQuery .= "MONTH(s.get_timestamp) = MONTH(CURDATE()) AND YEAR(s.get_timestamp) = YEAR(CURDATE())";
        break;
    case 'year':
        $branchQuery .= "YEAR(s.get_timestamp) = YEAR(CURDATE())";
        break;
    default:
        $branchQuery .= "MONTH(s.get_timestamp) = MONTH(CURDATE()) AND YEAR(s.get_timestamp) = YEAR(CURDATE())";
}

$branchQuery .= " GROUP BY s.branch_id ) s ON b.branch_id = s.branch_id
LEFT JOIN (
    SELECT 
        branch_id,
        COUNT(DISTINCT customsales_id) AS custom_service_count,
        SUM(amount_paid) AS custom_revenue
    FROM 
        customsales_tb
    WHERE ";

// Repeat for custom sales
switch ($period) {
    case 'week':
        $branchQuery .= "YEARWEEK(get_timestamp, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'month':
        $branchQuery .= "MONTH(get_timestamp) = MONTH(CURDATE()) AND YEAR(get_timestamp) = YEAR(CURDATE())";
        break;
    case 'year':
        $branchQuery .= "YEAR(get_timestamp) = YEAR(CURDATE())";
        break;
    default:
        $branchQuery .= "MONTH(get_timestamp) = MONTH(CURDATE()) AND YEAR(get_timestamp) = YEAR(CURDATE())";
}

$branchQuery .= " GROUP BY branch_id ) c ON b.branch_id = c.branch_id
LEFT JOIN (
    SELECT 
        branch_id,
        SUM(price) AS expenses
    FROM 
        expense_tb
    WHERE ";

// Repeat for expenses
switch ($period) {
    case 'week':
        $branchQuery .= "YEARWEEK(date, 1) = YEARWEEK(CURDATE(), 1) AND appearance = ?";
        break;
    case 'month':
        $branchQuery .= "MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE()) AND appearance = ?";
        break;
    case 'year':
        $branchQuery .= "YEAR(date) = YEAR(CURDATE()) AND appearance = ?";
        break;
    default:
        $branchQuery .= "MONTH(date) = MONTH(CURDATE()) AND YEAR(date) = YEAR(CURDATE()) AND appearance = ?";
}

$branchQuery .= " GROUP BY branch_id ) e ON b.branch_id = e.branch_id
WHERE 
    b.branch_id IN (1, 2)
ORDER BY 
    b.branch_name ASC";

$visible = "visible";
$stmt = $conn->prepare($branchQuery);
$stmt->bind_param("s", $visible);
$stmt->execute();
$branchResult = $stmt->get_result();

if ($branchResult->num_rows > 0) {
    while ($branch = $branchResult->fetch_assoc()) {
        // Your existing row generation code here
        // Same as in your original table body
    }
} else {
    echo '<tr><td colspan="7" class="px-4 py-3.5 text-sm text-center text-sidebar-text">No branch data available for the selected period.</td></tr>';
}
?>