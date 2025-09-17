<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include 'faviconLogo.php'; 

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

// Include database connection
require_once '../db_connect.php';

// Get stats data
$stats = [
    'total_plans' => 0,
    'active_plans' => 0,
    'pending_payments' => 0,
    'total_revenue' => 0
];

if ($conn) {

  // Get total beneficiaries count
$query = "SELECT COUNT(*) as total FROM lifeplan_tb WHERE archived = 'show'";
$result = $conn->query($query);
if ($result) {
    $row = $result->fetch_assoc();
    $totalBeneficiaries = $row['total'];
    $result->free();
} else {
    $totalBeneficiaries = 0;
}
    // Total Plans
    $query = "SELECT COUNT(*) as total FROM lifeplan_tb WHERE archived = 'show'";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_plans'] = $row['total'];
        $result->free();
    }

    // Active Plans (status = 'paid' or 'ongoing')
    $query = "SELECT COUNT(*) as total FROM lifeplan_tb WHERE archived = 'show' AND payment_status IN ('paid', 'ongoing')";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['active_plans'] = $row['total'];
        $result->free();
    }

    // Pending Payments (status = 'ongoing')
    $query = "SELECT COUNT(*) as total FROM lifeplan_tb WHERE archived = 'show' AND payment_status = 'ongoing'";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['pending_payments'] = $row['total'];
        $result->free();
    }

    // Total Revenue (sum of amount_paid)
    $query = "SELECT SUM(custom_price) as total FROM lifeplan_tb WHERE archived = 'show'";
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_revenue'] = $row['total'] ? $row['total'] : 0;
        $result->free();
    }
}

// Pagination settings
$recordsPerPage = 10; // Number of records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $recordsPerPage;
$totalPages = ceil($totalBeneficiaries / $recordsPerPage);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - LifePlan</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="tailwind.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
      .modal-scroll-container {
    scrollbar-width: thin;
    scrollbar-color: #d4a933 #f5f5f5;
}

.modal-scroll-container::-webkit-scrollbar {
    width: 8px;
}

.modal-scroll-container::-webkit-scrollbar-track {
    background: #f5f5f5;
}

.modal-scroll-container::-webkit-scrollbar-thumb {
    background-color: #d4a933;
    border-radius: 6px;
}
    </style>
    
</head>
<body class="flex bg-gray-50">
<?php include 'admin_sidebar.php'; ?>

<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
    <!-- Header Actions -->
    <!-- Header with breadcrumb and welcome message -->
    <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
      <div>
        <h1 class="text-2xl font-bold text-sidebar-text">LifePlan Subscriptions</h1>
      </div>
    </div>
    
    <!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <!-- Total Plans Card -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-100 to-blue-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Total Plans</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-blue-600 flex items-center justify-center">
                    <i class="fas fa-folder-open"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold text-gray-800"><?php echo number_format($stats['total_plans']); ?></span>
            </div>
        </div>
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-gray-500">
                <span class="text-xs">Updated today</span>
            </div>
        </div>
    </div>

    <!-- Active Plans Card -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <div class="bg-gradient-to-r from-green-100 to-green-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Active Plans</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-green-600 flex items-center justify-center">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold text-gray-800"><?php echo number_format($stats['active_plans']); ?></span>
            </div>
        </div>
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-gray-500">
                <span class="text-xs">Updated today</span>
            </div>
        </div>
    </div>

    <!-- Pending Payments Card -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <div class="bg-gradient-to-r from-orange-100 to-orange-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Pending Payments</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-orange-600 flex items-center justify-center">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold text-gray-800"><?php echo number_format($stats['pending_payments']); ?></span>
            </div>
        </div>
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-gray-500">
                <span class="text-xs">Updated today</span>
            </div>
        </div>
    </div>

    <!-- Total Revenue Card -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <div class="bg-gradient-to-r from-purple-100 to-purple-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Total Revenue</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-purple-600 flex items-center justify-center">
                    <i class="fas fa-money-bill-alt"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold text-gray-800">₱<?php echo number_format($stats['total_revenue'], 2); ?></span>
            </div>
        </div>
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-gray-500">
                <span class="text-xs">Updated today</span>
            </div>
        </div>
    </div>
</div>
    
    <!-- Table Card -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
  <!-- Header Section - Made responsive with better stacking -->
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
    <!-- Desktop layout for big screens - Title on left, controls on right -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
      <!-- Title and Counter -->
      <div class="flex items-center gap-3 mb-4 lg:mb-0">
        <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Beneficiaries</h4>
        
        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
          <?php echo isset($totalBeneficiaries) ? $totalBeneficiaries . ($totalBeneficiaries != 1 ? "" : "") : ""; ?>
        </span>
      </div>
      
      <!-- Controls for big screens - aligned right -->
      <div class="hidden lg:flex items-center gap-3">
        <!-- Search Input -->
        <div class="relative">
          <input type="text" id="beneficiarySearchInput" 
                placeholder="Search beneficiaries..." 
                class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
          <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
        </div>

        <!-- Status Dropdown Acting as Filter -->
        <select id="statusFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
          <option value="">All Status</option>
          <option value="paid">Paid</option>
          <option value="ongoing">Ongoing</option>
          <option value="overdue">Overdue</option>
        </select>

        <!-- Archive Button -->
        <button id="openArchiveModal" class="px-4 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover whitespace-nowrap">
          <i class="fas fa-archive text-sidebar-accent"></i>
          <span>Archive</span>
        </button>
      </div>
    </div>
    
    <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
    <div class="lg:hidden w-full mt-4">
      <!-- First row: Search bar with filter and archive icons on the right -->
      <div class="flex items-center w-full gap-3 mb-4">
        <!-- Search Input - Takes most of the space -->
        <div class="relative flex-grow">
          <input type="text" id="mobileBeneficiarySearchInput" 
                  placeholder="Search beneficiaries..." 
                  class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
          <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>

        <!-- Icon-only buttons for filter and archive -->
        <div class="flex items-center gap-3">
          <!-- Filter Status Dropdown for Mobile -->
          <div class="relative filter-dropdown">
            <button id="mobileFilterToggle" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
              <i class="fas fa-filter text-xl"></i>
              <span id="filterIndicator" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
            </button>
            
            <!-- Filter Options Dropdown -->
            <div id="mobileFilterDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-2">
              <select id="mobileStatusFilter" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                <option value="">All Status</option>
                <option value="paid">Paid</option>
                <option value="ongoing">Ongoing</option>
                <option value="overdue">Overdue</option>
              </select>
              
            </div>      
          </div>
        </div>
        <!-- Archive Icon Button -->
        <button id="openArchiveModalMobile" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
          <i class="fas fa-archive text-xl"></i>
        </button>
      </div>
    </div>
  </div>
  
  <!-- Responsive Table Container with improved spacing -->
  <div class="overflow-x-auto scrollbar-thin" id="beneficiaryTableContainer">
    <div id="beneficiaryLoadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
      <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
    </div>
    
    <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
    <div class="min-w-full">
      <table class="w-full">
        <thead>
          <tr class="bg-gray-50 border-b border-sidebar-border">
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(0)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-user text-sidebar-accent"></i> Beneficiary Name
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(1)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-hand-holding-heart text-sidebar-accent"></i> Service Name
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(2)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-calendar-alt text-sidebar-accent"></i> Payment Duration
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(3)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-tag text-sidebar-accent"></i> Price
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(4)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-credit-card text-sidebar-accent"></i> Payment Status
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-cogs text-sidebar-accent"></i> Actions
              </div>
            </th>
          </tr>
        </thead>
        <tbody id="beneficiaryTableBody">
          <?php
          // Initialize fetchedData array before the database query
          $fetchedData = array();

          // Include database connection
          require_once '../db_connect.php';
          
          // Database connection check
          if (!$conn) {
              echo '<tr><td colspan="6" class="p-6 text-sm text-center"><div class="flex flex-col items-center"><i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-3"></i><p class="text-red-500">Database connection failed</p></div></td></tr>';
          } else {
              // Prepare and execute the query using MySQLi
              $query = "SELECT 
    lp.lifeplan_id,
    lp.service_id,
    lp.customerID,
    lp.amount_paid,
    lp.balance,
    CONCAT_WS(' ',
        lp.benefeciary_fname,
        NULLIF(lp.benefeciary_mname, ''),
        lp.benefeciary_lname,
        NULLIF(lp.benefeciary_suffix, '')
    ) AS benefeciary_fullname,
    lp.payment_duration,
    lp.custom_price,
    lp.payment_status,
    s.service_name
FROM 
    lifeplan_tb lp
JOIN 
    services_tb s ON lp.service_id = s.service_id
WHERE
    lp.archived = 'show'
LIMIT $offset, $recordsPerPage";
              
              $result = $conn->query($query);
              
              // Check if query was successful
              if (!$result) {
                  echo '<tr><td colspan="6" class="p-6 text-sm text-center"><div class="flex flex-col items-center"><i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-3"></i><p class="text-red-500">Query error: ' . $conn->error . '</p></div></td></tr>';
              } else if ($result->num_rows == 0) {
                  echo '<tr><td colspan="6" class="p-6 text-sm text-center"><div class="flex flex-col items-center"><i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i><p class="text-gray-500">No beneficiaries found</p></div></td></tr>';
              } else {
                  // Loop through the results and display each row
                  while ($row = $result->fetch_assoc()) {
                      // Add row data to our logging array
                      $fetchedData[] = $row;
                      
                      // Determine status badge class
                      $statusClass = '';
                      $statusIcon = '';
                      switch ($row['payment_status']) {
                          case 'paid':
                              $statusClass = 'bg-green-100 text-green-600 border border-green-200';
                              $statusIcon = 'fa-check-circle';
                              break;
                          case 'ongoing':
                              $statusClass = 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                              $statusIcon = 'fa-clock';
                              break;
                          case 'overdue':
                              $statusClass = 'bg-red-100 text-red-600 border border-red-200';
                              $statusIcon = 'fa-exclamation-circle';
                              break;
                          default:
                              $statusClass = 'bg-gray-100 text-gray-800 border border-gray-200';
                              $statusIcon = 'fa-question-circle';
                      }
                      
                      // Format price with PHP currency symbol
                      $formattedPrice = '₱' . number_format($row['custom_price'], 2);
                      
                      echo '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                              <td class="px-4 py-3.5 text-sm text-sidebar-text">
                                <div class="flex items-center">
                                  ' . htmlspecialchars($row['benefeciary_fullname']) . '
                                </div>
                              </td>
                              <td class="px-4 py-3.5 text-sm text-sidebar-text">' . htmlspecialchars($row['service_name']) . '</td>
                              <td class="px-4 py-3.5 text-sm text-sidebar-text">' . htmlspecialchars($row['payment_duration']) . ' years</td>
                              <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">' . $formattedPrice . '</td>
                              <td class="px-4 py-3.5 text-sm">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ' . $statusClass . '">
                                  <i class="fas ' . $statusIcon . ' mr-1"></i> ' . htmlspecialchars($row['payment_status']) . '
                                </span>
                              </td>
                              <td class="px-4 py-3.5 text-sm">
                                <div class="flex space-x-2">
                                  <button class="p-2 bg-orange-100 text-orange-600 rounded-lg hover:bg-orange-200 transition-all tooltip view-receipt-btn" 
                                          title="View Receipt" 
                                          data-id="' . $row['lifeplan_id'] . '"
                                          data-name="' . htmlspecialchars($row['benefeciary_fullname']) . '"
                                          data-custom-price="' . $row['custom_price'] . '"
                                          data-duration="' . $row['payment_duration'] . '"
                                          data-total="' . number_format($row['amount_paid'], 2) . '"
                                          data-balance="' . number_format($row['balance'], 2) . '">
                                      <i class="fas fa-receipt"></i>
                                  </button>
                                  <button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" title="Edit">
                                    <i class="fas fa-edit"></i>
                                  </button>
                                  <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip convert-to-sale-btn" 
                                          title="Convert to Sale" 
                                          data-id="'. $row['lifeplan_id'] .'"
                                          data-name="'. htmlspecialchars($row['benefeciary_fullname']).'">
                                      <i class="fas fa-exchange-alt"></i>
                                  </button>
                                  <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip delete-btn" 
                                          title="Archive" 
                                          data-id="' . $row['lifeplan_id'] .'">
                                      <i class="fas fa-archive"></i>
                                  </button>
                                  
                                  
                                </div>
                              </td>
                            </tr>';
                  }
                  // Free result set
                  $result->free();
              }
              // Close database connection
              $conn->close();
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

  
  
  <!-- Sticky Pagination Footer with improved spacing -->
<div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
    <?php 
        // Make sure $totalBeneficiaries is set
        $totalBeneficiaries = isset($totalBeneficiaries) ? $totalBeneficiaries : 0;
        
        // Make sure $recordsPerPage is set
        $recordsPerPage = isset($recordsPerPage) ? $recordsPerPage : 10;
        
        // Make sure $page is set
        $page = isset($page) ? $page : 1;
        
        // Calculate offset
        $offset = isset($offset) ? $offset : ($page - 1) * $recordsPerPage;
        
        // Calculate total pages
        $totalPages = ceil($totalBeneficiaries / $recordsPerPage);
        
        if ($totalBeneficiaries > 0) {
            $start = $offset + 1;
            $end = min($offset + $recordsPerPage, $totalBeneficiaries);
            
            echo "Showing {$start} - {$end} of {$totalBeneficiaries} beneficiaries";
        } else {
            echo "No beneficiaries found";
        }
    ?>
    </div>
    <div id="paginationContainer" class="flex space-x-2">
        <?php 
        // Debug output to check variables
        // echo "<!-- Debug: totalBeneficiaries=$totalBeneficiaries, recordsPerPage=$recordsPerPage, totalPages=$totalPages, page=$page -->";
        
        // Changed condition to show pagination if there are any beneficiaries
        if ($totalBeneficiaries > 0): 
        ?>
            <!-- First page button (double arrow) -->
            <a href="?page=1" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &laquo;
            </a>
            
            <!-- Previous page button (single arrow) -->
            <a href="<?php echo '?page=' . max(1, $page - 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &lsaquo;
            </a>
            
            <?php
            // Show exactly 3 page numbers
            if ($totalPages <= 3) {
                // If total pages is 3 or less, show all pages
                $start_page = 1;
                $end_page = $totalPages;
            } else {
                // With more than 3 pages, determine which 3 to show
                if ($page == 1) {
                    // At the beginning, show first 3 pages
                    $start_page = 1;
                    $end_page = 3;
                } elseif ($page == $totalPages) {
                    // At the end, show last 3 pages
                    $start_page = $totalPages - 2;
                    $end_page = $totalPages;
                } else {
                    // In the middle, show current page with one before and after
                    $start_page = $page - 1;
                    $end_page = $page + 1;
                    
                    // Handle edge cases
                    if ($start_page < 1) {
                        $start_page = 1;
                        $end_page = 3;
                    }
                    if ($end_page > $totalPages) {
                        $end_page = $totalPages;
                        $start_page = $totalPages - 2;
                    }
                }
            }
            
            // Generate the page buttons
            for ($i = $start_page; $i <= $end_page; $i++) {
                $active_class = ($i == $page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
                echo '<a href="?page=' . $i . '" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</a>';
            }
            ?>
            
            <!-- Next page button (single arrow) -->
            <a href="<?php echo '?page=' . min($totalPages, $page + 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == $totalPages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &rsaquo;
            </a>
            
            <!-- Last page button (double arrow) -->
            <a href="<?php echo '?page=' . $totalPages; ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == $totalPages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &raquo;
            </a>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- JavaScript for Dropdown Toggle Functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Mobile filter toggle
  const mobileFilterToggle = document.getElementById('mobileFilterToggle');
  const mobileFilterDropdown = document.getElementById('mobileFilterDropdown');
  
  if (mobileFilterToggle && mobileFilterDropdown) {
    mobileFilterToggle.addEventListener('click', function() {
      mobileFilterDropdown.classList.toggle('hidden');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      if (!mobileFilterToggle.contains(event.target) && !mobileFilterDropdown.contains(event.target)) {
        mobileFilterDropdown.classList.add('hidden');
      }
    });
  }
  
  // Sync mobile and desktop filters
  const statusFilter = document.getElementById('statusFilter');
  const mobileStatusFilter = document.getElementById('mobileStatusFilter');
  
  if (statusFilter && mobileStatusFilter) {
    statusFilter.addEventListener('change', function() {
      mobileStatusFilter.value = this.value;
      filterTable();
    });
    
    mobileStatusFilter.addEventListener('change', function() {
      statusFilter.value = this.value;
      filterTable();
      mobileFilterDropdown.classList.add('hidden');
    });
  }
  
  // Handle search functionality
  const desktopSearchInput = document.getElementById('beneficiarySearchInput');
  const mobileSearchInput = document.getElementById('mobileBeneficiarySearchInput');
  
  if (desktopSearchInput && mobileSearchInput) {
    desktopSearchInput.addEventListener('input', function() {
      mobileSearchInput.value = this.value;
      filterTable();
    });
    
    mobileSearchInput.addEventListener('input', function() {
      desktopSearchInput.value = this.value;
      filterTable();
    });
  }
  
  // Filter table based on search and status
  function filterTable() {
    const searchValue = (desktopSearchInput.value || '').toLowerCase();
    const statusValue = statusFilter.value.toLowerCase();
    const rows = document.querySelectorAll('#beneficiaryTableBody tr');
    
    rows.forEach(row => {
      const nameCell = row.cells[0]?.textContent?.toLowerCase() || '';
      const serviceCell = row.cells[1]?.textContent?.toLowerCase() || '';
      const statusCell = row.cells[4]?.textContent?.toLowerCase() || '';
      
      const matchesSearch = nameCell.includes(searchValue) || serviceCell.includes(searchValue);
      const matchesStatus = statusValue === '' || statusCell.includes(statusValue);
      
      row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
    });
  }
});
</script>

    <!-- Convert to Sale Modal -->
