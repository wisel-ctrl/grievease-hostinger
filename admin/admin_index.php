<?php
session_start();
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

// Get revenue this month
$revenueQuery = "SELECT SUM(amount_paid) as total_revenue FROM sales_tb 
                WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
$stmt = $conn->prepare($revenueQuery);
$stmt->bind_param("ii", $currentMonth, $currentYear);
$stmt->execute();
$revenueResult = $stmt->get_result();
$revenueData = $revenueResult->fetch_assoc();
$totalRevenue = $revenueData['total_revenue'] ?? 0;

// Get revenue last month
$prevRevenueQuery = "SELECT SUM(amount_paid) as total_revenue FROM sales_tb 
                    WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
$stmt = $conn->prepare($prevRevenueQuery);
$stmt->bind_param("ii", $prevMonth, $prevYear);
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
$pendingQuery = "SELECT COUNT(*) as pending_count FROM sales_tb 
                WHERE status = 'Pending' AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
$stmt = $conn->prepare($pendingQuery);
$stmt->bind_param("ii", $currentMonth, $currentYear);
$stmt->execute();
$pendingResult = $stmt->get_result();
$pendingData = $pendingResult->fetch_assoc();
$pendingCount = $pendingData['pending_count'] ?? 0;

// Get pending services count last month
$prevPendingQuery = "SELECT COUNT(*) as pending_count FROM sales_tb 
                    WHERE status = 'Pending' AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
$stmt = $conn->prepare($prevPendingQuery);
$stmt->bind_param("ii", $prevMonth, $prevYear);
$stmt->execute();
$prevPendingResult = $stmt->get_result();
$prevPendingData = $prevPendingResult->fetch_assoc();
$prevPendingCount = $prevPendingData['pending_count'] ?? 0;

// Calculate pending percentage change
$pendingChange = 0;
if ($prevPendingCount > 0) {
    $pendingChange = (($pendingCount - $prevPendingCount) / $prevPendingCount) * 100;
}

// Get completed services count this month
$completedQuery = "SELECT COUNT(*) as completed_count FROM sales_tb 
                  WHERE status = 'Completed' AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
$stmt = $conn->prepare($completedQuery);
$stmt->bind_param("ii", $currentMonth, $currentYear);
$stmt->execute();
$completedResult = $stmt->get_result();
$completedData = $completedResult->fetch_assoc();
$completedCount = $completedData['completed_count'] ?? 0;

// Get completed services count last month
$prevCompletedQuery = "SELECT COUNT(*) as completed_count FROM sales_tb 
                      WHERE status = 'Completed' AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
$stmt = $conn->prepare($prevCompletedQuery);
$stmt->bind_param("ii", $prevMonth, $prevYear);
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
$monthLabels = [];
$currentDate = new DateTime(); // Get current date
$currentDate->modify('first day of this month'); // Start from beginning of current month

for ($i = 11; $i >= 0; $i--) {
    $date = clone $currentDate;
    $date->modify("-$i months");
    $monthLabels[] = $date->format('M Y');
}

// Get monthly projected income data (sum of discounted_price) for the last 12 months
$monthlyProjectedIncomeData = [];

for ($i = 11; $i >= 0; $i--) {
    $date = clone $currentDate;
    $date->modify("-$i months");
    $month = $date->format('m');
    $year = $date->format('Y');
    
    
    $query = "SELECT SUM(discounted_price) as projected_income FROM sales_tb 
              WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $monthlyProjectedIncomeData[] = $data['projected_income'] ?? 0;
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

  // Get current month revenue (sum of amount_paid)
  $revenueQuery = "SELECT SUM(amount_paid) as total_revenue FROM sales_tb 
                  WHERE branch_id = ? AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
  $stmt = $conn->prepare($revenueQuery);
  $stmt->bind_param("iii", $branchId, $currentMonth, $currentYear);
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
$monthlyRevenueData = [];


for ($i = 11; $i >= 0; $i--) {
    $date = clone $currentDate;
    $date->modify("-$i months");
    $month = $date->format('m');
    $year = $date->format('Y');
    
    
    
    
    $query = "SELECT SUM(amount_paid) as revenue FROM sales_tb 
              WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $month, $year);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    $monthlyRevenueData[] = $data['revenue'] ?? 0;
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
    
    $query = "SELECT SUM(amount_paid) as revenue FROM sales_tb 
              WHERE branch_id = 2 AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $month, $year);
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
    
    $query = "SELECT SUM(amount_paid) as revenue FROM sales_tb 
              WHERE branch_id = 1 AND MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $month, $year);
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
  
</head>
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
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-bell"></i>
      </button>
    </div>
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
      <h3 class="font-medium text-sidebar-text">Supposed Revenue</h3>
      
    </div>
    <div class="p-4 sm:p-5">
      <div class="w-full h-48 md:h-64">
        <div id="projectedIncomeChart" style="width: 100%; height: 100%;"></div>
      </div>
    </div>
  </div>
  
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 w-full">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 p-4 sm:p-5 border-b border-sidebar-border">
      <h3 class="font-medium text-sidebar-text">Monthly Revenue</h3>
      
    </div>
    <div class="p-4 sm:p-5">
      <div class="w-full h-48 md:h-64">
        <!-- <canvas id="revenueChart" style="width: 100%; height: 100%;"></canvas> -->
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
                                    COALESCE(s.service_count, 0) AS service_count,
                                    COALESCE(s.revenue, 0) AS revenue,
                                    COALESCE(e.expenses, 0) AS expenses,
                                    COALESCE(s.capital_total, 0) AS capital_total,
                                    (COALESCE(s.revenue, 0) - (COALESCE(s.capital_total, 0) + COALESCE(e.expenses, 0))) AS profit,
                                    CASE 
                                        WHEN COALESCE(s.revenue, 0) > 0 THEN 
                                            (COALESCE(s.revenue, 0) - (COALESCE(s.capital_total, 0) + COALESCE(e.expenses, 0))) / COALESCE(s.revenue, 0) * 100
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
                                    b.branch_id IN (1, 2)
                                ORDER BY 
                                    b.branch_name ASC
                                ";
                
                $stmt = $conn->prepare($branchQuery);
                $stmt->bind_param("iiiis", $currentMonth, $currentYear, $currentMonth, $currentYear, $visible);
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
    data: <?php echo json_encode($monthlyRevenueData); ?>
  }],
  chart: {
    type: 'area',
    height: '100%',
    width: '100%',
    animations: {
      enabled: true,
      easing: 'easeout',
      speed: 800
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
    categories: <?php echo json_encode($monthLabels); ?>,
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
</script>
<script>
// Projected Income Chart (using discounted_price instead of amount_paid)
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
        download: true,
        selection: true,
        zoom: true,
        zoomin: true,
        zoomout: true,
        pan: true,
        reset: true
      },
      export: {
        csv: {
          filename: 'projected-income',
          columnDelimiter: ',',
          headerCategory: 'Month',
          headerValue: 'Projected Income (₱)',
        },
        png: {
          filename: 'projected-income',
        },
        svg: {
          filename: 'projected-income',
        }
      }
    }
  },
  colors: ['#3b82f6'], // Different color from revenue chart
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
  </body>
</html>