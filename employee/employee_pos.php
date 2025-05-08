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



// Database connection
include '../db_connect.php';

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

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user information from database instead of relying on session data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

// Check if user exists
if ($user_result->num_rows === 0) {
    // User not found in database
    session_destroy();
    header("Location: ../Landing_Page/login.php?error=invalid_user");
    exit();
}

// Fetch user data
$user_data = $user_result->fetch_assoc();
$user_type = $user_data['user_type'];
$user_branch_id = $user_data['branch_loc'] ?? null;
$user_name = $user_data['first_name'] . ' ' . $user_data['last_name'];

// Check for employee user type (user_type = 2)
if ($user_type != 2) {
    // Redirect to appropriate page based on user type
    switch ($user_type) {
        case 1:
            header("Location: ../admin/index.php");
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

// Check if user has a branch location assigned
if (!$user_branch_id) {
    // Handle case where user doesn't have a branch assigned
    die("Error: User branch location not assigned.");
}

// Function to get all active services for the user's branch
function getServices($conn, $user_branch_id) {
  $sql = "SELECT s.service_id, s.service_name, s.description, s.service_categoryID, s.branch_id, 
                s.inclusions, s.flower_design, s.capital_price, s.selling_price, s.image_url, 
                b.branch_name, 
                c.service_category_name
         FROM services_tb s
         INNER JOIN branch_tb b ON s.branch_id = b.branch_id
         INNER JOIN service_category c ON s.service_categoryID = c.service_categoryID
         WHERE s.status = 'Active' AND s.branch_id = ?
         ORDER BY s.service_categoryID, s.service_name";
  
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $user_branch_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  $services = [];
  
  if ($result->num_rows > 0) {
      while($row = $result->fetch_assoc()) {
          $services[] = $row;
      }
  }
  
  return $services;
}

function getBranches($conn) {
  $sql = "SELECT branch_id, branch_name FROM branch_tb";
  $result = $conn->query($sql);
  $branches = [];
  
  if ($result->num_rows > 0) {
      while($row = $result->fetch_assoc()) {
          $branches[] = $row;
      }
  }
  
  return $branches;
}

// Function to get all service categories
function getServiceCategories($conn) {
  $sql = "SELECT service_categoryID, service_category_name FROM service_category ";
  $result = $conn->query($sql);
  $categories = [];
  
  if ($result->num_rows > 0) {
      while($row = $result->fetch_assoc()) {
          $categories[] = $row;
      }
  }
  
  return $categories;
}

$branches = getBranches($conn);
$categories = getServiceCategories($conn);
$allServices = getServices($conn, $user_branch_id);

// Convert to JSON for JavaScript
$branchesJson = json_encode($branches);
$categoriesJson = json_encode($categories);
$servicesJson = json_encode($allServices);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Point-Of-Sale</title>
  <?php include 'faviconLogo.php'; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
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
      <h1 class="text-2xl font-bold text-sidebar-text">Point-Of-Sale (POS)</h1>
      <p class="text-sm text-gray-600">Services available at your branch: 
        <span class="font-semibold text-sidebar-accent">
          <?php 
          // Get branch name from database
          $branch_query = "SELECT branch_name FROM branch_tb WHERE branch_id = ?";
          $stmt = $conn->prepare($branch_query);
          $stmt->bind_param("i", $user_branch_id);
          $stmt->execute();
          $branch_result = $stmt->get_result();
          $branch_name = $branch_result->fetch_assoc()['branch_name'];
          echo htmlspecialchars($branch_name);
          ?>
        </span>
      </p>
    </div>
    <div class="flex space-x-3">
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-bell"></i>
      </button>
    </div>
  </div>

  <!-- Services Section (Now visible by default) -->
  <div id="services-section" class="mb-8">
    <div class="flex items-center justify-between mb-6 bg-white p-4 rounded-lg shadow-sm border border-sidebar-border">
      <div class="flex items-center">
      <h2 class="text-gray-700 text-lg font-semibold text-sidebar-accent">
        Available Services
      </h2>
      </div>
      <div class="hidden md:flex">
        <div class="hidden md:flex items-center gap-4">
          
          <!-- Price Filter Dropdown -->
          <div class="relative">
            <select id="price-filter" class="appearance-none pl-3 pr-8 py-2 border border-sidebar-border rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent cursor-pointer">
              <option value="">All Prices</option>
              <option value="1-100000">₱1 - ₱100,000</option>
              <option value="100001-300000">₱100,001 - ₱300,000</option>
              <option value="300001-500000">₱300,001 - ₱500,000</option>
              <option value="500001-700000">₱500,001 - ₱700,000</option>
              <option value="700001+">₱700,001+</option>
            </select>
            <div class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
              <i class="fas fa-chevron-down text-gray-400"></i>
            </div>
          </div>
          
          <!-- Price Sort Dropdown -->
          <div class="relative">
            <select id="price-sort" class="appearance-none pl-3 pr-8 py-2 border border-sidebar-border rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent cursor-pointer">
              <option value="">Sort by Price</option>
              <option value="low-high">Low to High</option>
              <option value="high-low">High to Low</option>
            </select>
            <div class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
              <i class="fas fa-chevron-down text-gray-400"></i>
            </div>
          </div>
          <!-- Search Bar -->
          <div class="relative">
            <input type="text" id="service-search" placeholder="Search services..." 
                  class="pl-10 pr-4 py-2 border border-sidebar-border rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            <div id="search-error" class="hidden text-red-500 text-xs mt-1">Please avoid leading or multiple spaces</div>
          </div>
        </div>
      </div>
    </div>
    
    <div id="services-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <!-- Services will be dynamically added here based on branch and category selection -->
    </div>
  </div>

</div> <!-- MAIN CONTENT-->

<div id="package-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-3xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closePackageModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center" id="modal-title">
        Package Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5" id="modal-content">
      <!-- Content will be dynamically added here -->
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closePackageModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="addToCart()">
        Buy Now
      </button>
    </div>
  </div>
</div>

<!-- Checkout Modal -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto" id="checkoutModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-5xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeCheckoutModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200 ">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center" id="modal-package-title">
        Complete Your Order
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="checkoutForm" class="space-y-3 sm:space-y-4">
        <input type="hidden" id="service-id" name="service_id" value="">
        <input type="hidden" id="service-price" name="service_price">
        <input type="hidden" id="branch-id" name="branch_id" value="">
        <input type="hidden" id="sold_by" name="sold_by" value="<?php echo htmlspecialchars($_SESSION['user_id']); ?>">
        <input type="hidden" id="deceasedAddress" name="deceased_address" value="">

        <!-- Client Information Section -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Client Information
          </h4>
          <div class="space-y-3 sm:space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 sm:gap-4">
              <div>
                <label for="clientFirstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  First Name
                </label>
                <input type="text" id="clientFirstName" name="clientFirstName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="clientMiddleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Middle Name <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="text" id="clientMiddleName" name="clientMiddleName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="clientLastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Last Name
                </label>
                <input type="text" id="clientLastName" name="clientLastName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="clientSuffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Suffix <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <select id="clientSuffix" name="clientSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">None</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="I">I</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                    <option value="V">V</option>
                                </select>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-4">
              <div>
                <label for="clientPhone" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Phone Number <span class="text-red-500">*</span>
                </label>
                <input type="tel" id="clientPhone" name="clientPhone" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="clientEmail" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Email Address <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="email" id="clientEmail" name="clientEmail" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
          </div>
        </div>

        <!-- Deceased Information Section -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Deceased Information
          </h4>
          <div class="space-y-3 sm:space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 sm:gap-4">
              <div>
                <label for="deceasedFirstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  First Name
                </label>
                <input type="text" id="deceasedFirstName" name="deceasedFirstName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="deceasedMiddleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Middle Name <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="text" id="deceasedMiddleName" name="deceasedMiddleName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="deceasedLastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Last Name
                </label>
                <input type="text" id="deceasedLastName" name="deceasedLastName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="deceasedSuffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Suffix <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <select id="traditionalDeceasedSuffix" name="traditionalDeceasedSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">None</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="I">I</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                    <option value="V">V</option>
                                </select>
              </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 sm:gap-4">
              <div>
                <label for="dateOfBirth" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Date of Birth <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="date" id="dateOfBirth" name="dateOfBirth" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="dateOfDeath" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Date of Death <span class="text-red-500">*</span>
                </label>
                <input type="date" id="dateOfDeath" name="dateOfDeath" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="dateOfBurial" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Date of Burial/Cremation <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="date" id="dateOfBurial" name="dateOfBurial" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
            <!-- New Address Dropdown Hierarchy -->
    <div class="space-y-3">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-2 sm:gap-4">
        <div>
          <label for="deceasedRegion" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Region <span class="text-red-500">*</span>
          </label>
          <select id="deceasedRegion" name="deceasedRegion" required 
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select Region</option>
            <?php foreach ($regions as $region): ?>
              <option value="<?php echo $region['region_code']; ?>"><?php echo $region['region_name']; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="deceasedProvince" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Province <span class="text-red-500">*</span>
          </label>
          <select id="deceasedProvince" name="deceasedProvince" required disabled
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select Province</option>
          </select>
        </div>
        <div>
          <label for="deceasedCity" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            City/Municipality <span class="text-red-500">*</span>
          </label>
          <select id="deceasedCity" name="deceasedCity" required disabled
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select City/Municipality</option>
          </select>
        </div>
        <div>
          <label for="deceasedBarangay" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Barangay <span class="text-red-500">*</span>
          </label>
          <select id="deceasedBarangay" name="deceasedBarangay" required disabled
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select Barangay</option>
          </select>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-2 sm:gap-4">
        <div class="md:col-span-2">
          <label for="deceasedStreet" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Street Address <span class="text-red-500">*</span>
          </label>
          <input type="text" id="deceasedStreet" name="deceasedStreet" required 
                 class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
        </div>
        <div>
          <label for="deceasedZip" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            ZIP Code <span class="text-red-500">*</span>
          </label>
          <input type="text" id="deceasedZip" name="deceasedZip" required 
                 class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
        </div>
      </div>
    </div>
    
    <div>
      <label for="deathCertificate" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
        Death Certificate <span class="text-xs text-gray-500">(If available)</span>
      </label>
      <div class="relative">
        <div class="flex items-center border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-1 focus-within:ring-sidebar-accent focus-within:border-sidebar-accent transition-all duration-200">
          <input type="file" id="deathCertificate" name="deathCertificate" accept="image/*,.pdf" class="w-full focus:outline-none">
        </div>
        <!-- Image preview container -->
        <div id="deathCertificatePreview" class="mt-2 hidden">
          <div class="relative inline-block">
            <img id="previewImage" src="#" alt="Death Certificate Preview" class="max-h-40 rounded-lg border border-gray-200">
            <button type="button" id="removeImageBtn" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition-colors">
              <i class="fas fa-times text-xs"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
  
        <!-- Payment Information -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Payment Information
          </h4>
          <div class="space-y-3 sm:space-y-4">
            <div>
              <label for="paymentMethod" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Method of Payment
              </label>
              <select id="paymentMethod" name="paymentMethod" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                <option value="" disabled selected>Select payment method</option>
                <option value="Cash">Cash</option>
                <option value="G-Cash">G-Cash</option>
                <option value="Bank Transfer">Bank Transfer</option>
              </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-4">
              <div>
                <label for="totalPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Total Price 
                  <span class="text-xs text-gray-500 ml-1">(Minimum: <span id="min-price">₱0.00</span>)</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500">₱</span>
                  </div>
                  <input type="number" id="totalPrice" name="totalPrice" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                </div>
              </div>
              <div>
                <label for="amountPaid" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Amount Paid
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500">₱</span>
                  </div>
                  <input type="number" id="amountPaid" name="amountPaid" required class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cremation Checklist Section -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Additional Services
          </h4>
          <div class="space-y-3">
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="checkbox" name="withCremation" id="withCremation" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              With Cremation
            </label>
            <p class="text-sm text-gray-500 ml-8">Check this box if the service includes cremation</p>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-between items-center border-t border-gray-200 sticky bottom-0 bg-white">
      <div class="text-gray-600 mb-3 sm:mb-0 w-full sm:w-auto text-center sm:text-left">
        <p class="font-medium">Order Total: <span class="text-xl font-bold text-sidebar-accent" id="footer-total-price">₱0.00</span></p>
      </div>
      <div class="flex flex-col sm:flex-row gap-2 sm:gap-4 w-full sm:w-auto">
        <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeCheckoutModal()">
          Cancel
        </button>
        <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="confirmCheckout()">
          Confirm Order
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Service Type Selection Modal -->
<div id="serviceTypeModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeServiceTypeModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Select Service Type
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <button id="traditionalServiceBtn" class="bg-[#F9F6F0] hover:bg-yellow-100 border-2 border-[#CA8A04] text-[#2D2B30] px-4 py-6 rounded-lg shadow-md transition-all duration-300 flex flex-col items-center">
          <i class="fas fa-dove text-3xl text-[#CA8A04] mb-2"></i>
          <span class="font-medium text-lg">Traditional</span>
          <span class="text-sm text-gray-600 mt-2 text-center">For immediate funeral needs</span>
        </button>
        
        <button id="lifeplanServiceBtn" class="bg-[#F9F6F0] hover:bg-yellow-100 border-2 border-[#CA8A04] text-[#2D2B30] px-4 py-6 rounded-lg shadow-md transition-all duration-300 flex flex-col items-center">
          <i class="fas fa-seedling text-3xl text-[#CA8A04] mb-2"></i>
          <span class="font-medium text-lg">Lifeplan</span>
          <span class="text-sm text-gray-600 mt-2 text-center">lifeplan funeral planning</span>
        </button>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex justify-end border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeServiceTypeModal()">
        Cancel
      </button>
    </div>
  </div>
</div>

<!-- Lifeplan Checkout Modal -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto" id="lifeplanCheckoutModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-5xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeLifeplanCheckoutModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Complete Your Lifeplan Order
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="lifeplanCheckoutForm" class="space-y-3 sm:space-y-4" onsubmit="event.preventDefault(); confirmLifeplanCheckout();">
        <input type="hidden" id="lp-service-id" name="service_id" value="">
        <input type="hidden" id="lp-service-price" name="service_price">
        <input type="hidden" id="lp-branch-id" name="branch_id" value="">

        <!-- Client Information Section -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Client Information
          </h4>
          <div class="space-y-3 sm:space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 sm:gap-4">
              <div>
                <label for="lp-clientFirstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  First Name
                </label>
                <input type="text" id="lp-clientFirstName" name="lp-FirstName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="lp-clientMiddleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Middle Name <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="text" id="lp-clientMiddleName" name="lp-MiddleName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="lp-clientLastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Last Name
                </label>
                <input type="text" id="lp-clientLastName" name="lp-LastName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="lp-clientSuffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Suffix <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <select id="lp-clientSuffix" name="lp-Suffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">None</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="I">I</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                    <option value="V">V</option>
                                </select>
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-4">
              <div>
                <label for="lp-clientPhone" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Phone Number
                </label>
                <input type="tel" id="lp-clientPhone" name="lp-Phone" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" minlength="11" maxlength="11">
              </div>
              <div>
                <label for="lp-clientEmail" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Email Address <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="email" id="lp-clientEmail" name="lp-Email" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
          </div>
        </div>

        <!-- Beneficiary Information Section -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Beneficiary Information
          </h4>
          <div class="space-y-3 sm:space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 sm:gap-4">
              <div>
                <label for="beneficiaryFirstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  First Name
                </label>
                <input type="text" id="beneficiaryFirstName" name="beneficiaryFirstName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="beneficiaryMiddleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Middle Name <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="text" id="beneficiaryMiddleName" name="beneficiaryMiddleName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="beneficiaryLastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Last Name
                </label>
                <input type="text" id="beneficiaryLastName" name="beneficiaryLastName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="beneficiarySuffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Suffix <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <select id="beneficiarySuffix" name="beneficiarySuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">None</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="I">I</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                    <option value="V">V</option>
                                </select>
              </div>
            </div>
            
            <div>
              <label for="beneficiaryDateOfBirth" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Date of Birth
              </label>
              <input type="date" id="beneficiaryDateOfBirth" name="beneficiaryDateOfBirth" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
            <!-- New Address Dropdown Hierarchy -->
    <div class="space-y-3">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-2 sm:gap-4">
        <div>
          <label for="beneficiaryRegion" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Region <span class="text-red-500">*</span>
          </label>
          <select id="beneficiaryRegion" name="beneficiaryRegion" required 
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select Region</option>
            <?php foreach ($regions as $region): ?>
              <option value="<?php echo $region['region_code']; ?>"><?php echo $region['region_name']; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="beneficiaryProvince" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Province <span class="text-red-500">*</span>
          </label>
          <select id="beneficiaryProvince" name="beneficiaryProvince" required disabled
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select Province</option>
          </select>
        </div>
        <div>
          <label for="beneficiaryCity" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            City/Municipality <span class="text-red-500">*</span>
          </label>
          <select id="beneficiaryCity" name="beneficiaryCity" required disabled
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select City/Municipality</option>
          </select>
        </div>
        <div>
          <label for="beneficiaryBarangay" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Barangay <span class="text-red-500">*</span>
          </label>
          <select id="beneficiaryBarangay" name="beneficiaryBarangay" required disabled
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select Barangay</option>
          </select>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-2 sm:gap-4">
        <div class="md:col-span-2">
          <label for="beneficiaryStreet" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Street Address <span class="text-red-500">*</span>
          </label>
          <input type="text" id="beneficiaryStreet" name="beneficiaryAddress" required 
                 class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
        </div>
        <div>
          <label for="beneficiaryZip" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            ZIP Code <span class="text-red-500">*</span>
          </label>
          <input type="text" id="beneficiaryZip" name="beneficiaryZip" required 
                 class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
        </div>
      </div>
    </div>
    
            <div>
              <label for="beneficiaryRelationship" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Relationship to Client
              </label>
              <input type="text" id="beneficiaryRelationship" name="beneficiaryRelationship" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
          </div>
        </div>
  
        <!-- Payment Information -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Payment Information
          </h4>
          <div class="space-y-3 sm:space-y-4">
            <div>
              <label for="lp-paymentMethod" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Method of Payment
              </label>
              <select id="lp-paymentMethod" name="paymentMethod" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                <option value="" disabled selected>Select payment method</option>
                <option value="Cash">Cash</option>
                <option value="G-Cash">G-Cash</option>
                <option value="Bank Transfer">Bank Transfer</option>
              </select>
            </div>

            <div>
              <label for="lp-paymentTerm" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Payment Term
              </label>
              <select id="lp-paymentTerm" name="paymentTerm" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                <option value="1">1 Year (Full Payment)</option>
                <option value="2">2 Years</option>
                <option value="3">3 Years</option>
                <option value="5">5 Years</option>
              </select>
              <div id="lp-monthlyPayment" class="mt-2 text-sm text-gray-600 hidden">
                Monthly Payment: <span class="font-semibold text-sidebar-accent">₱0.00</span>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-4">
              <div>
                <label for="lp-totalPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Total Price 
                  <span class="text-xs text-gray-500 ml-1">(Minimum: <span id="lp-min-price">₱0.00</span>)</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500">₱</span>
                  </div>
                  <input type="number" id="lp-totalPrice" name="totalPrice" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                </div>
              </div>
              <div>
                <label for="lp-amountPaid" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Amount Paid
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500">₱</span>
                  </div>
                  <input type="number" id="lp-amountPaid" name="amountPaid" required class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Additional Services (Cremation) -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Additional Services
          </h4>
          <div class="space-y-3">
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="checkbox" name="withCremation" id="lp-withCremation" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              With Cremation
            </label>
            <p class="text-sm text-gray-500 ml-8">Check this box if the service includes cremation</p>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row justify-between items-center gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <div class="text-gray-600 w-full sm:w-auto">
        <p class="font-medium">Order Total: <span class="text-xl font-bold text-sidebar-accent" id="lp-footer-total-price">₱0.00</span></p>
      </div>
      <div class="flex flex-col sm:flex-row w-full sm:w-auto gap-2 sm:gap-4">
        <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeLifeplanCheckoutModal()">
          Cancel
        </button>
        <button id="lp-confirm-btn" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="confirmLifeplanCheckout()">
          Confirm Order
        </button>
      </div>
    </div>
  </div>
</div>

    <!-- Order Confirmation Modal -->
<div id="confirmation-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 z-10 transform transition-all duration-300">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeConfirmationModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        Order Confirmed
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5 text-center">
      <div class="w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
        <i class="fas fa-check-circle text-3xl text-green-600"></i>
      </div>
      <h4 class="text-lg font-semibold mb-2 text-sidebar-text">Order Confirmed!</h4>
      <p class="text-gray-600 mb-4">Your order has been successfully placed.</p>
      <p class="text-gray-600 mb-2">Order ID: <span id="order-id" class="font-semibold">ORD-12345</span></p>
      <p class="text-gray-600">A confirmation has been sent to your records.</p>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-6 py-4 flex justify-center border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="startNewOrder()">
        Start New Order
      </button>
    </div>
  </div>
</div>


 


<script>
  // Initialize JSON data from PHP
  const branches = <?php echo $branchesJson; ?>;
  const categories = <?php echo $categoriesJson; ?>;
  const services = <?php echo $servicesJson; ?>;
  const userBranchId = <?php echo $user_branch_id; ?>;
  
  // DOM elements
  const servicesSection = document.getElementById('services-section');
  const servicesContainer = document.getElementById('services-container');
  const priceFilter = document.getElementById('price-filter');
  const priceSort = document.getElementById('price-sort');
  const serviceSearch = document.getElementById('service-search');
  
  // Variables to track selections
  let currentFilteredServices = [];
  
  // On page load, show all services for the user's branch
  document.addEventListener('DOMContentLoaded', function() {
    filterAndDisplayServices();

      document.getElementById('traditionalServiceBtn').addEventListener('click', function() {
      document.getElementById('serviceTypeModal').classList.add('hidden');
      openTraditionalCheckout();
    });
    
    document.getElementById('lifeplanServiceBtn').addEventListener('click', function() {
      document.getElementById('serviceTypeModal').classList.add('hidden');
      openLifeplanCheckout();
    });

  });
  
  // Filter and display services based on current selections
  function filterAndDisplayServices() {
      // Start with branch filter (only show services from user's branch)
      let filteredServices = services.filter(service => service.branch_id == userBranchId);
      
      // Remove the category filter section completely
      
      // Apply price range filter
      if (priceFilter.value) {
        const range = priceFilter.value.split('-');
        if (range.length === 2) {
          const min = parseInt(range[0]);
          const max = parseInt(range[1]);
          filteredServices = filteredServices.filter(service => {
            const price = parseInt(service.selling_price);
            return price >= min && price <= max;
          });
        } else if (priceFilter.value.endsWith('+')) {
          const min = parseInt(priceFilter.value);
          filteredServices = filteredServices.filter(service => 
            parseInt(service.selling_price) >= min
          );
        }
      }
      
      // Apply search filter
      if (serviceSearch.value.trim()) {
        const searchTerm = serviceSearch.value.trim().toLowerCase();
        filteredServices = filteredServices.filter(service => 
          service.service_name.toLowerCase().includes(searchTerm) ||
          service.description.toLowerCase().includes(searchTerm)
        );
      }
      
      // Apply price sorting
      if (priceSort.value) {
        filteredServices.sort((a, b) => {
          const priceA = parseInt(a.selling_price);
          const priceB = parseInt(b.selling_price);
          return priceSort.value === 'low-high' ? priceA - priceB : priceB - priceA;
        });
      }
      
      // Store current filtered services
      currentFilteredServices = filteredServices;
      
      // Display services
      displayServices(filteredServices);
  }
  
  // Display services in the container
  // Display services in the container
  function displayServices(services) {
    servicesContainer.innerHTML = '';
    
    if (services.length === 0) {
      servicesContainer.innerHTML = `
        <div class="col-span-full p-8 bg-white rounded-lg border border-sidebar-border text-center">
          <i class="fas fa-search text-4xl text-gray-300 mb-3"></i>
          <h3 class="text-lg font-semibold text-gray-600">No services found</h3>
          <p class="text-gray-500">Try adjusting your filters or search criteria.</p>
        </div>
      `;
      return;
    }
    
    services.forEach(service => {
      const serviceCard = document.createElement('div');
      serviceCard.className = 'bg-white rounded-lg shadow-md overflow-hidden border border-sidebar-border hover:shadow-lg transition-all duration-300';
      
      const formattedPrice = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
      }).format(service.selling_price);
      
      serviceCard.innerHTML = `
        <div class="h-40 bg-gray-200 relative overflow-hidden">
          <img src="${service.image_url ? '/admin/servicesManagement/' + service.image_url : '/api/placeholder/400/250'}" 
             alt="${service.service_name}" 
             class="w-full h-full object-cover">
          <div class="absolute top-2 right-2 bg-yellow-500 text-white px-2 py-1 rounded-md text-sm font-semibold">
            ${formattedPrice}
          </div>
        </div>
        <div class="p-4">
          <h3 class="font-semibold text-sidebar-text mb-2">${service.service_name}</h3>
          <p class="text-sm text-gray-600 mb-3 line-clamp-2">${service.description}</p>
          <div class="flex justify-between items-center">
            <span class="text-xs bg-gray-100 px-2 py-1 rounded-full">${service.service_category_name}</span>
            <button onclick="showServiceDetails(${service.service_id})" class="px-3 py-1 bg-sidebar-accent text-white rounded-md text-sm hover:bg-yellow-600 transition-all duration-300">
              Select
            </button>
          </div>
        </div>
      `;
      
      servicesContainer.appendChild(serviceCard);
    });
  }
  
  // Select a service (implement this function based on your requirements)
  // Function to show service details in modal
  async function showServiceDetails(service) {
  try {
    // Get the service ID whether service is an object or just an ID
    const serviceId = service.service_id || service;
    
    const response = await fetch(`posFunctions/get_services.php?service_id=${serviceId}`);
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

    const serviceData = await response.json();
    
    // Check for error in response (your PHP returns either an object or {error: ...})
    if (serviceData.error) {
      throw new Error(serviceData.error);
    }

    console.log("Showing service details:", serviceData);
    selectedService = serviceData;
    
    // Rest of your code using serviceData instead of service...
    document.getElementById('modal-title').textContent = serviceData.service_name;
    document.getElementById('service-price').value = serviceData.selling_price;
    
    // Format inclusions as a list if it contains commas
    let inclusionsDisplay = serviceData.inclusions;
    if (serviceData.inclusions && serviceData.inclusions.includes(',')) {
      const inclusionsList = serviceData.inclusions.split(',').map(item => `<li class="mb-1">- ${item.trim()}</li>`).join('');
      inclusionsDisplay = `<ul class="list-none mt-2">${inclusionsList}</ul>`;
    }

    // Use a default image if none provided or if the URL is empty
    const imageUrl = serviceData.image_url && serviceData.image_url.trim() !== '' ? 
      '/admin/servicesManagement/' + serviceData.image_url : '';

    document.getElementById('modal-content').innerHTML = `
      <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <div class="${imageUrl ? 'h-64 bg-center bg-cover bg-no-repeat rounded-lg' : 'h-64 bg-gray-100 flex items-center justify-center rounded-lg'}" 
               ${imageUrl ? `style="background-image: url('${imageUrl}');"` : ''}>
            ${!imageUrl ? '<i class="fas fa-image text-5xl text-gray-300"></i>' : ''}
          </div>
        <div>
          <div class="text-lg font-bold mb-2.5 text-sidebar-text">${serviceData.service_name}</div>
          <div class="flex items-center mb-4">
            <span class="text-gray-500 text-sm mr-3"><i class="fas fa-map-marker-alt mr-1"></i> ${serviceData.branch_name}</span>
            <span class="text-gray-500 text-sm"><i class="fas fa-tag mr-1"></i> ${serviceData.service_category_name}</span>
          </div>
          <div class="text-lg font-bold text-sidebar-accent mb-4">₱${parseFloat(serviceData.selling_price).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>

          <!-- Add the description here -->
          ${serviceData.description ? `
            <div class="text-gray-700 text-sm mb-3">
              <strong>Description:</strong>
              <p class="mt-1">${serviceData.description}</p>
            </div>
          ` : ''}

          <div class="text-gray-700 text-sm mb-3">
            <strong>Flower Replacements:</strong>
            ${serviceData.flower_design}
          </div>
          <div class="text-gray-700 text-sm mb-3">
            <strong>Inclusions:</strong>
            ${inclusionsDisplay}
          </div>
        </div>
      </div>
    `;
    document.getElementById('package-modal').classList.remove('hidden');
  } catch (error) {
    console.error('Error fetching service details:', error);
    alert('Failed to load service details: ' + error.message);
  }
}

