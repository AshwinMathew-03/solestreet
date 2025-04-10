<?php
session_start(); // Start the session at the very beginning

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project1";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $password = $_POST['password'];

    // Debug line
    error_log("Login attempt for user: " . $name);

    // Prepare SQL statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, password, role FROM user WHERE name = ?");
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $result = $stmt->get_result();

    // Debug line
    error_log("Query executed, found rows: " . $result->num_rows);

    // Check if user exists
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $name;
            $_SESSION['role'] = $user['role'];

            // Debug line
            error_log("Session variables set: " . print_r($_SESSION, true));

            // Redirect based on role
            if ($user['role'] == 1) {
                header("Location: ../admin/admindashboard.php");
            } else {
                header("Location: ../userdashboard/userdashboard.php");
            }
            exit();
        } else {
            header("Location: login.html?error=incorrect_password");
            exit();
        }
    } else {
        header("Location: ../signup/signup.html?error=user_not_found");
        exit();
    }

    $stmt->close();
}

$conn->close();
?>