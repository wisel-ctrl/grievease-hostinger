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

// Fetch monthly revenue data for forecasting
$revenueQuery = "SELECT 
    DATE_FORMAT(sale_date, '%Y-%m-01') as month_start,
    SUM(discounted_price) as monthly_revenue
FROM 
    analytics_tb
GROUP BY 
    DATE_FORMAT(sale_date, '%Y-%m-01')
ORDER BY 
    month_start";
$revenueStmt = $conn->prepare($revenueQuery);
$revenueStmt->execute();
$revenueResult = $revenueStmt->get_result();
$revenueData = [];
while ($row = $revenueResult->fetch_assoc()) {
    $revenueData[] = $row;
}  

$casketQuery = "SELECT 
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
GROUP BY 
    DATE_FORMAT(sale_date, '%Y-%m'), item_name
ORDER BY 
    sale_month, item_name";

$casketStmt = $conn->prepare($casketQuery);
$casketStmt->execute();
$casketResult = $casketStmt->get_result();

$casketData = [];
while ($row = $casketResult->fetch_assoc()) {
    $casketData[] = [
        'sale_month' => $row['sale_month'],
        'item_name' => $row['item_name'],
        'casket_sold' => (int)$row['casket_sold']
    ];
}

$salesQuery = "SELECT 
    DATE_FORMAT(sale_date, '%Y-%m-01') AS month_start, 
    SUM(discounted_price) AS monthly_revenue, 
    SUM(amount_paid) AS monthly_amount_paid 
FROM 
    analytics_tb 
GROUP BY 
    DATE_FORMAT(sale_date, '%Y-%m-01') 
ORDER BY 
    month_start";

$salesStmt = $conn->prepare($salesQuery);
$salesStmt->execute();
$salesResult = $salesStmt->get_result();
$salesData = [];

while ($row = $salesResult->fetch_assoc()) {
    $salesData[] = $row;
}

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
                <h3 class="text-sm font-medium text-gray-700">Sales Forecast (Q2)</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-blue-600 flex items-center justify-center">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-2xl md:text-3xl font-bold text-gray-800">$142,850</span>
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
                <span class="text-2xl md:text-3xl font-bold text-gray-800">92.4%</span>
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
          <div class="flex items-end">
              <span class="text-2xl md:text-3xl font-bold text-gray-800 sales-forecast-value">$0</span>
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

<!-- Customer Payment Behavior -->
<div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
  <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
    <h3 class="font-medium text-sidebar-text">Sales & Payment Trends</h3>
    <div class="flex space-x-2">
      <select class="px-3 py-2 border border-sidebar-border rounded-md text-sm text-sidebar-text bg-white">
        <option>Last 6 Months</option>
        <option>Last Year</option>
        <option>Last Quarter</option>
      </select>
    </div>
  </div>
  <div class="p-5">
    <div id="salesSplineChart" class="h-96"></div>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-5 px-5 pb-5">
    <div class="bg-gray-50 p-4 rounded-lg">
      <div class="text-sm font-medium text-gray-600 mb-2">Average Price</div>
      <div class="text-2xl font-bold text-sidebar-text">$142.30 <span class="text-green-600 text-sm font-normal">+2.1%</span></div>
    </div>
    <div class="bg-gray-50 p-4 rounded-lg">
      <div class="text-sm font-medium text-gray-600 mb-2">Average Payment</div>
      <div class="text-2xl font-bold text-sidebar-text">$128.70 <span class="text-green-600 text-sm font-normal">+1.8%</span></div>
    </div>
    <div class="bg-gray-50 p-4 rounded-lg">
      <div class="text-sm font-medium text-gray-600 mb-2">Payment Ratio</div>
      <div class="text-2xl font-bold text-sidebar-text">90.4% <span class="text-green-600 text-sm font-normal">+0.8%</span></div>
    </div>
  </div>
</div>

<!-- JavaScript for Charts -->
<script>
    // Pass PHP data to JavaScript
    const historicalRevenueData = <?php echo json_encode($revenueData); ?>;
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
          x: index,
          y: parseFloat(item.monthly_revenue),
          date: new Date(item.month_start)
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
      const lastHistoricalDate = new Date(historicalData[historicalData.length - 1].month_start);
      
      for (let i = 1; i <= forecastMonths; i++) {
          const forecastDate = new Date(lastHistoricalDate);
          forecastDate.setMonth(forecastDate.getMonth() + i);
          
          const xValue = dataPoints.length + i - 1;
          const predictedY = slope * xValue + intercept;
          
          forecastData.push({
              x: forecastDate.getTime(),
              y: Math.max(0, predictedY) // Ensure revenue doesn't go negative
          });
      }
      
      return {
          slope,
          intercept,
          forecastData,
          historicalChartData: dataPoints.map(point => ({
              x: point.date.getTime(),
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
                      return new Date(value).toLocaleDateString('default', { month: 'short', year: 'numeric' });
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
                  format: 'MMM yyyy'
              },
              y: {
                  formatter: function(value) {
                      return '$' + value.toLocaleString();
                  }
              }
          }
      };

      var chart = new ApexCharts(document.querySelector("#salesForecastChart"), options);
      chart.render();
      
      // Update forecast summary
      const totalForecast = regressionResults.forecastData.reduce((sum, point) => sum + point.y, 0);
      document.querySelector('.sales-forecast-value').textContent = '$' + Math.round(totalForecast).toLocaleString();
  } else {
      document.querySelector("#salesForecastChart").innerHTML = '<div class="p-4 text-center text-gray-500">No revenue data available</div>';
  }


  

  
  
});
</script>

