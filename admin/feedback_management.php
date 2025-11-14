<?php
session_start();

include 'faviconLogo.php'; 

date_default_timezone_set('Asia/Manila');

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - GrieveEase</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
        }
        .sidebar-link.active {
            background-color: #FEF3C7;
            color: #92400E;
            font-weight: 500;
        }
        .sidebar-link:hover:not(.active) {
            background-color: #FEF9C3;
        }
        .shadow-sidebar {
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        .scrollbar-thin::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.05);
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: rgba(202, 138, 4, 0.6);
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content ml-0 md:ml-64 min-h-screen transition-all duration-300">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm sticky top-0 z-30">
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <!-- Mobile menu button -->
                <button id="mobile-hamburger" class="md:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                
                <!-- Page Title -->
                <h1 class="text-xl font-semibold text-gray-800">Feedback Management</h1>
                
                <!-- User Profile -->
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button id="user-menu" class="flex items-center space-x-2 focus:outline-none">
                            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover">
                            <span class="hidden md:inline text-sm font-medium text-gray-700"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></span>
                            <i class="fas fa-chevron-down text-xs text-gray-500 hidden md:inline"></i>
                        </button>
                        <!-- Dropdown menu -->
                        <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                            <a href="admin_settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
                            <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-4 md:p-6">
            <!-- Filters and Search -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div class="w-full md:w-1/3">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" id="searchInput" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500" placeholder="Search feedback...">
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3 w-full md:w-2/3 justify-end">
                        <select id="filterStatus" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            <option value="all">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="resolved">Resolved</option>
                        </select>
                        <select id="filterRating" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                            <option value="all">All Ratings</option>
                            <option value="5">5 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="2">2 Stars</option>
                            <option value="1">1 Star</option>
                        </select>
                        <button id="filterBtn" class="bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-filter mr-2"></i> Apply Filters
                        </button>
                        <button id="resetBtn" class="border border-gray-300 hover:bg-gray-50 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            <i class="fas fa-redo mr-2"></i> Reset
                        </button>
                    </div>
                </div>
            </div>

            <!-- Feedback Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 mb-6">
                <!-- Total Feedback Card -->
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-100 to-blue-200 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-700">Total Feedback</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-1">0</h3>
                            </div>
                            <div class="p-3 bg-white bg-opacity-30 rounded-full">
                                <i class="fas fa-comment-alt text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Average Rating Card -->
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-100 to-green-200 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-700">Average Rating</p>
                                <div class="flex items-center mt-1">
                                    <span class="text-2xl font-bold text-gray-800 mr-2">4.8</span>
                                    <div class="flex">
                                        <i class="fas fa-star text-yellow-400"></i>
                                        <i class="fas fa-star text-yellow-400"></i>
                                        <i class="fas fa-star text-yellow-400"></i>
                                        <i class="fas fa-star text-yellow-400"></i>
                                        <i class="fas fa-star-half-alt text-yellow-400"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 bg-white bg-opacity-30 rounded-full">
                                <i class="fas fa-star text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Review Card -->
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                    <div class="bg-gradient-to-r from-amber-100 to-amber-200 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-700">Pending Review</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-1">0</h3>
                            </div>
                            <div class="p-3 bg-white bg-opacity-30 rounded-full">
                                <i class="fas fa-clock text-amber-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resolved Card -->
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden">
                    <div class="bg-gradient-to-r from-purple-100 to-purple-200 px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-medium text-gray-700">Resolved</p>
                                <h3 class="text-2xl font-bold text-gray-800 mt-1">0</h3>
                            </div>
                            <div class="p-3 bg-white bg-opacity-30 rounded-full">
                                <i class="fas fa-check-circle text-purple-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback Table -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <!-- Table Header -->
                <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">Customer Feedback</h3>
                        <p class="text-sm text-gray-500 mt-1">Manage and respond to customer feedback</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button id="exportBtn" class="flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                            <i class="fas fa-download mr-2 text-gray-500"></i>
                            Export
                        </button>
                        <button id="refreshBtn" class="p-2 text-gray-500 hover:text-gray-700 focus:outline-none">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700">
                                    <div class="flex items-center">
                                        ID
                                        <i class="fas fa-sort ml-1 text-gray-400"></i>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700">
                                    <div class="flex items-center">
                                        Rating
                                        <i class="fas fa-sort ml-1 text-gray-400"></i>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feedback</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:text-gray-700">
                                    <div class="flex items-center">
                                        Status
                                        <i class="fas fa-sort ml-1 text-gray-400"></i>
                                    </div>
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="feedbackTableBody">
                            <!-- Sample Row 1 -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#FB001</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full" src="https://randomuser.me/api/portraits/women/44.jpg" alt="">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Jane Cooper</div>
                                            <div class="text-sm text-gray-500">jane@example.com</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex items-center">
                                            <i class="fas fa-star text-yellow-400"></i>
                                            <i class="fas fa-star text-yellow-400"></i>
                                            <i class="fas fa-star text-yellow-400"></i>
                                            <i class="fas fa-star text-yellow-400"></i>
                                            <i class="far fa-star text-yellow-400"></i>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 font-medium">Excellent service!</div>
                                    <div class="text-sm text-gray-500 line-clamp-2">The staff was very professional and the service was excellent. Highly recommend GrieveEase for their compassionate care.</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div>May 15, 2023</div>
                                    <div class="text-gray-400">10:30 AM</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Reviewed
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="text-amber-600 hover:text-amber-900 mr-3">
                                        <i class="far fa-eye"></i>
                                    </button>
                                    <button class="text-blue-600 hover:text-blue-900">
                                        <i class="far fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Sample Row 2 -->
                            <tr class="bg-gray-50 hover:bg-gray-100">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#FB002</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full" src="https://randomuser.me/api/portraits/men/32.jpg" alt="">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">John Smith</div>
                                            <div class="text-sm text-gray-500">john@example.com</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex items-center">
                                            <i class="fas fa-star text-yellow-400"></i>
                                            <i class="fas fa-star text-yellow-400"></i>
                                            <i class="fas fa-star text-yellow-400"></i>
                                            <i class="far fa-star text-yellow-400"></i>
                                            <i class="far fa-star text-yellow-400"></i>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 font-medium">Good but could improve</div>
                                    <div class="text-sm text-gray-500 line-clamp-2">The service was good overall, but there was a delay in the scheduled time. The staff was apologetic and professional.</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div>May 14, 2023</div>
                                    <div class="text-gray-400">2:15 PM</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        In Progress
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="text-amber-600 hover:text-amber-900 mr-3">
                                        <i class="far fa-eye"></i>
                                    </button>
                                    <button class="text-blue-600 hover:text-blue-900">
                                        <i class="far fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Sample Row 3 -->
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">#FB003</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <img class="h-10 w-10 rounded-full" src="https://randomuser.me/api/portraits/women/68.jpg" alt="">
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Sarah Johnson</div>
                                            <div class="text-sm text-gray-500">sarah@example.com</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex items-center">
                                            <i class="fas fa-star text-yellow-400"></i>
                                            <i class="fas fa-star text-yellow-400"></i>
                                            <i class="fas fa-star text-yellow-400"></i>
                                            <i class="fas fa-star text-yellow-400"></i>
                                            <i class="fas fa-star text-yellow-400"></i>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900 font-medium">Outstanding experience</div>
                                    <div class="text-sm text-gray-500 line-clamp-2">From the first call to the final service, everything was handled with the utmost care and professionalism. Highly recommended!</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div>May 13, 2023</div>
                                    <div class="text-gray-400">9:45 AM</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Resolved
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="text-amber-600 hover:text-amber-900 mr-3">
                                        <i class="far fa-eye"></i>
                                    </button>
                                    <button class="text-blue-600 hover:text-blue-900">
                                        <i class="far fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Previous
                        </a>
                        <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Next
                        </a>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">1</span> to <span class="font-medium">3</span> of <span class="font-medium">24</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left h-5 w-5"></i>
                                </a>
                                <a href="#" aria-current="page" class="z-10 bg-amber-50 border-amber-500 text-amber-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    1
                                </a>
                                <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    2
                                </a>
                                <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 hidden md:inline-flex relative items-center px-4 py-2 border text-sm font-medium">
                                    3
                                </a>
                                <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                    ...
                                </span>
                                <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    8
                                </a>
                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right h-5 w-5"></i>
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 py-4 px-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <p class="text-sm text-gray-500">© 2023 GrieveEase. All rights reserved.</p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Privacy</span>
                        <span class="text-sm">Privacy Policy</span>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Terms</span>
                        <span class="text-sm">Terms of Service</span>
                    </a>
                </div>
            </div>
        </footer>
    </div>

    <!-- View Feedback Modal -->
    <div id="viewFeedbackModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                    Feedback Details
                                </h3>
                                <button type="button" class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none" onclick="closeViewModal()">
                                    <span class="sr-only">Close</span>
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <div class="mt-6">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-start">
                                        <img id="modalUserImage" class="h-12 w-12 rounded-full" src="" alt="">
                                        <div class="ml-4">
                                            <div class="flex items-center">
                                                <h4 id="modalUserName" class="text-lg font-medium text-gray-900"></h4>
                                                <span id="modalUserEmail" class="ml-2 text-sm text-gray-500"></span>
                                            </div>
                                            <div id="modalRating" class="flex mt-1">
                                                <!-- Stars will be inserted here by JavaScript -->
                                            </div>
                                            <p id="modalDate" class="text-sm text-gray-500 mt-1"></p>
                                        </div>
                                    </div>
                                    <div class="mt-4">
                                        <h5 class="text-sm font-medium text-gray-700">Feedback:</h5>
                                        <p id="modalFeedback" class="mt-1 text-gray-600"></p>
                                    </div>
                                </div>
                                
                                <div class="mt-6">
                                    <h5 class="text-sm font-medium text-gray-700 mb-2">Admin Response:</h5>
                                    <div id="adminResponse" class="bg-blue-50 p-3 rounded-lg">
                                        <p class="text-sm text-gray-700">No response yet.</p>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <label for="responseText" class="block text-sm font-medium text-gray-700">Your Response</label>
                                        <div class="mt-1">
                                            <textarea id="responseText" rows="3" class="shadow-sm focus:ring-amber-500 focus:border-amber-500 block w-full sm:text-sm border border-gray-300 rounded-md p-2" placeholder="Type your response here..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-amber-600 text-base font-medium text-white hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Send Response
                    </button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeViewModal()">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Mobile sidebar toggle
        document.getElementById('mobile-hamburger').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
            document.querySelector('.main-content').classList.toggle('ml-64');
        });

        // User dropdown toggle
        const userMenuButton = document.getElementById('user-menu');
        const userDropdown = document.getElementById('user-dropdown');
        
        if (userMenuButton && userDropdown) {
            userMenuButton.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('hidden');
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!userDropdown.contains(e.target) && !userMenuButton.contains(e.target)) {
                userDropdown.classList.add('hidden');
            }
        });

        // View feedback modal functions
        function openViewModal(feedbackId) {
            // In a real application, you would fetch the feedback details by ID
            // For now, we'll use sample data
            const sampleFeedback = {
                id: 'FB001',
                userName: 'Jane Cooper',
                userEmail: 'jane@example.com',
                userImage: 'https://randomuser.me/api/portraits/women/44.jpg',
                rating: 4,
                feedback: 'The staff was very professional and the service was excellent. Highly recommend GrieveEase for their compassionate care during our difficult time. The attention to detail and the way they handled everything made the process much easier for our family.',
                date: 'May 15, 2023',
                time: '10:30 AM',
                status: 'Reviewed',
                response: 'Thank you for your kind words, Jane. We strive to provide the best possible service during these difficult times. Your feedback is greatly appreciated.'
            };

            // Populate modal with feedback data
            document.getElementById('modalUserName').textContent = sampleFeedback.userName;
            document.getElementById('modalUserEmail').textContent = sampleFeedback.userEmail;
            document.getElementById('modalUserImage').src = sampleFeedback.userImage;
            document.getElementById('modalUserImage').alt = sampleFeedback.userName;
            document.getElementById('modalDate').textContent = `${sampleFeedback.date} at ${sampleFeedback.time}`;
            document.getElementById('modalFeedback').textContent = sampleFeedback.feedback;
            
            // Set rating stars
            const ratingContainer = document.getElementById('modalRating');
            ratingContainer.innerHTML = '';
            for (let i = 1; i <= 5; i++) {
                const star = document.createElement('i');
                star.className = i <= sampleFeedback.rating ? 'fas fa-star text-yellow-400' : 'far fa-star text-yellow-400';
                ratingContainer.appendChild(star);
            }
            
            // Set admin response if exists
            const adminResponse = document.getElementById('adminResponse');
            if (sampleFeedback.response) {
                adminResponse.innerHTML = `
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <img class="h-8 w-8 rounded-full" src="${document.querySelector('img[alt="Profile"]').src}" alt="Admin">
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-900">${document.querySelector('#user-menu span').textContent}</p>
                            <p class="text-sm text-gray-700">${sampleFeedback.response}</p>
                            <p class="text-xs text-gray-500 mt-1">Responded on ${sampleFeedback.date}</p>
                        </div>
                    </div>
                `;
            } else {
                adminResponse.innerHTML = '<p class="text-sm text-gray-700">No response yet.</p>';
            }
            
            // Show modal
            document.getElementById('viewFeedbackModal').classList.remove('hidden');
        }

        function closeViewModal() {
            document.getElementById('viewFeedbackModal').classList.add('hidden');
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            // Add click handlers to view buttons in the table
            document.querySelectorAll('.fa-eye').forEach(button => {
                button.addEventListener('click', function() {
                    // Get the feedback ID from the row
                    const row = this.closest('tr');
                    const feedbackId = row.querySelector('td:first-child').textContent.trim();
                    openViewModal(feedbackId);
                });
            });
            
            // Make rows clickable
            document.querySelectorAll('tbody tr').forEach(row => {
                row.style.cursor = 'pointer';
                row.addEventListener('click', function(e) {
                    // Don't trigger if clicking on a button or link
                    if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'A' && e.target.tagName !== 'I') {
                        const feedbackId = this.querySelector('td:first-child').textContent.trim();
                        openViewModal(feedbackId);
                    }
                });
            });
            
            // Handle filter button click
            document.getElementById('filterBtn').addEventListener('click', function() {
                const status = document.getElementById('filterStatus').value;
                const rating = document.getElementById('filterRating').value;
                const search = document.getElementById('searchInput').value.toLowerCase();
                
                // In a real application, you would make an AJAX call to filter the feedback
                // For now, we'll just show an alert
                alert(`Filters applied:\nStatus: ${status}\nRating: ${rating}\nSearch: ${search || 'None'}`);
            });
            
            // Handle reset button click
            document.getElementById('resetBtn').addEventListener('click', function() {
                document.getElementById('filterStatus').value = 'all';
                document.getElementById('filterRating').value = 'all';
                document.getElementById('searchInput').value = '';
            });
            
            // Handle export button click
            document.getElementById('exportBtn').addEventListener('click', function() {
                // In a real application, this would trigger a file download
                alert('Exporting feedback data...');
            });
            
            // Handle refresh button click
            document.getElementById('refreshBtn').addEventListener('click', function() {
                // In a real application, this would refresh the data
                window.location.reload();
            });
        });
    </script>
</body>
</html>
