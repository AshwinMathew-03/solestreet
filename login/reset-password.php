<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "solestreet";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token and check if it's expired
    $stmt = $conn->prepare("SELECT userid FROM user WHERE reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo "<script>alert('Invalid or expired reset link.'); window.location.href='login.html';</script>";
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $_POST['token'];
    
    if ($new_password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!');</script>";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password and clear reset token
        $stmt = $conn->prepare("UPDATE user SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE reset_token = ?");
        $stmt->bind_param("ss", $hashed_password, $token);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo "<script>alert('Password updated successfully!'); window.location.href='login.html';</script>";
        } else {
            echo "<script>alert('Error updating password.');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="login.css">
    <style>
        body {
            background-image: url('shoelgn.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }

        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            padding: 10px 20px;
            background-color: rgba(255, 255, 255, 0.8);
            border: none;
            border-radius: 5px;
            color: #333;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background-color: rgba(255, 255, 255, 1);
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <a href="login.html" class="back-button">Back</a>
    <div class="login">
        <p id="text">Reset Password</p>
        <form method="post" action="reset-password.php" id="login">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
            <input type="password" name="new_password" placeholder="Enter new password" required><br>
            <input type="password" name="confirm_password" placeholder="Confirm new password" required><br>
            <input type="submit" value="Reset Password" id="buto">
        </form>
    </div>
</body>
</html> 