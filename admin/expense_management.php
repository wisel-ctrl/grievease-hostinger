<?php

session_start();

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
?>
<?php
// Calculate total expenses
$totalExpenseQuery = "SELECT SUM(price) as total FROM expense_tb WHERE appearance = 'visible'";
$totalExpenseResult = $conn->query($totalExpenseQuery);
$totalExpense = $totalExpenseResult->fetch_assoc();
$totalAmount = number_format($totalExpense['total'] ?? 0, 2);

// Calculate percentage change from last month
$lastMonthQuery = "SELECT 
    SUM(CASE WHEN MONTH(date) = MONTH(CURRENT_DATE()) AND YEAR(date) = YEAR(CURRENT_DATE()) THEN price ELSE 0 END) as current_month,
    SUM(CASE WHEN MONTH(date) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(date) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH) THEN price ELSE 0 END) as last_month
    FROM expense_tb WHERE appearance = 'visible'";

$lastMonthResult = $conn->query($lastMonthQuery);
$monthData = $lastMonthResult->fetch_assoc();

$currentMonth = $monthData['current_month'] ?? 0;
$lastMonth = $monthData['last_month'] ?? 0;

if ($lastMonth > 0) {
    $percentageChange = (($currentMonth - $lastMonth) / $lastMonth) * 100;
    $trendIcon = $percentageChange >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
    $trendClass = $percentageChange >= 0 ? 'text-green-600' : 'text-red-600';
    $trendText = abs(round($percentageChange)) . '% from last month';
} else {
    $trendIcon = 'fa-equals';
    $trendClass = 'text-gray-600';
    $trendText = 'No previous data';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Expenses</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
 
</head>
<body class="flex bg-gray-50">

<?php include 'admin_sidebar.php'; ?>

<!-- Main Content -->
<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Expenses</h1>
    </div>
  </div>

  <!-- Expense Overview Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php
    // Database connection (assuming you already have this)
    require_once '../db_connect.php';

    // Calculate Total Expenses
    // (Assuming $totalAmount is already calculated from somewhere in your existing code)
    
    // Calculate Monthly Budget Status
    // Set your monthly budget (you can change this value)
    // Option 1: Single budget for all expenses
    $monthlyBudget = 50000; // Set your default monthly budget here in PHP

    // Option 2: Budget by category (uncomment if you want category-specific budgets)
    $categoryBudgets = [
        'Supplies' => 15000,
        'Utilities' => 50000,
        'Salaries' => 100000,
        'Maintenance' => 25000,
        'Other' => 50000
    ];
    $monthlyBudget = array_sum($categoryBudgets); // Total of all categories

    // Calculate current month's expenses
    $currentMonthQuery = "SELECT SUM(price) as total 
                        FROM expense_tb 
                        WHERE appearance = 'visible' 
                        AND MONTH(date) = MONTH(CURRENT_DATE()) 
                        AND YEAR(date) = YEAR(CURRENT_DATE())";
    $currentMonthResult = $conn->query($currentMonthQuery);
    $currentMonthRow = $currentMonthResult->fetch_assoc();
    $currentSpend = $currentMonthRow['total'] ?? 0;

    // Calculate days remaining in the month
    $today = new DateTime();
    $lastDayOfMonth = new DateTime('last day of this month');
    $daysLeft = $today->diff($lastDayOfMonth)->days;

    // Calculate percentage used (with protection against division by zero)
    $percentageUsed = ($monthlyBudget > 0) ? min(($currentSpend / $monthlyBudget) * 100, 100) : 0;

    // Determine warning colors
    $warningLevel = ($percentageUsed >= 90) ? 'text-red-600' : 
                  (($percentageUsed >= 75) ? 'text-yellow-600' : 'text-green-600');
    $daysLeftClass = ($daysLeft > 5) ? 'text-green-600' : 'text-red-600';
    
    // Calculate daily rate and projected spend
    $daysPassed = date('j'); // Current day of month
    $dailyRate = ($daysPassed > 0) ? $currentSpend / $daysPassed : 0;
    $projectedSpend = $dailyRate * date('t');
    $projectionClass = ($projectedSpend > $monthlyBudget) ? 'text-red-600' : 'text-green-600';
    
    // Calculate overdue payments
    $overdueQuery = "SELECT COUNT(*) as count, SUM(price) as total 
                    FROM expense_tb 
                    WHERE appearance = 'visible' 
                    AND status = 'To be paid' 
                    AND date < CURDATE()";
    $overdueResult = $conn->query($overdueQuery);
    $overdueData = $overdueResult->fetch_assoc();
    $overdueCount = $overdueData['count'] ?? 0;
    $overdueAmount = $overdueData['total'] ?? 0;

    // Get urgency level (how many days overdue)
    $urgencyQuery = "SELECT 
                    SUM(CASE WHEN DATEDIFF(CURDATE(), date) > 30 THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN DATEDIFF(CURDATE(), date) BETWEEN 15 AND 30 THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN DATEDIFF(CURDATE(), date) BETWEEN 7 AND 14 THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN DATEDIFF(CURDATE(), date) BETWEEN 1 AND 6 THEN 1 ELSE 0 END) as low
                    FROM expense_tb 
                    WHERE appearance = 'visible' 
                    AND status = 'To be paid' 
                    AND date < CURDATE()";
    $urgencyResult = $conn->query($urgencyQuery);
    $urgencyData = $urgencyResult->fetch_assoc();
    
    // Calculate upcoming payments
    $upcomingQuery = "SELECT COUNT(*) as count 
                    FROM expense_tb 
                    WHERE appearance = 'visible' 
                    AND status = 'To be paid' 
                    AND date > CURDATE()";
    $upcomingResult = $conn->query($upcomingQuery);
    $upcomingCount = $upcomingResult->fetch_assoc()['count'] ?? 0;

    // Count last month's upcoming payments for comparison
    $lastMonthUpcomingQuery = "SELECT COUNT(*) as count 
                              FROM expense_tb 
                              WHERE appearance = 'visible' 
                              AND status = 'To be paid' 
                              AND date BETWEEN DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND CURDATE()";
    $lastMonthUpcomingResult = $conn->query($lastMonthUpcomingQuery);
    $lastMonthUpcomingCount = $lastMonthUpcomingResult->fetch_assoc()['count'] ?? 0;

    // Calculate percentage change
    if ($lastMonthUpcomingCount > 0) {
        $percentageChange = (($upcomingCount - $lastMonthUpcomingCount) / $lastMonthUpcomingCount) * 100;
    } else {
        $percentageChange = $upcomingCount > 0 ? 100 : 0;
    }

    // Determine trend styling
    if ($percentageChange > 0) {
        $trendClass = 'text-green-600';
        $trendIcon = 'fa-arrow-up';
        $trendText = abs(round($percentageChange)) . '% from last month';
    } elseif ($percentageChange < 0) {
        $trendClass = 'text-red-600';
        $trendIcon = 'fa-arrow-down';
        $trendText = abs(round($percentageChange)) . '% from last month';
    } else {
        $trendClass = 'text-gray-600';
        $trendIcon = 'fa-equals';
        $trendText = 'No change from last month';
    }

    // Get nearest upcoming payment date
    $nearestQuery = "SELECT MIN(date) as nearest_date 
                    FROM expense_tb 
                    WHERE appearance = 'visible' 
                    AND status = 'To be paid' 
                    AND date > CURDATE()";
    $nearestResult = $conn->query($nearestQuery);
    $nearestDate = $nearestResult->fetch_assoc()['nearest_date'];
    
    // Card data array for consistent styling
    $cards = [
        [
            'title' => 'Total Expenses',
            'value' => $totalAmount,
            'change' => isset($trendText) ? $trendText : '0% from last month',
            'change_class' => isset($trendClass) ? $trendClass : 'text-gray-600',
            'change_icon' => isset($trendIcon) ? $trendIcon : 'fa-equals',
            'icon' => 'peso-sign',
            'color' => 'blue',
            'prefix' => '₱',
            'suffix' => '',
            'extra_content' => ''
        ],
        [
            'title' => 'Monthly Budget Status',
            'value' => number_format($currentSpend, 2),
            'sub_text' => 'of ₱' . number_format($monthlyBudget, 2) . ' budget',
            'icon' => 'wallet',
            'color' => 'yellow',
            'prefix' => '₱',
            'suffix' => '',
            'warning_class' => $warningLevel,
            'extra_content' => '
                <div class="w-full bg-gray-200 rounded-full h-2 mb-1.5">
                    <div class="bg-yellow-500 h-2 rounded-full" style="width: ' . $percentageUsed . '%"></div>
                </div>
                <div class="flex justify-between items-center text-xs mb-1">
                    <div class="' . $daysLeftClass . ' flex items-center">
                        <i class="fas fa-clock mr-1"></i> ' . $daysLeft . ' days left
                    </div>
                    <div class="' . $warningLevel . '">
                        ' . round($percentageUsed) . '% used
                    </div>
                </div>
                ' . ($percentageUsed >= 90 ? '
                <div class="text-xs bg-' . ($percentageUsed >= 100 ? 'red' : 'yellow') . '-100 text-' . ($percentageUsed >= 100 ? 'red' : 'yellow') . '-800 py-0.5 px-1 rounded text-center">
                    <i class="fas fa-' . ($percentageUsed >= 100 ? 'exclamation-circle' : 'exclamation-triangle') . ' mr-1"></i> ' .
                    ($percentageUsed >= 100 ? 'Budget exceeded!' : 'Approaching limit') . '
                </div>
                ' : '') . '
                <div class="text-xs text-gray-500 mt-1 flex justify-between items-center">
                    <span>Daily: ₱' . number_format($dailyRate, 0) . '</span>
                    <span>Projected: <span class="' . $projectionClass . '">₱' . number_format($projectedSpend, 0) . '</span></span>
                </div>'
        ],
        [
            'title' => 'Overdue Payments',
            'value' => $overdueCount,
            'sub_text' => 'Total: ₱' . number_format($overdueAmount, 2),
            'icon' => 'exclamation-triangle',
            'color' => 'orange',
            'prefix' => '',
            'suffix' => '',
            'extra_content' => '
                <div class="grid grid-cols-4 gap-1 text-xs">
                    <div class="text-center">
                        <div class="text-red-600 font-medium">' . ($urgencyData['critical'] ?? 0) . '</div>
                        <div class="text-gray-500 text-xs">30d+</div>
                    </div>
                    <div class="text-center">
                        <div class="text-orange-500 font-medium">' . ($urgencyData['high'] ?? 0) . '</div>
                        <div class="text-gray-500 text-xs">15-30d</div>
                    </div>
                    <div class="text-center">
                        <div class="text-yellow-500 font-medium">' . ($urgencyData['medium'] ?? 0) . '</div>
                        <div class="text-gray-500 text-xs">7-14d</div>
                    </div>
                    <div class="text-center">
                        <div class="text-blue-500 font-medium">' . ($urgencyData['low'] ?? 0) . '</div>
                        <div class="text-gray-500 text-xs">1-6d</div>
                    </div>
                </div>'
        ],
        [
            'title' => 'Upcoming Payments',
            'value' => $upcomingCount,
            'sub_text' => $nearestDate ? 'Next due: ' . date('M j', strtotime($nearestDate)) : '',
            'icon' => 'calendar-alt',
            'color' => 'purple',
            'prefix' => '',
            'suffix' => '',
            'change' => $trendText,
            'change_class' => $trendClass,
            'change_icon' => $trendIcon,
            'extra_content' => ''
        ]
    ];
    
    // Render cards
    foreach ($cards as $card) {
    ?>
    
    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <!-- Card header with gradient background -->
        <div class="bg-gradient-to-r from-<?php echo $card['color']; ?>-100 to-<?php echo $card['color']; ?>-200 p-3">
            <div class="flex items-center justify-between mb-1">
                <div class="flex-grow">
                    <h3 class="text-sm font-medium text-gray-700"><?php echo $card['title']; ?></h3>
                    <?php if (isset($card['sub_text']) && !empty($card['sub_text'])): ?>
                    <div class="text-xs text-gray-500"><?php echo $card['sub_text']; ?></div>
                    <?php endif; ?>
                </div>
                <div class="w-8 h-8 rounded-full bg-white/90 text-<?php echo $card['color']; ?>-600 flex items-center justify-center ml-2 flex-shrink-0">
                    <i class="fas fa-<?php echo $card['icon']; ?> text-sm"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-xl md:text-2xl font-bold <?php echo isset($card['warning_class']) ? $card['warning_class'] : 'text-gray-800'; ?>">
                    <?php echo $card['prefix'] . $card['value'] . $card['suffix']; ?>
                </span>
            </div>
        </div>
        
        <!-- Extra content if any -->
        <?php if (!empty($card['extra_content'])): ?>
        <div class="px-3 py-2 bg-white border-t border-gray-50">
            <?php echo $card['extra_content']; ?>
        </div>
        <?php endif; ?>
        
        <!-- Card footer with change indicator -->
        <?php if (isset($card['change']) && !empty($card['change'])): ?>
        <div class="px-3 py-2 bg-white border-t border-gray-50 text-xs">
            <div class="flex items-center <?php echo $card['change_class']; ?>">
                <i class="fas <?php echo $card['change_icon']; ?> mr-1"></i>
                <span><?php echo $card['change']; ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php } ?>
</div>

  <!-- Expense Charts -->
  <?php
// Database connection
require_once '../db_connect.php';

// 1. Get expense trends data for the last 6 months
$trendData = [];
$categoriesData = [];

// Monthly trends (last 6 months)
$monthlyQuery = "SELECT 
                DATE_FORMAT(date, '%Y-%m') as month,
                SUM(price) as total
                FROM expense_tb
                WHERE appearance = 'visible'
                AND date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
                GROUP BY month
                ORDER BY month ASC";
$monthlyResult = $conn->query($monthlyQuery);

$monthlyLabels = [];
$monthlyValues = [];
while ($row = $monthlyResult->fetch_assoc()) {
    $monthlyLabels[] = date('M', strtotime($row['month']));
    $monthlyValues[] = $row['total'];
}

// Quarterly trends (last 4 quarters)
$quarterlyQuery = "SELECT 
                  CONCAT('Q', QUARTER(date), ' ', YEAR(date)) as quarter,
                  SUM(price) as total
                  FROM expense_tb
                  WHERE appearance = 'visible'
                  AND date >= DATE_SUB(CURRENT_DATE(), INTERVAL 15 MONTH)
                  GROUP BY quarter
                  ORDER BY MIN(date) ASC";
$quarterlyResult = $conn->query($quarterlyQuery);

$quarterlyLabels = [];
$quarterlyValues = [];
while ($row = $quarterlyResult->fetch_assoc()) {
    $quarterlyLabels[] = $row['quarter'];
    $quarterlyValues[] = $row['total'];
}

// Yearly trends (last 2 years)
$yearlyQuery = "SELECT 
               YEAR(date) as year,
               SUM(price) as total
               FROM expense_tb
               WHERE appearance = 'visible'
               AND date >= DATE_SUB(CURRENT_DATE(), INTERVAL 2 YEAR)
               GROUP BY year
               ORDER BY year ASC";
$yearlyResult = $conn->query($yearlyQuery);

$yearlyLabels = [];
$yearlyValues = [];
while ($row = $yearlyResult->fetch_assoc()) {
    $yearlyLabels[] = $row['year'];
    $yearlyValues[] = $row['total'];
}

// 2. Get expense categories data
$categoriesQuery = "SELECT 
                   category,
                   SUM(price) as total
                   FROM expense_tb
                   WHERE appearance = 'visible'
                   GROUP BY category
                   ORDER BY total DESC";
$categoriesResult = $conn->query($categoriesQuery);

$categoryLabels = [];
$categoryValues = [];
$categoryColors = ['#1976d2', '#4caf50', '#ff9800', '#9c27b0', '#f44336', '#607d8b', '#795548'];
$i = 0;

while ($row = $categoriesResult->fetch_assoc()) {
    $categoryLabels[] = $row['category'];
    $categoryValues[] = $row['total'];
    $i++;
}

// Encode data for JavaScript
$monthlyDataJson = json_encode([
    'labels' => $monthlyLabels,
    'data' => $monthlyValues
]);

$quarterlyDataJson = json_encode([
    'labels' => $quarterlyLabels,
    'data' => $quarterlyValues
]);

$yearlyDataJson = json_encode([
    'labels' => $yearlyLabels,
    'data' => $yearlyValues
]);

$categoriesDataJson = json_encode([
    'labels' => $categoryLabels,
    'data' => $categoryValues,
    'colors' => array_slice($categoryColors, 0, count($categoryLabels))
]);
?>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Expense Trends Chart -->
    <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300">
        <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
            <h3 class="font-medium text-sidebar-text">Expense Trends</h3>
            <select id="expenseTrendPeriod" class="bg-gray-100 border border-sidebar-border rounded px-3 py-1 text-sm text-sidebar-text focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                <option value="month">Last 6 Months</option>
                <option value="quarter">Last 4 Quarters</option>
                <option value="year">Last 2 Years</option>
            </select>
        </div>
        <div class="p-5">
            <canvas id="expenseTrendsChart" class="h-64"></canvas>
        </div>
    </div>
    
    <!-- Expense Categories Chart -->
    <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300">
        <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
            <h3 class="font-medium text-sidebar-text">Expense Categories</h3>
            <button class="flex items-center px-3 py-1 text-sm border border-sidebar-border rounded-md text-sidebar-text hover:bg-sidebar-hover transition-all duration-300" onclick="exportChartData()">
                <i class="fas fa-download mr-1"></i> Export
            </button>
        </div>
        <div class="p-5">
            <canvas id="expenseCategoriesChart" class="h-64"></canvas>
        </div>
    </div>
</div>

  <!-- Add New Expense Button -->
  <!-- Replace the entire expense table section with this code -->
<?php
include '../db_connect.php';

// Get filter parameters
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Pagination setup
$recordsPerPage = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;

// Fetch all branches first
$branchQuery = "SELECT branch_id, branch_name FROM branch_tb";
$branchResult = $conn->query($branchQuery);

if ($branchResult->num_rows > 0) {
    while($branch = $branchResult->fetch_assoc()) {
        $branchId = $branch['branch_id'];
        $branchName = $branch['branch_name'];
        
        // Calculate where clause based on filters
        $whereClause = "WHERE branch_id = ".$branchId." AND appearance = 'visible'";
        
        if (!empty($categoryFilter)) {
            $whereClause .= " AND category = '".$conn->real_escape_string($categoryFilter)."'";
        }
        
        if (!empty($statusFilter)) {
            $whereClause .= " AND status = '".$conn->real_escape_string($statusFilter)."'";
        }
        
        if (!empty($searchQuery)) {
            $whereClause .= " AND (expense_name LIKE '%".$conn->real_escape_string($searchQuery)."%' 
                        OR category LIKE '%".$conn->real_escape_string($searchQuery)."%')";
        }
        
        // Count total expenses for this branch with filters
        $countQuery = "SELECT COUNT(*) as total FROM expense_tb $whereClause";
        $countResult = $conn->query($countQuery);
        $totalBranchExpenses = $countResult->fetch_assoc()['total'];
        
        // Branch-specific pagination
        $branchPage = isset($_GET['page_'.$branchId]) ? intval($_GET['page_'.$branchId]) : 1;
        $branchOffset = ($branchPage - 1) * $recordsPerPage;
        $totalBranchPages = ceil($totalBranchExpenses / $recordsPerPage);
        
        // Fetch expenses for this branch with pagination and filters
        $expenseQuery = "SELECT * FROM expense_tb $whereClause ORDER BY date DESC LIMIT $branchOffset, $recordsPerPage";
        $expenseResult = $conn->query($expenseQuery);
?>

<!-- Branch-specific expense card -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden branch-expense-container" data-branch-id="<?php echo $branchId; ?>">
    <!-- Branch Header with Search and Filters - Made responsive with better stacking -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
        <!-- Desktop layout for big screens - Title on left, controls on right -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Title and Counter -->
            <div class="flex items-center gap-3 mb-4 lg:mb-0">
                <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap"><?php echo $branchName; ?> - Expenses</h4>
                
                <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                    <i class="fas fa-receipt"></i>
                    <?php echo $totalBranchExpenses . ($totalBranchExpenses != 1 ? "" : ""); ?>
                </span>
            </div>
            
            <!-- Controls for big screens - aligned right -->
<div class="hidden lg:flex items-center gap-3">
    <!-- Search Input -->
    <div class="relative">
        <input type="text" id="searchInput" 
               placeholder="Search expenses..." 
               class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
               oninput="debouncedSearch()">
        <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
    </div>

    <!-- Status Dropdown Acting as Filter -->
    <select id="statusFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
        <option value="">All Status</option>
        <option value="paid">Paid</option>
        <option value="to be paid">To Be Paid</option>
    </select>

    <!-- Category Filter -->
    <select id="categoryFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
        <option value="">All Categories</option>
        <option value="Supplies">Supplies</option>
        <option value="Utilities">Utilities</option>
        <option value="Salaries">Salaries</option>
        <option value="Maintenance">Maintenance</option>
        <option value="Other">Other</option>
    </select>

    <!-- Archive Button -->
    <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover whitespace-nowrap">
        <i class="fas fa-archive text-sidebar-accent"></i>
        <span>Archive</span>
    </button>
</div>

                <!-- Add Expense Button -->
                <button class="px-4 py-2 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap" 
                        onclick="openAddExpenseModal(<?php echo $branchId; ?>)"><span>Add Expense</span>
                </button>
            </div>
        </div>
        
        <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
        <div class="lg:hidden w-full mt-4">
            <!-- First row: Search bar with filter and archive icons on the right -->
            <div class="flex items-center w-full gap-3 mb-4">
                <!-- Search Input - Takes most of the space -->
                <div class="relative flex-grow">
                    <input type="text" id="searchInput<?php echo $branchId; ?>_mobile" 
                           placeholder="Search expenses..." 
                           value="<?php echo htmlspecialchars($searchQuery); ?>"
                           class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                           oninput="debouncedSearch(<?php echo $branchId; ?>)">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>

                <!-- Icon-only buttons for filter and archive -->
                <div class="flex items-center gap-3">
                    <!-- Filter Icon Button -->
                    <div class="relative filter-dropdown">
                        <button id="expenseFilterToggle<?php echo $branchId; ?>" class="w-10 h-10 flex items-center justify-center text-sidebar-accent" onclick="toggleFilterWindow(<?php echo $branchId; ?>)">
                            <i class="fas fa-filter text-xl"></i>
                            <span id="filterIndicator<?php echo $branchId; ?>" class="<?php echo ($categoryFilter || $statusFilter) ? '' : 'hidden'; ?> absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
                        </button>
                    </div>

                    <!-- Archive Icon Button -->
                    <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
                        <i class="fas fa-archive text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Second row: Add Expense Button - Full width -->
            <div class="w-full">
                <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap w-full justify-center" 
                        onclick="openAddExpenseModal(<?php echo $branchId; ?>)"><span>Add Expense</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Responsive Table Container with improved spacing -->
    <div class="overflow-x-auto scrollbar-thin" id="tableContainer<?php echo $branchId; ?>">
        <div id="loadingIndicator<?php echo $branchId; ?>" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
        </div>
        
        <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
        <div class="min-w-full">
            <table class="w-full">
            <thead>
    <tr class="bg-gray-50 border-b border-sidebar-border">
        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(0)">
            <div class="flex items-center gap-1.5">
                <i class="fas fa-hashtag text-sidebar-accent"></i> ID 
            </div>
        </th>
        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(1)">
            <div class="flex items-center gap-1.5">
                <i class="fa-solid fa-file-invoice text-sidebar-accent"></i> Expense Name 
            </div>
        </th>
        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(2)">
            <div class="flex items-center gap-1.5">
                <i class="fas fa-th-list text-sidebar-accent"></i> Category 
            </div>
        </th>
        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(3)">
            <div class="flex items-center gap-1.5">
                <i class="fas fa-peso-sign text-sidebar-accent"></i> Amount 
            </div>
        </th>
        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(4)">
            <div class="flex items-center gap-1.5">
                <i class="fas fa-calendar-alt text-sidebar-accent"></i> Date 
            </div>
        </th>
        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(5)">
            <div class="flex items-center gap-1.5">
                <i class="fas fa-check-circle text-sidebar-accent"></i> Status 
            </div>
        </th>
        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
            <div class="flex items-center gap-1.5">
                <i class="fas fa-cogs text-sidebar-accent"></i> Actions
            </div>
        </th>
    </tr>
</thead>
                <tbody>
                    <?php if ($expenseResult->num_rows > 0): ?>
                        <?php while($expense = $expenseResult->fetch_assoc()): ?>
                            <?php
                            $statusClass = $expense['status'] == 'paid' 
                                ? "bg-green-100 text-green-600 border border-green-200" 
                                : "bg-orange-100 text-orange-500 border border-orange-200";
                            $statusIcon = $expense['status'] == 'paid' ? "fa-check-circle" : "fa-clock";
                            $statusText = $expense['status'] == 'paid' ? 'Paid' : 'To be paid';
                            ?>
                            <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                                <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#EXP-<?php echo str_pad($expense['expense_ID'], 3, "0", STR_PAD_LEFT); ?></td>
                                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($expense['expense_name']); ?></td>
                                <td class="px-4 py-3.5 text-sm text-sidebar-text">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        <?php echo htmlspecialchars($expense['category']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">₱<?php echo number_format($expense['price'], 2); ?></td>
                                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo $expense['date']; ?></td>
                                <td class="px-4 py-3.5 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                        <i class="fas <?php echo $statusIcon; ?> mr-1"></i> <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-sm">
                                    <div class="flex space-x-2">
                                        <button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" title="Edit Expense" 
                                                onclick="openEditExpenseModal('#EXP-<?php echo str_pad($expense['expense_ID'], 3, '0', STR_PAD_LEFT); ?>', 
                                                    '<?php echo addslashes($expense['expense_name']); ?>', 
                                                    '<?php echo addslashes($expense['category']); ?>', 
                                                    '<?php echo $expense['price']; ?>', 
                                                    '<?php echo $expense['date']; ?>', 
                                                    '<?php echo $expense['branch_id']; ?>', 
                                                    '<?php echo $expense['status']; ?>', 
                                                    '<?php echo addslashes($expense['notes'] ?? ''); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip" title="Delete Expense" 
                                                onclick="deleteExpense('#EXP-<?php echo str_pad($expense['expense_ID'], 3, '0', STR_PAD_LEFT); ?>')">
                                            <i class="fas fa-archive text-red"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-sm text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                                    <p class="text-gray-500">No expenses found for this branch</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Sticky Pagination Footer with improved spacing -->
<div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
        Showing <?php echo ($branchOffset + 1) . ' - ' . min($branchOffset + $recordsPerPage, $totalBranchExpenses); ?> 
        of <?php echo $totalBranchExpenses; ?> expenses
    </div>
    <div class="flex space-x-2">
        <a href="<?php echo '?page=' . max(1, $branchPage - 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $branchPage <= 1 ? 'opacity-50 pointer-events-none' : ''; ?>">&laquo;</a>
        
        <?php for ($i = 1; $i <= $totalBranchPages; $i++): ?>
            <a href="<?php echo '?page=' . $i; ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm <?php echo $i == $branchPage ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
        
        <a href="<?php echo '?page=' . min($totalBranchPages, $branchPage + 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $branchPage >= $totalBranchPages ? 'opacity-50 pointer-events-none' : ''; ?>">&raquo;</a>
    </div>
</div>

<?php
    } // End branch while loop
} else {
    echo '<div class="bg-white rounded-lg shadow-md p-8 text-center">';
    echo '<i class="fas fa-store text-gray-300 text-4xl mb-3"></i>';
    echo '<p class="text-lg text-gray-500">No branches found</p>';
    echo '</div>';
}

$conn->close();
?>


<!-- Modal for Adding New Expense -->
<div id="addExpenseModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeAddExpenseModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Add New Expense
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <form id="expenseForm" class="space-y-3 sm:space-y-4">
        <!-- Basic Information -->
        <div>
          <label for="expenseDescription" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Expense Name
          </label>
          <div class="relative">
            <input type="text" id="expenseDescription" name="expenseDescription" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
          </div>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
          <div class="w-full sm:flex-1">
            <label for="expenseCategory" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Category
            </label>
            <div class="relative">
              <select id="expenseCategory" name="expenseCategory" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                <option value="" disabled selected>Select category</option>
                <option value="Supplies">Supplies</option>
                <option value="Utilities">Utilities</option>
                <option value="Salaries">Salaries</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
          
          <div class="w-full sm:flex-1">
            <label for="expenseDate" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Date
            </label>
            <div class="relative">
              <input type="date" id="expenseDate" name="expenseDate" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
            </div>
          </div>
        </div>
        
        <div>
          <label for="expenseAmount" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Amount
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="expenseAmount" name="expenseAmount" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
          </div>
        </div>
        
        <!-- Status -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Status
          </label>
          <div class="grid grid-cols-2 gap-2">
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="statusPaid" name="expenseStatus" value="paid" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent" checked>
              Paid
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="statusToBePaid" name="expenseStatus" value="to be paid" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              To Be Paid
            </label>
          </div>
        </div>
        
        <!-- Payment Method -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Payment Method
          </label>
          <div class="grid grid-cols-2 gap-2">
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="methodCash" name="paymentMethod" value="cash" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent" checked>
              Cash
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="methodCredit" name="paymentMethod" value="credit" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              Credit Card
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="methodTransfer" name="paymentMethod" value="transfer" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              Bank Transfer
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="methodOther" name="paymentMethod" value="other" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              Other
            </label>
          </div>
        </div>
        
        <!-- Branch -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border-l-4 border-gold">
          <label class="block text-xs font-medium text-gray-700 mb-1">Branch</label>
          <div class="grid grid-cols-2 gap-2">
          <?php
          // Include database connection
          require_once '../db_connect.php';
          
          // Create connection
          $conn = new mysqli($servername, $username, $password, $dbname);
          
          // Check connection
          if ($conn->connect_error) {
              die("Connection failed: " . $conn->connect_error);
          }
          
          $sql = "SELECT branch_id, branch_name FROM branch_tb";
          $result = $conn->query($sql);
          
          if ($result->num_rows > 0) {
              $first = true;
              while($row = $result->fetch_assoc()) {
                  echo '<label class="flex items-center space-x-2 cursor-pointer">';
                  echo '<input type="radio" name="expenseBranch" value="' . $row['branch_id'] . '"' . 
                       ($first ? ' checked' : '') . ' required class="hidden peer">';
                  echo '<div class="w-4 h-4 rounded-full border-2 border-gold flex items-center justify-center peer-checked:bg-gold peer-checked:border-darkgold transition-colors"></div>';
                  echo '<span class="text-sm text-gray-700">' . htmlspecialchars($row['branch_name']) . '</span>';
                  echo '</label>';
                  $first = false;
              }
          } else {
              echo '<p class="text-gray-500">No branches available.</p>';
          }
          $conn->close();
          ?>
          </div>
        </div>
        
        <!-- Note -->
        <div>
          <label for="expenseNote" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Note
          </label>
          <div class="relative">
            <textarea id="expenseNote" name="expenseNote" rows="3" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"></textarea>
          </div>
        </div>
        
        <!-- Receipt Upload -->
        <div>
          <label for="expenseReceipt" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Upload Receipt
          </label>
          <div class="relative flex items-center border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-1 focus-within:ring-sidebar-accent focus-within:border-sidebar-accent transition-all duration-200">
            <input type="file" id="expenseReceipt" name="expenseReceipt" class="w-full focus:outline-none">
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeAddExpenseModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="addExpense()">
        Add Expense
      </button>
    </div>
  </div>
</div>

<!-- Modal for Editing Expense -->
<div id="editExpenseModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeEditExpenseModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Edit Expense
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <form id="editExpenseForm" class="space-y-3 sm:space-y-4">
        <input type="hidden" id="editExpenseId" name="editExpenseId">
        
        <div>
          <label for="editExpenseDescription" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Expense Name
          </label>
          <input type="text" id="editExpenseDescription" name="editExpenseDescription" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
          <div class="w-full sm:flex-1">
            <label for="editExpenseCategory" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Category
            </label>
            <select id="editExpenseCategory" name="editExpenseCategory" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
              <option value="" disabled selected>Select category</option>
              <option value="Supplies">Supplies</option>
              <option value="Utilities">Utilities</option>
              <option value="Salaries">Salaries</option>
              <option value="Maintenance">Maintenance</option>
              <option value="Other">Other</option>
            </select>
          </div>
          
          <div class="w-full sm:flex-1">
            <label for="editExpenseDate" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Date
            </label>
            <input type="date" id="editExpenseDate" name="editExpenseDate" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
          </div>
        </div>
        
        <div>
          <label for="editExpenseAmount" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Amount
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="editExpenseAmount" name="editExpenseAmount" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
          </div>
        </div>
        
        <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
          <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Status
          </label>
          <div class="grid grid-cols-2 gap-2">
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="editStatusPaid" name="editExpenseStatus" value="paid" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              Paid
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="editStatusToBePaid" name="editExpenseStatus" value="to be paid" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              To Be Paid
            </label>
          </div>
        </div>
        
        <div class="bg-gray-50 p-3 rounded-lg border-l-4 border-gold">
          <label class="block text-xs font-medium text-gray-700 mb-1">Branch</label>
          <div class="grid grid-cols-2 gap-2">
          <?php
          // Include database connection
          require_once '../db_connect.php';
          
          // Create connection
          $conn = new mysqli($servername, $username, $password, $dbname);
          
          // Check connection
          if ($conn->connect_error) {
              die("Connection failed: " . $conn->connect_error);
          }
          
          $sql = "SELECT branch_id, branch_name FROM branch_tb";
          $result = $conn->query($sql);
          
          if ($result->num_rows > 0) {
              while($row = $result->fetch_assoc()) {
                  echo '<label class="flex items-center space-x-2 cursor-pointer">';
                  echo '<input type="radio" id="editBranch_' . htmlspecialchars($row['branch_id']) . '" 
                        name="editExpenseBranch" value="' . htmlspecialchars($row['branch_id']) . '" 
                        class="hidden peer">';
                  echo '<div class="w-4 h-4 rounded-full border-2 border-gold flex items-center justify-center peer-checked:bg-gold peer-checked:border-darkgold transition-colors"></div>';
                  echo '<span class="text-sm text-gray-700">' . htmlspecialchars($row['branch_name']) . '</span>';
                  echo '</label>';
              }
          } else {
              echo '<p class="text-gray-500">No branches available.</p>';
          }
          $conn->close();
          ?>
          </div>
        </div>
        
        <div>
          <label for="editExpenseNote" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Note
          </label>
          <textarea id="editExpenseNote" name="editExpenseNote" rows="3" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"></textarea>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeEditExpenseModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="saveExpenseChanges()">
        Save Changes
      </button>
    </div>
  </div>
</div>

  <script>
    // Function to open the Add Expense Modal
    function openAddExpenseModal() {
      document.getElementById('addExpenseModal').style.display = 'flex';
    }

    // Function to close the Add Expense Modal
    function closeAddExpenseModal() {
      document.getElementById('addExpenseModal').style.display = 'none';
    }

    // Function to add an expense
    // Function to add an expense
function addExpense() {
    const form = document.getElementById('expenseForm');
    
    if (form.checkValidity()) {
        // Get form values
        const description = document.getElementById('expenseDescription').value;
        const branch = document.querySelector('input[name="expenseBranch"]:checked').value;
        const category = document.getElementById('expenseCategory').value;
        const amount = document.getElementById('expenseAmount').value;
        const date = document.getElementById('expenseDate').value;
        const status = document.querySelector('input[name="expenseStatus"]:checked').value;
        const note = document.getElementById('expenseNote').value;
        
        // Create FormData object
        const formData = new FormData();
        formData.append('description', description);
        formData.append('branch', branch);
        formData.append('category', category);
        formData.append('amount', amount);
        formData.append('date', date);
        formData.append('status', status);
        formData.append('note', note);
        
        // Send data to server using AJAX
        fetch('expenses/add_expense.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Expense added successfully!');
                closeAddExpenseModal();
                // Refresh the expenses table or add the new expense to the table
                location.reload(); // Simple refresh for now
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding the expense.');
        });
    } else {
        form.reportValidity();
    }
}

    // Function to open the Edit Expense Modal
    // Updated openEditExpenseModal function
