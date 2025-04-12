  <?php
  
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
  ?>

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

  /* Table adjustments */
  .responsive-table {
    overflow-x: auto;
  }
  .responsive-table table {
    width: 100%;
    min-width: 600px; /* Ensure table has a minimum width */
  }
  .responsive-table th,
  .responsive-table td {
    padding: 12px; /* Increased padding for better readability */
    font-size: 14px; /* Adjusted font size */
  }

  /* Modal adjustments */
  .modal-content {
    width: 90%; /* Increased modal width */
    max-width: 800px; /* Maximum width for larger screens */
  }

  /* Sidebar adjustments */
  .sidebar {
    z-index: 40; /* Higher z-index to ensure visibility */
  }
  #sidebar {
    transition: transform 0.3s ease, width 0.3s ease, opacity 0.3s ease, visibility 0.3s ease;
  }

  /* Main content adjustments */
  .main-content {
    transition: margin-left 0.3s ease, width 0.3s ease, opacity 0.3s ease;
  }
  .sidebar-link span, 
  .menu-header, 
  #sidebar > div:first-child > *:not(#hamburger-menu), 
  #sidebar > div:nth-child(2) > div:not(:first-child) {
    transition: opacity 0.3s ease, visibility 0.3s ease;
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
      transform: translateX(0);
    }
    
    #sidebar.translate-x-0 {
      transform: translateX(0);
    }
    
    #sidebar.-translate-x-full {
      transform: translateX(-100%);
    }
    
    #hamburger-menu {
      display: block !important;
    }
    
    .main-content {
      margin-left: 0 !important;
      width: 100% !important;
    }
    
    #mobile-hamburger {
      display: block;
    }
  }
  
  /* Add these CSS rules to ensure only one hamburger is visible */
  @media (max-width: 768px) {
    #hamburger-menu {
      display: none !important; /* Force hide the sidebar hamburger */
    }
    
    #mobile-hamburger {
      display: block !important; /* Force show the mobile hamburger */
    }
    /* Container for the logo and hamburger menu */
    #sidebar > div:first-child {
      display: flex;
      align-items: center;
      position: relative;
      /* Preserve the original padding */
      padding: 1.5rem 1.25rem; /* py-6 px-5 in Tailwind equivalent */
    }
    
    /* Logo styling for mobile */
    #logo-text {
      width: 100%;
      text-align: center;
      /* Make sure it's visible */
      display: block !important;
      /* Remove any margins that might affect centering */
      margin: 0 auto;
    }
    
    /* Make sure hamburger menu doesn't interfere with centering */
    #hamburger-menu {
      position: absolute;
      left: 1.25rem;
    }
  }

  @media (min-width: 769px) {
    #mobile-hamburger {
      display: none !important; /* Force hide the mobile hamburger */
    }
    
    #hamburger-menu {
      display: block !important; /* Force show the sidebar hamburger */
    }
  }
  @media (min-width: 769px) {
    #mobile-hamburger {
      display: none;
    }
  }
