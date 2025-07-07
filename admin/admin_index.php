<?php
session_start();

include 'faviconLogo.php'; 

date_default_timezone_set('Asia/Manila'); // Or your appropriate timezone
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check for admin user type (user_type = 1)
if ($_SESSION['user_type'] != 1) {
    // Redirect to appropriate page based on user type
    switch ($_SESSION['user_type']) {
        case 2:
            header("Location: ../employee/index.php");
            break;
        case 3:
            header("Location: ../customer/index.php");
            break;
        default:
            // Invalid user_type
            session_destroy();
            header("Location: ../Landing_Page/login.php");
    }
    exit();
}

// Optional: Check for session timeout (30 minutes)
$session_timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session has expired
    session_unset();
    session_destroy();
    header("Location: ../Landing_Page/login.php?timeout=1");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Prevent caching for authenticated pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../db_connect.php'; // Database connection

                // Get user's first name from database
                $user_id = $_SESSION['user_id'];
                $query = "SELECT first_name , last_name , email , birthdate FROM users WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $first_name = $row['first_name']; // We're confident user_id exists
                $last_name = $row['last_name'];
                $email = $row['email'];
                
// Get current month and year
$currentMonth = date('m');
$currentYear = date('Y');

// Calculate previous month and year (handling January case)
$prevMonth = $currentMonth == 1 ? 12 : $currentMonth - 1;
$prevYear = $currentMonth == 1 ? $currentYear - 1 : $currentYear;

// Get services this month count
$servicesQuery = "SELECT COUNT(*) as services_count FROM sales_tb 
                 WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
$stmt = $conn->prepare($servicesQuery);
$stmt->bind_param("ii", $currentMonth, $currentYear);
$stmt->execute();
$servicesResult = $stmt->get_result();
$servicesData = $servicesResult->fetch_assoc();
$servicesCount = $servicesData['services_count'] ?? 0;

// Get services last month count
$prevServicesQuery = "SELECT COUNT(*) as services_count FROM sales_tb 
                     WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
$stmt = $conn->prepare($prevServicesQuery);
$stmt->bind_param("ii", $prevMonth, $prevYear);
$stmt->execute();
$prevServicesResult = $stmt->get_result();
$prevServicesData = $prevServicesResult->fetch_assoc();
$prevServicesCount = $prevServicesData['services_count'] ?? 0;

// Calculate services percentage change
$servicesChange = 0;
if ($prevServicesCount > 0) {
    $servicesChange = (($servicesCount - $prevServicesCount) / $prevServicesCount) * 100;
}

// Current month revenue with the new approach
$revenueQuery = "SELECT SUM(amount_paid) as total_revenue FROM (
    -- 1. Direct sales from sales_tb
    SELECT amount_paid 
    FROM sales_tb 
    WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
    
    UNION ALL
    
    -- 2. Direct custom sales from customsales_tb not referenced in analytics_tb
    SELECT amount_paid
    FROM customsales_tb
    WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
    AND customsales_id NOT IN (
        SELECT sales_id FROM analytics_tb 
        WHERE sales_type = 'custom'
        AND MONTH(sale_date) = ? AND YEAR(sale_date) = ?
    )
    
    UNION ALL
    
    -- 3. All analytics records (they may or may not reference other tables)
    SELECT 
        CASE
            -- If it's traditional and has a sales_id reference
            WHEN a.sales_type = 'traditional' AND s.sales_id IS NOT NULL THEN s.amount_paid
            -- If it's custom and has a customsales_id reference
            WHEN a.sales_type = 'custom' AND c.customsales_id IS NOT NULL THEN c.amount_paid
            -- Otherwise use analytics_tb's own amount_paid
            ELSE a.amount_paid
        END as amount_paid
    FROM analytics_tb a
    LEFT JOIN sales_tb s ON a.sales_type = 'traditional' AND a.sales_id = s.sales_id
    LEFT JOIN customsales_tb c ON a.sales_type = 'custom' AND a.sales_id = c.customsales_id
    WHERE MONTH(a.sale_date) = ? AND YEAR(a.sale_date) = ?
) as combined_sales";

$stmt = $conn->prepare($revenueQuery);
$stmt->bind_param("iiiiiiii", 
    $currentMonth, $currentYear,        // 1. sales_tb direct
    $currentMonth, $currentYear,        // 2. customsales_tb direct
    $currentMonth, $currentYear,        // 2. customsales_tb not in analytics
    $currentMonth, $currentYear         // 3. analytics_tb all records
);
$stmt->execute();
$revenueResult = $stmt->get_result();
$revenueData = $revenueResult->fetch_assoc();
$totalRevenue = $revenueData['total_revenue'] ?? 0;

// Previous month revenue with the new approach
$prevRevenueQuery = "SELECT SUM(amount_paid) as total_revenue FROM (
    -- 1. Direct sales from sales_tb
    SELECT amount_paid 
    FROM sales_tb 
    WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
    
    UNION ALL
    
    -- 2. Direct custom sales from customsales_tb not referenced in analytics_tb
    SELECT amount_paid
    FROM customsales_tb
    WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
    AND customsales_id NOT IN (
        SELECT sales_id FROM analytics_tb 
        WHERE sales_type = 'custom'
        AND MONTH(sale_date) = ? AND YEAR(sale_date) = ?
    )
    
    UNION ALL
    
    -- 3. All analytics records (they may or may not reference other tables)
    SELECT 
        CASE
            -- If it's traditional and has a sales_id reference
            WHEN a.sales_type = 'traditional' AND s.sales_id IS NOT NULL THEN s.amount_paid
            -- If it's custom and has a customsales_id reference
            WHEN a.sales_type = 'custom' AND c.customsales_id IS NOT NULL THEN c.amount_paid
            -- Otherwise use analytics_tb's own amount_paid
            ELSE a.amount_paid
        END as amount_paid
    FROM analytics_tb a
    LEFT JOIN sales_tb s ON a.sales_type = 'traditional' AND a.sales_id = s.sales_id
    LEFT JOIN customsales_tb c ON a.sales_type = 'custom' AND a.sales_id = c.customsales_id
    WHERE MONTH(a.sale_date) = ? AND YEAR(a.sale_date) = ?
) as combined_sales";

$stmt = $conn->prepare($prevRevenueQuery);
$stmt->bind_param("iiiiiiii", 
    $prevMonth, $prevYear,        // 1. sales_tb direct
    $prevMonth, $prevYear,        // 2. customsales_tb direct
    $prevMonth, $prevYear,        // 2. customsales_tb not in analytics
    $prevMonth, $prevYear         // 3. analytics_tb all records
);
$stmt->execute();
$prevRevenueResult = $stmt->get_result();
$prevRevenueData = $prevRevenueResult->fetch_assoc();
$prevTotalRevenue = $prevRevenueData['total_revenue'] ?? 0;

// Calculate revenue percentage change
$revenueChange = 0;
if ($prevTotalRevenue > 0) {
    $revenueChange = (($totalRevenue - $prevTotalRevenue) / $prevTotalRevenue) * 100;
}

// Get pending services count this month
// Get pending count this month from both tables
$pendingQuery = "SELECT SUM(total) as pending_count FROM (
                SELECT COUNT(*) as total FROM sales_tb 
                WHERE status = 'Pending' AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
                UNION ALL
                SELECT COUNT(*) as total FROM customsales_tb 
                WHERE status = 'Pending' AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
                ) as combined";
$stmt = $conn->prepare($pendingQuery);
$stmt->bind_param("iiii", $currentMonth, $currentYear, $currentMonth, $currentYear);
$stmt->execute();
$pendingResult = $stmt->get_result();
$pendingData = $pendingResult->fetch_assoc();
$pendingCount = $pendingData['pending_count'] ?? 0;

// Get pending count last month from both tables
$prevPendingQuery = "SELECT SUM(total) as pending_count FROM (
                    SELECT COUNT(*) as total FROM sales_tb 
                    WHERE status = 'Pending' AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
                    UNION ALL
                    SELECT COUNT(*) as total FROM customsales_tb 
                    WHERE status = 'Pending' AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
                    ) as combined";
$stmt = $conn->prepare($prevPendingQuery);
$stmt->bind_param("iiii", $prevMonth, $prevYear, $prevMonth, $prevYear);
$stmt->execute();
$prevPendingResult = $stmt->get_result();
$prevPendingData = $prevPendingResult->fetch_assoc();
$prevPendingCount = $prevPendingData['pending_count'] ?? 0;

// Calculate pending percentage change
$pendingChange = 0;
if ($prevPendingCount > 0) {
    $pendingChange = (($pendingCount - $prevPendingCount) / $prevPendingCount) * 100;
}

// Get completed count this month from both tables
$completedQuery = "SELECT SUM(total) as completed_count FROM (
                  SELECT COUNT(*) as total FROM sales_tb 
                  WHERE status = 'Completed' AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
                  UNION ALL
                  SELECT COUNT(*) as total FROM customsales_tb 
                  WHERE status = 'Completed' AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
                  ) as combined";
$stmt = $conn->prepare($completedQuery);
$stmt->bind_param("iiii", $currentMonth, $currentYear, $currentMonth, $currentYear);
$stmt->execute();
$completedResult = $stmt->get_result();
$completedData = $completedResult->fetch_assoc();
$completedCount = $completedData['completed_count'] ?? 0;

// Get completed count last month from both tables
$prevCompletedQuery = "SELECT SUM(total) as completed_count FROM (
                      SELECT COUNT(*) as total FROM sales_tb 
                      WHERE status = 'Completed' AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
                      UNION ALL
                      SELECT COUNT(*) as total FROM customsales_tb 
                      WHERE status = 'Completed' AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
                      ) as combined";
$stmt = $conn->prepare($prevCompletedQuery);
$stmt->bind_param("iiii", $prevMonth, $prevYear, $prevMonth, $prevYear);
$stmt->execute();
$prevCompletedResult = $stmt->get_result();
$prevCompletedData = $prevCompletedResult->fetch_assoc();
$prevCompletedCount = $prevCompletedData['completed_count'] ?? 0;

// Calculate completed percentage change
$completedChange = 0;
if ($prevCompletedCount > 0) {
    $completedChange = (($completedCount - $prevCompletedCount) / $prevCompletedCount) * 100;
}

