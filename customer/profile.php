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

                require_once '../addressDB.php';
                require_once '../db_connect.php'; // Database connection

                // Get user's first name from database
                $user_id = $_SESSION['user_id'];
                $query = "SELECT first_name, middle_name, last_name, email, phone_number, birthdate, 
                        region, city, province, barangay, street_address, zip_code FROM users WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $first_name = $row['first_name']; // We're confident user_id exists
                $last_name = $row['last_name'];
                $middle_name = $row['middle_name'];
                $email = $row['email'];
                $phone_number = $row['phone_number'];
                $birthdate = $row['birthdate'];
                $region = $row['region'];
                $city = $row['city'];
                $province = $row['province'];
                $barangay = $row['barangay'];
                $street_address = $row['street_address'];
                $zip_code = $row['zip_code'];

                $uploadedImagePath = null;
                
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

                

                // Fetch the uploaded ID image from valid_id_tb
                $query = "SELECT image_path FROM valid_id_tb WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->bind_result($uploadedImagePath);
                $stmt->fetch();

                $stmt->close();

                // Get regions for dropdown
                $regions = [];
                $region_query = "SELECT region_id, region_name FROM table_region";
                $region_result = $addressDB->query($region_query);
                while ($row = $region_result->fetch_assoc()) {
                    $regions[] = $row;
                }

 
                $addressDB->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Profile</title>
    <?php include 'faviconLogo.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../tailwind.js"></script>
    <script src="profile.js"></script>    
    <!-- Add this in the head section of your HTML document -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.32/dist/sweetalert2.all.min.js"></script>
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
        :root {
            --navbar-height: 64px; /* Define the height of the navbar */
            --section-spacing: 4rem; /* Standardized spacing between sections */
        }
        /* Additional styles for scrollbar */
.modal-scroll-container {
    scrollbar-width: thin;
    scrollbar-color: #d4a933 #f5f5f5;
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

 /* Add this to your existing styles */
 .sticky-sidebar {
        position: sticky;
        top: calc(var(--navbar-height) + 1rem); /* Position below navbar with some spacing */
        align-self: flex-start; /* Prevent stretching */
        height: calc(100vh - var(--navbar-height) - 1rem); /* Full height minus navbar */
        overflow-y: auto; /* Enable scrolling if content is too long */
    }
    /* Add this to your existing styles */
    .tab-header {
        padding: 1.5rem; /* p-6 equivalent to match left nav */
        height: 72px; /* Match the height of left nav header */
        display: flex;
        align-items: center;
    }
    
    /* Adjust the specific tab headers */
    #personal-info .bg-navy,
    #bookings .bookings {
        padding: 1.5rem !important;
        height: 72px !important;
        display: flex !important;
        align-items: center !important;
    }
    /* Add this to your existing styles */
    .modal-sticky-footer {
        position: sticky;
        bottom: 0;
        background: white;
        padding: 1rem;
        border-top: 1px solid #e5e7eb;
        margin-top: auto; /* Pushes footer to bottom */
    }
    
    /* Ensure modal content container has proper flex layout */
    .modal-content-container {
        display: flex;
        flex-direction: column;
        min-height: 100%;
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

    <!-- Profile Page with GrievEase Design -->
    <div class="min-h-screen bg-cream">
        <!-- Header Banner with Gradient Background -->
        <div class="relative w-full h-64 overflow-hidden mt-[var(--navbar-height)]">
            <!-- Background Image with Advanced Gradient Overlay -->
            <div class="absolute inset-0 bg-center bg-cover bg-no-repeat transition-transform duration-10000 ease-in-out hover:scale-105"
                 style="background-image: url('../Landing_Page/Landing_images/black-bg-image.jpg');">
                <!-- Multi-layered gradient overlay for depth and dimension -->
                <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/40 to-black/80"></div>
            </div>
            
            <!-- Profile Info Positioned at Bottom of Banner -->
            <div class="absolute bottom-0 left-0 right-0 p-6 md:p-12 z-10 flex items-end">
                <div class="relative">
                    <div class="absolute -top-16 w-24 h-24 rounded-full border-4 border-cream overflow-hidden bg-white">
                        <div class="w-full h-full bg-gray-300 flex items-center justify-center text-3xl font-bold text-gray-600">
                            <?php 
                                // Get initials from first letter of first_name and first letter of last_name
                                $initials = strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); 
                                echo htmlspecialchars($initials);
                            ?>
                        </div>
                    </div>
                    <div class="ml-32">
                        <h1 class="font-hedvig text-3xl text-white"><?php echo htmlspecialchars(ucwords($first_name . ' ' . $last_name)); ?></h1>
                        <p class="text-white/80">
                            Member since <?php 
                                // Format the created_at date (assuming it's stored in your $row variable)
                                $created_at = isset($row['created_at']) ? $row['created_at'] : date('Y-m-d H:i:s');
                                echo date('F Y', strtotime($created_at)); 
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Decorative Elements -->
            <div class="absolute top-6 right-6 w-16 h-16 border-t-2 border-r-2 border-white/20 pointer-events-none"></div>
            <div class="absolute bottom-6 left-6 w-16 h-16 border-b-2 border-l-2 border-white/20 pointer-events-none"></div>
        </div>
        
        <div class="container mx-auto px-6 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Sidebar with Navigation -->
            <div class="lg:col-span-1 sticky-sidebar">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6 border-b bg-navy border-gray-100">
                        <h3 class="font-hedvig text-xl text-white">Account Management</h3>
                    </div>
                    <nav class="p-4">
                        <ul class="space-y-1">
                            <li>
                                <a href="#" class="profile-tab flex items-center p-3 rounded-lg bg-yellow-600/10 text-yellow-600" data-tab="personal-info">
                                    <i class="fas fa-user-circle mr-3"></i>
                                    <span>Personal Information</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="profile-tab flex items-center p-3 rounded-lg hover:bg-gray-50 text-navy" data-tab="bookings">
                                    <i class="fas fa-calendar-check mr-3"></i>
                                    <span>My Bookings</span>
                                </a>
                            </li>
                            <li>
                                <a href="#" class="profile-tab flex items-center p-3 rounded-lg hover:bg-gray-50 text-navy" data-tab="transaction-logs">
                                    <i class="fas fa-credit-card mr-3"></i>
                                    <span>Payment History</span>
                                </a>
                            </li>
                            <li>
                                <a href="../logout.php" class="flex items-center p-3 rounded-lg hover:bg-gray-50 text-red-500">
                                    <i class="fas fa-sign-out-alt mr-3"></i>
                                    <span>Log Out</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>
    </div>
            
            <!-- Right Content Area -->
            <div class="lg:col-span-2">
    <!-- Personal Information Tab -->
<div id="personal-info" class="tab-content">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <!-- Header with enhanced styling - now stacked on mobile -->
        <div class="bg-navy p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 tab-header">
            <h3 class="font-hedvig text-xl sm:text-2xl text-white font-semibold">Personal Information</h3>
            
            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 sm:space-x-0 w-full sm:w-auto">
                <button id="open-change-password-modal" class="px-3 py-2 sm:px-4 sm:py-2 bg-white border border-yellow-700 text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center text-sm sm:text-base">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 inline">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    Change Password
                </button>
                <button id="edit-profile-btn" class="px-3 py-2 sm:px-4 sm:py-2 bg-yellow-600 hover:bg-darkgold text-white rounded-md transition-colors flex items-center justify-center text-sm sm:text-base">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Edit Profile
                </button>
            </div>
        </div>
        
        <!-- Content area with improved spacing and grouping -->
        <div class="p-4 sm:p-6 md:p-8">
            <!-- Information sections with card-based layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                <!-- Basic Information section with visual grouping -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="bg-navy bg-opacity-10 px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200">
                        <h4 class="flex items-center text-navy text-base sm:text-lg font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Basic Information
                        </h4>
                    </div>
                    <div class="p-4 sm:p-6 space-y-3 sm:space-y-4">
                        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Full Name</label>
                            <div class="flex flex-wrap gap-1 sm:gap-2">
                                <span class="text-navy font-medium text-base sm:text-lg"><?php echo htmlspecialchars(ucwords($first_name)); ?></span>
                                <span class="text-navy <?= empty($middle_name) ? 'hidden' : 'font-medium text-base sm:text-lg' ?>">
                                    <?= !empty($middle_name) ? htmlspecialchars(ucwords($middle_name)) : '' ?>
                                </span>
                                <span class="text-navy font-medium text-base sm:text-lg"><?php echo htmlspecialchars(ucwords($last_name)); ?></span>
                            </div>
                        </div>
                        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Date of Birth</label>
                            <p class="text-navy font-medium flex items-center text-sm sm:text-base">
                                <?php echo date('F d, Y', strtotime($birthdate)); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Contact section -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="bg-navy bg-opacity-10 px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200">
                        <h4 class="flex items-center text-navy text-base sm:text-lg font-semibold">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                            Contact Information
                        </h4>
                    </div>
                    <div class="p-4 sm:p-6 space-y-3 sm:space-y-4">
                        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Email Address</label>
                            <p class="text-navy font-medium flex items-center text-sm sm:text-base">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                </svg>
                                <?php echo htmlspecialchars($email); ?>
                            </p>
                        </div>
                        <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                            <label class="block text-xs font-medium text-gray-500 mb-1">Phone Number</label>
                            <p class="text-navy <?= empty($phone_number) ? 'opacity-60 italic text-gray-500' : 'font-medium' ?> flex items-center text-sm sm:text-base">
                                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 sm:mr-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"></path>
                                </svg>
                                <?= !empty($phone_number) ? htmlspecialchars($phone_number) : 'N/A' ?>
                            </p>
                        </div>
                    </div>
                </div>
                
               <!-- Address section taking full width -->
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden col-span-1 lg:col-span-2">
    <div class="bg-navy bg-opacity-10 px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200">
        <h4 class="flex items-center text-navy text-base sm:text-lg font-semibold">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Address Information
        </h4>
    </div>
    <div class="p-4 sm:p-6">
        <!-- Grid layout -->
        <div class="grid grid-cols-2 gap-3 sm:gap-4">
            <!-- Region -->
            <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                <label class="block text-xs font-medium text-gray-500 mb-1">Region</label>
                <p class="text-navy <?= empty($region) ? 'opacity-60 italic text-gray-500' : 'font-medium' ?> text-sm sm:text-base">
                    <?= !empty($region) ? htmlspecialchars($region) : 'N/A' ?>
                </p>
            </div>
            
            <!-- Province -->
            <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                <label class="block text-xs font-medium text-gray-500 mb-1">Province</label>
                <p class="text-navy <?= empty($province) ? 'opacity-60 italic text-gray-500' : 'font-medium' ?> text-sm sm:text-base">
                    <?= !empty($province) ? htmlspecialchars($province) : 'N/A' ?>
                </p>
            </div>
            
            <!-- City -->
            <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                <label class="block text-xs font-medium text-gray-500 mb-1">City</label>
                <p class="text-navy <?= empty($city) ? 'opacity-60 italic text-gray-500' : 'font-medium' ?> text-sm sm:text-base">
                    <?= !empty($city) ? htmlspecialchars($city) : 'N/A' ?>
                </p>
            </div>
            
            <!-- Barangay -->
            <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                <label class="block text-xs font-medium text-gray-500 mb-1">Barangay</label>
                <p class="text-navy <?= empty($barangay) ? 'opacity-60 italic text-gray-500' : 'font-medium' ?> text-sm sm:text-base">
                    <?= !empty($barangay) ? htmlspecialchars($barangay) : 'N/A' ?>
                </p>
            </div>
            
            <!-- Street Address (always 2 columns, even on small screens) -->
            <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                <label class="block text-xs font-medium text-gray-500 mb-1">Street Address</label>
                <p class="text-navy <?= empty($street_address) ? 'opacity-60 italic text-gray-500' : 'font-medium' ?> text-sm sm:text-base">
                    <?= !empty($street_address) ? htmlspecialchars($street_address) : 'N/A' ?>
                </p>
            </div>
            
            <!-- Zip Code (always 2 columns, even on small screens) -->
            <div class="bg-gray-50 p-3 sm:p-4 rounded-lg">
                <label class="block text-xs font-medium text-gray-500 mb-1">Zip Code</label>
                <p class="text-navy <?= empty($zip_code) ? 'opacity-60 italic text-gray-500' : 'font-medium' ?> text-sm sm:text-base">
                    <?= !empty($zip_code) ? htmlspecialchars($zip_code) : 'N/A' ?>
                </p>
            </div>
        </div>
        
        <?php if (!empty($street_address) && !empty($city)): ?>
        <!-- Complete Address (full width) -->
        <div class="mt-3 sm:mt-4">
            <div class="bg-gray-50 p-3 sm:p-4 rounded-lg border border-gray-200">
                <div class="flex items-center mb-1 sm:mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 text-navy mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                    </svg>
                    <label class="text-xs sm:text-sm font-medium text-gray-700">Complete Address</label>
                </div>
                <p class="text-navy font-medium text-sm sm:text-base">
                    <?php 
                        $address_parts = array_filter([
                            $street_address,
                            $barangay,
                            $city,
                            $province,
                            $region,
                            $zip_code
                        ]);
                        echo htmlspecialchars(implode(', ', $address_parts));
                    ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
                    </div>
            
            <!-- ID Upload Section with improved layout -->
            <div class="mt-6 sm:mt-8 pt-4 sm:pt-6 border-t border-gray-200">
                <div class="flex items-center mb-4 sm:mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6 text-navy mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                    </svg>
                    <h3 class="font-hedvig text-lg sm:text-xl text-navy font-semibold">Identity Verification</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <div class="bg-white p-3 sm:p-5 rounded-lg border border-gray-200 shadow-sm">
                        <?php if ($uploadedImagePath): ?>
                            <?php
                                // Fetch the validation status and decline reason from valid_id_tb
                                $status_query = "SELECT is_validated, decline_reason FROM valid_id_tb WHERE id = ?";
                                $status_stmt = $conn->prepare($status_query);
                                $status_stmt->bind_param("i", $user_id);
                                $status_stmt->execute();
                                $status_result = $status_stmt->get_result();
                                $status_row = $status_result->fetch_assoc();
                                $id_status = $status_row ? $status_row['is_validated'] : 'no';
                                $decline_reason = $status_row ? $status_row['decline_reason'] : '';
                                $status_stmt->close();
                                
                                // Define status label style based on status value
                                switch ($id_status) {
                                    case 'no':
                                        $statusText = 'PENDING';
                                        $statusClass = 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                                        $iconColor = 'text-yellow-500';
                                        $icon = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>';
                                        break;
                                    case 'valid':
                                        $statusText = 'APPROVED';
                                        $statusClass = 'bg-green-100 text-green-800 border border-green-200';
                                        $iconColor = 'text-green-500';
                                        $icon = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>';
                                        break;
                                    case 'denied':
                                        $statusText = 'DECLINED';
                                        $statusClass = 'bg-red-100 text-red-800 border border-red-200';
                                        $iconColor = 'text-red-500';
                                        $icon = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>';
                                        break;
                                    default:
                                        $statusText = 'PENDING';
                                        $statusClass = 'bg-yellow-100 text-yellow-800 border border-yellow-200';
                                        $iconColor = 'text-yellow-500';
                                        $icon = '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>';
                                        break;
                                }
                            ?>
                            
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3 sm:mb-4 bg-gray-50 p-2 sm:p-3 rounded-lg">
                                <div class="flex items-center">
                                    <span class="text-xs sm:text-sm font-medium text-gray-700 mr-2">Verification Status:</span>
                                    <span class="inline-flex items-center px-2 py-0.5 sm:px-3 sm:py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1 <?php echo $iconColor; ?>" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <?php echo $icon; ?>
                                        </svg>
                                        <?php echo $statusText; ?>
                                    </span>
                                </div>
                                
                                <?php if ($id_status === 'denied' && $decline_reason): ?>
                                    <button 
                                        class="text-red-600 hover:text-red-800 text-xs sm:text-sm flex items-center transition-colors" 
                                        onclick="openDeclineReasonModal('<?php echo htmlspecialchars($decline_reason); ?>')"
                                    >
                                        <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" clip-rule="evenodd"></path>
                                        </svg>
                                        View Details
                                    </button>
                                <?php endif; ?>
                            </div>
                            
                            <div class="relative group">
                                <!-- Container with enhanced hover effect -->
                                <div class="relative border-2 border-gray-200 rounded-lg overflow-hidden transition-all duration-300 group-hover:border-blue-400 shadow-sm group-hover:shadow-md">
                                    <!-- Thumbnail image that opens the modal when clicked -->
                                    <img 
                                        src="<?php echo '../uploads/valid_ids/' . htmlspecialchars($uploadedImagePath); ?>" 
                                        alt="Uploaded ID"
                                        class="w-full h-auto cursor-pointer hover:opacity-90 transition-opacity"
                                        onclick="openImageModal('<?php echo '../uploads/valid_ids/' . htmlspecialchars($uploadedImagePath); ?>')"
                                    >
                                    
                                    <!-- Enhanced overlay with view button -->
                                    <div class="absolute inset-0 bg-navy bg-opacity-70 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                        <button class="bg-white text-navy px-3 py-1 sm:px-4 sm:py-2 rounded-lg shadow-lg flex items-center transition-transform transform hover:scale-105 hover:bg-yellow-50 text-xs sm:text-sm"
                                            onclick="openImageModal('<?php echo '../uploads/valid_ids/' . htmlspecialchars($uploadedImagePath); ?>')">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                            View Full Size
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Enhanced caption -->
                                <p class="text-xs text-gray-500 mt-1 sm:mt-2 text-center">Click on the image to view in full size</p>
                            </div>
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center py-6 sm:py-10 px-3 sm:px-4 border-2 border-dashed border-gray-300 rounded-lg text-center cursor-pointer hover:bg-gray-50 transition-colors"
                                 onclick="openEditProfileToIDUpload()">
                                 <div class="bg-yellow-50 p-2 sm:p-3 rounded-full mb-3 sm:mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 sm:h-10 sm:w-10 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <p class="text-navy font-medium mb-1 text-sm sm:text-base">No ID uploaded yet</p>
                                <p class="text-gray-600 text-xs sm:text-sm mb-3 sm:mb-4">Upload a valid ID to verify your account</p>
                                <button class="px-3 py-1.5 sm:px-4 sm:py-2 bg-yellow-600 hover:bg-darkgold text-white rounded-md transition-colors flex items-center text-xs sm:text-sm">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0l-4 4m4-4v12" />
                                    </svg>
                                    Upload ID Now
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="hidden md:block">
                        <div class="bg-blue-50 p-4 sm:p-5 rounded-lg border border-blue-100 h-full">
                            <h4 class="font-medium text-navy mb-2 sm:mb-3 flex items-center text-sm sm:text-base">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-5 sm:w-5 mr-1 sm:mr-2 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                ID Upload Guidelines
                            </h4>
                            <ul class="space-y-1.5 sm:space-y-2 text-xs sm:text-sm text-gray-600">
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 text-green-500 mt-0.5 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Upload a clear, well-lit photo showing the entire ID
                                </li>
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 text-green-500 mt-0.5 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Ensure all four corners and edges are visible
                                </li>
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 text-green-500 mt-0.5 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    All text should be sharp and readable
                                </li>
                                <li class="flex items-start">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 sm:h-4 sm:w-4 text-green-500 mt-0.5 mr-1 sm:mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Avoid glare, shadows, or reflections
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Modal for enlarged image - improved version -->
<div id="imageModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-80 flex items-center justify-center p-4">
    <div class="relative max-w-4xl w-full mx-auto">
        <button onclick="closeImageModal()" class="absolute -top-10 right-0 text-white hover:text-gray-300 transition-colors">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        <img id="enlargedImage" src="" alt="Enlarged ID" class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-2xl">
    </div>
