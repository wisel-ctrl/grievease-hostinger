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
                
// PROFILE CONFIRMATION
// Initialize variables
$percentage = 25; // Base 25% for account creation
$status_text = "25% Complete";
$check_icons = [[
    'icon' => 'fa-user-circle',
    'color' => 'text-success',
    'text' => 'Account Created'
]];
$incomplete_steps = [];

// Check verification status
$user_query = "SELECT is_verified, branch_loc FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();

if ($user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    
    // Email verification adds +25% (total 50%)
    if ($user['is_verified'] == 1) {
        $percentage += 25;
        $check_icons[] = [
            'icon' => 'fa-check-circle',
            'color' => 'text-success',
            'text' => 'Account Verified'
        ];
    } else {
        $incomplete_steps[] = 'Account Verification';
    }
    
    // Branch selection adds +20% (total 70% if both previous steps done)
    if ($user['branch_loc'] != 'unknown') {
        $percentage += 20;
        $check_icons[] = [
            'icon' => 'fa-map-marker-alt',
            'color' => 'text-success',
            'text' => 'Branch Selected'
        ];
    } else {
        $incomplete_steps[] = 'Branch Selection';
    }
    
    // Check ID validation status
    $id_query = "SELECT is_validated FROM valid_id_tb WHERE id = ?";
    $id_stmt = $conn->prepare($id_query);
    $id_stmt->bind_param("i", $user_id);
    $id_stmt->execute();
    $id_result = $id_stmt->get_result();
    
    if ($id_result->num_rows > 0) {
        $id_data = $id_result->fetch_assoc();
        
        // ID uploaded adds +20% (total 90% if all previous steps done)
        if ($id_data['is_validated'] == 'no') {
            $percentage += 20;
            $check_icons[] = [
                'icon' => 'fa-id-card',
                'color' => 'text-success',
                'text' => 'ID Uploaded'
            ];
            $incomplete_steps[] = 'ID Verification';
        }
        // ID validated adds +30% (total 100% - can exceed 100% if branch missing)
        elseif ($id_data['is_validated'] == 'valid') {
            $percentage += 30;
            $check_icons[] = [
                'icon' => 'fa-check-circle',
                'color' => 'text-success',
                'text' => 'ID Verified'
            ];
        }
    } else {
        $incomplete_steps[] = 'ID Upload';
    }
}

// Cap at 100% but show penalty if branch is missing
if ($percentage >= 100) {
    if (in_array('Branch Selection', $incomplete_steps)) {
        $percentage = 80; // Penalty for missing branch
        $status_text = "80% Complete (Branch Missing)";
    } else {
        $percentage = 100;
        $status_text = "100% Complete";
    }
} else {
    $status_text = "$percentage% Complete";
}

// Ensure minimum 25% and maximum 100% (before penalty)
$percentage = min(max($percentage, 25), 100);


//ID STATUS
$id_query = "SELECT * FROM valid_id_tb WHERE id = ? ORDER BY upload_at DESC LIMIT 1";
$id_stmt = $conn->prepare($id_query);
$id_stmt->bind_param("i", $user_id);
$id_stmt->execute();
$id_result = $id_stmt->get_result();
$has_id = $id_result->num_rows > 0;
$id_data = $has_id ? $id_result->fetch_assoc() : null;

// Determine status and styling
$status = 'Not Uploaded';
$status_class = 'text-gray-500';
$icon_class = 'text-gray-400';
$bg_class = 'bg-gray-200';
$message = 'You haven\'t uploaded any ID for verification yet.';
$link_text = 'Upload ID Now';
$redirect_url = 'profile.php'; // Default for no ID

if ($has_id) {
    switch(strtolower($id_data['is_validated'])) {
        case 'valid':
            $status = 'Verified';
            $status_class = 'text-success';
            $icon_class = 'text-success';
            $bg_class = 'bg-success/10';
            $message = 'Your identity was successfully verified on '.date('F j, Y', strtotime($id_data['upload_at']));
            $link_text = 'View Details';
            $redirect_url = '#'; // Or set to view_details.php if you have that page
            break;
        case 'no':
            $status = 'Pending Verification';
            $status_class = 'text-yellow-600';
            $icon_class = 'text-yellow-600';
            $bg_class = 'bg-yellow-600/10';
            $message = 'Your ID is pending verification. Uploaded on '.date('F j, Y', strtotime($id_data['upload_at']));
            $link_text = 'Check Status';
            $redirect_url = 'notification.php'; // Redirect to notification.php when pending
            break;
        case 'denied':
            $status = 'Denied';
            $status_class = 'text-danger';
            $icon_class = 'text-danger';
            $bg_class = 'bg-danger/10';
            $message = 'Your ID was denied on '.date('F j, Y', strtotime($id_data['upload_at']));
            if (!empty($id_data['decline_reason'])) {
                $message .= '. Reason: '.htmlspecialchars($id_data['decline_reason']);
            }
            $link_text = 'Re-upload ID';
            $redirect_url = 'profile.php'; // Redirect to profile.php when declined
            break;
    }
}

