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

// Get the branch_id filter if set
$branch_filter = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;

// Fetch branches from the database for the modal
$sql = "SELECT branch_id, branch_name FROM branch_tb";
$branch_result = $conn->query($sql);
$branches = [];
if ($branch_result->num_rows > 0) {
    while ($branch_row = $branch_result->fetch_assoc()) {
        $branches[] = $branch_row;
    }
}

// Get total count of employees for pagination
$countSql = "SELECT COUNT(*) as total FROM employee_tb";
if ($branch_filter !== null) {
$countSql .= " WHERE branch_id = ?";
$stmtCount = $conn->prepare($countSql);
$stmtCount->bind_param("i", $branch_filter);
$stmtCount->execute();
$countResult = $stmtCount->get_result();
} else {
$countResult = $conn->query($countSql);
}
$totalRow = $countResult->fetch_assoc();
$totalEmployees = $totalRow['total'];
$totalPages = ceil($totalEmployees / $perPage);


// Pagination settings
$perPage = 10; // Number of items per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // Current page
$offset = ($page - 1) * $perPage; // Offset for SQL query
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Employee Management</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="flex bg-gray-50">


<?php include 'admin_sidebar.php'; ?>

  <!-- Main Content -->
  <div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Employee Management</h1>
    </div>
  </div>

<!-- View Employee Details Section -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
  <!-- Header Section - Made responsive with better stacking -->
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
    <!-- Desktop layout for big screens - Title on left, controls on right -->
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
      <!-- Title and Counter -->
      <div class="flex items-center gap-3 mb-4 lg:mb-0">
        <h3 class="text-lg font-bold text-sidebar-text">Employee Details</h3>
        
        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
    <?php echo $totalEmployees . ($totalEmployees != 1 ? " " : " "); ?>
