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
        <span class="text-2xl md:text-3xl font-bold text-gray-800">42</span>
      </div>
    </div>
    
    <!-- Card footer with change indicator -->
    <div class="px-6 py-3 bg-white border-t border-gray-100">
      <div class="flex items-center text-emerald-600">
        <i class="fas fa-arrow-up mr-1.5 text-xs"></i>
        <span class="font-medium text-xs">8% </span>
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
        <span class="text-2xl md:text-3xl font-bold text-gray-800">$87,320</span>
      </div>
    </div>
    
    <!-- Card footer with change indicator -->
    <div class="px-6 py-3 bg-white border-t border-gray-100">
      <div class="flex items-center text-emerald-600">
        <i class="fas fa-arrow-up mr-1.5 text-xs"></i>
        <span class="font-medium text-xs">12% </span>
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
        <span class="text-2xl md:text-3xl font-bold text-gray-800">14</span>
      </div>
    </div>
    
    <!-- Card footer with change indicator -->
    <div class="px-6 py-3 bg-white border-t border-gray-100">
      <div class="flex items-center text-rose-600">
        <i class="fas fa-arrow-down mr-1.5 text-xs"></i>
        <span class="font-medium text-xs">3% </span>
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
        <span class="text-2xl md:text-3xl font-bold text-gray-800">28</span>
      </div>
    </div>
    
    <!-- Card footer with change indicator -->
    <div class="px-6 py-3 bg-white border-t border-gray-100">
      <div class="flex items-center text-emerald-600">
        <i class="fas fa-arrow-up mr-1.5 text-xs"></i>
        <span class="font-medium text-xs">15% </span>
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
          <div class="text-xl font-bold text-gray-800">₱67,350</div>
          <div class="mt-2 text-xs flex items-center text-slate-600">
            <i class="fas fa-arrow-up mr-1"></i>
            <span>5.2% vs last month</span>
          </div>
        </div>
        
        <!-- Profit -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center mb-1 text-gray-600 text-sm">
            <i class="fas fa-wallet mr-2"></i>
            <span>Profit</span>
          </div>
          <div class="text-xl font-bold text-gray-800">₱21,550</div>
          <div class="mt-2 text-xs flex items-center text-slate-600">
            <i class="fas fa-arrow-up mr-1"></i>
            <span>3.8% vs last month</span>
          </div>
        </div>
        
        <!-- Margin -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center mb-1 text-gray-600 text-sm">
            <i class="fas fa-percentage mr-2"></i>
            <span>Margin</span>
          </div>
          <div class="text-xl font-bold text-gray-800">32.0%</div>
          <div class="mt-2 text-xs flex items-center text-slate-600">
            <i class="fas fa-arrow-up mr-1"></i>
            <span>1.2% vs last month</span>
          </div>
        </div>
        
        <!-- Customers -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center mb-1 text-gray-600 text-sm">
            <i class="fas fa-users mr-2"></i>
            <span>Customers</span>
          </div>
          <div class="text-xl font-bold text-gray-800">286</div>
          <div class="mt-2 text-xs flex items-center text-slate-600">
            <i class="fas fa-arrow-up mr-1"></i>
            <span>4.0% vs last month</span>
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
          <div class="text-xl font-bold text-gray-800">₱59,100</div>
          <div class="mt-2 text-xs flex items-center text-slate-600">
            <i class="fas fa-arrow-up mr-1"></i>
            <span>3.5% vs last month</span>
          </div>
        </div>
        
        <!-- Profit -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center mb-1 text-gray-600 text-sm">
            <i class="fas fa-wallet mr-2"></i>
            <span>Profit</span>
          </div>
          <div class="text-xl font-bold text-gray-800">₱16,690</div>
          <div class="mt-2 text-xs flex items-center text-slate-600">
            <i class="fas fa-arrow-down mr-1"></i>
            <span>1.2% vs last month</span>
          </div>
        </div>
        
        <!-- Margin -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center mb-1 text-gray-600 text-sm">
            <i class="fas fa-percentage mr-2"></i>
            <span>Margin</span>
          </div>
          <div class="text-xl font-bold text-gray-800">28.2%</div>
          <div class="mt-2 text-xs flex items-center text-slate-600">
            <i class="fas fa-arrow-down mr-1"></i>
            <span>0.5% vs last month</span>
          </div>
        </div>
        
        <!-- Customers -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
          <div class="flex items-center mb-1 text-gray-600 text-sm">
            <i class="fas fa-users mr-2"></i>
            <span>Customers</span>
          </div>
          <div class="text-xl font-bold text-gray-800">245</div>
          <div class="mt-2 text-xs flex items-center text-slate-600">
            <i class="fas fa-arrow-up mr-1"></i>
            <span>2.1% vs last month</span>
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
        <canvas id="revenueChart" style="width: 100%; height: 100%;"></canvas>
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
      <div class="w-full h-48 md:h-64">
        <canvas id="branchRevenueChart" style="width: 100%; height: 100%;"></canvas>
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
      <div class="w-full h-48 md:h-64">
        <canvas id="branchServicesChart" style="width: 100%; height: 100%;"></canvas>
      </div>
    </div>
  </div>