function openEditExpenseModal(expenseId, expenseName, category, amount, date, branchId, status, notes) {
    console.log(expenseId, expenseName, category, amount, date, branchId, status, notes);
    document.getElementById('editExpenseId').value = expenseId.replace('#EXP-', '');
    document.getElementById('editExpenseDescription').value = expenseName;
    document.getElementById('editExpenseCategory').value = category;
    document.getElementById('editExpenseAmount').value = amount;
    document.getElementById('editExpenseDate').value = date;
    document.getElementById('editExpenseNote').value = notes || '';
    
    // Set the branch radio button
    if (branchId) {
        const branchRadio = document.querySelector(`input[name="editExpenseBranch"][value="${branchId}"]`);
        if (branchRadio) {
            branchRadio.checked = true;
        }
    }
    
    if (status) {
        const statusValue = status.toLowerCase() === 'paid' ? 'paid' : 'to be paid'; // Changed to match database values
        document.querySelector(`input[name="editExpenseStatus"][value="${statusValue}"]`).checked = true;
    }
    console.log(status);
    
    document.getElementById('editExpenseModal').style.display = 'flex';
}

// Updated saveExpenseChanges function
function saveExpenseChanges() {
    const form = document.getElementById('editExpenseForm');
    
    if (form.checkValidity()) {
        // Get form values
        const expenseId = document.getElementById('editExpenseId').value;
        const description = document.getElementById('editExpenseDescription').value;
        const branch = document.querySelector('input[name="editExpenseBranch"]:checked').value;
        const category = document.getElementById('editExpenseCategory').value;
        const amount = document.getElementById('editExpenseAmount').value;
        const date = document.getElementById('editExpenseDate').value;
        const status = document.querySelector('input[name="editExpenseStatus"]:checked').value;
        const note = document.getElementById('editExpenseNote').value;
        
        // Debug: Log all values before creating FormData
        console.log('--- Form Values Before Submission ---');
        console.log('Expense ID:', expenseId);
        console.log('Description:', description);
        console.log('Branch:', branch);
        console.log('Category:', category);
        console.log('Amount:', amount);
        console.log('Date:', date);
        console.log('Status:', status);
        console.log('Note:', note);
        console.log('------------------------------------');

        // Create FormData object
        const formData = new FormData();
        formData.append('expense_id', expenseId);
        formData.append('description', description);
        formData.append('branch', branch);
        formData.append('category', category);
        formData.append('amount', amount);
        formData.append('date', date);
        formData.append('status', status);
        formData.append('note', note);

        // Debug: Log FormData contents
        console.log('--- FormData Contents ---');
        for (let [key, value] of formData.entries()) {
            console.log(key + ':', value);
        }
        console.log('-------------------------');

        // Send data to server using AJAX
        console.log('Sending request to server...');
        fetch('expenses/update_expense.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            console.log('Response received, processing...');
            return response.json();
        })
        .then(data => {
            console.log('Server response:', data);
            if (data.success) {
                console.log('Update successful');
                alert('Expense updated successfully!');
                closeEditExpenseModal();
                location.reload(); // Refresh the page to show changes
            } else {
                console.error('Server reported error:', data.message);
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            alert('An error occurred while updating the expense.');
        });
    } else {
        console.warn('Form validation failed');
        form.reportValidity();
    }
}

