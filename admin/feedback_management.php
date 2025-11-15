<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 1) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check for session timeout (30 minutes)
$session_timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../Landing_Page/login.php?timeout=1");
    exit();
}

$_SESSION['last_activity'] = time();

// Prevent caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../db_connect.php';

// Get user's first name from database
$user_id = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, profile_picture FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$first_name = $row['first_name'];
$last_name = $row['last_name'];
$profile_picture = $row['profile_picture'] ? '../' . $row['profile_picture'] : '../default.png';

// --- REAL DATA FETCH FROM DATABASE ---
// Get overall statistics
$stats_query = "SELECT 
    COUNT(*) as total_submissions,
    AVG(rating) as overall_rating,
    SUM(CASE WHEN status = 'Show' THEN 1 ELSE 0 END) as visible_feedbacks
    FROM feedback_tb";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

$overall_rating = $stats['overall_rating'] ? round($stats['overall_rating'], 1) : 0;
$total_submissions = $stats['total_submissions'] ? $stats['total_submissions'] : 0;
$visible_feedbacks = $stats['visible_feedbacks'] ? $stats['visible_feedbacks'] : 0;

// Get count of currently visible feedbacks
$visible_count_query = "SELECT COUNT(*) as visible_count FROM feedback_tb WHERE status = 'Show'";
$visible_count_result = $conn->query($visible_count_query);
$visible_count_row = $visible_count_result->fetch_assoc();
$current_visible_count = $visible_count_row['visible_count'];

// Initial pagination setup for first load
$per_page = 5;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $per_page;
$total_pages = ceil($total_submissions / $per_page);

// Initial data fetch for first page load
$feedbacks_query = "SELECT 
    f.id,
    f.customer_id,
    f.service_id,
    f.service_type,
    f.rating,
    f.feedback_text,
    f.status,
    f.created_at,
    CONCAT(u.first_name, ' ', u.last_name) as customer_name
    FROM feedback_tb f
    INNER JOIN users u ON f.customer_id = u.id
    ORDER BY f.created_at DESC
    LIMIT ? OFFSET ?";
$stmt = $conn->prepare($feedbacks_query);
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$feedbacks_result = $stmt->get_result();
$feedbacks = [];
while ($row = $feedbacks_result->fetch_assoc()) {
    $feedbacks[] = $row;
}
$stmt = $conn->prepare($feedbacks_query);
$stmt->bind_param("ii", $per_page, $offset);
$stmt->execute();
$feedbacks_result = $stmt->get_result();
$feedbacks = [];
while ($row = $feedbacks_result->fetch_assoc()) {
    $feedbacks[] = $row;
}

