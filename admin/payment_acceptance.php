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
$traditional_result = mysqli_query($conn, $traditional_query);
$traditional_requests = mysqli_fetch_all($traditional_result, MYSQLI_ASSOC);

// Fetch Custom Packages Payment Requests (replace with your actual query)
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
$custom_result = mysqli_query($conn, $custom_query);
$custom_requests = mysqli_fetch_all($custom_result, MYSQLI_ASSOC);

// Fetch Lifeplan Payment Requests (replace with your actual query)
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
JOIN services_tb AS s_tb ON sl_tb.service_id = s_tb.service_id";
$lifeplan_result = mysqli_query($conn, $lifeplan_query);
$lifeplan_requests = mysqli_fetch_all($lifeplan_result, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Payment Acceptance</title>
    <!-- Add Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="flex bg-gray-50">

<?php include 'admin_sidebar.php'; ?>

<!-- Main Content -->
<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Payment Acceptance</h1>
    </div>
    <div class="flex space-x-3">
    </div>
  </div>

  <!-- Page content -->
  <div class="space-y-8">
    <!-- Traditional Payment Requests Section -->
    <div class="bg-white rounded-xl shadow-md p-6 transition-all duration-300 hover:shadow-lg">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold text-sidebar-text flex items-center">
          <i class="fas fa-money-bill-wave mr-3 text-sidebar"></i>
          Traditional Payment Requests
        </h2>
        <span class="bg-blue-100 text-sidebar-text px-3 py-1 rounded-full text-sm font-medium">
          <?= count($traditional_requests) ?> Requests
        </span>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($traditional_requests as $request): ?>
          <div class="border border-gray-200 rounded-xl p-5 hover:shadow-lg transition-shadow bg-white relative overflow-hidden group">
            <!-- Status badge at top right -->
            <span class="absolute top-0 right-0 px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-bl-lg
              <?= $request['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                 ($request['status'] == 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') ?>">
              <?= ucfirst($request['status']) ?>
            </span>
            
            <div class="mb-4 pt-4">
              <h3 class="font-semibold text-lg text-sidebar-text"><?= htmlspecialchars($request['full_name']) ?></h3>
              <p class="text-gray-600 font-medium"><?= htmlspecialchars($request['service_name']) ?></p>
            </div>
            
            <div class="space-y-2 mb-4">
              <div class="flex justify-between items-center">
                <span class="text-gray-500">Total Price:</span>
                <span class="font-semibold">₱<?= number_format($request['discounted_price'], 2) ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-500">Payment Amount:</span>
                <span class="font-semibold text-sidebar-text">₱<?= number_format($request['amount'], 2) ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-500">Date:</span>
                <span><?= date('M d, Y', strtotime($request['request_date'])) ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-500">Method:</span>
                <span class="capitalize"><?= htmlspecialchars($request['payment_method']) ?></span>
              </div>
            </div>
            
            <div class="pt-3 border-t border-gray-100">
            <button onclick="openTraditionalModal('<?= htmlspecialchars($request['payment_url']) ?>', '<?= number_format($request['amount'], 2) ?>', '<?= $request['payment_id'] ?>', '<?= $request['sales_id'] ?>')" 
              class="w-full py-2 bg-sidebar-accent text-white rounded-lg hover:bg-hover-bg transition-colors flex items-center justify-center group-hover:shadow-md">
              <i class="fas fa-receipt mr-2"></i> View Receipt
            </button>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($traditional_requests)): ?>
          <div class="col-span-3 py-16 flex flex-col items-center justify-center bg-gray-50 rounded-xl border border-dashed border-gray-300">
            <i class="fas fa-file-invoice-dollar text-4xl text-gray-400 mb-3"></i>
            <p class="text-gray-500 text-lg">No traditional payment requests found.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Custom Packages Payment Requests Section -->
    <div class="bg-white rounded-xl shadow-md p-6 transition-all duration-300 hover:shadow-lg">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold text-sidebar-text flex items-center">
          <i class="fas fa-box-open mr-3 text-sidebar"></i>
          Custom Packages Payment Requests
        </h2>
        <span class="bg-blue-100 text-sidebar-text px-3 py-1 rounded-full text-sm font-medium">
          <?= count($custom_requests) ?> Requests
        </span>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($custom_requests as $request): ?>
          <div class="border border-gray-200 rounded-xl p-5 hover:shadow-lg transition-shadow bg-white relative overflow-hidden group">
            <!-- Status badge at top right -->
            <span class="absolute top-0 right-0 px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-bl-lg
              <?= $request['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                 ($request['status'] == 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') ?>">
              <?= ucfirst($request['status']) ?>
            </span>
            
            <div class="mb-4 pt-4">
              <h3 class="font-semibold text-lg text-sidebar-text"><?= htmlspecialchars($request['full_name']) ?></h3>
              <p class="text-gray-600 font-medium">Custom Package</p>
            </div>
            
            <div class="space-y-2 mb-4">
              <div class="flex justify-between items-center">
                <span class="text-gray-500">Total Price:</span>
                <span class="font-semibold">₱<?= number_format($request['discounted_price'], 2) ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-500">Payment Amount:</span>
                <span class="font-semibold text-sidebar-text">₱<?= number_format($request['amount'], 2) ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-500">Date:</span>
                <span><?= date('M d, Y', strtotime($request['request_date'])) ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-500">Method:</span>
                <span class="capitalize"><?= htmlspecialchars($request['payment_method']) ?></span>
              </div>
            </div>
            
            <div class="pt-3 border-t border-gray-100">
              <button onclick="openCustomModal('<?= htmlspecialchars($request['payment_url']) ?>', '<?= number_format($request['amount'], 2) ?>')" 
                class="w-full py-2 bg-sidebar-accent text-white rounded-lg hover:bg-hover-bg transition-colors flex items-center justify-center group-hover:shadow-md">
                <i class="fas fa-receipt mr-2"></i> View Receipt
              </button>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($custom_requests)): ?>
          <div class="col-span-3 py-16 flex flex-col items-center justify-center bg-gray-50 rounded-xl border border-dashed border-gray-300">
            <i class="fas fa-box-open text-4xl text-gray-400 mb-3"></i>
            <p class="text-gray-500 text-lg">No custom package payment requests found.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Lifeplan Payment Requests Section -->
    <div class="bg-white rounded-xl shadow-md p-6 transition-all duration-300 hover:shadow-lg">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold text-sidebar-text flex items-center">
          <i class="fas fa-heart mr-3 text-sidebar"></i>
          Lifeplan Payment Requests
        </h2>
        <span class="bg-blue-100 text-sidebar-text px-3 py-1 rounded-full text-sm font-medium">
          <?= count($lifeplan_requests) ?> Requests
        </span>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
        <?php foreach ($lifeplan_requests as $request): ?>
          <div class="border border-gray-200 rounded-xl p-5 hover:shadow-lg transition-shadow bg-white relative overflow-hidden group">
            <!-- Status badge at top right -->
            <span class="absolute top-0 right-0 px-3 py-1 text-xs font-bold uppercase tracking-wider rounded-bl-lg
              <?= $request['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                 ($request['status'] == 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') ?>">
              <?= ucfirst($request['status']) ?>
            </span>
            
            <div class="mb-4 pt-4">
              <h3 class="font-semibold text-lg text-sidebar-text"><?= htmlspecialchars($request['full_name']) ?></h3>
              <p class="text-gray-600 font-medium"><?= htmlspecialchars($request['service_name']) ?></p>
            </div>
            
            <div class="space-y-2 mb-4">
              <div class="flex justify-between items-center">
                <span class="text-gray-500">Total Price:</span>
                <span class="font-semibold">₱<?= number_format($request['custom_price'], 2) ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-500">Payment Amount:</span>
                <span class="font-semibold text-sidebar-text">₱<?= number_format($request['amount'], 2) ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-500">Date:</span>
                <span><?= date('M d, Y', strtotime($request['request_date'])) ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-gray-500">Method:</span>
                <span class="capitalize"><?= htmlspecialchars($request['payment_method']) ?></span>
              </div>
            </div>
            
            <div class="pt-3 border-t border-gray-100">
            <button onclick="openLifeplanModal('<?= htmlspecialchars($request['payment_url']) ?>', '<?= number_format($request['amount'], 2) ?>', '<?= $request['payment_id'] ?>', '<?= $request['lifeplan_id'] ?>')" 
              class="w-full py-2 bg-sidebar-accent text-white rounded-lg hover:bg-hover-bg transition-colors flex items-center justify-center group-hover:shadow-md">
              <i class="fas fa-receipt mr-2"></i> View Receipt
            </button>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($lifeplan_requests)): ?>
          <div class="col-span-3 py-16 flex flex-col items-center justify-center bg-gray-50 rounded-xl border border-dashed border-gray-300">
            <i class="fas fa-heart text-4xl text-gray-400 mb-3"></i>
            <p class="text-gray-500 text-lg">No lifeplan payment requests found.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<!-- Traditional Payment Modal -->
