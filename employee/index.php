<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check for employee user type (user_type = 2)
if ($_SESSION['user_type'] != 2) {
    // Redirect to appropriate page based on user type
    switch ($_SESSION['user_type']) {
        case 1: // Admin
            header("Location: ../admin/admin_index.php");
            break;
        case 3: // Customer
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

require_once '../db_connect.php';
  $user_id = $_SESSION['user_id'];
  $query = "SELECT first_name , last_name , email , birthdate, branch_loc FROM users WHERE id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $first_name = $row['first_name']; // We're confident user_id exists
  $last_name = $row['last_name'];
  $email = $row['email'];
  $branch = $row['branch_loc'];

// Calculate quick stats
$current_month = date('m');
$current_year = date('Y');

// Services this month
$services_query = "SELECT COUNT(*) as service_count FROM sales_tb 
                  WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ? AND branch_id = ?";
$stmt = $conn->prepare($services_query);
$stmt->bind_param("iii", $current_month, $current_year, $branch);
$stmt->execute();
$services_result = $stmt->get_result();
$services_data = $services_result->fetch_assoc();
$services_this_month = $services_data['service_count'];

// Cash revenue (sum of amount_paid)
$cash_query = "SELECT SUM(amount_paid) as cash_revenue FROM sales_tb 
              WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ? AND branch_id = ?";
$stmt = $conn->prepare($cash_query);
$stmt->bind_param("iii", $current_month, $current_year, $branch);
$stmt->execute();
$cash_result = $stmt->get_result();
$cash_data = $cash_result->fetch_assoc();
$cash_revenue = $cash_data['cash_revenue'] ? $cash_data['cash_revenue'] : 0;

// Accrual revenue (sum of discounted_price)
$accrual_query = "SELECT SUM(discounted_price) as accrual_revenue FROM sales_tb 
                WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ? AND branch_id = ?";
$stmt = $conn->prepare($accrual_query);
$stmt->bind_param("iii", $current_month, $current_year, $branch);
$stmt->execute();
$accrual_result = $stmt->get_result();
$accrual_data = $accrual_result->fetch_assoc();
$accrual_revenue = $accrual_data['accrual_revenue'] ? $accrual_data['accrual_revenue'] : 0;

// Ongoing services (status = 'Pending')
$ongoing_query = "SELECT COUNT(*) as ongoing_count FROM sales_tb WHERE status = 'Pending' AND branch_id = ?";
$stmt = $conn->prepare($ongoing_query);
$stmt -> bind_param("i",$branch);
$stmt->execute();
$ongoing_result = $stmt->get_result();
$ongoing_data = $ongoing_result->fetch_assoc();
$ongoing_services = $ongoing_data['ongoing_count'];


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Employee Dashboard</title>
  <?php include 'faviconLogo.php'; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    /* Custom scrollbar styles */
    .scrollbar-thin::-webkit-scrollbar {
      width: 4px;
      height: 4px;
    }
    
    .scrollbar-thin::-webkit-scrollbar-track {
      background: rgba(0, 0, 0, 0.05);
    }
    
    .scrollbar-thin::-webkit-scrollbar-thumb {
      background: rgba(202, 138, 4, 0.6);
      border-radius: 4px;
    }
    
    .scrollbar-thin::-webkit-scrollbar-thumb:hover {
      background: rgba(202, 138, 4, 0.9);
    }
    
    /* Hover and active states for sidebar links */
    .sidebar-link {
      position: relative;
      transition: all 0.3s ease;
    }
    
    .sidebar-link::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      height: 100%;
      width: 3px;
      background-color: transparent;
      transition: all 0.3s ease;
    }
    
    .sidebar-link:hover::before,
    .sidebar-link.active::before {
      background-color: #CA8A04;
    }
    
    /* Animate the sidebar
    @keyframes slideIn {
      from { transform: translateX(-100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    
    .animate-sidebar {
      animation: slideIn 0.3s ease forwards;
    } */

    /* Gradient background for menu section headers */
    .menu-header {
      background: linear-gradient(to right, rgba(202, 138, 4, 0.1), transparent);
    }
    /* Add this to your existing styles */
.main-content {
  margin-left: 16rem; /* Adjust this value to match the width of your sidebar */
  width: calc(100% - 16rem); /* Ensure the main content takes up the remaining width */
  z-index: 1; /* Ensure the main content is above the sidebar */
}

.sidebar {
  z-index: 10; /* Ensure the sidebar is below the main content */
}
/* Add this to your existing styles */
#sidebar {
  transition: width 0.3s ease, opacity 0.3s ease, visibility 0.3s ease;
}

