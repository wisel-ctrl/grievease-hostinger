<?php
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
    <title>LIFE PLAN</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
     /* Base Typography */
  body {
    font-family: 'Hedvig Letters Serif', serif;
  }
  
  /* Message status indicators */
  .message-new {
    border-left: 3px solid #CA8A04; /* Using your sidebar accent color */
  }
  
  .message-read {
    border-left: 3px solid transparent;
  }
  
  /* Header Styles */
  h1 {
    font-family: 'Cinzel', serif;
    font-size: 1.5rem; /* 24px */
    font-weight: 700;
    color: #1E293B; /* slate-800 */
  }
  
  h2 {
    font-family: 'Cinzel', serif;
    font-size: 1.25rem; /* 20px */
    font-weight: 600;
    color: #1E293B; /* slate-800 */
  }
  
  h3 {
    font-family: 'Cinzel', serif;
    font-size: 1.125rem; /* 18px */
    font-weight: 600;
    color: #1E293B; /* slate-800 */
  }
  
  h5 {
    font-family: 'Cinzel', serif;
    font-size: 0.875rem; /* 14px */
    font-weight: 500;
    color: #CA8A04; /* sidebar accent color */
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  
  /* Text Colors */
  .text-sidebar-accent {
    color: #CA8A04;
  }
  
  .text-sidebar-text {
    color: #334155; /* slate-700 */
  }
  
  /* Button Styles */
  button {
    font-family: 'Hedvig Letters Serif', serif;
    font-size: 0.875rem; /* 14px */
    transition: all 0.3s ease;
  }
  
  /* Input Fields */
  input, textarea {
    font-family: 'Hedvig Letters Serif', serif;
    font-size: 0.875rem; /* 14px */
    border: 1px solid #CBD5E1; /* slate-300 */
    border-radius: 0.375rem; /* 6px */
  }
  
  /* Icons */
  .fas {
    color: #64748B; /* slate-500 */
    transition: color 0.3s ease;
  }
  
  /* Hover States */
  button:hover .fas {
    color: #1E293B; /* slate-800 */
  }
  
  /* Message Bubbles */
  .admin-message {
    background-color: #CA8A04; /* sidebar accent */
    color: white;
  }
  
  .customer-message {
    background-color: #F1F5F9; /* slate-100 */
    color: #1E293B; /* slate-800 */
  }
  
  /* Timestamp Text */
  .message-time {
    font-size: 0.75rem; /* 12px */
    color: #64748B; /* slate-500 */
  }
  
  /* Badges */
  .badge {
    font-size: 0.75rem; /* 12px */
    background-color: #CA8A04; /* sidebar accent */
    color: white;
  }
  
  /* Ensure sidebar maintains styling */
  #sidebar {
    background-color: white !important;
    z-index: 50 !important;
    font-family: 'Hedvig Letters Serif', serif;
  }
  
  /* Mobile Responsiveness */
  @media (max-width: 768px) {
    #sidebar.translate-x-0 {
      background-color: white !important;
      box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }
    
    h1 {
      font-size: 1.25rem; /* 20px */
    }
    
    h2 {
      font-size: 1.125rem; /* 18px */
    }
  }
  
  /* Custom scrollbar to match sidebar */
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
    /* Message status indicators */
    .message-new {
      border-left: 3px solid #008080;
    }
    
    .message-read {
      border-left: 3px solid transparent;
    }
    /* Ensure sidebar maintains background in all views */
    #sidebar {
      background-color: white !important;
      z-index: 50 !important; /* Higher than chat content */
    }

    /* When sidebar is open on mobile, ensure it's above everything */
    @media (max-width: 768px) {
      #sidebar.translate-x-0 {
        background-color: white !important;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
      }
    }
    body.communication-chat #sidebar {
      background-color: white !important;
      z-index: 50 !important;
    }
    .main-content {
      z-index: 40; /* Lower than sidebar's 50 */
    }
  </style>
</head>
<body class="flex bg-gray-50">
<?php include 'admin_sidebar.php'; ?>

