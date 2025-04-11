<?php
// Start with PHP tag at the very beginning with no whitespace
session_start(); // Call session_start only once

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../Landing_Page/login.php");
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Customer Dashboard</title>
    <?php include 'faviconLogo.html'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="tailwind.js"></script>
    <script src="profile.js"></script>    
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
                    <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">2</span>
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
                <i class="fas fa-bell text-xl"></i>
                <span class="absolute -top-2 -right-2 bg-yellow-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center">2</span>
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
                            JD
                        </div>
                    </div>
                    <div class="ml-32">
                        <h1 class="font-hedvig text-3xl text-white">John Doe</h1>
                        <p class="text-white/80">Member since January 2025</p>
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
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                    <div class="p-6 border-b border-gray-100">
                        <h3 class="font-hedvig text-xl text-navy">Account Management</h3>
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
            <div class="p-6 border-b border-gray-100 flex justify-between items-center">
                <h3 class="font-hedvig text-xl text-navy">Personal Information</h3>
                <button id="edit-profile-btn" class="text-yellow-600 hover:text-yellow-700">
                    <i class="fas fa-pencil-alt mr-1"></i> Edit
                </button>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">First Name</label>
                        <p class="text-navy"> <?php echo htmlspecialchars($first_name); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Last Name</label>
                        <p class="text-navy"><?php echo htmlspecialchars($last_name); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Email Address</label>
                        <p class="text-navy"><?php echo htmlspecialchars($email); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Phone Number</label>
                        <p class="text-navy">(555) 123-4567</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Date of Birth</label>
                        <p class="text-navy"><?php echo date('F d Y', strtotime($birthdate)); ?></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Address</label>
                        <p class="text-navy">123 Main Street, Apt 4B</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">City</label>
                        <p class="text-navy">New York</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">State/Province</label>
                        <p class="text-navy">NY</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Zip/Postal Code</label>
                        <p class="text-navy">10001</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Country</label>
                        <p class="text-navy">United States</p>
                    </div>
                </div>
                
                <!-- New Uploaded Documents Section -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="font-hedvig text-lg text-navy mb-4">Uploaded Documents</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Uploaded ID</label>
                            <div class="flex items-center">
                                <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                                <p class="text-navy">Government_ID.pdf</p>
                                <a href="#" class="ml-2 text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Uploaded on: <?php echo date('F d, Y'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Existing Emergency Contact Section -->
                <div class="mt-8 pt-6 border-t border-gray-200">
                    <h3 class="font-hedvig text-lg text-navy mb-4">Emergency Contact</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Contact Name</label>
                            <p class="text-navy">Jane Doe</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Relationship</label>
                            <p class="text-navy">Spouse</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Phone Number</label>
                            <p class="text-navy">(555) 987-6543</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Email Address</label>
                            <p class="text-navy">jane.doe@example.com</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
                    
                    <!-- Bookings Tab -->
                    <div id="bookings" class="tab-content">
                        <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
                            <div class="p-6 border-b border-gray-100">
                                <h3 class="font-hedvig text-xl text-navy">My Bookings</h3>
                            </div>
                            <div class="p-6">
                                <!-- Active Booking -->
                                <div class="bg-yellow-600/5 border border-yellow-600/20 rounded-lg p-4 mb-6">
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <span class="bg-yellow-600/10 text-yellow-600 text-xs px-2 py-1 rounded-full">Active</span>
                                        </div>
                                        <p class="text-sm text-gray-500">Booking ID: #GE-2025-1234</p>
                                    </div>
                                    <h4 class="font-hedvig text-lg text-navy mb-2">Traditional Funeral Package</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                                        <div>
                                            <p class="text-sm text-gray-500">Service Date</p>
                                            <p class="text-navy">March 15, 2025</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Service Time</p>
                                            <p class="text-navy">10:00 AM - 12:00 PM</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Total Amount</p>
                                            <p class="text-navy font-bold">$4,995.00</p>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <button class="bg-navy/5 text-navy px-3 py-1 rounded hover:bg-navy/10 transition text-sm mr-2">
                                            <i class="fas fa-file-alt mr-1"></i> View Details
                                        </button>
                                        <button class="modify-booking bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 transition text-sm mr-2" data-booking="GE-2025-1234">
                                            <i class="fas fa-edit mr-1"></i> Modify
                                        </button>
                                        <button class="cancel-booking bg-red-600 text-white px-3 py-1 rounded hover:bg-red-700 transition text-sm" data-booking="GE-2025-1234">
                                            <i class="fas fa-times mr-1"></i> Cancel
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Past Booking -->
                                <div class="border border-gray-200 rounded-lg p-4 mb-6">
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <span class="bg-green-500/10 text-green-500 text-xs px-2 py-1 rounded-full">Completed</span>
                                        </div>
                                        <p class="text-sm text-gray-500">Booking ID: #GE-2025-0987</p>
                                    </div>
                                    <h4 class="font-hedvig text-lg text-navy mb-2">Memorial Service Package</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                                        <div>
                                            <p class="text-sm text-gray-500">Service Date</p>
                                            <p class="text-navy">January 20, 2025</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Service Time</p>
                                            <p class="text-navy">2:00 PM - 4:00 PM</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Total Amount</p>
                                            <p class="text-navy font-bold">$2,895.00</p>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <button class="bg-navy/5 text-navy px-3 py-1 rounded hover:bg-navy/10 transition text-sm">
                                            <i class="fas fa-file-alt mr-1"></i> View Details
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Life Plan Booking -->
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">Life Plan</span>
                                        </div>
                                        <p class="text-sm text-gray-500">Plan ID: #GE-PLAN-2025-5678</p>
                                    </div>
                                    <h4 class="font-hedvig text-lg text-navy mb-2">Premium Life Plan</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                                        <div>
                                            <p class="text-sm text-gray-500">Payment Schedule</p>
                                            <p class="text-navy">Monthly</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Next Payment</p>
                                            <p class="text-navy">April 15, 2025</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-500">Monthly Payment</p>
                                            <p class="text-navy font-bold">$85.00</p>
                                        </div>
                                    </div>
                                    <div class="flex justify-end">
                                        <button class="bg-navy/5 text-navy px-3 py-1 rounded hover:bg-navy/10 transition text-sm mr-2">
                                            <i class="fas fa-file-alt mr-1"></i> View Details
                                        </button>
                                        <button class="modify-booking bg-yellow-600 text-white px-3 py-1 rounded hover:bg-yellow-700 transition text-sm" data-booking="GE-PLAN-2025-5678">
                                            <i class="fas fa-edit mr-1"></i> Modify Plan
                                        </button>
                                    </div>
                                </div>
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
                    