// Handle AJAX pagination request
if (isset($_POST['ajax_pagination']) && $_POST['ajax_pagination'] == true) {
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $search_term = isset($_POST['search']) ? $_POST['search'] : '';
    $rating_filter = isset($_POST['rating']) ? $_POST['rating'] : 'all';
    
    $per_page = 5;
    $offset = ($page - 1) * $per_page;
    
    // Build query with filters
    $base_query = "FROM feedback_tb f INNER JOIN users u ON f.customer_id = u.id";
    $where_conditions = [];
    $params = [];
    $types = "";
    
    if (!empty($search_term)) {
        $where_conditions[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR f.feedback_text LIKE ?)";
        $search_param = "%$search_term%";
        $params = array_merge($params, [$search_param, $search_param, $search_param]);
        $types .= "sss";
    }
    
    if ($rating_filter !== 'all') {
        $where_conditions[] = "f.rating = ?";
        $params[] = $rating_filter;
        $types .= "i";
    }
    
    $where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";
    
    // Get total count
    $count_query = "SELECT COUNT(*) as total $base_query $where_clause";
    if ($where_conditions) {
        $count_stmt = $conn->prepare($count_query);
        $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
    } else {
        $count_result = $conn->query($count_query);
    }
    $total_with_filters = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_with_filters / $per_page);
    
    // Get current visible counts for both service groups
    $traditional_visible_query = "SELECT COUNT(*) as visible_count FROM feedback_tb WHERE status = 'Show' AND service_type IN ('traditional-funeral', 'custom-package')";
    $life_plan_visible_query = "SELECT COUNT(*) as visible_count FROM feedback_tb WHERE status = 'Show' AND service_type = 'life-plan'";

    $traditional_visible_result = $conn->query($traditional_visible_query);
    $life_plan_visible_result = $conn->query($life_plan_visible_query);

    $traditional_visible_count = $traditional_visible_result->fetch_assoc()['visible_count'];
    $life_plan_visible_count = $life_plan_visible_result->fetch_assoc()['visible_count'];
    
    // Get paginated results
    $feedbacks_query = "SELECT 
        f.id,
        f.customer_id,
        f.service_id,
        f.service_type,
        f.rating,
        f.feedback_text,
        f.status,
        f.created_at,
        CONCAT(u.first_name, ' ', u.last_name) as customer_name
        $base_query 
        $where_clause 
        ORDER BY f.created_at DESC 
        LIMIT ? OFFSET ?";
    
    $params[] = $per_page;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($feedbacks_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $feedbacks_result = $stmt->get_result();
    $feedbacks = [];
    while ($row = $feedbacks_result->fetch_assoc()) {
        $feedbacks[] = $row;
    }
    
    // Return JSON response for AJAX
    ob_start();
    ?>
    <?php if (count($feedbacks) > 0): ?>
        <?php foreach ($feedbacks as $feedback): ?>
            <?php
            $isVisible = $feedback['status'] === 'Show' ? 'checked' : '';
            $created_date = date('F j Y', strtotime($feedback['created_at']));
            $service_type = $feedback['service_type'];
            // Format service type for display
            $service_type_display = ucwords(str_replace('-', ' ', $service_type));
            $isDisabled = ($current_visible_count >= 2 && $feedback['status'] !== 'Show') ? 'disabled' : '';
            ?>
            <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors" data-rating="<?php echo $feedback['rating']; ?>">
                <td class="px-4 py-3.5 text-sm font-medium text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($feedback['customer_name']); ?></td>
                <td class="px-4 py-3.5 text-sm text-gray-700 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        <?php echo htmlspecialchars($service_type_display); ?>
                    </span>
                </td>
                <td class="px-4 py-3.5 text-sm text-yellow-600 whitespace-nowrap">
                    <?php echo getStarRatingHtml($feedback['rating']); ?> (<?php echo number_format($feedback['rating'], 1); ?>)
                </td>
                <td class="px-4 py-3.5 text-sm text-gray-700 max-w-[150px] truncate" title="<?php echo htmlspecialchars($feedback['feedback_text']); ?>">
                    <?php echo htmlspecialchars($feedback['feedback_text']); ?>
                </td>
                <td class="px-4 py-3.5 text-sm text-gray-500 whitespace-nowrap"><?php echo $created_date; ?></td>
                <td class="px-4 py-3.5 text-center">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" value="" class="sr-only peer toggle-checkbox" 
                               data-id="<?php echo $feedback['id']; ?>" 
                               <?php echo $isVisible; ?> 
                               <?php echo $isDisabled; ?>>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-sidebar-accent rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all <?php echo $isDisabled ? 'opacity-50 cursor-not-allowed' : ''; ?>"></div>
                    </label>
                </td>
                <td class="px-4 py-3.5 text-center">
                    <button class="p-1.5 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip view-feedback-btn" 
                            title="View Full Content"
                            data-customer="<?php echo htmlspecialchars($feedback['customer_name']); ?>"
                            data-service="<?php echo htmlspecialchars($service_type_display); ?>"
                            data-rating="<?php echo $feedback['rating']; ?>"
                            data-content="<?php echo htmlspecialchars($feedback['feedback_text']); ?>"
                            data-date="<?php echo $created_date; ?>"
                            data-visible="<?php echo $feedback['status'] === 'Show' ? 1 : 0; ?>"
                            data-id="<?php echo $feedback['id']; ?>">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                No feedback submissions found.
            </td>
        </tr>
    <?php endif; ?>
    <?php
    $table_content = ob_get_clean();
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'table_content' => $table_content,
        'pagination_info' => 'Showing ' . ($offset + 1) . ' - ' . min($offset + $per_page, $total_with_filters) . ' of ' . number_format($total_with_filters) . ' feedbacks',
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_items' => $total_with_filters,
        'traditional_visible_count' => $traditional_visible_count,
        'life_plan_visible_count' => $life_plan_visible_count
    ]);
    exit();
}

// Get count of currently visible feedbacks
$visible_count_query = "SELECT COUNT(*) as visible_count FROM feedback_tb WHERE status = 'Show'";
$visible_count_result = $conn->query($visible_count_query);
$visible_count_row = $visible_count_result->fetch_assoc();
$current_visible_count = $visible_count_row['visible_count'];

