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
    // Determine quantity cell class based on stock level
    $quantity = $row["quantity"];
    
    // Simplified stock level visualization with just three levels using text color
    if ($quantity <= 2) { // Critical stock
        $quantityClass = 'quantity-cell text-red-600 font-bold';
        $quantityText = $quantity . ' <span class="text-xs ml-1">(Critical)</span>';
        $stockIcon = '<i class="fas fa-exclamation-triangle mr-1"></i>';
    } elseif ($quantity <= 5) { // Low stock
        $quantityClass = 'quantity-cell text-yellow-600 font-medium';
        $quantityText = $quantity . ' <span class="text-xs ml-1">(Low)</span>';
        $stockIcon = '<i class="fas fa-arrow-down mr-1"></i>';
    } else { // Normal stock
        $quantityClass = 'quantity-cell text-green-600';
        $quantityText = $quantity;
        $stockIcon = '';
    }

    $html = '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">';
    $html .= '<td class="p-4 text-sm text-sidebar-text font-medium">#INV-' . str_pad($row["inventory_id"], 3, '0', STR_PAD_LEFT) . '</td>';
    $html .= '<td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($row["item_name"]) . '</td>';
    $html .= '<td class="p-4 text-sm text-sidebar-text">';
    $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">';
    $html .= htmlspecialchars($row["category"]) . '</span>';
    $html .= '</td>';
    
    // Enhanced quantity cell with visual indicators and proper padding
    $html .= '<td class="p-0 text-sm">';
    $html .= '<div class="' . $quantityClass . ' px-3 py-2 rounded-lg flex items-center justify-center">';
    $html .= $stockIcon . $quantityText;
    $html .= '</div>';
    $html .= '</td>';
    
    $html .= '<td class="p-4 text-sm font-medium text-sidebar-text" data-sort-value="' . $row["price"] . '">₱' . number_format($row["price"], 2) . '</td>';
    $html .= '<td class="p-4 text-sm font-medium text-sidebar-text" data-sort-value="' . $row["total_value"] . '">₱' . number_format($row["total_value"], 2) . '</td>';
    $html .= '<td class="p-4 text-sm">';
    $html .= '<div class="flex space-x-2">';
    $html .= '<button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all edit-btn" data-id="' . $row['inventory_id'] . '">';
    $html .= '<i class="fas fa-edit"></i>';
    $html .= '</button>';
    $html .= '<button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all archive-btn" data-id="' . $row['inventory_id'] . '" data-name="' . htmlspecialchars($row['item_name']) . '">';
    $html .= '<i class="fas fa-archive"></i>';
    $html .= '</button>';
    $html .= '</div>';
    $html .= '</td>';
    $html .= '</tr>';
    return $html;
}

// Check if it's an AJAX request
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $itemsPerPage = 5;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($currentPage - 1) * $itemsPerPage;

    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
    
    $count_query = "SELECT COUNT(*) as total FROM inventory_tb WHERE branch_id = ? AND status = 1";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $branch_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $totalItems = $count_result->fetch_assoc()['total'];
    $totalPages = ceil($totalItems / $itemsPerPage);

    $inventory_query = "SELECT i.inventory_id, i.item_name, ic.category_name as category, 
                       i.quantity, i.price, i.total_value, i.status
                       FROM inventory_tb i
                       JOIN inventory_category ic ON i.category_id = ic.category_id
                       WHERE i.branch_id = ? AND i.status = 1";
    
    switch ($sort) {
        case 'price_asc':
            $inventory_query .= " ORDER BY i.price ASC";
            break;
        case 'price_desc':
            $inventory_query .= " ORDER BY i.price DESC";
            break;
        case 'quantity_asc':
            $inventory_query .= " ORDER BY i.quantity ASC";
            break;
        case 'quantity_desc':
            $inventory_query .= " ORDER BY i.quantity DESC";
            break;
        case 'name_asc':
            $inventory_query .= " ORDER BY i.item_name ASC";
            break;
        case 'name_desc':
            $inventory_query .= " ORDER BY i.item_name DESC";
            break;
        default:
            $inventory_query .= " ORDER BY i.inventory_id ASC";
            break;
    }

    $inventory_query .= " LIMIT ? OFFSET ?";
    $inventory_stmt = $conn->prepare($inventory_query);
    $inventory_stmt->bind_param("iii", $branch_id, $itemsPerPage, $offset);
    $inventory_stmt->execute();
    $paginatedResult = $inventory_stmt->get_result();

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

    $paginationInfo = 'Showing ' . min(($currentPage - 1) * $itemsPerPage + 1, $totalItems) . ' - ' . 
                     min($currentPage * $itemsPerPage, $totalItems) . ' of ' . $totalItems . ' items';

    $paginationLinks = '';
    // First page (<<)
    if ($currentPage > 1) {
        $paginationLinks .= '<a href="#" onclick="loadPage(' . $branch_id . ', 1, \'' . $sort . '\')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">«</a>';
    } else {
        $paginationLinks .= '<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">«</button>';
    }
    
    // Previous page (<)
    if ($currentPage > 1) {
        $paginationLinks .= '<a href="#" onclick="loadPage(' . $branch_id . ', ' . ($currentPage - 1) . ', \'' . $sort . '\')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">‹</a>';
    } else {
        $paginationLinks .= '<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">‹</button>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $activeClass = ($i == $currentPage) ? 'bg-sidebar-accent text-white' : '';
        $paginationLinks .= '<a href="#" onclick="loadPage(' . $branch_id . ', ' . $i . ', \'' . $sort . '\')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ' . $activeClass . '">' . $i . '</a>';
    }
    
    // Next page (>)
    if ($currentPage < $totalPages) {
        $paginationLinks .= '<a href="#" onclick="loadPage(' . $branch_id . ', ' . ($currentPage + 1) . ', \'' . $sort . '\')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">›</a>';
    } else {
        $paginationLinks .= '<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">›</button>';
    }
    
    // Last page (>>)
    if ($currentPage < $totalPages) {
        $paginationLinks .= '<a href="#" onclick="loadPage(' . $branch_id . ', ' . $totalPages . ', \'' . $sort . '\')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">»</a>';
    } else {
        $paginationLinks .= '<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">»</button>';
    }

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
  <?php include 'faviconLogo.php'; ?>
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

    /* Heatmap color scale */
    .quantity-cell {
      border-radius: 0.5rem;
      padding: 0.5rem 1rem;
      text-align: center;
    }
    
    .quantity-critical {
      background-color: #fee2e2; /* red-100 */
      color: #b91c1c; /* red-800 */
      font-weight: 600;
    }
    
    .quantity-warning {
      background-color: #fef3c7; /* amber-100 */
      color: #92400e; /* amber-800 */
      font-weight: 500;
    }
    
    .quantity-normal {
      background-color: #dcfce7; /* green-100 */
      color: #166534; /* green-800 */
    }
    
    .quantity-high {
      background-color: #f0fdf4; /* emerald-50 */
      color: #064e3b; /* emerald-900 */
    }
  </style>
