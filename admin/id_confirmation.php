<?php
session_start();
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

// Include database connection
require_once '../db_connect.php';

// Process ID validation requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    
    $ph_timezone = new DateTimeZone('Asia/Manila');
    $current_time = new DateTime('now', $ph_timezone);
    $ph_time = $current_time->format('Y-m-d H:i:s');

    if ($action === 'approve') {
        // Update ID status to valid and set accepted_at timestamp
        $stmt = $conn->prepare("UPDATE valid_id_tb SET is_validated = 'valid', accepted_at = ? WHERE id = ?");
        $stmt->bind_param("si", $ph_time, $id);
        $stmt->execute();
        
        // Also update the user's validated_id status
        $stmt = $conn->prepare("UPDATE users SET validated_id = 'yes' WHERE id = (SELECT id FROM valid_id_tb WHERE id = ?)");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        
        // Set success message
        $_SESSION['message'] = "ID successfully approved.";
        $_SESSION['message_type'] = "success";
    } 
    elseif ($action === 'deny') {
        $decline_reason = filter_input(INPUT_POST, 'decline_reason', FILTER_SANITIZE_STRING);
        
        if (empty($decline_reason)) {
            $_SESSION['message'] = "Please select a reason for declining the ID.";
            $_SESSION['message_type'] = "error";
        } else {
            // Update ID status to denied with reason and set decline_at timestamp
            $stmt = $conn->prepare("UPDATE valid_id_tb SET is_validated = 'denied', decline_reason = ?, decline_at = ? WHERE id = ?");
            $stmt->bind_param("ssi", $decline_reason, $ph_time, $id);
            $stmt->execute();
            
            // Set info message
            $_SESSION['message'] = "ID request denied. Reason: " . htmlspecialchars($decline_reason);
            $_SESSION['message_type'] = "info";
        }
    } 
    // Redirect to refresh the page
    header("Location: id_confirmation.php");
    exit();
}

