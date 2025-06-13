<?php
session_start();

include 'faviconLogo.php'; 
require_once '../db_connect.php'; // Database connection

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

// Pagination variables
$per_page = 10; // Number of items per page
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $per_page;

// Fetch Traditional Payment Requests
$traditional_query = "SELECT 
    CONCAT(
        UPPER(LEFT(u.first_name, 1)), LOWER(SUBSTRING(u.first_name, 2)), ' ',
        IFNULL(CONCAT(UPPER(LEFT(u.middle_name, 1)), LOWER(SUBSTRING(u.middle_name, 2)), ' '), ''),
        UPPER(LEFT(u.last_name, 1)), LOWER(SUBSTRING(u.last_name, 2)),
        IF(u.suffix IS NOT NULL AND u.suffix != '', CONCAT(' ', UPPER(LEFT(u.suffix, 1)), LOWER(SUBSTRING(u.suffix, 2))), '')
    ) AS full_name,
    ir_tb.payment_id,
    ir_tb.sales_id,
    s_tb.service_name,
    sl_tb.discounted_price,
    ir_tb.request_date,
    ir_tb.amount,
    ir_tb.payment_method,
    ir_tb.payment_url,
    ir_tb.status
FROM installment_request_tb AS ir_tb
JOIN users AS u ON ir_tb.customer_id = u.id
JOIN sales_tb AS sl_tb ON ir_tb.sales_id = sl_tb.sales_id
JOIN services_tb AS s_tb ON sl_tb.service_id = s_tb.service_id
WHERE ir_tb.status = 'pending'";

// Get total count for traditional payments
$traditional_count_query = "SELECT COUNT(*) as total FROM installment_request_tb WHERE status = 'pending'";
$traditional_count_result = mysqli_query($conn, $traditional_count_query);
$traditional_total = mysqli_fetch_assoc($traditional_count_result)['total'];
$traditional_total_pages = ceil($traditional_total / $per_page);

// Add pagination to traditional query
$traditional_query .= " LIMIT $per_page OFFSET $offset";
$traditional_result = mysqli_query($conn, $traditional_query);
$traditional_requests = mysqli_fetch_all($traditional_result, MYSQLI_ASSOC);

// Fetch Custom Packages Payment Requests
$custom_query = "SELECT 
    CONCAT(
        UPPER(LEFT(u.first_name, 1)), LOWER(SUBSTRING(u.first_name, 2)), ' ',
        IFNULL(CONCAT(UPPER(LEFT(u.middle_name, 1)), LOWER(SUBSTRING(u.middle_name, 2)), ' '), ''),
        UPPER(LEFT(u.last_name, 1)), LOWER(SUBSTRING(u.last_name, 2)),
        IF(u.suffix IS NOT NULL AND u.suffix != '', CONCAT(' ', UPPER(LEFT(u.suffix, 1)), LOWER(SUBSTRING(u.suffix, 2))), '')
    ) AS full_name,
    ir_tb.payment_id,
    cs_tb.discounted_price,
    ir_tb.request_date,
    ir_tb.amount,
    ir_tb.payment_method,
    ir_tb.payment_url,
    ir_tb.status
FROM custompayment_request_tb AS ir_tb
JOIN users AS u ON ir_tb.customer_id = u.id
JOIN customsales_tb AS cs_tb ON ir_tb.customsales_id = cs_tb.customsales_id";

// Get total count for custom payments
$custom_count_query = "SELECT COUNT(*) as total FROM custompayment_request_tb";
$custom_count_result = mysqli_query($conn, $custom_count_query);
$custom_total = mysqli_fetch_assoc($custom_count_result)['total'];
$custom_total_pages = ceil($custom_total / $per_page);

// Add pagination to custom query
$custom_query .= " LIMIT $per_page OFFSET $offset";
$custom_result = mysqli_query($conn, $custom_query);
$custom_requests = mysqli_fetch_all($custom_result, MYSQLI_ASSOC);

// Fetch Lifeplan Payment Requests
$lifeplan_query = "SELECT 
    CONCAT(
        UPPER(LEFT(u.first_name, 1)), LOWER(SUBSTRING(u.first_name, 2)), ' ',
        IFNULL(CONCAT(UPPER(LEFT(u.middle_name, 1)), LOWER(SUBSTRING(u.middle_name, 2)), ' '), ''),
        UPPER(LEFT(u.last_name, 1)), LOWER(SUBSTRING(u.last_name, 2)),
        IF(u.suffix IS NOT NULL AND u.suffix != '', CONCAT(' ', UPPER(LEFT(u.suffix, 1)), LOWER(SUBSTRING(u.suffix, 2))), '')
    ) AS full_name,
    ir_tb.payment_id,
    ir_tb.lifeplan_id,
    s_tb.service_name,
    sl_tb.custom_price,
    ir_tb.request_date,
    ir_tb.amount,
    ir_tb.payment_method,
    ir_tb.payment_url,
    ir_tb.status
