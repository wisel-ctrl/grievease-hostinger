<?php
session_start();

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


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Point-Of-Sale - GrievEase</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>

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
      <div class="flex space-x-3">
        <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
          <i class="fas fa-bell"></i>
        </button>
        <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
          <i class="fas fa-cog"></i>
        </button>
      </div>
    </div>

    <!-- Branch Selection Section -->
    <div id="branch-selection" class="mb-8">
      <h2 class="mb-5 text-gray-600 text-lg">Select a Branch Location</h2>
      <div id="branches-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <!-- Branches will be dynamically added here based on database data -->
      </div>
    </div>

    <!-- Category Selection Section (Initially hidden) -->
    <div id="category-selection" class="mb-8 hidden">
      <div class="flex items-center mb-5">
        <button onclick="goBackToBranches()" class="mr-3 p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
          <i class="fas fa-arrow-left"></i>
        </button>
        <h2 class="text-gray-600 text-lg">Select a Service Category for <span id="selected-branch-name" class="font-semibold text-sidebar-accent"></span></h2>
      </div>
      <div id="categories-container" class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <!-- Categories will be dynamically added here based on database data -->
      </div>
    </div>

    <!-- Services Section (Initially hidden) -->
    <div id="services-section" class="mb-8 hidden">
      <div class="flex items-center mb-5">
        <button onclick="goBackToCategories()" class="mr-3 p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
          <i class="fas fa-arrow-left"></i>
        </button>
        <h2 class="text-gray-600 text-lg">
          <span id="selected-category-name" class="font-semibold text-sidebar-accent"></span> Services at 
          <span id="services-branch-name" class="font-semibold text-sidebar-accent"></span>
        </h2>
      </div>
      
      <div id="services-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        <!-- Services will be dynamically added here based on branch and category selection -->
      </div>
    </div>


  </div>

    <!-- Package Modal -->
    <div id="package-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-white rounded-lg w-full max-w-3xl overflow-hidden">
        <div class="p-5 border-b border-sidebar-border flex justify-between items-center">
          <h3 class="text-xl font-bold text-sidebar-text" id="modal-title">Package Details</h3>
          <button onclick="closePackageModal()" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <div class="p-5" id="modal-content">
          <!-- Content will be dynamically added here -->
        </div>
        <div class="p-5 border-t border-sidebar-border flex justify-end space-x-3">
          <button onclick="closePackageModal()" class="px-4 py-2 border border-sidebar-border text-sidebar-text rounded font-semibold text-sm hover:bg-sidebar-hover transition-all duration-300">Cancel</button>
          <button onclick="addToCart()" class="px-4 py-2 bg-sidebar-accent text-white rounded font-semibold text-sm hover:bg-darkgold transition-all duration-300">Buy Now</button>
        </div>
      </div>
    </div>

    <!-- Checkout Modal -->
    <div class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="checkoutModal">
  <div class="bg-white rounded-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header (Unchanged) -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 id="modal-package-title" class="text-xl font-bold text-white">Complete Your Order</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeCheckoutModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="p-6">
      <form id="checkoutForm" class="space-y-8">
        <input type="hidden" id="service-id" name="service_id" value="">
        <input type="hidden" id="service-price" name="service_price">
        <!-- In your checkout modal form, add this hidden input -->
        <input type="hidden" id="branch-id" name="branch_id" value="">

        <!-- Client Information Section -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
            Client Information
          </h4>
          <div class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label for="clientFirstName" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                <input type="text" id="clientFirstName" name="clientFirstName" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
              <div>
                <label for="clientMiddleName" class="block text-sm font-medium text-gray-700 mb-1">Middle Name <span class="text-xs text-gray-500">(Optional)</span></label>
                <input type="text" id="clientMiddleName" name="clientMiddleName" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
              <div>
                <label for="clientLastName" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                <input type="text" id="clientLastName" name="clientLastName" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
              <div>
                <label for="clientSuffix" class="block text-sm font-medium text-gray-700 mb-1">Suffix <span class="text-xs text-gray-500">(Optional)</span></label>
                <input type="text" id="clientSuffix" name="clientSuffix" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="clientPhone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input type="tel" id="clientPhone" name="clientPhone" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
              <div>
                <label for="clientEmail" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-xs text-gray-500">(Optional)</span></label>
                <input type="email" id="clientEmail" name="clientEmail" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
            </div>
          </div>
        </div>

        <!-- Deceased Information Section -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
              <circle cx="9" cy="7" r="4"></circle>
              <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
              <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
            </svg>
            Deceased Information
          </h4>
          <div class="space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label for="deceasedFirstName" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                <input type="text" id="deceasedFirstName" name="deceasedFirstName" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
              <div>
                <label for="deceasedMiddleName" class="block text-sm font-medium text-gray-700 mb-1">Middle Name <span class="text-xs text-gray-500">(Optional)</span></label>
                <input type="text" id="deceasedMiddleName" name="deceasedMiddleName" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
              <div>
                <label for="deceasedLastName" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                <input type="text" id="deceasedLastName" name="deceasedLastName" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
              <div>
                <label for="deceasedSuffix" class="block text-sm font-medium text-gray-700 mb-1">Suffix <span class="text-xs text-gray-500">(Optional)</span></label>
                <input type="text" id="deceasedSuffix" name="deceasedSuffix" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
            </div>
            
            <!-- In your checkout modal form -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label for="dateOfBirth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-xs text-gray-500">(Optional)</span></label>
                <input type="date" id="dateOfBirth" name="dateOfBirth" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
              <div>
                <label for="dateOfDeath" class="block text-sm font-medium text-gray-700 mb-1">Date of Death</label>
                <input type="date" id="dateOfDeath" name="dateOfDeath" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
              <div>
              <label for="dateOfBurial" class="block text-sm font-medium text-gray-700 mb-1">Date of Burial/Cremation <span class="text-xs text-gray-500">(Optional)</span></label>
              <input type="date" id="dateOfBurial" name="dateOfBurial" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
            </div>
            <div>
              <label for="deathCertificate" class="block text-sm font-medium text-gray-700 mb-1">Death Certificate <span class="text-xs text-gray-500">(If available)</span></label>
              <div class="relative">
                <input type="file" id="deathCertificate" name="deathCertificate" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-20">
                <div class="w-full p-3 border border-dashed border-gray-300 rounded-lg bg-gray-50 text-gray-500 text-sm flex items-center justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                  </svg>
                  Choose file or drag here
                </div>
              </div>
            </div>
            <div>
              <label for="deceasedAddress" class="block text-sm font-medium text-gray-700 mb-1">Address of the Deceased</label>
              <textarea id="deceasedAddress" name="deceasedAddress" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" rows="2"></textarea>
            </div>
          </div>
        </div>
  
        <!-- Payment Information (Unchanged) -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
              <line x1="1" y1="10" x2="23" y2="10"></line>
            </svg>
            Payment Information
          </h4>
          <div class="space-y-5">
            <div>
              <label for="paymentMethod" class="block text-sm font-medium text-gray-700 mb-1">Method of Payment</label>
              <select id="paymentMethod" name="paymentMethod" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
                <option value="" disabled selected>Select payment method</option>
                <option value="Cash">Cash</option>
                <option value="G-Cash">G-Cash</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Insurance">Insurance</option>
              </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="totalPrice" class="block text-sm font-medium text-gray-700 mb-1">
                Total Price 
                <span class="text-xs text-gray-500">(Minimum: <span id="min-price">₱0.00</span>)</span>
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">₱</span>
                </div>
                <input type="number" id="totalPrice" name="totalPrice" class="w-full pl-8 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
            </div>
              <div>
                <label for="amountPaid" class="block text-sm font-medium text-gray-700 mb-1">Amount Paid</label>
                <div class="relative">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <span class="text-gray-500">₱</span>
                  </div>
                  <input type="number" id="amountPaid" name="amountPaid" required class="w-full pl-8 p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Cremation Checklist Section -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
          <h4 class="text-lg font-bold text-gray-800 mb-4 pb-2 border-b border-gray-200 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
            </svg>
            Additional Services
          </h4>
          <div class="space-y-3">
            <label class="flex items-center space-x-3">
              <input type="checkbox" name="withCremation" class="form-checkbox h-5 w-5 text-sidebar-accent rounded border-gray-300 focus:ring-sidebar-accent">
              <span class="text-gray-700 font-medium">With Cremation</span>
            </label>
            <p class="text-sm text-gray-500 ml-8">Check this box if the service includes cremation</p>
          </div>
        </div>
      </form>
    </div>

    <!-- Modal Footer (Unchanged) -->
    <div class="p-6 flex justify-between items-center border-t border-gray-200 sticky bottom-0 bg-white">
      <div class="text-gray-600">
        <p class="font-medium">Order Total: <span class="text-xl font-bold text-sidebar-accent" id="footer-total-price">₱0.00</span></p>
      </div>
      <div class="flex gap-4">
        <button class="px-5 py-3 bg-white border border-sidebar-accent text-sidebar-accent rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeCheckoutModal()">Cancel</button>
        <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center" onclick="confirmCheckout()">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          Confirm Order
        </button>
      </div>
    </div>
  </div>
