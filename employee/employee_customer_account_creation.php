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
if ($_SESSION['user_type'] != 2) {
    // Redirect to appropriate page based on user type
    switch ($_SESSION['user_type']) {
        case 1:
            header("Location: ../admin/admin_index.php");
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

require_once '../db_connect.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Customer Account Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

/* Add to your existing styles */
#archivedAccountsModal table {
    width: 100%;
    border-collapse: collapse;
}

#archivedAccountsModal th, 
#archivedAccountsModal td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}

#archivedAccountsModal tr:hover {
    background-color: #f7fafc;
}

#archivedAccountsModal .max-h-\[60vh\] {
    max-height: 60vh;
}

#searchContainer {
  position: relative;
  width: 300px;
}

#searchCustomer {
  padding-right: 30px;
  width: 100%;
}

#clearSearchBtn {
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  padding: 5px;
  z-index: 10;
}

/* Loading indicator for search */
.search-loading::after {
  content: "";
  position: absolute;
  right: 8px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  border: 2px solid rgba(202, 138, 4, 0.2);
  border-top: 2px solid #CA8A04;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  0% { transform: translateY(-50%) rotate(0deg); }
  100% { transform: translateY(-50%) rotate(360deg); }
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
        <div class="text-sm font-medium text-sidebar-text">John Doe</div>
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
          <a href="history.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
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
          <a href="..\logout.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover hover:text-error">
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
<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Customer Account Management</h1>
    </div>
    <div class="flex space-x-3">
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-bell"></i>
      </button>
    </div>
  </div>

  <?php
  // Include the database connection
  require_once('../db_connect.php');

  // Initialize pagination variables
  $page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Default to page 1 if not specified
  $itemsPerPage = 5; // Number of items to display per page

  // Count total number of customers
  $totalCustomersQuery = "SELECT COUNT(*) AS total FROM users";
  $totalCustomersResult = mysqli_query($conn, $totalCustomersQuery);
  $totalCustomersRow = mysqli_fetch_assoc($totalCustomersResult);
  $totalCustomers = $totalCustomersRow['total'];

  // Calculate total pages
  $totalPages = ceil($totalCustomers / $itemsPerPage);

  // Ensure page is within valid range
  $page = max(1, min($page, $totalPages));

  // Calculate offset for pagination
  $offset = ($page - 1) * $itemsPerPage;

  // Fetch customers for current page
  $customersQuery = "SELECT * FROM users LIMIT $offset, $itemsPerPage";
  $customersResult = mysqli_query($conn, $customersQuery);
  ?>

  <!-- Mode Selector -->
  <div class="flex justify-start mb-6">
    <div class="bg-gray-100 rounded-lg overflow-hidden inline-flex">
      <!-- Manage Accounts button first -->
      <button id="manageBtn" onclick="switchMode('manage')" class="py-2 px-5 border-none bg-sidebar-accent text-white font-semibold cursor-pointer hover:bg-darkgold transition-all duration-300">Manage Accounts</button>
      <!-- Create Account button second -->
      <button id="createBtn" onclick="switchMode('create')" class="py-2 px-5 border-none bg-transparent text-sidebar-text cursor-pointer hover:bg-sidebar-hover transition-all duration-300">Create Account</button>
    </div>
  </div>

  <!-- Add Customer Account Form (Non-Modal Version) -->
  <div id="createAccountSection" class="hidden">
    <div class="bg-white rounded-xl shadow-card w-full mx-auto">
      <!-- Form Header -->
      <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200 rounded-t-xl">
        <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
          Add Customer Account
        </h3>
      </div>
      
      <!-- Form Body -->
      <div class="px-4 sm:px-6 py-4 sm:py-5">
        <form id="addCustomerAccountForm" method="post" action="../admin/addCustomer/add_customer.php" class="space-y-3 sm:space-y-4">
          <!-- Personal Information Section -->
          <div>
            <label for="firstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              First Name <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input type="text" id="firstName" name="firstName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="First Name" required>
            </div>
            <p id="firstNameError" class="text-red-500 text-xs mt-1 hidden"></p>
          </div>
          
          <div>
            <label for="lastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Last Name <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input type="text" id="lastName" name="lastName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Last Name" required>
            </div>
            <p id="lastNameError" class="text-red-500 text-xs mt-1 hidden"></p>
          </div>
          
          <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
            <div class="w-full sm:flex-1">
              <label for="middleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Middle Name
              </label>
              <div class="relative">
                <input type="text" id="middleName" name="middleName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Middle Name">
              </div>
              <p id="middleNameError" class="text-red-500 text-xs mt-1 hidden"></p>
            </div>
            
            <div class="w-full sm:flex-1">
              <label for="suffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Suffix
              </label>
              <div class="relative">
                <select id="suffix" name="suffix" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                  <option value="">Select Suffix</option>
                  <option value="Jr">Jr</option>
                  <option value="Sr">Sr</option>
                  <option value="I">I</option>
                  <option value="II">II</option>
                  <option value="III">III</option>
                  <option value="IV">IV</option>
                  <option value="V">V</option>
                </select>
              </div>
            </div>
          </div>
          
          <div>
            <label for="birthdate" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Birthdate <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input type="date" id="birthdate" name="birthdate" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                max="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <p id="birthdateError" class="text-red-500 text-xs mt-1 hidden"></p>
          </div>
          
          <div>
            <label for="branchLocation" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Branch Location <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <select id="branchLocation" name="branchLocation" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                <option value="">Select Branch</option>
                <!-- Branch options will be populated by AJAX -->
              </select>
            </div>
            <p id="branchError" class="text-red-500 text-xs mt-1 hidden">Please select a branch</p>
          </div>
          
          <!-- Contact Information Section -->
          <div>
            <label for="customerEmail" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Email Address <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input type="email" id="customerEmail" name="customerEmail" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="example@email.com" required>
            </div>
            <p id="emailError" class="text-red-500 text-xs mt-1 hidden"></p>
          </div>
          
          <div>
            <label for="customerPhone" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Phone Number <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input type="tel" id="customerPhone" name="customerPhone" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Phone Number" required>
            </div>
            <p id="phoneError" class="text-red-500 text-xs mt-1 hidden"></p>
          </div>
          
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Generated Password
            </label>
            <div class="relative">
              <input type="password" id="generatedPassword" name="password" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200 bg-gray-100" readonly>
              <button type="button" class="absolute right-2 top-2 text-gray-500 hover:text-gray-700" onclick="togglePassword()">
                <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 sm:w-6 sm:h-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 12s2.947-5.455 8.02-5.455S20.02 12 20.02 12s-2.947 5.455-8.02 5.455S3.98 12 3.98 12z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
                </svg>
              </button>
            </div>
          </div>
          
          <!-- Additional Information Card -->
          <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border-l-4 border-sidebar-accent mt-3 sm:mt-4">
            <h4 class="text-xs sm:text-sm font-medium text-gray-700 mb-2 flex items-center">
              <i class="fas fa-info-circle mr-2 text-sidebar-accent"></i>
              Account Information
            </h4>
            <p class="text-xs sm:text-sm text-gray-600">
              An account will be created with the provided information. A temporary password will be generated automatically.
            </p>
            <p class="text-xs sm:text-sm text-gray-600 mt-2">
              The customer will be able to change their password after logging in for the first time.
            </p>
          </div>
          
          <input type="hidden" name="user_type" value="3">
          <input type="hidden" name="is_verified" value="1">
        </form>
      </div>
      
      <!-- Form Footer -->
      <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 bg-white rounded-b-xl">
        <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="confirmSubmitCustomerForm()">
          Create Account
        </button>
      </div>
    </div>
  </div>

  <!-- Customer Account Management Section -->
  <div id="manageAccountSection" class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
    <!-- Account Header with Search and Filters -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
      <!-- Desktop layout for big screens - Title on left, controls on right -->
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <!-- Title and Counter -->
        <div class="flex items-center gap-3 mb-4 lg:mb-0">
          <h3 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Customer Accounts</h3>
          <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
            <span id="totalCustomers"><?php echo $totalCustomers; ?></span>
          </span>
        </div>
        
        <!-- Controls for big screens - aligned right -->
        <div class="hidden lg:flex items-center gap-3">
          <!-- Search Input -->
          <div class="relative">
            <input type="text" id="customerSearchInput" 
                  placeholder="Search customers..." 
                  class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
            <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
          </div>

          <!-- Filter Dropdown -->
          <div class="relative filter-dropdown">
            <button id="customerFilterToggle" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
              <i class="fas fa-filter text-sidebar-accent"></i>
              <span>Filters</span>
              <span id="filterIndicator" class="hidden h-2 w-2 bg-sidebar-accent rounded-full"></span>
            </button>
            
            <!-- Filter Window -->
            <div id="customerFilterDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
              <div class="space-y-4">
                <!-- Sort Options -->
                <div>
                  <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                  <div class="space-y-1">
                    <div class="flex items-center cursor-pointer filter-option" data-sort="id_asc">
                      <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        Default
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer filter-option" data-sort="name_asc">
                      <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        Name: A-Z
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer filter-option" data-sort="name_desc">
                      <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        Name: Z-A
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer filter-option" data-sort="email_asc">
                      <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        Email: A-Z
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer filter-option" data-sort="email_desc">
                      <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        Email: Z-A
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- For Customer Archive Button -->
          <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover" onclick="viewArchivedAccounts()">
            <i class="fas fa-archive text-sidebar-accent"></i>
            <span>Archive</span>
          </button>
          
          <!-- Add Customer Account Button -->
          <button class="px-4 py-2 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap" 
                 onclick="switchMode('create')">
            <i class="fas fa-plus"></i>
            <span>Add Customer Account</span>
          </button>
        </div>
      </div>
      
      <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
      <div class="lg:hidden w-full mt-4">
        <!-- First row: Search bar with filter icon on the right -->
        <div class="flex items-center w-full gap-3 mb-4">
          <!-- Search Input - Takes most of the space -->
          <div class="relative flex-grow">
            <input type="text" id="customerSearchInputMobile" 
                 placeholder="Search customers..." 
                 class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
          </div>

          <!-- Icon-only button for filter -->
          <div class="flex items-center">
            <!-- Filter Icon Button -->
            <div class="relative filter-dropdown">
              <button id="customerFilterToggleMobile" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
                <i class="fas fa-filter text-xl"></i>
                <span id="filterIndicatorMobile" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
              </button>
              
              <!-- Mobile Filter Dropdown -->
              <div id="customerFilterDropdownMobile" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
                <div class="space-y-2">
                  <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                  <div class="space-y-1">
                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="id_asc">
                      <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        Default
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="name_asc">
                      <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        Name: A-Z
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="name_desc">
                      <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        Name: Z-A
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="email_asc">
                      <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        Email: A-Z
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="email_desc">
                      <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        Email: Z-A
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Archive Icon Button -->
          <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent" onclick="viewArchivedAccounts()">
            <i class="fas fa-archive text-xl"></i>
          </button>
        </div>

        <!-- Second row: Add Customer Account Button - Full width -->
        <div class="w-full">
          <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap w-full justify-center" 
                 onclick="switchMode('create')">
            <i class="fas fa-plus mr-2"></i>
            <span>Add Customer Account</span>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Responsive Table Container with improved spacing -->
    <div class="overflow-x-auto scrollbar-thin" id="customerTableContainer">
      
      <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
      <div class="min-w-full">
        <table class="w-full" id="customerTable">
          <thead>
            <tr class="bg-gray-50 border-b border-sidebar-border">
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('id')">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-hashtag text-sidebar-accent"></i> Customer ID 
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('name')">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-user text-sidebar-accent"></i> Name 
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('email')">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-envelope text-sidebar-accent"></i> Email 
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('role')">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-id-badge text-sidebar-accent"></i> Type 
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('status')">
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
          <tbody id="customerTableBody">
            <!-- Table content will be dynamically loaded -->
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Sticky Pagination Footer with improved spacing -->
    <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
      <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
        Showing <span id="showingFrom">0</span> - <span id="showingTo">0</span> 
        of <span id="totalCount"><?php echo $totalCustomers; ?></span> customers
      </div>
      <div id="paginationContainer" class="flex space-x-1">
        <!-- Pagination buttons will be inserted here by JavaScript -->
      </div>
    </div>
  </div>
</div>

    <!-- Customer Details Modal -->
    <div id="customerModal" class="hidden fixed z-50 inset-0 overflow-auto bg-black bg-opacity-40">
      <div class="bg-white mx-auto my-[10%] p-5 border border-gray-300 w-4/5 max-w-3xl rounded-lg shadow-lg">
        <div class="flex justify-between items-center mb-5 border-b border-gray-300 pb-3">
          <h3 id="modalTitle" class="m-0 text-lg font-semibold">Customer Details</h3>
          <span onclick="closeModal()" class="cursor-pointer text-2xl">&times;</span>
        </div>
        <div id="modalContent">
          <!-- Content will be dynamically populated -->
        </div>
        <div class="mt-5 text-right border-t border-gray-300 pt-4">
          <button onclick="closeModal()" class="bg-gray-600 text-white border-none py-2 px-4 rounded-md cursor-pointer">Close</button>
          <button id="modalActionButton" class="bg-blue-600 text-white border-none py-2 px-4 rounded-md ml-3 cursor-pointer">Save Changes</button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed z-50 inset-0 overflow-auto bg-black bg-opacity-40">
      <div class="bg-white mx-auto my-[15%] p-5 border border-gray-300 w-[400px] rounded-lg shadow-lg">
        <div class="text-center mb-5">
          <i class="fas fa-exclamation-triangle text-5xl text-red-600"></i>
          <h3 class="mt-4 text-lg font-semibold">Confirm Deletion</h3>
          <p class="text-gray-600">Are you sure you want to delete this customer account? This action cannot be undone.</p>
        </div>
        <div class="flex justify-center gap-3">
          <button onclick="closeDeleteModal()" class="bg-gray-600 text-white border-none py-2 px-4 rounded-md cursor-pointer">Cancel</button>
          <button onclick="deleteCustomer()" class="bg-red-600 text-white border-none py-2 px-4 rounded-md cursor-pointer">Delete</button>
        </div>
      </div>
    </div>
  </div>
  
<!-- OTP Verification Modal -->
<div id="otpVerificationModal" class="fixed inset-0 bg-black bg-opacity-60 z-[9999] hidden overflow-y-auto flex items-center justify-center p-4 overscroll-contain [will-change:transform]">
  <div class="bg-white relative z-[10000] rounded-xl shadow-xl w-full max-w-md mx-2">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0 rounded-t-xl">
      <h3 class="text-xl font-bold text-white"><i class="fas fa-shield-alt"></i> Email Verification</h3>
      <button onclick="closeOtpModal()" class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="p-6">
      <p class="text-gray-700 mb-4">A verification code has been sent to <span id="otpEmail" class="font-medium"></span>. Please enter the code below.</p>
      <div class="flex justify-center gap-2 mb-4">
        <input type="text" class="otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
      </div>
      <div id="otpError" class="text-red-500 text-center text-sm mb-4 hidden"></div>
      <p class="text-sm text-gray-500 text-center">Didn't receive the code? <button type="button" onclick="resendOTP()" class="text-sidebar-accent hover:underline">Resend</button></p>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white rounded-b-xl">
      <button onclick="closeOtpModal()" class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors">
        Cancel
      </button>
      <button id="verifyOtpBtn" onclick="verifyOTP()" class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center">
        <i class="fas fa-check-circle mr-2"></i> Verify
      </button>
    </div>
  </div>
</div>
  
  
  <!-- CUSTOMER ACCOUNT CREATION VALIDATION -->
  
  <script>

document.getElementById("customerPhone").addEventListener("input", function (e) {
    this.value = this.value.replace(/[^0-9]/g, ""); // Remove non-numeric characters
  });

// Real-time validation functions
// Updated name validation functions
function validateFirstName() {
  const firstNameInput = document.getElementById('firstName');
  const firstNameError = document.getElementById('firstNameError');
  const firstName = firstNameInput.value.trim();
  const nameRegex = /^[A-Za-z]+$/;

  if (firstName === '') {
    firstNameError.textContent = 'First name is required';
    firstNameError.classList.remove('hidden');
    return false;
  } else if (!nameRegex.test(firstName)) {
    firstNameError.textContent = 'First name must contain only letters (A-Z, a-z)';
    firstNameError.classList.remove('hidden');
    return false;
  } else if (firstName.length === 1) {
    firstNameError.textContent = 'First name must not contain single characters only';
    firstNameError.classList.remove('hidden');
    return false;
  } else {
    firstNameError.classList.add('hidden');
    return true;
  }
}

function validateLastName() {
  const lastNameInput = document.getElementById('lastName');
  const lastNameError = document.getElementById('lastNameError');
  const lastName = lastNameInput.value.trim();
  const nameRegex = /^[A-Za-z]+$/;

  if (lastName === '') {
    lastNameError.textContent = 'Last name is required';
    lastNameError.classList.remove('hidden');
    return false;
  } else if (!nameRegex.test(lastName)) {
    lastNameError.textContent = 'Last name must not contain single characters only';
    lastNameError.classList.remove('hidden');
    return false;
  } else if (lastName.length === 1) {
    lastNameError.textContent = 'Last name must be at least 2 letters';
    lastNameError.classList.remove('hidden');
    return false;
  } else {
    lastNameError.classList.add('hidden');
    return true;
  }
}

function validateMiddleName() {
  const middleNameInput = document.getElementById('middleName');
  const middleNameError = document.getElementById('middleNameError');
  const middleName = middleNameInput.value.trim();
  const nameRegex = /^[A-Za-z]*$/;

  if (middleName !== '') {
    if (!nameRegex.test(middleName)) {
      middleNameError.textContent = 'Middle name must not contain single characters only';
      middleNameError.classList.remove('hidden');
      return false;
    } else if (middleName.length === 1) {
      middleNameError.textContent = 'Middle name must be at least 2 letters or empty';
      middleNameError.classList.remove('hidden');
      return false;
    }
  }
  
  middleNameError.classList.add('hidden');
  return true;
}

function validateBirthdate() {
  const birthdateInput = document.getElementById('birthdate');
  const birthdateError = document.getElementById('birthdateError');
  const birthdate = birthdateInput.value;

  if (birthdate === '') {
    birthdateError.textContent = 'Birthdate is required';
    birthdateError.classList.remove('hidden');
    return false;
  } 

  const today = new Date();
  const birthdateObj = new Date(birthdate);
  let age = today.getFullYear() - birthdateObj.getFullYear();
  const monthDiff = today.getMonth() - birthdateObj.getMonth();
  
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdateObj.getDate())) {
    age--;
  }
  
  if (age < 18) {
    birthdateError.textContent = 'You must be at least 18 years old';
    birthdateError.classList.remove('hidden');
    return false;
  } else {
    birthdateError.classList.add('hidden');
    return true;
  }
}

