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
                $conn->close();
?>

<script src="customer_support.js"></script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - LifePlan</title>
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
        .modal {
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
        }
        .modal.active {
            opacity: 1;
            pointer-events: auto;
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

<!-- Cremation Services Main Content -->
<div class="bg-cream py-20">
    <div class="container mx-auto px-6 max-w-6xl">
        <!-- Hero Section -->
        <div class="relative rounded-2xl overflow-hidden mb-16 shadow-xl">
            <div class="h-64 bg-cover bg-center" style="background-image: url('../Landing_Page/Landing_images/black-bg-image.jpg')">
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="absolute inset-0 flex flex-col justify-center items-center text-white p-8">
                    <h1 class="text-5xl font-hedvig text-center mb-6">Life Plan</h1>
                    <p class="text-lg max-w-3xl text-center">Taking the time to plan now means peace of mind for both you and your loved ones later.</p>
                </div>
            </div>
        </div>

        <!-- Benefits Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            <!-- Benefit 1 -->
            <div class="bg-white rounded-lg shadow-lg p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="bg-yellow-600/10 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <i class="fas fa-heart text-yellow-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy text-center mb-4">Emotional Peace</h3>
                <p class="text-dark text-sm text-center">Relieve your loved ones of the burden of making difficult decisions during their time of grief.</p>
            </div>
            
            <!-- Benefit 2 -->
            <div class="bg-white rounded-lg shadow-lg p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="bg-yellow-600/10 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <i class="fas fa-coins text-yellow-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy text-center mb-4">Financial Security</h3>
                <p class="text-dark text-sm text-center">Lock in today's prices and protect your family from future inflation and unexpected costs.</p>
            </div>
            
            <!-- Benefit 3 -->
            <div class="bg-white rounded-lg shadow-lg p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="bg-yellow-600/10 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <i class="fas fa-clipboard-check text-yellow-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy text-center mb-4">Personal Wishes</h3>
                <p class="text-dark text-sm text-center">Ensure your life is celebrated exactly how you envision, with every detail respected and honored.</p>
            </div>
        </div>
        
        <section id="lifeplan" class="mb-8 mt-16">
    <div class="flex justify-center mb-8">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-full bg-yellow-600/90 flex items-center justify-center text-white mr-4">
                <i class="fas fa-clipboard-list text-2xl"></i>
            </div>
            <h2 class="font-hedvig text-4xl text-navy">Life Plan</h2>
        </div>
    </div>

    <!-- Paragraph - Centered -->  
    <div class="flex justify-center mb-8">  
        <p class="text-dark max-w-3xl text-center">We provide flexible and compassionate life plan options, including a 5-year installment payment plan with 0% interest for your chosen traditional package, ensuring affordability without compromising dignity or care.</p>  
    </div>  
    
    <div class="max-w-6xl mx-auto">
        <div class="package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col max-h-[480px]" data-price="35000" data-service="cremate" data-name="Direct Cremation">
            <div class="h-12 bg-navy flex items-center justify-center">
                <h4 class="text-white font-hedvig text-xl">Flexible Payment Plan</h4>
                <div class="absolute top-0 right-0 w-16 h-16 flex items-center justify-center">
                    <div class="w-16 h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                </div>
            </div>
            <div class="p-6 flex flex-col">
                <div class="text-center mb-4">
                    <span class="text-3xl font-hedvig text-navy">5-Year Payment Option</span>
                    <p class="text-dark mt-2">Total Package Price ÷ 60 Months</p>
                </div>
                <ul class="space-y-2 mb-4">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">0% Interest Guaranteed</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">Fixed Monthly Payment</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">No Hidden Fees</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">Spread Cost Over 5 Years</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">Equal Monthly Installments</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">Flexible Payment Option</span>
                    </li>
                </ul>
                <button class="block w-full mt-4 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 text-center">
                    Select Package
                </button>
            </div>
        </div>
    </div>
</section>
        
        <!-- How It Works Section - Improved Timeline Design -->
        <div class="mb-8 mt-16">
            <div class="text-center mb-12">
                <h3 class="text-5xl font-hedvig text-navy mb-4">How Life Planning Works</h3>
                <p class="text-dark max-w-3xl mx-auto">Our simple process guides you through each step of creating your personalized life plan.</p>
            </div>
            
            <!-- Timeline Container -->
            <div class="relative mx-auto px-4">
                <!-- Connecting Line - Vertical on mobile, Horizontal on larger screens -->
                <div class="hidden md:block absolute top-1/2 left-0 right-0 h-1 bg-yellow-600 -translate-y-1/2 z-0"></div>
                <div class="md:hidden absolute left-1/2 top-0 bottom-0 w-1 bg-yellow-600 -translate-x-1/2 z-0"></div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12 md:gap-8 relative z-10">
                    <!-- Step 1 -->
                    <div class="flex flex-col md:items-center text-center relative">
                        <!-- Connector for mobile view -->
                        <div class="md:hidden absolute top-0 bottom-0 left-0 w-16 flex items-center justify-center">
                            <div class="h-full w-px bg-yellow-600 absolute left-8"></div>
                        </div>
                        
                        <!-- Step Number Circle -->
                        <div class="w-16 h-16 bg-white border-2 border-yellow-600 rounded-full flex items-center justify-center mb-6 shadow-lg z-10 md:mx-auto">
                            <span class="text-2xl font-hedvig text-navy">1</span>
                        </div>
                        
                        <!-- Content Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1 flex-1">
                            <h4 class="text-xl font-hedvig text-navy mb-3">Consultation</h4>
                            <p class="text-dark text-sm">Meet with our planning specialists to discuss your wishes and preferences. We'll guide you through available options.</p>
                            <div class="mt-4 text-yellow-600">
                                <i class="fas fa-handshake text-3xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2 -->
                    <div class="flex flex-col md:items-center text-center relative">
                        <!-- Connector for mobile view -->
                        <div class="md:hidden absolute top-0 bottom-0 left-0 w-16 flex items-center justify-center">
                            <div class="h-full w-px bg-yellow-600 absolute left-8"></div>
                        </div>
                        
                        <!-- Step Number Circle -->
                        <div class="w-16 h-16 bg-white border-2 border-yellow-600 rounded-full flex items-center justify-center mb-6 shadow-lg z-10 md:mx-auto">
                            <span class="text-2xl font-hedvig text-navy">2</span>
                        </div>
                        
                        <!-- Content Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1 flex-1">
                            <h4 class="text-xl font-hedvig text-navy mb-3">Personalization</h4>
                            <p class="text-dark text-sm">Customize your plan with specific selections and personal touches that reflect your wishes and values.</p>
                            <div class="mt-4 text-yellow-600">
                                <i class="fas fa-paint-brush text-3xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 3 -->
                    <div class="flex flex-col md:items-center text-center relative">
                        <!-- Connector for mobile view -->
                        <div class="md:hidden absolute top-0 bottom-0 left-0 w-16 flex items-center justify-center">
                            <div class="h-full w-px bg-yellow-600 absolute left-8"></div>
                        </div>
                        
                        <!-- Step Number Circle -->
                        <div class="w-16 h-16 bg-white border-2 border-yellow-600 rounded-full flex items-center justify-center mb-6 shadow-lg z-10 md:mx-auto">
                            <span class="text-2xl font-hedvig text-navy">3</span>
                        </div>
                        
                        <!-- Content Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1 flex-1">
                            <h4 class="text-xl font-hedvig text-navy mb-3">Documentation</h4>
                            <p class="text-dark text-sm">Receive comprehensive documentation of your arrangements and preferences for your records.</p>
                            <div class="mt-4 text-yellow-600">
                                <i class="fas fa-file-signature text-3xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 4 -->
                    <div class="flex flex-col md:items-center text-center relative">
                        <!-- Connector for mobile view -->
                        <div class="md:hidden absolute top-0 bottom-0 left-0 w-16 flex items-center justify-center">
                            <div class="h-full w-px bg-yellow-600 absolute left-8"></div>
                        </div>
                        
                        <!-- Step Number Circle -->
                        <div class="w-16 h-16 bg-white border-2 border-yellow-600 rounded-full flex items-center justify-center mb-6 shadow-lg z-10 md:mx-auto">
                            <span class="text-2xl font-hedvig text-navy">4</span>
                        </div>
                        
                        <!-- Content Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1 flex-1">
                            <h4 class="text-xl font-hedvig text-navy mb-3">Peace of Mind</h4>
                            <p class="text-dark text-sm">Rest assured knowing your wishes will be honored and your loved ones protected from difficult decisions.</p>
                            <div class="mt-4 text-yellow-600">
                                <i class="fas fa-heart text-3xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
<div class="container mx-auto px-6 py-12 max-w-3xl">
        <!-- Section Header -->
        <div class="text-center mb-16">
            <h2 class="text-5xl font-hedvig text-navy mb-4">We're Here to Help</h2>
            <p class="text-dark text-lg max-w-2xl mx-auto">Find answers to the most common questions about VJay Relova Funeral Services and our bereavement support.</p>
            <div class="w-20 h-1 bg-yellow-600 mx-auto mt-6"></div>
        </div>

<!-- FAQ Accordion -->
<div class="space-y-6">
            <!-- FAQ Item 1 -->
            <!-- FAQ Item on Pre-need Planning -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <button class="faq-question w-full text-left p-6 focus:outline-none">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-hedvig text-navy">Do you offer pre-need funeral plans?</h3>
            <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
        </div>
    </button>
    <div class="faq-answer px-6 pb-6 hidden">
        <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
            <p>Yes, we offer pre-need funeral plans for those who wish to arrange their funeral services in advance. Our pre-need plans offer several advantages:</p>
            <ul class="list-disc ml-6 mt-2 space-y-1">
                <li>Complete packages with all necessary services included</li>
                <li>Flexible, negotiable pricing unlike the fixed prices of other pre-need providers</li>
                <li>Customizable options to meet your specific preferences</li>
                <li>No hidden charges for special requests</li>
            </ul>
            <p class="mt-4">Many clients choose our pre-need plans over other providers because we provide more comprehensive packages without charging extra for additional requests. Our personalized approach ensures that all your wishes are honored exactly as you specify.</p>
            <p class="mt-2">To discuss pre-need planning options, we recommend scheduling an in-person consultation with our owner, Virgillo Jay G. Relova, who personally meets with each client to understand their specific needs and build a connection that ensures the highest quality of service.</p>
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

    <!-- JavaScript for FAQ Accordion -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const faqQuestions = document.querySelectorAll('.faq-question');
            
            faqQuestions.forEach(question => {
                question.addEventListener('click', function() {
                    const answer = this.nextElementSibling;
                    const icon = this.querySelector('.fa-chevron-down');
                    
                    // Toggle current answer
                    answer.classList.toggle('hidden');
                    icon.classList.toggle('rotate-180');
                });
            });
        })
    </script>    

                <!-- Testimonials Section -->
                <div class="bg-navy/5 py-12 px-6 rounded-xl">
                    <h3 class="text-3xl font-hedvig text-navy text-center mb-12">What Our Clients Say</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Testimonial 1 -->
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <div class="flex items-center mb-4">
                                <div class="text-yellow-600 mr-3">
                                    <i class="fas fa-quote-left text-3xl"></i>
                                </div>
                                <div>
                                    <div class="text-yellow-600 mb-1">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="text-dark italic mb-4">Planning ahead with GrievEase was one of the best decisions I've made. The team was compassionate and patient, guiding me through every option. I now have peace of mind knowing my family won't face difficult decisions later.</p>
                            <p class="font-hedvig text-navy">- Maria Reyes, 67</p>
                        </div>
                        
                        <!-- Testimonial 2 -->
                        <div class="bg-white p-6 rounded-lg shadow-md">
                            <div class="flex items-center mb-4">
                                <div class="text-yellow-600 mr-3">
                                    <i class="fas fa-quote-left text-3xl"></i>
                                </div>
                                <div>
                                    <div class="text-yellow-600 mb-1">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                    </div>
                                </div>
                            </div>
                            <p class="text-dark italic mb-4">After experiencing the stress of arranging a funeral for my father, I decided to plan ahead for myself. The Premium Plan offered exactly what I wanted, and the financial arrangements were clear and secure.</p>
                            <p class="font-hedvig text-navy">- Antonio Lim, 58</p>
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

    <!-- Traditional Packages Selection Modal (Hidden by Default) -->
