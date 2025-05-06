<?php
//employee_chat.php
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

// Database connection
require_once '../db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Expenses</title>
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
  <div id="main-content" class="ml-64 p-6 bg-gray-50 min-h-screen transition-all duration-300 main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Expense Tracking</h1>
    </div>
    <div class="flex space-x-3">
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-bell"></i>
      </button>
       
    </div>
  </div>

  <!-- Analytics Cards -->
  <div class="mb-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
      <div class="bg-white rounded-lg shadow-sidebar p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
        <div class="flex items-center mb-3">
          <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mr-3">
            <i class="fas fa-peso-sign text-lg"></i>
          </div>
          <span class="text-sidebar-text font-medium">Total Expenses</span>
        </div>
        <div class="text-3xl font-bold mb-2 text-sidebar-text">₱12,340</div>
        <div class="text-sm text-green-600 flex items-center">
          <i class="fas fa-arrow-up mr-1"></i> 8% from last month
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow-sidebar p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
        <div class="flex items-center mb-3">
          <div class="w-12 h-12 rounded-lg bg-green-100 text-green-600 flex items-center justify-center mr-3">
            <i class="fas fa-chart-line text-lg"></i>
          </div>
          <span class="text-sidebar-text font-medium">This Month</span>
        </div>
        <div class="text-3xl font-bold mb-2 text-sidebar-text">₱1,700</div>
        <div class="text-sm text-green-600 flex items-center">
          <i class="fas fa-arrow-up mr-1"></i> 12% from last month
        </div>
      </div>
      
      <div class="bg-white rounded-lg shadow-sidebar p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
        <div class="flex items-center mb-3">
          <div class="w-12 h-12 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center mr-3">
            <i class="fas fa-exclamation-triangle text-lg"></i>
          </div>
          <span class="text-sidebar-text font-medium">Pending Payments</span>
        </div>
        <div class="text-3xl font-bold mb-2 text-sidebar-text">2</div>
        <div class="text-sm text-red-600 flex items-center">
          <i class="fas fa-arrow-down mr-1"></i> 3% from last month
        </div>
      </div>
    </div>
  </div>

  <!-- Expenses Table Card -->
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
    <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
      <h3 class="font-medium text-sidebar-text">Expenses</h3>
      <div class="flex items-center gap-3">
        <div class="relative">
          <input type="text" placeholder="Search..." class="pl-9 pr-4 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
          <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <i class="fas fa-search text-gray-400"></i>
          </div>
        </div>
        <button class="px-4 py-2 bg-sidebar-accent text-white rounded-md text-sm flex items-center hover:bg-darkgold transition-all duration-300" onclick="openAddExpenseModal()">
          <i class="fas fa-plus mr-2"></i> Add Expense
        </button>
      </div>
    </div>
    <div class="overflow-x-auto scrollbar-thin">
      <table class="w-full">
        <thead>
          <tr class="bg-sidebar-hover">
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">
              <div class="flex items-center">
                ID <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">
              <div class="flex items-center">
                Description <i class="fas fa-sort ml-1 text-gray-400"></i>
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
        <tbody>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text font-medium">#EXP-001</td>
            <td class="p-4 text-sm text-sidebar-text">Office Supplies</td>
            <td class="p-4 text-sm text-sidebar-text">
              <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs">Supplies</span>
            </td>
            <td class="p-4 text-sm text-sidebar-text">₱500</td>
            <td class="p-4 text-sm text-sidebar-text">2023-10-01</td>
            <td class="p-4 text-sm">
              <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Paid</span>
            </td>
            <td class="p-4 text-sm">
              <div class="flex space-x-2">
                <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all">
                   <i class="fas fa-archive text-sidebar-accent"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text font-medium">#EXP-002</td>
            <td class="p-4 text-sm text-sidebar-text">Utility Bills</td>
            <td class="p-4 text-sm text-sidebar-text">
              <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Utilities</span>
            </td>
            <td class="p-4 text-sm text-sidebar-text">₱1,200</td>
            <td class="p-4 text-sm text-sidebar-text">2023-10-05</td>
            <td class="p-4 text-sm">
              <span class="px-2 py-1 bg-orange-100 text-orange-800 rounded-full text-xs">Pending</span>
            </td>
            <td class="p-4 text-sm">
              <div class="flex space-x-2">
                <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all">
                   <i class="fas fa-archive text-sidebar-accent"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
      <div class="text-sm text-gray-500">Showing 2 of 10 expenses</div>
      <div class="flex space-x-1">
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&laquo;</button>
        <button class="px-3 py-1 bg-sidebar-accent text-white rounded text-sm">1</button>
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">2</button>
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&raquo;</button>
      </div>
    </div>
  </div>

  <!-- Add Expense Modal -->
