<?php
// employee_inventory.php
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

// Database connection
require_once '../db_connect.php';

// Get employee information
$user_id = $_SESSION['user_id'];
$query = "SELECT id, first_name, last_name, branch_loc FROM users WHERE id = ? AND user_type = 2";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Employee not found or not valid
    session_destroy();
    header("Location: ../Landing_Page/login.php");
    exit();
}

$employee = $result->fetch_assoc();
$branch_id = $employee['branch_loc'];

// Function to generate table row
function generateInventoryRow($row) {
    $html = '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover">';
    $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text font-medium whitespace-nowrap">#INV-'.$row['inventory_id'].'</td>';
    $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text font-medium whitespace-nowrap">'.htmlspecialchars($row['item_name']).'</td>';
    $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text whitespace-nowrap">'.htmlspecialchars($row['category']).'</td>';
    $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text whitespace-nowrap">'.$row['quantity'].'</td>';
    $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text whitespace-nowrap">₱'.number_format($row['price'], 2).'</td>';
    $html .= '<td class="px-4 py-3.5 text-sm text-sidebar-text whitespace-nowrap">₱'.number_format($row['total_value'], 2).'</td>';
    $html .= '<td class="px-4 py-3.5 text-sm whitespace-nowrap">';
    $html .= '<div class="flex items-center gap-2">';
    $html .= '<button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all">';
    $html .= '<i class="fas fa-edit"></i>';
    $html .= '</button>';
    $html .= '<button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all">';
    $html .= '<i class="fas fa-archive"></i>';
    $html .= '</button>';
    $html .= '</div>';
    $html .= '</td>';
    $html .= '</tr>';
    return $html;
}

// Check if it's an AJAX request
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    // AJAX request - return only the table content
    
    // Pagination setup
    $itemsPerPage = 5;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($currentPage - 1) * $itemsPerPage;

    // Count total items
    $count_query = "SELECT COUNT(*) as total FROM inventory_tb WHERE branch_id = ?";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $branch_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $totalItems = $count_result->fetch_assoc()['total'];
    $totalPages = ceil($totalItems / $itemsPerPage);

    // Fetch inventory items for the employee's branch with pagination
    $inventory_query = "SELECT i.inventory_id, i.item_name, ic.category_name as category, 
                       i.quantity, i.price, i.total_value, i.status
                       FROM inventory_tb i
                       JOIN inventory_category ic ON i.category_id = ic.category_id
                       WHERE i.branch_id = ?
                       LIMIT ? OFFSET ?";
    $inventory_stmt = $conn->prepare($inventory_query);
    $inventory_stmt->bind_param("iii", $branch_id, $itemsPerPage, $offset);
    $inventory_stmt->execute();
    $paginatedResult = $inventory_stmt->get_result();

    // Generate table rows
    $rows = '';
    if ($paginatedResult->num_rows > 0) {
        while($row = $paginatedResult->fetch_assoc()) {
            $rows .= generateInventoryRow($row);
        }
    } else {
        $rows .= '<tr>';
        $rows .= '<td colspan="7" class="p-6 text-sm text-center">';
        $rows .= '<div class="flex flex-col items-center">';
        $rows .= '<i class="fas fa-box-open text-gray-300 text-4xl mb-3"></i>';
        $rows .= '<p class="text-gray-500">No inventory items found for this branch</p>';
        $rows .= '</div>';
        $rows .= '</td>';
        $rows .= '</tr>';
    }

    // Generate pagination info
    $paginationInfo = 'Showing ' . min(($currentPage - 1) * $itemsPerPage + 1, $totalItems) . ' - ' . 
                     min($currentPage * $itemsPerPage, $totalItems) . ' of ' . $totalItems . ' items';

    // Generate pagination links
    $paginationLinks = '';
    if ($currentPage > 1) {
        $paginationLinks .= '<a href="#" onclick="loadPage('.$branch_id.', '.($currentPage - 1).')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&laquo;</a>';
    } else {
        $paginationLinks .= '<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">&laquo;</button>';
    }
    
    for ($i = 1; $i <= $totalPages; $i++) {
        $activeClass = ($i == $currentPage) ? 'bg-sidebar-accent text-white' : '';
        $paginationLinks .= '<a href="#" onclick="loadPage('.$branch_id.', '.$i.')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover '.$activeClass.'">'.$i.'</a>';
    }
    
    if ($currentPage < $totalPages) {
        $paginationLinks .= '<a href="#" onclick="loadPage('.$branch_id.', '.($currentPage + 1).')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&raquo;</a>';
    } else {
        $paginationLinks .= '<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">&raquo;</button>';
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'rows' => $rows,
        'paginationInfo' => $paginationInfo,
        'paginationLinks' => $paginationLinks
    ]);
    exit();
}

