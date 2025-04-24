<?php

session_start();

require_once '../db_connect.php'; // Database connection

// Get user's first name from database
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name , last_name , email , birthdate FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$first_name = $row['first_name']; // We're confident user_id exists
$last_name = $row['last_name'];
$email = $row['email'];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
  // Redirect to login page
  header("Location: ../Landing_Page/login.php");
  exit();
}

// Check for admin user type (user_type = 1)
if ($_SESSION['user_type'] != 1) {
  // Redirect to appropriate page based on user type
  switch ($_SESSION['user_type']) {
      case 2:
          header("Location: ../employee/index.php");
          break;
      case 3:
          header("Location: ../customer/index.php");
          break;
      default:
          // Invalid user_type
          session_destroy();
          header("Location: ../Landing_Page/login.php");
  }
  exit();
}

// Optional: Check for session timeout (30 minutes)
$session_timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
  // Session has expired
  session_unset();
  session_destroy();
  header("Location: ../Landing_Page/login.php?timeout=1");
  exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Prevent caching for authenticated pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Services - GrievEase</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  
</head>
<body class="flex bg-gray-50">

<?php include 'admin_sidebar.php'; ?>

  <!-- Main Content -->
<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Service Management</h1>
    </div>
    
  </div>

  <!-- Add New Service Button -->
  <?php
// Database connection settings
require_once '../db_connect.php';

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get all branches to display separate tables
$branchSql = "SELECT branch_id, branch_name FROM branch_tb";
$branchResult = $conn->query($branchSql);

// Function to format price
function formatPrice($price) {
    return '$' . number_format($price, 2);
}
?>


  <!-- Summary statistics row -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <?php
    // Get total services count
    $totalSql = "SELECT COUNT(*) as total FROM services_tb";
    $totalResult = $conn->query($totalSql);
    $total = $totalResult->fetch_assoc()['total'];
    
    // Get active services count
    $activeSql = "SELECT COUNT(*) as total FROM services_tb WHERE status = 'Active'";
    $activeResult = $conn->query($activeSql);
    $active = $activeResult->fetch_assoc()['total'];
    
    // Get inactive services count
    $inactiveSql = "SELECT COUNT(*) as total FROM services_tb WHERE status = 'Inactive'";
    $inactiveResult = $conn->query($inactiveSql);
    $inactive = $inactiveResult->fetch_assoc()['total'];
    
    // Card data array
    $cards = [
        [
            'title' => 'Total Services',
            'value' => $total,
            'icon' => 'tags',
            'color' => 'blue'
        ],
        [
            'title' => 'Active Services',
            'value' => $active,
            'icon' => 'check-circle',
            'color' => 'green'
        ],
        [
            'title' => 'Inactive Services',
            'value' => $inactive,
            'icon' => 'pause-circle',
            'color' => 'orange'
        ]
    ];
    
    foreach ($cards as $card) {
    ?>
    
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <!-- Card header with brighter gradient background -->
        <div class="bg-gradient-to-r from-<?php echo $card['color']; ?>-100 to-<?php echo $card['color']; ?>-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700"><?php echo $card['title']; ?></h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-<?php echo $card['color']; ?>-600 flex items-center justify-center">
                    <i class="fas fa-<?php echo $card['icon']; ?>"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold text-gray-800"><?php echo $card['value']; ?></span>
            </div>
        </div>
        
        <!-- Card footer with simple info -->
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-gray-500">
                <span class="text-xs">Updated today</span>
            </div>
        </div>
    </div>
    
    <?php } ?>
</div>

  <?php
// Pagination and Search/Filter Logic
$recordsPerPage = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$searchQuery = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$statusFilter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';

