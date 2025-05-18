<?php
// employee_expenses.php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check for employee user type (user_type = 2)
if ($_SESSION['user_type'] != 2) {
    switch ($_SESSION['user_type']) {
        case 1: // Admin
            header("Location: ../admin/admin_index.php");
            break;
        case 3: // Customer
            header("Location: ../customer/index.php");
            break;
        default:
            session_destroy();
            header("Location: ../Landing_Page/login.php");
    }
    exit();
}

// Session timeout (30 minutes)
$session_timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../Landing_Page/login.php?timeout=1");
    exit();
}

$_SESSION['last_activity'] = time();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Database connection
require_once '../db_connect.php';
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, email, birthdate, branch_loc FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$first_name = $row['first_name'];
$last_name = $row['last_name'];
$email = $row['email'];
$branch = (int)$row['branch_loc'];
$_SESSION['branch_employee'] = (int)$row['branch_loc'];

// Get expenses for the current branch with pagination
$items_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Base query
$expense_query = "SELECT expense_ID, category, expense_name, date, branch_id, status, price, notes 
                  FROM `expense_tb` 
                  WHERE branch_id = ? AND appearance = 'visible'";

// Count total expenses for pagination
$count_query = "SELECT COUNT(*) as total FROM expense_tb WHERE branch_id = ? AND appearance = 'visible'";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param("s", $branch);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get filtered/sorted expenses
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Add filters to query
$params = [$branch];
$types = "s";

