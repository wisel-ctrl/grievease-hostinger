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
  <title>Inventory - GrievEase</title>
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
    <div class="flex space-x-3">
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-bell"></i>
      </button>
       
    </div>
  </div>

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
    
    <div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden branch-container" data-branch-id="<?php echo $branchId; ?>">
    <!-- Branch Header with Search and Filters -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div class="flex items-center gap-3">
        <h4 class="text-lg font-bold text-sidebar-text" id="branchTitle_<?php echo $branchId; ?>">
            <?php echo htmlspecialchars(ucwords($branchName)); ?> - Inventory Items
        </h4>
        
        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
            <i class="fas fa-box"></i>
            <?php echo $totalItems . " Item" . ($totalItems != 1 ? "s" : ""); ?>
        </span>
    </div>

        
        <!-- Search and Filter Section -->
        <div class="flex flex-col md:flex-row items-start md:items-center gap-3 w-full md:w-auto">
            <!-- Search Input -->
            <div class="relative w-full md:w-64">
                <input type="text" id="searchBox_<?php echo $branchId; ?>" 
                       placeholder="Search items..." 
                       class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
            </div>

            <!-- Filter Dropdown -->
            <div class="relative filter-dropdown">
                <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover"
                        id="filterButton_<?php echo $branchId; ?>">
                    <i class="fas fa-filter text-sidebar-accent"></i>
                    <span>Filters</span>
                </button>
                
                <!-- Filter Window -->
                <div id="filterDropdown_<?php echo $branchId; ?>" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
                    <div class="space-y-4">
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

            <!-- Archived Button -->
            <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover"
                    onclick="showArchivedItems(<?php echo $branchId; ?>)">
                <i class="fas fa-archive text-sidebar-accent"></i>
                <span>Archived</span>
            </button>

            <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap" 
                    onclick="openAddInventoryModal(<?php echo $branchId; ?>)">
                <i class="fas fa-plus-circle"></i> Add Item
            </button>
        </div>
    </div>
    
    <!-- Inventory Table for this branch -->
    <div class="overflow-x-auto scrollbar-thin" id="tableContainer<?php echo $branchId; ?>">
        <div id="loadingIndicator<?php echo $branchId; ?>" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
        </div>
        
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-sidebar-border">
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 0)">
                        <div class="flex items-center">
                            <i class="fas fa-hashtag mr-1.5 text-sidebar-accent"></i> ID 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 1)">
                        <div class="flex items-center">
                            <i class="fas fa-box mr-1.5 text-sidebar-accent"></i> Item Name 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 2)">
                        <div class="flex items-center">
                            <i class="fas fa-th-list mr-1.5 text-sidebar-accent"></i> Category 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 3)">
                        <div class="flex items-center">
                            <i class="fas fa-cubes mr-1.5 text-sidebar-accent"></i> Quantity 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 4)">
                        <div class="flex items-center">
                            <i class="fas fa-tag mr-1.5 text-sidebar-accent"></i> Unit Price 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 5)">
                        <div class="flex items-center">
                            <i class="fas fa-peso-sign mr-1.5 text-sidebar-accent"></i> Total Value 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text">
                        <div class="flex items-center">
                            <i class="fas fa-cogs mr-1.5 text-sidebar-accent"></i> Actions
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody id="inventoryTable_<?php echo $branchId; ?>">
                <?php
                if ($paginatedResult->num_rows > 0) {
                    // Output data of each row
                    while($row = $paginatedResult->fetch_assoc()) {
                        echo '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">';
                        echo '<td class="p-4 text-sm text-sidebar-text font-medium">#INV-' . str_pad($row["inventory_id"], 3, '0', STR_PAD_LEFT) . '</td>';
                        echo '<td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($row["item_name"]) . '</td>';
                        echo '<td class="p-4 text-sm text-sidebar-text">';
                        echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">';
                        echo  htmlspecialchars($row["category_name"]) . '</span>';
                        echo '</td>';
                        echo '<td class="p-4 text-sm text-sidebar-text" data-sort-value="' . $row["quantity"] . '">' . $row["quantity"] . '</td>';
                        echo '<td class="p-4 text-sm font-medium text-sidebar-text" data-sort-value="' . $row["price"] . '">₱' . number_format($row["price"], 2) . '</td>';
                        echo '<td class="p-4 text-sm font-medium text-sidebar-text" data-sort-value="' . $row["total_value"] . '">₱' . number_format($row["total_value"], 2) . '</td>';
                        echo '<td class="p-4 text-sm">';
                        echo '<div class="flex space-x-2">';
                        echo '<button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="Edit Item" onclick="openViewItemModal(' . $row["inventory_id"] . ')">';
                        echo '<i class="fas fa-edit"></i>';
                        echo '</button>';
                        echo '<form method="POST" action="inventory/delete_inventory_item.php" onsubmit="return false;" style="display:inline;" class="delete-form">';
                        echo '<input type="hidden" name="inventory_id" value="' . $row["inventory_id"] . '">';
                        echo '<button type="submit" class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip" title="Archive Item">';
                        echo '<i class="fas fa-archive"></i>';
                        echo '</button>';
                        echo '</form>';
                        echo '</div>';
                        echo '</td>';
                        echo '</tr>';
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
        
        <!-- Pagination -->
        <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
            <div class="text-sm text-gray-500">
                Showing <?php echo min(($currentPage - 1) * $itemsPerPage + 1, $totalItems) . ' - ' . min($currentPage * $itemsPerPage, $totalItems); ?> 
                of <?php echo $totalItems; ?> items
            </div>
            <div class="flex space-x-1">
                <?php if ($currentPage > 1): ?>
                    <a href="?page_<?php echo $branchId; ?>=<?php echo $currentPage - 1 ?>" class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&laquo;</a>
                <?php else: ?>
                    <button disabled class="px-3 py-1 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">&laquo;</button>
                <?php endif; ?>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $currentPage): ?>
                        <button class="px-3 py-1 bg-sidebar-accent text-white rounded text-sm"><?php echo $i ?></button>
                    <?php else: ?>
                        <a href="?page_<?php echo $branchId; ?>=<?php echo $i ?>" class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover"><?php echo $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($currentPage < $totalPages): ?>
                    <a href="?page_<?php echo $branchId; ?>=<?php echo $currentPage + 1 ?>" class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&raquo;</a>
                <?php else: ?>
                    <button disabled class="px-3 py-1 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">&raquo;</button>
                <?php endif; ?>
            </div>
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
<!-- Add this modal HTML at the end of your file, before the closing PHP tag -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="viewItemModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeViewItemModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        <i class="fas fa-box mr-2"></i>
        Inventory Item Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5" id="itemDetailsContent">
      <!-- Item details will be loaded here via AJAX -->
      <div class="flex justify-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-sidebar-accent"></div>
      </div>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-6 py-4 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center" onclick="closeViewItemModal()">
        <i class="fas fa-times mr-2"></i>
        Cancel
      </button>
      <button class="px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="saveItemChanges()">
        <i class="fas fa-save mr-2"></i>
        Save Changes
      </button>
    </div>
  </div>
</div>


<!-- Add Inventory Modal -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="addInventoryModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeAddInventoryModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        <i class="fas fa-box mr-2"></i>
        Add New Inventory Item
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5">
      <form id="addInventoryForm" class="grid grid-cols-1 gap-6">
        <!-- Item Name -->
        <div>
          <label for="itemName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            <i class="fas fa-tag mr-2 text-sidebar-accent"></i>
            Item Name
          </label>
          <div class="relative">
            <input type="text" id="itemName" name="itemName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Item Name">
          </div>
        </div>
        
        <!-- Category -->
        <div>
          <label for="category" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            <i class="fas fa-th-list mr-2 text-sidebar-accent"></i>
            Category
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
        <div class="bg-gray-50 p-4 rounded-lg border-l-4 border-gold">
          <label class="block text-xs font-medium text-gray-700 mb-2">Branch</label>
          <div class="flex flex-wrap gap-4">
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
            <i class="fas fa-sort-amount-up mr-2 text-sidebar-accent"></i>
            Quantity
          </label>
          <div class="relative">
            <input type="number" id="quantity" name="quantity" min="1" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Quantity">
          </div>
        </div>
        
        <!-- Unit Price -->
        <div>
          <label for="unitPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            <i class="fas fa-coins mr-2 text-sidebar-accent"></i>
            Unit Price
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="unitPrice" name="unitPrice" step="0.01" required class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="0.00">
          </div>
        </div>
        
        <!-- File Upload -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
          <label for="itemImage" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            <i class="fas fa-image mr-2 text-sidebar-accent"></i>
            Upload Item Image
          </label>
          <div class="relative">
            <div class="flex items-center border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-1 focus-within:ring-sidebar-accent focus-within:border-sidebar-accent transition-all duration-200">
              <i class="fas fa-upload text-gray-400 mr-2"></i>
              <input type="file" id="itemImage" name="itemImage" accept="image/*" class="w-full focus:outline-none">
            </div>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-6 py-4 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center" onclick="closeAddInventoryModal()">
        <i class="fas fa-times mr-2"></i>
        Cancel
      </button>
      <button type="submit" form="addInventoryForm" class="px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
        <i class="fas fa-plus mr-2"></i>
        Add Item
      </button>
    </div>
  </div>
</div>

<!-- Edit Inventory Modal -->

<div id="editInventoryModal" class="fixed hidden top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50">
  <div class="bg-white rounded-xl w-full max-w-md max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 class="text-xl font-bold text-white"><i class="fas fa-edit"></i> Edit Inventory Item</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeEditInventoryModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    
    <!-- Modal Body -->
    <div class="p-6">
      <form id="editInventoryForm" class="space-y-4">
        <div class="space-y-4">
          <div>
            <label for="editItemId" class="block text-sm font-medium text-gray-700 mb-1">Item ID</label>
            <input type="text" id="editItemId" name="editItemId" value="<?php echo $item_id; ?>" class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed" readonly>
          </div>

          <div>
            <label for="editItemName" class="block text-sm font-medium text-gray-700 mb-1">Item Name</label>
            <input type="text" id="editItemName" name="editItemName" value="<?php echo $item_name; ?>" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" placeholder="Item Name">
          </div>

          <!-- Category Dropdown -->
          <div>
            <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select id="category" name="category" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
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

          <div>
            <label for="editQuantity" class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
            <input type="number" id="editQuantity" name="editQuantity" value="<?php echo $quantity; ?>" min="1" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" placeholder="Quantity">
          </div>

          <div>
            <label for="editUnitPrice" class="block text-sm font-medium text-gray-700 mb-1">Unit Price</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500">₱</span>
              </div>
              <input type="number" id="editUnitPrice" name="editUnitPrice" value="<?php echo $unit_price; ?>" step="0.01" required class="w-full pl-8 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" placeholder="0.00">
            </div>
          </div>

          <!-- Current Image Preview -->
          <div class="bg-navy p-5 rounded-xl">
            <div class="flex flex-col items-center space-y-3">
              <div class="w-full h-32 bg-center bg-cover rounded-lg shadow-md" style="background-image: url('<?php echo $inventory_img; ?>');"></div>
              <span class="text-sm text-gray-600">Current Image</span>
            </div>
          </div>

          <!-- File Upload -->
          <div class="bg-gray-50 p-5 rounded-xl">
            <label for="editItemImage" class="block text-sm font-medium text-gray-700 mb-3">Upload New Image</label>
            <div class="relative">
              <input type="file" id="editItemImage" name="editItemImage" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
              <div class="w-full p-3 border border-dashed border-gray-300 rounded-lg bg-gray-50 text-gray-500 text-sm flex items-center justify-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
                  <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                  <polyline points="17 8 12 3 7 8"></polyline>
                  <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
                Choose file or drag here
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeEditInventoryModal()">
        Cancel
      </button>
      <button type="submit" form="editInventoryForm" class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
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
        <i class="fas fa-archive mr-2"></i>
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
        <i class="fas fa-times mr-2"></i>
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