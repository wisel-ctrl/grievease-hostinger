<?php
// employee_inventory.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check for employee user type (user_type = 2)
if ($_SESSION['user_type'] != 2) {
    // Redirect to appropriate page based on user type
    switch ($_SESSION['user_type']) {
        case 1: // Admin
            header("Location: ../admin/admin_index.php");
            break;
        case 3: // Customer
            header("Location: ../customer/index.php");
            break;
        default:
            // Invalid user_type
            session_destroy();
            header("Location: ../Landing_Page/login.php");
    }
    exit();
}

// Database connection
require_once '../db_connect.php';

// Get employee information
$user_id = $_SESSION['user_id'];
$query = "SELECT id, first_name, last_name, branch_loc FROM users WHERE id = ? AND user_type = 2";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Employee not found or not valid
    session_destroy();
    header("Location: ../Landing_Page/login.php");
    exit();
}

$employee = $result->fetch_assoc();
$branch_id = $employee['branch_loc'];

// Function to generate table row
function generateInventoryRow($row) {
    // Determine quantity cell class based on stock level
    $quantity = $row["quantity"];
    
    // Simplified stock level visualization with just three levels using text color
    if ($quantity <= 2) { // Critical stock
        $quantityClass = 'quantity-cell text-red-600 font-bold';
        $quantityText = $quantity . ' <span class="text-xs ml-1">(Critical)</span>';
        $stockIcon = '<i class="fas fa-exclamation-triangle mr-1"></i>';
    } elseif ($quantity <= 5) { // Low stock
        $quantityClass = 'quantity-cell text-yellow-600 font-medium';
        $quantityText = $quantity . ' <span class="text-xs ml-1">(Low)</span>';
        $stockIcon = '<i class="fas fa-arrow-down mr-1"></i>';
    } else { // Normal stock
        $quantityClass = 'quantity-cell text-green-600';
        $quantityText = $quantity;
        $stockIcon = '';
    }

    $html = '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">';
    $html .= '<td class="p-4 text-sm text-sidebar-text font-medium">#INV-' . str_pad($row["inventory_id"], 3, '0', STR_PAD_LEFT) . '</td>';
    $html .= '<td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($row["item_name"]) . '</td>';
    $html .= '<td class="p-4 text-sm text-sidebar-text">';
    $html .= '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">';
    $html .= htmlspecialchars($row["category"]) . '</span>';
    $html .= '</td>';
    
    // Enhanced quantity cell with visual indicators and proper padding
    $html .= '<td class="p-0 text-sm">';
    $html .= '<div class="' . $quantityClass . ' px-3 py-2 rounded-lg flex items-center justify-center">';
    $html .= $stockIcon . $quantityText;
    $html .= '</div>';
    $html .= '</td>';
    
    $html .= '<td class="p-4 text-sm font-medium text-sidebar-text" data-sort-value="' . $row["price"] . '">₱' . number_format($row["price"], 2) . '</td>';
    $html .= '<td class="p-4 text-sm font-medium text-sidebar-text" data-sort-value="' . $row["total_value"] . '">₱' . number_format($row["total_value"], 2) . '</td>';
    $html .= '<td class="p-4 text-sm">';
    $html .= '<div class="flex space-x-2">';
    $html .= '<button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all edit-btn" data-id="' . $row['inventory_id'] . '">';
    $html .= '<i class="fas fa-edit"></i>';
    $html .= '</button>';
    $html .= '<button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all archive-btn" data-id="' . $row['inventory_id'] . '" data-name="' . htmlspecialchars($row['item_name']) . '">';
    $html .= '<i class="fas fa-archive"></i>';
    $html .= '</button>';
    $html .= '</div>';
    $html .= '</td>';
    $html .= '</tr>';
    return $html;
}