// Fetch pending ID validation requests with branch location
$stmt = $conn->prepare("
    SELECT v.id as validation_id, v.image_path, v.is_validated, v.id,
           u.first_name, u.last_name, u.email, u.phone_number, u.branch_loc,
           b.branch_name
    FROM valid_id_tb v
    JOIN users u ON v.id = u.id
    LEFT JOIN branch_tb b ON u.branch_loc = b.branch_id
    WHERE v.is_validated = 'no'
    ORDER BY v.id DESC
");
$stmt->execute();
$result = $stmt->get_result();
$pending_ids = $result->fetch_all(MYSQLI_ASSOC);

// Count stats
$total_pending = count($pending_ids);

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM valid_id_tb WHERE is_validated = 'valid'");
$stmt->execute();
$result = $stmt->get_result();
$approved = $result->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM valid_id_tb WHERE is_validated = 'denied'");
$stmt->execute();
$result = $stmt->get_result();
$denied = $result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - ID Confirmation</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="tailwind.js"></script>
    <!-- Include SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="flex bg-gray-50">
<?php include 'admin_sidebar.php'; ?>

<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
    <!-- Header with breadcrumb and welcome message -->
    <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar">
      <div>
        <h1 class="text-2xl font-bold text-sidebar-text">ID Verification Management</h1>
      </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Pending Card -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-100 to-blue-200 px-6 py-4">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-medium text-gray-700">Pending Verifications</h3>
                    <div class="w-10 h-10 rounded-full bg-white/90 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="flex items-end">
                    <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800"><?php echo number_format($total_pending); ?></span>
                </div>
            </div>
            <div class="px-6 py-3 bg-white border-t border-gray-100">
                <div class="flex items-center text-gray-500">
                    <span class="text-xs">Updated today</span>
                </div>
            </div>
        </div>

        <!-- Approved Card -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
            <div class="bg-gradient-to-r from-green-100 to-green-200 px-6 py-4">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-medium text-gray-700">Approved IDs</h3>
                    <div class="w-10 h-10 rounded-full bg-white/90 text-green-600 flex items-center justify-center">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="flex items-end">
                    <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800"><?php echo number_format($approved); ?></span>
                </div>
            </div>
            <div class="px-6 py-3 bg-white border-t border-gray-100">
                <div class="flex items-center text-gray-500">
                    <span class="text-xs">Updated today</span>
                </div>
            </div>
        </div>

        <!-- Denied Card -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
            <div class="bg-gradient-to-r from-red-100 to-red-200 px-6 py-4">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-medium text-gray-700">Denied IDs</h3>
                    <div class="w-10 h-10 rounded-full bg-white/90 text-red-600 flex items-center justify-center">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <div class="flex items-end">
                    <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800"><?php echo number_format($denied); ?></span>
                </div>
            </div>
            <div class="px-6 py-3 bg-white border-t border-gray-100">
                <div class="flex items-center text-gray-500">
                    <span class="text-xs">Updated today</span>
                </div>
            </div>
        </div>

        <!-- Total Verifications Card -->
        <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
            <div class="bg-gradient-to-r from-purple-100 to-purple-200 px-6 py-4">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-medium text-gray-700">Total Verifications</h3>
                    <div class="w-10 h-10 rounded-full bg-white/90 text-purple-600 flex items-center justify-center">
                        <i class="fas fa-id-card"></i>
                    </div>
                </div>
                <div class="flex items-end">
                    <span class="text-2xl md:text-3xl font-bold font-cinzel text-gray-800"><?php echo number_format($total_pending + $approved + $denied); ?></span>
                </div>
            </div>
            <div class="px-6 py-3 bg-white border-t border-gray-100">
                <div class="flex items-center text-gray-500">
                    <span class="text-xs">Updated today</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notifications/Messages -->
    <?php if(isset($_SESSION['message'])): ?>
        <div class="mb-6 p-4 rounded-lg <?php echo $_SESSION['message_type'] === 'success' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-blue-100 text-blue-800 border border-blue-200'; ?> flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas <?php echo $_SESSION['message_type'] === 'success' ? 'fa-check-circle text-green-500' : 'fa-info-circle text-blue-500'; ?> mr-3"></i>
                <span><?php echo $_SESSION['message']; ?></span>
            </div>
            <button class="text-gray-500 hover:text-gray-700 focus:outline-none" onclick="this.parentElement.style.display='none'">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <?php 
        // Clear message after displaying
        unset($_SESSION['message']); 
        unset($_SESSION['message_type']);
        ?>
    <?php endif; ?>
    
    <!-- Table Card -->
    <div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
        <!-- Header Section -->
        <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <!-- Title and Counter -->
                <div class="flex items-center gap-3 mb-4 lg:mb-0">
                    <h4 class="text-lg font-bold text-sidebar-text whitespace-nowrap">Pending ID Verifications</h4>
                    
                    <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                        <?php echo $total_pending . ($total_pending != 1 ? "" : ""); ?>
                    </span>
                </div>
                
                <!-- Controls for big screens - aligned right -->
                <div class="hidden lg:flex items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative">
                        <input type="text" id="idSearchInput" 
                                placeholder="Search verifications..." 
                                class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                        <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
                    </div>
                </div>
            </div>
            
            <!-- Mobile/Tablet Controls - Only visible on smaller screens -->
            <div class="lg:hidden w-full mt-4">
                <!-- Search Input - Takes most of the space -->
                <div class="relative flex-grow">
                    <input type="text" id="mobileIdSearchInput" 
                            placeholder="Search verifications..." 
                            class="pl-8 pr-3 py-2.5 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                    <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                </div>
            </div>
        </div>
        
        <!-- Responsive Table Container -->
        <div class="overflow-x-auto scrollbar-thin" id="idTableContainer">
            <div id="idLoadingIndicator" class="hidden absolute inset-0 bg-white bg-opacity-50 flex items-center justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-sidebar-accent"></div>
            </div>
            
            <!-- Responsive Table -->
            <div class="min-w-full">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-sidebar-border">
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-user text-sidebar-accent"></i> Customer Name
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-envelope text-sidebar-accent"></i> Email
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-phone text-sidebar-accent"></i> Phone
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text cursor-pointer whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-map-marker-alt text-sidebar-accent"></i> Branch
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-id-card text-sidebar-accent"></i> ID Document
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-sidebar-text whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-cogs text-sidebar-accent"></i> Actions
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="idTableBody">
                        <?php if($total_pending > 0): ?>
                            <?php foreach($pending_ids as $id_request): ?>
                                <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                                    <td class="px-4 py-3.5 text-sm text-sidebar-text">
                                        <div class="flex items-center">
                                            <?php echo htmlspecialchars($id_request['first_name'] . ' ' . $id_request['last_name']); ?>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($id_request['email']); ?></td>
                                    <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($id_request['phone_number']); ?></td>
                                    <td class="px-4 py-3.5 text-sm text-sidebar-text"><?php echo htmlspecialchars($id_request['branch_name'] ? $id_request['branch_name'] : 'No branch assigned'); ?></td>
                                    <td class="px-4 py-3.5 text-sm">
                                        <!-- Direct ID image display in the table -->
                                        <div class="relative group">
                                            <div class="w-24 h-16 overflow-hidden rounded border border-gray-200">
                                                <img src="uploads/valid_ids/<?php echo htmlspecialchars(basename($id_request['image_path'])); ?>" 
                                                     alt="ID Document" 
                                                     class="w-full h-full object-cover cursor-pointer hover:opacity-90 transition-all" 
                                                     onclick="openModal('uploads/valid_ids/<?php echo htmlspecialchars(basename($id_request['image_path'])); ?>')">
                                            </div>
                                            <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all">
                                                <button class="p-1 bg-black bg-opacity-60 rounded-full text-white" 
                                                        onclick="openModal('uploads/valid_ids/<?php echo htmlspecialchars(basename($id_request['image_path'])); ?>')">
                                                    <i class="fas fa-search-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5 text-sm">
                                        <div class="flex space-x-2">
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_request['validation_id']); ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="button" class="p-2 bg-green-100 text-green-600 rounded-lg hover:bg-green-200 transition-all tooltip"
                                                        onclick="confirmAction('approve', this.form)"
                                                        title="Approve ID">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            
                                            <form method="POST" action="" class="inline">
                                                <input type="hidden" name="id" value="<?php echo htmlspecialchars($id_request['validation_id']); ?>">
                                                <input type="hidden" name="action" value="deny">
                                                <button type="button" class="p-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-all tooltip"
                                                        onclick="confirmDeny(this.form)"
                                                        title="Deny ID">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="p-6 text-sm text-center">
                                    <div class="flex flex-col items-center">
                                        <i class="fas fa-check-circle text-green-500 text-4xl mb-3"></i>
                                        <p class="text-gray-500">All caught up! No pending ID verification requests at this time.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Sticky Pagination Footer with improved spacing -->