</head>
<body class="flex bg-gray-50">
    <!-- Archived Inventory Items Modal -->
<div id="archivedItemsModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeArchivedModal()"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-sidebar-accent transition-colors" onclick="closeArchivedModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        <i class="fas fa-archive mr-2"></i> Archived Inventory Items
      </h3>
    </div>
    
    <!-- Search Bar -->
    <div class="px-4 sm:px-6 pt-4 pb-2 border-b border-gray-200">
      <div class="relative">
        <input type="text" id="archivedItemsSearch" placeholder="Search archived items..." 
               class="w-full pl-10 pr-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
          <i class="fas fa-search text-gray-400"></i>
        </div>
        <button id="clearArchivedSearch" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <div id="archivedItemsContent" class="min-h-[200px]">
        <!-- Content will be loaded via AJAX -->
        <div class="flex justify-center items-center h-full">
          <i class="fas fa-spinner fa-spin text-2xl text-gray-400"></i>
        </div>
      </div>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex justify-end border-t border-gray-200 sticky bottom-0 bg-white">
      <button type="button" class="px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200" onclick="closeArchivedModal()">
        Close
      </button>
    </div>
  </div>
</div>

<!-- Add New Inventory Item Modal -->
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
                   placeholder="Item Name"
                   minlength="2"
                   oninput="validateNameInput(this)"
                   onpaste="cleanPastedName(this)">
            <div id="itemNameError" class="text-red-500 text-xs mt-1 hidden">Item name must contain only letters and spaces (minimum 2 characters)</div>
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
            <div id="categoryError" class="text-red-500 text-xs mt-1 hidden">Please select a category</div>
          </div>
        </div>
        
        <!-- Quantity -->
        <div>
          <label for="quantity" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Quantity <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="number" id="quantity" name="quantity" min="1" required 
                   class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                   placeholder="Quantity"
                   oninput="validateQuantity(this)">
            <div id="quantityError" class="text-red-500 text-xs mt-1 hidden">Quantity must be 0 or more</div>
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
            <input type="number" id="unitPrice" name="unitPrice" step="0.01" min="0.01" required 
                   class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                   placeholder="0.00"
                   oninput="validateUnitPrice(this)">
            <div id="unitPriceError" class="text-red-500 text-xs mt-1 hidden">Price must be 0.00 or more</div>
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
            <div id="imageError" class="text-red-500 text-xs mt-1 hidden">Please upload a valid image file</div>
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
</div>

