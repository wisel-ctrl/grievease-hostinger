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


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase -  Account Management</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
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
  </style>

</head>
<body class="flex bg-gray-50">

<?php include 'admin_sidebar.php'; ?>


  <!-- Main Content -->
<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Account Management</h1>
    </div>

  </div>

  <?php
// Include the database connection
require_once('../db_connect.php');

// Initialize pagination variables
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Default to page 1 if not specified
$itemsPerPage = 5; // Number of items to display per page

// Count total number of customers
$totalCustomersQuery = "SELECT COUNT(*) AS total FROM users";
$totalCustomersResult = mysqli_query($conn, $totalCustomersQuery);
$totalCustomersRow = mysqli_fetch_assoc($totalCustomersResult);
$totalCustomers = $totalCustomersRow['total'];

// Calculate total pages
$totalPages = ceil($totalCustomers / $itemsPerPage);

// Ensure page is within valid range
$page = max(1, min($page, $totalPages));

// Calculate offset for pagination
$offset = ($page - 1) * $itemsPerPage;

// Fetch customers for current page
$customersQuery = "SELECT * FROM users LIMIT $offset, $itemsPerPage";
$customersResult = mysqli_query($conn, $customersQuery);
?>

<!-- Customer Account Management Section -->
<div id="customer-account-management" class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
    <!-- Account Header with Search and Filters -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
        <!-- Desktop layout for big screens - Title on left, controls on right -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Title and Counter -->
            <!-- In account_management.php, find the Customer Accounts section header -->
<div class="flex items-center gap-3 mb-4 lg:mb-0">
    <h3 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Customer Accounts</h3>
    <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
        
        <span id="totalCustomers"><?php echo $totalCustomers; ?></span>
    </span>
</div>
            
            <!-- Controls for big screens - aligned right -->
            <div class="hidden lg:flex items-center gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <input type="text" id="customerSearchInput" 
                           placeholder="Search customers..." 
                           class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                    <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
                </div>

                <!-- Filter Dropdown -->
                <div class="relative filter-dropdown">
                    <button id="customerFilterToggle" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
                        <i class="fas fa-filter text-sidebar-accent"></i>
                        <span>Filters</span>
                        <span id="filterIndicator" class="hidden h-2 w-2 bg-sidebar-accent rounded-full"></span>
                    </button>
                    
                    <!-- Filter Window -->
                    <div id="customerFilterDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
                        <div class="space-y-4">
                            <!-- Sort Options -->
                            <div>
                                <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                                <div class="space-y-1">
                                    <div class="flex items-center cursor-pointer filter-option" data-sort="id_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Default
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option" data-sort="name_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Name: A-Z
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option" data-sort="name_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Name: Z-A
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option" data-sort="email_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Email: A-Z
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option" data-sort="email_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Email: Z-A
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- For Customer Archive Button -->
                <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover" onclick="showArchivedCustomer">
                    <i class="fas fa-archive text-sidebar-accent"></i>
                    <span>Archive</span>
                </button>
                
                <!-- Add Customer Account Button -->
                <button class="px-4 py-2 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap" 
                        onclick="openAddCustomerAccountModal()">
                    <i class="fas fa-plus"></i>
                    <span>Add Customer Account</span>
                </button>
            </div>
        </div>
        
        <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
        <div class="lg:hidden w-full mt-4">
            <!-- First row: Search bar with filter icon on the right -->
            <div class="flex items-center w-full gap-3 mb-4">
                <!-- Search Input - Takes most of the space -->
                <div class="relative flex-grow">
                    <input type="text" id="customerSearchInputMobile" 
                            placeholder="Search customers..." 
                            class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>

                <!-- Icon-only button for filter -->
                <div class="flex items-center">
                    <!-- Filter Icon Button -->
                    <div class="relative filter-dropdown">
                        <button id="customerFilterToggleMobile" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
                            <i class="fas fa-filter text-xl"></i>
                            <span id="filterIndicatorMobile" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
                        </button>
                        
                        <!-- Mobile Filter Dropdown -->
                        <div id="customerFilterDropdownMobile" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
                            <div class="space-y-2">
                                <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                                <div class="space-y-1">
                                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="id_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Default
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="name_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Name: A-Z
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="name_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Name: Z-A
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="email_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Email: A-Z
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="email_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Email: Z-A
                                            </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Archive Icon Button -->
          <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent" onclick="showArchivedItems(<?php echo $branchId; ?>)">
            <i class="fas fa-archive text-xl"></i>
          </button>
</div>
            

            <!-- Second row: Add Customer Account Button - Full width -->
            <div class="w-full">
                <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap w-full justify-center" 
                        onclick="openAddCustomerAccountModal()">
                    <i class="fas fa-plus mr-2"></i>
                    <span>Add Customer Account</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Responsive Table Container with improved spacing -->
    <div class="overflow-x-auto scrollbar-thin" id="customerTableContainer">
        
        <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
        <div class="min-w-full">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-sidebar-border">
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('id')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-hashtag text-sidebar-accent"></i> ID 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('name')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-user text-sidebar-accent"></i> Name 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('email')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-envelope text-sidebar-accent"></i> Email 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('role')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-id-badge text-sidebar-accent"></i> Role 
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
                <tbody id="customerTableBody">
                    <!-- Table content will be dynamically loaded -->
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Sticky Pagination Footer with improved spacing -->
    <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
        <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
            Showing <span id="showingFrom">0</span> - <span id="showingTo">0</span> 
            of <span id="totalCount">0</span> customers
        </div>
        <div id="paginationContainer" class="flex space-x-1">
            <!-- Pagination buttons will be inserted here by JavaScript -->
        </div>
    </div>
</div>


