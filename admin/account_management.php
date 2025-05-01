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
                                            ID: Ascending
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option" data-sort="id_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            ID: Descending
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
                                            ID: Ascending
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="id_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            ID: Descending
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
        
        // Previous button
        const prevButton = document.createElement('button');
        prevButton.innerHTML = '&laquo;';
        prevButton.className = 'px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover' + 
            (currentPage === 1 ? ' opacity-50 cursor-not-allowed' : '');
        prevButton.disabled = currentPage === 1;
        prevButton.addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                fetchCustomerAccounts();
            }
        });
        paginationContainer.appendChild(prevButton);
        
        // Page number buttons - show up to 5 pages around current page
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = i;
            pageButton.className = 'px-3.5 py-1.5 border border-sidebar-border rounded text-sm ' + 
                (i === currentPage 
                    ? 'bg-sidebar-accent text-white' 
                    : 'hover:bg-sidebar-hover');
            pageButton.addEventListener('click', () => {
                currentPage = i;
                fetchCustomerAccounts();
            });
            paginationContainer.appendChild(pageButton);
        }
        
        // Next button
        const nextButton = document.createElement('button');
        nextButton.innerHTML = '&raquo;';
        nextButton.className = 'px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover' + 
            (currentPage === totalPages ? ' opacity-50 cursor-not-allowed' : '');
        nextButton.disabled = currentPage === totalPages;
        nextButton.addEventListener('click', () => {
            if (currentPage < totalPages) {
                currentPage++;
                fetchCustomerAccounts();
            }
        });
        paginationContainer.appendChild(nextButton);
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
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
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
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <form id="addCustomerAccountForm" method="post" action="addCustomer/add_customer.php" class="space-y-3 sm:space-y-4">
        <!-- Personal Information Section -->
        <div>
          <label for="firstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            First Name <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="text" id="firstName" name="firstName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="First Name" required>
          </div>
          <p id="firstNameError" class="text-red-500 text-xs mt-1 hidden"></p>
        </div>
        
        <div>
          <label for="lastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Last Name <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="text" id="lastName" name="lastName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Last Name" required>
          </div>
          <p id="lastNameError" class="text-red-500 text-xs mt-1 hidden"></p>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
          <div class="w-full sm:flex-1">
            <label for="middleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Middle Name
            </label>
            <div class="relative">
              <input type="text" id="middleName" name="middleName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Middle Name">
            </div>
            <p id="middleNameError" class="text-red-500 text-xs mt-1 hidden"></p>
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
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0 rounded-t-xl">
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
      <button onclick="verifyOTP()" class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center">
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

    // Existing event listeners for the customer form
    document.getElementById('firstName').addEventListener('input', validateFirstName);
    document.getElementById('middleName').addEventListener('input', validateMiddleName);
    document.getElementById('lastName').addEventListener('input', validateLastName);
    document.getElementById('birthdate').addEventListener('change', validateBirthdate);
    document.getElementById('customerEmail').addEventListener('input', validateEmail);
    document.getElementById('customerPhone').addEventListener('input', validatePhoneNumber);
    document.getElementById('branchLocation').addEventListener('change', validateBranchLocation);
});

function validateBirthdate() {
  const birthdateInput = document.getElementById('birthdate');
  const birthdateError = document.getElementById('birthdateError');
  const birthdate = birthdateInput.value;

  if (birthdate === '') {
    birthdateError.textContent = 'Birthdate is required';
    birthdateError.classList.remove('hidden');
    return false;
  } 

  const today = new Date();
  const birthdateObj = new Date(birthdate);
  let age = today.getFullYear() - birthdateObj.getFullYear();
  const monthDiff = today.getMonth() - birthdateObj.getMonth();
  
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdateObj.getDate())) {
    age--;
  }
  
  if (age < 18) {
    birthdateError.textContent = 'You must be at least 18 years old';
    birthdateError.classList.remove('hidden');
    return false;
  } else {
    birthdateError.classList.add('hidden');
    return true;
  }
}

function validateEmail() {
  const emailInput = document.getElementById('customerEmail');
  const emailError = document.getElementById('emailError');
  const email = emailInput.value.trim();
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (email === '') {
    emailError.textContent = 'Email is required';
    emailError.classList.remove('hidden');
    return false;
  } else if (!emailPattern.test(email)) {
    emailError.textContent = 'Please enter a valid email address';
    emailError.classList.remove('hidden');
    return false;
  } else {
    emailError.classList.add('hidden');
    return true;
  }
}