// Function to close the package modal
function closePackageModal() {
  document.getElementById('package-modal').classList.add('hidden');
  selectedService = null;
}

function closeServiceTypeModal() {
  const modal = document.getElementById('serviceTypeModal');
  if (modal) {
    modal.classList.add('hidden');
  }
}

// Function to handle adding to cart and immediately proceed to checkout
function addToCart() {
  console.log("Selected service in addToCart:", selectedService);
  if (selectedService) {
    // Store the selected service in the service type modal's data attributes
    const serviceTypeModal = document.getElementById('serviceTypeModal');
    serviceTypeModal.dataset.serviceId = selectedService.service_id;
    serviceTypeModal.dataset.servicePrice = selectedService.selling_price;
    serviceTypeModal.dataset.branchId = selectedService.branch_id;
    
    // Close package modal and show service type selection
    closePackageModal();
    document.getElementById('serviceTypeModal').classList.remove('hidden');
  }
}

// Function to open traditional checkout
function openTraditionalCheckout() {
  console.log("Opening traditional checkout with service:", document.getElementById('serviceTypeModal').dataset);
  const serviceTypeModal = document.getElementById('serviceTypeModal');
  const serviceId = serviceTypeModal.dataset.serviceId;
  const servicePrice = serviceTypeModal.dataset.servicePrice;
  const branchId = serviceTypeModal.dataset.branchId;

  // Set the service details in the form
  document.getElementById('service-id').value = serviceId;
  document.getElementById('service-price').value = servicePrice;
  document.getElementById('branch-id').value = branchId;
  
  // Update the total price in the checkout form
  document.getElementById('totalPrice').value = servicePrice;
  document.getElementById('footer-total-price').textContent = 
    `₱${parseFloat(servicePrice).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
  
  // Update minimum price display
  const minimumPrice = parseFloat(servicePrice) * 0.5;
  document.getElementById('min-price').textContent = `₱${minimumPrice.toFixed(2)}`;
  
  // Open checkout modal
  document.getElementById('checkoutModal').classList.remove('hidden');
}

function confirmCheckout() {
  // Get the form element
  const form = document.getElementById('checkoutForm');
  
  // Create a FormData object from the form (this automatically includes all form fields)
  const formData = new FormData(form);
  
  // Add session data that isn't in the form
  // Use a hidden input field in the form instead of direct PHP insertion
  const soldBy = document.getElementById('sold_by').value;
  formData.append('sold_by', soldBy);

  const address = document.getElementById('deceasedAdress').value;
  console.log("newAddress:", address);
  
  // Log the form data to the console for debugging
  console.log('Checkout Form Data:');
  for (let [key, value] of formData.entries()) {
    // Don't log file content, just log that a file was included
    if (value instanceof File) {
      console.log(key + ': File included - ' + value.name);
    } else {
      console.log(key + ': ' + value);
    }
  }
  
  // Send the data to the server (make sure the path is correct relative to the JS file location)
  fetch('posFunctions/traditional_checkout.php', {
    method: 'POST',
    body: formData
    // Don't set Content-Type header when using FormData - it will be set automatically with boundary
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok: ' + response.status);
    }
    return response.json();
  })
  .then(data => {
    console.log('Server response:', data);
    if (data.success) {
      // Show success message and order ID
      document.getElementById('order-id').textContent = data.order_id;
      showConfirmationModal();
    } else {
      // Show error message
      alert('Error: ' + (data.message || 'Failed to process order'));
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('An error occurred while processing your order. Please try again.');
  });
}

function showConfirmationModal() {
  // Generate a random order ID for demonstration
  const orderId = 'ORD-' + Math.floor(Math.random() * 100000);
  document.getElementById('order-id').textContent = orderId;
  
  // Show the confirmation modal
  document.getElementById('confirmation-modal').classList.remove('hidden');
  
  // Close the checkout modal
  closeCheckoutModal();
}

function startNewOrder() {
  // Close the confirmation modal
  document.getElementById('confirmation-modal').classList.add('hidden');
  
  // Reset the form
  document.getElementById('checkoutForm').reset();
  
  // You might want to reset other states here
}

// Similarly for the lifeplan checkout
function confirmLifeplanCheckout() {
  // Get the form element
  const form = document.getElementById('lifeplanCheckoutForm');
  
  // Create a FormData object from the form
  const formData = new FormData(form);
  
  // Convert FormData to a plain object for easier viewing
  const formDataObj = {};
  formData.forEach((value, key) => {
    formDataObj[key] = value;
  });
  
  // Log the form data to the console
  console.log('Lifeplan Checkout Form Data:', formDataObj);
  
  // Here you would typically send this data to your server
  // For now, we'll just log it and show the confirmation modal
  showConfirmationModal();
}

function setupLifeplanPaymentTerms() {
  const paymentTermSelect = document.getElementById('lp-paymentTerm');
  const totalPriceInput = document.getElementById('lp-totalPrice');
  const monthlyPaymentDiv = document.getElementById('lp-monthlyPayment');
  const monthlyPaymentAmount = monthlyPaymentDiv.querySelector('span');
  
  // Function to calculate monthly payment
  function calculateMonthlyPayment() {
    const servicePrice = parseFloat(document.getElementById('lp-service-price').value) || 0;
    const termYears = parseInt(paymentTermSelect.value) || 1;
    
    if (termYears === 1) {
      // Full payment
      monthlyPaymentDiv.classList.add('hidden');
      totalPriceInput.value = servicePrice.toFixed(2);
      document.getElementById('lp-footer-total-price').textContent = 
        `₱${servicePrice.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    } else {
      // Installment
      const termMonths = termYears * 12;
      const monthlyPayment = servicePrice / termMonths;
      
      monthlyPaymentAmount.textContent = 
        `₱${monthlyPayment.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
      monthlyPaymentDiv.classList.remove('hidden');
      
      // Update total price (can be overridden by user)
      totalPriceInput.value = servicePrice.toFixed(2);
      document.getElementById('lp-footer-total-price').textContent = 
        `₱${servicePrice.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }
    
    // Update minimum price
    const minimumPrice = servicePrice * 0.5;
    document.getElementById('lp-min-price').textContent = `₱${minimumPrice.toFixed(2)}`;
  }
  
  // Calculate when term changes
  paymentTermSelect.addEventListener('change', calculateMonthlyPayment);
  
  // Also calculate when service price changes
  document.getElementById('lp-service-price').addEventListener('change', calculateMonthlyPayment);
  
  // Initial calculation
  calculateMonthlyPayment();
}