<script>
    document.querySelectorAll('.fa-archive').forEach(icon => {
      icon.closest('button').addEventListener('click', function(e) {
        e.preventDefault();
        
        Swal.fire({
          title: 'Are you sure?',
          text: "You are about to view archived items. Do you want to proceed?",
          icon: 'question',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, view archived',
          cancelButtonText: 'Cancel'
        }).then((result) => {
          if (result.isConfirmed) {
            // User confirmed, proceed with archive view
            const branchId = <?php echo $branchId; ?>;
            showArchivedItems(branchId);
          }
        });
      });
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements for both desktop and mobile
    const searchInput = document.getElementById('customerSearchInput');
    const searchInputMobile = document.getElementById('customerSearchInputMobile');
    const filterToggle = document.getElementById('customerFilterToggle');
    const filterToggleMobile = document.getElementById('customerFilterToggleMobile');
    const filterDropdown = document.getElementById('customerFilterDropdown');
    const filterDropdownMobile = document.getElementById('customerFilterDropdownMobile');
    const filterOptions = document.querySelectorAll('#customerFilterDropdown .filter-option');
    const filterOptionsMobile = document.querySelectorAll('#customerFilterDropdownMobile .filter-option-mobile');
    const filterIndicator = document.getElementById('filterIndicator');
    const filterIndicatorMobile = document.getElementById('filterIndicatorMobile');
    
    const customerTableBody = document.getElementById('customerTableBody');
    const paginationInfoElement = document.getElementById('paginationInfo');
    const paginationContainer = document.getElementById('paginationContainer');
    const showingFrom = document.getElementById('showingFrom');
    const showingTo = document.getElementById('showingTo');
    const totalCount = document.getElementById('totalCount');
    
    let currentSearch = '';
    let currentSort = 'id_asc';
    let currentPage = 1;
    let totalPages = 1;

    // Function to create pagination buttons
    function createPaginationButtons() {
    paginationContainer.innerHTML = ''; // Clear existing buttons
    
    if (totalPages > 1) {
        // First page button (double arrow)
        const firstButton = document.createElement('a');
        firstButton.href = '#';
        firstButton.innerHTML = '&laquo;';
        firstButton.className = 'px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover' +
            (currentPage === 1 ? ' opacity-50 pointer-events-none' : '');
        firstButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage !== 1) {
                currentPage = 1;
                fetchCustomerAccounts();
            }
        });
        paginationContainer.appendChild(firstButton);
        
        // Previous page button (single arrow)
        const prevButton = document.createElement('a');
        prevButton.href = '#';
        prevButton.innerHTML = '&lsaquo;';
        prevButton.className = 'px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover' +
            (currentPage === 1 ? ' opacity-50 pointer-events-none' : '');
        prevButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage > 1) {
                currentPage--;
                fetchCustomerAccounts();
            }
        });
        paginationContainer.appendChild(prevButton);
        
        // Show exactly 3 page numbers as in the original code
        let startPage, endPage;
        
        if (totalPages <= 3) {
            // If total pages is 3 or less, show all pages
            startPage = 1;
            endPage = totalPages;
        } else {
            // With more than 3 pages, determine which 3 to show
            if (currentPage === 1) {
                // At beginning, show first 3 pages
                startPage = 1;
                endPage = 3;
            } else if (currentPage === totalPages) {
                // At end, show last 3 pages
                startPage = totalPages - 2;
                endPage = totalPages;
            } else {
                // In middle, show current page with one before and after
                startPage = currentPage - 1;
                endPage = currentPage + 1;
                
                // Handle edge cases
                if (startPage < 1) {
                    startPage = 1;
                    endPage = 3;
                }
                if (endPage > totalPages) {
                    endPage = totalPages;
                    startPage = totalPages - 2;
                }
            }
        }
        
        // Generate page buttons
        for (let i = startPage; i <= endPage; i++) {
            const pageButton = document.createElement('a');
            pageButton.href = '#';
            pageButton.textContent = i;
            pageButton.className = 'px-3.5 py-1.5 rounded text-sm ' + 
                (i === currentPage 
                    ? 'bg-sidebar-accent text-white' 
                    : 'border border-sidebar-border hover:bg-sidebar-hover');
            pageButton.addEventListener('click', (e) => {
                e.preventDefault();
                currentPage = i;
                fetchCustomerAccounts();
            });
            paginationContainer.appendChild(pageButton);
        }
        
        // Next page button (single arrow)
        const nextButton = document.createElement('a');
        nextButton.href = '#';
        nextButton.innerHTML = '&rsaquo;';
        nextButton.className = 'px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover' +
            (currentPage === totalPages ? ' opacity-50 pointer-events-none' : '');
        nextButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage < totalPages) {
                currentPage++;
                fetchCustomerAccounts();
            }
        });
        paginationContainer.appendChild(nextButton);
        
        // Last page button (double arrow)
        const lastButton = document.createElement('a');
        lastButton.href = '#';
        lastButton.innerHTML = '&raquo;';
        lastButton.className = 'px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover' +
            (currentPage === totalPages ? ' opacity-50 pointer-events-none' : '');
        lastButton.addEventListener('click', (e) => {
            e.preventDefault();
            if (currentPage !== totalPages) {
                currentPage = totalPages;
                fetchCustomerAccounts();
            }
        });
        paginationContainer.appendChild(lastButton);
    }
}

    // Function to fetch customer accounts via AJAX
    function fetchCustomerAccounts() {
        // Create a new XMLHttpRequest
        const xhr = new XMLHttpRequest();
        
        // Prepare the URL with search, sort, and page parameters
        const url = `addCustomer/fetch_customer_accounts.php?search=${encodeURIComponent(currentSearch)}&sort=${encodeURIComponent(currentSort)}&page=${currentPage}`;
        
        xhr.open('GET', url, true);
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    // Parse the JSON response
                    const response = JSON.parse(xhr.responseText);
                    
                    // Update table body
                    customerTableBody.innerHTML = response.tableContent || `
                        <tr>
                            <td colspan="6" class="text-center p-4 text-gray-500">
                                No customer accounts found.
                            </td>
                        </tr>
                    `;
                    
                    // Update pagination info
                    showingFrom.textContent = response.showingFrom || '0';
                    showingTo.textContent = response.showingTo || '0';
                    totalCount.textContent = response.totalCount || '0';

                    document.getElementById('totalCustomers').textContent = response.totalCount;
                    
                    // Update total pages and current page
                    totalPages = response.totalPages || 1;
                    currentPage = response.currentPage || 1;
                    
                    // Create pagination buttons
                    createPaginationButtons();
                    
                    // Update filter indicators if a sort is applied
                    if (currentSort !== 'id_asc') {
                        filterIndicator.classList.remove('hidden');
                        filterIndicatorMobile.classList.remove('hidden');
                    } else {
                        filterIndicator.classList.add('hidden');
                        filterIndicatorMobile.classList.add('hidden');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    customerTableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center p-4 text-red-500">
                                Error loading data. Please try again.
                            </td>
                        </tr>
                    `;
                }
            } else {
                console.error('Error fetching customer accounts:', xhr.statusText);
                customerTableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center p-4 text-red-500">
                            Failed to load customers. Please try again.
                        </td>
                    </tr>
                `;
            }
        };
        
        xhr.onerror = function() {
            console.error('Network error occurred');
            customerTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center p-4 text-red-500">
                        Network error. Please check your connection.
                    </td>
                </tr>
            `;
        };
        
        xhr.send();
    }

    // Initial load of customer accounts
    fetchCustomerAccounts();

    // Filter dropdown toggle for desktop
    filterToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        filterDropdown.classList.toggle('hidden');
        filterDropdownMobile.classList.add('hidden');
    });

    // Filter dropdown toggle for mobile
    filterToggleMobile.addEventListener('click', function(e) {
        e.stopPropagation();
        filterDropdownMobile.classList.toggle('hidden');
        filterDropdown.classList.add('hidden');
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        filterDropdown.classList.add('hidden');
        filterDropdownMobile.classList.add('hidden');
    });

    // Prevent dropdown from closing when clicking inside
    filterDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    filterDropdownMobile.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Search functionality with debounce for both desktop and mobile
    function setupSearchInput(inputElement) {
        let searchTimeout;
        inputElement.addEventListener('input', function() {
            currentSearch = this.value;
            currentPage = 1; // Reset to first page when searching
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            // Set new timeout to reduce unnecessary API calls
            searchTimeout = setTimeout(() => {
                fetchCustomerAccounts();
            }, 300); // 300ms delay
        });
    }
    
    setupSearchInput(searchInput);
    setupSearchInput(searchInputMobile);

    // Filter option selection for both desktop and mobile
    function setupFilterOptions(options, dropdown) {
        options.forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                currentSort = this.getAttribute('data-sort');
                currentPage = 1; // Reset to first page when changing sort
                
                // Close dropdown
                dropdown.classList.add('hidden');
                
                // Fetch customer accounts with selected sort
                fetchCustomerAccounts();
            });
        });
    }
    
    setupFilterOptions(filterOptions, filterDropdown);
    setupFilterOptions(filterOptionsMobile, filterDropdownMobile);
});
</script>



<!-- Add Customer Account Modal -->
<div id="addCustomerAccountModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeAddCustomerAccountModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Add Customer Account
      </h3>
    </div>
    
    <!-- Modal Body with Custom Scrollbar -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="addCustomerAccountForm" method="post" action="addCustomer/add_customer.php" class="space-y-3 sm:space-y-4">
        <!-- Personal Information Section -->
        <!-- First Name and Middle Name Row -->
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
          <div class="w-full sm:flex-1">
            <label for="firstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              First Name <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input type="text" id="firstName" name="firstName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="First Name" required>
            </div>
            <p id="firstNameError" class="text-red-500 text-xs mt-1 hidden"></p>
          </div>
          
          <div class="w-full sm:flex-1">
            <label for="middleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Middle Name
            </label>
            <div class="relative">
              <input type="text" id="middleName" name="middleName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Middle Name">
            </div>
            <p id="middleNameError" class="text-red-500 text-xs mt-1 hidden"></p>
          </div>
        </div>
        
        <!-- Last Name and Suffix Row -->
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
          <div class="w-full sm:flex-1">
            <label for="lastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Last Name <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input type="text" id="lastName" name="lastName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Last Name" required>
            </div>
            <p id="lastNameError" class="text-red-500 text-xs mt-1 hidden"></p>
          </div>
          
          <div class="w-full sm:flex-1">
            <label for="suffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Suffix
            </label>
            <div class="relative">
              <select id="suffix" name="suffix" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                <option value="">Select Suffix</option>
                <option value="Jr">Jr</option>
                <option value="Sr">Sr</option>
                <option value="I">I</option>
                <option value="II">II</option>
                <option value="III">III</option>
                <option value="IV">IV</option>
                <option value="V">V</option>
              </select>
            </div>
          </div>
        </div>
        
        <div>
          <label for="birthdate" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Birthdate <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="date" id="birthdate" name="birthdate" 
              class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
              max="<?php echo date('Y-m-d'); ?>" required>
          </div>
          <p id="birthdateError" class="text-red-500 text-xs mt-1 hidden"></p>
        </div>
        
        <div>
          <label for="branchLocation" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Branch Location <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <select id="branchLocation" name="branchLocation" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
              <option value="">Select Branch</option>
              <!-- Branch options will be populated by AJAX -->
            </select>
          </div>
          <p id="branchError" class="text-red-500 text-xs mt-1 hidden">Please select a branch</p>
        </div>
        
        <!-- Contact Information Section -->
        <div>
          <label for="customerEmail" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Email Address <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="email" id="customerEmail" name="customerEmail" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="example@email.com" required>
          </div>
          <p id="emailError" class="text-red-500 text-xs mt-1 hidden"></p>
        </div>
        
        <div>
          <label for="customerPhone" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Phone Number <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="tel" id="customerPhone" name="customerPhone" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Phone Number" required>
          </div>
          <p id="phoneError" class="text-red-500 text-xs mt-1 hidden"></p>
        </div>
        
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Generated Password
          </label>
          <div class="relative">
            <input type="password" id="generatedPassword" name="password" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200 bg-gray-100" readonly>
            <button type="button" class="absolute right-2 top-2 text-gray-500 hover:text-gray-700" onclick="togglePassword()">
              <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 sm:w-6 sm:h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 12s2.947-5.455 8.02-5.455S20.02 12 20.02 12s-2.947 5.455-8.02 5.455S3.98 12 3.98 12z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Additional Information Card -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border-l-4 border-sidebar-accent mt-3 sm:mt-4">
          <h4 class="text-xs sm:text-sm font-medium text-gray-700 mb-2 flex items-center">
            <i class="fas fa-info-circle mr-2 text-sidebar-accent"></i>
            Account Information
          </h4>
          <p class="text-xs sm:text-sm text-gray-600">
            An account will be created with the provided information. A temporary password will be generated automatically.
          </p>
          <p class="text-xs sm:text-sm text-gray-600 mt-2">
            The customer will be able to change their password after logging in for the first time.
          </p>
        </div>
        
        <input type="hidden" name="user_type" value="3">
        <input type="hidden" name="is_verified" value="1">
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeAddCustomerAccountModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="submitCustomerForm()">
        Create Account
      </button>
    </div>
  </div>
</div>

<!-- OTP Verification Modal -->
<div id="otpVerificationModal" class="fixed inset-0 bg-black bg-opacity-60 z-50 hidden overflow-y-auto flex items-center justify-center p-4 w-full h-full">
  <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-2">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-darkgold flex justify-between items-center p-6 flex-shrink-0 rounded-t-xl">
      <h3 class="text-xl font-bold text-white"><i class="fas fa-shield-alt"></i> Email Verification</h3>
      <button onclick="closeOtpModal()" class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="p-6">
      <p class="text-gray-700 mb-4">A verification code has been sent to <span id="otpEmail" class="font-medium"></span>. Please enter the code below.</p>
      <div class="flex justify-center gap-2 mb-4">
        <input type="text" class="otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
      </div>
      <div id="otpError" class="text-red-500 text-center text-sm mb-4 hidden"></div>
      <p class="text-sm text-gray-500 text-center">Didn't receive the code? <button type="button" onclick="resendOTP()" class="text-sidebar-accent hover:underline">Resend</button></p>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white rounded-b-xl">
      <button onclick="closeOtpModal()" class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors">
        Cancel
      </button>
      <button onclick="verifyOTP()" class="px-6 py-3 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center">
        <i class="fas fa-check-circle mr-2"></i> Verify
      </button>
    </div>
  </div>
</div>

<script>

document.getElementById("customerPhone").addEventListener("input", function (e) {
    this.value = this.value.replace(/[^0-9]/g, ""); // Remove non-numeric characters
  });

  document.addEventListener('DOMContentLoaded', function() {
    // Name fields in the add customer account modal
    const customerNameFields = [
        'firstName', 
        'middleName', 
        'lastName'
    ];

    // Function to validate name input
    function validateNameInput(field) {
        if (!field || !field.value) return '';
        
        // First, remove any invalid characters
        let newValue = field.value.replace(/[^a-zA-Z\s'-]/g, '');
        
        // Don't allow space as first character
        if (newValue.startsWith(' ')) {
            newValue = newValue.substring(1);
        }
        
        // Don't allow consecutive spaces
        newValue = newValue.replace(/\s{2,}/g, ' ');
        
        // Only allow space after at least 2 characters
        if (newValue.length < 2 && newValue.includes(' ')) {
            newValue = newValue.replace(/\s/g, '');
        }
        
        // Update the field value
        field.value = newValue;
        
        // Capitalize first letter of each word
        if (field.value.length > 0) {
            field.value = field.value.toLowerCase().replace(/(^|\s)\S/g, function(firstLetter) {
                return firstLetter.toUpperCase();
            });
        }
        
        return field.value;
    }

    // Function to apply validation to a field
    function applyNameValidation(field) {
        if (field) {
            // Validate on input
            field.addEventListener('input', function() {
                validateNameInput(this);
            });

            // Validate on blur (when field loses focus)
            field.addEventListener('blur', function() {
                validateNameInput(this);
            });

            // Prevent paste of invalid content
            field.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                const cleanedText = pastedText.replace(/[^a-zA-Z\s'-]/g, '');
                document.execCommand('insertText', false, cleanedText);
            });
        }
    }

    // Apply validation to customer fields
    customerNameFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        applyNameValidation(field);
    });

    // Additional validation for required fields
    const requiredCustomerFields = ['firstName', 'lastName'];

    // Function to apply required validation
    function applyRequiredValidation(field) {
        if (field) {
            field.addEventListener('blur', function() {
                if (this.value.trim().length < 2) {
                    this.setCustomValidity('Please enter at least 2 characters');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    }

    // Apply to customer fields
    requiredCustomerFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        applyRequiredValidation(field);
    });

    // Validate birthdate to ensure user is at least 18 years old
    function validateBirthdate() {
        const birthdateField = document.getElementById('birthdate');
        if (birthdateField) {
            const selectedDate = new Date(birthdateField.value);
            const today = new Date();
            const minBirthdate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
            
            if (selectedDate > minBirthdate) {
                birthdateField.setCustomValidity('Customer must be at least 18 years old');
            } else {
                birthdateField.setCustomValidity('');
            }
        }
    }

    // Set max date for birthdate field to 18 years ago
    function setMaxBirthdate() {
        const birthdateField = document.getElementById('birthdate');
        if (birthdateField) {
            const today = new Date();
            const maxDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
            birthdateField.max = maxDate.toISOString().split('T')[0];
        }
    }

    // Validate email input
    function validateEmail() {
        const emailField = document.getElementById('customerEmail');
        if (emailField) {
            // Remove any spaces from the email in real-time
            if (emailField.value.includes(' ')) {
                emailField.value = emailField.value.replace(/\s/g, '');
            }
            
            // Check basic email format requirements
            if (emailField.value.length > 0) {
                if (!emailField.value.includes('@')) {
                    emailField.setCustomValidity('Email must contain @ symbol');
                } else if (emailField.value.indexOf('@') === 0) {
                    emailField.setCustomValidity('Email cannot start with @');
                } else if (emailField.value.indexOf('@') === emailField.value.length - 1) {
                    emailField.setCustomValidity('Email cannot end with @');
                } else {
                    emailField.setCustomValidity('');
                }
            } else {
                emailField.setCustomValidity('');
            }
        }
    }

    const emailField = document.getElementById('customerEmail');
    if (emailField) {
        // Validate on every input
        emailField.addEventListener('input', validateEmail);
        
        // Prevent pasting spaces
        emailField.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const cleanedText = pastedText.replace(/\s/g, '');
            document.execCommand('insertText', false, cleanedText);
        });
        
        // Prevent spacebar key
        emailField.addEventListener('keydown', function(e) {
            if (e.key === ' ' || e.code === 'Space') {
                e.preventDefault();
            }
        });
    }

    // Validate Philippine phone number
    function validatePhoneNumber() {
        const phoneField = document.getElementById('customerPhone');
        if (phoneField) {
            // Remove any non-digit characters
            let phoneNumber = phoneField.value.replace(/\D/g, '');
            
            // Limit to 11 characters
            if (phoneNumber.length > 11) {
                phoneNumber = phoneNumber.substring(0, 11);
            }
            
            // Update the field value
            phoneField.value = phoneNumber;
            
            // Validate the format
            if (phoneNumber.length > 0) {
                if (!phoneNumber.startsWith('09')) {
                    phoneField.setCustomValidity('Philippine numbers must start with 09');
                } else if (phoneNumber.length < 11) {
                    phoneField.setCustomValidity('Phone number must be 11 digits');
                } else {
                    phoneField.setCustomValidity('');
                }
            } else {
                phoneField.setCustomValidity('');
            }
        }
    }

    // Format phone number as user types
    function formatPhoneNumber() {
        const phoneField = document.getElementById('customerPhone');
        if (phoneField) {
            let phoneNumber = phoneField.value.replace(/\D/g, '');
            
            // Auto-add 09 if user starts with 9
            if (phoneNumber.length === 1 && phoneNumber === '9') {
                phoneNumber = '09';
            }
            
            // Format with spaces for better readability (optional)
            if (phoneNumber.length > 4) {
                phoneNumber = phoneNumber.replace(/(\d{4})(\d{3})(\d{4})/, '$1 $2 $3');
            } else if (phoneNumber.length > 2) {
                phoneNumber = phoneNumber.replace(/(\d{2})(\d+)/, '$1 $2');
            }
            
            phoneField.value = phoneNumber;
        }
    }

    // Initialize phone field event listeners
    const phoneField = document.getElementById('customerPhone');
    if (phoneField) {
        phoneField.addEventListener('input', function() {
            formatPhoneNumber();
            validatePhoneNumber();
        });
        
        phoneField.addEventListener('keydown', function(e) {
            // Allow: backspace, delete, tab, escape, enter
            if ([46, 8, 9, 27, 13].includes(e.keyCode) || 
                // Allow: Ctrl+A
                (e.keyCode == 65 && e.ctrlKey === true) || 
                // Allow: home, end, left, right
                (e.keyCode >= 35 && e.keyCode <= 39)) {
                return;
            }
            // Ensure it's a number and stop the keypress if not
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
    }

    // Initialize max birthdate when page loads
    setMaxBirthdate();

    // Existing event listeners for the customer form
    document.getElementById('firstName').addEventListener('input', validateFirstName);
    document.getElementById('middleName').addEventListener('input', validateMiddleName);
    document.getElementById('lastName').addEventListener('input', validateLastName);
    document.getElementById('birthdate').addEventListener('change', validateBirthdate);
    document.getElementById('customerEmail').addEventListener('input', validateEmail);
    document.getElementById('customerPhone').addEventListener('input', validatePhoneNumber);
    document.getElementById('branchLocation').addEventListener('change', validateBranchLocation);
});

function validateBranchLocation() {
  const branchSelect = document.getElementById('branchLocation');
  const branchError = document.getElementById('branchError');

  if (branchSelect.value === '') {
    branchError.classList.remove('hidden');
    return false;
  } else {
    branchError.classList.add('hidden');
    return true;
  }
}

// Modal functions
function openAddCustomerAccountModal() {
  const modal = document.getElementById('addCustomerAccountModal');
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  document.body.classList.add('overflow-hidden');
}

function closeAddCustomerAccountModal() {
  const modal = document.getElementById('addCustomerAccountModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  document.body.classList.remove('overflow-hidden');
  
  // Reset form and error messages
  document.getElementById('addCustomerAccountForm').reset();
  document.querySelectorAll('.text-red-500.text-xs').forEach(element => {
    element.classList.add('hidden');
  });
}

// Toggle password visibility
function togglePassword() {
  const passwordInput = document.getElementById('generatedPassword');
  const eyeIcon = document.getElementById('eyeIcon');
  
  if (passwordInput.type === 'password') {
    passwordInput.type = 'text';
    eyeIcon.innerHTML = `
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.5s2.947 5.455 8.02 5.455S20.02 8.5 20.02 8.5s-2.947-5.455-8.02-5.455S3.98 8.5 3.98 8.5z" />
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
      <line x1="3" y1="3" x2="21" y2="21" stroke="currentColor" stroke-width="1.5" />
    `;
  } else {
    passwordInput.type = 'password';
    eyeIcon.innerHTML = `
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 12s2.947-5.455 8.02-5.455S20.02 12 20.02 12s-2.947 5.455-8.02 5.455S3.98 12 3.98 12z" />
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
    `;
  }
}


// Show OTP modal and send OTP
function showOTPModal() {
  // Set the email in the OTP modal
  const email = document.getElementById('customerEmail').value;
  document.getElementById('otpEmail').textContent = email;
  
  // Send OTP to email
  const formData = new FormData();
  formData.append('email', email);
  
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'addCustomer/send_otp.php', true);
  
  xhr.onload = function() {
    if (this.status === 200) {
      const response = JSON.parse(this.responseText);
      if (response.success) {
        // Show OTP modal
        const modal = document.getElementById('otpVerificationModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        
        // Focus on first OTP input
        const otpInputs = document.querySelectorAll('.otp-input');
        if (otpInputs.length > 0) {
          otpInputs[0].focus();
        }
      } else {
        Swal.fire({
          title: 'Error Occurred',
          text: response.message || 'Something went wrong', // Fallback if message is empty
          icon: 'error',
          confirmButtonText: 'OK',
          confirmButtonColor: '#d33',
          backdrop: `
            rgba(210,0,0,0.4)
            url("/images/nyan-cat.gif")
            center top
            no-repeat
          `
        });
      }
    }
  };
  
  xhr.send(formData);
}

// Close OTP modal
function closeOtpModal() {
  const modal = document.getElementById('otpVerificationModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  
  // Clear OTP inputs
  const otpInputs = document.querySelectorAll('.otp-input');
  otpInputs.forEach(input => {
    input.value = '';
  });
  
  // Hide error message
  document.getElementById('otpError').classList.add('hidden');
}

// Resend OTP
function resendOtp(accountType) {
    const emailField = accountType === 'customer' ? document.getElementById('editEmail') : document.getElementById('editEmpEmail');
    const email = emailField.value.trim();
    
    // Disable the resend button
    const resendBtn = document.getElementById('resendOtpBtn');
    if (resendBtn) {
        resendBtn.disabled = true;
        resendBtn.classList.remove('text-sidebar-accent', 'hover:underline');
        resendBtn.classList.add('text-gray-400', 'cursor-not-allowed');
    }
    
    // Show the timer
    const timerDisplay = document.getElementById('resendTimer');
    if (timerDisplay) {
        timerDisplay.classList.remove('hidden');
    }
    
    // Reset and start the timer
    resetResendTimer();
    startResendTimer();
    
    // Send the new OTP
    sendOtp(email, accountType).catch(error => {
        console.error('Error resending OTP:', error);
        // If sending fails, re-enable the button
        if (resendBtn) {
            resendBtn.disabled = false;
            resendBtn.classList.add('text-sidebar-accent', 'hover:underline');
            resendBtn.classList.remove('text-gray-400', 'cursor-not-allowed');
        }
        if (timerDisplay) {
            timerDisplay.classList.add('hidden');
        }
    });
}

function startResendTimer() {
    // Clear any existing timer
    if (resendOtpTimer) {
        clearInterval(resendOtpTimer);
    }
    
    // Reset time left
    resendOtpTimeLeft = 60;
    updateTimerDisplay();
    
    // Start new timer
    resendOtpTimer = setInterval(() => {
        resendOtpTimeLeft--;
        updateTimerDisplay();
        
        if (resendOtpTimeLeft <= 0) {
            clearInterval(resendOtpTimer);
            enableResendButton();
        }
    }, 1000);
}

function resetResendTimer() {
    resendOtpTimeLeft = 60;
    if (resendOtpTimer) {
        clearInterval(resendOtpTimer);
    }
}

function updateTimerDisplay() {
    const timerDisplay = document.getElementById('resendTimer');
    if (timerDisplay) {
        const minutes = Math.floor(resendOtpTimeLeft / 60);
        const seconds = resendOtpTimeLeft % 60;
        timerDisplay.textContent = ` (Resend available in ${minutes}:${seconds < 10 ? '0' : ''}${seconds})`;
    }
}

function enableResendButton() {
    const resendBtn = document.getElementById('resendOtpBtn');
    const timerDisplay = document.getElementById('resendTimer');
    
    if (resendBtn) {
        resendBtn.disabled = false;
        resendBtn.classList.add('text-sidebar-accent', 'hover:underline');
        resendBtn.classList.remove('text-gray-400', 'cursor-not-allowed');
    }
    
    if (timerDisplay) {
        timerDisplay.classList.add('hidden');
    }
}

// Verify OTP and submit form
function verifyOTP() {
  // Collect OTP from inputs
  const otpInputs = document.querySelectorAll('.otp-input');
  let otpValue = '';
  
  otpInputs.forEach(input => {
    otpValue += input.value;
  });
  
  // Check if OTP is complete
  if (otpValue.length !== 6) {
    document.getElementById('otpError').textContent = 'Please enter all 6 digits';
    document.getElementById('otpError').classList.remove('hidden');
    return;
  }
  
  // Verify OTP
  const formData = new FormData();
  formData.append('otp', otpValue);
  
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'addCustomer/verify_otp.php', true);
  
  xhr.onload = function() {
    if (this.status === 200) {
      const response = JSON.parse(this.responseText);
      if (response.success) {
        // OTP verified, proceed with form submission
        actuallySubmitForm();
      } else {
        document.getElementById('otpError').textContent = response.message;
        document.getElementById('otpError').classList.remove('hidden');
      }
    }
  };
  
  xhr.send(formData);
}

// Handle OTP input functionality
document.addEventListener('DOMContentLoaded', function() {
  const otpInputs = document.querySelectorAll('.otp-input');
  
  otpInputs.forEach((input, index) => {
    // Auto-focus next input
    input.addEventListener('input', function() {
      if (input.value.length === 1) {
        if (index < otpInputs.length - 1) {
          otpInputs[index + 1].focus();
        }
      }
    });
    
    // Handle backspace
    input.addEventListener('keydown', function(e) {
      if (e.key === 'Backspace' && input.value === '' && index > 0) {
        otpInputs[index - 1].focus();
      }
    });
    
    // Allow only numbers
    input.addEventListener('input', function() {
      input.value = input.value.replace(/[^0-9]/g, '');
    });
  });
});

// Add this function to check phone number availability before submission
function checkCustomerPhoneAvailability() {
  const phoneInput = document.getElementById('customerPhone');
  const phoneError = document.getElementById('phoneError');
  const phone = phoneInput.value.trim();
  
  // Only proceed if the phone number passed basic validation
  if (!validatePhoneNumber()) {
    return Promise.reject('Phone number is invalid');
  }
  
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'addCustomer/check_phone.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
      if (this.status === 200) {
        try {
          const response = JSON.parse(this.responseText);
          if (response.available) {
            phoneError.classList.add('hidden');
            resolve(true);
          } else {
            phoneError.textContent = 'Phone number already in use';
            phoneError.classList.remove('hidden');
            reject('Phone number already in use');
          }
        } catch (e) {
          console.error("Error parsing response:", e);
          phoneError.textContent = 'Error checking phone number';
          phoneError.classList.remove('hidden');
          reject('Error checking phone number');
        }
      } else {
        phoneError.textContent = 'Error checking phone number';
        phoneError.classList.remove('hidden');
        reject('Error checking phone number');
      }
    };
    
    xhr.onerror = function() {
      phoneError.textContent = 'Network error occurred';
      phoneError.classList.remove('hidden');
      reject('Network error occurred');
    };
    
    xhr.send('phoneNumber=' + encodeURIComponent(phone));
  });
}

function validateFirstName() {
    const firstName = document.getElementById('firstName');
    const errorElement = document.getElementById('firstNameError');
    
    if (!firstName.value || firstName.value.length < 2) {
        errorElement.textContent = 'Please enter at least 2 characters';
        errorElement.classList.remove('hidden');
        return false;
    }
    
    errorElement.classList.add('hidden');
    return true;
}

function validateMiddleName() {
    // Middle name is optional, so validation always passes
    return true;
}

function validateLastName() {
    const lastName = document.getElementById('lastName');
    const errorElement = document.getElementById('lastNameError');
    
    if (!lastName.value || lastName.value.length < 2) {
        errorElement.textContent = 'Please enter at least 2 characters';
        errorElement.classList.remove('hidden');
        return false;
    }
    
    errorElement.classList.add('hidden');
    return true;
}

function validateBirthdate() {
    const birthdateField = document.getElementById('birthdate');
    const errorElement = document.getElementById('birthdateError');
    
    if (!birthdateField.value) {
        errorElement.textContent = 'Birthdate is required';
        errorElement.classList.remove('hidden');
        return false;
    }
    
    const selectedDate = new Date(birthdateField.value);
    const today = new Date();
    const minBirthdate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
    
    if (selectedDate > minBirthdate) {
        errorElement.textContent = 'Customer must be at least 18 years old';
        errorElement.classList.remove('hidden');
        return false;
    }
    
    errorElement.classList.add('hidden');
    return true;
}

function validateEmail() {
    const emailField = document.getElementById('customerEmail');
    const errorElement = document.getElementById('emailError');
    
    if (!emailField.value || !emailField.value.includes('@')) {
        errorElement.textContent = 'Please enter a valid email address';
        errorElement.classList.remove('hidden');
        return false;
    }
    
    errorElement.classList.add('hidden');
    return true;
}

function validatePhoneNumber() {
    const phoneField = document.getElementById('customerPhone');
    const errorElement = document.getElementById('phoneError');
    
    if (!phoneField.value || !phoneField.value.startsWith('09') || phoneField.value.length !== 11) {
        errorElement.textContent = 'Philippine number must start with 09 and be 11 digits';
        errorElement.classList.remove('hidden');
        return false;
    }
    
    errorElement.classList.add('hidden');
    return true;
}

// Form submission function
function submitCustomerForm() {
  // Validate all fields
  const isValid = validateFirstName() && 
                  validateMiddleName() && 
                  validateLastName() && 
                  validateBirthdate() && 
                  validateEmail() && 
                  validatePhoneNumber() && 
                  validateBranchLocation();

                  if (isValid) {
    // Generate password if not already generated
    if (document.getElementById('generatedPassword').value === '') {
      generatePassword();
    }
    
    // First check if phone number is available
    checkCustomerPhoneAvailability()
      .then(() => {
        // If phone is available, show OTP verification modal
        showOTPModal();
      })
      .catch((error) => {
        console.error("Phone validation error:", error);
        // Error handling already done in checkPhoneAvailability function
      });
  }
}
// Add this new function for actual submission after OTP verification
function actuallySubmitForm() {
  const form = document.getElementById('addCustomerAccountForm');
  const formData = new FormData(form);
  
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'addCustomer/add_customer.php', true);
  
  xhr.onload = function() {
    if (this.status === 200) {
      const response = JSON.parse(this.responseText);
      if (response.success) {
          closeOtpModal(); // Close OTP modal
          
          Swal.fire({
              title: 'Success!',
              text: 'Customer account created successfully!',
              icon: 'success',
              confirmButtonColor: '#28a745',
              showCancelButton: false,
              confirmButtonText: 'OK',
              allowOutsideClick: false,
              willClose: () => {
                  closeAddCustomerAccountModal(); // Close the main modal after user clicks OK
                  // Optionally refresh customer list or perform other actions
              }
          });
      } else {
          Swal.fire({
              title: 'Error!',
              html: `<div style="color: #721c24; background-color: #f8d7da; padding: 10px; border-radius: 5px; border-left: 4px solid #f5c6cb;">
                        ${response.message || 'Failed to create account'}
                    </div>`,
              icon: 'error',
              confirmButtonColor: '#dc3545',
              confirmButtonText: 'Try Again',
              allowOutsideClick: false
          });
      }
    }
  };
  
  xhr.send(formData);
}

// Add event listeners for real-time validation
document.addEventListener('DOMContentLoaded', function() {
  // First Name validation
  document.getElementById('firstName').addEventListener('input', validateFirstName);
  
  // Middle Name validation
  document.getElementById('middleName').addEventListener('input', validateMiddleName);
  
  // Last Name validation
  document.getElementById('lastName').addEventListener('input', validateLastName);
  
  // Birthdate validation
  document.getElementById('birthdate').addEventListener('change', validateBirthdate);
  
  // Email validation
  document.getElementById('customerEmail').addEventListener('input', validateEmail);
  
  // Phone Number validation
  document.getElementById('customerPhone').addEventListener('input', validatePhoneNumber);
  
  // Branch Location validation
  document.getElementById('branchLocation').addEventListener('change', validateBranchLocation);

  // Close modal on 'Escape' key press
  window.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
      closeAddCustomerAccountModal();
    }
  });
});
</script>

<?php
// Include the database connection
require_once('../db_connect.php');

// Initialize search, sorting, and pagination variables
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $conn->real_escape_string($_GET['sort']) : 'id_asc';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 5;
$offset = ($page - 1) * $perPage;

// Base SQL query
$sql = "SELECT id, first_name, last_name, email, is_verified, created_at FROM users WHERE user_type = 2 AND is_verified = 1";

// Add search condition if search term is provided
if (!empty($search)) {
    $sql .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%')";
}

// Add sorting based on selected option
switch ($sort) {
    case 'id_asc':
        $sql .= " ORDER BY id ASC";
        break;
    case 'id_desc':
        $sql .= " ORDER BY id DESC";
        break;
    case 'name_asc':
        $sql .= " ORDER BY first_name ASC, last_name ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY first_name DESC, last_name DESC";
        break;
    case 'email_asc':
        $sql .= " ORDER BY email ASC";
        break;
    case 'email_desc':
        $sql .= " ORDER BY email DESC";
        break;
    case 'newest':
        $sql .= " ORDER BY created_at DESC";
        break;
    case 'oldest':
        $sql .= " ORDER BY created_at ASC";
        break;
    default:
        $sql .= " ORDER BY id ASC";
}

// Add pagination to the query
$sqlWithLimit = $sql . " LIMIT $perPage OFFSET $offset";
$result = $conn->query($sqlWithLimit);

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM users WHERE user_type = 2 AND is_verified = 1";
if (!empty($search)) {
    $countSql .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%')";
}
$countResult = $conn->query($countSql);
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

// Calculate showing from/to
$showingFrom = $offset + 1;
$showingTo = min($offset + $perPage, $totalRows);

// Initialize empty table content
$tableContent = '';

// Check if there are results
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
       // Determine status based on is_verified
        $status = $row['is_verified'] == 1 ? 
            '<span class="px-2 py-1 bg-green-100 text-green-600 rounded-full text-xs">Active</span>' : 
            '<span class="px-2 py-1 bg-yellow-100 text-yellow-600 rounded-full text-xs">Pending</span>';
        
        // Format employee ID
        $employeeId = "#EMP-" . str_pad($row['id'], 3, '0', STR_PAD_LEFT);
        
        // Format full name
        $fullName = ucfirst($row['first_name']) . ' ' . ucfirst($row['last_name']);
        
        // Create table row
        $tableContent .= '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text font-medium">' . $employeeId . '</td>
            <td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($fullName) . '</td>
            <td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($row['email']) . '</td>
            <td class="p-4 text-sm text-sidebar-text">Employee</td>
            <td class="p-4 text-sm">' . $status . '</td>
            <td class="p-4 text-sm">
              <div class="flex space-x-2">
                <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all" onclick="openEditEmployeeAccountModal(' . $row['id'] . ')">
                  <i class="fas fa-edit"></i>
                </button>
                <button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all" onclick="deleteEmployeeAccount(' . $row['id'] . ')">
                  <i class="fas fa-archive text-red"></i>
                </button>
              </div>
            </td>
          </tr>';
    }
    
    // Update pagination info
    $startCount = min($offset + 1, $totalRows);
    $endCount = min($offset + $perPage, $totalRows);
    $paginationInfo = "Showing {$startCount}-{$endCount} of {$totalRows} employee accounts";
} else {
    // If no employees found, display a message
    $tableContent = '<tr class="border-b border-sidebar-border">
        <td colspan="6" class="p-4 text-sm text-center text-gray-500">No employee accounts found</td>
    </tr>';
    
    // Set pagination info for empty results
    $paginationInfo = "Showing 0 of 0 employee accounts";
}
?>

<!-- Employee Account Management Section -->
<div id="employee-account-management" class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
    <!-- Account Header with Search and Filters -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
        <!-- Desktop layout for big screens - Title on left, controls on right -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Title and Counter -->
            <!-- In account_management.php, find the Employee Accounts section header -->
            <div class="flex items-center gap-3 mb-4 lg:mb-0">
    <h3 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Employee Accounts</h3>
    <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
        
        <span id="totalEmployees"><?php echo $totalRows; ?></span>
    </span>
</div>
            
            <!-- Controls for big screens - aligned right -->
            <div class="hidden lg:flex items-center gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <input type="text" id="searchInput" 
                           placeholder="Search employees..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                    <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
                </div>

                <!-- Filter Dropdown -->
                <div class="relative filter-dropdown">
                    <button id="filterToggle" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
                        <i class="fas fa-filter text-sidebar-accent"></i>
                        <span>Filters</span>
                        <span id="filterIndicator" class="hidden h-2 w-2 bg-sidebar-accent rounded-full"></span>
                    </button>
                    
                    <!-- Filter Window -->
                    <div id="filterDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
                        <div class="space-y-4">
                            <!-- Sort Options -->
                            <div>
                                <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                                <div class="space-y-1">
                                    <div class="flex items-center cursor-pointer filter-option" data-sort="id_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Default
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option" data-sort="name_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Name: A-Z
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option" data-sort="name_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Name: Z-A
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option" data-sort="email_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Email: A-Z
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option" data-sort="email_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Email: Z-A
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

                <!-- For Employee Archive Button -->
                <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover" onclick="showArchivedEmployee">
                    <i class="fas fa-archive text-sidebar-accent"></i>
                    <span>Archive</span>
                </button>

                <!-- Add Employee Account Button -->
                <button class="px-4 py-2 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap" 
                        onclick="openAddEmployeeAccountModal()">
                    <i class="fas fa-plus"></i>
                    <span>Add Employee Account</span>
                </button>
            </div>
        </div>
        
        <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
        <div class="lg:hidden w-full mt-4">
            <!-- First row: Search bar with filter icon on the right -->
            <div class="flex items-center w-full gap-3 mb-4">
                <!-- Search Input - Takes most of the space -->
                <div class="relative flex-grow">
                    <input type="text" id="searchInputMobile" 
                           placeholder="Search employees..."
                           value="<?php echo htmlspecialchars($search); ?>" 
                           class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>

                <!-- Icon-only button for filter -->
                <div class="flex items-center">
                    <!-- Filter Icon Button -->
                    <div class="relative filter-dropdown">
                        <button id="filterToggleMobile" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
                            <i class="fas fa-filter text-xl"></i>
                            <span id="filterIndicatorMobile" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
                        </button>
                        
                        <!-- Mobile Filter Dropdown -->
                        <div id="filterDropdownMobile" class="hidden absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
                            <div class="space-y-2">
                                <h5 class="text-sm font-medium text-sidebar-text mb-2">Sort By</h5>
                                <div class="space-y-1">
                                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="id_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Default
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="name_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Name: A-Z
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="name_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Name: Z-A
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="email_asc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Email: A-Z
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="email_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Email: Z-A
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

            <!-- Archive Icon Button -->
          <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent" onclick="showArchivedItems(<?php echo $branchId; ?>)">
            <i class="fas fa-archive text-xl"></i>
          </button>
</div>

            <!-- Second row: Add Employee Account Button - Full width -->
            <div class="w-full">
                <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap w-full justify-center" 
                        onclick="openAddEmployeeAccountModal()">
                    <i class="fas fa-plus mr-2"></i>
                    <span>Add Employee Account</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Responsive Table Container with improved spacing -->
    <div class="overflow-x-auto scrollbar-thin" id="employeeTableContainer">
        
        <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
        <div class="min-w-full">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-sidebar-border">
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-hashtag text-sidebar-accent"></i> ID 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-user text-sidebar-accent"></i> Name 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-envelope text-sidebar-accent"></i> Email 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-id-badge text-sidebar-accent"></i> Role 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
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
                <tbody id="employeeTableBody">
                    <?php echo $tableContent; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Sticky Pagination Footer with improved spacing -->
<div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="empPaginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
        Showing <span id="empShowingFrom"><?php echo $showingFrom; ?></span> - <span id="empShowingTo"><?php echo $showingTo; ?></span> 
        of <span id="empTotalCount"><?php echo $totalRows; ?></span> employees
    </div>
    <!-- Sticky Pagination Footer with improved spacing -->
<div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="empPaginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
        <?php 
        // Calculate and display pagination info
        if ($totalItems > 0) {
            $start = ($page - 1) * $itemsPerPage + 1;
            $end = min($start + $itemsPerPage - 1, $totalItems);
        
            echo "Showing {$start} - {$end} of {$totalItems} items";
        } else {
            echo "No items found";
        }
        ?>
    </div>
    <div id="empPaginationContainer" class="flex space-x-2">
        <?php if ($totalPages > 1): ?>
            <!-- First page button (double arrow) -->
            <button onclick="changeEmpPage(1)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &laquo;
            </button>
            
            <!-- Previous page button (single arrow) -->
            <button onclick="changeEmpPage(<?php echo max(1, $page - 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &lsaquo;
            </button>
            
            <?php
            // Show exactly 3 page numbers
            if ($totalPages <= 3) {
                // If total pages is 3 or less, show all pages
                $startPage = 1;
                $endPage = $totalPages;
            } else {
                // With more than 3 pages, determine which 3 to show
                if ($page == 1) {
                    // At the beginning, show first 3 pages
                    $startPage = 1;
                    $endPage = 3;
                } elseif ($page == $totalPages) {
                    // At the end, show last 3 pages
                    $startPage = $totalPages - 2;
                    $endPage = $totalPages;
                } else {
                    // In the middle, show current page with one before and after
                    $startPage = $page - 1;
                    $endPage = $page + 1;
                    
                    // Handle edge cases
                    if ($startPage < 1) {
                        $startPage = 1;
                        $endPage = 3;
                    }
                    if ($endPage > $totalPages) {
                        $endPage = $totalPages;
                        $startPage = $totalPages - 2;
                    }
                }
            }
            
            // Generate the page buttons
            for ($i = $startPage; $i <= $endPage; $i++) {
                $active_class = ($i == $page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
                echo '<button onclick="changeEmpPage(' . $i . ')" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</button>';
            }
            ?>
            
            <!-- Next page button (single arrow) -->
            <button onclick="changeEmpPage(<?php echo min($totalPages, $page + 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == $totalPages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &rsaquo;
            </button>
            
            <!-- Last page button (double arrow) -->
            <button onclick="changeEmpPage(<?php echo $totalPages; ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == $totalPages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &raquo;
            </button>
        <?php endif; ?>
    </div>
</div>

<script>
    function changeEmpPage(newPage) {
    const url = new URL(window.location.href);
    url.searchParams.set('empPage', newPage);
    window.location.href = url.toString();
}
</script>
<!-- Archived Accounts Modal -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="archivedModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeArchivedModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        <span id="modalTitle">Archived Accounts</span>
      </h3>
    </div>
    
    <!-- Search Bar -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <i class="fas fa-search text-gray-400"></i>
        </div>
        <input type="text" id="archivedAccountsSearch" placeholder="Search archived accounts..." 
          class="w-full pl-10 pr-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
      </div>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5 max-h-[70vh] overflow-y-auto w-full">
      <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
          <thead class="text-xs text-gray-700 uppercase bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3">ID</th>
              <th scope="col" class="px-6 py-3">Name</th>
              <th scope="col" class="px-6 py-3">Email</th>
              <th scope="col" class="px-6 py-3">Type</th>
              <th scope="col" class="px-6 py-3">Actions</th>
            </tr>
          </thead>
          <tbody id="archivedAccountsTable">
            <!-- Archived accounts will be loaded here -->
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-6 py-4 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center" onclick="closeArchivedModal()">
        Close
      </button>
    </div>
  </div>
</div>

<!--OPEN EDIT CUSTOMER/EMPLOYEE ACCOUNT-->
<!--OPEN EDIT CUSTOMER/EMPLOYEE ACCOUNT-->
<script>
// Global variables for OTP verification
let otpVerificationModal = null;
let isVerificationInProgress = false;
let originalFirstName = '';
let originalLastName = '';
let originalMiddleName = '';
let originalBranch = '';
let originalEmail = '';
let originalPhone = '';


/**
 * Unified function to validate email/phone existence in database
 * @param {string} fieldType - 'email' or 'phone'
 * @param {string} fieldId - ID of the input field
 * @param {string} errorElementId - ID of the error message element
 * @param {number} userId - Current user ID
 * @param {number} userType - 2 for employee, 3 for customer
 */
function setupEditFieldValidation(fieldType, fieldId, errorElementId, userId, userType) {
    const field = document.getElementById(fieldId);
    const errorElement = document.getElementById(errorElementId);
    
    if (!field || !errorElement) return;

    let validationTimeout;
    const fieldContainer = field.parentElement;
    
    // Create visual feedback element
    const fieldFeedback = document.createElement('div');
    fieldFeedback.className = 'absolute right-3 top-1/2 transform -translate-y-1/2';
    fieldContainer.appendChild(fieldFeedback);
    
    field.addEventListener('input', function() {
        clearTimeout(validationTimeout);
        const value = this.value.trim();
        
        // Clear previous feedback
        fieldFeedback.innerHTML = '';
        field.classList.remove('border-green-500', 'border-red-500');
        errorElement.classList.add('hidden');
        
        if (value.length === 0) return;
        
        // Basic format validation
        let isValidFormat = true;
        if (fieldType === 'email') {
            isValidFormat = value.includes('@');
            if (!isValidFormat) {
                errorElement.textContent = 'Please enter a valid email address';
            }
        } else { // phone
            isValidFormat = value.startsWith('09') && value.length === 11;
            if (!isValidFormat) {
                errorElement.textContent = 'Philippine number must start with 09 and be 11 digits';
            }
        }
        
        if (!isValidFormat) {
            field.classList.add('border-red-500');
            fieldFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
            errorElement.classList.remove('hidden');
            return;
        }

        validationTimeout = setTimeout(() => {
            const endpoint = fieldType === 'email' 
                ? 'check_edit_email.php' 
                : 'check_edit_phone.php';
                
            fetch(`editAccount/${endpoint}?${fieldType}=${encodeURIComponent(value)}&current_user=${userId}&user_type=${userType}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.exists) {
                        field.classList.add('border-red-500');
                        fieldFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                        errorElement.textContent = data.message || 
                            (fieldType === 'email' 
                                ? 'Email already registered to another account' 
                                : 'Phone number already exists in system');
                        errorElement.classList.remove('hidden');
                        showTooltip(fieldFeedback, fieldType === 'email' 
                            ? 'Email already in use' 
                            : 'Phone already in use');
                    } else {
                        field.classList.add('border-green-500');
                        fieldFeedback.innerHTML = '<i class="fas fa-check text-green-500"></i>';
                        errorElement.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error(`Error checking ${fieldType}:`, error);
                    // Don't show error to user for failed validation checks
                });
        }, 500);
    });
}

