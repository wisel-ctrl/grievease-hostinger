<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - Funeral Service Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Alex+Brush&family=Inter:wght@300;400;500;600;700&family=Cinzel:wght@400;500;600&family=Hedvig+Letters+Serif:opsz,wght@12..24,400&display=swap" rel="stylesheet">
    
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
                        'navy': '#F0F4F8',
                        'cream': '#F9F6F0',
                        'dark': '#4A5568',
                        'gold': '#D69E2E',
                        'darkgold': '#B7791F',
                        'primary': '#F8FAFC',
                        'primary-foreground': '#334155',
                        'secondary': '#F1F5F9',
                        'secondary-foreground': '#334155',
                        'border': '#E4E9F0',
                        'input-border': '#D3D8E1',
                        'error': '#E53E3E',
                        'success': '#38A169',
                        'sidebar-bg': '#FFFFFF',
                        'sidebar-hover': '#F1F5F9',
                        'sidebar-text': '#334155',
                        'sidebar-accent': '#CA8A04',
                        'sidebar-border': '#E2E8F0',
                    },
                    boxShadow: {
                        'input': '0 1px 2px rgba(0, 0, 0, 0.05)',
                        'card': '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)',
                        'sidebar': '0 0 15px rgba(0, 0, 0, 0.05)'
                    }
                }
            }
        }
    </script>
    <style>
        .notification-enter {
            animation: slideInFromRight 0.3s ease-out;
        }
        
        @keyframes slideInFromRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .loading-skeleton {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        .button-loading {
            pointer-events: none;
            opacity: 0.7;
        }
        
        .ripple-effect {
            position: relative;
            overflow: hidden;
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            transform: scale(0);
            animation: ripple 0.6s linear;
            background-color: rgba(255, 255, 255, 0.3);
        }
        
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .focus-visible:focus {
            outline: 2px solid #CA8A04;
            outline-offset: 2px;
        }
        
        .notification-priority-high {
            border-left-width: 4px;
            animation: pulse-border 2s infinite;
        }
        
        @keyframes pulse-border {
            0%, 100% { border-left-color: #EF4444; }
            50% { border-left-color: #FCA5A5; }
        }
    </style>
</head>
<body class="bg-primary font-inter">
    <!-- Header -->
    <header class="bg-sidebar-bg shadow-sidebar border-b border-sidebar-border sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <button 
                        onclick="history.back()" 
                        class="mr-4 p-2 rounded-lg bg-sidebar-hover text-sidebar-text hover:bg-border transition-all duration-200 focus-visible:focus transform hover:scale-105"
                        aria-label="Go back to previous page"
                        title="Go back"
                    >
                        <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    </button>
                    <div>
                        <h1 class="text-2xl font-semibold text-sidebar-text font-playfair">Notifications</h1>
                        <p class="text-sm text-gray-600 mt-1">Manage all system notifications and bookings</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <span class="bg-sidebar-accent text-white text-sm px-3 py-1 rounded-full font-medium shadow-sm animate-pulse">
                            <span id="total-notifications">7</span> Pending
                        </span>
                    </div>
                    <button 
                        onclick="showHelpModal()"
                        class="p-2 rounded-lg text-gray-400 hover:text-sidebar-accent hover:bg-sidebar-hover transition-all duration-200 focus-visible:focus"
                        aria-label="Help and information"
                        title="Need help?"
                    >
                        <i class="fas fa-question-circle text-lg" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-sidebar-bg rounded-lg p-6 shadow-card">
            <div class="flex items-center space-x-3">
                <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-sidebar-accent"></div>
                <span class="text-sidebar-text">Processing...</span>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
        <!-- Quick Actions Bar -->
        <div class="mb-6 bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border p-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex flex-wrap items-center gap-2">
                    <button 
                        onclick="markAllAsRead()" 
                        class="ripple-effect flex items-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                        title="Mark all notifications as read"
                    >
                        <i class="fas fa-check-double mr-2" aria-hidden="true"></i>
                        Mark All Read
                    </button>
                    <button 
                        onclick="refreshNotifications()" 
                        class="ripple-effect flex items-center px-4 py-2 bg-sidebar-hover text-sidebar-text rounded-lg hover:bg-border transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                        title="Refresh notifications"
                    >
                        <i class="fas fa-sync-alt mr-2" aria-hidden="true"></i>
                        Refresh
                    </button>
                    
                </div>
                <div class="flex items-center space-x-3">
                    <label for="sort-select" class="text-sm text-gray-600 font-medium">Sort by:</label>
                    <select 
                        id="sort-select"
                        onchange="sortNotifications(this.value)"
                        class="px-3 py-2 border border-input-border rounded-lg text-sm bg-sidebar-bg text-sidebar-text focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-sidebar-accent transition-all duration-200"
                        aria-label="Sort notifications"
                    >
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="priority">High Priority</option>
                        <option value="type">By Type</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="mb-8">
            <div class="border-b border-sidebar-border">
                <nav class="-mb-px flex space-x-1 overflow-x-auto" role="tablist">
                    <button 
                        onclick="filterNotifications('all')" 
                        class="filter-tab active whitespace-nowrap pb-4 px-4 border-b-2 font-medium text-sm transition-all duration-200 focus-visible:focus"
                        role="tab"
                        aria-selected="true"
                        title="View all notifications"
                    >
                        <i class="fas fa-list mr-2" aria-hidden="true"></i>
                        All Notifications
                        <span class="ml-2 bg-sidebar-accent text-white text-xs rounded-full px-2 py-0.5">7</span>
                    </button>
                    <button 
                        onclick="filterNotifications('funeral')" 
                        class="filter-tab whitespace-nowrap pb-4 px-4 border-b-2 font-medium text-sm transition-all duration-200 focus-visible:focus"
                        role="tab"
                        aria-selected="false"
                        title="View funeral booking notifications"
                    >
                        <i class="fas fa-cross mr-2" aria-hidden="true"></i>
                        Funeral Bookings
                        <span class="ml-2 bg-blue-500 text-white text-xs rounded-full px-2 py-0.5">3</span>
                    </button>
                    <button 
                        onclick="filterNotifications('lifeplan')" 
                        class="filter-tab whitespace-nowrap pb-4 px-4 border-b-2 font-medium text-sm transition-all duration-200 focus-visible:focus"
                        role="tab"
                        aria-selected="false"
                        title="View life plan booking notifications"
                    >
                        <i class="fas fa-heart mr-2" aria-hidden="true"></i>
                        Life Plan Bookings
                        <span class="ml-2 bg-purple-500 text-white text-xs rounded-full px-2 py-0.5">2</span>
                    </button>
                    <button 
                        onclick="filterNotifications('validation')" 
                        class="filter-tab whitespace-nowrap pb-4 px-4 border-b-2 font-medium text-sm transition-all duration-200 focus-visible:focus"
                        role="tab"
                        aria-selected="false"
                        title="View ID validation notifications"
                    >
                        <i class="fas fa-id-card mr-2" aria-hidden="true"></i>
                        ID Validations
                        <span class="ml-2 bg-yellow-500 text-white text-xs rounded-full px-2 py-0.5">2</span>
                    </button>
                </nav>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="mb-6 bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border p-4">
            <div class="flex flex-col md:flex-row gap-4">
                <div class="flex-1 relative">
                    <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                    <input 
                        type="text" 
                        placeholder="Search notifications..." 
                        class="w-full pl-10 pr-4 py-2 border border-input-border rounded-lg text-sm bg-sidebar-bg text-sidebar-text focus:outline-none focus:ring-2 focus:ring-sidebar-accent focus:border-sidebar-accent transition-all duration-200"
                        oninput="searchNotifications(this.value)"
                        aria-label="Search notifications"
                    >
                </div>
                <div class="flex items-center space-x-2">
                    <label class="flex items-center space-x-2 text-sm">
                        <input 
                            type="checkbox" 
                            onchange="toggleUnreadOnly(this.checked)"
                            class="rounded border-input-border text-sidebar-accent focus:ring-sidebar-accent focus:ring-2"
                        >
                        <span>Unread only</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div id="empty-state" class="hidden text-center py-12">
            <div class="bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border p-8">
                <i class="fas fa-bell-slash text-6xl text-gray-300 mb-4" aria-hidden="true"></i>
                <h3 class="text-lg font-semibold text-sidebar-text mb-2">No notifications found</h3>
                <p class="text-gray-600 mb-4">Try adjusting your filters or check back later for new notifications.</p>
                <button 
                    onclick="resetFilters()"
                    class="px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-colors"
                >
                    Reset Filters
                </button>
            </div>
        </div>

        <!-- Notifications List -->
        <div id="notifications-container" class="space-y-4">
            <!-- High Priority Funeral Booking -->
            <div class="notification-item funeral notification-priority-high bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1" data-priority="high" data-timestamp="1710504000">
                <div class="flex items-start p-6">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-red-500"></div>
                    <div class="flex-shrink-0 bg-red-100 rounded-full p-3 mr-4">
                        <i class="fas fa-cross text-red-600 text-lg" aria-hidden="true"></i>
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-grow">
                                <div class="flex items-center gap-2 mb-2">
                                    <h3 class="text-lg font-semibold text-sidebar-text">URGENT: Funeral Booking Request</h3>
                                    <span class="bg-red-100 text-red-800 text-xs font-medium px-2 py-1 rounded-full">High Priority</span>
                                </div>
                                <p class="text-sidebar-text">Smith, John Michael - Premium Funeral Package</p>
                                <p class="text-sm text-gray-600 mt-1">Requires immediate attention due to service date proximity</p>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                <span class="h-3 w-3 bg-red-600 rounded-full animate-pulse" aria-label="Unread notification"></span>
                                <span class="text-xs text-gray-500">Unread</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center text-sm text-gray-600 mb-4 gap-4">
                            <div class="flex items-center">
                                <i class="far fa-clock mr-2" aria-hidden="true"></i>
                                <span>2 hours ago</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-calendar mr-2" aria-hidden="true"></i>
                                <span>Service Date: March 15, 2025</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-user mr-2" aria-hidden="true"></i>
                                <span>Contact: (555) 123-4567</span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    onclick="viewBooking('funeral', 1)" 
                                    class="ripple-effect px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="View detailed booking information"
                                >
                                    <i class="fas fa-eye mr-2" aria-hidden="true"></i>View Details
                                </button>
                                <button 
                                    onclick="approveBooking('funeral', 1, this)" 
                                    class="ripple-effect px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="Approve this booking"
                                >
                                    <i class="fas fa-check mr-2" aria-hidden="true"></i>Approve
                                </button>
                                <button 
                                    onclick="rejectBooking('funeral', 1, this)" 
                                    class="ripple-effect px-4 py-2 bg-error text-white rounded-lg hover:bg-red-600 transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="Decline this booking"
                                >
                                    <i class="fas fa-times mr-2" aria-hidden="true"></i>Decline
                                </button>
                            </div>
                            <button 
                                onclick="markAsRead(this)" 
                                class="text-gray-400 hover:text-sidebar-accent transition-colors p-2 rounded focus-visible:focus"
                                title="Mark as read"
                                aria-label="Mark notification as read"
                            >
                                <i class="fas fa-check-circle text-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Life Plan Booking -->
            <div class="notification-item lifeplan bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1" data-priority="medium" data-timestamp="1710490800">
                <div class="flex items-start p-6">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-purple-500"></div>
                    <div class="flex-shrink-0 bg-purple-100 rounded-full p-3 mr-4">
                        <i class="fas fa-heart text-purple-600 text-lg" aria-hidden="true"></i>
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-grow">
                                <h3 class="text-lg font-semibold text-sidebar-text">New Life Plan Booking Request</h3>
                                <p class="text-sidebar-text mt-1">Johnson, Mary Elizabeth - Complete Life Plan Package</p>
                                <p class="text-sm text-gray-600 mt-1">Monthly payment plan enrollment</p>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                <span class="h-3 w-3 bg-purple-600 rounded-full" aria-label="Unread notification"></span>
                                <span class="text-xs text-gray-500">Unread</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center text-sm text-gray-600 mb-4 gap-4">
                            <div class="flex items-center">
                                <i class="far fa-clock mr-2" aria-hidden="true"></i>
                                <span>4 hours ago</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-dollar-sign mr-2" aria-hidden="true"></i>
                                <span>Monthly Plan: $250</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-envelope mr-2" aria-hidden="true"></i>
                                <span>mary.johnson@email.com</span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    onclick="viewBooking('lifeplan', 1)" 
                                    class="ripple-effect px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="View detailed booking information"
                                >
                                    <i class="fas fa-eye mr-2" aria-hidden="true"></i>View Details
                                </button>
                                <button 
                                    onclick="approveBooking('lifeplan', 1, this)" 
                                    class="ripple-effect px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="Approve this booking"
                                >
                                    <i class="fas fa-check mr-2" aria-hidden="true"></i>Approve
                                </button>
                                <button 
                                    onclick="rejectBooking('lifeplan', 1, this)" 
                                    class="ripple-effect px-4 py-2 bg-error text-white rounded-lg hover:bg-red-600 transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="Decline this booking"
                                >
                                    <i class="fas fa-times mr-2" aria-hidden="true"></i>Decline
                                </button>
                            </div>
                            <button 
                                onclick="markAsRead(this)" 
                                class="text-gray-400 hover:text-sidebar-accent transition-colors p-2 rounded focus-visible:focus"
                                title="Mark as read"
                                aria-label="Mark notification as read"
                            >
                                <i class="fas fa-check-circle text-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ID Validation -->
            <div class="notification-item validation bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1" data-priority="medium" data-timestamp="1710418800">
                <div class="flex items-start p-6">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-yellow-500"></div>
                    <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3 mr-4">
                        <i class="fas fa-id-card text-yellow-600 text-lg" aria-hidden="true"></i>
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-grow">
                                <h3 class="text-lg font-semibold text-sidebar-text">ID Validation Required</h3>
                                <p class="text-sidebar-text mt-1">New identification document uploaded for verification</p>
                                <p class="text-sm text-gray-600 mt-1">Driver's License - Expires in review queue after 7 days</p>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                <span class="h-3 w-3 bg-yellow-600 rounded-full" aria-label="Unread notification"></span>
                                <span class="text-xs text-gray-500">Unread</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center text-sm text-gray-600 mb-4 gap-4">
                            <div class="flex items-center">
                                <i class="far fa-clock mr-2" aria-hidden="true"></i>
                                <span>1 day ago</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-user mr-2" aria-hidden="true"></i>
                                <span>User ID: #12345</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-file-alt mr-2" aria-hidden="true"></i>
                                <span>Document Type: Driver's License</span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    onclick="viewValidation(1)" 
                                    class="ripple-effect px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="Review uploaded ID document"
                                >
                                    <i class="fas fa-eye mr-2" aria-hidden="true"></i>Review ID
                                </button>
                                <button 
                                    onclick="approveValidation(1, this)" 
                                    class="ripple-effect px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="Validate this ID"
                                >
                                    <i class="fas fa-check mr-2" aria-hidden="true"></i>Validate
                                </button>
                                <button 
                                    onclick="rejectValidation(1, this)" 
                                    class="ripple-effect px-4 py-2 bg-error text-white rounded-lg hover:bg-red-600 transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="Reject this ID"
                                >
                                    <i class="fas fa-times mr-2" aria-hidden="true"></i>Reject
                                </button>
                            </div>
                            <button 
                                onclick="markAsRead(this)" 
                                class="text-gray-400 hover:text-sidebar-accent transition-colors p-2 rounded focus-visible:focus"
                                title="Mark as read"
                                aria-label="Mark notification as read"
                            >
                                <i class="fas fa-check-circle text-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Regular Funeral Booking -->
            <div class="notification-item funeral bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1" data-priority="low" data-timestamp="1710332400">
                <div class="flex items-start p-6">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
                    <div class="flex-shrink-0 bg-blue-100 rounded-full p-3 mr-4">
                        <i class="fas fa-cross text-blue-600 text-lg" aria-hidden="true"></i>
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-grow">
                                <h3 class="text-lg font-semibold text-sidebar-text">Funeral Booking Update Required</h3>
                                <p class="text-sidebar-text mt-1">Garcia, Roberto Carlos - Basic Funeral Package</p>
                                <p class="text-sm text-gray-600 mt-1">Client requested additional services - awaiting approval</p>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                <span class="h-3 w-3 bg-blue-600 rounded-full" aria-label="Unread notification"></span>
                                <span class="text-xs text-gray-500">Unread</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center text-sm text-gray-600 mb-4 gap-4">
                            <div class="flex items-center">
                                <i class="far fa-clock mr-2" aria-hidden="true"></i>
                                <span>2 days ago</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-exclamation-triangle mr-2" aria-hidden="true"></i>
                                <span>Requires additional information</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-phone mr-2" aria-hidden="true"></i>
                                <span>(555) 987-6543</span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    onclick="viewBooking('funeral', 2)" 
                                    class="ripple-effect px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="View detailed booking information"
                                >
                                    <i class="fas fa-eye mr-2" aria-hidden="true"></i>View Details
                                </button>
                                <button 
                                    onclick="contactClient(2)" 
                                    class="ripple-effect px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="Contact client directly"
                                >
                                    <i class="fas fa-phone mr-2" aria-hidden="true"></i>Contact
                                </button>
                                <button 
                                    onclick="scheduleCallback(2)" 
                                    class="ripple-effect px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="Schedule a callback"
                                >
                                    <i class="fas fa-calendar mr-2" aria-hidden="true"></i>Schedule Call
                                </button>
                            </div>
                            <button 
                                onclick="markAsRead(this)" 
                                class="text-gray-400 hover:text-sidebar-accent transition-colors p-2 rounded focus-visible:focus"
                                title="Mark as read"
                                aria-label="Mark notification as read"
                            >
                                <i class="fas fa-check-circle text-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Life Plan Notification -->
            <div class="notification-item lifeplan bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1" data-priority="low" data-timestamp="1710246000">
                <div class="flex items-start p-6">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-purple-500"></div>
                    <div class="flex-shrink-0 bg-purple-100 rounded-full p-3 mr-4">
                        <i class="fas fa-heart text-purple-600 text-lg" aria-hidden="true"></i>
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-grow">
                                <h3 class="text-lg font-semibold text-sidebar-text">Life Plan Payment Confirmation</h3>
                                <p class="text-sidebar-text mt-1">Williams, David Michael - Premium Life Plan</p>
                                <p class="text-sm text-gray-600 mt-1">First payment received - activation pending</p>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                <span class="h-3 w-3 bg-gray-300 rounded-full" aria-label="Read notification"></span>
                                <span class="text-xs text-gray-500">Read</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center text-sm text-gray-600 mb-4 gap-4">
                            <div class="flex items-center">
                                <i class="far fa-clock mr-2" aria-hidden="true"></i>
                                <span>3 days ago</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-2 text-success" aria-hidden="true"></i>
                                <span>Payment Received: $350</span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    onclick="activateLifePlan(3)" 
                                    class="ripple-effect px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="Activate life plan"
                                >
                                    <i class="fas fa-play mr-2" aria-hidden="true"></i>Activate Plan
                                </button>
                                <button 
                                    onclick="viewBooking('lifeplan', 3)" 
                                    class="ripple-effect px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                >
                                    <i class="fas fa-eye mr-2" aria-hidden="true"></i>View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second ID Validation -->
            <div class="notification-item validation bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1" data-priority="medium" data-timestamp="1710159600">
                <div class="flex items-start p-6">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-yellow-500"></div>
                    <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3 mr-4">
                        <i class="fas fa-id-card text-yellow-600 text-lg" aria-hidden="true"></i>
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-grow">
                                <h3 class="text-lg font-semibold text-sidebar-text">ID Validation - Document Resubmitted</h3>
                                <p class="text-sidebar-text mt-1">Previous document was rejected - new upload received</p>
                                <p class="text-sm text-gray-600 mt-1">Passport - Second submission attempt</p>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                <span class="h-3 w-3 bg-yellow-600 rounded-full" aria-label="Unread notification"></span>
                                <span class="text-xs text-gray-500">Unread</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center text-sm text-gray-600 mb-4 gap-4">
                            <div class="flex items-center">
                                <i class="far fa-clock mr-2" aria-hidden="true"></i>
                                <span>4 days ago</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-redo mr-2" aria-hidden="true"></i>
                                <span>Resubmission</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-user mr-2" aria-hidden="true"></i>
                                <span>User ID: #67890</span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    onclick="viewValidation(2)" 
                                    class="ripple-effect px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                >
                                    <i class="fas fa-eye mr-2" aria-hidden="true"></i>Review ID
                                </button>
                                <button 
                                    onclick="compareDocuments(2)" 
                                    class="ripple-effect px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="Compare with previous submission"
                                >
                                    <i class="fas fa-balance-scale mr-2" aria-hidden="true"></i>Compare
                                </button>
                                <button 
                                    onclick="approveValidation(2, this)" 
                                    class="ripple-effect px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                >
                                    <i class="fas fa-check mr-2" aria-hidden="true"></i>Validate
                                </button>
                            </div>
                            <button 
                                onclick="markAsRead(this)" 
                                class="text-gray-400 hover:text-sidebar-accent transition-colors p-2 rounded focus-visible:focus"
                                title="Mark as read"
                                aria-label="Mark notification as read"
                            >
                                <i class="fas fa-check-circle text-lg" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Funeral Booking -->
            <div class="notification-item funeral bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border overflow-hidden hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1" data-priority="low" data-timestamp="1710073200">
                <div class="flex items-start p-6">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
                    <div class="flex-shrink-0 bg-blue-100 rounded-full p-3 mr-4">
                        <i class="fas fa-cross text-blue-600 text-lg" aria-hidden="true"></i>
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex-grow">
                                <h3 class="text-lg font-semibold text-sidebar-text">Funeral Service Completed</h3>
                                <p class="text-sidebar-text mt-1">Thompson, Sarah Jane - Standard Funeral Package</p>
                                <p class="text-sm text-gray-600 mt-1">Service completed successfully - feedback requested</p>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                <span class="h-3 w-3 bg-gray-300 rounded-full" aria-label="Read notification"></span>
                                <span class="text-xs text-gray-500">Read</span>
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center text-sm text-gray-600 mb-4 gap-4">
                            <div class="flex items-center">
                                <i class="far fa-clock mr-2" aria-hidden="true"></i>
                                <span>5 days ago</span>
                            </div>
                            <div class="flex items-center">
                                <i class="fas fa-check-circle mr-2 text-success" aria-hidden="true"></i>
                                <span>Service Completed</span>
                            </div>
                        </div>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                            <div class="flex flex-wrap gap-2">
                                <button 
                                    onclick="sendFeedbackForm(4)" 
                                    class="ripple-effect px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="Send feedback form to family"
                                >
                                    <i class="fas fa-comment mr-2" aria-hidden="true"></i>Send Feedback
                                </button>
                                <button 
                                    onclick="generateReport(4)" 
                                    class="ripple-effect px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-all duration-200 text-sm font-medium transform hover:scale-105 focus-visible:focus"
                                    title="Generate service report"
                                >
                                    <i class="fas fa-file-alt mr-2" aria-hidden="true"></i>Report
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Load More Button -->
        <div class="text-center mt-8">
            <button 
                onclick="loadMoreNotifications()" 
                class="ripple-effect px-6 py-3 bg-sidebar-hover text-sidebar-text rounded-lg hover:bg-border transition-all duration-200 font-medium transform hover:scale-105 focus-visible:focus"
                id="load-more-btn"
                title="Load additional notifications"
            >
                <i class="fas fa-chevron-down mr-2" aria-hidden="true"></i>
                Load More Notifications
            </button>
        </div>

        <!-- Pagination Info -->
        <div class="text-center mt-4 text-sm text-gray-600">
            Showing <span id="current-count">7</span> of <span id="total-count">15</span> notifications
        </div>
    </main>

    <!-- Help Modal -->
    <div id="help-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-sidebar-bg rounded-lg shadow-card max-w-2xl mx-4 max-h-96 overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold text-sidebar-text">Notification Help</h3>
                    <button 
                        onclick="closeHelpModal()" 
                        class="text-gray-400 hover:text-sidebar-accent p-1 rounded focus-visible:focus"
                        aria-label="Close help modal"
                    >
                        <i class="fas fa-times text-lg" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="space-y-4 text-sm text-gray-700">
                    <div>
                        <h4 class="font-semibold text-sidebar-text mb-2">Notification Types</h4>
                        <ul class="space-y-2">
                            <li class="flex items-center"><span class="w-3 h-3 bg-blue-500 rounded-full mr-3"></span>Funeral Bookings - New service requests and updates</li>
                            <li class="flex items-center"><span class="w-3 h-3 bg-purple-500 rounded-full mr-3"></span>Life Plan Bookings - Insurance and payment plan requests</li>
                            <li class="flex items-center"><span class="w-3 h-3 bg-yellow-500 rounded-full mr-3"></span>ID Validations - Document verification requests</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-sidebar-text mb-2">Priority Levels</h4>
                        <ul class="space-y-2">
                            <li class="flex items-center"><span class="w-3 h-3 bg-red-500 rounded-full mr-3"></span>High Priority - Urgent actions required</li>
                            <li class="flex items-center"><span class="w-3 h-3 bg-orange-500 rounded-full mr-3"></span>Medium Priority - Important but not urgent</li>
                            <li class="flex items-center"><span class="w-3 h-3 bg-green-500 rounded-full mr-3"></span>Low Priority - Standard processing</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-sidebar-text mb-2">Quick Actions</h4>
                        <p>Use keyboard shortcuts: <kbd class="bg-gray-100 px-2 py-1 rounded text-xs">Ctrl+A</kbd> to mark all as read, <kbd class="bg-gray-100 px-2 py-1 rounded text-xs">R</kbd> to refresh</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmation-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-sidebar-bg rounded-lg shadow-card max-w-md mx-4">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <i class="fas fa-question-circle text-sidebar-accent text-2xl mr-3" aria-hidden="true"></i>
                    <h3 class="text-lg font-semibold text-sidebar-text">Confirm Action</h3>
                </div>
                <p class="text-gray-700 mb-6" id="confirmation-message">Are you sure you want to perform this action?</p>
                <div class="flex justify-end space-x-3">
                    <button 
                        onclick="closeConfirmationModal()" 
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors focus-visible:focus"
                    >
                        Cancel
                    </button>
                    <button 
                        id="confirm-action-btn"
                        class="px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-colors focus-visible:focus"
                    >
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed top-4 right-4 bg-success text-white px-6 py-4 rounded-lg shadow-card transform translate-x-full transition-transform duration-300 z-50">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-3" aria-hidden="true"></i>
            <span id="toast-message">Action completed successfully</span>
            <button onclick="closeToast()" class="ml-4 text-white hover:text-gray-200" aria-label="Close notification">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <script>
        // Global variables
        let currentFilter = 'all';
        let currentSort = 'newest';
        let searchQuery = '';
        let unreadOnly = false;
        let confirmationCallback = null;

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            initializeEventListeners();
            updateNotificationCounts();
            
            // Set up keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'a') {
                    e.preventDefault();
                    markAllAsRead();
                } else if (e.key === 'r' || e.key === 'R') {
                    if (!e.ctrlKey && !e.altKey) {
                        e.preventDefault();
                        refreshNotifications();
                    }
                }
            });
        });

        // Initialize event listeners
        function initializeEventListeners() {
            // Add ripple effect to buttons
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('ripple-effect') || e.target.closest('.ripple-effect')) {
                    createRipple(e);
                }
            });
        }

        // Create ripple effect
        function createRipple(event) {
            const button = event.target.closest('.ripple-effect') || event.target;
            const circle = document.createElement("span");
            const diameter = Math.max(button.clientWidth, button.clientHeight);
            const radius = diameter / 2;
            
            const rect = button.getBoundingClientRect();
            circle.style.width = circle.style.height = diameter + "px";
            circle.style.left = (event.clientX - rect.left - radius) + "px";
            circle.style.top = (event.clientY - rect.top - radius) + "px";
            circle.classList.add("ripple");
            
            const existingRipple = button.getElementsByClassName("ripple")[0];
            if (existingRipple) {
                existingRipple.remove();
            }
            
            button.appendChild(circle);
        }

        // Filter functionality
        function filterNotifications(type) {
            currentFilter = type;
            const items = document.querySelectorAll('.notification-item');
            const tabs = document.querySelectorAll('.filter-tab');
            
            // Update active tab
            tabs.forEach(tab => {
                tab.classList.remove('active', 'border-sidebar-accent', 'text-sidebar-accent');
                tab.classList.add('border-transparent', 'text-gray-500', 'hover:text-sidebar-text', 'hover:border-gray-300');
                tab.setAttribute('aria-selected', 'false');
            });
            
            event.target.classList.add('active', 'border-sidebar-accent', 'text-sidebar-accent');
            event.target.classList.remove('border-transparent', 'text-gray-500', 'hover:text-sidebar-text', 'hover:border-gray-300');
            event.target.setAttribute('aria-selected', 'true');
            
            applyFilters();
        }

        // Apply all filters
        function applyFilters() {
            const items = document.querySelectorAll('.notification-item');
            let visibleCount = 0;
            
            items.forEach(item => {
                let show = true;
                
                // Filter by type
                if (currentFilter !== 'all' && !item.classList.contains(currentFilter)) {
                    show = false;
                }
                
                // Filter by unread only
                if (unreadOnly) {
                    const isUnread = item.querySelector('.h-3.w-3').classList.contains('bg-blue-600') ||
                                   item.querySelector('.h-3.w-3').classList.contains('bg-purple-600') ||
                                   item.querySelector('.h-3.w-3').classList.contains('bg-yellow-600') ||
                                   item.querySelector('.h-3.w-3').classList.contains('bg-red-600');
                    if (!isUnread) show = false;
                }
                
                // Filter by search query
                if (searchQuery) {
                    const text = item.textContent.toLowerCase();
                    if (!text.includes(searchQuery.toLowerCase())) {
                        show = false;
                    }
                }
                
                if (show) {
                    item.style.display = 'block';
                    item.classList.add('notification-enter');
                    visibleCount++;
                } else {
                    item.style.display = 'none';
                    item.classList.remove('notification-enter');
                }
            });
            
            // Show/hide empty state
            const emptyState = document.getElementById('empty-state');
            const notificationsContainer = document.getElementById('notifications-container');
            
            if (visibleCount === 0) {
                emptyState.classList.remove('hidden');
                notificationsContainer.classList.add('hidden');
            } else {
                emptyState.classList.add('hidden');
                notificationsContainer.classList.remove('hidden');
            }
            
            document.getElementById('current-count').textContent = visibleCount;
        }

        // Search functionality
        function searchNotifications(query) {
            searchQuery = query;
            applyFilters();
        }

        // Toggle unread only
        function toggleUnreadOnly(checked) {
            unreadOnly = checked;
            applyFilters();
        }

        // Sort notifications
        function sortNotifications(sortType) {
            currentSort = sortType;
            const container = document.getElementById('notifications-container');
            const items = Array.from(container.children);
            
            items.sort((a, b) => {
                switch (sortType) {
                    case 'newest':
                        return parseInt(b.dataset.timestamp) - parseInt(a.dataset.timestamp);
                    case 'oldest':
                        return parseInt(a.dataset.timestamp) - parseInt(b.dataset.timestamp);
                    case 'priority':
                        const priorityOrder = { 'high': 3, 'medium': 2, 'low': 1 };
                        return priorityOrder[b.dataset.priority] - priorityOrder[a.dataset.priority];
                    case 'type':
                        const typeOrder = { 'funeral': 1, 'lifeplan': 2, 'validation': 3 };
                        const aType = a.classList.contains('funeral') ? 'funeral' : 
                                     a.classList.contains('lifeplan') ? 'lifeplan' : 'validation';
                        const bType = b.classList.contains('funeral') ? 'funeral' : 
                                     b.classList.contains('lifeplan') ? 'lifeplan' : 'validation';
                        return typeOrder[aType] - typeOrder[bType];
                    default:
                        return 0;
                }
            });
            
            items.forEach(item => container.appendChild(item));
        }

        // Mark as read functionality
        function markAsRead(button) {
            const notificationItem = button.closest('.notification-item');
            const indicator = notificationItem.querySelector('.h-3.w-3');
            const status = notificationItem.querySelector('.text-xs');
            
            // Remove all color classes
            indicator.classList.remove('bg-blue-600', 'bg-purple-600', 'bg-yellow-600', 'bg-red-600');
            indicator.classList.add('bg-gray-300');
            status.textContent = 'Read';
            
            // Update counts
            updateNotificationCounts();
            showToast('Notification marked as read');
        }

        // Mark all as read
        function markAllAsRead() {
            const visibleItems = document.querySelectorAll('.notification-item:not([style*="display: none"])');
            
            visibleItems.forEach(item => {
                const indicator = item.querySelector('.h-3.w-3');
                const status = item.querySelector('.text-xs');
                
                if (!indicator.classList.contains('bg-gray-300')) {
                    indicator.classList.remove('bg-blue-600', 'bg-purple-600', 'bg-yellow-600', 'bg-red-600');
                    indicator.classList.add('bg-gray-300');
                    if (status.textContent === 'Unread') {
                        status.textContent = 'Read';
                    }
                }
            });
            
            updateNotificationCounts();
            showToast('All visible notifications marked as read');
        }

        // Update notification counts
        function updateNotificationCounts() {
            const totalUnread = document.querySelectorAll('.h-3.w-3:not(.bg-gray-300)').length;
            document.getElementById('total-notifications').textContent = totalUnread;
            
            // Update tab counts
            const funeralUnread = document.querySelectorAll('.funeral .h-3.w-3:not(.bg-gray-300)').length;
            const lifeplanUnread = document.querySelectorAll('.lifeplan .h-3.w-3:not(.bg-gray-300)').length;
            const validationUnread = document.querySelectorAll('.validation .h-3.w-3:not(.bg-gray-300)').length;
            
            document.querySelector('[onclick="filterNotifications(\'funeral\')"] .bg-blue-500').textContent = funeralUnread;
            document.querySelector('[onclick="filterNotifications(\'lifeplan\')"] .bg-purple-500').textContent = lifeplanUnread;
            document.querySelector('[onclick="filterNotifications(\'validation\')"] .bg-yellow-500').textContent = validationUnread;
            document.querySelector('[onclick="filterNotifications(\'all\')"] .bg-sidebar-accent').textContent = totalUnread;
        }

        // Action functions with confirmation
        function viewBooking(type, id) {
            showLoading();
            setTimeout(() => {
                hideLoading();
                showToast(`Opening ${type} booking #${id}`);
                // Simulate redirect
            }, 1000);
        }

        function approveBooking(type, id, button) {
            showConfirmation(
                `Are you sure you want to approve this ${type} booking?`,
                () => {
                    showLoading();
                    button.classList.add('button-loading');
                    setTimeout(() => {
                        hideLoading();
                        button.classList.remove('button-loading');
                        showToast(`${type} booking #${id} approved successfully`, 'success');
                        // Remove or update the notification
                        const notification = button.closest('.notification-item');
                        notification.style.opacity = '0.5';
                        notification.querySelector('h3').innerHTML = notification.querySelector('h3').innerHTML.replace('Request', 'Approved');
                    }, 1500);
                }
            );
        }

        function rejectBooking(type, id, button) {
            showConfirmation(
                `Are you sure you want to decline this ${type} booking? This action cannot be undone.`,
                () => {
                    showLoading();
                    button.classList.add('button-loading');
                    setTimeout(() => {
                        hideLoading();
                        button.classList.remove('button-loading');
                        showToast(`${type} booking #${id} declined`, 'error');
                        // Remove the notification with animation
                        const notification = button.closest('.notification-item');
                        notification.style.transform = 'translateX(100%)';
                        notification.style.opacity = '0';
                        setTimeout(() => notification.remove(), 300);
                    }, 1500);
                }
            );
        }

        function viewValidation(id) {
            showLoading();
            setTimeout(() => {
                hideLoading();
                showToast(`Opening ID validation review #${id}`);
            }, 1000);
        }

        function approveValidation(id, button) {
            showConfirmation(
                'Are you sure you want to validate this ID document?',
                () => {
                    showLoading();
                    button.classList.add('button-loading');
                    setTimeout(() => {
                        hideLoading();
                        button.classList.remove('button-loading');
                        showToast(`ID validation #${id} approved successfully`, 'success');
                        // Update notification status
                        const notification = button.closest('.notification-item');
                        notification.style.opacity = '0.5';
                        notification.querySelector('h3').innerHTML = notification.querySelector('h3').innerHTML.replace('Required', 'Approved');
                    }, 1500);
                }
            );
        }

        function rejectValidation(id, button) {
            showConfirmation(
                'Are you sure you want to reject this ID document? The user will need to resubmit.',
                () => {
                    showLoading();
                    button.classList.add('button-loading');
                    setTimeout(() => {
                        hideLoading();
                        button.classList.remove('button-loading');
                        showToast(`ID validation #${id} rejected`, 'error');
                        // Update notification
                        const notification = button.closest('.notification-item');
                        notification.querySelector('h3').innerHTML = notification.querySelector('h3').innerHTML.replace('Required', 'Rejected');
                        notification.querySelector('.w-1').classList.remove('bg-yellow-500');
                        notification.querySelector('.w-1').classList.add('bg-red-500');
                    }, 1500);
                }
            );
        }

        function contactClient(id) {
            showToast(`Opening contact form for client #${id}`);
            // Simulate opening contact modal or page
        }

        function scheduleCallback(id) {
            showToast(`Opening callback scheduler for client #${id}`);
            // Simulate opening calendar/scheduler
        }

        function activateLifePlan(id) {
            showConfirmation(
                'Are you sure you want to activate this life plan?',
                () => {
                    showLoading();
                    setTimeout(() => {
                        hideLoading();
                        showToast(`Life plan #${id} activated successfully`, 'success');
                    }, 1500);
                }
            );
        }

        function compareDocuments(id) {
            showToast(`Opening document comparison for ID #${id}`);
        }

        function sendFeedbackForm(id) {
            showToast(`Sending feedback form for service #${id}`);
        }

        function generateReport(id) {
            showLoading();
            setTimeout(() => {
                hideLoading();
                showToast(`Service report #${id} generated successfully`);
            }, 2000);
        }

        function refreshNotifications() {
            showLoading();
            setTimeout(() => {
                hideLoading();
                showToast('Notifications refreshed successfully');
                // Simulate new notifications or updates
                updateNotificationCounts();
            }, 1500);
        }

        function loadMoreNotifications() {
            const button = document.getElementById('load-more-btn');
            button.classList.add('button-loading');
            button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
            
            setTimeout(() => {
                button.classList.remove('button-loading');
                button.innerHTML = '<i class="fas fa-chevron-down mr-2"></i>Load More Notifications';
                showToast('More notifications loaded');
                document.getElementById('current-count').textContent = '15';
                document.getElementById('total-count').textContent = '15';
            }, 2000);
        }

        

        function resetFilters() {
            // Reset all filter states
            currentFilter = 'all';
            currentSort = 'newest';
            searchQuery = '';
            unreadOnly = false;
            
            // Reset UI elements
            document.querySelector('input[type="text"]').value = '';
            document.querySelector('input[type="checkbox"]').checked = false;
            document.getElementById('sort-select').value = 'newest';
            
            // Reset active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active', 'border-sidebar-accent', 'text-sidebar-accent');
                tab.classList.add('border-transparent', 'text-gray-500', 'hover:text-sidebar-text', 'hover:border-gray-300');
            });
            document.querySelector('[onclick="filterNotifications(\'all\')"]').classList.add('active', 'border-sidebar-accent', 'text-sidebar-accent');
            
            applyFilters();
            showToast('Filters reset successfully');
        }

        // Modal functions
        function showHelpModal() {
            document.getElementById('help-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeHelpModal() {
            document.getElementById('help-modal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function showConfirmation(message, callback) {
            document.getElementById('confirmation-message').textContent = message;
            document.getElementById('confirmation-modal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            confirmationCallback = callback;
            
            document.getElementById('confirm-action-btn').onclick = function() {
                closeConfirmationModal();
                if (confirmationCallback) {
                    confirmationCallback();
                    confirmationCallback = null;
                }
            };
        }

        function closeConfirmationModal() {
            document.getElementById('confirmation-modal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            confirmationCallback = null;
        }

        // Loading functions
        function showLoading() {
            document.getElementById('loading-overlay').classList.remove('hidden');
        }

        function hideLoading() {
            document.getElementById('loading-overlay').classList.add('hidden');
        }

        // Toast notification functions
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            
            toastMessage.textContent = message;
            
            // Update colors and icons based on type
            toast.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-card transform transition-transform duration-300 z-50`;
            const icon = toast.querySelector('i');
            
            if (type === 'success') {
                toast.classList.add('bg-success', 'text-white');
                icon.className = 'fas fa-check-circle mr-3';
            } else if (type === 'error') {
                toast.classList.add('bg-error', 'text-white');
                icon.className = 'fas fa-exclamation-circle mr-3';
            } else if (type === 'warning') {
                toast.classList.add('bg-yellow-500', 'text-white');
                icon.className = 'fas fa-exclamation-triangle mr-3';
            } else {
                toast.classList.add('bg-sidebar-accent', 'text-white');
                icon.className = 'fas fa-info-circle mr-3';
            }
            
            // Show toast
            toast.classList.remove('translate-x-full');
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (!toast.classList.contains('translate-x-full')) {
                    toast.classList.add('translate-x-full');
                }
            }, 5000);
        }

        function closeToast() {
            document.getElementById('toast').classList.add('translate-x-full');
        }

        // Close modals when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.id === 'help-modal') {
                closeHelpModal();
            }
            if (e.target.id === 'confirmation-modal') {
                closeConfirmationModal();
            }
        });

        // Initialize active tab styling
        document.addEventListener('DOMContentLoaded', function() {
            const activeTab = document.querySelector('.filter-tab.active');
            if (activeTab) {
                activeTab.classList.add('border-sidebar-accent', 'text-sidebar-accent');
                activeTab.classList.remove('border-transparent', 'text-gray-500');
            }
            
            // Set up other tabs
            document.querySelectorAll('.filter-tab:not(.active)').forEach(tab => {
                tab.classList.add('border-transparent', 'text-gray-500', 'hover:text-sidebar-text', 'hover:border-gray-300');
            });
        });

        // Accessibility improvements
        document.addEventListener('keydown', function(e) {
            // ESC key closes modals
            if (e.key === 'Escape') {
                if (!document.getElementById('help-modal').classList.contains('hidden')) {
                    closeHelpModal();
                }
                if (!document.getElementById('confirmation-modal').classList.contains('hidden')) {
                    closeConfirmationModal();
                }
                if (!document.getElementById('toast').classList.contains('translate-x-full')) {
                    closeToast();
                }
            }
        });

        // Auto-refresh notifications every 30 seconds
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                // Silently check for new notifications
                // In a real app, this would make an API call
                console.log('Checking for new notifications...');
            }
        }, 30000);

        // Show browser notification for high priority items
        function showBrowserNotification(title, body) {
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification(title, {
                    body: body,
                    icon: '/favicon.ico',
                    badge: '/favicon.ico'
                });
            }
        }

        // Request notification permission on load
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    </script>
</body>
</html>