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
      <div class="flex space-x-2">
        <select class="px-3 py-2 border border-sidebar-border rounded-md text-sm text-sidebar-text bg-white">
          <option>By Service Type</option>
          <option>By Product</option>
          <option>By Region</option>
        </select>
        <button class="px-3 py-2 border border-sidebar-border rounded-md text-sm flex items-center text-sidebar-text hover:bg-sidebar-hover transition-all duration-300">
          <i class="fas fa-download mr-2"></i> Export
        </button>
      </div>
    </div>
    <div class="p-5">
      <canvas id="demandPredictionChart" class="h-64"></canvas>
    </div>
    <div class="px-5 pb-5">
      <div class="flex justify-between text-sm text-gray-600">
        <div>
          <div class="font-medium">Top Category</div>
          <div>Premium Services</div>
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
    
    // Calculate forecast accuracy metrics (simplified example)
    const lastActual = regressionResults.historicalChartData[regressionResults.historicalChartData.length - 1].y;
    const firstForecast = regressionResults.forecastData[0].y;
    const forecastAccuracy = 100 - Math.abs((firstForecast - lastActual) / lastActual * 100);
    
    // Update the forecast accuracy display
    document.querySelector('.forecast-accuracy-value').textContent = forecastAccuracy.toFixed(1) + '%';
    
    // Create the chart
    var options = {
        series: [{
            name: 'Actual Revenue',
            data: regressionResults.historicalChartData,
            stroke: {
                width: 3,
                curve: 'smooth',
                dashArray: 0 // Solid line for actual data
            }
        }, {
            name: 'Forecasted Revenue',
            data: regressionResults.forecastData,
            stroke: {
                width: 3,
                curve: 'smooth',
                dashArray: 5 // Dashed line for forecast
            }
        }],
        chart: {
            height: 350,
            type: 'line',
        },
        xaxis: {
            type: 'datetime',
            tickAmount: 10,
            labels: {
                formatter: function(value) {
                    const date = new Date(value);
                    return date.toLocaleString('default', { month: 'short', year: 'numeric' });
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
        colors: ['#3A57E8', '#FF5733'], // First color for actual, second for forecast
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
        },
        forecastDataPoints: {
            count: 6
        }
    };

    var chart = new ApexCharts(document.querySelector("#salesForecastChart"), options);
    chart.render();
    
    // Update the forecast card values
    const totalForecast = regressionResults.forecastData.reduce((sum, point) => sum + point.y, 0);
    document.querySelector('.sales-forecast-value').textContent = '$' + totalForecast.toLocaleString(undefined, {maximumFractionDigits: 0});
} else {
    document.querySelector("#salesForecastChart").innerHTML = '<div class="p-4 text-center text-gray-500">No revenue data available for forecasting</div>';
}

  // Demand Prediction Chart
  const demandPredictionCtx = document.getElementById('demandPredictionChart').getContext('2d');
  const demandPredictionChart = new Chart(demandPredictionCtx, {
    type: 'bar',
    data: {
      labels: ['Premium Services', 'Standard Services', 'Basic Services', 'Custom Services', 'Transport Services'],
      datasets: [{
        label: 'Current Demand',
        data: [36, 42, 28, 18, 12],
        backgroundColor: 'rgba(59, 130, 246, 0.6)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 1
      }, {
        label: 'Predicted Demand',
        data: [42, 48, 30, 22, 14],
        backgroundColor: 'rgba(139, 92, 246, 0.6)',
        borderColor: 'rgba(139, 92, 246, 1)',
        borderWidth: 1
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
        },
        tooltip: {
          mode: 'index',
          intersect: false
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          grid: {
            drawBorder: false
          },
          title: {
            display: true,
            text: 'Number of Units'
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  });

  // Payment Timeline Chart
  const paymentTimelineCtx = document.getElementById('paymentTimelineChart').getContext('2d');
  const paymentTimelineChart = new Chart(paymentTimelineCtx, {
    type: 'line',
    data: {
      labels: ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
      datasets: [{
        label: 'Avg. Days to Payment',
        data: [18.2, 17.6, 16.8, 16.2, 15.4, 14.3],
        backgroundColor: 'rgba(59, 130, 246, 0.2)',
        borderColor: 'rgba(59, 130, 246, 1)',
        borderWidth: 2,
        pointRadius: 4,
        tension: 0.2
      }, {
        label: 'Target',
        data: [15, 15, 15, 15, 15, 15],
        backgroundColor: 'rgba(220, 38, 38, 0)',
        borderColor: 'rgba(220, 38, 38, 0.5)',
        borderWidth: 2,
        borderDash: [5, 5],
        pointRadius: 0
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
        },
        title: {
          display: true,
          text: 'Average Days to Payment'
        }
      },
      scales: {
        y: {
          beginAtZero: false,
          min: 10,
          grid: {
            drawBorder: false
          }
        },
        x: {
          grid: {
            display: false
          }
        }
      }
    }
  });

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

  // Seasonality Chart
  const seasonalityCtx = document.getElementById('seasonalityChart').getContext('2d');
  const seasonalityChart = new Chart(seasonalityCtx, {
    type: 'line',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
      datasets: [{
        label: 'Last Year',
        data: [86, 82, 80, 78, 75, 72, 68, 70, 78, 86, 95, 100],
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        borderColor: 'rgba(59, 130, 246, 0.8)',
        borderWidth: 2,
        pointRadius: 2,
        tension: 0.3
      }, {
        label: 'This Year (Actual + Forecast)',
        data: [88, 84, 82, 80, 76, 74, 70, 72, 80, 88, 96, 102],
        backgroundColor: 'rgba(139, 92, 246, 0.1)',
        borderColor: 'rgba(139, 92, 246, 1)',
        borderWidth: 2,
        pointRadius: 3,
        tension: 0.3
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
        y: {
          beginAtZero: false,
          min: 60,
          grid: {
            drawBorder: false
          },
          title: {
            display: true,
            text: 'Relative Demand (%)'
          }
        },
        x: {
          grid: {
            display: false
          }
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