function validateEmail() {
  const emailInput = document.getElementById('customerEmail');
  const emailError = document.getElementById('emailError');
  const email = emailInput.value.trim();
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (email === '') {
    emailError.textContent = 'Email is required';
    emailError.classList.remove('hidden');
    return false;
  } else if (!emailPattern.test(email)) {
    emailError.textContent = 'Please enter a valid email address';
    emailError.classList.remove('hidden');
    return false;
  } else {
    emailError.classList.add('hidden');
    return true;
  }
}

function validatePhoneNumber() {
  const phoneInput = document.getElementById('customerPhone');
  const phoneError = document.getElementById('phoneError');
  const phone = phoneInput.value.trim();
  const phonePattern = /^09\d{9}$/;

  // Remove any non-digit characters
  const cleanedPhone = phone.replace(/[^0-9]/g, '');

  if (phone === '') {
    phoneError.textContent = 'Phone number is required';
    phoneError.classList.remove('hidden');
    return false;
  } else if (!phonePattern.test(cleanedPhone)) {
    phoneError.textContent = 'Please enter a valid 11-digit mobile number (e.g., 09123456789)';
    phoneError.classList.remove('hidden');
    return false;
  } else {
    phoneError.classList.add('hidden');
    return true;
  }
}

function validateBranchLocation() {
  const branchSelect = document.getElementById('branchLocation');
  const branchError = document.getElementById('branchError');

  if (branchSelect.value === '') {
    branchError.classList.remove('hidden');
    return false;
  } else {
    branchError.classList.add('hidden');
    return true;
  }
}

