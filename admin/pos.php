<?php
session_start();

include 'faviconLogo.php'; 

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


// Database connection
include '../db_connect.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get all active services
function getServices($conn) {
    $sql = "SELECT s.service_id, s.service_name,s.description , s.service_categoryID, s.branch_id, 
                  s.inclusions,s.flower_design , s.capital_price, s.selling_price, s.image_url, 
                  b.branch_name, 
                  c.service_category_name
           FROM services_tb s
           INNER JOIN branch_tb b ON s.branch_id = b.branch_id
           INNER JOIN service_category c ON s.service_categoryID = c.service_categoryID
           WHERE s.status = 'Active'
           ORDER BY s.branch_id, s.service_categoryID, s.service_name";
    
    $result = $conn->query($sql);
    $services = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
    }
    
    return $services;
}

// Function to get all branches
function getBranches($conn) {
    $sql = "SELECT branch_id, branch_name FROM branch_tb";
    $result = $conn->query($sql);
    $branches = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $branches[] = $row;
        }
    }
    
    return $branches;
}

// Function to get all service categories
function getServiceCategories($conn) {
    $sql = "SELECT service_categoryID, service_category_name FROM service_category ";
    $result = $conn->query($sql);
    $categories = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

// Get data from database
$branches = getBranches($conn);
$categories = getServiceCategories($conn);
$allServices = getServices($conn);

// Convert to JSON for JavaScript
$branchesJson = json_encode($branches);
$categoriesJson = json_encode($categories);
$servicesJson = json_encode($allServices);

$userId = $_SESSION['user_id'];


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Point-Of-Sale</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
  /* Add these styles to your existing CSS */
      #serviceTypeModal .bg-cream {
        background-color: #FFF8E1;
      }

      #serviceTypeModal .border-yellow-600 {
        border-color: #D97706;
      }

      #serviceTypeModal .text-yellow-600 {
        color: #D97706;
      }

      #serviceTypeModal .text-navy {
        color: #1E3A8A;
      }

      #serviceTypeModal button:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      }

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
        <h1 class="text-2xl font-bold text-sidebar-text">Point-Of-Sale (POS)</h1>
      </div>
    </div>

    <div id="branch-selection" class="mb-8">
      <div class="flex justify-between items-center mb-5">
        <h2 class="text-gray-600 text-lg">Select a Branch Location</h2>
  
      </div>
    <div id="branches-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    <!-- Branches will be dynamically added here based on database data -->
  </div>
</div>

    <!-- Services Section (Initially hidden) -->
<div id="services-section" class="mb-8 hidden">
  <div class="flex items-center justify-between mb-6 bg-white p-4 rounded-lg shadow-sm border border-sidebar-border">
    <div class="flex items-center">
      <button onclick="goBackToBranches()" class="mr-4 p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300 flex items-center justify-center">
        <i class="fas fa-arrow-left"></i>
      </button>
      <h2 class="text-gray-700 text-lg">
        <span id="selected-category-name" class="font-semibold text-sidebar-accent"></span> Services at 
        <span id="services-branch-name" class="font-semibold text-sidebar-accent"></span>
      </h2>
    </div>
    <div class="hidden md:flex">
<div class="hidden md:flex items-center gap-4">
  <!-- Price Filter Dropdown -->
  <div class="relative">
    <select id="price-filter" class="appearance-none pl-3 pr-8 py-2 border border-sidebar-border rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent cursor-pointer">
      <option value="">All Prices</option>
      <option value="1-100000">₱1 - ₱100,000</option>
      <option value="100001-300000">₱100,001 - ₱300,000</option>
      <option value="300001-500000">₱300,001 - ₱500,000</option>
      <option value="500001-700000">₱500,001 - ₱700,000</option>
      <option value="700001+">₱700,001+</option>
    </select>
    <div class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
      <i class="fas fa-chevron-down text-gray-400"></i>
    </div>
  </div>
  
  <!-- Price Sort Dropdown -->
  <div class="relative">
    <select id="price-sort" class="appearance-none pl-3 pr-8 py-2 border border-sidebar-border rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent cursor-pointer">
      <option value="">Sort by Price</option>
      <option value="low-high">Low to High</option>
      <option value="high-low">High to Low</option>
    </select>
    <div class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
      <i class="fas fa-chevron-down text-gray-400"></i>
    </div>
  </div>
  <!-- Search Bar -->
  <div class="relative">
    <input type="text" id="service-search" placeholder="Search services..." 
           class="pl-10 pr-4 py-2 border border-sidebar-border rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
    <div id="search-error" class="hidden text-red-500 text-xs mt-1">Please avoid leading or multiple spaces</div>
  </div>
</div>
</div>
  </div>
  
  <div id="services-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
    <!-- Services will be dynamically added here based on branch and category selection -->
  </div>
</div>


  </div>
  <script>
function validateSearchInput(input) {
  const errorElement = document.getElementById('search-error');
  let value = input.value;
  
  // Check if first character is space
  if (value.length > 0 && value.charAt(0) === ' ') {
    errorElement.classList.remove('hidden');
    input.value = value.trim();
    return;
  }
  
  // Check for consecutive spaces
  if (value.includes('  ')) {
    errorElement.classList.remove('hidden');
    input.value = value.replace(/\s+/g, ' ');
    return;
  }
  
  errorElement.classList.add('hidden');
  
  // Auto-capitalize first letter
  if (value.length === 1) {
    input.value = value.charAt(0).toUpperCase() + value.slice(1);
  }
  
  // Optional: Trigger search as user types
  // You can add debounce functionality here if needed
  // performSearch(input.value);
}


// Helper function to validate name fields
// Updated name validation function
function validateNameInput(input, isRequired = true) {
  let value = input.value;
  
  // Backup the cursor position
  const cursorPosition = input.selectionStart;
  
  // Remove any numbers or special characters (except spaces, hyphens, and apostrophes)
  value = value.replace(/[^a-zA-Z\s\-']/g, '');
  
  // Replace multiple spaces with single space
  value = value.replace(/\s+/g, ' ');
  
  // Remove space at the beginning
  if (value.startsWith(' ')) {
    value = value.substring(1);
  }
  
  // Capitalize first letter of each word
  value = value.toLowerCase().replace(/(?:^|\s)\S/g, function(char) {
    return char.toUpperCase();
  });
  
  // Update the input value
  input.value = value;
  
  // Restore the cursor position
  input.setSelectionRange(cursorPosition, cursorPosition);
  
  // Validate minimum length for required fields
  if (isRequired && value.trim().length < 2) {
    input.setCustomValidity('Minimum 2 characters required');
  } else {
    input.setCustomValidity('');
  }
  
  return true;
}

// Helper function to validate address fields
function validateAddressInput(input) {
  let value = input.value;
  
  // Remove special characters (except spaces, commas, periods)
  value = value.replace(/[^a-zA-Z0-9\s,.]/g, '');
  
  // Replace multiple spaces with single space
  value = value.replace(/\s+/g, ' ');
  
  // Capitalize first letter of each word
  value = value.toLowerCase().replace(/(?:^|\s)\S/g, function(char) {
    return char.toUpperCase();
  });
  
  // Prevent space as first character
  if (value.length === 1 && value === ' ') {
    value = '';
  }
  
  input.value = value;
}

// Helper function to validate zip code
function validateZipCode(input) {
  let value = input.value;
  
  // Remove non-digit characters
  value = value.replace(/\D/g, '');
  
  // Limit to 10 characters
  if (value.length > 10) {
    value = value.substring(0, 10);
  }
  
  input.value = value;
  
  // Validate length
  if (value.length < 4) {
    input.setCustomValidity('ZIP code must be 4-10 digits');
  } else {
    input.setCustomValidity('');
  }
}

// Helper function to validate phone number
function validatePhoneNumber(input) {
  let value = input.value;
  
  // Remove non-digit characters
  value = value.replace(/\D/g, '');
  
  // Ensure it starts with 09
  if (value.length > 0 && !value.startsWith('09')) {
    value = '09' + value.substring(2);
  }
  
  // Limit to 11 characters
  if (value.length > 11) {
    value = value.substring(0, 11);
  }
  
  input.value = value;
  
  // Validate length and format
  if (value.length !== 11) {
    input.setCustomValidity('Philippine phone number must be 11 digits starting with 09');
  } else {
    input.setCustomValidity('');
  }
}

// Helper function to validate email
function validateEmail(input) {
  let value = input.value.trim();
  
  // Remove spaces
  value = value.replace(/\s/g, '');
  
  input.value = value;
  
  // Basic email validation
  if (value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
    input.setCustomValidity('Please enter a valid email address');
  } else {
    input.setCustomValidity('');
  }
}

// Helper function to validate relationship
function validateRelationship(input) {
  let value = input.value;
  
  // Remove numbers and special characters
  value = value.replace(/[^a-zA-Z\s]/g, '');
  
  // Replace multiple spaces with single space
  value = value.replace(/\s+/g, ' ');
  
  // Capitalize first letter
  if (value.length > 0) {
    value = value.charAt(0).toUpperCase() + value.slice(1).toLowerCase();
  }
  
  // Prevent space as first character
  if (value.length === 1 && value === ' ') {
    value = '';
  }
  
  input.value = value;
}

// Helper function to validate date fields
function setupDateValidations() {
  const today = new Date().toISOString().split('T')[0];
  const hundredYearsAgo = new Date();
  hundredYearsAgo.setFullYear(hundredYearsAgo.getFullYear() - 100);
  const minDate = hundredYearsAgo.toISOString().split('T')[0];
  
  // Traditional modal dates
  const dobInput = document.getElementById('dateOfBirth');
  const dodInput = document.getElementById('dateOfDeath');
  const dobInputLP = document.getElementById('beneficiaryDateOfBirth');
  
  if (dobInput) {
    dobInput.max = today;
    dobInput.min = minDate;
    
    dobInput.addEventListener('change', function() {
      if (dodInput.value && this.value > dodInput.value) {
        alert('Date of birth cannot be after date of death');
        this.value = '';
      }
      dodInput.min = this.value || minDate;
    });
  }
  
  if (dodInput) {
    dodInput.max = today;
    dodInput.min = minDate;
    
    dodInput.addEventListener('change', function() {
      if (dobInput.value && dobInput.value > this.value) {
        alert('Date of death cannot be before date of birth');
        this.value = '';
      }
      
      const burialInput = document.getElementById('dateOfBurial');
      if (burialInput) {
        burialInput.min = this.value || today;
      }
    });
  }
  
  if (dobInputLP) {
    dobInputLP.max = today;
    dobInputLP.min = minDate;
  }
  
  // Burial date validation
  const burialInput = document.getElementById('dateOfBurial');
  if (burialInput) {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    burialInput.min = tomorrow.toISOString().split('T')[0];
    
    burialInput.addEventListener('change', function() {
      if (dodInput.value && this.value < dodInput.value) {
        alert('Date of burial cannot be before date of death');
        this.value = '';
      }
    });
  }
}

// Helper function to validate file uploads
function validateFileUpload(input) {
  const file = input.files[0];
  if (!file) return;
  
  const validTypes = ['image/jpeg', 'image/jpg', 'image/png'];
  const maxSize = 5 * 1024 * 1024; // 5MB
  
  if (!validTypes.includes(file.type)) {
    alert('Please upload a valid image file (JPG, JPEG, or PNG)');
    input.value = '';
    return;
  }
  
  if (file.size > maxSize) {
    alert('File size must be less than 5MB');
    input.value = '';
    return;
  }
  
  // Show preview if it's an image
  if (file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = function(e) {
      const preview = document.getElementById('deathCertificatePreview');
      const previewImage = document.getElementById('previewImage');
      if (preview && previewImage) {
        previewImage.src = e.target.result;
        preview.classList.remove('hidden');
      }
    };
    reader.readAsDataURL(file);
  }
}

// Helper function to validate payment amounts
function validatePaymentAmounts() {
  const totalPriceInput = document.getElementById('totalPrice');
  const amountPaidInput = document.getElementById('amountPaid');
  const totalPriceInputLP = document.getElementById('lp-totalPrice');
  const amountPaidInputLP = document.getElementById('lp-amountPaid');
  
  function validateAmount(input, minValue = 0) {
    let value = parseFloat(input.value) || 0;
    if (value < minValue) {
      input.value = minValue;
      value = minValue;
    }
    return value;
  }
  
  if (totalPriceInput && amountPaidInput) {
    totalPriceInput.addEventListener('change', function() {
      validateAmount(this);
      amountPaidInput.max = this.value;
    });
    
    amountPaidInput.addEventListener('change', function() {
      validateAmount(this);
      if (parseFloat(this.value) > parseFloat(totalPriceInput.value)) {
        this.value = totalPriceInput.value;
      }
    });
  }
  
  if (totalPriceInputLP && amountPaidInputLP) {
    totalPriceInputLP.addEventListener('change', function() {
      validateAmount(this);
      amountPaidInputLP.max = this.value;
    });
    
    amountPaidInputLP.addEventListener('change', function() {
      validateAmount(this);
      if (parseFloat(this.value) > parseFloat(totalPriceInputLP.value)) {
        this.value = totalPriceInputLP.value;
      }
    });
  }
}

// Initialize all validations
function initializeAllValidations() {
  // Name fields validation (Traditional)
  document.getElementById('clientFirstName')?.addEventListener('input', function() {
    validateNameInput(this, true);
  });
  document.getElementById('clientMiddleName')?.addEventListener('input', function() {
    validateNameInput(this, false);
  });
  document.getElementById('clientLastName')?.addEventListener('input', function() {
    validateNameInput(this, true);
  });
  document.getElementById('deceasedFirstName')?.addEventListener('input', function() {
    validateNameInput(this, true);
  });
  document.getElementById('deceasedMiddleName')?.addEventListener('input', function() {
    validateNameInput(this, false);
  });
  document.getElementById('deceasedLastName')?.addEventListener('input', function() {
    validateNameInput(this, true);
  });


  
  // Name fields validation (Lifeplan)
  document.getElementById('lp-clientFirstName')?.addEventListener('input', function() {
    validateNameInput(this, true);
  });
  document.getElementById('lp-clientMiddleName')?.addEventListener('input', function() {
    validateNameInput(this, false);
  });
  document.getElementById('lp-clientLastName')?.addEventListener('input', function() {
    validateNameInput(this, true);
  });
  document.getElementById('beneficiaryFirstName')?.addEventListener('input', function() {
    validateNameInput(this, true);
  });
  document.getElementById('beneficiaryMiddleName')?.addEventListener('input', function() {
    validateNameInput(this, false);
  });
  document.getElementById('beneficiaryLastName')?.addEventListener('input', function() {
    validateNameInput(this, true);
  });
  document.getElementById('comakerFirstName')?.addEventListener('input', function() {
    validateNameInput(this, true);
  });
  document.getElementById('comakerMiddleName')?.addEventListener('input', function() {
    validateNameInput(this, false);
  });
  document.getElementById('comakerLastName')?.addEventListener('input', function() {
    validateNameInput(this, true);
  });
  
  // Address fields validation
  document.getElementById('beneficiaryStreet')?.addEventListener('input', function() {
    validateAddressInput(this);
  });

  document.getElementById('deceasedStreet')?.addEventListener('input', function() {
    validateAddressInput(this);
  });


  // ZIP code validation (Lifeplan)
  document.getElementById('beneficiaryZip')?.addEventListener('input', function() {
    validateZipCode(this);
  });
  document.getElementById('deceasedZip')?.addEventListener('input', function() {
    validateZipCode(this);
  });
  
  // Phone number validation
  document.getElementById('clientPhone')?.addEventListener('input', function() {
    validatePhoneNumber(this);
  });
  document.getElementById('lp-clientPhone')?.addEventListener('input', function() {
    validatePhoneNumber(this);
  });
  
  // Email validation
  document.getElementById('clientEmail')?.addEventListener('input', function() {
    validateEmail(this);
  });
  document.getElementById('lp-clientEmail')?.addEventListener('input', function() {
    validateEmail(this);
  });
  
  // Relationship validation
  document.getElementById('beneficiaryRelationship')?.addEventListener('input', function() {
    validateRelationship(this);
  });
  
  // Date validations
  setupDateValidations();
  
  // File upload validation
  document.getElementById('deathCertificate')?.addEventListener('change', function() {
    validateFileUpload(this);
  });
  
  // Payment amount validations
  validatePaymentAmounts();
  
  // Remove image button
  document.getElementById('removeImageBtn')?.addEventListener('click', function() {
    document.getElementById('deathCertificate').value = '';
    document.getElementById('deathCertificatePreview').classList.add('hidden');
  });
}

// Call this function when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  initializeAllValidations();
  
  // Also initialize when modals are shown
  document.getElementById('checkoutModal')?.addEventListener('shown.bs.modal', initializeAllValidations);
  document.getElementById('lifeplanCheckoutModal')?.addEventListener('shown.bs.modal', initializeAllValidations);
});
</script>

    <!-- Package Modal -->
