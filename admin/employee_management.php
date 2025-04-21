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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employees - GrievEase</title>
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
    <div class="flex space-x-3">
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-bell"></i>
      </button>
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-cog"></i>
      </button>
    </div>
  </div>

<!-- View Employee Details Section -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
    <!-- Employee Header with Search and Filters -->
    <div class="bg-sidebar-hover p-4 border-b border-sidebar-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3">
            <h3 class="text-lg font-bold text-sidebar-text">Employee Details</h3>
            
            <?php
// First, execute a dedicated count query for employees
$employeeCountQuery = "SELECT COUNT(*) as total FROM employee_tb";
$employeeCountResult = $conn->query($employeeCountQuery);
if (!$employeeCountResult) {
    die("Employee count query failed: " . $conn->error);
}
$totalEmployees = $employeeCountResult->fetch_assoc()['total'] ?? 0; // Default to 0 if fetch fails
?>

<span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
    <i class="fas fa-users"></i>
    <?php echo $totalEmployees . " Employee" . ($totalEmployees != 1 ? "s" : ""); ?>
</span>
        </div>
        
        <!-- Search and Filter Section -->
        <div class="flex flex-col md:flex-row items-start md:items-center gap-3 w-full md:w-auto">
            <!-- Search Input -->
            <div class="relative w-full md:w-64">
                <input type="text" id="searchEmployees" 
                       placeholder="Search employees..." 
                       class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent"
                       oninput="debouncedFilterEmployees()">
                <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
            </div>

            <!-- Branch Filter Dropdown -->
            <div class="relative w-full md:w-auto">
                <select id="branchFilter" onchange="filterByBranch(this.value)" class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 focus:outline-none focus:ring-2 focus:ring-sidebar-accent text-sm w-full md:w-auto">
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

            <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap" 
                    onclick="openAddEmployeeModal()">
                <i class="fas fa-plus-circle"></i> Add Employee
            </button>
        </div>
    </div>
    
    <!-- Employee Table -->
    <div class="overflow-x-auto scrollbar-thin" id="employeeTableContainer">
        <div id="loadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
        </div>
        
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-sidebar-border">
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(0)">
                        <div class="flex items-center">
                            <i class="fas fa-hashtag mr-1.5 text-sidebar-accent"></i> ID 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(1)">
                        <div class="flex items-center">
                            <i class="fas fa-user mr-1.5 text-sidebar-accent"></i> Name 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(2)">
                        <div class="flex items-center">
                            <i class="fas fa-briefcase mr-1.5 text-sidebar-accent"></i> Position 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(3)">
                        <div class="flex items-center">
                            <i class="fas fa-peso-sign mr-1.5 text-sidebar-accent"></i> Base Salary 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer" onclick="sortTable(4)">
                        <div class="flex items-center">
                            <i class="fas fa-toggle-on mr-1.5 text-sidebar-accent"></i> Status 
                            <i class="fas fa-sort ml-1 text-gray-400"></i>
                        </div>
                    </th>
                    <th class="p-4 text-left text-sm font-medium text-sidebar-text">
                        <div class="flex items-center">
                            <i class="fas fa-cogs mr-1.5 text-sidebar-accent"></i> Actions
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
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $branch_filter);
                        $stmt->execute();
                        $result = $stmt->get_result();
                    } else {
                        $result = $conn->query($sql);
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
                            echo "<td class='p-4 text-sm text-sidebar-text font-medium'>#" . htmlspecialchars($row['EmployeeID']) . "</td>";
                            echo "<td class='p-4 text-sm text-sidebar-text'>" . htmlspecialchars($full_name) . "</td>";
                            echo "<td class='p-4 text-sm text-sidebar-text'>";
                            echo "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100'>" . htmlspecialchars($position) . "</span>";
                            echo "</td>";
                            echo "<td class='p-4 text-sm font-medium text-sidebar-text'>$" . number_format($row['base_salary'], 2) . "</td>";
                            
                            echo "<td class='p-4 text-sm'>";
                            echo "<span class='inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium " . $statusClass . "'>";
                            echo "<i class='fas " . $statusIcon . " mr-1'></i> " . htmlspecialchars($status) . "</span>";
                            echo "</td>";
                            
                            // Actions column with styled buttons
                            echo "<td class='p-4 text-sm'>";
                            echo "<div class='flex space-x-2'>";
                            echo "<button class='p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip' title='Edit Employee' onclick='openEditEmployeeModal(\"" . htmlspecialchars($row['EmployeeID']) . "\")'>";
                            echo "<i class='fas fa-edit'></i></button>";
                            
                            if (strtolower($row['status']) == 'active') {
                                echo "<button class='p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip' title='Terminate Employee' onclick='terminateEmployee(\"" . htmlspecialchars($row['EmployeeID']) . "\")'>";
                                echo "<i class='fas fa-trash-alt'></i></button>";
                            } else {
                                echo "<button class='p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip' title='Reinstate Employee' onclick='reinstateEmployee(\"" . htmlspecialchars($row['EmployeeID']) . "\")'>";
                                echo "<i class='fas fa-check'></i></button>";
                            }
                            
                            echo "<button class='p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 transition-all tooltip' title='View Details' onclick='viewEmployeeDetails(\"" . htmlspecialchars($row['EmployeeID']) . "\")'>";
                            echo "<i class='fas fa-eye'></i></button>";
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
        
        <!-- Pagination (assuming you'd want to add pagination) -->
        <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
            <div class="text-sm text-gray-500">
                <?php if (isset($result) && $result->num_rows > 0): ?>
                    Showing 1 - <?php echo $result->num_rows; ?> of <?php echo $result->num_rows; ?> employees
                <?php else: ?>
                    No employees found
                <?php endif; ?>
            </div>
            <div class="flex space-x-1">
                <!-- Pagination would go here if needed -->
                <!-- This is a placeholder for future pagination implementation -->
                <!-- Similar to the first code's pagination structure -->
            </div>
        </div>
    </div>
</div>

<!-- View Employee Salary Details Modal -->
<div id="viewEmployeeModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeViewEmployeeModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        <i class="fas fa-money-bill-wave mr-2"></i>
        Employee Salary Details
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5">
      <div class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <div>
            <p class="text-xs font-medium text-gray-500">Employee ID</p>
            <p id="employeeId" class="text-sm font-medium text-gray-800">-</p>
          </div>
          <div>
            <p class="text-xs font-medium text-gray-500">Employee Name</p>
            <p id="employeeName" class="text-sm font-medium text-gray-800">-</p>
          </div>
          <div>
            <p class="text-xs font-medium text-gray-500">Base Salary</p>
            <p id="employeeBaseSalary" class="text-sm font-medium text-gray-800">-</p>
          </div>
        </div>
        
        <!-- Date Range Picker -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-4">
          <h4 class="text-sm font-medium text-gray-700 mb-3">Select Date Range</h4>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label for="startDate" class="block text-xs font-medium text-gray-700 mb-1">From Date</label>
              <input type="date" id="startDate" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
            <div>
              <label for="endDate" class="block text-xs font-medium text-gray-700 mb-1">To Date</label>
              <input type="date" id="endDate" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200">
            </div>
            <div class="flex items-end">
              <button onclick="fetchEmployeeSalary()" class="px-4 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center">
                <i class="fas fa-search mr-2"></i> Search
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Salary Summary -->
      <div class="mb-6">
        <h4 class="text-sm font-medium text-gray-700 mb-2">Salary Summary</h4>
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <p class="text-xs font-medium text-gray-500">Total Services</p>
              <p id="totalServices" class="text-lg font-bold text-gray-800">0</p>
            </div>
            <div>
              <p class="text-xs font-medium text-gray-500">Total Earnings</p>
              <p id="totalEarnings" class="text-lg font-bold text-green-600">₱0.00</p>
            </div>
            <div>
              <p class="text-xs font-medium text-gray-500">Base Salary</p>
              <p id="modalBaseSalary" class="text-lg font-bold text-blue-600">₱0.00</p>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Service Details Table -->
      <div>
        <h4 class="text-sm font-medium text-gray-700 mb-2">Service Details</h4>
        <div class="overflow-x-auto">
          <table class="w-full border-collapse">
            <thead>
              <tr class="bg-gray-100">
                <th class="p-3 text-left text-xs font-medium text-gray-700">Date</th>
                <th class="p-3 text-left text-xs font-medium text-gray-700">Service</th>
                <th class="p-3 text-left text-xs font-medium text-gray-700">Service Income</th>
              </tr>
            </thead>
            <tbody id="serviceDetailsBody">
              <!-- Service details will be populated here -->
              <tr>
                <td colspan="4" class="text-center p-4 text-gray-500">Select a date range to view service details</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-6 py-4 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button type="button" onclick="closeViewEmployeeModal()" class="px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
        <i class="fas fa-times mr-2"></i>
        Close
      </button>
    </div>
  </div>
</div>

<!-- Add Employee Modal -->
<div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="addEmployeeModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeAddEmployeeModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        <i class="fas fa-user-plus mr-2"></i>
        Add Employee Account
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5">
      <form id="addEmployeeAccountForm" class="space-y-5">
        <!-- Name Fields -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="firstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-user mr-2 text-sidebar-accent"></i>
              First Name *
            </label>
            <div class="relative">
              <input type="text" id="firstName" name="firstName" required
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="First Name" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
            </div>
          </div>
          <div>
            <label for="lastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-user mr-2 text-sidebar-accent"></i>
              Last Name *
            </label>
            <div class="relative">
              <input type="text" id="lastName" name="lastName" required
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Last Name" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
            </div>
          </div>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="middleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-user mr-2 text-sidebar-accent"></i>
              Middle Name
            </label>
            <div class="relative">
              <input type="text" id="middleName" name="middleName"
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Middle Name" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
            </div>
          </div>
          <div>
            <label for="suffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-user-tag mr-2 text-sidebar-accent"></i>
              Suffix <span class="text-xs text-gray-500">(Optional)</span>
            </label>
            <div class="relative">
              <input type="text" id="suffix" name="suffix"
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="e.g., Jr., Sr.">
            </div>
          </div>
        </div>

        <!-- Date of Birth Field -->
        <div>
          <label for="dateOfBirth" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            <i class="fas fa-calendar-alt mr-2 text-sidebar-accent"></i>
            Date of Birth *
          </label>
          <div class="relative">
            <input type="date" id="dateOfBirth" name="dateOfBirth" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
          </div>
        </div>

        <!-- Contact Information -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="employeeEmail" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-envelope mr-2 text-sidebar-accent"></i>
              Email Address *
            </label>
            <div class="relative">
              <input type="email" id="employeeEmail" name="employeeEmail" required
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Email">
            </div>
          </div>
          <div>
            <label for="employeePhone" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-phone-alt mr-2 text-sidebar-accent"></i>
              Phone Number *
            </label>
            <div class="relative">
              <input type="tel" id="employeePhone" name="employeePhone" required
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="09XXXXXXXXX or +63XXXXXXXXXX" pattern="(\+63|0)\d{10}" title="Philippine phone number (09XXXXXXXXX or +63XXXXXXXXXX)">
            </div>
          </div>
        </div>

        <!-- Position and Salary -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="employeePosition" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-briefcase mr-2 text-sidebar-accent"></i>
              Position *
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
          <div>
            <label for="employeeSalary" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-money-bill-wave mr-2 text-sidebar-accent"></i>
              Salary per Service (₱) *
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
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
          <p class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
            <i class="fas fa-venus-mars mr-2 text-sidebar-accent"></i>
            Gender *
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
        <div class="bg-gray-50 p-4 rounded-lg border-l-4 border-gold">
          <label class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
            <i class="fas fa-building mr-2 text-sidebar-accent"></i>
            Branch Location *
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
    <div class="px-6 py-4 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center" onclick="closeAddEmployeeModal()">
        <i class="fas fa-times mr-2"></i>
        Cancel
      </button>
      <button type="submit" form="addEmployeeAccountForm" class="px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
        <i class="fas fa-user-plus mr-2"></i>
        Add Employee
      </button>
    </div>
  </div>
</div>

<div class="fixed inset-0 z-50 flex items-center justify-center hidden" id="editEmployeeModal">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeEditEmployeeModal()">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-6 py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-xl font-bold text-white flex items-center">
        <i class="fas fa-user-edit mr-2"></i>
        Edit Employee Account
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-6 py-5">
      <form id="editEmployeeAccountForm" class="space-y-5">
        <!-- Hidden field for employee ID -->
        <input type="hidden" id="editEmployeeId" name="employeeId">
        
        <!-- Name Fields -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="editFirstName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-user mr-2 text-sidebar-accent"></i>
              First Name *
            </label>
            <div class="relative">
              <input type="text" id="editFirstName" name="firstName" required
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="First Name" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
            </div>
          </div>
          <div>
            <label for="editLastName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-user mr-2 text-sidebar-accent"></i>
              Last Name *
            </label>
            <div class="relative">
              <input type="text" id="editLastName" name="lastName" required
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Last Name" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
            </div>
          </div>
        </div>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="editMiddleName" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-user mr-2 text-sidebar-accent"></i>
              Middle Name
            </label>
            <div class="relative">
              <input type="text" id="editMiddleName" name="middleName"
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Middle Name" pattern="[A-Za-z\s]+" title="Only letters and spaces allowed">
            </div>
          </div>
          <div>
            <label for="editSuffix" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-user-tag mr-2 text-sidebar-accent"></i>
              Suffix <span class="text-xs text-gray-500">(Optional)</span>
            </label>
            <div class="relative">
              <input type="text" id="editSuffix" name="suffix"
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="e.g., Jr., Sr.">
            </div>
          </div>
        </div>

        <!-- Date of Birth Field -->
        <div>
          <label for="editDateOfBirth" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
            <i class="fas fa-calendar-alt mr-2 text-sidebar-accent"></i>
            Date of Birth *
          </label>
          <div class="relative">
            <input type="date" id="editDateOfBirth" name="dateOfBirth" required
                class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
          </div>
        </div>

        <!-- Contact Information -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="editEmployeeEmail" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-envelope mr-2 text-sidebar-accent"></i>
              Email Address *
            </label>
            <div class="relative">
              <input type="email" id="editEmployeeEmail" name="employeeEmail" required
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="Email">
            </div>
          </div>
          <div>
            <label for="editEmployeePhone" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-phone-alt mr-2 text-sidebar-accent"></i>
              Phone Number *
            </label>
            <div class="relative">
              <input type="tel" id="editEmployeePhone" name="employeePhone" required
                  class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200"
                  placeholder="09XXXXXXXXX or +63XXXXXXXXXX" pattern="(\+63|0)\d{10}" title="Philippine phone number (09XXXXXXXXX or +63XXXXXXXXXX)">
            </div>
          </div>
        </div>

        <!-- Position and Salary -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="editEmployeePosition" class="block text-xs font-medium text-gray-700 mb-1 flex items-center">
              <i class="fas fa-briefcase mr-2 text-sidebar-accent"></i>
              Position *
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
              <i class="fas fa-money-bill-wave mr-2 text-sidebar-accent"></i>
              Salary per Service (₱) *
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
        </div>
        
        <!-- Gender Selection -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
          <p class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
            <i class="fas fa-venus-mars mr-2 text-sidebar-accent"></i>
            Gender *
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
        <div class="bg-gray-50 p-4 rounded-lg border-l-4 border-gold">
          <label class="block text-xs font-medium text-gray-700 mb-2 flex items-center">
            <i class="fas fa-building mr-2 text-sidebar-accent"></i>
            Branch Location *
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
    <div class="px-6 py-4 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button class="px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center" onclick="closeEditEmployeeModal()">
        <i class="fas fa-times mr-2"></i>
        Cancel
      </button>
      <button type="submit" form="editEmployeeAccountForm" class="px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
        <i class="fas fa-save mr-2"></i>
        Update Employee
      </button>
    </div>
  </div>
</div>

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
      window.location.href = 'employee_management.php?branch_id=' + branchId;
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
  
</body>
</html>