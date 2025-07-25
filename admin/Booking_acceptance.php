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

// Set pagination variables
// Set pagination variables
$bookings_per_page = 5; // This is already set correctly
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

// Count total bookings with status "Pending"
$count_query = "SELECT COUNT(*) as total FROM booking_tb WHERE status = 'Pending' AND service_id IS NOT NULL";
$count_result = $conn->query($count_query);
$total_bookings = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_bookings / $bookings_per_page);

// If current page is greater than total pages, reset to last page
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

// Calculate offset for SQL LIMIT clause
$offset = ($current_page - 1) * $bookings_per_page;


// Custom bookings pagination
$custom_bookings_per_page = 5;
$custom_current_page = isset($_GET['custom_page']) ? (int)$_GET['custom_page'] : 1;
if ($custom_current_page < 1) $custom_current_page = 1;

// Count total custom bookings
$custom_count_query = "SELECT COUNT(*) as total FROM booking_tb WHERE status = 'Pending' AND service_id IS NULL";
$custom_count_result = $conn->query($custom_count_query);
$total_custom_bookings = $custom_count_result->fetch_assoc()['total'];
$total_custom_pages = ceil($total_custom_bookings / $custom_bookings_per_page);

if ($custom_current_page > $total_custom_pages && $total_custom_pages > 0) {
    $custom_current_page = $total_custom_pages;
}

$custom_offset = ($custom_current_page - 1) * $custom_bookings_per_page;


// Add these near the other pagination variables at the top
$lifeplan_bookings_per_page = 5; // Set items per page
$lifeplan_current_page = isset($_GET['lifeplan_page']) ? (int)$_GET['lifeplan_page'] : 1;
if ($lifeplan_current_page < 1) $lifeplan_current_page = 1;

// Update the count query to match your actual query conditions
$lifeplan_count_query = "SELECT COUNT(*) as total FROM lifeplan_booking_tb WHERE booking_status = 'pending'";
$lifeplan_count_result = $conn->query($lifeplan_count_query);
$total_lifeplan_bookings = $lifeplan_count_result->fetch_assoc()['total'];
$total_lifeplan_pages = ceil($total_lifeplan_bookings / $lifeplan_bookings_per_page);

if ($lifeplan_current_page > $total_lifeplan_pages && $total_lifeplan_pages > 0) {
    $lifeplan_current_page = $total_lifeplan_pages;
}

$lifeplan_offset = ($lifeplan_current_page - 1) * $lifeplan_bookings_per_page;


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

#customBookingNotes {
  white-space: pre-wrap; /* Preserves line breaks in notes */
  word-break: break-word; /* Prevents long words from overflowing */
}

