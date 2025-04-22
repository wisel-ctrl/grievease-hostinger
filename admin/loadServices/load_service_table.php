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
FROM Services_tb s
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
$countSql = "SELECT COUNT(*) as count FROM Services_tb s
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

<div class="relative">
    <div id="loadingIndicator<?php echo $branchId; ?>" class="absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center hidden">
        <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500"></div>
    </div>
    
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 0)">
                    <div class="flex items-center">
                        <i class="fas fa-hashtag mr-1.5 text-blue-500"></i> ID 
                               </div>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 1)">
                    <div class="flex items-center">
                        <i class="fas fa-tag mr-1.5 text-blue-500"></i> Service Name 
                               </div>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 2)">
                    <div class="flex items-center">
                        <i class="fas fa-th-list mr-1.5 text-blue-500"></i> Category 
                               </div>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 3)">
                    <div class="flex items-center">
                        <i class="fas fa-peso-sign mr-1.5 text-blue-500"></i> Price 
                               </div>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" onclick="sortTable(<?php echo $branchId; ?>, 4)">
                    <div class="flex items-center">
                        <i class="fas fa-toggle-on mr-1.5 text-blue-500"></i> Status 
                               </div>
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <div class="flex items-center">
                        <i class="fas fa-cogs mr-1.5 text-blue-500"></i> Actions
                    </div>
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <?php
                    $statusClass = $row["status"] == "Active" 
                        ? "bg-green-100 text-green-800" 
                        : "bg-yellow-100 text-yellow-800";
                    $statusIcon = $row["status"] == "Active" ? "fa-check-circle" : "fa-pause-circle";
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            #SVC-<?php echo str_pad($row['service_id'], 3, "0", STR_PAD_LEFT); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($row["service_name"]); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <i class="fas fa-folder-open mr-1"></i> <?php echo htmlspecialchars($row["service_category_name"]); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo formatPrice($row["selling_price"]); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                <i class="fas <?php echo $statusIcon; ?> mr-1"></i> <?php echo htmlspecialchars($row["status"]); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex space-x-2">
                                <button class="p-2 text-blue-600 hover:text-blue-900 hover:bg-blue-50 rounded transition-colors"
                                        onclick="openEditServiceModal('<?php echo $row["service_id"]; ?>', '<?php echo $branchId; ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="p-2 text-red-600 hover:text-red-900 hover:bg-red-50 rounded transition-colors"
                                        onclick="deleteService('<?php echo $row["service_id"]; ?>', '<?php echo $branchId; ?>')">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                        <div class="flex flex-col items-center justify-center py-6">
                            <i class="fas fa-inbox text-gray-300 text-4xl mb-3"></i>
                            <p>No services found for this branch</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Showing <span class="font-medium"><?php echo ($offset + 1); ?></span> to <span class="font-medium"><?php echo min($offset + $recordsPerPage, $totalServices); ?></span> of <span class="font-medium"><?php echo $totalServices; ?></span> results
                </p>
            </div>
            <div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <button onclick="changePage(<?php echo $branchId; ?>, <?php echo max(1, $page - 1); ?>)" 
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo $page <= 1 ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                            <?php echo $page <= 1 ? 'disabled' : ''; ?>>
                        <span class="sr-only">Previous</span>
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <button onclick="changePage(<?php echo $branchId; ?>, <?php echo $i; ?>)" 
                                class="<?php echo $i == $page ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                            <?php echo $i; ?>
                        </button>
                    <?php endfor; ?>
                    
                    <button onclick="changePage(<?php echo $branchId; ?>, <?php echo min($totalPages, $page + 1); ?>)" 
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo $page >= $totalPages ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                            <?php echo $page >= $totalPages ? 'disabled' : ''; ?>>
                        <span class="sr-only">Next</span>
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </nav>
            </div>
        </div>
    </div>
</div>