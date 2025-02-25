<?php
session_start();
require '../vendor/autoload.php'; // Add PHPMailer autoload
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "solestreet";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    
    // Check if email exists in database
    $stmt = $conn->prepare("SELECT userid, name FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Store token in database
        $updateStmt = $conn->prepare("UPDATE user SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
        $updateStmt->bind_param("sss", $token, $expiry, $email);
        $updateStmt->execute();

        // Create reset link
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "/login/reset-password.php?token=" . $token;
        
        // Create PHPMailer instance
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // Gmail SMTP server
            $mail->SMTPAuth   = true;
            $mail->Username   = 'your.email@gmail.com'; // Your Gmail address
            $mail->Password   = 'your-app-specific-password'; // Your Gmail app password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Recipients
            $mail->setFrom('your.email@gmail.com', 'Sole Street');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "
                <html>
                <body>
                    <h2>Password Reset Request</h2>
                    <p>Click the following link to reset your password:</p>
                    <p><a href='{$reset_link}'>{$reset_link}</a></p>
                    <p>This link will expire in 1 hour.</p>
                    <p>If you didn't request this password reset, please ignore this email.</p>
                </body>
                </html>
            ";
            $mail->AltBody = "Click the following link to reset your password: {$reset_link}";

            $mail->send();
            echo "<script>alert('Password reset instructions have been sent to your email.'); window.location.href='login.html';</script>";
        } catch (Exception $e) {
            echo "<script>alert('Error sending email. Please try again. Error: {$mail->ErrorInfo}');</script>";
        }
    } else {
        echo "<script>alert('Email not found!'); window.location.href='forgot-password.html';</script>";
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
    <title>Forgot Password</title>
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
        <p id="text">Forgot Password</p>
        <form method="post" action="forgot-password.php" id="login">
            <input type="email" name="email" placeholder="Enter your email" required><br>
            <input type="submit" value="Reset Password" id="buto">
        </form>
    </div>
</body>
</html>