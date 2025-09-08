<?php
//service_management.php
session_start();

include 'faviconLogo.php'; 

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
  <title>GrievEase - Services</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
      #imagePreviewContainer, #currentImagePreview {
    height: 100px;
    border: 1px dashed #d1d5db;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 0.5rem;
    overflow: hidden;
}

#imagePreviewContainer img, #currentImagePreview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}


.modal-scroll-container {
    scrollbar-width: thin;
    scrollbar-color: #d4a933 #f5f5f5;
}

.modal-scroll-container::-webkit-scrollbar {
    width: 8px;
}

.modal-scroll-container::-webkit-scrollbar-track {
    background: #f5f5f5;
}

.modal-scroll-container::-webkit-scrollbar-thumb {
    background-color: #d4a933;
    border-radius: 6px;
}
    </style>
  
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
    return '₱' . number_format($price, 2);
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
        $branchName = ucwords(strtolower($branch['branch_name']));

        
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
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden branch-container" 
     data-branch-id="<?php echo $branchId; ?>" 
     data-total-services="<?php echo $totalServices; ?>">
    <!-- Branch Header with Search and Filters -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
        <!-- Desktop layout for big screens - Title on left, controls on right -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Title and Counter -->
            <div class="flex items-center gap-3 mb-4 lg:mb-0">
                <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Branch: <?php echo $branchName; ?></h4>
                
                <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
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
                <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover whitespace-nowrap" onclick="openArchiveModal(<?php echo $branchId; ?>)">
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
                    <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent" onclick="openArchiveModal(<?php echo $branchId; ?>)">
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
                                        <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip" 
                                                title="Archive Service" 
                                                onclick="archiveService('<?php echo htmlspecialchars($row['service_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($branchId, ENT_QUOTES); ?>')">
                                            <i class="fas fa-archive"></i>
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
    

    
<!-- Sticky Pagination Footer with improved spacing -->
<!-- Sticky Pagination Footer with improved spacing -->
<div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="paginationInfo_<?php echo $branchId; ?>" class="text-sm text-gray-500 text-center sm:text-left">
        <?php 
        // Get the number of services on the current page
        if ($totalServices > 0) {
            $start = min(($page - 1) * $recordsPerPage + 1, $totalServices);
            $end = min($page * $recordsPerPage, $totalServices);
        
            echo "Showing {$start} - {$end} of {$totalServices} services";
        } else {
            echo "No services found";
        }
        ?>
    </div>
    <div id="paginationContainer_<?php echo $branchId; ?>" class="flex space-x-2">
        <?php if ($totalPages > 1): ?>
            <!-- First page button (double arrow) -->
            <button onclick="changePage(<?php echo $branchId; ?>, 1)" 
                    class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == 1) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                    <?php echo ($page == 1) ? 'disabled' : ''; ?>>
                &laquo;
            </button>
            
            <!-- Previous page button (single arrow) -->
            <button onclick="changePage(<?php echo $branchId; ?>, <?php echo max(1, $page - 1); ?>)" 
                    class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == 1) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                    <?php echo ($page == 1) ? 'disabled' : ''; ?>>
                &lsaquo;
            </button>
            
            <?php
            // Show exactly 3 page numbers
            if ($totalPages <= 3) {
                // If total pages is 3 or less, show all pages
                $start_page = 1;
                $end_page = $totalPages;
            } else {
                // With more than 3 pages, determine which 3 to show
                if ($page == 1) {
                    // At the beginning, show first 3 pages
                    $start_page = 1;
                    $end_page = 3;
                } elseif ($page == $totalPages) {
                    // At the end, show last 3 pages
                    $start_page = $totalPages - 2;
                    $end_page = $totalPages;
                } else {
                    // In the middle, show current page with one before and after
                    $start_page = $page - 1;
                    $end_page = $page + 1;
                    
                    // Handle edge cases
                    if ($start_page < 1) {
                        $start_page = 1;
                        $end_page = 3;
                    }
                    if ($end_page > $totalPages) {
                        $end_page = $totalPages;
                        $start_page = $totalPages - 2;
                    }
                }
            }
            
            // Generate the page buttons
            for ($i = $start_page; $i <= $end_page; $i++) {
                $active_class = ($i == $page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
                echo '<button onclick="changePage('.$branchId.', '.$i.')" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</button>';
            }
            ?>
            
            <!-- Next page button (single arrow) -->
            <button onclick="changePage(<?php echo $branchId; ?>, <?php echo min($totalPages, $page + 1); ?>)" 
                    class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == $totalPages) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                    <?php echo ($page == $totalPages) ? 'disabled' : ''; ?>>
                &rsaquo;
            </button>
            
            <!-- Last page button (double arrow) -->
            <button onclick="changePage(<?php echo $branchId; ?>, <?php echo $totalPages; ?>)" 
                    class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page == $totalPages) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                    <?php echo ($page == $totalPages) ? 'disabled' : ''; ?>>
                &raquo;
            </button>
        <?php endif; ?>
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

<!-- Add-Ons Management Card -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden branch-container" 
     data-branch-id="addons" 
     data-total-services="5">
    <!-- Branch Header with Search and Filters -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
        <!-- Desktop layout for big screens - Title on left, controls on right -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Title and Counter -->
            <div class="flex items-center gap-3 mb-4 lg:mb-0">
                <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Add-Ons Management</h4>
                
                <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1" id="addonsTotalCount">
                    5
                </span>
            </div>
            
            <!-- Controls for big screens - aligned right -->
            <div class="hidden lg:flex items-center gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <input type="text" id="searchInputAddons" 
                           placeholder="Search add-ons..." 
                           value=""
                           class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                           oninput="debouncedFilterAddons()">
                    <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
                </div>

                <!-- Filter Dropdown -->
                <div class="relative filter-dropdown">
                    <button id="filterToggleAddons" class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover"
                            onclick="toggleFilterWindowAddons()">
                        <i class="fas fa-filter text-sidebar-accent"></i>
                        <span>Filters</span>
                    </button>
                    
                    <!-- Filter Window -->
                    <div id="filterWindowAddons" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
                        <div class="space-y-4">
                            <!-- Status Filter -->
                            <div>
                                <h5 class="text-sm font-medium text-sidebar-text mb-2">Status</h5>
                                <div class="space-y-1">
                                    <div class="flex items-center cursor-pointer" onclick="setFilterAddons('status', '')">
                                        <span class="filter-option bg-sidebar-accent text-white px-2 py-1 rounded text-sm w-full">
                                            All Statuses
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer" onclick="setFilterAddons('status', 'Active')">
                                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Active
                                        </span>
                                    </div>
                                    <div class="flex items-center cursor-pointer" onclick="setFilterAddons('status', 'Inactive')">
                                        <span class="filter-option hover:bg-sidebar-hover px-2 py-1 rounded text-sm w-full">
                                            Inactive
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Archive Button -->
                <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover whitespace-nowrap" onclick="openAddonsArchived()">
                    <i class="fas fa-archive text-sidebar-accent"></i>
                    <span>Archive</span>
                </button>

                <!-- Add Service Button -->
                <button class="px-4 py-2 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap" 
                        onclick="openAddAddonsModal()"><span>+ Add-Ons</span>
                </button>
            </div>
        </div>
        
        <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
        <div class="lg:hidden w-full mt-4">
            <!-- First row: Search bar with filter and archive icons on the right -->
            <div class="flex items-center w-full gap-3 mb-4">
                <!-- Search Input - Takes most of the space -->
                <div class="relative flex-grow">
                    <input type="text" id="searchInputMobileAddons" 
                            placeholder="Search add-ons..." 
                            value=""
                            class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                            oninput="debouncedFilterAddons()">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>

                <!-- Icon-only buttons for filter and archive -->
                <div class="flex items-center gap-3">
                    <!-- Filter Icon Button -->
                    <div class="relative filter-dropdown">
                        <button id="serviceFilterToggleAddons" class="w-10 h-10 flex items-center justify-center text-sidebar-accent"
                                onclick="toggleFilterWindowAddons()">
                            <i class="fas fa-filter text-xl"></i>
                            <span id="filterIndicatorAddons" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
                        </button>
                    </div>

                    <!-- Archive Icon Button -->
                    <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent" onclick="openAddonsArchived()">
                        <i class="fas fa-archive text-xl"></i>
                    </button>
                </div>
            </div>

            <!-- Second row: Add Service Button - Full width -->
            <div class="w-full">
                <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap w-full justify-center" 
                        onclick="openAddAddonsModal()"><span>+ Add-Ons</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Responsive Table Container with improved spacing -->
    <div class="overflow-x-auto scrollbar-thin" id="tableContainerAddons">
        <div id="loadingIndicatorAddons" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
        </div>
        
        <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
        <div class="min-w-full">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-sidebar-border">
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text">ID</th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text">Name</th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text">Description</th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text">Icon</th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text">Price</th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text">Status</th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text">Actions</th>
                    </tr>
                </thead>
                <tbody id="addonTableBody">
                    <!-- Rows will be populated by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Sticky Pagination Footer -->
    <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
        <div id="paginationInfo_addons" class="text-sm text-gray-500 text-center sm:text-left">
            Loading add-ons...
        </div>
        <div id="paginationContainer_addons" class="flex space-x-2">
            <!-- Pagination buttons will be populated by JavaScript -->
        </div>
    </div>
</div>
    

<script>
// Global variable to track active filters
const activeFilters = {};