// Check if it's an AJAX request
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    $itemsPerPage = 5;
    $currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $offset = ($currentPage - 1) * $itemsPerPage;

    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
    
    $count_query = "SELECT COUNT(*) as total FROM inventory_tb WHERE branch_id = ? AND status = 1";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $branch_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $totalItems = $count_result->fetch_assoc()['total'];
    $totalPages = ceil($totalItems / $itemsPerPage);

    $inventory_query = "SELECT i.inventory_id, i.item_name, ic.category_name as category, 
                       i.quantity, i.price, i.total_value, i.status
                       FROM inventory_tb i
                       JOIN inventory_category ic ON i.category_id = ic.category_id
                       WHERE i.branch_id = ? AND i.status = 1";
    
    switch ($sort) {
        case 'price_asc':
            $inventory_query .= " ORDER BY i.price ASC";
            break;
        case 'price_desc':
            $inventory_query .= " ORDER BY i.price DESC";
            break;
        case 'quantity_asc':
            $inventory_query .= " ORDER BY i.quantity ASC";
            break;
        case 'quantity_desc':
            $inventory_query .= " ORDER BY i.quantity DESC";
            break;
        case 'name_asc':
            $inventory_query .= " ORDER BY i.item_name ASC";
            break;
        case 'name_desc':
            $inventory_query .= " ORDER BY i.item_name DESC";
            break;
        default:
            $inventory_query .= " ORDER BY i.inventory_id ASC";
            break;
    }

    $inventory_query .= " LIMIT ? OFFSET ?";
    $inventory_stmt = $conn->prepare($inventory_query);
    $inventory_stmt->bind_param("iii", $branch_id, $itemsPerPage, $offset);
    $inventory_stmt->execute();
    $paginatedResult = $inventory_stmt->get_result();

    $rows = '';
    if ($paginatedResult->num_rows > 0) {
        while($row = $paginatedResult->fetch_assoc()) {
            $rows .= generateInventoryRow($row);
        }
    } else {
        $rows .= '<tr>';
        $rows .= '<td colspan="7" class="p-6 text-sm text-center">';
        $rows .= '<div class="flex flex-col items-center">';
        $rows .= '<i class="fas fa-box-open text-gray-300 text-4xl mb-3"></i>';
        $rows .= '<p class="text-gray-500">No inventory items found for this branch</p>';
        $rows .= '</div>';
        $rows .= '</td>';
        $rows .= '</tr>';
    }

    $paginationInfo = 'Showing ' . min(($currentPage - 1) * $itemsPerPage + 1, $totalItems) . ' - ' . 
                     min($currentPage * $itemsPerPage, $totalItems) . ' of ' . $totalItems . ' items';

    $paginationLinks = '';
    // First page (<<)
    if ($currentPage > 1) {
        $paginationLinks .= '<a href="#" onclick="loadPage(' . $branch_id . ', 1, \'' . $sort . '\')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">«</a>';
    } else {
        $paginationLinks .= '<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">«</button>';
    }
    
    // Previous page (<)
    if ($currentPage > 1) {
        $paginationLinks .= '<a href="#" onclick="loadPage(' . $branch_id . ', ' . ($currentPage - 1) . ', \'' . $sort . '\')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">‹</a>';
    } else {
        $paginationLinks .= '<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">‹</button>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $activeClass = ($i == $currentPage) ? 'bg-sidebar-accent text-white' : '';
        $paginationLinks .= '<a href="#" onclick="loadPage(' . $branch_id . ', ' . $i . ', \'' . $sort . '\')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ' . $activeClass . '">' . $i . '</a>';
    }
    
    // Next page (>)
    if ($currentPage < $totalPages) {
        $paginationLinks .= '<a href="#" onclick="loadPage(' . $branch_id . ', ' . ($currentPage + 1) . ', \'' . $sort . '\')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">›</a>';
    } else {
        $paginationLinks .= '<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">›</button>';
    }
    
    // Last page (>>)
    if ($currentPage < $totalPages) {
        $paginationLinks .= '<a href="#" onclick="loadPage(' . $branch_id . ', ' . $totalPages . ', \'' . $sort . '\')" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">»</a>';
    } else {
        $paginationLinks .= '<button disabled class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 cursor-not-allowed">»</button>';
    }

    header('Content-Type: application/json');
    echo json_encode([
        'rows' => $rows,
        'paginationInfo' => $paginationInfo,
        'paginationLinks' => $paginationLinks
    ]);
    exit();
}

