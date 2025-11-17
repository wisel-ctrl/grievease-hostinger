<?php

session_start();

include 'faviconLogo.php'; 

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

$pageCustomOngoing = isset($_GET['page_custom_ongoing']) ? (int)$_GET['page_custom_ongoing'] : 1;
$pageCustomFullyPaid = isset($_GET['page_custom_fully_paid']) ? (int)$_GET['page_custom_fully_paid'] : 1;
$pageCustomOutstanding = isset($_GET['page_custom_outstanding']) ? (int)$_GET['page_custom_outstanding'] : 1;

$offsetCustomOngoing = ($pageCustomOngoing - 1) * $recordsPerPage;
$offsetCustomFullyPaid = ($pageCustomFullyPaid - 1) * $recordsPerPage;
$offsetCustomOutstanding = ($pageCustomOutstanding - 1) * $recordsPerPage;

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
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
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

.rotate-180 {
    transform: rotate(180deg);
    transition: transform 0.2s ease;
}

  .suggestion-item {
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid #f3f4f6;
  }

  .suggestion-item:hover {
    background-color: #f3f4f6;
  }

  .suggestion-item:last-child {
    border-bottom: none;
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
    <div class="relative">
      <button id="branchFilterToggle" class="px-4 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover transition-colors">
        <i class="fas fa-map-marker-alt text-sidebar-accent"></i>
        <span>Filter Branch</span>
        <i class="fas fa-chevron-down text-xs text-gray-500"></i>
      </button>
      <div id="branchFilterDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-sidebar-border py-1">
        <div class="space-y-1">
          <button class="w-full text-left px-4 py-2 text-sm text-sidebar-text hover:bg-sidebar-hover transition-colors" data-branch="all" data-branch-id="all">
            <i class="fas fa-globe-americas text-sidebar-accent mr-2"></i> All Branches
          </button>
          <button class="w-full text-left px-4 py-2 text-sm text-sidebar-text hover:bg-sidebar-hover transition-colors" data-branch="pila" data-branch-id="2">
            <i class="fas fa-store text-sidebar-accent mr-2"></i> Pila Branch
          </button>
          <button class="w-full text-left px-4 py-2 text-sm text-sidebar-text hover:bg-sidebar-hover transition-colors" data-branch="paete" data-branch-id="1">
            <i class="fas fa-store text-sidebar-accent mr-2"></i> Paete Branch
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Tabs Navigation -->
  <div class="mb-6">
    <ul class="flex border-b border-gray-200">
      <li>
        <button id="standard-tab" class="px-4 py-2 text-sm font-medium text-sidebar-text border-b-2 border-sidebar-accent focus:outline-none tab-button active" onclick="showTab('standard')">Standard Sales</button>
      </li>
      <li>
        <button id="custom-tab" class="px-4 py-2 text-sm font-medium text-gray-600 border-b-2 border-transparent hover:border-gray-300 focus:outline-none tab-button" onclick="showTab('custom')">Custom Sales</button>
      </li>
    </ul>
  </div>

  <!-- Standard Sales Tab Content -->
  <div id="standard-content" class="tab-content">
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
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
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
                    <i class="fas fa-calendar text-sidebar-accent"></i> Interment Date 
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
                    (SELECT COUNT(*) FROM employee_service_payments esp WHERE esp.sales_id = s.sales_id AND esp.sales_type = 'service') AS staff_assigned
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
        <div id="paginationContainer" class="flex space-x-2" data-table="ongoing">
            <?php 
            $totalPagesOngoing = ceil($totalOngoing / $recordsPerPage);
            if ($totalPagesOngoing > 1): 
            ?>
                <button onclick="loadOngoingServices(1)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                    &laquo;
                </button>
                <button onclick="loadOngoingServices(<?php echo max(1, $page - 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                    &lsaquo;
                </button>
                <?php
                $start_page = max(1, min($page - 1, $totalPagesOngoing - 2));
                $end_page = min($totalPagesOngoing, $start_page + 2);
                for ($i = $start_page; $i <= $end_page; $i++): 
                ?>
                    <button onclick="loadOngoingServices(<?php echo $i; ?>)" class="px-3.5 py-1.5 rounded text-sm <?php echo ($i == $page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover'; ?>">
                        <?php echo $i; ?>
                    </button>
                <?php endfor; ?>
                <button onclick="loadOngoingServices(<?php echo min($totalPagesOngoing, $page + 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == $totalPagesOngoing) ? 'opacity-50 pointer-events-none' : ''; ?>">
                    &rsaquo;
                </button>
                <button onclick="loadOngoingServices(<?php echo $totalPagesOngoing; ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == $totalPagesOngoing) ? 'opacity-50 pointer-events-none' : ''; ?>">
                    &raquo;
                </button>
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
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
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
                    <i class="fas fa-calendar text-sidebar-accent"></i> Interment Date
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
                        <button class="p-2 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-200 transition-all tooltip" 
                                title="Send Payment Reminder" 
                                onclick="sendPaymentReminder(<?php echo $row['sales_id']; ?>)">
                          <i class="fas fa-comment-dots"></i>
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
    <div id="paginationContainerFullyPaid" class="flex space-x-2" data-table="fullyPaid">
        <?php
        $totalPagesFullyPaid = ceil($totalServices / $recordsPerPage);
        ?>
        <button onclick="loadFullyPaidServices(1)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($fullyPaidPage == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &laquo;
        </button>
        <button onclick="loadFullyPaidServices(<?php echo max(1, $fullyPaidPage - 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($fullyPaidPage == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &lsaquo;
        </button>
        <?php
        $startPage = max(1, min($fullyPaidPage - 1, $totalPagesFullyPaid - 2));
        $endPage = min($totalPagesFullyPaid, $startPage + 2);
        for ($i = $startPage; $i <= $endPage; $i++): 
        ?>
            <button onclick="loadFullyPaidServices(<?php echo $i; ?>)" class="px-3.5 py-1.5 rounded text-sm <?php echo ($i == $fullyPaidPage) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover'; ?>">
                <?php echo $i; ?>
            </button>
        <?php endfor; ?>
        <button onclick="loadFullyPaidServices(<?php echo min($totalPagesFullyPaid, $fullyPaidPage + 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($fullyPaidPage >= $totalPagesFullyPaid) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &rsaquo;
        </button>
        <button onclick="loadFullyPaidServices(<?php echo $totalPagesFullyPaid; ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($fullyPaidPage >= $totalPagesFullyPaid) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &raquo;
        </button>
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

                    <!-- Filter Dropdown -->
            <!-- Filter Dropdown -->
<div class="relative filter-dropdown">
    <button id="filterToggleOutstanding" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
        <i class="fas fa-filter text-sidebar-accent"></i>
        <span>Filters</span>
        <span id="filterIndicatorOutstanding" class="hidden h-2 w-2 bg-sidebar-accent rounded-full"></span>
    </button>
    <div id="filterDropdownOutstanding" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
        <div class="space-y-4">
            <div>
                <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                <div class="space-y-1">
                    <div class="flex items-center cursor-pointer" data-sort="id_asc">
                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">ID: Ascending</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="id_desc">
                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">ID: Descending</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_asc">
                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Client: A-Z</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_desc">
                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Client: Z-A</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_asc">
                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Date: Oldest First</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_desc">
                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Date: Newest First</span>
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
                                    <i class="fas fa-calendar text-sidebar-accent"></i> Interment Date
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
                                            <button class="p-2 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-200 transition-all tooltip" 
                                                    title="Send Payment Reminder" 
                                                    onclick="sendPaymentReminder(<?php echo $row['sales_id']; ?>)">
                                              <i class="fas fa-comment-dots"></i>
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
        if ($totalOutstanding > 0) {
            $start = $offsetOutstanding + 1;
            $end = min($offsetOutstanding + $recordsPerPage, $totalOutstanding);
            echo "Showing {$start} - {$end} of {$totalOutstanding} services";
        } else {
            echo "No services found";
        }
        ?>
    </div>
    <div id="paginationContainerOutstanding" class="flex space-x-2" data-table="outstanding">
        <?php
        $totalPagesOutstanding = ceil($totalOutstanding / $recordsPerPage);
        ?>
        <button onclick="loadOutstandingServices(1)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($outstandingPage == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &laquo;
        </button>
        <button onclick="loadOutstandingServices(<?php echo max(1, $outstandingPage - 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($outstandingPage == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &lsaquo;
        </button>
        <?php
        $startPage = max(1, min($outstandingPage - 1, $totalPagesOutstanding - 2));
        $endPage = min($totalPagesOutstanding, $startPage + 2);
        for ($i = $startPage; $i <= $endPage; $i++): 
        ?>
            <button onclick="loadOutstandingServices(<?php echo $i; ?>)" class="px-3.5 py-1.5 rounded text-sm <?php echo ($i == $outstandingPage) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover'; ?>">
                <?php echo $i; ?>
            </button>
        <?php endfor; ?>
        <button onclick="loadOutstandingServices(<?php echo min($totalPagesOutstanding, $outstandingPage + 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($outstandingPage >= $totalPagesOutstanding) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &rsaquo;
        </button>
        <button onclick="loadOutstandingServices(<?php echo $totalPagesOutstanding; ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($outstandingPage >= $totalPagesOutstanding) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &raquo;
        </button>
    </div>
</div>
  </div>
            </div> 

  <!-- Custom Sales Tab Content -->
  <div id="custom-content" class="tab-content hidden">
  <!-- Ongoing Custom Services Section -->
  <div id="ongoing-custom-services-management" class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
    <!-- Header Section -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3 mb-4 lg:mb-0">
          <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Ongoing Custom Services</h4>
          <?php
          $countQuery = "SELECT COUNT(*) as total FROM customsales_tb WHERE status = 'Pending'";
          $countResult = $conn->query($countQuery);
          $totalOngoingCustom = $countResult->fetch_assoc()['total'];
          ?>
          <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
            <?php echo $totalOngoingCustom; ?>
          </span>
        </div>
        <div class="hidden lg:flex items-center gap-3">
          <div class="relative">
            <input type="text" id="searchCustomOngoing" 
                   placeholder="Search services..." 
                   class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                   oninput="debouncedFilterCustomOngoing()">
            <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
          </div>
          <div class="relative filter-dropdown">
            <button id="filterToggleCustomOngoing" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
              <i class="fas fa-filter text-sidebar-accent"></i>
              <span>Filters</span>
              <span id="filterIndicatorCustomOngoing" class="hidden h-2 w-2 bg-sidebar-accent rounded-full"></span>
            </button>
            <div id="filterDropdownCustomOngoing" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
              <div class="space-y-4">
                <div>
                  <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                  <div class="space-y-1">
                    <div class="flex items-center cursor-pointer" data-sort="id_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">ID: Ascending</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="id_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">ID: Descending</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Client: A-Z</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Client: Z-A</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Date: Oldest First</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Date: Newest First</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="lg:hidden w-full mt-4">
        <div class="flex items-center w-full gap-3 mb-4">
          <div class="relative flex-grow">
            <input type="text" id="searchCustomOngoingMobile" 
                   placeholder="Search services..." 
                   class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                   oninput="debouncedFilterCustomOngoing()">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
          </div>
          <div class="relative filter-dropdown">
            <button id="filterToggleCustomOngoingMobile" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
              <i class="fas fa-filter text-xl"></i>
              <span id="filterIndicatorCustomOngoingMobile" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
            </button>
            <div id="filterDropdownCustomOngoingMobile" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
              <div class="space-y-4">
                <div>
                  <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                  <div class="space-y-2">
                    <div class="flex items-center cursor-pointer" data-sort="id_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">ID: Ascending</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="id_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">ID: Descending</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">Client: A-Z</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">Client: Z-A</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">Date: Oldest First</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">Date: Newest First</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="overflow-x-auto scrollbar-thin" id="customOngoingTableContainer">
      <div id="customOngoingLoadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
      </div>
      <div class="min-w-full">
        <table class="w-full">
          <thead>
            <tr class="bg-gray-50 border-b border-sidebar-border">
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-hashtag text-sidebar-accent"></i> ID
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomOngoingTable(1)">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-user text-sidebar-accent"></i> Client
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomOngoingTable(2)">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-user-alt text-sidebar-accent"></i> Deceased
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomOngoingTable(3)">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-tag text-sidebar-accent"></i> Service Type
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomOngoingTable(4)">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-calendar text-sidebar-accent"></i> Interment Date
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomOngoingTable(5)">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-toggle-on text-sidebar-accent"></i> Status
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomOngoingTable(6)">
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
          <tbody id="customOngoingTableBody">
            <!-- Data will be loaded via AJAX -->
          </tbody>
        </table>
      </div>
    </div>
    <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
      <div id="paginationInfoCustomOngoing" class="text-sm text-gray-500 text-center sm:text-left">
        <!-- Pagination info will be updated via AJAX -->
      </div>
      <div id="paginationContainerCustomOngoing" class="flex space-x-2" data-table="customOngoing">
        <!-- Pagination buttons will be updated via AJAX -->
      </div>
    </div>
  </div>

  <!-- Past Custom Services - Fully Paid Section -->
  <div id="past-custom-services-fully-paid" class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3 mb-4 lg:mb-0">
          <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Past Custom Services - Fully Paid</h4>
          <?php
          $countQuery = "SELECT COUNT(*) as total FROM customsales_tb WHERE status = 'Completed' AND payment_status = 'Fully Paid' AND balance = 0";
          $countResult = $conn->query($countQuery);
          $totalCustomFullyPaid = $countResult->fetch_assoc()['total'];
          ?>
          <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
            <?php echo $totalCustomFullyPaid; ?>
          </span>
        </div>
        <div class="hidden lg:flex items-center gap-3">
          <div class="relative">
            <input type="text" id="searchCustomFullyPaid" 
                   placeholder="Search services..." 
                   class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                   oninput="debouncedFilterCustomFullyPaid()">
            <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
          </div>
          <div class="relative filter-dropdown">
            <button id="filterToggleCustomFullyPaid" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
              <i class="fas fa-filter text-sidebar-accent"></i>
              <span>Filters</span>
              <span id="filterIndicatorCustomFullyPaid" class="hidden h-2 w-2 bg-sidebar-accent rounded-full"></span>
            </button>
            <div id="filterDropdownCustomFullyPaid" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
              <div class="space-y-4">
                <div>
                  <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                  <div class="space-y-1">
                    <div class="flex items-center cursor-pointer" data-sort="id_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">ID: Ascending</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="id_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">ID: Descending</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Client: A-Z</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Client: Z-A</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Date: Oldest First</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Date: Newest First</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="lg:hidden w-full mt-4">
        <div class="flex items-center w-full gap-3 mb-4">
          <div class="relative flex-grow">
            <input type="text" id="searchCustomFullyPaidMobile" 
                   placeholder="Search services..." 
                   class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                   oninput="debouncedFilterCustomFullyPaid()">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
          </div>
          <div class="relative filter-dropdown">
            <button id="filterToggleCustomFullyPaidMobile" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
              <i class="fas fa-filter text-xl"></i>
              <span id="filterIndicatorCustomFullyPaidMobile" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
            </button>
            <div id="filterDropdownCustomFullyPaidMobile" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
              <div class="space-y-4">
                <div>
                  <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                  <div class="space-y-2">
                    <div class="flex items-center cursor-pointer" data-sort="id_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">ID: Ascending</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="id_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">ID: Descending</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">Client: A-Z</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">Client: Z-A</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">Date: Oldest First</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">Date: Newest First</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="overflow-x-auto scrollbar-thin" id="customFullyPaidTableContainer">
      <div id="customFullyPaidLoadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
      </div>
      <div class="min-w-full">
        <table class="w-full">
          <thead>
            <tr class="bg-gray-50 border-b border-sidebar-border">
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-hashtag text-sidebar-accent"></i> ID
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomFullyPaidTable(1)">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-user text-sidebar-accent"></i> Client
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomFullyPaidTable(2)">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-user-alt text-sidebar-accent"></i> Deceased
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomFullyPaidTable(3)">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-tag text-sidebar-accent"></i> Service Type
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomFullyPaidTable(4)">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-calendar text-sidebar-accent"></i> Interment Date
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomFullyPaidTable(5)">
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
          <tbody id="customFullyPaidTableBody">
            <!-- Data will be loaded via AJAX -->
          </tbody>
        </table>
      </div>
    </div>
    <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
      <div id="paginationInfoCustomFullyPaid" class="text-sm text-gray-500 text-center sm:text-left">
        <!-- Pagination info will be updated via AJAX -->
      </div>
      <div id="paginationContainerCustomFullyPaid" class="flex space-x-2" data-table="customFullyPaid">
        <!-- Pagination buttons will be updated via AJAX -->
      </div>
    </div>
  </div>

  <!-- Past Custom Services - With Outstanding Balance Section -->
  <div id="past-custom-services-outstanding" class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
      <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3 mb-4 lg:mb-0">
          <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Past Custom Services - With Outstanding Balance</h4>
          <?php
          $countQuery = "SELECT COUNT(*) as total FROM customsales_tb WHERE status = 'Completed' AND payment_status = 'With Balance'";
          $countResult = $conn->query($countQuery);
          $totalCustomOutstanding = $countResult->fetch_assoc()['total'];
          ?>
          <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
            <?php echo $totalCustomOutstanding; ?>
          </span>
        </div>
        <div class="hidden lg:flex items-center gap-3">
          <div class="relative">
            <input type="text" id="searchCustomOutstanding" 
                   placeholder="Search services..." 
                   class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                   oninput="debouncedFilterCustomOutstanding()">
            <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
          </div>
          <div class="relative filter-dropdown">
            <button id="filterToggleCustomOutstanding" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
              <i class="fas fa-filter text-sidebar-accent"></i>
              <span>Filters</span>
              <span id="filterIndicatorCustomOutstanding" class="hidden h-2 w-2 bg-sidebar-accent rounded-full"></span>
            </button>
            <div id="filterDropdownCustomOutstanding" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
              <div class="space-y-4">
                <div>
                  <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                  <div class="space-y-1">
                    <div class="flex items-center cursor-pointer" data-sort="id_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">ID: Ascending</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="id_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">ID: Descending</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Client: A-Z</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Client: Z-A</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Date: Oldest First</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">Date: Newest First</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="lg:hidden w-full mt-4">
        <div class="flex items-center w-full gap-3 mb-4">
          <div class="relative flex-grow">
            <input type="text" id="searchCustomOutstandingMobile" 
                   placeholder="Search services..." 
                   class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                   oninput="debouncedFilterCustomOutstanding()">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
          </div>
          <div class="relative filter-dropdown">
            <button id="filterToggleCustomOutstandingMobile" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
              <i class="fas fa-filter text-xl"></i>
              <span id="filterIndicatorCustomOutstandingMobile" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
            </button>
            <div id="filterDropdownCustomOutstandingMobile" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
              <div class="space-y-4">
                <div>
                  <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                  <div class="space-y-2">
                    <div class="flex items-center cursor-pointer" data-sort="id_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">ID: Ascending</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="id_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">ID: Descending</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">Client: A-Z</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="client_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">Client: Z-A</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_asc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">Date: Oldest First</span>
                    </div>
                    <div class="flex items-center cursor-pointer" data-sort="date_desc">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">Date: Newest First</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="overflow-x-auto scrollbar-thin" id="customOutstandingTableContainer">
      <div id="customOutstandingLoadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
      </div>
      <div class="min-w-full">
        <table class="w-full">
          <thead>
            <tr class="bg-gray-50 border-b border-sidebar-border">
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-hashtag text-sidebar-accent"></i> ID
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomOutstandingTable(1)">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-user text-sidebar-accent"></i> Client
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomOutstandingTable(2)">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-user-alt text-sidebar-accent"></i> Deceased
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomOutstandingTable(3)">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-tag text-sidebar-accent"></i> Service Type
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomOutstandingTable(4)">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-calendar text-sidebar-accent"></i> Interment Date
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomOutstandingTable(5)">
                <div class="flex items-center gap-1.5">
                  <i class="fas fa-toggle-on text-sidebar-accent"></i> Status
                </div>
              </th>
              <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomOutstandingTable(6)">
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
          <tbody id="customOutstandingTableBody">
            <!-- Data will be loaded via AJAX -->
          </tbody>
        </table>
      </div>
    </div>
    <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
      <div id="paginationInfoCustomOutstanding" class="text-sm text-gray-500 text-center sm:text-left">
        <!-- Pagination info will be updated via AJAX -->
      </div>
      <div id="paginationContainerCustomOutstanding" class="flex space-x-2" data-table="customOutstanding">
        <!-- Pagination buttons will be updated via AJAX -->
      </div>
    </div>
  </div>
</div>

<footer class="bg-white rounded-lg shadow-sidebar border border-sidebar-border p-4 text-center text-sm text-gray-500 mt-8">
    <p>© 2025 GrievEase.</p>
  </footer>

            

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
                <select id="nameSuffix" name="nameSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
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
                <select id="deceasedSuffix" name="deceasedSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
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

            <!-- Deceased Address - Display and Change Section -->
            <div class="form-group mb-4">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Deceased Address
              </label>
              
              <!-- Current Address Display (readonly) -->
              <div class="mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <label class="block text-xs font-medium text-gray-500 mb-1">Current Address</label>
                <input 
                  type="text" 
                  id="currentAddressDisplay" 
                  class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  readonly
                >
                <button 
                  type="button" 
                  class="mt-2 text-xs text-sidebar-accent hover:text-darkgold transition-colors underline"
                  onclick="toggleAddressChange()"
                >
                  Change Address
                </button>
              </div>
              
              <!-- Address Change Section (initially hidden) -->
              <div id="addressChangeSection" class="hidden">
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
                
                <div class="flex justify-end mt-3">
                  <button 
                    type="button" 
                    class="text-xs text-gray-500 hover:text-gray-700 transition-colors"
                    onclick="cancelAddressChange()"
                  >
                    Cancel
                  </button>
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
                  Interment Date
                </label>
                <input 
                  type="date" 
                  id="burialDate" 
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                >
              </div>
            </div>

            <!-- Death Certificate Upload with Preview -->
            <div class="form-group mt-4">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Death Certificate
              </label>
              
              <!-- Preview Section -->
              <div id="deathCertPreview" class="mb-3 hidden">
                <p class="text-xs text-gray-500 mb-2">Current Death Certificate:</p>
                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border">
                  <img id="deathCertPreviewImg" src="" alt="Death Certificate Preview" class="h-16 w-16 object-cover rounded border">
                  <div class="flex-1">
                    <p id="deathCertPreviewName" class="text-sm font-medium text-gray-700"></p>
                    <button 
                      type="button" 
                      onclick="removeDeathCertificate()"
                      class="text-xs text-red-600 hover:text-red-800 mt-1"
                    >
                      Remove
                    </button>
                  </div>
                </div>
              </div>

              <!-- Upload Section -->
              <div id="deathCertUpload" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg">
                <div class="space-y-1 text-center">
                  <div class="flex text-sm text-gray-600 justify-center">
                    <label for="deathCertificate" class="relative cursor-pointer bg-white rounded-md font-medium text-sidebar-accent hover:text-opacity-80 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-sidebar-accent">
                      <span>Upload a file</span>
                      <input 
                        id="deathCertificate" 
                        name="deathCertificate" 
                        type="file" 
                        class="sr-only"
                        accept=".pdf,.jpg,.jpeg,.png"
                        onchange="previewDeathCertificate(this)"
                      >
                    </label>
                    <p class="pl-1">or drag and drop</p>
                  </div>
                  <p class="text-xs text-gray-500">PNG, JPG, PDF up to 10MB</p>
                </div>
              </div>
              <p id="death-cert-file-name" class="mt-2 text-sm text-gray-500"></p>
            </div>

            <!-- Senior/PWD Discount ID Upload -->
            <div id="discountUploadSection" class="form-group mt-4 hidden">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Senior/PWD Discount ID
              </label>
              
              <!-- Preview Section -->
              <div id="discountIdPreview" class="mb-3 hidden">
                <p class="text-xs text-gray-500 mb-2">Current Discount ID:</p>
                <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg border">
                  <img id="discountIdPreviewImg" src="" alt="Discount ID Preview" class="h-16 w-16 object-cover rounded border">
                  <div class="flex-1">
                    <p id="discountIdPreviewName" class="text-sm font-medium text-gray-700"></p>
                    <button 
                      type="button" 
                      onclick="removeDiscountId()"
                      class="text-xs text-red-600 hover:text-red-800 mt-1"
                    >
                      Remove
                    </button>
                  </div>
                </div>
              </div>

              <!-- Upload Section -->
              <div id="discountIdUpload" class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg">
                <div class="space-y-1 text-center">
                  <div class="flex text-sm text-gray-600 justify-center">
                    <label for="discountIdFile" class="relative cursor-pointer bg-white rounded-md font-medium text-sidebar-accent hover:text-opacity-80 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-sidebar-accent">
                      <span>Upload Discount ID</span>
                      <input 
                        id="discountIdFile" 
                        name="discountIdFile" 
                        type="file" 
                        class="sr-only"
                        accept=".pdf,.jpg,.jpeg,.png"
                        onchange="previewDiscountId(this)"
                      >
                    </label>
                    <p class="pl-1">or drag and drop</p>
                  </div>
                  <p class="text-xs text-gray-500">PNG, JPG, PDF up to 10MB</p>
                </div>
              </div>
              <p id="discount-file-name" class="mt-2 text-sm text-gray-500"></p>
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
          <label for="internmentPlace" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Interment Place
          </label>
          <div class="relative">
            <input type="text" id="internmentPlace" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Type to search cemetery..." autocomplete="off">
            <div id="internmentSuggestions" class="absolute z-20 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-y-auto hidden"></div>
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

        <!-- Funeral Chapel Section -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <div class="flex items-center mb-2">
            <input type="checkbox" id="usedFuneralChapel" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
            <label for="usedFuneralChapel" class="text-xs sm:text-sm text-gray-700 font-medium">
              Did the customer use the funeral chapel? (₱6,000 per day)
            </label>
          </div>
          
          <!-- Chapel Days Input (Hidden by default) -->
          <div id="chapelDaysContainer" class="hidden mt-3">
            <label for="chapelDays" class="block text-xs font-medium text-gray-700 mb-1">
              Number of Chapel Days
            </label>
            <div class="relative">
              <input type="number" id="chapelDays" min="0" value="0" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
            <p class="text-xs text-gray-500 mt-1">
              Total Chapel Cost: ₱<span id="chapelTotalCost">0</span>
            </p>
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
              <label class="block text-xs font-medium text-gray-500">Service Price</label>
              <div id="servicePriceView" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Branch</label>
              <div id="branchName" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Interment Date</label>
              <div id="serviceDate" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Status</label>
              <div id="serviceStatus" class="text-sm font-medium text-gray-800">-</div>
            </div>

            <div class="space-y-1 col-span-1 sm:col-span-2">
              <label class="block text-xs font-medium text-gray-500">Interment Place</label>
              <div id="serviceIntermentPlace" class="text-sm font-medium text-gray-800">-</div>
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
      <button id="recordPaymentBtn" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" data-mode="">
        Record Payment
      </button>
    </div>
  </div>
</div>

<!-- Edit Custom Service Modal -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="editCustomServiceModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeEditCustomModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Edit Custom Service
      </h3>
    </div>
    
    <!-- Modal Body - Single Column Layout -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="editCustomServiceForm" class="space-y-3 sm:space-y-6">
        <input type="hidden" id="customSalesId" name="customsales_id">
        <input type="hidden" id="selectedCustomCustomerId" name="customer_id">
        
        <!-- Customer Information Section -->
        <div class="pb-4 border-b border-gray-200">
          <h4 class="text-lg font-semibold flex items-center text-gray-800 mb-4">
            Customer Information
          </h4>
          
          <div class="form-group mb-4">
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Search Customer
            </label>
            <div class="relative">
              <input 
                type="text" 
                id="customCustomerSearch" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Type customer name..."
                autocomplete="off"
              >
              <div id="customCustomerResults" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto hidden">
                <!-- Results will appear here -->
              </div>
            </div>
          </div>         
          
          <!-- Email and Phone -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Email
              </label>
              <input 
                type="email" 
                id="editCustomEmail" 
                name="editCustomEmail"
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
                id="editCustomPhone" 
                name="editCustomPhone"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Enter Phone Number"
              >
            </div>
          </div>
        </div>
        
        <!-- Service Details Section -->
        <div class="pt-4">
          <h4 class="text-lg font-semibold flex items-center text-gray-800 mb-4">
            Service Details
          </h4>
          
          <!-- Service Items -->
          <div class="space-y-4 mb-4">
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Casket
              </label>
              <input 
                type="text" 
                id="editCustomCasket" 
                name="editCustomCasket"
                class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Enter Casket Details"
                readonly
              >
            </div>
            
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Flower Arrangement
              </label>
              <input 
                type="text" 
                id="editCustomFlowerArrangement" 
                name="editCustomFlowerArrangement"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Enter Flower Arrangement Details"
              >
            </div>
            
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Additional Services
              </label>
              <textarea 
                id="editCustomAdditionalServices" 
                name="editCustomAdditionalServices"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Enter Additional Services"
                rows="4"
              ></textarea>
            </div>

            <div class="form-group flex items-center">
              <input 
                type="checkbox" 
                id="editCustomWithCremation" 
                name="editCustomWithCremation"
                class="h-4 w-4 text-sidebar-accent focus:ring-sidebar-accent border-gray-300 rounded"
              >
              <label class="ml-2 block text-xs font-medium text-gray-700">
                With Cremation
              </label>
            </div>
            
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Service Price
              </label>
              <input 
                type="number" 
                id="editCustomServicePrice" 
                name="editCustomServicePrice"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Enter Service Price"
              >
            </div>
          </div>
        </div>
        
        <!-- Deceased Information Section -->
        <div class="pt-4">
          <h4 class="text-lg font-semibold flex items-center text-gray-800 mb-4">
            Deceased Information
          </h4>
          
          <!-- Name Fields - 2 columns -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                First Name
              </label>
              <input 
                type="text" 
                id="editCustomDeceasedFirstName" 
                name="editCustomDeceasedFirstName"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Enter First Name"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Last Name
              </label>
              <input 
                type="text" 
                id="editCustomDeceasedLastName" 
                name="editCustomDeceasedLastName"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Enter Last Name"
              >
            </div>
          </div>
          
          <!-- Middle Name and Suffix - 2 columns -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Middle Name
              </label>
              <input 
                type="text" 
                id="editCustomDeceasedMiddleName" 
                name="editCustomDeceasedMiddleName"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Enter Middle Name"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Suffix
              </label>
              <select id="editCustomDeceasedSuffix" name="editCustomDeceasedSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
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
          
          <!-- Dates - 3 columns -->
          <div class="grid grid-cols-3 gap-4 mb-4">
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Birth Date
              </label>
              <input 
                type="date" 
                id="editCustomBirthDate" 
                name="editCustomBirthDate"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Death Date
              </label>
              <input 
                type="date" 
                id="editCustomDeathDate" 
                name="editCustomDeathDate"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Burial Date
              </label>
              <input 
                type="date" 
                id="editCustomBurialDate" 
                name="editCustomBurialDate"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              >
            </div>
          </div>
          
          <!-- Deceased Address -->
          <div class="form-group mb-4">
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Deceased Address
            </label>
            
            <!-- Current Address Display (readonly) -->
            <div class="mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
              <label class="block text-xs font-medium text-gray-500 mb-1">Current Address</label>
              <input 
                type="text" 
                id="editCustomCurrentAddressDisplay" 
                name="editCustomCurrentAddressDisplay"
                class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                readonly
              >
              <button 
                type="button" 
                class="mt-2 text-xs text-sidebar-accent hover:text-darkgold transition-colors underline"
                onclick="toggleCustomAddressChange()"
              >
                Change Address
              </button>
            </div>
            
            <!-- Address Change Section (initially hidden) -->
            <div id="editCustomAddressChangeSection" class="hidden">
              <!-- Region Dropdown -->
              <div class="mb-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Region</label>
                <select 
                  id="editCustomRegionSelect" 
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  onchange="loadCustomProvinces()"
                >
                  <option value="">Select Region</option>
                  <!-- Regions will be loaded dynamically -->
                </select>
              </div>
              
              <!-- Province Dropdown -->
              <div class="mb-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Province</label>
                <select 
                  id="editCustomProvinceSelect" 
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  disabled
                  onchange="loadCustomCities()"
                >
                  <option value="">Select Province</option>
                  <!-- Provinces will be loaded dynamically -->
                </select>
              </div>
              
              <!-- City/Municipality Dropdown -->
              <div class="mb-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">City/Municipality</label>
                <select 
                  id="editCustomCitySelect" 
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  disabled
                  onchange="loadCustomBarangays()"
                >
                  <option value="">Select City/Municipality</option>
                  <!-- Cities will be loaded dynamically -->
                </select>
              </div>
              
              <!-- Barangay Dropdown -->
              <div class="mb-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Barangay</label>
                <select 
                  id="editCustomBarangaySelect" 
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
                    id="editCustomStreetInput" 
                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                    placeholder="Street name, building, etc."
                  >
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1">Zip Code</label>
                  <input 
                    type="number" 
                    id="editCustomZipCodeInput" 
                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                    placeholder="Zip Code"
                    inputmode="numeric"
                    pattern="[0-9]*"
                  >
                </div>
              </div>
              
              <div class="flex justify-end mt-3">
                <button 
                  type="button" 
                  class="text-xs text-gray-500 hover:text-gray-700 transition-colors"
                  onclick="cancelCustomAddressChange()"
                >
                  Cancel
                </button>
              </div>
            </div>
          </div>
          
          <!-- Death Certificate Upload -->
          <div class="form-group mb-4">
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Death Certificate
            </label>
            
            <!-- Preview container -->
            <div id="deathCertPreviewContainer" class="mb-2 hidden">
              <img id="deathCertPreview" src="" alt="Death Certificate Preview" class="max-w-full h-auto max-h-48 border border-gray-300 rounded-lg">
            </div>
            
            <input 
              type="file" 
              id="editCustomDeathCertificate" 
              name="editCustomDeathCertificate"
              class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              accept=".pdf,.jpg,.jpeg,.png"
            >
          </div>
        </div>
        
        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
          <button 
            type="button" 
            onclick="closeEditCustomModal()"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent"
          >
            Cancel
          </button>
          <button 
            type="submit"
            class="px-4 py-2 text-sm font-medium text-white bg-sidebar-accent border border-transparent rounded-md hover:bg-darkgold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent"
          >
            Save Changes
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div id="assignCustomStaffModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeAssignCustomStaffModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Assign Staff to Custom Service
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="assignCustomStaffForm" class="space-y-3 sm:space-y-4">
        <input type="hidden" id="assignCustomServiceId">
        
        <?php
        // This will be populated by JavaScript when the modal opens
        $branch_id = 0;
        
        // Function to generate employee checkboxes by position
        function generateCustomEmployeeCheckboxes($position, $employees) {
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
                    echo '<input type="checkbox" id="custom'.$positionLower.$count.'" name="assigned_custom_staff[]" value="'.$employeeId.'" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">';
                    echo '<label for="custom'.$positionLower.$count.'" class="text-gray-700">'.$fullName.'</label>';
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
          <div id="customEmbalmersSection" class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
            <!-- Embalmers will be loaded here -->
          </div>
          
          <div id="customDriversSection" class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
            <!-- Drivers will be loaded here -->
          </div>
          
          <div id="customPersonnelSection" class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
            <!-- Personnel will be loaded here -->
          </div>
          
          <div>
            <label for="customAssignmentNotes" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Notes
            </label>
            <div class="relative">
              <textarea id="customAssignmentNotes" rows="5" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"></textarea>
            </div>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeAssignCustomStaffModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="saveCustomStaffAssignment()">
        Save Assignment
      </button>
    </div>
  </div>
</div>
  
<div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="completeCustomServiceModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeCompleteCustomModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Complete Custom Service
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="completeCustomServiceForm" class="space-y-4">
        <input type="hidden" id="completeCustomServiceId" name="customsales_id">
        
        <!-- Completion Date -->
        <div class="form-group">
          <label class="block text-sm font-medium text-gray-700 mb-1">Completion Date</label>
          <input 
            type="date" 
            id="customCompletionDate" 
            name="completion_date"
            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
          >
        </div>

        <!-- Assigned Staff Review -->
        <div id="customCompleteDriversSection" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
          <h4 class="text-sm font-semibold text-gray-700 mb-3">Assigned Drivers</h4>
          <div id="customCompleteDriversList">
            <!-- Assigned drivers will be populated here -->
          </div>
        </div>

        <div id="customCompletePersonnelSection" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
          <h4 class="text-sm font-semibold text-gray-700 mb-3">Assigned Personnel</h4>
          <div id="customCompletePersonnelList">
            <!-- Assigned personnel will be populated here -->
          </div>
        </div>

        <!-- Notes -->
        <div class="form-group">
          <label class="block text-sm font-medium text-gray-700 mb-1">Completion Notes</label>
          <textarea 
            id="customCompletionNotes" 
            name="completion_notes"
            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
            placeholder="Add any notes about the service completion..."
            rows="3"
          ></textarea>
        </div>

        <!-- Final Balance Settlement -->
        <div class="form-group">
          <label class="flex items-center space-x-2">
            <input 
              type="checkbox" 
              id="customFinalBalanceSettled" 
              name="final_balance_settled"
              class="rounded border-gray-300 text-sidebar-accent focus:ring-sidebar-accent"
            >
            <span class="text-sm text-gray-700">Final balance has been settled</span>
          </label>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
          <button 
            type="button" 
            onclick="closeCompleteCustomModal()"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent"
          >
            Cancel
          </button>
          <button 
            type="submit"
            class="px-4 py-2 text-sm font-medium text-white bg-sidebar-accent border border-transparent rounded-md hover:bg-darkgold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent"
          >
            Complete Service
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto" id="viewCustomServiceModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-xl w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeViewCustomServiceModal()">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
      </svg>
    </button>
    
    <!-- Modal Header -->
    <div class="px-5 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-white border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Custom Service Details
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
              <div id="customServiceId" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Client Name</label>
              <div id="customServiceClientName" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Service Price</label>
              <div id="customServicePrice" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Branch</label>
              <div id="customBranchName" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Interment Date</label>
              <div id="customServiceDate" class="text-sm font-medium text-gray-800">-</div>
            </div>
            
            <div class="space-y-1">
              <label class="block text-xs font-medium text-gray-500">Status</label>
              <div id="customServiceStatus" class="text-sm font-medium text-gray-800">-</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Service Components -->
      <div class="rounded-lg border border-gray-200 overflow-hidden mb-5">
        <div class="bg-gray-50 px-4 py-2 border-b border-gray-200">
          <h4 class="font-medium text-gray-700">Service Components</h4>
        </div>
        <div class="p-4">
          <div class="space-y-4">
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Casket</label>
              <div id="customServiceCasket" class="text-sm text-gray-800">-</div>
            </div>
            
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Flower Arrangement</label>
              <div id="customServiceFlowers" class="text-sm text-gray-800">-</div>
            </div>
            
            <div>
              <label class="block text-xs font-medium text-gray-500 mb-1">Additional Services</label>
              <div id="customServiceAdditional" class="text-sm text-gray-800">-</div>
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
          <div class="space-y-3">
            <div>
              <label class="block text-xs font-medium text-gray-500">Date</label>
              <div id="customInitialDate" class="text-sm text-gray-800">-</div>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500">Embalmers</label>
              <div id="customInitialEmbalmers" class="text-sm text-gray-800">-</div>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500">Drivers</label>
              <div id="customInitialDrivers" class="text-sm text-gray-800">-</div>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500">Personnel</label>
              <div id="customInitialPersonnel" class="text-sm text-gray-800">-</div>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500">Notes</label>
              <div id="customInitialNotes" class="text-sm text-gray-800">-</div>
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
          <div class="space-y-3">
            <div>
              <label class="block text-xs font-medium text-gray-500">Date</label>
              <div id="customBurialDate" class="text-sm text-gray-800">-</div>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500">Drivers</label>
              <div id="customBurialDrivers" class="text-sm text-gray-800">-</div>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500">Personnel</label>
              <div id="customBurialPersonnel" class="text-sm text-gray-800">-</div>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500">Notes</label>
              <div id="customBurialNotes" class="text-sm text-gray-800">-</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

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

  document.getElementById('recordPaymentBtn').setAttribute('data-mode', 'traditional');
  
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
  
  // Validate payment amount not greater than current balance
  if (paymentAmount > currentBalance) {
    swal({
      title: "Invalid Payment Amount",
      text: "Payment amount cannot be greater than current balance.",
      icon: "error",
      button: "OK",
    });
    return;
  }
  
  // Validate all fields
  if (!serviceId || !customerID || !branchID || !clientName || isNaN(paymentAmount) || 
      paymentAmount <= 0 || !paymentMethod || !paymentDate) {
    swal({
      title: "Validation Error",
      text: "Please fill all fields with valid values.",
      icon: "error",
      button: "OK",
    });
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
  const saveBtn = document.getElementById('recordPaymentBtn');
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
      swal({
        title: "Success!",
        text: `Payment recorded successfully! Total paid: ₱${data.new_amount_paid.toFixed(2)}`,
        icon: "success",
        button: "OK",
      }).then(() => {
        // Refresh the page to show updated values
        location.reload();
      });
    } else {
      throw new Error(data.message || 'Failed to record payment');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    swal({
      title: "Error!",
      text: error.message,
      icon: "error",
      button: "OK",
    });
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

// Preview Death Certificate
function previewDeathCertificate(input) {
  const preview = document.getElementById('deathCertPreview');
  const previewImg = document.getElementById('deathCertPreviewImg');
  const previewName = document.getElementById('deathCertPreviewName');
  const uploadSection = document.getElementById('deathCertUpload');
  const fileName = document.getElementById('death-cert-file-name');

  if (input.files && input.files[0]) {
    const file = input.files[0];
    
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = function(e) {
        previewImg.src = e.target.result;
      }
      reader.readAsDataURL(file);
    } else {
      previewImg.src = '../assets/icons/pdf-icon.png'; // Add a PDF icon
    }
    
    previewName.textContent = file.name;
    fileName.textContent = `Selected file: ${file.name}`;
    preview.classList.remove('hidden');
    uploadSection.classList.add('hidden');
  }
}

// Remove Death Certificate
function removeDeathCertificate() {
  const input = document.getElementById('deathCertificate');
  const preview = document.getElementById('deathCertPreview');
  const uploadSection = document.getElementById('deathCertUpload');
  const fileName = document.getElementById('death-cert-file-name');
  
  input.value = '';
  preview.classList.add('hidden');
  uploadSection.classList.remove('hidden');
  fileName.textContent = '';
}

// Preview Discount ID
function previewDiscountId(input) {
  const preview = document.getElementById('discountIdPreview');
  const previewImg = document.getElementById('discountIdPreviewImg');
  const previewName = document.getElementById('discountIdPreviewName');
  const uploadSection = document.getElementById('discountIdUpload');
  const fileName = document.getElementById('discount-file-name');

  if (input.files && input.files[0]) {
    const file = input.files[0];
    
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = function(e) {
        previewImg.src = e.target.result;
      }
      reader.readAsDataURL(file);
    } else {
      previewImg.src = '../assets/icons/pdf-icon.png';
    }
    
    previewName.textContent = file.name;
    fileName.textContent = `Selected file: ${file.name}`;
    preview.classList.remove('hidden');
    uploadSection.classList.add('hidden');
  }
}

// Remove Discount ID
function removeDiscountId() {
  const input = document.getElementById('discountIdFile');
  const preview = document.getElementById('discountIdPreview');
  const uploadSection = document.getElementById('discountIdUpload');
  const fileName = document.getElementById('discount-file-name');
  
  input.value = '';
  preview.classList.add('hidden');
  uploadSection.classList.remove('hidden');
  fileName.textContent = '';
}

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
        document.getElementById('currentAddressDisplay').value = data.deceased_address || '';
        document.getElementById('email').value = data.email || '';
        document.getElementById('phone').value = data.phone || '';
        
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

        // Handle Death Certificate Preview
        if (data.death_cert_image) {
          const deathCertPath = `../customer/booking/${data.death_cert_image}`;
          document.getElementById('deathCertPreviewImg').src = deathCertPath;
          document.getElementById('deathCertPreviewName').textContent = 'Death Certificate';
          document.getElementById('deathCertPreview').classList.remove('hidden');
          document.getElementById('deathCertUpload').classList.add('hidden');
        } else {
          document.getElementById('deathCertPreview').classList.add('hidden');
          document.getElementById('deathCertUpload').classList.remove('hidden');
        }

        // Handle Senior/PWD Discount Upload Section
        const discountSection = document.getElementById('discountUploadSection');
        if (data.senior_pwd_discount === "Yes") {
          discountSection.classList.remove('hidden');
          
          // Show existing discount ID if available
          if (data.discount_id_img) {
            const discountPath = `${data.discount_id_img}`;
            document.getElementById('discountIdPreviewImg').src = discountPath;
            document.getElementById('discountIdPreviewName').textContent = 'Discount ID';
            document.getElementById('discountIdPreview').classList.remove('hidden');
            document.getElementById('discountIdUpload').classList.add('hidden');
          } else {
            document.getElementById('discountIdPreview').classList.add('hidden');
            document.getElementById('discountIdUpload').classList.remove('hidden');
          }
        } else {
          discountSection.classList.add('hidden');
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
  // Check if selectedCustomerId is null or empty
  const selectedCustomerId = document.getElementById('selectedCustomerId').value;
  
 /* if (!selectedCustomerId) {
    Swal.fire({
      icon: 'warning',
      title: 'Customer Account Required',
      text: 'You need to connect this to a customer account first before changing some details.',
      confirmButtonColor: '#3085d6',
      confirmButtonText: 'OK'
    });
    return; // Stop the function execution
  } */

  // Create FormData object
  const formData = new FormData();
  
  // Add all form data
  formData.append('sales_id', document.getElementById('salesId').value);
  formData.append('customer_id', selectedCustomerId);
  formData.append('service_id', document.getElementById('serviceSelect').value);
  formData.append('service_price', document.getElementById('servicePrice').value);
  formData.append('firstName', document.getElementById('firstName').value);
  formData.append('middleName', document.getElementById('middleName').value);
  formData.append('lastName', document.getElementById('lastName').value);
  formData.append('nameSuffix', document.getElementById('nameSuffix').value);
  formData.append('email', document.getElementById('email').value);
  formData.append('phone', document.getElementById('phone').value);
  formData.append('deceasedFirstName', document.getElementById('deceasedFirstName').value);
  formData.append('deceasedMiddleName', document.getElementById('deceasedMiddleName').value);
  formData.append('deceasedLastName', document.getElementById('deceasedLastName').value);
  formData.append('deceasedSuffix', document.getElementById('deceasedSuffix').value);
  formData.append('birthDate', document.getElementById('birthDate').value);
  formData.append('deathDate', document.getElementById('deathDate').value);
  formData.append('burialDate', document.getElementById('burialDate').value);
  formData.append('deceasedAddress', document.getElementById('currentAddressDisplay').value);
  formData.append('branch', document.querySelector('input[name="branch"]:checked')?.value);

  // Add files if they exist
  const deathCertFile = document.getElementById('deathCertificate').files[0];
  const discountIdFile = document.getElementById('discountIdFile').files[0];
  
  if (deathCertFile) {
    formData.append('deathCertificate', deathCertFile);
  }
  
  if (discountIdFile) {
    formData.append('discountIdFile', discountIdFile);
  }

  // Log the form data to console
  console.log('Service Form Data:', Object.fromEntries(formData));
  
  fetch('history/update_history_sales.php', {
    method: 'POST',
    body: formData // Remove Content-Type header for FormData
  })
  .then(response => response.json())
  .then(data => {
    console.log('Success:', data);
    if (data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Service updated successfully!',
        confirmButtonColor: '#3085d6',
        confirmButtonText: 'OK'
      }).then((result) => {
        if (result.isConfirmed) {
          closeEditServiceModal();
          cancelAddressChange();
          location.reload();
        }
      });
    } else {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error: ' + data.message,
        confirmButtonColor: '#d33',
        confirmButtonText: 'OK'
      });
    }
  })
  .catch(error => {
    console.error('Error:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: 'An error occurred while updating the service',
      confirmButtonColor: '#d33',
      confirmButtonText: 'OK'
    });
  });
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
  document.getElementById('internmentPlace').value = '';
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