// Function to archive a service
// Function to archive a service
function archiveService(serviceId, branchId) {
  Swal.fire({
    title: 'Archive Service',
    text: 'Are you sure you want to archive this service?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, archive it!'
  }).then((result) => {
    if (result.isConfirmed) {
      // Send AJAX request to update service status to 'Inactive'
      $.ajax({
        url: 'servicesManagement/archive_service.php',
        type: 'POST',
        data: {
          service_id: serviceId,
          branch_id: branchId
        },
        success: function(response) {
          try {
            var data = JSON.parse(response);
            if (data.status === 'success') {
              Swal.fire(
                'Archived!',
                'Service has been archived successfully.',
                'success'
              ).then(() => {
                // Reload the page to reflect changes
                location.reload();
              });
            } else {
              Swal.fire(
                'Error!',
                'Failed to archive service: ' + data.message,
                'error'
              );
            }
          } catch (e) {
            console.error("Error parsing JSON response:", response);
            Swal.fire(
              'Error!',
              'Invalid response from server.',
              'error'
            );
          }
        },
        error: function(xhr, status, error) {
          console.error("AJAX Error:", error);
          Swal.fire(
            'Error!',
            'There was an error connecting to the server.',
            'error'
          );
        }
      });
    }
  });
}

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
        activeFilters[branchId] = { 
            search: '', 
            category: '', 
            status: '', 
            page: 1 
        };
    }
    
    // Validate page number
    const container = document.querySelector(`.branch-container[data-branch-id="${branchId}"]`);
    if (!container) return;
    
    const totalServices = parseInt(container.dataset.totalServices);
    const recordsPerPage = 5;
    const totalPages = Math.ceil(totalServices / recordsPerPage);
    
    if (page < 1) page = 1;
    if (page > totalPages) page = totalPages;
    
    activeFilters[branchId].page = page;
    loadBranchTable(branchId);
    
    // Update URL with the current page
    updateUrlWithFilters(branchId);
    
    // Scroll to the top of the table
    const tableContainer = document.getElementById(`tableContainer${branchId}`);
    if (tableContainer) {
        tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Function to update active page highlight
function updateActivePageHighlight(branchId, page) {
    // Remove active class from all pagination links
    const container = document.querySelector(`.branch-container[data-branch-id="${branchId}"]`);
    if (container) {
        const paginationLinks = container.querySelectorAll('.pagination a');
        paginationLinks.forEach(link => {
            link.classList.remove('bg-sidebar-accent', 'text-white');
            link.classList.add('hover:bg-sidebar-hover');
        });
        
        // Add active class to current page link
        const currentPageLink = container.querySelector(`.pagination a[onclick*="changePage(${branchId}, ${page})"]`);
        if (currentPageLink) {
            currentPageLink.classList.add('bg-sidebar-accent', 'text-white');
            currentPageLink.classList.remove('hover:bg-sidebar-hover');
        }
    }
}

// Load branch table via AJAX
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
    
    if (activeFilters[branchId]?.search) {
        params.append('search', activeFilters[branchId].search);
    }
    if (activeFilters[branchId]?.category) {
        params.append('category', activeFilters[branchId].category);
    }
    if (activeFilters[branchId]?.status) {
        params.append('status', activeFilters[branchId].status);
    }

    // Make AJAX request
    fetch(`loadServices/load_service_table.php?${params.toString()}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.text();
        })
        .then(html => {
            // Update the table content
            container.innerHTML = html;
            if (loadingIndicator) loadingIndicator.classList.add('hidden');
            
            // Update pagination info and controls
            updatePaginationInfo(branchId);
            updatePaginationControls(branchId);
        })
        .catch(error => {
            console.error('Error loading table:', error);
            if (loadingIndicator) loadingIndicator.classList.add('hidden');
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load service data. Please try again.'
            });
        });
}

// Update pagination info
function updatePaginationInfo(branchId) {
    if (!activeFilters[branchId]) return;
    
    const container = document.querySelector(`.branch-container[data-branch-id="${branchId}"]`);
    const totalServices = parseInt(container.dataset.totalServices);
    const recordsPerPage = 5;
    const currentPage = activeFilters[branchId].page;
    
    const startItem = (currentPage - 1) * recordsPerPage + 1;
    const endItem = Math.min(currentPage * recordsPerPage, totalServices);
    
    const paginationInfo = document.getElementById(`paginationInfo_${branchId}`);
    if (paginationInfo) {
        paginationInfo.textContent = `Showing ${startItem} - ${endItem} of ${totalServices} services`;
    }
}

// Update pagination controls
function updatePaginationControls(branchId) {
    if (!activeFilters[branchId]) return;
    
    const container = document.querySelector(`.branch-container[data-branch-id="${branchId}"]`);
    const totalServices = parseInt(container.dataset.totalServices);
    const recordsPerPage = 5;
    const totalPages = Math.ceil(totalServices / recordsPerPage);
    const currentPage = activeFilters[branchId].page;
    
    const paginationContainer = document.getElementById(`paginationContainer_${branchId}`);
    if (!paginationContainer) return;
    
    // Clear existing pagination controls
    paginationContainer.innerHTML = '';
    
    // Only show pagination if there are multiple pages
    if (totalPages > 1) {
        // First page button
        const firstButton = document.createElement('button');
        firstButton.innerHTML = '&laquo;';
        firstButton.className = `px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}`;
        firstButton.disabled = currentPage === 1;
        firstButton.onclick = () => changePage(branchId, 1);
        paginationContainer.appendChild(firstButton);
        
        // Previous page button
        const prevButton = document.createElement('button');
        prevButton.innerHTML = '&lsaquo;';
        prevButton.className = `px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}`;
        prevButton.disabled = currentPage === 1;
        prevButton.onclick = () => changePage(branchId, Math.max(1, currentPage - 1));
        paginationContainer.appendChild(prevButton);
        
        // Page number buttons
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);
        
        // Adjust if we're near the start or end
        if (currentPage <= 3) {
            endPage = Math.min(5, totalPages);
        } else if (currentPage >= totalPages - 2) {
            startPage = Math.max(totalPages - 4, 1);
        }
        
        for (let i = startPage; i <= endPage; i++) {
            const pageButton = document.createElement('button');
            pageButton.textContent = i;
            pageButton.className = `px-3.5 py-1.5 rounded text-sm ${i === currentPage ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover'}`;
            pageButton.onclick = () => changePage(branchId, i);
            paginationContainer.appendChild(pageButton);
        }
        
        // Next page button
        const nextButton = document.createElement('button');
        nextButton.innerHTML = '&rsaquo;';
        nextButton.className = `px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''}`;
        nextButton.disabled = currentPage === totalPages;
        nextButton.onclick = () => changePage(branchId, Math.min(totalPages, currentPage + 1));
        paginationContainer.appendChild(nextButton);
        
        // Last page button
        const lastButton = document.createElement('button');
        lastButton.innerHTML = '&raquo;';
        lastButton.className = `px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''}`;
        lastButton.disabled = currentPage === totalPages;
        lastButton.onclick = () => changePage(branchId, totalPages);
        paginationContainer.appendChild(lastButton);
    }
}

// Update URL with current filters
function updateUrlWithFilters(branchId) {
    if (!activeFilters[branchId]) return;
    
    const url = new URL(window.location);
    url.searchParams.set(`page_${branchId}`, activeFilters[branchId].page);
    
    if (activeFilters[branchId].search) {
        url.searchParams.set(`search_${branchId}`, activeFilters[branchId].search);
    } else {
        url.searchParams.delete(`search_${branchId}`);
    }
    
    if (activeFilters[branchId].category) {
        url.searchParams.set(`category_${branchId}`, activeFilters[branchId].category);
    } else {
        url.searchParams.delete(`category_${branchId}`);
    }
    
    if (activeFilters[branchId].status) {
        url.searchParams.set(`status_${branchId}`, activeFilters[branchId].status);
    } else {
        url.searchParams.delete(`status_${branchId}`);
    }
    
    window.history.pushState({ branchId, filters: activeFilters[branchId] }, '', url);
}


// Initialize active filters when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize active filters from current values for each branch
    document.querySelectorAll('.branch-container').forEach(container => {
        const branchId = container.dataset.branchId;
        const urlParams = new URLSearchParams(window.location.search);
        
        activeFilters[branchId] = {
            search: urlParams.get(`search_${branchId}`) || '',
            category: urlParams.get(`category_${branchId}`) || '',
            status: urlParams.get(`status_${branchId}`) || '',
            page: parseInt(urlParams.get(`page_${branchId}`)) || 1
        };
        
        // Set total services count from the server-side value
        const totalServicesElement = container.querySelector('.total-services-count');
        if (totalServicesElement) {
            container.dataset.totalServices = totalServicesElement.textContent;
        }
        
        // Initialize pagination controls
        updatePaginationControls(branchId);
    });
});

// Handle browser back/forward navigation
window.addEventListener('popstate', function(event) {
    if (event.state) {
        const { branchId, filters } = event.state;
        if (branchId && filters) {
            activeFilters[branchId] = filters;
            loadBranchTable(branchId);
        }
    }
});
</script>

<!-- ADD SERVICE MODAL -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto" id="addServiceModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-5xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
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
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
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
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-5xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
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
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
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
                <input type="file" id="editServiceImage" name="serviceImage" class="w-full focus:outline-none" accept="image/*">
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

<!-- Archived Services Modal -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="archiveModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" id="closeArchiveModal">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        <span>Archived Services</span>
      </h3>
    </div>
    
    <!-- Search Bar -->
    <div class="px-6 py-4 border-b border-gray-200">
      <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <i class="fas fa-search text-gray-400"></i>
        </div>
        <input type="text" id="archivedServicesSearch" placeholder="Search archived services..." 
          class="w-full pl-10 pr-4 py-3 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
      </div>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5 max-h-[70vh] overflow-y-auto w-full">
      <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
        <table class="w-full text-sm text-left text-gray-500">
          <thead class="text-xs text-gray-700 uppercase bg-gray-50">
            <tr>
              <th scope="col" class="px-6 py-3">
                <div class="flex items-center">ID
                </div>
              </th>
              <th scope="col" class="px-6 py-3">
                <div class="flex items-center">Service Name
                </div>
              </th>
              <th scope="col" class="px-6 py-3">
                <div class="flex items-center">Actions
                </div>
              </th>
            </tr>
          </thead>
          <tbody id="archivedServicesTable">
            <!-- Table content will be loaded dynamically -->
          </tbody>
        </table>
      </div>
      <div id="noArchivedServices" class="hidden text-center py-4 w-full">
        <div class="flex flex-col items-center justify-center py-6">
          <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
          <p>No archived services found</p>
        </div>
      </div>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-6 py-4 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center" id="closeArchiveModalBtn">
        Close
      </button>
    </div>
  </div>
