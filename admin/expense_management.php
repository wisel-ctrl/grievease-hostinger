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
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-lg shadow-sidebar p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
        <div class="flex items-center mb-3">
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mr-3">
            <i class="fas fa-peso-sign text-lg"></i>

            </div>
            <span class="text-sidebar-text font-medium">Total Expenses</span>
        </div>
        <div class="text-3xl font-bold mb-2 text-sidebar-text">₱<?php echo $totalAmount; ?></div>
        <div class="text-sm <?php echo $trendClass; ?> flex items-center">
            <i class="fas <?php echo $trendIcon; ?> mr-1"></i> <?php echo $trendText; ?>
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

      <div class="bg-white rounded-lg shadow-sidebar p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
          <div class="flex items-center mb-3">
              <div class="w-12 h-12 rounded-lg bg-yellow-100 text-yellow-600 flex items-center justify-center mr-3">
                  <i class="fas fa-wallet text-lg"></i>
              </div>
              <span class="text-sidebar-text font-medium">Monthly Budget Status</span>
          </div>
          
          <div class="flex items-end justify-between mb-2">
              <div class="text-2xl font-bold text-sidebar-text <?php echo $warningLevel; ?>">
                  ₱<?php echo number_format($currentSpend, 2); ?>
              </div>
              <div class="text-sm text-gray-500">of ₱<?php echo number_format($monthlyBudget, 2); ?> budget</div>
          </div>
          
          <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
              <div class="bg-yellow-500 h-3 rounded-full" style="width: <?php echo $percentageUsed; ?>%"></div>
          </div>
          
          <div class="flex justify-between items-center">
              <div class="text-sm <?php echo $daysLeftClass; ?> flex items-center">
                  <i class="fas fa-clock mr-1"></i> <?php echo $daysLeft; ?> days remaining
              </div>
              <div class="text-sm <?php echo $warningLevel; ?>">
                  <?php echo round($percentageUsed); ?>% used
              </div>
          </div>
          
          <?php if ($percentageUsed >= 100): ?>
              <div class="mt-2 text-xs bg-red-100 text-red-800 p-1 rounded text-center">
                  <i class="fas fa-exclamation-circle mr-1"></i> Budget exceeded!
              </div>
          <?php elseif ($percentageUsed >= 90): ?>
              <div class="mt-2 text-xs bg-yellow-100 text-yellow-800 p-1 rounded text-center">
                  <i class="fas fa-exclamation-triangle mr-1"></i> Approaching budget limit
              </div>
          <?php endif; ?>
          
          <!-- Optional: Daily spending rate projection -->
          <?php
          $daysPassed = date('j'); // Current day of month
          $dailyRate = ($daysPassed > 0) ? $currentSpend / $daysPassed : 0;
          $projectedSpend = $dailyRate * date('t');
          $projectionClass = ($projectedSpend > $monthlyBudget) ? 'text-red-600' : 'text-green-600';
          ?>
          <div class="mt-2 text-xs text-gray-500 border-t pt-2">
              Daily rate: ₱<?php echo number_format($dailyRate, 2); ?> | 
              Projected: <span class="<?php echo $projectionClass; ?>">₱<?php echo number_format($projectedSpend, 2); ?></span>
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

      <div class="bg-white rounded-lg shadow-sidebar p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
          <div class="flex items-center mb-3">
              <div class="w-12 h-12 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center mr-3">
                  <i class="fas fa-exclamation-triangle text-lg"></i>
              </div>
              <div>
                  <span class="text-sidebar-text font-medium">Overdue Payments</span>
                  <div class="text-xs text-gray-500">Total: ₱<?php echo number_format($overdueAmount, 2); ?></div>
              </div>
          </div>
          
          <div class="text-3xl font-bold mb-2 text-sidebar-text"><?php echo $overdueCount; ?></div>
          
          <!-- Urgency breakdown -->
          <div class="grid grid-cols-4 gap-2 mb-3 text-xs">
              <div class="text-center">
                  <div class="text-red-600 font-medium"><?php echo $urgencyData['critical'] ?? 0; ?></div>
                  <div class="text-gray-500">30+ days</div>
              </div>
              <div class="text-center">
                  <div class="text-orange-500 font-medium"><?php echo $urgencyData['high'] ?? 0; ?></div>
                  <div class="text-gray-500">15-30 days</div>
              </div>
              <div class="text-center">
                  <div class="text-yellow-500 font-medium"><?php echo $urgencyData['medium'] ?? 0; ?></div>
                  <div class="text-gray-500">7-14 days</div>
              </div>
              <div class="text-center">
                  <div class="text-blue-500 font-medium"><?php echo $urgencyData['low'] ?? 0; ?></div>
                  <div class="text-gray-500">1-6 days</div>
              </div>
          </div>
          
          <div class="text-sm <?php echo $trendClass; ?> flex items-center">
              <i class="fas <?php echo $trendIcon; ?> mr-1"></i> <?php echo $trendText; ?>
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
        $nearestQuery = "SELECT MIN(date) as nearest_date 
                        FROM expense_tb 
                        WHERE appearance = 'visible' 
                        AND status = 'To be paid' 
                        AND date > CURDATE()";
        $nearestResult = $conn->query($nearestQuery);
        $nearestDate = $nearestResult->fetch_assoc()['nearest_date'];
        ?>

        <div class="bg-white rounded-lg shadow-sidebar p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
            <div class="flex items-center mb-3">
                <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center mr-3">
                    <i class="fas fa-calendar-alt text-lg"></i>
                </div>
                <div>
                    <span class="text-sidebar-text font-medium">Upcoming Payments</span>
                    <?php if ($nearestDate): ?>
                    <div class="text-xs text-gray-500">Next due: <?php echo date('M j', strtotime($nearestDate)); ?></div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-3xl font-bold mb-2 text-sidebar-text"><?php echo $upcomingCount; ?></div>
            
            <div class="text-sm <?php echo $trendClass; ?> flex items-center">
                <i class="fas <?php echo $trendIcon; ?> mr-1"></i> <?php echo $trendText; ?>
            </div>
        </div>
  </div></div>

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
<div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
    <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
        <h3 class="font-medium text-sidebar-text">Expenses</h3>
        <button class="bg-sidebar-accent hover:bg-darkgold text-white px-4 py-2 rounded flex items-center text-sm transition-all duration-300" onclick="openAddExpenseModal()">
            <i class="fas fa-plus mr-2"></i> Add Expense
        </button>
    </div>
    <div class="overflow-x-auto scrollbar-thin">
        <?php
        include '../db_connect.php';
        
        // Fetch all branches first
        $branchQuery = "SELECT branch_id, branch_name FROM branch_tb";
        $branchResult = $conn->query($branchQuery);
        
        if ($branchResult->num_rows > 0) {
            while($branch = $branchResult->fetch_assoc()) {
                echo '<div class="mb-8">';
                echo '<h4 class="text-lg font-semibold p-4 bg-gray-100">'.$branch['branch_name'].' Expenses</h4>';
                
                // Fetch expenses for this branch
                // With this:
                $expenseQuery = "SELECT * FROM expense_tb WHERE branch_id = ".$branch['branch_id']." AND appearance = 'visible' ORDER BY date DESC";
                $expenseResult = $conn->query($expenseQuery);
                
                if ($expenseResult->num_rows > 0) {
                    echo '<table class="w-full">';
                    echo '<thead>
                            <tr class="bg-sidebar-hover">
                                <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">
                                    <div class="flex items-center">
                                        ID <i class="fas fa-sort ml-1 text-gray-400"></i>
                                    </div>
                                </th>
                                <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">
                                    <div class="flex items-center">
                                        Expense Name <i class="fas fa-sort ml-1 text-gray-400"></i>
                                    </div>
                                </th>
                                <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">
                                    <div class="flex items-center">
                                        Category <i class="fas fa-sort ml-1 text-gray-400"></i>
                                    </div>
                                </th>
                                <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">
                                    <div class="flex items-center">
                                        Amount <i class="fas fa-sort ml-1 text-gray-400"></i>
                                    </div>
                                </th>
                                <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">
                                    <div class="flex items-center">
                                        Date <i class="fas fa-sort ml-1 text-gray-400"></i>
                                    </div>
                                </th>
                                <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(5)">
                                    <div class="flex items-center">
                                        Status <i class="fas fa-sort ml-1 text-gray-400"></i>
                                    </div>
                                </th>
                                <th class="p-4 text-left text-sm font-medium text-sidebar-text">Actions</th>
                            </tr>
                        </thead>
                        <tbody>';
                    
                    while($expense = $expenseResult->fetch_assoc()) {
                        $statusClass = $expense['status'] == 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
                        $statusText = $expense['status'] == 'paid' ? 'Paid' : 'To be paid';
                        
                        echo '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover">';
                        echo '<td class="p-4 text-sm text-sidebar-text font-medium">#EXP-'.str_pad($expense['expense_ID'], 3, '0', STR_PAD_LEFT).'</td>';
                        echo '<td class="p-4 text-sm text-sidebar-text">'.$expense['expense_name'].'</td>';
                        echo '<td class="p-4 text-sm text-sidebar-text">'.$expense['category'].'</td>';
                        echo '<td class="p-4 text-sm text-sidebar-text">$'.number_format($expense['price'], 2).'</td>';
                        echo '<td class="p-4 text-sm text-sidebar-text">'.$expense['date'].'</td>';
                        echo '<td class="p-4 text-sm"><span class="px-2 py-1 '.$statusClass.' rounded-full text-xs">'.$statusText.'</span></td>';
                        echo '<td class="p-4 text-sm">';
                        echo '<div class="flex space-x-2">';
                        echo '<button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all" onclick="openEditExpenseModal(\'#EXP-'.str_pad($expense['expense_ID'], 3, '0', STR_PAD_LEFT).'\', \''.addslashes($expense['expense_name']).'\', \''.addslashes($expense['category']).'\', \''.$expense['price'].'\', \''.$expense['date'].'\', \''.$expense['branch_id'].'\', \''.$expense['status'].'\', \''.addslashes($expense['notes'] ?? '').'\');">';
                        echo '<i class="fas fa-edit"></i>';
                        echo '</button>';
                        echo '<button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all" onclick="deleteExpense(\'#EXP-'.str_pad($expense['expense_ID'], 3, '0', STR_PAD_LEFT).'\')">';
                        echo '<i class="fas fa-trash"></i>';
                        echo '</button>';
                        echo '</div>';
                        echo '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<div class="p-4 text-center text-sm text-sidebar-text">No expenses found for this branch</div>';
                }
                
                echo '</div>'; // Close branch section
            }
        } else {
            echo '<div class="p-4 text-center text-sm text-sidebar-text">No branches found</div>';
        }
        
        $conn->close();
        ?>
    </div>
</div>

      </div>

<!-- Modal for Adding New Expense -->
<div id="addExpenseModal" class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-xl w-full max-w-md max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 class="text-xl font-bold text-white">Add New Expense</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeAddExpenseModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    
    <!-- Modal Body -->
    <div class="p-6">
      <form id="expenseForm" class="space-y-5">
        <div>
          <label for="expenseDescription" class="block text-sm font-medium text-gray-700 mb-1">Expense Name</label>
          <input type="text" id="expenseDescription" name="expenseDescription" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
          <div class="flex gap-4">
          <?php
          // Include your MySQLi database connection
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
                  echo '<div class="flex items-center">';
                  echo '<input type="radio" id="branch_' . htmlspecialchars($row['branch_id']) . '" 
                        name="expenseBranch" value="' . htmlspecialchars($row['branch_id']) . '" 
                        class="w-4 h-4 text-sidebar-accent focus:ring-sidebar-accent"' . 
                        ($first ? ' checked' : '') . '>';
                  echo '<label for="branch_' . htmlspecialchars($row['branch_id']) . '" 
                        class="ml-2 text-sm text-gray-700">' . 
                        htmlspecialchars($row['branch_name']) . '</label>';
                  echo '</div>';
                  $first = false;
              }
          } else {
              echo '<div class="text-red-500">No branches found</div>';
          }
          $conn->close();
          ?>
          </div>
        </div>

        <div>
          <label for="expenseCategory" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
          <select id="expenseCategory" name="expenseCategory" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
            <option value="" disabled selected>Select a category</option>
            <option value="Supplies">Supplies</option>
            <option value="Utilities">Utilities</option>
            <option value="Salaries">Salaries</option>
            <option value="Maintenance">Maintenance</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div>
          <label for="expenseAmount" class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="expenseAmount" name="expenseAmount" class="w-full pl-8 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
          </div>
        </div>
        <div>
          <label for="expenseDate" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
          <input type="date" id="expenseDate" name="expenseDate" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <div class="flex gap-4">
            <div class="flex items-center">
              <input type="radio" id="statusPaid" name="expenseStatus" value="paid" class="w-4 h-4 text-sidebar-accent focus:ring-sidebar-accent" checked>
              <label for="statusPaid" class="ml-2 text-sm text-gray-700">Paid</label>
            </div>
            <div class="flex items-center">
              <input type="radio" id="statusToBePaid" name="expenseStatus" value="to be paid" class="w-4 h-4 text-sidebar-accent focus:ring-sidebar-accent">
              <label for="statusToBePaid" class="ml-2 text-sm text-gray-700">To Be Paid</label>
            </div>
          </div>
        </div>
        <div>
          <label for="expenseNote" class="block text-sm font-medium text-gray-700 mb-1">Note</label>
          <textarea id="expenseNote" name="expenseNote" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent"></textarea>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeAddExpenseModal()">Cancel</button>
      <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center" onclick="addExpense()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        Add Expense
      </button>
    </div>
  </div>