<div id="convertToSaleModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-lg mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" id="closeConvertModal">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Convert LifePlan to Sale
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <p class="text-gray-700 mb-4 text-center" id="convertBeneficiaryName">
        <!-- Beneficiary name will be inserted here -->
      </p>
      
      <div class="space-y-3 sm:space-y-4">
        <div>
          <label for="dateOfDeath" class="block text-xs font-medium text-gray-700 mb-1">
            Date of Death
          </label>
          <div class="relative">
            <input type="date" id="dateOfDeath" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
          </div>
        </div>
        
        <div>
          <label for="burialDate" class="block text-xs font-medium text-gray-700 mb-1">
            Internment Date
          </label>
          <div class="relative">
            <input type="date" id="burialDate" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
          </div>
        </div>
        
        <!-- Hidden inputs will be added here by JavaScript -->
        <div id="hiddenInputsContainer"></div>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" id="cancelConvertModal">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" id="confirmConvertToSale">
        Confirm Conversion
      </button>
    </div>
  </div>
</div>

    <!-- Archive Modal -->
    <div id="archiveModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
      <!-- Modal Backdrop -->
      <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
      <!-- Modal Content -->
      <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
        <!-- Close Button -->
        <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" id="closeArchiveModal">
          <i class="fas fa-times"></i>
        </button>
        <!-- Modal Header -->
        <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
          <h3 class="text-xl font-bold text-white flex items-center">
            Archived LifePlans
          </h3>
        </div>
        <!-- Search Bar -->
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <i class="fas fa-search text-gray-400"></i>
            </div>
            <input type="text" id="archivedLifePlansSearch" placeholder="Search archived lifeplans..." 
              class="w-full pl-10 pr-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
          </div>
        </div>
        <!-- Modal Body -->
        <div class="px-6 py-5 max-h-[70vh] overflow-y-auto w-full">
          <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
            <table class="w-full text-sm text-left text-gray-500">
              <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                  <th class="px-6 py-3">Beneficiary</th>
                  <th class="px-6 py-3">Service</th>
                  <th class="px-6 py-3">Duration</th>
                  <th class="px-6 py-3">Price</th>
                  <th class="px-6 py-3">Status</th>
                  <th class="px-6 py-3">Actions</th>
                </tr>
              </thead>
              <tbody id="archivedLifePlansBody" class="bg-white divide-y divide-gray-200">
                <!-- Archived plans will be loaded here -->
                <tr>
                  <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                    Loading archived lifeplans...
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <!-- Modal Footer --> 
        <div class="px-6 py-4 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
          <button class="px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center" id="archiveModalFooterClose">
            Close
          </button>
        </div>
      </div>
    </div>

  <!-- Receipt Modal -->
<div id="receiptModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" id="closeModal">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        <span class="line-clamp-1">Payment Receipt for </span><span id="beneficiaryName" class="ml-1 line-clamp-1"></span>
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
        <!-- Left side - Payment Logs -->
        <div>
          <h4 class="font-medium text-gray-700 mb-2">Payment History</h4>
          <div class="bg-gray-50 p-4 rounded-lg max-h-96 overflow-y-auto">
            <div class="space-y-4" id="paymentLogsContainer">
              <!-- Payment logs will be loaded here dynamically -->
              <div class="text-center py-4 text-gray-500">
                <i class="fas fa-spinner fa-spin"></i> Loading payment history...
              </div>
            </div>
          </div>
        </div>
        
        <!-- Right side - Payment Input -->
        <div class="space-y-3 sm:space-y-4">
          <div class="bg-gray-50 p-4 rounded-lg border-l-4 border-gold">
            <h4 class="font-medium text-gray-700 mb-2">Payment Summary</h4>
            <div class="grid grid-cols-2 gap-2 text-sm">
              <div>Monthly Amount:</div>
              <div class="font-medium" id="monthlyAmount">₱0</div>
              <div>Total Paid:</div>
              <div class="font-medium" id="totalPaid">₱15,000.00</div>
              <div>Remaining Balance:</div>
              <div class="font-medium" id="remainingBalance">₱45,000.00</div>
            </div>
          </div>
          
          <div>
            <h4 class="font-medium text-gray-700 mb-2">Record New Payment</h4>
            <div class="space-y-3">
              <div>
                <label for="paymentAmount" class="block text-xs font-medium text-gray-700 mb-1">Amount</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500">₱</span>
                  </div>
                  <input type="number" id="paymentAmount" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Enter amount">
                </div>
              </div>
              
              <div>
                <label for="paymentDate" class="block text-xs font-medium text-gray-700 mb-1">Date</label>
                <div class="relative">
                  <input type="date" id="paymentDate" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                </div>
              </div>
              
              <div>
                <label for="paymentNotes" class="block text-xs font-medium text-gray-700 mb-1">Notes</label>
                <div class="relative">
                  <textarea id="paymentNotes" rows="2" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Optional notes"></textarea>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" id="cancelModal">
        Close
      </button>
      <button id="submitPayment" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
        Record Payment
      </button>
    </div>
  </div>
</div>

  <!-- Edit LifePlan Modal -->