</div>

    <!-- Order Confirmation Modal -->
    <div id="confirmation-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
      <div class="bg-white rounded-lg w-full max-w-md overflow-hidden">
        <div class="p-5 border-b border-sidebar-border flex justify-between items-center">
          <h3 class="text-xl font-bold text-green-600">Order Confirmed</h3>
          <button onclick="closeConfirmationModal()" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
          </button>
        </div>
        <!-- Update your confirmation modal content -->
        <div class="p-5 text-center">
          <div class="w-16 h-16 mx-auto mb-4 bg-green-100 rounded-full flex items-center justify-center">
            <i class="fas fa-check-circle text-3xl text-green-600"></i>
          </div>
          <h4 class="text-lg font-semibold mb-2 text-sidebar-text">Order Confirmed!</h4>
          <p class="text-gray-600 mb-4">Your order has been successfully placed.</p>
          <p class="text-gray-600 mb-2">Order ID: <span id="order-id" class="font-semibold">ORD-12345</span></p>
          <p class="text-gray-600">A confirmation has been sent to your records.</p>
        </div>
        <div class="p-5 border-t border-sidebar-border flex justify-center">
          <button onclick="startNewOrder()" class="px-4 py-2 bg-sidebar-accent text-white rounded font-semibold text-sm hover:bg-darkgold transition-all duration-300">Start New Order</button>
        </div>
      </div>
    </div>
  </div>

  <script>
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
  
  // Initialize empty cart
  updateCart();
  
  // Sidebar toggle functionality
  document.getElementById('hamburger-menu').addEventListener('click', toggleSidebar);

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

