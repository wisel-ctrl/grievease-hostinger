// Dynamic Breadcrumb Navigation System
// Tracks user's navigation history and displays it in breadcrumbs

(function() {
    'use strict';
    
    // Configuration for page names and URLs
    const pageConfig = {
        'index.php': { name: 'Home', icon: 'fa-home' },
        'about.php': { name: 'About', icon: null },
        'services.php': { name: 'Services & Packages', icon: null },
        'faqs.php': { name: 'FAQs', icon: null },
        'traditional_funeral.php': { name: 'Traditional Funeral', icon: null },
        'lifeplan.php': { name: 'Life Plan', icon: null },
        'privacy_policy.php': { name: 'Privacy Policy', icon: null },
        'termsofservice.php': { name: 'Terms of Service', icon: null },
        'memorial.php': { name: 'Memorials', icon: null }
    };
    
    // Get current page filename
    function getCurrentPage() {
        const path = window.location.pathname;
        const filename = path.substring(path.lastIndexOf('/') + 1) || 'index.php';
        return filename;
    }
    
    // Get navigation history from sessionStorage
    function getNavigationHistory() {
        const history = sessionStorage.getItem('navigationHistory');
        return history ? JSON.parse(history) : [];
    }
    
    // Save navigation history to sessionStorage
    function saveNavigationHistory(history) {
        sessionStorage.setItem('navigationHistory', JSON.stringify(history));
    }
    
    // Adjust content margin based on breadcrumb visibility
    function adjustContentMargin(showBreadcrumb) {
        // Find main content elements that need margin adjustment
        const mainContent = document.querySelector('[style*="margin-top: calc(var(--navbar-height) + 48px)"]');
        const heroSection = document.querySelector('#home');
        
        if (showBreadcrumb) {
            // With breadcrumb: navbar + breadcrumb height
            if (mainContent && mainContent !== heroSection) {
                mainContent.style.marginTop = 'calc(var(--navbar-height) + 48px)';
            }
            if (heroSection) {
                heroSection.style.marginTop = 'calc(var(--navbar-height) + 48px)';
            }
        } else {
            // Without breadcrumb: only navbar height
            if (mainContent && mainContent !== heroSection) {
                mainContent.style.marginTop = 'var(--navbar-height)';
            }
            if (heroSection) {
                heroSection.style.marginTop = 'var(--navbar-height)';
            }
        }
    }
    
    // Add current page to navigation history
    function updateNavigationHistory() {
        const currentPage = getCurrentPage();
        let history = getNavigationHistory();
        
        console.log('Current Page:', currentPage);
        console.log('Previous History:', [...history]);
        
        // Initialize history if empty
        if (history.length === 0) {
            // If starting from home page
            if (currentPage === 'index.php') {
                history = ['index.php'];
            } else {
                // Started from a different page, add home first
                history = ['index.php', currentPage];
            }
            saveNavigationHistory(history);
            console.log('Initialized History:', [...history]);
            return history;
        }
        
        // Get the last page in history
        const lastPage = history[history.length - 1];
        
        // If current page is the same as the last page, don't add it again
        // (This handles page refreshes)
        if (currentPage === lastPage) {
            console.log('Same page as last visit - no change needed');
            return history;
        }
        
        // Special handling for home page
        if (currentPage === 'index.php') {
            // User explicitly navigated back to home, reset history
            history = ['index.php'];
            saveNavigationHistory(history);
            console.log('Navigated to Home - Reset History:', [...history]);
            return history;
        }
        
        // Check if user clicked a breadcrumb link to go back
        const existingIndex = history.indexOf(currentPage);
        
        if (existingIndex !== -1 && existingIndex < history.length - 1) {
            // User clicked a breadcrumb to go back
            // Trim everything after the clicked page
            history = history.slice(0, existingIndex + 1);
            console.log('Navigated back via breadcrumb - Trimmed History:', [...history]);
        } else {
            // New forward navigation - add to history
            history.push(currentPage);
            console.log('Forward navigation - Added to History:', [...history]);
            
            // Keep only last 6 pages to prevent breadcrumb from getting too long
            // (Home + 5 other pages)
            if (history.length > 6) {
                // Keep home and the last 5 pages
                history = ['index.php'].concat(history.slice(-5));
                console.log('History trimmed to 6 pages:', [...history]);
            }
        }
        
        saveNavigationHistory(history);
        return history;
    }
    
    // Build breadcrumb HTML
    function buildBreadcrumb() {
        const history = getNavigationHistory();
        const currentPage = getCurrentPage();
        const breadcrumbContainer = document.getElementById('dynamic-breadcrumb');
        const breadcrumbWrapper = breadcrumbContainer?.closest('div[class*="fixed"]');
        
        if (!breadcrumbContainer) return;
        
        // Hide breadcrumb if only home page is in history
        if (history.length <= 1 && history[0] === 'index.php') {
            if (breadcrumbWrapper) {
                breadcrumbWrapper.style.display = 'none';
                // Adjust content margin when breadcrumb is hidden
                adjustContentMargin(false);
            }
            return;
        }
        
        // Show breadcrumb if there's navigation history
        if (breadcrumbWrapper) {
            breadcrumbWrapper.style.display = 'block';
            // Adjust content margin when breadcrumb is shown
            adjustContentMargin(true);
        }
        
        let breadcrumbHTML = '';
        
        history.forEach((page, index) => {
            const pageInfo = pageConfig[page] || { name: page, icon: null };
            const isCurrentPage = page === currentPage;
            const isFirst = index === 0;
            
            // Add separator before each item except the first
            if (!isFirst) {
                breadcrumbHTML += `
                    <li class="flex items-center">
                      <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                      </li>
                `;
            }
            
            // Build breadcrumb item
            breadcrumbHTML += `
                <li class="flex items-center">
            `;
            
            if (isCurrentPage) {
                // Current page - not clickable
                breadcrumbHTML += `
                    ${isFirst && pageInfo.icon ? `<i class="fas ${pageInfo.icon} text-yellow-600 mr-2"></i>` : ''}
                    <span class="text-navy font-medium">${pageInfo.name}</span>
                `;
            } else {
                // Previous pages - clickable
                breadcrumbHTML += `
                    <a href="${page}" class="text-gray-600 hover:text-yellow-600 transition-colors flex items-center">

                        ${isFirst && pageInfo.icon ? `<i class="fas ${pageInfo.icon} mr-2"></i>` : ''}
                        <span>${pageInfo.name}</span>
                    </a>
                `;
            }
            
            breadcrumbHTML += `
                </li>
            `;
        });
        
        breadcrumbContainer.innerHTML = breadcrumbHTML;
    }
    
    // Initialize breadcrumb system
    function initBreadcrumb() {
        // Update navigation history with current page
        updateNavigationHistory();
        
        // Build and display breadcrumb
        buildBreadcrumb();
    }
    
    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initBreadcrumb);
    } else {
        initBreadcrumb();
    }
})();