<div id="package-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-3xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closePackageModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center" id="modal-title">
        Package Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5" id="modal-content">
      <!-- Content will be dynamically added here -->
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closePackageModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="addToCart()">
        Buy Now
      </button>
    </div>
  </div>
</div>

<!-- Checkout Modal -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto" id="checkoutModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-5xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeCheckoutModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200 ">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center" id="modal-package-title">
        Complete Your Order
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="checkoutForm" class="space-y-3 sm:space-y-4">
        <input type="hidden" id="service-id" name="service_id" value="">
        <input type="hidden" id="service-price" name="service_price">
        <input type="hidden" id="branch-id" name="branch_id" value="">
        <input type="hidden" id="deceasedAddress" name="deceased_address" value="">

        <!-- Client Information Section -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Client Information
          </h4>
          <div class="space-y-3 sm:space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 sm:gap-4">
              <div>
                <label for="clientFirstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  First Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="clientFirstName" name="clientFirstName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="clientMiddleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Middle Name <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="text" id="clientMiddleName" name="clientMiddleName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="clientLastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Last Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="clientLastName" name="clientLastName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="clientSuffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Suffix <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <select id="clientSuffix" name="clientSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-4">
              <div>
                <label for="clientPhone" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Phone Number <span class="text-red-500">*</span>
                </label>
                <input type="tel" id="clientPhone" name="clientPhone" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="clientEmail" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Email Address <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="email" id="clientEmail" name="clientEmail" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
          </div>
        </div>

        <!-- Deceased Information Section -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Deceased Information
          </h4>
          <div class="space-y-3 sm:space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 sm:gap-4">
              <div>
                <label for="deceasedFirstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  First Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="deceasedFirstName" name="deceasedFirstName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="deceasedMiddleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Middle Name <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="text" id="deceasedMiddleName" name="deceasedMiddleName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="deceasedLastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Last Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="deceasedLastName" name="deceasedLastName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="deceasedSuffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Suffix <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <select id="traditionalDeceasedSuffix" name="traditionalDeceasedSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
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
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-2 sm:gap-4">
              <div>
                <label for="dateOfBirth" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Date of Birth <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="date" id="dateOfBirth" name="dateOfBirth" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="dateOfDeath" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Date of Death <span class="text-red-500">*</span>
                </label>
                <input type="date" id="dateOfDeath" name="dateOfDeath" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="dateOfBurial" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Interment/Cremation Date <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="date" id="dateOfBurial" name="dateOfBurial" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
            <!-- New Address Dropdown Hierarchy -->
    <div class="space-y-3">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-2 sm:gap-4">
        <div>
          <label for="deceasedRegion" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Region <span class="text-red-500">*</span>
          </label>
          <select id="deceasedRegion" name="deceasedRegion" required 
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select Region</option>
            <?php foreach ($regions as $region): ?>
              <option value="<?php echo $region['region_code']; ?>"><?php echo $region['region_name']; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="deceasedProvince" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Province <span class="text-red-500">*</span>
          </label>
          <select id="deceasedProvince" name="deceasedProvince" required disabled
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select Province</option>
          </select>
        </div>
        <div>
          <label for="deceasedCity" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            City/Municipality <span class="text-red-500">*</span>
          </label>
          <select id="deceasedCity" name="deceasedCity" required disabled
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select City/Municipality</option>
          </select>
        </div>
        <div>
          <label for="deceasedBarangay" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Barangay <span class="text-red-500">*</span>
          </label>
          <select id="deceasedBarangay" name="deceasedBarangay" required disabled
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select Barangay</option>
          </select>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-2 sm:gap-4">
        <div class="md:col-span-2">
          <label for="deceasedStreet" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Street Address <span class="text-red-500">*</span>
          </label>
          <input type="text" id="deceasedStreet" name="deceasedStreet" required 
                 class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
        </div>
        <div>
          <label for="deceasedZip" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            ZIP Code <span class="text-red-500">*</span>
          </label>
          <input type="text" id="deceasedZip" name="deceasedZip" required 
                 class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
        </div>
      </div>
    </div>
    
    <div>
      <label for="deathCertificate" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
        Death Certificate <span class="text-xs text-gray-500">(If available)</span>
      </label>
      <div class="relative">
        <div class="flex items-center border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-1 focus-within:ring-sidebar-accent focus-within:border-sidebar-accent transition-all duration-200">
          <input type="file" id="deathCertificate" name="deathCertificate" accept="image/*,.pdf" class="w-full focus:outline-none">
        </div>
        <!-- Image preview container -->
        <div id="deathCertificatePreview" class="mt-2 hidden">
          <div class="relative inline-block">
            <img id="previewImage" src="#" alt="Death Certificate Preview" class="max-h-40 rounded-lg border border-gray-200">
            <button type="button" id="removeImageBtn" class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition-colors">
              <i class="fas fa-times text-xs"></i>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
  
        <!-- Payment Information -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Payment Information
          </h4>
          <div class="space-y-3 sm:space-y-4">
            <div>
              <label for="paymentMethod" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Method of Payment
              </label>
              <select id="paymentMethod" name="paymentMethod" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                <option value="" disabled selected>Select payment method</option>
                <option value="Cash">Cash</option>
                <option value="G-Cash">G-Cash</option>
                <option value="Bank Transfer">Bank Transfer</option>
              </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-4">
              <div>
                <label for="totalPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Total Price 
                  <span class="text-xs text-gray-500 ml-1">(Minimum: <span id="min-price">₱0.00</span>)</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500">₱</span>
                  </div>
                  <input type="number" id="totalPrice" name="totalPrice" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                </div>
              </div>
              <div>
                <label for="amountPaid" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Amount Paid
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500">₱</span>
                  </div>
                  <input type="number" id="amountPaid" name="amountPaid" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cremation Checklist Section -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Additional Services
          </h4>
          <div class="space-y-3">
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="checkbox" name="withCremation" id="withCremation" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              With Cremation
            </label>
            <p class="text-sm text-gray-500 ml-8">Check this box if the service includes cremation</p>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-between items-center border-t border-gray-200 sticky bottom-0 bg-white">
      <div class="text-gray-600 mb-3 sm:mb-0 w-full sm:w-auto text-center sm:text-left">
        <p class="font-medium">Order Total: <span class="text-xl font-bold text-sidebar-accent" id="footer-total-price">₱0.00</span></p>
      </div>
      <div class="flex flex-col sm:flex-row gap-2 sm:gap-4 w-full sm:w-auto">
        <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeCheckoutModal()">
          Cancel
        </button>
        <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="confirmCheckout()">
          Confirm Order
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Service Type Selection Modal -->
<div id="serviceTypeModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeServiceTypeModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Select Service Type
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <button id="traditionalServiceBtn" class="bg-[#F9F6F0] hover:bg-yellow-100 border-2 border-[#CA8A04] text-[#2D2B30] px-4 py-6 rounded-lg shadow-md transition-all duration-300 flex flex-col items-center">
          <i class="fas fa-dove text-3xl text-[#CA8A04] mb-2"></i>
          <span class="font-medium text-lg">Traditional</span>
          <span class="text-sm text-gray-600 mt-2 text-center">For immediate funeral needs</span>
        </button>
        
        <button id="lifeplanServiceBtn" class="bg-[#F9F6F0] hover:bg-yellow-100 border-2 border-[#CA8A04] text-[#2D2B30] px-4 py-6 rounded-lg shadow-md transition-all duration-300 flex flex-col items-center">
          <i class="fas fa-seedling text-3xl text-[#CA8A04] mb-2"></i>
          <span class="font-medium text-lg">Lifeplan</span>
          <span class="text-sm text-gray-600 mt-2 text-center">lifeplan funeral planning</span>
        </button>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex justify-end border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeServiceTypeModal()">
        Cancel
      </button>
    </div>
  </div>
