<?php
session_start();

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
                $stmt->close();
                $conn->close();
?>

<script src="customer_support.js"></script>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - GrievEase</title>
    <?php include 'faviconLogo.html'; ?>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cinzel:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&family=Hedvig+Letters+Serif:ital@0;1&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="tailwind.js"></script>
    <style>
        body {
            background-color: #F9F6F0;
        }
        .notification-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
        }
        :root {
            --navbar-height: 64px; /* Define the height of the navbar */
            --section-spacing: 4rem; /* Standardized spacing between sections */
        }
        
        /* Animation for notifications */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .notification-animate {
            animation: fadeIn 0.3s ease-out forwards;
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Filter button active state */
        .filter-active {
            position: relative;
            overflow: hidden;
        }
        
        .filter-active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #CA8A04;
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
    
    <!-- Main Content Container -->
    <div class="container mx-auto px-4 py-8 max-w-screen-xl mt-[var(--navbar-height)]">
        <!-- Page Header with subtle background -->
        <div class="bg-gradient-to-b from-yellow-600/10 to-transparent rounded-xl py-8 px-6 mb-10">
            <div class="max-w-3xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl font-hedvig text-navy mb-3">Notifications</h1>
                <p class="text-dark text-lg max-w-2xl mx-auto">Stay updated with important information about your services and arrangements.</p>
                <div class="w-16 h-1 bg-yellow-600 mx-auto mt-4"></div>
                
                <!-- Notification Summary -->
                <div class="mt-6 flex justify-center space-x-6">
                    <div class="flex flex-col items-center">
                        <div class="text-error font-bold text-2xl">2</div>
                        <div class="text-sm text-gray-600">Unread</div>
                    </div>
                    <div class="flex flex-col items-center">
                        <div class="text-navy font-bold text-2xl">5</div>
                        <div class="text-sm text-gray-600">Total</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Layout -->
        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Left Sidebar: Filter Controls -->
            <div class="lg:w-1/4">
                <div class="bg-white rounded-xl shadow-md p-5 sticky top-20">
                    <h2 class="text-navy text-xl mb-4">Filter by</h2>
                    
                    <!-- Filter Buttons -->
                    <div class="space-y-2">
                        <button id="filter-all" class="w-full bg-navy text-white px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between filter-active group transition-all duration-200">
                            <span class="flex items-center">
                                <i class="fas fa-inbox mr-3"></i>
                                <span>All Notifications</span>
                            </span>
                            <span class="bg-white text-navy w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs">5</span>
                        </button>
                        
                        <button id="filter-urgent" class="w-full bg-white hover:bg-error/10 border border-input-border text-navy px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between group transition-all duration-200">
                            <span class="flex items-center">
                                <span class="notification-dot bg-error mr-3"></span>
                                <span>Urgent</span>
                            </span>
                            <span class="bg-error/20 text-error w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs">1</span>
                        </button>
                        
                        <button id="filter-updates" class="w-full bg-white hover:bg-yellow-600/10 border border-input-border text-navy px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between group transition-all duration-200">
                            <span class="flex items-center">
                                <span class="notification-dot bg-yellow-600 mr-3"></span>
                                <span>Updates</span>
                            </span>
                            <span class="bg-yellow-600/20 text-yellow-600 w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs">1</span>
                        </button>
                        
                        <button id="filter-confirmations" class="w-full bg-white hover:bg-green-600/10 border border-input-border text-navy px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between group transition-all duration-200">
                            <span class="flex items-center">
                                <span class="notification-dot bg-success mr-3"></span>
                                <span>Confirmations</span>
                            </span>
                            <span class="bg-success/20 text-success w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs">1</span>
                        </button>
                        
                        <button id="filter-read" class="w-full bg-white hover:bg-gray-100 border border-input-border text-navy px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-between group transition-all duration-200">
                            <span class="flex items-center">
                                <i class="fas fa-check-circle text-gray-400 mr-3"></i>
                                <span>Read</span>
                            </span>
                            <span class="bg-gray-200 text-gray-600 w-6 h-6 rounded-full flex items-center justify-center font-bold text-xs">2</span>
                        </button>
                    </div>
                    
                    <!-- Additional Controls -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <button class="w-full bg-white border border-input-border text-navy px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-center hover:bg-secondary transition-colors mb-3">
                            <i class="fas fa-check-double mr-2"></i>
                            <span>Mark all as read</span>
                        </button>
                        
                        <button class="w-full bg-white border border-input-border text-error px-4 py-3 rounded-lg text-sm font-medium flex items-center justify-center hover:bg-error/5 transition-colors">
                            <i class="fas fa-trash-alt mr-2"></i>
                            <span>Clear all notifications</span>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Right Content: Notifications List -->
            <div class="lg:w-3/4">
                <!-- Search Bar -->
                <div class="mb-6 relative">
                    <input type="text" placeholder="Search notifications..." class="w-full pl-10 pr-4 py-3 bg-white border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600/50">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
                
                <!-- Notifications Container -->
                <div class="space-y-4">
                    <!-- Urgent Notification -->
                    <div class="bg-white rounded-lg shadow-lg p-5 border-l-4 border-error hover:shadow-xl transition-all duration-300 notification-animate">
                        <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                            <div class="flex items-start gap-4 w-full sm:w-auto">
                                <div class="rounded-full bg-error/10 p-3 flex-shrink-0 mt-1">
                                    <i class="fas fa-exclamation-circle text-error text-lg"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center mb-1">
                                        <span class="notification-dot bg-error mr-2"></span>
                                        <span class="text-xs text-error font-medium uppercase tracking-wider">Urgent</span>
                                    </div>
                                    <h3 class="font-hedvig text-navy text-xl font-medium">Payment Confirmation Required</h3>
                                    <p class="text-dark mt-2">Your payment for the traditional funeral service needs to be confirmed. Please check your email for details or contact our office.</p>
                                    <div class="flex items-center mt-3 text-sm text-gray-500">
                                        <i class="fas fa-clock mr-2"></i>
                                        <span>2 hours ago</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex sm:flex-col items-center sm:items-end gap-3 w-full sm:w-auto">
                                <button class="bg-navy text-white px-5 py-2 rounded-lg text-sm font-medium shadow-md hover:bg-navy/90 transition-colors w-full sm:w-auto">
                                    Review Payment
                                </button>
                                <button class="bg-white border border-input-border text-navy px-5 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors w-full sm:w-auto">
                                    Contact Support
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Update Notification -->
                    <div class="bg-white rounded-lg shadow-lg p-5 border-l-4 border-yellow-600 hover:shadow-xl transition-all duration-300 notification-animate animation-delay-100">
                        <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                            <div class="flex items-start gap-4 w-full sm:w-auto">
                                <div class="rounded-full bg-yellow-600/10 p-3 flex-shrink-0 mt-1">
                                    <i class="fas fa-bell text-yellow-600 text-lg"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center mb-1">
                                        <span class="notification-dot bg-yellow-600 mr-2"></span>
                                        <span class="text-xs text-yellow-600 font-medium uppercase tracking-wider">Update</span>
                                    </div>
                                    <h3 class="font-hedvig text-navy text-xl font-medium">Service Schedule Updated</h3>
                                    <p class="text-dark mt-2">The schedule for your loved one's memorial service has been updated. Please review the new time and location details.</p>
                                    <div class="flex items-center mt-3 text-sm text-gray-500">
                                        <i class="fas fa-clock mr-2"></i>
                                        <span>Yesterday at 3:45 PM</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex sm:flex-col items-center sm:items-end gap-3 w-full sm:w-auto">
                                <button class="bg-yellow-600 text-white px-5 py-2 rounded-lg text-sm font-medium shadow-md hover:bg-yellow-700 transition-colors w-full sm:w-auto">
                                    <i class="fas fa-calendar-alt mr-2"></i>
                                    View Details
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Confirmation Notification -->
                    <div class="bg-white rounded-lg shadow-lg p-5 border-l-4 border-success hover:shadow-xl transition-all duration-300 notification-animate animation-delay-200">
                        <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                            <div class="flex items-start gap-4 w-full sm:w-auto">
                                <div class="rounded-full bg-success/10 p-3 flex-shrink-0 mt-1">
                                    <i class="fas fa-check-circle text-success text-lg"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center mb-1">
                                        <span class="notification-dot bg-success mr-2"></span>
                                        <span class="text-xs text-success font-medium uppercase tracking-wider">Confirmed</span>
                                    </div>
                                    <h3 class="font-hedvig text-navy text-xl font-medium">Floral Arrangement Confirmed</h3>
                                    <p class="text-dark mt-2">Your floral arrangement selection has been confirmed and will be delivered as requested on the day of service.</p>
                                    <div class="flex items-center mt-3 text-sm text-gray-500">
                                        <i class="fas fa-clock mr-2"></i>
                                        <span>March 20, 2025</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex sm:flex-col items-center sm:items-end gap-3 w-full sm:w-auto">
                                <button class="bg-success text-white px-5 py-2 rounded-lg text-sm font-medium shadow-md hover:bg-success/90 transition-colors w-full sm:w-auto">
                                    <i class="fas fa-shopping-bag mr-2"></i>
                                    View Order
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Regular Update Notification (Read) -->
                    <div class="bg-white rounded-lg shadow-lg p-5 hover:shadow-xl transition-all duration-300 opacity-80 notification-animate animation-delay-300">
                        <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                            <div class="flex items-start gap-4 w-full sm:w-auto">
                                <div class="rounded-full bg-navy/10 p-3 flex-shrink-0 mt-1">
                                    <i class="fas fa-info-circle text-navy text-lg"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center mb-1">
                                        <span class="text-xs text-gray-500 font-medium uppercase tracking-wider">Documentation</span>
                                    </div>
                                    <h3 class="font-hedvig text-navy text-xl font-medium">Document Processing Complete</h3>
                                    <p class="text-dark mt-2">All necessary paperwork for your loved one's service has been processed. Copies are available upon request.</p>
                                    
                                    <div class="flex items-center justify-between mt-3">
                                        <div class="flex items-center text-sm text-gray-500">
                                            <i class="fas fa-clock mr-2"></i>
                                            <span>March 18, 2025</span>
                                        </div>
                                        <span class="text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded-full">Read</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex sm:flex-col items-center sm:items-end gap-3 w-full sm:w-auto">
                                <button class="bg-white border border-input-border text-navy px-5 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors w-full sm:w-auto">
                                    <i class="fas fa-download mr-2"></i>
                                    Download Copies
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Commemorative Item Notification (Read) -->
                    <div class="bg-white rounded-lg shadow-lg p-5 hover:shadow-xl transition-all duration-300 opacity-80 notification-animate animation-delay-400">
                        <div class="flex flex-col sm:flex-row justify-between items-start gap-4">
                            <div class="flex items-start gap-4 w-full sm:w-auto">
                                <div class="rounded-full bg-navy/10 p-3 flex-shrink-0 mt-1">
                                    <i class="fas fa-gift text-navy text-lg"></i>
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center mb-1">
                                        <span class="text-xs text-gray-500 font-medium uppercase tracking-wider">Memorial Items</span>
                                    </div>
                                    <h3 class="font-hedvig text-navy text-xl font-medium">Memorial Items Available</h3>
                                    <p class="text-dark mt-2">Memorial bookmarks and prayer cards are now available for your review. Please visit our office to approve the final design.</p>
                                    
                                    <div class="flex items-center justify-between mt-3">
                                        <div class="flex items-center text-sm text-gray-500">
                                            <i class="fas fa-clock mr-2"></i>
                                            <span>March 15, 2025</span>
                                        </div>
                                        <span class="text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded-full">Read</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex sm:flex-col items-center sm:items-end gap-3 w-full sm:w-auto">
                                <button class="bg-white border border-input-border text-navy px-5 py-2 rounded-lg text-sm font-medium hover:bg-secondary transition-colors w-full sm:w-auto">
                                    <i class="fas fa-eye mr-2"></i>
                                    View Items
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pagination Controls -->
                    <div class="flex justify-between items-center mt-8 bg-white rounded-lg shadow p-4">
                        <button class="text-navy hover:text-yellow-600 transition-colors disabled:text-gray-400" disabled>
                            <i class="fas fa-chevron-left mr-2"></i>
                            Previous
                        </button>
                        
                        <div class="flex items-center space-x-2">
                            <button class="w-8 h-8 rounded-full bg-navy text-white flex items-center justify-center">1</button>
                            <button class="w-8 h-8 rounded-full hover:bg-gray-100 text-navy flex items-center justify-center">2</button>
                            <button class="w-8 h-8 rounded-full hover:bg-gray-100 text-navy flex items-center justify-center">3</button>
                        </div>
                        
                        <button class="text-navy hover:text-yellow-600 transition-colors">
                            Next
                            <i class="fas fa-chevron-right ml-2"></i>
                        </button>
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
                    <div class="flex space-x-4"></div>
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
                            <a href="https://web.facebook.com/vjayrelovafuneralservices" class="hover:text-white transition">
                                <i class="fab fa-facebook-f mr-2 text-yellow-600"></i>
                                <span>VJay Relova Funeral Services</span>
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
    
    <script>
        function toggleMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
}
    </script>

    <?php include 'customService/chat_elements.html'; ?>
    
    </body>
    </html>