#main-content {
  transition: margin-left 0.3s ease;
}

.w-0 {
  width: 0;
}

.opacity-0 {
  opacity: 0;
}

.invisible {
  visibility: hidden;
}
.w-\[calc\(100\%-16rem\)\] {
  width: calc(100% - 16rem);
}

.w-\[calc\(100\%-4rem\)\] {
  width: calc(100% - 4rem);
}
  </style>
</head>
<body class="flex bg-gray-50">
  <!-- Modify the sidebar structure to include a dedicated space for the hamburger menu -->
<nav id="sidebar" class="w-64 h-screen bg-sidebar-bg font-hedvig fixed transition-all duration-300 overflow-y-auto z-10 scrollbar-thin shadow-sidebar animate-sidebar sidebar">
  <!-- Logo and Header with hamburger menu -->
  <div class="flex items-center px-5 py-6 border-b border-sidebar-border">
    <button id="hamburger-menu" class="p-2 mr-2 bg-white rounded-lg shadow-md text-gray-600 hover:text-gray-900 transition-all duration-300">
      <i class="fas fa-bars"></i>
    </button>
    <!-- <img src="../Landing_Page/Landing_images/logo.png" alt="GrievEase Logo" class="h-10 w-auto mr-3"> -->
    <div class="text-2xl font-cinzel font-bold text-sidebar-accent">GrievEase</div>
  </div>
    
    <!-- User Profile -->
    <div class="flex items-center px-5 py-4 border-b border-sidebar-border bg-gradient-to-r from-navy to-primary">
      <div class="w-10 h-10 rounded-full bg-yellow-600 flex items-center justify-center shadow-md">
        <i class="fas fa-user text-white"></i>
      </div>
      <div class="ml-3">
        <div class="text-sm font-medium text-sidebar-text">
          <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
        </div>
        <div class="text-xs text-sidebar-text opacity-70">Employee</div>
      </div>
      <div class="ml-auto">
        <span class="w-3 h-3 bg-success rounded-full block"></span>
      </div>
    </div>
    
    <!-- Menu Items -->
    <div class="pt-4 pb-8">
      <!-- Main Navigation -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Main</h5>
      </div>
      <ul class="list-none p-0 mb-6">
        <li>
          <a href="index.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-tachometer-alt w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Dashboard</span>
          </a>
        </li> 
        <li>
          <a href="employee_customer_account_creation.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-user-circle w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Customer Account Management</span>
          </a>
        </li>
        <li>
          <a href="employee_inventory.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-boxes w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>View Inventory</span>
          </a>
        </li>
        <li>
          <a href="employee_pos.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-cash-register w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Point-Of-Sale (POS)</span>
          </a>
        </li>
      </ul>
        
      <!-- Reports & Analytics -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Reports & Analytics</h5>
      </div>
      <ul class="list-none p-0 mb-6">
        <li>
          <a href="employee_expenses.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-money-bill-wave w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Expenses</span>
          </a>
        </li>
        <li>
          <a href="employee_history.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-history w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Service History</span>
          </a>
        </li>
      </ul>
        
      <!-- Services & Staff -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Communication</h5>
      </div>
      <ul class="list-none p-0 mb-6">
          <a href="employee_chat.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-comments w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Chats</span>
          </a>
        </li>
      </ul>
        
      <!-- Account -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Account</h5>
      </div>
      <ul class="list-none p-0">
        <li>
          <a href="../logout.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover hover:text-error">
            <i class="fas fa-sign-out-alt w-5 text-center mr-3 text-error"></i>
            <span>Logout</span>
          </a>
        </li>
      </ul>
    </div>
    
    <!-- Footer -->
    <div class="relative bottom-0 left-0 right-0 px-5 py-3 border-t border-sidebar-border bg-gradient-to-r from-navy to-primary">
      <div class="flex justify-between items-center">
        <p class="text-xs text-sidebar-text opacity-60">© 2025 GrievEase</p>
        <div class="text-xs text-sidebar-accent">
          <i class="fas fa-heart"></i> With Compassion
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
<div id="main-content" class="ml-64 p-6 bg-gray-50 min-h-screen transition-all duration-300 main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Employee Dashboard</h1>
      <p class="text-sm text-gray-500">Welcome back, </p>
    </div>
    <div class="flex space-x-3">
    <!-- Notification Bell with improved UI -->
