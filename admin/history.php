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

$customer_query = "SELECT id, CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS full_name 
                  FROM users 
                  WHERE user_type = 3 
                  ORDER BY last_name, first_name";
$customer_result = mysqli_query($conn, $customer_query);
$customers = [];
while ($row = mysqli_fetch_assoc($customer_result)) {
    $customers[] = $row;
}

// Pagination variables
$recordsPerPage = 5; // Number of records per page

// Ongoing Services Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offsetOngoing = ($page - 1) * $recordsPerPage;

// Fully Paid Services Pagination
$fullyPaidPage = isset($_GET['fullyPaidPage']) ? max(1, intval($_GET['fullyPaidPage'])) : 1;
$offsetFullyPaid = ($fullyPaidPage - 1) * $recordsPerPage;

// Outstanding Balance Services Pagination
$outstandingPage = isset($_GET['outstandingPage']) ? max(1, intval($_GET['outstandingPage'])) : 1;
$offsetOutstanding = ($outstandingPage - 1) * $recordsPerPage;

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - History</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body.modal-open {
      overflow: hidden;
    }
    .modal-scrollable {
      max-height: 80vh;
      overflow-y: auto;
    }

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

<!-- Main Content -->
<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">History</h1>
    </div>
  </div>

  <!-- Ongoing Services Section -->
<div id="ongoing-services-management" class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
  <!-- Header Section - Made responsive with better stacking -->
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
    <!-- Desktop layout for big screens - Title on left, controls on right -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
      <!-- Title and Counter -->
      <div class="flex items-center gap-3 mb-4 lg:mb-0">
        <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Ongoing Services</h4>
        
        <?php
        // Count total ongoing services (status = 'Pending')
        $countQuery = "SELECT COUNT(*) as total FROM sales_tb WHERE status = 'Pending'";
