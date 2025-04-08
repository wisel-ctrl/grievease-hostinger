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

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Services - GrievEase</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex bg-gray-50">

<?php include 'admin_sidebar.php'; ?>

  <!-- Main Content -->
<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Service Management</h1>
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

<div class="bg-gradient-to-b from-gray-50 to-white p-6 rounded-xl">

  <!-- Summary statistics row with sample data -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm flex items-center">
      <div class="p-3 bg-blue-100 text-blue-600 rounded-full mr-3">
        <i class="fas fa-tags"></i>
      </div>
      <div>
        <p class="text-xs text-gray-500">Total Services</p>
        <p class="text-lg font-semibold">24</p>
      </div>
    </div>
    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm flex items-center">
      <div class="p-3 bg-green-100 text-green-600 rounded-full mr-3">
        <i class="fas fa-check-circle"></i>
      </div>
      <div>
        <p class="text-xs text-gray-500">Active Services</p>
        <p class="text-lg font-semibold">18</p>
      </div>
    </div>
    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm flex items-center">
      <div class="p-3 bg-orange-100 text-orange-600 rounded-full mr-3">
        <i class="fas fa-pause-circle"></i>
      </div>
      <div>
        <p class="text-xs text-gray-500">Inactive Services</p>
        <p class="text-lg font-semibold">6</p>
      </div>
    </div>
  </div>

<!-- Sample Branch Card 1 -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden branch-container" data-branch-id="1">
    <!-- Branch Header with Search and Filters -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center">
            <i class="fas fa-building text-sidebar-accent mr-2"></i>
            <h4 class="text-lg font-bold text-sidebar-text">Branch: Main Branch</h4>
        </div>

        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                <i class="fas fa-clipboard-list"></i>
                12 Services
            </span>
        
        <!-- Search and Filter Section -->
        <div class="flex flex-col md:flex-row items-start md:items-center gap-3 w-full md:w-auto">
            <!-- Search Input -->
            <div class="relative w-full md:w-64">
                <input type="text" id="searchInput1" 
                       placeholder="Search services..." 
                       class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
            </div>

            <!-- Filter Dropdown -->
            <div class="relative filter-dropdown">
                <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
                    <i class="fas fa-filter text-sidebar-accent"></i>
                    <span>Filters</span>
                </button>
            </div>

            <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap">
                <i class="fas fa-plus-circle"></i> Add New Service
            </button>
        </div>
    </div>
    
    <!-- Services Table for this branch -->
    <div class="overflow-x-auto scrollbar-thin" id="tableContainer1">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-sidebar-border">
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text">
                        <div class="flex items-center">
                            <i class="fas fa-hashtag mr-1.5 text-sidebar-accent"></i> ID 
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text">
                        <div class="flex items-center">
                            <i class="fas fa-tag mr-1.5 text-sidebar-accent"></i> Service Name 
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text">
                        <div class="flex items-center">
                            <i class="fas fa-th-list mr-1.5 text-sidebar-accent"></i> Category 
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text">
                        <div class="flex items-center">
                            <i class="fas fa-dollar-sign mr-1.5 text-sidebar-accent"></i> Price 
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text">
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
                <!-- Sample Service Row 1 -->
                <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                    <td class="p-4 text-sm text-sidebar-text font-medium">#SVC-001</td>
                    <td class="p-4 text-sm text-sidebar-text">Basic Funeral Service</td>
                    <td class="p-4 text-sm text-sidebar-text">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                            <i class="fas fa-folder-open mr-1"></i> Funeral Services
                        </span>
                    </td>
                    <td class="p-4 text-sm font-medium text-sidebar-text">$1,500.00</td>
                    <td class="p-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-600 border border-green-200">
                            <i class="fas fa-check-circle mr-1"></i> Active
                        </span>
                    </td>
                    <td class="p-4 text-sm">
                        <div class="flex space-x-2">
                            <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all" title="Edit Service">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all" title="Delete Service">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                
                <!-- Sample Service Row 2 -->
                <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                    <td class="p-4 text-sm text-sidebar-text font-medium">#SVC-002</td>
                    <td class="p-4 text-sm text-sidebar-text">Premium Casket Package</td>
                    <td class="p-4 text-sm text-sidebar-text">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                            <i class="fas fa-folder-open mr-1"></i> Casket Packages
                        </span>
                    </td>
                    <td class="p-4 text-sm font-medium text-sidebar-text">$3,200.00</td>
                    <td class="p-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-600 border border-green-200">
                            <i class="fas fa-check-circle mr-1"></i> Active
                        </span>
                    </td>
                    <td class="p-4 text-sm">
                        <div class="flex space-x-2">
                            <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all" title="Edit Service">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all" title="Delete Service">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                
                <!-- Sample Service Row 3 -->
                <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                    <td class="p-4 text-sm text-sidebar-text font-medium">#SVC-003</td>
                    <td class="p-4 text-sm text-sidebar-text">Memorial Video</td>
                    <td class="p-4 text-sm text-sidebar-text">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                            <i class="fas fa-folder-open mr-1"></i> Memorial Services
                        </span>
                    </td>
                    <td class="p-4 text-sm font-medium text-sidebar-text">$450.00</td>
                    <td class="p-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-500 border border-orange-200">
                            <i class="fas fa-pause-circle mr-1"></i> Inactive
                        </span>
                    </td>
                    <td class="p-4 text-sm">
                        <div class="flex space-x-2">
                            <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all" title="Edit Service">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all" title="Delete Service">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
            <div class="text-sm text-gray-500">
                Showing 1 - 3 of 12 services
            </div>
            <div class="flex space-x-1">
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 cursor-not-allowed" disabled>&laquo;</button>
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm bg-sidebar-accent text-white">1</button>
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">2</button>
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">3</button>
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">4</button>
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&raquo;</button>
            </div>
        </div>
    </div>