// Cemetery data array
const cemeteryData = [
  { district: '3', municipality: 'San Pablo', cemetery: 'San Pablo City Municipal Cemetery', location: 'M. Leonor Ext., San Pablo City' },
  { district: '3', municipality: 'San Pablo', cemetery: 'San Gabriel Memorial Garden', location: 'Brgy. San Gabriel, San Pablo' },
  { district: '3', municipality: 'San Pablo', cemetery: 'Pines Memorial Garden', location: 'San Pablo' },
  { district: '3', municipality: 'Alaminos', cemetery: 'Alaminos Municipal Cemetery', location: 'Alaminos town proper' },
  { district: '3', municipality: 'Alaminos', cemetery: 'Mulberry Garden Memorial Park', location: 'Brgy. San Juan, Alaminos' },
  { district: '3', municipality: 'Alaminos', cemetery: 'Roloma Memorial Park', location: 'Alaminos' },
  { district: '3', municipality: 'Calauan', cemetery: 'Calauan Municipal Cemetery', location: 'Calauan town proper / Balayhangin area' },
  { district: '3', municipality: 'Calauan', cemetery: 'Amani Heritage Gardens / Memorial Gardens', location: 'Provincial Road, Lamot 2, Calauan' },
  { district: '3', municipality: 'Liliw', cemetery: 'Liliw Municipal Cemetery', location: 'Liliw' },
  { district: '3', municipality: 'Liliw', cemetery: 'Golden Haven / Memorial Park (Laguna area)', location: 'near Liliw area' },
  { district: '3', municipality: 'Nagcarlan', cemetery: 'Nagcarlan Underground Cemetery', location: 'Brgy. Bambang, Nagcarlan' },
  { district: '3', municipality: 'Nagcarlan', cemetery: 'Nagcarlan Municipal Cemetery', location: 'Nagcarlan' },
  { district: '3', municipality: 'Rizal', cemetery: 'Rizal Municipal Cemetery', location: 'Rizal, Talaga area' },
  { district: '3', municipality: 'Victoria', cemetery: 'Victoria Municipal Cemetery', location: 'JP Riza St., Brgy. Nanhaya, Victoria' },
  { district: '3', municipality: 'Victoria', cemetery: 'Garden of Angels / Victoria', location: 'Garden of Angels, Victoria' },
  { district: '4', municipality: 'Santa Cruz', cemetery: 'Santa Cruz Municipal Cemetery', location: 'Santa Cruz' },
  { district: '4', municipality: 'Cavinti', cemetery: 'Cavinti Municipal Cemetery', location: 'Cavinti' },
  { district: '4', municipality: 'Famy', cemetery: 'Famy Municipal Cemetery', location: 'Famy' },
  { district: '4', municipality: 'Kalayaan', cemetery: 'Kalayaan Municipal Cemetery', location: 'Kalayaan' },
  { district: '4', municipality: 'Luisiana', cemetery: 'Luisiana Municipal Cemetery', location: 'Luisiana' },
  { district: '4', municipality: 'Lumban', cemetery: 'Lumban Municipal Cemetery', location: 'Lumban' },
  { district: '4', municipality: 'Mabitac', cemetery: 'Mabitac Municipal Cemetery', location: 'Mabitac' },
  { district: '4', municipality: 'Magdalena', cemetery: 'Magdalena Municipal Cemetery', location: 'Magdalena' },
  { district: '4', municipality: 'Majayjay', cemetery: 'Majayjay Municipal Cemetery', location: 'Majayjay' },
  { district: '4', municipality: 'Paete', cemetery: 'Paete Municipal Cemetery', location: 'Paete' },
  { district: '4', municipality: 'Pagsanjan', cemetery: 'Pagsanjan Municipal Cemetery', location: 'Pagsanjan' },
  { district: '4', municipality: 'Pakil', cemetery: 'Pakil (Catholic / Municipal) Cemetery', location: 'Pakil' },
  { district: '4', municipality: 'Pangil', cemetery: 'Pangil Municipal Cemetery', location: 'Pangil' },
  { district: '4', municipality: 'Pila', cemetery: 'Pila Municipal Cemetery', location: 'Pila' },
  { district: '4', municipality: 'Santa Maria', cemetery: 'Santa Maria Municipal Cemetery', location: 'Santa Maria' },
  { district: '4', municipality: 'Siniloan', cemetery: 'Siniloan Municipal Cemetery', location: 'Siniloan' }
];

