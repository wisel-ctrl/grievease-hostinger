<?php
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


// Pagination settings
$perPage = 10; // Number of items per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1; // Current page
$offset = ($page - 1) * $perPage; // Offset for SQL query


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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<!-- Flatpickr JS -->
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
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
    <!-- Left Section -->
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Employee Management</h1>
    </div>

    <!-- Right Section (Button) -->
    <div>
      <button onclick="selectBranchForPayroll()" id="openPayrollModal" class="bg-[#D69E2E] hover:bg-[#B7791F] text-white font-semibold px-4 py-2 rounded-lg shadow-md transition">
        Record Payroll
      </button>
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
                <?php echo ucfirst(htmlspecialchars($branch['branch_name'])); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
          </div>
        </div>

        <!-- Archive Button -->
        <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover whitespace-nowrap"
                onclick="showArchivedEmployees()">
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
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-lg mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
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
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
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
            <p class="text-xs font-medium text-gray-500">Commission compensation</p>
            <p id="employeeBaseSalary" class="text-sm font-medium text-gray-800">-</p>
          </div>
          <div class="flex-1 px-1" id="monthlySalaryContainer" style="display: none;">
            <p class="text-xs font-medium text-gray-500">Monthly Salary</p>
            <p id="employeeMonthlySalary" class="text-sm font-medium text-gray-800">-</p>
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
              <p class="text-xs font-medium text-gray-500">Salary</p>
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
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
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
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
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

        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <p class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
            Payment Structure <span class="text-red-500">*</span>
          </p>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" name="paymentStructure" value="monthly" required class="mr-2 text-sidebar-accent focus:ring-sidebar-accent payment-radio">
              <i class="fas fa-calendar-alt mr-1 text-sidebar-accent"></i>
              Monthly
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" name="paymentStructure" value="commission" required class="mr-2 text-sidebar-accent focus:ring-sidebar-accent payment-radio">
              <i class="fas fa-percent mr-1 text-sidebar-accent"></i>
              Commission
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" name="paymentStructure" value="both" required class="mr-2 text-sidebar-accent focus:ring-sidebar-accent payment-radio">
              <i class="fas fa-random mr-1 text-sidebar-accent"></i>
              Both/Hybrid
            </label>
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
        </div>

        <!-- Monthly Salary Input (initially hidden) -->
        <div id="monthlySalaryContainerForAdding" class="hidden">
          <label for="monthlySalary" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Monthly Salary (₱) <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="monthlySalary" name="monthlySalary" step="0.01" min="0.01"
                class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Amount">
          </div>
        </div>

        <!-- Commission Salary Input (modified from original) -->
        <div id="commissionSalaryContainer">
          <label for="commissionSalary" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Pay per Commission (₱) <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="commissionSalary" name="commissionSalary" step="0.01" min="0.01"
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
                <span class="text-gray-700 font-medium"><?php echo htmlspecialchars(ucfirst($branch['branch_name'])); ?></span>
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
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
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
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
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

        <!-- Payment Structure Selection -->
        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
          <p class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
            Payment Structure <span class="text-red-500">*</span>
          </p>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" name="editPaymentStructure" value="monthly" required class="mr-2 text-sidebar-accent focus:ring-sidebar-accent edit-payment-radio">
              <i class="fas fa-calendar-alt mr-1 text-sidebar-accent"></i>
              Monthly
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" name="editPaymentStructure" value="commission" required class="mr-2 text-sidebar-accent focus:ring-sidebar-accent edit-payment-radio">
              <i class="fas fa-percent mr-1 text-sidebar-accent"></i>
              Commission
            </label>
            <label class="flex items-center bg-white p-2 rounded-md hover:bg-gray-100 transition-colors cursor-pointer border border-gray-200">
              <input type="radio" name="editPaymentStructure" value="both" required class="mr-2 text-sidebar-accent focus:ring-sidebar-accent edit-payment-radio">
              <i class="fas fa-random mr-1 text-sidebar-accent"></i>
              Both/Hybrid
            </label>
          </div>
        </div>
        
        <!-- Position -->
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

        <!-- Monthly Salary Input (initially hidden) -->
        <div id="editMonthlySalaryContainer" class="hidden">
          <label for="editMonthlySalary" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Monthly Salary (₱) <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="editMonthlySalary" name="monthlySalary" step="0.01" min="0.01"
                class="w-full pl-8 px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                placeholder="Amount">
          </div>
        </div>

        <!-- Commission Salary Input (modified from original) -->
        <div id="editCommissionSalaryContainer">
          <label for="editCommissionSalary" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            Pay per Commission (₱) <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <span class="text-gray-500">₱</span>
            </div>
            <input type="number" id="editCommissionSalary" name="commissionSalary" step="0.01" min="0.01"
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
                <span class="text-gray-700 font-medium"><?php echo htmlspecialchars(ucfirst($branch['branch_name'])); ?></span>
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

