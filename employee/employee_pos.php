<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Point-Of-Sale - GrievEase</title>
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
    
    /* Animate the sidebar
    @keyframes slideIn {
      from { transform: translateX(-100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }
    
    .animate-sidebar {
      animation: slideIn 0.3s ease forwards;
    } */

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
        <div class="text-sm font-medium text-sidebar-text">John Doe</div>
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

  <!-- Packages Section -->
  <div class="mb-8">
    <h2 class="mb-5 text-gray-600 text-lg">Select a Package</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
      <!-- Package 1 -->
      <div class="bg-white rounded-lg overflow-hidden shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 cursor-pointer" onclick="openPackageModal('essential')">
        <div class="h-44 bg-center bg-cover bg-no-repeat" style="background-image: url('image2.png');"></div>
        <div class="p-5">
          <div class="text-lg font-bold mb-2.5 text-sidebar-text">Essential Service</div>
          <div class="text-gray-500 mb-4 text-sm">A dignified and affordable funeral service package</div>
          <div class="text-xl font-bold text-sidebar-accent">$3,500</div>
          <div class="mt-2.5 text-sm text-gray-500">Includes casket, basic flowers, venue</div>
        </div>
      </div>
      
      <!-- Package 2 -->
      <div class="bg-white rounded-lg overflow-hidden shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 cursor-pointer" onclick="openPackageModal('standard')">
        <div class="h-44 bg-center bg-cover bg-no-repeat" style="background-image: url('image1.png');"></div>
        <div class="p-5">
          <div class="text-lg font-bold mb-2.5 text-sidebar-text">Standard Service</div>
          <div class="text-gray-500 mb-4 text-sm">Our most popular comprehensive service package</div>
          <div class="text-xl font-bold text-sidebar-accent">$6,200</div>
          <div class="mt-2.5 text-sm text-gray-500">Includes casket, flowers, venue, catering</div>
        </div>
      </div>
      
      <!-- Package 3 -->
      <div class="bg-white rounded-lg overflow-hidden shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 cursor-pointer" onclick="openPackageModal('premium')">
        <div class="h-44 bg-center bg-cover bg-no-repeat" style="background-image: url('image3.png');"></div>
        <div class="p-5">
          <div class="text-lg font-bold mb-2.5 text-sidebar-text">Premium Tribute</div>
          <div class="text-gray-500 mb-4 text-sm">An elegant and comprehensive celebration of life</div>
          <div class="text-xl font-bold text-sidebar-accent">$9,800</div>
          <div class="mt-2.5 text-sm text-gray-500">Includes premium casket, custom flowers, venue, catering</div>
        </div>
      </div>
      
      <!-- Package 4 -->
      <div class="bg-white rounded-lg overflow-hidden shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 cursor-pointer" onclick="openPackageModal('cremation')">
        <div class="h-44 bg-center bg-cover bg-no-repeat" style="background-image: url('image4.png');"></div>
        <div class="p-5">
          <div class="text-lg font-bold mb-2.5 text-sidebar-text">Memorial Cremation</div>
          <div class="text-gray-500 mb-4 text-sm">A beautiful memorial service with cremation</div>
          <div class="text-xl font-bold text-sidebar-accent">$4,200</div>
          <div class="mt-2.5 text-sm text-gray-500">Includes urn, memorial service, keepsakes</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Cart Section -->
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border p-5 hover:shadow-card transition-all duration-300">
    <h2 class="mb-5 text-gray-600 text-lg">Your Cart</h2>
    <div class="mb-5">
      <table class="w-full border-collapse">
        <thead>
          <tr class="bg-sidebar-hover">
            <th class="p-3 text-left border-b border-sidebar-border text-sidebar-text">Package/Item</th>
            <th class="p-3 text-left border-b border-sidebar-border text-sidebar-text">Details</th>
            <th class="p-3 text-left border-b border-sidebar-border text-sidebar-text">Price</th>
            <th class="p-3 text-left border-b border-sidebar-border text-sidebar-text">Action</th>
          </tr>
        </thead>
        <tbody id="cart-items-body">
          <!-- Cart items will be dynamically added here -->
        </tbody>
      </table>
    </div>
    <div class="text-lg text-right my-5">
      <strong class="text-sidebar-text">Total: </strong>
      <span id="cart-total" class="font-bold text-sidebar-accent">$0.00</span>
    </div>
    <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded font-semibold text-sm hover:bg-darkgold transition-all duration-300" onclick="checkout()">Proceed to Checkout</button>
  </div>