// Function to filter cemeteries based on search input
function filterCemeteries(searchTerm) {
  if (!searchTerm) return [];
  
  const lowerSearchTerm = searchTerm.toLowerCase();
  return cemeteryData.filter(cemetery => 
    cemetery.cemetery.toLowerCase().includes(lowerSearchTerm) ||
    cemetery.municipality.toLowerCase().includes(lowerSearchTerm) ||
    cemetery.location.toLowerCase().includes(lowerSearchTerm)
  );
}

// Function to show suggestions
function showSuggestions(suggestions) {
  const suggestionsContainer = document.getElementById('internmentSuggestions');
  suggestionsContainer.innerHTML = '';
  
  if (suggestions.length === 0) {
    suggestionsContainer.classList.add('hidden');
    return;
  }
  
  suggestions.forEach(cemetery => {
    const suggestionItem = document.createElement('div');
    suggestionItem.className = 'suggestion-item';
    suggestionItem.innerHTML = `
      <div class="font-medium text-sm">${cemetery.cemetery}</div>
      <div class="text-xs text-gray-600">${cemetery.municipality} - ${cemetery.location}</div>
    `;
    suggestionItem.addEventListener('click', () => {
      document.getElementById('internmentPlace').value = `${cemetery.cemetery}, ${cemetery.municipality}`;
      suggestionsContainer.classList.add('hidden');
    });
    suggestionsContainer.appendChild(suggestionItem);
  });
  
  suggestionsContainer.classList.remove('hidden');
}