<div class="relative">
  <button id="notification-bell" class="p-2.5 bg-white border border-sidebar-border rounded-full shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300 flex items-center justify-center focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:ring-opacity-50">
    <i class="fas fa-bell text-sidebar-accent"></i>
    <span class="absolute -top-1 -right-1 bg-error text-white text-xs rounded-full h-5 w-5 flex items-center justify-center transform transition-transform duration-300 scale-100">3</span>
  </button>
  
  <!-- Redesigned Notification Dropdown -->
  <div id="notifications-dropdown" class="absolute right-0 mt-3 w-96 bg-white rounded-lg shadow-card border border-sidebar-border z-50 hidden opacity-0 transform -translate-y-2 transition-all duration-300">
    <!-- Arrow indicator at the top -->
    <div class="absolute -top-2 right-4 w-4 h-4 bg-white transform rotate-45 border-t border-l border-sidebar-border"></div>
    
    <!-- Notifications Header -->
    <div class="px-5 py-4 border-b border-sidebar-border flex justify-between items-center">
      <h3 class="font-medium text-sidebar-text text-base">Notifications</h3>
      <div class="flex space-x-2">
        <button class="text-xs text-sidebar-accent hover:text-darkgold transition-colors duration-200 flex items-center">
          <i class="fas fa-check-double mr-1"></i>
          Mark all as read
        </button>
      </div>
    </div>
    
    <!-- Notifications Filter -->
    <div class="flex px-4 py-2 border-b border-sidebar-border">
      <button class="px-3 py-1.5 text-xs font-medium rounded-full bg-sidebar-accent text-white mr-2">All</button>
      <button class="px-3 py-1.5 text-xs font-medium rounded-full bg-sidebar-hover text-sidebar-text hover:bg-gray-200 transition-colors mr-2">Unread</button>
      <button class="px-3 py-1.5 text-xs font-medium rounded-full bg-sidebar-hover text-sidebar-text hover:bg-gray-200 transition-colors">Important</button>
    </div>
    
    <!-- Notifications List with improved styling -->
    <div class="max-h-96 overflow-y-auto scrollbar-thin">
      <!-- New booking notification -->
      <a href="#" class="block px-5 py-4 border-b border-sidebar-border hover:bg-sidebar-hover transition-all duration-300 flex items-start relative">
        <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500 rounded-r"></div>
        <div class="flex-shrink-0 bg-blue-100 rounded-full p-2.5 mr-4">
          <i class="fas fa-calendar-alt text-blue-600"></i>
        </div>
        <div class="flex-grow">
          <div class="flex justify-between items-start">
            <p class="text-sm font-semibold text-sidebar-text">New booking request</p>
            <span class="bg-blue-100 text-blue-600 text-xs px-2 py-0.5 rounded-full">New</span>
          </div>
          <p class="text-sm text-gray-500 mt-1">Maria Santos requested a funeral service</p>
          <div class="flex items-center mt-2">
            <i class="fas fa-clock text-gray-400 text-xs mr-1.5"></i>
            <p class="text-xs text-gray-400">10 minutes ago</p>
          </div>
        </div>
      </a>
      
      <!-- Low Inventory notification -->
      <a href="#" class="block px-5 py-4 border-b border-sidebar-border hover:bg-sidebar-hover transition-all duration-300 flex items-start relative">
        <div class="absolute left-0 top-0 bottom-0 w-1 bg-yellow-500 rounded-r"></div>
        <div class="flex-shrink-0 bg-yellow-100 rounded-full p-2.5 mr-4">
          <i class="fas fa-exclamation-triangle text-yellow-600"></i>
        </div>
        <div class="flex-grow">
          <div class="flex justify-between items-start">
            <p class="text-sm font-semibold text-sidebar-text">Low inventory alert</p>
            <span class="bg-yellow-100 text-yellow-600 text-xs px-2 py-0.5 rounded-full">Warning</span>
          </div>
          <p class="text-sm text-gray-500 mt-1">Casket Model C102 is running low (2 remaining)</p>
          <div class="flex items-center mt-2">
            <i class="fas fa-clock text-gray-400 text-xs mr-1.5"></i>
            <p class="text-xs text-gray-400">1 hour ago</p>
          </div>
        </div>
      </a>
      
      <!-- Payment notification -->
      <a href="#" class="block px-5 py-4 border-b border-sidebar-border hover:bg-sidebar-hover transition-all duration-300 flex items-start relative">
        <div class="absolute left-0 top-0 bottom-0 w-1 bg-green-500 rounded-r"></div>
        <div class="flex-shrink-0 bg-green-100 rounded-full p-2.5 mr-4">
          <i class="fas fa-peso-sign text-green-600"></i>
        </div>
        <div class="flex-grow">
          <div class="flex justify-between items-start">
            <p class="text-sm font-semibold text-sidebar-text">Payment received</p>
            <span class="bg-green-100 text-green-600 text-xs px-2 py-0.5 rounded-full">Payment</span>
          </div>
          <p class="text-sm text-gray-500 mt-1">₱15,000 payment from Juan Cruz (ID: 2450)</p>
          <div class="flex items-center mt-2">
            <i class="fas fa-clock text-gray-400 text-xs mr-1.5"></i>
            <p class="text-xs text-gray-400">Yesterday</p>
          </div>
        </div>
      </a>
      
      <!-- Read notification example -->
      <a href="#" class="block px-5 py-4 border-b border-sidebar-border hover:bg-sidebar-hover transition-all duration-300 flex items-start bg-gray-50">
        <div class="flex-shrink-0 bg-gray-100 rounded-full p-2.5 mr-4">
          <i class="fas fa-user-check text-gray-600"></i>
        </div>
        <div class="flex-grow">
          <div class="flex justify-between items-start">
            <p class="text-sm font-semibold text-gray-600">Customer account created</p>
            <span class="bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded-full">Read</span>
          </div>
          <p class="text-sm text-gray-500 mt-1">New customer account for Pedro Reyes created</p>
          <div class="flex items-center mt-2">
            <i class="fas fa-clock text-gray-400 text-xs mr-1.5"></i>
            <p class="text-xs text-gray-400">3 days ago</p>
          </div>
        </div>
      </a>
      
      <!-- Empty state (shown when no notifications) -->
      <div class="hidden p-8 text-center">
        <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
          <i class="fas fa-bell-slash text-gray-400 text-xl"></i>
        </div>
        <p class="text-sm text-gray-600 font-medium">No notifications yet</p>
        <p class="text-xs text-gray-400 mt-1">We'll notify you when something new arrives</p>
      </div>
    </div>
    
    <!-- Notifications Footer -->
    <div class="px-5 py-3 text-center border-t border-sidebar-border bg-sidebar-hover">
      <a href="#" class="text-sm text-sidebar-accent hover:text-darkgold transition-colors font-medium flex items-center justify-center">
        View all notifications
        <i class="fas fa-arrow-right ml-2 text-xs"></i>
      </a>
    </div>
  </div>
