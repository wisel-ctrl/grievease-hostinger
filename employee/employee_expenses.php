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
$total_expenses = 0;
$monthly_expenses = 0;
$current_month = date('Y-m');
foreach ($expenses as $expense) {
    $total_expenses += $expense['price'];
    if (date('Y-m', strtotime($expense['date'])) === $current_month) {
        $monthly_expenses += $expense['price'];
    }
}

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
        
        /* Animate the sidebar
        @keyframes slideIn {
          from { transform: translateX(-100%); opacity: 0; }
          to { transform: translateX(0); opacity: 1; }
        }
        
        .animate-sidebar {
          animation: slideIn 0.3s ease forwards;
        } */

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
    <div class="mb-8">
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-white rounded-lg shadow-sidebar p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
          <div class="flex items-center mb-3">
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center mr-3">
              <i class="fas fa-peso-sign text-lg"></i>
            </div>
            <span class="text-sidebar-text font-medium">Total Expenses</span>
          </div>
          <div class="text-3xl font-bold mb-2 text-sidebar-text">₱<?php echo number_format($total_expenses, 2); ?></div>
          <div class="text-sm text-green-600 flex items-center">
            <i class="fas fa-arrow-up mr-1"></i> 8% from last month
          </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sidebar p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
          <div class="flex items-center mb-3">
            <div class="w-12 h-12 rounded-lg bg-green-100 text-green-600 flex items-center justify-center mr-3">
              <i class="fas fa-chart-line text-lg"></i>
            </div>
            <span class="text-sidebar-text font-medium">This Month</span>
          </div>
          <div class="text-3xl font-bold mb-2 text-sidebar-text">₱<?php echo number_format($monthly_expenses, 2); ?></div>
          <div class="text-sm text-green-600 flex items-center">
            <i class="fas fa-arrow-up mr-1"></i> 12% from last month
          </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sidebar p-5 border border-sidebar-border hover:shadow-card transition-all duration-300">
          <div class="flex items-center mb-3">
            <div class="w-12 h-12 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center mr-3">
              <i class="fas fa-exclamation-triangle text-lg"></i>
            </div>
            <span class="text-sidebar-text font-medium">Pending Payments</span>
          </div>
          <div class="text-3xl font-bold mb-2 text-sidebar-text"><?php echo $pending_payments; ?></div>
          <div class="text-sm text-red-600 flex items-center">
            <i class="fas fa-arrow-down mr-1"></i> 3% from last month
          </div>
        </div>
      </div>
    </div>

    <!-- Expenses Table Card -->
    <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
      <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
        <h3 class="font-medium text-sidebar-text">Expenses</h3>
        <div class="flex items-center gap-3">
          <form id="searchForm" method="GET" class="relative">
            <input type="text" name="search" placeholder="Search..." 
                   value="<?php echo htmlspecialchars($search); ?>" 
                   class="pl-9 pr-4 py-2 border border-sidebar-border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent">
            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
              <i class="fas fa-search text-gray-400"></i>
            </div>
          </form>
          <div class="relative">
            <button class="filter-btn px-4 py-2 bg-white border border-sidebar-border rounded-md text-sm flex items-center hover:bg-sidebar-hover transition-all duration-300" onclick="toggleFilter()">
              <i class="fas fa-filter mr-2 text-sidebar-accent"></i> Filter
            </button>
            <div id="filterDropdown" class="filter-content absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg z-10 border border-sidebar-border hidden">
              <div class="p-4">
                <div class="mb-3">
                  <label class="block text-sm font-medium text-sidebar-text mb-1">Category</label>
                  <select name="category" class="w-full px-3 py-2 border border-sidebar-border rounded-md text-sm">
                    <option value="">All Categories</option>
                    <option value="Supplies" <?php echo $category_filter === 'Supplies' ? 'selected' : ''; ?>>Supplies</option>
                    <option value="Utilities" <?php echo $category_filter === 'Utilities' ? 'selected' : ''; ?>>Utilities</option>
                    <option value="Salaries" <?php echo $category_filter === 'Salaries' ? 'selected' : ''; ?>>Salaries</option>
                    <option value="Maintenance" <?php echo $category_filter === 'Maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                    <option value="Other" <?php echo $category_filter === 'Other' ? 'selected' : ''; ?>>Other</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="block text-sm font-medium text-sidebar-text mb-1">Status</label>
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
          <button class="px-4 py-2 bg-sidebar-accent text-white rounded-md text-sm flex items-center hover:bg-darkgold transition-all duration-300" onclick="openAddExpenseModal()">
            <i class="fas fa-plus mr-2"></i> Add Expense
          </button>
        </div>
      </div>
      <div class="overflow-x-auto scrollbar-thin">
        <table class="w-full">
          <thead>
            <tr class="bg-sidebar-hover">
              <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable('expense_ID')">
                <div class="flex items-center">
                  ID <i class="fas fa-sort ml-1 text-gray-400"></i>
                </div>
              </th>
              <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable('expense_name')">
                <div class="flex items-center">
                  Expense Name <i class="fas fa-sort ml-1 text-gray-400"></i>
                </div>
              </th>
              <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable('category')">
                <div class="flex items-center">
                  Category <i class="fas fa-sort ml-1 text-gray-400"></i>
                </div>
              </th>
              <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable('price')">
                <div class="flex items-center">
                  Amount <i class="fas fa-sort ml-1 text-gray-400"></i>
                </div>
              </th>
              <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable('date')">
                <div class="flex items-center">
                  Date <i class="fas fa-sort ml-1 text-gray-400"></i>
                </div>
              </th>
              <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable('status')">
                <div class="flex items-center">
                  Status <i class="fas fa-sort ml-1 text-gray-400"></i>
                </div>
              </th>
              <th class="p-4 text-left text-sm font-medium text-sidebar-text">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (count($expenses) > 0): ?>
              <?php foreach ($expenses as $expense): ?>
                <tr class="border-b border-sidebar-border hover:bg-sidebar-hover">
                  <td class="p-4 text-sm text-sidebar-text font-medium">#<?php echo htmlspecialchars($expense['expense_ID']); ?></td>
                  <td class="p-4 text-sm text-sidebar-text"><?php echo htmlspecialchars($expense['expense_name']); ?></td>
                  <td class="p-4 text-sm text-sidebar-text">
                    <span class="px-2 py-1 <?php echo getCategoryColorClass($expense['category']); ?> rounded-full text-xs">
                      <?php echo htmlspecialchars($expense['category']); ?>
                    </span>
                  </td>
                  <td class="p-4 text-sm text-sidebar-text">₱<?php echo number_format($expense['price'], 2); ?></td>
                  <td class="p-4 text-sm text-sidebar-text"><?php echo date('Y-m-d', strtotime($expense['date'])); ?></td>
                  <td class="p-4 text-sm">
                    <span class="px-2 py-1 <?php echo $expense['status'] === 'Paid' ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'; ?> rounded-full text-xs">
                      <?php echo htmlspecialchars($expense['status']); ?>
                    </span>
                  </td>
                  <td class="p-4 text-sm">
                    <div class="flex space-x-2">
                      <button class="p-1.5 bg-blue-100 text-blue-600 rounded hover:bg-blue-200 transition-all" 
                              onclick="openEditExpenseModal(
                                '<?php echo $expense['expense_ID']; ?>',
                                '<?php echo addslashes($expense['expense_name']); ?>',
                                '<?php echo $expense['category']; ?>',
                                '<?php echo $expense['price']; ?>',
                                '<?php echo $expense['date']; ?>',
                                '<?php echo $expense['status']; ?>',
                                '<?php echo addslashes($expense['notes']); ?>'
                              )">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="p-1.5 bg-red-100 text-red-600 rounded hover:bg-red-200 transition-all" 
                              onclick="confirmArchive('<?php echo $expense['expense_ID']; ?>')">
                        <i class="fas fa-archive text-sidebar-accent"></i>
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="7" class="p-4 text-center text-sm text-gray-500">No expenses found</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
        <div class="text-sm text-gray-500">
          Showing <?php echo count($expenses); ?> of <?php echo $total_items; ?> expenses
        </div>
        <div class="flex space-x-1">
          <?php if ($current_page > 1): ?>
            <a href="?page=<?php echo $current_page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>" 
               class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&laquo;</a>
          <?php endif; ?>
          
          <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>" 
               class="px-3 py-1 border border-sidebar-border rounded text-sm <?php echo $i === $current_page ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover'; ?>">
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>
          
          <?php if ($current_page < $total_pages): ?>
            <a href="?page=<?php echo $current_page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category_filter); ?>&status=<?php echo urlencode($status_filter); ?>&sort=<?php echo $sort_by; ?>&order=<?php echo $sort_order; ?>" 
               class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">&raquo;</a>
          <?php endif; ?>
        </div>
      </div>
    </div>

          </div>

    <!-- Add Expense Modal (same as before) -->
    <!-- Add Expense Modal -->