if (!empty($search)) {
    $expense_query .= " AND (expense_name LIKE ? OR notes LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if (!empty($category_filter)) {
    $expense_query .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $expense_query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add sorting
$valid_sort_columns = ['expense_ID', 'expense_name', 'category', 'price', 'date', 'status'];
$sort_by = in_array($sort_by, $valid_sort_columns) ? $sort_by : 'date';
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
$expense_query .= " ORDER BY $sort_by $sort_order";

// Add pagination
$expense_query .= " LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";

// Prepare and execute query
$expense_stmt = $conn->prepare($expense_query);
$expense_stmt->bind_param($types, ...$params);
$expense_stmt->execute();
$expense_result = $expense_stmt->get_result();
$expenses = $expense_result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_expenses_query = "SELECT SUM(price) as total FROM expense_tb WHERE branch_id = ? AND appearance = 'visible'";
$total_stmt = $conn->prepare($total_expenses_query);
$total_stmt->bind_param("s", $branch);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_expenses = $total_result->fetch_assoc()['total'] ?? 0;

// 2. Monthly expenses query (filtering by current month)
$current_month_start = date('Y-m-01'); // First day of current month
$next_month_start = date('Y-m-01', strtotime('+1 month')); // First day of next month

$monthly_expenses_query = "
    SELECT SUM(price) as total 
    FROM expense_tb 
    WHERE branch_id = ? 
      AND appearance = 'visible' 
      AND date >= ? 
      AND date < ?
";

$monthly_stmt = $conn->prepare($monthly_expenses_query);
$monthly_stmt->bind_param("iss", $branch, $current_month_start, $next_month_start);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();
$monthly_expenses = $monthly_result->fetch_assoc()['total'] ?? 0;


// Get pending payments count
$pending_query = "SELECT COUNT(*) as pending FROM expense_tb WHERE branch_id = ? AND status = 'To be paid' AND appearance = 'visible'";
$pending_stmt = $conn->prepare($pending_query);
$pending_stmt->bind_param("s", $branch);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();
$pending_payments = $pending_result->fetch_assoc()['pending'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GrievEase - Expenses</title>
  <?php include 'faviconLogo.php'; ?>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
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
        
        /* Gradient background for menu section headers */
        .menu-header {
          background: linear-gradient(to right, rgba(202, 138, 4, 0.1), transparent);
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

        /* Modal scroll container styles */
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

        /* Filter dropdown styles */
        .filter-dropdown {
          position: relative;
          display: inline-block;
        }

        .filter-window {
          position: absolute;
          right: 0;
          z-index: 10;
          margin-top: 0.5rem;
          width: 16rem;
          border-radius: 0.375rem;
          background-color: white;
          box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
          border: 1px solid #e5e7eb;
        }

        .filter-option {
          transition: all 0.2s ease;
        }

        .filter-option:hover {
          background-color: #f3f4f6;
        }

        /* Card styles */
        .card-gradient {
          background: linear-gradient(to right, var(--tw-gradient-from), var(--tw-gradient-to));
        }

        .card-hover {
          transition: all 0.3s ease;
        }

        .card-hover:hover {
          transform: translateY(-2px);
          box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        /* Table styles */
        .table-hover tr:hover {
          background-color: rgba(202, 138, 4, 0.05);
        }

        .table-striped tr:nth-child(even) {
          background-color: rgba(249, 250, 251, 0.5);
        }

        /* Status badge styles */
        .status-badge {
          display: inline-flex;
          align-items: center;
          padding: 0.25rem 0.75rem;
          border-radius: 9999px;
          font-size: 0.75rem;
          font-weight: 500;
          line-height: 1;
        }

        .status-badge i {
          margin-right: 0.375rem;
        }

        /* Action button styles */
        .action-button {
          padding: 0.5rem;
          border-radius: 0.5rem;
          transition: all 0.2s ease;
        }

        .action-button:hover {
          transform: translateY(-1px);
        }

        /* Tooltip styles */
        .tooltip {
          position: relative;
        }

        .tooltip:before {
          content: attr(title);
          position: absolute;
          bottom: 100%;
          left: 50%;
          transform: translateX(-50%);
          padding: 0.25rem 0.5rem;
          background-color: rgba(0, 0, 0, 0.8);
          color: white;
          font-size: 0.75rem;
          border-radius: 0.25rem;
          white-space: nowrap;
          opacity: 0;
          visibility: hidden;
          transition: all 0.2s ease;
        }

        .tooltip:hover:before {
          opacity: 1;
          visibility: visible;
        }
  </style>
</head>
<body class="flex bg-gray-50">
  <!-- Sidebar (same as before) -->
  <nav id="sidebar" class="w-64 h-screen bg-sidebar-bg font-hedvig fixed transition-all duration-300 overflow-y-auto z-10 scrollbar-thin shadow-sidebar animate-sidebar sidebar">
  <!-- Logo and Header with hamburger menu -->
  <div class="flex items-center px-5 py-6 border-b border-sidebar-border">
    <button id="hamburger-menu" class="p-2 mr-2 bg-white rounded-lg shadow-md text-gray-600 hover:text-gray-900 transition-all duration-300">
      <i class="fas fa-bars"></i>
    </button>
    <!-- <img src="../Landing_Page/Landing_images/logo.png" alt="GrievEase Logo" class="h-10 w-auto mr-3"> -->
    <div class="text-2xl font-cinzel font-bold text-sidebar-accent">GrievEase</div>
  </div>
    
    <!-- User Profile -->
    <div class="flex items-center px-5 py-4 border-b border-sidebar-border bg-gradient-to-r from-navy to-primary">
      <div class="w-10 h-10 rounded-full bg-yellow-600 flex items-center justify-center shadow-md">
        <i class="fas fa-user text-white"></i>
      </div>
      <div class="ml-3">
        <div class="text-sm font-medium text-sidebar-text">
          <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
        </div>
        <div class="text-xs text-sidebar-text opacity-70">Employee</div>
      </div>
      <div class="ml-auto">
        <span class="w-3 h-3 bg-success rounded-full block"></span>
      </div>
    </div>
    
    <!-- Menu Items -->
    <div class="pt-4 pb-8">
      <!-- Main Navigation -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Main</h5>
      </div>
      <ul class="list-none p-0 mb-6">
        <li>
          <a href="index.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-tachometer-alt w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Dashboard</span>
          </a>
        </li> 
        <li>
          <a href="employee_customer_account_creation.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-user-circle w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Customer Account Management</span>
          </a>
        </li>
        <li>
          <a href="employee_inventory.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-boxes w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>View Inventory</span>
          </a>
        </li>
        <li>
          <a href="employee_pos.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-cash-register w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Point-Of-Sale (POS)</span>
          </a>
        </li>
      </ul>
        
      <!-- Reports & Analytics -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Reports & Analytics</h5>
      </div>
      <ul class="list-none p-0 mb-6">
        <li>
          <a href="employee_expenses.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-money-bill-wave w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Expenses</span>
          </a>
        </li>
        <li>
          <a href="employee_history.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-history w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Service History</span>
          </a>
        </li>
      </ul>
        
      <!-- Services & Staff -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Communication</h5>
      </div>
      <ul class="list-none p-0 mb-6">
          <a href="employee_chat.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover">
            <i class="fas fa-comments w-5 text-center mr-3 text-sidebar-accent"></i>
            <span>Chats</span>
          </a>
        </li>
      </ul>
        
      <!-- Account -->
      <div class="px-5 mb-2 py-2 menu-header">
        <h5 class="text-xs font-medium text-sidebar-accent uppercase tracking-wider">Account</h5>
      </div>
      <ul class="list-none p-0">
        <li>
          <a href="..\logout.php" class="sidebar-link flex items-center px-5 py-3 text-sidebar-text opacity-80 hover:opacity-100 no-underline transition-all duration-300 hover:bg-sidebar-hover hover:text-error">
            <i class="fas fa-sign-out-alt w-5 text-center mr-3 text-error"></i>
            <span>Logout</span>
          </a>
        </li>
      </ul>
    </div>
    
    <!-- Footer -->
    <div class="relative bottom-0 left-0 right-0 px-5 py-3 border-t border-sidebar-border bg-gradient-to-r from-navy to-primary">
      <div class="flex justify-between items-center">
        <p class="text-xs text-sidebar-text opacity-60">© 2025 GrievEase</p>
        <div class="text-xs text-sidebar-accent">
          <i class="fas fa-heart"></i> With Compassion
        </div>
      </div>
    </div>
  </nav>

  <!-- Main Content -->
  <div id="main-content" class="ml-64 p-6 bg-gray-50 min-h-screen transition-all duration-300 main-content">
    <!-- Header with breadcrumb and welcome message -->
    <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
      <div>
        <h1 class="text-2xl font-bold text-sidebar-text">Expense Tracking</h1>
      </div>
      <div class="flex space-x-3">
        <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
          <i class="fas fa-bell"></i>
        </button>
      </div>
    </div>

    <!-- Analytics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <?php
        // Card data array for consistent styling
        $cards = [
            [
                'title' => 'Total Expenses',
                'value' => number_format($total_expenses, 2),
                'change' => '8% from last month',
                'change_class' => 'text-green-600',
                'change_icon' => 'fa-arrow-up',
                'icon' => 'peso-sign',
                'color' => 'blue',
                'prefix' => '₱',
                'suffix' => '',
                'extra_content' => ''
            ],
            [
                'title' => 'This Month',
                'value' => number_format($monthly_expenses, 2),
                'change' => '12% from last month',
                'change_class' => 'text-green-600',
                'change_icon' => 'fa-arrow-up',
                'icon' => 'chart-line',
                'color' => 'green',
                'prefix' => '₱',
                'suffix' => '',
                'extra_content' => ''
            ],
            [
                'title' => 'Pending Payments',
                'value' => $pending_payments,
                'change' => '3% from last month',
                'change_class' => 'text-red-600',
                'change_icon' => 'fa-arrow-down',
                'icon' => 'exclamation-triangle',
                'color' => 'orange',
                'prefix' => '',
                'suffix' => '',
                'extra_content' => ''
            ]
        ];
        
        // Render cards
        foreach ($cards as $card) {
        ?>
        
        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
            <!-- Card header with gradient background -->
            <div class="bg-gradient-to-r from-<?php echo $card['color']; ?>-100 to-<?php echo $card['color']; ?>-200 p-3">
                <div class="flex items-center justify-between mb-1">
                    <div class="flex-grow">
                        <h3 class="text-sm font-medium text-gray-700"><?php echo $card['title']; ?></h3>
                        <?php if (isset($card['sub_text']) && !empty($card['sub_text'])): ?>
                        <div class="text-xs text-gray-500"><?php echo $card['sub_text']; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="w-8 h-8 rounded-full bg-white/90 text-<?php echo $card['color']; ?>-600 flex items-center justify-center ml-2 flex-shrink-0">
                        <i class="fas fa-<?php echo $card['icon']; ?> text-sm"></i>
                    </div>
                </div>
                <div class="flex items-end">
                    <span class="text-xl md:text-2xl font-bold <?php echo isset($card['warning_class']) ? $card['warning_class'] : 'text-gray-800'; ?>">
                        <?php echo $card['prefix'] . $card['value'] . $card['suffix']; ?>
                    </span>
                </div>
            </div>
            
            <!-- Extra content if any -->
            <?php if (!empty($card['extra_content'])): ?>
            <div class="px-3 py-2 bg-white border-t border-gray-50">
                <?php echo $card['extra_content']; ?>
            </div>
            <?php endif; ?>
            
            <!-- Card footer with change indicator -->
            <?php if (isset($card['change']) && !empty($card['change'])): ?>
            <div class="px-3 py-2 bg-white border-t border-gray-50 text-xs">
                <div class="flex items-center <?php echo $card['change_class']; ?>">
                    <i class="fas <?php echo $card['change_icon']; ?> mr-1"></i>
                    <span><?php echo $card['change']; ?></span>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php } ?>
    </div>

    <!-- Expenses Table Card -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden branch-expense-container">
    <!-- Branch Header with Search and Filters - Made responsive with better stacking -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
        <!-- Desktop layout for big screens - Title on left, controls on right -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
            <!-- Title and Counter -->
            <div class="flex items-center gap-3 mb-4 lg:mb-0">
                <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Expenses</h4>
                
                <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                    <?php echo $total_items . ($total_items != 1 ? " items" : " item"); ?>
                </span>
            </div>
            
            <!-- Controls for big screens - aligned right -->
            <div class="hidden lg:flex items-center gap-3">
                <!-- Search Input -->
                <div class="relative">
                    <form id="searchForm" method="GET" class="relative">
                        <input type="text" 
                               name="search" 
                               placeholder="Search expenses..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </form>
                </div>

                <!-- Filter Dropdown -->
                <div class="relative filter-dropdown">
                    <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover"
                            onclick="toggleFilter()">
                        <i class="fas fa-filter text-sidebar-accent"></i>
                        <span>Filters</span>
                        <?php if($category_filter || $status_filter): ?>
                            <span class="h-2 w-2 bg-sidebar-accent rounded-full"></span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Filter Window -->
                    <div id="filterDropdown" class="filter-content absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border hidden p-4">
    <div class="space-y-4">
        <!-- Category Filter -->
        <div>
            <h5 class="text-sm font-medium text-sidebar-text mb-2">Category</h5>
            <select name="category" class="w-full px-3 py-2 border border-sidebar-border rounded-md text-sm">
                <option value="">All Categories</option>
                <option value="Supplies" <?php echo $category_filter === 'Supplies' ? 'selected' : ''; ?>>Supplies</option>
                <option value="Utilities" <?php echo $category_filter === 'Utilities' ? 'selected' : ''; ?>>Utilities</option>
                <option value="Salaries" <?php echo $category_filter === 'Salaries' ? 'selected' : ''; ?>>Salaries</option>
                <option value="Maintenance" <?php echo $category_filter === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                <option value="Other" <?php echo $category_filter === 'Other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>
        
        <!-- Status Filter -->
        <div>
            <h5 class="text-sm font-medium text-sidebar-text mb-2">Status</h5>
            <select name="status" class="w-full px-3 py-2 border border-sidebar-border rounded-md text-sm">
                <option value="">All Statuses</option>
                <option value="Paid" <?php echo $status_filter === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
            </select>
        </div>
        
        <button type="button" onclick="applyFilters()" class="w-full px-4 py-2 bg-sidebar-accent text-white rounded-md text-sm hover:bg-darkgold transition-all duration-300">
            Apply Filters
        </button>
    </div>
</div>
                </div>

                <!-- Add Expense Button -->
                <button class="px-4 py-2 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap" 
                        onclick="openAddExpenseModal()">
                    <i class="fas fa-plus mr-2"></i>
                    <span>Add Expense</span>
                </button>
            </div>
        </div>
        
        <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
        <div class="lg:hidden w-full mt-4">
            <!-- First row: Search bar with filter icons on the right -->
            <div class="flex items-center w-full gap-3 mb-4">
                <!-- Search Input - Takes most of the space -->
                <div class="relative flex-grow">
                    <form id="mobileSearchForm" method="GET" class="relative w-full">
                        <input type="text" 
                               name="search" 
                               placeholder="Search expenses..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                        <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                    </form>
                </div>

                <!-- Icon-only button for filter -->
                <div class="flex items-center gap-3">
                    <!-- Filter Icon Button -->
                    <div class="relative filter-dropdown">
                        <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent" onclick="toggleFilter()">
                            <i class="fas fa-filter text-xl"></i>
                            <span class="<?php echo ($category_filter || $status_filter) ? '' : 'hidden'; ?> absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Second row: Add Expense Button - Full width -->
            <div class="w-full">
                <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap w-full justify-center" 
                        onclick="openAddExpenseModal()">
                    <i class="fas fa-plus mr-2"></i>
                    <span>Add Expense</span>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Responsive Table Container with improved spacing -->
    <div class="overflow-x-auto scrollbar-thin">
        <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
        <div class="min-w-full">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-sidebar-border">
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('expense_ID')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-hashtag text-sidebar-accent"></i> ID 
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('expense_name')">
                            <div class="flex items-center gap-1.5">
                                <i class="fa-solid fa-file-invoice text-sidebar-accent"></i> Expense Name 
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('category')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-th-list text-sidebar-accent"></i> Category 
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('price')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-peso-sign text-sidebar-accent"></i> Amount 
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('date')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-calendar-alt text-sidebar-accent"></i> Date 
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
                            </div>
                        </th>
                        <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable('status')">
                            <div class="flex items-center gap-1.5">
                                <i class="fas fa-check-circle text-sidebar-accent"></i> Status 
                                <i class="fas fa-sort ml-1 text-gray-400"></i>
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
                    <?php if (count($expenses) > 0): ?>
                        <?php foreach ($expenses as $expense): ?>
                            <?php
                            $statusClass = $expense['status'] === 'Paid' 
                                ? "bg-green-100 text-green-600 border border-green-200" 
                                : "bg-orange-100 text-orange-500 border border-orange-200";
                            $statusIcon = $expense['status'] === 'Paid' ? "fa-check-circle" : "fa-clock";
                            ?>
                            <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                                <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#<?php echo htmlspecialchars($expense['expense_ID']); ?></td>
                                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($expense['expense_name']); ?></td>
                                <td class="px-4 py-3.5 text-sm text-sidebar-text">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                        <?php echo htmlspecialchars($expense['category']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">₱<?php echo number_format($expense['price'], 2); ?></td>
                                <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo date('Y-m-d', strtotime($expense['date'])); ?></td>
                                <td class="px-4 py-3.5 text-sm">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                        <i class="fas <?php echo $statusIcon; ?> mr-1"></i> <?php echo htmlspecialchars($expense['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-sm">
                                    <div class="flex space-x-2">
                                                                                <button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" title="Edit Expense"                                                 onclick="openEditExpenseModal(                                                    '<?php echo htmlspecialchars($expense['expense_ID']); ?>',                                                    '<?php echo htmlspecialchars(addslashes($expense['expense_name'])); ?>',                                                    '<?php echo htmlspecialchars($expense['category']); ?>',                                                    '<?php echo htmlspecialchars($expense['price']); ?>',                                                    '<?php echo htmlspecialchars($expense['date']); ?>',                                                    '<?php echo htmlspecialchars($expense['status']); ?>',                                                    '<?php echo htmlspecialchars(addslashes($expense['notes'] ?? '')); ?>'                                                )">                                            <i class="fas fa-edit"></i>                                        </button>
                                        <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip" title="Archive Expense" 
                                                onclick="confirmArchive('<?php echo $expense['expense_ID']; ?>')">
                                            <i class="fas fa-archive"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-sm text-center">
                                <div class="flex flex-col items-center">
                                    <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                                    <p class="text-gray-500">No expenses found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Sticky Pagination Footer with improved spacing -->
    <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
        <div class="text-sm text-gray-500 text-center sm:text-left">
    Showing <?php echo ($offset + 1) . ' to ' . min($offset + $items_per_page, $total_items); ?> of <?php echo $total_items; ?> expenses
</div>
        <div class="flex space-x-2">
            <?php if ($total_pages > 1): ?>
                <!-- First page button (double arrow) -->
                <a href="?page=1&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>" 
                   class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                    &laquo;
                </a>
                
                <!-- Previous page button (single arrow) -->
                <a href="?page=<?php echo max(1, $current_page - 1); ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>" 
                   class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                    &lsaquo;
                </a>
                
                <?php
                // Show exactly 3 page numbers
                if ($total_pages <= 3) {
                    // If total pages is 3 or less, show all pages
                    $start_page = 1;
                    $end_page = $total_pages;
                } else {
                    // With more than 3 pages, determine which 3 to show
                    if ($current_page == 1) {
                        // At the beginning, show first 3 pages
                        $start_page = 1;
                        $end_page = 3;
                    } elseif ($current_page == $total_pages) {
                        // At the end, show last 3 pages
                        $start_page = $total_pages - 2;
                        $end_page = $total_pages;
                    } else {
                        // In the middle, show current page with one before and after
                        $start_page = $current_page - 1;
                        $end_page = $current_page + 1;
                        
                        // Handle edge cases
                        if ($start_page < 1) {
                            $start_page = 1;
                            $end_page = 3;
                        }
                        if ($end_page > $total_pages) {
                            $end_page = $total_pages;
                            $start_page = $total_pages - 2;
                        }
                    }
                }
                
                // Generate the page buttons
                for ($i = $start_page; $i <= $end_page; $i++) {
                    $active_class = ($i == $current_page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
                    echo '<a href="?page=' . $i . '&search=' . urlencode($search) . '&category=' . urlencode($category_filter) . '&status=' . urlencode($status_filter) . '&sort=' . $sort_by . '&order=' . $sort_order . '" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</a>';
                }
                ?>
                
                <!-- Next page button (single arrow) -->
                <a href="?page=<?php echo min($total_pages, $current_page + 1); ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>" 
                   class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                    &rsaquo;
                </a>
                
                <!-- Last page button (double arrow) -->
                <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>" 
                   class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                    &raquo;
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

          </div>

    <!-- Add Expense Modal -->
<div id="addExpenseModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeAddExpenseModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Add New Expense
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <form id="expenseForm" class="space-y-3 sm:space-y-4">
        <input type="hidden" id="expenseBranch" name="branch_id" value="<?php echo $branch?>">
        
        <!-- Basic Information -->
        <div>
          <label for="expenseDescription" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Expense Name
          </label>
          <div class="relative">
            <select id="expenseNameDropdown" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" onchange="handleExpenseNameChange(this)">
              <option value="" disabled selected>Select common expense</option>
              <option value="Rent">Rent</option>
              <option value="Electricity">Electricity</option>
              <option value="Water">Water</option>
              <option value="Internet">Internet</option>
              <option value="Salaries">Salaries</option>
              <option value="Office Supplies">Office Supplies</option>
              <option value="Maintenance">Maintenance</option>
              <option value="Marketing">Marketing</option>
              <option value="Insurance">Insurance</option>
              <option value="Taxes">Taxes</option>
              <option value="Other">Other (specify)</option>
            </select>
            <input type="text" id="expenseDescription" name="expenseDescription" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200 mt-2 hidden" 
                   oninput="formatExpenseName(this)" 
                   onkeydown="preventDoubleSpace(event)" 
                   placeholder="Enter expense description"
                   required>
          </div>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
          <div class="w-full sm:flex-1">
            <label for="expenseCategory" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Category
            </label>
            <div class="relative">
              <select id="expenseCategory" name="expenseCategory" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                <option value="" disabled selected>Select category</option>
                <option value="Supplies">Supplies</option>
                <option value="Utilities">Utilities</option>
                <option value="Salaries">Salaries</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
          
          <div class="w-full sm:flex-1">
            <label for="expenseDate" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Date
            </label>
            <div class="relative">
              <input type="date" id="expenseDate" name="expenseDate" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
            </div>
          </div>
        </div>
        
        <div>
          <label for="expenseAmount" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Amount
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="expenseAmount" name="expenseAmount" min="0.01" step="0.01" placeholder="0.00" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
          </div>
        </div>
        
        <!-- Status -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Status
          </label>
          <div class="grid grid-cols-2 gap-2">
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="statusPaid" name="expenseStatus" value="paid" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent" checked onchange="updateDateLimits()">
              Paid
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="statusToBePaid" name="expenseStatus" value="To be paid" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent" onchange="updateDateLimits()">
              To Be Paid
            </label>
          </div>
        </div>
        
        <!-- Payment Method -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Payment Method
          </label>
          <div class="grid grid-cols-2 gap-2">
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="methodCash" name="paymentMethod" value="cash" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent" checked>
              Cash
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="methodCredit" name="paymentMethod" value="credit" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              Credit Card
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="methodTransfer" name="paymentMethod" value="transfer" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              Bank Transfer
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" id="methodOther" name="paymentMethod" value="other" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              Other
            </label>
          </div>
        </div>
        
        <!-- Note -->
        <div>
          <label for="expenseNotes" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Note <span class="text-xs text-gray-500">(Optional)</span>
          </label>
          <div class="relative">
            <textarea id="expenseNotes" name="expenseNotes" rows="3" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                      oninput="formatNote(this)" 
                      onkeydown="preventDoubleSpace(event)"
                      placeholder="Add any additional details here"></textarea>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeAddExpenseModal()">
        Cancel
      </button>
      <button type="button" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="addExpense()">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
          <path d="M12 5v14"></path>
          <path d="M5 12h14"></path>
        </svg>
        Add Expense
      </button>
    </div>
  </div>
</div>

    <!-- Edit Expense Modal -->
    <div id="editExpenseModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
      <!-- Modal Backdrop -->
      <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
      
      <!-- Modal Content -->
      <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
        <!-- Close Button -->
        <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeEditExpenseModal()">
          <i class="fas fa-times"></i>
        </button>
        
        <!-- Modal Header -->
        <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
          <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
            Edit Expense
          </h3>
        </div>
        
        <!-- Modal Body -->
        <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
          <form id="editExpenseForm" class="space-y-3 sm:space-y-4">
            <input type="hidden" id="editExpenseId" name="editExpenseId">
            
            <!-- Expense Name -->
            <div>
              <label for="editExpenseDescription" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Expense Name
              </label>
              <div class="relative">
                <select id="editExpenseNameDropdown" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" onchange="handleEditExpenseNameChange(this)">
                  <option value="" disabled selected>Select common expense</option>
                  <option value="Rent">Rent</option>
                  <option value="Electricity">Electricity</option>
                  <option value="Water">Water</option>
                  <option value="Internet">Internet</option>
                  <option value="Salaries">Salaries</option>
                  <option value="Office Supplies">Office Supplies</option>
                  <option value="Maintenance">Maintenance</option>
                  <option value="Marketing">Marketing</option>
                  <option value="Insurance">Insurance</option>
                  <option value="Taxes">Taxes</option>
                  <option value="Other">Other (specify)</option>
                </select>
                <input type="text" id="editExpenseDescription" name="editExpenseDescription" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200 mt-2 hidden" 
                       oninput="formatExpenseName(this)" 
                       onkeydown="preventDoubleSpace(event)" 
                       placeholder="Enter expense description"
                       required>
              </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
              <div class="w-full sm:flex-1">
                <label for="editExpenseCategory" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Category
                </label>
                <div class="relative">
                  <select id="editExpenseCategory" name="editExpenseCategory" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                    <option value="Supplies">Supplies</option>
                    <option value="Utilities">Utilities</option>
                    <option value="Salaries">Salaries</option>
                    <option value="Maintenance">Maintenance</option>
                    <option value="Other">Other</option>
                  </select>
                </div>
              </div>
              
              <div class="w-full sm:flex-1">
                <label for="editExpenseDate" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                  Date
                </label>
                <div class="relative">
                  <input type="date" id="editExpenseDate" name="editExpenseDate" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
                </div>
              </div>
            </div>
            
            <div>
              <label for="editExpenseAmount" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Amount
              </label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">₱</span>
                </div>
                <input type="number" id="editExpenseAmount" name="editExpenseAmount" min="0.01" step="0.01" placeholder="0.00" class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" required>
              </div>
            </div>
            
            <!-- Status -->
            <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
              <label class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Status
              </label>
              <div class="grid grid-cols-2 gap-2">
                <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                  <input type="radio" id="editStatusPaid" name="editExpenseStatus" value="paid" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent" onchange="updateEditDateLimits()">
                  Paid
                </label>
                <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
                  <input type="radio" id="editStatusToBePaid" name="editExpenseStatus" value="To be paid" class="mr-2 text-sidebar-accent focus:ring-sidebar-accent" onchange="updateEditDateLimits()">
                  To Be Paid
                </label>
              </div>
            </div>
            
            <!-- Note -->
            <div>
              <label for="editExpenseNotes" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
                Note <span class="text-xs text-gray-500">(Optional)</span>
              </label>
              <div class="relative">
                <textarea id="editExpenseNotes" name="editExpenseNotes" rows="3" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                          oninput="formatNote(this)" 
                          onkeydown="preventDoubleSpace(event)"
                          placeholder="Add any additional details here"></textarea>
              </div>
            </div>
          </form>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
          <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeEditExpenseModal()">
            Cancel
          </button>
          <button type="button" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="saveExpenseChanges()">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            Save Changes
          </button>
        </div>
      </div>
    </div>

    <!-- Archive Modal -->
    <div id="archiveModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
      <!-- Modal Backdrop -->
      <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
      
      <!-- Modal Content -->
      <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
        <!-- Close Button -->
        <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700 transition-colors" onclick="closeArchiveModal()">
          <i class="fas fa-times"></i>
        </button>
        
        <!-- Modal Header -->
        <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
          <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
            <i class="fas fa-archive mr-2"></i> Archived Expenses
          </h3>
        </div>
        
        <!-- Modal Body -->
        <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expense Name</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                  <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody id="archivedExpensesTableBody" class="bg-white divide-y divide-gray-200">
                <!-- Archived expenses will be loaded here -->
              </tbody>
            </table>
          </div>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-4 sm:px-6 py-3 sm:py-4 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
          <button type="button" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors" onclick="closeArchiveModal()">
            Close
          </button>
        </div>
      </div>
    </div>

    <!-- Archive Confirm Modal -->
    <div class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="archiveConfirmModal">
      <div class="bg-white rounded-xl w-full max-w-md mx-4 shadow-xl">
        <div class="p-6">
          <div class="flex items-center mb-4">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
              <i class="fas fa-exclamation text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-800">Archive Expense</h3>
          </div>
          <p class="text-gray-600 mb-6">Are you sure you want to archive expense <span id="archiveExpenseId" class="font-semibold"></span>? This action cannot be undone.</p>
          <div class="flex justify-end gap-3">
            <button class="px-5 py-2 bg-white border border-gray-300 text-gray-800 rounded-lg font-medium hover:bg-gray-50 transition-colors" onclick="closeArchiveConfirmModal()">Cancel</button>
            <button class="px-5 py-2 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition-colors" onclick="archiveExpense()">Archive</button>
          </div>
        </div>
      </div>
    </div>

    

  
    <script>
      // Initialize current date for date inputs
      document.addEventListener('DOMContentLoaded', function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('expenseDate').value = today;
      });
      
      // Function to toggle sidebar
      function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('collapsed');
      }
      
      // Function to toggle filter dropdown
      function toggleFilter() {
        document.getElementById('filterDropdown').classList.toggle('hidden');
      }
      
      function applyFilters() {
    submitSearch();
}

      // Function to sort table
      function sortTable(column) {
        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get('sort');
        const currentOrder = url.searchParams.get('order');
        
        let newOrder = 'ASC';
        if (currentSort === column && currentOrder === 'ASC') {
          newOrder = 'DESC';
        }
        
        url.searchParams.set('sort', column);
        url.searchParams.set('order', newOrder);
        window.location.href = url.toString();
      }

      // Function to open the Add Expense Modal
      function openAddExpenseModal() {
        document.getElementById('addExpenseModal').style.display = 'flex';
        document.getElementById('expenseDescription').focus();
      }

      // Function to close the Add Expense Modal
      function closeAddExpenseModal() {
        document.getElementById('addExpenseModal').style.display = 'none';
        document.getElementById('expenseForm').reset();
      }

      // Function to add an expense (AJAX implementation would go here)
      function addExpense() {
          const form = document.getElementById('expenseForm');
          if (form.checkValidity()) {
              // Submit the form via AJAX
              const formData = new FormData(form);
              
              fetch('expenses/add_expense_handler.php', {
                  method: 'POST',
                  body: formData
              })
              .then(response => response.text())
              .then(() => {
                  // On success, show SweetAlert notification
                  showNotification('Expense added successfully!');
                  closeAddExpenseModal();
                  // Optional: reload the page after a delay
                  setTimeout(() => {
                      window.location.reload();
                  }, 1500);
              })
              .catch(error => {
                  console.error('Error:', error);
                  showNotification('Error adding expense. Please try again.', false);
              });
          } else {
              form.reportValidity();
          }
      }

      // Add this to your script section
document.addEventListener('DOMContentLoaded', function() {
    // Handle search form submission
    const searchForm = document.getElementById('searchForm');
    const mobileSearchForm = document.getElementById('mobileSearchForm');
    
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitSearch();
        });
    }
    
    if (mobileSearchForm) {
        mobileSearchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitSearch();
        });
    }
    
    // Add event listener for search input changes with debounce
    const searchInput = document.querySelector('input[name="search"]');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                submitSearch();
            }, 500); // 500ms debounce
        });
    }
});

