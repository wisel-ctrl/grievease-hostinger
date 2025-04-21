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

// Set pagination variables
$bookings_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Count total bookings with status "Pending"
$count_query = "SELECT COUNT(*) as total FROM booking_tb WHERE status = 'Pending'";
$count_result = $conn->query($count_query);
$total_bookings = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_bookings / $bookings_per_page);

// If current page is greater than total pages, reset to last page
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Calculate offset for SQL LIMIT clause
$offset = ($current_page - 1) * $bookings_per_page;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - GrievEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.4.8/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.4.8/sweetalert2.min.css">
</head>

</head>
<body class="flex bg-gray-50">

<?php include 'admin_sidebar.php'; ?>

<!-- Main Content -->
<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Bookings</h1>
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

  <!-- Pending Bookings List -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden branch-container">
    <!-- Header with Search -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3">
            <h4 class="text-lg font-bold text-sidebar-text">Pending Requests</h4>
            
            <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                <i class="fas fa-list-ul"></i>
                <?php 
                  if ($total_bookings > 0) {
                      echo $total_bookings . " booking" . ($total_bookings != 1 ? "s" : "");
                  } else {
                      echo "No bookings";
                  }
                ?>
            </span>
        </div>
        
        <!-- Search Section -->
        <div class="flex flex-col md:flex-row items-start md:items-center gap-3 w-full md:w-auto">
            <!-- Search Input -->
            <div class="relative w-full md:w-64">
                <input type="text" placeholder="Search bookings..." 
                       class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
            </div>
        </div>
    </div>
    
    <!-- Bookings Table -->
    <div class="overflow-x-auto scrollbar-thin">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-sidebar-border">
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">
                        <div class="flex items-center">
                            <i class="fas fa-hashtag mr-1.5 text-sidebar-accent"></i> Booking ID 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">
                        <div class="flex items-center">
                            <i class="fas fa-user mr-1.5 text-sidebar-accent"></i> Customer 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">
                        <div class="flex items-center">
                            <i class="fas fa-tag mr-1.5 text-sidebar-accent"></i> Service 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">
                        <div class="flex items-center">
                            <i class="fas fa-calendar mr-1.5 text-sidebar-accent"></i> Date Requested 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">
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
                // Query to get booking data with joins and pagination
                  $query = "SELECT b.booking_id, b.booking_date, b.status, 
                          CONCAT(u.first_name, ' ', COALESCE(u.middle_name, ''), ' ', u.last_name, ' ', COALESCE(u.suffix, '')) AS customer_name,
                          s.service_name
                          FROM booking_tb b
                          JOIN users u ON b.customerID = u.id
                          JOIN services_tb s ON b.service_id = s.service_id
                          WHERE b.status = 'Pending'
                          ORDER BY b.booking_date DESC
                          LIMIT ?, ?";

                  $stmt = $conn->prepare($query);
                  $stmt->bind_param("ii", $offset, $bookings_per_page);
                  $stmt->execute();
                  $result = $stmt->get_result();

                  if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                      // Format booking ID
                      $booking_id = "#BK-" . date('Y', strtotime($row['booking_date'])) . "-" . str_pad($row['booking_id'], 3, '0', STR_PAD_LEFT);

                      // Format date
                      $formatted_date = date('M j, Y', strtotime($row['booking_date']));

                      // Status badge class
                      $status_class = "bg-yellow-100 text-yellow-800 border border-yellow-200";
                      $status_icon = "fa-clock";
                      if ($row['status'] == 'Accepted') {
                        $status_class = "bg-green-100 text-green-600 border border-green-200";
                        $status_icon = "fa-check-circle";
                      } elseif ($row['status'] == 'Declined') {
                        $status_class = "bg-red-100 text-red-600 border border-red-200";
                        $status_icon = "fa-times-circle";
                      }
                ?>
                      <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                          <td class="p-4 text-sm text-sidebar-text font-medium"><?php echo htmlspecialchars($booking_id); ?></td>
                          <td class="p-4 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                          <td class="p-4 text-sm text-sidebar-text">
                              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                  <?php echo htmlspecialchars($row['service_name']); ?>
                              </span>
                          </td>
                          <td class="p-4 text-sm text-sidebar-text"><?php echo htmlspecialchars($formatted_date); ?></td>
                          <td class="p-4 text-sm">
                              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                  <i class="fas <?php echo $status_icon; ?> mr-1"></i> <?php echo htmlspecialchars($row['status']); ?>
                              </span>
                          </td>
                          <td class="p-4 text-sm">
                              <div class="flex space-x-2">
                                  <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" 
                                          title="View Details" 
                                          onclick="openBookingDetails(<?php echo $row['booking_id']; ?>)">
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
                        <td colspan="6" class="p-6 text-sm text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-500">No pending bookings found</p>
                            </div>
                        </td>
                    </tr>
                <?php
                  }
                ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
        <div class="text-sm text-gray-500">
            <?php 
            // Get the number of bookings on the current page
            $current_page_bookings = $result->num_rows;

            if ($total_bookings > 0) {
              $start = $offset + 1;
              $end = $offset + $result->num_rows;
          
                echo "Showing {$start} - {$end} of {$total_bookings} bookings";
            } else {
                echo "No bookings found";
            }
            ?>
        </div>
        <div class="flex space-x-1">
            <?php if ($total_pages > 1): ?>
                <!-- First page and Previous page -->
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                        onclick="changePage(1)" 
                        <?php echo ($current_page == 1) ? 'disabled' : ''; ?>>&laquo;</button>
                
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                        onclick="changePage(<?php echo max(1, $current_page - 1); ?>)" 
                        <?php echo ($current_page == 1) ? 'disabled' : ''; ?>>&lsaquo;</button>
                
                <!-- Page numbers -->
                <?php
                // Determine the range of page numbers to show
                $range = 2; // Show 2 pages before and after the current page
                $start_page = max(1, $current_page - $range);
                $end_page = min($total_pages, $current_page + $range);
                
                // Always show first page
                if ($start_page > 1) {
                    echo '<button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover" onclick="changePage(1)">1</button>';
                    if ($start_page > 2) {
                        echo '<span class="px-3 py-1 text-gray-500">...</span>';
                    }
                }
                
                // Show page numbers
                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active_class = ($i == $current_page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
                    echo '<button class="px-3 py-1 rounded text-sm ' . $active_class . '" onclick="changePage(' . $i . ')">' . $i . '</button>';
                }
                
                // Always show last page
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<span class="px-3 py-1 text-gray-500">...</span>';
                    }
                    echo '<button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover" onclick="changePage(' . $total_pages . ')">' . $total_pages . '</button>';
                }
                ?>
                
                <!-- Next page and Last page -->
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $total_pages) ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                        onclick="changePage(<?php echo min($total_pages, $current_page + 1); ?>)" 
                        <?php echo ($current_page == $total_pages) ? 'disabled' : ''; ?>>&rsaquo;</button>
                        
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $total_pages) ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                        onclick="changePage(<?php echo $total_pages; ?>)" 
                        <?php echo ($current_page == $total_pages) ? 'disabled' : ''; ?>>&raquo;</button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Improved Booking Details Modal -->
