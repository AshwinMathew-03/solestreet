<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.html");
    exit();
}

// Include database connection
include '../database/connect.php';

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    header("Location: userdashboard.php");
    exit();
}

$orderId = $_GET['order_id'];

// Fetch order details
try {
    $orderSql = "SELECT * FROM orders WHERE order_id = ? AND user_id = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param("si", $orderId, $_SESSION['user_id']);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    
    if ($orderResult->num_rows === 0) {
        header("Location: userdashboard.php?message=Order not found&type=error");
        exit();
    }
    
    $order = $orderResult->fetch_assoc();
    
    // Fetch order items
    $itemsSql = "SELECT * FROM order_items WHERE order_id = ?";
    $itemsStmt = $conn->prepare($itemsSql);
    $itemsStmt->bind_param("s", $orderId);
    $itemsStmt->execute();
    $orderItems = $itemsStmt->get_result();
    
} catch (Exception $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    header("Location: userdashboard.php?message=Error retrieving order details&type=error");
    exit();
}

// Fetch user data
$user = null;
try {
    $userSql = "SELECT * FROM user WHERE id = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->bind_param("i", $_SESSION['user_id']);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    
    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
    }
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Solestreet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 5%;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 30px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
        }
        
        .account-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background-color: #f44336;
            color: white;
            padding: 8px 15px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
        }
        
        .confirmation-box {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
            margin-bottom: 30px;
        }
        
        .success-icon {
            font-size: 60px;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        
        .confirmation-title {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .confirmation-message {
            color: #666;
            margin-bottom: 30px;
        }
        
        .order-details {
            background-color: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-title {
            margin-bottom: 20px;
            color: #333;
            font-size: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .order-items {
            margin-top: 20px;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .item-name {
            font-weight: 500;
        }
        
        .back-btn {
            display: inline-block;
            background-color: #2196F3;
            color: white;
            padding: 12px 25px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            margin-top: 20px;
        }
        
        .back-btn:hover {
            background-color: #0b7dda;
        }
    </style>
</head>
<body>
    <nav>
        <a href="userdashboard.php" class="logo">Solestreet</a>
        
        <div class="nav-links">
            <a href="userdashboard.php">Home</a>
            <a href="cart.php">Cart</a>
            <a href="contact.php">Contact</a>
        </div>
        
        <div class="account-section">
            <a href="profile.php">
                <img src="<?php echo isset($user['profile_image']) && !empty($user['profile_image']) ? '../uploads/profile_images/' . htmlspecialchars($user['profile_image']) : 'https://via.placeholder.com/40'; ?>" 
                     style="border-radius: 50%;" 
                     height="40px" 
                     width="40px" 
                     alt="Profile">
            </a>
            <p><?php echo isset($user['name']) ? htmlspecialchars($user['name']) : 'User'; ?></p>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="confirmation-box">
            <i class="fas fa-check-circle success-icon"></i>
            <h1 class="confirmation-title">Order Confirmed!</h1>
            <p class="confirmation-message">Thank you for your order. We've received your order and will begin processing it soon.</p>
            <p>Your order ID is: <strong><?php echo htmlspecialchars($orderId); ?></strong></p>
            
            <a href="userdashboard.php" class="back-btn">Continue Shopping</a>
        </div>
        
        <div class="order-details">
            <h2 class="section-title">Order Details</h2>
            
            <div class="detail-row">
                <span>Order Date:</span>
                <span><?php echo date('F j, Y, g:i a', strtotime($order['order_date'])); ?></span>
            </div>
            
            <div class="detail-row">
                <span>Order Status:</span>
                <span><?php 
                    if ($order['status'] == 'pending') {
                        echo '<span style="color: #ff9800;">Pending</span>';
                    } else if ($order['status'] == 'paid') {
                        echo '<span style="color: #4CAF50;">Paid</span>';
                    } else {
                        echo htmlspecialchars($order['status']);
                    }
                ?></span>
            </div>
            
            <div class="detail-row">
                <span>Payment Method:</span>
                <span><?php 
                    if ($order['payment_method'] == 'cod') {
                        echo 'Cash on Delivery';
                    } else if ($order['payment_method'] == 'razorpay') {
                        echo 'Online Payment';
                    } else {
                        echo htmlspecialchars($order['payment_method']);
                    }
                ?></span>
            </div>
            
            <div class="detail-row">
                <span>Shipping Address:</span>
                <span><?php echo htmlspecialchars($order['address']); ?></span>
            </div>
            
            <h2 class="section-title">Order Items</h2>
            
            <div class="order-items">
                <?php while ($item = $orderItems->fetch_assoc()): ?>
                <div class="order-item">
                    <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                    <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                    <div class="item-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>
</html>