  <?php
  
  require_once '../db_connect.php'; // Database connection

  // Get user's first name from database
  $user_id = $_SESSION['user_id'];
    $query = "SELECT first_name, last_name, email, birthdate, profile_picture FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $first_name = $row['first_name'];
    $last_name = $row['last_name'];
    $email = $row['email'];
    $profile_picture = $row['profile_picture'] ? '../' . $row['profile_picture'] : '../default.png';
  ?>

<?php
// Get count of unvalidated IDs
$id_validation_count = 0;
$count_query3 = "SELECT COUNT(*) AS validation_count FROM valid_id_tb WHERE is_validated = 'no'";
$count_stmt3 = $conn->prepare($count_query3);
$count_stmt3->execute();
$result3 = $count_stmt3->get_result();
if ($result3->num_rows > 0) {
    $row3 = $result3->fetch_assoc();
    $id_validation_count = $row3['validation_count'];
}

// First, get the count of pending bookings
$pending_count = 0;

// Count pending from booking_tb
$count_query1 = "SELECT COUNT(*) AS pending_count FROM booking_tb WHERE status = 'Pending'";
$count_stmt1 = $conn->prepare($count_query1);
$count_stmt1->execute();
$result1 = $count_stmt1->get_result();
if ($result1->num_rows > 0) {
    $row1 = $result1->fetch_assoc();
    $pending_count += $row1['pending_count'];
}

// Count pending from lifeplan_booking_tb
$count_query2 = "SELECT COUNT(*) AS pending_count FROM lifeplan_booking_tb WHERE booking_status = 'Pending'";
$count_stmt2 = $conn->prepare($count_query2);
$count_stmt2->execute();
$result2 = $count_stmt2->get_result();
if ($result2->num_rows > 0) {
    $row2 = $result2->fetch_assoc();
    $pending_count += $row2['pending_count'];
}

// Get the count of unique chatRoomIds where status is 'sent'
$sent_count = 0;
$count_query = "SELECT COUNT(DISTINCT chatRoomId) AS sent_count FROM chat_messages WHERE status = 'sent'";
$count_stmt = $conn->prepare($count_query);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
if ($count_result->num_rows > 0) {
    $count_row = $count_result->fetch_assoc();
    $sent_count = $count_row['sent_count'];
}
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

  /* Profile section collapsed state improvements */
.profile-collapsed {
  justify-content: center !important;
  padding: 1rem 0.5rem !important;
  border-bottom: 1px solid rgba(229, 231, 235, 0.5) !important;
  position: relative;
  cursor: pointer;
  transition: background-color 0.3s ease;
}

.profile-collapsed:hover {
  background-color: rgba(202, 138, 4, 0.05) !important;
}

.profile-pic-collapsed {
  display: flex !important;
  justify-content: center !important;
  align-items: center !important;
  width: 100% !important;
  margin: 0 !important;
}

.profile-pic-collapsed .w-10.h-10 {
  width: 2.5rem !important;
  height: 2.5rem !important;
  border: 2px solid #CA8A04 !important;
  box-shadow: 0 2px 8px rgba(202, 138, 4, 0.3) !important;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
  position: relative;
}

.profile-pic-collapsed .w-10.h-10:hover {
  transform: scale(1.1) !important;
  box-shadow: 0 4px 12px rgba(202, 138, 4, 0.4) !important;
}

/* Online status indicator for collapsed state */
.profile-collapsed .ml-auto {
  position: absolute !important;
  bottom: -2px !important;
  right: calc(50% - 1.25rem + 6px) !important;
  margin: 0 !important;
}

.profile-collapsed .ml-auto .w-3.h-3 {
  width: 0.75rem !important;
  height: 0.75rem !important;
  border: 2px solid white !important;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3) !important;
}

/* Tooltip styles for collapsed sidebar */
.w-16 .sidebar-link {
  position: relative;
}

.w-16 .sidebar-link::after {
  content: attr(data-tooltip);
  position: absolute;
  left: 100%;
  top: 50%;
  transform: translateY(-50%);
  background-color: #1f2937;
  color: white;
  padding: 0.5rem 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  white-space: nowrap;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
  z-index: 1000;
  margin-left: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  pointer-events: none;
}

.w-16 .sidebar-link::before {
  content: '';
  position: absolute;
  left: 100%;
  top: 50%;
  transform: translateY(-50%);
  border: 5px solid transparent;
  border-right-color: #1f2937;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease, visibility 0.3s ease;
  z-index: 1000;
  margin-left: 0.25rem;
  pointer-events: none;
}

.w-16 .sidebar-link:hover::after,
.w-16 .sidebar-link:hover::before {
  opacity: 1;
  visibility: visible;
}

.w-16 .sidebar-link:hover::after {
  transform: translateY(-50%) translateX(4px);
}

/* Profile picture tooltip in collapsed state */
.profile-collapsed::after {
  content: attr(data-admin-name) " - Administrator";
  position: absolute;
  left: 100%;
  top: 50%;
  transform: translateY(-50%);
  background-color: #1f2937;
  color: white;
  padding: 0.5rem 0.75rem;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  font-weight: 500;
  white-space: nowrap;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
  z-index: 1000;
  margin-left: 0.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  pointer-events: none;
  min-width: max-content;
}

