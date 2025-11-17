<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../Landing_Page/login.php");
    exit();
}

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

$session_timeout = 1800;
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    session_unset();
    session_destroy();
    header("Location: ../Landing_Page/login.php?timeout=1");
    exit();
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

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
            session_destroy();
            header("Location: ../Landing_Page/login.php");
    }
    exit();
}

require_once '../db_connect.php';

$user_id = $_SESSION['user_id'];
$query = "SELECT first_name , last_name , email , birthdate FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$first_name = $row['first_name'];
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
                
                // Get user's life plan bookings from database (only unread notifications)
                $lifeplan_query = "SELECT * FROM lifeplan_booking_tb WHERE customer_id = ? AND is_read = FALSE ORDER BY initial_date DESC";
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
                    
                    // Only count unread booking notifications
                    $query = "SELECT status FROM booking_tb WHERE customerID = ? AND is_read = FALSE";
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
                    
                    // Get ID validation status (only unread)
                    $query = "SELECT is_validated FROM valid_id_tb WHERE id = ? AND is_read = FALSE";
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

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $feedback_text = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
    $service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : NULL;
    $service_type = isset($_POST['service_type']) ? $_POST['service_type'] : '';
    
    // Validate service type
    $allowed_service_types = ['traditional-funeral', 'custom-package', 'life-plan'];
    if (!in_array($service_type, $allowed_service_types)) {
        $error = 'Invalid service type. Please try again.';
    }
    // Check if user already submitted feedback for this service and service type
    elseif ($service_id) {
        $check_feedback_query = "SELECT id FROM feedback_tb WHERE customer_id = ? AND service_id = ? AND service_type = ?";
        $check_stmt = $conn->prepare($check_feedback_query);
        $check_stmt->bind_param("iis", $user_id, $service_id, $service_type);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'You have already submitted feedback for this service. Each user can only submit one feedback per service.';
            $check_stmt->close();
        } else {
            $check_stmt->close();
            
            // Proceed with validation and insertion
            if ($rating === 0) {
                $error = 'Please select a rating by clicking on the stars.';
            } elseif ($rating < 1 || $rating > 5) {
                $error = 'Please select a valid rating between 1 and 5 stars.';
            } elseif (empty($feedback_text)) {
                $error = 'Please provide your feedback.';
            } else {
                $ph_time = new DateTime('now', new DateTimeZone('Asia/Manila'));
                $created_at = $ph_time->format('Y-m-d H:i:s');
                
                // Updated INSERT query to include service_type
                $insert_query = "INSERT INTO feedback_tb (customer_id, service_id, service_type, rating, feedback_text, status, created_at) 
                                VALUES (?, ?, ?, ?, ?, 'Hidden', ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("iisiss", $user_id, $service_id, $service_type, $rating, $feedback_text, $created_at);
                
                if ($insert_stmt->execute()) {
                    $success = true;
                } else {
                    $error = 'Sorry, there was an error submitting your feedback. Please try again.';
                }
                
                $insert_stmt->close();
            }
        }
    } else {
        $error = 'Invalid service ID. Please try again.';
    }
}