function validatePhoneNumber() {
  const phoneInput = document.getElementById('customerPhone');
  const phoneError = document.getElementById('phoneError');
  const phone = phoneInput.value.trim();
  const phonePattern = /^09\d{9}$/;

  // Remove any non-digit characters
  const cleanedPhone = phone.replace(/[^0-9]/g, '');

  if (phone === '') {
    phoneError.textContent = 'Phone number is required';
    phoneError.classList.remove('hidden');
    return false;
  } else if (!phonePattern.test(cleanedPhone)) {
    phoneError.textContent = 'Please enter a valid 11-digit mobile number (e.g., 09123456789)';
    phoneError.classList.remove('hidden');
    return false;
  } else {
    phoneError.classList.add('hidden');
    return true;
  }
}

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
function resendOTP() {

  const email = document.getElementById('customerEmail').value;
  const formData = new FormData();
  formData.append('email', email);
  
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'addCustomer/send_otp.php', true);
  
  xhr.onload = function() {
    if (this.status === 200) {
      try {
        const response = JSON.parse(this.responseText);
        if (response.success) {
          Swal.fire({
            title: 'OTP Sent!',
            text: 'A new OTP has been sent to your email address.',
            icon: 'success',
            toast: true,
            position: 'top-end', 
            showConfirmButton: false,
            timer: 5000,
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
        } else {
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

// Construct the SQL query with search and sorting
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
$sql .= " LIMIT $perPage OFFSET $offset";

$result = $conn->query($sql);

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM users WHERE user_type = 2 AND is_verified = 1";
if (!empty($search)) {
    $countSql .= " AND (first_name LIKE '%$search%' OR last_name LIKE '%$search%' OR email LIKE '%$search%')";
}
$countResult = $conn->query($countSql);
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

// Initialize empty table content
$tableContent = '';

// Check if there are results
if ($result->num_rows > 0) {
    // Create table rows with actual data
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
                                            ID: Ascending
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option" data-sort="id_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            ID: Descending
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
                                            ID: Ascending
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer filter-option-mobile" data-sort="id_desc">
                                        <span class="hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            ID: Descending
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
        <div class="text-sm text-gray-500 text-center sm:text-left">
            <?php echo $paginationInfo; ?>
        </div>
        <div class="flex space-x-1">
            <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $page <= 1 ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                onclick="changePage(<?php echo $page - 1; ?>)" <?php echo $page <= 1 ? 'disabled' : ''; ?>>&laquo;</button>
            
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <button class="px-3 py-1 border border-sidebar-border rounded text-sm <?php echo $i == $page ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?> pagination-button" 
                    onclick="changePage(<?php echo $i; ?>)"><?php echo $i; ?></button>
            <?php endfor; ?>
            
            <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $page >= $totalPages ? 'opacity-50 cursor-not-allowed' : ''; ?>" 
                onclick="changePage(<?php echo $page + 1; ?>)" <?php echo $page >= $totalPages ? 'disabled' : ''; ?>>&raquo;</button>
        </div>
    </div>
</div>

<!-- Archived Accounts Modal -->
<div id="archivedModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg w-3/4 max-w-4xl max-h-[80vh] flex flex-col">
        <!-- Modal Header -->
        <div class="p-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-semibold" id="modalTitle">Archived Accounts</h3>
            <button onclick="closeArchivedModal()" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-4 overflow-y-auto flex-grow">
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
        <div class="p-4 border-t border-gray-200 flex justify-end">
            <button onclick="closeArchivedModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 transition">
                Close
            </button>
        </div>
    </div>
</div>

<!--OPEN EDIT CUSTOMER/EMPLOYEE ACCOUNT-->
<script>
    
    function openEditCustomerAccountModal(userId) {
    // Fetch user details
    fetch(`editAccount/fetch_customer_details.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
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
                    <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
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
                        <div class="px-4 sm:px-6 py-4 sm:py-5">
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
                                        <input type="text" name="first_name" value="${data.user.first_name ? data.user.first_name[0].toUpperCase() + data.user.first_name.slice(1) : ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                                    </div>
                                    <p id="firstNameError" class="text-red-500 text-xs mt-1 hidden"></p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                                        Last Name <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="text" name="last_name" value="${data.user.last_name ? data.user.last_name[0].toUpperCase() + data.user.last_name.slice(1) : ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                                    </div>
                                    <p id="lastNameError" class="text-red-500 text-xs mt-1 hidden"></p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Middle Name</label>
                                    <div class="relative">
                                        <input type="text" name="middle_name" value="${data.user.middle_name ? data.user.middle_name[0].toUpperCase() + data.user.middle_name.slice(1) : ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                                        Email Address <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="email" name="email" value="${data.user.email || ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                                    </div>
                                    <p id="emailError" class="text-red-500 text-xs mt-1 hidden"></p>
                                </div>
                                
                                <div>
                                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                                        Phone Number <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input type="tel" name="phone_number" value="${data.user.phone_number || ''}" 
                                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                                    </div>
                                    <p id="phoneError" class="text-red-500 text-xs mt-1 hidden"></p>
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
                            <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeEditCustomerModal()">
                                Cancel
                            </button>
                            <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="saveCustomerChanges()">
                                Save Changes
                            </button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
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
    
    fetch('editAccount/update_customer_account.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Customer account updated successfully');
            closeEditCustomerModal();
            // Refresh the customer list
            fetchCustomerAccounts(currentPage, currentSearch, currentSort);
        } else {
            alert('Failed to update customer: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        
    });
}

//edit employee Accounts
function openEditEmployeeAccountModal(userId) {
    // Fetch employee details
    fetch(`editAccount/fetch_employee_details.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
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
                modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden';
                modal.innerHTML = `
                    <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                        <!-- Modal Header -->
                        <div class="flex justify-between items-center p-4 border-b border-sidebar-border">
                            <h3 class="text-lg font-semibold text-sidebar-text">Edit Employee Account</h3>
                            <button onclick="closeEditEmployeeModal()" class="text-gray-500 hover:text-gray-700">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        
                        <!-- Modal Body -->
                        <div class="p-6">
                            <form id="editEmployeeForm" class="space-y-4">
                                <input type="hidden" name="user_id" value="${data.user.id}">
                                
                                <div>
                                    <label class="block text-sm font-medium text-sidebar-text mb-1">Employee ID</label>
                                    <input type="text" value="#EMP-${String(data.user.id).padStart(3, '0')}" 
                                           class="w-full p-2 border border-input-border rounded bg-gray-100" readonly>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-sidebar-text mb-1">First Name</label>
                                    <input type="text" name="first_name" value="${data.user.first_name ? data.user.first_name[0].toUpperCase() + data.user.first_name.slice(1) : ''}" 
                                           class="w-full p-2 border border-input-border rounded focus:ring-gold focus:border-gold">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-sidebar-text mb-1">Last Name</label>
                                    <input type="text" name="last_name" value="${data.user.last_name ? data.user.last_name[0].toUpperCase() + data.user.last_name.slice(1) :  ''}" 
                                           class="w-full p-2 border border-input-border rounded focus:ring-gold focus:border-gold">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-sidebar-text mb-1">Middle Name</label>
                                    <input type="text" name="middle_name" value="${data.user.middle_name ? data.user.middle_name[0].toUpperCase() + data.user.middle_name.slice(1) : ''}" 
                                           class="w-full p-2 border border-input-border rounded focus:ring-gold focus:border-gold">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-sidebar-text mb-1">Email</label>
                                    <input type="email" name="email" value="${data.user.email || ''}" 
                                           class="w-full p-2 border border-input-border rounded focus:ring-gold focus:border-gold">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-sidebar-text mb-1">Phone Number</label>
                                    <input type="tel" name="phone_number" value="${data.user.phone_number || ''}" 
                                           class="w-full p-2 border border-input-border rounded focus:ring-gold focus:border-gold">
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-sidebar-text mb-1">Branch Location</label>
                                    <select name="branch_loc" class="w-full p-2 border border-input-border rounded focus:ring-gold focus:border-gold">
                                        <option value="">-- Select Branch --</option>
                                        ${branchOptions}
                                    </select>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Modal Footer -->
                        <div class="flex justify-end p-4 border-t border-sidebar-border space-x-3">
                            <button onclick="closeEditEmployeeModal()" 
                                    class="px-4 py-2 border border-input-border rounded text-sidebar-text hover:bg-sidebar-hover">
                                Cancel
                            </button>
                            <button onclick="saveEmployeeChanges()" 
                                    class="px-4 py-2 bg-gold text-white rounded hover:bg-darkgold">
                                Save Changes
                            </button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                modal.classList.remove('hidden');
                
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

function closeEditEmployeeModal() {
    const modal = document.getElementById('editEmployeeModal');
    if (modal) {
        modal.remove();
    }
}

function saveEmployeeChanges() {
    const form = document.getElementById('editEmployeeForm');
    const formData = new FormData(form);
    
    fetch('editAccount/update_employee_account.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Employee account updated successfully');
            closeEditEmployeeModal();
            // Refresh the employee list
            fetchEmployeeAccounts(currentPage, currentSearch, currentSort);
        } else {
            alert('Failed to update employee: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
       
    });
}
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
    const searchInput = document.getElementById('searchInput');
    const filterToggle = document.getElementById('filterToggle');
    const filterDropdown = document.getElementById('filterDropdown');
    const filterOptions = document.querySelectorAll('.filter-option');
    const employeeTableBody = document.getElementById('employeeTableBody');
    const paginationInfoElement = document.querySelector('.text-sm.text-gray-500');

    // Function to fetch employee accounts via AJAX
    function fetchEmployeeAccounts(search = '', sort = 'id_asc', page = 1) {
        // Create a new XMLHttpRequest
        const xhr = new XMLHttpRequest();
        
        // Prepare the URL with search, sort, and page parameters
        const url = `addEmployee/fetch_employee_accounts.php?search=${encodeURIComponent(search)}&sort=${encodeURIComponent(sort)}&page=${page}`;
        
        xhr.open('GET', url, true);
        
        xhr.onload = function() {
        if (xhr.status === 200) {
            // Parse the JSON response
            const response = JSON.parse(xhr.responseText);
            
            // Update table body
            employeeTableBody.innerHTML = response.tableContent;

            // Update total employees count
            document.getElementById('totalEmployees').textContent = response.totalCount;

                
                
                // Update pagination info
                paginationInfoElement.textContent = response.paginationInfo;
                
                // Update pagination buttons
                document.querySelectorAll('.pagination-button').forEach(btn => {
                    btn.classList.remove('bg-sidebar-accent', 'text-white');
                    if (parseInt(btn.textContent) === page) {
                        btn.classList.add('bg-sidebar-accent', 'text-white');
                    }
                });
            } else {
                console.error('Error fetching employee accounts:', xhr.statusText);
                employeeTableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center p-4 text-red-500">
                            Failed to load employees. Please try again.
                        </td>
                    </tr>
                `;
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

    // Function to change page
    window.changePage = function(page) {
        const searchTerm = searchInput.value;
        const urlParams = new URLSearchParams(window.location.search);
        const sortValue = urlParams.get('sort') || 'id_asc';
        
        fetchEmployeeAccounts(searchTerm, sortValue, page);
    };

    // Filter dropdown toggle
    filterToggle.addEventListener('click', function() {
        filterDropdown.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(event) {
        if (!filterToggle.contains(event.target) && !filterDropdown.contains(event.target)) {
            filterDropdown.classList.add('hidden');
        }
    });

    // Search functionality with debounce
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value;
        
        // Clear previous timeout
        clearTimeout(searchTimeout);
        
        // Set new timeout to reduce unnecessary API calls
        searchTimeout = setTimeout(() => {
            fetchEmployeeAccounts(searchTerm, 'id_asc', 1);
        }, 300); // 300ms delay
    });

    // Filter option selection
    filterOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            const sortValue = this.getAttribute('data-sort');
            
            // Close dropdown
            filterDropdown.classList.add('hidden');
            
            // Fetch employee accounts with selected sort
            fetchEmployeeAccounts(searchInput.value, sortValue, 1);
        });
    });
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
<div id="addEmployeeAccountModal" class="fixed inset-0 bg-black bg-opacity-60 z-50 hidden overflow-y-auto flex items-center justify-center p-4 w-full h-full">
  <div class="bg-white rounded-xl shadow-xl w-full max-w-lg mx-2">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0 rounded-t-xl">
      <h3 class="text-xl font-bold text-white"><i class="fas fa-user-plus"></i> Add Employee Account</h3>
      <button onclick="closeAddEmployeeAccountModal()" class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="p-6">
      <form id="addEmployeeAccountForm" method="post" action="addEmployee/add_employee.php">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label for="empFirstName" class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
            <input type="text" id="empFirstName" name="firstName" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" placeholder="First Name" required>
            <p id="empFirstNameError" class="text-red-500 text-xs mt-1 hidden">First name is required</p>
          </div>
          <div>
            <label for="empMiddleName" class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
            <input type="text" id="empMiddleName" name="middleName" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" placeholder="Middle Name">
          </div>
          <div>
            <label for="empLastName" class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
            <input type="text" id="empLastName" name="lastName" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" placeholder="Last Name" required>
            <p id="empLastNameError" class="text-red-500 text-xs mt-1 hidden">Last name is required</p>
          </div>
          <div>
            <label for="suffix" class="block text-sm font-medium text-gray-700 mb-1">Suffix <span class="text-xs text-gray-500">(Optional)</span></label>
            <select id="suffix" name="suffix" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              <option value="">Select Suffix <option>
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
        
        <!-- Birthdate Field -->
        <div class="mt-4">
          <label for="empBirthdate" class="block text-sm font-medium text-gray-700 mb-1">Birthdate <span class="text-red-500">*</span></label>
          <input type="date" id="empBirthdate" name="birthdate" 
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" 
                max="<?php echo date('Y-m-d'); ?>" 
                required>
          <p id="empBirthdateError" class="text-red-500 text-xs mt-1 hidden">Birthdate is required and cannot be in the future</p>
        </div>
        
        <div class="mt-4">
          <label for="employeeEmail" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
          <input type="email" id="employeeEmail" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" placeholder="Email" required>
        </div>
              
        <div class="mt-4">
          <label for="employeePhone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
          <input type="tel" id="employeePhone" name="phoneNumber" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" placeholder="Phone Number" required>
        </div>
        
        <div class="mt-4">
          <label for="empBranchLocation" class="block text-sm font-medium text-gray-700 mb-1">Branch Location <span class="text-red-500">*</span></label>
          <select id="empBranchLocation" name="branchLocation" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
            <option value="">Select Branch</option>
            <!-- Branch options will be populated by JavaScript -->
          </select>
          <p id="empBranchError" class="text-red-500 text-xs mt-1 hidden">Please select a branch</p>
        </div>
        
        <!-- Generated Password Display -->
        <div class="mt-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Generated Password</label>
          <div class="relative">
            <input type="password" id="empGeneratedPassword" name="password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent bg-gray-100" readonly autocomplete="new-password">
    <button type="button" class="absolute right-2 top-2 text-gray-500 hover:text-gray-700" onclick="toggleEmpPassword()">
      <svg id="empEyeIcon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 12s2.947-5.455 8.02-5.455S20.02 12 20.02 12s-2.947 5.455-8.02 5.455S3.98 12 3.98 12z" />
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
      </svg>
    </button>
  </div>
</div>
        
        <input type="hidden" name="user_type" value="2">
        <input type="hidden" name="is_verified" value="1">
      </form>
    </div>
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white rounded-b-xl">
      <button onclick="closeAddEmployeeAccountModal()" class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors">
        Cancel
      </button>
      <button onclick="submitEmployeeForm()" class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center">
        <i class="fas fa-user-plus mr-2"></i> Create Account
      </button>
    </div>
  </div>
</div>

<!-- Employee OTP Verification Modal -->
<div id="empOtpVerificationModal" class="fixed inset-0 bg-black bg-opacity-60 z-50 hidden overflow-y-auto flex items-center justify-center p-4 w-full h-full">
  <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-2">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0 rounded-t-xl">
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
      <button onclick="verifyEmpOTP()" class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center">
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

// Real-time validation functions for Employee Form
function validateEmpFirstName() {
  const firstNameInput = document.getElementById('empFirstName');
  const firstNameError = document.getElementById('empFirstNameError');
  const firstName = firstNameInput.value.trim();
  const nameRegex = /^[A-Za-z\s]+$/;
  if (firstName === '') {
    firstNameError.textContent = 'First name is required';
    firstNameError.classList.remove('hidden');
    return false;
  } else if (!nameRegex.test(firstName)) {
    firstNameError.textContent = 'First name must contain only letters';
    firstNameError.classList.remove('hidden');
    return false;
  } else {
    firstNameError.classList.add('hidden');
    return true;
  }
}

function validateEmpMiddleName() {
  const middleNameInput = document.getElementById('empMiddleName');
  const middleNameError = document.getElementById('empMiddleNameError');
  
  // If you want to add a middleNameError element, uncomment and add to HTML
  if (!middleNameError) return true;
  const middleName = middleNameInput.value.trim();
  const nameRegex = /^[A-Za-z\s]*$/;
  if (middleName !== '' && !nameRegex.test(middleName)) {
    middleNameError.textContent = 'Middle name must contain only letters';
    middleNameError.classList.remove('hidden');
    return false;
  } else {
    middleNameError.classList.add('hidden');
    return true;
  }
}

function validateEmpLastName() {
  const lastNameInput = document.getElementById('empLastName');
  const lastNameError = document.getElementById('empLastNameError');
  const lastName = lastNameInput.value.trim();
  const nameRegex = /^[A-Za-z\s]+$/;
  if (lastName === '') {
    lastNameError.textContent = 'Last name is required';
    lastNameError.classList.remove('hidden');
    return false;
  } else if (!nameRegex.test(lastName)) {
    lastNameError.textContent = 'Last name must contain only letters';
    lastNameError.classList.remove('hidden');
    return false;
  } else {
    lastNameError.classList.add('hidden');
    return true;
  }
}

function validateEmpBirthdate() {
  const birthdateInput = document.getElementById('empBirthdate');
  const birthdateError = document.getElementById('empBirthdateError');
  const birthdate = birthdateInput.value;
  if (birthdate === '') {
    birthdateError.textContent = 'Birthdate is required';
    birthdateError.classList.remove('hidden');
    return false;
  } 
  const today = new Date();
  const birthdateObj = new Date(birthdate);
  let age = today.getFullYear() - birthdateObj.getFullYear();
  const monthDiff = today.getMonth() - birthdateObj.getMonth();
  
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthdateObj.getDate())) {
    age--;
  }
  
  if (age < 18) {
    birthdateError.textContent = 'You must be at least 18 years old';
    birthdateError.classList.remove('hidden');
    return false;
  } else {
    birthdateError.classList.add('hidden');
    return true;
  }
}

function validateEmpEmail() {
  const emailInput = document.getElementById('employeeEmail');
  const emailError = document.getElementById('empEmailError');
  
  // Add this error element to your HTML if not present
  if (!emailError) {
    const errorEl = document.createElement('p');
    errorEl.id = 'empEmailError';
    errorEl.className = 'text-red-500 text-xs mt-1 hidden';
    emailInput.parentNode.appendChild(errorEl);
  }
  const email = emailInput.value.trim();
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (email === '') {
    emailError.textContent = 'Email is required';
    emailError.classList.remove('hidden');
    return false;
  } else if (!emailPattern.test(email)) {
    emailError.textContent = 'Please enter a valid email address';
    emailError.classList.remove('hidden');
    return false;
  } else {
    emailError.classList.add('hidden');
    return true;
  }
}

function validateEmpPhoneNumber() {
  const phoneInput = document.getElementById('employeePhone');
  const phoneError = document.getElementById('empPhoneError');
  
  // Add this error element to your HTML if not present
  if (!phoneError) {
    const errorEl = document.createElement('p');
    errorEl.id = 'empPhoneError';
    errorEl.className = 'text-red-500 text-xs mt-1 hidden';
    phoneInput.parentNode.appendChild(errorEl);
  }
  const phone = phoneInput.value.trim();
  const phonePattern = /^09\d{9}$/;
  // Remove any non-digit characters
  const cleanedPhone = phone.replace(/[^0-9]/g, '');
  if (phone === '') {
    phoneError.textContent = 'Phone number is required';
    phoneError.classList.remove('hidden');
    return false;
  } else if (!phonePattern.test(cleanedPhone)) {
    phoneError.textContent = 'Please enter a valid 11-digit mobile number (e.g., 09123456789)';
    phoneError.classList.remove('hidden');
    return false;
  } else {
    phoneError.classList.add('hidden');
    return true;
  }
}

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
            timer: 5000,
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


// Modify your existing submitEmployeeForm function to use OTP verification
function submitEmployeeForm() {
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