// Loop through each branch and create a separate table
if ($branchResult->num_rows > 0) {
    while($branch = $branchResult->fetch_assoc()) {
        $branchId = $branch['branch_id'];
        $branchName = $branch['branch_name'];
        
        // SQL query for this branch with search and filter
        $sql = "SELECT 
            s.service_id, 
            s.service_name, 
            sc.service_category_name, 
            s.selling_price, 
            s.status,
            b.branch_name
        FROM services_tb s
        JOIN service_category sc ON s.service_categoryID = sc.service_categoryID
        JOIN branch_tb b ON s.branch_id = b.branch_id
        WHERE s.branch_id = $branchId
        " . 
        ($searchQuery ? "AND (s.service_name LIKE '%$searchQuery%' OR sc.service_category_name LIKE '%$searchQuery%') " : '') .
        ($categoryFilter ? "AND sc.service_category_name = '$categoryFilter' " : '') .
        ($statusFilter ? "AND s.status = '$statusFilter' " : '');

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as count FROM services_tb s
        JOIN service_category sc ON s.service_categoryID = sc.service_categoryID
        WHERE s.branch_id = $branchId
        " . 
        ($searchQuery ? "AND (s.service_name LIKE '%$searchQuery%' OR sc.service_category_name LIKE '%$searchQuery%') " : '') .
        ($categoryFilter ? "AND sc.service_category_name = '$categoryFilter' " : '') .
        ($statusFilter ? "AND s.status = '$statusFilter' " : '');
        
        $countResult = $conn->query($countSql);
        $totalServices = $countResult->fetch_assoc()['count'];
        $totalPages = ceil($totalServices / $recordsPerPage);

        // Add LIMIT for pagination
        $offset = ($page - 1) * $recordsPerPage;
        $sql .= " LIMIT $offset, $recordsPerPage";

        $result = $conn->query($sql);

        // Get unique categories and statuses for filters
        $categoriesSql = "SELECT DISTINCT service_category_name FROM service_category sc
        JOIN services_tb s ON s.service_categoryID = sc.service_categoryID
        WHERE s.branch_id = $branchId";
        $categoriesResult = $conn->query($categoriesSql);

        $statusesSql = "SELECT DISTINCT status FROM services_tb WHERE branch_id = $branchId";
        $statusesResult = $conn->query($statusesSql);
?>

<!-- Branch Card -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden branch-container" data-branch-id="<?php echo $branchId; ?>">
    <!-- Branch Header with Search and Filters -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
        <!-- Desktop layout for big screens - Title on left, controls on right -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Title and Counter -->
            <div class="flex items-center gap-3 mb-4 lg:mb-0">
                <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Branch: <?php echo $branchName; ?></h4>
                
                <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                    <i class="fas fa-clipboard-list"></i>
                    <?php echo $totalServices . ($totalServices != 1 ? "" : ""); ?>
                </span>
            </div>
            
            <!-- Controls for big screens - aligned right -->
            <div class="hidden lg:flex items-center gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <input type="text" id="searchInput<?php echo $branchId; ?>" 
                           placeholder="Search services..." 
                           value="<?php echo htmlspecialchars($searchQuery); ?>"
                           class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                           oninput="debouncedFilter(<?php echo $branchId; ?>)">
                    <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
                </div>

                <!-- Filter Dropdown -->
                <div class="relative filter-dropdown">
                    <button id="filterToggle<?php echo $branchId; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover"
                            onclick="toggleFilterWindow(<?php echo $branchId; ?>)">
                        <i class="fas fa-filter text-sidebar-accent"></i>
                        <span>Filters</span>
                        <?php if($categoryFilter || $statusFilter): ?>
                            <span class="h-2 w-2 bg-sidebar-accent rounded-full"></span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Filter Window -->
                    <div id="filterWindow<?php echo $branchId; ?>" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
                        <div class="space-y-4">
                            <!-- Category Filter -->
                            <div>
                                <h5 class="text-sm font-medium text-sidebar-text mb-2">Category</h5>
                                <div class="space-y-1">
                                    <div class="flex items-center cursor-pointer" onclick="setFilter(<?php echo $branchId; ?>, 'category', '')">
                                        <span class="filter-option <?php echo !$categoryFilter ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?> px-2 py-1 rounded text-sm w-full">
                                            All Categories
                                        </span>
                                    </div>
                                    <?php 
                                    $categoriesResult->data_seek(0);
                                    while($category = $categoriesResult->fetch_assoc()): 
                                        $isActive = $categoryFilter === $category['service_category_name'];
                                    ?>
                                        <div class="flex items-center cursor-pointer" onclick="setFilter(<?php echo $branchId; ?>, 'category', '<?php echo urlencode($category['service_category_name']); ?>')">
                                            <span class="filter-option <?php echo $isActive ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?> px-2 py-1 rounded text-sm w-full">
                                                <?php echo htmlspecialchars($category['service_category_name']); ?>
                                            </span>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            
                            <!-- Status Filter -->
                            <div>
                                <h5 class="text-sm font-medium text-sidebar-text mb-2">Status</h5>
                                <div class="space-y-1">
                                    <div class="flex items-center cursor-pointer" onclick="setFilter(<?php echo $branchId; ?>, 'status', '')">
                                        <span class="filter-option <?php echo !$statusFilter ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?> px-2 py-1 rounded text-sm w-full">
                                            All Statuses
                                        </span>
                                    </div>
                                    <?php 
                                    $statusesResult->data_seek(0);
                                    while($status = $statusesResult->fetch_assoc()): 
                                        $isActive = $statusFilter === $status['status'];
                                    ?>
                                        <div class="flex items-center cursor-pointer" onclick="setFilter(<?php echo $branchId; ?>, 'status', '<?php echo urlencode($status['status']); ?>')">
                                            <span class="filter-option <?php echo $isActive ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?> px-2 py-1 rounded text-sm w-full">
                                                <?php echo htmlspecialchars($status['status']); ?>
                                            </span>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Archive Button -->
                <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover whitespace-nowrap">
                    <i class="fas fa-archive text-sidebar-accent"></i>
                    <span>Archive</span>
                </button>

                <!-- Add Service Button -->
                <button class="px-4 py-2 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap" 
                        onclick="openAddServiceModal(<?php echo $branchId; ?>)"><span>Add New Service</span>
                </button>
            </div>
        </div>
        
        <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
        <div class="lg:hidden w-full mt-4">
            <!-- First row: Search bar with filter and archive icons on the right -->
            <div class="flex items-center w-full gap-3 mb-4">
                <!-- Search Input - Takes most of the space -->
                <div class="relative flex-grow">
                    <input type="text" id="searchInputMobile<?php echo $branchId; ?>" 
                            placeholder="Search services..." 
                            value="<?php echo htmlspecialchars($searchQuery); ?>"
                            class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                            oninput="debouncedFilter(<?php echo $branchId; ?>)">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>

                <!-- Icon-only buttons for filter and archive -->
                <div class="flex items-center gap-3">
                    <!-- Filter Icon Button -->
                    <div class="relative filter-dropdown">
                        <button id="serviceFilterToggle<?php echo $branchId; ?>" class="w-10 h-10 flex items-center justify-center text-sidebar-accent"
                                onclick="toggleFilterWindow(<?php echo $branchId; ?>)">
                            <i class="fas fa-filter text-xl"></i>
                            <span id="filterIndicator<?php echo $branchId; ?>" class="<?php echo ($categoryFilter || $statusFilter) ? '' : 'hidden'; ?> absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
                        </button>
                    </div>

                    <!-- Archive Icon Button -->
                    <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
                        <i class="fas fa-archive text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Second row: Add Service Button - Full width -->
            <div class="w-full">
                <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap w-full justify-center" 
                        onclick="openAddServiceModal(<?php echo $branchId; ?>)"><span>Add New Service</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Responsive Table Container with improved spacing -->
    <div class="overflow-x-auto scrollbar-thin" id="tableContainer<?php echo $branchId; ?>">
        <div id="loadingIndicator<?php echo $branchId; ?>" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
        </div>
        
        <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
        <div class="min-w-full">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-sidebar-border">
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 0)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-hashtag text-sidebar-accent"></i> ID 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 1)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-tag text-sidebar-accent"></i> Service Name 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 2)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-th-list text-sidebar-accent"></i> Category 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 3)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-peso-sign text-sidebar-accent"></i> Price 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 4)">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-toggle-on text-sidebar-accent"></i> Status 
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-cogs text-sidebar-accent"></i> Actions
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <?php
                            $statusClass = $row["status"] == "Active" 
                                ? "bg-green-100 text-green-600 border border-green-200" 
                                : "bg-orange-100 text-orange-500 border border-orange-200";
                            $statusIcon = $row["status"] == "Active" ? "fa-check-circle" : "fa-pause-circle";
                            ?>
                            <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                                <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#SVC-<?php echo str_pad($row['service_id'], 3, "0", STR_PAD_LEFT); ?></td>
                                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row["service_name"]); ?></td>
                                <td class="px-4 py-3.5 text-sm text-sidebar-text">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100"> <?php echo htmlspecialchars($row["service_category_name"]); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text"><?php echo formatPrice($row["selling_price"]); ?></td>
                                <td class="px-4 py-3.5 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                        <i class="fas <?php echo $statusIcon; ?> mr-1"></i> <?php echo htmlspecialchars($row["status"]); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-sm">
                                    <div class="flex space-x-2">
                                        <button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" title="Edit Service" onclick="openEditServiceModal('<?php echo $row["service_id"]; ?>', '<?php echo $branchId; ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip" title="Delete Service" onclick="deleteService('<?php echo $row["service_id"]; ?>', '<?php echo $branchId; ?>')">
                                        <i class="fas fa-archive text-red"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-sm text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                                    <p class="text-gray-500">No services found for this branch</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include 'loadServices/load_service_table'?>
    
    <!-- Sticky Pagination Footer with improved spacing -->
    <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-sm text-gray-500 text-center sm:text-left">
        Showing <?php echo ($offset + 1) . ' - ' . min($offset + $recordsPerPage, $totalServices); ?> 
        of <?php echo $totalServices; ?> services
    </div>
    <div class="flex space-x-2">
        <a href="javascript:void(0)" onclick="changePage(<?php echo $branchId; ?>, <?php echo max(1, $page - 1); ?>)" 
           class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $page <= 1 ? 'opacity-50 pointer-events-none' : ''; ?>">&laquo;</a>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="javascript:void(0)" onclick="changePage(<?php echo $branchId; ?>, <?php echo $i; ?>)"
               class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm <?php echo $i == $page ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>
        
        <a href="javascript:void(0)" onclick="changePage(<?php echo $branchId; ?>, <?php echo min($totalPages, $page + 1); ?>)" 
           class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $page >= $totalPages ? 'opacity-50 pointer-events-none' : ''; ?>">&raquo;</a>
    </div>
    </div>
</div>

<?php
    } // End branch while loop
} else {
    echo "<div class='p-8 text-center bg-white rounded-lg shadow-md'>";
    echo "<i class='fas fa-store-slash text-gray-300 text-5xl mb-4'></i>";
    echo "<p class='text-gray-500 text-lg'>No branches found in the system</p>";
    echo "<button class='mt-4 px-4 py-2 bg-sidebar-accent text-white rounded-md text-sm hover:bg-darkgold transition-all'>";
    echo "<i class='fas fa-plus-circle mr-2'></i> Add Branch</button>";
    echo "</div>";
}
?>

