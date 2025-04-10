<?php
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    require_once '../PHPMailer/src/Exception.php';
    require_once '../PHPMailer/src/PHPMailer.php';
    require_once '../PHPMailer/src/SMTP.php';

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;

    // Initialize error variable
    $error = isset($_GET['error']) ? $_GET['error'] : '';

    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "project1";

    $conn = new mysqli($servername, $username, $password, $dbname);

    // For AJAX email validation
    if(isset($_POST['check_email'])) {
        header('Content-Type: application/json');
        $email = $_POST['email'];
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM user WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        echo json_encode(['exists' => $row['count'] > 0]);
        exit();
    }

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['check_email'])) {
        if(isset($_POST['email'])) {
            $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
            
            $stmt = $conn->prepare("SELECT * FROM user WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Store email in session
                $_SESSION['reset_email'] = $email;
                
                // Debug line - remove in production
                error_log("Email stored in session: " . $_SESSION['reset_email']);
                
                // Redirect to send_otp.php with email parameter
                header("Location: send_otp.php?email=" . urlencode($email));
                exit();
            } else {
                $error = "No account found with that email.";
            }
        }
    }

    mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
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
            background:rgb(103, 104, 108);
        }

        .box {
            width: 380px;
            background: #1c1c1c;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }

        .form {
            text-align: center;
        }

        .form h2 {
            color: #45f3ff;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .form p {
            color: #8f8f8f;
            margin-bottom: 30px;
            font-size: 0.9em;
        }

        .inputBox {
            position: relative;
            margin-bottom: 20px;
        }

        .inputBox input {
            width: 100%;
            padding: 12px;
            background: #28292d;
            border: 1px solid #45f3ff;
            border-radius: 4px;
            color: #fff;
            font-size: 1em;
            outline: none;
            transition: 0.3s;
        }

        .inputBox input:focus {
            border-color: #45f3ff;
            box-shadow: 0 0 5px rgba(69, 243, 255, 0.3);
        }

        .error {
            color: #ff3333;
            font-size: 0.8em;
            text-align: left;
            margin-top: 5px;
        }

        .buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .back-btn {
            color: #8f8f8f;
            text-decoration: none;
            font-size: 0.9em;
            transition: 0.3s;
        }

        .back-btn:hover {
            color: #45f3ff;
        }

        .submit-btn {
            background: #45f3ff;
            color: #1c1c1c;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .submit-btn:hover {
            background: #3ad5e7;
        }
    </style>
</head>
<script>
    function validateEmail() 
        {
            const email = document.getElementById("email").value;
            const error = document.getElementById("emailError");

            // Check if email is empty
            if (!email) 
            {
                error.textContent = "E-mail is required.";
                return false;
            }

            // Check if email starts with a space or invalid character
            if (email[0] === " ") 
            {
                 error.textContent = "E-mail must start with a letter.";
                return false;
            }

            // Check if email has valid format and domain
            // const emailRegex = ;
        if (!/^[a-zA-Z0-9][^\s@]*@(gmail\.com|yahoo\.com|hotmail\.com|amaljyothi\.ac\.in|mca\.ajce\.in)$/.test(email)) 
        {
            // Check if it's the domain that's invalid
            if (email.includes('@')) 
            {
                const domain = email.split('@')[1];
                if (domain !== 'gmail.com' && domain !== 'yahoo.com') 
                {
                    error.textContent = "Invalid domain";
                    return false;
                }
            }
            error.textContent = "Invalid email address.";
            return false;
        }

    // Clear error message if all validations pass
        error.textContent = "";
        return true;
    }
    function validateForm() // It ensures that all fields in the form meet their respective validation criteria before allowing the form to be submitted.
        {
            const validEmail = validateEmail();
            return validEmail;//The && operator combines the results of all the validations. If any of the individual validations return false, the overall result will also be false.
        }
</script>
<body>
    <div class="box">
        <div class="form">
            <h2>Forgot Password</h2>
            <p>Enter your email to receive reset instructions</p>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="inputBox">
                    <input type="email" name="email" id="email" placeholder="Enter your email" required>
                    <p class="error" id="emailError"><?php echo htmlspecialchars($error); ?></p>
                </div>
                
                <div class="buttons">
                    <a href="login.html" class="back-btn">Back to Login</a>
                    <button type="submit" class="submit-btn">Send</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('email').addEventListener('input', function() {
            document.getElementById('emailError').textContent = '';
        });
    </script>
</body>
</html>