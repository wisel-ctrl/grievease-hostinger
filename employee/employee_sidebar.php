<?php
// employee_sidebar.php with admin sidebar functionality
require_once '../db_connect.php'; // Database connection

// Get employee data from database
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, email, birthdate, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
$first_name = $employee['first_name'];
$last_name = $employee['last_name'];
$email = $employee['email'];
$profile_picture = $employee['profile_picture'] ? '../' . $employee['profile_picture'] : '../default.png';

// Get notification counts if needed for employee sidebar
// Add any notification queries here if needed
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
    <div id="logo-text" class="text-2xl font-cinzel font-bold text-sidebar-accent">GrievEase</div>
  </div>
    
  <!-- User Profile -->
  <div class="flex items-center px-5 py-4 border-b border-sidebar-border bg-gradient-to-r from-navy to-primary">
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
    <div class="ml-3">
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
      <li>
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
        <a href="employee_settings.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
          <i class="fas fa-cog w-5 text-center mr-3 text-sidebar-accent"></i>
          <span>Settings</span>
        </a>
      </li>
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
    
  </div>
</nav>

<!-- Create mobile menu button -->
<button id="mobile-hamburger" class="fixed top-4 left-4 z-50 p-2 bg-white rounded-lg shadow-md text-gray-600 hover:text-gray-900 md:hidden transition-all duration-300">
  <i class="fas fa-bars"></i>
</button>

<!-- Mobile overlay -->
<div id="mobile-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden md:hidden transition-opacity duration-300"></div>

<script>
// Function to toggle sidebar visibility
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.getElementById("main-content");
  const hamburgerMenu = document.getElementById("hamburger-menu");

  if (!sidebar || !hamburgerMenu) {
    console.error("One or more elements not found:", {
      sidebar, hamburgerMenu
    });
    return;
  }

  if (sidebar.classList.contains("w-64")) {
    // Collapse the sidebar
    sidebar.classList.remove("w-64");
    sidebar.classList.add("w-16"); // Keep a small portion visible for the hamburger menu
    
    // Keep icons visible but hide text in navigation links
    document.querySelectorAll(".sidebar-link span").forEach(el => {
      el.classList.add("hidden");
    });
    
    // Hide menu section headers
    document.querySelectorAll(".menu-header").forEach(el => {
      el.classList.add("hidden");
    });
    
    // Hide elements except hamburger menu in the header
    const headerElements = document.querySelectorAll("#sidebar > div:first-child > *:not(#hamburger-menu)");
    headerElements.forEach(el => {
      el.classList.add("hidden");
    });
    
    // Hide user profile text but keep the icon
    const userProfileText = document.querySelectorAll("#sidebar > div:nth-child(2) > div:not(:first-child)");
    userProfileText.forEach(el => {
      el.classList.add("hidden");
    });
    
    // Center the navigation icons when collapsed
    document.querySelectorAll(".sidebar-link").forEach(el => {
      el.classList.add("justify-center");
      el.classList.remove("px-5");
      el.classList.add("px-0");
    });
    
    // Expand the main content to fill the space if it exists
    if (mainContent) {
      mainContent.classList.remove("ml-64");
      mainContent.classList.add("ml-16");
      mainContent.classList.remove("w-[calc(100%-16rem)]");
      mainContent.classList.add("w-[calc(100%-4rem)]");
    }

    // Change the hamburger menu icon to a right arrow
    hamburgerMenu.innerHTML = '<i class="fas fa-chevron-right"></i>';
  } else {
    // Expand the sidebar
    sidebar.classList.remove("w-16");
    sidebar.classList.add("w-64");
    
    // Show all text in navigation links
    document.querySelectorAll(".sidebar-link span").forEach(el => {
      el.classList.remove("hidden");
    });
    
    // Show menu section headers
    document.querySelectorAll(".menu-header").forEach(el => {
      el.classList.remove("hidden");
    });
    
    // Show logo and title in the header
    const headerElements = document.querySelectorAll("#sidebar > div:first-child > *");
    headerElements.forEach(el => {
      el.classList.remove("hidden");
    });
    
    // Show user profile text
    const userProfileText = document.querySelectorAll("#sidebar > div:nth-child(2) > div");
    userProfileText.forEach(el => {
      el.classList.remove("hidden");
    });
    
    // Restore original navigation link styling
    document.querySelectorAll(".sidebar-link").forEach(el => {
      el.classList.remove("justify-center");
      el.classList.remove("px-0");
      el.classList.add("px-5");
    });

    // Adjust the main content to its original size if it exists
    if (mainContent) {
      mainContent.classList.remove("ml-16");
      mainContent.classList.add("ml-64");
      mainContent.classList.remove("w-[calc(100%-4rem)]");
      mainContent.classList.add("w-[calc(100%-16rem)]");
    }

    // Change the right arrow icon back to a hamburger menu
    hamburgerMenu.innerHTML = '<i class="fas fa-bars"></i>';
  }
  
  // Ensure sidebar background is maintained after toggling
  updateSidebarBackground();
}