<!-- Archived Employees Modal -->
<div id="archivedEmployeesModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-sidebar-accent transition-colors" onclick="closeArchivedEmployeesModal()">
      <i class="fas fa-times text-xl"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-4 border-b bg-gradient-to-r from-sidebar-accent to-darkgold">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        <i class="fas fa-archive mr-2"></i> Archived Employees
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-4 overflow-y-auto modal-scroll-container">
      <div class="mb-4">
        <div class="relative">
          <input type="text" id="searchArchivedEmployees" 
                placeholder="Search archived employees..." 
                class="pl-10 pr-4 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                oninput="filterArchivedEmployees()">
          <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>
      </div>
      
      <div class="overflow-x-auto">
        <table class="w-full table-fixed">
          <thead>
            <tr class="bg-gray-50 border-b border-sidebar-border">
              <th class="w-24 px-4 py-3 text-left text-sm font-medium text-sidebar-text">ID</th>
              <th class="w-1/4 px-4 py-3 text-left text-sm font-medium text-sidebar-text">Name</th>
              <th class="w-1/3 px-4 py-3 text-left text-sm font-medium text-sidebar-text">Position</th>
              <th class="w-48 px-4 py-3 text-left text-sm font-medium text-sidebar-text">Actions</th>
            </tr>
          </thead>
          <tbody id="archivedEmployeesTableBody">
            <!-- Archived employees will be loaded here -->
            <tr>
              <td colspan="4" class="text-center py-4 text-gray-500">
                Loading archived employees...
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-6 py-3 flex justify-end border-t border-gray-200 sticky bottom-0 bg-white">
      <button type="button" onclick="closeArchivedEmployeesModal()" 
              class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition-colors">
        Close
      </button>
    </div>
  </div>
</div>

<!-- Branch Selection Modal -->
<div id="branchSelectionModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" onclick="closeBranchSelectionModal()" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        <i class="fas fa-map-marker-alt mr-2"></i>
        Select Branch
      </h3>
    </div>

    <!-- Modal Body -->
    <div class="p-4 sm:p-6 overflow-y-auto flex-1">
      <p class="text-sm text-gray-600 mb-4 text-center">Choose a branch to view payroll information:</p>
      
      <!-- Branch Options -->
      <div class="space-y-3">
        <!-- Pila Branch -->
        <button onclick="selectBranch('2')" class="w-full group">
          <div class="bg-white hover:bg-gray-50 border border-gray-200 rounded-lg p-4 transition-all duration-200">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="bg-sidebar-accent text-white rounded-full p-2 mr-3">
                  <i class="fas fa-building text-sm"></i>
                </div>
                <div class="text-left">
                  <h4 class="font-medium text-gray-800 group-hover:text-sidebar-accent">Pila Branch</h4>
                  <p class="text-xs text-gray-500">Main Office Location</p>
                </div>
              </div>
              <i class="fas fa-chevron-right text-gray-400 group-hover:text-sidebar-accent"></i>
            </div>
          </div>
        </button>

        <!-- Paete Branch -->
        <button onclick="selectBranch('1')" class="w-full group">
          <div class="bg-white hover:bg-gray-50 border border-gray-200 rounded-lg p-4 transition-all duration-200">
            <div class="flex items-center justify-between">
              <div class="flex items-center">
                <div class="bg-sidebar-accent text-white rounded-full p-2 mr-3">
                  <i class="fas fa-store-alt text-sm"></i>
                </div>
                <div class="text-left">
                  <h4 class="font-medium text-gray-800 group-hover:text-sidebar-accent">Paete Branch</h4>
                  <p class="text-xs text-gray-500">Secondary Location</p>
                </div>
              </div>
              <i class="fas fa-chevron-right text-gray-400 group-hover:text-sidebar-accent"></i>
            </div>
          </div>
        </button>
      </div>
    </div>

    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gray-50 border-t border-gray-200 flex justify-center">
      <button onclick="closeBranchSelectionModal()" class="px-6 py-2 border border-gray-300 bg-white text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors w-full sm:w-auto">
        Cancel
      </button>
    </div>
  </div>
</div>

