<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hedvig+Letters+Serif:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<style>
    /* Add this CSS to your stylesheet */
        @media (max-width: 639px) {
         
          
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
</style>
<body>
<!-- Navigation Bar - Improved & Smaller -->
<nav class="bg-black text-white shadow-md w-full fixed top-0 left-0 z-50 px-4 sm:px-6 lg:px-8" style="height: var(--navbar-height);">
    <div class="flex justify-between items-center h-16">
        <!-- Left side: Logo and Text with Link -->
        <a href="index.php" class="flex items-center space-x-2">
            <img src="Landing_Page/Landing_images/logo.png" alt="Logo" class="h-[42px] w-[38px]">
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
        
        <!-- mobile menu header -->
        <div class="md:hidden flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <button onclick="toggleMenu()" class="focus:outline-none text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
                    </svg>
                </button>
            </div>
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
            <a href="memorial.php" class="block text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300 relative group">
                <div class="flex justify-between items-center">
                    <span>Memorials</span>
                    <i class="fas fa-monument text-yellow-600 opacity-0 group-hover:opacity-100 transition-opacity"></i>
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
            <!-- Mobile Install Button -->
            <button id="installButtonMobile" class="hidden w-full text-left bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition mb-2">
                Install App
            </button>
            <div class="space-y-2">
                <a href="Landing_Page/login.php" class="flex items-center justify-between text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300">
                    <span>Login</span>
                    <i class="fas fa-sign-in-alt text-yellow-600"></i>
                </a>
                <a href="Landing_Page/register.php" class="flex items-center justify-between text-white py-3 px-4 hover:bg-gray-800 rounded-lg transition-colors duration-300">
                    <span>Register</span>
                    <i class="fas fa-user-plus text-yellow-600"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<script src="tailwind.js"></script>
<script>
function toggleMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
}
</script>
</body>
</html>