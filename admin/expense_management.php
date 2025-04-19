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
  <title>Expenses - GrievEase</title>
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
    <div class="flex space-x-3">
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-bell"></i>
      </button>
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-cog"></i>
      </button>
    </div>
  </div>

  <!-- Expense Overview Cards -->
<div class="mb-8">
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Total Expenses Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-all duration-300">
      <div class="flex items-center mb-4">
        <div class="w-14 h-14 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center mr-4">
          <i class="fas fa-peso-sign text-xl"></i>
        </div>
        <span class="text-gray-700 font-semibold text-lg">Total Expenses</span>
      </div>
      <div class="text-4xl font-bold mb-3 text-gray-800">₱<?php echo $totalAmount; ?></div>
      <div class="text-sm <?php echo $trendClass; ?> flex items-center font-medium">
        <i class="fas <?php echo $trendIcon; ?> mr-2"></i> <?php echo $trendText; ?>
      </div>
    </div>
    
    <?php
    // Database connection (assuming you already have this)
    require_once '../db_connect.php';

    // 1. Set your monthly budget (you can change this value)
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
    

    // 2. Calculate current month's expenses
    $currentMonthQuery = "SELECT SUM(price) as total 
                        FROM expense_tb 
                        WHERE appearance = 'visible' 
                        AND MONTH(date) = MONTH(CURRENT_DATE()) 
                        AND YEAR(date) = YEAR(CURRENT_DATE())";
    $currentMonthResult = $conn->query($currentMonthQuery);
    $currentMonthRow = $currentMonthResult->fetch_assoc();
    $currentSpend = $currentMonthRow['total'] ?? 0;

    // 3. Calculate days remaining in the month
    $today = new DateTime();
    $lastDayOfMonth = new DateTime('last day of this month');
    $daysLeft = $today->diff($lastDayOfMonth)->days;

    // 4. Calculate percentage used (with protection against division by zero)
    $percentageUsed = ($monthlyBudget > 0) ? min(($currentSpend / $monthlyBudget) * 100, 100) : 0;

    // 5. Determine warning colors
    $warningLevel = ($percentageUsed >= 90) ? 'text-red-600' : 
                  (($percentageUsed >= 75) ? 'text-yellow-600' : 'text-green-600');
    $daysLeftClass = ($daysLeft > 5) ? 'text-green-600' : 'text-red-600';
    ?>

    <!-- Monthly Budget Status Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-all duration-300">
      <div class="flex items-center mb-4">
        <div class="w-14 h-14 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center mr-4">
          <i class="fas fa-wallet text-xl"></i>
        </div>
        <span class="text-gray-700 font-semibold text-lg">Monthly Budget</span>
      </div>
      
      <div class="flex items-end justify-between mb-3">
        <div class="text-3xl font-bold <?php echo $warningLevel; ?>">
          ₱<?php echo number_format($currentSpend, 2); ?>
        </div>
        <div class="text-sm text-gray-500 font-medium">of ₱<?php echo number_format($monthlyBudget, 2); ?></div>
      </div>
      
      <div class="w-full bg-gray-200 rounded-full h-4 mb-3 overflow-hidden">
        <div class="<?php echo $percentageUsed >= 90 ? 'bg-red-500' : ($percentageUsed >= 75 ? 'bg-yellow-500' : 'bg-green-500'); ?> h-4 rounded-full" style="width: <?php echo $percentageUsed; ?>%"></div>
      </div>
      
      <div class="flex justify-between items-center">
        <div class="text-sm <?php echo $daysLeftClass; ?> flex items-center font-medium">
          <i class="fas fa-clock mr-2"></i> <?php echo $daysLeft; ?> days left
        </div>
        <div class="text-sm <?php echo $warningLevel; ?> font-medium">
          <?php echo round($percentageUsed); ?>% used
        </div>
      </div>
      
      <?php if ($percentageUsed >= 100): ?>
        <div class="mt-3 text-sm bg-red-100 text-red-800 p-2 rounded-lg font-medium text-center">
          <i class="fas fa-exclamation-circle mr-1"></i> Budget exceeded!
        </div>
      <?php elseif ($percentageUsed >= 90): ?>
        <div class="mt-3 text-sm bg-yellow-100 text-yellow-800 p-2 rounded-lg font-medium text-center">
          <i class="fas fa-exclamation-triangle mr-1"></i> Approaching limit
        </div>
      <?php endif; ?>
      
      <!-- Optional: Daily spending rate projection -->
      <?php
      $daysPassed = date('j'); // Current day of month
      $dailyRate = ($daysPassed > 0) ? $currentSpend / $daysPassed : 0;
      $projectedSpend = $dailyRate * date('t');
      $projectionClass = ($projectedSpend > $monthlyBudget) ? 'text-red-600' : 'text-green-600';
      ?>
      <div class="mt-3 text-xs text-gray-600 border-t border-gray-100 pt-3 flex justify-between">
        <span>Daily: ₱<?php echo number_format($dailyRate, 2); ?></span>
        <span>Projected: <span class="<?php echo $projectionClass; ?> font-medium">₱<?php echo number_format($projectedSpend, 2); ?></span></span>
      </div>
    </div>
    
    <?php
    // Enhanced query to get both count and total amount
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
    ?>

    <!-- Overdue Payments Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-all duration-300">
      <div class="flex items-center mb-4">
        <div class="w-14 h-14 rounded-full bg-red-100 text-red-600 flex items-center justify-center mr-4">
          <i class="fas fa-exclamation-triangle text-xl"></i>
        </div>
        <div>
          <span class="text-gray-700 font-semibold text-lg">Overdue Payments</span>
          <div class="text-sm text-gray-500">Total: ₱<?php echo number_format($overdueAmount, 2); ?></div>
        </div>
      </div>
      
      <div class="text-4xl font-bold mb-4 text-gray-800"><?php echo $overdueCount; ?></div>
      
      <!-- Urgency breakdown -->
      <div class="grid grid-cols-4 gap-3 mb-4">
        <div class="bg-red-50 p-2 rounded-lg text-center">
          <div class="text-red-600 font-bold text-lg"><?php echo $urgencyData['critical'] ?? 0; ?></div>
          <div class="text-gray-500 text-xs">30+ days</div>
        </div>
        <div class="bg-orange-50 p-2 rounded-lg text-center">
          <div class="text-orange-500 font-bold text-lg"><?php echo $urgencyData['high'] ?? 0; ?></div>
          <div class="text-gray-500 text-xs">15-30 days</div>
        </div>
        <div class="bg-yellow-50 p-2 rounded-lg text-center">
          <div class="text-yellow-500 font-bold text-lg"><?php echo $urgencyData['medium'] ?? 0; ?></div>
          <div class="text-gray-500 text-xs">7-14 days</div>
        </div>
        <div class="bg-blue-50 p-2 rounded-lg text-center">
          <div class="text-blue-500 font-bold text-lg"><?php echo $urgencyData['low'] ?? 0; ?></div>
          <div class="text-gray-500 text-xs">1-6 days</div>
        </div>
      </div>
      
      <div class="text-sm <?php echo $trendClass; ?> flex items-center font-medium">
        <i class="fas <?php echo $trendIcon; ?> mr-2"></i> <?php echo $trendText; ?>
      </div>
    </div>
    
    <?php
    // Database connection
    require_once '../db_connect.php';

    // 1. Count upcoming payments (status = "To be paid" with future dates)
    $upcomingQuery = "SELECT COUNT(*) as count 
                    FROM expense_tb 
                    WHERE appearance = 'visible' 
                    AND status = 'To be paid' 
                    AND date > CURDATE()";
    $upcomingResult = $conn->query($upcomingQuery);
    $upcomingCount = $upcomingResult->fetch_assoc()['count'] ?? 0;

    // 2. Count last month's upcoming payments for comparison
    $lastMonthUpcomingQuery = "SELECT COUNT(*) as count 
                              FROM expense_tb 
                              WHERE appearance = 'visible' 
                              AND status = 'To be paid' 
                              AND date BETWEEN DATE_SUB(CURDATE(), INTERVAL 1 MONTH) AND CURDATE()";
    $lastMonthUpcomingResult = $conn->query($lastMonthUpcomingQuery);
    $lastMonthUpcomingCount = $lastMonthUpcomingResult->fetch_assoc()['count'] ?? 0;

    // 3. Calculate percentage change
    if ($lastMonthUpcomingCount > 0) {
        $percentageChange = (($upcomingCount - $lastMonthUpcomingCount) / $lastMonthUpcomingCount) * 100;
    } else {
        $percentageChange = $upcomingCount > 0 ? 100 : 0;
    }

    // 4. Determine trend styling
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

    // 5. Get nearest upcoming payment date
    $nearestQuery = "SELECT MIN(date) as nearest_date, COUNT(*) as count_this_week
                    FROM expense_tb 
                    WHERE appearance = 'visible' 
                    AND status = 'To be paid' 
                    AND date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    $nearestResult = $conn->query($nearestQuery);
    $nearestData = $nearestResult->fetch_assoc();
    $nearestDate = $nearestData['nearest_date'];
    $countThisWeek = $nearestData['count_this_week'] ?? 0;
    ?>

    <!-- Upcoming Payments Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 hover:shadow-xl transition-all duration-300">
      <div class="flex items-center mb-4">
        <div class="w-14 h-14 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center mr-4">
          <i class="fas fa-calendar-alt text-xl"></i>
        </div>
        <div>
          <span class="text-gray-700 font-semibold text-lg">Upcoming Payments</span>
          <?php if ($nearestDate): ?>
          <div class="text-sm text-gray-500">Next: <?php echo date('M j', strtotime($nearestDate)); ?></div>
          <?php endif; ?>
        </div>
      </div>
      
      <div class="text-4xl font-bold mb-3 text-gray-800"><?php echo $upcomingCount; ?></div>
      
      <?php if ($countThisWeek > 0): ?>
      <div class="bg-purple-50 p-2 rounded-lg text-center mb-3">
        <div class="text-purple-600 font-medium"><?php echo $countThisWeek; ?> payment<?php echo $countThisWeek > 1 ? 's' : ''; ?> due this week</div>
      </div>
      <?php endif; ?>
      
      <div class="text-sm <?php echo $trendClass; ?> flex items-center font-medium">
        <i class="fas <?php echo $trendIcon; ?> mr-2"></i> <?php echo $trendText; ?>
      </div>
    </div>
  </div>
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
    <!-- Branch Header with Search and Filters -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3">
            <h4 class="text-lg font-bold text-sidebar-text"><?php echo $branchName; ?> - Expenses</h4>
            
            <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                <i class="fas fa-receipt"></i>
                <?php echo $totalBranchExpenses . " Expense" . ($totalBranchExpenses != 1 ? "s" : ""); ?>
            </span>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="flex flex-col md:flex-row items-start md:items-center gap-3 w-full md:w-auto">
            <!-- Search Input -->
            <div class="relative w-full md:w-64">
                <input type="text" id="searchInput<?php echo $branchId; ?>" 
                       placeholder="Search expenses..." 
                       value="<?php echo htmlspecialchars($searchQuery); ?>"
                       class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                       oninput="debouncedSearch(<?php echo $branchId; ?>)">
                <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
            </div>

            <!-- Filter Dropdown -->
            <div class="relative filter-dropdown">
                <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover"
                        onclick="toggleFilterWindow(<?php echo $branchId; ?>)">
                    <i class="fas fa-filter text-sidebar-accent"></i>
                    <span>Filters</span>
                    <?php if($categoryFilter || $statusFilter): ?>
                        <span class="h-2 w-2 bg-sidebar-accent rounded-full"></span>
                    <?php endif; ?>
                </button>
                
                <!-- Filter Window -->
                <div id="filterWindow<?php echo $branchId; ?>" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
                    <div class="space-y-4">
                        <!-- Category Filter -->
                        <div>
                            <h5 class="text-sm font-medium text-sidebar-text mb-2">Category</h5>
                            <div class="space-y-1">
                                <div class="flex items-center cursor-pointer" onclick="setFilter(<?php echo $branchId; ?>, 'category', '')">
                                    <span class="filter-option <?php echo !$categoryFilter ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?> px-2 py-1 rounded text-sm w-full">
                                        All Categories
                                    </span>
                                </div>
                                <?php 
                                // Fetch unique expense categories for this branch
                                $categoriesQuery = "SELECT DISTINCT category FROM expense_tb WHERE branch_id = $branchId AND appearance = 'visible'";
                                $categoriesResult = $conn->query($categoriesQuery);
                                
                                while($category = $categoriesResult->fetch_assoc()): 
                                    $isActive = $categoryFilter === $category['category'];
                                ?>
                                    <div class="flex items-center cursor-pointer" onclick="setFilter(<?php echo $branchId; ?>, 'category', '<?php echo urlencode($category['category']); ?>')">
                                        <span class="filter-option <?php echo $isActive ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?> px-2 py-1 rounded text-sm w-full">
                                            <?php echo htmlspecialchars($category['category']); ?>
                                        </span>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                        
                        <!-- Status Filter -->
                        <div>
                            <h5 class="text-sm font-medium text-sidebar-text mb-2">Status</h5>
                            <div class="space-y-1">
                                <div class="flex items-center cursor-pointer" onclick="setFilter(<?php echo $branchId; ?>, 'status', '')">
                                    <span class="filter-option <?php echo !$statusFilter ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?> px-2 py-1 rounded text-sm w-full">
                                        All Statuses
                                    </span>
                                </div>
                                
                                <?php 
                                // Expense statuses
                                $statuses = ['paid', 'to be paid'];
                                
                                foreach($statuses as $status): 
                                    $isActive = $statusFilter === $status;
                                ?>
                                    <div class="flex items-center cursor-pointer" onclick="setFilter(<?php echo $branchId; ?>, 'status', '<?php echo urlencode($status); ?>')">
                                        <span class="filter-option <?php echo $isActive ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?> px-2 py-1 rounded text-sm w-full">
                                            <?php echo ucfirst($status); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap" 
                    onclick="openAddExpenseModal(<?php echo $branchId; ?>)">
                <i class="fas fa-plus-circle"></i> Add Expense
            </button>
        </div>
    </div>
    
    <!-- Services Table for this branch -->
    <div class="overflow-x-auto scrollbar-thin" id="tableContainer<?php echo $branchId; ?>">
        <div id="loadingIndicator<?php echo $branchId; ?>" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
        </div>
        
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-sidebar-border">
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 0)">
                        <div class="flex items-center">
                            <i class="fas fa-hashtag mr-1.5 text-sidebar-accent"></i> ID 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 1)">
                        <div class="flex items-center">
                            <i class="fas fa-file-invoice-dollar mr-1.5 text-sidebar-accent"></i> Expense Name 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 2)">
                        <div class="flex items-center">
                            <i class="fas fa-th-list mr-1.5 text-sidebar-accent"></i> Category 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 3)">
                        <div class="flex items-center">
                            <i class="fas fa-dollar-sign mr-1.5 text-sidebar-accent"></i> Amount 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 4)">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt mr-1.5 text-sidebar-accent"></i> Date 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 5)">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle mr-1.5 text-sidebar-accent"></i> Status 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text">
                        <div class="flex items-center">
                            <i class="fas fa-cogs mr-1.5 text-sidebar-accent"></i> Actions
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
                            <td class="p-4 text-sm text-sidebar-text font-medium">#EXP-<?php echo str_pad($expense['expense_ID'], 3, "0", STR_PAD_LEFT); ?></td>
                            <td class="p-4 text-sm text-sidebar-text"><?php echo htmlspecialchars($expense['expense_name']); ?></td>
                            <td class="p-4 text-sm text-sidebar-text">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                    <?php echo htmlspecialchars($expense['category']); ?>
                                </span>
                            </td>
                            <td class="p-4 text-sm font-medium text-sidebar-text">$<?php echo number_format($expense['price'], 2); ?></td>
                            <td class="p-4 text-sm text-sidebar-text"><?php echo $expense['date']; ?></td>
                            <td class="p-4 text-sm">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                    <i class="fas <?php echo $statusIcon; ?> mr-1"></i> <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td class="p-4 text-sm">
                                <div class="flex space-x-2">
                                    <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="Edit Expense" 
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
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="p-6 text-sm text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-500">No expenses found for this branch</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
            <div class="text-sm text-gray-500">
                Showing <?php echo ($branchOffset + 1) . ' - ' . min($branchOffset + $recordsPerPage, $totalBranchExpenses); ?> 
                of <?php echo $totalBranchExpenses; ?> expenses
            </div>
            <div class="flex space-x-1">
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $branchPage <= 1 ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                        onclick="changeBranchPage(<?php echo $branchId; ?>, <?php echo $branchPage - 1; ?>)" 
                        <?php echo $branchPage <= 1 ? 'disabled' : ''; ?>>&laquo;</button>
                
                <?php for ($i = 1; $i <= $totalBranchPages; $i++): ?>
                    <button class="px-3 py-1 border border-sidebar-border rounded text-sm <?php echo $i == $branchPage ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?>" 
                            onclick="changeBranchPage(<?php echo $branchId; ?>, <?php echo $i; ?>)"><?php echo $i; ?></button>
                <?php endfor; ?>
                
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $branchPage >= $totalBranchPages ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                        onclick="changeBranchPage(<?php echo $branchId; ?>, <?php echo $branchPage + 1; ?>)" 
                        <?php echo $branchPage >= $totalBranchPages ? 'disabled' : ''; ?>>&raquo;</button>
            </div>
        </div>
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
<div id="addExpenseModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeAddExpenseModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-4 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        <i class="fas fa-plus-circle mr-2"></i>
        Add New Expense
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-4">
      <form id="expenseForm" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Left Column -->
        <div class="space-y-3">
          <div>
            <label for="expenseDescription" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-tag mr-2 text-sidebar-accent"></i>
              Expense Name
            </label>
            <input type="text" id="expenseDescription" name="expenseDescription" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
          </div>
          
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="expenseCategory" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                <i class="fas fa-th-list mr-2 text-sidebar-accent"></i>
                Category
              </label>
              <select id="expenseCategory" name="expenseCategory" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                <option value="" disabled selected>Select category</option>
                <option value="Supplies">Supplies</option>
                <option value="Utilities">Utilities</option>
                <option value="Salaries">Salaries</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Other">Other</option>
              </select>
            </div>
            
            <div>
              <label for="expenseDate" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                <i class="fas fa-calendar mr-2 text-sidebar-accent"></i>
                Date
              </label>
              <input type="date" id="expenseDate" name="expenseDate" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
            </div>
          </div>
          
          <div>
            <label for="expenseAmount" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-coins mr-2 text-sidebar-accent"></i>
              Amount
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500">₱</span>
              </div>
              <input type="number" id="expenseAmount" name="expenseAmount" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
            </div>
          </div>
          
          <div>
            <label for="expenseNote" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-sticky-note mr-2 text-sidebar-accent"></i>
              Note
            </label>
            <textarea id="expenseNote" name="expenseNote" rows="3" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"></textarea>
          </div>
          
          <div>
            <label for="expenseReceipt" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-file-invoice mr-2 text-sidebar-accent"></i>
              Upload Receipt
            </label>
            <div class="flex items-center border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-1 focus-within:ring-sidebar-accent focus-within:border-sidebar-accent transition-all duration-200">
              <i class="fas fa-upload text-gray-400 mr-2"></i>
              <input type="file" id="expenseReceipt" name="expenseReceipt" class="w-full focus:outline-none">
            </div>
          </div>
        </div>
        
        <!-- Right Column -->
        <div class="space-y-3">
          <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-check-circle mr-2 text-sidebar-accent"></i>
              Status
            </label>
            <div class="grid grid-cols-2 gap-2">
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="radio" id="statusPaid" name="expenseStatus" value="paid" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent" checked>
                <i class="fas fa-check-circle mr-1 text-sidebar-accent"></i>
                Paid
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="radio" id="statusToBePaid" name="expenseStatus" value="to be paid" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-clock mr-1 text-sidebar-accent"></i>
                To Be Paid
              </label>
            </div>
          </div>
          
          <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-money-bill-wave mr-2 text-sidebar-accent"></i>
              Payment Method
            </label>
            <div class="grid grid-cols-2 gap-2">
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="radio" id="methodCash" name="paymentMethod" value="cash" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent" checked>
                <i class="fas fa-money-bill-alt mr-1 text-sidebar-accent"></i>
                Cash
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="radio" id="methodCredit" name="paymentMethod" value="credit" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-credit-card mr-1 text-sidebar-accent"></i>
                Credit Card
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="radio" id="methodTransfer" name="paymentMethod" value="transfer" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-exchange-alt mr-1 text-sidebar-accent"></i>
                Bank Transfer
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="radio" id="methodOther" name="paymentMethod" value="other" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-ellipsis-h mr-1 text-sidebar-accent"></i>
                Other
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
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-6 py-3 flex justify-end gap-3 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-4 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center" onclick="closeAddExpenseModal()">
        <i class="fas fa-times mr-2"></i>
        Cancel
      </button>
      <button class="px-5 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="addExpense()">
        <i class="fas fa-plus mr-2"></i>
        Add Expense
      </button>
    </div>
  </div>
