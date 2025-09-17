<?php
session_start();
date_default_timezone_set('Asia/Manila'); // Or your appropriate timezone
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check for admin user type (user_type = 1)
if ($_SESSION['user_type'] != 2) {
    // Redirect to appropriate page based on user type
    switch ($_SESSION['user_type']) {
        case 1:
            header("Location: ../admin/admin_index.php");
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

require_once '../db_connect.php';
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Customer Account Management</title>
  <?php include 'faviconLogo.php'; ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    /* Gradient background for menu section headers */
    .menu-header {
      background: linear-gradient(to right, rgba(202, 138, 4, 0.1), transparent);
    }
    
    /* Responsive main content container */
    .main-content {
      margin-left: 16rem;
      width: calc(100% - 16rem);
      z-index: 1;
      transition: margin-left 0.3s ease, width 0.3s ease;
    }

    .sidebar {
      z-index: 10;
    }
    
    /* Sidebar responsive transitions */
    #sidebar {
      transition: width 0.3s ease, opacity 0.3s ease, visibility 0.3s ease;
    }

    #main-content {
      transition: margin-left 0.3s ease, width 0.3s ease;
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
    
    /* Mobile responsive styles */
    @media (max-width: 768px) {
      .main-content {
        margin-left: 0;
        width: 100%;
      }
      
      #main-content {
        margin-left: 0 !important;
        width: 100% !important;
      }
      
      .w-\[calc\(100\%-16rem\)\] {
        width: 100% !important;
      }
      
      /* Mobile-friendly touch targets */
      .mobile-touch-target {
        min-height: 44px;
        min-width: 44px;
      }
      
      /* Mobile table scrolling */
      .mobile-table-container {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
      }
      
      /* Mobile modal adjustments */
      .mobile-modal {
        margin: 1rem;
        max-height: calc(100vh - 2rem);
        overflow-y: auto;
      }
    }
    
    /* Tablet responsive styles */
    @media (min-width: 769px) and (max-width: 1024px) {
      .main-content {
        margin-left: 16rem;
        width: calc(100% - 16rem);
      }
    }

