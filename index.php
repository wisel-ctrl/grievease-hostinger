<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hedvig+Letters+Serif:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="tailwind.js"></script>
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#B68D40">
    <style>
        .modal {
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
        }
        :root {
            --navbar-height: 64px; /* Define the height of the navbar */
            --section-spacing: 4rem; /* Standardized spacing between sections */
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
        /* Add this CSS to your stylesheet */
        @media (max-width: 639px) {
          /* Target the text container div */
          #home .relative .absolute {
            left: 50%;
            top: 35%;
            transform: translate(-50%, -50%);
            width: 100%;
            padding: 0 1rem;
          }
          
          /* Ensure the h1 is centered horizontally */
          h1.font-alexbrush {
            text-align: center;
            width: 100%;
          }
          
          /* Fix scroll indicator centering */
          .animate-bounce {
            left: 50% !important;
            transform: translateX(-50%) !important;
            right: auto !important;
          }
          
          /* Mobile menu styles */
        #mobile-menu a {
            text-align: left;
            padding-left: 1rem;
            padding-right: 1rem;
        }
        
        /* Hover underline for all links (including login) */
        #mobile-menu a span.absolute {
            left: 1rem;
        }
        
        /* Login/Register section - remove extra padding */
        #mobile-menu .mt-3 {
            padding-left: 0; /* Remove extra padding */
        }
        
        /* Register button - ensure left alignment */
        #mobile-menu .mt-3 a.bg-white {
            margin-left: 1rem; /* Match left padding */
            display: inline-block;
        }
        }

        .candlelight:hover {
            transform: scale(1.1);
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.8);
        }
        html {
        scroll-behavior: smooth;
        }

        @keyframes flicker {
        0%, 100% { transform: scaleY(1) scaleX(1); opacity: 0.9; }
        25% { transform: scaleY(1.1) scaleX(0.95); opacity: 1; }
        50% { transform: scaleY(0.95) scaleX(1.05); opacity: 0.9; }
        75% { transform: scaleY(1.05) scaleX(0.95); opacity: 1; }
        }
    
        @keyframes flame {
        0%, 100% { transform: translateX(-50%) scaleY(1) scaleX(1); opacity: 0.9; }
        25% { transform: translateX(-50%) scaleY(1.1) scaleX(0.95); opacity: 1; }
        50% { transform: translateX(-50%) scaleY(0.95) scaleX(1.05); opacity: 0.9; }
        75% { transform: translateX(-50%) scaleY(1.05) scaleX(0.95); opacity: 1; }
        }
    
        .animate-flame {
        animation: flame 2s ease-in-out infinite;
        }

        @keyframes flicker {
            0%, 100% { transform: scale(1); opacity: 1; }
            25% { transform: scale(1.1, 0.9); opacity: 0.9; }
            50% { transform: scale(0.95, 1.05); opacity: 1; }
            75% { transform: scale(1.05, 0.95); opacity: 0.9; }
        }
        
        /* For radial gradient support */
        .bg-gradient-radial {
            background-image: radial-gradient(var(--tw-gradient-stops));

            
        }
    </style>
</head>
<body class="font-hedvig bg-cream">
<div class="bg-cream overflow-x-hidden w-full max-w-full m-0 p-0 font-hedvig">
    <!-- Notification Toast (Hidden by default) -->
    <div id="notification" class="fixed top-0 right-0 m-4 p-4 bg-black text-white rounded shadow-lg z-50 hidden notification">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span id="notification-message">Notification message here</span>
        </div>
    </div>

    <!-- Navigation Bar - Improved & Smaller -->
<nav class="bg-black text-white shadow-md w-full fixed top-0 left-0 z-50 px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16">
        <!-- Left side: Logo and Text with Link -->
        <a href="#home" class="flex items-center space-x-2">
            <img src="Landing_Page/Landing_images/logo.png" alt="Logo" class="h-[42px] w-[38px]">
            <span class="text-yellow-600 text-3xl">GrievEase</span>
        </a>
        
        <!-- Center: Navigation Links (Hidden on small screens) -->
        <div class="hidden md:flex space-x-6">
            <a href="#home" class="text-white hover:text-gray-300 transition relative group">
                Home
                <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
            </a>
            <a href="about.php" class="text-white hover:text-gray-300 transition relative group">
                About
                <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
            </a>
            <a href="memorial.php" class="text-white hover:text-gray-300 transition relative group">
                Memorials
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
        
        <!-- Right side: Login/Register links (visible on medium and larger screens) -->
        <div class="hidden md:flex items-center space-x-3">
            <!-- Desktop Install Button (hidden by default) -->