<!-- Edit Inventory Item Modal -->
<div id="editInventoryModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeEditInventoryModal()"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-sidebar-accent transition-colors" onclick="closeEditInventoryModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        <i class="fas fa-edit mr-2"></i> Edit Inventory Item
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <form id="editInventoryForm" class="space-y-3 sm:space-y-4" enctype="multipart/form-data">
        <input type="hidden" id="editInventoryId" name="editInventoryId">
        
        <!-- Item Name -->
        <div>
          <label for="editItemName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Item Name <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="text" id="editItemName" name="editItemName" required 
                   class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                   placeholder="Item Name"
                   minlength="2"
                   oninput="validateNameInput(this)"
                   onpaste="cleanPastedName(this)">
            <div id="editItemNameError" class="text-red-500 text-xs mt-1 hidden">Item name must contain only letters and spaces (minimum 2 characters)</div>
          </div>
        </div>
        
        <!-- Category -->
        <div>
          <label for="editCategoryId" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Category <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <select id="editCategoryId" name="editCategoryId" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              <option value="" disabled selected>Select a Category</option>
              <?php
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
            <div id="editCategoryError" class="text-red-500 text-xs mt-1 hidden">Please select a category</div>
          </div>
        </div>
        
        <!-- Quantity -->
        <div>
          <label for="editQuantity" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Quantity <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="number" id="editQuantity" name="editQuantity" min="0" required 
                   class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                   placeholder="Quantity"
                   oninput="validateQuantity(this)">
            <div id="editQuantityError" class="text-red-500 text-xs mt-1 hidden">Quantity must be 0 or more</div>
          </div>
        </div>
        
        <!-- Unit Price -->
        <div>
          <label for="editUnitPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Unit Price <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="editUnitPrice" name="editUnitPrice" step="0.01" min="0" required 
                   class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                   placeholder="0.00"
                   oninput="validateUnitPrice(this)">
            <div id="editUnitPriceError" class="text-red-500 text-xs mt-1 hidden">Price must be 0.00 or more</div>
          </div>
        </div>
        
        <!-- Current Image Preview -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Current Image
          </label>
          <div id="currentImageContainer" class="flex justify-center">
            <img id="currentImagePreview" src="#" alt="Current Image" class="max-h-40 rounded-lg border border-gray-300">
          </div>
        </div>
        
        <!-- File Upload -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <label for="editItemImage" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Upload New Image
          </label>
          <div class="relative">
            <div id="editImagePreviewContainer" class="hidden mb-3">
              <img id="editImagePreview" src="#" alt="Preview" class="max-h-40 rounded-lg border border-gray-300">
            </div>
            <div class="flex items-center border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-1 focus-within:ring-sidebar-accent focus-within:border-sidebar-accent transition-all duration-200">
              <input type="file" id="editItemImage" name="editItemImage" accept="image/*" 
                     class="w-full focus:outline-none"
                     onchange="previewEditImage(this)">
            </div>
            <div id="editImageError" class="text-red-500 text-xs mt-1 hidden">Please upload a valid image file</div>
          </div>
        </div>
        
        <!-- Modal Footer --> 
        <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
          <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeEditInventoryModal()">
            Cancel
          </button>
          <button type="submit" id="submitEditInventoryBtn" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
  <!-- Sidebar (same as before) -->
<?php include 'employee_sidebar.php'; ?>

<!-- Main Content -->
  <div id="main-content" class="ml-64 p-6 bg-gray-50 min-h-screen transition-all duration-300 main-content">
    <!-- Header with breadcrumb and welcome message -->
    <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
      <div>
        <h1 class="text-2xl font-bold text-sidebar-text">View Inventory</h1>
      </div>
    </div>

    <!-- Inventory Overview Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <?php
        // Get total items count
        $totalItemsQuery = "SELECT COUNT(*) as total_items FROM inventory_tb WHERE branch_id = ? AND status = 1";
        $totalItemsStmt = $conn->prepare($totalItemsQuery);
        $totalItemsStmt->bind_param("i", $branch_id);
        $totalItemsStmt->execute();
        $totalItems = $totalItemsStmt->get_result()->fetch_assoc()['total_items'];
        
        // Get total inventory value
        $totalValueQuery = "SELECT SUM(quantity * price) as total_value FROM inventory_tb WHERE branch_id = ? AND status = 1";
        $totalValueStmt = $conn->prepare($totalValueQuery);
        $totalValueStmt->bind_param("i", $branch_id);
        $totalValueStmt->execute();
        $totalValue = $totalValueStmt->get_result()->fetch_assoc()['total_value'];
        
        // Get low stock items (let's define low stock as quantity < 5)
        $lowStockQuery = "SELECT COUNT(*) as low_stock FROM inventory_tb WHERE branch_id = ? AND quantity < 5 AND status = 1";
        $lowStockStmt = $conn->prepare($lowStockQuery);
        $lowStockStmt->bind_param("i", $branch_id);
        $lowStockStmt->execute();
        $lowStock = $lowStockStmt->get_result()->fetch_assoc()['low_stock'];
        
        // Calculate turnover rate (simplified - could be based on historical data)
        $turnoverQuery = "SELECT 
                            (SUM(CASE WHEN quantity < 10 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as turnover_rate 
                          FROM inventory_tb 
                          WHERE branch_id = ? AND status = 1";
        $turnoverStmt = $conn->prepare($turnoverQuery);
        $turnoverStmt->bind_param("i", $branch_id);
        $turnoverStmt->execute();
        $turnoverRate = $turnoverStmt->get_result()->fetch_assoc()['turnover_rate'];
        $turnoverRate = number_format($turnoverRate, 1);
        
        // Get comparison data from last month
        $lastMonthQuery = "SELECT 
                            COUNT(*) as last_month_items,
                            SUM(quantity * price) as last_month_value,
                            SUM(CASE WHEN quantity < 5 THEN 1 ELSE 0 END) as last_month_low_stock
                          FROM inventory_tb 
                          WHERE branch_id = ? 
                          AND status = 1 
                          AND updated_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 2 MONTH) 
                          AND updated_at < DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)";
        $lastMonthStmt = $conn->prepare($lastMonthQuery);
        $lastMonthStmt->bind_param("i", $branch_id);
        $lastMonthStmt->execute();
        $lastMonthData = $lastMonthStmt->get_result()->fetch_assoc();
        
        // Calculate percentage changes
        $itemsChange = $lastMonthData['last_month_items'] > 0 ? 
                      (($totalItems - $lastMonthData['last_month_items']) / $lastMonthData['last_month_items']) * 100 : 0;
        $valueChange = $lastMonthData['last_month_value'] > 0 ? 
                      (($totalValue - $lastMonthData['last_month_value']) / $lastMonthData['last_month_value']) * 100 : 0;
        $lowStockChange = $lastMonthData['last_month_low_stock'] > 0 ? 
                         (($lowStock - $lastMonthData['last_month_low_stock']) / $lastMonthData['last_month_low_stock']) * 100 : 0;
        
        // Card data array
        $cards = [
            [
                'title' => 'Total Items',
                'value' => $totalItems,
                'change' => $itemsChange,
                'icon' => 'boxes',
                'color' => 'blue',
                'prefix' => ''
            ],
            [
                'title' => 'Total Value',
                'value' => number_format($totalValue, 2),
                'change' => $valueChange,
                'icon' => 'peso-sign',
                'color' => 'green',
                'prefix' => '₱'
            ],
            [
                'title' => 'Low Stock Items',
                'value' => $lowStock,
                'change' => $lowStockChange,
                'icon' => 'exclamation-triangle',
                'color' => 'orange',
                'prefix' => '',
                'inverse_change' => true // For low stock, increasing is bad
            ],
            [
                'title' => 'Turnover Rate',
                'value' => $turnoverRate,
                'change' => 3, // Hardcoded as in original
                'icon' => 'sync-alt',
                'color' => 'purple',
                'prefix' => '',
                'suffix' => '%'
            ]
        ];
        
        foreach ($cards as $card) {
            // Determine if change is positive (for display)
            $isPositive = isset($card['inverse_change']) && $card['inverse_change'] ? 
                        $card['change'] < 0 : $card['change'] >= 0;
            
            // Set color class for change indicator
            $changeColorClass = $isPositive ? 'text-emerald-600' : 'text-rose-600';
            
            // Format the change value
            $changeValue = abs(round($card['change']));
            
            // Set suffix if present
            $suffix = isset($card['suffix']) ? $card['suffix'] : '';
        ?>
        
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
            <!-- Card header with brighter gradient background -->
            <div class="bg-gradient-to-r from-<?php echo $card['color']; ?>-100 to-<?php echo $card['color']; ?>-200 px-6 py-4">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-medium text-gray-700"><?php echo $card['title']; ?></h3>
                    <div class="w-10 h-10 rounded-full bg-white/90 text-<?php echo $card['color']; ?>-600 flex items-center justify-center">
                        <i class="fas fa-<?php echo $card['icon']; ?>"></i>
                    </div>
                </div>
                <div class="flex items-end">
                    <span class="text-2xl md:text-3xl font-bold text-gray-800"><?php echo $card['prefix'] . $card['value'] . $suffix; ?></span>
                </div>
            </div>
            
            <!-- Card footer with change indicator -->
            <div class="px-6 py-3 bg-white border-t border-gray-100">
                <div class="flex items-center <?php echo $changeColorClass; ?>">
                    <i class="fas fa-arrow-<?php echo $isPositive ? 'up' : 'down'; ?> mr-1.5 text-xs"></i>
                    <span class="font-medium text-xs"><?php echo $changeValue; ?>% </span>
                    <span class="text-xs text-gray-500 ml-1">from last month</span>
                </div>
            </div>
        </div>
        
        <?php } ?>
    </div>

    <!-- Inventory Table -->
