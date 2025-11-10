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
    
    // Add current page to navigation history
    function updateNavigationHistory() {
        const currentPage = getCurrentPage();
        let history = getNavigationHistory();
        
        console.log('Current Page:', currentPage);
        console.log('Previous History:', [...history]);
        
        // If this is the home page, reset the history
        if (currentPage === 'index.php') {
            history = ['index.php'];
            saveNavigationHistory(history);
            console.log('Reset to Home. New History:', [...history]);
            return history;
        }
        
        // Check if current page already exists in history
        const existingIndex = history.indexOf(currentPage);
        
        if (existingIndex !== -1) {
            // Page exists in history - user is going back
            // Remove all pages after this one (trim the trail)
            console.log('Page found at index:', existingIndex, '- Trimming history');
            history = history.slice(0, existingIndex + 1);
            console.log('Trimmed History:', [...history]);
        } else {
            // New page - add to the end
            console.log('New page - adding to history');
            // Ensure home is always at the beginning
            if (history.length === 0 || history[0] !== 'index.php') {
                history = ['index.php'];
            }
            
            history.push(currentPage);
            
            // Keep only last 5 pages to prevent breadcrumb from getting too long
            if (history.length > 5) {
                history = history.slice(-5);
                // Re-ensure home is at the beginning after slicing
                if (history[0] !== 'index.php') {
                    history = ['index.php'].concat(history.slice(1));
                }
            }
            console.log('Updated History:', [...history]);
        }
        
        saveNavigationHistory(history);
        return history;
    }
    
    // Build breadcrumb HTML
    function buildBreadcrumb() {
        const history = getNavigationHistory();
        const currentPage = getCurrentPage();
        const breadcrumbContainer = document.getElementById('dynamic-breadcrumb');
        
        if (!breadcrumbContainer) return;
        
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