</div>
</div>

  <!-- Quick Stats -->
    <div class="mb-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <!-- Services this Month -->
      <div class="bg-white rounded-lg shadow-sidebar p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
        <div class="flex items-center mb-3">
          <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mr-3">
            <i class="fas fa-calendar-alt text-lg"></i>
          </div>
          <span class="text-sidebar-text font-medium">Services this Month</span>
        </div>
        <div class="text-3xl font-bold mb-2 text-sidebar-text"><?php echo $services_this_month; ?></div>
        <div class="text-sm text-green-600 flex items-center">
          <i class="fas fa-arrow-up mr-1"></i> 2% from last week
        </div>
      </div>
      
      <!-- Monthly Revenue with Toggle -->
      <div class="bg-white rounded-lg shadow-sidebar p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center">
            <div class="w-12 h-12 rounded-lg bg-green-100 text-green-600 flex items-center justify-center mr-3">
              <i class="fas fa-peso-sign text-lg"></i>
            </div>
            <span class="text-sidebar-text font-medium">Monthly Revenue</span>
          </div>
          <div class="relative">
            <button id="revenue-toggle" class="p-1 bg-gray-100 rounded-full flex items-center">
              <span id="revenue-type" class="text-xs px-2">Cash</span>
              <i class="fas fa-chevron-down text-xs mr-1"></i>
            </button>
            <div id="revenue-dropdown" class="absolute right-0 mt-1 w-24 bg-white rounded-md shadow-lg hidden z-10">
              <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="toggleRevenue('cash')">Cash</button>
              <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="toggleRevenue('accrual')">Accrual</button>
            </div>
          </div>
        </div>
        <div id="cash-revenue" class="text-3xl font-bold mb-2 text-sidebar-text">₱<?php echo number_format($cash_revenue, 2); ?></div>
        <div id="accrual-revenue" class="text-3xl font-bold mb-2 text-sidebar-text hidden">₱<?php echo number_format($accrual_revenue, 2); ?></div>
        <div class="text-sm text-green-600 flex items-center">
          <i class="fas fa-arrow-up mr-1"></i> 5% from yesterday
        </div>
      </div>
      
      <!-- Ongoing Services -->
      <div class="bg-white rounded-lg shadow-sidebar p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
        <div class="flex items-center mb-3">
          <div class="w-12 h-12 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center mr-3">
            <i class="fas fa-tasks text-lg"></i>
          </div>
          <span class="text-sidebar-text font-medium">Ongoing Services</span>
        </div>
        <div class="text-3xl font-bold mb-2 text-sidebar-text"><?php echo $ongoing_services; ?></div>
        <div class="text-sm text-red-600 flex items-center">
          <i class="fas fa-arrow-down mr-1"></i> 1 task added
        </div>
      </div>
    </div>
  </div>

  <!-- Pending Bookings Table -->
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
    <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
      <h3 class="font-medium text-sidebar-text">Pending Bookings</h3>
    </div>
    <div class="overflow-x-auto scrollbar-thin">
      <table class="w-full">
        <thead>
          <tr class="bg-sidebar-hover">
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">
              <div class="flex items-center">
                Client Name <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">
              <div class="flex items-center">
                Service Type <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">
              <div class="flex items-center">
                Date <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">
              <div class="flex items-center">
                Location <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">
              <div class="flex items-center">
                Status <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Execute the pending bookings query
          $pending_query = "SELECT 
                              b.booking_id,
                              COALESCE(s.service_name, 'Custom Package') AS service_name,
                              CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name, ' ', u.suffix) AS full_name,
                              b.booking_date,
                              b.deceased_address
                            FROM booking_tb AS b
                            JOIN users AS u ON b.customerID = u.id
                            LEFT JOIN services_tb AS s ON b.service_id = s.service_id 
                            WHERE b.status='Pending'";
          
          $stmt = $conn->prepare($pending_query);
          $stmt->execute();
          $pending_result = $stmt->get_result();
          
          if ($pending_result->num_rows > 0) {
            while ($booking = $pending_result->fetch_assoc()) {
              echo '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover">';
              echo '<td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($booking['full_name']) . '</td>';
              echo '<td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($booking['service_name']) . '</td>';
              echo '<td class="p-4 text-sm text-sidebar-text">' . date('M j, Y', strtotime($booking['booking_date'])) . '</td>';
              echo '<td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($booking['deceased_address']) . '</td>';
              echo '<td class="p-4 text-sm">';
              echo '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Pending</span>';
              echo '</td>';
              echo '</tr>';
            }
          } else {
            echo '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover">';
            echo '<td colspan="5" class="p-4 text-sm text-sidebar-text text-center">No pending bookings found</td>';
            echo '</tr>';
          }
          ?>
        </tbody>
      </table>
    </div>
    <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
      <div class="text-sm text-gray-500">
        Showing <?php echo $pending_result->num_rows; ?> pending bookings
      </div>
    </div>
  </div>

  <!-- Recent Inventory Activity -->
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
      <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
          <h3 class="font-medium text-sidebar-text">Recent Inventory Activity</h3>
          <button class="px-4 py-2 bg-sidebar-accent text-white rounded-md text-sm flex items-center hover:bg-darkgold transition-all duration-300">
              <i class="fas fa-box mr-2"></i> Manage Inventory
          </button>
      </div>
      <div class="overflow-x-auto scrollbar-thin">
          <table class="w-full">
              <thead>
                  <tr class="bg-sidebar-hover">
                      <th class="p-4 text-left text-sm font-medium text-sidebar-text">Item</th>
                      <th class="p-4 text-left text-sm font-medium text-sidebar-text">ID</th>
                      <th class="p-4 text-left text-sm font-medium text-sidebar-text">Action</th>
                      <th class="p-4 text-left text-sm font-medium text-sidebar-text">Date</th>
                      <th class="p-4 text-left text-sm font-medium text-sidebar-text">Quantity</th>
                  </tr>
              </thead>
              <tbody id="inventoryLogsBody">
                  <!-- Loading indicator row -->
                  <tr id="inventoryLoadingIndicator" class="border-b border-sidebar-border">
                      <td colspan="5" class="p-4 text-sm text-center text-sidebar-text">
                          <i class="fas fa-circle-notch fa-spin mr-2"></i> Loading inventory activity...
                      </td>
                  </tr>
              </tbody>
          </table>
      </div>
      <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
          <div id="inventoryPaginationInfo" class="text-sm text-gray-500">Loading...</div>
          <div id="paginationControls" class="flex space-x-1"></div>
      </div>
  </div>

  <!-- Footer -->
  <footer class="bg-white rounded-lg shadow-sidebar border border-sidebar-border p-4 text-center text-sm text-gray-500 mt-8">
    <p>© 2025 GrievEase.</p>
  </footer>
