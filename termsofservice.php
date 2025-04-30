<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Terms of Service</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="tailwind.js"></script>
</head>
<?php include 'navbar.php' ?>
<body class="bg-cream text-dark font-inter">
    <!-- Full-Page Hero Section -->
    <div class="relative w-full h-[50vh] overflow-hidden">
        <div class="absolute inset-0 bg-center bg-cover bg-no-repeat" 
             style="background-image: url('Landing_Page/Landing_images/black-bg-image.jpg');">
            <div class="absolute inset-0 bg-gradient-to-b from-black/70 via-black/40 to-black/80"></div>
        </div>
        
        <div class="relative h-full flex items-center justify-center px-6 md:px-12 z-10">
            <div class="text-center" data-aos="fade-up" data-aos-delay="300" data-aos-duration="1000">
                <h1 class="font-hedvig text-5xl sm:text-6xl lg:text-7xl text-white text-shadow-lg mb-4">
                    Terms of Service
                </h1>
                <p class="text-white/80 max-w-2xl mx-auto text-lg">Last Updated: March 2024</p>
            </div>
        </div>
    </div>

    <!-- Terms of Service Content -->
    <div class="container mx-auto px-6 py-16 max-w-4xl">
        <div class="bg-white rounded-2xl shadow-lg p-8 md:p-12">
            <div class="prose prose-lg max-w-none">
                <section class="mb-12">
                    <h2 class="text-2xl font-hedvig text-navy mb-4">1. Acceptance of Terms</h2>
                    <p>By accessing and using the GrievEase website and services, you agree to be bound by these Terms of Service. If you do not agree with these terms, please do not use our services.</p>
                </section>

                <section class="mb-12">
                    <h2 class="text-2xl font-hedvig text-navy mb-4">2. Services</h2>
                    <p>GrievEase provides funeral and memorial services with the utmost compassion and respect. We reserve the right to modify, suspend, or discontinue any aspect of our services at any time.</p>
                </section>

                <section class="mb-12">
                    <h2 class="text-2xl font-hedvig text-navy mb-4">3. User Responsibilities</h2>
                    <ul class="list-disc list-inside space-y-2">
                        <li>Provide accurate and complete information during service arrangements</li>
                        <li>Respect the guidelines and policies of our funeral home</li>
                        <li>Treat our staff with dignity and respect</li>
                    </ul>
                </section>

                <section class="mb-12">
                    <h2 class="text-2xl font-hedvig text-navy mb-4">4. Privacy</h2>
                    <p>We are committed to protecting your privacy. Please review our Privacy Policy, which explains how we collect, use, and protect your personal information.</p>
                </section>

                <section class="mb-12">
                    <h2 class="text-2xl font-hedvig text-navy mb-4">5. Payment and Fees</h2>
                    <p>All fees for services are due at the time of service unless otherwise arranged. We accept various payment methods and can discuss payment plans during consultation.</p>
                </section>

                <section class="mb-12">
                    <h2 class="text-2xl font-hedvig text-navy mb-4">6. Limitation of Liability</h2>
                    <p>GrievEase strives to provide compassionate and professional services. However, we are not liable for any indirect, incidental, or consequential damages arising from our services.</p>
                </section>
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


    <?php include 'footer.php' ?>
</body>
</html>