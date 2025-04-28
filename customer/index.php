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
                    'id_validation' => 0
                ];

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
$booking_redirect_url = 'services.php'; // Default for no booking

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
                        <?php echo htmlspecialchars($first_name . ' ' . $last_name); ?>
                    </span>

                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-card overflow-hidden invisible group-hover:visible transition-all duration-300 opacity-0 group-hover:opacity-100">
                        <div class="p-3 border-b border-gray-100">
                            <p class="text-sm font-medium text-navy"><?php echo htmlspecialchars($first_name . ' ' . $last_name); ?></p>
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
        <a href="profile.php#payment-info" class="text-sm text-yellow-600 hover:text-darkgold font-medium flex items-center">
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
                
                <?php if (!in_array('Branch Selected', array_column($check_icons, 'text'))): ?>
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
        
        <a href="profile.php#personal-info" class="text-sm text-yellow-600 hover:text-darkgold font-medium flex items-center">
            <?= $percentage < 100 ? 'Complete Profile' : 'View Profile' ?> <i class="fas fa-arrow-right ml-2"></i>
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
    <!-- Packages Section -->
<div class="mt-16">
    <h2 class="text-2xl font-hedvig text-navy">Packages</h2>
    <div class="flex justify-end mb-4">
        <a href="packages.php" class="text-yellow-600 hover:text-darkgold text-sm sm:text-l font-medium">View All Packages
           <i class="fa-solid fa-arrow-right ml-2"></i>
        </a>
    </div>
</div>

