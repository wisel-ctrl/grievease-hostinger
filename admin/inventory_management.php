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

function generateInventoryRow($row) {
    $html = '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">';
    $html .= '<td class="p-4 text-sm text-sidebar-text font-medium">#INV-' . str_pad($row["inventory_id"], 3, '0', STR_PAD_LEFT) . '</td>';
    $html .= '<td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($row["item_name"]) . '</td>';
    $html .= '<td class="p-4 text-sm text-sidebar-text">';
    $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">';
    $html .= htmlspecialchars($row["category_name"]) . '</span>';
    $html .= '</td>';
    $html .= '<td class="p-4 text-sm text-sidebar-text" data-sort-value="' . $row["quantity"] . '">' . $row["quantity"] . '</td>';
    $html .= '<td class="p-4 text-sm font-medium text-sidebar-text" data-sort-value="' . $row["price"] . '">₱' . number_format($row["price"], 2) . '</td>';
    $html .= '<td class="p-4 text-sm font-medium text-sidebar-text" data-sort-value="' . $row["total_value"] . '">₱' . number_format($row["total_value"], 2) . '</td>';
    $html .= '<td class="p-4 text-sm">';
    $html .= '<div class="flex space-x-2">';
    $html .= '<button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" title="Edit Item" onclick="openViewItemModal(' . $row["inventory_id"] . ')">';
    $html .= '<i class="fas fa-edit"></i>';
    $html .= '</button>';
    $html .= '<form method="POST" action="inventory/delete_inventory_item.php" onsubmit="return false;" style="display:inline;" class="delete-form">';
    $html .= '<input type="hidden" name="inventory_id" value="' . $row["inventory_id"] . '">';
    $html .= '<button type="submit" class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip" title="Archive Item">';
    $html .= '<i class="fas fa-archive"></i>';
    $html .= '</button>';
    $html .= '</form>';
    $html .= '</div>';
    $html .= '</td>';
    $html .= '</tr>';
    
    return $html;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Inventory</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <!-- Include SweetAlert2 CSS and JS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>


  
</head>
<body class="flex bg-gray-50">

<?php include 'admin_sidebar.php'; ?>


<!-- Main Content -->
<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
  <!-- Header -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Inventory Management</h1>
    </div>
  </div>

  <!-- Inventory Overview Cards -->
  <!-- Inventory Overview Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <?php
    // Get total items count
    $totalItemsQuery = "SELECT COUNT(*) as total_items FROM inventory_tb WHERE status = 1";
    $totalItemsResult = $conn->query($totalItemsQuery);
    $totalItems = $totalItemsResult->fetch_assoc()['total_items'];
    
    // Get total inventory value
    $totalValueQuery = "SELECT SUM(quantity * price) as total_value FROM inventory_tb WHERE status = 1";
    $totalValueResult = $conn->query($totalValueQuery);
    $totalValue = $totalValueResult->fetch_assoc()['total_value'];
    
    // Get low stock items (let's define low stock as quantity < 5)
    $lowStockQuery = "SELECT COUNT(*) as low_stock FROM inventory_tb WHERE quantity < 5 AND status = 1";
    $lowStockResult = $conn->query($lowStockQuery);
    $lowStock = $lowStockResult->fetch_assoc()['low_stock'];
    
    // Calculate turnover rate (simplified - could be based on historical data)
    // This is a placeholder - you might want to calculate this differently
    $turnoverQuery = "SELECT 
                        (SUM(CASE WHEN quantity < 10 THEN 1 ELSE 0 END) / COUNT(*)) * 100 as turnover_rate 
                      FROM inventory_tb 
                      WHERE status = 1";
    $turnoverResult = $conn->query($turnoverQuery);
    $turnoverRate = $turnoverResult->fetch_assoc()['turnover_rate'];
    $turnoverRate = number_format($turnoverRate, 1);
    
    // Get comparison data from last month (simplified)
    $lastMonthQuery = "SELECT 
                        COUNT(*) as last_month_items,
                        SUM(quantity * price) as last_month_value,
                        SUM(CASE WHEN quantity < 5 THEN 1 ELSE 0 END) as last_month_low_stock
                      FROM inventory_tb 
                      WHERE status = 1 
                      AND updated_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 2 MONTH) 
                      AND updated_at < DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)";
    $lastMonthResult = $conn->query($lastMonthQuery);
    $lastMonthData = $lastMonthResult->fetch_assoc();
    
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
            'change' => 3, // Hardcoded in original
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
  

  <!-- Inventory Charts -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
  <?php
