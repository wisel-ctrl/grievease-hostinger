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
          <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent" onclick="showArchivedCustomerMobile">
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

    // Search input validation for all search bars
function setupSearchInputValidation(inputElement) {
    inputElement.addEventListener('input', function(e) {
        let value = this.value;
        // Remove multiple consecutive spaces
        value = value.replace(/\s{2,}/g, ' ');
        // Only allow space after at least 2 characters
        if (value.length < 2) {
            value = value.replace(/\s/g, '');
        }
        this.value = value;
    });
}

// Apply validation to all search bars (customer and employee, desktop and mobile)
document.addEventListener('DOMContentLoaded', function() {
    var customerSearchInput = document.getElementById('customerSearchInput');
    var customerSearchInputMobile = document.getElementById('customerSearchInputMobile');
    var searchInput = document.getElementById('searchInput');
    var searchInputMobile = document.getElementById('searchInputMobile');
    if (customerSearchInput) setupSearchInputValidation(customerSearchInput);
    if (customerSearchInputMobile) setupSearchInputValidation(customerSearchInputMobile);
    if (searchInput) setupSearchInputValidation(searchInput);
    if (searchInputMobile) setupSearchInputValidation(searchInputMobile);
});

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
          <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent" onclick="showArchivedEmployeeMobile">
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
    <div id="empPaginationContainer" class="flex space-x-1">
        <!-- Previous Button -->
        <?php if ($page > 1): ?>
            <button onclick="changeEmpPage(<?php echo $page - 1; ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">
                &laquo;
            </button>
        <?php else: ?>
            <button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">
                &laquo;
            </button>
        <?php endif; ?>

        <!-- Page Numbers -->
        <?php 
        $startPage = max(1, $page - 2);
        $endPage = min($totalPages, $page + 2);
        
        for ($i = $startPage; $i <= $endPage; $i++): ?>
            <button onclick="changeEmpPage(<?php echo $i; ?>)" 
                    class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm <?php echo $i == $page ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?>">
                <?php echo $i; ?>
            </button>
        <?php endfor; ?>

        <!-- Next Button -->
        <?php if ($page < $totalPages): ?>
            <button onclick="changeEmpPage(<?php echo $page + 1; ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">
                &raquo;
            </button>
        <?php else: ?>
            <button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">
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
                            <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200" onclick="closeEditEmployeeModal()">
                                Cancel
                            </button>
                            <button type="button" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300" onclick="validateAndSaveEmployeeChanges()">
                                Save Changes
                            </button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                setupEmployeeRealTimeValidation(userId);
                

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
            document.getElementById('empEmailError').classList.add('hidden');
            document.getElementById('empEmailExistsError').classList.add('hidden');
            
            if (email.length === 0) return;
            
            // Basic format validation
            if (!email.includes('@')) {
                emailField.classList.add('border-red-500');
                emailFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                document.getElementById('empEmailError').textContent = 'Please enter a valid email address';
                document.getElementById('empEmailError').classList.remove('hidden');
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
                            document.getElementById('empEmailExistsError').textContent = data.message || 'Email already registered to another account';
                            document.getElementById('empEmailExistsError').classList.remove('hidden');
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
            document.getElementById('empPhoneError').classList.add('hidden');
            document.getElementById('empPhoneExistsError').classList.add('hidden');
            
            if (phone.length === 0) return;
            
            // Basic format validation
            if (!phone.startsWith('09') || phone.length !== 11) {
                phoneField.classList.add('border-red-500');
                phoneFeedback.innerHTML = '<i class="fas fa-times text-red-500"></i>';
                document.getElementById('empPhoneError').textContent = 'Philippine number must start with 09 and be 11 digits';
                document.getElementById('empPhoneError').classList.remove('hidden');
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
                            document.getElementById('empPhoneExistsError').textContent = data.message || 'Phone number already exists';
                            document.getElementById('empPhoneExistsError').classList.remove('hidden');
                            showTooltip(phoneFeedback, 'Phone number already in use');
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }, 500);
        });

        // Add input formatting
        phoneField.addEventListener('input', function() {
            this.value = this.value.replace(/\D/g, '');
            if (this.value.length > 11) this.value = this.value.substring(0, 11);
            if (this.value.length === 1 && this.value === '9') this.value = '09';
        });

        // Add keydown restrictions
        phoneField.addEventListener('keydown', function(e) {
            if (!/[0-9]|Backspace|Delete|ArrowLeft|ArrowRight/.test(e.key)) {
                e.preventDefault();
            }
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
            icon: 'error'
        });
    }
    
    if (!phoneExistsError.classList.contains('hidden')) {
        isValid = false;
        Swal.fire({
            title: 'Phone Already Exists',
            icon: 'error'
        });
    }
    
    if (isValid) {
        // Check if email has changed and needs verification
        const currentEmail = email.value.trim();
        if (currentEmail !== originalEmail) {
            showEmpOTPModal();
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
  const otp = Array.from(otpInputs).map(input => input.value).join('');
  
  // Check if OTP is complete
  if (otp.length !== 6) {
    document.getElementById('empOtpError').textContent = 'Please enter all 6 digits';
    document.getElementById('empOtpError').classList.remove('hidden');
    return;
  }
  
  // Verify OTP
  const formData = new FormData();
  formData.append('otp', otp);
  
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