// Function to toggle sidebar
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  const mainContent = document.getElementById('main-content');
  
  if (sidebar.classList.contains('w-64')) {
    sidebar.classList.remove('w-64');
    sidebar.classList.add('w-16');
    mainContent.classList.remove('ml-64');
    mainContent.classList.add('ml-16');
  } else {
    sidebar.classList.remove('w-16');
    sidebar.classList.add('w-64');
    mainContent.classList.remove('ml-16');
    mainContent.classList.add('ml-64');
  }
}

// Function to load branches
function loadBranches() {
  const container = document.getElementById('branches-container');
  container.innerHTML = '';
  
  branches.forEach(branch => {
    const branchCard = document.createElement('div');
    branchCard.className = 'bg-white rounded-lg p-5 shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 cursor-pointer';
    branchCard.innerHTML = `
      <div class="text-xl font-bold mb-3 text-sidebar-text">${branch.branch_name}</div>
      <div class="flex justify-between items-center">
        <div class="text-gray-500 text-sm"><i class="fas fa-map-marker-alt mr-1"></i> Branch Location</div>
        <i class="fas fa-chevron-right text-sidebar-accent"></i>
      </div>
    `;
    branchCard.onclick = () => selectBranch(branch.branch_id, branch.branch_name);
    container.appendChild(branchCard);
  });
}

// Function to select a branch
function selectBranch(branchId, branchName) {
  selectedBranch = branchId;
  document.getElementById('selected-branch-name').textContent = branchName;
  document.getElementById('branch-selection').classList.add('hidden');
  document.getElementById('category-selection').classList.remove('hidden');
  document.getElementById('services-section').classList.add('hidden');
  
  // Load categories for this branch
  loadCategories();
}

