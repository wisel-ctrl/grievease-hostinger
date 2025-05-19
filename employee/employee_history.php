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

// Calculate offsets
$offsetOngoing = ($pageOngoing - 1) * $recordsPerPage;
$offsetFullyPaid = ($pageFullyPaid - 1) * $recordsPerPage;
$offsetOutstanding = ($pageOutstanding - 1) * $recordsPerPage;

$customer_query = "SELECT id, CONCAT(first_name, ' ', COALESCE(middle_name, ''), ' ', last_name) AS full_name 
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
                    <td class="px-4 Watch Grok 3: Next-Gen AI Assistant in Action!py-3.5 text-sm">
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
                    <i class="fas fa-toggle-on text-sidebar-accent"></嘴里
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
              <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#1001</td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text">Jane Smith</td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text">John Smith</td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text">₱50,000.00</td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text">2025-06-15</td>
                <td class="px-4 py-3.5 text-sm">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-500 border border-orange-200">
                    <i class="fas fa-pause-circle mr-1"></i> Pending
                  </span>
                </td>
                <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">₱25,000.00</td>
                <td class="px-4 py-3.5 text-sm">
                  <div class="flex space-x-2">
                    <ipl button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" title="Edit Service" onclick="openEditServiceModal('1001')">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button class="p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 transition-all tooltip assign-staff-btn" title="Assign Staff" onclick="checkCustomerBeforeAssign('1001', true)">
                      <i class="fas fa-users"></i>
                    </button>
                    <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip complete-btn" title="Complete Service" onclick="checkCustomerBeforeComplete('1001', true)">
                      <i class="fas fa-check"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div class="flex justify-between items-center p-4">
            <div>
              <p class="text-sm text-gray-600">Showing 1 to 1 of 1 entries</p>
            </div>
            <div class="flex space-x-2">
              <span class="px-3 py-1 bg-sidebar-accent text-white rounded-md">1</span>
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
              <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#1002</td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text">Robert Johnson</td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text">Mary Johnson</td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text">₱75,000.00</td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text">2025-04-20</td>
                <td class="px-4 py-3.5 text-sm">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-500 border border-green-200">
                    <i class="fas fa-check-circle mr-1"></i> Completed
                  </span>
                </td>
                <td class="px-4 py-3.5 text-sm">
                  <div class="flex space-x-2">
                    <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details" onclick="viewServiceDetails('1002')">
                      <i class="fas fa-eye"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div class="flex justify-between items-center p-4">
            <div>
              <p class="text-sm text-gray-600">Showing 1 to 1 of 1 entries</p>
            </div>
            <div class="flex space-x-2">
              <span class="px-3 py-1 bg-sidebar-accent text-white rounded-md">1</span>
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
              <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                <td class="px-4 py-4 text-sm text-sidebar-text font-medium">#1003</td>
                <td class="px-4 py-4 text-sm text-sidebar-text">Alice Brown</td>
                <td class="px-4 py-4 text-sm text-sidebar-text">James Brown</td>
                <td class="px-4 py-4 text-sm text-sidebar-text">₱60,000.00</td>
                <td class="px-4 py-4 text-sm text-sidebar-text">2025-03-10</td>
                <td class="px-4 py-4 text-sm">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-500 border border-yellow-200">
                    <i class="fas fa-exclamation-circle mr-1"></i> With Balance
                  </span>
                </td>
                <td class="px-4 py-4 text-sm font-medium text-sidebar-text">₱30,000.00</td>
                <td class="px-4 py-4 text-sm">
                  <div class="flex space-x-2">
                    <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details" onclick="viewServiceDetails('1003')">
                      <i class="fas fa-eye"></i>
                    </button>
                    <button class="p-2 bg-orange-100 text-orange-600 rounded-lg hover:bg-orange-200 transition-all tooltip" title="Record Payment" onclick="openRecordPaymentModal('1003','Alice Brown','30000')">
                      <i class="fas fa-money-bill-wave"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div class="flex justify-between items-center p-4">
            <div>
              <p class="text-sm text-gray-600">Showing 1 to 1 of 1 entries</p>
            </div>
            <div class="flex space-x-2">
              <span class="px-3 py-1 bg-sidebar-accent text-white rounded-md"> 극단
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
<div class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="assignStaffModal">
  <div class="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 class="text-xl font-bold text-white">Assign Staff to Service</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeAssignStaffModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    
    <!-- Modal Body -->
    <div class="p-6">
      <form id="assignStaffForm" class="space-y-6">
        <input type="hidden" id="assignServiceId">
        
        <div class="bg-gray-50 p-5 rounded-xl">
          <h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Embalmers
          </h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center">
              <input type="checkbox" id="embalmer1" class="mr-2">
              <label for="embalmer1" class="text-gray-700">Juan Dela Cruz</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="embalmer2" class="mr-2">
              <label for="embalmer2" class="text-gray-700">Pedro Santos</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="embalmer3" class="mr-2">
              <label for="embalmer3" class="text-gray-700">Maria Lopez</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="embalmer4" class="mr-2">
              <label for="embalmer4" class="text-gray-700">Roberto Garcia</label>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 p-5 rounded-xl">
          <h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <rect x="1" y="3" width="15" height="13"></rect>
              <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
              <circle cx="5.5" cy="18.5" r="2.5"></circle>
              <circle cx="18.5" cy="18.5" r="2.5"></circle>
            </svg>
            Drivers
          </h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center">
              <input type="checkbox" id="driver1" class="mr-2">
              <label for="driver1" class="text-gray-700">Carlos Reyes</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="driver2" class="mr-2">
              <label for="driver2" class="text-gray-700">Ricardo Lim</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="driver3" class="mr-2">
              <label for="driver3" class="text-gray-700">Eduardo Torres</label>
            </div>
          </div>
        </div>
        
        <div class="bg-gray-50 p-5 rounded-xl">
          <h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Other Staff
          </h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center">
              <input type="checkbox" id="staff1" class="mr-2">
              <label for="staff1" class="text-gray-700">Ana Gonzales (Receptionist)</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="staff2" class="mr-2">
              <label for="staff2" class="text-gray-700">Miguel Ramos (Assistant)</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="staff3" class="mr-2">
              <label for="staff3" class="text-gray-700">Luisa Rivera (Coordinator)</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="staff4" class="mr-2">
              <label for="staff4" class="text-gray-700">Paolo Mendoza (Helper)</label>
            </div>
          </div>
        </div>
        
        <div>
          <label for="assignmentNotes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
          <textarea id="assignmentNotes" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent"></textarea>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeAssignStaffModal()">Cancel</button>
      <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center" onclick="saveStaffAssignment()">
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

