<?php
require_once '../../db_connect.php';

header('Content-Type: application/json');

$period = $_GET['period'] ?? 'month';

$currentDate = new DateTime();
$labels = [];
$values = [];
$keys = [];

switch ($period) {
    case 'quarter':
        $interval = 'QUARTER';
        $count = 4;
        $format = 'Y \\Qq';
        $dbFormat = '%Y Q%q';
        
        for ($i = $count-1; $i >= 0; $i--) {
            $date = clone $currentDate;
            $date->modify("-$i quarters");
            $labels[] = $date->format($format);
            $keys[] = $date->format('Y Qq');
        }
        break;
        
    case 'year':
        $interval = 'YEAR';
        $count = 5;
        $format = 'Y';
        $dbFormat = '%Y';
        
        for ($i = $count-1; $i >= 0; $i--) {
            $date = clone $currentDate;
            $date->modify("-$i years");
            $labels[] = $date->format($format);
            $keys[] = $date->format('Y');
        }
        break;
        
    default: // month
        $interval = 'MONTH';
        $count = 12;
        $format = 'M';
        $dbFormat = '%Y-%m';
        
        for ($i = $count-1; $i >= 0; $i--) {
            $date = clone $currentDate;
            $date->modify("-$i months");
            $labels[] = $date->format($format);
            $keys[] = $date->format('Y-m');
        }
}

// Initialize all values to 0
$values = array_fill(0, $count, 0);

// Get data from database
$query = "SELECT 
            DATE_FORMAT(updated_at, '$dbFormat') as period,
            SUM(quantity * price) as period_value
          FROM inventory_tb
          WHERE status = 1
          AND updated_at >= DATE_SUB(CURRENT_DATE(), INTERVAL $count $interval)
          GROUP BY DATE_FORMAT(updated_at, '$dbFormat')";

$result = $conn->query($query);

if ($result) {
    $dbData = [];
    while ($row = $result->fetch_assoc()) {
        $dbData[$row['period']] = $row['period_value'];
    }
    
    // Match database data with our period keys
    foreach ($keys as $index => $key) {
        if (isset($dbData[$key])) {
            $values[$index] = $dbData[$key];
        }
    }
}

echo json_encode([
    'labels' => $labels,
    'values' => $values
]);
?>