</span>
      </div>
      
      <!-- Controls for big screens - aligned right -->
      <div class="hidden lg:flex items-center gap-3">
        <!-- Search Input -->
        <div class="relative">
          <input type="text" id="searchEmployees" 
                placeholder="Search employees..." 
                class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                oninput="debouncedFilterEmployees()">
          <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
        </div>

        <!-- Branch Filter Dropdown -->
        <div class="relative">
          <select id="branchFilter" onchange="filterByBranch(this.value)" class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-sidebar-accent text-sm">
            <option value="">All Branches</option>
            <?php foreach ($branches as $branch): ?>
              <option value="<?php echo $branch['branch_id']; ?>" <?php echo ($branch_filter == $branch['branch_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($branch['branch_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
          </div>
        </div>

        <!-- Archive Button -->
        <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover whitespace-nowrap">
          <i class="fas fa-archive text-sidebar-accent"></i>
          <span>Archive</span>
        </button>

        <!-- Add Employee Button -->
        <button class="px-4 py-2 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap" 
              onclick="openAddEmployeeModal()"><span>Add Employee</span>
        </button>
      </div>
    </div>
    
    <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
    <div class="lg:hidden w-full mt-4">
      <!-- First row: Search bar with filter and archive icons on the right -->
      <div class="flex items-center w-full gap-3 mb-4">
        <!-- Search Input - Takes most of the space -->
        <div class="relative flex-grow">
          <input type="text" id="searchEmployees" 
                  placeholder="Search employees..." 
                  class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                  oninput="debouncedFilterEmployees()">
          <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>

        <!-- Icon-only buttons for branch filter and archive -->
        <div class="flex items-center gap-3">
          <!-- Branch Filter Dropdown Icon -->
          <div class="relative">
            <button id="branchFilterToggle" class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
              <i class="fas fa-filter text-xl"></i>
              <span id="branchFilterIndicator" class="hidden absolute top-1 right-1 h-2 w-2 bg-sidebar-accent rounded-full"></span>
            </button>
            
            <!-- Branch Filter Dropdown -->
            <div id="branchFilterDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-md shadow-lg z-10 border border-sidebar-border p-4">
              <div class="space-y-4">
                <div>
                  <h5 class="text-sm font-medium text-sidebar-text mb-2">Filter by Branch</h5>
                  <div class="space-y-2">
                    <div class="flex items-center cursor-pointer" data-branch="">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        All Branches
                      </span>
                    </div>
                    <?php foreach ($branches as $branch): ?>
                    <div class="flex items-center cursor-pointer" data-branch="<?php echo $branch['branch_id']; ?>">
                      <span class="filter-option hover:bg-sidebar-hover px-3 py-1.5 rounded text-sm w-full">
                        <?php echo htmlspecialchars($branch['branch_name']); ?>
                      </span>
                    </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Archive Icon Button -->
          <button class="w-10 h-10 flex items-center justify-center text-sidebar-accent">
            <i class="fas fa-archive text-xl"></i>
          </button>
        </div>
      </div>

      <!-- Second row: Add Employee Button - Full width -->
      <div class="w-full">
        <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap w-full justify-center" 
                onclick="openAddEmployeeModal()"><span>Add Employee</span>
        </button>
      </div>
    </div>
  </div>
  
  <!-- Responsive Table Container with improved spacing -->
  <div class="overflow-x-auto scrollbar-thin" id="employeeTableContainer">
    <div id="loadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
      <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
    </div>
    
    <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
    <div class="min-w-full">
      <table class="w-full">
        <thead>
          <tr class="bg-gray-50 border-b border-sidebar-border">
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(0)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-hashtag text-sidebar-accent"></i> ID 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(1)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-user text-sidebar-accent"></i> Name 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(2)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-briefcase text-sidebar-accent"></i> Position 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(3)">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-peso-sign text-sidebar-accent"></i> Base Salary 
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(4)">
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
          <?php 
          // Function to capitalize each word
          function capitalizeWords($string) {
            return ucwords(strtolower(trim($string)));
          }
                
          // SQL query to fetch employee details with concatenated name
          // SQL query to fetch employee details with concatenated name
$sql = "SELECT 
e.EmployeeID,
CONCAT(
    COALESCE(e.fname, ''), 
    CASE WHEN e.mname IS NOT NULL AND e.mname != '' THEN CONCAT(' ', e.mname) ELSE '' END,
    CASE WHEN e.lname IS NOT NULL AND e.lname != '' THEN CONCAT(' ', e.lname) ELSE '' END,
    CASE WHEN e.suffix IS NOT NULL AND e.suffix != '' THEN CONCAT(' ', e.suffix) ELSE '' END
) AS full_name,
e.position,
e.base_salary,
e.status,
e.branch_id,
b.branch_name,
e.gender,
e.bday,
e.phone_number,
e.email,
e.date_created
FROM employee_tb e
JOIN branch_tb b ON e.branch_id = b.branch_id";

// Add branch filter if set
if ($branch_filter !== null) {
$sql .= " WHERE e.branch_id = ?";
$sql .= " LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $branch_filter, $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
} else {
$sql .= " LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$result = $stmt->get_result();
}

          // Check if there are results
          if ($result->num_rows > 0) {
              // Output data of each row
              while($row = $result->fetch_assoc()) {
                  // Capitalize names and first letter of status
                  $full_name = capitalizeWords($row['full_name']);
                  $position = capitalizeWords($row['position']);
                  $status = ucfirst(strtolower($row['status']));

                  // Determine status color and text
                  switch (strtolower($row['status'])) {
                      case 'active':
                          $statusClass = "bg-green-100 text-green-600 border border-green-200";
                          $statusIcon = "fa-check-circle";
                          break;
                      case 'terminated':
                          $statusClass = "bg-red-100 text-red-600 border border-red-200";
                          $statusIcon = "fa-times-circle";
                          break;
                      default:
                          $statusClass = "bg-gray-100 text-gray-600 border border-gray-200";
                          $statusIcon = "fa-question-circle";
                  }

                  echo "<tr class='border-b border-sidebar-border hover:bg-sidebar-hover transition-colors'>";
                  echo "<td class='px-4 py-4 text-sm text-sidebar-text font-medium'>#" . htmlspecialchars($row['EmployeeID']) . "</td>";
                  echo "<td class='px-4 py-4 text-sm text-sidebar-text'>" . htmlspecialchars($full_name) . "</td>";
                  echo "<td class='px-4 py-4 text-sm text-sidebar-text'>";
                  echo "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100'>" . htmlspecialchars($position) . "</span>";
                  echo "</td>";
                  echo "<td class='px-4 py-4 text-sm font-medium text-sidebar-text'>₱" . number_format($row['base_salary'], 2) . "</td>";
                  
                  echo "<td class='px-4 py-4 text-sm'>";
                  echo "<span class='inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium " . $statusClass . "'>";
                  echo "<i class='fas " . $statusIcon . " mr-1'></i> " . htmlspecialchars($status) . "</span>";
                  echo "</td>";
                  
                  // Actions column with styled buttons
echo "<td class='px-4 py-4 text-sm'>";
echo "<div class='flex space-x-2'>";

// View button (first)
echo "<button class='p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip' title='View Details' onclick='viewEmployeeDetails(\"" . htmlspecialchars($row['EmployeeID']) . "\")'>";
echo "<i class='fas fa-eye'></i></button>";

// Edit button (second)
echo "<button class='p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip' title='Edit Employee' onclick='openEditEmployeeModal(\"" . htmlspecialchars($row['EmployeeID']) . "\")'>";
echo "<i class='fas fa-edit'></i></button>";

// Terminate/Reinstate button (third)
if (strtolower($row['status']) == 'active') {
   echo "<button class='p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip' title='Terminate Employee' onclick='terminateEmployee(\"" . htmlspecialchars($row['EmployeeID']) . "\")'>";
   echo "<i class='fas fa-archive text-red'></i></button>";
} else {
   echo "<button class='p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip' title='Reinstate Employee' onclick='reinstateEmployee(\"" . htmlspecialchars($row['EmployeeID']) . "\")'>";
   echo "<i class='fas fa-check'></i></button>";
}

echo "</div>";
echo "</td>";
echo "</tr>";
}
} else {
   echo "<tr>";
   echo "<td colspan='6' class='p-6 text-sm text-center'>";
   echo "<div class='flex flex-col items-center'>";
   echo "<i class='fas fa-users text-gray-300 text-4xl mb-3'></i>";
   echo "<p class='text-gray-500'>No employees found</p>";
   echo "</div>";
   echo "</td>";
   echo "</tr>";
}
          // Close connection
          $conn->close();
          ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Sticky Pagination Footer with improved spacing -->
<div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
        <?php 
        // Get the number of employees on the current page
        $current_page_employees = isset($result) ? $result->num_rows : 0;

        if (isset($result) && $result->num_rows > 0) {
            $offset = ($page - 1) * $perPage;
            $start = $offset + 1;
            $end = $offset + $result->num_rows;
        
            echo "Showing {$start} - {$end} of {$totalEmployees} employees";
        } else {
            echo "No employees found";
        }
        ?>
    </div>
    <div class="flex space-x-2">
        <?php if (isset($totalPages) && $totalPages > 1): ?>
            <!-- First page button (double arrow) -->
            <a href="?page=1<?php echo $branch_filter !== null ? '&branch_id='.$branch_filter : ''; ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page <= 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &laquo;
            </a>
            
            <!-- Previous page button (single arrow) -->
            <a href="<?php echo '?page=' . max(1, $page - 1) . ($branch_filter !== null ? '&branch_id='.$branch_filter : ''); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page <= 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &lsaquo;
            </a>
            
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
                echo '<a href="?page=' . $i . ($branch_filter !== null ? '&branch_id='.$branch_filter : '') . '" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</a>';
            }
            ?>
            
            <!-- Next page button (single arrow) -->
            <a href="<?php echo '?page=' . min($totalPages, $page + 1) . ($branch_filter !== null ? '&branch_id='.$branch_filter : ''); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page >= $totalPages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &rsaquo;
            </a>
            
            <!-- Last page button (double arrow) -->
            <a href="<?php echo '?page=' . $totalPages . ($branch_filter !== null ? '&branch_id='.$branch_filter : ''); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($page >= $totalPages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &raquo;
            </a>
        <?php else: ?>
            <!-- Show placeholder buttons when only one page exists -->
            <span class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 pointer-events-none">&laquo;</span>
            <span class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 pointer-events-none">&lsaquo;</span>
            <span class="px-3.5 py-1.5 rounded text-sm bg-sidebar-accent text-white">1</span>
            <span class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 pointer-events-none">&rsaquo;</span>
            <span class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm opacity-50 pointer-events-none">&raquo;</span>
        <?php endif; ?>
    </div>
</div>
</div>

<!-- View Employee Salary Details Modal -->
<div id="viewEmployeeModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-lg mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeViewEmployeeModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Employee Salary Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <!-- Employee Information -->
      <div class="mb-4 sm:mb-6">
        <div class="flex flex-row justify-between">
          <div class="flex-1 px-1">
            <p class="text-xs font-medium text-gray-500">Employee ID</p>
            <p id="employeeId" class="text-sm font-medium text-gray-800">-</p>
          </div>
          <div class="flex-1 px-1">
            <p class="text-xs font-medium text-gray-500">Employee Name</p>
            <p id="employeeName" class="text-sm font-medium text-gray-800">-</p>
          </div>
          <div class="flex-1 px-1">
            <p class="text-xs font-medium text-gray-500">Base Salary</p>
            <p id="employeeBaseSalary" class="text-sm font-medium text-gray-800">-</p>
          </div>
        </div>
        
        <!-- Date Range Picker -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200 mt-4 sm:mt-6">
          <h4 class="text-xs sm:text-sm font-medium text-gray-700 mb-2 sm:mb-3">Select Date Range</h4>
          <div class="space-y-3 sm:space-y-4">
            <div>
              <label for="startDate" class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
              <input type="date" id="startDate" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
            <div>
              <label for="endDate" class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
              <input type="date" id="endDate" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
            <div>
              <button onclick="fetchEmployeeSalary()" class="w-full px-4 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
                <i class="fas fa-search mr-2"></i> Search
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Salary Summary -->
      <div class="mb-4 sm:mb-6">
        <h4 class="text-xs sm:text-sm font-medium text-gray-700 mb-2">Salary Summary</h4>
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <div class="flex flex-row justify-between">
            <div class="flex-1 px-1">
              <p class="text-xs font-medium text-gray-500">Total Services</p>
              <p id="totalServices" class="text-lg font-bold text-gray-800">0</p>
            </div>
            <div class="flex-1 px-1">
              <p class="text-xs font-medium text-gray-500">Total Earnings</p>
              <p id="totalEarnings" class="text-lg font-bold text-green-600">₱0.00</p>
            </div>
            <div class="flex-1 px-1">
              <p class="text-xs font-medium text-gray-500">Base Salary</p>
              <p id="modalBaseSalary" class="text-lg font-bold text-blue-600">₱0.00</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Service Details Table -->
      <div>
        <h4 class="text-xs sm:text-sm font-medium text-gray-700 mb-2">Service Details</h4>
        <div class="overflow-x-auto">
          <table class="w-full border-collapse">
            <thead>
              <tr class="bg-gray-100">
                <th class="p-2 sm:p-3 text-left text-xs font-medium text-gray-700">Date</th>
                <th class="p-2 sm:p-3 text-left text-xs font-medium text-gray-700">Service</th>
                <th class="p-2 sm:p-3 text-left text-xs font-medium text-gray-700">Income</th>
              </tr>
            </thead>
            <tbody id="serviceDetailsBody">
              <!-- Service details will be populated here -->
              <tr>
                <td colspan="3" class="text-center p-3 sm:p-4 text-gray-500">Select a date range to view service details</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex justify-center border-t border-gray-200 sticky bottom-0 bg-white">
      <button type="button" onclick="closeViewEmployeeModal()" class="w-full sm:w-auto px-4 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
        Close
      </button>
    </div>
  </div>
</div>

<!-- Add Employee Modal -->
<div id="addEmployeeModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeAddEmployeeModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Add Employee Account
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <form id="addEmployeeAccountForm" class="space-y-3 sm:space-y-4">
        <!-- Name Fields -->
        <div>
          <label for="firstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            First Name <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="text" id="firstName" name="firstName" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="First Name" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
          </div>
        </div>
        
        <div>
          <label for="lastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Last Name <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="text" id="lastName" name="lastName" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Last Name" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
          </div>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
          <div class="w-full sm:flex-1">
            <label for="middleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Middle Name
            </label>
            <div class="relative">
              <input type="text" id="middleName" name="middleName"
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Middle Name" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
            </div>
          </div>
          <div class="w-full sm:flex-1">
            <label for="suffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Suffix <span class="text-xs text-gray-500">(Optional)</span>
            </label>
            <div class="relative">
            <select id="suffix" name="suffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">None</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="I">I</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                    <option value="V">V</option>
                                </select>
            </div>
          </div>
        </div>

        <!-- Date of Birth Field -->
        <div>
          <label for="dateOfBirth" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Date of Birth <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="date" id="dateOfBirth" name="dateOfBirth" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
          </div>
        </div>

        <!-- Contact Information -->
        <div>
          <label for="employeeEmail" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Email Address <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="email" id="employeeEmail" name="employeeEmail" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Email">
          </div>
        </div>
        
        <div>
          <label for="employeePhone" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Phone Number <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="tel" id="employeePhone" name="employeePhone" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="09XXXXXXXXX or +63XXXXXXXXXX" pattern="(\+63|0)\d{10}" title="Philippine phone number (09XXXXXXXXX or +63XXXXXXXXXX)">
          </div>
        </div>

        <!-- Position and Salary -->
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
          <div class="w-full sm:flex-1">
            <label for="employeePosition" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Position <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <select id="employeePosition" name="employeePosition" required
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                  <option value="">Select Position</option>
                  <option value="Embalmer">Embalmer</option>
                  <option value="Driver">Driver</option>
                  <option value="Secretary">Secretary</option>
                  <option value="Financial Manager">Financial Manager</option>
                  <option value="Operational Head">Operational Head</option>
                  <option value="Personnel">Personnel</option>
              </select>
            </div>
          </div>
          <div class="w-full sm:flex-1">
            <label for="employeeSalary" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Salary per Service (₱) <span class="text-red-500">*</span>
            </label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <span class="text-gray-500">₱</span>
              </div>
              <input type="number" id="employeeSalary" name="employeeSalary" required step="0.01" min="0.01"
                  class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Amount">
            </div>
          </div>
        </div>

        <!-- Gender Selection -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <p class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
            Gender <span class="text-red-500">*</span>
          </p>
          <div class="grid grid-cols-2 sm:grid-cols-2 gap-3">
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" name="gender" value="Male" required class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              <i class="fas fa-male mr-1 text-sidebar-accent"></i>
              Male
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" name="gender" value="Female" required class="mr-2 text-sidebar-accent focus:ring-sidebar-accent">
              <i class="fas fa-female mr-1 text-sidebar-accent"></i>
              Female
            </label>
          </div>
        </div>
        
        <!-- Branch Selection -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border-l-4 border-gold">
          <label class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
            Branch Location <span class="text-red-500">*</span>
          </label>
          <div class="flex flex-wrap gap-4">
            <?php foreach ($branches as $branch): ?>
              <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="branch" value="<?php echo $branch['branch_id']; ?>" required class="hidden peer">
                <div class="w-5 h-5 rounded-full border-2 border-gold flex items-center justify-center peer-checked:bg-gold peer-checked:border-darkgold transition-colors"></div>
                <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($branch['branch_name']); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeAddEmployeeModal()">
        Cancel
      </button>
      <button type="submit" form="addEmployeeAccountForm" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
        Add Employee
      </button>
    </div>
  </div>
</div>

<div class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto" id="editEmployeeModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeEditEmployeeModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Edit Employee Account
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5">
      <form id="editEmployeeAccountForm" class="space-y-3 sm:space-y-4">
        <!-- Hidden field for employee ID -->
        <input type="hidden" id="editEmployeeId" name="employeeId">
        
        <!-- Name Fields -->
        <div>
          <label for="editFirstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            First Name <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="text" id="editFirstName" name="firstName" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="First Name" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
          </div>
        </div>
        
        <div>
          <label for="editLastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Last Name <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="text" id="editLastName" name="lastName" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Last Name" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
          </div>
        </div>
        
        <div class="flex flex-col sm:flex-row gap-2 sm:gap-4">
          <div class="w-full sm:flex-1">
            <label for="editMiddleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Middle Name
            </label>
            <div class="relative">
              <input type="text" id="editMiddleName" name="middleName"
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Middle Name" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
            </div>
          </div>
          <div class="w-full sm:flex-1">
            <label for="editSuffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              Suffix
            </label>
            <div class="relative">
            <select id="editSuffix" name="editSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                    <option value="">None</option>
                                    <option value="Jr.">Jr.</option>
                                    <option value="Sr.">Sr.</option>
                                    <option value="I">I</option>
                                    <option value="II">II</option>
                                    <option value="III">III</option>
                                    <option value="IV">IV</option>
                                    <option value="V">V</option>
                                </select>
            </div>
          </div>
        </div>

        <!-- Date of Birth Field -->
        <div>
          <label for="editDateOfBirth" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Date of Birth <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="date" id="editDateOfBirth" name="dateOfBirth" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
          </div>
        </div>

        <!-- Contact Information -->
        <div>
          <label for="editEmployeeEmail" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Email Address <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="email" id="editEmployeeEmail" name="employeeEmail" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Email">
          </div>
        </div>
        
        <div>
          <label for="editEmployeePhone" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Phone Number <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input type="tel" id="editEmployeePhone" name="employeePhone" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="09XXXXXXXXX or +63XXXXXXXXXX" pattern="(\+63|0)\d{10}" title="Philippine phone number (09XXXXXXXXX or +63XXXXXXXXXX)">
          </div>
        </div>

        <!-- Position and Salary -->
        <div>
          <label for="editEmployeePosition" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Position <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <select id="editEmployeePosition" name="employeePosition" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
                <option value="">Select Position</option>
                <option value="Embalmer">Embalmer</option>
                <option value="Driver">Driver</option>
                <option value="Secretary">Secretary</option>
                <option value="Financial Manager">Financial Manager</option>
                <option value="Operational Head">Operational Head</option>
                <option value="Personnel">Personnel</option>
            </select>
          </div>
        </div>
        
        <div>
          <label for="editEmployeeSalary" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Salary per Service (₱) <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="editEmployeeSalary" name="employeeSalary" required step="0.01" min="0.01"
                class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Amount">
          </div>
        </div>
        
        <!-- Gender Selection -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <p class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
            Gender <span class="text-red-500">*</span>
          </p>
          <div class="grid grid-cols-2 sm:grid-cols-2 gap-3">
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" name="gender" value="Male" required class="mr-2 text-sidebar-accent focus:ring-sidebar-accent" id="editGenderMale">
              <i class="fas fa-male mr-1 text-sidebar-accent"></i>
              Male
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" name="gender" value="Female" required class="mr-2 text-sidebar-accent focus:ring-sidebar-accent" id="editGenderFemale">
              <i class="fas fa-female mr-1 text-sidebar-accent"></i>
              Female
            </label>
          </div>
        </div>
        
        <!-- Branch Selection -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border-l-4 border-gold">
          <label class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
            Branch Location <span class="text-red-500">*</span>
          </label>
          <div class="flex flex-wrap gap-4">
            <?php foreach ($branches as $branch): ?>
              <label class="flex items-center space-x-2 cursor-pointer">
                <input type="radio" name="branch" value="<?php echo $branch['branch_id']; ?>" required class="hidden peer editBranchRadio" id="editBranch<?php echo $branch['branch_id']; ?>">
                <div class="w-5 h-5 rounded-full border-2 border-gold flex items-center justify-center peer-checked:bg-gold peer-checked:border-darkgold transition-colors"></div>
                <span class="text-gray-700 font-medium"><?php echo htmlspecialchars($branch['branch_name']); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
      </form>
    </div>
    
    <!-- Modal Footer --> 
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="closeEditEmployeeModal()">
        Cancel
      </button>
      <button type="submit" form="editEmployeeAccountForm" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
        Update Employee
      </button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Common validation functions
    function validateNameInput(input) {
        // Remove any numbers or symbols (except spaces and apostrophes)
        let value = input.value.replace(/[^a-zA-Z\s']/g, '');
        
        // Remove multiple consecutive spaces
        value = value.replace(/\s{2,}/g, ' ');
        
        // Capitalize first letter of each word
        value = value.toLowerCase().replace(/(?:^|\s)\S/g, function(char) {
            return char.toUpperCase();
        });
        
        // Update the input value
        input.value = value;
        
        // Validate minimum length (only for required fields)
        if (input.required && value.trim().length < 2) {
            input.setCustomValidity('Minimum 2 characters required');
            return false;
        } else {
            input.setCustomValidity('');
            return true;
        }
    }

    function handleNamePaste(e) {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        
        // Clean the pasted text
        let cleaned = text.replace(/[^a-zA-Z\s']/g, '')
                         .replace(/\s{2,}/g, ' ')
                         .toLowerCase()
                         .replace(/(?:^|\s)\S/g, function(char) {
                             return char.toUpperCase();
                         });
        
        // Insert the cleaned text
        document.execCommand('insertText', false, cleaned);
    }

    function validateEmail(emailInput) {
        const email = emailInput.value.trim();
        
        // Remove any spaces
        emailInput.value = email.replace(/\s/g, '');
        
        // Basic email validation
        if (!email.includes('@')) {
            emailInput.setCustomValidity('Email must contain @ symbol');
            return false;
        } else {
            emailInput.setCustomValidity('');
            return true;
        }
    }

    function validatePhoneNumber(phoneInput) {
        let phone = phoneInput.value.trim();
        
        // Remove any non-digit characters
        phone = phone.replace(/\D/g, '');
        
        // Validate Philippine phone number format
        if (!phone.match(/^(09|\+639)\d{9}$/) && !phone.match(/^0\d{10}$/)) {
            // If it doesn't match either format, try to correct it
            if (phone.startsWith('9') && phone.length === 10) {
                phone = '0' + phone;
            } else if (phone.startsWith('639') && phone.length === 11) {
                phone = '0' + phone.substring(3);
            }
        }
        
        // Limit to 11 digits
        if (phone.length > 11) {
            phone = phone.substring(0, 11);
        }
        
        phoneInput.value = phone;
        
        if (phone.length < 11 || !phone.startsWith('09')) {
            phoneInput.setCustomValidity('Please enter a valid Philippine phone number (09XXXXXXXXX)');
            return false;
        } else {
            phoneInput.setCustomValidity('');
            return true;
        }
    }

    // Apply validations to add employee modal
    const addModal = document.getElementById('addEmployeeModal');
    if (addModal) {
        // Name fields
        const nameFields = ['firstName', 'middleName', 'lastName', 'suffix'];
        nameFields.forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                input.addEventListener('input', () => validateNameInput(input));
                input.addEventListener('paste', handleNamePaste);
            }
        });

        // Email field
        const emailInput = document.getElementById('employeeEmail');
        if (emailInput) {
            emailInput.addEventListener('input', () => validateEmail(emailInput));
            emailInput.addEventListener('paste', (e) => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                document.execCommand('insertText', false, text.replace(/\s/g, ''));
                validateEmail(emailInput);
            });
        }

        // Phone field
        const phoneInput = document.getElementById('employeePhone');
        if (phoneInput) {
            phoneInput.addEventListener('input', () => validatePhoneNumber(phoneInput));
            phoneInput.addEventListener('paste', (e) => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                document.execCommand('insertText', false, text.replace(/\D/g, ''));
                validatePhoneNumber(phoneInput);
            });
        }

        // Date of birth field
        const dobInput = document.getElementById('dateOfBirth');
        if (dobInput) {
            // Set max date (today) and min date (100 years ago)
            const today = new Date();
            const minDate = new Date();
            minDate.setFullYear(today.getFullYear() - 100);
            
            dobInput.max = today.toISOString().split('T')[0];
            dobInput.min = minDate.toISOString().split('T')[0];
            
            dobInput.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const eighteenYearsAgo = new Date();
                eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
                
                if (selectedDate > eighteenYearsAgo) {
                    this.setCustomValidity('Employee must be at least 18 years old');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    }

    // Apply validations to edit employee modal
    const editModal = document.getElementById('editEmployeeModal');
    if (editModal) {
        // Name fields
        const editNameFields = ['editFirstName', 'editMiddleName', 'editLastName', 'editSuffix'];
        editNameFields.forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                input.addEventListener('input', () => validateNameInput(input));
                input.addEventListener('paste', handleNamePaste);
            }
        });

        // Email field
        const editEmailInput = document.getElementById('editEmployeeEmail');
        if (editEmailInput) {
            editEmailInput.addEventListener('input', () => validateEmail(editEmailInput));
            editEmailInput.addEventListener('paste', (e) => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                document.execCommand('insertText', false, text.replace(/\s/g, ''));
                validateEmail(editEmailInput);
            });
        }

        // Phone field
        const editPhoneInput = document.getElementById('editEmployeePhone');
        if (editPhoneInput) {
            editPhoneInput.addEventListener('input', () => validatePhoneNumber(editPhoneInput));
            editPhoneInput.addEventListener('paste', (e) => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                document.execCommand('insertText', false, text.replace(/\D/g, ''));
                validatePhoneNumber(editPhoneInput);
            });
        }

        // Date of birth field
        const editDobInput = document.getElementById('editDateOfBirth');
        if (editDobInput) {
            // Set max date (today) and min date (100 years ago)
            const today = new Date();
            const minDate = new Date();
            minDate.setFullYear(today.getFullYear() - 100);
            
            editDobInput.max = today.toISOString().split('T')[0];
            editDobInput.min = minDate.toISOString().split('T')[0];
            
            editDobInput.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const eighteenYearsAgo = new Date();
                eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
                
                if (selectedDate > eighteenYearsAgo) {
                    this.setCustomValidity('Employee must be at least 18 years old');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    }

    // Form submission handlers
    const addEmployeeForm = document.getElementById('addEmployeeAccountForm');
    if (addEmployeeForm) {
        addEmployeeForm.addEventListener('submit', function(event) {
            // Validate all fields before submission
            let isValid = true;
            
            // Validate required name fields
            const firstName = document.getElementById('firstName');
            const lastName = document.getElementById('lastName');
            if (!validateNameInput(firstName) || !validateNameInput(lastName)) {
                isValid = false;
            }
            
            // Validate email
            const email = document.getElementById('employeeEmail');
            if (!validateEmail(email)) {
                isValid = false;
            }
            
            // Validate phone
            const phone = document.getElementById('employeePhone');
            if (!validatePhoneNumber(phone)) {
                isValid = false;
            }
            
            // Validate date of birth
            const dob = document.getElementById('dateOfBirth');
            if (dob.value) {
                const selectedDate = new Date(dob.value);
                const eighteenYearsAgo = new Date();
                eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
                
                if (selectedDate > eighteenYearsAgo) {
                    dob.setCustomValidity('Employee must be at least 18 years old');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
                // Show validation messages
                addEmployeeForm.classList.add('was-validated');
            }
        });
    }

    const editEmployeeForm = document.getElementById('editEmployeeAccountForm');
    if (editEmployeeForm) {
        editEmployeeForm.addEventListener('submit', function(event) {
            // Validate all fields before submission
            let isValid = true;
            
            // Validate required name fields
            const firstName = document.getElementById('editFirstName');
            const lastName = document.getElementById('editLastName');
            if (!validateNameInput(firstName) || !validateNameInput(lastName)) {
                isValid = false;
            }
            
            // Validate email
            const email = document.getElementById('editEmployeeEmail');
            if (!validateEmail(email)) {
                isValid = false;
            }
            
            // Validate phone
            const phone = document.getElementById('editEmployeePhone');
            if (!validatePhoneNumber(phone)) {
                isValid = false;
            }
            
            // Validate date of birth
            const dob = document.getElementById('editDateOfBirth');
            if (dob.value) {
                const selectedDate = new Date(dob.value);
                const eighteenYearsAgo = new Date();
                eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
                
                if (selectedDate > eighteenYearsAgo) {
                    dob.setCustomValidity('Employee must be at least 18 years old');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
                // Show validation messages
                editEmployeeForm.classList.add('was-validated');
            }
        });
    }
});
</script>

  <script src="script.js"></script>
  <script>
      document.addEventListener('DOMContentLoaded', function() {
          // Get the form elements
          const dateOfBirthInput = document.getElementById('dateOfBirth');
          const employeeSalaryInput = document.getElementById('employeeSalary');
          const addEmployeeForm = document.getElementById('addEmployeeAccountForm');

          // Set max date for birthdate (18 years ago)
          const today = new Date();
          const minDate = new Date();
          minDate.setFullYear(today.getFullYear() - 18);
          const minDateString = minDate.toISOString().split('T')[0];
          dateOfBirthInput.max = minDateString;

          // Validate birthdate on change
          dateOfBirthInput.addEventListener('change', function() {
              const selectedDate = new Date(this.value);
              const eighteenYearsAgo = new Date();
              eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
              
              if (selectedDate > eighteenYearsAgo) {
                  alert('Employee must be at least 18 years old.');
                  this.value = '';
                  this.focus();
              }
          });

          // Validate salary on input
          employeeSalaryInput.addEventListener('input', function() {
              const salary = parseFloat(this.value);
              if (salary > 10000) {
                  alert('Salary must be less than or equal to ₱10,000');
                  this.value = '';
                  this.focus();
              }
          });

          // Form submission validation
          addEmployeeForm.addEventListener('submit', function(event) {
              // Revalidate birthdate
              const birthDate = new Date(dateOfBirthInput.value);
              const eighteenYearsAgo = new Date();
              eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
              
              if (birthDate > eighteenYearsAgo) {
                  alert('Employee must be at least 18 years old.');
                  dateOfBirthInput.focus();
                  event.preventDefault();
                  return;
              }

              // Revalidate salary
              const salary = parseFloat(employeeSalaryInput.value);
              if (salary > 10000) {
                  alert('Salary must be less than or equal to ₱10,000');
                  employeeSalaryInput.focus();
                  event.preventDefault();
                  return;
              }

              // If all validations pass, the form will submit
          });
      });

          document.addEventListener('DOMContentLoaded', function() {
          const addEmployeeAccountForm = document.getElementById('addEmployeeAccountForm');
          
          addEmployeeAccountForm.addEventListener('submit', function(event) {
              // Prevent the default form submission
              event.preventDefault();
              
              // Create FormData object to easily send form data
              const formData = new FormData(addEmployeeAccountForm);
              
              // Send data to server using fetch
              fetch('employeeManagement/add_employee.php', {
                  method: 'POST',
                  body: formData
              })
              .then(response => response.json())
              .then(data => {
                  if (data.status === 'success') {
                      // Success handling
                      console.log('Employee added successfully:', data);
                      alert(data.message);
                      closeAddEmployeeModal();
                      location.reload();
                      addEmployeeAccountForm.reset();
                  } else {
                      // Error handling
                      console.error('Error:', data);
                      if (data.errors) {
                          // Display validation errors
                          alert(data.errors.join('\n'));
                      } else {
                          alert(data.message || 'An error occurred');
                      }
                  }
              })
              .catch(error => {
                  console.error('Network or server error:', error);
                  alert('An unexpected error occurred. Please try again.');
              });
          });
      });

      
      function filterByBranch(branchId) {
    if (branchId === '') {
        window.location.href = 'employee_management.php';
    } else {
        window.location.href = 'employee_management.php?branch_id=' + branchId + '&page=1';
    }
}

    // Function to open the Add Employee Modal
    function openAddEmployeeModal() {
      document.getElementById('addEmployeeModal').style.display = 'flex';
    }

    // Function to close the Add Employee Modal
    function closeAddEmployeeModal() {
      document.getElementById('addEmployeeModal').style.display = 'none';
    }

    // Function to open the Edit Employee Modal
    function openEditEmployeeModal(employeeId) {
      document.getElementById('editEmployeeModal').classList.remove('hidden');
      
      // Fetch employee data and populate the form
      fetchEmployeeData(employeeId);
    }

    document.getElementById('editEmployeeAccountForm').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent default form submission

    // Validate form
    if (!this.checkValidity()) {
        alert('Please fill out all required fields correctly.');
        return;
    }

    // Prepare form data
    const formData = new FormData(this);

    // Send AJAX request to update employee
    fetch('employeeManagement/update_employee.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Employee updated successfully!');
            closeEditEmployeeModal();
            location.reload();

            // Optionally refresh the employee list or update the specific row
        } else {
            alert('Error updating employee: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the employee.');
    });
});

    // Function to close the Edit Employee Modal
    function closeEditEmployeeModal() {
      document.getElementById('editEmployeeModal').classList.add('hidden');
    }

    function fetchEmployeeData(employeeId) {
  // Send AJAX request to get employee data
  fetch('employeeManagement/get_employee.php?id=' + employeeId)
    .then(response => response.json())
    .then(data => {
      if (data) {
        // Populate the form fields
        document.getElementById('editEmployeeId').value = data.EmployeeID;
        document.getElementById('editFirstName').value = data.fname || '';
        document.getElementById('editLastName').value = data.lname || '';
        document.getElementById('editMiddleName').value = data.mname || '';
        document.getElementById('editSuffix').value = data.suffix || '';
        document.getElementById('editDateOfBirth').value = data.bday || '';
        document.getElementById('editEmployeeEmail').value = data.email || '';
        document.getElementById('editEmployeePhone').value = data.phone_number || '';
        document.getElementById('editEmployeePosition').value = data.position || '';
        document.getElementById('editEmployeeSalary').value = data.base_salary || '';
        
        // Set gender radio button
        if (data.gender === 'Male') {
          document.getElementById('editGenderMale').checked = true;
        } else if (data.gender === 'Female') {
          document.getElementById('editGenderFemale').checked = true;
        }
        
        // Set branch radio button
        const branchRadio = document.querySelector(`.editBranchRadio[value="${data.branch_id}"]`);
        if (branchRadio) {
          branchRadio.checked = true;
          // Trigger the visual change for the custom radio button
          const visualRadio = branchRadio.nextElementSibling;
          visualRadio.classList.add('peer-checked:bg-gold', 'peer-checked:border-darkgold');
        }
      }
    })
    .catch(error => {
      console.error('Error fetching employee data:', error);
      alert('Failed to load employee data');
    });
}

    // Function to save changes to an employee
    function saveEmployeeChanges() {
      const form = document.getElementById('editEmployeeForm');
      if (form.checkValidity()) {
        // Add logic to save changes
        alert('Employee details updated successfully!');
        closeEditEmployeeModal();
      } else {
        form.reportValidity();
      }
    }

    // Function to terminate an employee
    function terminateEmployee(employeeId) {
    if (confirm('Are you sure you want to terminate this employee?')) {
        // Create AJAX request
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "employeeManagement/terminate_employee.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    // Success - parse the response if needed
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        alert(`Employee ${employeeId} terminated successfully!`);
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                } else {
                    alert('Error terminating employee');
                }
            }
        };
        
        // Send the employee ID to the PHP script
        xhr.send("employeeId=" + encodeURIComponent(employeeId));
    }
}

    // Function to reinstate an employee
    function reinstateEmployee(employeeId) {
      if (confirm('Are you sure you want to rehire this employee?')) {
        // Create AJAX request
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "employeeManagement/rehire_employee.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
        xhr.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status === 200) {
                    // Success - parse the response if needed
                    var response = JSON.parse(this.responseText);
                    if (response.success) {
                        alert(`Employee ${employeeId} reinstated successfully!`);
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                } else {
                    alert('Error rehiring employee');
                }
            }
        };
        
        // Send the employee ID to the PHP script
        xhr.send("employeeId=" + encodeURIComponent(employeeId));
    }
    }

  </script>

  <script>