// Modal functions
function openAddCustomerAccountModal() {
  const modal = document.getElementById('addCustomerAccountModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  document.body.classList.add('overflow-hidden');
}

function closeAddCustomerAccountModal() {
  const modal = document.getElementById('addCustomerAccountModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  document.body.classList.remove('overflow-hidden');
  
  // Reset form and error messages
  document.getElementById('addCustomerAccountForm').reset();
  document.querySelectorAll('.text-red-500.text-xs').forEach(element => {
    element.classList.add('hidden');
  });
}

// Toggle password visibility
function togglePassword() {
  const passwordInput = document.getElementById('generatedPassword');
  const eyeIcon = document.getElementById('eyeIcon');
  
  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    eyeIcon.innerHTML = `
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.5s2.947 5.455 8.02 5.455S20.02 8.5 20.02 8.5s-2.947-5.455-8.02-5.455S3.98 8.5 3.98 8.5z" />
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
      <line x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="1.5" />
    `;
  } else {
    passwordInput.type = 'password';
    eyeIcon.innerHTML = `
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 12s2.947-5.455 8.02-5.455S20.02 12 20.02 12s-2.947 5.455-8.02 5.455S3.98 12 3.98 12z" />
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
    `;
  }
}


// Show OTP modal and send OTP
function showOTPModal() {
  // Set the email in the OTP modal
  const email = document.getElementById('customerEmail').value;
  document.getElementById('otpEmail').textContent = email;
  
  // Send OTP to email
  const formData = new FormData();
  formData.append('email', email);
  
  const xhr = new XMLHttpRequest();
  xhr.open('POST', '../admin/addCustomer/send_otp.php', true);
  
  xhr.onload = function() {
    if (this.status === 200) {
      const response = JSON.parse(this.responseText);
      if (response.success) {
        // Show OTP modal
        const modal = document.getElementById('otpVerificationModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Focus on first OTP input
        const otpInputs = document.querySelectorAll('.otp-input');
        if (otpInputs.length > 0) {
          otpInputs[0].focus();
        }
      } else {
        Swal.fire({
          title: 'Error Occurred',
          text: response.message || 'Something went wrong', // Fallback if message is empty
          icon: 'error',
          confirmButtonText: 'OK',
          confirmButtonColor: '#d33',
          backdrop: `
            rgba(210,0,0,0.4)
            url("/images/nyan-cat.gif")
            center top
            no-repeat
          `
        });
      }
    }
  };
  
  xhr.send(formData);
}

// Close OTP modal
function closeOtpModal() {
  const modal = document.getElementById('otpVerificationModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  
  // Clear OTP inputs
  const otpInputs = document.querySelectorAll('.otp-input');
  otpInputs.forEach(input => {
    input.value = '';
  });
  
  // Hide error message
  document.getElementById('otpError').classList.add('hidden');
}

// Resend OTP
function resendOTP() {

  const email = document.getElementById('customerEmail').value;
  const formData = new FormData();
  formData.append('email', email);
  
  const xhr = new XMLHttpRequest();
  xhr.open('POST', '../admin/addCustomer/send_otp.php', true);
  
  xhr.onload = function() {
    if (this.status === 200) {
      try {
        const response = JSON.parse(this.responseText);
        if (response.success) {
          Swal.fire({
            title: 'OTP Sent!',
            text: 'A new OTP has been sent to your email address.',
            icon: 'success',
            toast: true,
            position: 'top-end', 
            showConfirmButton: false,
            timer: 5000,
            timerProgressBar: true,
            background: '#f8f9fa',
            iconColor: '#28a745',
            width: '400px',
            padding: '1em',
            customClass: {
              container: 'custom-swal-container',
              popup: 'custom-swal-popup'
            }
          });
        } else {
          Swal.fire({
            title: 'Failed',
            text: response.message || 'Failed to send OTP',
            icon: 'error',
            toast: true,
            position: 'top-end', // top-right corner
            showConfirmButton: false,
            timer: 4000, // Longer display for errors
            timerProgressBar: true,
            background: '#f8f9fa',
            iconColor: '#dc3545',
            width: '400px',
            padding: '1em',
            customClass: {
              container: 'custom-swal-container',
              popup: 'custom-swal-popup-error' // Special class for errors
            }
          });
        }
      } catch (e) {
        Swal.fire({
          title: 'Error',
          text: 'Invalid server response',
          icon: 'error'
        });
      }
    }
  };
  
  xhr.onerror = function() {
    Swal.fire({
      title: 'Connection Error',
      text: 'Failed to connect to server',
      icon: 'error'
    });
  };
  
  xhr.send(formData);
}

// Verify OTP and submit form
function verifyOTP() {
  // Collect OTP from inputs
  const otpInputs = document.querySelectorAll('.otp-input');
  let otpValue = '';
  
  otpInputs.forEach(input => {
    otpValue += input.value;
  });
  
  // Check if OTP is complete
  if (otpValue.length !== 6) {
    document.getElementById('otpError').textContent = 'Please enter all 6 digits';
    document.getElementById('otpError').classList.remove('hidden');
    return;
  }
  
  // Verify OTP
  const formData = new FormData();
  formData.append('otp', otpValue);
  
  const xhr = new XMLHttpRequest();
  xhr.open('POST', '../admin/addCustomer/verify_otp.php', true);
  
  xhr.onload = function() {
    if (this.status === 200) {
      const response = JSON.parse(this.responseText);
      if (response.success) {
        // OTP verified, proceed with form submission
        actuallySubmitForm();
      } else {
        document.getElementById('otpError').textContent = response.message;
        document.getElementById('otpError').classList.remove('hidden');
      }
    }
  };
  
  xhr.send(formData);
}

// Handle OTP input functionality
document.addEventListener('DOMContentLoaded', function() {
    
    generatePassword();
  
  // Regenerate password when these fields change
  document.getElementById('firstName').addEventListener('input', generatePassword);
  document.getElementById('lastName').addEventListener('input', generatePassword);
  document.getElementById('birthdate').addEventListener('change', generatePassword);
    
  const otpInputs = document.querySelectorAll('.otp-input');
  
  otpInputs.forEach((input, index) => {
    // Auto-focus next input
    input.addEventListener('input', function() {
      if (input.value.length === 1) {
        if (index < otpInputs.length - 1) {
          otpInputs[index + 1].focus();
        }
      }
    });
    
    // Handle backspace
    input.addEventListener('keydown', function(e) {
      if (e.key === 'Backspace' && input.value === '' && index > 0) {
        otpInputs[index - 1].focus();
      }
    });
    
    // Allow only numbers
    input.addEventListener('input', function() {
      input.value = input.value.replace(/[^0-9]/g, '');
    });
  });
});

// Add this function to check phone number availability before submission
function checkCustomerPhoneAvailability() {
  const phoneInput = document.getElementById('customerPhone');
  const phoneError = document.getElementById('phoneError');
  const phone = phoneInput.value.trim();
  
  // Only proceed if the phone number passed basic validation
  if (!validatePhoneNumber()) {
    return Promise.reject('Phone number is invalid');
  }
  
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../admin/addCustomer/check_phone.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
      if (this.status === 200) {
        try {
          const response = JSON.parse(this.responseText);
          if (response.available) {
            phoneError.classList.add('hidden');
            resolve(true);
          } else {
            phoneError.textContent = 'Phone number already in use';
            phoneError.classList.remove('hidden');
            reject('Phone number already in use');
          }
        } catch (e) {
          console.error("Error parsing response:", e);
          phoneError.textContent = 'Error checking phone number';
          phoneError.classList.remove('hidden');
          reject('Error checking phone number');
        }
      } else {
        phoneError.textContent = 'Error checking phone number';
        phoneError.classList.remove('hidden');
        reject('Error checking phone number');
      }
    };
    
    xhr.onerror = function() {
      phoneError.textContent = 'Network error occurred';
      phoneError.classList.remove('hidden');
      reject('Network error occurred');
    };
    
    xhr.send('phoneNumber=' + encodeURIComponent(phone));
  });
}