// Add event listeners for the internment place input
document.addEventListener('DOMContentLoaded', function() {
  const internmentInput = document.getElementById('internmentPlace');
  const suggestionsContainer = document.getElementById('internmentSuggestions');
  
  if (internmentInput) {
    internmentInput.addEventListener('input', function() {
      const searchTerm = this.value;
      const suggestions = filterCemeteries(searchTerm);
      showSuggestions(suggestions);
    });
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
      if (!internmentInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
        suggestionsContainer.classList.add('hidden');
      }
    });
    
    // Handle keyboard navigation
    internmentInput.addEventListener('keydown', function(e) {
      const suggestions = suggestionsContainer.querySelectorAll('.suggestion-item');
      const activeSuggestion = suggestionsContainer.querySelector('.suggestion-item.active');
      
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!activeSuggestion) {
          suggestions[0]?.classList.add('active');
        } else {
          const next = activeSuggestion.nextElementSibling;
          if (next) {
            activeSuggestion.classList.remove('active');
            next.classList.add('active');
          }
        }
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (activeSuggestion) {
          const prev = activeSuggestion.previousElementSibling;
          if (prev) {
            activeSuggestion.classList.remove('active');
            prev.classList.add('active');
          }
        }
      } else if (e.key === 'Enter' && activeSuggestion) {
        e.preventDefault();
        activeSuggestion.click();
      }
    });
  }
});

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

document.addEventListener('DOMContentLoaded', function() {
    const chapelCheckbox = document.getElementById('usedFuneralChapel');
    const chapelDaysContainer = document.getElementById('chapelDaysContainer');
    const chapelDaysInput = document.getElementById('chapelDays');
    const chapelTotalCost = document.getElementById('chapelTotalCost');
    
    const CHAPEL_RATE_PER_DAY = 6000;
    
    // Toggle chapel days input visibility
    chapelCheckbox.addEventListener('change', function() {
        if (this.checked) {
            chapelDaysContainer.classList.remove('hidden');
            updateChapelTotalCost();
        } else {
            chapelDaysContainer.classList.add('hidden');
            chapelDaysInput.value = '0';
            updateChapelTotalCost();
        }
    });
    
    // Update total cost when chapel days change
    chapelDaysInput.addEventListener('input', updateChapelTotalCost);
    
    function updateChapelTotalCost() {
        const days = parseInt(chapelDaysInput.value) || 0;
        const total = days * CHAPEL_RATE_PER_DAY;
        chapelTotalCost.textContent = total.toLocaleString();
    }
});

// Function to finalize service completion
// Function to finalize service completion
function finalizeServiceCompletion() {
    const serviceId = document.getElementById('completeServiceId').value;
    const completionDateInput = document.getElementById('completionDate').value;
    const completionNotes = document.getElementById('completionNotes').value;
    const balanceSettled = document.getElementById('finalBalanceSettled').checked;
    const intermentPlace = document.getElementById('internmentPlace').value;
    const usedChapel = document.getElementById('usedFuneralChapel').checked;
    const chapelDays = document.getElementById('chapelDays').value || 0;
    
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
                balance_settled: balanceSettled,
                interment_place: intermentPlace,
                used_chapel: usedChapel ? 'Yes' : 'No',
                chapel_days: chapelDays
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
        document.getElementById('servicePriceView').textContent = data.discounted_price ? formatCurrency(data.discounted_price) : '₱0.00';
        console.log(data.discounted_price );
        document.getElementById('branchName').textContent = data.branch_name ? toProperCase(data.branch_name) : 'N/A';
        document.getElementById('serviceDate').textContent = data.date_of_burial ? formatDate(data.date_of_burial) : 'N/A';
        document.getElementById('serviceStatus').textContent = data.status || 'N/A';
        document.getElementById('serviceIntermentPlace').textContent = data.interment_place || 'N/A';
        document.getElementById('serviceOutstandingBalance').textContent = 
          data.balance ? `${formatCurrency(data.balance)}` : '₱0.00';

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

    // ✅ Call PHP API to check pre-burial staff assignment
    fetch('history/check_preburial.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ sales_id: salesId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            openCompleteModal(salesId);
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Pre-Burial Required',
                text: data.message,
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Server Error',
            text: 'Something went wrong. Please try again later.'
        });
    });
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

<script>
function showTab(tabName) {
  console.log('Switching to tab:', tabName); // Debug log
  
  // Hide all tab contents
  const allContents = document.querySelectorAll('.tab-content');
  console.log('Found tab contents:', allContents.length); // Debug log
  allContents.forEach(content => {
    content.style.display = 'none';
    content.classList.add('hidden');
  });
  
  // Remove active class from all tab buttons
  const allButtons = document.querySelectorAll('.tab-button');
  console.log('Found tab buttons:', allButtons.length); // Debug log
  allButtons.forEach(button => {
    button.classList.remove('active', 'border-sidebar-accent', 'text-sidebar-text');
    button.classList.add('text-gray-600', 'border-transparent');
  });
  
  // Show selected tab content
  const tabContent = document.getElementById(tabName + '-content');
  console.log('Selected content element:', tabContent); // Debug log
  if (tabContent) {
    tabContent.style.display = 'block';
    tabContent.classList.remove('hidden');
  }
  
  // Add active class to selected tab button
  const activeButton = document.getElementById(tabName + '-tab');
  console.log('Selected button element:', activeButton); // Debug log
  if (activeButton) {
    activeButton.classList.add('active', 'border-sidebar-accent', 'text-sidebar-text');
    activeButton.classList.remove('text-gray-600', 'border-transparent');
  }
}

// Initialize tabs on page load
document.addEventListener('DOMContentLoaded', function() {
  // Show standard tab by default
  showTab('standard');
});

// Branch Filter Dropdown Toggle
const branchFilterToggle = document.getElementById('branchFilterToggle');
const branchFilterDropdown = document.getElementById('branchFilterDropdown');

// Toggle dropdown visibility
branchFilterToggle.addEventListener('click', function(e) {
    e.stopPropagation(); // Prevent immediate document click handler
    branchFilterDropdown.classList.toggle('hidden');
    
    // Rotate chevron icon
    const chevron = this.querySelector('.fa-chevron-down');
    chevron.classList.toggle('rotate-180');
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!branchFilterDropdown.contains(e.target) && e.target !== branchFilterToggle) {
        branchFilterDropdown.classList.add('hidden');
        // Reset chevron icon rotation
        const chevron = branchFilterToggle.querySelector('.fa-chevron-down');
        chevron.classList.remove('rotate-180');
    }
});

// Branch selection handler
// Branch selection handler
document.querySelectorAll('#branchFilterDropdown button').forEach(button => {
    button.addEventListener('click', function() {
        const branch = this.getAttribute('data-branch');
        const branchId = this.getAttribute('data-branch-id');
        
        // Update button text to show selected branch
        const branchText = this.textContent.trim();
        branchFilterToggle.querySelector('span').textContent = branchText.split('\n')[0];
        
        // Close dropdown
        branchFilterDropdown.classList.add('hidden');
        const chevron = branchFilterToggle.querySelector('.fa-chevron-down');
        chevron.classList.remove('rotate-180');
        
        // Update branch filter in all table states
        for (const table in tableStates) {
            tableStates[table].branch = branchId;
        }
        
        // Reload all tables with the new branch filter
        loadOngoingServices(1);
        loadFullyPaidServices(1);
        loadOutstandingServices(1);
        loadCustomOngoingServices(1);
        loadCustomFullyPaidServices(1);
        loadCustomOutstandingServices(1);
    });
});

// State management for each table
// State management for each table
const tableStates = {
    ongoing: { page: <?php echo $page; ?>, search: '', sort: 'id_asc', branch: 'all' },
    fullyPaid: { page: <?php echo $fullyPaidPage; ?>, search: '', sort: 'id_asc', branch: 'all' },
    outstanding: { page: <?php echo $outstandingPage; ?>, search: '', sort: 'id_asc', branch: 'all' },
    customOngoing: { page: 1, search: '', sort: 'id_asc', branch: 'all' },
    customFullyPaid: { page: 1, search: '', sort: 'id_asc', branch: 'all' },
    customOutstanding: { page: 1, search: '', sort: 'id_asc', branch: 'all' }
};

// Debounce function to limit search/filter requests
function debounce(func, wait) {
    let timeout;
    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(this, args), wait);
    };
}

