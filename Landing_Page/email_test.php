// Create a separate test file called email_test.php with this code
<?php
require '../vendor/autoload.php';
$mail = new PHPMailer\PHPMailer\PHPMailer(true);
$mail->SMTPDebug = 2; // Enable verbose debug output

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'grieveease@gmail.com';
    $mail->Password = 'tzhb makw oiyz xvvt';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    
    // Recipients
    $mail->setFrom('grieveease@gmail.com', 'GrievEase');
    $mail->addAddress('wesleyfuentes2k18@gmail.com'); // Use your actual email for testing
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'GrievEase Test Email';
    $mail->Body = 'This is a test email. If you see this, the email system is working correctly.';
    
    $mail->send();
    echo 'Email sent successfully!';
} catch (Exception $e) {
    echo "Email could not be sent. Error: {$mail->ErrorInfo}";
}
?>