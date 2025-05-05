<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Database connection
include '../db_connect.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user information from database instead of relying on session data
$user_id = $_SESSION['user_id'];
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

// Check if user exists
if ($user_result->num_rows === 0) {
    // User not found in database
    session_destroy();
    header("Location: ../Landing_Page/login.php?error=invalid_user");
    exit();
}

// Fetch user data
$user_data = $user_result->fetch_assoc();
$user_type = $user_data['user_type'];
$user_branch_id = $user_data['branch_loc'] ?? null;
$user_name = $user_data['first_name'] . ' ' . $user_data['last_name'];

// Check for employee user type (user_type = 2)
if ($user_type != 2) {
    // Redirect to appropriate page based on user type
    switch ($user_type) {
        case 1:
            header("Location: ../admin/index.php");
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

// Check if user has a branch location assigned
if (!$user_branch_id) {
    // Handle case where user doesn't have a branch assigned
    die("Error: User branch location not assigned.");
}

// Function to get all active services for the user's branch
function getServices($conn, $user_branch_id) {
  $sql = "SELECT s.service_id, s.service_name, s.description, s.service_categoryID, s.branch_id, 
                s.inclusions, s.flower_design, s.capital_price, s.selling_price, s.image_url, 
                b.branch_name, 
                c.service_category_name
         FROM services_tb s
         INNER JOIN branch_tb b ON s.branch_id = b.branch_id
         INNER JOIN service_category c ON s.service_categoryID = c.service_categoryID
         WHERE s.status = 'Active' AND s.branch_id = ?
         ORDER BY s.service_categoryID, s.service_name";
  
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $user_branch_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  $services = [];
  
  if ($result->num_rows > 0) {
      while($row = $result->fetch_assoc()) {
          $services[] = $row;
      }
  }
  
  return $services;
}

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

$branches = getBranches($conn);
$categories = getServiceCategories($conn);
$allServices = getServices($conn, $user_branch_id);

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
  <title>GrievEase - Point-Of-Sale</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
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
    /* Add this to your existing styles */
.main-content {
  margin-left: 16rem; /* Adjust this value to match the width of your sidebar */
  width: calc(100% - 16rem); /* Ensure the main content takes up the remaining width */
  z-index: 1; /* Ensure the main content is above the sidebar */
}

.sidebar {
  z-index: 10; /* Ensure the sidebar is below the main content */
}
/* Add this to your existing styles */
#sidebar {
  transition: width 0.3s ease, opacity 0.3s ease, visibility 0.3s ease;
}

#main-content {
  transition: margin-left 0.3s ease;
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
  </style>
</head>
<body class="flex bg-gray-50">
  <!-- Modify the sidebar structure to include a dedicated space for the hamburger menu -->
<nav id="sidebar" class="w-64 h-screen bg-sidebar-bg font-hedvig fixed transition-all duration-300 overflow-y-auto z-10 scrollbar-thin shadow-sidebar animate-sidebar sidebar">
  <!-- Logo and Header with hamburger menu -->
  <div class="flex items-center px-5 py-6 border-b border-sidebar-border">
    <button id="hamburger-menu" class="p-2 mr-2 bg-white rounded-lg shadow-md text-gray-600 hover:text-gray-900 transition-all duration-300">
      <i class="fas fa-bars"></i>
    </button>
    <!-- <img src="../Landing_Page/Landing_images/logo.png" alt="GrievEase Logo" class="h-10 w-auto mr-3"> -->
    <div class="text-2xl font-cinzel font-bold text-sidebar-accent">GrievEase</div>
  </div>
    
    <!-- User Profile -->
    <div class="flex items-center px-5 py-4 border-b border-sidebar-border bg-gradient-to-r from-navy to-primary">
      <div class="w-10 h-10 rounded-full bg-yellow-600 flex items-center justify-center shadow-md">
        <i class="fas fa-user text-white"></i>
      </div>
      <div class="ml-3">
        <div class="text-sm font-medium text-sidebar-text"><?php echo htmlspecialchars($user_name); ?></div>
        <div class="text-xs text-sidebar-text opacity-70">Employee</div>
      </div>
      <div class="ml-auto">
        <span class="w-3 h-3 bg-success rounded-full block"></span>
      </div>
    </div>
    
    <!-- Menu Items -->
    <div class="pt-4 pb-8">
      <!-- Main Navigation -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Main</h5>
      </div>
      <ul class="list-none p-0 mb-6">
        <li>
          <a href="index.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-tachometer-alt w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Dashboard</span>
          </a>
        </li> 
        <li>
          <a href="employee_customer_account_creation.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-user-circle w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Customer Account Management</span>
          </a>
        </li>
        <li>
          <a href="employee_inventory.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-boxes w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>View Inventory</span>
          </a>
        </li>
        <li>
          <a href="employee_pos.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-cash-register w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Point-Of-Sale (POS)</span>
          </a>
        </li>
      </ul>
        
      <!-- Reports & Analytics -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Reports & Analytics</h5>
      </div>
      <ul class="list-none p-0 mb-6">
        <li>
          <a href="employee_expenses.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-money-bill-wave w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Expenses</span>
          </a>
        </li>
        <li>
          <a href="history.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-history w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Service History</span>
          </a>
        </li>
      </ul>
        
      <!-- Services & Staff -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Communication</h5>
      </div>
      <ul class="list-none p-0 mb-6">
          <a href="employee_chat.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-comments w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Chats</span>
          </a>
        </li>
      </ul>
        
      <!-- Account -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Account</h5>
      </div>
      <ul class="list-none p-0">
        <li>
          <a href="..\logout.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover hover:text-error">
            <i class="fas fa-sign-out-alt w-5 text-center mr-3 text-error"></i>
            <span>Logout</span>
          </a>
        </li>
      </ul>
    </div>
    
    <!-- Footer -->
    <div class="relative bottom-0 left-0 right-0 px-5 py-3 border-t border-sidebar-border bg-gradient-to-r from-navy to-primary">
      <div class="flex justify-between items-center">
        <p class="text-xs text-sidebar-text opacity-60">© 2025 GrievEase</p>
        <div class="text-xs text-sidebar-accent">
          <i class="fas fa-heart"></i> With Compassion
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div id="main-content" class="ml-64 p-6 bg-gray-50 min-h-screen transition-all duration-300 main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Point-Of-Sale (POS)</h1>
      <p class="text-sm text-gray-600">Services available at your branch: 
        <span class="font-semibold text-sidebar-accent">
          <?php 
          // Get branch name from database
          $branch_query = "SELECT branch_name FROM branch_tb WHERE branch_id = ?";
          $stmt = $conn->prepare($branch_query);
          $stmt->bind_param("i", $user_branch_id);
          $stmt->execute();
          $branch_result = $stmt->get_result();
          $branch_name = $branch_result->fetch_assoc()['branch_name'];
          echo htmlspecialchars($branch_name);
          ?>
        </span>
      </p>
    </div>
    <div class="flex space-x-3">
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-bell"></i>
      </button>
    </div>
  </div>

  <!-- Services Section (Now visible by default) -->
  <div id="services-section" class="mb-8">
    <div class="flex items-center justify-between mb-6 bg-white p-4 rounded-lg shadow-sm border border-sidebar-border">
      <div class="flex items-center">
        <h2 class="text-gray-700 text-lg">
          <span id="selected-category-name" class="font-semibold text-sidebar-accent">All</span> Services
        </h2>
      </div>
      <div class="hidden md:flex">
        <div class="hidden md:flex items-center gap-4">
          <!-- Category Filter Dropdown -->
          <div class="relative">
            <select id="category-filter" class="appearance-none pl-3 pr-8 py-2 border border-sidebar-border rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent cursor-pointer">
              <option value="all">All Categories</option>
              <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['service_categoryID']; ?>">
                  <?php echo htmlspecialchars($category['service_category_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="absolute right-3 top-1/2 transform -translate-y-1/2 pointer-events-none">
              <i class="fas fa-chevron-down text-gray-400"></i>
            </div>
          </div>
          
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

</div> <!-- MAIN CONTENT-->

 


<script>
  // Initialize JSON data from PHP
  const branches = <?php echo $branchesJson; ?>;
  const categories = <?php echo $categoriesJson; ?>;
  const services = <?php echo $servicesJson; ?>;
  const userBranchId = <?php echo $user_branch_id; ?>;
  
  // DOM elements
  const servicesSection = document.getElementById('services-section');
  const servicesContainer = document.getElementById('services-container');
  const selectedCategoryName = document.getElementById('selected-category-name');
  const categoryFilter = document.getElementById('category-filter');
  const priceFilter = document.getElementById('price-filter');
  const priceSort = document.getElementById('price-sort');
  const serviceSearch = document.getElementById('service-search');
  
  // Variables to track selections
  let selectedCategoryId = 'all';
  let currentFilteredServices = [];
  
  // On page load, show all services for the user's branch
  document.addEventListener('DOMContentLoaded', function() {
    filterAndDisplayServices();
  });
  
  // Filter and display services based on current selections
  function filterAndDisplayServices() {
    // Start with branch filter (only show services from user's branch)
    let filteredServices = services.filter(service => service.branch_id == userBranchId);
    
    // Apply category filter if not "all"
    if (selectedCategoryId !== 'all') {
      filteredServices = filteredServices.filter(service => 
        service.service_categoryID == selectedCategoryId
      );
    }
    
    // Apply price range filter
    if (priceFilter.value) {
      const range = priceFilter.value.split('-');
      if (range.length === 2) {
        const min = parseInt(range[0]);
        const max = parseInt(range[1]);
        filteredServices = filteredServices.filter(service => {
          const price = parseInt(service.selling_price);
          return price >= min && price <= max;
        });
      } else if (priceFilter.value.endsWith('+')) {
        const min = parseInt(priceFilter.value);
        filteredServices = filteredServices.filter(service => 
          parseInt(service.selling_price) >= min
        );
      }
    }
    
    // Apply search filter
    if (serviceSearch.value.trim()) {
      const searchTerm = serviceSearch.value.trim().toLowerCase();
      filteredServices = filteredServices.filter(service => 
        service.service_name.toLowerCase().includes(searchTerm) ||
        service.description.toLowerCase().includes(searchTerm)
      );
    }
    
    // Apply price sorting
    if (priceSort.value) {
      filteredServices.sort((a, b) => {
        const priceA = parseInt(a.selling_price);
        const priceB = parseInt(b.selling_price);
        return priceSort.value === 'low-high' ? priceA - priceB : priceB - priceA;
      });
    }
    
    // Store current filtered services
    currentFilteredServices = filteredServices;
    
    // Display services
    displayServices(filteredServices);
  }
  
  // Display services in the container
  function displayServices(services) {
    servicesContainer.innerHTML = '';
    
    if (services.length === 0) {
      servicesContainer.innerHTML = `
        <div class="col-span-full p-8 bg-white rounded-lg border border-sidebar-border text-center">
          <i class="fas fa-search text-4xl text-gray-300 mb-3"></i>
          <h3 class="text-lg font-semibold text-gray-600">No services found</h3>
          <p class="text-gray-500">Try adjusting your filters or search criteria.</p>
        </div>
      `;
      return;
    }
    
    services.forEach(service => {
      const serviceCard = document.createElement('div');
      serviceCard.className = 'bg-white rounded-lg shadow-md overflow-hidden border border-sidebar-border hover:shadow-lg transition-all duration-300';
      
      const formattedPrice = new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2
      }).format(service.selling_price);
      
      serviceCard.innerHTML = `
        <div class="h-40 bg-gray-200 relative overflow-hidden">
          <img src="${service.image_url || '/api/placeholder/400/250'}" alt="${service.service_name}" class="w-full h-full object-cover">
          <div class="absolute top-2 right-2 bg-yellow-500 text-white px-2 py-1 rounded-md text-sm font-semibold">
            ${formattedPrice}
          </div>
        </div>
        <div class="p-4">
          <h3 class="font-semibold text-sidebar-text mb-2">${service.service_name}</h3>
          <p class="text-sm text-gray-600 mb-3 line-clamp-2">${service.description}</p>
          <div class="flex justify-between items-center">
            <span class="text-xs bg-gray-100 px-2 py-1 rounded-full">${service.service_category_name}</span>
            <button onclick="selectService(${service.service_id})" class="px-3 py-1 bg-sidebar-accent text-white rounded-md text-sm hover:bg-yellow-600 transition-all duration-300">
              Select
            </button>
          </div>
        </div>
      `;
      
      servicesContainer.appendChild(serviceCard);
    });
  }
  
  // Select a service (implement this function based on your requirements)
  function selectService(serviceId) {
    const selectedService = services.find(service => service.service_id == serviceId);
    if (selectedService) {
      alert(`Selected service: ${selectedService.service_name}\nPrice: ₱${selectedService.selling_price}\n\nImplement your POS logic here.`);
      // Navigate to transaction page or show modal for order processing
    }
  }
  
  // Event listeners for filtering and sorting
  categoryFilter.addEventListener('change', function() {
    selectedCategoryId = this.value;
    if (selectedCategoryId === 'all') {
      selectedCategoryName.textContent = 'All';
    } else {
      const selectedCategory = categories.find(c => c.service_categoryID == selectedCategoryId);
      selectedCategoryName.textContent = selectedCategory ? selectedCategory.service_category_name : 'All';
    }
    filterAndDisplayServices();
  });
  
  priceFilter.addEventListener('change', filterAndDisplayServices);
  priceSort.addEventListener('change', filterAndDisplayServices);
  serviceSearch.addEventListener('input', event => {
    // Validate search input
    const searchValue = event.target.value;
    const searchError = document.getElementById('search-error');
    
    if (searchValue.startsWith(' ') || searchValue.includes('  ')) {
      searchError.classList.remove('hidden');
    } else {
      searchError.classList.add('hidden');
      filterAndDisplayServices();
    }
  });
</script>

  <script src="sidebar.js"></script>
  <script src="tailwind.js"></script>

</body>
</html>