<div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
    <?php 
        // Get the number of records on the current page
        $current_page_items = min($recordsPerPage, $totalOutstanding - $offsetOutstanding);

        if ($totalOutstanding > 0) {
            $start = $offsetOutstanding + 1;
            $end = $offsetOutstanding + $current_page_items;
        
            echo "Showing {$start} - {$end} of {$totalOutstanding} records";
        } else {
            echo "No records found";
        }
    ?>
    </div>
    <div id="paginationContainer" class="flex space-x-2">
        <?php 
        $totalPagesOutstanding = ceil($totalOutstanding / $recordsPerPage);
        
        if ($totalPagesOutstanding > 1): 
        ?>
            <!-- First page button (double arrow) -->
            <a href="?outstandingPage=1" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($outstandingPage == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &laquo;
            </a>
            
            <!-- Previous page button (single arrow) -->
            <a href="<?php echo '?outstandingPage=' . max(1, $outstandingPage - 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($outstandingPage == 1) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &lsaquo;
            </a>
            
            <?php
            // Show exactly 3 page numbers
            if ($totalPagesOutstanding <= 3) {
                // If total pages is 3 or less, show all pages
                $startPage = 1;
                $endPage = $totalPagesOutstanding;
            } else {
                // With more than 3 pages, determine which 3 to show
                if ($outstandingPage == 1) {
                    // At the beginning, show first 3 pages
                    $startPage = 1;
                    $endPage = 3;
                } elseif ($outstandingPage == $totalPagesOutstanding) {
                    // At the end, show last 3 pages
                    $startPage = $totalPagesOutstanding - 2;
                    $endPage = $totalPagesOutstanding;
                } else {
                    // In the middle, show current page with one before and after
                    $startPage = $outstandingPage - 1;
                    $endPage = $outstandingPage + 1;
                    
                    // Handle edge cases
                    if ($startPage < 1) {
                        $startPage = 1;
                        $endPage = 3;
                    }
                    if ($endPage > $totalPagesOutstanding) {
                        $endPage = $totalPagesOutstanding;
                        $startPage = $totalPagesOutstanding - 2;
                    }
                }
            }
            
            // Generate the page buttons
            for ($i = $startPage; $i <= $endPage; $i++) {
                $active_class = ($i == $outstandingPage) ? 'bg-sidebar-accent text-white' : 'border border-sidebar-border hover:bg-sidebar-hover';
                echo '<a href="?outstandingPage=' . $i . '" class="px-3.5 py-1.5 rounded text-sm ' . $active_class . '">' . $i . '</a>';
            }
            ?>
            
            <!-- Next page button (single arrow) -->
            <a href="<?php echo '?outstandingPage=' . min($totalPagesOutstanding, $outstandingPage + 1); ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($outstandingPage == $totalPagesOutstanding) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &rsaquo;
            </a>
            
            <!-- Last page button (double arrow) -->
            <a href="<?php echo '?outstandingPage=' . $totalPagesOutstanding; ?>" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo ($outstandingPage == $totalPagesOutstanding) ? 'opacity-50 pointer-events-none' : ''; ?>">
                &raquo;
            </a>
        <?php elseif ($totalOutstanding > 0): ?>
            <!-- If only one page but we have records, show disabled navigation buttons -->
            <button class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 pointer-events-none">&laquo;</button>
            <button class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm bg-sidebar-accent text-white">1</button>
            <button class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 pointer-events-none">&raquo;</button>
        <?php endif; ?>
    </div>
</div>
    </div>
</div>

<!-- Image Modal (Kept for full-size view) -->
<div id="imageModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        
        <!-- Modal container -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 border-b pb-2">
                            ID Document Preview
                        </h3>
                        
                        <div class="mt-4">
                            <img id="modalImage" src="" alt="ID Document Full View" class="w-full h-auto max-h-[70vh] object-contain">
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeModal()">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Custom Decline Reason Modal -->
<div id="customReasonModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
    <!-- Modal Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm"></div>
    
    <!-- Modal Content -->
    <div class="relative bg-white rounded-xl shadow-card w-full max-w-md mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] overflow-y-auto">
        <!-- Close Button -->
        <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeCustomReasonModal()">
            <i class="fas fa-times"></i>
        </button>
        
        <!-- Modal Header -->
        <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
            <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
                Enter Custom Decline Reason
            </h3>
        </div>
        
        <!-- Modal Body -->
        <div class="px-4 sm:px-6 py-4 sm:py-5">
            <textarea id="customDeclineReason" rows="4" class="w-full px-3 py-2 bg-white border border-gray-300 rounded-lg focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent outline-none transition-all duration-200" placeholder="Please specify the reason for declining..."></textarea>
        </div>
        
        <!-- Modal Footer -->
        <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
            <button class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-white border border-sidebar-accent text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" onclick="cancelCustomReason()">
                Cancel
            </button>
            <button class="w-full sm:w-auto px-5 sm:px-6 py-2 bg-gradient-to-r from-sidebar-accent to-darkgold text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center" onclick="saveCustomReason()">
                Save Reason
            </button>
        </div>
    </div>
</div>

<script>
// Image modal functionality
function openModal(imagePath) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    
    modal.classList.remove('hidden');
    modalImg.src = imagePath;
}

