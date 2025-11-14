<?php
session_start();

include 'faviconLogo.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
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

// Set page title
$pageTitle = "Feedback Management";

// Include header
include 'admin_header.php';

// Get feedback counts
$activeCount = 0;
$archivedCount = 0;
$countQuery = "SELECT 
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_count
    FROM feedback";
$countResult = $conn->query($countQuery);
if ($countResult && $countResult->num_rows > 0) {
    $counts = $countResult->fetch_assoc();
    $activeCount = $counts['active_count'] ?? 0;
    $archivedCount = $counts['archived_count'] ?? 0;
}
?>

<!-- Main Content -->
<div class="ml-64 w-[calc(100%-16rem)] p-4 sm:p-6 transition-all duration-300">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-sidebar-text">Feedback Management</h1>
            <p class="text-sm text-gray-500 mt-1">Manage and respond to customer feedback and reviews</p>
        </div>
        <div class="mt-4 sm:mt-0">
            <button onclick="exportFeedback('active')" class="inline-flex items-center px-4 py-2 border border-sidebar-accent text-sm font-medium rounded-md text-sidebar-accent bg-white hover:bg-sidebar-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent transition-colors">
                <i class="fas fa-file-export mr-2"></i> Export Active
            </button>
            <button onclick="exportFeedback('archived')" class="ml-2 inline-flex items-center px-4 py-2 border border-sidebar-accent text-sm font-medium rounded-md text-sidebar-accent bg-white hover:bg-sidebar-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent transition-colors">
                <i class="fas fa-file-archive mr-2"></i> Export Archived
            </button>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="border-b border-sidebar-border mb-6">
        <nav class="-mb-px flex space-x-8">
            <button id="active-tab" class="tab-button active border-b-2 border-sidebar-accent text-sidebar-accent whitespace-nowrap py-4 px-1 text-sm font-medium flex items-center">
                <i class="fas fa-comment-alt mr-2"></i>
                Active Feedback
                <span id="active-count" class="ml-2 bg-sidebar-accent text-white text-xs font-medium px-2 py-0.5 rounded-full"><?php echo $activeCount; ?></span>
            </button>
            <button id="archived-tab" class="tab-button border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 text-sm font-medium flex items-center">
                <i class="fas fa-archive mr-2"></i>
                Archived
                <span id="archived-count" class="ml-2 bg-gray-200 text-gray-600 text-xs font-medium px-2 py-0.5 rounded-full"><?php echo $archivedCount; ?></span>
            </button>
        </nav>
    </div>

    <!-- Search and Filter Bar -->
    <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border mb-6">
        <div class="p-4">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-grow">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </div>
                    <input type="text" id="search-input" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent sm:text-sm" placeholder="Search feedback by customer name, email, or comment...">
                </div>
                <div class="w-full sm:w-48">
                    <select id="filter-rating" class="block w-full pl-3 pr-10 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent">
                        <option value="">All Ratings</option>
                        <option value="5">★★★★★ (5)</option>
                        <option value="4">★★★★☆ (4)</option>
                        <option value="3">★★★☆☆ (3)</option>
                        <option value="2">★★☆☆☆ (2)</option>
                        <option value="1">★☆☆☆☆ (1)</option>
                    </select>
                </div>
                <div class="w-full sm:w-48">
                    <select id="filter-date" class="block w-full pl-3 pr-10 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-1 focus:ring-sidebar-accent focus:border-sidebar-accent">
                        <option value="">All Time</option>
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="year">This Year</option>
                    </select>
                </div>
                <button id="reset-filters" class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-lg text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent transition-colors">
                    <i class="fas fa-sync-alt mr-1"></i> Reset
                </button>
                <button id="apply-filters" class="px-4 py-2 bg-sidebar-accent text-white text-sm font-medium rounded-lg hover:bg-sidebar-accent/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent transition-colors">
                    <i class="fas fa-filter mr-1"></i> Apply
                </button>
            </div>
        </div>
    </div>

    <!-- Feedback List -->
    <div class="bg-white rounded-lg shadow-sidebar border border-sidebar-border overflow-hidden">
        <!-- Active Feedback Tab Content -->
        <div id="active-feedback" class="feedback-tab-content">
            <div class="overflow-x-auto scrollbar-thin">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-sidebar-hover">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-sidebar-text uppercase tracking-wider">
                                <div class="flex items-center">
                                    <span>Customer</span>
                                    <button class="ml-1 text-gray-400 hover:text-sidebar-accent focus:outline-none">
                                        <i class="fas fa-sort"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-sidebar-text uppercase tracking-wider">
                                <div class="flex items-center">
                                    <span>Rating</span>
                                    <button class="ml-1 text-gray-400 hover:text-sidebar-accent focus:outline-none">
                                        <i class="fas fa-sort"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-sidebar-text uppercase tracking-wider">Feedback</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-sidebar-text uppercase tracking-wider">
                                <div class="flex items-center">
                                    <span>Date</span>
                                    <button class="ml-1 text-gray-400 hover:text-sidebar-accent focus:outline-none">
                                        <i class="fas fa-sort"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-sidebar-text uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="active-feedback-body" class="bg-white divide-y divide-gray-200">
                        <!-- Loading Skeleton -->
                        <tr id="loading-skeleton">
                            <td colspan="5" class="px-6 py-4">
                                <div class="animate-pulse space-y-4">
                                    <?php for($i = 0; $i < 5; $i++): ?>
                                    <div class="flex items-center justify-between py-3">
                                        <div class="h-4 bg-gray-200 rounded w-1/4"></div>
                                        <div class="h-4 bg-gray-200 rounded w-1/6"></div>
                                        <div class="h-4 bg-gray-200 rounded w-1/3"></div>
                                        <div class="h-4 bg-gray-200 rounded w-1/6"></div>
                                        <div class="h-4 bg-gray-200 rounded w-1/5"></div>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                            </td>
                        </tr>
                        <!-- Empty state will be shown if no feedback -->
                        <tr id="empty-state" class="hidden">
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-comment-slash text-4xl text-gray-300 mb-3"></i>
                                    <h3 class="text-lg font-medium text-gray-900">No feedback found</h3>
                                    <p class="text-gray-500 mt-1">There are no feedback items to display.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-sidebar-border sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button id="prev-page-mobile" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Previous
                    </button>
                    <button id="next-page-mobile" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p id="pagination-info" class="text-sm text-gray-700">
                            Loading...
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button id="first-page" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <span class="sr-only">First</span>
                                <i class="fas fa-angle-double-left h-4 w-4"></i>
                            </button>
                            <button id="prev-page" class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left h-4 w-4"></i>
                            </button>
                            <div id="page-numbers" class="flex">
                                <!-- Page numbers will be inserted here -->
                            </div>
                            <button id="next-page" class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right h-4 w-4"></i>
                            </button>
                            <button id="last-page" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <span class="sr-only">Last</span>
                                <i class="fas fa-angle-double-right h-4 w-4"></i>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Archived Feedback Tab Content (initially hidden) -->
        <div id="archived-feedback" class="feedback-tab-content hidden">
            <div class="overflow-x-auto scrollbar-thin">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-sidebar-hover">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-sidebar-text uppercase tracking-wider">
                                <div class="flex items-center">
                                    <span>Customer</span>
                                    <button class="ml-1 text-gray-400 hover:text-sidebar-accent focus:outline-none">
                                        <i class="fas fa-sort"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-sidebar-text uppercase tracking-wider">
                                <div class="flex items-center">
                                    <span>Rating</span>
                                    <button class="ml-1 text-gray-400 hover:text-sidebar-accent focus:outline-none">
                                        <i class="fas fa-sort"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-sidebar-text uppercase tracking-wider">Feedback</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-sidebar-text uppercase tracking-wider">
                                <div class="flex items-center">
                                    <span>Date Archived</span>
                                    <button class="ml-1 text-gray-400 hover:text-sidebar-accent focus:outline-none">
                                        <i class="fas fa-sort"></i>
                                    </button>
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-sidebar-text uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="archived-feedback-body" class="bg-white divide-y divide-gray-200">
                        <!-- Empty state -->
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <i class="fas fa-archive text-4xl text-gray-300 mb-3"></i>
                                    <h3 class="text-lg font-medium text-gray-900">No archived feedback</h3>
                                    <p class="text-gray-500 mt-1">Archived feedback will appear here.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-sidebar-border sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <button id="archived-prev-page-mobile" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Previous
                    </button>
                    <button id="archived-next-page-mobile" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                        Next
                    </button>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p id="archived-pagination-info" class="text-sm text-gray-700">
                            No archived feedback
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button id="archived-first-page" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <span class="sr-only">First</span>
                                <i class="fas fa-angle-double-left h-4 w-4"></i>
                            </button>
                            <button id="archived-prev-page" class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left h-4 w-4"></i>
                            </button>
                            <div id="archived-page-numbers" class="flex">
                                <!-- Page numbers will be inserted here -->
                            </div>
                            <button id="archived-next-page" class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right h-4 w-4"></i>
                            </button>
                            <button id="archived-last-page" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                                <span class="sr-only">Last</span>
                                <i class="fas fa-angle-double-right h-4 w-4"></i>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Feedback Modal -->
