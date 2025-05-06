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
JOIN services_tb AS s_tb ON sl_tb.service_id = s_tb.service_id";
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
$lifeplan_query = "SELECT * FROM lifeplanpayment_request_tb LIMIT 0"; // Placeholder
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
    <div class="bg-white rounded-lg shadow-sidebar p-6">
      <h2 class="text-xl font-semibold mb-4 text-sidebar-text">Traditional Payment Requests</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($traditional_requests as $request): ?>
          <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
              <div>
                <h3 class="font-medium"><?= htmlspecialchars($request['full_name']) ?></h3>
                <p class="text-sm text-gray-600"><?= htmlspecialchars($request['service_name']) ?></p>
                <p class="text-sm mt-2">
                  <span class="font-medium">Amount:</span> ₱<?= number_format($request['amount'], 2) ?>
                </p>
                <p class="text-sm">
                  <span class="font-medium">Date:</span> <?= date('M d, Y', strtotime($request['request_date'])) ?>
                </p>
                <p class="text-sm">
                  <span class="font-medium">Method:</span> <?= htmlspecialchars($request['payment_method']) ?>
                </p>
                <span class="inline-block mt-2 px-2 py-1 text-xs rounded-full 
                  <?= $request['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                     ($request['status'] == 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') ?>">
                  <?= ucfirst($request['status']) ?>
                </span>
              </div>
              <button onclick="openTraditionalModal('<?= htmlspecialchars($request['payment_url']) ?>', '<?= number_format($request['amount'], 2) ?>')" 
                class="px-3 py-1 bg-sidebar text-white rounded hover:bg-blue-700 transition-colors text-sm">
                View Receipt
              </button>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($traditional_requests)): ?>
          <p class="text-gray-500 col-span-3 text-center py-4">No traditional payment requests found.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Custom Packages Payment Requests Section -->
    <!-- Custom Packages Payment Requests Section -->
    <div class="bg-white rounded-lg shadow-sidebar p-6">
    <h2 class="text-xl font-semibold mb-4 text-sidebar-text">Custom Packages Payment Requests</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($custom_requests as $request): ?>
        <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
            <div>
                <h3 class="font-medium"><?= htmlspecialchars($request['full_name']) ?></h3>
                <p class="text-sm text-gray-600">Custom Package</span> ₱<?= number_format($request['discounted_price'], 2) ?></p>
                <p class="text-sm mt-2">
                <span class="font-medium">Amount:</span> ₱<?= number_format($request['amount'], 2) ?>
                </p>
                <p class="text-sm">
                <span class="font-medium">Date:</span> <?= date('M d, Y', strtotime($request['request_date'])) ?>
                </p>
                <p class="text-sm">
                <span class="font-medium">Method:</span> <?= htmlspecialchars($request['payment_method']) ?>
                </p>
                <span class="inline-block mt-2 px-2 py-1 text-xs rounded-full 
                <?= $request['status'] == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                    ($request['status'] == 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') ?>">
                <?= ucfirst($request['status']) ?>
                </span>
            </div>
            <button onclick="openCustomModal('<?= htmlspecialchars($request['payment_url']) ?>', '<?= number_format($request['amount'], 2) ?>')" 
                class="px-3 py-1 bg-sidebar text-white rounded hover:bg-blue-700 transition-colors text-sm">
                View Receipt
            </button>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (empty($custom_requests)): ?>
        <p class="text-gray-500 col-span-3 text-center py-4">No custom package payment requests found.</p>
        <?php endif; ?>
    </div>
    </div>

    <!-- Lifeplan Payment Requests Section -->
    <div class="bg-white rounded-lg shadow-sidebar p-6">
      <h2 class="text-xl font-semibold mb-4 text-sidebar-text">Lifeplan Payment Requests</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($lifeplan_requests as $request): ?>
          <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex justify-between items-start">
              <div>
                <h3 class="font-medium">Customer Name</h3>
                <p class="text-sm text-gray-600">Lifeplan</p>
                <p class="text-sm mt-2">
                  <span class="font-medium">Amount:</span> ₱0.00
                </p>
                <p class="text-sm">
                  <span class="font-medium">Date:</span> Jan 01, 2023
                </p>
                <p class="text-sm">
                  <span class="font-medium">Method:</span> Bank Transfer
                </p>
                <span class="inline-block mt-2 px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">
                  Pending
                </span>
              </div>
              <button onclick="openLifeplanModal('', '0.00')" 
                class="px-3 py-1 bg-sidebar text-white rounded hover:bg-blue-700 transition-colors text-sm">
                View Receipt
              </button>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($lifeplan_requests)): ?>
          <p class="text-gray-500 col-span-3 text-center py-4">No lifeplan payment requests found.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Traditional Payment Modal -->