// Fetch inventory data by category
$categoryQuery = "SELECT 
                    c.category_name, 
                    COUNT(i.inventory_id) as item_count
                  FROM inventory_category c
                  LEFT JOIN inventory_tb i ON c.category_id = i.category_id AND i.status = 1
                  GROUP BY c.category_name
                  ORDER BY item_count DESC";
$categoryResult = $conn->query($categoryQuery);

$categoryLabels = [];
$categoryCounts = [];
$categoryColors = ['#008080', '#E76F51', '#4caf50', '#ff9800', '#9c27b0', '#607d8b']; // Add more colors if needed

while ($row = $categoryResult->fetch_assoc()) {
    $categoryLabels[] = $row['category_name'];
    $categoryCounts[] = $row['item_count'];
    // You could also use $row['total_quantity'] or $row['total_value'] depending on what you want to show
}
?>
    <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300">
      <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
        <h3 class="font-medium text-sidebar-text">Inventory by Category</h3>
        <button class="px-3 py-2 border border-sidebar-border rounded-md text-sm flex items-center text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
          <i class="fas fa-download mr-2"></i> Export
        </button>
      </div>
      <div class="p-5">
        <canvas id="inventoryCategoryChart" class="h-64"></canvas>
      </div>
    </div>

    <?php
// Fetch inventory value trends data
$currentDate = new DateTime();
$monthLabels = [];
$monthValues = [];

// Generate labels for the last 6 months
for ($i = 5; $i >= 0; $i--) {
    $date = clone $currentDate;
    $date->modify("-$i months");
    $monthLabels[] = $date->format('M'); // Short month name (Jan, Feb, etc.)
    $monthKeys[] = $date->format('Y-m'); // For database comparison
}

// Get data from database
$trendQuery = "SELECT 
                DATE_FORMAT(updated_at, '%Y-%m') as month,
                SUM(quantity * price) as monthly_value
              FROM inventory_tb
              WHERE status = 1
              AND updated_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
              GROUP BY DATE_FORMAT(updated_at, '%Y-%m')";
$trendResult = $conn->query($trendQuery);

// Initialize all values to 0
$monthValues = array_fill(0, 6, 0);

// Fill in actual values where data exists
if ($trendResult) {
    $dbData = [];
    while ($row = $trendResult->fetch_assoc()) {
        $dbData[$row['month']] = $row['monthly_value'];
    }
    
    // Match database data with our month labels
    foreach ($monthKeys as $index => $monthKey) {
        if (isset($dbData[$monthKey])) {
            $monthValues[$index] = $dbData[$monthKey];
        }
    }
}
?>
    <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300">
      <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
        <h3 class="font-medium text-sidebar-text">Inventory Value Trends</h3>
        <select id="valueTrendPeriod" class="p-2 border border-sidebar-border rounded-md text-sm text-sidebar-text focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
          <option value="month">Last 12 Months</option>
          <option value="year">Last 5 Years</option>
        </select>
      </div>
      <div class="p-5">
        <canvas id="inventoryValueChart" class="h-64"></canvas>
      </div>
    </div>
  </div>

  <!-- Additional Charts -->
  

  <?php
include '../db_connect.php';
// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// First, get all branches
$branchQuery = "SELECT branch_id, branch_name FROM branch_tb";
$branchResult = $conn->query($branchQuery);