<!-- Complete Service Modal -->
<div class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="completeServiceModal">
  <div class="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 class="text-xl font-bold text-white">Complete Service</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeCompleteModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    
    <!-- Modal Body -->
    <div class="p-6">
      <form id="completeServiceForm" class="space-y-6">
        <input type="hidden" id="completeServiceId">
        
        <div class="bg-gray-50 p-5 rounded-xl">
          <h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Assign Additional Staff for Burial
          </h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="flex items-center">
              <input type="checkbox" id="burial1" class="mr-2">
              <label for="burial1" class="text-gray-700">Javier Lopez (Grave Digger)</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="burial2" class="mr-2">
              <label for="burial2" class="text-gray-700">Fernando Cruz (Helper)</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="burial3" class="mr-2">
              <label for="burial3" class="text-gray-700">Tomas Santos (Helper)</label>
            </div>
            <div class="flex items-center">
              <input type="checkbox" id="burial4" class="mr-2">
              <label for="burial4" class="text-gray-700">Victor Reyes (Coordinator)</label>
            </div>
          </div>
        </div>
        
        <div>
          <label for="completionDate" class="block text-sm font-medium text-gray-700 mb-1">Completion Date</label>
          <input type="date" id="completionDate" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
        </div>
        
        <div>
          <label for="completionNotes" class="block text-sm font-medium text-gray-700 mb-1">Completion Notes</label>
          <textarea id="completionNotes" rows="3" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent"></textarea>
        </div>
        
        <div class="bg-navy p-5 rounded-xl">
          <div class="flex items-center">
            <input type="checkbox" id="finalBalanceSettled" class="mr-2">
            <label for="finalBalanceSettled" class="text-gray-700 font-medium">Confirm all balances are settled</label>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeCompleteModal()">Cancel</button>
      <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center" onclick="finalizeServiceCompletion()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
          <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>
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
      <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center" onclick="savePayment()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
          <polyline points="20 6 9 17 4 12"></polyline>
        </svg>
        Record Payment
      </button>
    </div>
  </div>