// BOOKING STATUS
$booking_query = "SELECT * FROM booking_tb WHERE customerID = ? ORDER BY booking_date DESC LIMIT 1";
$booking_stmt = $conn->prepare($booking_query);
$booking_stmt->bind_param("i", $user_id);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();
$has_booking = $booking_result->num_rows > 0;
$booking_data = $has_booking ? $booking_result->fetch_assoc() : null;

// Determine booking status and styling
$booking_status = 'No Booking';
$booking_status_class = 'text-gray-500';
$booking_icon_class = 'text-gray-400';
$booking_bg_class = 'bg-gray-200';
$booking_message = 'You haven\'t made any service bookings yet.';
$booking_link_text = 'Book a Service';
$booking_redirect_url = 'packages.php'; // Default for no booking

if ($has_booking) {
    switch(strtolower($booking_data['status'])) {
        case 'accepted':
            $booking_status = 'Approved';
            $booking_status_class = 'text-success';
            $booking_icon_class = 'text-success';
            $booking_bg_class = 'bg-success/10';
            
            // Format the burial date if available
            $burial_date = !empty($booking_data['deceased_dateOfBurial']) ? 
                date('F j, Y', strtotime($booking_data['deceased_dateOfBurial'])) : 'a future date';
            
            $booking_message = 'Your service booking has been confirmed for ' . $burial_date;
            
            // Add time if you store that information separately
            if (!empty($booking_data['accepted_date'])) {
                $booking_message .= ' (approved on ' . date('F j, Y', strtotime($booking_data['accepted_date'])) . ')';
            }
            
            $booking_link_text = 'View Details';
            $booking_redirect_url = 'profile.php#bookings';
            break;
            
        case 'pending':
            $booking_status = 'Pending';
            $booking_status_class = 'text-yellow-600';
            $booking_icon_class = 'text-yellow-600';
            $booking_bg_class = 'bg-yellow-600/10';
            
            $booking_message = 'Your booking request is under review. Submitted on ' . 
                date('F j, Y', strtotime($booking_data['booking_date']));
            
            $booking_link_text = 'Check Status';
            $booking_redirect_url = 'notification.php';
            break;
            
        case 'declined':
            $booking_status = 'Declined';
            $booking_status_class = 'text-danger';
            $booking_icon_class = 'text-danger';
            $booking_bg_class = 'bg-danger/10';
            
            $booking_message = 'Your booking was declined';
            
            if (!empty($booking_data['decline_date'])) {
                $booking_message .= ' on ' . date('F j, Y', strtotime($booking_data['decline_date']));
            }
            
            if (!empty($booking_data['reason_for_decline'])) {
                $booking_message .= '. Reason: ' . htmlspecialchars($booking_data['reason_for_decline']);
            }
            
            $booking_link_text = 'Book Again';
            $booking_redirect_url = 'services.php';
            break;
    }
}
                
$show_booking_card = !$has_booking || strtolower($booking_data['status']) != 'accepted';
$show_profile_card = $percentage < 100;
$show_id_card = !$has_id || strtolower($id_data['is_validated']) != 'valid';

                $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Home</title>
    <?php include 'faviconLogo.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../tailwind.js"></script>
    <style>
        .modal {
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
        }
        .modal.active {
            opacity: 1;
            pointer-events: auto;
        }
        @keyframes slideIn {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }
        @keyframes slideOut {
            from { transform: translateY(0); }
            to { transform: translateY(-100%); }
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9F6F0;
        }
        .text-shadow-lg {
            text-shadow: 0 4px 8px rgba(0, 0, 0, 0.25);
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        :root {
            --navbar-height: 64px; /* Adjust this value to match your navbar height */
        }
        /* Apply proper padding to main content */
        .main-content {
            padding-top: var(--navbar-height);
        }

        .text-shadow-lg {
            text-shadow: 0 2px 5px rgba(0, 0, 0, 0.5);
        }
        
        .bg-blur {
            backdrop-filter: blur(8px);
        }

        /* Animation for welcome elements */
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

        .delay-1 {
            animation-delay: 0.2s;
        }
        .delay-2 {
            animation-delay: 0.4s;
        }
        .delay-3 {
            animation-delay: 0.6s;
        }
        
        /* Pulsing effect for get started button */
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
    /* Apply scrollbar to the modal content wrapper */
    .modal-content-wrapper::-webkit-scrollbar {
        width: 8px;
    }
    .modal-content-wrapper::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .modal-content-wrapper::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }
    .modal-content-wrapper::-webkit-scrollbar-thumb:hover {
        background: #555;
    }
    </style>