// Global variable to store current employee ID
let currentEmployeeId = null;

// Function to view employee salary details
function viewEmployeeDetails(employeeId) {
  currentEmployeeId = employeeId;
  
  // Fetch employee basic info
  fetch('employeeManagement/get_employee.php?id=' + employeeId)
    .then(response => response.json())
    .then(data => {
      if (data) {
        // Populate the modal with employee info
        document.getElementById('employeeId').textContent = data.EmployeeID;
        document.getElementById('employeeName').textContent = 
          (data.fname || '') + ' ' + 
          (data.mname ? data.mname + ' ' : '') + 
          (data.lname || '') + 
          (data.suffix ? ' ' + data.suffix : '');
        
        // Format base salary
        const baseSalary = parseFloat(data.base_salary || 0);
        document.getElementById('employeeBaseSalary').textContent = '₱' + baseSalary.toFixed(2);
        document.getElementById('modalBaseSalary').textContent = '₱' + baseSalary.toFixed(2);
        
        // Set default date range (last 30 days)
        const endDate = new Date();
        const startDate = new Date();
        startDate.setDate(endDate.getDate() - 30);
        
        document.getElementById('startDate').valueAsDate = startDate;
        document.getElementById('endDate').valueAsDate = endDate;
        
        // Show the modal
        document.getElementById('viewEmployeeModal').style.display = 'flex';
        
        // Automatically fetch salary data for default date range
        fetchEmployeeSalary();
      }
    })
    .catch(error => {
      console.error('Error fetching employee details:', error);
      alert('Failed to load employee details');
    });
}

