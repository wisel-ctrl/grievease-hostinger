<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifePlan - GrievEase</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="tailwind.js"></script>
    
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
                <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800"><?php echo number_format($stats['total_plans']); ?></span>
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
                <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800"><?php echo number_format($stats['active_plans']); ?></span>
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
                <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800"><?php echo number_format($stats['pending_payments']); ?></span>
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
                <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800">₱<?php echo number_format($stats['total_revenue'], 2); ?></span>
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
          <i class="fas fa-clipboard-list"></i>
          <?php echo isset($totalBeneficiaries) ? $totalBeneficiaries . " Beneficiar" . ($totalBeneficiaries != 1 ? "ies" : "y") : "Beneficiaries"; ?>
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

          <!-- Archive Icon Button -->
            <button id="openArchiveModalMobile" class="px-4 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover whitespace-nowrap">
                <i class="fas fa-archive text-sidebar-accent"></i>
                <span>Archive</span>
            </button>
        </div>
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
                    LIMIT 6"; // Limit to 6 records for pagination
              
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
                                  <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip view-receipt-btn" 
                                          title="View Receipt" 
                                          data-id="' . $row['lifeplan_id'] . '"
                                          data-name="' . htmlspecialchars($row['benefeciary_fullname']) . '"
                                          data-custom-price="' . $row['custom_price'] . '"
                                          data-duration="' . $row['payment_duration'] . '"
                                          data-total="' . number_format($row['amount_paid']) . '"
                                          data-balance="' . number_format($row['balance']) . '">
                                      <i class="fas fa-receipt"></i>
                                  </button>
                                  <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="Edit">
                                    <i class="fas fa-edit"></i>
                                  </button>
                                  <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip delete-btn" 
                                          title="Archive" 
                                          data-id="' . $row['lifeplan_id'] .'">
                                      <i class="fas fa-archive"></i>
                                  </button>
                                  <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip convert-to-sale-btn" 
                                          title="Convert to Sale" 
                                          data-id="'. $row['lifeplan_id'] .'"
                                          data-name="'. htmlspecialchars($row['benefeciary_fullname']).'">
                                      <i class="fas fa-exchange-alt"></i>
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
      Showing <?php echo isset($offset) ? ($offset + 1) : '1'; ?> - <?php echo isset($offset) && isset($recordsPerPage) ? min($offset + $recordsPerPage, isset($totalBeneficiaries) ? $totalBeneficiaries : 6) : '6'; ?> 
      of <?php echo isset($totalBeneficiaries) ? $totalBeneficiaries : '6'; ?> beneficiaries
    </div>
    <div class="flex space-x-2">
      <a href="<?php echo '?page=' . (isset($page) ? max(1, $page - 1) : '1'); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo (!isset($page) || $page <= 1) ? 'opacity-50 pointer-events-none' : ''; ?>">&laquo;</a>
      
      <?php 
      $totalPages = isset($totalPages) ? $totalPages : 1;
      $page = isset($page) ? $page : 1;
      
      for ($i = 1; $i <= $totalPages; $i++): 
      ?>
        <a href="<?php echo '?page=' . $i; ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm <?php echo $i == $page ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?>">
          <?php echo $i; ?>
        </a>
      <?php endfor; ?>
      
      <a href="<?php echo '?page=' . (isset($page) ? min($totalPages, $page + 1) : '2'); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo (!isset($page) || $page >= $totalPages) ? 'opacity-50 pointer-events-none' : ''; ?>">&raquo;</a>
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
    <div id="convertToSaleModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            
            <!-- Modal container -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2">
                                Convert LifePlan to Sale
                            </h3>
                            
                            <div class="mt-4">
                                <p class="text-sm text-gray-500 mb-4" id="convertBeneficiaryName">
                                    <!-- Beneficiary name will be inserted here -->
                                </p>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="dateOfDeath" class="block text-sm font-medium text-gray-700">Date of Death</label>
                                        <input type="date" id="dateOfDeath" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    
                                    <div>
                                        <label for="burialDate" class="block text-sm font-medium text-gray-700">Burial Date</label>
                                        <input type="date" id="burialDate" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                    </div>
                                    
                                    <div>
                                        <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                        <textarea id="notes" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Additional notes about the service"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirmConvertToSale" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Confirm Conversion
                    </button>
                    <button type="button" id="closeConvertModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Archive Modal -->
    <div id="archiveModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>
            
            <!-- Modal container -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2">
                                Archived LifePlans
                            </h3>
                            
                            <div class="mt-4">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Beneficiary</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
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
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="closeArchiveModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

  <!-- Receipt Modal -->
  <div id="receiptModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <!-- Background overlay -->
          <div class="fixed inset-0 transition-opacity" aria-hidden="true">
              <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
          </div>
          
          <!-- Modal container -->
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                  <div class="sm:flex sm:items-start">
                      <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                          <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2">
                              Payment Receipt for <span id="beneficiaryName"></span>
                          </h3>
                          
                          <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-6">
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
                              <div>
                                  <div class="bg-blue-50 p-4 rounded-lg mb-4">
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
                                  
                                  <div class="mt-4">
                                      <h4 class="font-medium text-gray-700 mb-2">Record New Payment</h4>
                                      <div class="space-y-3">
                                          <div>
                                              <label for="paymentAmount" class="block text-sm font-medium text-gray-700">Amount</label>
                                              <input type="number" id="paymentAmount" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Enter amount">
                                          </div>
                                          <div>
                                              <label for="paymentDate" class="block text-sm font-medium text-gray-700">Date</label>
                                              <input type="date" id="paymentDate" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                          </div>
                                          <div>
                                              <label for="paymentNotes" class="block text-sm font-medium text-gray-700">Notes</label>
                                              <textarea id="paymentNotes" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Optional notes"></textarea>
                                          </div>
                                          <button id="submitPayment" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                              Record Payment
                                          </button>
                                      </div>
                                  </div>
                              </div>
                          </div>
                      </div>
                  </div>
              </div>
              <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                  <button type="button" id="closeModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                      Close
                  </button>
              </div>
          </div>
      </div>
  </div>     

  <!-- Edit LifePlan Modal -->
  <div id="editLifePlanModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
      <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
          <!-- Background overlay -->
          <div class="fixed inset-0 transition-opacity" aria-hidden="true">
              <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
          </div>
          
          <!-- Modal container -->
          <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
              <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                  <div class="sm:flex sm:items-start">
                      <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                          <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2">
                              Edit LifePlan Subscription
                          </h3>
                          
                          <div class="mt-4 grid grid-cols-1 gap-4">
                              <!-- Customer Search -->
                              <div>
                                  <label for="customerSearch" class="block text-sm font-medium text-gray-700">Search Customer</label>
                                  <div class="mt-1 relative">
                                      <input type="text" id="customerSearch" class="block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Type customer name...">
                                      <div id="customerSuggestions" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto hidden"></div>
                                  </div>
                                  <input type="hidden" id="customerID">
                              </div>
                              
                              <!-- Customer Details -->
                              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                  <div>
                                      <label for="fname" class="block text-sm font-medium text-gray-700">First Name</label>
                                      <input type="text" id="fname" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                  </div>
                                  <div>
                                      <label for="mname" class="block text-sm font-medium text-gray-700">Middle Name</label>
                                      <input type="text" id="mname" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                  </div>
                                  <div>
                                      <label for="lname" class="block text-sm font-medium text-gray-700">Last Name</label>
                                      <input type="text" id="lname" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                  </div>
                                  <div>
                                      <label for="suffix" class="block text-sm font-medium text-gray-700">Suffix</label>
                                      <input type="text" id="suffix" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                  </div>
                                  <div>
                                      <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                      <input type="email" id="email" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                  </div>
                                  <div>
                                      <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                      <input type="text" id="phone" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                  </div>
                              </div>
                              
                              <!-- Beneficiary Details -->
                              <div class="border-t pt-4 mt-4">
                                  <h4 class="text-md font-medium text-gray-700 mb-3">Beneficiary Information</h4>
                                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                      <div>
                                          <label for="benefeciary_fname" class="block text-sm font-medium text-gray-700">First Name</label>
                                          <input type="text" id="benefeciary_fname" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                      </div>
                                      <div>
                                          <label for="benefeciary_mname" class="block text-sm font-medium text-gray-700">Middle Name</label>
                                          <input type="text" id="benefeciary_mname" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                      </div>
                                      <div>
                                          <label for="benefeciary_lname" class="block text-sm font-medium text-gray-700">Last Name</label>
                                          <input type="text" id="benefeciary_lname" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                      </div>
                                      <div>
                                          <label for="benefeciary_suffix" class="block text-sm font-medium text-gray-700">Suffix</label>
                                          <input type="text" id="benefeciary_suffix" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                      </div>
                                      <div>
                                          <label for="benefeciary_dob" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                                          <input type="date" id="benefeciary_dob" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                      </div>
                                      <div>
                                          <label for="relationship_to_client" class="block text-sm font-medium text-gray-700">Relationship</label>
                                          <input type="text" id="relationship_to_client" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                      </div>
                                  </div>
                                  <div class="mt-4">
                                      <label for="benefeciary_address" class="block text-sm font-medium text-gray-700">Address</label>
                                      <textarea id="benefeciary_address" rows="2" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"></textarea>
                                  </div>
                              </div>
                              
                              <!-- Plan Details -->
                              <div class="border-t pt-4 mt-4">
                                  <h4 class="text-md font-medium text-gray-700 mb-3">Plan Information</h4>
                                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                      <div>
                                          <label for="service_id" class="block text-sm font-medium text-gray-700">Service</label>
                                          <select id="service_id" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                              <!-- Options will be populated via JavaScript -->
                                          </select>
                                      </div>
                                      <div>
                                          <label for="payment_duration" class="block text-sm font-medium text-gray-700">Payment Duration (years)</label>
                                          <input type="number" id="payment_duration" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                      </div>
                                      <div>
                                          <label for="custom_price" class="block text-sm font-medium text-gray-700">Price</label>
                                          <input type="number" step="0.01" id="custom_price" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                      </div>
                                      <div>
                                          <label for="payment_status" class="block text-sm font-medium text-gray-700">Payment Status</label>
                                          <select id="payment_status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
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
              </div>
              <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                  <button type="button" id="saveLifePlan" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                      Save Changes
                  </button>
                  <button type="button" id="closeEditModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                      Cancel
                  </button>
              </div>
          </div>
      </div>
  </div>