<div id="bookingDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center hidden" >
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 id="modal-package-title" class="text-xl font-bold text-white flex items-center">
        <i class="fas fa-info-circle mr-2"></i>
        Booking Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5">
      <!-- Booking ID and Status Banner -->
      <div class="flex justify-between items-center mb-6 bg-gray-50 p-4 rounded-lg">
        <div class="flex items-center">
          <div class="bg-navy rounded-full p-2 mr-3">
            <i class="fas fa-hashtag text-sidebar-accent"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">Booking ID</p>
            <p class="font-semibold text-gray-800" id="bookingId">#BK-2025-001</p>
          </div>
        </div>
        <div>
          <p class="text-sm text-gray-500 mb-1">Status</p>
          <div id="bookingStatus">
            <span class="px-3 py-1.5 text-sm font-medium rounded-full bg-yellow-100 text-sidebar-accent flex items-center">
              <i class="fas fa-clock mr-1.5"></i>
              Pending
            </span>
          </div>
        </div>
      </div>
      
      <!-- Content Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Left Column -->
        <div class="space-y-4">
          <!-- Customer Information -->
          <div class="bg-white rounded-lg p-5 border border-gray-200 shadow-sm">
            <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
              <i class="fas fa-user text-sidebar-accent mr-2"></i>
              Customer Information
            </h4>
            <div class="space-y-3">
              <div class="flex">
                <div class="w-1/3 text-sm text-gray-500">Name</div>
                <div class="w-2/3 font-medium text-gray-800" id="customerName">John Doe</div>
              </div>
              <div class="flex">
                <div class="w-1/3 text-sm text-gray-500">Contact</div>
                <div class="w-2/3 font-medium text-gray-800" id="contactNumber">(555) 123-4567</div>
              </div>
              <div class="flex">
                <div class="w-1/3 text-sm text-gray-500">Email</div>
                <div class="w-2/3 font-medium text-gray-800" id="emailAddress">john.doe@example.com</div>
              </div>
              <div class="flex">
                <div class="w-1/3 text-sm text-gray-500">Address</div>
                <div class="w-2/3 font-medium text-gray-800" id="address">123 Main St, Anytown, CA 12345</div>
              </div>
            </div>
          </div>

          <!-- Service Details -->
          <div class="bg-white rounded-lg p-5 border border-gray-200 shadow-sm">
            <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
              <i class="fas fa-hands-helping text-sidebar-accent mr-2"></i>
              Service Details
            </h4>
            <div class="space-y-3">
              <div class="flex">
                <div class="w-1/3 text-sm text-gray-500">Service Type</div>
                <div class="w-2/3 font-medium text-gray-800" id="serviceType">Funeral Service Package A</div>
              </div>
              <div class="flex">
                <div class="w-1/3 text-sm text-gray-500">Date Requested</div>
                <div class="w-2/3 font-medium text-gray-800" id="dateRequested">Mar 15, 2025</div>
              </div>
              <div class="flex">
                <div class="w-1/3 text-sm text-gray-500">Service Date</div>
                <div class="w-2/3 font-medium text-gray-800" id="serviceDate">Mar 20, 2025</div>
              </div>
              <div class="flex">
                <div class="w-1/3 text-sm text-gray-500">Amount Paid</div>
                <div class="w-2/3 font-medium text-gray-800" id="amountPaid">$3,500.00</div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Right Column -->
        <div class="space-y-4">
          <!-- Documents -->
          <div class="bg-white rounded-lg p-5 border border-gray-200 shadow-sm">
            <h4 class="font-semibold text-gray-800 mb-4 flex items-center">
              <i class="fas fa-file-alt text-sidebar-accent mr-2"></i>
              Documents
            </h4>
            
            <!-- Death Certificate -->
            <div id="deathCertificateSection" class="mb-5">
              <h5 class="font-medium text-gray-700 mb-2 flex items-center">
                <i class="fas fa-certificate text-gray-500 mr-2"></i>
                Death Certificate
              </h5>
              <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div id="deathCertificateAvailable" class="text-center">
                  <div class="relative bg-gray-100 p-1">
                    <img id="deathCertificateImage" alt="Death Certificate" class="mx-auto rounded-md max-h-48 object-contain" />
                    <div class="absolute top-2 right-2">
                      <button class="bg-white rounded-full p-1 shadow-md hover:bg-gray-100 transition-colors duration-200" title="View Full Size">
                        <i class="fas fa-search-plus text-blue-600"></i>
                      </button>
                    </div>
                  </div>
                </div>
                <div id="deathCertificateNotAvailable" class="hidden">
                  <div class="flex flex-col items-center justify-center py-8 px-4 bg-gray-50">
                    <i class="fas fa-exclamation-circle text-gray-400 text-3xl mb-2"></i>
                    <p class="text-gray-500 text-center">No death certificate has been uploaded yet.</p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Payment Proof -->
            <div>
              <h5 class="font-medium text-gray-700 mb-2 flex items-center">
                <i class="fas fa-receipt text-gray-500 mr-2"></i>
                Payment Proof
              </h5>
              <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="relative bg-gray-100 p-1">
                  <img id="paymentProofImage" alt="Payment Proof" class="mx-auto rounded-md max-h-48 object-contain" />
                  <div class="absolute top-2 right-2">
                    <button class="bg-white rounded-full p-1 shadow-md hover:bg-gray-100 transition-colors duration-200" title="View Full Size">
                      <i class="fas fa-search-plus text-blue-600"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-6 py-4 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center" onclick="confirmDecline()">
        <i class="fas fa-times-circle mr-2"></i>
        Decline Booking
      </button>
      <button class="px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="confirmAccept()">
        <i class="fas fa-check-circle mr-2"></i>
        Accept Booking
      </button>
    </div>
  </div>

  <!-- Payment Modal -->