<script>
// Global variable to track active filters
const activeFilters = {};

// Debounce function to limit how often a function is called
function debounce(func, wait) {
    let timeout;
    return function(...args) {
        const context = this;
        clearTimeout(timeout);
        timeout = setTimeout(() => func.apply(context, args), wait);
    };
}

// Toggle filter window visibility
function toggleFilterWindow(branchId) {
    const filterWindow = document.getElementById(`filterWindow${branchId}`);
    filterWindow.classList.toggle('hidden');
    
    // Close other open filter windows
    document.querySelectorAll('.filter-dropdown .absolute').forEach(window => {
        if (window.id !== `filterWindow${branchId}`) {
            window.classList.add('hidden');
        }
    });
}

// Close filter windows when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.filter-dropdown')) {
        document.querySelectorAll('.filter-dropdown .absolute').forEach(window => {
            window.classList.add('hidden');
        });
    }
});

// Set filter and apply immediately
function setFilter(branchId, type, value) {
    // Update the active filter
    if (!activeFilters[branchId]) {
        activeFilters[branchId] = { search: '', category: '', status: '', page: 1 };
    }
    
    if (type === 'category') {
        activeFilters[branchId].category = decodeURIComponent(value);
    } else if (type === 'status') {
        activeFilters[branchId].status = decodeURIComponent(value);
    }
    
    // Close the filter window
    document.getElementById(`filterWindow${branchId}`).classList.add('hidden');
    
    // Apply the filter
    applyFilters(branchId);
}