// Function to go back to branch selection
function goBackToBranches() {
  document.getElementById('branch-selection').classList.remove('hidden');
  document.getElementById('category-selection').classList.add('hidden');
  document.getElementById('services-section').classList.add('hidden');
  selectedBranch = null;
}

// Function to load categories
function loadCategories() {
  const container = document.getElementById('categories-container');
  container.innerHTML = '';
  
  // Get all unique categories that have services in the selected branch
  const branchServices = allServices.filter(service => service.branch_id == selectedBranch);
  const branchCategories = [];
  
  // Find all categories that have services in this branch
  branchServices.forEach(service => {
    const category = categories.find(cat => cat.service_categoryID == service.service_categoryID);
    if (category && !branchCategories.some(c => c.service_categoryID == category.service_categoryID)) {
      branchCategories.push(category);
    }
  });
  
  if (branchCategories.length === 0) {
    container.innerHTML = '<div class="col-span-full text-center py-8 text-gray-500">No service categories available for this branch.</div>';
    return;
  }
  
  branchCategories.forEach(category => {
    const categoryCard = document.createElement('div');
    categoryCard.className = 'bg-white rounded-lg p-5 shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 cursor-pointer';
    categoryCard.innerHTML = `
      <div class="text-xl font-bold mb-3 text-sidebar-text">${category.service_category_name}</div>
      <div class="flex justify-between items-center">
        <div class="text-gray-500 text-sm"><i class="fas fa-tag mr-1"></i> Service Category</div>
        <i class="fas fa-chevron-right text-sidebar-accent"></i>
      </div>
    `;
    categoryCard.onclick = () => selectCategory(category.service_categoryID, category.service_category_name);
    container.appendChild(categoryCard);
  });
}

// Function to select a category
function selectCategory(categoryId, categoryName) {
  selectedCategory = categoryId;
  document.getElementById('selected-category-name').textContent = categoryName;
  document.getElementById('services-branch-name').textContent = 
    branches.find(branch => branch.branch_id == selectedBranch).branch_name;
  
  document.getElementById('category-selection').classList.add('hidden');
  document.getElementById('services-section').classList.remove('hidden');
  
  // Load services for this branch and category
  loadServices();
}

// Function to go back to category selection
function goBackToCategories() {
  document.getElementById('category-selection').classList.remove('hidden');
  document.getElementById('services-section').classList.add('hidden');
  selectedCategory = null;
}

// Function to load services for the selected branch and category
function loadServices() {
  const container = document.getElementById('services-container');
  container.innerHTML = '';

  // Filter services that match both the selected branch and category
  const filteredServices = allServices.filter(service => 
    service.branch_id == selectedBranch && 
    service.service_categoryID == selectedCategory
  );

  if (filteredServices.length === 0) {
    container.innerHTML = '<div class="col-span-full text-center py-8 text-gray-500">No services available for this category at this branch.</div>';
    return;
  }

  filteredServices.forEach(service => {
    const serviceCard = document.createElement('div');
    serviceCard.className = 'bg-white rounded-lg overflow-hidden shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 cursor-pointer';
    serviceCard.onclick = () => showServiceDetails(service);

    // Use a default image if none provided or if the URL is empty
    const imageUrl = service.image_url && service.image_url.trim() !== '' ? service.image_url : 'assets/images/service-default.png';

    // Create a truncated version of inclusions for the card
    const inclusionsSummary = service.inclusions && service.inclusions.length > 100 
      ? service.inclusions.substring(0, 100) + '...'
      : service.inclusions || 'No inclusions specified';

    serviceCard.innerHTML = `
      <div class="h-40 bg-center bg-cover bg-no-repeat" style="background-image: url('${imageUrl}');"></div>
      <div class="p-5">
        <div class="text-lg font-bold mb-2.5 text-sidebar-text">${service.service_name}</div>
        <div class="text-gray-500 text-sm mb-3">${service.flower_design}</div>
        <div class="text-gray-500 text-sm mb-3">${inclusionsSummary}</div>
        <div class="text-lg font-bold text-sidebar-accent">₱${parseFloat(service.selling_price).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</div>
      </div>
    `;
    container.appendChild(serviceCard);
  });
}