// Check if we have branches
if ($branchResult->num_rows > 0) {
  // Loop through each branch
  while($branch = $branchResult->fetch_assoc()) {
    $branchId = $branch["branch_id"];
    $branchName = $branch["branch_name"];
    
    // Modified SQL query with status filter
      $sql = "SELECT 
      i.inventory_id, 
      i.item_name, 
      c.category_name, 
      i.quantity, 
      i.price, 
      (i.quantity * i.price) AS total_value
      FROM inventory_tb i
      JOIN inventory_category c ON i.category_id = c.category_id
      WHERE i.branch_id = $branchId AND i.status = 1";

    $result = $conn->query($sql);

    // Calculate pagination for this branch
    $itemsPerPage = 5;
    $totalItems = $result->num_rows;
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = isset($_GET['page_'.$branchId]) ? (int)$_GET['page_'.$branchId] : 1;
    $startItem = ($currentPage - 1) * $itemsPerPage;

    // Modify query to include pagination
    $paginatedSql = $sql . " LIMIT $startItem, $itemsPerPage";
    $paginatedResult = $conn->query($paginatedSql);
    ?>
    
    <div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden branch-container" 
     data-branch-id="<?php echo $branchId; ?>" 
     data-total-items="<?php echo $totalItems; ?>">
  <!-- Branch Header with Search and Filters -->
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
    <!-- Desktop layout for big screens - Title on left, controls on right -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
      <!-- Title and Counter -->
      <div class="flex items-center gap-3 mb-4 lg:mb-0">
        <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap" id="branchTitle_<?php echo $branchId; ?>">
          <?php echo htmlspecialchars(ucwords($branchName)); ?> - Inventory Items
        </h4>
        
        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
          <i class="fas fa-box"></i>
          <?php echo $totalItems . ($totalItems != 1 ? "" : ""); ?>
        </span>
      </div>
      
      <!-- Controls for big screens - aligned right -->
      <div class="hidden lg:flex items-center gap-3">
        <!-- Search Input -->
        <div class="relative">
          <input type="text" id="searchBox_<?php echo $branchId; ?>" 
                placeholder="Search items..." 
                class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
          <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
        </div>

        <!-- Filter Dropdown -->
        <div class="relative filter-dropdown">
          <button id="filterButton_<?php echo $branchId; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
            <i class="fas fa-filter text-sidebar-accent"></i>
            <span>Filters</span>
            <span id="filterIndicator_<?php echo $branchId; ?>" class="hidden h-2 w-2 bg-sidebar-accent rounded-full"></span>
          </button>
          
          <!-- Filter Window -->
          <div id="filterDropdown_<?php echo $branchId; ?>" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
            <div class="space-y-4">
              <!-- Sort Options -->
              <div>
                <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                <div class="space-y-1">
                  <div class="flex items-center cursor-pointer">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full" data-sort="default" data-branch="<?php echo $branchId; ?>">
                      Default (Unsorted)
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full" data-sort="price_asc" data-branch="<?php echo $branchId; ?>">
                      Price: Low to High
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full" data-sort="price_desc" data-branch="<?php echo $branchId; ?>">
                      Price: High to Low
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full" data-sort="quantity_asc" data-branch="<?php echo $branchId; ?>">
                      Quantity: Low to High
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full" data-sort="quantity_desc" data-branch="<?php echo $branchId; ?>">
                      Quantity: High to Low
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full" data-sort="name_asc" data-branch="<?php echo $branchId; ?>">
                      Name: A to Z
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full" data-sort="name_desc" data-branch="<?php echo $branchId; ?>">
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
                onclick="showArchivedItems(<?php echo $branchId; ?>)">
          <i class="fas fa-archive text-sidebar-accent"></i>
          <span>Archived</span>
        </button>

        <!-- Add Item Button -->
        <button class="px-4 py-2 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap"
                onclick="openAddInventoryModal(<?php echo $branchId; ?>)"> Add Item
        </button>
      </div>
    </div>
    
    <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
    <div class="lg:hidden w-full mt-4">
      <!-- First row: Search bar with filter and archive icons on the right -->
      <div class="flex items-center w-full gap-3 mb-4">
        <!-- Search Input - Takes most of the space -->
        <div class="relative flex-grow">
          <input type="text" id="searchBox_<?php echo $branchId; ?>" 
                  placeholder="Search items..." 
                  class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
          <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>

        <!-- Icon-only buttons for filter and archive -->
        <div class="flex items-center gap-3">
          <!-- Filter Icon Button -->
          <div class="relative filter-dropdown">
            <button id="filterButton_<?php echo $branchId; ?>" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
              <i class="fas fa-filter text-xl"></i>
              <span id="filterIndicator_<?php echo $branchId; ?>" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
            </button>
            
            <!-- Filter Window - Positioned below the icon -->
            <div id="filterDropdown_<?php echo $branchId; ?>" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
              <div class="space-y-4">
                <!-- Sort Options -->
                <div>
                  <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                  <div class="space-y-2">
                    <div class="flex items-center cursor-pointer">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full" data-sort="default" data-branch="<?php echo $branchId; ?>">
                        Default (Unsorted)
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full" data-sort="price_asc" data-branch="<?php echo $branchId; ?>">
                        Price: Low to High
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full" data-sort="price_desc" data-branch="<?php echo $branchId; ?>">
                        Price: High to Low
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full" data-sort="quantity_asc" data-branch="<?php echo $branchId; ?>">
                        Quantity: Low to High
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full" data-sort="quantity_desc" data-branch="<?php echo $branchId; ?>">
                        Quantity: High to Low
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full" data-sort="name_asc" data-branch="<?php echo $branchId; ?>">
                        Name: A to Z
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full" data-sort="name_desc" data-branch="<?php echo $branchId; ?>">
                        Name: Z to A
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Archive Icon Button -->
          <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent" onclick="showArchivedItems(<?php echo $branchId; ?>)">
            <i class="fas fa-archive text-xl"></i>
          </button>
        </div>
      </div>

      <!-- Second row: Add Item Button - Full width -->
      <div class="w-full">
        <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap w-full justify-center" 
                onclick="openAddInventoryModal(<?php echo $branchId; ?>)"> Add Item
        </button>
      </div>
    </div>
  </div>
  
  <!-- Responsive Table Container with improved spacing -->
  <div class="overflow-x-auto scrollbar-thin relative" id="tableContainer<?php echo $branchId; ?>">
    <div id="loadingIndicator<?php echo $branchId; ?>" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center z-10">
      <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-sidebar-accent"></div>
    </div>

    <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
    <div class="min-w-full">
      <table class="w-full">
        <thead>
          <tr class="bg-gray-50 border-b border-sidebar-border">
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 0)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-hashtag text-sidebar-accent"></i> ID 
                 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 1)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-box text-sidebar-accent"></i> Item Name 
                 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 2)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-th-list text-sidebar-accent"></i> Category 
                 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 3)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-cubes text-sidebar-accent"></i> Quantity 
                 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 4)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-tag text-sidebar-accent"></i> Unit Price 
                 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 5)">
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
            <tbody id="inventoryTable_<?php echo $branchId; ?>">
              <?php
              if ($paginatedResult->num_rows > 0) {
                  while($row = $paginatedResult->fetch_assoc()) {
                      echo generateInventoryRow($row);
                  }
              } else {
                  echo '<tr>';
                  echo '<td colspan="7" class="p-6 text-sm text-center">';
                  echo '<div class="flex flex-col items-center">';
                  echo '<i class="fas fa-box-open text-gray-300 text-4xl mb-3"></i>';
                  echo '<p class="text-gray-500">No inventory items found for this branch</p>';
                  echo '</div>';
                  echo '</td>';
                  echo '</tr>';
              }
              ?>
            </tbody>
        </table>
    </div>
  </div>
  
  <!-- Sticky Pagination Footer with improved spacing -->
    <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
        <div id="paginationInfo_<?php echo $branchId; ?>" class="text-sm text-gray-500 text-center sm:text-left">
            Showing <?php echo min(($currentPage - 1) * $itemsPerPage + 1, $totalItems) . ' - ' . min($currentPage * $itemsPerPage, $totalItems); ?> 
            of <?php echo $totalItems; ?> items
        </div>
        <div class="flex space-x-2" id="paginationControls_<?php echo $branchId; ?>">
            <!-- Previous Button -->
            <button onclick="loadPage(<?php echo $branchId; ?>, <?php echo $currentPage - 1; ?>)" 
                    class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($currentPage <= 1) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                    <?php echo ($currentPage <= 1) ? 'disabled' : ''; ?>>
                &laquo;
            </button>
            
            <!-- Page Numbers -->
            <?php 
            // Calculate page range to display (max 5 pages)
            $startPage = max(1, $currentPage - 2);
            $endPage = min($totalPages, $currentPage + 2);
            
            // Adjust if we're at the beginning
            if ($currentPage <= 3) {
                $endPage = min(5, $totalPages);
            }
            // Adjust if we're at the end
            if ($currentPage >= $totalPages - 2) {
                $startPage = max(1, $totalPages - 4);
            }
            
            for ($i = $startPage; $i <= $endPage; $i++): ?>
                <button onclick="loadPage(<?php echo $branchId; ?>, <?php echo $i; ?>)" 
                        class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($i == $currentPage) ? 'bg-sidebar-accent text-white' : ''; ?>">
                    <?php echo $i; ?>
                </button>
            <?php endfor; ?>
            
            <!-- Next Button -->
            <button onclick="loadPage(<?php echo $branchId; ?>, <?php echo $currentPage + 1; ?>)" 
                    class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($currentPage >= $totalPages) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                    <?php echo ($currentPage >= $totalPages) ? 'disabled' : ''; ?>>
                &raquo;
            </button>
        </div>
    </div>