<!-- Payroll Record Modal -->
<div id="payrollModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" id="closeModal" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Payroll Record
      </h3>
      
      <!-- Date Range Picker -->
      <div class="mt-3">
        <label class="block text-amber-100 text-sm font-medium mb-2">Select Payroll Period</label>
        <div class="flex flex-col sm:flex-row gap-2">
          <div class="flex flex-col sm:flex-row gap-2 flex-1">
            <div class="flex-1">
              <label class="text-amber-100 text-xs block mb-1">From Date</label>
              <input type="text" id="startDatePicker" 
                     class="w-full px-3 py-2 bg-white bg-opacity-20 border border-amber-300 rounded-lg text-white placeholder-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent text-sm"
                     placeholder="Start date">
            </div>
            <div class="flex-1">
              <label class="text-amber-100 text-xs block mb-1">To Date</label>
              <input type="text" id="endDatePicker" 
                     class="w-full px-3 py-2 bg-white bg-opacity-20 border border-amber-300 rounded-lg text-white placeholder-amber-200 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent text-sm"
                     placeholder="End date">
            </div>
          </div>
          <button id="applyDateRange" 
                  class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 text-white rounded-lg transition border border-amber-300 self-end mt-2 sm:mt-0 text-sm">
            Apply
          </button>
        </div>
        <p class="text-amber-100 mt-2 text-sm" id="currentMonth">November 2024</p>
      </div>
    </div>

    <!-- Modal Body -->
    <div class="p-4 sm:p-6 overflow-y-auto flex-1">
      <!-- Employee List -->
      <div class="mb-6">
        <h3 class="text-base font-semibold text-gray-700 mb-3 flex items-center">
          <span class="w-1.5 h-1.5 bg-sidebar-accent rounded-full mr-2"></span>
          Employee Payroll Details
        </h3>
        
        <div class="overflow-x-auto">
          <table class="w-full border border-gray-200 rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">Employee Name</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">Monthly Salary</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">Commission</th>
                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider border-b border-gray-200">Total Pay</th>
              </tr>
            </thead>
            <tbody id="employeeTableBody" class="bg-white divide-y divide-gray-200">
              <!-- Employee rows will be inserted here -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Summary Section -->
      <div class="bg-gray-50 rounded-lg p-4 sm:p-6 border border-gray-200">
        <h3 class="text-base font-semibold text-gray-700 mb-4 flex items-center">
          <span class="w-1.5 h-1.5 bg-sidebar-accent rounded-full mr-2"></span>
          Payroll Summary
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
          <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
            <p class="text-xs text-gray-500 mb-1">Total Monthly Salaries</p>
            <p class="text-lg font-semibold text-gray-800" id="totalMonthlySalary">₱0.00</p>
          </div>
          <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
            <p class="text-xs text-gray-500 mb-1">Total Commissions</p>
            <p class="text-lg font-semibold text-gray-800" id="totalCommissions">₱0.00</p>
          </div>
          <div class="bg-white p-3 rounded-lg border border-gray-200 text-center">
            <p class="text-xs text-gray-500 mb-1">Total Employees</p>
            <p class="text-lg font-semibold text-gray-800" id="totalEmployees">0</p>
          </div>
        </div>
        <div class="pt-4 border-t border-gray-200">
          <div class="text-center">
            <p class="text-sm text-gray-600 mb-1">Grand Total Payroll Expense</p>
            <p class="text-2xl font-bold text-sidebar-accent" id="grandTotal">₱0.00</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 bg-gray-50 border-t border-gray-200 flex flex-col sm:flex-row justify-between items-center gap-3">
      <p class="text-sm text-gray-500 text-center sm:text-left">
        Ready to record this expense in your books
      </p>
      <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">
        <button id="cancelBtn" class="px-4 py-2 border border-gray-300 bg-white text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors w-full sm:w-auto">
          Cancel
        </button>
        <button id="recordExpenseBtn" class="px-6 py-2 bg-sidebar-accent hover:bg-sidebar-accent-hover text-white rounded-lg font-medium transition-colors w-full sm:w-auto">
          Record Expense
        </button>
      </div>
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

// Function to show archived employees modal
function showArchivedEmployees() {
  const modal = document.getElementById('archivedEmployeesModal');
  modal.style.display = 'flex';
  
  // Load archived employees
  fetchArchivedEmployees();
}

// Function to close archived employees modal
function closeArchivedEmployeesModal() {
  document.getElementById('archivedEmployeesModal').style.display = 'none';
}

// Function to fetch archived employees
function fetchArchivedEmployees() {
  const tbody = document.getElementById('archivedEmployeesTableBody');
  tbody.innerHTML = `
    <tr>
      <td colspan="5" class="text-center py-4 text-gray-500">
        <i class="fas fa-spinner fa-spin mr-2"></i> Loading archived employees...
      </td>
    </tr>
  `;
  
  fetch('employeeManagement/get_archived_employees.php')
    .then(response => response.json())
    .then(data => {
      if (data && data.length > 0) {
        tbody.innerHTML = '';
        
        data.forEach(employee => {
          const row = document.createElement('tr');
          row.className = 'border-b border-gray-200 hover:bg-gray-50';
          row.innerHTML = `
            <td class="px-4 py-3 text-sm text-gray-700">#${employee.EmployeeID}</td>
            <td class="px-4 py-3 text-sm text-gray-700">
              ${employee.fname} ${employee.mname ? employee.mname + ' ' : ''}${employee.lname} ${employee.suffix || ''}
            </td>
            <td class="px-4 py-3 text-sm text-gray-700">${employee.position}</td>
            <td class="px-4 py-3 text-sm">
              <button class="px-3 py-1 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-colors"
                      onclick="unarchiveEmployee(${employee.EmployeeID}, '${employee.fname} ${employee.lname}')">
                <i class="fas fa-undo mr-1"></i> Unarchive
              </button>
            </td>
          `;
          tbody.appendChild(row);
        });
      } else {
        tbody.innerHTML = `
          <tr>
            <td colspan="5" class="text-center py-4 text-gray-500">
              No archived employees found
            </td>
          </tr>
        `;
      }
    })
    .catch(error => {
      console.error('Error fetching archived employees:', error);
      tbody.innerHTML = `
        <tr>
          <td colspan="5" class="text-center py-4 text-gray-500">
            Error loading archived employees
          </td>
        </tr>
      `;
    });
}