<div id="traditionalModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50 backdrop-blur-sm transition-all duration-300 overflow-y-auto py-8">
  <div class="bg-white rounded-xl p-0 max-w-3xl w-full mx-4 my-8 shadow-2xl transform transition-all duration-300 flex flex-col max-h-[calc(100vh-64px)]">
    <div class="bg-sidebar-text py-4 px-6 flex justify-between items-center sticky top-0 z-10">
      <h3 class="text-xl font-semibold text-white flex items-center">
        <i class="fas fa-receipt mr-2"></i> Payment Receipt
      </h3>
      <button onclick="closeTraditionalModal()" class="text-white hover:text-gray-200 transition-colors">
        <i class="fas fa-times text-lg"></i>
      </button>
    </div>
    
    <!-- Scrollable content area -->
    <div class="p-6 overflow-y-auto flex-1">
      <div class="mb-6 rounded-lg overflow-hidden border-4 border-gray-200 shadow-lg">
        <img id="traditionalReceiptImage" src="" alt="Payment Receipt" class="w-full max-h-[70vh] object-contain mx-auto">
      </div>
      
      <div class="bg-blue-50 p-5 rounded-lg shadow-inner mb-6">
        <div class="flex justify-between items-center">
          <p class="text-sidebar-text font-semibold text-lg">Payment Amount:</p>
          <p id="traditionalAmount" class="text-2xl font-bold text-sidebar">₱0.00</p>
        </div>
      </div>
    </div>
    
    <!-- Action buttons - now outside scrollable area -->
    <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4">
      <div class="flex space-x-4 justify-end">
        <button onclick="closeTraditionalModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
          Close
        </button>
        <button class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
          <i class="fas fa-check mr-2"></i> Approve Payment
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Custom Packages Modal -->
<div id="customModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50 backdrop-blur-sm transition-all duration-300 overflow-y-auto py-8">
  <div class="bg-white rounded-xl p-0 max-w-3xl w-full mx-4 my-8 shadow-2xl transform transition-all duration-300 flex flex-col max-h-[calc(100vh-64px)]">
    <div class="bg-sidebar-text py-4 px-6 flex justify-between items-center sticky top-0 z-10">
      <h3 class="text-xl font-semibold text-white flex items-center">
        <i class="fas fa-box-open mr-2"></i> Custom Package Receipt
      </h3>
      <button onclick="closeCustomModal()" class="text-white hover:text-gray-200 transition-colors">
        <i class="fas fa-times text-lg"></i>
      </button>
    </div>
    
    <!-- Scrollable content area -->
    <div class="p-6 overflow-y-auto flex-1">
      <div class="mb-6 rounded-lg overflow-hidden border-4 border-gray-200 shadow-lg">
        <img id="customReceiptImage" src="" alt="Payment Receipt" class="w-full max-h-[70vh] object-contain mx-auto">
      </div>
      
      <div class="bg-blue-50 p-5 rounded-lg shadow-inner mb-6">
        <div class="flex justify-between items-center">
          <p class="text-sidebar-text font-semibold text-lg">Payment Amount:</p>
          <p id="customAmount" class="text-2xl font-bold text-sidebar">₱0.00</p>
        </div>
      </div>
    </div>
    
    <!-- Action buttons - now outside scrollable area -->
    <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4">
      <div class="flex space-x-4 justify-end">
        <button onclick="closeCustomModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
          Close
        </button>
        <button class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
          <i class="fas fa-check mr-2"></i> Approve Payment
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Lifeplan Modal -->
<div id="lifeplanModal" class="fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center hidden z-50 backdrop-blur-sm transition-all duration-300 overflow-y-auto py-8">
  <div class="bg-white rounded-xl p-0 max-w-3xl w-full mx-4 my-8 shadow-2xl transform transition-all duration-300 flex flex-col max-h-[calc(100vh-64px)]">
    <div class="bg-sidebar-text py-4 px-6 flex justify-between items-center sticky top-0 z-10">
      <h3 class="text-xl font-semibold text-white flex items-center">
        <i class="fas fa-heart mr-2"></i> Lifeplan Payment Receipt
      </h3>
      <button onclick="closeLifeplanModal()" class="text-white hover:text-gray-200 transition-colors">
        <i class="fas fa-times text-lg"></i>
      </button>
    </div>
    
    <!-- Scrollable content area -->
    <div class="p-6 overflow-y-auto flex-1">
      <div class="mb-6 rounded-lg overflow-hidden border-4 border-gray-200 shadow-lg">
        <img id="lifeplanReceiptImage" src="" alt="Payment Receipt" class="w-full max-h-[70vh] object-contain mx-auto">
      </div>
      
      <div class="bg-blue-50 p-5 rounded-lg shadow-inner mb-6">
        <div class="flex justify-between items-center">
          <p class="text-sidebar-text font-semibold text-lg">Payment Amount:</p>
          <p id="lifeplanAmount" class="text-2xl font-bold text-sidebar">₱0.00</p>
        </div>
      </div>
    </div>
    
    <!-- Action buttons - now outside scrollable area -->
    <div class="sticky bottom-0 bg-white border-t border-gray-200 px-6 py-4">
      <div class="flex space-x-4 justify-end">
        <button onclick="closeLifeplanModal()" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
          Close
        </button>
        <button class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
          <i class="fas fa-check mr-2"></i> Approve Payment
        </button>
      </div>
    </div>
  </div>