/* Add to your existing styles */
#archivedAccountsModal table {
    width: 100%;
    border-collapse: collapse;
  }
  
  #archivedAccountsModal th, 
  #archivedAccountsModal td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
  }
  
  #archivedAccountsModal tr:hover {
    background-color: #f7fafc;
  }
  
  #archivedAccountsModal .max-h-\[60vh\] {
    max-height: 60vh;
  }
  
  #searchContainer {
    position: relative;
    width: 100%;
  }
  
  @media (min-width: 640px) {
    #searchContainer {
      width: 300px;
    }
  }
  
  #searchCustomer {
    padding-right: 30px;
    width: 100%;
  }
  
  #clearSearchBtn {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    cursor: pointer;
    padding: 5px;
    z-index: 10;
  }

  /* Loading indicator for search */
  .search-loading::after {
    content: "";
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 16px;
    height: 16px;
    border: 2px solid rgba(202, 138, 4, 0.2);
    border-top: 2px solid #CA8A04;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }

  @keyframes spin {
    0% { transform: translateY(-50%) rotate(0deg); }
    100% { transform: translateY(-50%) rotate(360deg); }
  }

  .availability-indicator {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.75rem;
    display: flex;
    align-items: center;
  }
  .availability-indicator i {
    margin-right: 4px;
  }
  
  </style>
</head>
<body class="flex bg-gray-50">
  <!-- Modify the sidebar structure to include a dedicated space for the hamburger menu -->
<?php include 'employee_sidebar.php'; ?>

  <!-- Main Content -->
<div id="main-content" class="ml-64 w-[calc(100%-16rem)] p-4 sm:p-6 bg-gray-50 min-h-screen transition-all duration-300 main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 sm:mb-6 bg-white p-4 sm:p-5 rounded-lg shadow-sidebar gap-3 sm:gap-0">
    <div class="flex-1">
      <h1 class="text-xl sm:text-2xl font-bold text-sidebar-text">Customer Account Management</h1>
    </div>
  </div>

  <!-- Mode Selector -->
  <div class="flex justify-start mb-4 sm:mb-6">
      <div class="bg-gray-100 rounded-lg overflow-hidden inline-flex w-full sm:w-auto">
        <!-- Manage Accounts button first -->
        <button id="manageBtn" onclick="switchMode('manage')" class="flex-1 sm:flex-none py-2 sm:py-2 px-3 sm:px-5 border-none bg-sidebar-accent text-white font-semibold cursor-pointer hover:bg-darkgold transition-all duration-300 text-sm sm:text-base mobile-touch-target">Manage Accounts</button>
        <!-- Create Account button second -->
        <button id="createBtn" onclick="switchMode('create')" class="flex-1 sm:flex-none py-2 sm:py-2 px-3 sm:px-5 border-none bg-transparent text-sidebar-text cursor-pointer hover:bg-sidebar-hover transition-all duration-300 text-sm sm:text-base mobile-touch-target">Create Account</button>
      </div>
  </div>

<!-- Add Customer Account Form (Non-Modal Version) -->
<div id="createAccountSection" class="hidden">
<div class="bg-white rounded-xl shadow-card w-full mx-auto">
  <!-- Form Header -->
  <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200 rounded-t-xl">
    <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
      Add Customer Account
    </h3>
  </div>
  
  <!-- Form Body -->
  <div class="px-4 sm:px-6 py-4 sm:py-5">
    <form id="addCustomerAccountForm" method="post" action="../admin/addCustomer/add_customer.php" class="space-y-3 sm:space-y-4">
      <!-- Personal Information Section -->
      <div>
        <label for="firstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
          First Name <span class="text-red-500">*</span>
        </label>
        <div class="relative">
          <input type="text" id="firstName" name="firstName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="First Name" required
                 oninput="validateName(this, 'firstNameError')">
        </div>
        <p id="firstNameError" class="text-red-500 text-xs mt-1 hidden">First name must be at least 2 letters</p>
      </div>
      
      <div>
        <label for="lastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
          Last Name <span class="text-red-500">*</span>
        </label>
        <div class="relative">
          <input type="text" id="lastName" name="lastName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Last Name" required
                 oninput="validateName(this, 'lastNameError')">
        </div>
        <p id="lastNameError" class="text-red-500 text-xs mt-1 hidden">Last name must be at least 2 letters</p>
      </div>
      
      <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
        <div class="w-full sm:flex-1">
          <label for="middleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Middle Name
          </label>
          <div class="relative">
            <input type="text" id="middleName" name="middleName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Middle Name"
                   oninput="validateName(this, 'middleNameError')">
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
            max="<?php echo date('Y-m-d'); ?>" 
            min="<?php echo date('Y-m-d', strtotime('-100 years')); ?>"
            onchange="validateBirthdate(this)" required>
        </div>
        <p id="birthdateError" class="text-red-500 text-xs mt-1 hidden">Please enter a valid birthdate (must be between 100 years ago and today)</p>
      </div>
      
      <div>
        <label for="branchLocation" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
          Branch Location <span class="text-red-500">*</span>
        </label>
        <div class="relative">
          <select id="branchLocation" name="branchLocation" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required
        onchange="validateBranchLocation(this)">
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
            <input type="email" id="customerEmail" name="customerEmail" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="example@email.com" required
                   oninput="let value = this.value;
                            // Remove any spaces immediately
                            let cleanedValue = value.replace(/\s/g, '');
                            this.value = cleanedValue;
                            validateEmail(this); 
                            checkEmailAvailability(this.value);"
                   onkeydown="if (event.key === ' ') event.preventDefault();"
                   onpaste="const text = event.clipboardData.getData('text/plain').replace(/\s/g, '');
                            event.preventDefault();
                            this.value = text;
                            validateEmail(this); 
                            checkEmailAvailability(this.value);">
            <span id="emailAvailability" class="availability-indicator hidden">
                <i class="fas fa-check-circle text-green-500"></i> Available
            </span>
            <span id="emailUnavailable" class="availability-indicator hidden">
                <i class="fas fa-times-circle text-red-500"></i> In use
            </span>
        </div>
        <p id="emailError" class="text-red-500 text-xs mt-1 hidden">Please enter a valid email address</p>
    </div>
      
      <div>
        <label for="customerPhone" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Phone Number <span class="text-red-500">*</span>
        </label>
        <div class="relative">
            <input type="tel" id="customerPhone" name="customerPhone" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Phone Number" required
                   oninput="validatePhoneNumber(this); checkPhoneAvailability(this.value)" maxlength="11">
            <span id="phoneAvailability" class="availability-indicator hidden">
                <i class="fas fa-check-circle text-green-500"></i> Available
            </span>
            <span id="phoneUnavailable" class="availability-indicator hidden">
                <i class="fas fa-times-circle text-red-500"></i> In use
            </span>
        </div>
        <p id="phoneError" class="text-red-500 text-xs mt-1 hidden">Please enter a valid Philippine phone number (09XXXXXXXXX)</p>
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
  
  <!-- Form Footer -->
  <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 bg-white rounded-b-xl">
    <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="confirmSubmitCustomerForm()">
      Create Account
    </button>
  </div>
</div>
</div>

<script>
// Name validation function
function validateName(input, errorElementId) {
  const errorElement = document.getElementById(errorElementId);
  let value = input.value;
  
  // Clean pasted text: remove numbers and symbols, keep only letters and single spaces
  let cleanedValue = value.replace(/[^a-zA-Z\s]/g, '');
  
  // Remove multiple consecutive spaces
  cleanedValue = cleanedValue.replace(/\s+/g, ' ');
  
  // Capitalize first letter of each word
  cleanedValue = cleanedValue.toLowerCase().replace(/(?:^|\s)\S/g, function(a) {
    return a.toUpperCase();
  });
  
  // If this is a required field (first or last name)
  const isRequired = errorElementId === 'firstNameError' || errorElementId === 'lastNameError';
  
  // For required fields, don't allow space unless there are at least 2 characters
  if (isRequired && cleanedValue.length === 1 && cleanedValue === ' ') {
    cleanedValue = '';
  }
  
  // Update the input value
  input.value = cleanedValue;
  
  // Validate required fields
  if (isRequired) {
    if (cleanedValue.trim().length < 2) {
      errorElement.classList.remove('hidden');
      return false;
    } else {
      errorElement.classList.add('hidden');
      return true;
    }
  }
  
  // For optional fields (middle name)
  errorElement.classList.add('hidden');
  return true;
}

// Email validation


// Phone number validation
function validatePhoneNumber(input) {
  const errorElement = document.getElementById('phoneError');
  let phone = input.value.trim();
  
  // Remove any non-digit characters
  phone = phone.replace(/\D/g, '');
  
  // Update the input value (only digits)
  input.value = phone;
  
  // Validate Philippine phone number (09XXXXXXXXX)
  if (phone.length > 0 && (phone.length !== 11 || !phone.startsWith('09'))) {
    errorElement.classList.remove('hidden');
    return false;
  } else {
    errorElement.classList.add('hidden');
    return true;
  }
}

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
</script>



  <!-- Manage Customer Accounts Section -->
<div id="manageAccountSection">
    <div class="bg-white rounded-lg shadow-sidebar p-4 sm:p-5 mb-4 sm:mb-6 border border-sidebar-border hover:shadow-card transition-all duration-300">
        <div class="mb-4 sm:mb-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
            <h3 class="text-lg font-semibold text-sidebar-text flex-shrink-0">Customer Accounts</h3>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3 w-full sm:w-auto">
                <div id="searchContainer" class="relative sm:w-64">
                    <input type="text" id="searchCustomer" placeholder="Search customers..." 
                           class="mobile-touch-target p-2 border border-sidebar-border rounded-md text-sm text-sidebar-text focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent w-full"
                           oninput="let value = this.value;
                                    // Remove multiple consecutive spaces
                                    let cleanedValue = value.replace(/\s+/g, ' ');
                                    // Don't allow space unless there are at least 2 characters
                                    if (cleanedValue.length === 1 && cleanedValue === ' ') {
                                        cleanedValue = '';
                                    }
                                    // Don't allow space at the beginning
                                    if (cleanedValue.startsWith(' ')) {
                                        cleanedValue = cleanedValue.substring(1);
                                    }
                                    this.value = cleanedValue;
                                    searchCustomers();"
                           onkeydown="if (event.key === ' ' && this.value.length < 2) event.preventDefault();"
                           onpaste="const text = event.clipboardData.getData('text/plain').replace(/\s+/g, ' ');
                                    event.preventDefault();
                                    let cleanedText = text;
                                    if (cleanedText.startsWith(' ')) {
                                        cleanedText = cleanedText.substring(1);
                                    }
                                    if (this.value.length < 2 && cleanedText.startsWith(' ')) {
                                        cleanedText = cleanedText.replace(/^\s+/, '');
                                    }
                                    this.value = cleanedText;
                                    searchCustomers();">
                    <button id="clearSearchBtn" onclick="clearSearch()" class="absolute right-2 top-2 text-gray-400 hover:text-gray-600 hidden">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <button id="viewArchivedBtn" onclick="viewArchivedAccounts()" class="mobile-touch-target bg-yellow-600 text-white border-none py-2 px-3 sm:px-4 rounded-md cursor-pointer hover:bg-darkgold transition-all duration-300 text-sm sm:text-base whitespace-nowrap flex-shrink-0">
                    <i class="fas fa-archive mr-1 sm:mr-2"></i><span class="hidden sm:inline">Archived</span><span class="sm:hidden">Archive</span>
                </button>
            </div>
        </div>
        <div class="p-3 sm:p-5">
            <div class="mobile-table-container overflow-x-auto scrollbar-thin">
                <table id="customerTable" class="w-full border-collapse min-w-[700px]">
                    <thead>
                        <tr class="bg-sidebar-hover text-left">
                            <th class="p-2 sm:p-3 border-b border-sidebar-border text-xs sm:text-sm font-medium text-sidebar-text">
                                <i class="fas fa-id-card mr-1 sm:mr-2 text-sidebar-accent"></i>
                                <span class="hidden sm:inline">Customer ID</span><span class="sm:hidden">ID</span>
                            </th>
                            <th class="p-2 sm:p-3 border-b border-sidebar-border text-xs sm:text-sm font-medium text-sidebar-text">
                                <i class="fas fa-user mr-1 sm:mr-2 text-sidebar-accent"></i>
                                Name
                            </th>
                            <th class="p-2 sm:p-3 border-b border-sidebar-border text-xs sm:text-sm font-medium text-sidebar-text hidden sm:table-cell">
                                <i class="fas fa-envelope mr-1 sm:mr-2 text-sidebar-accent"></i>
                                Email
                            </th>
                            <th class="p-2 sm:p-3 border-b border-sidebar-border text-xs sm:text-sm font-medium text-sidebar-text hidden md:table-cell">
                                <i class="fas fa-tag mr-1 sm:mr-2 text-sidebar-accent"></i>
                                Type
                            </th>
                            <th class="p-2 sm:p-3 border-b border-sidebar-border text-xs sm:text-sm font-medium text-sidebar-text">
                                <i class="fas fa-circle mr-1 sm:mr-2 text-sidebar-accent"></i>
                                Status
                            </th>
                            <th class="p-2 sm:p-3 border-b border-sidebar-border text-xs sm:text-sm font-medium text-sidebar-text">
                                <i class="fas fa-cogs mr-1 sm:mr-2 text-sidebar-accent"></i>
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Table content will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div class="mt-3 sm:mt-5 bottom-0 left-0 right-0 px-3 sm:px-4 py-3 sm:py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-3 sm:gap-4">
                <div id="paginationInfo" class="text-xs sm:text-sm text-gray-500 text-center sm:text-left order-2 sm:order-1">
                    Loading customer accounts...
                </div>
                <div id="paginationContainer" class="flex flex-wrap justify-center sm:justify-end gap-1 sm:gap-2 order-1 sm:order-2">
                    <!-- JavaScript will populate this -->
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Customer Details Modal -->
    <div id="customerModal" class="hidden fixed z-50 inset-0 overflow-auto bg-black bg-opacity-40 p-4">
      <div class="bg-white mx-auto my-4 sm:my-[10%] p-4 sm:p-5 border border-gray-300 w-full sm:w-4/5 max-w-3xl rounded-lg shadow-lg mobile-modal">
        <div class="flex justify-between items-center mb-4 sm:mb-5 border-b border-gray-300 pb-3">
          <h3 id="modalTitle" class="m-0 text-base sm:text-lg font-semibold">Customer Details</h3>
          <span onclick="closeModal()" class="cursor-pointer text-xl sm:text-2xl mobile-touch-target">&times;</span>
        </div>
        <div id="modalContent" class="max-h-[60vh] overflow-y-auto">
          <!-- Content will be dynamically populated -->
        </div>
        <div class="mt-4 sm:mt-5 flex flex-col sm:flex-row justify-end gap-2 sm:gap-3 border-t border-gray-300 pt-4">
          <button onclick="closeModal()" class="mobile-touch-target bg-gray-600 text-white border-none py-2 px-4 rounded-md cursor-pointer text-sm sm:text-base">Close</button>
          <button id="modalActionButton" class="mobile-touch-target bg-blue-600 text-white border-none py-2 px-4 rounded-md cursor-pointer text-sm sm:text-base">Save Changes</button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed z-50 inset-0 overflow-auto bg-black bg-opacity-40 p-4">
      <div class="bg-white mx-auto my-8 sm:my-[15%] p-4 sm:p-5 border border-gray-300 w-full max-w-sm sm:w-[400px] rounded-lg shadow-lg mobile-modal">
        <div class="text-center mb-4 sm:mb-5">
          <i class="fas fa-exclamation-triangle text-4xl sm:text-5xl text-red-600"></i>
          <h3 class="mt-3 sm:mt-4 text-base sm:text-lg font-semibold">Confirm Deletion</h3>
          <p class="text-sm sm:text-base text-gray-600 mt-2">Are you sure you want to delete this customer account? This action cannot be undone.</p>
        </div>
        <div class="flex flex-col sm:flex-row justify-center gap-2 sm:gap-3">
          <button onclick="closeDeleteModal()" class="mobile-touch-target bg-gray-600 text-white border-none py-2 px-4 rounded-md cursor-pointer text-sm sm:text-base">Cancel</button>
          <button onclick="deleteCustomer()" class="mobile-touch-target bg-red-600 text-white border-none py-2 px-4 rounded-md cursor-pointer text-sm sm:text-base">Delete</button>
        </div>
      </div>
    </div>
  </div>
  
<!-- OTP Verification Modal -->
<div id="otpVerificationModal" class="fixed inset-0 bg-black bg-opacity-60 z-[9999] hidden overflow-y-auto flex items-center justify-center p-4 overscroll-contain [will-change:transform]">
  <div class="bg-white relative z-[10000] rounded-xl shadow-xl w-full max-w-md mx-2 mobile-modal">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-4 sm:p-6 flex-shrink-0 rounded-t-xl">
      <h3 class="text-lg sm:text-xl font-bold text-white"><i class="fas fa-shield-alt mr-2"></i> Email Verification</h3>
      <button onclick="closeOtpModal()" class="mobile-touch-target bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="p-4 sm:p-6">
      <p class="text-sm sm:text-base text-gray-700 mb-4">A verification code has been sent to <span id="otpEmail" class="font-medium"></span>. Please enter the code below.</p>
      <div class="flex justify-center gap-1 sm:gap-2 mb-4">
        <input type="text" class="otp-input w-10 h-10 sm:w-12 sm:h-12 border border-gray-300 rounded-md text-center text-lg sm:text-xl font-bold mobile-touch-target" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-10 h-10 sm:w-12 sm:h-12 border border-gray-300 rounded-md text-center text-lg sm:text-xl font-bold mobile-touch-target" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-10 h-10 sm:w-12 sm:h-12 border border-gray-300 rounded-md text-center text-lg sm:text-xl font-bold mobile-touch-target" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-10 h-10 sm:w-12 sm:h-12 border border-gray-300 rounded-md text-center text-lg sm:text-xl font-bold mobile-touch-target" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-10 h-10 sm:w-12 sm:h-12 border border-gray-300 rounded-md text-center text-lg sm:text-xl font-bold mobile-touch-target" maxlength="1" autocomplete="off">
        <input type="text" class="otp-input w-10 h-10 sm:w-12 sm:h-12 border border-gray-300 rounded-md text-center text-lg sm:text-xl font-bold mobile-touch-target" maxlength="1" autocomplete="off">
      </div>
      <div id="otpError" class="text-red-500 text-center text-xs sm:text-sm mb-4 hidden"></div>
      <p class="text-xs sm:text-sm text-gray-500 text-center">Didn't receive the code? <button type="button" onclick="resendOTP()" class="text-sidebar-accent hover:underline mobile-touch-target">Resend</button></p>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-4 sm:p-6 flex flex-col sm:flex-row justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white rounded-b-xl">
      <button onclick="closeOtpModal()" class="mobile-touch-target px-4 sm:px-5 py-2 sm:py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors text-sm sm:text-base">
        Cancel
      </button>
      <button id="verifyOtpBtn" onclick="verifyOTP()" class="mobile-touch-target px-5 sm:px-6 py-2 sm:py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center justify-center text-sm sm:text-base">
        <i class="fas fa-check-circle mr-2"></i> Verify
      </button>
    </div>
  </div>
</div>
  
  
  <!-- CUSTOMER ACCOUNT CREATION VALIDATION -->
  
  <script>

document.getElementById("customerPhone").addEventListener("input", function (e) {
    this.value = this.value.replace(/[^0-9]/g, ""); // Remove non-numeric characters
  });

// Real-time validation functions
// Updated name validation functions
function validateFirstName() {
    const firstNameInput = document.getElementById('firstName');
    const firstNameError = document.getElementById('firstNameError');
    const firstName = firstNameInput.value.trim();
    const nameRegex = /^[A-Za-z]+$/;

    if (firstName === '') {
        firstNameError.textContent = 'First name is required';
        firstNameError.classList.remove('hidden');
        return false;
    } else if (!nameRegex.test(firstName)) {
        firstNameError.textContent = 'First name must contain only letters (A-Z, a-z)';
        firstNameError.classList.remove('hidden');
        return false;
    } else if (firstName.length === 1) {
        firstNameError.textContent = 'First name must not contain single characters only';
        firstNameError.classList.remove('hidden');
        return false;
    } else {
        firstNameError.classList.add('hidden');
        return true;
    }
}

function validateLastName() {
    const lastNameInput = document.getElementById('lastName');
    const lastNameError = document.getElementById('lastNameError');
    const lastName = lastNameInput.value.trim();
    const nameRegex = /^[A-Za-z]+$/;

    if (lastName === '') {
        lastNameError.textContent = 'Last name is required';
        lastNameError.classList.remove('hidden');
        return false;
    } else if (!nameRegex.test(lastName)) {
        lastNameError.textContent = 'Last name must contain only letters (A-Z, a-z)';
        lastNameError.classList.remove('hidden');
        return false;
    } else if (lastName.length === 1) {
        lastNameError.textContent = 'Last name must be at least 2 letters';
        lastNameError.classList.remove('hidden');
        return false;
    } else {
        lastNameError.classList.add('hidden');
        return true;
    }
}

function validateMiddleName() {
    const middleNameInput = document.getElementById('middleName');
    const middleNameError = document.getElementById('middleNameError');
    const middleName = middleNameInput.value.trim();
    const nameRegex = /^[A-Za-z]*$/;

    if (middleName !== '') {
        if (!nameRegex.test(middleName)) {
            middleNameError.textContent = 'Middle name must contain only letters (A-Z, a-z)';
            middleNameError.classList.remove('hidden');
            return false;
        } else if (middleName.length === 1) {
            middleNameError.textContent = 'Middle name must be at least 2 letters or empty';
            middleNameError.classList.remove('hidden');
            return false;
        }
    }
    
    middleNameError.classList.add('hidden');
    return true;
}

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

function validateEmail(input) {
    const emailError = document.getElementById('emailError');
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    
    if (!emailPattern.test(input.value)) {
        input.classList.add('border-red-500');
        emailError.classList.remove('hidden');
        return false;
    } else {
        input.classList.remove('border-red-500');
        emailError.classList.add('hidden');
        return true;
    }
}

function validatePhoneNumber(input) {
    const phoneError = document.getElementById('phoneError');
    const phone = input.value.trim();
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

function validateBranchLocation(select) {
    const branchError = document.getElementById('branchError');

    if (select.value === '') {
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
    xhr.open('POST', '../admin/addCustomer/send_otp.php', true);
    
    xhr.onload = function() {
        Swal.close(); // Close the loading dialog
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    // Show OTP modal
                    const modal = document.getElementById('otpVerificationModal');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    
                    // Make sure the verify button is set for creation flow
                    const verifyBtn = document.getElementById('verifyOtpBtn');
                    verifyBtn.setAttribute('onclick', 'verifyOTP()');
                    
                    // Focus on first OTP input
                    const otpInputs = document.querySelectorAll('.otp-input');
                    if (otpInputs.length > 0) {
                        otpInputs[0].focus();
                    }
                } else {
                    Swal.fire({
                        title: 'Error Occurred',
                        text: response.message || 'Failed to send OTP',
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#d33'
                    });
                }
            } catch (e) {
                Swal.fire({
                    title: 'Error',
                    text: 'Invalid server response',
                    icon: 'error'
                });
            }
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Failed to connect to server',
                icon: 'error'
            });
        }
    };
    
    xhr.onerror = function() {
        Swal.close();
        Swal.fire({
            title: 'Connection Error',
            text: 'Failed to connect to server',
            icon: 'error'
        });
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
    xhr.open('POST', '../admin/addCustomer/send_otp.php', true);
    
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
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 4000,
                        timerProgressBar: true,
                        background: '#f8f9fa',
                        iconColor: '#dc3545',
                        width: '400px',
                        padding: '1em',
                        customClass: {
                            container: 'custom-swal-container',
                            popup: 'custom-swal-popup-error'
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
    
    // Show loading state
    Swal.fire({
        title: 'Verifying...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Verify OTP
    const formData = new FormData();
    formData.append('otp', otpValue);
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../admin/addCustomer/verify_otp.php', true);
    
    xhr.onload = function() {
        Swal.close();
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                if (response.success) {
                    // OTP verified, proceed with form submission
                    actuallySubmitForm();
                } else {
                    document.getElementById('otpError').textContent = response.message;
                    document.getElementById('otpError').classList.remove('hidden');
                }
            } catch (e) {
                Swal.fire({
                    title: 'Error',
                    text: 'Invalid server response',
                    icon: 'error'
                });
            }
        } else {
            Swal.fire({
                title: 'Error',
                text: 'Failed to connect to server',
                icon: 'error'
            });
        }
    };
    
    xhr.onerror = function() {
        Swal.close();
        Swal.fire({
            title: 'Connection Error',
            text: 'Failed to connect to server',
            icon: 'error'
        });
    };
    
    xhr.send(formData);
}

// Handle OTP input functionality
document.addEventListener('DOMContentLoaded', function() {
    generatePassword();
  
    // Regenerate password when these fields change
    document.getElementById('firstName').addEventListener('input', generatePassword);
    document.getElementById('lastName').addEventListener('input', generatePassword);
    document.getElementById('birthdate').addEventListener('change', generatePassword);
    
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
    if (!validatePhoneNumber(phoneInput)) {
        return Promise.reject('Phone number is invalid');
    }
    
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../admin/addCustomer/check_phone.php', true);
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

function generatePassword() {
    const firstName = document.getElementById('firstName').value.trim();
    const lastName = document.getElementById('lastName').value.trim();
    const birthdate = document.getElementById('birthdate').value;
    
    if (firstName !== '' && lastName !== '' && birthdate !== '') {
        // Format: First letter of first name (uppercase) + First letter of last name (lowercase) + birthdate (YYYYMMDD)
        const password = firstName.charAt(0).toUpperCase() + 
                         lastName.charAt(0).toLowerCase() + 
                         birthdate.replace(/-/g, '');
        document.getElementById('generatedPassword').value = password;
    } else {
        // If fields are empty, generate a random password as fallback
        const length = 12;
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        let password = "";
        
        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * charset.length);
            password += charset[randomIndex];
        }
        
        document.getElementById('generatedPassword').value = password;
    }
}

// Confirmation before submitting form
function confirmSubmitCustomerForm() {
    const isValid = validateFirstName() &&
                    validateMiddleName() &&
                    validateLastName() &&
                    validateBirthdate() &&
                    validateEmail(document.getElementById('customerEmail')) &&
                    validatePhoneNumber(document.getElementById('customerPhone')) &&
                    validateBranchLocation(document.getElementById('branchLocation'));

    if (isValid) {
        // Check if email or phone is unavailable
        if (!document.getElementById('emailUnavailable').classList.contains('hidden') ||
            !document.getElementById('phoneUnavailable').classList.contains('hidden')) {
            Swal.fire({
                title: 'Validation Error',
                text: 'Please use a different email or phone number as the current ones are already in use.',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
            return;
        }

        // Check if availability checks are still in progress
        if (document.getElementById('emailAvailability').innerHTML.includes('fa-spinner') ||
            document.getElementById('phoneAvailability').innerHTML.includes('fa-spinner')) {
            Swal.fire({
                title: 'Please Wait',
                text: 'Availability checks are still in progress',
                icon: 'warning',
                confirmButtonColor: '#d33'
            });
            return;
        }

        Swal.fire({
            title: 'Confirm Account Creation',
            html: `
                <div style="text-align: left;">
                    <p>Are you sure you want to create this customer account?</p>
                    <div style="margin-top: 15px; background: #f8f9fa; padding: 10px; border-radius: 5px; border-left: 4px solid #CA8A04;">
                        <p><strong>Name:</strong> ${document.getElementById('firstName').value} ${document.getElementById('lastName').value}</p>
                        <p><strong>Email:</strong> ${document.getElementById('customerEmail').value}</p>
                        <p><strong>Phone:</strong> ${document.getElementById('customerPhone').value}</p>
                    </div>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#CA8A04',
            confirmButtonText: 'Yes, create account',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                if (document.getElementById('generatedPassword').value === '') {
                    generatePassword();
                }
                
                // Show loading state before sending OTP
                Swal.fire({
                    title: 'Sending OTP...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
                
                // Call showOTPModal after confirmation
                showOTPModal();
            }
        });
    }
}

// Add this new function for actual submission after OTP verification
function actuallySubmitForm() {
    const form = document.getElementById('addCustomerAccountForm');
    const formData = new FormData(form);
    
    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../admin/addCustomer/add_customer.php', true);
    
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
                        // Reload the page after account creation
                        window.location.reload();
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

// Function to load branches
function loadBranches() {
    const branchSelect = document.getElementById('branchLocation');
    
    fetch('../admin/addCustomer/get_branches.php')
        .then(response => response.json())
        .then(data => {
            // Clear existing options except the first one
            branchSelect.innerHTML = '<option value="">Select Branch</option>';
            
            // Add new options
            data.forEach(branch => {
                const option = document.createElement('option');
                option.value = branch.branch_id;
                option.textContent = branch.branch_name;
                branchSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading branches:', error);
        });
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
    document.getElementById('customerEmail').addEventListener('input', function() {
        validateEmail(this);
        checkEmailAvailability(this.value);
    });
    
    // Phone Number validation
    document.getElementById('customerPhone').addEventListener('input', function() {
        validatePhoneNumber(this);
        checkPhoneAvailability(this.value);
    });
    
    // Branch Location validation
    document.getElementById('branchLocation').addEventListener('change', function() {
        validateBranchLocation(this);
    });
    
    loadBranches();

    // Close modal on 'Escape' key press
    window.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeAddCustomerAccountModal();
        }
    });
});
</script>
  
  <script>
      // Function to fetch and display customer accounts
      function fetchCustomerAccounts(page = 1, search = '', sort = 'id_asc') {
    const xhr = new XMLHttpRequest();
    xhr.open('GET', `accountManagement/fetch_customer_accounts.php?page=${page}&search=${encodeURIComponent(search)}&sort=${sort}`, true);
    
    xhr.onload = function() {
        searchContainer.classList.remove('search-loading');
        if (this.status === 200) {
            try {
                const response = JSON.parse(this.responseText);
                
                // Update the table content
                document.querySelector('#customerTable tbody').innerHTML = response.tableContent;
                
                // Update pagination info
                const paginationInfo = `Showing ${response.showingFrom}-${response.showingTo} of ${response.totalCount} entries`;
                document.getElementById('paginationInfo').textContent = paginationInfo;
                
                // Update pagination buttons
                const paginationContainer = document.getElementById('paginationContainer');
                
                // Clear existing buttons
                paginationContainer.innerHTML = '';
                
                // Previous button
                const prevButton = document.createElement('button');
                prevButton.className = 'px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ' + (page === 1 ? 'opacity-50 cursor-not-allowed' : '');
                prevButton.textContent = 'Previous';
                prevButton.disabled = page === 1;
                prevButton.onclick = () => {
                    if (page > 1) fetchCustomerAccounts(page - 1, search, sort);
                };
                paginationContainer.appendChild(prevButton);
                
                // Page buttons
                const maxPagesToShow = 5;
                let startPage = Math.max(1, page - Math.floor(maxPagesToShow / 2));
                let endPage = Math.min(response.totalPages, startPage + maxPagesToShow - 1);
                
                // Adjust if we're at the beginning or end
                if (endPage - startPage + 1 < maxPagesToShow) {
                    startPage = Math.max(1, endPage - maxPagesToShow + 1);
                }
                
                // First page and ellipsis if needed
                if (startPage > 1) {
                    const firstPageButton = document.createElement('button');
                    firstPageButton.className = 'px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover';
                    firstPageButton.textContent = '1';
                    firstPageButton.onclick = () => fetchCustomerAccounts(1, search, sort);
                    paginationContainer.appendChild(firstPageButton);
                    
                    if (startPage > 2) {
                        const ellipsis = document.createElement('span');
                        ellipsis.className = 'px-2 flex items-center';
                        ellipsis.textContent = '...';
                        paginationContainer.appendChild(ellipsis);
                    }
                }
                
                // Page number buttons
                for (let i = startPage; i <= endPage; i++) {
                    const pageButton = document.createElement('button');
                    pageButton.className = `px-3 py-1 rounded text-sm mx-0.5 ${i === page ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover'}`;
                    pageButton.textContent = i;
                    pageButton.onclick = () => fetchCustomerAccounts(i, search, sort);
                    paginationContainer.appendChild(pageButton);
                }
                
                // Last page and ellipsis if needed
                if (endPage < response.totalPages) {
                    if (endPage < response.totalPages - 1) {
                        const ellipsis = document.createElement('span');
                        ellipsis.className = 'px-2 flex items-center';
                        ellipsis.textContent = '...';
                        paginationContainer.appendChild(ellipsis);
                    }
                    
                    const lastPageButton = document.createElement('button');
                    lastPageButton.className = 'px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover';
                    lastPageButton.textContent = response.totalPages;
                    lastPageButton.onclick = () => fetchCustomerAccounts(response.totalPages, search, sort);
                    paginationContainer.appendChild(lastPageButton);
                }
                
                // Next button
                const nextButton = document.createElement('button');
                nextButton.className = 'px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ' + (page === response.totalPages ? 'opacity-50 cursor-not-allowed' : '');
                nextButton.textContent = 'Next';
                nextButton.disabled = page === response.totalPages;
                nextButton.onclick = () => {
                    if (page < response.totalPages) fetchCustomerAccounts(page + 1, search, sort);
                };
                paginationContainer.appendChild(nextButton);
                
            } catch (e) {
                console.error('Error parsing response:', e);
                document.getElementById('paginationInfo').textContent = 'Error loading data';
            }
        } else {
            document.getElementById('paginationInfo').textContent = 'Error loading data';
        }
    };
    
    xhr.onerror = function() {
        document.getElementById('paginationInfo').textContent = 'Network error';
        console.error('Request failed');
    };
    
    xhr.send();
}
// Add a debounce function to prevent too many rapid searches
let searchTimeout;
const searchDebounceTime = 300; // milliseconds

// Client-side search function
function searchCustomers() {
  const searchTerm = document.getElementById('searchCustomer').value.trim().toLowerCase();
  const clearBtn = document.getElementById('clearSearchBtn');
  const table = document.getElementById('customerTable');
  const rows = table.querySelectorAll('tbody tr');
  
  // Show/hide clear button
  clearBtn.classList.toggle('hidden', searchTerm.length === 0);
  
  // If empty search, show all rows
  if (searchTerm === '') {
    rows.forEach(row => row.style.display = '');
    updatePaginationInfo(rows.length, rows.length);
    return;
  }
  
  let visibleCount = 0;
  
  // Search through each row
  rows.forEach(row => {
    const cells = row.querySelectorAll('td');
    let rowMatches = false;
    
    // Check each cell (except the last one with actions)
    for (let i = 0; i < cells.length - 1; i++) {
      const cellText = cells[i].textContent.toLowerCase();
      if (cellText.includes(searchTerm)) {
        rowMatches = true;
        break;
      }
    }
    
    // Show/hide row based on match
    row.style.display = rowMatches ? '' : 'none';
    if (rowMatches) visibleCount++;
  });
  
  // Update the "Showing X-Y of Z" info
  updatePaginationInfo(visibleCount, rows.length);
}

// Helper function to update pagination info
function updatePaginationInfo(visibleCount, totalCount) {
  const infoElement = document.querySelector('#manageAccountSection .text-sm.text-gray-600');
  infoElement.textContent = `Showing ${visibleCount} of ${totalCount} entries`;
}

// Clear search function
function clearSearch() {
  document.getElementById('searchCustomer').value = '';
  document.getElementById('clearSearchBtn').classList.add('hidden');
  searchCustomers(); // This will show all rows again
}

// Initialize with all rows visible
document.addEventListener('DOMContentLoaded', function() {
  const table = document.getElementById('customerTable');
  const rows = table.querySelectorAll('tbody tr');
  updatePaginationInfo(rows.length, rows.length);
});    
    
    // Function to archive a customer account
function archiveCustomerAccount(userId) {
    Swal.fire({
        title: 'Archive Customer Account',
        html: `Are you sure you want to archive this customer account?<br><br>
               <span class="text-sm text-gray-500">Archived accounts can be restored later if needed.</span>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#CA8A04',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, archive it',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        backdrop: `
            rgba(0,0,0,0.6)
            url("/images/nyan-cat.gif")
            left top
            no-repeat
        `
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Archiving...',
                html: 'Please wait while we archive the customer account',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request to archive the user
            const formData = new FormData();
            formData.append('user_id', userId);
            formData.append('status', 0); // 0 means archived
            formData.append('user_type', 3); // 3 is customer type

            fetch('../admin/accountManagement/archive_user.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        title: 'Archived!',
                        text: 'Customer account has been archived.',
                        icon: 'success',
                        confirmButtonColor: '#28a745',
                        timer: 2000,
                        timerProgressBar: true,
                        willClose: () => {
                            // Refresh the customer list
                            fetchCustomerAccounts();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to archive customer account',
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while archiving the account: ' + error,
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            });
        }
    });
}

// Update the deleteCustomerAccount function in your table to call archiveCustomerAccount instead
function deleteCustomerAccount(userId) {
    archiveCustomerAccount(userId);
}

// Global variables to track current table state
let currentPage = 1;
let currentSearch = '';
let currentSort = 'id_asc';

// Global variables for edit modal
let originalEmail = '';
let originalPhone = '';
let currentUserId = 0;
let emailChanged = false;

function openEditCustomerAccountModal(userId) {
    currentUserId = userId;
    emailChanged = false;
    
    // Show loading state
    Swal.fire({
        title: 'Loading...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Fetch user details
    fetch(`accountManagement/fetch_customer_details.php?user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            Swal.close();
            if (data.success) {
                originalEmail = data.user.email;
                originalPhone = data.user.phone_number;
                
                // Create modal HTML with validation indicators
const modalHTML = `
<div id="editCustomerModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto">
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
        <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeEditCustomerModal()">
            <i class="fas fa-times"></i>
        </button>
        <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
            <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
                Edit Customer Account
            </h3>
        </div>
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
                        <input type="text" name="first_name" value="${data.user.first_name || ''}" 
                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                               required
                               minlength="2"
                               oninput="this.value = this.value.replace(/[^A-Za-z ]/g, '').replace(/\s{2,}/g, ' '); 
                                        if(this.value.length > 1 && this.value.includes(' ')) {
                                            this.value = this.value.substring(0, this.value.indexOf(' '));
                                        }
                                        this.value = this.value.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(' ');"
                               onpaste="const text = event.clipboardData.getData('text/plain').replace(/[^A-Za-z ]/g, '').replace(/\s{2,}/g, ' ');
                                        event.preventDefault();
                                        const selection = window.getSelection();
                                        if (!selection.rangeCount) return;
                                        selection.deleteFromDocument();
                                        selection.getRangeAt(0).insertNode(document.createTextNode(text));">
                        <div class="text-xs text-red-500 mt-1 hidden" id="firstNameError">Minimum 2 characters required</div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                        Last Name <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" name="last_name" value="${data.user.last_name || ''}" 
                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                               required
                               minlength="2"
                               oninput="this.value = this.value.replace(/[^A-Za-z ]/g, '').replace(/\s{2,}/g, ' '); 
                                        if(this.value.length > 1 && this.value.includes(' ')) {
                                            this.value = this.value.substring(0, this.value.indexOf(' '));
                                        }
                                        this.value = this.value.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(' ');"
                               onpaste="const text = event.clipboardData.getData('text/plain').replace(/[^A-Za-z ]/g, '').replace(/\s{2,}/g, ' ');
                                        event.preventDefault();
                                        const selection = window.getSelection();
                                        if (!selection.rangeCount) return;
                                        selection.deleteFromDocument();
                                        selection.getRangeAt(0).insertNode(document.createTextNode(text));">
                        <div class="text-xs text-red-500 mt-1 hidden" id="lastNameError">Minimum 2 characters required</div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">Middle Name</label>
                    <div class="relative">
                        <input type="text" name="middle_name" value="${data.user.middle_name || ''}" 
                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                               oninput="let value = this.value;
                                        // Remove numbers and symbols, keep only letters and spaces
                                        let cleanedValue = value.replace(/[^a-zA-Z\\s]/g, '');
                                        // Remove multiple consecutive spaces
                                        cleanedValue = cleanedValue.replace(/\\s+/g, ' ');
                                        // Don't allow space unless there are at least 2 characters
                                        if (cleanedValue.length === 1 && cleanedValue === ' ') {
                                            cleanedValue = '';
                                        }
                                        // Don't allow space at the beginning
                                        if (cleanedValue.startsWith(' ')) {
                                            cleanedValue = cleanedValue.substring(1);
                                        }
                                        // Capitalize first letter of each word
                                        cleanedValue = cleanedValue.toLowerCase().replace(/(?:^|\\s)\\S/g, function(a) {
                                            return a.toUpperCase();
                                        });
                                        this.value = cleanedValue;"
                               onkeydown="if (event.key === ' ' && this.value.length < 2) event.preventDefault();"
                               onpaste="const text = event.clipboardData.getData('text/plain').replace(/[^A-Za-z ]/g, '').replace(/\\s+/g, ' ');
                                        event.preventDefault();
                                        let cleanedText = text;
                                        if (cleanedText.startsWith(' ')) {
                                            cleanedText = cleanedText.substring(1);
                                        }
                                        if (this.value.length < 2 && cleanedText.startsWith(' ')) {
                                            cleanedText = cleanedText.replace(/^\\s+/, '');
                                        }
                                        const selection = window.getSelection();
                                        if (!selection.rangeCount) return;
                                        selection.deleteFromDocument();
                                        selection.getRangeAt(0).insertNode(document.createTextNode(cleanedText));">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                        Email Address <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="email" name="email" value="${data.user.email || ''}" 
                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" 
                               required 
                               oninput="validateEmail(this); checkEmailAvailability(this.value, ${data.user.id})"
                               onkeydown="if (event.key === ' ') event.preventDefault();"
                               onpaste="const text = event.clipboardData.getData('text/plain').replace(/\s/g, '');
                                        event.preventDefault();
                                        const selection = window.getSelection();
                                        if (!selection.rangeCount) return;
                                        selection.deleteFromDocument();
                                        selection.getRangeAt(0).insertNode(document.createTextNode(text));">
                        <span id="emailAvailability" class="availability-indicator hidden">
                            <i class="fas fa-check-circle text-green-500"></i> Available
                        </span>
                        <span id="emailUnavailable" class="availability-indicator hidden">
                            <i class="fas fa-times-circle text-red-500"></i> In use
                        </span>
                        <div class="text-xs text-red-500 mt-1 hidden" id="emailError">Valid email with @ symbol required</div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                        Phone Number <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" name="phone_number" value="${data.user.phone_number || ''}"
                               class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                               inputmode="numeric" 
                               pattern="09[0-9]{9}" 
                               maxlength="11"
                               required 
                               oninput="let value = this.value;
                                        // Remove any non-digit characters immediately
                                        let cleanedValue = value.replace(/[^0-9]/g, '');
                                        // Limit to 11 digits
                                        cleanedValue = cleanedValue.substring(0, 11);
                                        this.value = cleanedValue;
                                        validatePhoneNumber(this); 
                                        checkPhoneAvailability(this.value, ${data.user.id})"
                               onkeydown="// Allow only numbers, backspace, delete, tab, escape, enter, and arrow keys
                                          const allowedKeys = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
                                          if (!allowedKeys.includes(event.key) && (event.key < '0' || event.key > '9')) {
                                              event.preventDefault();
                                          }"
                               onpaste="const text = event.clipboardData.getData('text/plain').replace(/[^0-9]/g, '');
                                        event.preventDefault();
                                        let pastedValue = text.substring(0, 11);
                                        if(pastedValue.length > 0 && !pastedValue.startsWith('09')) {
                                            pastedValue = '09' + pastedValue.substring(2);
                                        }
                                        this.value = pastedValue;
                                        validatePhoneNumber(this); 
                                        checkPhoneAvailability(this.value, ${data.user.id})">
                        <span id="phoneAvailability" class="availability-indicator hidden">
                            <i class="fas fa-check-circle text-green-500"></i> Available
                        </span>
                        <span id="phoneUnavailable" class="availability-indicator hidden">
                            <i class="fas fa-times-circle text-red-500"></i> In use
                        </span>
                        <div class="text-xs text-red-500 mt-1 hidden" id="phoneError">Valid Philippine number starting with 09 (11 digits)</div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                        Branch Location <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <select name="branch_loc" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                            <option value="">-- Select Branch --</option>
                            ${data.branches.map(branch => `
                                <option value="${branch.branch_id}" ${data.user.branch_loc == branch.branch_id ? 'selected' : ''}>
                                    ${branch.branch_name}
                                </option>
                            `).join('')}
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
            <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeEditCustomerModal()">
                Cancel
            </button>
            <button type="button" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="validateAndSaveCustomerChanges()">
                Save Changes
            </button>
        </div>
    </div>
</div>
`;
                // Add modal to DOM
                document.body.insertAdjacentHTML('beforeend', modalHTML);

                // Add event listeners for email and phone changes
                const emailInput = document.querySelector('#editCustomerModal input[name="email"]');
                emailInput.addEventListener('change', function() {
                    emailChanged = this.value !== originalEmail;
                });

                // Add event listener for Escape key
                document.addEventListener('keydown', function handleEscape(e) {
                    if (e.key === 'Escape') {
                        closeEditCustomerModal();
                        document.removeEventListener('keydown', handleEscape);
                    }
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: data.message || 'Failed to fetch customer details',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            }
        })
        .catch(error => {
            Swal.close();
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while fetching customer details',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
            console.error('Error:', error);
        });
}

// Check if email is available
function checkEmailAvailability(email, userId = 0) {
    const emailAvailability = document.getElementById('emailAvailability');
    const emailUnavailable = document.getElementById('emailUnavailable');
    
    // Hide both indicators initially
    emailAvailability.classList.add('hidden');
    emailUnavailable.classList.add('hidden');

    // If email is empty, return early
    if (!email) {
        return;
    }

    // Validate email format
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email)) {
        document.getElementById('emailError').classList.remove('hidden');
        return;
    } else {
        document.getElementById('emailError').classList.add('hidden');
    }

    // Show loading state
    emailAvailability.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
    emailAvailability.classList.remove('hidden');

    fetch(`accountManagement/check_email.php?email=${encodeURIComponent(email)}&current_user=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                emailAvailability.innerHTML = '<i class="fas fa-check-circle text-green-500"></i> Available';
                emailAvailability.classList.remove('hidden');
                emailUnavailable.classList.add('hidden');
            } else {
                emailAvailability.classList.add('hidden');
                emailUnavailable.innerHTML = '<i class="fas fa-times-circle text-red-500"></i> In use';
                emailUnavailable.classList.remove('hidden');
                Swal.fire({
                    title: 'Email In Use',
                    text: 'This email address is already registered.',
                    icon: 'error',
                    confirmButtonColor: '#d33',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            }
        })
        .catch(error => {
            console.error('Error checking email availability:', error);
            emailAvailability.classList.add('hidden');
            emailUnavailable.innerHTML = '<i class="fas fa-exclamation-triangle text-yellow-500"></i> Error checking';
            emailUnavailable.classList.remove('hidden');
            setTimeout(() => emailUnavailable.classList.add('hidden'), 3000);
        });
}

// Check if phone number is available
function checkPhoneAvailability(phone, userId = 0) {
    const phoneAvailability = document.getElementById('phoneAvailability');
    const phoneUnavailable = document.getElementById('phoneUnavailable');
    
    // Hide both indicators initially
    phoneAvailability.classList.add('hidden');
    phoneUnavailable.classList.add('hidden');

    // If phone is empty, return early
    if (!phone) {
        return;
    }

    // Validate phone format
    const phonePattern = /^09\d{9}$/;
    const cleanedPhone = phone.replace(/[^0-9]/g, '');
    if (!phonePattern.test(cleanedPhone)) {
        document.getElementById('phoneError').classList.remove('hidden');
        return;
    } else {
        document.getElementById('phoneError').classList.add('hidden');
    }

    // Show loading state
    phoneAvailability.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
    phoneAvailability.classList.remove('hidden');

    fetch(`accountManagement/check_phone.php?phone=${encodeURIComponent(cleanedPhone)}&current_user=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.available) {
                phoneAvailability.innerHTML = '<i class="fas fa-check-circle text-green-500"></i> Available';
                phoneAvailability.classList.remove('hidden');
                phoneUnavailable.classList.add('hidden');
            } else {
                phoneAvailability.classList.add('hidden');
                phoneUnavailable.innerHTML = '<i class="fas fa-times-circle text-red-500"></i> In use';
                phoneUnavailable.classList.remove('hidden');
                Swal.fire({
                    title: 'Phone Number In Use',
                    text: 'This phone number is already registered.',
                    icon: 'error',
                    confirmButtonColor: '#d33',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            }
        })
        .catch(error => {
            console.error('Error checking phone availability:', error);
            phoneAvailability.classList.add('hidden');
            phoneUnavailable.innerHTML = '<i class="fas fa-exclamation-triangle text-yellow-500"></i> Error checking';
            phoneUnavailable.classList.remove('hidden');
            setTimeout(() => phoneUnavailable.classList.add('hidden'), 3000);
        });
}
function validateAndSaveCustomerChanges() {
    const form = document.getElementById('editCustomerForm');
    if (!form) return;

    const formData = new FormData(form);
    const newEmail = formData.get('email');
    const newPhone = formData.get('phone_number');

    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;

    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('border-red-500');
            isValid = false;
        } else {
            field.classList.remove('border-red-500');
        }
    });

    if (!isValid) {
        Swal.fire({
            title: 'Validation Error',
            text: 'Please fill in all required fields',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
        return;
    }

    if (newEmail !== originalEmail) {
        if (!document.getElementById('emailUnavailable').classList.contains('hidden')) {
            Swal.fire({
                title: 'Email In Use',
                text: 'The new email address is already in use by another account.',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
            return;
        }

        if (document.getElementById('emailAvailability').innerHTML.includes('fa-spinner') ||
            (document.getElementById('emailAvailability').classList.contains('hidden') &&
             document.getElementById('emailUnavailable').classList.contains('hidden'))) {
            Swal.fire({
                title: 'Please Wait',
                text: 'Email availability check is still in progress',
                icon: 'warning',
                confirmButtonColor: '#d33'
            });
            return;
        }
    }

    if (newPhone !== originalPhone) {
        if (!document.getElementById('phoneUnavailable').classList.contains('hidden')) {
            Swal.fire({
                title: 'Phone In Use',
                text: 'The new phone number is already in use by another account.',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
            return;
        }

        if (document.getElementById('phoneAvailability').innerHTML.includes('fa-spinner') ||
            (document.getElementById('phoneAvailability').classList.contains('hidden') &&
             document.getElementById('phoneUnavailable').classList.contains('hidden'))) {
            Swal.fire({
                title: 'Please Wait',
                text: 'Phone availability check is still in progress',
                icon: 'warning',
                confirmButtonColor: '#d33'
            });
            return;
        }
    }

    if (emailChanged) {
        showEditOTPModal(newEmail);
    } else {
        saveCustomerChanges();
    }
}

// Show OTP modal for email verification during edit
function showEditOTPModal(email) {
    // Set the email in the OTP modal
    document.getElementById('otpEmail').textContent = email;
    
    // Send OTP to email
    const formData = new FormData();
    formData.append('email', email);
    
    // Show loading
    Swal.fire({
        title: 'Sending OTP...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../admin/addCustomer/send_otp.php', true);
    
    xhr.onload = function() {
        Swal.close();
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            if (response.success) {
                // Show OTP modal
                const modal = document.getElementById('otpVerificationModal');
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                
                // Update the verify button to handle edit flow
                const verifyBtn = modal.querySelector('button[onclick="verifyOTP()"]');
                verifyBtn.setAttribute('onclick', 'verifyEditOTP()');
                
                // Focus on first OTP input
                const otpInputs = document.querySelectorAll('.otp-input');
                if (otpInputs.length > 0) {
                    otpInputs[0].focus();
                }
            } else {
                Swal.fire({
                    title: 'Error Occurred',
                    text: response.message || 'Failed to send OTP',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#d33'
                });
            }
        }
    };
    
    xhr.onerror = function() {
        Swal.close();
        Swal.fire({
            title: 'Connection Error',
            text: 'Failed to connect to server',
            icon: 'error'
        });
    };
    
    xhr.send(formData);
}

// Verify OTP for edit flow
function verifyEditOTP() {
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
    
    // Show loading
    Swal.fire({
        title: 'Verifying...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '../admin/addCustomer/verify_otp.php', true);
    
    xhr.onload = function() {
        Swal.close();
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            if (response.success) {
                // OTP verified, proceed with saving changes
                closeOtpModal();
                saveCustomerChanges();
            } else {
                document.getElementById('otpError').textContent = response.message;
                document.getElementById('otpError').classList.remove('hidden');
            }
        }
    };
    
    xhr.send(formData);
}

function closeEditCustomerModal() {
    const modal = document.getElementById('editCustomerModal');
    if (modal) {
        modal.remove();
    }
}

function saveCustomerChanges() {
    const form = document.getElementById('editCustomerForm');
    if (!form) return;

    // Get all current form values
    const formData = new FormData(form);
    const currentEmail = formData.get('email');
    
    // Validate required fields
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.classList.add('border-red-500');
            isValid = false;
        } else {
            field.classList.remove('border-red-500');
        }
    });

    if (!isValid) {
        Swal.fire({
            title: 'Validation Error',
            text: 'Please fill in all required fields',
            icon: 'error',
            confirmButtonColor: '#d33'
        });
        return;
    }

    // Show loading state
    Swal.fire({
        title: 'Saving...',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Add the current_user parameter to check against
    formData.append('current_user', currentUserId);
    
    fetch('accountManagement/update_customer_account.php', {
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
        Swal.close();
        if (data.success) {
            Swal.fire({
                title: 'Success!',
                text: 'Customer account updated successfully',
                icon: 'success',
                confirmButtonColor: '#28a745',
                willClose: () => {
                    closeEditCustomerModal();
                    // Refresh the customer list with current filters
                    fetchCustomerAccounts(currentPage, currentSearch, currentSort);
                }
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: data.message || 'Failed to update customer account',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire({
            title: 'Error!',
            text: 'An error occurred while updating customer account: ' + error.message,
            icon: 'error',
            confirmButtonColor: '#d33'
        });
        console.error('Error:', error);
    });
}

// Function to view archived accounts
function viewArchivedAccounts() {
    // Show loading state
    const viewArchivedBtn = document.getElementById('viewArchivedBtn');
    const originalBtnText = viewArchivedBtn.innerHTML;
    viewArchivedBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Loading...';
    viewArchivedBtn.disabled = true;

    // Create modal container if it doesn't exist
    if (!document.getElementById('archivedAccountsModal')) {
      const modalHTML = `
<!-- Archived Accounts Modal -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="archivedAccountsModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeArchivedAccountsModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        <span id="modalTitle">Archived Customer Accounts</span>
      </h3>
    </div>
    
    <!-- Search Bar -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <i class="fas fa-search text-gray-400"></i>
        </div>
        <input type="text" id="archivedAccountsSearch" placeholder="Search archived accounts..." 
          class="w-full pl-10 pr-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
          oninput="let value = this.value;
                   // Remove multiple consecutive spaces
                   let cleanedValue = value.replace(/\s+/g, ' ');
                   // Don't allow space unless there are at least 2 characters
                   if (cleanedValue.length === 1 && cleanedValue === ' ') {
                       cleanedValue = '';
                   }
                   // Don't allow space at the beginning
                   if (cleanedValue.startsWith(' ')) {
                       cleanedValue = cleanedValue.substring(1);
                   }
                   this.value = cleanedValue;
                   filterArchivedAccounts();"
          onkeydown="if (event.key === ' ' && this.value.length < 2) event.preventDefault();"
          onpaste="const text = event.clipboardData.getData('text/plain').replace(/\s+/g, ' ');
                   event.preventDefault();
                   let cleanedText = text;
                   if (cleanedText.startsWith(' ')) {
                       cleanedText = cleanedText.substring(1);
                   }
                   if (this.value.length < 2 && cleanedText.startsWith(' ')) {
                       cleanedText = cleanedText.replace(/^\s+/, '');
                   }
                   this.value = cleanedText;
                   filterArchivedAccounts();">
      </div>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5 max-h-[70vh] overflow-y-auto w-full">
      <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
          <thead class="text-xs text-gray-700 uppercase bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3">Customer ID</th>
              <th scope="col" class="px-6 py-3">Name</th>
              <th scope="col" class="px-6 py-3">Email</th>
              <th scope="col" class="px-6 py-3">Type</th>
              <th scope="col" class="px-6 py-3">Actions</th>
            </tr>
          </thead>
          <tbody id="archivedAccountsTableBody">
            <!-- Archived accounts will be loaded here -->
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-6 py-4 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center" onclick="closeArchivedAccountsModal()">
        Close
      </button>
    </div>
  </div>
</div>
`;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }

    // Fetch archived accounts
    fetch('../admin/accountManagement/fetch_archived_accounts.php?user_type=3')
        .then(response => response.json())
        .then(data => {
            document.getElementById('archivedAccountsTableBody').innerHTML = data.tableContent;
            document.getElementById('archivedAccountsModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                title: 'Error!',
                text: 'Failed to load archived accounts',
                icon: 'error',
                confirmButtonColor: '#d33'
            });
        })
        .finally(() => {
            viewArchivedBtn.innerHTML = originalBtnText;
            viewArchivedBtn.disabled = false;
        });
}

// Function to close the archived accounts modal
function closeArchivedAccountsModal() {
    document.getElementById('archivedAccountsModal').classList.add('hidden');
}


// Function to unarchive an account
function unarchiveAccount(userId) {
    Swal.fire({
        title: 'Unarchive Account',
        html: `Are you sure you want to restore this account?<br><br>
               <span class="text-sm text-gray-500">The account will be active again.</span>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, restore it',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading indicator
            Swal.fire({
                title: 'Restoring...',
                html: 'Please wait while we restore the account',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Send AJAX request to unarchive the account
            const formData = new FormData();
            formData.append('id', userId);

            fetch('../admin/accountManagement/unarchive_account.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Restored!',
                        text: 'Account has been successfully restored.',
                        icon: 'success',
                        confirmButtonColor: '#28a745',
                        timer: 2000,
                        timerProgressBar: true,
                        willClose: () => {
                            // Close the archived accounts modal
                            closeArchivedAccountsModal();
                            // Reload the page to reflect changes
                            window.location.reload();
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to restore account',
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while restoring the account',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            });
        }
    });
}
    
      
    // Mode switching functionality
    function switchMode(mode) {
        if (mode === 'create') {
            document.getElementById('createAccountSection').classList.remove('hidden');
            document.getElementById('manageAccountSection').classList.add('hidden');
            document.getElementById('createBtn').classList.add('bg-sidebar-accent', 'text-white');
            document.getElementById('createBtn').classList.remove('bg-transparent', 'text-sidebar-text');
            document.getElementById('manageBtn').classList.add('bg-transparent', 'text-sidebar-text');
            document.getElementById('manageBtn').classList.remove('bg-sidebar-accent', 'text-white');
            document.getElementById('searchContainer').classList.add('hidden');
            document.getElementById('viewArchivedBtn').classList.add('hidden');
        } else { // manage mode
            document.getElementById('createAccountSection').classList.add('hidden');
            document.getElementById('manageAccountSection').classList.remove('hidden');
            document.getElementById('manageBtn').classList.add('bg-sidebar-accent', 'text-white');
            document.getElementById('manageBtn').classList.remove('bg-transparent', 'text-sidebar-text');
            document.getElementById('createBtn').classList.add('bg-transparent', 'text-sidebar-text');
            document.getElementById('createBtn').classList.remove('bg-sidebar-accent', 'text-white');
            document.getElementById('searchContainer').classList.remove('hidden');
            document.getElementById('viewArchivedBtn').classList.remove('hidden');
            fetchCustomerAccounts(); // Load data when manage section is shown
        }
    }
    
    
    
    // On page load, default to manage mode
    document.addEventListener('DOMContentLoaded', function() {
        switchMode('manage'); // Set manage as default view
        fetchCustomerAccounts(); // Load initial data
    });


  </script>
  <script src="sidebar.js"></script>
  <script src="tailwind.js"></script>
</body>
</html>