<div id="editLifePlanModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" id="closeEditModal">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Edit LifePlan Subscription
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <div class="grid grid-cols-1 gap-3 sm:gap-6">
        <!-- Customer Search -->
        <div>
          <label for="customerSearch" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Search Customer</label>
          <div class="relative">
            <input type="text" id="customerSearch" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Type customer name...">
            <div id="customerSuggestions" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto hidden"></div>
          </div>
          <input type="hidden" id="customerID">
        </div>
        
        <!-- Customer Details -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
          <div>
            <label for="fname" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">First Name</label>
            <div class="relative">
              <input type="text" id="fname" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
          </div>
          <div>
            <label for="mname" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Middle Name</label>
            <div class="relative">
              <input type="text" id="mname" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
          </div>
          <div>
            <label for="lname" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Last Name</label>
            <div class="relative">
              <input type="text" id="lname" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
          </div>
          <div>
            <label for="suffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Suffix</label>
            <select id="suffix" name="suffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">None</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="I">I</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                    <option value="V">V</option>
                                </select>
            <div class="relative">
            </div>
          </div>
          <div>
            <label for="email" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Email</label>
            <div class="relative">
              <input type="email" id="email" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
          </div>
          <div>
            <label for="phone" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Phone</label>
            <div class="relative">
              <input type="text" id="phone" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
          </div>
        </div>
        
        <!-- Beneficiary Details -->
        <div class="border-t pt-3 sm:pt-4 mt-3 sm:mt-4">
          <h4 class="text-sm sm:text-md font-medium text-gray-700 mb-2 sm:mb-3">Beneficiary Information</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
            <div>
              <label for="benefeciary_fname" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">First Name</label>
              <div class="relative">
                <input type="text" id="benefeciary_fname" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
            <div>
              <label for="benefeciary_mname" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Middle Name</label>
              <div class="relative">
                <input type="text" id="benefeciary_mname" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
            <div>
              <label for="benefeciary_lname" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Last Name</label>
              <div class="relative">
                <input type="text" id="benefeciary_lname" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
            <div>
              <label for="benefeciary_suffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Suffix</label>
              <select id="benefeciary_suffix" name="benefeciary_suffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">None</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="I">I</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                    <option value="V">V</option>
                                </select>
              <div class="relative">
              </div>
            </div>
            <div>
              <label for="benefeciary_dob" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Date of Birth</label>
              <div class="relative">
                <input type="date" id="benefeciary_dob" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
            <div>
              <label for="relationship_to_client" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Relationship</label>
              <div class="relative">
                <input type="text" id="relationship_to_client" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
          </div>
          
          <!-- Beneficiary Address Summary -->
          <div class="mt-4">
            <label class="block text-xs font-medium text-gray-700 mb-1">Address</label>
            <div class="flex items-center">
              <input type="text" id="benefeciary_address" name="benefeciary_address" class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg outline-none transition-all duration-200" readonly>
              <button type="button" class="ml-2 text-sidebar-accent hover:text-darkgold text-sm font-medium transition-colors" onclick="toggleAddressSection('beneficiary')">
                Change Address
              </button>
            </div>
          </div>
          
          <!-- Beneficiary Address Details (Initially Hidden) -->
          <div id="beneficiaryAddressSection" class="space-y-3 sm:space-y-4 mt-4 hidden">
              <!-- Region and Province in same row -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                  <!-- Region Dropdown -->
                  <div>
                      <label for="benefeciaryRegion" class="block text-xs font-medium text-gray-700 mb-1">Region</label>
                      <select id="benefeciaryRegion" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                          <option value="">Select Region</option>
                          <!-- Regions will be populated via JavaScript -->
                      </select>
                  </div>
                  
                  <!-- Province Dropdown -->
                  <div>
                      <label for="benefeciaryProvince" class="block text-xs font-medium text-gray-700 mb-1">Province</label>
                      <select id="benefeciaryProvince" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" disabled required>
                          <option value="">Select Province</option>
                      </select>
                  </div>
              </div>
              
              <!-- City/Municipality and Barangay in same row -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                  <!-- City/Municipality Dropdown -->
                  <div>
                      <label for="benefeciaryCity" class="block text-xs font-medium text-gray-700 mb-1">City/Municipality</label>
                      <select id="benefeciaryCity" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" disabled required>
                          <option value="">Select City/Municipality</option>
                      </select>
                  </div>
                  
                  <!-- Barangay Dropdown -->
                  <div>
                      <label for="benefeciaryBarangay" class="block text-xs font-medium text-gray-700 mb-1">Barangay</label>
                      <select id="benefeciaryBarangay" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" disabled required>
                          <option value="">Select Barangay</option>
                      </select>
                  </div>
              </div>
              
              <!-- Street and Zip Code in same row (zip code smaller) -->
              <div class="grid grid-cols-1 md:grid-cols-[1fr_120px] gap-3 sm:gap-4">
                  <!-- Street (Manual Input) -->
                  <div>
                      <label for="benefeciaryStreet" class="block text-xs font-medium text-gray-700 mb-1">Street/Building</label>
                      <input type="text" id="benefeciaryStreet" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                  </div>
                  
              </div>
          </div>
        </div>
        
        <!-- Co-Maker Details -->
        <div class="border-t pt-3 sm:pt-4 mt-3 sm:mt-4">
          <h4 class="text-sm sm:text-md font-medium text-gray-700 mb-2 sm:mb-3">Co-Maker Information</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
            <div>
              <label for="comaker_fname" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">First Name</label>
              <div class="relative">
                <input type="text" id="comaker_fname" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
            <div>
              <label for="comaker_mname" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Middle Name</label>
              <div class="relative">
                <input type="text" id="comaker_mname" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
            <div>
              <label for="comaker_lname" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Last Name</label>
              <div class="relative">
                <input type="text" id="comaker_lname" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
            <div>
              <label for="comaker_suffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Suffix</label>
              <select id="comaker_suffix" name="comaker_suffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
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
            <div>
              <label for="comaker_occupation" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Occupation</label>
              <div class="relative">
                <input type="text" id="comaker_occupation" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
            <div>
              <label for="comaker_license_type" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">License Type</label>
              <div class="relative">
                <select id="comaker_license_type" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                  <option value="">Select License Type</option>
                  <option value="Driver's License">Driver's License</option>
                  <option value="Professional ID">Professional ID</option>
                  <option value="Passport">Passport</option>
                  <option value="UMID">UMID</option>
                  <option value="Other">Other</option>
                </select>
              </div>
            </div>
            <div>
              <label for="comaker_license_number" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">License Number</label>
              <div class="relative">
                <input type="text" id="comaker_license_number" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
          </div>
          
          <!-- Co-Maker Address Summary -->
          <div class="mt-4">
            <label class="block text-xs font-medium text-gray-700 mb-1">Address</label>
            <div class="flex items-center">
              <input type="text" id="comaker_address" name="comaker_address" class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg outline-none transition-all duration-200" readonly>
              <button type="button" class="ml-2 text-sidebar-accent hover:text-darkgold text-sm font-medium transition-colors" onclick="toggleAddressSection('comaker')">
                Change Address
              </button>
            </div>
          </div>
          
          <!-- Co-Maker Address Details (Initially Hidden) -->
          <div id="comakerAddressSection" class="space-y-3 sm:space-y-4 mt-4 hidden">
              <!-- Region and Province in same row -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                  <!-- Region Dropdown -->
                  <div>
                      <label for="comakerRegion" class="block text-xs font-medium text-gray-700 mb-1">Region</label>
                      <select id="comakerRegion" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                          <option value="">Select Region</option>
                          <!-- Regions will be populated via JavaScript -->
                      </select>
                  </div>
                  
                  <!-- Province Dropdown -->
                  <div>
                      <label for="comakerProvince" class="block text-xs font-medium text-gray-700 mb-1">Province</label>
                      <select id="comakerProvince" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" disabled required>
                          <option value="">Select Province</option>
                      </select>
                  </div>
              </div>
              
              <!-- City/Municipality and Barangay in same row -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
                  <!-- City/Municipality Dropdown -->
                  <div>
                      <label for="comakerCity" class="block text-xs font-medium text-gray-700 mb-1">City/Municipality</label>
                      <select id="comakerCity" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" disabled required>
                          <option value="">Select City/Municipality</option>
                      </select>
                  </div>
                  
                  <!-- Barangay Dropdown -->
                  <div>
                      <label for="comakerBarangay" class="block text-xs font-medium text-gray-700 mb-1">Barangay</label>
                      <select id="comakerBarangay" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" disabled required>
                          <option value="">Select Barangay</option>
                      </select>
                  </div>
              </div>
              
              <!-- Street and Zip Code in same row (zip code smaller) -->
              <div class="grid grid-cols-1 md:grid-cols-[1fr_120px] gap-3 sm:gap-4">
                  <!-- Street (Manual Input) -->
                  <div>
                      <label for="comakerStreet" class="block text-xs font-medium text-gray-700 mb-1">Street/Building</label>
                      <input type="text" id="comakerStreet" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                  </div>
                  
              </div>
          </div>
        </div>
        
        <!-- Plan Details -->
        <div class="border-t pt-3 sm:pt-4 mt-3 sm:mt-4">
          <h4 class="text-sm sm:text-md font-medium text-gray-700 mb-2 sm:mb-3">Plan Information</h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 sm:gap-4">
            <div>
              <label for="service_id" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Service</label>
              <div class="relative">
                <select id="service_id" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                  <!-- Options will be populated via JavaScript -->
                </select>
              </div>
            </div>
            <div>
              <label for="payment_duration" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Payment Duration (years)</label>
              <div class="relative">
                <input type="number" id="payment_duration" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
            <div>
              <label for="custom_price" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Price</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">₱</span>
                </div>
                <input type="number" step="0.01" id="custom_price" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
            <div>
              <label for="payment_status" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Payment Status</label>
              <div class="relative">
                <select id="payment_status" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                  <option value="ongoing">Ongoing</option>
                  <option value="paid">Paid</option>
                  <option value="canceled">Canceled</option>
                  <option value="overdue">Overdue</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" id="cancelEditModal">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" id="saveLifePlan">
        Save Changes
      </button>
    </div>
  </div>
</div>

<script>
// Function to toggle address section visibility
function toggleAddressSection(type) {
  const addressSection = document.getElementById(`${type}AddressSection`);
  addressSection.classList.toggle('hidden');
}
</script>


  <script>
// Log the fetched data to console
console.log("Fetched Lifeplan Data:", <?php echo json_encode($fetchedData); ?>);

// Detailed log of each record
<?php foreach ($fetchedData as $index => $record): ?>
    console.log("Record #<?php echo $index + 1; ?>:", {
        lifeplan_id: "<?php echo $record['lifeplan_id']; ?>",
        beneficiary: "<?php echo $record['benefeciary_fullname']; ?>",
        service: "<?php echo $record['service_name']; ?>",
        duration: "<?php echo $record['payment_duration']; ?> years",
        price: "₱<?php echo number_format($record['custom_price'], 2); ?>",
        status: "<?php echo $record['payment_status']; ?>"
    });
<?php endforeach; ?>
//github push test
// Summary log
console.log("Total records fetched: <?php echo count($fetchedData); ?>");
</script>

<script>
// Function to update stats cards periodically
function updateStats() {
    fetch('lifeplan_process/get_stats.php')
        .then(response => response.json())
        .then(data => {
            if (data) {
                document.querySelector('.stats-card:nth-child(1) .text-2xl').textContent = data.total_plans;
                document.querySelector('.stats-card:nth-child(2) .text-2xl').textContent = data.active_plans;
                document.querySelector('.stats-card:nth-child(3) .text-2xl').textContent = data.pending_payments;
                document.querySelector('.stats-card:nth-child(4) .text-2xl').textContent = '₱' + data.total_revenue.toLocaleString('en-US', {minimumFractionDigits: 2});
        }
    })
  .catch(error => console.error('Error updating stats:', error));
}

// Update every 5 minutes (300000 ms)
setInterval(updateStats, 300000);

// Initial update
updateStats();
</script>

