<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Services</title>
    <?php include 'faviconLogo.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cinzel:wght@400;500;600;700&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="tailwind.js"></script>
    <style>
        :root {
            --navbar-height: 64px; /* Define the height of the navbar */
            --section-spacing: 4rem; /* Standardized spacing between sections */
        }
    </style>
</head>
<body class="bg-cream overflow-x-hidden w-full max-w-full m-0 p-0 font-hedvig">
    <?php include 'navbar.php' ?>

    <!-- Breadcrumb Navigation -->
    <div class="bg-white border-b border-gray-200 fixed top-[var(--navbar-height)] left-0 right-0 z-40">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <nav class="flex items-center text-sm" aria-label="Breadcrumb">
                <ol id="dynamic-breadcrumb" class="flex items-center space-x-2">
                    <!-- Breadcrumb will be populated by JavaScript -->
                </ol>
            </nav>
        </div>
    </div>
    <script src="breadcrumb-navigation.js"></script>

    <div class="bg-cream py-20" style="margin-top: calc(var(--navbar-height) + 48px);">
    <div class="container mx-auto px-6 max-w-6xl">
        <!-- Hero Section -->
        <div class="relative rounded-2xl overflow-hidden mb-16 shadow-xl">
            <div class="h-64 bg-cover bg-center" style="background-image: url('../Landing_Page/Landing_images/black-bg-image.jpg')">
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Category 1 -->
            <a href="traditional_funeral.php" class="flex flex-col items-center p-4 bg-navy/5 rounded-lg hover:bg-navy/10 transition-colors duration-300">
                <div class="w-16 h-16 rounded-full bg-yellow-600/90 flex items-center justify-center text-white mb-4">
                    <i class="fas fa-dove text-2xl"></i>
                </div>
                <h4 class="font-hedvig text-navy text-xl mb-2">Traditional Funeral</h4>
                <p class="text-dark text-center text-sm">Full-service traditional funerals with viewing, ceremony, and burial options.</p>
            </a>
            
            <!-- Category 2 -->
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
<section id="traditional" class="scroll-mt-24 mb-16 px-4 sm:px-0">
    <!-- Section Header - Centered -->
    <div class="flex justify-center mb-6 sm:mb-8">
        <div class="flex items-center">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-yellow-600/90 flex items-center justify-center text-white mr-3 sm:mr-4">
                <i class="fas fa-dove text-lg sm:text-xl"></i>
            </div>
            <h2 class="font-hedvig text-3xl sm:text-4xl text-navy">Traditional Funeral</h2>
        </div>
    </div>
    
    <!-- Paragraph - Centered -->
    <div class="flex justify-center mb-6">
        <p class="text-dark max-w-4xl text-center text-sm sm:text-base">Our traditional funeral services honor your loved one with dignity and respect while providing support for family and friends. Each service can be customized to reflect the unique life being celebrated.</p>
    </div>
    
    <!-- Packages Carousel -->
    <div class="max-w-6xl mx-auto relative">
        <!-- Carousel Container -->
        <div class="overflow-hidden relative">
            <div id="carousel-container" class="flex transition-transform duration-500 ease-in-out">
                <!-- Package 1: Legacy Tribute -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                    <div class="bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="700000" data-service="traditional" data-name="Legacy Tribute" data-image="image/700.jpg">
                        <div class="h-10 sm:h-12 bg-navy flex items-center justify-center">
                            <h4 class="text-white font-hedvig text-lg sm:text-xl">Legacy Tribute</h4>
                            <div class="absolute top-0 right-0 w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center">
                                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                            </div>
                        </div>
                        <div class="p-4 sm:p-6 flex flex-col flex-grow">
                            <div class="mb-3 sm:mb-4 flex justify-center">
                                <img src="image/700.jpg" alt="Legacy Tribute" class="w-full h-40 sm:h-48 object-cover rounded-lg">
                            </div>
                            <div class="text-center mb-4 sm:mb-6">
                                <span class="text-2xl sm:text-3xl font-hedvig text-navy">₱700,000</span>
                            </div>
                            <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 flex-grow text-sm sm:text-base">
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Engraved Metal</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">3 Floral Replacement</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Transportation</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Embalming</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Catering</span>
                                </li>
                            </ul>
                            <a href="Landing_Page/register.php" class="block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                                Select Package
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Package 2: Eternal Remembrance -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                    <div class="bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="300000" data-service="traditional" data-name="Eternal Remembrance" data-image="image/300.jpg">
                        <div class="h-10 sm:h-12 bg-navy flex items-center justify-center">
                            <h4 class="text-white font-hedvig text-lg sm:text-xl">Eternal Remembrance</h4>
                            <div class="absolute top-0 right-0 w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center">
                                <div class="w-12 h-12 sm:w-16 sm:h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                            </div>
                        </div>
                        <div class="p-4 sm:p-6 flex flex-col flex-grow">
                            <div class="mb-3 sm:mb-4 flex justify-center">
                                <img src="image/300.jpg" alt="Eternal Remembrance" class="w-full h-40 sm:h-48 object-cover rounded-lg">
                            </div>
                            <div class="text-center mb-4 sm:mb-6">
                                <span class="text-2xl sm:text-3xl font-hedvig text-navy">₱300,000</span>
                            </div>
                            <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 flex-grow text-sm sm:text-base">
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Semi Imported</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">2 Floral Replacement</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Transportation</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Embalming</span>
                                </li>
                            </ul>
                            <a href="Landing_Page/register.php" class="block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                                Select Package
                            </a>
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
                                <img src="Landing_Page/Landing_images/logo.png" alt="Custom Memorial" class="w-full h-40 sm:h-48 object-cover rounded-lg">
                            </div>
                            <div class="text-center mb-4 sm:mb-6">
                                <span class="text-2xl sm:text-3xl font-hedvig text-navy">Starting at ₱54,000</span>
                                <p class="text-xs sm:text-sm text-gray-600 mt-1">Final price depends on selections</p>
                            </div>
                            <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 flex-grow text-sm sm:text-base">
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark"><strong>Choose your casket</strong> from our selection</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark"><strong>Select number of flower replacements</strong> (1-3)</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-sliders-h mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark"><strong>Add catering options</strong> if desired</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                                    <span class="text-dark">Core funeral services included</span>
                                </li>
                            </ul>
                            <a href="Landing_Page/register.php" class="block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                                Customize Package
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- View All Packages Blur Card -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-2 sm:px-4">
                    <a href="Landing_Page/register.php" class="block h-full">
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