</div>

<!-- Modal for decline reason - improved version -->
<div id="declineReasonModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-80 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg p-6 max-w-md w-full shadow-2xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-red-600 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <!-- Completing the SVG from where it was cut off -->
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                Reason for Decline
            </h3>
            <button onclick="closeDeclineReasonModal()" class="text-gray-500 hover:text-gray-700 transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
            <p id="declineReason" class="text-gray-700"></p>
        </div>
        <div class="flex justify-end">
            <button onclick="closeDeclineReasonModal()" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-4 py-2 rounded-lg transition-colors">
                Close
            </button>
            <button onclick="reuploadID()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg ml-3 transition-colors">
                Reupload ID
            </button>
        </div>
    </div>
</div>

<!-- JavaScript to handle modals -->
<script>
    // Function to open image modal
    function openImageModal(imageSrc) {
        document.getElementById('enlargedImage').src = imageSrc;
        document.getElementById('imageModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    // Function to close image modal
    function closeImageModal() {
        document.getElementById('imageModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    // Function to open decline reason modal
    function openDeclineReasonModal(reason) {
        document.getElementById('declineReason').textContent = reason;
        document.getElementById('declineReasonModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    // Function to close decline reason modal
    function closeDeclineReasonModal() {
        document.getElementById('declineReasonModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    function reuploadID() {
    // Close the decline reason modal first 
     console.log('Reupload ID function called');
    closeDeclineReasonModal();
    
    // Open the edit profile modal
    const modal = document.getElementById('edit-profile-modal');
    modal.classList.remove('hidden');
    modal.classList.remove('opacity-0', 'scale-95');
    modal.classList.add('opacity-100', 'scale-100');
    
    // Load address data (if needed)
    setTimeout(loadAddressData, 100);
    
    // Scroll to the ID upload section with a slight delay to ensure the modal is fully open
    setTimeout(() => {
        const idUploadSection = document.querySelector('label[for="id-upload"]');
        if (idUploadSection) {
            idUploadSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Highlight the upload area
            const uploadContainer = idUploadSection.closest('.flex.flex-col.sm\\:flex-row.gap-4');
            if (uploadContainer) {
                uploadContainer.classList.add('ring-2', 'ring-yellow-500', 'animate-pulse');
                setTimeout(() => {
                    uploadContainer.classList.remove('ring-2', 'ring-yellow-500', 'animate-pulse');
                }, 2000);
            }
        }
    }, 500);
}

// Ensure the edit profile button listener is properly attached
document.addEventListener('DOMContentLoaded', function() {
    const editProfileBtn = document.getElementById('edit-profile-btn');
    if (editProfileBtn) {
        editProfileBtn.addEventListener('click', function() {
            const modal = document.getElementById('edit-profile-modal');
            modal.classList.remove('hidden');
            modal.classList.remove('opacity-0', 'scale-95');
            modal.classList.add('opacity-100', 'scale-100');
            setTimeout(loadAddressData, 100);
        });
    }
});
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === document.getElementById('imageModal')) {
            closeImageModal();
        }
        if (event.target === document.getElementById('declineReasonModal')) {
            closeDeclineReasonModal();
        }
    });
    
    // Close modals with escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeImageModal();
            closeDeclineReasonModal();
        }
    });

    function openEditProfileToIDUpload() {
    // Open the edit profile modal
    const modal = document.getElementById('edit-profile-modal');
    modal.classList.remove('hidden');
    modal.classList.remove('opacity-0', 'scale-95');
    modal.classList.add('opacity-100', 'scale-100');
    
    // Load address data (if needed)
    setTimeout(loadAddressData, 100);
    
    // Scroll to the ID upload section with a slight delay to ensure the modal is fully open
    setTimeout(() => {
        const idUploadSection = document.querySelector('label[for="id-upload"]');
        if (idUploadSection) {
            idUploadSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Highlight the upload area
            const uploadContainer = idUploadSection.closest('.flex.flex-col.sm\\:flex-row.gap-4');
            if (uploadContainer) {
                uploadContainer.classList.add('ring-2', 'ring-yellow-500', 'animate-pulse');
                setTimeout(() => {
                    uploadContainer.classList.remove('ring-2', 'ring-yellow-500', 'animate-pulse');
                }, 2000);
            }
        }
    }, 500);
}
    
    
</script>
                    
<!-- Bookings Tab -->
<div id="bookings" class="tab-content">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <!-- Header with enhanced styling -->
        <div class="bookings bg-navy p-6 border-b border-gray-100 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 tab-header">
            <h3 class="font-hedvig text-xl sm:text-2xl text-white font-semibold">My Bookings</h3>
        </div>
        
        <!-- Content area with improved spacing and grouping -->
        <div class="p-6">
            <?php
            // Fetch all bookings for the current customer
            $query = "SELECT b.*, s.service_name, s.selling_price, br.branch_name 
                      FROM booking_tb b
                      JOIN services_tb s ON b.service_id = s.service_id
                      JOIN branch_tb br ON b.branch_id = br.branch_id
                      WHERE b.customerID = ?
                      ORDER BY CASE 
                          WHEN b.status = 'Pending' THEN 1
                          WHEN b.status = 'Accepted' THEN 2
                          WHEN b.status = 'Declined' THEN 3
                          WHEN b.status = 'Cancelled' THEN 4
                          ELSE 5
                      END, b.booking_date DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                while ($booking = $result->fetch_assoc()) {
                    // Determine status color and text
                    $status_class = '';
                    $status_text = '';
                    switch ($booking['status']) {
                        case 'Pending':
                            $status_class = 'bg-yellow-600/10 text-yellow-600';
                            $status_text = 'Pending';
                            break;
                        case 'Accepted':
                            $status_class = 'bg-green-500/10 text-green-500';
                            $status_text = 'Accepted';
                            break;
                        case 'Declined':
                            $status_class = 'bg-red-500/10 text-red-500';
                            $status_text = 'Declined';
                            break;
                        case 'Cancelled':
                            $status_class = 'bg-gray-500/10 text-gray-500';
                            $status_text = 'Cancelled';
                            break;
                        default:
                            $status_class = 'bg-blue-500/10 text-blue-500';
                            $status_text = $booking['status'];
                    }
                    
                    // Format dates
                    $booking_date = date('F j, Y', strtotime($booking['booking_date']));
                    $burial_date = $booking['deceased_dateOfBurial'] ? date('F j, Y', strtotime($booking['deceased_dateOfBurial'])) : 'Not set';
                    
                    // Format deceased name
                    $deceased_name = $booking['deceased_lname'] . ', ' . $booking['deceased_fname'];
                    if (!empty($booking['deceased_midname'])) {
                        $deceased_name .= ' ' . $booking['deceased_midname'];
                    }
                    if (!empty($booking['deceased_suffix'])) {
                        $deceased_name .= ' ' . $booking['deceased_suffix'];
                    }
                    
                    // Format price
                    $price = number_format($booking['selling_price'], 2);
                    $amount_paid = $booking['amount_paid'] ? number_format($booking['amount_paid'], 2) : '0.00';
                    $balance = number_format($booking['selling_price'] - ($booking['amount_paid'] ?? 0), 2);
            ?>
            
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mb-4">
                <div class="bg-navy bg-opacity-10 px-4 py-3 sm:px-6 sm:py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between mb-3">
                        <span class="<?php echo $status_class; ?> text-xs px-2 py-1 rounded-full"><?php echo $status_text; ?></span>
                        <p class="text-sm text-gray-500">Booking ID: <?php echo $booking['booking_id']; ?></p>
                    </div>
                    <h4 class="font-hedvig text-lg text-navy mb-2"><?php echo $booking['service_name']; ?></h4>
                </div>
                <div class="p-4 sm:p-6 space-y-3 sm:space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                        <div>
                            <p class="text-sm text-gray-500">Deceased Name</p>
                            <p class="text-navy"><?php echo ucwords(strtolower($deceased_name)); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Branch</p>
                            <p class="text-navy"><?php echo ucwords(strtolower($booking['branch_name'])); ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Burial Date</p>
                            <p class="text-navy"><?php echo $burial_date; ?></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                        <div>
                            <p class="text-sm text-gray-500">Total Amount</p>
                            <p class="text-navy font-bold">₱<?php echo $price; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Amount Paid</p>
                            <p class="text-navy">₱<?php echo $amount_paid; ?></p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Balance</p>
                            <p class="text-navy">₱<?php echo $balance; ?></p>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button class="view-details bg-navy/5 text-navy px-3 py-1 rounded hover:bg-navy/10 transition text-sm mr-2" data-booking="<?php echo $booking['booking_id']; ?>">
                            <i class="fas fa-file-alt mr-1"></i> View Details
                        </button>
                        
                        <?php if ($booking['status'] === 'Accepted'): ?>
                            <button class="view-receipt bg-green-600 text-white px-3 py-1 rounded hover:bg-green-700 transition text-sm mr-2" data-booking="<?php echo $booking['booking_id']; ?>">
                                <i class="fas fa-receipt mr-1"></i> View Receipt
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($booking['status'] === 'Pending' || $booking['status'] === 'Declined'): ?>
                            <button class="modify-booking bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 transition text-sm mr-2" data-booking="<?php echo $booking['booking_id']; ?>">
                                <i class="fas fa-edit mr-1"></i> Modify
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($booking['status'] === 'Pending'): ?>
                            <button class="cancel-booking bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition text-sm" data-booking="<?php echo $booking['booking_id']; ?>">
                                <i class="fas fa-times mr-1"></i> Cancel
                            </button>
                        <?php elseif ($booking['status'] === 'Cancelled'): ?>
                            <span class="text-gray-500 text-sm py-1 px-3">
                                <i class="fas fa-ban mr-1"></i> Cancelled
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php
                }
            } else {
                echo '<p class="text-gray-500">You have no bookings yet.</p>';
            }
            $stmt->close();
            ?>
        </div>
    </div>