</div>
  
  <!-- Branch Statistics -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3">
            <h4 class="text-lg font-bold text-sidebar-text">Branch Performance</h4>
        </div>
        
        <!-- Search Section -->
        <div class="flex flex-col md:flex-row items-start md:items-center gap-3 w-full md:w-auto">
            <!-- Search Input -->
            <div class="relative w-full md:w-64">
                <input type="text" placeholder="Search..." 
                       class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
            </div>
        </div>
    </div>
    
    <!-- Branch Performance Table -->
    <div class="overflow-x-auto scrollbar-thin">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-sidebar-border">
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">
                        <div class="flex items-center">
                            <i class="fas fa-building mr-1.5 text-sidebar-accent"></i> Branch
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">
                        <div class="flex items-center">
                            <i class="fas fa-clipboard-list mr-1.5 text-sidebar-accent"></i> Services
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">
                        <div class="flex items-center">
                            <i class="fas fa-dollar-sign mr-1.5 text-sidebar-accent"></i> Revenue
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">
                        <div class="flex items-center">
                            <i class="fas fa-credit-card mr-1.5 text-sidebar-accent"></i> Expenses
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">
                        <div class="flex items-center">
                            <i class="fas fa-chart-line mr-1.5 text-sidebar-accent"></i> Profit
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(5)">
                        <div class="flex items-center">
                            <i class="fas fa-percentage mr-1.5 text-sidebar-accent"></i> Growth
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                    <td class="p-4 text-sm text-sidebar-text font-medium">Downtown Branch</td>
                    <td class="p-4 text-sm text-sidebar-text">27</td>
                    <td class="p-4 text-sm font-medium text-sidebar-text">$52,380</td>
                    <td class="p-4 text-sm text-sidebar-text">$19,240</td>
                    <td class="p-4 text-sm font-medium text-sidebar-text">$33,140</td>
                    <td class="p-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-600 border border-green-200">
                            <i class="fas fa-arrow-up mr-1"></i> 12%
                        </span>
                    </td>
                </tr>
                <tr class="hover:bg-sidebar-hover transition-colors">
                    <td class="p-4 text-sm text-sidebar-text font-medium">Westside Branch</td>
                    <td class="p-4 text-sm text-sidebar-text">15</td>
                    <td class="p-4 text-sm font-medium text-sidebar-text">$34,940</td>
                    <td class="p-4 text-sm text-sidebar-text">$15,280</td>
                    <td class="p-4 text-sm font-medium text-sidebar-text">$19,660</td>
                    <td class="p-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-600 border border-green-200">
                            <i class="fas fa-arrow-up mr-1"></i> 8%
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination (similar to first code) -->
    <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
        <div class="text-sm text-gray-500">
            Showing 1 - 2 of 2 branches
        </div>
        <div class="flex space-x-1">
            <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 cursor-not-allowed" disabled>&laquo;</button>
            <button class="px-3 py-1 border border-sidebar-border rounded text-sm bg-sidebar-accent text-white">1</button>
            <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 cursor-not-allowed" disabled>&raquo;</button>
        </div>
    </div>