<section id="lifeplan" class="px-4 sm:px-0">
    <div class="flex justify-center mb-6 sm:mb-8">
        <div class="flex items-center">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-yellow-600/90 flex items-center justify-center text-white mr-3 sm:mr-4">
                <i class="fas fa-clipboard-list text-xl sm:text-2xl"></i>
            </div>
            <h2 class="font-hedvig text-3xl sm:text-4xl text-navy">Life Plan</h2>
        </div>
    </div>
    
    <!-- Paragraph - Centered -->  
    <div class="flex justify-center mb-6">  
        <p class="text-dark max-w-4xl text-center text-sm sm:text-base">We provide flexible and compassionate life plan options, including a 5-year installment payment plan with 0% interest for your chosen traditional package, ensuring affordability without compromising dignity or care.</p>  
    </div>  
    
    <div class="max-w-xl mx-auto">
        <div class="package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="Total Package Price ÷ 60 Months" data-service="lifeplan" data-name="Life Plan">
            <div class="h-10 sm:h-12 bg-navy flex items-center justify-center">
                <h4 class="text-white font-hedvig text-lg sm:text-xl">Flexible Payment Plan</h4>
                <div class="absolute top-0 right-0 w-12 h-12 sm:w-16 sm:h-16 flex items-center justify-center">
                    <div class="w-12 h-12 sm:w-16 sm:h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                </div>
            </div>
            <div class="p-4 sm:p-6 flex flex-col flex-grow">
                <div class="text-center mb-4 sm:mb-6">
                    <span class="text-2xl sm:text-3xl font-hedvig text-navy">5-Year Payment Option</span>
                    <p class="text-dark mt-1 sm:mt-2 text-sm sm:text-base">Total Package Price by 60 Months</p>
                </div>
                <ul class="space-y-2 sm:space-y-3 mb-4 sm:mb-6 flex-grow text-sm sm:text-base">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                        <span class="text-dark">0% Interest Guaranteed</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                        <span class="text-dark">Fixed Monthly Payment</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                        <span class="text-dark">No Hidden Fees</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                        <span class="text-dark">Spread Cost Over 5 Years</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1 flex-shrink-0"></i>
                        <span class="text-dark">Equal Monthly Installments</span>
                    </li>
                </ul>
                <a href="Landing_Page/register.php" class="block w-full mt-4 sm:mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 sm:py-3 rounded-lg shadow-md transition-all duration-300 text-center text-sm sm:text-base">
                    Select Package
                </a>
            </div>
        </div>
    </div>
</section>
</div></div>

<!-- FAQ Section -->
<div class="container mx-auto px-6 py-12 max-w-4xl">
        <!-- Section Header -->
        <div class="text-center mb-16">
            <h2 class="text-5xl font-hedvig text-navy mb-4">We're Here to Help</h2>
            <p class="text-dark text-lg max-w-3xl mx-auto">Find answers to the most common questions about VJay Relova Funeral Services and our bereavement support.</p>
            <div class="w-20 h-1 bg-yellow-600 mx-auto mt-6"></div>
        </div>