#imageEnlargerOverlay {
    z-index: 9999; /* Ensure it's above everything else */
  }
  
  #modalPaymentProofImage {
    transition: transform 0.2s;
  }
  
  #modalPaymentProofImage:hover {
    transform: scale(1.01);
  }
    </style>
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
                    CONCAT(
                        UPPER(LEFT(u.first_name, 1)), LOWER(SUBSTRING(u.first_name, 2)), ' ',
                        UPPER(LEFT(COALESCE(u.middle_name, ''), 1)), LOWER(SUBSTRING(COALESCE(u.middle_name, ''), 2)), ' ',
                        UPPER(LEFT(u.last_name, 1)), LOWER(SUBSTRING(u.last_name, 2)), ' ',
                        UPPER(LEFT(COALESCE(u.suffix, ''), 1)), LOWER(SUBSTRING(COALESCE(u.suffix, ''), 2))
                    ) AS customer_name,
                    COALESCE(s.service_name, 'Custom Package') AS service_name
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
        if ($total_bookings > 0) {
            $start = $offset + 1;
            $end = $offset + $result->num_rows;
            echo "Showing {$start} - {$end} of {$total_bookings} bookings";
        } else {
            echo "No bookings found";
        }
        ?>
    </div>
    <div id="paginationContainer" class="flex space-x-2">
        <?php if ($total_pages > 1): ?>
            <!-- First page button (double arrow) -->
            <button onclick="loadBookings(1)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &laquo;
            </button>
            
            <!-- Previous page button (single arrow) -->
            <button onclick="loadBookings(<?php echo max(1, $current_page - 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &lsaquo;
            </button>
            
            <?php
            // Show exactly 3 page numbers
            if ($total_pages <= 3) {
                $start_page = 1;
                $end_page = $total_pages;
            } else {
                if ($current_page == 1) {
                    $start_page = 1;
                    $end_page = 3;
                } elseif ($current_page == $total_pages) {
                    $start_page = $total_pages - 2;
                    $end_page = $total_pages;
                } else {
                    $start_page = $current_page - 1;
                    $end_page = $current_page + 1;
                    
                    if ($start_page < 1) {
                        $start_page = 1;
                        $end_page = 3;
                    }
                    if ($end_page > $total_pages) {
                        $end_page = $total_pages;
                        $start_page = $total_pages - 2;
                    }
                }
            }
            
            for ($i = $start_page; $i <= $end_page; $i++) {
                $active_class = ($i == $current_page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
                echo '<button onclick="loadBookings(' . $i . ')" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</button>';
            }
            ?>
            
            <!-- Next page button (single arrow) -->
            <button onclick="loadBookings(<?php echo min($total_pages, $current_page + 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &rsaquo;
            </button>
            
            <!-- Last page button (double arrow) -->
            <button onclick="loadBookings(<?php echo $total_pages; ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &raquo;
            </button>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- Custom Bookings List -->
<div id="custom-bookings" class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
    <!-- Header Section - Made responsive with better stacking -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
        <!-- Desktop layout for big screens - Title on left, controls on right -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Title and Counter -->
            <div class="flex items-center gap-3 mb-4 lg:mb-0">
                <h3 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Custom Bookings</h3>
                
                <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                <span id="totalCustomBookings">
    <?php 
    if ($total_custom_bookings > 0) {
        echo $total_custom_bookings . ($total_custom_bookings != 1 ? "" : "");
    } else {
        echo "No bookings";
    }
    ?>
</span>
                </span>
            </div>
            
            <!-- Controls for big screens - aligned right -->
            <div class="hidden lg:flex items-center gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <input type="text" id="customBookingSearchInput" 
                           placeholder="Search custom bookings..." 
                           class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                    <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
                </div>

                <!-- Status Dropdown Acting as Filter -->
                <div class="relative filter-dropdown">
                    <button id="customBookingFilterToggle" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
                        <i class="fas fa-filter text-sidebar-accent"></i>
                        <span>Filters</span>
                        <span id="customFilterIndicator" class="hidden h-2 w-2 bg-sidebar-accent rounded-full"></span>
                    </button>
                    
                    <!-- Filter Options Dropdown -->
                    <div id="customBookingFilterDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-2">
                        <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                        <div class="space-y-1">
                            <div class="flex items-center cursor-pointer custom-filter-option" data-sort="id_asc">
                                <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                    ID: Ascending
                                </span>
                            </div>
                            <div class="flex items-center cursor-pointer custom-filter-option" data-sort="id_desc">
                                <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                    ID: Descending
                                </span>
                            </div>
                            <div class="flex items-center cursor-pointer custom-filter-option" data-sort="customer_asc">
                                <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                    Customer: A-Z
                                </span>
                            </div>
                            <div class="flex items-center cursor-pointer custom-filter-option" data-sort="customer_desc">
                                <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                    Customer: Z-A
                                </span>
                            </div>
                            <div class="flex items-center cursor-pointer custom-filter-option" data-sort="newest">
                                <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                    Newest First
                                </span>
                            </div>
                            <div class="flex items-center cursor-pointer custom-filter-option" data-sort="oldest">
                                <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                    Oldest First
                                </span>
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
                        <input type="text" id="customBookingSearchInputMobile" 
                               placeholder="Search custom bookings..." 
                               class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>

                    <!-- Icon-only buttons for filter -->
                    <div class="flex items-center gap-3">
                        <!-- Filter Status Dropdown for Mobile -->
                        <div class="relative filter-dropdown">
                            <button id="customBookingFilterToggleMobile" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
                                <i class="fas fa-filter text-xl"></i>
                                <span id="customFilterIndicatorMobile" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
                            </button>
                            
                            <!-- Filter Options Dropdown -->
                            <div id="customBookingFilterDropdownMobile" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-2">
                                <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                                <div class="space-y-1">
                                    <div class="flex items-center cursor-pointer custom-filter-option-mobile" data-sort="id_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            ID: Ascending
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer custom-filter-option-mobile" data-sort="id_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            ID: Descending
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer custom-filter-option-mobile" data-sort="customer_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Customer: A-Z
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer custom-filter-option-mobile" data-sort="customer_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Customer: Z-A
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer custom-filter-option-mobile" data-sort="newest">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Newest First
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer custom-filter-option-mobile" data-sort="oldest">
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
    <div class="overflow-x-auto scrollbar-thin" id="customBookingTableContainer">
        <div id="customLoadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
        </div>
        
        <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
        <div class="min-w-full">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-sidebar-border">
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomTable('booking_id')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-hashtag text-sidebar-accent"></i> Booking ID 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomTable('customer')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-user text-sidebar-accent"></i> Customer 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomTable('package')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-box text-sidebar-accent"></i> Package Price
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomTable('date')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-calendar text-sidebar-accent"></i> Date Requested 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomTable('status')">
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
                <tbody id="customBookingTableBody">
                    <!-- This section will be populated when you add your data -->
                    <?php
                    // Query to get custom booking data (where service_id is NULL)
                    $custom_query = "SELECT b.booking_id, b.booking_date, b.status, b.initial_price,
                                    CONCAT(
                                        UPPER(LEFT(u.first_name, 1)), LOWER(SUBSTRING(u.first_name, 2)), ' ',
                                        UPPER(LEFT(COALESCE(u.middle_name, ''), 1)), LOWER(SUBSTRING(COALESCE(u.middle_name, ''), 2)), ' ',
                                        UPPER(LEFT(u.last_name, 1)), LOWER(SUBSTRING(u.last_name, 2)), ' ',
                                        UPPER(LEFT(COALESCE(u.suffix, ''), 1)), LOWER(SUBSTRING(COALESCE(u.suffix, ''), 2))
                                    ) AS customer_name
                                    FROM booking_tb b
                                    JOIN users u ON b.customerID = u.id
                                    WHERE b.service_id IS NULL AND b.status = 'Pending'
                                    ORDER BY b.booking_date DESC
                                    LIMIT ?, ?";

                    $custom_stmt = $conn->prepare($custom_query);
                    $custom_stmt->bind_param("ii", $custom_offset, $custom_bookings_per_page);
                    $custom_stmt->execute();
                    $custom_result = $custom_stmt->get_result();

                    if ($custom_result->num_rows > 0) {
                        while ($row = $custom_result->fetch_assoc()) {
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
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-100">
                                        ₱<?php echo number_format($row['initial_price'], 2); ?>
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
                                                onclick="openCustomDetails(<?php echo $row['booking_id']; ?>)">
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
                                    <p class="text-gray-500">No custom bookings found</p>
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
    <div id="customPaginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
        <?php 
        if ($total_custom_bookings > 0) {
            $custom_start = $custom_offset + 1;
            $custom_end = $custom_offset + $custom_result->num_rows;
            echo "Showing {$custom_start} - {$custom_end} of {$total_custom_bookings} " . ($total_custom_bookings != 1 ? "bookings" : "booking");
        } else {
            echo "No custom bookings found";
        }
        ?>
    </div>
    <div id="customPaginationContainer" class="flex space-x-2">
        <?php if ($total_custom_pages > 1): ?>
            <!-- First page button (double arrow) -->
            <button onclick="loadCustomBookings(1)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($custom_current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &laquo;
            </button>
            
            <!-- Previous page button (single arrow) -->
            <button onclick="loadCustomBookings(<?php echo max(1, $custom_current_page - 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($custom_current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &lsaquo;
            </button>
            
            <?php
            // Show exactly 3 page numbers
            if ($total_custom_pages <= 3) {
                $custom_start_page = 1;
                $custom_end_page = $total_custom_pages;
            } else {
                if ($custom_current_page == 1) {
                    $custom_start_page = 1;
                    $custom_end_page = 3;
                } elseif ($custom_current_page == $total_custom_pages) {
                    $custom_start_page = $total_custom_pages - 2;
                    $custom_end_page = $total_custom_pages;
                } else {
                    $custom_start_page = $custom_current_page - 1;
                    $custom_end_page = $custom_current_page + 1;
                    
                    if ($custom_start_page < 1) {
                        $custom_start_page = 1;
                        $custom_end_page = 3;
                    }
                    if ($custom_end_page > $total_custom_pages) {
                        $custom_end_page = $total_custom_pages;
                        $custom_start_page = $total_custom_pages - 2;
                    }
                }
            }
            
            for ($i = $custom_start_page; $i <= $custom_end_page; $i++) {
                $active_class = ($i == $custom_current_page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
                echo '<button onclick="loadCustomBookings(' . $i . ')" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</button>';
            }
            ?>
            
            <!-- Next page button (single arrow) -->
            <button onclick="loadCustomBookings(<?php echo min($total_custom_pages, $custom_current_page + 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($custom_current_page == $total_custom_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &rsaquo;
            </button>
            
            <!-- Last page button (double arrow) -->
            <button onclick="loadCustomBookings(<?php echo $total_custom_pages; ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($custom_current_page == $total_custom_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &raquo;
            </button>
        <?php endif; ?>
    </div>
</div>
</div>


<!-- LifePlan Bookings List -->
<div id="lifeplan-bookings" class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
    <!-- Header Section - Made responsive with better stacking -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
        <!-- Desktop layout for big screens - Title on left, controls on right -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Title and Counter -->
            <div class="flex items-center gap-3 mb-4 lg:mb-0">
                <h3 class="text-lg font-bold text-sidebar-text whitespace-nowrap">LifePlan Bookings</h3>
                
                <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
    <span id="totalLifeplanBookings">
        <?php 
        if ($total_lifeplan_bookings > 0) {
            echo $total_lifeplan_bookings . ($total_lifeplan_bookings != 1 ? "" : "");
        } else {
            echo "No bookings";
        }
        ?>
    </span>
</span>
            </div>
            
            <!-- Controls for big screens - aligned right -->
            <div class="hidden lg:flex items-center gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <input type="text" id="lifeplanBookingSearchInput" 
                           placeholder="Search lifeplan bookings..." 
                           class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                    <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
                </div>

                <!-- Status Dropdown Acting as Filter -->
                <div class="relative filter-dropdown">
                    <button id="lifeplanBookingFilterToggle" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
                        <i class="fas fa-filter text-sidebar-accent"></i>
                        <span>Filters</span>
                        <span id="lifeplanFilterIndicator" class="hidden h-2 w-2 bg-sidebar-accent rounded-full"></span>
                    </button>
                    
                    <!-- Filter Options Dropdown -->
                    <div id="lifeplanBookingFilterDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-2">
                        <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                        <div class="space-y-1">
                            <div class="flex items-center cursor-pointer lifeplan-filter-option" data-sort="id_asc">
                                <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                    ID: Ascending
                                </span>
                            </div>
                            <div class="flex items-center cursor-pointer lifeplan-filter-option" data-sort="id_desc">
                                <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                    ID: Descending
                                </span>
                            </div>
                            <div class="flex items-center cursor-pointer lifeplan-filter-option" data-sort="customer_asc">
                                <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                    Customer: A-Z
                                </span>
                            </div>
                            <div class="flex items-center cursor-pointer lifeplan-filter-option" data-sort="customer_desc">
                                <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                    Customer: Z-A
                                </span>
                            </div>
                            <div class="flex items-center cursor-pointer lifeplan-filter-option" data-sort="newest">
                                <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                    Newest First
                                </span>
                            </div>
                            <div class="flex items-center cursor-pointer lifeplan-filter-option" data-sort="oldest">
                                <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                    Oldest First
                                </span>
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
                        <input type="text" id="lifeplanBookingSearchInputMobile" 
                               placeholder="Search lifeplan bookings..." 
                               class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>

                    <!-- Icon-only buttons for filter -->
                    <div class="flex items-center gap-3">
                        <!-- Filter Status Dropdown for Mobile -->
                        <div class="relative filter-dropdown">
                            <button id="lifeplanBookingFilterToggleMobile" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
                                <i class="fas fa-filter text-xl"></i>
                                <span id="lifeplanFilterIndicatorMobile" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
                            </button>
                            
                            <!-- Filter Options Dropdown -->
                            <div id="lifeplanBookingFilterDropdownMobile" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-2">
                                <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                                <div class="space-y-1">
                                    <div class="flex items-center cursor-pointer lifeplan-filter-option-mobile" data-sort="id_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            ID: Ascending
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer lifeplan-filter-option-mobile" data-sort="id_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            ID: Descending
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer lifeplan-filter-option-mobile" data-sort="customer_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Customer: A-Z
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer lifeplan-filter-option-mobile" data-sort="customer_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Customer: Z-A
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer lifeplan-filter-option-mobile" data-sort="newest">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Newest First
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer lifeplan-filter-option-mobile" data-sort="oldest">
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
    <div class="overflow-x-auto scrollbar-thin" id="lifeplanBookingTableContainer">
        <div id="lifeplanLoadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
        </div>
        
        <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
        <div class="min-w-full">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-sidebar-border">
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortLifeplanTable('booking_id')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-hashtag text-sidebar-accent"></i> Booking ID 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortLifeplanTable('customer')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-user text-sidebar-accent"></i> Customer 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortLifeplanTable('plan_type')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-heart text-sidebar-accent"></i> Plan Type
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortLifeplanTable('date')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-calendar text-sidebar-accent"></i> Date Requested 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortCustomTable('package')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-box text-sidebar-accent"></i> Package Price
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortLifeplanTable('status')">
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
<?php
// Ensure these variables are defined before the table
$lifeplan_bookings_per_page = 5;
$lifeplan_current_page = isset($_GET['lifeplan_page']) ? max(1, (int)$_GET['lifeplan_page']) : 1;
$lifeplan_offset = ($lifeplan_current_page - 1) * $lifeplan_bookings_per_page;

// Count total bookings for pagination
$total_lifeplan_query = "SELECT COUNT(*) as total 
                        FROM lifeplan_booking_tb 
                        WHERE booking_status = 'pending'";
$total_lifeplan_stmt = $conn->prepare($total_lifeplan_query);
$total_lifeplan_stmt->execute();
$total_lifeplan_result = $total_lifeplan_stmt->get_result();
$total_lifeplan_bookings = $total_lifeplan_result->fetch_assoc()['total'];
$total_lifeplan_pages = ceil($total_lifeplan_bookings / $lifeplan_bookings_per_page);
?>

<tbody id="lifeplanBookingTableBody">
    <?php
    // Initial query for first page load
    $lifeplanQuery = "SELECT lb.*, 
        CONCAT(
            UPPER(LEFT(u.first_name, 1)), LOWER(SUBSTRING(u.first_name, 2)), ' ',
            UPPER(LEFT(COALESCE(u.middle_name, ''), 1)), LOWER(SUBSTRING(COALESCE(u.middle_name, ''), 2)), ' ',
            UPPER(LEFT(u.last_name, 1)), LOWER(SUBSTRING(u.last_name, 2)), ' ',
            UPPER(LEFT(COALESCE(u.suffix, ''), 1)), LOWER(SUBSTRING(COALESCE(u.suffix, ''), 2))
        ) AS customer_name,
        s.service_name
        FROM lifeplan_booking_tb lb
        JOIN users u ON lb.customer_id = u.id
        LEFT JOIN services_tb s ON lb.service_id = s.service_id
        WHERE lb.booking_status = 'pending'
        ORDER BY lb.lpbooking_id DESC
        LIMIT ?, ?";

    $lifeplan_stmt = $conn->prepare($lifeplanQuery);
    $lifeplan_stmt->bind_param("ii", $lifeplan_offset, $lifeplan_bookings_per_page);
    $lifeplan_stmt->execute();
    $lifeplanResult = $lifeplan_stmt->get_result();
    
    if ($lifeplanResult->num_rows > 0) {
        while ($row = $lifeplanResult->fetch_assoc()) {
            $bookingId = "#LP-" . date('Y') . "-" . str_pad($row['lpbooking_id'], 3, '0', STR_PAD_LEFT);
            $statusClass = "bg-yellow-100 text-yellow-800 border border-yellow-200";
            $statusIcon = "fa-clock";
            if ($row['booking_status'] == 'Confirmed') {
                $statusClass = "bg-green-100 text-green-600 border border-green-200";
                $statusIcon = "fa-check-circle";
            } elseif ($row['booking_status'] == 'Cancelled') {
                $statusClass = "bg-red-100 text-red-600 border border-red-200";
                $statusIcon = "fa-times-circle";
            }
    ?>
            <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium"><?php echo htmlspecialchars($bookingId); ?></td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['customer_name']); ?></td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-100">
                        <?php echo htmlspecialchars($row['service_name'] ?: 'Custom LifePlan'); ?>
                    </span>
                </td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row['initial_date']); ?></td>
                <td class="px-4 py-3.5 text-sm text-sidebar-text">₱<?php echo number_format($row['package_price'], 2); ?></td>
                <td class="px-4 py-3.5 text-sm">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                        <i class="fas <?php echo $statusIcon; ?> mr-1"></i> <?php echo htmlspecialchars($row['booking_status']); ?>
                    </span>
                </td>
                <td class="px-4 py-3.5 text-sm">
                    <div class="flex space-x-2">
                        <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" 
                                title="View Details" 
                                onclick="openLifeplanDetails(<?php echo $row['lpbooking_id']; ?>)">
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
                    <p class="text-gray-500">No lifeplan bookings found</p>
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
    
    <!-- Replace the existing lifeplan pagination footer with this: -->
<div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="lifeplanPaginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
        <?php 
        $current_page_lifeplan_bookings = $lifeplanResult->num_rows;
        if ($total_lifeplan_bookings > 0) {
            $lifeplan_start = $lifeplan_offset + 1;
            $lifeplan_end = $lifeplan_offset + $current_page_lifeplan_bookings;
            echo "Showing {$lifeplan_start} - {$lifeplan_end} of {$total_lifeplan_bookings} lifeplan bookings";
        } else {
            echo "No lifeplan bookings found";
        }
        ?>
    </div>
    <div id="lifeplanPaginationContainer" class="flex space-x-2">
        <?php if ($total_lifeplan_pages > 1): ?>
            <!-- First page button (double arrow) -->
            <button onclick="loadLifeplanBookings(1)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($lifeplan_current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                «
            </button>
            
            <!-- Previous page button (single arrow) -->
            <button onclick="loadLifeplanBookings(<?php echo max(1, $lifeplan_current_page - 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($lifeplan_current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                ‹
            </button>
            
            <?php
            if ($total_lifeplan_pages <= 3) {
                $lifeplan_start_page = 1;
                $lifeplan_end_page = $total_lifeplan_pages;
            } else {
                if ($lifeplan_current_page == 1) {
                    $lifeplan_start_page = 1;
                    $lifeplan_end_page = 3;
                } elseif ($lifeplan_current_page == $total_lifeplan_pages) {
                    $lifeplan_start_page = $total_lifeplan_pages - 2;
                    $lifeplan_end_page = $total_lifeplan_pages;
                } else {
                    $lifeplan_start_page = $lifeplan_current_page - 1;
                    $lifeplan_end_page = $lifeplan_current_page + 1;
                    if ($lifeplan_start_page < 1) {
                        $lifeplan_start_page = 1;
                        $lifeplan_end_page = 3;
                    }
                    if ($lifeplan_end_page > $total_lifeplan_pages) {
                        $lifeplan_end_page = $total_lifeplan_pages;
                        $lifeplan_start_page = $total_lifeplan_pages - 2;
                    }
                }
            }
            
            for ($i = $lifeplan_start_page; $i <= $lifeplan_end_page; $i++) {
                $active_class = ($i == $lifeplan_current_page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
                echo '<button onclick="loadLifeplanBookings(' . $i . ')" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</button>';
            }
            ?>
            
            <!-- Next page button (single arrow) -->
            <button onclick="loadLifeplanBookings(<?php echo min($total_lifeplan_pages, $lifeplan_current_page + 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($lifeplan_current_page == $total_lifeplan_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                ›
            </button>
            
            <!-- Last page button (double arrow) -->
            <button onclick="loadLifeplanBookings(<?php echo $total_lifeplan_pages; ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($lifeplan_current_page == $total_lifeplan_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                »
            </button>
        <?php endif; ?>
    </div>
</div>


 

<!-- Improved Booking Details Modal -->
<div id="bookingDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
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
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <!-- Top Info Bar - Booking ID and Status -->
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
      
      <!-- Main Content Area -->
      <div class="space-y-4 sm:space-y-6">
        <!-- Service Details -->
        <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
          <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
            <i class="fas fa-calendar-check mr-2 text-sidebar-accent"></i>
            Service Details
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-2 sm:space-y-3">
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Service Type</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="serviceType">Funeral Service Package A</div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Date Requested</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="dateRequested">Mar 15, 2025</div>
              </div>
            </div>
            <div class="space-y-2 sm:space-y-3">
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Service Date</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="serviceDate">Mar 20, 2025</div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Price</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="amountPaid">$3,500.00</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Customer Information -->
        <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
          <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
            <i class="fas fa-user mr-2 text-sidebar-accent"></i>
            Customer Information
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
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

        <!-- Deceased Information -->
        <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
          <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
            <i class="fas fa-sticky-note mr-2 text-sidebar-accent"></i>
            Deceased Information
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="flex flex-wrap">
              <div class="w-1/3 text-sm text-gray-500">Full Name</div>
              <div class="w-2/3 font-medium text-gray-800 break-words" id="deceasedFullName">
                <!-- Will be populated by JavaScript -->
              </div>
            </div>
            <div class="flex flex-wrap">
              <div class="w-1/3 text-sm text-gray-500">Date of Birth</div>
              <div class="w-2/3 font-medium text-gray-800 break-words" id="deceasedBirth">
                <!-- Will be populated by JavaScript -->
              </div>
            </div>
            <div class="flex flex-wrap">
              <div class="w-1/3 text-sm text-gray-500">Date of Death</div>
              <div class="w-2/3 font-medium text-gray-800 break-words" id="deceasedDeath">
                <!-- Will be populated by JavaScript -->
              </div>
            </div>
            <div class="flex flex-wrap">
              <div class="w-1/3 text-sm text-gray-500">Address</div>
              <div class="w-2/3 font-medium text-gray-800 break-words" id="deceasedAddress">
                <!-- Will be populated by JavaScript -->
              </div>
            </div>
          </div>
        </div>
        
        <!-- Documents -->
        <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
          <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
            <i class="fas fa-file-alt mr-2 text-sidebar-accent"></i>
            Documents
          </h4>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Death Certificate -->
            <div id="deathCertificateSection">
              <h5 class="font-medium text-gray-700 mb-2 flex items-center">
                <i class="fas fa-certificate text-sm mr-2 text-gray-500"></i>
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
                <i class="fas fa-receipt text-sm mr-2 text-gray-500"></i>
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
        <i class="fas fa-times-circle mr-2"></i>
        Decline Booking
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="confirmAccept()">
        <i class="fas fa-check-circle mr-2"></i>
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
        
        
        <!-- Add this payment proof display section -->
        <div class="mb-4">
          <h5 class="font-medium text-gray-700 mb-2 flex items-center">
            <i class="fas fa-receipt text-sm mr-2 text-gray-500"></i>
            Payment Proof
          </h5>
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="relative bg-gray-100 p-1">
              <img id="modalPaymentProofImage" alt="Payment Proof" class="mx-auto rounded-md max-h-48 object-contain cursor-zoom-in" onclick="enlargeImage(this)" />
            </div>
          </div>
          <div id="processingIndicator" class="text-center py-2">
            <div class="inline-block animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-blue-500"></div>
            <p class="text-gray-600 text-sm mt-1">Processing receipt for amount...</p>
          </div>
          <p id="extractionMessage" class="text-sm mt-1 hidden text-center"></p>
        </div>
        
        <!-- Amount Paid Input -->
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
        
        <!-- Payment Method Select -->
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

<!-- Image enlarger overlay -->
        <div id="imageEnlargerOverlay" class="fixed inset-0 bg-black bg-opacity-90 z-[9999] hidden flex items-center justify-center p-4" onclick="closeEnlargedImage()">
  <div class="max-w-full max-h-full flex flex-col items-center justify-center">
    <img id="enlargedImage" class="max-w-full max-h-[90vh] object-contain" />
    <button class="mt-4 text-white text-2xl hover:text-gray-300" onclick="closeEnlargedImage(event)">×</button>
  </div>
</div>


<!-- Custom Booking Details Modal -->
<div id="customDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeCustomModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Custom Package Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <!-- Top Info Bar - Booking ID and Status -->
      <div class="flex justify-between items-center mb-6 bg-gray-50 p-3 sm:p-4 rounded-lg">
        <div class="flex items-center">
          <div class="bg-navy rounded-full p-2 mr-3">
            <i class="fas fa-hashtag text-sidebar-accent"></i>
          </div>
          <div>
            <p class="text-sm text-gray-500">Booking ID</p>
            <p class="font-semibold text-gray-800" id="customBookingId">#BK-2025-001</p>
          </div>
        </div>
        <div>
          <p class="text-sm text-gray-500 mb-1">Status</p>
          <div id="customBookingStatus">
            <span class="px-3 py-1.5 text-sm font-medium rounded-full bg-yellow-100 text-sidebar-accent flex items-center">
              Pending
            </span>
          </div>
        </div>
      </div>
      
      <!-- Main Content Area -->
      <div class="space-y-4 sm:space-y-6">
        <!-- Package Details -->
        <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
          <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
            <i class="fas fa-box mr-2 text-sidebar-accent"></i>
            Package Details
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-2 sm:space-y-3">
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Package Type</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="customPackageType">Custom Package</div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Date Requested</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="customDateRequested">Mar 15, 2025</div>
              </div>
            </div>
            <div class="space-y-2 sm:space-y-3">
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Service Date</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="customServiceDate">Mar 20, 2025</div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Price</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="customAmountPaid">$3,500.00</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Customer Information -->
        <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
          <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
            <i class="fas fa-user mr-2 text-sidebar-accent"></i>
            Customer Information
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="flex flex-wrap">
              <div class="w-1/3 text-sm text-gray-500">Name</div>
              <div class="w-2/3 font-medium text-gray-800 break-words" id="customCustomerName">John Doe</div>
            </div>
            <div class="flex flex-wrap">
              <div class="w-1/3 text-sm text-gray-500">Contact</div>
              <div class="w-2/3 font-medium text-gray-800 break-words" id="customContactNumber">(555) 123-4567</div>
            </div>
            <div class="flex flex-wrap">
              <div class="w-1/3 text-sm text-gray-500">Email</div>
              <div class="w-2/3 font-medium text-gray-800 break-words" id="customEmailAddress">john.doe@example.com</div>
            </div>
            <div class="flex flex-wrap">
              <div class="w-1/3 text-sm text-gray-500">Address</div>
              <div class="w-2/3 font-medium text-gray-800 break-words" id="customAddress">123 Main St, Anytown, CA 12345</div>
            </div>
          </div>
        </div>

        <!-- Deceased Information -->
        <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
          <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
            <i class="fas fa-sticky-note mr-2 text-sidebar-accent"></i>
            Deceased Information
          </h4>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="flex flex-wrap">
              <div class="w-1/3 text-sm text-gray-500">Full Name</div>
              <div class="w-2/3 font-medium text-gray-800 break-words" id="customDeceasedFullName">
                <!-- Will be populated by JavaScript -->
              </div>
            </div>
            <div class="flex flex-wrap">
              <div class="w-1/3 text-sm text-gray-500">Date of Birth</div>
              <div class="w-2/3 font-medium text-gray-800 break-words" id="customDeceasedBirth">
                <!-- Will be populated by JavaScript -->
              </div>
            </div>
            <div class="flex flex-wrap">
              <div class="w-1/3 text-sm text-gray-500">Date of Death</div>
              <div class="w-2/3 font-medium text-gray-800 break-words" id="customDeceasedDeath">
                <!-- Will be populated by JavaScript -->
              </div>
            </div>
            <div class="flex flex-wrap">
              <div class="w-1/3 text-sm text-gray-500">Address</div>
              <div class="w-2/3 font-medium text-gray-800 break-words" id="customDeceasedAddress">
                <!-- Will be populated by JavaScript -->
              </div>
            </div>
          </div>
        </div>
        
        <!-- Custom Package Components -->
        <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
          <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
            <i class="fas fa-cubes mr-2 text-sidebar-accent"></i>
            Package Components
          </h4>
          
          <!-- Casket Selection -->
          <div class="mb-6">
            <h5 class="font-medium text-gray-700 mb-2 flex items-center">
              <i class="fas fa-bed text-sm mr-2 text-gray-500"></i>
              Selected Casket
            </h5>
            <div class="border border-gray-200 rounded-lg p-4" id="casketDetails">
              <div class="flex flex-col sm:flex-row gap-4">
                <div class="sm:w-1/3">
                  <img id="casketImage" src="" alt="Selected Casket" class="w-full h-auto rounded-md object-cover">
                </div>
                <div class="sm:w-2/3">
                  <h6 class="font-semibold text-lg" id="casketName">No casket selected</h6>
                  <div class="flex justify-between items-center">
                    <span class="font-bold text-sidebar-accent" id="casketPrice">₱0.00</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Flower Design -->
          <div class="mb-6">
            <h5 class="font-medium text-gray-700 mb-2 flex items-center">
              <i class="fas fa-spa text-sm mr-2 text-gray-500"></i>
              Flower Design
            </h5>
            <div class="border border-gray-200 rounded-lg p-4" id="flowerDesignDetails">
              <div class="flex flex-col sm:flex-row gap-4">
                
                <div class="sm:w-2/3">
                  <h6 class="font-semibold text-lg" id="flowerDesignName">No flower design selected</h6>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Inclusions -->
          <div>
            <h5 class="font-medium text-gray-700 mb-2 flex items-center">
              <i class="fas fa-list-check text-sm mr-2 text-gray-500"></i>
              Package Inclusions
            </h5>
            <div class="border border-gray-200 rounded-lg p-4">
              <ul class="space-y-2" id="inclusionsList">
                <!-- Will be populated by JavaScript -->
                <li class="text-gray-600">No inclusions selected</li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Booking Notes -->
        <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
          <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
            <i class="fas fa-sticky-note mr-2 text-sidebar-accent"></i>
            Booking Notes
          </h4>
          <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
            <p id="customBookingNotes" class="text-gray-800">
              <!-- Will be populated by JavaScript -->
            </p>
          </div>
        </div>

        <!-- Cremation Information -->
        <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
          <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
            <i class="fas fa-fire mr-2 text-sidebar-accent"></i>
            Cremation Information
          </h4>
          <div class="flex items-center">
            <span class="text-sm text-gray-700 mr-2">Includes Cremation:</span>
            <span id="customWithCremate" class="px-2 py-1 rounded-full text-xs font-medium">
              <!-- Will be populated by JavaScript -->
            </span>
          </div>
        </div>
        
        <!-- Documents -->
        <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
          <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
            <i class="fas fa-file-alt mr-2 text-sidebar-accent"></i>
            Documents
          </h4>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Death Certificate -->
            <div id="customDeathCertificateSection">
              <h5 class="font-medium text-gray-700 mb-2 flex items-center">
                <i class="fas fa-certificate text-sm mr-2 text-gray-500"></i>
                Death Certificate
              </h5>
              <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div id="customDeathCertificateAvailable" class="text-center">
                  <div class="relative bg-gray-100 p-1">
                    <img id="customDeathCertificateImage" alt="Death Certificate" class="mx-auto rounded-md max-h-48 object-contain" />
                    <div class="absolute top-2 right-2">
                      <button class="bg-white rounded-full p-1 shadow-md hover:bg-gray-100 transition-colors duration-200" title="View Full Size">
                        <i class="fas fa-search-plus text-blue-600"></i>
                      </button>
                    </div>
                  </div>
                </div>
                <div id="customDeathCertificateNotAvailable" class="hidden">
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
                <i class="fas fa-receipt text-sm mr-2 text-gray-500"></i>
                Payment Proof
              </h5>
              <div class="border border-gray-200 rounded-lg overflow-hidden">
                <div class="relative bg-gray-100 p-1">
                  <img id="customPaymentProofImage" alt="Payment Proof" class="mx-auto rounded-md max-h-48 object-contain" />
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
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="confirmCustomDecline()">
        <i class="fas fa-times-circle mr-2"></i>
        Decline Booking
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="confirmCustomAccept()">
        <i class="fas fa-check-circle mr-2"></i>
        Accept Booking
      </button>
    </div>
  </div>
</div>

<!-- Custom Decline Reason Modal -->
<div id="customDeclineReasonModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 z-10 transform transition-all duration-300">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-sidebar-accent transition-colors" onclick="closeCustomDeclineReasonModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-red-600 to-red-800 border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        Decline Custom Booking
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5">
      <form id="customDeclineReasonForm">
        <input type="hidden" id="customBookingIdForDecline" name="bookingId">
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Declining</label>
          
          <!-- Suggested Reasons -->
          <div class="grid grid-cols-2 gap-2 mb-4">
            <button type="button" onclick="selectCustomDeclineReason('Invalid payment certificate')" 
                    class="text-left p-2 border border-gray-300 rounded hover:bg-gray-100 text-sm"> Invalid payment certificate
            </button>
            <button type="button" onclick="selectCustomDeclineReason('Incomplete documents')" 
                    class="text-left p-2 border border-gray-300 rounded hover:bg-gray-100 text-sm">Incomplete documents
            </button>
            <button type="button" onclick="selectCustomDeclineReason('Unavailable service date')" 
                    class="text-left p-2 border border-gray-300 rounded hover:bg-gray-100 text-sm">Unavailable service date
            </button>
            <button type="button" onclick="selectCustomDeclineReason('Selected items not available')" 
                    class="text-left p-2 border border-gray-300 rounded hover:bg-gray-100 text-sm">Selected items not available
            </button>
          </div>
          
          <!-- Custom Reason -->
          <div>
            <label for="customDeclineReason" class="block text-sm font-medium text-gray-700 mb-1">Or specify your own reason:</label>
            <textarea id="customDeclineReason" name="customReason" rows="3" 
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 py-2 px-3 border"
                      placeholder="Enter your reason for declining this booking..."></textarea>
          </div>
        </div>
        
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" onclick="closeCustomDeclineReasonModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
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

<!-- Add this image enlarger overlay (place it near the modal in your HTML) -->
<div id="imageEnlargerOverlay" class="fixed inset-0 bg-black bg-opacity-90 z-[9999] hidden flex items-center justify-center p-4" onclick="closeEnlargedImage()">
  <div class="max-w-full max-h-full flex flex-col items-center justify-center">
    <img id="enlargedImage" class="max-w-full max-h-[90vh] object-contain" />
    <button class="mt-4 text-white text-2xl hover:text-gray-300" onclick="closeEnlargedImage(event)">×</button>
  </div>
</div>

<!-- Custom Payment Modal -->
<div id="customPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 z-10 transform transition-all duration-300">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-sidebar-accent transition-colors" onclick="closeCustomPaymentModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        Payment Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5">
      <form id="customPaymentForm">
        <!-- Booking Information -->
        <input type="hidden" id="customBookingIdForPayment" name="bookingId">
        
        <!-- Customer Information -->
        <input type="hidden" id="customCustomerId" name="customerId">
        <input type="hidden" id="customCustomerFirstName" name="first_name">
        <input type="hidden" id="customCustomerMiddleName" name="middle_name">
        <input type="hidden" id="customCustomerLastName" name="last_name">
        <input type="hidden" id="customCustomerSuffix" name="suffix">
        <input type="hidden" id="customCustomerEmail" name="email">
        <input type="hidden" id="customCustomerPhone" name="phone_number">
        
        <!-- Deceased Information -->
        <input type="hidden" id="customDeceasedFname" name="deceased_fname">
        <input type="hidden" id="customDeceasedMname" name="deceased_mname">
        <input type="hidden" id="customDeceasedLname" name="deceased_lname">
        <input type="hidden" id="customDeceasedSuffix" name="deceased_suffix">
        <input type="hidden" id="customDeceasedAddressInput" name="deceased_address">
        
        <!-- Deceased Dates -->
        <input type="hidden" id="customDeceasedBirthInput" name="deceased_birth">
        <input type="hidden" id="customDeceasedDeathInput" name="deceased_dodeath">
        <input type="hidden" id="customDeceasedBurialInput" name="deceased_dateOfBurial">
        
        <!-- Package Components -->
        <input type="hidden" id="customCasketId" name="casket_id">
        <input type="hidden" id="customFlowerId" name="flower_id">
        <input type="hidden" id="customInclusions" name="inclusions">
        
        <!-- Other Information -->
        <input type="hidden" id="customBranchId" name="branchId">
        <input type="hidden" id="customInitialPrice" name="initial_price">
        <input type="hidden" id="customDeathCertUrl" name="deathcert_url">
        <input type="hidden" id="customWithCremateInput" name="with_cremate">
        
       <!-- Add this payment proof display section -->
        <div class="mb-4">
          <h5 class="font-medium text-gray-700 mb-2 flex items-center">
            <i class="fas fa-receipt text-sm mr-2 text-gray-500"></i>
            Payment Proof
          </h5>
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="relative bg-gray-100 p-1">
              <img id="customModalPaymentProofImage" alt="Payment Proof" class="mx-auto rounded-md max-h-48 object-contain cursor-zoom-in" onclick="enlargeImage(this)" />
            </div>
          </div>
          <div id="customProcessingIndicator" class="text-center py-2">
            <div class="inline-block animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-blue-500"></div>
            <p class="text-gray-600 text-sm mt-1">Processing receipt for amount...</p>
          </div>
          <p id="customExtractionMessage" class="text-sm mt-1 hidden text-center"></p>
        </div>
        
        <!-- Amount Paid Input -->
        <div class="mb-4">
          <label for="customAmountPaidInput" class="block text-sm font-medium text-gray-700 mb-1">Amount Paid</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" step="0.01" id="customAmountPaidInput" name="amountPaid" 
                   class="pl-8 block w-full rounded-md border-gray-300 shadow-sm focus:border-sidebar-accent focus:ring focus:ring-sidebar-accent focus:ring-opacity-50 py-2 px-3 border" 
                   placeholder="0.00" required>
          </div>
        </div>
        
        <!-- Payment Method Select -->
        <div class="mb-4">
          <label for="customPaymentMethod" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
          <select id="customPaymentMethod" name="paymentMethod" 
                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-sidebar-accent focus:ring focus:ring-sidebar-accent focus:ring-opacity-50 py-2 px-3 border" required>
            <option value="">Select payment method</option>
            <option value="Bank">Bank Transfer</option>
            <option value="GCash">GCash</option>
            <option value="Cash">Cash</option>
          </select>
        </div>
        
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" onclick="closeCustomPaymentModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
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

<!-- LifePlan Details Modal -->
<div id="lifeplanDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
    
    <!-- Modal Content -->
    <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
      <!-- Close Button -->
      <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeLifeplanModal()">
        <i class="fas fa-times"></i>
      </button>
      
      <!-- Modal Header -->
      <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
        <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
          LifePlan Booking Details
        </h3>
      </div>
      
      <!-- Modal Body -->
      <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
        <!-- Top Info Bar - Booking ID and Status -->
        <div class="flex justify-between items-center mb-6 bg-gray-50 p-3 sm:p-4 rounded-lg">
          <div class="flex items-center">
            <div class="bg-navy rounded-full p-2 mr-3">
              <i class="fas fa-hashtag text-sidebar-accent"></i>
            </div>
            <div>
              <p class="text-sm text-gray-500">Booking ID</p>
              <p class="font-semibold text-gray-800" id="lifeplanBookingId">#LP-2025-001</p>
            </div>
          </div>
          <div>
            <p class="text-sm text-gray-500 mb-1">Status</p>
            <div id="lifeplanBookingStatus">
              <span class="px-3 py-1.5 text-sm font-medium rounded-full bg-yellow-100 text-sidebar-accent flex items-center">
                Pending
              </span>
            </div>
          </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="space-y-4 sm:space-y-6">
          <!-- Plan Details -->
          <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
            <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
              <i class="fas fa-heart mr-2 text-sidebar-accent"></i>
              Plan Details
            </h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-2 sm:space-y-3">
                <div class="flex flex-wrap">
                  <div class="w-1/3 text-sm text-gray-500">Plan Type</div>
                  <div class="w-2/3 font-medium text-gray-800 break-words" id="lifeplanType">Premium LifePlan</div>
                </div>
                <div class="flex flex-wrap">
                  <div class="w-1/3 text-sm text-gray-500">Date Requested</div>
                  <div class="w-2/3 font-medium text-gray-800 break-words" id="lifeplanDateRequested">Mar 15, 2025</div>
                </div>
              </div>
              <div class="space-y-2 sm:space-y-3">
                <div class="flex flex-wrap">
                  <div class="w-1/3 text-sm text-gray-500">Plan Price</div>
                  <div class="w-2/3 font-medium text-gray-800 break-words" id="lifeplanPrice">₱50,000.00</div>
                </div>
                <div class="flex flex-wrap">
                  <div class="w-1/3 text-sm text-gray-500">Payment Terms</div>
                  <div class="w-2/3 font-medium text-gray-800 break-words" id="lifeplanTerms">Monthly (12 months)</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Customer Information -->
          <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
            <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
              <i class="fas fa-user mr-2 text-sidebar-accent"></i>
              Customer Information
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Name</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="lifeplanCustomerName">John Doe</div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Contact</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="lifeplanContactNumber">(555) 123-4567</div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Email</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="lifeplanEmailAddress">john.doe@example.com</div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Address</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="lifeplanAddress">123 Main St, Anytown, CA 12345</div>
              </div>
            </div>
          </div>

          <!-- Beneficiary Information -->
          <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
            <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
              <i class="fas fa-users mr-2 text-sidebar-accent"></i>
              Beneficiary Information
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Full Name</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="beneficiaryFullName">
                  Jane Doe
                </div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Relationship</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="beneficiaryRelationship">
                  Spouse
                </div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Contact</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="beneficiaryContact">
                  (555) 987-6543
                </div>
              </div>
              <div class="flex flex-wrap">
                <div class="w-1/3 text-sm text-gray-500">Address</div>
                <div class="w-2/3 font-medium text-gray-800 break-words" id="beneficiaryAddress">
                  Same as customer
                </div>
              </div>
            </div>
          </div>
          
          <!-- Documents -->
          <div class="bg-white rounded-lg p-4 sm:p-5 border border-gray-200 shadow-sm">
            <h4 class="font-semibold text-gray-800 mb-3 sm:mb-4 flex items-center">
              <i class="fas fa-file-alt mr-2 text-sidebar-accent"></i>
              Documents
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Valid ID -->
              <div id="validIdSection">
                <h5 class="font-medium text-gray-700 mb-2 flex items-center">
                  <i class="fas fa-id-card text-sm mr-2 text-gray-500"></i>
                  Valid ID
                </h5>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                  <div id="validIdAvailable" class="text-center">
                    <div class="relative bg-gray-100 p-1">
                      <img id="validIdImage" alt="Valid ID" class="mx-auto rounded-md max-h-48 object-contain" />
                      <div class="absolute top-2 right-2">
                        <button class="bg-white rounded-full p-1 shadow-md hover:bg-gray-100 transition-colors duration-200" title="View Full Size">
                          <i class="fas fa-search-plus text-blue-600"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                  <div id="validIdNotAvailable" class="hidden">
                    <div class="flex flex-col items-center justify-center py-8 px-4 bg-gray-50">
                      <i class="fas fa-exclamation-circle text-gray-400 text-3xl mb-2"></i>
                      <p class="text-gray-500 text-center">No valid ID has been uploaded yet.</p>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Payment Proof -->
              <div>
                <h5 class="font-medium text-gray-700 mb-2 flex items-center">
                  <i class="fas fa-receipt text-sm mr-2 text-gray-500"></i>
                  Initial Payment Proof
                </h5>
                <div class="border border-gray-200 rounded-lg overflow-hidden">
                  <div class="relative bg-gray-100 p-1">
                    <img id="lifeplanPaymentProofImage" alt="Payment Proof" class="mx-auto rounded-md max-h-48 object-contain" />
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
        <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="confirmLifeplanDecline()">
          <i class="fas fa-times-circle mr-2"></i>
          Decline Plan
        </button>
        <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="confirmLifeplanAccept()">
          <i class="fas fa-check-circle mr-2"></i>
          Accept Plan
        </button>
      </div>
    </div>
  </div>
</div>

<!-- LifePlan Decline Reason Modal -->
<div id="lifeplanDeclineReasonModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 z-10 transform transition-all duration-300">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-sidebar-accent transition-colors" onclick="closeLifeplanDeclineReasonModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-red-600 to-red-800 border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        Decline LifePlan
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5">
      <form id="lifeplanDeclineReasonForm">
        <input type="hidden" id="lifeplanIdForDecline" name="lifeplanId">
        
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-2">Reason for Declining</label>
          
          <!-- Suggested Reasons -->
          <div class="grid grid-cols-2 gap-2 mb-4">
            <button type="button" onclick="selectLifeplanDeclineReason('Invalid documents')" 
                    class="text-left p-2 border border-gray-300 rounded hover:bg-gray-100 text-sm"> Invalid documents
            </button>
            <button type="button" onclick="selectLifeplanDeclineReason('Incomplete information')" 
                    class="text-left p-2 border border-gray-300 rounded hover:bg-gray-100 text-sm">Incomplete information
            </button>
            <button type="button" onclick="selectLifeplanDeclineReason('Payment issues')" 
                    class="text-left p-2 border border-gray-300 rounded hover:bg-gray-100 text-sm">Payment issues
            </button>
            <button type="button" onclick="selectLifeplanDeclineReason('Plan not available')" 
                    class="text-left p-2 border border-gray-300 rounded hover:bg-gray-100 text-sm">Plan not available
            </button>
          </div>
          
          <!-- Custom Reason -->
          <div>
            <label for="lifeplanCustomReason" class="block text-sm font-medium text-gray-700 mb-1">Or specify your own reason:</label>
            <textarea id="lifeplanCustomReason" name="customReason" rows="3" 
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring focus:ring-red-200 focus:ring-opacity-50 py-2 px-3 border"
                      placeholder="Enter your reason for declining this lifeplan..."></textarea>
          </div>
        </div>
        
        <div class="flex justify-end gap-3 mt-6">
          <button type="button" onclick="closeLifeplanDeclineReasonModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
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

<!-- LifePlan Payment Modal -->
<div id="lifeplanPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 z-10 transform transition-all duration-300">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-sidebar-accent transition-colors" onclick="closeLifeplanPaymentModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        LifePlan Payment Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5">
      <form id="lifeplanPaymentForm">
          <!-- LifePlan Information -->
          <input type="hidden" id="lifeplanIdForPayment" name="lifeplanId">
          
          <!-- Customer Information -->
          <input type="hidden" id="lifeplanCustomerID" name="customerId">
          <input type="hidden" id="lifeplanCustomerBranch" name="branchId">
          <input type="hidden" id="lifeplanCustomerFirstName" name="first_name">
          <input type="hidden" id="lifeplanCustomerMiddleName" name="middle_name">
          <input type="hidden" id="lifeplanCustomerLastName" name="last_name">
          <input type="hidden" id="lifeplanCustomerSuffix" name="suffix">
          <input type="hidden" id="lifeplanCustomerEmail" name="email">
          <input type="hidden" id="lifeplanCustomerPhone" name="phone_number">
          
          <!-- Beneficiary Information -->
          <input type="hidden" id="beneficiaryFirstName" name="beneficiary_fname">
          <input type="hidden" id="beneficiaryMiddleName" name="beneficiary_mname">
          <input type="hidden" id="beneficiaryLastName" name="beneficiary_lname">
          <input type="hidden" id="beneficiarySuffix" name="beneficiary_suffix">
          <input type="hidden" id="beneficiaryBirthdate" name="beneficiary_birth">
          <input type="hidden" id="beneficiaryAddressInput" name="beneficiary_address">
          <input type="hidden" id="relationshipWithClient" name="relationship_with_client">
          
          <!-- Plan Information -->
          <input type="hidden" id="lifeplanServiceId" name="service_id">
          <input type="hidden" id="lifeplanPackagePrice" name="package_price">
          <input type="hidden" id="lifeplanPaymentDuration" name="payment_duration">
          <input type="hidden" id="lifeplanValidIdUrl" name="valid_id_url">
          <input type="hidden" id="lifeplanInitialDate" name="initial_date">
          <input type="hidden" id="lifeplanEndDate" name="end_date">
          <input type="hidden" id="withCremateInput" name="with_cremate">
          
          <!-- Add this payment proof display section -->
          <div class="mb-4">
            <h5 class="font-medium text-gray-700 mb-2 flex items-center">
              <i class="fas fa-receipt text-sm mr-2 text-gray-500"></i>
              Payment Proof
            </h5>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
              <div class="relative bg-gray-100 p-1">
                <img id="modalLifeplanPaymentProofImage" alt="Payment Proof" class="mx-auto rounded-md max-h-48 object-contain cursor-zoom-in" onclick="enlargeImage(this)" />
              </div>
            </div>
            <div id="lifeplanProcessingIndicator" class="text-center py-2">
              <div class="inline-block animate-spin rounded-full h-6 w-6 border-t-2 border-b-2 border-blue-500"></div>
              <p class="text-gray-600 text-sm mt-1">Processing receipt for amount...</p>
            </div>
            <p id="lifeplanExtractionMessage" class="text-sm mt-1 hidden text-center"></p>
          </div>
          
          <!-- Amount Paid Input -->
          <div class="mb-4">
            <label for="lifeplanAmountPaidInput" class="block text-sm font-medium text-gray-700 mb-1">Amount Paid</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500">₱</span>
              </div>
              <input type="number" step="0.01" id="lifeplanAmountPaidInput" name="amountPaid" 
                    class="pl-8 block w-full rounded-md border-gray-300 shadow-sm focus:border-sidebar-accent focus:ring focus:ring-sidebar-accent focus:ring-opacity-50 py-2 px-3 border" 
                    placeholder="0.00" required>
            </div>
          </div>
          
          <!-- Payment Method Select -->
          <div class="mb-4">
            <label for="lifeplanPaymentMethod" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
            <select id="lifeplanPaymentMethod" name="paymentMethod" 
                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-sidebar-accent focus:ring focus:ring-sidebar-accent focus:ring-opacity-50 py-2 px-3 border" required>
              <option value="">Select payment method</option>
              <option value="Bank">Bank Transfer</option>
              <option value="GCash">GCash</option>
              <option value="Cash">Cash</option>
            </select>
          </div>
          
          <div class="flex justify-end gap-3 mt-6">
            <button type="button" onclick="closeLifeplanPaymentModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
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

<!-- Image enlarger overlay (can be shared with other modals) -->
<div id="imageEnlargerOverlay" class="fixed inset-0 bg-black bg-opacity-90 z-[9999] hidden flex items-center justify-center p-4" onclick="closeEnlargedImage()">
  <div class="max-w-full max-h-full flex flex-col items-center justify-center">
    <img id="enlargedImage" class="max-w-full max-h-[90vh] object-contain" />
    <button class="mt-4 text-white text-2xl hover:text-gray-300" onclick="closeEnlargedImage(event)">×</button>
  </div>
</div>



    <script src="script.js"></script>
    <script src="tailwind.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@4/dist/tesseract.min.js"></script>

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
        document.getElementById('amountPaid').textContent = 
    "₱" + (parseFloat(data.initial_price) || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

      const deceasedFullName = [
        data.deceased_fname || '',
        data.deceased_midname || '',
        data.deceased_lname || '',
        data.deceased_suffix || ''
      ].filter(Boolean).join(' ');

      document.getElementById('deceasedFullName').textContent = deceasedFullName || "Not provided";
      document.getElementById('deceasedBirth').textContent = data.deceased_birth ? 
        new Date(data.deceased_birth).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 
        "Not provided";
      document.getElementById('deceasedDeath').textContent = data.deceased_dodeath ? 
        new Date(data.deceased_dodeath).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 
        "Not provided";
      document.getElementById('deceasedAddress').textContent = data.deceased_address || "Not provided";
      
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

      // Handle Payment Proof Images
const paymentProofImage = document.getElementById('paymentProofImage');
const modalPaymentProofImage = document.getElementById('modalPaymentProofImage');
const paymentProofContainer = paymentProofImage.parentElement;
const modalPaymentProofContainer = modalPaymentProofImage.parentElement;

if (data.payment_url && data.payment_url !== '') {
  // Use relative path with ../ to navigate up and then to the customer/booking/uploads folder
  const paymentProofPath = '../customer/booking/uploads/' + data.payment_url.replace(/^uploads\//, '');
  console.log("Payment Proof Path:", paymentProofPath);
  
  // Set error handler before setting src for both images
  const errorHandler = function() {
    console.error("Failed to load payment proof image:", paymentProofPath);
    // Create a placeholder instead of loading another image
    const placeholderHTML = `
      <div class="flex flex-col items-center justify-center py-8 px-4 bg-gray-50">
        <i class="fas fa-exclamation-circle text-gray-400 text-3xl mb-2"></i>
        <p class="text-gray-500 text-center">Image could not be loaded</p>
      </div>`;
    paymentProofContainer.innerHTML = placeholderHTML;
    modalPaymentProofContainer.innerHTML = placeholderHTML;
  };
  
  paymentProofImage.onerror = errorHandler;
  modalPaymentProofImage.onerror = errorHandler;
  
  // Set the source for both images
  paymentProofImage.src = paymentProofPath;
  modalPaymentProofImage.src = paymentProofPath;
} else {
  // Create a placeholder for when no payment proof exists
  const placeholderHTML = `
    <div class="flex flex-col items-center justify-center py-8 px-4 bg-gray-50">
      <i class="fas fa-exclamation-circle text-gray-400 text-3xl mb-2"></i>
      <p class="text-gray-500 text-center">No payment proof provided</p>
    </div>`;
  paymentProofContainer.innerHTML = placeholderHTML;
  modalPaymentProofContainer.innerHTML = placeholderHTML;
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

// Add this to your script section
function setupImageViewerListeners(modalId) {
  const modal = document.getElementById(modalId);
  if (!modal) return;

  // Add click handlers to all "View Full Size" buttons in this modal
  modal.querySelectorAll('button[title="View Full Size"]').forEach(button => {
    button.addEventListener('click', function() {
      const imageElement = this.closest('.relative').querySelector('img');
      if (imageElement && imageElement.src) {
        viewFullSizeImage(imageElement.src);
      }
    });
  });

  // Make the main images clickable too
  modal.querySelectorAll('img[id$="Image"]').forEach(img => {
    img.style.cursor = 'zoom-in';
    img.addEventListener('click', function() {
      if (this.src) {
        viewFullSizeImage(this.src);
      }
    });
  });
}

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


// Function to extract amount from text
function extractAmountFromText(text) {
  // Enhanced patterns for amount detection
  const amountPatterns = [
    /Amount\s*:\s*₱?\s*([\d,]+\.\d{2})/i,      // "Amount: ₱1,234.56"
    /Amount\s*₱?\s*([\d,]+\.\d{2})/i,          // "Amount ₱1,234.56"
    /Amount\s*PHP\s*([\d,]+\.\d{2})/i,         // "Amount PHP 1,234.56"
    /^.*Amount\s*[^0-9]*([\d,]+\.\d{2})/im,    // More flexible amount match
    /Total\s*:\s*₱?\s*([\d,]+\.\d{2})/i,       // "Total: ₱1,234.56"
    /Total\s*₱?\s*([\d,]+\.\d{2})/i,           // "Total ₱1,234.56"
    /₱\s*([\d,]+\.\d{2})/i,                    // "₱1,234.56"
    /PHP\s*([\d,]+\.\d{2})/i,                  // "PHP 1,234.56"
    /([\d,]+\.\d{2})\s*PHP/i,                  // "1,234.56 PHP"
    /Paid\s*:\s*₱?\s*([\d,]+\.\d{2})/i,        // "Paid: ₱1,234.56"
    /Payment\s*:\s*₱?\s*([\d,]+\.\d{2})/i,     // "Payment: ₱1,234.56"
    /([\d,]+\.\d{2})\s*pesos/i,                // "1,234.56 pesos"
    /([\d,]+\.\d{2})\s*PH/i                    // "1,234.56 PH"
  ];
  
  // First try to find exact matches
  for (const pattern of amountPatterns) {
    const match = text.match(pattern);
    if (match && match[1]) {
      return parseFloat(match[1].replace(/,/g, ''));
    }
  }
  
  // Fallback - look for any number with 2 decimal places
  const fallbackMatch = text.match(/(\d[\d,]*\.\d{2})\b/);
  if (fallbackMatch && fallbackMatch[1]) {
    return parseFloat(fallbackMatch[1].replace(/,/g, ''));
  }
  
  // Final fallback - return 0 if no amount found
  return 0;
}

// When opening the modal, make sure to set the image source
function openPaymentModalWithImage(bookingId, imageUrl) {
  // Reset form
  document.getElementById('paymentForm').reset();
  
  // Reset messages
  document.getElementById('extractionMessage').classList.add('hidden');
  
  // Set the payment proof image
  const proofImage = document.getElementById('modalPaymentProofImage');
  proofImage.src = imageUrl;
  proofImage.onerror = function() {
    this.style.display = 'none';
  };
  
  // Show modal
  document.getElementById('paymentModal').classList.remove('hidden');
  
  // Set booking ID or other data as needed
  document.getElementById('bookingIdForPayment').value = bookingId;
}

function openPaymentModal() {
    const modal = document.getElementById("paymentModal");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    document.body.classList.add("overflow-hidden");
    
    // Automatically start processing the receipt when modal opens
    autoExtractAmountFromImage();
}

async function autoExtractAmountFromImage() {
  const amountInput = document.getElementById('amountPaidInput');
  const processingIndicator = document.getElementById('processingIndicator');
  const extractionMessage = document.getElementById('extractionMessage');
  
  const proofImage = document.getElementById('modalPaymentProofImage');
  if (!proofImage.src) {
    processingIndicator.classList.add('hidden');
    extractionMessage.textContent = 'No payment proof image available';
    extractionMessage.classList.remove('hidden');
    extractionMessage.classList.add('text-red-500');
    return;
  }
  
  try {
    // Convert the image to a blob for processing
    const blob = await fetch(proofImage.src).then(res => res.blob());
    
    // Process with Tesseract
    const { data: { text } } = await Tesseract.recognize(
      blob,
      'eng',
      { 
        logger: m => console.log(m),
        tessedit_pageseg_mode: 6 // Assume a single uniform block of text
      }
    );
    
    console.log('Extracted text:', text); // For debugging
    
    // Extract the amount
    const amount = extractAmountFromText(text);
    
    // Fill the amount input if found
    if (amount > 0) {
      amountInput.value = amount.toFixed(2);
      extractionMessage.textContent = 'Amount automatically detected from receipt!';
      extractionMessage.classList.remove('text-red-500');
      extractionMessage.classList.add('text-green-500');
    } else {
      extractionMessage.textContent = 'Could not automatically detect amount. Please enter manually.';
      extractionMessage.classList.remove('text-green-500');
      extractionMessage.classList.add('text-red-500');
    }
    extractionMessage.classList.remove('hidden');
  } catch (error) {
    console.error('Error processing receipt:', error);
    extractionMessage.textContent = 'Error processing receipt. Please enter amount manually.';
    extractionMessage.classList.remove('text-green-500');
    extractionMessage.classList.add('text-red-500');
    extractionMessage.classList.remove('hidden');
  } finally {
    // Hide processing indicator
    processingIndicator.classList.add('hidden');
  }
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
    
    const amountPaid = parseFloat(document.getElementById('amountPaidInput').value);
    const paymentMethod = document.getElementById('paymentMethod').value;
    const initialPrice = parseFloat(document.getElementById('initialPrice').value) || 0;
    
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

    if (amountPaid <= 0) {
        console.log('Validation failed - amount paid is zero or negative'); // Log validation failure
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Amount paid must be greater than ₱0.00',
        });
        return;
    }
    
    // Add validation for amountPaid being greater than initialPrice
    if (amountPaid > initialPrice) {
        console.log('Validation failed - amount paid exceeds initial price'); // Log validation failure
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: `Amount paid <strong>₱${amountPaid.toFixed(2)}</strong> is higher than the initial price <strong>₱${initialPrice.toFixed(2)}</strong>`,
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
    const balance = initialPrice - amountPaid;
    const paymentStatus = balance <= 0 ? 'Fully Paid' : 'With Balance';
    console.log('Calculated values - Initial Price:', initialPrice, 'Balance:', balance, 'Status:', paymentStatus);
    
    // Show confirmation dialog with payment summary
    Swal.fire({
        title: 'Confirm Payment Details',
        html: `<div class="text-left">
            <p><strong>Amount Paid:</strong> ₱${amountPaid.toFixed(2)}</p>
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
// Function to initialize all filter functionality
function initializeAllFilters() {
    // Initialize filters for each table type
    initializeTableFilters('booking', '#bookingTableBody', '#totalBookings', '#paginationInfo');
    initializeTableFilters('custom', '#customBookingTableBody', '#totalCustomBookings', '#customPaginationInfo');
    initializeTableFilters('lifeplan', '#lifeplanBookingTableBody', '#totalLifeplanBookings', '#lifeplanPaginationInfo');
}

// Generic function to initialize filters for a table
function initializeTableFilters(tableType, tableBodySelector, totalCountSelector, paginationInfoSelector) {
    // Mobile filter toggle
    const mobileFilterToggle = document.getElementById(`${tableType}BookingFilterToggleMobile`);
    const mobileFilterDropdown = document.getElementById(`${tableType}BookingFilterDropdownMobile`);
    
    if (mobileFilterToggle && mobileFilterDropdown) {
        mobileFilterToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            mobileFilterDropdown.classList.toggle('hidden');
        });
    }
    
    // Desktop filter toggle
    const desktopFilterToggle = document.getElementById(`${tableType}BookingFilterToggle`);
    const desktopFilterDropdown = document.getElementById(`${tableType}BookingFilterDropdown`);
    
    if (desktopFilterToggle && desktopFilterDropdown) {
        desktopFilterToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            desktopFilterDropdown.classList.toggle('hidden');
        });
    }
    
    // Handle search functionality
    const desktopSearchInput = document.getElementById(`${tableType}BookingSearchInput`);
    const mobileSearchInput = document.getElementById(`${tableType}BookingSearchInputMobile`);
    
    if (desktopSearchInput && mobileSearchInput) {
        desktopSearchInput.addEventListener('input', function() {
            mobileSearchInput.value = this.value;
            filterTable(tableType, tableBodySelector, totalCountSelector, paginationInfoSelector);
        });
        
        mobileSearchInput.addEventListener('input', function() {
            desktopSearchInput.value = this.value;
            filterTable(tableType, tableBodySelector, totalCountSelector, paginationInfoSelector);
        });
    }
    
    // Handle filter options (both mobile and desktop)
    document.querySelectorAll(`.${tableType}-filter-option, .${tableType}-filter-option-mobile`).forEach(option => {
        option.addEventListener('click', function() {
            const sortValue = this.getAttribute('data-sort');
            
            // Update UI to show active filter
            document.querySelectorAll(`.${tableType}-filter-option, .${tableType}-filter-option-mobile`).forEach(opt => {
                opt.classList.remove('bg-sidebar-hover');
            });
            this.classList.add('bg-sidebar-hover');
            
            // Show filter indicator
            document.getElementById(`${tableType}FilterIndicator`).classList.remove('hidden');
            document.getElementById(`${tableType}FilterIndicatorMobile`).classList.remove('hidden');
            
            // Apply sorting
            sortTable(tableType, sortValue, tableBodySelector);
            
            // Close dropdowns
            if (mobileFilterDropdown) mobileFilterDropdown.classList.add('hidden');
            if (desktopFilterDropdown) desktopFilterDropdown.classList.add('hidden');
        });
    });
}

// Function to filter table based on search input
function filterTable(tableType, tableBodySelector, totalCountSelector, paginationInfoSelector) {
    const searchValue = (document.getElementById(`${tableType}BookingSearchInput`).value || '').toLowerCase();
    const rows = document.querySelectorAll(`${tableBodySelector} tr`);
    
    let visibleCount = 0;
    
    rows.forEach(row => {
        // Get all cells that should be searchable (adjust indexes as needed)
        const cells = row.cells;
        let matchesSearch = false;
        
        // Check each cell for a match
        for (let i = 0; i < cells.length - 1; i++) { // Skip last cell (actions)
            const cellText = cells[i]?.textContent?.toLowerCase() || '';
            if (cellText.includes(searchValue)) {
                matchesSearch = true;
                break;
            }
        }
        
        if (matchesSearch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update total count display
    if (totalCountSelector) {
        const totalElement = document.querySelector(totalCountSelector);
        if (totalElement) {
            if (visibleCount === 0) {
                totalElement.textContent = "No bookings";
            } else {
                totalElement.textContent = visibleCount;
            }
        }
    }
    
    // Update pagination info
    if (paginationInfoSelector) {
        const totalRows = document.querySelectorAll(`${tableBodySelector} tr`).length;
        const paginationInfo = document.querySelector(paginationInfoSelector);
        if (paginationInfo) {
            paginationInfo.textContent = `Showing ${visibleCount} of ${totalRows} bookings`;
        }
    }
}

// Function to sort table
function sortTable(tableType, sortValue, tableBodySelector) {
    const rows = Array.from(document.querySelectorAll(`${tableBodySelector} tr:not([style*="display: none"])`));
    const tbody = document.querySelector(tableBodySelector);
    
    // Clear the table
    tbody.innerHTML = '';
    
    // Sort the rows based on the selected option
    rows.sort((a, b) => {
        const aId = parseInt(a.cells[0].textContent.split('-')[2]);
        const bId = parseInt(b.cells[0].textContent.split('-')[2]);
        
        const aCustomer = a.cells[1].textContent.toLowerCase();
        const bCustomer = b.cells[1].textContent.toLowerCase();
        
        // For custom bookings, the date is in cell 3 (index 3)
        // For lifeplan bookings, the date might be in a different cell
        const dateCellIndex = tableType === 'lifeplan' ? 3 : 3; // Adjust as needed
        const aDate = new Date(a.cells[dateCellIndex].textContent);
        const bDate = new Date(b.cells[dateCellIndex].textContent);
        
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

// Initialize all filters when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    initializeAllFilters();
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        // Close booking filter dropdowns
        if (!event.target.closest('.filter-dropdown')) {
            document.querySelectorAll('[id$="BookingFilterDropdown"], [id$="BookingFilterDropdownMobile"]').forEach(dropdown => {
                dropdown.classList.add('hidden');
            });
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

<script>
  //functions for custom bookings 
  let currentCustomBookingIdForPayment = null;

function openCustomDetails(bookingId) {
  // First, fetch custom booking details via AJAX
  fetch('bookingpage/get_custom_booking_details.php?id=' + bookingId)
    .then(response => response.json())
    .then(data => {
      // Populate modal with the basic details
      document.getElementById('customBookingId').textContent = '#BK-' + 
        new Date(data.booking_date).getFullYear() + '-' + 
        String(data.booking_id).padStart(3, '0');
      document.getElementById('customCustomerName').textContent = data.customer_name;
      document.getElementById('customContactNumber').textContent = data.contact_number || "Not provided";
      document.getElementById('customEmailAddress').textContent = data.email;
      document.getElementById('customAddress').textContent = data.address || "Not provided";
      document.getElementById('customPackageType').textContent = "Custom Package";
      document.getElementById('customDateRequested').textContent = 
        new Date(data.booking_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
      document.getElementById('customServiceDate').textContent = 
        data.deceased_dateOfBurial ? new Date(data.deceased_dateOfBurial).toLocaleDateString('en-US', 
        { month: 'short', day: 'numeric', year: 'numeric' }) : "Not scheduled";
        document.getElementById('customAmountPaid').textContent = 
    "₱" + (parseFloat(data.initial_price) || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

      const deceasedFullName = [
        data.deceased_fname || '',
        data.deceased_midname || '',
        data.deceased_lname || '',
        data.deceased_suffix || ''
      ].filter(Boolean).join(' ');

      document.getElementById('customDeceasedFullName').textContent = deceasedFullName || "Not provided";
      document.getElementById('customDeceasedBirth').textContent = data.deceased_birth ? 
        new Date(data.deceased_birth).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 
        "Not provided";
      document.getElementById('customDeceasedDeath').textContent = data.deceased_dodeath ? 
        new Date(data.deceased_dodeath).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 
        "Not provided";
      document.getElementById('customDeceasedAddress').textContent = data.deceased_address || "Not provided";
      
      // Update booking status
      const statusElement = document.getElementById('customBookingStatus');
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

      // Handle Booking Notes
      document.getElementById('customBookingNotes').textContent = 
        data.booking_notes || "No additional notes provided for this booking.";

      // Handle Cremation Information
      const cremationElement = document.getElementById('customWithCremate');
      if (data.with_cremate === 'yes') {
        cremationElement.innerHTML = `
          <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
            <i class="fas fa-check-circle mr-1"></i> Yes
          </span>`;
      } else {
        cremationElement.innerHTML = `
          <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 border border-gray-200">
            <i class="fas fa-times-circle mr-1"></i> No
          </span>`;
      }
      
      // Handle Death Certificate Image
      const deathCertAvailable = document.getElementById('customDeathCertificateAvailable');
      const deathCertNotAvailable = document.getElementById('customDeathCertificateNotAvailable');
      const deathCertImage = document.getElementById('customDeathCertificateImage');

      if (data.deathcert_url && data.deathcert_url !== '') {
        const deathCertPath = '../customer/booking/uploads/' + data.deathcert_url.replace(/^uploads\//, '');
        
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
      const paymentProofImage = document.getElementById('customPaymentProofImage');
      const modalPaymentProofImage = document.getElementById('customModalPaymentProofImage');
      const paymentProofContainer = paymentProofImage.parentElement;
      const modalPaymentProofContainer = modalPaymentProofImage.parentElement;

      if (data.payment_url && data.payment_url !== '') {
        const paymentProofPath = '../customer/booking/uploads/' + data.payment_url.replace(/^uploads\//, '');
        console.log("Payment Proof Path:", paymentProofPath);
        
       const errorHandler = function() {
            console.error("Failed to load payment proof image:", paymentProofPath);
            // Create a placeholder instead of loading another image
            const placeholderHTML = `
                <div class="flex flex-col items-center justify-center py-8 px-4 bg-gray-50">
                    <i class="fas fa-exclamation-circle text-gray-400 text-3xl mb-2"></i>
                    <p class="text-gray-500 text-center">Image could not be loaded</p>
                </div>`;
            paymentProofContainer.innerHTML = placeholderHTML;
            modalPaymentProofContainer.innerHTML = placeholderHTML;
        };
        
        paymentProofImage.onerror = errorHandler;
        modalPaymentProofImage.onerror = errorHandler;
        
        // Set the source for both images
        paymentProofImage.src = paymentProofPath;
        modalPaymentProofImage.src = paymentProofPath;
        
        // Initialize Tesseract OCR processing
        processReceiptWithTesseract(paymentProofPath, 'custom');
      } else {
        // Create a placeholder for when no payment proof exists
        const placeholderHTML = `
            <div class="flex flex-col items-center justify-center py-8 px-4 bg-gray-50">
                <i class="fas fa-exclamation-circle text-gray-400 text-3xl mb-2"></i>
                <p class="text-gray-500 text-center">No payment proof provided</p>
            </div>`;
        paymentProofContainer.innerHTML = placeholderHTML;
        modalPaymentProofContainer.innerHTML = placeholderHTML;
      }
      
      // Tesseract OCR processing function
        function processReceiptWithTesseract(imagePath, type = 'custom') {
            const processingIndicator = document.getElementById(`${type}ProcessingIndicator`);
            const extractionMessage = document.getElementById(`${type}ExtractionMessage`);
            const amountInput = document.getElementById(`${type}AmountPaidInput`);
            
            // Show processing indicator
            processingIndicator.classList.remove('hidden');
            extractionMessage.classList.add('hidden');
            
            // Initialize Tesseract.js
            Tesseract.recognize(
                imagePath,
                'eng',
                {
                    logger: m => console.log(m),
                    tessedit_char_whitelist: '0123456789.,$₱Pp ',
                }
            ).then(({ data: { text } }) => {
                console.log('OCR Result:', text);
                
                // Hide processing indicator
                processingIndicator.classList.add('hidden');
                
                // Extract amount from text
                const amountRegex = /(?:₱|P|p|\$)\s*(\d{1,3}(?:,\d{3})*(?:\.\d{2})?)|\d{1,3}(?:,\d{3})*(?:\.\d{2})/g;
                const matches = text.match(amountRegex);
                
                if (matches && matches.length > 0) {
                    // Clean the amount (remove currency symbols and commas)
                    let extractedAmount = matches[0].replace(/[₱Pp$,]/g, '').trim();
                    
                    // If the amount ends with . or .0, add two zeros
                    if (extractedAmount.endsWith('.')) {
                        extractedAmount += '00';
                    } else if (extractedAmount.endsWith('.0')) {
                        extractedAmount += '0';
                    }
                    
                    // Set the extracted amount in the input field
                    if (amountInput) {
                        amountInput.value = parseFloat(extractedAmount).toFixed(2);
                        extractionMessage.textContent = `Extracted amount: ₱${parseFloat(extractedAmount).toFixed(2)}`;
                        extractionMessage.classList.remove('hidden');
                        extractionMessage.classList.add('text-green-600');
                    }
                } else {
                    extractionMessage.textContent = 'No amount found in receipt. Please enter manually.';
                    extractionMessage.classList.remove('hidden');
                    extractionMessage.classList.add('text-yellow-600');
                }
            }).catch(err => {
                console.error('OCR Error:', err);
                processingIndicator.classList.add('hidden');
                extractionMessage.textContent = 'Failed to process receipt. Please enter amount manually.';
                extractionMessage.classList.remove('hidden');
                extractionMessage.classList.add('text-red-600');
            });
        }
      
      // Handle Casket Details
      if (data.casket_id && data.casket_name) {
        document.getElementById('casketName').textContent = data.casket_name;
        document.getElementById('casketPrice').textContent = 
    "₱" + (parseFloat(data.casket_price) || 0).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
        
        const casketImage = document.getElementById('casketImage');
        if (data.casket_image) {
          casketImage.src = data.casket_image;
          casketImage.onerror = function() {
            this.src = '../admin/caskets/default_casket.jpg';
          };
        } else {
          casketImage.src = '../admin/caskets/default_casket.jpg';
        }
      } else {
        document.getElementById('casketName').textContent = "No casket selected";
        document.getElementById('casketPrice').textContent = "₱0.00";
        document.getElementById('casketImage').src = '../admin/caskets/default_casket.jpg';
      }
      
      // Handle Flower Design Details
      if ( data.flower_name) {
        document.getElementById('flowerDesignName').textContent = data.flower_name;
        
        
      
      } else {
        document.getElementById('flowerDesignName').textContent = "No flower design selected";
      }
      
      // Handle Inclusions
      const inclusionsList = document.getElementById('inclusionsList');
      inclusionsList.innerHTML = '';

      try {
        const inclusions = JSON.parse(data.inclusions); // data.inclusions is a JSON string

        if (Array.isArray(inclusions) && inclusions.length > 0) {
          inclusions.forEach(item => {
            const li = document.createElement('li');
            li.className = 'flex items-start mb-2';
            li.innerHTML = `
              <span class="flex-shrink-0 mt-1 mr-2">
                <i class="fas fa-check-circle text-green-500"></i>
              </span>
              <span class="text-gray-800">
                ${item}
              </span>
            `;
            inclusionsList.appendChild(li);
          });
        } else {
          const li = document.createElement('li');
          li.className = 'text-gray-600';
          li.textContent = 'No inclusions selected';
          inclusionsList.appendChild(li);
        }
      } catch (error) {
        console.error('Error parsing inclusions:', error);
        const li = document.createElement('li');
        li.className = 'text-red-500';
        li.textContent = 'Failed to load inclusions.';
        inclusionsList.appendChild(li);
      }

      
      // Show the modal
      const modal = document.getElementById("customDetailsModal");
      modal.classList.remove("hidden");
      modal.classList.add("flex");
      document.body.classList.add("overflow-hidden");
      
      // Set the current booking ID for payment
      currentCustomBookingIdForPayment = data.booking_id;
    })
    .catch(error => {
      console.error('Error:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to load custom booking details. Please try again.',
      });
    });
    // Set up image viewer listeners for this modal
setupImageViewerListeners('customDetailsModal');
}

function closeCustomModal() {
    const modal = document.getElementById("customDetailsModal");
    if (modal) {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        document.body.classList.remove("overflow-hidden");
    }
}

function confirmCustomAccept() {
    // Get the booking ID from the modal
    const bookingId = currentCustomBookingIdForPayment;
    
    // Fetch booking details again to get all the required fields
    fetch('bookingpage/get_custom_details_for_accept.php?id=' + bookingId)
        .then(response => response.json())
        .then(response => {
            console.log('RECEIVED DATA: ', response);
            
            // Check if the response was successful
            if (!response.success) {
                throw new Error(response.error || 'Failed to load booking details');
            }
            
            // Access the nested data object
            const data = response.data;
            
            // Set all the hidden fields
            document.getElementById('customBookingIdForPayment').value = bookingId;
            
            // Customer information
            document.getElementById('customCustomerId').value = data.customerID || '';
            document.getElementById('customCustomerFirstName').value = data.first_name || '';
            console.log('first_name: ', data.first_name);
            document.getElementById('customCustomerMiddleName').value = data.middle_name || '';
            document.getElementById('customCustomerLastName').value = data.last_name || '';
            document.getElementById('customCustomerSuffix').value = data.suffix || '';
            document.getElementById('customCustomerEmail').value = data.email || '';
            document.getElementById('customCustomerPhone').value = data.phone_number || '';
            
            // Deceased information
            document.getElementById('customDeceasedFname').value = data.deceased_fname || '';
            document.getElementById('customDeceasedMname').value = data.deceased_midname || '';
            document.getElementById('customDeceasedLname').value = data.deceased_lname || '';
            document.getElementById('customDeceasedSuffix').value = data.deceased_suffix || '';
            document.getElementById('customDeceasedAddressInput').value = data.deceased_address || '';

            // Dates information
            document.getElementById('customDeceasedBirthInput').value = data.deceased_birth || '';
            document.getElementById('customDeceasedDeathInput').value = data.deceased_dodeath || '';
            document.getElementById('customDeceasedBurialInput').value = data.deceased_dateOfBurial || '';            
            
            // Package components
            document.getElementById('customCasketId').value = data.casket_id || '';
            document.getElementById('customFlowerId').value = data.flower_design || '';
            document.getElementById('customInclusions').value = JSON.stringify(data.inclusions) || '[]';
            
            // Other information
            document.getElementById('customBranchId').value = data.branch_id || '';
            document.getElementById('customInitialPrice').value = data.initial_price || '';
            document.getElementById('customDeathCertUrl').value = data.deathcert_url || '';
            document.getElementById('customWithCremateInput').value = data.with_cremate || 'no';
            
            // Show the payment modal
            openCustomPaymentModal();
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load booking details for acceptance. Please try again.',
            });
        });
}

function openCustomPaymentModal() {
    const modal = document.getElementById("customPaymentModal");
    modal.classList.remove("hidden");
    document.body.classList.add("overflow-hidden");
}

function closeCustomPaymentModal() {
    const modal = document.getElementById("customPaymentModal");
    modal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
}

function confirmCustomDecline() {
    const bookingId = currentCustomBookingIdForPayment;
    document.getElementById('customBookingIdForDecline').value = bookingId;
    
    const modal = document.getElementById("customDeclineReasonModal");
    modal.classList.remove("hidden");
    document.body.classList.add("overflow-hidden");
}

function closeCustomDeclineReasonModal() {
    const modal = document.getElementById("customDeclineReasonModal");
    modal.classList.add("hidden");
    document.body.classList.remove("overflow-hidden");
    
    // Clear the form
    document.getElementById('customDeclineReason').value = '';
}

function selectCustomDeclineReason(reason) {
    document.getElementById('customDeclineReason').value = reason;
}

// Handle form submission for declining booking
document.getElementById('customDeclineReasonForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const bookingId = document.getElementById('customBookingIdForDecline').value;
    const reason = document.getElementById('customDeclineReason').value;
    
    if (!reason.trim()) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please provide a reason for declining this booking.',
        });
        return;
    }
    
    Swal.fire({
        title: 'Confirm Decline',
        text: 'Are you sure you want to decline this booking?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, decline it'
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit the decline via AJAX
            fetch('bookingpage/decline_custom_booking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `bookingId=${bookingId}&reason=${encodeURIComponent(reason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Booking Declined',
                        text: 'The booking has been successfully declined.',
                    }).then(() => {
                        // Close all modals and refresh the page
                        closeCustomDeclineReasonModal();
                        closeCustomModal();
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to decline booking. Please try again.',
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while processing your request.',
                });
            });
        }
    });
});

// Handle form submission for accepting booking with payment
document.getElementById('customPaymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const bookingId = document.getElementById('customBookingIdForPayment').value;
    const amountPaid = document.getElementById('customAmountPaidInput').value;
    const paymentMethod = document.getElementById('customPaymentMethod').value;
    
    if (!amountPaid || parseFloat(amountPaid) <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Amount',
            text: 'Please enter a valid payment amount.',
        });
        return;
    }
    
    if (!paymentMethod) {
        Swal.fire({
            icon: 'error',
            title: 'Payment Method Required',
            text: 'Please select a payment method.',
        });
        return;
    }
    
    Swal.fire({
        title: 'Confirm Acceptance',
        text: 'Are you sure you want to accept this booking with the provided payment details?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, accept booking'
    }).then((result) => {
        if (result.isConfirmed) {
            // Submit the form data via AJAX
            const formData = new FormData(document.getElementById('customPaymentForm'));
            console.log("FormData contents:");
            for (let [key, value] of formData.entries()) {
                console.log(key, value);
            }
            
            fetch('bookingpage/accept_custom_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Booking Accepted',
                        text: 'The booking has been successfully accepted and payment recorded.',
                    }).then(() => {
                        // Close all modals and refresh the page
                        closeCustomPaymentModal();
                        closeCustomModal();
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.error || 'Failed to accept booking. Please try again.',
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'An error occurred while processing your request.',
                });
            });
        }
    });
});