</style>

  <!-- Sidebar Navigation -->
  <nav id="sidebar" class="w-64 h-screen bg-sidebar-bg font-hedvig fixed transition-all duration-300 overflow-y-auto z-40 scrollbar-thin shadow-sidebar">
    <!-- Logo and Header with hamburger menu -->
    <div class="flex items-center px-5 py-6 border-b border-sidebar-border">
      <button id="hamburger-menu" class="p-2 mr-2 bg-white rounded-lg shadow-md text-gray-600 hover:text-gray-900 transition-all duration-300">
        <i class="fas fa-bars"></i>
      </button>
      <!-- <img src="../Landing_Page/Landing_images/logo.png" alt="GrievEase Logo" class="h-10 w-auto mr-3"> -->
      <div id="logo-text" class="text-2xl font-cinzel font-bold text-sidebar-accent">GrievEase</div>
    </div>
      
    <!-- User Profile -->
    <div class="flex items-center px-5 py-4 border-b border-sidebar-border bg-gradient-to-r from-navy to-primary">
      <div class="w-10 h-10 rounded-full bg-yellow-600 flex items-center justify-center shadow-md">
        <i class="fas fa-user text-white"></i>
      </div>
      <div class="ml-3">
        <div class="text-sm font-medium text-sidebar-text">
          <span class="md:inline text-sm">
            <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
          </span>
        </div>
        <div class="text-xs text-sidebar-text opacity-70">Administrator</div>
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
          <a href="admin_index.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-tachometer-alt w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Dashboard</span>
          </a>
        </li> 
        <li>
          <a href="account_management.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-user-circle w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Account Management</span>
          </a>
        </li>
        <li>
          <a href="inventory_management.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-boxes w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Inventory</span>
          </a>
        </li>
        <li>
          <a href="pos.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-cash-register w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Point-Of-Sale (POS)</span>
          </a>
        </li>
        <li>
          <a href="Booking_acceptance.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover relative">
            <i class="fas fa-clipboard w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Bookings</span>
            <!-- Notification Badge -->
            <span id="booking-badge" class="absolute right-4 bg-red-500 text-white text-xs font-bold w-5 h-5 flex items-center justify-center rounded-full">
              3
            </span>
          </a>
        </li>
        <!-- New: LifePlan Menu Item -->
        <li>
          <a href="lifeplan.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-heartbeat w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>LifePlan</span>
          </a>
        </li>
        <!-- New: ID Confirmation Menu Item -->
        <li>
          <a href="id_confirmation.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover relative">
            <i class="fas fa-id-card w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>ID Confirmation</span>
            <!-- Notification Badge (optional) -->
            <span id="id-badge" class="absolute right-4 bg-red-500 text-white text-xs font-bold w-5 h-5 flex items-center justify-center rounded-full hidden">
              0
            </span>
          </a>
        </li>
      </ul>
        
      <!-- Reports & Analytics -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Reports & Analytics</h5>
      </div>
      <ul class="list-none p-0 mb-6">
        <li>
          <a href="reports_dashboard.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-chart-bar w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Reports</span>
          </a>
        </li>
        <li>
          <a href="expense_management.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-money-bill-wave w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Expenses</span>
          </a>
        </li>
        <li>
          <a href="history.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-history w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>History</span>
          </a>
        </li>
      </ul>
        
      <!-- Services & Staff -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Services & Staff</h5>
      </div>
      <ul class="list-none p-0 mb-6">
        <li>
          <a href="service_management.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-hands-helping w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Services</span>
          </a>
        </li>
        <li>
          <a href="employee_management.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-users w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Employees</span>
          </a>
        </li>
        <li>
          <a href="communication_chat.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
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
          <a href="../logout.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover hover:text-error">
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


  <script src="sidebar.js"></script>

  <script>
    // Function to load content via AJAX
  function loadPage(pageUrl) {
      // Show loading indicator if needed
      document.getElementById('main-content').innerHTML = '<div class="flex justify-center items-center h-full">Loading...</div>';
      
      // Fetch the new page content
      fetch(pageUrl)
          .then(response => response.text())
          .then(html => {
              // Parse the response to extract just the main content
              const parser = new DOMParser();
              const doc = parser.parseFromString(html, 'text/html');
              const newContent = doc.getElementById('main-content').innerHTML;
              
              // Update the main content area
              document.getElementById('main-content').innerHTML = newContent;
              
              // Update the URL in the browser without reload
              window.history.pushState({page: pageUrl}, '', pageUrl);
              
              // Update active sidebar link
              updateActiveSidebarLink();
              
              // Reinitialize any scripts that need to run on page load
              initializePageScripts();
          })
          .catch(error => {
              console.error('Error loading page:', error);
              document.getElementById('main-content').innerHTML = '<div class="text-red-500">Error loading page. Please try again.</div>';
          });
  }

  // Function to initialize page-specific scripts
  function initializePageScripts() {
      // This will vary based on your page content
      // For example, if you have charts or other JS that needs initialization
      if (typeof initCharts === 'function') {
          initCharts();
      }
      if (typeof initDataTables === 'function') {
          initDataTables();
      }
  }

  // Handle navigation clicks
  document.addEventListener('click', function(e) {
      const link = e.target.closest('[data-page]');
      if (link) {
          e.preventDefault();
          const pageUrl = link.getAttribute('data-page');
          loadPage(pageUrl);
      }
  });

  // Handle browser back/forward buttons
  window.addEventListener('popstate', function(e) {
      if (e.state && e.state.page) {
          loadPage(e.state.page);
      } else {
          // Fallback to loading the current URL
          loadPage(window.location.pathname);
      }
  });

  // Update the active sidebar link function
  function updateActiveSidebarLink() {
      const currentPage = window.location.pathname.split('/').pop();
      const sidebarLinks = document.querySelectorAll('.sidebar-link');
      
      sidebarLinks.forEach(link => {
          link.classList.remove('active');
          const linkPage = link.getAttribute('data-page');
          if (linkPage && linkPage.includes(currentPage)) {
              link.classList.add('active');
          }
      });
  }

  </script>