// Apply all filters for a branch
function applyFilters(branchId) {
    activeFilters[branchId].page = 1; // Reset to first page when filters change
    loadBranchTable(branchId);
}

// Debounced filter for search input
const debouncedFilter = debounce(function(branchId) {
    if (!activeFilters[branchId]) {
        activeFilters[branchId] = { search: '', category: '', status: '', page: 1 };
    }
    activeFilters[branchId].search = document.getElementById(`searchInput${branchId}`).value;
    applyFilters(branchId);
}, 300);

// Change page
function changePage(branchId, page) {
    if (!activeFilters[branchId]) {
        activeFilters[branchId] = { search: '', category: '', status: '', page: 1 };
    }
    activeFilters[branchId].page = page;
    loadBranchTable(branchId);
}

// Load branch table via AJAX
function loadBranchTable(branchId) {
    const container = document.querySelector(`.branch-container[data-branch-id="${branchId}"] .overflow-x-auto`);
    const loadingIndicator = document.getElementById(`loadingIndicator${branchId}`);
    
    if (!container) return;
    
    // Show loading indicator
    if (loadingIndicator) loadingIndicator.classList.remove('hidden');
    
    // Prepare query parameters
    const params = new URLSearchParams();
      params.append('branch_id', branchId);
      params.append('page', activeFilters[branchId]?.page || 1);
      if (activeFilters[branchId]?.search) params.append('search', activeFilters[branchId].search);
      if (activeFilters[branchId]?.category) params.append('category', activeFilters[branchId].category);
      if (activeFilters[branchId]?.status) params.append('status', activeFilters[branchId].status);

// Make AJAX request
    fetch(`loadServices/load_service_table.php?${params.toString()}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(html => {
            container.innerHTML = html;
            if (loadingIndicator) loadingIndicator.classList.add('hidden');
        })
        .catch(error => {
            console.error('Error loading table:', error);
            if (loadingIndicator) loadingIndicator.classList.add('hidden');
        });
}

// Initialize active filters when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize active filters from current values for each branch
    document.querySelectorAll('.branch-container').forEach(container => {
        const branchId = container.dataset.branchId;
        activeFilters[branchId] = {
            search: document.getElementById(`searchInput${branchId}`)?.value || '',
            category: '<?php echo $categoryFilter; ?>',
            status: '<?php echo $statusFilter; ?>',
            page: <?php echo $page; ?>
        };
    });
});
</script>

<!-- ADD SERVICE MODAL -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto" id="addServiceModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-5xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeAddServiceModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Add New Service
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <form id="serviceForm" class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
        <!-- Left Column -->
        <div class="space-y-3 sm:space-y-4">
          <div>
            <label for="serviceName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Service Name
            </label>
            <div class="relative">
              <input type="text" id="serviceName" name="serviceName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
            </div>
          </div>

          <div>
            <label for="serviceDescription" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Description
            </label>
            <div class="relative">
              <textarea id="serviceDescription" name="serviceDescription" rows="3" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"></textarea>
            </div>
          </div>
          
          <!-- Price Section -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
            <div>
              <label for="capitalPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Capital Price
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">₱</span>
                </div>
                <input type="number" id="capitalPrice" name="capitalPrice" placeholder="0.00" min="0" step="0.01" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
              </div>
            </div>
            <div>
              <label for="sellingPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Selling Price
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">₱</span>
                </div>
                <input type="number" id="sellingPrice" name="sellingPrice" placeholder="0.00" min="0" step="0.01" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
              </div>
            </div>
          </div>
          
          <div>
            <label for="serviceCategory" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Category
            </label>
            <div class="relative">
              <select id="serviceCategory" name="serviceCategory" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                <option value="">Select a Category</option>
                <?php
                require_once '../db_connect.php';
                // Fetch service categories
                $sql = "SELECT service_categoryID, service_category_name FROM service_category";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . $row["service_categoryID"] . '">' . htmlspecialchars($row["service_category_name"]) . '</option>';
                    }
                } else {
                    echo '<option value="">No categories found</option>';
                }
                ?>
              </select>
            </div>
          </div>
          
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
            <div>
              <label for="casketType" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Casket Type
              </label>
              <div class="relative">
                <select id="casketType" name="casketType" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                  <option value="">Select a Casket</option>
                  <?php
                  // Fetch inventory items where category_id = 1 and status = 1
                  // $sql = "SELECT inventory_id, item_name FROM inventory_tb WHERE category_id = 1 AND status = 1";
                  // $result = $conn->query($sql);

                  // if ($result->num_rows > 0) {
                  //     while ($row = $result->fetch_assoc()) {
                  //         echo '<option value="' . $row["inventory_id"] . '">' . htmlspecialchars($row["item_name"]) . '</option>';
                  //     }
                  // } else {
                  //     echo '<option value="">No caskets available</option>';
                  // }
                  ?>
                </select>
              </div>
            </div>
            <div>
              <label for="urnType" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Urn Type
              </label>
              <div class="relative">
                <select id="urnType" name="urnType" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                  <option value="">Select a Urn</option>
                  <?php
                  // Fetch inventory items where category_id = 1 and status = 1
                  // $sql = "SELECT inventory_id, item_name FROM inventory_tb WHERE category_id = 3 AND status = 1";
                  // $result = $conn->query($sql);

                  // if ($result->num_rows > 0) {
                  //     while ($row = $result->fetch_assoc()) {
                  //         echo '<option value="' . $row["inventory_id"] . '">' . htmlspecialchars($row["item_name"]) . '</option>';
                  //     }
                  // } else {
                  //     echo '<option value="">No urn available</option>';
                  // }
                  ?>
                </select>
              </div>
            </div>
          </div>

          <?php
          // Include database connection
          require_once '../db_connect.php';

          // Fetch branches from the database
          $sql = "SELECT branch_id, branch_name FROM branch_tb";
          $result = $conn->query($sql);
          ?>

          <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border-l-4 border-gold">
            <label class="block text-xs font-medium text-gray-700 mb-2">Branch</label>
            <div class="flex flex-wrap gap-3 sm:gap-4">
              <?php
              if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                      echo '<label class="flex items-center space-x-2 cursor-pointer">';
                      echo '<input type="radio" name="branch_id" value="' . $row['branch_id'] . '" required class="hidden peer">';
                      echo '<div class="w-5 h-5 rounded-full border-2 border-gold flex items-center justify-center peer-checked:bg-gold peer-checked:border-darkgold transition-colors"></div>';
                      echo '<span class="text-gray-700 font-medium">' . htmlspecialchars($row['branch_name']) . '</span>';
                      echo '</label>';
                  }
              } else {
                  echo '<p class="text-gray-500">No branches available.</p>';
              }
              ?>
            </div>
          </div>
          
          <div>
            <label for="serviceImage" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Upload Image
            </label>
            <div class="relative">
              <div class="flex items-center border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-1 focus-within:ring-sidebar-accent focus-within:border-sidebar-accent transition-all duration-200">
                <input type="file" id="serviceImage" name="serviceImage" class="w-full focus:outline-none">
              </div>
            </div>
          </div>
        </div>
        
        <!-- Right Column -->
        <div class="space-y-3 sm:space-y-4">
          <!-- Flower Design Section -->
          <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
            <p class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
              Flower Arrangement Sets
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2 sm:gap-3">
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="flowerDesign" value="3 Floral Replacement" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-leaf mr-1 text-sidebar-accent"></i>
                3 Floral Replacement
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="flowerDesign" value="2 Floral Replacement" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-leaf mr-1 text-sidebar-accent"></i>
                2 Floral Replacement
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="flowerDesign" value="1 Floral Replacement" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-leaf mr-1 text-sidebar-accent"></i>
                1 Floral Replacement
              </label>
            </div>
          </div>
          
          <!-- Enhanced Essential Services Section -->
          <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
            <p class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
              Other Essential Services
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="essentialServices" value="Transportation" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-car mr-2 text-sidebar-accent"></i>
                Transportation Service
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="essentialServices" value="Embalming" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-procedures mr-2 text-sidebar-accent"></i>
                Embalming and Preparation
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="essentialServices" value="MemorialPrograms" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-book-open mr-2 text-sidebar-accent"></i>
                Memorial Programs
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="essentialServices" value="Videography" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-video mr-2 text-sidebar-accent"></i>
                Videography & Photography
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="essentialServices" value="LiveStreaming" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-broadcast-tower mr-2 text-sidebar-accent"></i>
                Live Streaming Service
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="essentialServices" value="GriefCounseling" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-hands-helping mr-2 text-sidebar-accent"></i>
                Grief Counseling
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="essentialServices" value="Catering" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-utensils mr-2 text-sidebar-accent"></i>
                Catering Service
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="essentialServices" value="MusicService" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-music mr-2 text-sidebar-accent"></i>
                Music Service
              </label>
            </div>
          </div>
        </div>
        
        <input type="hidden" id="serviceStatus" name="serviceStatus" value="true">
      </form>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeAddServiceModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="addService()">
        Add Service
      </button>
    </div>
  </div>
</div>


<!-- Edit service modal -->
<div id="editServiceModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-5xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeEditServiceModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Edit Service
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <form id="editServiceForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <input type="hidden" id="editServiceId" name="serviceId">
        
        <!-- Left Column -->
        <div class="space-y-3 sm:space-y-4">
          <div>
            <label for="editServiceName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Service Name
            </label>
            <div class="relative">
              <input type="text" id="editServiceName" name="serviceName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
            </div>
          </div>

          <div>
            <label for="editServiceDescription" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Description
            </label>
            <div class="relative">
              <textarea id="editServiceDescription" name="serviceDescription" rows="3" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"></textarea>
            </div>
          </div>
          
          <!-- Price Section -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="editCapitalPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Capital Price
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">₱</span>
                </div>
                <input type="number" id="editCapitalPrice" name="capitalPrice" placeholder="0.00" min="0" step="0.01" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
              </div>
            </div>
            <div>
              <label for="editSellingPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Selling Price
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">₱</span>
                </div>
                <input type="number" id="editSellingPrice" name="sellingPrice" placeholder="0.00" min="0" step="0.01" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
              </div>
            </div>
          </div>
          
          <div>
            <label for="editServiceCategory" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Category
            </label>
            <div class="relative">
              <select id="editServiceCategory" name="serviceCategory" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                <option value="">Select a Category</option>
                <?php
                require_once '../db_connect.php';
                // Fetch service categories
                $sql = "SELECT service_categoryID, service_category_name FROM service_category";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo '<option value="' . $row["service_categoryID"] . '">' . htmlspecialchars($row["service_category_name"]) . '</option>';
                    }
                } else {
                    echo '<option value="">No categories found</option>';
                }
                ?>
              </select>
            </div>
          </div>
          
          <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
            <div class="w-full sm:flex-1">
              <label for="editCasketType" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Casket Type
              </label>
              <div class="relative">
                <select id="editCasketType" name="casketType" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                  <option value="">Select a Casket</option>
                  <?php
                  // Fetch inventory items where category_id = 1 and status = 1
                  $sql = "SELECT inventory_id, item_name FROM inventory_tb WHERE category_id = 1 AND status = 1";
                  $result = $conn->query($sql);

                  if ($result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                          echo '<option value="' . $row["inventory_id"] . '">' . htmlspecialchars($row["item_name"]) . '</option>';
                      }
                  } else {
                      echo '<option value="">No caskets available</option>';
                  }
                  ?>
                </select>
              </div>
            </div>
            <div class="w-full sm:flex-1">
              <label for="editUrnType" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Urn Type
              </label>
              <div class="relative">
                <select id="editUrnType" name="urnType" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                  <option value="">Select a Urn</option>
                  <?php
                  // Fetch inventory items where category_id = 3 and status = 1
                  $sql = "SELECT inventory_id, item_name FROM inventory_tb WHERE category_id = 3 AND status = 1";
                  $result = $conn->query($sql);

                  if ($result->num_rows > 0) {
                      while ($row = $result->fetch_assoc()) {
                          echo '<option value="' . $row["inventory_id"] . '">' . htmlspecialchars($row["item_name"]) . '</option>';
                      }
                  } else {
                      echo '<option value="">No urn available</option>';
                  }
                  ?>
                </select>
              </div>
            </div>
          </div>

          <?php
          // Include database connection
          require_once '../db_connect.php';

          // Fetch branches from the database
          $sql = "SELECT branch_id, branch_name FROM branch_tb";
          $result = $conn->query($sql);
          ?>

          <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border-l-4 border-gold">
            <label class="block text-xs font-medium text-gray-700 mb-2">Branch</label>
            <div class="flex flex-wrap gap-4">
              <?php
              if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                      echo '<label class="flex items-center space-x-2 cursor-pointer">';
                      echo '<input type="radio" name="branch_id" value="' . $row['branch_id'] . '" required class="hidden peer">';
                      echo '<div class="w-5 h-5 rounded-full border-2 border-gold flex items-center justify-center peer-checked:bg-gold peer-checked:border-darkgold transition-colors"></div>';
                      echo '<span class="text-gray-700 font-medium">' . htmlspecialchars($row['branch_name']) . '</span>';
                      echo '</label>';
                  }
              } else {
                  echo '<p class="text-gray-500">No branches available.</p>';
              }
              ?>
            </div>
          </div>
          
          <div>
            <label for="editServiceImage" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Update Image
            </label>
            <div class="relative">
              <div class="flex items-center border border-gray-300 rounded-lg px-3 py-2 focus-within:ring-1 focus-within:ring-sidebar-accent focus-within:border-sidebar-accent transition-all duration-200">
                <input type="file" id="editServiceImage" name="serviceImage" class="w-full focus:outline-none">
              </div>
            </div>
            <input type="hidden" id="currentImagePath" name="currentImagePath">
            <div id="currentImagePreview" class="mt-2 h-24 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center">
              <img id="currentServiceImage" src="" alt="Current service image" class="h-full object-cover">
              <p class="text-gray-500 text-sm p-2" id="noImageText">No image currently set</p>
            </div>
          </div>
        </div>
        
        <!-- Right Column -->
        <div class="space-y-3 sm:space-y-4">
          <!-- Flower Design Section -->
          <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
            <p class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
              Flower Arrangement Sets
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="flowerDesign" value="3 Floral Replacement" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-leaf mr-1 text-sidebar-accent"></i>
                3 Floral Replacement
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="flowerDesign" value="2 Floral Replacement" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-leaf mr-1 text-sidebar-accent"></i>
                2 Floral Replacement
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="flowerDesign" value="1 Floral Replacement" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-leaf mr-1 text-sidebar-accent"></i>
                1 Floral Replacement
              </label>
            </div>
          </div>
          
          <!-- Enhanced Essential Services Section -->
          <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
            <p class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
              Other Essential Services
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2" id="inclusionsContainer">
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="inclusions" value="Transportation" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-car mr-2 text-sidebar-accent"></i>
                Transportation Service
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="inclusions" value="Embalming" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-procedures mr-2 text-sidebar-accent"></i>
                Embalming and Preparation
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="inclusions" value="MemorialPrograms" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-book-open mr-2 text-sidebar-accent"></i>
                Memorial Programs
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="inclusions" value="Videography" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-video mr-2 text-sidebar-accent"></i>
                Videography & Photography
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="inclusions" value="LiveStreaming" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-broadcast-tower mr-2 text-sidebar-accent"></i>
                Live Streaming Service
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="inclusions" value="GriefCounseling" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-hands-helping mr-2 text-sidebar-accent"></i>
                Grief Counseling
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="inclusions" value="Catering" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-utensils mr-2 text-sidebar-accent"></i>
                Catering Service
              </label>
              <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                <input type="checkbox" name="inclusions" value="MusicService" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
                <i class="fas fa-music mr-2 text-sidebar-accent"></i>
                Music Service
              </label>
            </div>
          </div>
          
          <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
            <p class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
              Service Status
            </p>
            <div class="flex items-center space-x-4">
              <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="status" value="1" class="hidden peer">
                <div class="w-5 h-5 rounded-full border-2 border-green-500 flex items-center justify-center peer-checked:bg-green-500 transition-colors"></div>
                <span class="text-gray-700 font-medium">Active</span>
              </label>
              <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="status" value="0" class="hidden peer">
                <div class="w-5 h-5 rounded-full border-2 border-red-500 flex items-center justify-center peer-checked:bg-red-500 transition-colors"></div>
                <span class="text-gray-700 font-medium">Inactive</span>
              </label>
            </div>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeEditServiceModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="updateService()">
        Update Service
      </button>
    </div>
  </div>
</div>

<!-- JavaScript to handle fetching and displaying service data -->
<script>
// Function to open the edit service modal and load data
function openEditServiceModal(serviceId) {
    // Show the modal
    document.getElementById('editServiceModal').classList.remove('hidden');
    
    // Fetch service data
    fetch(`servicesManagement/get_service.php?service_id=${serviceId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                populateEditForm(data.service);
            } else {
                alert('Error loading service data');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading service data');
        });
}