// Function to fetch employee salary data
function fetchEmployeeSalary() {
  if (!currentEmployeeId) return;
  
  const startDate = document.getElementById('startDate').value;
  const endDate = document.getElementById('endDate').value;
  
  if (!startDate || !endDate) {
    alert('Please select both start and end dates');
    return;
  }
  
  // Show loading state
  document.getElementById('serviceDetailsBody').innerHTML = `
    <tr>
      <td colspan="4" class="text-center p-4 text-gray-500">
        <i class="fas fa-spinner fa-spin mr-2"></i> Loading...
      </td>
    </tr>
  `;
  
  // Fetch salary data
  fetch(`employeeManagement/get_employee_salary.php?employeeId=${currentEmployeeId}&startDate=${startDate}&endDate=${endDate}`)
    .then(response => response.json())
    .then(data => {
      if (data && data.success) {
        // Update summary
        document.getElementById('totalServices').textContent = data.total_services || 0;
        document.getElementById('totalEarnings').textContent = '₱' + parseFloat(data.total_earnings || 0).toFixed(2);
        
        // Update service details table
        const tbody = document.getElementById('serviceDetailsBody');
        tbody.innerHTML = '';
        
        if (data.services && data.services.length > 0) {
          data.services.forEach(service => {
            const row = document.createElement('tr');
            row.className = 'border-b border-gray-200 hover:bg-gray-50';
            row.innerHTML = `
              <td class="p-3 text-sm text-gray-700">${service.payment_date}</td>
              <td class="p-3 text-sm text-gray-700">${service.service_name}</td>
              <td class="p-3 text-sm text-gray-700">₱${parseFloat(service.service_income).toFixed(2)}</td>
            `;
            tbody.appendChild(row);
          });
        } else {
          tbody.innerHTML = `
            <tr>
              <td colspan="4" class="text-center p-4 text-gray-500">No services found for selected date range</td>
            </tr>
          `;
        }
      } else {
        document.getElementById('serviceDetailsBody').innerHTML = `
          <tr>
            <td colspan="4" class="text-center p-4 text-gray-500">${data.message || 'Error loading salary data'}</td>
          </tr>
        `;
      }
    })
    .catch(error => {
      console.error('Error fetching salary data:', error);
      document.getElementById('serviceDetailsBody').innerHTML = `
        <tr>
          <td colspan="4" class="text-center p-4 text-gray-500">Error loading salary data</td>
        </tr>
      `;
    });
}