<!-- Edit Profile Modal -->
<div id="edit-profile-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden overflow-y-auto">
    <!-- Modal Backdrop -->
    <div class="fixed inset-0 bg-black bg-opacity-60 backdrop-blur-sm"></div>
    
    <!-- Modal Content - Responsive width and max-height -->
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl mx-4 z-10 transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="bg-gradient-to-r from-yellow-600 to-white flex justify-between items-center p-4 sm:p-6 md:p-8 flex-shrink-0 rounded-t-2xl">
            <h3 class="text-xl sm:text-2xl font-bold text-white">Edit Profile</h3>
            <button type="button" id="close-edit-profile-modal" class="bg-black bg-opacity-20 hover:bg-opacity-30 rounded-full p-2 sm:p-3 text-white hover:text-white transition-all duration-200">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 sm:h-6 sm:w-6" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>
        
        <!-- Modal Body - More spacious layout, responsive padding -->
        <div class="p-4 sm:p-6 md:p-8 space-y-4 sm:space-y-6">
            <p class="text-gray-600 text-base sm:text-lg mb-4 sm:mb-6">Update your personal information below. Fields marked with * are required.</p>
            
            <form class="space-y-4 sm:space-y-6">
                <div class="grid sm:grid-cols-2 gap-4 sm:gap-6">
                    <div>
                        <label for="firstName" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">First Name*</label>
                        <input type="text" id="firstName" name="firstName" value="John" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent text-sm sm:text-base">
                    </div>
                    
                    <div>
                        <label for="lastName" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">Last Name*</label>
                        <input type="text" id="lastName" name="lastName" value="Doe" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent text-sm sm:text-base">
                    </div>
                </div>
                
                <div class="grid sm:grid-cols-2 gap-4 sm:gap-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">Email Address*</label>
                        <div class="relative">
                            <input type="email" id="email" name="email" value="john.doe@example.com" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent pr-10 text-sm sm:text-base">
                            <span class="absolute right-3 sm:right-4 top-1/2 transform -translate-y-1/2 text-yellow-600">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                    <polyline points="22,6 12,13 2,6"></polyline>
                                </svg>
                            </span>
                        </div>
                    </div>
                    
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">Phone Number*</label>
                        <div class="relative">
                            <input type="tel" id="phone" name="phone" value="(555) 123-4567" required class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent pr-10 text-sm sm:text-base">
                            <span class="absolute right-3 sm:right-4 top-1/2 transform -translate-y-1/2 text-yellow-600">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div>
                    <label for="dob" class="block text-sm font-medium text-gray-700 mb-1 sm:mb-2">Date of Birth</label>
                    <input type="date" id="dob" name="dob" value="1980-01-15" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent text-sm sm:text-base">
                </div>
                
                <!-- Address Information Section -->
                <div class="bg-navy p-4 sm:p-6 rounded-xl">
                    <h4 class="text-lg sm:text-xl font-bold text-white flex items-center mb-4 sm:mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 sm:mr-3 text-yellow-600">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Address Information
                    </h4>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <!-- Region Dropdown -->
                        <div class="relative">
                            <select id="region" name="region" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent appearance-none text-sm sm:text-base" onchange="updateProvinces()">
                                <option value="" selected disabled>Select Region</option>
                                <!-- Regions will be populated via JS or server-side -->
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 sm:px-3 text-gray-700">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Province Dropdown -->
                        <div class="relative">
                            <select id="province" name="province" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent appearance-none text-sm sm:text-base" onchange="updateCities()" disabled>
                                <option value="" selected disabled>Select Province</option>
                                <!-- Provinces will be populated based on selected region -->
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 sm:px-3 text-gray-700">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- City/Municipality Dropdown -->
                        <div class="relative">
                            <select id="city" name="city" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent appearance-none text-sm sm:text-base" onchange="updateBarangays()" disabled>
                                <option value="" selected disabled>Select City/Municipality</option>
                                <!-- Cities will be populated based on selected province -->
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 sm:px-3 text-gray-700">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Barangay Dropdown -->
                        <div class="relative">
                            <select id="barangay" name="barangay" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent appearance-none text-sm sm:text-base" disabled>
                                <option value="" selected disabled>Select Barangay</option>
                                <!-- Barangays will be populated based on selected city -->
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 sm:px-3 text-gray-700">
                                <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </div>
                        </div>
                        
                        <!-- Street Address Manual Input (full width) -->
                        <div class="sm:col-span-2">
                            <input type="text" id="street_address" name="street_address" placeholder="Street Address (House/Lot/Unit No., Building, Street Name)" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent text-sm sm:text-base">
                        </div>
                        
                        <!-- Zip/Postal Code -->
                        <div class="sm:col-span-2">
                            <input type="text" id="zip" name="zip" placeholder="Zip/Postal Code" class="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600 focus:border-transparent text-sm sm:text-base">
                        </div>
                    </div>
                </div>

                <script>
                    function updateProvinces() {
                        // Enable the province dropdown
                        document.getElementById('province').disabled = false;
                        // Disable dependent dropdowns
                        document.getElementById('city').disabled = true;
                        document.getElementById('barangay').disabled = true;
                        // Reset dependent dropdown values
                        document.getElementById('city').innerHTML = '<option value="" selected disabled>Select City/Municipality</option>';
                        document.getElementById('barangay').innerHTML = '<option value="" selected disabled>Select Barangay</option>';
                    }
                    
                    function updateCities() {
                        // Enable the city dropdown
                        document.getElementById('city').disabled = false;
                        // Disable dependent dropdown
                        document.getElementById('barangay').disabled = true;
                        // Reset dependent dropdown value
                        document.getElementById('barangay').innerHTML = '<option value="" selected disabled>Select Barangay</option>';
                    }
                    
                    function updateBarangays() {
                        // Enable the barangay dropdown
                        document.getElementById('barangay').disabled = false;
                    }
                </script>
                
                <!-- Document Uploads Section -->
                <div class="bg-navy p-4 sm:p-6 rounded-xl space-y-4 sm:space-y-6">
                    <h4 class="text-lg sm:text-xl font-bold text-white flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 sm:mr-3 text-yellow-600">
                            <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                            <polyline points="13 2 13 9 20 9"></polyline>
                        </svg>
                        Valid ID
                    </h4>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <!-- ID Upload -->
                        <div>
                            <label for="id-upload" class="block text-sm font-medium text-white mb-2 sm:mb-3">Government-Issued ID*</label>
                            <div class="flex items-center justify-center w-full">
                                <label for="id-upload" class="flex flex-col border-4 border-dashed border-gray-300 hover:bg-gray-100 hover:border-yellow-600 rounded-lg p-4 sm:p-6 group text-center cursor-pointer">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-8 h-8 sm:w-12 sm:h-12 text-gray-400 group-hover:text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="text-sm sm:text-base text-gray-500 group-hover:text-yellow-600 mt-2">Upload Government ID</p>
                                        <p class="text-xs sm:text-sm text-gray-500">(PDF, JPG, PNG)</p>
                                        <p class="text-xs text-gray-500 mt-1">Max file size: 5MB</p>
                                    </div>
                                    <input type="file" id="id-upload" name="id-upload" class="hidden" accept=".pdf,.jpg,.jpeg,.png" required>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Example of a Correct ID Upload - Stack on mobile -->
                        <div class="sm:block">
                            <h5 class="block text-sm font-medium text-white mb-2 sm:mb-3">Example of a Correct ID Upload</h5>
                            <div class="bg-blue-50 border-l-4 border-blue-500 p-3 sm:p-4">
                                <h5 class="font-bold text-navy mb-1 sm:mb-2 text-sm sm:text-base">Example of a Correct ID Upload</h5>
                                <ul class="list-disc list-inside text-xs sm:text-sm text-blue-700 space-y-1 sm:space-y-2">
                                    <li>Full document clearly visible</li>
                                    <li>No glare or shadows</li>
                                    <li>All four corners of the ID are shown</li>
                                    <li>High-resolution (at least 300 DPI)</li>
                                    <li>Personal information is legible</li>
                                    <li>No cuts or cropped edges</li>
                                </ul>
                                <div class="mt-2 sm:mt-3 flex justify-center">
                                    <div class="flex flex-col sm:flex-row sm:space-x-4 space-y-2 sm:space-y-0">
                                        <img src="../image/wrongID.jpg" alt="Incorrect ID Upload" class="w-full sm:w-1/2 max-w-md">
                                        <img src="../image/rightID.jpg" alt="Correct ID Upload" class="w-full sm:w-1/2 max-w-md">
                                    </div>
                                </div>
                                <p class="text-xs text-blue-600 mt-1 sm:mt-2 text-center">Top/Left: Poor Upload, Bottom/Right: Correct Upload</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
            
        <!-- Modal Footer -->
        <div class="p-4 sm:p-6 md:p-8 flex flex-col sm:flex-row sm:justify-end gap-3 sm:gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
            <button class="w-full sm:w-auto px-4 sm:px-6 py-2 sm:py-3 bg-white border border-yellow-600 text-gray-800 rounded-lg font-semibold hover:bg-gray-100 transition-colors text-sm sm:text-base" onclick="closeEditProfileModal()">Cancel</button>
            <button class="w-full sm:w-auto px-5 sm:px-7 py-2 sm:py-3 bg-yellow-600 text-white rounded-lg font-semibold hover:bg-yellow-700 transition-colors flex items-center justify-center sm:justify-start text-sm sm:text-base">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" sm:width="20" sm:height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                    <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                    <polyline points="17 21 17 13 7 13 7 21"></polyline>
                    <polyline points="7 3 7 8 15 8"></polyline>
                </svg>
                Save Changes
            </button>
        </div>
    </div>
</div>

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
        <div class="p-6 flex justify-end gap-4 border-t border-gray-200 sticky bottom-0 bg-white">
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

<script>
    function toggleMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
}
</script>
</body> 
</html>