// Regular page load - show full page
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Inventory</title>
  <?php include 'faviconLogo.php'; ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
   <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    
    /* Add this to your existing styles */
    .main-content {
      margin-left: 16rem; /* Adjust this value to match the width of your sidebar */
      width: calc(100% - 16rem); /* Ensure the main content takes up the remaining width */
      z-index: 1; /* Ensure the main content is above the sidebar */
    }

    .sidebar {
      z-index: 10; /* Ensure the sidebar is below the main content */
    }
    /* Add this to your existing styles */
    #sidebar {
      transition: width 0.3s ease, opacity 0.3s ease, visibility 0.3s ease;
    }

    #main-content {
      transition: margin-left 0.3s ease;
    }

    .w-0 {
      width: 0;
    }

    .opacity-0 {
      opacity: 0;
    }

    .invisible {
      visibility: hidden;
    }
    .w-\[calc\(100\%-16rem\)\] {
      width: calc(100% - 16rem);
    }

    .w-\[calc\(100\%-4rem\)\] {
      width: calc(100% - 4rem);
    }

    /* Heatmap color scale */
    .quantity-cell {
      border-radius: 0.5rem;
      padding: 0.5rem 1rem;
      text-align: center;
    }
    
    .quantity-critical {
      background-color: #fee2e2; /* red-100 */
      color: #b91c1c; /* red-800 */
      font-weight: 600;
    }
    
    .quantity-warning {
      background-color: #fef3c7; /* amber-100 */
      color: #92400e; /* amber-800 */
      font-weight: 500;
    }
    
    .quantity-normal {
      background-color: #dcfce7; /* green-100 */
      color: #166534; /* green-800 */
    }
    
    .quantity-high {
      background-color: #f0fdf4; /* emerald-50 */
      color: #064e3b; /* emerald-900 */
    }
    
    /* Modal blur effects */
    .modal-blur {
      filter: blur(4px);
      transition: filter 0.3s ease;
      pointer-events: none;
    }
    
    .modal-blur-remove {
      filter: none;
      transition: filter 0.3s ease;
      pointer-events: auto;
    }
  </style>
</head>
<body class="flex bg-gray-50">
    <!-- Archived Inventory Items Modal -->
{{ ... }}
        console.log(`Sorting branch ${branchId} by column ${columnIndex}`);
        // You can extend this to include sorting in your AJAX call
    }
    
    // Add Inventory function
    // Function to open the add inventory modal
    function openAddInventoryModal() {
        document.getElementById('addInventoryModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        
        // Add blur effect to sidebar and main content
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        if (sidebar) sidebar.classList.add('modal-blur');
        if (mainContent) mainContent.classList.add('modal-blur');
    }

    // Function to close the add inventory modal
    function closeAddInventoryModal() {
        document.getElementById('addInventoryModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        
        // Remove blur effect from sidebar and main content
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');
        if (sidebar) sidebar.classList.remove('modal-blur');
        if (mainContent) mainContent.classList.remove('modal-blur');
        
        // Reset form and clear preview
        document.getElementById('addInventoryForm').reset();
        document.getElementById('imagePreviewContainer').classList.add('hidden');
    }

    // Image preview function
    function previewImage(input) {
        const previewContainer = document.getElementById('imagePreviewContainer');
{{ ... }}
        });
    }
});

//edit inventory functions
// Function to open the edit inventory modal
function openEditInventoryModal(inventoryId) {
    // Show loading state
    const modal = document.getElementById('editInventoryModal');
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    
    // Add blur effect to sidebar and main content
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    if (sidebar) sidebar.classList.add('modal-blur');
    if (mainContent) mainContent.classList.add('modal-blur');
    
    // Fetch inventory item details
    fetch(`inventory/get_inventory_item.php?id=${inventoryId}`)
        .then(response => response.json())
        .then(data => {
{{ ... }}
            });
            closeEditInventoryModal();
        });
}