// Handle mobile sidebar toggle differently
function toggleMobileSidebar() {
  const sidebar = document.getElementById("sidebar");
  const logoText = document.getElementById("logo-text");
  const overlay = document.getElementById("mobile-overlay");
  
  console.log("toggleMobileSidebar called");
  console.log("Current sidebar classes:", sidebar.className);
  
  if (sidebar.classList.contains("-translate-x-full")) {
    // Show sidebar
    sidebar.classList.remove("-translate-x-full");
    sidebar.classList.add("translate-x-0");
    if (overlay) {
      overlay.classList.remove("hidden");
    }
    console.log("Showing sidebar");
  } else {
    // Hide sidebar
    sidebar.classList.remove("translate-x-0");
    sidebar.classList.add("-translate-x-full");
    if (overlay) {
      overlay.classList.add("hidden");
    }
    console.log("Hiding sidebar");
  }
  
  // Ensure logo remains visible on mobile when sidebar is open
  if (window.innerWidth < 768 && logoText) {
    logoText.classList.remove("hidden");
  }
  
  // Ensure sidebar background is maintained after toggling on mobile
  updateSidebarBackground();
}

// Function to update sidebar background consistently
function updateSidebarBackground() {
  const sidebar = document.getElementById("sidebar");
  const currentPath = window.location.pathname;
  const filename = currentPath.substring(currentPath.lastIndexOf('/') + 1);
  
  // Always set background to white and important z-index
  sidebar.style.backgroundColor = "white";
  sidebar.style.zIndex = "50";
  
  // Special handling for chat page
  if (filename === "employee_chat.php") {
    sidebar.style.backgroundColor = "white !important";
    sidebar.style.boxShadow = "2px 0 10px rgba(0,0,0,0.1)";
  }
}

// Function to highlight the current active page in the sidebar
function setActivePage() {
  const currentPath = window.location.pathname;
  const filename = currentPath.substring(currentPath.lastIndexOf('/') + 1);
  
  const sidebarLinks = document.querySelectorAll('.sidebar-link');
  sidebarLinks.forEach(link => {
    // Reset all links to their default state
    link.classList.remove('active', 'bg-sidebar-hover');
    link.style.color = ''; // Reset text color to default
    // Apply active styles to the current link
    const href = link.getAttribute('href');
    if (href === filename || (filename === '' && href === 'index.php')) {
      link.classList.add('active', 'bg-sidebar-hover');
      link.style.color = '#CA8A04'; // Apply the sidebar-accent color to text
    }
  });
  
  // Ensure sidebar background is properly set
  updateSidebarBackground();
}

// Initialize mobile menu for smaller screens
function initMobileMenu() {
  const mobileMenuBtn = document.getElementById('mobile-hamburger');
  console.log("initMobileMenu called, mobileMenuBtn:", mobileMenuBtn);
  
  if (mobileMenuBtn) {
    // Remove any existing event listeners to prevent duplicates
    mobileMenuBtn.removeEventListener('click', toggleMobileSidebar);
    mobileMenuBtn.addEventListener('click', toggleMobileSidebar);
    console.log("Mobile hamburger event listener added");
  }
  
  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(event) {
    const sidebar = document.getElementById("sidebar");
    const mobileMenuBtn = document.getElementById('mobile-hamburger');
    const hamburgerMenu = document.getElementById('hamburger-menu');
    const overlay = document.getElementById('mobile-overlay');
        
    if (window.innerWidth < 768 && 
        sidebar && !sidebar.contains(event.target) && 
        (!mobileMenuBtn || !mobileMenuBtn.contains(event.target)) &&
        (!hamburgerMenu || !hamburgerMenu.contains(event.target))) {
      sidebar.classList.remove('translate-x-0');
      sidebar.classList.add('-translate-x-full');
      if (overlay) {
        overlay.classList.add('hidden');
      }
      
      // Ensure background is maintained after closing on mobile
      updateSidebarBackground();
    }
  });
}

