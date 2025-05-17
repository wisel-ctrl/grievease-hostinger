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
$stmt = bind_param("i",$branch);
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
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-bell"></i>
      </button>
       
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
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text">Robert Johnson</td>
            <td class="p-4 text-sm text-sidebar-text">Memorial Service</td>
            <td class="p-4 text-sm text-sidebar-text">Mar 8, 2025</td>
            <td class="p-4 text-sm text-sidebar-text">St. Mary's Chapel</td>
            <td class="p-4 text-sm">
              <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Pending</span>
            </td>
          </tr>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text">Emily Williams</td>
            <td class="p-4 text-sm text-sidebar-text">Funeral Service</td>
            <td class="p-4 text-sm text-sidebar-text">Mar 10, 2025</td>
            <td class="p-4 text-sm text-sidebar-text">Oak Hill Cemetery</td>
            <td class="p-4 text-sm">
              <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Pending</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
      <div class="text-sm text-gray-500">Showing 2 of 5 services</div>
      <div class="flex space-x-1">
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&laquo;</button>
        <button class="px-3 py-1 bg-sidebar-accent text-white rounded text-sm">1</button>
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">2</button>
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&raquo;</button>
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
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">
              <div class="flex items-center">
                Item <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">
              <div class="flex items-center">
                Category <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">
              <div class="flex items-center">
                Action <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">
              <div class="flex items-center">
                Quantity <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">
              <div class="flex items-center">
                Date <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text">Oak Casket - Premium</td>
            <td class="p-4 text-sm text-sidebar-text">Caskets</td>
            <td class="p-4 text-sm text-sidebar-text">Stock Added</td>
            <td class="p-4 text-sm text-sidebar-text">+5</td>
            <td class="p-4 text-sm text-sidebar-text">Mar 5, 2025</td>
          </tr>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text">Floral Arrangement - Classic</td>
            <td class="p-4 text-sm text-sidebar-text">Flowers</td>
            <td class="p-4 text-sm text-sidebar-text">Stock Removed</td>
            <td class="p-4 text-sm text-sidebar-text">-3</td>
            <td class="p-4 text-sm text-sidebar-text">Mar 4, 2025</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
      <div class="text-sm text-gray-500">Showing 2 of 5 activities</div>
      <div class="flex space-x-1">
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&laquo;</button>
        <button class="px-3 py-1 bg-sidebar-accent text-white rounded text-sm">1</button>
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">2</button>
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&raquo;</button>
      </div>
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
</body>
</html>