<button id="installButton" class="hidden bg-green-600 text-white px-4 py-1.5 rounded-lg hover:bg-green-700 transition">
    Install App
</button>
            <a href="Landing_Page/login.php" class="text-white hover:text-gray-300 relative group">
                Login
                <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
            </a>
            <a href="Landing_Page/register.php" class="bg-white text-black px-4 py-1.5 rounded-lg hover:bg-gray-200 transition">
                Register
            </a>
        </div>
        
        <!-- Hamburger Menu Button (only visible on small screens) -->
        <button onclick="toggleMenu()" class="md:hidden focus:outline-none text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
            </svg>
        </button>
    </div>
    
    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden bg-black p-4 rounded-lg shadow-lg absolute left-0 right-0 mt-1 border-t border-gray-700">
        <a href="#home" class="block text-white py-2 hover:text-gray-300 relative group">
            Home
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
        </a>
        <a href="about.php" class="block text-white py-2 hover:text-gray-300 relative group">
            About
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
        </a>
        <a href="memorial.php" class="block text-white py-2 hover:text-gray-300 relative group">
            Memorials
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
        </a>
        <a href="services.php" class="block text-white py-2 hover:text-gray-300 relative group">
            Services & Packages
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
        </a>
        <a href="faqs.php" class="block text-white py-2 hover:text-gray-300 relative group">
            FAQs
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
        </a>
        <!-- Mobile Install Button (for hamburger menu) -->
<button id="installButtonMobile" class="hidden w-full text-left bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition mt-2">
    Install App
</button>
        <!-- Login & Register Section (FIXED) -->
    <div class="mt-3 pt-3 border-t border-gray-700">
        <a href="Landing_Page/login.php" class="block text-white py-2 hover:text-gray-300 relative group pl-4">
            Login
            <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-yellow-600 group-hover:w-full transition-all duration-300"></span>
        </a>
        <a href="Landing_Page/register.php" class="block text-white py-2 hover:text-gray-300 relative group pl-4">
            Register
        </a>
        </div>
    </div>
</nav>

<!-- Full-Page Hero Section with Background Image -->
<div id="home" class="relative w-full h-screen overflow-hidden mt-[var(--navbar-height)]">
    <!-- Background Image with Advanced Gradient Overlay -->
    <div class="absolute inset-0 bg-center bg-cover bg-no-repeat transition-transform duration-10000 ease-in-out hover:scale-105"
         style="background-image: url('Landing_Page/Landing_images/black-bg-image.jpg');">
        <!-- Multi-layered gradient overlay for depth and dimension -->
        <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/40 to-black/80"></div>
    </div>
    
    <!-- Content Container -->
<div class="relative h-full flex flex-col items-start justify-start px-6 md:px-12 z-10">
    <!-- Text centered vertically in the left side of screen -->
    <div class="absolute top-1/2 left-1/4 transform -translate-x-1/2 -translate-y-1/2 max-w-lg transition-all duration-1000 ease-out" 
         data-aos="fade-up" data-aos-delay="300" data-aos-duration="1000">
         <h1 class="font-alexbrush text-5xl sm:text-6xl lg:text-7xl leading-tight text-white text-shadow-lg mb-6">
            Mula noon, hanggang ngayon.<br>
            <span class="text-yellow-600">A funeral service with a Heart...</span>
        </h1>
    </div>
    </div>
    
    <!-- Decorative Elements -->
    <div class="absolute top-6 right-6 w-16 h-16 border-t-2 border-r-2 border-white/20 pointer-events-none"></div>
            <div class="absolute bottom-6 left-6 w-16 h-16 border-b-2 border-l-2 border-white/20 pointer-events-none"></div>
        </div>

    <!-- Scroll Indicator -->
    <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 flex flex-col items-center text-white/70 animate-bounce">
        <span class="text-sm tracking-wider mb-2">Scroll</span>
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
        </svg>
    </div>

    <!-- How to Use Our System Section -->