</div>

<script src="script.js"></script>
<script src="tailwind.js"></script>
<script>
    // Traditional Payment Modal Functions
    function openTraditionalModal(imageUrl, amount, paymentId, salesId) {
      document.getElementById('traditionalReceiptImage').src = '../customer/payments/' + imageUrl;
      document.getElementById('traditionalAmount').textContent = '₱' + amount;
      document.getElementById('traditionalModal').classList.remove('hidden');
      document.body.classList.add('overflow-hidden');

      const approveBtn = document.querySelector('#traditionalModal .bg-green-600');
      
      approveBtn.onclick = async function() {
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

          if (!isConfirmed) return; // Exit if user cancels

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
                  // 4. Optional: Close modal and refresh data (if needed)
                  document.getElementById('traditionalModal').classList.add('hidden');
                  document.body.classList.remove('overflow-hidden');
                  // location.reload(); // Uncomment if you need to refresh the page
              });

          } catch (error) {
              Swal.fire({
                  title: 'Error!',
                  text: 'Failed to accept payment: ' + error.message,
                  icon: 'error'
              });
          }
      };

      // Modal animation
      setTimeout(() => {
          document.getElementById('traditionalModal').querySelector('.bg-white').classList.add('scale-100');
          document.getElementById('traditionalModal').querySelector('.bg-white').classList.remove('scale-95');
      }, 10);
  }

  function closeTraditionalModal() {
    document.getElementById('traditionalModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  // Custom Packages Modal Functions
  function openCustomModal(imageUrl, amount) {
    document.getElementById('customReceiptImage').src = '../customer/payments/' + imageUrl;
    document.getElementById('customAmount').textContent = '₱' + amount;
    document.getElementById('customModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    
    // Add fade-in animation
    setTimeout(() => {
      document.getElementById('customModal').querySelector('.bg-white').classList.add('scale-100');
      document.getElementById('customModal').querySelector('.bg-white').classList.remove('scale-95');
    }, 10);
  }

  function closeCustomModal() {
    document.getElementById('customModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  // Lifeplan Modal Functions
  function openLifeplanModal(imageUrl, amount, paymentId, lifeplanId) {
    document.getElementById('lifeplanReceiptImage').src = '../customer/payments/' + imageUrl;
    document.getElementById('lifeplanAmount').textContent = '₱' + amount;
    document.getElementById('lifeplanModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');

    const approveBtn = document.querySelector('#lifeplanModal .bg-green-600');
    approveBtn.onclick = function() {
      window.location.href = `payments/accept_lifeplan.php?payment_id=${paymentId}&lifeplan_id=${lifeplanId}&amount=${amount}`;
    };
    
    // Add fade-in animation
    setTimeout(() => {
      document.getElementById('lifeplanModal').querySelector('.bg-white').classList.add('scale-100');
      document.getElementById('lifeplanModal').querySelector('.bg-white').classList.remove('scale-95');
    }, 10);
  }

  function closeLifeplanModal() {
    document.getElementById('lifeplanModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
  }

  // Close modals when clicking outside
  window.addEventListener('click', function(event) {
    if (event.target.id === 'traditionalModal') {
      closeTraditionalModal();
    }
    if (event.target.id === 'customModal') {
      closeCustomModal();
    }
    if (event.target.id === 'lifeplanModal') {
      closeLifeplanModal();
    }
  });
  
  // Initialize modals with scale-95 for animation
  document.addEventListener('DOMContentLoaded', function() {
    const modals = document.querySelectorAll('#traditionalModal .bg-white, #customModal .bg-white, #lifeplanModal .bg-white');
    modals.forEach(modal => {
      modal.classList.add('scale-95', 'transition-transform', 'duration-300');
    });
  });
</script>
</body>
</html>