<div id="paymentModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 z-10 transform transition-all duration-300">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-sidebar-accent transition-colors" onclick="closePaymentModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        <i class="fas fa-money-bill-wave mr-2"></i>
        Payment Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5">
    <form id="paymentForm">
        <!-- Booking Information -->
        <input type="hidden" id="bookingIdForPayment" name="bookingId">
        
        <!-- Customer Information -->
        <input type="hidden" id="customerFirstName" name="first_name">
        <input type="hidden" id="customerMiddleName" name="middle_name">
        <input type="hidden" id="customerLastName" name="last_name">
        <input type="hidden" id="customerSuffix" name="suffix">
        <input type="hidden" id="customerEmail" name="email">
        <input type="hidden" id="customerPhone" name="phone_number">
        
        <!-- Deceased Information -->
        <input type="hidden" id="deceasedFname" name="deceased_fname">
        <input type="hidden" id="deceasedMname" name="deceased_mname">
        <input type="hidden" id="deceasedLname" name="deceased_lname">
        <input type="hidden" id="deceasedSuffix" name="deceased_suffix">
        <input type="hidden" id="deceasedAddress" name="deceased_address">
        
        <!-- Service Information -->
        <input type="hidden" id="serviceId" name="service_id">
        <input type="hidden" id="branchId" name="branch_id">
        <input type="hidden" id="initialPrice" name="initial_price">
        <input type="hidden" id="deathCertUrl" name="deathcert_url">
        <input type="hidden" id="withCremate" name="with_cremate">
        
        <div class="mb-4">
          <label for="amountPaidInput" class="block text-sm font-medium text-gray-700 mb-1">Amount Paid</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" step="0.01" id="amountPaidInput" name="amountPaid" 
                   class="pl-8 block w-full rounded-md border-gray-300 shadow-sm focus:border-sidebar-accent focus:ring focus:ring-sidebar-accent focus:ring-opacity-50 py-2 px-3 border" 
                   placeholder="0.00" required>
          </div>
        </div>
        
        <div class="mb-4">
          <label for="paymentMethod" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
          <select id="paymentMethod" name="paymentMethod" 
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-sidebar-accent focus:ring focus:ring-sidebar-accent focus:ring-opacity-50 py-2 px-3 border" required>
            <option value="">Select payment method</option>
            <option value="Bank">Bank Transfer</option>
            <option value="GCash">GCash</option>
            <option value="PayMaya">PayMaya</option>
            <option value="Cash">Cash</option>
            <option value="Others">Others</option>
          </select>
        </div>
        
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" onclick="closePaymentModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
            Cancel
          </button>
          <button type="submit" class="px-4 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg hover:shadow-md transition-all">
            Confirm Payment
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