<script>
// Integrated Modal functionality with payment processing
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('receiptModal');
    const closeModalBtn = document.getElementById('closeModal');
    const cancelModalBtn = document.getElementById('cancelModal');
    const viewReceiptBtns = document.querySelectorAll('.view-receipt-btn');
    const submitPaymentBtn = document.getElementById('submitPayment');
    
    let currentLifeplanId = null;
    let currentCustomerId = null;
    let currentBalance = 0;
    let currentAmountPaid = 0;

    // Open modal when clicking View Receipt buttons
    viewReceiptBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            currentLifeplanId = this.getAttribute('data-id');
            const beneficiaryName = this.getAttribute('data-name');
            
            // Get the raw values before formatting
            const customPrice = parseFloat(this.getAttribute('data-custom-price'));
            const paymentDuration = parseInt(this.getAttribute('data-duration'));
            const totalPaid = parseFloat(this.getAttribute('data-total').replace(/,/g, ''));
            currentBalance = parseFloat(this.getAttribute('data-balance').replace(/,/g, ''));
            currentAmountPaid = totalPaid;
            
            // Calculate monthly amount properly
            const monthlyAmount = (customPrice / (paymentDuration * 12)).toFixed(2);
            console.log("wawi bai");
            
            // Fetch customer ID associated with this lifeplan
            fetchCustomerId(currentLifeplanId);
            
            // Update modal content
            document.getElementById('beneficiaryName').textContent = beneficiaryName;
            document.getElementById('monthlyAmount').textContent = formatPrice(monthlyAmount);
            document.getElementById('totalPaid').textContent = formatPrice(currentAmountPaid);
            document.getElementById('remainingBalance').textContent = formatPrice(currentBalance);
            
            // Fetch and display payment logs
            fetchPaymentLogs(currentLifeplanId);
            
            // Show modal
            modal.classList.remove('hidden');
        });
    });

    // Function to fetch payment logs
    function fetchPaymentLogs(lifeplanId) {
    const paymentLogsContainer = document.getElementById('paymentLogsContainer');
    paymentLogsContainer.innerHTML = '<div class="text-center py-4 text-gray-500"><i class="fas fa-spinner fa-spin"></i> Loading payment history...</div>';
    
    fetch(`lifeplan_process/get_payment_logs.php?lifeplan_id=${lifeplanId}`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                let html = '';
                data.forEach((log, index) => {
                    const paymentDate = new Date(log.log_date);
                    const formattedDate = paymentDate.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });
                    
                    // Construct customer name
                    let customerName = log.first_name;
                    if (log.middle_name) customerName += ' ' + log.middle_name;
                    customerName += ' ' + log.last_name;
                    if (log.suffix) customerName += ' ' + log.suffix;
                    
                    html += `
                        <div class="border-b pb-3 mb-3">
                            <div class="flex justify-between">
                                <span class="font-medium">Payment #${index + 1}</span>
                                <span class="text-green-600">${formatPrice(log.installment_amount)}</span>
                            </div>
                            <div class="text-sm text-gray-500">${formattedDate}</div>
                            <div class="text-sm mt-1">New Balance: ${formatPrice(log.new_balance)}</div>
                            <div class="text-sm mt-1 text-gray-600">Paid by: ${customerName}</div>
                        </div>
                    `;
                });
                paymentLogsContainer.innerHTML = html;
            } else {
                paymentLogsContainer.innerHTML = '<div class="text-center py-4 text-gray-500">No payment history found</div>';
            }
        })
        .catch(error => {
            console.error('Error fetching payment logs:', error);
            paymentLogsContainer.innerHTML = '<div class="text-center py-4 text-gray-500">Error loading payment history</div>';
        });
    }

    // Open modal when clicking View Receipt buttons
    // Open modal when clicking View Receipt buttons
    // viewReceiptBtns.forEach(btn => {
    //     btn.addEventListener('click', function() {
    //         currentLifeplanId = this.getAttribute('data-id');
    //         const beneficiaryName = this.getAttribute('data-name');
            
    //         // Get the raw values before formatting
    //         const customPrice = parseFloat(this.getAttribute('data-custom-price'));
    //         const paymentDuration = parseInt(this.getAttribute('data-duration'));
    //         const totalPaid = parseFloat(this.getAttribute('data-total').replace(/,/g, ''));
    //         currentBalance = parseFloat(this.getAttribute('data-balance').replace(/,/g, ''));
    //         currentAmountPaid = totalPaid;
    //         console.log(customPrice, paymentDuration);
    //         // Calculate monthly amount properly
    //         const monthlyAmount = (customPrice / (paymentDuration * 12)).toFixed(2);
    //         console.log(monthlyAmount);
            
    //         // Fetch customer ID associated with this lifeplan
    //         fetchCustomerId(currentLifeplanId);
            
    //         // Update modal content
    //         document.getElementById('beneficiaryName').textContent = beneficiaryName;
    //         document.getElementById('monthlyAmount').textContent = '₱' + monthlyAmount;
    //         document.getElementById('totalPaid').textContent = '₱' + currentAmountPaid.toFixed(2);
    //         document.getElementById('remainingBalance').textContent = '₱' + currentBalance.toFixed(2);
            
    //         // Show modal
    //         modal.classList.remove('hidden');
    //     });
    // });
    
    // Fetch customer ID associated with a lifeplan
    function fetchCustomerId(lifeplanId) {
        fetch(`lifeplan_process/get_lifeplan.php?id=${lifeplanId}`)
            .then(response => response.json())
            .then(data => {
                if (data && data.customerID) {
                    currentCustomerId = data.customerID;
                }
            })
            .catch(error => {
                console.error('Error fetching customer ID:', error);
            });
    }
    
    // Close modal
    closeModalBtn.addEventListener('click', function() {
        resetPaymentForm();
        modal.classList.add('hidden');
    });

    // Close modal
    cancelModalBtn.addEventListener('click', function() {
        resetPaymentForm();
        modal.classList.add('hidden');
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            resetPaymentForm();
            modal.classList.add('hidden');
        }
    });
    
    // Submit payment handler - Integrated version
    submitPaymentBtn.addEventListener('click', function() {
      const amount = parseFloat(document.getElementById('paymentAmount').value);

      // Validation: Check if payment amount exceeds current balance
      if (amount > currentBalance) {
          Swal.fire({
              title: 'Payment Error',
              text: `Payment amount (₱${amount.toFixed(2)}) cannot exceed the remaining balance (₱${currentBalance.toFixed(2)})`,
              icon: 'error',
              confirmButtonText: 'OK'
          });
          return;
      }

      Swal.fire({
          title: 'Confirm Payment',
          text: 'Are you sure you want to record this payment?',
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Yes, record it!',
          cancelButtonText: 'Cancel'
      }).then((result) => {
          if (result.isConfirmed) {
              const date = document.getElementById('paymentDate').value;
              const notes = document.getElementById('paymentNotes').value;
              
              // Validation from existing code
              if (!amount || !date) {
                  Swal.fire({
                      title: 'Error!',
                      text: 'Please fill in all required fields',
                      icon: 'error',
                      confirmButtonText: 'OK'
                  });
                  return;
              }
              
              // Additional validation from enhanced code
              if (isNaN(amount) || amount <= 0) {
                  Swal.fire({
                      title: 'Error!',
                      text: 'Please enter a valid payment amount',
                      icon: 'error',
                      confirmButtonText: 'OK'
                  });
                  return;
              }
              
              if (!currentLifeplanId || !currentCustomerId) {
                  Swal.fire({
                      title: 'Error!',
                      text: 'Error: Missing required data. Please try again.',
                      icon: 'error',
                      confirmButtonText: 'OK'
                  });
                  return;
              }
              
              // Calculate new values
              const newAmountPaid = currentAmountPaid + amount;
              const newBalance = Math.max(0, currentBalance - amount); // Ensure balance doesn't go negative
              
              // Prepare data for submission
              const paymentData = {
                  lifeplan_id: currentLifeplanId,
                  customer_id: currentCustomerId,
                  installment_amount: amount,
                  current_balance: currentBalance,
                  new_balance: newBalance,
                  payment_date: date,
                  notes: notes,
                  amount_paid: newAmountPaid
              };
              
              // Disable button to prevent multiple submissions (from enhanced code)
              submitPaymentBtn.disabled = true;
              submitPaymentBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
              
              // Send payment data to server (enhanced functionality)
              fetch('lifeplan_process/record_payment.php', {
                  method: 'POST',
                  headers: {
                      'Content-Type': 'application/json',
                  },
                  body: JSON.stringify(paymentData)
              })
              .then(response => response.json())
              .then(data => {
                  if (data.success) {
                      // Success message from existing code
                      Swal.fire({
                          title: 'Success!',
                          text: 'Payment recorded successfully!',
                          icon: 'success',
                          confirmButtonText: 'OK'
                      }).then(() => {
                          // Update the UI with new values (enhanced functionality)
                          document.getElementById('totalPaid').textContent = '₱' + newAmountPaid.toFixed(2);
                          document.getElementById('remainingBalance').textContent = '₱' + newBalance.toFixed(2);
                          
                          // Update current values in case user wants to make another payment
                          currentAmountPaid = newAmountPaid;
                          currentBalance = newBalance;
                          
                          // Reset form (from existing code)
                          document.getElementById('paymentAmount').value = '';
                          document.getElementById('paymentDate').value = '';
                          document.getElementById('paymentNotes').value = '';
                          
                          // Set default date to today (from existing code)
                          document.getElementById('paymentDate').valueAsDate = new Date();
                          
                          modal.classList.add('hidden');
                          
                          // Optionally refresh the table data
                          location.reload();
                      });
                  } else {
                      Swal.fire({
                          title: 'Error!',
                          text: 'Error recording payment: ' + (data.message || 'Unknown error'),
                          icon: 'error',
                          confirmButtonText: 'OK'
                      });
                  }
              })
              .catch(error => {
                  console.error('Error:', error);
                  Swal.fire({
                      title: 'Error!',
                      text: 'An error occurred while recording the payment',
                      icon: 'error',
                      confirmButtonText: 'OK'
                  });
              })
              .finally(() => {
                  submitPaymentBtn.disabled = false;
                  submitPaymentBtn.textContent = 'Record Payment';
              });
        }
      });
});
    
    // Reset payment form (helper function)
    function resetPaymentForm() {
        document.getElementById('paymentAmount').value = '';
        document.getElementById('paymentDate').value = '';
        document.getElementById('paymentNotes').value = '';
    }
    
    // Set default date to today (from existing code)
    document.getElementById('paymentDate').valueAsDate = new Date();
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const lifeplanId = this.getAttribute('data-id');
            const beneficiaryName = this.closest('tr').querySelector('td:first-child').textContent.trim();
            
            Swal.fire({
                title: 'Confirm Archive',
                text: `Are you sure you want to archive the lifeplan for ${beneficiaryName}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d4a933',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, archive it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    
                    fetch('lifeplan_process/archive_lifeplan.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            lifeplan_id: lifeplanId
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            this.closest('tr').remove();
                            Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: 'LifePlan archived successfully!',
                                confirmButtonColor: '#d4a933'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Error archiving LifePlan: ' + (data.message || 'Unknown error'),
                                confirmButtonColor: '#d4a933'
                            });
                            this.innerHTML = '<i class="fas fa-archive"></i>';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'An error occurred while archiving the LifePlan',
                            confirmButtonColor: '#d4a933'
                        });
                        this.innerHTML = '<i class="fas fa-archive"></i>';
                    });
                }
            });
        });
    });
});
</script>
<script>
  // Edit LifePlan Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editLifePlanModal');
    const closeEditModalBtn = document.getElementById('closeEditModal');
    const cancelEditModalBtn = document.getElementById('cancelEditModal');
    const editButtons = document.querySelectorAll('.fa-edit').forEach(btn => {
        btn.closest('button').addEventListener('click', function() {
            const row = this.closest('tr');
            const lifeplanId = row.querySelector('.view-receipt-btn').getAttribute('data-id');
            
            // Fetch the LifePlan data
            fetchLifePlanData(lifeplanId);
            
            // Show modal
            editModal.classList.remove('hidden');
        });
    });
    
    // Add event listeners to both buttons
closeEditModalBtn.addEventListener('click', function() {
    resetEditModal()
    editModal.classList.add('hidden');
});

cancelEditModalBtn.addEventListener('click', function() {
    resetEditModal()
    editModal.classList.add('hidden');
});

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    if (event.target === editModal) {
        resetEditModal()
        editModal.classList.add('hidden');
    }
});
    
    // Customer search functionality
    const customerSearch = document.getElementById('customerSearch');
    const customerSuggestions = document.getElementById('customerSuggestions');
    
    customerSearch.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        
        if (searchTerm.length < 2) {
            customerSuggestions.classList.add('hidden');
            return;
        }
        
        // Fetch customer suggestions
        fetch('lifeplan_process/search_customers.php?term=' + encodeURIComponent(searchTerm))
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    customerSuggestions.innerHTML = '';
                    data.forEach(customer => {
                        const suggestionItem = document.createElement('div');
                        suggestionItem.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer';
                        suggestionItem.textContent = `${customer.first_name} ${customer.last_name} (${customer.email})`;
                        
                        suggestionItem.addEventListener('click', function() {
                            customerSearch.value = `${customer.first_name} ${customer.last_name}`;
                            document.getElementById('customerID').value = customer.id;
                            
                            // Update the form fields with customer data
                            document.getElementById('fname').value = customer.first_name || '';
                            document.getElementById('mname').value = customer.middle_name || '';
                            document.getElementById('lname').value = customer.last_name || '';
                            document.getElementById('suffix').value = customer.suffix || '';
                            document.getElementById('email').value = customer.email || '';
                            document.getElementById('phone').value = customer.phone_number || '';
                            
                            customerSuggestions.classList.add('hidden');
                        });
                        
                        customerSuggestions.appendChild(suggestionItem);
                    });
                    customerSuggestions.classList.remove('hidden');
                } else {
                    customerSuggestions.innerHTML = '<div class="px-4 py-2 text-gray-500">No customers found</div>';
                    customerSuggestions.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error fetching customer suggestions:', error);
            });
    });
    
    // Save LifePlan changes
document.getElementById('saveLifePlan').addEventListener('click', function() {
    Swal.fire({
        title: 'Are you sure?',
        text: 'Do you want to save the changes to this LifePlan?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, save it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const lifeplanId = document.getElementById('lifeplanId').value;
            const formData = new FormData();
            
            // Add all form fields to FormData
            formData.append('lifeplan_id', lifeplanId);
            formData.append('customerID', document.getElementById('customerID').value);
            formData.append('fname', document.getElementById('fname').value);
            formData.append('mname', document.getElementById('mname').value);
            formData.append('lname', document.getElementById('lname').value);
            formData.append('suffix', document.getElementById('suffix').value);
            formData.append('email', document.getElementById('email').value);
            formData.append('phone', document.getElementById('phone').value);
            
            // Beneficiary fields
            formData.append('benefeciary_fname', document.getElementById('benefeciary_fname').value);
            formData.append('benefeciary_mname', document.getElementById('benefeciary_mname').value);
            formData.append('benefeciary_lname', document.getElementById('benefeciary_lname').value);
            formData.append('benefeciary_suffix', document.getElementById('benefeciary_suffix').value);
            formData.append('benefeciary_dob', document.getElementById('benefeciary_dob').value);
            formData.append('benefeciary_address', document.getElementById('benefeciary_address').value);
            formData.append('relationship_to_client', document.getElementById('relationship_to_client').value);

            // Comaker fields
            formData.append('comaker_fname', document.getElementById('comaker_fname').value);
            formData.append('comaker_mname', document.getElementById('comaker_mname').value);
            formData.append('comaker_lname', document.getElementById('comaker_lname').value);
            formData.append('comaker_suffix', document.getElementById('comaker_suffix').value);
            formData.append('comaker_occupation', document.getElementById('comaker_occupation').value);
            formData.append('comaker_license_type', document.getElementById('comaker_license_type').value);
            formData.append('comaker_license_number', document.getElementById('comaker_license_number').value);
            formData.append('comaker_address', document.getElementById('comaker_address').value);

            // Plan fields
            formData.append('service_id', document.getElementById('service_id').value);
            formData.append('payment_duration', document.getElementById('payment_duration').value);
            formData.append('custom_price', document.getElementById('custom_price').value);
            formData.append('payment_status', document.getElementById('payment_status').value);
            
            // Send the data to the server
            fetch('lifeplan_process/update_lifeplan.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'LifePlan updated successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Refresh the page or update the table row
                        resetEditModal()
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Error updating LifePlan: ' + (data.message || 'Unknown error'),
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while updating the LifePlan',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        }
    });
});
    
    // Function to fetch LifePlan data
    function fetchLifePlanData(lifeplanId) {
        fetch('lifeplan_process/get_lifeplan.php?id=' + lifeplanId)
            .then(response => response.json())
            .then(data => {
                if (data) {
                    // Store the lifeplan_id in a hidden field
                    const hiddenId = document.createElement('input');
                    hiddenId.type = 'hidden';
                    hiddenId.id = 'lifeplanId';
                    hiddenId.value = lifeplanId;
                    document.getElementById('editLifePlanModal').querySelector('.bg-white').appendChild(hiddenId);
                    
                    // Populate customer fields if customerID exists
                    if (data.customerID) {
                        document.getElementById('customerID').value = data.customerID;
                        // Fetch customer details
                        fetch('lifeplan_process/get_customer.php?id=' + data.customerID)
                            .then(response => response.json())
                            .then(customer => {
                                if (customer) {
                                    document.getElementById('fname').value = customer.first_name || '';
                                    document.getElementById('mname').value = customer.middle_name || '';
                                    document.getElementById('lname').value = customer.last_name || '';
                                    document.getElementById('suffix').value = customer.suffix || '';
                                    document.getElementById('email').value = customer.email || '';
                                    document.getElementById('phone').value = customer.phone_number || '';
                                    
                                    // Set the search input to the customer's name
                                    document.getElementById('customerSearch').value = 
                                        `${customer.first_name} ${customer.last_name}`;
                                } 
                            });
                    } else {
                        // If no customerID, use the individual fields from the lifeplan data
                        document.getElementById('fname').value = data.fname || '';
                        document.getElementById('mname').value = data.mname || '';
                        document.getElementById('lname').value = data.lname || '';
                        document.getElementById('suffix').value = data.suffix || '';
                        document.getElementById('email').value = data.email || '';
                        document.getElementById('phone').value = data.phone || '';
                    }
                    
                    // Populate beneficiary fields
                    document.getElementById('benefeciary_fname').value = data.benefeciary_fname || '';
                    document.getElementById('benefeciary_mname').value = data.benefeciary_mname || '';
                    document.getElementById('benefeciary_lname').value = data.benefeciary_lname || '';
                    document.getElementById('benefeciary_suffix').value = data.benefeciary_suffix || '';
                    document.getElementById('benefeciary_dob').value = data.benefeciary_dob || '';
                    document.getElementById('benefeciary_address').value = data.benefeciary_address || '';
                    document.getElementById('relationship_to_client').value = data.relationship_to_client || '';

                    document.getElementById('comaker_fname').value = data.comaker_fname || '';
                    document.getElementById('comaker_mname').value = data.comaker_mname || '';
                    document.getElementById('comaker_lname').value = data.comaker_lname || '';
                    document.getElementById('comaker_suffix').value = data.comaker_suffix || '';
                    document.getElementById('comaker_address').value = data.comaker_address || '';
                    document.getElementById('comaker_occupation').value = data.comaker_work || '';
                    setSelectWithFallback('comaker_license_type', data.comaker_idtype);
                    document.getElementById('comaker_license_number').value = data.comaker_idnumber || '';
                    
                    // Populate plan fields
                    document.getElementById('payment_duration').value = data.payment_duration || '';
                    document.getElementById('custom_price').value = data.custom_price || '';
                    document.getElementById('payment_status').value = data.payment_status || 'ongoing';

                    // Then capitalize the appropriate fields
                    function capitalizeWords(str) {
                        if (!str || typeof str !== 'string') return str || '';
                        return str.toLowerCase()
                            .split(' ')
                            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                            .join(' ');
                    }

                    // Capitalize the required fields
                    const fieldsToCapitalize = [
                        'benefeciary_fname', 'benefeciary_mname', 'benefeciary_lname',
                        'benefeciary_address', 'relationship_to_client',
                        'comaker_fname', 'comaker_mname', 'comaker_lname',
                        'comaker_address', 'comaker_occupation'
                    ];

                    fieldsToCapitalize.forEach(fieldId => {
                        const element = document.getElementById(fieldId);
                        if (element) {
                            element.value = capitalizeWords(element.value);
                        }
                    });

                    
                    // Fetch and populate services dropdown
                    fetch('lifeplan_process/get_services.php?lifeplan_id=' + lifeplanId)
                        .then(response => response.json())
                        .then(services => {
                            const serviceSelect = document.getElementById('service_id');
                            serviceSelect.innerHTML = '';
                            services.forEach(service => {
                                const option = document.createElement('option');
                                option.value = service.service_id;
                                option.textContent = service.service_name;
                                option.selected = (service.service_id == data.service_id);
                                serviceSelect.appendChild(option);
                            });
                        });
                }
            })
            .catch(error => {
                console.error('Error fetching LifePlan data:', error);
                alert('Error loading LifePlan data');
            });
    }
});

function setSelectWithFallback(selectId, value) {
  const selectElement = document.getElementById(selectId);
  if (!selectElement || value === null || value === undefined) return;
  
  // Check if the value exists in options
  const optionExists = Array.from(selectElement.options).some(
    option => option.value === value.toString()
  );
  
  if (optionExists) {
    selectElement.value = value;
  } else if (value) {
    // Add the missing option
    const newOption = document.createElement('option');
    newOption.value = value;
    newOption.textContent = value;
    newOption.selected = true;
    
    // Try to insert before "Other" option if it exists
    const otherOption = selectElement.querySelector('option[value="Other"]');
    if (otherOption) {
      selectElement.insertBefore(newOption, otherOption);
    } else {
      selectElement.appendChild(newOption);
    }
  }
}

function resetEditModal() {
    // Reset customer search and related fields
    document.getElementById('customerSearch').value = '';
    document.getElementById('customerID').value = '';
    
    // Hide customer suggestions
    const customerSuggestions = document.getElementById('customerSuggestions');
    if (customerSuggestions) {
        customerSuggestions.classList.add('hidden');
    }
    
    // Reset customer details
    document.getElementById('fname').value = '';
    document.getElementById('mname').value = '';
    document.getElementById('lname').value = '';
    document.getElementById('suffix').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';
    
    // Reset beneficiary details
    document.getElementById('benefeciary_fname').value = '';
    document.getElementById('benefeciary_mname').value = '';
    document.getElementById('benefeciary_lname').value = '';
    document.getElementById('benefeciary_suffix').value = '';
    document.getElementById('benefeciary_dob').value = '';
    document.getElementById('benefeciary_address').value = '';
    document.getElementById('relationship_to_client').value = '';
    
    // Reset beneficiary address fields
    document.getElementById('benefeciaryRegion').value = '';
    document.getElementById('benefeciaryProvince').value = '';
    document.getElementById('benefeciaryCity').value = '';
    document.getElementById('benefeciaryBarangay').value = '';
    document.getElementById('benefeciaryStreet').value = '';
    
    // Hide beneficiary address section
    const beneficiaryAddressSection = document.getElementById('beneficiaryAddressSection');
    if (beneficiaryAddressSection) {
        beneficiaryAddressSection.classList.add('hidden');
    }
    
    // Reset co-maker details
    document.getElementById('comaker_fname').value = '';
    document.getElementById('comaker_mname').value = '';
    document.getElementById('comaker_lname').value = '';
    document.getElementById('comaker_suffix').value = '';
    document.getElementById('comaker_occupation').value = '';
    document.getElementById('comaker_license_type').value = '';
    document.getElementById('comaker_license_number').value = '';
    document.getElementById('comaker_address').value = '';
    
    // Reset co-maker address fields
    document.getElementById('comakerRegion').value = '';
    document.getElementById('comakerProvince').value = '';
    document.getElementById('comakerCity').value = '';
    document.getElementById('comakerBarangay').value = '';
    document.getElementById('comakerStreet').value = '';
    
    // Hide co-maker address section
    const comakerAddressSection = document.getElementById('comakerAddressSection');
    if (comakerAddressSection) {
        comakerAddressSection.classList.add('hidden');
    }
    
    // Reset plan details
    document.getElementById('service_id').value = '';
    document.getElementById('payment_duration').value = '';
    document.getElementById('custom_price').value = '';
    document.getElementById('payment_status').value = 'ongoing';
    
    // Remove the hidden lifeplanId field if it exists
    const existingHiddenId = document.getElementById('lifeplanId');
    if (existingHiddenId) {
        existingHiddenId.remove();
    }
    
    // Reset any disabled states on address dropdowns
    document.getElementById('benefeciaryProvince').disabled = true;
    document.getElementById('benefeciaryCity').disabled = true;
    document.getElementById('benefeciaryBarangay').disabled = true;
    
    document.getElementById('comakerProvince').disabled = true;
    document.getElementById('comakerCity').disabled = true;
    document.getElementById('comakerBarangay').disabled = true;
}
</script>


<script>
// Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const convertModal = document.getElementById('convertToSaleModal');
    const closeConvertModalBtn = document.getElementById('closeConvertModal');
    const cancelConvertModalBtn = document.getElementById('cancelConvertModal');
    const confirmConvertBtn = document.getElementById('confirmConvertToSale');
    const convertBtns = document.querySelectorAll('.convert-to-sale-btn');
    
    let currentLifeplanId = null;
    
    // Open modal when clicking Convert to Sale buttons
    // Update the click handler for convert buttons
    convertBtns.forEach(btn => {
    btn.addEventListener('click', function() {
        console.log('Convert to Sale button clicked');
        
        currentLifeplanId = this.getAttribute('data-id');
        const beneficiaryName = this.getAttribute('data-name');
        
        console.log('LifePlan ID:', currentLifeplanId);
        console.log('Beneficiary Name:', beneficiaryName);
        
        // Update modal content
        document.getElementById('convertBeneficiaryName').textContent = 
            `Converting LifePlan for ${beneficiaryName} to completed sale`;
        
        // Set default dates to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('dateOfDeath').value = today;
        document.getElementById('burialDate').value = today;
        
        console.log('Set default dates - Date of Death:', today, 'Burial Date:', today);
        
        // Fetch LifePlan data and populate hidden inputs
        console.log('Fetching LifePlan data from:', `lifeplan_process/get_lifeplan.php?id=${currentLifeplanId}`);
        
        fetch(`lifeplan_process/get_lifeplan.php?id=${currentLifeplanId}`)
            .then(response => {
                console.log('Received response from server, parsing JSON...');
                return response.json();
            })
            .then(data => {
                if (data) {
                    console.log('Successfully fetched LifePlan data:', data);
                    
                    const container = document.getElementById('hiddenInputsContainer');
                    container.innerHTML = '';
                    console.log('Cleared hidden inputs container');
                    
                    // Create hidden inputs for all required fields
                    const fields = [
                        { name: 'customerID', value: data.customerID || '' },
                        { name: 'branch_id', value: data.branch_id || ''},
                        { name: 'service_id', value: data.service_id || ''},
                        { name: 'fname', value: data.fname || '' },
                        { name: 'mname', value: data.mname || '' },
                        { name: 'lname', value: data.lname || '' },
                        { name: 'suffix', value: data.suffix || '' },
                        { name: 'email', value: data.email || '' },
                        { name: 'phone', value: data.phone || '' },
                        { name: 'fname_deceased', value: data.fname || '' },
                        { name: 'mname_deceased', value: data.mname || '' },
                        { name: 'lname_deceased', value: data.lname || '' },
                        { name: 'suffix_deceased', value: data.suffix || '' },
                        { name: 'date_of_birth', value: data.birthdate || '' },
                        { name: 'deceased_address', value: data.full_address || '' },
                        { name: 'with_cremate', value: data.with_cremate || '0' },
                        { name: 'initial_price', value: data.initial_price || data.custom_price || '0' },
                        { name: 'discounted_price', value: data.custom_price || '0' },
                        { name: 'amount_paid', value: data.amount_paid || '0' },
                        { name: 'balance', value: data.balance || '0' },
                        { name: 'sold_by', value: <?php echo $_SESSION['user_id']; ?> },
                        { name: 'payment_method', value: 'Lifeplan' }
                    ];
                    
                    console.log('Preparing to create hidden inputs for fields:', fields);
                    
                    fields.forEach(field => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = field.name;
                        input.value = field.value;
                        input.id = `hidden_${field.name}`;
                        container.appendChild(input);
                        
                        console.log(`Created hidden input: ${field.name} = ${field.value}`);
                    });
                    
                    // Verify all inputs were created
                    console.log('All hidden inputs created. Current container content:', container.innerHTML);
                    
                    // Show modal after inputs are populated
                    convertModal.classList.remove('hidden');
                    console.log('Modal shown with all data populated');
                } else {
                    console.error('No data received from server');
                    alert('Error loading LifePlan data');
                }
            })
            .catch(error => {
                console.error('Error fetching LifePlan data:', {
                    error: error,
                    message: error.message,
                    stack: error.stack
                });
                alert('Error loading LifePlan data');
            });
    });
});
    
    // Close modal
    closeConvertModalBtn.addEventListener('click', function() {
        convertModal.classList.add('hidden');
    });

    // Close modal
    cancelConvertModalBtn.addEventListener('click', function() {
        convertModal.classList.add('hidden');
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === convertModal) {
            convertModal.classList.add('hidden');
        }
    });
    
    // Confirm conversion
    confirmConvertBtn.addEventListener('click', function() {
        const dateOfDeath = document.getElementById('dateOfDeath').value;
        const burialDate = document.getElementById('burialDate').value;
        
        
        if (!dateOfDeath || !burialDate) {
            alert('Please fill in all required dates');
            return;
        }
        
        // Disable button to prevent multiple submissions
        confirmConvertBtn.disabled = true;
        confirmConvertBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        
        // Collect all data to be sent
        const submissionData = {
            lifeplan_id: currentLifeplanId,
            date_of_death: dateOfDeath,
            burial_date: burialDate,
        };
        
        // Add all hidden input values to the submission data
        const hiddenInputs = document.querySelectorAll('#hiddenInputsContainer input');
        hiddenInputs.forEach(input => {
            submissionData[input.name] = input.value;
        });
        
        console.log('Submitting data:', submissionData); // For debugging
        
        // Send conversion data to server
        fetch('lifeplan_process/convert_to_sale.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(submissionData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Success!',
                    text: 'LifePlan successfully converted to sale!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Close modal
                    convertModal.classList.add('hidden');
                    // Optionally refresh the page or update the table
                    location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: 'Error converting to sale: ' + (data.message || 'Unknown error'),
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during conversion');
        })
        .finally(() => {
            confirmConvertBtn.disabled = false;
            confirmConvertBtn.textContent = 'Confirm Conversion';
        });
    });
});

document.addEventListener('DOMContentLoaded', function() {
    const convertModal = document.getElementById('convertToSaleModal');
    const dateOfDeathInput = document.getElementById('dateOfDeath');
    const burialDateInput = document.getElementById('burialDate');
    
    // Set max date for date of death (today)
    const today = new Date().toISOString().split('T')[0];
    dateOfDeathInput.max = today;
    
    // Set min date for burial date (tomorrow)
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    burialDateInput.min = tomorrow.toISOString().split('T')[0];
    
    // Validate date of death on input
    dateOfDeathInput.addEventListener('input', function() {
        if (this.value > today) {
            this.value = today;
        }
        
        // Ensure burial date is after date of death
        if (burialDateInput.value && burialDateInput.value <= this.value) {
            const minBurialDate = new Date(this.value);
            minBurialDate.setDate(minBurialDate.getDate() + 1);
            burialDateInput.min = minBurialDate.toISOString().split('T')[0];
            burialDateInput.value = burialDateInput.min;
        }
    });
    
    // Validate burial date on input
    burialDateInput.addEventListener('input', function() {
        if (dateOfDeathInput.value && this.value <= dateOfDeathInput.value) {
            const minBurialDate = new Date(dateOfDeathInput.value);
            minBurialDate.setDate(minBurialDate.getDate() + 1);
            this.value = minBurialDate.toISOString().split('T')[0];
        }
        
        // Ensure burial date is not before tomorrow
        if (this.value < tomorrow.toISOString().split('T')[0]) {
            this.value = tomorrow.toISOString().split('T')[0];
        }
    });
    
    // When modal opens, set default dates
    document.querySelectorAll('.convert-to-sale-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            // Set date of death to today by default
            dateOfDeathInput.value = today;
            
            // Set burial date to tomorrow by default
            burialDateInput.value = tomorrow.toISOString().split('T')[0];
        });
    });
});


    // Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const archiveModal = document.getElementById('archiveModal');
    const openArchiveModalBtn = document.getElementById('openArchiveModal');
    const openArchiveModalMobileBtn = document.getElementById('openArchiveModalMobile');
    const closeArchiveModalBtn = document.getElementById('closeArchiveModal');
    const archiveModalFooterCloseBtn = document.getElementById('archiveModalFooterClose');
    const archivedLifePlansBody = document.getElementById('archivedLifePlansBody');

    // Open archive modal
    openArchiveModalBtn.addEventListener('click', function() {
        fetchArchivedLifePlans();
        archiveModal.classList.remove('hidden');
    });

    openArchiveModalMobileBtn.addEventListener('click', function() {
        fetchArchivedLifePlans();
        archiveModal.classList.remove('hidden');
    });

    // Close archive modal (header close button)
    closeArchiveModalBtn.addEventListener('click', function() {
        archiveModal.classList.add('hidden');
    });

    // Close archive modal (footer close button)
    archiveModalFooterCloseBtn.addEventListener('click', function() {
        archiveModal.classList.add('hidden');
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === archiveModal) {
            archiveModal.classList.add('hidden');
        }
    });
    // Function to fetch archived lifeplans
    function fetchArchivedLifePlans() {
        archivedLifePlansBody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                    <i class="fas fa-spinner fa-spin"></i> Loading archived lifeplans...
                </td>
            </tr>
        `;
        
        fetch('lifeplan_process/get_archived_lifeplans.php')
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    let html = '';
                    data.forEach(plan => {
                        // Determine status badge class
                        let statusClass = '';
                        let statusIcon = '';
                        switch (plan.payment_status) {
                            case 'paid':
                                statusClass = 'bg-green-100 text-green-600 border border-green-200';
                                statusIcon = 'fa-check-circle';
                                break;
                            case 'ongoing':
                                statusClass = 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                                statusIcon = 'fa-clock';
                                break;
                            case 'overdue':
                                statusClass = 'bg-red-100 text-red-600 border border-red-200';
                                statusIcon = 'fa-exclamation-circle';
                                break;
                            default:
                                statusClass = 'bg-gray-100 text-gray-800 border border-gray-200';
                                statusIcon = 'fa-question-circle';
                        }
                        
                        html += `
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${plan.benefeciary_fullname}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    ${plan.service_name}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    ${plan.payment_duration} years
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    ₱${parseFloat(plan.custom_price).toFixed(2)}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${statusClass}">
                                        <i class="fas ${statusIcon} mr-1"></i> ${plan.payment_status}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all unarchive-btn" 
                                            data-id="${plan.lifeplan_id}">
                                        <i class="fas fa-undo"></i> Unarchive
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    archivedLifePlansBody.innerHTML = html;
                    
                    // Add event listeners to unarchive buttons
                    document.querySelectorAll('.unarchive-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const lifeplanId = this.getAttribute('data-id');
        const beneficiaryName = this.closest('tr').querySelector('td:first-child').textContent.trim();
        
        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to unarchive the lifeplan for ${beneficiaryName}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, unarchive it!',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading indicator
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                // Send request to unarchive the record
                fetch('lifeplan_process/unarchive_lifeplan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        lifeplan_id: lifeplanId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Remove the row from the table
                        this.closest('tr').remove();
                        // Show success message
                        Swal.fire({
                            title: 'Success!',
                            text: 'LifePlan unarchived successfully!',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Refresh the main table
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Error unarchiving LifePlan: ' + (data.message || 'Unknown error'),
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        // Reset button
                        this.innerHTML = '<i class="fas fa-undo"></i> Unarchive';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred while unarchiving the LifePlan',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    // Reset button
                    this.innerHTML = '<i class="fas fa-undo"></i> Unarchive';
                });
            }
        });
    });
});
                } else {
                    archivedLifePlansBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                No archived lifeplans found
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(error => {
                console.error('Error fetching archived lifeplans:', error);
                archivedLifePlansBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                            Error loading archived lifeplans
                        </td>
                    </tr>
                `;
            });
    }
});

