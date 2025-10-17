<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check for correct user type based on which directory we're in
$current_directory = basename(dirname($_SERVER['PHP_SELF']));
$allowed_user_type = null;

switch ($current_directory) {
    case 'admin':
        $allowed_user_type = 1;
        break;
    case 'employee':
        $allowed_user_type = 2;
        break;
    case 'customer':
        $allowed_user_type = 3;
        break;
}

// If user is not the correct type for this page, redirect to appropriate page
if ($_SESSION['user_type'] != $allowed_user_type) {
    switch ($_SESSION['user_type']) {
        case 1:
            header("Location: ../admin/index.php");
            break;
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

require_once '../db_connect.php'; // Database connection

                // Get user's first name from database
                $user_id = $_SESSION['user_id'];
                $query = "SELECT first_name , last_name , email , birthdate FROM users WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $first_name = $row['first_name']; // We're confident user_id exists
                $last_name = $row['last_name'];
                $email = $row['email'];
                
                // Get notification count for the current user
                $notifications_count = [
                    'total' => 0,
                    'pending' => 0,
                    'accepted' => 0,
                    'declined' => 0,
                    'id_pending' => 0,
                    'id_accepted' => 0,
                    'id_declined' => 0
                ];
                
                // Get user's life plan bookings from database (notifications)
                $lifeplan_query = "SELECT * FROM lifeplan_booking_tb WHERE customer_id = ? ORDER BY initial_date DESC";
                $lifeplan_stmt = $conn->prepare($lifeplan_query);
                $lifeplan_stmt->bind_param("i", $user_id);
                $lifeplan_stmt->execute();
                $lifeplan_result = $lifeplan_stmt->get_result();
                $lifeplan_bookings = [];
                
                while ($lifeplan_booking = $lifeplan_result->fetch_assoc()) {
                    $lifeplan_bookings[] = $lifeplan_booking;
                    
                    switch ($lifeplan_booking['booking_status']) {
                        case 'pending':
                            $notifications_count['total']++;
                            $notifications_count['pending']++;
                            break;
                        case 'accepted':
                            $notifications_count['total']++;
                            $notifications_count['accepted']++;
                            break;
                        case 'decline':
                            $notifications_count['total']++;
                            $notifications_count['declined']++;
                            break;
                    }
                }

                if (isset($_SESSION['user_id'])) {
                    $user_id = $_SESSION['user_id'];
                    $query = "SELECT status FROM booking_tb WHERE customerID = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    while ($booking = $result->fetch_assoc()) {
                        $notifications_count['total']++;
                        
                        switch ($booking['status']) {
                            case 'Pending':
                                $notifications_count['pending']++;
                                break;
                            case 'Accepted':
                                $notifications_count['accepted']++;
                                break;
                            case 'Declined':
                                $notifications_count['declined']++;
                                break;
                        }
                    }
                    $stmt->close();
                    
                    // Get ID validation status
                $query = "SELECT is_validated FROM valid_id_tb WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($id_validation = $result->fetch_assoc()) {
                    if ($id_validation['is_validated'] == 'no') {
                        $notifications_count['id_validation']++;
                        $notifications_count['total']++;
                    }
                }
                $stmt->close();
                }
                
                
                $profile_query = "SELECT profile_picture FROM users WHERE id = ?";
                $profile_stmt = $conn->prepare($profile_query);
                $profile_stmt->bind_param("i", $user_id);
                $profile_stmt->execute();
                $profile_result = $profile_stmt->get_result();
                $profile_data = $profile_result->fetch_assoc();
                
                $profile_picture = $profile_data['profile_picture'];
                
                $conn->close();
?>