// Function to load Ongoing Services via AJAX
function loadOngoingServices(page = 1) {
    tableStates.ongoing.page = page;
    const loadingIndicator = document.getElementById('ongoingLoadingIndicator');
    const tableBody = document.getElementById('ongoingServiceTableBody');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationContainer = document.getElementById('paginationContainer');

    loadingIndicator.classList.remove('hidden');
    tableBody.innerHTML = '';

    fetch(`historyAjax/fetch_ongoing_services.php?page=${page}&search=${encodeURIComponent(tableStates.ongoing.search)}&sort=${tableStates.ongoing.sort}&branch=${tableStates.ongoing.branch || 'all'}`)
        .then(response => response.json())
        .then(data => {
            // Update table body
            if (data.records.length > 0) {
                tableBody.innerHTML = data.records.map(row => {
                    const clientName = `${row.fname} ${row.mname ? row.mname + ' ' : ''}${row.lname}${row.suffix ? ' ' + row.suffix : ''}`;
                    const deceasedName = `${row.fname_deceased} ${row.mname_deceased ? row.mname_deceased + ' ' : ''}${row.lname_deceased}${row.suffix_deceased ? ' ' + row.suffix_deceased : ''}`;
                    const isDisabled = !row.customerID;
                    
                    return `
                        <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#${row.sales_id}</td>
                            <td class="px-4 py-3.5 text-sm text-sidebar-text">${clientName}</td>
                            <td class="px-4 py-3.5 text-sm text-sidebar-text">${deceasedName}</td>
                            <td class="px-4 py-3.5 text-sm text-sidebar-text">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                    ${row.service_name}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-sm text-sidebar-text">${row.date_of_burial}</td>
                            <td class="px-4 py-3.5 text-sm">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-500 border border-orange-200">
                                    <i class="fas fa-pause-circle mr-1"></i> ${row.status}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">${formatCurrency(row.balance || 0)}</td>
                            <td class="px-4 py-3.5 text-sm">
                                <div class="flex space-x-2">
                                    <button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" title="Edit Service" onclick="openEditServiceModal('${row.sales_id}')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    ${row.staff_assigned == 0 ? `
                                        <button class="p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 transition-all tooltip assign-staff-btn ${isDisabled ? 'opacity-40 cursor-not-allowed' : ''}" 
                                                title="${isDisabled ? 'Connect to customer account first' : 'Assign Staff'}"
                                                onclick="${isDisabled ? '' : `checkCustomerBeforeAssign('${row.sales_id}', true)`}"
                                                ${isDisabled ? 'disabled' : ''}>
                                            <i class="fas fa-users"></i>
                                        </button>
                                    ` : ''}
                                    <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip complete-btn ${isDisabled ? 'opacity-40 cursor-not-allowed' : ''}" 
                                            title="${isDisabled ? 'Connect to customer account first' : 'Complete Service'}"
                                            onclick="${isDisabled ? '' : `checkCustomerBeforeComplete('${row.sales_id}', true)`}"
                                            ${isDisabled ? 'disabled' : ''}>
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip payment-btn ${isDisabled ? 'opacity-40 cursor-not-allowed grayscale' : ''}" 
                                            title="${isDisabled ? 'Connect to customer account first' : 'Record Payment'}"
                                            onclick="${isDisabled ? '' : `openRecordPaymentModal('${row.sales_id}', '${clientName.replace(/'/g, "\\'")}', ${row.balance || 0})`}"
                                            ${isDisabled ? 'disabled' : ''}>
                                        <i class="fas fa-money-bill"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="p-6 text-sm text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-500">No ongoing services found</p>
                            </div>
                        </td>
                    </tr>
                `;
            }

            // Update pagination info
            const start = (data.pagination.currentPage - 1) * data.pagination.recordsPerPage + 1;
            const end = Math.min(start + data.pagination.recordsPerPage - 1, data.total);
            paginationInfo.textContent = `Showing ${start} - ${end} of ${data.total} services`;

            // Update pagination controls
            paginationContainer.innerHTML = '';
            if (data.pagination.totalPages > 1) {
                paginationContainer.innerHTML = `
                    <button onclick="loadOngoingServices(1)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.pagination.currentPage === 1 ? 'opacity-50 pointer-events-none' : ''}">
                        &laquo;
                    </button>
                    <button onclick="loadOngoingServices(${Math.max(1, data.pagination.currentPage - 1)})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.pagination.currentPage === 1 ? 'opacity-50 pointer-events-none' : ''}">
                        &lsaquo;
                    </button>
                    ${Array.from({ length: Math.min(3, data.pagination.totalPages) }, (_, i) => {
                        const pageNum = Math.max(1, Math.min(data.pagination.currentPage - 1, data.pagination.totalPages - 2)) + i;
                        return `<button onclick="loadOngoingServices(${pageNum})" class="px-3.5 py-1.5 rounded text-sm ${pageNum === data.pagination.currentPage ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover'}">${pageNum}</button>`;
                    }).join('')}
                    <button onclick="loadOngoingServices(${Math.min(data.pagination.totalPages, data.pagination.currentPage + 1)})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.pagination.currentPage === data.pagination.totalPages ? 'opacity-50 pointer-events-none' : ''}">
                        &rsaquo;
                    </button>
                    <button onclick="loadOngoingServices(${data.pagination.totalPages})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.pagination.currentPage === data.pagination.totalPages ? 'opacity-50 pointer-events-none' : ''}">
                        &raquo;
                    </button>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading ongoing services:', error);
            tableBody.innerHTML = `<tr><td colspan="8" class="p-6 text-sm text-center text-red-500">Error loading data</td></tr>`;
        })
        .finally(() => {
            loadingIndicator.classList.add('hidden');
        });
}

// Function to load Fully Paid Services via AJAX
function loadFullyPaidServices(page = 1) {
    tableStates.fullyPaid.page = page;
    const loadingIndicator = document.getElementById('loadingIndicatorFullyPaid');
    const tableBody = document.getElementById('fullyPaidTableBody');
    const paginationInfo = document.getElementById('paginationInfoFullyPaid');
    const paginationContainer = document.getElementById('paginationContainerFullyPaid');

    loadingIndicator.classList.remove('hidden');
    tableBody.innerHTML = '';

    fetch(`historyAjax/fetch_fully_paid_services.php?page=${page}&search=${encodeURIComponent(tableStates.fullyPaid.search)}&sort=${tableStates.fullyPaid.sort}&branch=${tableStates.ongoing.branch || 'all'}`)
        .then(response => response.json())
        .then(data => {
            // Update table body
            if (data.records.length > 0) {
                tableBody.innerHTML = data.records.map(row => {
                    const clientName = `${row.fname} ${row.mname ? row.mname + ' ' : ''}${row.lname}${row.suffix ? ' ' + row.suffix : ''}`;
                    const deceasedName = `${row.fname_deceased} ${row.mname_deceased ? row.mname_deceased + ' ' : ''}${row.lname_deceased}${row.suffix_deceased ? ' ' + row.suffix_deceased : ''}`;
                    return `
                        <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#${row.sales_id}</td>
                            <td class="px-4 py-3.5 text-sm text-sidebar-text">${clientName}</td>
                            <td class="px-4 py-3.5 text-sm text-sidebar-text">${deceasedName}</td>
                            <td class="px-4 py-3.5 text-sm text-sidebar-text">${row.service_name}</td>
                            <td class="px-4 py-3.5 text-sm text-sidebar-text">${row.date_of_burial}</td>
                            <td class="px-4 py-3.5 text-sm">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-500 border border-green-200">
                                    <i class="fas fa-check-circle mr-1"></i> ${row.status}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-sm">
                                <div class="flex space-x-2">
                                    <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details" onclick="viewServiceDetails('${row.sales_id}')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-sm text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-500">No fully paid past services found</p>
                            </div>
                        </td>
                    </tr>
                `;
            }

            // Update pagination info
            const start = (data.pagination.currentPage - 1) * data.pagination.recordsPerPage + 1;
            const end = Math.min(start + data.pagination.recordsPerPage - 1, data.total);
            paginationInfo.textContent = data.total > 0 ? `Showing ${start} - ${end} of ${data.total} services` : 'No services found';

            // Update pagination controls
            paginationContainer.innerHTML = '';
            if (data.pagination.totalPages > 1) {
                paginationContainer.innerHTML = `
                    <button onclick="loadFullyPaidServices(1)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.pagination.currentPage === 1 ? 'opacity-50 pointer-events-none' : ''}">
                        &laquo;
                    </button>
                    <button onclick="loadFullyPaidServices(${Math.max(1, data.pagination.currentPage - 1)})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.pagination.currentPage === 1 ? 'opacity-50 pointer-events-none' : ''}">
                        &lsaquo;
                    </button>
                    ${Array.from({ length: Math.min(3, data.pagination.totalPages) }, (_, i) => {
                        const pageNum = Math.max(1, Math.min(data.pagination.currentPage - 1, data.pagination.totalPages - 2)) + i;
                        return `<button onclick="loadFullyPaidServices(${pageNum})" class="px-3.5 py-1.5 rounded text-sm ${pageNum === data.pagination.currentPage ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover'}">${pageNum}</button>`;
                    }).join('')}
                    <button onclick="loadFullyPaidServices(${Math.min(data.pagination.totalPages, data.pagination.currentPage + 1)})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.pagination.currentPage === data.pagination.totalPages ? 'opacity-50 pointer-events-none' : ''}">
                        &rsaquo;
                    </button>
                    <button onclick="loadFullyPaidServices(${data.pagination.totalPages})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.pagination.currentPage === data.pagination.totalPages ? 'opacity-50 pointer-events-none' : ''}">
                        &raquo;
                    </button>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading fully paid services:', error);
            tableBody.innerHTML = `<tr><td colspan="7" class="p-6 text-sm text-center text-red-500">Error loading data</td></tr>`;
        })
        .finally(() => {
            loadingIndicator.classList.add('hidden');
        });
}

// Function to load Outstanding Balance Services via AJAX
function loadOutstandingServices(page = 1) {
    tableStates.outstanding.page = page;
    const loadingIndicator = document.getElementById('outstandingLoadingIndicator');
    const tableBody = document.getElementById('outstandingTableBody');
    const paginationInfo = document.getElementById('paginationInfoPastWithBal');
    const paginationContainer = document.getElementById('paginationContainerOutstanding');

    loadingIndicator.classList.remove('hidden');
    tableBody.innerHTML = '';

    fetch(`historyAjax/fetch_outstanding_services.php?page=${page}&search=${encodeURIComponent(tableStates.outstanding.search)}&sort=${tableStates.outstanding.sort}&branch=${tableStates.ongoing.branch || 'all'}`)
        .then(response => response.json())
        .then(data => {
            // Update table body
            if (data.records.length > 0) {
                tableBody.innerHTML = data.records.map(row => {
                    const clientName = `${row.fname} ${row.mname ? row.mname + ' ' : ''}${row.lname}${row.suffix ? ' ' + row.suffix : ''}`;
                    const deceasedName = `${row.fname_deceased} ${row.mname_deceased ? row.mname_deceased + ' ' : ''}${row.lname_deceased}${row.suffix_deceased ? ' ' + row.suffix_deceased : ''}`;
                    return `
                        <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#${row.sales_id}</td>
                            <td class="px-4 py-3.5 text-sm text-sidebar-text">${clientName}</td>
                            <td class="px-4 py-3.5 text-sm text-sidebar-text">${deceasedName}</td>
                            <td class="px-4 py-3.5 text-sm text-sidebar-text">${row.service_name}</td>
                            <td class="px-4 py-3.5 text-sm text-sidebar-text">${row.date_of_burial}</td>
                            <td class="px-4 py-3.5 text-sm">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-500 border border-red-200">
                                    <i class="fas fa-exclamation-circle mr-1"></i> ${row.payment_status}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">${formatCurrency(row.balance || 0)}</td>
                            <td class="px-4 py-3.5 text-sm">
                                <div class="flex space-x-2">
                                    <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details" onclick="viewServiceDetails('${row.sales_id}')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip" title="Record Payment" onclick="openRecordPaymentModal('${row.sales_id}', '${clientName.replace(/'/g, "\\'")}', ${row.balance})">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </button>
                                    <button class="p-2 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-200 transition-all tooltip" 
                                            title="Send Payment Reminder" 
                                            onclick="sendPaymentReminder(${row.sales_id})">
                                      <i class="fas fa-comment-dots"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-sm text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-500">No services with outstanding balance found</p>
                            </div>
                        </td>
                    </tr>
                `;
            }

            // Update pagination info
            const start = (data.pagination.currentPage - 1) * data.pagination.recordsPerPage + 1;
            const end = Math.min(start + data.pagination.recordsPerPage - 1, data.total);
            paginationInfo.textContent = data.total > 0 ? `Showing ${start} - ${end} of ${data.total} services` : 'No services found';

            // Update pagination controls
            paginationContainer.innerHTML = '';
            if (data.pagination.totalPages > 1) {
                paginationContainer.innerHTML = `
                    <button onclick="loadOutstandingServices(1)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.pagination.currentPage === 1 ? 'opacity-50 pointer-events-none' : ''}">
                        &laquo;
                    </button>
                    <button onclick="loadOutstandingServices(${Math.max(1, data.pagination.currentPage - 1)})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.pagination.currentPage === 1 ? 'opacity-50 pointer-events-none' : ''}">
                        &lsaquo;
                    </button>
                    ${Array.from({ length: Math.min(3, data.pagination.totalPages) }, (_, i) => {
                        const pageNum = Math.max(1, Math.min(data.pagination.currentPage - 1, data.pagination.totalPages - 2)) + i;
                        return `<button onclick="loadOutstandingServices(${pageNum})" class="px-3.5 py-1.5 rounded text-sm ${pageNum === data.pagination.currentPage ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover'}">${pageNum}</button>`;
                    }).join('')}
                    <button onclick="loadOutstandingServices(${Math.min(data.pagination.totalPages, data.pagination.currentPage + 1)})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.pagination.currentPage === data.pagination.totalPages ? 'opacity-50 pointer-events-none' : ''}">
                        &rsaquo;
                    </button>
                    <button onclick="loadOutstandingServices(${data.pagination.totalPages})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.pagination.currentPage === data.pagination.totalPages ? 'opacity-50 pointer-events-none' : ''}">
                        &raquo;
                    </button>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading outstanding services:', error);
            tableBody.innerHTML = `<tr><td colspan="8" class="p-6 text-sm text-center text-red-500">Error loading data</td></tr>`;
        })
        .finally(() => {
            loadingIndicator.classList.add('hidden');
        });
}

// Debounced search functions
const debouncedOngoingSearch = debounce(() => {
    tableStates.ongoing.search = document.getElementById('searchOngoing').value;
    loadOngoingServices(1);
}, 300);

const debouncedFullyPaidSearch = debounce(() => {
    tableStates.fullyPaid.search = document.getElementById('searchFullyPaid').value || document.getElementById('searchFullyPaidMobile').value;
    loadFullyPaidServices(1);
}, 300);

const debouncedOutstandingSearch = debounce(() => {
    tableStates.outstanding.search = document.getElementById('searchOutstanding').value;
    loadOutstandingServices(1);
}, 300);

// Filter and sort handlers
function setupFilterHandlers() {
    // Ongoing Services Filter
    const filterDropdownOngoing = document.getElementById('filterDropdown');
    const filterToggleOngoing = document.getElementById('filterToggle');
    const filterIndicatorOngoing = document.getElementById('filterIndicator');

    if (filterDropdownOngoing && filterToggleOngoing) {
        filterToggleOngoing.addEventListener('click', () => {
            filterDropdownOngoing.classList.toggle('hidden');
        });

        filterDropdownOngoing.querySelectorAll('.filter-option').forEach(option => {
            option.addEventListener('click', () => {
                tableStates.ongoing.sort = option.parentElement.dataset.sort;
                filterIndicatorOngoing.classList.remove('hidden');
                filterDropdownOngoing.classList.add('hidden');
                loadOngoingServices(1);
            });
        });
    }

    // Fully Paid Services Filter
    const filterDropdownFullyPaid = document.getElementById('filterDropdownFullyPaid');
    const filterToggleFullyPaid = document.getElementById('filterToggleFullyPaid');
    const filterToggleFullyPaidMobile = document.getElementById('filterToggleFullyPaidMobile');
    const filterIndicatorFullyPaid = document.getElementById('filterIndicatorFullyPaid');

    if (filterDropdownFullyPaid && filterToggleFullyPaid) {
        filterToggleFullyPaid.addEventListener('click', () => {
            filterDropdownFullyPaid.classList.toggle('hidden');
        });

        filterToggleFullyPaidMobile.addEventListener('click', () => {
            document.getElementById('filterDropdownFullyPaidMobile').classList.toggle('hidden');
        });

        filterDropdownFullyPaid.querySelectorAll('.filter-option').forEach(option => {
            option.addEventListener('click', () => {
                tableStates.fullyPaid.sort = option.parentElement.dataset.sort;
                filterIndicatorFullyPaid.classList.remove('hidden');
                filterDropdownFullyPaid.classList.add('hidden');
                document.getElementById('filterDropdownFullyPaidMobile').classList.add('hidden');
                loadFullyPaidServices(1);
            });
        });

        document.getElementById('filterDropdownFullyPaidMobile').querySelectorAll('.filter-option').forEach(option => {
            option.addEventListener('click', () => {
                tableStates.fullyPaid.sort = option.parentElement.dataset.sort;
                filterIndicatorFullyPaid.classList.remove('hidden');
                filterDropdownFullyPaid.classList.add('hidden');
                document.getElementById('filterDropdownFullyPaidMobile').classList.add('hidden');
                loadFullyPaidServices(1);
            });
        });
    }

    // Outstanding Services Filter
    const filterDropdownOutstanding = document.getElementById('filterDropdownOutstanding');
    const filterToggleOutstanding = document.getElementById('filterToggleOutstanding');

    if (filterDropdownOutstanding && filterToggleOutstanding) {
        filterToggleOutstanding.addEventListener('click', () => {
            filterDropdownOutstanding.classList.toggle('hidden');
        });

        filterDropdownOutstanding.querySelectorAll('.filter-option').forEach(option => {
            option.addEventListener('click', () => {
                tableStates.outstanding.sort = option.parentElement.dataset.sort;
                document.getElementById('filterIndicatorOutstanding').classList.remove('hidden');
                filterDropdownOutstanding.classList.add('hidden');
                loadOutstandingServices(1);
            });
        });
    }
}

// Initialize search and filter listeners
document.addEventListener('DOMContentLoaded', () => {
    const searchOngoing = document.getElementById('searchOngoing');
    const searchFullyPaid = document.getElementById('searchFullyPaid');
    const searchFullyPaidMobile = document.getElementById('searchFullyPaidMobile');
    const searchOutstanding = document.getElementById('searchOutstanding');

    if (searchOngoing) {
        searchOngoing.addEventListener('input', debouncedOngoingSearch);
    }
    if (searchFullyPaid) {
        searchFullyPaid.addEventListener('input', debouncedFullyPaidSearch);
    }
    if (searchFullyPaidMobile) {
        searchFullyPaidMobile.addEventListener('input', debouncedFullyPaidSearch);
    }
    if (searchOutstanding) {
        searchOutstanding.addEventListener('input', debouncedOutstandingSearch);
    }

    setupFilterHandlers();

    // Initial loads
    loadOngoingServices(tableStates.ongoing.page);
    loadFullyPaidServices(tableStates.fullyPaid.page);
    loadOutstandingServices(tableStates.outstanding.page);
});




// Debounce function for search inputs
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// AJAX functions for Custom Sales tables
function loadCustomOngoingServices(page = 1, search = '', sort = '') {
  const tableContainer = document.getElementById('customOngoingTableContainer');
  const tableBody = document.getElementById('customOngoingTableBody');
  const loadingIndicator = document.getElementById('customOngoingLoadingIndicator');
  const paginationInfo = document.getElementById('paginationInfoCustomOngoing');
  const paginationContainer = document.getElementById('paginationContainerCustomOngoing');
  
  loadingIndicator.classList.remove('hidden');
  
  fetch(`historyAjax/get_custom_ongoing_services.php?page=${page}&search=${encodeURIComponent(tableStates.customOngoing.search)}&sort=${tableStates.customOngoing.sort}&branch=${tableStates.customOngoing.branch || 'all'}`)
    .then(response => response.json())
    .then(data => {
      tableBody.innerHTML = '';
      if (data.services.length > 0) {
        data.services.forEach(row => {
          const clientName = `${row.fname || ''} ${row.mname || ''} ${row.lname || ''} ${row.suffix || ''}`.trim();
          const deceasedName = `${row.fname_deceased || ''} ${row.mname_deceased || ''} ${row.lname_deceased || ''} ${row.suffix_deceased || ''}`.trim();
          const serviceType = row.with_cremate === 'yes' ? 'Cremation Service' : 'Burial Service';
          const hasStaffAssigned = row.staff_assigned > 0;
          
          tableBody.innerHTML += `
            <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
              <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#${row.customsales_id}</td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text">${clientName}</td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text">${deceasedName}</td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                  ${serviceType}
                </span>
              </td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text">${row.date_of_burial || 'N/A'}</td>
              <td class="px-4 py-3.5 text-sm">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-500 border border-orange-200">
                  <i class="fas fa-pause-circle mr-1"></i> ${row.status}
                </span>
              </td>
              <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">${formatCurrency(row.balance || 0)}</td>
              <td class="px-4 py-3.5 text-sm">
                <div class="flex space-x-2">
                  <button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" title="Edit Service" onclick="openEditCustomServiceModal('${row.customsales_id}')">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 transition-all tooltip assign-staff-btn" 
                          title="${hasStaffAssigned ? 'Staff already assigned' : 'Assign Staff'}" 
                          onclick="checkCustomerBeforeAssignCustom('${row.customsales_id}', ${row.customer_id ? 'true' : 'false'})"
                          ${(row.customer_id && !hasStaffAssigned) ? '' : 'disabled'}>
                    <i class="fas fa-users"></i>
                  </button>
                  <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip complete-btn" 
                          title="${hasStaffAssigned ? 'Complete Service' : 'Assign staff first'}" 
                          onclick="checkCustomerBeforeCompleteCustom('${row.customsales_id}', ${row.customer_id ? 'true' : 'false'})"
                          ${(row.customer_id && hasStaffAssigned) ? '' : 'disabled'}>
                    <i class="fas fa-check"></i>
                  </button>
                </div>
              </td>
            </tr>
          `;
        });
      } else {
        tableBody.innerHTML = `
          <tr>
            <td colspan="8" class="p-6 text-sm text-center">
              <div class="flex flex-col items-center">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500">No ongoing custom services found</p>
              </div>
            </td>
          </tr>
        `;
      }
      
      paginationInfo.innerHTML = `Showing ${data.start} - ${data.end} of ${data.total} services`;
      paginationContainer.innerHTML = generatePagination(data.total_pages, page, 'loadCustomOngoingServices');
    })
    .catch(error => {
      console.error('Error:', error);
      tableBody.innerHTML = `
        <tr>
          <td colspan="8" class="p-6 text-sm text-center text-red-500">Error loading services</td>
        </tr>
      `;
    })
    .finally(() => {
      loadingIndicator.classList.add('hidden');
    });
}

function loadCustomFullyPaidServices(page = 1, search = '', sort = '') {
  const tableContainer = document.getElementById('customFullyPaidTableContainer');
  const tableBody = document.getElementById('customFullyPaidTableBody');
  const loadingIndicator = document.getElementById('customFullyPaidLoadingIndicator');
  const paginationInfo = document.getElementById('paginationInfoCustomFullyPaid');
  const paginationContainer = document.getElementById('paginationContainerCustomFullyPaid');
  
  loadingIndicator.classList.remove('hidden');
  
  fetch(`historyAjax/get_custom_fully_paid_services.php?page=${page}&search=${encodeURIComponent(tableStates.customFullyPaid.search)}&sort=${tableStates.customFullyPaid.sort}&branch=${tableStates.customFullyPaid.branch || 'all'}`)
    .then(response => response.json())
    .then(data => {
      tableBody.innerHTML = '';
      if (data.services.length > 0) {
        data.services.forEach(row => {
          const clientName = `${row.fname || ''} ${row.mname || ''} ${row.lname || ''} ${row.suffix || ''}`.trim();
          const deceasedName = `${row.fname_deceased || ''} ${row.mname_deceased || ''} ${row.lname_deceased || ''} ${row.suffix_deceased || ''}`.trim();
          const serviceType = row.with_cremate === 'yes' ? 'Cremation Service' : 'Burial Service';
          tableBody.innerHTML += `
            <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
              <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#${row.customsales_id}</td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text">${clientName}</td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text">${deceasedName}</td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text">${serviceType}</td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text">${row.date_of_burial || 'N/A'}</td>
              <td class="px-4 py-3.5 text-sm">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-500 border border-green-200">
                  <i class="fas fa-check-circle mr-1"></i> ${row.status}
                </span>
              </td>
              <td class="px-4 py-3.5 text-sm">
                <div class="flex space-x-2">
                  <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details" onclick="viewCustomServiceDetails('${row.customsales_id}')">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </td>
            </tr>
          `;
        });
      } else {
        tableBody.innerHTML = `
          <tr>
            <td colspan="7" class="px-4 py-6 text-sm text-center">
              <div class="flex flex-col items-center">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500">No fully paid past custom services found</p>
              </div>
            </td>
          </tr>
        `;
      }
      
      paginationInfo.innerHTML = `Showing ${data.start} - ${data.end} of ${data.total} services`;
      paginationContainer.innerHTML = generatePagination(data.total_pages, page, 'loadCustomFullyPaidServices');
    })
    .catch(error => {
      console.error('Error:', error);
      tableBody.innerHTML = `
        <tr>
          <td colspan="7" class="p-6 text-sm text-center text-red-500">Error loading services</td>
        </tr>
      `;
    })
    .finally(() => {
      loadingIndicator.classList.add('hidden');
    });
}

function loadCustomOutstandingServices(page = 1, search = '', sort = '') {
  const tableContainer = document.getElementById('customOutstandingTableContainer');
  const tableBody = document.getElementById('customOutstandingTableBody');
  const loadingIndicator = document.getElementById('customOutstandingLoadingIndicator');
  const paginationInfo = document.getElementById('paginationInfoCustomOutstanding');
  const paginationContainer = document.getElementById('paginationContainerCustomOutstanding');
  
  loadingIndicator.classList.remove('hidden');
  
  fetch(`historyAjax/get_custom_outstanding_services.php?page=${page}&search=${encodeURIComponent(tableStates.customOutstanding.search)}&sort=${tableStates.customOutstanding.sort}&branch=${tableStates.customOutstanding.branch || 'all'}`)
    .then(response => response.json())
    .then(data => {
      tableBody.innerHTML = '';
      if (data.services.length > 0) {
        data.services.forEach(row => {
          const clientName = `${row.fname || ''} ${row.mname || ''} ${row.lname || ''} ${row.suffix || ''}`.trim();
          const deceasedName = `${row.fname_deceased || ''} ${row.mname_deceased || ''} ${row.lname_deceased || ''} ${row.suffix_deceased || ''}`.trim();
          const serviceType = row.with_cremate === 'yes' ? 'Cremation Service' : 'Burial Service';
          tableBody.innerHTML += `
            <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
              <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#${row.customsales_id}</td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text">${clientName}</td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text">${deceasedName}</td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text">${serviceType}</td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text">${row.date_of_burial || 'N/A'}</td>
              <td class="px-4 py-3.5 text-sm">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-500 border border-green-200">
                  <i class="fas fa-check-circle mr-1"></i> ${row.status}
                </span>
              </td>
              <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">${formatCurrency(row.balance || 0)}</td>
              <td class="px-4 py-3.5 text-sm">
                <div class="flex space-x-2">
                  <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details" onclick="viewCustomServiceDetails('${row.customsales_id}')">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip" title="Record Payment" onclick="openCustomRecordPaymentModal('${row.customsales_id}', '${clientName.replace(/'/g, "\\'")}', ${row.balance})">
                    <i class="fas fa-money-bill-wave"></i>
                  </button>
                  <button class="p-2 bg-indigo-100 text-indigo-600 rounded-lg hover:bg-indigo-200 transition-all tooltip" 
                                            title="Send Payment Reminder" 
                                            onclick="sendCustomPaymentReminder(${row.customsales_id})">
                                        <i class="fas fa-comment-dots"></i>
                                    </button>
                </div>
              </td>
            </tr>
          `;
        });
      } else {
        tableBody.innerHTML = `
          <tr>
            <td colspan="8" class="px-4 py-6 text-sm text-center">
              <div class="flex flex-col items-center">
                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500">No past custom services with outstanding balance found</p>
              </div>
            </td>
          </tr>
        `;
      }
      
      paginationInfo.innerHTML = `Showing ${data.start} - ${data.end} of ${data.total} services`;
      paginationContainer.innerHTML = generatePagination(data.total_pages, page, 'loadCustomOutstandingServices');
    })
    .catch(error => {
      console.error('Error:', error);
      tableBody.innerHTML = `
        <tr>
          <td colspan="8" class="p-6 text-sm text-center text-red-500">Error loading services</td>
        </tr>
      `;
    })
    .finally(() => {
      loadingIndicator.classList.add('hidden');
    });
}

// Generate pagination buttons
function generatePagination(totalPages, currentPage, loadFunction) {
  let html = '';
  if (totalPages > 1) {
    html += `
      <button onclick="${loadFunction}(${1})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${currentPage === 1 ? 'opacity-50 pointer-events-none' : ''}">
        &laquo;
      </button>
      <button onclick="${loadFunction}(${Math.max(1, currentPage - 1)})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${currentPage === 1 ? 'opacity-50 pointer-events-none' : ''}">
        &lsaquo;
      </button>
    `;
    const startPage = Math.max(1, Math.min(currentPage - 1, totalPages - 2));
    const endPage = Math.min(totalPages, startPage + 2);
    for (let i = startPage; i <= endPage; i++) {
      html += `
        <button onclick="${loadFunction}(${i})" class="px-3.5 py-1.5 rounded text-sm ${i === currentPage ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover'}">
          ${i}
        </button>
      `;
    }
    html += `
      <button onclick="${loadFunction}(${Math.min(totalPages, currentPage + 1)})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${currentPage === totalPages ? 'opacity-50 pointer-events-none' : ''}">
        &rsaquo;
      </button>
      <button onclick="${loadFunction}(${totalPages})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${currentPage === totalPages ? 'opacity-50 pointer-events-none' : ''}">
        &raquo;
      </button>
    `;
  }
  return html;
}

// Sorting functions for Custom Sales tables
function sortCustomOngoingTable(column) {
  // Implement sorting logic if needed
}

function sortCustomFullyPaidTable(column) {
  // Implement sorting logic if needed
}

function sortCustomOutstandingTable(column) {
  // Implement sorting logic if needed
}

// Debounced filter functions
const debouncedFilterCustomOngoing = debounce(() => {
  const search = document.getElementById('searchCustomOngoing')?.value || document.getElementById('searchCustomOngoingMobile')?.value || '';
  loadCustomOngoingServices(1, search);
}, 300);

const debouncedFilterCustomFullyPaid = debounce(() => {
  const search = document.getElementById('searchCustomFullyPaid')?.value || document.getElementById('searchCustomFullyPaidMobile')?.value || '';
  loadCustomFullyPaidServices(1, search);
}, 300);

const debouncedFilterCustomOutstanding = debounce(() => {
  const search = document.getElementById('searchCustomOutstanding')?.value || document.getElementById('searchCustomOutstandingMobile')?.value || '';
  loadCustomOutstandingServices(1, search);
}, 300);

// Filter toggle handlers
function setupFilterToggle(buttonId, dropdownId, indicatorId, tableType) {
  const button = document.getElementById(buttonId);
  const dropdown = document.getElementById(dropdownId);
  const indicator = document.getElementById(indicatorId);
  
  button.addEventListener('click', () => {
    dropdown.classList.toggle('hidden');
  });
  
  dropdown.querySelectorAll('.filter-option').forEach(option => {
    option.addEventListener('click', () => {
      const sort = option.parentElement.getAttribute('data-sort');
      indicator.classList.remove('hidden');
      dropdown.classList.add('hidden');
      if (tableType === 'customOngoing') {
        loadCustomOngoingServices(1, '', sort);
      } else if (tableType === 'customFullyPaid') {
        loadCustomFullyPaidServices(1, '', sort);
      } else if (tableType === 'customOutstanding') {
        loadCustomOutstandingServices(1, '', sort);
      }
    });
  });
  
  document.addEventListener('click', (e) => {
    if (!button.contains(e.target) && !dropdown.contains(e.target)) {
      dropdown.classList.add('hidden');
    }
  });
}

// Initialize filter toggles
document.addEventListener('DOMContentLoaded', () => {
  setupFilterToggle('filterToggleCustomOngoing', 'filterDropdownCustomOngoing', 'filterIndicatorCustomOngoing', 'customOngoing');
  setupFilterToggle('filterToggleCustomOngoingMobile', 'filterDropdownCustomOngoingMobile', 'filterIndicatorCustomOngoingMobile', 'customOngoing');
  setupFilterToggle('filterToggleCustomFullyPaid', 'filterDropdownCustomFullyPaid', 'filterIndicatorCustomFullyPaid', 'customFullyPaid');
  setupFilterToggle('filterToggleCustomFullyPaidMobile', 'filterDropdownCustomFullyPaidMobile', 'filterIndicatorCustomFullyPaidMobile', 'customFullyPaid');
  setupFilterToggle('filterToggleCustomOutstanding', 'filterDropdownCustomOutstanding', 'filterIndicatorCustomOutstanding', 'customOutstanding');
  setupFilterToggle('filterToggleCustomOutstandingMobile', 'filterDropdownCustomOutstandingMobile', 'filterIndicatorCustomOutstandingMobile', 'customOutstanding');
  
  // Load initial data
  loadCustomOngoingServices();
  loadCustomFullyPaidServices();
  loadCustomOutstandingServices();
});

// Customer check functions for custom services
function checkCustomerBeforeAssignCustom(serviceId, hasCustomer) {
  if (!hasCustomer) {
    Swal.fire({
      icon: 'warning',
      title: 'No Customer Assigned',
      text: 'Please assign a customer to this service before assigning staff.',
      confirmButtonText: 'OK'
    });
    return;
  }
  openAssignCustomStaffModal(serviceId);
}

function checkCustomerBeforeCompleteCustom(serviceId, hasCustomer) {
  if (!hasCustomer) {
    Swal.fire({
      icon: 'warning',
      title: 'No Customer Assigned',
      text: 'Please assign a customer to this service before marking it as complete.',
      confirmButtonText: 'OK'
    });
    return;
  }
  openCompleteCustomModal(serviceId);
}
</script>

<script>
// Function to toggle address change section
function toggleAddressChange() {
  const addressChangeSection = document.getElementById('addressChangeSection');
  if (addressChangeSection) {
    addressChangeSection.classList.toggle('hidden');
  }
}

// Function to cancel address change
function cancelAddressChange() {
  const addressChangeSection = document.getElementById('addressChangeSection');
  if (addressChangeSection) {
    addressChangeSection.classList.add('hidden');
    // Reset the form fields
    document.getElementById('regionSelect').value = '';
    document.getElementById('provinceSelect').value = '';
    document.getElementById('citySelect').value = '';
    document.getElementById('barangaySelect').value = '';
    document.getElementById('streetInput').value = '';
    document.getElementById('zipCodeInput').value = '';
  }
}

// Add this function to validate search input
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

// Apply validation to all search inputs in the history page
document.addEventListener('DOMContentLoaded', function() {
    // Standard Sales Tab search inputs
    validateSearchInput(document.getElementById('searchOngoing'));
    validateSearchInput(document.getElementById('searchFullyPaid'));
    validateSearchInput(document.getElementById('searchFullyPaidMobile'));
    validateSearchInput(document.getElementById('searchOutstanding'));
    
    // Custom Sales Tab search inputs
    validateSearchInput(document.getElementById('searchCustomOngoing'));
    validateSearchInput(document.getElementById('searchCustomOngoingMobile'));
    validateSearchInput(document.getElementById('searchCustomFullyPaid'));
    validateSearchInput(document.getElementById('searchCustomFullyPaidMobile'));
    validateSearchInput(document.getElementById('searchCustomOutstanding'));
    validateSearchInput(document.getElementById('searchCustomOutstandingMobile'));
});
</script>

<script>
// Function to load regions
function loadRegions() {
    const regionSelect = document.getElementById('regionSelect');
    if (!regionSelect) {
        console.error('Region select element not found');
        return;
    }
    
    // Clear existing options
    regionSelect.innerHTML = '<option value="">Select Region</option>';
    
    fetch('../employee/historyAPI/addressDB.php?action=getRegions')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            console.log('Regions data:', data);
            data.forEach(region => {
                const option = document.createElement('option');
                option.value = region.region_id;
                option.textContent = region.region_name;
                regionSelect.appendChild(option);
            });
            console.log('Regions loaded:', regionSelect.options.length);
        })
        .catch(error => console.error('Error loading regions:', error));
}

// Function to load provinces based on selected region
function loadProvinces(regionId) {
    console.log('Loading provinces for region:', regionId);
    const provinceSelect = document.getElementById('provinceSelect');
    if (!provinceSelect) {
        console.error('Province select element not found');
        return;
    }
    
    // Clear existing options
    provinceSelect.innerHTML = '<option value="">Select Province</option>';
    
    if (!regionId) return;
    
    fetch(`../employee/historyAPI/addressDB.php?action=getProvinces&region_id=${regionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            console.log('Provinces data:', data);
            data.forEach(province => {
                const option = document.createElement('option');
                option.value = province.province_id;
                option.textContent = province.province_name;
                provinceSelect.appendChild(option);
            });
            console.log('Provinces loaded:', provinceSelect.options.length);
            
            // Enable the province select after loading options
            provinceSelect.disabled = false;
        })
        .catch(error => console.error('Error loading provinces:', error));
}

// Function to load municipalities based on selected province
function loadCities(provinceId) {
    console.log('Loading cities for province:', provinceId);
    const citySelect = document.getElementById('citySelect');
    if (!citySelect) {
        console.error('City select element not found');
        return;
    }
    
    // Clear existing options
    citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
    
    if (!provinceId) return;
    
    fetch(`../employee/historyAPI/addressDB.php?action=getMunicipalities&province_id=${provinceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            console.log('Cities data:', data);
            data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.municipality_id;
                option.textContent = city.municipality_name;
                citySelect.appendChild(option);
            });
            console.log('Cities loaded:', citySelect.options.length);
            
            // Enable the city select after loading options
            citySelect.disabled = false;
        })
        .catch(error => console.error('Error loading cities:', error));
}