FROM lifeplanpayment_request_tb AS ir_tb
JOIN users AS u ON ir_tb.customer_id = u.id
JOIN lifeplan_tb AS sl_tb ON ir_tb.lifeplan_id = sl_tb.lifeplan_id
JOIN services_tb AS s_tb ON sl_tb.service_id = s_tb.service_id
WHERE ir_tb.status = 'pending'";

// Get total count for lifeplan payments
$lifeplan_count_query = "SELECT COUNT(*) as total FROM lifeplanpayment_request_tb";
$lifeplan_count_result = mysqli_query($conn, $lifeplan_count_query);
$lifeplan_total = mysqli_fetch_assoc($lifeplan_count_result)['total'];
$lifeplan_total_pages = ceil($lifeplan_total / $per_page);

// Add pagination to lifeplan query
$lifeplan_query .= " LIMIT $per_page OFFSET $offset";
$lifeplan_result = mysqli_query($conn, $lifeplan_query);
$lifeplan_requests = mysqli_fetch_all($lifeplan_result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Payment Acceptance</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="tailwind.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
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

<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
    <!-- Header Actions -->
    <!-- Header with breadcrumb and welcome message -->
    <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
      <div>
        <h1 class="text-2xl font-bold text-sidebar-text">Payment Acceptance</h1>
      </div>
    </div>
    
    <!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <!-- Traditional Payments Card -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <div class="bg-gradient-to-r from-blue-100 to-blue-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Traditional Payments</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-blue-600 flex items-center justify-center">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800"><?php echo number_format($traditional_total); ?></span>
            </div>
        </div>
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-gray-500">
                <span class="text-xs">Updated today</span>
            </div>
        </div>
    </div>

    <!-- Custom Packages Card -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <div class="bg-gradient-to-r from-purple-100 to-purple-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Custom Packages</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-purple-600 flex items-center justify-center">
                    <i class="fas fa-box-open"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800"><?php echo number_format($custom_total); ?></span>
            </div>
        </div>
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-gray-500">
                <span class="text-xs">Updated today</span>
            </div>
        </div>
    </div>

    <!-- Lifeplan Payments Card -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <div class="bg-gradient-to-r from-green-100 to-green-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Lifeplan Payments</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-green-600 flex items-center justify-center">
                    <i class="fas fa-heart"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800"><?php echo number_format($lifeplan_total); ?></span>
            </div>
        </div>
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-gray-500">
                <span class="text-xs">Updated today</span>
            </div>
        </div>
    </div>
</div>

<!-- Traditional Payment Requests Section -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
  <!-- Header Section -->
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
      <!-- Title and Counter -->
      <div class="flex items-center gap-3 mb-4 lg:mb-0">
        <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Traditional Payment Requests</h4>
        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
          <?php echo count($traditional_requests) ?>
        </span>
      </div>
    </div>
  </div>
  
  <!-- Table Container -->
  <div class="overflow-x-auto scrollbar-thin">
    <div class="min-w-full">
      <table class="w-full">
        <thead>
          <tr class="bg-gray-50 border-b border-sidebar-border">
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-user text-sidebar-accent"></i> Customer Name
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-hand-holding-heart text-sidebar-accent"></i> Service Name
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-money-bill-wave text-sidebar-accent"></i> Amount
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-calendar-alt text-sidebar-accent"></i> Date
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-credit-card text-sidebar-accent"></i> Method
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
          <?php foreach ($traditional_requests as $request): ?>
            <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
              <td class="px-4 py-3.5 text-sm text-sidebar-text">
                <div class="flex items-center">
                  <?= htmlspecialchars($request['full_name']) ?>
                </div>
              </td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text"><?= htmlspecialchars($request['service_name']) ?></td>
              <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">₱<?= number_format($request['amount'], 2) ?></td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text"><?= date('M d, Y', strtotime($request['request_date'])) ?></td>
              <td class="px-4 py-3.5 text-sm">
                <span class="capitalize"><?= htmlspecialchars($request['payment_method']) ?></span>
              </td>
              <td class="px-4 py-3.5 text-sm">
                <div class="flex space-x-2">
                  <button onclick="openTraditionalModal('<?= htmlspecialchars($request['payment_url']) ?>', '<?= number_format($request['amount'], 2) ?>', '<?= $request['payment_id'] ?>', '<?= $request['sales_id'] ?>')" 
                    class="p-2 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" 
                    title="View Receipt">
                    <i class="fas fa-receipt"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($traditional_requests)): ?>
            <tr>
              <td colspan="6" class="p-6 text-sm text-center">
                <div class="flex flex-col items-center">
                  <i class="fas fa-file-invoice-dollar text-gray-300 text-4xl mb-3"></i>
                  <p class="text-gray-500">No traditional payment requests found</p>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Traditional Payments Pagination -->
  <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
      <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
      <?php 
          if ($traditional_total > 0) {
              $start = $offset + 1;
              $end = $offset + count($traditional_requests);
          
              echo "Showing {$start} - {$end} of {$traditional_total} requests";
          } else {
              echo "No requests found";
          }
          ?>
      </div>
      <div id="paginationContainer" class="flex space-x-2">
          <?php if ($traditional_total_pages > 1): ?>
              <!-- First page button (double arrow) -->
              <a href="?page=1" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                  &laquo;
              </a>
              
              <!-- Previous page button (single arrow) -->
              <a href="<?php echo '?page=' . max(1, $current_page - 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                  &lsaquo;
              </a>
              
              <?php
              // Show exactly 3 page numbers
              if ($traditional_total_pages <= 3) {
                  // If total pages is 3 or less, show all pages
                  $start_page = 1;
                  $end_page = $traditional_total_pages;
              } else {
                  // With more than 3 pages, determine which 3 to show
                  if ($current_page == 1) {
                      // At the beginning, show first 3 pages
                      $start_page = 1;
                      $end_page = 3;
                  } elseif ($current_page == $traditional_total_pages) {
                      // At the end, show last 3 pages
                      $start_page = $traditional_total_pages - 2;
                      $end_page = $traditional_total_pages;
                  } else {
                      // In the middle, show current page with one before and after
                      $start_page = $current_page - 1;
                      $end_page = $current_page + 1;
                      
                      // Handle edge cases
                      if ($start_page < 1) {
                          $start_page = 1;
                          $end_page = 3;
                      }
                      if ($end_page > $traditional_total_pages) {
                          $end_page = $traditional_total_pages;
                          $start_page = $traditional_total_pages - 2;
                      }
                  }
              }
              
              // Generate the page buttons
              for ($i = $start_page; $i <= $end_page; $i++) {
                  $active_class = ($i == $current_page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
                  echo '<a href="?page=' . $i . '" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</a>';
              }
              ?>
              
              <!-- Next page button (single arrow) -->
              <a href="<?php echo '?page=' . min($traditional_total_pages, $current_page + 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $traditional_total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                  &rsaquo;
              </a>
              
              <!-- Last page button (double arrow) -->
              <a href="<?php echo '?page=' . $traditional_total_pages; ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $traditional_total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                  &raquo;
              </a>
          <?php endif; ?>
      </div>
  </div>
</div>

<!-- Custom Packages Payment Requests Section -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
  <!-- Header Section -->
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
      <!-- Title and Counter -->
      <div class="flex items-center gap-3 mb-4 lg:mb-0">
        <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Custom Packages Payment Requests</h4>
        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
          <?php echo count($custom_requests) ?>
        </span>
      </div>
    </div>
  </div>
  
  <!-- Table Container -->
  <div class="overflow-x-auto scrollbar-thin">
    <div class="min-w-full">
      <table class="w-full">
        <thead>
          <tr class="bg-gray-50 border-b border-sidebar-border">
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-user text-sidebar-accent"></i> Customer Name
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-box-open text-sidebar-accent"></i> Service Type
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-money-bill-wave text-sidebar-accent"></i> Amount
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-calendar-alt text-sidebar-accent"></i> Date
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-credit-card text-sidebar-accent"></i> Method
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
          <?php foreach ($custom_requests as $request): ?>
            <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
              <td class="px-4 py-3.5 text-sm text-sidebar-text">
                <div class="flex items-center">
                  <?= htmlspecialchars($request['full_name']) ?>
                </div>
              </td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text">Custom Package</td>
              <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">₱<?= number_format($request['amount'], 2) ?></td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text"><?= date('M d, Y', strtotime($request['request_date'])) ?></td>
              <td class="px-4 py-3.5 text-sm">
                <span class="capitalize"><?= htmlspecialchars($request['payment_method']) ?></span>
              </td>
              <td class="px-4 py-3.5 text-sm">
                <div class="flex space-x-2">
                  <button onclick="openCustomModal('<?= htmlspecialchars($request['payment_url']) ?>', '<?= number_format($request['amount'], 2) ?>', '<?= $request['payment_id'] ?>')" 
                    class="p-2 bg-purple-100 text-purple-600 rounded-lg hover:bg-purple-200 transition-all tooltip" 
                    title="View Receipt">
                    <i class="fas fa-receipt"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($custom_requests)): ?>
            <tr>
              <td colspan="6" class="p-6 text-sm text-center">
                <div class="flex flex-col items-center">
                  <i class="fas fa-box-open text-gray-300 text-4xl mb-3"></i>
                  <p class="text-gray-500">No custom package payment requests found</p>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Custom Packages Pagination -->
  <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
      <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
      <?php 
          if ($custom_total > 0) {
              $start = $offset + 1;
              $end = $offset + count($custom_requests);
          
              echo "Showing {$start} - {$end} of {$custom_total} requests";
          } else {
              echo "No requests found";
          }
          ?>
      </div>
      <div id="paginationContainer" class="flex space-x-2">
          <?php if ($custom_total_pages > 1): ?>
              <!-- First page button (double arrow) -->
              <a href="?page=1" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                  &laquo;
              </a>
              
              <!-- Previous page button (single arrow) -->
              <a href="<?php echo '?page=' . max(1, $current_page - 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                  &lsaquo;
              </a>
              
              <?php
              // Show exactly 3 page numbers
              if ($custom_total_pages <= 3) {
                  // If total pages is 3 or less, show all pages
                  $start_page = 1;
                  $end_page = $custom_total_pages;
              } else {
                  // With more than 3 pages, determine which 3 to show
                  if ($current_page == 1) {
                      // At the beginning, show first 3 pages
                      $start_page = 1;
                      $end_page = 3;
                  } elseif ($current_page == $custom_total_pages) {
                      // At the end, show last 3 pages
                      $start_page = $custom_total_pages - 2;
                      $end_page = $custom_total_pages;
                  } else {
                      // In the middle, show current page with one before and after
                      $start_page = $current_page - 1;
                      $end_page = $current_page + 1;
                      
                      // Handle edge cases
                      if ($start_page < 1) {
                          $start_page = 1;
                          $end_page = 3;
                      }
                      if ($end_page > $custom_total_pages) {
                          $end_page = $custom_total_pages;
                          $start_page = $custom_total_pages - 2;
                      }
                  }
              }
              
              // Generate the page buttons
              for ($i = $start_page; $i <= $end_page; $i++) {
                  $active_class = ($i == $current_page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
                  echo '<a href="?page=' . $i . '" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</a>';
              }
              ?>
              
              <!-- Next page button (single arrow) -->
              <a href="<?php echo '?page=' . min($custom_total_pages, $current_page + 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $custom_total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                  &rsaquo;
              </a>
              
              <!-- Last page button (double arrow) -->
              <a href="<?php echo '?page=' . $custom_total_pages; ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $custom_total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                  &raquo;
              </a>
          <?php endif; ?>
      </div>
  </div>
</div>

<!-- Lifeplan Payment Requests Section -->
<div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
  <!-- Header Section -->
  <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
      <!-- Title and Counter -->
      <div class="flex items-center gap-3 mb-4 lg:mb-0">
        <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Lifeplan Payment Requests</h4>
        <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
          <?php echo count($lifeplan_requests) ?>
        </span>
      </div>
    </div>
  </div>
  
  <!-- Table Container -->
  <div class="overflow-x-auto scrollbar-thin">
    <div class="min-w-full">
      <table class="w-full">
        <thead>
          <tr class="bg-gray-50 border-b border-sidebar-border">
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-user text-sidebar-accent"></i> Customer Name
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-heart text-sidebar-accent"></i> Service Name
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-money-bill-wave text-sidebar-accent"></i> Amount
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-calendar-alt text-sidebar-accent"></i> Date
              </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
              <div class="flex items-center gap-1.5">
                <i class="fas fa-credit-card text-sidebar-accent"></i> Method
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
          <?php foreach ($lifeplan_requests as $request): ?>
            <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
              <td class="px-4 py-3.5 text-sm text-sidebar-text">
                <div class="flex items-center">
                  <?= htmlspecialchars($request['full_name']) ?>
                </div>
              </td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text"><?= htmlspecialchars($request['service_name']) ?></td>
              <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">₱<?= number_format($request['amount'], 2) ?></td>
              <td class="px-4 py-3.5 text-sm text-sidebar-text"><?= date('M d, Y', strtotime($request['request_date'])) ?></td>
              <td class="px-4 py-3.5 text-sm">
                <span class="capitalize"><?= htmlspecialchars($request['payment_method']) ?></span>
              </td>
              <td class="px-4 py-3.5 text-sm">
                <div class="flex space-x-2">
                  <button onclick="openLifeplanModal('<?= htmlspecialchars($request['payment_url']) ?>', '<?= number_format($request['amount'], 2) ?>', '<?= $request['payment_id'] ?>', '<?= $request['lifeplan_id'] ?>')" 
                    class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip" 
                    title="View Receipt">
                    <i class="fas fa-receipt"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($lifeplan_requests)): ?>
            <tr>
              <td colspan="6" class="p-6 text-sm text-center">
                <div class="flex flex-col items-center">
                  <i class="fas fa-heart text-gray-300 text-4xl mb-3"></i>
                  <p class="text-gray-500">No lifeplan payment requests found</p>
                </div>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Lifeplan Payments Pagination -->
  <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
      <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
      <?php 
          if ($lifeplan_total > 0) {
              $start = $offset + 1;
              $end = $offset + count($lifeplan_requests);
          
              echo "Showing {$start} - {$end} of {$lifeplan_total} requests";
          } else {
              echo "No requests found";
          }
          ?>
      </div>
      <div id="paginationContainer" class="flex space-x-2">
          <?php if ($lifeplan_total_pages > 1): ?>
              <!-- First page button (double arrow) -->
              <a href="?page=1" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                  &laquo;
              </a>
              
              <!-- Previous page button (single arrow) -->
              <a href="<?php echo '?page=' . max(1, $current_page - 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                  &lsaquo;
              </a>
              
              <?php
              // Show exactly 3 page numbers
              if ($lifeplan_total_pages <= 3) {
                  // If total pages is 3 or less, show all pages
                  $start_page = 1;
                  $end_page = $lifeplan_total_pages;
              } else {
                  // With more than 3 pages, determine which 3 to show
                  if ($current_page == 1) {
                      // At the beginning, show first 3 pages
                      $start_page = 1;
                      $end_page = 3;
                  } elseif ($current_page == $lifeplan_total_pages) {
                      // At the end, show last 3 pages
                      $start_page = $lifeplan_total_pages - 2;
                      $end_page = $lifeplan_total_pages;
                  } else {
                      // In the middle, show current page with one before and after
                      $start_page = $current_page - 1;
                      $end_page = $current_page + 1;
                      
                      // Handle edge cases
                      if ($start_page < 1) {
                          $start_page = 1;
                          $end_page = 3;
                      }
                      if ($end_page > $lifeplan_total_pages) {
                          $end_page = $lifeplan_total_pages;
                          $start_page = $lifeplan_total_pages - 2;
                      }
                  }
              }
              
              // Generate the page buttons
              for ($i = $start_page; $i <= $end_page; $i++) {
                  $active_class = ($i == $current_page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
                  echo '<a href="?page=' . $i . '" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</a>';
              }
              ?>
              
              <!-- Next page button (single arrow) -->
              <a href="<?php echo '?page=' . min($lifeplan_total_pages, $current_page + 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $lifeplan_total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                  &rsaquo;
              </a>
              
              <!-- Last page button (double arrow) -->
              <a href="<?php echo '?page=' . $lifeplan_total_pages; ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $lifeplan_total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
                  &raquo;
              </a>
          <?php endif; ?>
      </div>
  </div>
</div>

<!-- Traditional Payment Modal -->
<div id="traditionalModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" id="closeTraditionalModal">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        <i class="fas fa-money-bill-wave mr-2"></i> Payment Receipt
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <div class="mb-6 rounded-lg overflow-hidden border-4 border-gray-200 shadow-lg">
        <img id="traditionalReceiptImage" src="" alt="Payment Receipt" class="w-full max-h-[70vh] object-contain mx-auto">
      </div>
      
      <div class="bg-blue-50 p-5 rounded-lg shadow-inner mb-6">
        <div class="flex justify-between items-center">
          <p class="text-sidebar-text font-semibold text-lg">Payment Amount:</p>
          <input type="number" id="traditionalAmountInput" class="text-2xl font-bold text-sidebar w-32 text-right" value="0.00" step="0.01">
        </div>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" id="cancelTraditionalModal">
        Close
      </button>
      <button id="approveTraditionalPayment" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
        <i class="fas fa-check mr-2"></i> Approve Payment
      </button>
    </div>
  </div>
</div>

<!-- Custom Packages Modal -->
<div id="customModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" id="closeCustomModal">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        <i class="fas fa-box-open mr-2"></i> Custom Package Receipt
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <div class="mb-6 rounded-lg overflow-hidden border-4 border-gray-200 shadow-lg">
        <img id="customReceiptImage" src="" alt="Payment Receipt" class="w-full max-h-[70vh] object-contain mx-auto">
      </div>
      
      <div class="bg-purple-50 p-5 rounded-lg shadow-inner mb-6">
        <div class="flex justify-between items-center">
          <p class="text-sidebar-text font-semibold text-lg">Payment Amount:</p>
          <input type="number" id="customAmountInput" class="text-2xl font-bold text-sidebar w-32 text-right" value="0.00" step="0.01">
        </div>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" id="cancelCustomModal">
        Close
      </button>
      <button id="approveCustomPayment" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
        <i class="fas fa-check mr-2"></i> Approve Payment
      </button>
    </div>
  </div>
</div>

<!-- Lifeplan Modal -->
<div id="lifeplanModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-4xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" id="closeLifeplanModal">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        <i class="fas fa-heart mr-2"></i> Lifeplan Payment Receipt
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <div class="mb-6 rounded-lg overflow-hidden border-4 border-gray-200 shadow-lg">
        <img id="lifeplanReceiptImage" src="" alt="Payment Receipt" class="w-full max-h-[70vh] object-contain mx-auto">
      </div>
      
      <div class="bg-green-50 p-5 rounded-lg shadow-inner mb-6">
        <div class="flex justify-between items-center">
          <p class="text-sidebar-text font-semibold text-lg">Payment Amount:</p>
          <input type="number" id="lifeplanAmountInput" class="text-2xl font-bold text-sidebar w-32 text-right" value="0.00" step="0.01">
        </div>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" id="cancelLifeplanModal">
        Close
      </button>
      <button id="approveLifeplanPayment" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
        <i class="fas fa-check mr-2"></i> Approve Payment
      </button>
    </div>
  </div>
</div>

<!-- Decline Payment Modal -->
<div id="declineModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
  
  <!-- Modal Content -->
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-2xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <!-- Close Button -->
    <button type="button" class="absolute top-4 right-4 text-gray-500 hover:text-sidebar-accent transition-colors" id="closeDeclineModal">
      <i class="fas fa-times"></i>
    </button>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-red-500 to-red-600 border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        <i class="fas fa-times-circle mr-2"></i> Decline Payment
      </h3>
    </div>
    
    <!-- Modal Body -->
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container">
      <div class="mb-4">
        <h4 id="declinePaymentTitle" class="text-lg font-semibold text-gray-800 mb-2"></h4>
        <p class="text-gray-600 mb-4">Please select the reason for declining this payment:</p>
        
        <div class="space-y-3 mb-4">
          <div class="flex items-center">
            <input type="radio" id="reason1" name="declineReason" value="Wrong reference number" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300">
            <label for="reason1" class="ml-2 block text-sm text-gray-700">Wrong reference number</label>
          </div>
          <div class="flex items-center">
            <input type="radio" id="reason2" name="declineReason" value="Wrong amount" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300">
            <label for="reason2" class="ml-2 block text-sm text-gray-700">Wrong amount</label>
          </div>
          <div class="flex items-center">
            <input type="radio" id="reason3" name="declineReason" value="Unclear receipt" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300">
            <label for="reason3" class="ml-2 block text-sm text-gray-700">Unclear receipt</label>
          </div>
          <div class="flex items-center">
            <input type="radio" id="reason4" name="declineReason" value="Expired payment" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300">
            <label for="reason4" class="ml-2 block text-sm text-gray-700">Expired payment</label>
          </div>
          <div class="flex items-center">
            <input type="radio" id="reason5" name="declineReason" value="Others" class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300">
            <label for="reason5" class="ml-2 block text-sm text-gray-700">Others</label>
          </div>
        </div>
        
        <div id="otherReasonContainer" class="hidden mt-4">
          <label for="otherReason" class="block text-sm font-medium text-gray-700 mb-1">Please specify:</label>
          <textarea id="otherReason" rows="3" class="shadow-sm focus:ring-red-500 focus:border-red-500 block w-full sm:text-sm border-gray-300 rounded-md"></textarea>
        </div>
      </div>
    </div>
    
    <!-- Modal Footer -->
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
      <button type="button" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-gray-300 text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" id="cancelDeclineModal">
        Cancel
      </button>
      <button id="submitDecline" class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
        <i class="fas fa-times-circle mr-2"></i> Decline Payment
      </button>
    </div>
  </div>
</div>

<script>
    // Global variables to track current payment details
    let currentPaymentType = '';
    let currentPaymentId = '';
    let currentSalesId = '';
    let currentLifeplanId = '';

    // Traditional Payment Modal Functions
    function openTraditionalModal(imageUrl, amount, paymentId, salesId) {
        const imgSrc = '../customer/payments/' + imageUrl;
        document.getElementById('traditionalReceiptImage').src = imgSrc;
        document.getElementById('traditionalAmountInput').value = amount;
        document.getElementById('traditionalModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // Set current payment details
        currentPaymentType = 'traditional';
        currentPaymentId = paymentId;
        currentSalesId = salesId;

        // Update decline button onclick
        document.getElementById('submitDecline').setAttribute('onclick', `declinePayment('traditional', '${paymentId}', '${salesId}')`);

        // Load Tesseract.js and process the image
        Tesseract.recognize(
            imgSrc,
            'eng',
            { logger: m => console.log(m) }
        ).then(({ data: { text } }) => {
            console.log('Extracted text from receipt:', text);
        }).catch(err => {
            console.error('Error during OCR:', err);
        });

        const approveBtn = document.getElementById('approveTraditionalPayment');
        
        approveBtn.onclick = async function() {
            const amount = document.getElementById('traditionalAmountInput').value;
            
            // 1. Show confirmation dialog
            const { isConfirmed } = await Swal.fire({
                title: 'Confirm Acceptance',
                text: 'Are you sure you want to accept this payment?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, accept it!'
            });

            if (!isConfirmed) return;

            try {
                // 2. Send AJAX request
                const response = await fetch(
                    `payments/accept_traditional.php?payment_id=${paymentId}&sales_id=${salesId}&amount=${amount}`,
                    { method: 'GET' }
                );
                
                if (!response.ok) throw new Error('Server error');

                // 3. Show success message
                Swal.fire({
                    title: 'Success!',
                    text: 'Payment accepted successfully',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // 4. Close modal and refresh
                    document.getElementById('traditionalModal').classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                    location.reload();
                });

            } catch (error) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to accept payment: ' + error.message,
                    icon: 'error'
                });
            }
        };

        // Close button handlers
        document.getElementById('closeTraditionalModal').onclick = function() {
            document.getElementById('traditionalModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };
        
        document.getElementById('cancelTraditionalModal').onclick = function() {
            document.getElementById('traditionalModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };

        // Add decline button to footer
        const footer = document.querySelector('#traditionalModal .sticky.bottom-0');
        if (!footer.querySelector('#declineTraditionalPayment')) {
            const declineBtn = document.createElement('button');
            declineBtn.id = 'declineTraditionalPayment';
            declineBtn.className = 'w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center';
            declineBtn.innerHTML = '<i class="fas fa-times-circle mr-2"></i> Decline Payment';
            declineBtn.onclick = function() {
                document.getElementById('declinePaymentTitle').textContent = 'Traditional Payment - ' + amount;
                document.getElementById('declineModal').classList.remove('hidden');
            };
            footer.insertBefore(declineBtn, footer.lastElementChild);
        }
    }

    // Custom Packages Modal Functions
    function openCustomModal(imageUrl, amount, paymentId) {
        const imgSrc = '../customer/payments/' + imageUrl;
        document.getElementById('customReceiptImage').src = imgSrc;
        document.getElementById('customAmountInput').value = amount;
        document.getElementById('customModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        console.log('payment amount: ',amount);

        // Set current payment details
        currentPaymentType = 'custom';
        currentPaymentId = paymentId;

        // Update decline button onclick
        document.getElementById('submitDecline').setAttribute('onclick', `declinePayment('custom', '${paymentId}')`);

        // Close button handlers
        document.getElementById('closeCustomModal').onclick = function() {
            document.getElementById('customModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };
        
        document.getElementById('cancelCustomModal').onclick = function() {
            document.getElementById('customModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };

        // Add decline button to footer if not already present
        const footer = document.querySelector('#customModal .sticky.bottom-0');
        if (!footer.querySelector('#declineCustomPayment')) {
            const declineBtn = document.createElement('button');
            declineBtn.id = 'declineCustomPayment';
            declineBtn.className = 'w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center';
            declineBtn.innerHTML = '<i class="fas fa-times-circle mr-2"></i> Decline Payment';
            declineBtn.onclick = function() {
                document.getElementById('declinePaymentTitle').textContent = 'Custom Package - ' + amount;
                document.getElementById('declineModal').classList.remove('hidden');
            };
            footer.insertBefore(declineBtn, footer.lastElementChild);
        }

        // Handle approve button click
        const approveBtn = document.getElementById('approveCustomPayment');
        
        approveBtn.onclick = async function() {
            const amount = document.getElementById('customAmountInput').value;
            
            // 1. Show confirmation dialog
            const { isConfirmed } = await Swal.fire({
                title: 'Confirm Acceptance',
                text: 'Are you sure you want to accept this payment?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, accept it!'
            });

            if (!isConfirmed) return;

            try {
                // 2. Send AJAX request
                const response = await fetch(
                    `payments/accept_custom.php?payment_id=${paymentId}&amount=${amount}`,
                    { method: 'GET' }
                );
                
                if (!response.ok) throw new Error('Server error');

                const data = await response.json();
                
                if (data.error) throw new Error(data.error);

                // 3. Show success message
                Swal.fire({
                    title: 'Success!',
                    text: 'Payment accepted successfully',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // 4. Close modal and refresh
                    document.getElementById('customModal').classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                    location.reload();
                });

            } catch (error) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to accept payment: ' + error.message,
                    icon: 'error'
                });
            }
        };
    }

    // Lifeplan Modal Functions
    function openLifeplanModal(imageUrl, amount, paymentId, lifeplanId) {
        document.getElementById('lifeplanReceiptImage').src = '../customer/payments/' + imageUrl;
        document.getElementById('lifeplanAmountInput').value = amount;
        document.getElementById('lifeplanModal').classList.remove('hidden');
        document.body.classList.add('overflow-hidden');

        // Set current payment details
        currentPaymentType = 'lifeplan';
        currentPaymentId = paymentId;
        currentLifeplanId = lifeplanId;

        // Update decline button onclick
        document.getElementById('submitDecline').setAttribute('onclick', `declinePayment('lifeplan', '${paymentId}', '', '${lifeplanId}')`);

        const approveBtn = document.getElementById('approveLifeplanPayment');
        approveBtn.onclick = async function() {
            const amount = document.getElementById('lifeplanAmountInput').value;
            
            const { isConfirmed } = await Swal.fire({
                title: 'Confirm Acceptance',
                text: 'Are you sure you want to accept this payment?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, accept it!'
            });

            if (isConfirmed) {
                try {
                    const response = await fetch(
                        `payments/accept_lifeplan.php?payment_id=${paymentId}&lifeplan_id=${lifeplanId}&amount=${amount}`,
                        { method: 'GET' }
                    );
                    
                    if (!response.ok) throw new Error('Server error');

                    Swal.fire({
                        title: 'Success!',
                        text: 'Payment accepted successfully',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        location.reload();
                    });
                } catch (error) {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to accept payment: ' + error.message,
                        icon: 'error'
                    });
                }
            }
        };
        
        // Close button handlers
        document.getElementById('closeLifeplanModal').onclick = function() {
            document.getElementById('lifeplanModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };
        
        document.getElementById('cancelLifeplanModal').onclick = function() {
            document.getElementById('lifeplanModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        };

        // Add decline button to footer
        const footer = document.querySelector('#lifeplanModal .sticky.bottom-0');
        if (!footer.querySelector('#declineLifeplanPayment')) {
            const declineBtn = document.createElement('button');
            declineBtn.id = 'declineLifeplanPayment';
            declineBtn.className = 'w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center';
            declineBtn.innerHTML = '<i class="fas fa-times-circle mr-2"></i> Decline Payment';
            declineBtn.onclick = function() {
                document.getElementById('declinePaymentTitle').textContent = 'Lifeplan Payment - ' + amount;
                document.getElementById('declineModal').classList.remove('hidden');
            };
            footer.insertBefore(declineBtn, footer.lastElementChild);
        }
    }

    // Decline Payment Function
    function declinePayment(type, paymentId, salesId = '', lifeplanId = '') {
        const selectedReason = document.querySelector('input[name="declineReason"]:checked');
        let reason = '';
        
        if (!selectedReason) {
            Swal.fire('Error!', 'Please select a reason for declining', 'error');
            return;
        }
        
        reason = selectedReason.value;
        if (reason === 'Others') {
            reason = document.getElementById('otherReason').value;
            if (!reason.trim()) {
                Swal.fire('Error!', 'Please specify the reason', 'error');
                return;
            }
        }
        
        // Send the decline request
        let endpoint = '';
        if (type === 'traditional') {
            endpoint = `payments/decline_traditional.php?payment_id=${paymentId}&sales_id=${salesId}&reason=${encodeURIComponent(reason)}`;
        } else if (type === 'custom') {
            endpoint = `payments/decline_custom.php?payment_id=${paymentId}&reason=${encodeURIComponent(reason)}`;
        } else if (type === 'lifeplan') {
            endpoint = `payments/decline_lifeplan.php?payment_id=${paymentId}&lifeplan_id=${lifeplanId}&reason=${encodeURIComponent(reason)}`;
        }
        
        fetch(endpoint, { method: 'GET' })
            .then(response => {
                if (!response.ok) throw new Error('Server error');
                return response.json();
            })
            .then(data => {
                Swal.fire({
                    title: 'Success!',
                    text: 'Payment has been declined',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Close both modals and refresh
                    document.getElementById('declineModal').classList.add('hidden');
                    if (type === 'traditional') {
                        document.getElementById('traditionalModal').classList.add('hidden');
                    } else if (type === 'custom') {
                        document.getElementById('customModal').classList.add('hidden');
                    } else if (type === 'lifeplan') {
                        document.getElementById('lifeplanModal').classList.add('hidden');
                    }
                    document.body.classList.remove('overflow-hidden');
                    location.reload();
                });
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to decline payment: ' + error.message,
                    icon: 'error'
                });
            });
    }

    // Initialize decline modal functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Show/hide other reason textarea
        document.querySelectorAll('input[name="declineReason"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('otherReasonContainer').classList.toggle('hidden', this.value !== 'Others');
            });
        });

        // Close decline modal
        document.getElementById('closeDeclineModal').onclick = function() {
            document.getElementById('declineModal').classList.add('hidden');
        };
        
        document.getElementById('cancelDeclineModal').onclick = function() {
            document.getElementById('declineModal').classList.add('hidden');
        };
    });

    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target.id === 'traditionalModal') {
            document.getElementById('traditionalModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
        if (event.target.id === 'customModal') {
            document.getElementById('customModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
        if (event.target.id === 'lifeplanModal') {
            document.getElementById('lifeplanModal').classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
        if (event.target.id === 'declineModal') {
            document.getElementById('declineModal').classList.add('hidden');
        }
    });
</script>
</body>
</html>