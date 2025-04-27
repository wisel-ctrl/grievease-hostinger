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
    <title>GrievEase - Services</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cinzel:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../tailwind.js"></script>
    <style>
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
                <span class="text-yellow-600 text-2xl">GrievEase</span>
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

                <a href="services.php" class="text-white hover:text-gray-300 transition relative group">
                    Services & Packages
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
        <a href="services.php" class="block text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300 relative group">
            <div class="flex justify-between items-center">
                <span>Services & Packages</span>
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

    <div class="bg-cream py-20">
    <div class="container mx-auto px-6 max-w-6xl">
        <!-- Hero Section -->
        <div class="relative rounded-2xl overflow-hidden mb-16 shadow-xl">
            <div class="h-64 bg-cover bg-center" style="background-image: url('../Landing_page/Landing_images/black-bg-image.jpg')">
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="absolute inset-0 flex flex-col justify-center items-center text-white p-8">
                    <h1 class="text-5xl md:text-6xl font-hedvig text-center mb-6">Our Services</h1>
                    <p class="text-lg max-w-2xl text-center">Dignified services, personalized care—honoring legacies with every farewell.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-6">
        <!-- Introduction -->
        <div class="text-center mb-12">
            <h2 class="text-4xl font-hedvig text-navy mb-4">Honoring Lives with Dignity and Respect</h2>
            <p class="text-dark text-lg max-w-4xl mx-auto">At GrievEase, we offer a comprehensive range of funeral services designed to honor your loved one's memory in a meaningful way. Our experienced team provides personalized care to guide you through this difficult time with compassion and understanding.</p>
            <div class="w-20 h-1 bg-yellow-600 mx-auto mt-6"></div>
            <div class="border-l-4 border-yellow-600 pl-4 italic my-8 text-left max-w-xl mx-auto">
                <p class="text-lg text-navy">"Mula noon, hanggang ngayon. A funeral service with a Heart..."</p>
            </div>
        </div>

        <!-- Services Categories -->
        <div class="mb-16">
            <div class="bg-white rounded-xl shadow-lg p-6 mb-8">
                <h3 class="font-hedvig text-navy text-2xl mb-6 pb-3 border-b border-gray-200">Our Service Categories</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Category 1 -->
                    <a href="#traditional" class="flex flex-col items-center p-4 bg-navy/5 rounded-lg hover:bg-navy/10 transition-colors duration-300">
                        <div class="w-16 h-16 rounded-full bg-yellow-600/90 flex items-center justify-center text-white mb-4">
                            <i class="fas fa-dove text-2xl"></i>
                        </div>
                        <h4 class="font-hedvig text-navy text-xl mb-2">Traditional Funeral</h4>
                        <p class="text-dark text-center text-sm">Full-service traditional funerals with viewing, ceremony, and burial options.</p>
                    </a>
                    
                    <!-- Category 2 -->
                    <a href="#cremation" class="flex flex-col items-center p-4 bg-navy/5 rounded-lg hover:bg-navy/10 transition-colors duration-300">
                        <div class="w-16 h-16 rounded-full bg-yellow-600/90 flex items-center justify-center text-white mb-4">
                            <i class="fas fa-fire text-2xl"></i>
                        </div>
                        <h4 class="font-hedvig text-navy text-xl mb-2">Cremation</h4>
                        <p class="text-dark text-center text-sm">Dignified cremation options with memorial service opportunities.</p>
                    </a>
                    
                    <!-- Category 3 -->
                    <a href="lifeplan.php" class="flex flex-col items-center p-4 bg-navy/5 rounded-lg hover:bg-navy/10 transition-colors duration-300">
                        <div class="w-16 h-16 rounded-full bg-yellow-600/90 flex items-center justify-center text-white mb-4">
                            <i class="fas fa-clipboard-list text-2xl"></i>
                        </div>
                        <h4 class="font-hedvig text-navy text-xl mb-2">Life Plan</h4>
                        <p class="text-dark text-center text-sm">Plan ahead to ensure your wishes are honored and provide peace of mind.</p>
                    </a>
                </div>
            </div>
        </div>

<!-- Traditional Funeral Services Section -->
<section id="traditional" class="scroll-mt-24">
    <!-- Section Header - Centered -->
    <div class="flex justify-center mb-8">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-full bg-yellow-600/90 flex items-center justify-center text-white mr-4">
                <i class="fas fa-dove text-xl"></i>
            </div>
            <h2 class="font-hedvig text-4xl text-navy">Traditional Funeral</h2>
        </div>
    </div>
    
    <!-- Paragraph - Centered -->
    <div class="flex justify-center mb-8">
        <p class="text-dark max-w-3xl text-lg text-center">Our traditional funeral services honor your loved one with dignity and respect while providing support for family and friends. Each service can be customized to reflect the unique life being celebrated.</p>
    </div>
    
    <!-- Packages Carousel -->
    <div class="max-w-6xl mx-auto relative">
        <!-- Carousel Container -->
        <div class="overflow-hidden relative">
            <div id="carousel-container" class="flex transition-transform duration-500 ease-in-out">
                <!-- Package 1: Legacy Tribute -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-4">
                    <div class="package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="700000" data-service="traditional" data-name="Legacy Tribute" data-image="../image/700.jpg">
                        <div class="h-12 bg-navy flex items-center justify-center">
                            <h4 class="text-white font-hedvig text-xl">Legacy Tribute</h4>
                            <div class="absolute top-0 right-0 w-16 h-16 flex items-center justify-center">
                                <div class="w-16 h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                            </div>
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <div class="mb-4 flex justify-center">
                                <img src="../image/700.jpg" alt="Legacy Tribute" class="w-full h-48 object-cover rounded-lg">
                            </div>
                            <div class="text-center mb-6">
                                <span class="text-3xl font-hedvig text-navy">₱700,000</span>
                            </div>
                            <ul class="space-y-3 mb-6 flex-grow">
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">3 sets of flower arrangements</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Catering on last day</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Premium casket selection</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Extended viewing period</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Complete funeral service</span>
                                </li>
                            </ul>
                            <button class="selectPackageBtn block w-full mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 text-center">
                    Select Package </button>
                        </div>
                    </div>
                </div>
                
                <!-- Package 2: Eternal Remembrance -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-4">
                    <div class="package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="300000" data-service="traditional" data-name="Eternal Remembrance" data-image="../image/300.jpg">
                        <div class="h-12 bg-navy flex items-center justify-center">
                            <h4 class="text-white font-hedvig text-xl">Eternal Remembrance</h4>
                            <div class="absolute top-0 right-0 w-16 h-16 flex items-center justify-center">
                                <div class="w-16 h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                            </div>
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <div class="mb-4 flex justify-center">
                                <img src="../image/300.jpg" alt="Legacy Tribute" class="w-full h-48 object-cover rounded-lg">
                            </div>
                            <div class="text-center mb-6">
                                <span class="text-3xl font-hedvig text-navy">₱300,000</span>
                            </div>
                            <ul class="space-y-3 mb-6 flex-grow">
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">2 sets of flower arrangements</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Premium casket selection</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Extended viewing period</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Complete funeral service</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Curtains and lighting</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Water dispenser</span>
                                </li>
                            </ul>
                            <button class="selectPackageBtn block w-full mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 text-center">
                    Select Package </button>
                        </div>
                    </div>
                </div>
                
                <!-- Package 3: Custom Memorial (Replaced Heritage Memorial) -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-4">
                    <div class="bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="custom" data-service="traditional" data-name="Custom Memorial" data-image="image/custom.jpg">
                        <div class="h-12 bg-navy flex items-center justify-center">
                            <h4 class="text-white font-hedvig text-xl">Custom Memorial</h4>
                            <div class="absolute top-0 right-0 w-16 h-16 flex items-center justify-center">
                                <div class="w-16 h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                            </div>
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <div class="mb-4 flex justify-center">
                                <img src="../Landing_Page/Landing_images/logo.png" alt="Custom Memorial" class="w-full h-48 object-cover rounded-lg">
                            </div>
                            <div class="text-center mb-6">
                                <span class="text-3xl font-hedvig text-navy">Starting at ₱150,000</span>
                                <p class="text-sm text-gray-600 mt-1">Final price depends on selections</p>
                            </div>
                            <ul class="space-y-3 mb-6 flex-grow">
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark"><strong>Choose your casket</strong> from our selection</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark"><strong>Select flower arrangements</strong> (1-3 sets)</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark"><strong>Customize viewing period</strong> to your needs</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark"><strong>Add catering options</strong> if desired</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Core funeral services included</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Personalized assistance throughout</span>
                                </li>
                            </ul>
                            <button class="customtraditionalpckg block w-full mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 text-center">
    Select Package
