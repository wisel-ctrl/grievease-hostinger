<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inventory - GrievEase</title>
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
          <a href="employee_history.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
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
      <h1 class="text-2xl font-bold text-sidebar-text">View Inventory</h1>
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

  <!-- Inventory Table -->
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
    <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
      <h3 class="font-medium text-sidebar-text">Inventory Items</h3>
      <div class="flex items-center gap-3">
        <div class="relative">
          <input type="text" placeholder="Search inventory..." class="pl-9 pr-4 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
          <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <i class="fas fa-search text-gray-400"></i>
          </div>
        </div>
        <button class="px-4 py-2 bg-sidebar-accent text-white rounded-md text-sm flex items-center hover:bg-darkgold transition-all duration-300">
          <i class="fas fa-plus mr-2"></i> Add Item
        </button>
      </div>
    </div>
    <div class="overflow-x-auto scrollbar-thin">
      <table class="w-full">
        <thead>
          <tr class="bg-sidebar-hover">
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">
              <div class="flex items-center">
                Item <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">
              <div class="flex items-center">
                Category <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">
              <div class="flex items-center">
                Stock Level <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">
              <div class="flex items-center">
                Status <i class="fas fa-sort ml-1 text-gray-400"></i>
              </div>
            </th>
            <th class="p-4 text-left text-sm font-medium text-sidebar-text">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text font-medium">Oak Casket - Premium</td>
            <td class="p-4 text-sm text-sidebar-text">Caskets</td>
            <td class="p-4 text-sm text-sidebar-text">5</td>
            <td class="p-4 text-sm">
              <span class="px-2 py-1 bg-red-100 text-red-600 rounded-full text-xs">Low Stock</span>
            </td>
            <td class="p-4 text-sm">
              <div class="flex space-x-2">
                <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text font-medium">Floral Arrangement - Classic</td>
            <td class="p-4 text-sm text-sidebar-text">Flowers</td>
            <td class="p-4 text-sm text-sidebar-text">15</td>
            <td class="p-4 text-sm">
              <span class="px-2 py-1 bg-green-100 text-green-600 rounded-full text-xs">In Stock</span>
            </td>
            <td class="p-4 text-sm">
              <div class="flex space-x-2">
                <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text font-medium">Memorial Urns - Bronze</td>
            <td class="p-4 text-sm text-sidebar-text">Urns</td>
            <td class="p-4 text-sm text-sidebar-text">8</td>
            <td class="p-4 text-sm">
              <span class="px-2 py-1 bg-green-100 text-green-600 rounded-full text-xs">In Stock</span>
            </td>
            <td class="p-4 text-sm">
              <div class="flex space-x-2">
                <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text font-medium">Printed Programs - Standard</td>
            <td class="p-4 text-sm text-sidebar-text">Stationery</td>
            <td class="p-4 text-sm text-sidebar-text">100</td>
            <td class="p-4 text-sm">
              <span class="px-2 py-1 bg-green-100 text-green-600 rounded-full text-xs">In Stock</span>
            </td>
            <td class="p-4 text-sm">
              <div class="flex space-x-2">
                <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </td>
          </tr>
          <tr class="hover:bg-sidebar-hover">
            <td class="p-4 text-sm text-sidebar-text font-medium">Guest Book - Deluxe</td>
            <td class="p-4 text-sm text-sidebar-text">Stationery</td>
            <td class="p-4 text-sm text-sidebar-text">2</td>
            <td class="p-4 text-sm">
              <span class="px-2 py-1 bg-red-100 text-red-600 rounded-full text-xs">Low Stock</span>
            </td>
            <td class="p-4 text-sm">
              <div class="flex space-x-2">
                <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all">
                  <i class="fas fa-eye"></i>
                </button>
                <button class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all">
                  <i class="fas fa-edit"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
      <div class="text-sm text-gray-500">Showing 5 of 25 items</div>
      <div class="flex space-x-1">
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&laquo;</button>
        <button class="px-3 py-1 bg-sidebar-accent text-white rounded text-sm">1</button>
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">2</button>
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">3</button>
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&raquo;</button>
      </div>
    </div>
  </div>

</div>
  
  <script src="sidebar.js"></script>
  <script src="tailwind.js"></script>
</body>
</html>