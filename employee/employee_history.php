<?php
//employee_chat.php
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

// Database connection
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

  $recordsPerPage = 10; // Number of records to display per page

// Initialize page variables
$pageOngoing = isset($_GET['page_ongoing']) ? (int)$_GET['page_ongoing'] : 1;
$pageFullyPaid = isset($_GET['page_fully_paid']) ? (int)$_GET['page_fully_paid'] : 1;
$pageOutstanding = isset($_GET['page_outstanding']) ? (int)$_GET['page_outstanding'] : 1;
$pageCustomOngoing = isset($_GET['page_custom_ongoing']) ? (int)$_GET['page_custom_ongoing'] : 1;
$pageCustomFullyPaid = isset($_GET['page_custom_fully_paid']) ? (int)$_GET['page_custom_fully_paid'] : 1;
$pageCustomOutstanding = isset($_GET['page_custom_outstanding']) ? (int)$_GET['page_custom_outstanding'] : 1;

// Calculate offsets
$offsetOngoing = ($pageOngoing - 1) * $recordsPerPage;
$offsetFullyPaid = ($pageFullyPaid - 1) * $recordsPerPage;
$offsetOutstanding = ($pageOutstanding - 1) * $recordsPerPage;
$offsetCustomOngoing = ($pageCustomOngoing - 1) * $recordsPerPage;
$offsetCustomFullyPaid = ($pageCustomFullyPaid - 1) * $recordsPerPage;
$offsetCustomOutstanding = ($pageCustomOutstanding - 1) * $recordsPerPage;

$customer_query = "SELECT id, CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name, ' ',COALESCE(suffix, '')) AS full_name, first_name, middle_name, last_name, suffix, email, phone_number 
                  FROM users 
                  WHERE user_type = 3 
                  ORDER BY last_name, first_name";
