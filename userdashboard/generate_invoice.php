<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.html");
    exit();
}

// Include database connection
include '../database/connect.php';

// Fetch user details
$userId = $_SESSION['user_id'];
$userName = $_SESSION['name'] ?? 'Customer';

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

try {
    // Fetch user details
    $userSql = "SELECT * FROM user WHERE id = ?";
    $userStmt = $conn->prepare($userSql);
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userResult = $userStmt->get_result();
    $user = $userResult->fetch_assoc();

    // Fetch cart items
    $sql = "SELECT c.cart_id, c.quantity, p.id as product_id, p.name, p.price, p.image_path 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Calculate total
    $total = 0;
    $cartItems = [];

    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $cartItems[] = $row;
            $total += $row['price'] * $row['quantity'];
        }
    }

    // If cart is empty, redirect back to cart page
    if (empty($cartItems)) {
        header("Location: cart.php?message=Your cart is empty&type=info");
        exit();
    }
    
    $shipping = ($total > 0) ? 100 : 0;
    $grandTotal = $total + $shipping;

    // Output HTML invoice
    header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Solestreet Invoice</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
            line-height: 1.6;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .invoice-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
        }
        .invoice-header h1 {
            color: #444;
            margin-bottom: 5px;
        }
        .invoice-details {
            margin-bottom: 30px;
        }
        .invoice-details p {
            margin: 5px 0;
        }
        .logo {
            text-align: center;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f8f8f8;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .summary {
            margin-left: auto;
            width: 300px;
        }
        .summary p {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
        }
        .summary .total {
            font-weight: bold;
            font-size: 18px;
            border-top: 2px solid #eee;
            padding-top: 10px;
            margin-top: 10px;
        }
        .terms {
            margin-top: 40px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .buttons {
            margin: 30px 0;
            text-align: center;
        }
        .print-button, .back-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            text-align: center;
            text-decoration: none;
            display: inline-block;
            font-size: 16px;
            margin: 4px 2px;
            cursor: pointer;
            border-radius: 4px;
        }
        .back-button {
            background-color: #555;
        }
        @media print {
            .buttons {
                display: none;
            }
            .invoice-container {
                border: none;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="buttons">
            <button class="print-button" onclick="window.print()">Print Invoice</button>
            <button class="back-button" onclick="window.location.href='cart.php'">Back to Cart</button>
        </div>
        
        <div class="invoice-header">
            <div class="logo">SOLESTREET</div>
            <h1>INVOICE</h1>
            <p>#INV-<?php echo time(); ?></p>
        </div>
        
        <div class="invoice-details">
            <p><strong>Date:</strong> <?php echo date('d-m-Y'); ?></p>
            <p><strong>Customer:</strong> <?php echo htmlspecialchars($userName); ?></p>
            <?php if (isset($user['email'])): ?>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <?php endif; ?>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                    <td class="text-right">₹<?php echo number_format($item['price'], 2); ?></td>
                    <td class="text-right">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="summary">
            <p>
                <span><strong>Subtotal:</strong></span>
                <span>₹<?php echo number_format($total, 2); ?></span>
            </p>
            <p>
                <span><strong>Shipping:</strong></span>
                <span>₹<?php echo number_format($shipping, 2); ?></span>
            </p>
            <p class="total">
                <span><strong>Total:</strong></span>
                <span>₹<?php echo number_format($grandTotal, 2); ?></span>
            </p>
        </div>
        
        <div class="terms">
            <h3>Terms and Conditions:</h3>
            <p>Thank you for your purchase. All prices include applicable taxes. Products can be returned within 7 days of delivery.</p>
        </div>
    </div>
    
    <script>
        // Auto-print when the page loads (after a short delay to ensure rendering)
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 1000);
        }
    </script>
</body>
</html>
<?php
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