</div>


                    <!-- Transaction Logs Tab -->
<div id="transaction-logs" class="tab-content">
    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
        <div class="p-6 border-b border-gray-100 flex justify-between items-center">
            <h3 class="font-hedvig text-xl text-navy">Payment History</h3>
            <div class="flex space-x-2">
                <button id="export-transactions" class="bg-navy text-white px-3 py-1 rounded hover:bg-navy/80 transition text-sm">
                    <i class="fas fa-download mr-1"></i> Export Statement
                </button>
                <button id="filter-transactions" class="bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 transition text-sm">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
            </div>
        </div>
        <div class="p-6">
            <!-- Life Plan Summary -->
            <div class="mb-8 bg-gray-50 p-5 rounded-lg">
                <h3 class="font-hedvig text-lg text-navy mb-3">Life Plan: Premium Family Package</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Plan ID</p>
                        <p class="font-medium text-navy">LP-23789-F</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Start Date</p>
                        <p class="font-medium text-navy">Jan 15, 2024</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Payment Schedule</p>
                        <p class="font-medium text-navy">Monthly</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total Plan Value</p>
                        <p class="font-medium text-navy">$12,500.00</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Remaining Balance</p>
                        <p class="font-medium text-navy">$9,875.50</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Payment Status</p>
                        <span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Current</span>
                    </div>
                </div>
            </div>
            
            <!-- Transaction History -->
            <div class="mb-8">
                <h3 class="font-hedvig text-lg text-navy mb-4">Payment History</h3>
                
                <!-- Transaction Item 1 -->
                <div class="border border-gray-200 rounded-lg p-4 mb-4">
                    <div class="flex flex-col md:flex-row justify-between mb-2">
                        <div>
                            <p class="font-medium text-navy">Monthly Premium</p>
                            <p class="text-sm text-gray-500">Mar 15, 2025</p>
                        </div>
                        <div class="md:text-right mt-2 md:mt-0">
                            <p class="font-semibold text-green-600">$208.33</p>
                            <span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Successful</span>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-gray-100 mt-2 flex justify-between items-center">
                        <p class="text-sm text-gray-600">Premium Family Package - Monthly Payment</p>
                        <button class="text-sm text-yellow-600 hover:text-yellow-700 transition">Receipt</button>
                    </div>
                </div>
                
                <!-- Transaction Item 2 -->
                <div class="border border-gray-200 rounded-lg p-4 mb-4">
                    <div class="flex flex-col md:flex-row justify-between mb-2">
                        <div>
                            <p class="font-medium text-navy">Monthly Premium</p>
                            <p class="text-sm text-gray-500">Feb 15, 2025</p>
                        </div>
                        <div class="md:text-right mt-2 md:mt-0">
                            <p class="font-semibold text-green-600">$208.33</p>
                            <span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Successful</span>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-gray-100 mt-2 flex justify-between items-center">
                        <p class="text-sm text-gray-600">Premium Family Package - Monthly Payment</p>
                        <button class="text-sm text-yellow-600 hover:text-yellow-700 transition">Receipt</button>
                    </div>
                </div>
                
                <!-- Transaction Item 3 - Service Add-on -->
                <div class="border border-gray-200 rounded-lg p-4 mb-4">
                    <div class="flex flex-col md:flex-row justify-between mb-2">
                        <div>
                            <p class="font-medium text-navy">Memorial Service Upgrade</p>
                            <p class="text-sm text-gray-500">Feb 10, 2025</p>
                        </div>
                        <div class="md:text-right mt-2 md:mt-0">
                            <p class="font-semibold text-green-600">$350.00</p>
                            <span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Successful</span>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-gray-100 mt-2 flex justify-between items-center">
                        <p class="text-sm text-gray-600">Premium Casket Selection - One-time Payment</p>
                        <button class="text-sm text-yellow-600 hover:text-yellow-700 transition">Receipt</button>
                    </div>
                </div>
                
                <!-- Transaction Item 4 (Late Payment) -->
                <div class="border border-gray-200 rounded-lg p-4 mb-4">
                    <div class="flex flex-col md:flex-row justify-between mb-2">
                        <div>
                            <p class="font-medium text-navy">Monthly Premium</p>
                            <p class="text-sm text-gray-500">Jan 20, 2025</p>
                        </div>
                        <div class="md:text-right mt-2 md:mt-0">
                            <p class="font-semibold text-orange-600">$208.33</p>
                            <span class="inline-block px-2 py-1 bg-orange-100 text-orange-800 text-xs font-semibold rounded-full">Late Payment</span>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-gray-100 mt-2 flex justify-between items-center">
                        <p class="text-sm text-gray-600">Premium Family Package - Due Jan 15, 2025</p>
                        <button class="text-sm text-yellow-600 hover:text-yellow-700 transition">Receipt</button>
                    </div>
                </div>
                
                <!-- Transaction Item 5 (Initial Payment) -->
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex flex-col md:flex-row justify-between mb-2">
                        <div>
                            <p class="font-medium text-navy">Initial Payment</p>
                            <p class="text-sm text-gray-500">Jan 15, 2024</p>
                        </div>
                        <div class="md:text-right mt-2 md:mt-0">
                            <p class="font-semibold text-green-600">$1,250.00</p>
                            <span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded-full">Successful</span>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-gray-100 mt-2 flex justify-between items-center">
                        <p class="text-sm text-gray-600">Premium Family Package - Down Payment (10%)</p>
                        <button class="text-sm text-yellow-600 hover:text-yellow-700 transition">Receipt</button>
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Payments -->
            <div class="mb-8">
                <h3 class="font-hedvig text-lg text-navy mb-4">Upcoming Payments</h3>
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex flex-col md:flex-row justify-between mb-2">
                        <div>
                            <p class="font-medium text-navy">Monthly Premium</p>
                            <p class="text-sm text-gray-500">Apr 15, 2025</p>
                        </div>
                        <div class="md:text-right mt-2 md:mt-0">
                            <p class="font-semibold text-navy">$208.33</p>
                            <span class="inline-block px-2 py-1 bg-gray-100 text-gray-800 text-xs font-semibold rounded-full">Upcoming</span>
                        </div>
                    </div>
                    <div class="pt-2 border-t border-gray-100 mt-2 flex justify-between items-center">
                        <p class="text-sm text-gray-600">Premium Family Package - Monthly Payment</p>
                        <button class="text-sm bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 transition">Pay Now</button>
                    </div>
                </div>
            </div>
            
            <!-- Payment Summary -->
            <div>
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-hedvig text-lg text-navy">Payment Summary</h3>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <p class="text-sm text-gray-500">Total Paid to Date</p>
                            <p class="text-xl font-semibold text-green-600">$2,624.50</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <p class="text-sm text-gray-500">Remaining Payments</p>
                            <p class="text-xl font-semibold text-navy">47</p>
                        </div>
                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <p class="text-sm text-gray-500">Next Payment</p>
                            <div class="flex justify-between items-center">
                                <p class="text-xl font-semibold text-navy">$208.33</p>
                                <p class="text-sm text-gray-500">Apr 15, 2025</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    </div>
    </div>
    </div>
</div>
</div>  
                    
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
                    
