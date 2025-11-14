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

// --- Placeholder/Mock Data Fetch (Replace with actual database query for real data) ---
$overall_rating = 4.7;
$total_submissions = 342;
$visible_feedbacks = 12;

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
// --- End Placeholder/Mock Data Fetch ---
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
        
        <div class="bg-white rounded-xl shadow-card hover:shadow-md transition-all duration-300 overflow-hidden col-span-1 lg:col-span-2">
            <div class="bg-gradient-to-r from-yellow-100 to-yellow-200 px-6 py-4">
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
                    <span class="text-xs">Based on <strong><?php echo number_format($total_submissions); ?></strong> total submissions.</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-card hover:shadow-md transition-all duration-300 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-100 to-blue-200 px-6 py-4">
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

        <div class="bg-white rounded-xl shadow-card hover:shadow-md transition-all duration-300 overflow-hidden">
            <div class="bg-gradient-to-r from-green-100 to-green-200 px-6 py-4">
                <div class="flex items-center justify-between mb-1">
                    <h3 class="text-sm font-medium text-gray-700">Visible on Landing Page</h3>
                    <div class="w-10 h-10 rounded-full bg-white/90 text-green-600 flex items-center justify-center">
                        <i class="fas fa-eye"></i>
                    </div>
                </div>
                <div class="flex items-end">
                    <span class="text-3xl font-bold text-gray-800"><?php echo number_format($visible_feedbacks); ?></span>
                </div>
            </div>
            <div class="px-6 py-3 bg-white border-t border-gray-100">
                <span class="text-xs text-gray-500">Currently featured testimonials</span>
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
                </div>
                
                <div class="flex flex-col sm:flex-row items-center gap-3 w-full lg:w-auto">
                    <select class="px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sidebar-accent w-full sm:w-auto">
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
                        <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                            <td class="px-4 py-3.5 text-sm font-medium text-gray-800 whitespace-nowrap">Jane Doe</td>
                            <td class="px-4 py-3.5 text-sm text-yellow-600 whitespace-nowrap">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i> (5.0)
                            </td>
                            <td class="px-4 py-3.5 text-sm text-gray-700 max-w-[150px] truncate" title="The service was exceptional, highly recommend GrievEase to everyone! The process was smooth and the support team was very helpful.">
                                The service was exceptional, highly recommend GrievEase to everyone! The process was smooth and the support team was very helpful.
                            </td>
                            <td class="px-4 py-3.5 text-sm text-gray-500 whitespace-nowrap">2025-10-25</td>
                            <td class="px-4 py-3.5 text-center">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" value="" class="sr-only peer toggle-checkbox" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-sidebar-accent rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <button class="p-1.5 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Full Content">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        
                        <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                            <td class="px-4 py-3.5 text-sm font-medium text-gray-800 whitespace-nowrap">John Smith</td>
                            <td class="px-4 py-3.5 text-sm text-yellow-600 whitespace-nowrap">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="far fa-star"></i> (4.0)
                            </td>
                            <td class="px-4 py-3.5 text-sm text-gray-700 max-w-[150px] truncate" title="Good experience overall, but the initial response was a bit slow. The issue was eventually resolved after a couple of days.">
                                Good experience overall, but the initial response was a bit slow. The issue was eventually resolved after a couple of days.
                            </td>
                            <td class="px-4 py-3.5 text-sm text-gray-500 whitespace-nowrap">2025-09-15</td>
                            <td class="px-4 py-3.5 text-center">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" value="" class="sr-only peer toggle-checkbox">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-sidebar-accent rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <button class="p-1.5 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Full Content">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>

                        <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                            <td class="px-4 py-3.5 text-sm font-medium text-gray-800 whitespace-nowrap">Alice Johnson</td>
                            <td class="px-4 py-3.5 text-sm text-yellow-600 whitespace-nowrap">
                                <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star-half-alt"></i><i class="far fa-star"></i> (3.5)
                            </td>
                            <td class="px-4 py-3.5 text-sm text-gray-700 max-w-[150px] truncate" title="It was okay. Could use better communication updates. I wish the status changes were more frequent.">
                                It was okay. Could use better communication updates. I wish the status changes were more frequent.
                            </td>
                            <td class="px-4 py-3.5 text-sm text-gray-500 whitespace-nowrap">2025-11-01</td>
                            <td class="px-4 py-3.5 text-center">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" value="" class="sr-only peer toggle-checkbox" checked>
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-sidebar-accent rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <button class="p-1.5 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Full Content">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        
                        <tr class="border-b border-sidebar-border hover:bg-sidebar-hover transition-colors">
                            <td class="px-4 py-3.5 text-sm font-medium text-gray-800 whitespace-nowrap">Bob Williams</td>
                            <td class="px-4 py-3.5 text-sm text-yellow-600 whitespace-nowrap">
                                <i class="fas fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i><i class="far fa-star"></i> (1.0)
                            </td>
                            <td class="px-4 py-3.5 text-sm text-gray-700 max-w-[150px] truncate" title="Very disappointed. Issue was not resolved in the timeline promised and I had to follow up multiple times.">
                                Very disappointed. Issue was not resolved in the timeline promised and I had to follow up multiple times.
                            </td>
                            <td class="px-4 py-3.5 text-sm text-gray-500 whitespace-nowrap">2025-08-20</td>
                            <td class="px-4 py-3.5 text-center">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" value="" class="sr-only peer toggle-checkbox">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-sidebar-accent rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all"></div>
                                </label>
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <button class="p-1.5 bg-blue-100 text-blue-600 rounded-lg hover:bg-blue-200 transition-all tooltip" title="View Full Content">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="sticky bottom-0 left-0 right-0 px-4 py-3.5 border-t border-sidebar-border bg-white flex flex-col sm:flex-row justify-between items-center gap-4">
            <div id="paginationInfo" class="text-sm text-gray-500 text-center sm:text-left">
                Showing <strong>1 - 10</strong> of <strong><?php echo number_format($total_submissions); ?></strong> feedbacks
            </div>
            <div id="paginationContainer" class="flex space-x-2">
                <a href="#" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 pointer-events-none">
                    &laquo;
                </a>
                <a href="#" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover opacity-50 pointer-events-none">
                    &lsaquo;
                </a>
                <a href="#" class="px-3.5 py-1.5 rounded text-sm bg-sidebar-accent text-white">1</a>
                <a href="#" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">2</a>
                <a href="#" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">3</a>
                <a href="#" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">
                    &rsaquo;
                </a>
                <a href="#" class="px-3.5 py-1.5 border border-sidebar-border rounded text-sm hover:bg-sidebar-hover">
                    &raquo;
                </a>
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
    function openFeedbackModal(customerName, rating, content, date, isVisible, feedbackId) {
        const modal = document.getElementById('viewFeedbackModal');
        const toggle = document.getElementById('modalVisibilityToggle');
        const statusText = document.getElementById('modalVisibilityStatus');

        // Populate content
        document.getElementById('modalCustomerName').textContent = customerName;
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

    // Function to close the view feedback modal
    window.closeFeedbackModal = function() {
        const modal = document.getElementById('viewFeedbackModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    // --- Dynamic Content Setup and Event Listeners ---
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile sidebar toggle (Kept from original code)
        const mobileHamburger = document.getElementById('mobile-hamburger');
        const sidebar = document.getElementById('sidebar');
        
        if (mobileHamburger && sidebar) {
            mobileHamburger.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
            });
        }
        
        // Close modal when clicking outside (updated for new modal ID)
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
        
        const tableBody = document.getElementById('feedbackTableBody');
        
        // Loop through all table rows to attach click handlers
        tableBody.querySelectorAll('tr').forEach((row, index) => {
            // Target the button with the fa-eye icon
            const viewButton = row.querySelector('.fa-eye').closest('button'); 
            
            // NOTE: Since this is mock HTML, we use the row index + 1 as a mock feedback ID
            const feedbackId = index + 1; 

            // Assuming the table columns are structured: 0: Name, 1: Rating, 2: Content Snippet, 3: Toggle, 4: Date
            const customerName = row.cells[0]?.textContent?.trim() || 'N/A';
            
            // Extract rating from the cell text (e.g., from "⭐...⭐ (5.0)" extract "5.0")
            const ratingTextMatch = row.cells[1].textContent.trim().match(/\((.*)\)/);
            const ratingText = ratingTextMatch ? ratingTextMatch[1] : '0.0';
            const rating = parseFloat(ratingText);

            // Get full content from the title attribute
            const content = row.cells[2].getAttribute('title') || row.cells[2].textContent.trim();
            
            // Get visibility status, date, and ID
            const toggleInput = row.cells[4].querySelector('input[type="checkbox"]');
            const isVisible = toggleInput ? (toggleInput.checked ? 1 : 0) : 0;
            const date = row.cells[3]?.textContent?.trim() || 'N/A'; 
            
            // Attach mock ID to table toggle for two-way sync
            if (toggleInput) {
                // IMPORTANT: The HTML column order is [0: Name, 1: Rating, 2: Content, 3: Date, 4: Toggle]
                toggleInput.setAttribute('data-id', feedbackId);
            }
            
            if (viewButton) {
                // The onclick function is updated to pass the date, isVisible, and feedbackId
                viewButton.onclick = function() {
                    openFeedbackModal(customerName, rating, content, date, isVisible, feedbackId);
                };
            }
        });
        
        // Search functionality
        const searchInput = document.getElementById('feedbackSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', filterTable);
        }

        function filterTable() {
            const searchValue = (searchInput.value || '').toLowerCase();
            const rows = document.querySelectorAll('#feedbackTableBody tr');
            
            rows.forEach(row => {
                const nameCell = row.cells[0]?.textContent?.toLowerCase() || '';
                const contentCell = row.cells[2]?.textContent?.toLowerCase() || '';
                
                const matchesSearch = nameCell.includes(searchValue) || 
                                    contentCell.includes(searchValue);
                
                row.style.display = matchesSearch ? '' : 'none';
            });
        }
        
        // Add listener for the modal visibility toggle
        document.getElementById('modalVisibilityToggle')?.addEventListener('change', function() {
            const feedbackId = this.getAttribute('data-feedback-id');
            const newStatus = this.checked ? 1 : 0;
            const statusText = document.getElementById('modalVisibilityStatus');
            
            statusText.textContent = newStatus === 1 ? 'Visible' : 'Hidden';
            
            // Update the corresponding toggle in the main table for instant visual feedback
            // This relies on the 'data-id' attribute being set in the DOMContentLoaded function above.
            const tableToggle = document.querySelector(`input[data-id="${feedbackId}"]`);
            if (tableToggle) {
                tableToggle.checked = this.checked;
            }
            
            // --- AJAX IMPLEMENTATION ---
            // Add your fetch/AJAX call here to update the database visibility status
            // The body would be: `feedback_id=${feedbackId}&is_visible=${newStatus}`
        });
    });
</script>

</body>
</html>