</div>


    <script src="script.js"></script>
    <script src="tailwind.js"></script>

    <script>
      let currentBookingIdForPayment = null;

    function openBookingDetails(bookingId) {
  // First, fetch booking details via AJAX
  fetch('bookingpage/get_booking_details.php?id=' + bookingId)
    .then(response => response.json())
    .then(data => {
      // Populate modal with the basic details
      document.getElementById('bookingId').textContent = '#BK-' + 
        new Date(data.booking_date).getFullYear() + '-' + 
        String(data.booking_id).padStart(3, '0');
      document.getElementById('customerName').textContent = data.customer_name;
      document.getElementById('contactNumber').textContent = data.contact_number || "Not provided";
      document.getElementById('emailAddress').textContent = data.email;
      document.getElementById('address').textContent = data.address || "Not provided";
      document.getElementById('serviceType').textContent = data.service_name;
      document.getElementById('dateRequested').textContent = 
        new Date(data.booking_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
      document.getElementById('serviceDate').textContent = 
        data.deceased_dateOfBurial ? new Date(data.deceased_dateOfBurial).toLocaleDateString('en-US', 
        { month: 'short', day: 'numeric', year: 'numeric' }) : "Not scheduled";
      document.getElementById('amountPaid').textContent = "$" + parseFloat(data.amount_paid).toFixed(2);
      
      // Update booking status
      const statusElement = document.getElementById('bookingStatus');
      if (data.status === 'Accepted') {
        statusElement.innerHTML = `
          <span class="px-3 py-1.5 text-sm font-medium rounded-full bg-green-100 text-green-800 flex items-center">
            <i class="fas fa-check-circle mr-1.5"></i>
            Accepted
          </span>`;
      } else if (data.status === 'Declined') {
        statusElement.innerHTML = `
          <span class="px-3 py-1.5 text-sm font-medium rounded-full bg-red-100 text-red-800 flex items-center">
            <i class="fas fa-times-circle mr-1.5"></i>
            Declined
          </span>`;
      } else {
        statusElement.innerHTML = `
          <span class="px-3 py-1.5 text-sm font-medium rounded-full bg-yellow-100 text-sidebar-accent flex items-center">
            <i class="fas fa-clock mr-1.5"></i>
            Pending
          </span>`;
      }
      
      // Debug the data received
      console.log("Death Certificate URL:", data.deathcert_url);
      console.log("Payment Proof URL:", data.payment_url);
      
      // Handle Death Certificate Image
      const deathCertAvailable = document.getElementById('deathCertificateAvailable');
      const deathCertNotAvailable = document.getElementById('deathCertificateNotAvailable');
      const deathCertImage = document.getElementById('deathCertificateImage');

      if (data.deathcert_url && data.deathcert_url !== '') {
        // Use relative path with ../ to navigate up and then to the customer/booking/uploads folder
        const deathCertPath = '../customer/booking/uploads/' + data.deathcert_url.replace(/^uploads\//, '');
        console.log("Death Certificate Path:", deathCertPath);
        
        // Set error handler before setting src
        deathCertImage.onerror = function() {
          console.error("Failed to load death certificate image:", deathCertPath);
          deathCertAvailable.classList.add('hidden');
          deathCertNotAvailable.classList.remove('hidden');
        };
        
        deathCertImage.src = deathCertPath;
        deathCertAvailable.classList.remove('hidden');
        deathCertNotAvailable.classList.add('hidden');
      } else {
        deathCertAvailable.classList.add('hidden');
        deathCertNotAvailable.classList.remove('hidden');
      }

      // Handle Payment Proof Image
      const paymentProofImage = document.getElementById('paymentProofImage');
      const paymentProofContainer = paymentProofImage.parentElement;

      if (data.payment_url && data.payment_url !== '') {
        // Use relative path with ../ to navigate up and then to the customer/booking/uploads folder
        const paymentProofPath = '../customer/booking/uploads/' + data.payment_url.replace(/^uploads\//, '');
        console.log("Payment Proof Path:", paymentProofPath);
        
        // Set error handler before setting src
        paymentProofImage.onerror = function() {
          console.error("Failed to load payment proof image:", paymentProofPath);
          // Create a placeholder instead of loading another image
          const placeholderHTML = `
            <div class="flex flex-col items-center justify-center py-8 px-4 bg-gray-50">
              <i class="fas fa-exclamation-circle text-gray-400 text-3xl mb-2"></i>
              <p class="text-gray-500 text-center">Image could not be loaded</p>
            </div>`;
          paymentProofContainer.innerHTML = placeholderHTML;
        };
        
        paymentProofImage.src = paymentProofPath;
      } else {
        // Create a placeholder for when no payment proof exists
        const placeholderHTML = `
          <div class="flex flex-col items-center justify-center py-8 px-4 bg-gray-50">
            <i class="fas fa-exclamation-circle text-gray-400 text-3xl mb-2"></i>
            <p class="text-gray-500 text-center">No payment proof provided</p>
          </div>`;
        paymentProofContainer.innerHTML = placeholderHTML;
      }
            
      // Show the modal
      const modal = document.getElementById("bookingDetailsModal");
      modal.classList.remove("hidden");
      modal.classList.add("flex");
      document.body.classList.add("overflow-hidden");
      
      // Add image viewer listeners
      setTimeout(addImageViewerListeners, 100); // Small delay to ensure DOM is updated
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Failed to load booking details. Please try again.');
    });
}

// Function to add click handlers to the magnify buttons
function addImageViewerListeners() {
  const zoomButtons = document.querySelectorAll('#bookingDetailsModal button[title="View Full Size"]');
  zoomButtons.forEach(button => {
    button.addEventListener('click', function() {
      const imageElement = this.closest('.relative').querySelector('img');
      if (imageElement && imageElement.src) {
        viewFullSizeImage(imageElement.src);
      }
    });
  });
}

// Function to show full-size image
function viewFullSizeImage(imageSrc) {
  const fullSizeModal = document.createElement('div');
  fullSizeModal.className = 'fixed inset-0 bg-black bg-opacity-90 z-[60] flex items-center justify-center p-4';
  
  fullSizeModal.innerHTML = `
    <div class="relative max-w-4xl w-full">
      <img src="${imageSrc}" class="max-w-full max-h-[80vh] object-contain mx-auto" />
      <button class="absolute top-4 right-4 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white transition-all duration-200">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
  `;
  
  document.body.appendChild(fullSizeModal);
  
  // Close on click
  fullSizeModal.addEventListener('click', function(e) {
    if (e.target === fullSizeModal || e.target.closest('button')) {
      document.body.removeChild(fullSizeModal);
    }
  });
}

function closeModal() {
    const modal = document.getElementById("bookingDetailsModal");
    if (modal) {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        document.body.classList.remove("overflow-hidden");
    }
}

// Close modal when clicking outside content
window.addEventListener("click", function (event) {
    const modal = document.getElementById("bookingDetailsModal");
    if (modal && event.target === modal) {
        closeModal();
    }
});

// Close modal on 'Escape' key press
window.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        closeModal();
    }
});