</div>

<!-- Add Add-ons Modal -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto" id="addAddonsModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeAddAddonsModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Add New Add-on
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="addAddonsForm" class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
        <!-- Left Column -->
        <div class="space-y-3 sm:space-y-4">
          <div>
            <label for="addonName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Add-on Name
            </label>
            <div class="relative">
              <input type="text" id="addonName" name="addonName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Enter add-on name..." required>
            </div>
          </div>

          <div>
            <label for="addonDescription" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Description
            </label>
            <div class="relative">
              <textarea id="addonDescription" name="addonDescription" rows="3" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Enter add-on description..."></textarea>
            </div>
          </div>
          
          <!-- Price Section -->
          <div>
            <label for="addonPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Price
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500">₱</span>
              </div>
              <input type="number" id="addonPrice" name="addonPrice" placeholder="0.00" min="0" step="0.01" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
            </div>
          </div>
          
          <div>
            <label for="addonBranch" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Branch
            </label>
            <div class="relative">
              <select id="addonBranch" name="addonBranch" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                <option value="">Select Branch</option>
              </select>
            </div>
          </div>
        </div>
        
        <!-- Right Column -->
        <div class="space-y-3 sm:space-y-4">
          <!-- Icon Selection Section -->
          <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
            <p class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
              Icon Selection
            </p>
            
            <!-- Icon Preview and Search -->
            <div class="flex mb-4">
              <div class="flex items-center justify-center px-4 py-3 rounded-l-lg bg-sidebar-accent border border-sidebar-accent">
                <i id="selectedIconPreview" class="fas fa-plus text-white text-lg"></i>
              </div>
              <input type="text" id="iconSearch" 
                class="flex-1 px-4 py-3 rounded-r-lg border border-l-0 border-gray-300 bg-white focus:outline-none focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent transition-all duration-200" 
                placeholder="Search icons...">
              <input type="hidden" id="selectedIcon" value="fa-plus">
            </div>
            
            <!-- Icon Grid -->
            <div class="mb-2">
              <p class="text-xs text-gray-500 mb-3">Choose an icon for your add-on:</p>
            </div>
            <div id="iconGrid" class="grid grid-cols-8 gap-3 max-h-48 overflow-y-auto p-2 bg-white rounded-lg border border-gray-200">
              
            </div>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeAddAddonsModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" id="saveAddonBtn">
        Add Add-on
      </button>
    </div>
  </div>
</div>

<!-- Edit Add-on Modal -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto" id="editAddonsModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeEditModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Edit Add-on
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="editAddonsForm" class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
        <input type="hidden" id="editAddonId">
        
        <!-- Left Column -->
        <div class="space-y-3 sm:space-y-4">
          <div>
            <label for="editAddonName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Add-on Name
            </label>
            <div class="relative">
              <input type="text" id="editAddonName" name="editAddonName" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Enter add-on name..." required>
            </div>
          </div>

          <div>
            <label for="editAddonDescription" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Description
            </label>
            <div class="relative">
              <textarea id="editAddonDescription" name="editAddonDescription" rows="3" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Enter add-on description..."></textarea>
            </div>
          </div>
          
          <!-- Price Section -->
          <div>
            <label for="editAddonPrice" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Price
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500">₱</span>
              </div>
              <input type="number" id="editAddonPrice" name="editAddonPrice" placeholder="0.00" min="0" step="0.01" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
            </div>
          </div>
          
          <div>
            <label for="editAddonBranch" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Branch
            </label>
            <div class="relative">
              <select id="editAddonBranch" name="editAddonBranch" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                <option value="">Select Branch</option>
              </select>
            </div>
          </div>
          
          <!-- Status Toggle -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Status
            </label>
            <div class="relative flex items-center">
              <label class="inline-flex items-center cursor-pointer">
                <input type="checkbox" id="editAddonStatus" class="sr-only peer" checked>
                <div class="relative w-14 h-7 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-sidebar-accent/30 rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-sidebar-accent"></div>
                <span class="ml-3 text-sm font-medium text-gray-600" id="editAddonStatusLabel">Active</span>
              </label>
            </div>
          </div>
        </div>
        
        <!-- Right Column -->
        <div class="space-y-3 sm:space-y-4">
          <!-- Icon Selection Section -->
          <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
            <p class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
              Icon Selection
            </p>
            
            <!-- Icon Preview and Search -->
            <div class="flex mb-4">
              <div class="flex items-center justify-center px-4 py-3 rounded-l-lg bg-sidebar-accent border border-sidebar-accent">
                <i id="editSelectedIconPreview" class="fas fa-edit text-white text-lg"></i>
              </div>
              <input type="text" id="editIconSearch" 
                class="flex-1 px-4 py-3 rounded-r-lg border border-l-0 border-gray-300 bg-white focus:outline-none focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent transition-all duration-200" 
                placeholder="Search icons...">
              <input type="hidden" id="editSelectedIcon" value="fa-edit">
            </div>
            
            <!-- Icon Grid -->
            <div class="mb-2">
              <p class="text-xs text-gray-500 mb-3">Choose an icon for your add-on:</p>
            </div>
            <div id="editIconGrid" class="grid grid-cols-8 gap-3 max-h-48 overflow-y-auto p-2 bg-white rounded-lg border border-gray-200">
              
            </div>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeEditModal()">
        Cancel
      </button>
      <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" id="updateAddonBtn">
        Update Add-on
      </button>
    </div>
  </div>
</div>

    <div id="archivedModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-11/12 max-w-6xl max-h-[90vh] overflow-hidden slide-down">
            <!-- Modal Header -->
            <div class="flex justify-between items-center bg-blue-500 text-white p-6">
                <h2 class="text-2xl font-bold">Archived Add-Ons</h2>
                <button onclick="closeModal()" class="text-white hover:text-gray-200 text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Search and Filter Section -->
            <div class="p-4 bg-gray-50 border-b flex flex-wrap gap-4">
                <div class="relative flex-1 min-w-[200px]">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" id="searchInput" placeholder="Search archived add-ons..." 
                    oninput="handleSearch()"
                    class="pl-10 pr-4 py-2 w-full border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div class="flex gap-2">
                    <select id="branchFilter" class="border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" onchange="handleSearch()">
                        <option value="">All Branches</option>
                        <!-- Options will be populated dynamically -->
                    </select>
                    <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition-colors" onclick="handleSearch()">
                        <i class="fas fa-filter mr-2"></i> Filter
                    </button>
                </div>
            </div>
            
            <!-- Modal Body with Table -->
            <div class="p-4 overflow-auto table-container max-h-[50vh]">
                <table class="w-full text-left rounded-lg overflow-hidden">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="p-4 font-semibold">ID</th>
                            <th class="p-4 font-semibold">Service Name</th>
                            <th class="p-4 font-semibold">Price</th>
                            <th class="p-4 font-semibold">Branch</th>
                            <th class="p-4 font-semibold">Status</th>
                            <th class="p-4 font-semibold text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="archivedTableBody">
                        <!-- Table rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Modal Footer -->
            <div class="flex justify-between items-center p-4 border-t bg-gray-50">
                <div class="text-sm text-gray-600">
                    Showing <span id="itemCount">5</span> out of 8 archived items
                </div>
                <div class="flex space-x-2">
                    <button class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition-colors" onclick="closeModal()">
                        Close
                    </button>
                    <button class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg transition-colors">
                        <i class="fas fa-download mr-2"></i> Export
                    </button>
                </div>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const archiveModal = document.getElementById('archiveModal');
    const closeArchiveModal = document.getElementById('closeArchiveModal');
    const closeArchiveModalBtn = document.getElementById('closeArchiveModalBtn');
    const archivedServicesTable = document.getElementById('archivedServicesTable');
    const noArchivedServices = document.getElementById('noArchivedServices');
    let currentBranchId = null;

    // Function to open archive modal
    window.openArchiveModal = function(branchId) {
        currentBranchId = branchId;
        archiveModal.classList.remove('hidden');
        fetchArchivedServices(branchId);
    };

    // Function to close archive modal
    function closeModal() {
        archiveModal.classList.add('hidden');
        currentBranchId = null;
        archivedServicesTable.innerHTML = ''; // Clear table content
        noArchivedServices.classList.add('hidden'); // Hide no services message
    }

    // Close modal when close button (X) is clicked
    if (closeArchiveModal) {
        closeArchiveModal.addEventListener('click', closeModal);
    }

    // Close modal when close button (footer) is clicked
    if (closeArchiveModalBtn) {
        closeArchiveModalBtn.addEventListener('click', closeModal);
    }

    // Close modal when clicking outside the modal content
    if (archiveModal) {
        archiveModal.addEventListener('click', function(e) {
            if (e.target === archiveModal) {
                closeModal();
            }
        });
    }

    // Function to fetch archived services for a specific branch
    function fetchArchivedServices(branchId) {
        // Show loading state
        archivedServicesTable.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center"><div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500 mx-auto"></div></td></tr>';
        
        fetch(`servicesManagement/fetch_archived_services.php?branch_id=${branchId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Server responded with ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                archivedServicesTable.innerHTML = '';
                
                if (data.length === 0) {
                    noArchivedServices.classList.remove('hidden');
                } else {
                    noArchivedServices.classList.add('hidden');
                    
                    data.forEach(service => {
                        const row = document.createElement('tr');
                        row.className = 'hover:bg-gray-50';
                        
                        const formattedId = `#SVC-${String(service.service_id).padStart(3, '0')}`;
                        
                        row.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                ${formattedId}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                ${service.service_name}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <button 
                                    class="unarchive-btn px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 flex items-center gap-2"
                                    data-service-id="${service.service_id}"
                                    data-service-name="${service.service_name}"
                                    data-branch-id="${branchId}">
                                    <i class="fas fa-archive"></i>
                                    <span>Unarchive</span>
                                </button>
                            </td>
                        `;
                        archivedServicesTable.appendChild(row);
                    });

                    // Add event listeners to unarchive buttons
                    document.querySelectorAll('.unarchive-btn').forEach(btn => {
                        btn.removeEventListener('click', handleUnarchiveClick); // Remove previous listeners to prevent duplicates
                        btn.addEventListener('click', handleUnarchiveClick);
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching archived services:', error);
                archivedServicesTable.innerHTML = '<tr><td colspan="3" class="px-6 py-4 text-center text-red-500">Error loading archived services</td></tr>';
            });
    }

    // Handle unarchive button click
    function handleUnarchiveClick() {
        const serviceId = this.getAttribute('data-service-id');
        const serviceName = this.getAttribute('data-service-name');
        const branchId = this.getAttribute('data-branch-id');
        showUnarchiveConfirmation(serviceId, serviceName, branchId);
    }

    // Function to show confirmation dialog using SweetAlert
    function showUnarchiveConfirmation(serviceId, serviceName, branchId) {
        Swal.fire({
            title: 'Unarchive Service',
            html: `Are you sure you want to unarchive <strong>${serviceName}</strong>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10B981',
            cancelButtonColor: '#6B7280',
            confirmButtonText: 'Yes, unarchive it!',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusConfirm: false
        }).then((result) => {
            if (result.isConfirmed) {
                unarchiveService(serviceId, branchId);
            }
        });
    }

    // Function to unarchive a service
    function unarchiveService(serviceId, branchId) {
        Swal.fire({
            title: 'Processing...',
            text: 'Please wait while we unarchive the service.',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            willOpen: () => {
                Swal.showLoading();
            }
        });

        const formData = new FormData();
        formData.append('service_id', serviceId);
        formData.append('branch_id', branchId);
        
        fetch('servicesManagement/unarchive_service.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Success!',
                    text: 'Service has been unarchived successfully.',
                    icon: 'success',
                    confirmButtonColor: '#10B981'
                }).then(() => {
                    closeModal(); // Close modal after unarchiving
                    loadBranchTable(branchId); // Refresh the branch table
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to unarchive service: ' + data.message,
                    icon: 'error',
                    confirmButtonColor: '#EF4444'
                });
            }
        })
        .catch(error => {
            console.error('Error unarchiving service:', error);
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred while unarchiving the service',
                icon: 'error',
                confirmButtonColor: '#EF4444'
            });
        });
    }
});
</script>


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
        document.getElementById('currentImagePath').value = "servicesManagement/" + service.image_url;
        document.getElementById('currentServiceImage').src = "servicesManagement/" + service.image_url;
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

