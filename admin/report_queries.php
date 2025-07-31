<?php
// report_queries.php

function getRevenueData($conn, $branchId = null) {
    $branchCondition = $branchId ? "WHERE branch_id = ?" : "";
    $branchJoinCondition = $branchId ? "AND at.branch_id = ?" : "";
    
    $query = "WITH RECURSIVE date_series AS (
        SELECT 
            DATE_FORMAT(CONVERT_TZ(MIN(sale_date), '+00:00', '+08:00'), '%Y-%m-01') as month_start
        FROM 
            analytics_tb
        $branchCondition
        
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
        analytics_tb at 
        ON DATE_FORMAT(CONVERT_TZ(at.sale_date, '+00:00', '+08:00'), '%Y-%m-01') = ds.month_start
        $branchJoinCondition
    GROUP BY 
        ds.month_start
    ORDER BY 
        ds.month_start";

    $stmt = $conn->prepare($query);
    
    if ($branchId) {
        $params = [];
        $types = '';
        
        // First parameter for branchCondition
        if ($branchCondition) {
            $params[] = $branchId;
            $types .= 'i';
        }
        
        // Second parameter for branchJoinCondition
        if ($branchJoinCondition) {
            $params[] = $branchId;
            $types .= 'i';
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

function getCasketData($conn, $branchId = null) {
    $branchCondition = $branchId ? "WHERE branch_id = ?" : "";
    $branchJoinCondition = $branchId ? "AND analytics_tb.branch_id = ?" : "";
    
    $query = "WITH RECURSIVE all_months AS (
        SELECT DATE_FORMAT(MIN(sale_date), '%Y-%m-01') AS month
        FROM analytics_tb
        $branchCondition
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
            $branchJoinCondition
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
        sale_month, item_name";

    $stmt = $conn->prepare($query);
    
    if ($branchId) {
        $params = [];
        $types = '';
        
        if ($branchCondition) {
            $params[] = $branchId;
            $types .= 'i';
        }
        
        if ($branchJoinCondition) {
            $params[] = $branchId;
            $types .= 'i';
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'sale_month' => $row['sale_month'],
            'item_name' => $row['item_name'],
            'casket_sold' => (int)$row['casket_sold'],
            'is_forecast' => (bool)$row['is_forecast']
        ];
    }
    
    return $data;
}

function getSalesData($conn, $branchId = null) {
    $branchCondition = $branchId ? "WHERE branch_id = ?" : "";
    $branchJoinCondition = $branchId ? "AND a.branch_id = ?" : "";
    
    $query = "WITH RECURSIVE months AS (
        SELECT DATE_FORMAT(MIN(sale_date), '%Y-%m-01') AS month_start
        FROM analytics_tb
        $branchCondition
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
        analytics_tb a 
        ON DATE_FORMAT(a.sale_date, '%Y-%m-01') = m.month_start
        $branchJoinCondition
    GROUP BY 
        m.month_start
    ORDER BY 
        m.month_start";

    $stmt = $conn->prepare($query);
    
    if ($branchId) {
        $params = [];
        $types = '';
        
        if ($branchCondition) {
            $params[] = $branchId;
            $types .= 'i';
        }
        
        if ($branchJoinCondition) {
            $params[] = $branchId;
            $types .= 'i';
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    
    return $data;
}

function getBasicStats($conn, $branchId = null) {
    $branchCondition = $branchId ? "WHERE branch_id = ?" : "";
    
    $query = "SELECT 
        AVG(discounted_price) as avg_price,
        AVG(amount_paid) as avg_payment,
        (SUM(amount_paid) / SUM(discounted_price)) * 100 as payment_ratio
    FROM analytics_tb
    $branchCondition";
    
    $stmt = $conn->prepare($query);
    
    if ($branchId) {
        $stmt->bind_param("i", $branchId);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getLastDate($conn, $branchId = null) {
    $branchCondition = $branchId ? "WHERE branch_id = ?" : "";
    
    $query = "SELECT MAX(sale_date) as max_date FROM analytics_tb $branchCondition";
    
    $stmt = $conn->prepare($query);
    
    if ($branchId) {
        $stmt->bind_param("i", $branchId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['max_date'];
}

function getChanges($conn, $lastDate, $branchId = null) {
    $branchCondition = $branchId ? "AND branch_id = ?" : "";
    
    $query = "SELECT 
        CASE 
            WHEN (SELECT AVG(discounted_price) FROM analytics_tb WHERE sale_date < DATE_SUB(?, INTERVAL 1 MONTH) $branchCondition) > 0 
            THEN (SELECT AVG(discounted_price) FROM analytics_tb WHERE sale_date >= DATE_SUB(?, INTERVAL 1 MONTH) $branchCondition) / 
                 (SELECT AVG(discounted_price) FROM analytics_tb WHERE sale_date < DATE_SUB(?, INTERVAL 1 MONTH) $branchCondition) * 100 - 100 
            ELSE 0 
        END as price_change,
        
        CASE 
            WHEN (SELECT AVG(amount_paid) FROM analytics_tb WHERE sale_date < DATE_SUB(?, INTERVAL 1 MONTH) $branchCondition) > 0 
            THEN (SELECT AVG(amount_paid) FROM analytics_tb WHERE sale_date >= DATE_SUB(?, INTERVAL 1 MONTH) $branchCondition) / 
                 (SELECT AVG(amount_paid) FROM analytics_tb WHERE sale_date < DATE_SUB(?, INTERVAL 1 MONTH) $branchCondition) * 100 - 100 
            ELSE 0 
        END as payment_change,
        
        CASE 
            WHEN (SELECT SUM(discounted_price) FROM analytics_tb WHERE sale_date < DATE_SUB(?, INTERVAL 1 MONTH) $branchCondition) > 0 
                 AND (SELECT SUM(amount_paid) FROM analytics_tb WHERE sale_date < DATE_SUB(?, INTERVAL 1 MONTH) $branchCondition) > 0 
            THEN (SELECT (SUM(amount_paid)/SUM(discounted_price)*100) FROM analytics_tb WHERE sale_date >= DATE_SUB(?, INTERVAL 1 MONTH) $branchCondition) / 
                 (SELECT (SUM(amount_paid)/SUM(discounted_price)*100) FROM analytics_tb WHERE sale_date < DATE_SUB(?, INTERVAL 1 MONTH) $branchCondition) * 100 - 100 
            ELSE 0 
        END as ratio_change";

    $stmt = $conn->prepare($query);
    
    if ($branchId) {
        // For branch filtering, we need to pass the branch ID 10 times (once for each subquery)
        $params = array_fill(0, 10, $lastDate);
        $params = array_merge($params, array_fill(0, 10, $branchId));
        $types = str_repeat("s", 10) . str_repeat("i", 10);
        $stmt->bind_param($types, ...$params);
    } else {
        $params = array_fill(0, 10, $lastDate);
        $types = str_repeat("s", 10);
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
?>