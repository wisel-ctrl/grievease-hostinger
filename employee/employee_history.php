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
  <title>GrievEase - History</title>
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
      <h1 class="text-2xl font-bold text-sidebar-text">Service History</h1>
    </div>
    <div class="flex space-x-3">
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-bell"></i>
      </button>
       
    </div>
  </div>

  <!-- Ongoing Services Section -->
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
    <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
      <h3 class="text-lg font-semibold text-sidebar-text">Ongoing Services</h3>
      <div class="flex gap-2">
        <div class="relative">
          <input type="text" id="searchOngoing" placeholder="Search..." class="pl-9 pr-4 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
          <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <i class="fas fa-search text-gray-400"></i>
          </div>
        </div>
        <button class="px-4 py-2 bg-sidebar-accent text-white rounded-md text-sm flex items-center hover:bg-darkgold transition-all duration-300" onclick="openAddServiceModal()">
          <i class="fas fa-plus mr-2"></i> Add Service
        </button>
      </div>
    </div>
    <div class="overflow-x-auto scrollbar-thin">
      <table class="w-full">
        <thead>
          <tr class="bg-sidebar-hover">
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">ID</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">Client Name</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">Service Type</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">Date</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">Status</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(5)">Outstanding Balance</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text">#SRV-001</td>
            <td class="p-4 text-sm text-sidebar-text">John Doe</td>
            <td class="p-4 text-sm text-sidebar-text">Memorial Service</td>
            <td class="p-4 text-sm text-sidebar-text">2023-10-15</td>
            <td class="p-4 text-sm">
              <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-500">Pending</span>
            </td>
            <td class="p-4 text-sm text-sidebar-text">₱500</td>
            <td class="p-4 text-sm">
              <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all" onclick="openEditServiceModal('SRV-001')">
                <i class="fas fa-edit"></i>
              </button>
              <button class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all" onclick="openAssignStaffModal('SRV-001')">
                <i class="fas fa-users"></i>
              </button>
              <button class="p-1.5 bg-purple-100 text-purple-600 rounded hover:bg-purple-200 transition-all" onclick="openCompleteModal('SRV-001')">
                <i class="fas fa-check"></i>
              </button>
            </td>
          </tr>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text">#SRV-002</td>
            <td class="p-4 text-sm text-sidebar-text">Jane Smith</td>
            <td class="p-4 text-sm text-sidebar-text">Funeral Service</td>
            <td class="p-4 text-sm text-sidebar-text">2023-10-20</td>
            <td class="p-4 text-sm">
              <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-500">Pending</span>
            </td>
            <td class="p-4 text-sm text-sidebar-text">₱0</td>
            <td class="p-4 text-sm">
              <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all" onclick="openEditServiceModal('SRV-002')">
                <i class="fas fa-edit"></i>
              </button>
              <button class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all" onclick="openAssignStaffModal('SRV-002')">
                <i class="fas fa-users"></i>
              </button>
              <button class="p-1.5 bg-purple-100 text-purple-600 rounded hover:bg-purple-200 transition-all" onclick="openCompleteModal('SRV-002')">
                <i class="fas fa-check"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Past Services - Fully Paid Section -->
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
    <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
      <h3 class="text-lg font-semibold text-sidebar-text">Past Services - Fully Paid</h3>
      <div class="relative">
        <input type="text" id="searchFullyPaid" placeholder="Search..." class="pl-9 pr-4 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
          <i class="fas fa-search text-gray-400"></i>
        </div>
      </div>
    </div>
    <div class="overflow-x-auto scrollbar-thin">
      <table class="w-full">
        <thead>
          <tr class="bg-sidebar-hover">
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">ID</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">Client Name</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">Service Type</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">Date</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">Status</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text">#SRV-003</td>
            <td class="p-4 text-sm text-sidebar-text">Michael Brown</td>
            <td class="p-4 text-sm text-sidebar-text">Cremation Service</td>
            <td class="p-4 text-sm text-sidebar-text">2023-09-10</td>
            <td class="p-4 text-sm">
              <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-500">Completed</span>
            </td>
            <td class="p-4 text-sm">
              <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all" onclick="viewServiceDetails('SRV-003')">
                <i class="fas fa-eye"></i>
              </button>
            </td>
          </tr>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text">#SRV-004</td>
            <td class="p-4 text-sm text-sidebar-text">Emily Davis</td>
            <td class="p-4 text-sm text-sidebar-text">Visitation</td>
            <td class="p-4 text-sm text-sidebar-text">2023-08-25</td>
            <td class="p-4 text-sm">
              <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-500">Completed</span>
            </td>
            <td class="p-4 text-sm">
              <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all" onclick="viewServiceDetails('SRV-004')">
                <i class="fas fa-eye"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Past Services - With Outstanding Balance Section -->
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
    <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
      <h3 class="text-lg font-semibold text-sidebar-text">Past Services - With Outstanding Balance</h3>
      <div class="relative">
        <input type="text" id="searchOutstanding" placeholder="Search..." class="pl-9 pr-4 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
          <i class="fas fa-search text-gray-400"></i>
        </div>
      </div>
    </div>
    <div class="overflow-x-auto scrollbar-thin">
      <table class="w-full">
        <thead>
          <tr class="bg-sidebar-hover">
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">ID</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">Client Name</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">Service Type</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">Date</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">Status</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(5)">Outstanding Balance</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text">#SRV-005</td>
            <td class="p-4 text-sm text-sidebar-text">Robert Johnson</td>
            <td class="p-4 text-sm text-sidebar-text">Burial Service</td>
            <td class="p-4 text-sm text-sidebar-text">2023-09-05</td>
            <td class="p-4 text-sm">
              <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-500">Completed with Balance</span>
            </td>
            <td class="p-4 text-sm text-sidebar-text">₱350</td>
            <td class="p-4 text-sm">
              <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all" onclick="viewServiceDetails('SRV-005')">
                <i class="fas fa-eye"></i>
              </button>
              <button class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all" onclick="openRecordPaymentModal('SRV-005','Robert Johnson','350')">
                <i class="fas fa-money-bill-wave"></i>
              </button>
            </td>
          </tr>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text">#SRV-006</td>
            <td class="p-4 text-sm text-sidebar-text">Sarah Wilson</td>
            <td class="p-4 text-sm text-sidebar-text">Memorial Service</td>
            <td class="p-4 text-sm text-sidebar-text">2023-08-15</td>
            <td class="p-4 text-sm">
              <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-500">Completed with Balance</span>
            </td>
            <td class="p-4 text-sm text-sidebar-text">₱200</td>
            <td class="p-4 text-sm">
              <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all" onclick="viewServiceDetails('SRV-006')">
                <i class="fas fa-eye"></i>
              </button>
              <button class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all" onclick="openRecordPaymentModal('SRV-006','Sarah Wilson','200')">
                <i class="fas fa-money-bill-wave"></i>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

  <!-- Modal for Editing Service -->
