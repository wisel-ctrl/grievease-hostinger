// Simple Breadcrumb Navigation System
// Tracks ONLY the pages you actually visit in sequence

(function() {
    'use strict';
    
    // Page display names
    const PAGE_NAMES = {
        'index.php': 'Home',
        'about.php': 'About',
        'services.php': 'Services & Packages',
        'faqs.php': 'FAQs',
        'traditional_funeral.php': 'Traditional Funeral',
        'lifeplan.php': 'Life Plan',
        'cremation_service.php': 'Cremation Service',
        'privacy_policy.php': 'Privacy Policy',
        'termsofservice.php': 'Terms of Service',
        'memorial.php': 'Memorials'
    };
    
    const STORAGE_KEY = 'breadcrumbTrail';
    const MAX_TRAIL_LENGTH = 6;
    
    // Get current page
    function getCurrentPage() {
        const path = window.location.pathname;
        return path.substring(path.lastIndexOf('/') + 1) || 'index.php';
    }
    
    // Get breadcrumb trail from storage
    function getTrail() {
        try {
            const stored = sessionStorage.getItem(STORAGE_KEY);
            return stored ? JSON.parse(stored) : [];
        } catch (e) {
            return [];
        }
    }
    
    // Save trail to storage
    function saveTrail(trail) {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify(trail));
    }
    
    // Update trail with current page
    function updateTrail() {
        const currentPage = getCurrentPage();
        let trail = getTrail();
        
        // Get last visited page
        const lastPage = trail[trail.length - 1];
        
        // Same page? Skip (page refresh)
        if (currentPage === lastPage) {
            return trail;
        }
        
        // Check if going back via breadcrumb
        const pageIndex = trail.indexOf(currentPage);
        if (pageIndex !== -1) {
            // Going back - trim trail to that point
            trail = trail.slice(0, pageIndex + 1);
        } else {
            // New page - add to trail
            trail.push(currentPage);
            
            // Limit trail length
            if (trail.length > MAX_TRAIL_LENGTH) {
                trail = trail.slice(-MAX_TRAIL_LENGTH);
            }
        }
        
        saveTrail(trail);
        return trail;
    }
    
    // Render breadcrumb HTML
    function renderBreadcrumb() {
        const trail = getTrail();
        const currentPage = getCurrentPage();
        const container = document.getElementById('dynamic-breadcrumb');
        const wrapper = container?.closest('div[class*="fixed"]');
        
        if (!container) return;
        
        // Hide if only 1 page (no trail to show)
        if (trail.length <= 1) {
            if (wrapper) wrapper.style.display = 'none';
            adjustMargin(false);
            return;
        }
        
        // Show breadcrumb
        if (wrapper) wrapper.style.display = 'block';
        adjustMargin(true);
        
        // Build HTML
        let html = '';
        trail.forEach((page, i) => {
            const pageName = PAGE_NAMES[page] || page;
            const isCurrent = (page === currentPage);
            
            // Separator
            if (i > 0) {
                html += '<li class="flex items-center"><i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i></li>';
            }
            
            // Breadcrumb item
            html += '<li class="flex items-center">';
            if (isCurrent) {
                html += `<span class="text-navy font-medium">${pageName}</span>`;
            } else {
                html += `<a href="${page}" class="text-gray-600 hover:text-yellow-600 transition-colors">${pageName}</a>`;
            }
            html += '</li>';
        });
        
        container.innerHTML = html;
    }
    
    // Adjust page margin
    function adjustMargin(showBreadcrumb) {
        const mainContent = document.querySelector('[style*="margin-top: calc(var(--navbar-height) + 48px)"]');
        const heroSection = document.querySelector('#home');
        
        const margin = showBreadcrumb ? 'calc(var(--navbar-height) + 48px)' : 'var(--navbar-height)';
        
        if (mainContent && mainContent !== heroSection) {
            mainContent.style.marginTop = margin;
        }
        if (heroSection) {
            heroSection.style.marginTop = margin;
        }
    }
    
    // Initialize
    function init() {
        updateTrail();
        renderBreadcrumb();
    }
    
    // Run on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Expose reset function globally (optional)
    window.resetBreadcrumbs = function() {
        sessionStorage.removeItem(STORAGE_KEY);
        location.reload();
    };
    
})();
