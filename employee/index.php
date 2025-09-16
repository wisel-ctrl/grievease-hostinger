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

require_once '../db_connect.php';
  $user_id = $_SESSION['user_id'];
  $query = "SELECT first_name , last_name , email , birthdate, branch_loc FROM users WHERE id = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $first_name = $row['first_name']; // We're confident user_id exists
  $last_name = $row['last_name'];
  $email = $row['email'];
  $branch = $row['branch_loc'];

// Calculate quick stats
$current_month = date('m');
$current_year = date('Y');

// Services this month
$services_query = "SELECT COUNT(*) as service_count FROM sales_tb 
                  WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ? AND branch_id = ?";
$stmt = $conn->prepare($services_query);
$stmt->bind_param("iii", $current_month, $current_year, $branch);
$stmt->execute();
$services_result = $stmt->get_result();
$services_data = $services_result->fetch_assoc();
$services_this_month = $services_data['service_count'];

// Cash revenue (sum of amount_paid)
$cash_query = "SELECT SUM(amount_paid) as cash_revenue FROM sales_tb 
              WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ? AND branch_id = ?";
$stmt = $conn->prepare($cash_query);
$stmt->bind_param("iii", $current_month, $current_year, $branch);
$stmt->execute();
$cash_result = $stmt->get_result();
$cash_data = $cash_result->fetch_assoc();
$cash_revenue = $cash_data['cash_revenue'] ? $cash_data['cash_revenue'] : 0;

// Accrual revenue (sum of discounted_price)
$accrual_query = "SELECT SUM(discounted_price) as accrual_revenue FROM sales_tb 
                WHERE MONTH(get_timestamp) = ? AND YEAR(get_timestamp) = ? AND branch_id = ?";
$stmt = $conn->prepare($accrual_query);
$stmt->bind_param("iii", $current_month, $current_year, $branch);
$stmt->execute();
$accrual_result = $stmt->get_result();
$accrual_data = $accrual_result->fetch_assoc();
$accrual_revenue = $accrual_data['accrual_revenue'] ? $accrual_data['accrual_revenue'] : 0;

// Ongoing services (status = 'Pending')
$ongoing_query = "SELECT COUNT(*) as ongoing_count FROM sales_tb WHERE status = 'Pending' AND branch_id = ?";
$stmt = $conn->prepare($ongoing_query);
$stmt -> bind_param("i",$branch);
$stmt->execute();
$ongoing_result = $stmt->get_result();
$ongoing_data = $ongoing_result->fetch_assoc();
$ongoing_services = $ongoing_data['ongoing_count'];


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Employee Dashboard</title>
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

    /* Gradient background for menu section headers */
    .menu-header {
      background: linear-gradient(to right, rgba(202, 138, 4, 0.1), transparent);
    }
    
    /* Responsive sidebar and main content */
    .main-content {
      transition: margin-left 0.3s ease, padding 0.3s ease;
    }
    
    /* Desktop styles */
    @media (min-width: 1024px) {
      .main-content {
        margin-left: 16rem;
        width: calc(100% - 16rem);
      }
    }
    
    /* Tablet styles */
    @media (max-width: 1023px) and (min-width: 768px) {
      .main-content {
        margin-left: 0;
        width: 100%;
        padding: 1rem;
      }
    }
    
    /* Mobile styles */
    @media (max-width: 767px) {
      .main-content {
        margin-left: 0;
        width: 100%;
        padding: 0.75rem;
      }
      
      /* Adjust card spacing for mobile */
      .mobile-card-spacing {
        gap: 0.75rem;
      }
      
      /* Mobile table styles */
      .mobile-table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }
      
      /* Mobile header adjustments */
      .mobile-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
      }
      
      .mobile-header h1 {
        font-size: 1.5rem;
      }
    }
    
    /* Sidebar responsive styles */
    #sidebar {
      transition: transform 0.3s ease, opacity 0.3s ease;
    }
    
    @media (max-width: 1023px) {
      #sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        z-index: 50;
        transform: translateX(-100%);
      }
      
      #sidebar.show {
        transform: translateX(0);
      }
      
      /* Mobile overlay */
      .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 40;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
      }
      
      .sidebar-overlay.show {
        opacity: 1;
        visibility: visible;
      }
    }
    
    /* Mobile hamburger menu */
    .hamburger-menu {
      display: none;
    }
    
    @media (max-width: 1023px) {
      .hamburger-menu {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        background-color: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
      }
      
      .hamburger-menu:hover {
        background-color: #f9fafb;
        border-color: #d1d5db;
      }
    }
    
    /* Revenue toggle responsive */
    @media (max-width: 640px) {
      #revenue-dropdown {
        right: 0;
        left: auto;
        min-width: 6rem;
      }
    }
    
    /* Pagination responsive */
    @media (max-width: 640px) {
      #paginationControls button {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
      }
    }
  </style>