<div class="bg-cream py-16 px-8 relative overflow-hidden" id="how-to-use">
    <!-- Section Title -->
    <div class="max-w-6xl mx-auto mb-12 text-center">
        <h2 class="font-hedvig font-semibold text-5xl text-navy mb-4">How to Use GrievEase</h2>
        <p class="text-dark text-lg max-w-3xl mx-auto">Our platform provides simple, compassionate ways to honor and remember your loved ones. Follow these steps to get started.</p>
    </div>
    
    <!-- Step Cards -->
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-3 gap-8">
        <!-- Step 1: Create Account -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
            <div class="h-2 bg-yellow-600"></div>
            <div class="p-6">
                <div class="w-12 h-12 bg-yellow-600 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-user-plus text-white text-xl"></i>
                </div>
                <h3 class="font-hedvig font-semibold text-xl text-navy mb-3">1. Create an Account</h3>
                <p class="text-dark/80 text-m mb-4">Register for a GrievEase account to access all our memorial and funeral service features.</p>
                <a href="Landing_Page/register.php" class="text-yellow-600 hover:text-yellow-700 inline-flex items-center text-m">
                    Register now
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
        
        <!-- Step 2: Explore Services -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
            <div class="h-2 bg-yellow-600"></div>
            <div class="p-6">
                <div class="w-12 h-12 bg-yellow-600 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-list-alt text-white text-xl"></i>
                </div>
                <h3 class="font-hedvig font-semibold text-xl text-navy mb-3">2. Choose a Service</h3>
                <p class="text-dark/80 text-m mb-4">Browse our services and select the one that best honors your loved one's memory.</p>
                <a href="services.php" class="text-yellow-600 hover:text-yellow-700 inline-flex items-center text-m">
                    View services
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
        
        <!-- Step 3: Select Package -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1">
            <div class="h-2 bg-yellow-600"></div>
            <div class="p-6">
                <div class="w-12 h-12 bg-yellow-600 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-box text-white text-xl"></i>
                </div>
                <h3 class="font-hedvig text-xl font-semibold text-navy mb-3">3. Select a Package</h3>
                <p class="text-dark/80 text-m mb-4">Choose from our tailored packages designed to meet your specific needs and preferences.</p>
                <a href="services.php" class="text-yellow-600 hover:text-yellow-700 inline-flex items-center text-m">
                    See packages
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Additional Features -->
    <div class="max-w-6xl mx-auto mt-16 grid grid-cols-1 md:grid-cols-2 gap-8">
        <!-- Virtual Memorials -->
        <div class="flex bg-white rounded-lg shadow-md overflow-hidden">
            <div class="w-1/3 bg-cover bg-center" style="background-image: url('image/image.png');">
            </div>
            <div class="w-2/3 p-6">
                <h3 class="font-hedvig text-xl font-semibold text-navy mb-3">Virtual Memorial Candles</h3>
                <p class="text-dark/80 text-m mb-4">Light a virtual candle and write a personal dedication to honor your loved one's memory. Share your memorial with family and friends.</p>
                <a href="memorial.php" class="text-yellow-600 hover:text-yellow-700 inline-flex items-center text-m">
                    Light a candle
                    <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </a>
            </div>
        </div>
        
        <!-- Support & Resources -->
        <div class="flex bg-white rounded-lg shadow-md overflow-hidden">
            <div class="w-1/3 bg-cover bg-center" style="background-image: url('Landing_Page/Landing_images/black-bg-image.jpg');">
            </div>
            <div class="w-2/3 p-6">
                <h3 class="font-hedvig text-xl font-semibold text-navy mb-3">24/7 Support</h3>
                <p class="text-dark/80 text-m mb-4">Our compassionate team is always available to provide guidance and support throughout the entire process.</p>
                
            </div>
        </div>
    </div>