// Format the revenue with PHP's number_format
$formattedRevenue = number_format($totalRevenue, 2);
?>
<?php
// Get monthly labels and data
$monthLabels = [];
$yearLabels = [];
$currentDate = new DateTime(); // Get current date
$currentDate->modify('first day of this month'); // Start from beginning of current month

// Prepare labels for last 12 months
for ($i = 11; $i >= 0; $i--) {
    $date = clone $currentDate;
    $date->modify("-$i months");
    $monthLabels[] = $date->format('M Y');
}

// Prepare labels for last 5 years
for ($i = 4; $i >= 0; $i--) {
    $date = clone $currentDate;
    $date->modify("-$i years");
    $yearLabels[] = $date->format('Y');
}

// Get monthly projected income data
$monthlyProjectedIncomeData = [];
for ($i = 11; $i >= 0; $i--) {
    $date = clone $currentDate;
    $date->modify("-$i months");
    $month = $date->format('m');
    $year = $date->format('Y');
    
    $query = "SELECT SUM(total_discounted) as projected_income FROM (
        -- 1. Direct sales from sales_tb
        SELECT discounted_price as total_discounted 
        FROM sales_tb 
        WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
        
        UNION ALL
        
        -- 2. Direct custom sales from customsales_tb not referenced in analytics_tb
        SELECT discounted_price as total_discounted
        FROM customsales_tb
        WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
        AND customsales_id NOT IN (
            SELECT sales_id FROM analytics_tb 
            WHERE sales_type = 'custom'
            AND MONTH(sale_date) = ? AND YEAR(sale_date) = ?
        )
        
        UNION ALL
        
        -- 3. All analytics records (they may or may not reference other tables)
        SELECT 
            CASE
                -- If it's traditional and has a sales_id reference
                WHEN a.sales_type = 'traditional' AND s.sales_id IS NOT NULL THEN s.discounted_price
                -- If it's custom and has a customsales_id reference
                WHEN a.sales_type = 'custom' AND c.customsales_id IS NOT NULL THEN c.discounted_price
                -- Otherwise use analytics_tb's own discounted_price
                ELSE a.discounted_price
            END as total_discounted
        FROM analytics_tb a
        LEFT JOIN sales_tb s ON a.sales_type = 'traditional' AND a.sales_id = s.sales_id
        LEFT JOIN customsales_tb c ON a.sales_type = 'custom' AND a.sales_id = c.customsales_id
        WHERE MONTH(a.sale_date) = ? AND YEAR(a.sale_date) = ?
    ) as combined_sales";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiiiiii", 
        $month, $year, $month, $year, $month, $year, $month, $year
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $monthlyProjectedIncomeData[] = $data['projected_income'] ?? 0;
}

// Get yearly projected income data
$yearlyProjectedIncomeData = [];
for ($i = 4; $i >= 0; $i--) {
    $date = clone $currentDate;
    $date->modify("-$i years");
    $year = $date->format('Y');
    
    $query = "SELECT SUM(total_discounted) as projected_income FROM (
        -- Your existing query modified for yearly
        SELECT discounted_price as total_discounted 
        FROM sales_tb 
        WHERE YEAR(get_timestamp) = ?
        
        UNION ALL
        
        SELECT discounted_price as total_discounted
        FROM customsales_tb
        WHERE YEAR(get_timestamp) = ?
        AND customsales_id NOT IN (
            SELECT sales_id FROM analytics_tb 
            WHERE sales_type = 'custom'
            AND YEAR(sale_date) = ?
        )
        
        UNION ALL
        
        SELECT 
            CASE
                WHEN a.sales_type = 'traditional' AND s.sales_id IS NOT NULL THEN s.discounted_price
                WHEN a.sales_type = 'custom' AND c.customsales_id IS NOT NULL THEN c.discounted_price
                ELSE a.discounted_price
            END as total_discounted
        FROM analytics_tb a
        LEFT JOIN sales_tb s ON a.sales_type = 'traditional' AND a.sales_id = s.sales_id
        LEFT JOIN customsales_tb c ON a.sales_type = 'custom' AND a.sales_id = c.customsales_id
        WHERE YEAR(a.sale_date) = ?
    ) as combined_sales";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiii", $year, $year, $year, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $yearlyProjectedIncomeData[] = $data['projected_income'] ?? 0;
}
?>
<?php
function getBranchMetrics($conn, $branchId) {
  $metrics = [
      'revenue' => 0,
      'profit' => 0,
      'margin' => 0,
      'customers' => 0
  ];

  // Current month and year
  $currentMonth = date('m');
  $currentYear = date('Y');

  // Get current month revenue (sum of amount_paid from both sales_tb and customsales_tb)
  $revenueQuery = "SELECT 
                      (SELECT COALESCE(SUM(amount_paid), 0) FROM sales_tb 
                        WHERE branch_id = ? AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?)
                      +
                      (SELECT COALESCE(SUM(amount_paid), 0) FROM customsales_tb 
                        WHERE branch_id = ? AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?)
                      AS total_revenue";
  
  $stmt = $conn->prepare($revenueQuery);
  $stmt->bind_param("iiiiii", $branchId, $currentMonth, $currentYear, $branchId, $currentMonth, $currentYear);
  $stmt->execute();
  $revenueResult = $stmt->get_result();
  $revenueData = $revenueResult->fetch_assoc();
  $metrics['revenue'] = $revenueData['total_revenue'] ?? 0;

  // Get current month capital price by joining sales_tb with services_tb
  $capitalQuery = "SELECT SUM(s.capital_price) as total_capital 
                  FROM sales_tb sa
                  JOIN services_tb s ON sa.service_id = s.service_id
                  WHERE sa.branch_id = ? AND MONTH(sa.get_timestamp) = ? AND YEAR(sa.get_timestamp) = ?";
  $stmt = $conn->prepare($capitalQuery);
  $stmt->bind_param("iii", $branchId, $currentMonth, $currentYear);
  $stmt->execute();
  $capitalResult = $stmt->get_result();
  $capitalData = $capitalResult->fetch_assoc();
  $totalCapital = $capitalData['total_capital'] ?? 0;
  $visible = "visible";

  // Get sum of expenses for current month
  $expensesQuery = "SELECT SUM(price) as total_expenses FROM expense_tb 
                    WHERE branch_id = ? AND MONTH(date) = ? AND YEAR(date) = ? AND appearance = ?";
  $stmt = $conn->prepare($expensesQuery);
  $stmt->bind_param("iiis", $branchId, $currentMonth, $currentYear, $visible);
  $stmt->execute();
  $expensesResult = $stmt->get_result();
  $expensesData = $expensesResult->fetch_assoc();
  $totalExpenses = $expensesData['total_expenses'] ?? 0;

  // Calculate current month profit
  $metrics['profit'] = $metrics['revenue'] - ($totalCapital + $totalExpenses);

  // Calculate margin (profit / revenue * 100)
  if ($metrics['revenue'] > 0) {
      $metrics['margin'] = ($metrics['profit'] / $metrics['revenue']) * 100;
  }

  // Get current month customer count (users with user_type = 3 and branch_loc = branch_id)
  $customersQuery = "SELECT COUNT(*) as customer_count FROM users 
                    WHERE user_type = 3 AND branch_loc = ?";
  $stmt = $conn->prepare($customersQuery);
  $stmt->bind_param("i", $branchId);
  $stmt->execute();
  $customersResult = $stmt->get_result();
  $customersData = $customersResult->fetch_assoc();
  $metrics['customers'] = $customersData['customer_count'] ?? 0;

  return $metrics;
}

// Get metrics for both branches
$pilaMetrics = getBranchMetrics($conn, 2); // Pila branch_id = 2
$paeteMetrics = getBranchMetrics($conn, 1); // Paete branch_id = 1