// Mock function to generate star HTML
function getStarRatingHtml($rating) {
    $html = '';
    $rating = round($rating * 2) / 2; // Round to nearest half
    $fullStars = floor($rating);
    $hasHalfStar = ($rating - $fullStars) >= 0.5;

    for ($i = 0; $i < $fullStars; $i++) {
        $html .= '<i class="fas fa-star"></i>';
    }
    if ($hasHalfStar) {
        $html .= '<i class="fas fa-star-half-alt"></i>';
    }
    for ($i = 0; $i < 5 - ceil($rating); $i++) {
        $html .= '<i class="far fa-star"></i>';
    }
    return $html;
}
// --- End Real Data Fetch ---
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Feedback Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="tailwind.js"></script>
    <!-- Add SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style type="text/css">
        /* Custom scrollbar for better visual appeal */
        .scrollbar-thin::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #D3D8E1;
            border-radius: 10px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #A0AEC0;
        }

        /* MODAL STYLES COPIED FROM ACCOUNT_MANAGEMENT.PHP FOR CONSISTENCY */
        .modal-scroll-container {
            scrollbar-width: thin;
            scrollbar-color: #d4a933 #f5f5f5; /* Gold scrollbar */
        }

        .modal-scroll-container::-webkit-scrollbar {
            width: 8px;
        }

        .modal-scroll-container::-webkit-scrollbar-track {
            background: #f5f5f5;
        }

        .modal-scroll-container::-webkit-scrollbar-thumb {
            background-color: #d4a933;
            border-radius: 6px;
        }
        /* End MODAL STYLES */

        /* Toggle Switch Styling */
        /* Adopted gold/accent color from id_confirmation context */
        .toggle-checkbox:checked {
            background-color: #CA8A04; /* sidebar-accent color */
        }
        .toggle-checkbox:checked + .toggle-label {
            transform: translateX(100%);
        }

        /* Custom styles from id_confirmation.php for consistency */
        body {
            /* Keep Inter font, which is Tailwind default */
            font-family: 'Inter', sans-serif; 
        }
        .shadow-sidebar {
            /* Replaced with a generic but strong shadow for consistency */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        .shadow-card {
            /* Adopting the smaller, sharper shadow from id_confirmation's cards */
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }
        .tooltip {
            position: relative;
        }
        .tooltip:hover::after {
            content: attr(title);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background: #333;
            color: #fff;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            white-space: nowrap;
            z-index: 10;
            margin-bottom: 5px;
        }
        /* Color Palette (Implicitly from id_confirmation.php/tailwind.js) */
        /* sidebar-accent: #CA8A04 (Gold/Dark Yellow) */
        /* sidebar-text: #1F2937 (Dark Gray) */
        /* sidebar-border: #E5E7EB (Light Gray) */
        /* sidebar-hover: #F9FAFB (Very Light Gray) */
        
        /* Disabled toggle style */
        .toggle-checkbox:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .toggle-checkbox:disabled + div {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body class="flex bg-gray-50">

<?php include 'admin_sidebar.php'; ?>

<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
    
    <div class="flex justify-between items-center mb-6 bg-white p-5 rounded-lg shadow-sidebar border border-gray-200">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Customer Feedback Management</h1>
            <p class="text-sm text-gray-500 mt-1">Review and manage which customer ratings are shown on the landing page.</p>
        </div>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">

    <!-- Card 1 -->
    <div class="bg-white rounded-xl shadow-card hover:shadow-md transition-all duration-300 overflow-hidden 
                col-span-1 lg:col-span-2 h-full flex flex-col">
        <div class="bg-gradient-to-r from-yellow-100 to-yellow-200 px-6 py-4 flex-grow">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Overall Average Rating</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-yellow-600 flex items-center justify-center">
                    <i class="fas fa-star"></i>
                </div>
            </div>
            <div class="flex items-end mt-2">
                <span class="text-4xl font-extrabold text-gray-800"><?php echo number_format($overall_rating, 1); ?></span>
                <span class="ml-2 text-xl font-semibold text-gray-600">/ 5.0</span>
            </div>
        </div>
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <div class="flex items-center text-gray-500 justify-between">
                <div class="text-xl text-yellow-600">
                    <?php echo getStarRatingHtml($overall_rating); ?>
                </div>
                <span class="text-xs">
                    Based on <strong><?php echo number_format($total_submissions); ?></strong> total submissions.
                </span>
            </div>
        </div>
    </div>

    <!-- Card 2 -->
    <div class="bg-white rounded-xl shadow-card hover:shadow-md transition-all duration-300 overflow-hidden 
                col-span-1 lg:col-span-2 h-full flex flex-col">
        <div class="bg-gradient-to-r from-blue-100 to-blue-200 px-6 py-4 flex-grow">
            <div class="flex items-center justify-between mb-1">
                <h3 class="text-sm font-medium text-gray-700">Total Feedbacks</h3>
                <div class="w-10 h-10 rounded-full bg-white/90 text-blue-600 flex items-center justify-center">
                    <i class="fas fa-comment-dots"></i>
                </div>
            </div>
            <div class="flex items-end">
                <span class="text-3xl font-bold text-gray-800"><?php echo number_format($total_submissions); ?></span>
            </div>
        </div>
        <div class="px-6 py-3 bg-white border-t border-gray-100">
            <span class="text-xs text-gray-500">All-time submissions</span>
        </div>
    </div>

</div>

    
    <div class="bg-white rounded-lg shadow-md mb-8 border border-sidebar-border overflow-hidden">
        <div class="bg-sidebar-hover p-4 border-b border-sidebar-border">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3 mb-4 lg:mb-0">
                    <h4 class="text-lg font-bold text-gray-800 whitespace-nowrap">All Customer Feedbacks</h4>
                    <span class="bg-sidebar-accent bg-opacity-10 text-sidebar-accent px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                        <?php echo number_format($total_submissions); ?> Total
                    </span>
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                        <i class="fas fa-dove"></i> <?php echo $traditional_visible_count; ?>/2 Traditional+Custom
                    </span>
                    <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded-full text-xs font-medium flex items-center gap-1">
                        <i class="fas fa-seedling"></i> <?php echo $life_plan_visible_count; ?>/2 Life Plan
                    </span>
                </div>
                
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                    <select id="ratingFilter" class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent w-full sm:w-auto">
                        <option value="all">Filter: All Ratings</option>
                        <option value="5">5 Stars</option>
                        <option value="4">4 Stars</option>
                        <option value="3">3 Stars</option>
                        <option value="2">2 Stars</option>
                        <option value="1">1 Star</option>
                    </select>
        
                    <div class="relative w-full sm:w-auto">
                        <input type="text" id="feedbackSearchInput" 
                                placeholder="Search feedback or customer..." 
                                class="pl-8 pr-3 py-2 w-full border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                        <i class="fas fa-search absolute left-2.5 top-3 text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto scrollbar-thin">
            <div class="min-w-full">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-sidebar-border">
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-gray-700 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-user text-sidebar-accent"></i> Customer
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-gray-700 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-dove text-sidebar-accent"></i> Service Type
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-gray-700 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-star text-sidebar-accent"></i> Rating
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-gray-700">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-comment text-sidebar-accent"></i> Feedback Content
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-left text-sm font-medium text-gray-700 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <i class="fas fa-calendar-alt text-sidebar-accent"></i> Date
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-center text-sm font-medium text-gray-700 whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <i class="fas fa-toggle-on text-sidebar-accent"></i> Show on Landing
                                </div>
                            </th>
                            <th class="px-4 py-3.5 text-center text-sm font-medium text-gray-700 whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <i class="fas fa-cogs text-sidebar-accent"></i> Actions
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody id="feedbackTableBody">
                        <?php if (count($feedbacks) > 0): ?>
                            <?php foreach ($feedbacks as $feedback): ?>
                                <?php
                                $isVisible = $feedback['status'] === 'Show' ? 'checked' : '';
                                $created_date = date('F j Y', strtotime($feedback['created_at']));
                                $service_type = $feedback['service_type'];
                                // Format service type for display
                                $service_type_display = ucwords(str_replace('-', ' ', $service_type));
                                ?>
                                <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors" data-rating="<?php echo $feedback['rating']; ?>">
                                    <td class="px-4 py-3.5 text-sm font-medium text-gray-800 whitespace-nowrap"><?php echo htmlspecialchars($feedback['customer_name']); ?></td>
                                    <td class="px-4 py-3.5 text-sm text-gray-700 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo htmlspecialchars($service_type_display); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-sm text-yellow-600 whitespace-nowrap">
                                        <?php echo getStarRatingHtml($feedback['rating']); ?> (<?php echo number_format($feedback['rating'], 1); ?>)
                                    </td>
                                    <td class="px-4 py-3.5 text-sm text-gray-700 max-w-[150px] truncate" title="<?php echo htmlspecialchars($feedback['feedback_text']); ?>">
                                        <?php echo htmlspecialchars($feedback['feedback_text']); ?>
                                    </td>
                                    <td class="px-4 py-3.5 text-sm text-gray-500 whitespace-nowrap"><?php echo $created_date; ?></td>
                                    <td class="px-4 py-3.5 text-center">
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" value="" class="sr-only peer toggle-checkbox" 
                                                   data-id="<?php echo $feedback['id']; ?>" 
                                                   <?php echo $isVisible; ?>>
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-sidebar-accent rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                        </label>
                                    </td>
                                    <td class="px-4 py-3.5 text-center">
                                        <button class="p-1.5 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip view-feedback-btn" 
                                                title="View Full Content"
                                                data-customer="<?php echo htmlspecialchars($feedback['customer_name']); ?>"
                                                data-service="<?php echo htmlspecialchars($service_type_display); ?>"
                                                data-rating="<?php echo $feedback['rating']; ?>"
                                                data-content="<?php echo htmlspecialchars($feedback['feedback_text']); ?>"
                                                data-date="<?php echo $created_date; ?>"
                                                data-visible="<?php echo $feedback['status'] === 'Show' ? 1 : 0; ?>"
                                                data-id="<?php echo $feedback['id']; ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                                    No feedback submissions found.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
    <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
        Showing <strong><?php echo ($offset + 1); ?> - <?php echo min($offset + $per_page, $total_submissions); ?></strong> of <strong><?php echo number_format($total_submissions); ?></strong> feedbacks
    </div>
    <div id="paginationContainer" class="flex space-x-2">
        <!-- First Page -->
        <button data-page="1" class="pagination-btn px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $current_page == 1 ? 'opacity-50 pointer-events-none' : ''; ?>">
            &laquo;
        </button>
        
        <!-- Previous Page -->
        <button data-page="<?php echo $current_page - 1; ?>" class="pagination-btn px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $current_page == 1 ? 'opacity-50 pointer-events-none' : ''; ?>">
            &lsaquo;
        </button>

        <!-- Page Numbers -->
        <?php
        $start_page = max(1, $current_page - 2);
        $end_page = min($total_pages, $current_page + 2);
        
        for ($i = $start_page; $i <= $end_page; $i++): 
        ?>
            <button data-page="<?php echo $i; ?>" class="pagination-btn px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $i == $current_page ? 'bg-sidebar-accent text-white border-sidebar-accent' : ''; ?>">
                <?php echo $i; ?>
            </button>
        <?php endfor; ?>

        <!-- Next Page -->
        <button data-page="<?php echo $current_page + 1; ?>" class="pagination-btn px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $current_page == $total_pages ? 'opacity-50 pointer-events-none' : ''; ?>">
            &rsaquo;
        </button>
        
        <!-- Last Page -->
        <button data-page="<?php echo $total_pages; ?>" class="pagination-btn px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover <?php echo $current_page == $total_pages ? 'opacity-50 pointer-events-none' : ''; ?>">
            &raquo;
        </button>
    </div>
</div>
    </div>
    
    <footer class="bg-white rounded-lg shadow-sidebar border border-gray-200 p-4 text-center text-sm text-gray-500 mt-8">
        <p>© 2025 GrievEase.</p>
    </footer>
</div>

<div id="viewFeedbackModal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
  <div class="fixed inset-0 bg-black bg-opacity-50 backdrop-blur-sm" onclick="closeFeedbackModal()"></div>
  
  <div class="relative bg-white rounded-xl shadow-card w-full max-w-xl mx-4 sm:mx-auto z-10 transform transition-all duration-300 max-h-[90vh] flex flex-col">
    <button type="button" class="absolute top-4 right-4 text-white hover:text-sidebar-accent transition-colors" onclick="closeFeedbackModal()">
      <i class="fas fa-times text-xl"></i>
    </button>
    
    <div class="px-4 sm:px-6 py-4 sm:py-5 border-b bg-gradient-to-r from-sidebar-accent to-darkgold border-gray-200">
      <h3 class="text-lg sm:text-xl font-bold text-white flex items-center">
        Customer Feedback
      </h3>
    </div>
    
    <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto modal-scroll-container space-y-4">
        <p class="text-sm font-medium text-gray-700">Customer: <span class="ml-2 font-semibold text-gray-800" id="modalCustomerName"></span></p>
        
        <p class="text-sm font-medium text-gray-700">Service Type: <span class="ml-2 font-semibold text-gray-800" id="modalServiceType"></span></p>
        
        <p class="text-sm font-medium text-gray-700 flex items-center">Rating: 
            <span class="ml-2 text-lg text-yellow-600" id="modalRatingStars"></span> 
            <span class="ml-2 text-sm text-gray-500" id="modalRatingText"></span>
        </p>
        
        <p class="text-sm font-medium text-gray-700">Submitted On: <span class="ml-2 font-semibold text-gray-800" id="modalSubmissionDate"></span></p>

        <p class="text-base font-semibold text-gray-800 pt-2">Feedback Details:</p>
        
        <div id="modalContent" class="mt-1 p-3 bg-gray-50 rounded-lg border border-gray-200 text-gray-700 whitespace-pre-wrap">
            </div>
    </div>
    
    <div class="px-4 sm:px-6 py-3 sm:py-4 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
        <div class="flex items-center space-x-2">
            <span class="text-sm text-gray-600 font-medium">Toggle Visibility:</span>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" id="modalVisibilityToggle" class="sr-only peer toggle-checkbox">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-sidebar-accent rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                <span class="ml-3 text-sm font-medium text-gray-900" id="modalVisibilityStatus"></span>
            </label>
        </div>
        <button onclick="closeFeedbackModal()" class="w-full sm:w-auto px-4 sm:px-5 py-2 text-sm font-medium text-sidebar-text bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors shadow-sm">
            Close
        </button>
    </div>
  </div>
</div>

<!-- Add SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<script>
    // Utility function to generate star HTML
    function getStarRatingHtml(rating) {
        let html = '';
        let roundedRating = Math.round(rating * 2) / 2;
        let fullStars = Math.floor(roundedRating);
        let hasHalfStar = (roundedRating - fullStars) >= 0.5;

        for (let i = 0; i < fullStars; i++) {
            html += '<i class="fas fa-star"></i>';
        }
        if (hasHalfStar) {
            html += '<i class="fas fa-star-half-alt"></i>';
        }
        for (let i = 0; i < 5 - Math.ceil(roundedRating); i++) {
            html += '<i class="far fa-star"></i>';
        }
        return html;
    }

    /**
     * Opens the view feedback modal with populated data.
     * Arguments: customerName, rating (float), content (string), date (string), isVisible (0 or 1), feedbackId (int)
     */
    function openFeedbackModal(customerName, serviceType, rating, content, date, isVisible, feedbackId) {
        const modal = document.getElementById('viewFeedbackModal');
        const toggle = document.getElementById('modalVisibilityToggle');
        const statusText = document.getElementById('modalVisibilityStatus');
    
        // Populate content
        document.getElementById('modalCustomerName').textContent = customerName;
        document.getElementById('modalServiceType').textContent = serviceType;
        document.getElementById('modalRatingStars').innerHTML = getStarRatingHtml(rating);
        document.getElementById('modalRatingText').textContent = `(${rating.toFixed(1)})`;
        document.getElementById('modalContent').textContent = content;
        document.getElementById('modalSubmissionDate').textContent = date;
        
        // Handle visibility toggle
        toggle.checked = isVisible === 1;
        toggle.setAttribute('data-feedback-id', feedbackId);
        statusText.textContent = isVisible === 1 ? 'Visible' : 'Hidden';
    
        // Display modal
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
    
    // Add listener for the modal visibility toggle
    document.getElementById('modalVisibilityToggle')?.addEventListener('change', function() {
        const feedbackId = this.getAttribute('data-feedback-id');
        const newStatus = this.checked ? 1 : 0;
        const statusText = document.getElementById('modalVisibilityStatus');
        
        // Check if trying to enable when max is reached
        if (newStatus === 1 && checkMaxVisibleReached() && !this.checked) {
            this.checked = false;
            showMaxReachedAlert();
            return;
        }
        
        statusText.textContent = newStatus === 1 ? 'Visible' : 'Hidden';
        
        // AJAX call to update the database visibility status
        const formData = new FormData();
        formData.append('feedback_id', feedbackId);
        formData.append('is_visible', newStatus);
        
        fetch('update_feedback_visibility.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Visibility updated successfully');
                
                // Reload the current page to get updated data from server
                const currentPageBtn = document.querySelector('.pagination-btn.bg-sidebar-accent');
                const currentPage = currentPageBtn ? parseInt(currentPageBtn.getAttribute('data-page')) : 1;
                loadPage(currentPage);
                
                // Close the modal after successful update
                closeFeedbackModal();
                
            } else {
                console.error('Failed to update visibility:', data.message);
                
                // If it's a max reached error, show the alert
                if (data.max_reached) {
                    showMaxReachedAlert();
                }
                
                // Revert the toggle if update failed
                this.checked = !this.checked;
                statusText.textContent = this.checked ? 'Visible' : 'Hidden';
            }
        })
        .catch(error => {
            console.error('Error updating visibility:', error);
            // Revert the toggle if update failed
            this.checked = !this.checked;
            statusText.textContent = this.checked ? 'Visible' : 'Hidden';
        });
    });

    // Function to close the view feedback modal
    window.closeFeedbackModal = function() {
        const modal = document.getElementById('viewFeedbackModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

// Check if maximum visible feedbacks reached for specific service type
function checkMaxVisibleReached(serviceType) {
    // Get the current counts from the server data (stored in global variables)
    let traditionalCount = window.currentTraditionalCount || 0;
    let lifePlanCount = window.currentLifePlanCount || 0;
    
    console.log(`Checking max for ${serviceType} - Traditional: ${traditionalCount}, Life Plan: ${lifePlanCount}`);
    
    if (serviceType === 'life-plan') {
        return lifePlanCount >= 2;
    } else {
        return traditionalCount >= 2;
    }
}

// Get service type from service text
function getServiceTypeFromText(serviceText) {
    if (serviceText === 'Life Plan') {
        return 'life-plan';
    } else if (serviceText === 'Traditional Funeral' || serviceText === 'Custom Package') {
        return 'traditional';
    }
    return '';
}

// Update toggle states based on current visible count
function updateToggleStates() {
    // Get the current counts from the server data
    let traditionalCount = window.currentTraditionalCount || 0;
    let lifePlanCount = window.currentLifePlanCount || 0;
    
    console.log(`Updating toggles - Traditional: ${traditionalCount}, Life Plan: ${lifePlanCount}`);
    
    // Update traditional/custom toggles
    document.querySelectorAll('tr[data-rating]').forEach(row => {
        const serviceTypeCell = row.querySelector('td:nth-child(2) span');
        if (serviceTypeCell) {
            const serviceText = serviceTypeCell.textContent.trim();
            if (serviceText === 'Traditional Funeral' || serviceText === 'Custom Package') {
                const toggle = row.querySelector('.toggle-checkbox');
                if (toggle && !toggle.checked) {
                    if (traditionalCount >= 2) {
                        toggle.disabled = true;
                        if (toggle.nextElementSibling) {
                            toggle.nextElementSibling.classList.add('opacity-50', 'cursor-not-allowed');
                        }
                    } else {
                        toggle.disabled = false;
                        if (toggle.nextElementSibling) {
                            toggle.nextElementSibling.classList.remove('opacity-50', 'cursor-not-allowed');
                        }
                    }
                }
            }
        }
    });
    
    // Update life-plan toggles
    document.querySelectorAll('tr[data-rating]').forEach(row => {
        const serviceTypeCell = row.querySelector('td:nth-child(2) span');
        if (serviceTypeCell) {
            const serviceText = serviceTypeCell.textContent.trim();
            if (serviceText === 'Life Plan') {
                const toggle = row.querySelector('.toggle-checkbox');
                if (toggle && !toggle.checked) {
                    if (lifePlanCount >= 2) {
                        toggle.disabled = true;
                        if (toggle.nextElementSibling) {
                            toggle.nextElementSibling.classList.add('opacity-50', 'cursor-not-allowed');
                        }
                    } else {
                        toggle.disabled = false;
                        if (toggle.nextElementSibling) {
                            toggle.nextElementSibling.classList.remove('opacity-50', 'cursor-not-allowed');
                        }
                    }
                }
            }
        }
    });
}


function updateVisibleCountDisplay() {
    let traditionalCount = window.currentTraditionalCount || 0;
    let lifePlanCount = window.currentLifePlanCount || 0;
    
    // Update traditional count display
    const traditionalCountElement = document.querySelector('.bg-green-100');
    if (traditionalCountElement) {
        traditionalCountElement.innerHTML = `<i class="fas fa-dove"></i> ${traditionalCount}/2 Traditional+Custom`;
    }
    
    // Update life plan count display
    const lifePlanCountElement = document.querySelector('.bg-purple-100');
    if (lifePlanCountElement) {
        lifePlanCountElement.innerHTML = `<i class="fas fa-seedling"></i> ${lifePlanCount}/2 Life Plan`;
    }
}

// Show maximum reached alert for specific service group
function showMaxReachedAlert(serviceGroup) {
    let serviceName = serviceGroup === 'life-plan' ? 'Life Plan' : 'Traditional/Custom';
    
    Swal.fire({
        icon: 'warning',
        title: 'Maximum Reached',
        html: `You can only show <strong>2 feedbacks</strong> for ${serviceName} services.<br><br>Please hide another ${serviceName} feedback first to show this one.`,
        confirmButtonColor: '#CA8A04',
        confirmButtonText: 'OK',
        backdrop: true,
        allowOutsideClick: true,
        allowEscapeKey: true
    });
}

    // --- Dynamic Content Setup and Event Listeners ---
    document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle
    const mobileHamburger = document.getElementById('mobile-hamburger');
    const sidebar = document.getElementById('sidebar');
    
    if (mobileHamburger && sidebar) {
        mobileHamburger.addEventListener('click', function() {
            sidebar.classList.toggle('-translate-x-full');
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('viewFeedbackModal');
        if (event.target === modal) {
            closeFeedbackModal();
        }
    });
    
    // Handle escape key to close modal
    document.addEventListener('keydown', function(event) {
        if (event.key === "Escape") {
            closeFeedbackModal();
        }
    });
    
    // Search functionality
    const searchInput = document.getElementById('feedbackSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            // Load first page with current filters
            loadPage(1);
        });
    }
    
    // Rating filter functionality
    const ratingFilter = document.getElementById('ratingFilter');
    if (ratingFilter) {
        ratingFilter.addEventListener('change', function() {
            // Load first page with current filters
            loadPage(1);
        });
    }
    
    // Initial attachment of event listeners to existing elements
    attachEventListeners();
    
    // Initialize toggle states
    updateToggleStates();
    
    // Initialize pagination listeners
    attachPaginationListeners();
});
    