// Function to populate the edit form with service data
// Function to populate the edit form with service data
function populateEditForm(service) {
    // Set basic fields
    document.getElementById('editServiceId').value = service.service_id;
    document.getElementById('editServiceName').value = service.service_name;
    document.getElementById('editCapitalPrice').value = service.capital_price;
    document.getElementById('editSellingPrice').value = service.selling_price;
    document.getElementById('editServiceCategory').value = service.service_categoryID;
    // Add this with the other field settings
    document.getElementById('editServiceDescription').value = service.description || '';
    
    // Set casket and urn if available
    if (service.casket_id) {
        document.getElementById('editCasketType').value = service.casket_id;
    }
    if (service.urn_id) {
        document.getElementById('editUrnType').value = service.urn_id;
    }
    
    // Set branch - Loop through all branch radio buttons and check the matching one
    const branchRadios = document.querySelectorAll('input[name="branch_id"]');
    branchRadios.forEach(radio => {
        if (radio.value == service.branch_id) {
            radio.checked = true;
        } else {
            radio.checked = false;
        }
    });
    
    // Set image if available
    if (service.image_url) {
        document.getElementById('currentImagePath').value = service.image_url;
        document.getElementById('currentServiceImage').src = service.image_url;
        document.getElementById('currentServiceImage').classList.remove('hidden');
        document.getElementById('noImageText').classList.add('hidden');
    } else {
        document.getElementById('currentServiceImage').classList.add('hidden');
        document.getElementById('noImageText').classList.remove('hidden');
    }
    
    // Set flower designs - Reset all checkboxes first
    const flowerCheckboxes = document.getElementsByName('flowerDesign');
    for (let checkbox of flowerCheckboxes) {
        checkbox.checked = false;
    }
    
    if (service.flower_design) {
        const flowerDesigns = service.flower_design.split(',');
        for (let checkbox of flowerCheckboxes) {
            if (flowerDesigns.includes(checkbox.value.trim())) {
                checkbox.checked = true;
            }
        }
    }
    
    // Set inclusions - Reset all checkboxes first then properly check matching ones
    const inclusionCheckboxes = document.getElementsByName('inclusions');
    for (let checkbox of inclusionCheckboxes) {
        checkbox.checked = false;
    }
    
    if (service.inclusions) {
        const inclusions = service.inclusions.split(',');
        for (let checkbox of inclusionCheckboxes) {
            // Trim values to handle any spaces in the data
            if (inclusions.some(inclusion => inclusion.trim() === checkbox.value.trim())) {
                checkbox.checked = true;
            }
        }
    }
    
    // Set status - Loop through all status radio buttons and check the matching one
    const statusRadios = document.getElementsByName('status');
    const statusValue = service.status.toString();
    
    // Handle status as either a numeric value or 'Active'/'Inactive' string
    let numericStatus;
    if (statusValue === 'Active') {
        numericStatus = '1';
    } else if (statusValue === 'Inactive') {
        numericStatus = '0';
    } else {
        numericStatus = statusValue;
    }
    
    statusRadios.forEach(radio => {
        radio.checked = (radio.value === numericStatus);
    });
    
    console.log('Branch ID:', service.branch_id);
    console.log('Status:', service.status, 'Numeric Status:', numericStatus);
}

