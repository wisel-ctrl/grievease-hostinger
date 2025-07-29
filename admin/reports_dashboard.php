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

// Fetch monthly revenue data for forecasting
$revenueQuery = "WITH RECURSIVE date_series AS (
    SELECT 
        DATE_FORMAT(CONVERT_TZ(MIN(sale_date), '+00:00', '+08:00'), '%Y-%m-01') as month_start
    FROM 
        analytics_tb
    
    UNION ALL
    
    SELECT 
        DATE_ADD(month_start, INTERVAL 1 MONTH)
    FROM 
        date_series
    WHERE 
        DATE_ADD(month_start, INTERVAL 1 MONTH) <= DATE_FORMAT(CONVERT_TZ(CURRENT_DATE(), '+00:00', '+08:00'), '%Y-%m-01')
)

SELECT 
    DATE_FORMAT(ds.month_start, '%Y-%m-%d') as month_start,
    COALESCE(SUM(at.discounted_price), 0) as monthly_revenue
FROM 
    date_series ds
LEFT JOIN 
    analytics_tb at ON DATE_FORMAT(CONVERT_TZ(at.sale_date, '+00:00', '+08:00'), '%Y-%m-01') = ds.month_start
GROUP BY 
    ds.month_start
ORDER BY 
    ds.month_start;";
$revenueStmt = $conn->prepare($revenueQuery);
$revenueStmt->execute();
$revenueResult = $revenueStmt->get_result();
$revenueData = [];
while ($row = $revenueResult->fetch_assoc()) {
    $revenueData[] = $row;
}  

$casketQuery = "
WITH RECURSIVE all_months AS (
    SELECT DATE_FORMAT(MIN(sale_date), '%Y-%m-01') AS month
    FROM analytics_tb
    UNION ALL
    SELECT DATE_ADD(month, INTERVAL 1 MONTH)
    FROM all_months
    WHERE month < DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 6 MONTH), '%Y-%m-01')
),
casket_sales AS (
    SELECT 
        DATE_FORMAT(sale_date, '%Y-%m') AS sale_month,
        item_name,
        COUNT(*) AS casket_sold
    FROM 
        analytics_tb
    JOIN 
        inventory_tb 
    ON 
        casket_id = inventory_id
    WHERE 
        casket_id IS NOT NULL
        AND inventory_tb.category_id = 1
    GROUP BY 
        DATE_FORMAT(sale_date, '%Y-%m'), item_name
)
SELECT 
    DATE_FORMAT(am.month, '%Y-%m') AS sale_month,
    it.item_name,
    COALESCE(cs.casket_sold, 0) AS casket_sold,
    CASE WHEN am.month > DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN 1 ELSE 0 END AS is_forecast
FROM 
    all_months am
CROSS JOIN 
    (SELECT DISTINCT item_name FROM inventory_tb WHERE category_id = 1) it
LEFT JOIN 
    casket_sales cs 
ON 
    DATE_FORMAT(am.month, '%Y-%m') = cs.sale_month AND it.item_name = cs.item_name
ORDER BY 
    sale_month, item_name
";

$casketStmt = $conn->prepare($casketQuery);
$casketStmt->execute();
$casketResult = $casketStmt->get_result();

$casketData = [];
while ($row = $casketResult->fetch_assoc()) {
    $casketData[] = [
        'sale_month' => $row['sale_month'],
        'item_name' => $row['item_name'],
        'casket_sold' => (int)$row['casket_sold'],
        'is_forecast' => (bool)$row['is_forecast']
    ];
}

$salesQuery = "WITH RECURSIVE months AS (
    SELECT DATE_FORMAT(MIN(sale_date), '%Y-%m-01') AS month_start
    FROM analytics_tb
    UNION ALL
    SELECT DATE_ADD(month_start, INTERVAL 1 MONTH)
    FROM months
    WHERE month_start < DATE_FORMAT(CURDATE(), '%Y-%m-01')
)

SELECT 
    m.month_start,
    COALESCE(SUM(a.discounted_price), 0) AS monthly_revenue,
    COALESCE(SUM(a.amount_paid), 0) AS monthly_amount_paid
FROM 
    months m
LEFT JOIN 
    analytics_tb a ON DATE_FORMAT(a.sale_date, '%Y-%m-01') = m.month_start
GROUP BY 
    m.month_start
ORDER BY 
    m.month_start;";

$salesStmt = $conn->prepare($salesQuery);
$salesStmt->execute();
$salesResult = $salesStmt->get_result();
$salesData = [];

while ($row = $salesResult->fetch_assoc()) {
    $salesData[] = $row;
}


$basicStatsQuery = "SELECT 
    AVG(discounted_price) as avg_price,
    AVG(amount_paid) as avg_payment,
    (SUM(amount_paid) / SUM(discounted_price)) * 100 as payment_ratio
FROM analytics_tb";
$basicStatsStmt = $conn->prepare($basicStatsQuery);
$basicStatsStmt->execute();
$basicStats = $basicStatsStmt->get_result()->fetch_assoc();

// Get last month's date from data
$lastDateQuery = "SELECT MAX(sale_date) as max_date FROM analytics_tb";
$lastDateStmt = $conn->prepare($lastDateQuery);
$lastDateStmt->execute();
$lastDate = $lastDateStmt->get_result()->fetch_assoc()['max_date'];