// Function to load barangays based on selected municipality
function loadBarangays(municipalityId) {
    console.log('Loading barangays for municipality:', municipalityId);
    const barangaySelect = document.getElementById('barangaySelect');
    if (!barangaySelect) {
        console.error('Barangay select element not found');
        return;
    }
    
    // Clear existing options
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    
    if (!municipalityId) return;
    
    fetch(`../employee/historyAPI/addressDB.php?action=getBarangays&municipality_id=${municipalityId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            console.log('Barangays data:', data);
            data.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.barangay_id;
                option.textContent = barangay.barangay_name;
                barangaySelect.appendChild(option);
            });
            console.log('Barangays loaded:', barangaySelect.options.length);
            
            // Enable the barangay select after loading options
            barangaySelect.disabled = false;
        })
        .catch(error => console.error('Error loading barangays:', error));
}

// Function to update the current address display
function updateCurrentAddress() {
    const region = document.getElementById('regionSelect');
    const province = document.getElementById('provinceSelect');
    const city = document.getElementById('citySelect');
    const barangay = document.getElementById('barangaySelect');
    const street = document.getElementById('streetInput');
    const zipcode = document.getElementById('zipCodeInput');
    
    if (!region || !province || !city || !barangay || !street || !zipcode) {
        console.error('One or more address elements not found');
        return;
    }
    
    let address = '';
    
    // Add street if available
    if (street.value.trim()) {
        address += street.value.trim();
    }
    
    // Add barangay if selected
    if (barangay.value) {
        if (address) address += ', ';
        address += barangay.options[barangay.selectedIndex].text;
    }
    
    // Add city if selected
    if (city.value) {
        if (address) address += ', ';
        address += city.options[city.selectedIndex].text;
    }
    
    // Add province if selected
    if (province.value) {
        if (address) address += ', ';
        address += province.options[province.selectedIndex].text;
    }
    
    // Add region if selected
    if (region.value) {
        if (address) address += ', ';
        address += region.options[region.selectedIndex].text;
    }
    
    // Add zipcode if available
    if (zipcode.value.trim()) {
        if (address) address += ' ';
        address += zipcode.value.trim();
    }
    
    const currentAddressDisplay = document.getElementById('currentAddressDisplay');
    if (currentAddressDisplay) {
        currentAddressDisplay.value = address;
        console.log('Updated address:', address);
    } else {
        console.error('Current address display element not found');
    }
}

// Add event listeners when the document is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing address dropdowns');
    
    // Get all select elements and input fields
    const regionSelect = document.getElementById('regionSelect');
    const provinceSelect = document.getElementById('provinceSelect');
    const citySelect = document.getElementById('citySelect');
    const barangaySelect = document.getElementById('barangaySelect');
    const streetInput = document.getElementById('streetInput');
    const zipcodeInput = document.getElementById('zipCodeInput');
    
    // Verify all elements exist
    if (!regionSelect || !provinceSelect || !citySelect || !barangaySelect || !streetInput || !zipcodeInput) {
        console.error('One or more address elements not found');
        return;
    }
    
    // Initially disable dependent dropdowns
    provinceSelect.disabled = true;
    citySelect.disabled = true;
    barangaySelect.disabled = true;
    
    // Load initial regions
    loadRegions();
    
    // Add change event listeners for cascading dropdowns
    regionSelect.addEventListener('change', function() {
        console.log('Region changed:', this.value);
        if (this.value) {
            loadProvinces(this.value);
            provinceSelect.disabled = false;
        } else {
            provinceSelect.innerHTML = '<option value="">Select Province</option>';
            provinceSelect.disabled = true;
        }
        citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
        citySelect.disabled = true;
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        barangaySelect.disabled = true;
        updateCurrentAddress();
    });
    
    provinceSelect.addEventListener('change', function() {
        console.log('Province changed:', this.value);
        if (this.value) {
            loadCities(this.value);
            citySelect.disabled = false;
        } else {
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            citySelect.disabled = true;
        }
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        barangaySelect.disabled = true;
        updateCurrentAddress();
    });
    
    citySelect.addEventListener('change', function() {
        console.log('City changed:', this.value);
        if (this.value) {
            loadBarangays(this.value);
            barangaySelect.disabled = false;
        } else {
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;
        }
        updateCurrentAddress();
    });
    
    barangaySelect.addEventListener('change', function() {
        console.log('Barangay changed:', this.value);
        updateCurrentAddress();
    });
    
    // Add input event listeners for street and zipcode
    streetInput.addEventListener('input', function() {
        console.log('Street changed:', this.value);
        updateCurrentAddress();
    });
    
    zipcodeInput.addEventListener('input', function() {
        console.log('Zipcode changed:', this.value);
        updateCurrentAddress();
    });
});

// Function to handle customer search in custom modal
function setupCustomCustomerSearch() {
  console.log("setupCustomCustomerSearch called");
  const customerSearch = document.getElementById('customCustomerSearch');
  const customerResults = document.getElementById('customCustomerResults');
  
  if (!customerSearch || !customerResults) return;
  
  customerSearch.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    
    if (searchTerm.length < 2) {
      customerResults.classList.add('hidden');
      return;
    }

    const filteredCustomers = customers.filter(customer => 
      customer.full_name.toLowerCase().includes(searchTerm)
    ).slice(0, 10); // Limit to 10 results

    if (filteredCustomers.length > 0) {
      customerResults.innerHTML = filteredCustomers.map(customer => `
        <div class="cursor-default select-none relative py-2 pl-3 pr-9 hover:bg-gray-100" 
             data-id="${customer.id}" 
             onclick="selectCustomCustomer(this, '${customer.id}', '${customer.full_name.replace(/'/g, "\\'")}')">
          ${customer.full_name}
        </div>
      `).join('');
      customerResults.classList.remove('hidden');
      console.log("success");
    } else {
      customerResults.innerHTML = '<div class="py-2 pl-3 pr-9 text-gray-500">No customers found</div>';
      customerResults.classList.remove('hidden');
      console.log("not found");
    }
  });
  
  // Close dropdown when clicking outside
  document.addEventListener('click', function(e) {
    if (!e.target.closest('#customCustomerSearch') && !e.target.closest('#customCustomerResults')) {
      customerResults.classList.add('hidden');
    }
  });
}

// Function to select a customer in custom modal
function selectCustomCustomer(element, id, fullName) {
  document.getElementById('customCustomerSearch').value = fullName;
  document.getElementById('selectedCustomCustomerId').value = id;
  document.getElementById('customCustomerResults').classList.add('hidden');
  
  // Fetch customer details and populate the form
  fetchCustomerDetails(id);
}

// Function to fetch customer details and populate the form
function fetchCustomerDetails(customerId) {
  fetch(`history/get_customer_details.php?id=${customerId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Removed setting full name field
        document.getElementById('editCustomEmail').value = data.email || '';
        document.getElementById('editCustomPhone').value = data.phone || '';
      } else {
        console.error('Failed to fetch customer details:', data.message);
      }
    })
    .catch(error => {
      console.error('Error fetching customer details:', error);
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  setupCustomCustomerSearch();
  console.log("new function called");
});