</button>
                        </div>
                    </div>
                </div>
                
                <!-- View All Packages Blur Card -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-4">
                    <a href="packages.php" class="block h-full">
                        <div class="bg-white/30 backdrop-blur-md rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full border-2 border-dashed border-navy/40">
                            <div class="flex items-center justify-center h-full p-6">
                                <div class="text-center">
                                    <div class="mb-6 flex justify-center">
                                        <div class="w-20 h-20 rounded-full bg-navy/10 flex items-center justify-center">
                                            <i class="fas fa-ellipsis-h text-3xl text-navy/60"></i>
                                        </div>
                                    </div>
                                    <h3 class="text-2xl font-hedvig text-navy mb-4">View All Packages</h3>
                                    <p class="text-dark/70 mb-6">Explore our complete range of funeral service options</p>
                                    <div class="inline-block bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300">
                                        View All
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Navigation Arrows -->
        <button id="prev-btn" class="absolute left-0 top-1/2 transform -translate-y-1/2 -ml-4 bg-yellow-600 w-10 h-10 rounded-full shadow-lg flex items-center justify-center border border-gray-200 z-20 hover:bg-yellow-700 focus:outline-none">
            <i class="fas fa-chevron-left text-white"></i>
        </button>
        <button id="next-btn" class="absolute right-0 top-1/2 transform -translate-y-1/2 -mr-4 bg-yellow-600 w-10 h-10 rounded-full shadow-lg flex items-center justify-center border border-gray-200 z-20 hover:bg-yellow-700 focus:outline-none">
            <i class="fas fa-chevron-right text-white"></i>
        </button>

        <!-- Dots Indicator -->
        <div class="flex justify-center mt-6">
            <div id="carousel-dots" class="flex space-x-2">
                <button class="w-3 h-3 rounded-full bg-navy opacity-100" data-index="0"></button>
                <button class="w-3 h-3 rounded-full bg-navy opacity-50" data-index="1"></button>
                <button class="w-3 h-3 rounded-full bg-navy opacity-50" data-index="2"></button>
                <button class="w-3 h-3 rounded-full bg-navy opacity-50" data-index="3"></button>
            </div>
        </div>
    </div>
</section>

</div>
    </div>

<!-- Carousel JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const carouselContainer = document.getElementById('carousel-container');
        const prevBtn = document.getElementById('prev-btn');
        const nextBtn = document.getElementById('next-btn');
        const dots = document.querySelectorAll('#carousel-dots button');
        
        let currentIndex = 0;
        const itemCount = 4; // Total number of items (3 packages + view all)
        const itemsPerView = window.innerWidth >= 768 ? 3 : 1; // Show 3 items on medium screens, 1 on small
        const maxIndex = itemCount - itemsPerView;
        
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
            if (newItemsPerView !== itemsPerView) {
                location.reload(); // Simple solution to handle responsive change
            }
        });
        
        // Initial setup
        updateCarousel();
    });
</script>
    