<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
    <!-- Header Actions -->
    <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
      <div>
        <h1 class="text-2xl font-bold text-sidebar-text">Lifeplan Subscriptions</h1>
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
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-accent">
        <div class="text-gray-500 text-sm mb-1 flex items-center">
          <i class="fas fa-folder-open mr-2"></i> Total Plans
        </div>
        <div class="font-cinzel text-2xl font-semibold text-slate-800">124</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-accent">
        <div class="text-gray-500 text-sm mb-1 flex items-center">
          <i class="fas fa-check-circle mr-2"></i> Active Plans
        </div>
        <div class="font-cinzel text-2xl font-semibold text-slate-800">98</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-accent">
        <div class="text-gray-500 text-sm mb-1 flex items-center">
          <i class="fas fa-clock mr-2"></i> Pending Payments
        </div>
        <div class="font-cinzel text-2xl font-semibold text-slate-800">12</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-5 border-l-4 border-accent">
        <div class="text-gray-500 text-sm mb-1 flex items-center">
          <i class="fas fa-money-bill-alt mr-2"></i> Total Revenue
        </div>
        <div class="font-cinzel text-2xl font-semibold text-slate-800">₱4.2M</div>
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
            // Include database connection
            require_once '../db_connect.php';
            
            // Prepare and execute the query using MySQLi
            $query = "SELECT 
                        lp.lifeplan_id,
                        lp.service_id,
                        lp.customerID,
                        CONCAT(lp.benefeciary_fname, ' ', 
                              COALESCE(lp.benefeciary_mname, ''), 
                              CASE 
                                  WHEN lp.benefeciary_mname IS NOT NULL AND lp.benefeciary_mname != '' THEN ' ' 
                                  ELSE '' 
                              END,
                              lp.benefeciary_lname, 
                              CASE 
                                  WHEN lp.benefeciary_suffix IS NOT NULL AND lp.benefeciary_suffix != '' THEN CONCAT(' ', lp.benefeciary_suffix) 
                                  ELSE '' 
                              END) AS benefeciary_fullname,
                        lp.payment_duration,
                        lp.custom_price,
                        lp.payment_status,
                        s.service_name
                      FROM 
                        lifeplan_tb lp
                      JOIN 
                        services_tb s ON lp.service_id = s.service_id
                      LIMIT 6"; // Limit to 6 records for pagination
            
            $result = $mysqli->query($query);
            
            // Check if query was successful
            if ($result) {
              // Loop through the results and display each row
              while ($row = $result->fetch_assoc()) {
                // Determine status badge class
                $statusClass = '';
                $statusIcon = '';
                switch ($row['payment_status']) {
                  case 'Paid':
                    $statusClass = 'bg-green-100 text-green-800';
                    $statusIcon = 'fa-check-circle';
                    break;
                  case 'Pending':
                    $statusClass = 'bg-yellow-100 text-yellow-800';
                    $statusIcon = 'fa-clock';
                    break;
                  case 'Overdue':
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
            } else {
              // Handle query error
              echo '<tr><td colspan="6" class="px-6 py-4 text-center text-red-500">Error loading data: ' . $mysqli->error . '</td></tr>';
            }
            ?>
          </tbody>
        </table>
      </div>
      
      <!-- Pagination -->
      <div class="flex justify-between items-center mt-6">
        <?php
        // Get total count of records using MySQLi
        $countQuery = "SELECT COUNT(*) as total FROM lifeplan_tb";
        $countResult = $mysqli->query($countQuery);
        
        if ($countResult) {
          $totalRecords = $countResult->fetch_assoc()['total'];
          $countResult->free();
        } else {
          $totalRecords = 0;
        }
        ?>
        <div class="text-sm text-gray-500">Showing 1 to 6 of <?php echo $totalRecords; ?> entries</div>
        <div class="flex gap-2">
          <button class="px-3 py-1 border border-gray-300 rounded bg-white text-gray-500 hover:bg-gray-50 transition-colors">
            <i class="fas fa-chevron-left"></i>
          </button>
          <button class="px-3 py-1 border border-accent bg-accent text-white rounded hover:bg-yellow-700 transition-colors">1</button>
          <button class="px-3 py-1 border border-gray-300 rounded bg-white text-gray-500 hover:bg-gray-50 transition-colors">2</button>
          <button class="px-3 py-1 border border-gray-300 rounded bg-white text-gray-500 hover:bg-gray-50 transition-colors">3</button>
          <button class="px-3 py-1 border border-gray-300 rounded bg-white text-gray-500 hover:bg-gray-50 transition-colors">
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>
    
    <!-- Footer -->
    <div class="mt-8 text-center text-gray-500 text-sm">
      <p>© 2025 Grievease. All rights reserved.</p>
    </div>
  </div>
</body>
</html>