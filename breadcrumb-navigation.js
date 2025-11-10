/**
 * Breadcrumb Navigation System
 * Tracks user navigation path and displays breadcrumb trail
 */

class BreadcrumbNavigation {
    constructor() {
        this.storageKey = 'breadcrumb_trail';
        this.pageMapping = {
            'index.php': { name: 'Home', url: 'index.php' },
            '/': { name: 'Home', url: 'index.php' },
            '': { name: 'Home', url: 'index.php' },
            'about.php': { name: 'About', url: 'about.php' },
            'services.php': { name: 'Services', url: 'services.php' },
            'faqs.php': { name: 'FAQs', url: 'faqs.php' },
            'traditional_funeral.php': { name: 'Traditional Funeral', url: 'traditional_funeral.php' },
            'cremation_service.php': { name: 'Cremation Service', url: 'cremation_service.php' },
            'memorial.php': { name: 'Memorial Service', url: 'memorial.php' },
            'lifeplan.php': { name: 'Lifeplan', url: 'lifeplan.php' },
            'privacy_policy.php': { name: 'Privacy Policy', url: 'privacy_policy.php' },
            'termsofservice.php': { name: 'Terms of Service', url: 'termsofservice.php' }
        };
        this.init();
    }

    /**
     * Initialize breadcrumb navigation
     */
    init() {
        const currentPage = this.getCurrentPage();
        const currentPageInfo = this.pageMapping[currentPage];

        if (!currentPageInfo) {
            console.warn('Current page not found in page mapping:', currentPage);
            return;
        }

        // Get existing trail from sessionStorage
        let trail = this.getTrail();

        // Check if current page already exists in trail
        const existingIndex = trail.findIndex(item => item.url === currentPageInfo.url);

        if (existingIndex !== -1) {
            // Page exists in trail - remove all pages after it (back navigation)
            trail = trail.slice(0, existingIndex + 1);
        } else {
            // New page - add to trail
            trail.push(currentPageInfo);
        }

        // Limit trail to reasonable length (optional)
        if (trail.length > 10) {
            trail = trail.slice(-10);
        }

        // Save updated trail
        this.saveTrail(trail);

        // Render breadcrumb
        this.renderBreadcrumb(trail);
    }

    /**
     * Get current page filename
     */
    getCurrentPage() {
        const path = window.location.pathname;
        const filename = path.substring(path.lastIndexOf('/') + 1);
        return filename || 'index.php';
    }

    /**
     * Get breadcrumb trail from sessionStorage
     */
    getTrail() {
        try {
            const stored = sessionStorage.getItem(this.storageKey);
            if (stored) {
                return JSON.parse(stored);
            }
        } catch (e) {
            console.error('Error reading breadcrumb trail:', e);
        }
        return [];
    }

    /**
     * Save breadcrumb trail to sessionStorage
     */
    saveTrail(trail) {
        try {
            sessionStorage.setItem(this.storageKey, JSON.stringify(trail));
        } catch (e) {
            console.error('Error saving breadcrumb trail:', e);
        }
    }

    /**
     * Clear breadcrumb trail
     */
    clearTrail() {
        sessionStorage.removeItem(this.storageKey);
    }

    /**
     * Render breadcrumb navigation
     */
    renderBreadcrumb(trail) {
        const container = document.getElementById('breadcrumb-container');
        
        if (!container) {
            console.warn('Breadcrumb container not found. Add <div id="breadcrumb-container"></div> to your page.');
            return;
        }

        if (trail.length === 0) {
            container.innerHTML = '';
            return;
        }

        // Build breadcrumb HTML
        let breadcrumbHTML = '<nav class="breadcrumb-nav" aria-label="Breadcrumb">';
        breadcrumbHTML += '<ol class="breadcrumb-list">';

        trail.forEach((page, index) => {
            const isLast = index === trail.length - 1;
            
            breadcrumbHTML += '<li class="breadcrumb-item">';
            
            if (isLast) {
                // Current page - not clickable
                breadcrumbHTML += `<span class="breadcrumb-current" aria-current="page">${page.name}</span>`;
            } else {
                // Previous pages - clickable
                breadcrumbHTML += `<a href="${page.url}" class="breadcrumb-link">${page.name}</a>`;
            }
            
            breadcrumbHTML += '</li>';
            
            // Add separator (not after last item)
            if (!isLast) {
                breadcrumbHTML += '<li class="breadcrumb-separator" aria-hidden="true">→</li>';
            }
        });

        breadcrumbHTML += '</ol>';
        breadcrumbHTML += '</nav>';

        container.innerHTML = breadcrumbHTML;
    }

    /**
     * Add custom page to mapping (for dynamic pages)
     */
    addPage(filename, name, url) {
        this.pageMapping[filename] = { name, url };
    }

    /**
     * Reset to home (clear trail and start fresh)
     */
    resetToHome() {
        this.clearTrail();
        window.location.href = 'index.php';
    }
}

// Default styles (can be customized)
const defaultStyles = `
<style>
.breadcrumb-nav {
    margin: 1rem 0;
    padding: 0.75rem 1rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.breadcrumb-list {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    list-style: none;
    margin: 0;
    padding: 0;
    gap: 0.5rem;
}

.breadcrumb-item {
    display: inline-flex;
    align-items: center;
}

.breadcrumb-link {
    color: #2563eb;
    text-decoration: none;
    transition: color 0.2s ease;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.breadcrumb-link:hover {
    color: #1d4ed8;
    background-color: #dbeafe;
}

.breadcrumb-current {
    color: #6b7280;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
}

.breadcrumb-separator {
    color: #9ca3af;
    margin: 0 0.25rem;
    user-select: none;
}

/* Mobile responsive */
@media (max-width: 640px) {
    .breadcrumb-nav {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
    }
    
    .breadcrumb-link,
    .breadcrumb-current {
        padding: 0.125rem 0.25rem;
    }
}
</style>
`;

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        // Inject default styles if not already present
        if (!document.getElementById('breadcrumb-styles')) {
            const styleDiv = document.createElement('div');
            styleDiv.id = 'breadcrumb-styles';
            styleDiv.innerHTML = defaultStyles;
            document.head.appendChild(styleDiv.querySelector('style'));
        }
        
        // Initialize breadcrumb
        window.breadcrumbNav = new BreadcrumbNavigation();
    });
} else {
    // Inject default styles if not already present
    if (!document.getElementById('breadcrumb-styles')) {
        const styleDiv = document.createElement('div');
        styleDiv.id = 'breadcrumb-styles';
        styleDiv.innerHTML = defaultStyles;
        document.head.appendChild(styleDiv.querySelector('style'));
    }
    
    // Initialize breadcrumb
    window.breadcrumbNav = new BreadcrumbNavigation();
}

// Export for use in other scripts if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BreadcrumbNavigation;
}
