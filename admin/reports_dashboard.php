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
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
  <!-- Sales Forecasting -->
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
  <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300">
    <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
      <h3 class="font-medium text-sidebar-text">Demand Prediction</h3>
      
    </div>
    <div class="p-5">
      <div id="demandPredictionChart" class="h-64"></div>
    </div>
    <div class="px-5 pb-5">
      <div class="flex justify-between text-sm text-gray-600">
        <div>
          <div class="font-medium">Top Casket</div>
          <div>Premium Casket</div>
        </div>
        <div>
          <div class="font-medium">Growth Rate</div>
          <div class="text-green-600">+14.2%</div>
        </div>
        <div>
          <div class="font-medium">Seasonality Impact</div>
          <div>Medium</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Customer Payment Behavior -->
<div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border hover:shadow-card transition-all duration-300 mb-8">
  <div class="flex justify-between items-center p-5 border-b border-sidebar-border">
    <h3 class="font-medium text-sidebar-text">Customer Payment Behavior</h3>
    <div class="flex space-x-2">
      <select class="px-3 py-2 border border-sidebar-border rounded-md text-sm text-sidebar-text bg-white">
        <option>Last 6 Months</option>
        <option>Last Year</option>
        <option>Last Quarter</option>
      </select>
      <button class="px-3 py-2 border border-sidebar-border rounded-md text-sm flex items-center text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
        <i class="fas fa-download mr-2"></i> Export
      </button>
    </div>
  </div>
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 p-5">
    <div>
      <canvas id="paymentTimelineChart" class="h-64"></canvas>
    </div>
    <div>
      <canvas id="paymentMethodsChart" class="h-64"></canvas>
    </div>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-3 gap-5 px-5 pb-5">
    <div class="bg-gray-50 p-4 rounded-lg">
      <div class="text-sm font-medium text-gray-600 mb-2">Average Days to Payment</div>
      <div class="text-2xl font-bold text-sidebar-text">14.3 <span class="text-green-600 text-sm font-normal">-2.1 days</span></div>
    </div>
    <div class="bg-gray-50 p-4 rounded-lg">
      <div class="text-sm font-medium text-gray-600 mb-2">Payment Completion Rate</div>
      <div class="text-2xl font-bold text-sidebar-text">92.4% <span class="text-green-600 text-sm font-normal">+3.2%</span></div>
    </div>
    <div class="bg-gray-50 p-4 rounded-lg">
      <div class="text-sm font-medium text-gray-600 mb-2">Overdue Payments</div>
      <div class="text-2xl font-bold text-sidebar-text">7.6% <span class="text-green-600 text-sm font-normal">-3.2%</span></div>
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

  // Demand Prediction Chart
   
//   SELECT 
//      DATE_FORMAT(sale_date, '%Y-%m') AS sale_month,
//      item_name,
//      COUNT(*) AS casket_sold
//  FROM 
//      analytics_tb
//  JOIN 
//      inventory_tb 
//  ON 
//      casket_id = inventory_id
//  WHERE 
//      casket_id IS NOT NULL
//  GROUP BY 
//      sale_month, item_name
//  ORDER BY 
//      sale_month, item_name;
// Process the data into heatmap format
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
      
      // Create forecast data points (simple linear regression for demo)
      // In a real app, you'd use a proper forecasting model
      const lastActual = actualData[actualData.length - 1].y;
      const growthRate = 0.05; // 5% monthly growth (adjust based on your data)
      
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
      series
    };
  }

  // Process the data
  const rawCasketData = <?php echo json_encode($casketData); ?>;
const heatmapData = processDataForHeatmap(rawCasketData);

// Calculate dimensions based on data size
const itemCount = heatmapData.items.length;
const monthCount = heatmapData.months.length;
const cellSize = 40; // Base size for each cell
const headerHeight = 50; // Space for title and axis labels

// Calculate dynamic dimensions
const dynamicHeight = Math.max(350, headerHeight + (itemCount * cellSize));
const dynamicWidth = Math.max(800, monthCount * cellSize);