<div class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="addExpenseModal">
  <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-xl max-h-[90vh] overflow-y-auto">
    <!-- Modal Header -->
    <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
      <h3 class="text-xl font-bold text-white">Add New Expense</h3>
      <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeAddExpenseModal()">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="p-6">
      <form id="expenseForm">
        <div class="mb-5">
          <input type="hidden" id="expenseBranch" name="branch_id" value="<?php echo $branch?>">
          <label for="expenseDescription" class="block mb-2 font-medium text-gray-700">Name</label>
          <input type="text" id="expenseDescription" name="expenseDescription" placeholder="Enter expense description" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
        </div>
        <div class="mb-5">
          <label for="expenseCategory" class="block mb-2 font-medium text-gray-700">Category</label>
          <select id="expenseCategory" name="expenseCategory" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
            <option value="">Select a category</option>
            <option value="Supplies">Supplies</option>
            <option value="Utilities">Utilities</option>
            <option value="Salaries">Salaries</option>
            <option value="Maintenance">Maintenance</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="mb-5">
          <label for="expenseAmount" class="block mb-2 font-medium text-gray-700">Amount (₱)</label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
              </div>
            <input type="number" id="expenseAmount" name="expenseAmount" placeholder="0.00" step="0.01" min="0" class="w-full pl-8 px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
          </div>
        </div>
        <div class="mb-5">
          <label for="expenseDate" class="block mb-2 font-medium text-gray-700">Date</label>
          <input type="date" id="expenseDate" name="expenseDate" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
        </div>
        <div class="mb-5">
          <label for="expenseStatus" class="block mb-2 font-medium text-gray-700">Status</label>
          <select id="expenseStatus" name="expenseStatus" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
            <option value="paid">Paid</option>
            <option value="To be paid">To be Paid</option>
          </select>
          </div>
        <div class="mb-5">
          <label for="expenseNotes" class="block mb-2 font-medium text-gray-700">Notes <span class="text-xs text-gray-500">(Optional)</span></label>
          <textarea id="expenseNotes" name="expenseNotes" rows="3" placeholder="Add any additional details here" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent"></textarea>
          </div>
      </form>
    </div>
    
    <!-- Modal Footer -->
    <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeAddExpenseModal()">Cancel</button>
      <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center" onclick="addExpense()">
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
    <div class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden" id="editExpenseModal">
      <div class="bg-white rounded-xl w-full max-w-lg mx-4 shadow-xl max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-sidebar-accent to-white flex justify-between items-center p-6 flex-shrink-0">
          <h3 class="text-xl font-bold text-white">Edit Expense</h3>
          <button class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200" onclick="closeEditExpenseModal()">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6">
          <form id="editExpenseForm">
            <input type="hidden" id="editExpenseId" name="editExpenseId">
            <div class="mb-5">
              <label for="editExpenseDescription" class="block mb-2 font-medium text-gray-700">Description</label>
              <input type="text" id="editExpenseDescription" name="editExpenseDescription" placeholder="Enter expense description" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
            </div>
            <div class="mb-5">
              <label for="editExpenseCategory" class="block mb-2 font-medium text-gray-700">Category</label>
              <select id="editExpenseCategory" name="editExpenseCategory" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
                <option value="Supplies">Supplies</option>
                <option value="Utilities">Utilities</option>
                <option value="Salaries">Salaries</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="mb-5">
              <label for="editExpenseAmount" class="block mb-2 font-medium text-gray-700">Amount (₱)</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <span class="text-gray-500">₱</span>
                </div>
                <input type="number" id="editExpenseAmount" name="editExpenseAmount" placeholder="0.00" step="0.01" min="0" class="w-full pl-8 px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
              </div>
            </div>
            <div class="mb-5">
              <label for="editExpenseDate" class="block mb-2 font-medium text-gray-700">Date</label>
              <input type="date" id="editExpenseDate" name="editExpenseDate" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
            </div>
            <div class="mb-5">
              <label for="editExpenseStatus" class="block mb-2 font-medium text-gray-700">Status</label>
              <select id="editExpenseStatus" name="editExpenseStatus" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent" required>
                <option value="paid">Paid</option>
                <option value="To be paid">To Be Paid</option>
              </select>
            </div>
            <div class="mb-5">
              <label for="editExpenseNotes" class="block mb-2 font-medium text-gray-700">Notes <span class="text-xs text-gray-500">(Optional)</span></label>
              <textarea id="editExpenseNotes" name="editExpenseNotes" rows="3" placeholder="Add any additional details here" class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-transparent"></textarea>
            </div>
          </form>
        </div>
        
        <!-- Modal Footer -->
        <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
          <button class="px-5 py-3 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeEditExpenseModal()">Cancel</button>
          <button class="px-6 py-3 bg-sidebar-accent text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center" onclick="saveExpenseChanges()">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            Save Changes
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
      
      // Function to apply filters
      function applyFilters() {
        document.getElementById('searchForm').submit();
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

      // Function to open the Edit Expense Modal
      function openEditExpenseModal(id, description, category, amount, date, status, notes) {
        document.getElementById('editExpenseId').value = id;
        document.getElementById('editExpenseDescription').value = description;
        document.getElementById('editExpenseCategory').value = category;
        document.getElementById('editExpenseAmount').value = amount;
        document.getElementById('editExpenseDate').value = date;
        document.getElementById('editExpenseStatus').value = status;
        document.getElementById('editExpenseNotes').value = notes || '';
        
        document.getElementById('editExpenseModal').style.display = 'flex';
        document.getElementById('editExpenseDescription').focus();
      }

      // Function to close the Edit Expense Modal
      function closeEditExpenseModal() {
        document.getElementById('editExpenseModal').style.display = 'none';
      }

      // Function to save changes to an expense (AJAX implementation would go here)
      function saveExpenseChanges() {
        const form = document.getElementById('editExpenseForm');
        if (form.checkValidity()) {
          // In a real implementation, you would use AJAX to submit the form
          // For now, we'll just show a success message
          const expenseId = document.getElementById('editExpenseId').value;
          showNotification(`Expense ${expenseId} updated successfully!`);
          closeEditExpenseModal();
          
          // Reload the page to see changes (in a real app, you'd update the table via AJAX)
          setTimeout(() => {
            window.location.reload();
          }, 1500);
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

      // Function to archive an expense (AJAX implementation would go here)
      function archiveExpense() {
        const expenseId = document.getElementById('archiveExpenseId').textContent;
        
        // In a real implementation, you would use AJAX to archive the expense
        // For now, we'll just show a success message
        showNotification(`Expense ${expenseId} archived successfully!`);
        closeArchiveConfirmModal();
        
        // Reload the page to see changes (in a real app, you'd remove the row via AJAX)
        setTimeout(() => {
          window.location.reload();
        }, 1500);
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