<div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
  <!-- Branch Header with Search and Filters -->
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
    <!-- Desktop layout for big screens - Title on left, controls on right -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
      <!-- Title and Counter -->
      <div class="flex items-center gap-3 mb-4 lg:mb-0">
        <h3 class="font-medium text-sidebar-text">Inventory Items</h3>
        
        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
          <?php echo $totalItems . ($totalItems != 1 ? "" : ""); ?>
        </span>
      </div>
      
      <!-- Controls for big screens - aligned right -->
      <div class="hidden lg:flex items-center gap-3">
        <!-- Search Input -->
        <div class="relative">
          <input type="text" id="inventorySearch" 
                placeholder="Search inventory..." 
                class="pl-9 pr-8 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
          <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <i class="fas fa-search text-gray-400"></i>
          </div>
          <button id="clearSearch" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
              <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Filter Dropdown -->
        <div class="relative filter-dropdown">
          <button id="filterButton_mobile<?php echo $branch_id; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
            <i class="fas fa-filter text-sidebar-accent"></i>
            <span>Filters</span>
            <span id="filterIndicator_mobile<?php echo $branch_id; ?>" class="hidden h-2 w-2 bg-sidebar-accent rounded-full"></span>
          </button>
          
          <!-- Filter Window -->
          <div id="filterDropdown_mobile<?php echo $branch_id; ?>" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
            <div class="space-y-4">
              <!-- Sort Options -->
              <div>
                <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                <div class="space-y-1">
                  <div class="flex items-center cursor-pointer">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full" data-sort="default" data-branch="<?php echo $branch_id; ?>">
                      Default (Unsorted)
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full" data-sort="price_asc" data-branch="<?php echo $branch_id; ?>">
                      Price: Low to High
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full" data-sort="price_desc" data-branch="<?php echo $branch_id; ?>">
                      Price: High to Low
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full" data-sort="quantity_asc" data-branch="<?php echo $branch_id; ?>">
                      Quantity: Low to High
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full" data-sort="quantity_desc" data-branch="<?php echo $branch_id; ?>">
                      Quantity: High to Low
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full" data-sort="name_asc" data-branch="<?php echo $branch_id; ?>">
                      Name: A to Z
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full" data-sort="name_desc" data-branch="<?php echo $branch_id; ?>">
                      Name: Z to A
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Archive Button -->
        <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover whitespace-nowrap"
                onclick="openArchivedModal()">
          <i class="fas fa-archive text-sidebar-accent"></i>
          <span>Archived</span>
        </button>

        <!-- Add Item Button -->
        <button class="px-4 py-2 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap"
                onclick="openAddInventoryModal()"> Add Item
        </button>
      </div>
    </div>
    
    <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
    <div class="lg:hidden w-full mt-4">
      <!-- First row: Search bar with filter and archive icons on the right -->
      <div class="flex items-center w-full gap-3 mb-4">
        <!-- Search Input - Takes most of the space -->
        <div class="relative flex-grow">
          <input type="text" id="inventorySearch" 
                  placeholder="Search inventory..." 
                  class="pl-9 pr-8 py-2.5 w-full border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
          <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <i class="fas fa-search text-gray-400"></i>
          </div>
          <button id="clearSearch" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
              <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Icon-only buttons for filter and archive -->
        <div class="flex items-center gap-3">
          <!-- Filter Icon Button -->
          <div class="relative filter-dropdown">
            <button id="filterButton_<?php echo $branch_id; ?>" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
              <i class="fas fa-filter text-xl"></i>
              <span id="filterIndicator_<?php echo $branch_id; ?>" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
            </button>
            
            <!-- Filter Window - Positioned below the icon -->
            <div id="filterDropdown_<?php echo $branch_id; ?>" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
              <div class="space-y-4">
                <!-- Sort Options -->
                <div>
                  <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                  <div class="space-y-2">
                    <div class="flex items-center cursor-pointer">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full" data-sort="default" data-branch="<?php echo $branch_id; ?>">
                        Default (Unsorted)
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full" data-sort="price_asc" data-branch="<?php echo $branch_id; ?>">
                        Price: Low to High
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full" data-sort="price_desc" data-branch="<?php echo $branch_id; ?>">
                        Price: High to Low
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full" data-sort="quantity_asc" data-branch="<?php echo $branch_id; ?>">
                        Quantity: Low to High
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full" data-sort="quantity_desc" data-branch="<?php echo $branch_id; ?>">
                        Quantity: High to Low
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full" data-sort="name_asc" data-branch="<?php echo $branch_id; ?>">
                        Name: A to Z
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full" data-sort="name_desc" data-branch="<?php echo $branch_id; ?>">
                        Name: Z to A
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Archive Icon Button -->
          <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent" onclick="openArchivedModal()">
            <i class="fas fa-archive text-xl"></i>
          </button>
        </div>
      </div>

      <!-- Second row: Add Item Button - Full width -->
      <div class="w-full">
        <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap w-full justify-center" 
                onclick="openAddInventoryModal()"> Add Item
        </button>
      </div>
    </div>
  </div>
  
  <!-- Responsive Table Container with improved spacing -->
  <div class="overflow-x-auto scrollbar-thin relative" id="tableContainer<?php echo $branch_id; ?>">
    <div id="loadingIndicator<?php echo $branch_id; ?>" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center z-10">
      <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-sidebar-accent"></div>
    </div>

    <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
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
  
  <!-- Sticky Pagination Footer with improved spacing -->
  <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="paginationInfo_<?php echo $branch_id; ?>" class="text-sm text-gray-500 text-center sm:text-left">
      <!-- Will be updated via AJAX -->
    </div>
    <div id="paginationLinks_<?php echo $branch_id; ?>" class="flex space-x-2">
      <!-- Will be updated via AJAX -->
    </div>
  </div>