</div>

<!-- Lifeplan Checkout Modal -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto" id="lifeplanCheckoutModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-5xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeLifeplanCheckoutModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Complete Your Lifeplan Order
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="lifeplanCheckoutForm" class="space-y-3 sm:space-y-4" onsubmit="event.preventDefault(); confirmLifeplanCheckout();">
        <input type="hidden" id="lp-service-id" name="service_id" value="">
        <input type="hidden" id="lp-service-price" name="service_price">
        <input type="hidden" id="lp-branch-id" name="branch_id" value="">
        <input type="hidden" id="beneficiaryAddress" name="beneficiaryAddress" value="">
        <input type="hidden" id="comakerAddress" name="comakerAddress" value="">

        <!-- Client Information Section -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Client Information
          </h4>
          <div class="space-y-3 sm:space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 sm:gap-4">
              <div>
                <label for="lp-clientFirstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  First Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="lp-clientFirstName" name="clientFirstName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="lp-clientMiddleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Middle Name <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="text" id="lp-clientMiddleName" name="clientMiddleName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="lp-clientLastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Last Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="lp-clientLastName" name="clientLastName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="lp-clientSuffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Suffix <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <select id="lp-clientSuffix" name="clientSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-4">
              <div>
                <label for="lp-clientPhone" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Phone Number <span class="text-red-500">*</span>
                </label>
                <input type="tel" id="lp-clientPhone" name="clientPhone" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" minlength="11" maxlength="11">
              </div>
              <div>
                <label for="lp-clientEmail" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Email Address <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="email" id="lp-clientEmail" name="clientEmail" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
            </div>
          </div>
        </div>

        <!-- Beneficiary Information Section -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Beneficiary Information
          </h4>
          <div class="space-y-3 sm:space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 sm:gap-4">
              <div>
                <label for="beneficiaryFirstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  First Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="beneficiaryFirstName" name="beneficiaryFirstName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="beneficiaryMiddleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Middle Name <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="text" id="beneficiaryMiddleName" name="beneficiaryMiddleName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="beneficiaryLastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Last Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="beneficiaryLastName" name="beneficiaryLastName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="beneficiarySuffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Suffix <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <select id="beneficiarySuffix" name="beneficiarySuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
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
            
            <div>
              <label for="beneficiaryDateOfBirth" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Date of Birth
              </label>
              <input type="date" id="beneficiaryDateOfBirth" name="beneficiaryDateOfBirth" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
            <!-- New Address Dropdown Hierarchy -->
    <div class="space-y-3">
      <div class="grid grid-cols-1 md:grid-cols-4 gap-2 sm:gap-4">
        <div>
          <label for="beneficiaryRegion" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Region <span class="text-red-500">*</span>
          </label>
          <select id="beneficiaryRegion" name="beneficiaryRegion" required 
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select Region</option>
            <?php foreach ($regions as $region): ?>
              <option value="<?php echo $region['region_code']; ?>"><?php echo $region['region_name']; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label for="beneficiaryProvince" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Province <span class="text-red-500">*</span>
          </label>
          <select id="beneficiaryProvince" name="beneficiaryProvince" required disabled
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select Province</option>
          </select>
        </div>
        <div>
          <label for="beneficiaryCity" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            City/Municipality <span class="text-red-500">*</span>
          </label>
          <select id="beneficiaryCity" name="beneficiaryCity" required disabled
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select City/Municipality</option>
          </select>
        </div>
        <div>
          <label for="beneficiaryBarangay" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Barangay <span class="text-red-500">*</span>
          </label>
          <select id="beneficiaryBarangay" name="beneficiaryBarangay" required disabled
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            <option value="" disabled selected>Select Barangay</option>
          </select>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-3 gap-2 sm:gap-4">
        <div class="md:col-span-2">
          <label for="beneficiaryStreet" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Street Address <span class="text-red-500">*</span>
          </label>
          <input type="text" id="beneficiaryStreet" name="beneficiaryStreet" required 
                 class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
        </div>
        <div>
          <label for="beneficiaryZip" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            ZIP Code <span class="text-red-500">*</span>
          </label>
          <input type="text" id="beneficiaryZip" name="beneficiaryZip" required 
                 class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
        </div>
      </div>
    </div>
    
            <div>
              <label for="beneficiaryRelationship" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Relationship to Client
              </label>
              <input type="text" id="beneficiaryRelationship" name="beneficiaryRelationship" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
          </div>
        </div>

        <!-- Co-Maker Information Section -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Co-Maker Information 
          </h4>
          <div class="space-y-3 sm:space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-2 sm:gap-4">
              <div>
                <label for="comakerFirstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  First Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="comakerFirstName" name="comakerFirstName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="comakerMiddleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Middle Name <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <input type="text" id="comakerMiddleName" name="comakerMiddleName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="comakerLastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Last Name <span class="text-red-500">*</span>
                </label>
                <input type="text" id="comakerLastName" name="comakerLastName" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
              </div>
              <div>
                <label for="comakerSuffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Suffix <span class="text-xs text-gray-500">(Optional)</span>
                </label>
                <select id="comakerSuffix" name="comakerSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
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
            
            <div>
              <label for="comakerOccupation" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Occupation/Work <span class="text-red-500">*</span>
              </label>
              <input type="text" id="comakerOccupation" name="comakerOccupation" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
            
            <!-- New Address Dropdown Hierarchy -->
            <div class="space-y-3">
              <div class="grid grid-cols-1 md:grid-cols-4 gap-2 sm:gap-4">
                <div>
                  <label for="comakerRegion" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                    Region <span class="text-red-500">*</span>
                  </label>
                  <select id="comakerRegion" name="comakerRegion" required 
                          class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                    <option value="" disabled selected>Select Region</option>
                    <?php foreach ($regions as $region): ?>
                      <option value="<?php echo $region['region_code']; ?>"><?php echo $region['region_name']; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div>
                  <label for="comakerProvince" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                    Province <span class="text-red-500">*</span>
                  </label>
                  <select id="comakerProvince" name="comakerProvince" required disabled
                          class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                    <option value="" disabled selected>Select Province</option>
                  </select>
                </div>
                <div>
                  <label for="comakerCity" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                    City/Municipality <span class="text-red-500">*</span>
                  </label>
                  <select id="comakerCity" name="comakerCity" required disabled
                          class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                    <option value="" disabled selected>Select City/Municipality</option>
                  </select>
                </div>
                <div>
                  <label for="comakerBarangay" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                    Barangay <span class="text-red-500">*</span>
                  </label>
                  <select id="comakerBarangay" name="comakerBarangay" required disabled
                          class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                    <option value="" disabled selected>Select Barangay</option>
                  </select>
                </div>
              </div>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-2 sm:gap-4">
                <div class="md:col-span-2">
                  <label for="comakerStreet" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                    Street Address <span class="text-red-500">*</span>
                  </label>
                  <input type="text" id="comakerStreet" name="comakerStreet" required 
                         class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                </div>
                <div>
                  <label for="comakerZip" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                    ZIP Code <span class="text-red-500">*</span>
                  </label>
                  <input type="text" id="comakerZip" name="comakerZip" required 
                         class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                </div>
              </div>
            </div>
            
            <!-- ID Image Upload Section -->
            <div class="pt-4 border-t border-gray-200">
              <h5 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                <i class="fas fa-id-card mr-2 text-sidebar-accent"></i> Identification Document
              </h5>
              
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label for="comakerIdType" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                    ID Type <span class="text-red-500">*</span>
                  </label>
                  <select id="comakerIdType" name="comakerIdType" required 
                          class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                    <option value="" disabled selected>Select ID Type</option>
                    <option value="Passport">Passport</option>
                    <option value="Driver's License">Driver's License</option>
                    <option value="SSS ID">SSS ID</option>
                    <option value="PhilHealth ID">PhilHealth ID</option>
                    <option value="TIN ID">TIN ID</option>
                    <option value="Postal ID">Postal ID</option>
                    <option value="Voter's ID">Voter's ID</option>
                    <option value="PRC ID">PRC ID</option>
                    <option value="UMID">Unified Multi-Purpose ID (UMID)</option>
                    <option value="Company ID">Company ID</option>
                    <option value="School ID">School ID</option>
                    <option value="Other">Other Government-Issued ID</option>
                  </select>
                </div>
                
                <div>
                  <label for="comakerIdNumber" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                    ID Number <span class="text-red-500">*</span>
                  </label>
                  <input type="text" id="comakerIdNumber" name="comakerIdNumber" required 
                         class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                         placeholder="Enter ID number">
                </div>
              </div>
              
              <div class="mt-4">
                <label class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
                  Upload ID Image <span class="text-red-500">*</span>
                  <span class="text-xs text-gray-500 ml-2">(Max 5MB, JPG, PNG or PDF)</span>
                </label>
                
                <div class="flex items-center justify-center w-full">
                  <label for="comakerIdImage" class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                      <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                      <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                      <p class="text-xs text-gray-500">JPG, PNG, PDF (Max 5MB)</p>
                    </div>
                    <input id="comakerIdImage" name="comakerIdImage" type="file" class="hidden" accept=".jpg,.jpeg,.png,.pdf" required />
                  </label>
                </div>
                
                <div id="comakerIdPreview" class="mt-3 hidden">
                  <p class="text-xs text-gray-700 mb-1">Preview:</p>
                  <div class="border border-gray-200 rounded-lg p-2 flex items-center">
                    <div class="mr-3" id="comakerIdPreviewImage">
                      <!-- Image preview will be inserted here -->
                    </div>
                    <div class="flex-1">
                      <p id="comakerIdFileName" class="text-sm font-medium text-gray-700"></p>
                      <p id="comakerIdFileSize" class="text-xs text-gray-500"></p>
                    </div>
                    <button type="button" id="comakerIdRemove" class="text-red-500 hover:text-red-700 ml-2">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
  
        <!-- Payment Information -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Payment Information
          </h4>
          <div class="space-y-3 sm:space-y-4">
            <div>
              <label for="lp-paymentMethod" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Method of Payment
              </label>
              <select id="lp-paymentMethod" name="paymentMethod" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                <option value="" disabled selected>Select payment method</option>
                <option value="Cash">Cash</option>
                <option value="G-Cash">G-Cash</option>
                <option value="Bank Transfer">Bank Transfer</option>
              </select>
            </div>

            <div>
              <label for="lp-paymentTerm" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Payment Term
              </label>
              <select id="lp-paymentTerm" name="paymentTerm" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                <option value="1">1 Year (Full Payment)</option>
                <option value="2">2 Years</option>
                <option value="3">3 Years</option>
                <option value="5">5 Years</option>
              </select>
              <div id="lp-monthlyPayment" class="mt-2 text-sm text-gray-600 hidden">
                Monthly Payment: <span class="font-semibold text-sidebar-accent">₱0.00</span>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-4">
              <div>
                <label for="lp-totalPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Total Price 
                  <span class="text-xs text-gray-500 ml-1">(Minimum: <span id="lp-min-price">₱0.00</span>)</span>
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500">₱</span>
                  </div>
                  <input type="number" id="lp-totalPrice" name="totalPrice" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                </div>
              </div>
              <div>
                <label for="lp-amountPaid" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Amount Paid
                </label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500">₱</span>
                  </div>
                  <input type="number" id="lp-amountPaid" name="amountPaid" required class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Additional Services (Cremation) -->
        <div class="bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-base sm:text-lg font-bold text-gray-800 mb-3 sm:mb-4 pb-2 border-b border-gray-200 flex items-center">
            Additional Services
          </h4>
          <div class="space-y-3">
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="checkbox" name="withCremation" id="lp-withCremation" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              With Cremation
            </label>
            <p class="text-sm text-gray-500 ml-8">Check this box if the service includes cremation</p>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row justify-between items-center gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <div class="text-gray-600 w-full sm:w-auto">
        <p class="font-medium">Order Total: <span class="text-xl font-bold text-sidebar-accent" id="lp-footer-total-price">₱0.00</span></p>
      </div>
      <div class="flex flex-col sm:flex-row w-full sm:w-auto gap-2 sm:gap-4">
        <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeLifeplanCheckoutModal()">
          Cancel
        </button>
        <button id="lp-confirm-btn" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="confirmLifeplanCheckout()">
          Confirm Order
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Function to handle ID image upload preview
document.addEventListener('DOMContentLoaded', function() {
  const comakerIdImage = document.getElementById('comakerIdImage');
  const comakerIdPreview = document.getElementById('comakerIdPreview');
  const comakerIdPreviewImage = document.getElementById('comakerIdPreviewImage');
  const comakerIdFileName = document.getElementById('comakerIdFileName');
  const comakerIdFileSize = document.getElementById('comakerIdFileSize');
  const comakerIdRemove = document.getElementById('comakerIdRemove');
  
  if (comakerIdImage) {
    comakerIdImage.addEventListener('change', function(event) {
      const file = event.target.files[0];
      if (file) {
        // Check file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          alert('File size exceeds 5MB limit. Please choose a smaller file.');
          this.value = '';
          return;
        }
        
        // Check file type
        const validTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!validTypes.includes(file.type)) {
          alert('Please select a JPG, PNG, or PDF file.');
          this.value = '';
          return;
        }
        
        // Display file info
        comakerIdFileName.textContent = file.name;
        comakerIdFileSize.textContent = formatFileSize(file.size);
        
        // Create preview for images
        if (file.type.includes('image')) {
          const reader = new FileReader();
          reader.onload = function(e) {
            // Clear previous preview
            comakerIdPreviewImage.innerHTML = '';
            
            // Create image element
            const img = document.createElement('img');
            img.src = e.target.result;
            img.alt = "ID Preview";
            img.className = "w-16 h-16 object-cover rounded";
            
            // Add to preview container
            comakerIdPreviewImage.appendChild(img);
          };
          reader.readAsDataURL(file);
        } else {
          // For PDF files, show a document icon
          comakerIdPreviewImage.innerHTML = '<i class="fas fa-file-pdf text-3xl text-red-500"></i>';
        }
        
        comakerIdPreview.classList.remove('hidden');
      }
    });
  }
  
  if (comakerIdRemove) {
    comakerIdRemove.addEventListener('click', function() {
      comakerIdImage.value = '';
      comakerIdPreview.classList.add('hidden');
      comakerIdPreviewImage.innerHTML = '';
    });
  }
  
  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }
});
</script>

    <!-- Order Confirmation Modal -->