</head>
<body class="bg-gray-50">
  <!-- Mobile sidebar overlay -->
  <div id="sidebar-overlay" class="sidebar-overlay"></div>
  <!-- Modify the sidebar structure to include a dedicated space for the hamburger menu -->
<?php include 'employee_sidebar.php'; ?>

  <!-- Main Content -->
<div id="main-content" class="lg:ml-64 p-3 sm:p-4 lg:p-6 bg-gray-50 min-h-screen transition-all duration-300 main-content">
  <!-- Mobile hamburger menu -->
  <div class="hamburger-menu lg:hidden mb-4" id="hamburger-menu">
    <i class="fas fa-bars text-gray-600"></i>
  </div>
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 bg-white p-4 sm:p-5 rounded-lg shadow-sidebar mobile-header">
    <div class="mb-2 sm:mb-0">
      <h1 class="text-xl sm:text-2xl font-bold text-sidebar-text mobile-header">Employee Dashboard</h1>
      <p class="text-sm text-gray-500">Welcome back, <?php echo htmlspecialchars($first_name); ?></p>
    </div>
    <div class="flex space-x-3 w-full sm:w-auto justify-end">
    
    </div>
  </div>

  <!-- Quick Stats -->
    <div class="mb-6 sm:mb-8">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4 lg:gap-5 mobile-card-spacing">
      <!-- Services this Month -->
      <div class="bg-white rounded-lg shadow-sidebar p-4 sm:p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
        <div class="flex items-center mb-3">
          <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mr-3">
            <i class="fas fa-calendar-alt text-lg"></i>
          </div>
          <span class="text-sidebar-text font-medium">Services this Month</span>
        </div>
        <div class="text-3xl font-bold mb-2 text-sidebar-text"><?php echo $services_this_month; ?></div>
        <div class="text-sm text-green-600 flex items-center">
          <i class="fas fa-arrow-up mr-1"></i> 2% from last week
        </div>
      </div>
      
      <!-- Monthly Revenue with Toggle -->
      <div class="bg-white rounded-lg shadow-sidebar p-4 sm:p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center">
            <div class="w-12 h-12 rounded-lg bg-green-100 text-green-600 flex items-center justify-center mr-3">
              <i class="fas fa-peso-sign text-lg"></i>
            </div>
            <span class="text-sidebar-text font-medium">Monthly Revenue</span>
          </div>
          <div class="relative">
            <button id="revenue-toggle" class="p-1 bg-gray-100 rounded-full flex items-center">
              <span id="revenue-type" class="text-xs px-2">Cash</span>
              <i class="fas fa-chevron-down text-xs mr-1"></i>
            </button>
            <div id="revenue-dropdown" class="absolute right-0 mt-1 w-24 bg-white rounded-md shadow-lg hidden z-10">
              <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="toggleRevenue('cash')">Cash</button>
              <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="toggleRevenue('accrual')">Accrual</button>
            </div>
          </div>
        </div>
        <div id="cash-revenue" class="text-3xl font-bold mb-2 text-sidebar-text">₱<?php echo number_format($cash_revenue, 2); ?></div>
        <div id="accrual-revenue" class="text-3xl font-bold mb-2 text-sidebar-text hidden">₱<?php echo number_format($accrual_revenue, 2); ?></div>
        <div class="text-sm text-green-600 flex items-center">
          <i class="fas fa-arrow-up mr-1"></i> 5% from yesterday
        </div>
      </div>
      
      <!-- Ongoing Services -->
      <div class="bg-white rounded-lg shadow-sidebar p-4 sm:p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
        <div class="flex items-center mb-3">
          <div class="w-12 h-12 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center mr-3">
            <i class="fas fa-tasks text-lg"></i>
          </div>
          <span class="text-sidebar-text font-medium">Ongoing Services</span>
        </div>
        <div class="text-3xl font-bold mb-2 text-sidebar-text"><?php echo $ongoing_services; ?></div>
        <div class="text-sm text-red-600 flex items-center">
          <i class="fas fa-arrow-down mr-1"></i> 1 task added
        </div>
      </div>
    </div>
  </div>

  <!-- Pending Bookings Table -->
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-6 sm:mb-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-4 sm:p-5 border-b border-sidebar-border gap-2 sm:gap-0">
      <h3 class="font-medium text-sidebar-text">Pending Bookings</h3>
    </div>
    <div class="overflow-x-auto scrollbar-thin mobile-table-scroll">
      <table class="w-full">
        <thead>
          <tr class="bg-sidebar-hover">
            <th class="p-3 sm:p-4 text-left text-xs sm:text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(0)">
              <div class="flex items-center">
                Client Name <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-3 sm:p-4 text-left text-xs sm:text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(1)">
              <div class="flex items-center">
                Service Type <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-3 sm:p-4 text-left text-xs sm:text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(2)">
              <div class="flex items-center">
                Date <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-3 sm:p-4 text-left text-xs sm:text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(3)">
              <div class="flex items-center">
                Location <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-3 sm:p-4 text-left text-xs sm:text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(4)">
              <div class="flex items-center">
                Status <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Execute the pending bookings query
          $pending_query = "SELECT 
                              b.booking_id,
                              COALESCE(s.service_name, 'Custom Package') AS service_name,
                              CONCAT(u.first_name, ' ', u.middle_name, ' ', u.last_name, ' ', u.suffix) AS full_name,
                              b.booking_date,
                              b.deceased_address
                            FROM booking_tb AS b
                            JOIN users AS u ON b.customerID = u.id
                            LEFT JOIN services_tb AS s ON b.service_id = s.service_id 
                            WHERE b.status='Pending'";
          
          $stmt = $conn->prepare($pending_query);
          $stmt->execute();
          $pending_result = $stmt->get_result();
          
          if ($pending_result->num_rows > 0) {
            while ($booking = $pending_result->fetch_assoc()) {
              echo '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover">';
              echo '<td class="p-3 sm:p-4 text-xs sm:text-sm text-sidebar-text whitespace-nowrap">' . htmlspecialchars($booking['full_name']) . '</td>';
              echo '<td class="p-3 sm:p-4 text-xs sm:text-sm text-sidebar-text whitespace-nowrap">' . htmlspecialchars($booking['service_name']) . '</td>';
              echo '<td class="p-3 sm:p-4 text-xs sm:text-sm text-sidebar-text whitespace-nowrap">' . date('M j, Y', strtotime($booking['booking_date'])) . '</td>';
              echo '<td class="p-3 sm:p-4 text-xs sm:text-sm text-sidebar-text">' . htmlspecialchars($booking['deceased_address']) . '</td>';
              echo '<td class="p-3 sm:p-4 text-xs sm:text-sm">';
              echo '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs whitespace-nowrap">Pending</span>';
              echo '</td>';
              echo '</tr>';
            }
          } else {
            echo '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover">';
            echo '<td colspan="5" class="p-3 sm:p-4 text-xs sm:text-sm text-sidebar-text text-center">No pending bookings found</td>';
            echo '</tr>';
          }
          ?>
        </tbody>
      </table>
    </div>
    <div class="p-3 sm:p-4 border-t border-sidebar-border flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 sm:gap-0">
      <div class="text-xs sm:text-sm text-gray-500">
        Showing <?php echo $pending_result->num_rows; ?> pending bookings
      </div>
    </div>
  </div>

  <!-- Recent Inventory Activity -->
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-6 sm:mb-8">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center p-4 sm:p-5 border-b border-sidebar-border gap-3 sm:gap-0">
          <h3 class="font-medium text-sidebar-text">Recent Inventory Activity</h3>
          <button class="px-3 sm:px-4 py-2 bg-sidebar-accent text-white rounded-md text-xs sm:text-sm flex items-center hover:bg-darkgold transition-all duration-300 w-full sm:w-auto justify-center sm:justify-start">
              <i class="fas fa-box mr-2"></i> <span class="hidden sm:inline">Manage </span>Inventory
          </button>
      </div>
      <div class="overflow-x-auto scrollbar-thin mobile-table-scroll">
          <table class="w-full">
              <thead>
                  <tr class="bg-sidebar-hover">
                      <th class="p-3 sm:p-4 text-left text-xs sm:text-sm font-medium text-sidebar-text whitespace-nowrap">Item</th>
                      <th class="p-3 sm:p-4 text-left text-xs sm:text-sm font-medium text-sidebar-text whitespace-nowrap">ID</th>
                      <th class="p-3 sm:p-4 text-left text-xs sm:text-sm font-medium text-sidebar-text whitespace-nowrap">Action</th>
                      <th class="p-3 sm:p-4 text-left text-xs sm:text-sm font-medium text-sidebar-text whitespace-nowrap">Date</th>
                      <th class="p-3 sm:p-4 text-left text-xs sm:text-sm font-medium text-sidebar-text whitespace-nowrap">Quantity</th>
                  </tr>
              </thead>
              <tbody id="inventoryLogsBody">
                  <!-- Loading indicator row -->
                  <tr id="inventoryLoadingIndicator" class="border-b border-sidebar-border">
                      <td colspan="5" class="p-3 sm:p-4 text-xs sm:text-sm text-center text-sidebar-text">
                          <i class="fas fa-circle-notch fa-spin mr-2"></i> Loading inventory activity...
                      </td>
                  </tr>
              </tbody>
          </table>
      </div>
      <div class="p-3 sm:p-4 border-t border-sidebar-border flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-0">
          <div id="inventoryPaginationInfo" class="text-xs sm:text-sm text-gray-500">Loading...</div>
          <div id="paginationControls" class="flex space-x-1 overflow-x-auto"></div>
      </div>
  </div>

  <!-- Footer -->
  <footer class="bg-white rounded-lg shadow-sidebar border border-sidebar-border p-3 sm:p-4 text-center text-xs sm:text-sm text-gray-500 mt-6 sm:mt-8">
    <p> 2025 GrievEase.</p>
  </footer>
