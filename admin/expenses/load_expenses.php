<?php
require_once '../db_connect.php';

// Get parameters
$branchId = $_POST['branch_id'] ?? '';
$page = $_POST['page'] ?? 1;
$searchTerm = $_POST['search'] ?? '';
$categoryFilter = $_POST['category'] ?? '';
$statusFilter = $_POST['status'] ?? '';

// Validate inputs
if (!is_numeric($branchId) || !is_numeric($page)) {
    die("Invalid parameters");
}

// Calculate where clause
$whereClause = "WHERE branch_id = ? AND appearance = 'visible'";
$params = [$branchId];
$types = "i";

if (!empty($categoryFilter)) {
    $whereClause .= " AND category = ?";
    $params[] = $categoryFilter;
    $types .= "s";
}

if (!empty($statusFilter)) {
    $whereClause .= " AND status = ?";
    $params[] = $statusFilter;
    $types .= "s";
}

if (!empty($searchTerm)) {
    $whereClause .= " AND (expense_name LIKE ? OR category LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ss";
}

// Pagination setup
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

// Count total expenses
$countQuery = "SELECT COUNT(*) as total FROM expense_tb $whereClause";
$stmt = $conn->prepare($countQuery);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$countResult = $stmt->get_result();
$totalExpenses = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalExpenses / $recordsPerPage);

// Fetch expenses
$expenseQuery = "SELECT * FROM expense_tb $whereClause ORDER BY date DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($expenseQuery);
$params[] = $recordsPerPage;
$params[] = $offset;
$types .= "ii";
$stmt->bind_param($types, ...$params);
$stmt->execute();
$expenseResult = $stmt->get_result();

// Output the table and pagination
?>
<table class="w-full">
    <thead>
        <tr class="bg-gray-50 border-b border-sidebar-border">
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?= $branchId ?>, 0)">
                <div class="flex items-center gap-1.5">
                    <i class="fas fa-hashtag text-sidebar-accent"></i> ID 
                </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?= $branchId ?>, 1)">
                <div class="flex items-center gap-1.5">
                    <i class="fa-solid fa-file-invoice text-sidebar-accent"></i> Expense Name 
                </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?= $branchId ?>, 2)">
                <div class="flex items-center gap-1.5">
                    <i class="fas fa-th-list text-sidebar-accent"></i> Category 
                </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?= $branchId ?>, 3)">
                <div class="flex items-center gap-1.5">
                    <i class="fas fa-peso-sign text-sidebar-accent"></i> Amount 
                </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?= $branchId ?>, 4)">
                <div class="flex items-center gap-1.5">
                    <i class="fas fa-calendar-alt text-sidebar-accent"></i> Date 
                </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?= $branchId ?>, 5)">
                <div class="flex items-center gap-1.5">
                    <i class="fas fa-check-circle text-sidebar-accent"></i> Status 
                </div>
            </th>
            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
                <div class="flex items-center gap-1.5">
                    <i class="fas fa-cogs text-sidebar-accent"></i> Actions
                </div>
            </th>
        </tr>
    </thead>
    <tbody>
        <?php if ($expenseResult->num_rows > 0): ?>
            <?php while($expense = $expenseResult->fetch_assoc()): ?>
                <?php
                $statusClass = $expense['status'] == 'paid' 
                    ? "bg-green-100 text-green-600 border border-green-200" 
                    : "bg-orange-100 text-orange-500 border border-orange-200";
                $statusIcon = $expense['status'] == 'paid' ? "fa-check-circle" : "fa-clock";
                $statusText = $expense['status'] == 'paid' ? 'Paid' : 'To be paid';
                ?>
                <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                    <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#EXP-<?= str_pad($expense['expense_ID'], 3, "0", STR_PAD_LEFT) ?></td>
                    <td class="px-4 py-3.5 text-sm text-sidebar-text"><?= htmlspecialchars($expense['expense_name']) ?></td>
                    <td class="px-4 py-3.5 text-sm text-sidebar-text">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                            <?= htmlspecialchars($expense['category']) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text">₱<?= number_format($expense['price'], 2) ?></td>
                    <td class="px-4 py-3.5 text-sm text-sidebar-text"><?= $expense['date'] ?></td>
                    <td class="px-4 py-3.5 text-sm">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?= $statusClass ?>">
                            <i class="fas <?= $statusIcon ?> mr-1"></i> <?= $statusText ?>
                        </span>
                    </td>
                    <td class="px-4 py-3.5 text-sm">
                        <div class="flex space-x-2">
                            <button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" title="Edit Expense" 
                                    onclick="openEditExpenseModal('#EXP-<?= str_pad($expense['expense_ID'], 3, '0', STR_PAD_LEFT) ?>', 
                                        '<?= addslashes($expense['expense_name']) ?>', 
                                        '<?= addslashes($expense['category']) ?>', 
                                        '<?= $expense['price'] ?>', 
                                        '<?= $expense['date'] ?>', 
                                        '<?= $expense['branch_id'] ?>', 
                                        '<?= $expense['status'] ?>', 
                                        '<?= addslashes($expense['notes'] ?? '') ?>')">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip" title="Delete Expense" 
                                    onclick="deleteExpense('#EXP-<?= str_pad($expense['expense_ID'], 3, '0', STR_PAD_LEFT) ?>')">
                                <i class="fas fa-archive text-red"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="7" class="px-4 py-6 text-sm text-center">
                    <div class="flex flex-col items-center">
                        <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                        <p class="text-gray-500">No expenses found for this branch</p>
                    </div>
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- Pagination -->
<div class="pagination-container sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div class="text-sm text-gray-500 text-center sm:text-left">
        Showing <?= ($offset + 1) . ' - ' . min($offset + $recordsPerPage, $totalExpenses) ?> 
        of <?= $totalExpenses ?> expenses
    </div>
    <div class="flex space-x-2">
        <a href="#" onclick="changeBranchPage(<?= $branchId ?>, <?= $page - 1 ?>); return false;" 
           class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?= $page <= 1 ? 'opacity-50 pointer-events-none' : '' ?>">&laquo;</a>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="#" onclick="changeBranchPage(<?= $branchId ?>, <?= $i ?>); return false;" 
               class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm <?= $i == $page ? 'bg-sidebar-accent text-white' : 'hover:bg-sidebar-hover' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
        
        <a href="#" onclick="changeBranchPage(<?= $branchId ?>, <?= $page + 1 ?>); return false;" 
           class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?= $page >= $totalPages ? 'opacity-50 pointer-events-none' : '' ?>">&raquo;</a>
    </div>
</div>