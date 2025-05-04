<?php
// check_login.php - Verify credentials against the users table only
include '../db_connect.php';
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Check connection
if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

// Get form data
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required']);
    exit;
}

// Check login using only the users table
$stmt = $conn->prepare("SELECT id, first_name, last_name, password, user_type, is_verified FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    if ($user['is_verified'] != 1) {
        echo json_encode([
            'status' => 'error', 
            'message' => 'Your account is currently on Hold', 
            'alert_type' => 'warning',
            'icon' => 'warning',
            'title' => 'Account On Hold'
        ]);
        exit;
    }

    // Use password_verify()
    if (password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['user_type'] = $user['user_type'];
        
        // Determine redirect based on user_type
        $redirect = '';
        $role = '';
        
        switch ($user['user_type']) {
            case 1:
                $redirect = '../admin/admin_index.php';
                $role = 'Administrator';
                break;
            case 2:
                $redirect = '../employee/index.php';
                $role = 'Employee';
                break;
            case 3:
                $redirect = '../customer/index.php';
                $role = 'Customer';
                break;
            case 69:  // Add this new case for Super Admin
                $redirect = '../super_admin/index.php';
                $role = 'Super Administrator';
                break;
            default:
                echo json_encode(['status' => 'error', 'message' => 'Invalid user type']);
                exit;
        }
        
        // Set a session last activity timestamp
        $_SESSION['last_activity'] = time();
        
        echo json_encode(['status' => 'success', 'role' => $role, 'redirect' => $redirect]);
        $stmt->close();
        $conn->close();
        exit;
    }
}

// If no matches, return error
echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
$stmt->close();
$conn->close();
?>