</div>


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
            
            // Fetch customer ID associated with this lifeplan
            fetchCustomerId(currentLifeplanId);
            
            // Update modal content
            document.getElementById('beneficiaryName').textContent = beneficiaryName;
            document.getElementById('monthlyAmount').textContent = '₱' + monthlyAmount;
            document.getElementById('totalPaid').textContent = '₱' + currentAmountPaid.toFixed(2);
            document.getElementById('remainingBalance').textContent = '₱' + currentBalance.toFixed(2);
            
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
                                <span class="text-green-600">₱${parseFloat(log.installment_amount).toFixed(2)}</span>
                            </div>
                            <div class="text-sm text-gray-500">${formattedDate}</div>
                            <div class="text-sm mt-1">New Balance: ₱${parseFloat(log.new_balance).toFixed(2)}</div>
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
            console.log(customPrice, paymentDuration);
            // Calculate monthly amount properly
            const monthlyAmount = (customPrice / (paymentDuration * 12)).toFixed(2);
            console.log(monthlyAmount);
            
            // Fetch customer ID associated with this lifeplan
            fetchCustomerId(currentLifeplanId);
            
            // Update modal content
            document.getElementById('beneficiaryName').textContent = beneficiaryName;
            document.getElementById('monthlyAmount').textContent = '₱' + monthlyAmount;
            document.getElementById('totalPaid').textContent = '₱' + currentAmountPaid.toFixed(2);
            document.getElementById('remainingBalance').textContent = '₱' + currentBalance.toFixed(2);
            
            // Show modal
            modal.classList.remove('hidden');
        });
    });
    
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
        const date = document.getElementById('paymentDate').value;
        const notes = document.getElementById('paymentNotes').value;
        
        // Validation from existing code
        if (!amount || !date) {
            alert('Please fill in all required fields');
            return;
        }
        
        // Additional validation from enhanced code
        if (isNaN(amount) || amount <= 0) {
            alert('Please enter a valid payment amount');
            return;
        }
        
        if (!currentLifeplanId || !currentCustomerId) {
            alert('Error: Missing required data. Please try again.');
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
                alert('Payment recorded successfully!');
                
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
            } else {
                alert('Error recording payment: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while recording the payment');
        })
        .finally(() => {
            submitPaymentBtn.disabled = false;
            submitPaymentBtn.textContent = 'Record Payment';
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
    // Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Archive/Delete button functionality
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const lifeplanId = this.getAttribute('data-id');
            const beneficiaryName = this.closest('tr').querySelector('td:first-child').textContent.trim();
            
            if (confirm(`Are you sure you want to archive the lifeplan for ${beneficiaryName}?`)) {
                // Show loading indicator
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                // Send request to archive the record
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
                        // Remove the row from the table
                        this.closest('tr').remove();
                        // Optionally show a success message
                        alert('LifePlan archived successfully!');
                    } else {
                        alert('Error archiving LifePlan: ' + (data.message || 'Unknown error'));
                        // Reset button icon
                        this.innerHTML = '<i class="fas fa-archive"></i>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while archiving the LifePlan');
                    // Reset button icon
                    this.innerHTML = '<i class="fas fa-archive"></i>';
                });
            }
        });
    });
});
</script>
<script>
  // Edit LifePlan Modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('editLifePlanModal');
    const closeEditModalBtn = document.getElementById('closeEditModal');
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
    
    // Close modal
    closeEditModalBtn.addEventListener('click', function() {
        editModal.classList.add('hidden');
    });
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === editModal) {
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
                alert('LifePlan updated successfully!');
                // Refresh the page or update the table row
                location.reload();
            } else {
                alert('Error updating LifePlan: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the LifePlan');
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
                    }
                    
                    // Populate beneficiary fields
                    document.getElementById('benefeciary_fname').value = data.benefeciary_fname || '';
                    document.getElementById('benefeciary_mname').value = data.benefeciary_mname || '';
                    document.getElementById('benefeciary_lname').value = data.benefeciary_lname || '';
                    document.getElementById('benefeciary_suffix').value = data.benefeciary_suffix || '';
                    document.getElementById('benefeciary_dob').value = data.benefeciary_dob || '';
                    document.getElementById('benefeciary_address').value = data.benefeciary_address || '';
                    document.getElementById('relationship_to_client').value = data.relationship_to_client || '';
                    
                    // Populate plan fields
                    document.getElementById('payment_duration').value = data.payment_duration || '';
                    document.getElementById('custom_price').value = data.custom_price || '';
                    document.getElementById('payment_status').value = data.payment_status || 'ongoing';
                    
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
</script>
<script>
    // Add this to your existing JavaScript
document.addEventListener('DOMContentLoaded', function() {
    const archiveModal = document.getElementById('archiveModal');
    const openArchiveModalBtn = document.getElementById('openArchiveModal');
    const openArchiveModalMobileBtn = document.getElementById('openArchiveModalMobile');
    const closeArchiveModalBtn = document.getElementById('closeArchiveModal');
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

    // Close archive modal
    closeArchiveModalBtn.addEventListener('click', function() {
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
                            
                            if (confirm(`Are you sure you want to unarchive the lifeplan for ${beneficiaryName}?`)) {
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
                                        alert('LifePlan unarchived successfully!');
                                        // Refresh the main table if needed
                                        location.reload();
                                    } else {
                                        alert('Error unarchiving LifePlan: ' + (data.message || 'Unknown error'));
                                        // Reset button
                                        this.innerHTML = '<i class="fas fa-undo"></i> Unarchive';
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    alert('An error occurred while unarchiving the LifePlan');
                                    // Reset button
                                    this.innerHTML = '<i class="fas fa-undo"></i> Unarchive';
                                });
                            }
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
</script>

</body>
</html>