</script>

<script>
  // Add Service Modal Validation Logic
document.getElementById('serviceName').addEventListener('input', function(e) {
    // Auto-capitalize first character
    if (this.value.length === 1) {
        this.value = this.value.toUpperCase();
    }
    
    // Prevent multiple consecutive spaces
    this.value = this.value.replace(/\s{2,}/g, ' ');
    
    // Don't allow space as first character
    if (this.value.startsWith(' ')) {
        this.value = this.value.trim();
    }
    
    // Require at least 2 characters before allowing space
    if (this.value.length < 2 && this.value.includes(' ')) {
        this.value = this.value.replace(/\s/g, '');
    }
});

// Prevent numbers-only service names
document.getElementById('serviceName').addEventListener('blur', function() {
    if (/^\d+$/.test(this.value.trim())) {
        alert('Service name cannot be numbers only');
        this.value = '';
        this.focus();
    }
});

// Description auto-capitalize first character
// Update the description validation to match service name
document.getElementById('serviceDescription').addEventListener('input', function(e) {
    // Auto-capitalize first character
    if (this.value.length === 1) {
        this.value = this.value.toUpperCase();
    }
    
    // Prevent multiple consecutive spaces
    this.value = this.value.replace(/\s{2,}/g, ' ');
    
    // Don't allow space as first character
    if (this.value.startsWith(' ')) {
        this.value = this.value.trim();
    }
    
    // Require at least 2 characters before allowing space
    if (this.value.length < 2 && this.value.includes(' ')) {
        this.value = this.value.replace(/\s/g, '');
    }
});

// Prevent numbers-only descriptions
document.getElementById('serviceDescription').addEventListener('blur', function() {
    if (/^\d+$/.test(this.value.trim())) {
        alert('Description cannot be numbers only');
        this.value = '';
        this.focus();
    }
});

// Image preview functionality
document.getElementById('serviceImage').addEventListener('change', function(e) {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            // Create preview container if it doesn't exist
            let previewContainer = document.getElementById('imagePreviewContainer');
            if (!previewContainer) {
                previewContainer = document.createElement('div');
                previewContainer.id = 'imagePreviewContainer';
                previewContainer.className = 'mt-2 h-24 rounded-lg overflow-hidden bg-gray-100 flex items-center justify-center';
                previewContainer.innerHTML = '<img id="imagePreview" src="" alt="Image preview" class="h-full object-cover">';
                document.getElementById('serviceImage').parentNode.parentNode.appendChild(previewContainer);
            }
            
            const preview = document.getElementById('imagePreview');
            preview.src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

// Edit Service Modal Validation Logic (same as add service)
document.getElementById('editServiceName').addEventListener('input', function(e) {
    // Auto-capitalize first character
    if (this.value.length === 1) {
        this.value = this.value.toUpperCase();
    }
    
    // Prevent multiple consecutive spaces
    this.value = this.value.replace(/\s{2,}/g, ' ');
    
    // Don't allow space as first character
    if (this.value.startsWith(' ')) {
        this.value = this.value.trim();
    }
    
    // Require at least 2 characters before allowing space
    if (this.value.length < 2 && this.value.includes(' ')) {
        this.value = this.value.replace(/\s/g, '');
    }
});

// Prevent numbers-only service names
document.getElementById('editServiceName').addEventListener('blur', function() {
    if (/^\d+$/.test(this.value.trim())) {
        alert('Service name cannot be numbers only');
        this.focus();
    }
});

// Update the description validation to match service name
document.getElementById('editServiceDescription').addEventListener('input', function(e) {
    // Auto-capitalize first character
    if (this.value.length === 1) {
        this.value = this.value.toUpperCase();
    }
    
    // Prevent multiple consecutive spaces
    this.value = this.value.replace(/\s{2,}/g, ' ');
    
    // Don't allow space as first character
    if (this.value.startsWith(' ')) {
        this.value = this.value.trim();
    }
    
    // Require at least 2 characters before allowing space
    if (this.value.length < 2 && this.value.includes(' ')) {
        this.value = this.value.replace(/\s/g, '');
    }
});

// Prevent numbers-only descriptions
document.getElementById('editServiceDescription').addEventListener('blur', function() {
    if (/^\d+$/.test(this.value.trim())) {
        alert('Description cannot be numbers only');
        this.focus();
    }
});

// Image preview functionality for edit modal
document.getElementById('editServiceImage').addEventListener('change', function(e) {
    const file = this.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('currentServiceImage');
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            document.getElementById('noImageText').classList.add('hidden');
        }
        reader.readAsDataURL(file);
    }
});

// Global variable for add-ons filters
const addonsFilters = {
    search: '',
    status: '',
    page: 1
};

// Debounced filter for add-ons search input
const debouncedFilterAddons = debounce(function() {
    addonsFilters.search = document.getElementById('searchInputAddons').value || 
                           document.getElementById('searchInputMobileAddons').value;
    addonsFilters.page = 1;
    fetchAndPopulateAddOns();
}, 300);

// Toggle filter window for add-ons
function toggleFilterWindowAddons() {
    const filterWindow = document.getElementById('filterWindowAddons');
    filterWindow.classList.toggle('hidden');
}

// Set filter for add-ons
function setFilterAddons(type, value) {
    if (type === 'status') {
        addonsFilters.status = value;
    }
    
    // Close the filter window
    document.getElementById('filterWindowAddons').classList.add('hidden');
    
    // Apply the filter
    addonsFilters.page = 1;
    fetchAndPopulateAddOns();
}

// Change page for add-ons
function changePageAddons(page) {
    addonsFilters.page = page;
    fetchAndPopulateAddOns();
}