</div>

  <!-- Upcoming Services Table -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
    <!-- Header with Search and Filters -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3">
            <h3 class="text-lg font-bold text-sidebar-text">Upcoming Services</h3>
            
            <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                <i class="fas fa-clipboard-list"></i>
                25 Services
            </span>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="flex flex-col md:flex-row items-start md:items-center gap-3 w-full md:w-auto">
            <!-- Search Input -->
            <div class="relative w-full md:w-64">
                <input type="text" 
                       placeholder="Search services..." 
                       class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
            </div>
        </div>
    </div>
    
    <!-- Services Table -->
    <div class="overflow-x-auto scrollbar-thin">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-sidebar-border">
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">
                        <div class="flex items-center">
                            <i class="fas fa-hashtag mr-1.5 text-sidebar-accent"></i> ID
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">
                        <div class="flex items-center">
                            <i class="fas fa-user mr-1.5 text-sidebar-accent"></i> Client Name
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">
                        <div class="flex items-center">
                            <i class="fas fa-th-list mr-1.5 text-sidebar-accent"></i> Service Type
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt mr-1.5 text-sidebar-accent"></i> Date
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">
                        <div class="flex items-center">
                            <i class="fas fa-map-marker-alt mr-1.5 text-sidebar-accent"></i> Location
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(5)">
                        <div class="flex items-center">
                            <i class="fas fa-toggle-on mr-1.5 text-sidebar-accent"></i> Status
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
                <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                    <td class="p-4 text-sm text-sidebar-text font-medium">#FNS-2023001</td>
                    <td class="p-4 text-sm text-sidebar-text">Robert Johnson</td>
                    <td class="p-4 text-sm text-sidebar-text">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                            Memorial Service
                        </span>
                    </td>
                    <td class="p-4 text-sm text-sidebar-text">Mar 8, 2025</td>
                    <td class="p-4 text-sm text-sidebar-text">St. Mary's Chapel</td>
                    <td class="p-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                            <i class="fas fa-clock mr-1"></i> Pending
                        </span>
                    </td>
                    <td class="p-4 text-sm">
                        <div class="flex space-x-2">
                            <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip" title="Edit Service">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                    <td class="p-4 text-sm text-sidebar-text font-medium">#FNS-2023002</td>
                    <td class="p-4 text-sm text-sidebar-text">Emily Williams</td>
                    <td class="p-4 text-sm text-sidebar-text">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                            Funeral Service
                        </span>
                    </td>
                    <td class="p-4 text-sm text-sidebar-text">Mar 10, 2025</td>
                    <td class="p-4 text-sm text-sidebar-text">Oak Hill Cemetery</td>
                    <td class="p-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                            <i class="fas fa-clock mr-1"></i> Pending
                        </span>
                    </td>
                    <td class="p-4 text-sm">
                        <div class="flex space-x-2">
                            <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip" title="Edit Service">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                    <td class="p-4 text-sm text-sidebar-text font-medium">#FNS-2023003</td>
                    <td class="p-4 text-sm text-sidebar-text">Michael Davis</td>
                    <td class="p-4 text-sm text-sidebar-text">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                            Visitation
                        </span>
                    </td>
                    <td class="p-4 text-sm text-sidebar-text">Mar 7, 2025</td>
                    <td class="p-4 text-sm text-sidebar-text">Grace Funeral Home</td>
                    <td class="p-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-600 border border-green-200">
                            <i class="fas fa-check-circle mr-1"></i> Confirmed
                        </span>
                    </td>
                    <td class="p-4 text-sm">
                        <div class="flex space-x-2">
                            <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip" title="Edit Service">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                    <td class="p-4 text-sm text-sidebar-text font-medium">#FNS-2023004</td>
                    <td class="p-4 text-sm text-sidebar-text">Sarah Thompson</td>
                    <td class="p-4 text-sm text-sidebar-text">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                            Cremation Service
                        </span>
                    </td>
                    <td class="p-4 text-sm text-sidebar-text">Mar 12, 2025</td>
                    <td class="p-4 text-sm text-sidebar-text">Riverside Crematorium</td>
                    <td class="p-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-200">
                            <i class="fas fa-clock mr-1"></i> Pending
                        </span>
                    </td>
                    <td class="p-4 text-sm">
                        <div class="flex space-x-2">
                            <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip" title="Edit Service">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <tr class="hover:bg-sidebar-hover transition-colors">
                    <td class="p-4 text-sm text-sidebar-text font-medium">#FNS-2023005</td>
                    <td class="p-4 text-sm text-sidebar-text">David Miller</td>
                    <td class="p-4 text-sm text-sidebar-text">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                            Memorial Service
                        </span>
                    </td>
                    <td class="p-4 text-sm text-sidebar-text">Mar 15, 2025</td>
                    <td class="p-4 text-sm text-sidebar-text">Lakeside Gardens</td>
                    <td class="p-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-600 border border-red-200">
                            <i class="fas fa-times-circle mr-1"></i> Cancelled
                        </span>
                    </td>
                    <td class="p-4 text-sm">
                        <div class="flex space-x-2">
                            <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip" title="Edit Service">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
        <div class="text-sm text-gray-500">
            Showing 1 - 5 of 25 services
        </div>
        <div class="flex space-x-1">
            <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 cursor-not-allowed" disabled>&laquo;</button>
            <button class="px-3 py-1 border border-sidebar-border rounded text-sm bg-sidebar-accent text-white">1</button>
            <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">2</button>
            <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">3</button>
            <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&raquo;</button>
        </div>
    </div>
