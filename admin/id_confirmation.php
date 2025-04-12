<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: ../Landing_Page/login.php");
    exit();
}

// Check for admin user type (user_type = 1)
if ($_SESSION['user_type'] != 1) {
    // Redirect to appropriate page based on user type
    switch ($_SESSION['user_type']) {
        case 2:
            header("Location: ../employee/index.php");
            break;
        case 3:
            header("Location: ../customer/index.php");
            break;
        default:
            // Invalid user_type
            session_destroy();
            header("Location: ../Landing_Page/login.php");
    }
    exit();
}

// Optional: Check for session timeout (30 minutes)
$session_timeout = 1800; // 30 minutes in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $session_timeout)) {
    // Session has expired
    session_unset();
    session_destroy();
    header("Location: ../Landing_Page/login.php?timeout=1");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Prevent caching for authenticated pages
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ID Confirmation</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
     /* Base Typography */
  body {
    font-family: 'Hedvig Letters Serif', serif;
  }
  
  /* Message status indicators */
  .message-new {
    border-left: 3px solid #CA8A04; /* Using your sidebar accent color */
  }
  
  .message-read {
    border-left: 3px solid transparent;
  }
  
  /* Header Styles */
  h1 {
    font-family: 'Cinzel', serif;
    font-size: 1.5rem; /* 24px */
    font-weight: 700;
    color: #1E293B; /* slate-800 */
  }
  
  h2 {
    font-family: 'Cinzel', serif;
    font-size: 1.25rem; /* 20px */
    font-weight: 600;
    color: #1E293B; /* slate-800 */
  }
  
  h3 {
    font-family: 'Cinzel', serif;
    font-size: 1.125rem; /* 18px */
    font-weight: 600;
    color: #1E293B; /* slate-800 */
  }
  
  h5 {
    font-family: 'Cinzel', serif;
    font-size: 0.875rem; /* 14px */
    font-weight: 500;
    color: #CA8A04; /* sidebar accent color */
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  
  /* Text Colors */
  .text-sidebar-accent {
    color: #CA8A04;
  }
  
  .text-sidebar-text {
    color: #334155; /* slate-700 */
  }
  
  /* Button Styles */
  button {
    font-family: 'Hedvig Letters Serif', serif;
    font-size: 0.875rem; /* 14px */
    transition: all 0.3s ease;
  }
  
  /* Input Fields */
  input, textarea {
    font-family: 'Hedvig Letters Serif', serif;
    font-size: 0.875rem; /* 14px */
    border: 1px solid #CBD5E1; /* slate-300 */
    border-radius: 0.375rem; /* 6px */
  }
  
  /* Icons */
  .fas {
    color: #64748B; /* slate-500 */
    transition: color 0.3s ease;
  }
  
  /* Hover States */
  button:hover .fas {
    color: #1E293B; /* slate-800 */
  }
  
  /* Message Bubbles */
  .admin-message {
    background-color: #CA8A04; /* sidebar accent */
    color: white;
  }
  
  .customer-message {
    background-color: #F1F5F9; /* slate-100 */
    color: #1E293B; /* slate-800 */
  }
  
  /* Timestamp Text */
  .message-time {
    font-size: 0.75rem; /* 12px */
    color: #64748B; /* slate-500 */
  }
  
  /* Badges */
  .badge {
    font-size: 0.75rem; /* 12px */
    background-color: #CA8A04; /* sidebar accent */
    color: white;
  }
  
  /* Ensure sidebar maintains styling */
  #sidebar {
    background-color: white !important;
    z-index: 50 !important;
    font-family: 'Hedvig Letters Serif', serif;
  }
  
  /* Mobile Responsiveness */
  @media (max-width: 768px) {
    #sidebar.translate-x-0 {
      background-color: white !important;
      box-shadow: 2px 0 10px rgba(0,0,0,0.1);
    }
    
    h1 {
      font-size: 1.25rem; /* 20px */
    }
    
    h2 {
      font-size: 1.125rem; /* 18px */
    }
  }
  
  /* Custom scrollbar to match sidebar */
  .scrollbar-thin::-webkit-scrollbar {
    width: 4px;
    height: 4px;
  }
  .scrollbar-thin::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
  }
  .scrollbar-thin::-webkit-scrollbar-thumb {
    background: rgba(202, 138, 4, 0.6);
    border-radius: 4px;
  }
  .scrollbar-thin::-webkit-scrollbar-thumb:hover {
    background: rgba(202, 138, 4, 0.9);
  }
    /* Message status indicators */
    .message-new {
      border-left: 3px solid #008080;
    }
    
    .message-read {
      border-left: 3px solid transparent;
    }
    /* Ensure sidebar maintains background in all views */
    #sidebar {
      background-color: white !important;
      z-index: 50 !important; /* Higher than chat content */
    }

    /* When sidebar is open on mobile, ensure it's above everything */
    @media (max-width: 768px) {
      #sidebar.translate-x-0 {
        background-color: white !important;
        box-shadow: 2px 0 10px rgba(0,0,0,0.1);
      }
    }
    body.communication-chat #sidebar {
      background-color: white !important;
      z-index: 50 !important;
    }
    .main-content {
      z-index: 40; /* Lower than sidebar's 50 */
    }
  </style>
</head>
<body class="flex bg-gray-50">
<?php include 'admin_sidebar.php'; ?>

<div id="main-content" class="p-6 bg-gray-50 min-h-screen transition-all duration-300 ml-64 w-[calc(100%-16rem)] main-content">
<p>ID CONFIRMATION</p>
</div>    
</body>
</html>