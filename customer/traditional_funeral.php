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
                    'declined' => 0
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
                }
                $conn->close();
?>

<script src="customer_support.js"></script>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Traditional Funeral Service</title>
    <?php include 'faviconLogo.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hedvig+Letters+Serif:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="../tailwind.js"></script>
    <style>
        .modal {
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
        }
        :root {
            --navbar-height: 64px; /* Adjust this value as needed */
        }
        .modal.active {
            opacity: 1;
            pointer-events: auto;
        }
        .notification {
            animation: slideIn 0.5s forwards, slideOut 0.5s 4.5s forwards;
        }
        @keyframes slideIn {
            from { transform: translateY(-100%); }
            to { transform: translateY(0); }
        }
        @keyframes slideOut {
            from { transform: translateY(0); }
            to { transform: translateY(-100%); }
        }

        .candlelight {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 15px 20px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 90;
            font-size: 24px;
            box-shadow: 0 0 20px rgba(212, 175, 55, 0.5);
            transition: all 0.3s ease;
        }

        .candlelight:hover {
            transform: scale(1.1);
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.8);
        }
        html {
        scroll-behavior: smooth;
    }
    </style>
    <script>
        function toggleMenu() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        }

        // Enhanced carousel functionality with infinite loop
        document.addEventListener('DOMContentLoaded', function() {
            const partnerContainer = document.querySelector('#partner-carousel');
            const partners = document.querySelectorAll('.partner-logo');
            const totalPartners = partners.length;
            const visiblePartners = window.innerWidth < 768 ? 2 : 4;
            let position = 0;
            
            // Clone partners for infinite loop
            partners.forEach(partner => {
                const clone = partner.cloneNode(true);
                partnerContainer.appendChild(clone);
            });
            
            function moveCarousel() {
                position++;
                
                // Reset position smoothly for infinite loop
                if (position >= totalPartners) {
                    // Quick reset after transition completes
                    setTimeout(() => {
                        partnerContainer.style.transition = 'none';
                        position = 0;
                        partnerContainer.style.transform = `translateX(-${position * (100 / visiblePartners)}%)`;
                        // Re-enable transition after reset
                        setTimeout(() => {
                            partnerContainer.style.transition = 'transform 500ms ease-in-out';
                        }, 50);
                    }, 500);
                }
                
                partnerContainer.style.transform = `translateX(-${position * (100 / visiblePartners)}%)`;
            }
            
            // Center the carousel
            partnerContainer.style.display = 'flex';
            partnerContainer.style.justifyContent = 'center';
            
            setInterval(moveCarousel, 3000);
        });
    </script>
