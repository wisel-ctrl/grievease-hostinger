<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

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
    <title>LifePlan - GrievEase</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="tailwind.js"></script>
    
</head>
<body class="flex bg-gray-50">
<?php include 'admin_sidebar.php'; ?>

<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
    <!-- Header Actions -->
    <!-- Header with breadcrumb and welcome message -->
    <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
      <div>
        <h1 class="text-2xl font-bold text-sidebar-text">LifePlan Subscriptions</h1>
      </div>
      <div class="flex space-x-3">
        <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
          <i class="fas fa-bell"></i>
        </button>
      </div>
    </div>
    
    <!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Plans Card -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <!-- Card header with gradient background -->
        <div class="bg-gradient-to-r from-blue-100 to-blue-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Total Plans</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-blue-600 flex items-center justify-center">
                    <i class="fas fa-folder-open"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800">124</span>
            </div>
        </div>
        
        <!-- Card footer with change indicator (simplified since no change data) -->
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-gray-500">
                <span class="text-xs">Updated today</span>
            </div>
        </div>
    </div>
    
    <!-- Active Plans Card -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <!-- Card header with gradient background -->
        <div class="bg-gradient-to-r from-green-100 to-green-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Active Plans</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-green-600 flex items-center justify-center">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800">98</span>
            </div>
        </div>
        
        <!-- Card footer with change indicator -->
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-gray-500">
                <span class="text-xs">Updated today</span>
            </div>
        </div>
    </div>
    
    <!-- Pending Payments Card -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <!-- Card header with gradient background -->
        <div class="bg-gradient-to-r from-orange-100 to-orange-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Pending Payments</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-orange-600 flex items-center justify-center">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800">12</span>
            </div>
        </div>
        
        <!-- Card footer with change indicator -->
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-gray-500">
                <span class="text-xs">Updated today</span>
            </div>
        </div>
    </div>
    
    <!-- Total Revenue Card -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <!-- Card header with gradient background -->
        <div class="bg-gradient-to-r from-purple-100 to-purple-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Total Revenue</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-purple-600 flex items-center justify-center">
                    <i class="fas fa-money-bill-alt"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800">₱4.2M</span>
            </div>
        </div>
        
        <!-- Card footer with change indicator -->
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-gray-500">
                <span class="text-xs">Updated today</span>
            </div>
        </div>
    </div>
</div>
    
    <!-- Table Card -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
  <!-- Header with Search and Filters -->
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <div class="flex items-center gap-3">
      <h4 class="text-lg font-bold text-sidebar-text">Beneficiaries</h4>
      
      <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
        <i class="fas fa-clipboard-list"></i>
        <?php echo isset($totalBeneficiaries) ? $totalBeneficiaries . " Beneficiar" . ($totalBeneficiaries != 1 ? "ies" : "y") : "Beneficiaries"; ?>
      </span>
    </div>
    
    <!-- Search and Filter Section -->
    <div class="flex flex-col md:flex-row items-start md:items-center gap-3 w-full md:w-auto">
      <!-- Search Input -->
      <div class="relative w-full md:w-64">
        <input type="text" 
               placeholder="Search beneficiaries..." 
               class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
        <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
      </div>

      <!-- Filter Dropdown -->
      <div class="relative filter-dropdown">
        <button class="px-3 py-2 border border-gray-300 rounded-lg text-sm flex items-center gap-2 hover:bg-sidebar-hover">
          <i class="fas fa-filter text-sidebar-accent"></i>
          <span>Filters</span>
        </button>