<!-- FAQ Accordion -->
<div class="space-y-6">
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
                            <li>If the death occurs at home, call emergency services or the person's doctor.</li>
                            <li>If the death occurs at a hospital or care facility, staff will guide you through the initial procedures.</li>
                            <li>Once the death has been officially pronounced, contact us at VJay Relova Funeral Services.</li>
                        </ul>
                        <p class="mt-4">We recommend visiting us in person if possible, as discussing arrangements for your loved one is better done face-to-face, especially in such difficult moments.</p>
                        <p class="mt-4"><strong>Important:</strong> In cases of accident or disease-related deaths, there are additional processes involving SOCO (Scene of the Crime Operatives) that need to be followed before the burial can proceed. The body cannot be touched until a death certificate is issued to ensure there was no foul play.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 2 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">What services do you offer?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>At VJay Relova Funeral Services, we offer:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li><strong>Complete Funeral Packages:</strong> Including casket, curtains, lighting, water dispenser, tent, chairs, table, hearse, transportation, and embalming services</li>
                            <li><strong>Separate Services:</strong> Depending on your needs, we can provide individual services such as viewing chapel rental, casket only, or other specific requirements</li>
                            <li><strong>Chapel Rental:</strong> For families who wish to hold the wake at our facilities (₱6,000/day)</li>
                            <li><strong>lifeplan Planning:</strong> For those who wish to arrange their funeral services in advance</li>
                        </ul>
                        <p class="mt-4">Our owner, Virgillo Jay G. Relova, personally meets with each family to understand your specific needs and build a connection that ensures the highest quality of service during this difficult time.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 3 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">What is included in your funeral packages?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>Our complete funeral packages include:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li><strong>Casket:</strong> Various options including standard and premium imported brass caskets</li>
                            <li><strong>Flowers:</strong> For basic packages, flowers last until the end of service; for premium packages (₱100,000+), flowers are changed twice during the wake</li>
                            <li><strong>Viewing Equipment:</strong> Curtains, lights, water dispenser, tent, chairs, and tables</li>
                            <li><strong>Transportation:</strong> Hearse service and other necessary transportation</li>
                            <li><strong>Professional Services:</strong> Licensed embalmer, who cleans and prepares the body (process takes 2-3 hours)</li>
                            <li><strong>Setup:</strong> Complete preparation of the viewing area before bringing your loved one home</li>
                        </ul>
                        <p class="mt-4">We accommodate different religious and cultural practices, including Catholic and Muslim burial rites. Our staff will set up the viewing area first, then bring the prepared body to the location in the casket.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 4 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">What are your payment options?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>We offer flexible payment options to accommodate various financial situations:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li><strong>For packages ₱100,000 and above:</strong> 30% down payment required</li>
                            <li><strong>Installment options:</strong> Available without interest for those who need additional time to pay</li>
                            <li><strong>lifeplan plans:</strong> Available for those planning ahead</li>
                        </ul>
                        <p class="mt-4">We understand that some families may be waiting for SSS claims or other benefits. We offer short-term installment arrangements (typically 1-3 months) and may provide additional discounts for prompt payment.</p>
                        <p class="mt-2">For families experiencing financial difficulties, we're willing to work with you to find a solution that honors your loved one while respecting your budget.</p>
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
                        <p>Our funeral packages range from ₱25,000 for a complete basic package to ₱500,000 for premium services. For special requests and premium imported caskets, we've provided services up to ₱800,000-1,000,000.</p>
                        <p class="mt-2">Price factors include:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li>Quality and type of casket (imported brass caskets start at ₱350,000)</li>
                            <li>Flower arrangements and frequency of replacement</li>
                            <li>Duration of the wake and viewing</li>
                            <li>Additional services or special requests</li>
                        </ul>
                        <p class="mt-4">We offer automatic 20% discounts for PWDs and senior citizens. Additional discounts may be available based on your situation and negotiations. Unlike other funeral homes with fixed prices, we understand that each family's needs and circumstances are unique, and we're willing to work with you to provide the best service within your budget.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 6 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">How do you handle cremation services?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>We offer cremation services through trusted third-party partnerships with crematoriums. When you arrange cremation through us:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li>We handle all coordination with the crematorium</li>
                            <li>We can prioritize your preferred schedule, even on days when the crematorium is busy</li>
                            <li>You can make a single payment to us that covers both the wake and cremation services</li>
                            <li>Cremation typically happens on the last day of the wake</li>
                            <li>For cremation, we can provide rental caskets that are returned after the service</li>
                        </ul>
                        <p class="mt-4">Our staff will guide you through the entire process, ensuring a dignified and respectful transition from the wake to the final cremation service.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 7 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">Can you accommodate special requests during the funeral service?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>Yes, we pride ourselves on our flexibility and willingness to accommodate special requests:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li>We can extend the wake period, even up to 2 weeks if needed</li>
                            <li>For extended wakes, we provide re-embalming services and fresh flowers as required</li>
                            <li>We accommodate different religious and cultural practices</li>
                            <li>For additional items not included in your package, we can connect you with trusted suppliers</li>
                            <li>We have no limit on the number of services we can provide simultaneously—if our equipment is fully utilized, we arrange to rent additional items</li>
                        </ul>
                        <p class="mt-4">What sets VJay Relova Funeral Services apart is the personal connection between our owner and your family. We understand that small details matter greatly during this difficult time, and we're committed to being responsive and attentive to all your needs and requests.</p>
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
    <?php include 'faq-accordion.js' ?>

</body>
</html>