// Close modal when clicking outside of it
window.addEventListener('click', function(event) {
    const customDetailsModal = document.getElementById('customDetailsModal');
    const customDeclineReasonModal = document.getElementById('customDeclineReasonModal');
    const customPaymentModal = document.getElementById('customPaymentModal');
    
    if (event.target === customDetailsModal) {
        closeCustomModal();
    }
    
    if (event.target === customDeclineReasonModal) {
        closeCustomDeclineReasonModal();
    }
    
    if (event.target === customPaymentModal) {
        closeCustomPaymentModal();
    }
});
</script>


<script>
// Function to open LifePlan details modal
function openLifeplanDetails(lifeplanId) {
  // Fetch lifeplan details via AJAX
  fetch('bookingpage/get_lifeplan_details.php?id=' + lifeplanId)
    .then(response => response.json())
    .then(response => {
      // Check if the response is successful
      if (!response.success) {
        throw new Error(response.error || 'Failed to load lifeplan details');
      }
      
      // Get the data from the response
      const data = response.data;
      
      // Populate modal with the basic details
      document.getElementById('lifeplanBookingId').textContent = '#LP-' + 
        new Date(data.initial_date).getFullYear() + '-' + 
        String(data.lpbooking_id).padStart(3, '0');
      document.getElementById('lifeplanCustomerName').textContent = data.customer_name;
      document.getElementById('lifeplanContactNumber').textContent = data.contact_number || "Not provided";
      document.getElementById('lifeplanEmailAddress').textContent = data.email;
      document.getElementById('lifeplanAddress').textContent = data.address || "Not provided";
      document.getElementById('lifeplanType').textContent = data.service_name || "Custom LifePlan";
      document.getElementById('lifeplanDateRequested').textContent = data.initial_date_formatted;
      document.getElementById('lifeplanPrice').textContent = "₱" + data.package_price_formatted;
      document.getElementById('lifeplanTerms').textContent = data.payment_duration ? data.payment_duration + " years" : "Not specified";


      // Beneficiary information
      document.getElementById('beneficiaryFullName').textContent = data.beneficiary_name || "Not provided";
      document.getElementById('beneficiaryRelationship').textContent = data.relationship_to_client || "Not provided";
      document.getElementById('beneficiaryContact').textContent = data.phone || "Not provided";
      document.getElementById('beneficiaryAddress').textContent = data.benefeciary_address || "Same as customer";
      
      // Update lifeplan status
      const statusElement = document.getElementById('lifeplanBookingStatus');
      if (data.booking_status === 'Confirmed') {
        statusElement.innerHTML = `
          <span class="px-3 py-1.5 text-sm font-medium rounded-full bg-green-100 text-green-800 flex items-center">
            <i class="fas fa-check-circle mr-1.5"></i>
            Confirmed
          </span>`;
      } else if (data.booking_status === 'Cancelled') {
        statusElement.innerHTML = `
          <span class="px-3 py-1.5 text-sm font-medium rounded-full bg-red-100 text-red-800 flex items-center">
            <i class="fas fa-times-circle mr-1.5"></i>
            Cancelled
          </span>`;
      } else {
        statusElement.innerHTML = `
          <span class="px-3 py-1.5 text-sm font-medium rounded-full bg-yellow-100 text-sidebar-accent flex items-center">
            <i class="fas fa-clock mr-1.5"></i>
            Pending
          </span>`;
      }
      
      // Handle Valid ID Image
      const validIdAvailable = document.getElementById('validIdAvailable');
      const validIdNotAvailable = document.getElementById('validIdNotAvailable');
      const validIdImage = document.getElementById('validIdImage');

      if (data.image_path && data.image_path !== '') {
        const validIdPath = data.image_path;
        document.getElementById('validIdImage').src = validIdPath;
        
        validIdImage.onerror = function() {
          console.error("Failed to load valid ID image:", validIdPath);
          validIdAvailable.classList.add('hidden');
          validIdNotAvailable.classList.remove('hidden');
        };
        
        validIdImage.src = validIdPath;
        validIdAvailable.classList.remove('hidden');
        validIdNotAvailable.classList.add('hidden');
      } else {
        validIdAvailable.classList.add('hidden');
        validIdNotAvailable.classList.remove('hidden');
      }

      // Handle Payment Proof Image - Updated for Lifeplan
    const paymentProofImage = document.getElementById('lifeplanPaymentProofImage');
    const modalPaymentProofImage = document.getElementById('modalLifeplanPaymentProofImage');
    const paymentProofContainer = paymentProofImage.parentElement;
    const modalPaymentProofContainer = modalPaymentProofImage.parentElement;
    
    if (data.payment_url && data.payment_url !== '') {
      // Use relative path with ../ to navigate up and then to the customer/booking/uploads folder
      const paymentProofPath = '../customer/booking/uploads/' + data.payment_url.replace(/^uploads\//, '');
      console.log("Payment Proof Path:", paymentProofPath);
      
      // Set error handler before setting src for both images
      const errorHandler = function() {
        console.error("Failed to load payment proof image:", paymentProofPath);
        // Create a placeholder instead of loading another image
        const placeholderHTML = `
          <div class="flex flex-col items-center justify-center py-8 px-4 bg-gray-50">
            <i class="fas fa-exclamation-circle text-gray-400 text-3xl mb-2"></i>
            <p class="text-gray-500 text-center">Image could not be loaded</p>
          </div>`;
        paymentProofContainer.innerHTML = placeholderHTML;
        modalPaymentProofContainer.innerHTML = placeholderHTML;
      };
      
      paymentProofImage.onerror = errorHandler;
      modalPaymentProofImage.onerror = errorHandler;
      
      // Set the source for both images
      paymentProofImage.src = paymentProofPath;
      modalPaymentProofImage.src = paymentProofPath;
      
      // Initialize Tesseract OCR processing
      processPaymentProofWithTesseract(paymentProofPath, 'lifeplan');
    } else {
      // Create a placeholder for when no payment proof exists
      const placeholderHTML = `
        <div class="flex flex-col items-center justify-center py-8 px-4 bg-gray-50">
          <i class="fas fa-exclamation-circle text-gray-400 text-3xl mb-2"></i>
          <p class="text-gray-500 text-center">No payment proof provided</p>
        </div>`;
      paymentProofContainer.innerHTML = placeholderHTML;
      modalPaymentProofContainer.innerHTML = placeholderHTML;
    }

// Tesseract OCR processing function
function processPaymentProofWithTesseract(imagePath, type = 'lifeplan') {
  const processingIndicator = document.getElementById(`${type}ProcessingIndicator`);
  const extractionMessage = document.getElementById(`${type}ExtractionMessage`);
  const amountInput = document.getElementById(`${type}AmountPaidInput`);
  
  // Show processing indicator
  processingIndicator.classList.remove('hidden');
  
  // Load Tesseract.js and process the image
  Tesseract.recognize(
    imagePath,
    'eng',
    { logger: m => console.log(m) }
  ).then(({ data: { text } }) => {
    console.log("OCR Result:", text);
    
    // Hide processing indicator
    processingIndicator.classList.add('hidden');
    
    // Try to extract amount from the text
    const amountRegex = /(?:₱|PHP|P|Php|Amount|Total)\s*[:=]?\s*([\d,]+\.\d{2})/i;
    const match = text.match(amountRegex);
    
    if (match && match[1]) {
      const extractedAmount = match[1].replace(/,/g, '');
      amountInput.value = extractedAmount;
      extractionMessage.textContent = `Detected amount: ₱${extractedAmount}`;
      extractionMessage.classList.remove('hidden');
      extractionMessage.classList.add('text-green-600');
    } else {
      extractionMessage.textContent = 'No amount detected in receipt. Please enter manually.';
      extractionMessage.classList.remove('hidden');
      extractionMessage.classList.add('text-yellow-600');
    }
  }).catch(err => {
    console.error("OCR Error:", err);
    processingIndicator.classList.add('hidden');
    extractionMessage.textContent = 'Failed to process receipt. Please enter amount manually.';
    extractionMessage.classList.remove('hidden');
    extractionMessage.classList.add('text-red-600');
  });
}
            
      // Show the modal
      const modal = document.getElementById("lifeplanDetailsModal");
      modal.classList.remove("hidden");
      modal.classList.add("flex");
      document.body.classList.add("overflow-hidden");
      
      // Set the lifeplan ID for decline/accept actions
      document.getElementById('lifeplanIdForDecline').value = data.lpbooking_id;
    })
    .catch(error => {
      console.error('Error:', error);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Failed to load lifeplan details. Please try again.',
      });
    });
    
    // Set up image viewer listeners for this modal
