<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "solestreet";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

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
    $stmt = $conn->prepare("INSERT INTO user (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $password);

    // Execute query
    if ($stmt->execute()) {
        echo "<script>alert('Registration successful! Redirecting to login page...');</script>";
        echo "<script>window.location.href='login.html';</script>"; // Redirect to login page
    } else {
        echo "Error: " . $stmt->error;
    }


    // Close statement and connection
    $stmt->close();
    $conn->close();
}
?>