<!-- Edit Profile Modal (Enhanced UI) -->
<div id="edit-profile-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-60 p-4 hidden backdrop-blur-sm">
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] transform transition-all duration-300">
        <div class="modal-scroll-container overflow-y-auto max-h-[90vh]">
            <!-- Header with close button -->
            <div class="bg-navy p-6 flex justify-between items-center border-b-4 border-yellow-500">
                <h2 class="text-2xl font-hedvig text-white flex items-center">
                    Edit Profile
                </h2>
                <button id="close-edit-profile-modal" class="text-white hover:text-yellow-300 transition-colors duration-200 transform hover:scale-110">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <!-- Modal Body -->
            <div class="p-8 bg-cream">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r">
                    <p class="text-gray-700 text-base sm:text-lg">Update your personal information below. Fields marked with <span class="text-red-500 font-semibold">*</span> are required.</p>
                </div>
                
                <form class="space-y-6 sm:space-y-8" id="profile-form" method="POST" action="profile/update_profile.php" enctype="multipart/form-data">
                    
                    <!-- Personal Information Section -->
                    <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
                        <h4 class="font-semibold text-gray-800 mb-4 flex items-center text-lg border-b pb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-3 text-yellow-600">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                            Personal Information
                        </h4>
                        
                        <div class="grid sm:grid-cols-2 gap-5 sm:gap-6">
                            <div>
                                <label for="firstName" class="block text-sm font-medium text-gray-700 mb-2">First Name <span class="text-red-500">*</span></label>
                                <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($first_name); ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent text-base shadow-sm transition-all duration-200">
                            </div>
                            
                            <div>
                                <label for="lastName" class="block text-sm font-medium text-gray-700 mb-2">Last Name <span class="text-red-500">*</span></label>
                                <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($last_name); ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent text-base shadow-sm transition-all duration-200">
                            </div>
                            
                            <div>
                                <label for="middleName" class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                                <input type="text" id="middleName" name="middleName" value="<?php echo htmlspecialchars($middle_name); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent text-base shadow-sm transition-all duration-200">
                            </div>

                            <div>
                                <label for="dob" class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                                <div class="relative">
                                    <input type="date" id="dob" name="dob" value="<?php echo htmlspecialchars($birthdate); ?>" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent text-base shadow-sm transition-all duration-200">
                                    <span class="absolute right-4 top-1/2 transform -translate-y-1/2 text-yellow-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="grid sm:grid-cols-2 gap-5 sm:gap-6 mt-5">
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required readonly class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-100 cursor-not-allowed text-base shadow-sm">
                                    <span class="absolute right-4 top-1/2 transform -translate-y-1/2 text-yellow-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                            <polyline points="22,6 12,13 2,6"></polyline>
                                        </svg>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Email cannot be changed</p>
                            </div>
                                
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="tel" id="phone" name="phone" pattern="^(\+63\d{10}|0\d{10}|\d{10})$"
                                    title="Phone number (09XXXXXXXXX or +639XXXXXXXXX)" value="<?php echo htmlspecialchars($phone_number); ?>" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent pr-10 text-base shadow-sm transition-all duration-200">
                                    <span class="absolute right-4 top-1/2 transform -translate-y-1/2 text-yellow-600">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                        </svg>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Format: 09XXXXXXXXX or +639XXXXXXXXX</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Address Information Section -->
                    <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
                        <h4 class="font-semibold text-gray-800 mb-4 flex items-center text-lg border-b pb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-3 text-yellow-600">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                            Address Information
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
                            <!-- Region Dropdown -->
                            <div class="relative">
                                <label for="region" class="block text-sm font-medium text-gray-700 mb-2">Region</label>
                                <div class="relative">
                                    <select id="region" name="region" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent appearance-none text-base shadow-sm transition-all duration-200" onchange="updateProvinces()">
                                        <option value="" selected disabled>Select Region</option>
                                        <?php foreach ($regions as $region_option): ?>
                                            <option value="<?php echo $region_option['region_id']; ?>" <?php echo ($region_option['region_name'] == $region) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($region_option['region_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Province Dropdown -->
                            <div class="relative">
                                <label for="province" class="block text-sm font-medium text-gray-700 mb-2">Province</label>
                                <div class="relative">
                                    <select id="province" name="province" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent appearance-none text-base shadow-sm transition-all duration-200" onchange="updateCities()" disabled>
                                        <option value="" selected disabled>Select Province</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- City/Municipality Dropdown -->
                            <div class="relative">
                                <label for="city" class="block text-sm font-medium text-gray-700 mb-2">City/Municipality</label>
                                <div class="relative">
                                    <select id="city" name="city" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent appearance-none text-base shadow-sm transition-all duration-200" onchange="updateBarangays()" disabled>
                                        <option value="" selected disabled>Select City/Municipality</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Barangay Dropdown -->
                            <div class="relative">
                                <label for="barangay" class="block text-sm font-medium text-gray-700 mb-2">Barangay</label>
                                <div class="relative">
                                    <select id="barangay" name="barangay" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent appearance-none text-base shadow-sm transition-all duration-200" disabled>
                                        <option value="" selected disabled>Select Barangay</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-700">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Street Address and Zip Code -->
                            <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-4 gap-5">
                                <!-- Street Address (taking 3/4 of the width) -->
                                <div class="sm:col-span-3">
                                    <label for="street_address" class="block text-sm font-medium text-gray-700 mb-2">Street Address</label>
                                    <input type="text" id="street_address" name="street_address" placeholder="House/Lot/Unit No., Building, Street Name" 
                                        value="<?php echo htmlspecialchars($street_address); ?>" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent text-base shadow-sm transition-all duration-200">
                                </div>
                                
                                <!-- Zip/Postal Code (taking 1/4 of the width) -->
                                <div class="sm:col-span-1">
                                    <label for="zip" class="block text-sm font-medium text-gray-700 mb-2">Zip Code</label>
                                    <input type="text" id="zip" name="zip" placeholder="Zip Code" 
                                        value="<?php echo htmlspecialchars($zip_code); ?>" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent text-base shadow-sm transition-all duration-200">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($id_status !== 'valid'): ?>
                    <!-- Document Uploads Section -->
                    <div class="bg-white rounded-xl p-6 border border-gray-200 shadow-sm">
                        <h4 class="font-semibold text-gray-800 mb-4 flex items-center text-lg border-b pb-3">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-3 text-yellow-600">
                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                <polyline points="13 2 13 9 20 9"></polyline>
                            </svg>
                            Valid ID
                        </h4>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <!-- ID Upload -->
                            <div>
                                <label for="id-upload" class="block text-sm font-medium text-gray-700 mb-3">Government-Issued ID <span class="text-red-500">*</span></label>
                                <div class="flex flex-col sm:flex-row gap-4">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-center w-full">
                                            <label for="id-upload" class="flex flex-col border-4 border-dashed border-gray-300 hover:bg-gray-50 hover:border-yellow-500 rounded-lg p-6 group text-center cursor-pointer transition-all duration-200">
                                                <div class="flex flex-col items-center justify-center">
                                                    <svg class="w-12 h-12 text-gray-400 group-hover:text-yellow-600 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                                                    <p class="text-base text-gray-500 group-hover:text-yellow-600 mt-3 font-medium">Upload Government ID</p>
                                                    <p class="text-sm text-gray-500 mt-1">(JPG, PNG)</p>
                                                    <p class="text-xs text-gray-500 mt-1">Max file size: 5MB</p>
                                                </div>
                                                <input type="file" id="id-upload" name="id-upload" class="hidden" accept=".jpg,.jpeg,.png" required>
                                            </label>
                                        </div>
                                    </div>
                                    <!-- Image Preview -->
                                    <div class="flex-1">
                                        <div class="border border-gray-300 rounded-lg p-4 h-full shadow-sm">
                                            <h5 class="text-sm font-medium text-gray-700 mb-2">ID Preview</h5>
                                            <div id="image-preview-container" class="flex items-center justify-center bg-gray-100 rounded-lg h-48 overflow-hidden">
                                                <p class="text-gray-500 text-sm">Preview will appear here</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Example of a Correct ID Upload -->
                            <div class="sm:block">
                                <h5 class="block text-sm font-medium text-gray-700 mb-3">Example of a Correct ID Upload</h5>
                                <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-r">
                                    <h5 class="font-bold text-gray-800 mb-2 text-base">ID Upload Guidelines</h5>
                                    <ul class="list-disc list-inside text-sm text-blue-700 space-y-2">
                                        <li>Full document clearly visible</li>
                                        <li>No glare or shadows</li>
                                        <li>All four corners of the ID are shown</li>
                                        <li>High-resolution (at least 300 DPI)</li>
                                        <li>Personal information is legible</li>
                                        <li>No cuts or cropped edges</li>
                                    </ul>
                                    <div class="mt-3 flex justify-center">
                                        <div class="flex flex-col sm:flex-row sm:space-x-4 space-y-2 sm:space-y-0">
                                            <div class="relative w-full sm:w-1/2 max-w-md group">
                                                <img src="../image/wrongID.jpg" alt="Incorrect ID Upload" class="w-full border-2 border-red-400 rounded shadow">
                                                <div class="absolute top-2 right-2 bg-red-500 text-white rounded-full h-6 w-6 flex items-center justify-center">✕</div>
                                                <p class="text-xs text-red-600 mt-1 text-center">Poor Upload</p>
                                            </div>
                                            <div class="relative w-full sm:w-1/2 max-w-md group">
                                                <img src="../image/rightID.jpg" alt="Correct ID Upload" class="w-full border-2 border-green-400 rounded shadow">
                                                <div class="absolute top-2 right-2 bg-green-500 text-white rounded-full h-6 w-6 flex items-center justify-center">✓</div>
                                                <p class="text-xs text-green-600 mt-1 text-center">Correct Upload</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                
                    <!-- Modal Footer -->
                    <div class="modal-sticky-footer flex flex-col sm:flex-row sm:justify-end gap-3 sm:gap-4 mt-8">
                        <button type="button" class="w-full sm:w-auto px-5 sm:px-6 py-3 bg-white border-2 border-yellow-600 text-gray-800 rounded-lg font-medium hover:bg-gray-50 transition-all duration-200 flex items-center justify-center" onclick="closeEditProfileModal()">
                            Cancel
                        </button>
                        <button type="submit" class="w-full sm:w-auto px-6 sm:px-8 py-3 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium shadow-lg hover:shadow-xl transition-all duration-300 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- Change Password Modal -->
<div id="change-password-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
  <!-- Modal Content -->
  <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden max-h-[90vh]">
    <div class="modal-scroll-container overflow-y-auto max-h-[90vh]">
      <!-- Header with close button -->
      <div class="bg-navy p-6 flex justify-between items-center">
        <h2 class="text-2xl font-hedvig text-white">Change Password</h2>
        <button id="close-change-password-modal" class="text-white hover:text-yellow-300">
          <i class="fas fa-times text-2xl"></i>
        </button>
      </div>
      
      <!-- Modal Body -->
      <div class="p-6 bg-cream">
        <p class="text-gray-600 text-base mb-4">Enter your current password and choose a new strong password.</p>
        
        <form class="space-y-4 sm:space-y-6" id="password-form" method="POST" action="profile/update_password.php">
          <!-- Current Password -->
          <div>
            <label for="current-password" class="block text-sm font-medium text-navy mb-1 sm:mb-2">Current Password*</label>
            <div class="relative">
              <input type="password" id="current-password" name="current-password" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent text-sm sm:text-base">
              <span class="password-toggle absolute right-3 sm:right-4 top-1/2 transform -translate-y-1/2 text-gray-600 cursor-pointer" data-target="current-password">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-show">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-hide hidden">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                  <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>
              </span>
            </div>
            <p id="current-password-error" class="mt-1 text-sm text-red-600 hidden"></p>
          </div>
          
          <!-- New Password -->
          <div>
            <label for="new-password" class="block text-sm font-medium text-navy mb-1 sm:mb-2">New Password*</label>
            <div class="relative">
              <input type="password" id="new-password" name="new-password" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent text-sm sm:text-base">
              <span class="password-toggle absolute right-3 sm:right-4 top-1/2 transform -translate-y-1/2 text-gray-600 cursor-pointer" data-target="new-password">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-show">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-hide hidden">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                  <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>
              </span>
            </div>
            <p id="new-password-error" class="mt-1 text-sm text-red-600 hidden"></p>
            <div class="mt-2 text-xs text-gray-500">
              Password must:
              <ul class="list-disc list-inside ml-1 space-y-1 mt-1">
                <li id="length-check" class="text-gray-500">Be at least 8 characters long</li>
                <li id="uppercase-check" class="text-gray-500">Contain at least one uppercase letter</li>
                <li id="lowercase-check" class="text-gray-500">Contain at least one lowercase letter</li>
                <li id="number-check" class="text-gray-500">Contain at least one number</li>
                <li id="special-check" class="text-gray-500">Contain at least one special character</li>
              </ul>
            </div>
          </div>
          
          <!-- Confirm Password -->
          <div>
            <label for="confirm-password" class="block text-sm font-medium text-navy mb-1 sm:mb-2">Confirm New Password*</label>
            <div class="relative">
              <input type="password" id="confirm-password" name="confirm-password" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent text-sm sm:text-base">
              <span class="password-toggle absolute right-3 sm:right-4 top-1/2 transform -translate-y-1/2 text-gray-600 cursor-pointer" data-target="confirm-password">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-show">
                  <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-hide hidden">
                  <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                  <line x1="1" y1="1" x2="23" y2="23"></line>
                </svg>
              </span>
            </div>
            <p id="confirm-password-error" class="mt-1 text-sm text-red-600 hidden"></p>
          </div>
        </form>
      </div>
      
      <!-- Modal Footer -->
      <div class="modal-sticky-footer px-6 py-4 flex flex-col sm:flex-row sm:justify-end gap-3 border-t border-gray-200 bg-white">
        <button class="w-full sm:w-auto px-6 py-3 bg-white border border-yellow-600 text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center" id="cancel-change-password">
          Cancel
        </button>
        <button type="submit" id="submit-change-password" class="w-full sm:w-auto px-6 py-3 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg shadow-md transition-all duration-300 flex items-center justify-center">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
          </svg>
          Update Password
        </button>
      </div>
    </div>
  </div>
</div>


<!-- View Details Modal -->
<div id="viewDetailsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 p-4 hidden">
  <!-- Modal Content -->
  <div class="w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh]">
    <div class="modal-scroll-container overflow-y-auto max-h-[90vh]">
      <!-- Header with close button -->
      <div class="bg-navy p-6 flex justify-between items-center">
        <div class="flex items-center">
          <i class="fas fa-calendar-check text-yellow-300 mr-3 text-2xl"></i>
          <h2 class="text-2xl font-hedvig text-white">Booking Details</h2>
        </div>
        <button class="close-modal text-white hover:text-yellow-300 transition-colors duration-200">
          <i class="fas fa-times text-2xl"></i>
        </button>
      </div>
      
      <!-- Booking Status Banner -->
      <div id="status-banner" class="px-6 py-3 bg-blue-50 border-b border-blue-100 flex items-center">
        <div class="flex items-center">
          <div class="h-3 w-3 rounded-full bg-blue-500 mr-2"></div>
          <span class="font-medium text-navy">Status:</span>
          <span id="detail-status" class="ml-2 text-navy font-bold"></span>
        </div>
        <div class="ml-auto text-sm">
          <span class="text-gray-500">Reference Code:</span>
          <span id="detail-reference" class="text-navy font-mono ml-1"></span>
        </div>
      </div>
      
      <!-- Modal Body -->
      <div class="p-6 bg-cream">
        <!-- Service Information Card -->
        <div class="bg-white rounded-xl shadow-sm p-5 mb-6">
          <div class="flex items-center mb-4">
            <i class="fas fa-concierge-bell text-yellow-600 mr-2"></i>
            <h4 class="font-semibold text-navy text-lg">Service Information</h4>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3">
            <div class="flex">
              <span class="text-gray-500 w-32">Service Type:</span> 
              <span id="detail-service" class="text-navy font-medium"></span>
            </div>
            <div class="flex">
              <span class="text-gray-500 w-32">Branch:</span> 
              <span id="detail-branch" class="text-navy font-medium"></span>
            </div>
            <div class="flex">
              <span class="text-gray-500 w-32">Booking Date:</span> 
              <span id="detail-booking-date" class="text-navy font-medium"></span>
            </div>
            <div class="flex">
              <span class="text-gray-500 w-32">Total Amount:</span> 
              <span id="detail-total" class="text-navy font-bold"></span>
            </div>
          </div>
        </div>
        
        <!-- Deceased Information Card -->
        <div class="bg-white rounded-xl shadow-sm p-5 mb-6">
          <div class="flex items-center mb-4">
            <i class="fas fa-user text-yellow-600 mr-2"></i>
            <h4 class="font-semibold text-navy text-lg">Deceased Information</h4>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-3">
            <div class="flex">
              <span class="text-gray-500 w-32">Full Name:</span> 
              <span id="detail-deceased-name" class="text-navy font-medium"></span>
            </div>
            <div class="flex">
              <span class="text-gray-500 w-32">Birth Date:</span> 
              <span id="detail-birth" class="text-navy font-medium"></span>
            </div>
            <div class="flex">
              <span class="text-gray-500 w-32">Date of Death:</span> 
              <span id="detail-dod" class="text-navy font-medium"></span>
            </div>
            <div class="flex">
              <span class="text-gray-500 w-32">Burial Date:</span> 
              <span id="detail-burial" class="text-navy font-medium"></span>
            </div>
            <div class="flex md:col-span-2">
              <span class="text-gray-500 w-32">Address:</span> 
              <span id="detail-address" class="text-navy font-medium"></span>
            </div>
          </div>
        </div>
        
        <!-- Payment Information Card -->
        <div class="bg-white rounded-xl shadow-sm p-5 mb-6">
          <div class="flex items-center mb-4">
            <i class="fas fa-credit-card text-yellow-600 mr-2"></i>
            <h4 class="font-semibold text-navy text-lg">Payment Information</h4>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
              <div class="flex items-center mb-1">
                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                <p class="text-gray-600 font-medium">Amount Paid</p>
              </div>
              <p id="detail-paid" class="text-navy font-bold text-lg"></p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
              <div class="flex items-center mb-1">
                <i class="fas fa-balance-scale text-blue-500 mr-2"></i>
                <p class="text-gray-600 font-medium">Balance</p>
              </div>
              <p id="detail-balance" class="text-navy font-bold text-lg"></p>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
              <div class="flex justify-center items-center h-full">
                <button id="make-payment-btn" class="bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                  <i class="fas fa-plus-circle mr-2"></i>
                  Make Payment
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Documents Section -->
        <div class="bg-white rounded-xl shadow-sm p-5">
          <div class="flex items-center mb-4">
            <i class="fas fa-file-alt text-yellow-600 mr-2"></i>
            <h4 class="font-semibold text-navy text-lg">Documents</h4>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="border border-gray-200 rounded-lg p-4 flex items-center">
              <div class="bg-blue-50 p-3 rounded-lg mr-3">
                <i class="fas fa-file-medical text-blue-600 text-xl"></i>
              </div>
              <div class="flex-grow">
                <h5 class="font-medium text-navy">Death Certificate</h5>
                <p class="text-xs text-gray-500">Uploaded on: <span id="cert-upload-date">May 1, 2025</span></p>
              </div>
              <button id="viewDeathCertBtn" class="bg-blue-600 text-white p-2 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                <i class="fas fa-eye"></i>
              </button>
            </div>
            <div class="border border-gray-200 rounded-lg p-4 flex items-center">
              <div class="bg-green-50 p-3 rounded-lg mr-3">
                <i class="fas fa-receipt text-green-600 text-xl"></i>
              </div>
              <div class="flex-grow">
                <h5 class="font-medium text-navy">Payment Receipt</h5>
                <p class="text-xs text-gray-500">Last payment: <span id="payment-date">May 1, 2025</span></p>
              </div>
              <button id="viewPaymentBtn" class="bg-green-600 text-white p-2 rounded-lg hover:bg-green-700 transition-colors duration-200">
                <i class="fas fa-eye"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Modal Footer -->
      <div class="modal-sticky-footer px-6 py-4 flex flex-col sm:flex-row sm:justify-end gap-3 border-t border-gray-200 bg-white">
        <button class="close-modal w-full sm:w-auto px-6 py-3 bg-white border border-yellow-600 text-gray-800 rounded-lg font-medium hover:bg-gray-100 transition-all duration-200 flex items-center justify-center">
          <i class="fas fa-times mr-2"></i>
          Close
        </button>
        <button id="print-details-btn" class="w-full sm:w-auto px-6 py-3 bg-navy hover:bg-navy/90 text-white rounded-lg shadow-md transition-all duration-300 flex items-center justify-center">
          <i class="fas fa-print mr-2"></i>
          Print Details
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modify Booking Modal -->
<div id="modifyBookingModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-hedvig text-xl text-navy">Modify Booking</h3>
            <button class="close-modal text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <form id="modifyBookingForm">
                <input type="hidden" id="modify-booking-id" name="booking_id">
                <input type="hidden" id="modify-service-id" name="service_id">
                <input type="hidden" id="modify-branch-id" name="branch_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="font-semibold text-navy mb-3">Service Information</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-gray-500 text-sm mb-1">Service Package</label>
                                <div id="display-service-package" class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50">
                                    <!-- Service package details will be displayed here -->
                                </div>
                            </div>
                            <div>
                                <label class="block text-gray-500 text-sm mb-1">Branch Location</label>
                                <div id="display-branch-location" class="w-full border border-gray-300 rounded px-3 py-2 bg-gray-50">
                                    <!-- Branch location will be displayed here -->
                                </div>
                            </div>
                            <div>
                                <label class="block text-gray-500 text-sm mb-1">Burial Date</label>
                                <input type="date" name="deceased_dateOfBurial" class="w-full border border-gray-300 rounded px-3 py-2" required>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-semibold text-navy mb-3">Deceased Information</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-gray-500 text-sm mb-1">First Name</label>
                                <input type="text" name="deceased_fname" class="w-full border border-gray-300 rounded px-3 py-2" required>
                            </div>
                            <div>
                                <label class="block text-gray-500 text-sm mb-1">Middle Name</label>
                                <input type="text" name="deceased_midname" class="w-full border border-gray-300 rounded px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-gray-500 text-sm mb-1">Last Name</label>
                                <input type="text" name="deceased_lname" class="w-full border border-gray-300 rounded px-3 py-2" required>
                            </div>
                            <div>
                                <label class="block text-gray-500 text-sm mb-1">Suffix</label>
                                <input type="text" name="deceased_suffix" class="w-full border border-gray-300 rounded px-3 py-2">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-6">
                    <h4 class="font-semibold text-navy mb-3">Additional Information</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-500 text-sm mb-1">Birth Date</label>
                            <input type="date" name="deceased_birth" class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-gray-500 text-sm mb-1">Date of Death</label>
                            <input type="date" name="deceased_dodeath" class="w-full border border-gray-300 rounded px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-gray-500 text-sm mb-1">Address</label>
                            <textarea name="deceased_address" rows="2" class="w-full border border-gray-300 rounded px-3 py-2"></textarea>
                        </div>
                        <div class="flex items-center">
                            <input type="checkbox" name="with_cremate" id="with_cremate" class="mr-2">
                            <label for="with_cremate" class="text-gray-500 text-sm">Include Cremation Service</label>
                        </div>
                    </div>
                    
                    <h4 class="font-semibold text-navy mb-3">Document Uploads</h4>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-500 text-sm mb-1">Death Certificate</label>
                            <input type="file" name="death_certificate" class="w-full border border-gray-300 rounded px-3 py-2">
                            <?php if (!empty($booking['deathcert_url'])): ?>
                                <p class="text-sm text-gray-500 mt-1">Current file: <?php echo basename($booking['deathcert_url']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div>
                            <label class="block text-gray-500 text-sm mb-1">Payment Proof</label>
                            <input type="file" name="payment_proof" class="w-full border border-gray-300 rounded px-3 py-2">
                            <?php if (!empty($booking['payment_url'])): ?>
                                <p class="text-sm text-gray-500 mt-1">Current file: <?php echo basename($booking['payment_url']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" class="close-modal bg-gray-100 text-gray-700 px-4 py-2 rounded hover:bg-gray-200 transition">
                        Cancel
                    </button>
                    <button type="submit" class="bg-navy text-white px-4 py-2 rounded hover:bg-navy/90 transition">
                        <i class="fas fa-save mr-2"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cancel Booking Modal -->
<div id="cancelBookingModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg w-full max-w-md mx-4">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-hedvig text-xl text-navy">Cancel Booking</h3>
            <button class="close-modal text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6">
            <form id="cancelBookingForm">
                <input type="hidden" id="cancel-booking-id" name="booking_id">
                <p class="mb-4">Are you sure you want to cancel this booking?</p>
                <p class="mb-4 text-red-600 font-semibold">Please note that your downpayment will NOT be refunded if you proceed with cancellation.</p>
                
                <div class="mb-4">
                    <label class="block text-gray-500 text-sm mb-1">Reason for Cancellation</label>
                    <textarea name="cancel_reason" rows="3" class="w-full border border-gray-300 rounded px-3 py-2" required></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-500 text-sm mb-1">OTP Verification</label>
                    <div class="flex items-center space-x-2">
                        <input type="text" name="otp" id="otpInput" class="border border-gray-300 rounded px-3 py-2 flex-1" placeholder="Enter OTP" required>
                        <button type="button" id="sendOtpBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                            Send OTP
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">We'll send a verification code to your email</p>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" class="close-modal bg-gray-100 text-gray-700 px-4 py-2 rounded hover:bg-gray-200 transition">
                        No, Keep Booking
                    </button>
                    <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                        <i class="fas fa-times mr-2"></i> Confirm Cancellation
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
    </div>
</div>

<!-- View Document Modal (for death cert and payment proof) -->
<div id="viewDocumentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-hidden">
        <div class="p-6 border-b border-gray-200 flex justify-between items-center">
            <h3 class="font-hedvig text-xl text-navy" id="document-modal-title">Document</h3>
            <button class="close-modal text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="p-6 flex justify-center">
            <img id="document-image" src="" alt="Document" class="max-w-full max-h-[70vh]">
            <iframe id="document-pdf" src="" class="hidden w-full h-[70vh]"></iframe>
        </div>
        <div class="p-4 border-t border-gray-200 flex justify-end">
            <button class="close-modal bg-navy text-white px-4 py-2 rounded hover:bg-navy/90 transition">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div id="receiptModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-2/3 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-800">Payment Receipt</h3>
            <button id="closeReceiptModal" class="text-gray-500 hover:text-gray-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Receipt Content (will be populated by JS) -->
        <div id="receiptContent" class="bg-white p-6 border border-gray-200 rounded">
            <!-- This will be filled dynamically -->
        </div>
        
        <div class="mt-4 flex justify-end space-x-2">
            <button id="printReceipt" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                <i class="fas fa-print mr-2"></i>Print
            </button>
            <button id="downloadPdf" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                <i class="fas fa-file-pdf mr-2"></i>PDF
            </button>
            <button id="downloadImage" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                <i class="fas fa-image mr-2"></i>Image
            </button>
        </div>
    </div>
</div>

<!-- Include html2canvas and jsPDF for export functionality -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<!-- Success Notification -->
<div id="successNotification" class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow-lg z-50 hidden">
    <div class="flex items-center">
        <i class="fas fa-check-circle mr-2"></i>
        <span id="successMessage">Operation completed successfully!</span>
    </div>
</div>

<!-- Error Notification -->
<div id="errorNotification" class="fixed top-4 right-4 bg-red-500 text-white px-4 py-2 rounded shadow-lg z-50 hidden">
    <div class="flex items-center">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <span id="errorMessage">An error occurred. Please try again.</span>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const receiptModal = document.getElementById('receiptModal');
    const closeReceiptModal = document.getElementById('closeReceiptModal');
    const receiptContent = document.getElementById('receiptContent');
    const printReceiptBtn = document.getElementById('printReceipt');
    const downloadPdfBtn = document.getElementById('downloadPdf');
    const downloadImageBtn = document.getElementById('downloadImage');

    // View Receipt button click handler
    document.addEventListener('click', function(e) {
        if (e.target.closest('.view-receipt')) {
            const bookingId = e.target.closest('.view-receipt').getAttribute('data-booking');
            fetchReceiptDetails(bookingId);
        }
    });

    // Close modal
    closeReceiptModal.addEventListener('click', () => {
        receiptModal.classList.add('hidden');
    });

    // Fetch receipt details
    function fetchReceiptDetails(bookingId) {
        fetch(`profile/fetch_receipt_details.php?booking_id=${bookingId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateReceipt(data);
                    receiptModal.classList.remove('hidden');
                } else {
                    alert('Failed to fetch receipt details: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching receipt details');
            });
    }

    // Populate receipt content
// Inside your populateReceipt function, update the accepterInfo section:
function populateReceipt(data) {
    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('en-US', options);
    };

    const deceasedName = `${data.deceased_lname}, ${data.deceased_fname}` + 
                       (data.deceased_midname ? ` ${data.deceased_midname}` : '') + 
                       (data.deceased_suffix ? ` ${data.deceased_suffix}` : '');

    // Format accepter's information if available
    let accepterInfo = '';
    if (data.accepter_first) {
        const accepterName = `${data.accepter_last}, ${data.accepter_first}` + 
                           (data.accepter_middle ? ` ${data.accepter_middle}` : '') + 
                           (data.accepter_suffix ? ` ${data.accepter_suffix}` : '');
        accepterInfo = `
            <div class="border-t border-b border-gray-200 py-4 mb-4">
                <h3 class="font-bold mb-2">Processed By</h3>
                <p><strong>Staff Name:</strong> ${accepterName ? accepterName.toLowerCase().split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') : ''}</p>
                ${data.accepter_email ? `<p><strong>Email:</strong> ${data.accepter_email}</p>` : ''}
                ${data.accepter_phone ? `<p><strong>Phone:</strong> ${data.accepter_phone}</p>` : ''}
            </div>
        `;
    }

    receiptContent.innerHTML = `
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold capitalize">${data.branch_name} Branch</h2>
            <p class="text-gray-600">Official Receipt</p>
        </div>
        
        <div class="flex justify-between mb-6">
            <div>
                    <p class="font-semibold mb-2">Transaction Details</p>
                    <p><strong>Receipt #:</strong> ${data.receipt_number || 'N/A'}</p>
                    <p><strong>Reference Code:</strong> ${data.reference_code || 'N/A'}</p>
                <p><strong>Booking Date:</strong> ${formatDate(data.booking_date)}</p>
                <p><strong>Accepted Date:</strong> ${formatDate(data.accepted_date)}</p>
            </div>
            <div class="text-right">
                <p> <span class="text-green-600">${data.status}</span></p>
            </div>
        </div>
        
        <div class="border-t border-b border-gray-200 py-4 mb-4">
            <h3 class="font-bold mb-2">Service Details</h3>
            <p><strong>Service:</strong> ${data.service_name}</p>
            <p><strong>Total Amount:</strong> ₱${parseFloat(data.selling_price).toFixed(2)}</p>
            <p><strong>Amount Paid:</strong> ₱${parseFloat(data.amount_paid || 0).toFixed(2)}</p>
            <p><strong>Balance:</strong> ₱${(parseFloat(data.selling_price) - parseFloat(data.amount_paid || 0)).toFixed(2)}</p>
        </div>
        
        <div class="border-t border-b border-gray-200 py-4 mb-4">
            <h3 class="font-bold mb-2">Deceased Information</h3>
            <p><strong>Name:</strong> ${deceasedName ? deceasedName.toLowerCase().replace(/\b\w/g, c => c.toUpperCase()) : ''}</p>
            ${data.deceased_birth ? `<p><strong>Date of Birth:</strong> ${new Date(data.deceased_birth).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</p>` : ''}
            ${data.deceased_dodeath ? `<p><strong>Date of Death:</strong> ${new Date(data.deceased_dodeath).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</p>` : ''}
            ${data.deceased_dateOfBurial ? `<p><strong>Date of Burial:</strong> ${new Date(data.deceased_dateOfBurial).toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })}</p>` : ''}        
        </div>
        
        ${accepterInfo}
        
        <div class="text-center mt-8 text-sm text-gray-500">
            <p>Thank you for your business!</p>
            <p>For inquiries, please contact our branch.</p>
        </div>
    `;
}

    // Print receipt
    printReceiptBtn.addEventListener('click', () => {
        const printContents = receiptContent.innerHTML;
        const originalContents = document.body.innerHTML;
        
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        window.location.reload();
    });

    // Download as PDF
    downloadPdfBtn.addEventListener('click', () => {
        const { jsPDF } = window.jspdf;
        
        html2canvas(receiptContent).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jsPDF();
            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
            
            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save(`receipt_${new Date().getTime()}.pdf`);
        });
    });

    // Download as Image
    downloadImageBtn.addEventListener('click', () => {
        html2canvas(receiptContent).then(canvas => {
            const link = document.createElement('a');
            link.download = `receipt_${new Date().getTime()}.png`;
            link.href = canvas.toDataURL('image/png');
            link.click();
        });
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // View Details Modal
    const viewDetailsButtons = document.querySelectorAll('.view-details');
    const viewDetailsModal = document.getElementById('viewDetailsModal');
    
    viewDetailsButtons.forEach(button => {
        button.addEventListener('click', function() {
            const bookingId = this.getAttribute('data-booking');
            fetchBookingDetails(bookingId);
        });
    });

    // Modify Booking Modal
    const modifyButtons = document.querySelectorAll('.modify-booking');
    const modifyModal = document.getElementById('modifyBookingModal');
    
    modifyButtons.forEach(button => {
        button.addEventListener('click', function() {
            const bookingId = this.getAttribute('data-booking');
            fetchBookingForModification(bookingId);
        });
    });

    // Cancel Booking Modal
    const cancelButtons = document.querySelectorAll('.cancel-booking');
    const cancelModal = document.getElementById('cancelBookingModal');
    
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            const bookingId = this.getAttribute('data-booking');
            document.getElementById('cancel-booking-id').value = bookingId;
            cancelModal.classList.remove('hidden');
        });
    });

    // View Document Buttons
    const viewDeathCertBtn = document.getElementById('viewDeathCertBtn');
    const viewPaymentBtn = document.getElementById('viewPaymentBtn');
    const viewDocumentModal = document.getElementById('viewDocumentModal');
    
    let currentDocumentType = '';
    let currentDocumentUrl = '';
    
    viewDeathCertBtn.addEventListener('click', function() {
        currentDocumentType = 'death_cert';
        showDocument('Death Certificate', currentDocumentUrl);
    });
    
    viewPaymentBtn.addEventListener('click', function() {
        currentDocumentType = 'payment_proof';
        showDocument('Payment Proof', currentDocumentUrl);
    });

    // Close Modal Buttons
    const closeModalButtons = document.querySelectorAll('.close-modal');
    closeModalButtons.forEach(button => {
        button.addEventListener('click', function() {
            viewDetailsModal.classList.add('hidden');
            modifyModal.classList.add('hidden');
            cancelModal.classList.add('hidden');
            viewDocumentModal.classList.add('hidden');
        });
    });

    // Form Submissions
    document.getElementById('modifyBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitBookingModification();
    });
    
    document.getElementById('cancelBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        submitBookingCancellation();
    });

    // Click outside modal to close
    window.addEventListener('click', function(e) {
        if (e.target === viewDetailsModal) viewDetailsModal.classList.add('hidden');
        if (e.target === modifyModal) modifyModal.classList.add('hidden');
        if (e.target === cancelModal) cancelModal.classList.add('hidden');
        if (e.target === viewDocumentModal) viewDocumentModal.classList.add('hidden');
    });

    // Functions
function fetchBookingDetails(bookingId) {
    fetch(`profile/fetch_booking_details.php?booking_id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate the view details modal
                document.getElementById('detail-service').textContent = data.service_name;
                document.getElementById('detail-branch').textContent = data.branch_name 
                ? data.branch_name.toLowerCase().replace(/\b\w/g, c => c.toUpperCase())
                : '';
                document.getElementById('detail-status').textContent = data.status;
                document.getElementById('detail-booking-date').textContent = formatDate(data.booking_date);
                document.getElementById('detail-total').textContent = `₱${parseFloat(data.selling_price).toFixed(2)}`;
                
                const container = document.querySelector('.space-y-2');

                // Check if status is Accepted or Declined
                if (data.status === 'Accepted' && data.accepted_date) {
                    // Create new paragraph for Accepted Date
                    const acceptedDateP = document.createElement('p');
                    acceptedDateP.innerHTML = `<span class="text-gray-500">Accepted Date:</span> <span class="text-navy">${formatDate(data.accepted_date)}</span>`;
                    
                    // Insert after the status paragraph
                    document.getElementById('detail-status').parentNode.insertAdjacentElement('afterend', acceptedDateP);
                } 
                else if (data.status === 'Declined' && data.decline_date) {
                    // Create new paragraph for Declined Date
                    const declinedDateP = document.createElement('p');
                    declinedDateP.innerHTML = `<span class="text-gray-500">Declined Date:</span> <span class="text-navy">${formatDate(data.decline_date)}</span>`;
                    
                    // Insert after the status paragraph
                    document.getElementById('detail-status').parentNode.insertAdjacentElement('afterend', declinedDateP);
                }
                
                // Deceased info
                let deceasedName = `${data.deceased_lname}, ${data.deceased_fname}`;
                if (data.deceased_midname) deceasedName += ` ${data.deceased_midname}`;
                if (data.deceased_suffix) deceasedName += ` ${data.deceased_suffix}`;
                
                document.getElementById('detail-deceased-name').textContent = deceasedName 
                ? deceasedName.toLowerCase().replace(/\b\w/g, c => c.toUpperCase())
                : '';
                document.getElementById('detail-birth').textContent = data.deceased_birth ? formatDate(data.deceased_birth) : 'Not provided';
                document.getElementById('detail-dod').textContent = data.deceased_dodeath ? formatDate(data.deceased_dodeath) : 'Not provided';
                document.getElementById('detail-burial').textContent = data.deceased_dateOfBurial ? formatDate(data.deceased_dateOfBurial) : 'Not set';
                document.getElementById('detail-address').textContent = data.deceased_address || 'Not provided';
                
                // Payment info
                document.getElementById('detail-paid').textContent = `₱${parseFloat(data.amount_paid || 0).toFixed(2)}`;
                const balance = parseFloat(data.selling_price) - parseFloat(data.amount_paid || 0);
                document.getElementById('detail-balance').textContent = `₱${balance.toFixed(2)}`;
                document.getElementById('detail-reference').textContent = data.reference_code || 'N/A';
                
                // Store document URLs for viewing
                currentDeathCertUrl = data.death_certificate || '';
                currentPaymentUrl = data.payment_proof || '';
                
                // ALWAYS show buttons (regardless of status or URL existence)
                document.getElementById('viewDeathCertBtn').style.display = 'block';
                document.getElementById('viewPaymentBtn').style.display = 'block';
                
                // Show the modal
                viewDetailsModal.classList.remove('hidden');
            } else {
                showError(data.message || 'Failed to fetch booking details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An error occurred while fetching booking details');
        });
}

// Update your document viewing functions
viewDeathCertBtn.addEventListener('click', function() {
    showDocument('Death Certificate', currentDeathCertUrl);
});

viewPaymentBtn.addEventListener('click', function() {
    showDocument('Payment Proof', currentPaymentUrl);
});

function showDocument(title, url) {
    
    
    document.getElementById('document-modal-title').textContent = title;
    const imgElement = document.getElementById('document-image');
    const pdfElement = document.getElementById('document-pdf');
    
    // Check if the URL is a PDF
    if (url.toLowerCase().endsWith('.pdf')) {
        imgElement.style.display = 'none';
        pdfElement.style.display = 'block';
        pdfElement.src = url;
    } else {
        // For images
        imgElement.style.display = 'block';
        pdfElement.style.display = 'none';
        imgElement.src = url;
    }
    
    viewDocumentModal.classList.remove('hidden');
}

function fetchBookingForModification(bookingId) {
    fetch(`profile/fetch_booking_for_modification.php?booking_id=${bookingId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Populate the modify form
                const form = document.getElementById('modifyBookingForm');
                form.reset();
                
                // Set booking ID and hidden service/branch IDs
                document.getElementById('modify-booking-id').value = data.booking_id;
                document.getElementById('modify-service-id').value = data.service_id;
                document.getElementById('modify-branch-id').value = data.branch_id;
                
                // Display service package and branch name in read-only divs
                document.getElementById('display-service-package').textContent = data.service_name + 
                    ' (₱' + parseFloat(data.selling_price).toFixed(2) + ')';
                document.getElementById('display-branch-location').textContent = data.branch_name;
                
                // Set dates
                form.querySelector('input[name="deceased_dateOfBurial"]').value = data.deceased_dateOfBurial || '';
                form.querySelector('input[name="deceased_birth"]').value = data.deceased_birth || '';
                form.querySelector('input[name="deceased_dodeath"]').value = data.deceased_dodeath || '';
                
                // Set deceased info
                form.querySelector('input[name="deceased_fname"]').value = data.deceased_fname || '';
                form.querySelector('input[name="deceased_midname"]').value = data.deceased_midname || '';
                form.querySelector('input[name="deceased_lname"]').value = data.deceased_lname || '';
                form.querySelector('input[name="deceased_suffix"]').value = data.deceased_suffix || '';
                form.querySelector('textarea[name="deceased_address"]').value = data.deceased_address || '';
                form.querySelector('input[name="with_cremate"]').checked = data.with_cremate == 'yes' || data.with_cremate == 1;
                
                // Show the modal
                document.getElementById('modifyBookingModal').classList.remove('hidden');
            } else {
                showError(data.message || 'Failed to fetch booking for modification');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('An error occurred while fetching booking for modification');
        });
}

function submitBookingModification() {
    const form = document.getElementById('modifyBookingForm');
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Disable submit button during processing
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Saving...';
    
    fetch('booking/update_booking.php', {
        method: 'POST',
        body: formData // FormData will automatically handle file uploads
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess(data.message || 'Booking updated successfully!');
            document.getElementById('modifyBookingModal').classList.add('hidden');
            
            // Refresh the bookings list after a short delay
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showError(data.message || 'Failed to update booking');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while updating booking');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i> Save Changes';
    });
}

// Function to send OTP
document.getElementById('sendOtpBtn').addEventListener('click', function() {
    const bookingId = document.getElementById('cancel-booking-id').value;
    if (!bookingId) {
        showError('No booking selected');
        return;
    }
    
    // Disable button to prevent multiple clicks
    const otpBtn = this;
    otpBtn.disabled = true;
    otpBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
    
    fetch('profile/send_cancel_otp.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ booking_id: bookingId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('OTP sent to your email!');
            document.getElementById('otpInput').focus();
        } else {
            showError(data.message || 'Failed to send OTP');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('Failed to send OTP');
    })
    .finally(() => {
        otpBtn.disabled = false;
        otpBtn.textContent = 'Send OTP';
    });
});

// Form submission handler
document.getElementById('cancelBookingForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Disable submit button during processing
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
    
    fetch('profile/cancel_booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Booking cancelled successfully!');
            // Close modal and update UI without full page reload
            document.getElementById('cancelBookingModal').classList.add('hidden');
            
            // Option 1: If you're using a bookings list, refresh just that section
            // loadBookings(); // Your function to reload bookings
            
            // Option 2: If you must reload, delay slightly for user to see success message
            setTimeout(() => {
                // Only reload if necessary
                if (window.location.pathname.includes('profile')) {
                    window.location.reload();
                } else {
                    // Update UI as needed
                }
            }, 1500);
        } else {
            showError(data.message || 'Failed to cancel booking');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError('An error occurred while cancelling booking');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-times mr-2"></i> Confirm Cancellation';
    });
});

// Remove the redundant submitBookingCancellation() function



    function formatDate(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return new Date(dateString).toLocaleDateString('en-US', options);
    }

    function formatDateForInput(dateString) {
        const date = new Date(dateString);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function showSuccess(message) {
        const notification = document.getElementById('successNotification');
        document.getElementById('successMessage').textContent = message;
        notification.classList.remove('hidden');
        
        setTimeout(() => {
            notification.classList.add('hidden');
        }, 5000);
    }

    function showError(message) {
        const notification = document.getElementById('errorNotification');
        document.getElementById('errorMessage').textContent = message;
        notification.classList.remove('hidden');
        
        setTimeout(() => {
            notification.classList.add('hidden');
        }, 5000);
    }
});
</script>

<script>
    
    // Function to load address data when modal opens
function loadAddressData() {
    const regionId = document.getElementById('region').value;
    if (regionId) {
        updateProvinces();
        
        // We need to wait for provinces to load before selecting the right one
        setTimeout(() => {
            // Select the province if we have the data
            const provinceSelect = document.getElementById('province');
            if (provinceSelect && '<?php echo $province; ?>') {
                for (let i = 0; i < provinceSelect.options.length; i++) {
                    if (provinceSelect.options[i].text === '<?php echo $province; ?>') {
                        provinceSelect.value = provinceSelect.options[i].value;
                        provinceSelect.dispatchEvent(new Event('change'));
                        break;
                    }
                }
            }
            
            // Wait for cities to load
            setTimeout(() => {
                // Select the city if we have the data
                const citySelect = document.getElementById('city');
                if (citySelect && '<?php echo $city; ?>') {
                    for (let i = 0; i < citySelect.options.length; i++) {
                        if (citySelect.options[i].text === '<?php echo $city; ?>') {
                            citySelect.value = citySelect.options[i].value;
                            citySelect.dispatchEvent(new Event('change'));
                            break;
                        }
                    }
                }
                
                // Wait for barangays to load
                setTimeout(() => {
                    // Select the barangay if we have the data
                    const barangaySelect = document.getElementById('barangay');
                    if (barangaySelect && '<?php echo $barangay; ?>') {
                        for (let i = 0; i < barangaySelect.options.length; i++) {
                            if (barangaySelect.options[i].text === '<?php echo $barangay; ?>') {
                                barangaySelect.value = barangaySelect.options[i].value;
                                break;
                            }
                        }
                    }
                }, 500);
            }, 500);
        }, 500);
    }
}

// Call loadAddressData when edit modal opens
document.getElementById('edit-profile-btn').addEventListener('click', function() {
    // Show modal first
    const modal = document.getElementById('edit-profile-modal');
    modal.classList.remove('hidden');
    modal.classList.remove('opacity-0', 'scale-95');
    modal.classList.add('opacity-100', 'scale-100');
    
    // Then load address data
    setTimeout(loadAddressData, 100);
});
// Enhanced address dropdown functions with AJAX
function updateProvinces() {
    const regionId = document.getElementById('region').value;
    const provinceDropdown = document.getElementById('province');
    
    if (!regionId) {
        provinceDropdown.disabled = true;
        document.getElementById('city').disabled = true;
        document.getElementById('barangay').disabled = true;
        return;
    }
    
    // Fetch provinces via AJAX
    fetch('address/get_provinces.php?region_id=' + regionId)
        .then(response => response.json())
        .then(data => {
            provinceDropdown.innerHTML = '<option value="" selected disabled>Select Province</option>';
            data.forEach(province => {
                provinceDropdown.innerHTML += `<option value="${province.province_id}">${province.province_name}</option>`;
            });
            provinceDropdown.disabled = false;
            
            // Reset dependent dropdowns
            document.getElementById('city').innerHTML = '<option value="" selected disabled>Select City/Municipality</option>';
            document.getElementById('city').disabled = true;
            document.getElementById('barangay').innerHTML = '<option value="" selected disabled>Select Barangay</option>';
            document.getElementById('barangay').disabled = true;
        });
}

function updateCities() {
    const provinceId = document.getElementById('province').value;
    const cityDropdown = document.getElementById('city');
    
    if (!provinceId) {
        cityDropdown.disabled = true;
        document.getElementById('barangay').disabled = true;
        return;
    }
    
    // Fetch cities via AJAX
    fetch('address/get_cities.php?province_id=' + provinceId)
        .then(response => response.json())
        .then(data => {
            cityDropdown.innerHTML = '<option value="" selected disabled>Select City/Municipality</option>';
            data.forEach(city => {
                cityDropdown.innerHTML += `<option value="${city.municipality_id}">${city.municipality_name}</option>`;
            });
            cityDropdown.disabled = false;
            
            // Reset dependent dropdown
            document.getElementById('barangay').innerHTML = '<option value="" selected disabled>Select Barangay</option>';
            document.getElementById('barangay').disabled = true;
        });
}

function updateBarangays() {
    const cityId = document.getElementById('city').value;
    const barangayDropdown = document.getElementById('barangay');
    
    if (!cityId) {
        barangayDropdown.disabled = true;
        return;
    }
    
    // Fetch barangays via AJAX
    fetch('address/get_barangays.php?city_id=' + cityId)
        .then(response => response.json())
        .then(data => {
            barangayDropdown.innerHTML = '<option value="" selected disabled>Select Barangay</option>';
            data.forEach(barangay => {
                barangayDropdown.innerHTML += `<option value="${barangay.barangay_id}">${barangay.barangay_name}</option>`;
            });
            barangayDropdown.disabled = false;
        });
}

// Phone number validation with format instructions
function validatePhoneNumber(phone) {
    // Remove all non-digit and non-plus characters
    const cleaned = phone.replace(/[^0-9+]/g, '');
    
    // Check if starts with +63 (Philippines country code)
    if (phone.startsWith('+63')) {
        return cleaned.length === 13; // +63 plus 10 digits
    }
    
    // Check if starts with 0 (local number)
    if (phone.startsWith('0')) {
        return cleaned.length === 11; // 0 plus 10 digits
    }
    
    // If doesn't start with 0 or +63, must be 10 digits
    return cleaned.length === 10;
}

// Restrict phone input to numbers and + only
function restrictPhoneInput() {
    const phoneInput = document.getElementById('phone');
    
    // Add help text
    const helpText = document.createElement('p');
    helpText.className = 'mt-1 text-xs text-gray-500';
    phoneInput.parentNode.appendChild(helpText);
    
    phoneInput.addEventListener('keydown', function(e) {
        // Allow: backspace, delete, tab, escape, enter
        if ([46, 8, 9, 27, 13].includes(e.keyCode) || 
            // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
            (e.keyCode === 65 && e.ctrlKey === true) || 
            (e.keyCode === 67 && e.ctrlKey === true) || 
            (e.keyCode === 86 && e.ctrlKey === true) || 
            (e.keyCode === 88 && e.ctrlKey === true) ||
            // Allow: home, end, left, right
            (e.keyCode >= 35 && e.keyCode <= 39)) {
                return;
        }
        
        // Ensure it's a number or + (only at start)
        if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && 
            (e.keyCode < 96 || e.keyCode > 105) && 
            (e.keyCode !== 187 || this.value.length !== 0)) {
            e.preventDefault();
        }
    });
    
    phoneInput.addEventListener('input', function() {
        const value = this.value;
        // Remove any non-digit/non-plus characters
        this.value = value.replace(/[^0-9+]/g, '');
        
        // Validate in real-time
        if (!value) {
            showError('phone', 'Phone number is required');
        } else if (!validatePhoneNumber(value)) {
            showError('phone', 'Invalid format. Use: 09XXXXXXXXX or +639XXXXXXXXX');
        } else {
            clearError('phone');
        }
    });
}

// Date of birth validation - at least 18 years old
function validateDateOfBirth(dob) {
    if (!dob) return false;
    
    const birthDate = new Date(dob);
    const today = new Date();
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    return age >= 18;
}

// Set up date picker restrictions
function setupDatePicker() {
    const dobInput = document.getElementById('dob');
    const today = new Date();
    const minDate = new Date();
    minDate.setFullYear(today.getFullYear() - 18);
    
    // Set max date to today
    dobInput.max = today.toISOString().split('T')[0];
    
    // Set min date to 18 years ago
    dobInput.min = new Date(today.getFullYear() - 120, today.getMonth(), today.getDate()).toISOString().split('T')[0];
}

// Form validation function
function validateForm() {
    let isValid = true;
    
    // First name validation
    const firstName = document.getElementById('firstName').value.trim();
    if (!firstName) {
        isValid = false;
        showError('firstName', 'First name is required');
    } else {
        clearError('firstName');
    }
    
    // Last name validation
    const lastName = document.getElementById('lastName').value.trim();
    if (!lastName) {
        isValid = false;
        showError('lastName', 'Last name is required');
    } else {
        clearError('lastName');
    }
    
    // Phone number validation
    const phone = document.getElementById('phone').value.trim();
    if (!phone) {
        isValid = false;
        showError('phone', 'Phone number is required');
    } else if (!validatePhoneNumber(phone)) {
        isValid = false;
        showError('phone', 'Please enter a valid Philippine phone number (10 digits, or 11 digits starting with 0, or +63 followed by 10 digits)');
    } else {
        clearError('phone');
    }
    
    // Date of birth validation
    const dob = document.getElementById('dob').value;
    if (dob && !validateDateOfBirth(dob)) {
        isValid = false;
        showError('dob', 'You must be at least 18 years old');
    } else {
        clearError('dob');
    }
    
    // Address validation
    const region = document.getElementById('region').value;
    const province = document.getElementById('province').value;
    const city = document.getElementById('city').value;
    const barangay = document.getElementById('barangay').value;
    const streetAddress = document.getElementById('street_address').value.trim();
    const zip = document.getElementById('zip').value.trim();
    
    if (!region) {
        isValid = false;
        showError('region', 'Region is required');
    } else {
        clearError('region');
    }
    
    if (!province) {
        isValid = false;
        showError('province', 'Province is required');
    } else {
        clearError('province');
    }
    
    if (!city) {
        isValid = false;
        showError('city', 'City/Municipality is required');
    } else {
        clearError('city');
    }
    
    if (!barangay) {
        isValid = false;
        showError('barangay', 'Barangay is required');
    } else {
        clearError('barangay');
    }
    
    if (!streetAddress) {
        isValid = false;
        showError('street_address', 'Street address is required');
    } else {
        clearError('street_address');
    }
    
    if (!zip) {
        isValid = false;
        showError('zip', 'Zip/Postal code is required');
    } else {
        clearError('zip');
    }
    
    return isValid;
}

// Helper function to show error messages
function showError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorElement = document.getElementById(`${fieldId}-error`) || createErrorElement(fieldId);
    
    field.classList.add('border-red-500');
    errorElement.textContent = message;
    errorElement.classList.remove('hidden');
}

// Helper function to clear error messages
function clearError(fieldId) {
    const field = document.getElementById(fieldId);
    const errorElement = document.getElementById(`${fieldId}-error`);
    
    if (errorElement) {
        field.classList.remove('border-red-500');
        errorElement.classList.add('hidden');
    }
}

// Helper function to create error message elements
function createErrorElement(fieldId) {
    const field = document.getElementById(fieldId);
    const errorElement = document.createElement('p');
    errorElement.id = `${fieldId}-error`;
    errorElement.className = 'mt-1 text-sm text-red-600 hidden';
    
    // Insert the error element after the field
    field.parentNode.insertBefore(errorElement, field.nextSibling);
    
    return errorElement;
}

// Define the modal functions
function closeEditProfileModal() {
    const modal = document.getElementById('edit-profile-modal');
    modal.classList.add('opacity-0', 'scale-95');
    modal.classList.remove('opacity-100', 'scale-100');
    
    // After animation completes, hide the modal
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

// Connect the Save Changes button to submit the form with SweetAlert confirmation
document.querySelector('button[type="submit"]').addEventListener('click', function(e) {
    e.preventDefault(); // Prevent the default form submission
    
    // Validate the form first
    if (!validateForm()) {
        Swal.fire({
            title: 'Validation Error',
            text: 'Please correct the errors in the form before submitting.',
            icon: 'error',
            confirmButtonColor: '#d9a404'
        });
        return;
    }
    
    Swal.fire({
        title: 'Save Changes?',
        text: 'Are you sure you want to update your profile information?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#d9a404', // Yellow color matching your theme
        cancelButtonColor: '#718096', // Gray color
        confirmButtonText: 'Yes, save changes',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            // User confirmed, now submit the form
            submitProfileForm();
        }
    });
});

function submitProfileForm() {
    const form = document.getElementById('profile-form');
    const formData = new FormData(form);
    const submitButton = document.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    
    // Show loading state
    submitButton.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Saving...';
    submitButton.disabled = true;
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new TypeError("Oops, we didn't get JSON!");
        }
        
        return response.json();
    })
    .then(data => {
        submitButton.innerHTML = originalButtonText;
        submitButton.disabled = false;
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message,
                confirmButtonColor: '#d9a404'
            }).then(() => {
                closeEditProfileModal();
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'An unknown error occurred',
                confirmButtonColor: '#d9a404'
            });
        }
    })
    .catch(error => {
        submitButton.innerHTML = originalButtonText;
        submitButton.disabled = false;
        
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred: ' + error.message,
            confirmButtonColor: '#d9a404'
        });
    });
}

// Handle form submission in case it's triggered by hitting enter
document.getElementById('profile-form').addEventListener('submit', function(e) {
    e.preventDefault();
    document.querySelector('button[type="submit"]').click();
});

// Close modal when the X button is clicked
document.getElementById('close-edit-profile-modal').addEventListener('click', closeEditProfileModal);

// Initialize the date picker when the DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    setupDatePicker();
    
    // Create error message elements for all required fields
    const requiredFields = [
        'firstName', 'lastName', 'phone', 'region', 'province', 
        'city', 'barangay', 'street_address', 'zip'
    ];
    
    requiredFields.forEach(fieldId => {
        createErrorElement(fieldId);
    });
    
    // Also create for date of birth in case it's required later
    createErrorElement('dob');
});

// Enhanced name validation
function isValidName(name) {
    // Must contain at least one non-space character and no special chars/numbers
    return name.trim().length > 0 && 
           /^[a-zA-Z\s\-'.]+$/.test(name) && 
           !/^\s+$/.test(name);
}

// Real-time validation setup
function setupRealTimeValidation() {

    restrictPhoneInput();

    // First Name
    document.getElementById('firstName').addEventListener('input', function() {
        const value = this.value.trim();
        if (!value) {
            showError('firstName', 'First name is required');
        } else if (!isValidName(value)) {
            showError('firstName', 'Only letters, spaces, hyphens, apostrophes and periods allowed');
        } else {
            clearError('firstName');
        }
    });

    // Last Name
    document.getElementById('lastName').addEventListener('input', function() {
        const value = this.value.trim();
        if (!value) {
            showError('lastName', 'Last name is required');
        } else if (!isValidName(value)) {
            showError('lastName', 'Only letters, spaces, hyphens, apostrophes and periods allowed');
        } else {
            clearError('lastName');
        }
    });

    // Middle Name (optional but still validate format)
    document.getElementById('middleName').addEventListener('input', function() {
        const value = this.value.trim();
        if (value && !isValidName(value)) {
            showError('middleName', 'Only letters, spaces, hyphens, apostrophes and periods allowed');
        } else {
            clearError('middleName');
        }
    });

    // Phone Number
    document.getElementById('phone').addEventListener('input', function() {
        const value = this.value.trim();
        if (!value) {
            showError('phone', 'Phone number is required');
        } else if (!validatePhoneNumber(value)) {
            showError('phone', 'Invalid Philippine phone number format');
        } else {
            clearError('phone');
        }
    });

    // Date of Birth
    document.getElementById('dob').addEventListener('change', function() {
        const value = this.value;
        if (value && !validateDateOfBirth(value)) {
            showError('dob', 'You must be at least 18 years old');
        } else {
            clearError('dob');
        }
    });

    // Address Fields
    const addressFields = ['region', 'province', 'city', 'barangay', 'street_address', 'zip'];
    addressFields.forEach(field => {
        document.getElementById(field).addEventListener('change', function() {
            const value = this.value.trim();
            if (!value) {
                showError(field, `${field.replace('_', ' ')} is required`);
            } else {
                clearError(field);
            }
        });
    });
}

// Update DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    setupDatePicker();
    setupRealTimeValidation();
    
    // Create error message elements
    const fields = [
        'firstName', 'middleName', 'lastName', 'phone', 'dob',
        'region', 'province', 'city', 'barangay', 'street_address', 'zip'
    ];
    
    fields.forEach(fieldId => createErrorElement(fieldId));
});

// Image upload preview and validation
document.getElementById('id-upload').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const previewContainer = document.getElementById('image-preview-container');
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    // Clear previous preview
    previewContainer.innerHTML = '';
    
    if (!file) {
        previewContainer.innerHTML = '<p class="text-gray-500 text-sm">Preview will appear here</p>';
        return;
    }
    
    // Validate file type
    const validTypes = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!validTypes.includes(file.type)) {
        Swal.fire({
            title: 'Invalid File Type',
            text: 'Please upload only JPG or PNG images.',
            icon: 'error',
            confirmButtonColor: '#d9a404'
        });
        e.target.value = ''; // Clear the file input
        previewContainer.innerHTML = '<p class="text-gray-500 text-sm">Preview will appear here</p>';
        return;
    }
    
    // Validate file size
    if (file.size > maxSize) {
        Swal.fire({
            title: 'File Too Large',
            text: 'Maximum file size is 5MB.',
            icon: 'error',
            confirmButtonColor: '#d9a404'
        });
        e.target.value = ''; // Clear the file input
        previewContainer.innerHTML = '<p class="text-gray-500 text-sm">Preview will appear here</p>';
        return;
    }
    
    // Create preview
    const reader = new FileReader();
    reader.onload = function(event) {
        const img = document.createElement('img');
        img.src = event.target.result;
        img.classList.add('max-h-full', 'max-w-full', 'object-contain');
        previewContainer.innerHTML = '';
        previewContainer.appendChild(img);
    };
    reader.readAsDataURL(file);
});
</script>

<script>
// Password change modal functions
function openChangePasswordModal() {
    const modal = document.getElementById('change-password-modal');
    modal.classList.remove('hidden');
    
    // Animate opening
    setTimeout(() => {
        modal.querySelector('.relative').classList.remove('scale-95', 'opacity-0');
        modal.querySelector('.relative').classList.add('scale-100', 'opacity-100');
    }, 10);
    
    // Reset form
    document.getElementById('password-form').reset();
    clearPasswordValidationState();
}

function closeChangePasswordModal() {
    const modal = document.getElementById('change-password-modal');
    modal.querySelector('.relative').classList.remove('opacity-100', 'scale-100');
    modal.querySelector('.relative').classList.add('opacity-0', 'scale-95');
    
    // After animation completes, hide the modal
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}

// Toggle password visibility
function setupPasswordToggle() {
    const toggles = document.querySelectorAll('.password-toggle');
    
    toggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const eyeShow = this.querySelector('.eye-show');
            const eyeHide = this.querySelector('.eye-hide');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeShow.classList.add('hidden');
                eyeHide.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                eyeShow.classList.remove('hidden');
                eyeHide.classList.add('hidden');
            }
        });
    });
}

// Clear password validation state
function clearPasswordValidationState() {
    const checks = ['length-check', 'uppercase-check', 'lowercase-check', 'number-check', 'special-check'];
    checks.forEach(check => {
        document.getElementById(check).classList.remove('text-green-500');
        document.getElementById(check).classList.add('text-gray-500');
    });
    
    // Clear error messages
    document.getElementById('current-password-error').classList.add('hidden');
    document.getElementById('new-password-error').classList.add('hidden');
    document.getElementById('confirm-password-error').classList.add('hidden');
    
    // Remove red borders
    document.getElementById('current-password').classList.remove('border-red-500');
    document.getElementById('new-password').classList.remove('border-red-500');
    document.getElementById('confirm-password').classList.remove('border-red-500');
}

// Validate password strength
function validatePasswordStrength(password) {
    const lengthCheck = password.length >= 8;
    const uppercaseCheck = /[A-Z]/.test(password);
    const lowercaseCheck = /[a-z]/.test(password);
    const numberCheck = /[0-9]/.test(password);
    const specialCheck = /[^A-Za-z0-9]/.test(password);
    
    document.getElementById('length-check').className = lengthCheck ? 'text-green-500' : 'text-gray-500';
    document.getElementById('uppercase-check').className = uppercaseCheck ? 'text-green-500' : 'text-gray-500';
    document.getElementById('lowercase-check').className = lowercaseCheck ? 'text-green-500' : 'text-gray-500';
    document.getElementById('number-check').className = numberCheck ? 'text-green-500' : 'text-gray-500';
    document.getElementById('special-check').className = specialCheck ? 'text-green-500' : 'text-gray-500';
    
    return lengthCheck && uppercaseCheck && lowercaseCheck && numberCheck && specialCheck;
}

// Setup password change form validation
function setupPasswordFormValidation() {
    const currentPasswordInput = document.getElementById('current-password');
    const newPasswordInput = document.getElementById('new-password');
    const confirmPasswordInput = document.getElementById('confirm-password');
    
    // Current password validation
    currentPasswordInput.addEventListener('input', function() {
        if (!this.value) {
            showPasswordError('current-password', 'Current password is required');
        } else {
            clearPasswordError('current-password');
        }
    });
    
    // New password validation with strength requirements
    newPasswordInput.addEventListener('input', function() {
        const password = this.value;
        
        if (!password) {
            showPasswordError('new-password', 'New password is required');
            return;
        }
        
        const isStrong = validatePasswordStrength(password);
        
        if (!isStrong) {
            showPasswordError('new-password', 'Password does not meet all requirements');
        } else {
            clearPasswordError('new-password');
        }
        
        // Check if confirm password matches
        const confirmPassword = confirmPasswordInput.value;
        if (confirmPassword && confirmPassword !== password) {
            showPasswordError('confirm-password', 'Passwords do not match');
        } else if (confirmPassword) {
            clearPasswordError('confirm-password');
        }
    });
    
    // Confirm password validation
    confirmPasswordInput.addEventListener('input', function() {
        const confirmPassword = this.value;
        const newPassword = newPasswordInput.value;
        
        if (!confirmPassword) {
            showPasswordError('confirm-password', 'Please confirm your password');
        } else if (confirmPassword !== newPassword) {
            showPasswordError('confirm-password', 'Passwords do not match');
        } else {
            clearPasswordError('confirm-password');
        }
    });
}

// Show password error
function showPasswordError(fieldId, message) {
    const field = document.getElementById(fieldId);
    const errorElement = document.getElementById(`${fieldId}-error`);
    
    field.classList.add('border-red-500');
    errorElement.textContent = message;
    errorElement.classList.remove('hidden');
}

// Clear password error
function clearPasswordError(fieldId) {
    const field = document.getElementById(fieldId);
    const errorElement = document.getElementById(`${fieldId}-error`);
    
    field.classList.remove('border-red-500');
    errorElement.classList.add('hidden');
}

// Handle form submission
function handleChangePasswordSubmit() {
    const currentPassword = document.getElementById('current-password').value;
    const newPassword = document.getElementById('new-password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    
    // Validate all fields
    let isValid = true;
    
    if (!currentPassword) {
        showPasswordError('current-password', 'Current password is required');
        isValid = false;
    }
    
    if (!newPassword) {
        showPasswordError('new-password', 'New password is required');
        isValid = false;
    } else if (!validatePasswordStrength(newPassword)) {
        showPasswordError('new-password', 'Password does not meet all requirements');
        isValid = false;
    }
    
    if (!confirmPassword) {
        showPasswordError('confirm-password', 'Please confirm your password');
        isValid = false;
    } else if (confirmPassword !== newPassword) {
        showPasswordError('confirm-password', 'Passwords do not match');
        isValid = false;
    }
    
    if (!isValid) {
        return;
    }
    
    // Show loading state
    const submitButton = document.getElementById('submit-change-password');
    const originalButtonText = submitButton.innerHTML;
    submitButton.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Updating...';
    submitButton.disabled = true;
    
    // Submit the form via AJAX
    const form = document.getElementById('password-form');
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        submitButton.innerHTML = originalButtonText;
        submitButton.disabled = false;
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: data.message || 'Password updated successfully!',
                confirmButtonColor: '#d9a404'
            }).then(() => {
                closeChangePasswordModal();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to update password. Please try again.',
                confirmButtonColor: '#d9a404'
            });
        }
    })
    .catch(error => {
        submitButton.innerHTML = originalButtonText;
        submitButton.disabled = false;
        
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred: ' + error.message,
            confirmButtonColor: '#d9a404'
        });
    });
}

// Initialize all change password related functionality
document.addEventListener('DOMContentLoaded', function() {
    // Set up open/close modal buttons
    document.getElementById('open-change-password-modal').addEventListener('click', openChangePasswordModal);
    document.getElementById('close-change-password-modal').addEventListener('click', closeChangePasswordModal);
    document.getElementById('cancel-change-password').addEventListener('click', closeChangePasswordModal);
    
    // Set up form submission
    document.getElementById('submit-change-password').addEventListener('click', handleChangePasswordSubmit);
    document.getElementById('password-form').addEventListener('submit', function(e) {
        e.preventDefault();
        handleChangePasswordSubmit();
    });
    
    // Set up password toggle functionality
    setupPasswordToggle();
    
    // Set up real-time validation
    setupPasswordFormValidation();
});
</script>



<!-- Add Payment Method Modal (Hidden by default) -->
<div id="add-payment-method-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
    <!-- Modal Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm"></div>
    
    <!-- Modal Content -->
    <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md mx-4 z-10 transform transition-all duration-300 scale-95 opacity-0">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-yellow-600 to-white flex justify-between items-center p-6 flex-shrink-0">
            <h3 class="text-xl font-bold text-white">Add Payment Method</h3>
            <button type="button" id="close-add-payment-method-modal" class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 text-white hover:text-white transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6">
            <p class="text-gray-600 mb-4">Enter your payment details below.</p>
            
            <form class="space-y-4">
                <div>
                    <label for="cardNumber" class="block text-sm font-medium text-gray-700 mb-1">Card Number*</label>
                    <div class="relative">
                        <input type="text" id="cardNumber" name="cardNumber" placeholder="1234 5678 9012 3456" required class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent pr-10">
                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-yellow-600">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                                <line x1="1" y1="10" x2="23" y2="10"></line>
                            </svg>
                        </span>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="expDate" class="block text-sm font-medium text-gray-700 mb-1">Expiration Date*</label>
                        <input type="text" id="expDate" name="expDate" placeholder="MM/YY" required class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label for="cvv" class="block text-sm font-medium text-gray-700 mb-1">CVV*</label>
                        <input type="text" id="cvv" name="cvv" placeholder="123" required class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent">
                    </div>
                </div>
                
                <div>
                    <label for="nameOnCard" class="block text-sm font-medium text-gray-700 mb-1">Name on Card*</label>
                    <input type="text" id="nameOnCard" name="nameOnCard" placeholder="John M. Doe" required class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent">
                </div>
                
                <!-- Card Selection -->
                <div class="bg-navy p-5 rounded-xl shadow-sm border border-purple-100">
                    <h4 class="text-lg font-bold mb-4 text-gray-700 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 text-yellow-600">
                            <rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect>
                            <line x1="1" y1="10" x2="23" y2="10"></line>
                        </svg>
                        Card Type
                    </h4>
                    <div class="flex flex-wrap gap-3">
                        <label class="flex-1 min-w-32 inline-flex items-center p-3 border border-gray-300 rounded-lg bg-white hover:border-yellow-600 hover:shadow-md cursor-pointer transition-all">
                            <input type="radio" name="cardType" value="visa" class="mr-2 h-4 w-4 text-yellow-600" checked>
                            <span class="font-medium text-gray-800">Visa</span>
                        </label>
                        <label class="flex-1 min-w-32 inline-flex items-center p-3 border border-gray-300 rounded-lg bg-white hover:border-yellow-600 hover:shadow-md cursor-pointer transition-all">
                            <input type="radio" name="cardType" value="mastercard" class="mr-2 h-4 w-4 text-yellow-600">
                            <span class="font-medium text-gray-800">Mastercard</span>
                        </label>
                        <label class="flex-1 min-w-32 inline-flex items-center p-3 border border-gray-300 rounded-lg bg-white hover:border-yellow-600 hover:shadow-md cursor-pointer transition-all">
                            <input type="radio" name="cardType" value="amex" class="mr-2 h-4 w-4 text-yellow-600">
                            <span class="font-medium text-gray-800">Amex</span>
                        </label>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Modal Footer -->
        <div class="modal-sticky-footer p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
            <button class="px-5 py-3 bg-white border border-yellow-600 text-gray-800 rounded-lg font-semibold hover:bg-navy transition-colors" onclick="closeAddPaymentModal()">Cancel</button>
            <button class="px-6 py-3 bg-yellow-600 text-white rounded-lg font-semibold hover:bg-darkgold transition-colors flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Add Payment Method
            </button>
        </div>
    </div>
</div>

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
</body> 
</html>