// Get monthly revenue data for the last 6 months
$yearlyRevenueData = [];
$yearLabels = [];
if (isset($_GET['view']) && $_GET['view'] === 'yearly') {
    for ($i = 5; $i >= 0; $i--) {
        $date = clone $currentDate;
        $date->modify("-$i years");
        $year = $date->format('Y');
        
        $query = "SELECT SUM(amount_paid) as revenue FROM (
            -- 1. Direct sales from sales_tb
            SELECT amount_paid 
            FROM sales_tb 
            WHERE YEAR(get_timestamp) = ?
            
            UNION ALL
            
            -- 2. Direct custom sales from customsales_tb not referenced in analytics_tb
            SELECT amount_paid
            FROM customsales_tb
            WHERE YEAR(get_timestamp) = ?
            AND customsales_id NOT IN (
                SELECT sales_id FROM analytics_tb 
                WHERE sales_type = 'custom'
                AND YEAR(sale_date) = ?
            )
            
            UNION ALL
            
            -- 3. All analytics records (they may or may not reference other tables)
            SELECT 
                CASE
                    -- If it's traditional and has a sales_id reference
                    WHEN a.sales_type = 'traditional' AND s.sales_id IS NOT NULL THEN s.amount_paid
                    -- If it's custom and has a customsales_id reference
                    WHEN a.sales_type = 'custom' AND c.customsales_id IS NOT NULL THEN c.amount_paid
                    -- Otherwise use analytics_tb's own amount_paid
                    ELSE a.amount_paid
                END as amount_paid
            FROM analytics_tb a
            LEFT JOIN sales_tb s ON a.sales_type = 'traditional' AND a.sales_id = s.sales_id
            LEFT JOIN customsales_tb c ON a.sales_type = 'custom' AND a.sales_id = c.customsales_id
            WHERE YEAR(a.sale_date) = ?
        ) as combined_sales";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiii", 
            $year,        // 1. sales_tb direct
            $year,        // 2. customsales_tb direct
            $year,        // 2. customsales_tb not in analytics
            $year         // 3. analytics_tb all records
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        $yearlyRevenueData[] = $data['revenue'] ?? 0;
        $yearLabels[] = $year;
    }
} else {
    // Original monthly data query
    $monthlyRevenueData = [];
    $monthLabels = [];
    for ($i = 11; $i >= 0; $i--) {
        $date = clone $currentDate;
        $date->modify("-$i months");
        $month = $date->format('m');
        $year = $date->format('Y');
        
        $query = "SELECT SUM(amount_paid) as revenue FROM (
            -- 1. Direct sales from sales_tb
            SELECT amount_paid 
            FROM sales_tb 
            WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
            
            UNION ALL
            
            -- 2. Direct custom sales from customsales_tb not referenced in analytics_tb
            SELECT amount_paid
            FROM customsales_tb
            WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
            AND customsales_id NOT IN (
                SELECT sales_id FROM analytics_tb 
                WHERE sales_type = 'custom'
                AND MONTH(sale_date) = ? AND YEAR(sale_date) = ?
            )
            
            UNION ALL
            
            -- 3. All analytics records (they may or may not reference other tables)
            SELECT 
                CASE
                    -- If it's traditional and has a sales_id reference
                    WHEN a.sales_type = 'traditional' AND s.sales_id IS NOT NULL THEN s.amount_paid
                    -- If it's custom and has a customsales_id reference
                    WHEN a.sales_type = 'custom' AND c.customsales_id IS NOT NULL THEN c.amount_paid
                    -- Otherwise use analytics_tb's own amount_paid
                    ELSE a.amount_paid
                END as amount_paid
            FROM analytics_tb a
            LEFT JOIN sales_tb s ON a.sales_type = 'traditional' AND a.sales_id = s.sales_id
            LEFT JOIN customsales_tb c ON a.sales_type = 'custom' AND a.sales_id = c.customsales_id
            WHERE MONTH(a.sale_date) = ? AND YEAR(a.sale_date) = ?
        ) as combined_sales";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiiiiiii", 
            $month, $year,        // 1. sales_tb direct
            $month, $year,        // 2. customsales_tb direct
            $month, $year,        // 2. customsales_tb not in analytics
            $month, $year         // 3. analytics_tb all records
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        
        $monthlyRevenueData[] = $data['revenue'] ?? 0;
        $monthLabels[] = $date->format('M Y');
    }
}
?>
<?php
// Get monthly revenue data for Pila branch (branch_id = 2)
$pilaMonthlyRevenue = [];
for ($i = 11; $i >= 0; $i--) {
    $date = clone $currentDate;
    $date->modify("-$i months");
    $month = $date->format('m');
    $year = $date->format('Y');
    
    $query = "SELECT SUM(amount_paid) as revenue FROM (
        -- 1. Direct sales from sales_tb
        SELECT amount_paid 
        FROM sales_tb 
        WHERE branch_id = 2 AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
        
        UNION ALL
        
        -- 2. Direct custom sales from customsales_tb not referenced in analytics_tb
        SELECT amount_paid
        FROM customsales_tb
        WHERE branch_id = 2 AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
        AND customsales_id NOT IN (
            SELECT sales_id FROM analytics_tb 
            WHERE sales_type = 'custom'
            AND MONTH(sale_date) = ? AND YEAR(sale_date) = ?
        )
        
        UNION ALL
        
        -- 3. All analytics records (they may or may not reference other tables)
        SELECT 
            CASE
                -- If it's traditional and has a sales_id reference
                WHEN a.sales_type = 'traditional' AND s.sales_id IS NOT NULL THEN s.amount_paid
                -- If it's custom and has a customsales_id reference
                WHEN a.sales_type = 'custom' AND c.customsales_id IS NOT NULL THEN c.amount_paid
                -- Otherwise use analytics_tb's own amount_paid
                ELSE a.amount_paid
            END as amount_paid
        FROM analytics_tb a
        LEFT JOIN sales_tb s ON a.sales_type = 'traditional' AND a.sales_id = s.sales_id AND s.branch_id = 2
        LEFT JOIN customsales_tb c ON a.sales_type = 'custom' AND a.sales_id = c.customsales_id AND c.branch_id = 2
        WHERE MONTH(a.sale_date) = ? AND YEAR(a.sale_date) = ?
        AND (a.branch_id = 2 OR (s.sales_id IS NOT NULL OR c.customsales_id IS NOT NULL))
    ) as combined_sales";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiiiiii", 
        $month, $year,        // 1. sales_tb direct
        $month, $year,        // 2. customsales_tb direct
        $month, $year,        // 2. customsales_tb not in analytics
        $month, $year         // 3. analytics_tb all records
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $pilaMonthlyRevenue[] = $data['revenue'] ?? 0;
}

// Get monthly revenue data for Paete branch (branch_id = 1)
$paeteMonthlyRevenue = [];
for ($i = 11; $i >= 0; $i--) {
    $date = clone $currentDate;
    $date->modify("-$i months");
    $month = $date->format('m');
    $year = $date->format('Y');
    
    $query = "SELECT SUM(amount_paid) as revenue FROM (
        -- 1. Direct sales from sales_tb
        SELECT amount_paid 
        FROM sales_tb 
        WHERE branch_id = 1 AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
        
        UNION ALL
        
        -- 2. Direct custom sales from customsales_tb not referenced in analytics_tb
        SELECT amount_paid
        FROM customsales_tb
        WHERE branch_id = 1 AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
        AND customsales_id NOT IN (
            SELECT sales_id FROM analytics_tb 
            WHERE sales_type = 'custom'
            AND MONTH(sale_date) = ? AND YEAR(sale_date) = ?
        )
        
        UNION ALL
        
        -- 3. All analytics records (they may or may not reference other tables)
        SELECT 
            CASE
                -- If it's traditional and has a sales_id reference
                WHEN a.sales_type = 'traditional' AND s.sales_id IS NOT NULL THEN s.amount_paid
                -- If it's custom and has a customsales_id reference
                WHEN a.sales_type = 'custom' AND c.customsales_id IS NOT NULL THEN c.amount_paid
                -- Otherwise use analytics_tb's own amount_paid
                ELSE a.amount_paid
            END as amount_paid
        FROM analytics_tb a
        LEFT JOIN sales_tb s ON a.sales_type = 'traditional' AND a.sales_id = s.sales_id AND s.branch_id = 1
        LEFT JOIN customsales_tb c ON a.sales_type = 'custom' AND a.sales_id = c.customsales_id AND c.branch_id = 1
        WHERE MONTH(a.sale_date) = ? AND YEAR(a.sale_date) = ?
        AND (a.branch_id = 1 OR (s.sales_id IS NOT NULL OR c.customsales_id IS NOT NULL))
    ) as combined_sales";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiiiiii", 
        $month, $year,        // 1. sales_tb direct
        $month, $year,        // 2. customsales_tb direct
        $month, $year,        // 2. customsales_tb not in analytics
        $month, $year         // 3. analytics_tb all records
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $paeteMonthlyRevenue[] = $data['revenue'] ?? 0;
}

?>
<?php
$serviceSalesQuery = "SELECT 
                        s.service_name,
                        sa.branch_id,
                        COUNT(*) AS total_sales
                      FROM sales_tb sa
                      JOIN services_tb s ON sa.service_id = s.service_id
                      GROUP BY s.service_name, sa.branch_id";

$stmt = $conn->prepare($serviceSalesQuery);
$stmt->execute();
$result = $stmt->get_result();

// Organize the data by service and branch
$serviceData = [];
while ($row = $result->fetch_assoc()) {
    $serviceName = $row['service_name'];
    $branchId = $row['branch_id'];
    $totalSales = $row['total_sales'];
    
    if (!isset($serviceData[$serviceName])) {
        $serviceData[$serviceName] = [
            'Pila' => 0,
            'Paete' => 0
        ];
    }
    
    if ($branchId == 1) { // Paete branch
        $serviceData[$serviceName]['Paete'] = $totalSales;
    } else if ($branchId == 2) { // Pila branch
        $serviceData[$serviceName]['Pila'] = $totalSales;
    }
}

// Prepare data for chart
$services = array_keys($serviceData);
$pilaData = [];
$paeteData = [];