</div>


  <script src="sidebar.js"></script>
  <script src="tailwind.js"></script>
<script>
    // Revenue Toggle Functionality
  document.getElementById('revenue-toggle').addEventListener('click', function() {
    const dropdown = document.getElementById('revenue-dropdown');
    dropdown.classList.toggle('hidden');
  });

  function toggleRevenue(type) {
    const cashElement = document.getElementById('cash-revenue');
    const accrualElement = document.getElementById('accrual-revenue');
    const typeElement = document.getElementById('revenue-type');
    const dropdown = document.getElementById('revenue-dropdown');
    
    if (type === 'cash') {
      cashElement.classList.remove('hidden');
      accrualElement.classList.add('hidden');
      typeElement.textContent = 'Cash';
    } else {
      cashElement.classList.add('hidden');
      accrualElement.classList.remove('hidden');
      typeElement.textContent = 'Accrual';
    }
    
    dropdown.classList.add('hidden');
  }

  // Close dropdown when clicking outside
  document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('revenue-dropdown');
    const toggle = document.getElementById('revenue-toggle');
    
    if (!toggle.contains(event.target) && !dropdown.contains(event.target)) {
      dropdown.classList.add('hidden');
    }
  });
</script>
<script>
// Load inventory logs when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadInventoryLogs(1);
});