<div class="max-w-6xl mx-auto relative">
    <!-- Carousel Container -->
    <div class="overflow-hidden relative">
        <div id="carousel-container" class="flex transition-transform duration-500 ease-in-out">
            <!-- Legacy Tribute Package -->
            <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                <div class="bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="700000" data-service="traditional" data-name="Legacy Tribute" data-image="../image/700.jpg">
                    <div class="h-10 sm:h-12 bg-navy flex items-center justify-center">
                        <h4 class="text-white font-hedvig text-lg sm:text-xl">Legacy Tribute</h4>
                        <div class="absolute top-0 right-0 w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6 flex flex-col flex-grow">
                        <div class="mb-3 sm:mb-4 flex justify-center">
                            <img src="../image/700.jpg" alt="Legacy Tribute" class="w-full h-40 sm:h-48 object-cover rounded-lg">
                        </div>
                        <div class="text-center mb-4 sm:mb-6">
                            <span class="text-2xl sm:text-3xl font-hedvig text-navy">₱700,000</span>
                        </div>
                        <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 flex-grow text-sm sm:text-base">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">3 sets of flower changes</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Catering on the last day</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Premium casket selection</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Complete funeral arrangements</span>
                            </li>
                        </ul>
                        <button class="selectPackageBtn block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                            Select Package
                        </button>
                    </div>
                </div>
            </div>

            <!-- Eternal Remembrance Package -->
            <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                <div class="bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="300000" data-service="traditional" data-name="Eternal Remembrance" data-image="../image/300.jpg">
                    <div class="h-10 sm:h-12 bg-navy flex items-center justify-center">
                        <h4 class="text-white font-hedvig text-lg sm:text-xl">Eternal Remembrance</h4>
                        <div class="absolute top-0 right-0 w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6 flex flex-col flex-grow">
                        <div class="mb-3 sm:mb-4 flex justify-center">
                            <img src="../image/300.jpg" alt="Eternal Remembrance" class="w-full h-40 sm:h-48 object-cover rounded-lg">
                        </div>
                        <div class="text-center mb-4 sm:mb-6">
                            <span class="text-2xl sm:text-3xl font-hedvig text-navy">₱300,000</span>
                        </div>
                        <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 flex-grow text-sm sm:text-base">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">2 sets of flower changes</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Quality casket selection</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Complete funeral service</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Professional embalming</span>
                            </li>
                        </ul>
                        <button class="selectPackageBtn block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                            Select Package
                        </button>
                    </div>
                </div>
            </div>

            <!-- Heritage Memorial Package -->
            <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                <div class="bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="250000" data-service="traditional" data-name="Heritage Memorial" data-image="../image/250.jpg">
                    <div class="h-10 sm:h-12 bg-navy flex items-center justify-center">
                        <h4 class="text-white font-hedvig text-lg sm:text-xl">Heritage Memorial</h4>
                        <div class="absolute top-0 right-0 w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6 flex flex-col flex-grow">
                        <div class="mb-3 sm:mb-4 flex justify-center">
                            <img src="../image/250.jpg" alt="Heritage Memorial" class="w-full h-40 sm:h-48 object-cover rounded-lg">
                        </div>
                        <div class="text-center mb-4 sm:mb-6">
                            <span class="text-2xl sm:text-3xl font-hedvig text-navy">₱250,000</span>
                        </div>
                        <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 flex-grow text-sm sm:text-base">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">2 sets of flower changes</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Standard casket</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Complete funeral arrangements</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Professional embalming</span>
                            </li>
                        </ul>
                        <button class="selectPackageBtn block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                            Select Package
                        </button>
                    </div>
                </div>
            </div>

            <!-- Serene Passage Package -->
            <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                <div class="bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="200000" data-service="traditional" data-name="Serene Passage" data-image="../image/200.jpg">
                    <div class="h-10 sm:h-12 bg-navy flex items-center justify-center">
                        <h4 class="text-white font-hedvig text-lg sm:text-xl">Serene Passage</h4>
                        <div class="absolute top-0 right-0 w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6 flex flex-col flex-grow">
                        <div class="mb-3 sm:mb-4 flex justify-center">
                            <img src="../image/200.jpg" alt="Serene Passage" class="w-full h-40 sm:h-48 object-cover rounded-lg">
                        </div>
                        <div class="text-center mb-4 sm:mb-6">
                            <span class="text-2xl sm:text-3xl font-hedvig text-navy">₱200,000</span>
                        </div>
                        <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 flex-grow text-sm sm:text-base">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">2 sets of flower changes</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Basic casket</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Essential funeral service</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Professional embalming</span>
                            </li>
                        </ul>
                        <button class="selectPackageBtn block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                            Select Package
                        </button>
                    </div>
                </div>
            </div>

            <!-- Dignified Farewell Package -->
            <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                <div class="bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="180000" data-service="traditional" data-name="Dignified Farewell" data-image="../image/180.jpg">
                    <div class="h-10 sm:h-12 bg-navy flex items-center justify-center">
                        <h4 class="text-white font-hedvig text-lg sm:text-xl">Dignified Farewell</h4>
                        <div class="absolute top-0 right-0 w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6 flex flex-col flex-grow">
                        <div class="mb-3 sm:mb-4 flex justify-center">
                            <img src="../image/180.jpg" alt="Dignified Farewell" class="w-full h-40 sm:h-48 object-cover rounded-lg">
                        </div>
                        <div class="text-center mb-4 sm:mb-6">
                            <span class="text-2xl sm:text-3xl font-hedvig text-navy">₱180,000</span>
                        </div>
                        <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 flex-grow text-sm sm:text-base">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">2 sets of flower changes</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Basic casket</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Essential funeral service</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Professional embalming</span>
                            </li>
                        </ul>
                        <button class="selectPackageBtn block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                            Select Package
                        </button>
                    </div>
                </div>
            </div>

            <!-- Peaceful Journey Package -->
            <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                <div class="bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="150000" data-service="traditional" data-name="Peaceful Journey" data-image="../image/150.jpg">
                    <div class="h-10 sm:h-12 bg-navy flex items-center justify-center">
                        <h4 class="text-white font-hedvig text-lg sm:text-xl">Peaceful Journey</h4>
                        <div class="absolute top-0 right-0 w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center">
                            <div class="w-12 h-12 sm:w-16 sm:h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                        </div>
                    </div>
                    <div class="p-4 sm:p-6 flex flex-col flex-grow">
                        <div class="mb-3 sm:mb-4 flex justify-center">
                            <img src="../image/150.jpg" alt="Peaceful Journey" class="w-full h-40 sm:h-48 object-cover rounded-lg">
                        </div>
                        <div class="text-center mb-4 sm:mb-6">
                            <span class="text-2xl sm:text-3xl font-hedvig text-navy">₱150,000</span>
                        </div>
                        <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 flex-grow text-sm sm:text-base">
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">2 sets of flower changes</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Basic casket</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Essential funeral service</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                <span class="text-dark">Professional embalming</span>
                            </li>
                        </ul>
                        <button class="selectPackageBtn block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                            Select Package
                        </button>
                    </div>
                </div>
            </div>

            <!-- View All Packages Blur Card -->
            <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                <a href="packages.php" class="block h-full">
                    <div class="bg-white/30 backdrop-blur-md rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full border-2 border-dashed border-navy/40">
                        <div class="flex items-center justify-center h-full p-4 sm:p-6">
                            <div class="text-center">
                                <div class="mb-4 sm:mb-6 flex justify-center">
                                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-navy/10 flex items-center justify-center">
                                        <i class="fas fa-ellipsis-h text-2xl sm:text-3xl text-navy/60"></i>
                                    </div>
                                </div>
                                <h3 class="text-xl sm:text-2xl font-hedvig text-navy mb-3 sm:mb-4">View All Packages</h3>
                                <p class="text-dark/70 mb-4 sm:mb-6 text-sm sm:text-base">Explore our complete range of funeral service options</p>
                                <div class="inline-block bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-sm sm:text-base">
                                    View All
                                </div>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Navigation Arrows - Modified for smaller screens -->
    <button id="prev-btn" class="absolute left-0 top-1/2 transform -translate-y-1/2 -ml-2 sm:-ml-4 bg-yellow-600 w-8 h-8 sm:w-10 sm:h-10 rounded-full shadow-lg flex items-center justify-center border border-gray-200 z-20 hover:bg-yellow-700 focus:outline-none">
        <i class="fas fa-chevron-left text-white text-xs sm:text-base"></i>
    </button>
    <button id="next-btn" class="absolute right-0 top-1/2 transform -translate-y-1/2 -mr-2 sm:-mr-4 bg-yellow-600 w-8 h-8 sm:w-10 sm:h-10 rounded-full shadow-lg flex items-center justify-center border border-gray-200 z-20 hover:bg-yellow-700 focus:outline-none">
        <i class="fas fa-chevron-right text-white text-xs sm:text-base"></i>
    </button>

    <!-- Dots Indicator -->
    <div class="flex justify-center mt-4 sm:mt-6 mb-4 sm:mb-8">
        <div id="carousel-dots" class="flex space-x-2">
            <button class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-navy opacity-100" data-index="0"></button>
            <button class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-navy opacity-50" data-index="1"></button>
            <button class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-navy opacity-50" data-index="2"></button>
            <button class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-navy opacity-50" data-index="3"></button>
            <button class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-navy opacity-50" data-index="4"></button>
        </div>
    </div>
