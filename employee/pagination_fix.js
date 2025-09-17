// Updated pagination functions with employee_inventory.php styling

// Function to update pagination controls
function updatePaginationControls(currentPage, totalPages) {
    let paginationHTML = '';
    
    // First page button
    if (currentPage > 1) {
        paginationHTML += `<a href="#" onclick="goToPage(1)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">«</a>`;
    } else {
        paginationHTML += `<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">«</button>`;
    }
    
    // Previous page button
    if (currentPage > 1) {
        paginationHTML += `<a href="#" onclick="goToPage(${currentPage - 1})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">‹</a>`;
    } else {
        paginationHTML += `<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">‹</button>`;
    }
    
    // Page numbers
    const maxVisiblePages = 5;
    let startPage, endPage;
    
    if (totalPages <= maxVisiblePages) {
        startPage = 1;
        endPage = totalPages;
    } else {
        const maxPagesBeforeCurrent = Math.floor(maxVisiblePages / 2);
        const maxPagesAfterCurrent = Math.ceil(maxVisiblePages / 2) - 1;
        
        if (currentPage <= maxPagesBeforeCurrent) {
            startPage = 1;
            endPage = maxVisiblePages;
        } else if (currentPage + maxPagesAfterCurrent >= totalPages) {
            startPage = totalPages - maxVisiblePages + 1;
            endPage = totalPages;
        } else {
            startPage = currentPage - maxPagesBeforeCurrent;
            endPage = currentPage + maxPagesAfterCurrent;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = (i === currentPage) ? 'bg-sidebar-accent text-white' : '';
        paginationHTML += `<a href="#" onclick="goToPage(${i})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${activeClass}">${i}</a>`;
    }
    
    // Next page button
    if (currentPage < totalPages) {
        paginationHTML += `<a href="#" onclick="goToPage(${currentPage + 1})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">›</a>`;
    } else {
        paginationHTML += `<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">›</button>`;
    }
    
    // Last page button
    if (currentPage < totalPages) {
        paginationHTML += `<a href="#" onclick="goToPage(${totalPages})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">»</a>`;
    } else {
        paginationHTML += `<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">»</button>`;
    }
    
    document.getElementById('paginationControls').innerHTML = paginationHTML;
}

// Function to update pagination controls for Fully Paid
function updateFullyPaidPaginationControls(currentPage, totalPages) {
    let paginationHTML = '';
    
    // First page button
    if (currentPage > 1) {
        paginationHTML += `<a href="#" onclick="goToFullyPaidPage(1)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">«</a>`;
    } else {
        paginationHTML += `<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">«</button>`;
    }
    
    // Previous page button
    if (currentPage > 1) {
        paginationHTML += `<a href="#" onclick="goToFullyPaidPage(${currentPage - 1})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">‹</a>`;
    } else {
        paginationHTML += `<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">‹</button>`;
    }
    
    // Page numbers
    const maxVisiblePages = 5;
    let startPage, endPage;
    
    if (totalPages <= maxVisiblePages) {
        startPage = 1;
        endPage = totalPages;
    } else {
        const maxPagesBeforeCurrent = Math.floor(maxVisiblePages / 2);
        const maxPagesAfterCurrent = Math.ceil(maxVisiblePages / 2) - 1;
        
        if (currentPage <= maxPagesBeforeCurrent) {
            startPage = 1;
            endPage = maxVisiblePages;
        } else if (currentPage + maxPagesAfterCurrent >= totalPages) {
            startPage = totalPages - maxVisiblePages + 1;
            endPage = totalPages;
        } else {
            startPage = currentPage - maxPagesBeforeCurrent;
            endPage = currentPage + maxPagesAfterCurrent;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = (i === currentPage) ? 'bg-sidebar-accent text-white' : '';
        paginationHTML += `<a href="#" onclick="goToFullyPaidPage(${i})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${activeClass}">${i}</a>`;
    }
    
    // Next page button
    if (currentPage < totalPages) {
        paginationHTML += `<a href="#" onclick="goToFullyPaidPage(${currentPage + 1})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">›</a>`;
    } else {
        paginationHTML += `<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">›</button>`;
    }
    
    // Last page button
    if (currentPage < totalPages) {
        paginationHTML += `<a href="#" onclick="goToFullyPaidPage(${totalPages})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">»</a>`;
    } else {
        paginationHTML += `<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">»</button>`;
    }
    
    document.getElementById('paginationControlsFullyPaid').innerHTML = paginationHTML;
}

// Function to update pagination controls for outstanding services
function updateOutstandingPaginationControls(currentPage, totalPages) {
    let paginationHTML = '';
    
    // First page button
    if (currentPage > 1) {
        paginationHTML += `<a href="#" onclick="goToOutstandingPage(1)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">«</a>`;
    } else {
        paginationHTML += `<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">«</button>`;
    }
    
    // Previous page button
    if (currentPage > 1) {
        paginationHTML += `<a href="#" onclick="goToOutstandingPage(${currentPage - 1})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">‹</a>`;
    } else {
        paginationHTML += `<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">‹</button>`;
    }
    
    // Page numbers
    const maxVisiblePages = 5;
    let startPage, endPage;
    
    if (totalPages <= maxVisiblePages) {
        startPage = 1;
        endPage = totalPages;
    } else {
        const maxPagesBeforeCurrent = Math.floor(maxVisiblePages / 2);
        const maxPagesAfterCurrent = Math.ceil(maxVisiblePages / 2) - 1;
        
        if (currentPage <= maxPagesBeforeCurrent) {
            startPage = 1;
            endPage = maxVisiblePages;
        } else if (currentPage + maxPagesAfterCurrent >= totalPages) {
            startPage = totalPages - maxVisiblePages + 1;
            endPage = totalPages;
        } else {
            startPage = currentPage - maxPagesBeforeCurrent;
            endPage = currentPage + maxPagesAfterCurrent;
        }
    }
    
    for (let i = startPage; i <= endPage; i++) {
        const activeClass = (i === currentPage) ? 'bg-sidebar-accent text-white' : '';
        paginationHTML += `<a href="#" onclick="goToOutstandingPage(${i})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${activeClass}">${i}</a>`;
    }
    
    // Next page button
    if (currentPage < totalPages) {
        paginationHTML += `<a href="#" onclick="goToOutstandingPage(${currentPage + 1})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">›</a>`;
    } else {
        paginationHTML += `<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">›</button>`;
    }
    
    // Last page button
    if (currentPage < totalPages) {
        paginationHTML += `<a href="#" onclick="goToOutstandingPage(${totalPages})" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">»</a>`;
    } else {
        paginationHTML += `<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">»</button>`;
    }
    
    document.getElementById('paginationOutstandingControls').innerHTML = paginationHTML;
}
