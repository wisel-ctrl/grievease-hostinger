<?php
require_once 'db_connect.php';

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch branches from the database
$branches = [];
$branchQuery = "SELECT branch_id, branch_name FROM branch_tb";
$result = $conn->query($branchQuery);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $branches[] = $row;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST["first_name"]);
    $middle_name = trim($_POST["middle_name"]);
    $last_name = trim($_POST["last_name"]);
    $birthdate = $_POST["birthdate"];
    $email = trim($_POST["email"]);
    $password = password_hash($_POST["password"], PASSWORD_BCRYPT);
    $user_type = 2; // Setting user_type to 2

    // Fetch selected branch name from branch_tb
    $branch_id = $_POST["branch_id"];
    $branch_loc = "";

    $stmt_branch = $conn->prepare("SELECT branch_name FROM branch_tb WHERE branch_id = ?");
    $stmt_branch->bind_param("i", $branch_id);
    $stmt_branch->execute();
    $stmt_branch->bind_result($branch_loc);
    $stmt_branch->fetch();
    $stmt_branch->close();

    // Insert user with selected branch location
    $sql = "INSERT INTO users (first_name, middle_name, last_name, birthdate, email, password, user_type, branch_loc) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssis", $first_name, $middle_name, $last_name, $birthdate, $email, $password, $user_type, $branch_loc);

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
    <title>Create User Account</title>
</head>
<body>
    <h2>Create User Account</h2>
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
        <label>Branch:</label>
        <select name="branch_id" required>
            <option value="" disabled selected hidden>Select a branch</option>
            <?php foreach ($branches as $branch): ?>
                <option value="<?= $branch['branch_id'] ?>"><?= htmlspecialchars($branch['branch_name']) ?></option>
            <?php endforeach; ?>
        </select><br>
        <button type="submit">Create Account</button>
    </form>
</body>
</html>