// Update the initialization and resize logic
document.addEventListener("DOMContentLoaded", function() {
  // Add event listener to the hamburger menu
  const hamburgerMenu = document.getElementById("hamburger-menu");
  if (hamburgerMenu) {
    hamburgerMenu.addEventListener("click", toggleSidebar);
    
    // Hide the sidebar hamburger on small screens
    if (window.innerWidth < 768) {
      hamburgerMenu.style.display = "none";
    }
  }
  
  // Get mobile menu button (it should already exist in the HTML)
  let mobileMenuBtn = document.getElementById('mobile-hamburger');
  console.log("Mobile menu button found:", mobileMenuBtn);
  
  // Add event listener to mobile hamburger if it exists
  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', toggleMobileSidebar);
    console.log("Event listener added to mobile hamburger");
    // Only show mobile hamburger on small screens
    mobileMenuBtn.style.display = window.innerWidth < 768 ? "block" : "none";
  }
  
  // Add event listener to mobile overlay to close sidebar when clicked
  const mobileOverlay = document.getElementById('mobile-overlay');
  if (mobileOverlay) {
    mobileOverlay.addEventListener('click', function() {
      if (window.innerWidth < 768) {
        toggleMobileSidebar();
      }
    });
  }
  
  // Initialize the sidebar state
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.getElementById("main-content");
  
  // Set initial state based on screen size
  if (window.innerWidth < 768) {
    // On mobile, start with hidden sidebar
    sidebar.classList.add("-translate-x-full");
    sidebar.classList.remove("translate-x-0");
    
    // Show the mobile hamburger and hide the in-sidebar one
    if (mobileMenuBtn) {
      mobileMenuBtn.style.display = "block";
    }
    if (hamburgerMenu) {
      hamburgerMenu.style.display = "none";
    }
  } else {
    // On desktop, start with expanded sidebar
    sidebar.classList.remove("-translate-x-full");
    sidebar.classList.add("translate-x-0");
    
    // Hide mobile hamburger and show the in-sidebar one
    if (mobileMenuBtn) {
      mobileMenuBtn.style.display = "none";
    }
    if (hamburgerMenu) {
      hamburgerMenu.style.display = "block";
    }
  }
  
  // Call functions to set up the page
  setActivePage();
  initMobileMenu();
  updateSidebarBackground();
  
  // Add hover effects to sidebar links
  const sidebarLinks = document.querySelectorAll('.sidebar-link');
  sidebarLinks.forEach(link => {
    link.addEventListener('mouseenter', function() {
      if (!this.classList.contains('active')) {
        this.style.backgroundColor = '#F1F5F9'; // sidebar-hover color
      }
    });
    
    link.addEventListener('mouseleave', function() {
      if (!this.classList.contains('active')) {
        this.style.backgroundColor = '';
      }
    });
  });
});

// Add CSS for transition effects
document.addEventListener("DOMContentLoaded", function() {
  const style = document.createElement('style');
  style.textContent = `
    #sidebar {
      transition: width 0.3s ease, transform 0.3s ease;
      background-color: white; /* Ensure background is always set */
    }
    
    #main-content {
      transition: margin-left 0.3s ease, width 0.3s ease;
    }
    
    .w-16 {
      width: 4rem;
    }
    
    @media (max-width: 768px) {
      #sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100%;
        width: 16rem !important;
        z-index: 50;
        transform: translateX(0);
        background-color: white; /* Reinforced background for mobile */
      }
      
      #sidebar.translate-x-0 {
        transform: translateX(0);
      }
      
      #sidebar.-translate-x-full {
        transform: translateX(-100%);
      }
      
      #main-content {
        margin-left: 0 !important;
        width: 100% !important;
      }
      
      /* Hide in-sidebar hamburger on mobile */
      #hamburger-menu {
        display: none !important;
      }
      
      /* Show mobile hamburger on mobile */
      #mobile-hamburger {
        display: block !important;
      }
    }
    
    @media (min-width: 769px) {
      #mobile-hamburger {
        display: none !important;
      }
      
      #hamburger-menu {
        display: block !important;
      }
    }
    
    /* Special handling for employee_chat.php */
    body.employee-chat #sidebar {
      background-color: white !important;
    }
  `;
  document.head.appendChild(style);
});

// Update sidebar state on window resize
window.addEventListener('resize', function() {
  const sidebar = document.getElementById("sidebar");
  const hamburgerMenu = document.getElementById("hamburger-menu");
  const mobileMenuBtn = document.getElementById('mobile-hamburger');
  const overlay = document.getElementById('mobile-overlay');
  
  if (window.innerWidth < 768) {
    // Mobile view: Show mobile hamburger, hide sidebar hamburger
    if (hamburgerMenu) hamburgerMenu.style.display = "none";
    if (mobileMenuBtn) mobileMenuBtn.style.display = "block";
    
    // Ensure sidebar is hidden initially on mobile
    if (sidebar) {
      sidebar.classList.add('-translate-x-full');
      sidebar.classList.remove('translate-x-0');
    }
    // Hide overlay on mobile when resizing
    if (overlay) {
      overlay.classList.add('hidden');
    }
  } else {
    // Desktop view: Hide mobile hamburger, show sidebar hamburger
    if (hamburgerMenu) hamburgerMenu.style.display = "block";
    if (mobileMenuBtn) mobileMenuBtn.style.display = "none";
    
    // Ensure sidebar is visible initially on desktop
    if (sidebar) {
      sidebar.classList.remove('-translate-x-full');
      sidebar.classList.add('translate-x-0');
    }
    // Always hide overlay on desktop
    if (overlay) {
      overlay.classList.add('hidden');
    }
  }
  
  // Always ensure the background is maintained after resize
  updateSidebarBackground();
});
</script>