// Function to close the edit service modal
function closeEditServiceModal() {
    document.getElementById('editServiceModal').classList.add('hidden');
    document.getElementById('editServiceForm').reset();
}

// Function to update service
function updateService() {
    const form = document.getElementById('editServiceForm');
    const formData = new FormData(form);

    // Log the FormData to the console
    console.log('FormData being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}:`, value);
    }

    // Convert status to "Active" or "Inactive"
    const status = formData.get('status') === '1' ? 'Active' : 'Inactive';
    formData.set('status', status);

    // Combine inclusions into a single string
    const inclusions = formData.getAll('inclusions').join(', ');
    formData.set('inclusions', inclusions);

    // Log the updated FormData
    console.log('Updated FormData being sent:');
    for (let [key, value] of formData.entries()) {
        console.log(`${key}:`, value);
    }

    fetch('servicesManagement/update_service.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            console.log('Service updated successfully:', data);
            alert('Service updated successfully');
            location.reload();
            closeEditServiceModal();
            // Optionally, refresh the service list or perform other actions
        } else {
            console.error('Error updating service:', data.message);
            alert('Error updating service: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating service');
    });
}
</script>

  <script>
    // Function to open the Add Service Modal
    function openAddServiceModal(branchId) {
  // Display the modal
  document.getElementById('addServiceModal').style.display = 'flex';
  
  // If a branchId was provided, select the corresponding radio button
  if (branchId) {
    // Find the radio button with the matching branch ID
    const radioButton = document.querySelector(`input[name="branch_id"][value="${branchId}"]`);
    
    // If found, select it
    if (radioButton) {
      radioButton.checked = true;
      // Load caskets and urns for this branch
      fetchItemsByBranch(branchId);
    }
  }
  
  // Add event listeners to all branch radio buttons
  const branchRadios = document.querySelectorAll('input[name="branch_id"]');
  branchRadios.forEach(radio => {
    radio.addEventListener('change', function() {
      if (this.checked) {
        fetchItemsByBranch(this.value);
      }
    });
  });
}

