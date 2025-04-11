<?php
// Enable error reporting for development, comment out for production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration variables - could be moved to a config file in production
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project1";
$role = 2; // Users' default role is 2, admin role is 1

/**
 * Validates user input data
 * 
 * @param string $name User's full name
 * @param string $email User's email address
 * @param string $password User's password
 * @return array Array containing validation status and error messages
 */
function validateInput($name, $email, $password, $confirmPassword) {
    $errors = [];
    
    // Validate name (letters and spaces only, 2-50 characters)
    if (empty($name)) {
        $errors['name'] = "Name is required";
    } elseif (!preg_match("/^[A-Za-z\s]{2,50}$/", $name)) {
        $errors['name'] = "Name should only contain letters and be 2-50 characters long";
    }
    
    // Validate email
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }
    
    // Validate password (minimum 8 characters with at least one letter, one number, one special character)
    if (empty($password)) {
        $errors['password'] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Password must be at least 8 characters long";
    } elseif (!preg_match("/[A-Za-z]/", $password) || !preg_match("/\d/", $password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
        $errors['password'] = "Password must contain at least one letter, one number, and one special character";
    }
    
    // Validate password confirmation
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = "Passwords do not match";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

/**
 * Checks if email already exists in the database
 * 
 * @param mysqli $conn Database connection
 * @param string $email Email to check
 * @return bool True if email exists, false otherwise
 */
function emailExists($conn, $email) {
    $stmt = $conn->prepare("SELECT id FROM user WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    return $exists;
}

/**
 * Creates a new user account
 * 
 * @param mysqli $conn Database connection
 * @param string $name User's full name
 * @param string $email User's email address
 * @param string $password User's password (already hashed)
 * @param int $role User's role ID
 * @return array Result of the operation
 */
function createUser($conn, $name, $email, $password, $role) {
    try {
        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO user (name, email, password, role) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("sssi", $name, $email, $password, $role);
        
        // Execute query
        $success = $stmt->execute();
        if (!$success) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $userId = $stmt->insert_id;
        $stmt->close();
        
        return [
            'success' => true,
            'user_id' => $userId,
            'message' => 'Registration successful!'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Registration failed: ' . $e->getMessage()
        ];
    }
}

// Initialize variables
$name = $email = $password = $confirmPassword = "";
$registrationResult = null;

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data
    $name = trim(htmlspecialchars($_POST['name']));
    $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmpassword'];
    
    // Create database connection
    $conn = new mysqli($servername, $username, $password_db = $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Validate input
    $validation = validateInput($name, $email, $password, $confirmPassword);
    
    if ($validation['valid']) {
        // Check if email already exists
        if (emailExists($conn, $email)) {
            $validation['valid'] = false;
            $validation['errors']['email'] = "Email already registered";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Create user
            $registrationResult = createUser($conn, $name, $email, $hashedPassword, $role);
            
            if ($registrationResult['success']) {
                // Redirect to login page
                echo "<script>alert('Registration successful! Redirecting to login page...');</script>";
                echo "<script>window.location.href='../login/login.html';</script>";
                exit();
            }
        }
    }
    
    // Close connection
    $conn->close();
}

// If validation failed or there was an error, display the form again with errors
// This would be implemented in a more sophisticated way with proper templating
if ($_SERVER["REQUEST_METHOD"] == "POST" && (!isset($validation['valid']) || !$validation['valid'])) {
    // This would redirect back to the form with errors
    // For simplicity, we're just alerting the error
    $errorMessage = '';
    if (isset($validation['errors'])) {
        foreach ($validation['errors'] as $error) {
            $errorMessage .= $error . "\n";
        }
    } elseif (isset($registrationResult) && !$registrationResult['success']) {
        $errorMessage = $registrationResult['message'];
    }
    
    echo "<script>alert('Registration failed: " . str_replace("'", "\\'", $errorMessage) . "');</script>";
    echo "<script>window.location.href='signup.html';</script>";
}
?>
