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

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>History - GrievEase</title>
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
    <div class="flex space-x-3">
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-bell"></i>
      </button>
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-cog"></i>
      </button>
    </div>
  </div>

  <!-- Ongoing Services Section -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div class="flex items-center gap-3">
      <h4 class="text-lg font-bold text-sidebar-text">Ongoing Services</h4>
        
      <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
        <i class="fas fa-clipboard-list"></i>
        <?php 
          $ongoingCount = $ongoingResult->num_rows;
          echo $ongoingCount . " Service" . ($ongoingCount != 1 ? "s" : ""); 
        ?>
      </span>
    </div>
      
    <!-- Search Section -->
    <div class="flex flex-col md:flex-row items-start md:items-center gap-3 w-full md:w-auto">
      <!-- Search Input -->
      <div class="relative w-full md:w-64">
        <input type="text" id="searchOngoing" 
               placeholder="Search services..." 
               class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
        <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
      </div>
    </div>
  </div>
    
  <!-- Services Table -->
  <div class="overflow-x-auto scrollbar-thin">      
    <table class="w-full">
      <thead>
        <tr class="bg-gray-50 border-b border-sidebar-border">
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">
            <div class="flex items-center">
              <i class="fas fa-hashtag mr-1.5 text-sidebar-accent"></i> ID 
              <i class="fas fa-sort ml-1 text-gray-400"></i>
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">
            <div class="flex items-center">
              <i class="fas fa-user mr-1.5 text-sidebar-accent"></i> Client
              <i class="fas fa-sort ml-1 text-gray-400"></i>
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">
            <div class="flex items-center">
              <i class="fas fa-user-alt mr-1.5 text-sidebar-accent"></i> Deceased
              <i class="fas fa-sort ml-1 text-gray-400"></i>
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">
            <div class="flex items-center">
              <i class="fas fa-tag mr-1.5 text-sidebar-accent"></i> Service Type 
              <i class="fas fa-sort ml-1 text-gray-400"></i>
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">
            <div class="flex items-center">
              <i class="fas fa-calendar mr-1.5 text-sidebar-accent"></i> Date of Burial 
              <i class="fas fa-sort ml-1 text-gray-400"></i>
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(5)">
            <div class="flex items-center">
              <i class="fas fa-toggle-on mr-1.5 text-sidebar-accent"></i> Status 
              <i class="fas fa-sort ml-1 text-gray-400"></i>
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(6)">
            <div class="flex items-center">
              <i class="fas fa-peso-sign mr-1.5 text-sidebar-accent"></i> Outstanding Balance 
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
      <tbody>
        <?php
        // Query for Ongoing Services (status = 'Pending')
        // Modify your ongoingQuery to include a check for assigned staff
        $ongoingQuery = "SELECT s.sales_id, s.fname, s.mname, s.lname, s.suffix, 
        s.fname_deceased, s.mname_deceased, s.lname_deceased, s.suffix_deceased,
        sv.service_name, s.date_of_burial, s.balance, s.status, s.customerID, s.payment_status,
        (SELECT COUNT(*) FROM employee_service_payments esp WHERE esp.sales_id = s.sales_id) AS staff_assigned
        FROM sales_tb s
        JOIN services_tb sv ON s.service_id = sv.service_id
        WHERE s.status = 'Pending'";
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
              <td class="p-4 text-sm text-sidebar-text font-medium">#<?php echo $row['sales_id']; ?></td>
              <td class="p-4 text-sm text-sidebar-text"><?php echo $clientName; ?></td>
              <td class="p-4 text-sm text-sidebar-text"><?php echo $deceasedName; ?></td>
              <td class="p-4 text-sm text-sidebar-text">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                  <?php echo htmlspecialchars($row['service_name']); ?>
                </span>
              </td>
              <td class="p-4 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['date_of_burial']); ?></td>
              <td class="p-4 text-sm">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-500 border border-orange-200">
                  <i class="fas fa-pause-circle mr-1"></i> <?php echo htmlspecialchars($row['status']); ?>
                </span>
              </td>
              <td class="p-4 text-sm font-medium text-sidebar-text">₱<?php echo number_format($row['balance'], 2); ?></td>
              <td class="p-4 text-sm">
                <div class="flex space-x-2">
                  <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="Edit Service" onclick="openEditServiceModal('<?php echo $row['sales_id']; ?>')">
                    <i class="fas fa-edit"></i>
                  </button>
                  <?php if ($row['staff_assigned'] == 0): ?>
                    <button class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip assign-staff-btn" 
                            title="Assign Staff"
                            onclick="checkCustomerBeforeAssign('<?php echo $row['sales_id']; ?>', <?php echo $row['customerID'] ? 'true' : 'false'; ?>)"
                            <?php echo !$row['customerID'] ? 'disabled' : ''; ?>>
                      <i class="fas fa-users"></i>
                    </button>
                  <?php endif; ?>
                  <button class="p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 transition-all tooltip complete-btn" 
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
      
    <!-- Pagination placeholder - can be dynamically populated if needed -->
    <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
      <div class="text-sm text-gray-500">
        Showing 1 - <?php echo $ongoingResult->num_rows; ?> of <?php echo $ongoingResult->num_rows; ?> services
      </div>
      <!-- Add pagination controls here if needed -->
    </div>
  </div>