function openEditCustomerAccountModal(userId) {
    // Fetch user details
    fetch(`editAccount/fetch_customer_details.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Store original email and phone for comparison
                originalFirstName = data.user.first_name || '';
                originalLastName = data.user.last_name || '';
                originalMiddleName = data.user.middle_name || '';
                originalBranch = data.user.branch_loc || '';
                originalEmail = data.user.email || '';
                originalPhone = data.user.phone_number || '';
                
                // Create and show the modal
                let branchOptions = data.branches.map(branch => {
                    const branchName = branch.branch_name
                        .split(' ')
                        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                        .join(' ');
                    
                    return `<option value="${branch.branch_id}" ${data.user.branch_loc == branch.branch_id ? 'selected' : ''}>
                                ${branchName}
                            </option>`;
                }).join('');

                const modal = document.createElement('div');
                modal.id = 'editCustomerModal';
                modal.className = 'fixed inset-0 z-50 flex items-center justify-center overflow-y-auto';
                modal.innerHTML = `
                    <!-- Modal Backdrop -->
                    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
                    
                    <!-- Modal Content -->
                    <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
                        <!-- Close Button -->
                        <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeEditCustomerModal()">
                            <i class="fas fa-times"></i>
                        </button>
                        
                        <!-- Modal Header -->
                        <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
                            <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
                                Edit Customer Account
                            </h3>
                        </div>
                        
                        <!-- Modal Body -->
                        <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
                            <form id="editCustomerForm" class="space-y-3 sm:space-y-4">
                                <input type="hidden" name="user_id" value="${data.user.id}">
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Customer ID</label>
                                    <div class="relative">
                                        <input type="text" value="#CUST-${String(data.user.id).padStart(3, '0')}" 
                                               class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" readonly>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text" id="editFirstName" name="first_name" value="${data.user.first_name ? data.user.first_name[0].toUpperCase() + data.user.first_name.slice(1) : ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                                               pattern="[-A-Za-z']{2,}" 
                                               title="Only letters, apostrophes and hyphens allowed (minimum 2 characters)"
                                               required>
                                    </div>
                                    <p id="firstNameError" class="text-red-500 text-xs mt-1 hidden"></p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text" id="editLastName" name="last_name" value="${data.user.last_name ? data.user.last_name[0].toUpperCase() + data.user.last_name.slice(1) : ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                                               pattern="[-A-Za-z']{2,}" 
                                               title="Only letters, apostrophes and hyphens allowed (minimum 2 characters)"
                                               required>
                                    </div>
                                    <p id="lastNameError" class="text-red-500 text-xs mt-1 hidden"></p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Middle Name</label>
                                    <div class="relative">
                                        <input type="text" id="editMiddleName" name="middle_name" value="${data.user.middle_name ? data.user.middle_name[0].toUpperCase() + data.user.middle_name.slice(1) : ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                                               pattern="[A-Za-z'-]*"
                                               title="Only letters, apostrophes and hyphens allowed">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="email" id="editEmail" name="email" value="${data.user.email || ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                                               pattern="[^@\s]+@[^@\s]+\.[^@\s]+" 
                                               title="Please enter a valid email address (no spaces allowed)"
                                               required>
                                    </div>
                                    <p id="emailError" class="text-red-500 text-xs mt-1 hidden"></p>
                                    <p id="emailExistsError" class="text-red-500 text-xs mt-1 hidden"></p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                                        Phone Number <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="tel" id="editPhone" name="phone_number" value="${data.user.phone_number || ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                                               pattern="09\d{9}" 
                                               maxlength="11"
                                               title="Philippine number must start with 09 and be 11 digits total"
                                               required>
                                    </div>
                                    <p id="phoneError" class="text-red-500 text-xs mt-1 hidden"></p>
                                    <p id="phoneExistsError" class="text-red-500 text-xs mt-1 hidden"></p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                                        Branch Location <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <select name="branch_loc" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                                            <option value="">Select Branch</option>
                                            ${branchOptions}
                                        </select>
                                    </div>
                                    <p id="branchError" class="text-red-500 text-xs mt-1 hidden">Please select a branch</p>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Modal Footer -->
                        <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
                            <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeEditCustomerModal()">
                                Cancel
                            </button>
                            <button type="button" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="validateAndSaveCustomerChanges()">
                                Save Changes
                            </button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Add phone validation
setupPhoneValidation('editPhone');
                
                // Add validation event listeners
                setupEditFormValidations();
                
                // Add real-time validation for email and phone
                setupRealTimeValidation();
                
                // Add event listener for Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeEditCustomerModal();
                    }
                });
            } else {
                alert('Failed to fetch customer details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while fetching customer details');
        });
        
    setupEditFieldValidation('email', 'editEmail', 'emailExistsError', userId, 3);
    setupEditFieldValidation('phone', 'editPhone', 'phoneExistsError', userId, 3);
}