</div> 
 
    
    <?php
        }
    } else {
        echo '<div class="p-4 bg-red-100 text-red-700 rounded-lg">No branches found in the database.</div>';
    }

    // Close connection
    $conn->close();
    ?>
</div>

<!-- View Item Modal -->
<div id="viewItemModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeViewItemModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Inventory Item Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5" id="itemDetailsContent">
      <!-- Item details will be loaded here via AJAX -->
      <div class="flex justify-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-sidebar-accent"></div>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeViewItemModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="saveItemChanges()">
        Save Changes
      </button>
    </div>
  </div>
</div>


<!-- Add Inventory Modal -->
<div id="addInventoryModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeAddInventoryModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Add New Inventory Item
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <form id="addInventoryForm" class="space-y-3 sm:space-y-4">
        <!-- Item Name -->
        <div>
          <label for="itemName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Item Name <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="text" id="itemName" name="itemName" required 
                   class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                   placeholder="Item Name"
                   oninput="validateItemName(this)">
            <div id="itemNameError" class="text-red-500 text-xs mt-1 hidden">Item name cannot start with space or have consecutive spaces</div>
          </div>
        </div>
        
        <!-- Category -->
        <div>
          <label for="category" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Category <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <select id="category" name="category" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
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

        <!-- Branch -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border-l-4 border-gold">
          <label class="block text-xs font-medium text-gray-700 mb-2">Branch <span class="text-red-500">*</span></label>
          <div class="flex flex-wrap gap-3 sm:gap-4">
            <?php
            // Include database connection
            include '../db_connect.php';

            // Fetch branches from the database
            $sql = "SELECT branch_id, branch_name FROM branch_tb";
            $result = $conn->query($sql);
            
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '<label class="flex items-center space-x-2 cursor-pointer">';
                    echo '<input type="radio" name="branch" value="' . $row['branch_id'] . '" required class="hidden peer">';
                    echo '<div class="w-5 h-5 rounded-full border-2 border-gold flex items-center justify-center peer-checked:bg-gold peer-checked:border-darkgold transition-colors"></div>';
                    echo '<span class="text-gray-700 font-medium">' . htmlspecialchars($row['branch_name']) . '</span>';
                    echo '</label>';
                }
            } else {
                echo '<p class="text-gray-500">No branches available.</p>';
            }
            ?>
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
                   placeholder="0.00"
                   oninput="validateUnitPrice(this)">
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
      </form>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeAddInventoryModal()">
        Cancel
      </button>
      <button type="submit" form="addInventoryForm" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
        Add Item
      </button>
    </div>
  </div>
