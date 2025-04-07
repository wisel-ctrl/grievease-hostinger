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
<nav class="bg-black text-white shadow-md w-full fixed top-0 left-0 z-50 px-4 sm:px-6 lg:px-8">
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
        <a href="index.php" class="block text-white py-2 hover:text-gray-300 relative group">
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

<script src="tailwind.js"></script>
<script>
function toggleMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
}
</script>
</body>
</html>