<div id="viewFeedbackModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Feedback Details
                                </h3>
                                <div class="mt-1 flex items-center" id="modal-rating">
                                    <!-- Rating stars will be inserted here -->
                                </div>
                                <p class="mt-1 text-sm text-gray-500" id="modal-date">
                                    <!-- Date will be inserted here -->
                                </p>
                            </div>
                            <button type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none" id="close-view-modal">
                                <span class="sr-only">Close</span>
                                <i class="fas fa-times h-5 w-5"></i>
                            </button>
                        </div>
                        <div class="mt-4">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                    <i class="fas fa-user text-gray-500"></i>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900" id="modal-customer">
                                        <!-- Customer name will be inserted here -->
                                    </p>
                                    <p class="text-sm text-gray-500" id="modal-email">
                                        <!-- Customer email will be inserted here -->
                                    </p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <p class="text-sm text-gray-700" id="modal-feedback">
                                    <!-- Feedback content will be inserted here -->
                                </p>
                            </div>
                            <div class="mt-4 border-t border-gray-200 pt-4" id="modal-response-section">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Admin Response</h4>
                                <div class="bg-gray-50 p-3 rounded-md" id="modal-response-content">
                                    <!-- Admin response will be inserted here or a form if no response exists -->
                                    <p class="text-sm text-gray-500 italic">No response yet.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-sidebar-accent text-base font-medium text-white hover:bg-sidebar-accent/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent sm:ml-3 sm:w-auto sm:text-sm" id="respond-btn">
                    Respond
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" id="archive-feedback-btn">
                    <i class="fas fa-archive mr-2"></i> Archive
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" id="close-modal-btn">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Response Modal -->
<div id="responseModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="response-modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <div class="flex justify-between items-start">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="response-modal-title">
                                Respond to Feedback
                            </h3>
                            <button type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none" id="close-response-modal">
                                <span class="sr-only">Close</span>
                                <i class="fas fa-times h-5 w-5"></i>
                            </button>
                        </div>
                        <div class="mt-4">
                            <div class="mb-4">
                                <label for="response-text" class="block text-sm font-medium text-gray-700">Your Response</label>
                                <div class="mt-1">
                                    <textarea id="response-text" rows="4" class="shadow-sm focus:ring-sidebar-accent focus:border-sidebar-accent block w-full sm:text-sm border border-gray-300 rounded-md p-2"></textarea>
                                </div>
                                <p class="mt-2 text-sm text-gray-500">
                                    Your response will be visible to the customer.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-sidebar-accent text-base font-medium text-white hover:bg-sidebar-accent/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent sm:ml-3 sm:w-auto sm:text-sm" id="submit-response-btn">
                    Submit Response
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sidebar-accent sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" id="cancel-response-btn">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for handling UI interactions -->
<script>
// DOM Elements
const activeTab = document.getElementById('active-tab');
const archivedTab = document.getElementById('archived-tab');
const activeContent = document.getElementById('active-feedback');
const archivedContent = document.getElementById('archived-feedback');
const viewModal = document.getElementById('viewFeedbackModal');
const responseModal = document.getElementById('responseModal');
const closeModalBtn = document.getElementById('close-modal-btn');
const closeViewModalBtn = document.getElementById('close-view-modal');
const closeResponseModalBtn = document.getElementById('close-response-modal');
const cancelResponseBtn = document.getElementById('cancel-response-btn');
const archiveBtn = document.getElementById('archive-feedback-btn');
const respondBtn = document.getElementById('respond-btn');
const submitResponseBtn = document.getElementById('submit-response-btn');
const responseText = document.getElementById('response-text');