// Function to close the edit inventory modal
function closeEditInventoryModal() {
    document.getElementById('editInventoryModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    
    // Remove blur effect from sidebar and main content
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    if (sidebar) sidebar.classList.remove('modal-blur');
    if (mainContent) mainContent.classList.remove('modal-blur');
    
    // Reset form and clear preview
    document.getElementById('editInventoryForm').reset();
    document.getElementById('editImagePreviewContainer').classList.add('hidden');
}

// Image preview function for edit modal
function previewEditImage(input) {
    const previewContainer = document.getElementById('editImagePreviewContainer');
{{ ... }}
        rows.forEach(row => row.style.display = '');
    });
}


// Function to open archived items modal
function openArchivedModal() {
    const modal = document.getElementById('archivedItemsModal');
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    
    // Add blur effect to sidebar and main content
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    if (sidebar) sidebar.classList.add('modal-blur');
    if (mainContent) mainContent.classList.add('modal-blur');
    
    // Load archived items
    loadArchivedItems();
    
    // Set up search functionality
    document.getElementById('archivedItemsSearch').addEventListener('input', 
        debounce(searchArchivedItems, 300));
    
    // Set up clear button
    setupArchivedSearchClear();
}

// Function to close archived items modal
function closeArchivedModal() {
    document.getElementById('archivedItemsModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    
    // Remove blur effect from sidebar and main content
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('main-content');
    if (sidebar) sidebar.classList.remove('modal-blur');
    if (mainContent) mainContent.classList.remove('modal-blur');
}

// Function to load archived items via AJAX
async function loadArchivedItems() {
    try {
{{ ... }}
        const response = await fetch('inventory/get_archived_items.php?branch_id=<?php echo $branch_id; ?>');
        const data = await response.json();
        
        if (data.success) {
            renderArchivedItems(data.items);
        } else {
            throw new Error(data.message || 'Failed to load archived items');
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('archivedItemsContent').innerHTML = `
            <div class="text-center py-10 text-gray-500">
                <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                <p>${error.message || 'Failed to load archived items'}</p>
            </div>
        `;
    }
}

// Function to render archived items in the modal
function renderArchivedItems(items) {
    const container = document.getElementById('archivedItemsContent');
    const searchTerm = document.getElementById('archivedItemsSearch').value.toLowerCase();
    
    if (!items || items.length === 0) {
        container.innerHTML = `
            <div class="text-center py-10 text-gray-500">
                <i class="fas fa-box-open text-3xl mb-3"></i>
                <p>No archived items found</p>
            </div>
        `;
        return;
    }
    
    let html = `
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
    `;
    
    items.forEach(item => {
        // Safely format price
        const price = typeof item.price === 'number' ? item.price : 
                     typeof item.price === 'string' ? parseFloat(item.price) : 0;
        const formattedPrice = isNaN(price) ? '0.00' : price.toFixed(2);
        
        // Check if item matches current search term
        const matchesSearch = searchTerm === '' || 
                            item.item_name.toLowerCase().includes(searchTerm) ||
                            (item.category && item.category.toLowerCase().includes(searchTerm)) ||
                            item.inventory_id.toString().includes(searchTerm);
        
        html += `
            <tr ${matchesSearch ? '' : 'style="display: none"'}>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">#INV-${item.inventory_id}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">${escapeHtml(item.item_name)}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${escapeHtml(item.category)}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${item.quantity || 0}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">₱${formattedPrice}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                    <button class="p-1.5 bg-green-100 text-green-600 rounded hover:bg-green-200 transition-all unarchive-btn" 
                            data-id="${item.inventory_id}" data-name="${escapeHtml(item.item_name)}">
                        <i class="fas fa-undo"></i> Unarchive
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
    
    // Add event listeners for unarchive buttons
    document.querySelectorAll('.unarchive-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const inventoryId = this.getAttribute('data-id');
            const itemName = this.getAttribute('data-name');
            confirmUnarchive(inventoryId, itemName);
        });
    });
}

// Function to confirm unarchive
function confirmUnarchive(inventoryId, itemName) {
    Swal.fire({
        title: 'Unarchive Inventory Item',
        html: `Are you sure you want to unarchive <strong>${itemName}</strong>?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, unarchive it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            unarchiveInventoryItem(inventoryId);
        }
    });
}

// Function to unarchive inventory item
async function unarchiveInventoryItem(inventoryId) {
    try {
        const response = await fetch('inventory/unarchive_inventory.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ inventory_id: inventoryId })
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Unarchived!',
                text: data.message,
                showConfirmButton: false,
                timer: 1500
            });
            
            // Reload the archived items list
            loadArchivedItems();
            // Refresh the main inventory table
            loadPage(<?php echo $branch_id; ?>, 1);
        } else {
            throw new Error(data.message || 'Failed to unarchive item');
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message || 'An error occurred while unarchiving the item',
        });
    }
}