</div>

  <!-- Package Details Modal -->
<div class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="packageModal">
  <div class="bg-white rounded-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 sticky top-0 z-10">
      <h3 id="modal-package-title" class="text-xl font-bold text-white">Package Details</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closePackageModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>
    
    <!-- Modal Body -->
    <div class="p-6">
      <!-- Package Info Section -->
      <div class="flex flex-col md:flex-row gap-6 mb-8">
        <div class="w-full md:w-80 h-56 bg-center bg-cover rounded-xl shadow-md flex-shrink-0" id="modal-package-image" style="background-image: url('/api/placeholder/600/400');"></div>
        <div class="flex-1">
          <div class="text-2xl font-bold mb-2 text-gray-800" id="modal-package-name">Package Name</div>
          <div class="mb-4 text-gray-600 leading-relaxed" id="modal-package-description">Package description goes here.</div>
          <div class="text-3xl font-bold text-sidebar-accent" id="modal-package-price">$0.00</div>
        </div>
      </div>
      
      <!-- Package Includes Section - Improved layout -->
      <div class="mb-8 bg-gray-50 p-6 rounded-xl shadow-sm">
        <h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center border-b border-gray-200 pb-3">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
            <polyline points="9 11 12 14 22 4"></polyline>
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
          </svg>
          Package Includes:
        </h4>
        <ul id="modal-package-includes" class="space-y-3 grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
          <!-- Package items will be dynamically added here -->
        </ul>
      </div>
      
      <!-- Add-ons Section -->
      <div class="mb-8">
        <h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center border-b border-gray-200 pb-3">
          <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="16"></line>
            <line x1="8" y1="12" x2="16" y2="12"></line>
          </svg>
          Optional Add-ons:
        </h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="modal-add-ons">
          <!-- Add-on items will be dynamically added here -->
        </div>
      </div>
      
      <!-- Summary Section -->
      <div class="bg-navy p-6 rounded-xl shadow-sm border border-purple-100">
        <h4 class="font-bold text-lg mb-4 text-gray-800 border-b border-purple-100 pb-3">Order Summary</h4>
        <div class="flex justify-between mb-3 text-gray-700">
          <span>Package Price:</span>
          <span id="summary-package-price" class="font-medium">$0.00</span>
        </div>
        <div class="flex justify-between mb-3 text-gray-700">
          <span>Selected Add-ons:</span>
          <span id="summary-addons-price" class="font-medium">$0.00</span>
        </div>
        <div class="flex justify-between font-bold text-lg mt-4 pt-4 border-t border-dashed border-purple-200 text-sidebar-accent">
          <span>Total:</span>
          <span id="summary-total-price">$0.00</span>
        </div>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closePackageModal()">Cancel</button>
      <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center" onclick="addPackageToCart()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
          <circle cx="9" cy="21" r="1"></circle>
          <circle cx="20" cy="21" r="1"></circle>
          <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
        </svg>
        Add to Cart
      </button>
    </div>
  </div>
</div>
<!-- Checkout Modal -->
<div class="fixed top-0 left-0 w-full h-full bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="checkoutModal">
  <div class="bg-white rounded-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-xl">
    <!-- Modal Header -->
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
            <div>
              <label for="clientName" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
              <input type="text" id="clientName" name="clientName" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="deceasedName" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" id="deceasedName" name="deceasedName" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
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
            </div>
            
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
                <label for="dateOfBurial" class="block text-sm font-medium text-gray-700 mb-1">Date of Burial <span class="text-xs text-gray-500">(Optional)</span></label>
                <input type="date" id="dateOfBurial" name="dateOfBurial" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
              </div>
            </div>
            <div>
              <label for="deceasedAddress" class="block text-sm font-medium text-gray-700 mb-1">Address of the Deceased</label>
              <textarea id="deceasedAddress" name="deceasedAddress" required class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" rows="2"></textarea>
            </div>
          </div>
        </div>

        <!-- Branch Selection -->
        <div class="bg-navy p-6 rounded-xl shadow-sm border border-purple-100">
          <h4 class="text-lg font-bold text-gray-800 mb-4 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-sidebar-accent">
              <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
              <polyline points="9 22 9 12 15 12 15 22"></polyline>
            </svg>
            Select Branch Location
          </h4>
          <div class="flex flex-wrap gap-4">
            <label class="flex-1 min-w-48 inline-flex items-center p-4 border border-gray-300 rounded-lg bg-white hover:border-sidebar-accent hover:shadow-md cursor-pointer transition-all">
              <input type="radio" name="branchLocation" value="Pila" class="mr-3 h-4 w-4 text-purple-600" required>
              <div>
                <span class="font-medium text-gray-800 block">Pila Branch</span>
                <span class="text-xs text-gray-500">123 Main Street, Pila</span>
              </div>
            </label>
            <label class="flex-1 min-w-48 inline-flex items-center p-4 border border-gray-300 rounded-lg bg-white hover:border-sidebar-accent hover:shadow-md cursor-pointer transition-all">
              <input type="radio" name="branchLocation" value="Paete" class="mr-3 h-4 w-4 text-purple-600">
              <div>
                <span class="font-medium text-gray-800 block">Paete Branch</span>
                <span class="text-xs text-gray-500">456 Oak Avenue, Paete</span>
              </div>
            </label>
          </div>
        </div>

        <!-- Payment Information -->
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
                <option value="Credit Card">Credit Card</option>
                <option value="Bank Transfer">Bank Transfer</option>
                <option value="Insurance">Insurance</option>
              </select>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="totalPrice" class="block text-sm font-medium text-gray-700 mb-1">Total Price <span class="text-xs text-gray-500">(Customizable for discounts)</span></label>
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
      </form>
    </div>

    <!-- Modal Footer -->
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

  <script>
  // Cart data