// Tab switching functionality
function switchTab(tab) {
    // Update tab styles
    if (tab === 'active') {
        activeTab.classList.add('border-sidebar-accent', 'text-sidebar-accent');
        activeTab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        archivedTab.classList.remove('border-sidebar-accent', 'text-sidebar-accent');
        archivedTab.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        
        // Show active content, hide archived
        activeContent.classList.remove('hidden');
        archivedContent.classList.add('hidden');
    } else {
        archivedTab.classList.add('border-sidebar-accent', 'text-sidebar-accent');
        archivedTab.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        activeTab.classList.remove('border-sidebar-accent', 'text-sidebar-accent');
        activeTab.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        
        // Show archived content, hide active
        archivedContent.classList.remove('hidden');
        activeContent.classList.add('hidden');
    }
    
    // Here you would typically load the appropriate data for the selected tab
    // loadFeedbackData(tab);
}

// Event listeners for tabs
activeTab.addEventListener('click', () => switchTab('active'));
archivedTab.addEventListener('click', () => switchTab('archived'));

// Modal control functions
function openViewModal(feedbackId) {
    // Here you would fetch the feedback details for the given ID
    // and populate the modal with the data
    // For now, we'll just show the modal with some placeholder data
    
    // Example data - in a real app, this would come from an API call
    const exampleFeedback = {
        id: feedbackId,
        customer: 'John Doe',
        email: 'john@example.com',
        rating: 4,
        date: '2025-03-15',
        feedback: 'The service was excellent! The staff was very professional and caring during our difficult time. The facilities were clean and well-maintained.',
        hasResponse: false,
        response: ''
    };
    
    // Populate the modal with the feedback data
    document.getElementById('modal-customer').textContent = exampleFeedback.customer;
    document.getElementById('modal-email').textContent = exampleFeedback.email;
    document.getElementById('modal-date').textContent = `Submitted on ${new Date(exampleFeedback.date).toLocaleDateString()}`;
    document.getElementById('modal-feedback').textContent = exampleFeedback.feedback;
    
    // Set the rating stars
    const ratingContainer = document.getElementById('modal-rating');
    ratingContainer.innerHTML = ''; // Clear any existing stars
    
    for (let i = 1; i <= 5; i++) {
        const star = document.createElement('span');
        star.className = `text-${i <= exampleFeedback.rating ? 'yellow-400' : 'gray-300'} text-lg`;
        star.innerHTML = '★';
        ratingContainer.appendChild(star);
    }
    
    // Show/hide response section
    const responseSection = document.getElementById('modal-response-content');
    if (exampleFeedback.hasResponse) {
        responseSection.innerHTML = `<p class="text-sm text-gray-700">${exampleFeedback.response}</p>`;
    } else {
        responseSection.innerHTML = '<p class="text-sm text-gray-500 italic">No response yet.</p>';
    }
    
    // Show the modal
    viewModal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    
    // Store the current feedback ID on the respond button for later use
    respondBtn.setAttribute('data-feedback-id', feedbackId);
    archiveBtn.setAttribute('data-feedback-id', feedbackId);
}