<section id="cremation" class="scroll-mb-16">
    <!-- Section Header - Centered -->
    <div class="flex justify-center mb-8">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-full bg-yellow-600/90 flex items-center justify-center text-white mr-4">
                <i class="fas fa-fire text-xl"></i>
            </div>
            <h2 class="font-hedvig text-4xl text-navy">Cremation Service</h2>
        </div>
    </div>
    
    <!-- Paragraph - Centered -->
    <div class="flex justify-center mb-8">
        <p class="text-dark max-w-3xl text-lg text-center">We offer comprehensive service options to honor your loved one's memory and meet your specific needs and preferences.</p>
    </div>
    
    <!-- Packages Carousel (matching traditional section) -->
    <div class="max-w-6xl mx-auto relative">
        <!-- Carousel Container -->
        <div class="overflow-hidden relative">
            <div id="cremation-carousel-container" class="flex transition-transform duration-500 ease-in-out">
                <!-- Package 1: Direct Cremation -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-4">
                    <div class="package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="35000" data-service="cremate" data-name="Direct Cremation" data-image="../Landing_Page/Landing_images/cremateSAMPOL.jpg">
                        <div class="h-12 bg-navy flex items-center justify-center">
                            <h4 class="text-white font-hedvig text-xl">Direct Cremation</h4>
                            <div class="absolute top-0 right-0 w-16 h-16 flex items-center justify-center">
                                <div class="w-16 h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                            </div>
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <div class="mb-4 flex justify-center">
                                <img src="../Landing_Page/Landing_images/cremateSAMPOL.jpg" alt="Direct Cremation" class="w-full h-48 object-cover rounded-lg">
                            </div>
                            <div class="text-center mb-6">
                                <span class="text-3xl font-hedvig text-navy">₱35,000</span>
                            </div>
                            <ul class="space-y-3 mb-6 flex-grow">
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Immediate cremation without viewing</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Basic cremation container</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Transportation of deceased</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Cremation process</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Necessary paperwork</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Basic urn</span>
                                </li>
                            </ul>
                            <button class="selectPackageBtn block w-full mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 text-center">
                            Select Package </button>
                        </div>
                    </div>
                </div>
                
                <!-- Package 2: Traditional Wake and Cremation -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-4">
                    <div class="package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="125000" data-service="cremate" data-name="Traditional Cremation" data-image="../Landing_Page/Landing_images/cremateSAMPOL.jpg">
                        <div class="h-12 bg-navy flex items-center justify-center">
                            <h4 class="text-white font-hedvig text-xl">Traditional Cremation</h4>
                            <div class="absolute top-0 right-0 w-16 h-16 flex items-center justify-center">
                                <div class="w-16 h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                            </div>
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <div class="mb-4 flex justify-center">
                                <img src="../Landing_Page/Landing_images/cremateSAMPOL.jpg" alt="Traditional Cremation" class="w-full h-48 object-cover rounded-lg">
                            </div>
                            <div class="text-center mb-6">
                                <span class="text-3xl font-hedvig text-navy">₱125,000</span>
                            </div>
                            <ul class="space-y-3 mb-6 flex-grow">
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Funeral casket</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Full wake service (3-5 days)</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Transportation of deceased</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Viewing and visitation services</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Memorial service</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Cremation on final day</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Premium urn</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Guest register book</span>
                                </li>
                            </ul>
                            <button class="selectPackageBtn block w-full mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 text-center">
                            Select Package </button>
                        </div>
                    </div>
                </div>
                
                <!-- Package 3: Custom Cremation -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-4">
                    <div class="package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="custom" data-service="cremate" data-name="Custom Cremation" data-image="../Landing_Page/Landing_images/cremateSAMPOL.jpg">
                        <div class="h-12 bg-navy flex items-center justify-center">
                            <h4 class="text-white font-hedvig text-xl">Custom Cremation</h4>
                            <div class="absolute top-0 right-0 w-16 h-16 flex items-center justify-center">
                                <div class="w-16 h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                            </div>
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <div class="mb-4 flex justify-center">
                                <img src="../Landing_Page/Landing_images/cremateSAMPOL.jpg" alt="Custom Cremation" class="w-full h-48 object-cover rounded-lg">
                            </div>
                            <div class="text-center mb-6">
                                <span class="text-3xl font-hedvig text-navy">Starting at ₱50,000</span>
                                <p class="text-sm text-gray-600 mt-1">Final price depends on selections</p>
                            </div>
                            <ul class="space-y-3 mb-6 flex-grow">
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark"><strong>Choose your cremation container</strong> from our selection</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark"><strong>Select urn options</strong> from basic to premium</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark"><strong>Add viewing period</strong> (none, private, or public)</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark"><strong>Include memorial service</strong> if desired</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark"><strong>Add flower arrangements</strong> to your service</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Cremation process included</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">All necessary paperwork handled</span>
                                </li>
                            </ul>
                            <button class="customcremationpckg block w-full mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 text-center">
    Select Package
</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

    <!-- Testimonials Section -->
<div class="mb-24 mt-16 bg-navy/5 py-12 px-6 rounded-xl" data-aos="fade-up">
    <div class="max-w-6xl mx-auto">
        <h3 class="text-3xl font-hedvig text-navy text-center mb-12">Words from Families We've Served</h3>
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
                <p class="text-dark italic mb-4">In our darkest hour, the team at GrievEase provided a light of compassion and support. Their attention to detail and genuine care made an unbearable time slightly more bearable. We will be forever grateful.</p>
                <p class="font-hedvig text-navy">- The Reyes Family</p>
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
                <p class="text-dark italic mb-4">Mr. Relova and his team treated our family with such respect and dignity. They guided us through every decision with patience and understanding, making a difficult process much easier to navigate.</p>
                <p class="font-hedvig text-navy">- The Santos Family</p>
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
                        <li><a href="services.php" class="text-gray-300 hover:text-white transition">Services & Packages</a></li>
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
    
    <!-- Initial Service Type Selection Modal (Hidden by Default) -->
<div id="serviceTypeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6">
            <h2 class="text-2xl font-hedvig text-navy mb-6 text-center">Select Service Type</h2>
            
            <div class="grid grid-cols-2 gap-4">
                <button id="traditionalServiceBtn" class="bg-cream hover:bg-yellow-100 border-2 border-yellow-600 text-navy px-6 py-8 rounded-lg shadow-md transition-all duration-300 flex flex-col items-center">
                    <i class="fas fa-dove text-3xl text-yellow-600 mb-2"></i>
                    <span class="font-hedvig text-lg">Traditional</span>
                    <span class="text-sm text-gray-600 mt-2 text-center">For immediate funeral needs</span>
                </button>
                
                <button id="lifeplanServiceBtn" class="bg-cream hover:bg-yellow-100 border-2 border-yellow-600 text-navy px-6 py-8 rounded-lg shadow-md transition-all duration-300 flex flex-col items-center">
                    <i class="fas fa-seedling text-3xl text-yellow-600 mb-2"></i>
                    <span class="font-hedvig text-lg">Lifeplan</span>
                    <span class="text-sm text-gray-600 mt-2 text-center">Pre-need funeral planning</span>
                </button>
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