function validateSearchInput(inputElement) {
    if (!inputElement) return;
    
    inputElement.addEventListener('input', function() {
        let value = this.value;
        
        // Don't allow consecutive spaces
        if (/\s{2,}/.test(value)) {
            this.value = value.replace(/\s{2,}/g, ' ');
            return;
        }
        
        // Don't allow space as first character
        if (value.startsWith(' ')) {
            this.value = value.substring(1);
            return;
        }
        
        // Only allow space after at least 2 characters
        if (value.length < 2 && value.includes(' ')) {
            this.value = value.replace(/\s/g, '');
            return;
        }
    });
    
    // Prevent paste of content with invalid spacing
    inputElement.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        
        // Clean the pasted text
        let cleanedText = pastedText;
        
        // Remove consecutive spaces
        cleanedText = cleanedText.replace(/\s{2,}/g, ' ');
        
        // Remove leading space
        if (cleanedText.startsWith(' ')) {
            cleanedText = cleanedText.substring(1);
        }
        
        // Remove spaces before 2 characters
        if (cleanedText.length < 2 && cleanedText.includes(' ')) {
            cleanedText = cleanedText.replace(/\s/g, '');
        }
        
        document.execCommand('insertText', false, cleanedText);
    });
}

// Apply validation to all search inputs
document.addEventListener('DOMContentLoaded', function() {
    // Find all search input elements
    const searchInputs = document.querySelectorAll('input[type="search"], input[placeholder*="search" i], input[placeholder*="Search" i], input[id*="search" i], input[name*="search" i]');
    
    // Apply validation to all found search inputs
    searchInputs.forEach(input => {
        validateSearchInput(input);
    });
});
</script>