let cart = [];
let total = 0;

// Package data
const packages = {
  essential: {
    name: "Essential Service",
    description: "A dignified and affordable funeral service that provides all the necessary elements for a respectful farewell.",
    price: 3500,
    image: "image2.png", // Updated image path
    includes: [
      "Standard oak casket",
      "Basic floral arrangement",
      "Funeral home service (3 hours)",
      "Standard printed programs (50 copies)",
      "Guest register book",
      "Transportation service",
      "Basic embalming and preparation"
    ],
    addOns: [
      { id: "e1", name: "Premium Floral Arrangement", price: 350, description: "Upgraded flowers with personalized design" },
      { id: "e2", name: "Additional Service Time", price: 400, description: "Extended service hours (2 additional hours)" },
      { id: "e3", name: "Video Tribute", price: 250, description: "Memorial slideshow with 50 photos" },
      { id: "e4", name: "Additional Programs", price: 75, description: "50 additional printed programs" }
    ]
  },
  standard: {
    name: "Standard Service",
    description: "Our most popular comprehensive service package offering additional amenities for a more personalized ceremony.",
    price: 6200,
    image: "image1.png", // Updated image path
    includes: [
      "Premier hardwood casket",
      "Enhanced floral arrangements",
      "Funeral home service (4 hours)",
      "Custom printed programs (100 copies)",
      "Premium guest register book",
      "Transportation service with limousine",
      "Complete embalming and cosmetic preparation",
      "Memorial video tribute",
      "Basic catering service (up to 50 guests)"
    ],
    addOns: [
      { id: "s1", name: "Premium Catering Package", price: 850, description: "Enhanced menu for up to 75 guests" },
      { id: "s2", name: "Live Music Performance", price: 600, description: "Professional musicians for ceremony" },
      { id: "s3", name: "Memory Keepsake Set", price: 350, description: "Personalized keepsakes for family members" },
      { id: "s4", name: "Additional Limousine", price: 450, description: "Extra limousine for family transport" }
    ]
  },
  premium: {
    name: "Premium Tribute",
    description: "An elegant and comprehensive celebration of life with premium amenities and highly personalized service.",
    price: 9800,
    image: "image3.png", // Updated image path
    includes: [
      "Premium mahogany or metal casket",
      "Custom floral arrangements",
      "Full-day funeral service",
      "Luxury printed materials (200 copies)",
      "Leather guest book with custom embossing",
      "Complete transportation package with multiple vehicles",
      "Premium preparation services",
      "Professional video production and photography",
      "Premium catering (up to 100 guests)",
      "Memorial website",
      "Professional ceremony coordination"
    ],
    addOns: [
      { id: "p1", name: "String Quartet", price: 1200, description: "Live classical music performance" },
      { id: "p2", name: "Custom Monument", price: 2500, description: "Personalized headstone or memorial" },
      { id: "p3", name: "Dove Release Ceremony", price: 450, description: "Symbolic release of doves" },
      { id: "p4", name: "Memorial Portrait", price: 650, description: "Professional portrait by commissioned artist" },
      { id: "p5", name: "Legacy Video Biography", price: 1800, description: "Professional documentary of loved one's life" }
    ]
  },
  cremation: {
    name: "Memorial Cremation",
    description: "A beautiful memorial service with cremation that focuses on celebration and remembrance.",
    price: 4200,
    image: "image4.png", // Updated image path
    includes: [
      "Cremation process",
      "Choice of standard urn",
      "Memorial service (3 hours)",
      "Floral tribute display",
      "Memorial printed programs (75 copies)",
      "Guest register book",
      "Photo display board",
      "Basic keepsake items for family"
    ],
    addOns: [
      { id: "c1", name: "Premium Urn", price: 450, description: "Upgraded custom-designed urn" },
      { id: "c2", name: "Keepsake Urns", price: 350, description: "Set of smaller matching urns for family members" },
      { id: "c3", name: "Cremation Jewelry", price: 275, description: "Memorial pendants containing a small portion of ashes" },
      { id: "c4", name: "Memorial Garden Stone", price: 320, description: "Personalized garden memorial stone" },
      { id: "c5", name: "Tree Planting Service", price: 500, description: "Memorial tree planting with ceremony" }
    ]
  }
};