// Modified fetchAndPopulateAddOns function with filtering
function fetchAndPopulateAddOns() {
    const tableBody = document.getElementById('addonTableBody');
    const paginationContainer = document.getElementById('paginationContainer_addons');
    const paginationInfo = document.getElementById('paginationInfo_addons');
    const loadingIndicator = document.getElementById('loadingIndicatorAddons');
    const totalCountElement = document.getElementById('addonsTotalCount');

    // Show loading indicator
    if (loadingIndicator) loadingIndicator.classList.remove('hidden');

    // Prepare query parameters
    const params = new URLSearchParams();
    params.append('page', addonsFilters.page);
    
    if (addonsFilters.search) {
        params.append('search', addonsFilters.search);
    }
    if (addonsFilters.status) {
        params.append('status', addonsFilters.status);
    }

    fetch(`servicesManagement/get_addOns.php?${params.toString()}`, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        }
    })
    .then(response => response.json())
    .then(data => {
        // Hide loading indicator
        if (loadingIndicator) loadingIndicator.classList.add('hidden');

        if (data.error) {
            console.error('Error:', data.error);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-4 py-6 text-sm text-center">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-exclamation-circle text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">Error loading add-ons</p>
                        </div>
                    </td>
                </tr>`;
            return;
        }

        // Update total count
        if (totalCountElement) {
            totalCountElement.textContent = data.totalRecords;
        }

        // Update dataset for pagination
        const container = document.querySelector('.branch-container[data-branch-id="addons"]');
        if (container) {
            container.dataset.totalServices = data.totalRecords;
        }

        // Populate table
        tableBody.innerHTML = '';
        if (data.data.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-4 py-6 text-sm text-center">
                        <div class="flex flex-col items-center">
                            <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                            <p class="text-gray-500">No add-ons found</p>
                        </div>
                    </td>
                </tr>`;
        } else {
            data.data.forEach(addon => {
                const statusClass = addon.status === 'active' 
                    ? 'bg-green-100 text-green-600 border border-green-200' 
                    : 'bg-orange-100 text-orange-500 border border-orange-200';
                const statusIcon = addon.status === 'active' ? 'fa-check-circle' : 'fa-pause-circle';

                const row = document.createElement('tr');
                row.className = 'border-b border-sidebar-border hover:bg-sidebar-hover transition-colors';
                row.innerHTML = `
                    <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">AOS-${String(addon.addOns_id).padStart(3, '0')}</td>
                    <td class="px-4 py-3.5 text-sm text-sidebar-text">${addon.addOns_name}</td>
                    <td class="px-4 py-3.5 text-sm text-sidebar-text">${addon.description || 'N/A'}</td>
                    <td class="px-4 py-3.5 text-sm text-sidebar-text">
                        <i class="${addon.icon} mr-2"></i>
                    </td>
                    <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">${formatPrice(addon.price)}</td>
                    <td class="px-4 py-3.5 text-sm">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ${statusClass}">
                            <i class="fas ${statusIcon} mr-1"></i> ${addon.status.charAt(0).toUpperCase() + addon.status.slice(1)}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-sm">
                        <div class="flex space-x-2">
                            <button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" 
                                    title="Edit Add-on" 
                                    onclick="editAddOn(${addon.addOns_id})">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip" 
                                    title="Archive Add-on" 
                                    onclick="deleteAddOn(${addon.addOns_id})">
                                <i class="fas fa-archive"></i>
                            </button>
                        </div>
                    </td>`;
                tableBody.appendChild(row);
            });
        }

        // Update pagination info
        const start = Math.min((data.currentPage - 1) * 5 + 1, data.totalRecords);
        const end = Math.min(data.currentPage * 5, data.totalRecords);
        paginationInfo.innerHTML = data.totalRecords > 0 
            ? `Showing ${start} - ${end} of ${data.totalRecords} add-ons`
            : 'No add-ons found';

        // Generate pagination controls
        paginationContainer.innerHTML = '';
        if (data.totalPages > 1) {
            // First page button
            const firstButton = document.createElement('button');
            firstButton.className = `px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}`;
            firstButton.innerHTML = '&laquo;';
            firstButton.onclick = () => { if (data.currentPage !== 1) changePageAddons(1); };
            if (data.currentPage === 1) firstButton.disabled = true;
            paginationContainer.appendChild(firstButton);
            
            // Previous page button
            const prevButton = document.createElement('button');
            prevButton.className = `px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}`;
            prevButton.innerHTML = '&lsaquo;';
            prevButton.onclick = () => { if (data.currentPage > 1) changePageAddons(data.currentPage - 1); };
            if (data.currentPage === 1) prevButton.disabled = true;
            paginationContainer.appendChild(prevButton);
            
            // Page numbers
            let startPage = Math.max(1, data.currentPage - 2);
            let endPage = Math.min(data.totalPages, startPage + 4);
            
            if (endPage - startPage < 4) {
                startPage = Math.max(1, endPage - 4);
            }
            
            for (let i = startPage; i <= endPage; i++) {
                const pageButton = document.createElement('button');
                pageButton.className = `px-3.5 py-1.5 rounded text-sm ${i === data.currentPage ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover'}`;
                pageButton.textContent = i;
                pageButton.onclick = () => changePageAddons(i);
                paginationContainer.appendChild(pageButton);
            }
            
            // Next page button
            const nextButton = document.createElement('button');
            nextButton.className = `px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.currentPage === data.totalPages ? 'opacity-50 cursor-not-allowed' : ''}`;
            nextButton.innerHTML = '&rsaquo;';
            nextButton.onclick = () => { if (data.currentPage < data.totalPages) changePageAddons(data.currentPage + 1); };
            if (data.currentPage === data.totalPages) nextButton.disabled = true;
            paginationContainer.appendChild(nextButton);
            
            // Last page button
            const lastButton = document.createElement('button');
            lastButton.className = `px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${data.currentPage === data.totalPages ? 'opacity-50 cursor-not-allowed' : ''}`;
            lastButton.innerHTML = '&raquo;';
            lastButton.onclick = () => { if (data.currentPage !== data.totalPages) changePageAddons(data.totalPages); };
            if (data.currentPage === data.totalPages) lastButton.disabled = true;
            paginationContainer.appendChild(lastButton);
        }
    })
    .catch(error => {
        console.error('Error fetching add-ons:', error);
        if (loadingIndicator) loadingIndicator.classList.add('hidden');
        tableBody.innerHTML = `
            <tr>
                <td colspan="7" class="px-4 py-6 text-sm text-center">
                    <div class="flex flex-col items-center">
                        <i class="fas fa-exclamation-circle text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500">Error loading add-ons</p>
                    </div>
                </td>
            </tr>`;
    });
}

// Initialize add-ons table on page load
document.addEventListener('DOMContentLoaded', function() {
    fetchAndPopulateAddOns(1);
});

// Update the filter function to use the specific table body ID
function filterAddOns(branchId) {
    const searchInput = document.getElementById(`searchInput${branchId}`) || 
                        document.getElementById(`searchInputMobile${branchId}`);
    const statusFilter = document.querySelector(`.branch-container[data-branch-id="${branchId}"] .filter-option.bg-sidebar-accent`);
    
    const searchTerm = searchInput.value.toLowerCase();
    let statusTerm = '';
    
    if (statusFilter && !statusFilter.textContent.includes('All Statuses')) {
        statusTerm = statusFilter.textContent.trim();
    }
    
    const rows = document.querySelectorAll('#addOnsTableBody tr');
    
    let visibleCount = 0;
    rows.forEach(row => {
        const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const status = row.querySelector('td:nth-child(5) span').textContent.trim();
        
        const nameMatch = name.includes(searchTerm);
        const statusMatch = statusTerm === '' || status === statusTerm;
        
        if (nameMatch && statusMatch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // Update visible count
    const totalRows = rows.length;
    document.getElementById(`paginationInfo_${branchId}`).textContent = 
        `Showing ${visibleCount > 0 ? 1 : 0} - ${visibleCount} of ${totalRows} add-ons`;
}

// Call the function when the page loads
document.addEventListener('DOMContentLoaded', fetchAndPopulateAddOns);



function deleteAddOn(id) {
    Swal.fire({
        title: "Are you sure?",
        text: "Warning: Archiving will disable this add-on for all users. You can restore it later if needed.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, archive it!" // Changed text
    }).then((result) => {
        if (result.isConfirmed) {
            // AJAX call to archive the add-on
            $.ajax({
                url: 'servicesManagement/delete_addOns.php', // This file archives, not deletes
                type: 'POST',
                data: { id: id },
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            "Archived!",
                            response.message,
                            "success"
                        ).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire(
                            "Error!",
                            response.message,
                            "error"
                        );
                    }
                },
                error: function() {
                    Swal.fire(
                        "Error!",
                        "An error occurred while processing your request.",
                        "error"
                    );
                }
            });
        }
    });
}

// Function to open the modal
function openAddAddonsModal() {
  // Clear previous selections
  document.getElementById('addAddonsForm').reset();
  document.getElementById('selectedIcon').value = 'fa-plus';
  document.getElementById('selectedIconPreview').className = 'fas fa-plus text-gray-500';
  
  // Load branches
  loadBranches();
  
  // Load icons
  loadFontAwesomeIcons();
  
  // Show modal
  document.getElementById('addAddonsModal').classList.remove('hidden');
}

// Function to close the modal
function closeAddAddonsModal() {
  document.getElementById('addAddonsModal').classList.add('hidden');
}

// Function to load branches
async function loadBranches() {
  const branchSelect = document.getElementById('addonBranch');
  branchSelect.innerHTML = '<option value="">Select Branch</option>';
  
  try {
    const response = await fetch('addCustomer/get_branches.php');
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    
    const branches = await response.json();
    
    if (branches && branches.length > 0) {
      branches.forEach(branch => {
        const option = document.createElement('option');
        option.value = branch.branch_id;
        option.textContent = branch.branch_name.replace(/\b\w/g, char => char.toUpperCase());
        branchSelect.appendChild(option);
      });
    } else {
      console.warn('No branches found');
      // Optional: Show a message to the user
      const option = document.createElement('option');
      option.value = '';
      option.textContent = 'No branches available';
      option.disabled = true;
      branchSelect.appendChild(option);
    }
  } catch (error) {
    console.error('Error loading branches:', error);
    // Optional: Show error message to user
    const option = document.createElement('option');
    option.value = '';
    option.textContent = 'Error loading branches';
    option.disabled = true;
    branchSelect.appendChild(option);
    
    // Show SweetAlert error
    Swal.fire({
      title: 'Error!',
      text: 'Failed to load branches. Please try again later.',
      icon: 'error',
      confirmButtonText: 'OK'
    });
  }
}

