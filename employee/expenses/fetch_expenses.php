<?php
// fetch_expenses.php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in and is an employee
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 2) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

$branch = $_SESSION['branch_employee'];
$items_per_page = 5;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Base query
$expense_query = "SELECT expense_ID, category, expense_name, date, branch_id, status, price, notes 
                  FROM `expense_tb` 
                  WHERE branch_id = ? AND appearance = 'visible'";

// Count total expenses for pagination
$count_query = "SELECT COUNT(*) as total FROM expense_tb WHERE branch_id = ? AND appearance = 'visible'";
$params = [$branch];
$types = "s";

// Add filters to count query
if (!empty($search)) {
    $count_query .= " AND (expense_name LIKE ? OR notes LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if (!empty($category_filter)) {
    $count_query .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $count_query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Execute count query
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_items = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_items / $items_per_page);

// Add filters to main query
$params = [$branch];
$types = "s";

if (!empty($search)) {
    $expense_query .= " AND (expense_name LIKE ? OR notes LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "ss";
}

if (!empty($category_filter)) {
    $expense_query .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($status_filter)) {
    $expense_query .= " AND status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Add sorting
$valid_sort_columns = ['expense_ID', 'expense_name', 'category', 'price', 'date', 'status'];
$sort_by = in_array($sort_by, $valid_sort_columns) ? $sort_by : 'date';
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
$expense_query .= " ORDER BY $sort_by $sort_order";

// Add pagination
$expense_query .= " LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";

// Prepare and execute main query
$expense_stmt = $conn->prepare($expense_query);
$expense_stmt->bind_param($types, ...$params);
$expense_stmt->execute();
$expense_result = $expense_stmt->get_result();
$expenses = $expense_result->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_expenses_query = "SELECT SUM(price) as total FROM expense_tb WHERE branch_id = ? AND appearance = 'visible'";
$total_stmt = $conn->prepare($total_expenses_query);
$total_stmt->bind_param("s", $branch);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_expenses = $total_result->fetch_assoc()['total'] ?? 0;

// Monthly expenses query
$current_month_start = date('Y-m-01');
$next_month_start = date('Y-m-01', strtotime('+1 month'));
$monthly_expenses_query = "SELECT SUM(price) as total FROM expense_tb WHERE branch_id = ? AND appearance = 'visible' AND date >= ? AND date < ?";
$monthly_stmt = $conn->prepare($monthly_expenses_query);
$monthly_stmt->bind_param("iss", $branch, $current_month_start, $next_month_start);
$monthly_stmt->execute();
$monthly_result = $monthly_stmt->get_result();
$monthly_expenses = $monthly_result->fetch_assoc()['total'] ?? 0;

// Get pending payments count
$pending_query = "SELECT COUNT(*) as pending FROM expense_tb WHERE branch_id = ? AND status = 'To be paid' AND appearance = 'visible'";
$pending_stmt = $conn->prepare($pending_query);
$pending_stmt->bind_param("s", $branch);
$pending_stmt->execute();
$pending_result = $pending_stmt->get_result();
$pending_payments = $pending_result->fetch_assoc()['pending'];

// Prepare response
$response = [
    'expenses' => $expenses,
    'total_items' => $total_items,
    'total_pages' => $total_pages,
    'current_page' => $current_page,
    'total_expenses' => $total_expenses,
    'monthly_expenses' => $monthly_expenses,
    'pending_payments' => $pending_payments,
    'pagination_html' => generatePaginationHTML($total_pages, $current_page, $search, $category_filter, $status_filter, $sort_by, $sort_order),
    'showing_text' => 'Showing ' . ($offset + 1) . ' to ' . min($offset + $items_per_page, $total_items) . ' of ' . $total_items . ' expenses'
];

echo json_encode($response);

function generatePaginationHTML($total_pages, $current_page, $search, $category_filter, $status_filter, $sort_by, $sort_order) {
    ob_start();
    ?>
    <?php if ($total_pages > 1): ?>
        <!-- First page button (double arrow) -->
        <a href="#" onclick="loadExpenses(1)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &laquo;
        </a>
        
        <!-- Previous page button (single arrow) -->
        <a href="#" onclick="loadExpenses(<?php echo max(1, $current_page - 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &lsaquo;
        </a>
        
        <?php
        // Show exactly 3 page numbers
        if ($total_pages <= 3) {
            // If total pages is 3 or less, show all pages
            $start_page = 1;
            $end_page = $total_pages;
        } else {
            // With more than 3 pages, determine which 3 to show
            if ($current_page == 1) {
                // At the beginning, show first 3 pages
                $start_page = 1;
                $end_page = 3;
            } elseif ($current_page == $total_pages) {
                // At the end, show last 3 pages
                $start_page = $total_pages - 2;
                $end_page = $total_pages;
            } else {
                // In the middle, show current page with one before and after
                $start_page = $current_page - 1;
                $end_page = $current_page + 1;
                
                // Handle edge cases
                if ($start_page < 1) {
                    $start_page = 1;
                    $end_page = 3;
                }
                if ($end_page > $total_pages) {
                    $end_page = $total_pages;
                    $start_page = $total_pages - 2;
                }
            }
        }
        
        // Generate the page buttons
        for ($i = $start_page; $i <= $end_page; $i++) {
            $active_class = ($i == $current_page) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
            echo '<a href="#" onclick="loadExpenses(' . $i . ')" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</a>';
        }
        ?>
        
        <!-- Next page button (single arrow) -->
        <a href="#" onclick="loadExpenses(<?php echo min($total_pages, $current_page + 1); ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &rsaquo;
        </a>
        
        <!-- Last page button (double arrow) -->
        <a href="#" onclick="loadExpenses(<?php echo $total_pages; ?>)" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($current_page == $total_pages) ? 'opacity-50 pointer-events-none' : ''; ?>">
            &raquo;
        </a>
    <?php endif; ?>
    <?php
    return ob_get_clean();
}