<script>
    // Add this to your existing JavaScript for the receipt modal
document.addEventListener('DOMContentLoaded', function() {
    const paymentAmount = document.getElementById('paymentAmount');
    const paymentNotes = document.getElementById('paymentNotes');

    // Payment amount validation - no negative numbers
    paymentAmount.addEventListener('input', function() {
        if (this.value < 0) {
            this.value = '';
        }
    });

    // Payment notes validation
    paymentNotes.addEventListener('input', function() {
        // Remove multiple consecutive spaces
        this.value = this.value.replace(/\s{2,}/g, ' ');
        
        // Auto capitalize first letter of each sentence
        this.value = this.value.replace(/(^\s*\w|[.!?]\s*\w)/g, function(c) {
            return c.toUpperCase();
        });
        
        // Don't allow space unless there are already 2 characters
        if (this.value.length < 2 && this.value.includes(' ')) {
            this.value = this.value.trim();
        }
    });
}); 

// Add this to your existing JavaScript for the receipt modal
document.addEventListener('DOMContentLoaded', function() {
    // Beneficiary name fields validation (first, middle, last names, suffix)
    const beneficiaryNameFields = ['benefeciary_fname', 'benefeciary_mname', 'benefeciary_lname'];
    beneficiaryNameFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                // Allow only letters and single spaces
                this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
                
                // Remove multiple consecutive spaces
                this.value = this.value.replace(/\s{2,}/g, ' ');
                
                // Auto capitalize first letter
                if (this.value.length === 1) {
                    this.value = this.value.toUpperCase();
                }
                
                // Don't allow space unless there are already 2 characters
                if (this.value.length < 2 && this.value.includes(' ')) {
                    this.value = this.value.trim();
                }
            });
        }
    });

    // Beneficiary date of birth validation
    const beneficiaryDobField = document.getElementById('benefeciary_dob');
    if (beneficiaryDobField) {
        beneficiaryDobField.addEventListener('input', function() {
            const today = new Date().toISOString().split('T')[0];
            const selectedDate = this.value;
            
            if (selectedDate > today) {
                this.value = today;
            }
        });
        beneficiaryDobField.max = new Date().toISOString().split('T')[0]; // Set max date to today
    }

    // Relationship validation
    const relationshipField = document.getElementById('relationship_to_client');
    if (relationshipField) {
        relationshipField.addEventListener('input', function() {
            // Allow only letters and single spaces
            this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
            
            // Remove multiple consecutive spaces
            this.value = this.value.replace(/\s{2,}/g, ' ');
            
            // Auto capitalize first letter
            if (this.value.length === 1) {
                this.value = this.value.toUpperCase();
            }
            
            // Don't allow space unless there are already 2 characters
            if (this.value.length < 2 && this.value.includes(' ')) {
                this.value = this.value.trim();
            }
        });
    }

    // Beneficiary address validation
    const beneficiaryAddressField = document.getElementById('benefeciary_address');
    if (beneficiaryAddressField) {
        beneficiaryAddressField.addEventListener('input', function() {
            // Remove multiple consecutive spaces
            this.value = this.value.replace(/\s{2,}/g, ' ');
            
            // Auto capitalize first letter of each sentence
            this.value = this.value.replace(/(^\s*\w|[.!?]\s*\w)/g, function(c) {
                return c.toUpperCase();
            });
            
            // Don't allow space unless there are already 2 characters
            if (this.value.length < 2 && this.value.includes(' ')) {
                this.value = this.value.trim();
            }
        });
    }
});