</div>

<!-- Carousel JavaScript - Updated for better responsiveness -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const carouselContainer = document.getElementById('carousel-container');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const dots = document.querySelectorAll('#carousel-dots button');
        
        let currentIndex = 0;
        const itemCount = 7; // Total number of items (6 packages + view all)
        let itemsPerView = window.innerWidth >= 768 ? 3 : 1; // Show 3 items on medium screens, 1 on small
        let maxIndex = itemCount - itemsPerView;
        
        // Initially hide prev button if at start
        prevBtn.style.display = currentIndex === 0 ? 'none' : 'flex';
        
        // Function to update carousel position
        function updateCarousel() {
            const translateValue = currentIndex * -100 / itemsPerView;
            carouselContainer.style.transform = `translateX(${translateValue}%)`;
            
            // Update dots
            dots.forEach((dot, index) => {
                dot.classList.toggle('opacity-100', index === currentIndex);
                dot.classList.toggle('opacity-50', index !== currentIndex);
            });
            
            // Show/hide prev button based on position
            prevBtn.style.display = currentIndex === 0 ? 'none' : 'flex';
            
            // Show/hide next button based on position
            nextBtn.style.display = currentIndex >= maxIndex ? 'none' : 'flex';
        }
        
        // Click handlers for navigation buttons
        nextBtn.addEventListener('click', function() {
            if (currentIndex < maxIndex) {
                currentIndex++;
                updateCarousel();
            }
        });
        
        prevBtn.addEventListener('click', function() {
            if (currentIndex > 0) {
                currentIndex--;
                updateCarousel();
            }
        });
        
        // Click handlers for dots
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                currentIndex = Math.min(index, maxIndex);
                updateCarousel();
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            const newItemsPerView = window.innerWidth >= 768 ? 3 : 1;
            
            // Update variables without reload
            if (newItemsPerView !== itemsPerView) {
                itemsPerView = newItemsPerView;
                maxIndex = itemCount - itemsPerView;
                
                // Reset to first slide if current position would be invalid
                if (currentIndex > maxIndex) {
                    currentIndex = maxIndex;
                }
                
                updateCarousel();
            }
        });
        
        // Initial setup
        updateCarousel();
        
        // Add touch swipe support for mobile
        let touchStartX = 0;
        let touchEndX = 0;
        
        carouselContainer.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, false);
        
        carouselContainer.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, false);
        
        function handleSwipe() {
            const swipeThreshold = 50;
            if (touchEndX < touchStartX - swipeThreshold) {
                // Swipe left - go next
                if (currentIndex < maxIndex) {
                    currentIndex++;
                    updateCarousel();
                }
            } else if (touchEndX > touchStartX + swipeThreshold) {
                // Swipe right - go back
                if (currentIndex > 0) {
                    currentIndex--;
                    updateCarousel();
                }
            }
        }
    });