</div>

         <!-- Testimonials Section -->
         <div class="mb-16 mt-8 mr-8 ml-8 bg-navy/5 py-12 px-6 rounded-xl" data-aos="fade-up">
            <!-- Added max-width container for testimonials -->
            <div class="max-w-5xl mx-auto">
                <h3 class="text-4xl font-hedvig text-navy text-center mb-12">Words from Families We've Served</h3>
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
                        <p class="text-dark text-m italic mb-4">In our darkest hour, the team at VJay Relova provided a light of compassion and support. Their attention to detail and genuine care made an unbearable time slightly more bearable. We will be forever grateful.</p>
                        <p class="font-hedvig text-m text-navy">- The Reyes Family</p>
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
                        <p class="text-dark text-m italic mb-4">Mr. Relova and his team treated our family with such respect and dignity. They guided us through every decision with patience and understanding, making a difficult process much easier to navigate.</p>
                        <p class="font-hedvig text-m text-navy">- The Santos Family</p>
                    </div>
                </div>
            </div>
        </div>

    <!-- Footer -->
<footer class="bg-black font-playfair text-white py-8 md:py-12">
    <div class="container mx-auto px-4 md:px-6">
        <!-- Mobile Accordion Style for Small Screens -->
        <div class="md:hidden">
            <!-- Company Info - Always Visible -->
            <div class="mb-6">
                <h3 class="text-yellow-600 text-xl mb-3">GrievEase</h3>
                <p class="text-gray-300 text-sm mb-3">Providing dignified funeral services with compassion and respect since 1980.</p>
            </div>
            
            <!-- Collapsible Sections -->
            <details class="border-t border-gray-800 py-3">
                <summary class="text-lg font-medium cursor-pointer focus:outline-none">Quick Links</summary>
                <ul class="mt-3 pl-2 space-y-2">
                    <li><a href="#home" class="text-gray-300 hover:text-white transition text-sm">Home</a></li>
                    <li><a href="about.php" class="text-gray-300 hover:text-white transition text-sm">About</a></li>
                    <li><a href="memorial.php" class="text-gray-300 hover:text-white transition text-sm">Memorials</a></li>
                    <li><a href="services.php" class="text-gray-300 hover:text-white transition text-sm">Services & Packages</a></li>
                    <li><a href="faqs.php" class="text-gray-300 hover:text-white transition text-sm">FAQs</a></li>
                </ul>
            </details>
            
            <details class="border-t border-gray-800 py-3">
                <summary class="text-lg font-medium cursor-pointer focus:outline-none">Our Services</summary>
                <ul class="mt-3 pl-2 space-y-2">
                    <li><a href="traditional_funeral.php" class="text-gray-300 hover:text-white transition text-sm">Traditional Funeral</a></li>
                    <li><a href="lifeplan.php" class="text-gray-300 hover:text-white transition text-sm">Life Plan</a></li>
                </ul>
            </details>
            
            <details class="border-t border-gray-800 py-3">
                <summary class="text-lg font-medium cursor-pointer focus:outline-none">Contact Us</summary>
                <ul class="mt-3 pl-2 space-y-3">
                    <li class="flex items-start">
                        <i class="fas fa-map-marker-alt mt-1 mr-2 text-yellow-600"></i>
                        <span class="text-sm">#6 J.P Rizal St. Brgy. Sta Clara Sur, (Pob) Pila, Laguna</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-phone-alt mt-1 mr-2 text-yellow-600"></i>
                        <span class="text-sm">(0956) 814-3000 <br> (0961) 345-4283</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-envelope mt-1 mr-2 text-yellow-600"></i>
                        <span class="text-sm">GrievEase@gmail.com</span>
                    </li>
                    <li class="flex items-start">
                        <a href="https://web.facebook.com/vjayrelovafuneralservices" class="hover:text-white transition flex items-start">
                            <i class="fab fa-facebook-f mt-1 mr-2 text-yellow-600"></i>
                            <span class="text-sm">VJay Relova Funeral Services</span>
                        </a>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-clock mt-1 mr-2 text-yellow-600"></i>
                        <span class="text-sm">Available 24/7</span>
                    </li>
                </ul>
            </details>
        </div>
        
        <!-- Desktop Layout - Unchanged for Larger Screens -->
        <div class="hidden md:grid md:grid-cols-4 gap-8">
            <!-- Company Info -->
            <div>
                <h3 class="text-yellow-600 text-2xl mb-4">GrievEase</h3>
                <p class="text-gray-300 mb-4">Providing dignified funeral services with compassion and respect since 1980.</p>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h3 class="text-lg mb-4">Quick Links</h3>
                <ul class="space-y-2">
                    <li><a href="#home" class="text-gray-300 hover:text-white transition">Home</a></li>
                    <li><a href="about.php" class="text-gray-300 hover:text-white transition">About</a></li>
                    <li><a href="memorial.php" class="text-gray-300 hover:text-white transition">Memorials</a></li>
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
        
        <!-- Copyright Section -->
        <div class="border-t border-gray-800 mt-6 md:mt-8 pt-6 md:pt-8 text-center text-gray-400 text-xs md:text-sm">
            <p class="text-yellow-600">&copy; 2025 Vjay Relova Funeral Services. All rights reserved.</p>
            <div class="mt-2">
                <a href="privacy_policy.php" class="text-gray-400 hover:text-white transition mx-2">Privacy Policy</a>
                <a href="#" class="text-gray-400 hover:text-white transition mx-2">Terms of Service</a>
            </div>
        </div>
    </div>