// Helper function to escape HTML
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

//searchbar functions
// Function to perform the search
function performInventorySearch(searchTerm, branchId, page = 1) {
    const loadingIndicator = document.getElementById(`loadingIndicator${branchId}`);
    const inventoryTable = document.getElementById(`inventoryTable_${branchId}`);
    
    // Show loading indicator
    loadingIndicator.classList.remove('hidden');
    
    // Make AJAX request to search endpoint
    fetch(`inventory/search_inventory.php?search=${encodeURIComponent(searchTerm)}&branch_id=${branchId}&page=${page}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update table with search results
                inventoryTable.innerHTML = data.rows;
                
                // Update pagination info if available
                if (data.paginationInfo) {
                    document.getElementById(`paginationInfo_${branchId}`).textContent = data.paginationInfo;
                }
                
                // Update pagination links if available
                if (data.paginationLinks) {
                    document.getElementById(`paginationLinks_${branchId}`).innerHTML = data.paginationLinks;
                }
            } else {
                throw new Error(data.message || 'Search failed');
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            inventoryTable.innerHTML = `
                <tr>
                    <td colspan="7" class="p-6 text-sm text-center text-red-500">
                        Error: ${error.message || 'Failed to perform search'}
                    </td>
                </tr>
            `;
        })
        .finally(() => {
            loadingIndicator.classList.add('hidden');
        });
}

// Function to handle search input
function setupInventorySearch(branchId) {
    const searchInput = document.getElementById('inventorySearch');
    const clearBtn = document.getElementById('clearSearch');
    
    // Debounced search function
    const debouncedSearch = debounce((searchTerm) => {
        if (searchTerm.length > 0) {
            performInventorySearch(searchTerm, branchId);
        } else {
            // If search is empty, load the regular page
            loadPage(branchId, 1);
        }
    }, 300);
    
    // Search input event
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.trim();
        
        // Show/hide clear button
        clearBtn.style.display = searchTerm.length > 0 ? '' : 'none';
        
        // Only search if there are at least 2 characters or empty
        if (searchTerm.length > 1 || searchTerm.length === 0) {
            debouncedSearch(searchTerm);
        }
    });
    
    // Clear search button
    clearBtn.addEventListener('click', function() {
        searchInput.value = '';
        clearBtn.style.display = 'none';
        loadPage(branchId, 1);
    });
    
    // Also trigger search on Enter key
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const searchTerm = this.value.trim();
            if (searchTerm.length > 0) {
                performInventorySearch(searchTerm, branchId);
            }
        }
    });
}

// Initialize the search when the page loads
document.addEventListener('DOMContentLoaded', function() {
    setupInventorySearch(<?php echo $branch_id; ?>);
});


// Toggle filter dropdown
function setupFilterDropdowns(branchId) {
    // Handle both mobile and desktop filter buttons
    const filterButtons = [
        document.getElementById(`filterButton_${branchId}`), // Mobile
        document.getElementById(`filterButton_mobile${branchId}`) // Desktop
    ];

    const filterDropdowns = [
        document.getElementById(`filterDropdown_${branchId}`), // Mobile
        document.getElementById(`filterDropdown_mobile${branchId}`) // Desktop
    ];

    filterButtons.forEach((button, index) => {
        if (button) {
            button.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent closing immediately
                const dropdown = filterDropdowns[index];
                if (dropdown) {
                    // Toggle visibility
                    dropdown.classList.toggle('hidden');
                    // Update filter indicator
                    const indicator = document.getElementById(`filterIndicator_${branchId}`);
                    if (indicator) {
                        indicator.classList.toggle('hidden', dropdown.classList.contains('hidden'));
                    }
                }
            });
        }
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        filterDropdowns.forEach(dropdown => {
            if (dropdown && !dropdown.contains(e.target) && !filterButtons.some(btn => btn && btn.contains(e.target))) {
                dropdown.classList.add('hidden');
                // Update filter indicators
                const indicator = document.getElementById(`filterIndicator_${branchId}`);
                if (indicator) {
                    indicator.classList.add('hidden');
                }
            }
        });
    });

    // Handle filter option clicks
    document.querySelectorAll(`.filter-option[data-branch="${branchId}"]`).forEach(option => {
        option.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent dropdown from closing
            const sortType = this.getAttribute('data-sort');
            applyFilter(branchId, sortType);
            // Close dropdown after selection
            filterDropdowns.forEach(dropdown => {
                if (dropdown) {
                    dropdown.classList.add('hidden');
                }
            });
            // Update filter indicator
            const indicator = document.getElementById(`filterIndicator_${branchId}`);
            if (indicator) {
                indicator.classList.remove('hidden');
            }
        });
    });
}

// Apply filter and reload table
function applyFilter(branchId, sortType) {
    // Update the loadPage function to include sorting
    loadPage(branchId, 1, sortType);
}

// Modified loadPage function to handle sorting
function loadPage(branchId, page, sortType = 'default') {
    const tableContainer = document.getElementById(`tableContainer${branchId}`);
    const loadingIndicator = document.getElementById(`loadingIndicator${branchId}`);
    const inventoryTable = document.getElementById(`inventoryTable_${branchId}`);
    const paginationInfo = document.getElementById(`paginationInfo_${branchId}`);
    const paginationLinks = document.getElementById(`paginationLinks_${branchId}`);

    // Show loading indicator
    loadingIndicator.classList.remove('hidden');
    tableContainer.style.opacity = '0.5';

    // Construct the URL with sort parameter
    let url = `employee_inventory.php?ajax=1&page=${page}&branch_id=${branchId}`;
    if (sortType !== 'default') {
        url += `&sort=${sortType}`;
    }

    // Make AJAX request
    fetch(url)
        .then(response => response.json())
        .then(data => {
            // Update table content
            inventoryTable.innerHTML = data.rows;
            
            // Update pagination info
            paginationInfo.textContent = data.paginationInfo;
            
            // Update pagination links
            paginationLinks.innerHTML = data.paginationLinks;
            
            // Hide loading indicator
            loadingIndicator.classList.add('hidden');
            tableContainer.style.opacity = '1';
        })
        .catch(error => {
            console.error('Error:', error);
            loadingIndicator.classList.add('hidden');
            tableContainer.style.opacity = '1';
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load inventory data',
            });
        });
}

// Initialize filter dropdowns when the page loads
document.addEventListener('DOMContentLoaded', function() {
    setupFilterDropdowns(<?php echo $branch_id; ?>);
    loadPage(<?php echo $branch_id; ?>, 1);
});

</script>
  <script src="sidebar.js"></script>
  <script src="tailwind.js"></script>
</body>
</html>