<div id="traditionalPackagesModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-6xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[90vh]">
        <div class="modal-scroll-container overflow-y-auto max-h-[90vh]">
            <!-- Header with close button -->
            <div class="bg-navy p-6 flex justify-between items-center">
                <h2 class="text-2xl font-hedvig text-white">Select Traditional Package</h2>
                <button class="closePackagesModalBtn text-white hover:text-yellow-300">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            
            <!-- Packages grid -->
            <div class="p-6 bg-cream">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="traditionalPackagesGrid">
                    <!-- Packages will be dynamically inserted here -->
                </div>
            </div>
        </div>
    </div>
</div>

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

                <form id="lifeplanBookingForm" class="space-y-4">
                    <input type="hidden" id="lifeplanSelectedPackageName" name="packageName">
                    <input type="hidden" id="lifeplanSelectedPackagePrice" name="packagePrice">
                    
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Plan Holder Information</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 mb-3 md:mb-4">
                            <div>
                                <label for="lifeplanHolderFirstName" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">First Name *</label>
                                <input type="text" id="lifeplanHolderFirstName" name="holderFirstName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanHolderMiddleName" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Middle Name</label>
                                <input type="text" id="lifeplanHolderMiddleName" name="holderMiddleName" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 mb-3 md:mb-4">
                            <div>
                                <label for="lifeplanHolderLastName" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Last Name *</label>
                                <input type="text" id="lifeplanHolderLastName" name="holderLastName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanHolderSuffix" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Suffix</label>
                                <input type="text" id="lifeplanHolderSuffix" name="holderSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 mb-3 md:mb-4">
                            <div>
                                <label for="lifeplanDateOfBirth" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Date of Birth *</label>
                                <input type="date" id="lifeplanDateOfBirth" name="dateOfBirth" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanContactNumber" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Contact Number *</label>
                                <input type="tel" id="lifeplanContactNumber" name="contactNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="mt-3 md:mt-4">
                            <label for="lifeplanEmailAddress" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Email Address *</label>
                            <input type="email" id="lifeplanEmailAddress" name="emailAddress" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                        </div>
                        <div class="mt-3 md:mt-4">
                            <label for="lifeplanHolderAddress" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Current Address *</label>
                            <textarea id="lifeplanHolderAddress" name="holderAddress" rows="2" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600"></textarea>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-base md:text-lg font-hedvig text-navy mb-3 md:mb-4">Payment Plan</h3>
                        <div class="mb-3 md:mb-4">
                            <label class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">Payment Term:</label>
                            <select id="lifeplanPaymentTerm" name="paymentTerm" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                <option value="60">5 Years (60 Monthly Payments)</option>
                                <option value="36">3 Years (36 Monthly Payments)</option>
                                <option value="24">2 Years (24 Monthly Payments)</option>
                                <option value="12">1 Year (12 Monthly Payments)</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4">
                            <div>
                                <label for="lifeplanGcashReceipt" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">First Payment Receipt *</label>
                                <input type="file" id="lifeplanGcashReceipt" name="gcashReceipt" accept=".pdf,.jpg,.jpeg,.png" required class="w-full text-xs md:text-sm px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanReferenceNumber" class="block text-xs md:text-sm font-medium text-navy mb-1 md:mb-2">GCash Reference Number *</label>
                                <input type="text" id="lifeplanReferenceNumber" name="referenceNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                    </div>

                    <div class="bg-cream p-3 md:p-4 rounded-lg">
                        <div class="flex justify-between text-xs md:text-sm mb-2">
                            <span class="text-navy">Package Total</span>
                            <span id="lifeplanTotalPrice" class="text-yellow-600">₱0</span>
                        </div>
                        <div class="flex justify-between text-xs md:text-sm mb-2">
                            <span class="text-navy">Payment Term</span>
                            <span id="lifeplanPaymentTermDisplay" class="text-yellow-600">5 Years (60 Monthly Payments)</span>
                        </div>
                        <div class="flex justify-between font-bold mt-2 pt-2 border-t border-gray-300">
                            <span class="text-navy">Monthly Payment</span>
                            <span id="lifeplanMonthlyPayment" class="text-yellow-600">₱0</span>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300">
                        Confirm Lifeplan Booking
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Package data array
const packages = [
    {
        price: 700000,
        service: "traditional", 
        name: "Legacy Tribute",
        description: "Our premium package with 3 sets of flower changes and catering on the last day.",
        image: "../image/700.jpg",
        icon: "star",
        features: [
            "3 sets of flower changes",
            "Catering on the last day", 
            "Premium casket selection",
            "Complete funeral arrangements"
        ]
    },
    {
        price: 300000,
        service: "traditional", 
        name: "Eternal Remembrance",
        description: "A dignified package with 2 sets of flower changes and comprehensive service.",
        image: "../image/300.jpg",
        icon: "crown",
        features: [
            "2 sets of flower changes",
            "Quality casket selection", 
            "Complete funeral service",
            "Professional embalming"
        ]
    },
    {
        price: 250000,
        service: "traditional", 
        name: "Heritage Memorial",
        description: "A traditional package with 2 sets of flower changes and full service.",
        image: "../image/250.jpg",
        icon: "heart",
        features: [
            "2 sets of flower changes",
            "Standard casket",
            "Complete funeral arrangements", 
            "Professional embalming"
        ]
    },
    {
        price: 200000,
        service: "traditional", 
        name: "Serene Passage",
        description: "A peaceful package with 2 sets of flower changes and basic service.",
        image: "../image/200.jpg",
        icon: "dove",
        features: [
            "2 sets of flower changes",
            "Basic casket",
            "Essential funeral service",
            "Professional embalming"
        ]
    },
    {
        price: 180000,
        service: "traditional", 
        name: "Dignified Farewell",
        description: "A respectful package with 2 sets of flower changes and essential service.",
        image: "../image/180.jpg",
        icon: "cross",
        features: [
            "2 sets of flower changes",
            "Basic casket",
            "Essential funeral service",
            "Professional embalming"
        ]
    },
    {
        price: 150000,
        service: "traditional", 
        name: "Peaceful Journey",
        description: "A comforting package with 2 sets of flower changes and basic service.",
        image: "../image/150.jpg",
        icon: "peace",
        features: [
            "2 sets of flower changes",
            "Basic casket",
            "Essential funeral service",
            "Professional embalming"
        ]
    },
    {
        price: 120000,
        service: "traditional", 
        name: "Cherished Memories",
        description: "A meaningful package with 1 set of flowers and essential service.",
        image: "../image/120.jpg",
        icon: "memory",
        features: [
            "1 set of flowers",
            "Basic casket",
            "Essential funeral service",
            "Professional embalming"
        ]
    },
    {
        price: 90000,
        service: "traditional", 
        name: "Gentle Passage",
        description: "A simple package with 1 set of flowers and basic service.",
        image: "../image/90.jpg",
        icon: "cloud",
        features: [
            "1 set of flowers",
            "Basic casket",
            "Essential funeral service",
            "Professional embalming"
        ]
    },
    {
        price: 80000,
        service: "traditional", 
        name: "Sincere Tribute",
        description: "A heartfelt package with 2 sets of flowers and basic service.",
        image: "../image/80.jpg",
        icon: "ribbon",
        features: [
            "2 sets of flowers",
            "Basic casket",
            "Essential funeral service",
            "Professional embalming"
        ]
    },
    {
        price: 75000,
        service: "traditional", 
        name: "Heartfelt Farewell",
        description: "A compassionate package with 1 set of flowers and essential service.",
        image: "../image/75.jpg",
        icon: "heart-broken",
        features: [
            "1 set of flowers",
            "Basic casket",
            "Essential funeral service",
            "Professional embalming"
        ]
    },
    {
        price: 60000,
        service: "traditional", 
        name: "Simple Dignity",
        description: "A straightforward package with 2 sets of flowers and basic service.",
        image: "../image/60.jpg",
        icon: "leaf",
        features: [
            "2 sets of flowers",
            "Basic casket",
            "Essential funeral service",
            "Professional embalming"
        ]
    },
    {
        price: 50000,
        service: "traditional", 
        name: "Essential Remembrance",
        description: "A basic package with 1 set of flowers and essential service.",
        image: "../image/50.jpg",
        icon: "leaf",
        features: [
            "1 set of flowers",
            "Basic casket",
            "Essential funeral service",
            "Professional embalming"
        ]
    },
    {
        price: 35000,
        service: "traditional", 
        name: "Modest Memorial",
        description: "Our most affordable package with 1 set of flowers and basic service.",
        image: "../image/35.jpg",
        icon: "leaf",
        features: [
            "1 set of flowers",
            "Basic casket",
            "Essential funeral service",
            "Professional embalming"
        ]
    }
];