</div>



<script>
// Validate name input (letters only, capitalize first letter, no multiple spaces)
function validateNameInput(input) {
  const errorElement = document.getElementById(input.id + 'Error');
  let value = input.value;
  
  // Remove any numbers or special characters
  value = value.replace(/[^a-zA-Z\s]/g, '');
  
  // Replace multiple spaces with single space
  value = value.replace(/\s+/g, ' ');
  
  // Capitalize first letter of each word
  value = value.replace(/\b\w/g, char => char.toUpperCase());
  
  // If user tries to start with space or add space before 2 characters
  if ((value.startsWith(' ') || (value.includes(' ') && value.replace(/\s/g, '').length < 2))) {
    value = value.trim();
  }
  
  input.value = value;
  
  // Validate minimum length
  if (input.required && value.trim().length < 2) {
    errorElement.classList.remove('hidden');
    return false;
  } else {
    errorElement.classList.add('hidden');
    return true;
  }
}

// Clean pasted text for name fields
function cleanPastedName(input) {
  setTimeout(() => {
    validateNameInput(input);
  }, 0);
}

// Validate quantity (must be 0 or more)
function validateQuantity(input) {
  const errorElement = document.getElementById(input.id + 'Error');
  let value = parseFloat(input.value);
  
  if (isNaN(value) || value < 0) {
    errorElement.classList.remove('hidden');
    return false;
  } else {
    errorElement.classList.add('hidden');
    return true;
  }
}

// Validate unit price (must be 0.00 or more)
function validateUnitPrice(input) {
  const errorElement = document.getElementById(input.id + 'Error');
  let value = parseFloat(input.value);
  
  if (isNaN(value) || value < 0) {
    errorElement.classList.remove('hidden');
    return false;
  } else {
    errorElement.classList.add('hidden');
    return true;
  }
}

