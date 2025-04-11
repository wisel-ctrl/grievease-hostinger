
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - GrievEase</title>
    <?php include 'faviconLogo.html'; ?>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Alex+Brush&family=Inter:wght@300;400;500;600&family=Cinzel:wght@400;500;600;700&family=Hedvig+Letters+Serif:wght@400;500&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="tailwind.js"></script>
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
    <?php include 'navbar.php' ?>


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
                <img src="Landing_Page/Landing_images/logo.png" alt="GrievEase Platform" class="w-full h-auto">
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
                <img src="Landing_Page/Landing_images/sampleImageLANG.jpg" alt="VJay Relova History" class="w-full h-auto">
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
                        <div class="h-64 bg-cover bg-center relative" style="background-image: url('image/vjay_avatar.jpg')">
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
                        <div class="h-64 bg-cover bg-center relative" style="background-image: url('image/marcial_avatar.jpg')">
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
                        <div class="h-64 bg-cover bg-center relative" style="background-image: url('image/dave_avatar.jpg')">
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
    <img src="image/orgchart.png" alt="Organization Chart" class="w-full h-auto rounded-lg shadow-md">
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
                            <img src="image/flower.png">
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
<?php include 'footer.php' ?>
</body>
</html>