// Current selected package and add-ons
let currentPackage = null;
let selectedAddOns = [];

// Open package modal
function openPackageModal(packageId) {
  // Set current package
  currentPackage = packageId;
  selectedAddOns = [];
  
  // Update modal content
  const pkg = packages[packageId];
  document.getElementById('modal-package-title').textContent = `${pkg.name} Details`;
  document.getElementById('modal-package-name').textContent = pkg.name;
  document.getElementById('modal-package-description').textContent = pkg.description;
  document.getElementById('modal-package-price').textContent = `$${pkg.price.toLocaleString()}`;
  document.getElementById('modal-package-image').style.backgroundImage = `url('${pkg.image}')`;
  
  // Update includes list
  const includesList = document.getElementById('modal-package-includes');
  includesList.innerHTML = '';
  pkg.includes.forEach(item => {
    const li = document.createElement('li');
    li.className = 'flex items-center text-gray-600';
    li.innerHTML = `<i class="fas fa-check-circle text-green-500 mr-2"></i>${item}`;
    includesList.appendChild(li);
  });
  
  // Update add-ons
  const addOnsGrid = document.getElementById('modal-add-ons');
  addOnsGrid.innerHTML = '';
  pkg.addOns.forEach(addOn => {
    const addOnDiv = document.createElement('div');
    addOnDiv.className = 'bg-white p-3 rounded-lg border border-gray-200 shadow-sm hover:shadow-md transition-shadow';
    addOnDiv.innerHTML = `
      <div class="flex items-center mb-2">
        <input type="checkbox" id="addon-${addOn.id}" class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-500" 
               data-id="${addOn.id}" data-price="${addOn.price}" onChange="updateSelectedAddOns(this)">
        <label for="addon-${addOn.id}" class="ml-2 text-sm font-medium text-gray-700">${addOn.name}</label>
      </div>
      <div class="text-sidebar-accent font-semibold mb-1.5">$${addOn.price.toLocaleString()}</div>
      <div class="text-xs text-gray-500">${addOn.description}</div>
    `;
    addOnsGrid.appendChild(addOnDiv);
  });
  
  // Update summary
  updatePackageSummary();
  
  // Show modal
  document.getElementById('packageModal').style.display = 'flex';
}

// Close package modal
function closePackageModal() {
  document.getElementById('packageModal').style.display = 'none';
  currentPackage = null;
  selectedAddOns = [];
}

// Update selected add-ons
function updateSelectedAddOns(checkbox) {
  const addOnId = checkbox.dataset.id;
  const addOnPrice = parseFloat(checkbox.dataset.price);
  
  if (checkbox.checked) {
    // Add to selected add-ons
    const pkg = packages[currentPackage];
    const addOn = pkg.addOns.find(a => a.id === addOnId);
    selectedAddOns.push({
      id: addOnId,
      name: addOn.name,
      price: addOnPrice
    });
  } else {
    // Remove from selected add-ons
    selectedAddOns = selectedAddOns.filter(a => a.id !== addOnId);
  }
  
  // Update summary
  updatePackageSummary();
}