</div>



  <script src="script.js"></script>
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
    // Function to open the modal and populate fields with service data
function openRecordPaymentModal(serviceId, clientName, balance) {
  // Get the modal element
  const modal = document.getElementById('recordPaymentModal');
  
  // Populate the readonly fields
  document.getElementById('paymentServiceId').value = serviceId;
  document.getElementById('paymentClientName').value = clientName;
  document.getElementById('currentBalance').value = `$${parseFloat(balance).toFixed(2)}`;
  
  // Set default payment amount to the full balance
  document.getElementById('paymentAmount').value = '';
  
  // Set today's date as default payment date
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('paymentDate').value = today;
  
  // Clear any previous input in notes
  document.getElementById('paymentNotes').value = '';
  
  // Display the modal
  modal.classList.remove('hidden');
  
  // Add event listener to close modal when clicking outside
  modal.addEventListener('click', function(event) {
    if (event.target === modal) {
      closeRecordPaymentModal();
    }
  });
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
  
  // Create payment data object
  const paymentData = {
    serviceId,
    clientName,
    paymentAmount: parseFloat(paymentAmount),
    paymentMethod,
    paymentDate,
    notes,
    newBalance: (parseFloat(currentBalance) - parseFloat(paymentAmount)).toFixed(2)
  };
  
  // Here you would typically send this data to your server
  console.log('Payment recorded:', paymentData);
  
  // Example of API call (uncomment and modify as needed)
  /*
  fetch('/api/payments', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(paymentData)
  })
  .then(response => response.json())
  .then(data => {
    console.log('Success:', data);
    closeRecordPaymentModal();
    // Optionally refresh the page or update UI
    // location.reload();
  })
  .catch(error => {
    console.error('Error:', error);
    alert('Failed to record payment. Please try again.');
  });
  */
  
  // For demo purposes, just close the modal
  alert('Payment of $' + paymentAmount + ' recorded successfully!');
  closeRecordPaymentModal();
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
// Function to open the Edit Service Modal and populate with service details
function openEditServiceModal(serviceId) {
  // Fetch service details via AJAX
  fetch(`../admin/get_service_details.php?sales_id=${serviceId}`)
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        // Populate the form fields with the service details
        const customerSearch = document.getElementById('editCustomerSearch');
        const selectedCustomerId = document.getElementById('editCustomerResults');

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
            document.getElementById('editCustomerSearch').value = customer.full_name;
            document.getElementById('editCustomerResults').value = customer.id;
          }
        } else {
          // Explicitly clear if customerID is null or undefined
          document.getElementById('editCustomerSearch').value = '';
          document.getElementById('editCustomerResults').value = '';
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
        
        // Address Information - You'll need to implement the address loading functions
        // For now, we'll just set the street input
        document.getElementById('editStreetInput').value = data.deceased_address || '';
        
        // Show the modal
        document.getElementById('editServiceModal').classList.remove('hidden');
        toggleBodyScroll(true);
        
        // Load services for this branch
        loadServicesForBranch(data.branch_id, data.service_id);
        
        // Load address dropdowns if needed
        // loadAddressDropdowns(data.region, data.province, data.city, data.barangay);
        
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
    customer_id: document.getElementById('editSelectedCustomerId').value,
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
    streetInput: document.getElementById('editStreetInput').value,
    region: document.getElementById('editRegionSelect').value,
    province: document.getElementById('editProvinceSelect').value,
    city: document.getElementById('editCitySelect').value,
    barangay: document.getElementById('editBarangaySelect').value,
    zipCode: document.getElementById('editZipCodeInput').value,
    deathCertificate: document.getElementById('editDeathCertificate').files[0]?.name || ''
  };

  // Validate required fields
  if (!formData.firstName || !formData.lastName || !formData.deceasedFirstName || 
      !formData.deceasedLastName || !formData.burialDate || !formData.service_id) {
    alert('Please fill in all required fields');
    return;
  }

  // Send data to server
  fetch('update_history_sales.php', {
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
  // Fetch current assignments if any and populate the form
  document.getElementById('assignServiceId').value = serviceId;
  
  // Reset checkboxes (in a real app, you would pre-select based on existing assignments)
  const checkboxes = document.querySelectorAll('#assignStaffForm input[type="checkbox"]');
  checkboxes.forEach(checkbox => checkbox.checked = false);
  
  document.getElementById('assignmentNotes').value = '';
  document.getElementById('assignStaffModal').style.display = 'flex';
  toggleBodyScroll(true);
}

// Function to close the Assign Staff Modal
function closeAssignStaffModal() {
  document.getElementById('assignStaffModal').style.display = 'none';
  toggleBodyScroll(false);
}

// Function to save staff assignments
function saveStaffAssignment() {
  const form = document.getElementById('assignStaffForm');
  const serviceId = document.getElementById('assignServiceId').value;
  
  // Collect selected staff
  const selectedStaff = [];
  const checkboxes = document.querySelectorAll('#assignStaffForm input[type="checkbox"]:checked');
  checkboxes.forEach(checkbox => {
    selectedStaff.push(checkbox.id);
  });
  
  // In a real application, you would save this data to your database
  console.log(`Assigned staff for service ${serviceId}:`, selectedStaff);
  alert(`Staff assigned successfully to service ${serviceId}!`);
  
  closeAssignStaffModal();
}

// Function to open the Complete Service Modal
function openCompleteModal(serviceId) {
  // Set service ID and default values
  document.getElementById('completeServiceId').value = serviceId;
  document.getElementById('completionDate').valueAsDate = new Date();
  document.getElementById('completionNotes').value = '';
  document.getElementById('finalBalanceSettled').checked = false;
  
  // Reset burial staff checkboxes
  const checkboxes = document.querySelectorAll('#completeServiceForm input[type="checkbox"]');
  checkboxes.forEach(checkbox => {
    if (checkbox.id !== 'finalBalanceSettled') {
      checkbox.checked = false;
    }
  });
  
  document.getElementById('completeServiceModal').style.display = 'flex';
  toggleBodyScroll(true);
}

// Function to close the Complete Service Modal
function closeCompleteModal() {
  document.getElementById('completeServiceModal').style.display = 'none';
  toggleBodyScroll(false);
}

// Function to finalize service completion
function finalizeServiceCompletion() {
  const form = document.getElementById('completeServiceForm');
  const serviceId = document.getElementById('completeServiceId').value;
  
  if (!document.getElementById('completionDate').value) {
    alert('Please specify a completion date.');
    return;
  }
  
  if (!document.getElementById('finalBalanceSettled').checked) {
    if (!confirm('The balance settlement has not been confirmed. Are you sure you want to mark this service as complete?')) {
      return;
    }
  }
  
  // Collect selected burial staff
  const selectedStaff = [];
  const checkboxes = document.querySelectorAll('#completeServiceForm input[type="checkbox"]:checked');
  checkboxes.forEach(checkbox => {
    if (checkbox.id !== 'finalBalanceSettled') {
      selectedStaff.push(checkbox.id);
    }
  });
  
  // In a real application, you would save this data to your database
  console.log(`Service ${serviceId} completed with burial staff:`, selectedStaff);
  alert(`Service ${serviceId} has been marked as complete!`);
  
  closeCompleteModal();
  
  // Update the status in the table (in a real app, you might refresh data from server)
  const tableRows = document.querySelectorAll('tbody tr');
  tableRows.forEach(row => {
    const idCell = row.querySelector('td:first-child');
    if (idCell && idCell.textContent.includes(serviceId)) {
      const statusCell = row.querySelector('td:nth-child(5) span');
      statusCell.className = 'px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-500';
      statusCell.textContent = 'Completed';
    }
  });
}

// Function to view service details (kept from original)
function viewServiceDetails(serviceId) {
  document.getElementById('serviceId').textContent = serviceId;
  document.getElementById('serviceClientName').textContent = 'John Doe';
  document.getElementById('serviceServiceType').textContent = 'Memorial Service';
  document.getElementById('serviceDate').textContent = '2023-10-15';
  document.getElementById('serviceStatus').textContent = 'Completed';
  document.getElementById('serviceOutstandingBalance').textContent = '₱0';

  document.getElementById('viewServiceModal').style.display = 'flex';
  toggleBodyScroll(true);
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
<script src="tailwind.js"></script>
<Script src="sidebar.js"></Script>
</body> 
</html>