</div>


  <script src="sidebar.js"></script>
  <script src="tailwind.js"></script>
<script>
// Mobile hamburger menu functionality
document.addEventListener('DOMContentLoaded', function() {
    const hamburgerMenu = document.getElementById('hamburger-menu');
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    
    // Toggle sidebar on hamburger click
    if (hamburgerMenu) {
        hamburgerMenu.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            sidebarOverlay.classList.toggle('show');
        });
    }
    
    // Close sidebar when clicking overlay
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        });
    }
    
    // Close sidebar when clicking a link (mobile only)
    const sidebarLinks = sidebar.querySelectorAll('a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 1023) {
                sidebar.classList.remove('show');
                sidebarOverlay.classList.remove('show');
            }
        });
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1023) {
            sidebar.classList.remove('show');
            sidebarOverlay.classList.remove('show');
        }
    });
});
</script>
<script>
    // Revenue Toggle Functionality
  document.getElementById('revenue-toggle').addEventListener('click', function() {
    const dropdown = document.getElementById('revenue-dropdown');
    dropdown.classList.toggle('hidden');
  });

  function toggleRevenue(type) {
    const cashElement = document.getElementById('cash-revenue');
    const accrualElement = document.getElementById('accrual-revenue');
    const typeElement = document.getElementById('revenue-type');
    const dropdown = document.getElementById('revenue-dropdown');
    
    if (type === 'cash') {
      cashElement.classList.remove('hidden');
      accrualElement.classList.add('hidden');
      typeElement.textContent = 'Cash';
    } else {
      cashElement.classList.add('hidden');
      accrualElement.classList.remove('hidden');
      typeElement.textContent = 'Accrual';
    }
    
    dropdown.classList.add('hidden');
  }

  // Close dropdown when clicking outside
  document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('revenue-dropdown');
    const toggle = document.getElementById('revenue-toggle');
    
    if (!toggle.contains(event.target) && !dropdown.contains(event.target)) {
      dropdown.classList.add('hidden');
    }
  });