function generatePassword() {
  const firstName = document.getElementById('firstName').value.trim();
  const lastName = document.getElementById('lastName').value.trim();
  const birthdate = document.getElementById('birthdate').value;
  
  if (firstName !== '' && lastName !== '' && birthdate !== '') {
    // Format: First letter of first name (uppercase) + First letter of last name (lowercase) + birthdate (YYYYMMDD)
    const password = firstName.charAt(0).toUpperCase() + 
                     lastName.charAt(0).toLowerCase() + 
                     birthdate.replace(/-/g, '');
    document.getElementById('generatedPassword').value = password;
  } else {
    // If fields are empty, generate a random password as fallback
    const length = 12;
    const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    let password = "";
    
    for (let i = 0; i < length; i++) {
      const randomIndex = Math.floor(Math.random() * charset.length);
      password += charset[randomIndex];
    }
    
    document.getElementById('generatedPassword').value = password;
  }
}

// Confirmation before submitting form
function confirmSubmitCustomerForm() {
  // Validate all fields
  const isValid = validateFirstName() && 
                  validateMiddleName() && 
                  validateLastName() && 
                  validateBirthdate() && 
                  validateEmail() && 
                  validatePhoneNumber() && 
                  validateBranchLocation();

  if (isValid) {
    // Show confirmation dialog
    Swal.fire({
      title: 'Confirm Account Creation',
      html: `
        <div style="text-align: left;">
          <p>Are you sure you want to create this customer account?</p>
          <div style="margin-top: 15px; background: #f8f9fa; padding: 10px; border-radius: 5px; border-left: 4px solid #CA8A04;">
            <p><strong>Name:</strong> ${document.getElementById('firstName').value} ${document.getElementById('lastName').value}</p>
            <p><strong>Email:</strong> ${document.getElementById('customerEmail').value}</p>
            <p><strong>Phone:</strong> ${document.getElementById('customerPhone').value}</p>
          </div>
        </div>
      `,
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#CA8A04',
      confirmButtonText: 'Yes, create account',
      cancelButtonText: 'Cancel',
      reverseButtons: true
    }).then((result) => {
      if (result.isConfirmed) {
        // Generate password if not already generated
        if (document.getElementById('generatedPassword').value === '') {
          generatePassword();
        }
        
        // First check if phone number is available
        checkCustomerPhoneAvailability()
          .then(() => {
            // If phone is available, show OTP verification modal
            showOTPModal();
          })
          .catch((error) => {
            console.error("Phone validation error:", error);
            // Error handling already done in checkPhoneAvailability function
          });
      }
    });
  }
}