<div class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="addExpenseModal">
  <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-xl max-h-[90vh] overflow-y-auto">
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
      <form id="expenseForm">
        <div class="mb-5">
          <label for="expenseDescription" class="block mb-2 font-medium text-gray-700">Description</label>
          <input type="text" id="expenseDescription" name="expenseDescription" placeholder="Enter expense description" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
        </div>
        <div class="mb-5">
          <label for="expenseCategory" class="block mb-2 font-medium text-gray-700">Category</label>
          <select id="expenseCategory" name="expenseCategory" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
            <option value="">Select a category</option>
            <option value="Supplies">Supplies</option>
            <option value="Utilities">Utilities</option>
            <option value="Salaries">Salaries</option>
            <option value="Maintenance">Maintenance</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="mb-5">
          <label for="expenseAmount" class="block mb-2 font-medium text-gray-700">Amount (₱)</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="expenseAmount" name="expenseAmount" placeholder="0.00" step="0.01" min="0" class="w-full pl-8 px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
          </div>
        </div>
        <div class="mb-5">
          <label for="expenseDate" class="block mb-2 font-medium text-gray-700">Date</label>
          <input type="date" id="expenseDate" name="expenseDate" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
        </div>
        <div class="mb-5">
          <label for="expenseStatus" class="block mb-2 font-medium text-gray-700">Status</label>
          <select id="expenseStatus" name="expenseStatus" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
            <option value="Paid">Paid</option>
            <option value="Pending">Pending</option>
          </select>
        </div>
        <div class="mb-5">
          <label for="expenseNotes" class="block mb-2 font-medium text-gray-700">Notes <span class="text-xs text-gray-500">(Optional)</span></label>
          <textarea id="expenseNotes" name="expenseNotes" rows="3" placeholder="Add any additional details here" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent"></textarea>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeAddExpenseModal()">Cancel</button>
      <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center" onclick="addExpense()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
          <path d="M12 5v14"></path>
          <path d="M5 12h14"></path>
        </svg>
        Add Expense
      </button>
    </div>
  </div>
</div>