</head>
<body class="bg-cream overflow-x-hidden w-full max-w-full m-0 p-0 font-hedvig">
    <!-- Notification Toast (Hidden by default) -->
    <div id="notification" class="fixed top-0 right-0 m-4 p-4 bg-black text-white rounded shadow-lg z-50 hidden notification">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span id="notification-message">Notification message here</span>
        </div>
    </div>

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
                    <?php if ($notifications_count['pending'] > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                        <?php echo $notifications_count['pending']; ?>
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
                    <?php if ($notifications_count['pending'] > 0): ?>
                    <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">
                        <?php echo $notifications_count['pending']; ?>
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

<!-- Traditional Funeral Services Main Content -->
<div class="bg-cream py-20">
    <div class="container mx-auto px-6 max-w-6xl">
        <!-- Hero Section -->
        <div class="relative rounded-2xl overflow-hidden mb-16 shadow-xl">
            <div class="h-64 bg-cover bg-center" style="background-image: url('../Landing_Page/Landing_images/sampleImageLANG.jpg')">
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="absolute inset-0 flex flex-col justify-center items-center text-white p-8">
                    <h1 class="text-5xl md:text-6xl font-hedvig text-center mb-6">Traditional Funeral</h1>
                    <p class="text-xl max-w-3xl text-center">Honoring your loved one with dignity, respect, and time-honored traditions</p>
                </div>
            </div>
        </div>

        <!-- Introduction Section -->
        <div class="max-w-4xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-hedvig text-navy mb-6">A Dignified Farewell</h2>
            <p class="text-lg text-dark">Our traditional funeral services honor your loved one with dignity and respect while providing support for family and friends. Each service can be customized to reflect the unique life being celebrated.</p>
            <div class="w-20 h-1 bg-yellow-600 mx-auto mt-8"></div>
        </div>

        <!-- Service Features -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-20">
            <!-- Feature 1 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 text-center">
                <div class="w-16 h-16 bg-yellow-600/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-users text-2xl text-yellow-600"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy mb-4">Viewing & Visitation</h3>
                <p class="text-dark text-sm">A private or public gathering that allows family and friends to pay their respects and offer condolences in a supportive environment.</p>
            </div>
            
            <!-- Feature 2 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 text-center">
                <div class="w-16 h-16 bg-yellow-600/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-church text-2xl text-yellow-600"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy mb-4">Funeral Ceremony</h3>
                <p class="text-dark text-sm">A formal service held at our chapel, a place of worship, or another meaningful location to honor and celebrate your loved one's life.</p>
            </div>
            
            <!-- Feature 3 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 text-center">
                <div class="w-16 h-16 bg-yellow-600/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-car text-2xl text-yellow-600"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy mb-4">Procession & Committal</h3>
                <p class="text-dark text-sm">A dignified journey to the final resting place, followed by a brief but meaningful graveside service for final farewells.</p>
            </div>
        </div>

        <!-- Traditional Funeral Services Section -->
<section id="traditional" class="scroll-mt-24 mb-16 px-4 sm:px-0">
    <!-- Section Header - Centered -->
    <div class="flex justify-center mb-6 sm:mb-8">
        <div class="flex items-center">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-yellow-600/90 flex items-center justify-center text-white mr-3 sm:mr-4">
                <i class="fas fa-dove text-lg sm:text-xl"></i>
            </div>
            <h2 class="font-hedvig text-3xl sm:text-4xl text-navy">Traditional</h2>
        </div>
    </div>
    
    <!-- Paragraph - Centered -->
    <div class="flex justify-center mb-6">
        <p class="text-dark max-w-4xl text-center text-sm sm:text-lg">Our traditional funeral services honor your loved one with dignity and respect while providing support for family and friends. Each service can be customized to reflect the unique life being celebrated.</p>
    </div>
    
    <!-- Packages Carousel -->
    <div class="max-w-6xl mx-auto relative">
        <!-- Carousel Container -->
        <div class="overflow-hidden relative">
            <div id="carousel-container" class="flex transition-transform duration-500 ease-in-out">
                <!-- Package 1: Legacy Tribute -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                    <div class="package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="700000" data-service="traditional" data-name="Legacy Tribute" data-image="../image/700.jpg">
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
                                    <span class="text-dark">3 sets of flower arrangements</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Catering on last day</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Premium casket selection</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Extended viewing period</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Complete funeral service</span>
                                </li>
                            </ul>
                            <button class="selectPackageBtn block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                                Select Package
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Package 2: Eternal Remembrance -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                    <div class="package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="300000" data-service="traditional" data-name="Eternal Remembrance" data-image="../image/300.jpg">
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
                                    <span class="text-dark">2 sets of flower arrangements</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Premium casket selection</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Extended viewing period</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Complete funeral service</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Curtains and lighting</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Water dispenser</span>
                                </li>
                            </ul>
                            <button class="selectPackageBtn block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                                Select Package
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Package 3: Custom Memorial -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                    <div class="bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="custom" data-service="traditional" data-name="Custom Memorial" data-image="image/custom.jpg">
                        <div class="h-10 sm:h-12 bg-navy flex items-center justify-center">
                            <h4 class="text-white font-hedvig text-lg sm:text-xl">Custom Memorial</h4>
                            <div class="absolute top-0 right-0 w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center">
                                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                            </div>
                        </div>
                        <div class="p-4 sm:p-6 flex flex-col flex-grow">
                            <div class="mb-3 sm:mb-4 flex justify-center">
                                <img src="../Landing_Page/Landing_images/logo.png" alt="Custom Memorial" class="w-full h-40 sm:h-48 object-cover rounded-lg">
                            </div>
                            <div class="text-center mb-4 sm:mb-6">
                                <span class="text-2xl sm:text-3xl font-hedvig text-navy">Starting at ₱150,000</span>
                                <p class="text-xs sm:text-sm text-gray-600 mt-1">Final price depends on selections</p>
                            </div>
                            <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 flex-grow text-sm sm:text-base">
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark"><strong>Choose your casket</strong> from our selection</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark"><strong>Select flower arrangements</strong> (1-3 sets)</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark"><strong>Customize viewing period</strong> to your needs</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark"><strong>Add catering options</strong> if desired</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Core funeral services included</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Personalized assistance throughout</span>
                                </li>
                            </ul>
                            <button class="customtraditionalpckg block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                                Customize Package
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
        <div class="flex justify-center mt-4 sm:mt-6">
            <div id="carousel-dots" class="flex space-x-2">
                <button class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-navy opacity-100" data-index="0"></button>
                <button class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-navy opacity-50" data-index="1"></button>
                <button class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-navy opacity-50" data-index="2"></button>
                <button class="w-2 h-2 sm:w-3 sm:h-3 rounded-full bg-navy opacity-50" data-index="3"></button>
            </div>
        </div>
    </div>
</section>
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
        const itemCount = 4; // Total number of items (3 packages + view all)
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

    <!-- FAQ Section -->
    <div class="container mx-auto px-6  max-w-4xl">
        
    <!-- FAQ Section -->
<div class="container mx-auto px-6  max-w-4xl">
        <!-- Section Header -->
        <div class="text-center mb-16">
            <h2 class="text-5xl font-hedvig text-navy mb-4">We're Here to Help</h2>
            <p class="text-dark text-lg max-w-3xl mx-auto">Find answers to the most common questions about VJay Relova Funeral Services and our bereavement support.</p>
            <div class="w-20 h-1 bg-yellow-600 mx-auto mt-6"></div>
        </div>
        
        <!-- FAQ Accordion -->
        <div class="space-y-6 mb-12">
            <!-- FAQ Item 1 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">What should I do when a death occurs?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>When a death occurs, the first step is to notify the appropriate authorities:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li>If the death occurs at home, call emergency services (911) or the person's doctor.</li>
                            <li>If the death occurs at a hospital or care facility, staff will guide you through the initial procedures.</li>
                            <li>Once the death has been officially pronounced, contact us at GrievEase at our 24/7 number for immediate assistance.</li>
                        </ul>
                        <p class="mt-4">Our compassionate staff will guide you through the next steps, including transportation of your loved one to our facility and beginning the arrangement process.</p>
                        <p class="mt-4"><strong>Important:</strong> In cases of accident or disease-related deaths, there may be additional processes involving SOCO (Scene of the Crime Operatives) that need to be followed before the burial can proceed.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 2 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">How do I plan a meaningful funeral service?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>Planning a meaningful funeral service involves several considerations:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li>Reflect on your loved one's personality, interests, and wishes</li>
                            <li>Consider religious or cultural traditions that were important to them</li>
                            <li>Select meaningful music, readings, or other elements that celebrate their life</li>
                            <li>Decide on personalization options like photo displays, memory tables, or tribute videos</li>
                        </ul>
                        <p class="mt-4">At VJay Relova Funeral Services, we believe that the personal connection between our staff and your family is essential. Our owner will personally talk with you to understand your specific needs and requests. We'll guide you through each step, helping you create a service that truly honors your loved one's life and legacy.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 3 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">What is the difference between burial and cremation?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>The main differences between burial and cremation involve:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li><strong>Process:</strong> Burial preserves the body in a casket placed in the ground or a mausoleum, while cremation reduces the body to cremated remains through heat.</li>
                            <li><strong>Timeline:</strong> Burials typically occur within a week of death, while cremation offers more flexibility for memorial service timing.</li>
                            <li><strong>Memorialization:</strong> Burial provides a permanent gravesite to visit, while cremated remains can be kept in an urn, scattered, or placed in a columbarium.</li>
                            <li><strong>Cost:</strong> Cremation is generally less expensive than traditional burial due to fewer required elements.</li>
                        </ul>
                        <p class="mt-4">For cremation services, we partner with third-party crematoriums. The advantage of arranging cremation through us is that we can help prioritize your scheduling needs. We can arrange for cremation on a specific day of your choosing, even if there are others scheduled for cremation on the same day.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 4 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">What are the benefits of pre-planning a funeral?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>Pre-planning a funeral offers several significant benefits:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li>Ensures your specific wishes are known and honored</li>
                            <li>Relieves your loved ones from making difficult decisions during a time of grief</li>
                            <li>Provides opportunity to make thoughtful, informed choices without time pressure</li>
                            <li>Allows you to handle financial arrangements in advance, potentially saving money</li>
                            <li>Creates peace of mind for you and your family</li>
                        </ul>
                        <p class="mt-4">We offer pre-need installment plans for those who wish to plan ahead. Some clients prefer our services over other pre-need companies because our packages are complete and customizable, with negotiable prices. We work with you to ensure everything you desire for your funeral service is included in your plan.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 5 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">How much does a funeral typically cost?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>Our funeral packages range from ₱25,000 for a complete basic package to ₱500,000 for premium services. We've provided services up to ₱800,000-1,000,000 for clients with specific requirements.</p>
                        <p class="mt-2">A typical funeral package may include:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li>Casket (various options available, including imported brass caskets)</li>
                            <li>Flowers (replaced twice for premium packages)</li>
                            <li>Chapel rental with curtains and lighting</li>
                            <li>Embalming services with licensed professionals</li>
                            <li>Hearse and transportation</li>
                            <li>Viewing equipment (water dispenser, tent, chairs, tables)</li>
                        </ul>
                        <p class="mt-4">At VJay Relova Funeral Services, we're committed to transparency in pricing. Our packages are customizable, and prices are negotiable based on your needs. We offer automatic 20% discounts for PWDs and senior citizens, and we can discuss other discount options based on your situation.</p>
                        <p class="mt-2">For services over ₱100,000, we require a 30% down payment. For services under ₱100,000, payment is typically made before or on the day of burial.</p>
                    </div>
                </div>
            </div>
            <!-- View All FAQs Link -->
            <div class="text-center mt-8">
                <a href="faqs.php" class="text-yellow-600 hover:text-yellow-700  text-lg">
                    View All FAQs <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
    </div>
    </div>
    </div>

    <!-- Traditional Funeral Modal (Hidden by Default) -->
<div id="traditionalModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[80vh]">
        <!-- Scroll container for both columns -->
        <div class="modal-scroll-container grid grid-cols-1 md:grid-cols-2 overflow-y-auto max-h-[80vh]">
            <!-- Left Side: Package Details -->
            <div class="bg-cream p-8">
                <!-- Package Image -->
                <div class="mb-6">
                    <img id="traditionalPackageImage" src="" alt="" class="w-full h-64 object-cover rounded-lg mb-4">
                </div>

                <!-- Package Header -->
                <div class="flex justify-between items-center mb-6">
                    <h2 id="traditionalPackageName" class="text-3xl font-hedvig text-navy"></h2>
                    <div id="traditionalPackagePrice" class="text-3xl font-hedvig text-yellow-600"></div>
                </div>

                <!-- Package Description -->
                <p id="traditionalPackageDesc" class="text-dark mb-6"></p>

                <!-- Main Package Details -->
                <div class="border-t border-gray-200 pt-4">
                    <h3 class="text-xl font-hedvig text-navy mb-4">Package Includes:</h3>
                    <ul id="traditionalPackageFeatures" class="space-y-2">
                        <!-- Features will be inserted here by JavaScript -->
                    </ul>
                </div>

                <div class="border-t border-gray-200 pt-4 mt-6">
    <h3 class="text-xl font-hedvig text-navy mb-4">Additional Services:</h3>
    <div id="traditionalAdditionalServices" class="space-y-3">
        <div class="flex items-center">
            <input type="checkbox" id="traditionalFlowers" name="additionalServices" value="3500" class="traditional-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Floral Arrangements">
            <label for="traditionalFlowers" class="ml-3 text-sm text-gray-700">Floral Arrangements (₱3,500)</label>
        </div>
        <div class="flex items-center">
            <input type="checkbox" id="traditionalCatering" name="additionalServices" value="15000" class="traditional-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Catering Service (50 pax)">
            <label for="traditionalCatering" class="ml-3 text-sm text-gray-700">Catering Service - 50 pax (₱15,000)</label>
        </div>
        <div class="flex items-center">
            <input type="checkbox" id="traditionalVideography" name="additionalServices" value="7500" class="traditional-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Video Memorial Service">
            <label for="traditionalVideography" class="ml-3 text-sm text-gray-700">Video Memorial Service (₱7,500)</label>
        </div>
        <div class="flex items-center">
            <input type="checkbox" id="traditionalTransport" name="additionalServices" value="4500" class="traditional-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Additional Transportation">
            <label for="traditionalTransport" class="ml-3 text-sm text-gray-700">Additional Transportation (₱4,500)</label>
        </div>
        <div class="flex items-center">
            <input type="checkbox" id="traditionalUrn" name="additionalServices" value="6000" class="traditional-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Premium Urn Upgrade">
            <label for="traditionalUrn" class="ml-3 text-sm text-gray-700">Premium Urn Upgrade (₱6,000)</label>
        </div>
    </div>
</div>
            </div>

            <!-- Right Side: Traditional Booking Form -->
            <div class="bg-white p-8 border-l border-gray-200 overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-hedvig text-navy">Book Your Package</h2>
                    <button class="closeModalBtn text-gray-500 hover:text-navy">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <form id="traditionalBookingForm" class="space-y-4">
                    <input type="hidden" id="traditionalSelectedPackageName" name="packageName">
                    <input type="hidden" id="traditionalSelectedPackagePrice" name="packagePrice">
                    
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-lg font-hedvig text-navy mb-4">Deceased Information</h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="traditionalDeceasedFirstName" class="block text-sm font-medium text-navy mb-2">First Name *</label>
                                <input type="text" id="traditionalDeceasedFirstName" name="deceasedFirstName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDeceasedMiddleName" class="block text-sm font-medium text-navy mb-2">Middle Name</label>
                                <input type="text" id="traditionalDeceasedMiddleName" name="deceasedMiddleName" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="traditionalDeceasedLastName" class="block text-sm font-medium text-navy mb-2">Last Name *</label>
                                <input type="text" id="traditionalDeceasedLastName" name="deceasedLastName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDeceasedSuffix" class="block text-sm font-medium text-navy mb-2">Suffix</label>
                                <input type="text" id="traditionalDeceasedSuffix" name="deceasedSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label for="traditionalDateOfBirth" class="block text-sm font-medium text-navy mb-2">Date of Birth</label>
                                <input type="date" id="traditionalDateOfBirth" name="dateOfBirth" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDateOfDeath" class="block text-sm font-medium text-navy mb-2">Date of Death *</label>
                                <input type="date" id="traditionalDateOfDeath" name="dateOfDeath" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalDateOfBurial" class="block text-sm font-medium text-navy mb-2">Date of Burial</label>
                                <input type="date" id="traditionalDateOfBurial" name="dateOfBurial" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="traditionalDeathCertificate" class="block text-sm font-medium text-navy mb-2">Death Certificate</label>
                            <input type="file" id="traditionalDeathCertificate" name="deathCertificate" accept=".pdf,.jpg,.jpeg,.png" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                        </div>
                        <div class="mt-4">
                            <label for="traditionalDeceasedAddress" class="block text-sm font-medium text-navy mb-2">Address of the Deceased</label>
                            <textarea id="traditionalDeceasedAddress" name="deceasedAddress" rows="3" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600"></textarea>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-lg font-hedvig text-navy mb-4">Payment</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="traditionalGcashReceipt" class="block text-sm font-medium text-navy mb-2">GCash Receipt *</label>
                                <input type="file" id="traditionalGcashReceipt" name="gcashReceipt" accept=".pdf,.jpg,.jpeg,.png" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="traditionalReferenceNumber" class="block text-sm font-medium text-navy mb-2">GCash Reference Number *</label>
                                <input type="text" id="traditionalReferenceNumber" name="referenceNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                    </div>

                    <div class="bg-cream p-4 rounded-lg">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-navy">Package Total</span>
                            <span id="traditionalTotalPrice" class="text-yellow-600">₱0</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
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

<!-- Custom Package Selection Modal (Hidden by Default) -->
<div id="customPackageModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[80vh]">
        <div class="modal-scroll-container overflow-y-auto max-h-[80vh]">
            <div class="bg-cream p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-hedvig text-navy">Custom Memorial Package</h2>
                    <button class="closeModalBtn text-gray-500 hover:text-navy">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                
                <p class="text-dark mb-8">Create your personalized memorial package by selecting the options that best suit your needs and preferences. Our team will assist you throughout the process.</p>
                
                <!-- Package Options Selection -->
                <div id="customStepOptions" class="custom-step">
                    <h3 class="text-xl font-hedvig text-navy mb-6">Select Package Components</h3>
                    
                    <div class="space-y-8">
                        <!-- Casket Selection -->
                        <div class="border-b border-gray-200 pb-6">
                            <h4 class="text-lg font-hedvig text-navy mb-4">Select Casket</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="border rounded-lg p-4 cursor-pointer casket-option" data-price="45000" data-name="Standard Wooden Casket">
                                    <img src="/api/placeholder/300/200" alt="Standard Wooden Casket" class="w-full h-32 object-cover rounded-lg mb-2">
                                    <h5 class="font-medium mb-1">Standard Wooden Casket</h5>
                                    <p class="text-yellow-600">₱45,000</p>
                                </div>
                                <div class="border rounded-lg p-4 cursor-pointer casket-option" data-price="75000" data-name="Premium Wooden Casket">
                                    <img src="/api/placeholder/300/200" alt="Premium Wooden Casket" class="w-full h-32 object-cover rounded-lg mb-2">
                                    <h5 class="font-medium mb-1">Premium Wooden Casket</h5>
                                    <p class="text-yellow-600">₱75,000</p>
                                </div>
                                <div class="border rounded-lg p-4 cursor-pointer casket-option" data-price="120000" data-name="Luxury Metal Casket">
                                    <img src="/api/placeholder/300/200" alt="Luxury Metal Casket" class="w-full h-32 object-cover rounded-lg mb-2">
                                    <h5 class="font-medium mb-1">Luxury Metal Casket</h5>
                                    <p class="text-yellow-600">₱120,000</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Viewing Period Selection -->
                        <div class="border-b border-gray-200 pb-6">
                            <h4 class="text-lg font-hedvig text-navy mb-4">Viewing Period</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="border rounded-lg p-4 cursor-pointer viewing-option" data-price="30000" data-name="3-Day Viewing">
                                    <h5 class="font-medium mb-1">3-Day Viewing</h5>
                                    <p class="text-sm text-gray-600 mb-2">Standard viewing period</p>
                                    <p class="text-yellow-600">₱30,000</p>
                                </div>
                                <div class="border rounded-lg p-4 cursor-pointer viewing-option" data-price="50000" data-name="5-Day Viewing">
                                    <h5 class="font-medium mb-1">5-Day Viewing</h5>
                                    <p class="text-sm text-gray-600 mb-2">Extended viewing period</p>
                                    <p class="text-yellow-600">₱50,000</p>
                                </div>
                                <div class="border rounded-lg p-4 cursor-pointer viewing-option" data-price="70000" data-name="7-Day Viewing">
                                    <h5 class="font-medium mb-1">7-Day Viewing</h5>
                                    <p class="text-sm text-gray-600 mb-2">Full week viewing period</p>
                                    <p class="text-yellow-600">₱70,000</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Flower Arrangements -->
                        <div class="border-b border-gray-200 pb-6">
                            <h4 class="text-lg font-hedvig text-navy mb-4">Flower Arrangements</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="border rounded-lg p-4 cursor-pointer flower-option" data-price="15000" data-name="Standard Floral Package">
                                    <img src="/api/placeholder/300/200" alt="Standard Floral Package" class="w-full h-32 object-cover rounded-lg mb-2">
                                    <h5 class="font-medium mb-1">Standard Floral Package</h5>
                                    <p class="text-sm text-gray-600 mb-2">1 standing spray, casket spray</p>
                                    <p class="text-yellow-600">₱15,000</p>
                                </div>
                                <div class="border rounded-lg p-4 cursor-pointer flower-option" data-price="25000" data-name="Premium Floral Package">
                                    <img src="/api/placeholder/300/200" alt="Premium Floral Package" class="w-full h-32 object-cover rounded-lg mb-2">
                                    <h5 class="font-medium mb-1">Premium Floral Package</h5>
                                    <p class="text-sm text-gray-600 mb-2">2 standing sprays, casket spray</p>
                                    <p class="text-yellow-600">₱25,000</p>
                                </div>
                                <div class="border rounded-lg p-4 cursor-pointer flower-option" data-price="40000" data-name="Luxury Floral Package">
                                    <img src="/api/placeholder/300/200" alt="Luxury Floral Package" class="w-full h-32 object-cover rounded-lg mb-2">
                                    <h5 class="font-medium mb-1">Luxury Floral Package</h5>
                                    <p class="text-sm text-gray-600 mb-2">3 standing sprays, premium casket spray</p>
                                    <p class="text-yellow-600">₱40,000</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Services Checkboxes -->
                        <div class="border-b border-gray-200 pb-6">
                            <h4 class="text-lg font-hedvig text-navy mb-4">Additional Services</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex items-start border rounded-lg p-4">
                                    <input type="checkbox" id="customCatering" name="additionalServices" value="25000" class="custom-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500 mt-1" data-name="Catering Service (100 pax)">
                                    <label for="customCatering" class="ml-3">
                                        <span class="block font-medium mb-1">Catering Service (100 pax)</span>
                                        <span class="block text-sm text-gray-600 mb-1">Food and refreshments for 100 people</span>
                                        <span class="text-yellow-600">₱25,000</span>
                                    </label>
                                </div>
                                <div class="flex items-start border rounded-lg p-4">
                                    <input type="checkbox" id="customVideo" name="additionalServices" value="15000" class="custom-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500 mt-1" data-name="Video Memorial Package">
                                    <label for="customVideo" class="ml-3">
                                        <span class="block font-medium mb-1">Video Memorial Package</span>
                                        <span class="block text-sm text-gray-600 mb-1">Professional photo/video service</span>
                                        <span class="text-yellow-600">₱15,000</span>
                                    </label>
                                </div>
                                <div class="flex items-start border rounded-lg p-4">
                                    <input type="checkbox" id="customTransport" name="additionalServices" value="8000" class="custom-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500 mt-1" data-name="Additional Transportation">
                                    <label for="customTransport" class="ml-3">
                                        <span class="block font-medium mb-1">Additional Transportation</span>
                                        <span class="block text-sm text-gray-600 mb-1">For family members (up to 10 people)</span>
                                        <span class="text-yellow-600">₱8,000</span>
                                    </label>
                                </div>
                                <div class="flex items-start border rounded-lg p-4">
                                    <input type="checkbox" id="customLiveStream" name="additionalServices" value="12000" class="custom-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500 mt-1" data-name="Live Streaming Service">
                                    <label for="customLiveStream" class="ml-3">
                                        <span class="block font-medium mb-1">Live Streaming Service</span>
                                        <span class="block text-sm text-gray-600 mb-1">For remote family and friends</span>
                                        <span class="text-yellow-600">₱12,000</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Package Summary -->
                    <div class="mt-8 bg-white p-6 rounded-lg shadow">
                        <h4 class="text-lg font-hedvig text-navy mb-4">Package Summary</h4>
                        
                        <div id="customSelectionsSummary" class="space-y-2 mb-4">
                            <p class="text-gray-500 italic">No items selected yet</p>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between font-bold mb-2">
                                <span>Total Package Price:</span>
                                <span id="customTotalPrice" class="text-yellow-600">₱0</span>
                            </div>
                            
                            <div id="customTraditionalPayment">
                                <div class="flex justify-between text-sm mb-2">
                                    <span>Required Downpayment (30%):</span>
                                    <span id="customDownpayment" class="text-yellow-600">₱0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-center mt-8">
                        <button id="proceedToBooking" class="bg-yellow-600 hover:bg-yellow-700 text-white px-8 py-3 rounded-lg shadow-md transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Continue to Booking Form
                        </button>
                    </div>
                </div>
                
                <!-- Booking Form -->
                <div id="customStepBooking" class="custom-step hidden">
                    <div class="flex items-center mb-6">
                        <button id="backToOptions" class="text-yellow-600 hover:text-yellow-700 mr-4">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </button>
                        <h3 class="text-xl font-hedvig text-navy">Complete Your Booking</h3>
                    </div>
                    
                    <p class="mb-8">Please provide your information to complete the booking process.</p>
                    
                    <!-- Will load traditional form here via JS -->
                    <div id="customBookingFormContainer">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables to store custom package selections
    let selectedCasket = null;
    let selectedViewing = null;
    let selectedFlowers = null;
    let selectedAddons = [];
    let totalPackagePrice = 0;
    
    // Get the modal elements
    const customPackageModal = document.getElementById('customPackageModal');
    const traditionalModal = document.getElementById('traditionalModal');
    
    // Function to open custom package modal
    function openCustomPackageModal() {
        // Reset selections
        resetCustomSelections();
        
        // Show the modal
        customPackageModal.classList.remove('hidden');
    }
    
    // Function to close custom package modal
    function closeCustomPackageModal() {
        customPackageModal.classList.add('hidden');
    }
    
    // Attach event listener to the custom package button
    document.querySelectorAll('button.customtraditionalpckg').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            openCustomPackageModal();
        });
    });
    
    // Close modal when clicking the close button
    document.querySelectorAll('.closeModalBtn').forEach(button => {
        button.addEventListener('click', function() {
            if (this.closest('#customPackageModal')) {
                closeCustomPackageModal();
            } else if (this.closest('#traditionalModal')) {
                traditionalModal.classList.add('hidden');
            }
        });
    });
    
    // Close modal when clicking outside the modal content
    customPackageModal.addEventListener('click', function(e) {
        if (e.target === customPackageModal) {
            closeCustomPackageModal();
        }
    });
    
    traditionalModal.addEventListener('click', function(e) {
        if (e.target === traditionalModal) {
            traditionalModal.classList.add('hidden');
        }
    });
    
    // Function to reset all custom selections
    function resetCustomSelections() {
        selectedCasket = null;
        selectedViewing = null;
        selectedFlowers = null;
        selectedAddons = [];
        totalPackagePrice = 0;
        
        // Reset UI selections
        document.querySelectorAll('.casket-option, .viewing-option, .flower-option').forEach(el => {
            el.classList.remove('border-yellow-600', 'border-2');
        });
        
        document.querySelectorAll('.custom-addon').forEach(el => {
            el.checked = false;
        });
        
        // Reset summary
        updateCustomSummary();
        
        // Disable continue button
        document.getElementById('proceedToBooking').disabled = true;
    }
    
    // Function to show a specific step in the custom package form
    function showCustomStep(stepId) {
        document.querySelectorAll('.custom-step').forEach(step => {
            step.classList.add('hidden');
        });
        document.getElementById(stepId).classList.remove('hidden');
    }
    
    // Casket selection
    document.querySelectorAll('.casket-option').forEach(option => {
        option.addEventListener('click', function() {
            // Clear previous selection
            document.querySelectorAll('.casket-option').forEach(el => {
                el.classList.remove('border-yellow-600', 'border-2');
            });
            
            // Mark this option as selected
            this.classList.add('border-yellow-600', 'border-2');
            
            // Store selected casket
            selectedCasket = {
                name: this.dataset.name,
                price: parseInt(this.dataset.price)
            };
            
            updateCustomSummary();
            checkRequiredSelections();
        });
    });
    
    // Viewing period selection
    document.querySelectorAll('.viewing-option').forEach(option => {
        option.addEventListener('click', function() {
            // Clear previous selection
            document.querySelectorAll('.viewing-option').forEach(el => {
                el.classList.remove('border-yellow-600', 'border-2');
            });
            
            // Mark this option as selected
            this.classList.add('border-yellow-600', 'border-2');
            
            // Store selected viewing
            selectedViewing = {
                name: this.dataset.name,
                price: parseInt(this.dataset.price)
            };
            
            updateCustomSummary();
            checkRequiredSelections();
        });
    });
    
    // Flower arrangements selection
    document.querySelectorAll('.flower-option').forEach(option => {
        option.addEventListener('click', function() {
            // Clear previous selection
            document.querySelectorAll('.flower-option').forEach(el => {
                el.classList.remove('border-yellow-600', 'border-2');
            });
            
            // Mark this option as selected
            this.classList.add('border-yellow-600', 'border-2');
            
            // Store selected flowers
            selectedFlowers = {
                name: this.dataset.name,
                price: parseInt(this.dataset.price)
            };
            
            updateCustomSummary();
            checkRequiredSelections();
        });
    });
    
    // Additional services checkboxes (custom package)
    document.querySelectorAll('.custom-addon').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            // Update selected addons list
            selectedAddons = [];
            document.querySelectorAll('.custom-addon:checked').forEach(checked => {
                selectedAddons.push({
                    name: checked.dataset.name,
                    price: parseInt(checked.value)
                });
            });
            
            updateCustomSummary();
        });
    });
    
    // Additional services checkboxes (traditional modal)
    document.querySelectorAll('.traditional-addon').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateTraditionalPaymentSummary();
        });
    });
    
    // Function to check if required selections are made
    function checkRequiredSelections() {
        // Enable proceed button only if all required selections are made
        const requiredSelectionsComplete = selectedCasket && selectedViewing && selectedFlowers;
        document.getElementById('proceedToBooking').disabled = !requiredSelectionsComplete;
    }
    
    // Function to update the custom package summary
    function updateCustomSummary() {
        const summaryElement = document.getElementById('customSelectionsSummary');
        let summaryHTML = '';
        totalPackagePrice = 0;
        
        // Add casket info if selected
        if (selectedCasket) {
            summaryHTML += `<div class="flex justify-between text-sm">
                <span>${selectedCasket.name}</span>
                <span>₱${selectedCasket.price.toLocaleString()}</span>
            </div>`;
            totalPackagePrice += selectedCasket.price;
        }
        
        // Add viewing period info if selected
        if (selectedViewing) {
            summaryHTML += `<div class="flex justify-between text-sm">
                <span>${selectedViewing.name}</span>
                <span>₱${selectedViewing.price.toLocaleString()}</span>
            </div>`;
            totalPackagePrice += selectedViewing.price;
        }
        
        // Add flower arrangements info if selected
        if (selectedFlowers) {
            summaryHTML += `<div class="flex justify-between text-sm">
                <span>${selectedFlowers.name}</span>
                <span>₱${selectedFlowers.price.toLocaleString()}</span>
            </div>`;
            totalPackagePrice += selectedFlowers.price;
        }
        
        // Add additional services if any
        if (selectedAddons.length > 0) {
            selectedAddons.forEach(addon => {
                summaryHTML += `<div class="flex justify-between text-sm">
                    <span>${addon.name}</span>
                    <span>₱${addon.price.toLocaleString()}</span>
                </div>`;
                totalPackagePrice += addon.price;
            });
        }
        
        // Update summary section
        if (summaryHTML) {
            summaryElement.innerHTML = summaryHTML;
        } else {
            summaryElement.innerHTML = '<p class="text-gray-500 italic">No items selected yet</p>';
        }
        
        // Update total price display
        document.getElementById('customTotalPrice').textContent = `₱${totalPackagePrice.toLocaleString()}`;
        
        // Update downpayment (30%)
        const downpayment = Math.ceil(totalPackagePrice * 0.3);
        document.getElementById('customDownpayment').textContent = `₱${downpayment.toLocaleString()}`;
    }
    
    // Proceed to booking button click event
    document.getElementById('proceedToBooking').addEventListener('click', function() {
        // Create package details
        const packageName = 'Custom Memorial Package';
        const packagePrice = totalPackagePrice;
        
        // Create feature list from selections
        const features = [];
        if (selectedCasket) features.push(selectedCasket.name);
        if (selectedViewing) features.push(selectedViewing.name);
        if (selectedFlowers) features.push(selectedFlowers.name);
        selectedAddons.forEach(addon => {
            features.push(addon.name);
        });
        
        // Close the custom package modal
        closeCustomPackageModal();
        
        // Open the traditional booking modal with our custom package data
        openTraditionalModalWithCustomPackage(packageName, packagePrice, features);
    });
    
    // Function to open traditional booking modal with custom package data
    function openTraditionalModalWithCustomPackage(packageName, packagePrice, features) {
        // Get the traditional modal elements
        const packageNameElement = document.getElementById('traditionalPackageName');
        const packagePriceElement = document.getElementById('traditionalPackagePrice');
        const packageFeaturesElement = document.getElementById('traditionalPackageFeatures');
        const hiddenPackageName = document.getElementById('traditionalSelectedPackageName');
        const hiddenPackagePrice = document.getElementById('traditionalSelectedPackagePrice');
        const totalPriceElement = document.getElementById('traditionalTotalPrice');
        const downpaymentElement = document.getElementById('traditionalDownpayment');
        const amountDueElement = document.getElementById('traditionalAmountDue');
        
        // Set the package details
        packageNameElement.textContent = packageName;
        packagePriceElement.textContent = `₱${packagePrice.toLocaleString()}`;
        hiddenPackageName.value = packageName;
        hiddenPackagePrice.value = packagePrice;
        
        // Set the features list
        packageFeaturesElement.innerHTML = '';
        features.forEach(feature => {
            const li = document.createElement('li');
            li.className = 'flex items-start';
            li.innerHTML = `
                <i class="fas fa-check-circle mr-2 mt-1 text-yellow-600"></i>
                <span>${feature}</span>
            `;
            packageFeaturesElement.appendChild(li);
        });
        
        // Calculate and display payment information
        const downpayment = Math.ceil(packagePrice * 0.3);
        totalPriceElement.textContent = `₱${packagePrice.toLocaleString()}`;
        downpaymentElement.textContent = `₱${downpayment.toLocaleString()}`;
        amountDueElement.textContent = `₱${downpayment.toLocaleString()}`;
        
        // Reset additional services checkboxes
        document.querySelectorAll('.traditional-addon').forEach(checkbox => {
            checkbox.checked = false;
        });
        
        // Show the modal
        traditionalModal.classList.remove('hidden');
    }
    
    // Function to update payment summary in traditional modal
    function updateTraditionalPaymentSummary() {
        const basePrice = parseInt(document.getElementById('traditionalSelectedPackagePrice').value) || 0;
        let additionalCost = 0;
        
        document.querySelectorAll('.traditional-addon:checked').forEach(checkbox => {
            additionalCost += parseInt(checkbox.value);
        });
        
        const totalPrice = basePrice + additionalCost;
        const downpayment = Math.ceil(totalPrice * 0.3);
        
        document.getElementById('traditionalTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('traditionalDownpayment').textContent = `₱${downpayment.toLocaleString()}`;
        document.getElementById('traditionalAmountDue').textContent = `₱${downpayment.toLocaleString()}`;
    }
    
    // Back to options button
    document.getElementById('backToOptions').addEventListener('click', function() {
        showCustomStep('customStepOptions');
    });
});
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
                    <a href="privacy_policy.php" class="text-gray-400 hover:text-white transition mx-2">Privacy Policy</a>
                    <a href="#" class="text-gray-400 hover:text-white transition mx-2">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