// Function to show service details in modal
function showServiceDetails(service) {
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
  const imageUrl = service.image_url && service.image_url.trim() !== '' ? service.image_url : 'assets/images/service-default.png';

  document.getElementById('modal-content').innerHTML = `
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
      <div class="h-64 bg-center bg-cover bg-no-repeat rounded-lg" style="background-image: url('${imageUrl}');"></div>
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

// Function to add the selected service to cart
// Function to handle adding to cart and immediately proceed to checkout
// Function to handle adding to cart and immediately proceed to checkout
function addToCart() {
  if (selectedService) {
    // Set the service details in the form
    document.getElementById('service-id').value = selectedService.service_id;
    document.getElementById('service-price').value = selectedService.selling_price;
    document.getElementById('branch-id').value = selectedService.branch_id;
    
    // Update the total price in the checkout form
    document.getElementById('totalPrice').value = selectedService.selling_price;
    document.getElementById('footer-total-price').textContent = 
      `₱${parseFloat(selectedService.selling_price).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    
    // Update minimum price display
    const minimumPrice = parseFloat(selectedService.selling_price) * 0.5;
    document.getElementById('min-price').textContent = `₱${minimumPrice.toFixed(2)}`;
    
    // Close the package modal and open checkout
    closePackageModal();
    document.getElementById('checkoutModal').classList.remove('hidden');
    
    // Log the added service details
    console.log("Proceeding to checkout with service:", {
      service_id: selectedService.service_id,
      branch_id: selectedService.branch_id,
      price: selectedService.selling_price
    });
  }
}



// Function to handle checkout


function closeCheckoutModal() {
  document.getElementById('checkoutModal').classList.add('hidden');
}

// Function to confirm checkout with all validations
function confirmCheckout() {
  // Get all form inputs
  const form = document.getElementById('checkoutForm');
  const formData = new FormData(form);

  // Validate email format
  const email = document.getElementById('clientEmail').value;
  if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    alert("Please enter a valid email address.");
    return;
  }

  // Validate phone number (digits only)
  const phone = document.getElementById('clientPhone').value;
  if (phone && !/^\d+$/.test(phone)) {
    alert("Phone number should contain only digits.");
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
      return;
    }
    if (dateOfDeath && dateOfBirth > dateOfDeath) {
      alert("Date of birth cannot be after date of death.");
      return;
    }
  }

  // Validate date of death (required)
  if (!dateOfDeath) {
    alert("Date of death is required.");
    return;
  }
  if (dateOfDeath > today) {
    alert("Date of death cannot be in the future.");
    return;
  }

  // Validate date of burial (if provided)
  if (dateOfBurial) {
    if (dateOfBurial < dateOfDeath) {
      alert("Date of burial cannot be before date of death.");
      return;
    }
  }

  // Validate date of death (required)
  if (!dateOfDeath) {
    alert("Date of death is required.");
    return;
  }
  if (dateOfDeath > today) {
    alert("Date of death cannot be in the future.");
    return;
  }

  // Validate date of burial (if provided)
  if (dateOfBurial) {
    if (dateOfBurial < dateOfDeath) {
      alert("Date of burial cannot be before date of death.");
      return;
    }
  }

  // Payment validations
  const servicePrice = parseFloat(document.getElementById('service-price').value) || 0;
  const totalPrice = parseFloat(document.getElementById('totalPrice').value) || 0;
  const amountPaid = parseFloat(document.getElementById('amountPaid').value) || 0;

  // Validate total price is at least 50% of service price
  const minimumAllowedPrice = servicePrice * 0.5;
  if (totalPrice < minimumAllowedPrice) {
    alert(`Total price cannot be lower than 50% of the service price (₱${minimumAllowedPrice.toFixed(2)}).`);
    return;
  }

  // Validate amount paid doesn't exceed total price
  if (amountPaid > totalPrice) {
    alert("Amount paid cannot exceed the total price.");
    return;
  }

  // Add service data (single service now instead of cart)
  formData.append('service_id', document.getElementById('service-id').value);
  formData.append('sold_by', 1); // Assuming admin ID is 1
  formData.append('status', 'Pending');
  
  // Calculate balance
  const balance = totalPrice - amountPaid;
  
  formData.append('balance', balance.toFixed(2));
  formData.append('payment_status', balance > 0 ? 'With Balance' : 'Fully Paid');

  // Send data to server using AJAX
  fetch('process_checkout.php', {
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