setupImageViewerListeners('lifeplanDetailsModal');
}

function closeLifeplanModal() {
    const modal = document.getElementById("lifeplanDetailsModal");
    if (modal) {
        modal.classList.add("hidden");
        modal.classList.remove("flex");
        document.body.classList.remove("overflow-hidden");
    }
}

let currentLifeplanIdForPayment = null;

function confirmLifeplanAccept() {
    // Get the lifeplan ID from the modal
    const lifeplanId = document.getElementById('lifeplanIdForDecline').value;
    let currentLifeplanIdForPayment = lifeplanId;

    // Fetch lifeplan details to get all the required fields
    fetch('bookingpage/get_lifeplan_for_accept.php?id=' + encodeURIComponent(lifeplanId))
        .then(response => response.json())
        .then(responseData => {
            
            const data = responseData.data;
            if (!responseData.success) {
                throw new Error(data.error || 'Failed to load lifeplan details');
            }

            console.log("Fetched successfully");

            // Customer information
            console.log("Customer ID:", data.customer_id);
            document.getElementById('lifeplanCustomerID').value = data.customer_id || '';

            document.getElementById('lifeplanIdForPayment').value = data.lpbooking_id || '';

            console.log("Branch ID:", data.branch_id);
            document.getElementById('lifeplanCustomerBranch').value = data.branch_id || '';

            console.log("First Name:", data.first_name);
            document.getElementById('lifeplanCustomerFirstName').value = data.first_name || '';

            console.log("Middle Name:", data.middle_name);
            document.getElementById('lifeplanCustomerMiddleName').value = data.middle_name || '';

            console.log("Last Name:", data.last_name);
            document.getElementById('lifeplanCustomerLastName').value = data.last_name || '';

            console.log("Suffix:", data.suffix);
            document.getElementById('lifeplanCustomerSuffix').value = data.suffix || '';

            console.log("Email:", data.email);
            document.getElementById('lifeplanCustomerEmail').value = data.email || '';

            console.log("Phone Number:", data.contact_number);
            document.getElementById('lifeplanCustomerPhone').value = data.contact_number || '';

            // Beneficiary information (note: check for spelling errors in keys)
            console.log("Beneficiary First Name:", data.benefeciary_fname);
            document.getElementById('beneficiaryFirstName').value = data.benefeciary_fname || '';

            console.log("Beneficiary Middle Name:", data.benefeciary_mname);
            document.getElementById('beneficiaryMiddleName').value = data.benefeciary_mname || '';

            console.log("Beneficiary Last Name:", data.benefeciary_lname);
            document.getElementById('beneficiaryLastName').value = data.benefeciary_lname || '';

            console.log("Beneficiary Suffix:", data.benefeciary_suffix);
            document.getElementById('beneficiarySuffix').value = data.benefeciary_suffix || '';

            console.log("Beneficiary Birthdate:", data.benefeciary_birth);
            document.getElementById('beneficiaryBirthdate').value = data.benefeciary_birth || '';

            console.log("Beneficiary Address:", data.benefeciary_address);
            document.getElementById('beneficiaryAddressInput').value = data.benefeciary_address || '';

            console.log("Relationship to Client:", data.relationship_to_client);
            document.getElementById('relationshipWithClient').value = data.relationship_to_client || '';

            // Plan information
            console.log("Service ID:", data.service_id);
            document.getElementById('lifeplanServiceId').value = data.service_id || '';

            console.log("Package Price:", data.package_price);
            document.getElementById('lifeplanPackagePrice').value = data.package_price || '';

            console.log("Payment Duration:", data.payment_duration);
            document.getElementById('lifeplanPaymentDuration').value = data.payment_duration || '';

            console.log("Valid ID URL:", data.image_path);
            document.getElementById('lifeplanValidIdUrl').value = data.image_path || '';

            console.log("Initial Date:", data.initial_date);
            document.getElementById('lifeplanInitialDate').value = data.initial_date || '';

            console.log("End Date:", data.end_date);
            document.getElementById('lifeplanEndDate').value = data.end_date || '';

            console.log("With Cremate:", data.with_cremate);
            document.getElementById('withCremateInput').value = data.with_cremate ? 'yes' : 'no';

            // Show the payment modal
            openLifeplanPaymentModal();
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load lifeplan details',
            });
        });
}