// Function to open lifeplan checkout
function openLifeplanCheckout() {
  const serviceTypeModal = document.getElementById('serviceTypeModal');
  const serviceId = serviceTypeModal.dataset.serviceId;
  const servicePrice = serviceTypeModal.dataset.servicePrice;
  const branchId = serviceTypeModal.dataset.branchId;

  // Set the service details in the lifeplan form
  document.getElementById('lp-service-id').value = serviceId;
  document.getElementById('lp-service-price').value = servicePrice;
  document.getElementById('lp-branch-id').value = branchId;
  
  // Update the total price in the lifeplan checkout form
  document.getElementById('lp-totalPrice').value = servicePrice;
  document.getElementById('lp-footer-total-price').textContent = 
    `₱${parseFloat(servicePrice).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
  
  // Update minimum price display
  const minimumPrice = parseFloat(servicePrice) * 0.5;
  document.getElementById('lp-min-price').textContent = `₱${minimumPrice.toFixed(2)}`;
  
  setupLifeplanPaymentTerms();
  // Open lifeplan checkout modal
  document.getElementById('lifeplanCheckoutModal').classList.remove('hidden');
}

// Function to close the traditional checkout modal
function closeCheckoutModal() {
  const modal = document.getElementById('checkoutModal');
  if (modal) {
    modal.classList.add('hidden');
    
    // Optionally reset the form fields
    const form = document.getElementById('checkoutForm');
    if (form) {
      form.reset();
    }
    
    // Reset any dynamic elements
    document.getElementById('footer-total-price').textContent = '₱0.00';
  }
}

// Function to close the lifeplan checkout modal
function closeLifeplanCheckoutModal() {
  const modal = document.getElementById('lifeplanCheckoutModal');
  if (modal) {
    modal.classList.add('hidden');
    
    // Optionally reset the form fields
    const form = document.getElementById('lifeplanCheckoutForm');
    if (form) {
      form.reset();
    }
    
    // Reset any dynamic elements
    document.getElementById('lp-footer-total-price').textContent = '₱0.00';
    document.getElementById('lp-monthlyPayment').classList.add('hidden');
  }
}
  
  // Event listeners for filtering and sorting
  
  
  priceFilter.addEventListener('change', filterAndDisplayServices);
  priceSort.addEventListener('change', filterAndDisplayServices);
  serviceSearch.addEventListener('input', event => {
    // Validate search input
    const searchValue = event.target.value;
    const searchError = document.getElementById('search-error');
    
    if (searchValue.startsWith(' ') || searchValue.includes('  ')) {
      searchError.classList.remove('hidden');
    } else {
      searchError.classList.add('hidden');
      filterAndDisplayServices();
    }
  });
  
  function fetchRegions() {
    console.log('Fetching regions...'); // Add this
    fetch('../customer/address/get_regions.php')
        .then(response => {
            console.log('Regions response:', response); // Add this
            return response.json();
        })
        .then(data => {
            const regionSelect = document.getElementById('deceasedRegion');
            regionSelect.innerHTML = '<option value="" disabled selected>Select Region</option>';
            
            data.forEach(region => {
                const option = document.createElement('option');
                option.value = region.region_id;  // Changed from region_code
                option.textContent = region.region_name;
                regionSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error fetching regions:', error));
}

function fetchProvinces(regionCode) {
    console.log('[DEBUG] Fetching provinces for region:', regionCode); // Check if regionCode is correct
    
    const provinceSelect = document.getElementById('deceasedProvince');
    provinceSelect.innerHTML = '<option value="" disabled selected>Select Province</option>';
    provinceSelect.disabled = true;
    
    if (!regionCode) {
        console.warn('[WARNING] No regionCode provided!');
        return;
    }
    
    const apiUrl = `../customer/address/get_provinces.php?region_id=${regionCode}`;
    console.log('[DEBUG] Fetching from:', apiUrl); // Check if URL is correct
    
    fetch(apiUrl)
        .then(response => {
            console.log('[DEBUG] Provinces API Response:', response); // Check HTTP response
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('[DEBUG] Provinces Data:', data); // Check if data is correct
            
            if (!data || data.length === 0) {
                console.warn('[WARNING] No provinces returned!');
                return;
            }
            
            provinceSelect.innerHTML = '<option value="" disabled selected>Select Province</option>';
            
            data.forEach(province => {
                const option = document.createElement('option');
                option.value = province.province_id;  // Changed from province_code
                option.textContent = province.province_name;
                provinceSelect.appendChild(option);
            });
            
            provinceSelect.disabled = false;
        })
        .catch(error => console.error('[ERROR] fetchProvinces failed:', error));
}

function fetchCities(provinceCode) {
    console.log('[DEBUG] Fetching cities for province:', provinceCode); // Check if provinceCode is correct
    
    const citySelect = document.getElementById('deceasedCity');
    citySelect.innerHTML = '<option value="" disabled selected>Select City/Municipality</option>';
    citySelect.disabled = true;
    
    if (!provinceCode) {
        console.warn('[WARNING] No provinceCode provided!');
        return;
    }
    
    const apiUrl = `../customer/address/get_cities.php?province_id=${provinceCode}`;
    console.log('[DEBUG] Fetching from:', apiUrl); // Check if URL is correct
    
    fetch(apiUrl)
        .then(response => {
            console.log('[DEBUG] Cities API Response:', response); // Check HTTP response
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('[DEBUG] Cities Data:', data); // Check if data is correct
            
            if (!data || data.length === 0) {
                console.warn('[WARNING] No cities returned!');
                return;
            }
            
            citySelect.innerHTML = '<option value="" disabled selected>Select City/Municipality</option>';
            
            data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.municipality_id;  // Changed from city_code
                option.textContent = city.municipality_name;  // Changed from city_name
                citySelect.appendChild(option);
            });
            
            citySelect.disabled = false;
        })
        .catch(error => console.error('[ERROR] fetchCities failed:', error));
}

function fetchBarangays(cityCode) {
    console.log('[DEBUG] Fetching barangays for city:', cityCode); // Check if cityCode is correct
    
    const barangaySelect = document.getElementById('deceasedBarangay');
    barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
    barangaySelect.disabled = true;
    
    if (!cityCode) {
        console.warn('[WARNING] No cityCode provided!');
        return;
    }
    
    const apiUrl = `../customer/address/get_barangays.php?city_id=${cityCode}`;
    console.log('[DEBUG] Fetching from:', apiUrl); // Check if URL is correct
    
    fetch(apiUrl)
        .then(response => {
            console.log('[DEBUG] Barangays API Response:', response); // Check HTTP response
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('[DEBUG] Barangays Data:', data); // Check if data is correct
            
            if (!data || data.length === 0) {
                console.warn('[WARNING] No barangays returned!');
                return;
            }
            
            barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
            
            data.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.barangay_id;  // Changed from barangay_code
                option.textContent = barangay.barangay_name;
                barangaySelect.appendChild(option);
            });
            
            barangaySelect.disabled = false;
        })
        .catch(error => console.error('[ERROR] fetchBarangays failed:', error));
}

function updateCombinedAddress() {
    const regionSelect = document.getElementById('deceasedRegion');
    const provinceSelect = document.getElementById('deceasedProvince');
    const citySelect = document.getElementById('deceasedCity');
    const barangaySelect = document.getElementById('deceasedBarangay');
    const streetAddress = document.getElementById('deceasedStreet').value;
    const zipCode = document.getElementById('deceasedZip').value;
    
    const region = regionSelect.options[regionSelect.selectedIndex]?.text || '';
    const province = provinceSelect.options[provinceSelect.selectedIndex]?.text || '';
    const city = citySelect.options[citySelect.selectedIndex]?.text || '';
    const barangay = barangaySelect.options[barangaySelect.selectedIndex]?.text || '';
    
    // Create an array of non-empty address components
    const addressParts = [];
    if (streetAddress) addressParts.push(streetAddress);
    if (barangay) addressParts.push(barangay);
    if (city) addressParts.push(city);
    if (province) addressParts.push(province);
    if (region) addressParts.push(region);
    if (zipCode) addressParts.push(zipCode);
    
    // Join the parts with commas
    const combinedAddress = addressParts.join(', ');
    document.getElementById('deceasedAddress').value = combinedAddress;
}

// Initialize address dropdowns when the page loads
document.addEventListener('DOMContentLoaded', function() {
    fetchRegions();
    
    // Set up event listeners for cascading dropdowns
    document.getElementById('deceasedRegion').addEventListener('change', function() {
        fetchProvinces(this.value);
        document.getElementById('deceasedProvince').value = '';
        document.getElementById('deceasedCity').value = '';
        document.getElementById('deceasedBarangay').value = '';
        document.getElementById('deceasedCity').disabled = true;
        document.getElementById('deceasedBarangay').disabled = true;
        updateCombinedAddress();
    });
    
    document.getElementById('deceasedProvince').addEventListener('change', function() {
        fetchCities(this.value);
        document.getElementById('deceasedCity').value = '';
        document.getElementById('deceasedBarangay').value = '';
        document.getElementById('deceasedBarangay').disabled = true;
        updateCombinedAddress();
    });
    
    document.getElementById('deceasedCity').addEventListener('change', function() {
        fetchBarangays(this.value);
        document.getElementById('deceasedBarangay').value = '';
        updateCombinedAddress();
    });
    
    document.getElementById('deceasedBarangay').addEventListener('change', updateCombinedAddress);
    document.getElementById('deceasedStreet').addEventListener('input', updateCombinedAddress);
    document.getElementById('deceasedZip').addEventListener('input', updateCombinedAddress);
    
    // Also update combined address when form is submitted
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        updateCombinedAddress();
        // Continue with form submission
    });
});


      // LIFE-PLAN 
      // Address handling functions for beneficiary
function fetchBeneficiaryRegions() {
    fetch('../customer/address/get_regions.php')
        .then(response => response.json())
        .then(data => {
            const regionSelect = document.getElementById('beneficiaryRegion');
            regionSelect.innerHTML = '<option value="" disabled selected>Select Region</option>';
            
            data.forEach(region => {
                const option = document.createElement('option');
                option.value = region.region_id;  // Changed to match PHP response
                option.textContent = region.region_name;
                regionSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error fetching regions:', error));
}

function fetchBeneficiaryProvinces(regionId) {
    const provinceSelect = document.getElementById('beneficiaryProvince');
    provinceSelect.innerHTML = '<option value="" disabled selected>Select Province</option>';
    provinceSelect.disabled = true;
    
    if (!regionId) return;
    
    fetch(`../customer/address/get_provinces.php?region_id=${regionId}`)
        .then(response => response.json())
        .then(data => {
            provinceSelect.innerHTML = '<option value="" disabled selected>Select Province</option>';
            
            data.forEach(province => {
                const option = document.createElement('option');
                option.value = province.province_id;  // Changed to match PHP response
                option.textContent = province.province_name;
                provinceSelect.appendChild(option);
            });
            
            provinceSelect.disabled = false;
        })
        .catch(error => console.error('Error fetching provinces:', error));
}

function fetchBeneficiaryCities(provinceId) {
    const citySelect = document.getElementById('beneficiaryCity');
    citySelect.innerHTML = '<option value="" disabled selected>Select City/Municipality</option>';
    citySelect.disabled = true;
    
    if (!provinceId) return;
    
    fetch(`../customer/address/get_cities.php?province_id=${provinceId}`)
        .then(response => response.json())
        .then(data => {
            citySelect.innerHTML = '<option value="" disabled selected>Select City/Municipality</option>';
            
            data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.municipality_id;  // Changed to match PHP response
                option.textContent = city.municipality_name;
                citySelect.appendChild(option);
            });
            
            citySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching cities:', error));
}

function fetchBeneficiaryBarangays(cityId) {
    const barangaySelect = document.getElementById('beneficiaryBarangay');
    barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
    barangaySelect.disabled = true;
    
    if (!cityId) return;
    
    fetch(`../customer/address/get_barangays.php?city_id=${cityId}`)
        .then(response => response.json())
        .then(data => {
            barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
            
            data.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.barangay_id;  // Changed to match PHP response
                option.textContent = barangay.barangay_name;
                barangaySelect.appendChild(option);
            });
            
            barangaySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching barangays:', error));
}

function updateBeneficiaryCombinedAddress() {
    const regionSelect = document.getElementById('beneficiaryRegion');
    const provinceSelect = document.getElementById('beneficiaryProvince');
    const citySelect = document.getElementById('beneficiaryCity');
    const barangaySelect = document.getElementById('beneficiaryBarangay');
    const streetAddress = document.getElementById('beneficiaryStreet').value;
    const zipCode = document.getElementById('beneficiaryZip').value;
    
    const region = regionSelect.options[regionSelect.selectedIndex]?.text || '';
    const province = provinceSelect.options[provinceSelect.selectedIndex]?.text || '';
    const city = citySelect.options[citySelect.selectedIndex]?.text || '';
    const barangay = barangaySelect.options[barangaySelect.selectedIndex]?.text || '';
    
    const addressParts = [];
    if (streetAddress) addressParts.push(streetAddress);
    if (barangay) addressParts.push(barangay);
    if (city) addressParts.push(city);
    if (province) addressParts.push(province);
    if (region) addressParts.push(region);
    if (zipCode) addressParts.push(zipCode);
    
    const combinedAddress = addressParts.join(', ');
    document.getElementById('beneficiaryAddress').value = combinedAddress;
}

// Initialize beneficiary address dropdowns
document.addEventListener('DOMContentLoaded', function() {
    fetchBeneficiaryRegions();
    
    // Set up event listeners for beneficiary cascading dropdowns
    document.getElementById('beneficiaryRegion').addEventListener('change', function() {
        fetchBeneficiaryProvinces(this.value);
        document.getElementById('beneficiaryProvince').value = '';
        document.getElementById('beneficiaryCity').value = '';
        document.getElementById('beneficiaryBarangay').value = '';
        document.getElementById('beneficiaryCity').disabled = true;
        document.getElementById('beneficiaryBarangay').disabled = true;
        updateBeneficiaryCombinedAddress();
    });
    
    document.getElementById('beneficiaryProvince').addEventListener('change', function() {
        fetchBeneficiaryCities(this.value);
        document.getElementById('beneficiaryCity').value = '';
        document.getElementById('beneficiaryBarangay').value = '';
        document.getElementById('beneficiaryBarangay').disabled = true;
        updateBeneficiaryCombinedAddress();
    });
    
    document.getElementById('beneficiaryCity').addEventListener('change', function() {
        fetchBeneficiaryBarangays(this.value);
        document.getElementById('beneficiaryBarangay').value = '';
        updateBeneficiaryCombinedAddress();
    });
    
    document.getElementById('beneficiaryBarangay').addEventListener('change', updateBeneficiaryCombinedAddress);
    document.getElementById('beneficiaryStreet').addEventListener('input', updateBeneficiaryCombinedAddress);
    document.getElementById('beneficiaryZip').addEventListener('input', updateBeneficiaryCombinedAddress);
});

</script>

  <script src="sidebar.js"></script>
  <script src="tailwind.js"></script>

</body>
</html>