// Add this to your existing JavaScript for the edit modal
document.addEventListener('DOMContentLoaded', function() {
    // Payment duration validation - strictly positive integers only
    const paymentDuration = document.getElementById('payment_duration');
    if (paymentDuration) {
        paymentDuration.addEventListener('input', function() {
            // Remove any non-digit characters
            let value = this.value.replace(/[^0-9]/g, '');
            
            // Ensure minimum value is 1 (can't be 0 or negative)
            value = value === '' ? '1' : value;
            value = parseInt(value) < 1 ? '1' : value;
            
            this.value = value;
        });
        
        // Validate on blur in case of paste or other input methods
        paymentDuration.addEventListener('blur', function() {
            if (!this.value || parseInt(this.value) < 1) {
                this.value = '1';
            }
        });
        
        // Prevent minus key press
        paymentDuration.addEventListener('keydown', function(e) {
            if (e.key === '-' || e.key === 'e' || e.key === 'E') {
                e.preventDefault();
            }
        });
    }

    // Price validation - strictly positive numbers only
    const customPrice = document.getElementById('custom_price');
    if (customPrice) {
        customPrice.addEventListener('input', function() {
            // Get current cursor position
            const cursorPos = this.selectionStart;
            
            // Remove any unwanted characters but keep numbers and single decimal
            let value = this.value.replace(/[^0-9.]/g, '');
            
            // Remove extra decimal points
            const decimalCount = (value.match(/\./g) || []).length;
            if (decimalCount > 1) {
                value = value.substring(0, value.indexOf('.')) + 
                        value.substring(value.indexOf('.')).replace(/\./g, '');
            }
            
            // Ensure value is positive and has minimum 0.01
            if (value.startsWith('0') && !value.startsWith('0.')) {
                value = '0' + value.substring(1).replace(/^0+/, '');
            }
            if (value === '.' || value === '') {
                value = '0.00';
            }
            if (parseFloat(value) <= 0) {
                value = '0.01';
            }
            
            // Format to 2 decimal places if it has decimal
            if (value.includes('.')) {
                const parts = value.split('.');
                if (parts[1].length > 2) {
                    value = parseFloat(value).toFixed(2);
                }
            }
            
            this.value = value;
            
            // Restore cursor position
            this.setSelectionRange(cursorPos, cursorPos);
        });
        
        // Validate on blur
        customPrice.addEventListener('blur', function() {
            if (!this.value || parseFloat(this.value) <= 0) {
                this.value = '0.01';
            }
            // Ensure proper decimal format
            if (!this.value.includes('.')) {
                this.value = parseFloat(this.value).toFixed(2);
            } else {
                const parts = this.value.split('.');
                if (parts[1].length < 2) {
                    this.value = parseFloat(this.value).toFixed(2);
                }
            }
        });
        
        // Prevent minus key press
        customPrice.addEventListener('keydown', function(e) {
            if (e.key === '-' || (e.key === 'e' && !e.ctrlKey && !e.metaKey)) {
                e.preventDefault();
            }
        });
    }
});

// Add this to your existing JavaScript for the edit modal
document.addEventListener('DOMContentLoaded', function() {
    // Customer search validation
    const customerSearch = document.getElementById('customerSearch');
    customerSearch.addEventListener('input', function() {
        // Remove numbers
        this.value = this.value.replace(/[0-9]/g, '');
        
        // Remove multiple consecutive spaces
        this.value = this.value.replace(/\s{2,}/g, ' ');
        
        // Don't allow space unless there are already 2 characters
        if (this.value.length < 2 && this.value.includes(' ')) {
            this.value = this.value.trim();
        }
    });

    // Name fields validation (first, middle, last names)
    const nameFields = ['fname', 'mname', 'lname'];
    nameFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        field.addEventListener('input', function() {
            // Allow only letters and single spaces
            this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
            
            // Remove multiple consecutive spaces
            this.value = this.value.replace(/\s{2,}/g, ' ');
            
            // Auto capitalize first letter
            if (this.value.length === 1) {
                this.value = this.value.toUpperCase();
            }
            
            // Don't allow space unless there are already 2 characters
            if (this.value.length < 2 && this.value.includes(' ')) {
                this.value = this.value.trim();
            }
        });
    });

    // Email validation
    const emailField = document.getElementById('email');
    emailField.addEventListener('input', function() {
        // Remove spaces
        this.value = this.value.replace(/\s/g, '');
    });

    // Phone validation (Philippine number)
    const phoneField = document.getElementById('phone');
    phoneField.addEventListener('input', function() {
        // Remove non-numeric characters
        this.value = this.value.replace(/\D/g, '');
        
        // Limit to 11 digits
        if (this.value.length > 11) {
            this.value = this.value.substring(0, 11);
        }
        
        // Ensure it starts with 09
        if (this.value.length >= 1 && !this.value.startsWith('0')) {
            this.value = '';
        }
        if (this.value.length >= 2 && !this.value.startsWith('09')) {
            this.value = '0' + this.value.substring(1, 1);
        }
    });

    // Date of birth validation
    const dobField = document.getElementById('benefeciary_dob');
    dobField.addEventListener('input', function() {
        const today = new Date().toISOString().split('T')[0];
        const selectedDate = this.value;
        
        if (selectedDate > today) {
            this.value = today;
        }
    });
    dobField.max = new Date().toISOString().split('T')[0]; // Set max date to today

    // Relationship validation
    const relationshipField = document.getElementById('relationship_to_client');
    relationshipField.addEventListener('input', function() {
        // Allow only letters and single spaces
        this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
        
        // Remove multiple consecutive spaces
        this.value = this.value.replace(/\s{2,}/g, ' ');
        
        // Auto capitalize first letter
        if (this.value.length === 1) {
            this.value = this.value.toUpperCase();
        }
        
        // Don't allow space unless there are already 2 characters
        if (this.value.length < 2 && this.value.includes(' ')) {
            this.value = this.value.trim();
        }
    });
});
</script>