function openLifeplanPaymentModal() {
    const modal = document.getElementById("lifeplanPaymentModal");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    document.body.classList.add("overflow-hidden");
    console.log(currentLifeplanIdForPayment);
}

function closeLifeplanPaymentModal() {
    const modal = document.getElementById("lifeplanPaymentModal");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    document.body.classList.remove("overflow-hidden");
}

document.getElementById('lifeplanPaymentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const amountPaid = parseFloat(document.getElementById('lifeplanAmountPaidInput').value);
    const paymentMethod = document.getElementById('lifeplanPaymentMethod').value;
    
    if (!amountPaid || !paymentMethod) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please fill in all payment details',
        });
        return;
    }
    
    // Prepare the data to send - include all hidden fields
    const formData = new FormData(this);
    
    // Add additional data that might not be in the form
    formData.append('action', 'acceptLifeplan');
    
    // Calculate balance for display in confirmation
    const packagePrice = parseFloat(document.getElementById('lifeplanPackagePrice').value) || 0;
    const balance = packagePrice - parseFloat(amountPaid);
    const paymentStatus = balance <= 0 ? 'Fully Paid' : 'With Balance';

    if (amountPaid <= 0) {
        console.log('Validation failed - amount paid is zero or negative'); // Log validation failure
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Amount paid must be greater than ₱0.00',
        });
        return;
    }
    
    // Add validation for amountPaid being greater than packagePrice
    if (amountPaid > packagePrice) {
        console.log('Validation failed - amount paid exceeds package price'); // Log validation failure
        Swal.fire({
            icon: 'error',
            title: 'Error',
            html: `Amount paid <strong>₱${amountPaid.toFixed(2)}</strong> is higher than the package price <strong>₱${packagePrice.toFixed(2)}</strong>`,
        });
        return;
    }
    
    // Show confirmation dialog with payment summary
    Swal.fire({
        title: 'Confirm Payment Details',
        html: `<div class="text-left">
            <p><strong>Amount Paid:</strong> ₱${parseFloat(amountPaid).toFixed(2)}</p>
            <p><strong>Payment Method:</strong> ${paymentMethod}</p>
            <p><strong>Plan Price:</strong> ₱${packagePrice.toFixed(2)}</p>
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
            fetch('bookingpage/process_lifeplan.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'LifePlan accepted and payment recorded successfully!',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        closeLifeplanPaymentModal();
                        closeLifeplanModal();
                        // Refresh the page to update the table
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to process lifeplan');
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



function confirmLifeplanDecline() {
    const lifeplanId = document.getElementById('lifeplanIdForDecline').value;
    
    // Open the decline reason modal
    openLifeplanDeclineReasonModal();
}

function openLifeplanDeclineReasonModal() {
    const modal = document.getElementById("lifeplanDeclineReasonModal");
    modal.classList.remove("hidden");
    modal.classList.add("flex");
    document.body.classList.add("overflow-hidden");
}

function closeLifeplanDeclineReasonModal() {
    const modal = document.getElementById("lifeplanDeclineReasonModal");
    modal.classList.add("hidden");
    modal.classList.remove("flex");
    document.body.classList.remove("overflow-hidden");
}

function selectLifeplanDeclineReason(reason) {
    document.getElementById('lifeplanCustomReason').value = reason;
}

// Handle lifeplan decline form submission
document.getElementById('lifeplanDeclineReasonForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const lifeplanId = document.getElementById('lifeplanIdForDecline').value;
    const customReason = document.getElementById('lifeplanCustomReason').value;
    
    if (!customReason) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please provide a reason for declining this lifeplan',
        });
        return;
    }
    
    Swal.fire({
        title: 'Confirm Decline',
        html: `<div class="text-left">
            <p>Are you sure you want to decline this LifePlan?</p>
            <p><strong>Reason:</strong> ${customReason}</p>
        </div>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Decline LifePlan',
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
            fetch('bookingpage/decline_lifeplan.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=declineLifeplan&lifeplanId=${lifeplanId}&reason=${encodeURIComponent(customReason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'LifePlan has been declined',
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        closeLifeplanDeclineReasonModal();
                        closeLifeplanModal();
                        // Refresh the page to update the table
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to decline lifeplan');
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

// Close modals when clicking outside content
window.addEventListener("click", function (event) {
    const lifeplanModal = document.getElementById("lifeplanDetailsModal");
    if (lifeplanModal && event.target === lifeplanModal) {
        closeLifeplanModal();
    }
    
    const declineModal = document.getElementById("lifeplanDeclineReasonModal");
    if (declineModal && event.target === declineModal) {
        closeLifeplanDeclineReasonModal();
    }
});

// Close modals on 'Escape' key press
window.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        closeLifeplanModal();
        closeLifeplanDeclineReasonModal();
    }
});