// Format price to Philippine Peso
function formatPrice(price) {
    return '₱' + price.toLocaleString();
}

// Calculate monthly payment based on selected term
function calculateMonthlyPayment(price, term) {
    return price / term;
}

// Format monthly payment
function formatMonthlyPayment(price, term = 60) {
    const monthly = calculateMonthlyPayment(price, term);
    return '₱' + monthly.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Function to populate the packages grid
function populatePackagesGrid() {
    const grid = document.getElementById('traditionalPackagesGrid');
    if (!grid) return; // Safety check
    
    grid.innerHTML = ''; // Clear existing content
    
    packages.forEach(pkg => {
        const card = document.createElement('div');
        card.className = 'package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col';
        card.dataset.price = pkg.price;
        card.dataset.name = pkg.name;
        card.dataset.service = pkg.service;
        card.dataset.image = pkg.image;
        card.dataset.description = pkg.description;
        
        // Create package card HTML
        card.innerHTML = `
            <div class="h-12 bg-navy flex items-center justify-center">
                <h4 class="text-white font-hedvig text-xl">${pkg.name}</h4>
                <div class="absolute top-0 right-0 w-16 h-16 flex items-center justify-center">
                    <div class="w-16 h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                </div>
            </div>
            <div class="p-6 flex flex-col flex-grow">
                <div class="mb-4">
                    <img src="${pkg.image}" alt="${pkg.name}" class="w-full h-40 object-cover rounded-lg">
                </div>
                <div class="text-center mb-4">
                    <span class="text-2xl font-hedvig text-navy">${formatPrice(pkg.price)}</span>
                    <p class="text-dark mt-1 text-sm">Monthly: ${formatMonthlyPayment(pkg.price)}</p>
                </div>
                <p class="text-dark mb-4 text-sm">${pkg.description}</p>
                <div class="flex-grow">
                    <h5 class="font-medium text-navy mb-2">Includes:</h5>
                    <ul class="space-y-1 mb-4">
                        ${pkg.features.map(feature => `
                            <li class="flex items-start">
                                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 text-sm"></i>
                                <span class="text-dark text-sm">${feature}</span>
                            </li>
                        `).join('')}
                    </ul>
                </div>
                <button class="select-package-btn block w-full mt-4 bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded-lg shadow-md transition-all duration-300 text-center text-sm"
                        data-package-id="${packages.indexOf(pkg)}">
                    Select Package
                </button>
            </div>
        `;
        
        grid.appendChild(card);
    });
    
    // Add event listeners to the new buttons
    document.querySelectorAll('.select-package-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const packageId = parseInt(this.dataset.packageId);
            const selectedPackage = packages[packageId];
            
            // Close the packages selection modal
            const packagesModal = document.getElementById('traditionalPackagesModal');
            if (packagesModal) {
                packagesModal.classList.add('hidden');
            }
            
            // Open the traditional package details modal with the selected package
            openTraditionalModal(selectedPackage);
        });
    });
}

