<nav id="sidebar" class="w-64 lg:w-64 md:w-16 sm:w-16 h-screen bg-sidebar-bg font-hedvig fixed transition-all duration-300 overflow-y-auto z-20 scrollbar-thin shadow-sidebar animate-sidebar sidebar -translate-x-full lg:translate-x-0">
  <!-- Logo and Header with hamburger menu -->
  <div class="flex items-center px-3 sm:px-5 py-4 sm:py-6 border-b border-sidebar-border">
    <button id="hamburger-menu" class="p-2 mr-2 bg-white rounded-lg shadow-md text-gray-600 hover:text-gray-900 transition-all duration-300 lg:hidden">
      <i class="fas fa-bars"></i>
    </button>
    <button id="desktop-hamburger" class="p-2 mr-2 bg-white rounded-lg shadow-md text-gray-600 hover:text-gray-900 transition-all duration-300 hidden lg:block">
      <i class="fas fa-bars"></i>
    </button>
    <!-- <img src="../Landing_Page/Landing_images/logo.png" alt="GrievEase Logo" class="h-10 w-auto mr-3"> -->
    <div class="text-xl sm:text-2xl font-cinzel font-bold text-sidebar-accent sidebar-logo">GrievEase</div>
  </div>
    
    <!-- User Profile -->
    <div class="flex items-center px-3 sm:px-5 py-3 sm:py-4 border-b border-sidebar-border bg-gradient-to-r from-navy to-primary user-profile">
    <?php if (!empty($employee['profile_picture'])): ?>
        <div class="w-10 h-10 rounded-full overflow-hidden flex items-center justify-center shadow-md">
            <img src="../profile_picture/<?php echo htmlspecialchars($employee['profile_picture']); ?>" 
                 alt="Profile" 
                 class="w-full h-full object-cover">
        </div>
    <?php else: ?>
        <div class="w-10 h-10 rounded-full bg-yellow-600 flex items-center justify-center shadow-md">
            <span class="text-white font-medium">
                <?php 
                    $first_initial = !empty($first_name) ? strtoupper(substr($first_name, 0, 1)) : '';
                    $last_initial = !empty($last_name) ? strtoupper(substr($last_name, 0, 1)) : '';
                    echo $first_initial . $last_initial;
                ?>
            </span>
        </div>
    <?php endif; ?>
    <div class="ml-3 user-info">
        <div class="text-sm font-medium text-sidebar-text capitalize">
            <?php 
                // Capitalize first letter of each name
                $display_first = !empty($first_name) ? ucfirst(strtolower($first_name)) : '';
                $display_last = !empty($last_name) ? ucfirst(strtolower($last_name)) : '';
                echo htmlspecialchars($display_first . ' ' . $display_last); 
            ?>
        </div>
        <div class="text-xs text-sidebar-text opacity-70">Employee</div>
    </div>
    <div class="ml-auto">
        <span class="w-3 h-3 bg-success rounded-full block"></span>
    </div>
</div>
    
    <!-- Menu Items -->
    <div class="pt-4 pb-8">
      <!-- Main Navigation -->
      <div class="px-3 sm:px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Main</h5>
      </div>
      <ul class="list-none p-0 mb-6">
        <li>
          <a href="index.php" class="sidebar-link flex items-center px-3 sm:px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-tachometer-alt w-5 text-center mr-3 text-sidebar-accent flex-shrink-0"></i>
            <span class="sidebar-text">Dashboard</span>
          </a>
        </li> 
        <li>
          <a href="employee_customer_account_creation.php" class="sidebar-link flex items-center px-3 sm:px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-user-circle w-5 text-center mr-3 text-sidebar-accent flex-shrink-0"></i>
            <span class="sidebar-text">Customer Account Management</span>
          </a>
        </li>
        <li>
          <a href="employee_inventory.php" class="sidebar-link flex items-center px-3 sm:px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-boxes w-5 text-center mr-3 text-sidebar-accent flex-shrink-0"></i>
            <span class="sidebar-text">View Inventory</span>
          </a>
        </li>
        <li>
          <a href="employee_pos.php" class="sidebar-link flex items-center px-3 sm:px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-cash-register w-5 text-center mr-3 text-sidebar-accent flex-shrink-0"></i>
            <span class="sidebar-text">Point-Of-Sale (POS)</span>
          </a>
        </li>
      </ul>
        
      <!-- Reports & Analytics -->
      <div class="px-3 sm:px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Reports & Analytics</h5>
      </div>
      <ul class="list-none p-0 mb-6">
        <li>
          <a href="employee_expenses.php" class="sidebar-link flex items-center px-3 sm:px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-money-bill-wave w-5 text-center mr-3 text-sidebar-accent flex-shrink-0"></i>
            <span class="sidebar-text">Expenses</span>
          </a>
        </li>
        <li>
          <a href="employee_history.php" class="sidebar-link flex items-center px-3 sm:px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-history w-5 text-center mr-3 text-sidebar-accent flex-shrink-0"></i>
            <span class="sidebar-text">Service History</span>
          </a>
        </li>
      </ul>
        
      <!-- Services & Staff -->
      <div class="px-3 sm:px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Communication</h5>
      </div>
      <ul class="list-none p-0 mb-6">
        <li>
          <a href="employee_chat.php" class="sidebar-link flex items-center px-3 sm:px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-comments w-5 text-center mr-3 text-sidebar-accent flex-shrink-0"></i>
            <span class="sidebar-text">Chats</span>
          </a>
        </li>
      </ul>
        
      <!-- Account -->
      <div class="px-3 sm:px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Account</h5>
      </div>
      <ul class="list-none p-0">
        <li>
          <a href="employee_settings.php" class="sidebar-link flex items-center px-3 sm:px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-cog w-5 text-center mr-3 text-sidebar-accent flex-shrink-0"></i>
            <span class="sidebar-text">Settings</span>
          </a>
        </li>
        <li>
          <a href="../logout.php" class="sidebar-link flex items-center px-3 sm:px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover hover:text-error">
            <i class="fas fa-sign-out-alt w-5 text-center mr-3 text-error flex-shrink-0"></i>
            <span class="sidebar-text">Logout</span>
          </a>
        </li>
      </ul>
    </div>
    
    <!-- Footer -->
    <div class="relative bottom-0 left-0 right-0 px-3 sm:px-5 py-3 border-t border-sidebar-border bg-gradient-to-r from-navy to-primary sidebar-footer">
      <div class="flex justify-between items-center">
        <p class="text-xs text-sidebar-text opacity-60">© 2025 GrievEase</p>
        <div class="text-xs text-sidebar-accent hidden sm:block">
          <i class="fas fa-heart"></i> With Compassion
        </div>
      </div>
    </div>
  </nav>