</script>

<script>
// Edit Modal specific functions
function previewEditImage(input) {
  const errorElement = document.getElementById('editImageError');
  const previewContainer = document.getElementById('editImagePreviewContainer');
  const preview = document.getElementById('editImagePreview');
  
  if (input.files && input.files[0]) {
    const file = input.files[0];
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (validTypes.includes(file.type)) {
      const reader = new FileReader();
      
      reader.onload = function(e) {
        preview.src = e.target.result;
        previewContainer.classList.remove('hidden');
        errorElement.classList.add('hidden');
      }
      
      reader.readAsDataURL(file);
    } else {
      errorElement.classList.remove('hidden');
      previewContainer.classList.add('hidden');
      input.value = '';
    }
  } else {
    previewContainer.classList.add('hidden');
  }
}

// Validate the edit form before submission
document.getElementById('editInventoryForm').addEventListener('submit', function(e) {
  const itemNameValid = validateNameInput(document.getElementById('editItemName'));
  const categoryValid = document.getElementById('editCategoryId').value !== '';
  const quantityValid = validateQuantity(document.getElementById('editQuantity'));
  const unitPriceValid = validateUnitPrice(document.getElementById('editUnitPrice'));
  
  // Show error if category not selected
  if (!categoryValid) {
    document.getElementById('editCategoryError').classList.remove('hidden');
  } else {
    document.getElementById('editCategoryError').classList.add('hidden');
  }
  
  if (!itemNameValid || !categoryValid || !quantityValid || !unitPriceValid) {
    e.preventDefault();
    // Scroll to the first error
    const firstError = document.querySelector('.text-red-500:not(.hidden)');
    if (firstError) {
      firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }
});
</script>


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

//edit inventory functions
// Function to open the edit inventory modal
function openEditInventoryModal(inventoryId) {
    // Show loading state
    const modal = document.getElementById('editInventoryModal');
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    
    // Fetch inventory item details
    fetch(`inventory/get_inventory_item.php?id=${inventoryId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate form fields
                document.getElementById('editInventoryId').value = data.item.inventory_id;
                document.getElementById('editItemName').value = data.item.item_name;
                document.getElementById('editCategoryId').value = data.item.category_id;
                document.getElementById('editQuantity').value = data.item.quantity;
                document.getElementById('editUnitPrice').value = data.item.price;
                
                // Set current image preview
                const currentImagePreview = document.getElementById('currentImagePreview');
                if (data.item.inventory_img) {
                    currentImagePreview.src = data.item.inventory_img;
                    document.getElementById('currentImageContainer').classList.remove('hidden');
                } else {
                    document.getElementById('currentImageContainer').classList.add('hidden');
                }
            } else {
                throw new Error(data.message || 'Failed to load item details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'An error occurred while loading item details',
            });
            closeEditInventoryModal();
        });
}

// Function to close the edit inventory modal
function closeEditInventoryModal() {
    document.getElementById('editInventoryModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    // Reset form and clear preview
    document.getElementById('editInventoryForm').reset();
    document.getElementById('editImagePreviewContainer').classList.add('hidden');
}

// Image preview function for edit modal
function previewEditImage(input) {
    const previewContainer = document.getElementById('editImagePreviewContainer');
    const preview = document.getElementById('editImagePreview');
    
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

// Add event listener for edit buttons (delegated to handle dynamic content)
document.addEventListener('click', function(e) {
    if (e.target.closest('.edit-btn')) {
        const inventoryId = e.target.closest('.edit-btn').getAttribute('data-id');
        openEditInventoryModal(inventoryId);
    }
});

// Form submission handler for edit
document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.getElementById('editInventoryForm');
    if (editForm) {
        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const submitBtn = document.getElementById('submitEditInventoryBtn');
            
            if (!submitBtn) {
                console.error('Submit button not found');
                return;
            }
            
            const originalBtnText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
            
            try {
                const response = await fetch('inventory/update_inventory.php', {
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
                    
                    closeEditInventoryModal();
                    loadPage(<?php echo $branch_id; ?>, 1);
                } else {
                    throw new Error(data.message || 'Failed to update item');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'An error occurred while updating the item',
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

//functions to archive item in inventory
// Function to handle archive confirmation
function confirmArchive(inventoryId, itemName) {
    Swal.fire({
        title: 'Archive Inventory Item',
        html: `Are you sure you want to archive <strong>${itemName}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, archive it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            archiveInventoryItem(inventoryId);
        }
    });
}

// Function to archive inventory item
async function archiveInventoryItem(inventoryId) {
    try {
        const response = await fetch('inventory/archive_inventory.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ inventory_id: inventoryId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Archived!',
                text: data.message,
                showConfirmButton: false,
                timer: 1500
            });
            
            // Reload the current page to reflect changes
            loadPage(<?php echo $branch_id; ?>, 1);
        } else {
            throw new Error(data.message || 'Failed to archive item');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'An error occurred while archiving the item',
        });
    }
}

// Add event listener for archive buttons (delegated to handle dynamic content)
document.addEventListener('click', function(e) {
    if (e.target.closest('.archive-btn')) {
        const btn = e.target.closest('.archive-btn');
        const inventoryId = btn.getAttribute('data-id');
        const itemName = btn.getAttribute('data-name');
        confirmArchive(inventoryId, itemName);
    }
});


//Unarchived functions


