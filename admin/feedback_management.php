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
        .star-rating {
            color: #fbbf24;
        }
        .star-rating .far {
            color: #d1d5db;
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
                <button id="mobile-hamburger" class="md:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-xl font-semibold text-gray-800">Feedback Management</h1>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button id="user-menu" class="flex items-center space-x-2 focus:outline-none">
                            <img src="<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile" class="w-8 h-8 rounded-full object-cover">
                            <span class="hidden md:inline text-sm font-medium text-gray-700"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></span>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="p-4 md:p-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 mb-6">
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
                                    <span class="text-2xl font-bold text-gray-800 mr-2">0.0</span>
                                    <div class="star-rating">
                                        <i class="far fa-star"></i>
                                        <i class="far fa-star"></i>
                                        <i class="far fa-star"></i>
                                        <i class="far fa-star"></i>
                                        <i class="far fa-star"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="p-3 bg-white bg-opacity-30 rounded-full">
                                <i class="fas fa-star text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feedback Controls -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div class="w-full sm:w-1/2">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-sort text-gray-400"></i>
                            </div>
                            <select id="sortBy" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500">
                                <option value="newest">Newest First</option>
                                <option value="oldest">Oldest First</option>
                                <option value="highest">Highest Rating</option>
                                <option value="lowest">Lowest Rating</option>
                            </select>
                        </div>
                    </div>
                    <button id="toggleVisibility" class="w-full sm:w-auto bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                        <i class="fas fa-eye-slash mr-2"></i> Toggle Visibility
                    </button>
                </div>
            </div>

            <!-- Feedback List -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Customer
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rating
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Feedback
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Visible
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="feedbackTableBody">
                            <!-- Feedback items will be loaded here -->
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                    No feedback available
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>

        <footer class="bg-white border-t border-gray-200 py-4 px-6">
            <p class="text-sm text-center text-gray-500">© 2023 GrieveEase. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Mobile sidebar toggle
        document.getElementById('mobile-hamburger').addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
            document.querySelector('.main-content').classList.toggle('ml-64');
        });

        // Toggle feedback visibility
        document.getElementById('toggleVisibility').addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (icon.classList.contains('fa-eye-slash')) {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                this.classList.remove('bg-amber-600');
                this.classList.add('bg-green-600');
                // In a real app, you would make an AJAX call to update visibility
                alert('Feedback visibility toggled');
            } else {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                this.classList.remove('bg-green-600');
                this.classList.add('bg-amber-600');
                // In a real app, you would make an AJAX call to update visibility
                alert('Feedback visibility toggled');
            }
        });

        // Handle sort change
        document.getElementById('sortBy').addEventListener('change', function() {
            const sortValue = this.value;
            // In a real app, you would make an AJAX call to sort the feedback
            console.log('Sorting by:', sortValue);
        });

        // Sample function to update average rating stars
        function updateAverageRating(rating) {
            const stars = document.querySelectorAll('.star-rating i');
            stars.forEach((star, index) => {
                if (index < Math.floor(rating)) {
                    star.className = 'fas fa-star';
                } else if (index < Math.ceil(rating)) {
                    star.className = 'fas fa-star-half-alt';
                } else {
                    star.className = 'far fa-star';
                }
            });
        }

        // Initialize with sample data (in a real app, this would come from the server)
        document.addEventListener('DOMContentLoaded', function() {
            // Set sample average rating (4.2 out of 5)
            updateAverageRating(4.2);
            
            // Set sample total feedback count
            document.querySelector('.bg-gradient-to-r.from-blue-100 .text-2xl').textContent = '42';
        });
    </script>
</body>
</html>
