// Function to toggle sidebar visibility
function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.getElementById("main-content");
  const hamburgerMenu = document.getElementById("hamburger-menu");

  if (!sidebar || !mainContent || !hamburgerMenu) {
    console.error("One or more elements not found:", {
      sidebar, mainContent, hamburgerMenu
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
    
    // Expand the main content to fill the space
    mainContent.classList.remove("ml-64");
    mainContent.classList.add("ml-16");
    mainContent.classList.remove("w-[calc(100%-16rem)]"); // Remove the previous width calculation
    mainContent.classList.add("w-[calc(100%-4rem)]"); // Adjust the width to fill the space

    // Change the hamburger menu icon to a right arrow
    hamburgerMenu.innerHTML = '<i class="fas fa-chevron-right"></i>';
    console.log("Toggling sidebar. Current icon:", hamburgerMenu.innerHTML);

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

    // Adjust the main content to its original size
    mainContent.classList.remove("ml-16");
    mainContent.classList.add("ml-64");
    mainContent.classList.remove("w-[calc(100%-4rem)]"); // Remove the adjusted width
    mainContent.classList.add("w-[calc(100%-16rem)]"); // Restore the original width calculation

    // Change the right arrow icon back to a hamburger menu
    hamburgerMenu.innerHTML = '<i class="fas fa-bars"></i>';
  }
}

// Update the initialization and resize logic
document.addEventListener("DOMContentLoaded", function() {
  // Remove the fixed positioning of the hamburger menu from the original code
  const oldHamburgerMenu = document.querySelector("button#hamburger-menu.fixed");
  if (oldHamburgerMenu) {
    oldHamburgerMenu.remove();
  }
  
  // Add event listener to the hamburger menu
  const hamburgerMenu = document.getElementById("hamburger-menu");
  if (hamburgerMenu) {
    hamburgerMenu.addEventListener("click", toggleSidebar);
  }
  
  // Initialize the sidebar state
  const sidebar = document.getElementById("sidebar");
  const mainContent = document.getElementById("main-content");
  
  // Set initial state based on screen size
  if (window.innerWidth < 768) {
    // On mobile, start with collapsed sidebar
    sidebar.classList.remove("w-64");
    sidebar.classList.add("w-16");
    mainContent.classList.remove("ml-64");
    mainContent.classList.add("ml-16");
    
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
    
    // Update hamburger icon
    hamburgerMenu.innerHTML = '<i class="fas fa-chevron-right"></i>';
  }
});

// Add CSS for transition effects
document.addEventListener("DOMContentLoaded", function() {
  const style = document.createElement('style');
  style.textContent = `
    #sidebar {
      transition: width 0.3s ease;
    }
    
    #main-content {
      transition: margin-left 0.3s ease;
    }
    
    .w-16 {
      width: 4rem;
    }
  `;
  document.head.appendChild(style);
});

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
}

// Initialize mobile menu for smaller screens
function initMobileMenu() {
  const mobileMenuBtn = document.getElementById('mobile-menu-btn');
  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', function() {
      const sidebar = document.querySelector("nav");
      sidebar.classList.toggle('translate-x-0');
      sidebar.classList.toggle('-translate-x-full');
    });
  }
  
  // Close sidebar when clicking outside on mobile
  document.addEventListener('click', function(event) {
    const sidebar = document.querySelector("nav");
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    
    if (window.innerWidth < 768 && 
        !sidebar.contains(event.target) && 
        (!mobileMenuBtn || !mobileMenuBtn.contains(event.target))) {
      sidebar.classList.remove('translate-x-0');
      sidebar.classList.add('-translate-x-full');
    }
  });
}

// Execute when DOM is fully loaded
document.addEventListener('DOMContentLoaded', function() {
  setActivePage();
  initMobileMenu();
  
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

// Update sidebar state on window resize
window.addEventListener('resize', function() {
  const sidebar = document.querySelector("nav");
  if (window.innerWidth < 768) {
    sidebar.classList.add('-translate-x-full');
    sidebar.classList.remove('translate-x-0');
  } else {
    sidebar.classList.remove('-translate-x-full');
    sidebar.classList.add('translate-x-0');
  }
});