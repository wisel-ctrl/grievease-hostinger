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
// Set pagination variables
$bookings_per_page = 10; // This is already set correctly
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
    <title>GrievEase - Bookings</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
  </div>

  <!-- Pending Bookings List -->
<div id="pending-bookings" class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
    <!-- Header Section - Made responsive with better stacking -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
        <!-- Desktop layout for big screens - Title on left, controls on right -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Title and Counter -->
            <div class="flex items-center gap-3 mb-4 lg:mb-0">
                <h3 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Pending Requests</h3>
                
                <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                    <i class="fas fa-list-ul"></i>
                    <span id="totalBookings">
                        <?php 
                          if ($total_bookings > 0) {
                              echo $total_bookings . ($total_bookings != 1 ? "" : "");
                          } else {
                              echo "No bookings";
                          }
                        ?>
                    </span>
                </span>
            </div>
            
            <!-- Controls for big screens - aligned right -->
            <!-- Controls for big screens - aligned right -->
<div class="hidden lg:flex items-center gap-3">
    <!-- Search Input -->
    <div class="relative">
        <input type="text" id="bookingSearchInput" 
               placeholder="Search bookings..." 
               class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
        <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
    </div>

    <!-- Status Dropdown Acting as Filter -->
    <div class="relative filter-dropdown">
        <button id="bookingFilterToggle" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
            <i class="fas fa-filter text-sidebar-accent"></i>
            <span>Filters</span>
            <span id="filterIndicator" class="hidden h-2 w-2 bg-sidebar-accent rounded-full"></span>
        </button>
        
        <!-- Filter Options Dropdown -->
        <div id="bookingFilterDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-2">
            <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
            <div class="space-y-1">
                <div class="flex items-center cursor-pointer filter-option" data-sort="id_asc">
                    <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        ID: Ascending
                    </span>
                </div>
                <div class="flex items-center cursor-pointer filter-option" data-sort="id_desc">
                    <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        ID: Descending
                    </span>
                </div>
                <div class="flex items-center cursor-pointer filter-option" data-sort="customer_asc">
                    <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        Customer: A-Z
                    </span>
                </div>
                <div class="flex items-center cursor-pointer filter-option" data-sort="customer_desc">
                    <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        Customer: Z-A
                    </span>
                </div>
                <div class="flex items-center cursor-pointer filter-option" data-sort="newest">
                    <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        Newest First
                    </span>
                </div>
                <div class="flex items-center cursor-pointer filter-option" data-sort="oldest">
                    <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                        Oldest First
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
        </div>
        
        <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