.profile-collapsed::before {
  content: '';
  position: absolute;
  left: 100%;
  top: 50%;
  transform: translateY(-50%);
  border: 5px solid transparent;
  border-right-color: #1f2937;
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.3s ease, visibility 0.3s ease;
  z-index: 1000;
  margin-left: 0.25rem;
  pointer-events: none;
}

.profile-collapsed:hover::after,
.profile-collapsed:hover::before {
  opacity: 1;
  visibility: visible;
}

.profile-collapsed:hover::after {
  transform: translateY(-50%) translateX(4px);
}

/* Enhanced notification badges positioning for collapsed state */
.w-16 .sidebar-link .absolute {
  right: -0.25rem !important;
  top: 0.25rem !important;
  transform: scale(0.85) !important;
  z-index: 10;
}

/* Smooth animations for all transitions */
#sidebar * {
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Improved focus states for accessibility */
.sidebar-link:focus-visible {
  outline: 2px solid #CA8A04;
  outline-offset: 2px;
  border-radius: 0.25rem;
}

.profile-collapsed:focus-visible {
  outline: 2px solid #CA8A04;
  outline-offset: 2px;
  border-radius: 0.25rem;
}

/* Loading state for profile picture */
.profile-pic-collapsed .w-10.h-10 img {
  transition: opacity 0.3s ease;
}

.profile-pic-collapsed .w-10.h-10 img[src=""] {
  opacity: 0;
}

/* Responsive improvements */
@media (max-width: 768px) {
  /* Hide tooltips on mobile */
  .sidebar-link::after,
  .sidebar-link::before,
  .profile-collapsed::after,
  .profile-collapsed::before {
    display: none !important;
  }
  
  /* Ensure profile section works well on mobile */
  .profile-collapsed {
    padding: 1rem !important;
  }
  
  .profile-pic-collapsed .w-10.h-10 {
    width: 2.25rem !important;
    height: 2.25rem !important;
  }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .profile-pic-collapsed .w-10.h-10 {
    border-width: 3px !important;
  }
  
  .w-16 .sidebar-link::after,
  .profile-collapsed::after {
    background-color: #000 !important;
    border: 1px solid #fff !important;
  }
  
  .w-16 .sidebar-link::before,
  .profile-collapsed::before {
    border-right-color: #000 !important;
  }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .profile-pic-collapsed .w-10.h-10,
  .sidebar-link,
  .profile-collapsed,
  #sidebar,
  #main-content,
  #sidebar * {
    transition: none !important;
    animation: none !important;
  }
  
  .profile-pic-collapsed .w-10.h-10:hover {
    transform: none !important;
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
    <!-- User Profile Section - Replace your existing user profile section with this -->
<div class="flex items-center px-5 py-4 border-b border-sidebar-border bg-gradient-to-r from-navy to-primary" 
     data-admin-name="<?php echo htmlspecialchars(ucfirst($first_name) . ' ' . ucfirst($last_name)); ?>">
    <div class="w-10 h-10 rounded-full bg-yellow-600 flex items-center justify-center shadow-md overflow-hidden">
        <img src="<?php echo htmlspecialchars($profile_picture); ?>" 
             alt="<?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>" alt="Profile Picture" class="w-full h-full object-cover">
        </div>
        <div class="ml-3">
            <div class="text-sm font-medium text-sidebar-text">
                <span class="md:inline text-sm">
                    <?php echo htmlspecialchars(ucfirst($first_name) . ' ' . ucfirst($last_name)); ?>
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
            <!-- Notification Badge - Only show if there are pending bookings -->
            <?php if ($pending_count > 0): ?>
            <span id="booking-badge" class="absolute right-4 bg-red-500 text-white text-xs font-bold w-5 h-5 flex items-center justify-center rounded-full">
                <?php echo $pending_count; ?>
            </span>
            <?php endif; ?>
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
            <!-- Notification Badge - Only show if there are unvalidated IDs -->
            <?php if ($id_validation_count > 0): ?>
            <span id="id-badge" class="absolute right-4 bg-red-500 text-white text-xs font-bold w-5 h-5 flex items-center justify-center rounded-full">
              <?php echo $id_validation_count; ?>
            </span>
            <?php endif; ?>
          </a>
        </li>
        <li>
          <a href="payment_acceptance.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover relative">
            <i class="fas fa-hand-holding-usd w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Payment Acceptance</span>
            <!-- Notification Badge (optional) -->
            
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
            <a href="communication_chat.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover relative">
                <i class="fas fa-comments w-5 text-center mr-3 text-sidebar-accent"></i>
                <span>Chats</span>
                <!-- Notification Badge - Only show if there are sent messages -->
                <?php if ($sent_count > 0): ?>
                <span id="chat-badge" class="absolute right-4 bg-red-500 text-white text-xs font-bold w-5 h-5 flex items-center justify-center rounded-full">
                    <?php echo $sent_count; ?>
                </span>
                <?php endif; ?>
            </a>
        </li>
      </ul>
        
      <!-- Account -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Account</h5>
      </div>
        <ul class="list-none p-0">
            <li>
                <a href="admin_settings.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
                    <i class="fas fa-cog w-5 text-center mr-3 text-sidebar-accent"></i>
                    <span>Settings</span>
                </a>
            </li>
        </ul>
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