// Function to open the packages selection modal
function openPackagesModal() {
    const modal = document.getElementById('traditionalPackagesModal');
    if (modal) {
        modal.classList.remove('hidden');
        populatePackagesGrid();
    }
}

// Function to open the traditional package details modal
function openTraditionalModal(packageDetails) {
    const modal = document.getElementById('traditionalModal');
    if (!modal) return;
    
    // Update the details modal with the selected package information
    const packageImage = document.getElementById('traditionalPackageImage');
    if (packageImage) packageImage.src = packageDetails.image;
    
    const packageName = document.getElementById('traditionalPackageName');
    if (packageName) packageName.textContent = packageDetails.name;
    
    const packagePrice = document.getElementById('traditionalPackagePrice');
    if (packagePrice) packagePrice.textContent = formatPrice(packageDetails.price);
    
    const packageDesc = document.getElementById('traditionalPackageDesc');
    if (packageDesc) packageDesc.textContent = packageDetails.description;
    
    // Set hidden form fields
    const hiddenNameField = document.getElementById('lifeplanSelectedPackageName');
    if (hiddenNameField) hiddenNameField.value = packageDetails.name;
    
    const hiddenPriceField = document.getElementById('lifeplanSelectedPackagePrice');
    if (hiddenPriceField) hiddenPriceField.value = packageDetails.price;
    
    // Populate features list
    const featuresList = document.getElementById('traditionalPackageFeatures');
    if (featuresList) {
        featuresList.innerHTML = '';
        packageDetails.features.forEach(feature => {
            const li = document.createElement('li');
            li.className = 'flex items-start';
            li.innerHTML = `
                <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                <span class="text-dark">${feature}</span>
            `;
            featuresList.appendChild(li);
        });
    }
    
    // Update price information in modal
    updateLifeplanPriceCalculations(packageDetails.price);
    
    // Reset all addon checkboxes
    document.querySelectorAll('.traditional-addon').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Calculate and display prices for mobile
    const totalPrice = packageDetails.price;
    const downpayment = Math.ceil(totalPrice * 0.3);
    
    const totalPriceMobile = document.getElementById('traditionalTotalPriceMobile');
    if (totalPriceMobile) totalPriceMobile.textContent = formatPrice(totalPrice);
    
    const amountDueMobile = document.getElementById('traditionalAmountDueMobile');
    if (amountDueMobile) amountDueMobile.textContent = formatPrice(downpayment);
    
    // Show the traditional modal
    modal.classList.remove('hidden');
}

