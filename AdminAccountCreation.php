<?php
$servername = "localhost";
$username = "root";
$password = "Abcd1234";
$dbname = "grievease";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $last_name = trim($_POST["last_name"]);
    $birthdate = $_POST["birthdate"];
    $email = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);
    $user_type = 1; // Setting user_type to 1

    $sql = "INSERT INTO users (first_name, middle_name, last_name, birthdate, email, password, user_type) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $first_name, $middle_name, $last_name, $birthdate, $email, $password, $user_type);

    if ($stmt->execute()) {
        echo "User account created successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account</title>
</head>
<body>
    <h2>Create Admin Account</h2>
    <form action="" method="POST">
        <label>First Name:</label>
        <input type="text" name="first_name" required><br>
        <label>Middle Name:</label>
        <input type="text" name="middle_name"><br>
        <label>Last Name:</label>
        <input type="text" name="last_name" required><br>
        <label>Birthdate:</label>
        <input type="date" name="birthdate"><br>
        <label>Email:</label>
        <input type="email" name="email" required><br>
        <label>Password:</label>
        <input type="password" name="password" required><br>
        <button type="submit">Create Account</button>
    </form>
</body>
</html>