<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Employee Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
    
    .main-content {
      margin-left: 16rem;
      width: calc(100% - 16rem);
      z-index: 1;
    }
    
    .sidebar {
      z-index: 10;
    }
    
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
    
    /* Mobile sidebar styles */
    @media (max-width: 768px) {
      #sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100%;
        width: 16rem !important;
        z-index: 50;
        transform: translateX(-100%);
        transition: transform 0.3s ease;
      }
      
      #sidebar.mobile-open {
        transform: translateX(0);
      }
      
      .main-content {
        margin-left: 0 !important;
        width: 100% !important;
      }
    }
  </style>
</head>
<body class="flex bg-gray-50">
  <!-- Mobile Hamburger Menu Button -->
  <button id="mobile-hamburger-button" class="md:hidden fixed top-4 left-4 z-50 p-2 bg-white rounded-lg shadow-md text-gray-600 hover:text-gray-900 transition-all duration-300">
    <i class="fas fa-bars"></i>
  </button>

  <!-- Sidebar -->
  <nav id="sidebar" class="w-64 h-screen sidebar-bg font-hedvig fixed transition-all duration-300 overflow-y-auto z-40 scrollbar-thin shadow-sidebar">
    <!-- Logo and Header with hamburger menu -->
    <div class="flex items-center px-5 py-6 border-b sidebar-border">
      <button id="hamburger-menu" class="p-2 mr-2 bg-white rounded-lg shadow-md text-gray-600 hover:text-gray-900 transition-all duration-300">
        <i class="fas fa-bars"></i>
      </button>
      <div id="logo-text" class="text-2xl font-cinzel font-bold sidebar-accent">GrievEase</div>
    </div>
      
    <!-- User Profile -->
    <div class="flex items-center px-5 py-4 border-b sidebar-border bg-gradient-to-r from-navy to-primary">
      <div class="w-10 h-10 rounded-full bg-yellow-600 flex items-center justify-center shadow-md">
        <span class="text-white font-medium">JD</span>
      </div>
      <div class="ml-3">
        <div class="text-sm font-medium sidebar-text capitalize">John Doe</div>
        <div class="text-xs sidebar-text opacity-70">Employee</div>
      </div>
      <div class="ml-auto">
        <span class="w-3 h-3 success rounded-full block"></span>
      </div>
    </div>
      
    <!-- Menu Items -->
    <div class="pt-4 pb-8">
      <!-- Main Navigation -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium sidebar-accent uppercase tracking-wider">Main</h5>
      </div>
      <ul class="list-none p-0 mb-6">
        <li>
          <a href="index.php" class="sidebar-link flex items-center px-5 py-3 sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:sidebar-hover active">
            <i class="fas fa-tachometer-alt w-5 text-center mr-3 sidebar-accent"></i>
            <span>Dashboard</span>
          </a>
        </li> 
        <li>
          <a href="employee_customer_account_creation.php" class="sidebar-link flex items-center px-5 py-3 sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:sidebar-hover">
            <i class="fas fa-user-circle w-5 text-center mr-3 sidebar-accent"></i>
            <span>Customer Account Management</span>
          </a>
        </li>
        <li>
          <a href="employee_inventory.php" class="sidebar-link flex items-center px-5 py-3 sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:sidebar-hover">
            <i class="fas fa-boxes w-5 text-center mr-3 sidebar-accent"></i>
            <span>View Inventory</span>
          </a>
        </li>
        <li>
          <a href="employee_pos.php" class="sidebar-link flex items-center px-5 py-3 sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:sidebar-hover">
            <i class="fas fa-cash-register w-5 text-center mr-3 sidebar-accent"></i>
            <span>Point-Of-Sale (POS)</span>
          </a>
        </li>
      </ul>
        
      <!-- Reports & Analytics -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium sidebar-accent uppercase tracking-wider">Reports & Analytics</h5>
      </div>
      <ul class="list-none p-0 mb-6">
        <li>
          <a href="employee_expenses.php" class="sidebar-link flex items-center px-5 py-3 sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:sidebar-hover">
            <i class="fas fa-money-bill-wave w-5 text-center mr-3 sidebar-accent"></i>
            <span>Expenses</span>
          </a>
        </li>
        <li>
          <a href="employee_history.php" class="sidebar-link flex items-center px-5 py-3 sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:sidebar-hover">
            <i class="fas fa-history w-5 text-center mr-3 sidebar-accent"></i>
            <span>Service History</span>
          </a>
        </li>
      </ul>
        
      <!-- Services & Staff -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium sidebar-accent uppercase tracking-wider">Communication</h5>
      </div>
      <ul class="list-none p-0 mb-6">
        <li>
          <a href="employee_chat.php" class="sidebar-link flex items-center px-5 py-3 sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:sidebar-hover">
            <i class="fas fa-comments w-5 text-center mr-3 sidebar-accent"></i>
            <span>Chats</span>
          </a>
        </li>
      </ul>
        
      <!-- Account -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium sidebar-accent uppercase tracking-wider">Account</h5>
      </div>
      <ul class="list-none p-0">
        <li>
          <a href="employee_settings.php" class="sidebar-link flex items-center px-5 py-3 sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:sidebar-hover">
            <i class="fas fa-cog w-5 text-center mr-3 sidebar-accent"></i>
            <span>Settings</span>
          </a>
        </li>
        <li>
          <a href="../logout.php" class="sidebar-link flex items-center px-5 py-3 sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:sidebar-hover error">
            <i class="fas fa-sign-out-alt w-5 text-center mr-3 error"></i>
            <span>Logout</span>
          </a>
        </li>
      </ul>
    </div>
      
    <!-- Footer -->
    <div class="relative bottom-0 left-0 right-0 px-5 py-3 border-t sidebar-border bg-gradient-to-r from-navy to-primary">
      <div class="flex justify-between items-center">
        <p class="text-xs sidebar-text opacity-60">© 2025 GrievEase</p>
        <div class="text-xs sidebar-accent">
          <i class="fas fa-heart"></i> With Compassion
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
    <!-- Header with breadcrumb and welcome message -->
    <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
      <div>
        <h1 class="text-2xl font-bold sidebar-text">Employee Dashboard</h1>
        <p class="text-sm text-gray-500">
          Welcome back, 
          <span class="hidden md:inline">John Doe</span>
        </p>
      </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-8">
      <!-- Services This Month Card -->
      <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden cursor-pointer">
        <div class="bg-gradient-to-r from-blue-100 to-blue-200 px-4 sm:px-6 py-3 sm:py-4">
          <div class="flex items-center justify-between mb-1">
            <h3 class="text-xs sm:text-sm font-medium text-gray-700 leading-tight">Services This Month</h3>
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-white/90 text-slate-600 flex items-center justify-center">
              <i class="fas fa-calendar-alt text-sm sm:text-base"></i>
            </div>
          </div>
          <div class="flex items-end">
            <span class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">42</span>
          </div>
        </div>
        
        <div class="px-4 sm:px-6 py-2 sm:py-3 bg-white border-t border-gray-100">
          <div class="flex items-center text-emerald-600">
            <i class="fas fa-arrow-up mr-1 sm:mr-1.5 text-xs"></i>
            <span class="font-medium text-xs">2% </span>
            <span class="text-xs text-gray-500 ml-1">from last week</span>
          </div>
        </div>
      </div>
          
      <!-- Monthly Revenue Card -->
      <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden cursor-pointer">
        <div class="bg-gradient-to-r from-green-100 to-green-200 px-4 sm:px-6 py-3 sm:py-4">
          <div class="flex items-center justify-between mb-1">
            <h3 class="text-xs sm:text-sm font-medium text-gray-700 leading-tight">Monthly Revenue</h3>
            <div class="flex items-center gap-2">
              <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-white/90 text-green-600 flex items-center justify-center">
                <i class="fas fa-peso-sign text-sm sm:text-base"></i>
              </div>
              <div class="relative">
                <button id="revenue-toggle" class="p-1 bg-white/90 rounded-full flex items-center text-xs">
                  <span id="revenue-type" class="px-2">Cash</span>
                  <i class="fas fa-chevron-down ml-1"></i>
                </button>
                <div id="revenue-dropdown" class="absolute right-0 mt-1 w-24 bg-white rounded-md shadow-lg hidden z-10">
                  <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="toggleRevenue('cash')">Cash</button>
                  <button class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" onclick="toggleRevenue('accrual')">Accrual</button>
                </div>
              </div>
            </div>
          </div>
          <div class="flex items-end">
            <div id="cash-revenue" class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">₱12,345.67</div>
            <div id="accrual-revenue" class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800 hidden">₱15,678.90</div>
          </div>
        </div>
        
        <div class="px-4 sm:px-6 py-2 sm:py-3 bg-white border-t border-gray-100">
          <div class="flex items-center text-emerald-600">
            <i class="fas fa-arrow-up mr-1 sm:mr-1.5 text-xs"></i>
            <span class="font-medium text-xs">5% </span>
            <span class="text-xs text-gray-500 ml-1">from yesterday</span>
          </div>
        </div>
      </div>
          
      <!-- Ongoing Services Card -->
      <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden cursor-pointer">
        <div class="bg-gradient-to-r from-orange-100 to-orange-200 px-4 sm:px-6 py-3 sm:py-4">
          <div class="flex items-center justify-between mb-1">
            <h3 class="text-xs sm:text-sm font-medium text-gray-700 leading-tight">Ongoing Services</h3>
            <div class="w-8 h-8 sm:w-10 sm:h-10 rounded-full bg-white/90 text-orange-600 flex items-center justify-center">
              <i class="fas fa-tasks text-sm sm:text-base"></i>
            </div>
          </div>
          <div class="flex items-end">
            <span class="text-xl sm:text-2xl md:text-3xl font-bold text-gray-800">8</span>
          </div>
        </div>
        
        <div class="px-4 sm:px-6 py-2 sm:py-3 bg-white border-t border-gray-100">
          <div class="flex items-center text-rose-600">
            <i class="fas fa-arrow-down mr-1 sm:mr-1.5 text-xs"></i>
            <span class="font-medium text-xs">1 </span>
            <span class="text-xs text-gray-500 ml-1">task added</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <footer class="bg-white rounded-lg shadow-sidebar border sidebar-border p-4 text-center text-sm text-gray-500 mt-8">
      <p>© 2025 GrievEase.</p>
    </footer>
  </div>

  <script>
    // Mobile hamburger menu functionality
    document.addEventListener('DOMContentLoaded', function() {
      const mobileHamburgerButton = document.getElementById('mobile-hamburger-button');
      const sidebarHamburgerMenu = document.getElementById('hamburger-menu');
      const sidebar = document.getElementById('sidebar');
      const mainContent = document.getElementById('main-content');
      
      // Toggle sidebar on mobile hamburger button click
      if (mobileHamburgerButton) {
        mobileHamburgerButton.addEventListener('click', function() {
          sidebar.classList.toggle('mobile-open');
        });
      }
      
      // Toggle sidebar on desktop hamburger menu click
      if (sidebarHamburgerMenu) {
        sidebarHamburgerMenu.addEventListener('click', function() {
          if (window.innerWidth >= 768) {
            toggleDesktopSidebar();
          } else {
            sidebar.classList.toggle('mobile-open');
          }
        });
      }
      
      // Close sidebar when clicking outside on mobile
      document.addEventListener('click', function(event) {
        if (window.innerWidth < 768 && 
            sidebar.classList.contains('mobile-open') &&
            !sidebar.contains(event.target) && 
            !mobileHamburgerButton.contains(event.target)) {
          sidebar.classList.remove('mobile-open');
        }
      });
      
      // Desktop sidebar toggle function
      function toggleDesktopSidebar() {
        if (sidebar.classList.contains('w-64')) {
          // Collapse the sidebar
          sidebar.classList.remove('w-64');
          sidebar.classList.add('w-16');
          
          // Keep icons visible but hide text in navigation links
          document.querySelectorAll('.sidebar-link span').forEach(el => {
            el.classList.add('hidden');
          });
          
          // Hide menu section headers
          document.querySelectorAll('.menu-header').forEach(el => {
            el.classList.add('hidden');
          });
          
          // Hide elements except hamburger menu in the header
          const headerElements = document.querySelectorAll('#sidebar > div:first-child > *:not(#hamburger-menu)');
          headerElements.forEach(el => {
            el.classList.add('hidden');
          });
          
          // Hide user profile text but keep the icon
          const userProfileText = document.querySelectorAll('#sidebar > div:nth-child(2) > div:not(:first-child)');
          userProfileText.forEach(el => {
            el.classList.add('hidden');
          });
          
          // Center the navigation icons when collapsed
          document.querySelectorAll('.sidebar-link').forEach(el => {
            el.classList.add('justify-center');
            el.classList.remove('px-5');
            el.classList.add('px-0');
          });
          
          // Expand the main content to fill the space
          mainContent.classList.remove('ml-64');
          mainContent.classList.add('ml-16');
          mainContent.classList.remove('w-[calc(100%-16rem)]');
          mainContent.classList.add('w-[calc(100%-4rem)]');
          
          // Change the hamburger menu icon to a right arrow
          sidebarHamburgerMenu.innerHTML = '<i class="fas fa-chevron-right"></i>';
        } else {
          // Expand the sidebar
          sidebar.classList.remove('w-16');
          sidebar.classList.add('w-64');
          
          // Show all text in navigation links
          document.querySelectorAll('.sidebar-link span').forEach(el => {
            el.classList.remove('hidden');
          });
          
          // Show menu section headers
          document.querySelectorAll('.menu-header').forEach(el => {
            el.classList.remove('hidden');
          });
          
          // Show logo and title in the header
          const headerElements = document.querySelectorAll('#sidebar > div:first-child > *');
          headerElements.forEach(el => {
            el.classList.remove('hidden');
          });
          
          // Show user profile text
          const userProfileText = document.querySelectorAll('#sidebar > div:nth-child(2) > div');
          userProfileText.forEach(el => {
            el.classList.remove('hidden');
          });
          
          // Restore original navigation link styling
          document.querySelectorAll('.sidebar-link').forEach(el => {
            el.classList.remove('justify-center');
            el.classList.remove('px-0');
            el.classList.add('px-5');
          });

          // Adjust the main content to its original size
          mainContent.classList.remove('ml-16');
          mainContent.classList.add('ml-64');
          mainContent.classList.remove('w-[calc(100%-4rem)]');
          mainContent.classList.add('w-[calc(100%-16rem)]');

          // Change the right arrow icon back to a hamburger menu
          sidebarHamburgerMenu.innerHTML = '<i class="fas fa-bars"></i>';
        }
      }
      
      // Revenue Toggle Functionality
      document.getElementById('revenue-toggle').addEventListener('click', function(e) {
        e.stopPropagation();
        const dropdown = document.getElementById('revenue-dropdown');
        dropdown.classList.toggle('hidden');
      });

      function toggleRevenue(type) {
        const cashElement = document.getElementById('cash-revenue');
        const accrualElement = document.getElementById('accrual-revenue');
        const typeElement = document.getElementById('revenue-type');
        const dropdown = document.getElementById('revenue-dropdown');
        
        if (type === 'cash') {
          cashElement.classList.remove('hidden');
          accrualElement.classList.add('hidden');
          typeElement.textContent = 'Cash';
        } else {
          cashElement.classList.add('hidden');
          accrualElement.classList.remove('hidden');
          typeElement.textContent = 'Accrual';
        }
        
        dropdown.classList.add('hidden');
      }

      // Close dropdown when clicking outside
      document.addEventListener('click', function(event) {
        const dropdown = document.getElementById('revenue-dropdown');
        const toggle = document.getElementById('revenue-toggle');
        
        if (!toggle.contains(event.target) && !dropdown.contains(event.target)) {
          dropdown.classList.add('hidden');
        }
      });
      
      // Handle window resize
      window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
          // On desktop, ensure sidebar is visible and not in mobile mode
          sidebar.classList.remove('mobile-open');
          if (sidebar.classList.contains('w-16')) {
            // If sidebar is collapsed, keep it that way
            mainContent.classList.remove('ml-64');
            mainContent.classList.add('ml-16');
            mainContent.classList.remove('w-[calc(100%-16rem)]');
            mainContent.classList.add('w-[calc(100%-4rem)]');
          } else {
            // If sidebar is expanded, keep it that way
            mainContent.classList.remove('ml-16');
            mainContent.classList.add('ml-64');
            mainContent.classList.remove('w-[calc(100%-4rem)]');
            mainContent.classList.add('w-[calc(100%-16rem)]');
          }
        } else {
          // On mobile, ensure sidebar is hidden by default
          sidebar.classList.remove('mobile-open');
          mainContent.classList.remove('ml-16', 'ml-64');
          mainContent.classList.add('ml-0');
          mainContent.classList.remove('w-[calc(100%-4rem)]', 'w-[calc(100%-16rem)]');
          mainContent.classList.add('w-full');
        }
      });
    });
  </script>
</body>
</html>