// Update package summary
function updatePackageSummary() {
  const pkg = packages[currentPackage];
  const packagePrice = pkg.price;
  const addOnsTotal = selectedAddOns.reduce((sum, addOn) => sum + addOn.price, 0);
  const total = packagePrice + addOnsTotal;
  
  document.getElementById('summary-package-price').textContent = `$${packagePrice.toLocaleString()}`;
  document.getElementById('summary-addons-price').textContent = `$${addOnsTotal.toLocaleString()}`;
  document.getElementById('summary-total-price').textContent = `$${total.toLocaleString()}`;
}

// Add package to cart
function addPackageToCart() {
  const pkg = packages[currentPackage];
  
  // Create cart item
  const cartItem = {
    id: Date.now(), // Unique ID
    type: 'package',
    name: pkg.name,
    price: pkg.price,
    addOns: [...selectedAddOns]
  };
  
  // Add to cart
  cart.push(cartItem);
  
  // Update cart display
  updateCartDisplay();
  
  // Close modal
  closePackageModal();
}

// Update cart display
function updateCartDisplay() {
  const cartBody = document.getElementById('cart-items-body');
  cartBody.innerHTML = '';
  total = 0;
  
  cart.forEach(item => {
    const row = document.createElement('tr');
    
    // Calculate item total
    const addOnsTotal = item.addOns.reduce((sum, addOn) => sum + addOn.price, 0);
    const itemTotal = item.price + addOnsTotal;
    total += itemTotal;
    
    // Create add-ons text
    let addOnsText = '';
    if (item.addOns.length > 0) {
      addOnsText = '<strong>Add-ons:</strong><br>';
      item.addOns.forEach(addOn => {
        addOnsText += `- ${addOn.name} ($${addOn.price.toLocaleString()})<br>`;
      });
    }
    
    row.innerHTML = `
      <td class="p-3 border-b border-gray-200">${item.name}</td>
      <td class="p-3 border-b border-gray-200">
        <strong>Base Package:</strong> $${item.price.toLocaleString()}<br>
        ${addOnsText}
      </td>
      <td class="p-3 border-b border-gray-200">$${itemTotal.toLocaleString()}</td>
      <td class="p-3 border-b border-gray-200">
        <button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all" 
                onclick="removeFromCart(${item.id})">
          <i class="fas fa-trash"></i> Remove
        </button>
      </td>
    `;
    
    cartBody.appendChild(row);
  });
  
  document.getElementById('cart-total').textContent = `$${total.toLocaleString()}`;
}

// Remove from cart
function removeFromCart(itemId) {
  cart = cart.filter(item => item.id !== itemId);
  updateCartDisplay();
}

// Checkout
function checkout() {
  if (cart.length === 0) {
    alert('Your cart is empty. Please add a package before checking out.');
    return;
  }

  // Set total amount in checkout form
  document.getElementById('totalPrice').value = total;

  // Show checkout modal
  document.getElementById('checkoutModal').style.display = 'flex';
}

// Close checkout modal
function closeCheckoutModal() {
  document.getElementById('checkoutModal').style.display = 'none';
}

// Confirm checkout
function confirmCheckout() {
  const form = document.getElementById('checkoutForm');
  if (!form.checkValidity()) {
    // Trigger browser's built-in validation
    form.reportValidity();
    return;
  }

  // Gather form data
  const formData = new FormData(form);
  const orderDetails = {
    clientName: formData.get('clientName'),
    clientPhone: formData.get('clientPhone'),
    clientEmail: formData.get('clientEmail'),
    deceasedName: formData.get('deceasedName'),
    dateOfDeath: formData.get('dateOfDeath'),
    dateOfBurial: formData.get('dateOfBurial'),
    deceasedAddress: formData.get('deceasedAddress'),
    branchLocation: formData.get('branchLocation'),
    paymentMethod: formData.get('paymentMethod'),
    totalPrice: formData.get('totalPrice'),
    amountPaid: formData.get('amountPaid'),
    items: cart
  };

  // Here you would typically send the orderDetails to your server for processing
  console.log('Order Details:', orderDetails);

  // Reset cart and close modals
  cart = [];
  total = 0;
  updateCartDisplay();
  closeCheckoutModal();

  // Show confirmation message
  alert('Thank you for your order! We will contact you shortly to confirm the details.');
}


// Initialize the page
function init() {
  updateCartDisplay();
  
  // Set current date as default for date inputs
  const today = new Date().toISOString().split('T')[0];
  if (document.getElementById('dateOfDeath')) {
    document.getElementById('dateOfDeath').value = today;
  }
}

// Run initialization when the page loads
window.onload = init;
  </script>
  <script src="sidebar.js"></script>
  <script src="tailwind.js"></script>
</body>
</html>