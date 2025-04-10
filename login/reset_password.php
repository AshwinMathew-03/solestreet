<?php
    session_start();
    include "../database/database.php";
    $dbname="project1";
    mysqli_select_db($conn,$dbname);
    $email = urldecode($_GET['email']);
    if($_SERVER['REQUEST_METHOD']=='POST')
    {
        $password=$_POST['password'];
        $hashed_password=password_hash($password,PASSWORD_DEFAULT);
        //collecting email from session
        if(isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL))
        {
            echo $email;
            $sql = "UPDATE user SET password='$hashed_password' WHERE email='$email'";
            if (mysqli_query($conn, $sql)) 
            {
                header("Location: login.html");
            } else {
                echo "Error updating password: " . mysqli_error($conn);
            }
        }
    }
    mysqli_close( $conn );
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
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

        .inputBox {
            position: relative;
            margin-bottom: 20px;
        }

        .inputBox input {
            width: 100%;
            padding: 12px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 4px;
            color: #333;
            font-size: 1em;
            outline: none;
            transition: 0.3s;
        }

        .inputBox input:focus {
            border-color: #1a73e8;
            box-shadow: 0 0 5px rgba(26, 115, 232, 0.2);
        }

        .error {
            color: #d93025;
            font-size: 0.8em;
            text-align: left;
            margin-top: 5px;
        }

        .buttons {
            display: flex;
            justify-content: center;
            margin-top: 20px;
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
            width: 100%;
        }

        .submit-btn:hover {
            background: #1557b0;
        }

        @media (max-width: 480px) {
            .box {
                width: 90%;
                margin: 0 20px;
            }
        }
    </style>
</head>
<body>
    <div class="box">
        <div class="form">
            <h2>Reset Password</h2>
            <p>Please set your new password</p>
            
            <form method="post" onsubmit="return validateForm()">
                <div class="inputBox">
                    <input type="password" name="password" id="password" 
                           placeholder="Enter new password" onkeyup="validatePassword()">
                    <p class="error" id="passwordError"></p>
                </div>
                
                <div class="inputBox">
                    <input type="password" name="confirmpassword" id="confirmpassword" 
                           placeholder="Confirm new password" onkeyup="validateConfirmPassword()">
                    <p class="error" id="confirmpasswordError"></p>
                </div>
                
                <div class="buttons">
                    <button type="submit" class="submit-btn">Change Password</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function validatePassword() {
            const password = document.getElementById("password").value;
            const error = document.getElementById("passwordError");
            if (!password) {
                error.textContent = "Password is required.";
                return false;
            } else if (password.length < 6) {
                error.textContent = "Password must be at least 6 characters.";
                return false;
            } else if (!/[^a-zA-Z0-9]/.test(password)) {
                error.textContent = "Password should contain at least one special character.";
                return false;
            }
            error.textContent = "";
            return true;
        }

        function validateConfirmPassword() {
            const password = document.getElementById("password").value;
            const confirmpassword = document.getElementById("confirmpassword").value;
            const error = document.getElementById("confirmpasswordError");
            if (!confirmpassword) {
                error.textContent = "Confirm password is required.";
                return false;
            } else if (confirmpassword !== password) {
                error.textContent = "Passwords do not match.";
                return false;
            }
            error.textContent = "";
            return true;
        }

        function validateForm() {
            const validPassword = validatePassword();
            const validConfirmPassword = validateConfirmPassword();
            return validPassword && validConfirmPassword;
        }
    </script>
</body>
</html>