// Calculate changes
$changesQuery = "SELECT 
    CASE 
        WHEN (SELECT AVG(discounted_price) FROM analytics_tb WHERE sale_date < DATE_SUB(?, INTERVAL 1 MONTH)) > 0 
        THEN (SELECT AVG(discounted_price) FROM analytics_tb WHERE sale_date >= DATE_SUB(?, INTERVAL 1 MONTH)) / 
             (SELECT AVG(discounted_price) FROM analytics_tb WHERE sale_date < DATE_SUB(?, INTERVAL 1 MONTH)) * 100 - 100 
        ELSE 0 
    END as price_change,
    
    CASE 
        WHEN (SELECT AVG(amount_paid) FROM analytics_tb WHERE sale_date < DATE_SUB(?, INTERVAL 1 MONTH)) > 0 
        THEN (SELECT AVG(amount_paid) FROM analytics_tb WHERE sale_date >= DATE_SUB(?, INTERVAL 1 MONTH)) / 
             (SELECT AVG(amount_paid) FROM analytics_tb WHERE sale_date < DATE_SUB(?, INTERVAL 1 MONTH)) * 100 - 100 
        ELSE 0 
    END as payment_change,
    
    CASE 
        WHEN (SELECT SUM(discounted_price) FROM analytics_tb WHERE sale_date < DATE_SUB(?, INTERVAL 1 MONTH)) > 0 
             AND (SELECT SUM(amount_paid) FROM analytics_tb WHERE sale_date < DATE_SUB(?, INTERVAL 1 MONTH)) > 0 
        THEN (SELECT (SUM(amount_paid)/SUM(discounted_price)*100) FROM analytics_tb WHERE sale_date >= DATE_SUB(?, INTERVAL 1 MONTH)) / 
             (SELECT (SUM(amount_paid)/SUM(discounted_price)*100) FROM analytics_tb WHERE sale_date < DATE_SUB(?, INTERVAL 1 MONTH)) * 100 - 100 
        ELSE 0 
    END as ratio_change";

$changesStmt = $conn->prepare($changesQuery);
$changesStmt->bind_param("ssssssssss", $lastDate, $lastDate, $lastDate, $lastDate, $lastDate, $lastDate, $lastDate, $lastDate, $lastDate, $lastDate);
$changesStmt->execute();
$changes = $changesStmt->get_result()->fetch_assoc();

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
      <button class="p-2 bg-white border border-sidebar-border rounded-lg shadow-input text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-bell"></i>
      </button>
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
                <span class="text-2xl md:text-3xl font-bold text-gray-800 sales-forecast-value">$142,850</span>
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
              <div class="w-10 h-10 rounded-full bg-white/90 text-blue-600 flex items-center justify-center">
                  <i class="fas fa-chart-line"></i>
              </div>
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
        <div class="w-10 h-10 rounded-full bg-white/90 text-purple-600 flex items-center justify-center">
          <i class="fas fa-box-open"></i>
        </div>
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

  // Only proceed if we have historical data
  // Only proceed if we have historical data
  if (historicalRevenueData && historicalRevenueData.length > 0) {
      const regressionResults = calculateLinearRegressionForecast(historicalRevenueData, 6);
      
      // Calculate forecast accuracy metrics
      const lastActual = regressionResults.historicalChartData[regressionResults.historicalChartData.length - 1].y;
      const firstForecast = regressionResults.forecastData[0].y;
      const forecastAccuracy = 100 - Math.abs((firstForecast - lastActual) / lastActual * 100);
      
      
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
                  enabled: false // More precise rendering
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
// Create the heatmap chart with improved configuration
// Create the heatmap chart with improved configuration
const rawCasketData = <?php echo json_encode($casketData); ?>;
const heatmapData = processDataForHeatmap(rawCasketData);

console.log('Raw PHP data:', <?php echo json_encode($casketData); ?>);
console.log('Raw casket data (JS):', rawCasketData);
console.log('Processed heatmap data:', heatmapData);

// Create the heatmap chart with enhanced design that matches sales forecast style
var options = {
  series: heatmapData.series,
  chart: {
    height: 380, // Increased height for better visibility
    type: 'heatmap',
    toolbar: {
      show: true,
      tools: {
        download: true,
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
</script>

<script>
const salesData = <?php echo json_encode($salesData); ?>;

document.addEventListener('DOMContentLoaded', function() {
  // Function to calculate simple forecast (same as before)
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
    
    // Get the last data point's date and values
    const lastDataPoint = recentData[recentData.length - 1];
    const lastDate = new Date(lastDataPoint.month_start);
    const lastRevenue = parseFloat(lastDataPoint.monthly_revenue);
    const lastPayment = parseFloat(lastDataPoint.monthly_amount_paid);
    
    // Generate forecast data
    const forecastData = [];
    
    for (let i = 1; i <= monthsToForecast; i++) {
      const forecastDate = new Date(lastDate);
      forecastDate.setMonth(forecastDate.getMonth() + i);
      
      const forecastRevenue = lastRevenue * Math.pow(1 + cappedGrowthRateRevenue, i);
      const forecastPayment = lastPayment * Math.pow(1 + cappedGrowthRatePayment, i);
      
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
    
  } else {
    document.querySelector("#salesSplineChart").innerHTML = '<div class="p-4 text-center text-gray-500">No sales data available</div>';
  }
});
</script>

    <script src="tailwind.js"></script>
</body>
</html>