</div>

<script>
// Item Name Validation
function validateItemName(input) {
  const errorElement = document.getElementById('itemNameError');
  let value = input.value;
  
  // Check if first character is space
  if (value.length > 0 && value.charAt(0) === ' ') {
    errorElement.classList.remove('hidden');
    input.value = value.trim();
    return;
  }
  
  // Check for consecutive spaces
  if (value.includes('  ')) {
    errorElement.classList.remove('hidden');
    input.value = value.replace(/\s+/g, ' ');
    return;
  }
  
  errorElement.classList.add('hidden');
  
  // Auto-capitalize first letter
  if (value.length === 1) {
    input.value = value.charAt(0).toUpperCase() + value.slice(1);
  }
}

// Unit Price Validation
function validateUnitPrice(input) {
  if (input.value < 0) {
    input.value = '';
  }
}

// Image Preview Functionality
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
    previewContainer.classList.add('hidden');
    preview.src = '#';
  }
}

// Form submission validation
document.getElementById('addInventoryForm').addEventListener('submit', function(e) {
  const itemName = document.getElementById('itemName').value;
  
  // Final validation for item name
  if (itemName.trim() === '' || itemName.charAt(0) === ' ' || itemName.includes('  ')) {
    e.preventDefault();
    document.getElementById('itemNameError').classList.remove('hidden');
    document.getElementById('itemName').focus();
  }
});
</script>

<!-- Edit Inventory Modal -->

