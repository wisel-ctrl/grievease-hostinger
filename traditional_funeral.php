
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Traditional Funeral Service</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hedvig+Letters+Serif:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="tailwind.js"></script>
    <style>
        .modal {
            transition: opacity 0.3s ease-in-out;
            pointer-events: none;
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

        .candlelight:hover {
            transform: scale(1.1);
            box-shadow: 0 0 30px rgba(212, 175, 55, 0.8);
        }
        html {
        scroll-behavior: smooth;
    }
    </style>
    <script>
        function toggleMenu() {
            document.getElementById('mobile-menu').classList.toggle('hidden');
        }

        // Enhanced carousel functionality with infinite loop
        document.addEventListener('DOMContentLoaded', function() {
            const partnerContainer = document.querySelector('#partner-carousel');
            const partners = document.querySelectorAll('.partner-logo');
            const totalPartners = partners.length;
            const visiblePartners = window.innerWidth < 768 ? 2 : 4;
            let position = 0;
            
            // Clone partners for infinite loop
            partners.forEach(partner => {
                const clone = partner.cloneNode(true);
                partnerContainer.appendChild(clone);
            });
            
            function moveCarousel() {
                position++;
                
                // Reset position smoothly for infinite loop
                if (position >= totalPartners) {
                    // Quick reset after transition completes
                    setTimeout(() => {
                        partnerContainer.style.transition = 'none';
                        position = 0;
                        partnerContainer.style.transform = `translateX(-${position * (100 / visiblePartners)}%)`;
                        // Re-enable transition after reset
                        setTimeout(() => {
                            partnerContainer.style.transition = 'transform 500ms ease-in-out';
                        }, 50);
                    }, 500);
                }
                
                partnerContainer.style.transform = `translateX(-${position * (100 / visiblePartners)}%)`;
            }
            
            // Center the carousel
            partnerContainer.style.display = 'flex';
            partnerContainer.style.justifyContent = 'center';
            
            setInterval(moveCarousel, 3000);
        });
    </script>
</head>
<body class="bg-cream overflow-x-hidden w-full max-w-full m-0 p-0 font-hedvig">
    <!-- Notification Toast (Hidden by default) -->
    <div id="notification" class="fixed top-0 right-0 m-4 p-4 bg-black text-white rounded shadow-lg z-50 hidden notification">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span id="notification-message">Notification message here</span>
        </div>
    </div>

    <?php include 'navbar.php' ?>

    <!-- Navigation Bar - Improved & Smaller -->

<!-- Traditional Funeral Services Main Content -->
<div class="bg-cream py-20">
    <div class="container mx-auto px-6 max-w-6xl">
        <!-- Hero Section -->
        <div class="relative rounded-2xl overflow-hidden mb-16 shadow-xl">
            <div class="h-64 bg-cover bg-center" style="background-image: url('Landing_Page/Landing_images/sampleImageLANG.jpg')">
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="absolute inset-0 flex flex-col justify-center items-center text-white p-8">
                    <h1 class="text-5xl md:text-6xl font-hedvig text-center mb-6">Traditional Funeral Services</h1>
                    <p class="text-xl max-w-2xl text-center">Honoring your loved one with dignity, respect, and time-honored traditions</p>
                </div>
            </div>
        </div>

        <!-- Introduction Section -->
        <div class="max-w-3xl mx-auto text-center mb-16">
            <h2 class="text-3xl font-hedvig text-navy mb-6">A Dignified Farewell</h2>
            <p class="text-lg text-dark">Our traditional funeral services honor your loved one with dignity and respect while providing support for family and friends. Each service can be customized to reflect the unique life being celebrated.</p>
            <div class="w-20 h-1 bg-yellow-600 mx-auto mt-8"></div>
        </div>

        <!-- Service Features -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mb-20">
            <!-- Feature 1 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 text-center">
                <div class="w-16 h-16 bg-yellow-600/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-users text-2xl text-yellow-600"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy mb-4">Viewing & Visitation</h3>
                <p class="text-dark text-sm">A private or public gathering that allows family and friends to pay their respects and offer condolences in a supportive environment.</p>
            </div>
            
            <!-- Feature 2 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 text-center">
                <div class="w-16 h-16 bg-yellow-600/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-church text-2xl text-yellow-600"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy mb-4">Funeral Ceremony</h3>
                <p class="text-dark text-sm">A formal service held at our chapel, a place of worship, or another meaningful location to honor and celebrate your loved one's life.</p>
            </div>
            
            <!-- Feature 3 -->
            <div class="bg-white rounded-xl p-8 shadow-lg hover:shadow-xl transition-all duration-300 text-center">
                <div class="w-16 h-16 bg-yellow-600/10 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-car text-2xl text-yellow-600"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy mb-4">Procession & Committal</h3>
                <p class="text-dark text-sm">A dignified journey to the final resting place, followed by a brief but meaningful graveside service for final farewells.</p>
            </div>
        </div>

        <!-- Traditional Funeral Services Section -->
<section id="traditional" class="scroll-mt-24 mb-6">
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
    <div class="flex justify-center mb-4">
        <p class="text-dark max-w-3xl text-center">Our traditional funeral services honor your loved one with dignity and respect while providing support for family and friends. Each service can be customized to reflect the unique life being celebrated.</p>
    </div>
    
    <!-- Packages Carousel -->
    <div class="max-w-6xl mx-auto relative">
        <!-- Carousel Container -->
        <div class="overflow-hidden relative">
            <div id="carousel-container" class="flex transition-transform duration-500 ease-in-out">
                <!-- Package 1: Legacy Tribute -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-4">
                    <div class="bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="700000" data-service="traditional" data-name="Legacy Tribute" data-image="image/700.jpg">
                        <div class="h-12 bg-navy flex items-center justify-center">
                            <h4 class="text-white font-hedvig text-xl">Legacy Tribute</h4>
                            <div class="absolute top-0 right-0 w-16 h-16 flex items-center justify-center">
                                <div class="w-16 h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                            </div>
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <div class="mb-4 flex justify-center">
                                <img src="image/700.jpg" alt="Legacy Tribute" class="w-full h-48 object-cover rounded-lg">
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
                            <a href="Landing_Page/register.php" class="block w-full mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 text-center">
                                Select Package
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Package 2: Eternal Remembrance -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-4">
                    <div class="bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="300000" data-service="traditional" data-name="Eternal Remembrance" data-image="image/300.jpg">
                        <div class="h-12 bg-navy flex items-center justify-center">
                            <h4 class="text-white font-hedvig text-xl">Eternal Remembrance</h4>
                            <div class="absolute top-0 right-0 w-16 h-16 flex items-center justify-center">
                                <div class="w-16 h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                            </div>
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <div class="mb-4 flex justify-center">
                                <img src="image/300.jpg" alt="Legacy Tribute" class="w-full h-48 object-cover rounded-lg">
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
                            <a href="Landing_Page/register.php" class="block w-full mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 text-center">
                                Select Package
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Package 3: Heritage Memorial -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-4">
                    <div class="bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col h-full" data-price="250000" data-service="traditional" data-name="Heritage Memorial" data-image="image/250.jpg">
                        <div class="h-12 bg-navy flex items-center justify-center">
                            <h4 class="text-white font-hedvig text-xl">Heritage Memorial</h4>
                            <div class="absolute top-0 right-0 w-16 h-16 flex items-center justify-center">
                                <div class="w-16 h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                            </div>
                        </div>
                        <div class="p-6 flex flex-col flex-grow">
                            <div class="mb-4 flex justify-center">
                                <img src="image/250.jpg" alt="Legacy Tribute" class="w-full h-48 object-cover rounded-lg">
                            </div>
                            <div class="text-center mb-6">
                                <span class="text-3xl font-hedvig text-navy">₱250,000</span>
                            </div>
                            <ul class="space-y-3 mb-6 flex-grow">
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">2 sets of flower arrangements</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Standard casket selection</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Standard viewing period</span>
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
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                                    <span class="text-dark">Tent, chairs, and table</span>
                                </li>
                            </ul>
                            <a href="Landing_Page/register.php" class="block w-full mt-6 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 text-center">
                                Select Package
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- View All Packages Blur Card -->
                <div class="package-card min-w-full md:min-w-[33.333%] px-4">
                    <a href="Landing_Page/register.php" class="block h-full">
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
        <button id="prev-btn" class="absolute left-0 top-1/2 transform -translate-y-1/2 -ml-4 bg-white w-10 h-10 rounded-full shadow-lg flex items-center justify-center border border-gray-200 z-20 hover:bg-gray-50 focus:outline-none">
            <i class="fas fa-chevron-left text-navy"></i>
        </button>
        <button id="next-btn" class="absolute right-0 top-1/2 transform -translate-y-1/2 -mr-4 bg-white w-10 h-10 rounded-full shadow-lg flex items-center justify-center border border-gray-200 z-20 hover:bg-gray-50 focus:outline-none">
            <i class="fas fa-chevron-right text-navy"></i>
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

    <!-- FAQ Section -->
    <div class="container mx-auto px-6  max-w-4xl">
        
    <!-- FAQ Section -->
<div class="container mx-auto px-6  max-w-4xl">
        <!-- Section Header -->
        <div class="text-center mb-16">
            <h2 class="text-5xl font-hedvig text-navy mb-4">We're Here to Help</h2>
            <p class="text-dark text-lg max-w-3xl mx-auto">Find answers to the most common questions about VJay Relova Funeral Services and our bereavement support.</p>
            <div class="w-20 h-1 bg-yellow-600 mx-auto mt-6"></div>
        </div>
        
        <!-- FAQ Accordion -->
        <div class="space-y-6 mb-12">
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
                            <li>If the death occurs at home, call emergency services (911) or the person's doctor.</li>
                            <li>If the death occurs at a hospital or care facility, staff will guide you through the initial procedures.</li>
                            <li>Once the death has been officially pronounced, contact us at GrievEase at our 24/7 number for immediate assistance.</li>
                        </ul>
                        <p class="mt-4">Our compassionate staff will guide you through the next steps, including transportation of your loved one to our facility and beginning the arrangement process.</p>
                        <p class="mt-4"><strong>Important:</strong> In cases of accident or disease-related deaths, there may be additional processes involving SOCO (Scene of the Crime Operatives) that need to be followed before the burial can proceed.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 2 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">How do I plan a meaningful funeral service?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>Planning a meaningful funeral service involves several considerations:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li>Reflect on your loved one's personality, interests, and wishes</li>
                            <li>Consider religious or cultural traditions that were important to them</li>
                            <li>Select meaningful music, readings, or other elements that celebrate their life</li>
                            <li>Decide on personalization options like photo displays, memory tables, or tribute videos</li>
                        </ul>
                        <p class="mt-4">At VJay Relova Funeral Services, we believe that the personal connection between our staff and your family is essential. Our owner will personally talk with you to understand your specific needs and requests. We'll guide you through each step, helping you create a service that truly honors your loved one's life and legacy.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 3 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">What is the difference between burial and cremation?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>The main differences between burial and cremation involve:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li><strong>Process:</strong> Burial preserves the body in a casket placed in the ground or a mausoleum, while cremation reduces the body to cremated remains through heat.</li>
                            <li><strong>Timeline:</strong> Burials typically occur within a week of death, while cremation offers more flexibility for memorial service timing.</li>
                            <li><strong>Memorialization:</strong> Burial provides a permanent gravesite to visit, while cremated remains can be kept in an urn, scattered, or placed in a columbarium.</li>
                            <li><strong>Cost:</strong> Cremation is generally less expensive than traditional burial due to fewer required elements.</li>
                        </ul>
                        <p class="mt-4">For cremation services, we partner with third-party crematoriums. The advantage of arranging cremation through us is that we can help prioritize your scheduling needs. We can arrange for cremation on a specific day of your choosing, even if there are others scheduled for cremation on the same day.</p>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Item 4 -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <button class="faq-question w-full text-left p-6 focus:outline-none">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-hedvig text-navy">What are the benefits of pre-planning a funeral?</h3>
                        <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
                    </div>
                </button>
                <div class="faq-answer px-6 pb-6 hidden">
                    <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
                        <p>Pre-planning a funeral offers several significant benefits:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li>Ensures your specific wishes are known and honored</li>
                            <li>Relieves your loved ones from making difficult decisions during a time of grief</li>
                            <li>Provides opportunity to make thoughtful, informed choices without time pressure</li>
                            <li>Allows you to handle financial arrangements in advance, potentially saving money</li>
                            <li>Creates peace of mind for you and your family</li>
                        </ul>
                        <p class="mt-4">We offer pre-need installment plans for those who wish to plan ahead. Some clients prefer our services over other pre-need companies because our packages are complete and customizable, with negotiable prices. We work with you to ensure everything you desire for your funeral service is included in your plan.</p>
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
                        <p>Our funeral packages range from ₱25,000 for a complete basic package to ₱500,000 for premium services. We've provided services up to ₱800,000-1,000,000 for clients with specific requirements.</p>
                        <p class="mt-2">A typical funeral package may include:</p>
                        <ul class="list-disc ml-6 mt-2 space-y-1">
                            <li>Casket (various options available, including imported brass caskets)</li>
                            <li>Flowers (replaced twice for premium packages)</li>
                            <li>Chapel rental with curtains and lighting</li>
                            <li>Embalming services with licensed professionals</li>
                            <li>Hearse and transportation</li>
                            <li>Viewing equipment (water dispenser, tent, chairs, tables)</li>
                        </ul>
                        <p class="mt-4">At VJay Relova Funeral Services, we're committed to transparency in pricing. Our packages are customizable, and prices are negotiable based on your needs. We offer automatic 20% discounts for PWDs and senior citizens, and we can discuss other discount options based on your situation.</p>
                        <p class="mt-2">For services over ₱100,000, we require a 30% down payment. For services under ₱100,000, payment is typically made before or on the day of burial.</p>
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
        });
    </script>

<!-- Footer -->
<?php include 'footer.php'?>

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
</body>
</html>