<!-- Lifeplan Modal (Hidden by Default) -->
<div id="lifeplanModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[80vh]">
        <!-- Scroll container for both columns -->
        <div class="modal-scroll-container grid grid-cols-1 md:grid-cols-2 overflow-y-auto max-h-[80vh]">
            <!-- Left Side: Package Details -->
            <div class="bg-cream p-8">
                <!-- Package Image -->
                <div class="mb-6">
                    <img id="lifeplanPackageImage" src="" alt="" class="w-full h-64 object-cover rounded-lg mb-4">
                </div>

                <!-- Package Header -->
                <div class="flex justify-between items-center mb-6">
                    <h2 id="lifeplanPackageName" class="text-3xl font-hedvig text-navy"></h2>
                    <div id="lifeplanPackagePrice" class="text-3xl font-hedvig text-yellow-600"></div>
                </div>

                <!-- Package Description -->
                <p id="lifeplanPackageDesc" class="text-dark mb-6"></p>

                <!-- Main Package Details -->
                <div class="border-t border-gray-200 pt-4">
                    <h3 class="text-xl font-hedvig text-navy mb-4">Package Includes:</h3>
                    <ul id="lifeplanPackageFeatures" class="space-y-2">
                        <!-- Features will be inserted here by JavaScript -->
                    </ul>
                </div>

                <div class="border-t border-gray-200 pt-4 mt-6">
    <h3 class="text-xl font-hedvig text-navy mb-4">Additional Services:</h3>
    <div id="lifeplanAdditionalServices" class="space-y-3">
        <div class="flex items-center">
            <input type="checkbox" id="lifeplanFlowers" name="additionalServices" value="3500" class="lifeplan-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Floral Arrangements">
            <label for="lifeplanFlowers" class="ml-3 text-sm text-gray-700">Floral Arrangements (₱3,500)</label>
        </div>
        <div class="flex items-center">
            <input type="checkbox" id="lifeplanCatering" name="additionalServices" value="15000" class="lifeplan-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Catering Service (50 pax)">
            <label for="lifeplanCatering" class="ml-3 text-sm text-gray-700">Catering Service - 50 pax (₱15,000)</label>
        </div>
        <div class="flex items-center">
            <input type="checkbox" id="lifeplanVideography" name="additionalServices" value="7500" class="lifeplan-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Video Memorial Service">
            <label for="lifeplanVideography" class="ml-3 text-sm text-gray-700">Video Memorial Service (₱7,500)</label>
        </div>
        <div class="flex items-center">
            <input type="checkbox" id="lifeplanTransport" name="additionalServices" value="4500" class="lifeplan-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Additional Transportation">
            <label for="lifeplanTransport" class="ml-3 text-sm text-gray-700">Additional Transportation (₱4,500)</label>
        </div>
        <div class="flex items-center">
            <input type="checkbox" id="lifeplanUrn" name="additionalServices" value="6000" class="lifeplan-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500" data-name="Premium Urn Upgrade">
            <label for="lifeplanUrn" class="ml-3 text-sm text-gray-700">Premium Urn Upgrade (₱6,000)</label>
        </div>
    </div>
</div>
            </div>

            <!-- Right Side: Lifeplan Booking Form -->
            <div class="bg-white p-8 border-l border-gray-200 overflow-y-auto">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-hedvig text-navy">Book Your Package</h2>
                    <button class="closeModalBtn text-gray-500 hover:text-navy">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>

                <form id="lifeplanBookingForm" class="space-y-4">
                    <input type="hidden" id="lifeplanSelectedPackageName" name="packageName">
                    <input type="hidden" id="lifeplanSelectedPackagePrice" name="packagePrice">
                    
                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-lg font-hedvig text-navy mb-4">Plan Holder Information</h3>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="lifeplanHolderFirstName" class="block text-sm font-medium text-navy mb-2">First Name *</label>
                                <input type="text" id="lifeplanHolderFirstName" name="holderFirstName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanHolderMiddleName" class="block text-sm font-medium text-navy mb-2">Middle Name</label>
                                <input type="text" id="lifeplanHolderMiddleName" name="holderMiddleName" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="lifeplanHolderLastName" class="block text-sm font-medium text-navy mb-2">Last Name *</label>
                                <input type="text" id="lifeplanHolderLastName" name="holderLastName" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanHolderSuffix" class="block text-sm font-medium text-navy mb-2">Suffix</label>
                                <input type="text" id="lifeplanHolderSuffix" name="holderSuffix" class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="lifeplanDateOfBirth" class="block text-sm font-medium text-navy mb-2">Date of Birth *</label>
                                <input type="date" id="lifeplanDateOfBirth" name="dateOfBirth" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanContactNumber" class="block text-sm font-medium text-navy mb-2">Contact Number *</label>
                                <input type="tel" id="lifeplanContactNumber" name="contactNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label for="lifeplanEmailAddress" class="block text-sm font-medium text-navy mb-2">Email Address *</label>
                            <input type="email" id="lifeplanEmailAddress" name="emailAddress" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                        </div>
                        <div class="mt-4">
                            <label for="lifeplanHolderAddress" class="block text-sm font-medium text-navy mb-2">Current Address *</label>
                            <textarea id="lifeplanHolderAddress" name="holderAddress" rows="3" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600"></textarea>
                        </div>
                    </div>

                    <div class="border-b border-gray-200 pb-4 mb-4">
                        <h3 class="text-lg font-hedvig text-navy mb-4">Payment Plan</h3>
                        <div class="flex items-center mb-4">
                            <label class="block text-sm font-medium text-navy mb-2 mr-4">Payment Term:</label>
                            <select id="lifeplanPaymentTerm" name="paymentTerm" class="px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                <option value="60">5 Years (60 Monthly Payments)</option>
                                <option value="36">3 Years (36 Monthly Payments)</option>
                                <option value="24">2 Years (24 Monthly Payments)</option>
                                <option value="12">1 Year (12 Monthly Payments)</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="lifeplanGcashReceipt" class="block text-sm font-medium text-navy mb-2">First Payment Receipt *</label>
                                <input type="file" id="lifeplanGcashReceipt" name="gcashReceipt" accept=".pdf,.jpg,.jpeg,.png" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                            <div>
                                <label for="lifeplanReferenceNumber" class="block text-sm font-medium text-navy mb-2">GCash Reference Number *</label>
                                <input type="text" id="lifeplanReferenceNumber" name="referenceNumber" required class="w-full px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                            </div>
                        </div>
                    </div>

                    <div class="bg-cream p-4 rounded-lg">
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-navy">Package Total</span>
                            <span id="lifeplanTotalPrice" class="text-yellow-600">₱0</span>
                        </div>
                        <div class="flex justify-between text-sm mb-2">
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
                
                <!-- Step 1: Service Type Selection -->
                <div id="customStepServiceType" class="custom-step">
                    <h3 class="text-xl font-hedvig text-navy mb-6">Step 1: Select Service Type</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-white border-2 border-yellow-600 rounded-xl p-6 hover:shadow-lg transition-all duration-300 cursor-pointer service-option" data-service="traditional">
                            <div class="flex flex-col items-center text-center">
                                <i class="fas fa-dove text-4xl text-yellow-600 mb-4"></i>
                                <h4 class="text-xl font-hedvig text-navy mb-2">Traditional Service</h4>
                                <p class="text-gray-600 mb-4">For immediate funeral needs with full traditional services.</p>
                                <ul class="text-left w-full space-y-2 mb-4">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Immediate processing</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Complete funeral service</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>30% downpayment required</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="bg-white border-2 border-yellow-600 rounded-xl p-6 hover:shadow-lg transition-all duration-300 cursor-pointer service-option" data-service="lifeplan">
                            <div class="flex flex-col items-center text-center">
                                <i class="fas fa-seedling text-4xl text-yellow-600 mb-4"></i>
                                <h4 class="text-xl font-hedvig text-navy mb-2">Lifeplan Service</h4>
                                <p class="text-gray-600 mb-4">Pre-need funeral planning with flexible payment terms.</p>
                                <ul class="text-left w-full space-y-2 mb-4">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Pre-need planning</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Monthly payment options</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Transferable benefits</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-center">
                        <button id="nextToOptions" class="bg-yellow-600 hover:bg-yellow-700 text-white px-8 py-3 rounded-lg shadow-md transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Continue to Package Options
                        </button>
                    </div>
                </div>
                
                <!-- Step 2: Package Options Selection -->
                <div id="customStepOptions" class="custom-step hidden">
                    <div class="flex items-center mb-6">
                        <button id="backToServiceType" class="text-yellow-600 hover:text-yellow-700 mr-4">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </button>
                        <h3 class="text-xl font-hedvig text-navy">Step 2: Select Package Components</h3>
                    </div>
                    
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
                            
                            <!-- Traditional-specific -->
                            <div id="customTraditionalPayment" class="hidden">
                                <div class="flex justify-between text-sm mb-2">
                                    <span>Required Downpayment (30%):</span>
                                    <span id="customDownpayment" class="text-yellow-600">₱0</span>
                                </div>
                            </div>
                            
                            <!-- Lifeplan-specific -->
                            <div id="customLifeplanPayment" class="hidden">
                                <div class="flex items-center mb-4">
                                    <label class="block text-sm font-medium mr-4">Payment Term:</label>
                                    <select id="customPaymentTerm" class="px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                        <option value="60">5 Years (60 Monthly Payments)</option>
                                        <option value="36">3 Years (36 Monthly Payments)</option>
                                        <option value="24">2 Years (24 Monthly Payments)</option>
                                        <option value="12">1 Year (12 Monthly Payments)</option>
                                    </select>
                                </div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span>Monthly Payment:</span>
                                    <span id="customMonthlyPayment" class="text-yellow-600">₱0</span>
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
                
                <!-- Step 3: Booking Form (Will use existing forms depending on service type) -->
                <div id="customStepBooking" class="custom-step hidden">
                    <div class="flex items-center mb-6">
                        <button id="backToOptions" class="text-yellow-600 hover:text-yellow-700 mr-4">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </button>
                        <h3 class="text-xl font-hedvig text-navy">Step 3: Complete Your Booking</h3>
                    </div>
                    
                    <p class="mb-8">Please provide your information to complete the booking process.</p>
                    
                    <!-- Will load either traditional or lifeplan form here via JS -->
                    <div id="customBookingFormContainer">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Cremation Modal (Hidden by Default) -->