// Create the heatmap chart
var options = {
  series: heatmapData.series,
  chart: {
    height: dynamicHeight,
    width: dynamicWidth,
    type: 'heatmap',
    toolbar: { show: false },
    animations: { enabled: false } // Improves rendering performance
  },
  stroke: {
    width: 1,
    colors: ['#fff'] // White border around circles
  },
  plotOptions: {
    heatmap: {
      radius: cellSize * 0.35, // Perfect circle size relative to cell
      enableShades: false,
      useFillColorAsStroke: true,
      distributed: false,
      colorScale: {
        ranges: [
          { from: 0, to: 50, color: '#008FFB' },
          { from: 51, to: 100, color: '#00E396' },
          { from: 101, to: 150, color: '#FEB019' }
        ]
      }
    }
  },
  dataLabels: {
    enabled: true,
    style: {
      colors: ['#fff'],
      fontSize: '10px',
      fontFamily: 'Helvetica, Arial, sans-serif',
      fontWeight: 'bold'
    },
    formatter: function(val, opts) {
      if (opts.seriesIndex >= 0 && opts.dataPointIndex >= 0) {
        const point = heatmapData.series[opts.seriesIndex].data[opts.dataPointIndex];
        if (point.forecast) {
          return val + ' (F)';
        }
      }
      return val;
    },
    offsetY: -1 // Fine-tune label position
  },
  xaxis: {
    type: 'category',
    categories: heatmapData.months,
    labels: {
      formatter: function(value) {
        return value.split('-')[1] + '/' + value.split('-')[0].slice(2);
      },
      style: {
        fontSize: '10px',
        cssClass: 'heatmap-xaxis-label'
      },
      rotate: -45, // Diagonal labels for better fit
      hideOverlappingLabels: true
    },
    tooltip: { enabled: false },
    axisBorder: { show: false }
  },
  yaxis: {
    labels: {
      style: {
        fontSize: '11px'
      },
      offsetX: 5 // Add some spacing
    }
  },
  grid: {
    padding: {
      top: 20,
      right: 10,
      bottom: 10,
      left: 10
    },
    xaxis: { lines: { show: false } },
    yaxis: { lines: { show: false } }
  },
  tooltip: {
    custom: function({ series, seriesIndex, dataPointIndex, w }) {
      const item = w.config.series[seriesIndex].name;
      const month = w.config.xaxis.categories[dataPointIndex];
      const value = series[seriesIndex][dataPointIndex];
      const isForecast = w.config.series[seriesIndex].data[dataPointIndex].forecast;
      
      return `
        <div class="p-2 bg-white border border-gray-200 rounded shadow">
          <div class="font-bold">${item}</div>
          <div>Month: ${month}</div>
          <div>Sold: ${value}</div>
          ${isForecast ? '<div class="text-yellow-600">Forecasted Value</div>' : ''}
        </div>
      `;
    }
  },
  title: {
    text: 'Casket Sales Heatmap with Forecast',
    align: 'center',
    style: {
      fontSize: '14px',
      fontWeight: 'bold',
      cssClass: 'heatmap-title'
    },
    margin: 10
  },
  annotations: {
    xaxis: [{
      x: heatmapData.months[heatmapData.months.length - 6],
      strokeDashArray: 0,
      borderColor: '#FF4560',
      label: {
        borderColor: '#FF4560',
        style: {
          color: '#fff',
          background: '#FF4560',
          fontSize: '10px'
        },
        text: 'Forecast Start',
        offsetY: 5
      }
    }]
  }
};

// Apply CSS to prevent label overlap
const style = document.createElement('style');
style.textContent = `
  .heatmap-xaxis-label {
    white-space: nowrap;
    text-overflow: ellipsis;
    max-width: ${cellSize}px;
    display: inline-block;
    overflow: hidden;
  }
  .heatmap-title {
    margin-bottom: 5px !important;
  }
`;
document.head.appendChild(style);

// Render the chart
var chart = new ApexCharts(document.querySelector("#demandPredictionChart"), options);
chart.render();


  // Payment Methods Chart
  const paymentMethodsCtx = document.getElementById('paymentMethodsChart').getContext('2d');
  const paymentMethodsChart = new Chart(paymentMethodsCtx, {
    type: 'doughnut',
    data: {
      labels: ['Credit Card', 'Bank Transfer', 'Insurance', 'Payment Plan', 'Other'],
      datasets: [{
        data: [42, 28, 18, 10, 2],
        backgroundColor: [
          'rgba(59, 130, 246, 0.8)',
          'rgba(139, 92, 246, 0.8)',
          'rgba(16, 185, 129, 0.8)',
          'rgba(245, 158, 11, 0.8)',
          'rgba(107, 114, 128, 0.8)'
        ],
        borderColor: [
          'rgba(59, 130, 246, 1)',
          'rgba(139, 92, 246, 1)',
          'rgba(16, 185, 129, 1)',
          'rgba(245, 158, 11, 1)',
          'rgba(107, 114, 128, 1)'
        ],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'right',
          labels: {
            boxWidth: 10,
            usePointStyle: true
          }
        },
        title: {
          display: true,
          text: 'Payment Methods (%)'
        }
      }
    }
  });

  // Customer Segments Chart
  const customerSegmentsCtx = document.getElementById('customerSegmentsChart').getContext('2d');
  const customerSegmentsChart = new Chart(customerSegmentsCtx, {
    type: 'radar',
    data: {
      labels: ['Prompt Payment', 'Payment Consistency', 'Full Payment', 'Service Value', 'Repeat Business'],
      datasets: [{
        label: 'Premium Customers',
        data: [90, 85, 95, 100, 80],
        backgroundColor: 'rgba(59, 130, 246, 0.2)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: 'rgba(59, 130, 246, 1)'
      }, {
        label: 'Standard Customers',
        data: [75, 70, 85, 70, 65],
        backgroundColor: 'rgba(139, 92, 246, 0.2)',
        borderColor: 'rgba(139, 92, 246, 1)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: 'rgba(139, 92, 246, 1)'
      }, {
        label: 'At-Risk Customers',
        data: [40, 45, 60, 55, 30],
        backgroundColor: 'rgba(220, 38, 38, 0.2)',
        borderColor: 'rgba(220, 38, 38, 1)',
        borderWidth: 2,
        pointRadius: 4,
        pointBackgroundColor: 'rgba(220, 38, 38, 1)'
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'top',
          labels: {
            boxWidth: 10,
            usePointStyle: true
          }
        }
      },
      scales: {
        r: {
          angleLines: {
            display: true
          },
          suggestedMin: 0,
          suggestedMax: 100
        }
      }
    }
  });

  
});
</script>
    <script src="script.js"></script>
    <script src="tailwind.js"></script>
</body>
</html>