<div class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="editServiceModal">
  <div class="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 class="text-xl font-bold text-white">Edit Service</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeEditServiceModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    
    <!-- Modal Body -->
    <div class="p-6">
      <form id="editServiceForm" class="space-y-4">
        <div>
          <label for="editClientName" class="block text-sm font-medium text-gray-700 mb-1">Client Name</label>
          <input type="text" id="editClientName" name="editClientName" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
        </div>
        <div>
          <label for="editServiceType" class="block text-sm font-medium text-gray-700 mb-1">Service Type</label>
          <select id="editServiceType" name="editServiceType" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
            <option value="Memorial Service">Memorial Service</option>
            <option value="Funeral Service">Funeral Service</option>
            <option value="Cremation Service">Cremation Service</option>
            <option value="Visitation">Visitation</option>
            <option value="Burial Service">Burial Service</option>
          </select>
        </div>
        <div>
          <label for="editServiceDate" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
          <input type="date" id="editServiceDate" name="editServiceDate" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
        </div>
        <div>
          <label for="editOutstandingBalance" class="block text-sm font-medium text-gray-700 mb-1">Outstanding Balance</label>
          <input type="number" id="editOutstandingBalance" name="editOutstandingBalance" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeEditServiceModal()">Cancel</button>
      <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors" onclick="saveServiceChanges()">Save Changes</button>
    </div>
  </div>