// Function to filter archived employees
function filterArchivedEmployees() {
  const searchTerm = document.getElementById('searchArchivedEmployees').value.toLowerCase();
  const rows = document.querySelectorAll('#archivedEmployeesTableBody tr');
  
  rows.forEach(row => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(searchTerm) ? '' : 'none';
  });
}

// Function to unarchive an employee
function unarchiveEmployee(employeeId, employeeName) {
  Swal.fire({
    title: 'Unarchive Employee',
    html: `Are you sure you want to restore <b>${employeeName}</b> to active status?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, unarchive',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      fetch('employeeManagement/unarchive_employee.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `employeeId=${employeeId}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire(
            'Success!',
            `Employee ${employeeName} has been restored.`,
            'success'
          ).then(() => {
            // Refresh the archived employees list
            fetchArchivedEmployees();
            // Optionally refresh the main employee table
            location.reload();
          });
        } else {
          Swal.fire(
            'Error!',
            data.message || 'Failed to unarchive employee.',
            'error'
          );
        }
      })
      .catch(error => {
        Swal.fire(
          'Error!',
          'An error occurred while unarchiving the employee.',
          'error'
        );
      });
    }
  });
}

// Update the terminateEmployee function to use SweetAlert
function terminateEmployee(employeeId) {
  Swal.fire({
    title: 'Archive Employee',
    text: "Are you sure you want to archive this employee?",
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#3085d6',
    cancelButtonColor: '#d33',
    confirmButtonText: 'Yes, archive',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      fetch('employeeManagement/terminate_employee.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `employeeId=${employeeId}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          Swal.fire(
            'Archived!',
            'The employee has been archived.',
            'success'
          ).then(() => {
            location.reload();
          });
        } else {
          Swal.fire(
            'Error!',
            data.message || 'Failed to archive employee.',
            'error'
          );
        }
      })
      .catch(error => {
        Swal.fire(
          'Error!',
          'An error occurred while archiving the employee.',
          'error'
        );
      });
    }
  });
}
</script>

  <script src="script.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the form elements
    const dateOfBirthInput = document.getElementById('dateOfBirth');
    const addEmployeeForm = document.getElementById('addEmployeeAccountForm');
    const paymentStructure = document.querySelector('input[name="paymentStructure"]:checked').value;
    const monthlySalary = document.getElementById('monthlySalary');
    const commissionSalary = document.getElementById('commissionSalary');

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
        const salary = parseFloat(monthlySalary.value);
        if (salary > 10000) {
            alert('Salary must be less than or equal to ₱10,000');
            monthlySalary.focus();
            event.preventDefault();
            return;
        }

        if (paymentStructure === 'monthly' || paymentStructure === 'both') {
          if (!monthlySalary.value) {
            e.preventDefault();
            alert('Monthly salary is required');
            return;
          }
        }
        
        if (paymentStructure === 'commission' || paymentStructure === 'both') {
          if (!commissionSalary.value) {
            e.preventDefault();
            alert('Commission salary is required');
            return;
          }
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
            Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                closeAddEmployeeModal();
                location.reload();
                addEmployeeAccountForm.reset();
            });
        } else {
            // Error handling
            console.error('Error:', data);
            if (data.errors) {
                // Display validation errors
                Swal.fire({
                    title: 'Validation Error',
                    text: data.errors.join('\n'),
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.message || 'An error occurred',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        }
    })
    .catch(error => {
        console.error('Network or server error:', error);
        Swal.fire({
            title: 'Network Error',
            text: 'An unexpected error occurred. Please try again.',
            icon: 'error',
            confirmButtonText: 'OK'
        });
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
        
        // Set gender radio button
        if (data.gender === 'Male') {
          document.getElementById('editGenderMale').checked = true;
        } else if (data.gender === 'Female') {
          document.getElementById('editGenderFemale').checked = true;
        }

        setEditPaymentStructure(
          data.pay_structure, 
          data.monthly_salary, 
          data.base_salary
        );
        
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
    Swal.fire({
        title: 'Are you sure?',
        text: 'Are you sure you want to terminate this employee?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes',
        cancelButtonText: 'No'
    }).then((result) => {
        if (result.isConfirmed) {
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
                            Swal.fire(
                                'Success',
                                `Employee ${employeeId} terminated successfully!`,
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error',
                                'Error: ' + response.message,
                                'error'
                            );
                        }
                    } else {
                        Swal.fire(
                            'Error',
                            'Error terminating employee',
                            'error'
                        );
                    }
                }
            };
            
            // Send the employee ID to the PHP script
            xhr.send("employeeId=" + encodeURIComponent(employeeId));
        }
    });
}

    // Function to reinstate an employee
    function reinstateEmployee(employeeId) {
    Swal.fire({
        title: 'Rehire Employee',
        text: 'Are you sure you want to rehire this employee?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, rehire them!'
    }).then((result) => {
        if (result.isConfirmed) {
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
                            Swal.fire(
                                'Reinstated!',
                                `Employee ${employeeId} reinstated successfully!`,
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Error!',
                                response.message,
                                'error'
                            );
                        }
                    } else {
                        Swal.fire(
                            'Error!',
                            'Error rehiring employee',
                            'error'
                        );
                    }
                }
            };
            
            // Send the employee ID to the PHP script
            xhr.send("employeeId=" + encodeURIComponent(employeeId));
        }
    });
}

  </script>

  <script>