<!-- Add this JavaScript code -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Show traditional modal directly when package is selected
    document.querySelectorAll('.selectPackageBtn').forEach(button => {
        button.addEventListener('click', function() {
            // Get package details from the parent card
            const packageCard = this.closest('.package-card');
            if (!packageCard) return; // Safety check
            
            const packageName = packageCard.dataset.name;
            const packagePrice = packageCard.dataset.price;
            const packageImage = packageCard.dataset.image || '';
            
            // Store package details in sessionStorage for later use
            sessionStorage.setItem('selectedPackageName', packageName);
            sessionStorage.setItem('selectedPackagePrice', packagePrice);
            sessionStorage.setItem('selectedPackageImage', packageImage);
            
            // Get other details from the card content
            const features = Array.from(packageCard.querySelectorAll('ul li')).map(li => li.innerHTML);
            sessionStorage.setItem('selectedPackageFeatures', JSON.stringify(features));
            
            // Open traditional modal directly
            openTraditionalModal();
        });
    });

    // Traditional addon checkbox event handling
    document.querySelectorAll('.traditional-addon').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateTraditionalTotal();
        });
    });

    // Function to update traditional total price when addons are selected
    function updateTraditionalTotal() {
        const basePrice = parseInt(sessionStorage.getItem('selectedPackagePrice') || '0');
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
        
        // Update hidden fields
        document.getElementById('traditionalSelectedPackagePrice').value = totalPrice;
    }

    // Function to open traditional modal with package details
    function openTraditionalModal() {
        // Get stored package details
        const packageName = sessionStorage.getItem('selectedPackageName');
        const packagePrice = sessionStorage.getItem('selectedPackagePrice');
        const packageImage = sessionStorage.getItem('selectedPackageImage');
        const packageFeatures = JSON.parse(sessionStorage.getItem('selectedPackageFeatures') || '[]');
        
        // Update modal title
        document.querySelector('#traditionalModal .font-hedvig.text-2xl.text-navy').textContent = 'Book Your Package';
        
        // Update traditional modal with package details
        document.getElementById('traditionalPackageName').textContent = packageName;
        document.getElementById('traditionalPackagePrice').textContent = `₱${parseInt(packagePrice).toLocaleString()}`;
        
        // Only set image src if packageImage exists
        if (packageImage) {
            document.getElementById('traditionalPackageImage').src = packageImage;
            document.getElementById('traditionalPackageImage').alt = packageName;
        }
        
        // Calculate downpayment (30%)
        const totalPrice = parseInt(packagePrice);
        const downpayment = Math.ceil(totalPrice * 0.3);
        
        document.getElementById('traditionalTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('traditionalDownpayment').textContent = `₱${downpayment.toLocaleString()}`;
        document.getElementById('traditionalAmountDue').textContent = `₱${downpayment.toLocaleString()}`;

        // Update features list
        const featuresList = document.getElementById('traditionalPackageFeatures');
        featuresList.innerHTML = '';
        packageFeatures.forEach(feature => {
            featuresList.innerHTML += `<li class="flex items-center text-sm text-gray-700">${feature}</li>`;
        });
        
        // Update the form's hidden fields with package info
        document.getElementById('traditionalSelectedPackageName').value = packageName;
        document.getElementById('traditionalSelectedPackagePrice').value = packagePrice;
        
        // Show traditional modal
        document.getElementById('traditionalModal').classList.remove('hidden');
    }
    
    document.querySelectorAll('.traditional-addon').forEach(checkbox => {
        checkbox.checked = false;
    });

    // Close modals when close button is clicked
    document.querySelectorAll('.closeModalBtn').forEach(button => {
        button.addEventListener('click', function() {
            closeAllModals();
        });
    });

    // Function to close all modals
    function closeAllModals() {
        document.getElementById('traditionalModal').classList.add('hidden');
    }

    // Close modals when pressing Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeAllModals();
        }
    });

    // Close modals when clicking outside of modal content
    document.querySelectorAll('#traditionalModal').forEach(modal => {
        modal.addEventListener('click', function(event) {
            // Check if the click was directly on the modal background (not its children)
            if (event.target === modal) {
                closeAllModals();
            }
        });
    });

    // Form submission for Traditional
    document.getElementById('traditionalBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        // Add booking submission logic here
        alert('Service booking submitted successfully!');
        closeAllModals();
    });
});

function toggleMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
}
</script>


<?php include 'customService/chat_elements.html'; ?>
</body>
</html>