</div>

<!-- Assign Staff Modal -->
<div class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="assignStaffModal">
  <div class="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 class="text-xl font-bold text-white">Assign Staff to Service</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeAssignStaffModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    
    <!-- Modal Body -->
    <div class="p-6">
      <form id="assignStaffForm" class="space-y-6">
        <input type="hidden" id="assignServiceId">
        
        <div class="bg-gray-50 p-5 rounded-xl">
          <h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Embalmers
          </h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center">
              <input type="checkbox" id="embalmer1" class="mr-2">
              <label for="embalmer1" class="text-gray-700">Juan Dela Cruz</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="embalmer2" class="mr-2">
              <label for="embalmer2" class="text-gray-700">Pedro Santos</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="embalmer3" class="mr-2">
              <label for="embalmer3" class="text-gray-700">Maria Lopez</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="embalmer4" class="mr-2">
              <label for="embalmer4" class="text-gray-700">Roberto Garcia</label>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 p-5 rounded-xl">
          <h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <rect x="1" y="3" width="15" height="13"></rect>
              <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
              <circle cx="5.5" cy="18.5" r="2.5"></circle>
              <circle cx="18.5" cy="18.5" r="2.5"></circle>
            </svg>
            Drivers
          </h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center">
              <input type="checkbox" id="driver1" class="mr-2">
              <label for="driver1" class="text-gray-700">Carlos Reyes</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="driver2" class="mr-2">
              <label for="driver2" class="text-gray-700">Ricardo Lim</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="driver3" class="mr-2">
              <label for="driver3" class="text-gray-700">Eduardo Torres</label>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 p-5 rounded-xl">
          <h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Other Staff
          </h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center">
              <input type="checkbox" id="staff1" class="mr-2">
              <label for="staff1" class="text-gray-700">Ana Gonzales (Receptionist)</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="staff2" class="mr-2">
              <label for="staff2" class="text-gray-700">Miguel Ramos (Assistant)</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="staff3" class="mr-2">
              <label for="staff3" class="text-gray-700">Luisa Rivera (Coordinator)</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="staff4" class="mr-2">
              <label for="staff4" class="text-gray-700">Paolo Mendoza (Helper)</label>
            </div>
          </div>
        </div>
        
        <div>
          <label for="assignmentNotes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
          <textarea id="assignmentNotes" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent"></textarea>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeAssignStaffModal()">Cancel</button>
      <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center" onclick="saveStaffAssignment()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
          <polyline points="17 21 17 13 7 13 7 21"></polyline>
          <polyline points="7 3 7 8 15 8"></polyline>
        </svg>
        Save Assignment
      </button>
    </div>
  </div>
</div>

<!-- Complete Service Modal -->
<div class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="completeServiceModal">
  <div class="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 class="text-xl font-bold text-white">Complete Service</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeCompleteModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    
    <!-- Modal Body -->
    <div class="p-6">
      <form id="completeServiceForm" class="space-y-6">
        <input type="hidden" id="completeServiceId">
        
        <div class="bg-gray-50 p-5 rounded-xl">
          <h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Assign Additional Staff for Burial
          </h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center">
              <input type="checkbox" id="burial1" class="mr-2">
              <label for="burial1" class="text-gray-700">Javier Lopez (Grave Digger)</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="burial2" class="mr-2">
              <label for="burial2" class="text-gray-700">Fernando Cruz (Helper)</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="burial3" class="mr-2">
              <label for="burial3" class="text-gray-700">Tomas Santos (Helper)</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="burial4" class="mr-2">
              <label for="burial4" class="text-gray-700">Victor Reyes (Coordinator)</label>
            </div>
          </div>
        </div>
        
        <div>
          <label for="completionDate" class="block text-sm font-medium text-gray-700 mb-1">Completion Date</label>
          <input type="date" id="completionDate" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
        </div>
        
        <div>
          <label for="completionNotes" class="block text-sm font-medium text-gray-700 mb-1">Completion Notes</label>
          <textarea id="completionNotes" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent"></textarea>
        </div>
        
        <div class="bg-navy p-5 rounded-xl">
          <div class="flex items-center">
            <input type="checkbox" id="finalBalanceSettled" class="mr-2">
            <label for="finalBalanceSettled" class="text-gray-700 font-medium">Confirm all balances are settled</label>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeCompleteModal()">Cancel</button>
      <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center" onclick="finalizeServiceCompletion()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
        Complete Service
      </button>
    </div>
  </div>