// Update the edit button in your table rows to pass all necessary parameters:
// onclick="openEditExpenseModal('#EXP-'.str_pad($expense['expense_ID'], 3, '0', STR_PAD_LEFT), '".$expense['expense_name']."', '".$expense['category']."', ".$expense['price'].", '".$expense['date']."', ".$expense['branch_id'].", '".$expense['status']."', '".($expense['notes'] ?? '')."')"

    // Function to close the Edit Expense Modal
    function closeEditExpenseModal() {
      document.getElementById('editExpenseModal').style.display = 'none';
    }

    // Function to delete an expense
    // Function to "delete" an expense by setting appearance to 'hidden'
function deleteExpense(expenseId) {
    if (confirm('Are you sure you want to hide this expense? It will no longer be visible in the table.')) {
        // Create FormData object
        const formData = new FormData();
        const numericId = expenseId.replace('#EXP-', ''); // Extract numeric ID
        formData.append('expense_id', numericId);
        
        // Send request to update appearance
        fetch('expenses/hide_expense.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Expense hidden successfully!');
                location.reload(); // Refresh the page to show changes
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while hiding the expense.');
        });
    }
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Chart data from PHP
    const monthlyData = <?php echo $monthlyDataJson; ?>;
    const quarterlyData = <?php echo $quarterlyDataJson; ?>;
    const yearlyData = <?php echo $yearlyDataJson; ?>;
    const categoriesData = <?php echo $categoriesDataJson; ?>;
    
    // Initialize charts
    let expenseTrendsChart, expenseCategoriesChart;
    
    function initTrendsChart(period) {
        let data, labels;
        
        switch(period) {
            case 'quarter':
                labels = quarterlyData.labels;
                data = quarterlyData.data;
                break;
            case 'year':
                labels = yearlyData.labels;
                data = yearlyData.data;
                break;
            default: // month
                labels = monthlyData.labels;
                data = monthlyData.data;
        }
        
        if (expenseTrendsChart) {
            expenseTrendsChart.data.labels = labels;
            expenseTrendsChart.data.datasets[0].data = data;
            expenseTrendsChart.update();
        } else {
            const ctx = document.getElementById('expenseTrendsChart').getContext('2d');
            expenseTrendsChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Expenses',
                        data: data,
                        borderColor: '#f44336',
                        backgroundColor: 'rgba(244, 67, 54, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₱' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return '₱' + context.raw.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
    }
    
    function initCategoriesChart() {
        const ctx = document.getElementById('expenseCategoriesChart').getContext('2d');
        expenseCategoriesChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categoriesData.labels,
                datasets: [{
                    data: categoriesData.data,
                    backgroundColor: categoriesData.colors,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ₱${value.toLocaleString()} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Initialize charts
    initTrendsChart('month');
    initCategoriesChart();
    
    // Handle period change
    document.getElementById('expenseTrendPeriod').addEventListener('change', function() {
        initTrendsChart(this.value);
    });
    
    // Export function
    window.exportChartData = function() {
        // You can implement CSV or Excel export here
        const data = {
            categories: categoriesData.labels,
            amounts: categoriesData.data
        };
        
        // Simple alert for demonstration
        alert('Export functionality would download:\n' + 
              JSON.stringify(data, null, 2));
    };
});
</script>

<script>
// Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    function debouncedSearch(branchId) {
        clearTimeout(window.searchTimer);
        window.searchTimer = setTimeout(() => {
            applyFilters(branchId);
        }, 300);
    }

    // Filter functionality
    function toggleFilterWindow(branchId) {
        const filterWindow = document.getElementById(`filterWindow${branchId}`);
        filterWindow.classList.toggle('hidden');
        
        // Close when clicking outside
        document.addEventListener('click', function closeOnClickOutside(e) {
            if (!e.target.closest(`#filterWindow${branchId}`) {
                filterWindow.classList.add('hidden');
                document.removeEventListener('click', closeOnClickOutside);
            }
        });
    }

    function setFilter(branchId, type, value) {
        // Update the UI to show active filters
        if (value) {
            document.getElementById(`filterIndicator${branchId}`).classList.remove('hidden');
        } else if (type === 'category' && !document.querySelector(`input[name="status"]:checked`) ||
                   type === 'status' && !document.querySelector(`input[name="category"]:checked`)) {
            document.getElementById(`filterIndicator${branchId}`).classList.add('hidden');
        }
        
        applyFilters(branchId);
    }

    function applyFilters(branchId) {
        const searchValue = document.getElementById(`searchInput${branchId}`).value.toLowerCase();
        const categoryFilter = document.querySelector(`input[name="category"]:checked`)?.value || '';
        const statusFilter = document.querySelector(`input[name="status"]:checked`)?.value || '';
        
        const rows = document.querySelectorAll(`#tableContainer${branchId} tbody tr`);
        
        rows.forEach(row => {
            const nameCell = row.cells[1]?.textContent?.toLowerCase() || '';
            const categoryCell = row.cells[2]?.textContent?.toLowerCase() || '';
            const statusCell = row.cells[5]?.textContent?.toLowerCase() || '';
            
            const matchesSearch = nameCell.includes(searchValue);
            const matchesCategory = !categoryFilter || categoryCell.includes(categoryFilter.toLowerCase());
            const matchesStatus = !statusFilter || statusCell.includes(statusFilter.toLowerCase());
            
            row.style.display = (matchesSearch && matchesCategory && matchesStatus) ? '' : 'none';
        });
    }

    // Pagination functionality
    function changeBranchPage(branchId, page) {
        const url = new URL(window.location.href);
        url.searchParams.set(`page_${branchId}`, page);
        window.location.href = url.toString();
    }

    // Initialize filters from URL parameters
    function initFiltersFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const categoryFilter = urlParams.get('category');
        const statusFilter = urlParams.get('status');
        const searchQuery = urlParams.get('search');
        
        if (categoryFilter) {
            document.querySelector(`input[name="category"][value="${categoryFilter}"]`).checked = true;
        }
        if (statusFilter) {
            document.querySelector(`input[name="status"][value="${statusFilter}"]`).checked = true;
        }
        if (searchQuery) {
            document.getElementById('searchInput').value = searchQuery;
        }
    }

    initFiltersFromUrl();
});

function sortTable(columnIndex) {
    const table = document.querySelector('table');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isAscending = table.getAttribute('data-sort-direction') === 'asc';
    
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        // Special handling for numeric and date columns
        if (columnIndex === 0 || columnIndex === 3) { // ID or Amount columns
            const aNum = parseFloat(aValue.replace(/[^0-9.-]/g, ''));
            const bNum = parseFloat(bValue.replace(/[^0-9.-]/g, ''));
            return isAscending ? aNum - bNum : bNum - aNum;
        } else if (columnIndex === 4) { // Date column
            return isAscending 
                ? new Date(aValue) - new Date(bValue)
                : new Date(bValue) - new Date(aValue);
        } else {
            // Default text comparison
            return isAscending 
                ? aValue.localeCompare(bValue)
                : bValue.localeCompare(aValue);
        }
    });
    
    // Remove all existing rows
    while (tbody.firstChild) {
        tbody.removeChild(tbody.firstChild);
    }
    
    // Add sorted rows
    rows.forEach(row => tbody.appendChild(row));
    
    // Update sort direction for next click
    table.setAttribute('data-sort-direction', isAscending ? 'desc' : 'asc');
}
</script>
  <script src="script.js"></script>
  <script src="tailwind.js"></script>
</body>
</html>