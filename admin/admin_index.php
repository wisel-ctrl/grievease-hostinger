<?php
session_start();

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

  // Get sum of expenses for current month
  $expensesQuery = "SELECT SUM(price) as total_expenses FROM expense_tb 
                   WHERE branch_id = ? AND MONTH(date) = ? AND YEAR(date) = ?";
  $stmt = $conn->prepare($expensesQuery);
  $stmt->bind_param("iii", $branchId, $currentMonth, $currentYear);
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
$monthLabels = [];

for ($i = 11; $i >= 0; $i--) {
    $date = new DateTime();
    $date->modify("-$i months");
    $month = $date->format('m');
    $year = $date->format('Y');
    
    // Save the month name for our labels
    $monthLabels[] = $date->format('M');
    
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
    $date = new DateTime();
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
    $date = new DateTime();
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

// Generate month labels
$monthLabels = [];
for ($i = 11; $i >= 0; $i--) {
    $date = new DateTime();
    $date->modify("-$i months");
    $monthLabels[] = $date->format('M Y');
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
      <h3 class="font-medium text-sidebar-text">Services by Type</h3>
      <button class="px-3 py-2 border border-sidebar-border rounded-md text-sm flex items-center text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-download mr-2"></i> Export
      </button>
    </div>
    <div class="p-4 sm:p-5">
      <div class="w-full h-48 md:h-64">
        <canvas id="servicesChart" style="width: 100%; height: 100%;"></canvas>
      </div>
    </div>
  </div>
  
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 w-full">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 p-4 sm:p-5 border-b border-sidebar-border">
      <h3 class="font-medium text-sidebar-text">Monthly Revenue</h3>
      <button class="px-3 py-2 border border-sidebar-border rounded-md text-sm flex items-center text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-download mr-2"></i> Export
      </button>
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
      <button class="px-3 py-2 border border-sidebar-border rounded-md text-sm flex items-center text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-download mr-2"></i> Export
      </button>
    </div>
    <div class="p-4 sm:p-5">
    <div class="w-full" style="min-height: 300px; height: 60vh; max-height: 500px;">
        <div id="branchRevenueChart" style="width: 100%; height: 100%;"></div>
      </div>
    </div>
  </div>
  
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 w-full">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 p-4 sm:p-5 border-b border-sidebar-border">
      <h3 class="font-medium text-sidebar-text">Services by Type & Branch</h3>
      <button class="px-3 py-2 border border-sidebar-border rounded-md text-sm flex items-center text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-download mr-2"></i> Export
      </button>
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
            
            <!-- Controls for big screens - aligned right -->
            <div class="hidden lg:flex items-center gap-3">
                <!-- Filter Dropdown -->
                <div class="relative filter-dropdown">
                    <button id="branchFilterToggle" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
                        <i class="fas fa-filter text-sidebar-accent"></i>
                        <span>Filters</span>
                        <?php if(isset($sortFilter) && $sortFilter): ?>
                            <span class="h-2 w-2 bg-sidebar-accent rounded-full"></span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Filter Window -->
                    <div id="branchFilterDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
                        <div class="space-y-4">
                            <!-- Sort Options -->
                            <div>
                                <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                                <div class="space-y-1">
                                    <div class="flex items-center cursor-pointer" data-sort="branch_asc">
                                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Branch: A-Z
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer" data-sort="branch_desc">
                                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Branch: Z-A
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer" data-sort="revenue_high">
                                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Revenue: High to Low
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer" data-sort="revenue_low">
                                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Revenue: Low to High
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer" data-sort="profit_high">
                                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Profit: High to Low
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer" data-sort="profit_low">
                                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Profit: Low to High
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer" data-sort="growth_high">
                                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Growth: High to Low
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer" data-sort="growth_low">
                                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Growth: Low to High
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Export Button (styled like the Add button) -->
                <button class="px-4 py-2 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap" 
                        onclick="exportBranchData()">
                    <i class="fas fa-download"></i> <span>Export</span>
                </button>
            </div>
        </div>
        
        <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
        <div class="lg:hidden w-full mt-4">
            <!-- First row: Filter and Export icons -->
            <div class="flex items-center w-full gap-3 mb-4">
                <!-- Filter Dropdown - Takes most of the space -->
                <div class="relative flex-grow filter-dropdown">
                    <button id="mobileBranchFilterToggle" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm flex items-center justify-between hover:bg-sidebar-hover">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-filter text-sidebar-accent"></i>
                            <span>Filters</span>
                        </div>
                        <i class="fas fa-chevron-down text-gray-400"></i>
                    </button>
                    
                    <!-- Filter Window - Positioned below the button -->
                    <div id="mobileBranchFilterDropdown" class="hidden absolute left-0 right-0 mt-2 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
                        <div class="space-y-4">
                            <!-- Sort Options -->
                            <div>
                                <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                                <div class="space-y-2">
                                    <div class="flex items-center cursor-pointer" data-sort="branch_asc">
                                        <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                                            Branch: A-Z
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer" data-sort="branch_desc">
                                        <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                                            Branch: Z-A
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer" data-sort="revenue_high">
                                        <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                                            Revenue: High to Low
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer" data-sort="revenue_low">
                                        <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                                            Revenue: Low to High
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second row: Export Button - Full width -->
            <div class="w-full">
                <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap w-full justify-center" 
                        onclick="exportBranchData()">
                    <i class="fas fa-download"></i> <span>Export Branch Data</span>
                </button>
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
                    <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                        <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">Downtown Branch</td>
                        <td class="px-4 py-3.5 text-sm text-sidebar-text">27</td>
                        <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">$52,380</td>
                        <td class="px-4 py-3.5 text-sm text-sidebar-text">$19,240</td>
                        <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">$33,140</td>
                        <td class="px-4 py-3.5 text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-600 border border-green-200">
                                <i class="fas fa-arrow-up mr-1"></i> 12%
                            </span>
                        </td>
                    </tr>
                    <tr class="hover:bg-sidebar-hover transition-colors">
                        <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">Westside Branch</td>
                        <td class="px-4 py-3.5 text-sm text-sidebar-text">15</td>
                        <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">$34,940</td>
                        <td class="px-4 py-3.5 text-sm text-sidebar-text">$15,280</td>
                        <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">$19,660</td>
                        <td class="px-4 py-3.5 text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-600 border border-green-200">
                                <i class="fas fa-arrow-up mr-1"></i> 8%
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Sticky Pagination Footer with improved spacing -->
    <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
        <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
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

  <!-- Upcoming Services Table -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
  <!-- Header Section - Made responsive with better stacking -->
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
    <!-- Desktop layout for big screens - Title on left, controls on right -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
      <!-- Title and Counter -->
      <div class="flex items-center gap-3 mb-4 lg:mb-0">
        <h3 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Upcoming Services</h3>
        
        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
          <i class="fas fa-clipboard-list"></i>
          25 Services
        </span>
      </div>
      
      <!-- Controls for big screens - aligned right -->
      <div class="hidden lg:flex items-center gap-3">
        <!-- Search Input -->
        <div class="relative">
          <input type="text" id="servicesSearchInput" 
                placeholder="Search services..." 
                class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
          <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
        </div>

        <!-- Filter Dropdown -->
        <div class="relative filter-dropdown">
          <button id="servicesFilterToggle" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
            <i class="fas fa-filter text-sidebar-accent"></i>
            <span>Filters</span>
            <?php if(isset($sortFilter) && $sortFilter): ?>
              <span class="h-2 w-2 bg-sidebar-accent rounded-full"></span>
            <?php endif; ?>
          </button>
          
          <!-- Filter Window -->
          <div id="servicesFilterDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
            <div class="space-y-4">
              <!-- Sort Options -->
              <div>
                <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                <div class="space-y-1">
                  <div class="flex items-center cursor-pointer" data-sort="id_asc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      ID: Ascending
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="id_desc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      ID: Descending
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="name_asc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      Client: A-Z
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="name_desc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      Client: Z-A
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="date_asc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      Date: Earliest First
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="date_desc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      Date: Latest First
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
    <div class="lg:hidden w-full mt-4">
      <!-- First row: Search bar with filter and archive icons on the right -->
      <div class="flex items-center w-full gap-3 mb-4">
        <!-- Search Input - Takes most of the space -->
        <div class="relative flex-grow">
          <input type="text" id="mobileServicesSearchInput" 
                  placeholder="Search services..." 
                  class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
          <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>

        <!-- Icon-only buttons for filter and archive -->
        <div class="flex items-center gap-3">
          <!-- Filter Icon Button -->
          <div class="relative filter-dropdown">
            <button id="mobileServicesFilterToggle" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
              <i class="fas fa-filter text-xl"></i>
              <span id="filterIndicator" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
            </button>
            
            <!-- Filter Window - Positioned below the icon -->
            <div id="mobileServicesFilterDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
              <div class="space-y-4">
                <!-- Sort Options -->
                <div>
                  <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                  <div class="space-y-2">
                    <div class="flex items-center cursor-pointer" data-sort="id_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        ID: Ascending
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="id_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        ID: Descending
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="name_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        Client: A-Z
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="name_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        Client: Z-A
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        Date: Earliest First
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        Date: Latest First
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Archive Icon Button -->
          <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
            <i class="fas fa-archive text-xl"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Responsive Table Container with improved spacing -->
  <div class="overflow-x-auto scrollbar-thin" id="servicesTableContainer">
    <div id="servicesLoadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
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
                <i class="fas fa-user text-sidebar-accent"></i> Client Name
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(2)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-th-list text-sidebar-accent"></i> Service Type
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(3)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-calendar-alt text-sidebar-accent"></i> Date
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(4)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-map-marker-alt text-sidebar-accent"></i> Location
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(5)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-toggle-on text-sidebar-accent"></i> Status
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-cogs text-sidebar-accent"></i> Actions
              </div>
            </th>
          </tr>
        </thead>
        <tbody id="servicesTableBody">
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#FNS-2023001</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Robert Johnson</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                Memorial Service
              </span>
            </td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Mar 8, 2025</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">St. Mary's Chapel</td>
            <td class="px-4 py-3.5 text-sm">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                <i class="fas fa-clock mr-1"></i> Pending
              </span>
            </td>
            <td class="px-4 py-3.5 text-sm">
              <div class="flex space-x-2">
                <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="p-1.5 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 transition-all" title="Edit Service">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#FNS-2023002</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Emily Williams</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                Funeral Service
              </span>
            </td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Mar 10, 2025</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Oak Hill Cemetery</td>
            <td class="px-4 py-3.5 text-sm">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                <i class="fas fa-clock mr-1"></i> Pending
              </span>
            </td>
            <td class="px-4 py-3.5 text-sm">
              <div class="flex space-x-2">
                <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="p-1.5 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 transition-all" title="Edit Service">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#FNS-2023003</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Michael Davis</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                Visitation
              </span>
            </td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Mar 7, 2025</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Grace Funeral Home</td>
            <td class="px-4 py-3.5 text-sm">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-600 border border-green-200">
                <i class="fas fa-check-circle mr-1"></i> Confirmed
              </span>
            </td>
            <td class="px-4 py-3.5 text-sm">
              <div class="flex space-x-2">
                <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="p-1.5 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 transition-all" title="Edit Service">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#FNS-2023004</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Sarah Thompson</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                Cremation Service
              </span>
            </td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Mar 12, 2025</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Riverside Crematorium</td>
            <td class="px-4 py-3.5 text-sm">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                <i class="fas fa-clock mr-1"></i> Pending
              </span>
            </td>
            <td class="px-4 py-3.5 text-sm">
              <div class="flex space-x-2">
                <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="p-1.5 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 transition-all" title="Edit Service">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr class="hover:bg-sidebar-hover transition-colors">
            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#FNS-2023005</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">David Miller</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                Memorial Service
              </span>
            </td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Mar 15, 2025</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Lakeside Gardens</td>
            <td class="px-4 py-3.5 text-sm">
              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-600 border border-red-200">
                <i class="fas fa-times-circle mr-1"></i> Cancelled
              </span>
            </td>
            <td class="px-4 py-3.5 text-sm">
              <div class="flex space-x-2">
                <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="p-1.5 bg-yellow-100 text-yellow-600 rounded hover:bg-yellow-200 transition-all" title="Edit Service">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Sticky Pagination Footer with improved spacing -->
  <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
      Showing 1 - 5 of 25 services
    </div>
    <div class="flex space-x-2">
      <a href="<?php echo '?page=' . max(1, $page - 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $page <= 1 ? 'opacity-50 pointer-events-none' : ''; ?>">&laquo;</a>
      
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="<?php echo '?page=' . $i; ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm <?php echo $i == $page ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?>">
          <?php echo $i; ?>
        </a>
      <?php endfor; ?>
      
      <a href="<?php echo '?page=' . min($totalPages, $page + 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $page >= $totalPages ? 'opacity-50 pointer-events-none' : ''; ?>">&raquo;</a>
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
          <i class="fas fa-clipboard-list"></i>
          18 Activities
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
    <div id="inventoryLoadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
      <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
    </div>
    
    <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
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
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-cogs text-sidebar-accent"></i> Actions
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Premium Casket</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">CSK-001</td>
            <td class="px-4 py-3.5 text-sm">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600 border border-red-200">
                <i class="fas fa-exclamation-circle mr-1"></i> Depleted
              </span>
            </td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Mar 15, 2025</td>
            <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">0</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Downtown</td>
            <td class="px-4 py-3.5 text-sm">
              <div class="flex space-x-2">
                <button class="p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 transition-all tooltip" title="Add Stock">
                  <i class="fas fa-plus"></i>
                </button>
                <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Prayer Cards</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">PRC-011</td>
            <td class="px-4 py-3.5 text-sm">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-600 border border-yellow-200">
                <i class="fas fa-exclamation-triangle mr-1"></i> Low Stock
              </span>
            </td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Mar 14, 2025</td>
            <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">25</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Westside</td>
            <td class="px-4 py-3.5 text-sm">
              <div class="flex space-x-2">
                <button class="p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 transition-all tooltip" title="Add Stock">
                  <i class="fas fa-plus"></i>
                </button>
                <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Cremation Urns</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">URN-032</td>
            <td class="px-4 py-3.5 text-sm">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-600 border border-green-200">
                <i class="fas fa-check-circle mr-1"></i> Restocked
              </span>
            </td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Mar 13, 2025</td>
            <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">+15</td>
            <td class="px-4 py-3.5 text-sm text-sidebar-text">Downtown</td>
            <td class="px-4 py-3.5 text-sm">
              <div class="flex space-x-2">
                <button class="p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 transition-all tooltip" title="Add Stock">
                  <i class="fas fa-plus"></i>
                </button>
                <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Sticky Pagination Footer with improved spacing -->
  <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div class="text-sm text-gray-500 text-center sm:text-left">
      Showing 1 - 3 of 18 activities
    </div>
    <div class="flex space-x-2">
      <button class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 cursor-not-allowed" disabled>&laquo;</button>
      
      <button class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm bg-sidebar-accent text-white">1</button>
      <button class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">2</button>
      <button class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">3</button>
      
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
            polygons: {
                strokeColors: '#e8e8e8',
                fill: {
                    colors: ['#f8f8f8', '#fff']
                }
            }
        }
    },
    responsive: [{
        breakpoint: 768,
        options: {
            legend: {
                position: 'bottom'
            },
            chart: {
                height: 400
            }
        }
    }]
};

var branchServicesChart = new ApexCharts(document.querySelector("#branchServicesChart"), branchServicesOptions);
branchServicesChart.render();
</script>
  </body>
</html>