</script>
    </main>

    <!-- Traditional Funeral Modal (Hidden by Default) -->
<div id="traditionalModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[90vh] md:max-h-[80vh]">
        <!-- Scroll container for both columns -->
        <div class="modal-scroll-container grid grid-cols-1 md:grid-cols-2 overflow-y-auto max-h-[90vh] md:max-h-[80vh]">
            <!-- Left Side: Package Details -->
            <div class="bg-cream p-4 md:p-8 details-section">
                <!-- Header and Close Button for Mobile -->
                <div class="flex justify-between items-center mb-4 md:hidden">
                    <h2 class="text-xl font-hedvig text-navy">Package Details</h2>
                    <button class="closeModalBtn text-gray-500 hover:text-navy">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <!-- Package Image -->
                <div class="mb-4 md:mb-6">
                    <img id="traditionalPackageImage" src="" alt="" class="w-full h-48 md:h-64 object-cover rounded-lg mb-4">
                </div>

                <!-- Package Header -->
                <div class="flex justify-between items-center mb-4 md:mb-6">
                    <h2 id="traditionalPackageName" class="text-2xl md:text-3xl font-hedvig text-navy"></h2>
                    <div id="traditionalPackagePrice" class="text-2xl md:text-3xl font-hedvig text-yellow-600"></div>
                </div>

                <!-- Package Description -->
                <p id="traditionalPackageDesc" class="text-dark mb-4 md:mb-6 text-sm md:text-base"></p>

                <!-- Main Package Details -->
                <div class="border-t border-gray-200 pt-4">
                    <h3 class="text-lg md:text-xl font-hedvig text-navy mb-3 md:mb-4">Package Includes:</h3>
                    <ul id="traditionalPackageFeatures" class="space-y-1 md:space-y-2">
                        <!-- Features will be inserted here by JavaScript -->
                    </ul>
                </div>

                <div class="border-t border-gray-200 pt-4 mt-4 md:mt-6">
                    <h3 class="text-lg md:text-xl font-hedvig text-navy mb-3 md:mb-4">Additional Services:</h3>
                    <div id="traditionalAdditionalServices" class="space-y-2 md:space-y-3">
                        <div class="flex items-center">
                            <input type="checkbox" id="traditionalFlowers" name="additionalServices" value="3500" class="traditional-addon h-4 md:h-5 w-4 md:w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Floral Arrangements">
                            <label for="traditionalFlowers" class="ml-2 md:ml-3 text-xs md:text-sm text-gray-700">Floral Arrangements (₱3,500)</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="traditionalCatering" name="additionalServices" value="15000" class="traditional-addon h-4 md:h-5 w-4 md:w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Catering Service (50 pax)">
                            <label for="traditionalCatering" class="ml-2 md:ml-3 text-xs md:text-sm text-gray-700">Catering Service - 50 pax (₱15,000)</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="traditionalVideography" name="additionalServices" value="7500" class="traditional-addon h-4 md:h-5 w-4 md:w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Video Memorial Service">
                            <label for="traditionalVideography" class="ml-2 md:ml-3 text-xs md:text-sm text-gray-700">Video Memorial Service (₱7,500)</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="traditionalTransport" name="additionalServices" value="4500" class="traditional-addon h-4 md:h-5 w-4 md:w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Additional Transportation">
                            <label for="traditionalTransport" class="ml-2 md:ml-3 text-xs md:text-sm text-gray-700">Additional Transportation (₱4,500)</label>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" id="traditionalUrn" name="additionalServices" value="6000" class="traditional-addon h-4 md:h-5 w-4 md:w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Premium Urn Upgrade">
                            <label for="traditionalUrn" class="ml-2 md:ml-3 text-xs md:text-sm text-gray-700">Premium Urn Upgrade (₱6,000)</label>
                        </div>
                    </div>
                </div>
                
                <!-- Mobile-only summary and navigation button -->
                <div class="mt-6 border-t border-gray-200 pt-4 md:hidden">
                    <div class="bg-white p-4 rounded-lg shadow-sm">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-navy">Package Total</span>
                            <span id="traditionalTotalPriceMobile" class="text-yellow-600">₱0</span>
                        </div>
                        <div class="flex justify-between font-bold mt-2 pt-2 border-t border-gray-300">
                            <span class="text-navy">Amount Due Now (30%)</span>
                            <span id="traditionalAmountDueMobile" class="text-yellow-600">₱0</span>
                        </div>
                    </div>
                    <button id="continueToFormBtn" class="mt-4 w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300">
                        Continue to Booking
                    </button>
                </div>
            </div>

            <!-- Right Side: Traditional Booking Form -->
            <div class="bg-white p-4 md:p-8 border-t md:border-t-0 md:border-l border-gray-200 overflow-y-auto form-section md:block">
                <!-- Header and back button for mobile -->
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl md:text-2xl font-hedvig text-navy">Book Your Package</h2>
                    <div class="flex items-center">
                        <button id="backToDetailsBtn" class="mr-2 text-gray-500 hover:text-navy md:hidden">
                            <i class="fas fa-arrow-left text-lg"></i>
                        </button>
                        <button class="closeModalBtn text-gray-500 hover:text-navy">
                            <i class="fas fa-times text-xl md:text-2xl"></i>
                        </button>
                    </div>
                </div>

                <form id="traditionalBookingForm" class="space-y-4">
                    <input type="hidden" id="traditionalSelectedPackageName" name="packageName">
                    <input type="hidden" id="traditionalSelectedPackagePrice" name="packagePrice">
                    
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Deceased Information</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 mb-3 md:mb-4">
                            <div>
                                <label for="traditionalDeceasedFirstName" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">First Name *</label>
                                <input type="text" id="traditionalDeceasedFirstName" name="deceasedFirstName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDeceasedMiddleName" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Middle Name</label>
                                <input type="text" id="traditionalDeceasedMiddleName" name="deceasedMiddleName" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 mb-3 md:mb-4">
                            <div>
                                <label for="traditionalDeceasedLastName" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Last Name *</label>
                                <input type="text" id="traditionalDeceasedLastName" name="deceasedLastName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDeceasedSuffix" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Suffix</label>
                                <input type="text" id="traditionalDeceasedSuffix" name="deceasedSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-4">
                            <div>
                                <label for="traditionalDateOfBirth" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Date of Birth</label>
                                <input type="date" id="traditionalDateOfBirth" name="dateOfBirth" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDateOfDeath" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Date of Death *</label>
                                <input type="date" id="traditionalDateOfDeath" name="dateOfDeath" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDateOfBurial" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Date of Burial</label>
                                <input type="date" id="traditionalDateOfBurial" name="dateOfBurial" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="mt-3 md:mt-4">
                            <label for="traditionalDeathCertificate" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Death Certificate</label>
                            <input type="file" id="traditionalDeathCertificate" name="deathCertificate" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs md:text-sm px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                        </div>
                        <div class="mt-3 md:mt-4">
                            <label for="traditionalDeceasedAddress" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Address of the Deceased</label>
                            <textarea id="traditionalDeceasedAddress" name="deceasedAddress" rows="2" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600"></textarea>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Payment</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4">
                            <div>
                                <label for="traditionalGcashReceipt" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">GCash Receipt *</label>
                                <input type="file" id="traditionalGcashReceipt" name="gcashReceipt" accept=".pdf,.jpg,.jpeg,.png" required class="w-full text-xs md:text-sm px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalReferenceNumber" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">GCash Reference Number *</label>
                                <input type="text" id="traditionalReferenceNumber" name="referenceNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                    </div>

                    <div class="bg-cream p-3 md:p-4 rounded-lg">
                        <div class="flex justify-between text-xs md:text-sm mb-2">
                            <span class="text-navy">Package Total</span>
                            <span id="traditionalTotalPrice" class="text-yellow-600">₱0</span>
                        </div>
                        <div class="flex justify-between text-xs md:text-sm mb-2">
                            <span class="text-navy">Required Downpayment (30%)</span>
                            <span id="traditionalDownpayment" class="text-yellow-600">₱0</span>
                        </div>
                        <div class="flex justify-between font-bold mt-2 pt-2 border-t border-gray-300">
                            <span class="text-navy">Amount Due Now</span>
                            <span id="traditionalAmountDue" class="text-yellow-600">₱0</span>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300">
                        Confirm Booking
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Add initial mobile class setup
    if (window.innerWidth < 768) {
        document.querySelector('.form-section').classList.add('hidden');
    }
    
    // Show traditional modal directly when package is selected
    document.querySelectorAll('.selectPackageBtn').forEach(button => {
        button.addEventListener('click', function() {
            // Get package details from the parent card
            const packageCard = this.closest('[data-name]');
            if (!packageCard) return;
            
            const packageName = packageCard.dataset.name;
            const packagePrice = packageCard.dataset.price;
            const packageImage = packageCard.dataset.image || '';
            const packageDesc = packageCard.dataset.description || '';
            
            // Get features from the card
            const features = [];
            const featureItems = packageCard.querySelectorAll('ul li');
            featureItems.forEach(item => {
                features.push(item.innerHTML);
            });
            
            // Update modal with package details
            document.getElementById('traditionalPackageName').textContent = packageName;
            document.getElementById('traditionalPackagePrice').textContent = `₱${parseInt(packagePrice).toLocaleString()}`;
            document.getElementById('traditionalPackageDesc').textContent = packageDesc;
            
            if (packageImage) {
                document.getElementById('traditionalPackageImage').src = packageImage;
                document.getElementById('traditionalPackageImage').alt = packageName;
            }
            
            // Update features list
            const featuresList = document.getElementById('traditionalPackageFeatures');
            featuresList.innerHTML = '';
            features.forEach(feature => {
                featuresList.innerHTML += `<li class="flex items-center text-xs md:text-sm text-gray-700">${feature}</li>`;
            });
            
            // Calculate and display prices
            const totalPrice = parseInt(packagePrice);
            const downpayment = Math.ceil(totalPrice * 0.3);
            
            document.getElementById('traditionalTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
            document.getElementById('traditionalDownpayment').textContent = `₱${downpayment.toLocaleString()}`;
            document.getElementById('traditionalAmountDue').textContent = `₱${downpayment.toLocaleString()}`;
            
            // Update mobile price displays
            document.getElementById('traditionalTotalPriceMobile').textContent = `₱${totalPrice.toLocaleString()}`;
            document.getElementById('traditionalAmountDueMobile').textContent = `₱${downpayment.toLocaleString()}`;
            
            // Update hidden form fields
            document.getElementById('traditionalSelectedPackageName').value = packageName;
            document.getElementById('traditionalSelectedPackagePrice').value = totalPrice;
            
            // Reset addons
            document.querySelectorAll('.traditional-addon').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Setup mobile view
            if (window.innerWidth < 768) {
                document.querySelector('.details-section').classList.remove('hidden');
                document.querySelector('.form-section').classList.add('hidden');
            }
            
            // Show the modal
            document.getElementById('traditionalModal').classList.remove('hidden');
        });
    });

    // Setup mobile navigation buttons
    document.getElementById('continueToFormBtn').addEventListener('click', function() {
        document.querySelector('.details-section').classList.add('hidden');
        document.querySelector('.form-section').classList.remove('hidden');
    });
    
    document.getElementById('backToDetailsBtn').addEventListener('click', function() {
        document.querySelector('.form-section').classList.add('hidden');
        document.querySelector('.details-section').classList.remove('hidden');
    });

    // Handle addon changes
    document.querySelectorAll('.traditional-addon').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateTraditionalTotal();
        });
    });

    // Function to update traditional total price when addons are selected
    function updateTraditionalTotal() {
        const basePrice = parseInt(document.getElementById('traditionalSelectedPackagePrice').value || '0');
        let addonTotal = 0;
        
        // Calculate addons total
        document.querySelectorAll('.traditional-addon:checked').forEach(checkbox => {
            addonTotal += parseInt(checkbox.value);
        });
        
        // Update totals
        const totalPrice = basePrice + addonTotal;
        const downpayment = Math.ceil(totalPrice * 0.3);
        
        document.getElementById('traditionalTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('traditionalDownpayment').textContent = `₱${downpayment.toLocaleString()}`;
        document.getElementById('traditionalAmountDue').textContent = `₱${downpayment.toLocaleString()}`;
        
        // Update mobile price displays
        document.getElementById('traditionalTotalPriceMobile').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('traditionalAmountDueMobile').textContent = `₱${downpayment.toLocaleString()}`;
    }

    // Close modal when close button is clicked
    document.querySelectorAll('.closeModalBtn').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('traditionalModal').classList.add('hidden');
        });
    });

    // Close modal when clicking outside
    document.getElementById('traditionalModal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });

    // Form submission for Traditional
    document.getElementById('traditionalBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        // Add booking submission logic here
        alert('Traditional service booking submitted successfully!');
        document.getElementById('traditionalModal').classList.add('hidden');
    });
    
    // Handle resize events
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) {
            // Desktop view - show both sections
            document.querySelector('.details-section').classList.remove('hidden');
            document.querySelector('.form-section').classList.remove('hidden');
        } else {
            // Mobile view - show details by default, hide form
            if (!document.getElementById('traditionalModal').classList.contains('hidden')) {
                document.querySelector('.details-section').classList.remove('hidden');
                document.querySelector('.form-section').classList.add('hidden');
            }
        }
    });
});

function toggleMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
}
</script>


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
        <?php include 'customService/chat_elements.html'; ?>

        <script src="customer_support.js"></script>

</body> 
</html> 