<div id="customCremationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 hidden">
    <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden max-h-[80vh]">
        <div class="modal-scroll-container overflow-y-auto max-h-[80vh]">
            <div class="bg-cream p-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-3xl font-hedvig text-navy">Custom Cremation Package</h2>
                    <button class="closeModalBtn text-gray-500 hover:text-navy">
                        <i class="fas fa-times text-2xl"></i>
                    </button>
                </div>
                
                <p class="text-dark mb-8">Create your personalized cremation package by selecting the options that best suit your needs and preferences. Our team will assist you throughout the process.</p>
                
                <!-- Step 1: Cremation Type Selection -->
                <div id="cremationStepType" class="cremation-step">
                    <h3 class="text-xl font-hedvig text-navy mb-6">Step 1: Select Cremation Type</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-white border-2 border-yellow-600 rounded-xl p-6 hover:shadow-lg transition-all duration-300 cursor-pointer cremation-type-option" data-cremation-type="direct">
                            <div class="flex flex-col items-center text-center">
                                <i class="fas fa-fire text-4xl text-yellow-600 mb-4"></i>
                                <h4 class="text-xl font-hedvig text-navy mb-2">Direct Cremation</h4>
                                <p class="text-gray-600 mb-4">Simple cremation service without viewing or ceremonies.</p>
                                <ul class="text-left w-full space-y-2 mb-4">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Basic cremation container</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Transport to crematory</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Return of cremated remains</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="bg-white border-2 border-yellow-600 rounded-xl p-6 hover:shadow-lg transition-all duration-300 cursor-pointer cremation-type-option" data-cremation-type="traditional">
                            <div class="flex flex-col items-center text-center">
                                <i class="fas fa-pray text-4xl text-yellow-600 mb-4"></i>
                                <h4 class="text-xl font-hedvig text-navy mb-2">Traditional Cremation with Wake</h4>
                                <p class="text-gray-600 mb-4">Full memorial service with viewing before cremation.</p>
                                <ul class="text-left w-full space-y-2 mb-4">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Viewing period with casket</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Memorial service</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Cremation after services</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-center">
                        <button id="nextToServiceType" class="bg-yellow-600 hover:bg-yellow-700 text-white px-8 py-3 rounded-lg shadow-md transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Continue to Service Type
                        </button>
                    </div>
                </div>
                
                <!-- Step 2: Service Type Selection -->
                <div id="cremationStepServiceType" class="cremation-step hidden">
                    <div class="flex items-center mb-6">
                        <button id="backToCremationType" class="text-yellow-600 hover:text-yellow-700 mr-4">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </button>
                        <h3 class="text-xl font-hedvig text-navy">Step 2: Select Service Type</h3>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="bg-white border-2 border-yellow-600 rounded-xl p-6 hover:shadow-lg transition-all duration-300 cursor-pointer service-option" data-service="immediate">
                            <div class="flex flex-col items-center text-center">
                                <i class="fas fa-clock text-4xl text-yellow-600 mb-4"></i>
                                <h4 class="text-xl font-hedvig text-navy mb-2">Immediate Service</h4>
                                <p class="text-gray-600 mb-4">For immediate cremation needs with full payment.</p>
                                <ul class="text-left w-full space-y-2 mb-4">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Immediate processing</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Complete service</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>30% downpayment required</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="bg-white border-2 border-yellow-600 rounded-xl p-6 hover:shadow-lg transition-all duration-300 cursor-pointer service-option" data-service="lifeplan">
                            <div class="flex flex-col items-center text-center">
                                <i class="fas fa-seedling text-4xl text-yellow-600 mb-4"></i>
                                <h4 class="text-xl font-hedvig text-navy mb-2">Lifeplan Service</h4>
                                <p class="text-gray-600 mb-4">Pre-need cremation planning with flexible payment terms.</p>
                                <ul class="text-left w-full space-y-2 mb-4">
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Pre-need planning</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Monthly payment options</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="fas fa-check text-yellow-600 mr-2 mt-1"></i>
                                        <span>Transferable benefits</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-center">
                        <button id="cremationnextToOptions" class="bg-yellow-600 hover:bg-yellow-700 text-white px-8 py-3 rounded-lg shadow-md transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Continue to Package Options
                        </button>
                    </div>
                </div>
                
                <!-- Step 3: Package Options Selection -->
                <div id="cremationStepOptions" class="cremation-step hidden">
                    <div class="flex items-center mb-6">
                        <button id="backToServiceType" class="text-yellow-600 hover:text-yellow-700 mr-4">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </button>
                        <h3 class="text-xl font-hedvig text-navy">Step 3: Select Package Components</h3>
                    </div>
                    
                    <div class="space-y-8">
                        <!-- Direct Cremation Options (Show only if Direct Cremation selected) -->
                        <div id="directCremationOptions" class="hidden">
                            <!-- Cremation Container Selection -->
                            <div class="border-b border-gray-200 pb-6">
                                <h4 class="text-lg font-hedvig text-navy mb-4">Select Cremation Container</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="border rounded-lg p-4 cursor-pointer container-option" data-price="5000" data-name="Standard Cremation Container">
                                        <img src="/api/placeholder/300/200" alt="Standard Cremation Container" class="w-full h-32 object-cover rounded-lg mb-2">
                                        <h5 class="font-medium mb-1">Standard Cremation Container</h5>
                                        <p class="text-sm text-gray-600 mb-1">Basic cardboard container</p>
                                        <p class="text-yellow-600">₱5,000</p>
                                    </div>
                                    <div class="border rounded-lg p-4 cursor-pointer container-option" data-price="15000" data-name="Pine Wood Container">
                                        <img src="/api/placeholder/300/200" alt="Pine Wood Container" class="w-full h-32 object-cover rounded-lg mb-2">
                                        <h5 class="font-medium mb-1">Pine Wood Container</h5>
                                        <p class="text-sm text-gray-600 mb-1">Simple pine wood container</p>
                                        <p class="text-yellow-600">₱15,000</p>
                                    </div>
                                    <div class="border rounded-lg p-4 cursor-pointer container-option" data-price="25000" data-name="Premium Wooden Container">
                                        <img src="/api/placeholder/300/200" alt="Premium Wooden Container" class="w-full h-32 object-cover rounded-lg mb-2">
                                        <h5 class="font-medium mb-1">Premium Wooden Container</h5>
                                        <p class="text-sm text-gray-600 mb-1">High-quality hardwood</p>
                                        <p class="text-yellow-600">₱25,000</p>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Urn Selection -->
                            <div class="border-b border-gray-200 pb-6">
                                <h4 class="text-lg font-hedvig text-navy mb-4">Select Urn</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="border rounded-lg p-4 cursor-pointer urn-option" data-price="8000" data-name="Basic Urn">
                                        <img src="/api/placeholder/300/200" alt="Basic Urn" class="w-full h-32 object-cover rounded-lg mb-2">
                                        <h5 class="font-medium mb-1">Basic Urn</h5>
                                        <p class="text-sm text-gray-600 mb-1">Simple ceramic design</p>
                                        <p class="text-yellow-600">₱8,000</p>
                                    </div>
                                    <div class="border rounded-lg p-4 cursor-pointer urn-option" data-price="15000" data-name="Classic Wooden Urn">
                                        <img src="/api/placeholder/300/200" alt="Classic Wooden Urn" class="w-full h-32 object-cover rounded-lg mb-2">
                                        <h5 class="font-medium mb-1">Classic Wooden Urn</h5>
                                        <p class="text-sm text-gray-600 mb-1">Polished wooden finish</p>
                                        <p class="text-yellow-600">₱15,000</p>
                                    </div>
                                    <div class="border rounded-lg p-4 cursor-pointer urn-option" data-price="30000" data-name="Premium Metal Urn">
                                        <img src="/api/placeholder/300/200" alt="Premium Metal Urn" class="w-full h-32 object-cover rounded-lg mb-2">
                                        <h5 class="font-medium mb-1">Premium Metal Urn</h5>
                                        <p class="text-sm text-gray-600 mb-1">Elegant metal design</p>
                                        <p class="text-yellow-600">₱30,000</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Traditional Cremation Options (Show only if Traditional Cremation selected) -->
                        <div id="traditionalCremationOptions" class="hidden">
                            <!-- Casket Selection for Viewing -->
                            <div class="border-b border-gray-200 pb-6">
                                <h4 class="text-lg font-hedvig text-navy mb-4">Select Viewing Casket</h4>
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
                            
                            <!-- Urn Selection (for post-cremation) -->
                            <div class="border-b border-gray-200 pb-6">
                                <h4 class="text-lg font-hedvig text-navy mb-4">Select Urn</h4>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="border rounded-lg p-4 cursor-pointer urn-option" data-price="8000" data-name="Basic Urn">
                                        <img src="/api/placeholder/300/200" alt="Basic Urn" class="w-full h-32 object-cover rounded-lg mb-2">
                                        <h5 class="font-medium mb-1">Basic Urn</h5>
                                        <p class="text-sm text-gray-600 mb-1">Simple ceramic design</p>
                                        <p class="text-yellow-600">₱8,000</p>
                                    </div>
                                    <div class="border rounded-lg p-4 cursor-pointer urn-option" data-price="15000" data-name="Classic Wooden Urn">
                                        <img src="/api/placeholder/300/200" alt="Classic Wooden Urn" class="w-full h-32 object-cover rounded-lg mb-2">
                                        <h5 class="font-medium mb-1">Classic Wooden Urn</h5>
                                        <p class="text-sm text-gray-600 mb-1">Polished wooden finish</p>
                                        <p class="text-yellow-600">₱15,000</p>
                                    </div>
                                    <div class="border rounded-lg p-4 cursor-pointer urn-option" data-price="30000" data-name="Premium Metal Urn">
                                        <img src="/api/placeholder/300/200" alt="Premium Metal Urn" class="w-full h-32 object-cover rounded-lg mb-2">
                                        <h5 class="font-medium mb-1">Premium Metal Urn</h5>
                                        <p class="text-sm text-gray-600 mb-1">Elegant metal design</p>
                                        <p class="text-yellow-600">₱30,000</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Additional Services Checkboxes (Common to both types) -->
                        <div class="border-b border-gray-200 pb-6">
                            <h4 class="text-lg font-hedvig text-navy mb-4">Additional Services</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="flex items-start border rounded-lg p-4">
                                    <input type="checkbox" id="cremationCatering" name="additionalServices" value="25000" class="cremation-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500 mt-1" data-name="Catering Service (100 pax)">
                                    <label for="cremationCatering" class="ml-3">
                                        <span class="block font-medium mb-1">Catering Service (100 pax)</span>
                                        <span class="block text-sm text-gray-600 mb-1">Food and refreshments for 100 people</span>
                                        <span class="text-yellow-600">₱25,000</span>
                                    </label>
                                </div>
                                <div class="flex items-start border rounded-lg p-4">
                                    <input type="checkbox" id="cremationVideo" name="additionalServices" value="15000" class="cremation-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500 mt-1" data-name="Video Memorial Package">
                                    <label for="cremationVideo" class="ml-3">
                                        <span class="block font-medium mb-1">Video Memorial Package</span>
                                        <span class="block text-sm text-gray-600 mb-1">Professional photo/video service</span>
                                        <span class="text-yellow-600">₱15,000</span>
                                    </label>
                                </div>
                                <div class="flex items-start border rounded-lg p-4">
                                    <input type="checkbox" id="cremationTransport" name="additionalServices" value="8000" class="cremation-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500 mt-1" data-name="Additional Transportation">
                                    <label for="cremationTransport" class="ml-3">
                                        <span class="block font-medium mb-1">Additional Transportation</span>
                                        <span class="block text-sm text-gray-600 mb-1">For family members (up to 10 people)</span>
                                        <span class="text-yellow-600">₱8,000</span>
                                    </label>
                                </div>
                                <div class="flex items-start border rounded-lg p-4">
                                    <input type="checkbox" id="cremationLiveStream" name="additionalServices" value="12000" class="cremation-addon h-5 w-5 text-yellow-600 rounded focus:ring-yellow-500 mt-1" data-name="Live Streaming Service">
                                    <label for="cremationLiveStream" class="ml-3">
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
                        
                        <div id="cremationSelectionsSummary" class="space-y-2 mb-4">
                            <p class="text-gray-500 italic">No items selected yet</p>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-4">
                            <div class="flex justify-between font-bold mb-2">
                                <span>Total Package Price:</span>
                                <span id="cremationTotalPrice" class="text-yellow-600">₱0</span>
                            </div>
                            
                            <!-- Immediate Service-specific -->
                            <div id="cremationImmediatePayment" class="hidden">
                                <div class="flex justify-between text-sm mb-2">
                                    <span>Required Downpayment (30%):</span>
                                    <span id="cremationDownpayment" class="text-yellow-600">₱0</span>
                                </div>
                            </div>
                            
                            <!-- Lifeplan-specific -->
                            <div id="cremationLifeplanPayment" class="hidden">
                                <div class="flex items-center mb-4">
                                    <label class="block text-sm font-medium mr-4">Payment Term:</label>
                                    <select id="cremationPaymentTerm" class="px-3 py-2 border border-input-border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-600">
                                        <option value="60">5 Years (60 Monthly Payments)</option>
                                        <option value="36">3 Years (36 Monthly Payments)</option>
                                        <option value="24">2 Years (24 Monthly Payments)</option>
                                        <option value="12">1 Year (12 Monthly Payments)</option>
                                    </select>
                                </div>
                                <div class="flex justify-between text-sm mb-2">
                                    <span>Monthly Payment:</span>
                                    <span id="cremationMonthlyPayment" class="text-yellow-600">₱0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex justify-center mt-8">
                        <button id="cremationProceedToBooking" class="bg-yellow-600 hover:bg-yellow-700 text-white px-8 py-3 rounded-lg shadow-md transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Continue to Booking Form
                        </button>
                    </div>
                </div>
                
                <!-- Step 4: Booking Form -->
                <div id="cremationStepBooking" class="cremation-step hidden">
                    <div class="flex items-center mb-6">
                        <button id="backToCremationOptions" class="text-yellow-600 hover:text-yellow-700 mr-4">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </button>
                        <h3 class="text-xl font-hedvig text-navy">Step 4: Complete Your Booking</h3>
                    </div>
                    
                    <p class="mb-8">Please provide your information to complete the booking process.</p>
                    
                    <!-- Will load either traditional or lifeplan form here via JS -->
                    <div id="cremationBookingFormContainer">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="customcremationpackage.js"></script>