// Function to load Font Awesome icons
function loadFontAwesomeIcons(searchTerm = '') {
  const icons = [
    // General funeral services
    'fa-church', 
    'fa-pray', 
    'fa-hands-praying',
    'fa-cross',
    'fa-star-of-david',
    'fa-om',
    'fa-place-of-worship',
    'fa-bible',
    'fa-torah',
    'fa-quran',
    'fa-book-open',
    
    // Memorial symbols
    'fa-urn',
    'fa-headstone',
    'fa-monument',
    'fa-memorial',
    'fa-landmark',
    'fa-ribbon',
    'fa-dove',
    'fa-feather',
    'fa-feather-alt',
    'fa-leaf',
    'fa-tree',
    'fa-seedling',
    
    // Sympathy and grief
    'fa-heart',
    'fa-heart-broken',
    'fa-hand-holding-heart',
    'fa-hands-helping',
    'fa-handshake',
    'fa-hands-holding',
    'fa-hands-holding-heart',
    
    // Funeral process
    'fa-hearse',
    'fa-car-side',
    'fa-van-shuttle',
    'fa-flower',
    'fa-flower-tulip',
    'fa-flower-daffodil',
    'fa-lilies',
    'fa-roses',
    'fa-wreath',
    'fa-candle-holder',
    'fa-candle',
    'fa-fire',
    'fa-smoke',
    
    // Death symbols
    'fa-skull',
    'fa-skull-crossbones',
    'fa-hourglass',
    'fa-hourglass-half',
    'fa-hourglass-end',
    'fa-clock',
    'fa-calendar',
    'fa-calendar-days',
    
    // Afterlife concepts
    'fa-cloud',
    'fa-cloud-sun',
    'fa-sun',
    'fa-moon',
    'fa-stars',
    'fa-star',
    'fa-star-and-crescent',
    'fa-angel',
    'fa-angels',
    'fa-spa',
    
    // Documentation
    'fa-file-contract',
    'fa-file-signature',
    'fa-certificate',
    'fa-award',
    'fa-medal',
    
    // Support services
    'fa-phone',
    'fa-envelope',
    'fa-comments',
    'fa-user-nurse',
    'fa-user-md',
    'fa-user-graduate',
    'fa-user-tie',
    'fa-users',
    
    // Payment/Financial
    'fa-money-bill',
    'fa-money-bill-wave',
    'fa-credit-card',
    'fa-receipt',
    'fa-calculator',
    
    // Location
    'fa-map',
    'fa-map-marker',
    'fa-map-marker-alt',
    'fa-globe',
    'fa-globe-americas',
    
    // Digital memorials
    'fa-laptop',
    'fa-tablet',
    'fa-mobile',
    'fa-camera',
    'fa-photo-film',
    'fa-images',
    'fa-portrait',
    'fa-frame'
  ];
  
  const filteredIcons = icons.filter(icon => 
    icon.toLowerCase().includes(searchTerm.toLowerCase())
  );
  
  const iconGrid = document.getElementById('iconGrid');
  iconGrid.innerHTML = '';
  
  // Enhanced no results styling
  if (filteredIcons.length === 0) {
    const noResults = document.createElement('div');
    noResults.className = 'col-span-full flex flex-col items-center justify-center py-8 text-center';
    noResults.innerHTML = `
      <div class="w-16 h-16 bg-gradient-to-br from-gold/20 to-yellow-600/20 rounded-full flex items-center justify-center mb-3">
        <i class="fas fa-search text-2xl text-gold"></i>
      </div>
      <p class="text-gray-500 font-inter font-medium">No icons found</p>
      <p class="text-xs text-gray-400 font-inter mt-1">Try a different search term</p>
    `;
    iconGrid.appendChild(noResults);
    return;
  }
  
  filteredIcons.forEach(icon => {
    const iconName = icon.replace('fa-', '');
    const iconItem = document.createElement('div');
    
    // Enhanced styling with gold theme
    iconItem.className = 'icon-item group flex flex-col items-center p-3 rounded-xl border-2 border-gray-200 cursor-pointer transition-all duration-300 hover:border-gold hover:bg-gold/10 hover:scale-110 hover:shadow-gold bg-white/70 backdrop-blur-sm';
    iconItem.dataset.icon = icon;
    
    // Icon display inside a circle
    iconItem.innerHTML = `
      <div class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 group-hover:bg-sidebar-accent transition-colors duration-200">
        <i class="fas ${icon} text-lg text-gray-600 group-hover:text-white transition-colors duration-200"></i>
      </div>
    `;
    
    // Enhanced click handler with smooth animations
    iconItem.addEventListener('click', function() {
      // Update hidden input and preview
      document.getElementById('selectedIcon').value = icon;
      const previewIcon = document.getElementById('selectedIconPreview');
      previewIcon.className = `fas ${icon} text-white text-lg`;
      
      // Remove selected class from all icons with smooth transitions
      document.querySelectorAll('.icon-item').forEach(el => {
        el.classList.remove('selected', 'border-gold', 'bg-gold/20', 'shadow-gold');
        el.classList.add('border-gray-200');
        
        // Reset icon and text colors
        const iconEl = el.querySelector('i');
        const textEl = el.querySelector('span');
        if (iconEl && textEl) {
          iconEl.classList.remove('text-gold');
          iconEl.classList.add('text-gray-600');
          textEl.classList.remove('text-dark');
          textEl.classList.add('text-gray-600');
        }
      });
      
      // Add selected class to clicked icon with enhanced styling
      this.classList.add('selected', 'border-gold', 'bg-gold/20', 'shadow-gold');
      this.classList.remove('border-gray-200');
      
      // Update icon and text colors for selected state
      const iconEl = this.querySelector('i');
      const textEl = this.querySelector('span');
      if (iconEl && textEl) {
        iconEl.classList.add('text-gold');
        iconEl.classList.remove('text-gray-600');
        textEl.classList.add('text-dark');
        textEl.classList.remove('text-gray-600');
      }
      
      // Add a subtle bounce animation to indicate selection
      this.style.animation = 'bounceGentle 0.6s ease-out';
      setTimeout(() => {
        this.style.animation = '';
      }, 600);
    });
    
    iconGrid.appendChild(iconItem);
  });
}

// Enhanced initialization function with search functionality
function initializeIconSelector() {
  // Load icons initially
  loadFontAwesomeIcons();
  
  // Enhanced search functionality
  const iconSearch = document.getElementById('iconSearch');
  if (iconSearch) {
    let searchTimeout;
    
    iconSearch.addEventListener('input', function() {
      clearTimeout(searchTimeout);
      const searchIcon = this.parentElement.querySelector('.search-icon');
      
      // Add loading state
      if (searchIcon) {
        searchIcon.className = 'fas fa-spinner fa-spin text-gold absolute right-3 top-1/2 transform -translate-y-1/2';
      }
      
      // Debounce search
      searchTimeout = setTimeout(() => {
        loadFontAwesomeIcons(this.value.trim());
        
        // Reset search icon
        if (searchIcon) {
          searchIcon.className = 'fas fa-search text-gold absolute right-3 top-1/2 transform -translate-y-1/2';
        }
      }, 300);
    });
    
    // Add search icon if it doesn't exist
    if (!iconSearch.parentElement.querySelector('.search-icon')) {
      const searchIconEl = document.createElement('i');
      searchIconEl.className = 'fas fa-search text-gold absolute right-3 top-1/2 transform -translate-y-1/2 search-icon pointer-events-none';
      iconSearch.parentElement.style.position = 'relative';
      iconSearch.parentElement.appendChild(searchIconEl);
      iconSearch.classList.add('pr-10'); // Add padding to make room for icon
    }
  }
}

// Enhanced category filtering (optional enhancement)
function filterIconsByCategory(category = 'all') {
  const categoryMap = {
    'religious': ['fa-church', 'fa-pray', 'fa-hands-praying', 'fa-cross', 'fa-star-of-david', 'fa-om', 'fa-place-of-worship', 'fa-bible', 'fa-torah', 'fa-quran', 'fa-book-open'],
    'memorial': ['fa-urn', 'fa-headstone', 'fa-monument', 'fa-memorial', 'fa-landmark', 'fa-ribbon', 'fa-dove', 'fa-feather', 'fa-feather-alt', 'fa-leaf', 'fa-tree', 'fa-seedling'],
    'sympathy': ['fa-heart', 'fa-heart-broken', 'fa-hand-holding-heart', 'fa-hands-helping', 'fa-handshake', 'fa-hands-holding', 'fa-hands-holding-heart'],
    'flowers': ['fa-flower', 'fa-flower-tulip', 'fa-flower-daffodil', 'fa-lilies', 'fa-roses', 'fa-wreath'],
    'spiritual': ['fa-cloud', 'fa-cloud-sun', 'fa-sun', 'fa-moon', 'fa-stars', 'fa-star', 'fa-star-and-crescent', 'fa-angel', 'fa-angels', 'fa-spa'],
    'services': ['fa-phone', 'fa-envelope', 'fa-comments', 'fa-user-nurse', 'fa-user-md', 'fa-user-graduate', 'fa-user-tie', 'fa-users']
  };
  
  if (category === 'all') {
    loadFontAwesomeIcons();
  } else if (categoryMap[category]) {
    const iconGrid = document.getElementById('iconGrid');
    iconGrid.innerHTML = '';
    
    categoryMap[category].forEach(icon => {
      // Use the same icon creation logic as in loadFontAwesomeIcons
      const iconName = icon.replace('fa-', '');
      const iconItem = document.createElement('div');
      iconItem.className = 'icon-item group flex flex-col items-center p-3 rounded-xl border-2 border-gray-200 cursor-pointer transition-all duration-300 hover:border-gold hover:bg-gold/10 hover:scale-110 hover:shadow-gold bg-white/70 backdrop-blur-sm';
      iconItem.dataset.icon = icon;
      iconItem.innerHTML = `
        <div class="w-10 h-10 flex items-center justify-center rounded-full bg-gray-100 group-hover:bg-sidebar-accent transition-colors duration-200">
          <i class="fas ${icon} text-lg text-gray-600 group-hover:text-white transition-colors duration-200"></i>
        </div>
      `;
      
      // Add the same click handler logic
      iconItem.addEventListener('click', function() {
        document.getElementById('selectedIcon').value = icon;
        const previewIcon = document.getElementById('selectedIconPreview');
        previewIcon.className = `fas ${icon} text-white text-lg`;
        
        document.querySelectorAll('.icon-item').forEach(el => {
          el.classList.remove('selected', 'border-gold', 'bg-gold/20', 'shadow-gold');
          el.classList.add('border-gray-200');
          const iconEl = el.querySelector('i');
          const textEl = el.querySelector('span');
          if (iconEl && textEl) {
            iconEl.classList.remove('text-gold');
            iconEl.classList.add('text-gray-600');
            textEl.classList.remove('text-dark');
            textEl.classList.add('text-gray-600');
          }
        });
        
        this.classList.add('selected', 'border-gold', 'bg-gold/20', 'shadow-gold');
        this.classList.remove('border-gray-200');
        const iconEl = this.querySelector('i');
        const textEl = this.querySelector('span');
        if (iconEl && textEl) {
          iconEl.classList.add('text-gold');
          iconEl.classList.remove('text-gray-600');
          textEl.classList.add('text-dark');
          textEl.classList.remove('text-gray-600');
        }
        
        this.style.animation = 'bounceGentle 0.6s ease-out';
        setTimeout(() => {
          this.style.animation = '';
        }, 600);
      });
      
      iconGrid.appendChild(iconItem);
    });
  }
}