$countResult = $conn->query($countQuery);
$totalOngoing = $countResult->fetch_assoc()['total'];
        ?>
        
        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
          <?php echo $totalOngoing . ($totalOngoing != 1 ? "" : ""); ?>
        </span>
      </div>
      
      <!-- Controls for big screens - aligned right -->
      <div class="hidden lg:flex items-center gap-3">
        <!-- Search Input -->
        <div class="relative">
          <input type="text" id="searchOngoing" 
                placeholder="Search services..." 
                class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
          <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
        </div>

        <!-- Filter Dropdown -->
        <div class="relative filter-dropdown">
          <button id="filterToggle" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
            <i class="fas fa-filter text-sidebar-accent"></i>
            <span>Filters</span>
            <?php if(isset($sortFilter) && $sortFilter): ?>
              <span class="h-2 w-2 bg-sidebar-accent rounded-full"></span>
            <?php endif; ?>
          </button>
          
          <!-- Filter Window -->
          <div id="filterDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
            <div class="space-y-4">
              <!-- Sort Options -->
              <div>
                <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                <div class="space-y-1">
                  <div class="flex items-center cursor-pointer" data-sort="id_asc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      ID: Ascending
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="id_desc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      ID: Descending
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="client_asc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      Client: A-Z
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="client_desc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      Client: Z-A
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="date_asc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      Date: Oldest First
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="date_desc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      Date: Newest First
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
    <div class="lg:hidden w-full mt-4">
      <!-- First row: Search bar with filter and archive icons on the right -->
      <div class="flex items-center w-full gap-3 mb-4">
        <!-- Search Input - Takes most of the space -->
        <div class="relative flex-grow">
          <input type="text" id="searchOngoing" 
                  placeholder="Search services..." 
                  class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
          <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>

        <!-- Icon-only buttons for filter and archive -->
        <div class="flex items-center gap-3">
          <!-- Filter Icon Button -->
          <div class="relative filter-dropdown">
            <button id="serviceFilterToggle" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
              <i class="fas fa-filter text-xl"></i>
              <span id="filterIndicator" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
            </button>
            
            <!-- Filter Window - Positioned below the icon -->
            <div id="serviceFilterDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
              <div class="space-y-4">
                <!-- Sort Options -->
                <div>
                  <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                  <div class="space-y-2">
                    <div class="flex items-center cursor-pointer" data-sort="id_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        ID: Ascending
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="id_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        ID: Descending
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        Client: A-Z
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        Client: Z-A
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        Date: Oldest First
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        Date: Newest First
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
  
  <!-- Responsive Table Container with improved spacing -->
  <div class="overflow-x-auto scrollbar-thin" id="ongoingServiceTableContainer">
    <div id="ongoingLoadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
      <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
    </div>
    
    <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
    <div class="min-w-full">
      <table class="w-full">
        <thead>
          <tr class="bg-gray-50 border-b border-sidebar-border">
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(0)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-hashtag text-sidebar-accent"></i> ID 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(1)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-user text-sidebar-accent"></i> Client 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(2)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-user-alt text-sidebar-accent"></i> Deceased 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(3)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-tag text-sidebar-accent"></i> Service Type 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(4)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-calendar text-sidebar-accent"></i> Date of Burial 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(5)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-toggle-on text-sidebar-accent"></i> Status 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(6)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-peso-sign text-sidebar-accent"></i> Outstanding Balance 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-cogs text-sidebar-accent"></i> Actions
              </div>
            </th>
          </tr>
        </thead>
        <tbody id="ongoingServiceTableBody">
          <?php
          // Query for Ongoing Services (status = 'Pending')
          // Modify your ongoingQuery to include a check for assigned staff
          $ongoingQuery = "SELECT s.sales_id, s.fname, s.mname, s.lname, s.suffix, 
                s.fname_deceased, s.mname_deceased, s.lname_deceased, s.suffix_deceased,
                sv.service_name, s.date_of_burial, s.balance, s.status, s.customerID, s.payment_status,
                (SELECT COUNT(*) FROM employee_service_payments esp WHERE esp.sales_id = s.sales_id) AS staff_assigned
                FROM sales_tb s
                JOIN services_tb sv ON s.service_id = sv.service_id
                WHERE s.status = 'Pending'
                LIMIT $offsetOngoing, $recordsPerPage";
          $ongoingResult = $conn->query($ongoingQuery);
          
          if ($ongoingResult->num_rows > 0) {
            while($row = $ongoingResult->fetch_assoc()) {
              $clientName = htmlspecialchars($row['fname'] . ' ' . 
                          ($row['mname'] ? $row['mname'] . ' ' : '') . 
                          $row['lname'] . 
                          ($row['suffix'] ? ' ' . $row['suffix'] : ''));
                          
              $deceasedName = htmlspecialchars($row['fname_deceased'] . ' ' . 
                              ($row['mname_deceased'] ? $row['mname_deceased'] . ' ' : '') . 
                              $row['lname_deceased'] . 
                              ($row['suffix_deceased'] ? ' ' . $row['suffix_deceased'] : ''));
              ?>
              <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#<?php echo $row['sales_id']; ?></td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo $clientName; ?></td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo $deceasedName; ?></td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                    <?php echo htmlspecialchars($row['service_name']); ?>
                  </span>
                </td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['date_of_burial']); ?></td>
                <td class="px-4 py-3.5 text-sm">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-500 border border-orange-200">
                    <i class="fas fa-pause-circle mr-1"></i> <?php echo htmlspecialchars($row['status']); ?>
                  </span>
                </td>
                <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">₱<?php echo number_format($row['balance'], 2); ?></td>
                <td class="px-4 py-3.5 text-sm">
                  <div class="flex space-x-2">
                    <button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" title="Edit Service" onclick="openEditServiceModal('<?php echo $row['sales_id']; ?>')">
                      <i class="fas fa-edit"></i>
                    </button>
                    <?php if ($row['staff_assigned'] == 0): ?>
                      <button class="p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 transition-all tooltip assign-staff-btn" 
                              title="Assign Staff"
                              onclick="checkCustomerBeforeAssign('<?php echo $row['sales_id']; ?>', <?php echo $row['customerID'] ? 'true' : 'false'; ?>)"
                              <?php echo !$row['customerID'] ? 'disabled' : ''; ?>>
                        <i class="fas fa-users"></i>
                      </button>
                    <?php endif; ?>
                    <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip complete-btn" 
                            title="Complete Service"
                            onclick="checkCustomerBeforeComplete('<?php echo $row['sales_id']; ?>', <?php echo $row['customerID'] ? 'true' : 'false'; ?>)"
                            <?php echo !$row['customerID'] ? 'disabled' : ''; ?>>
                      <i class="fas fa-check"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php
            }
          } else {
            ?>
            <tr>
              <td colspan="8" class="p-6 text-sm text-center">
                <div class="flex flex-col items-center">
                  <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                  <p class="text-gray-500">No ongoing services found</p>
                </div>
              </td>
            </tr>
            <?php
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Sticky Pagination Footer with improved spacing -->
<div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
        Showing <?php echo ($offsetOngoing + 1) . ' - ' . min($offsetOngoing + $recordsPerPage, $totalOngoing); ?> of <?php echo $totalOngoing; ?> services
    </div>
    <div id="paginationContainer" class="flex space-x-2">
        <?php 
        $totalPagesOngoing = ceil($totalOngoing / $recordsPerPage);
        
        if ($totalPagesOngoing > 1): 
        ?>
            <!-- First page button (double arrow) -->
            <a href="?page=1" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &laquo;
            </a>
            
            <!-- Previous page button (single arrow) -->
            <a href="?page=<?php echo max(1, $page - 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &lsaquo;
            </a>
            
            <?php
            // Show exactly 3 page numbers
            if ($totalPagesOngoing <= 3) {
                // If total pages is 3 or less, show all pages
                $start_page = 1;
                $end_page = $totalPagesOngoing;
            } else {
                // With more than 3 pages, determine which 3 to show
                if ($page == 1) {
                    // At the beginning, show first 3 pages
                    $start_page = 1;
                    $end_page = 3;
                } elseif ($page == $totalPagesOngoing) {
                    // At the end, show last 3 pages
                    $start_page = $totalPagesOngoing - 2;
                    $end_page = $totalPagesOngoing;
                } else {
                    // In the middle, show current page with one before and after
                    $start_page = $page - 1;
                    $end_page = $page + 1;
                    
                    // Handle edge cases
                    if ($start_page < 1) {
                        $start_page = 1;
                        $end_page = 3;
                    }
                    if ($end_page > $totalPagesOngoing) {
                        $end_page = $totalPagesOngoing;
                        $start_page = $totalPagesOngoing - 2;
                    }
                }
            }
            
            // Generate the page buttons
            for ($i = $start_page; $i <= $end_page; $i++): 
            ?>
                <a href="?page=<?php echo $i; ?>" class="px-3.5 py-1.5 rounded text-sm <?php echo ($i == $page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover'; ?>">
                    <?php echo $i; ?>
                </a>
            <?php 
            endfor; 
            ?>
            
            <!-- Next page button (single arrow) -->
            <a href="?page=<?php echo min($totalPagesOngoing, $page + 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == $totalPagesOngoing) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &rsaquo;
            </a>
            
            <!-- Last page button (double arrow) -->
            <a href="?page=<?php echo $totalPagesOngoing; ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == $totalPagesOngoing) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &raquo;
            </a>
        <?php endif; ?>
    </div>
</div>
</div>

  <!-- Past Services - Fully Paid Section -->
<div id="past-services-fully-paid" class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
  <!-- Header Section - Made responsive with better stacking -->
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
    <!-- Desktop layout for big screens - Title on left, controls on right -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
      <!-- Title and Counter -->
      <div class="flex items-center gap-3 mb-4 lg:mb-0">
        <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Past Services - Fully Paid</h4>
        
        <?php
        // Count total fully paid services
        $countQuery = "SELECT COUNT(*) as total FROM sales_tb WHERE status = 'Completed' AND payment_status = 'Fully Paid' AND balance = 0";
$countResult = $conn->query($countQuery);
$totalServices = $countResult->fetch_assoc()['total'];
        ?>
        
        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
          <?php echo $totalServices . ($totalServices != 1 ? "" : ""); ?>
        </span>
      </div>
      
      <!-- Controls for big screens - aligned right -->
      <div class="hidden lg:flex items-center gap-3">
        <!-- Search Input -->
        <div class="relative">
          <input type="text" id="searchFullyPaid" 
                placeholder="Search services..." 
                class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                oninput="debouncedFilterFullyPaid()">
          <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
        </div>

        <!-- Filter Dropdown -->
        <div class="relative filter-dropdown">
          <button id="filterToggleFullyPaid" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
            <i class="fas fa-filter text-sidebar-accent"></i>
            <span>Filters</span>
            <?php if(isset($sortFilter) && $sortFilter): ?>
              <span class="h-2 w-2 bg-sidebar-accent rounded-full"></span>
            <?php endif; ?>
          </button>
          
          <!-- Filter Window -->
          <div id="filterDropdownFullyPaid" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
            <div class="space-y-4">
              <!-- Sort Options -->
              <div>
                <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                <div class="space-y-1">
                  <div class="flex items-center cursor-pointer" data-sort="id_asc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      ID: Ascending
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="id_desc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      ID: Descending
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="client_asc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      Client: A-Z
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="client_desc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      Client: Z-A
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="deceased_asc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      Deceased: A-Z
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="deceased_desc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      Deceased: Z-A
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="date_asc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      Date: Oldest First
                    </span>
                  </div>
                  <div class="flex items-center cursor-pointer" data-sort="date_desc">
                    <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                      Date: Newest First
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
    <div class="lg:hidden w-full mt-4">
      <!-- First row: Search bar with filter icon on the right -->
      <div class="flex items-center w-full gap-3 mb-4">
        <!-- Search Input - Takes most of the space -->
        <div class="relative flex-grow">
          <input type="text" id="searchFullyPaidMobile" 
                  placeholder="Search services..." 
                  class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                  oninput="debouncedFilterFullyPaid()">
          <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>

        <!-- Icon-only button for filter -->
        <div class="flex items-center gap-3">
          <!-- Filter Icon Button -->
          <div class="relative filter-dropdown">
            <button id="filterToggleFullyPaidMobile" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
              <i class="fas fa-filter text-xl"></i>
              <span id="filterIndicatorFullyPaid" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
            </button>
            
            <!-- Filter Window - Positioned below the icon -->
            <div id="filterDropdownFullyPaidMobile" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
              <div class="space-y-4">
                <!-- Sort Options -->
                <div>
                  <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                  <div class="space-y-2">
                    <div class="flex items-center cursor-pointer" data-sort="id_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        ID: Ascending
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="id_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        ID: Descending
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        Client: A-Z
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        Client: Z-A
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        Date: Oldest First
                      </span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        Date: Newest First
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Responsive Table Container with improved spacing -->
  <div class="overflow-x-auto scrollbar-thin" id="fullyPaidTableContainer">
    <div id="loadingIndicatorFullyPaid" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
      <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
    </div>
    
    <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
    <div class="min-w-full">
      <table class="w-full">
        <thead>
          <tr class="bg-gray-50 border-b border-sidebar-border">
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(0)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-hashtag text-sidebar-accent"></i> ID 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(1)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-user text-sidebar-accent"></i> Client
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(2)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-user-alt text-sidebar-accent"></i> Deceased
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(3)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-tag text-sidebar-accent"></i> Service Type
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(4)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-calendar text-sidebar-accent"></i> Date of Burial
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(5)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-toggle-on text-sidebar-accent"></i> Status
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-cogs text-sidebar-accent"></i> Actions
              </div>
            </th>
          </tr>
        </thead>
        <tbody id="fullyPaidTableBody">
          <?php
          // Query for Past Services - Fully Paid (status = 'Completed' AND payment_status = 'Fully Paid' AND balance = 0)
          $fullyPaidQuery = "SELECT s.sales_id, s.fname, s.mname, s.lname, s.suffix, 
                  s.fname_deceased, s.mname_deceased, s.lname_deceased, s.suffix_deceased,
                  sv.service_name, s.date_of_burial, s.balance, s.status, s.payment_status
                  FROM sales_tb s
                  JOIN services_tb sv ON s.service_id = sv.service_id
                  WHERE s.status = 'Completed' AND s.payment_status = 'Fully Paid' AND s.balance = 0
                  LIMIT $offsetFullyPaid, $recordsPerPage";
          $fullyPaidResult = $conn->query($fullyPaidQuery);
          
          if ($fullyPaidResult->num_rows > 0) {
            while($row = $fullyPaidResult->fetch_assoc()) {
              $clientName = htmlspecialchars($row['fname'] . ' ' . 
                          ($row['mname'] ? $row['mname'] . ' ' : '') . 
                          $row['lname'] . 
                          ($row['suffix'] ? ' ' . $row['suffix'] : ''));
                          
              $deceasedName = htmlspecialchars($row['fname_deceased'] . ' ' . 
                              ($row['mname_deceased'] ? $row['mname_deceased'] . ' ' : '') . 
                              $row['lname_deceased'] . 
                              ($row['suffix_deceased'] ? ' ' . $row['suffix_deceased'] : ''));
              ?>
              <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#<?php echo $row['sales_id']; ?></td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo $clientName; ?></td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo $deceasedName; ?></td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['service_name']); ?></td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['date_of_burial']); ?></td>
                <td class="px-4 py-3.5 text-sm">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-500 border border-green-200">
                    <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars($row['status']); ?>
                  </span>
                </td>
                <td class="px-4 py-3.5 text-sm">
                  <div class="flex space-x-2">
                    <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details" onclick="viewServiceDetails('<?php echo $row['sales_id']; ?>')">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php
            }
          } else {
            ?>
            <tr>
              <td colspan="7" class="px-4 py-6 text-sm text-center">
                <div class="flex flex-col items-center">
                  <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                  <p class="text-gray-500">No fully paid past services found</p>
                </div>
              </td>
            </tr>
            <?php
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Sticky Pagination Footer with improved spacing -->
<div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="paginationInfoFullyPaid" class="text-sm text-gray-500 text-center sm:text-left">
        <?php 
        if ($totalServices > 0) {
            $start = $offsetFullyPaid + 1;
            $end = min($offsetFullyPaid + $recordsPerPage, $totalServices);
            
            echo "Showing {$start} - {$end} of {$totalServices} services";
        } else {
            echo "No services found";
        }
        ?>
    </div>
    <div id="paginationContainerFullyPaid" class="flex space-x-2">
        <?php
        // Calculate total pages before the if condition
        $totalPagesFullyPaid = ceil($totalServices / $recordsPerPage);
        
        // Always show pagination controls, even if there's only one page
        // First page button (double arrow)
        ?>
        <a href="?fullyPaidPage=1" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($fullyPaidPage == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &laquo;
        </a>
        
        <!-- Previous page button (single arrow) -->
        <a href="?fullyPaidPage=<?php echo max(1, $fullyPaidPage - 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($fullyPaidPage == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &lsaquo;
        </a>
        
        <?php
        // Show exactly 3 page numbers when possible
        if ($totalPagesFullyPaid <= 3) {
            // If total pages is 3 or less, show all pages
            $startPage = 1;
            $endPage = max(1, $totalPagesFullyPaid);
        } else {
            // With more than 3 pages, determine which 3 to show
            if ($fullyPaidPage == 1) {
                // At the beginning, show first 3 pages
                $startPage = 1;
                $endPage = 3;
            } elseif ($fullyPaidPage == $totalPagesFullyPaid) {
                // At the end, show last 3 pages
                $startPage = $totalPagesFullyPaid - 2;
                $endPage = $totalPagesFullyPaid;
            } else {
                // In the middle, show current page with one before and after
                $startPage = $fullyPaidPage - 1;
                $endPage = $fullyPaidPage + 1;
                
                // Handle edge cases
                if ($startPage < 1) {
                    $startPage = 1;
                    $endPage = 3;
                }
                if ($endPage > $totalPagesFullyPaid) {
                    $endPage = $totalPagesFullyPaid;
                    $startPage = max(1, $totalPagesFullyPaid - 2);
                }
            }
        }
        
        // Generate the page buttons
        for ($i = $startPage; $i <= $endPage; $i++) {
            $active_class = ($i == $fullyPaidPage) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
            echo '<a href="?fullyPaidPage=' . $i . '" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</a>';
        }
        ?>
        
        <!-- Next page button (single arrow) -->
        <a href="?fullyPaidPage=<?php echo min(max(1, $totalPagesFullyPaid), $fullyPaidPage + 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($fullyPaidPage >= $totalPagesFullyPaid) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &rsaquo;
        </a>
        
        <!-- Last page button (double arrow) -->
        <a href="?fullyPaidPage=<?php echo max(1, $totalPagesFullyPaid); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($fullyPaidPage >= $totalPagesFullyPaid) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &raquo;
        </a>
    </div>
</div>
</div>

  <!-- Past Services - With Outstanding Balance Section -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden branch-container">
    <!-- Header Section - Made responsive with better stacking -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
        <!-- Desktop layout for big screens - Title on left, controls on right -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Title and Counter -->
            <div class="flex items-center gap-3 mb-4 lg:mb-0">
                <h3 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Past Services - With Outstanding Balance</h3>

                <?php
                // Count total with outstanding balance
$countQuery = "SELECT COUNT(*) as total FROM sales_tb WHERE status = 'Completed' AND payment_status = 'With Balance'";
$countResult = $conn->query($countQuery);
$totalOutstanding = $countResult->fetch_assoc()['total'];
?>
                
                <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                  <?php echo $totalWithBalance . ($totalWithBalance != 1 ? "" : ""); ?>
                </span>
            </div>
            
            <!-- Controls for big screens - aligned right -->
            <div class="hidden lg:flex items-center gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <input type="text" id="searchOutstanding" 
                           placeholder="Search records..." 
                           class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                           oninput="debouncedOutstandingFilter()">
                    <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
                </div>

                <!-- Archive Button -->
                <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover whitespace-nowrap">
                    <i class="fas fa-archive text-sidebar-accent"></i>
                    <span>Archive</span>
                </button>

                <!-- Export Button -->
                <button class="px-4 py-2 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap" 
                        onclick="exportOutstandingBalances()">
                    <i class="fas fa-file-download"></i> Export Data
                </button>
            </div>
        </div>
    
        <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
        <div class="lg:hidden w-full mt-4">
            <!-- First row: Search bar with archive icon on the right -->
            <div class="flex items-center w-full gap-3 mb-4">
                <!-- Search Input - Takes most of the space -->
                <div class="relative flex-grow">
                    <input type="text" id="searchOutstanding" 
                           placeholder="Search records..." 
                           class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                           oninput="debouncedOutstandingFilter()">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>

                <!-- Archive Icon Button -->
                <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
                    <i class="fas fa-archive text-xl"></i>
                </button>
            </div>

            <!-- Second row: Export Button - Full width -->
            <div class="w-full">
                <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap w-full justify-center" 
                        onclick="exportOutstandingBalances()">
                    <i class="fas fa-file-download"></i> Export Data
                </button>
            </div>
        </div>
    </div>
    
    <!-- Responsive Table Container with improved spacing -->
    <div class="overflow-x-auto scrollbar-thin" id="outstandingTableContainer">
        <div id="outstandingLoadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
        </div>
        
        <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
        <div class="min-w-full">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-sidebar-border">
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortOutstandingTable(0)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-hashtag text-sidebar-accent"></i> ID 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortOutstandingTable(1)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-user text-sidebar-accent"></i> Client
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortOutstandingTable(2)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-user-circle text-sidebar-accent"></i> Deceased
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortOutstandingTable(3)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-tag text-sidebar-accent"></i> Service Type 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortOutstandingTable(4)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-calendar text-sidebar-accent"></i> Date of Burial 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortOutstandingTable(5)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-toggle-on text-sidebar-accent"></i> Status 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortOutstandingTable(6)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-peso-sign text-sidebar-accent"></i> Outstanding Balance 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-cogs text-sidebar-accent"></i> Actions
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="outstandingTableBody">
                    <?php
                    // Query for Past Services - With Balance (status = 'Completed' AND payment_status = 'With Balance')
                    $withBalanceQuery = "SELECT s.sales_id, s.fname, s.mname, s.lname, s.suffix, 
                    s.fname_deceased, s.mname_deceased, s.lname_deceased, s.suffix_deceased,
                    sv.service_name, s.date_of_burial, s.balance, s.status, s.payment_status
                    FROM sales_tb s
                    JOIN services_tb sv ON s.service_id = sv.service_id
                    WHERE s.status = 'Completed' AND s.payment_status = 'With Balance'
                    LIMIT $offsetOutstanding, $recordsPerPage";
                    $withBalanceResult = $conn->query($withBalanceQuery);
                    
                    if ($withBalanceResult->num_rows > 0) {
                        while($row = $withBalanceResult->fetch_assoc()) {
                            $clientName = htmlspecialchars($row['fname'] . ' ' . 
                                        ($row['mname'] ? $row['mname'] . ' ' : '') . 
                                        $row['lname'] . 
                                        ($row['suffix'] ? ' ' . $row['suffix'] : ''));
                                        
                            $deceasedName = htmlspecialchars($row['fname_deceased'] . ' ' . 
                                        ($row['mname_deceased'] ? $row['mname_deceased'] . ' ' : '') . 
                                        $row['lname_deceased'] . 
                                        ($row['suffix_deceased'] ? ' ' . $row['suffix_deceased'] : ''));
                            ?>
                            <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                                <td class="px-4 py-4 text-sm text-sidebar-text font-medium">#<?php echo $row['sales_id']; ?></td>
                                <td class="px-4 py-4 text-sm text-sidebar-text"><?php echo $clientName; ?></td>
                                <td class="px-4 py-4 text-sm text-sidebar-text"><?php echo $deceasedName; ?></td>
                                <td class="px-4 py-4 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['service_name']); ?></td>
                                <td class="px-4 py-4 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['date_of_burial']); ?></td>
                                <td class="px-4 py-4 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-500 border border-yellow-200">
                                        <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($row['payment_status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-sm font-medium text-sidebar-text">₱<?php echo number_format($row['balance'], 2); ?></td>
                                <td class="px-4 py-4 text-sm">
                                    <div class="flex space-x-2">
                                        <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details" onclick="viewServiceDetails('<?php echo $row['sales_id']; ?>')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="p-2 bg-orange-100 text-orange-600 rounded-lg hover:bg-orange-200 transition-all tooltip" title="Record Payment" onclick="openRecordPaymentModal('<?php echo $row['sales_id']; ?>','<?php echo $clientName; ?>','<?php echo $row['balance']; ?>')">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-sm text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                                    <p class="text-gray-500">No past services with outstanding balance found</p>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
  
    <!-- Sticky Pagination Footer with improved spacing -->
<div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="paginationInfoPastWithBal" class="text-sm text-gray-500 text-center sm:text-left">
    <?php 
        // Get the number of records on the current page
        if ($totalOutstanding > 0) {
            $start = $offsetOutstanding + 1;
            $end = min($offsetOutstanding + $recordsPerPage, $totalOutstanding);
        
            echo "Showing {$start} - {$end} of {$totalOutstanding} records";
        } else {
            echo "No records found";
        }
    ?>
    </div>
    
    <div id="paginationContainerPastWithBal" class="flex space-x-2">
    <?php
        $totalPagesOutstanding = ceil($totalOutstanding / $recordsPerPage);
        
        // Make sure we have something to paginate
        if ($totalOutstanding > 0):
    ?>
        <!-- First page button -->
        <a href="?outstandingPage=1" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($outstandingPage <= 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &laquo;
        </a>
        
        <!-- Previous page button -->
        <a href="?outstandingPage=<?php echo max(1, $outstandingPage - 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($outstandingPage <= 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &lsaquo;
        </a>
        
        <?php
        // Show exactly 3 page numbers
        if ($totalPagesOutstanding <= 3) {
            // If total pages is 3 or less, show all pages
            $startPage = 1;
            $endPage = $totalPagesOutstanding;
        } else {
            // With more than 3 pages, determine which 3 to show
            if ($outstandingPage == 1) {
                // At the beginning, show first 3 pages
                $startPage = 1;
                $endPage = 3;
            } elseif ($outstandingPage == $totalPagesOutstanding) {
                // At the end, show last 3 pages
                $startPage = $totalPagesOutstanding - 2;
                $endPage = $totalPagesOutstanding;
            } else {
                // In the middle, show current page with one before and after
                $startPage = $outstandingPage - 1;
                $endPage = $outstandingPage + 1;
                
                // Handle edge cases
                if ($startPage < 1) {
                    $startPage = 1;
                    $endPage = 3;
                }
                if ($endPage > $totalPagesOutstanding) {
                    $endPage = $totalPagesOutstanding;
                    $startPage = $totalPagesOutstanding - 2;
                }
            }
        }
        
        // Generate the page buttons
        for ($i = $startPage; $i <= $endPage; $i++):
            $active_class = ($i == $outstandingPage) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
        ?>
            <a href="?outstandingPage=<?php echo $i; ?>" class="px-3.5 py-1.5 rounded text-sm <?php echo $active_class; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
        
        <!-- Next page button -->
        <a href="?outstandingPage=<?php echo min($totalPagesOutstanding, $outstandingPage + 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($outstandingPage >= $totalPagesOutstanding) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &rsaquo;
        </a>
        
        <!-- Last page button -->
        <a href="?outstandingPage=<?php echo $totalPagesOutstanding; ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($outstandingPage >= $totalPagesOutstanding) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &raquo;
        </a>
    <?php endif; ?>
    </div>
</div>
</div>

  <!-- Modal for Editing Service -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="editServiceModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeEditServiceModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Edit Service
      </h3>
    </div>
    
    <!-- Modal Body - Single Column Layout -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="serviceForm" class="space-y-3 sm:space-y-6">
        <input type="hidden" id="salesId" name="sales_id">
        
        <!-- Customer Information Section -->
        <div class="pb-4 border-b border-gray-200">
          <h4 class="text-lg font-semibold flex items-center text-gray-800 mb-4">
            Customer Information
          </h4>

          <!-- Customer Selection -->
          <div class="form-group mb-4">
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Search Customer
            </label>
            <div class="relative">
              <input 
                type="text" 
                id="customerSearch" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Type customer name..."
                autocomplete="off"
              >
              <div id="customerResults" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto hidden">
                <!-- Results will appear here -->
              </div>
            </div>
            <input type="hidden" id="selectedCustomerId" name="customer_id">
          </div>

          <!-- Customer Name Fields - 2x2 grid maintained for better layout -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                First Name
              </label>
              <input 
                type="text" 
                id="firstName" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="First Name"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Last Name
              </label>
              <input 
                type="text" 
                id="lastName" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Last Name"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Middle Name
              </label>
              <input 
                type="text" 
                id="middleName" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Middle Name"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Suffix
              </label>
              <input 
                type="text" 
                id="nameSuffix" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Suffix"
              >
            </div>
          </div>

          <!-- Contact Information - 2 columns -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Email
              </label>
              <input 
                type="email" 
                id="email" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Enter Email"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Phone
              </label>
              <input 
                type="tel" 
                id="phone" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Enter Phone Number"
              >
            </div>
          </div>

          <!-- Service Selection & Price - 2 columns -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Select Service
              </label>
              <select 
                id="serviceSelect" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              >
                <option value="">Choose Service</option>
                <!-- Options will be populated dynamically -->
              </select>
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Service Price
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">₱</span>
                </div>
                <input 
                  type="number" 
                  id="servicePrice" 
                  class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Enter Service Price"
                >
              </div>
            </div>
          </div>

          <!-- Branch Selection -->
          <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border-l-4 border-gold">
            <label class="block text-xs font-medium text-gray-700 mb-2">Branch</label>
            <div class="flex flex-wrap gap-6">
              <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="branch" value="2" class="hidden peer">
                <div class="w-5 h-5 rounded-full border-2 border-gold flex items-center justify-center peer-checked:bg-gold peer-checked:border-darkgold transition-colors"></div>
                <span class="text-gray-700 font-medium">Pila</span>
              </label>
              <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="branch" value="1" class="hidden peer">
                <div class="w-5 h-5 rounded-full border-2 border-gold flex items-center justify-center peer-checked:bg-gold peer-checked:border-darkgold transition-colors"></div>
                <span class="text-gray-700 font-medium">Paete</span>
              </label>
            </div>
          </div>
        </div>
        
        <!-- Deceased Information Section -->
        <div class="pt-2">
          <h4 class="text-lg font-semibold flex items-center text-gray-800 mb-4">
            Deceased Information
          </h4>
          
          <!-- Deceased Name Fields - 2x2 grid maintained for better layout -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                First Name
              </label>
              <input 
                type="text" 
                id="deceasedFirstName" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="First Name"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Last Name
              </label>
              <input 
                type="text" 
                id="deceasedLastName" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Last Name"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Middle Name
              </label>
              <input 
                type="text" 
                id="deceasedMiddleName" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Middle Name"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Suffix
              </label>
              <input 
                type="text" 
                id="deceasedSuffix" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Suffix"
              >
            </div>
          </div>

          <!-- Deceased Address - Dropdown System -->
          <div class="form-group mb-4">
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Deceased Address
            </label>
            
            <!-- Region Dropdown -->
            <div class="mb-3">
              <label class="block text-xs font-medium text-gray-500 mb-1">Region</label>
              <select 
                id="regionSelect" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                onchange="loadProvinces()"
              >
                <option value="">Select Region</option>
                <!-- Regions will be loaded dynamically -->
              </select>
            </div>
            
            <!-- Province Dropdown -->
            <div class="mb-3">
              <label class="block text-xs font-medium text-gray-500 mb-1">Province</label>
              <select 
                id="provinceSelect" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                disabled
                onchange="loadCities()"
              >
                <option value="">Select Province</option>
                <!-- Provinces will be loaded dynamically -->
              </select>
            </div>
            
            <!-- City/Municipality Dropdown -->
            <div class="mb-3">
              <label class="block text-xs font-medium text-gray-500 mb-1">City/Municipality</label>
              <select 
                id="citySelect" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                disabled
                onchange="loadBarangays()"
              >
                <option value="">Select City/Municipality</option>
                <!-- Cities will be loaded dynamically -->
              </select>
            </div>
            
            <!-- Barangay Dropdown -->
            <div class="mb-3">
              <label class="block text-xs font-medium text-gray-500 mb-1">Barangay</label>
              <select 
                id="barangaySelect" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                disabled
              >
                <option value="">Select Barangay</option>
                <!-- Barangays will be loaded dynamically -->
              </select>
            </div>
            
            <!-- Street and Zip Code -->
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Street</label>
                <input 
                  type="text" 
                  id="streetInput" 
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Street name, building, etc."
                >
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Zip Code</label>
                <input 
                  type="text" 
                  id="zipCodeInput" 
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Zip Code"
                >
              </div>
            </div>
          </div>

          <!-- Deceased Dates - 3 columns for dates -->
          <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Birth Date
              </label>
              <input 
                type="date" 
                id="birthDate" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Date of Death
              </label>
              <input 
                type="date" 
                id="deathDate" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Date of Burial
              </label>
              <input 
                type="date" 
                id="burialDate" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              >
            </div>
          </div>

          <!-- Death Certificate Upload -->
          <div class="form-group mt-4">
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Death Certificate
            </label>
            <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg">
              <div class="space-y-1 text-center">
                <div class="flex text-sm text-gray-600">
                  <label for="deathCertificate" class="relative cursor-pointer bg-white rounded-md font-medium text-sidebar-accent hover:text-opacity-80 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-sidebar-accent">
                    <span>Upload a file</span>
                    <input 
                      id="deathCertificate" 
                      name="deathCertificate" 
                      type="file" 
                      class="sr-only"
                      accept=".pdf,.jpg,.jpeg,.png"
                    >
                  </label>
                  <p class="pl-1">or drag and drop</p>
                </div>
                <p class="text-xs text-gray-500">PNG, JPG, PDF up to 10MB</p>
              </div>
            </div>
            <p id="file-name" class="mt-2 text-sm text-gray-500"></p>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeEditServiceModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="saveServiceChanges()">
        Save Changes
      </button>
    </div>
  </div>
</div>

<!-- Assign Staff Modal -->
<div id="assignStaffModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeAssignStaffModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Assign Staff to Service
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="assignStaffForm" class="space-y-3 sm:space-y-4">
        <input type="hidden" id="assignServiceId">
        
        <?php
        // This will be populated by JavaScript when the modal opens
        $branch_id = 0;
        
        // Function to generate employee checkboxes by position
        function generateEmployeeCheckboxes($position, $employees) {
            $positionLower = strtolower($position);
            $icon = '';
            $iconClass = 'mr-2 text-sidebar-accent';
            
            if ($positionLower === 'embalmer') {
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="'.$iconClass.'"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>';
            } elseif ($positionLower === 'driver') {
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="'.$iconClass.'"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>';
            } else {
                $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="'.$iconClass.'"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
            }
            
            echo '<div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200 mb-4">';
            echo '<h4 class="text-sm sm:text-lg font-bold mb-3 sm:mb-4 text-gray-700 flex items-center">';
            echo $icon . ucfirst($positionLower) . 's';
            echo '</h4>';
            echo '<div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">';
            
            if (!empty($employees)) {
                $count = 1;
                foreach ($employees as $employee) {
                    $fullName = htmlspecialchars($employee['fname'] . ' ' . $employee['mname'] . ' ' . $employee['lname']);
                    $employeeId = htmlspecialchars($employee['employee_id']);
                    
                    echo '<div class="flex items-center">';
                    echo '<input type="checkbox" id="'.$positionLower.$count.'" name="assigned_staff[]" value="'.$employeeId.'" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">';
                    echo '<label for="'.$positionLower.$count.'" class="text-gray-700">'.$fullName.'</label>';
                    echo '</div>';
                    
                    $count++;
                }
            } else {
                echo '<p class="text-gray-500 col-span-2">No '.$positionLower.'s available</p>';
            }
            
            echo '</div></div>';
        }
        
        // These sections will be populated via AJAX when the modal opens
        ?>
        
        <!-- Changed to a single column layout with consistent spacing -->
        <div class="space-y-3 sm:space-y-4">
          <div id="embalmersSection" class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
            <!-- Embalmers will be loaded here -->
          </div>
          
          <div id="driversSection" class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
            <!-- Drivers will be loaded here -->
          </div>
          
          <div id="personnelSection" class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
            <!-- Personnel will be loaded here -->
          </div>
          
          <div>
            <label for="assignmentNotes" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Notes
            </label>
            <div class="relative">
              <textarea id="assignmentNotes" rows="5" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"></textarea>
            </div>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeAssignStaffModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="saveStaffAssignment()">
        Save Assignment
      </button>
    </div>
  </div>
</div>

<!-- Complete Service Modal -->
<div id="completeServiceModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeCompleteModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Complete Service
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="completeServiceForm" class="space-y-3 sm:space-y-4">
        <input type="hidden" id="completeServiceId">
        
        <!-- Drivers Section -->
        <div id="completeDriversSection" class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <h4 class="text-xs sm:text-sm font-medium text-gray-700 mb-2 flex items-center">
            Drivers
          </h4>
          <div class="grid grid-cols-2 gap-3 sm:gap-4" id="completeDriversList">
            <!-- Drivers will be populated here -->
          </div>
        </div>
        
        <!-- Personnel Section -->
        <div id="completePersonnelSection" class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <h4 class="text-xs sm:text-sm font-medium text-gray-700 mb-2 flex items-center">
            Personnel
          </h4>
          <div class="grid grid-cols-2 gap-3 sm:gap-4" id="completePersonnelList">
            <!-- Personnel will be populated here -->
          </div>
        </div>
        
        <div>
          <label for="completionDate" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Completion Date
          </label>
          <div class="relative">
            <input type="date" id="completionDate" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
          </div>
        </div>
        
        <div>
          <label for="completionNotes" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Completion Notes
          </label>
          <div class="relative">
            <textarea id="completionNotes" rows="3" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"></textarea>
          </div>
        </div>
        
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border-l-4 border-gold mt-3 sm:mt-4">
          <div class="flex items-center">
            <input type="checkbox" id="finalBalanceSettled" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
            <label for="finalBalanceSettled" class="text-xs sm:text-sm text-gray-700 font-medium">Confirm all balances are settled</label>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeCompleteModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="finalizeServiceCompletion()">
        Complete Service
      </button>
    </div>
  </div>
</div>

<div class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto" id="viewServiceModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeViewServiceModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-5 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Service Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-5 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <!-- Service Information -->
      <div class="rounded-lg border border-gray-200 overflow-hidden mb-5">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
          <h4 class="font-medium text-gray-700">Basic Information</h4>
        </div>
        <div class="p-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">ID</label>
              <div id="serviceId" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Client Name</label>
              <div id="serviceClientName" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Service Type</label>
              <div id="serviceServiceType" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Branch</label>
              <div id="branchName" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Date</label>
              <div id="serviceDate" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Status</label>
              <div id="serviceStatus" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1 col-span-1 sm:col-span-2">
              <label class="block text-xs font-medium text-gray-500">Outstanding Balance</label>
              <div id="serviceOutstandingBalance" class="text-base font-bold text-sidebar-accent">-</div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Initial Staff Section -->
      <div class="rounded-lg border border-gray-200 overflow-hidden mb-5">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
          <h4 class="font-medium text-gray-700">Initial Staff</h4>
        </div>
        <div class="p-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Date</label>
              <div id="initialDate" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Embalmers</label>
              <div id="initialEmbalmers" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Drivers</label>
              <div id="initialDrivers" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Personnel</label>
              <div id="initialPersonnel" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1 col-span-1 sm:col-span-2">
              <label class="block text-xs font-medium text-gray-500">Notes</label>
              <div id="initialNotes" class="text-sm font-medium text-gray-800 whitespace-pre-line">-</div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Burial Staff Section -->
      <div class="rounded-lg border border-gray-200 overflow-hidden">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
          <h4 class="font-medium text-gray-700">Burial Staff</h4>
        </div>
        <div class="p-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Date</label>
              <div id="burialDate1" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Drivers</label>
              <div id="burialDrivers" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Personnel</label>
              <div id="burialPersonnel" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1 col-span-1 sm:col-span-2">
              <label class="block text-xs font-medium text-gray-500">Notes</label>
              <div id="burialNotes" class="text-sm font-medium text-gray-800 whitespace-pre-line">-</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-5 sm:px-6 py-4 sm:py-5 flex flex-col sm:flex-row sm:justify-end gap-3 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2.5 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeViewServiceModal()">
        Close
      </button>
    </div>
  </div>
</div>

  <!-- Modal for Recording Payment -->
<div id="recordPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeRecordPaymentModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Record Payment
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="recordPaymentForm" class="space-y-3 sm:space-y-4">
        <input type="hidden" id="customerID" name="customerID">
        <input type="hidden" id="branchID" name="branchID">
        
        <!-- Service ID -->
        <div>
          <label for="paymentServiceId" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Sales ID
          </label>
          <div class="relative">
            <input type="text" id="paymentServiceId" name="paymentServiceId" readonly class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
          </div>
        </div>
        
        <!-- Client Name -->
        <div>
          <label for="paymentClientName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Client Name
          </label>
          <div class="relative">
            <input type="text" id="paymentClientName" name="paymentClientName" readonly class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
          </div>
        </div>
        
        <!-- Outstanding Balance -->
        <div>
          <label for="currentBalance" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Outstanding Balance
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="text" id="currentBalance" name="currentBalance" readonly class="w-full pl-8 px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
          </div>
        </div>
        
        <!-- Payment Amount -->
        <div>
          <label for="paymentAmount" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Payment Amount
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input 
      type="number" 
      id="paymentAmount" 
      name="paymentAmount" 
      required 
      min="0" 
      step="0.01" 
      class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
      oninput="validatePaymentAmount(this)"
    >
          </div>
        </div>
        
        <!-- Payment Method -->
        <div>
          <label for="paymentMethod" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Payment Method
          </label>
          <div class="relative">
            <select id="paymentMethod" name="paymentMethod" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              <option value="" disabled selected>Select payment method</option>
              <option value="Cash">Cash</option>
              <option value="G Cash">G-Cash</option>
              <option value="Credit Card">Credit Card</option>
              <option value="Bank Transfer">Bank Transfer</option>
            </select>
          </div>
        </div>
        
        <!-- Payment Date -->
        <div>
          <label for="paymentDate" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Payment Date
          </label>
          <div class="relative">
            <input type="date" id="paymentDate" name="paymentDate" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" max="<?php echo date('Y-m-d'); ?>">
          </div>
        </div>
        
        <!-- Notes Section -->
        <div>
          <label for="paymentNotes" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Notes
          </label>
          <div class="relative">
            <textarea id="paymentNotes" name="paymentNotes" rows="3" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"></textarea>
          </div>
        </div>
        
        <!-- Summary Section -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200 mt-3 sm:mt-4">
          <p class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
            Payment Summary
          </p>
          <div class="flex justify-between mb-2 text-gray-700">
            <span>Current Balance:</span>
            <span id="summary-current-balance" class="font-medium">₱0.00</span>
          </div>
          <div class="flex justify-between mb-2 text-gray-700">
            <span>Payment Amount:</span>
            <span id="summary-payment-amount" class="font-medium">₱0.00</span>
          </div>
          <div class="flex justify-between mb-2 text-gray-700">
            <span>Total Paid:</span>
            <span id="total-amount-paid" class="font-medium">₱0.00</span>
          </div>
          <div class="flex justify-between font-bold text-lg mt-4 pt-4 border-t border-dashed border-purple-200 text-sidebar-accent">
            <span>New Balance:</span>
            <span id="summary-new-balance">₱0.00</span>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeRecordPaymentModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="savePayment()">
        Record Payment
      </button>
    </div>
  </div>
</div>


  <script src="script.js"></script>
  <script>
// Pass PHP data to JavaScript
const customers = <?php echo json_encode($customers); ?>;

document.getElementById('customerSearch').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const resultsContainer = document.getElementById('customerResults');
    
    if (searchTerm.length < 2) {
        resultsContainer.classList.add('hidden');
        return;
    }

    const filteredCustomers = customers.filter(customer => 
        customer.full_name.toLowerCase().includes(searchTerm)
    ).slice(0, 10); // Limit to 10 results

    if (filteredCustomers.length > 0) {
        resultsContainer.innerHTML = filteredCustomers.map(customer => `
            <div class="cursor-default select-none relative py-2 pl-3 pr-9 hover:bg-gray-100" 
                 data-id="${customer.id}" 
                 onclick="selectCustomer(this, '${customer.id}', '${customer.full_name.replace(/'/g, "\\'")}')">
                ${customer.full_name}
            </div>
        `).join('');
        resultsContainer.classList.remove('hidden');
    } else {
        resultsContainer.innerHTML = '<div class="py-2 pl-3 pr-9 text-gray-500">No customers found</div>';
        resultsContainer.classList.remove('hidden');
    }
});

function selectCustomer(element, id, fullName) {
    document.getElementById('customerSearch').value = fullName;
    document.getElementById('selectedCustomerId').value = id;
    document.getElementById('customerResults').classList.add('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('#customerSearch') && !e.target.closest('#customerResults')) {
        document.getElementById('customerResults').classList.add('hidden');
    }
});
</script>
  <script>
    // Function to open the modal and populate fields with service data
    function openRecordPaymentModal(serviceId, clientName, balance) {
  // Get the modal element
  const modal = document.getElementById('recordPaymentModal');

  const today = new Date().toISOString().split('T')[0];
  const paymentDateInput = document.getElementById('paymentDate');
  paymentDateInput.value = today;
  paymentDateInput.max = today;
  
  // Fetch additional details (customerID, branch_id, and amount_paid) via AJAX
  fetch(`history/get_payment_details.php?sales_id=${serviceId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Populate the readonly fields
        document.getElementById('paymentServiceId').value = serviceId;
        document.getElementById('paymentClientName').value = clientName;
        document.getElementById('currentBalance').value = parseFloat(balance).toFixed(2);
        document.getElementById('total-amount-paid').textContent = 
          `₱${parseFloat(data.amount_paid || 0).toFixed(2)}`;
        
        // Store customerID and branchID in hidden fields
        document.getElementById('customerID').value = data.customerID;
        document.getElementById('branchID').value = data.branch_id;
        
        // Set default payment amount to empty
        document.getElementById('paymentAmount').value = '';
        
        // Set today's date as default payment date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('paymentDate').value = today;
        
        // Clear any previous input in notes
        document.getElementById('paymentNotes').value = '';
        
        // Update summary
        updatePaymentSummary();
        
        // Display the modal
        modal.classList.remove('hidden');
      } else {
        alert('Failed to fetch payment details: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while fetching payment details');
    });
}

// Function to close the modal
function closeRecordPaymentModal() {
  const modal = document.getElementById('recordPaymentModal');
  modal.classList.add('hidden');
}

// Function to update payment summary
function updatePaymentSummary() {
  const currentBalance = parseFloat(document.getElementById('currentBalance').value) || 0;
  const paymentAmount = parseFloat(document.getElementById('paymentAmount').value) || 0;
  const newBalance = currentBalance - paymentAmount;
  
  document.getElementById('summary-current-balance').textContent = `₱${currentBalance.toFixed(2)}`;
  document.getElementById('summary-payment-amount').textContent = `₱${paymentAmount.toFixed(2)}`;
  document.getElementById('summary-new-balance').textContent = `₱${newBalance.toFixed(2)}`;
}

// Add event listener to payment amount field to update summary
document.getElementById('paymentAmount').addEventListener('input', updatePaymentSummary);


// Function to handle the payment submission
function savePayment() {
  // Get form values
  const serviceId = document.getElementById('paymentServiceId').value;
  const customerID = document.getElementById('customerID').value;
  const branchID = document.getElementById('branchID').value;
  const clientName = document.getElementById('paymentClientName').value;
  const currentBalance = parseFloat(document.getElementById('currentBalance').value);
  const paymentAmount = parseFloat(document.getElementById('paymentAmount').value);
  const paymentMethod = document.getElementById('paymentMethod').value;
  const paymentDate = document.getElementById('paymentDate').value;
  const notes = document.getElementById('paymentNotes').value;
  
  // Validate all fields
  if (!serviceId || !customerID || !branchID || !clientName || isNaN(paymentAmount) || 
      paymentAmount <= 0 || paymentAmount > currentBalance || !paymentMethod || !paymentDate) {
    alert('Please fill all fields with valid values.');
    return;
  }

  // Calculate new balance
  const newBalance = currentBalance - paymentAmount;
  
  // Create payment data object
  const paymentData = {
    customerID: customerID,
    sales_id: serviceId,
    branch_id: branchID,
    client_name: clientName,
    before_balance: currentBalance,
    after_payment_balance: newBalance,
    payment_amount: paymentAmount,
    method_of_payment: paymentMethod,
    payment_date: paymentDate,
    notes: notes
  };
  
  // Show loading state
  const saveBtn = document.querySelector('#recordPaymentModal button[onclick="savePayment()"]');
  const originalBtnText = saveBtn.innerHTML;
  saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
  saveBtn.disabled = true;
  
  // Send data to server
  fetch('history/record_payment.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(paymentData)
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    return response.json();
  })
  .then(data => {
    if (data.success) {
      alert(`Payment recorded successfully! Total paid: ₱${data.new_amount_paid.toFixed(2)}`);
      
      // Refresh the page to show updated values
      location.reload();
    } else {
      throw new Error(data.message || 'Failed to record payment');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Error: ' + error.message);
  })
  .finally(() => {
    // Restore button state
    saveBtn.innerHTML = originalBtnText;
    saveBtn.disabled = false;
  });
}

// Function to update sales_tb balance
function updateSalesBalance(salesId, newBalance) {
  const balanceData = {
    sales_id: salesId,
    new_balance: newBalance
  };
  
  fetch('history/update_sales_balance.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(balanceData)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Refresh the page to show updated balance
      location.reload();
    } else {
      console.error('Failed to update sales balance:', data.message);
    }
  })
  .catch(error => {
    console.error('Error updating sales balance:', error);
  });
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
// Function to open the Edit Service Modal
function openEditServiceModal(serviceId) {
  
  // Fetch service details via AJAX
  fetch(`get_service_details.php?sales_id=${serviceId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Check if elements exist before manipulating them
        const customerSearch = document.getElementById('customerSearch');
        const selectedCustomerId = document.getElementById('selectedCustomerId');

        if (customerSearch && selectedCustomerId) {
          if (data.customerID) {
            const customer = customers.find(c => c.id == data.customerID);
            if (customer) {
              customerSearch.value = customer.full_name;
              selectedCustomerId.value = customer.id;
            }
          } else {
            // Explicitly clear if customerID is null or undefined
            customerSearch.value = '';
            selectedCustomerId.value = '';
          }
        }

        // Populate the form fields with the service details
        if (data.customerID) {
          const customer = customers.find(c => c.id == data.customerID);
          if (customer) {
            document.getElementById('customerSearch').value = customer.full_name;
            document.getElementById('selectedCustomerId').value = customer.id;
          }
        } else {
          // Explicitly clear if customerID is null or undefined
          document.getElementById('customerSearch').value = '';
          document.getElementById('selectedCustomerId').value = '';
        }
        document.getElementById('salesId').value = data.sales_id;
        document.getElementById('firstName').value = data.fname || '';
        document.getElementById('middleName').value = data.mname || '';
        document.getElementById('lastName').value = data.lname || '';
        document.getElementById('nameSuffix').value = data.suffix || '';
        document.getElementById('deceasedFirstName').value = data.fname_deceased || '';
        document.getElementById('deceasedMiddleName').value = data.mname_deceased || '';
        document.getElementById('deceasedLastName').value = data.lname_deceased || '';
        document.getElementById('deceasedSuffix').value = data.suffix_deceased || '';
        document.getElementById('birthDate').value = data.date_of_birth || '';
        document.getElementById('deathDate').value = data.date_of_death || '';
        document.getElementById('burialDate').value = data.date_of_burial || '';
        document.getElementById('streetInput').value = data.deceased_address || '';
        document.getElementById('email').value = data.email || '';
        document.getElementById('phone').value = data.phone || '';
        
        // Select the branch radio button
        // Select the branch radio button - FIXED VERSION
        if (data.branch_id) {
          const branchRadios = document.getElementsByName('branch');
          for (let radio of branchRadios) {
            // Convert both values to strings for comparison to avoid type issues
            if (radio.value.toString() === data.branch_id.toString()) {
              radio.checked = true;
              break;
            }
          }
        }
        
        // Set the service price from discounted_price
        if (data.discounted_price) {
          document.getElementById('servicePrice').value = data.discounted_price;
        }
        
        // Now fetch services for this branch
        fetchServicesForBranch(data.branch_id, data.service_id);
        
        // Show the modal
        document.getElementById('editServiceModal').style.display = 'flex';
        
        setTimeout(initEditModalValidations, 100);
        toggleBodyScroll(true);
      } else {
        alert('Failed to fetch service details: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while fetching service details');
    });
}

function fetchServicesForBranch(branchId, currentServiceId) {
  // Fetch services for this branch via AJAX
  fetch(`get_services_for_branch.php?branch_id=${branchId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const serviceSelect = document.getElementById('serviceSelect');
        serviceSelect.innerHTML = '<option value="">Choose Service</option>';
        
        data.services.forEach(service => {
          const option = document.createElement('option');
          option.value = service.service_id;
          option.textContent = service.service_name;
          if (service.service_id == currentServiceId) {
            option.selected = true;
          }
          serviceSelect.appendChild(option);
        });
      } else {
        console.error('Failed to fetch services:', data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
    });
}

// Function to close the Edit Service Modal
function closeEditServiceModal() {
  document.getElementById('editServiceModal').style.display = 'none';
  document.getElementById('customerSearch').value = '';
  toggleBodyScroll(false);
}

// Function to save changes to a service
function saveServiceChanges() {
  // Get all form values
  const formData = {
    sales_id: document.getElementById('salesId').value,
    customer_id: document.getElementById('selectedCustomerId').value,
    service_id: document.getElementById('serviceSelect').value,
    service_price: document.getElementById('servicePrice').value,
    firstName: document.getElementById('firstName').value,
    middleName: document.getElementById('middleName').value,
    lastName: document.getElementById('lastName').value,
    nameSuffix: document.getElementById('nameSuffix').value,
    email: document.getElementById('email').value,
    phone: document.getElementById('phone').value,
    deceasedFirstName: document.getElementById('deceasedFirstName').value,
    deceasedMiddleName: document.getElementById('deceasedMiddleName').value,
    deceasedLastName: document.getElementById('deceasedLastName').value,
    deceasedSuffix: document.getElementById('deceasedSuffix').value,
    birthDate: document.getElementById('birthDate').value,
    deathDate: document.getElementById('deathDate').value,
    burialDate: document.getElementById('burialDate').value,
    streetInput: document.getElementById('streetInput').value,
    branch: document.querySelector('input[name="branch"]:checked')?.value,
    deathCertificate: document.getElementById('deathCertificate').files[0]?.name || 'No file selected'
  };

  // Log the form data to console
  console.log('Service Form Data:', formData);
  
  fetch('update_history_sales.php', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
  },
  body: JSON.stringify(formData)
})
.then(response => response.json())
.then(data => {
  console.log('Success:', data);
  if (data.success) {
    alert('Service updated successfully!');
    closeEditServiceModal();
  } else {
    alert('Error: ' + data.message);
  }
})
.catch(error => {
  console.error('Error:', error);
  alert('An error occurred while updating the service');
});

  // For demo purposes, just show an alert
  alert('Service changes would be saved here. Check console for form data.');
  closeEditServiceModal();
}

// Function to open the Assign Staff Modal
function openAssignStaffModal(salesId) {
    // Set the service ID in the form
    document.getElementById('assignServiceId').value = salesId;
    
    // Show the modal
    document.getElementById('assignStaffModal').classList.remove('hidden');
    
    // Fetch the branch_id and employees via AJAX
    fetch('history/get_branch_and_employees.php?sales_id=' + salesId)
        .then(response => response.json())
        .then(data => {
            // Populate the sections
            populateEmployeeSection('embalmersSection', 'Embalmer', data.embalmers);
            populateEmployeeSection('driversSection', 'Driver', data.drivers);
            populateEmployeeSection('personnelSection', 'Personnel', data.personnel);
        })
        .catch(error => console.error('Error:', error));
}

function populateEmployeeSection(sectionId, position, employees) {
    console.group(`populateEmployeeSection - ${position}`);
    console.log('Section ID:', sectionId);
    console.log('Position:', position);
    console.log('Employees data received:', employees);

    const section = document.getElementById(sectionId);
    const positionLower = position.toLowerCase();
    let icon = '';
    
    if (positionLower === 'embalmer') {
        icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>';
    } else if (positionLower === 'driver') {
        icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>';
    } else {
        icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>';
    }
    
    let html = `<h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center">
        ${icon}${position}s
    </h4>
    <div class="grid grid-cols-2 gap-4">`;
    
    if (employees && employees.length > 0) {
        console.log(`Processing ${employees.length} ${positionLower}(s)`);
        
        employees.forEach((employee, index) => {
            console.group(`Employee ${index + 1}`);
            console.log('Raw employee data:', employee);

            // Format each name part
            const formatName = (name) => {
                if (!name || name.toLowerCase() === 'null') {
                    console.log(`Empty name part detected (converted to empty string)`);
                    return '';
                }
                return name.charAt(0).toUpperCase() + name.slice(1).toLowerCase();
            };

            const firstName = formatName(employee.fname);
            const middleName = formatName(employee.mname);
            const lastName = formatName(employee.lname);

            console.log('Formatted name parts:', {
                firstName,
                middleName,
                lastName
            });

            // Combine names with proper spacing
            let fullName = [firstName, middleName, lastName]
                .filter(name => name && name.trim() !== '')
                .join(' ');

            console.log('Full name:', fullName);
            console.log('Employee ID:', employee.employeeID);
            
            const checkboxId = `${positionLower}${index+1}`;
            console.log('Checkbox attributes:', {
                id: checkboxId,
                name: 'assigned_staff[]',
                value: employee.employeeID
            });

            html += `<div class="flex items-center">
                <input type="checkbox" id="${checkboxId}" name="assigned_staff[]" value="${employee.employeeID}" class="mr-2">
                <label for="${checkboxId}" class="text-gray-700">${fullName}</label>
            </div>`;
            
            console.groupEnd();
        });
    } else {
        console.log(`No ${positionLower}s available`);
        html += `<p class="text-gray-500 col-span-2">No ${positionLower}s available</p>`;
    }
    
    html += `</div>`;
    
    console.log('Generated HTML:', html);
    section.innerHTML = html;
    
    console.log('Section populated successfully');
    console.groupEnd();
}

function closeAssignStaffModal() {
    document.getElementById('assignStaffModal').classList.add('hidden');
}

// Function to save staff assignments
function saveStaffAssignment() {
    const salesId = document.getElementById('assignServiceId').value;
    const notes = document.getElementById('assignmentNotes').value;
    
    // Get all checked checkboxes within the assignStaffModal
    const modal = document.getElementById('assignStaffModal');
    const checkboxes = modal.querySelectorAll('input[name="assigned_staff[]"]:checked');
    
    // Extract the employee IDs from the checkboxes
    const assignedStaff = Array.from(checkboxes).map(checkbox => {
        return checkbox.value;
    }).filter(id => id); // Filter out any undefined/empty values

    if (assignedStaff.length === 0) {
        alert('Please select at least one staff member');
        return;
    }

    // Get base salaries for selected employees
    fetch('get_employee_salaries.php?employee_ids=' + assignedStaff.join(','))
        .then(response => response.json())
        .then(salaries => {
            // Prepare the data to send
            const assignmentData = {
                sales_id: salesId,
                staff_data: assignedStaff.map(employeeId => ({
                    employee_id: employeeId,
                    salary: salaries[employeeId] || 0 // Default to 0 if salary not found
                })),
                notes: notes
            };

            console.log('Sending assignment data:', assignmentData);
            
            // Send data to server
            return fetch('save_staff_assignment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(assignmentData)
            });
        })
        .then(response => {
    if (!response.ok) {
        return response.text().then(text => {
            console.error('Server response:', text);
            throw new Error('Server error: ' + response.status);
        });
    }
    return response.json();
})
        .then(data => {
            if (data.success) {
                alert('Staff assigned successfully!');
                closeAssignStaffModal();
                // Optionally refresh the page or update the UI
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
    console.error('Error details:', error);
    alert('An error occurred while saving the assignment. See console for details.');
});
}

// Function to open the Complete Service Modal
function openCompleteModal(serviceId) {
  // Set service ID and default values
  document.getElementById('completeServiceId').value = serviceId;
  // Set current date in yyyy-mm-dd format
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  document.getElementById('completionDate').value = `${year}-${month}-${day}`;
  document.getElementById('completionNotes').value = '';
  document.getElementById('finalBalanceSettled').checked = false;
  
  // Fetch the branch_id and employees via AJAX
  fetch('history/get_branch_and_employees.php?sales_id=' + serviceId)
    .then(response => response.json())
    .then(data => {
      // Populate the sections with only drivers and personnel
      populateCompleteEmployeeSection('completeDriversList', 'Driver', data.drivers);
      populateCompleteEmployeeSection('completePersonnelList', 'Personnel', data.personnel);
      
      // Show the modal
      document.getElementById('completeServiceModal').classList.remove('hidden');
      toggleBodyScroll(true);
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while fetching employee data');
    });
}

function populateCompleteEmployeeSection(sectionId, position, employees) {
  const section = document.getElementById(sectionId);
  section.innerHTML = ''; // Clear existing content
  
  if (employees && employees.length > 0) {
    employees.forEach((employee, index) => {
      // Format each name part
      const formatName = (name) => {
        if (!name || name.toLowerCase() === 'null') return '';
        return name.charAt(0).toUpperCase() + name.slice(1).toLowerCase();
      };

      const firstName = formatName(employee.fname);
      const middleName = formatName(employee.mname);
      const lastName = formatName(employee.lname);

      // Combine names with proper spacing
      let fullName = [firstName, middleName, lastName]
        .filter(name => name && name.trim() !== '')
        .join(' ');
      
      const checkboxId = `complete-${position.toLowerCase()}-${index+1}`;
      
      const div = document.createElement('div');
      div.className = 'flex items-center';
      div.innerHTML = `
        <input type="checkbox" id="${checkboxId}" name="complete_assigned_staff[]" value="${employee.employeeID}" class="mr-2">
        <label for="${checkboxId}" class="text-gray-700">${fullName}</label>
      `;
      
      section.appendChild(div);
    });
  } else {
    section.innerHTML = `<p class="text-gray-500 col-span-2">No ${position.toLowerCase()}s available</p>`;
  }
}

// Function to close the Complete Service Modal
function closeCompleteModal() {
  document.getElementById('completeServiceModal').classList.add('hidden');
  toggleBodyScroll(false);
}


// Function to finalize service completion
// Function to finalize service completion
function finalizeServiceCompletion() {
    const serviceId = document.getElementById('completeServiceId').value;
    const completionDateInput = document.getElementById('completionDate').value;
    const completionNotes = document.getElementById('completionNotes').value;
    const balanceSettled = document.getElementById('finalBalanceSettled').checked;
    
    if (!completionDateInput) {
        alert('Please specify a completion date.');
        return;
    }
    
    
    // Get current time
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const currentTime = `${hours}:${minutes}:${seconds}`;
    
    // Combine date and time for SQL timestamp format (yyyy-mm-dd HH:MM:SS)
    const completionDateTime = `${completionDateInput} ${currentTime}`;

    // Get all checked checkboxes within the completeServiceModal
    const modal = document.getElementById('completeServiceModal');
    const checkboxes = modal.querySelectorAll('input[name="complete_assigned_staff[]"]:checked');
    
    // Extract the employee IDs from the checkboxes
    const assignedStaff = Array.from(checkboxes).map(checkbox => {
        return checkbox.value;
    }).filter(id => id); // Filter out any undefined/empty values

    // Get base salaries for selected employees
    fetch('get_employee_salaries.php?employee_ids=' + assignedStaff.join(','))
        .then(response => response.json())
        .then(salaries => {
            // Prepare the data to send
            const completionData = {
                sales_id: serviceId,
                staff_data: assignedStaff.map(employeeId => ({
                    employee_id: employeeId,
                    salary: salaries[employeeId] || 0 // Default to 0 if salary not found
                })),
                notes: completionNotes,
                service_stage: 'completion',
                completion_date: completionDateTime, // Now includes time
                balance_settled: balanceSettled
            };

            console.log('Sending completion data:', completionData);
            
            // Send data to server
            return fetch('history/save_service_completion.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(completionData)
            });
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Server response:', text);
                    throw new Error('Server error: ' + response.status);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Service completed successfully!');
                closeCompleteModal();
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error details:', error);
            alert('An error occurred while completing the service. See console for details.');
        });
}
// Function to view service details (kept from original)
// Function to view service details with data from all four tables
// Function to view service details with data from all four tables
function viewServiceDetails(serviceId) {
  // Show loading state
  document.getElementById('serviceId').textContent = 'Loading...';
  
  // Fetch service details from server
  fetch(`history/get_service_full_details.php?sales_id=${serviceId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Populate basic service info
        document.getElementById('serviceId').textContent = data.sales_id;
        document.getElementById('serviceClientName').textContent = 
          `${data.fname} ${data.mname ? data.mname + ' ' : ''}${data.lname}${data.suffix ? ' ' + data.suffix : ''}`;
        document.getElementById('serviceServiceType').textContent = data.service_name || 'N/A';
        document.getElementById('branchName').textContent = data.branch_name || 'N/A';
        document.getElementById('serviceDate').textContent = data.date_of_burial ? formatDate(data.date_of_burial) : 'N/A';
        document.getElementById('serviceStatus').textContent = data.status || 'N/A';
        document.getElementById('serviceOutstandingBalance').textContent = 
          data.balance ? `₱${parseFloat(data.balance).toFixed(2)}` : '₱0.00';

        // Populate initial staff section
        if (data.initial_staff) {
          document.getElementById('initialDate').textContent = 
            data.initial_staff.date ? formatDate(data.initial_staff.date) : 'N/A';
          document.getElementById('initialEmbalmers').textContent = 
            data.initial_staff.embalmers.length > 0 ? data.initial_staff.embalmers.join(', ') : 'None';
          document.getElementById('initialDrivers').textContent = 
            data.initial_staff.drivers.length > 0 ? data.initial_staff.drivers.join(', ') : 'None';
          document.getElementById('initialPersonnel').textContent = 
            data.initial_staff.personnel.length > 0 ? data.initial_staff.personnel.join(', ') : 'None';
          document.getElementById('initialNotes').textContent = 
            data.initial_staff.notes || 'None';
        }

        // Populate burial staff section
        if (data.burial_staff) {
          // Use the burial date from sales_tb if available, otherwise from payment records
          document.getElementById('burialDate1').textContent = 
          data.burial_staff.date ? formatDate(data.burial_staff.date) : 'N/A';
          document.getElementById('burialDrivers').textContent = 
            data.burial_staff.drivers.length > 0 ? data.burial_staff.drivers.join(', ') : 'None';
          document.getElementById('burialPersonnel').textContent = 
            data.burial_staff.personnel.length > 0 ? data.burial_staff.personnel.join(', ') : 'None';
          document.getElementById('burialNotes').textContent = 
            data.burial_staff.notes || 'None';
        }

        // Show the modal
        document.getElementById('viewServiceModal').style.display = 'flex';
        toggleBodyScroll(true);
      } else {
        alert('Failed to fetch service details: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while fetching service details');
    });
}

// Helper function to format dates consistently
function formatDate(dateString) {
  if (!dateString) return 'N/A';
  
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
  } catch (e) {
    console.error('Error formatting date:', e);
    return dateString; // Return the raw string if formatting fails
  }
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
</script> 
<script>
// Function to check customer before assigning staff
function checkCustomerBeforeAssign(salesId, hasCustomer) {
    if (!hasCustomer) {
        Swal.fire({
            icon: 'warning',
            title: 'Customer Required',
            text: 'Please enter a customer account first by clicking the edit button',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
        return;
    }
    openAssignStaffModal(salesId);
}

// Function to check customer before completing service
function checkCustomerBeforeComplete(salesId, hasCustomer) {
    if (!hasCustomer) {
        Swal.fire({
            icon: 'warning',
            title: 'Customer Required',
            text: 'Please enter a customer account first by clicking the edit button',
            confirmButtonColor: '#3085d6',
            confirmButtonText: 'OK'
        });
        return;
    }
    openCompleteModal(salesId);
}

// Add CSS for disabled buttons
const style = document.createElement('style');
style.textContent = `
    .assign-staff-btn:disabled, .complete-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    .assign-staff-btn:disabled:hover, .complete-btn:disabled:hover {
        background-color: initial !important;
    }
`;
document.head.appendChild(style);

</script>
<script src="tailwind.js"></script>

<script>
// Function to validate inputs with specific patterns
function validateInput(input, pattern, errorMessage = 'Invalid input') {
    const value = input.value;
    if (!pattern.test(value)) {
        input.value = value.slice(0, -1); // Remove last character if invalid
        return false;
    }
    return true;
}

// Function to auto-capitalize first letter and handle spaces
function formatNameInput(input) {
    let value = input.value;
    
    // Remove multiple consecutive spaces
    value = value.replace(/\s+/g, ' ');
    
    // Capitalize first letter of each word
    value = value.replace(/\b\w/g, char => char.toUpperCase());
    
    // Don't allow space unless there are at least 2 characters
    if (value.endsWith(' ') && value.trim().length < 2) {
        value = value.trim();
    }
    
    input.value = value;
}

// Function to validate email
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!re.test(email.value)) {
        email.setCustomValidity('Please enter a valid email address');
        return false;
    }
    email.setCustomValidity('');
    return true;
}

// Function to validate phone number
function validatePhone(phone) {
    const re = /^09\d{9}$/;
    if (!re.test(phone.value)) {
        phone.setCustomValidity('Philippine mobile number must start with 09 and be 11 digits');
        return false;
    }
    phone.setCustomValidity('');
    return true;
}

// Function to validate service price
function validatePrice(price) {
    if (parseFloat(price.value) < 0) {
        price.value = '0';
    }
    return true;
}

// Function to validate dates in the edit service modal
function validateDates() {
    const birthDateInput = document.getElementById('birthDate');
    const deathDateInput = document.getElementById('deathDate');
    const burialDateInput = document.getElementById('burialDate');
    
    // Get today's date in YYYY-MM-DD format
    const today = new Date();
    const todayFormatted = today.toISOString().split('T')[0];
    
    // 1. Set Date of Birth constraints (past dates only, up to today)
    birthDateInput.max = todayFormatted;
    
    // 2. Set Date of Death constraints (from Date of Birth to today)
    if (birthDateInput.value) {
        deathDateInput.min = birthDateInput.value;
        deathDateInput.max = todayFormatted;
        deathDateInput.disabled = false;
    } else {
        deathDateInput.disabled = true;
        deathDateInput.value = '';
    }
    
    // 3. Set Date of Burial constraints (from day after Date of Death to future)
    if (deathDateInput.value) {
        const deathDate = new Date(deathDateInput.value);
        const minBurialDate = new Date(deathDate);
        minBurialDate.setDate(deathDate.getDate() + 1);
        
        burialDateInput.min = minBurialDate.toISOString().split('T')[0];
        burialDateInput.disabled = false;
    } else {
        burialDateInput.disabled = true;
        burialDateInput.value = '';
    }
}

// Function to validate death certificate file
function validateDeathCertificate(input) {
    const file = input.files[0];
    if (file) {
        const validTypes = ['image/jpeg', 'image/png'];
        if (!validTypes.includes(file.type)) {
            alert('Only JPG and PNG files are allowed');
            input.value = '';
            return false;
        }
    }
    return true;
}

// Initialize all validations when modal opens
function initEditModalValidations() {
    // Customer search (letters only)
    const customerSearch = document.getElementById('customerSearch');
    customerSearch.addEventListener('input', function() {
        this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
        formatNameInput(this);
    });
    
    // Name fields (first, middle, last)
    const nameFields = ['firstName', 'middleName', 'lastName', 'deceasedFirstName', 'deceasedMiddleName', 'deceasedLastName'];
    nameFields.forEach(field => {
        const input = document.getElementById(field);
        if (input) {
            input.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
                formatNameInput(this);
            });
        }
    });
    
    // Email validation
    const email = document.getElementById('email');
    email.addEventListener('input', function() {
        this.value = this.value.replace(/\s/g, '');
        validateEmail(this);
    });
    
    // Phone validation
    const phone = document.getElementById('phone');
    phone.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '').substring(0, 11);
        validatePhone(this);
    });
    
    // Service price validation
    const servicePrice = document.getElementById('servicePrice');
    servicePrice.addEventListener('input', function() {
        validatePrice(this);
    });
    
    // Date validations
    const birthDate = document.getElementById('birthDate');
    const deathDate = document.getElementById('deathDate');
    const burialDate = document.getElementById('burialDate');
    
    // Set initial constraints
    validateDates();
    
    // Add event listeners
    birthDate.addEventListener('change', function() {
        validateDates();
        // If birth date changes, clear death and burial dates
        deathDate.value = '';
        burialDate.value = '';
    });
    
    deathDate.addEventListener('change', function() {
        validateDates();
        // If death date changes, clear burial date
        burialDate.value = '';
    });
    
    burialDate.addEventListener('change', validateDates);
    
    // Death certificate validation
    const deathCertificate = document.getElementById('deathCertificate');
    deathCertificate.addEventListener('change', function() {
        validateDeathCertificate(this);
    });
    
    // Deceased address - this would need to be implemented with a proper API for Philippine addresses
    // For now, we'll just add basic validation
    const streetInput = document.getElementById('streetInput');
    streetInput.addEventListener('input', function() {
        // Remove multiple consecutive spaces
        this.value = this.value.replace(/\s+/g, ' ');
        
        // Capitalize first letter of each word
        this.value = this.value.replace(/\b\w/g, char => char.toUpperCase());
    });
}