function loadInventoryLogs(page = 1) {
    const loadingIndicator = document.getElementById('inventoryLoadingIndicator');
    const tableBody = document.getElementById('inventoryLogsBody');
    const paginationInfo = document.getElementById('inventoryPaginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    loadingIndicator.classList.remove('hidden');
    tableBody.innerHTML = '';
    
    fetch(`indexFunctions/fetch_inventory_logs.php?page=${page}&branch=<?php echo $branch; ?>`)
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
                    `;
                    tableBody.appendChild(row);
                });
                
                // Update pagination info
                updatePaginationInfo(paginationInfo, page, data.perPage, data.total);
                
                // Update pagination controls
                updatePaginationControls(paginationControls, page, data.perPage, data.total, 'loadInventoryLogs');
                
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
function updatePaginationControls(container, currentPage, perPage, totalItems, callbackFunction) {
    const totalPages = Math.ceil(totalItems / perPage);
    
    let html = `
        <button class="px-3.5 py-1.5 border rounded text-sm ${
            currentPage <= 1 ? 'border-gray-300 text-gray-400 cursor-not-allowed' : 'border-sidebar-border hover:bg-sidebar-hover'
        }" ${currentPage <= 1 ? 'disabled' : ''} onclick="${callbackFunction}(${currentPage - 1})">
            &laquo;
        </button>
    `;
    
    // Show page numbers
    for (let i = 1; i <= totalPages; i++) {
        html += `
            <button class="px-3.5 py-1.5 border rounded text-sm ${
                i === currentPage ? 'bg-sidebar-accent text-white border-sidebar-accent' : 'border-sidebar-border hover:bg-sidebar-hover'
            }" onclick="${callbackFunction}(${i})">
                ${i}
            </button>
        `;
    }
    
    html += `
        <button class="px-3.5 py-1.5 border rounded text-sm ${
            currentPage >= totalPages ? 'border-gray-300 text-gray-400 cursor-not-allowed' : 'border-sidebar-border hover:bg-sidebar-hover'
        }" ${currentPage >= totalPages ? 'disabled' : ''} onclick="${callbackFunction}(${currentPage + 1})">
            &raquo;
        </button>
    `;
    
    container.innerHTML = html;
}

// Helper function to show error message
function showError(container, message) {
    container.innerHTML = `
        <tr>
            <td colspan="5" class="px-4 py-3.5 text-sm text-center text-red-600">
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

