<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.html");
    exit();
}

// Include database connection
include '../database/connect.php';

// Initialize message variables
$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
    $messageContent = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($messageContent)) {
        $message = "Please fill in all required fields";
        $messageType = "error";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address";
        $messageType = "error";
    } else {
        // Save message to database
        try {
            $sql = "INSERT INTO contact_messages (user_id, name, email, subject, message, status, created_at) 
                   VALUES (?, ?, ?, ?, ?, 'new', NOW())";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issss", $_SESSION['user_id'], $name, $email, $subject, $messageContent);
            
            if ($stmt->execute()) {
                $message = "Your message has been sent successfully! We'll get back to you soon.";
                $messageType = "success";
                
                // Clear form data on success
                $name = $email = $subject = $messageContent = '';
            } else {
                $message = "Failed to send your message. Please try again later.";
                $messageType = "error";
            }
        } catch (Exception $e) {
            error_log("Error saving contact message: " . $e->getMessage());
            $message = "An error occurred while processing your request. Please try again later.";
            $messageType = "error";
        }
    }
}

// Get cart count for notification
$cartCount = 0;
try {
    $cartCountSql = "SELECT COUNT(*) as total FROM cart WHERE user_id = ?";
    $cartStmt = $conn->prepare($cartCountSql);
    $cartStmt->bind_param("i", $_SESSION['user_id']);
    $cartStmt->execute();
    $cartResult = $cartStmt->get_result();
    
    if ($cartResult->num_rows > 0) {
        $cartCount = $cartResult->fetch_assoc()['total'];
    }
} catch (Exception $e) {
    error_log("Error fetching cart count: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Solestreet</title>
    <link rel="stylesheet" href="userdashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3d5afe;       /* Vibrant blue */
            --hover: #536dfe;         /* Lighter blue for hover */
            --dark: #283593;          /* Darker blue */
            --white: #ffffff;
            --price-color: #1a237e;   /* Deep blue for price */
            --light-gray: #f5f5f5;
            --medium-gray: #e0e0e0;
            --dark-gray: #757575;
        }

        /* Message styles */
        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-size: 14px;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-width: 300px;
            animation: slideIn 0.5s ease-out;
        }

        .success {
            background-color: #4CAF50;
        }

        .error {
            background-color: #f44336;
        }

        .info {
            background-color: #2196F3;
        }

        .warning {
            background-color: #ff9800;
        }

        .close-btn {
            margin-left: 15px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        .close-btn:hover {
            opacity: 0.7;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        /* Cart notification badge */
        .cart-btn {
            position: relative;
            display: inline-block;
        }
        
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #f44336;
            color: white;
            font-size: 12px;
            font-weight: bold;
            min-width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* Contact page specific styles */
        .contact-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .contact-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .contact-header h1 {
            font-size: 32px;
            color: var(--dark);
            margin-bottom: 10px;
        }

        .contact-header p {
            color: var(--dark-gray);
            font-size: 16px;
            max-width: 600px;
            margin: 0 auto;
        }

        .contact-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }

        .contact-info {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .contact-info h2 {
            font-size: 22px;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--medium-gray);
        }

        .info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }

        .info-item i {
            font-size: 24px;
            color: var(--primary);
            margin-right: 15px;
            min-width: 30px;
            text-align: center;
        }

        .info-item .info-text {
            flex: 1;
        }

        .info-item h3 {
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .info-item p {
            color: var(--dark-gray);
            line-height: 1.5;
        }

        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }

        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: var(--light-gray);
            border-radius: 50%;
            color: var(--dark);
            transition: all 0.3s;
        }

        .social-links a:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }

        .contact-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .contact-form h2 {
            font-size: 22px;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--medium-gray);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            color: var(--dark);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--medium-gray);
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 120px;
        }

        .submit-btn {
            display: inline-block;
            background: linear-gradient(145deg, var(--primary), var(--dark));
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(61, 90, 254, 0.3);
        }

        /* Responsive design */
        @media screen and (max-width: 768px) {
            .contact-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .contact-header h1 {
                font-size: 28px;
            }
        }

        /* Professional Header Styles */
        .main-header {
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 12px 0;
            position: relative;
            z-index: 1000;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .header-content {
            display: flex;
            align-items: center;
        }

        .brand {
            margin-right: auto; /* Pushes everything else to the right */
        }

        .brand h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .nav-container {
            display: flex;
            align-items: center;
        }

        .main-nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .main-nav li {
            padding: 0;
            margin: 0 5px;
        }

        .main-nav a {
            display: block;
            padding: 8px 15px;
            color: #555;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.2s;
        }

        .main-nav a:hover,
        .main-nav a.active {
            color: #007bff;
        }

        .user-controls {
            display: flex;
            align-items: center;
            margin-left: 20px;
        }

        .cart-icon {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f5f7fa;
            color: #555;
            margin-right: 15px;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .cart-icon:hover {
            background-color: #e9ecef;
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dc3545;
            color: white;
            font-size: 12px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-dropdown {
            position: relative;
        }

        .user-toggle {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            background-color: #f5f7fa;
            border-radius: 20px;
            text-decoration: none;
            color: #555;
            transition: background-color 0.2s;
        }

        .user-toggle:hover {
            background-color: #e9ecef;
        }

        .user-toggle i {
            margin-right: 8px;
        }

        .dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            width: 180px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 8px 0;
            margin-top: 10px;
            display: none;
            z-index: 100;
        }

        .user-dropdown:hover .dropdown-menu {
            display: block;
        }

        .dropdown-menu a {
            display: block;
            padding: 10px 15px;
            color: #555;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .dropdown-menu a:hover {
            background-color: #f5f7fa;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .header-content {
                flex-wrap: wrap;
            }
            
            .brand {
                margin-bottom: 10px;
            }
            
            .nav-container {
                width: 100%;
                justify-content: space-between;
            }
            
            .main-nav {
                order: 2;
            }
            
            .main-nav ul {
                flex-wrap: wrap;
            }
            
            .user-controls {
                order: 1;
                margin-left: 0;
            }
        }

        /* Prominent Alert Message */
        .alert-message {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background-color: #4CAF50; /* Default green */
            color: white;
            text-align: center;
            padding: 15px 30px;
            z-index: 9999; /* Ensures it's above everything */
            display: flex;
            justify-content: center;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        .alert-message.error {
            background-color: #f44336;
        }

        .alert-message.info {
            background-color: #2196F3;
        }

        .alert-message.warning {
            background-color: #ff9800;
        }

        .alert-content {
            flex: 1;
            font-weight: 500;
            font-size: 16px;
        }

        .alert-close {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0 0 0 20px;
            opacity: 0.8;
        }

        .alert-close:hover {
            opacity: 1;
        }

        /* Add padding to body to prevent content from being hidden behind the alert */
        body {
            padding-top: 50px;
        }

        /* Only add the padding when alert is present */
        <?php if($message): ?>
        body {
            padding-top: 50px;
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <?php if($message): ?>
    <div class="alert-message <?php echo htmlspecialchars($messageType); ?>">
        <div class="alert-content">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <button class="alert-close" onclick="this.parentElement.style.display='none';">&times;</button>
    </div>
    <?php endif; ?>

    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <!-- Left: Brand Name -->
                <div class="brand">
                    <h1>Solestreet</h1>
                </div>
                
                <!-- Middle to Right: Navigation and Controls -->
                <div class="nav-container">
                    <nav class="main-nav">
                        <ul>
                            <li><a href="userdashboard.php">Home</a></li>
                            <li><a href="#">Footwear</a></li>
                            <li><a href="#">Shop</a></li>
                            <li><a href="contact.php" class="active">Contact</a></li>
                            <li><a href="#">Sale</a></li>
                        </ul>
                    </nav>
                    
                    <div class="user-controls">
                        <a href="cart.php" class="cart-icon">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if($cartCount > 0): ?>
                                <span class="cart-count"><?php echo $cartCount; ?></span>
                            <?php endif; ?>
                        </a>
                        
                        <div class="user-dropdown">
                            <a href="#" class="user-toggle">
                                <i class="fas fa-user"></i>
                                <span><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : 'Account'; ?></span>
                            </a>
                            <div class="dropdown-menu">
                                <a href="profile.php">Profile</a>
                                <a href="my_orders.php">My Orders</a>
                                <a href="../logout.php">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="contact-container">
        <div class="contact-header">
            <h1>Get in Touch</h1>
            <p>Have questions about our products or services? We're here to help you. Fill out the form below and we'll get back to you as soon as possible.</p>
        </div>

        <div class="contact-content">
            <div class="contact-info">
                <h2>Contact Information</h2>
                
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div class="info-text">
                        <h3>Our Location</h3>
                        <p>KK ROAD, Fashion District<br>KOTTAYAM, India 110001</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-phone-alt"></i>
                    <div class="info-text">
                        <h3>Call Us</h3>
                        <p>+91 98765 43210<br>+91 12345 67890</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <div class="info-text">
                        <h3>Email Us</h3>
                        <p>support@solestreet.com<br>info@solestreet.com</p>
                    </div>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <div class="info-text">
                        <h3>Working Hours</h3>
                        <p>Monday - Friday: 9:00 AM - 8:00 PM<br>Saturday & Sunday: 10:00 AM - 6:00 PM</p>
                    </div>
                </div>
                
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            
            <div class="contact-form">
                <h2>Send Us a Message</h2>
                
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="form-group">
                        <label for="name">Your Name *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Your Email *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" class="form-control" id="subject" name="subject" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Your Message *</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required><?php echo isset($messageContent) ? htmlspecialchars($messageContent) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Auto-hide alert after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alertMessage = document.querySelector('.alert-message');
            if(alertMessage) {
                setTimeout(function() {
                    alertMessage.style.display = 'none';
                    document.body.style.paddingTop = '0'; // Remove padding after alert is hidden
                }, 5000);
            }
        });
    </script>
</body>
</html> 