foreach ($serviceData as $service => $branches) {
    $pilaData[] = $branches['Pila'];
    $paeteData[] = $branches['Paete'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Admin Dashboard</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
  
</head>
<style>
  /* Add this to your stylesheet */
.bg-gray-100 .bg-blue-500 {
  transition: all 0.3s ease;
}

.bg-gray-100 .text-gray-600:hover {
  color: #4b5563;
}
</style>
<body class="flex bg-gray-50">

<?php include 'admin_sidebar.php'; ?>

<!-- Main Content -->
  <div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
    <!-- Header with breadcrumb and welcome message -->
    <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
      <div>
        <h1 class="text-2xl font-bold text-sidebar-text">Dashboard</h1>
        <p class="text-sm text-gray-500">
      Welcome back, 
      <span class="hidden md:inline">
          <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
      </span>
  </p>

    </div>
<div class="flex space-x-3">
    <!-- Notification Bell Button with improved styling -->
    <div class="relative">
        <?php
        // Include database connection
        require_once '../db_connect.php';
        
        // Query for pending funeral bookings
        $funeralQuery = "SELECT 
                            b.booking_id,
                            CONCAT(b.deceased_lname, ', ', b.deceased_fname, ' ', IFNULL(b.deceased_midname, '')) AS deceased_name,
                            b.booking_date AS notification_date,
                            IFNULL(s.service_name, 'Custom Package') AS service_name,
                            'funeral' AS notification_type,
                            'Booking_acceptance.php' AS link_base
                         FROM booking_tb b
                         LEFT JOIN services_tb s ON b.service_id = s.service_id
                         WHERE b.status = 'Pending'
                         ORDER BY b.booking_date DESC";
        
        // Query for pending life plan bookings
        $lifeplanQuery = "SELECT 
                            lb.lpbooking_id AS booking_id,
                            CONCAT(lb.benefeciary_lname, ', ', lb.benefeciary_fname, ' ', IFNULL(lb.benefeciary_mname, '')) AS deceased_name,
                            lb.initial_date AS notification_date,
                            s.service_name,
                            'lifeplan' AS notification_type,
                            'Booking_acceptance.php' AS link_base
                          FROM lifeplan_booking_tb lb
                          JOIN services_tb s ON lb.service_id = s.service_id
                          WHERE lb.booking_status = 'pending'
                          ORDER BY lb.initial_date DESC";
        
        // Query for pending ID validations
        $idValidationQuery = "SELECT 
                                id AS booking_id,
                                '' AS deceased_name,
                                upload_at AS notification_date,
                                'ID Validation' AS service_name,
                                'id_validation' AS notification_type,
                                'id_confirmation.php' AS link_base
                             FROM valid_id_tb
                             WHERE is_validated = 'no'
                             ORDER BY upload_at DESC";
        
        // Execute all queries
        $funeralResult = $conn->query($funeralQuery);
        $lifeplanResult = $conn->query($lifeplanQuery);
        $idValidationResult = $conn->query($idValidationQuery);
        
        // Combine all results into a single array
        $allNotifications = [];
        
        if ($funeralResult && $funeralResult->num_rows > 0) {
            while ($row = $funeralResult->fetch_assoc()) {
                $allNotifications[] = $row;
            }
        }
        
        if ($lifeplanResult && $lifeplanResult->num_rows > 0) {
            while ($row = $lifeplanResult->fetch_assoc()) {
                $allNotifications[] = $row;
            }
        }
        
        if ($idValidationResult && $idValidationResult->num_rows > 0) {
            while ($row = $idValidationResult->fetch_assoc()) {
                $allNotifications[] = $row;
            }
        }
        
        // Sort all notifications by date (newest first)
        usort($allNotifications, function($a, $b) {
            return strtotime($b['notification_date']) - strtotime($a['notification_date']);
        });
        
        $totalPending = count($allNotifications);
        ?>
        
        <button id="notification-bell" class="p-2 rounded-full bg-white border border-sidebar-border shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300 relative">
            <i class="fas fa-bell"></i>
            <?php if ($totalPending > 0): ?>
            <span class="absolute -top-1 -right-1 bg-error text-white text-xs rounded-full h-5 w-5 flex items-center justify-center transform transition-all duration-300 scale-100 origin-center shadow-sm"><?php echo $totalPending; ?></span>
            <?php endif; ?>
        </button>
        
        <!-- Improved Notification Dropdown -->
        <div id="notifications-dropdown" class="absolute right-0 mt-3 w-96 bg-white rounded-lg shadow-card border border-sidebar-border z-50 hidden transform transition-all duration-300 opacity-0 translate-y-2" style="max-height: 85vh;">
            <!-- Notifications Header with improved styling -->
            <div class="px-5 py-4 border-b border-sidebar-border flex justify-between items-center bg-gradient-to-r from-gray-50 to-white rounded-t-lg">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full bg-sidebar-accent bg-opacity-10 text-sidebar-accent flex items-center justify-center mr-3">
                        <i class="fas fa-bell"></i>
                    </div>
                    <h3 class="font-medium text-sidebar-text">Notifications</h3>
                    <?php if ($totalPending > 0): ?>
                    <span class="ml-2 bg-error text-white text-xs rounded-full h-5 w-5 flex items-center justify-center"><?php echo $totalPending; ?></span>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Notifications List with improved styling -->
            <div class="max-h-[60vh] overflow-y-auto scrollbar-thin">
                <?php
                if ($totalPending > 0) {
                    foreach ($allNotifications as $notification) {
                        $timeAgo = time_elapsed_string($notification['notification_date']);
                        
                        // Determine styling based on notification type
                        if ($notification['notification_type'] === 'funeral') {
                            $color = 'blue';
                            $icon = 'fas fa-cross';
                            $title = 'New funeral booking request';
                        } elseif ($notification['notification_type'] === 'lifeplan') {
                            $color = 'purple';
                            $icon = 'fas fa-heart';
                            $title = 'New life plan booking request';
                        } else { // id_validation
                            $color = 'amber';
                            $icon = 'fas fa-id-card';
                            $title = 'New ID validation request';
                        }
                        ?>
                        <a href="<?php echo $notification['link_base']; ?>" class="block px-5 py-4 border-b border-sidebar-border hover:bg-sidebar-hover transition-all duration-300 flex items-start relative">
                            <div class="absolute left-0 top-0 bottom-0 w-1 bg-<?php echo $color; ?>-500 rounded-r"></div>
                            <div class="flex-shrink-0 bg-<?php echo $color; ?>-100 rounded-full p-2.5 mr-4">
                                <i class="<?php echo $icon; ?> text-<?php echo $color; ?>-600"></i>
                            </div>
                            <div class="flex-grow">
                                <div class="flex justify-between items-start">
                                    <p class="text-sm font-semibold text-sidebar-text"><?php echo $title; ?></p>
                                    <span class="h-2.5 w-2.5 bg-<?php echo $color; ?>-600 rounded-full block flex-shrink-0 ml-2 mt-1"></span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">
                                    <?php 
                                    if ($notification['notification_type'] === 'id_validation') {
                                        echo 'ID image uploaded and awaiting validation';
                                    } else {
                                        echo htmlspecialchars($notification['deceased_name']) . ' - ' . htmlspecialchars($notification['service_name']);
                                    }
                                    ?>
                                </p>
                                <div class="flex items-center mt-2 text-xs text-gray-400">
                                    <i class="far fa-clock mr-1.5"></i>
                                    <span><?php echo $timeAgo; ?></span>
                                </div>
                            </div>
                        </a>
                        <?php
                    }
                } else {
                    ?>
                    <div class="px-5 py-4 text-center text-gray-500">
                        <i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i>
                        <p>No pending notifications</p>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <!-- Notifications Footer with improved styling -->
            <div class="px-5 py-3 text-center border-t border-sidebar-border bg-gradient-to-r from-gray-50 to-white rounded-b-lg">
                <a href="notification.php" class="text-sm text-sidebar-accent hover:text-darkgold transition-colors font-medium inline-flex items-center">
                    View all notifications
                    <i class="fas fa-chevron-right ml-1 text-xs"></i>
                </a>
            </div>
        </div>
    </div>
</div>
<?php
// Function to calculate time ago
// Function to calculate time ago
function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    // Calculate weeks separately without adding to the DateInterval object
    $weeks = floor($diff->d / 7);
    $days = $diff->d % 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    
    // Replace days with the remainder days
    $diff->d = $days;
    
    foreach ($string as $k => &$v) {
        if ($k === 'w') {
            // Handle weeks separately
            if ($weeks) {
                $v = $weeks . ' ' . $v . ($weeks > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        } elseif ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}
?>
  </div>

  <!-- Analytics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
  <!-- Services This Month Card -->
  <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
    <!-- Card header with brighter gradient background -->
    <div class="bg-gradient-to-r from-blue-100 to-blue-200 px-6 py-4">
      <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-medium text-gray-700">Services This Month</h3>
        <div class="w-10 h-10 rounded-full bg-white/90 text-slate-600 flex items-center justify-center">
          <i class="fas fa-calendar-alt"></i>
        </div>
      </div>
      <div class="flex items-end">
       <span class="text-2xl md:text-3xl font-bold text-gray-800"><?php echo $servicesCount; ?></span>
      </div>
    </div>
    
    <!-- Card footer with change indicator -->
    <div class="px-6 py-3 bg-white border-t border-gray-100">
      <div class="flex items-center text-emerald-600">
        <i class="fas fa-arrow-up mr-1.5 text-xs"></i>
        <span class="font-medium text-xs"><?php echo number_format(abs($servicesChange), 1); ?>% </span>
        <span class="text-xs text-gray-500 ml-1">from last month</span>
      </div>
    </div>
  </div>
  
  <!-- Revenue Card -->
  <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
    <!-- Card header with brighter gradient background -->
    <div class="bg-gradient-to-r from-green-100 to-green-200 px-6 py-4">
      <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-medium text-gray-700">Revenue</h3>
        <div class="w-10 h-10 rounded-full bg-white/90 text-green-600 flex items-center justify-center">
          <i class="fas fa-peso-sign"></i>
        </div>
      </div>
      <div class="flex items-end">
      <span class="text-2xl md:text-3xl font-bold text-gray-800">₱<?php echo $formattedRevenue; ?></span>
      </div>
    </div>
    
    <!-- Card footer with change indicator -->
    <div class="px-6 py-3 bg-white border-t border-gray-100">
      <div class="flex items-center text-emerald-600">
        <i class="fas fa-arrow-up mr-1.5 text-xs"></i>
        <span class="font-medium text-xs"><?php echo number_format(abs($revenueChange), 1); ?>% </span>
        <span class="text-xs text-gray-500 ml-1">from last month</span>
      </div>
    </div>
  </div>
  
  <!-- Pending Services Card -->
  <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
    <!-- Card header with brighter gradient background -->
    <div class="bg-gradient-to-r from-orange-100 to-orange-200 px-6 py-4">
      <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-medium text-gray-700">Pending Services</h3>
        <div class="w-10 h-10 rounded-full bg-white/90 text-orange-600 flex items-center justify-center">
          <i class="fas fa-tasks"></i>
        </div>
      </div>
      <div class="flex items-end">
        <span class="text-2xl md:text-3xl font-bold text-gray-800"><?php echo $pendingCount; ?></span>
      </div>
    </div>
    
    <!-- Card footer with change indicator -->
    <div class="px-6 py-3 bg-white border-t border-gray-100">
      <div class="flex items-center text-rose-600">
        <i class="fas fa-arrow-down mr-1.5 text-xs"></i>
        <span class="font-medium text-xs"><?php echo number_format(abs($pendingChange), 1); ?>% </span>
        <span class="text-xs text-gray-500 ml-1">from last month</span>
      </div>
    </div>
  </div>
  
  <!-- Completed Services Card -->
  <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
    <!-- Card header with brighter gradient background -->
    <div class="bg-gradient-to-r from-purple-100 to-purple-200 px-6 py-4">
      <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-medium text-gray-700">Completed Services</h3>
        <div class="w-10 h-10 rounded-full bg-white/90 text-purple-600 flex items-center justify-center">
          <i class="fas fa-clipboard-check"></i>
        </div>
      </div>
      <div class="flex items-end">
        <span class="text-2xl md:text-3xl font-bold text-gray-800"><?php echo $completedCount; ?></span>
      </div>
    </div>
    
    <!-- Card footer with change indicator -->
    <div class="px-6 py-3 bg-white border-t border-gray-100">
      <div class="flex items-center text-emerald-600">
        <i class="fas fa-arrow-up mr-1.5 text-xs"></i>
        <span class="font-medium text-xs"><?php echo number_format(abs($completedChange), 1); ?>% </span>
        <span class="text-xs text-gray-500 ml-1">from last month</span>
      </div>
    </div>
  </div>
</div>

 <!-- Branch Comparison -->
 <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
  <!-- Pila Branch Card -->
  <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-200">
    <!-- Card header with subdued gradient background -->
    <div class="bg-gradient-to-r from-gray-100 to-slate-500 p-5 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-full bg-white/90 text-slate-700 flex items-center justify-center shadow-sm">
            <i class="fas fa-building"></i>
          </div>
          <h3 class="text-lg font-semibold text-gray-800">Pila Branch</h3>
        </div>
        <span class="px-3 py-1 text-xs font-medium bg-slate-100 text-slate-700 rounded-full">Main Branch</span>
      </div>
    </div>
    
    <!-- Card content -->
    <div class="p-6">
      <div class="grid grid-cols-2 gap-6">
        <!-- Revenue -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center mb-1 text-gray-600 text-sm">
            <i class="fas fa-chart-line mr-2"></i>
            <span>Revenue</span>
          </div>
          <div class="text-xl font-bold text-gray-800">₱<?php echo number_format($pilaMetrics['revenue'], 2); ?></div>
          <div class="mt-2 text-xs text-gray-500">
  Current month
</div>
        </div>
        
        <!-- Profit -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center mb-1 text-gray-600 text-sm">
            <i class="fas fa-wallet mr-2"></i>
            <span>Profit</span>
          </div>
          <div class="text-xl font-bold text-gray-800">₱<?php echo number_format($pilaMetrics['profit'], 2); ?></div>
          <div class="mt-2 text-xs text-gray-500">
  Current month
</div>
        </div>
        
        <!-- Margin -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center mb-1 text-gray-600 text-sm">
            <i class="fas fa-percentage mr-2"></i>
            <span>Margin</span>
          </div>
          <div class="text-xl font-bold text-gray-800"><?php echo number_format($pilaMetrics['margin'], 1); ?>%</div>
          <div class="mt-2 text-xs text-gray-500">
  Current month
</div>
        </div>
        
        <!-- Customers -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center mb-1 text-gray-600 text-sm">
            <i class="fas fa-users mr-2"></i>
            <span>Customers</span>
          </div>
          <div class="text-xl font-bold text-gray-800"><?php echo $pilaMetrics['customers']; ?></div>
          <div class="mt-2 text-xs text-gray-500">
  Current month
</div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Paete Branch Card -->
  <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-200">
    <!-- Card header with subdued gradient background -->
    <div class="bg-gradient-to-r from-gray-100 to-slate-500 p-5 border-b border-gray-200">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="w-10 h-10 rounded-full bg-white/90 text-slate-700 flex items-center justify-center shadow-sm">
            <i class="fas fa-store"></i>
          </div>
          <h3 class="text-lg font-semibold text-gray-800">Paete Branch</h3>
        </div>
        <span class="px-3 py-1 text-xs font-medium bg-slate-100 text-slate-700 rounded-full">Secondary Branch</span>
      </div>
    </div>
    
    <!-- Card content -->
    <div class="p-6">
      <div class="grid grid-cols-2 gap-6">
        <!-- Revenue -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center mb-1 text-gray-600 text-sm">
            <i class="fas fa-chart-line mr-2"></i>
            <span>Revenue</span>
          </div>
          <div class="text-xl font-bold text-gray-800">₱<?php echo number_format($paeteMetrics['revenue'], 2); ?></div>
          <div class="mt-2 text-xs text-gray-500">
  Current month
</div>
        </div>
        
        <!-- Profit -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center mb-1 text-gray-600 text-sm">
            <i class="fas fa-wallet mr-2"></i>
            <span>Profit</span>
          </div>
          <div class="text-xl font-bold text-gray-800">₱<?php echo number_format($paeteMetrics['profit'], 2); ?></div>
          <div class="mt-2 text-xs text-gray-500">
  Current month
</div>
        </div>
        
        <!-- Margin -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center mb-1 text-gray-600 text-sm">
            <i class="fas fa-percentage mr-2"></i>
            <span>Margin</span>
          </div>
          <div class="text-xl font-bold text-gray-800"><?php echo number_format($paeteMetrics['margin'], 1); ?>%</div>
          <div class="mt-2 text-xs text-gray-500">
  Current month
</div>
        </div>
        
        <!-- Customers -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center mb-1 text-gray-600 text-sm">
            <i class="fas fa-users mr-2"></i>
            <span>Customers</span>
          </div>
          <div class="text-xl font-bold text-gray-800"><?php echo $paeteMetrics['customers']; ?></div>
          <div class="mt-2 text-xs text-gray-500">
  Current month
</div>
        </div>
      </div>
    </div>
  </div>
</div>


  <!-- Charts -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-6 md:mb-8">
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 w-full">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 p-4 sm:p-5 border-b border-sidebar-border">
      <div class="flex items-center justify-between w-full">
        <h3 class="font-medium text-sidebar-text">Accrued Revenue</h3>
        <div class="flex items-center gap-2">
          <div class="flex items-center bg-gray-100 rounded-full p-1">
            <button id="monthlyView" class="px-3 py-1 rounded-full text-sm font-medium bg-blue-500 text-white">
              Monthly
            </button>
            <button id="yearlyView" class="px-3 py-1 rounded-full text-sm font-medium text-gray-600 hover:text-gray-800">
              Yearly
            </button>
          </div>
          <button id="exportProjectedIncome" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
              <polyline points="7 10 12 15 17 10"></polyline>
              <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            Export
          </button>
        </div>
      </div>
    </div>
    <div class="p-4 sm:p-5">
      <div class="w-full h-48 md:h-64">
        <div id="projectedIncomeChart" style="width: 100%; height: 100%;"></div>
      </div>
    </div>
  </div>
  
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 w-full">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 p-4 sm:p-5 border-b border-sidebar-border">
      <div class="flex items-center justify-between w-full">
        <h3 class="font-medium text-sidebar-text">Cash Revenue</h3>
        <div class="flex items-center gap-2">
          <div class="flex items-center bg-gray-100 rounded-full p-1">
            <button id="cashMonthlyView" class="px-3 py-1 rounded-full text-sm font-medium <?php echo (!isset($_GET['view']) || $_GET['view'] !== 'yearly') ? 'bg-[#4ade80] text-white' : 'text-gray-600 hover:text-gray-800' ?>">
              Monthly
            </button>
            <button id="cashYearlyView" class="px-3 py-1 rounded-full text-sm font-medium <?php echo (isset($_GET['view']) && $_GET['view'] === 'yearly') ? 'bg-[#4ade80] text-white' : 'text-gray-600 hover:text-gray-800' ?>">
              Yearly
            </button>
          </div>
          <button id="exportCashRevenue" class="bg-[#4ade80] hover:bg-[#3bc973] text-white px-3 py-1 rounded text-sm flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
              <polyline points="7 10 12 15 17 10"></polyline>
              <line x1="12" y1="15" x2="12" y2="3"></line>
            </svg>
            Export
          </button>
        </div>
      </div>
    </div>
    <div class="p-4 sm:p-5">
      <div class="w-full h-48 md:h-64">
        <div id="revenueChart" style="width: 100%; height: 100%;"></div>
      </div>
    </div>
  </div>
</div>

<!-- Branch Comparison Charts -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-6 md:mb-8">
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 w-full">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 p-4 sm:p-5 border-b border-sidebar-border">
      <h3 class="font-medium text-sidebar-text">Revenue by Branch</h3>
      
    </div>
    <div class="p-4 sm:p-5">
    <div class="w-full" style="min-height: 300px; height: 60vh; max-height: 500px;">
        <div id="branchRevenueChart" style="width: 100%; height: 100%;"></div>
      </div>
    </div>
  </div>
  
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 w-full">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 p-4 sm:p-5 border-b border-sidebar-border">
      <h3 class="font-medium text-sidebar-text">Top Selling Packages</h3>
      
    </div>
    <div class="p-4 sm:p-5">
      <div class="w-full" style="min-height: 300px; height: 60vh; max-height: 500px;">
        <div id="branchServicesChart" style="width: 100%; height: 100%;"></div>
      </div>
    </div>
  </div>
</div>
  
  <!-- Branch Statistics -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
    <!-- Header Section - Made responsive with better stacking -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
        <!-- Desktop layout for big screens - Title on left, controls on right -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Title and Counter -->
            <div class="flex items-center gap-3 mb-4 lg:mb-0">
                <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Branch Performance</h4>
            </div>
</div>
</div>

    
    <!-- Responsive Table Container with improved spacing -->
    <div class="overflow-x-auto scrollbar-thin" id="branchTableContainer">
        <div id="branchLoadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
        </div>
        
        <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
        <div class="min-w-full">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-sidebar-border">
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(0)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-building text-sidebar-accent"></i> Branch
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(1)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-clipboard-list text-sidebar-accent"></i> Services
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(2)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-dollar-sign text-sidebar-accent"></i> Revenue
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(3)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-credit-card text-sidebar-accent"></i> Expenses
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(3)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-coins text-sidebar-accent"></i> Capital
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(4)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-chart-line text-sidebar-accent"></i> Profit
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(5)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-percentage text-sidebar-accent"></i> Growth
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="branchTableBody">
                <?php
                // Fetch branch performance data
                $visible = "visible";
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
                            (COALESCE(s.revenue, 0) + COALESCE(c.custom_revenue, 0) - (COALESCE(s.capital_total, 0) + COALESCE(e.expenses, 0))) / (COALESCE(s.revenue, 0) + COALESCE(c.custom_revenue, 0)) * 100
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
                        COUNT(DISTINCT customsales_id) AS custom_service_count,
                        SUM(amount_paid) AS custom_revenue
                    FROM 
                        customsales_tb
                    WHERE 
                        MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?
                    GROUP BY 
                        branch_id
                ) c ON b.branch_id = c.branch_id
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
                    b.branch_id IN (1, 2)
                ORDER BY 
                    b.branch_name ASC";
                
                $stmt = $conn->prepare($branchQuery);
                $stmt->bind_param("iiiiiis", $currentMonth, $currentYear, $currentMonth, $currentYear, $currentMonth, $currentYear, $visible);
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
                        
                        // Alternatively, you could use margin for styling:
                        // $marginClass = $margin >= 0 ? 'bg-green-100 text-green-600 border-green-200' : 'bg-red-100 text-red-600 border-red-200';
                        // $marginIcon = $margin >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
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
                    ?>
                    <tr>
                        <td colspan="7" class="px-4 py-3.5 text-sm text-center text-sidebar-text">No branch data available for the current month.</td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
            </table>
        </div>
    </div>
    
    <!-- Sticky Pagination Footer with improved spacing -->
    <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="branchPaginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
    Showing 1 - 2 of 2 branches
</div>
        <div class="flex space-x-2">
            <a href="<?php echo '?page=' . max(1, $page - 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 pointer-events-none">&laquo;</a>
            
            <a href="<?php echo '?page=1'; ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm bg-sidebar-accent text-white">
                1
            </a>
            
            <a href="<?php echo '?page=' . min($totalPages, $page + 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 pointer-events-none">&raquo;</a>
        </div>
    </div>
</div>


  
  

  <!-- Recent Inventory Activity -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
  <!-- Header Section - Made responsive with better stacking -->
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
    <!-- Desktop layout for big screens - Title on left, controls on right -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
      <!-- Title and Counter -->
      <div class="flex items-center gap-3 mb-4 lg:mb-0">
        <h3 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Recent Inventory Activity</h3>
        
        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
          18
        </span>
      </div>
      
      <!-- Controls for big screens - aligned right -->
      <div class="hidden lg:flex items-center gap-3">

        <!-- Manage Inventory Button -->
        <a href="inventory_management.php">
          <button class="px-4 py-2 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap">
            <i class="fas fa-box"></i> <span>Manage Inventory</span>
          </button>
        </a>
      </div>
    </div>
    
    <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
    <div class="lg:hidden w-full mt-4">

      <!-- Second row: Manage Inventory Button - Full width -->
      <div class="w-full">
        <a href="inventory_management.php" class="w-full block">
          <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap w-full justify-center">
            <i class="fas fa-box"></i> <span>Manage Inventory</span>
          </button>
        </a>
      </div>
    </div>
  </div>
  
  <!-- Responsive Table Container with improved spacing -->
  <div class="overflow-x-auto scrollbar-thin" id="inventoryTableContainer">
    <div id="inventoryLoadingIndicator" class="absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
      <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
    </div>
    
    <!-- Responsive Table -->
    <div class="min-w-full">
      <table class="w-full">
        <thead>
          <tr class="bg-gray-50 border-b border-sidebar-border">
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(0)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-box text-sidebar-accent"></i> Item
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(1)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-barcode text-sidebar-accent"></i> SKU
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(2)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-chart-line text-sidebar-accent"></i> Activity
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(3)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-calendar-alt text-sidebar-accent"></i> Date
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(4)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-cubes text-sidebar-accent"></i> Quantity
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(5)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-building text-sidebar-accent"></i> Branch
              </div>
            </th>
          </tr>
        </thead>
        <tbody id="inventoryLogsBody">
          <!-- Data will be loaded here via JavaScript -->
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Pagination Footer -->
  <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="inventoryPaginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
    </div>
    <div class="flex space-x-2" id="paginationControls">
      <button class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 cursor-not-allowed" disabled>&laquo;</button>
      <button class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm bg-sidebar-accent text-white">1</button>
      <button class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&raquo;</button>
    </div>
  </div>

</div>
            
  <!-- Footer -->
  <footer class="bg-white rounded-lg shadow-sidebar border border-sidebar-border p-4 text-center text-sm text-gray-500 mt-8">
    <p>© 2025 GrievEase.</p>
  </footer>
</div>

<script src="tailwind.js"></script>
<script src="script.js"></script>

<script>
  document.querySelectorAll('.branch-name').forEach(div => {
    div.textContent = div.textContent
      .toLowerCase()
      .replace(/\b\w/g, char => char.toUpperCase()); // Title Case
  });
</script>

<script>
// Function to load inventory logs with your exact query structure
function loadInventoryLogs(page = 1) {
    const loadingIndicator = document.getElementById('inventoryLoadingIndicator');
    const tableBody = document.getElementById('inventoryLogsBody');
    const paginationInfo = document.getElementById('inventoryPaginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    loadingIndicator.classList.remove('hidden');
    tableBody.innerHTML = '';
    
    fetch(`dashboard/fetch_inventory_logs.php?page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear any existing rows
                tableBody.innerHTML = '';
                
                // Populate table with logs
                data.logs.forEach(log => {
                    const branchName = log.branch_name
                    ? log.branch_name.charAt(0).toUpperCase() + log.branch_name.slice(1)
                    : 'N/A';
                    const row = document.createElement('tr');
                    row.className = 'border-b border-sidebar-border hover:bg-sidebar-hover transition-colors';
                    
                    // Determine badge styling
                    const badgeStyles = {
                        'Depleted': 'bg-red-100 text-red-600 border-red-200',
                        'Low Stock': 'bg-yellow-100 text-yellow-600 border-yellow-200',
                        'Restocked': 'bg-green-100 text-green-600 border-green-200',
                        'Added': 'bg-green-100 text-green-600 border-green-200',
                        'Removed': 'bg-orange-100 text-orange-600 border-orange-200',
                        'Adjusted': 'bg-blue-100 text-blue-600 border-blue-200'
                    };
                    
                    const badgeClass = badgeStyles[log.activity_type] || 'bg-gray-100 text-gray-600 border-gray-200';
                    const badgeIcon = getActivityIcon(log.activity_type);
                    
                    // Format date
                    const activityDate = new Date(log.activity_date);
                    const formattedDate = activityDate.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    // Format quantity
                    const quantityDisplay = formatQuantityChange(log.quantity_change, log.old_quantity, log.new_quantity);

                    row.innerHTML = `
                        <td class="px-4 py-3.5 text-sm text-sidebar-text">${log.item_name}</td>
                        <td class="px-4 py-3.5 text-sm text-sidebar-text">
                            <span class="inline-block bg-gray-100 rounded-full px-2 py-1 text-xs font-medium">
                                ID: ${log.inventory_id}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeClass} border">
                                <i class="fas ${badgeIcon} mr-1"></i> ${log.activity_type}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-sm text-sidebar-text">${formattedDate}</td>
                        <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">
                            ${quantityDisplay}
                        </td>
                        <td class="px-4 py-3.5 text-sm text-sidebar-text">${branchName || 'N/A'}</td>
                    `;
                    tableBody.appendChild(row);
                });
                
                // Update pagination info
                updatePaginationInfo(paginationInfo, page, data.perPage, data.total);
                
                // Update pagination controls
                updatePaginationControls(paginationControls, page, data.perPage, data.total);
                
            } else {
                showError(tableBody, data.error);
            }
        })
        .catch(error => {
            showError(tableBody, error.message);
        })
        .finally(() => {
            loadingIndicator.classList.add('hidden');
        });
}

// Helper function to get appropriate icon for activity type
function getActivityIcon(activityType) {
    const icons = {
        'Depleted': 'fa-exclamation-circle',
        'Low Stock': 'fa-exclamation-triangle',
        'Restocked': 'fa-boxes',
        'Added': 'fa-plus-circle',
        'Removed': 'fa-minus-circle',
        'Adjusted': 'fa-adjust'
    };
    return icons[activityType] || 'fa-info-circle';
}

// Helper function to format quantity display
function formatQuantityChange(change, oldQty, newQty) {
    const changeSymbol = change > 0 ? '+' : '';
    return `
        <div class="flex flex-col">
            <span class="font-medium ${change > 0 ? 'text-green-600' : change < 0 ? 'text-red-600' : 'text-gray-600'}">
                ${changeSymbol}${change}
            </span>
            <span class="text-xs text-gray-500 mt-1">
                ${oldQty} → ${newQty}
            </span>
        </div>
    `;
}

// Helper function to update pagination info
function updatePaginationInfo(element, currentPage, perPage, totalItems) {
    const startItem = (currentPage - 1) * perPage + 1;
    const endItem = Math.min(currentPage * perPage, totalItems);
    element.innerHTML = `
        Showing <span class="font-medium">${startItem}-${endItem}</span> of 
        <span class="font-medium">${totalItems}</span> activities
    `;
}

// Helper function to update pagination controls
function updatePaginationControls(container, currentPage, perPage, totalItems) {
    const totalPages = Math.ceil(totalItems / perPage);
    
    let html = `
        <button class="px-3.5 py-1.5 border rounded text-sm ${
            currentPage <= 1 ? 'border-gray-300 text-gray-400 cursor-not-allowed' : 'border-sidebar-border hover:bg-sidebar-hover'
        }" ${currentPage <= 1 ? 'disabled' : ''} onclick="loadInventoryLogs(${currentPage - 1})">
            &laquo;
        </button>
    `;
    
    // Show page numbers
    for (let i = 1; i <= totalPages; i++) {
        html += `
            <button class="px-3.5 py-1.5 border rounded text-sm ${
                i === currentPage ? 'bg-sidebar-accent text-white border-sidebar-accent' : 'border-sidebar-border hover:bg-sidebar-hover'
            }" onclick="loadInventoryLogs(${i})">
                ${i}
            </button>
        `;
    }
    
    html += `
        <button class="px-3.5 py-1.5 border rounded text-sm ${
            currentPage >= totalPages ? 'border-gray-300 text-gray-400 cursor-not-allowed' : 'border-sidebar-border hover:bg-sidebar-hover'
        }" ${currentPage >= totalPages ? 'disabled' : ''} onclick="loadInventoryLogs(${currentPage + 1})">
            &raquo;
        </button>
    `;
    
    container.innerHTML = html;
}

// Helper function to show error message
function showError(container, message) {
    container.innerHTML = `
        <tr>
            <td colspan="7" class="px-4 py-3.5 text-sm text-center text-red-600">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Error loading data: ${escapeHtml(message)}
            </td>
        </tr>
    `;
}

// Helper function to escape HTML
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

// View log details
function viewLogDetails(logId) {
    // Implement your detail view logic here
    console.log('Viewing details for log ID:', logId);
    // Example: window.location.href = `inventory_log_details.php?id=${logId}`;
}

// Initial load
document.addEventListener('DOMContentLoaded', () => {
    loadInventoryLogs();
});
</script>

<script>
var options = {
  series: [{
    name: "Revenue",
    data: <?php echo isset($_GET['view']) && $_GET['view'] === 'yearly' ? json_encode($yearlyRevenueData) : json_encode($monthlyRevenueData); ?>
  }],
  chart: {
    type: 'area',
    height: '100%',
    width: '100%',
    animations: {
      enabled: true,
      easing: 'easeout',
      speed: 800
    },
    toolbar: {
      show: true,
      tools: {
        download: false,
        selection: true,
        zoom: true,
        zoomin: true,
        zoomout: true,
        pan: true,
        reset: true
      }
    }
  },
  colors: ['#4ade80'],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth',
    width: 2
  },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.7,
      opacityTo: 0.3,
    }
  },
  xaxis: {
    categories: <?php echo isset($_GET['view']) && $_GET['view'] === 'yearly' ? json_encode($yearLabels) : json_encode($monthLabels); ?>,
  },
  yaxis: {
    labels: {
      formatter: function(val) {
        return "₱" + val.toLocaleString()
      }
    }
  },
  tooltip: {
    y: {
      formatter: function(val) {
        return "₱" + val.toLocaleString()
      }
    }
  }
};

var chart = new ApexCharts(document.querySelector("#revenueChart"), options);
chart.render();

// Toggle between monthly and yearly views
document.getElementById('cashMonthlyView').addEventListener('click', function() {
    window.location.href = window.location.pathname + '?view=monthly';
});

document.getElementById('cashYearlyView').addEventListener('click', function() {
    window.location.href = window.location.pathname + '?view=yearly';
});

// Export PDF function
document.getElementById('exportCashRevenue').addEventListener('click', function() {
    // Get the data based on current view
    const isYearly = <?php echo (isset($_GET['view']) && $_GET['view'] === 'yearly') ? 'true' : 'false'; ?>;
    const categories = [...<?php echo isset($_GET['view']) && $_GET['view'] === 'yearly' ? json_encode($yearLabels) : json_encode($monthLabels); ?>];
    const revenues = [...<?php echo isset($_GET['view']) && $_GET['view'] === 'yearly' ? json_encode($yearlyRevenueData) : json_encode($monthlyRevenueData); ?>];
    
    // Format the data for the table
    const tableData = [[isYearly ? 'Year' : 'Month', 'Revenue (PHP)']];

    categories.forEach((category, index) => {
        const cleanCategory = category.toString()
            .replace(/±/g, '')
            .replace(/[^a-zA-Z0-9ñÑ\s]/g, '')
            .trim() || (isYearly ? `Year ${index + 1}` : `Month ${index + 1}`);

        let value = Number(revenues[index]);
        if (isNaN(value)) value = 0;

        tableData.push([
            cleanCategory,
            'PHP ' + value.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            })
        ]);
    });

    // Calculate total
    const total = revenues.reduce((sum, val) => sum + (Number(val) || 0), 0);
    tableData.push([
        'TOTAL',
        'PHP ' + total.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        })
    ]);

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({
        orientation: 'portrait'
    });

    // Set document metadata
    doc.setProperties({
        title: 'Vjay Relova Cash Revenue Report',
        subject: 'Financial Report',
        author: 'Vjay Relova Funeral Services',
        keywords: 'revenue, report, financial',
        creator: 'Vjay Relova Web Application'
    });

    // Add header
    doc.setFontSize(20);
    doc.setFont('helvetica', 'bold');
    doc.setTextColor(33, 37, 41);
    doc.text('VJAY RELOVA FUNERAL SERVICES', 105, 20, { align: 'center' });

    doc.setFontSize(16);
    doc.text('CASH REVENUE REPORT - ' + (isYearly ? 'YEARLY' : 'MONTHLY'), 105, 30, { align: 'center' });

    doc.setFontSize(10);
    doc.setTextColor(100, 100, 100);
    doc.text('Generated on: ' + new Date().toLocaleString('en-PH', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }), 105, 36, { align: 'center' });

    // Calculate page width and column widths
    const pageWidth = doc.internal.pageSize.width;
    const margin = 15;
    const availableWidth = pageWidth - (2 * margin);
    
    // Create table with adjusted settings
    doc.autoTable({
        head: [tableData[0]],
        body: tableData.slice(1, -1),
        startY: 45,
        theme: 'grid',
        headStyles: {
            fillColor: [74, 222, 128],
            textColor: 255,
            fontStyle: 'bold',
            fontSize: 11,
            halign: 'center'
        },
        columnStyles: {
            0: { 
                cellWidth: availableWidth * 0.4,
                halign: 'left',
                fontStyle: 'bold'
            },
            1: { 
                cellWidth: availableWidth * 0.6,
                halign: 'right',
                minCellHeight: 10
            }
        },
        styles: {
            fontSize: 9,
            cellPadding: 3,
            overflow: 'linebreak',
            valign: 'middle',
            lineWidth: 0.1
        },
        margin: { 
            left: margin,
            right: margin,
            top: 45
        },
        tableWidth: 'wrap',
        didDrawPage: function (data) {
            if (data.pageCount === data.pageNumber) {
                const finalY = data.cursor.y + 10;
                doc.setFontSize(12);
                doc.setFont('courier', 'bold');
                
                // Draw total line with full width
                doc.setFillColor(240, 240, 240);
                doc.rect(margin, finalY - 5, availableWidth, 10, 'F');
                
                doc.text(tableData[tableData.length - 1][0], margin + 5, finalY);
                doc.text(tableData[tableData.length - 1][1], pageWidth - margin - 5, finalY, { align: 'right' });

                const footerY = doc.internal.pageSize.height - 10;
                doc.setFontSize(9);
                doc.setTextColor(100, 100, 100);
                doc.setFont('courier', 'normal');
                doc.text('For inquiries: Tel: (02) 1234-5678 • Mobile: 0917-123-4567 • Email: info@vjayrelova.com',
                    105, footerY, { align: 'center' });
            }
        }
    });

    doc.save(`Vjay-Relova-Cash-Revenue-Report-${isYearly ? 'Yearly' : 'Monthly'}-${new Date().toISOString().slice(0, 10)}.pdf`);
});
</script>
<script>
// Initialize chart with monthly data
var projectedIncomeOptions = {
  series: [{
    name: "Projected Income",
    data: <?php echo json_encode($monthlyProjectedIncomeData); ?>
  }],
  chart: {
    type: 'area',
    height: '100%',
    width: '100%',
    animations: {
      enabled: true,
      easing: 'easeout',
      speed: 800
    },
    toolbar: {
      show: true,
      tools: {
        download: false,
        selection: true,
        zoom: true,
        zoomin: true,
        zoomout: true,
        pan: true,
        reset: true
      }
    }
  },
  colors: ['#3b82f6'],
  dataLabels: {
    enabled: false
  },
  stroke: {
    curve: 'smooth',
    width: 2
  },
  fill: {
    type: 'gradient',
    gradient: {
      shadeIntensity: 1,
      opacityFrom: 0.7,
      opacityTo: 0.3,
    }
  },
  xaxis: {
    categories: <?php echo json_encode($monthLabels); ?>,
  },
  yaxis: {
    title: {
      text: 'Projected Income (₱)'
    },
    labels: {
      formatter: function(val) {
        return "₱" + val.toLocaleString()
      }
    }
  },
  tooltip: {
    y: {
      formatter: function(val) {
        return "₱" + val.toLocaleString()
      }
    }
  },
  title: {
    text: '(If all payments have been settled)',
    align: 'left',
    style: {
      fontSize: '14px',
      fontWeight: 'bold',
      color: '#333'
    }
  }
};

var projectedIncomeChart = new ApexCharts(document.querySelector("#projectedIncomeChart"), projectedIncomeOptions);
projectedIncomeChart.render();

// Toggle between monthly and yearly views
document.getElementById('monthlyView').addEventListener('click', function() {
  this.classList.add('bg-blue-500', 'text-white');
  this.classList.remove('text-gray-600');
  document.getElementById('yearlyView').classList.remove('bg-blue-500', 'text-white');
  document.getElementById('yearlyView').classList.add('text-gray-600');
  
  projectedIncomeChart.updateOptions({
    series: [{
      data: <?php echo json_encode($monthlyProjectedIncomeData); ?>
    }],
    xaxis: {
      categories: <?php echo json_encode($monthLabels); ?>
    }
  });
});

document.getElementById('yearlyView').addEventListener('click', function() {
  this.classList.add('bg-blue-500', 'text-white');
  this.classList.remove('text-gray-600');
  document.getElementById('monthlyView').classList.remove('bg-blue-500', 'text-white');
  document.getElementById('monthlyView').classList.add('text-gray-600');
  
  projectedIncomeChart.updateOptions({
    series: [{
      data: <?php echo json_encode($yearlyProjectedIncomeData); ?>
    }],
    xaxis: {
      categories: <?php echo json_encode($yearLabels); ?>
    }
  });
});

// Update export functionality to handle both views
document.getElementById('exportProjectedIncome').addEventListener('click', function () {
  const isMonthly = document.getElementById('monthlyView').classList.contains('bg-blue-500');
  const seriesData = isMonthly ? [...<?php echo json_encode($monthlyProjectedIncomeData); ?>] : [...<?php echo json_encode($yearlyProjectedIncomeData); ?>];
  const categories = isMonthly ? [...<?php echo json_encode($monthLabels); ?>] : [...<?php echo json_encode($yearLabels); ?>];
  
  // Reverse the order of both arrays to show latest first
  seriesData.reverse();
  categories.reverse();

  const tableData = [[isMonthly ? 'Month' : 'Year', 'Accrued Revenue (PHP)']];

  categories.forEach((period, index) => {
    const cleanPeriod = period.toString()
      .replace(/±/g, '')
      .replace(/[^a-zA-Z0-9ñÑ\s]/g, '')
      .trim() || (isMonthly ? `Month ${index + 1}` : `Year ${index + 1}`);

    let value = Number(seriesData[index]);
    if (isNaN(value)) value = 0;

    tableData.push([
      cleanPeriod,
      'PHP ' + value.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })
    ]);
  });


  const total = seriesData.reduce((sum, val) => sum + (Number(val) || 0), 0);
  tableData.push([
    'TOTAL',
    'PHP ' + total.toLocaleString('en-PH', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    })
  ]);

  const { jsPDF } = window.jspdf;
  const doc = new jsPDF({
    orientation: 'portrait'
  });

  // Set document metadata
  doc.setProperties({
    title: 'Vjay Relova Accrued Revenue Report',
    subject: 'Financial Report',
    author: 'Vjay Relova Funeral Services',
    keywords: 'revenue, report, financial',
    creator: 'Vjay Relova Web Application'
  });

  // Add header
  doc.setFontSize(20);
  doc.setFont('helvetica', 'bold');
  doc.setTextColor(33, 37, 41);
  doc.text('VJAY RELOVA FUNERAL SERVICES', 105, 20, { align: 'center' });

  doc.setFontSize(16);
  doc.text('ACCRUED REVENUE REPORT', 105, 30, { align: 'center' });

  doc.setFontSize(10);
  doc.setTextColor(100, 100, 100);
  doc.text('Generated on: ' + new Date().toLocaleString('en-PH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }), 105, 36, { align: 'center' });

  // Calculate page width and column widths
  const pageWidth = doc.internal.pageSize.width;
  const margin = 15;
  const availableWidth = pageWidth - (2 * margin);
  
  // Create table with adjusted settings
  doc.autoTable({
    head: [tableData[0]],
    body: tableData.slice(1, -1),
    startY: 45,
    theme: 'grid',
    headStyles: {
      fillColor: [59, 130, 246],
      textColor: 255,
      fontStyle: 'bold',
      fontSize: 11,
      halign: 'center'
    },
    columnStyles: {
      0: { 
        cellWidth: availableWidth * 0.4,  // Increased width for Month column
        halign: 'left',
        fontStyle: 'bold'
      },
      1: { 
        cellWidth: availableWidth * 0.6,  // Adjusted width for Amount column
        halign: 'right',
        minCellHeight: 10  // Ensure enough height for values
      }
    },
    styles: {
      fontSize: 9,  // Slightly smaller font to prevent overlap
      cellPadding: 3,  // Reduced padding
      overflow: 'linebreak',
      valign: 'middle',
      lineWidth: 0.1  // Thinner grid lines
    },
    margin: { 
      left: margin,
      right: margin,
      top: 45
    },
    tableWidth: 'wrap',  // Let the table adjust its width
    didDrawPage: function (data) {
      if (data.pageCount === data.pageNumber) {
        const finalY = data.cursor.y + 10;
        doc.setFontSize(12);
        doc.setFont('courier', 'bold');
        
        // Draw total line with full width
        doc.setFillColor(240, 240, 240);
        doc.rect(margin, finalY - 5, availableWidth, 10, 'F');
        
        doc.text(tableData[tableData.length - 1][0], margin + 5, finalY);
        doc.text(tableData[tableData.length - 1][1], pageWidth - margin - 5, finalY, { align: 'right' });

        const footerY = doc.internal.pageSize.height - 10;
        doc.setFontSize(9);
        doc.setTextColor(100, 100, 100);
        doc.setFont('courier', 'normal');
        doc.text('For inquiries: Tel: (02) 1234-5678 • Mobile: 0917-123-4567 • Email: info@vjayrelova.com',
          105, footerY, { align: 'center' });
      }
    }
  });

  doc.save(`Vjay-Relova-Income-Report-${new Date().toISOString().slice(0, 10)}.pdf`);
});
</script>
<script>
// Get the revenue data for both branches
var pilaRevenue = <?php echo $pilaMetrics['revenue']; ?>;
var paeteRevenue = <?php echo $paeteMetrics['revenue']; ?>;

var branchRevenueOptions = {
  series: [
    {
      name: 'Pila Branch',
      data: <?php echo json_encode($pilaMonthlyRevenue); ?>
    },
    {
      name: 'Paete Branch',
      data: <?php echo json_encode($paeteMonthlyRevenue); ?>
    }
  ],
  chart: {
    type: 'bar',
    height: '100%',
    width: '100%',
    stacked: false, // Set to true if you want stacked bars
    toolbar: {
      show: true, // This enables the toolbar with zoom/export options
      tools: {
        download: true, // Show download options
        selection: true,
        zoom: true,
        zoomin: true,
        zoomout: true,
        pan: true,
        reset: true
      },
      export: {
        csv: {
          filename: 'branch-revenue-comparison',
          columnDelimiter: ',',
          headerCategory: 'Month',
          headerValue: 'Revenue (₱)',
        },
        png: {
          filename: 'branch-revenue-comparison',
        },
        svg: {
          filename: 'branch-revenue-comparison',
        }
      }
    }
  },
  plotOptions: {
    bar: {
      horizontal: false,
      columnWidth: '80%',
      endingShape: 'rounded',
      borderRadius: 4
    },
  },
  dataLabels: {
    enabled: false
  },
  colors: ['#4f46e5', '#10b981'], // Different colors for each branch
  stroke: {
    show: true,
    width: 2,
    colors: ['transparent']
  },
  xaxis: {
    categories: <?php echo json_encode($monthLabels); ?>,
    labels: {
      style: {
        fontSize: '12px',
        fontWeight: 500
      },
      rotate: -45, // Rotate labels if they're too long
      hideOverlappingLabels: true
    }
  },
  yaxis: {
    title: {
      text: 'Revenue (₱)'
    },
    labels: {
      formatter: function(val) {
        return "₱" + val.toLocaleString();
      }
    }
  },
  fill: {
    opacity: 1
  },
  tooltip: {
    y: {
      formatter: function(val) {
        return "₱" + val.toLocaleString();
      }
    }
  },
  legend: {
    position: 'top',
    horizontalAlign: 'center',
    offsetY: 0,
    markers: {
      width: 12,
      height: 12,
      radius: 12,
    }
  },
  responsive: [{
    breakpoint: 768,
    options: {
      chart: {
        height: 400
      },
      xaxis: {
        labels: {
          rotate: -45
        }
      }
    }
  }]
};

var branchRevenueChart = new ApexCharts(document.querySelector("#branchRevenueChart"), branchRevenueOptions);
branchRevenueChart.render();
</script>
<script>
  var branchServicesOptions = {
    series: [
        {
            name: 'Pila Branch',
            data: <?php echo json_encode($pilaData); ?>
        },
        {
            name: 'Paete Branch',
            data: <?php echo json_encode($paeteData); ?>
        }
    ],
    chart: {
        height: '100%',
        width: '100%',
        type: 'radar',
        dropShadow: {
            enabled: true,
            blur: 1,
            left: 1,
            top: 1
        },
        toolbar: {
            show: true,
            tools: {
                download: true,
                selection: false,
                zoom: false,
                zoomin: false,
                zoomout: false,
                pan: false,
                reset: true
            },
            export: {
                csv: {
                    filename: 'branch-services-comparison',
                    headerCategory: 'Service',
                    headerValue: 'Sales Count',
                },
                png: {
                    filename: 'branch-services-comparison',
                },
                svg: {
                    filename: 'branch-services-comparison',
                }
            }
        }
    },
    colors: ['#4f46e5', '#10b981'],
    labels: <?php echo json_encode($services); ?>,
    markers: {
        size: 5,
        hover: {
            size: 7
        }
    },
    yaxis: {
        show: false,
        min: 0
    },
    fill: {
        opacity: 0.2
    },
    stroke: {
        width: 2
    },
    tooltip: {
        y: {
            formatter: function(val) {
                return val + ' sales';
            }
        }
    },
    legend: {
        position: 'bottom',
        horizontalAlign: 'center'
    },
    plotOptions: {
    radar: {
      size: 140, // Adjust based on screen size
      polygons: {
        strokeColors: '#e8e8e8',
        connectorColors: '#e8e8e8'
      }
    }
  },
  responsive: [{
    breakpoint: 1200,
    options: {
      plotOptions: {
        radar: {
          size: 160
        }
      }
    }
  },
  {
    breakpoint: 992,
    options: {
      plotOptions: {
        radar: {
          size: 140
        }
      }
    }
  },
  {
    breakpoint: 768,
    options: {
      plotOptions: {
        radar: {
          size: 120
        }
      },
      legend: {
        position: 'bottom',
        horizontalAlign: 'center'
      }
    }
  }]
};

var branchServicesChart = new ApexCharts(document.querySelector("#branchServicesChart"), branchServicesOptions);
branchServicesChart.render();
</script>

<script>
// Improved notification bell functionality
document.getElementById('notification-bell').addEventListener('click', function(event) {
  event.stopPropagation();
  const dropdown = document.getElementById('notifications-dropdown');
  
  if (dropdown.classList.contains('hidden')) {
    // Show dropdown with animation
    dropdown.classList.remove('hidden');
    setTimeout(() => {
      dropdown.classList.remove('opacity-0', 'translate-y-2');
    }, 10);
  } else {
    // Hide dropdown with animation
    dropdown.classList.add('opacity-0', 'translate-y-2');
    setTimeout(() => {
      dropdown.classList.add('hidden');
    }, 300);
  }
  
  // If showing notifications, mark as read (update counter)
  if (!dropdown.classList.contains('hidden')) {
    const notificationCounter = document.querySelector('#notification-bell > span');
    
    // Add a slight delay before animating the counter
    setTimeout(() => {
      notificationCounter.classList.add('scale-75', 'opacity-50');
      
      setTimeout(() => {
        notificationCounter.textContent = '0';
        notificationCounter.classList.add('scale-0');
        
        setTimeout(() => {
          if (notificationCounter.textContent === '0') {
            notificationCounter.classList.add('hidden');
          }
        }, 300);
      }, 500);
    }, 2000);
  }
});

// Close notification dropdown when clicking outside
document.addEventListener('click', function(event) {
  const notificationsDropdown = document.getElementById('notifications-dropdown');
  const notificationBell = document.getElementById('notification-bell');
  
  if (notificationsDropdown && notificationBell && !notificationBell.contains(event.target) && !notificationsDropdown.contains(event.target)) {
    // Hide with animation
    notificationsDropdown.classList.add('opacity-0', 'translate-y-2');
    setTimeout(() => {
      notificationsDropdown.classList.add('hidden');
    }, 300);
  }
});

// Add smooth transitions for notification counter badge
document.querySelector('#notification-bell > span').classList.add('transition-all', 'duration-300');

// Add animation to new notifications - subtle pulse effect
document.addEventListener('DOMContentLoaded', function() {
  const unreadIndicators = document.querySelectorAll('#notifications-dropdown .bg-blue-600, #notifications-dropdown .bg-yellow-600, #notifications-dropdown .bg-green-600');
  
  unreadIndicators.forEach(indicator => {
    setInterval(() => {
      indicator.classList.add('scale-125', 'opacity-70');
      setTimeout(() => {
        indicator.classList.remove('scale-125', 'opacity-70');
      }, 500);
    }, 3000);
  });
});
</script>
</body>
</html>