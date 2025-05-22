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
</head>
<body class="bg-primary font-inter">
    <!-- Header -->
    <header class="bg-sidebar-bg shadow-sidebar border-b border-sidebar-border">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <button onclick="history.back()" class="mr-4 p-2 rounded-lg bg-sidebar-hover text-sidebar-text hover:bg-border transition-colors">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div>
                        <h1 class="text-2xl font-semibold text-sidebar-text font-playfair">Notifications</h1>
                        <p class="text-sm text-gray-600">Manage all system notifications</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <span class="bg-sidebar-accent text-white text-sm px-3 py-1 rounded-full font-medium">
                            <span id="total-notifications">7</span> Pending
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Filter Tabs -->
        <div class="mb-8">
            <div class="border-b border-sidebar-border">
                <nav class="-mb-px flex space-x-8">
                    <button onclick="filterNotifications('all')" class="filter-tab active whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        All Notifications
                        <span class="ml-2 bg-sidebar-accent text-white text-xs rounded-full px-2 py-0.5">7</span>
                    </button>
                    <button onclick="filterNotifications('funeral')" class="filter-tab whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        Funeral Bookings
                        <span class="ml-2 bg-blue-500 text-white text-xs rounded-full px-2 py-0.5">3</span>
                    </button>
                    <button onclick="filterNotifications('lifeplan')" class="filter-tab whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        Life Plan Bookings
                        <span class="ml-2 bg-purple-500 text-white text-xs rounded-full px-2 py-0.5">2</span>
                    </button>
                    <button onclick="filterNotifications('validation')" class="filter-tab whitespace-nowrap pb-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        ID Validations
                        <span class="ml-2 bg-yellow-500 text-white text-xs rounded-full px-2 py-0.5">2</span>
                    </button>
                </nav>
            </div>
        </div>

        <!-- Actions Bar -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div class="flex items-center space-x-4">
                <button onclick="markAllAsRead()" class="flex items-center px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors text-sm font-medium">
                    <i class="fas fa-check-double mr-2"></i>
                    Mark All as Read
                </button>
                <button onclick="refreshNotifications()" class="flex items-center px-4 py-2 bg-sidebar-hover text-sidebar-text rounded-lg hover:bg-border transition-colors text-sm font-medium">
                    <i class="fas fa-sync-alt mr-2"></i>
                    Refresh
                </button>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-600">Sort by:</span>
                <select class="px-3 py-2 border border-input-border rounded-lg text-sm bg-sidebar-bg text-sidebar-text focus:outline-none focus:ring-2 focus:ring-sidebar-accent">
                    <option>Newest First</option>
                    <option>Oldest First</option>
                    <option>Priority</option>
                </select>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="space-y-4">
            <!-- Funeral Booking Notification -->
            <div class="notification-item funeral bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border overflow-hidden hover:shadow-lg transition-shadow">
                <div class="flex items-start p-6">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
                    <div class="flex-shrink-0 bg-blue-100 rounded-full p-3 mr-4">
                        <i class="fas fa-cross text-blue-600 text-lg"></i>
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="text-lg font-semibold text-sidebar-text">New Funeral Booking Request</h3>
                                <p class="text-sidebar-text mt-1">Smith, John Michael - Premium Funeral Package</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="h-3 w-3 bg-blue-600 rounded-full"></span>
                                <span class="text-xs text-gray-500">Unread</span>
                            </div>
                        </div>
                        <div class="flex items-center text-sm text-gray-600 mb-4">
                            <i class="far fa-clock mr-2"></i>
                            <span>2 hours ago</span>
                            <span class="mx-2">•</span>
                            <i class="fas fa-calendar mr-2"></i>
                            <span>Service Date: March 15, 2025</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex space-x-3">
                                <button onclick="viewBooking('funeral', 1)" class="px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-colors text-sm font-medium">
                                    <i class="fas fa-eye mr-2"></i>View Details
                                </button>
                                <button onclick="approveBooking('funeral', 1)" class="px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors text-sm font-medium">
                                    <i class="fas fa-check mr-2"></i>Approve
                                </button>
                                <button onclick="rejectBooking('funeral', 1)" class="px-4 py-2 bg-error text-white rounded-lg hover:bg-red-600 transition-colors text-sm font-medium">
                                    <i class="fas fa-times mr-2"></i>Decline
                                </button>
                            </div>
                            <button onclick="markAsRead(this)" class="text-gray-400 hover:text-sidebar-accent transition-colors">
                                <i class="fas fa-check-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Life Plan Booking Notification -->
            <div class="notification-item lifeplan bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border overflow-hidden hover:shadow-lg transition-shadow">
                <div class="flex items-start p-6">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-purple-500"></div>
                    <div class="flex-shrink-0 bg-purple-100 rounded-full p-3 mr-4">
                        <i class="fas fa-heart text-purple-600 text-lg"></i>
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="text-lg font-semibold text-sidebar-text">New Life Plan Booking Request</h3>
                                <p class="text-sidebar-text mt-1">Johnson, Mary Elizabeth - Complete Life Plan Package</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="h-3 w-3 bg-purple-600 rounded-full"></span>
                                <span class="text-xs text-gray-500">Unread</span>
                            </div>
                        </div>
                        <div class="flex items-center text-sm text-gray-600 mb-4">
                            <i class="far fa-clock mr-2"></i>
                            <span>4 hours ago</span>
                            <span class="mx-2">•</span>
                            <i class="fas fa-dollar-sign mr-2"></i>
                            <span>Monthly Plan: $250</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex space-x-3">
                                <button onclick="viewBooking('lifeplan', 1)" class="px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-colors text-sm font-medium">
                                    <i class="fas fa-eye mr-2"></i>View Details
                                </button>
                                <button onclick="approveBooking('lifeplan', 1)" class="px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors text-sm font-medium">
                                    <i class="fas fa-check mr-2"></i>Approve
                                </button>
                                <button onclick="rejectBooking('lifeplan', 1)" class="px-4 py-2 bg-error text-white rounded-lg hover:bg-red-600 transition-colors text-sm font-medium">
                                    <i class="fas fa-times mr-2"></i>Decline
                                </button>
                            </div>
                            <button onclick="markAsRead(this)" class="text-gray-400 hover:text-sidebar-accent transition-colors">
                                <i class="fas fa-check-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ID Validation Notification -->
            <div class="notification-item validation bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border overflow-hidden hover:shadow-lg transition-shadow">
                <div class="flex items-start p-6">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-yellow-500"></div>
                    <div class="flex-shrink-0 bg-yellow-100 rounded-full p-3 mr-4">
                        <i class="fas fa-id-card text-yellow-600 text-lg"></i>
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="text-lg font-semibold text-sidebar-text">ID Validation Required</h3>
                                <p class="text-sidebar-text mt-1">New identification document uploaded for verification</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="h-3 w-3 bg-yellow-600 rounded-full"></span>
                                <span class="text-xs text-gray-500">Unread</span>
                            </div>
                        </div>
                        <div class="flex items-center text-sm text-gray-600 mb-4">
                            <i class="far fa-clock mr-2"></i>
                            <span>1 day ago</span>
                            <span class="mx-2">•</span>
                            <i class="fas fa-user mr-2"></i>
                            <span>User ID: #12345</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex space-x-3">
                                <button onclick="viewValidation(1)" class="px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-colors text-sm font-medium">
                                    <i class="fas fa-eye mr-2"></i>Review ID
                                </button>
                                <button onclick="approveValidation(1)" class="px-4 py-2 bg-success text-white rounded-lg hover:bg-green-600 transition-colors text-sm font-medium">
                                    <i class="fas fa-check mr-2"></i>Validate
                                </button>
                                <button onclick="rejectValidation(1)" class="px-4 py-2 bg-error text-white rounded-lg hover:bg-red-600 transition-colors text-sm font-medium">
                                    <i class="fas fa-times mr-2"></i>Reject
                                </button>
                            </div>
                            <button onclick="markAsRead(this)" class="text-gray-400 hover:text-sidebar-accent transition-colors">
                                <i class="fas fa-check-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- More notifications with similar structure -->
            <div class="notification-item funeral bg-sidebar-bg rounded-lg shadow-card border border-sidebar-border overflow-hidden hover:shadow-lg transition-shadow">
                <div class="flex items-start p-6">
                    <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
                    <div class="flex-shrink-0 bg-blue-100 rounded-full p-3 mr-4">
                        <i class="fas fa-cross text-blue-600 text-lg"></i>
                    </div>
                    <div class="flex-grow">
                        <div class="flex justify-between items-start mb-2">
                            <div>
                                <h3 class="text-lg font-semibold text-sidebar-text">Funeral Booking Update Required</h3>
                                <p class="text-sidebar-text mt-1">Garcia, Roberto Carlos - Basic Funeral Package</p>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="h-3 w-3 bg-blue-600 rounded-full"></span>
                                <span class="text-xs text-gray-500">Unread</span>
                            </div>
                        </div>
                        <div class="flex items-center text-sm text-gray-600 mb-4">
                            <i class="far fa-clock mr-2"></i>
                            <span>2 days ago</span>
                            <span class="mx-2">•</span>
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <span>Requires additional information</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex space-x-3">
                                <button onclick="viewBooking('funeral', 2)" class="px-4 py-2 bg-sidebar-accent text-white rounded-lg hover:bg-darkgold transition-colors text-sm font-medium">
                                    <i class="fas fa-eye mr-2"></i>View Details
                                </button>
                                <button onclick="contactClient(2)" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm font-medium">
                                    <i class="fas fa-phone mr-2"></i>Contact
                                </button>
                            </div>
                            <button onclick="markAsRead(this)" class="text-gray-400 hover:text-sidebar-accent transition-colors">
                                <i class="fas fa-check-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Load More Button -->
        <div class="text-center mt-8">
            <button onclick="loadMoreNotifications()" class="px-6 py-3 bg-sidebar-hover text-sidebar-text rounded-lg hover:bg-border transition-colors font-medium">
                <i class="fas fa-chevron-down mr-2"></i>
                Load More Notifications
            </button>
        </div>
    </main>

    <!-- Toast Notification -->
    <div id="toast" class="fixed top-4 right-4 bg-success text-white px-6 py-4 rounded-lg shadow-card transform translate-x-full transition-transform duration-300 z-50">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-3"></i>
            <span id="toast-message">Action completed successfully</span>
        </div>
    </div>

    <script>
        // Filter functionality
        function filterNotifications(type) {
            const items = document.querySelectorAll('.notification-item');
            const tabs = document.querySelectorAll('.filter-tab');
            
            // Update active tab
            tabs.forEach(tab => {
                tab.classList.remove('active', 'border-sidebar-accent', 'text-sidebar-accent');
                tab.classList.add('border-transparent', 'text-gray-500', 'hover:text-sidebar-text', 'hover:border-gray-300');
            });
            
            event.target.classList.add('active', 'border-sidebar-accent', 'text-sidebar-accent');
            event.target.classList.remove('border-transparent', 'text-gray-500', 'hover:text-sidebar-text', 'hover:border-gray-300');
            
            // Filter items
            items.forEach(item => {
                if (type === 'all' || item.classList.contains(type)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Mark as read functionality
        function markAsRead(button) {
            const notificationItem = button.closest('.notification-item');
            const indicator = notificationItem.querySelector('.h-3.w-3');
            const status = notificationItem.querySelector('.text-xs');
            
            indicator.classList.remove('bg-blue-600', 'bg-purple-600', 'bg-yellow-600');
            indicator.classList.add('bg-gray-300');
            status.textContent = 'Read';
            
            showToast('Notification marked as read');
        }

        // Mark all as read
        function markAllAsRead() {
            const indicators = document.querySelectorAll('.h-3.w-3');
            const statuses = document.querySelectorAll('.text-xs');
            
            indicators.forEach(indicator => {
                indicator.classList.remove('bg-blue-600', 'bg-purple-600', 'bg-yellow-600');
                indicator.classList.add('bg-gray-300');
            });
            
            statuses.forEach(status => {
                if (status.textContent === 'Unread') {
                    status.textContent = 'Read';
                }
            });
            
            showToast('All notifications marked as read');
        }

        // Action functions
        function viewBooking(type, id) {
            showToast(`Opening ${type} booking #${id}`);
            // Redirect to booking details page
        }

        function approveBooking(type, id) {
            showToast(`${type} booking #${id} approved`, 'success');
        }

        function rejectBooking(type, id) {
            showToast(`${type} booking #${id} declined`, 'error');
        }

        function viewValidation(id) {
            showToast(`Opening ID validation review #${id}`);
        }

        function approveValidation(id) {
            showToast(`ID validation #${id} approved`, 'success');
        }

        function rejectValidation(id) {
            showToast(`ID validation #${id} rejected`, 'error');
        }

        function contactClient(id) {
            showToast(`Opening contact form for client #${id}`);
        }

        function refreshNotifications() {
            showToast('Notifications refreshed');
        }

        function loadMoreNotifications() {
            showToast('Loading more notifications...');
        }

        // Toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            
            toastMessage.textContent = message;
            
            // Update colors based on type
            toast.className = `fixed top-4 right-4 px-6 py-4 rounded-lg shadow-card transform transition-transform duration-300 z-50`;
            
            if (type === 'success') {
                toast.classList.add('bg-success', 'text-white');
            } else if (type === 'error') {
                toast.classList.add('bg-error', 'text-white');
            } else {
                toast.classList.add('bg-sidebar-accent', 'text-white');
            }
            
            // Show toast
            toast.classList.remove('translate-x-full');
            
            // Hide after 3 seconds
            setTimeout(() => {
                toast.classList.add('translate-x-full');
            }, 3000);
        }

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
    </script>
</body>
</html>