</footer>


    <!-- Back to Top Button -->
<button onclick="window.scrollTo({top: 0, behavior: 'smooth'})" class="fixed bottom-8 right-8 bg-black hover:bg-black/80 text-white w-12 h-12 rounded-full flex items-center justify-center shadow-lg transition-all duration-300 opacity-100">
    <svg class="w-6 h-6 transform group-hover:-translate-y-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
    </svg>
</button>

    
    <!-- Additional Scripts -->
    <script>
        // Smooth scrolling for all links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
        
        // Add fade-in animation for elements as they enter viewport
        document.addEventListener('DOMContentLoaded', function() {
            const fadeInElements = document.querySelectorAll('.fade-in');
            
            function checkFade() {
                fadeInElements.forEach(element => {
                    const elementTop = element.getBoundingClientRect().top;
                    const elementVisible = 150;
                    
                    if (elementTop < window.innerHeight - elementVisible) {
                        element.classList.add('active');
                    }
                });
            }
            
            window.addEventListener('scroll', checkFade);
            checkFade(); // Check on initial load
        });

    </script>

<!-- Add this to your index.html before the closing body tag -->

<!-- Loading Animation Overlay -->
<div id="page-loader" class="fixed inset-0 bg-black bg-opacity-80 z-[999] flex items-center justify-center opacity-0 pointer-events-none transition-opacity duration-500">
    <div class="text-center">
        <!-- Animated Candle -->
        <div class="relative w-full h-48 mb-6">
            <!-- Candle -->
            <div class="absolute left-1/2 transform -translate-x-1/2 bottom-0 w-16">
                <!-- Wick -->
                <div class="w-1 h-5 bg-gray-700 mx-auto mb-0 rounded-t-lg"></div>
                
                <!-- Animated Flame -->
                <div>
                    <!-- Outer Flame -->
                    <div class="absolute left-1/2 transform -translate-x-1/2 bottom-[75px] w-6 h-12 bg-yellow-600/80 rounded-full blur-sm animate-pulse"></div>
                    
                    <!-- Inner Flame -->
                    <div class="absolute left-1/2 transform -translate-x-1/2 bottom-[80px] w-3 h-10 bg-white/90 rounded-full blur-[2px] animate-flame"></div>
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

<script>
    // Check if PWA is installable & show button
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    // Prevent automatic prompt (we want manual control)
    e.preventDefault();
    deferredPrompt = e;
    
    // Show install buttons
    document.getElementById('installButton').classList.remove('hidden');
    document.getElementById('installButtonMobile').classList.remove('hidden');
});

// Trigger installation when button is clicked
function installPWA() {
    if (deferredPrompt) {
        deferredPrompt.prompt(); // Show install dialog
        
        deferredPrompt.userChoice.then((choiceResult) => {
            if (choiceResult.outcome === 'accepted') {
                console.log('User accepted install');
            } else {
                console.log('User dismissed install');
            }
            deferredPrompt = null; // Reset for next time
        });
    }
}

// Add click listeners
document.getElementById('installButton').addEventListener('click', installPWA);
document.getElementById('installButtonMobile').addEventListener('click', installPWA);

// Hide buttons after installation
window.addEventListener('appinstalled', () => {
    document.getElementById('installButton').classList.add('hidden');
    document.getElementById('installButtonMobile').classList.add('hidden');
});
</script>
<script>
  if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/GrievEase/grievease-hostinger/sw.js')
    .then(registration => console.log('SW registered'))
    .catch(err => console.log('SW registration failed:', err));
}
</script>

</body>
</html>