<script>
// Beneficiary Address handling functions
function fetchBeneficiaryRegions() {
    fetch('../customer/address/get_regions.php')
        .then(response => response.json())
        .then(data => {
            const regionSelect = document.getElementById('benefeciaryRegion');
            regionSelect.innerHTML = '<option value="">Select Region</option>';
            
            data.forEach(region => {
                const option = document.createElement('option');
                option.value = region.region_id;
                option.textContent = region.region_name;
                regionSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error fetching regions for beneficiary:', error));
}

function fetchBeneficiaryProvinces(regionId) {
    const provinceSelect = document.getElementById('benefeciaryProvince');
    provinceSelect.innerHTML = '<option value="">Select Province/City</option>';
    provinceSelect.disabled = true;
    
    if (!regionId) return;
    
    fetch(`../customer/address/get_provinces.php?region_id=${regionId}`)
        .then(response => response.json())
        .then(data => {
            provinceSelect.innerHTML = '<option value="">Select Province/City</option>';
            
            data.forEach(province => {
                const option = document.createElement('option');
                option.value = province.province_id;
                option.textContent = province.province_name;
                provinceSelect.appendChild(option);
            });
            
            provinceSelect.disabled = false;
        })
        .catch(error => console.error('Error fetching provinces for beneficiary:', error));
}

function fetchBeneficiaryMunicipalities(provinceId) {
    const municipalitySelect = document.getElementById('benefeciaryCity');
    municipalitySelect.innerHTML = '<option value="">Select Municipality</option>';
    municipalitySelect.disabled = true;
    
    if (!provinceId) return;
    
    fetch(`../customer/address/get_cities.php?province_id=${provinceId}`)
        .then(response => response.json())
        .then(data => {
            municipalitySelect.innerHTML = '<option value="">Select Municipality</option>';
            
            data.forEach(municipality => {
                const option = document.createElement('option');
                option.value = municipality.municipality_id;
                option.textContent = municipality.municipality_name;
                municipalitySelect.appendChild(option);
            });
            
            municipalitySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching municipalities for beneficiary:', error));
}

function fetchBeneficiaryBarangays(municipalityId) {
    const barangaySelect = document.getElementById('benefeciaryBarangay');
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    barangaySelect.disabled = true;
    
    if (!municipalityId) return;
    
    fetch(`../customer/address/get_barangays.php?city_id=${municipalityId}`)
        .then(response => response.json())
        .then(data => {
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            data.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.barangay_id;
                option.textContent = barangay.barangay_name;
                barangaySelect.appendChild(option);
            });
            
            barangaySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching barangays for beneficiary:', error));
}

function updateBeneficiaryCombinedAddress() {
    const regionSelect = document.getElementById('benefeciaryRegion');
    const provinceSelect = document.getElementById('benefeciaryProvince');
    const municipalitySelect = document.getElementById('benefeciaryCity');
    const barangaySelect = document.getElementById('benefeciaryBarangay');
    const streetAddress = document.getElementById('benefeciaryStreet').value;
    
    // Get the TEXT values of the selected options, not the IDs
    const region = regionSelect.options[regionSelect.selectedIndex]?.text || '';
    const province = provinceSelect.options[provinceSelect.selectedIndex]?.text || '';
    const municipality = municipalitySelect.options[municipalitySelect.selectedIndex]?.text || '';
    const barangay = barangaySelect.options[barangaySelect.selectedIndex]?.text || '';
    
    // Create an array of non-empty address components
    const addressParts = [];
    if (streetAddress) addressParts.push(streetAddress);
    if (barangay) addressParts.push(barangay);
    if (municipality) addressParts.push(municipality);
    if (province) addressParts.push(province);
    if (region) addressParts.push(region);
    
    // Join the parts with commas
    const combinedAddress = addressParts.join(', ');
    // Assuming you have a hidden field for the beneficiary address
    document.getElementById('benefeciary_address').value = combinedAddress;
}

// Initialize beneficiary address dropdowns when the page loads
document.addEventListener('DOMContentLoaded', function() {
    fetchBeneficiaryRegions();
    
    // Set up event listeners for cascading dropdowns
    document.getElementById('benefeciaryRegion').addEventListener('change', function() {
        fetchBeneficiaryProvinces(this.value);
        document.getElementById('benefeciaryProvince').value = '';
        document.getElementById('benefeciaryCity').value = '';
        document.getElementById('benefeciaryBarangay').value = '';
        document.getElementById('benefeciaryCity').disabled = true;
        document.getElementById('benefeciaryBarangay').disabled = true;
        updateBeneficiaryCombinedAddress();
    });
    
    document.getElementById('benefeciaryProvince').addEventListener('change', function() {
        fetchBeneficiaryMunicipalities(this.value);
        document.getElementById('benefeciaryCity').value = '';
        document.getElementById('benefeciaryBarangay').value = '';
        document.getElementById('benefeciaryBarangay').disabled = true;
        updateBeneficiaryCombinedAddress();
    });
    
    document.getElementById('benefeciaryCity').addEventListener('change', function() {
        fetchBeneficiaryBarangays(this.value);
        document.getElementById('benefeciaryBarangay').value = '';
        updateBeneficiaryCombinedAddress();
    });
    
    document.getElementById('benefeciaryBarangay').addEventListener('change', updateBeneficiaryCombinedAddress);
    document.getElementById('benefeciaryStreet').addEventListener('input', updateBeneficiaryCombinedAddress);
    
    // Also update combined address when form is submitted
    const form = document.getElementById('lifeplanBookingForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            updateBeneficiaryCombinedAddress();
            // Continue with form submission
        });
    }
});
</script>

<script>
// Comaker Address handling functions
function fetchComakerRegions() {
    fetch('../customer/address/get_regions.php')
        .then(response => response.json())
        .then(data => {
            const regionSelect = document.getElementById('comakerRegion');
            regionSelect.innerHTML = '<option value="">Select Region</option>';
            
            data.forEach(region => {
                const option = document.createElement('option');
                option.value = region.region_id;
                option.textContent = region.region_name;
                regionSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error fetching regions for comaker:', error));
}

function fetchComakerProvinces(regionId) {
    const provinceSelect = document.getElementById('comakerProvince');
    provinceSelect.innerHTML = '<option value="">Select Province/City</option>';
    provinceSelect.disabled = true;
    
    if (!regionId) return;
    
    fetch(`../customer/address/get_provinces.php?region_id=${regionId}`)
        .then(response => response.json())
        .then(data => {
            provinceSelect.innerHTML = '<option value="">Select Province/City</option>';
            
            data.forEach(province => {
                const option = document.createElement('option');
                option.value = province.province_id;
                option.textContent = province.province_name;
                provinceSelect.appendChild(option);
            });
            
            provinceSelect.disabled = false;
        })
        .catch(error => console.error('Error fetching provinces for comaker:', error));
}

function fetchComakerMunicipalities(provinceId) {
    const municipalitySelect = document.getElementById('comakerCity');
    municipalitySelect.innerHTML = '<option value="">Select Municipality</option>';
    municipalitySelect.disabled = true;
    
    if (!provinceId) return;
    
    fetch(`../customer/address/get_cities.php?province_id=${provinceId}`)
        .then(response => response.json())
        .then(data => {
            municipalitySelect.innerHTML = '<option value="">Select Municipality</option>';
            
            data.forEach(municipality => {
                const option = document.createElement('option');
                option.value = municipality.municipality_id;
                option.textContent = municipality.municipality_name;
                municipalitySelect.appendChild(option);
            });
            
            municipalitySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching municipalities for comaker:', error));
}

function fetchComakerBarangays(municipalityId) {
    const barangaySelect = document.getElementById('comakerBarangay');
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    barangaySelect.disabled = true;
    
    if (!municipalityId) return;
    
    fetch(`../customer/address/get_barangays.php?city_id=${municipalityId}`)
        .then(response => response.json())
        .then(data => {
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            
            data.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.barangay_id;
                option.textContent = barangay.barangay_name;
                barangaySelect.appendChild(option);
            });
            
            barangaySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching barangays for comaker:', error));
}

function updateComakerCombinedAddress() {
    const regionSelect = document.getElementById('comakerRegion');
    const provinceSelect = document.getElementById('comakerProvince');
    const municipalitySelect = document.getElementById('comakerCity');
    const barangaySelect = document.getElementById('comakerBarangay');
    const streetAddress = document.getElementById('comakerStreet').value;
    
    // Get the TEXT values of the selected options, not the IDs
    const region = regionSelect.options[regionSelect.selectedIndex]?.text || '';
    const province = provinceSelect.options[provinceSelect.selectedIndex]?.text || '';
    const municipality = municipalitySelect.options[municipalitySelect.selectedIndex]?.text || '';
    const barangay = barangaySelect.options[barangaySelect.selectedIndex]?.text || '';
    
    // Create an array of non-empty address components
    const addressParts = [];
    if (streetAddress) addressParts.push(streetAddress);
    if (barangay) addressParts.push(barangay);
    if (municipality) addressParts.push(municipality);
    if (province) addressParts.push(province);
    if (region) addressParts.push(region);
    
    // Join the parts with commas
    const combinedAddress = addressParts.join(', ');
    document.getElementById('comaker_address').value = combinedAddress;
}

// Initialize comaker address dropdowns when the page loads
document.addEventListener('DOMContentLoaded', function() {
    fetchComakerRegions();
    
    // Set up event listeners for cascading dropdowns
    document.getElementById('comakerRegion').addEventListener('change', function() {
        fetchComakerProvinces(this.value);
        document.getElementById('comakerProvince').value = '';
        document.getElementById('comakerCity').value = '';
        document.getElementById('comakerBarangay').value = '';
        document.getElementById('comakerCity').disabled = true;
        document.getElementById('comakerBarangay').disabled = true;
        updateComakerCombinedAddress();
    });
    
    document.getElementById('comakerProvince').addEventListener('change', function() {
        fetchComakerMunicipalities(this.value);
        document.getElementById('comakerCity').value = '';
        document.getElementById('comakerBarangay').value = '';
        document.getElementById('comakerBarangay').disabled = true;
        updateComakerCombinedAddress();
    });
    
    document.getElementById('comakerCity').addEventListener('change', function() {
        fetchComakerBarangays(this.value);
        document.getElementById('comakerBarangay').value = '';
        updateComakerCombinedAddress();
    });
    
    document.getElementById('comakerBarangay').addEventListener('change', updateComakerCombinedAddress);
    document.getElementById('comakerStreet').addEventListener('input', updateComakerCombinedAddress);
    
    // Also update combined address when form is submitted
    const form = document.getElementById('lifeplanBookingForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            updateComakerCombinedAddress();
            // Continue with form submission
        });
    }
});

// Toggle function for address sections
function toggleAddressSection(type) {
    const section = document.getElementById(`${type}AddressSection`);
    section.classList.toggle('hidden');
}

function formatPrice(amount) {
    return "₱" + Number(amount).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}
</script>



</body>
</html>