</div>

<!-- Modal for Viewing Service Details -->
<div class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="viewServiceModal">
  <div class="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 class="text-xl font-bold text-white">Service Details</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeViewServiceModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    
    <!-- Modal Body -->
    <div class="p-6">
      <div class="bg-gray-50 p-5 rounded-xl">
        <div class="space-y-3">
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">ID:</span>
            <span id="serviceId" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Client Name:</span>
            <span id="serviceClientName" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Service Type:</span>
            <span id="serviceServiceType" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Date:</span>
            <span id="serviceDate" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Status:</span>
            <span id="serviceStatus" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Outstanding Balance:</span>
            <span id="serviceOutstandingBalance" class="text-gray-800 font-bold text-sidebar-accent"></span>
          </p>
        </div>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeViewServiceModal()">Close</button>
    </div>
  </div>
</div>

  <!-- Modal for Recording Payment -->
<div class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="recordPaymentModal">
  <div class="bg-white rounded-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 class="text-xl font-bold text-white">Record Payment</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeRecordPaymentModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    
    <!-- Modal Body -->
    <div class="p-6">
      <form id="recordPaymentForm" class="space-y-6">
        <!-- Payment Information Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Service ID -->
          <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="paymentServiceId">Service ID</label>
            <input class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent bg-gray-100" type="text" id="paymentServiceId" name="paymentServiceId" readonly>
          </div>
          
          <!-- Client Name -->
          <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="paymentClientName">Client Name</label>
            <input class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent bg-gray-100" type="text" id="paymentClientName" name="paymentClientName" readonly>
          </div>
          
          <!-- Outstanding Balance -->
          <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="currentBalance">Outstanding Balance</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500">₱</span>
              </div>
              <input class="w-full pl-8 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent bg-gray-100" type="text" id="currentBalance" name="currentBalance" readonly>
            </div>
          </div>
          
          <!-- Payment Amount -->
          <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="paymentAmount">Payment Amount</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500">₱</span>
              </div>
              <input class="w-full pl-8 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" type="number" id="paymentAmount" name="paymentAmount" required>
            </div>
          </div>
          
          <!-- Payment Method -->
          <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="paymentMethod">Payment Method</label>
            <select class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" id="paymentMethod" name="paymentMethod" required>
              <option value="" disabled selected>Select payment method</option>
              <option value="Cash">Cash</option>
              <option value="Credit Card">Credit Card</option>
              <option value="Debit Card">Debit Card</option>
              <option value="Check">Check</option>
              <option value="Bank Transfer">Bank Transfer</option>
            </select>
          </div>
          
          <!-- Payment Date -->
          <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="paymentDate">Payment Date</label>
            <input class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" type="date" id="paymentDate" name="paymentDate" required>
          </div>
        </div>
        
        <!-- Notes Section -->
        <div class="bg-navy p-5 rounded-xl shadow-sm border border-purple-100">
          <label class="block text-sm font-medium text-gray-700 mb-1" for="paymentNotes">Notes</label>
          <textarea class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" id="paymentNotes" name="paymentNotes" rows="3"></textarea>
        </div>
        
        <!-- Summary Section -->
        <div class="bg-navy p-6 rounded-xl shadow-sm border border-purple-100">
          <h4 class="font-bold text-lg mb-4 text-gray-800 border-b border-purple-100 pb-2 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
              <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            Payment Summary
          </h4>
          <div class="flex justify-between mb-2 text-gray-700">
            <span>Current Balance:</span>
            <span id="summary-current-balance" class="font-medium">₱0.00</span>
          </div>
          <div class="flex justify-between mb-2 text-gray-700">
            <span>Payment Amount:</span>
            <span id="summary-payment-amount" class="font-medium">₱0.00</span>
          </div>
          <div class="flex justify-between font-bold text-lg mt-4 pt-4 border-t border-dashed border-purple-200 text-sidebar-accent">
            <span>New Balance:</span>
            <span id="summary-new-balance">₱0.00</span>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeRecordPaymentModal()">Cancel</button>
      <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center" onclick="savePayment()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        Record Payment
      </button>
    </div>
  </div>