</div>

  <!-- Recent Inventory Activity -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div class="flex items-center gap-3">
      <h3 class="text-lg font-bold text-sidebar-text">Recent Inventory Activity</h3>
      
      <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
          <i class="fas fa-clipboard-list"></i>
          18 Activities
      </span>
    </div>
    
    <a href="inventory_management.php">
      <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap">
        <i class="fas fa-box"></i> Manage Inventory
      </button>
    </a>
  </div>
  
  <div class="overflow-x-auto scrollbar-thin">
    <table class="w-full">
      <thead>
        <tr class="bg-gray-50 border-b border-sidebar-border">
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">
            <div class="flex items-center">
              <i class="fas fa-box mr-1.5 text-sidebar-accent"></i> Item
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">
            <div class="flex items-center">
              <i class="fas fa-barcode mr-1.5 text-sidebar-accent"></i> SKU
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">
            <div class="flex items-center">
              <i class="fas fa-chart-line mr-1.5 text-sidebar-accent"></i> Activity
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">
            <div class="flex items-center">
              <i class="fas fa-calendar-alt mr-1.5 text-sidebar-accent"></i> Date
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">
            <div class="flex items-center">
              <i class="fas fa-cubes mr-1.5 text-sidebar-accent"></i> Quantity
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(5)">
            <div class="flex items-center">
              <i class="fas fa-building mr-1.5 text-sidebar-accent"></i> Branch
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
        <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
          <td class="p-4 text-sm text-sidebar-text">Premium Casket</td>
          <td class="p-4 text-sm text-sidebar-text font-medium">CSK-001</td>
          <td class="p-4 text-sm">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-600 border border-red-200">
              <i class="fas fa-exclamation-circle mr-1"></i> Depleted
            </span>
          </td>
          <td class="p-4 text-sm text-sidebar-text">Mar 15, 2025</td>
          <td class="p-4 text-sm font-medium text-sidebar-text">0</td>
          <td class="p-4 text-sm text-sidebar-text">Downtown</td>
          <td class="p-4 text-sm">
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
          <td class="p-4 text-sm text-sidebar-text">Prayer Cards</td>
          <td class="p-4 text-sm text-sidebar-text font-medium">PRC-011</td>
          <td class="p-4 text-sm">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-600 border border-yellow-200">
              <i class="fas fa-exclamation-triangle mr-1"></i> Low Stock
            </span>
          </td>
          <td class="p-4 text-sm text-sidebar-text">Mar 14, 2025</td>
          <td class="p-4 text-sm font-medium text-sidebar-text">25</td>
          <td class="p-4 text-sm text-sidebar-text">Westside</td>
          <td class="p-4 text-sm">
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
          <td class="p-4 text-sm text-sidebar-text">Cremation Urns</td>
          <td class="p-4 text-sm text-sidebar-text font-medium">URN-032</td>
          <td class="p-4 text-sm">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-600 border border-green-200">
              <i class="fas fa-check-circle mr-1"></i> Restocked
            </span>
          </td>
          <td class="p-4 text-sm text-sidebar-text">Mar 13, 2025</td>
          <td class="p-4 text-sm font-medium text-sidebar-text">+15</td>
          <td class="p-4 text-sm text-sidebar-text">Downtown</td>
          <td class="p-4 text-sm">
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
  
  <!-- Pagination -->
  <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
    <div class="text-sm text-gray-500">
      Showing 1 - 3 of 18 activities
    </div>
    <div class="flex space-x-1">
      <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 cursor-not-allowed" disabled>&laquo;</button>
      
      <button class="px-3 py-1 border border-sidebar-border rounded text-sm bg-sidebar-accent text-white">1</button>
      <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">2</button>
      <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">3</button>
      
      <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&raquo;</button>
    </div>
  </div>
            
              <!-- Footer -->
              <footer class="bg-white rounded-lg shadow-sidebar border border-sidebar-border p-4 text-center text-sm text-gray-500 mt-8">
                <p>© 2025 GrievEase.</p>
              </footer>
            </div>
            
            <!-- JavaScript for modal -->
            <script>
              function openModal() {
                document.getElementById('serviceModal').classList.remove('hidden');
              }
              
              function closeModal() {
                document.getElementById('serviceModal').classList.add('hidden');
              }
              
              function sortTable(columnIndex) {
                // Implement sorting functionality here
                console.log('Sorting by column', columnIndex);
              }
            </script>
            <script src="tailwind.js"></script>
            <script src="script.js"></script>
            </body>
            </html>