// Global variable to store current employee ID
let currentEmployeeId = null;

// Function to view employee salary details
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
        
        // Handle different pay structures
        const payStructure = data.pay_structure || "commissioned";
        const monthlySalaryContainer = document.getElementById('monthlySalaryContainer');
        const employeeBaseSalaryEl = document.getElementById('employeeBaseSalary');
        const modalBaseSalaryEl = document.getElementById('modalBaseSalary');
        
        // Format base salary
        const baseSalary = parseFloat(data.base_salary || 0);
        employeeBaseSalaryEl.textContent = formatPrice(baseSalary);
        modalBaseSalaryEl.textContent = formatPrice(baseSalary);
        
        // Handle monthly salary display based on pay structure
        if (payStructure === "monthly") {
          // Hide base salary, show monthly salary
          employeeBaseSalaryEl.textContent = "-";
          modalBaseSalaryEl.textContent = formatPrice(parseFloat(data.monthly_salary || 0));
          monthlySalaryContainer.style.display = 'block';
          document.getElementById('employeeMonthlySalary').textContent = formatPrice(parseFloat(data.monthly_salary || 0));
        } 
        else if (payStructure === "both") {
          // Show both base and monthly salary
          monthlySalaryContainer.style.display = 'block';
          document.getElementById('employeeMonthlySalary').textContent = formatPrice(parseFloat(data.monthly_salary || 0));
        }
        else {
          // Commissioned only - default behavior
          monthlySalaryContainer.style.display = 'none';
        }
        
        // Rest of your date handling code remains the same...
        // Set default date range (last 30 days)
        const today = new Date();
        const thirtyDaysAgo = new Date();
        thirtyDaysAgo.setDate(today.getDate() - 30);
        
        // Set max date for both inputs to today
        const startDateInput = document.getElementById('startDate');
        const endDateInput = document.getElementById('endDate');
        
        startDateInput.max = today.toISOString().split('T')[0];
        endDateInput.max = today.toISOString().split('T')[0];
        
        // Set default values
        startDateInput.valueAsDate = thirtyDaysAgo;
        endDateInput.valueAsDate = today;
        
        // Add event listeners for date validation
        startDateInput.addEventListener('change', function() {
          const selectedStartDate = new Date(this.value);
          const endDate = new Date(endDateInput.value);
          
          // Ensure end date is not before start date
          if (endDate < selectedStartDate) {
            endDateInput.value = this.value;
          }
          
          // Set min date for end date
          endDateInput.min = this.value;
        });
        
        endDateInput.addEventListener('change', function() {
          const selectedEndDate = new Date(this.value);
          const startDate = new Date(startDateInput.value);
          
          // Ensure end date is not before start date
          if (selectedEndDate < startDate) {
            this.value = startDateInput.value;
          }
        });
        
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
        document.getElementById('totalEarnings').textContent = formatPrice(data.total_earnings || 0);
        
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
              <td class="p-3 text-sm text-gray-700">${formatPrice(service.service_income)}</td>
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