</div>

  <!-- Past Services - Fully Paid Section -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div class="flex items-center gap-3">
      <h4 class="text-lg font-bold text-sidebar-text">Past Services - Fully Paid</h4>
      
      <?php
      // Count total fully paid services
      $countQuery = "SELECT COUNT(*) as total FROM sales_tb WHERE status = 'Completed' AND payment_status = 'Fully Paid' AND balance = 0";
      $countResult = $conn->query($countQuery);
      $totalServices = $countResult->fetch_assoc()['total'];
      ?>
      
      <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
        <i class="fas fa-clipboard-list"></i>
        <?php echo $totalServices . " Service" . ($totalServices != 1 ? "s" : ""); ?>
      </span>
    </div>
    
    <!-- Search and Filter Section -->
    <div class="flex flex-col md:flex-row items-start md:items-center gap-3 w-full md:w-auto">
      <!-- Search Input -->
      <div class="relative w-full md:w-64">
        <input type="text" id="searchFullyPaid" 
               placeholder="Search services..." 
               class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
               oninput="debouncedFilterFullyPaid()">
        <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
      </div>
    </div>
  </div>
  
  <!-- Services Table for fully paid section -->
  <div class="overflow-x-auto scrollbar-thin" id="fullyPaidTableContainer">
    <div id="loadingIndicatorFullyPaid" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
      <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
    </div>
    
    <table class="w-full">
      <thead>
        <tr class="bg-gray-50 border-b border-sidebar-border">
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">
            <div class="flex items-center">
              <i class="fas fa-hashtag mr-1.5 text-sidebar-accent"></i> ID 
              <i class="fas fa-sort ml-1 text-gray-400"></i>
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">
            <div class="flex items-center">
              <i class="fas fa-user mr-1.5 text-sidebar-accent"></i> Client
              <i class="fas fa-sort ml-1 text-gray-400"></i>
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">
            <div class="flex items-center">
              <i class="fas fa-user-alt mr-1.5 text-sidebar-accent"></i> Deceased
              <i class="fas fa-sort ml-1 text-gray-400"></i>
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">
            <div class="flex items-center">
              <i class="fas fa-tag mr-1.5 text-sidebar-accent"></i> Service Type 
              <i class="fas fa-sort ml-1 text-gray-400"></i>
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">
            <div class="flex items-center">
              <i class="fas fa-calendar mr-1.5 text-sidebar-accent"></i> Date of Burial 
              <i class="fas fa-sort ml-1 text-gray-400"></i>
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(5)">
            <div class="flex items-center">
              <i class="fas fa-toggle-on mr-1.5 text-sidebar-accent"></i> Status 
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
      <tbody>
        <?php
        // Query for Past Services - Fully Paid (status = 'Completed' AND payment_status = 'Fully Paid' AND balance = 0)
        $fullyPaidQuery = "SELECT s.sales_id, s.fname, s.mname, s.lname, s.suffix, 
                          s.fname_deceased, s.mname_deceased, s.lname_deceased, s.suffix_deceased,
                          sv.service_name, s.date_of_burial, s.balance, s.status, s.payment_status
                          FROM sales_tb s
                          JOIN services_tb sv ON s.service_id = sv.service_id
                          WHERE s.status = 'Completed' AND s.payment_status = 'Fully Paid' AND s.balance = 0";
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
              <td class="p-4 text-sm text-sidebar-text font-medium">#<?php echo $row['sales_id']; ?></td>
              <td class="p-4 text-sm text-sidebar-text"><?php echo $clientName; ?></td>
              <td class="p-4 text-sm text-sidebar-text"><?php echo $deceasedName; ?></td>
              <td class="p-4 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['service_name']); ?></td>
              <td class="p-4 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['date_of_burial']); ?></td>
              <td class="p-4 text-sm">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-100 text-green-500 border border-green-200">
                  <i class="fas fa-check-circle mr-1"></i> <?php echo htmlspecialchars($row['status']); ?>
                </span>
              </td>
              <td class="p-4 text-sm">
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
            <td colspan="7" class="p-6 text-sm text-center">
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
    
    <!-- Pagination (you can add actual pagination logic here if needed) -->
    <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
      <div class="text-sm text-gray-500">
        Showing <?php echo min($fullyPaidResult->num_rows, 1); ?> - <?php echo $fullyPaidResult->num_rows; ?> 
        of <?php echo $totalServices; ?> services
      </div>
      <div class="flex space-x-1">
        <!-- Add pagination buttons here if needed -->
        <!-- This is a placeholder. You'll need to implement pagination logic similar to the first code -->
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm bg-sidebar-accent text-white">1</button>
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
          <tr class="bg-sidebar-hover">
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">ID</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">Client Name</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">Deceased Name</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">Service Type</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">Date of Burial</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(5)">Status</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(6)">Outstanding Balance</th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Query for Past Services - With Balance (status = 'Completed' AND payment_status = 'With Balance')
          $withBalanceQuery = "SELECT s.sales_id, s.fname, s.mname, s.lname, s.suffix, 
                              s.fname_deceased, s.mname_deceased, s.lname_deceased, s.suffix_deceased,
                              sv.service_name, s.date_of_burial, s.balance, s.status, s.payment_status
                              FROM sales_tb s
                              JOIN services_tb sv ON s.service_id = sv.service_id
                              WHERE s.status = 'Completed' AND s.payment_status = 'With Balance'";
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
              <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
                <td class="p-4 text-sm text-sidebar-text">#<?php echo $row['sales_id']; ?></td>
                <td class="p-4 text-sm text-sidebar-text"><?php echo $clientName; ?></td>
                <td class="p-4 text-sm text-sidebar-text"><?php echo $deceasedName; ?></td>
                <td class="p-4 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['service_name']); ?></td>
                <td class="p-4 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['date_of_burial']); ?></td>
                <td class="p-4 text-sm">
                  <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-500"><?php echo htmlspecialchars($row['payment_status']); ?></span>
                </td>
                <td class="p-4 text-sm text-sidebar-text">₱<?php echo number_format($row['balance'], 2); ?></td>
                <td class="p-4 text-sm">
                  <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all" onclick="viewServiceDetails('<?php echo $row['sales_id']; ?>')">
                    <i class="fas fa-eye"></i>
                  </button>
                  <button class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all" onclick="openRecordPaymentModal('<?php echo $row['sales_id']; ?>','<?php echo $clientName; ?>','<?php echo $row['balance']; ?>')">
                    <i class="fas fa-money-bill-wave"></i>
                  </button>
                </td>
              </tr>
              <?php
            }
          } else {
            ?>
            <tr>
              <td colspan="8" class="p-4 text-sm text-center text-sidebar-text">No past services with outstanding balance found</td>
            </tr>
            <?php
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
        </div>

  <!-- Modal for Editing Service -->
<div class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="editServiceModal">
  <div class="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 class="text-xl font-bold text-white flex items-center">
        <i class="fas fa-edit mr-3 text-white"></i>
        Edit Service
      </h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeEditServiceModal()">
        <i class="fas fa-times text-xl"></i>
      </button>
    </div>
    
    <form id="serviceForm" class="p-6 space-y-6">
      <!-- Customer and Service Selection -->
      <div class="grid md:grid-cols-2 gap-4">
      <input type="hidden" id="salesId" name="sales_id">
      <div>
        <label class="block text-sm font-medium text-gray-700 flex items-center">
          <i class="fas fa-user mr-2 text-sidebar-accent"></i>
          Search Customer
        </label>
        <div class="relative">
          <input 
            type="text" 
            id="customerSearch" 
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
            placeholder="Type customer name..."
            autocomplete="off"
          >
          <div id="customerResults" class="absolute z-10 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto hidden">
            <!-- Results will appear here -->
          </div>
        </div>
        <input type="hidden" id="selectedCustomerId" name="customer_id">
      </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 flex items-center">
            <i class="fas fa-briefcase mr-2 text-sidebar-accent"></i>
            Select Service
          </label>
          <select 
            id="serviceSelect" 
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
          >
            <option value="">Choose Service</option>
            <!-- Options will be populated dynamically -->
          </select>
        </div>
      </div>

      <!-- Service Price -->
      <div>
        <label class="block text-sm font-medium text-gray-700 flex items-center">
          <i class="fas fa-dollar-sign mr-2 text-sidebar-accent"></i>
          Service Price
        </label>
        <input 
          type="number" 
          id="servicePrice" 
          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
          placeholder="Enter Service Price"
        >
      </div>

      <!-- Name Fields - Now in 2 columns -->
      <div class="grid md:grid-cols-2 gap-4">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 flex items-center">
              <i class="fas fa-user mr-2 text-sidebar-accent"></i>
              First Name
            </label>
            <input 
              type="text" 
              id="firstName" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
              placeholder="First Name"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 flex items-center">
              <i class="fas fa-user mr-2 text-sidebar-accent"></i>
              Last Name
            </label>
            <input 
              type="text" 
              id="lastName" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
              placeholder="Last Name"
            >
          </div>
        </div>
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 flex items-center">
              <i class="fas fa-user mr-2 text-sidebar-accent"></i>
              Middle Name
            </label>
            <input 
              type="text" 
              id="middleName" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
              placeholder="Middle Name"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 flex items-center">
              <i class="fas fa-user mr-2 text-sidebar-accent"></i>
              Suffix
            </label>
            <input 
              type="text" 
              id="nameSuffix" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
              placeholder="Suffix"
            >
          </div>
        </div>
      </div>

      <!-- Contact Information -->
      <div class="grid md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 flex items-center">
            <i class="fas fa-envelope mr-2 text-sidebar-accent"></i>
            Email
          </label>
          <input 
            type="email" 
            id="email" 
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
            placeholder="Enter Email"
          >
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 flex items-center">
            <i class="fas fa-phone mr-2 text-sidebar-accent"></i>
            Phone
          </label>
          <input 
            type="tel" 
            id="phone" 
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
            placeholder="Enter Phone Number"
          >
        </div>
      </div>

      <!-- Deceased Information -->
      <div class="space-y-4">
        <h4 class="text-lg font-semibold flex items-center">
          <i class="fas fa-file-medical mr-2 text-sidebar-accent"></i>
          Deceased Information
        </h4>
        
        <!-- Deceased Name - Now in 2 columns -->
        <div class="grid md:grid-cols-2 gap-4">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 flex items-center">
                <i class="fas fa-user mr-2 text-sidebar-accent"></i>
                First Name
              </label>
              <input 
                type="text" 
                id="deceasedFirstName" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                placeholder="First Name"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 flex items-center">
                <i class="fas fa-user mr-2 text-sidebar-accent"></i>
                Last Name
              </label>
              <input 
                type="text" 
                id="deceasedLastName" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                placeholder="Last Name"
              >
            </div>
          </div>
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 flex items-center">
                <i class="fas fa-user mr-2 text-sidebar-accent"></i>
                Middle Name
              </label>
              <input 
                type="text" 
                id="deceasedMiddleName" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                placeholder="Middle Name"
              >
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 flex items-center">
                <i class="fas fa-user mr-2 text-sidebar-accent"></i>
                Suffix
              </label>
              <input 
                type="text" 
                id="deceasedSuffix" 
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
                placeholder="Suffix"
              >
            </div>
          </div>
        </div>

        <!-- Deceased Dates -->
        <div class="grid md:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 flex items-center">
              <i class="fas fa-calendar-alt mr-2 text-sidebar-accent"></i>
              Birth Date
            </label>
            <input 
              type="date" 
              id="birthDate" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 flex items-center">
              <i class="fas fa-calendar-times mr-2 text-sidebar-accent"></i>
              Date of Death
            </label>
            <input 
              type="date" 
              id="deathDate" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
            >
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 flex items-center">
              <i class="fas fa-calendar-check mr-2 text-sidebar-accent"></i>
              Date of Burial/Cremation
            </label>
            <input 
              type="date" 
              id="burialDate" 
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
            >
          </div>
        </div>

        <!-- Deceased Address -->
        <div>
          <label class="block text-sm font-medium text-gray-700 flex items-center">
            <i class="fas fa-map-marker-alt mr-2 text-sidebar-accent"></i>
            Deceased Address
          </label>
          <input 
            type="text" 
            id="deceasedAddress" 
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200"
            placeholder="Enter Deceased Address"
          >
        </div>

        <!-- Branch Selection -->
        <div>
          <label class="block text-sm font-medium text-gray-700 flex items-center">
            <i class="fas fa-building mr-2 text-sidebar-accent"></i>
            Select Branch
          </label>
          <div class="mt-2 space-x-4">
            <label class="inline-flex items-center">
              <input 
                type="radio" 
                name="branch" 
                value="1" 
                class="form-radio h-4 w-4 text-sidebar-accent"
              >
              <span class="ml-2">Pila</span>
            </label>
            <label class="inline-flex items-center">
              <input 
                type="radio" 
                name="branch" 
                value="2" 
                class="form-radio h-4 w-4 text-sidebar-accent"
              >
              <span class="ml-2">Paete</span>
            </label>
          </div>
        </div>

        <!-- Death Certificate Upload -->
        <div>
          <label class="block text-sm font-medium text-gray-700 flex items-center">
            <i class="fas fa-file-upload mr-2 text-sidebar-accent"></i>
            Death Certificate
          </label>
          <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
            <div class="space-y-1 text-center">
              <i class="fas fa-cloud-upload-alt text-4xl text-sidebar-accent mb-4"></i>
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
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeEditServiceModal()">
        <i class="fas fa-times mr-2 text-sidebar-accent"></i>
        Cancel
      </button>
      <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors" onclick="saveServiceChanges()">
        <i class="fas fa-save mr-2"></i>
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
            
            echo '<div class="bg-gray-50 p-5 rounded-xl">';
            echo '<h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center">';
            echo $icon . ucfirst($positionLower) . 's';
            echo '</h4>';
            echo '<div class="grid grid-cols-2 gap-4">';
            
            if (!empty($employees)) {
                $count = 1;
                foreach ($employees as $employee) {
                    $fullName = htmlspecialchars($employee['fname'] . ' ' . $employee['mname'] . ' ' . $employee['lname']);
                    $employeeId = htmlspecialchars($employee['employee_id']);
                    
                    echo '<div class="flex items-center">';
                    echo '<input type="checkbox" id="'.$positionLower.$count.'" name="assigned_staff[]" value="'.$employeeId.'" class="mr-2">';
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
        <div id="embalmersSection" class="bg-gray-50 p-5 rounded-xl">
          <!-- Embalmers will be loaded here -->
        </div>
        
        <div id="driversSection" class="bg-gray-50 p-5 rounded-xl">
          <!-- Drivers will be loaded here -->
        </div>
        
        <div id="personnelSection" class="bg-gray-50 p-5 rounded-xl">
          <!-- Personnel will be loaded here -->
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
        
        <!-- Drivers Section -->
        <div id="completeDriversSection" class="bg-gray-50 p-5 rounded-xl">
          <h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <rect x="1" y="3" width="15" height="13"></rect>
              <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon>
              <circle cx="5.5" cy="18.5" r="2.5"></circle>
              <circle cx="18.5" cy="18.5" r="2.5"></circle>
            </svg>
            Drivers
          </h4>
          <div class="grid grid-cols-2 gap-4" id="completeDriversList">
            <!-- Drivers will be populated here -->
          </div>
        </div>
        
        <!-- Personnel Section -->
        <div id="completePersonnelSection" class="bg-gray-50 p-5 rounded-xl">
          <h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Personnel
          </h4>
          <div class="grid grid-cols-2 gap-4" id="completePersonnelList">
            <!-- Personnel will be populated here -->
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

<div class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="viewServiceModal">
  <div class="bg-white rounded-xl w-full max-w-lg max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 class="text-xl font-bold text-white">Service Details</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeViewServiceModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    
    <!-- Modal Body -->
    <div class="p-6">
      <div class="bg-gray-50 p-5 rounded-xl">
        <div class="space-y-3">
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">ID:</span>
            <span id="serviceId" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Client Name:</span>
            <span id="serviceClientName" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Service Type:</span>
            <span id="serviceServiceType" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Branch:</span>
            <span id="branchName" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Date:</span>
            <span id="serviceDate" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Status:</span>
            <span id="serviceStatus" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Outstanding Balance:</span>
            <span id="serviceOutstandingBalance" class="text-gray-800 font-bold text-sidebar-accent"></span>
          </p>
        </div>
      </div>
      
      <!-- Initial Staff Section -->
      <div class="bg-gray-50 p-5 rounded-xl mt-4">
        <h4 class="font-semibold text-gray-800 mb-3">Initial Staff</h4>
        <div class="space-y-3">
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Date:</span>
            <span id="initialDate" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Embalmers:</span>
            <span id="initialEmbalmers" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Drivers:</span>
            <span id="initialDrivers" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Personnel:</span>
            <span id="initialPersonnel" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between items-start">
            <span class="font-medium text-gray-700">Notes:</span>
            <span id="initialNotes" class="text-gray-800 text-right"></span>
          </p>
        </div>
      </div>
      
      <!-- Burial Staff Section -->
      <div class="bg-gray-50 p-5 rounded-xl mt-4">
        <h4 class="font-semibold text-gray-800 mb-3">Burial Staff</h4>
        <div class="space-y-3">
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Date:</span>
            <span id="burialDate1" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Drivers:</span>
            <span id="burialDrivers" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between">
            <span class="font-medium text-gray-700">Personnel:</span>
            <span id="burialPersonnel" class="text-gray-800"></span>
          </p>
          <p class="flex justify-between items-start">
            <span class="font-medium text-gray-700">Notes:</span>
            <span id="burialNotes" class="text-gray-800 text-right"></span>
          </p>
        </div>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeViewServiceModal()">Close</button>
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
        <input type="hidden" id="customerID" name="customerID">
        <input type="hidden" id="branchID" name="branchID">
          <!-- Service ID -->
          <div class="bg-gray-50 p-5 rounded-xl border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-1" for="paymentServiceId">Sales ID</label>
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
              <option value="G  Cash">G-Cash</option>
              <option value="Credit Card">Credit Card</option>
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
            <i class="fas fa-money-bill-wave mr-2 text-sidebar-accent"></i>
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
        document.getElementById('deceasedAddress').value = data.deceased_address || '';
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
    deceasedAddress: document.getElementById('deceasedAddress').value,
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
</body> 
</html>