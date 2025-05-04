<?php
// logout.php

// Start the session
session_start();

// Unset all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session
session_destroy();

// Regenerate session ID for security
session_regenerate_id(true);

// Clear any existing output buffering
if (ob_get_length()) {
    ob_end_clean();
}

// Redirect to login page
header("Location: ../Landing_Page/login.php");
exit();
?>