// Add this function to validate search input
function validateSearchInput(inputElement) {
    if (!inputElement) return;
    
    inputElement.addEventListener('input', function() {
        let value = this.value;
        
        // Don't allow consecutive spaces
        if (/\s{2,}/.test(value)) {
            this.value = value.replace(/\s{2,}/g, ' ');
            return;
        }
        
        // Don't allow space as first character
        if (value.startsWith(' ')) {
            this.value = value.substring(1);
            return;
        }
        
        // Only allow space after at least 2 characters
        if (value.length < 2 && value.includes(' ')) {
            this.value = value.replace(/\s/g, '');
            return;
        }
    });
    
    // Prevent paste of content with invalid spacing
    inputElement.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        
        // Clean the pasted text
        let cleanedText = pastedText;
        
        // Remove consecutive spaces
        cleanedText = cleanedText.replace(/\s{2,}/g, ' ');
        
        // Remove leading space
        if (cleanedText.startsWith(' ')) {
            cleanedText = cleanedText.substring(1);
        }
        
        // Remove spaces before 2 characters
        if (cleanedText.length < 2 && cleanedText.includes(' ')) {
            cleanedText = cleanedText.replace(/\s/g, '');
        }
        
        document.execCommand('insertText', false, cleanedText);
    });
}

// Apply validation to all search inputs in this file
document.addEventListener('DOMContentLoaded', function() {
    // Employee search inputs
    const searchEmployees = document.getElementById('searchEmployees');
    const searchArchivedEmployees = document.getElementById('searchArchivedEmployees');
    
    // Apply validation to all search inputs
    validateSearchInput(searchEmployees);
    validateSearchInput(searchArchivedEmployees);
});
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