// Function to update price calculations in the Lifeplan form
function updateLifeplanPriceCalculations(packagePrice, additionalCost = 0) {
    const totalPriceElement = document.getElementById('lifeplanTotalPrice');
    const monthlyPaymentElement = document.getElementById('lifeplanMonthlyPayment');
    const paymentTermSelect = document.getElementById('lifeplanPaymentTerm');
    const paymentTermDisplay = document.getElementById('lifeplanPaymentTermDisplay');
    
    if (!totalPriceElement || !monthlyPaymentElement || !paymentTermSelect) return;
    
    const totalPrice = packagePrice + additionalCost;
    const paymentTerm = parseInt(paymentTermSelect.value);
    const monthlyPayment = calculateMonthlyPayment(totalPrice, paymentTerm);
    
    totalPriceElement.textContent = formatPrice(totalPrice);
    monthlyPaymentElement.textContent = formatPrice(monthlyPayment);
    
    // Update payment term display
    if (paymentTermDisplay) {
        let termText = "";
        switch(paymentTerm) {
            case 60: termText = "5 Years (60 Monthly Payments)"; break;
            case 36: termText = "3 Years (36 Monthly Payments)"; break;
            case 24: termText = "2 Years (24 Monthly Payments)"; break;
            case 12: termText = "1 Year (12 Monthly Payments)"; break;
            default: termText = paymentTerm + " Monthly Payments";
        }
        paymentTermDisplay.textContent = termText;
    }
}