</head>
<body class="bg-cream overflow-x-hidden w-full max-w-full m-0 p-0 font-hedvig">
    <!-- Fixed Navigation Bar -->
    <nav class="bg-black text-white shadow-md w-full fixed top-0 left-0 z-50 px-4 sm:px-6 lg:px-8" style="height: var(--navbar-height);">
        <div class="flex justify-between items-center h-16">
            <!-- Left side: Logo and Text with Link -->
            <a href="index.php" class="flex items-center space-x-2">
                <img src="..\Landing_Page/Landing_images/logo.png" alt="Logo" class="h-[42px] w-[38px]">
                <span class="text-yellow-600 text-3xl">GrievEase</span>
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
                    <?php if ($notifications_count['pending'] > 0 || $notifications_count['id_validation'] > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                        <?php echo $notifications_count['pending'] + $notifications_count['id_validation']; ?>
                    </span>
                    <?php endif; ?>
                </a>
                
                <div class="relative group">
                    <button class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full bg-yellow-600 flex items-center justify-center text-sm">
                        <?php 
                            $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); 
                            echo htmlspecialchars($initials);
                        ?>
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
            
            <!-- mobile menu header -->
            <div class="md:hidden flex justify-between items-center px-4 py-3 border-b border-gray-700">
        <div class="flex items-center space-x-4">
                <a href="notification.php" class="relative text-white hover:text-yellow-600 transition-colors">
                    <i class="fas fa-bell"></i>
                    <?php if ($notifications_count['pending'] > 0 || $notifications_count['id_validation'] > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                        <?php echo $notifications_count['pending'] + $notifications_count['id_validation']; ?>
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

    <!-- Dashboard Content -->
    <main class="max-w-screen-xl mx-auto px-4 sm:px-6 py-8 mt-[var(--navbar-height)]">
        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-navy/90 to-navy/40 rounded-xl p-6 sm:p-10 mb-8 relative overflow-hidden">
            <!-- Background Pattern -->
            <div class="absolute inset-0 bg-center bg-cover bg-no-repeat transition-transform duration-10000 ease-in-out hover:scale-105"
         style="background-image: url('../Landing_Page/Landing_images/black-bg-image.jpg');">
        <!-- Multi-layered gradient overlay for depth and dimension -->
        <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/40 to-black/80"></div>
    </div>
            
            <div class="relative z-10 max-w-3xl">


                <h1 class="font-hedvig text-3xl md:text-4xl text-white text-shadow-lg mb-2 fade-in-up">Welcome back, 
                    <?php 
                        echo htmlspecialchars(ucfirst($first_name)); 
                        ?>
                </h1>
                <p class="text-white/80 max-w-lg mb-6 fade-in-up delay-1">Here you can manage your services, track payments, and get updates on your requests.</p>
                <div class="flex flex-wrap gap-3 fade-in-up delay-2">
    <a href="packages.php" class="bg-yellow-600 hover:bg-darkgold text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors pulse-slow">Explore Packages</a>
    <?php if ($percentage < 100): ?>
        <a href="profile.php" class="bg-white/20 hover:bg-white/30 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors backdrop-blur-sm"><i class="fas fa-user-edit mr-2"></i>Complete Your Profile</a>
    <?php endif; ?>
</div>
            </div>
        </div>
        
        <!-- Status Cards Row -->
<div class="grid grid-cols-1 gap-6 mb-8 dashboard-cards-container">
    <!-- Payment Status Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 dashboard-card transition-all duration-300">
        <div class="flex items-start justify-between mb-4">
            <div>
                <p class="text-sm text-gray-500 font-medium">Payment Status</p>
                <h3 class="text-xl font-hedvig text-navy">Pending Payment</h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-yellow-600/10 flex items-center justify-center">
                <i class="fas fa-credit-card text-yellow-600"></i>
            </div>
        </div>
        <p class="text-sm text-dark mb-4">Your Traditional Funeral package payment is due by March 28, 2025.</p>
        <a href="profile.php#transaction-logs" class="text-sm text-yellow-600 hover:text-darkgold font-medium flex items-center">
            Make Payment <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>
    
    <?php if ($show_booking_card): ?>
    <!-- Booking Status Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 dashboard-card transition-all duration-300">
        <div class="flex items-start justify-between mb-4">
            <div>
                <p class="text-sm text-gray-500 font-medium">Booking Status</p>
                <h3 class="text-xl font-hedvig <?= $booking_status_class ?>"><?= $booking_status ?></h3>
            </div>
            <div class="w-10 h-10 rounded-full <?= $booking_bg_class ?> flex items-center justify-center">
                <i class="fas 
                    <?= $booking_status === 'Approved' ? 'fa-check-circle' : '' ?>
                    <?= $booking_status === 'Pending' ? 'fa-hourglass-half' : '' ?>
                    <?= $booking_status === 'Declined' ? 'fa-times-circle' : '' ?>
                    <?= $booking_status === 'No Booking' ? 'fa-calendar-plus' : '' ?>
                    <?= $booking_icon_class ?>">
                </i>
            </div>
        </div>
        <p class="text-sm text-dark mb-4"><?= $booking_message ?></p>
        <a href="<?= $booking_redirect_url ?>" class="text-sm text-yellow-600 hover:text-darkgold font-medium flex items-center">
            <?= $booking_link_text ?> <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if ($show_profile_card): ?>
    <!-- Profile Completion Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 dashboard-card transition-all duration-300">
        <div class="flex items-start justify-between mb-4">
            <div>
                <p class="text-sm text-gray-500 font-medium">Profile Completion</p>
                <h3 class="text-xl font-hedvig text-navy"><?= $status_text ?></h3>
            </div>
            <div class="w-10 h-10 rounded-full bg-yellow-600/10 flex items-center justify-center">
                <i class="fas fa-user-circle text-yellow-600"></i>
            </div>
        </div>
        
        <!-- Progress bar -->
        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
            <div class="bg-yellow-600 h-2.5 rounded-full" style="width: <?= $percentage ?>%"></div>
        </div>
        
        <!-- Completion steps -->
        <div class="space-y-2 mb-4">
            <?php foreach ($check_icons as $item): ?>
            <div class="flex items-center text-sm">
                <i class="fas <?= $item['icon'] ?> <?= $item['color'] ?> mr-2"></i>
                <span><?= $item['text'] ?></span>
            </div>
            <?php endforeach; ?>
            
            <?php if ($percentage < 100): ?>
                <?php if (!in_array('Account Verified', array_column($check_icons, 'text'))): ?>
                    <div class="flex items-center text-sm text-gray-400">
                        <i class="far fa-circle mr-2"></i>
                        <span>Verify Account</span>
                    </div>
                <?php endif; ?>
                
                <?php 
                $branch_not_selected = !in_array('Branch Selected', array_column($check_icons, 'text'));
                if ($branch_not_selected): ?>
                    <div class="flex items-center text-sm text-gray-400">
                        <i class="far fa-circle mr-2"></i>
                        <span>Select Branch</span>
                    </div>
                <?php endif; ?>
                
                <?php if (!in_array('ID Uploaded', array_column($check_icons, 'text')) && !in_array('ID Verified', array_column($check_icons, 'text'))): ?>
                    <div class="flex items-center text-sm text-gray-400">
                        <i class="far fa-circle mr-2"></i>
                        <span>Upload ID</span>
                    </div>
                <?php endif; ?>
                
                <?php if (in_array('ID Uploaded', array_column($check_icons, 'text')) && !in_array('ID Verified', array_column($check_icons, 'text'))): ?>
                    <div class="flex items-center text-sm text-gray-400">
                        <i class="far fa-circle mr-2"></i>
                        <span>Verify ID</span>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <a href="<?= ($percentage < 100 && $branch_not_selected) ? 'packages.php' : 'profile.php#personal-info' ?>" class="text-sm text-yellow-600 hover:text-darkgold font-medium flex items-center">
            <?= ($percentage < 100 && $branch_not_selected) ? 'Select Branch' : ($percentage < 100 ? 'Complete Profile' : 'View Profile') ?> <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>
    <?php endif; ?>
    
    <?php if ($show_id_card): ?>
    <!-- ID Verification Card -->
    <div class="bg-white rounded-xl shadow-lg p-6 dashboard-card transition-all duration-300">
        <div class="flex items-start justify-between mb-4">
            <div>
                <p class="text-sm text-gray-500 font-medium">ID Verification</p>
                <h3 class="text-xl font-hedvig <?= $status_class ?>"><?= $status ?></h3>
            </div>
            <div class="w-10 h-10 rounded-full <?= $bg_class ?> flex items-center justify-center">
                <i class="fas fa-id-card <?= $icon_class ?>"></i>
            </div>
        </div>
        
        <p class="text-sm text-dark mb-4"><?= $message ?></p>
        
        <a href="<?= $redirect_url ?>" class="text-sm text-yellow-600 hover:text-darkgold font-medium flex items-center">
            <?= $link_text ?> <i class="fas fa-arrow-right ml-2"></i>
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the container and count visible cards
    const cardsContainer = document.querySelector('.dashboard-cards-container');
    const visibleCards = cardsContainer.querySelectorAll('.dashboard-card').length;
    
    // Apply appropriate grid classes based on number of visible cards
    if (visibleCards === 1) {
        cardsContainer.classList.add('grid-cols-1');
        cardsContainer.querySelectorAll('.dashboard-card').forEach(card => {
            card.classList.add('py-4'); // Reduced height for single card
        });
    } else if (visibleCards === 2) {
        cardsContainer.classList.add('sm:grid-cols-2');
    } else if (visibleCards === 3) {
        cardsContainer.classList.add('sm:grid-cols-2', 'lg:grid-cols-3');
    } else if (visibleCards >= 4) {
        cardsContainer.classList.add('sm:grid-cols-2', 'lg:grid-cols-4');
    }
});
</script>
        
        <!-- Services Section -->
