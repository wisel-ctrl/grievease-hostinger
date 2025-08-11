// Function to toggle sidebar visibility
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.getElementById("main-content");
  const hamburgerMenu = document.getElementById("hamburger-menu");
  const userProfileContainer = document.querySelector("#sidebar > div:nth-child(2)"); // Select the user profile div

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
      // Don't hide the logo on mobile
      if (el.id !== 'logo-text' || window.innerWidth >= 768) {
        el.classList.add("hidden");
      }
    });
    
    // Hide user profile text but keep the icon
    const userProfileText = document.querySelectorAll("#sidebar > div:nth-child(2) > div:not(:first-child)");
    userProfileText.forEach(el => {
      el.classList.add("hidden");
    });
    
    // Center the profile picture in collapsed state
    if (userProfileContainer) {
      userProfileContainer.classList.remove("px-5");
      userProfileContainer.classList.add("justify-center");
    }
    
    // Center the navigation icons when collapsed
    document.querySelectorAll(".sidebar-link").forEach(el => {
      el.classList.add("justify-center");
      el.classList.remove("px-5");
      el.classList.add("px-0");
    });
    
    // Expand the main content to fill the space
    if (mainContent) {
      mainContent.classList.remove("ml-64");
      mainContent.classList.add("ml-16");
      mainContent.classList.remove("w-[calc(100%-16rem)]"); // Remove the previous width calculation
      mainContent.classList.add("w-[calc(100%-4rem)]"); // Adjust the width to fill the space
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
    
    // Restore user profile container styling
    if (userProfileContainer) {
      userProfileContainer.classList.add("px-5");
      userProfileContainer.classList.remove("justify-center");
    }
    
    // Restore original navigation link styling
    document.querySelectorAll(".sidebar-link").forEach(el => {
      el.classList.remove("justify-center");
      el.classList.remove("px-0");
      el.classList.add("px-5");
    });
    // Adjust the main content to its original size
    if (mainContent) {
      mainContent.classList.remove("ml-16");
      mainContent.classList.add("ml-64");
      mainContent.classList.remove("w-[calc(100%-4rem)]"); // Remove the adjusted width
      mainContent.classList.add("w-[calc(100%-16rem)]"); // Restore the original width calculation
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
  
  if (sidebar.classList.contains("translate-x-0")) {
    // Hide sidebar
    sidebar.classList.remove("translate-x-0");
    sidebar.classList.add("-translate-x-full");
  } else {
    // Show sidebar
    sidebar.classList.remove("-translate-x-full");
    sidebar.classList.add("translate-x-0");
    
    // Ensure logo remains visible on mobile when sidebar is open
    if (window.innerWidth < 768 && logoText) {
      logoText.classList.remove("hidden");
    }
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
  if (filename === "communication_chat.php") {
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
    if (href === filename || (filename === '' && href === 'admin_index.php')) {
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
  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', toggleMobileSidebar);
  }
  
  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(event) {
    const sidebar = document.getElementById("sidebar");
    const mobileMenuBtn = document.getElementById('mobile-hamburger');
    const hamburgerMenu = document.getElementById('hamburger-menu');
        
    if (window.innerWidth < 768 && 
        sidebar && !sidebar.contains(event.target) && 
        (!mobileMenuBtn || !mobileMenuBtn.contains(event.target)) &&
        (!hamburgerMenu || !hamburgerMenu.contains(event.target))) {
      sidebar.classList.remove('translate-x-0');
      sidebar.classList.add('-translate-x-full');
      
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
  
  // Create mobile menu button if it doesn't exist
  let mobileMenuBtn = document.getElementById('mobile-hamburger');
  
  // Only create the mobile button if it doesn't exist
  if (!mobileMenuBtn) {
    mobileMenuBtn = document.createElement('button');
    mobileMenuBtn.id = 'mobile-hamburger';
    mobileMenuBtn.className = 'fixed top-4 left-4 z-50 p-2 bg-white rounded-lg shadow-md text-gray-600 hover:text-gray-900 md:hidden transition-all duration-300';
    mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
    document.body.appendChild(mobileMenuBtn);
    
    mobileMenuBtn.addEventListener('click', toggleMobileSidebar);
    // Only show mobile hamburger on small screens
    mobileMenuBtn.style.display = window.innerWidth < 768 ? "block" : "none";
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
    
    /* Special handling for communication_chat.php */
    body.communication-chat #sidebar {
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
  
  if (window.innerWidth < 768) {
    // Mobile view: Show mobile hamburger, hide sidebar hamburger
    if (hamburgerMenu) hamburgerMenu.style.display = "none";
    if (mobileMenuBtn) mobileMenuBtn.style.display = "block";
    
    // Ensure sidebar is hidden initially on mobile
    if (sidebar) {
      sidebar.classList.add('-translate-x-full');
      sidebar.classList.remove('translate-x-0');
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
  }
  
  // Always ensure the background is maintained after resize
  updateSidebarBackground();
});