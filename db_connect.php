<?php
// Database connection parameters
$servername = "localhost";
$username = "u349622494_grievease";
$password = "Grievease_2k25";
$dbname = "u349622494_grievease";

try {
    // Create a new connection using MySQLi
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>