</script>
<script>
// Load inventory logs when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadInventoryLogs(1);
});

function loadInventoryLogs(page = 1) {
    const loadingIndicator = document.getElementById('inventoryLoadingIndicator');
    const tableBody = document.getElementById('inventoryLogsBody');
    const paginationInfo = document.getElementById('inventoryPaginationInfo');
    const paginationControls = document.getElementById('paginationControls');
    
    loadingIndicator.classList.remove('hidden');
    tableBody.innerHTML = '';
    
    fetch(`indexFunctions/fetch_inventory_logs.php?page=${page}&branch=<?php echo $branch; ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear any existing rows
                tableBody.innerHTML = '';
                
                // Populate table with logs
                data.logs.forEach(log => {
                    const branchName = log.branch_name
                    ? log.branch_name.charAt(0).toUpperCase() + log.branch_name.slice(1)
                    : 'N/A';
                    const row = document.createElement('tr');
                    row.className = 'border-b border-sidebar-border hover:bg-sidebar-hover transition-colors';
                    
                    // Determine badge styling
                    const badgeStyles = {
                        'Depleted': 'bg-red-100 text-red-600 border-red-200',
                        'Low Stock': 'bg-yellow-100 text-yellow-600 border-yellow-200',
                        'Restocked': 'bg-green-100 text-green-600 border-green-200',
                        'Added': 'bg-green-100 text-green-600 border-green-200',
                        'Removed': 'bg-orange-100 text-orange-600 border-orange-200',
                        'Adjusted': 'bg-blue-100 text-blue-600 border-blue-200'
                    };
                    
                    const badgeClass = badgeStyles[log.activity_type] || 'bg-gray-100 text-gray-600 border-gray-200';
                    const badgeIcon = getActivityIcon(log.activity_type);
                    
                    // Format date
                    const activityDate = new Date(log.activity_date);
                    const formattedDate = activityDate.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    // Format quantity
                    const quantityDisplay = formatQuantityChange(log.quantity_change, log.old_quantity, log.new_quantity);

                    row.innerHTML = `
                        <td class="px-4 py-3.5 text-sm text-sidebar-text">${log.item_name}</td>
                        <td class="px-4 py-3.5 text-sm text-sidebar-text">
                            <span class="inline-block bg-gray-100 rounded-full px-2 py-1 text-xs font-medium">
                                ID: ${log.inventory_id}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badgeClass} border">
                                <i class="fas ${badgeIcon} mr-1"></i> ${log.activity_type}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 text-sm text-sidebar-text">${formattedDate}</td>
                        <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">
                            ${quantityDisplay}
                        </td>
                    `;
                    tableBody.appendChild(row);
                });
                
                // Update pagination info
                updatePaginationInfo(paginationInfo, page, data.perPage, data.total);
                
                // Update pagination controls
                updatePaginationControls(paginationControls, page, data.perPage, data.total, 'loadInventoryLogs');
                
            } else {
                showError(tableBody, data.error);
            }
        })
        .catch(error => {
            showError(tableBody, error.message);
        })
        .finally(() => {
            loadingIndicator.classList.add('hidden');
        });
}

// Helper function to get appropriate icon for activity type
function getActivityIcon(activityType) {
    const icons = {
        'Depleted': 'fa-exclamation-circle',
        'Low Stock': 'fa-exclamation-triangle',
        'Restocked': 'fa-boxes',
        'Added': 'fa-plus-circle',
        'Removed': 'fa-minus-circle',
        'Adjusted': 'fa-adjust'
    };
    return icons[activityType] || 'fa-info-circle';
}

// Helper function to format quantity display
function formatQuantityChange(change, oldQty, newQty) {
    const changeSymbol = change > 0 ? '+' : '';
    return `
        <div class="flex flex-col">
            <span class="font-medium ${change > 0 ? 'text-green-600' : change < 0 ? 'text-red-600' : 'text-gray-600'}">
                ${changeSymbol}${change}
            </span>
            <span class="text-xs text-gray-500 mt-1">
                ${oldQty} → ${newQty}
            </span>
        </div>
    `;
}

// Helper function to update pagination info
function updatePaginationInfo(element, currentPage, perPage, totalItems) {
    const startItem = (currentPage - 1) * perPage + 1;
    const endItem = Math.min(currentPage * perPage, totalItems);
    element.innerHTML = `
        Showing <span class="font-medium">${startItem}-${endItem}</span> of 
        <span class="font-medium">${totalItems}</span> activities
    `;
}

// Helper function to update pagination controls
function updatePaginationControls(container, currentPage, perPage, totalItems, callbackFunction) {
    const totalPages = Math.ceil(totalItems / perPage);
    
    let html = `
        <button class="px-3.5 py-1.5 border rounded text-sm ${
            currentPage <= 1 ? 'border-gray-300 text-gray-400 cursor-not-allowed' : 'border-sidebar-border hover:bg-sidebar-hover'
        }" ${currentPage <= 1 ? 'disabled' : ''} onclick="${callbackFunction}(${currentPage - 1})">
            &laquo;
        </button>
    `;
    
    // Show page numbers
    for (let i = 1; i <= totalPages; i++) {
        html += `
            <button class="px-3.5 py-1.5 border rounded text-sm ${
                i === currentPage ? 'bg-sidebar-accent text-white border-sidebar-accent' : 'border-sidebar-border hover:bg-sidebar-hover'
            }" onclick="${callbackFunction}(${i})">
                ${i}
            </button>
        `;
    }
    
    html += `
        <button class="px-3.5 py-1.5 border rounded text-sm ${
            currentPage >= totalPages ? 'border-gray-300 text-gray-400 cursor-not-allowed' : 'border-sidebar-border hover:bg-sidebar-hover'
        }" ${currentPage >= totalPages ? 'disabled' : ''} onclick="${callbackFunction}(${currentPage + 1})">
            &raquo;
        </button>
    `;
    
    container.innerHTML = html;
}

// Helper function to show error message
function showError(container, message) {
    container.innerHTML = `
        <tr>
            <td colspan="5" class="px-4 py-3.5 text-sm text-center text-red-600">
                <i class="fas fa-exclamation-circle mr-2"></i>
                Error loading data: ${escapeHtml(message)}
            </td>
        </tr>
    `;
}

// Helper function to escape HTML
function escapeHtml(unsafe) {
    if (!unsafe) return '';
    return unsafe.toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
        }
</script>

<!-- Add this to your existing JavaScript at the bottom of the file -->
<script>
// Improved notification bell functionality
document.getElementById('notification-bell').addEventListener('click', function(event) {
  event.stopPropagation();
  const dropdown = document.getElementById('notifications-dropdown');
  
  if (dropdown.classList.contains('hidden')) {
    // Show dropdown with animation
    dropdown.classList.remove('hidden');
    setTimeout(() => {
      dropdown.classList.remove('opacity-0', 'translate-y-2');
    }, 10);
  } else {
    // Hide dropdown with animation
    dropdown.classList.add('opacity-0', 'translate-y-2');
    setTimeout(() => {
      dropdown.classList.add('hidden');
    }, 300);
  }
  
  // If showing notifications, mark as read (update counter)
  if (!dropdown.classList.contains('hidden')) {
    const notificationCounter = document.querySelector('#notification-bell > span');
    
    // Add a slight delay before animating the counter
    setTimeout(() => {
      notificationCounter.classList.add('scale-75', 'opacity-50');
      
      setTimeout(() => {
        notificationCounter.textContent = '0';
        notificationCounter.classList.add('scale-0');
        
        setTimeout(() => {
          if (notificationCounter.textContent === '0') {
            notificationCounter.classList.add('hidden');
          }
        }, 300);
      }, 500);
    }, 2000);
  }
});

// Close notification dropdown when clicking outside
document.addEventListener('click', function(event) {
  const notificationsDropdown = document.getElementById('notifications-dropdown');
  const notificationBell = document.getElementById('notification-bell');
  
  if (notificationsDropdown && notificationBell && !notificationBell.contains(event.target) && !notificationsDropdown.contains(event.target)) {
    // Hide with animation
    notificationsDropdown.classList.add('opacity-0', 'translate-y-2');
    setTimeout(() => {
      notificationsDropdown.classList.add('hidden');
    }, 300);
  }
});

// Add smooth transitions for notification counter badge
document.querySelector('#notification-bell > span').classList.add('transition-all', 'duration-300');

// Add animation to new notifications - subtle pulse effect
document.addEventListener('DOMContentLoaded', function() {
  const unreadIndicators = document.querySelectorAll('#notifications-dropdown .bg-blue-600, #notifications-dropdown .bg-yellow-600, #notifications-dropdown .bg-green-600');
  
  unreadIndicators.forEach(indicator => {
    setInterval(() => {
      indicator.classList.add('scale-125', 'opacity-70');
      setTimeout(() => {
        indicator.classList.remove('scale-125', 'opacity-70');
      }, 500);
    }, 3000);
  });
});
</script>
</body>
</html>