// Function to open the Edit Custom Service Modal
function openEditCustomServiceModal(serviceId) {
    loadRegions();
    fetch(`../employee/historyAPI/get_custom_service_details.php?customsales_id=${serviceId}`)
        .then(response => response.json())
        .then(data => {
          console.log("Fetched data:", data);
            if (data.success) {
                document.getElementById('customSalesId').value = data.customsales_id;
                document.getElementById('selectedCustomCustomerId').value = data.customerID;
                console.log("customer finds: ",customers);

                // Set customer information if exists
                if (data.customerID) {
                    const customer = customers.find(c => c.id == data.customerID);
                    console.log("Matched customer:", customer); 
                    

                    if (customer) {
                        document.getElementById('customCustomerSearch').value = customer.full_name;
                        document.getElementById('selectedCustomCustomerId').value = customer.id;
                    }
                } else {
                    document.getElementById('customCustomerSearch').value = '';
                    document.getElementById('selectedCustomCustomerId').value = '';
                }

                const previewContainer = document.getElementById('deathCertPreviewContainer');
                const previewImg = document.getElementById('deathCertPreview');
                
                if (data.death_cert_image) {
                    const imagePath = `../customer/booking/${data.death_cert_image}`;
                    previewImg.src = imagePath;
                    previewContainer.classList.remove('hidden');
                } else {
                    previewContainer.classList.add('hidden');
                }
                
                // Removed setting the combined full name for customer
                document.getElementById('editCustomEmail').value = data.email || '';
                document.getElementById('editCustomPhone').value = data.phone_number || '';
                
                // Service details
                document.getElementById('editCustomCasket').value = data.casket_name || '';
                document.getElementById('editCustomFlowerArrangement').value = data.flower_design || '';
                let inclusionText = '';
                if (data.inclusion) {
                  try {
                    // Parse the JSON string if it's a string
                    const inclusions = typeof data.inclusion === 'string' ? JSON.parse(data.inclusion) : data.inclusion;
                    // Join the array elements with newlines
                    inclusionText = Array.isArray(inclusions) ? inclusions.join('\n') : inclusions;
                  } catch (e) {
                    // If parsing fails, use the raw value
                    inclusionText = data.inclusion;
                  }
                }
                document.getElementById('editCustomAdditionalServices').value = inclusionText;
                document.getElementById('editCustomWithCremation').checked = data.with_cremate === 'yes';
                document.getElementById('editCustomServicePrice').value = data.discounted_price || '';
                
                // Deceased information
                document.getElementById('editCustomDeceasedFirstName').value = data.fname_deceased || '';
                document.getElementById('editCustomDeceasedMiddleName').value = data.mname_deceased || '';
                document.getElementById('editCustomDeceasedLastName').value = data.lname_deceased || '';
                document.getElementById('editCustomDeceasedSuffix').value = data.suffix_deceased || '';
                document.getElementById('editCustomBirthDate').value = data.date_of_birth || '';
                document.getElementById('editCustomDeathDate').value = data.date_of_death || '';
                document.getElementById('editCustomBurialDate').value = data.date_of_burial || '';
                
                if (data.deceased_address) {
                    document.getElementById('editCustomCurrentAddressDisplay').value = data.deceased_address;
                }
                
                document.getElementById('editCustomServiceModal').classList.remove('hidden');
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

document.getElementById('editCustomDeathCertificate').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const previewContainer = document.getElementById('deathCertPreviewContainer');
    const previewImg = document.getElementById('deathCertPreview');
    
    if (file) {
        if (file.type.match('image.*')) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                previewContainer.classList.remove('hidden');
            };
            
            reader.readAsDataURL(file);
        } else {
            // Handle PDF or other file types differently
            previewContainer.classList.add('hidden');
            // You might want to show a PDF icon or some indication
        }
    } else {
        previewContainer.classList.add('hidden');
    }
});

// Function to close the Edit Custom Service Modal
function closeEditCustomModal() {
    document.getElementById('editCustomServiceModal').classList.add('hidden');
    toggleBodyScroll(false);
}

// Date validation functions
function validateDates() {
  const birthDate = new Date(document.getElementById('editCustomBirthDate').value);
  const deathDate = new Date(document.getElementById('editCustomDeathDate').value);
  const burialDate = new Date(document.getElementById('editCustomBurialDate').value);
  const today = new Date();
  
  // Validate birth date (can't be in future)
  if (birthDate > today) {
    Swal.fire({
      icon: 'error',
      title: 'Invalid Birth Date',
      text: 'Birth date cannot be in the future',
    });
    return false;
  }
  
  // Validate death date (must be after birth date)
  if (deathDate < birthDate) {
    Swal.fire({
      icon: 'error',
      title: 'Invalid Death Date',
      text: 'Death date cannot be before birth date',
    });
    return false;
  }
  
  // Validate burial date (must be after death date)
  if (burialDate < deathDate) {
    Swal.fire({
      icon: 'error',
      title: 'Invalid Burial Date',
      text: 'Burial date cannot be before death date',
    });
    return false;
  }
  
  return true;
}

// Price validation function
function validateCustomPrice() {
  let rawPrice = document.getElementById('editCustomServicePrice').value.trim();
  console.log('raw price:', rawPrice);
  const cleanedPrice = rawPrice.replace(/[^0-9.]/g, ''); // strip out anything except digits and dot
  
  const price = parseFloat(cleanedPrice);
  console.log('Parsed price:', price);
  
  if (price <= 0 || isNaN(price)) {
    Swal.fire({
      icon: 'error',
      title: 'Invalid Price',
      text: 'Price must be a positive number',
    });
    return false;
  }
  
  return true;
}

// Form submission handler with validations
document.getElementById('editCustomServiceForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  // Validate price first
  if (!validateCustomPrice()) {
    return;
  }
  
  // Validate dates
  if (!validateDates()) {
    return;
  }
  
  const submitBtn = this.querySelector('button[type="submit"]');
  const originalBtnText = submitBtn.innerHTML;
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
  submitBtn.disabled = true;
  
  const formData = new FormData(this);
  
  fetch('history/update_custom_service.php', {
    method: 'POST',
    body: formData
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    return response.json();
  })
  .then(data => {
    if (data.success) {
      Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Service updated successfully',
        timer: 1500,
        showConfirmButton: false
      }).then(() => {
        closeEditCustomModal();
        location.reload();
      });
    } else {
      throw new Error(data.message || 'Failed to update service');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.message || 'An error occurred while updating the service',
    });
  })
  .finally(() => {
    submitBtn.innerHTML = originalBtnText;
    submitBtn.disabled = false;
  });
});

// Real-time validation for price field
document.getElementById('editCustomServicePrice').addEventListener('input', function() {
  const submitBtn = document.querySelector('#editCustomServiceForm button[type="submit"]');
  const price = parseFloat(this.value);
  
  if (price <= 0 || isNaN(price)) {
    submitBtn.disabled = true;
  } else {
    submitBtn.disabled = false;
  }
});

// Date change event listeners for real-time validation
document.getElementById('editCustomBirthDate').addEventListener('change', validateDates);
document.getElementById('editCustomDeathDate').addEventListener('change', validateDates);
document.getElementById('editCustomBurialDate').addEventListener('change', validateDates);

// Helper function to show notifications
function showNotification(type, message) {
    // You can implement your own notification system here
    // For example using Toastr, SweetAlert, or a custom div
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 px-4 py-2 rounded-md shadow-md text-white ${
        type === 'success' ? 'bg-green-500' : 'bg-red-500'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 5000);
}

function toggleCustomAddressChange() {
    const addressChangeSection = document.getElementById('editCustomAddressChangeSection');
    const currentAddressDisplay = document.getElementById('editCustomCurrentAddressDisplay');
    
    if (addressChangeSection.classList.contains('hidden')) {
        // Show the address change section
        addressChangeSection.classList.remove('hidden');
        
        initializeCustomAddressFields();
        loadCustomRegions();
    } else {
        // Hide the address change section
        addressChangeSection.classList.add('hidden');
        // Show the current address display
        currentAddressDisplay.classList.remove('hidden');
    }
}

// Function to cancel custom address change
function cancelCustomAddressChange() {
    const addressChangeSection = document.getElementById('editCustomAddressChangeSection');
    const currentAddressDisplay = document.getElementById('editCustomCurrentAddressDisplay');
    
    // Hide the address change section
    addressChangeSection.classList.add('hidden');
    // Show the current address display
    currentAddressDisplay.classList.remove('hidden');
    
    // Reset the dropdowns to their default state
    document.getElementById('editCustomRegionSelect').value = '';
    document.getElementById('editCustomProvinceSelect').value = '';
    document.getElementById('editCustomCitySelect').value = '';
    document.getElementById('editCustomBarangaySelect').value = '';
    document.getElementById('editCustomStreetInput').value = '';
    document.getElementById('editCustomZipCodeInput').value = '';
}

function loadCustomRegions() {
    const regionSelect = document.getElementById('editCustomRegionSelect');
    if (!regionSelect) {
        console.error('Region select element not found');
        return;
    }
    
    // Clear existing options
    regionSelect.innerHTML = '<option value="">Select Region</option>';
    
    fetch('../employee/historyAPI/addressDB.php?action=getRegions')
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            console.log('Regions data:', data);
            data.forEach(region => {
                const option = document.createElement('option');
                option.value = region.region_id;
                option.textContent = region.region_name;
                regionSelect.appendChild(option);
            });
            console.log('Regions loaded:', regionSelect.options.length);
        })
        .catch(error => console.error('Error loading regions:', error));
}

// Function to load provinces based on selected region
function loadCustomProvinces() {
    const regionSelect = document.getElementById('editCustomRegionSelect');
    if (!regionSelect) return;
    
    const regionId = regionSelect.value;
    console.log('Loading provinces for region:', regionId);
    const provinceSelect = document.getElementById('editCustomProvinceSelect');
    if (!provinceSelect) {
        console.error('Province select element not found');
        return;
    }
    
    // Clear existing options
    provinceSelect.innerHTML = '<option value="">Select Province</option>';
    
    if (!regionId) return;
    
    fetch(`../employee/historyAPI/addressDB.php?action=getProvinces&region_id=${regionId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            console.log('Provinces data:', data);
            data.forEach(province => {
                const option = document.createElement('option');
                option.value = province.province_id;
                option.textContent = province.province_name;
                provinceSelect.appendChild(option);
            });
            console.log('Provinces loaded:', provinceSelect.options.length);
            
            // Enable the province select after loading options
            provinceSelect.disabled = false;
        })
        .catch(error => console.error('Error loading provinces:', error));
}

// Function to load municipalities based on selected province
function loadCustomCities() {
    const provinceSelect = document.getElementById('editCustomProvinceSelect');
    if (!provinceSelect) return;
    
    const provinceId = provinceSelect.value;
    console.log('Loading cities for province:', provinceId);
    const citySelect = document.getElementById('editCustomCitySelect');
    if (!citySelect) {
        console.error('City select element not found');
        return;
    }
    
    // Clear existing options
    citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
    
    if (!provinceId) return;
    
    fetch(`../employee/historyAPI/addressDB.php?action=getMunicipalities&province_id=${provinceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            console.log('Cities data:', data);
            data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.municipality_id;
                option.textContent = city.municipality_name;
                citySelect.appendChild(option);
            });
            console.log('Cities loaded:', citySelect.options.length);
            
            // Enable the city select after loading options
            citySelect.disabled = false;
        })
        .catch(error => console.error('Error loading cities:', error));
}