<div class="mb-4">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-hedvig text-navy">Available Services</h2>
        <!-- <a href="services.php" class="text-yellow-600 hover:text-darkgold text-l font-medium">View All</a> -->
    </div>
</div>
            
<!-- Services Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Traditional Funeral Service -->
    <div class="bg-white rounded-[20px] shadow-lg hover:shadow-xl transition-all duration-500 overflow-hidden hover:translate-y-[-8px] group">
        <div class="h-48 bg-cover bg-center relative" style="background-image: url('../Landing_Page/Landing_images/sampleImageLANG.jpg')">
            <div class="absolute inset-0 bg-black/40 group-hover:bg-black/30 transition-all duration-300"></div>
            <div class="absolute top-4 right-4 w-12 h-12 rounded-full bg-yellow-600/90 flex items-center justify-center text-white">
                <i class="fas fa-dove text-xl"></i>
            </div>
        </div>
        <div class="p-6">
            <h3 class="text-xl font-hedvig text-navy mb-2">Traditional Funeral</h3>
            <p class="text-dark text-sm mb-4">Our traditional funeral services honor your loved one's life with dignity and respect.</p>
            <div class="flex justify-between items-center">
            <a href="traditional_funeral.php" class="bg-yellow-600 hover:bg-darkgold text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">View Packages</a>
            </div>
        </div>
    </div>
    
    <!-- Pre-Planning Services -->
    <div class="bg-white rounded-[20px] shadow-lg hover:shadow-xl transition-all duration-500 overflow-hidden hover:translate-y-[-8px] group">
        <div class="h-48 bg-cover bg-center relative" style="background-image: url('../Landing_Page/Landing_images/samplePreplan.jpg')">
            <div class="absolute inset-0 bg-black/40 group-hover:bg-black/30 transition-all duration-300"></div>
            <div class="absolute top-4 right-4 w-12 h-12 rounded-full bg-yellow-600/90 flex items-center justify-center text-white">
                <i class="fas fa-clipboard-list text-xl"></i>
            </div>
        </div>
        <div class="p-6">
            <h3 class="text-xl font-hedvig text-navy mb-2">Life Plan</h3>
            <p class="text-dark text-sm mb-4">Plan ahead to ensure your wishes are honored and provide peace of mind.</p>
            <div class="flex justify-between items-center">
            <a href="lifeplan.php" class="bg-yellow-600 hover:bg-darkgold text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors">View Packages</a>
            </div>
        </div>
    </div>