<div id="editInventoryModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeEditInventoryModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Edit Inventory Item
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <form id="editInventoryForm" class="space-y-3 sm:space-y-4">
        <div>
          <label for="editItemId" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Item ID
          </label>
          <input type="text" id="editItemId" name="editItemId" value="<?php echo $item_id; ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed" readonly>
        </div>

        <div>
          <label for="editItemName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Item Name <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="text" id="editItemName" name="editItemName" value="<?php echo $item_name; ?>" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Item Name">
          </div>
        </div>

        <!-- Category Dropdown -->
        <div>
          <label for="category" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Category <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <select id="category" name="category" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              <option value="" disabled>Select a Category</option>
              <?php
              // Fetch categories again
              include '../db_connect.php';
              $sql = "SELECT category_id, category_name FROM inventory_category";
              $result = $conn->query($sql);
              if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                      $selected = ($row['category_id'] == $category_id) ? 'selected' : '';
                      echo '<option value="' . $row['category_id'] . '" ' . $selected . '>' . htmlspecialchars($row['category_name']) . '</option>';
                  }
              } else {
                  echo '<option value="" disabled>No Categories Available</option>';
              }
              ?>
            </select>
          </div>
        </div>

        <div>
          <label for="editQuantity" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Quantity <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="number" id="editQuantity" name="editQuantity" value="<?php echo $quantity; ?>" min="1" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Quantity">
          </div>
        </div>

        <div>
          <label for="editUnitPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Unit Price <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="editUnitPrice" name="editUnitPrice" value="<?php echo $unit_price; ?>" step="0.01" required class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="0.00">
          </div>
        </div>

        <!-- Current Image Preview -->
        <div class="bg-navy p-3 sm:p-4 rounded-lg">
          <div class="flex flex-col items-center space-y-2 sm:space-y-3">
            <div class="w-full h-32 bg-center bg-cover rounded-lg shadow-md" style="background-image: url('<?php echo $inventory_img; ?>');"></div>
            <span class="text-xs sm:text-sm text-gray-600">Current Image</span>
          </div>
        </div>

        <!-- File Upload -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
          <label for="editItemImage" class="block text-xs font-medium text-gray-700 mb-2 sm:mb-3 flex items-center">Upload New Image</label>
          <div class="relative">
            <input type="file" id="editItemImage" name="editItemImage" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
            <div class="w-full px-3 py-2 border border-dashed border-gray-300 rounded-lg bg-gray-50 text-gray-500 text-xs sm:text-sm flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                <polyline points="17 8 12 3 7 8"></polyline>
                <line x1="12" y1="3" x2="12" y2="15"></line>
              </svg>
              Choose file or drag here
            </div>
          </div>
        </div>
      </form>
    </div>
  
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeEditInventoryModal()">
        Cancel
      </button>
      <button type="submit" form="editInventoryForm" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
          <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
          <polyline points="17 21 17 13 7 13 7 21"></polyline>
          <polyline points="7 3 7 8 15 8"></polyline>
        </svg>
        Save Changes
      </button>
    </div>
  </div>
</div>

<!-- Archived Items Modal -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="archivedItemsModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-3xl mx-4 z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeArchivedModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        <span id="archivedItemsTitle">Archived Items</span>
      </h3>
    </div>
    
    <!-- Search Bar -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <i class="fas fa-search text-gray-400"></i>
        </div>
        <input type="text" id="archivedItemsSearch" placeholder="Search archived items..." 
          class="w-full pl-10 pr-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
      </div>
    </div>
    
    <!-- Modal Body -->
    <div id="archivedItemsContent" class="px-6 py-5 max-h-[70vh] overflow-y-auto w-full">
      <!-- Content will be loaded here via AJAX -->
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-6 py-4 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center" onclick="closeArchivedModal()">
        Close
      </button>
    </div>
  </div>
</div>

<style>
  .border-gold { border-color: #d4af37; } /* Gold Border */
  .focus\:ring-gold:focus { box-shadow: 0 0 0 2px #d4af37; }
  .bg-gold { background-color: #d4af37; }
  .hover\:bg-darkgold:hover { background-color: #b8860b; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the data from PHP
    const categoryLabels = <?php echo json_encode($categoryLabels); ?>;
    const categoryData = <?php echo json_encode($categoryCounts); ?>;
    const categoryColors = <?php echo json_encode(array_slice($categoryColors, 0, count($categoryLabels))); ?>;

    // Create the chart
    const inventoryCategoryChart = new Chart(document.getElementById('inventoryCategoryChart'), {
        type: 'doughnut',
        data: {
            labels: categoryLabels,
            datasets: [{
                label: 'Inventory by Category',
                data: categoryData,
                backgroundColor: categoryColors,
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.raw + ' items';
                            return label;
                        }
                    }
                }
            }
        }
    });
});