// Function to close the View Employee Modal
function closeViewEmployeeModal() {
  document.getElementById('viewEmployeeModal').style.display = 'none';
  currentEmployeeId = null;
}
</script>
  <script src="script.js"></script>
  <script src="tailwind.js"></script>

  <script>
document.addEventListener('DOMContentLoaded', function() {
    // Common validation functions
    function validateNameInput(input) {
        // Remove any numbers or symbols (except spaces and apostrophes)
        let value = input.value.replace(/[^a-zA-Z\s']/g, '');
        
        // Remove multiple consecutive spaces
        value = value.replace(/\s{2,}/g, ' ');
        
        // Capitalize first letter of each word
        value = value.toLowerCase().replace(/(?:^|\s)\S/g, function(char) {
            return char.toUpperCase();
        });
        
        // Update the input value
        input.value = value;
        
        // Validate minimum length (only for required fields)
        if (input.required && value.trim().length < 2) {
            input.setCustomValidity('Minimum 2 characters required');
            return false;
        } else {
            input.setCustomValidity('');
            return true;
        }
    }

    function handleNamePaste(e) {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        
        // Clean the pasted text
        let cleaned = text.replace(/[^a-zA-Z\s']/g, '')
                         .replace(/\s{2,}/g, ' ')
                         .toLowerCase()
                         .replace(/(?:^|\s)\S/g, function(char) {
                             return char.toUpperCase();
                         });
        
        // Insert the cleaned text
        document.execCommand('insertText', false, cleaned);
    }

    function validateEmail(emailInput) {
        const email = emailInput.value.trim();
        
        // Remove any spaces
        emailInput.value = email.replace(/\s/g, '');
        
        // Basic email validation
        if (!email.includes('@')) {
            emailInput.setCustomValidity('Email must contain @ symbol');
            return false;
        } else {
            emailInput.setCustomValidity('');
            return true;
        }
    }

    function validatePhoneNumber(phoneInput) {
        let phone = phoneInput.value.trim();
        
        // Remove any non-digit characters
        phone = phone.replace(/\D/g, '');
        
        // Validate Philippine phone number format
        if (!phone.match(/^(09|\+639)\d{9}$/) && !phone.match(/^0\d{10}$/)) {
            // If it doesn't match either format, try to correct it
            if (phone.startsWith('9') && phone.length === 10) {
                phone = '0' + phone;
            } else if (phone.startsWith('639') && phone.length === 11) {
                phone = '0' + phone.substring(3);
            }
        }
        
        // Limit to 11 digits
        if (phone.length > 11) {
            phone = phone.substring(0, 11);
        }
        
        phoneInput.value = phone;
        
        if (phone.length < 11 || !phone.startsWith('09')) {
            phoneInput.setCustomValidity('Please enter a valid Philippine phone number (09XXXXXXXXX)');
            return false;
        } else {
            phoneInput.setCustomValidity('');
            return true;
        }
    }

    // Apply validations to add employee modal
    const addModal = document.getElementById('addEmployeeModal');
    if (addModal) {
        // Name fields
        const nameFields = ['firstName', 'middleName', 'lastName', 'suffix'];
        nameFields.forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                input.addEventListener('input', () => validateNameInput(input));
                input.addEventListener('paste', handleNamePaste);
            }
        });

        // Email field
        const emailInput = document.getElementById('employeeEmail');
        if (emailInput) {
            emailInput.addEventListener('input', () => validateEmail(emailInput));
            emailInput.addEventListener('paste', (e) => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                document.execCommand('insertText', false, text.replace(/\s/g, ''));
                validateEmail(emailInput);
            });
        }

        // Phone field
        const phoneInput = document.getElementById('employeePhone');
        if (phoneInput) {
            phoneInput.addEventListener('input', () => validatePhoneNumber(phoneInput));
            phoneInput.addEventListener('paste', (e) => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                document.execCommand('insertText', false, text.replace(/\D/g, ''));
                validatePhoneNumber(phoneInput);
            });
        }

        // Date of birth field
        const dobInput = document.getElementById('dateOfBirth');
        if (dobInput) {
            // Set max date (today) and min date (100 years ago)
            const today = new Date();
            const minDate = new Date();
            minDate.setFullYear(today.getFullYear() - 100);
            
            dobInput.max = today.toISOString().split('T')[0];
            dobInput.min = minDate.toISOString().split('T')[0];
            
            dobInput.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const eighteenYearsAgo = new Date();
                eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
                
                if (selectedDate > eighteenYearsAgo) {
                    this.setCustomValidity('Employee must be at least 18 years old');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    }

    // Apply validations to edit employee modal
    const editModal = document.getElementById('editEmployeeModal');
    if (editModal) {
        // Name fields
        const editNameFields = ['editFirstName', 'editMiddleName', 'editLastName', 'editSuffix'];
        editNameFields.forEach(field => {
            const input = document.getElementById(field);
            if (input) {
                input.addEventListener('input', () => validateNameInput(input));
                input.addEventListener('paste', handleNamePaste);
            }
        });

        // Email field
        const editEmailInput = document.getElementById('editEmployeeEmail');
        if (editEmailInput) {
            editEmailInput.addEventListener('input', () => validateEmail(editEmailInput));
            editEmailInput.addEventListener('paste', (e) => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                document.execCommand('insertText', false, text.replace(/\s/g, ''));
                validateEmail(editEmailInput);
            });
        }

        // Phone field
        const editPhoneInput = document.getElementById('editEmployeePhone');
        if (editPhoneInput) {
            editPhoneInput.addEventListener('input', () => validatePhoneNumber(editPhoneInput));
            editPhoneInput.addEventListener('paste', (e) => {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text');
                document.execCommand('insertText', false, text.replace(/\D/g, ''));
                validatePhoneNumber(editPhoneInput);
            });
        }

        // Date of birth field
        const editDobInput = document.getElementById('editDateOfBirth');
        if (editDobInput) {
            // Set max date (today) and min date (100 years ago)
            const today = new Date();
            const minDate = new Date();
            minDate.setFullYear(today.getFullYear() - 100);
            
            editDobInput.max = today.toISOString().split('T')[0];
            editDobInput.min = minDate.toISOString().split('T')[0];
            
            editDobInput.addEventListener('change', function() {
                const selectedDate = new Date(this.value);
                const eighteenYearsAgo = new Date();
                eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
                
                if (selectedDate > eighteenYearsAgo) {
                    this.setCustomValidity('Employee must be at least 18 years old');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    }

    // Form submission handlers
    const addEmployeeForm = document.getElementById('addEmployeeAccountForm');
    if (addEmployeeForm) {
        addEmployeeForm.addEventListener('submit', function(event) {
            // Validate all fields before submission
            let isValid = true;
            
            // Validate required name fields
            const firstName = document.getElementById('firstName');
            const lastName = document.getElementById('lastName');
            if (!validateNameInput(firstName) || !validateNameInput(lastName)) {
                isValid = false;
            }
            
            // Validate email
            const email = document.getElementById('employeeEmail');
            if (!validateEmail(email)) {
                isValid = false;
            }
            
            // Validate phone
            const phone = document.getElementById('employeePhone');
            if (!validatePhoneNumber(phone)) {
                isValid = false;
            }
            
            // Validate date of birth
            const dob = document.getElementById('dateOfBirth');
            if (dob.value) {
                const selectedDate = new Date(dob.value);
                const eighteenYearsAgo = new Date();
                eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
                
                if (selectedDate > eighteenYearsAgo) {
                    dob.setCustomValidity('Employee must be at least 18 years old');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
                // Show validation messages
                addEmployeeForm.classList.add('was-validated');
            }
        });
    }

    const editEmployeeForm = document.getElementById('editEmployeeAccountForm');
    if (editEmployeeForm) {
        editEmployeeForm.addEventListener('submit', function(event) {
            // Validate all fields before submission
            let isValid = true;
            
            // Validate required name fields
            const firstName = document.getElementById('editFirstName');
            const lastName = document.getElementById('editLastName');
            if (!validateNameInput(firstName) || !validateNameInput(lastName)) {
                isValid = false;
            }
            
            // Validate email
            const email = document.getElementById('editEmployeeEmail');
            if (!validateEmail(email)) {
                isValid = false;
            }
            
            // Validate phone
            const phone = document.getElementById('editEmployeePhone');
            if (!validatePhoneNumber(phone)) {
                isValid = false;
            }
            
            // Validate date of birth
            const dob = document.getElementById('editDateOfBirth');
            if (dob.value) {
                const selectedDate = new Date(dob.value);
                const eighteenYearsAgo = new Date();
                eighteenYearsAgo.setFullYear(eighteenYearsAgo.getFullYear() - 18);
                
                if (selectedDate > eighteenYearsAgo) {
                    dob.setCustomValidity('Employee must be at least 18 years old');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                event.preventDefault();
                event.stopPropagation();
                // Show validation messages
                editEmployeeForm.classList.add('was-validated');
            }
        });
    }
});
</script>
  
</body>
</html>