function formatPrice(amount) {
    return "₱" + Number(amount).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Payment structure radio button logic
document.addEventListener('DOMContentLoaded', function() {
  const paymentRadios = document.querySelectorAll('.payment-radio');
  const monthlyContainer = document.getElementById('monthlySalaryContainerForAdding');
  const commissionContainer = document.getElementById('commissionSalaryContainer');
  
  paymentRadios.forEach(radio => {
    radio.addEventListener('change', function() {
      if (this.value === 'monthly') {
        monthlyContainer.classList.remove('hidden');
        commissionContainer.classList.add('hidden');
      } else if (this.value === 'commission') {
        monthlyContainer.classList.add('hidden');
        commissionContainer.classList.remove('hidden');
      } else if (this.value === 'both') {
        monthlyContainer.classList.remove('hidden');
        commissionContainer.classList.remove('hidden');
      }
    });
  });
  
  // Set initial state based on default selected radio
  const defaultSelected = document.querySelector('.payment-radio:checked');
  if (defaultSelected) {
    defaultSelected.dispatchEvent(new Event('change'));
  }
});

// Edit modal payment structure radio button logic
document.addEventListener('DOMContentLoaded', function() {
  const editPaymentRadios = document.querySelectorAll('.edit-payment-radio');
  const editMonthlyContainer = document.getElementById('editMonthlySalaryContainer');
  const editCommissionContainer = document.getElementById('editCommissionSalaryContainer');
  
  editPaymentRadios.forEach(radio => {
    radio.addEventListener('change', function() {
      if (this.value === 'monthly') {
        editMonthlyContainer.classList.remove('hidden');
        editCommissionContainer.classList.add('hidden');
      } else if (this.value === 'commission') {
        editMonthlyContainer.classList.add('hidden');
        editCommissionContainer.classList.remove('hidden');
      } else if (this.value === 'both') {
        editMonthlyContainer.classList.remove('hidden');
        editCommissionContainer.classList.remove('hidden');
      }
    });
  });
  
  // Function to set payment structure when editing an employee
  window.setEditPaymentStructure = function(paymentType, monthlySalary, commissionSalary) {
    const radio = document.querySelector(`input[name="editPaymentStructure"][value="${paymentType}"]`);
    if (radio) {
      radio.checked = true;
      radio.dispatchEvent(new Event('change'));
      
      if (monthlySalary) {
        document.getElementById('editMonthlySalary').value = monthlySalary;
      }
      
      if (commissionSalary) {
        document.getElementById('editCommissionSalary').value = commissionSalary;
      }
    }
  };
});
</script>

<script>
// Function to format currency
function formatCurrency(amount) {
    return '₱' + amount.toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

document.addEventListener('DOMContentLoaded', function() {
  const modal = document.getElementById('payrollModal');
  const branchModal = document.getElementById('branchSelectionModal');
  const closeModal = document.getElementById('closeModal');
  const cancelBtn = document.getElementById('cancelBtn');
  const recordExpenseBtn = document.getElementById('recordExpenseBtn');
  const startDatePicker = document.getElementById('startDatePicker');
  const endDatePicker = document.getElementById('endDatePicker');
  const applyDateRangeBtn = document.getElementById('applyDateRange');

  let selectedBranchId = null;
  let selectedDateRange = {
      startDate: null,
      endDate: null
  };

  let startDateFP, endDateFP;

  startDateFP = flatpickr(startDatePicker, {
      dateFormat: "Y-m-d",
      maxDate: "today",
      onChange: function(selectedDates, dateStr, instance) {
          selectedDateRange.startDate = selectedDates[0];
          if (selectedDates[0]) {
              endDateFP.set('minDate', selectedDates[0]);
              // Auto-set end date to same month if start date is selected
              autoSetEndDate(selectedDates[0]);
          }
      }
  });

  endDateFP = flatpickr(endDatePicker, {
      dateFormat: "Y-m-d",
      maxDate: "today",
      onChange: function(selectedDates, dateStr, instance) {
          selectedDateRange.endDate = selectedDates[0];
          if (selectedDates[0]) {
              startDateFP.set('maxDate', selectedDates[0]);
          }
      }
  });

  // Auto-set end date to end of same month as start date
  function autoSetEndDate(startDate) {
      if (!selectedDateRange.endDate || 
          selectedDateRange.endDate.getMonth() !== startDate.getMonth() ||
          selectedDateRange.endDate.getFullYear() !== startDate.getFullYear()) {
          
          const endOfMonth = new Date(startDate.getFullYear(), startDate.getMonth() + 1, 0);
          endDateFP.setDate(endOfMonth);
          selectedDateRange.endDate = endOfMonth;
      }
  }

  // Function to reset date pickers
  function resetDatePickers() {
      startDateFP.clear();
      endDateFP.clear();
      startDateFP.set('maxDate', null);
      endDateFP.set('minDate', null);
      selectedDateRange = { startDate: null, endDate: null };
  }

  // Function to show branch selection modal
  function selectBranchForPayroll() {
      branchModal.classList.remove('hidden');
  }

  // Function to hide branch selection modal
  function closeBranchSelectionModal() {
      branchModal.classList.add('hidden');
  }

  // Function to select branch and open payroll modal
  function selectBranch(branchId) {
      selectedBranchId = branchId;
      closeBranchSelectionModal();
      openPayrollModal(branchId);
  }
  
  // Function to open modal and load data
  function openPayrollModal(branchId) {
    modal.classList.remove('hidden');
    resetDatePickers();
    loadPayrollData(branchId); // Pass branchId to load function
  }
  
  // Function to close modal
  function closePayrollModal() {
    resetDatePickers(); // Reset date pickers when closing modal
    modal.classList.add('hidden');
  }
  
  // Event listeners
  closeModal.addEventListener('click', closePayrollModal);
  cancelBtn.addEventListener('click', closePayrollModal);
  
  // Apply date range button
  applyDateRangeBtn.addEventListener('click', function() {
      if (selectedDateRange.startDate && selectedDateRange.endDate) {
          loadPayrollData(selectedBranchId, selectedDateRange);
      } else {
          alert('Please select both start date and end date');
      }
  });
  
  // Event listener for recording expense
  recordExpenseBtn.addEventListener('click', async function() {
      // Get the grand total from the summary
      const grandTotalText = document.getElementById('grandTotal').textContent;
      const grandTotal = parseFloat(grandTotalText.replace('₱', '').replace(/,/g, ''));
      
      // Get the branch_id from your branch selection
      const branchId = selectedBranchId;
      
      if (!branchId) {
          alert('Please select a branch first!');
          return;
      }
      
      if (grandTotal <= 0) {
          alert('No payroll expense to record!');
          return;
      }
      
      try {
          // Show loading state
          recordExpenseBtn.disabled = true;
          recordExpenseBtn.textContent = 'Recording...';
          
          // FIXED: Prepare date parameters without timezone conversion issues
          let startDateParam = null;
          let endDateParam = null;
          
          if (selectedDateRange.startDate && selectedDateRange.endDate) {
              // Use local date formatting to avoid timezone issues
              const formatDateForAPI = (date) => {
                  const year = date.getFullYear();
                  const month = String(date.getMonth() + 1).padStart(2, '0');
                  const day = String(date.getDate()).padStart(2, '0');
                  return `${year}-${month}-${day}`;
              };
              
              startDateParam = formatDateForAPI(selectedDateRange.startDate);
              endDateParam = formatDateForAPI(selectedDateRange.endDate);
              
              console.log('Sending dates to server:', { startDateParam, endDateParam });
          }
          
          const response = await fetch('employeeManagement/record_payroll_expense.php', {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                  branch_id: branchId,
                  grand_total: grandTotal,
                  start_date: startDateParam,
                  end_date: endDateParam
              })
          });
          
          // Check if response is OK before parsing JSON
          if (!response.ok) {
              throw new Error(`HTTP error! status: ${response.status}`);
          }
          
          const data = await response.json();
          
          if (data.success) {
              alert(`✅ Payroll expense recorded successfully!\nExpense: ${data.expense_name}\nAmount: ₱${grandTotal.toLocaleString('en-PH')}\nBranch: ${branchId}`);
              closePayrollModal();
          } else {
              alert('Error recording expense: ' + data.message);
          }
          
      } catch (error) {
          console.error('Error recording expense:', error);
          alert('Error recording expense. Please check console for details and try again.');
      } finally {
          // Reset button state
          recordExpenseBtn.disabled = false;
          recordExpenseBtn.textContent = 'Record Expense';
      }
  });
  
  // Load payroll data from API
  async function loadPayrollData(branchId, dateRange = null) {
    try {
      let url = `employeeManagement/payroll.php?branch_id=${branchId}`;
      
      // Add date range parameters if provided
      if (dateRange && dateRange.startDate && dateRange.endDate) {
          const startDateStr = dateRange.startDate.getFullYear() + '-' + 
                              String(dateRange.startDate.getMonth() + 1).padStart(2, '0') + '-' + 
                              String(dateRange.startDate.getDate()).padStart(2, '0');
                              
          const endDateStr = dateRange.endDate.getFullYear() + '-' + 
                            String(dateRange.endDate.getMonth() + 1).padStart(2, '0') + '-' + 
                            String(dateRange.endDate.getDate()).padStart(2, '0');
          
          url += `&start_date=${encodeURIComponent(startDateStr)}&end_date=${encodeURIComponent(endDateStr)}`;
          
          // Update display to show date range
          const startFormatted = dateRange.startDate.toLocaleDateString('en-PH', { 
              month: 'short', 
              day: 'numeric' 
          });
          const endFormatted = dateRange.endDate.toLocaleDateString('en-PH', { 
              month: 'short', 
              day: 'numeric',
              year: 'numeric'
          });
          document.getElementById('currentMonth').textContent = 
              `${startFormatted} - ${endFormatted}`;
      } else {
          updateCurrentMonth();
      }
      
      const response = await fetch(url);
      const data = await response.json();
      
      if (data.success) {
        populateEmployeeTable(data.employees);
        updateSummary(data.summary);
      } else {
        console.error('Error loading payroll data:', data.message);
        alert('Error loading payroll data: ' + data.message);
      }
    } catch (error) {
      console.error('Error fetching payroll data:', error);
      alert('Error fetching payroll data. Please try again.');
    }
  }

  // Populate employee table (no frontend calculation needed - backend handles it)
  function populateEmployeeTable(employees) {
    const tableBody = document.getElementById('employeeTableBody');
    tableBody.innerHTML = '';
    
    employees.forEach(employee => {
      const row = document.createElement('tr');
      row.className = 'border-b border-amber-200 hover:bg-amber-50 transition';
      row.innerHTML = `
        <td class="px-4 py-3 text-gray-700">${employee.full_name}</td>
        <td class="px-4 py-3 text-right text-gray-700">${formatCurrency(parseFloat(employee.monthly_salary))}</td>
        <td class="px-4 py-3 text-right text-gray-700">${formatCurrency(parseFloat(employee.commission_salary))}</td>
        <td class="px-4 py-3 text-right font-semibold text-gray-800">${formatCurrency(parseFloat(employee.total_salary))}</td>
      `;
      tableBody.appendChild(row);
    });
  }

  // Calculate proration factor based on date range
  function calculateProrationFactor(startDate, endDate) {
    // Calculate total days in the selected range
    const timeDiff = endDate.getTime() - startDate.getTime();
    const daysInRange = Math.ceil(timeDiff / (1000 * 3600 * 24)) + 1; // +1 to include both start and end dates
    
    // Get the number of days in the current month of the start date
    const year = startDate.getFullYear();
    const month = startDate.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    // Calculate proration factor
    const prorationFactor = daysInRange / daysInMonth;
    
    console.log(`Date Range: ${startDate.toDateString()} to ${endDate.toDateString()}`);
    console.log(`Days in range: ${daysInRange}, Days in month: ${daysInMonth}, Proration factor: ${prorationFactor}`);
    
    return prorationFactor;
  }
  
  // Update summary section
  function updateSummary(summary) {
    document.getElementById('totalMonthlySalary').textContent = `₱${parseFloat(summary.total_monthly_salary).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
    document.getElementById('totalCommissions').textContent = `₱${parseFloat(summary.total_commission_salary).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
    document.getElementById('totalEmployees').textContent = summary.total_employees;
    document.getElementById('grandTotal').textContent = `₱${parseFloat(summary.total_salary).toLocaleString('en-PH', { minimumFractionDigits: 2 })}`;
  }
  
  // Update current month display
  function updateCurrentMonth() {
    const now = new Date();
    const month = now.toLocaleString('default', { month: 'long' });
    const year = now.getFullYear();
    document.getElementById('currentMonth').textContent = `${month} ${year}`;
  }
  
  // Make the open function available globally
  window.openPayrollModal = openPayrollModal;
  window.selectBranchForPayroll = selectBranchForPayroll;
  window.selectBranch = selectBranch;
  window.closeBranchSelectionModal = closeBranchSelectionModal;
});
</script>

<footer class="bg-white rounded-lg shadow-sidebar border border-sidebar-border p-4 text-center text-sm text-gray-500 mt-8">
    <p>© 2025 GrievEase.</p>
  </footer>
  
</body>
</html>