// AJAX Pagination function
function loadPage(page) {
    const searchValue = document.getElementById('feedbackSearchInput').value;
    const ratingValue = document.getElementById('ratingFilter').value;
    
    // Show loading state
    const tableBody = document.getElementById('feedbackTableBody');
    tableBody.innerHTML = `
        <tr>
            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                <div class="flex justify-center items-center">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-sidebar-accent"></div>
                    <span class="ml-2">Loading...</span>
                </div>
            </td>
        </tr>
    `;
    
    // Disable pagination buttons during load
    document.querySelectorAll('.pagination-btn').forEach(btn => {
        btn.disabled = true;
    });
    
    const formData = new FormData();
    formData.append('ajax_pagination', true);
    formData.append('page', page);
    formData.append('search', searchValue);
    formData.append('rating', ratingValue);
    
    fetch('feedback_management.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update table content
            tableBody.innerHTML = data.table_content;
            
            // Update pagination info
            document.getElementById('paginationInfo').innerHTML = data.pagination_info;
            
            // Update pagination buttons
            updatePaginationButtons(data.current_page, data.total_pages);
            
            // Store the current visible counts from server in global variables
            if (data.traditional_visible_count !== undefined && data.life_plan_visible_count !== undefined) {
                window.currentTraditionalCount = data.traditional_visible_count;
                window.currentLifePlanCount = data.life_plan_visible_count;
                
                const traditionalCountElement = document.querySelector('.bg-green-100');
                const lifePlanCountElement = document.querySelector('.bg-purple-100');
                
                if (traditionalCountElement) {
                    traditionalCountElement.innerHTML = `<i class="fas fa-dove"></i> ${data.traditional_visible_count}/2 Traditional+Custom`;
                }
                if (lifePlanCountElement) {
                    lifePlanCountElement.innerHTML = `<i class="fas fa-seedling"></i> ${data.life_plan_visible_count}/2 Life Plan`;
                }
                
                console.log(`Server counts stored - Traditional: ${data.traditional_visible_count}, Life Plan: ${data.life_plan_visible_count}`);
            }
            
            // Re-attach event listeners to new elements
            attachEventListeners();
            
            // Update toggle states based on server counts
            updateToggleStates();
            
            console.log('Page loaded successfully');
        } else {
            console.error('Failed to load page:', data.message);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                        Error loading data. Please try again.
                    </td>
                </tr>
            `;
        }
    })
    .catch(error => {
        console.error('Error loading page:', error);
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                    Error loading data. Please try again.
                </td>
            </tr>
        `;
    })
    .finally(() => {
        // Re-enable pagination buttons
        document.querySelectorAll('.pagination-btn').forEach(btn => {
            btn.disabled = false;
        });
    });
}