// Function to update totals when addons are selected
function updateTotalWithAddons(packagePrice) {
    let addonTotal = 0;
    
    document.querySelectorAll('.traditional-addon:checked').forEach(checkbox => {
        addonTotal += parseInt(checkbox.value);
    });
    
    const newTotal = packagePrice + addonTotal;
    
    // Update lifeplan form prices
    updateLifeplanPriceCalculations(packagePrice, addonTotal);
    
    // Update mobile price displays too
    const totalPriceMobile = document.getElementById('traditionalTotalPriceMobile');
    if (totalPriceMobile) totalPriceMobile.textContent = formatPrice(newTotal);
    
    const amountDueMobile = document.getElementById('traditionalAmountDueMobile');
    if (amountDueMobile) {
        const downpayment = Math.ceil(newTotal * 0.3);
        amountDueMobile.textContent = formatPrice(downpayment);
    }
}

// Initialize event listeners when the document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Add initial mobile class setup
    if (window.innerWidth < 768) {
        const formSection = document.querySelector('.form-section');
        if (formSection) {
            formSection.classList.add('hidden');
        }
    }
    
    // Event listener for the Life Plan "Select Package" button
    const lifePlanButton = document.querySelector('#lifeplan button');
    if (lifePlanButton) {
        lifePlanButton.addEventListener('click', openPackagesModal);
    }
    
    // Event listeners for closing modals
    document.querySelectorAll('.closePackagesModalBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            const packagesModal = document.getElementById('traditionalPackagesModal');
            if (packagesModal) packagesModal.classList.add('hidden');
        });
    });
    
    document.querySelectorAll('.closeModalBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            const traditionalModal = document.getElementById('traditionalModal');
            if (traditionalModal) traditionalModal.classList.add('hidden');
        });
    });
    
    // Setup mobile navigation buttons
    const continueToFormBtn = document.getElementById('continueToFormBtn');
    if (continueToFormBtn) {
        continueToFormBtn.addEventListener('click', function() {
            const detailsSection = document.querySelector('.details-section');
            const formSection = document.querySelector('.form-section');
            
            if (detailsSection) detailsSection.classList.add('hidden');
            if (formSection) formSection.classList.remove('hidden');
        });
    }
    
    const backToDetailsBtn = document.getElementById('backToDetailsBtn');
    if (backToDetailsBtn) {
        backToDetailsBtn.addEventListener('click', function() {
            const formSection = document.querySelector('.form-section');
            const detailsSection = document.querySelector('.details-section');
            
            if (formSection) formSection.classList.add('hidden');
            if (detailsSection) detailsSection.classList.remove('hidden');
        });
    }
    
    // Add event listeners to addon checkboxes
    document.querySelectorAll('.traditional-addon').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const basePrice = parseInt(document.getElementById('lifeplanSelectedPackagePrice')?.value || 0);
            updateTotalWithAddons(basePrice);
        });
    });
    
    // Add event listener to payment term select
    const paymentTermSelect = document.getElementById('lifeplanPaymentTerm');
    if (paymentTermSelect) {
        paymentTermSelect.addEventListener('change', function() {
            const basePrice = parseInt(document.getElementById('lifeplanSelectedPackagePrice')?.value || 0);
            let addonTotal = 0;
            
            document.querySelectorAll('.traditional-addon:checked').forEach(checkbox => {
                addonTotal += parseInt(checkbox.value);
            });
            
            updateLifeplanPriceCalculations(basePrice, addonTotal);
        });
    }
    
    // Form submission handler for lifeplan booking form
    const lifeplanForm = document.getElementById('lifeplanBookingForm');
    if (lifeplanForm) {
        lifeplanForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate form fields
            const requiredFields = lifeplanForm.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                } else {
                    field.classList.remove('border-red-500');
                }
            });
            
            if (!isValid) {
                alert('Please fill in all required fields.');
                return;
            }
            
            // Get selected package and additional services
            const packageName = document.getElementById('lifeplanSelectedPackageName')?.value;
            const packagePrice = parseFloat(document.getElementById('lifeplanSelectedPackagePrice')?.value || 0);
            const selectedAddons = [];
            
            document.querySelectorAll('.traditional-addon:checked').forEach(checkbox => {
                selectedAddons.push({
                    name: checkbox.dataset.name,
                    price: parseFloat(checkbox.value)
                });
            });
            
            // Get personal information
            const firstName = document.getElementById('lifeplanHolderFirstName')?.value;
            const lastName = document.getElementById('lifeplanHolderLastName')?.value;
            const email = document.getElementById('lifeplanEmailAddress')?.value;
            const phone = document.getElementById('lifeplanContactNumber')?.value;
            
            // Get payment information
            const paymentTerm = document.getElementById('lifeplanPaymentTerm')?.value;
            const refNumber = document.getElementById('lifeplanReferenceNumber')?.value;
            
            // Create booking data object
            const bookingData = {
                package: {
                    name: packageName,
                    price: packagePrice
                },
                addons: selectedAddons,
                customer: {
                    firstName,
                    lastName,
                    email,
                    phone
                },
                payment: {
                    term: paymentTerm,
                    referenceNumber: refNumber
                }
            };
            
            console.log('Booking data:', bookingData);
            
            // Here you would typically send this data to your server
            // For now, just show a success message
            alert('Thank you for your booking! We will contact you shortly.');
            document.getElementById('traditionalModal').classList.add('hidden');
        });
    }
    
    // Handle resize events
    window.addEventListener('resize', function() {
        const traditionalModal = document.getElementById('traditionalModal');
        if (traditionalModal && !traditionalModal.classList.contains('hidden')) {
            const detailsSection = document.querySelector('.details-section');
            const formSection = document.querySelector('.form-section');
            
            if (window.innerWidth >= 768) {
                // Desktop view - show both sections
                if (detailsSection) detailsSection.classList.remove('hidden');
                if (formSection) formSection.classList.remove('hidden');
            } else {
                // Mobile view - show details by default, hide form
                if (detailsSection) detailsSection.classList.remove('hidden');
                if (formSection) formSection.classList.add('hidden');
            }
        }
    });
});
</script>

<style>
/* Additional styles if needed */
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
</style>


    

<script>
        function toggleMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
}
    </script>

    <?php include 'customService/chat_elements.html'; ?>
</body>
</html>