function enlargeImage(imgElement) {
    const enlargedImage = document.getElementById('enlargedImage');
    const overlay = document.getElementById('imageEnlargerOverlay');
    
    enlargedImage.src = imgElement.src;
    overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden'; // Prevent scrolling when overlay is open
  }

  function closeEnlargedImage(event) {
    if (event) {
      event.stopPropagation(); // Prevent the overlay click from triggering when clicking the button
    }
    const overlay = document.getElementById('imageEnlargerOverlay');
    overlay.classList.add('hidden');
    document.body.style.overflow = ''; // Re-enable scrolling
  }

  // Close when pressing Escape key
  document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      closeEnlargedImage();
    }
  });


// Function to load bookings via AJAX
function loadBookings(page) {
    // Show loading indicator
    document.getElementById('loadingIndicator').classList.remove('hidden');
    
    // Get current search and filter values
    const searchQuery = document.getElementById('bookingSearchInput').value || 
                       document.getElementById('bookingSearchInputMobile').value || '';
    const sortBy = document.querySelector('.filter-option.active')?.dataset.sort || 
                  document.querySelector('.filter-option-mobile.active')?.dataset.sort || 'newest';
    
    // Create AJAX request
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'bookingpage/ajax_pagination_pending_table.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                
                // Update table body
                document.getElementById('bookingTableBody').innerHTML = response.html;
                
                // Update pagination info
                document.getElementById('paginationInfo').textContent = response.paginationInfo;
                
                // Update booking counter
                document.getElementById('totalBookings').textContent = response.totalBookings > 0 ? 
                    response.totalBookings : "No bookings";
                
                // Update pagination buttons
                updatePaginationButtons(response.currentPage, response.totalPages);
                
            } catch (e) {
                console.error('Error parsing response:', e);
            }
        } else {
            console.error('Request failed. Status:', this.status);
        }
        
        // Hide loading indicator
        document.getElementById('loadingIndicator').classList.add('hidden');
    };
    
    xhr.onerror = function() {
        console.error('Request failed');
        document.getElementById('loadingIndicator').classList.add('hidden');
    };
    
    // Send request with parameters
    xhr.send(`page=${page}&search=${encodeURIComponent(searchQuery)}&sort=${sortBy}`);
}