</div>



  <script src="script.js"></script>
  <script>
    // Function to open the modal and populate fields with service data
function openRecordPaymentModal(serviceId, clientName, balance) {
  // Get the modal element
  const modal = document.getElementById('recordPaymentModal');
  
  // Populate the readonly fields
  document.getElementById('paymentServiceId').value = serviceId;
  document.getElementById('paymentClientName').value = clientName;
  document.getElementById('currentBalance').value = `$${parseFloat(balance).toFixed(2)}`;
  
  // Set default payment amount to the full balance
  document.getElementById('paymentAmount').value = '';
  
  // Set today's date as default payment date
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('paymentDate').value = today;
  
  // Clear any previous input in notes
  document.getElementById('paymentNotes').value = '';
  
  // Display the modal
  modal.classList.remove('hidden');
  
  // Add event listener to close modal when clicking outside
  modal.addEventListener('click', function(event) {
    if (event.target === modal) {
      closeRecordPaymentModal();
    }
  });
}

// Function to close the modal
function closeRecordPaymentModal() {
  const modal = document.getElementById('recordPaymentModal');
  modal.classList.add('hidden');
}

// Function to handle the payment submission
function savePayment() {
  // Get form values
  const serviceId = document.getElementById('paymentServiceId').value;
  const clientName = document.getElementById('paymentClientName').value;
  const currentBalance = document.getElementById('currentBalance').value.replace('$', '');
  const paymentAmount = document.getElementById('paymentAmount').value;
  const paymentMethod = document.getElementById('paymentMethod').value;
  const paymentDate = document.getElementById('paymentDate').value;
  const notes = document.getElementById('paymentNotes').value;
  
  // Validate payment amount
  if (!paymentAmount || parseFloat(paymentAmount) <= 0) {
    alert('Please enter a valid payment amount.');
    return;
  }
  
  // Create payment data object
  const paymentData = {
    serviceId,
    clientName,
    paymentAmount: parseFloat(paymentAmount),
    paymentMethod,
    paymentDate,
    notes,
    newBalance: (parseFloat(currentBalance) - parseFloat(paymentAmount)).toFixed(2)
  };
  
  // Here you would typically send this data to your server
  console.log('Payment recorded:', paymentData);
  
  // Example of API call (uncomment and modify as needed)
  /*
  fetch('/api/payments', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(paymentData)
  })
  .then(response => response.json())
  .then(data => {
    console.log('Success:', data);
    closeRecordPaymentModal();
    // Optionally refresh the page or update UI
    // location.reload();
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to record payment. Please try again.');
  });
  */
  
  // For demo purposes, just close the modal
  alert('Payment of $' + paymentAmount + ' recorded successfully!');
  closeRecordPaymentModal();
}
    // Function to toggle body scroll when modal is open
function toggleBodyScroll(isOpen) {
  if (isOpen) {
    document.body.classList.add('modal-open');
  } else {
    document.body.classList.remove('modal-open');
  }
}

// Function to open the Edit Service Modal
function openEditServiceModal(serviceId) {
  // Fetch service details and populate the form
  document.getElementById('editServiceModal').style.display = 'flex';
  toggleBodyScroll(true);
}