// Regular page load - show full page
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Inventory</title>
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
  <!-- Sidebar (same as before) -->
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
  <div id="main-content" class="ml-64 p-6 bg-gray-50 min-h-screen transition-all duration-300 main-content">
    <!-- Header with breadcrumb and welcome message -->
    <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
      <div>
        <h1 class="text-2xl font-bold text-sidebar-text">View Inventory</h1>
      </div>
      <div class="flex space-x-3">
        <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
          <i class="fas fa-bell"></i>
        </button>
      </div>
    </div>

    <!-- Inventory Table -->
    <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
      <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
        <h3 class="font-medium text-sidebar-text">Inventory Items</h3>
        <div class="flex items-center gap-3">
          <div class="relative">
            <input type="text" placeholder="Search inventory..." class="pl-9 pr-4 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <i class="fas fa-search text-gray-400"></i>
            </div>
          </div>
            <button onclick="openAddInventoryModal()" class="px-4 py-2 bg-sidebar-accent text-white rounded-md text-sm flex items-center hover:bg-darkgold transition-all duration-300">
              <i class="fas fa-plus mr-2"></i> Add Item
            </button>
        </div>
      </div>

      <!-- Responsive Table Container with improved spacing -->
      <div class="overflow-x-auto scrollbar-thin relative" id="tableContainer<?php echo $branch_id; ?>">
        <div id="loadingIndicator<?php echo $branch_id; ?>" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center z-10">
          <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-sidebar-accent"></div>
        </div>

        <!-- Table will be loaded via AJAX -->
        <div class="min-w-full">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-sidebar-border">
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branch_id; ?>, 0)">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-hashtag text-sidebar-accent"></i> ID 
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branch_id; ?>, 1)">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-box text-sidebar-accent"></i> Item Name 
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branch_id; ?>, 2)">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-th-list text-sidebar-accent"></i> Category 
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branch_id; ?>, 3)">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-cubes text-sidebar-accent"></i> Quantity 
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branch_id; ?>, 4)">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-tag text-sidebar-accent"></i> Unit Price 
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branch_id; ?>, 5)">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-peso-sign text-sidebar-accent"></i> Total Value 
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-cogs text-sidebar-accent"></i> Actions
                  </div>
                </th>
              </tr>
            </thead>
            <tbody id="inventoryTable_<?php echo $branch_id; ?>">
              <!-- Content will be loaded via AJAX -->
            </tbody>
          </table>
        </div>
      </div>
      
      <!-- Sticky Pagination Footer -->
      <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
        <div id="paginationInfo_<?php echo $branch_id; ?>" class="text-sm text-gray-500 text-center sm:text-left">
          <!-- Will be updated via AJAX -->
        </div>
        <div id="paginationLinks_<?php echo $branch_id; ?>" class="flex space-x-2">
          <!-- Will be updated via AJAX -->
        </div>
      </div>
    </div>
  </div>


<!-- Add Inventory Modal -->
<div id="addInventoryModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeAddInventoryModal()"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-sidebar-accent transition-colors" onclick="closeAddInventoryModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        <i class="fas fa-boxes mr-2"></i> Add New Inventory Item
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <form id="addInventoryForm" class="space-y-3 sm:space-y-4" enctype="multipart/form-data">
        <!-- Item Name -->
        <div>
          <label for="itemName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Item Name <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="text" id="itemName" name="itemName" required 
                   class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                   placeholder="Item Name">
          </div>
        </div>
        
        <!-- Category -->
        <div>
          <label for="category_id" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Category <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <select id="category_id" name="category_id" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              <option value="" disabled selected>Select a Category</option>
              <?php
              // Include database connection
              include '../db_connect.php';

              // Fetch categories from the database
              $sql = "SELECT category_id, category_name FROM inventory_category";
              $result = $conn->query($sql);
              
              if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                      echo '<option value="' . $row['category_id'] . '">' . htmlspecialchars($row['category_name']) . '</option>';
                  }
              } else {
                  echo '<option value="" disabled>No Categories Available</option>';
              }
              ?>
            </select>
          </div>
        </div>
        
        <!-- Quantity -->
        <div>
          <label for="quantity" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Quantity <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="number" id="quantity" name="quantity" min="1" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Quantity">
          </div>
        </div>
        
        <!-- Unit Price -->
        <div>
          <label for="unitPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Unit Price <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="unitPrice" name="unitPrice" step="0.01" min="0" required 
                   class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                   placeholder="0.00">
          </div>
        </div>
        
        <!-- File Upload -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <label for="itemImage" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Upload Item Image
          </label>
          <div class="relative">
            <div id="imagePreviewContainer" class="hidden mb-3">
              <img id="imagePreview" src="#" alt="Preview" class="max-h-40 rounded-lg border border-gray-300">
            </div>
            <div class="flex items-center border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-1 focus-within:ring-sidebar-accent focus-within:border-sidebar-accent transition-all duration-200">
              <input type="file" id="itemImage" name="itemImage" accept="image/*" 
                     class="w-full focus:outline-none"
                     onchange="previewImage(this)">
            </div>
          </div>
        </div>
 
    </div>
    
    <!-- Modal Footer --> 
        <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
            <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeAddInventoryModal()">
                Cancel
            </button>
            <button type="submit" id="submitInventoryBtn" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
                Add Item
            </button>
        </div>
    </form>
  </div>
