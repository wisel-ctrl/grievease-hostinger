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
        <h1 class="text-2xl font-bold text-sidebar-text">LifePlan Subscription</h1>
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
    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
      <!-- Search and Filter -->
      <div class="flex flex-col md:flex-row gap-4 mb-6">
        <div class="relative flex-1">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3">
            <i class="fas fa-search text-gray-400"></i>
          </span>
          <input 
            type="text" 
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent" 
            placeholder="Search beneficiaries..."
          >
        </div>
        
        <div class="flex gap-4">
          <select class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent bg-white">
            <option value="">All Services</option>
            <option value="memorial">Memorial Service</option>
            <option value="funeral">Funeral Service</option>
            <option value="cremation">Cremation</option>
          </select>
          
          <select class="px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-accent focus:border-transparent bg-white">
            <option value="">All Status</option>
            <option value="paid">Paid</option>
            <option value="pending">Pending</option>
            <option value="overdue">Overdue</option>
          </select>
          
          <button class="px-4 py-2 bg-white border border-accent text-accent rounded-md hover:bg-yellow-50 transition-colors flex items-center gap-2">
            <i class="fas fa-filter"></i> Filter
          </button>
        </div>
      </div>
      
      <!-- Table -->
    <div class="overflow-x-auto">
        <table class="w-full">
        <thead>
            <tr class="bg-gray-50 text-left">
              <th class="px-6 py-3 text-gray-700 font-semibold">
                <div class="flex items-center">
                  <i class="fas fa-user mr-2 text-accent"></i> Beneficiary Name
                </div>
              </th>
              <th class="px-6 py-3 text-gray-700 font-semibold">
                <div class="flex items-center">
                  <i class="fas fa-hand-holding-heart mr-2 text-accent"></i> Service Name
                </div>
              </th>
              <th class="px-6 py-3 text-gray-700 font-semibold">
                <div class="flex items-center">
                  <i class="fas fa-calendar-alt mr-2 text-accent"></i> Payment Duration
                </div>
              </th>
              <th class="px-6 py-3 text-gray-700 font-semibold">
                <div class="flex items-center">
                  <i class="fas fa-tag mr-2 text-accent"></i> Price
                </div>
              </th>
              <th class="px-6 py-3 text-gray-700 font-semibold">
                <div class="flex items-center">
                  <i class="fas fa-credit-card mr-2 text-accent"></i> Payment Status
                </div>
              </th>
              <th class="px-6 py-3 text-gray-700 font-semibold">
                <div class="flex items-center">
                  <i class="fas fa-cogs mr-2 text-accent"></i> Actions
                </div>
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <?php
            // Initialize fetchedData array before the database query
            $fetchedData = array();

            // Include database connection
            require_once '../db_connect.php';
            
            // Database connection check
            if (!$conn) {
                echo '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Database connection failed</td></tr>';
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
                    echo '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Query error: ' . $conn->error . '</td></tr>';
                } else if ($result->num_rows == 0) {
                    echo '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No records found</td></tr>';
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
                                $statusClass = 'bg-green-100 text-green-800';
                                $statusIcon = 'fa-check-circle';
                                break;
                            case 'ongoing':
                                $statusClass = 'bg-yellow-100 text-yellow-800';
                                $statusIcon = 'fa-clock';
                                break;
                            case 'overdue':
                                $statusClass = 'bg-red-100 text-red-800';
                                $statusIcon = 'fa-exclamation-circle';
                                break;
                            default:
                                $statusClass = 'bg-gray-100 text-gray-800';
                                $statusIcon = 'fa-question-circle';
                        }
                        
                        // Format price with PHP currency symbol
                        $formattedPrice = '₱' . number_format($row['custom_price'], 2);
                        
                        echo '<tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-slate-800">
                                  <div class="flex items-center">
                                    <div class="w-8 h-8 rounded-full bg-accent/20 flex items-center justify-center text-accent mr-3">
                                      <i class="fas fa-user"></i>
                                    </div>
                                    ' . htmlspecialchars($row['benefeciary_fullname']) . '
                                  </div>
                                </td>
                                <td class="px-6 py-4 text-slate-800">' . htmlspecialchars($row['service_name']) . '</td>
                                <td class="px-6 py-4 text-slate-800">' . htmlspecialchars($row['payment_duration']) . ' years</td>
                                <td class="px-6 py-4 text-slate-800">' . $formattedPrice . '</td>
                                <td class="px-6 py-4">
                                  <span class="px-3 py-1 rounded-full text-xs font-medium ' . $statusClass . '">
                                    <i class="fas ' . $statusIcon . ' mr-1"></i> ' . htmlspecialchars($row['payment_status']) . '
                                  </span>
                                </td>
                                <td class="px-6 py-4">
                                  <div class="flex gap-2">
                                    <button class="p-1 hover:bg-gray-100 rounded text-gray-500 hover:text-slate-800 transition-colors" title="View Details">
                                      <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="p-1 hover:bg-gray-100 rounded text-gray-500 hover:text-slate-800 transition-colors" title="Edit">
                                      <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="p-1 hover:bg-gray-100 rounded text-gray-500 hover:text-slate-800 transition-colors" title="Delete">
                                      <i class="fas fa-trash"></i>
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
      </div>
      
      <!-- Pagination -->
      
    </div>
    
    <!-- Footer -->
    <div class="mt-8 text-center text-gray-500 text-sm">
      <p>© 2025 Grievease. All rights reserved.</p>
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