<div class="lg:hidden w-full mt-4">
    <!-- First row: Search bar with filter and archive icons on the right -->
    <div class="flex items-center w-full gap-3 mb-4">
        <!-- Search Input - Takes most of the space -->
        <div class="relative flex-grow">
            <input type="text" id="bookingSearchInputMobile" 
                   placeholder="Search bookings..." 
                   class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>

        <!-- Icon-only buttons for filter -->
        <div class="flex items-center gap-3">
            <!-- Filter Status Dropdown for Mobile -->
            <div class="relative filter-dropdown">
                <button id="bookingFilterToggleMobile" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
                    <i class="fas fa-filter text-xl"></i>
                    <span id="filterIndicatorMobile" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
                </button>
                
                <!-- Filter Options Dropdown -->
                <div id="bookingFilterDropdownMobile" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-2">
                    <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                    <div class="space-y-1">
                        <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="id_asc">
                            <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                ID: Ascending
                            </span>
                        </div>
                        <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="id_desc">
                            <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                ID: Descending
                            </span>
                        </div>
                        <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="customer_asc">
                            <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                Customer: A-Z
                            </span>
                        </div>
                        <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="customer_desc">
                            <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                Customer: Z-A
                            </span>
                        </div>
                        <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="newest">
                            <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                Newest First
                            </span>
                        </div>
                        <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="oldest">
                            <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                Oldest First
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
    
    <!-- Responsive Table Container with improved spacing -->
    <div class="overflow-x-auto scrollbar-thin" id="bookingTableContainer">
        <div id="loadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
        </div>
        
        <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
        <div class="min-w-full">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-sidebar-border">
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('booking_id')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-hashtag text-sidebar-accent"></i> Booking ID 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('customer')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-user text-sidebar-accent"></i> Customer 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('service')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-tag text-sidebar-accent"></i> Service 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('date')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-calendar text-sidebar-accent"></i> Date Requested 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('status')">
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
                <tbody id="bookingTableBody">
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
                                <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium"><?php echo htmlspecialchars($booking_id); ?></td>
                                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                <td class="px-4 py-3.5 text-sm text-sidebar-text">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        <?php echo htmlspecialchars($row['service_name']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($formatted_date); ?></td>
                                <td class="px-4 py-3.5 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $status_class; ?>">
                                        <i class="fas <?php echo $status_icon; ?> mr-1"></i> <?php echo htmlspecialchars($row['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-sm">
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
                            <td colspan="6" class="px-4 py-6 text-sm text-center">
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
    </div>
    
    <!-- Sticky Pagination Footer with improved spacing -->
<div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
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
    <div id="paginationContainer" class="flex space-x-1">
        <?php if ($total_pages > 1): ?>
            <!-- First page and Previous page -->
            <a href="<?php echo '?page=' . max(1, $current_page - 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">&laquo;</a>
            
            <?php
            // Determine the range of page numbers to show
            $range = 2; // Show 2 pages before and after the current page
            $start_page = max(1, $current_page - $range);
            $end_page = min($total_pages, $current_page + $range);
            
            // Always show first page
            if ($start_page > 1) {
                echo '<a href="?page=1" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">1</a>';
                if ($start_page > 2) {
                    echo '<span class="px-3.5 py-1.5 text-gray-500">...</span>';
                }
            }
            
            // Show page numbers
            for ($i = $start_page; $i <= $end_page; $i++) {
                $active_class = ($i == $current_page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
                echo '<a href="?page=' . $i . '" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</a>';
            }
            
            // Always show last page
            if ($end_page < $total_pages) {
                if ($end_page < $total_pages - 1) {
                    echo '<span class="px-3.5 py-1.5 text-gray-500">...</span>';
                }
                echo '<a href="?page=' . $total_pages . '" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">' . $total_pages . '</a>';
            }
            ?>
            
            <!-- Next page -->
            <a href="<?php echo '?page=' . min($total_pages, $current_page + 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">&raquo;</a>
        <?php endif; ?>
    </div>
</div>
</div>
 

<!-- Improved Booking Details Modal -->
<div id="bookingDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 id="modal-package-title" class="text-lg sm:text-xl font-bold text-white flex items-center">
        Booking Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <!-- Booking ID and Status Banner - Always in same row -->
      <div class="flex justify-between items-center mb-6 bg-gray-50 p-3 sm:p-4 rounded-lg">
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
              Pending
            </span>
          </div>
        </div>
      </div>
      
      <!-- Content Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
        <!-- Left Column -->
        <div class="space-y-3 sm:space-y-4">
          <!-- Customer Information -->
          <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
            <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
              Customer Information
            </h4>
            <div class="space-y-2 sm:space-y-3">
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Name</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="customerName">John Doe</div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Contact</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="contactNumber">(555) 123-4567</div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Email</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="emailAddress">john.doe@example.com</div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Address</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="address">123 Main St, Anytown, CA 12345</div>
              </div>
            </div>
          </div>

          <!-- Service Details -->
          <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
            <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
              Service Details
            </h4>
            <div class="space-y-2 sm:space-y-3">
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Service Type</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="serviceType">Funeral Service Package A</div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Date Requested</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="dateRequested">Mar 15, 2025</div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Service Date</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="serviceDate">Mar 20, 2025</div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Amount Paid</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="amountPaid">$3,500.00</div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Right Column -->
        <div class="space-y-3 sm:space-y-4">
          <!-- Documents -->
          <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
            <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
              Documents
            </h4>
            
            <!-- Death Certificate -->
            <div id="deathCertificateSection" class="mb-4 sm:mb-5">
              <h5 class="font-medium text-gray-700 mb-2 flex items-center">
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
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="confirmDecline()">
        Decline Booking
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="confirmAccept()">
        Accept Booking
      </button>
    </div>
  </div>
</div>

<!-- Decline Reason Modal -->
<div id="declineReasonModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 z-10 transform transition-all duration-300">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-sidebar-accent transition-colors" onclick="closeDeclineReasonModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-red-600 to-red-800 border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        Decline Booking
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5">
      <form id="declineReasonForm">
        <input type="hidden" id="bookingIdForDecline" name="bookingId">
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Declining</label>
          
          <!-- Suggested Reasons -->
          <div class="grid grid-cols-2 gap-2 mb-4">
            <button type="button" onclick="selectDeclineReason('Invalid payment certificate')" 
                    class="text-left p-2 border border-gray-300 rounded hover:bg-gray-100 text-sm"> Invalid payment certificate
            </button>
            <button type="button" onclick="selectDeclineReason('Incomplete documents')" 
                    class="text-left p-2 border border-gray-300 rounded hover:bg-gray-100 text-sm">Incomplete documents
            </button>
            <button type="button" onclick="selectDeclineReason('Unavailable service date')" 
                    class="text-left p-2 border border-gray-300 rounded hover:bg-gray-100 text-sm">Unavailable service date
            </button>
            <button type="button" onclick="selectDeclineReason('Service not available')" 
                    class="text-left p-2 border border-gray-300 rounded hover:bg-gray-100 text-sm">Service not available
            </button>
          </div>
          
          <!-- Custom Reason -->
          <div>
            <label for="customReason" class="block text-sm font-medium text-gray-700 mb-1">Or specify your own reason:</label>
            <textarea id="customReason" name="customReason" rows="3" 
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 py-2 px-3 border"
                      placeholder="Enter your reason for declining this booking..."></textarea>
          </div>
        </div>
        
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" onclick="closeDeclineReasonModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
            Cancel
          </button>
          <button type="submit" class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:shadow-md transition-all">
            Submit Decline
          </button>
        </div>
      </form>
    </div>
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
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
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
        
        <!-- Deceased Dates -->
        <input type="hidden" id="deceasedBirth" name="deceased_birth">
        <input type="hidden" id="deceasedDeath" name="deceased_dodeath">
        <input type="hidden" id="deceasedBurial" name="deceased_dateOfBurial">
       

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
            <option value="Cash">Cash</option>
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
      document.getElementById('amountPaid').textContent = "₱" + parseFloat(data.amount_paid).toFixed(2);
      
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
    fetch('bookingpage/getting_details_for_accept.php?id=' + bookingId)
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
            document.getElementById('deceasedMname').value = data.deceased_midname || '';
            document.getElementById('deceasedLname').value = data.deceased_lname || '';
            document.getElementById('deceasedSuffix').value = data.deceased_suffix || '';
            document.getElementById('deceasedAddress').value = data.deceased_address || '';

            // Dates information
            document.getElementById('deceasedBirth').value = data.deceased_birth || '';
            document.getElementById('deceasedDeath').value = data.deceased_dodeath || '';
            document.getElementById('deceasedBurial').value = data.deceased_dateOfBurial || '';            
            
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

function confirmDecline() {
    // Get the booking ID from the modal
    const bookingIdText = document.getElementById('bookingId').textContent;
    const bookingId = bookingIdText.split('-')[2].trim();
    
    // Set the booking ID in the decline form
    document.getElementById('bookingIdForDecline').value = bookingId;
    
    // Open the decline reason modal
    openDeclineReasonModal();
}

function openDeclineReasonModal() {
    const modal = document.getElementById("declineReasonModal");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    document.body.classList.add("overflow-hidden");
}

function closeDeclineReasonModal() {
    const modal = document.getElementById("declineReasonModal");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    document.body.classList.remove("overflow-hidden");
}

function selectDeclineReason(reason) {
    document.getElementById('customReason').value = reason;
}

// Handle decline form submission
document.getElementById('declineReasonForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const bookingId = document.getElementById('bookingIdForDecline').value;
    const customReason = document.getElementById('customReason').value;
    
    if (!customReason) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please provide a reason for declining this booking',
        });
        return;
    }
    
    Swal.fire({
        title: 'Confirm Decline',
        html: `<div class="text-left">
            <p>Are you sure you want to decline this booking?</p>
            <p><strong>Reason:</strong> ${customReason}</p>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Decline Booking',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Processing...',
                html: 'Please wait while we process your request',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            // Send the data via AJAX
            fetch('bookingpage/process_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=declineBooking&bookingId=${bookingId}&reason=${encodeURIComponent(customReason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Booking has been declined',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        closeDeclineReasonModal();
                        closeModal();
                        // Refresh the page to update the table
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to decline booking');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'An error occurred while processing your request',
                });
            });
        }
    });
});

// Handle payment form submission
document.getElementById('paymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Payment form submitted'); // Log form submission
    
    const amountPaid = document.getElementById('amountPaidInput').value;
    const paymentMethod = document.getElementById('paymentMethod').value;
    console.log('Amount Paid:', amountPaid, 'Payment Method:', paymentMethod); // Log input values
    
    if (!amountPaid || !paymentMethod) {
        console.log('Validation failed - missing fields'); // Log validation failure
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please fill in all payment details',
        });
        return;
    }
    
    // Prepare the data to send - include all hidden fields
    const formData = new FormData(this); // This will automatically include all form fields
    console.log('FormData entries:'); // Log FormData contents
    for (let [key, value] of formData.entries()) {
        console.log(key, value);
    }
    
    // Add additional data that might not be in the form
    formData.append('bookingId', currentBookingIdForPayment);
    formData.append('action', 'acceptBooking');
    console.log('Added additional fields - bookingId:', currentBookingIdForPayment, 'action: acceptBooking');
    
    // Calculate balance for display in confirmation
    const initialPrice = parseFloat(document.getElementById('initialPrice').value) || 0;
    const balance = initialPrice - parseFloat(amountPaid);
    const paymentStatus = balance <= 0 ? 'Fully Paid' : 'With Balance';
    console.log('Calculated values - Initial Price:', initialPrice, 'Balance:', balance, 'Status:', paymentStatus);
    
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
            console.log('User confirmed payment'); // Log user confirmation
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
            console.log('Sending AJAX request to bookingpage/process_booking.php'); // Log AJAX request
            fetch('bookingpage/process_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('Received response, status:', response.status); // Log response status
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data); // Log response data
                if (data.success) {
                    console.log('Payment processed successfully'); // Log success
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Booking accepted and sales record created successfully!',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        console.log('Closing modals and reloading page'); // Log final actions
                        closePaymentModal();
                        closeModal();
                        // Refresh the page to update the table
                        window.location.reload();
                    });
                } else {
                    console.error('Server reported error:', data.message || 'No error message provided'); // Log server error
                    throw new Error(data.message || 'Failed to process booking');
                }
            })
            .catch(error => {
                console.error('Error in payment processing:', error); // Enhanced error logging
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'An error occurred while processing your request',
                });
            });
        } else {
            console.log('User cancelled payment'); // Log cancellation
        }
    });
});
    </script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Mobile filter toggle
  const mobileFilterToggle = document.getElementById('bookingFilterToggleMobile');
  const mobileFilterDropdown = document.getElementById('bookingFilterDropdownMobile');
  
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
  
  // Desktop filter toggle
  const desktopFilterToggle = document.getElementById('bookingFilterToggle');
  const desktopFilterDropdown = document.getElementById('bookingFilterDropdown');
  
  if (desktopFilterToggle && desktopFilterDropdown) {
    desktopFilterToggle.addEventListener('click', function() {
      desktopFilterDropdown.classList.toggle('hidden');
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
      if (!desktopFilterToggle.contains(event.target) && !desktopFilterDropdown.contains(event.target)) {
        desktopFilterDropdown.classList.add('hidden');
      }
    });
  }
  
  // Handle search functionality
  const desktopSearchInput = document.getElementById('bookingSearchInput');
  const mobileSearchInput = document.getElementById('bookingSearchInputMobile');
  
  if (desktopSearchInput && mobileSearchInput) {
    desktopSearchInput.addEventListener('input', function() {
      mobileSearchInput.value = this.value;
      filterBookings();
    });
    
    mobileSearchInput.addEventListener('input', function() {
      desktopSearchInput.value = this.value;
      filterBookings();
    });
  }
  
  // Handle filter options (both mobile and desktop)
  document.querySelectorAll('.filter-option, .filter-option-mobile').forEach(option => {
    option.addEventListener('click', function() {
      const sortValue = this.getAttribute('data-sort');
      // Update UI to show active filter
      document.querySelectorAll('.filter-option, .filter-option-mobile').forEach(opt => {
        opt.classList.remove('bg-sidebar-hover');
      });
      this.classList.add('bg-sidebar-hover');
      
      // Show filter indicator
      document.getElementById('filterIndicator').classList.remove('hidden');
      document.getElementById('filterIndicatorMobile').classList.remove('hidden');
      
      // Apply sorting
      sortBookings(sortValue);
      
      // Close dropdowns
      mobileFilterDropdown.classList.add('hidden');
      desktopFilterDropdown.classList.add('hidden');
    });
  });
  
  // Function to filter bookings based on search input
  function filterBookings() {
    const searchValue = (desktopSearchInput.value || '').toLowerCase();
    const rows = document.querySelectorAll('#bookingTableBody tr');
    
    rows.forEach(row => {
      const customerCell = row.cells[1]?.textContent?.toLowerCase() || '';
      const serviceCell = row.cells[2]?.textContent?.toLowerCase() || '';
      const statusCell = row.cells[4]?.textContent?.toLowerCase() || '';
      
      const matchesSearch = customerCell.includes(searchValue) || 
                          serviceCell.includes(searchValue) || 
                          statusCell.includes(searchValue);
      
      row.style.display = matchesSearch ? '' : 'none';
    });
    
    // Update pagination info
    updatePaginationInfo();
  }
  
  // Function to sort bookings
  function sortBookings(sortValue) {
    const rows = Array.from(document.querySelectorAll('#bookingTableBody tr:not([style*="display: none"])'));
    const tbody = document.getElementById('bookingTableBody');
    
    // Clear the table
    tbody.innerHTML = '';
    
    // Sort the rows based on the selected option
    rows.sort((a, b) => {
      const aId = parseInt(a.cells[0].textContent.split('-')[2]);
      const bId = parseInt(b.cells[0].textContent.split('-')[2]);
      
      const aCustomer = a.cells[1].textContent.toLowerCase();
      const bCustomer = b.cells[1].textContent.toLowerCase();
      
      const aDate = new Date(a.cells[3].textContent);
      const bDate = new Date(b.cells[3].textContent);
      
      switch(sortValue) {
        case 'id_asc':
          return aId - bId;
        case 'id_desc':
          return bId - aId;
        case 'customer_asc':
          return aCustomer.localeCompare(bCustomer);
        case 'customer_desc':
          return bCustomer.localeCompare(aCustomer);
        case 'newest':
          return bDate - aDate;
        case 'oldest':
          return aDate - bDate;
        default:
          return 0;
      }
    });
    
    // Re-append the sorted rows
    rows.forEach(row => tbody.appendChild(row));
  }
  
  // Function to update pagination info after filtering
  function updatePaginationInfo() {
    const visibleRows = document.querySelectorAll('#bookingTableBody tr:not([style*="display: none"])').length;
    const totalRows = document.querySelectorAll('#bookingTableBody tr').length;
    
    document.getElementById('paginationInfo').textContent = 
      `Showing ${visibleRows} of ${totalRows} bookings`;
  }
  
  // Initialize the filter indicators based on URL params
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('sort')) {
    const sortValue = urlParams.get('sort');
    const activeOption = document.querySelector(`.filter-option[data-sort="${sortValue}"], .filter-option-mobile[data-sort="${sortValue}"]`);
    if (activeOption) {
      activeOption.classList.add('bg-sidebar-hover');
      document.getElementById('filterIndicator').classList.remove('hidden');
      document.getElementById('filterIndicatorMobile').classList.remove('hidden');
    }
  }
});
</script>
</body>
</html>