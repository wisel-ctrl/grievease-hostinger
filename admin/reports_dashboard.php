<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include 'faviconLogo.php'; 
include 'report_queries.php';

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

// Get branch filter from request
$branchFilter = isset($_GET['branch']) ? $_GET['branch'] : 'all';
$branchId = null;

if ($branchFilter === 'paete') {
    $branchId = 1;
} elseif ($branchFilter === 'pila') {
    $branchId = 2;
}

// Fetch data using our new functions
$revenueData = getRevenueData($conn, $branchId);
$casketData = getCasketData($conn, $branchId);
$salesData = getSalesData($conn, $branchId);
$basicStats = getBasicStats($conn, $branchId);
$lastDate = getLastDate($conn, $branchId);
$changes = getChanges($conn, $lastDate, $branchId);

// Extract values for display
// Extract values for display with null checks
$avgPrice = number_format($basicStats['avg_price'] ?? 0, 2);
$avgPayment = number_format($basicStats['avg_payment'] ?? 0, 2);
$paymentRatio = number_format($basicStats['payment_ratio'] ?? 0, 1);

$priceChange = number_format($changes['price_change'] ?? 0, 1);
$paymentChange = number_format($changes['payment_change'] ?? 0, 1);
$ratioChange = number_format($changes['ratio_change'] ?? 0, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Reports</title>
    <!-- Add Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>  
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
        
</head>
<body class="flex bg-gray-50">

<?php include 'admin_sidebar.php'; ?>

<!-- Main Content -->
<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
  <!-- Header with breadcrumb and welcome message -->
  <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
    <div>
      <h1 class="text-2xl font-bold text-sidebar-text">Reports</h1>
    </div>
    <div class="flex space-x-3">
      <div class="relative">
        <button id="branchDropdownButton" class="flex items-center justify-between p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300 w-32">
          <span class="truncate">All Branches</span>
          <i class="fas fa-chevron-down ml-2 text-xs"></i>
        </button>
        <div id="branchDropdown" class="hidden absolute right-0 mt-1 w-48 bg-white rounded-md shadow-lg z-10 border border-sidebar-border">
          <ul class="py-1">
            <li>
              <a href="#" class="block px-4 py-2 text-sm text-sidebar-text hover:bg-sidebar-hover branch-option" data-branch="all">All Branches</a>
            </li>
            <li>
              <a href="#" class="block px-4 py-2 text-sm text-sidebar-text hover:bg-sidebar-hover branch-option" data-branch="pila">Pila</a>
            </li>
            <li>
              <a href="#" class="block px-4 py-2 text-sm text-sidebar-text hover:bg-sidebar-hover branch-option" data-branch="paete">Paete</a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- Analytics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <!-- Sales Forecast Card -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <!-- Card header with brighter gradient background -->
        <div class="bg-gradient-to-r from-blue-100 to-blue-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Sales Forecast (next 6 Months)</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-blue-600 flex items-center justify-center">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold text-gray-800 sales-forecast-value">₱000,000</span>
            </div>
        </div>
        
        <!-- Card footer with change indicator -->
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-emerald-600">
                <i class="fas fa-arrow-up mr-1.5 text-xs"></i>
                <span class="font-medium text-xs">12% </span>
                <span class="text-xs text-gray-500 ml-1">projected growth</span>
            </div>
        </div>
    </div>
    
    <!-- Projected Orders Card -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <!-- Card header with brighter gradient background -->
        <div class="bg-gradient-to-r from-purple-100 to-purple-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Projected Orders</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-purple-600 flex items-center justify-center">
                    <i class="fas fa-box"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold text-gray-800">86</span>
            </div>
        </div>
        
        <!-- Card footer with change indicator -->
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-emerald-600">
                <i class="fas fa-arrow-up mr-1.5 text-xs"></i>
                <span class="font-medium text-xs">8% </span>
                <span class="text-xs text-gray-500 ml-1">from this quarter</span>
            </div>
        </div>
    </div>
    
    <!-- Payment Rate Card -->
    <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
        <!-- Card header with brighter gradient background -->
        <div class="bg-gradient-to-r from-green-100 to-green-200 px-6 py-4">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Payment Rate</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-green-600 flex items-center justify-center">
                    <i class="fas fa-money-check-alt"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold text-gray-800"><?php echo $paymentRatio; ?>%</span>
            </div>
        </div>
        
        <!-- Card footer with change indicator -->
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-emerald-600">
                <i class="fas fa-arrow-up mr-1.5 text-xs"></i>
                <span class="font-medium text-xs">3.2% </span>
                <span class="text-xs text-gray-500 ml-1">from last month</span>
            </div>
        </div>
    </div>
</div>

<!-- Main Analytics Section -->
<div class="grid grid-cols-1 gap-4 mb-6">
  <!-- Sales Forecast Card -->
  <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
      <!-- Card header with brighter gradient background -->
      <div class="bg-gradient-to-r from-blue-100 to-blue-200 px-6 py-4">
          <div class="flex items-center justify-between mb-1">
              <h3 class="text-sm font-medium text-gray-700">Sales Forecast (Next 6 Months)</h3>
              <button  onclick="printRevenueTables()" class="w-10 h-10 rounded-full bg-white/90 text-blue-600 flex items-center justify-center hover:bg-blue-50" title="Print/Export">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                  </svg>
              </button>
          </div>
      </div>
      <div id="salesForecastChart"></div>
      <!-- Card footer with change indicator -->
      <div class="px-6 py-3 bg-white border-t border-gray-100">
          <div class="flex items-center text-emerald-600">
              <i class="fas fa-arrow-up mr-1.5 text-xs"></i>
              <span class="font-medium text-xs forecast-accuracy-value">0% </span>
              <span class="text-xs text-gray-500 ml-1">forecast accuracy</span>
          </div>
      </div>
  </div>
  
  <!-- Demand Prediction -->
  <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden mb-6">
    <!-- Card header with brighter gradient background -->
    <div class="bg-gradient-to-r from-purple-100 to-purple-200 px-6 py-4">
      <div class="flex items-center justify-between mb-1">
        <h3 class="text-sm font-medium text-gray-700">Demand Prediction</h3>
        <button onclick="printDemandPrediction()" class="w-10 h-10 rounded-full bg-white/90 text-purple-600 flex items-center justify-center hover:bg-purple-50" title="Print/Export">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
            </svg>
        </button>
      </div>
    </div>
    
    <div class="p-5">
      <div class="relative" style="height: 400px; min-height: 400px;">
        <div id="demandPredictionChart" class="absolute top-0 left-0 w-full h-full"></div>
      </div>
    </div>
    
    <!-- Card footer with statistics -->
    <div class="px-6 py-4 bg-white border-t border-gray-100">
      <div class="grid grid-cols-3 gap-4 text-sm">
        <div class="bg-gray-50 p-3 rounded-lg">
          <div class="font-medium text-gray-600 mb-1">Top Casket</div>
          <div id="topCasketValue" class="font-bold text-gray-800">Premium Casket</div>
        </div>
        <div class="bg-gray-50 p-3 rounded-lg">
          <div class="font-medium text-gray-600 mb-1">Growth Rate</div>
          <div id="growthRateValue" class="font-bold text-green-600">+14.2%</div>
        </div>
        <div class="bg-gray-50 p-3 rounded-lg">
          <div class="font-medium text-gray-600 mb-1">Seasonality Impact</div>
          <div id="seasonalityImpactValue" class="font-bold text-yellow-600">Medium</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
  <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
    <h3 class="font-medium text-sidebar-text">Sales & Payment Trends</h3>
    <button id="printButton" class="w-10 h-10 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-gray-200" title="Print/Export">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
        </svg>
    </button>
  </div>
  <div class="p-5">
    <canvas id="salesSplineChart" class="h-96"></canvas>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-5 px-5 pb-5">
    <div class="bg-gray-50 p-4 rounded-lg">
        <div class="text-sm font-medium text-gray-600 mb-2">Average Price</div>
          <div class="text-2xl font-bold text-sidebar-text">₱<?php echo $avgPrice; ?> 
              <span class="<?php echo ($priceChange >= 0) ? 'text-green-600' : 'text-red-600'; ?> text-sm font-normal">
                  <?php echo ($priceChange >= 0) ? '+' : ''; ?><?php echo $priceChange; ?>%
              </span>
          </div>
        </div>
    <div class="bg-gray-50 p-4 rounded-lg">
        <div class="text-sm font-medium text-gray-600 mb-2">Average Payment</div>
          <div class="text-2xl font-bold text-sidebar-text">₱<?php echo $avgPayment; ?> 
              <span class="<?php echo ($paymentChange >= 0) ? 'text-green-600' : 'text-red-600'; ?> text-sm font-normal">
                  <?php echo ($paymentChange >= 0) ? '+' : ''; ?><?php echo $paymentChange; ?>%
              </span>
          </div>
        </div>
    <div class="bg-gray-50 p-4 rounded-lg">
        <div class="text-sm font-medium text-gray-600 mb-2">Payment Ratio</div>
        <div class="text-2xl font-bold text-sidebar-text"><?php echo $paymentRatio; ?>% 
            <span class="<?php echo ($ratioChange >= 0) ? 'text-green-600' : 'text-red-600'; ?> text-sm font-normal">
                <?php echo ($ratioChange >= 0) ? '+' : ''; ?><?php echo $ratioChange; ?>%
            </span>
        </div>
    </div>
</div>

<div id="printContent" style="display:none;">
    <div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;">
        <!-- Header -->
        <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #3A57E8; padding-bottom: 10px;">
            <h1 style="color: #3A57E8; margin-bottom: 5px;">VJay Relova Funeral Services</h1>
            <h3 style="color: #666; margin-top: 0; margin-bottom: 5px;">#6 J.P Rizal St. Brgy. Sta Clara Sur, (Pob) Pila, Laguna</h3>
            <h2 style="color: #3A57E8; margin-bottom: 5px;">Revenue Report</h2>
            <p style="color: #666; margin-top: 0;">Generated on: <span id="printDate"></span></p>
        </div>
        
        <!-- Historical Revenue Table -->
        <h2 style="color: #3A57E8; margin-top: 30px; margin-bottom: 15px;">Historical Revenue</h2>
        <table id="historicalTable" style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
            <thead>
                <tr style="background-color: #f5f7ff;">
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #3A57E8;">Month</th>
                    <th style="padding: 10px; text-align: right; border-bottom: 2px solid #3A57E8;">Revenue (PHP)</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        
        <!-- Forecasted Revenue Table -->
        <h2 style="color: #FF5733; margin-top: 30px; margin-bottom: 15px;">Forecasted Revenue</h2>
        <table id="forecastTable" style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
            <thead>
                <tr style="background-color: #fff5f3;">
                    <th style="padding: 10px; text-align: left; border-bottom: 2px solid #FF5733;">Month</th>
                    <th style="padding: 10px; text-align: right; border-bottom: 2px solid #FF5733;">Revenue (PHP)</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
        
        <!-- Footer -->
        <div style="text-align: center; margin-top: 30px; border-top: 2px solid #3A57E8; padding-top: 10px; color: #666; font-size: 12px;">
            <p>Forecast Accuracy: <span id="printAccuracy"></span></p>
            <p>Contact: (0956) 814-3000 | (0961) 345-4283 | Email: GrievEase@gmail.com</p>
            <p>© <?php echo date('Y'); ?> VJay Relova Funeral Services. All rights reserved.</p>
        </div>
    </div>
</div>

<div id="printableTable" class="hidden bg-white p-5" style="font-family: Arial, sans-serif; width: 100%;">
    <!-- Header -->
    <div style="text-align: center; margin-bottom: 20px; border-bottom: 2px solid #3A57E8; padding-bottom: 10px;">
        <h1 style="color: #3A57E8; margin-bottom: 5px; font-size: 24px;">VJay Relova Funeral Services</h1>
        <h3 style="color: #666; margin-top: 0; margin-bottom: 5px; font-size: 14px;">#6 J.P Rizal St. Brgy. Sta Clara Sur, (Pob) Pila, Laguna</h3>
        <h2 style="color: #3A57E8; margin-bottom: 5px; font-size: 18px;">Sales & Payment Trends Report</h2>
        <p style="color: #666; margin-top: 0; font-size: 12px;">Generated on: <span id="printDate"></span></p>
    </div>
    
    <h2 style="font-size: 16px; font-weight: bold; margin-bottom: 10px;">Sales & Payment Trends Data</h2>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
        <thead>
            <tr style="background-color: #f3f4f6;">
                <th style="border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px;">Month</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: right; font-size: 12px;">Accrued Revenue (₱)</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: right; font-size: 12px;">Cash Revenue (₱)</th>
                <th style="border: 1px solid #ddd; padding: 8px; text-align: center; font-size: 12px;">Type</th>
            </tr>
        </thead>
        <tbody id="tableBody" style="font-size: 12px;">
            <!-- Table content will be filled by JavaScript -->
        </tbody>
    </table>
    
    <!-- Footer -->
    <div style="text-align: center; margin-top: 20px; border-top: 2px solid #3A57E8; padding-top: 10px; color: #666; font-size: 10px;">
        <p>Contact: (0956) 814-3000 | (0961) 345-4283 | Email: GrievEase@gmail.com</p>
        <p>© <?php echo date('Y'); ?> VJay Relova Funeral Services. All rights reserved.</p>
    </div>
</div>

</div>
<footer class="bg-white rounded-lg shadow-sidebar border border-sidebar-border p-4 text-center text-sm text-gray-500 mt-8">
    <p>© 2025 GrievEase.</p>
  </footer>
<!-- JavaScript for Charts -->
<script>
    // Pass PHP data to JavaScript
  const historicalRevenueData = <?php echo json_encode($revenueData); ?>;

  function createManilaDate(dateString) {
      const date = new Date(dateString);
      // Manila is UTC+8, so we need to adjust the time accordingly
      return new Date(date.getTime() + (8 * 60 * 60 * 1000));
  }

   function formatCurrency(value) {
        return '₱' + parseFloat(value).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatDate(date) {
        return date.toLocaleDateString('en-PH', {
            timeZone: 'Asia/Manila',
            month: 'short',
            year: 'numeric'
        });
    }

    function printRevenueTables() {
        if (!historicalRevenueData || historicalRevenueData.length === 0) {
            alert('No revenue data available to print');
            return;
        }

        const regressionResults = calculateLinearRegressionForecast(historicalRevenueData, 6);
        
        // Set the current date in the print content
        const now = new Date();
        document.getElementById('printDate').textContent = now.toLocaleString('en-PH', {
            timeZone: 'Asia/Manila',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        // Calculate forecast accuracy
        const lastActual = regressionResults.historicalChartData[regressionResults.historicalChartData.length - 1].y;
        const firstForecast = regressionResults.forecastData[0].y;
        let forecastAccuracy = 0;
        if (lastActual !== 0) {
            forecastAccuracy = 100 - Math.abs((firstForecast - lastActual) / lastActual * 100);
        }
        document.getElementById('printAccuracy').textContent = forecastAccuracy.toFixed(1) + '%';
        
        // Populate historical table
        const historicalTableBody = document.querySelector('#historicalTable tbody');
        historicalTableBody.innerHTML = '';
        regressionResults.historicalChartData.forEach(item => {
            const row = document.createElement('tr');
            row.style.borderBottom = '1px solid #eee';
            
            const dateCell = document.createElement('td');
            dateCell.style.padding = '8px 10px';
            dateCell.textContent = formatDate(new Date(item.x));
            
            const revenueCell = document.createElement('td');
            revenueCell.style.padding = '8px 10px';
            revenueCell.style.textAlign = 'right';
            revenueCell.textContent = formatCurrency(item.y);
            
            row.appendChild(dateCell);
            row.appendChild(revenueCell);
            historicalTableBody.appendChild(row);
        });
        
        // Populate forecast table
        const forecastTableBody = document.querySelector('#forecastTable tbody');
        forecastTableBody.innerHTML = '';
        regressionResults.forecastData.forEach(item => {
            const row = document.createElement('tr');
            row.style.borderBottom = '1px solid #eee';
            
            const dateCell = document.createElement('td');
            dateCell.style.padding = '8px 10px';
            dateCell.textContent = formatDate(new Date(item.x));
            
            const revenueCell = document.createElement('td');
            revenueCell.style.padding = '8px 10px';
            revenueCell.style.textAlign = 'right';
            revenueCell.textContent = formatCurrency(item.y);
            
            row.appendChild(dateCell);
            row.appendChild(revenueCell);
            forecastTableBody.appendChild(row);
        });
        
        // Show the print content
        const printContent = document.getElementById('printContent').cloneNode(true);
        printContent.style.display = 'block';
        
        // Create a new window for printing
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Revenue Report - VJay Relova Funeral Services</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        table { width: 100%; border-collapse: collapse; }
                        th { text-align: left; padding: 8px 10px; background-color: #f5f5f5; }
                        td { padding: 8px 10px; border-bottom: 1px solid #eee; }
                        .text-right { text-align: right; }
                    </style>
                </head>
                <body>
                    ${printContent.innerHTML}
                </body>
            </html>
        `);
        printWindow.document.close();
        
        // Wait for content to load before printing
        printWindow.onload = function() {
            setTimeout(() => {
                printWindow.print();
                printWindow.close();
            }, 500);
        };
    }
</script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Adjust chart sizing for better responsiveness
  const resizeCharts = function() {
    const charts = document.querySelectorAll('canvas');
    charts.forEach(chart => {
      chart.style.maxWidth = '100%';
    });
  };
  
  // Call on load and on window resize
  resizeCharts();
  window.addEventListener('resize', resizeCharts);

  // Sales Forecasting Chart
  // Sales Forecasting Chart with Linear Regression
  function calculateLinearRegressionForecast(historicalData, forecastMonths = 6) {
      // Convert historical data to numerical format
      const dataPoints = historicalData.map((item, index) => ({
          x: index, // Use index as x-value instead of timestamp for better scaling
          y: parseFloat(item.monthly_revenue),
          date: createManilaDate(item.month_start)
      }));
      
      // Calculate means
      const meanX = dataPoints.reduce((sum, point) => sum + point.x, 0) / dataPoints.length;
      const meanY = dataPoints.reduce((sum, point) => sum + point.y, 0) / dataPoints.length;
      
      // Calculate slope (a) and intercept (b)
      let numerator = 0;
      let denominator = 0;
      
      dataPoints.forEach(point => {
          numerator += (point.x - meanX) * (point.y - meanY);
          denominator += Math.pow(point.x - meanX, 2);
      });
      
      const slope = numerator / denominator;
      const intercept = meanY - slope * meanX;
      
      // Generate forecast
      const forecastData = [];
      const lastHistoricalDate = createManilaDate(historicalData[historicalData.length - 1].month_start);

      for (let i = 1; i <= forecastMonths; i++) {
          const forecastDate = new Date(lastHistoricalDate);
          forecastDate.setMonth(forecastDate.getMonth() + i);
          
          const xValue = dataPoints.length + i - 1;
          const predictedY = slope * xValue + intercept;
          
          forecastData.push({
              x: forecastDate.getTime(), // Use actual date for display
              y: Math.max(0, predictedY)
          });
      }
      
      return {
          slope,
          intercept,
          forecastData,
          historicalChartData: dataPoints.map((point, index) => ({
              x: point.date.getTime(), // Use actual date for display
              y: point.y
          }))
      };
  }

  window.calculateLinearRegressionForecast = calculateLinearRegressionForecast;

  // Only proceed if we have historical data
  // Only proceed if we have historical data
  if (historicalRevenueData && historicalRevenueData.length > 0) {
      const regressionResults = calculateLinearRegressionForecast(historicalRevenueData, 6);
      
      // Calculate forecast accuracy metrics
      const lastActual = regressionResults.historicalChartData[regressionResults.historicalChartData.length - 1].y;
      const firstForecast = regressionResults.forecastData[0].y;
      let forecastAccuracy = 0;
      if (lastActual !== 0) {
          forecastAccuracy = 100 - Math.abs((firstForecast - lastActual) / lastActual * 100);
      } else {
          // Handle zero case - maybe show N/A or use a different metric
          forecastAccuracy = 100; // Or whatever makes sense for your business
      }
      
      
      // Update the forecast accuracy display
      document.querySelector('.forecast-accuracy-value').textContent = forecastAccuracy.toFixed(1) + '%';
      
      // Create the chart with explicit line styles
      var options = {
          series: [{
              name: 'Actual Revenue',
              data: regressionResults.historicalChartData,
              color: '#3A57E8', // Explicit color for actual
              stroke: {
                  width: 3,
                  curve: 'smooth',
                  lineCap: 'round',
                  dashArray: 0 // Ensures solid line
              }
          }, {
              name: 'Forecasted Revenue',
              data: regressionResults.forecastData,
              color: '#FF5733', // Explicit color for forecast
              stroke: {
                  width: 3,
                  curve: 'smooth',
                  dashArray: [5, 5] // Explicit dashed pattern
              }
          }],
          chart: {
              height: 350,
              type: 'line',
              animations: {
                  enabled: false
              },
              toolbar: {
                  show: true,
                  tools: {
                      download: false // Disables the download menu
                  }
              }
          },
          xaxis: {
              type: 'datetime',
              tickAmount: 10,
              labels: {
                  formatter: function(value) {
                      const date = new Date(value);
                      // Format in Manila time
                      return date.toLocaleDateString('en-PH', { 
                          timeZone: 'Asia/Manila',
                          month: 'short', 
                          year: 'numeric' 
                      });
                  }
              }
          },
          yaxis: {
              labels: {
                  formatter: function(value) {
                    return value.toLocaleString('en-PH', {
                        style: 'currency',
                        currency: 'PHP',
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                  }
              }
          },
          title: {
              text: 'Revenue Forecast',
              align: 'left',
              style: {
                  fontSize: "16px",
                  color: '#666'
              }
          },
          markers: {
              size: 4,
              hover: {
                  size: 7
              }
          },
          tooltip: {
              x: {
                  formatter: function(value) {
                      return new Date(value).toLocaleDateString('en-PH', {
                          timeZone: 'Asia/Manila',
                          month: 'short',
                          year: 'numeric'
                      });
                  }
              },
              y: {
                  formatter: function(value) {
                      return '₱' + value.toLocaleString('en-PH');
                  }
              }
          }
      };

      var chart = new ApexCharts(document.querySelector("#salesForecastChart"), options);
      chart.render();
      
      // Update forecast summary
      const totalForecast = regressionResults.forecastData.reduce((sum, point) => sum + point.y, 0);
      document.querySelector('.sales-forecast-value').textContent = 
    '₱' + parseFloat(totalForecast).toLocaleString('en-PH', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
  } else {
      document.querySelector("#salesForecastChart").innerHTML = '<div class="p-4 text-center text-gray-500">No revenue data available</div>';
  }

});
</script>

<script>
  function processDataForHeatmap(data, forecastMonths = 6) {
    console.group('Initial Data Processing');
    console.log('Raw data from PHP:', data);
    
    // Get unique item names
    const items = [...new Set(data.map(d => d.item_name))];
    console.log('Unique items:', items);
    
    // Separate actual and forecast data
    const actualData = data.filter(d => !d.is_forecast);
    const forecastData = data.filter(d => d.is_forecast);
    console.log('Actual data records:', actualData.length);
    console.log('Forecast data records:', forecastData.length);
    
    // Get unique months (sorted)
    const months = [...new Set(actualData.map(d => d.sale_month))].sort();
    console.log('Actual months:', months);
    
    // Get forecast months (already included in the SQL query)
    const forecastDates = [...new Set(forecastData.map(d => d.sale_month))].sort();
    console.log('Forecast months:', forecastDates);
    
    // Combine actual and forecast months
    const allMonths = [...months, ...forecastDates];
    console.log('All months combined:', allMonths);
    
    // Calculate total sales per item to find top casket
    const itemTotals = {};
    items.forEach(item => {
      itemTotals[item] = actualData
        .filter(d => d.item_name === item)
        .reduce((sum, d) => sum + d.casket_sold, 0);
    });
    console.log('Item totals:', itemTotals);
    
    // Find the top casket (highest total sales)
    const topCasket = Object.keys(itemTotals).reduce((a, b) => 
      itemTotals[a] > itemTotals[b] ? a : b
    );
    console.log('Top casket:', topCasket, 'with', itemTotals[topCasket], 'units');
    
    // Calculate overall growth rate
    let overallGrowthRate = 0;
    let seasonalityImpact = "Low";
    
    console.group('Series Processing');
    // Create series for each item
    const series = items.map(item => {
      console.group(`Processing item: ${item}`);
      
      // Combine actual and forecast data for this item
      const itemData = data.filter(d => d.item_name === item);
      console.log('Item data:', itemData);
      
      // Format the data for the heatmap
      const formattedData = allMonths.map(month => {
        const found = itemData.find(d => d.sale_month === month);
        const dataPoint = {
          x: month,
          y: found ? found.casket_sold : 0,
          forecast: found ? found.is_forecast : false
        };
        console.log(`Data point for ${month}:`, dataPoint);
        return dataPoint;
      });
      
      // Calculate growth rate for display (only based on actual data)
      const actualItemData = actualData.filter(d => d.item_name === item);
      let growthRate = 0.05; // Default 5%
      
      if (actualItemData.length >= 2) {
        const recentValues = actualItemData.slice(-3).map(d => d.casket_sold).filter(y => y > 0);
        console.log('Recent values for growth calculation:', recentValues);
        
        if (recentValues.length >= 2) {
          const oldest = recentValues[0];
          const newest = recentValues[recentValues.length - 1];
          console.log(`Growth calc - oldest: ${oldest}, newest: ${newest}`);
          
          if (oldest > 0) {
            growthRate = (newest / oldest - 1) / recentValues.length;
            growthRate = Math.max(-0.2, Math.min(0.3, growthRate));
            console.log('Calculated growth rate:', growthRate);
          }
        }
      }
      
      // If this is the top casket, use its growth rate for display
      if (item === topCasket) {
        overallGrowthRate = growthRate;
        console.log('Setting overall growth rate to:', overallGrowthRate);
        
        // Calculate seasonality impact
        if (actualItemData.length >= 4) {
          const values = actualItemData.map(d => d.casket_sold);
          const avg = values.reduce((sum, val) => sum + val, 0) / values.length;
          const variance = values.reduce((sum, val) => sum + Math.pow(val - avg, 2), 0) / values.length;
          const stdDev = Math.sqrt(variance);
          const variationCoeff = stdDev / avg;
          
          console.log('Seasonality calculation:', {
            values: values,
            average: avg,
            variance: variance,
            stdDev: stdDev,
            coeff: variationCoeff
          });
          
          if (variationCoeff < 0.1) seasonalityImpact = "Low";
          else if (variationCoeff < 0.25) seasonalityImpact = "Medium";
          else seasonalityImpact = "High";
          
          console.log('Seasonality impact:', seasonalityImpact);
        }
      }
      
      const seriesItem = {
        name: item,
        data: formattedData
      };
      
      console.log('Final series item:', seriesItem);
      console.groupEnd();
      return seriesItem;
    });
    console.groupEnd();
    
    const result = {
      items,
      months: allMonths,
      series,
      topCasket,
      growthRate: overallGrowthRate,
      seasonalityImpact
    };
    
    console.log('Final processed data:', result);
    console.groupEnd();
    
    return result;
}

// Create the heatmap chart with improved configuration
const rawCasketData = <?php echo json_encode($casketData); ?>;
const heatmapData = processDataForHeatmap(rawCasketData);

console.log('Raw PHP data:', <?php echo json_encode($casketData); ?>);
console.log('Raw casket data (JS):', rawCasketData);
console.log('Processed heatmap data:', heatmapData);

function printDemandPrediction() {
    // Get the metrics
    const topCasket = document.getElementById('topCasketValue').textContent;
    const growthRate = document.getElementById('growthRateValue').textContent;
    const seasonalityImpact = document.getElementById('seasonalityImpactValue').textContent;

    // Create transposed table data from the heatmap data
    let tableHTML = `
        <table border="1" cellpadding="5" cellspacing="0" style="width:100%; border-collapse:collapse; margin-top:20px;">
            <thead>
                <tr style="background-color:#f3f4f6;">
                    <th>Month</th>`;

    // Add product names as column headers
    heatmapData.series.forEach(product => {
        tableHTML += `<th>${product.name}</th>`;
    });

    tableHTML += `</tr></thead><tbody>`;

    // For each month, create a row with all product values
    heatmapData.months.forEach((month, monthIndex) => {
        tableHTML += `<tr><td style="font-weight:bold;">${month}</td>`;

        heatmapData.series.forEach(product => {
            const item = product.data[monthIndex];
            const value = item.y === 0 ? '-' : item.y;
            const forecastClass = item.forecast ? 'style="background-color:#f0f9ff; color:#0369a1;"' : '';
            tableHTML += `<td ${forecastClass}>${value}${item.forecast ? ' (F)' : ''}</td>`;
        });

        tableHTML += `</tr>`;
    });

    tableHTML += `</tbody></table>`;

    // Create print window content
    const printContent = `
        <!DOCTYPE html>
        <html>
            <head>
                <title>Demand Prediction Report</title>
                <style>
                    body { font-family: Arial, sans-serif; padding:20px; }
                    h1 { color: #333; border-bottom:1px solid #ddd; padding-bottom:10px; }
                    .metrics { display:flex; margin:20px 0; gap:15px; }
                    .metric-box { border:1px solid #eee; padding:15px; border-radius:5px; flex:1; }
                    .metric-title { font-weight:bold; margin-bottom:5px; color:#555; }
                    .metric-value { font-size:18px; font-weight:bold; }
                    table th { background-color:#f3f4f6; text-align:left; }
                    table { page-break-inside:auto; }
                    tr { page-break-inside:avoid; page-break-after:auto; }
                    .footer { margin-top:30px; font-size:12px; color:#777; }
                    .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; }
                    .header-info { display: flex; justify-content: space-between; font-size: 12px; }
                    .footer-info { text-align: center; margin-top: 20px; padding-top: 10px; border-top: 2px solid #333; font-size: 12px; }
                    @media print {
                        @page { size:auto; margin:10mm; }
                        body { -webkit-print-color-adjust:exact; }
                        table { width:100%; overflow-wrap:break-word; }
                        th, td { padding:4px; font-size:12px; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>VJay Relova Funeral Services</h1>
                    <div class="header-info">
                        <div>
                            <strong>Address:</strong> #6 J.P Rizal St. Brgy. Sta Clara Sur, (Pob) Pila, Laguna
                        </div>
                        <div>
                            <strong>Available 24/7</strong>
                        </div>
                    </div>
                    <div class="header-info">
                        <div>
                            <strong>Phone:</strong> (0956) 814-3000 / (0961) 345-4283
                        </div>
                        <div>
                            <strong>Email:</strong> GrievEase@gmail.com
                        </div>
                    </div>
                </div>

                <h2>Demand Prediction Report</h2>

                <div class="metrics">
                    <div class="metric-box">
                        <div class="metric-title">Top Casket</div>
                        <div class="metric-value">${topCasket}</div>
                        <p>Best-selling product based on historical data</p>
                    </div>
                    <div class="metric-box">
                        <div class="metric-title">Growth Rate</div>
                        <div class="metric-value">${growthRate}</div>
                        <p>Projected demand trend (positive = growth)</p>
                    </div>
                    <div class="metric-box">
                        <div class="metric-title">Seasonality Impact</div>
                        <div class="metric-value">${seasonalityImpact}</div>
                        <p>How much demand fluctuates seasonally</p>
                    </div>
                </div>

                <h3>Demand Data</h3>
                ${tableHTML}

                <div class="footer">
                    <div class="footer-info">
                        <div>#6 J.P Rizal St. Brgy. Sta Clara Sur, (Pob) Pila, Laguna</div>
                        <div>(0956) 814-3000 | (0961) 345-4283 | GrievEase@gmail.com</div>
                        <div>VJay Relova Funeral Services - Available 24/7</div>
                    </div>
                    <p>Report generated on ${new Date().toLocaleDateString()}</p>
                    <p>(F) indicates forecasted values</p>
                </div>

                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                            window.close();
                        }, 200);
                    };
                <\/script>
            </body>
        </html>`;

    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
}

window.printDemandPrediction = printDemandPrediction;

// Create the heatmap chart with enhanced design that matches sales forecast style
var options = {
  series: heatmapData.series,
  chart: {
    height: 380, // Increased height for better visibility
    type: 'heatmap',
    toolbar: {
      show: true,
      tools: {
        download: false,
        selection: false,
        zoom: false,
        zoomin: false,
        zoomout: false,
        pan: false,
        reset: false
      }
    },
    animations: {
      enabled: true,
      easing: 'easeinout',
      speed: 800,
      animateGradually: {
        enabled: true,
        delay: 150
      },
      dynamicAnimation: {
        enabled: true,
        speed: 350
      }
    },
    fontFamily: 'Inter, Helvetica, sans-serif',
    background: '#fff',
    foreColor: '#4B5563',
    dropShadow: {
      enabled: true,
      top: 3,
      left: 2,
      blur: 4,
      opacity: 0.1
    }
  },
  stroke: {
    width: 1,
    colors: ['#fff']
  },
  plotOptions: {
    heatmap: {
      radius: 0,
      enableShades: true,
      colorScale: {
        ranges: [
          { from: 0, to: 0, color: '#E5E7EB', name: 'No Data' },        // Slightly darker gray for better visibility
          { from: 1, to: 10, color: '#BFDBFE', name: 'Very Low (1-10)' }, // Brighter blue
          { from: 11, to: 20, color: '#60A5FA', name: 'Low (11-20)' },   // More vibrant light blue
          { from: 21, to: 30, color: '#3B82F6', name: 'Medium (21-30)' }, // Strong blue
          { from: 31, to: 40, color: '#2563EB', name: 'High (31-40)' },   // Darker rich blue
          { from: 41, to: 50, color: '#1D4ED8', name: 'Very High (41-50)' } // Deep, intense blue
        ],
      },
    }
  },
  dataLabels: {
    enabled: true,
    style: {
      colors: ['#fff'],
      fontSize: '12px',
      fontWeight: 600,
      fontFamily: 'Inter, Helvetica, sans-serif',
      textShadow: '0px 0px 2px rgba(0,0,0,0.5)'
    },
    formatter: function(val, opts) {
      if (val === 0) return ''; // Don't show zeros
      
      if (opts.seriesIndex >= 0 && opts.dataPointIndex >= 0) {
        const point = heatmapData.series[opts.seriesIndex].data[opts.dataPointIndex];
        if (point.forecast) {
          return val + ' (F)';
        }
      }
      return val;
    }
  },
  xaxis: {
    type: 'category',
    categories: heatmapData.months,
    labels: {
      formatter: function(value) {
        return value.split('-')[1] + '/' + value.split('-')[0].slice(2);
      },
      style: {
        fontSize: '12px',
        fontWeight: 500
      },
      rotateAlways: false
    },
    axisBorder: {
      show: true,
      color: '#E5E7EB',
    },
    axisTicks: {
      show: true,
      color: '#E5E7EB',
    },
    title: {
      text: 'Month',
      style: {
        fontSize: '14px',
        fontWeight: 600
      }
    }
  },
  yaxis: {
    labels: {
      style: {
        fontSize: '12px',
        fontWeight: 500
      }
    },
    title: {
      text: 'Product Type',
      style: {
        fontSize: '14px',
        fontWeight: 600
      }
    }
  },
  title: {
    text: 'Casket Demand Forecast',
    align: 'left',
    style: {
      fontSize: '16px',
      fontWeight: 600,
      color: '#111827'
    }
  },
  subtitle: {
    text: 'Historical sales and predicted future demand',
    align: 'left',
    style: {
      fontSize: '13px',
      color: '#6B7280'
    }
  },
  theme: {
    mode: 'light',
    palette: 'palette1',
    monochrome: {
      enabled: false
    }
  },
  legend: {
    show: true,
    position: 'bottom'
  },
  tooltip: {
    custom: function({ series, seriesIndex, dataPointIndex, w }) {
      const item = w.config.series[seriesIndex].name;
      const month = w.config.xaxis.categories[dataPointIndex];
      const value = series[seriesIndex][dataPointIndex];
      const isForecast = w.config.series[seriesIndex].data[dataPointIndex].forecast;
      
      // Parse the date for better formatting
      const [year, monthNum] = month.split('-');
      const date = new Date(parseInt(year), parseInt(monthNum) - 1);
      const formattedDate = date.toLocaleDateString('default', { month: 'long', year: 'numeric' });
      
      return `
        <div class="p-3 bg-white border border-gray-200 rounded-lg shadow-lg">
          <div class="text-lg font-bold text-gray-800 mb-1">${item}</div>
          <div class="grid grid-cols-2 gap-1 text-sm">
            <div class="text-gray-500">Month:</div>
            <div class="font-medium">${formattedDate}</div>
            <div class="text-gray-500">Units Sold:</div>
            <div class="font-medium">${value}</div>
          </div>
          ${isForecast ? '<div class="mt-2 py-1 px-2 bg-blue-100 text-blue-800 rounded text-xs font-medium">Forecasted Value</div>' : ''}
        </div>
      `;
    }
  }
};

// Render the chart
var chart = new ApexCharts(document.querySelector("#demandPredictionChart"), options);
console.log('Chart options:', options);
chart.render();

// Update the summary information boxes with calculated values
document.addEventListener('DOMContentLoaded', function() {
  // Update Top Casket
  const topCasketElement = document.getElementById('topCasketValue');
  if (topCasketElement && heatmapData.topCasket) {
    topCasketElement.textContent = heatmapData.topCasket;
  }
  
  // Update Growth Rate
  const growthRateElement = document.getElementById('growthRateValue');
  if (growthRateElement && heatmapData.growthRate !== undefined) {
    const formattedGrowthRate = (heatmapData.growthRate * 100).toFixed(1) + '%';
    growthRateElement.textContent = (heatmapData.growthRate >= 0 ? '+' : '') + formattedGrowthRate;
    
    // Style based on positive/negative growth
    if (heatmapData.growthRate >= 0) {
      growthRateElement.className = 'text-green-600 font-medium';
    } else {
      growthRateElement.className = 'text-red-600 font-medium';
    }
  }
  
  // Update Seasonality Impact
  const seasonalityElement = document.getElementById('seasonalityImpactValue');
  if (seasonalityElement && heatmapData.seasonalityImpact) {
    seasonalityElement.textContent = heatmapData.seasonalityImpact;
    
    // Style based on impact level
    if (heatmapData.seasonalityImpact === 'Low') {
      seasonalityElement.className = 'text-blue-600 font-medium';
    } else if (heatmapData.seasonalityImpact === 'Medium') {
      seasonalityElement.className = 'text-yellow-600 font-medium';  
    } else {
      seasonalityElement.className = 'text-red-600 font-medium';
    }
  }
});

// After processing the heatmap data, calculate total forecasted orders
const forecastedOrders = heatmapData.series.reduce((total, series) => {
  const forecastDataPoints = series.data.filter(point => point.forecast);
  const seriesForecast = forecastDataPoints.reduce((sum, point) => sum + point.y, 0);
  return total + seriesForecast;
}, 0);

console.log('Total forecasted orders:', forecastedOrders);

// Update the Projected Orders card
document.addEventListener('DOMContentLoaded', function() {
  const projectedOrdersElement = document.querySelector('.bg-gradient-to-r.from-purple-100.to-purple-200 .text-2xl');
  if (projectedOrdersElement) {
    projectedOrdersElement.textContent = forecastedOrders;
  }
  
  // You can also update the percentage change if you have historical data to compare
  const changeElement = document.querySelector('.bg-white.border-t.border-gray-100 .font-medium.text-xs');
  if (changeElement) {
    // Calculate percentage change (this is just an example - adjust based on your business logic)
    const historicalSales = heatmapData.series.reduce((total, series) => {
      const actualDataPoints = series.data.filter(point => !point.forecast);
      const seriesSales = actualDataPoints.reduce((sum, point) => sum + point.y, 0);
      return total + seriesSales;
    }, 0);
    
    const avgHistoricalQuarterlySales = historicalSales / (heatmapData.months.filter(m => !m.is_forecast).length / 3);
    const percentageChange = ((forecastedOrders - avgHistoricalQuarterlySales) / avgHistoricalQuarterlySales * 100).toFixed(1);
    
    changeElement.textContent = `${percentageChange}%`;
    
    // Set color based on positive/negative change
    if (parseFloat(percentageChange) >= 0) {
      changeElement.parentElement.className = 'flex items-center text-emerald-600';
    } else {
      changeElement.parentElement.className = 'flex items-center text-red-600';
    }
  }
});
</script>

<script>
const salesData = <?php echo json_encode($salesData); ?>;

document.addEventListener('DOMContentLoaded', function() {
  // Function to calculate simple forecast
  function calculateForecast(historicalData, monthsToForecast = 6) {
    // Filter out months with zero sales to get meaningful data for forecasting
    const nonZeroData = historicalData.filter(item => 
      parseFloat(item.monthly_revenue) > 0 || parseFloat(item.monthly_amount_paid) > 0
    );
    
    if (!nonZeroData || nonZeroData.length < 2) return [];
    
    // Get last few months of data for forecasting
    const recentData = nonZeroData.slice(-6);
    
    // Calculate average monthly growth rate
    let totalGrowthRateRevenue = 0;
    let totalGrowthRatePayment = 0;
    let countPairs = 0;
    
    for (let i = 1; i < recentData.length; i++) {
      const prevRevenue = parseFloat(recentData[i-1].monthly_revenue);
      const currRevenue = parseFloat(recentData[i].monthly_revenue);
      const prevPayment = parseFloat(recentData[i-1].monthly_amount_paid);
      const currPayment = parseFloat(recentData[i].monthly_amount_paid);
      
      if (prevRevenue > 0 && prevPayment > 0) {
        totalGrowthRateRevenue += (currRevenue / prevRevenue - 1);
        totalGrowthRatePayment += (currPayment / prevPayment - 1);
        countPairs++;
      }
    }
    
    // Average monthly growth rate (with safeguards)
    const avgGrowthRateRevenue = countPairs > 0 ? totalGrowthRateRevenue / countPairs : 0.05;
    const avgGrowthRatePayment = countPairs > 0 ? totalGrowthRatePayment / countPairs : 0.05;
    
    // Cap growth rates between -15% and +25%
    const cappedGrowthRateRevenue = Math.max(-0.15, Math.min(0.25, avgGrowthRateRevenue));
    const cappedGrowthRatePayment = Math.max(-0.15, Math.min(0.25, avgGrowthRatePayment));
    
    // Get the current date and set it to the first of the next month
    const currentDate = new Date();
    const forecastStartDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
    
    // Get the last data point's values
    const lastDataPoint = recentData[recentData.length - 1];
    const lastRevenue = parseFloat(lastDataPoint.monthly_revenue);
    const lastPayment = parseFloat(lastDataPoint.monthly_amount_paid);
    
    // Generate forecast data starting from next month
    const forecastData = [];
    
    for (let i = 0; i < monthsToForecast; i++) {
      const forecastDate = new Date(forecastStartDate);
      forecastDate.setMonth(forecastDate.getMonth() + i);
      
      // Calculate forecast values (starting with last actual values)
      const forecastRevenue = lastRevenue * Math.pow(1 + cappedGrowthRateRevenue, i + 1);
      const forecastPayment = lastPayment * Math.pow(1 + cappedGrowthRatePayment, i + 1);
      
      forecastData.push({
        month_start: forecastDate.toISOString().split('T')[0],
        monthly_revenue: forecastRevenue.toFixed(2),
        monthly_amount_paid: forecastPayment.toFixed(2),
        is_forecast: true
      });
    }
    
    return forecastData;
  }
  
  // Prepare data for chart
  if (salesData && salesData.length > 0) {
    const forecastData = calculateForecast(salesData, 6);
    const combinedData = [...salesData, ...forecastData];
    
    // Format dates for Chart.js
    const labels = combinedData.map(item => {
      const date = new Date(item.month_start);
      return date.toLocaleDateString('default', { month: 'short', year: 'numeric' });
    });
    
    // Prepare datasets
    const datasets = [
      {
        label: 'Accrued revenue',
        data: combinedData.map(item => parseFloat(item.monthly_revenue) || 0),
        borderColor: '#3B82F6', // Blue
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        borderWidth: 3,
        tension: 0.3,
        pointRadius: 5,
        pointHoverRadius: 7,
        borderDash: combinedData.map(item => item.is_forecast ? [5, 5] : [0, 0]),
        segment: {
          borderDash: ctx => ctx.p1.raw.is_forecast ? [5, 5] : undefined
        }
      },
      {
        label: 'Cash revenue',
        data: combinedData.map(item => parseFloat(item.monthly_amount_paid) || 0),
        borderColor: '#10B981', // Green
        backgroundColor: 'rgba(16, 185, 129, 0.1)',
        borderWidth: 3,
        tension: 0.3,
        pointRadius: 5,
        pointHoverRadius: 7,
        borderDash: combinedData.map(item => item.is_forecast ? [5, 5] : [0, 0]),
        segment: {
          borderDash: ctx => ctx.p1.raw.is_forecast ? [5, 5] : undefined
        }
      }
    ];
    
    // Create Chart.js config
    const config = {
      type: 'line',
      data: {
        labels: labels,
        datasets: datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'top',
            align: 'end'
          },
          tooltip: {
            mode: 'index',
            intersect: false,
            callbacks: {
              label: function(context) {
                let label = context.dataset.label || '';
                if (label) {
                  label += ': ';
                }
                if (context.parsed.y !== null) {
                  label += '₱' + context.parsed.y.toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                  });
                }
                
                // Add forecast indicator if this is a forecast point
                const dataIndex = context.dataIndex;
                if (dataIndex >= salesData.length) {
                  label += ' (forecast)';
                }
                
                return label;
              }
            }
          }
        },
        scales: {
          x: {
            title: {
              display: true,
              text: 'Month',
              color: '#64748b',
              font: {
                size: 14,
                weight: 'bold'
              }
            },
            grid: {
              color: '#f1f1f1',
              drawOnChartArea: true
            }
          },
          y: {
            beginAtZero: true,
            title: {
              display: true,
              text: 'Amount (₱)',
              color: '#64748b',
              font: {
                size: 14,
                weight: 'bold'
              }
            },
            grid: {
              color: '#f1f1f1',
              drawOnChartArea: true
            },
            ticks: {
              callback: function(value) {
                return '₱' + value.toLocaleString('en-US', {
                  minimumFractionDigits: 0,
                  maximumFractionDigits: 0
                });
              }
            }
          }
        },
        interaction: {
          intersect: false,
          mode: 'index'
        }
      }
    };
    
    // Render the chart
    const ctx = document.getElementById('salesSplineChart').getContext('2d');
    new Chart(ctx, config);
    
    // Add vertical line at the end of historical data
    const addVerticalLine = () => {
      const chartCanvas = document.getElementById('salesSplineChart');
      const chartInstance = Chart.getChart(chartCanvas);
      
      if (chartInstance) {
        const xAxis = chartInstance.scales.x;
        const yAxis = chartInstance.scales.y;
        
        // Get the x-coordinate of the last historical data point
        const lastHistoricalIndex = salesData.length - 1;
        const xPos = xAxis.getPixelForValue(labels[lastHistoricalIndex]);
        
        // Draw the line
        chartInstance.ctx.save();
        chartInstance.ctx.beginPath();
        chartInstance.ctx.moveTo(xPos, yAxis.top);
        chartInstance.ctx.lineTo(xPos, yAxis.bottom);
        chartInstance.ctx.lineWidth = 1;
        chartInstance.ctx.strokeStyle = '#775DD0';
        chartInstance.ctx.stroke();
        chartInstance.ctx.restore();
        
        // Add label
        chartInstance.ctx.save();
        chartInstance.ctx.fillStyle = '#775DD0';
        chartInstance.ctx.fillRect(xPos - 30, yAxis.bottom - 20, 60, 20);
        chartInstance.ctx.fillStyle = '#fff';
        chartInstance.ctx.textAlign = 'center';
        chartInstance.ctx.fillText('Current', xPos, yAxis.bottom - 5);
        chartInstance.ctx.restore();
      }
    };
    
    // Wait for chart to render then add the line
    setTimeout(addVerticalLine, 500);
    
    // Populate the printable table
    const tableBody = document.getElementById('tableBody');
    combinedData.forEach(item => {
      const row = document.createElement('tr');
      const date = new Date(item.month_start);
      const monthYear = date.toLocaleDateString('default', { month: 'short', year: 'numeric' });
      
      row.innerHTML = `
        <td class="border p-2">${monthYear}</td>
        <td class="border p-2 text-right">${parseFloat(item.monthly_revenue).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
        <td class="border p-2 text-right">${parseFloat(item.monthly_amount_paid).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
        <td class="border p-2 text-center">${item.is_forecast ? 'Forecast' : 'Actual'}</td>
      `;
      tableBody.appendChild(row);
    });
    
  } else {
    document.querySelector("#salesSplineChart").innerHTML = '<div class="p-4 text-center text-gray-500">No sales data available</div>';
    document.getElementById('tableBody').innerHTML = '<tr><td colspan="4" class="border p-2 text-center">No sales data available</td></tr>';
  }
  
  // Add print-specific styles
  const printStyle = document.createElement('style');
  printStyle.innerHTML = `
      @media print {
          body * {
              visibility: hidden;
              margin: 0;
              padding: 0;
          }
          #printableTable, #printableTable * {
              visibility: visible;
          }
          #printableTable {
              position: absolute;
              left: 0;
              top: 0;
              width: 100%;
              padding: 10px;
              box-sizing: border-box;
          }
          #printableTable table {
              page-break-inside: avoid;
          }
          @page {
              size: auto;
              margin: 10mm;
          }
      }
  `;
  document.head.appendChild(printStyle);

  // Modify your existing JavaScript to ensure proper printing
  document.getElementById('printButton').addEventListener('click', function() {
      // Set the current date
      document.getElementById('printDate').textContent = new Date().toLocaleDateString();
      
      // Show the printable table
      const printableTable = document.getElementById('printableTable');
      printableTable.classList.remove('hidden');
      
      // Adjust the table content if needed to fit one page
      const tableBody = document.getElementById('tableBody');
      const rows = tableBody.querySelectorAll('tr');
      if (rows.length > 20) { // If too many rows, reduce font size
          tableBody.style.fontSize = '10px';
          const allCells = printableTable.querySelectorAll('td, th');
          allCells.forEach(cell => {
              cell.style.padding = '4px';
          });
      }
      
      // Print the window
      window.print();
      
      // Hide the table again after printing
      setTimeout(() => {
          printableTable.classList.add('hidden');
          // Reset any style changes
          tableBody.style.fontSize = '';
          const allCells = printableTable.querySelectorAll('td, th');
          allCells.forEach(cell => {
              cell.style.padding = '8px';
          });
      }, 500);
  });
});
</script>
<script>
  // Update the branch selection script
  document.addEventListener('DOMContentLoaded', function() {
      const dropdownButton = document.getElementById('branchDropdownButton');
      const dropdownMenu = document.getElementById('branchDropdown');
      const branchOptions = document.querySelectorAll('.branch-option');
      
      // Get current branch from URL
      const urlParams = new URLSearchParams(window.location.search);
      const currentBranch = urlParams.get('branch') || 'all';
      
      // Set initial button text
      const initialBranchText = currentBranch === 'all' ? 'All Branches' : 
                              (currentBranch === 'paete' ? 'Paete' : 'Pila');
      dropdownButton.querySelector('span').textContent = initialBranchText;
      
      // Toggle dropdown
      dropdownButton.addEventListener('click', function(e) {
          e.stopPropagation();
          dropdownMenu.classList.toggle('hidden');
          const icon = this.querySelector('i');
          icon.classList.toggle('fa-chevron-down');
          icon.classList.toggle('fa-chevron-up');
      });
      
      // Close dropdown when clicking outside
      document.addEventListener('click', function() {
          dropdownMenu.classList.add('hidden');
          const icon = dropdownButton.querySelector('i');
          icon.classList.remove('fa-chevron-up');
          icon.classList.add('fa-chevron-down');
      });
      
      // Handle branch selection
      branchOptions.forEach(option => {
          option.addEventListener('click', function(e) {
              e.preventDefault();
              const branch = this.getAttribute('data-branch');
              const branchName = this.textContent;
              
              // Update button text
              dropdownButton.querySelector('span').textContent = branchName;
              
              // Close dropdown
              dropdownMenu.classList.add('hidden');
              const icon = dropdownButton.querySelector('i');
              icon.classList.remove('fa-chevron-up');
              icon.classList.add('fa-chevron-down');
              
              // Reload page with new branch filter
              const url = new URL(window.location.href);
              if (branch === 'all') {
                  url.searchParams.delete('branch');
              } else {
                  url.searchParams.set('branch', branch);
              }
              window.location.href = url.toString();
          });
      });
  });
</script>

    <script src="tailwind.js"></script>
</body>
</html>