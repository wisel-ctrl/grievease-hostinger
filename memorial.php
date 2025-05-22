<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GrievEase - Memorials</title>
    <?php include 'faviconLogo.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Hedvig+Letters+Serif:wght@400;500&display=swap" rel="stylesheet">
    <script src="tailwind.js"></script>
    
    <!-- Facebook Meta Tags -->
    <meta property="og:url" content="https://grievease.com/memorial.php" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="GrievEase - Memorial Dedications" />
    <meta property="og:description" content="Honor and remember loved ones with virtual candle dedications" />
    <meta property="og:image" content="https://grievease.com/path/to/your/image.jpg" />
    
    <!-- Facebook SDK -->
    <script>
    window.fbAsyncInit = function() {
        FB.init({
            appId: 'your-app-id', // Replace with your Facebook App ID
            xfbml: true,
            version: 'v18.0'
        });
    };

    (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s);
        js.id = id;
        js.src = "https://connect.facebook.net/en_US/sdk.js";
        fjs.parentNode.insertBefore(js, fjs);
    }(document, 'script', 'facebook-jssdk'));
    </script>
    
    <style>
        .text-shadow-sm {
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }
        
        .text-shadow-md {
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .text-shadow-lg {
            text-shadow: 3px 3px 6px rgba(0, 0, 0, 0.7);
        }
        
        .candle-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .candle-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -10px rgba(0, 0, 0, 0.2);
        }
        :root {
            --navbar-height: 64px; /* Define the height of the navbar */
            --section-spacing: 4rem; /* Standardized spacing between sections */
        }
        
        .animated-entry {
            opacity: 0;
            transform: translateY(10px);
            animation: fadeIn 0.5s ease-in forwards;
        }
        
        .dedication-delay-1 { animation-delay: 0.1s; }
        .dedication-delay-2 { animation-delay: 0.2s; }
        .dedication-delay-3 { animation-delay: 0.3s; }
        .dedication-delay-4 { animation-delay: 0.4s; }
        .dedication-delay-5 { animation-delay: 0.5s; }
        .dedication-delay-6 { animation-delay: 0.6s; }

        @keyframes flicker {
            0%, 100% { transform: scale(1); opacity: 1; }
            25% { transform: scale(1.1, 0.9); opacity: 0.9; }
            50% { transform: scale(0.95, 1.05); opacity: 1; }
            75% { transform: scale(1.05, 0.95); opacity: 0.9; }
        }

        @keyframes flame {
            0% { transform: translateX(-50%) scaleY(1); }
            50% { transform: translateX(-50%) scaleY(1.1); }
            100% { transform: translateX(-50%) scaleY(1); }
        }

        @keyframes glow {
            0% { opacity: 0.7; }
            50% { opacity: 0.9; }
            100% { opacity: 0.7; }
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

.from-yellow-400 { --tw-gradient-from: #facc15; }
.to-yellow-600 { --tw-gradient-to: #ca8a04; }
.from-yellow-500 { --tw-gradient-from: #eab308; }
.to-yellow-700 { --tw-gradient-to: #a16207; }
.from-white { --tw-gradient-from: #ffffff; }
.to-gray-100 { --tw-gradient-to: #f3f4f6; }
.to-gray-300 { --tw-gradient-to: #d1d5db; }
    </style>
    <script>
        // Function to toggle the mobile menu
function toggleMenu() {
    const mobileMenu = document.getElementById('mobile-menu');
    mobileMenu.classList.toggle('hidden');
}
    </script>
</head>
<body class="font-hedvig text-navy antialiased">
<?php include 'navbar.php' ?>
    <!-- Semi-transparent overlay -->
    <div class="fixed inset-0 z-[-1] bg-black bg-opacity-70"></div>
    
    <!-- Background image -->
    <div class="fixed inset-0 z-[-2] bg-[url('Landing_page/Landing_images/black-bg-image.jpg')] bg-cover bg-center bg-no-repeat" style="background-image: url('Landing_Page/Landing_images/black-bg-image.jpg');"></div>
    

    <main class="w-full max-w-7xl mx-auto px-4 py-8 pt-20  mt-[var(--navbar-height)]" >
        <!-- Memorial Header -->
         
        <div class="text-center mb-12">
            <h1 class="font-hedvig text-5xl text-white text-shadow-lg mb-4">Memorial Dedications</h1>
            <p class="text-white/80 max-w-3xl mx-auto text-shadow-sm">In remembrance of those we have loved and lost. Each candle represents a dedication made in memory of someone special.</p>
        </div>

        
        <!-- Dedication Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="dedication-grid">
            <!-- These will be populated by JavaScript -->
        </div>
    </main>
    
    <!-- Notification Toast -->
    <div id="notification" class="fixed bottom-4 right-4 bg-gray-900 text-white rounded-lg shadow-xl px-4 py-3 flex items-center hidden z-50 transform transition-all duration-500">
        <div class="text-green-400 mr-3">
            <i class="fas fa-check-circle text-lg"></i>
        </div>
        <div>
            <p id="notification-message" class="font-medium text-sm"></p>
        </div>
    </div>

    <!-- Virtual Candle Modal -->
<div id="candle-modal" class="fixed inset-0 bg-black/90 flex items-center justify-center z-[100] opacity-0 pointer-events-none transition-opacity duration-500">
    <div class="relative w-full max-w-4xl mx-2 sm:mx-4 bg-black rounded-xl overflow-hidden shadow-2xl max-h-[90vh] overflow-y-auto">
        <!-- Close Button -->
        <button id="close-candle" class="absolute top-2 right-2 text-white/70 hover:text-white z-10 p-2">
            <i class="fas fa-times"></i>
        </button>
        
        <!-- Responsive Layout - Stacked on mobile, side-by-side on larger screens -->
        <div class="flex flex-col md:flex-row">
            <!-- Candle Section - Full width on mobile -->
            <div class="w-full md:w-1/3 p-3 sm:p-4 flex flex-col items-center justify-center bg-gradient-to-b from-black to-gray-900">
                <h3 class="text-lg sm:text-xl font-hedvig text-white mb-2 text-center">Light a Virtual Candle</h3>
                
                <!-- Candle Animation Container - Smaller on mobile -->
<div class="relative w-full h-32 sm:h-40 mb-2 sm:mb-3 flex items-center justify-center">
    <!-- Candle -->
    <div id="candle" class="relative w-12 sm:w-16">
        <!-- Updated Wick with Flame -->
        <div class="relative w-1 h-3 sm:h-4 bg-gray-700 mx-auto rounded-t-lg">
            <!-- Outer Flame (hidden initially) -->
            <div id="flame" class="hidden">
                <!-- Outer Flame -->
                <div class="absolute left-1/2 top-[-18px] transform -translate-x-1/2 w-4 h-8 bg-yellow-600/80 rounded-full blur-sm animate-flame"></div>
                
                <!-- Inner Flame -->
                <div class="absolute left-1/2 top-[-15px] transform -translate-x-1/2 w-2 h-6 bg-white/90 rounded-full blur-[2px] animate-flame"></div>
            </div>
        </div>
        
        <!-- Candle Body - Smaller on mobile -->
        <div class="w-10 sm:w-12 h-20 sm:h-24 bg-gradient-to-b from-cream to-white mx-auto rounded-t-lg"></div>
        
        <!-- Candle Base -->
        <div class="w-12 sm:w-16 h-2.5 sm:h-3 bg-gradient-to-b from-cream to-yellow-600/20 mx-auto rounded-b-lg"></div>
    </div>
    
    <!-- Reflection/Glow -->
    <div id="candle-glow" class="absolute bottom-2 w-36 sm:w-48 h-6 sm:h-8 bg-yellow-600/0 rounded-full blur-xl transition-all duration-1000"></div>
</div>
                
                <!-- Light Button -->
                <button id="light-button" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 sm:px-6 py-2 rounded-lg shadow-lg transition-all duration-300 text-sm w-full max-w-xs">
                    Light Candle
                </button>
            </div>
            
            <!-- Form Section - Full width on mobile -->
            <div class="w-full md:w-2/3 p-3 sm:p-4">
                <!-- Dedication Form -->
                <div id="dedication-form" class="hidden">
                    <form class="space-y-2 sm:space-y-3">
                        <!-- Responsive grid - Single column on small screens -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
                            <div>
                                <label for="in-memory-of" class="block text-white/90 text-xs sm:text-sm mb-1">In Memory Of</label>
                                <input type="text" id="in-memory-of" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-2 sm:px-3 py-1.5 sm:py-2 focus:outline-none focus:ring-2 focus:ring-yellow-600/50 text-sm" placeholder="Name of loved one">
                            </div>
                            
                            <div>
                                <label for="dedicated-by" class="block text-white/90 text-xs sm:text-sm mb-1">Dedicated By</label>
                                <input type="text" id="dedicated-by" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-2 sm:px-3 py-1.5 sm:py-2 focus:outline-none focus:ring-2 focus:ring-yellow-600/50 text-sm" placeholder="Your name">
                            </div>
                        </div>
                        
                        <div>
                            <label for="dedication-message" class="block text-white/90 text-xs sm:text-sm mb-1">Dedication Message</label>
                            <textarea id="dedication-message" rows="2" class="w-full bg-gray-800 border border-gray-700 text-white rounded-lg px-2 sm:px-3 py-1.5 sm:py-2 focus:outline-none focus:ring-2 focus:ring-yellow-600/50 text-sm" placeholder="Share your thoughts or memories..."></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-white/90 text-xs sm:text-sm mb-1">Candle Type</label>
                            <div class="grid grid-cols-3 gap-1 sm:gap-2">
                                <div class="relative">
                                    <input type="radio" name="candle-type" id="candle-white" value="white" class="absolute opacity-0 w-full h-full cursor-pointer peer">
                                    <label for="candle-white" class="block text-center p-1 sm:p-2 border border-gray-700 rounded-lg text-xs text-white/90 peer-checked:bg-yellow-600/30 peer-checked:border-yellow-600 transition-all">White</label>
                                </div>
                                <div class="relative">
                                    <input type="radio" name="candle-type" id="candle-cream" value="cream" class="absolute opacity-0 w-full h-full cursor-pointer peer" checked>
                                    <label for="candle-cream" class="block text-center p-1 sm:p-2 border border-gray-700 rounded-lg text-xs text-white/90 peer-checked:bg-yellow-600/30 peer-checked:border-yellow-600 transition-all">Cream</label>
                                </div>
                                <div class="relative">
                                    <input type="radio" name="candle-type" id="candle-gold" value="gold" class="absolute opacity-0 w-full h-full cursor-pointer peer">
                                    <label for="candle-gold" class="block text-center p-1 sm:p-2 border border-gray-700 rounded-lg text-xs text-white/90 peer-checked:bg-yellow-600/30 peer-checked:border-yellow-600 transition-all">Gold</label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" id="submit-dedication" class="w-full bg-yellow-600 hover:bg-yellow-700 text-white py-1.5 sm:py-2 rounded-lg shadow transition-colors text-sm">Submit Dedication</button>
                    </form>
                </div>

                <!-- Dedication Confirmation -->
                <div id="dedication-confirmation" class="hidden">
                    <div class="bg-yellow-600/20 border border-yellow-600/50 rounded-lg p-2 sm:p-3 mb-2 sm:mb-3">
                        <div class="text-center">
                            <i class="fas fa-heart text-yellow-600 text-base sm:text-lg mb-1"></i>
                            <p class="text-white text-xs sm:text-sm">Your dedication has been submitted.</p>
                            <p class="text-white/80 text-xs mt-1">Thank you for honoring your loved one's memory.</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 sm:gap-3">
                        <button id="new-dedication" class="bg-gray-800 hover:bg-gray-700 text-white py-1.5 sm:py-2 rounded-lg shadow transition-colors text-xs sm:text-sm">Create Another</button>
                        <button id="view-dedications" class="bg-transparent hover:bg-gray-800/50 text-white/80 hover:text-white py-1.5 sm:py-2 rounded-lg transition-colors text-xs sm:text-sm">View All</button>
                        <button id="share-facebook" class="bg-[#1877F2] hover:bg-[#0d6efd] text-white py-1.5 sm:py-2 rounded-lg shadow transition-colors text-xs sm:text-sm flex items-center justify-center gap-2">
                            <i class="fab fa-facebook-f"></i>
                            <span>Share</span>
                        </button>
                    </div>
                </div>
                
                <!-- Initial State Message -->
                <div id="initial-message" class="text-center py-4 sm:py-8">
                    <p class="text-white/80 text-xs sm:text-sm mb-3 sm:mb-6">Honor the memory of your loved one by lighting a virtual candle.</p>
                    <p class="text-white/60 text-xs">Click the "Light Candle" button to begin your dedication.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Floating Candlelight Button -->
<div class="candlelight" id="light-candle">
    🕯️
</div>

    <!-- JavaScript -->
    <script>
        // Modal Control
const candleModal = document.getElementById('candle-modal');
const lightCandleBtn = document.getElementById('light-candle');
const closeCandleBtn = document.getElementById('close-candle');

// Candle Elements
const flame = document.getElementById('flame');
const candleGlow = document.getElementById('candle-glow');
const lightButton = document.getElementById('light-button');
const dedicationForm = document.getElementById('dedication-form');
const dedicationConfirmation = document.getElementById('dedication-confirmation');

// Form submission handling
const submitDedication = document.getElementById('submit-dedication');
const newDedication = document.getElementById('new-dedication');
const viewDedications = document.getElementById('view-dedications');
const dedicationGrid = document.getElementById('dedication-grid'); // Declare dedicationGrid here

// Open Modal
lightCandleBtn.addEventListener('click', () => {
    candleModal.classList.remove('opacity-0', 'pointer-events-none');
});

// Close Modal
closeCandleBtn.addEventListener('click', () => {
    candleModal.classList.add('opacity-0', 'pointer-events-none');
    // Reset candle state when modal is closed
    setTimeout(() => {
        flame.classList.add('hidden');
        candleGlow.classList.remove('bg-yellow-600/30');
        candleGlow.classList.add('bg-yellow-600/0');
        lightButton.textContent = 'Light Candle';
        lightButton.classList.remove('bg-gray-700');
        lightButton.classList.add('bg-yellow-600');
        dedicationForm.classList.add('hidden');
        dedicationConfirmation.classList.add('hidden');
    }, 500);
});

// Light Candle Effect
lightButton.addEventListener('click', () => {
    if (flame.classList.contains('hidden')) {
        // Light the candle
        flame.classList.remove('hidden');
        candleGlow.classList.remove('bg-yellow-600/0');
        candleGlow.classList.add('bg-yellow-600/30');
        lightButton.textContent = 'Add Dedication';
        
        // Show dedication form after delay
        setTimeout(() => {
            dedicationForm.classList.remove('hidden');
        }, 1000);
    } else {
        // If already lit, show the form
        dedicationForm.classList.remove('hidden');
    }
});

// Candle color change functionality
// Candle color change functionality for the virtual candle modal
const candleTypeRadios = document.querySelectorAll('input[name="candle-type"]');
const modalCandleBody = document.querySelector('#candle .w-12.sm\\:w-16'); // Target the candle container
const modalCandleWax = modalCandleBody.querySelector('div:nth-child(2)'); // Candle wax element
const modalCandleBase = modalCandleBody.querySelector('div:nth-child(3)'); // Candle base element

// Color classes for each candle type
const candleColors = {
    white: {
        wax: 'bg-gradient-to-b from-white to-gray-100',
        base: 'bg-gradient-to-b from-white to-gray-300'
    },
    cream: {
        wax: 'bg-gradient-to-b from-cream to-white',
        base: 'bg-gradient-to-b from-cream to-yellow-600/20'
    },
    gold: {
        wax: 'bg-gradient-to-b from-yellow-400 to-yellow-600',
        base: 'bg-gradient-to-b from-yellow-500 to-yellow-700'
    }
};

// Set initial candle color based on default selection
function setInitialCandleColor() {
    const selectedRadio = document.querySelector('input[name="candle-type"]:checked');
    if (selectedRadio) {
        updateModalCandleColor(selectedRadio.value);
    }
}

// Validation functions
function validateNameInput(input) {
    // Remove any non-letter characters (including numbers and special chars)
    input.value = input.value.replace(/[^a-zA-Z\s]/g, '');
    
    // Prevent multiple consecutive spaces
    input.value = input.value.replace(/\s{2,}/g, ' ');
    
    // Don't allow space as first character or unless there are already 2 letters
    if (input.value.length === 1 && input.value === ' ') {
        input.value = '';
    } else if (input.value.length === 2 && input.value.endsWith(' ')) {
        input.value = input.value.trim();
    }
    
    // Capitalize first letter
    if (input.value.length > 0) {
        input.value = input.value.charAt(0).toUpperCase() + input.value.slice(1).toLowerCase();
    }
}

function validateMessageInput(input) {
    // Prevent multiple consecutive spaces
    input.value = input.value.replace(/\s{2,}/g, ' ');
    
    // Don't allow space as first character or unless there are already 2 characters
    if (input.value.length === 1 && input.value === ' ') {
        input.value = '';
    } else if (input.value.length === 2 && input.value.endsWith(' ')) {
        input.value = input.value.trim();
    }
    
    // Capitalize first letter
    if (input.value.length > 0) {
        input.value = input.value.charAt(0).toUpperCase() + input.value.slice(1);
    }
}

// Add event listeners for validation
document.getElementById('in-memory-of').addEventListener('input', function(e) {
    validateNameInput(e.target);
});

document.getElementById('dedicated-by').addEventListener('input', function(e) {
    validateNameInput(e.target);
});

document.getElementById('dedication-message').addEventListener('input', function(e) {
    validateMessageInput(e.target);
});

// Update candle color in modal
function updateModalCandleColor(color) {
    // Remove all color classes first
    modalCandleWax.className = 'w-10 sm:w-12 h-20 sm:h-24 mx-auto rounded-t-lg';
    modalCandleBase.className = 'w-12 sm:w-16 h-2.5 sm:h-3 mx-auto rounded-b-lg';
    
    // Add the selected color classes
    modalCandleWax.classList.add(...candleColors[color].wax.split(' '));
    modalCandleBase.classList.add(...candleColors[color].base.split(' '));
}

// Add event listeners to radio buttons
candleTypeRadios.forEach(radio => {
    radio.addEventListener('change', function() {
        updateModalCandleColor(this.value);
    });
});

// Set initial candle color when page loads
document.addEventListener('DOMContentLoaded', setInitialCandleColor);

// Store the last dedication
let lastDedication = null;

// Update the submit dedication handler
submitDedication.addEventListener('click', (e) => {
    e.preventDefault();

    // Get form values
    const inMemoryOf = document.getElementById('in-memory-of').value.trim();
    const dedicatedBy = document.getElementById('dedicated-by').value.trim();
    const message = document.getElementById('dedication-message').value.trim();
    
    // Validate inputs
    if (!inMemoryOf || inMemoryOf.length < 2) {
        showNotification('Please enter a valid name (at least 2 characters) for "In Memory Of"');
        return;
    }
    
    if (!dedicatedBy || dedicatedBy.length < 2) {
        showNotification('Please enter a valid name (at least 2 characters) for "Dedicated By"');
        return;
    }
    
    if (!message || message.length < 2) {
        showNotification('Please enter a meaningful message (at least 2 characters)');
        return;
    }
    
    // Store the dedication for sharing
    lastDedication = {
        name: inMemoryOf,
        message: message,
        dedicatedBy: dedicatedBy,
        date: new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
    };
    
    // Create a new dedication object (all dedications are now public)
    const newDedication = {
        name: inMemoryOf,
        message: message,
        dedicatedBy: dedicatedBy,
        date: new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' })
    };
    
    // Add the new dedication to the grid
    addDedicationToGrid(newDedication, 0);
    
    // Store in local storage to persist between page loads
    const storedDedications = JSON.parse(localStorage.getItem('userDedications') || '[]');
    storedDedications.push(newDedication);
    localStorage.setItem('userDedications', JSON.stringify(storedDedications));
    
    // Show confirmation
    dedicationForm.classList.add('hidden');
    dedicationConfirmation.classList.remove('hidden');
    
    // Show notification
    showNotification('Your dedication has been added successfully.');
});

function addDedicationToGrid(dedication, index) {
    const selectedCandleType = document.querySelector('input[name="candle-type"]:checked').value;
    
    // Define candle colors for the grid items
    const gridCandleColors = {
        white: {
            body: 'bg-gradient-to-b from-white to-gray-100',
            base: 'bg-gradient-to-b from-white to-gray-300'
        },
        cream: {
            body: 'bg-gradient-to-b from-cream to-white',
            base: 'bg-gradient-to-b from-cream to-yellow-600/20'
        },
        gold: {
            body: 'bg-gradient-to-b from-yellow-400 to-yellow-600',
            base: 'bg-gradient-to-b from-yellow-500 to-yellow-700'
        }
    };
    
    const card = document.createElement('div');
    card.className = `bg-black/40 backdrop-blur-sm border border-gray-700/50 rounded-lg p-5 shadow-lg candle-card animated-entry dedication-delay-${index % 6 + 1}`;
    
    card.innerHTML = `
        <div class="flex flex-col h-full">
            <div class="flex items-center justify-center mb-4">
                <!-- Candle Animation with updated flame style -->
                <div class="relative w-20 h-32">
                    <!-- Candle -->
                    <div class="absolute left-1/2 transform -translate-x-1/2 bottom-0 w-12">
                        <!-- Updated Wick with Flame (matches large candle style) -->
                        <div class="relative w-1 h-4 bg-gray-700 mx-auto rounded-t-lg">
                            <!-- Outer Flame (scaled down but same style) -->
                            <div class="absolute left-1/2 top-[-18px] transform -translate-x-1/2 w-4 h-8 bg-yellow-600/80 rounded-full blur-sm animate-flame"></div>

                            <!-- Inner Flame (scaled down but same style) -->
                            <div class="absolute left-1/2 top-[-15px] transform -translate-x-1/2 w-2 h-6 bg-white/90 rounded-full blur-[2px] animate-flame"></div>
                        </div>

                        <!-- Candle Body (unchanged) -->
                        <div class="w-8 h-16 ${gridCandleColors[selectedCandleType].body} mx-auto rounded-t-lg"></div>

                        <!-- Candle Base (unchanged) -->
                        <div class="w-10 h-2 ${gridCandleColors[selectedCandleType].base} mx-auto rounded-b-lg"></div>
                    </div>

                    <!-- Reflection/Glow (unchanged) -->
                    <div class="absolute left-1/2 transform -translate-x-1/2 bottom-2 w-32 h-6 bg-yellow-600/20 rounded-full blur-xl" style="animation: glow 3s ease-in-out infinite"></div>
                </div>
            </div>
            
            <h3 class="font-hedvig text-lg text-yellow-600 mb-1">In Memory of ${dedication.name}</h3>
            
            <div class="bg-black/30 rounded-lg p-3 mb-3 border border-gray-700/50 flex-grow">
                <p class="text-white/90 text-sm italic">"${dedication.message}"</p>
            </div>
            
            <div class="flex items-center justify-between text-xs text-gray-400">
                <span>By: ${dedication.dedicatedBy}</span>
                <span>${dedication.date}</span>
            </div>
        </div>
    `;

    
    // Add it as the first child - new dedications appear at the top
    if (dedicationGrid.firstChild) {
        dedicationGrid.insertBefore(card, dedicationGrid.firstChild);
    } else {
        dedicationGrid.appendChild(card);
    }
    
    // Ensure animations work by explicitly setting style
    setTimeout(() => {
        card.style.opacity = "1";
        card.style.transform = "translateY(0)";
    }, 100);
}

// When page loads, check if there are any stored dedications
document.addEventListener('DOMContentLoaded', () => {
    // Load any previously stored dedications
    const storedDedications = JSON.parse(localStorage.getItem('userDedications') || '[]');
    storedDedications.forEach((dedication, index) => {
        addDedicationToGrid(dedication, index);
    });
});

// The rest of your existing event listeners
newDedication.addEventListener('click', () => {
    dedicationConfirmation.classList.add('hidden');
    document.querySelector('form').reset();
    dedicationForm.classList.remove('hidden');
});

viewDedications.addEventListener('click', () => {
    candleModal.classList.add('opacity-0', 'pointer-events-none');
    // Scroll to dedication grid
    setTimeout(() => {
        window.scrollTo({
            top: document.getElementById('dedication-grid').offsetTop - 100,
            behavior: 'smooth'
        });
    }, 500);
});

// Facebook sharing functionality
document.getElementById('share-facebook').addEventListener('click', function() {
    if (!lastDedication) {
        showNotification('No dedication available to share');
        return;
    }

    try {
        const shareUrl = window.location.href;
        const shareText = `In loving memory of ${lastDedication.name}\n\n"${lastDedication.message}"\n\nDedicated by ${lastDedication.dedicatedBy} on ${lastDedication.date}`;
        
        // Fallback to Web Share API if FB SDK fails
        if (typeof FB === 'undefined') {
            if (navigator.share) {
                navigator.share({
                    title: 'Memorial Dedication',
                    text: shareText,
                    url: shareUrl
                }).then(() => {
                    showNotification('Successfully shared dedication');
                }).catch(() => {
                    // Open in new window as last resort
                    const fbShareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}&quote=${encodeURIComponent(shareText)}`;
                    window.open(fbShareUrl, '_blank', 'width=600,height=400');
                });
            } else {
                // Fallback to direct Facebook share URL
                const fbShareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl)}&quote=${encodeURIComponent(shareText)}`;
                window.open(fbShareUrl, '_blank', 'width=600,height=400');
            }
            return;
        }

        // Use Facebook SDK if available
        FB.ui({
            method: 'share',
            href: shareUrl,
            quote: shareText,
        }, function(response) {
            if (response && !response.error_message) {
                showNotification('Successfully shared to Facebook');
            } else {
                showNotification('Could not share to Facebook. Please try again.');
            }
        });
    } catch (error) {
        console.error('Sharing error:', error);
        showNotification('An error occurred while sharing. Please try again.');
    }
});

// Notification function
function showNotification(message) {
    const notification = document.getElementById('notification');
    const notificationMessage = document.getElementById('notification-message');
    
    notificationMessage.textContent = message;
    notification.classList.remove('hidden');
    notification.classList.add('transform', 'translate-y-0');
    
    setTimeout(() => {
        notification.classList.add('hidden');
    }, 3000);
}

// First, make sure we have the required CSS animation keyframes
document.head.insertAdjacentHTML('beforeend', `
<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    @keyframes flame {
        0% { transform: translateX(-50%) scaleY(1); }
        50% { transform: translateX(-50%) scaleY(1.1); }
        100% { transform: translateX(-50%) scaleY(1); }
    }
    
    @keyframes glow {
        0% { opacity: 0.7; }
        50% { opacity: 0.9; }
        100% { opacity: 0.7; }
    }
</style>
`);

// Fix color class definitions if needed
if (!document.getElementById('tailwind-colors')) {
    document.head.insertAdjacentHTML('beforeend', `
    <style id="tailwind-colors">
        .from-cream { --tw-gradient-from: #FFFDD0; }
        .to-white { --tw-gradient-to: #FFFFFF; }
        .to-yellow-600\/20 { --tw-gradient-to: rgba(202, 138, 4, 0.2); }
        .bg-yellow-600\/80 { background-color: rgba(202, 138, 4, 0.8); }
        .bg-white\/90 { background-color: rgba(255, 255, 255, 0.9); }
        .bg-yellow-600\/20 { background-color: rgba(202, 138, 4, 0.2); }
        .bg-yellow-600\/30 { background-color: rgba(202, 138, 4, 0.3); }
    </style>
    `);
}

document.addEventListener('DOMContentLoaded', function() {
    const dedicationGrid = document.getElementById('dedication-grid');

    // Retrieve dedications from localStorage
    const dedications = JSON.parse(localStorage.getItem('dedications') || '[]');

    // Loop through dedications and create cards
    dedications.forEach(dedication => {
        const card = document.createElement('div');
        card.className = 'bg-black/40 backdrop-blur-sm border border-gray-700/50 rounded-lg p-5 shadow-lg candle-card animated-entry';

        card.innerHTML = `
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-center mb-4">
                    <!-- Small Candle Animation -->
                    <div class="relative w-20 h-32">
                        <!-- Candle -->
                        <div class="absolute left-1/2 transform -translate-x-1/2 bottom-0 w-12">
                            <!-- Wick -->
                            <div class="w-0.5 h-3 bg-gray-700 mx-auto mb-0 rounded-t-lg"></div>
                            
                            <!-- Flame -->
                            <div>
                                <!-- Outer Flame -->
                                <div class="absolute left-1/2 transform -translate-x-1/2 bottom-[46px] w-4 h-8 bg-yellow-600/80 rounded-full blur-sm" style="animation: flame 1.5s ease-in-out infinite"></div>
                                
                                <!-- Inner Flame -->
                                <div class="absolute left-1/2 transform -translate-x-1/2 bottom-[48px] w-2 h-6 bg-white/90 rounded-full blur-[1px]" style="animation: flame 2s ease-in-out infinite"></div>
                            </div>
                            
                            <!-- Candle Body -->
                            <div class="w-8 h-16 bg-gradient-to-b from-cream to-white mx-auto rounded-t-lg"></div>
                            
                            <!-- Candle Base -->
                            <div class="w-10 h-2 bg-gradient-to-b from-cream to-yellow-600/20 mx-auto rounded-b-lg"></div>
                        </div>
                        
                        <!-- Reflection/Glow -->
                        <div class="absolute left-1/2 transform -translate-x-1/2 bottom-2 w-32 h-6 bg-yellow-600/20 rounded-full blur-xl" style="animation: glow 3s ease-in-out infinite"></div>
                    </div>
                </div>
                
                <h3 class="font-hedvig text-lg text-yellow-600 mb-1">In Memory of ${dedication.for}</h3>
                
                <div class="bg-black/30 rounded-lg p-3 mb-3 border border-gray-700/50 flex-grow">
                    <p class="text-white/90 text-sm italic">"${dedication.text}"</p>
                </div>
                
                <div class="flex items-center justify-between text-xs text-gray-400">
                    <span>${dedication.timestamp}</span>
                </div>
            </div>
        `;

        // Add the card to the grid
        dedicationGrid.appendChild(card);
    });
});
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
</body>
</html>