// Add this to the initEditModalValidations() function
const zipCodeInput = document.getElementById('zipCodeInput');
if (zipCodeInput) {
    zipCodeInput.addEventListener('input', function() {
        // Remove any non-digit characters
        this.value = this.value.replace(/\D/g, '');
        
        // Limit to 10 characters
        if (this.value.length > 10) {
            this.value = this.value.slice(0, 10);
        }
    });
    
    zipCodeInput.addEventListener('blur', function() {
        // Validate length (4-10 digits)
        if (this.value.length < 4 || this.value.length > 10) {
            this.setCustomValidity('Zip code must be between 4-10 digits');
            this.reportValidity();
        } else {
            this.setCustomValidity('');
        }
    });
}

// Add this to your existing JavaScript code

// Add this to your existing JavaScript code

// Update the DOMContentLoaded event listener to include both notes fields
document.addEventListener('DOMContentLoaded', function() {
    // Assign staff modal notes
    const assignmentNotes = document.getElementById('assignmentNotes');
    if (assignmentNotes) {
        assignmentNotes.addEventListener('input', function() {
            validateNotesInput(this);
        });
    }
    
    // Complete service modal notes
    const completionNotes = document.getElementById('completionNotes');
    if (completionNotes) {
        completionNotes.addEventListener('input', function() {
            validateNotesInput(this);
        });
    }

    const paymentNotes = document.getElementById('paymentNotes');
    if (paymentNotes) {
      paymentNotes.addEventListener('input', function() {
            validateNotesInput(this);
        });
    }
});

// Enhanced validateNotesInput function (same as before but with better handling)
function validateNotesInput(input) {
    // Get current cursor position
    const startPos = input.selectionStart;
    const endPos = input.selectionEnd;
    
    let value = input.value;
    
    // Remove multiple consecutive spaces
    value = value.replace(/\s+/g, ' ');
    
    // Don't allow space unless there are at least 2 characters
    if (value.endsWith(' ') && value.trim().length < 2) {
        value = value.trim();
    }
    
    // Capitalize first letter if input starts with a letter
    if (value.length > 0 && /[a-z]/.test(value[0])) {
        value = value.charAt(0).toUpperCase() + value.slice(1);
    }
    
    // Only update if value changed to prevent cursor jumping
    if (value !== input.value) {
        input.value = value;
        // Restore cursor position
        input.setSelectionRange(startPos, endPos);
    }
}

</script>

</body> 
</html>