<div id="confirmation-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 z-10 transform transition-all duration-300">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeConfirmationModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        Order Confirmed
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5 text-center">
      <div class="w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
        <i class="fas fa-check-circle text-3xl text-green-600"></i>
      </div>
      <h4 class="text-lg font-semibold mb-2 text-sidebar-text">Order Confirmed!</h4>
      <p class="text-gray-600 mb-4">Your order has been successfully placed.</p>
      <p class="text-gray-600 mb-2">Order ID: <span id="order-id" class="font-semibold">ORD-12345</span></p>
      <p class="text-gray-600">A confirmation has been sent to your records.</p>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-6 py-4 flex justify-center border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="startNewOrder()">
        Start New Order
      </button>
    </div>
  </div>
</div>

  <script>
      // TRADITIONAL_FUNERAL Address handling functions
function fetchRegions() {
    console.log('Fetching regions...'); // Add this
    fetch('../customer/address/get_regions.php')
        .then(response => {
            console.log('Regions response:', response); // Add this
            return response.json();
        })
        .then(data => {
            const regionSelect = document.getElementById('deceasedRegion');
            regionSelect.innerHTML = '<option value="" disabled selected>Select Region</option>';
            
            data.forEach(region => {
                const option = document.createElement('option');
                option.value = region.region_id;  // Changed from region_code
                option.textContent = region.region_name;
                regionSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error fetching regions:', error));
}

function fetchProvinces(regionCode) {
    console.log('[DEBUG] Fetching provinces for region:', regionCode); // Check if regionCode is correct
    
    const provinceSelect = document.getElementById('deceasedProvince');
    provinceSelect.innerHTML = '<option value="" disabled selected>Select Province</option>';
    provinceSelect.disabled = true;
    
    if (!regionCode) {
        console.warn('[WARNING] No regionCode provided!');
        return;
    }
    
    const apiUrl = `../customer/address/get_provinces.php?region_id=${regionCode}`;
    console.log('[DEBUG] Fetching from:', apiUrl); // Check if URL is correct
    
    fetch(apiUrl)
        .then(response => {
            console.log('[DEBUG] Provinces API Response:', response); // Check HTTP response
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('[DEBUG] Provinces Data:', data); // Check if data is correct
            
            if (!data || data.length === 0) {
                console.warn('[WARNING] No provinces returned!');
                return;
            }
            
            provinceSelect.innerHTML = '<option value="" disabled selected>Select Province</option>';
            
            data.forEach(province => {
                const option = document.createElement('option');
                option.value = province.province_id;  // Changed from province_code
                option.textContent = province.province_name;
                provinceSelect.appendChild(option);
            });
            
            provinceSelect.disabled = false;
        })
        .catch(error => console.error('[ERROR] fetchProvinces failed:', error));
}

function fetchCities(provinceCode) {
    console.log('[DEBUG] Fetching cities for province:', provinceCode); // Check if provinceCode is correct
    
    const citySelect = document.getElementById('deceasedCity');
    citySelect.innerHTML = '<option value="" disabled selected>Select City/Municipality</option>';
    citySelect.disabled = true;
    
    if (!provinceCode) {
        console.warn('[WARNING] No provinceCode provided!');
        return;
    }
    
    const apiUrl = `../customer/address/get_cities.php?province_id=${provinceCode}`;
    console.log('[DEBUG] Fetching from:', apiUrl); // Check if URL is correct
    
    fetch(apiUrl)
        .then(response => {
            console.log('[DEBUG] Cities API Response:', response); // Check HTTP response
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('[DEBUG] Cities Data:', data); // Check if data is correct
            
            if (!data || data.length === 0) {
                console.warn('[WARNING] No cities returned!');
                return;
            }
            
            citySelect.innerHTML = '<option value="" disabled selected>Select City/Municipality</option>';
            
            data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.municipality_id;  // Changed from city_code
                option.textContent = city.municipality_name;  // Changed from city_name
                citySelect.appendChild(option);
            });
            
            citySelect.disabled = false;
        })
        .catch(error => console.error('[ERROR] fetchCities failed:', error));
}

function fetchBarangays(cityCode) {
    console.log('[DEBUG] Fetching barangays for city:', cityCode); // Check if cityCode is correct
    
    const barangaySelect = document.getElementById('deceasedBarangay');
    barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
    barangaySelect.disabled = true;
    
    if (!cityCode) {
        console.warn('[WARNING] No cityCode provided!');
        return;
    }
    
    const apiUrl = `../customer/address/get_barangays.php?city_id=${cityCode}`;
    console.log('[DEBUG] Fetching from:', apiUrl); // Check if URL is correct
    
    fetch(apiUrl)
        .then(response => {
            console.log('[DEBUG] Barangays API Response:', response); // Check HTTP response
            if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            console.log('[DEBUG] Barangays Data:', data); // Check if data is correct
            
            if (!data || data.length === 0) {
                console.warn('[WARNING] No barangays returned!');
                return;
            }
            
            barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
            
            data.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.barangay_id;  // Changed from barangay_code
                option.textContent = barangay.barangay_name;
                barangaySelect.appendChild(option);
            });
            
            barangaySelect.disabled = false;
        })
        .catch(error => console.error('[ERROR] fetchBarangays failed:', error));
}

function updateCombinedAddress() {
    const regionSelect = document.getElementById('deceasedRegion');
    const provinceSelect = document.getElementById('deceasedProvince');
    const citySelect = document.getElementById('deceasedCity');
    const barangaySelect = document.getElementById('deceasedBarangay');
    const streetAddress = document.getElementById('deceasedStreet').value;
    const zipCode = document.getElementById('deceasedZip').value;
    
    const region = regionSelect.options[regionSelect.selectedIndex]?.text || '';
    const province = provinceSelect.options[provinceSelect.selectedIndex]?.text || '';
    const city = citySelect.options[citySelect.selectedIndex]?.text || '';
    const barangay = barangaySelect.options[barangaySelect.selectedIndex]?.text || '';
    
    // Create an array of non-empty address components
    const addressParts = [];
    if (streetAddress) addressParts.push(streetAddress);
    if (barangay) addressParts.push(barangay);
    if (city) addressParts.push(city);
    if (province) addressParts.push(province);
    if (region) addressParts.push(region);
    if (zipCode) addressParts.push(zipCode);
    
    // Join the parts with commas
    const combinedAddress = addressParts.join(', ');
    document.getElementById('deceasedAddress').value = combinedAddress;
}

// Initialize address dropdowns when the page loads
document.addEventListener('DOMContentLoaded', function() {
    fetchRegions();
    
    // Set up event listeners for cascading dropdowns
    document.getElementById('deceasedRegion').addEventListener('change', function() {
        fetchProvinces(this.value);
        document.getElementById('deceasedProvince').value = '';
        document.getElementById('deceasedCity').value = '';
        document.getElementById('deceasedBarangay').value = '';
        document.getElementById('deceasedCity').disabled = true;
        document.getElementById('deceasedBarangay').disabled = true;
        updateCombinedAddress();
    });
    
    document.getElementById('deceasedProvince').addEventListener('change', function() {
        fetchCities(this.value);
        document.getElementById('deceasedCity').value = '';
        document.getElementById('deceasedBarangay').value = '';
        document.getElementById('deceasedBarangay').disabled = true;
        updateCombinedAddress();
    });
    
    document.getElementById('deceasedCity').addEventListener('change', function() {
        fetchBarangays(this.value);
        document.getElementById('deceasedBarangay').value = '';
        updateCombinedAddress();
    });
    
    document.getElementById('deceasedBarangay').addEventListener('change', updateCombinedAddress);
    document.getElementById('deceasedStreet').addEventListener('input', updateCombinedAddress);
    document.getElementById('deceasedZip').addEventListener('input', updateCombinedAddress);
    
    // Also update combined address when form is submitted
    document.getElementById('bookingForm').addEventListener('submit', function(e) {
        updateCombinedAddress();
        // Continue with form submission
    });
});
      
      
      
      // LIFE-PLAN 