</div>

<!-- Sample Branch Card 2 -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden branch-container" data-branch-id="2">
    <!-- Branch Header with Search and Filters -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center">
            <i class="fas fa-building text-sidebar-accent mr-2"></i>
            <h4 class="text-lg font-bold text-sidebar-text">Branch: North Branch</h4>
        </div>

        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                <i class="fas fa-clipboard-list"></i>
                8 Services
            </span>
        
        <!-- Search and Filter Section -->
        <div class="flex flex-col md:flex-row items-start md:items-center gap-3 w-full md:w-auto">
            <!-- Search Input -->
            <div class="relative w-full md:w-64">
                <input type="text" id="searchInput2" 
                       placeholder="Search services..." 
                       class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
            </div>

            <!-- Filter Dropdown -->
            <div class="relative filter-dropdown">
                <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
                    <i class="fas fa-filter text-sidebar-accent"></i>
                    <span>Filters</span>
                </button>
            </div>

            <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap">
                <i class="fas fa-plus-circle"></i> Add New Service
            </button>
        </div>
    </div>
    
    <!-- Services Table for this branch -->
    <div class="overflow-x-auto scrollbar-thin" id="tableContainer2">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-sidebar-border">
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text">
                        <div class="flex items-center">
                            <i class="fas fa-hashtag mr-1.5 text-sidebar-accent"></i> ID 
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text">
                        <div class="flex items-center">
                            <i class="fas fa-tag mr-1.5 text-sidebar-accent"></i> Service Name 
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text">
                        <div class="flex items-center">
                            <i class="fas fa-th-list mr-1.5 text-sidebar-accent"></i> Category 
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text">
                        <div class="flex items-center">
                            <i class="fas fa-dollar-sign mr-1.5 text-sidebar-accent"></i> Price 
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text">
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
                <!-- Sample Service Row 1 -->
                <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                    <td class="p-4 text-sm text-sidebar-text font-medium">#SVC-101</td>
                    <td class="p-4 text-sm text-sidebar-text">Standard Funeral Service</td>
                    <td class="p-4 text-sm text-sidebar-text">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                            <i class="fas fa-folder-open mr-1"></i> Funeral Services
                        </span>
                    </td>
                    <td class="p-4 text-sm font-medium text-sidebar-text">$2,000.00</td>
                    <td class="p-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-600 border border-green-200">
                            <i class="fas fa-check-circle mr-1"></i> Active
                        </span>
                    </td>
                    <td class="p-4 text-sm">
                        <div class="flex space-x-2">
                            <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all" title="Edit Service">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all" title="Delete Service">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                
                <!-- Sample Service Row 2 -->
                <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                    <td class="p-4 text-sm text-sidebar-text font-medium">#SVC-102</td>
                    <td class="p-4 text-sm text-sidebar-text">Deluxe Urn Package</td>
                    <td class="p-4 text-sm text-sidebar-text">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                            <i class="fas fa-folder-open mr-1"></i> Urn Packages
                        </span>
                    </td>
                    <td class="p-4 text-sm font-medium text-sidebar-text">$1,200.00</td>
                    <td class="p-4 text-sm">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-600 border border-green-200">
                            <i class="fas fa-check-circle mr-1"></i> Active
                        </span>
                    </td>
                    <td class="p-4 text-sm">
                        <div class="flex space-x-2">
                            <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all" title="Edit Service">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all" title="Delete Service">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
            <div class="text-sm text-gray-500">
                Showing 1 - 2 of 8 services
            </div>
            <div class="flex space-x-1">
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 cursor-not-allowed" disabled>&laquo;</button>
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm bg-sidebar-accent text-white">1</button>
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">2</button>
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">3</button>
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">4</button>
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&raquo;</button>
            </div>
        </div>
    </div>
</div>

</div>

  <script src="script.js"></script>
  <script src="tailwind.js"></script>
  <script src="sidebar.js"></script>
</body>
</html>