function closeViewModal() {
    viewModal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

function openResponseModal() {
    // Get the feedback ID from the respond button
    const feedbackId = respondBtn.getAttribute('data-feedback-id');
    
    // In a real app, you might want to pre-fill the response if one exists
    // For now, we'll just clear the textarea
    responseText.value = '';
    
    // Store the feedback ID on the submit button for later use
    submitResponseBtn.setAttribute('data-feedback-id', feedbackId);
    
    // Hide the view modal and show the response modal
    viewModal.classList.add('hidden');
    responseModal.classList.remove('hidden');
}

function closeResponseModal() {
    responseModal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    
    // Re-show the view modal if it was open before
    if (!viewModal.classList.contains('hidden')) {
        viewModal.classList.remove('hidden');
    }
}

function submitResponse() {
    const feedbackId = submitResponseBtn.getAttribute('data-feedback-id');
    const response = responseText.value.trim();
    
    if (!response) {
        alert('Please enter a response before submitting.');
        return;
    }
    
    // Here you would typically send the response to your server
    // For now, we'll just log it and show a success message
    console.log(`Submitting response for feedback ${feedbackId}:`, response);
    
    // Close the response modal
    closeResponseModal();
    
    // Show a success message (in a real app, you might want to update the UI to show the new response)
    alert('Your response has been submitted successfully!');
    
    // In a real app, you would refresh the feedback data or update the UI directly
    // to show the new response
}

function archiveFeedback() {
    const feedbackId = archiveBtn.getAttribute('data-feedback-id');
    
    // Ask for confirmation before archiving
    if (confirm('Are you sure you want to archive this feedback? This action cannot be undone.')) {
        // Here you would typically send a request to your server to archive the feedback
        console.log(`Archiving feedback ${feedbackId}`);
        
        // Close the modal
        closeViewModal();
        
        // Show a success message
        alert('Feedback has been archived.');
        
        // In a real app, you would refresh the feedback data or update the UI directly
        // to remove the archived feedback from the active list
    }
}

// Event listeners for modals
closeModalBtn.addEventListener('click', closeViewModal);
closeViewModalBtn.addEventListener('click', closeViewModal);
closeResponseModalBtn.addEventListener('click', closeResponseModal);
cancelResponseBtn.addEventListener('click', closeResponseModal);
respondBtn.addEventListener('click', openResponseModal);
submitResponseBtn.addEventListener('click', submitResponse);
archiveBtn.addEventListener('click', archiveFeedback);

// Close modals when clicking outside the content
window.addEventListener('click', (e) => {
    if (e.target === viewModal) {
        closeViewModal();
    } else if (e.target === responseModal) {
        closeResponseModal();
    }
});

// Close modals with Escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        if (!viewModal.classList.contains('hidden')) {
            closeViewModal();
        } else if (!responseModal.classList.contains('hidden')) {
            closeResponseModal();
        }
    }
});

// Example of how to programmatically open the modal for a specific feedback item
// This would typically be called when clicking on a feedback item in the list
function setupFeedbackItemClickHandlers() {
    // In a real app, you would add click handlers to each feedback item
    // For now, we'll just add a button to demonstrate the modal
    const demoButtons = document.querySelectorAll('.view-feedback-btn');
    demoButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const feedbackId = btn.getAttribute('data-feedback-id');
            openViewModal(feedbackId);
        });
    });
}

// Initialize the page
document.addEventListener('DOMContentLoaded', () => {
    // Set up any initial UI state
    switchTab('active');
    
    // Set up event listeners for any interactive elements
    setupFeedbackItemClickHandlers();
    
    // In a real app, you would load the initial feedback data here
    // loadFeedbackData('active');
    
    // Update the counts (these would come from your API in a real app)
    document.getElementById('active-count').textContent = '5';
    document.getElementById('archived-count').textContent = '2';
});
</script>

<!-- Include the admin footer -->
<?php include 'admin_footer.php'; ?>
