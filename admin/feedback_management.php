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
        <p>© 2025 GrievEase. Feedback Management UI.</p>
    </footer>
</div>

<div id="feedbackModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        
        <div class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-xl leading-6 font-bold text-gray-900 border-b pb-2 flex items-center gap-2">
                            <i class="fas fa-comment-alt text-sidebar-accent"></i> Full Feedback Details
                        </h3>
                        
                        <div class="mt-4 space-y-3">
                            <p class="text-sm font-medium text-gray-700">Customer: <span class="font-normal text-gray-800" id="modalCustomerName">Jane Doe</span></p>
                            <p class="text-sm font-medium text-gray-700 flex items-center">Rating: 
                                <span class="ml-2 text-lg text-yellow-600" id="modalRatingStars">
                                    <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                                </span>
                                <span class="ml-1 text-sm text-gray-600">(5.0)</span>
                            </p>
                            <div class="p-3 bg-gray-100 rounded-lg border border-gray-200">
                                <p class="text-sm font-medium text-gray-700 mb-1">Feedback:</p>
                                <p class="text-base text-gray-800" id="modalFeedbackContent">
                                    The service was exceptional, highly recommend GrievEase to everyone! The process was smooth and the support team was very helpful.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-100 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeFeedbackModal()">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Mobile sidebar toggle
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile hamburger menu toggle
        const mobileHamburger = document.getElementById('mobile-hamburger');
        const sidebar = document.getElementById('sidebar');
        
        if (mobileHamburger && sidebar) {
            mobileHamburger.addEventListener('click', function() {
                sidebar.classList.toggle('-translate-x-full');
            });
        }

        // Function to open feedback modal
        function openFeedbackModal(customerName, rating, content) {
            document.getElementById('modalCustomerName').textContent = customerName;
            
            // Simple star rendering logic
            let starsHtml = '';
            const fullStars = Math.floor(rating);
            const hasHalfStar = rating % 1 !== 0;
            
            for (let i = 0; i < fullStars; i++) {
                starsHtml += '<i class="fas fa-star"></i>';
            }
            
            if (hasHalfStar) {
                starsHtml += '<i class="fas fa-star-half-alt"></i>';
            }
            
            for (let i = 0; i < 5 - Math.ceil(rating); i++) {
                starsHtml += '<i class="far fa-star"></i>';
            }

            document.getElementById('modalRatingStars').innerHTML = starsHtml;
            document.getElementById('modalFeedbackContent').textContent = content;
            
            // Update rating number in parentheses
            const ratingNumberElement = document.querySelector('#modalRatingStars + span');
            if (ratingNumberElement) {
                ratingNumberElement.textContent = `(${rating.toFixed(1)})`;
            }

            document.getElementById('feedbackModal').classList.remove('hidden');
        }

        // Function to close feedback modal
        window.closeFeedbackModal = function() {
            document.getElementById('feedbackModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('feedbackModal');
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

        // Attach event listeners to view buttons
        document.querySelectorAll('#feedbackTableBody tr').forEach(row => {
            const viewButton = row.querySelector('.fa-eye').closest('button');
            const customerName = row.cells[0].textContent.trim();
            
            // Extract the rating number from the text content of the second cell
            // e.g., from "⭐...⭐ (5.0)" extract "5.0"
            const ratingTextMatch = row.cells[1].textContent.trim().match(/\((.*)\)/);
            const ratingText = ratingTextMatch ? ratingTextMatch[1] : '0.0';
            const rating = parseFloat(ratingText);

            // Get full content from the title attribute
            const content = row.cells[2].getAttribute('title');

            if (viewButton) {
                viewButton.onclick = function() {
                    openFeedbackModal(customerName, rating, content);
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
    });
</script>

</body>
</html>