</script>

<script>
// Notification Bell Toggle with animations
document.getElementById('notification-bell').addEventListener('click', function(event) {
  event.stopPropagation();
  const dropdown = document.getElementById('notifications-dropdown');
  
  if(dropdown.classList.contains('hidden')) {
    // Show dropdown with animation
    dropdown.classList.remove('hidden');
    setTimeout(() => {
      dropdown.classList.remove('opacity-0', '-translate-y-2');
    }, 10);
  } else {
    // Hide dropdown with animation
    dropdown.classList.add('opacity-0', '-translate-y-2');
    setTimeout(() => {
      dropdown.classList.add('hidden');
    }, 300);
  }
  
  // If showing notifications, mark as read (update counter) with animation
  if (!dropdown.classList.contains('hidden')) {
    setTimeout(() => {
      const notificationCounter = document.querySelector('#notification-bell span');
      // Animate the counter change
      notificationCounter.classList.add('scale-0');
      setTimeout(() => {
        notificationCounter.textContent = '0';
        notificationCounter.classList.remove('scale-0');
      }, 300);
    }, 2000);
  }
});

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
  // Notifications dropdown
  const notificationsDropdown = document.getElementById('notifications-dropdown');
  const notificationBell = document.getElementById('notification-bell');
  
  if (notificationsDropdown && notificationBell && 
      !notificationBell.contains(event.target) && 
      !notificationsDropdown.contains(event.target)) {
    // Hide with animation
    notificationsDropdown.classList.add('opacity-0', '-translate-y-2');
    setTimeout(() => {
      notificationsDropdown.classList.add('hidden');
    }, 300);
  }
});

// Add the ability to dismiss individual notifications
document.querySelectorAll('.notification-dismiss').forEach(button => {
  button.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    
    const notification = this.closest('.notification-item');
    notification.style.height = notification.offsetHeight + 'px';
    notification.classList.add('opacity-0');
    
    setTimeout(() => {
      notification.style.height = '0';
      notification.style.padding = '0';
      notification.style.margin = '0';
      notification.style.borderWidth = '0';
      
      setTimeout(() => {
        notification.remove();
        
        // Update counter
        const counter = document.querySelector('#notification-bell span');
        const currentCount = parseInt(counter.textContent);
        if(currentCount > 0) {
          counter.textContent = (currentCount - 1).toString();
          if(currentCount - 1 === 0) {
            counter.classList.add('scale-0');
          }
        }
      }, 300);
    }, 100);
  });
});
</script>
</body>
</html>