$conn->close();
?>

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
    <nav class="bg-black text-white shadow-md w-full fixed top-0 left-0 z-50 px-4 sm:px-6 lg:px-8" style="height: var(--navbar-height);">
        <div class="flex justify-between items-center h-16">
            <a href="index.php" class="flex items-center space-x-2">
                <img src="../Landing_Page/Landing_images/logo.png" alt="Logo" class="h-[42px] w-[38px]">
                <span class="text-yellow-600 text-3xl">GrieveEase</span>
            </a>
            
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
            
            <div class="hidden md:flex items-center space-x-4">
                <a href="notification.php" id="notification-bell" class="relative text-white hover:text-yellow-600 transition-colors">
                    <i class="fas fa-bell"></i>
                    <?php if ($notifications_count['pending'] > 0 || $notifications_count['id_validation'] > 0): ?>
                    <span id="notification-count" class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                        <?php echo $notifications_count['pending'] + $notifications_count['id_validation']; ?>
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
            
            <div class="md:hidden flex justify-between items-center px-4 py-3 border-b border-gray-700">
                <div class="flex items-center space-x-4">
                    <a href="notification.php" class="relative text-white hover:text-yellow-600 transition-colors">
                        <i class="fas fa-bell"></i>
                        <?php if ($notifications_count['total'] > 0): ?>
                        <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                            <?php echo $notifications_count['total']; ?>
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

    <main class="max-w-screen-xl mx-auto px-4 sm:px-6 py-8 mt-[var(--navbar-height)]">
        <div class="bg-gradient-to-r from-navy/90 to-navy/40 rounded-xl p-6 sm:p-10 mb-8 relative overflow-hidden">
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
        
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden dashboard-card transition-all duration-300">
                <div class="p-6 sm:p-8">
                    <?php if ($success): ?>
                    <div id="success-message" class="bg-gradient-to-r from-green-50 to-green-100 border-l-4 border-green-500 text-green-700 px-6 py-5 rounded-lg mb-6 fade-in-up" role="alert">
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
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                    <div id="error-message" class="bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg mb-6 fade-in-up" role="alert">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                            <div>
                                <strong class="font-bold">Error!</strong>
                                <span id="error-text" class="block text-sm mt-1"><?php echo htmlspecialchars($error); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form id="feedback-form" method="POST" class="space-y-8">
                        <input type="hidden" name="service_id" value="<?php echo isset($_GET['service_id']) ? htmlspecialchars($_GET['service_id']) : ''; ?>">
                        <input type="hidden" name="service_type" value="<?php echo isset($_GET['service_type']) ? htmlspecialchars($_GET['service_type']) : ''; ?>">
                        
                        <!-- Service Type Display -->
                        <?php if (isset($_GET['service_type'])): ?>
                        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-200">
                            <div class="flex items-center justify-center">
                                <i class="fas fa-cube text-blue-600 mr-2"></i>
                                <span class="text-blue-800 font-medium">
                                    Service Type: 
                                    <?php 
                                    $service_type_display = htmlspecialchars($_GET['service_type']);
                                    echo ucwords(str_replace('-', ' ', $service_type_display));
                                    ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="bg-gradient-to-br from-yellow-50 to-orange-50 p-6 rounded-xl border border-yellow-200">
                            <label class="block text-navy text-lg font-hedvig mb-4 text-center">
                                <i class="fas fa-star text-yellow-600 mr-2"></i>
                                How would you rate your experience?
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="rating-container py-4">
                                <input type="radio" id="star5" name="rating" value="5" class="rating-input">
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

    <footer class="bg-black font-playfair text-white py-12 mt-12">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-yellow-600 text-2xl mb-4">GrieveEase</h3>
                    <p class="text-gray-300 mb-4">Providing dignified funeral services with compassion and respect since 1980.</p>
                </div>
                
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
                
                <div>
                    <h3 class="text-lg mb-4">Our Services</h3>
                    <ul class="space-y-2">
                        <li><a href="traditional_funeral.php" class="text-gray-300 hover:text-white transition">Traditional Funeral</a></li>
                        <li><a href="lifeplan.php" class="text-gray-300 hover:text-white transition">Life Plan</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg mb-4">Contact Us</h3>
                    <ul class="space-y-2">
                        <li class="flex items-start">
            <i class="fas fa-map-marker-alt mt-1 mr-2 text-yellow-600"></i>
            <a href="https://www.google.com/maps/place/Relova+House/@14.2334299,121.3641134,3a,75y,175.91h,95.84t/data=!3m7!1e1!3m5!1sJ3xMh7oB9IPYcVOoWh7r1w!2e0!6shttps:%2F%2Fstreetviewpixels-pa.googleapis.com%2Fv1%2Fthumbnail%3Fcb_client%3Dmaps_sv.tactile%26w%3D900%26h%3D600%26pitch%3D-5.83729800653137%26panoid%3DJ3xMh7oB9IPYcVOoWh7r1w%26yaw%3D175.90838416612516!7i16384!8i8192!4m6!3m5!1s0x3397e3e78f3dbe85:0xbf90dbd162697767!8m2!3d14.2334537!4d121.3639905!16s%2Fg%2F11j56kmr4k?entry=ttu&g_ep=EgoyMDI1MTExMi4wIKXMDSoASAFQAw%3D%3D" target="_blank" class="text-gray-300 hover:text-white transition hover:underline">
                #6 J.P Rizal St. Brgy. Sta Clara Sur, (Pob) Pila, Laguna
            </a>
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
                    <a href="privacy_policy.php" class="text-gray-400 hover:text-white transition mx-2">Privacy Policy</a>
                    <a href="termsofservice.php" class="text-gray-400 hover:text-white transition mx-2">Terms of Service</a>
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
                e
                
                
                const feedback = document.getElementById('feedback').value.trim();
                
                const ratingInputs = document.querySelectorAll('input[name="rating"]');
                let ratingSelected = false;
                
                // Check if any rating is selected
                ratingInputs.forEach(input => {
                    if (input.checked) {
                        ratingSelected = true;
                    }
                });
                
                // If no rating selected, prevent default and show error
                if (!ratingSelected) {
                    e.preventDefault();
                    showError('Please select a rating by clicking on the stars.');
                    return false;
                }
                
                if (!feedback) {
                    e.preventDefault();
                    showError('Please provide your feedback');
                    return false;
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
            
            function showError(message) {
                // Create or update error message
                let errorMessageDiv = document.getElementById('error-message');
                let errorTextSpan = document.getElementById('error-text');
                
                if (!errorMessageDiv) {
                    // Create error message element if it doesn't exist
                    errorMessageDiv = document.createElement('div');
                    errorMessageDiv.id = 'error-message';
                    errorMessageDiv.className = 'bg-gradient-to-r from-red-50 to-red-100 border-l-4 border-red-500 text-red-700 px-6 py-4 rounded-lg mb-6 fade-in-up';
                    errorMessageDiv.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-2xl mr-3"></i>
                            <div>
                                <strong class="font-bold">Error!</strong>
                                <span id="error-text" class="block text-sm mt-1">${message}</span>
                            </div>
                        </div>
                    `;
                    
                    // Insert before the form
                    const form = document.getElementById('feedback-form');
                    form.parentNode.insertBefore(errorMessageDiv, form);
                } else {
                    // Update existing error message
                    errorTextSpan = document.getElementById('error-text');
                    if (errorTextSpan) {
                        errorTextSpan.textContent = message;
                    }
                    errorMessageDiv.classList.remove('hidden');
                }
                
                // Hide success message if it exists
                const successMessage = document.getElementById('success-message');
                if (successMessage) {
                    successMessage.classList.add('hidden');
                }
                
                // Scroll to error message
                errorMessageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Hide error after 5 seconds
                setTimeout(() => {
                    errorMessageDiv.classList.add('hidden');
                }, 5000);
            }
        });
        
        // Notification bell click handler
        document.addEventListener('DOMContentLoaded', function() {
            const notificationBell = document.getElementById('notification-bell');
            const notificationCount = document.getElementById('notification-count');
            
            if (notificationBell && notificationCount) {
                notificationBell.addEventListener('click', async function(e) {
                    // Only mark as read if there are notifications
                    if (notificationCount.textContent > 0) {
                        e.preventDefault();
                        
                        try {
                            // Mark notifications as read
                            const response = await fetch('notification/mark_notifications_seen.php');
                            const result = await response.json();
                            
                            if (result.success) {
                                // Remove the notification count badge
                                notificationCount.remove();
                                
                                // Then navigate to notification page
                                window.location.href = 'notification.php';
                            } else {
                                // If marking as read fails, just navigate normally
                                window.location.href = 'notification.php';
                            }
                        } catch (error) {
                            console.error('Error marking notifications as read:', error);
                            // If there's an error, just navigate normally
                            window.location.href = 'notification.php';
                        }
                    }
                    // If no notifications, the normal link behavior will proceed
                });
            }
        });
    </script>
</body>
</html>