// Search functionality for icons
document.getElementById('iconSearch').addEventListener('input', function() {
  loadFontAwesomeIcons(this.value);
});

// Save add-on functionality
document.getElementById('saveAddonBtn').addEventListener('click', function() {
  const addonName = document.getElementById('addonName').value.trim();
  const addonDescription = document.getElementById('addonDescription').value.trim();
  const addonPrice = document.getElementById('addonPrice').value.trim();
  const addonBranch = document.getElementById('addonBranch').value;
  const addonIcon = "fas " + document.getElementById('selectedIcon').value;
  
  // Validate all required fields are filled
  if (!addonName || !addonPrice || !addonBranch || !addonIcon) {
    Swal.fire({
      title: 'Error!',
      text: 'Please fill in all required fields',
      icon: 'error',
      confirmButtonText: 'OK'
    });
    return;
  }
  
  // Validate price is a positive number
  const priceValue = parseFloat(addonPrice);
  if (isNaN(priceValue) || priceValue < 0) {
    Swal.fire({
      title: 'Error!',
      text: 'Price must be a positive number',
      icon: 'error',
      confirmButtonText: 'OK'
    });
    return;
  }
  
  // Validate name isn't too short
  if (addonName.length < 2) {
    Swal.fire({
      title: 'Error!',
      text: 'Addon name must be at least 2 characters',
      icon: 'error',
      confirmButtonText: 'OK'
    });
    return;
  }
  
  const addonData = {
    name: addonName,
    description: addonDescription, // Added description to the data object
    price: priceValue,
    branch_id: addonBranch,
    icon: addonIcon
  };
  
  fetch('servicesManagement/add_addOns.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(addonData)
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      Swal.fire({
        title: 'Success!',
        text: data.message,
        icon: 'success',
        confirmButtonText: 'OK'
      }).then(() => {
        if (typeof closeModal === "function") {
          closeModal();
        }
        window.location.reload();
      });
    } else {
      Swal.fire({
        title: 'Error!',
        text: data.message,
        icon: 'error',
        confirmButtonText: 'OK'
      });
    }
  })
  .catch(error => {
    Swal.fire({
      title: 'Error!',
      text: 'Network error: ' + error.message,
      icon: 'error',
      confirmButtonText: 'OK'
    });
  });
});

// Input validation for add-on modal fields
document.addEventListener('DOMContentLoaded', function() {
  // Add-on name input validation
  const addonNameInput = document.getElementById('addonName');
  if (addonNameInput) {
    addonNameInput.addEventListener('keypress', function(e) {
      validateSpaceInput(e, this);
    });
    
    addonNameInput.addEventListener('input', function(e) {
      preventConsecutiveSpaces(this);
    });
  }
  
  // Add-on description input validation
  const addonDescInput = document.getElementById('addonDescription');
  if (addonDescInput) {
    addonDescInput.addEventListener('keypress', function(e) {
      validateSpaceInput(e, this);
    });
    
    addonDescInput.addEventListener('input', function(e) {
      preventConsecutiveSpaces(this);
    });
  }
  
  // Icon search input validation
  const iconSearchInput = document.getElementById('iconSearch');
  if (iconSearchInput) {
    iconSearchInput.addEventListener('keypress', function(e) {
      validateSpaceInput(e, this);
    });
    
    iconSearchInput.addEventListener('input', function(e) {
      preventConsecutiveSpaces(this);
    });
  }
});

// Function to validate space input
function validateSpaceInput(event, input) {
  const char = event.key;
  const currentValue = input.value;
  
  // If space is pressed
  if (char === ' ') {
    // Check if there are at least 2 non-space characters
    const nonSpaceChars = currentValue.replace(/\s/g, '');
    if (nonSpaceChars.length < 2) {
      event.preventDefault();
      return false;
    }
    
    // Check if the last character is already a space
    if (currentValue.length > 0 && currentValue[currentValue.length - 1] === ' ') {
      event.preventDefault();
      return false;
    }
  }
  
  return true;
}

// Function to prevent consecutive spaces in existing text
function preventConsecutiveSpaces(input) {
  let value = input.value;
  const cursorPosition = input.selectionStart;
  
  // Replace multiple consecutive spaces with single space
  const newValue = value.replace(/\s{2,}/g, ' ');
  
  // Update input value if it changed
  if (newValue !== value) {
    input.value = newValue;
    
    // Restore cursor position (adjust for removed spaces)
    const removedSpaces = value.length - newValue.length;
    const newCursorPosition = Math.max(0, cursorPosition - removedSpaces);
    input.setSelectionRange(newCursorPosition, newCursorPosition);
  }
  
  // Additional validation: ensure no spaces at the beginning
  if (input.value.startsWith(' ')) {
    input.value = input.value.trimStart();
  }
}