<script>
  function processDataForHeatmap(data, forecastMonths = 6) {
    // Get unique item names
    const items = [...new Set(data.map(d => d.item_name))];
    
    // Get unique months (sorted)
    const months = [...new Set(data.map(d => d.sale_month))].sort();
    
    // Generate forecast months
    const lastDate = new Date(months[months.length-1] + '-01');
    const forecastDates = [];
    for (let i = 1; i <= forecastMonths; i++) {
      lastDate.setMonth(lastDate.getMonth() + 1);
      const year = lastDate.getFullYear();
      const month = String(lastDate.getMonth() + 1).padStart(2, '0');
      forecastDates.push(`${year}-${month}`);
    }
    
    // Combine actual and forecast months
    const allMonths = [...months, ...forecastDates];
    
    // Calculate total sales per item to find top casket
    const itemTotals = {};
    items.forEach(item => {
      itemTotals[item] = data
        .filter(d => d.item_name === item)
        .reduce((sum, d) => sum + d.casket_sold, 0);
    });
    
    // Find the top casket (highest total sales)
    const topCasket = Object.keys(itemTotals).reduce((a, b) => 
      itemTotals[a] > itemTotals[b] ? a : b
    );
    
    // Calculate overall growth rate
    let overallGrowthRate = 0;
    let seasonalityImpact = "Low";
    
    // Create series for each item
    const series = items.map(item => {
      const itemData = data.filter(d => d.item_name === item);
      
      // Create data points for actual months
      const actualData = months.map(month => {
        const found = itemData.find(d => d.sale_month === month);
        return {
          x: month,
          y: found ? found.casket_sold : 0
        };
      });
      
      // Calculate growth rate for this item based on historical data
      let growthRate = 0.05; // Default 5%
      
      if (actualData.length >= 2) {
        // Look at last few months to calculate growth rate
        const recentValues = actualData.slice(-3).map(d => d.y).filter(y => y > 0);
        if (recentValues.length >= 2) {
          const oldest = recentValues[0];
          const newest = recentValues[recentValues.length - 1];
          if (oldest > 0) {
            growthRate = (newest / oldest - 1) / recentValues.length;
            // Cap growth rate between -20% and +30%
            growthRate = Math.max(-0.2, Math.min(0.3, growthRate));
          }
        }
      }
      
      // If this is the top casket, use its growth rate for display
      if (item === topCasket) {
        overallGrowthRate = growthRate;
        
        // Calculate seasonality impact by looking at variance
        if (actualData.length >= 4) {
          const values = actualData.map(d => d.y);
          const avg = values.reduce((sum, val) => sum + val, 0) / values.length;
          const variance = values.reduce((sum, val) => sum + Math.pow(val - avg, 2), 0) / values.length;
          const stdDev = Math.sqrt(variance);
          const variationCoeff = stdDev / avg;
          
          if (variationCoeff < 0.1) seasonalityImpact = "Low";
          else if (variationCoeff < 0.25) seasonalityImpact = "Medium";
          else seasonalityImpact = "High";
        }
      }
      
      // Create forecast data points with calculated growth rate
      const lastActual = actualData[actualData.length - 1].y;
      const forecastData = forecastDates.map((month, i) => {
        const forecastValue = Math.round(lastActual * Math.pow(1 + growthRate, i + 1));
        return {
          x: month,
          y: forecastValue,
          forecast: true
        };
      });
      
      return {
        name: item,
        data: [...actualData, ...forecastData]
      };
    });
    
    return {
      items,
      months: allMonths,
      series,
      topCasket,
      growthRate: overallGrowthRate,
      seasonalityImpact
    };
  }

