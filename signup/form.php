<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project1";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
$role = 2; // Users' default role is 2, admin role is 1

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password

    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO user (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $name, $email, $password, $role);

    // Execute query
    if ($stmt->execute()) {
        echo "<script>alert('Registration successful! Redirecting to login page...');</script>";
        echo "<script>window.location.href='../login/login.html';</script>"; // Redirect to login page
    } else {
        echo "Error: " . $stmt->error;
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
}
?>