// Update pagination buttons
function updatePaginationButtons(currentPage, totalPages) {
    const paginationContainer = document.getElementById('paginationContainer');
    let paginationHTML = '';
    
    // First Page
    paginationHTML += `
        <button data-page="1" class="pagination-btn px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${currentPage == 1 ? 'opacity-50 pointer-events-none' : ''}">
            &laquo;
        </button>
    `;
    
    // Previous Page
    paginationHTML += `
        <button data-page="${currentPage - 1}" class="pagination-btn px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${currentPage == 1 ? 'opacity-50 pointer-events-none' : ''}">
            &lsaquo;
        </button>
    `;
    
    // Page Numbers
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);
    
    for (let i = startPage; i <= endPage; i++) {
        paginationHTML += `
            <button data-page="${i}" class="pagination-btn px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${i == currentPage ? 'bg-sidebar-accent text-white border-sidebar-accent' : ''}">
                ${i}
            </button>
        `;
    }
    
    // Next Page
    paginationHTML += `
        <button data-page="${currentPage + 1}" class="pagination-btn px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${currentPage == totalPages ? 'opacity-50 pointer-events-none' : ''}">
            &rsaquo;
        </button>
    `;
    
    // Last Page
    paginationHTML += `
        <button data-page="${totalPages}" class="pagination-btn px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover ${currentPage == totalPages ? 'opacity-50 pointer-events-none' : ''}">
            &raquo;
        </button>
    `;
    
    paginationContainer.innerHTML = paginationHTML;
    
    // Re-attach event listeners to new pagination buttons
    attachPaginationListeners();
}