function submitSearch() {
    const url = new URL(window.location.href);
    const searchInput = document.querySelector('input[name="search"]');
    const categoryFilter = document.querySelector('select[name="category"]');
    const statusFilter = document.querySelector('select[name="status"]');
    
    // Update URL parameters
    if (searchInput && searchInput.value) {
        url.searchParams.set('search', searchInput.value);
    } else {
        url.searchParams.delete('search');
    }
    
    if (categoryFilter && categoryFilter.value) {
        url.searchParams.set('category', categoryFilter.value);
    } else {
        url.searchParams.delete('category');
    }
    
    if (statusFilter && statusFilter.value) {
        url.searchParams.set('status', statusFilter.value);
    } else {
        url.searchParams.delete('status');
    }
    
    // Reset to page 1 when searching
    url.searchParams.set('page', '1');
    
    window.location.href = url.toString();
}

      // Function to open the Edit Expense Modal
      function openEditExpenseModal(id, description, category, amount, date, status, notes) {
        document.getElementById('editExpenseId').value = id;
        
        // Handle expense name - check if it's in the dropdown
        const dropdown = document.getElementById('editExpenseNameDropdown');
        const expenseInput = document.getElementById('editExpenseDescription');
        let foundInDropdown = false;
        
        for (let i = 0; i < dropdown.options.length; i++) {
            if (dropdown.options[i].value === description) {
                dropdown.selectedIndex = i;
                expenseInput.classList.add('hidden');
                expenseInput.value = description;
                foundInDropdown = true;
                break;
            }
        }
        
        if (!foundInDropdown) {
            dropdown.value = 'Other';
            expenseInput.classList.remove('hidden');
            expenseInput.value = description;
        }
        
        document.getElementById('editExpenseCategory').value = category;
        document.getElementById('editExpenseAmount').value = amount;
        document.getElementById('editExpenseDate').value = date;
        
        // Set status radio button
        if (status.toLowerCase() === 'paid') {
            document.getElementById('editStatusPaid').checked = true;
        } else {
            document.getElementById('editStatusToBePaid').checked = true;
        }
        
        document.getElementById('editExpenseNotes').value = notes || '';
        
        // Show the modal
        document.getElementById('editExpenseModal').style.display = 'flex';
      }

      // Function to close the Edit Expense Modal
      function closeEditExpenseModal() {
        document.getElementById('editExpenseModal').style.display = 'none';
      }

      // Function to save changes to an expense
      function saveExpenseChanges() {
          const form = document.getElementById('editExpenseForm');
          if (form.checkValidity()) {
              // Submit the form via AJAX
              const formData = new FormData(form);
              
              fetch('expenses/save_expense_changes_handler.php', {
                  method: 'POST',
                  body: formData
              })
              .then(response => response.json())
              .then(data => {
                  if (data.success) {
                      showNotification(data.message);
                      closeEditExpenseModal();
                      // Reload the page after a delay
                      setTimeout(() => {
                          window.location.reload();
                      }, 1500);
                  } else {
                      showNotification(data.message, false);
                  }
              })
              .catch(error => {
                  console.error('Error:', error);
                  showNotification('Error updating expense. Please try again.', false);
              });
          } else {
              form.reportValidity();
          }
      }
      
      // Function to confirm archive
      function confirmArchive(id) {
        document.getElementById('archiveExpenseId').textContent = id;
        document.getElementById('archiveConfirmModal').style.display = 'flex';
      }
      
      // Function to close archive confirmation modal
      function closeArchiveConfirmModal() {
        document.getElementById('archiveConfirmModal').style.display = 'none';
      }

      // Function to archive an expense
      function archiveExpense() {
          const expenseId = document.getElementById('archiveExpenseId').textContent;
          
          // Create form data
          const formData = new FormData();
          formData.append('expense_id', expenseId);
          
          // Send AJAX request
          fetch('expenses/archive_expense_handler.php', {
              method: 'POST',
              body: formData
          })
          .then(response => response.json())
          .then(data => {
              if (data.success) {
                  showNotification(data.message);
                  closeArchiveConfirmModal();
                  // Reload the page after a delay
                  setTimeout(() => {
                      window.location.reload();
                  }, 1500);
              } else {
                  showNotification(data.message, false);
              }
          })
          .catch(error => {
              console.error('Error:', error);
              showNotification('Error archiving expense. Please try again.', false);
          });
      }
      
      // Function to show notification
      function showNotification(message, isSuccess = true) {
        Swal.fire({
          title: isSuccess ? 'Success!' : 'Error!',
          text: message,
          icon: isSuccess ? 'success' : 'error',
          confirmButtonColor: '#CA8A04',
          timer: 3000,
          timerProgressBar: true,
          toast: true,
          position: 'top-end',
          showConfirmButton: false
        });
      }

      // Function to handle expense name dropdown change
      function handleExpenseNameChange(select) {
          const expenseInput = document.getElementById('expenseDescription');
          if (select.value === 'Other') {
              expenseInput.classList.remove('hidden');
              expenseInput.value = '';
              expenseInput.focus();
          } else {
              expenseInput.classList.add('hidden');
              expenseInput.value = select.value;
          }
      }

      // Function to handle edit expense name dropdown change
      function handleEditExpenseNameChange(select) {
          const expenseInput = document.getElementById('editExpenseDescription');
          if (select.value === 'Other') {
              expenseInput.classList.remove('hidden');
              expenseInput.value = '';
              expenseInput.focus();
          } else {
              expenseInput.classList.add('hidden');
              expenseInput.value = select.value;
          }
      }

      // Function to format expense name (capitalize first letter)
      function formatExpenseName(input) {
          input.value = input.value.replace(/[^a-zA-Z0-9\s]/g, '');
          input.value = input.value.charAt(0).toUpperCase() + input.value.slice(1);
      }

      // Function to format note
      function formatNote(textarea) {
          textarea.value = textarea.value.replace(/[^a-zA-Z0-9\s.,!?-]/g, '');
      }

      // Function to prevent double spaces
      function preventDoubleSpace(event) {
          if (event.key === ' ' && event.target.value.slice(-1) === ' ') {
              event.preventDefault();
          }
      }

      // Function to update date limits based on status
      function updateDateLimits() {
          const dateInput = document.getElementById('expenseDate');
          const statusPaid = document.getElementById('statusPaid').checked;
          
          if (statusPaid) {
              dateInput.max = new Date().toISOString().split('T')[0];
          } else {
              dateInput.removeAttribute('max');
          }
      }

      // Function to update edit date limits based on status
      function updateEditDateLimits() {
          const dateInput = document.getElementById('editExpenseDate');
          const statusPaid = document.getElementById('editStatusPaid').checked;
          
          if (statusPaid) {
              dateInput.max = new Date().toISOString().split('T')[0];
          } else {
              dateInput.removeAttribute('max');
          }
      }
    </script>
    <script src="tailwind.js"></script>
    <script src="sidebar.js"></script>
  </div>
</body>
</html>

<?php
// Helper function to get category color class
function getCategoryColorClass($category) {
  switch ($category) {
    case 'Supplies':
      return 'bg-blue-100 text-blue-800';
    case 'Utilities':
      return 'bg-green-100 text-green-800';
    case 'Salaries':
      return 'bg-purple-100 text-purple-800';
    case 'Maintenance':
      return 'bg-yellow-100 text-yellow-800';
    case 'Other':
      return 'bg-gray-100 text-gray-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}
?>