<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.html");
    exit();
}

// Include database connection
include '../database/connect.php';

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    header("Location: userdashboard.php?message=Invalid order&type=error");
    exit();
}

$orderId = $_GET['order_id'];
$userId = $_SESSION['user_id'];

// Fetch order details
try {
    $orderSql = "SELECT o.*, u.name, u.email, u.phone, u.address 
                FROM orders o
                JOIN user u ON o.user_id = u.id 
                WHERE o.id = ? AND o.user_id = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param("ii", $orderId, $userId);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    
    if ($orderResult->num_rows == 0) {
        header("Location: userdashboard.php?message=Order not found&type=error");
        exit();
    }
    
    $order = $orderResult->fetch_assoc();
    
    // Fetch order items
    $itemsSql = "SELECT oi.*, p.name, p.image_path, p.price 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
    $itemsStmt = $conn->prepare($itemsSql);
    $itemsStmt->bind_param("i", $orderId);
    $itemsStmt->execute();
    $orderItems = $itemsStmt->get_result();
    
} catch (Exception $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    header("Location: userdashboard.php?message=Error loading order details&type=error");
    exit();
}

// Format date
$orderDate = new DateTime($order['created_at']);
$formattedDate = $orderDate->format('d M Y, h:i A');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $order['id']; ?> - Solestreet</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            padding: 20px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 40px;
        }
        
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .logo {
            font-size: 28px;
            font-weight: 700;
            color: #1a237e;
        }
        
        .invoice-title {
            font-size: 24px;
            font-weight: 700;
            text-align: right;
        }
        
        .invoice-id {
            color: #666;
            font-size: 16px;
            margin-top: 5px;
        }
        
        .invoice-section {
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .invoice-details {
            display: flex;
            justify-content: space-between;
        }
        
        .invoice-details > div {
            flex: 1;
        }
        
        .detail-row {
            margin-bottom: 8px;
        }
        
        .detail-label {
            color: #777;
            margin-right: 10px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        .items-table th {
            background-color: #f9f9f9;
            text-align: left;
            padding: 12px;
            font-weight: 600;
            color: #444;
        }
        
        .items-table td {
            padding: 12px;
            border-top: 1px solid #eee;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .product-name {
            margin-bottom: 5px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .invoice-summary {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        
        .summary-table {
            width: 100%;
            max-width: 400px;
            margin-left: auto;
        }
        
        .summary-table td {
            padding: 8px 0;
        }
        
        .summary-table td:last-child {
            text-align: right;
        }
        
        .grand-total {
            font-size: 18px;
            font-weight: 700;
            color: #1a237e;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        
        .invoice-footer {
            margin-top: 50px;
            text-align: center;
            color: #777;
            font-size: 14px;
        }
        
        .print-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background-color: #1a237e;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 30px;
            transition: all 0.3s ease;
        }
        
        .print-btn:hover {
            background-color: #3949ab;
        }
        
        @media print {
            body {
                padding: 0;
                background: white;
            }
            
            .invoice-container {
                box-shadow: none;
                padding: 0;
            }
            
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="invoice-header">
            <div class="logo">Solestreet</div>
            <div>
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-id">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></div>
            </div>
        </div>
        
        <div class="invoice-section">
            <div class="invoice-details">
                <div>
                    <div class="section-title">Bill To</div>
                    <div class="detail-row"><?php echo htmlspecialchars($order['name']); ?></div>
                    <div class="detail-row"><?php echo htmlspecialchars($order['email']); ?></div>
                    <div class="detail-row"><?php echo htmlspecialchars($order['phone']); ?></div>
                    <div class="detail-row"><?php echo htmlspecialchars($order['address']); ?></div>
                </div>
                <div>
                    <div class="section-title">Invoice Details</div>
                    <div class="detail-row">
                        <span class="detail-label">Invoice Date:</span>
                        <?php echo $formattedDate; ?>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Order ID:</span>
                        #<?php echo $order['id']; ?>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Payment Status:</span>
                        <span style="color: #4caf50; font-weight: 600;">Paid</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="invoice-section">
            <div class="section-title">Order Items</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 10%;">Image</th>
                        <th style="width: 40%;">Product</th>
                        <th style="width: 15%;">Price</th>
                        <th style="width: 15%;">Quantity</th>
                        <th style="width: 20%;" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal = 0;
                    while ($item = $orderItems->fetch_assoc()):
                        $itemTotal = $item['price'] * $item['quantity'];
                        $subtotal += $itemTotal;
                    ?>
                    <tr>
                        <td>
                            <img src="../uploads/products/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                class="product-image" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        </td>
                        <td>
                            <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        </td>
                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td class="text-right">₹<?php echo number_format($itemTotal, 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="invoice-summary">
            <table class="summary-table">
                <tr>
                    <td>Subtotal</td>
                    <td>₹<?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <?php 
                // Shipping cost (if available in your order)
                $shipping = isset($order['shipping_cost']) ? $order['shipping_cost'] : 0;
                
                // Tax calculation (if you store tax in your orders table)
                $tax = isset($order['tax']) ? $order['tax'] : round($subtotal * 0.05, 2); // Default 5% tax
                
                // Calculate grand total
                $grandTotal = $subtotal + $shipping + $tax;
                ?>
                <tr>
                    <td>Shipping</td>
                    <td>₹<?php echo number_format($shipping, 2); ?></td>
                </tr>
                <tr>
                    <td>Tax (5%)</td>
                    <td>₹<?php echo number_format($tax, 2); ?></td>
                </tr>
                <tr class="grand-total">
                    <td>Grand Total</td>
                    <td>₹<?php echo number_format($grandTotal, 2); ?></td>
                </tr>
            </table>
        </div>
        
        <div class="invoice-footer">
            <p>Thank you for shopping with Solestreet!</p>
            <p>For any questions, please contact support@solestreet.com</p>
        </div>
        
        <div style="text-align: center;">
            <button class="print-btn" onclick="window.print()">
                <i class="fas fa-print"></i> Print Invoice
            </button>
        </div>
    </div>
</body>
</html> 