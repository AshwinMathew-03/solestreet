<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../PHPMailer/src/Exception.php';
require_once '../PHPMailer/src/PHPMailer.php';
require_once '../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$error = "";

// Generate OTP function
function generateOTP() {
    $otp = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $_SESSION['otp'] = $otp;
    $_SESSION['otp_time'] = time();
    return $otp;
}

// Generate new OTP if not exists or expired
if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_time']) || (time() - $_SESSION['otp_time'] > 600)) {
    $otp = generateOTP();
} else {
    $otp = $_SESSION['otp'];
}

// Send OTP email if email parameter exists
if (isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)) {
    $email = $_GET['email'];
    $_SESSION['reset_email'] = $email; // Store email in session

    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'solestreet990@gmail.com';
        $mail->Password   = 'opmj dcjk gcmw gmzm';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        //Recipients
        $mail->setFrom('solestreet990@gmail.com', 'SoleStreet');
        $mail->addAddress($email);

        //Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset OTP';
        $mail->Body    = "
            <html>
            <body>
                <h2 style='color:#333;'>Password Reset OTP</h2>
                <p style='color:#555;'>Your OTP for password reset is: <strong>{$otp}</strong></p>
                <p style='color:#999;font-size:12px;'>This OTP will expire in 10 minutes.</p>
                <p style='color:#999;font-size:12px;'>If you didn't request this, please ignore this email.</p>
            </body>
            </html>
        ";

        $mail->send();
    } catch (Exception $e) {
        $error = "Email could not be sent. Error: " . $mail->ErrorInfo;
    }
}

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['otp'])) {
        $entered_otp = implode('', $_POST['otp']);
        
        // Verify OTP
        if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_time'])) {
            $error = "OTP has expired. Please request a new one.";
        } 
        else if (time() - $_SESSION['otp_time'] > 600) {
            $error = "OTP has expired. Please request a new one.";
            unset($_SESSION['otp']);
            unset($_SESSION['otp_time']);
        }
        else if ($entered_otp === $_SESSION['otp']) {
            // OTP is valid
            unset($_SESSION['otp']);
            unset($_SESSION['otp_time']);
            header("Location: reset_password.php?email=" . urlencode($_SESSION['reset_email']));
            exit();
        } 
        else {
            $error = "Invalid OTP. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Verification</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #23242a;
        }

        .box {
            width: 380px;
            background: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .form {
            text-align: center;
        }

        .form h2 {
            color: #1a73e8;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .form p {
            color: #666;
            margin-bottom: 30px;
            font-size: 0.9em;
        }

        .otp-container {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 20px;
        }

        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #fff;
            color: #333;
            outline: none;
            transition: 0.3s;
        }

        .otp-input:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 5px rgba(26, 115, 232, 0.2);
        }

        .error {
            color: #d93025;
            font-size: 0.8em;
            text-align: center;
            margin: 10px 0;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .resend-link {
            color: #666;
            text-decoration: none;
            font-size: 0.9em;
            transition: 0.3s;
        }

        .resend-link:hover {
            color: #1a73e8;
        }

        .submit-btn {
            background: #1a73e8;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: 0.3s;
        }

        .submit-btn:hover {
            background: #1557b0;
        }

        @media (max-width: 480px) {
            .box {
                width: 90%;
                margin: 0 20px;
            }
            
            .otp-container {
                gap: 8px;
            }
            
            .otp-input {
                width: 45px;
                height: 45px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="form">
            <h2>OTP Verification</h2>
            <p>Please enter the 4-digit code sent to your email<br>
                <strong><?php 
                    if(isset($_SESSION['reset_email'])) {
                        echo htmlspecialchars($_SESSION['reset_email']);
                    }
                ?></strong>
            </p>
            
            <?php if(!empty($error)): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" id="otpForm">
                <div class="otp-container">
                    <input type="text" name="otp[]" class="otp-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" name="otp[]" class="otp-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" name="otp[]" class="otp-input" maxlength="1" pattern="[0-9]" required>
                    <input type="text" name="otp[]" class="otp-input" maxlength="1" pattern="[0-9]" required>
                </div>
                
                <div class="buttons">
                    <a href="?resend=true" class="resend-link">Resend OTP</a>
                    <button type="submit" class="submit-btn">Verify</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const otpInputs = document.querySelectorAll('.otp-input');

        otpInputs.forEach((input, index) => {
            // Auto-focus next input
            input.addEventListener('input', (e) => {
                if (e.target.value.length === 1) {
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                }
            });

            // Handle backspace
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && !e.target.value && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });

            // Allow only numbers
            input.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });
        });

        // Handle paste event
        otpInputs[0].addEventListener('paste', (e) => {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').slice(0, 4);
            if (/^\d+$/.test(pastedData)) {
                [...pastedData].forEach((digit, index) => {
                    if (otpInputs[index]) {
                        otpInputs[index].value = digit;
                    }
                });
                if (otpInputs[pastedData.length]) {
                    otpInputs[pastedData.length].focus();
                }
            }
        });
    </script>
</body>
</html>