function updatePaginationButtons(currentPage, totalPages) {
    const paginationContainer = document.getElementById('paginationContainer');
    if (!paginationContainer || totalPages <= 1) return;
    
    // Get all pagination buttons
    const buttons = paginationContainer.querySelectorAll('button');
    
    // Update button states
    buttons.forEach(button => {
        const buttonText = button.textContent.trim();
        const isNumberButton = !isNaN(parseInt(buttonText));
        
        // Reset all buttons
        button.classList.remove('opacity-50', 'pointer-events-none');
        button.classList.remove('bg-sidebar-accent', 'text-white');
        button.classList.add('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
        
        // Handle number buttons
        if (isNumberButton) {
            const pageNum = parseInt(buttonText);
            if (pageNum === currentPage) {
                button.classList.add('bg-sidebar-accent', 'text-white');
                button.classList.remove('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
            }
        }
        
        // Handle navigation buttons
        if (buttonText === '«') { // First page button
            if (currentPage === 1) {
                button.classList.add('opacity-50', 'pointer-events-none');
            }
        } else if (buttonText === '‹') { // Previous page button
            if (currentPage === 1) {
                button.classList.add('opacity-50', 'pointer-events-none');
            }
        } else if (buttonText === '›') { // Next page button
            if (currentPage === totalPages) {
                button.classList.add('opacity-50', 'pointer-events-none');
            }
        } else if (buttonText === '»') { // Last page button
            if (currentPage === totalPages) {
                button.classList.add('opacity-50', 'pointer-events-none');
            }
        }
    });
    
    // Update the displayed page numbers
    updatePageNumberButtons(currentPage, totalPages);
}

function updatePageNumberButtons(currentPage, totalPages) {
    const paginationContainer = document.getElementById('paginationContainer');
    if (!paginationContainer) return;
    
    // Find the number buttons (between navigation buttons)
    const buttons = Array.from(paginationContainer.querySelectorAll('button'));
    const numberButtons = buttons.filter(button => !isNaN(parseInt(button.textContent.trim())));
    
    if (numberButtons.length === 0) return;
    
    // Determine which pages to show (always show 3 pages if possible)
    let startPage, endPage;
    
    if (totalPages <= 3) {
        // Show all pages if 3 or fewer
        startPage = 1;
        endPage = totalPages;
    } else {
        // Show current page in the middle when possible
        if (currentPage <= 2) {
            startPage = 1;
            endPage = 3;
        } else if (currentPage >= totalPages - 1) {
            startPage = totalPages - 2;
            endPage = totalPages;
        } else {
            startPage = currentPage - 1;
            endPage = currentPage + 1;
        }
    }
    
    // Update the number buttons
    let pageNum = startPage;
    numberButtons.forEach(button => {
        if (pageNum <= endPage) {
            button.textContent = pageNum;
            button.style.display = 'inline-block';
            
            // Update active state
            button.classList.remove('bg-sidebar-accent', 'text-white');
            button.classList.add('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
            
            if (pageNum === currentPage) {
                button.classList.add('bg-sidebar-accent', 'text-white');
                button.classList.remove('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
            }
            
            pageNum++;
        } else {
            button.style.display = 'none';
        }
    });
}
// Add event listeners for filter options
document.querySelectorAll('.filter-option, .filter-option-mobile').forEach(option => {
    option.addEventListener('click', function() {
        // Remove active class from all options
        document.querySelectorAll('.filter-option, .filter-option-mobile').forEach(el => {
            el.classList.remove('active');
        });
        
        // Add active class to clicked option
        this.classList.add('active');
        
        // Show filter indicator
        document.getElementById('filterIndicator').classList.remove('hidden');
        document.getElementById('filterIndicatorMobile').classList.remove('hidden');
        
        // Reload bookings with new filter
        loadBookings(1);
    });
});

// Add event listeners for search inputs
const searchInputs = ['bookingSearchInput', 'bookingSearchInputMobile'];
searchInputs.forEach(id => {
    const input = document.getElementById(id);
    if (input) {
        let searchTimeout;
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadBookings(1);
            }, 500);
        });
    }
});