// Function to close the Edit Service Modal
function closeEditServiceModal() {
  document.getElementById('editServiceModal').style.display = 'none';
  toggleBodyScroll(false);
}

// Function to save changes to a service
function saveServiceChanges() {
  const form = document.getElementById('editServiceForm');
  if (form.checkValidity()) {
    // Save changes logic here
    alert('Service updated successfully!');
    closeEditServiceModal();
  } else {
    form.reportValidity();
  }
}

// Function to open the Assign Staff Modal
function openAssignStaffModal(serviceId) {
  // Fetch current assignments if any and populate the form
  document.getElementById('assignServiceId').value = serviceId;
  
  // Reset checkboxes (in a real app, you would pre-select based on existing assignments)
  const checkboxes = document.querySelectorAll('#assignStaffForm input[type="checkbox"]');
  checkboxes.forEach(checkbox => checkbox.checked = false);
  
  document.getElementById('assignmentNotes').value = '';
  document.getElementById('assignStaffModal').style.display = 'flex';
  toggleBodyScroll(true);
}

// Function to close the Assign Staff Modal
function closeAssignStaffModal() {
  document.getElementById('assignStaffModal').style.display = 'none';
  toggleBodyScroll(false);
}

// Function to save staff assignments
function saveStaffAssignment() {
  const form = document.getElementById('assignStaffForm');
  const serviceId = document.getElementById('assignServiceId').value;
  
  // Collect selected staff
  const selectedStaff = [];
  const checkboxes = document.querySelectorAll('#assignStaffForm input[type="checkbox"]:checked');
  checkboxes.forEach(checkbox => {
    selectedStaff.push(checkbox.id);
  });
  
  // In a real application, you would save this data to your database
  console.log(`Assigned staff for service ${serviceId}:`, selectedStaff);
  alert(`Staff assigned successfully to service ${serviceId}!`);
  
  closeAssignStaffModal();
}

// Function to open the Complete Service Modal
function openCompleteModal(serviceId) {
  // Set service ID and default values
  document.getElementById('completeServiceId').value = serviceId;
  document.getElementById('completionDate').valueAsDate = new Date();
  document.getElementById('completionNotes').value = '';
  document.getElementById('finalBalanceSettled').checked = false;
  
  // Reset burial staff checkboxes
  const checkboxes = document.querySelectorAll('#completeServiceForm input[type="checkbox"]');
  checkboxes.forEach(checkbox => {
    if (checkbox.id !== 'finalBalanceSettled') {
      checkbox.checked = false;
    }
  });
  
  document.getElementById('completeServiceModal').style.display = 'flex';
  toggleBodyScroll(true);
}

// Function to close the Complete Service Modal
function closeCompleteModal() {
  document.getElementById('completeServiceModal').style.display = 'none';
  toggleBodyScroll(false);
}

// Function to finalize service completion
function finalizeServiceCompletion() {
  const form = document.getElementById('completeServiceForm');
  const serviceId = document.getElementById('completeServiceId').value;
  
  if (!document.getElementById('completionDate').value) {
    alert('Please specify a completion date.');
    return;
  }
  
  if (!document.getElementById('finalBalanceSettled').checked) {
    if (!confirm('The balance settlement has not been confirmed. Are you sure you want to mark this service as complete?')) {
      return;
    }
  }
  
  // Collect selected burial staff
  const selectedStaff = [];
  const checkboxes = document.querySelectorAll('#completeServiceForm input[type="checkbox"]:checked');
  checkboxes.forEach(checkbox => {
    if (checkbox.id !== 'finalBalanceSettled') {
      selectedStaff.push(checkbox.id);
    }
  });
  
  // In a real application, you would save this data to your database
  console.log(`Service ${serviceId} completed with burial staff:`, selectedStaff);
  alert(`Service ${serviceId} has been marked as complete!`);
  
  closeCompleteModal();
  
  // Update the status in the table (in a real app, you might refresh data from server)
  const tableRows = document.querySelectorAll('tbody tr');
  tableRows.forEach(row => {
    const idCell = row.querySelector('td:first-child');
    if (idCell && idCell.textContent.includes(serviceId)) {
      const statusCell = row.querySelector('td:nth-child(5) span');
      statusCell.className = 'px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-500';
      statusCell.textContent = 'Completed';
    }
  });
}

