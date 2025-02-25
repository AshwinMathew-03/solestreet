<?php
session_start(); // Start the session

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "solestreet";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $password = $_POST['password'];

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT userid, password FROM user WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $stmt->store_result();

    // Check if user exists
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $hashed_password);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $id; // Store user ID in session
            $_SESSION['name'] = $name; // Store username in session
            
            // Redirect to dashboard or home page
            header("Location: ../userdashboard/userdashboard.php"); 
            exit();
        } else 
        {
            header("Location: ../admin/admindashboard.php");
        }
    } else 
    {
        echo "<script>alert('User not found!'); window.location.href='../signup/signup.html';</script>";
    }

    $stmt->close();
}

$conn->close();
?>