<div id="traditionalModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-auto">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-semibold">Payment Receipt</h3>
      <button onclick="closeTraditionalModal()" class="text-gray-500 hover:text-gray-700">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="mb-4">
      <img id="traditionalReceiptImage" src="" alt="Payment Receipt" class="w-full h-auto border rounded">
    </div>
    <div class="bg-gray-100 p-4 rounded">
      <p class="font-medium">Amount: <span id="traditionalAmount" class="font-normal">₱0.00</span></p>
    </div>
    <div class="mt-4 flex justify-end">
      <button onclick="closeTraditionalModal()" class="px-4 py-2 bg-sidebar text-white rounded hover:bg-blue-700 transition-colors">
        Close
      </button>
    </div>
  </div>
</div>

<!-- Custom Packages Modal -->
<div id="customModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-auto">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-semibold">Payment Receipt</h3>
      <button onclick="closeCustomModal()" class="text-gray-500 hover:text-gray-700">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="mb-4">
      <img id="customReceiptImage" src="" alt="Payment Receipt" class="w-full h-auto border rounded">
    </div>
    <div class="bg-gray-100 p-4 rounded">
      <p class="font-medium">Amount: <span id="customAmount" class="font-normal">₱0.00</span></p>
    </div>
    <div class="mt-4 flex justify-end">
      <button onclick="closeCustomModal()" class="px-4 py-2 bg-sidebar text-white rounded hover:bg-blue-700 transition-colors">
        Close
      </button>
    </div>
  </div>
</div>

<!-- Lifeplan Modal -->
<div id="lifeplanModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-auto">
    <div class="flex justify-between items-center mb-4">
      <h3 class="text-xl font-semibold">Payment Receipt</h3>
      <button onclick="closeLifeplanModal()" class="text-gray-500 hover:text-gray-700">
        <i class="fas fa-times"></i>
      </button>
    </div>
    <div class="mb-4">
      <img id="lifeplanReceiptImage" src="" alt="Payment Receipt" class="w-full h-auto border rounded">
    </div>
    <div class="bg-gray-100 p-4 rounded">
      <p class="font-medium">Amount: <span id="lifeplanAmount" class="font-normal">₱0.00</span></p>
    </div>
    <div class="mt-4 flex justify-end">
      <button onclick="closeLifeplanModal()" class="px-4 py-2 bg-sidebar text-white rounded hover:bg-blue-700 transition-colors">
        Close
      </button>
    </div>
  </div>
</div>

<script src="script.js"></script>
<script src="tailwind.js"></script>
<script>
  // Traditional Payment Modal Functions
  function openTraditionalModal(imageUrl, amount) {
    document.getElementById('traditionalReceiptImage').src = '../customer/payments/' + imageUrl;

    document.getElementById('traditionalAmount').textContent = '₱' + amount;
    document.getElementById('traditionalModal').classList.remove('hidden');
  }

  function closeTraditionalModal() {
    document.getElementById('traditionalModal').classList.add('hidden');
  }

  // Custom Packages Modal Functions
  function openCustomModal(imageUrl, amount) {
    document.getElementById('customReceiptImage').src = '../customer/payments/' + imageUrl;
    document.getElementById('customAmount').textContent = '₱' + amount;
    document.getElementById('customModal').classList.remove('hidden');
  }

  function closeCustomModal() {
    document.getElementById('customModal').classList.add('hidden');
  }

  // Lifeplan Modal Functions
  function openLifeplanModal(imageUrl, amount) {
    document.getElementById('lifeplanReceiptImage').src = '../customer/payments/' + imageUrl;
    document.getElementById('lifeplanAmount').textContent = '₱' + amount;
    document.getElementById('lifeplanModal').classList.remove('hidden');
  }

  function closeLifeplanModal() {
    document.getElementById('lifeplanModal').classList.add('hidden');
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
</script>
</body>
</html>