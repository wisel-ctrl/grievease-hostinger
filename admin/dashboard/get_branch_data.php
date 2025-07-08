<?php
require_once '../../db_connect.php'; // Your database connection file

$period = $_GET['period'] ?? 'month'; // Default to month if not specified

// Calculate date ranges based on period
$currentDate = new DateTime();
$currentYear = $currentDate->format('Y');
$currentMonth = $currentDate->format('m');
$currentWeek = $currentDate->format('W');

$branchQuery = "SELECT 
    b.branch_id,
    b.branch_name,
    COALESCE(s.service_count, 0) + COALESCE(c.custom_service_count, 0) + COALESCE(a.analytics_service_count, 0) AS service_count,
    COALESCE(s.revenue, 0) + COALESCE(c.custom_revenue, 0) + COALESCE(a.analytics_revenue, 0) AS revenue,
    COALESCE(e.expenses, 0) AS expenses,
    COALESCE(s.capital_total, 0) AS capital_total,
    (COALESCE(s.revenue, 0) + COALESCE(c.custom_revenue, 0) + COALESCE(a.analytics_revenue, 0) - (COALESCE(s.capital_total, 0) + COALESCE(e.expenses, 0))) AS profit,
    CASE 
        WHEN COALESCE(s.revenue, 0) + COALESCE(c.custom_revenue, 0) + COALESCE(a.analytics_revenue, 0) > 0 THEN 
            (COALESCE(s.revenue, 0) + COALESCE(c.custom_revenue, 0) + COALESCE(a.analytics_revenue, 0) - (COALESCE(s.capital_total, 0) + COALESCE(e.expenses, 0))) / (COALESCE(s.revenue, 0) + COALESCE(c.custom_revenue, 0) + COALESCE(a.analytics_revenue, 0)) * 100
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
        a.branch_id,
        COUNT(DISTINCT a.analytics_id) AS analytics_service_count,
        SUM(a.amount_paid) AS analytics_revenue
    FROM 
        analytics_tb a
    WHERE 
        (a.sales_id IS NULL OR 
         (a.sales_type = 'traditional' AND NOT EXISTS (SELECT 1 FROM sales_tb s WHERE s.sales_id = a.sales_id)) OR
         (a.sales_type = 'custom' AND NOT EXISTS (SELECT 1 FROM customsales_tb cs WHERE cs.customsales_id = a.sales_id))
        ) AND ";

// Add date filtering for analytics based on period
switch ($period) {
    case 'week':
        $branchQuery .= "YEARWEEK(a.sale_date, 1) = YEARWEEK(CURDATE(), 1)";
        break;
    case 'month':
        $branchQuery .= "MONTH(a.sale_date) = MONTH(CURDATE()) AND YEAR(a.sale_date) = YEAR(CURDATE())";
        break;
    case 'year':
        $branchQuery .= "YEAR(a.sale_date) = YEAR(CURDATE())";
        break;
    default:
        $branchQuery .= "MONTH(a.sale_date) = MONTH(CURDATE()) AND YEAR(a.sale_date) = YEAR(CURDATE())";
}

$branchQuery .= " GROUP BY a.branch_id ) a ON b.branch_id = a.branch_id
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
        $branchName = htmlspecialchars($branch['branch_name']);
        $serviceCount = $branch['service_count'] ?? 0;
        $revenue = $branch['revenue'] ?? 0;
        $expenses = $branch['expenses'] ?? 0;
        $capitalTotal = $branch['capital_total'] ?? 0;
        $profit = $branch['profit'] ?? 0;
        $margin = $branch['margin'] ?? 0;
        
        // Format numbers
        $formattedRevenue = number_format($revenue, 2);
        $formattedExpenses = number_format($expenses, 2);
        $formattedCapital = number_format($capitalTotal, 2);
        $formattedProfit = number_format($profit, 2);
        $formattedMargin = number_format($margin, 1);
        
        // Determine styling based on PROFIT (not growth)
        $profitClass = $profit >= 0 ? 'bg-green-100 text-green-600 border-green-200' : 'bg-red-100 text-red-600 border-red-200';
        $profitIcon = $profit >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
        ?>
        <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">
                <div class="flex items-center">
                    <i class="fas fa-store mr-2 text-sidebar-accent"></i>
                    <div>
                        <div class="branch-name"><?php echo $branchName; ?></div>
                    </div>
                </div>
            </td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo $serviceCount; ?></td>
            <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">₱<?php echo $formattedRevenue; ?></td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">₱<?php echo $formattedExpenses; ?></td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">₱<?php echo $formattedCapital; ?></td>
            <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">₱<?php echo $formattedProfit; ?></td>
            <td class="px-4 py-3.5 text-sm">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $profitClass; ?> border">
                    <i class="fas <?php echo $profitIcon; ?> mr-1"></i> <?php echo $formattedMargin; ?>%
                </span>
            </td>
        </tr>
        <?php
    }
} else {
    echo '<tr><td colspan="7" class="px-4 py-3.5 text-sm text-center text-sidebar-text">No branch data available for the selected period.</td></tr>';
}
?>