// Debounce function to limit search execution frequency
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Search archived items (client-side)
function searchArchivedItems() {
    const searchInput = document.getElementById('archivedItemsSearch');
    const searchTerm = searchInput.value.toLowerCase();
    const container = document.getElementById('archivedItemsContent');
    const rows = container.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const cells = row.cells;
        if (cells.length < 5) return; // Skip if not a data row
        
        const itemName = cells[1].textContent.toLowerCase();
        const itemCategory = cells[2].textContent.toLowerCase();
        const itemId = cells[0].textContent.toLowerCase();
        
        if (itemName.includes(searchTerm) || 
            itemCategory.includes(searchTerm) || 
            itemId.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Clear search functionality
function setupArchivedSearchClear() {
    const searchInput = document.getElementById('archivedItemsSearch');
    const clearBtn = document.getElementById('clearArchivedSearch');
    
    searchInput.addEventListener('input', function() {
        clearBtn.style.display = this.value ? '' : 'none';
    });
    
    clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        this.style.display = 'none';
        // Reset the display of all rows
        const rows = document.querySelectorAll('#archivedItemsContent tbody tr');
        rows.forEach(row => row.style.display = '');
    });
}


// Function to open archived items modal
function openArchivedModal() {
    const modal = document.getElementById('archivedItemsModal');
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    
    // Load archived items
    loadArchivedItems();
    
    // Set up search functionality
    document.getElementById('archivedItemsSearch').addEventListener('input', 
        debounce(searchArchivedItems, 300));
    
    // Set up clear button
    setupArchivedSearchClear();
}

// Function to close archived items modal
function closeArchivedModal() {
    document.getElementById('archivedItemsModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

// Function to load archived items via AJAX
async function loadArchivedItems() {
    try {
        const response = await fetch('inventory/get_archived_items.php?branch_id=<?php echo $branch_id; ?>');
        const data = await response.json();
        
        if (data.success) {
            renderArchivedItems(data.items);
        } else {
            throw new Error(data.message || 'Failed to load archived items');
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('archivedItemsContent').innerHTML = `
            <div class="text-center py-10 text-gray-500">
                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                <p>${error.message || 'Failed to load archived items'}</p>
            </div>
        `;
    }
}

// Function to render archived items in the modal
function renderArchivedItems(items) {
    const container = document.getElementById('archivedItemsContent');
    const searchTerm = document.getElementById('archivedItemsSearch').value.toLowerCase();
    
    if (!items || items.length === 0) {
        container.innerHTML = `
            <div class="text-center py-10 text-gray-500">
                <i class="fas fa-box-open text-3xl mb-3"></i>
                <p>No archived items found</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
    `;
    
    items.forEach(item => {
        // Safely format price
        const price = typeof item.price === 'number' ? item.price : 
                     typeof item.price === 'string' ? parseFloat(item.price) : 0;
        const formattedPrice = isNaN(price) ? '0.00' : price.toFixed(2);
        
        // Check if item matches current search term
        const matchesSearch = searchTerm === '' || 
                            item.item_name.toLowerCase().includes(searchTerm) ||
                            (item.category && item.category.toLowerCase().includes(searchTerm)) ||
                            item.inventory_id.toString().includes(searchTerm);
        
        html += `
            <tr ${matchesSearch ? '' : 'style="display: none"'}>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">#INV-${item.inventory_id}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">${escapeHtml(item.item_name)}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${escapeHtml(item.category)}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${item.quantity || 0}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">₱${formattedPrice}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    <button class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all unarchive-btn" 
                            data-id="${item.inventory_id}" data-name="${escapeHtml(item.item_name)}">
                        <i class="fas fa-undo"></i> Unarchive
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
    
    // Add event listeners for unarchive buttons
    document.querySelectorAll('.unarchive-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const inventoryId = this.getAttribute('data-id');
            const itemName = this.getAttribute('data-name');
            confirmUnarchive(inventoryId, itemName);
        });
    });
}

// Function to confirm unarchive
function confirmUnarchive(inventoryId, itemName) {
    Swal.fire({
        title: 'Unarchive Inventory Item',
        html: `Are you sure you want to unarchive <strong>${itemName}</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, unarchive it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            unarchiveInventoryItem(inventoryId);
        }
    });
}

// Function to unarchive inventory item
async function unarchiveInventoryItem(inventoryId) {
    try {
        const response = await fetch('inventory/unarchive_inventory.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ inventory_id: inventoryId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Unarchived!',
                text: data.message,
                showConfirmButton: false,
                timer: 1500
            });
            
            // Reload the archived items list
            loadArchivedItems();
            // Refresh the main inventory table
            loadPage(<?php echo $branch_id; ?>, 1);
        } else {
            throw new Error(data.message || 'Failed to unarchive item');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'An error occurred while unarchiving the item',
        });
    }
}

// Helper function to escape HTML
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