// Update the loadPage function
function loadPage(branchId, page) {
    // Validate page number
    if (page < 1) page = 1;
    
    // Get total pages from the container's data attribute
    const container = document.querySelector(`.branch-container[data-branch-id="${branchId}"]`);
    const totalItems = parseInt(container.dataset.totalItems);
    const itemsPerPage = 5;
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (page > totalPages) page = totalPages;
    
    // Show loading indicator
    const loadingIndicator = document.getElementById(`loadingIndicator${branchId}`);
    const tableContainer = document.getElementById(`tableContainer${branchId}`);
    
    loadingIndicator.classList.remove('hidden');
    tableContainer.style.opacity = '0.5';
    
    // Make AJAX request
    fetch(`inventory/load_inventory_table.php?branch_id=${branchId}&page=${page}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text();
        })
        .then(data => {
            // Update the table content
            document.getElementById(`inventoryTable_${branchId}`).innerHTML = data;
            
            // Update URL without reloading
            const url = new URL(window.location);
            url.searchParams.set(`page_${branchId}`, page);
            window.history.pushState({ branchId, page }, '', url);
            
            // Update pagination info
            updatePaginationInfo(branchId, page, totalItems, itemsPerPage);
            
            // Update pagination controls
            updatePaginationControls(branchId, page, totalPages);
            
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
                text: 'Failed to load inventory data. Please try again.'
            });
        });
}


// Add this function to update pagination info text
function updatePaginationInfo(branchId, currentPage, totalItems, itemsPerPage) {
    const startItem = (currentPage - 1) * itemsPerPage + 1;
    const endItem = Math.min(currentPage * itemsPerPage, totalItems);
    
    document.getElementById(`paginationInfo_${branchId}`).textContent = 
        `Showing ${startItem} - ${endItem} of ${totalItems} items`;
}

function updatePaginationControls(branchId, currentPage, totalPages) {
    const paginationContainer = document.getElementById(`paginationControls_${branchId}`);
    if (!paginationContainer) return;
    
    // Update previous button
    const prevButton = paginationContainer.querySelector('button:first-child');
    if (prevButton) {
        prevButton.disabled = currentPage <= 1;
        prevButton.classList.toggle('opacity-50', currentPage <= 1);
        prevButton.classList.toggle('cursor-not-allowed', currentPage <= 1);
        prevButton.onclick = function() { loadPage(branchId, currentPage - 1); };
    }
    
    // Update next button
    const nextButton = paginationContainer.querySelector('button:last-child');
    if (nextButton) {
        nextButton.disabled = currentPage >= totalPages;
        nextButton.classList.toggle('opacity-50', currentPage >= totalPages);
        nextButton.classList.toggle('cursor-not-allowed', currentPage >= totalPages);
        nextButton.onclick = function() { loadPage(branchId, currentPage + 1); };
    }
    
    // Update page number buttons
    const pageButtons = paginationContainer.querySelectorAll('button:not(:first-child):not(:last-child)');
    pageButtons.forEach(button => {
        const pageNum = parseInt(button.textContent);
        button.classList.toggle('bg-sidebar-accent', pageNum === currentPage);
        button.classList.toggle('text-white', pageNum === currentPage);
        button.onclick = function() { loadPage(branchId, pageNum); };
    });
}

// Update the updatePaginationActiveState function
function updatePaginationActiveState(branchId, currentPage) {
    const paginationContainer = document.querySelector(`#paginationInfo_${branchId}`).nextElementSibling;
    if (!paginationContainer) return;
    
    const pageButtons = paginationContainer.querySelectorAll('button');
    const totalItems = parseInt(document.querySelector(`.branch-container[data-branch-id="${branchId}"]`).dataset.totalItems);
    const totalPages = Math.ceil(totalItems / 5);
    
    pageButtons.forEach(button => {
        // Remove active class from all buttons
        button.classList.remove('bg-sidebar-accent', 'text-white');
        button.classList.add('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
        
        // Check if this button is the current page
        const pageNumber = parseInt(button.textContent);
        if (!isNaN(pageNumber)) {
            if (pageNumber === currentPage) {
                button.classList.remove('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
                button.classList.add('bg-sidebar-accent', 'text-white');
            }
            
            // Disable page buttons that are out of range
            if (pageNumber < 1 || pageNumber > totalPages) {
                button.style.display = 'none';
            } else {
                button.style.display = '';
            }
        }
        
        // Enable/disable arrow buttons based on current page
        if (button.innerHTML === '&raquo;' || button.textContent === '»') {
            button.disabled = currentPage >= totalPages;
            if (button.disabled) {
                button.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                button.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        } else if (button.innerHTML === '&laquo;' || button.textContent === '«') {
            button.disabled = currentPage <= 1;
            if (button.disabled) {
                button.classList.add('opacity-50', 'cursor-not-allowed');
            } else {
                button.classList.remove('opacity-50', 'cursor-not-allowed');
            }
        }
    });
}

// Add this to handle browser back/forward navigation
window.addEventListener('popstate', function(event) {
    if (event.state) {
        // When navigating back/forward, reload the current page state
        const { branchId, page } = event.state;
        loadPage(branchId, page);
    } else {
        // Initial page load - load all branches with their current page
        document.querySelectorAll('.branch-container').forEach(container => {
            const branchId = container.dataset.branchId;
            const urlParams = new URLSearchParams(window.location.search);
            const currentPage = urlParams.get(`page_${branchId}`) || 1;
            loadPage(branchId, currentPage);
        });
    }
});

// Initialize pagination on page load
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.branch-container').forEach(container => {
        const branchId = container.dataset.branchId;
        const urlParams = new URLSearchParams(window.location.search);
        const currentPage = urlParams.get(`page_${branchId}`) || 1;
        
        // Store total items in the container's dataset
        const totalItems = <?php echo $totalItems; ?>;
        container.dataset.totalItems = totalItems;
        
        // Initialize pagination
        updatePaginationInfo(branchId, currentPage);
        updatePaginationActiveState(branchId, currentPage);
    });
});

