<?php
// loadServices/load_service_table.php
session_start();

include '../../db_connect.php';

// Default values for pagination and filtering
$recordsPerPage = 5;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$searchQuery = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$categoryFilter = isset($_GET['category']) ? $conn->real_escape_string($_GET['category']) : '';
$statusFilter = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
$branchId = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;

function formatPrice($price) {
    // Ensure the price is a numeric value
    $price = floatval($price);
    
    // Format the price with two decimal places and currency symbol
    return '₱ ' . number_format($price, 2, '.', ',');
}

// Validate branch ID
if (!$branchId) {
    echo "<tr><td colspan='6' class='text-center text-red-500'>Invalid branch ID</td></tr>";
    exit;
}


// Construct SQL query with search and filter
$sql = "SELECT 
    s.service_id, 
    s.service_name, 
    sc.service_category_name, 
    s.selling_price, 
    s.status,
    b.branch_name
FROM services_tb s
JOIN service_category sc ON s.service_categoryID = sc.service_categoryID
JOIN branch_tb b ON s.branch_id = b.branch_id
WHERE s.branch_id = $branchId
" . 
($searchQuery ? "AND (
    s.service_id LIKE '%$searchQuery%' OR 
    s.service_name LIKE '%$searchQuery%' OR 
    sc.service_category_name LIKE '%$searchQuery%' OR 
    s.selling_price LIKE '%$searchQuery%' OR 
    s.status LIKE '%$searchQuery%'
) " : '') .
($categoryFilter ? "AND sc.service_category_name = '$categoryFilter' " : '') .
($statusFilter ? "AND s.status = '$statusFilter' " : '');

// Update the count query similarly
$countSql = "SELECT COUNT(*) as count FROM services_tb s
JOIN service_category sc ON s.service_categoryID = sc.service_categoryID
WHERE s.branch_id = $branchId
" . 
($searchQuery ? "AND (
    s.service_id LIKE '%$searchQuery%' OR 
    s.service_name LIKE '%$searchQuery%' OR 
    sc.service_category_name LIKE '%$searchQuery%' OR 
    s.selling_price LIKE '%$searchQuery%' OR 
    s.status LIKE '%$searchQuery%'
) " : '') .
($categoryFilter ? "AND sc.service_category_name = '$categoryFilter' " : '') .
($statusFilter ? "AND s.status = '$statusFilter' " : '');

// Execute count query
$countResult = $conn->query($countSql);
$totalServices = $countResult ? $countResult->fetch_assoc()['count'] : 0;
$totalPages = ceil($totalServices / $recordsPerPage);

// Pagination offset
$offset = max(0, ($page - 1) * $recordsPerPage);

// Add LIMIT for pagination
$sql .= " LIMIT $offset, $recordsPerPage";

// Execute main query
$result = $conn->query($sql);
?>

<!-- Responsive Table Container with improved spacing -->
<div class="overflow-x-auto scrollbar-thin" id="tableContainer<?php echo $branchId; ?>">
    <div id="loadingIndicator<?php echo $branchId; ?>" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
        <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
    </div>
    
    <!-- Responsive Table with improved spacing and horizontal scroll for small screens -->
    <div class="min-w-full">
        <table class="w-full">
            <thead>
                <tr class="bg-gray-50 border-b border-sidebar-border">
                    <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 0)">
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-hashtag text-sidebar-accent"></i> ID 
                        </div>
                    </th>
                    <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 1)">
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-tag text-sidebar-accent"></i> Service Name 
                        </div>
                    </th>
                    <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 2)">
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-th-list text-sidebar-accent"></i> Category 
                        </div>
                    </th>
                    <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 3)">
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-peso-sign text-sidebar-accent"></i> Price 
                        </div>
                    </th>
                    <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap" onclick="sortTable(<?php echo $branchId; ?>, 4)">
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-toggle-on text-sidebar-accent"></i> Status 
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
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <?php
                        $statusClass = $row["status"] == "Active" 
                            ? "bg-green-100 text-green-600 border border-green-200" 
                            : "bg-orange-100 text-orange-500 border border-orange-200";
                        $statusIcon = $row["status"] == "Active" ? "fa-check-circle" : "fa-pause-circle";
                        ?>
                        <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                            <td class="px-4 py-3.5 text-sm text-sidebar-text font-medium">#SVC-<?php echo str_pad($row['service_id'], 3, "0", STR_PAD_LEFT); ?></td>
                            <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($row["service_name"]); ?></td>
                            <td class="px-4 py-3.5 text-sm text-sidebar-text">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100"> <?php echo htmlspecialchars($row["service_category_name"]); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-sm font-medium text-sidebar-text"><?php echo formatPrice($row["selling_price"]); ?></td>
                            <td class="px-4 py-3.5 text-sm">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                    <i class="fas <?php echo $statusIcon; ?> mr-1"></i> <?php echo htmlspecialchars($row["status"]); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-sm">
                                <div class="flex space-x-2">
                                    <button class="p-2 bg-yellow-100 text-yellow-600 rounded-lg hover:bg-yellow-200 transition-all tooltip" 
                                            title="Edit Service" 
                                            onclick="openEditServiceModal('<?php echo $row["service_id"]; ?>', '<?php echo $branchId; ?>')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip" 
                                            title="Archive Service" 
                                            onclick="archiveService('<?php echo htmlspecialchars($row['service_id'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($branchId, ENT_QUOTES); ?>')">
                                        <i class="fas fa-archive-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="px-4 py-6 text-sm text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-500">No services found for this branch</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>