// Function to load barangays based on selected municipality
function loadCustomBarangays() {
    const citySelect = document.getElementById('editCustomCitySelect');
    if (!citySelect) return;
    
    const municipalityId = citySelect.value;
    console.log('Loading barangays for municipality:', municipalityId);
    const barangaySelect = document.getElementById('editCustomBarangaySelect');
    if (!barangaySelect) {
        console.error('Barangay select element not found');
        return;
    }
    
    // Clear existing options
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    
    if (!municipalityId) return;
    
    fetch(`../employee/historyAPI/addressDB.php?action=getBarangays&municipality_id=${municipalityId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            console.log('Barangays data:', data);
            data.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.barangay_id;
                option.textContent = barangay.barangay_name;
                barangaySelect.appendChild(option);
            });
            console.log('Barangays loaded:', barangaySelect.options.length);
            
            // Enable the barangay select after loading options
            barangaySelect.disabled = false;
        })
        .catch(error => console.error('Error loading barangays:', error));
}

// Function to update the current address display
function updateCustomCurrentAddress() {
    const region = document.getElementById('editCustomRegionSelect');
    const province = document.getElementById('editCustomProvinceSelect');
    const city = document.getElementById('editCustomCitySelect');
    const barangay = document.getElementById('editCustomBarangaySelect');
    const street = document.getElementById('editCustomStreetInput');
    const zipcode = document.getElementById('editCustomZipCodeInput');
    
    if (!region || !province || !city || !barangay || !street || !zipcode) {
        console.error('One or more address elements not found');
        return;
    }
    
    let address = '';
    
    // Add street if available
    if (street.value.trim()) {
        address += street.value.trim();
    }
    
    // Add barangay if selected
    if (barangay.value) {
        if (address) address += ', ';
        address += barangay.options[barangay.selectedIndex].text;
    }
    
    // Add city if selected
    if (city.value) {
        if (address) address += ', ';
        address += city.options[city.selectedIndex].text;
    }
    
    // Add province if selected
    if (province.value) {
        if (address) address += ', ';
        address += province.options[province.selectedIndex].text;
    }
    
    // Add region if selected
    if (region.value) {
        if (address) address += ', ';
        address += region.options[region.selectedIndex].text;
    }
    
    // Add zipcode if available
    if (zipcode.value.trim()) {
        if (address) address += ' ';
        address += zipcode.value.trim();
    }
    
    const currentAddressDisplay = document.getElementById('editCustomCurrentAddressDisplay');
    if (currentAddressDisplay) {
        currentAddressDisplay.value = address;
        console.log('Updated address:', address);
    } else {
        console.error('Current address display element not found');
    }
}


// Initialize address fields
function initializeCustomAddressFields() {
    console.log('Initializing custom address fields');
    
    // Get all select elements and input fields
    const regionSelect = document.getElementById('editCustomRegionSelect');
    const provinceSelect = document.getElementById('editCustomProvinceSelect');
    const citySelect = document.getElementById('editCustomCitySelect');
    const barangaySelect = document.getElementById('editCustomBarangaySelect');
    const streetInput = document.getElementById('editCustomStreetInput');
    const zipcodeInput = document.getElementById('editCustomZipCodeInput');
    
    // Verify all elements exist
    if (!regionSelect || !provinceSelect || !citySelect || !barangaySelect || !streetInput || !zipcodeInput) {
        console.error('One or more address elements not found');
        return;
    }
    
    // Initially disable dependent dropdowns
    provinceSelect.disabled = true;
    citySelect.disabled = true;
    barangaySelect.disabled = true;
    
    // Clear all fields
    regionSelect.innerHTML = '<option value="">Select Region</option>';
    provinceSelect.innerHTML = '<option value="">Select Province</option>';
    citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    streetInput.value = '';
    zipcodeInput.value = '';
    
    // Load initial regions
    loadCustomRegions();
    
    // Add change event listeners for cascading dropdowns
    regionSelect.addEventListener('change', function() {
        console.log('Region changed:', this.value);
        if (this.value) {
            loadCustomProvinces();
            provinceSelect.disabled = false;
        } else {
            provinceSelect.innerHTML = '<option value="">Select Province</option>';
            provinceSelect.disabled = true;
        }
        citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
        citySelect.disabled = true;
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        barangaySelect.disabled = true;
        updateCustomCurrentAddress();
    });
    
    provinceSelect.addEventListener('change', function() {
        console.log('Province changed:', this.value);
        if (this.value) {
            loadCustomCities();
            citySelect.disabled = false;
        } else {
            citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
            citySelect.disabled = true;
        }
        barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
        barangaySelect.disabled = true;
        updateCustomCurrentAddress();
    });
    
    citySelect.addEventListener('change', function() {
        console.log('City changed:', this.value);
        if (this.value) {
            loadCustomBarangays();
            barangaySelect.disabled = false;
        } else {
            barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
            barangaySelect.disabled = true;
        }
        updateCustomCurrentAddress();
    });
    
    barangaySelect.addEventListener('change', function() {
        console.log('Barangay changed:', this.value);
        updateCustomCurrentAddress();
    });
    
    // Add input event listeners for street and zipcode
    streetInput.addEventListener('input', function() {
        console.log('Street changed:', this.value);
        updateCustomCurrentAddress();
    });
    
    zipcodeInput.addEventListener('input', function() {
        console.log('Zipcode changed:', this.value);
        updateCustomCurrentAddress();
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing custom address fields');
    // We'll initialize the fields only when the "Change Address" button is clicked
});

function openAssignCustomStaffModal(customsales_id) {
    // Set the custom service ID in the form
    document.getElementById('assignCustomServiceId').value = customsales_id;
    
    // Show the modal
    document.getElementById('assignCustomStaffModal').classList.remove('hidden');
    
    // Fetch the branch_id and employees via AJAX
    fetch('history/get_employee_for_customsales.php?customsales_id=' + customsales_id)
        .then(response => response.json())
        .then(data => {
            // Populate the sections
            populateCustomEmployeeSection('customEmbalmersSection', 'Embalmer', data.embalmers);
            populateCustomEmployeeSection('customDriversSection', 'Driver', data.drivers);
            populateCustomEmployeeSection('customPersonnelSection', 'Personnel', data.personnel);
        })
        .catch(error => console.error('Error:', error));
}

function populateCustomEmployeeSection(sectionId, position, employees) {
    console.group(`populateCustomEmployeeSection - ${position}`);
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
            
            const checkboxId = `custom${positionLower}${index+1}`;
            console.log('Checkbox attributes:', {
                id: checkboxId,
                name: 'assigned_custom_staff[]',
                value: employee.employeeID
            });

            html += `<div class="flex items-center">
                <input type="checkbox" id="${checkboxId}" name="assigned_custom_staff[]" value="${employee.employeeID}" class="mr-2">
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

function closeAssignCustomStaffModal() {
    document.getElementById('assignCustomStaffModal').classList.add('hidden');
}

function saveCustomStaffAssignment() {
    const customServiceId = document.getElementById('assignCustomServiceId').value;
    const notes = document.getElementById('customAssignmentNotes').value;
    
    // Get all checked checkboxes within the assignCustomStaffModal
    const modal = document.getElementById('assignCustomStaffModal');
    const checkboxes = modal.querySelectorAll('input[name="assigned_custom_staff[]"]:checked');
    
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
                customsales_id: customServiceId,
                staff_data: assignedStaff.map(employeeId => ({
                    employee_id: employeeId,
                    salary: salaries[employeeId] || 0 // Default to 0 if salary not found
                })),
                notes: notes
            };

            console.log('Sending custom assignment data:', assignmentData);
            
            // Send data to server
            return fetch('history/custom_save_staff_assignment.php', {
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
                alert('Staff assigned successfully to custom service!');
                closeAssignCustomStaffModal();
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

function openCompleteCustomModal(serviceId) {
  // Set service ID and default values
  document.getElementById('completeCustomServiceId').value = serviceId;
  
  // Set current date in yyyy-mm-dd format
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  document.getElementById('customCompletionDate').value = `${year}-${month}-${day}`;
  document.getElementById('customCompletionNotes').value = '';
  document.getElementById('customFinalBalanceSettled').checked = false;
  
  // Fetch the employees via AJAX
  fetch('history/get_employee_for_customsales.php?customsales_id=' + serviceId)
    .then(response => response.json())
    .then(data => {
      // Populate the sections with drivers and personnel
      populateCustomCompleteEmployeeSection('customCompleteDriversList', 'Driver', data.drivers);
      populateCustomCompleteEmployeeSection('customCompletePersonnelList', 'Personnel', data.personnel);
      
      // Show the modal
      document.getElementById('completeCustomServiceModal').classList.remove('hidden');
      toggleBodyScroll(true);
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while fetching employee data');
    });
}

// Function to close the Complete Custom Service Modal
function closeCompleteCustomModal() {
  document.getElementById('completeCustomServiceModal').classList.add('hidden');
  toggleBodyScroll(false);
}

// Function to populate employee sections for custom service completion
function populateCustomCompleteEmployeeSection(sectionId, position, employees) {
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
      const fullName = [firstName, middleName, lastName]
        .filter(name => name && name.trim() !== '')
        .join(' ');
      
      const div = document.createElement('div');
      div.className = 'flex items-center justify-between p-2 bg-white rounded border border-gray-200 mb-2';
      
      const nameSpan = document.createElement('span');
      nameSpan.className = 'text-gray-700';
      nameSpan.textContent = fullName;
      
      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.name = 'complete_assigned_staff[]';
      checkbox.value = employee.employeeID;
      checkbox.className = 'text-sidebar-accent focus:ring-sidebar-accent';
      
      div.appendChild(nameSpan);
      div.appendChild(checkbox);
      section.appendChild(div);
    });
  } else {
    section.innerHTML = `<p class="text-gray-500">No ${position.toLowerCase()}s assigned</p>`;
  }
}

document.getElementById('completeCustomServiceForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  // Get form values
  const serviceId = document.getElementById('completeCustomServiceId').value;
  const completionDateInput = document.getElementById('customCompletionDate').value;
  const completionNotes = document.getElementById('customCompletionNotes').value;
  const balanceSettled = document.getElementById('customFinalBalanceSettled').checked;
  
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
  const modal = document.getElementById('completeCustomServiceModal');
  const checkboxes = modal.querySelectorAll('input[name="complete_assigned_staff[]"]:checked');
  
  // Extract the employee IDs from the checkboxes
  const assignedStaff = Array.from(checkboxes).map(checkbox => {
    return checkbox.value;
  }).filter(id => id); // Filter out any undefined/empty values

  if (assignedStaff.length === 0) {
    alert('Please select at least one staff member who completed this service.');
    return;
  }

  // First get the salaries for the selected employees
  fetch('get_employee_salaries.php?employee_ids=' + assignedStaff.join(','))
    .then(response => response.json())
    .then(salaries => {
      // Prepare the data to send with salary information
      const completionData = {
        customsales_id: serviceId,
        staff_data: assignedStaff.map(employeeId => ({
          employee_id: employeeId,
          salary: salaries[employeeId] || 0 // Default to 0 if salary not found
        })),
        notes: completionNotes,
        service_stage: 'completion',
        completion_date: completionDateTime,
        balance_settled: balanceSettled
      };

      // Send data to server
      return fetch('history/custom_save_staff_completion.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(completionData)
      });
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        alert('Service completed successfully!');
        closeCompleteCustomModal();
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while completing the service');
    });
});

function closeViewCustomServiceModal() {
  document.getElementById('viewCustomServiceModal').style.display = 'none';
  toggleBodyScroll(false);
}

// Function to view custom service details
function viewCustomServiceDetails(serviceId) {
  // Show loading state
  document.getElementById('customServiceId').textContent = 'Loading...';

  // Fetch service details from server
  fetch(`history/get_customsales_details.php?customsales_id=${serviceId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Populate basic service info
        document.getElementById('customServiceId').textContent = data.customsales_id;
        document.getElementById('customServiceClientName').textContent = data.client_name;
        document.getElementById('customServicePrice').textContent = 
    data.discounted_price
        ? `₱${parseFloat(data.discounted_price).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
        : '₱0.00';
        document.getElementById('customBranchName').textContent = data.branch_name || 'N/A';
        document.getElementById('customServiceDate').textContent = data.get_timestamp ? formatDate(data.get_timestamp) : 'N/A';
        document.getElementById('customServiceStatus').textContent = data.status || 'N/A';

        // Populate service components
        document.getElementById('customServiceCasket').textContent = data.casket || 'N/A';
        document.getElementById('customServiceFlowers').textContent = data.flower_design || 'N/A';
        document.getElementById('customServiceAdditional').textContent = data.inclusion || 'N/A';

        // Populate initial staff section
        if (data.initial_staff) {
          document.getElementById('customInitialDate').textContent = 
            data.initial_staff.date ? formatDate(data.initial_staff.date) : 'N/A';
          document.getElementById('customInitialEmbalmers').textContent = 
            data.initial_staff.embalmers.length > 0 ? data.initial_staff.embalmers.join(', ') : 'None';
          document.getElementById('customInitialDrivers').textContent = 
            data.initial_staff.drivers.length > 0 ? data.initial_staff.drivers.join(', ') : 'None';
          document.getElementById('customInitialPersonnel').textContent = 
            data.initial_staff.personnel.length > 0 ? data.initial_staff.personnel.join(', ') : 'None';
          document.getElementById('customInitialNotes').textContent = 
            data.initial_staff.notes || 'None';
        }

        // Populate burial staff section
        if (data.burial_staff) {
          document.getElementById('customBurialDate').textContent = 
            data.burial_staff.date ? formatDate(data.burial_staff.date) : 'N/A';
          document.getElementById('customBurialDrivers').textContent = 
            data.burial_staff.drivers.length > 0 ? data.burial_staff.drivers.join(', ') : 'None';
          document.getElementById('customBurialPersonnel').textContent = 
            data.burial_staff.personnel.length > 0 ? data.burial_staff.personnel.join(', ') : 'None';
          document.getElementById('customBurialNotes').textContent = 
            data.burial_staff.notes || 'None';
        }

        // Show the modal
        document.getElementById('viewCustomServiceModal').style.display = 'flex';
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

function openCustomRecordPaymentModal(serviceId, clientName, balance) {
  // Get the modal element
  const modal = document.getElementById('recordPaymentModal');
  
  // Set the data-mode attribute
  document.getElementById('recordPaymentBtn').setAttribute('data-mode', 'custom');
  
  // Fetch customerID and branch_id
  fetch(`history/get_custom_payment_details.php?sales_id=${serviceId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Set the hidden input values
        document.getElementById('customerID').value = data.customerID;
        document.getElementById('branchID').value = data.branch_id;
        console.log(data.customerID);console.log(data.branch_id);
        
        // Populate the readonly fields
        document.getElementById('paymentServiceId').value = serviceId;
        document.getElementById('paymentClientName').value = clientName;
        document.getElementById('currentBalance').value = `${parseFloat(balance).toFixed(2)}`;
        
        // Update summary section
        document.getElementById('summary-current-balance').textContent = `₱${parseFloat(balance).toFixed(2)}`;
        document.getElementById('summary-payment-amount').textContent = '₱0.00';
        document.getElementById('total-amount-paid').textContent = `₱${parseFloat(data.amount_paid).toFixed(2)}`;
        document.getElementById('summary-new-balance').textContent = `₱${parseFloat(balance).toFixed(2)}`;
        
        // Set default payment amount to empty
        document.getElementById('paymentAmount').value = '';
        
        // Set today's date as default payment date
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('paymentDate').value = today;
        
        // Clear any previous input in notes
        document.getElementById('paymentNotes').value = '';
        
        // Add event listener for real-time updates
        document.getElementById('paymentAmount').addEventListener('input', updatePaymentSummary);
        
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

function saveCustomPayment() {
  // Get all the necessary values
  const serviceId = document.getElementById('paymentServiceId').value;
  const customerID = document.getElementById('customerID').value;
  const branchID = document.getElementById('branchID').value;
  const clientName = document.getElementById('paymentClientName').value;
  const currentBalance = parseFloat(document.getElementById('currentBalance').value);
  const paymentAmount = parseFloat(document.getElementById('paymentAmount').value);
  const paymentMethod = document.getElementById('paymentMethod').value;
  const paymentDate = document.getElementById('paymentDate').value;
  const notes = document.getElementById('paymentNotes').value;

  if (paymentAmount > currentBalance) {
    swal({
      title: "Invalid Payment Amount",
      text: "Payment amount cannot be greater than current balance.",
      icon: "error",
      button: "OK",
    });
    return;
  }

  // Validate required fields
  if (!customerID || !branchID) {
    swal({
      title: "Missing Information",
      text: "Missing required information. Please try again.",
      icon: "error",
      button: "OK",
    });
    return;
  }

  if (!paymentAmount || isNaN(paymentAmount) || paymentAmount <= 0) {
    swal({
      title: "Invalid Amount",
      text: "Please enter a valid payment amount (greater than 0).",
      icon: "error",
      button: "OK",
    });
    return;
  }

  if (!paymentMethod) {
    swal({
      title: "Payment Method Required",
      text: "Please select a payment method.",
      icon: "error",
      button: "OK",
    });
    return;
  }

  if (!paymentDate) {
    swal({
      title: "Date Required",
      text: "Please select a payment date.",
      icon: "error",
      button: "OK",
    });
    return;
  }

  const newBalance = currentBalance - paymentAmount;

  // Create payment data object
  const paymentData = {
    sales_id: serviceId,
    customerID: customerID,
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
  const saveBtn = document.getElementById('recordPaymentBtn');
  const originalBtnText = saveBtn.innerHTML;
  saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
  saveBtn.disabled = true;

  // Send data to server
  fetch('history/record_custom_payment.php', {
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
      swal({
        title: "Success!",
        text: `Payment recorded successfully! Total paid: ₱${data.new_amount_paid.toFixed(2)}`,
        icon: "success",
        button: "OK",
      }).then(() => {
        closeRecordPaymentModal();
        // Refresh the page to show updated values
        location.reload();
      });
    } else {
      throw new Error(data.message || 'Failed to record payment');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    swal({
      title: "Error!",
      text: error.message,
      icon: "error",
      button: "OK",
    });
  })
  .finally(() => {
    // Restore button state
    saveBtn.innerHTML = originalBtnText;
    saveBtn.disabled = false;
  });
}

document.addEventListener('DOMContentLoaded', function() {
  const recordPaymentBtn = document.getElementById('recordPaymentBtn');
  if (recordPaymentBtn) {
    recordPaymentBtn.addEventListener('click', function() {
      const mode = this.getAttribute('data-mode');
      
      if (!mode) {
        alert('No payment mode specified');
        return;
      }
      
      if (mode === 'traditional') {
        savePayment();
      } else if (mode === 'custom') {
        saveCustomPayment();
      } else {
        alert('Invalid payment mode');
      }
    });
  }
});

function validatePaymentAmount(input) {
    const value = parseFloat(input.value);

    if (isNaN(value) || value <= 0) {
        input.value = '';
    }
}

function formatCurrency(amount) {
    // Convert the amount to a number if it's not already
    const num = typeof amount === 'number' ? amount : parseFloat(amount);
    
    // Check if the conversion resulted in a valid number
    if (isNaN(num)) {
        return '₱ 0.00';
    }
    
    // Format the number with commas and 2 decimal places
    const formattedAmount = num.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
        useGrouping: true
    });
    
    return `₱ ${formattedAmount}`;
}

function toProperCase(str) {
  if (!str) return '';
  return str
    .toLowerCase()               // make everything lowercase first
    .replace(/\b\w/g, c => c.toUpperCase()); // capitalize each word’s first letter
}


function sendPaymentReminder(salesId) {
  Swal.fire({
    title: 'Send Reminder?',
    text: 'This will send an SMS reminder to the customer about their outstanding balance.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, send it!',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      // Show loading
      Swal.fire({
        title: 'Sending...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      // AJAX to send the reminder
      fetch(`history/send_sms_notification.php?sales_id=${salesId}`)
        .then(response => response.json())
        .then(data => {
          Swal.close();
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: 'Reminder sent successfully.',
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Failed to send reminder.'
            });
          }
        })
        .catch(error => {
          Swal.close();
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred: ' + error.message
          });
        });
    }
  });
}


function sendCustomPaymentReminder(customsalesId) {
  Swal.fire({
    title: 'Send Reminder?',
    text: 'This will send an SMS reminder to the customer about their outstanding balance for the custom service.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Yes, send it!',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        title: 'Sending...',
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      fetch(`history/send_custom_sms_notification.php?customsales_id=${customsalesId}`)
        .then(response => response.json())
        .then(data => {
          Swal.close();
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Success!',
              text: 'Reminder sent successfully.',
              timer: 2000,
              showConfirmButton: false
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Failed to send reminder.'
            });
          }
        })
        .catch(error => {
          Swal.close();
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred: ' + error.message
          });
        });
    }
  });
}

</script>

</body> 
</html>