// Add this new function for actual submission after OTP verification
function actuallySubmitForm() {
  const form = document.getElementById('addCustomerAccountForm');
  const formData = new FormData(form);
  
  const xhr = new XMLHttpRequest();
  xhr.open('POST', '../admin/addCustomer/add_customer.php', true);
  
  xhr.onload = function() {
    if (this.status === 200) {
      const response = JSON.parse(this.responseText);
      if (response.success) {
          closeOtpModal(); // Close OTP modal
          
          Swal.fire({
              title: 'Success!',
              text: 'Customer account created successfully!',
              icon: 'success',
              confirmButtonColor: '#28a745',
              showCancelButton: false,
              confirmButtonText: 'OK',
              allowOutsideClick: false,
              willClose: () => {
                  // Reload the page after account creation
                  window.location.reload();
              }
          });
      } else {
          Swal.fire({
              title: 'Error!',
              html: `<div style="color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 5px; border-left: 4px solid #f5c6cb;">
                        ${response.message || 'Failed to create account'}
                    </div>`,
              icon: 'error',
              confirmButtonColor: '#dc3545',
              confirmButtonText: 'Try Again',
              allowOutsideClick: false
          });
      }
    }
  };
  
  xhr.send(formData);
}

// Function to load branches
function loadBranches() {
  const branchSelect = document.getElementById('branchLocation');
  
  fetch('../admin/addCustomer/get_branches.php')
    .then(response => response.json())
    .then(data => {
      // Clear existing options except the first one
      branchSelect.innerHTML = '<option value="">Select Branch</option>';
      
      // Add new options
      data.forEach(branch => {
        const option = document.createElement('option');
        option.value = branch.branch_id;
        option.textContent = branch.branch_name;
        branchSelect.appendChild(option);
      });
    })
    .catch(error => {
      console.error('Error loading branches:', error);
    });
}

// Add event listeners for real-time validation
document.addEventListener('DOMContentLoaded', function() {
  // First Name validation
  document.getElementById('firstName').addEventListener('input', validateFirstName);
  
  // Middle Name validation
  document.getElementById('middleName').addEventListener('input', validateMiddleName);
  
  // Last Name validation
  document.getElementById('lastName').addEventListener('input', validateLastName);
  
  // Birthdate validation
  document.getElementById('birthdate').addEventListener('change', validateBirthdate);
  
  // Email validation
  document.getElementById('customerEmail').addEventListener('input', validateEmail);
  
  // Phone Number validation
  document.getElementById('customerPhone').addEventListener('input', validatePhoneNumber);
  
  // Branch Location validation
  document.getElementById('branchLocation').addEventListener('change', validateBranchLocation);
  
  loadBranches();

  // Close modal on 'Escape' key press
  window.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      closeAddCustomerAccountModal();
    }
  });
});





</script>
  
  <script>
      // Function to fetch and display customer accounts
    // Function to fetch and display customer accounts
function fetchCustomerAccounts(page = 1, search = '', sort = 'id_asc') {
    // Show loading indicator
    const tableBody = document.querySelector('#customerTable tbody');
    tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4">Loading...</td></tr>';
    
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `accountManagement/fetch_customer_accounts.php?page=${page}&search=${encodeURIComponent(search)}&sort=${sort}`, true);
    
    xhr.onload = function() {
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                
                if (response.error) {
                    // Show error message
                    tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-red-500">${response.error}</td></tr>`;
                    return;
                }
                
                // Update the table content
                tableBody.innerHTML = response.tableContent;
                
                // Update pagination info
                document.getElementById('showingFrom').textContent = response.showingFrom;
                document.getElementById('showingTo').textContent = response.showingTo;
                document.getElementById('totalCount').textContent = response.totalCount;
                
                // Update pagination buttons
                updatePaginationButtons(response.totalPages, page, search, sort);
                
            } catch (e) {
                console.error('Error parsing response:', e);
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-red-500">Error loading data</td></tr>';
            }
        } else {
            tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-red-500">Failed to load data</td></tr>';
        }
    };
    
    xhr.onerror = function() {
        tableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-red-500">Network error occurred</td></tr>';
    };
    
    xhr.send();
}
    
// Add a debounce function to prevent too many rapid searches
let searchTimeout;
const searchDebounceTime = 300; // milliseconds


// Helper function to update pagination buttons
function updatePaginationButtons(totalPages, currentPage, search, sort) {
    const paginationContainer = document.getElementById('paginationContainer');
    paginationContainer.innerHTML = '';
    
    // Previous button
    const prevButton = document.createElement('button');
    prevButton.className = 'px-3 py-1 border rounded mr-1' + (currentPage === 1 ? ' opacity-50 cursor-not-allowed' : ' hover:bg-gray-100');
    prevButton.textContent = 'Previous';
    prevButton.disabled = currentPage === 1;
    prevButton.onclick = () => {
        if (currentPage > 1) fetchCustomerAccounts(currentPage - 1, search, sort);
    };
    paginationContainer.appendChild(prevButton);
    
    // Page buttons
    const maxPagesToShow = 5;
    let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
    let endPage = Math.min(totalPages, startPage + maxPagesToShow - 1);
    
    if (endPage - startPage + 1 < maxPagesToShow) {
        startPage = Math.max(1, endPage - maxPagesToShow + 1);
    }
    
    // First page button
    if (startPage > 1) {
        const firstPageButton = document.createElement('button');
        firstPageButton.className = 'px-3 py-1 border rounded mr-1 hover:bg-gray-100';
        firstPageButton.textContent = '1';
        firstPageButton.onclick = () => fetchCustomerAccounts(1, search, sort);
        paginationContainer.appendChild(firstPageButton);
        
        if (startPage > 2) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'px-2';
            ellipsis.textContent = '...';
            paginationContainer.appendChild(ellipsis);
        }
    }
    
    // Page number buttons
    for (let i = startPage; i <= endPage; i++) {
        const pageButton = document.createElement('button');
        pageButton.className = `px-3 py-1 border rounded mx-0.5 ${i === currentPage ? 'bg-sidebar-accent text-white' : 'hover:bg-gray-100'}`;
        pageButton.textContent = i;
        pageButton.onclick = () => fetchCustomerAccounts(i, search, sort);
        paginationContainer.appendChild(pageButton);
    }
    
    // Last page button
    if (endPage < totalPages) {
        if (endPage < totalPages - 1) {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'px-2';
            ellipsis.textContent = '...';
            paginationContainer.appendChild(ellipsis);
        }
        
        const lastPageButton = document.createElement('button');
        lastPageButton.className = 'px-3 py-1 border rounded ml-1 hover:bg-gray-100';
        lastPageButton.textContent = totalPages;
        lastPageButton.onclick = () => fetchCustomerAccounts(totalPages, search, sort);
        paginationContainer.appendChild(lastPageButton);
    }
    
    // Next button
    const nextButton = document.createElement('button');
    nextButton.className = 'px-3 py-1 border rounded ml-1' + (currentPage === totalPages ? ' opacity-50 cursor-not-allowed' : ' hover:bg-gray-100');
    nextButton.textContent = 'Next';
    nextButton.disabled = currentPage === totalPages;
    nextButton.onclick = () => {
        if (currentPage < totalPages) fetchCustomerAccounts(currentPage + 1, search, sort);
    };
    paginationContainer.appendChild(nextButton);
}