function setupRealTimeValidation() {
    const emailField = document.getElementById('editEmail');
    const phoneField = document.getElementById('editPhone');
    const userId = document.querySelector('input[name="user_id"]').value;

    // Email validation with visual feedback
    if (emailField) {
        const emailContainer = emailField.parentElement;
        const emailFeedback = document.createElement('div');
        emailFeedback.className = 'absolute right-3 top-1/2 transform -translate-y-1/2';
        emailContainer.appendChild(emailFeedback);

        let emailTimeout;
        emailField.addEventListener('input', function() {
            clearTimeout(emailTimeout);
            const email = this.value.trim();
            
            // Clear previous feedback
            emailFeedback.innerHTML = '';
            emailField.classList.remove('border-green-500', 'border-red-500');
            
            if (email.length === 0) return;
            
            if (!email.includes('@')) {
                emailField.classList.add('border-red-500');
                emailFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                return;
            }

            emailTimeout = setTimeout(() => {
                fetch(`../employee/accountManagement/check_email.php?email=${encodeURIComponent(email)}&current_user=${userId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.available) {
                            emailField.classList.add('border-green-500');
                            emailFeedback.innerHTML = '<i class="fas fa-check text-green-500"></i>';
                        } else {
                            emailField.classList.add('border-red-500');
                            emailFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                            showTooltip(emailFeedback, 'Email already in use');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }, 500);
        });
    }

    // Phone validation with visual feedback
    if (phoneField) {
        const phoneContainer = phoneField.parentElement;
        const phoneFeedback = document.createElement('div');
        phoneFeedback.className = 'absolute right-3 top-1/2 transform -translate-y-1/2';
        phoneContainer.appendChild(phoneFeedback);

        let phoneTimeout;
        phoneField.addEventListener('input', function() {
            clearTimeout(phoneTimeout);
            const phone = this.value.trim();
            
            // Clear previous feedback
            phoneFeedback.innerHTML = '';
            phoneField.classList.remove('border-green-500', 'border-red-500');
            
            if (phone.length === 0) return;
            
            if (!phone.startsWith('09') || phone.length !== 11) {
                phoneField.classList.add('border-red-500');
                phoneFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                return;
            }

            phoneTimeout = setTimeout(() => {
                fetch(`../employee/accountManagement/check_phone.php?phone=${encodeURIComponent(phone)}&current_user=${userId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.available) {
                            phoneField.classList.add('border-green-500');
                            phoneFeedback.innerHTML = '<i class="fas fa-check text-green-500"></i>';
                        } else {
                            phoneField.classList.add('border-red-500');
                            phoneFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                            showTooltip(phoneFeedback, 'Phone number already in use');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }, 500);
        });
    }
}

// Add this function to your script
function setupEditEmailValidation(emailFieldId, errorElementId, userId, userType) {
    const emailField = document.getElementById(emailFieldId);
    const errorElement = document.getElementById(errorElementId);
    
    if (!emailField || !errorElement) return;

    let validationTimeout;
    const emailContainer = emailField.parentElement;
    
    // Create visual feedback element
    const emailFeedback = document.createElement('div');
    emailFeedback.className = 'absolute right-3 top-1/2 transform -translate-y-1/2';
    emailContainer.appendChild(emailFeedback);
    
    emailField.addEventListener('input', function() {
        clearTimeout(validationTimeout);
        const email = this.value.trim();
        
        // Clear previous feedback
        emailFeedback.innerHTML = '';
        emailField.classList.remove('border-green-500', 'border-red-500');
        errorElement.classList.add('hidden');
        
        if (email.length === 0) return;
        
        // Basic format validation
        if (!email.includes('@')) {
            emailField.classList.add('border-red-500');
            emailFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
            errorElement.textContent = 'Please enter a valid email address';
            errorElement.classList.remove('hidden');
            return;
        }

        validationTimeout = setTimeout(() => {
            fetch(`editAccount/check_edit_email.php?email=${encodeURIComponent(email)}&current_user=${userId}&user_type=${userType}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.exists) {
                        emailField.classList.add('border-red-500');
                        emailFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                        errorElement.textContent = data.message || 'Email already registered to another account';
                        errorElement.classList.remove('hidden');
                        showTooltip(emailFeedback, 'Email already in use by another user');
                    } else {
                        emailField.classList.add('border-green-500');
                        emailFeedback.innerHTML = '<i class="fas fa-check text-green-500"></i>';
                        errorElement.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error checking email:', error);
                    // Don't show error to user for failed validation checks
                });
        }, 500);
    });
}


// Helper function to show tooltips
function showTooltip(element, message) {
    const tooltip = document.createElement('div');
    tooltip.className = 'absolute z-10 w-max px-2 py-1 text-xs text-white bg-gray-800 rounded-md opacity-0 transition-opacity duration-200';
    tooltip.textContent = message;
    element.appendChild(tooltip);
    
    // Position tooltip
    tooltip.style.bottom = '100%';
    tooltip.style.left = '50%';
    tooltip.style.transform = 'translateX(-50%)';
    
    setTimeout(() => {
        tooltip.classList.remove('opacity-0');
        tooltip.classList.add('opacity-100');
    }, 10);
    
    // Remove tooltip after delay
    setTimeout(() => {
        tooltip.remove();
    }, 3000);
}

function setupPhoneValidation(phoneFieldId) {
    const phoneField = document.getElementById(phoneFieldId);
    if (!phoneField) return;
    
    phoneField.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
        if (this.value.length > 11) this.value = this.value.substring(0, 11);
        if (this.value.length === 1 && this.value === '9') this.value = '09';
    });
    
    phoneField.addEventListener('keydown', function(e) {
        if (!/[0-9]|Backspace|Delete|ArrowLeft|ArrowRight/.test(e.key)) {
            e.preventDefault();
        }
    });
}

function setupEditPhoneValidation(phoneFieldId, errorElementId, userId, userType) {
    const phoneField = document.getElementById(phoneFieldId);
    const errorElement = document.getElementById(errorElementId);
    
    if (!phoneField || !errorElement) return;

    let validationTimeout;
    const phoneContainer = phoneField.parentElement;
    
    // Create visual feedback element
    const phoneFeedback = document.createElement('div');
    phoneFeedback.className = 'absolute right-3 top-1/2 transform -translate-y-1/2';
    phoneContainer.appendChild(phoneFeedback);
    
    phoneField.addEventListener('input', function() {
        clearTimeout(validationTimeout);
        const phone = this.value.trim();
        
        // Clear previous feedback
        phoneFeedback.innerHTML = '';
        phoneField.classList.remove('border-green-500', 'border-red-500');
        errorElement.classList.add('hidden');
        
        if (phone.length === 0) return;
        
        // Basic format validation
        if (!phone.startsWith('09') || phone.length !== 11) {
            phoneField.classList.add('border-red-500');
            phoneFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
            errorElement.textContent = 'Philippine number must start with 09 and be 11 digits';
            errorElement.classList.remove('hidden');
            return;
        }

        validationTimeout = setTimeout(() => {
            fetch(`editAccount/check_edit_phone.php?phone=${encodeURIComponent(phone)}&current_user=${userId}&user_type=${userType}`)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.json();
                })
                .then(data => {
                    if (data.exists) {
                        phoneField.classList.add('border-red-500');
                        phoneFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                        errorElement.textContent = data.message || 'Phone number already exists in system';
                        errorElement.classList.remove('hidden');
                        showTooltip(phoneFeedback, 'Phone already in use');
                    } else {
                        phoneField.classList.add('border-green-500');
                        phoneFeedback.innerHTML = '<i class="fas fa-check text-green-500"></i>';
                        errorElement.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error checking phone:', error);
                    // Don't show error to user for failed validation checks
                });
        }, 500);
    });
}

function setupEmployeeRealTimeValidation(userId) {
    // Email Validation
    const emailField = document.getElementById('editEmpEmail');
    if (emailField) {
        const emailContainer = emailField.parentElement;
        const emailFeedback = document.createElement('div');
        emailFeedback.className = 'absolute right-3 top-1/2 transform -translate-y-1/2';
        emailContainer.appendChild(emailFeedback);

        let emailTimeout;
        emailField.addEventListener('input', function() {
            clearTimeout(emailTimeout);
            const email = this.value.trim();
            
            // Clear previous feedback
            emailFeedback.innerHTML = '';
            emailField.classList.remove('border-green-500', 'border-red-500');
            document.getElementById('empEmailExistsError').classList.add('hidden');
            
            if (email.length === 0) {
                document.getElementById('empEmailError').classList.add('hidden');
                return;
            }
            
            // Basic format validation
            if (!email.includes('@')) {
                emailField.classList.add('border-red-500');
                emailFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                document.getElementById('empEmailError').textContent = 'Please enter a valid email address';
                document.getElementById('empEmailError').classList.remove('hidden');
                return;
            } else {
                document.getElementById('empEmailError').classList.add('hidden');
            }

            emailTimeout = setTimeout(() => {
                fetch(`editAccount/check_edit_email.php?email=${encodeURIComponent(email)}&current_user=${userId}&user_type=2`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            emailField.classList.add('border-red-500');
                            emailFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                            document.getElementById('empEmailExistsError').textContent = data.message || 'Email already registered to another account';
                            document.getElementById('empEmailExistsError').classList.remove('hidden');
                            showTooltip(emailFeedback, 'Email already in use');
                        } else {
                            emailField.classList.add('border-green-500');
                            emailFeedback.innerHTML = '<i class="fas fa-check text-green-500"></i>';
                            document.getElementById('empEmailExistsError').classList.add('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Error checking email:', error);
                        // Don't show error to user for failed validation checks
                    });
            }, 500);
        });
    }

    // Phone Validation
    const phoneField = document.getElementById('editEmpPhone');
    if (phoneField) {
        const phoneContainer = phoneField.parentElement;
        const phoneFeedback = document.createElement('div');
        phoneFeedback.className = 'absolute right-3 top-1/2 transform -translate-y-1/2';
        phoneContainer.appendChild(phoneFeedback);

        let phoneTimeout;
        phoneField.addEventListener('input', function() {
            clearTimeout(phoneTimeout);
            const phone = this.value.trim();
            
            // Clear previous feedback
            phoneFeedback.innerHTML = '';
            phoneField.classList.remove('border-green-500', 'border-red-500');
            document.getElementById('empPhoneExistsError').classList.add('hidden');
            
            if (phone.length === 0) {
                document.getElementById('empPhoneError').classList.add('hidden');
                return;
            }
            
            // Basic format validation
            if (!phone.startsWith('09') || phone.length !== 11) {
                phoneField.classList.add('border-red-500');
                phoneFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                document.getElementById('empPhoneError').textContent = 'Philippine number must start with 09 and be 11 digits';
                document.getElementById('empPhoneError').classList.remove('hidden');
                return;
            } else {
                document.getElementById('empPhoneError').classList.add('hidden');
            }

            phoneTimeout = setTimeout(() => {
                fetch(`editAccount/check_edit_phone.php?phone=${encodeURIComponent(phone)}&current_user=${userId}&user_type=2`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            phoneField.classList.add('border-red-500');
                            phoneFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                            document.getElementById('empPhoneExistsError').textContent = data.message || 'Phone number already exists in system';
                            document.getElementById('empPhoneExistsError').classList.remove('hidden');
                            showTooltip(phoneFeedback, 'Phone already in use');
                        } else {
                            phoneField.classList.add('border-green-500');
                            phoneFeedback.innerHTML = '<i class="fas fa-check text-green-500"></i>';
                            document.getElementById('empPhoneExistsError').classList.add('hidden');
                        }
                    })
                    .catch(error => {
                        console.error('Error checking phone:', error);
                        // Don't show error to user for failed validation checks
                    });
            }, 500);
        });
    }
}

function checkEmailExists(email, errorElementId) {
    if (!email) return;
    
    fetch('../employee/accountManagement/check_email.php?email=' + encodeURIComponent(email))
        .then(response => response.json())
        .then(data => {
            const errorElement = document.getElementById(errorElementId);
            if (data.exists) {
                errorElement.textContent = 'Email already exists in our system';
                errorElement.classList.remove('hidden');
            } else {
                errorElement.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error checking email:', error);
        });
}

function checkPhoneExists(phone, errorElementId) {
    if (!phone) return;
    
    fetch('../employee/accountManagement/check_phone.php?phone=' + encodeURIComponent(phone))
        .then(response => response.json())
        .then(data => {
            const errorElement = document.getElementById(errorElementId);
            if (data.exists) {
                errorElement.textContent = 'Phone number already exists in our system';
                errorElement.classList.remove('hidden');
            } else {
                errorElement.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error checking phone:', error);
        });
}



function setupEditFormValidations() {
    // Name validation
    const nameFields = ['editFirstName', 'editMiddleName', 'editLastName'];
    nameFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                // Remove invalid characters
                this.value = this.value.replace(/[^a-zA-Z\s'-]/g, '');
                
                // No leading spaces
                if (this.value.startsWith(' ')) {
                    this.value = this.value.substring(1);
                }
                
                // No consecutive spaces
                this.value = this.value.replace(/\s{2,}/g, ' ');
                
                // Capitalize first letter of each word
                if (this.value.length > 0) {
                    this.value = this.value.toLowerCase().replace(/(^|\s)\S/g, function(firstLetter) {
                        return firstLetter.toUpperCase();
                    });
                }
            });
        }
    });

    // Email validation - no spaces
    const emailField = document.getElementById('editEmail');
    if (emailField) {
        emailField.addEventListener('input', function() {
            // Remove any spaces
            if (this.value.includes(' ')) {
                this.value = this.value.replace(/\s/g, '');
            }
        });
        
        emailField.addEventListener('keydown', function(e) {
            if (e.key === ' ') {
                e.preventDefault();
            }
        });
    }

    // Phone validation - Philippine format
    const phoneField = document.getElementById('editPhone');
    if (phoneField) {
        phoneField.addEventListener('input', function() {
            // Remove non-digits
            this.value = this.value.replace(/\D/g, '');
            
            // Limit to 11 characters
            if (this.value.length > 11) {
                this.value = this.value.substring(0, 11);
            }
            
            // Auto-add 09 if starts with 9
            if (this.value.length === 1 && this.value === '9') {
                this.value = '09';
            }
        });
        
        phoneField.addEventListener('keydown', function(e) {
            // Allow only numbers, backspace, delete, arrows
            if (!/[0-9]|Backspace|Delete|ArrowLeft|ArrowRight/.test(e.key)) {
                e.preventDefault();
            }
        });
    }
}

function validateAndSaveCustomerChanges() {
    const form = document.getElementById('editCustomerForm');
    const formData = new FormData(form);
    const currentData = Object.fromEntries(formData.entries());
    
    // Check if no changes were made
     let hasChanges = false;
    if (currentData.first_name !== originalFirstName || 
        currentData.last_name !== originalLastName || 
        currentData.middle_name !== originalMiddleName || 
        currentData.email !== originalEmail || 
        currentData.phone_number !== originalPhone || 
        currentData.branch_loc !== originalBranch) {
        hasChanges = true;
    }
    
    if (!hasChanges) {
        Swal.fire({
            title: 'No Changes',
            text: 'You haven\'t made any changes to save.',
            icon: 'info'
        });
        return;
    }
    // Validate all fields before submission
    const firstName = document.getElementById('editFirstName');
    const lastName = document.getElementById('editLastName');
    const email = document.getElementById('editEmail');
    const phone = document.getElementById('editPhone');
     const emailError = document.getElementById('emailExistsError');
    const phoneError = document.getElementById('phoneExistsError');
    
    let isValid = true;
    
    // If email already exists for another user, block the save
    if (!emailExistsError.classList.contains('hidden')) {
        Swal.fire({
            title: 'Email Already Exists',
            text: 'This email is already registered to another account. Please use a different email.',
            icon: 'error'
        });
        return;
    }
    
    // If phone already exists for another user, block the save
    if (!phoneExistsError.classList.contains('hidden')) {
        Swal.fire({
            title: 'Phone Already Exists',
            text: 'This phone number is already registered to another account. Please use a different number.',
            icon: 'error'
        });
        return;
    }
    
    // Validate required fields
    if (!firstName.value || firstName.value.length < 2) {
        document.getElementById('firstNameError').textContent = 'Please enter at least 2 characters';
        document.getElementById('firstNameError').classList.remove('hidden');
        isValid = false;
    } else {
        document.getElementById('firstNameError').classList.add('hidden');
    }
    
    if (!lastName.value || lastName.value.length < 2) {
        document.getElementById('lastNameError').textContent = 'Please enter at least 2 characters';
        document.getElementById('lastNameError').classList.remove('hidden');
        isValid = false;
    } else {
        document.getElementById('lastNameError').classList.add('hidden');
    }
    
    if (!email.value || !email.value.includes('@')) {
        document.getElementById('emailError').textContent = 'Please enter a valid email address';
        document.getElementById('emailError').classList.remove('hidden');
        isValid = false;
    } else if (document.getElementById('emailExistsError') && !document.getElementById('emailExistsError').classList.contains('hidden')) {
        isValid = false;
    } else {
        document.getElementById('emailError').classList.add('hidden');
    }
    
    if (!phone.value || !phone.value.startsWith('09') || phone.value.length !== 11) {
        document.getElementById('phoneError').textContent = 'Philippine number must start with 09 and be 11 digits';
        document.getElementById('phoneError').classList.remove('hidden');
        isValid = false;
    } else if (document.getElementById('phoneExistsError') && !document.getElementById('phoneExistsError').classList.contains('hidden')) {
        isValid = false;
    } else {
        document.getElementById('phoneError').classList.add('hidden');
    }
    
    if (isValid) {
        // Check if email has changed and needs verification
        const currentEmail = email.value.trim();
        if (currentEmail !== originalEmail) {
            showOtpVerificationModal('customer');
        } else {
            saveCustomerChanges();
        }
    }
}

function closeEditCustomerModal() {
    const modal = document.getElementById('editCustomerModal');
    if (modal) {
        modal.remove();
    }
}

function saveCustomerChanges() {
    const form = document.getElementById('editCustomerForm');
    const formData = new FormData(form);
    
    // Show confirmation dialog
    Swal.fire({
        title: 'Confirm Update',
        text: 'Are you sure you want to update this customer account?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, update it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Updating...',
                html: 'Please wait while we update the customer account.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('editAccount/update_customer_account.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // First check if the response is OK (status 200-299)
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Make sure we got valid JSON data
                 if (!data) throw new Error('No data received from server');
                
                
                // Check if the update was successful
                if (data.success === true) {
                    // Show success message with 2-second timer
                    Swal.fire({
                        title: 'Success!',
                        text: 'Customer account updated successfully.',
                        icon: 'success',
                        timer: 2000, // 2 seconds
                        timerProgressBar: true,
                        showConfirmButton: false,
                        willClose: () => {
                            closeEditCustomerModal();
                            window.location.reload();
                        }
                    });
                } else {
                    throw new Error(data.message || 'Update failed without error message');
                }
            })
            .catch(error => {
                console.error('Update error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: error.message || 'An error occurred while updating the customer account',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
            });
        }
    });
}

//edit employee Accounts
function openEditEmployeeAccountModal(userId) {
    // Fetch employee details
    fetch(`editAccount/fetch_employee_details.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Store original email and phone for comparison
                originalFirstName = data.user.first_name || '';
                originalLastName = data.user.last_name || '';
                originalMiddleName = data.user.middle_name || '';
                originalBranch = data.user.branch_loc || '';
                originalEmail = data.user.email || '';
                originalPhone = data.user.phone_number || '';
                
                // Generate branch options
                let branchOptions = data.branches.map(branch => {
                    const branchName = branch.branch_name
                        .split(' ')
                        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                        .join(' ');
                    
                    return `<option value="${branch.branch_id}" ${data.user.branch_loc == branch.branch_id ? 'selected' : ''}>
                                ${branchName}
                            </option>`;
                }).join('');

                // Create and show the modal
                const modal = document.createElement('div');
                modal.id = 'editEmployeeModal';
                modal.className = 'fixed inset-0 z-50 flex items-center justify-center overflow-y-auto';
                modal.innerHTML = `
                    <!-- Modal Backdrop -->
                    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
                    
                    <!-- Modal Content -->
                    <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
                        <!-- Close Button -->
                        <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeEditEmployeeModal()">
                            <i class="fas fa-times"></i>
                        </button>
                        
                        <!-- Modal Header -->
                        <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
                            <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
                                Edit Employee Account
                            </h3>
                        </div>
                        
                        <!-- Modal Body -->
                        <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
                            <form id="editEmployeeForm" class="space-y-3 sm:space-y-4">
                                <input type="hidden" name="user_id" value="${data.user.id}">
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Employee ID</label>
                                    <div class="relative">
                                        <input type="text" value="#EMP-${String(data.user.id).padStart(3, '0')}" 
                                               class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" readonly>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                                        First Name <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text" id="editEmpFirstName" name="first_name" value="${data.user.first_name ? data.user.first_name[0].toUpperCase() + data.user.first_name.slice(1) : ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                                               pattern="[-A-Za-z']{2,}"
                                               title="Only letters, apostrophes and hyphens allowed (minimum 2 characters)"
                                               required>
                                    </div>
                                    <p id="empFirstNameError" class="text-red-500 text-xs mt-1 hidden"></p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text" id="editEmpLastName" name="last_name" value="${data.user.last_name ? data.user.last_name[0].toUpperCase() + data.user.last_name.slice(1) : ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                                               pattern="[-A-Za-z']{2,}"
                                               title="Only letters, apostrophes and hyphens allowed (minimum 2 characters)"
                                               required>
                                    </div>
                                    <p id="empLastNameError" class="text-red-500 text-xs mt-1 hidden"></p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Middle Name</label>
                                    <div class="relative">
                                        <input type="text" id="editEmpMiddleName" name="middle_name" value="${data.user.middle_name ? data.user.middle_name[0].toUpperCase() + data.user.middle_name.slice(1) : ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                                               pattern="[A-Za-z'-]*"
                                               title="Only letters, apostrophes and hyphens allowed">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                                        Email <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="email" id="editEmpEmail" name="email" value="${data.user.email || ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                                               pattern="[^@\s]+@[^@\s]+\.[^@\s]+"
                                               title="Please enter a valid email address (no spaces allowed)"
                                               required>
                                    </div>
                                    <p id="empEmailError" class="text-red-500 text-xs mt-1 hidden"></p>
                                    <p id="empEmailExistsError" class="text-red-500 text-xs mt-1 hidden"></p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                                        Phone Number <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="tel" id="editEmpPhone" name="phone_number" value="${data.user.phone_number || ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                                               pattern="09\d{9}"
                                               maxlength="11"
                                               title="Philippine number must start with 09 and be 11 digits total"
                                               required>
                                    </div>
                                    <p id="empPhoneError" class="text-red-500 text-xs mt-1 hidden"></p>
                                    <p id="empPhoneExistsError" class="text-red-500 text-xs mt-1 hidden"></p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                                        Branch Location <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <select name="branch_loc" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                                            <option value="">Select Branch</option>
                                            ${branchOptions}
                                        </select>
                                    </div>
                                    <p id="empBranchError" class="text-red-500 text-xs mt-1 hidden">Please select a branch</p>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Modal Footer -->
                        <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
                            <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeEditEmployeeModal()">
                                Cancel
                            </button>
                            <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="validateAndSaveEmployeeChanges()">
                                Save Changes
                            </button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                setupPhoneValidation('editEmpPhone');
                
                setupEditFieldValidation('email', 'editEmpEmail', 'empEmailExistsError', userId, 2);
                setupEditFieldValidation('phone', 'editEmpPhone', 'empPhoneExistsError', userId, 2);
                
                // Add event listener for Escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        closeEditEmployeeModal();
                    }
                });
            } else {
                alert('Failed to fetch employee details: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while fetching employee details');
        });
        
        
        

}

function setupEmployeeRealTimeValidation(userId) {
    const emailField = document.getElementById('editEmpEmail');
    const phoneField = document.getElementById('editEmpPhone');
    
    // Email validation with visual feedback
    if (emailField) {
        const emailContainer = emailField.parentElement;
        const emailFeedback = document.createElement('div');
        emailFeedback.className = 'absolute right-3 top-1/2 transform -translate-y-1/2';
        emailContainer.appendChild(emailFeedback);

        let emailTimeout;
        emailField.addEventListener('input', function() {
            clearTimeout(emailTimeout);
            const email = this.value.trim();
            
            // Clear previous feedback
            emailFeedback.innerHTML = '';
            emailField.classList.remove('border-green-500', 'border-red-500');
            
            if (email.length === 0) return;
            
            if (!email.includes('@')) {
                emailField.classList.add('border-red-500');
                emailFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                return;
            }

            emailTimeout = setTimeout(() => {
                fetch(`editAccount/check_edit_email.php?email=${encodeURIComponent(email)}&current_user=${userId}&user_type=2`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.available) {
                            emailField.classList.add('border-green-500');
                            emailFeedback.innerHTML = '<i class="fas fa-check text-green-500"></i>';
                        } else {
                            emailField.classList.add('border-red-500');
                            emailFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                            showTooltip(emailFeedback, 'Email already in use');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }, 500);
        });
    }

    // Phone validation with visual feedback
    if (phoneField) {
        const phoneContainer = phoneField.parentElement;
        const phoneFeedback = document.createElement('div');
        phoneFeedback.className = 'absolute right-3 top-1/2 transform -translate-y-1/2';
        phoneContainer.appendChild(phoneFeedback);

        let phoneTimeout;
        phoneField.addEventListener('input', function() {
            clearTimeout(phoneTimeout);
            const phone = this.value.trim();
            
            // Clear previous feedback
            phoneFeedback.innerHTML = '';
            phoneField.classList.remove('border-green-500', 'border-red-500');
            
            if (phone.length === 0) return;
            
            if (!phone.startsWith('09') || phone.length !== 11) {
                phoneField.classList.add('border-red-500');
                phoneFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                return;
            }

            phoneTimeout = setTimeout(() => {
                fetch(`editAccount/check_edit_phone.php?phone=${encodeURIComponent(phone)}&current_user=${userId}&user_type=2`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.available) {
                            phoneField.classList.add('border-green-500');
                            phoneFeedback.innerHTML = '<i class="fas fa-check text-green-500"></i>';
                        } else {
                            phoneField.classList.add('border-red-500');
                            phoneFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                            showTooltip(phoneFeedback, 'Phone number already in use');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }, 500);
        });
    }
}

function setupEditEmployeeValidations() {
    // Name validation
    const nameFields = ['editEmpFirstName', 'editEmpMiddleName', 'editEmpLastName'];
    nameFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            field.addEventListener('input', function() {
                // Remove invalid characters
                this.value = this.value.replace(/[^a-zA-Z\s'-]/g, '');
                
                // No leading spaces
                if (this.value.startsWith(' ')) {
                    this.value = this.value.substring(1);
                }
                
                // No consecutive spaces
                this.value = this.value.replace(/\s{2,}/g, ' ');
                
                // Capitalize first letter of each word
                if (this.value.length > 0) {
                    this.value = this.value.toLowerCase().replace(/(^|\s)\S/g, function(firstLetter) {
                        return firstLetter.toUpperCase();
                    });
                }
            });
        }
    });

    // Email validation - no spaces
    const emailField = document.getElementById('editEmpEmail');
    if (emailField) {
        emailField.addEventListener('input', function() {
            // Remove any spaces
            if (this.value.includes(' ')) {
                this.value = this.value.replace(/\s/g, '');
            }
        });
        
        emailField.addEventListener('keydown', function(e) {
            if (e.key === ' ') {
                e.preventDefault();
            }
        });
    }

    // Phone validation - Philippine format
    const phoneField = document.getElementById('editEmpPhone');
    if (phoneField) {
        phoneField.addEventListener('input', function() {
            // Remove non-digits
            this.value = this.value.replace(/\D/g, '');
            
            // Limit to 11 characters
            if (this.value.length > 11) {
                this.value = this.value.substring(0, 11);
            }
            
            // Auto-add 09 if starts with 9
            if (this.value.length === 1 && this.value === '9') {
                this.value = '09';
            }
        });
        
        phoneField.addEventListener('keydown', function(e) {
            // Allow only numbers, backspace, delete, arrows
            if (!/[0-9]|Backspace|Delete|ArrowLeft|ArrowRight/.test(e.key)) {
                e.preventDefault();
            }
        });
    }
}

function validateAndSaveEmployeeChanges() {
    const form = document.getElementById('editEmployeeForm');
    const formData = new FormData(form);
    const currentData = Object.fromEntries(formData.entries());
    
    // Check if no changes were made
    let hasChanges = false;
    if (currentData.first_name !== originalFirstName || 
        currentData.last_name !== originalLastName || 
        currentData.middle_name !== originalMiddleName || 
        currentData.email !== originalEmail || 
        currentData.phone_number !== originalPhone || 
        currentData.branch_loc !== originalBranch) {
        hasChanges = true;
    }
    
    if (!hasChanges) {
        Swal.fire({
            title: 'No Changes',
            text: 'You haven\'t made any changes to save.',
            icon: 'info'
        });
        return;
    }

    // Validate all fields before submission
    const firstName = document.getElementById('editEmpFirstName');
    const lastName = document.getElementById('editEmpLastName');
    const email = document.getElementById('editEmpEmail');
    const phone = document.getElementById('editEmpPhone');
    
    const emailExistsError = document.getElementById('empEmailExistsError');
    const phoneExistsError = document.getElementById('empPhoneExistsError');
    
    let isValid = true;
    
    // First check basic validation
    if (!firstName.value || firstName.value.length < 2) {
        document.getElementById('empFirstNameError').textContent = 'Please enter at least 2 characters';
        document.getElementById('empFirstNameError').classList.remove('hidden');
        isValid = false;
    } else {
        document.getElementById('empFirstNameError').classList.add('hidden');
    }
    
    if (!lastName.value || lastName.value.length < 2) {
        document.getElementById('empLastNameError').textContent = 'Please enter at least 2 characters';
        document.getElementById('empLastNameError').classList.remove('hidden');
        isValid = false;
    } else {
        document.getElementById('empLastNameError').classList.add('hidden');
    }
    
    if (!email.value || !email.value.includes('@')) {
        document.getElementById('empEmailError').textContent = 'Please enter a valid email address';
        document.getElementById('empEmailError').classList.remove('hidden');
        isValid = false;
    } else {
        document.getElementById('empEmailError').classList.add('hidden');
    }
    
    if (!phone.value || !phone.value.startsWith('09') || phone.value.length !== 11) {
        document.getElementById('empPhoneError').textContent = 'Philippine number must start with 09 and be 11 digits';
        document.getElementById('empPhoneError').classList.remove('hidden');
        isValid = false;
    } else {
        document.getElementById('empPhoneError').classList.add('hidden');
    }
    
    // Then check if there are any existing email/phone errors
    if (!emailExistsError.classList.contains('hidden')) {
        isValid = false;
        Swal.fire({
            title: 'Email Already Exists',
            text: emailExistsError.textContent,
            icon: 'error'
        });
    }
    
    if (!phoneExistsError.classList.contains('hidden')) {
        isValid = false;
        Swal.fire({
            title: 'Phone Already Exists',
            text: phoneExistsError.textContent,
            icon: 'error'
        });
    }
    
    if (isValid) {
        // Check if email has changed and needs verification
        const currentEmail = email.value.trim();
        if (currentEmail !== originalEmail) {
            showOtpVerificationModal('employee');
        } else {
            saveEmployeeChanges();
        }
    }
}

function closeEditEmployeeModal() {
    const modal = document.getElementById('editEmployeeModal');
    if (modal) {
        modal.remove();
    }
}

function saveEmployeeChanges() {
    const form = document.getElementById('editEmployeeForm');
    const formData = new FormData(form);
    
    // Show confirmation dialog
    Swal.fire({
        title: 'Confirm Update',
        text: 'Are you sure you want to update this employee account?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, update it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Updating...',
                html: 'Please wait while we update the employee account.',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            fetch('editAccount/update_employee_account.php', {
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
                 if (!data) throw new Error('No data received from server');
                
                if (data.success === true) {
                    // Show success message with 2-second timer - MATCHING CUSTOMER UI
                    Swal.fire({
                        title: 'Success!',
                        text: 'Employee account updated successfully.',
                        icon: 'success',
                        timer: 2000, // 2 seconds
                        timerProgressBar: true,
                        showConfirmButton: false,
                        willClose: () => {
                            closeEditEmployeeModal();
                            window.location.reload();
                        }
                    });
                } else {
                    throw new Error(data.message || 'Update failed without error message');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: error.message || 'An error occurred while updating the employee account',
                    icon: 'error',
                    confirmButtonColor: '#3085d6'
                });
            });
        }
    });
}

let resendOtpTimer = null;
let resendOtpTimeLeft = 60; // 60 seconds countdown

// OTP Verification Modal Functions
function showOtpVerificationModal(accountType) {
    if (isVerificationInProgress) return;
    isVerificationInProgress = true;
    
    const emailField = accountType === 'customer' ? document.getElementById('editEmail') : document.getElementById('editEmpEmail');
    const email = emailField.value.trim();
    
    // Create OTP verification modal with higher z-index
    otpVerificationModal = document.createElement('div');
    otpVerificationModal.id = 'otpVerificationModal';
    otpVerificationModal.className = 'fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto';
    otpVerificationModal.innerHTML = `
        <!-- Modal Backdrop - darker and higher z-index -->
        <div class="fixed inset-0 bg-black bg-opacity-70 backdrop-blur-md z-[101]"></div>
        
        <!-- Modal Content - highest z-index -->
        <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 sm:mx-auto z-[102] transform transition-all duration-300">
            <!-- Close Button -->
            <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 transition-colors" onclick="closeOtpVerificationModal()">
                <i class="fas fa-times"></i>
            </button>
            
            <!-- Modal Header -->
            <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
                <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
                    Verify Email Address
                </h3>
            </div>
            
            <!-- Modal Body -->
            <div class="px-4 sm:px-6 py-4 sm:py-5">
                <p class="text-sm text-gray-600 mb-4">We've sent a 6-digit verification code to <span class="font-semibold">${email}</span>. Please enter it below:</p>
                
                <form id="otpVerificationForm" class="space-y-4">
                    <div class="flex justify-center gap-2">
                        <input type="text" maxlength="1" class="w-12 h-12 text-center text-2xl border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200 otp-input" pattern="\d" required>
                        <input type="text" maxlength="1" class="w-12 h-12 text-center text-2xl border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200 otp-input" pattern="\d" required>
                        <input type="text" maxlength="1" class="w-12 h-12 text-center text-2xl border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200 otp-input" pattern="\d" required>
                        <input type="text" maxlength="1" class="w-12 h-12 text-center text-2xl border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200 otp-input" pattern="\d" required>
                        <input type="text" maxlength="1" class="w-12 h-12 text-center text-2xl border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200 otp-input" pattern="\d" required>
                        <input type="text" maxlength="1" class="w-12 h-12 text-center text-2xl border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200 otp-input" pattern="\d" required>
                    </div>
                    
                    <p id="otpError" class="text-red-500 text-sm text-center hidden"></p>
                    
                    <div class="text-center">
                        <button type="button" id="resendOtpBtn" class="text-sm text-sidebar-accent hover:underline" onclick="resendOtp('${accountType}')">
                            Didn't receive code? Resend
                        </button>
                        <span id="resendTimer" class="text-sm text-gray-500 hidden"></span>
                    </div>
                </form>
            </div>
            
            <!-- Modal Footer -->
            <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200">
                <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200" onclick="closeOtpVerificationModal()">
                    Cancel
                </button>
                <button type="button" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300" onclick="verifyOtp('${accountType}')">
                    Verify
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(otpVerificationModal);
    
    // Setup OTP input fields behavior
    const otpInputs = document.querySelectorAll('.otp-input');
    otpInputs.forEach((input, index) => {
        // Allow only numbers
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            
            // Auto-focus next input
            if (this.value.length === 1 && index < otpInputs.length - 1) {
                otpInputs[index + 1].focus();
            }
        });
        
        // Handle backspace
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Backspace' && this.value.length === 0 && index > 0) {
                otpInputs[index - 1].focus();
            }
        });
    });
    
    // Focus first input
    if (otpInputs.length > 0) {
        otpInputs[0].focus();
    }
    
    // Send OTP immediately when modal opens
    sendOtp(email, accountType).then(() => {
        // Start the timer only after successful OTP send
        startResendTimer();
    });
}

function closeOtpVerificationModal() {
    if (otpVerificationModal) {
        otpVerificationModal.remove();
        otpVerificationModal = null;
    }
    isVerificationInProgress = false;
    
    // Clear the timer when modal is closed
    if (resendOtpTimer) {
        clearInterval(resendOtpTimer);
        resendOtpTimer = null;
    }
}


function sendOtp(email, accountType) {
    return new Promise((resolve, reject) => {
        fetch('editAccount/send_otp.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `email=${encodeURIComponent(email)}`
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                document.getElementById('otpError').textContent = data.message || 'Failed to send OTP';
                document.getElementById('otpError').classList.remove('hidden');
                reject(data.message);
            } else {
                document.getElementById('otpError').classList.add('hidden');
                resolve();
            }
        })
        .catch(error => {
            console.error('Error sending OTP:', error);
            document.getElementById('otpError').textContent = 'Failed to send OTP. Please try again.';
            document.getElementById('otpError').classList.remove('hidden');
            reject(error);
        });
    });
}