//searchbar functions
// Function to perform the search
function performInventorySearch(searchTerm, branchId, page = 1) {
    const loadingIndicator = document.getElementById(`loadingIndicator${branchId}`);
    const inventoryTable = document.getElementById(`inventoryTable_${branchId}`);
    
    // Show loading indicator
    loadingIndicator.classList.remove('hidden');
    
    // Make AJAX request to search endpoint
    fetch(`inventory/search_inventory.php?search=${encodeURIComponent(searchTerm)}&branch_id=${branchId}&page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update table with search results
                inventoryTable.innerHTML = data.rows;
                
                // Update pagination info if available
                if (data.paginationInfo) {
                    document.getElementById(`paginationInfo_${branchId}`).textContent = data.paginationInfo;
                }
                
                // Update pagination links if available
                if (data.paginationLinks) {
                    document.getElementById(`paginationLinks_${branchId}`).innerHTML = data.paginationLinks;
                }
            } else {
                throw new Error(data.message || 'Search failed');
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            inventoryTable.innerHTML = `
                <tr>
                    <td colspan="7" class="p-6 text-sm text-center text-red-500">
                        Error: ${error.message || 'Failed to perform search'}
                    </td>
                </tr>
            `;
        })
        .finally(() => {
            loadingIndicator.classList.add('hidden');
        });
}

// Function to handle search input
function setupInventorySearch(branchId) {
    const searchInput = document.getElementById('inventorySearch');
    const clearBtn = document.getElementById('clearSearch');
    
    // Debounced search function
    const debouncedSearch = debounce((searchTerm) => {
        if (searchTerm.length > 0) {
            performInventorySearch(searchTerm, branchId);
        } else {
            // If search is empty, load the regular page
            loadPage(branchId, 1);
        }
    }, 300);
    
    // Search input event
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        
        // Show/hide clear button
        clearBtn.style.display = searchTerm.length > 0 ? '' : 'none';
        
        // Only search if there are at least 2 characters or empty
        if (searchTerm.length > 1 || searchTerm.length === 0) {
            debouncedSearch(searchTerm);
        }
    });
    
    // Clear search button
    clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        clearBtn.style.display = 'none';
        loadPage(branchId, 1);
    });
    
    // Also trigger search on Enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const searchTerm = this.value.trim();
            if (searchTerm.length > 0) {
                performInventorySearch(searchTerm, branchId);
            }
        }
    });
}

// Initialize the search when the page loads
document.addEventListener('DOMContentLoaded', function() {
    setupInventorySearch(<?php echo $branch_id; ?>);
});


// Toggle filter dropdown
function setupFilterDropdowns(branchId) {
    // Handle both mobile and desktop filter buttons
    const filterButtons = [
        document.getElementById(`filterButton_${branchId}`), // Mobile
        document.getElementById(`filterButton_mobile${branchId}`) // Desktop
    ];

    const filterDropdowns = [
        document.getElementById(`filterDropdown_${branchId}`), // Mobile
        document.getElementById(`filterDropdown_mobile${branchId}`) // Desktop
    ];

    filterButtons.forEach((button, index) => {
        if (button) {
            button.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent closing immediately
                const dropdown = filterDropdowns[index];
                if (dropdown) {
                    // Toggle visibility
                    dropdown.classList.toggle('hidden');
                    // Update filter indicator
                    const indicator = document.getElementById(`filterIndicator_${branchId}`);
                    if (indicator) {
                        indicator.classList.toggle('hidden', dropdown.classList.contains('hidden'));
                    }
                }
            });
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        filterDropdowns.forEach(dropdown => {
            if (dropdown && !dropdown.contains(e.target) && !filterButtons.some(btn => btn && btn.contains(e.target))) {
                dropdown.classList.add('hidden');
                // Update filter indicators
                const indicator = document.getElementById(`filterIndicator_${branchId}`);
                if (indicator) {
                    indicator.classList.add('hidden');
                }
            }
        });
    });

    // Handle filter option clicks
    document.querySelectorAll(`.filter-option[data-branch="${branchId}"]`).forEach(option => {
        option.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent dropdown from closing
            const sortType = this.getAttribute('data-sort');
            applyFilter(branchId, sortType);
            // Close dropdown after selection
            filterDropdowns.forEach(dropdown => {
                if (dropdown) {
                    dropdown.classList.add('hidden');
                }
            });
            // Update filter indicator
            const indicator = document.getElementById(`filterIndicator_${branchId}`);
            if (indicator) {
                indicator.classList.remove('hidden');
            }
        });
    });
}

// Apply filter and reload table
function applyFilter(branchId, sortType) {
    // Update the loadPage function to include sorting
    loadPage(branchId, 1, sortType);
}

// Modified loadPage function to handle sorting
function loadPage(branchId, page, sortType = 'default') {
    const tableContainer = document.getElementById(`tableContainer${branchId}`);
    const loadingIndicator = document.getElementById(`loadingIndicator${branchId}`);
    const inventoryTable = document.getElementById(`inventoryTable_${branchId}`);
    const paginationInfo = document.getElementById(`paginationInfo_${branchId}`);
    const paginationLinks = document.getElementById(`paginationLinks_${branchId}`);

    // Show loading indicator
    loadingIndicator.classList.remove('hidden');
    tableContainer.style.opacity = '0.5';

    // Construct the URL with sort parameter
    let url = `employee_inventory.php?ajax=1&page=${page}&branch_id=${branchId}`;
    if (sortType !== 'default') {
        url += `&sort=${sortType}`;
    }

    // Make AJAX request
    fetch(url)
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
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load inventory data',
            });
        });
}

// Initialize filter dropdowns when the page loads
document.addEventListener('DOMContentLoaded', function() {
    setupFilterDropdowns(<?php echo $branch_id; ?>);
    loadPage(<?php echo $branch_id; ?>, 1);
});

</script>
  <script src="sidebar.js"></script>
  <script src="tailwind.js"></script>
</body>
</html>