</div>


    
    
</div>


    </main>


    <!-- Footer -->
    <footer class="bg-black font-playfair text-white py-12">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Company Info -->
                <div>
                    <h3 class="text-yellow-600 text-2xl mb-4">GrievEase</h3>
                    <p class="text-gray-300 mb-4">Providing dignified funeral services with compassion and respect since 1980.</p>
                    <div class="flex space-x-4">
                        
                    </div>
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
                        <li class=" flex items-center">
                        <a href="https://web.facebook.com/vjayrelovafuneralservices" class=" hover:text-white transition">
                            <i class="fab fa-facebook-f mr-2 text-yellow-600"></i>
                            <span> VJay Relova Funeral Services</span>
                        </a>
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
                    <a href="..\privacy_policy.php" class="text-gray-400 hover:text-white transition mx-2">Privacy Policy</a>
                    <a href="..\termsofservice.php" class="text-gray-400 hover:text-white transition mx-2">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Loading Animation Overlay -->
<div id="page-loader" class="fixed inset-0 bg-black bg-opacity-80 z-[999] flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-500">
    <div class="text-center">
        <!-- Animated Candle -->
        <div class="relative w-full h-48 mb-6">
            <!-- Candle -->
            <div class="absolute left-1/2 transform -translate-x-1/2 bottom-0 w-16">
                <!-- Wick with Flame (updated positioning) -->
                <div class="relative w-1 h-5 bg-gray-700 mx-auto rounded-t-lg">
                    <!-- Outer Flame (updated positioning) -->
                    <div class="absolute left-1/2 top-[-24px] transform -translate-x-1/2 w-6 h-12 bg-yellow-600/80 rounded-full blur-sm animate-flame"></div>
                    
                    <!-- Inner Flame (updated positioning) -->
                    <div class="absolute left-1/2 top-[-20px] transform -translate-x-1/2 w-3 h-10 bg-white/90 rounded-full blur-[2px] animate-flame"></div>
                </div>
                
                <!-- Candle Body -->
                <div class="w-12 h-24 bg-gradient-to-b from-cream to-white mx-auto rounded-t-lg"></div>
                
                <!-- Candle Base -->
                <div class="w-16 h-3 bg-gradient-to-b from-cream to-yellow-600/20 mx-auto rounded-b-lg"></div>
            </div>
            
            <!-- Reflection/Glow -->
            <div class="absolute left-1/2 transform -translate-x-1/2 bottom-4 w-48 h-10 bg-yellow-600/30 rounded-full blur-xl"></div>
        </div>
        
        <!-- Loading Text -->
        <h3 class="text-white text-xl font-hedvig">Loading...</h3>
        
        <!-- Pulsing Dots -->
        <div class="flex justify-center mt-3 space-x-2">
            <div class="w-2 h-2 bg-yellow-600 rounded-full animate-pulse" style="animation-delay: 0s"></div>
            <div class="w-2 h-2 bg-yellow-600 rounded-full animate-pulse" style="animation-delay: 0.2s"></div>
            <div class="w-2 h-2 bg-yellow-600 rounded-full animate-pulse" style="animation-delay: 0.4s"></div>
        </div>
    </div>