$customer_result = mysqli_query($conn, $customer_query);
$customers = [];
while ($row = mysqli_fetch_assoc($customer_result)) {
    $customers[] = $row;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - History</title>
  <?php include 'faviconLogo.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    
    /* Animate the sidebar
    @keyframes slideIn {
      from { transform: translateX(-100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    
    .animate-sidebar {
      animation: slideIn 0.3s ease forwards;
    } */

    /* Gradient background for menu section headers */
    .menu-header {
      background: linear-gradient(to right, rgba(202, 138, 4, 0.1), transparent);
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
  <!-- Modify the sidebar structure to include a dedicated space for the hamburger menu -->
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
        <div class="text-sm font-medium text-sidebar-text">
          <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
        </div>
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
          <a href="employee_history.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
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
        <h1 class="text-2xl font-bold text-sidebar-text">Service History</h1>
      </div>
      <div class="flex space-x-3">
        <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
          <i class="fas fa-bell"></i>
        </button>
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
      <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
        <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
          <h3 class="text-lg font-semibold text-sidebar-text">Ongoing Services</h3>
          <div class="flex gap-2">
            <div class="relative">
              <input type="text" id="searchOngoing" placeholder="Search..." class="pl-9 pr-4 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                <i class="fas fa-search text-gray-400"></i>
              </div>
            </div>
            
          </div>
        </div>
        <div class="overflow-x-auto scrollbar-thin">
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
              $ongoingQuery = "SELECT s.sales_id, s.fname, s.mname, s.lname, s.suffix, 
                      s.fname_deceased, s.mname_deceased, s.lname_deceased, s.suffix_deceased,
                      sv.service_name, s.date_of_burial, s.balance, s.status, s.customerID, s.payment_status,
                      (SELECT COUNT(*) FROM employee_service_payments esp WHERE esp.sales_id = s.sales_id) AS staff_assigned
                      FROM sales_tb s
                      JOIN services_tb sv ON s.service_id = sv.service_id
                      WHERE s.status = 'Pending' AND s.branch_id = '$branch'
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
                                  data-has-customer="<?php echo $row['customerID'] ? 'true' : 'false'; ?>">
                            <i class="fas fa-users"></i>
                          </button>
                        <?php endif; ?>
                        <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip complete-btn" 
                                title="Complete Service"
                                onclick="checkCustomerBeforeComplete('<?php echo $row['sales_id']; ?>', <?php echo $row['customerID'] ? 'true' : 'false'; ?>)"
                                data-has-customer="<?php echo $row['customerID'] ? 'true' : 'false'; ?>">
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

          <?php
          // Count total records for Ongoing Services
          $countQuery = "SELECT COUNT(*) as total FROM sales_tb WHERE status = 'Pending' AND branch_id = ?";
          $stmt = $conn->prepare($countQuery);
          $stmt->bind_param("s", $branch);
          $stmt->execute();
          $countResult = $stmt->get_result();
          $totalRecords = $countResult->fetch_assoc()['total'];
          $totalPages = ceil($totalRecords / $recordsPerPage);
          $stmt->close();
          ?>

          <div class="flex justify-between items-center p-4">
            <div>
              <p class="text-sm text-gray-600">
                Showing <?php echo $offsetOngoing + 1; ?> to <?php echo min($offsetOngoing + $recordsPerPage, $totalRecords); ?> of <?php echo $totalRecords; ?> entries
              </p>
            </div>
            <div class="flex space-x-2">
              <?php if ($pageOngoing > 1): ?>
                <a href="?page_ongoing=<?php echo $pageOngoing - 1; ?>" class="px-3 py-1 bg-sidebar-accent text-white rounded-md hover:bg-darkgold">Previous</a>
              <?php endif; ?>
              <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page_ongoing=<?php echo $i; ?>" class="px-3 py-1 <?php echo $i == $pageOngoing ? 'bg-sidebar-accent text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-md hover:bg-darkgold hover:text-white"><?php echo $i; ?></a>
              <?php endfor; ?>
              <?php if ($pageOngoing < $totalPages): ?>
                <a href="?page_ongoing=<?php echo $pageOngoing + 1; ?>" class="px-3 py-1 bg-sidebar-accent text-white rounded-md hover:bg-darkgold">Next</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Past Services - Fully Paid Section -->
      <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
        <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
          <h3 class="text-lg font-semibold text-sidebar-text">Past Services - Fully Paid</h3>
          <div class="relative">
            <input type="text" id="searchFullyPaid" placeholder="Search..." class="pl-9 pr-4 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <i class="fas fa-search text-gray-400"></i>
            </div>
          </div>
        </div>
        <div class="overflow-x-auto scrollbar-thin">
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
              $fullyPaidQuery = "SELECT s.sales_id, s.fname, s.mname, s.lname, s.suffix, 
                      s.fname_deceased, s.mname_deceased, s.lname_deceased, s.suffix_deceased,
                      sv.service_name, s.date_of_burial, s.balance, s.status, s.payment_status
                      FROM sales_tb s
                      JOIN services_tb sv ON s.service_id = sv.service_id
                      WHERE s.status = 'Completed' AND s.payment_status = 'Fully Paid' AND s.balance = 0 AND s.branch_id = ?
                      LIMIT ?, ?";
              $stmt = $conn->prepare($fullyPaidQuery);
              $stmt->bind_param("iii", $branch, $offsetFullyPaid, $recordsPerPage);
              $stmt->execute();
              $fullyPaidResult = $stmt->get_result();

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
                    <td class="pxEp. 2 - Grok 3: The Ultimate AI Powerhouse Unveiled!4 py-3.5 text-sm text-sidebar-text font-medium">#<?php echo $row['sales_id']; ?></td>
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
              $stmt->close();
              ?>
            </tbody>
          </table>

          <?php
          // Count total records for Fully Paid Services
          $countQueryFullyPaid = "SELECT COUNT(*) as total FROM sales_tb WHERE status = 'Completed' AND payment_status = 'Fully Paid' AND balance = 0 AND branch_id = ?";
          $stmt = $conn->prepare($countQueryFullyPaid);
          $stmt->bind_param("s", $branch);
          $stmt->execute();
          $countResult = $stmt->get_result();
          $totalRecordsFullyPaid = $countResult->fetch_assoc()['total'];
          $totalPagesFullyPaid = ceil($totalRecordsFullyPaid / $recordsPerPage);
          $stmt->close();
          ?>

          <div class="flex justify-between items-center p-4">
            <div>
              <p class="text-sm text-gray-600">
                Showing <?php echo $offsetFullyPaid + 1; ?> to <?php echo min($offsetFullyPaid + $recordsPerPage, $totalRecordsFullyPaid); ?> of <?php echo $totalRecordsFullyPaid; ?> entries
              </p>
            </div>
            <div class="flex space-x-2">
              <?php if ($pageFullyPaid > 1): ?>
                <a href="?page_fully_paid=<?php echo $pageFullyPaid - 1; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_outstanding=<?php echo $pageOutstanding; ?>" class="px-3 py-1 bg-sidebar-accent text-white rounded-md hover:bg-darkgold">Previous</a>
              <?php endif; ?>
              <?php for ($i = 1; $i <= $totalPagesFullyPaid; $i++): ?>
                <a href="?page_fully_paid=<?php echo $i; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_outstanding=<?php echo $pageOutstanding; ?>" class="px-3 py-1 <?php echo $i == $pageFullyPaid ? 'bg-sidebar-accent text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-md hover:bg-darkgold hover:text-white"><?php echo $i; ?></a>
              <?php endfor; ?>
              <?php if ($pageFullyPaid < $totalPagesFullyPaid): ?>
                <a href="?page_fully_paid=<?php echo $pageFullyPaid + 1; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_outstanding=<?php echo $pageOutstanding; ?>" class="px-3 py-1 bg-sidebar-accent text-white rounded-md hover:bg-darkgold">Next</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Past Services - With Outstanding Balance Section -->
      <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
        <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
          <h3 class="text-lg font-semibold text-sidebar-text">Past Services - With Outstanding Balance</h3>
          <div class="relative">
            <input type="text" id="searchOutstanding" placeholder="Search..." class="pl-9 pr-4 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <i class="fas fa-search text-gray-400"></i>
            </div>
          </div>
        </div>
        <div class="overflow-x-auto scrollbar-thin">
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
              $withBalanceQuery = "SELECT s.sales_id, s.fname, s.mname, s.lname, s.suffix, 
                      s.fname_deceased, s.mname_deceased, s.lname_deceased, s.suffix_deceased,
                      sv.service_name, s.date_of_burial, s.balance, s.status, s.payment_status
                      FROM sales_tb s
                      JOIN services_tb sv ON s.service_id = sv.service_id
                      WHERE s.status = 'Completed' AND s.payment_status = 'With Balance' AND s.branch_id = ?
                      LIMIT ?, ?";
              $stmt = $conn->prepare($withBalanceQuery);
              $stmt->bind_param("iii", $branch, $offsetOutstanding, $recordsPerPage);
              $stmt->execute();
              $withBalanceResult = $stmt->get_result();

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
              $stmt->close();
              ?>
            </tbody>
          </table>

          <?php
          // Count total records for Outstanding Balance Services
          $countQueryOutstanding = "SELECT COUNT(*) as total FROM sales_tb WHERE status = 'Completed' AND payment_status = 'With Balance' AND branch_id = ?";
          $stmt = $conn->prepare($countQueryOutstanding);
          $stmt->bind_param("s", $branch);
          $stmt->execute();
          $countResult = $stmt->get_result();
          $totalRecordsOutstanding = $countResult->fetch_assoc()['total'];
          $totalPagesOutstanding = ceil($totalRecordsOutstanding / $recordsPerPage);
          $stmt->close();
          ?>

          <div class="flex justify-between items-center p-4">
            <div>
              <p class="text-sm text-gray-600">
                Showing <?php echo $offsetOutstanding + 1; ?> to <?php echo min($offsetOutstanding + $recordsPerPage, $totalRecordsOutstanding); ?> of <?php echo $totalRecordsOutstanding; ?> entries
              </p>
            </div>
            <div class="flex space-x-2">
              <?php if ($pageOutstanding > 1): ?>
                <a href="?page_outstanding=<?php echo $pageOutstanding - 1; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_fully_paid=<?php echo $pageFullyPaid; ?>" class="px-3 py-1 bg-sidebar-accent text-white rounded-md hover:bg-darkgold">Previous</a>
              <?php endif; ?>
              <?php for ($i = 1; $i <= $totalPagesOutstanding; $i++): ?>
                <a href="?page_outstanding=<?php echo $i; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_fully_paid=<?php echo $pageFullyPaid; ?>" class="px-3 py-1 <?php echo $i == $pageOutstanding ? 'bg-sidebar-accent text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-md hover:bg-darkgold hover:text-white"><?php echo $i; ?></a>
              <?php endfor; ?>
              <?php if ($pageOutstanding < $totalPagesOutstanding): ?>
                <a href="?page_outstanding=<?php echo $pageOutstanding + 1; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_fully_paid=<?php echo $pageFullyPaid; ?>" class="px-3 py-1 bg-sidebar-accent text-white rounded-md hover:bg-darkgold">Next</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Custom Sales Tab Content -->
    <div id="custom-content" class="tab-content hidden">
      <!-- Custom Ongoing Services Section -->
      <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
        <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
          <h3 class="text-lg font-semibold text-sidebar-text">Custom Ongoing Services</h3>
          <div class="relative">
            <input type="text" id="searchCustomOngoing" placeholder="Search..." class="pl-9 pr-4 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <i class="fas fa-search text-gray-400"></i>
            </div>
          </div>
        </div>
        <div class="overflow-x-auto scrollbar-thin">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-sidebar-border">
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(0, 'customOngoing')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-hashtag text-sidebar-accent"></i> ID
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(1, 'customOngoing')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-user text-sidebar-accent"></i> Client
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(2, 'customOngoing')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-user-alt text-sidebar-accent"></i> Deceased
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(3, 'customOngoing')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-peso-sign text-sidebar-accent"></i> Price
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(4, 'customOngoing')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-calendar text-sidebar-accent"></i> Date of Burial
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(5, 'customOngoing')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-toggle-on text-sidebar-accent"></i> Status
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(6, 'customOngoing')">
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
            <tbody id="customOngoingServiceTableBody">
              <?php
              // Query for Custom Ongoing Services
              $customOngoingQuery = "SELECT 
                cs.customsales_id,
                CONCAT_WS(' ', 
                  u.first_name, 
                  COALESCE(u.middle_name, ''), 
                  u.last_name, 
                  COALESCE(u.suffix, '')
                ) AS client_name,
                CONCAT_WS(' ', 
                  cs.fname_deceased, 
                  COALESCE(cs.mname_deceased, ''), 
                  cs.lname_deceased, 
                  COALESCE(cs.suffix_deceased, '')
                ) AS deceased_name,
                cs.discounted_price,
                cs.date_of_burial,
                cs.status,
                cs.balance,
                cs.customer_id,
                (SELECT COUNT(*) FROM employee_service_payments esp WHERE esp.sales_id = cs.customsales_id AND esp.sales_type = 'custom') AS staff_assigned
              FROM customsales_tb AS cs
              JOIN users AS u ON cs.customer_id = u.id
              WHERE cs.branch_id = ? AND cs.status = 'Pending'
                    LIMIT ?, ?";
              $stmt = $conn->prepare($customOngoingQuery);
              $stmt->bind_param("iii", $branch, $offsetCustomOngoing, $recordsPerPage); 
              $stmt->execute();
              $customOngoingResult = $stmt->get_result();
              
              if ($customOngoingResult->num_rows > 0) {
                while($row = $customOngoingResult->fetch_assoc()) {
                  ?>
              <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#<?php echo $row['customsales_id']; ?></td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['client_name']); ?></td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['deceased_name']); ?></td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                    <?php echo '₱' . number_format($row['discounted_price'], 2); ?>
                  </span>
                </td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['date_of_burial']); ?></td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text">
                  <button onclick="viewCustomServiceDetails(<?php echo $row['customsales_id']; ?>)" class="text-sidebar-accent hover:text-darkgold transition-colors">
                    <i class="fas fa-eye"></i> View Details
                  </button>
                </td>
                <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">₱<?php echo number_format($row['balance'], 2); ?></td>
                <td class="px-4 py-3.5 text-sm">
                  <div class="flex space-x-2">
                    <button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" title="Edit Service" onclick="openEditCustomServiceModal('<?php echo $row['customsales_id']; ?>')">
                      <i class="fas fa-edit"></i>
                    </button>
                    <?php if ($row['staff_assigned'] == 0): ?>
                      <button class="p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 transition-all tooltip assign-staff-btn" 
                      title="Assign Staff" 
                      onclick="openAssignCustomStaffModal('<?php echo $row['customsales_id']; ?>')"
                      data-has-customer="<?php echo $row['customer_id'] ? 'true' : 'false'; ?>">
                        <i class="fas fa-users"></i>
                      </button>
                    <?php endif; ?>
                    <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip complete-btn" 
                    title="Complete Service" 
                    onclick="openCompleteCustomModal('<?php echo $row['customsales_id']; ?>')"
                    data-has-customer="<?php echo $row['customer_id'] ? 'true' : 'false'; ?>">
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
                      <p class="text-gray-500">No custom ongoing services found</p>
                    </div>
                  </td>
                </tr>
                <?php
              }
              $stmt->close();
              ?>
            </tbody>
          </table>
          <?php
          // Count total records for Custom Ongoing Services
          $countCustomOngoingQuery = "SELECT COUNT(*) as total FROM customsales_tb cs JOIN users AS u ON cs.customer_id = u.id WHERE cs.branch_id = ? AND cs.status = 'Pending'";
          $stmt = $conn->prepare($countCustomOngoingQuery);
          $stmt->bind_param("i", $branch);
          $stmt->execute();
          $countResult = $stmt->get_result();
          $totalRecordsCustomOngoing = $countResult->fetch_assoc()['total'];
          $totalPagesCustomOngoing = ceil($totalRecordsCustomOngoing / $recordsPerPage);
          $stmt->close();
          ?>
          <div class="flex justify-between items-center p-4">
            <div>
              <p class="text-sm text-gray-600">
                Showing <?php echo $offsetCustomOngoing + 1; ?> to <?php echo min($offsetCustomOngoing + $recordsPerPage, $totalRecordsCustomOngoing); ?> of <?php echo $totalRecordsCustomOngoing; ?> entries
              </p>
            </div>
            <div class="flex space-x-2">
              <?php if ($pageCustomOngoing > 1): ?>
                <a href="?page_custom_ongoing=<?php echo $pageCustomOngoing - 1; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_fully_paid=<?php echo $pageFullyPaid; ?>&page_outstanding=<?php echo $pageOutstanding; ?>&page_custom_fully_paid=<?php echo $pageCustomFullyPaid; ?>&page_custom_outstanding=<?php echo $pageCustomOutstanding; ?>" class="px-3 py-1 bg-sidebar-accent text-white rounded-md hover:bg-darkgold">Previous</a>
              <?php endif; ?>
              <?php for ($i = 1; $i <= $totalPagesCustomOngoing; $i++): ?>
                <a href="?page_custom_ongoing=<?php echo $i; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_fully_paid=<?php echo $pageFullyPaid; ?>&page_outstanding=<?php echo $pageOutstanding; ?>&page_custom_fully_paid=<?php echo $pageCustomFullyPaid; ?>&page_custom_outstanding=<?php echo $pageCustomOutstanding; ?>" class="px-3 py-1 <?php echo $i == $pageCustomOngoing ? 'bg-sidebar-accent text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-md hover:bg-darkgold hover:text-white"><?php echo $i; ?></a>
              <?php endfor; ?>
              <?php if ($pageCustomOngoing < $totalPagesCustomOngoing): ?>
                <a href="?page_custom_ongoing=<?php echo $pageCustomOngoing + 1; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_fully_paid=<?php echo $pageFullyPaid; ?>&page_outstanding=<?php echo $pageOutstanding; ?>&page_custom_fully_paid=<?php echo $pageCustomFullyPaid; ?>&page_custom_outstanding=<?php echo $pageCustomOutstanding; ?>" class="px-3 py-1 bg-sidebar-accent text-white rounded-md hover:bg-darkgold">Next</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Custom Past Services - Fully Paid Section -->
      <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
        <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
          <h3 class="text-lg font-semibold text-sidebar-text">Custom Past Services - Fully Paid</h3>
          <div class="relative">
            <input type="text" id="searchCustomFullyPaid" placeholder="Search..." class="pl-9 pr-4 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <i class="fas fa-search text-gray-400"></i>
            </div>
          </div>
        </div>
        <div class="overflow-x-auto scrollbar-thin">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-sidebar-border">
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(0, 'customFullyPaid')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-hashtag text-sidebar-accent"></i> ID
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(1, 'customFullyPaid')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-user text-sidebar-accent"></i> Client
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(2, 'customFullyPaid')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-user-alt text-sidebar-accent"></i> Deceased
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(3, 'customFullyPaid')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-peso-sign text-sidebar-accent"></i> Price
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(4, 'customFullyPaid')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-calendar text-sidebar-accent"></i> Date of Burial
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(5, 'customFullyPaid')">
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
              <?php
              // Query for Custom Fully Paid Services
              $customFullyPaidQuery = "SELECT 
                cs.customsales_id,
                CONCAT_WS(' ', 
                  u.first_name, 
                  COALESCE(u.middle_name, ''), 
                  u.last_name, 
                  COALESCE(u.suffix, '')
                ) AS client_name,
                CONCAT_WS(' ', 
                  cs.fname_deceased, 
                  COALESCE(cs.mname_deceased, ''), 
                  cs.lname_deceased, 
                  COALESCE(cs.suffix_deceased, '')
                ) AS deceased_name,
                cs.discounted_price,
                cs.date_of_burial,
                cs.status,
                cs.payment_status
              FROM customsales_tb AS cs
              JOIN users AS u ON cs.customer_id = u.id
              WHERE cs.branch_id = ? AND cs.status = 'Completed' AND cs.payment_status = 'Fully Paid'
              LIMIT ?, ?";
              $stmt = $conn->prepare($customFullyPaidQuery);
              $stmt->bind_param("iii", $branch, $offsetCustomFullyPaid, $recordsPerPage);
              $stmt->execute();
              $customFullyPaidResult = $stmt->get_result();

              if ($customFullyPaidResult->num_rows > 0) {
                while($row = $customFullyPaidResult->fetch_assoc()) {
                  ?>
                  <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                    <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#<?php echo $row['customsales_id']; ?></td>
                    <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['client_name']); ?></td>
                    <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['deceased_name']); ?></td>
                    <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo '₱' . number_format($row['discounted_price'], 2); ?></td>
                    <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['date_of_burial']); ?></td>
                    <td class="px-4 py-3.5 text-sm">
                      <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-500 border border-green-200">
                        <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars($row['status']); ?>
                      </span>
                    </td>
                    <td class="px-4 py-3.5 text-sm">
                      <div class="flex space-x-2">
                        <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details" onclick="viewCustomServiceDetails('<?php echo $row['customsales_id']; ?>', 'custom')">
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
                      <p class="text-gray-500">No custom fully paid services found</p>
                    </div>
                  </td>
                </tr>
                <?php
              }
              $stmt->close();
              ?>
            </tbody>
          </table>

          <?php
          // Count total records for Custom Fully Paid Services
          $countCustomFullyPaidQuery = "SELECT COUNT(*) as total FROM customsales_tb cs JOIN users AS u ON cs.customer_id = u.id WHERE cs.branch_id = ? AND cs.status = 'Completed' AND cs.payment_status = 'Fully Paid'";
          $stmt = $conn->prepare($countCustomFullyPaidQuery);
          $stmt->bind_param("i", $branch);
          $stmt->execute();
          $countResult = $stmt->get_result();
          $totalRecordsCustomFullyPaid = $countResult->fetch_assoc()['total'];
          $totalPagesCustomFullyPaid = ceil($totalRecordsCustomFullyPaid / $recordsPerPage);
          $stmt->close();
          ?>

          <div class="flex justify-between items-center p-4">
            <div>
              <p class="text-sm text-gray-600">
                Showing <?php echo $offsetCustomFullyPaid + 1; ?> to <?php echo min($offsetCustomFullyPaid + $recordsPerPage, $totalRecordsCustomFullyPaid); ?> of <?php echo $totalRecordsCustomFullyPaid; ?> entries
              </p>
            </div>
            <div class="flex space-x-2">
              <?php if ($pageCustomFullyPaid > 1): ?>
                <a href="?page_custom_fully_paid=<?php echo $pageCustomFullyPaid - 1; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_fully_paid=<?php echo $pageFullyPaid; ?>&page_outstanding=<?php echo $pageOutstanding; ?>&page_custom_ongoing=<?php echo $pageCustomOngoing; ?>&page_custom_outstanding=<?php echo $pageCustomOutstanding; ?>" class="px-3 py-1 bg-sidebar-accent text-white rounded-md hover:bg-darkgold">Previous</a>
              <?php endif; ?>
              <?php for ($i = 1; $i <= $totalPagesCustomFullyPaid; $i++): ?>
                <a href="?page_custom_fully_paid=<?php echo $i; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_fully_paid=<?php echo $pageFullyPaid; ?>&page_outstanding=<?php echo $pageOutstanding; ?>&page_custom_ongoing=<?php echo $pageCustomOngoing; ?>&page_custom_outstanding=<?php echo $pageCustomOutstanding; ?>" class="px-3 py-1 <?php echo $i == $pageCustomFullyPaid ? 'bg-sidebar-accent text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-md hover:bg-darkgold hover:text-white"><?php echo $i; ?></a>
              <?php endfor; ?>
              <?php if ($pageCustomFullyPaid < $totalPagesCustomFullyPaid): ?>
                <a href="?page_custom_fully_paid=<?php echo $pageCustomFullyPaid + 1; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_fully_paid=<?php echo $pageFullyPaid; ?>&page_outstanding=<?php echo $pageOutstanding; ?>&page_custom_ongoing=<?php echo $pageCustomOngoing; ?>&page_custom_outstanding=<?php echo $pageCustomOutstanding; ?>" class="px-3 py-1 bg-sidebar-accent text-white rounded-md hover:bg-darkgold">Next</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Custom Past Services - With Outstanding Balance Section -->
      <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
        <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
          <h3 class="text-lg font-semibold text-sidebar-text">Custom Past Services - With Outstanding Balance</h3>
          <div class="relative">
            <input type="text" id="searchCustomOutstanding" placeholder="Search..." class="pl-9 pr-4 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <i class="fas fa-search text-gray-400"></i>
            </div>
          </div>
        </div>
        <div class="overflow-x-auto scrollbar-thin">
          <table class="w-full">
            <thead>
              <tr class="bg-gray-50 border-b border-sidebar-border">
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(0, 'customOutstanding')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-hashtag text-sidebar-accent"></i> ID
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(1, 'customOutstanding')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-user text-sidebar-accent"></i> Client
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(2, 'customOutstanding')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-user-circle text-sidebar-accent"></i> Deceased
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(3, 'customOutstanding')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-peso-sign text-sidebar-accent"></i> Price
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(4, 'customOutstanding')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-calendar text-sidebar-accent"></i> Date of Burial
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(5, 'customOutstanding')">
                  <div class="flex items-center gap-1.5">
                    <i class="fas fa-toggle-on text-sidebar-accent"></i> Status
                  </div>
                </th>
                <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(6, 'customOutstanding')">
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
              <?php
              // Query for Custom With Outstanding Balance Services
              $customOutstandingQuery = "SELECT 
                cs.customsales_id,
                CONCAT_WS(' ', 
                  u.first_name, 
                  COALESCE(u.middle_name, ''), 
                  u.last_name, 
                  COALESCE(u.suffix, '')
                ) AS client_name,
                CONCAT_WS(' ', 
                  cs.fname_deceased, 
                  COALESCE(cs.mname_deceased, ''), 
                  cs.lname_deceased, 
                  COALESCE(cs.suffix_deceased, '')
                ) AS deceased_name,
                cs.discounted_price,
                cs.date_of_burial,
                cs.status,
                cs.payment_status,
                cs.balance
              FROM customsales_tb AS cs
              JOIN users AS u ON cs.customer_id = u.id
              WHERE cs.branch_id = ? AND cs.status = 'Completed' AND cs.payment_status = 'With Balance'
              LIMIT ?, ?";
              $stmt = $conn->prepare($customOutstandingQuery);
              $stmt->bind_param("iii", $branch, $offsetCustomOutstanding, $recordsPerPage);
              $stmt->execute();
              $customOutstandingResult = $stmt->get_result();

              if ($customOutstandingResult->num_rows > 0) {
                while($row = $customOutstandingResult->fetch_assoc()) {
                  ?>
                  <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                    <td class="px-4 py-4 text-sm text-sidebar-text font-medium">#<?php echo $row['customsales_id']; ?></td>
                    <td class="px-4 py-4 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['client_name']); ?></td>
                    <td class="px-4 py-4 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['deceased_name']); ?></td>
                    <td class="px-4 py-4 text-sm text-sidebar-text"><?php echo '₱' . number_format($row['discounted_price'], 2); ?></td>
                    <td class="px-4 py-4 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['date_of_burial']); ?></td>
                    <td class="px-4 py-4 text-sm">
                      <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-500 border border-yellow-200">
                        <i class="fas fa-exclamation-circle mr-1"></i> <?php echo htmlspecialchars($row['payment_status']); ?>
                      </span>
                    </td>
                    <td class="px-4 py-4 text-sm font-medium text-sidebar-text">₱<?php echo number_format($row['balance'], 2); ?></td>
                    <td class="px-4 py-4 text-sm">
                      <div class="flex space-x-2">
                        <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details" onclick="viewCustomServiceDetails('<?php echo $row['customsales_id']; ?>', 'custom')">
                          <i class="fas fa-eye"></i>
                        </button>
                        <button class="p-2 bg-orange-100 text-orange-600 rounded-lg hover:bg-orange-200 transition-all tooltip" title="Record Payment" onclick="openCustomRecordPaymentModal('<?php echo $row['customsales_id']; ?>','<?php echo htmlspecialchars($row['client_name']); ?>','<?php echo $row['balance']; ?>')">
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
                      <p class="text-gray-500">No custom services with outstanding balance found</p>
                    </div>
                  </td>
                </tr>
                <?php
              }
              $stmt->close();
              ?>
            </tbody>
          </table>

          <?php
          // Count total records for Custom Outstanding Balance Services
          $countCustomOutstandingQuery = "SELECT COUNT(*) as total FROM customsales_tb cs JOIN users AS u ON cs.customer_id = u.id WHERE cs.branch_id = ? AND cs.status = 'Completed' AND cs.payment_status = 'With Balance'";
          $stmt = $conn->prepare($countCustomOutstandingQuery);
          $stmt->bind_param("i", $branch);
          $stmt->execute();
          $countResult = $stmt->get_result();
          $totalRecordsCustomOutstanding = $countResult->fetch_assoc()['total'];
          $totalPagesCustomOutstanding = ceil($totalRecordsCustomOutstanding / $recordsPerPage);
          $stmt->close();
          ?>

          <div class="flex justify-between items-center p-4">
            <div>
              <p class="text-sm text-gray-600">
                Showing <?php echo $offsetCustomOutstanding + 1; ?> to <?php echo min($offsetCustomOutstanding + $recordsPerPage, $totalRecordsCustomOutstanding); ?> of <?php echo $totalRecordsCustomOutstanding; ?> entries
              </p>
            </div>
            <div class="flex space-x-2">
              <?php if ($pageCustomOutstanding > 1): ?>
                <a href="?page_custom_outstanding=<?php echo $pageCustomOutstanding - 1; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_fully_paid=<?php echo $pageFullyPaid; ?>&page_outstanding=<?php echo $pageOutstanding; ?>&page_custom_ongoing=<?php echo $pageCustomOngoing; ?>&page_custom_fully_paid=<?php echo $pageCustomFullyPaid; ?>" class="px-3 py-1 bg-sidebar-accent text-white rounded-md hover:bg-darkgold">Previous</a>
              <?php endif; ?>
              <?php for ($i = 1; $i <= $totalPagesCustomOutstanding; $i++): ?>
                <a href="?page_custom_outstanding=<?php echo $i; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_fully_paid=<?php echo $pageFullyPaid; ?>&page_outstanding=<?php echo $pageOutstanding; ?>&page_custom_ongoing=<?php echo $pageCustomOngoing; ?>&page_custom_fully_paid=<?php echo $pageCustomFullyPaid; ?>" class="px-3 py-1 <?php echo $i == $pageCustomOutstanding ? 'bg-sidebar-accent text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-md hover:bg-darkgold hover:text-white"><?php echo $i; ?></a>
              <?php endfor; ?>
              <?php if ($pageCustomOutstanding < $totalPagesCustomOutstanding): ?>
                <a href="?page_custom_outstanding=<?php echo $pageCustomOutstanding + 1; ?>&page_ongoing=<?php echo $pageOngoing; ?>&page_fully_paid=<?php echo $pageFullyPaid; ?>&page_outstanding=<?php echo $pageOutstanding; ?>&page_custom_ongoing=<?php echo $pageCustomOngoing; ?>&page_custom_fully_paid=<?php echo $pageCustomFullyPaid; ?>" class="px-3 py-1 bg-sidebar-accent text-white rounded-md hover:bg-darkgold">Next</a>
              <?php endif; ?>
            </div>
          </div>
        </div>
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
      <form id="editServiceForm" class="space-y-3 sm:space-y-6">
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
                id="editCustomerSearch" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Type customer name..."
                autocomplete="off"
              >
              <div id="editCustomerResults" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto hidden">
                <!-- Results will appear here -->
              </div>
            </div>
            <input type="hidden" id="editSelectedCustomerId" name="customer_id">
          </div>

          <!-- Customer Name Fields - 2x2 grid -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                First Name
              </label>
              <input 
                type="text" 
                id="editFirstName" 
                name="editFirstName"
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
                id="editLastName" 
                name="editLastName"
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
                id="editMiddleName" 
                name="editMiddleName"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Middle Name"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Suffix
              </label>
              <select id="editNameSuffix" name="editNameSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
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
                id="editEmail" 
                name="editEmail"
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
                id="editPhone" 
                name="editPhone"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Enter Phone Number"
              >
            </div>
          </div>

          <!-- Service Selection & Price - 2 columns -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Service Type
              </label>
              <select 
                id="editServiceType" 
                name="editServiceType"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              >
                <option value="">Choose Service</option>
                <option value="Memorial Service">Memorial Service</option>
                <option value="Funeral Service">Funeral Service</option>
                <option value="Cremation Service">Cremation Service</option>
                <option value="Visitation">Visitation</option>
                <option value="Burial Service">Burial Service</option>
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
                  id="editServicePrice" 
                  name="editServicePrice"
                  class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Enter Service Price"
                >
              </div>
            </div>
          </div>
        </div>
        
        <!-- Deceased Information Section -->
        <div class="pt-2">
          <h4 class="text-lg font-semibold flex items-center text-gray-800 mb-4">
            Deceased Information
          </h4>
          
          <!-- Deceased Name Fields - 2x2 grid -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                First Name
              </label>
              <input 
                type="text" 
                id="editDeceasedFirstName" 
                name="editDeceasedFirstName"
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
                id="editDeceasedLastName" 
                name="editDeceasedLastName"
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
                id="editDeceasedMiddleName" 
                name="editDeceasedMiddleName"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Middle Name"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Suffix
              </label>
              <select id="editDeceasedSuffix" name="editDeceasedSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
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

          <!-- Deceased Address - Dropdown System -->
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
                class="mt-2 text-xs text-sidebar-accent hover:text-darkgold transition-colors"
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
                  id="editRegionSelect" 
                  name="editRegionSelect"
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  onchange="loadProvinces('edit')"
                >
                  <option value="">Select Region</option>
                  <!-- Regions will be loaded dynamically -->
                </select>
              </div>
              
              <!-- Province Dropdown -->
              <div class="mb-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Province</label>
                <select 
                  id="editProvinceSelect" 
                  name="editProvinceSelect"
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  disabled
                  onchange="loadCities('edit')"
                >
                  <option value="">Select Province</option>
                  <!-- Provinces will be loaded dynamically -->
                </select>
              </div>
              
              <!-- City/Municipality Dropdown -->
              <div class="mb-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">City/Municipality</label>
                <select 
                  id="editCitySelect" 
                  name="editCitySelect"
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  disabled
                  onchange="loadBarangays('edit')"
                >
                  <option value="">Select City/Municipality</option>
                  <!-- Cities will be loaded dynamically -->
                </select>
              </div>
              
              <!-- Barangay Dropdown -->
              <div class="mb-3">
                <label class="block text-xs font-medium text-gray-500 mb-1">Barangay</label>
                <select 
                  id="editBarangaySelect" 
                  name="editBarangaySelect"
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
                    id="editStreetInput" 
                    name="editStreetInput"
                    class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                    placeholder="Street name, building, etc."
                  >
                </div>
                <div>
                  <label class="block text-xs font-medium text-gray-500 mb-1">Zip Code</label>
                  <input 
                    type="text" 
                    id="editZipCodeInput" 
                    name="editZipCodeInput"
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
                id="editBirthDate" 
                name="editBirthDate"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Date of Death
              </label>
              <input 
                type="date" 
                id="editDeathDate" 
                name="editDeathDate"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Date of Burial
              </label>
              <input 
                type="date" 
                id="editBurialDate" 
                name="editBurialDate"
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
                  <label for="editDeathCertificate" class="relative cursor-pointer bg-white rounded-md font-medium text-sidebar-accent hover:text-opacity-80 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-sidebar-accent">
                    <span>Upload a file</span>
                    <input 
                      id="editDeathCertificate" 
                      name="editDeathCertificate" 
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
            <p id="edit-file-name" class="mt-2 text-sm text-gray-500"></p>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeEditServiceModal()">
        Cancel
      </button>
      <button type="button" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="saveServiceChanges()">
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
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <div class="flex justify-between items-center">
        <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
          Assign Staff to Service
        </h3>
        <button type="button" class="text-white hover:text-gray-200 transition-colors" onclick="closeAssignStaffModal()">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="assignStaffForm" class="space-y-4">
        <input type="hidden" id="assignServiceId">
        
        <!-- Staff Sections -->
        <div class="space-y-4">
          <!-- Embalmers Section -->
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h4 class="text-lg font-bold mb-3 text-gray-700 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                <circle cx="9" cy="7" r="4"></circle>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
              </svg>
              Embalmers
            </h4>
            <div id="embalmersSection" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <!-- Will be populated by JavaScript -->
            </div>
          </div>
          
          <!-- Drivers Section -->
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h4 class="text-lg font-bold mb-3 text-gray-700 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
                <rect x="1" y="3" width="15" height="13"></rect>
                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
                <circle cx="5.5" cy="18.5" r="2.5"></circle>
                <circle cx="18.5" cy="18.5" r="2.5"></circle>
              </svg>
              Drivers
            </h4>
            <div id="driversSection" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <!-- Will be populated by JavaScript -->
            </div>
          </div>
          
          <!-- Other Staff Section -->
          <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h4 class="text-lg font-bold mb-3 text-gray-700 flex items-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
              </svg>
              Other Staff
            </h4>
            <div id="personnelSection" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <!-- Will be populated by JavaScript -->
            </div>
          </div>
          
          <!-- Notes Section -->
          <div>
            <label for="assignmentNotes" class="block text-sm font-medium text-gray-700 mb-2">
              Notes
            </label>
            <textarea id="assignmentNotes" rows="3" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-sidebar-accent focus:border-transparent outline-none transition-all duration-200"></textarea>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-4 border-t border-gray-200 bg-white">
      <div class="flex flex-col sm:flex-row justify-end gap-3">
        <button type="button" class="w-full sm:w-auto px-4 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200" onclick="closeAssignStaffModal()">
          Cancel
        </button>
        <button type="button" class="w-full sm:w-auto px-5 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="saveStaffAssignment()">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
            <polyline points="17 21 17 13 7 13 7 21"></polyline>
            <polyline points="7 3 7 8 15 8"></polyline>
          </svg>
          Save Assignment
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Complete Service Modal -->
<div id="completeServiceModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <div class="flex justify-between items-center">
        <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
          Complete Service
        </h3>
        <button type="button" class="text-white hover:text-gray-200 transition-colors" onclick="closeCompleteModal()">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
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
      <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeCompleteModal()">
        Cancel
      </button>
      <button type="button" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="finalizeServiceCompletion()">
        Complete Service
      </button>
    </div>
  </div>
</div>

<!-- Modal for Viewing Service Details -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto" id="viewServiceModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-xl w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeViewServiceModal()">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
      </svg>
    </button>
    
    <!-- Modal Header -->
    <div class="px-5 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-white border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Service Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-5 sm:px-6 py-4 sm:py-5 overflow-y-auto">
      <!-- Basic Information Section -->
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
<div class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="recordPaymentModal">
  <div class="bg-white rounded-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 class="text-xl font-bold text-white">Record Payment</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeRecordPaymentModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    
    <!-- Modal Body -->
    <div class="p-6">
      <form id="recordPaymentForm" class="space-y-6">
        <!-- Hidden inputs -->
        <input type="hidden" id="customerID" name="customerID">
        <input type="hidden" id="branchID" name="branchID">
        
        <!-- Payment Information Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Service ID -->
          <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="paymentServiceId">Service ID</label>
            <input class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent bg-gray-100" type="text" id="paymentServiceId" name="paymentServiceId" readonly>
          </div>
          
          <!-- Client Name -->
          <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="paymentClientName">Client Name</label>
            <input class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent bg-gray-100" type="text" id="paymentClientName" name="paymentClientName" readonly>
          </div>
          
          <!-- Outstanding Balance -->
          <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="currentBalance">Outstanding Balance</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500">₱</span>
              </div>
              <input class="w-full pl-8 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent bg-gray-100" type="text" id="currentBalance" name="currentBalance" readonly>
            </div>
          </div>
          
          <!-- Payment Amount -->
          <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="paymentAmount">Payment Amount</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500">₱</span>
              </div>
              <input class="w-full pl-8 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" type="number" id="paymentAmount" name="paymentAmount" required>
            </div>
          </div>
          
          <!-- Payment Method -->
          <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="paymentMethod">Payment Method</label>
            <select class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" id="paymentMethod" name="paymentMethod" required>
              <option value="" disabled selected>Select payment method</option>
              <option value="Cash">Cash</option>
              <option value="Credit Card">Credit Card</option>
              <option value="Debit Card">Debit Card</option>
              <option value="Check">Check</option>
              <option value="Bank Transfer">Bank Transfer</option>
            </select>
          </div>
          
          <!-- Payment Date -->
          <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="paymentDate">Payment Date</label>
            <input class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" type="date" id="paymentDate" name="paymentDate" required>
          </div>
        </div>
        
        <!-- Notes Section -->
        <div class="bg-navy p-5 rounded-xl shadow-sm border border-purple-100">
          <label class="block text-sm font-medium text-gray-700 mb-1" for="paymentNotes">Notes</label>
          <textarea class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" id="paymentNotes" name="paymentNotes" rows="3"></textarea>
        </div>
        
        <!-- Summary Section -->
        <div class="bg-navy p-6 rounded-xl shadow-sm border border-purple-100">
          <h4 class="font-bold text-lg mb-4 text-gray-800 border-b border-purple-100 pb-2 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
              <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            Payment Summary
          </h4>
          <div class="flex justify-between mb-2 text-gray-700">
            <span>Current Balance:</span>
            <span id="summary-current-balance" class="font-medium">₱0.00</span>
          </div>
          <div class="flex justify-between mb-2 text-gray-700">
            <span>Payment Amount:</span>
            <span id="summary-payment-amount" class="font-medium">₱0.00</span>
          </div>
          <div class="flex justify-between font-bold text-lg mt-4 pt-4 border-t border-dashed border-purple-200 text-sidebar-accent">
            <span>New Balance:</span>
            <span id="summary-new-balance">₱0.00</span>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeRecordPaymentModal()">Cancel</button>
      <button id="recordPaymentBtn" class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center" data-mode="">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        Record Payment
      </button>
    </div>
  </div>
</div>

<script>

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

  // Validate required fields
  if (!customerID || !branchID) {
    alert('Missing required information. Please try again.');
    return;
  }

  if (!paymentAmount || isNaN(paymentAmount) || paymentAmount <= 0) {
    alert('Please enter a valid payment amount');
    return;
  }

  if (!paymentMethod) {
    alert('Please select a payment method');
    return;
  }

  if (!paymentDate) {
    alert('Please select a payment date');
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
  const saveBtn = document.querySelector('#recordPaymentModal button[onclick="saveCustomPayment()"]');
  const originalBtnText = saveBtn.innerHTML;
  saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
  saveBtn.disabled = true;

  // Send data to server
  fetch('historyAPI/record_custom_payment.php', {
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
      closeRecordPaymentModal();
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
</script>

  <script>
    function showTab(tabName) {
      // Get tab content elements
      const standardContent = document.getElementById('standard-content');
      const customContent = document.getElementById('custom-content');
      const standardTab = document.getElementById('standard-tab');
      const customTab = document.getElementById('custom-tab');

      // Toggle content visibility
      if (tabName === 'standard') {
        standardContent.classList.remove('hidden');
        customContent.classList.add('hidden');
        standardTab.classList.add('active', 'border-sidebar-accent', 'text-sidebar-text');
        standardTab.classList.remove('text-gray-600', 'border-transparent');
        customTab.classList.remove('active', 'border-sidebar-accent', 'text-sidebar-text');
        customTab.classList.add('text-gray-600', 'border-transparent');
      } else if (tabName === 'custom') {
        customContent.classList.remove('hidden');
        standardContent.classList.add('hidden');
        customTab.classList.add('active', 'border-sidebar-accent', 'text-sidebar-text');
        customTab.classList.remove('text-gray-600', 'border-transparent');
        standardTab.classList.remove('active', 'border-sidebar-accent', 'text-sidebar-text');
        standardTab.classList.add('text-gray-600', 'border-transparent');
      }
    }
  </script>
  <script>
    const customers = <?php echo json_encode($customers); ?>;
    // Customer search functionality
document.addEventListener('DOMContentLoaded', function() {
  console.log('dom1');
    const customerSearch = document.getElementById('editCustomerSearch');
    const customerResults = document.getElementById('editCustomerResults');
    const selectedCustomerId = document.getElementById('editSelectedCustomerId');
    
    if (customerSearch && customerResults && selectedCustomerId) {
        // Handle input events on the customer search field
        customerSearch.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            // Clear previous results
            customerResults.innerHTML = '';
            
            // If search term is empty, hide results and return
            if (searchTerm.trim() === '') {
                customerResults.classList.add('hidden');
                selectedCustomerId.value = '';
                return;
            }
            
            // Filter customers based on search term
            const filteredCustomers = customers.filter(customer => 
                customer.full_name.toLowerCase().includes(searchTerm)
            );
            
            // Display results
            if (filteredCustomers.length > 0) {
                filteredCustomers.forEach(customer => {
                    const div = document.createElement('div');
                    div.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer';
                    div.textContent = customer.full_name;
                    div.addEventListener('click', function() {
                        customerSearch.value = customer.full_name;
                        selectedCustomerId.value = customer.id;
                        customerResults.classList.add('hidden');
                        
                        // You can also populate other customer fields here if needed
                        populateCustomerDetails(customer.id);
                    });
                    customerResults.appendChild(div);
                });
                customerResults.classList.remove('hidden');
            } else {
                const div = document.createElement('div');
                div.className = 'px-4 py-2 text-gray-500';
                div.textContent = 'No customers found';
                customerResults.appendChild(div);
                customerResults.classList.remove('hidden');
                selectedCustomerId.value = '';
            }
        });
        
        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!customerSearch.contains(e.target)) {
                customerResults.classList.add('hidden');
            }
        });
    }
});

