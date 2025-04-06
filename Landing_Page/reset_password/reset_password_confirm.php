<?php
require_once '../../db_connect.php';

// Validate reset token
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Improved token validation
    $stmt = $conn->prepare("SELECT id, email, reset_token_expiry, 
        TIMESTAMPDIFF(MINUTE, NOW(), reset_token_expiry) AS token_validity 
        FROM users 
        WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // More detailed token expiration check
        if ($user['token_validity'] > 0) {
            // Token is valid, show password reset form
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>Reset Password</title>
                <style>
                    body { font-family: Arial, sans-serif; max-width: 400px; margin: 0 auto; padding: 20px; }
                    form { display: flex; flex-direction: column; }
                    input { margin: 10px 0; padding: 10px; }
                    button { padding: 10px; background-color: #4CAF50; color: white; border: none; }
                </style>
            </head>
            <body>
                <form id="resetPasswordForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="password" name="new_password" required placeholder="New Password" minlength="8">
                    <input type="password" name="confirm_password" required placeholder="Confirm Password" minlength="8">
                    <button type="submit">Reset Password</button>
                </form>

                <script>
                document.getElementById('resetPasswordForm').addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const newPassword = this.new_password.value;
                    const confirmPassword = this.confirm_password.value;
                    
                    if (newPassword !== confirmPassword) {
                        alert('Passwords do not match');
                        return;
                    }
                    
                    fetch('process_password_reset.php', {
                        method: 'POST',
                        body: new FormData(this)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            alert('Password reset successfully');
                            window.location.href = '../login.php';
                        } else {
                            alert(data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An unexpected error occurred');
                    });
                });
                </script>
            </body>
            </html>
            <?php
        } else {
            // Token has expired
            echo "Reset token has expired. Please request a new password reset.";
        }
    } else {
        // Invalid token
        echo "Invalid reset link. Please request a new password reset.";
    }
    
    // Close the statement
    $stmt->close();
} else {
    // No token provided
    echo "No reset token found.";
}
?>