// Attach event listeners to pagination buttons
function attachPaginationListeners() {
    document.querySelectorAll('.pagination-btn').forEach(button => {
        button.addEventListener('click', function() {
            const page = parseInt(this.getAttribute('data-page'));
            if (page && !this.disabled) {
                loadPage(page);
            }
        });
    });
}

// Re-attach all event listeners after AJAX load
function attachEventListeners() {
    // Attach view feedback button listeners
    document.querySelectorAll('.view-feedback-btn').forEach(button => {
        button.addEventListener('click', function() {
            const customerName = this.getAttribute('data-customer');
            const serviceType = this.getAttribute('data-service');
            const rating = parseFloat(this.getAttribute('data-rating'));
            const content = this.getAttribute('data-content');
            const date = this.getAttribute('data-date');
            const isVisible = parseInt(this.getAttribute('data-visible'));
            const feedbackId = parseInt(this.getAttribute('data-id'));
            
            openFeedbackModal(customerName, serviceType, rating, content, date, isVisible, feedbackId);
        });
    });
    
// Attach toggle listeners
document.querySelectorAll('input.toggle-checkbox[data-id]').forEach(toggle => {
    toggle.addEventListener('change', function() {
        const feedbackId = this.getAttribute('data-id');
        const newStatus = this.checked ? 1 : 0;
        
        // Get the service type from the table row
        const row = this.closest('tr');
        const serviceTypeCell = row.querySelector('td:nth-child(2) span');
        let serviceType = '';
        
        if (serviceTypeCell) {
            const serviceText = serviceTypeCell.textContent.trim();
            serviceType = getServiceTypeFromText(serviceText);
        }
        
        console.log(`Toggle change - Service: ${serviceType}, New Status: ${newStatus}`);
        
        // Check if trying to enable when max is reached for this service type
        if (newStatus === 1 && serviceType && checkMaxVisibleReached(serviceType)) {
            console.log(`Max reached for ${serviceType}, reverting toggle`);
            // Show SweetAlert and revert the toggle
            this.checked = false;
            showMaxReachedAlert(serviceType);
            return;
        }
        
        // AJAX call to update the database visibility status
        const formData = new FormData();
        formData.append('feedback_id', feedbackId);
        formData.append('is_visible', newStatus);
        
        fetch('update_feedback_visibility.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Visibility updated successfully');
                console.log('Server response:', data);
                
                // Store the updated counts from server
                if (data.traditional_visible_count !== undefined && data.life_plan_visible_count !== undefined) {
                    window.currentTraditionalCount = data.traditional_visible_count;
                    window.currentLifePlanCount = data.life_plan_visible_count;
                    
                    const traditionalCountElement = document.querySelector('.bg-green-100');
                    const lifePlanCountElement = document.querySelector('.bg-purple-100');
                    
                    if (traditionalCountElement) {
                        traditionalCountElement.innerHTML = `<i class="fas fa-dove"></i> ${data.traditional_visible_count}/2 Traditional+Custom`;
                    }
                    if (lifePlanCountElement) {
                        lifePlanCountElement.innerHTML = `<i class="fas fa-seedling"></i> ${data.life_plan_visible_count}/2 Life Plan`;
                    }
                }
                
                // Reload the current page to get updated data from server
                const currentPageBtn = document.querySelector('.pagination-btn.bg-sidebar-accent');
                const currentPage = currentPageBtn ? parseInt(currentPageBtn.getAttribute('data-page')) : 1;
                loadPage(currentPage);
                
            } else {
                console.error('Failed to update visibility:', data.message);
                
                // If it's a max reached error, show the alert
                if (data.max_reached) {
                    showMaxReachedAlert(data.service_group);
                }
                
                // Revert the toggle if update failed
                this.checked = !this.checked;
                updateToggleStates();
            }
        })
        .catch(error => {
            console.error('Error updating visibility:', error);
            // Revert the toggle if update failed
            this.checked = !this.checked;
            updateToggleStates();
        });
    });
});
    
    // Attach pagination listeners
    attachPaginationListeners();
}
</script>

</body>
</html>