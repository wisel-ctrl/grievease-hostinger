
<!DOCTYPE php>
<php lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - LifePlan</title>
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
        :root {
            --navbar-height: 64px; /* Define the height of the navbar */
            --section-spacing: 4rem; /* Standardized spacing between sections */
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
        .php {
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

 <!-- Cremation Services Main Content -->
<div class="bg-cream py-20">
    <div class="container mx-auto px-6 max-w-6xl">
        <!-- Hero Section -->
        <div class="relative rounded-2xl overflow-hidden mb-16 shadow-xl">
            <div class="h-64 bg-cover bg-center" style="background-image: url('Landing_Page/Landing_images/black-bg-image.jpg');">
                <div class="absolute inset-0 bg-black/50"></div>
                <div class="absolute inset-0 flex flex-col justify-center items-center text-white p-8">
                    <h1 class="text-5xl font-hedvig text-center mb-6">Lifeplan</h1>
                    <p class="text-lg max-w-3xl text-center">Taking the time to plan now means peace of mind for both you and your loved ones later.</p>
                </div>
            </div>
        </div>

        <!-- Benefits Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
            <!-- Benefit 1 -->
            <div class="bg-white rounded-lg shadow-lg p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="bg-yellow-600/10 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <i class="fas fa-heart text-yellow-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy text-center mb-4">Emotional Peace</h3>
                <p class="text-dark text-sm text-center">Relieve your loved ones of the burden of making difficult decisions during their time of grief.</p>
            </div>
            
            <!-- Benefit 2 -->
            <div class="bg-white rounded-lg shadow-lg p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="bg-yellow-600/10 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <i class="fas fa-coins text-yellow-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy text-center mb-4">Financial Security</h3>
                <p class="text-dark text-sm text-center">Lock in today's prices and protect your family from future inflation and unexpected costs.</p>
            </div>
            
            <!-- Benefit 3 -->
            <div class="bg-white rounded-lg shadow-lg p-8 hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
                <div class="bg-yellow-600/10 w-16 h-16 rounded-full flex items-center justify-center mb-6 mx-auto">
                    <i class="fas fa-clipboard-check text-yellow-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-hedvig text-navy text-center mb-4">Personal Wishes</h3>
                <p class="text-dark text-sm text-center">Ensure your life is celebrated exactly how you envision, with every detail respected and honored.</p>
            </div>
        </div>
        
        <section id="lifeplan" class="mb-8 mt-16">
    <div class="flex justify-center mb-8">
        <div class="flex items-center">
            <div class="w-12 h-12 rounded-full bg-yellow-600/90 flex items-center justify-center text-white mr-4">
                <i class="fas fa-clipboard-list text-2xl"></i>
            </div>
            <h2 class="font-hedvig text-4xl text-navy">Life Plan</h2>
        </div>
    </div>

    <!-- Paragraph - Centered -->  
    <div class="flex justify-center mb-8">  
        <p class="text-dark max-w-3xl text-center">We provide flexible and compassionate life plan options, including a 5-year installment payment plan with 0% interest for your chosen traditional package, ensuring affordability without compromising dignity or care.</p>  
    </div>  
    
    <div class="max-w-6xl mx-auto">
        <div class="package-card bg-white rounded-[20px] shadow-lg overflow-hidden relative group hover:shadow-xl transition-all duration-300 flex flex-col max-h-[480px]" data-price="35000" data-service="cremate" data-name="Direct Cremation">
            <div class="h-12 bg-navy flex items-center justify-center">
                <h4 class="text-white font-hedvig text-xl">Flexible Payment Plan</h4>
                <div class="absolute top-0 right-0 w-16 h-16 flex items-center justify-center">
                    <div class="w-16 h-16 bg-yellow-600/90 rotate-45 transform origin-bottom-left"></div>
                </div>
            </div>
            <div class="p-6 flex flex-col">
                <div class="text-center mb-4">
                    <span class="text-3xl font-hedvig text-navy">5-Year Payment Option</span>
                    <p class="text-dark mt-2">Total Package Price ÷ 60 Months</p>
                </div>
                <ul class="space-y-2 mb-4">
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">0% Interest Guaranteed</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">Fixed Monthly Payment</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">No Hidden Fees</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">Spread Cost Over 5 Years</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">Equal Monthly Installments</span>
                    </li>
                    <li class="flex items-start">
                        <i class="fas fa-check-circle mr-2 text-yellow-600 mt-1"></i>
                        <span class="text-dark">Flexible Payment Option</span>
                    </li>
                </ul>
                <a href="Landing_Page/register.php" class="block w-full mt-4 bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg shadow-md transition-all duration-300 text-center">
                    Select Package
                </a>
            </div>
        </div>
    </div>
</section>
        
        <!-- How It Works Section - Improved Timeline Design -->
        <div class="mb-8 mt-16">
            <div class="text-center mb-12">
                <h3 class="text-5xl font-hedvig text-navy mb-4">How Life Planning Works</h3>
                <p class="text-dark max-w-3xl mx-auto">Our simple process guides you through each step of creating your personalized life plan.</p>
            </div>
            
            <!-- Timeline Container -->
            <div class="relative mx-auto px-4">
                <!-- Connecting Line - Vertical on mobile, Horizontal on larger screens -->
                <div class="hidden md:block absolute top-1/2 left-0 right-0 h-1 bg-yellow-600 -translate-y-1/2 z-0"></div>
                <div class="md:hidden absolute left-1/2 top-0 bottom-0 w-1 bg-yellow-600 -translate-x-1/2 z-0"></div>
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12 md:gap-8 relative z-10">
                    <!-- Step 1 -->
                    <div class="flex flex-col md:items-center text-center relative">
                        <!-- Connector for mobile view -->
                        <div class="md:hidden absolute top-0 bottom-0 left-0 w-16 flex items-center justify-center">
                            <div class="h-full w-px bg-yellow-600 absolute left-8"></div>
                        </div>
                        
                        <!-- Step Number Circle -->
                        <div class="w-16 h-16 bg-white border-2 border-yellow-600 rounded-full flex items-center justify-center mb-6 shadow-lg z-10 md:mx-auto">
                            <span class="text-2xl font-hedvig text-navy">1</span>
                        </div>
                        
                        <!-- Content Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1 flex-1">
                            <h4 class="text-xl font-hedvig text-navy mb-3">Consultation</h4>
                            <p class="text-dark text-sm">Meet with our planning specialists to discuss your wishes and preferences. We'll guide you through available options.</p>
                            <div class="mt-4 text-yellow-600">
                                <i class="fas fa-handshake text-3xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2 -->
                    <div class="flex flex-col md:items-center text-center relative">
                        <!-- Connector for mobile view -->
                        <div class="md:hidden absolute top-0 bottom-0 left-0 w-16 flex items-center justify-center">
                            <div class="h-full w-px bg-yellow-600 absolute left-8"></div>
                        </div>
                        
                        <!-- Step Number Circle -->
                        <div class="w-16 h-16 bg-white border-2 border-yellow-600 rounded-full flex items-center justify-center mb-6 shadow-lg z-10 md:mx-auto">
                            <span class="text-2xl font-hedvig text-navy">2</span>
                        </div>
                        
                        <!-- Content Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1 flex-1">
                            <h4 class="text-xl font-hedvig text-navy mb-3">Personalization</h4>
                            <p class="text-dark text-sm">Customize your plan with specific selections and personal touches that reflect your wishes and values.</p>
                            <div class="mt-4 text-yellow-600">
                                <i class="fas fa-paint-brush text-3xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 3 -->
                    <div class="flex flex-col md:items-center text-center relative">
                        <!-- Connector for mobile view -->
                        <div class="md:hidden absolute top-0 bottom-0 left-0 w-16 flex items-center justify-center">
                            <div class="h-full w-px bg-yellow-600 absolute left-8"></div>
                        </div>
                        
                        <!-- Step Number Circle -->
                        <div class="w-16 h-16 bg-white border-2 border-yellow-600 rounded-full flex items-center justify-center mb-6 shadow-lg z-10 md:mx-auto">
                            <span class="text-2xl font-hedvig text-navy">3</span>
                        </div>
                        
                        <!-- Content Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1 flex-1">
                            <h4 class="text-xl font-hedvig text-navy mb-3">Documentation</h4>
                            <p class="text-dark text-sm">Receive comprehensive documentation of your arrangements and preferences for your records.</p>
                            <div class="mt-4 text-yellow-600">
                                <i class="fas fa-file-signature text-3xl"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 4 -->
                    <div class="flex flex-col md:items-center text-center relative">
                        <!-- Connector for mobile view -->
                        <div class="md:hidden absolute top-0 bottom-0 left-0 w-16 flex items-center justify-center">
                            <div class="h-full w-px bg-yellow-600 absolute left-8"></div>
                        </div>
                        
                        <!-- Step Number Circle -->
                        <div class="w-16 h-16 bg-white border-2 border-yellow-600 rounded-full flex items-center justify-center mb-6 shadow-lg z-10 md:mx-auto">
                            <span class="text-2xl font-hedvig text-navy">4</span>
                        </div>
                        
                        <!-- Content Card -->
                        <div class="bg-white rounded-lg shadow-md p-6 transform transition-all duration-300 hover:shadow-xl hover:-translate-y-1 flex-1">
                            <h4 class="text-xl font-hedvig text-navy mb-3">Peace of Mind</h4>
                            <p class="text-dark text-sm">Rest assured knowing your wishes will be honored and your loved ones protected from difficult decisions.</p>
                            <div class="mt-4 text-yellow-600">
                                <i class="fas fa-heart text-3xl"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
<div class="container mx-auto px-6 py-12 max-w-3xl">
        <!-- Section Header -->
        <div class="text-center mb-16">
            <h2 class="text-5xl font-hedvig text-navy mb-4">We're Here to Help</h2>
            <p class="text-dark text-lg max-w-2xl mx-auto">Find answers to the most common questions about VJay Relova Funeral Services and our bereavement support.</p>
            <div class="w-20 h-1 bg-yellow-600 mx-auto mt-6"></div>
        </div>

<!-- FAQ Accordion -->
<div class="space-y-6">
            <!-- FAQ Item 1 -->
            <!-- FAQ Item on Pre-need Planning -->
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <button class="faq-question w-full text-left p-6 focus:outline-none">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-hedvig text-navy">Do you offer lifeplans?</h3>
            <i class="fas fa-chevron-down text-yellow-600 transition-transform duration-300"></i>
        </div>
    </button>
    <div class="faq-answer px-6 pb-6 hidden">
        <div class="border-t border-gray-200 pt-4 mt-2 text-dark">
            <p>Yes, we offer lifeplans for those who wish to arrange their funeral services in advance. Our pre-need plans offer several advantages:</p>
            <ul class="list-disc ml-6 mt-2 space-y-1">
                <li>Complete packages with all necessary services included</li>
                <li>Flexible, negotiable pricing unlike the fixed prices of other pre-need providers</li>
                <li>Customizable options to meet your specific preferences</li>
                <li>No hidden charges for special requests</li>
            </ul>
            <p class="mt-4">Many clients choose our pre-need plans over other providers because we provide more comprehensive packages without charging extra for additional requests. Our personalized approach ensures that all your wishes are honored exactly as you specify.</p>
            <p class="mt-2">To discuss pre-need planning options, we recommend scheduling an in-person consultation with our owner, Virgillo Jay G. Relova, who personally meets with each client to understand their specific needs and build a connection that ensures the highest quality of service.</p>
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

                
                <!-- Testimonials Section -->
                <div class="bg-navy/5 py-12 px-6 rounded-xl mb-16">
                    <h3 class="text-3xl font-hedvig text-navy text-center mb-12">What Our Clients Say</h3>
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
                            <p class="text-dark italic mb-4">Planning ahead with GrievEase was one of the best decisions I've made. The team was compassionate and patient, guiding me through every option. I now have peace of mind knowing my family won't face difficult decisions later.</p>
                            <p class="font-hedvig text-navy">- Maria Reyes, 67</p>
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
                            <p class="text-dark italic mb-4">After experiencing the stress of arranging a funeral for my father, I decided to plan ahead for myself. The Premium Plan offered exactly what I wanted, and the financial arrangements were clear and secure.</p>
                            <p class="font-hedvig text-navy">- Antonio Lim, 58</p>
                        </div>
                    </div>
                </div>
    </div>
    </div>
<!-- Footer -->
<?php include 'footer.php'?>

<!-- Add this to your index.php before the closing body tag -->

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
</body>
</php>