function loadCustomBookings(page) {
    // Show loading indicator
    document.getElementById('loadingIndicator').classList.remove('hidden');
    
    // Get current search and filter values
    const searchQuery = document.getElementById('customBookingSearchInput').value || 
                       document.getElementById('customBookingSearchInputMobile').value || '';
    const sortBy = document.querySelector('.custom-filter-option.active')?.dataset.sort || 
                  document.querySelector('.custom-filter-option-mobile.active')?.dataset.sort || 'newest';
    
    // Create AJAX request
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'bookingpage/ajax_pagination_custom_table.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                
                // Update table body
                document.getElementById('customBookingTableBody').innerHTML = response.html;
                
                // Update pagination info
                document.getElementById('customPaginationInfo').textContent = response.paginationInfo;
                
                // Update booking counter if needed
                if (document.getElementById('totalCustomBookings')) {
                    document.getElementById('totalCustomBookings').textContent = response.totalBookings > 0 ? 
                        response.totalBookings : "No bookings";
                }
                
                // Update pagination buttons
                updateCustomPaginationButtons(response.currentPage, response.totalPages);
                
            } catch (e) {
                console.error('Error parsing response:', e);
            }
        } else {
            console.error('Request failed. Status:', this.status);
        }
        
        // Hide loading indicator
        document.getElementById('loadingIndicator').classList.add('hidden');
    };
    
    xhr.onerror = function() {
        console.error('Request failed');
        document.getElementById('loadingIndicator').classList.add('hidden');
    };
    
    // Send request with parameters
    xhr.send(`page=${page}&search=${encodeURIComponent(searchQuery)}&sort=${sortBy}`);
}

function updateCustomPaginationButtons(currentPage, totalPages) {
    const paginationContainer = document.getElementById('customPaginationContainer');
    if (!paginationContainer || totalPages <= 1) return;
    
    // Get all pagination buttons
    const buttons = paginationContainer.querySelectorAll('button');
    
    // Update button states
    buttons.forEach(button => {
        const buttonText = button.textContent.trim();
        const isNumberButton = !isNaN(parseInt(buttonText));
        
        // Reset all buttons
        button.classList.remove('opacity-50', 'pointer-events-none');
        button.classList.remove('bg-sidebar-accent', 'text-white');
        button.classList.add('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
        
        // Handle number buttons
        if (isNumberButton) {
            const pageNum = parseInt(buttonText);
            if (pageNum === currentPage) {
                button.classList.add('bg-sidebar-accent', 'text-white');
                button.classList.remove('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
            }
        }
        
        // Handle navigation buttons
        if (buttonText === '«') { // First page button
            if (currentPage === 1) {
                button.classList.add('opacity-50', 'pointer-events-none');
            }
        } else if (buttonText === '‹') { // Previous page button
            if (currentPage === 1) {
                button.classList.add('opacity-50', 'pointer-events-none');
            }
        } else if (buttonText === '›') { // Next page button
            if (currentPage === totalPages) {
                button.classList.add('opacity-50', 'pointer-events-none');
            }
        } else if (buttonText === '»') { // Last page button
            if (currentPage === totalPages) {
                button.classList.add('opacity-50', 'pointer-events-none');
            }
        }
    });
    
    // Update the displayed page numbers
    updateCustomPageNumberButtons(currentPage, totalPages);
}

function updateCustomPageNumberButtons(currentPage, totalPages) {
    const paginationContainer = document.getElementById('customPaginationContainer');
    if (!paginationContainer) return;
    
    // Find the number buttons (between navigation buttons)
    const buttons = Array.from(paginationContainer.querySelectorAll('button'));
    const numberButtons = buttons.filter(button => !isNaN(parseInt(button.textContent.trim())));
    
    if (numberButtons.length === 0) return;
    
    // Determine which pages to show (always show 3 pages if possible)
    let startPage, endPage;
    
    if (totalPages <= 3) {
        // Show all pages if 3 or fewer
        startPage = 1;
        endPage = totalPages;
    } else {
        // Show current page in the middle when possible
        if (currentPage <= 2) {
            startPage = 1;
            endPage = 3;
        } else if (currentPage >= totalPages - 1) {
            startPage = totalPages - 2;
            endPage = totalPages;
        } else {
            startPage = currentPage - 1;
            endPage = currentPage + 1;
        }
    }
    
    // Update the number buttons
    let pageNum = startPage;
    numberButtons.forEach(button => {
        if (pageNum <= endPage) {
            button.textContent = pageNum;
            button.style.display = 'inline-block';
            
            // Update active state
            button.classList.remove('bg-sidebar-accent', 'text-white');
            button.classList.add('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
            
            if (pageNum === currentPage) {
                button.classList.add('bg-sidebar-accent', 'text-white');
                button.classList.remove('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
            }
            
            pageNum++;
        } else {
            button.style.display = 'none';
        }
    });
}
// Add event listeners for filter options if they exist
document.querySelectorAll('.custom-filter-option, .custom-filter-option-mobile').forEach(option => {
    option.addEventListener('click', function() {
        // Remove active class from all options
        document.querySelectorAll('.custom-filter-option, .custom-filter-option-mobile').forEach(el => {
            el.classList.remove('active');
        });
        
        // Add active class to clicked option
        this.classList.add('active');
        
        // Show filter indicator if needed
        if (document.getElementById('customFilterIndicator')) {
            document.getElementById('customFilterIndicator').classList.remove('hidden');
        }
        if (document.getElementById('customFilterIndicatorMobile')) {
            document.getElementById('customFilterIndicatorMobile').classList.remove('hidden');
        }
        
        // Reload bookings with new filter
        loadCustomBookings(1);
    });
});

// Add event listeners for search inputs
const customSearchInputs = ['customBookingSearchInput', 'customBookingSearchInputMobile'];
customSearchInputs.forEach(id => {
    const input = document.getElementById(id);
    if (input) {
        let searchTimeout;
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadCustomBookings(1);
            }, 500);
        });
    }
});


function loadLifeplanBookings(page) {
    // Show loading indicator
    document.getElementById('loadingIndicator').classList.remove('hidden');
    
    // Get current search and filter values
    const searchQuery = document.getElementById('lifeplanSearchInput')?.value || 
                       document.getElementById('lifeplanSearchInputMobile')?.value || '';
    const sortBy = document.querySelector('.lifeplan-filter-option.active')?.dataset.sort || 
                  document.querySelector('.lifeplan-filter-option-mobile.active')?.dataset.sort || 'newest';
    
    // Create AJAX request
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'bookingpage/ajax_pagination_lifeplan_table.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                
                // Update table body
                document.getElementById('lifeplanBookingTableBody').innerHTML = response.html;
                
                // Update pagination info
                document.getElementById('lifeplanPaginationInfo').textContent = response.paginationInfo;
                
                // Update booking counter
                if (document.getElementById('totalLifeplanBookings')) {
                    document.getElementById('totalLifeplanBookings').textContent = response.totalBookings > 0 ? 
                        response.totalBookings : "No lifeplan bookings";
                }
                
                // Update pagination buttons
                updateLifeplanPaginationButtons(response.currentPage, response.totalPages);
                
            } catch (e) {
                console.error('Error parsing response:', e);
            }
        } else {
            console.error('Request failed. Status:', this.status);
        }
        
        // Hide loading indicator
        document.getElementById('loadingIndicator').classList.add('hidden');
    };
    
    xhr.onerror = function() {
        console.error('Request failed');
        document.getElementById('loadingIndicator').classList.add('hidden');
    };
    
    // Send request with parameters
    xhr.send(`page=${page}&search=${encodeURIComponent(searchQuery)}&sort=${sortBy}`);
}

function updateLifeplanPaginationButtons(currentPage, totalPages) {
    const paginationContainer = document.getElementById('lifeplanPaginationContainer');
    if (!paginationContainer || totalPages <= 1) return;
    
    // Get all pagination buttons
    const buttons = paginationContainer.querySelectorAll('button');
    
    // Update button states
    buttons.forEach(button => {
        const buttonText = button.textContent.trim();
        const isNumberButton = !isNaN(parseInt(buttonText));
        
        // Reset all buttons
        button.classList.remove('opacity-50', 'pointer-events-none');
        button.classList.remove('bg-sidebar-accent', 'text-white');
        button.classList.add('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
        
        // Handle number buttons
        if (isNumberButton) {
            const pageNum = parseInt(buttonText);
            if (pageNum === currentPage) {
                button.classList.add('bg-sidebar-accent', 'text-white');
                button.classList.remove('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
            }
        }
        
        // Handle navigation buttons
        if (buttonText === '«') { // First page button
            if (currentPage === 1) {
                button.classList.add('opacity-50', 'pointer-events-none');
            }
        } else if (buttonText === '‹') { // Previous page button
            if (currentPage === 1) {
                button.classList.add('opacity-50', 'pointer-events-none');
            }
        } else if (buttonText === '›') { // Next page button
            if (currentPage === totalPages) {
                button.classList.add('opacity-50', 'pointer-events-none');
            }
        } else if (buttonText === '»') { // Last page button
            if (currentPage === totalPages) {
                button.classList.add('opacity-50', 'pointer-events-none');
            }
        }
    });
    
    // Update the displayed page numbers
    updateLifeplanPageNumberButtons(currentPage, totalPages);
}

function updateLifeplanPageNumberButtons(currentPage, totalPages) {
    const paginationContainer = document.getElementById('lifeplanPaginationContainer');
    if (!paginationContainer) return;
    
    // Find the number buttons (between navigation buttons)
    const buttons = Array.from(paginationContainer.querySelectorAll('button'));
    const numberButtons = buttons.filter(button => !isNaN(parseInt(button.textContent.trim())));
    
    if (numberButtons.length === 0) return;
    
    // Determine which pages to show (always show 3 pages if possible)
    let startPage, endPage;
    
    if (totalPages <= 3) {
        // Show all pages if 3 or fewer
        startPage = 1;
        endPage = totalPages;
    } else {
        // Show current page in the middle when possible
        if (currentPage <= 2) {
            startPage = 1;
            endPage = 3;
        } else if (currentPage >= totalPages - 1) {
            startPage = totalPages - 2;
            endPage = totalPages;
        } else {
            startPage = currentPage - 1;
            endPage = currentPage + 1;
        }
    }
    
    // Update the number buttons
    let pageNum = startPage;
    numberButtons.forEach(button => {
        if (pageNum <= endPage) {
            button.textContent = pageNum;
            button.style.display = 'inline-block';
            
            // Update active state
            button.classList.remove('bg-sidebar-accent', 'text-white');
            button.classList.add('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
            
            if (pageNum === currentPage) {
                button.classList.add('bg-sidebar-accent', 'text-white');
                button.classList.remove('border', 'border-sidebar-border', 'hover:bg-sidebar-hover');
            }
            
            pageNum++;
        } else {
            button.style.display = 'none';
        }
    });
}
// Add event listeners for filter options
document.querySelectorAll('.lifeplan-filter-option, .lifeplan-filter-option-mobile').forEach(option => {
    option.addEventListener('click', function() {
        // Remove active class from all options
        document.querySelectorAll('.lifeplan-filter-option, .lifeplan-filter-option-mobile').forEach(el => {
            el.classList.remove('active');
        });
        
        // Add active class to clicked option
        this.classList.add('active');
        
        // Show filter indicator
        document.getElementById('lifeplanFilterIndicator')?.classList.remove('hidden');
        document.getElementById('lifeplanFilterIndicatorMobile')?.classList.remove('hidden');
        
        // Reload bookings with new filter
        loadLifeplanBookings(1);
    });
});

// Add event listeners for search inputs
const lifeplanSearchInputs = ['lifeplanSearchInput', 'lifeplanSearchInputMobile'];
lifeplanSearchInputs.forEach(id => {
    const input = document.getElementById(id);
    if (input) {
        let searchTimeout;
        input.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                loadLifeplanBookings(1);
            }, 500);
        });
    }
});

</script>



</body>
</html>