// Client-side search function
function searchCustomers() {
  const searchTerm = document.getElementById('searchCustomer').value.trim().toLowerCase();
  const clearBtn = document.getElementById('clearSearchBtn');
  const table = document.getElementById('customerTable');
  const rows = table.querySelectorAll('tbody tr');
  
  // Show/hide clear button
  clearBtn.classList.toggle('hidden', searchTerm.length === 0);
  
  // If empty search, show all rows
  if (searchTerm === '') {
    rows.forEach(row => row.style.display = '');
    updatePaginationInfo(rows.length, rows.length);
    return;
  }
  
  let visibleCount = 0;
  
  // Search through each row
  rows.forEach(row => {
    const cells = row.querySelectorAll('td');
    let rowMatches = false;
    
    // Check each cell (except the last one with actions)
    for (let i = 0; i < cells.length - 1; i++) {
      const cellText = cells[i].textContent.toLowerCase();
      if (cellText.includes(searchTerm)) {
        rowMatches = true;
        break;
      }
    }
    
    // Show/hide row based on match
    row.style.display = rowMatches ? '' : 'none';
    if (rowMatches) visibleCount++;
  });
  
  // Update the "Showing X-Y of Z" info
  updatePaginationInfo(visibleCount, rows.length);
}

// Helper function to update pagination info
function updatePaginationInfo(visibleCount, totalCount) {
  const infoElement = document.querySelector('#manageAccountSection .text-sm.text-gray-600');
  infoElement.textContent = `Showing ${visibleCount} of ${totalCount} entries`;
}

// Clear search function
function clearSearch() {
  document.getElementById('searchCustomer').value = '';
  document.getElementById('clearSearchBtn').classList.add('hidden');
  searchCustomers(); // This will show all rows again
}

// Initialize with all rows visible
document.addEventListener('DOMContentLoaded', function() {
  const table = document.getElementById('customerTable');
  const rows = table.querySelectorAll('tbody tr');
  updatePaginationInfo(rows.length, rows.length);
});    
    
    // Function to archive a customer account