// Create the heatmap chart with improved configuration
// Create the heatmap chart with improved configuration
// Create the heatmap chart with improved configuration
const rawCasketData = <?php echo json_encode($casketData); ?>;
const heatmapData = processDataForHeatmap(rawCasketData);

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
  // Function to calculate simple forecast based on historical data
  function calculateForecast(historicalData, monthsToForecast = 6) {
    if (!historicalData || historicalData.length < 2) return [];
    
    // Get last few months of data for forecasting
    const recentData = historicalData.slice(-6);
    
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
    const avgGrowthRateRevenue = countPairs > 0 ? totalGrowthRateRevenue / countPairs : 0.05; // Default 5% if no data
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
    // Calculate forecast for the next 6 months
    const forecastData = calculateForecast(salesData, 6);
    
    // Combine historical and forecast data
    const combinedData = [...salesData, ...forecastData];
    
    // Prepare series data
    const revenueData = combinedData.map(item => ({
      x: new Date(item.month_start).getTime(),
      y: parseFloat(item.monthly_revenue),
      isForecast: item.is_forecast || false
    }));
    
    const paymentsData = combinedData.map(item => ({
      x: new Date(item.month_start).getTime(),
      y: parseFloat(item.monthly_amount_paid),
      isForecast: item.is_forecast || false
    }));
    
    // Create ApexCharts options
    const options = {
      series: [
        {
          name: 'Total Price (Discounted)',
          data: revenueData,
          color: '#3B82F6', // Blue
          stroke: {
            width: 3,
            curve: 'smooth',
            lineCap: 'round'
          }
        },
        {
          name: 'Amount Paid',
          data: paymentsData,
          color: '#10B981', // Green
          stroke: {
            width: 3,
            curve: 'smooth',
            lineCap: 'round'
          }
        }
      ],
      chart: {
        height: 380,
        type: 'area',
        fontFamily: 'Inter, Helvetica, sans-serif',
        toolbar: {
          show: true
        },
        zoom: {
          enabled: false
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
        }
      },
      dataLabels: {
        enabled: false
      },
      stroke: {
        curve: 'smooth',
        width: 3
      },
      fill: {
        type: 'gradient',
        gradient: {
          shadeIntensity: 1,
          opacityFrom: 0.4,
          opacityTo: 0.1,
          stops: [0, 95, 100]
        }
      },
      grid: {
        borderColor: '#f1f1f1',
        strokeDashArray: 4,
        xaxis: {
          lines: {
            show: true
          }
        }
      },
      xaxis: {
        type: 'datetime',
        labels: {
          formatter: function(value) {
            return new Date(value).toLocaleDateString('default', { month: 'short', year: 'numeric' });
          }
        },
        title: {
          text: 'Month',
          style: {
            fontSize: '14px',
            fontWeight: 600,
            color: '#64748b'
          }
        }
      },
      yaxis: {
        title: {
          text: 'Amount ($)',
          style: {
            fontSize: '14px',
            fontWeight: 600,
            color: '#64748b'
          }
        },
        labels: {
          formatter: function(value) {
            return '$' + value.toLocaleString('en-US', {
              minimumFractionDigits: 0,
              maximumFractionDigits: 0
            });
          }
        }
      },
      tooltip: {
        shared: true,
        x: {
          format: 'MMM yyyy'
        },
        y: {
          formatter: function(value) {
            return '$' + value.toLocaleString('en-US', {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2
            });
          }
        },
        custom: function({ seriesIndex, dataPointIndex, w }) {
          const point = w.config.series[seriesIndex].data[dataPointIndex];
          const isForecast = point.isForecast;
          
          if (isForecast) {
            return `<div class="forecast-tooltip">
                      <div class="forecast-badge">Forecast</div>
                      <div class="tooltip-content">
                        ${w.globals.seriesNames[seriesIndex]}: $${point.y.toLocaleString('en-US', {
                          minimumFractionDigits: 2,
                          maximumFractionDigits: 2
                        })}
                      </div>
                    </div>`;
          }
          
          return undefined; // Use default tooltip
        }
      },
      markers: {
        size: 4,
        strokeWidth: 2,
        hover: {
          size: 7
        }
      },
      legend: {
        position: 'top',
        horizontalAlign: 'right',
        floating: true,
        offsetY: -25,
        offsetX: -5
      },
      annotations: {
        xaxis: [{
          x: salesData[salesData.length - 1].month_start,
          strokeDashArray: 0,
          borderColor: '#775DD0',
          label: {
            borderColor: '#775DD0',
            style: {
              color: '#fff',
              background: '#775DD0'
            },
            text: 'Current'
          }
        }]
      }
    };
    
    // Render the chart
    const chart = new ApexCharts(document.querySelector("#salesSplineChart"), options);
    chart.render();
    
    // Add custom styles for forecast tooltip
    const style = document.createElement('style');
    style.innerHTML = `
      .forecast-tooltip {
        padding: 10px;
        background: #fff;
        border-radius: 5px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        border: 1px solid #eee;
      }
      .forecast-badge {
        background: #775DD0;
        color: white;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 11px;
        margin-bottom: 5px;
        display: inline-block;
      }
      .tooltip-content {
        font-size: 13px;
        font-weight: 500;
      }
    `;
    document.head.appendChild(style);
  } else {
    document.querySelector("#salesSplineChart").innerHTML = '<div class="p-4 text-center text-gray-500">No sales data available</div>';
  }
});
</script>

    <script src="script.js"></script>
    <script src="tailwind.js"></script>
</body>
</html>