<!-- Edit Expense Modal -->
<div class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="editExpenseModal">
  <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-xl max-h-[90vh] overflow-y-auto">
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
      <form id="editExpenseForm">
        <input type="hidden" id="editExpenseId" name="editExpenseId">
        <div class="mb-5">
          <label for="editExpenseDescription" class="block mb-2 font-medium text-gray-700">Description</label>
          <input type="text" id="editExpenseDescription" name="editExpenseDescription" placeholder="Enter expense description" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
        </div>
        <div class="mb-5">
          <label for="editExpenseCategory" class="block mb-2 font-medium text-gray-700">Category</label>
          <select id="editExpenseCategory" name="editExpenseCategory" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
            <option value="Supplies">Supplies</option>
            <option value="Utilities">Utilities</option>
            <option value="Salaries">Salaries</option>
            <option value="Maintenance">Maintenance</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="mb-5">
          <label for="editExpenseAmount" class="block mb-2 font-medium text-gray-700">Amount (₱)</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="editExpenseAmount" name="editExpenseAmount" placeholder="0.00" step="0.01" min="0" class="w-full pl-8 px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
          </div>
        </div>
        <div class="mb-5">
          <label for="editExpenseDate" class="block mb-2 font-medium text-gray-700">Date</label>
          <input type="date" id="editExpenseDate" name="editExpenseDate" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
        </div>
        <div class="mb-5">
          <label for="editExpenseStatus" class="block mb-2 font-medium text-gray-700">Status</label>
          <select id="editExpenseStatus" name="editExpenseStatus" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
            <option value="Paid">Paid</option>
            <option value="Pending">Pending</option>
          </select>
        </div>
        <div class="mb-5">
          <label for="editExpenseNotes" class="block mb-2 font-medium text-gray-700">Notes <span class="text-xs text-gray-500">(Optional)</span></label>
          <textarea id="editExpenseNotes" name="editExpenseNotes" rows="3" placeholder="Add any additional details here" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent"></textarea>
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

  <script src="employee_script.js"></script>
  <script>
    // Initialize current date for date inputs
    document.addEventListener('DOMContentLoaded', function() {
      const today = new Date().toISOString().split('T')[0];
      document.getElementById('expenseDate').value = today;
      
      // Set default filter dates (last 30 days)
      const thirtyDaysAgo = new Date();
      thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
      document.getElementById('startDate').value = thirtyDaysAgo.toISOString().split('T')[0];
      document.getElementById('endDate').value = today;
    });
    
    // Function to toggle sidebar
    function toggleSidebar() {
      const sidebar = document.querySelector('.sidebar');
      sidebar.classList.toggle('collapsed');
    }
    
    // Function to toggle filter dropdown
    function toggleFilter() {
      document.getElementById('filterDropdown').classList.toggle('show');
    }
    
    // Close filter dropdown when clicking outside
    window.onclick = function(event) {
      if (!event.target.matches('.filter-btn') && !event.target.closest('.filter-content')) {
        const dropdowns = document.getElementsByClassName('filter-content');
        for (let i = 0; i < dropdowns.length; i++) {
          const openDropdown = dropdowns[i];
          if (openDropdown.classList.contains('show')) {
            openDropdown.classList.remove('show');
          }
        }
      }
    }
    
    // Function to apply filters
    function applyFilters() {
      // Implementation would go here
      alert('Filters applied!');
      document.getElementById('filterDropdown').classList.remove('show');
    }

    // Function to open the Add Expense Modal
    function openAddExpenseModal() {
      document.getElementById('addExpenseModal').style.display = 'flex';
      document.getElementById('expenseDescription').focus();
    }

    // Function to close the Add Expense Modal
    function closeAddExpenseModal() {
      document.getElementById('addExpenseModal').style.display = 'none';
      document.getElementById('expenseForm').reset();
    }

    // Function to add an expense
    function addExpense() {
      const form = document.getElementById('expenseForm');
      if (form.checkValidity()) {
        // Implementation would go here to save the expense
        
        // Show success notification
        showNotification('Expense added successfully!', 'success');
        closeAddExpenseModal();
        
        // Reset form for next use
        form.reset();
        
        // Set current date for next expense
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('expenseDate').value = today;
      } else {
        form.reportValidity();
      }
    }

    // Function to open the Edit Expense Modal
    function openEditExpenseModal(id, description, category, amount, date) {
      document.getElementById('editExpenseId').value = id;
      document.getElementById('editExpenseDescription').value = description;
      document.getElementById('editExpenseCategory').value = category;
      document.getElementById('editExpenseAmount').value = amount;
      document.getElementById('editExpenseDate').value = date;
      document.getElementById('editExpenseStatus').value = id === 'EXP-001' ? 'Paid' : 'Pending';
      
      document.getElementById('editExpenseModal').style.display = 'flex';
      document.getElementById('editExpenseDescription').focus();
    }

    // Function to close the Edit Expense Modal
    function closeEditExpenseModal() {
      document.getElementById('editExpenseModal').style.display = 'none';
    }

    // Function to save changes to an expense
    function saveExpenseChanges() {
      const form = document.getElementById('editExpenseForm');
      if (form.checkValidity()) {
        // Implementation would go here to update the expense
        
        // Show success notification
        const expenseId = document.getElementById('editExpenseId').value;
        showNotification(`Expense ${expenseId} updated successfully!`, 'success');
        closeEditExpenseModal();
      } else {
        form.reportValidity();
      }
    }
    
    // Function to confirm delete
    function confirmDelete(id) {
      document.getElementById('deleteExpenseId').textContent = id;
      document.getElementById('deleteConfirmModal').style.display = 'flex';
    }
    
    // Function to close delete confirmation modal
    function closeDeleteConfirmModal() {
      document.getElementById('deleteConfirmModal').style.display = 'none';
    }

    // Function to delete an expense
    function deleteExpense() {
      const expenseId = document.getElementById('deleteExpenseId').textContent;
      
      // Implementation would go here to delete the expense
      
      // Show success notification
      showNotification(`Expense ${expenseId} deleted successfully!`, 'success');
      closeDeleteConfirmModal();
    }
    
    // Function to show notification
    function showNotification(message, type = 'info') {
      // Create notification element
      const notification = document.createElement('div');
      notification.className = `notification ${type}`;
      notification.innerHTML = `
        <div class="notification-content">
          <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}"></i>
          <span>${message}</span>
        </div>
        <button onclick="this.parentElement.remove()">
          <i class="fas fa-times"></i>
        </button>
      `;
      
      // Add notification to page
      document.body.appendChild(notification);
      
      // Remove after 3 seconds
      setTimeout(() => {
        notification.classList.add('fade-out');
        setTimeout(() => {
          notification.remove();
        }, 300);
      }, 3000);
    }
    
    // Add notification styles dynamically
    const style = document.createElement('style');
    style.textContent = `
      .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        min-width: 300px;
        padding: 15px;
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        display: flex;
        justify-content: space-between;
        align-items: center;
        animation: slide-in 0.3s ease;
      }
      
      .notification-content {
        display: flex;
        align-items: center;
        gap: 10px;
      }
      
      .notification.success i {
        color: #4caf50;
      }
      
      .notification.info i {
        color: #2196f3;
      }
      
      .notification button {
        background: none;
        border: none;
        cursor: pointer;
        color: #757575;
      }
      
      .fade-out {
        animation: fade-out 0.3s ease forwards;
      }
      
      @keyframes slide-in {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      
      @keyframes fade-out {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
      }
      
      /* Responsive improvements */
      @media (max-width: 768px) {
        .stats-container {
          flex-direction: column;
        }
        
        .stat-card {
          width: 100%;
          margin-bottom: 10px;
        }
        
        .table-actions {
          flex-direction: column;
          align-items: stretch;
        }
        
        .search-container, .filter-dropdown, .table-actions button {
          margin-bottom: 10px;
          width: 100%;
        }
        
        .notification {
          min-width: auto;
          width: calc(100% - 40px);
        }
      }
    `;
    document.head.appendChild(style);
  </script>
  <script src="tailwind.js"></script>
  <script src="sidebar.js"></script>
</body>
</html>