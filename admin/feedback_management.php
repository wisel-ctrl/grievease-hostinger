<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - Grievease Admin</title>
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="../favicon.ico">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Star rating */
        .star-rating {
            color: #f1c40f;
            font-size: 1.2rem;
            letter-spacing: 2px;
        }
        
        /* Tab styles */
        .tab {
            @apply px-4 py-2 rounded-t-lg font-medium text-sm transition-colors duration-200;
        }
        .tab.active {
            @apply bg-white text-blue-600 border-b-2 border-blue-600;
        }
        .tab:not(.active) {
            @apply text-gray-500 hover:text-gray-700;
        }
        
        /* Feedback card */
        .feedback-card {
            @apply bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden transition-all duration-200 hover:shadow-md;
        }
        .feedback-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mock Header -->
    <header class="fixed top-0 right-0 left-64 bg-white shadow-sm z-30">
        <div class="flex items-center justify-between px-6 py-3">
            <div class="flex items-center">
                <button class="p-2 rounded-full hover:bg-gray-100">
                    <i class="fas fa-bell text-gray-600"></i>
                </button>
            </div>
            <div class="flex items-center space-x-4">
                <span class="text-sm font-medium text-gray-700">Admin User</span>
                <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-white font-medium">
                    AU
                </div>
            </div>
        </div>
    </header>
    
    <!-- Mock Sidebar -->
    <aside class="fixed top-0 left-0 w-64 h-full bg-gray-800 text-white">
        <div class="p-4 border-b border-gray-700">
            <h1 class="text-xl font-bold">Grievease</h1>
        </div>
        <nav class="mt-4">
            <!-- Other menu items would go here -->
            <a href="#" class="flex items-center px-4 py-3 text-gray-300 hover:bg-gray-700">
                <i class="fas fa-comment-alt w-5 text-center mr-3"></i>
                <span>Feedback Management</span>
            </a>
        </nav>
    </aside>
    
    <!-- Main Content -->
    <div class="ml-64 p-6 mt-16">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">Customer Feedback</h1>
            <div class="flex space-x-2">
                <a href="#" class="tab active">
                    <i class="fas fa-inbox mr-1"></i> Active
                </a>
                <a href="#" class="tab">
                    <i class="fas fa-archive mr-1"></i> Archived
                </a>
            </div>
        </div>
        
        <!-- Feedback List -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Feedback Card 1 -->
            <div class="feedback-card">
                <div class="p-5">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="font-semibold text-gray-800">John Doe</h3>
                            <p class="text-sm text-gray-500">john.doe@example.com</p>
                            <div class="star-rating my-2" title="Rating: 5/5">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400">
                            Nov 14, 2025
                        </span>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded-lg mb-4">
                        <p class="text-gray-700">The service was excellent! The staff was very professional and handled everything with great care. Highly recommended to anyone in need of these services.</p>
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <button class="text-sm text-gray-500 hover:text-gray-700">
                            <i class="fas fa-archive mr-1"></i> Archive
                        </button>
                    </div>
                </div>
            </div>

            <!-- Feedback Card 2 -->
            <div class="feedback-card">
                <div class="p-5">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="font-semibold text-gray-800">Jane Smith</h3>
                            <p class="text-sm text-gray-500">jane.smith@example.com</p>
                            <div class="star-rating my-2" title="Rating: 4/5">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="far fa-star"></i>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400">
                            Nov 12, 2025
                        </span>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded-lg mb-4">
                        <p class="text-gray-700">Good service overall, but there was a slight delay in the paperwork processing. The staff was very apologetic and professional about it.</p>
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <button class="text-sm text-gray-500 hover:text-gray-700">
                            <i class="fas fa-archive mr-1"></i> Archive
                        </button>
                    </div>
                </div>
            </div>

            <!-- Feedback Card 3 -->
            <div class="feedback-card">
                <div class="p-5">
                    <div class="flex justify-between items-start mb-3">
                        <div>
                            <h3 class="font-semibold text-gray-800">Robert Johnson</h3>
                            <p class="text-sm text-gray-500">robert.j@example.com</p>
                            <div class="star-rating my-2" title="Rating: 5/5">
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                                <i class="fas fa-star"></i>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400">
                            Nov 10, 2025
                        </span>
                    </div>
                    
                    <div class="bg-gray-50 p-3 rounded-lg mb-4">
                        <p class="text-gray-700">Absolutely amazing service during our difficult time. The team was compassionate, professional, and handled everything with the utmost care. Thank you so much.</p>
                    </div>
                    
                    <div class="flex justify-end space-x-2">
                        <button class="text-sm text-gray-500 hover:text-gray-700">
                            <i class="fas fa-archive mr-1"></i> Archive
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pagination -->
        <div class="flex justify-center mt-8">
            <nav class="inline-flex rounded-md shadow">
                <a href="#" class="px-3 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <a href="#" class="px-4 py-2 border-t border-b border-gray-300 bg-white text-sm font-medium text-blue-600 bg-blue-50">
                    1
                </a>
                <a href="#" class="px-4 py-2 border-t border-b border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                    2
                </a>
                <a href="#" class="px-4 py-2 border-t border-b border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                    3
                </a>
                <a href="#" class="px-3 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </nav>
        </div>
    </div>
    
    <!-- Scripts -->
    <script>
        // Tab switching functionality
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                
                // In a real implementation, this would load the appropriate feedback
                console.log('Tab clicked:', this.textContent.trim());
            });
        });
        
        // Archive button functionality
        document.querySelectorAll('.feedback-card button').forEach(button => {
            button.addEventListener('click', function() {
                const card = this.closest('.feedback-card');
                if (confirm('Are you sure you want to archive this feedback?')) {
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                        // In a real implementation, this would make an API call to archive the feedback
                    }, 300);
                }
            });
        });
    </script>
</body>
</html>
