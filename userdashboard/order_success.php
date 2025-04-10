<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.html");
    exit();
}

$orderId = $_GET['order_id'] ?? 'Unknown';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - Solestreet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f8f9fa;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        
        .success-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 40px;
            text-align: center;
            max-width: 500px;
            width: 90%;
        }
        
        .success-icon {
            font-size: 80px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 15px;
        }
        
        p {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.6;
        }
        
        .order-id {
            background-color: #f8f9fa;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 30px;
            display: inline-block;
            font-weight: 500;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .btn:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <i class="fas fa-check-circle success-icon"></i>
        <h1>Order Successful!</h1>
        <p>Thank you for your purchase. Your order has been received and is being processed.</p>
        <div class="order-id">Order ID: <?php echo htmlspecialchars($orderId); ?></div>
        <a href="userdashboard.php" class="btn">Back to Home</a>
    </div>
</body>
</html> 