<script src="custompackage.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show service type selection modal for all packages
    document.querySelectorAll('.selectPackageBtn').forEach(button => {
        button.addEventListener('click', function() {
            // Get package details from the parent card
            const packageCard = this.closest('.package-card');
            if (!packageCard) return; // Safety check
            
            const packageName = packageCard.dataset.name;
            const packagePrice = packageCard.dataset.price;
            const packageImage = packageCard.dataset.image || '';
            const serviceType = packageCard.dataset.service;

            // Store package details in sessionStorage for later use
            sessionStorage.setItem('selectedPackageName', packageName);
            sessionStorage.setItem('selectedPackagePrice', packagePrice);
            sessionStorage.setItem('selectedPackageImage', packageImage);
            sessionStorage.setItem('selectedServiceType', serviceType);
            
            // Get other details from the card content
            const features = Array.from(packageCard.querySelectorAll('ul li')).map(li => li.innerHTML);
            sessionStorage.setItem('selectedPackageFeatures', JSON.stringify(features));
            
            // Update service type modal based on service type
            if (serviceType === 'cremate') {
                // Change traditional button to cremation
                const traditionalBtn = document.getElementById('traditionalServiceBtn');
                traditionalBtn.innerHTML = `
                    <i class="fas fa-fire text-3xl text-yellow-600 mb-2"></i>
                    <span class="font-hedvig text-lg">Cremation</span>
                    <span class="text-sm text-gray-600 mt-2 text-center">For immediate cremation needs</span>
                `;
            } else {
                // Reset to default for other service types
                const traditionalBtn = document.getElementById('traditionalServiceBtn');
                traditionalBtn.innerHTML = `
                    <i class="fas fa-dove text-3xl text-yellow-600 mb-2"></i>
                    <span class="font-hedvig text-lg">Traditional</span>
                    <span class="text-sm text-gray-600 mt-2 text-center">For immediate funeral needs</span>
                `;
            }
            
            // Show service type selection modal
            document.getElementById('serviceTypeModal').classList.remove('hidden');
        });
    });

    // Traditional/Cremation Service button click event
    document.getElementById('traditionalServiceBtn').addEventListener('click', function() {
        // Hide service type modal
        document.getElementById('serviceTypeModal').classList.add('hidden');
        
        // Open traditional modal
        openTraditionalModal();
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

    // Lifeplan addon checkbox event handling
    document.querySelectorAll('.lifeplan-addon').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateLifeplanTotal();
        });
    });

    // Function to update lifeplan total price when addons are selected
    function updateLifeplanTotal() {
        const basePrice = parseInt(sessionStorage.getItem('selectedPackagePrice') || '0');
        let addonTotal = 0;
        
        // Calculate addons total
        document.querySelectorAll('.lifeplan-addon:checked').forEach(checkbox => {
            addonTotal += parseInt(checkbox.value);
        });
        
        // Update totals
        const totalPrice = basePrice + addonTotal;
        const months = parseInt(document.getElementById('lifeplanPaymentTerm').value);
        const monthlyPayment = Math.ceil(totalPrice / months);
        
        document.getElementById('lifeplanTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('lifeplanMonthlyPayment').textContent = `₱${monthlyPayment.toLocaleString()}`;
        
        // Update hidden fields
        document.getElementById('lifeplanSelectedPackagePrice').value = totalPrice;
    }

    // Function to open traditional modal with package details
    function openTraditionalModal() {
        // Get stored package details
        const packageName = sessionStorage.getItem('selectedPackageName');
        const packagePrice = sessionStorage.getItem('selectedPackagePrice');
        const packageImage = sessionStorage.getItem('selectedPackageImage');
        const packageFeatures = JSON.parse(sessionStorage.getItem('selectedPackageFeatures') || '[]');
        const serviceType = sessionStorage.getItem('selectedServiceType');
        
        // Update modal title based on service type
        if (serviceType === 'cremate') {
            document.querySelector('#traditionalModal .font-hedvig.text-2xl.text-navy').textContent = 'Book Your Cremation';
        } else {
            document.querySelector('#traditionalModal .font-hedvig.text-2xl.text-navy').textContent = 'Book Your Package';
        }
        
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

    // Lifeplan Service button click event
    document.getElementById('lifeplanServiceBtn').addEventListener('click', function() {
        // Hide service type modal
        document.getElementById('serviceTypeModal').classList.add('hidden');
        
        // Get stored package details
        const packageName = sessionStorage.getItem('selectedPackageName');
        const packagePrice = sessionStorage.getItem('selectedPackagePrice');
        const packageImage = sessionStorage.getItem('selectedPackageImage');
        const packageFeatures = JSON.parse(sessionStorage.getItem('selectedPackageFeatures') || '[]');
        
        // Update lifeplan modal with package details
        document.getElementById('lifeplanPackageName').textContent = packageName;
        document.getElementById('lifeplanPackagePrice').textContent = `₱${parseInt(packagePrice).toLocaleString()}`;
        
        if (packageImage) {
            document.getElementById('lifeplanPackageImage').src = packageImage;
            document.getElementById('lifeplanPackageImage').alt = packageName;
        }
        
        // Calculate monthly payment (default: 5 years / 60 months)
        const totalPrice = parseInt(packagePrice);
        const monthlyPayment = Math.ceil(totalPrice / 60);
        
        document.getElementById('lifeplanTotalPrice').textContent = `₱${totalPrice.toLocaleString()}`;
        document.getElementById('lifeplanMonthlyPayment').textContent = `₱${monthlyPayment.toLocaleString()}`;

        // Update features list
        const featuresList = document.getElementById('lifeplanPackageFeatures');
        featuresList.innerHTML = '';
        packageFeatures.forEach(feature => {
            featuresList.innerHTML += `<li class="flex items-center text-sm text-gray-700">${feature}</li>`;
        });
        
        // Update the form's hidden fields with package info
        document.getElementById('lifeplanSelectedPackageName').value = packageName;
        document.getElementById('lifeplanSelectedPackagePrice').value = packagePrice;
        
        // Show lifeplan modal
        document.getElementById('lifeplanModal').classList.remove('hidden');
    });

    document.querySelectorAll('.lifeplan-addon').forEach(checkbox => {
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
        document.getElementById('lifeplanModal').classList.add('hidden');
        document.getElementById('serviceTypeModal').classList.add('hidden');
    }

    // Close modals when pressing Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeAllModals();
        }
    });

    // Close modals when clicking outside of modal content
    document.querySelectorAll('#serviceTypeModal, #traditionalModal, #lifeplanModal').forEach(modal => {
        modal.addEventListener('click', function(event) {
            // Check if the click was directly on the modal background (not its children)
            if (event.target === modal) {
                closeAllModals();
            }
        });
    });

    // Update lifeplan monthly payment when payment term changes
    document.getElementById('lifeplanPaymentTerm').addEventListener('change', function() {
        updateLifeplanTotal();
        const months = parseInt(this.value);
        const totalPrice = parseInt(sessionStorage.getItem('selectedPackagePrice') || '0');
        const monthlyPayment = Math.ceil(totalPrice / months);
        
        let termText = '';
        if (months === 60) termText = '5 Years (60 Monthly Payments)';
        else if (months === 36) termText = '3 Years (36 Monthly Payments)';
        else if (months === 24) termText = '2 Years (24 Monthly Payments)';
        else if (months === 12) termText = '1 Year (12 Monthly Payments)';
        
        document.getElementById('lifeplanPaymentTermDisplay').textContent = termText;
        document.getElementById('lifeplanMonthlyPayment').textContent = `₱${monthlyPayment.toLocaleString()}`;
    });

    // Form submission for Traditional
    document.getElementById('traditionalBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        // Get service type
        const serviceType = sessionStorage.getItem('selectedServiceType');
        let successMessage = 'Traditional service booking submitted successfully!';
        
        if (serviceType === 'cremate') {
            successMessage = 'Cremation service booking submitted successfully!';
        }
        
        // Add booking submission logic here
        alert(successMessage);
        closeAllModals();
    });

    // Form submission for Lifeplan
    document.getElementById('lifeplanBookingForm').addEventListener('submit', function(e) {
        e.preventDefault();
        // Add booking submission logic here
        alert('Lifeplan booking submitted successfully!');
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