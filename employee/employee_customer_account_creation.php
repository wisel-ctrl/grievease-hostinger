<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Account Management - Funeral Service Management System</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
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
      <h1 class="text-2xl font-bold text-sidebar-text">Customer Account Management</h1>
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

  <!-- Mode Selector -->
  <div class="flex justify-start mb-6">
    <div class="bg-gray-100 rounded-lg overflow-hidden inline-flex">
      <button id="createBtn" onclick="switchMode('create')" class="py-2 px-5 border-none bg-sidebar-accent text-white font-semibold cursor-pointer hover:bg-darkgold transition-all duration-300">Create Account</button>
      <button id="manageBtn" onclick="switchMode('manage')" class="py-2 px-5 border-none bg-transparent text-sidebar-text cursor-pointer hover:bg-sidebar-hover transition-all duration-300">Manage Accounts</button>
    </div>
    <div class="ml-auto flex items-center">
      <div id="searchContainer" class="hidden">
        <input type="text" id="searchCustomer" placeholder="Search customers..." class="p-2 border border-sidebar-border rounded-md text-sm text-sidebar-text focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
        <button onclick="searchCustomers()" class="ml-2 bg-sidebar-accent text-white border-none py-2 px-3 rounded-md cursor-pointer hover:bg-darkgold transition-all duration-300">
          <i class="fas fa-search"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Create Customer Account Form Section -->
  <div id="createAccountSection">
    <div class="bg-white rounded-lg shadow-sidebar p-5 mb-6 border border-sidebar-border hover:shadow-card transition-all duration-300">
      <div class="mb-5">
        <h3 class="text-lg font-semibold text-sidebar-text">Enter Customer Details</h3>
      </div>
      <form id="customerAccountForm" class="flex flex-col gap-4">
        <div class="flex flex-wrap gap-4">
          <div class="flex-1 min-w-[250px]">
            <div class="mb-4">
              <label for="firstName" class="block text-sm text-sidebar-text mb-1 font-medium">First Name</label>
              <input type="text" id="firstName" name="firstName" required class="w-full p-3 border border-sidebar-border rounded-md text-sm transition duration-300 bg-gray-50 focus:border-sidebar-accent focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
            </div>
            <div class="mb-4">
              <label for="lastName" class="block text-sm text-sidebar-text mb-1 font-medium">Last Name</label>
              <input type="text" id="lastName" name="lastName" required class="w-full p-3 border border-sidebar-border rounded-md text-sm transition duration-300 bg-gray-50 focus:border-sidebar-accent focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
            </div>
            <div class="mb-4">
              <label for="email" class="block text-sm text-sidebar-text mb-1 font-medium">Email Address</label>
              <input type="email" id="email" name="email" required class="w-full p-3 border border-sidebar-border rounded-md text-sm transition duration-300 bg-gray-50 focus:border-sidebar-accent focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
            </div>
            <div class="mb-4">
              <label for="phone" class="block text-sm text-sidebar-text mb-1 font-medium">Phone Number</label>
              <input type="tel" id="phone" name="phone" required class="w-full p-3 border border-sidebar-border rounded-md text-sm transition duration-300 bg-gray-50 focus:border-sidebar-accent focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
            </div>
          </div>
          <div class="flex-1 min-w-[250px]">
            <div class="mb-4">
              <label for="address" class="block text-sm text-sidebar-text mb-1 font-medium">Address</label>
              <input type="text" id="address" name="address" required class="w-full p-3 border border-sidebar-border rounded-md text-sm transition duration-300 bg-gray-50 focus:border-sidebar-accent focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
            </div>
            <div class="mb-4">
              <label for="city" class="block text-sm text-sidebar-text mb-1 font-medium">City</label>
              <input type="text" id="city" name="city" required class="w-full p-3 border border-sidebar-border rounded-md text-sm transition duration-300 bg-gray-50 focus:border-sidebar-accent focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
            </div>
            <div class="mb-4">
              <label for="state" class="block text-sm text-sidebar-text mb-1 font-medium">State</label>
              <input type="text" id="state" name="state" required class="w-full p-3 border border-sidebar-border rounded-md text-sm transition duration-300 bg-gray-50 focus:border-sidebar-accent focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
            </div>
            <div class="mb-4">
              <label for="zip" class="block text-sm text-sidebar-text mb-1 font-medium">Zip Code</label>
              <input type="text" id="zip" name="zip" required class="w-full p-3 border border-sidebar-border rounded-md text-sm transition duration-300 bg-gray-50 focus:border-sidebar-accent focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
            </div>
          </div>
        </div>
        <div class="mb-4">
          <label for="relationshipType" class="block text-sm text-sidebar-text mb-1 font-medium">Relationship Type</label>
          <select id="relationshipType" name="relationshipType" class="w-full p-2 border border-sidebar-border rounded-md text-sm text-sidebar-text focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
            <option value="">Select Relationship</option>
            <option value="primary">Primary Contact</option>
            <option value="family">Family Member</option>
            <option value="friend">Friend</option>
            <option value="legal">Legal Representative</option>
          </select>
        </div>
        <div class="mb-4">
          <label for="notes" class="block text-sm text-sidebar-text mb-1 font-medium">Additional Notes</label>
          <textarea id="notes" name="notes" rows="4" class="w-full p-3 border border-sidebar-border rounded-md text-sm transition duration-300 bg-gray-50 focus:border-sidebar-accent focus:outline-none focus:ring-2 focus:ring-sidebar-accent"></textarea>
        </div>
        <div class="flex justify-end mt-5">
          <button type="submit" class="bg-sidebar-accent text-white px-4 py-2 rounded-md font-medium text-sm hover:bg-darkgold transition duration-300">Create Account</button>
          <button type="reset" class="bg-gray-100 text-gray-700 border border-sidebar-border px-4 py-2 rounded-md font-medium text-sm ml-3 hover:bg-gray-200 transition duration-300">Clear Form</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Manage Customer Accounts Section -->
  <div id="manageAccountSection" class="hidden">
    <div class="bg-white rounded-lg shadow-sidebar p-5 mb-6 border border-sidebar-border hover:shadow-card transition-all duration-300">
      <div class="mb-5">
        <h3 class="text-lg font-semibold text-sidebar-text">Customer Accounts</h3>
      </div>
      <div class="p-5">
        <div class="overflow-x-auto scrollbar-thin">
          <table id="customerTable" class="w-full border-collapse min-w-[600px]">
            <thead>
              <tr class="bg-sidebar-hover text-left">
                <th class="p-3 border-b border-sidebar-border text-sm font-medium text-sidebar-text">Name</th>
                <th class="p-3 border-b border-sidebar-border text-sm font-medium text-sidebar-text">Email</th>
                <th class="p-3 border-b border-sidebar-border text-sm font-medium text-sidebar-text">Phone</th>
                <th class="p-3 border-b border-sidebar-border text-sm font-medium text-sidebar-text">Location</th>
                <th class="p-3 border-b border-sidebar-border text-sm font-medium text-sidebar-text">Actions</th>
              </tr>
            </thead>
            <tbody>
              <!-- Sample customers for demonstration -->
              <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
                <td class="p-3 text-sm text-sidebar-text">Jane Smith</td>
                <td class="p-3 text-sm text-sidebar-text">jane.smith@example.com</td>
                <td class="p-3 text-sm text-sidebar-text">(555) 123-4567</td>
                <td class="p-3 text-sm text-sidebar-text">Austin, TX</td>
                <td class="p-3">
                  <button onclick="editCustomer(1)" class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button onclick="viewCustomerDetails(1)" class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all">
                    <i class="fas fa-eye"></i>
                    </button>
                  <button onclick="confirmDeleteCustomer(1)" class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all">
                  <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
              <!-- Add more rows as needed -->
            </tbody>
          </table>
        </div>
        <div class="mt-5 flex justify-between items-center">
          <div>
            <span class="text-sm text-gray-600">Showing 1-3 of 3 entries</span>
          </div>
          <div>
            <button disabled class="p-2 border border-sidebar-border bg-gray-100 rounded-md mr-1 cursor-not-allowed">Previous</button>
            <button class="p-2 border border-sidebar-border bg-sidebar-accent text-white rounded-md cursor-pointer hover:bg-darkgold transition-all duration-300">1</button>
            <button disabled class="p-2 border border-sidebar-border bg-gray-100 rounded-md ml-1 cursor-not-allowed">Next</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

    <!-- Customer Details Modal -->
    <div id="customerModal" class="hidden fixed z-50 inset-0 overflow-auto bg-black bg-opacity-40">
      <div class="bg-white mx-auto my-[10%] p-5 border border-gray-300 w-4/5 max-w-3xl rounded-lg shadow-lg">
        <div class="flex justify-between items-center mb-5 border-b border-gray-300 pb-3">
          <h3 id="modalTitle" class="m-0 text-lg font-semibold">Customer Details</h3>
          <span onclick="closeModal()" class="cursor-pointer text-2xl">&times;</span>
        </div>
        <div id="modalContent">
          <!-- Content will be dynamically populated -->
        </div>
        <div class="mt-5 text-right border-t border-gray-300 pt-4">
          <button onclick="closeModal()" class="bg-gray-600 text-white border-none py-2 px-4 rounded-md cursor-pointer">Close</button>
          <button id="modalActionButton" class="bg-blue-600 text-white border-none py-2 px-4 rounded-md ml-3 cursor-pointer">Save Changes</button>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed z-50 inset-0 overflow-auto bg-black bg-opacity-40">
      <div class="bg-white mx-auto my-[15%] p-5 border border-gray-300 w-[400px] rounded-lg shadow-lg">
        <div class="text-center mb-5">
          <i class="fas fa-exclamation-triangle text-5xl text-red-600"></i>
          <h3 class="mt-4 text-lg font-semibold">Confirm Deletion</h3>
          <p class="text-gray-600">Are you sure you want to delete this customer account? This action cannot be undone.</p>
        </div>
        <div class="flex justify-center gap-3">
          <button onclick="closeDeleteModal()" class="bg-gray-600 text-white border-none py-2 px-4 rounded-md cursor-pointer">Cancel</button>
          <button onclick="deleteCustomer()" class="bg-red-600 text-white border-none py-2 px-4 rounded-md cursor-pointer">Delete</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Mode switching functionality
    function switchMode(mode) {
      if (mode === 'create') {
        document.getElementById('createAccountSection').style.display = 'block';
        document.getElementById('manageAccountSection').style.display = 'none';
        document.getElementById('createBtn').style.backgroundColor = '#CA8A04';
        document.getElementById('createBtn').style.color = 'white';
        document.getElementById('manageBtn').style.backgroundColor = 'transparent';
        document.getElementById('manageBtn').style.color = '#333';
        document.getElementById('searchContainer').style.display = 'none';
      } else {
        document.getElementById('createAccountSection').style.display = 'none';
        document.getElementById('manageAccountSection').style.display = 'block';
        document.getElementById('manageBtn').style.backgroundColor = '#CA8A04';
        document.getElementById('manageBtn').style.color = 'white';
        document.getElementById('createBtn').style.backgroundColor = 'transparent';
        document.getElementById('createBtn').style.color = '#333';
        document.getElementById('searchContainer').style.display = 'flex';
      }
    }

    // Placeholder functions for customer management
    function editCustomer(id) {
      document.getElementById('customerModal').style.display = 'block';
      document.getElementById('modalTitle').innerText = 'Edit Customer';
      
      // For demonstration, showing a form with customer data
      let customer = getCustomerById(id);
      document.getElementById('modalContent').innerHTML = `
        <form id="editCustomerForm">
          <div style="display: flex; flex-wrap: wrap; gap: 15px;">
            <div style="flex: 1; min-width: 250px;">
              <div class="form-group">
                <label for="editFirstName">First Name</label>
                <input type="text" id="editFirstName" name="editFirstName" value="${customer.firstName}" required>
              </div>
              <div class="form-group">
                <label for="editLastName">Last Name</label>
                <input type="text" id="editLastName" name="editLastName" value="${customer.lastName}" required>
              </div>
              <div class="form-group">
                <label for="editEmail">Email Address</label>
                <input type="email" id="editEmail" name="editEmail" value="${customer.email}" required>
              </div>
              <div class="form-group">
                <label for="editPhone">Phone Number</label>
                <input type="tel" id="editPhone" name="editPhone" value="${customer.phone}" required>
              </div>
            </div>
            <div style="flex: 1; min-width: 250px;">
              <div class="form-group">
                <label for="editAddress">Address</label>
                <input type="text" id="editAddress" name="editAddress" value="${customer.address}" required>
              </div>
              <div class="form-group">
                <label for="editCity">City</label>
                <input type="text" id="editCity" name="editCity" value="${customer.city}" required>
              </div>
              <div class="form-group">
                <label for="editState">State</label>
                <input type="text" id="editState" name="editState" value="${customer.state}" required>
              </div>
              <div class="form-group">
                <label for="editZip">Zip Code</label>
                <input type="text" id="editZip" name="editZip" value="${customer.zip}" required>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label for="editNotes">Additional Notes</label>
            <textarea id="editNotes" name="editNotes" rows="4">${customer.notes}</textarea>
          </div>
        </form>
      `;
      
      document.getElementById('modalActionButton').innerText = 'Save Changes';
      document.getElementById('modalActionButton').onclick = function() {
        saveCustomerChanges(id);
      };
    }

    function viewCustomerDetails(id) {
      document.getElementById('customerModal').style.display = 'block';
      document.getElementById('modalTitle').innerText = 'Customer Details';
      
      // For demonstration, showing customer details
      let customer = getCustomerById(id);
      document.getElementById('modalContent').innerHTML = `
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
          <div style="flex: 1; min-width: 250px;">
            <h4 style="margin-top: 0; color: #555; font-size: 16px;">Personal Information</h4>
            <p><strong>Name:</strong> ${customer.firstName} ${customer.lastName}</p>
            <p><strong>Email:</strong> ${customer.email}</p>
            <p><strong>Phone:</strong> ${customer.phone}</p>
          </div>
          <div style="flex: 1; min-width: 250px;">
            <h4 style="margin-top: 0; color: #555; font-size: 16px;">Address</h4>
            <p>${customer.address}</p>
            <p>${customer.city}, ${customer.state} ${customer.zip}</p>
          </div>
        </div>
        <div style="margin-top: 20px;">
          <h4 style="color: #555; font-size: 16px;">Notes</h4>
          <p>${customer.notes}</p>
        </div>
        <div style="margin-top: 20px;">
          <h4 style="color: #555; font-size: 16px;">Service History</h4>
          <table style="width: 100%; border-collapse: collapse;">
            <thead>
              <tr style="background-color: #f3f4f6; text-align: left;">
                <th style="padding: 8px 10px; border-bottom: 1px solid #ddd;">Date</th>
                <th style="padding: 8px 10px; border-bottom: 1px solid #ddd;">Service Type</th>
                <th style="padding: 8px 10px; border-bottom: 1px solid #ddd;">Amount</th>
                <th style="padding: 8px 10px; border-bottom: 1px solid #ddd;">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 8px 10px;">02/15/2025</td>
                <td style="padding: 8px 10px;">Memorial Service</td>
                <td style="padding: 8px 10px;">$2,500</td>
                <td style="padding: 8px 10px;"><span style="background-color: #28a745; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px;">Completed</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      `;
      
      document.getElementById('modalActionButton').innerText = 'Edit Customer';
      document.getElementById('modalActionButton').onclick = function() {
        closeModal();
        editCustomer(id);
      };
    }

    function confirmDeleteCustomer(id) {
      document.getElementById('deleteModal').style.display = 'block';
      // Store the ID for the delete operation
      document.getElementById('deleteModal').dataset.customerId = id;
    }

    function deleteCustomer() {
      const id = document.getElementById('deleteModal').dataset.customerId;
      // In a real application, this would send a request to delete the customer
      console.log(`Deleting customer with ID: ${id}`);
      
      // Close the modal and update UI
      closeDeleteModal();
      // This would typically refresh the customer list or remove the row
    }

    function closeModal() {
      document.getElementById('customerModal').style.display = 'none';
    }

    function closeDeleteModal() {
      document.getElementById('deleteModal').style.display = 'none';
    }

    function saveCustomerChanges(id) {
      // In a real application, this would save the customer data
      console.log(`Saving changes for customer with ID: ${id}`);
      closeModal();
      // Show a success message or refresh the table
    }

    function searchCustomers() {
      const searchTerm = document.getElementById('searchCustomer').value.toLowerCase();
      // In a real application, this would filter the customers table
      console.log(`Searching for: ${searchTerm}`);
    }

    // Helper function to get customer by ID (mock data for demonstration)
    function getCustomerById(id) {
      const customers = {
        1: {
          firstName: 'Jane',
          lastName: 'Smith',
          email: 'jane.smith@example.com',
          phone: '(555) 123-4567',
          address: '123 Main St',
          city: 'Austin',
          state: 'TX',
          zip: '78701',
          notes: 'Prefers email communication. Has existing arrangements for spouse.'
        },
        2: {
          firstName: 'Robert',
          lastName: 'Johnson',
          email: 'robert.j@example.com',
          phone: '(555) 987-6543',
          address: '456 Oak Ave',
          city: 'Dallas',
          state: 'TX',
          zip: '75201',
          notes: 'Requested information about pre-planning services.'
        },
        3: {
          firstName: 'Mary',
          lastName: 'Williams',
          email: 'mary.w@example.com',
          phone: '(555) 456-7890',
          address: '789 Pine Blvd',
          city: 'Houston',
          state: 'TX',
          zip: '77002',
          notes: 'Recent client. Family has multiple accounts.'
        }
      };
      return customers[id] || {};
    }

    // Toggle sidebar function (assuming it's defined in the original file)
    function toggleSidebar() {
      document.querySelector('.sidebar').classList.toggle('collapsed');
      document.querySelector('.main-content').classList.toggle('expanded');
    }

    // Form submission handler
    document.getElementById('customerAccountForm').addEventListener('submit', function(e) {
      e.preventDefault();
      // In a real application, this would save the new customer
      alert('Customer account created successfully!');
      this.reset();
    });
  </script>
  <script src="sidebar.js"></script>
  <script src="tailwind.js"></script>
</body>
</html>