// Fetch co-maker regions
function fetchComakerRegions() {
    fetch('../customer/address/get_regions.php')
        .then(response => response.json())
        .then(data => {
            const regionSelect = document.getElementById('comakerRegion');
            regionSelect.innerHTML = '<option value="" disabled selected>Select Region</option>';
            
            data.forEach(region => {
                const option = document.createElement('option');
                option.value = region.region_id;
                option.textContent = region.region_name;
                regionSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error fetching regions:', error));
}

// Fetch co-maker provinces based on region
function fetchComakerProvinces(regionId) {
    const provinceSelect = document.getElementById('comakerProvince');
    provinceSelect.innerHTML = '<option value="" disabled selected>Select Province</option>';
    provinceSelect.disabled = true;
    
    if (!regionId) return;
    
    fetch(`../customer/address/get_provinces.php?region_id=${regionId}`)
        .then(response => response.json())
        .then(data => {
            provinceSelect.innerHTML = '<option value="" disabled selected>Select Province</option>';
            
            data.forEach(province => {
                const option = document.createElement('option');
                option.value = province.province_id;
                option.textContent = province.province_name;
                provinceSelect.appendChild(option);
            });
            
            provinceSelect.disabled = false;
        })
        .catch(error => console.error('Error fetching provinces:', error));
}

// Fetch co-maker cities based on province
function fetchComakerCities(provinceId) {
    const citySelect = document.getElementById('comakerCity');
    citySelect.innerHTML = '<option value="" disabled selected>Select City/Municipality</option>';
    citySelect.disabled = true;
    
    if (!provinceId) return;
    
    fetch(`../customer/address/get_cities.php?province_id=${provinceId}`)
        .then(response => response.json())
        .then(data => {
            citySelect.innerHTML = '<option value="" disabled selected>Select City/Municipality</option>';
            
            data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.municipality_id;
                option.textContent = city.municipality_name;
                citySelect.appendChild(option);
            });
            
            citySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching cities:', error));
}

// Fetch co-maker barangays based on city
function fetchComakerBarangays(cityId) {
    const barangaySelect = document.getElementById('comakerBarangay');
    barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
    barangaySelect.disabled = true;
    
    if (!cityId) return;
    
    fetch(`../customer/address/get_barangays.php?city_id=${cityId}`)
        .then(response => response.json())
        .then(data => {
            barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
            
            data.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.barangay_id;
                option.textContent = barangay.barangay_name;
                barangaySelect.appendChild(option);
            });
            
            barangaySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching barangays:', error));
}

// Update the combined address for co-maker
function updateComakerCombinedAddress() {
    const regionSelect = document.getElementById('comakerRegion');
    const provinceSelect = document.getElementById('comakerProvince');
    const citySelect = document.getElementById('comakerCity');
    const barangaySelect = document.getElementById('comakerBarangay');
    const streetAddress = document.getElementById('comakerStreet').value;
    const zipCode = document.getElementById('comakerZip').value;
    
    const region = regionSelect.options[regionSelect.selectedIndex]?.text || '';
    const province = provinceSelect.options[provinceSelect.selectedIndex]?.text || '';
    const city = citySelect.options[citySelect.selectedIndex]?.text || '';
    const barangay = barangaySelect.options[barangaySelect.selectedIndex]?.text || '';
    
    const addressParts = [];
    if (streetAddress) addressParts.push(streetAddress);
    if (barangay) addressParts.push(barangay);
    if (city) addressParts.push(city);
    if (province) addressParts.push(province);
    if (region) addressParts.push(region);
    if (zipCode) addressParts.push(zipCode);
    
    const combinedAddress = addressParts.join(', ');
    // If you have a hidden field for the comaker address, update it here
    document.getElementById('comakerAddress').value = combinedAddress;
}

// Initialize co-maker address dropdowns
document.addEventListener('DOMContentLoaded', function() {
    // Initialize both beneficiary and co-maker regions
    fetchBeneficiaryRegions();
    fetchComakerRegions();
    
    // Set up event listeners for co-maker cascading dropdowns
    document.getElementById('comakerRegion').addEventListener('change', function() {
        fetchComakerProvinces(this.value);
        document.getElementById('comakerProvince').value = '';
        document.getElementById('comakerCity').value = '';
        document.getElementById('comakerBarangay').value = '';
        document.getElementById('comakerCity').disabled = true;
        document.getElementById('comakerBarangay').disabled = true;
        updateComakerCombinedAddress();
    });
    
    document.getElementById('comakerProvince').addEventListener('change', function() {
        fetchComakerCities(this.value);
        document.getElementById('comakerCity').value = '';
        document.getElementById('comakerBarangay').value = '';
        document.getElementById('comakerBarangay').disabled = true;
        updateComakerCombinedAddress();
    });
    
    document.getElementById('comakerCity').addEventListener('change', function() {
        fetchComakerBarangays(this.value);
        document.getElementById('comakerBarangay').value = '';
        updateComakerCombinedAddress();
    });
    
    document.getElementById('comakerBarangay').addEventListener('change', updateComakerCombinedAddress);
    document.getElementById('comakerStreet').addEventListener('input', updateComakerCombinedAddress);
    document.getElementById('comakerZip').addEventListener('input', updateComakerCombinedAddress);
});
      // Address handling functions for beneficiary
function fetchBeneficiaryRegions() {
    fetch('../customer/address/get_regions.php')
        .then(response => response.json())
        .then(data => {
            const regionSelect = document.getElementById('beneficiaryRegion');
            regionSelect.innerHTML = '<option value="" disabled selected>Select Region</option>';
            
            data.forEach(region => {
                const option = document.createElement('option');
                option.value = region.region_id;  // Changed to match PHP response
                option.textContent = region.region_name;
                regionSelect.appendChild(option);
            });
        })
        .catch(error => console.error('Error fetching regions:', error));
}

function fetchBeneficiaryProvinces(regionId) {
    const provinceSelect = document.getElementById('beneficiaryProvince');
    provinceSelect.innerHTML = '<option value="" disabled selected>Select Province</option>';
    provinceSelect.disabled = true;
    
    if (!regionId) return;
    
    fetch(`../customer/address/get_provinces.php?region_id=${regionId}`)
        .then(response => response.json())
        .then(data => {
            provinceSelect.innerHTML = '<option value="" disabled selected>Select Province</option>';
            
            data.forEach(province => {
                const option = document.createElement('option');
                option.value = province.province_id;  // Changed to match PHP response
                option.textContent = province.province_name;
                provinceSelect.appendChild(option);
            });
            
            provinceSelect.disabled = false;
        })
        .catch(error => console.error('Error fetching provinces:', error));
}

function fetchBeneficiaryCities(provinceId) {
    const citySelect = document.getElementById('beneficiaryCity');
    citySelect.innerHTML = '<option value="" disabled selected>Select City/Municipality</option>';
    citySelect.disabled = true;
    
    if (!provinceId) return;
    
    fetch(`../customer/address/get_cities.php?province_id=${provinceId}`)
        .then(response => response.json())
        .then(data => {
            citySelect.innerHTML = '<option value="" disabled selected>Select City/Municipality</option>';
            
            data.forEach(city => {
                const option = document.createElement('option');
                option.value = city.municipality_id;  // Changed to match PHP response
                option.textContent = city.municipality_name;
                citySelect.appendChild(option);
            });
            
            citySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching cities:', error));
}

function fetchBeneficiaryBarangays(cityId) {
    const barangaySelect = document.getElementById('beneficiaryBarangay');
    barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
    barangaySelect.disabled = true;
    
    if (!cityId) return;
    
    fetch(`../customer/address/get_barangays.php?city_id=${cityId}`)
        .then(response => response.json())
        .then(data => {
            barangaySelect.innerHTML = '<option value="" disabled selected>Select Barangay</option>';
            
            data.forEach(barangay => {
                const option = document.createElement('option');
                option.value = barangay.barangay_id;  // Changed to match PHP response
                option.textContent = barangay.barangay_name;
                barangaySelect.appendChild(option);
            });
            
            barangaySelect.disabled = false;
        })
        .catch(error => console.error('Error fetching barangays:', error));
}

function updateBeneficiaryCombinedAddress() {
    const regionSelect = document.getElementById('beneficiaryRegion');
    const provinceSelect = document.getElementById('beneficiaryProvince');
    const citySelect = document.getElementById('beneficiaryCity');
    const barangaySelect = document.getElementById('beneficiaryBarangay');
    const streetAddress = document.getElementById('beneficiaryStreet').value;
    const zipCode = document.getElementById('beneficiaryZip').value;
    
    const region = regionSelect.options[regionSelect.selectedIndex]?.text || '';
    const province = provinceSelect.options[provinceSelect.selectedIndex]?.text || '';
    const city = citySelect.options[citySelect.selectedIndex]?.text || '';
    const barangay = barangaySelect.options[barangaySelect.selectedIndex]?.text || '';
    
    const addressParts = [];
    if (streetAddress) addressParts.push(streetAddress);
    if (barangay) addressParts.push(barangay);
    if (city) addressParts.push(city);
    if (province) addressParts.push(province);
    if (region) addressParts.push(region);
    if (zipCode) addressParts.push(zipCode);
    
    const combinedAddress = addressParts.join(', ');
    document.getElementById('beneficiaryAddress').value = combinedAddress;
}

// Initialize beneficiary address dropdowns
document.addEventListener('DOMContentLoaded', function() {
    fetchBeneficiaryRegions();
    
    // Set up event listeners for beneficiary cascading dropdowns
    document.getElementById('beneficiaryRegion').addEventListener('change', function() {
        fetchBeneficiaryProvinces(this.value);
        document.getElementById('beneficiaryProvince').value = '';
        document.getElementById('beneficiaryCity').value = '';
        document.getElementById('beneficiaryBarangay').value = '';
        document.getElementById('beneficiaryCity').disabled = true;
        document.getElementById('beneficiaryBarangay').disabled = true;
        updateBeneficiaryCombinedAddress();
    });
    
    document.getElementById('beneficiaryProvince').addEventListener('change', function() {
        fetchBeneficiaryCities(this.value);
        document.getElementById('beneficiaryCity').value = '';
        document.getElementById('beneficiaryBarangay').value = '';
        document.getElementById('beneficiaryBarangay').disabled = true;
        updateBeneficiaryCombinedAddress();
    });
    
    document.getElementById('beneficiaryCity').addEventListener('change', function() {
        fetchBeneficiaryBarangays(this.value);
        document.getElementById('beneficiaryBarangay').value = '';
        updateBeneficiaryCombinedAddress();
    });
    
    document.getElementById('beneficiaryBarangay').addEventListener('change', updateBeneficiaryCombinedAddress);
    document.getElementById('beneficiaryStreet').addEventListener('input', updateBeneficiaryCombinedAddress);
    document.getElementById('beneficiaryZip').addEventListener('input', updateBeneficiaryCombinedAddress);
});
      
   // Initialize data from PHP
let allServices = <?php echo $servicesJson; ?>;
let branches = <?php echo $branchesJson; ?>;
let categories = <?php echo $categoriesJson; ?>;

// Initialize important variables
let selectedBranch = null;
let selectedCategory = null;
let selectedService = null;

// DOM loaded event
document.addEventListener('DOMContentLoaded', function() {
  // Load branches first
  loadBranches();
  
  // Update the back button in services section to go back to branches
  document.querySelector('#services-section button').onclick = goBackToBranches;
  
  // Sidebar toggle functionality
  document.getElementById('hamburger-menu').addEventListener('click', toggleSidebar);

  document.getElementById('traditionalServiceBtn').addEventListener('click', function() {
    document.getElementById('serviceTypeModal').classList.add('hidden');
    openTraditionalCheckout();
  });
  
  // Lifeplan Service Button
  document.getElementById('lifeplanServiceBtn').addEventListener('click', function() {
    document.getElementById('serviceTypeModal').classList.add('hidden');
    openLifeplanCheckout();
  });

  // Initialize payment validations
  initializePaymentValidations();

  const today = new Date().toISOString().split('T')[0];
  
  // Set max date for all date fields to today
  document.getElementById('dateOfBirth').max = today;
  document.getElementById('dateOfDeath').max = today;

  // Date of death change affects date of birth max and date of burial min
  document.getElementById('dateOfDeath').addEventListener('change', function() {
    document.getElementById('dateOfBirth').max = this.value;
    document.getElementById('dateOfBurial').min = this.value;
  });

  // Date of birth change affects date of death min
  document.getElementById('dateOfBirth').addEventListener('change', function() {
    document.getElementById('dateOfDeath').min = this.value;
  });
});

// Initialize payment validations
function initializePaymentValidations() {
  const servicePriceInput = document.getElementById('service-price');
  const totalPriceInput = document.getElementById('totalPrice');
  const amountPaidInput = document.getElementById('amountPaid');

  // Update minimum price display when service is selected
  function updateMinimumPrice() {
    const servicePrice = parseFloat(servicePriceInput.value) || 0;
    const minimumPrice = servicePrice * 0.5;
    document.getElementById('min-price').textContent = `₱${minimumPrice.toFixed(2)}`;
  }

  // Calculate and enforce minimum price when total price changes
  if (totalPriceInput) {
    totalPriceInput.addEventListener('change', function() {
      const servicePrice = parseFloat(servicePriceInput.value) || 0;
      const totalPrice = parseFloat(this.value) || 0;
      const minimumAllowedPrice = servicePrice * 0.5;

      if (totalPrice < minimumAllowedPrice) {
        alert(`Total price cannot be lower than ₱${minimumAllowedPrice.toFixed(2)} (50% of service price)`);
        this.value = minimumAllowedPrice.toFixed(2);
      }
    });
  }

  // Prevent amount paid from exceeding total price
  if (amountPaidInput) {
    amountPaidInput.addEventListener('change', function() {
      const totalPrice = parseFloat(totalPriceInput.value) || 0;
      const amountPaid = parseFloat(this.value) || 0;

      if (amountPaid > totalPrice) {
        alert("Amount paid cannot exceed the total price.");
        this.value = totalPrice.toFixed(2);
      }
    });
  }

  // Call this when a service is added to cart
  updateMinimumPrice();
}


// Function to load branches
function loadBranches() {
  const container = document.getElementById('branches-container');
  container.innerHTML = '';
  
  branches.forEach(branch => {
    const branchCard = document.createElement('div');
    branchCard.className = 'bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 cursor-pointer overflow-hidden';
    
    // Create dynamic image path based on branch_id
    const branchImagePath = `../branch${branch.branch_id}.png`;
    
    branchCard.innerHTML = `
      <div class="h-40 bg-gray-100" style="background-image: url('${branchImagePath}'); background-size: cover; background-position: center;"></div>
      <div class="p-5">
        <div class="text-xl font-bold mb-3 text-sidebar-text">${branch.branch_name}</div>
        <div class="flex justify-between items-center">
          <div class="text-gray-500 text-sm">
            <i class="fas fa-map-marker-alt mr-1"></i> ${branch.address || 'Branch Location'}
          </div>
          <button class="select-branch-btn bg-sidebar-accent text-white py-1 px-3 rounded-md hover:bg-sidebar-accent/90 transition-colors duration-300">
            Select
          </button>
        </div>
      </div>
    `;
    
    const selectBtn = branchCard.querySelector('.select-branch-btn');
    if (selectBtn) {
      selectBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        selectBranch(branch.branch_id, branch.branch_name);
      });
    }
    
    branchCard.addEventListener('click', () => {
      selectBranch(branch.branch_id, branch.branch_name);
    });
    
    container.appendChild(branchCard);
  });
}

// Initialize Add Branch button functionality
document.getElementById('add-branch-btn').addEventListener('click', () => {
  // Show modal or navigate to add branch page
  showAddBranchModal();
});

// Function to show the add branch modal
function showAddBranchModal() {
  // Implementation for showing the modal to add a new branch
  // This could be implemented separately or use a modal library
  console.log("Add branch modal triggered");
  
  // Example implementation:
  const modal = document.getElementById('add-branch-modal');
  if (modal) {
    modal.classList.remove('hidden');
  } else {
    alert("Add branch functionality will be implemented here");
  }
}

// Function to select a branch
function selectBranch(branchId, branchName) {
  selectedBranch = branchId;
  document.getElementById('services-branch-name').textContent = branchName;
  
  // Skip category selection and go directly to services
  document.getElementById('branch-selection').classList.add('hidden');
  document.getElementById('services-section').classList.remove('hidden');
  
  // Load all services for this branch
  loadServices();
}

// Function to go back to branch selection
function goBackToBranches() {
  document.getElementById('branch-selection').classList.remove('hidden');
  document.getElementById('services-section').classList.add('hidden');
  selectedBranch = null;
}

// Function to load services for the selected branch and category
function loadServices() {
  const container = document.getElementById('services-container');
  container.innerHTML = '';

  // Filter services that match the selected branch (no category filter)
  const filteredServices = allServices.filter(service => 
    service.branch_id == selectedBranch
  );

  if (filteredServices.length === 0) {
    container.innerHTML = `
      <div class="col-span-full flex flex-col items-center justify-center py-16 text-gray-500">
        <i class="fas fa-store-slash text-4xl mb-4 text-gray-400"></i>
        <p class="text-lg">No services available for this branch.</p>
      </div>`;
    return;
  }

  // Group services by category for better organization
  const servicesByCategory = {};
  filteredServices.forEach(service => {
    const categoryName = categories.find(cat => 
      cat.service_categoryID == service.service_categoryID
    ).service_category_name;
    
    if (!servicesByCategory[categoryName]) {
      servicesByCategory[categoryName] = [];
    }
    servicesByCategory[categoryName].push(service);
  });

  // Add event listener for real-time search
const searchInput = document.getElementById('service-search');
searchInput.addEventListener('input', function() {
    // Validate input
    validateSearchInput(this);
    
    // Filter services in real-time
    filterServices(this.value.toLowerCase());
});

// Function to validate search input
function validateSearchInput(input) {
    const errorElement = document.getElementById('search-error');
    let value = input.value;
    
    // Check if first character is space
    if (value.length > 0 && value.charAt(0) === ' ') {
        errorElement.classList.remove('hidden');
        input.value = value.trim();
        return;
    }
    
    // Check for consecutive spaces
    if (value.includes('  ')) {
        errorElement.classList.remove('hidden');
        input.value = value.replace(/\s+/g, ' ');
        return;
    }
    
    errorElement.classList.add('hidden');
    
    // Auto-capitalize first letter
    if (value.length === 1) {
        input.value = value.charAt(0).toUpperCase() + value.slice(1);
    }
}

function filterServices(searchText) {
    const serviceCards = document.querySelectorAll('.service-card');
    let visibleCount = 0;
    
    serviceCards.forEach(card => {
        const serviceName = card.dataset.name;
        const categoryName = card.dataset.category;
        const serviceDescription = card.dataset.description || '';
        
        // Search in name, category, and description
        if (serviceName.includes(searchText) || 
            categoryName.includes(searchText) || 
            serviceDescription.toLowerCase().includes(searchText)) {
            card.style.display = 'flex';
            visibleCount++;
        } else {
            card.style.display = 'none';
        }
    });

    // Show or hide category headers based on visible services
    const categoryHeaders = document.querySelectorAll('.col-span-full');
    categoryHeaders.forEach(header => {
        const categoryName = header.querySelector('h3')?.textContent.toLowerCase();
        const hasVisibleServices = Array.from(serviceCards).some(card => 
            card.dataset.category === categoryName && card.style.display !== 'none'
        );
        
        header.style.display = hasVisibleServices ? 'flex' : 'none';
    });

    // Show no results message if needed
    const noResultsMsg = document.getElementById('no-results-message');
    if (visibleCount === 0 && searchText) {
        if (!noResultsMsg) {
            const message = document.createElement('div');
            message.id = 'no-results-message';
            message.className = 'col-span-full text-center py-12 text-gray-500';
            message.innerHTML = `
                <i class="fas fa-search text-4xl mb-3 text-gray-300"></i>
                <p class="text-lg">No services found matching "${searchText}"</p>
                <button id="clear-search" class="mt-4 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-all">
                    Clear Search
                </button>
            `;
            container.appendChild(message);
            document.getElementById('clear-search').addEventListener('click', () => {
                searchInput.value = '';
                filterServices('');
            });
        }
    } else if (noResultsMsg) {
        noResultsMsg.remove();
    }
}

// Function to filter and sort services
function filterAndSortServices() {
  const searchText = document.getElementById('service-search').value.toLowerCase();
  const priceFilter = document.getElementById('price-filter').value;
  const priceSort = document.getElementById('price-sort').value;
  
  const serviceCards = document.querySelectorAll('.service-card');
  let visibleCount = 0;
  
  // First filter by price range
  serviceCards.forEach(card => {
    const price = parseFloat(card.dataset.price);
    let priceMatch = true;
    
    if (priceFilter) {
      if (priceFilter === '1-100000') {
        priceMatch = price >= 1 && price <= 100000;
      } else if (priceFilter === '100001-300000') {
        priceMatch = price >= 100001 && price <= 300000;
      } else if (priceFilter === '300001-500000') {
        priceMatch = price >= 300001 && price <= 500000;
      } else if (priceFilter === '500001-700000') {
        priceMatch = price >= 500001 && price <= 700000;
      } else if (priceFilter === '700001+') {
        priceMatch = price >= 700001;
      }
    }
    
    // Then filter by search text if price matches
    if (priceMatch) {
      const serviceName = card.dataset.name;
      const categoryName = card.dataset.category;
      const serviceDescription = card.dataset.description || '';
      
      if (!searchText || 
          serviceName.includes(searchText) || 
          categoryName.includes(searchText) || 
          serviceDescription.includes(searchText)) {
        card.style.display = 'flex';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    } else {
      card.style.display = 'none';
    }
  });

  // Sort services if a sort option is selected
  if (priceSort) {
    const container = document.getElementById('services-container');
    const cards = Array.from(container.querySelectorAll('.service-card[style*="flex"]'));
    
    cards.sort((a, b) => {
      const priceA = parseFloat(a.dataset.price);
      const priceB = parseFloat(b.dataset.price);
      
      if (priceSort === 'low-high') {
        return priceA - priceB;
      } else {
        return priceB - priceA;
      }
    });
    
    // Re-append cards in sorted order
    cards.forEach(card => {
      container.appendChild(card);
    });
  }

  // Show or hide category headers based on visible services
  const categoryHeaders = document.querySelectorAll('.col-span-full');
  categoryHeaders.forEach(header => {
    const categoryName = header.querySelector('h3')?.textContent.toLowerCase();
    const hasVisibleServices = Array.from(serviceCards).some(card => 
      card.dataset.category === categoryName && card.style.display !== 'none'
    );
    
    header.style.display = hasVisibleServices ? 'flex' : 'none';
  });

  // Show no results message if needed
  const noResultsMsg = document.getElementById('no-results-message');
  if (visibleCount === 0 && (searchText || priceFilter)) {
    if (!noResultsMsg) {
      const container = document.getElementById('services-container');
      const message = document.createElement('div');
      message.id = 'no-results-message';
      message.className = 'col-span-full text-center py-12 text-gray-500';
      message.innerHTML = `
        <i class="fas fa-search text-4xl mb-3 text-gray-300"></i>
        <p class="text-lg">No services found matching your criteria</p>
        <button id="clear-filters" class="mt-4 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-all">
          Clear Filters
        </button>
      `;
      container.appendChild(message);
      document.getElementById('clear-filters').addEventListener('click', () => {
        document.getElementById('service-search').value = '';
        document.getElementById('price-filter').value = '';
        document.getElementById('price-sort').value = '';
        filterAndSortServices();
      });
    }
  } else if (noResultsMsg) {
    noResultsMsg.remove();
  }
}

// Add event listeners for the new filter controls
document.getElementById('price-filter').addEventListener('change', filterAndSortServices);
document.getElementById('price-sort').addEventListener('change', filterAndSortServices);

  // Create sections for each category
  for (const [categoryName, services] of Object.entries(servicesByCategory)) {
    // Add category heading with count
    const categoryHeader = document.createElement('div');
    categoryHeader.className = 'col-span-full mt-8 mb-4 flex items-center justify-between';
    categoryHeader.innerHTML = `
      <div class="flex items-center gap-2">
        <h3 class="text-xl font-bold text-sidebar-text">${categoryName}</h3>
        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">${services.length} ${services.length === 1 ? 'Service' : 'Services'}</span>
      </div>
    `;
    container.appendChild(categoryHeader);

    // Add services for this category
    services.forEach(service => {
      const serviceCard = document.createElement('div');
      serviceCard.className = 'bg-white rounded-lg overflow-hidden shadow-sidebar border border-sidebar-border hover:shadow-card hover:border-sidebar-accent transition-all duration-300 cursor-pointer service-card flex flex-col';
      serviceCard.dataset.name = service.service_name.toLowerCase();
      serviceCard.dataset.category = categoryName.toLowerCase();
      serviceCard.dataset.description = service.description ? service.description.toLowerCase() : '';
      serviceCard.dataset.price = service.selling_price;
      serviceCard.onclick = () => showServiceDetails(service);

      const imageUrl = service.image_url && service.image_url.trim() !== '' ? 
  'servicesManagement/' + service.image_url : '';

      // Process inclusions as a list
      let inclusionsHtml = '';
      if (service.inclusions && service.inclusions.trim() !== '') {
        // Assuming inclusions might be comma separated
        const inclusionsArray = service.inclusions.split(',').map(item => item.trim()).filter(item => item !== '');
        
        // Limit to 3 items for preview
        const displayItems = inclusionsArray.slice(0, 3);
        const remainingCount = inclusionsArray.length - displayItems.length;
        
        if (displayItems.length > 0) {
          inclusionsHtml = `
            <div class="text-gray-500 text-sm mb-4">
              <div class="font-medium text-gray-600 mb-1">Inclusions:</div>
              <ul class="list-disc pl-5 space-y-1">
                ${displayItems.map(item => `<li>${item}</li>`).join('')}
                ${remainingCount > 0 ? `<li class="text-gray-400">+${remainingCount} more...</li>` : ''}
              </ul>
            </div>
          `;
        } else {
          inclusionsHtml = '<div class="text-gray-500 text-sm mb-4">No inclusions specified</div>';
        }
      } else {
        inclusionsHtml = '<div class="text-gray-500 text-sm mb-4">No inclusions specified</div>';
      }

      serviceCard.innerHTML = `
        <div class="h-48 ${imageUrl ? 'bg-center bg-cover bg-no-repeat' : 'bg-gray-100'} flex items-center justify-center" 
             ${imageUrl ? `style="background-image: url('${imageUrl}');"` : ''}>
          ${!imageUrl ? '<i class="fas fa-image text-4xl text-gray-300"></i>' : ''}
          <div class="w-full h-full bg-gradient-to-t from-black/30 to-transparent flex items-end">
            <div class="p-3 text-white">
              <span class="text-xs font-medium bg-sidebar-accent/80 px-2 py-1 rounded-full">${categoryName}</span>
            </div>
          </div>
        </div>
        <div class="p-5 flex-grow flex flex-col">
          <div class="text-lg font-bold mb-2 text-sidebar-text">${service.service_name}</div>
          ${service.flower_design ? `<div class="text-gray-600 text-sm mb-2"><i class="fas fa-leaf text-gray-400 mr-2"></i>${service.flower_design}</div>` : ''}
          ${inclusionsHtml}
          <div class="text-lg font-bold text-sidebar-accent mt-auto">₱${parseFloat(service.selling_price).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
        </div>
        <div class="px-5 pb-5 flex justify-end">
          <button class="text-white bg-sidebar-accent px-4 py-2 rounded-lg hover:bg-opacity-90 transition-all duration-300"> Select
          </button>
        </div>
      `;
      container.appendChild(serviceCard);
    });
  }

  // Function to filter services based on search input
  function filterServices(searchText) {
    const serviceCards = document.querySelectorAll('.service-card');
    let visibleCount = 0;
    
    serviceCards.forEach(card => {
      const serviceName = card.dataset.name;
      const categoryName = card.dataset.category;
      
      if (serviceName.includes(searchText) || categoryName.includes(searchText)) {
        card.style.display = 'flex';
        visibleCount++;
      } else {
        card.style.display = 'none';
      }
    });

    // Show or hide category headers based on visible services
    const categoryHeaders = document.querySelectorAll('.col-span-full');
    categoryHeaders.forEach(header => {
      const categoryName = header.querySelector('h3')?.textContent.toLowerCase();
      const hasVisibleServices = Array.from(serviceCards).some(card => 
        card.dataset.category === categoryName && card.style.display !== 'none'
      );
      
      header.style.display = hasVisibleServices ? 'flex' : 'none';
    });

    // Show no results message if needed
    const noResultsMsg = document.getElementById('no-results-message');
    if (visibleCount === 0 && searchText) {
      if (!noResultsMsg) {
        const message = document.createElement('div');
        message.id = 'no-results-message';
        message.className = 'col-span-full text-center py-12 text-gray-500';
        message.innerHTML = `
          <i class="fas fa-search text-4xl mb-3 text-gray-300"></i>
          <p class="text-lg">No services found matching "${searchText}"</p>
          <button id="clear-search" class="mt-4 px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-all">
            Clear Search
          </button>
        `;
        container.appendChild(message);
        document.getElementById('clear-search').addEventListener('click', () => {
          document.getElementById('service-search').addEventListener('input', function() {
              validateSearchInput(this);
              filterAndSortServices();
            });
        });
      }
    } else if (noResultsMsg) {
      noResultsMsg.remove();
    }
  }
}

// Function to show service details in modal
function showServiceDetails(service) {
  console.log("Showing service details:", service);
  selectedService = service;
  document.getElementById('modal-title').textContent = service.service_name;
  document.getElementById('service-price').value = service.selling_price;
  
  // Format inclusions as a list if it contains commas
  let inclusionsDisplay = service.inclusions;
  if (service.inclusions && service.inclusions.includes(',')) {
    const inclusionsList = service.inclusions.split(',').map(item => `<li class="mb-1">- ${item.trim()}</li>`).join('');
    inclusionsDisplay = `<ul class="list-none mt-2">${inclusionsList}</ul>`;
  }

  // Use a default image if none provided or if the URL is empty
const imageUrl = service.image_url && service.image_url.trim() !== '' ? 
  'servicesManagement/' + service.image_url : '';

  document.getElementById('modal-content').innerHTML = `
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div class="${imageUrl ? 'h-64 bg-center bg-cover bg-no-repeat rounded-lg' : 'h-64 bg-gray-100 flex items-center justify-center rounded-lg'}" 
             ${imageUrl ? `style="background-image: url('${imageUrl}');"` : ''}>
          ${!imageUrl ? '<i class="fas fa-image text-5xl text-gray-300"></i>' : ''}
        </div>
      <div>
        <div class="text-lg font-bold mb-2.5 text-sidebar-text">${service.service_name}</div>
        <div class="flex items-center mb-4">
          <span class="text-gray-500 text-sm mr-3"><i class="fas fa-map-marker-alt mr-1"></i> ${service.branch_name}</span>
          <span class="text-gray-500 text-sm"><i class="fas fa-tag mr-1"></i> ${service.service_category_name}</span>
        </div>
        <div class="text-lg font-bold text-sidebar-accent mb-4">₱${parseFloat(service.selling_price).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>

        <!-- Add the description here -->
        ${service.description ? `
          <div class="text-gray-700 text-sm mb-3">
            <strong>Description:</strong>
            <p class="mt-1">${service.description}</p>
          </div>
        ` : ''}

        <div class="text-gray-700 text-sm mb-3">
          <strong>Flower Replacements:</strong>
          ${service.flower_design}
        </div>
        <div class="text-gray-700 text-sm mb-3">
          <strong>Inclusions:</strong>
          ${inclusionsDisplay}
        </div>
      </div>
    </div>
  `;
  document.getElementById('package-modal').classList.remove('hidden');
}

// Function to close the package modal
function closePackageModal() {
  document.getElementById('package-modal').classList.add('hidden');
  selectedService = null;
}

function closeServiceTypeModal() {
  const modal = document.getElementById('serviceTypeModal');
  if (modal) {
    modal.classList.add('hidden');
  }
}

// Function to handle adding to cart and immediately proceed to checkout
// Modify the addToCart function to show service type selection first
function addToCart() {
  console.log("Selected service in addToCart:", selectedService);
  if (selectedService) {
    // Store the selected service in the service type modal's data attributes
    const serviceTypeModal = document.getElementById('serviceTypeModal');
    serviceTypeModal.dataset.serviceId = selectedService.service_id;
    serviceTypeModal.dataset.servicePrice = selectedService.selling_price;
    serviceTypeModal.dataset.branchId = selectedService.branch_id;
    
    // Close package modal and show service type selection
    closePackageModal();
    document.getElementById('serviceTypeModal').classList.remove('hidden');
  }
}

// Function to open traditional checkout
function openTraditionalCheckout() {
  console.log("Opening traditional checkout with service:", document.getElementById('serviceTypeModal').dataset);
  const serviceTypeModal = document.getElementById('serviceTypeModal');
  const serviceId = serviceTypeModal.dataset.serviceId;
  const servicePrice = serviceTypeModal.dataset.servicePrice;
  const branchId = serviceTypeModal.dataset.branchId;

  // Set the service details in the form
  document.getElementById('service-id').value = serviceId;
  document.getElementById('service-price').value = servicePrice;
  document.getElementById('branch-id').value = branchId;
  
  // Update the total price in the checkout form
  document.getElementById('totalPrice').value = servicePrice;
  document.getElementById('footer-total-price').textContent = 
    `₱${parseFloat(servicePrice).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
  
  // Update minimum price display
  const minimumPrice = parseFloat(servicePrice) * 0.5;
  document.getElementById('min-price').textContent = `₱${minimumPrice.toFixed(2)}`;
  
  // Open checkout modal
  document.getElementById('checkoutModal').classList.remove('hidden');
}

function setupLifeplanPaymentTerms() {
  const paymentTermSelect = document.getElementById('lp-paymentTerm');
  const totalPriceInput = document.getElementById('lp-totalPrice');
  const monthlyPaymentDiv = document.getElementById('lp-monthlyPayment');
  const monthlyPaymentAmount = monthlyPaymentDiv.querySelector('span');
  
  // Function to calculate monthly payment
  function calculateMonthlyPayment() {
    const servicePrice = parseFloat(document.getElementById('lp-service-price').value) || 0;
    const termYears = parseInt(paymentTermSelect.value) || 1;
    
    if (termYears === 1) {
      // Full payment
      monthlyPaymentDiv.classList.add('hidden');
      totalPriceInput.value = servicePrice.toFixed(2);
      document.getElementById('lp-footer-total-price').textContent = 
        `₱${servicePrice.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    } else {
      // Installment
      const termMonths = termYears * 12;
      const monthlyPayment = servicePrice / termMonths;
      
      monthlyPaymentAmount.textContent = 
        `₱${monthlyPayment.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
      monthlyPaymentDiv.classList.remove('hidden');
      
      // Update total price (can be overridden by user)
      totalPriceInput.value = servicePrice.toFixed(2);
      document.getElementById('lp-footer-total-price').textContent = 
        `₱${servicePrice.toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }
    
    // Update minimum price
    const minimumPrice = servicePrice * 0.5;
    document.getElementById('lp-min-price').textContent = `₱${minimumPrice.toFixed(2)}`;
  }
  
  // Calculate when term changes
  paymentTermSelect.addEventListener('change', calculateMonthlyPayment);
  
  // Also calculate when service price changes
  document.getElementById('lp-service-price').addEventListener('change', calculateMonthlyPayment);
  
  // Initial calculation
  calculateMonthlyPayment();
}

// Function to open lifeplan checkout
function openLifeplanCheckout() {
  const serviceTypeModal = document.getElementById('serviceTypeModal');
  const serviceId = serviceTypeModal.dataset.serviceId;
  const servicePrice = serviceTypeModal.dataset.servicePrice;
  const branchId = serviceTypeModal.dataset.branchId;

  // Set the service details in the lifeplan form
  document.getElementById('lp-service-id').value = serviceId;
  document.getElementById('lp-service-price').value = servicePrice;
  document.getElementById('lp-branch-id').value = branchId;
  
  // Update the total price in the lifeplan checkout form
  document.getElementById('lp-totalPrice').value = servicePrice;
  document.getElementById('lp-footer-total-price').textContent = 
    `₱${parseFloat(servicePrice).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
  
  // Update minimum price display
  const minimumPrice = parseFloat(servicePrice) * 0.5;
  document.getElementById('lp-min-price').textContent = `₱${minimumPrice.toFixed(2)}`;
  
  setupLifeplanPaymentTerms();
  // Open lifeplan checkout modal
  document.getElementById('lifeplanCheckoutModal').classList.remove('hidden');
}

// Function to close lifeplan checkout modal
function closeLifeplanCheckoutModal() {
  document.getElementById('lifeplanCheckoutModal').classList.add('hidden');
}

// Function to confirm lifeplan checkout
function confirmLifeplanCheckout() {
  const form = document.getElementById('lifeplanCheckoutForm');
  const submitBtn = document.getElementById('lp-confirm-btn');
  let originalBtnText = submitBtn.innerHTML;
  
  // Set loading state
  submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
  submitBtn.disabled = true;

  // Validate required fields - ADDED CO-MAKER FIELDS
  const requiredFields = [
    'lp-clientFirstName', 'lp-clientLastName', 'lp-clientPhone',
    'beneficiaryFirstName', 'beneficiaryLastName', 'beneficiaryRelationship',
    'comakerFirstName', 'comakerLastName', 'comakerOccupation',
    'comakerIdType', 'comakerIdNumber'
  ];

  for (const fieldId of requiredFields) {
    const field = document.getElementById(fieldId);
    if (!field || !field.value.trim()) {
      submitBtn.innerHTML = originalBtnText;
      submitBtn.disabled = false;
      alert(`Please fill in ${field.labels[0]?.textContent || fieldId}`);
      if (field) field.focus();
      return;
    }
  }

  // Validate ID image
  const idImageInput = document.getElementById('comakerIdImage');
  if (!idImageInput.files || !idImageInput.files[0]) {
    submitBtn.innerHTML = originalBtnText;
    submitBtn.disabled = false;
    alert('Please upload a valid ID image for the co-maker');
    return;
  }

  // Prepare data for submission
  const formData = new FormData(form);

  let userId = <?php echo json_encode($userId); ?>;
  
  // Add additional fields that aren't in the form
  formData.set('service_id', document.getElementById('lp-service-id').value);
  formData.set('branch_id', document.getElementById('lp-branch-id').value);
  formData.set('sold_by', userId); // Example admin ID
  formData.set('withCremation', document.getElementById('lp-withCremation').checked ? 'on' : 'off');
  
  // Calculate balance
  const totalPrice = parseFloat(document.getElementById('lp-totalPrice').value) || 0;
  const amountPaid = parseFloat(parseFloat(document.getElementById('lp-amountPaid').value).toFixed(2)) || 0;
  const balance = totalPrice - amountPaid;
  
  formData.set('balance', balance.toFixed(2));
  formData.set('payment_status', balance > 0 ? 'With Balance' : 'Fully Paid');

  // Add the ID image file
  formData.append('comakerIdImage', idImageInput.files[0]);

  console.log('Submitting the following form data:');
  for (let [key, value] of formData.entries()) {
    console.log(`${key}: ${value}`);
  }

  fetch('posFunctions/process_lifeplan_checkout.php', {
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
      alert('Transaction successfully saved!');
      form.reset();
      closeLifeplanCheckoutModal();
    } else {
      throw new Error(data.message || 'Error processing your request.');
    }
  })
  .catch(error => {
    console.error('Error:', error);
    alert(error.message || 'An error occurred while saving the data.');
  })
  .finally(() => {
    submitBtn.innerHTML = originalBtnText;
    submitBtn.disabled = false;
  });
}

function closeCheckoutModal() {
  document.getElementById('checkoutModal').classList.add('hidden');
}

function confirmCheckout() {
  // Get all form inputs
  const form = document.getElementById('checkoutForm');
  const formData = new FormData(form);

  //address
  const address = document.getElementById('deceasedAddress').value;
  formData.append('deceasedAddress', address);

  const withCremation = document.getElementById('withCremation').checked;
  formData.append('withCremation', withCremation ? 'on' : 'off');

  // Validate email format
  const email = document.getElementById('clientEmail').value;
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    alert("Please enter a valid email address.");
    document.getElementById('clientEmail').focus();
    return;
  }

  // Validate Philippine phone number (must start with 09 and be 11 digits)
  const phone = document.getElementById('clientPhone').value;
  if (!phone) {
    alert("Phone number is required.");
    document.getElementById('clientPhone').focus();
    return;
  }
  
  if (!/^09\d{9}$/.test(phone)) {
    alert("Please enter a valid Philippine phone number starting with 09 and 11 digits long (e.g., 09123456789).");
    document.getElementById('clientPhone').focus();
    return;
  }

  // Date validations
  const dateOfBirth = document.getElementById('dateOfBirth').value;
  const dateOfDeath = document.getElementById('dateOfDeath').value;
  const dateOfBurial = document.getElementById('dateOfBurial').value;
  const today = new Date().toISOString().split('T')[0];

  // Validate date of birth (if provided)
  if (dateOfBirth) {
    if (dateOfBirth > today) {
      alert("Date of birth cannot be in the future.");
      document.getElementById('dateOfBirth').focus();
      return;
    }
    if (dateOfDeath && dateOfBirth > dateOfDeath) {
      alert("Date of birth cannot be after date of death.");
      document.getElementById('dateOfBirth').focus();
      return;
    }
  }

  // Validate date of death (required)
  if (!dateOfDeath) {
    alert("Date of death is required.");
    document.getElementById('dateOfDeath').focus();
    return;
  }
  if (dateOfDeath > today) {
    alert("Date of death cannot be in the future.");
    document.getElementById('dateOfDeath').focus();
    return;
  }

  // Validate date of burial (if provided)
  if (dateOfBurial) {
    if (dateOfBurial < dateOfDeath) {
      alert("Date of burial cannot be before date of death.");
      document.getElementById('dateOfBurial').focus();
      return;
    }
  }

  // Payment validations
  const servicePrice = parseFloat(document.getElementById('service-price').value);
  const totalPrice = parseFloat(document.getElementById('totalPrice').value);
  const amountPaid = parseFloat(document.getElementById('amountPaid').value);

  // Check if values are valid numbers
  if (isNaN(servicePrice) || servicePrice <= 0) {
    alert("Service price must be a valid positive number.");
    document.getElementById('service-price').focus();
    return;
  }

  if (isNaN(totalPrice) || totalPrice <= 0) {
    alert("Total price must be a valid positive number.");
    document.getElementById('totalPrice').focus();
    return;
  }

  if (isNaN(amountPaid) || amountPaid < 0) {
    alert("Amount paid must be a valid non-negative number.");
    document.getElementById('amountPaid').focus();
    return;
  }

  // Validate total price is at least 50% of service price
  const minimumAllowedPrice = servicePrice * 0.5;
  if (totalPrice < minimumAllowedPrice) {
    alert(`Total price cannot be lower than 50% of the service price (₱${minimumAllowedPrice.toFixed(2)}).`);
    document.getElementById('totalPrice').focus();
    return;
  }

  // Validate amount paid doesn't exceed total price
  if (amountPaid > totalPrice) {
    alert("Amount paid cannot exceed the total price.");
    document.getElementById('amountPaid').focus();
    return;
  }

  let userId = <?php echo json_encode($userId); ?>;

  // Add service data (single service now instead of cart)
  formData.append('service_id', document.getElementById('service-id').value);
  formData.append('sold_by', userId); 
  formData.append('status', 'Pending');
  
  // Calculate balance
  const balance = totalPrice - amountPaid;
  
  formData.append('balance', balance.toFixed(2));
  formData.append('payment_status', balance > 0 ? 'With Balance' : 'Fully Paid');

  // Show confirmation dialog before submitting
  if (confirm("Are you sure you want to proceed with this order?")) {
    // Send data to server using AJAX
    fetch('posFunctions/process_checkout.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        document.getElementById('checkoutModal').classList.add('hidden');
        showConfirmation(data.orderId);
      } else {
        alert('Error: ' + (data.message || 'Failed to process checkout'));
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while processing your order. Please try again.');
    });
  }
}

// New function to show confirmation with order ID
function showConfirmation(orderId) {
  document.getElementById('order-id').textContent = orderId;
  document.getElementById('confirmation-modal').classList.remove('hidden');
}

// Function to close confirmation modal
function closeConfirmationModal() {
  document.getElementById('confirmation-modal').classList.add('hidden');
}

// Function to start a new order
function startNewOrder() {
  
  
  // Close all modals
  
  document.getElementById('confirmation-modal').classList.add('hidden');
  
  // Reset the form (optional)
  document.getElementById('checkoutForm').reset();
  
  // Go back to branch selection
  goBackToBranches();
}
  </script>
  <script src = 'tailwind.js'></script>
  
</body>
</html>