function confirmAccept() {
    // Get the booking ID from the modal
    const bookingIdText = document.getElementById('bookingId').textContent;
    const bookingId = bookingIdText.split('-')[2].trim();
    currentBookingIdForPayment = bookingId;
    
    // Fetch booking details again to get all the required fields
    fetch('bookingpage/get_booking_details.php?id=' + bookingId)
        .then(response => response.json())
        .then(data => {
            // Set all the hidden fields
            document.getElementById('bookingIdForPayment').value = bookingId;
            
            // Customer information
            document.getElementById('customerFirstName').value = data.first_name || '';
            document.getElementById('customerMiddleName').value = data.middle_name || '';
            document.getElementById('customerLastName').value = data.last_name || '';
            document.getElementById('customerSuffix').value = data.suffix || '';
            document.getElementById('customerEmail').value = data.email || '';
            document.getElementById('customerPhone').value = data.phone_number || '';
            
            // Deceased information
            document.getElementById('deceasedFname').value = data.deceased_fname || '';
            document.getElementById('deceasedMname').value = data.deceased_mname || '';
            document.getElementById('deceasedLname').value = data.deceased_lname || '';
            document.getElementById('deceasedSuffix').value = data.deceased_suffix || '';
            document.getElementById('deceasedAddress').value = data.deceased_address || '';
            
            // Service information
            document.getElementById('serviceId').value = data.service_id || '';
            document.getElementById('branchId').value = data.branch_id || '';
            document.getElementById('initialPrice').value = data.initial_price || '';
            document.getElementById('deathCertUrl').value = data.deathcert_url || '';
            document.getElementById('withCremate').value = data.with_cremate || 'no';
            
            
            
            // Show the payment modal
            openPaymentModal();
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load booking details',
            });
        });
}