<script src="customer_support.js"></script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback - GrieveEase</title>
    <?php include 'faviconLogo.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../tailwind.js"></script>
    <style>
        :root {
            --navbar-height: 64px;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9F6F0;
        }
        
        .main-content {
            padding-top: var(--navbar-height);
        }
        
        .rating-container {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .rating-input {
            display: none;
        }
        
        .rating-label {
            font-size: 3rem;
            color: #d1d5db;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .rating-label:hover,
        .rating-label:hover ~ .rating-label {
            color: #fbbf24;
            transform: scale(1.1);
        }
        
        .rating-input:checked ~ .rating-label {
            color: #f59e0b;
            animation: starPulse 0.3s ease;
        }
        
        @keyframes starPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.8s ease forwards;
            opacity: 0;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.4s; }
        .delay-3 { animation-delay: 0.6s; }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        
        .pulse-slow {
            animation: pulseSlow 3s infinite;
        }
        
        @keyframes pulseSlow {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 0 0 0 rgba(202, 138, 4, 0.4);
            }
            70% {
                transform: scale(1.03);
                box-shadow: 0 0 0 15px rgba(202, 138, 4, 0);
            }
        }
        
        .text-shadow-lg {
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>
<body class="bg-cream overflow-x-hidden w-full max-w-full m-0 p-0 font-hedvig">
    <!-- Fixed Navigation Bar -->
    <nav class="bg-black text-white shadow-md w-full fixed top-0 left-0 z-50 px-4 sm:px-6 lg:px-8" style="height: var(--navbar-height);">
        <div class="flex justify-between items-center h-16">
            <!-- Left side: Logo and Text with Link -->
            <a href="index.php" class="flex items-center space-x-2">
                <img src="../Landing_Page/Landing_images/logo.png" alt="Logo" class="h-[42px] w-[38px]">
                <span class="text-yellow-600 text-3xl">GrieveEase</span>
            </a>
            
            <!-- Center: Navigation Links (Hidden on small screens) -->
            <div class="hidden md:flex space-x-6">
                <a href="index.php" class="text-white hover:text-gray-300 transition relative group">
                    Home
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="about.php" class="text-white hover:text-gray-300 transition relative group">
                    About
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="lifeplan.php" class="text-white hover:text-gray-300 transition relative group">
                    Life Plan
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="traditional_funeral.php" class="text-white hover:text-gray-300 transition relative group">
                    Traditional Funeral
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="packages.php" class="text-white hover:text-gray-300 transition relative group">
                    Packages
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="faqs.php" class="text-white hover:text-gray-300 transition relative group">
                    FAQs
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
            </div>
            
            <!-- User Menu -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="notification.php" class="relative text-white hover:text-yellow-600 transition-colors">
                    <i class="fas fa-bell"></i>
                    <?php if ($notifications_count > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                        <?php echo $notifications_count; ?>
                    </span>
                    <?php endif; ?>
                </a>
                
                <div class="relative group">
                    <button class="flex items-center space-x-2">
                        <div class="w-8 h-8 rounded-full bg-yellow-600 flex items-center justify-center text-sm overflow-hidden">
                            <?php if ($profile_picture && file_exists('../profile_picture/' . $profile_picture)): ?>
                                <img src="../profile_picture/<?php echo htmlspecialchars($profile_picture); ?>" 
                                     alt="Profile Picture" 
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <?php 
                                    $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); 
                                    echo htmlspecialchars($initials);
                                ?>
                            <?php endif; ?>
                        </div>
                        <span class="hidden md:inline text-sm">
                            <?php echo htmlspecialchars(ucwords($first_name . ' ' . $last_name)); ?>
                        </span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-card overflow-hidden invisible group-hover:visible transition-all duration-300 opacity-0 group-hover:opacity-100">
                        <div class="p-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-navy"><?php echo htmlspecialchars(ucwords($first_name . ' ' . $last_name)); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($email); ?></p>
                        </div>
                        <div class="py-1">
                            <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                            <a href="../logout.php" class="block px-4 py-2 text-sm text-error hover:bg-gray-100">Sign Out</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Mobile menu header -->
            <div class="md:hidden flex justify-between items-center px-4 py-3 border-b border-gray-700">
                <div class="flex items-center space-x-4">
                    <a href="notification.php" class="relative text-white hover:text-yellow-600 transition-colors">
                        <i class="fas fa-bell"></i>
                        <?php if ($notifications_count > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                            <?php echo $notifications_count; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <button onclick="toggleMenu()" class="focus:outline-none text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden fixed left-0 right-0 top-[--navbar-height] bg-black/90 backdrop-blur-md p-4 z-40 max-h-[calc(100vh-var(--navbar-height))] overflow-y-auto">
            <div class="space-y-2">
                <a href="index.php" class="block text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300 relative group">
                    <div class="flex justify-between items-center">
                        <span>Home</span>
                        <i class="fas fa-home text-yellow-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="about.php" class="block text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300 relative group">
                    <div class="flex justify-between items-center">
                        <span>About</span>
                        <i class="fas fa-info-circle text-yellow-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="lifeplan.php" class="block text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300 relative group">
                    <div class="flex justify-between items-center">
                        <span>Life Plan</span>
                        <i class="fas fa-calendar-alt text-yellow-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="traditional_funeral.php" class="block text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300 relative group">
                    <div class="flex justify-between items-center">
                        <span>Traditional Funeral</span>
                        <i class="fas fa-briefcase text-yellow-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="packages.php" class="block text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300 relative group">
                    <div class="flex justify-between items-center">
                        <span>Packages</span>
                        <i class="fa-solid fa-cube text-yellow-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
                <a href="faqs.php" class="block text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300 relative group">
                    <div class="flex justify-between items-center">
                        <span>FAQs</span>
                        <i class="fas fa-question-circle text-yellow-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
                    </div>
                    <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
                </a>
            </div>
            
            <div class="mt-6 border-t border-gray-700 pt-4">
                <div class="space-y-2">
                    <a href="profile.php" class="flex items-center justify-between text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300">
                        <span>My Profile</span>
                        <i class="fas fa-user text-yellow-600"></i>
                    </a>
                    <a href="../logout.php" class="flex items-center justify-between text-red-400 py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300">
                        <span>Sign Out</span>
                        <i class="fas fa-sign-out-alt text-red-400"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="max-w-screen-xl mx-auto px-4 sm:px-6 py-8 mt-[var(--navbar-height)]">
        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-navy/90 to-navy/40 rounded-xl p-6 sm:p-10 mb-8 relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 bg-center bg-cover bg-no-repeat transition-transform duration-10000 ease-in-out hover:scale-105"
                 style="background-image: url('../Landing_Page/Landing_images/black-bg-image.jpg');">
                <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/40 to-black/80"></div>
            </div>
            
            <div class="relative z-10 max-w-3xl">
                <div class="flex items-center mb-4 fade-in-up">
                    <i class="fas fa-comment-dots text-yellow-600 text-4xl mr-4"></i>
                    <div>
                        <h1 class="font-hedvig text-3xl md:text-4xl text-white text-shadow-lg">Share Your Feedback</h1>
                        <p class="text-white/80 mt-1">We value your opinion and would love to hear about your experience.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Feedback Form Card -->
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden dashboard-card transition-all duration-300">
                <div class="p-6 sm:p-8">
                    <!-- Success Message (Hidden by default, shown via JS) -->
                    <div id="success-message" class="hidden bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-500 text-green-700 px-6 py-5 rounded-lg mb-6 fade-in-up" role="alert">
                        <div class="flex items-center mb-3">
                            <i class="fas fa-check-circle text-3xl mr-3"></i>
                            <div>
                                <strong class="font-bold text-lg">Thank You!</strong>
                                <p class="text-sm mt-1">Your feedback has been submitted successfully.</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 mt-4">
                            <a href="profile.php" class="inline-flex items-center bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-user mr-2"></i> View Profile
                            </a>
                            <a href="index.php" class="inline-flex items-center bg-white hover:bg-gray-50 text-green-700 border border-green-300 px-5 py-2 rounded-lg text-sm font-medium transition-colors">
                                <i class="fas fa-home mr-2"></i> Back to Home
                            </a>
                        </div>
                    </div>

                    <!-- Error Message (Hidden by default, shown via JS) -->
                    <div id="error-message" class="hidden bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg mb-6 fade-in-up" role="alert">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                            <div>
                                <strong class="font-bold">Error!</strong>
                                <span id="error-text" class="block text-sm mt-1"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Feedback Form -->
                    <form id="feedback-form" class="space-y-8">
                            <!-- Rating Section -->
                            <div class="bg-gradient-to-br from-yellow-50 to-orange-50 p-6 rounded-xl border border-yellow-200">
                                <label class="block text-navy text-lg font-hedvig mb-4 text-center">
                                    <i class="fas fa-star text-yellow-600 mr-2"></i>
                                    How would you rate your experience?
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="rating-container py-4">
                                    <input type="radio" id="star5" name="rating" value="5" class="rating-input" required>
                                    <label for="star5" class="rating-label" title="5 stars">★</label>
                                    
                                    <input type="radio" id="star4" name="rating" value="4" class="rating-input">
                                    <label for="star4" class="rating-label" title="4 stars">★</label>
                                    
                                    <input type="radio" id="star3" name="rating" value="3" class="rating-input">
                                    <label for="star3" class="rating-label" title="3 stars">★</label>
                                    
                                    <input type="radio" id="star2" name="rating" value="2" class="rating-input">
                                    <label for="star2" class="rating-label" title="2 stars">★</label>
                                    
                                    <input type="radio" id="star1" name="rating" value="1" class="rating-input">
                                    <label for="star1" class="rating-label" title="1 star">★</label>
                                </div>
                                <p class="text-center text-sm text-gray-600 mt-2">Click on the stars to rate your experience</p>
                            </div>

                            <!-- Feedback Section -->
                            <div>
                                <label for="feedback" class="block text-navy text-lg font-hedvig mb-3">
                                    <i class="fas fa-pen text-yellow-600 mr-2"></i>
                                    Your Feedback
                                    <span class="text-red-500">*</span>
                                </label>
                                <textarea id="feedback" name="feedback" rows="8" 
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-yellow-600 transition-all"
                                    placeholder="Please share your thoughts about our service. Your feedback helps us improve and serve you better..."
                                    required></textarea>
                                <p class="text-sm text-gray-500 mt-2"><i class="fas fa-info-circle mr-1"></i>Please provide detailed feedback to help us understand your experience better.</p>
                            </div>

                            <!-- Buttons -->
                            <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4 pt-4 border-t border-gray-200">
                                <button type="button" onclick="window.history.back()" class="inline-flex items-center justify-center px-6 py-3 border-2 border-gray-300 rounded-lg text-gray-700 bg-white hover:bg-gray-50 font-medium transition-all duration-300">
                                    <i class="fas fa-times mr-2"></i> Cancel
                                </button>
                                <button type="submit" class="inline-flex items-center justify-center px-6 py-3 rounded-lg text-white bg-yellow-600 hover:bg-darkgold font-medium transition-all duration-300 shadow-lg hover:shadow-xl pulse-slow">
                                    <i class="fas fa-paper-plane mr-2"></i> Submit Feedback
                                </button>
                            </div>
                        </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-black font-playfair text-white py-12 mt-12">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <h3 class="text-yellow-600 text-2xl mb-4">GrieveEase</h3>
                    <p class="text-gray-300 mb-4">Providing dignified funeral services with compassion and respect since 1980.</p>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h3 class="text-lg mb-4">Quick Links</h3>
                    <ul class="space-y-2">
                        <li><a href="index.php" class="text-gray-300 hover:text-white transition">Home</a></li>
                        <li><a href="about.php" class="text-gray-300 hover:text-white transition">About</a></li>
                        <li><a href="lifeplan.php" class="text-gray-300 hover:text-white transition">Life Plan</a></li>
                        <li><a href="traditional_funeral.php" class="text-gray-300 hover:text-white transition">Traditional Funeral</a></li>
                        <li><a href="faqs.php" class="text-gray-300 hover:text-white transition">FAQs</a></li>
                    </ul>
                </div>
                
                <!-- Services -->
                <div>
                    <h3 class="text-lg mb-4">Our Services</h3>
                    <ul class="space-y-2">
                        <li><a href="traditional_funeral.php" class="text-gray-300 hover:text-white transition">Traditional Funeral</a></li>
                        <li><a href="lifeplan.php" class="text-gray-300 hover:text-white transition">Life Plan</a></li>
                    </ul>
                </div>
                
                <!-- Contact Info -->
                <div>
                    <h3 class="text-lg mb-4">Contact Us</h3>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-2 text-yellow-600"></i>
                            <span>#6 J.P Rizal St. Brgy. Sta Clara Sur, (Pob) Pila, Laguna</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone-alt mr-2 text-yellow-600"></i>
                            <span>(0956) 814-3000 <br> (0961) 345-4283</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-2 text-yellow-600"></i>
                            <span>GrievEase@gmail.com</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-clock mr-2 text-yellow-600"></i>
                            <span>Available 24/7</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
                <p class="text-yellow-600">&copy; 2025 Vjay Relova Funeral Services. All rights reserved.</p>
                <div class="mt-2">
                    <a href="../privacy_policy.php" class="text-gray-400 hover:text-white transition mx-2">Privacy Policy</a>
                    <a href="../termsofservice.php" class="text-gray-400 hover:text-white transition mx-2">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Toggle mobile menu
        function toggleMenu() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        }
        
        // Handle form submission (UI Only - No Backend)
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('feedback-form');
            const successMessage = document.getElementById('success-message');
            const errorMessage = document.getElementById('error-message');
            const errorText = document.getElementById('error-text');
            
            // Star rating interaction
            const stars = document.querySelectorAll('.rating-label');
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const ratingText = this.getAttribute('title');
                    console.log('Rating selected: ' + ratingText);
                });
            });
            
            // Form submission handler
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent actual form submission
                
                // Get form data
                const rating = document.querySelector('input[name="rating"]:checked');
                const feedback = document.getElementById('feedback').value.trim();
                
                // Validate form
                if (!rating) {
                    // Show error
                    errorText.textContent = 'Please select a rating between 1 and 5';
                    errorMessage.classList.remove('hidden');
                    successMessage.classList.add('hidden');
                    
                    // Scroll to error
                    errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Hide error after 5 seconds
                    setTimeout(() => {
                        errorMessage.classList.add('hidden');
                    }, 5000);
                    return;
                }
                
                if (!feedback) {
                    // Show error
                    errorText.textContent = 'Please provide your feedback';
                    errorMessage.classList.remove('hidden');
                    successMessage.classList.add('hidden');
                    
                    // Scroll to error
                    errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Hide error after 5 seconds
                    setTimeout(() => {
                        errorMessage.classList.add('hidden');
                    }, 5000);
                    return;
                }
                
                // Success - Show success message
                console.log('Feedback submitted (UI Demo):', {
                    rating: rating.value,
                    feedback: feedback
                });
                
                // Hide form and show success
                form.classList.add('hidden');
                errorMessage.classList.add('hidden');
                successMessage.classList.remove('hidden');
                
                // Scroll to success message
                successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Optional: Reset form after showing success
                setTimeout(() => {
                    form.reset();
                }, 1000);
            });
        });
    </script>
</body>
</html>