// Add this function to reattach event listeners
function reattachEventListeners(branchId) {
    // Reattach click handlers for archive buttons
    document.querySelectorAll('.delete-form').forEach(form => {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            const formElement = this;
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, archive it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    formElement.submit();
                }
            });
        });
    });
    
    // Reattach click handlers for view item buttons
    document.querySelectorAll(`#inventoryTable_${branchId} button[onclick^="openViewItemModal"]`).forEach(button => {
        button.onclick = function() {
            const inventoryId = this.getAttribute('onclick').match(/openViewItemModal\((\d+)\)/)[1];
            openViewItemModal(inventoryId);
        };
    });
}


// Helper function to update pagination active state
function updatePaginationActiveState(branchId, currentPage) {
    const paginationContainer = document.querySelector(`#paginationInfo_${branchId}`).nextElementSibling;
    const pageButtons = paginationContainer.querySelectorAll('button');
    
    pageButtons.forEach(button => {
        // Remove active class from all buttons
        button.classList.remove('bg-sidebar-accent', 'text-white');
        button.classList.add('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
        
        // Check if this button is the current page
        const pageNumber = parseInt(button.textContent);
        if (!isNaN(pageNumber) && pageNumber === currentPage) {
            button.classList.remove('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
            button.classList.add('bg-sidebar-accent', 'text-white');
        }
    });
}
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the data from PHP
    const monthLabels = <?php echo json_encode($monthLabels); ?>;
    const monthValues = <?php echo json_encode($monthValues); ?>;
    
    // Create the chart
    const inventoryValueChart = new Chart(document.getElementById('inventoryValueChart'), {
        type: 'line',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'Inventory Value',
                data: monthValues,
                borderColor: '#008080',
                backgroundColor: 'rgba(0, 128, 128, 0.1)',
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#008080',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '₱' + context.raw.toLocaleString('en-PH', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString('en-PH');
                        }
                    }
                }
            }
        }
    });

    // Add event listener for period selector
    document.getElementById('valueTrendPeriod').addEventListener('change', function() {
        const period = this.value;
        fetchInventoryTrendData(period);
    });

    // Function to fetch data based on selected period
    function fetchInventoryTrendData(period) {
    fetch(`inventory/get_trend_data.php?period=${period}`)
        .then(response => response.json())
        .then(data => {
            // For quarters, you might want to format the labels differently
            if (period === 'quarter') {
                data.labels = data.labels.map(label => {
                    // Format as "Q1 2023" instead of "2023 Q1" if preferred
                    return label.replace(/(\d{4}) Q(\d)/, 'Q$2 $1');
                });
            }
            
            inventoryValueChart.data.labels = data.labels;
            inventoryValueChart.data.datasets[0].data = data.values;
            inventoryValueChart.update();
        })
        .catch(error => console.error('Error:', error));
}
});
</script>
  <script src="inventory_functions.js"></script>
  <script src="script.js"></script>
  <script src="tailwind.js"></script>
</body>
</html>