function verifyOtp(accountType) {
    const otpInputs = document.querySelectorAll('.otp-input');
    const otp = Array.from(otpInputs).map(input => input.value).join('');
    
    if (otp.length !== 6) {
        Swal.fire({
            title: 'Incomplete Code',
            text: 'Please enter the complete 6-digit code',
            icon: 'warning',
            confirmButtonColor: '#3085d6'
        });
        return;
    }
    
    // Show loading indicator
    Swal.fire({
        title: 'Verifying...',
        html: 'Please wait while we verify your code.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    fetch('editAccount/verify_otp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `otp=${encodeURIComponent(otp)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Verified!',
                text: 'Your email has been successfully verified',
                icon: 'success',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                closeOtpVerificationModal();
                
                // Proceed with saving changes
                if (accountType === 'customer') {
                    saveCustomerChanges();
                } else {
                    saveEmployeeChanges();
                }
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: data.message || 'Invalid verification code',
                icon: 'error',
                confirmButtonColor: '#3085d6'
            });
        }
    })
    .catch(error => {
        console.error('Error verifying OTP:', error);
        Swal.fire({
            title: 'Error!',
            text: 'Error verifying code. Please try again.',
            icon: 'error',
            confirmButtonColor: '#3085d6'
        });
    });
}
// The rest of your existing functions remain unchanged...
</script>

<script>
    
    document.querySelector('[onclick="showArchivedCustomer"]').setAttribute('onclick', 'showArchivedCustomer()');
    document.querySelector('[onclick="showArchivedEmployee"]').setAttribute('onclick', 'showArchivedEmployee()');

// Function to show archived customers
function showArchivedCustomer() {
    document.getElementById('modalTitle').textContent = 'Archived Customer Accounts';
    fetchArchivedAccounts(3); // 3 is the user_type for customers
    document.getElementById('archivedModal').classList.remove('hidden');
}

// Function to show archived employees
function showArchivedEmployee() {
    document.getElementById('modalTitle').textContent = 'Archived Employee Accounts';
    fetchArchivedAccounts(2); // 2 is the user_type for employees
    document.getElementById('archivedModal').classList.remove('hidden');
}

// Function to close the modal
function closeArchivedModal() {
    document.getElementById('archivedModal').classList.add('hidden');
}

// Function to fetch archived accounts
function fetchArchivedAccounts(userType) {
    fetch(`accountManagement/fetch_archived_accounts.php?user_type=${userType}`)
        .then(response => response.json())
        .then(data => {
            const tableBody = document.getElementById('archivedAccountsTable');
            tableBody.innerHTML = data.tableContent;
        })
        .catch(error => console.error('Error:', error));
}

// Function to unarchive an account
function unarchiveAccount(userId) {
    Swal.fire({
        title: 'Confirm',
        text: 'Are you sure you want to unarchive this account?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, unarchive it!'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('accountManagement/unarchive_account.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${userId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: 'Account unarchived successfully!',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload(); // Reload the page after clicking OK
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: 'Error unarchiving account: ' + data.message,
                        icon: 'error'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error',
                    text: 'An error occurred while unarchiving the account.',
                    icon: 'error'
                });
            });
        }
    });
}
function closeArchivedModal() {
    document.getElementById('archivedModal').classList.add('hidden');
}
</script>


<script>
function deleteCustomerAccount(userId) {
    Swal.fire({
        title: 'Archive Customer Account?',
        text: "Are you sure you want to Archive this customer account?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Archive it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to update the database
            updateUserStatus(userId, 3, 0); // 3 = customer, 0 = inactive
        }
    });
}

function deleteEmployeeAccount(userId) {
    Swal.fire({
        title: 'Archive Employee Account?',
        text: "Are you sure you want to Archive this employee account?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, Archive it!'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send AJAX request to update the database
            updateUserStatus(userId, 2, 0); // 2 = employee, 0 = inactive
        }
    });
}

function updateUserStatus(userId, userType, status) {
    // Create AJAX request
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'accountManagement/archive_user.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                
                if (response.success) {
                    Swal.fire(
                        'Archived!',
                        'The account has been Archived successfully.',
                        'success'
                    ).then(() => {
                        // Reload the page to reflect changes
                        location.reload();
                    });
                } else {
                    Swal.fire(
                        'Error!',
                        'There was an error deleting the account: ' + response.message,
                        'error'
                    );
                }
            } catch (e) {
                Swal.fire(
                    'Error!',
                    'Invalid response from server.',
                    'error'
                );
                console.error('Invalid JSON response:', this.responseText);
            }
        }
    };
    
    xhr.send(`user_id=${userId}&user_type=${userType}&status=${status}`);
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements for employee table
    const searchInput = document.getElementById('searchInput');
    const searchInputMobile = document.getElementById('searchInputMobile');
    const filterToggle = document.getElementById('filterToggle');
    const filterToggleMobile = document.getElementById('filterToggleMobile');
    const filterDropdown = document.getElementById('filterDropdown');
    const filterDropdownMobile = document.getElementById('filterDropdownMobile');
    const filterOptions = document.querySelectorAll('#filterDropdown .filter-option');
    const filterOptionsMobile = document.querySelectorAll('#filterDropdownMobile .filter-option-mobile');
    const filterIndicator = document.getElementById('filterIndicator');
    const filterIndicatorMobile = document.getElementById('filterIndicatorMobile');
    
    const employeeTableBody = document.getElementById('employeeTableBody');
    const empPaginationInfoElement = document.getElementById('empPaginationInfo');
    const empPaginationContainer = document.getElementById('empPaginationContainer');
    const empShowingFrom = document.getElementById('empShowingFrom');
    const empShowingTo = document.getElementById('empShowingTo');
    const empTotalCount = document.getElementById('empTotalCount');
    
    let currentEmpSearch = '';
    let currentEmpSort = 'id_asc';
    let currentEmpPage = 1;
    let totalEmpPages = 1;

    // Function to create pagination buttons for employees
    function createEmpPaginationButtons() {
        empPaginationContainer.innerHTML = ''; // Clear existing buttons
        
        // Previous button
        const prevButton = document.createElement('button');
        prevButton.innerHTML = '&laquo;';
        prevButton.className = 'px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover' + 
            (currentEmpPage === 1 ? ' opacity-50 cursor-not-allowed' : '');
        prevButton.disabled = currentEmpPage === 1;
        prevButton.addEventListener('click', () => {
            if (currentEmpPage > 1) {
                currentEmpPage--;
                fetchEmployeeAccounts();
            }
        });
        empPaginationContainer.appendChild(prevButton);
        
        // Page number buttons - show up to 5 pages around current page
        const startPage = Math.max(1, currentEmpPage - 2);
        const endPage = Math.min(totalEmpPages, currentEmpPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = i;
            pageButton.className = 'px-3.5 py-1.5 border border-sidebar-border rounded text-sm ' + 
                (i === currentEmpPage 
                    ? 'bg-sidebar-accent text-white' 
                    : 'hover:bg-sidebar-hover');
            pageButton.addEventListener('click', () => {
                currentEmpPage = i;
                fetchEmployeeAccounts();
            });
            empPaginationContainer.appendChild(pageButton);
        }
        
        // Next button
        const nextButton = document.createElement('button');
        nextButton.innerHTML = '&raquo;';
        nextButton.className = 'px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover' + 
            (currentEmpPage === totalEmpPages ? ' opacity-50 cursor-not-allowed' : '');
        nextButton.disabled = currentEmpPage === totalEmpPages;
        nextButton.addEventListener('click', () => {
            if (currentEmpPage < totalEmpPages) {
                currentEmpPage++;
                fetchEmployeeAccounts();
            }
        });
        empPaginationContainer.appendChild(nextButton);
    }

    // Function to fetch employee accounts via AJAX
    function fetchEmployeeAccounts() {
        // Create a new XMLHttpRequest
        const xhr = new XMLHttpRequest();
        
        // Prepare the URL with search, sort, and page parameters
        const url = `addEmployee/fetch_employee_accounts.php?search=${encodeURIComponent(currentEmpSearch)}&sort=${encodeURIComponent(currentEmpSort)}&page=${currentEmpPage}`;
        
        xhr.open('GET', url, true);
        
        xhr.onload = function() {
            if (xhr.status === 200) {
                try {
                    // Parse the JSON response
                    const response = JSON.parse(xhr.responseText);
                    
                    // Update table body
                    employeeTableBody.innerHTML = response.tableContent || `
                        <tr>
                            <td colspan="6" class="text-center p-4 text-gray-500">
                                No employee accounts found.
                            </td>
                        </tr>
                    `;
                    
                    if (response.showingFrom && response.showingTo && response.totalCount) {
                empShowingFrom.textContent = response.showingFrom;
                empShowingTo.textContent = response.showingTo;
                empTotalCount.textContent = response.totalCount;
                document.getElementById('totalEmployees').textContent = response.totalCount;
            }
                    
                    // Update total pages and current page
                    totalEmpPages = response.totalPages || 1;
            currentEmpPage = response.currentPage || 1;
                    
                    // Create pagination buttons
                    createEmpPaginationButtons();
                    
                    // Update filter indicators if a sort is applied
                    if (currentEmpSort !== 'id_asc') {
                filterIndicator.classList.remove('hidden');
                filterIndicatorMobile.classList.remove('hidden');
            } else {
                filterIndicator.classList.add('hidden');
                filterIndicatorMobile.classList.add('hidden');
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    employeeTableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="text-center p-4 text-red-500">
                                Error loading data. Please try again.
                            </td>
                        </tr>
                    `;
                }
            
            }
        };
        
        xhr.onerror = function() {
            console.error('Network error occurred');
            employeeTableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center p-4 text-red-500">
                        Network error. Please check your connection.
                    </td>
                </tr>
            `;
        };
        
        xhr.send();
    }

    // Initial load of employee accounts
    fetchEmployeeAccounts();

    // Filter dropdown toggle for desktop
    filterToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        filterDropdown.classList.toggle('hidden');
        filterDropdownMobile.classList.add('hidden');
    });

    // Filter dropdown toggle for mobile
    filterToggleMobile.addEventListener('click', function(e) {
        e.stopPropagation();
        filterDropdownMobile.classList.toggle('hidden');
        filterDropdown.classList.add('hidden');
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        filterDropdown.classList.add('hidden');
        filterDropdownMobile.classList.add('hidden');
    });

    // Prevent dropdown from closing when clicking inside
    filterDropdown.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    filterDropdownMobile.addEventListener('click', function(e) {
        e.stopPropagation();
    });

    // Search functionality with debounce for both desktop and mobile
    function setupSearchInput(inputElement) {
        let searchTimeout;
        inputElement.addEventListener('input', function() {
            currentEmpSearch = this.value;
            currentEmpPage = 1; // Reset to first page when searching
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            // Set new timeout to reduce unnecessary API calls
            searchTimeout = setTimeout(() => {
                fetchEmployeeAccounts();
            }, 300); // 300ms delay
        });
    }
    
    setupSearchInput(searchInput);
    setupSearchInput(searchInputMobile);

    // Filter option selection for both desktop and mobile
    function setupFilterOptions(options, dropdown) {
        options.forEach(option => {
            option.addEventListener('click', function(e) {
                e.preventDefault();
                currentEmpSort = this.getAttribute('data-sort');
                currentEmpPage = 1; // Reset to first page when changing sort
                
                // Close dropdown
                dropdown.classList.add('hidden');
                
                // Fetch employee accounts with selected sort
                fetchEmployeeAccounts();
            });
        });
    }
    
    setupFilterOptions(filterOptions, filterDropdown);
    setupFilterOptions(filterOptionsMobile, filterDropdownMobile);
});
// Function to open the add employee account modal
function openAddEmployeeAccountModal() {
  const modal = document.getElementById('addEmployeeAccountModal');
  if (modal) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    
    // Generate password when modal opens
    updateEmpPassword();
  }
}

// Function to close the add employee account modal
function closeAddEmployeeAccountModal() {
  const modal = document.getElementById('addEmployeeAccountModal');
  if (modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }
}

</script>

<!-- Employee Account Modal -->
<div id="addEmployeeAccountModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeAddEmployeeAccountModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Add Employee Account
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="addEmployeeAccountForm" method="post" action="addEmployee/add_employee.php" class="space-y-3 sm:space-y-4">
        <!-- Personal Information Section -->
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
          <div class="w-full sm:flex-1">
            <label for="empFirstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              First Name <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input type="text" id="empFirstName" name="firstName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="First Name" required>
            </div>
            <p id="empFirstNameError" class="text-red-500 text-xs mt-1 hidden">First name is required</p>
          </div>
          
          <div class="w-full sm:flex-1">
            <label for="empMiddleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Middle Name
            </label>
            <div class="relative">
              <input type="text" id="empMiddleName" name="middleName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Middle Name">
            </div>
          </div>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
          <div class="w-full sm:flex-1">
            <label for="empLastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Last Name <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <input type="text" id="empLastName" name="lastName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Last Name" required>
            </div>
            <p id="empLastNameError" class="text-red-500 text-xs mt-1 hidden">Last name is required</p>
          </div>
          
          <div class="w-full sm:flex-1">
            <label for="suffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Suffix <span class="text-xs text-gray-500">(Optional)</span>
            </label>
            <div class="relative">
              <select id="suffix" name="suffix" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                <option value="">Select Suffix</option>
                <option value="Jr">Jr</option>
                <option value="Sr">Sr</option>
                <option value="I">I</option>
                <option value="II">II</option>
                <option value="III">III</option>
                <option value="IV">IV</option>
                <option value="V">V</option>
              </select>
            </div>
          </div>
        </div>
        
        <div>
          <label for="empBirthdate" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Birthdate <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="date" id="empBirthdate" name="birthdate" 
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                  max="<?php echo date('Y-m-d'); ?>" 
                  required>
          </div>
          <p id="empBirthdateError" class="text-red-500 text-xs mt-1 hidden">Birthdate is required and cannot be in the future</p>
        </div>
        
        <div>
          <label for="empBranchLocation" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Branch Location <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <select id="empBranchLocation" name="branchLocation" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
              <option value="">Select Branch</option>
              <!-- Branch options will be populated by JavaScript -->
            </select>
          </div>
          <p id="empBranchError" class="text-red-500 text-xs mt-1 hidden">Please select a branch</p>
        </div>
        
        <div>
          <label for="employeeEmail" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Email <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="email" id="employeeEmail" name="email" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Email" required>
          </div>
          <p id="empEmailError" class="text-red-500 text-xs mt-1 hidden"></p>
        </div>
        
        <div>
          <label for="employeePhone" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Phone Number <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="tel" id="employeePhone" name="phoneNumber" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Phone Number" required>
          </div>
          <p id="empPhoneError" class="text-red-500 text-xs mt-1 hidden"></p>
        </div>
        
        <div>
          <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Generated Password
          </label>
          <div class="relative">
            <input type="password" id="empGeneratedPassword" name="password" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200 bg-gray-100" readonly autocomplete="new-password">
            <button type="button" class="absolute right-2 top-2 text-gray-500 hover:text-gray-700" onclick="toggleEmpPassword()">
              <svg id="empEyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 sm:w-6 sm:h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 12s2.947-5.455 8.02-5.455S20.02 12 20.02 12s-2.947 5.455-8.02 5.455S3.98 12 3.98 12z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
              </svg>
            </button>
          </div>
        </div>
        
        <!-- Additional Information Card -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border-l-4 border-sidebar-accent mt-3 sm:mt-4">
          <h4 class="text-xs sm:text-sm font-medium text-gray-700 mb-2 flex items-center">
            <i class="fas fa-info-circle mr-2 text-sidebar-accent"></i>
            Account Information
          </h4>
          <p class="text-xs sm:text-sm text-gray-600">
            An employee account will be created with the provided information. A temporary password will be generated automatically.
          </p>
          <p class="text-xs sm:text-sm text-gray-600 mt-2">
            The employee will be able to change their password after logging in for the first time.
          </p>
        </div>
        
        <input type="hidden" name="user_type" value="2">
        <input type="hidden" name="is_verified" value="1">
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeAddEmployeeAccountModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="submitEmployeeForm()">
        <i class="fas fa-user-plus mr-2"></i> Create Account
      </button>
    </div>
  </div>
</div>
<!-- Employee OTP Verification Modal -->
<div id="empOtpVerificationModal" class="fixed inset-0 bg-black bg-opacity-60 z-50 hidden overflow-y-auto flex items-center justify-center p-4 w-full h-full">
  <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-2">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-darkgold flex justify-between items-center p-6 flex-shrink-0 rounded-t-xl">
      <h3 class="text-xl font-bold text-white"><i class="fas fa-shield-alt"></i> Email Verification</h3>
      <button onclick="closeEmpOtpModal()" class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="p-6">
      <p class="text-gray-700 mb-4">A verification code has been sent to <span id="empOtpEmail" class="font-medium"></span>. Please enter the code below.</p>
      <div class="flex justify-center gap-2 mb-4">
        <input type="text" class="emp-otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="emp-otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="emp-otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="emp-otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="emp-otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
        <input type="text" class="emp-otp-input w-12 h-12 border border-gray-300 rounded-md text-center text-xl font-bold" maxlength="1" autocomplete="off">
      </div>
      <div id="empOtpError" class="text-red-500 text-center text-sm mb-4 hidden"></div>
      <p class="text-sm text-gray-500 text-center">Didn't receive the code? <button type="button" onclick="resendEmpOTP()" class="text-sidebar-accent hover:underline">Resend</button></p>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white rounded-b-xl">
      <button onclick="closeEmpOtpModal()" class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors">
        Cancel
      </button>
      <button onclick="verifyEmpOTP()" class="px-6 py-3 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center">
        <i class="fas fa-check-circle mr-2"></i> Verify
      </button>
    </div>
  </div>
</div>

<!--employee validations-->
<script>

document.getElementById("employeePhone").addEventListener("input", function (e) {
    this.value = this.value.replace(/[^0-9]/g, ""); // Remove non-numeric characters
  });
  // Load branch locations when the document is ready
document.addEventListener('DOMContentLoaded', function() {
  loadBranchLocations();
  loadEmpBranchLocations();
  
  // Add input event listeners for password generation
  document.getElementById('firstName').addEventListener('input', updatePassword);
  document.getElementById('lastName').addEventListener('input', updatePassword);
  document.getElementById('birthdate').addEventListener('input', updatePassword);

  // Add input event listeners for employee password generation
  document.getElementById('empFirstName').addEventListener('input', updateEmpPassword);
  document.getElementById('empLastName').addEventListener('input', updateEmpPassword);
  document.getElementById('empBirthdate').addEventListener('input', updateEmpPassword);

  // First Name validation
  document.getElementById('empFirstName').addEventListener('input', validateEmpFirstName);
  
  // Middle Name validation
  document.getElementById('empMiddleName').addEventListener('input', validateEmpMiddleName);
  
  // Last Name validation
  document.getElementById('empLastName').addEventListener('input', validateEmpLastName);
  
  // Birthdate validation
  document.getElementById('empBirthdate').addEventListener('change', validateEmpBirthdate);
  
  // Email validation
  document.getElementById('employeeEmail').addEventListener('input', validateEmpEmail);
  
  // Phone Number validation
  document.getElementById('employeePhone').addEventListener('input', validateEmpPhoneNumber);
  
  // Branch Location validation
  document.getElementById('empBranchLocation').addEventListener('change', validateEmpBranchLocation);
});

document.addEventListener('DOMContentLoaded', function() {
    // Employee Name fields
    const employeeNameFields = [
        'empFirstName', 
        'empMiddleName', 
        'empLastName'
    ];

    function validateNameInput(field) {
        if (!field || !field.value) return '';
        
        // First, remove any invalid characters
        let newValue = field.value.replace(/[^a-zA-Z\s'-]/g, '');
        
        // Don't allow space as first character
        if (newValue.startsWith(' ')) {
            newValue = newValue.substring(1);
        }
        
        // Don't allow consecutive spaces
        newValue = newValue.replace(/\s{2,}/g, ' ');
        
        // Only allow space after at least 2 characters
        if (newValue.length < 2 && newValue.includes(' ')) {
            newValue = newValue.replace(/\s/g, '');
        }
        
        // Update the field value
        field.value = newValue;
        
        // Capitalize first letter of each word
        if (field.value.length > 0) {
            field.value = field.value.toLowerCase().replace(/(^|\s)\S/g, function(firstLetter) {
                return firstLetter.toUpperCase();
            });
        }
        
        return field.value;
    }
    // Function to apply validation to a field
    function applyNameValidation(field) {
        if (field) {
            // Validate on input
            field.addEventListener('input', function() {
                validateNameInput(this);
            });

            // Validate on blur (when field loses focus)
            field.addEventListener('blur', function() {
                validateNameInput(this);
            });

            // Prevent paste of invalid content
            field.addEventListener('paste', function(e) {
                e.preventDefault();
                const pastedText = (e.clipboardData || window.clipboardData).getData('text');
                const cleanedText = pastedText.replace(/[^a-zA-Z\s'-]/g, '');
                document.execCommand('insertText', false, cleanedText);
            });
        }
    }

    // Apply validation to employee name fields
    employeeNameFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        applyNameValidation(field);
    });

    // Additional validation for required fields
    const requiredEmployeeFields = ['empFirstName', 'empLastName'];

    // Function to apply required validation
    function applyRequiredValidation(field) {
        if (field) {
            field.addEventListener('blur', function() {
                if (this.value.trim().length < 2) {
                    this.setCustomValidity('Please enter at least 2 characters');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    }

    // Apply to employee fields
    requiredEmployeeFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        applyRequiredValidation(field);
    });

    // Validate birthdate to ensure employee is at least 18 years old
    function validateEmpBirthdate() {
        const birthdateField = document.getElementById('empBirthdate');
        if (birthdateField) {
            const selectedDate = new Date(birthdateField.value);
            const today = new Date();
            const minBirthdate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
            
            if (selectedDate > minBirthdate) {
                birthdateField.setCustomValidity('Employee must be at least 18 years old');
            } else {
                birthdateField.setCustomValidity('');
            }
        }
    }

    // Set max date for birthdate field to 18 years ago
    function setEmpMaxBirthdate() {
        const birthdateField = document.getElementById('empBirthdate');
        if (birthdateField) {
            const today = new Date();
            const maxDate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
            birthdateField.max = maxDate.toISOString().split('T')[0];
        }
    }

    // Validate email input
    function validateEmpEmail() {
        const emailField = document.getElementById('employeeEmail');
        if (emailField) {
            // Remove any spaces from the email in real-time
            if (emailField.value.includes(' ')) {
                emailField.value = emailField.value.replace(/\s/g, '');
            }
            
            // Check basic email format requirements
            if (emailField.value.length > 0) {
                if (!emailField.value.includes('@')) {
                    emailField.setCustomValidity('Email must contain @ symbol');
                } else if (emailField.value.indexOf('@') === 0) {
                    emailField.setCustomValidity('Email cannot start with @');
                } else if (emailField.value.indexOf('@') === emailField.value.length - 1) {
                    emailField.setCustomValidity('Email cannot end with @');
                } else {
                    emailField.setCustomValidity('');
                }
            } else {
                emailField.setCustomValidity('');
            }
        }
    }

    // Setup email field validation
    const empEmailField = document.getElementById('employeeEmail');
    if (empEmailField) {
        // Validate on every input
        empEmailField.addEventListener('input', validateEmpEmail);
        
        // Prevent pasting spaces
        empEmailField.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const cleanedText = pastedText.replace(/\s/g, '');
            document.execCommand('insertText', false, cleanedText);
        });
        
        // Prevent spacebar key
        empEmailField.addEventListener('keydown', function(e) {
            if (e.key === ' ' || e.code === 'Space') {
                e.preventDefault();
            }
        });
    }

    // Validate Philippine phone number
    function validateEmpPhoneNumber() {
        const phoneField = document.getElementById('employeePhone');
        if (phoneField) {
            // Remove any non-digit characters
            let phoneNumber = phoneField.value.replace(/\D/g, '');
            
            // Limit to 11 characters
            if (phoneNumber.length > 11) {
                phoneNumber = phoneNumber.substring(0, 11);
            }
            
            // Update the field value
            phoneField.value = phoneNumber;
            
            // Validate the format
            if (phoneNumber.length > 0) {
                if (!phoneNumber.startsWith('09')) {
                    phoneField.setCustomValidity('Philippine numbers must start with 09');
                } else if (phoneNumber.length < 11) {
                    phoneField.setCustomValidity('Phone number must be 11 digits');
                } else {
                    phoneField.setCustomValidity('');
                }
            } else {
                phoneField.setCustomValidity('');
            }
        }
    }

    // Format phone number as user types
    function formatEmpPhoneNumber() {
        const phoneField = document.getElementById('employeePhone');
        if (phoneField) {
            let phoneNumber = phoneField.value.replace(/\D/g, '');
            
            // Auto-add 09 if user starts with 9
            if (phoneNumber.length === 1 && phoneNumber === '9') {
                phoneNumber = '09';
            }
            
            // Format with spaces for better readability (optional)
            if (phoneNumber.length > 4) {
                phoneNumber = phoneNumber.replace(/(\d{4})(\d{3})(\d{4})/, '$1 $2 $3');
            } else if (phoneNumber.length > 2) {
                phoneNumber = phoneNumber.replace(/(\d{2})(\d+)/, '$1 $2');
            }
            
            phoneField.value = phoneNumber;
        }
    }

    // Initialize phone field event listeners
    const empPhoneField = document.getElementById('employeePhone');
    if (empPhoneField) {
        empPhoneField.addEventListener('input', function() {
            formatEmpPhoneNumber();
            validateEmpPhoneNumber();
        });
        
        empPhoneField.addEventListener('keydown', function(e) {
            // Allow: backspace, delete, tab, escape, enter
            if ([46, 8, 9, 27, 13].includes(e.keyCode) || 
                // Allow: Ctrl+A
                (e.keyCode == 65 && e.ctrlKey === true) || 
                // Allow: home, end, left, right
                (e.keyCode >= 35 && e.keyCode <= 39)) {
                return;
            }
            // Ensure it's a number and stop the keypress if not
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
    }

    // Initialize max birthdate when page loads
    setEmpMaxBirthdate();

    // Event listeners for the employee form
    document.getElementById('empFirstName').addEventListener('input', validateNameInput);
    document.getElementById('empMiddleName').addEventListener('input', validateNameInput);
    document.getElementById('empLastName').addEventListener('input', validateNameInput);
    document.getElementById('empBirthdate').addEventListener('change', validateEmpBirthdate);
    document.getElementById('employeeEmail').addEventListener('input', validateEmpEmail);
    document.getElementById('employeePhone').addEventListener('input', validateEmpPhoneNumber);
    document.getElementById('branchLocation').addEventListener('change', validateBranchLocation);
  });



function validateEmpBranchLocation() {
  const branchSelect = document.getElementById('empBranchLocation');
  const branchError = document.getElementById('empBranchError');
  if (branchSelect.value === '') {
    branchError.classList.remove('hidden');
    return false;
  } else {
    branchError.classList.add('hidden');
    return true;
  }
}

// Add the following functions to your employee script

// Show OTP modal and send OTP for employees
function showEmpOTPModal() {
  // Set the email in the OTP modal
  const email = document.getElementById('employeeEmail').value;
  document.getElementById('empOtpEmail').textContent = email;
  
  console.log("Sending OTP to email:", email);
  
  // Send OTP to email
  const formData = new FormData();
  formData.append('email', email);
  
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'addEmployee/send_employee_otp.php', true);
  
  xhr.onload = function() {
    console.log("Raw server response:", this.responseText);
    
    if (this.status === 200) {
      try {
        const response = JSON.parse(this.responseText);
        console.log("Parsed response:", response);
        
        if (response.success) {
          console.log("OTP sent successfully");
          // Show OTP modal
          const modal = document.getElementById('empOtpVerificationModal');
          modal.classList.remove('hidden');
          modal.classList.add('flex');
          
          // Focus on first OTP input
          const otpInputs = document.querySelectorAll('.emp-otp-input');
          if (otpInputs.length > 0) {
            otpInputs[0].focus();
          }
        } else {
          console.error("Server error:", response.message);
          alert('Error: ' + response.message);
        }
      } catch (e) {
        console.error("Error parsing response:", e);
        console.error("Response text:", this.responseText);
        alert('Error processing server response. Check console for details.');
      }
    } else {
      console.error("HTTP error:", this.status);
      alert('Server returned an error: ' + this.status);
    }
  };
  
  xhr.onerror = function() {
    console.error("Network error");
    alert('Network error occurred');
  };
  
  xhr.send(formData);
}

// Close OTP modal for employees
function closeEmpOtpModal() {
  const modal = document.getElementById('empOtpVerificationModal');
  modal.classList.add('hidden');
  modal.classList.remove('flex');
  
  // Clear OTP inputs
  const otpInputs = document.querySelectorAll('.emp-otp-input');
  otpInputs.forEach(input => {
    input.value = '';
  });
  
  // Hide error message
  document.getElementById('empOtpError').classList.add('hidden');
}

// Resend OTP for employees
function resendEmpOTP() {
  const email = document.getElementById('employeeEmail').value;
  const formData = new FormData();
  formData.append('email', email);
  
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'addEmployee/send_employee_otp.php', true);
  
  xhr.onload = function() {
    if (this.status === 200) {
      try {
        const response = JSON.parse(this.responseText);
        // Success notification (top-right)
        if (response.success) {
          Swal.fire({
            title: 'OTP Sent!',
            text: 'A new OTP has been sent to your email address.',
            icon: 'success',
            toast: true,
            position: 'top-end', 
            showConfirmButton: false,
            timer: 1000,
            timerProgressBar: true,
            background: '#f8f9fa',
            iconColor: '#28a745',
            width: '400px',
            padding: '1em',
            customClass: {
              container: 'custom-swal-container',
              popup: 'custom-swal-popup'
            }
          });
        } 
        // Error notification (top-right)
        else {
          Swal.fire({
            title: 'Failed',
            text: response.message || 'Failed to send OTP',
            icon: 'error',
            toast: true,
            position: 'top-end', // top-right corner
            showConfirmButton: false,
            timer: 4000, // Longer display for errors
            timerProgressBar: true,
            background: '#f8f9fa',
            iconColor: '#dc3545',
            width: '400px',
            padding: '1em',
            customClass: {
              container: 'custom-swal-container',
              popup: 'custom-swal-popup-error' // Special class for errors
            }
          });
        }
      } catch (e) {
        Swal.fire({
          title: 'Error',
          text: 'Invalid server response',
          icon: 'error'
        });
      }
    }
  };
  
  xhr.onerror = function() {
    Swal.fire({
      title: 'Connection Error',
      text: 'Failed to connect to server',
      icon: 'error'
    });
  };
  
  xhr.send(formData);
}

// Verify OTP and submit form for employees
function verifyEmpOTP() {
  // Collect OTP from inputs
  const otpInputs = document.querySelectorAll('.emp-otp-input');
  let otpValue = '';
  
  otpInputs.forEach(input => {
    otpValue += input.value;
  });
  
  // Check if OTP is complete
  if (otpValue.length !== 6) {
    document.getElementById('empOtpError').textContent = 'Please enter all 6 digits';
    document.getElementById('empOtpError').classList.remove('hidden');
    return;
  }
  
  // Verify OTP
  const formData = new FormData();
  formData.append('otp', otpValue);
  
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'addEmployee/verify_employee_otp.php', true);
  
  xhr.onload = function() {
    if (this.status === 200) {
      const response = JSON.parse(this.responseText);
      if (response.success) {
        // OTP verified, proceed with form submission
        actuallySubmitEmpForm();
      } else {
        document.getElementById('empOtpError').textContent = response.message;
        document.getElementById('empOtpError').classList.remove('hidden');
      }
    }
  };
  
  xhr.send(formData);
}

// Handle OTP input functionality for employees
document.addEventListener('DOMContentLoaded', function() {
  const empOtpInputs = document.querySelectorAll('.emp-otp-input');
  
  empOtpInputs.forEach((input, index) => {
    // Auto-focus next input
    input.addEventListener('input', function() {
      if (input.value.length === 1) {
        if (index < empOtpInputs.length - 1) {
          empOtpInputs[index + 1].focus();
        }
      }
    });
    
    // Handle backspace
    input.addEventListener('keydown', function(e) {
      if (e.key === 'Backspace' && input.value === '' && index > 0) {
        empOtpInputs[index - 1].focus();
      }
    });
    
    // Allow only numbers
    input.addEventListener('input', function() {
      input.value = input.value.replace(/[^0-9]/g, '');
    });
  });
});

// Add this function to check phone number availability before submission
function checkPhoneAvailability() {
  const phoneInput = document.getElementById('employeePhone');
  const phoneError = document.getElementById('empPhoneError');
  const phone = phoneInput.value.trim();
  
  // Only proceed if the phone number passed basic validation
  if (!validateEmpPhoneNumber()) {
    return Promise.reject('Phone number is invalid');
  }
  
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'addEmployee/check_phone.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onload = function() {
      if (this.status === 200) {
        try {
          const response = JSON.parse(this.responseText);
          if (response.available) {
            phoneError.classList.add('hidden');
            resolve(true);
          } else {
            phoneError.textContent = 'Phone number already in use';
            phoneError.classList.remove('hidden');
            reject('Phone number already in use');
          }
        } catch (e) {
          console.error("Error parsing response:", e);
          phoneError.textContent = 'Error checking phone number';
          phoneError.classList.remove('hidden');
          reject('Error checking phone number');
        }
      } else {
        phoneError.textContent = 'Error checking phone number';
        phoneError.classList.remove('hidden');
        reject('Error checking phone number');
      }
    };
    
    xhr.onerror = function() {
      phoneError.textContent = 'Network error occurred';
      phoneError.classList.remove('hidden');
      reject('Network error occurred');
    };
    
    xhr.send('phoneNumber=' + encodeURIComponent(phone));
  });
}

// Employee validation functions
function validateEmpFirstName() {
    const firstName = document.getElementById('empFirstName');
    const errorElement = document.getElementById('empFirstNameError');
    
    if (!firstName.value || firstName.value.length < 2) {
        errorElement.textContent = 'Please enter at least 2 characters';
        errorElement.classList.remove('hidden');
        return false;
    }
    
    errorElement.classList.add('hidden');
    return true;
}

function validateEmpMiddleName() {
    // Middle name is optional, so validation always passes
    return true;
}

function validateEmpLastName() {
    const lastName = document.getElementById('empLastName');
    const errorElement = document.getElementById('empLastNameError');
    
    if (!lastName.value || lastName.value.length < 2) {
        errorElement.textContent = 'Please enter at least 2 characters';
        errorElement.classList.remove('hidden');
        return false;
    }
    
    errorElement.classList.add('hidden');
    return true;
}

function validateEmpBirthdate() {
    const birthdateField = document.getElementById('empBirthdate');
    const errorElement = document.getElementById('empBirthdateError');
    
    if (!birthdateField.value) {
        errorElement.textContent = 'Birthdate is required';
        errorElement.classList.remove('hidden');
        return false;
    }
    
    const selectedDate = new Date(birthdateField.value);
    const today = new Date();
    const minBirthdate = new Date(today.getFullYear() - 18, today.getMonth(), today.getDate());
    
    if (selectedDate > minBirthdate) {
        errorElement.textContent = 'Employee must be at least 18 years old';
        errorElement.classList.remove('hidden');
        return false;
    }
    
    errorElement.classList.add('hidden');
    return true;
}

function validateEmpEmail() {
    const emailField = document.getElementById('employeeEmail');
    const errorElement = document.getElementById('empEmailError');
    
    // Add null checks
    if (!emailField || !errorElement) {
        console.error("Email validation elements not found");
        return false;
    }
    
    if (!emailField.value || !emailField.value.includes('@')) {
        errorElement.textContent = 'Please enter a valid email address';
        errorElement.classList.remove('hidden');
        return false;
    }
    
    errorElement.classList.add('hidden');
    return true;
}

function validateEmpPhoneNumber() {
    const phoneField = document.getElementById('employeePhone');
    const errorElement = document.getElementById('empPhoneError');
    
    // Add null checks
    if (!phoneField || !errorElement) {
        console.error("Phone validation elements not found");
        return false;
    }
    
    if (!phoneField.value || !phoneField.value.startsWith('09') || phoneField.value.length !== 11) {
        errorElement.textContent = 'Philippine number must start with 09 and be 11 digits';
        errorElement.classList.remove('hidden');
        return false;
    }
    
    errorElement.classList.add('hidden');
    return true;
}
// Modify your existing submitEmployeeForm function to use OTP verification
function submitEmployeeForm() {
    
    console.log("Checking if elements exist:");
    console.log("employeeEmail:", document.getElementById('employeeEmail'));
    console.log("empEmailError:", document.getElementById('empEmailError'));
    console.log("employeePhone:", document.getElementById('employeePhone'));
    console.log("empPhoneError:", document.getElementById('empPhoneError'));
  // Validate all fields
  const isValid = validateEmpFirstName() && 
                  validateEmpMiddleName() && 
                  validateEmpLastName() && 
                  validateEmpBirthdate() && 
                  validateEmpEmail() && 
                  validateEmpPhoneNumber() && 
                  validateEmpBranchLocation();
  
  if (isValid) {
    // Generate password if not already generated
    if (document.getElementById('empGeneratedPassword').value === '') {
      updateEmpPassword();
    }
    
    // First check if phone number is available
    checkPhoneAvailability()
      .then(() => {
        // If phone is available, show OTP verification modal
        showEmpOTPModal();
      })
      .catch((error) => {
        console.error("Phone validation error:", error);
        // Error handling already done in checkPhoneAvailability function
      });
  }
}

// Actual form submission after OTP verification
function actuallySubmitEmpForm() {
  const form = document.getElementById('addEmployeeAccountForm');
  const formData = new FormData(form);
  
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'addEmployee/add_employee.php', true);
  
  xhr.onload = function() {
    console.log("Raw response text:", this.responseText);
    
    if (this.status === 200) {
      try {
        if (!this.responseText.trim()) {
          throw new Error("Empty server response");
        }
        
        const response = JSON.parse(this.responseText);
        console.log("Parsed server response:", response);
        
        if (response.success) {
          // Close OTP modal immediately
          closeEmpOtpModal();
          
          // Show success notification
          Swal.fire({
            title: 'Success!',
            text: 'Employee account created successfully!',
            icon: 'success',
            confirmButtonColor: '#28a745',
            showConfirmButton: true,
            allowOutsideClick: false,
            willClose: () => {
              // Close main modal
              const modalElement = document.getElementById('addEmployeeAccountModal');
              if (modalElement) {
                modalElement.classList.remove('show');
                modalElement.setAttribute('aria-hidden', 'true');
                modalElement.style.display = 'none';
                
                // Remove backdrop if exists
                const backdrop = document.querySelector('.modal-backdrop');
                if (backdrop) {
                  backdrop.remove();
                }
              }
              // Refresh page after slight delay
              setTimeout(() => location.reload(), 300);
            }
          });
          
        } else {
          // Show error notification
          Swal.fire({
            title: 'Error!',
            html: `<div style="
              color: #721c24;
              background-color: #f8d7da;
              padding: 10px;
              border-radius: 5px;
              border-left: 4px solid #f5c6cb;
              margin: 8px 0;
            ">
              ${response.message || 'Unknown error occurred'}
            </div>`,
            icon: 'error',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Try Again'
          });
        }
      } catch (e) {
        console.error("Detailed parsing error:", e);
        console.error("Response that caused error:", this.responseText);
        Swal.fire({
          title: 'Parsing Error',
          html: `<div style="
            color: #856404;
            background-color: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            border-left: 4px solid #ffeeba;
          ">
            Error parsing server response<br><br>
            <small>${e.message}</small>
          </div>`,
          icon: 'warning',
          confirmButtonColor: '#ffc107'
        });
      }
    } else {
      console.error("HTTP Error:", this.status, this.statusText);
      Swal.fire({
        title: 'Request Failed',
        text: `Server returned status: ${this.status} (${this.statusText})`,
        icon: 'error',
        confirmButtonColor: '#6c757d'
      });
    }
  };
  
  xhr.onerror = function() {
    console.error("Network error occurred");
    Swal.fire({
      title: 'Network Error',
      text: 'Failed to connect to server. Please check your internet connection.',
      icon: 'error',
      confirmButtonColor: '#6c757d',
      confirmButtonText: 'Retry'
    });
  };
  
  xhr.send(formData);
}

// Existing functions from the original script remain the same
function loadEmpBranchLocations() {
  const xhr = new XMLHttpRequest();
  xhr.open('GET', 'addCustomer/get_branches.php', true);
  
  xhr.onload = function() {
    if (this.status === 200) {
      const branches = JSON.parse(this.responseText);
      const branchSelect = document.getElementById('empBranchLocation');
      
      // Clear existing options except the first one
      while (branchSelect.options.length > 1) {
        branchSelect.remove(1);
      }
      
      branches.forEach(branch => {
        const option = document.createElement('option');
        option.value = branch.branch_id;
        option.textContent = branch.branch_name;
        branchSelect.appendChild(option);
      });
    }
  };
  
  xhr.send();
}

function updateEmpPassword() {
  const firstName = document.getElementById('empFirstName').value.trim();
  const lastName = document.getElementById('empLastName').value.trim();
  const birthdate = document.getElementById('empBirthdate').value;
  
  if (firstName !== '' && lastName !== '' && birthdate !== '') {
    // Format: First letter of first name (uppercase) + First letter of last name (lowercase) + birthdate
    const password = firstName.charAt(0).toUpperCase() + lastName.charAt(0).toLowerCase() + birthdate.replace(/-/g, '');
    document.getElementById('empGeneratedPassword').value = password;
  } else {
    document.getElementById('empGeneratedPassword').value = '';
  }
}

function toggleEmpPassword() {
  const passwordField = document.getElementById('empGeneratedPassword');
  const eyeIcon = document.getElementById('empEyeIcon');
  
  if (passwordField.type === 'password') {
    passwordField.type = 'text';
    eyeIcon.innerHTML = `
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8c0 1.211.765 2.996 3.917 4.945C10.778 14.845 12.9 16 16.02 16c3.121 0 5.223-1.155 8.085-3.055C26.235 10.996 28 9.211 28 8s-1.765-2.996-3.896-4.945C21.244 1.155 19.142 0 16.02 0c-3.121 0-5.243 1.155-8.104 3.055C5.745 5.004 3.98 6.789 3.98 8z"/>
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 5a3 3 0 110 6 3 3 0 010-6z"/>
    `;
  } else {
    passwordField.type = 'password';
    eyeIcon.innerHTML = `
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 12s2.947-5.455 8.02-5.455S20.02 12 20.02 12s-2.947 5.455-8.02 5.455S3.98 12 3.98 12z" />
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
    `;
  }
}

// Existing functions like loadBranchLocations(), updatePassword(), togglePassword() remain the same
function loadBranchLocations() {
  const xhr = new XMLHttpRequest();
  xhr.open('GET', 'addCustomer/get_branches.php', true);
  
  xhr.onload = function() {
    if (this.status === 200) {
      const branches = JSON.parse(this.responseText);
      const branchSelect = document.getElementById('branchLocation');
      
      branches.forEach(branch => {
        const option = document.createElement('option');
        option.value = branch.branch_id;
        option.textContent = branch.branch_name;
        branchSelect.appendChild(option);
      });
    }
  };
  
  xhr.send();
}

function updatePassword() {
  const firstName = document.getElementById('firstName').value.trim();
  const lastName = document.getElementById('lastName').value.trim();
  const birthdate = document.getElementById('birthdate').value;
  
  if (firstName !== '' && lastName !== '' && birthdate !== '') {
    // Format: First letter of first name (uppercase) + First letter of last name (lowercase) + birthdate
    const password = firstName.charAt(0).toUpperCase() + lastName.charAt(0).toLowerCase() + birthdate.replace(/-/g, '');
    document.getElementById('generatedPassword').value = password;
  } else {
    document.getElementById('generatedPassword').value = '';
  }
}

function togglePassword() {
  const passwordField = document.getElementById('generatedPassword');
  const eyeIcon = document.getElementById('eyeIcon');
  
  if (passwordField.type === 'password') {
    passwordField.type = 'text';
    eyeIcon.innerHTML = `
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8c0 1.211.765 2.996 3.917 4.945C10.778 14.845 12.9 16 16.02 16c3.121 0 5.223-1.155 8.085-3.055C26.235 10.996 28 9.211 28 8s-1.765-2.996-3.896-4.945C21.244 1.155 19.142 0 16.02 0c-3.121 0-5.243 1.155-8.104 3.055C5.745 5.004 3.98 6.789 3.98 8z"/>
      <path stroke-linecap="round" stroke-linejoin="round" d="M15 5a3 3 0 110 6 3 3 0 010-6z"/>
    `;
  } else {
    passwordField.type = 'password';
    eyeIcon.innerHTML = `
      <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 12s2.947-5.455 8.02-5.455S20.02 12 20.02 12s-2.947 5.455-8.02 5.455S3.98 12 3.98 12z" />
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
    `;
  }
}
</script>

<div id="editEmployeeAccountModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
    <!-- Modal content for editing an employee account -->
</div>

</div>

  <script src="script.js"></script>
  <script src="tailwind.js"></script>

  
</body>
</html>