// Optional: Function to populate customer details if needed
function populateCustomerDetails(customerId) {
    // Find the customer in the customers array
    const customer = customers.find(c => c.id == customerId);
    
    if (customer) {
        // You would need to fetch additional customer details via AJAX
        // or have them already in your customers array
        // Example:
        document.getElementById('editFirstName').value = customer.first_name || '';
        document.getElementById('editLastName').value = customer.last_name || ''; 
        document.getElementById('editMiddleName').value = customer.middle_name || '';
        document.getElementById('editNameSuffix').value = customer.suffix || '';
        document.getElementById('editEmail').value = customer.email || '';
        document.getElementById('editPhone').value = customer.phone_number || '';
        // etc.
        // etc.
    }
}
    // Function to open the modal and populate fields with service data
function openRecordPaymentModal(serviceId, clientName, balance) {
  // Get the modal element
  const modal = document.getElementById('recordPaymentModal');
  
  // Set the data-mode attribute
  document.getElementById('recordPaymentBtn').setAttribute('data-mode', 'service');
  
  // Fetch customerID and branch_id
  fetch(`historyAPI/get_payment_details.php?sales_id=${serviceId}`)
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

// Function to update payment summary in real-time
function updatePaymentSummary() {
  const currentBalance = parseFloat(document.getElementById('currentBalance').value) || 0;
  const paymentAmount = parseFloat(document.getElementById('paymentAmount').value) || 0;
  const newBalance = currentBalance - paymentAmount;
  
  // Update summary section
  document.getElementById('summary-current-balance').textContent = `₱${currentBalance.toFixed(2)}`;
  document.getElementById('summary-payment-amount').textContent = `₱${paymentAmount.toFixed(2)}`;
  document.getElementById('summary-new-balance').textContent = `₱${newBalance.toFixed(2)}`;
}

// Function to close the modal
function closeRecordPaymentModal() {
  const modal = document.getElementById('recordPaymentModal');
  modal.classList.add('hidden');
}

// Function to handle the payment submission
function savePayment() {
  // Get form values
  const serviceId = document.getElementById('paymentServiceId').value;
  const customerID = document.getElementById('customerID').value;
  const branchID = document.getElementById('branchID').value;
  const clientName = document.getElementById('paymentClientName').value;
  const currentBalance = document.getElementById('currentBalance').value.replace('$', '');
  const paymentAmount = document.getElementById('paymentAmount').value;
  const paymentMethod = document.getElementById('paymentMethod').value;
  const paymentDate = document.getElementById('paymentDate').value;
  const notes = document.getElementById('paymentNotes').value;
  
  // Validate payment amount
  if (!paymentAmount || parseFloat(paymentAmount) <= 0) {
    alert('Please enter a valid payment amount.');
    return;
  }

  // Validate required fields
  if (!customerID || !branchID) {
    alert('Missing required information. Please try again.');
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
    notes: notes
  };
  
  // Show loading state
  const saveBtn = document.querySelector('#recordPaymentModal button[onclick="savePayment()"]');
  const originalBtnText = saveBtn.innerHTML;
  saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
  saveBtn.disabled = true;
  
  // Send data to server
  fetch('historyAPI/record_payment.php', {
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
      closeRecordPaymentModal();
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
    // Function to toggle body scroll when modal is open
function toggleBodyScroll(isOpen) {
  if (isOpen) {
    document.body.classList.add('modal-open');
  } else {
    document.body.classList.remove('modal-open');
  }
}

// Function to toggle address change section
function toggleAddressChange() {
  const addressChangeSection = document.getElementById('addressChangeSection');
  addressChangeSection.classList.toggle('hidden');
}

// Function to cancel address change
function cancelAddressChange() {
  document.getElementById('addressChangeSection').classList.add('hidden');
  // Optionally reset the dropdowns if you want
  // resetAddressDropdowns();
}

// Function to open the Edit Service Modal
// Function to open the Edit Service Modal and populate with service details
function openEditServiceModal(serviceId) {
  // Fetch service details via AJAX
  fetch(`../admin/get_service_details.php?sales_id=${serviceId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Populate the form fields with the service details
        const customerSearch = document.getElementById('editCustomerSearch');
        const selectedCustomerId = document.getElementById('editSelectedCustomerId');

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

        document.getElementById('salesId').value = data.sales_id;
        
        // Customer Information
        document.getElementById('editFirstName').value = data.fname || '';
        document.getElementById('editMiddleName').value = data.mname || '';
        document.getElementById('editLastName').value = data.lname || '';
        document.getElementById('editNameSuffix').value = data.suffix || '';
        document.getElementById('editEmail').value = data.email || '';
        document.getElementById('editPhone').value = data.phone || '';
        
        // Service Information
        document.getElementById('editServiceType').value = data.service_name || '';
        document.getElementById('editServicePrice').value = data.discounted_price || '';
        
        // Deceased Information
        document.getElementById('editDeceasedFirstName').value = data.fname_deceased || '';
        document.getElementById('editDeceasedMiddleName').value = data.mname_deceased || '';
        document.getElementById('editDeceasedLastName').value = data.lname_deceased || '';
        document.getElementById('editDeceasedSuffix').value = data.suffix_deceased || '';
        document.getElementById('editBirthDate').value = data.date_of_birth || '';
        document.getElementById('editDeathDate').value = data.date_of_death || '';
        document.getElementById('editBurialDate').value = data.date_of_burial || '';
        
        // Address Information
        if (data.deceased_address) {
          document.getElementById('currentAddressDisplay').value = data.deceased_address;
        }
        
        // Show the modal
        document.getElementById('editServiceModal').classList.remove('hidden');
        toggleBodyScroll(true);
        
        // Load services for this branch
        loadServicesForBranch(data.branch_id, data.service_id);
        
      } else {
        alert('Failed to fetch service details: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while fetching service details');
    });
}

// Function to load services for a specific branch
function loadServicesForBranch(branchId, currentServiceId) {
  fetch(`../admin/get_services_for_branch.php?branch_id=${branchId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const serviceSelect = document.getElementById('editServiceType');
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

// Function to save changes to a service
function saveServiceChanges() {
    // Get all form values
    const formData = {
        sales_id: document.getElementById('salesId').value,
        customer_id: document.getElementById('editSelectedCustomerId').value || null,
        service_id: document.getElementById('editServiceType').value,
        service_price: document.getElementById('editServicePrice').value,
        firstName: document.getElementById('editFirstName').value,
        middleName: document.getElementById('editMiddleName').value,
        lastName: document.getElementById('editLastName').value,
        nameSuffix: document.getElementById('editNameSuffix').value,
        email: document.getElementById('editEmail').value,
        phone: document.getElementById('editPhone').value,
        deceasedFirstName: document.getElementById('editDeceasedFirstName').value,
        deceasedMiddleName: document.getElementById('editDeceasedMiddleName').value,
        deceasedLastName: document.getElementById('editDeceasedLastName').value,
        deceasedSuffix: document.getElementById('editDeceasedSuffix').value,
        birthDate: document.getElementById('editBirthDate').value,
        deathDate: document.getElementById('editDeathDate').value,
        burialDate: document.getElementById('editBurialDate').value,
        deceased_address: document.getElementById('currentAddressDisplay').value,
        deathCertificate: document.getElementById('editDeathCertificate').files[0]?.name || ''
    };

    console.log('Saving service changes with data:', formData);

    // Validate required fields
    if (!formData.firstName || !formData.lastName || !formData.deceasedFirstName || 
        !formData.deceasedLastName || !formData.burialDate || !formData.service_id) {
        alert('Please fill in all required fields');
        return;
    }

    // Send data to server
    fetch('historyAPI/update_history_sales.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Service updated successfully!');
            closeEditServiceModal();
            // Optionally refresh the page or update the table
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the service');
    });
}

// Function to close the Edit Service Modal
function closeEditServiceModal() {
  document.getElementById('editServiceModal').classList.add('hidden');
  toggleBodyScroll(false);
}

// Function to toggle body scroll when modal is open
function toggleBodyScroll(isOpen) {
  if (isOpen) {
    document.body.style.overflow = 'hidden';
  } else {
    document.body.style.overflow = '';
  }
}

// Function to open the Assign Staff Modal
function openAssignStaffModal(serviceId) {
    // Set the service ID in the form
    document.getElementById('assignServiceId').value = serviceId;
    
    // Show the modal
    const modal = document.getElementById('assignStaffModal');
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Fetch employees for each position
    fetch(`historyAPI/get_employees.php?service_id=${serviceId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Populate each section
            populateEmployeeSection('embalmersSection', 'Embalmer', data.embalmers || []);
            populateEmployeeSection('driversSection', 'Driver', data.drivers || []);
            populateEmployeeSection('personnelSection', 'Personnel', data.personnel || []);
        })
        .catch(error => {
            console.error('Error fetching employees:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load employees. Please try again.',
                confirmButtonColor: '#3085d6'
            });
            closeAssignStaffModal();
        });
}

function populateEmployeeSection(sectionId, position, employees) {
    const section = document.getElementById(sectionId);
    const positionLower = position.toLowerCase();
    
    let html = '';
    
    if (employees && employees.length > 0) {
        employees.forEach((employee, index) => {
            // Format name parts
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
            
            const checkboxId = `${positionLower}${index + 1}`;
            
            html += `
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="${checkboxId}" 
                           name="assigned_staff[]" 
                           value="${employee.employeeID}" 
                           class="w-4 h-4 text-sidebar-accent border-gray-300 rounded focus:ring-sidebar-accent">
                    <label for="${checkboxId}" class="ml-2 text-sm text-gray-700">${fullName}</label>
                </div>
            `;
        });
    } else {
        html = `<p class="text-gray-500 col-span-2">No ${positionLower}s available</p>`;
    }
    
    section.innerHTML = html;
}

function closeAssignStaffModal() {
    const modal = document.getElementById('assignStaffModal');
    modal.classList.add('hidden');
    document.body.style.overflow = '';
    document.getElementById('assignStaffForm').reset();
}

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
        Swal.fire({
            icon: 'warning',
            title: 'No Staff Selected',
            text: 'Please select at least one staff member to assign.',
            confirmButtonColor: '#3085d6'
        });
        return;
    }

    // Show loading state
    Swal.fire({
        title: 'Saving Assignment',
        text: 'Please wait...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Get base salaries for selected employees
    fetch('historyAPI/get_employee_salaries.php?employee_ids=' + assignedStaff.join(','))
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
            return fetch('historyAPI/assign_staff.php', {
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
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: data.message || 'Staff assigned successfully!',
                    confirmButtonColor: '#3085d6'
                }).then(() => {
                    closeAssignStaffModal();
                    location.reload();
                });
            } else {
                throw new Error(data.message || 'Failed to assign staff');
            }
        })
        .catch(error => {
            console.error('Error details:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error.message || 'An error occurred while saving the assignment. Please try again.',
                confirmButtonColor: '#3085d6'
            });
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
  
  // Fetch the employees via AJAX
  fetch('historyAPI/get_employees.php?service_id=' + serviceId)
    .then(response => response.json())
    .then(data => {
      // Populate the sections with drivers and personnel
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

  // First get the salaries for the selected employees
  fetch('historyAPI/get_employee_salaries.php?employee_ids=' + assignedStaff.join(','))
    .then(response => response.json())
    .then(salaries => {
      // Prepare the data to send with salary information
      const completionData = {
        sales_id: serviceId,
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
      return fetch('historyAPI/save_service_completion.php', {
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
        closeCompleteModal();
        location.reload();
      } else {
        alert('Error: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while completing the service');
    });
}

// Function to view service details (kept from original)
function viewServiceDetails(serviceId) {
  // Show loading state
  document.getElementById('serviceId').textContent = 'Loading...';
  
  // Fetch service details from server
  fetch(`historyAPI/get_service_full_details.php?sales_id=${serviceId}`)
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
  console.log('dom2');
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

// Function to check customer before assigning staff
function checkCustomerBeforeAssign(serviceId, hasCustomer) {
    if (hasCustomer) {
        openAssignStaffModal(serviceId);
    }
}

// Function to check customer before completing service
function checkCustomerBeforeComplete(serviceId, hasCustomer) {
    if (hasCustomer) {
        openCompleteModal(serviceId);
    }
}

// Add event listeners for disabled buttons
document.addEventListener('DOMContentLoaded', function() {
  console.log('dom3');
    // Handle assign staff buttons
    document.querySelectorAll('.assign-staff-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const hasCustomer = this.getAttribute('data-has-customer') === 'true';
            if (!hasCustomer) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Customer Required',
                    text: 'Please enter a customer account first by clicking the edit button',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    // Handle complete service buttons
    document.querySelectorAll('.complete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            const hasCustomer = this.getAttribute('data-has-customer') === 'true';
            if (!hasCustomer) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Customer Required',
                    text: 'Please enter a customer account first by clicking the edit button',
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'OK'
                });
            }
        });
    });
});

// Add event listener for the record payment button
document.addEventListener('DOMContentLoaded', function() {
  console.log('dom4');
  const recordPaymentBtn = document.getElementById('recordPaymentBtn');
  if (recordPaymentBtn) {
    recordPaymentBtn.addEventListener('click', function() {
      const mode = this.getAttribute('data-mode');
      
      if (!mode) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'No payment mode specified'
        });
        return;
      }
      
      if (mode === 'service') {
        savePayment();
      } else if (mode === 'custom') {
        saveCustomPayment();
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Invalid payment mode'
        });
      }
    });
  }
});

</script>
<script src="tailwind.js"></script>
<Script src="sidebar.js"></Script>
<script>
// Include the address database file
<?php include '../addressDB.php'; ?>

// Function to load regions
function loadRegions() {
    const regionSelect = document.getElementById('editRegionSelect');
    if (!regionSelect) {
        console.error('Region select element not found');
        return;
    }
    
    // Clear existing options
    regionSelect.innerHTML = '<option value="">Select Region</option>';
    
    fetch('historyAPI/addressDB.php?action=getRegions')
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
    const provinceSelect = document.getElementById('editProvinceSelect');
    if (!provinceSelect) {
        console.error('Province select element not found');
        return;
    }
    
    // Clear existing options
    provinceSelect.innerHTML = '<option value="">Select Province</option>';
    
    if (!regionId) return;
    
    fetch(`historyAPI/addressDB.php?action=getProvinces&region_id=${regionId}`)
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
function loadMunicipalities(provinceId) {
    console.log('Loading municipalities for province:', provinceId);
    const citySelect = document.getElementById('editCitySelect');
    if (!citySelect) {
        console.error('City select element not found');
        return;
    }
    
    // Clear existing options
    citySelect.innerHTML = '<option value="">Select City/Municipality</option>';
    
    if (!provinceId) return;
    
    fetch(`historyAPI/addressDB.php?action=getMunicipalities&province_id=${provinceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error:', data.error);
                return;
            }
            console.log('Municipalities data:', data);
            data.forEach(municipality => {
                const option = document.createElement('option');
                option.value = municipality.municipality_id;
                option.textContent = municipality.municipality_name;
                citySelect.appendChild(option);
            });
            console.log('Municipalities loaded:', citySelect.options.length);
            
            // Enable the city select after loading options
            citySelect.disabled = false;
        })
        .catch(error => console.error('Error loading municipalities:', error));
}

// Function to load barangays based on selected municipality
function loadBarangays(municipalityId) {
    console.log('Loading barangays for municipality:', municipalityId);
    const barangaySelect = document.getElementById('editBarangaySelect');
    if (!barangaySelect) {
        console.error('Barangay select element not found');
        return;
    }
    
    // Clear existing options
    barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
    
    if (!municipalityId) return;
    
    fetch(`historyAPI/addressDB.php?action=getBarangays&municipality_id=${municipalityId}`)
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
        })
        .catch(error => console.error('Error loading barangays:', error));
}

// Function to update the currentAddressDisplay
function updateCurrentAddress() {
    const region = document.getElementById('editRegionSelect');
    const province = document.getElementById('editProvinceSelect');
    const city = document.getElementById('editCitySelect');
    const barangay = document.getElementById('editBarangaySelect');
    const street = document.getElementById('editStreetInput');
    const zipcode = document.getElementById('editZipCodeInput');
    
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
    const regionSelect = document.getElementById('editRegionSelect');
    const provinceSelect = document.getElementById('editProvinceSelect');
    const citySelect = document.getElementById('editCitySelect');
    const barangaySelect = document.getElementById('editBarangaySelect');
    const streetInput = document.getElementById('editStreetInput');
    const zipcodeInput = document.getElementById('editZipCodeInput');
    
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
            loadMunicipalities(this.value);
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
</script>

<!-- Custom Service Modals -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="editCustomServiceModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeEditCustomServiceModal()">
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
                id="editCustomCustomerSearch" 
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Type customer name..."
                autocomplete="off"
              >
              <div id="editCustomCustomerResults" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto hidden">
                <!-- Results will appear here -->
              </div>
            </div>
            <input type="hidden" id="editCustomSelectedCustomerId" name="customer_id">
          </div>

          <!-- Contact Information - 2 columns -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Email
              </label>
              <input 
                type="email" 
                id="editCustomEmail" 
                name="editCustomEmail"
                class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Enter Email"
                readonly
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
                class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Enter Phone Number"
                readonly
              >
            </div>
          </div>
        </div>
        
        <!-- Service Details Section -->
        <div class="pt-4">
          <h4 class="text-lg font-semibold flex items-center text-gray-800 mb-4">
            Service Details
          </h4>

          <!-- Casket Selection -->
          <div class="form-group mb-4">
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Casket
            </label>
            <input 
              type="text" 
              id="editCustomCasket" 
              name="editCustomCasket"
              class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              readonly
            >
          </div>

          <!-- Flower Arrangements -->
          <div class="form-group mb-4">
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Flower Arrangements
            </label>
            <textarea 
              id="editCustomFlowerArrangements" 
              name="editCustomFlowerArrangements"
              class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              placeholder="Enter flower arrangements details"
              rows="3"
            ></textarea>
          </div>

          <!-- Additional Services -->
          <div class="form-group mb-4">
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Additional Services
            </label>
            <textarea 
              id="editCustomAdditionalServices" 
              name="editCustomAdditionalServices"
              class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              placeholder="Enter additional services details"
              rows="3"
            ></textarea>
          </div>

          <!-- Service Price -->
          <div class="form-group mb-4">
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Service Price
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500">₱</span>
              </div>
              <input 
                type="number" 
                id="editCustomServicePrice" 
                name="editCustomServicePrice"
                class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Enter Service Price"
              >
            </div>
          </div>
        </div>

        <!-- Deceased Information Section -->
        <div class="pt-4 border-t border-gray-200">
          <h4 class="text-lg font-semibold flex items-center text-gray-800 mb-4">
            Deceased Information
          </h4>

          <!-- Deceased Name Fields - 2x2 grid -->
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
                placeholder="First Name"
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
                placeholder="Last Name"
              >
            </div>
            <div class="form-group">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Middle Name
              </label>
              <input 
                type="text" 
                id="editCustomDeceasedMiddleName" 
                name="editCustomDeceasedMiddleName"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Middle Name"
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
                Date of Birth
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
                Date of Death
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
                Date of Burial
              </label>
              <input 
                type="date" 
                id="editCustomBurialDate" 
                name="editCustomBurialDate"
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
              >
            </div>
          </div>

          <!-- Address -->
          <div class="form-group mb-4">
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Deceased Address
            </label>
            <div class="space-y-3">
              <!-- Region Dropdown -->
              <div class="form-group">
                <select 
                  id="editCustomRegion" 
                  name="editCustomRegion"
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  onchange="loadCustomProvinces(this.value); updateCustomAddress()"
                >
                  <option value="">Select Region</option>
                </select>
              </div>

              <!-- Province Dropdown -->
              <div class="form-group">
                <select 
                  id="editCustomProvince" 
                  name="editCustomProvince"
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  onchange="loadCustomCities(this.value); updateCustomAddress()"
                >
                  <option value="">Select Province</option>
                </select>
              </div>

              <!-- City Dropdown -->
              <div class="form-group">
                <select 
                  id="editCustomCity" 
                  name="editCustomCity"
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  onchange="loadCustomBarangays(this.value); updateCustomAddress()"
                >
                  <option value="">Select City</option>
                </select>
              </div>

              <!-- Barangay Dropdown -->
              <div class="form-group">
                <select 
                  id="editCustomBarangay" 
                  name="editCustomBarangay"
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  onchange="updateCustomAddress()"
                >
                  <option value="">Select Barangay</option>
                </select>
              </div>

              <!-- Street Address -->
              <div class="form-group">
                <input 
                  type="text" 
                  id="editCustomStreetAddress" 
                  name="editCustomStreetAddress"
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Enter Street Address"
                  onchange="updateCustomAddress()"
                >
              </div>

              <!-- Address Textarea -->
              <div class="form-group">
                <textarea 
                  id="editCustomDeceasedAddress" 
                  name="editCustomDeceasedAddress"
                  class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Selected Address"
                  rows="3"
                  readonly
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Death Certificate Section -->
          <div class="form-group mb-4">
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Death Certificate
            </label>
            
            <!-- Display Area for Existing Death Certificate -->
            <div id="editCustomDeathCertDisplay" class="mb-3 hidden">
              <div class="relative border border-gray-300 rounded-lg p-4">
                <div class="flex items-center justify-between">
                  <div class="flex items-center">
                    <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                    <span id="editCustomDeathCertName" class="text-sm text-gray-700"></span>
                  </div>
                  <div class="flex space-x-2">
                    <button 
                      type="button" 
                      onclick="viewDeathCertificate()"
                      class="text-sidebar-accent hover:text-darkgold"
                    >
                      <i class="fas fa-eye"></i>
                    </button>
                    <button 
                      type="button" 
                      onclick="removeDeathCertificate()"
                      class="text-red-500 hover:text-red-700"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Upload Button -->
            <div class="flex items-center space-x-3">
              <label class="flex-1">
                <input 
                  type="file" 
                  id="editCustomDeathCert" 
                  name="editCustomDeathCert"
                  accept=".pdf,.jpg,.jpeg,.png"
                  class="hidden"
                  onchange="handleDeathCertUpload(this)"
                >
                <div class="w-full px-4 py-2 bg-white border border-gray-300 rounded-lg cursor-pointer hover:bg-gray-50 text-center text-sm text-gray-700">
                  <i class="fas fa-upload mr-2"></i>
                  Upload Death Certificate
                </div>
              </label>
              <button 
                type="button"
                onclick="document.getElementById('editCustomDeathCert').click()"
                class="px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent"
              >
                Browse
              </button>
            </div>
            <p class="mt-1 text-xs text-gray-500">Accepted formats: PDF, JPG, JPEG, PNG</p>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
          <button 
            type="button" 
            onclick="closeEditCustomServiceModal()"
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

<!-- Assign Staff Modal for Custom Services -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="assignCustomStaffModal">
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
      <form id="assignCustomStaffForm" class="space-y-4">
        <input type="hidden" id="assignCustomServiceId" name="customsales_id">
        
        <!-- Staff Assignment Sections -->
        <div id="customEmbalmersSection" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
          <h4 class="text-sm font-semibold text-gray-700 mb-3">Embalmers</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="customEmbalmersList">
            <!-- Embalmers will be populated here -->
          </div>
        </div>

        <div id="customDriversSection" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
          <h4 class="text-sm font-semibold text-gray-700 mb-3">Drivers</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="customDriversList">
            <!-- Drivers will be populated here -->
          </div>
        </div>

        <div id="customPersonnelSection" class="bg-gray-50 p-4 rounded-lg border border-gray-200">
          <h4 class="text-sm font-semibold text-gray-700 mb-3">Personnel</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="customPersonnelList">
            <!-- Personnel will be populated here -->
          </div>
        </div>

        <!-- Notes -->
        <div class="form-group">
          <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
          <textarea 
            id="customAssignmentNotes" 
            name="assignment_notes"
            class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
            placeholder="Add any notes about the staff assignment..."
            rows="3"
          ></textarea>
        </div>

        <!-- Form Actions -->
        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
          <button 
            type="button" 
            onclick="closeAssignCustomStaffModal()"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent"
          >
            Cancel
          </button>
          <button 
            type="submit"
            class="px-4 py-2 text-sm font-medium text-white bg-sidebar-accent border border-transparent rounded-md hover:bg-darkgold focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent"
          >
            Assign Staff
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Complete Custom Service Modal -->
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

<script>
// Function to open the Edit Custom Service Modal
function openEditCustomServiceModal(serviceId) {
  // Load regions when opening the modal
  loadCustomRegions();
  
  // Rest of your existing openEditCustomServiceModal code...
  fetch(`historyAPI/get_custom_service_details.php?customsales_id=${serviceId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Populate the form fields with the service details
        const customerSearch = document.getElementById('editCustomCustomerSearch');
        const selectedCustomerId = document.getElementById('editCustomSelectedCustomerId');

        if (customerSearch && selectedCustomerId) {
          if (data.customerID) {
            const customer = customers.find(c => c.id == data.customerID);
            if (customer) {
              customerSearch.value = `${customer.first_name} ${customer.middle_name} ${customer.last_name} ${customer.suffix}`.trim();
              selectedCustomerId.value = customer.id;
            }
          } else {
            customerSearch.value = '';
            selectedCustomerId.value = '';
          }
        }

        document.getElementById('customSalesId').value = serviceId;
        
        // Customer Information
        document.getElementById('editCustomEmail').value = data.email || '';
        document.getElementById('editCustomPhone').value = data.phone_number || '';
        
        // Service Information
        document.getElementById('editCustomServicePrice').value = data.discounted_price || '';
        document.getElementById('editCustomFlowerArrangements').value = data.flower_design || '';
        
        // Format inclusion data
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
        
        // Set the casket name
        document.getElementById('editCustomCasket').value = data.casket_name || '';
        
        // Deceased Information
        document.getElementById('editCustomDeceasedFirstName').value = data.fname_deceased || '';
        document.getElementById('editCustomDeceasedMiddleName').value = data.mname_deceased || '';
        document.getElementById('editCustomDeceasedLastName').value = data.lname_deceased || '';
        document.getElementById('editCustomDeceasedSuffix').value = data.suffix_deceased || '';
        document.getElementById('editCustomBirthDate').value = data.date_of_birth || '';
        document.getElementById('editCustomDeathDate').value = data.date_of_death || '';
        document.getElementById('editCustomBurialDate').value = data.date_of_burial || '';
        document.getElementById('editCustomDeceasedAddress').value = data.deceased_address || '';

        if (data.deceased_address) {
          try {
            const address = JSON.parse(data.deceased_address);
            document.getElementById('editCustomRegion').value = address.region || '';
            if (address.region) {
              loadCustomProvinces(address.region);
              setTimeout(() => {
                document.getElementById('editCustomProvince').value = address.province || '';
                if (address.province) {
                  loadCustomCities(address.province);
                  setTimeout(() => {
                    document.getElementById('editCustomCity').value = address.city || '';
                    if (address.city) {
                      loadCustomBarangays(address.city);
                      setTimeout(() => {
                        document.getElementById('editCustomBarangay').value = address.barangay || '';
                        document.getElementById('editCustomStreetAddress').value = address.street || '';
                        updateCustomAddress(); // Update the address textarea after all fields are set
                      }, 500);
                    }
                  }, 500);
                }
              }, 500);
            }
          } catch (error) {
            console.error('Error parsing address:', error);
            // If parsing fails, use the raw value
            document.getElementById('editCustomDeceasedAddress').value = data.deceased_address;
          }
        }
        // Handle death certificate display
        const deathCertDisplay = document.getElementById('editCustomDeathCertDisplay');
        if (data.death_cert_image) {
          deathCertDisplay.classList.remove('hidden');
          document.getElementById('editCustomDeathCertName').textContent = data.death_cert_image;
        } else {
          deathCertDisplay.classList.add('hidden');
        }

      } else {
        alert('Failed to fetch service details: ' + data.message);
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while fetching service details');
    });

  // Show the modal
  document.getElementById('editCustomServiceModal').classList.remove('hidden');
  toggleBodyScroll(true);
}

// Function to close the Edit Custom Service Modal
function closeEditCustomServiceModal() {
  document.getElementById('editCustomServiceModal').classList.add('hidden');
  toggleBodyScroll(false);
}

// Function to open the Assign Staff Modal for Custom Services
function openAssignCustomStaffModal(serviceId) {
  // Set the service ID in the form
  document.getElementById('assignCustomServiceId').value = serviceId;
  
  // Show the modal
  document.getElementById('assignCustomStaffModal').classList.remove('hidden');
  
  // Fetch the branch_id and employees via AJAX
  fetch('historyAPI/get_employees_for_custom.php?customsales_id=' + serviceId)
    .then(response => response.json())
    .then(data => {
      // Populate the sections
      populateCustomEmployeeSection('customEmbalmersList', 'Embalmer', data.embalmers);
      populateCustomEmployeeSection('customDriversList', 'Driver', data.drivers);
      populateCustomEmployeeSection('customPersonnelList', 'Personnel', data.personnel);
    })
    .catch(error => console.error('Error:', error));
}

// Function to close the Assign Staff Modal for Custom Services
function closeAssignCustomStaffModal() {
  document.getElementById('assignCustomStaffModal').classList.add('hidden');
}

// Function to populate employee sections for custom services
function populateCustomEmployeeSection(sectionId, position, employees) {
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
      div.className = 'flex items-center';
      
      const checkbox = document.createElement('input');
      checkbox.type = 'checkbox';
      checkbox.name = 'assigned_staff[]';
      checkbox.value = employee.employeeID;
      checkbox.className = 'mr-2 text-sidebar-accent focus:ring-sidebar-accent';
      
      const label = document.createElement('label');
      label.className = 'text-gray-700';
      label.textContent = fullName;
      
      div.appendChild(checkbox);
      div.appendChild(label);
      section.appendChild(div);
    });
  } else {
    section.innerHTML = `<p class="text-gray-500">No ${position.toLowerCase()}s available</p>`;
  }
}

// Function to open the Complete Custom Service Modal
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
  fetch('historyAPI/get_employees_for_custom.php?customsales_id=' + serviceId)
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

// Add event listeners for the custom service forms
document.getElementById('editCustomServiceForm').addEventListener('submit', function(e) {
  e.preventDefault();
  // Handle form submission
  const formData = new FormData(this);
  
  // Send data to server
  fetch('historyAPI/update_custom_history_sales.php', {
    method: 'POST',
    body: formData // Send FormData directly without JSON.stringify
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      alert('Service updated successfully!');
      closeEditCustomServiceModal();
      location.reload();
    } else {
      alert('Error: ' + data.message);
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert('An error occurred while updating the service');
  });
});

document.getElementById('assignCustomStaffForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  // Get form values
  const salesId = document.getElementById('assignCustomServiceId').value;
  const notes = document.getElementById('customAssignmentNotes').value;
  
  // Get all checked checkboxes within the assignCustomStaffModal
  const modal = document.getElementById('assignCustomStaffModal');
  const checkboxes = modal.querySelectorAll('input[name="assigned_staff[]"]:checked');
  
  // Extract the employee IDs from the checkboxes
  const assignedStaff = Array.from(checkboxes).map(checkbox => {
      return checkbox.value;
  }).filter(id => id); // Filter out any undefined/empty values

  if (assignedStaff.length === 0) {
      Swal.fire({
          icon: 'warning',
          title: 'No Staff Selected',
          text: 'Please select at least one staff member to assign.',
          confirmButtonColor: '#3085d6'
      });
      return;
  }

  // Show loading state
  Swal.fire({
      title: 'Saving Assignment',
      text: 'Please wait...',
      allowOutsideClick: false,
      didOpen: () => {
          Swal.showLoading();
      }
  });

  // Get base salaries for selected employees
  fetch('historyAPI/get_employee_salaries.php?employee_ids=' + assignedStaff.join(','))
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
          
          // Send data to server - changed to your new endpoint
          return fetch('historyAPI/assign_custom_staff.php', {
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
              Swal.fire({
                  icon: 'success',
                  title: 'Success',
                  text: data.message || 'Staff assigned successfully!',
                  confirmButtonColor: '#3085d6'
              }).then(() => {
                  closeAssignCustomStaffModal();
                  location.reload();
              });
          } else {
              throw new Error(data.message || 'Failed to assign staff');
          }
      })
      .catch(error => {
          console.error('Error details:', error);
          Swal.fire({
              icon: 'error',
              title: 'Error',
              text: error.message || 'An error occurred while saving the assignment. Please try again.',
              confirmButtonColor: '#3085d6'
          });
      });
});

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
  fetch('historyAPI/get_employee_salaries.php?employee_ids=' + assignedStaff.join(','))
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
      return fetch('historyAPI/save_custom_service_completion.php', {
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


// Add these JavaScript functions after the existing modal functions
function handleDeathCertUpload(input) {
  if (input.files && input.files[0]) {
    const file = input.files[0];
    const displayArea = document.getElementById('editCustomDeathCertDisplay');
    const fileName = document.getElementById('editCustomDeathCertName');
    
    // Create a temporary URL for the uploaded file
    const fileUrl = URL.createObjectURL(file);
    
    // Store both the file name and the temporary URL
    fileName.textContent = file.name;
    fileName.setAttribute('data-temp-url', fileUrl);
    fileName.setAttribute('data-is-new-upload', 'true');
    
    // Show the display area
    displayArea.classList.remove('hidden');
  }
}

function viewDeathCertificate() {
  const fileName = document.getElementById('editCustomDeathCertName');
  const isNewUpload = fileName.getAttribute('data-is-new-upload') === 'true';
  
  if (fileName.textContent) {
    let fileUrl;
    if (isNewUpload) {
      // Use the temporary URL for newly uploaded files
      fileUrl = fileName.getAttribute('data-temp-url');
    } else {
      // Use the system path for existing files
      fileUrl = `../customer/booking/${fileName.textContent}`;
    }
    
    if (fileUrl) {
      window.open(fileUrl, '_blank');
    }
  }
}

function removeDeathCertificate() {
  const displayArea = document.getElementById('editCustomDeathCertDisplay');
  const fileInput = document.getElementById('editCustomDeathCert');
  const fileName = document.getElementById('editCustomDeathCertName');
  
  // If it's a new upload, revoke the temporary URL
  if (fileName.getAttribute('data-is-new-upload') === 'true') {
    const tempUrl = fileName.getAttribute('data-temp-url');
    if (tempUrl) {
      URL.revokeObjectURL(tempUrl);
    }
  }
  
  // Hide the display area
  displayArea.classList.add('hidden');
  
  // Clear the file input
  fileInput.value = '';
  
  // Clear the file name and attributes
  fileName.textContent = '';
  fileName.removeAttribute('data-temp-url');
  fileName.removeAttribute('data-is-new-upload');
}

// Add this after the existing customer search functions
document.getElementById('editCustomCustomerSearch').addEventListener('input', function(e) {
  const searchTerm = e.target.value.trim();
  const resultsDiv = document.getElementById('editCustomCustomerResults');
  
  if (searchTerm.length < 2) {
    resultsDiv.classList.add('hidden');
    return;
  }

  // Filter customers based on search term
  const filteredCustomers = customers.filter(customer => {
    const fullName = `${customer.first_name} ${customer.middle_name} ${customer.last_name} ${customer.suffix}`.toLowerCase();
    return fullName.includes(searchTerm.toLowerCase());
  });

  // Display results
  if (filteredCustomers.length > 0) {
    resultsDiv.innerHTML = filteredCustomers.map(customer => `
      <div class="px-4 py-2 hover:bg-gray-100 cursor-pointer" 
           onclick="selectEditCustomCustomer(${customer.id}, '${customer.first_name}', '${customer.middle_name}', '${customer.last_name}', '${customer.suffix}', '${customer.email}', '${customer.phone_number}')">
        ${customer.first_name} ${customer.middle_name} ${customer.last_name} ${customer.suffix}
      </div>
    `).join('');
    resultsDiv.classList.remove('hidden');
  } else {
    resultsDiv.innerHTML = '<div class="px-4 py-2 text-gray-500">No customers found</div>';
    resultsDiv.classList.remove('hidden');
  }
});

// Add click outside listener for the search results
document.addEventListener('click', function(e) {
  const searchInput = document.getElementById('editCustomCustomerSearch');
  const resultsDiv = document.getElementById('editCustomCustomerResults');
  
  if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
    resultsDiv.classList.add('hidden');
  }
});

// Function to select a customer in the edit custom service modal
function selectEditCustomCustomer(customerId, firstName, middleName, lastName, suffix, email, phone) {
  const searchInput = document.getElementById('editCustomCustomerSearch');
  const selectedCustomerId = document.getElementById('editCustomSelectedCustomerId');
  const resultsDiv = document.getElementById('editCustomCustomerResults');
  
  // Set the selected customer
  selectedCustomerId.value = customerId;
  searchInput.value = `${firstName} ${middleName} ${lastName} ${suffix}`.trim();
  
  // Set email and phone
  document.getElementById('editCustomEmail').value = email || '';
  document.getElementById('editCustomPhone').value = phone || '';
  
  // Hide results
  resultsDiv.classList.add('hidden');
}

// Add these functions after the existing address-related functions
function loadCustomRegions() {
  fetch('historyAPI/addressDB.php?action=getRegions')
    .then(response => response.json())
    .then(data => {
      const regionSelect = document.getElementById('editCustomRegion');
      regionSelect.innerHTML = '<option value="">Select Region</option>';
      data.forEach(region => {
        regionSelect.innerHTML += `<option value="${region.region_id}">${region.region_name}</option>`;
      });
    })
    .catch(error => console.error('Error loading regions:', error));
}

function loadCustomProvinces(regionCode) {
  if (!regionCode) return;
  
  fetch(`historyAPI/addressDB.php?action=getProvinces&region_id=${regionCode}`)
    .then(response => response.json())
    .then(data => {
      const provinceSelect = document.getElementById('editCustomProvince');
      provinceSelect.innerHTML = '<option value="">Select Province</option>';
      data.forEach(province => {
        provinceSelect.innerHTML += `<option value="${province.province_id}">${province.province_name}</option>`;
      });
      // Clear dependent dropdowns
      document.getElementById('editCustomCity').innerHTML = '<option value="">Select City</option>';
      document.getElementById('editCustomBarangay').innerHTML = '<option value="">Select Barangay</option>';
    })
    .catch(error => console.error('Error loading provinces:', error));
}

function loadCustomCities(provinceCode) {
  if (!provinceCode) return;
  
  fetch(`historyAPI/addressDB.php?action=getMunicipalities&province_id=${provinceCode}`)
    .then(response => response.json())
    .then(data => {
      const citySelect = document.getElementById('editCustomCity');
      citySelect.innerHTML = '<option value="">Select City</option>';
      data.forEach(city => {
        citySelect.innerHTML += `<option value="${city.municipality_id}">${city.municipality_name}</option>`;
      });
      // Clear barangay dropdown
      document.getElementById('editCustomBarangay').innerHTML = '<option value="">Select Barangay</option>';
    })
    .catch(error => console.error('Error loading cities:', error));
}

function loadCustomBarangays(cityCode) {
  if (!cityCode) return;
  
  fetch(`historyAPI/addressDB.php?action=getBarangays&municipality_id=${cityCode}`)
    .then(response => response.json())
    .then(data => {
      const barangaySelect = document.getElementById('editCustomBarangay');
      barangaySelect.innerHTML = '<option value="">Select Barangay</option>';
      data.forEach(barangay => {
        barangaySelect.innerHTML += `<option value="${barangay.barangay_id}">${barangay.barangay_name}</option>`;
      });
    })
    .catch(error => console.error('Error loading barangays:', error));
}

// Add this function after the loadCustomBarangays function
function updateCustomAddress() {
  const region = document.getElementById('editCustomRegion');
  const province = document.getElementById('editCustomProvince');
  const city = document.getElementById('editCustomCity');
  const barangay = document.getElementById('editCustomBarangay');
  const street = document.getElementById('editCustomStreetAddress');
  const addressTextarea = document.getElementById('editCustomDeceasedAddress');

  let addressParts = [];
  
  if (street.value) addressParts.push(street.value);
  if (barangay.value) addressParts.push(barangay.options[barangay.selectedIndex].text);
  if (city.value) addressParts.push(city.options[city.selectedIndex].text);
  if (province.value) addressParts.push(province.options[province.selectedIndex].text);
  if (region.value) addressParts.push(region.options[region.selectedIndex].text);

  addressTextarea.value = addressParts.join(', ');
}
</script>

<!-- Custom Service Details Modal -->
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
              <label class="block text-xs font-medium text-gray-500">Date of Burial</label>
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
// Function to close the custom service modal
function closeViewCustomServiceModal() {
  document.getElementById('viewCustomServiceModal').style.display = 'none';
  toggleBodyScroll(false);
}

// Function to view custom service details
function viewCustomServiceDetails(serviceId) {
  // Show loading state
  document.getElementById('customServiceId').textContent = 'Loading...';

  // Fetch service details from server
  fetch(`historyAPI/get_custom_service_full_details.php?customsales_id=${serviceId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Populate basic service info
        document.getElementById('customServiceId').textContent = data.customsales_id;
        document.getElementById('customServiceClientName').textContent = data.client_name;
        document.getElementById('customServicePrice').textContent = 
          data.discounted_price ? `₱${parseFloat(data.discounted_price).toFixed(2)}` : '₱0.00';
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
  fetch(`historyAPI/get_custom_payment_details.php?sales_id=${serviceId}`)
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
// ... existing code ...
</script>

</body> 
</html>