</div>

<!-- Add this JavaScript code -->
<script>
    // Function to show the loading animation
    function showLoader() {
        const loader = document.getElementById('page-loader');
        loader.classList.remove('opacity-0', 'pointer-events-none');
        document.body.style.overflow = 'hidden'; // Prevent scrolling while loading
    }
    
    // Function to hide the loading animation
    function hideLoader() {
        const loader = document.getElementById('page-loader');
        loader.classList.add('opacity-0', 'pointer-events-none');
        document.body.style.overflow = ''; // Restore scrolling
    }
    
    // Add event listeners to all internal links
    document.addEventListener('DOMContentLoaded', function() {
        // Get all internal links that are not anchors
        const internalLinks = document.querySelectorAll('a[href]:not([href^="#"]):not([href^="http"]):not([href^="mailto"])');
        
        internalLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                // Check if it's a normal click (not control/command + click to open in new tab)
                if (!e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                    showLoader();
                    
                    // Store the href to navigate to
                    const href = this.getAttribute('href');
                    
                    // Delay navigation slightly to show the loader
                    setTimeout(() => {
                        window.location.href = href;
                    }, 800);
                }
            });
        });
        
        // Hide loader when back button is pressed
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                hideLoader();
            }
        });
    });
    
    // Hide loader when the page is fully loaded
    window.addEventListener('load', hideLoader);
    function toggleMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
}
</script>
        <?php include 'customService/chat_elements.html'; ?>

        <script src="customer_support.js"></script>

</body> 
</html> 