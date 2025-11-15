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
    <title>GrievEase - About Us</title>
    <?php include 'faviconLogo.php'; ?>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Alex+Brush&family=Inter:wght@300;400;500;600&family=Cinzel:wght@400;500;600;700&family=Hedvig+Letters+Serif:wght@400;500&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../tailwind.js"></script>
    <style>
        :root {
            --navbar-height: 64px; /* Define the height of the navbar */
            --section-spacing: 4rem; /* Standardized spacing between sections */
        }
        
        /* Organization Chart Styles */
        .org-chart-container {
            position: relative;
            padding: 20px 0;
        }
        
        /* Connectors */
        .connector-vertical {
            position: absolute;
            top: 80px;
            left: 50%;
            height: 40px;
            width: 2px;
            background-color: #B4530980;
            transform: translateX(-50%);
        }
        
        .connector-horizontal-top {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            height: 2px;
            width: 90%;
            background-color: #B4530980;
            z-index: 0;
        }
        
        .connector-container {
            position: relative;
            height: 40px;
            margin-bottom: 10px;
        }
        
        .connector-vertical-left {
            position: absolute;
            top: 0;
            left: calc(25% - 26px);
            height: 30px;
            width: 2px;
            background-color: #B4530980;
        }
        
        .connector-vertical-center {
            position: absolute;
            top: 0;
            left: 50%;
            height: 30px;
            width: 2px;
            background-color: #B4530980;
            transform: translateX(-50%);
        }
        
        .connector-vertical-right {
            position: absolute;
            top: 0;
            right: calc(25% - 26px);
            height: 30px;
            width: 2px;
            background-color: #B4530980;
        }
        
        .org-box {
            position: relative;
            z-index: 1;
        }
        
        .org-ceo {
            margin-bottom: 10px;
        }
        
        .team-container {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        /* Section margins standardization */
        .section {
            margin-bottom: var(--section-spacing);
        }
        
        /* Consistent section heading spacing */
        .section-heading {
            margin-bottom: 2rem;
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

    <div class="bg-cream py-20">
    <div class="container mx-auto px-6 max-w-6xl">
        <!-- Hero Section -->
        <div class="relative rounded-2xl overflow-hidden mb-16 shadow-xl">
            <div class="h-64 bg-cover bg-center" style="background-image: url('../Landing_Page/Landing_images/black-bg-image.jpg')">
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="absolute inset-0 flex flex-col justify-center items-center text-white p-8">
                    <h1 class="text-5xl md:text-6xl font-hedvig text-center mb-6">About Us</h1>
                </div>
            </div>
        </div>
    </div>

<!-- Main Content Container with standardized padding -->
<div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <!-- GrievEase Section -->
    <div id="about" class="bg-cream">
    <div class="container mx-auto px-6">
        <!-- GrievEase Section Header -->
        <div class="text-center mb-16">
            <h2 class="text-5xl md:text-5xl font-hedvig text-navy mb-4">GrievEase</h2>
            <p class="text-dark text-lg max-w-3xl mx-auto">Our digital platform enhancing the VJay Relova Funeral Services experience for families.</p>
            <div class="w-20 h-1 bg-yellow-600 mx-auto mt-6"></div>
        </div>
    <div class="section grid grid-cols-1 lg:grid-cols-2 gap-12 items-center mb-8" data-aos="fade-up">
        <!-- Left Column - Image -->
        <div class="relative">
            <div class="relative rounded-lg overflow-hidden shadow-xl">
                <img src="../Landing_Page/Landing_images/logo.png" alt="GrievEase Platform" class="w-full h-auto">
                <div class="absolute inset-0 bg-black/20"></div>
            </div>
            <!-- Decorative Element -->
            <div class="absolute -bottom-6 -right-6 w-32 h-32 border-r-2 border-b-2 border-yellow-600/30 hidden md:block"></div>
        </div>
        
        
        <!-- Right Column - Text Content -->
        <div class="space-y-6">
            <h2 class="text-3xl font-hedvig text-navy">GrievEase</h2>
            <div class="w-16 h-1 bg-yellow-600"></div>
            <p class="text-dark text-lg">
                GrievEase is our innovative web system created specifically for VJay Relova Funeral Services clients. We understand that arranging funeral services can be overwhelming during a difficult time, which is why we've developed this digital platform to simplify the process.
            </p>
            <p class="text-dark text-lg">
                Our platform provides a secure online space where you can manage funeral arrangements, view service details, share memories, and coordinate with family members—all with the support of our team.
            </p>
            <div class="border-l-4 border-yellow-600 pl-4 italic my-6">
                <p class="text-lg text-navy">"Technology with a heart, serving families when they need it most."</p>
            </div>
            
            
        </div>
    </div>

    <!-- VJay Relova Section Header -->
    <div class="text-center mb-16 pt-12">
            <h2 class="text-5xl md:text-5xl font-hedvig text-navy mb-4">VJay Relova Funeral Services</h2>
            <p class="text-dark text-lg max-w-2xl mx-auto">Offering compassionate funeral services to our community since 1980.</p>
            <div class="w-20 h-1 bg-yellow-600 mx-auto mt-6"></div>
        </div>

    <!-- Our History Section (VJay Relova) -->
    <div class="section grid grid-cols-1 lg:grid-cols-2 gap-12 items-center" data-aos="fade-up">
            <!-- Left Column - Text Content -->
            <div class="space-y-6">
                <h2 class="text-3xl font-hedvig text-navy">Our Story</h2>
                <div class="w-16 h-1 bg-yellow-600"></div>
                <p class="text-dark text-lg">
                Founded in 1980 by Bernardo "Sosoy" Relova Jr., VJay Relova Funeral Services has grown from a small family business to a trusted name in funeral care. With branches in Paete, and our main branch in Pila, Laguna, we continue to serve families with compassion and dignity.
                </p>
                <div class="border-l-4 border-yellow-600 pl-4 italic my-6">
                    <p class="text-lg text-navy">"Mula noon, hanggang ngayon. A funeral service with a Heart..."</p>
                </div>
                <p class="text-dark text-lg">
                    Today, VJay Relova continues to be family-operated, preserving the personal touch that has distinguished us for over four decades while embracing modern approaches to memorial services that meet the evolving needs of our community.
                </p>
                <h3 class="text-3xl font-hedvig text-navy">Our Values</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div class="flex items-start">
                        <div class="bg-yellow-600/10 p-2 rounded-full mr-3">
                            <i class="fas fa-heart text-yellow-600"></i>
                        </div>
                        <div>
                            <h4 class="font-hedvig font-medium text-navy">Compassion</h4>
                            <p class="text-sm text-dark">We approach each family with genuine care and empathy.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="bg-yellow-600/10 p-2 rounded-full mr-3">
                            <i class="fas fa-handshake text-yellow-600"></i>
                        </div>
                        <div>
                            <h4 class="font-hedvig font-medium text-navy">Integrity</h4>
                            <p class="text-sm text-dark">We conduct our services with honesty and transparency.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="bg-yellow-600/10 p-2 rounded-full mr-3">
                            <i class="fas fa-users text-yellow-600"></i>
                        </div>
                        <div>
                            <h4 class="font-hedvig font-medium text-navy">Respect</h4>
                            <p class="text-sm text-dark">We honor diverse cultural and religious traditions.</p>
                        </div>
                    </div>
                    <div class="flex items-start">
                        <div class="bg-yellow-600/10 p-2 rounded-full mr-3">
                            <i class="fas fa-star text-yellow-600"></i>
                        </div>
                        <div>
                            <h4 class="font-hedvig font-medium text-navy">Excellence</h4>
                            <p class="text-sm text-dark">We strive for the highest standards in all our services.</p>
                        </div>
                    </div>
                </div>
            </div>
        
        <!-- Right Column - Image -->
        <div class="relative">
            <div class="relative rounded-lg overflow-hidden shadow-xl">
                <img src="../Landing_Page/Landing_images/sampleImageLANG.jpg" alt="VJay Relova History" class="w-full h-auto">
                <div class="absolute inset-0 bg-black/20"></div>
            </div>
            <!-- Decorative Element -->
            <div class="absolute -bottom-6 -right-6 w-32 h-32 border-r-2 border-b-2 border-yellow-600/30 hidden md:block"></div>
        </div>
    </div>
</div>
    </div>
        
        <!-- Our Philosophy Section -->
        <div class="section" data-aos="fade-up">
            <div class="section-heading text-center">
                <h2 class="text-5xl font-hedvig text-navy">Our Philosophy</h2>
                <div class="w-16 h-1 bg-yellow-600 mx-auto mt-3"></div>
            </div>
            
            <!-- Added max-width container -->
            <div class="max-w-5xl mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <!-- Philosophy Item 1 -->
                    <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="bg-yellow-600/10 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                            <i class="fas fa-heart text-yellow-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-hedvig text-navy text-center mb-4">Compassionate Care</h3>
                        <p class="text-dark text-sm text-center">
                            We approach each family with genuine empathy, understanding that each grief journey is unique and deserves personalized attention and support.
                        </p>
                    </div>
                    
                    <!-- Philosophy Item 2 -->
                    <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="bg-yellow-600/10 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                            <i class="fas fa-hands text-yellow-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-hedvig text-navy text-center mb-4">Dignified Service</h3>
                        <p class="text-dark text-sm text-center">
                            We believe in honoring each life with dignity and respect, creating meaningful ceremonies that celebrate the uniqueness of the individual.
                        </p>
                    </div>
                    
                    <!-- Philosophy Item 3 -->
                    <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="bg-yellow-600/10 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                            <i class="fas fa-users text-yellow-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-hedvig text-navy text-center mb-4">Commitment</h3>
                        <p class="text-dark text-sm text-center">
                            As members of the community we serve, we are dedicated to supporting families beyond the funeral service, offering continued guidance and resources.
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Our Team Section -->
        <div class="section" data-aos="fade-up">
            <div class="section-heading text-center">
                <h2 class="text-5xl font-hedvig text-navy">Our Dedicated Team</h2>
                <div class="w-16 h-1 bg-yellow-600 mx-auto mt-3"></div>
                <p class="text-dark text-lg max-w-3xl mx-auto mt-4">
                    Meet the dedicated professionals who lead VJay Relova Funeral Services with compassion and excellence.
                </p>
            </div>
            
            <!-- Leadership Team -->
            <!-- Container with max-width constraint -->
            <div class="max-w-5xl mx-auto px-4"> <!-- Added max-width constraint -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <!-- Team Member 1 -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 group">
                        <div class="h-64 bg-cover bg-center relative" style="background-image: url('../image/vjay_avatar.jpg')">
                            <div class="absolute inset-0 bg-black/30 group-hover:bg-black/20 transition-all duration-300"></div>
                        </div>
                        <div class="p-6 text-center">
                            <h4 class="text-xl font-hedvig text-navy mb-1">Virgillo Jay G. Relova</h4>
                            <p class="text-yellow-600 mb-3">Owner & General Manager</p>
                            <p class="text-dark text-sm">Leading with compassion and vision for over four decades, ensuring that every family receives exceptional care.</p>
                        </div>
                    </div>
                    
                    <!-- Team Member 2 -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 group">
                        <div class="h-64 bg-cover bg-center relative" style="background-image: url('../image/marcial_avatar.jpg')">
                            <div class="absolute inset-0 bg-black/30 group-hover:bg-black/20 transition-all duration-300"></div>
                        </div>
                        <div class="p-6 text-center">
                            <h4 class="text-xl font-hedvig text-navy mb-1">Marcial Legua</h4>
                            <p class="text-yellow-600 mb-3">Operations Head</p>
                            <p class="text-dark text-sm">Overseeing all operational aspects of VJay Relova to ensure seamless service delivery for every family.</p>
                        </div>
                    </div>
                    
                    <!-- Team Member 3 -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 group">
                        <div class="h-64 bg-cover bg-center relative" style="background-image: url('../image/dave_avatar.jpg')">
                            <div class="absolute inset-0 bg-black/30 group-hover:bg-black/20 transition-all duration-300"></div>
                        </div>
                        <div class="p-6 text-center">
                            <h4 class="text-xl font-hedvig text-navy mb-1">Dave Ramos</h4>
                            <p class="text-yellow-600 mb-3">Financial Manager</p>
                            <p class="text-dark text-sm">Managing the financial aspects with integrity and transparency to provide fair and accessible services.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Organization Chart - Hierarchical Design -->
<h3 class="text-5xl font-hedvig text-navy text-center">Our Organizational Chart</h3>
<div class="w-16 h-1 bg-yellow-600 mx-auto mt-3 mb-8"></div>

<!-- Image replacement -->
<div class="max-w-5xl mx-auto px-4">
    <img src="../image/orgchartfinal.png" alt="Organization Chart" class="w-full h-auto rounded-lg shadow-md">
</div>

<div>
    </div>
    </div>

        <!-- Our Partnerships Section -->
        <div class="section mt-16" data-aos="fade-up">
            <div class="section-heading text-center">
                <h2 class="text-5xl font-hedvig text-navy">Our Partners</h2>
                <div class="w-16 h-1 bg-yellow-600 mx-auto mt-3"></div>
                <p class="text-dark text-lg max-w-3xl mx-auto mt-4">
                    We work with trusted partners to provide comprehensive services of the highest quality.
                </p>
            </div>
            
            <!-- Added max-width container -->
            <div class="max-w-5xl mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <!-- Partnership 1 -->
                    <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="h-16 w-16 bg-yellow-600/10 rounded-full flex items-center justify-center mx-auto mb-4">
                            <img src="../image/flower.png">
                        </div>
                        <h3 class="text-xl font-hedvig text-navy text-center mb-2">Floral Services</h3>
                        <p class="text-center text-dark font-medium">Roselle's Flowershop</p>
                        
                    </div>
                    
                    <!-- Partnership 2 -->
                    <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="h-16 w-16 bg-yellow-600/10 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-fire text-yellow-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-hedvig text-navy text-center mb-2">Cremation Services</h3>
                        <p class="text-center text-dark font-medium">Laguna Sunrise & Peter Anthony Crematorium</p>
                        
                    </div>
                    
                    <!-- Partnership 3 -->
                    <div class="bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition-all duration-300">
                        <div class="h-16 w-16 bg-yellow-600/10 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-box text-yellow-600 text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-hedvig text-navy text-center mb-2">Casket Craftsmanship</h3>
                        <p class="text-center text-dark font-medium">Edwin Batac Enterprises</p>
                        
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
</body>
</html>