</div>

<!-- Modal for Editing Expense -->
<div id="editExpenseModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-lg mx-4 z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeEditExpenseModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-4 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        <i class="fas fa-edit mr-2"></i>
        Edit Expense
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-4">
      <form id="editExpenseForm" class="space-y-4">
        <input type="hidden" id="editExpenseId" name="editExpenseId">
        
        <div>
          <label for="editExpenseDescription" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            <i class="fas fa-tag mr-2 text-sidebar-accent"></i>
            Expense Name
          </label>
          <input type="text" id="editExpenseDescription" name="editExpenseDescription" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
        </div>
        
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label for="editExpenseCategory" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-th-list mr-2 text-sidebar-accent"></i>
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
          
          <div>
            <label for="editExpenseDate" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-calendar mr-2 text-sidebar-accent"></i>
              Date
            </label>
            <input type="date" id="editExpenseDate" name="editExpenseDate" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
          </div>
        </div>
        
        <div>
          <label for="editExpenseAmount" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            <i class="fas fa-coins mr-2 text-sidebar-accent"></i>
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
            <i class="fas fa-check-circle mr-2 text-sidebar-accent"></i>
            Status
          </label>
          <div class="grid grid-cols-2 gap-2">
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="editStatusPaid" name="editExpenseStatus" value="paid" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              <i class="fas fa-check-circle mr-1 text-sidebar-accent"></i>
              Paid
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="editStatusToBePaid" name="editExpenseStatus" value="to be paid" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              <i class="fas fa-clock mr-1 text-sidebar-accent"></i>
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
            <i class="fas fa-sticky-note mr-2 text-sidebar-accent"></i>
            Note
          </label>
          <textarea id="editExpenseNote" name="editExpenseNote" rows="3" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"></textarea>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-6 py-3 flex justify-end gap-3 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-4 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center" onclick="closeEditExpenseModal()">
        <i class="fas fa-times mr-2"></i>
        Cancel
      </button>
      <button class="px-5 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="saveExpenseChanges()">
        <i class="fas fa-save mr-2"></i>
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
  <script src="script.js"></script>
  <script src="tailwind.js"></script>
</body>
</html>