function archiveCustomerAccount(userId) {
    Swal.fire({
        title: 'Archive Customer Account',
        html: `Are you sure you want to archive this customer account?<br><br>
               <span class="text-sm text-gray-500">Archived accounts can be restored later if needed.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#CA8A04',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, archive it',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        backdrop: `
            rgba(0,0,0,0.6)
            url("/images/nyan-cat.gif")
            left top
            no-repeat
        `
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Archiving...',
                html: 'Please wait while we archive the customer account',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request to archive the user
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('status', 0); // 0 means archived
            formData.append('user_type', 3); // 3 is customer type

            fetch('../admin/accountManagement/archive_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        title: 'Archived!',
                        text: 'Customer account has been archived.',
                        icon: 'success',
                        confirmButtonColor: '#28a745',
                        timer: 2000,
                        timerProgressBar: true,
                        willClose: () => {
                            // Refresh the customer list
                            fetchCustomerAccounts();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to archive customer account',
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while archiving the account: ' + error,
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            });
        }
    });
}

// Update the deleteCustomerAccount function in your table to call archiveCustomerAccount instead
function deleteCustomerAccount(userId) {
    archiveCustomerAccount(userId);
}

// Global variables to track current table state
let currentPage = 1;
let currentSearch = '';
let currentSort = 'id_asc';

// Global variables for edit modal
let originalEmail = '';
let originalPhone = '';
let currentUserId = 0;
let emailChanged = false;

function openEditCustomerAccountModal(userId) {
    currentUserId = userId;
    emailChanged = false;
    
    // Show loading state
    Swal.fire({
        title: 'Loading...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Fetch user details
    fetch(`accountManagement/fetch_customer_details.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.success) {
                originalEmail = data.user.email;
                originalPhone = data.user.phone_number;
                
                // Create modal HTML with validation indicators
                const modalHTML = `
                <div id="editCustomerModal" class="fixed inset-0 z-50 overflow-auto bg-black bg-opacity-40 flex items-center justify-center">
                    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
                        <!-- Modal Header -->
                        <div class="flex justify-between items-center p-4 border-b border-sidebar-border">
                            <h3 class="text-lg font-semibold text-sidebar-text">Edit Customer Account</h3>
                            <button onclick="closeEditCustomerModal()" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <!-- Modal Body -->
                        <div class="p-6">
                            <form id="editCustomerForm" class="space-y-4">
                                <input type="hidden" name="user_id" value="${data.user.id}">
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Customer ID</label>
                                    <input type="text" value="#CUST-${String(data.user.id).padStart(3, '0')}" 
                                           class="w-full p-2 border border-gray-300 rounded bg-gray-100 text-sm" readonly>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="first_name" value="${data.user.first_name || ''}" 
                                           class="w-full p-2 border border-gray-300 rounded text-sm focus:ring-sidebar-accent focus:border-sidebar-accent" required>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="last_name" value="${data.user.last_name || ''}" 
                                           class="w-full p-2 border border-gray-300 rounded text-sm focus:ring-sidebar-accent focus:border-sidebar-accent" required>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Middle Name</label>
                                    <input type="text" name="middle_name" value="${data.user.middle_name || ''}" 
                                           class="w-full p-2 border border-gray-300 rounded text-sm focus:ring-sidebar-accent focus:border-sidebar-accent">
                                </div>
                                
                                <div class="relative">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                    <input type="email" name="email" value="${data.user.email || ''}" 
                                           class="w-full p-2 border border-gray-300 rounded text-sm focus:ring-sidebar-accent focus:border-sidebar-accent" 
                                           required oninput="checkEmailAvailability(this.value)">
                                    <div id="emailAvailability" class="absolute right-2 top-7 text-xs hidden">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="ml-1">Available</span>
                                    </div>
                                    <div id="emailUnavailable" class="absolute right-2 top-7 text-xs hidden">
                                        <i class="fas fa-times-circle text-red-500"></i>
                                        <span class="ml-1">In use</span>
                                    </div>
                                </div>
                                
                                <div class="relative">
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
                                    <input type="text" name="phone_number" value="${data.user.phone_number || ''}"
                                       class="w-full p-2 border border-gray-300 rounded text-sm focus:ring-sidebar-accent focus:border-sidebar-accent"
                                       inputmode="numeric" pattern="[0-9]*" maxlength="15"
                                       required oninput="this.value = this.value.replace(/[^0-9]/g, ''); checkPhoneAvailability(this.value)">
                                    <div id="phoneAvailability" class="absolute right-2 top-7 text-xs hidden">
                                        <i class="fas fa-check-circle text-green-500"></i>
                                        <span class="ml-1">Available</span>
                                    </div>
                                    <div id="phoneUnavailable" class="absolute right-2 top-7 text-xs hidden">
                                        <i class="fas fa-times-circle text-red-500"></i>
                                        <span class="ml-1">In use</span>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1">Branch Location <span class="text-red-500">*</span></label>
                                    <select name="branch_loc" class="w-full p-2 border border-gray-300 rounded text-sm focus:ring-sidebar-accent focus:border-sidebar-accent" required>
                                        <option value="">-- Select Branch --</option>
                                        ${data.branches.map(branch => `
                                            <option value="${branch.branch_id}" ${data.user.branch_loc == branch.branch_id ? 'selected' : ''}>
                                                ${branch.branch_name}
                                            </option>
                                        `).join('')}
                                    </select>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Modal Footer -->
                        <div class="flex justify-end p-4 border-t border-gray-200 space-x-3">
                            <button onclick="closeEditCustomerModal()" 
                                    class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-100 text-sm">
                                Cancel
                            </button>
                            <button onclick="validateAndSaveCustomerChanges()" 
                                    class="px-4 py-2 bg-sidebar-accent text-white rounded hover:bg-darkgold text-sm">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>`;

                // Add modal to DOM
                document.body.insertAdjacentHTML('beforeend', modalHTML);

                // Add event listeners for email and phone changes
                const emailInput = document.querySelector('#editCustomerModal input[name="email"]');
                emailInput.addEventListener('change', function() {
                    emailChanged = this.value !== originalEmail;
                });

                // Add event listener for Escape key
                document.addEventListener('keydown', function handleEscape(e) {
                    if (e.key === 'Escape') {
                        closeEditCustomerModal();
                        document.removeEventListener('keydown', handleEscape);
                    }
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: data.message || 'Failed to fetch customer details',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            }
        })
        .catch(error => {
            Swal.close();
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while fetching customer details',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
            console.error('Error:', error);
        });
}

// Check if email is available
function checkEmailAvailability(email) {
    const emailAvailability = document.getElementById('emailAvailability');
    const emailUnavailable = document.getElementById('emailUnavailable');
    
    // Hide both indicators initially
    emailAvailability.classList.add('hidden');
    emailUnavailable.classList.add('hidden');

    // If email is empty or unchanged, return early
    if (!email || email === originalEmail) {
        return;
    }

    // Validate email format
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        return;
    }

    // Show loading state
    emailAvailability.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
    emailAvailability.classList.remove('hidden');

    fetch(`accountManagement/check_email.php?email=${encodeURIComponent(email)}&current_user=${currentUserId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Availability response:', data);
            
            if (data.available) {
                emailAvailability.innerHTML = '<i class="fas fa-check-circle text-green-500"></i> Available';
                emailAvailability.classList.remove('hidden');
                emailUnavailable.classList.add('hidden');
            } else {
                emailAvailability.classList.add('hidden');
                emailUnavailable.innerHTML = `<i class="fas fa-times-circle text-red-500"></i> ${data.message || 'In use'}`;
                emailUnavailable.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error checking email availability:', error);
            emailAvailability.classList.add('hidden');
            emailUnavailable.innerHTML = '<i class="fas fa-exclamation-triangle text-yellow-500"></i> Error checking';
            emailUnavailable.classList.remove('hidden');
            setTimeout(() => emailUnavailable.classList.add('hidden'), 3000);
        });
}

// Check if phone number is available
function checkPhoneAvailability(phone) {
    if (phone === originalPhone) {
        // If phone hasn't changed, hide both indicators
        document.getElementById('phoneAvailability').classList.add('hidden');
        document.getElementById('phoneUnavailable').classList.add('hidden');
        return;
    }

    // Simple phone validation first
    const phonePattern = /^09\d{9}$/;
    const cleanedPhone = phone.replace(/[^0-9]/g, '');
    if (!phonePattern.test(cleanedPhone)) {
        document.getElementById('phoneAvailability').classList.add('hidden');
        document.getElementById('phoneUnavailable').classList.add('hidden');
        return;
    }

    fetch(`accountManagement/check_phone.php?phone=${encodeURIComponent(phone)}&current_user=${currentUserId}`)
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                document.getElementById('phoneAvailability').classList.remove('hidden');
                document.getElementById('phoneUnavailable').classList.add('hidden');
            } else {
                document.getElementById('phoneAvailability').classList.add('hidden');
                document.getElementById('phoneUnavailable').classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error checking phone availability:', error);
        });
}

function validateAndSaveCustomerChanges() {
    const form = document.getElementById('editCustomerForm');
    if (!form) return;

    // Get form data
    const formData = new FormData(form);
    const newEmail = formData.get('email');
    const newPhone = formData.get('phone_number');

    // Validate required fields
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('border-red-500');
            isValid = false;
        } else {
            field.classList.remove('border-red-500');
        }
    });

    if (!isValid) {
        Swal.fire({
            title: 'Validation Error',
            text: 'Please fill in all required fields',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
        return;
    }

    // Check if email is available (if changed)
    if (newEmail !== originalEmail) {
        // Check if the unavailable indicator is visible
        if (!document.getElementById('emailUnavailable').classList.contains('hidden')) {
            Swal.fire({
                title: 'Email In Use',
                text: 'The new email address is already in use by another account.',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
            return;
        }
        
        // Also check if we haven't finished checking availability
        if (document.getElementById('emailAvailability').classList.contains('hidden') && 
            document.getElementById('emailUnavailable').classList.contains('hidden')) {
            Swal.fire({
                title: 'Please Wait',
                text: 'Email availability check is still in progress',
                icon: 'warning',
                confirmButtonColor: '#d33'
            });
            return;
        }
    }

    // Check if phone is available (if changed)
    if (newPhone !== originalPhone) {
        // Check if the unavailable indicator is visible
        if (!document.getElementById('phoneUnavailable').classList.contains('hidden')) {
            Swal.fire({
                title: 'Phone In Use',
                text: 'The new phone number is already in use by another account.',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
            return;
        }
        
        // Also check if we haven't finished checking availability
        if (document.getElementById('phoneAvailability').classList.contains('hidden') && 
            document.getElementById('phoneUnavailable').classList.contains('hidden')) {
            Swal.fire({
                title: 'Please Wait',
                text: 'Phone availability check is still in progress',
                icon: 'warning',
                confirmButtonColor: '#d33'
            });
            return;
        }
    }

    // If email was changed, show OTP verification
    if (emailChanged) {
        showEditOTPModal(newEmail);
    } else {
        // Otherwise, save changes directly
        saveCustomerChanges();
    }
}
// Show OTP modal for email verification during edit
function showEditOTPModal(email) {
    // Set the email in the OTP modal
    document.getElementById('otpEmail').textContent = email;
    
    // Send OTP to email
    const formData = new FormData();
    formData.append('email', email);
    
    // Show loading
    Swal.fire({
        title: 'Sending OTP...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../admin/addCustomer/send_otp.php', true);
    
    xhr.onload = function() {
        Swal.close();
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            if (response.success) {
                // Show OTP modal
                const modal = document.getElementById('otpVerificationModal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                
                // Update the verify button to handle edit flow
                const verifyBtn = modal.querySelector('button[onclick="verifyOTP()"]');
                verifyBtn.setAttribute('onclick', 'verifyEditOTP()');
                
                // Focus on first OTP input
                const otpInputs = document.querySelectorAll('.otp-input');
                if (otpInputs.length > 0) {
                    otpInputs[0].focus();
                }
            } else {
                Swal.fire({
                    title: 'Error Occurred',
                    text: response.message || 'Failed to send OTP',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#d33'
                });
            }
        }
    };
    
    xhr.onerror = function() {
        Swal.close();
        Swal.fire({
            title: 'Connection Error',
            text: 'Failed to connect to server',
            icon: 'error'
        });
    };
    
    xhr.send(formData);
}

// Verify OTP for edit flow
function verifyEditOTP() {
    // Collect OTP from inputs
    const otpInputs = document.querySelectorAll('.otp-input');
    let otpValue = '';
    
    otpInputs.forEach(input => {
        otpValue += input.value;
    });
    
    // Check if OTP is complete
    if (otpValue.length !== 6) {
        document.getElementById('otpError').textContent = 'Please enter all 6 digits';
        document.getElementById('otpError').classList.remove('hidden');
        return;
    }
    
    // Verify OTP
    const formData = new FormData();
    formData.append('otp', otpValue);
    
    // Show loading
    Swal.fire({
        title: 'Verifying...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../admin/addCustomer/verify_otp.php', true);
    
    xhr.onload = function() {
        Swal.close();
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            if (response.success) {
                // OTP verified, proceed with saving changes
                closeOtpModal();
                saveCustomerChanges();
            } else {
                document.getElementById('otpError').textContent = response.message;
                document.getElementById('otpError').classList.remove('hidden');
            }
        }
    };
    
    xhr.send(formData);
}

function closeEditCustomerModal() {
    const modal = document.getElementById('editCustomerModal');
    if (modal) {
        modal.remove();
    }
}

function saveCustomerChanges() {
    const form = document.getElementById('editCustomerForm');
    if (!form) return;

    // Get all current form values
    const formData = new FormData(form);
    const currentEmail = formData.get('email');
    
    // Validate required fields
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('border-red-500');
            isValid = false;
        } else {
            field.classList.remove('border-red-500');
        }
    });

    if (!isValid) {
        Swal.fire({
            title: 'Validation Error',
            text: 'Please fill in all required fields',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
        return;
    }

    // Show loading state
    Swal.fire({
        title: 'Saving...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Add the current_user parameter to check against
    formData.append('current_user', currentUserId);
    
    fetch('accountManagement/update_customer_account.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        Swal.close();
        if (data.success) {
            Swal.fire({
                title: 'Success!',
                text: 'Customer account updated successfully',
                icon: 'success',
                confirmButtonColor: '#28a745',
                willClose: () => {
                    closeEditCustomerModal();
                    // Refresh the customer list with current filters
                    fetchCustomerAccounts(currentPage, currentSearch, currentSort);
                }
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: data.message || 'Failed to update customer account',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({
            title: 'Error!',
            text: 'An error occurred while updating customer account: ' + error.message,
            icon: 'error',
            confirmButtonColor: '#d33'
        });
        console.error('Error:', error);
    });
}

// Function to view archived accounts
function viewArchivedAccounts() {
    // Show loading state
    const viewArchivedBtn = document.getElementById('viewArchivedBtn');
    const originalBtnText = viewArchivedBtn.innerHTML;
    viewArchivedBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Loading...';
    viewArchivedBtn.disabled = true;

    // Create modal container if it doesn't exist
    if (!document.getElementById('archivedAccountsModal')) {
        const modalHTML = `
        <div id="archivedAccountsModal" class="hidden fixed z-50 inset-0 overflow-auto bg-black bg-opacity-40">
            <div class="bg-white mx-auto my-[5%] p-5 border border-gray-300 w-4/5 max-w-4xl rounded-lg shadow-lg">
                <div class="flex justify-between items-center mb-5 border-b border-gray-300 pb-3">
                    <h3 class="m-0 text-lg font-semibold">Archived Customer Accounts</h3>
                    <span onclick="closeArchivedAccountsModal()" class="cursor-pointer text-2xl">&times;</span>
                </div>
                <div class="max-h-[60vh] overflow-y-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-sidebar-hover text-left">
                                <th class="p-3 border-b border-sidebar-border text-sm font-medium text-sidebar-text">Customer ID</th>
                                <th class="p-3 border-b border-sidebar-border text-sm font-medium text-sidebar-text">Name</th>
                                <th class="p-3 border-b border-sidebar-border text-sm font-medium text-sidebar-text">Email</th>
                                <th class="p-3 border-b border-sidebar-border text-sm font-medium text-sidebar-text">Type</th>
                                <th class="p-3 border-b border-sidebar-border text-sm font-medium text-sidebar-text">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="archivedAccountsTableBody">
                            <!-- Content will be loaded here -->
                        </tbody>
                    </table>
                </div>
                <div class="mt-5 text-right border-t border-gray-300 pt-4">
                    <button onclick="closeArchivedAccountsModal()" class="bg-gray-600 text-white border-none py-2 px-4 rounded-md cursor-pointer">Close</button>
                </div>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    // Fetch archived accounts
    fetch('../admin/accountManagement/fetch_archived_accounts.php?user_type=3')
        .then(response => response.json())
        .then(data => {
            document.getElementById('archivedAccountsTableBody').innerHTML = data.tableContent;
            document.getElementById('archivedAccountsModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error!',
                text: 'Failed to load archived accounts',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        })
        .finally(() => {
            viewArchivedBtn.innerHTML = originalBtnText;
            viewArchivedBtn.disabled = false;
        });
}

// Function to close the archived accounts modal
function closeArchivedAccountsModal() {
    document.getElementById('archivedAccountsModal').classList.add('hidden');
}


// Function to unarchive an account
function unarchiveAccount(userId) {
    Swal.fire({
        title: 'Unarchive Account',
        html: `Are you sure you want to restore this account?<br><br>
               <span class="text-sm text-gray-500">The account will be active again.</span>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, restore it',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Restoring...',
                html: 'Please wait while we restore the account',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request to unarchive the account
            const formData = new FormData();
            formData.append('id', userId);

            fetch('../admin/accountManagement/unarchive_account.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Restored!',
                        text: 'Account has been successfully restored.',
                        icon: 'success',
                        confirmButtonColor: '#28a745',
                        timer: 2000,
                        timerProgressBar: true,
                        willClose: () => {
                            // Close the archived accounts modal
                            closeArchivedAccountsModal();
                            // Reload the page to reflect changes
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to restore account',
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while restoring the account',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            });
        }
    });
}
    
      
    // Mode switching functionality
    function switchMode(mode) {
        if (mode === 'create') {
            document.getElementById('createAccountSection').classList.remove('hidden');
            document.getElementById('manageAccountSection').classList.add('hidden');
            document.getElementById('createBtn').classList.add('bg-sidebar-accent', 'text-white');
            document.getElementById('createBtn').classList.remove('bg-transparent', 'text-sidebar-text');
            document.getElementById('manageBtn').classList.add('bg-transparent', 'text-sidebar-text');
            document.getElementById('manageBtn').classList.remove('bg-sidebar-accent', 'text-white');
            document.getElementById('searchContainer').classList.add('hidden');
            document.getElementById('viewArchivedBtn').classList.add('hidden');
        } else { // manage mode
            document.getElementById('createAccountSection').classList.add('hidden');
            document.getElementById('manageAccountSection').classList.remove('hidden');
            document.getElementById('manageBtn').classList.add('bg-sidebar-accent', 'text-white');
            document.getElementById('manageBtn').classList.remove('bg-transparent', 'text-sidebar-text');
            document.getElementById('createBtn').classList.add('bg-transparent', 'text-sidebar-text');
            document.getElementById('createBtn').classList.remove('bg-sidebar-accent', 'text-white');
            document.getElementById('searchContainer').classList.remove('hidden');
            document.getElementById('viewArchivedBtn').classList.remove('hidden');
            fetchCustomerAccounts(); // Load data when manage section is shown
        }
    }
    
    
    
    // On page load, default to manage mode
    document.addEventListener('DOMContentLoaded', function() {
        switchMode('manage'); // Set manage as default view
        fetchCustomerAccounts(); // Load initial data
    });


  </script>
  <script src="sidebar.js"></script>
  <script src="tailwind.js"></script>
</body>
</html>