</div>


<script>
    // Load initial page content
    document.addEventListener('DOMContentLoaded', function() {
        loadPage(<?php echo $branch_id; ?>, 1);
    });

    // AJAX function to load page content
    function loadPage(branchId, page) {
        const tableContainer = document.getElementById(`tableContainer${branchId}`);
        const loadingIndicator = document.getElementById(`loadingIndicator${branchId}`);
        const inventoryTable = document.getElementById(`inventoryTable_${branchId}`);
        const paginationInfo = document.getElementById(`paginationInfo_${branchId}`);
        const paginationLinks = document.getElementById(`paginationLinks_${branchId}`);

        // Show loading indicator
        loadingIndicator.classList.remove('hidden');
        tableContainer.style.opacity = '0.5';

        // Make AJAX request
        fetch(`employee_inventory.php?ajax=1&page=${page}&branch_id=${branchId}`)
            .then(response => response.json())
            .then(data => {
                // Update table content
                inventoryTable.innerHTML = data.rows;
                
                // Update pagination info
                paginationInfo.textContent = data.paginationInfo;
                
                // Update pagination links
                paginationLinks.innerHTML = data.paginationLinks;
                
                // Hide loading indicator
                loadingIndicator.classList.add('hidden');
                tableContainer.style.opacity = '1';
            })
            .catch(error => {
                console.error('Error:', error);
                loadingIndicator.classList.add('hidden');
                tableContainer.style.opacity = '1';
            });
    }

    function sortTable(branchId, columnIndex) {
        // Implement your sorting logic here
        console.log(`Sorting branch ${branchId} by column ${columnIndex}`);
        // You can extend this to include sorting in your AJAX call
    }
    
    // Add Inventory function
    // Function to open the add inventory modal
    function openAddInventoryModal() {
        document.getElementById('addInventoryModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    // Function to close the add inventory modal
    function closeAddInventoryModal() {
        document.getElementById('addInventoryModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        // Reset form and clear preview
        document.getElementById('addInventoryForm').reset();
        document.getElementById('imagePreviewContainer').classList.add('hidden');
    }

    // Image preview function
    function previewImage(input) {
        const previewContainer = document.getElementById('imagePreviewContainer');
        const preview = document.getElementById('imagePreview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.src = e.target.result;
                previewContainer.classList.remove('hidden');
            }
            
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.src = '#';
            previewContainer.classList.add('hidden');
        }
    }

    // FIXED Form submission handler
// Update the form submission handler
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addInventoryForm');
    if (form) {
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            // Use the button's ID for reliable selection
            const submitBtn = document.getElementById('submitInventoryBtn');
            
            if (!submitBtn) {
                console.error('Submit button not found');
                return;
            }
            
            const originalBtnText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Adding...';
            
            try {
                // Make sure the path is correct - adjust if needed
                const response = await fetch('inventory/add_inventory.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    });
                    
                    closeAddInventoryModal();
                    loadPage(<?php echo $branch_id; ?>, 1);
                } else {
                    throw new Error(data.message || 'Failed to add item');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'An error occurred while adding the item',
                });
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            }
        });
    }
});
</script>
  <script src="sidebar.js"></script>
  <script src="tailwind.js"></script>
</body>
</html>