</div>
      
      <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none">
        <option value="">All Status</option>
        <option value="paid">Paid</option>
        <option value="pending">Pending</option>
        <option value="overdue">Overdue</option>
      </select>

      <button class="px-4 py-2.5 bg-sidebar-accent text-white rounded-lg text-sm flex items-center gap-2 hover:bg-darkgold transition-colors shadow-sm whitespace-nowrap">
        <i class="fas fa-plus-circle"></i> Add New Beneficiary
      </button>
    </div>
  </div>
  
  <!-- Services Table for this branch -->
  <div class="overflow-x-auto scrollbar-thin">
    <table class="w-full">
      <thead>
        <tr class="bg-gray-50 border-b border-sidebar-border">
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer">
            <div class="flex items-center">
              <i class="fas fa-user mr-1.5 text-sidebar-accent"></i> Beneficiary Name
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer">
            <div class="flex items-center">
              <i class="fas fa-hand-holding-heart mr-1.5 text-sidebar-accent"></i> Service Name
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer">
            <div class="flex items-center">
              <i class="fas fa-calendar-alt mr-1.5 text-sidebar-accent"></i> Payment Duration
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer">
            <div class="flex items-center">
              <i class="fas fa-tag mr-1.5 text-sidebar-accent"></i> Price
            </div>
          </th>
          <th class="p-4 text-left text-sm font-medium text-sidebar-text cursor-pointer">
            <div class="flex items-center">
              <i class="fas fa-credit-card mr-1.5 text-sidebar-accent"></i> Payment Status
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
        // Initialize fetchedData array before the database query
        $fetchedData = array();

        // Include database connection
        require_once '../db_connect.php';
        
        // Database connection check
        if (!$conn) {
            echo '<tr><td colspan="6" class="p-6 text-sm text-center"><div class="flex flex-col items-center"><i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-3"></i><p class="text-red-500">Database connection failed</p></div></td></tr>';
        } else {
            // Prepare and execute the query using MySQLi
            $query = "SELECT 
                          lp.lifeplan_id,
                          lp.service_id,
                          lp.customerID,
                          CONCAT_WS(' ',
                              lp.benefeciary_fname,
                              NULLIF(lp.benefeciary_mname, ''),
                              lp.benefeciary_lname,
                              NULLIF(lp.benefeciary_suffix, '')
                          ) AS benefeciary_fullname,
                          lp.payment_duration,
                          lp.custom_price,
                          lp.payment_status,
                          s.service_name
                      FROM 
                          lifeplan_tb lp
                      JOIN 
                          services_tb s ON lp.service_id = s.service_id
                      LIMIT 6
                      "; // Limit to 6 records for pagination
            
            $result = $conn->query($query);
            
            // Check if query was successful
            if (!$result) {
                echo '<tr><td colspan="6" class="p-6 text-sm text-center"><div class="flex flex-col items-center"><i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-3"></i><p class="text-red-500">Query error: ' . $conn->error . '</p></div></td></tr>';
            } else if ($result->num_rows == 0) {
                echo '<tr><td colspan="6" class="p-6 text-sm text-center"><div class="flex flex-col items-center"><i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i><p class="text-gray-500">No beneficiaries found</p></div></td></tr>';
            } else {
                // Loop through the results and display each row
                while ($row = $result->fetch_assoc()) {
                    // Add row data to our logging array
                    $fetchedData[] = $row;
                    
                    // Determine status badge class
                    $statusClass = '';
                    $statusIcon = '';
                    switch ($row['payment_status']) {
                        case 'paid':
                            $statusClass = 'bg-green-100 text-green-600 border border-green-200';
                            $statusIcon = 'fa-check-circle';
                            break;
                        case 'ongoing':
                            $statusClass = 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                            $statusIcon = 'fa-clock';
                            break;
                        case 'overdue':
                            $statusClass = 'bg-red-100 text-red-600 border border-red-200';
                            $statusIcon = 'fa-exclamation-circle';
                            break;
                        default:
                            $statusClass = 'bg-gray-100 text-gray-800 border border-gray-200';
                            $statusIcon = 'fa-question-circle';
                    }
                    
                    // Format price with PHP currency symbol
                    $formattedPrice = '₱' . number_format($row['custom_price'], 2);
                    
                    echo '<tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                            <td class="p-4 text-sm text-sidebar-text">
                              <div class="flex items-center">
                                ' . htmlspecialchars($row['benefeciary_fullname']) . '
                              </div>
                            </td>
                            <td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($row['service_name']) . '</td>
                            <td class="p-4 text-sm text-sidebar-text">' . htmlspecialchars($row['payment_duration']) . ' years</td>
                            <td class="p-4 text-sm font-medium text-sidebar-text">' . $formattedPrice . '</td>
                            <td class="p-4 text-sm">
                              <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ' . $statusClass . '">
                                <i class="fas ' . $statusIcon . ' mr-1"></i> ' . htmlspecialchars($row['payment_status']) . '
                              </span>
                            </td>
                            <td class="p-4 text-sm">
                              <div class="flex space-x-2">
                                <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Details">
                                  <i class="fas fa-eye"></i>
                                </button>
                                <button class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="Edit">
                                  <i class="fas fa-edit"></i>
                                </button>
                                <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip" title="Delete">
                                  <i class="fas fa-trash-alt"></i>
                                </button>
                              </div>
                            </td>
                          </tr>';
                }
                
                // Free result set
                $result->free();
            }
            
            // Close database connection
            $conn->close();
        }
        ?>
      </tbody>
    </table>
    
    <!-- Pagination -->
    <div class="p-4 border-t border-sidebar-border flex justify-between items-center">
      <div class="text-sm text-gray-500">
        Showing <?php echo isset($offset) ? ($offset + 1) : '1'; ?> - <?php echo isset($offset) && isset($recordsPerPage) ? min($offset + $recordsPerPage, isset($totalBeneficiaries) ? $totalBeneficiaries : 6) : '6'; ?> 
        of <?php echo isset($totalBeneficiaries) ? $totalBeneficiaries : '6'; ?> beneficiaries
      </div>
      <div class="flex space-x-1">
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 cursor-not-allowed" disabled>&laquo;</button>
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm bg-sidebar-accent text-white">1</button>
        <button class="px-3 py-1 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 cursor-not-allowed" disabled>&raquo;</button>
      </div>
    </div>
  </div>
</div>


  <script>
// Log the fetched data to console
console.log("Fetched Lifeplan Data:", <?php echo json_encode($fetchedData); ?>);

// Detailed log of each record
<?php foreach ($fetchedData as $index => $record): ?>
    console.log("Record #<?php echo $index + 1; ?>:", {
        lifeplan_id: "<?php echo $record['lifeplan_id']; ?>",
        beneficiary: "<?php echo $record['benefeciary_fullname']; ?>",
        service: "<?php echo $record['service_name']; ?>",
        duration: "<?php echo $record['payment_duration']; ?> years",
        price: "₱<?php echo number_format($record['custom_price'], 2); ?>",
        status: "<?php echo $record['payment_status']; ?>"
    });
<?php endforeach; ?>

// Summary log
console.log("Total records fetched: <?php echo count($fetchedData); ?>");
</script>
</body>
</html>