function fetchItemsByBranch(branchId) {
  // Create AJAX request to fetch items for this branch
  const xhr = new XMLHttpRequest();
  xhr.open('POST', 'fetch_branch_items.php', true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
  
  xhr.onload = function() {
    if (this.status === 200) {
      const response = JSON.parse(this.responseText);
      
      // Update casket dropdown
      const casketSelect = document.getElementById('casketType');
      casketSelect.innerHTML = '<option value="">Select a Casket</option>';
      
      if (response.caskets && response.caskets.length > 0) {
        response.caskets.forEach(casket => {
          const option = document.createElement('option');
          option.value = casket.inventory_id;
          option.textContent = casket.item_name;
          casketSelect.appendChild(option);
        });
      } else {
        const option = document.createElement('option');
        option.value = "";
        option.textContent = "No caskets available for this branch";
        casketSelect.appendChild(option);
      }
      
      // Update urn dropdown
      const urnSelect = document.getElementById('urnType');
      urnSelect.innerHTML = '<option value="">Select a Urn</option>';
      
      if (response.urns && response.urns.length > 0) {
        response.urns.forEach(urn => {
          const option = document.createElement('option');
          option.value = urn.inventory_id;
          option.textContent = urn.item_name;
          urnSelect.appendChild(option);
        });
      } else {
        const option = document.createElement('option');
        option.value = "";
        option.textContent = "No urns available for this branch";
        urnSelect.appendChild(option);
      }
    }
  };
  
  xhr.send('branch_id=' + branchId);
}

    // Function to close the Add Service Modal
    function closeAddServiceModal() {
      document.getElementById('addServiceModal').style.display = 'none';
    }

    // Function to add a new service
    function addService() {
    // Create a FormData object
    const form = document.getElementById('serviceForm');
    const formData = new FormData(form);
    
    // Get selected branch (assuming it's a radio button with name="branch_id")
    const selectedBranch = document.querySelector('input[name="branch_id"]:checked');
    if (selectedBranch) {
        formData.append('branch_id', selectedBranch.value);
    }

    // Get all checked flower designs
    document.querySelectorAll('input[name="flowerDesign"]:checked').forEach(checkbox => {
        formData.append('flowerDesign[]', checkbox.value);
    });
    
    // Get all checked essential services
    document.querySelectorAll('input[name="essentialServices"]:checked').forEach(checkbox => {
        formData.append('essentialServices[]', checkbox.value);
    });
    
    // Debug: Log form data
    console.log("Submitting form data:");
    for (let pair of formData.entries()) {
        console.log(pair[0] + ': ' + pair[1]);
    }
    
    // Send the form data
    fetch('servicesManagement/add_service_handler.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log("Response status:", response.status);
        return response.json();
    })
    .then(data => {
        console.log("Response data:", data);
        
        if (data.status === 'success') {
            // Show simple alert
            alert('Service added successfully');
            // Reload the page
            location.reload();
        } else {
            // Show error alert
            alert('Error: ' + (data.message || 'Something went wrong'));
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('Error: Failed to communicate with the server');
    });
    
    // Close the modal
    closeAddServiceModal();
}
    // Function to save changes to a service
    function saveServiceChanges() {
      const form = document.getElementById('editServiceForm');
      if (form.checkValidity()) {
        // Save changes logic here
        alert('Service updated successfully!');
        closeEditServiceModal();
      } else {
        form.reportValidity();
      }
    }

    // Function to delete a service
    function deleteService() {
      if (confirm('Are you sure you want to delete this service?')) {
        // Delete service logic here
        alert('Service deleted successfully!');
      }
    }
  </script>
  
  <script src="tailwind.js"></script>
  
</body>
</html>