</div>

<!-- Modal for Editing Expense -->
<div id="editExpenseModal" class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-xl w-full max-w-md max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 class="text-xl font-bold text-white">Edit Expense</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeEditExpenseModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    
    <!-- Modal Body -->
    <div class="p-6">
      <form id="editExpenseForm" class="space-y-5">
        <input type="hidden" id="editExpenseId" name="editExpenseId">
        <div>
          <label for="editExpenseDescription" class="block text-sm font-medium text-gray-700 mb-1">Expense Name</label>
          <input type="text" id="editExpenseDescription" name="editExpenseDescription" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
          <div class="flex gap-4">
          <?php
          // Include your MySQLi database connection
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
                  echo '<div class="flex items-center">';
                  echo '<input type="radio" id="editBranch_' . htmlspecialchars($row['branch_id']) . '" 
                        name="editExpenseBranch" value="' . htmlspecialchars($row['branch_id']) . '" 
                        class="w-4 h-4 text-sidebar-accent focus:ring-sidebar-accent">';
                  echo '<label for="editBranch_' . htmlspecialchars($row['branch_id']) . '" 
                        class="ml-2 text-sm text-gray-700">' . 
                        htmlspecialchars($row['branch_name']) . '</label>';
                  echo '</div>';
              }
          } else {
              echo '<div class="text-red-500">No branches found</div>';
          }
          $conn->close();
          ?>
          </div>
        </div>

        <div>
          <label for="editExpenseCategory" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
          <select id="editExpenseCategory" name="editExpenseCategory" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
            <option value="" disabled selected>Select a category</option>
            <option value="Supplies">Supplies</option>
            <option value="Utilities">Utilities</option>
            <option value="Salaries">Salaries</option>
            <option value="Maintenance">Maintenance</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div>
          <label for="editExpenseAmount" class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="editExpenseAmount" name="editExpenseAmount" class="w-full pl-8 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
          </div>
        </div>
        <div>
          <label for="editExpenseDate" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
          <input type="date" id="editExpenseDate" name="editExpenseDate" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
          <div class="flex gap-4">
            <div class="flex items-center">
              <input type="radio" id="editStatusPaid" name="editExpenseStatus" value="paid" class="w-4 h-4 text-sidebar-accent focus:ring-sidebar-accent">
              <label for="editStatusPaid" class="ml-2 text-sm text-gray-700">Paid</label>
            </div>
            <div class="flex items-center">
              <input type="radio" id="editStatusToBePaid" name="editExpenseStatus" value="to be paid" class="w-4 h-4 text-sidebar-accent focus:ring-sidebar-accent">
              <label for="editStatusToBePaid" class="ml-2 text-sm text-gray-700">To Be Paid</label>
            </div>
          </div>
        </div>
        <div>
          <label for="editExpenseNote" class="block text-sm font-medium text-gray-700 mb-1">Note</label>
          <textarea id="editExpenseNote" name="editExpenseNote" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent"></textarea>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeEditExpenseModal()">Cancel</button>
      <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center" onclick="saveExpenseChanges()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
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