function openPaymentModal() {
    const modal = document.getElementById("paymentModal");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    document.body.classList.add("overflow-hidden");
}

function closePaymentModal() {
    const modal = document.getElementById("paymentModal");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    document.body.classList.remove("overflow-hidden");
}

// Handle payment form submission
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const amountPaid = document.getElementById('amountPaidInput').value;
    const paymentMethod = document.getElementById('paymentMethod').value;
    
    if (!amountPaid || !paymentMethod) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please fill in all payment details',
        });
        return;
    }
    
    // Prepare the data to send - include all hidden fields
    const formData = new FormData(this); // This will automatically include all form fields
    
    // Add additional data that might not be in the form
    formData.append('bookingId', currentBookingIdForPayment);
    formData.append('action', 'acceptBooking');
    
    // Calculate balance for display in confirmation
    const initialPrice = parseFloat(document.getElementById('initialPrice').value) || 0;
    const balance = initialPrice - parseFloat(amountPaid);
    const paymentStatus = balance <= 0 ? 'Fully Paid' : 'With Balance';
    
    // Show confirmation dialog with payment summary
    Swal.fire({
        title: 'Confirm Payment Details',
        html: `<div class="text-left">
            <p><strong>Amount Paid:</strong> ₱${parseFloat(amountPaid).toFixed(2)}</p>
            <p><strong>Payment Method:</strong> ${paymentMethod}</p>
            <p><strong>Initial Price:</strong> ₱${initialPrice.toFixed(2)}</p>
            <p><strong>Balance:</strong> ₱${balance.toFixed(2)}</p>
            <p><strong>Payment Status:</strong> ${paymentStatus}</p>
        </div>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Confirm Payment',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Processing...',
                html: 'Please wait while we process your payment',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send the data via AJAX
            fetch('bookingpage/process_booking.php', {
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
                        text: 'Booking accepted and sales record created successfully!',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        closePaymentModal();
                        closeModal();
                        // Refresh the page to update the table
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to process booking');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'An error occurred while processing your request',
                });
            });
        }
    });
});
    </script>
</body>
</html>