// Function to view service details (kept from original)
function viewServiceDetails(serviceId) {
  document.getElementById('serviceId').textContent = serviceId;
  document.getElementById('serviceClientName').textContent = 'John Doe';
  document.getElementById('serviceServiceType').textContent = 'Memorial Service';
  document.getElementById('serviceDate').textContent = '2023-10-15';
  document.getElementById('serviceStatus').textContent = 'Completed';
  document.getElementById('serviceOutstandingBalance').textContent = '₱0';

  document.getElementById('viewServiceModal').style.display = 'flex';
  toggleBodyScroll(true);
}

// Function to close the View Service Modal (kept from original)
function closeViewServiceModal() {
  document.getElementById('viewServiceModal').style.display = 'none';
  toggleBodyScroll(false);
}

// Function to sort table columns (kept from original)
function sortTable(columnIndex) {
  const table = event.target.closest('table');
  const tbody = table.querySelector('tbody');
  const rows = Array.from(tbody.querySelectorAll('tr'));
  const isAscending = table.getAttribute('data-sort-asc') === 'true';

  rows.sort((a, b) => {
    const aValue = a.querySelectorAll('td')[columnIndex].textContent.trim();
    const bValue = b.querySelectorAll('td')[columnIndex].textContent.trim();
    return isAscending ? aValue.localeCompare(bValue) : bValue.localeCompare(aValue);
  });

  // Clear existing rows
  while (tbody.firstChild) {
    tbody.removeChild(tbody.firstChild);
  }

  // Append sorted rows
  rows.forEach(row => tbody.appendChild(row));

  // Toggle sort order
  table.setAttribute('data-sort-asc', !isAscending);
}

// Initialize search functionality (kept from original)
document.addEventListener('DOMContentLoaded', function() {
  setupSearch();
});

// Function to filter table based on search input (kept from original)
function setupSearch() {
  const searchOngoing = document.getElementById('searchOngoing');
  if (searchOngoing) {
    searchOngoing.addEventListener('keyup', function() {
      const searchTerm = this.value.toLowerCase();
      const table = this.closest('div').nextElementSibling.querySelector('table');
      filterTable(table, searchTerm);
    });
  }
  
  const searchFullyPaid = document.getElementById('searchFullyPaid');
  if (searchFullyPaid) {
    searchFullyPaid.addEventListener('keyup', function() {
      const searchTerm = this.value.toLowerCase();
      const table = this.closest('div').nextElementSibling.querySelector('table');
      filterTable(table, searchTerm);
    });
  }
  
  const searchOutstanding = document.getElementById('searchOutstanding');
  if (searchOutstanding) {
    searchOutstanding.addEventListener('keyup', function() {
      const searchTerm = this.value.toLowerCase();
      const table = this.closest('div').nextElementSibling.querySelector('table');
      filterTable(table, searchTerm);
    });
  }
}

// Function to filter table rows based on search term (kept from original)
function filterTable(table, searchTerm) {
  const rows = table.querySelectorAll('tbody tr');
  rows.forEach(row => {
    const cells = row.querySelectorAll('td');
    let match = false;
    cells.forEach(cell => {
      if (cell.textContent.toLowerCase().includes(searchTerm)) {
        match = true;
      }
    });
    if (match) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });
}

// Function to open the Add Service Modal (kept from original)
function openAddServiceModal() {
  // Clear the form and open the modal
  document.getElementById('editServiceForm').reset();
  document.getElementById('editServiceModal').style.display = 'flex';
  toggleBodyScroll(true);
}
</script>
<script src="tailwind.js"></script>
<Script src="sidebar.js"></Script>
</body> 
</html>