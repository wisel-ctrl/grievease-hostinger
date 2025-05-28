<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifePlan Contract Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Cinzel:wght@400;500;600&family=Hedvig+Letters+Serif:opsz@12..24&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'playfair': ['"Playfair Display"', 'serif'],
                        'alexbrush': ['"Alex Brush"', 'cursive'],
                        'inter': ['Inter', 'sans-serif'],
                        'cinzel': ['Cinzel', 'serif'],
                        'hedvig': ['Hedvig Letters Serif', 'serif']
                    },
                    colors: {
                        'yellow': {
                            600: '#CA8A04',
                        },
                        'navy': '#2D2B30',
                        'cream': '#F9F6F0',
                        'dark': '#1E1E1E',
                        'gold': '#C9A773',
                        'darkgold': '#B08D50',
                        'primary': '#2D2B30',
                        'primary-foreground': '#FFFFFF',
                        'secondary': '#F1F5F9',
                        'secondary-foreground': '#1E1E1E',
                        'border': '#E4E9F0',
                        'input-border': '#D3D8E1',
                        'error': '#E53E3E',
                        'success': '#38A169',
                    },
                    boxShadow: {
                        'input': '0 1px 2px rgba(0, 0, 0, 0.05)',
                        'card': '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes shimmer {
            0%, 100% { transform: translateX(-100%); }
            50% { transform: translateX(100%); }
        }
        
        .animate-fadeInUp {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        .shimmer-effect::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: shimmer 2s infinite;
        }
        
        .glass-effect {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        
        .contract-section {
            opacity: 0;
            animation: fadeInUp 0.8s ease-out forwards;
        }
        
        .contract-section:nth-child(1) { animation-delay: 0.1s; }
        .contract-section:nth-child(2) { animation-delay: 0.2s; }
        .contract-section:nth-child(3) { animation-delay: 0.3s; }
        .contract-section:nth-child(4) { animation-delay: 0.4s; }
        .contract-section:nth-child(5) { animation-delay: 0.5s; }
        .contract-section:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-navy via-primary to-dark font-inter">
    <!-- Header -->
    <div class="relative overflow-hidden bg-gradient-to-r from-navy to-primary">
        <div class="absolute inset-0 shimmer-effect"></div>
        <div class="relative z-10 px-6 py-16 text-center">
            <h1 class="text-5xl md:text-6xl font-playfair font-bold text-white mb-4 tracking-tight">
                LifePlan Contract
            </h1>
            <p class="text-xl text-gold font-inter font-light">
                Funeral Service Management System
            </p>
            <div class="mt-8 inline-flex items-center space-x-2 bg-success/20 text-success px-4 py-2 rounded-full text-sm font-medium">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span>Active Contract</span>
            </div>
        </div>
    </div>

    <!-- Main Container -->
    <div class="max-w-7xl mx-auto px-6 py-12 -mt-8 relative z-20">
        <div class="glass-effect rounded-3xl shadow-2xl overflow-hidden">
            
            <!-- Contract Overview -->
            <div class="contract-section p-8 bg-gradient-to-r from-cream to-white">
                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div class="bg-white rounded-2xl p-6 shadow-card border border-border hover:shadow-lg transition-all duration-300">
                        <div class="text-sm font-medium text-navy/60 uppercase tracking-wide">Contract No.</div>
                        <div class="text-2xl font-playfair font-bold text-navy mt-1">LP-2025-001234</div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-card border border-border hover:shadow-lg transition-all duration-300">
                        <div class="text-sm font-medium text-navy/60 uppercase tracking-wide">Monthly Payment</div>
                        <div class="text-2xl font-playfair font-bold text-navy mt-1">₱5,833.33</div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-card border border-border hover:shadow-lg transition-all duration-300">
                        <div class="text-sm font-medium text-navy/60 uppercase tracking-wide">Total Value</div>
                        <div class="text-2xl font-playfair font-bold text-navy mt-1">₱350,000.00</div>
                    </div>
                    <div class="bg-white rounded-2xl p-6 shadow-card border border-border hover:shadow-lg transition-all duration-300">
                        <div class="text-sm font-medium text-navy/60 uppercase tracking-wide">Term</div>
                        <div class="text-2xl font-playfair font-bold text-navy mt-1">60 Months</div>
                    </div>
                </div>
            </div>

            <!-- Payment Schedule -->
            <div class="contract-section px-8 py-8">
                <h2 class="text-3xl font-playfair font-bold text-navy mb-6 flex items-center">
                    <div class="w-8 h-8 bg-gold rounded-full flex items-center justify-center mr-4">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"></path>
                        </svg>
                    </div>
                    Payment Schedule
                </h2>
                
                <div class="bg-white rounded-2xl shadow-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-navy text-white">
                                <tr>
                                    <th class="px-6 py-4 text-left font-inter font-semibold uppercase tracking-wide text-sm">Description</th>
                                    <th class="px-6 py-4 text-left font-inter font-semibold uppercase tracking-wide text-sm">Amount</th>
                                    <th class="px-6 py-4 text-left font-inter font-semibold uppercase tracking-wide text-sm">Due Date</th>
                                    <th class="px-6 py-4 text-left font-inter font-semibold uppercase tracking-wide text-sm">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr class="hover:bg-secondary/30 transition-colors">
                                    <td class="px-6 py-4 font-medium text-navy">Monthly Payment</td>
                                    <td class="px-6 py-4 text-navy font-semibold">₱5,833.33</td>
                                    <td class="px-6 py-4 text-navy">15th of each month</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-success/10 text-success">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            Paid
                                        </span>
                                    </td>
                                </tr>
                                <tr class="hover:bg-secondary/30 transition-colors">
                                    <td class="px-6 py-4 font-medium text-navy">Service Coverage</td>
                                    <td class="px-6 py-4 text-navy font-semibold">₱350,000.00</td>
                                    <td class="px-6 py-4 text-navy">Upon need</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gold/10 text-darkgold">
                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            Guaranteed
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Death Benefit -->
            <div class="contract-section px-8 py-8 bg-secondary/20">
                <h2 class="text-3xl font-playfair font-bold text-navy mb-6 flex items-center">
                    <div class="w-8 h-8 bg-error/80 rounded-full flex items-center justify-center mr-4">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    Death Benefit & Payment Obligations
                </h2>
                
                <div class="bg-gradient-to-r from-error/10 to-error/5 border-l-4 border-error rounded-lg p-6 mb-8">
                    <div class="flex items-start">
                        <svg class="w-6 h-6 text-error mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                        <div>
                            <h3 class="font-semibold text-error text-lg mb-2">Critical Information</h3>
                            <p class="text-navy">What happens when the subscriber passes away</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Scenario 1 -->
                    <div class="bg-white rounded-2xl p-6 shadow-card">
                        <h3 class="text-xl font-playfair font-bold text-navy mb-4 flex items-center">
                            <span class="bg-error/10 text-error w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold mr-3">1</span>
                            Death During Payment Period
                        </h3>
                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-success mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <strong class="text-navy">Immediate Benefit Activation:</strong>
                                    <span class="text-navy/80"> Full funeral service benefits become immediately available regardless of payment completion status</span>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-success mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <strong class="text-navy">Outstanding Balance:</strong>
                                    <span class="text-navy/80"> Remaining monthly payments become the responsibility of the designated co-maker</span>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-success mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <strong class="text-navy">Payment Schedule:</strong>
                                    <span class="text-navy/80"> Co-maker must continue monthly payments until the 60-month term is completed</span>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-success mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <strong class="text-navy">No Penalty Fees:</strong>
                                    <span class="text-navy/80"> No additional charges or penalties are imposed upon death of subscriber</span>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-success mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <strong class="text-navy">Grace Period:</strong>
                                    <span class="text-navy/80"> 30-day grace period for co-maker to arrange payment continuation</span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Scenario 2 -->
                    <div class="bg-white rounded-2xl p-6 shadow-card">
                        <h3 class="text-xl font-playfair font-bold text-navy mb-4 flex items-center">
                            <span class="bg-success/10 text-success w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold mr-3">2</span>
                            Death After Full Payment
                        </h3>
                        <ul class="space-y-3">
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-success mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <strong class="text-navy">Full Benefits:</strong>
                                    <span class="text-navy/80"> Complete funeral service package available with no additional costs</span>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-success mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <strong class="text-navy">No Further Obligations:</strong>
                                    <span class="text-navy/80"> No payment obligations for family members or estate</span>
                                </div>
                            </li>
                            <li class="flex items-start">
                                <svg class="w-5 h-5 text-success mr-3 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <div>
                                    <strong class="text-navy">Transferable Rights:</strong>
                                    <span class="text-navy/80"> Benefits can be transferred to immediate family members if needed</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Co-Maker Responsibilities -->
            <div class="contract-section px-8 py-8">
                <h2 class="text-3xl font-playfair font-bold text-navy mb-6 flex items-center">
                    <div class="w-8 h-8 bg-yellow-600 rounded-full flex items-center justify-center mr-4">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    Co-Maker Responsibilities & Rights
                </h2>
                
                <div class="bg-gradient-to-r from-yellow-600/10 to-yellow-600/5 border-l-4 border-yellow-600 rounded-lg p-6 mb-8">
                    <h3 class="font-semibold text-yellow-600 text-lg mb-2">Co-Maker Legal Obligations</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-yellow-600 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-navy mb-2">Payment Continuation</h4>
                                <p class="text-navy/70 text-sm">Assume monthly payment responsibilities immediately upon subscriber's death</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-yellow-600 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884zM18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-navy mb-2">Communication</h4>
                                <p class="text-navy/70 text-sm">Maintain updated contact information with the company</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-yellow-600 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-navy mb-2">Legal Standing</h4>
                                <p class="text-navy/70 text-sm">Co-maker has legal standing to make decisions regarding funeral arrangements</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-yellow-600 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-navy mb-2">Modification Rights</h4>
                                <p class="text-navy/70 text-sm">Can request payment plan modifications with company approval</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-yellow-600 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-navy mb-2">Benefit Access</h4>
                                <p class="text-navy/70 text-sm">Can claim benefits on behalf of deceased subscriber's family</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Coverage -->
            <div class="contract-section px-8 py-8 bg-secondary/20">
                <h2 class="text-3xl font-playfair font-bold text-navy mb-6 flex items-center">
                    <div class="w-8 h-8 bg-gold rounded-full flex items-center justify-center mr-4">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    Complete Service Coverage
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300 hover:scale-105">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gold/10 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-gold" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-navy">Funeral Services</h3>
                        </div>
                        <p class="text-navy/70 text-sm">Complete funeral arrangement & coordination</p>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300 hover:scale-105">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gold/10 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-gold" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-navy">Casket</h3>
                        </div>
                        <p class="text-navy/70 text-sm">Premium wooden casket with velvet interior</p>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300 hover:scale-105">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gold/10 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-gold" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 2a4 4 0 00-4 4v1H5a1 1 0 00-.994.89l-1 9A1 1 0 004 18h12a1 1 0 00.994-1.11l-1-9A1 1 0 0015 7h-1V6a4 4 0 00-4-4zm2 5V6a2 2 0 10-4 0v1h4zm-6 3a1 1 0 112 0 1 1 0 01-2 0zm7-1a1 1 0 100 2 1 1 0 000-2z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-navy">Embalming</h3>
                        </div>
                        <p class="text-navy/70 text-sm">Professional embalming services</p>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300 hover:scale-105">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gold/10 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-gold" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-navy">Chapel Services</h3>
                        </div>
                        <p class="text-navy/70 text-sm">3-day chapel rental with decorations</p>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300 hover:scale-105">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gold/10 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-gold" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8 2a2 2 0 00-2 2v1.5a.5.5 0 01-.5.5h-1a.5.5 0 01-.5-.5V4a4 4 0 118 0v1.5a.5.5 0 01-.5.5h-1a.5.5 0 01-.5-.5V4a2 2 0 00-2-2zM3 7a1 1 0 000 2h.5a.5.5 0 01.5.5v6a.5.5 0 01-.5.5H3a1 1 0 100 2h14a1 1 0 100-2h-.5a.5.5 0 01-.5-.5v-6a.5.5 0 01.5-.5H17a1 1 0 100-2H3z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-navy">Transportation</h3>
                        </div>
                        <p class="text-navy/70 text-sm">Hearse and family car services</p>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300 hover:scale-105">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gold/10 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-gold" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-navy">Documentation</h3>
                        </div>
                        <p class="text-navy/70 text-sm">All legal documents and permits</p>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300 hover:scale-105">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gold/10 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-gold" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
                                    <path fill-rule="evenodd" d="M4 5a2 2 0 012-2v1a2 2 0 002 2h4a2 2 0 002-2V3a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm2.5 7a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm2.45 4a2.5 2.5 0 01-4.9 0h4.9zM12 9a1 1 0 100 2h3a1 1 0 100-2h-3zm-1 4a1 1 0 011-1h2a1 1 0 110 2h-2a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-navy">Burial/Cremation</h3>
                        </div>
                        <p class="text-navy/70 text-sm">Choice of burial or cremation services</p>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300 hover:scale-105">
                        <div class="flex items-center mb-4">
                            <div class="w-12 h-12 bg-gold/10 rounded-full flex items-center justify-center mr-4">
                                <svg class="w-6 h-6 text-gold" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 2a1 1 0 011 1v1h1a1 1 0 010 2H6v1a1 1 0 01-2 0V6H3a1 1 0 010-2h1V3a1 1 0 011-1zm0 10a1 1 0 011 1v1h1a1 1 0 110 2H6v1a1 1 0 11-2 0v-1H3a1 1 0 110-2h1v-1a1 1 0 011-1zM12 2a1 1 0 01.967.744L14.146 7.2 17.5 9.134a1 1 0 010 1.732L14.146 12.8l-1.179 4.456a1 1 0 01-1.934 0L9.854 12.8 6.5 10.866a1 1 0 010-1.732L9.854 7.2l1.179-4.456A1 1 0 0112 2z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="font-semibold text-navy">Memorial Items</h3>
                        </div>
                        <p class="text-navy/70 text-sm">Flowers, memorial cards, and keepsakes</p>
                    </div>
                </div>
            </div>

            <!-- Default & Remedies -->
            <div class="contract-section px-8 py-8">
                <h2 class="text-3xl font-playfair font-bold text-navy mb-6 flex items-center">
                    <div class="w-8 h-8 bg-error/80 rounded-full flex items-center justify-center mr-4">
                        <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    Default & Remedies
                </h2>
                
                <div class="bg-gradient-to-r from-error/10 to-error/5 border-l-4 border-error rounded-lg p-6 mb-8">
                    <div class="flex items-start">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-error/20 text-error mr-4">
                            Important
                        </span>
                        <div>
                            <h3 class="font-semibold text-error text-lg mb-2">Consequences of missed payments</h3>
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="bg-white rounded-xl p-6 shadow-card">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-success mr-4 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-navy mb-2">Grace Period</h4>
                                <p class="text-navy/70">30 days grace period for all missed payments</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-yellow-600 mr-4 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-navy mb-2">Late Fees</h4>
                                <p class="text-navy/70">₱500 penalty fee after grace period expires</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-error mr-4 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-navy mb-2">Contract Suspension</h4>
                                <p class="text-navy/70">Benefits suspended after 60 days of non-payment</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-success mr-4 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 100-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-navy mb-2">Reinstatement</h4>
                                <p class="text-navy/70">Contract can be reinstated within 12 months with full payment of arrears</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-yellow-600 mr-4 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-navy mb-2">Co-Maker Liability</h4>
                                <p class="text-navy/70">Co-maker becomes immediately liable for all missed payments</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-error mr-4 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M3 6a3 3 0 013-3h10a1 1 0 01.8 1.6L14.25 8l2.55 3.4A1 1 0 0116 13H6a1 1 0 00-1 1v3a1 1 0 11-2 0V6z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-navy mb-2">Legal Action</h4>
                                <p class="text-navy/70 text-sm">Company reserves the right to take legal action for contract breaches</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-yellow-600 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-navy mb-2">Death Notification</h4>
                                <p class="text-navy/70 text-sm">Must notify the company within 72 hours of subscriber's death</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-yellow-600 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-navy mb-2">Documentation</h4>
                                <p class="text-navy/70 text-sm">Provide death certificate and legal documents for benefit processing</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-6 shadow-card hover:shadow-lg transition-all duration-300">
                        <div class="flex items-start">
                            <svg class="w-6 h-6 text-yellow-600 mr-3 mt-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z"></path>
                            </svg>
                            <div>
                                <h4 class="font-semibold text-navy mb-2">Payment Guarantee</h4>
                                <p class="text-navy/70 text-sm">Co-maker guarantees all payment obligations if subscriber becomes unable to pay</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="contract-section px-8 py-8 bg-navy text-white">
                <div class="text-center">
                    <p class="text-sm text-gold/80">This contract is subject to the terms and conditions outlined above.</p>
                    <p class="text-sm text-gold/80 mt-2">For inquiries, please contact our customer service department.</p>
                    <div class="mt-4">
                        <span class="inline-flex items-center space-x-2 text-xs text-gold/60">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            <span>Contract Version: 2024-01</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>