function closeModal() {
    document.getElementById('imageModal').classList.add('hidden');
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('imageModal');
    if (event.target === modal) {
        closeModal();
    }
});

// Handle escape key to close modal
document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        closeModal();
    }
});

// SweetAlert confirmation for approve/deny actions
function confirmAction(actionType, form) {
    const actionText = actionType === 'approve' ? 'approve' : 'deny';
    const actionTitle = actionType === 'approve' ? 'Approve ID Verification' : 'Deny ID Verification';
    const actionMessage = actionType === 'approve' 
        ? 'Are you sure you want to approve this ID verification?' 
        : 'Are you sure you want to deny this ID verification?';
    const iconType = actionType === 'approve' ? 'question' : 'warning';
    
    Swal.fire({
        title: actionTitle,
        text: actionMessage,
        icon: iconType,
        showCancelButton: true,
        confirmButtonColor: actionType === 'approve' ? '#10B981' : '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Yes, ' + actionText + ' it!',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
}

let currentDenyForm = null;

function openCustomReasonModal() {
    document.getElementById('customReasonModal').classList.remove('hidden');
}

function closeCustomReasonModal() {
    document.getElementById('customReasonModal').classList.add('hidden');
    document.getElementById('customDeclineReason').value = '';
}

function cancelCustomReason() {
    closeCustomReasonModal();
    // Reopen the SweetAlert
    confirmDeny(currentDenyForm);
}

function saveCustomReason() {
    const customReason = document.getElementById('customDeclineReason').value.trim();
    if (!customReason) {
        Swal.fire('Error', 'Please enter a decline reason', 'error');
        return;
    }
    
    // Add the custom reason to the form
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'decline_reason';
    input.value = customReason;
    currentDenyForm.appendChild(input);
    
    // Submit the form
    currentDenyForm.submit();
}

function confirmDeny(form) {
    currentDenyForm = form;
    
    const declineReasons = [
        'Incomplete Document Visibility',
        'Cropped or Cut-off Text',
        'Blurry or Low-Quality Image',
        'Glare or Shadows',
        'Missing Critical Details',
        'Others...'
    ];
    
    // Create HTML for the select dropdown
    let selectHtml = '<select id="swalDeclineReason" class="w-full mt-3 p-2 border border-gray-300 rounded focus:ring-yellow-500 focus:border-yellow-500">';
    selectHtml += '<option value="">Select a reason for decline</option>';
    declineReasons.forEach(reason => {
        selectHtml += `<option value="${reason}">${reason}</option>`;
    });
    selectHtml += '</select>';
    
    Swal.fire({
        title: 'Deny ID Verification',
        html: `Are you sure you want to deny this ID verification?<br><br>${selectHtml}`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Confirm Decline',
        cancelButtonText: 'Cancel',
        focusConfirm: false,
        preConfirm: () => {
            const reason = document.getElementById('swalDeclineReason').value;
            if (!reason) {
                Swal.showValidationMessage('Please select a reason for decline');
                return false;
            }
            
            if (reason === 'Others...') {
                // Show custom reason modal and prevent form submission
                openCustomReasonModal();
                return false;
            }
            
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            // Add the selected reason to the form
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'decline_reason';
            input.value = result.value;
            form.appendChild(input);
            
            // Submit the form
            form.submit();
        }
    });
    
    // Add event listener for the select change
    setTimeout(() => {
        const selectElement = document.getElementById('swalDeclineReason');
        if (selectElement) {
            selectElement.addEventListener('change', function() {
                if (this.value === 'Others...') {
                    // Close the SweetAlert and open custom reason modal
                    Swal.close();
                    openCustomReasonModal();
                }
            });
        }
    }, 100);
}

// Search functionality
document.addEventListener('DOMContentLoaded', function() {
    const desktopSearchInput = document.getElementById('idSearchInput');
    const mobileSearchInput = document.getElementById('mobileIdSearchInput');
    
    if (desktopSearchInput && mobileSearchInput) {
        desktopSearchInput.addEventListener('input', function() {
            mobileSearchInput.value = this.value;
            filterTable();
        });
        
        mobileSearchInput.addEventListener('input', function() {
            desktopSearchInput.value = this.value;
            filterTable();
        });
    }
    
    // Filter table based on search
    function filterTable() {
        const searchValue = (desktopSearchInput.value || '').toLowerCase();
        const rows = document.querySelectorAll('#idTableBody tr');
        
        rows.forEach(row => {
            const nameCell = row.cells[0]?.textContent?.toLowerCase() || '';
            const emailCell = row.cells[1]?.textContent?.toLowerCase() || '';
            const phoneCell = row.cells[2]?.textContent?.toLowerCase() || '';
            const branchCell = row.cells[3]?.textContent?.toLowerCase() || '';
            
            const matchesSearch = nameCell.includes(searchValue) || 
                                emailCell.includes(searchValue) || 
                                phoneCell.includes(searchValue) || 
                                branchCell.includes(searchValue);
            
            row.style.display = matchesSearch ? '' : 'none';
        });
    }
});
</script>
</body>
</html>