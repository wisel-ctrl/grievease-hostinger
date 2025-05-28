<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LifePlan Contract Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Cinzel:wght@400;500;600&family=Hedvig+Letters+Serif:opsz,wght@12..24,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        .gradient-bg {
            background: linear-gradient(135deg, #F9F6F0 0%, #F1F5F9 100%);
        }
        .smooth-scroll {
            scroll-behavior: smooth;
        }
        .fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .hover-lift {
            transition: all 0.3s ease;
        }
        .hover-lift:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        }
        .section-divider {
            background: linear-gradient(90deg, transparent, #C9A773, transparent);
            height: 1px;
        }
    </style>
</head>
<body class="gradient-bg font-inter smooth-scroll">
    <div class="min-h-screen py-8 px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Header -->
            <div class="text-center mb-12 fade-in">
                <div class="inline-flex items-center justify-center mb-6">
                    <div class="bg-navy p-4 rounded-full shadow-card">
                        <i class="fas fa-dove text-3xl text-gold"></i>
                    </div>
                </div>
                <h1 class="font-playfair text-4xl md:text-5xl font-bold text-navy mb-4">LifePlan Contract</h1>
                <p class="text-lg text-navy/70 max-w-2xl mx-auto">Comprehensive funeral service management plan with flexible payment options and complete family protection</p>
            </div>

            <!-- Main Content Card -->
            <div class="bg-white rounded-2xl shadow-card overflow-hidden fade-in">
                <!-- Contract Header -->
                <div class="bg-navy text-white p-8">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 class="font-cinzel text-2xl font-semibold mb-2">Premium LifePlan Package</h2>
                            <p class="text-gold font-medium">5-Year Payment Term | Comprehensive Coverage</p>
                        </div>
                        <div class="mt-4 md:mt-0 text-right">
                            <div class="text-3xl font-bold text-gold">₱125,000</div>
                            <div class="text-sm text-gray-300">Total Package Value</div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="bg-secondary border-b border-border">
                    <div class="flex flex-wrap gap-1 p-2">
                        <button onclick="showSection('overview')" class="tab-btn active px-6 py-3 rounded-lg font-medium transition-all duration-300" id="overview-tab">
                            <i class="fas fa-eye mr-2"></i>Overview
                        </button>
                        <button onclick="showSection('payment')" class="tab-btn px-6 py-3 rounded-lg font-medium transition-all duration-300" id="payment-tab">
                            <i class="fas fa-credit-card mr-2"></i>Payment Terms
                        </button>
                        <button onclick="showSection('death-benefits')" class="tab-btn px-6 py-3 rounded-lg font-medium transition-all duration-300" id="death-benefits-tab">
                            <i class="fas fa-heart mr-2"></i>Death Benefits
                        </button>
                        <button onclick="showSection('comaker')" class="tab-btn px-6 py-3 rounded-lg font-medium transition-all duration-300" id="comaker-tab">
                            <i class="fas fa-users mr-2"></i>Co-maker Details
                        </button>
                        <button onclick="showSection('legal')" class="tab-btn px-6 py-3 rounded-lg font-medium transition-all duration-300" id="legal-tab">
                            <i class="fas fa-gavel mr-2"></i>Legal Terms
                        </button>
                    </div>
                </div>

                <!-- Content Sections -->
                <div class="p-8">
                    <!-- Overview Section -->
                    <div id="overview-section" class="section">
                        <div class="grid md:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div class="bg-cream rounded-xl p-6 hover-lift">
                                    <div class="flex items-center mb-4">
                                        <div class="bg-gold/20 p-3 rounded-full mr-4">
                                            <i class="fas fa-calendar-alt text-gold text-xl"></i>
                                        </div>
                                        <h3 class="font-playfair text-xl font-semibold text-navy">Contract Duration</h3>
                                    </div>
                                    <p class="text-navy/80">5 years payment period with lifetime coverage benefits upon completion</p>
                                </div>

                                <div class="bg-cream rounded-xl p-6 hover-lift">
                                    <div class="flex items-center mb-4">
                                        <div class="bg-gold/20 p-3 rounded-full mr-4">
                                            <i class="fas fa-shield-alt text-gold text-xl"></i>
                                        </div>
                                        <h3 class="font-playfair text-xl font-semibold text-navy">Coverage</h3>
                                    </div>
                                    <ul class="text-navy/80 space-y-2">
                                        <li class="flex items-center"><i class="fas fa-check text-success mr-2"></i>Complete funeral services</li>
                                        <li class="flex items-center"><i class="fas fa-check text-success mr-2"></i>Premium casket selection</li>
                                        <li class="flex items-center"><i class="fas fa-check text-success mr-2"></i>Embalming and preparation</li>
                                        <li class="flex items-center"><i class="fas fa-check text-success mr-2"></li>Memorial services coordination</li>
                                    </ul>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div class="bg-gold/10 rounded-xl p-6 border border-gold/30">
                                    <h3 class="font-playfair text-xl font-semibold text-navy mb-4">Package Highlights</h3>
                                    <div class="space-y-3">
                                        <div class="flex justify-between items-center py-2 border-b border-gold/20">
                                            <span class="text-navy/80">Monthly Payment</span>
                                            <span class="font-semibold text-navy">₱2,083</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-gold/20">
                                            <span class="text-navy/80">Total Payments</span>
                                            <span class="font-semibold text-navy">₱125,000</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2 border-b border-gold/20">
                                            <span class="text-navy/80">Service Value</span>
                                            <span class="font-semibold text-success">₱175,000</span>
                                        </div>
                                        <div class="flex justify-between items-center py-2">
                                            <span class="text-navy/80">Your Savings</span>
                                            <span class="font-semibold text-success">₱50,000</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-secondary rounded-xl p-6">
                                    <h4 class="font-semibold text-navy mb-3">Contract Status</h4>
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-success rounded-full mr-3"></div>
                                        <span class="text-success font-medium">Active & Current</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Terms Section -->
                    <div id="payment-section" class="section hidden">
                        <div class="space-y-8">
                            <div class="text-center">
                                <h3 class="font-playfair text-2xl font-semibold text-navy mb-2">Payment Structure</h3>
                                <p class="text-navy/70">Flexible payment options designed for your convenience</p>
                            </div>

                            <div class="grid md:grid-cols-3 gap-6">
                                <div class="bg-cream rounded-xl p-6 text-center hover-lift">
                                    <div class="bg-gold/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-calendar text-gold text-2xl"></i>
                                    </div>
                                    <h4 class="font-semibold text-navy mb-2">Monthly Payment</h4>
                                    <div class="text-2xl font-bold text-navy mb-1">₱2,083</div>
                                    <p class="text-sm text-navy/70">For 60 months</p>
                                </div>

                                <div class="bg-cream rounded-xl p-6 text-center hover-lift">
                                    <div class="bg-gold/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-percentage text-gold text-2xl"></i>
                                    </div>
                                    <h4 class="font-semibold text-navy mb-2">Interest Rate</h4>
                                    <div class="text-2xl font-bold text-navy mb-1">0%</div>
                                    <p class="text-sm text-navy/70">No hidden charges</p>
                                </div>

                                <div class="bg-cream rounded-xl p-6 text-center hover-lift">
                                    <div class="bg-gold/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <i class="fas fa-clock text-gold text-2xl"></i>
                                    </div>
                                    <h4 class="font-semibold text-navy mb-2">Grace Period</h4>
                                    <div class="text-2xl font-bold text-navy mb-1">30</div>
                                    <p class="text-sm text-navy/70">Days after due date</p>
                                </div>
                            </div>

                            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                                <h4 class="font-semibold text-navy mb-4 flex items-center">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                    Important Payment Information
                                </h4>
                                <div class="space-y-3 text-navy/80">
                                    <p><strong>Payment Methods:</strong> Bank transfer, cash payment at office, online banking, or authorized collection agents</p>
                                    <p><strong>Late Payment Fee:</strong> ₱200 per month after grace period expires</p>
                                    <p><strong>Advance Payments:</strong> Accepted and will reduce remaining balance</p>
                                    <p><strong>Payment Schedule:</strong> Due on the same date each month as contract signing date</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Death Benefits Section -->
                    <div id="death-benefits-section" class="section hidden">
                        <div class="space-y-8">
                            <div class="text-center">
                                <h3 class="font-playfair text-2xl font-semibold text-navy mb-2">What Happens Upon Death</h3>
                                <p class="text-navy/70">Comprehensive coverage and protection for your family</p>
                            </div>

                            <div class="grid md:grid-cols-2 gap-8">
                                <div class="space-y-6">
                                    <div class="bg-success/10 border border-success/30 rounded-xl p-6">
                                        <h4 class="font-semibold text-navy mb-4 flex items-center">
                                            <i class="fas fa-check-circle text-success mr-2"></i>
                                            If Payments are Complete (5 years paid)
                                        </h4>
                                        <ul class="space-y-2 text-navy/80">
                                            <li class="flex items-start"><i class="fas fa-arrow-right text-success mt-1 mr-2 text-sm"></i>Full funeral service immediately available</li>
                                            <li class="flex items-start"><i class="fas fa-arrow-right text-success mt-1 mr-2 text-sm"></i>No additional payments required</li>
                                            <li class="flex items-start"><i class="fas fa-arrow-right text-success mt-1 mr-2 text-sm"></i>Premium package benefits activated</li>
                                            <li class="flex items-start"><i class="fas fa-arrow-right text-success mt-1 mr-2 text-sm"></i>Family receives full service value (₱175,000)</li>
                                        </ul>
                                    </div>

                                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                                        <h4 class="font-semibold text-navy mb-4 flex items-center">
                                            <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                                            If Death Occurs During Payment Period
                                        </h4>
                                        <ul class="space-y-2 text-navy/80">
                                            <li class="flex items-start"><i class="fas fa-arrow-right text-blue-600 mt-1 mr-2 text-sm"></i>Immediate service activation</li>
                                            <li class="flex items-start"><i class="fas fa-arrow-right text-blue-600 mt-1 mr-2 text-sm"></i>No balance collection from family</li>
                                            <li class="flex items-start"><i class="fas fa-arrow-right text-blue-600 mt-1 mr-2 text-sm"></i>Full package benefits provided</li>
                                            <li class="flex items-start"><i class="fas fa-arrow-right text-blue-600 mt-1 mr-2 text-sm"></i>Outstanding balance automatically forgiven</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <div class="bg-cream rounded-xl p-6">
                                        <h4 class="font-semibold text-navy mb-4">Service Activation Process</h4>
                                        <div class="space-y-4">
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-gold rounded-full flex items-center justify-center text-white text-sm font-bold mr-3">1</div>
                                                <span class="text-navy/80">Family contacts our 24/7 hotline</span>
                                            </div>
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-gold rounded-full flex items-center justify-center text-white text-sm font-bold mr-3">2</div>
                                                <span class="text-navy/80">Contract verification (2-4 hours)</span>
                                            </div>
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-gold rounded-full flex items-center justify-center text-white text-sm font-bold mr-3">3</div>
                                                <span class="text-navy/80">Services commence immediately</span>
                                            </div>
                                            <div class="flex items-center">
                                                <div class="w-8 h-8 bg-gold rounded-full flex items-center justify-center text-white text-sm font-bold mr-3">4</div>
                                                <span class="text-navy/80">Complete service delivery</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="bg-gold/10 rounded-xl p-6">
                                        <h4 class="font-semibold text-navy mb-3">Emergency Contact</h4>
                                        <div class="space-y-2">
                                            <p class="flex items-center text-navy/80">
                                                <i class="fas fa-phone text-gold mr-2"></i>
                                                24/7 Hotline: (02) 8-FUNERAL
                                            </p>
                                            <p class="flex items-center text-navy/80">
                                                <i class="fas fa-mobile-alt text-gold mr-2"></i>
                                                Mobile: +63 917 123 4567
                                            </p>
                                            <p class="flex items-center text-navy/80">
                                                <i class="fas fa-envelope text-gold mr-2"></i>
                                                emergency@lifeplan.com.ph
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Co-maker Section -->
                    <div id="comaker-section" class="section hidden">
                        <div class="space-y-8">
                            <div class="text-center">
                                <h3 class="font-playfair text-2xl font-semibold text-navy mb-2">Co-maker Responsibilities</h3>
                                <p class="text-navy/70">Understanding the role and obligations of the co-maker</p>
                            </div>

                            <div class="grid md:grid-cols-2 gap-8">
                                <div class="space-y-6">
                                    <div class="bg-cream rounded-xl p-6">
                                        <h4 class="font-semibold text-navy mb-4 flex items-center">
                                            <i class="fas fa-user-shield text-gold mr-2"></i>
                                            Co-maker Role
                                        </h4>
                                        <ul class="space-y-2 text-navy/80">
                                            <li class="flex items-start"><i class="fas fa-dot-circle text-gold mt-1 mr-2 text-xs"></i>Guarantees payment obligations</li>
                                            <li class="flex items-start"><i class="fas fa-dot-circle text-gold mt-1 mr-2 text-xs"></i>Backup contact for contract matters</li>
                                            <li class="flex items-start"><i class="fas fa-dot-circle text-gold mt-1 mr-2 text-xs"></i>Receives payment reminders if needed</li>
                                            <li class="flex items-start"><i class="fas fa-dot-circle text-gold mt-1 mr-2 text-xs"></i>Can authorize service activation</li>
                                        </ul>
                                    </div>

                                    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                                        <h4 class="font-semibold text-navy mb-4 flex items-center">
                                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                            If Primary Subscriber Dies
                                        </h4>
                                        <ul class="space-y-2 text-navy/80">
                                            <li class="flex items-start"><i class="fas fa-times text-error mt-1 mr-2 text-sm"></i>Co-maker is <strong>NOT</strong> liable for remaining payments</li>
                                            <li class="flex items-start"><i class="fas fa-check text-success mt-1 mr-2 text-sm"></i>Full service benefits are provided</li>
                                            <li class="flex items-start"><i class="fas fa-check text-success mt-1 mr-2 text-sm"></i>Outstanding balance is forgiven</li>
                                            <li class="flex items-start"><i class="fas fa-check text-success mt-1 mr-2 text-sm"></i>Co-maker assists with service coordination</li>
                                        </ul>
                                    </div>
                                </div>

                                <div class="space-y-6">
                                    <div class="bg-secondary rounded-xl p-6">
                                        <h4 class="font-semibold text-navy mb-4">Co-maker Requirements</h4>
                                        <ul class="space-y-3 text-navy/80">
                                            <li class="flex items-center">
                                                <i class="fas fa-check text-success mr-3"></i>
                                                Must be 21-65 years old
                                            </li>
                                            <li class="flex items-center">
                                                <i class="fas fa-check text-success mr-3"></i>
                                                Filipino citizen or permanent resident
                                            </li>
                                            <li class="flex items-center">
                                                <i class="fas fa-check text-success mr-3"></i>
                                                Financially capable
                                            </li>
                                            <li class="flex items-center">
                                                <i class="fas fa-check text-success mr-3"></i>
                                                Not related by blood or marriage
                                            </li>
                                        </ul>
                                    </div>

                                    <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                                        <h4 class="font-semibold text-navy mb-4">Payment Default Scenarios</h4>
                                        <div class="space-y-3 text-navy/80">
                                            <div class="p-3 bg-white rounded-lg">
                                                <p class="font-medium text-navy mb-1">If subscriber becomes unable to pay:</p>
                                                <p class="text-sm">Co-maker may be contacted but is not legally obligated to continue payments</p>
                                            </div>
                                            <div class="p-3 bg-white rounded-lg">
                                                <p class="font-medium text-navy mb-1">Contract remains valid:</p>
                                                <p class="text-sm">Even with payment delays, death benefits are still honored</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Legal Terms Section -->
                    <div id="legal-section" class="section hidden">
                        <div class="space-y-8">
                            <div class="text-center">
                                <h3 class="font-playfair text-2xl font-semibold text-navy mb-2">Legal Terms & Conditions</h3>
                                <p class="text-navy/70">Complete terms governing this LifePlan contract</p>
                            </div>

                            <div class="space-y-6">
                                <div class="bg-cream rounded-xl p-6">
                                    <h4 class="font-semibold text-navy mb-4 flex items-center">
                                        <i class="fas fa-scale-balanced text-gold mr-2"></i>
                                        Contract Validity & Coverage
                                    </h4>
                                    <div class="space-y-3 text-navy/80">
                                        <p><strong>Effective Date:</strong> Contract becomes effective immediately upon signing and first payment</p>
                                        <p><strong>Coverage Period:</strong> Lifetime coverage after completion of 60 monthly payments</p>
                                        <p><strong>Territorial Coverage:</strong> Services available nationwide in the Philippines</p>
                                        <p><strong>Age Limits:</strong> Subscriber must be 18-75 years old at time of contract signing</p>
                                    </div>
                                </div>

                                <div class="bg-secondary rounded-xl p-6">
                                    <h4 class="font-semibold text-navy mb-4 flex items-center">
                                        <i class="fas fa-times-circle text-error mr-2"></i>
                                        Exclusions & Limitations
                                    </h4>
                                    <ul class="space-y-2 text-navy/80">
                                        <li class="flex items-start"><i class="fas fa-minus text-error mt-1 mr-2 text-sm"></i>Death due to suicide within first 2 years of contract</li>
                                        <li class="flex items-start"><i class="fas fa-minus text-error mt-1 mr-2 text-sm"></i>Death due to participation in illegal activities</li>
                                        <li class="flex items-start"><i class="fas fa-minus text-error mt-1 mr-2 text-sm"></i>Death occurring during acts of war or terrorism</li>
                                        <li class="flex items-start"><i class="fas fa-minus text-error mt-1 mr-2 text-sm"></i>Services outside the Philippines (additional charges apply)</li>
                                    </ul>
                                </div>

                                <div class="bg-gold/10 border border-gold/30 rounded-xl p-6">
                                    <h4 class="font-semibold text-navy mb-4 flex items-center">
                                        <i class="fas fa-handshake text-gold mr-2"></i>
                                        Rights & Obligations
                                    </h4>
                                    <div class="grid md:grid-cols-2 gap-6">
                                        <div>
                                            <h5 class="font-medium text-navy mb-3">Subscriber Rights:</h5>
                                            <ul class="space-y-1 text-sm text-navy/80">
                                                <li>• Modify beneficiary information</li>
                                                <li>• Request contract suspension (max 6 months)</li>
                                                <li>• Advance payment without penalty</li>
                                                <li>• Transfer contract to family member</li>
                                            </ul>
                                        </div>
                                        <div>
                                            <h5 class="font-medium text-navy mb-3">Subscriber Obligations:</h5>
                                            <ul class="space-y-1 text-sm text-navy/80">
                                                <li>• Timely monthly payments</li>
                                                <li>• Notify of address changes</li>
                                                <li>• Provide accurate health information</li>
                                                <li>• Maintain updated beneficiary details</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-blue-50 border border-blue-200 rounded-xl p-6">
                                    <h4 class="font-semibold text-navy mb-4 flex items-center">
                                        <i class="fas fa-file-contract text-blue-600 mr-2"></i>
                                        Contract Modification & Cancellation
                                    </h4>
                                    <div class="space-y-3 text-navy/80">
                                        <p><strong>Modification:</strong> Contract terms may be modified with mutual written consent of both parties</p>
                                        <p><strong>Cancellation:</strong> Subscriber may cancel within 15 days of signing with full refund of payments made</p>
                                        <p><strong>Voluntary Termination:</strong> After 15 days, cancellation results in forfeiture of payments made, but contract remains valid for death benefits</p>
                                        <p><strong>Company Termination:</strong> Only possible for non-payment exceeding 90 days or material breach of contract terms</p>
                                    </div>
                                </div>

                                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                                    <h4 class="font-semibold text-navy mb-4 flex items-center">
                                        <i class="fas fa-gavel text-yellow-600 mr-2"></i>
                                        Dispute Resolution
                                    </h4>
                                    <div class="space-y-3 text-navy/80">
                                        <p><strong>Governing Law:</strong> This contract is governed by the laws of the Republic of the Philippines</p>
                                        <p><strong>Jurisdiction:</strong> All disputes shall be resolved in the courts of Metro Manila</p>
                                        <p><strong>Mediation:</strong> Parties agree to attempt mediation before pursuing legal action</p>
                                        <p><strong>Arbitration:</strong> If mediation fails, binding arbitration may be pursued as agreed by both parties</p>
                                    </div>
                                </div>

                                <div class="bg-green-50 border border-green-200 rounded-xl p-6">
                                    <h4 class="font-semibold text-navy mb-4 flex items-center">
                                        <i class="fas fa-shield-alt text-green-600 mr-2"></i>
                                        Consumer Protection
                                    </h4>
                                    <div class="space-y-3 text-navy/80">
                                        <p><strong>Regulatory Compliance:</strong> This contract complies with Insurance Commission regulations and DTI consumer protection guidelines</p>
                                        <p><strong>Transparency:</strong> All fees, charges, and terms are disclosed upfront with no hidden costs</p>
                                        <p><strong>Data Privacy:</strong> All personal information is protected under the Data Privacy Act of 2012</p>
                                        <p><strong>Complaint Mechanism:</strong> Free complaint hotline and online dispute resolution available</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-navy text-white p-8">
                    <div class="text-center">
                        <div class="flex items-center justify-center mb-4">
                            <i class="fas fa-certificate text-gold text-2xl mr-3"></i>
                            <span class="font-cinzel text-xl">LifePlan Funeral Services</span>
                        </div>
                        <p class="text-gray-300 mb-4">Providing dignity, comfort, and peace of mind for Filipino families since 1995</p>
                        <div class="section-divider mb-4"></div>
                        <div class="grid md:grid-cols-3 gap-6 text-sm">
                            <div>
                                <h5 class="font-semibold mb-2 text-gold">Contact Information</h5>
                                <p class="text-gray-300">123 Memorial Drive, Quezon City</p>
                                <p class="text-gray-300">Phone: (02) 8-FUNERAL</p>
                                <p class="text-gray-300">Email: info@lifeplan.com.ph</p>
                            </div>
                            <div>
                                <h5 class="font-semibold mb-2 text-gold">Business Hours</h5>
                                <p class="text-gray-300">Monday - Friday: 8:00 AM - 6:00 PM</p>
                                <p class="text-gray-300">Saturday: 9:00 AM - 4:00 PM</p>
                                <p class="text-gray-300">Emergency: 24/7 Available</p>
                            </div>
                            <div>
                                <h5 class="font-semibold mb-2 text-gold">Legal Compliance</h5>
                                <p class="text-gray-300">SEC Registration: CS200012345</p>
                                <p class="text-gray-300">DTI Permit: 12345678</p>
                                <p class="text-gray-300">Insurance Commission Licensed</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information Cards -->
            <div class="mt-12 grid md:grid-cols-2 gap-8 fade-in">
                <div class="bg-white rounded-xl shadow-card p-6 hover-lift">
                    <div class="flex items-center mb-4">
                        <div class="bg-success/20 p-3 rounded-full mr-4">
                            <i class="fas fa-headset text-success text-xl"></i>
                        </div>
                        <h3 class="font-playfair text-xl font-semibold text-navy">24/7 Support</h3>
                    </div>
                    <p class="text-navy/80 mb-4">Our dedicated support team is available round the clock to assist you with any questions or concerns about your LifePlan contract.</p>
                    <div class="bg-cream rounded-lg p-4">
                        <p class="font-medium text-navy mb-2">Emergency Hotline</p>
                        <p class="text-2xl font-bold text-success">(02) 8-FUNERAL</p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-card p-6 hover-lift">
                    <div class="flex items-center mb-4">
                        <div class="bg-gold/20 p-3 rounded-full mr-4">
                            <i class="fas fa-mobile-alt text-gold text-xl"></i>
                        </div>
                        <h3 class="font-playfair text-xl font-semibold text-navy">Mobile App</h3>
                    </div>
                    <p class="text-navy/80 mb-4">Download our mobile app to track payments, update beneficiary information, and access emergency services with just a tap.</p>
                    <div class="flex space-x-3">
                        <div class="bg-navy text-white px-4 py-2 rounded-lg text-sm font-medium">
                            <i class="fab fa-apple mr-2"></i>App Store
                        </div>
                        <div class="bg-navy text-white px-4 py-2 rounded-lg text-sm font-medium">
                            <i class="fab fa-google-play mr-2"></i>Play Store
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        function showSection(sectionName) {
            // Hide all sections
            const sections = document.querySelectorAll('.section');
            sections.forEach(section => {
                section.classList.add('hidden');
            });

            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab-btn');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected section
            document.getElementById(sectionName + '-section').classList.remove('hidden');
            
            // Add active class to clicked tab
            document.getElementById(sectionName + '-tab').classList.add('active');
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Set up tab styling
            const style = document.createElement('style');
            style.textContent = `
                .tab-btn {
                    color: #2D2B30;
                    background-color: transparent;
                }
                .tab-btn:hover {
                    background-color: #F9F6F0;
                    color: #C9A773;
                }
                .tab-btn.active {
                    background-color: #C9A773;
                    color: white;
                    font-weight: 600;
                }
            `;
            document.head.appendChild(style);

            // Add smooth animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe all cards for animation
            document.querySelectorAll('.hover-lift').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                observer.observe(card);
            });

            // Add click animations
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 100);
                });
            });

            // Show payment schedule calculator on payment tab
            const paymentTab = document.getElementById('payment-tab');
            paymentTab.addEventListener('click', function() {
                setTimeout(() => {
                    const paymentSection = document.getElementById('payment-section');
                    if (paymentSection && !paymentSection.classList.contains('hidden')) {
                        animateNumbers();
                    }
                }, 100);
            });
        });

        // Animate numbers when payment section is shown
        function animateNumbers() {
            const numbers = document.querySelectorAll('#payment-section .text-2xl');
            numbers.forEach(num => {
                const finalValue = num.textContent;
                if (finalValue.includes('₱') || finalValue.includes('%')) {
                    num.textContent = '0';
                    let current = 0;
                    const target = parseInt(finalValue.replace(/[₱,%]/g, ''));
                    const increment = target / 50;
                    const timer = setInterval(() => {
                        current += increment;
                        if (current >= target) {
                            num.textContent = finalValue;
                            clearInterval(timer);
                        } else {
                            if (finalValue.includes('₱')) {
                                num.textContent = '₱' + Math.floor(current).toLocaleString();
                            } else if (finalValue.includes('%')) {
                                num.textContent = Math.floor(current) + '%';
                            } else {
                                num.textContent = Math.floor(current);
                            }
                        }
                    }, 20);
                }
            });
        }

        // Add print functionality
        function printContract() {
            window.print();
        }

        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            const tabs = ['overview', 'payment', 'death-benefits', 'comaker', 'legal'];
            const currentTab = document.querySelector('.tab-btn.active').id.replace('-tab', '');
            const currentIndex = tabs.indexOf(currentTab);

            if (e.key === 'ArrowRight' && currentIndex < tabs.length - 1) {
                showSection(tabs[currentIndex + 1]);
            } else if (e.key === 'ArrowLeft' && currentIndex > 0) {
                showSection(tabs[currentIndex - 1]);
            }
        });
    </script>
</body>
</html>