// Function to open edit modal
async function editAddOn(id) {
    console.log('Edit add-on with ID:', id);
    
    try {
        // Show loading state
        Swal.fire({
            title: 'Loading add-on details',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        // Fetch add-on details
        const response = await fetch(`servicesManagement/get_editAddondetails.php?id=${id}`);
        const data = await response.json();
        
        Swal.close();
        
        if (data.error) {
            Swal.fire('Error', data.error, 'error');
            return;
        }
        
        const addon = data.data;
        
        // Populate form fields
        document.getElementById('editAddonId').value = addon.addOns_id;
        document.getElementById('editAddonName').value = addon.addOns_name;
        document.getElementById('editAddonDescription').value = addon.description || '';
        document.getElementById('editAddonPrice').value = addon.price;
        
        // Set status toggle
        const statusToggle = document.getElementById('editAddonStatus');
        statusToggle.checked = addon.status === 'active';
        document.getElementById('editAddonStatusLabel').textContent = addon.status === 'active' ? 'Active' : 'Inactive';
        
        // Set icon
        document.getElementById('editSelectedIcon').value = addon.icon;
        document.getElementById('editSelectedIconPreview').className = `fas ${addon.icon} text-white text-lg`;
        
        // Load branches and set selected branch
        await loadEditBranches(addon.branch_id);
        
        // Load icons and highlight selected one
        loadEditFontAwesomeIcons();
        highlightSelectedEditIcon(addon.icon);
        
        // Show modal
        document.getElementById('editAddonsModal').classList.remove('hidden');
        
    } catch (error) {
        Swal.close();
        Swal.fire('Error', 'Failed to load add-on details: ' + error.message, 'error');
        console.error('Error loading add-on details:', error);
    }
}

// Function to close edit modal
function closeEditModal() {
    document.getElementById('editAddonsModal').classList.add('hidden');
}

// Function to load branches for edit modal
async function loadEditBranches(selectedBranchId = null) {
    const branchSelect = document.getElementById('editAddonBranch');
    branchSelect.innerHTML = '<option value="">Select Branch</option>';
    
    try {
        const response = await fetch('addCustomer/get_branches.php');
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const branches = await response.json();
        
        if (branches && branches.length > 0) {
            branches.forEach(branch => {
                const option = document.createElement('option');
                option.value = branch.branch_id;
                option.textContent = branch.branch_name.replace(/\b\w/g, char => char.toUpperCase());
                option.selected = (selectedBranchId && branch.branch_id == selectedBranchId);
                branchSelect.appendChild(option);
            });
        } else {
            console.warn('No branches found');
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No branches available';
            option.disabled = true;
            branchSelect.appendChild(option);
        }
    } catch (error) {
        console.error('Error loading branches:', error);
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Error loading branches';
        option.disabled = true;
        branchSelect.appendChild(option);
    }
}

// Function to load icons for edit modal
function loadEditFontAwesomeIcons(searchTerm = '') {
    // Use the same icons array as in the add modal
    const icons = [
    // General funeral services
    'fa-church', 
    'fa-pray', 
    'fa-hands-praying',
    'fa-cross',
    'fa-star-of-david',
    'fa-om',
    'fa-place-of-worship',
    'fa-bible',
    'fa-torah',
    'fa-quran',
    'fa-book-open',
    
    // Memorial symbols
    'fa-urn',
    'fa-headstone',
    'fa-monument',
    'fa-memorial',
    'fa-landmark',
    'fa-ribbon',
    'fa-dove',
    'fa-feather',
    'fa-feather-alt',
    'fa-leaf',
    'fa-tree',
    'fa-seedling',
    
    // Sympathy and grief
    'fa-heart',
    'fa-heart-broken',
    'fa-hand-holding-heart',
    'fa-hands-helping',
    'fa-handshake',
    'fa-hands-holding',
    'fa-hands-holding-heart',
    
    // Funeral process
    'fa-hearse',
    'fa-car-side',
    'fa-van-shuttle',
    'fa-flower',
    'fa-flower-tulip',
    'fa-flower-daffodil',
    'fa-lilies',
    'fa-roses',
    'fa-wreath',
    'fa-candle-holder',
    'fa-candle',
    'fa-fire',
    'fa-smoke',
    
    // Death symbols
    'fa-skull',
    'fa-skull-crossbones',
    'fa-hourglass',
    'fa-hourglass-half',
    'fa-hourglass-end',
    'fa-clock',
    'fa-calendar',
    'fa-calendar-days',
    
    // Afterlife concepts
    'fa-cloud',
    'fa-cloud-sun',
    'fa-sun',
    'fa-moon',
    'fa-stars',
    'fa-star',
    'fa-star-and-crescent',
    'fa-angel',
    'fa-angels',
    'fa-spa',
    
    // Documentation
    'fa-file-contract',
    'fa-file-signature',
    'fa-certificate',
    'fa-award',
    'fa-medal',
    
    // Support services
    'fa-phone',
    'fa-envelope',
    'fa-comments',
    'fa-user-nurse',
    'fa-user-md',
    'fa-user-graduate',
    'fa-user-tie',
    'fa-users',
    
    // Payment/Financial
    'fa-money-bill',
    'fa-money-bill-wave',
    'fa-credit-card',
    'fa-receipt',
    'fa-calculator',
    
    // Location
    'fa-map',
    'fa-map-marker',
    'fa-map-marker-alt',
    'fa-globe',
    'fa-globe-americas',
    
    // Digital memorials
    'fa-laptop',
    'fa-tablet',
    'fa-mobile',
    'fa-camera',
    'fa-photo-film',
    'fa-images',
    'fa-portrait',
    'fa-frame'
  ];
    
    const filteredIcons = icons.filter(icon => 
        icon.toLowerCase().includes(searchTerm.toLowerCase())
    );
    
    const iconGrid = document.getElementById('editIconGrid');
    iconGrid.innerHTML = '';
    
    if (filteredIcons.length === 0) {
        const noResults = document.createElement('div');
        noResults.className = 'col-span-full flex flex-col items-center justify-center py-8 text-center';
        noResults.innerHTML = `
            <div class="w-16 h-16 bg-gradient-to-br from-gold/20 to-yellow-600/20 rounded-full flex items-center justify-center mb-3">
                <i class="fas fa-search text-2xl text-gold"></i>
            </div>
            <p class="text-gray-500 font-inter font-medium">No icons found</p>
            <p class="text-xs text-gray-400 font-inter mt-1">Try a different search term</p>
        `;
        iconGrid.appendChild(noResults);
        return;
    }
    
    filteredIcons.forEach(icon => {
        const iconName = icon.replace('fa-', '');
        const iconItem = document.createElement('div');
        iconItem.className = 'icon-item group flex flex-col items-center p-3 rounded-xl border-2 border-gray-200 cursor-pointer transition-all duration-300 hover:border-gold hover:bg-gold/10 hover:scale-110 hover:shadow-gold bg-white/70 backdrop-blur-sm';
        iconItem.dataset.icon = icon;
        iconItem.innerHTML = `
            <div class="w-8 h-8 flex items-center justify-center mb-2 transition-colors duration-200">
                <i class="fas ${icon} text-lg text-gray-600 group-hover:text-gold transition-colors duration-200"></i>
            </div>
            <span class="text-xs text-center font-inter font-medium text-gray-600 group-hover:text-dark transition-colors duration-200 leading-tight max-w-full truncate" title="${iconName}">${iconName}</span>
        `;
        
        iconItem.addEventListener('click', function() {
            document.getElementById('editSelectedIcon').value = icon;
            const previewIcon = document.getElementById('editSelectedIconPreview');
            previewIcon.className = `fas ${icon} text-white text-lg`;
            
            document.querySelectorAll('#editIconGrid .icon-item').forEach(el => {
                el.classList.remove('selected', 'border-gold', 'bg-gold/20', 'shadow-gold');
                el.classList.add('border-gray-200');
                const iconEl = el.querySelector('i');
                const textEl = el.querySelector('span');
                if (iconEl && textEl) {
                    iconEl.classList.remove('text-gold');
                    iconEl.classList.add('text-gray-600');
                    textEl.classList.remove('text-dark');
                    textEl.classList.add('text-gray-600');
                }
            });
            
            this.classList.add('selected', 'border-gold', 'bg-gold/20', 'shadow-gold');
            this.classList.remove('border-gray-200');
            const iconEl = this.querySelector('i');
            const textEl = this.querySelector('span');
            if (iconEl && textEl) {
                iconEl.classList.add('text-gold');
                iconEl.classList.remove('text-gray-600');
                textEl.classList.add('text-dark');
                textEl.classList.remove('text-gray-600');
            }
            
            this.style.animation = 'bounceGentle 0.6s ease-out';
            setTimeout(() => {
                this.style.animation = '';
            }, 600);
        });
        
        iconGrid.appendChild(iconItem);
    });
}

// Function to highlight the selected icon in edit modal
function highlightSelectedEditIcon(icon) {
    setTimeout(() => {
        const iconItems = document.querySelectorAll('#editIconGrid .icon-item');
        iconItems.forEach(item => {
            if (item.dataset.icon === icon) {
                item.click(); // Simulate click to select it
            }
        });
    }, 300); // Small delay to ensure icons are loaded
}

// Status toggle event listener
document.getElementById('editAddonStatus').addEventListener('change', function() {
    document.getElementById('editAddonStatusLabel').textContent = this.checked ? 'Active' : 'Inactive';
});

// Icon search for edit modal
document.getElementById('editIconSearch').addEventListener('input', function() {
    loadEditFontAwesomeIcons(this.value);
});

// Update add-on functionality
document.getElementById('updateAddonBtn').addEventListener('click', function() {
    const addonId = document.getElementById('editAddonId').value;
    const addonName = document.getElementById('editAddonName').value.trim();
    const addonDescription = document.getElementById('editAddonDescription').value.trim();
    const addonPrice = document.getElementById('editAddonPrice').value.trim();
    const addonBranch = document.getElementById('editAddonBranch').value;
    const addonIcon = "fas " + document.getElementById('editSelectedIcon').value;
    const addonStatus = document.getElementById('editAddonStatus').checked ? 'active' : 'inactive';
    
    // Validate all required fields are filled
    if (!addonName || !addonPrice || !addonBranch || !addonIcon) {
        Swal.fire({
            title: 'Error!',
            text: 'Please fill in all required fields',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Validate price is a positive number
    const priceValue = parseFloat(addonPrice);
    if (isNaN(priceValue)) {
        Swal.fire({
            title: 'Error!',
            text: 'Price must be a valid number',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    if (priceValue < 0) {
        Swal.fire({
            title: 'Error!',
            text: 'Price must be a positive number',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    // Validate name isn't too short
    if (addonName.length < 2) {
        Swal.fire({
            title: 'Error!',
            text: 'Addon name must be at least 2 characters',
            icon: 'error',
            confirmButtonText: 'OK'
        });
        return;
    }
    
    const addonData = {
        id: addonId,
        name: addonName,
        description: addonDescription,
        price: priceValue,
        branch_id: addonBranch,
        icon: addonIcon,
        status: addonStatus
    };
    
    fetch('servicesManagement/update_addOns.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(addonData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                closeEditModal();
                window.location.reload();
            });
        } else {
            Swal.fire({
                title: 'Error!',
                text: data.message,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    })
    .catch(error => {
        Swal.fire({
            title: 'Error!',
            text: 'Network error: ' + error.message,
            icon: 'error',
            confirmButtonText: 'OK'
        });
    });
});

function formatPrice(amount) {
    return "₱" + Number(amount).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

</script>

<script>
// Function to open the modal
function openAddonsArchived() {
    const modal = document.getElementById('archivedModal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.add('fade-in');
    }, 10);
    loadArchivedAddons();
    populateBranchFilter();
}

// Close modal when clicking outside the content
window.onclick = function(event) {
    const modal = document.getElementById('archivedModal');
    if (event.target === modal) {
        closeModal();
    }
};

// Function to load archived add-ons (simulated with static data)
function loadArchivedAddons(searchTerm = '', branchFilter = '') {
    // Fetch data from the PHP API
    fetch('servicesManagement/get_archived_addOns.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            search: searchTerm,
            branch: branchFilter
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(archivedAddons => {
        const tableBody = document.getElementById('archivedTableBody');
        tableBody.innerHTML = '';
        
        // Update item count
        document.getElementById('itemCount').textContent = archivedAddons.length;
        
        archivedAddons.forEach(addon => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50 border-b';
            row.innerHTML = `
                <td class="p-4 font-medium">${addon.addOns_id}</td>
                <td class="p-4">${addon.addOns_name}</td>
                <td class="p-4">${formatPrice(addon.price)}</td>
                <td class="p-4">${addon.branch_name.replace(/\b\w/g, char => char.toUpperCase())}</td>
                <td class="p-4"><span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Archived</span></td>
                <td class="p-4 text-center">
                    <button class="px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded transition-colors" onclick="unarchiveAddon(${addon.addOns_id})">
                        <i class="fas fa-box-open mr-1"></i> Unarchive
                    </button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    })
    .catch(error => {
        console.error('Error fetching archived add-ons:', error);
        // Fallback to hardcoded data if API fails
        console.log('Using fallback data');
        // You could add the hardcoded fallback here if needed
    });
}

// Function to handle search
function handleSearch() {
    const searchTerm = document.getElementById('searchInput').value;
    const branchFilter = document.getElementById('branchFilter').value;
    loadArchivedAddons(searchTerm, branchFilter);
}

// Function to unarchive an addon
function unarchiveAddon(id) {
    if (confirm('Are you sure you want to unarchive this add-on?')) {
        fetch('servicesManagement/unarchive_addOns.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Add-on unarchived successfully');
                loadArchivedAddons(); // Reload the table
                fetchAndPopulateAddOns();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error unarchiving add-on');
        });
    }
}

// Function to close modal
function closeModal() {
    document.getElementById('archivedModal').classList.add('hidden');
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', function() {
    const searchText = this.value.toLowerCase();
    const rows = document.querySelectorAll('#archivedTableBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchText) ? '' : 'none';
    });
});

// Initialize the modal with data on first open
document.addEventListener('DOMContentLoaded', function() {
    // Preload the data
    loadArchivedAddons();
});

// Function to populate branch filter options
function populateBranchFilter() {
    // You would typically fetch this from an API
    const branches = ["Paete", "Pila"];
    const select = document.getElementById('branchFilter');
    
    branches.forEach(branch => {
        const option = document.createElement('option');
        option.value = branch.toLowerCase();;
        option.textContent = branch;
        select.appendChild(option);
    });
}
</script>
  

  <script src="tailwind.js"></script>
  
</body>
</html>