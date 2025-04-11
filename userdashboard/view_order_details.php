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
    header("Location: my_orders.php?message=Invalid order&type=error");
    exit();
}

$orderId = $_GET['order_id'];
$userId = $_SESSION['user_id'];

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

// Fetch order details
try {
    $orderSql = "SELECT * FROM orders WHERE order_id = ? AND user_id = ?";
    $orderStmt = $conn->prepare($orderSql);
    $orderStmt->bind_param("si", $orderId, $userId);
    $orderStmt->execute();
    $orderResult = $orderStmt->get_result();
    
    if ($orderResult->num_rows === 0) {
        header("Location: my_orders.php?message=Order not found&type=error");
        exit();
    }
    
    $order = $orderResult->fetch_assoc();
    
    // Fetch order items
    $itemsSql = "SELECT oi.*, p.name, p.image_path FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
    $itemsStmt = $conn->prepare($itemsSql);
    $itemsStmt->bind_param("s", $orderId);
    $itemsStmt->execute();
    $orderItems = $itemsStmt->get_result();
    
} catch (Exception $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    header("Location: my_orders.php?message=Error retrieving order details&type=error");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Solestreet</title>
    <link rel="stylesheet" href="userdashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .order-details-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .order-details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
            flex-wrap: wrap;
        }

        .order-details-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            position: relative;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .order-details-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .order-summary {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .summary-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .summary-label {
            font-size: 14px;
            color: #666;
        }

        .summary-value {
            font-size: 16px;
            font-weight: 600;
            color: #333;
        }

        .order-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff8e1;
            color: #f57c00;
        }

        .status-processing {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .status-shipped {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .status-delivered {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .status-cancelled {
            background-color: #ffebee;
            color: #d32f2f;
        }

        .status-paid {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .order-progress {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
        }

        .progress-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }

        .progress-track {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            position: relative;
        }

        .progress-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
            z-index: 1;
            flex: 1;
        }

        .step-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            color: #999;
            border: 2px solid #ddd;
            transition: all 0.3s;
        }

        .step-icon i {
            font-size: 18px;
        }

        .step-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .step-date {
            font-size: 12px;
            color: #999;
        }

        .progress-track:before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 2px;
            background: #ddd;
            z-index: 0;
        }

        .progress-track:after {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            height: 2px;
            background: var(--primary);
            z-index: 0;
            transition: width 0.5s ease;
        }

        .step-complete .step-icon {
            background: #e8f5e9;
            color: #388e3c;
            border-color: #4caf50;
        }

        .step-current .step-icon {
            background: #e3f2fd;
            color: #1976d2;
            border-color: #2196f3;
            transform: scale(1.1);
            box-shadow: 0 0 0 5px rgba(33, 150, 243, 0.1);
        }

        .step-pending .step-icon {
            background: #f5f5f5;
            color: #999;
            border-color: #ddd;
        }

        .order-items-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .order-items {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .order-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f5f5f5;
        }

        .order-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            background: #f5f5f5;
            border: 1px solid #eee;
        }

        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .item-price {
            color: var(--price-color);
            font-weight: 600;
            margin-bottom: 5px;
        }

        .item-quantity {
            font-size: 14px;
            color: #666;
        }

        .item-total {
            font-weight: 600;
            color: #333;
            text-align: right;
        }

        .order-totals {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .total-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .total-label {
            color: #666;
        }

        .total-value {
            font-weight: 600;
            color: #333;
        }

        .grand-total {
            font-size: 18px;
            font-weight: 700;
            color: var(--price-color);
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #f5f5f5;
        }

        .order-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn-action {
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-secondary {
            background: #f5f5f5;
            color: #333;
        }

        .btn-primary:hover {
            background: var(--hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(61, 90, 254, 0.2);
        }

        .btn-secondary:hover {
            background: #e0e0e0;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .summary-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .progress-track {
                flex-direction: column;
                align-items: flex-start;
                gap: 20px;
            }
            
            .progress-step {
                width: 100%;
                flex-direction: row;
                align-items: center;
                text-align: left;
                gap: 15px;
            }
            
            .progress-track:before, 
            .progress-track:after {
                width: 2px !important;
                height: auto;
                top: 0;
                bottom: 0;
                left: 20px;
            }
            
            .order-item {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .item-total {
                text-align: left;
                margin-top: 10px;
            }
            
            .order-actions {
                flex-direction: column;
            }
            
            .btn-action {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="top-bar-content">
                <div class="top-contact">
                    <a href="tel:+1234567890"><i class="fas fa-phone-alt"></i> <span>+1 (234) 567-890</span></a>
                    <a href="mailto:info@solestreet.com"><i class="fas fa-envelope"></i> <span>info@solestreet.com</span></a>
                </div>
                <div class="top-contact">
                    <a href="#"><i class="fas fa-shipping-fast"></i> <span>Free Shipping Over ₹1500</span></a>
                </div>
            </div>
        </div>
    
        <div class="container">
            <div class="header-content">
                <div class="brand">
                    <h1>Sole<span>street</span></h1>
                </div>
                
                <div class="nav-container">
                    <button class="mobile-menu-btn">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="top-social-links">
                        <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
                        <a href="https://twitter.com" target="_blank"><i class="fab fa-twitter"></i></a>
                        <a href="https://pinterest.com" target="_blank"><i class="fab fa-pinterest-p"></i></a>
                    </div>
                    
                    <nav class="main-nav">
                        <ul>
                            <li><a href="userdashboard.php">Home</a></li>
                            <li><a href="products.php">Products</a></li>
                            <li><a href="contact.php">Contact</a></li>
                            <li><a href="about.php">About</a></li>
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
                                <a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a>
                                <a href="my_orders.php"><i class="fas fa-box"></i> My Orders</a>
                                <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="order-details-container">
        <div class="order-details-header">
            <h1 class="order-details-title">Order Details</h1>
            <a href="my_orders.php" class="btn-action btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>
        
        <div class="order-summary">
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="summary-label">Order ID</div>
                    <div class="summary-value">#<?php echo htmlspecialchars($order['order_id']); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Order Date</div>
                    <div class="summary-value"><?php echo date('F j, Y', strtotime($order['order_date'])); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Status</div>
                    <div class="summary-value">
                        <?php 
                        $statusClass = 'status-' . strtolower($order['status']);
                        echo '<span class="order-status ' . $statusClass . '">' . ucfirst($order['status']) . '</span>';
                        ?>
                    </div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Payment Method</div>
                    <div class="summary-value">
                        <?php 
                        echo ($order['payment_method'] == 'cod') ? 'Cash on Delivery' : 'Online Payment';
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="order-progress">
            <div class="progress-title">Order Progress</div>
            <?php
            // Define order progress steps
            $orderSteps = [
                ['label' => 'Order Placed', 'icon' => 'fa-shopping-cart', 'date' => date('M d, Y', strtotime($order['order_date']))],
                ['label' => 'Processing', 'icon' => 'fa-cog', 'date' => '-'],
                ['label' => 'Shipped', 'icon' => 'fa-truck', 'date' => '-'],
                ['label' => 'Delivered', 'icon' => 'fa-check-circle', 'date' => '-']
            ];
            
            // Determine current step
            $currentStep = 0;
            switch($order['status']) {
                case 'pending':
                    $currentStep = 0;
                    break;
                case 'processing':
                    $currentStep = 1;
                    break;
                case 'shipped':
                    $currentStep = 2;
                    break;
                case 'delivered':
                    $currentStep = 3;
                    break;
                case 'paid':
                    $currentStep = 1;
                    break;
                default:
                    $currentStep = 0;
            }
            
            // Adjust the progress bar width
            $progressWidth = ($currentStep / (count($orderSteps) - 1)) * 100;
            ?>
            
            <div class="progress-track" style="--progress-width: <?php echo $progressWidth; ?>%">
                <?php foreach($orderSteps as $index => $step): 
                    $stepClass = $index < $currentStep ? 'step-complete' : ($index === $currentStep ? 'step-current' : 'step-pending');
                ?>
                <div class="progress-step <?php echo $stepClass; ?>">
                    <div class="step-icon">
                        <i class="fas <?php echo $step['icon']; ?>"></i>
                    </div>
                    <div>
                        <div class="step-label"><?php echo $step['label']; ?></div>
                        <div class="step-date"><?php echo $step['date']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="order-items-section">
            <div class="section-title">Order Items</div>
            <div class="order-items">
                <?php 
                $subtotal = 0;
                while ($item = $orderItems->fetch_assoc()): 
                    $itemTotal = $item['price'] * $item['quantity'];
                    $subtotal += $itemTotal;
                ?>
                <div class="order-item">
                    <div class="item-image">
                        <?php if (!empty($item['image_path'])): ?>
                        <img src="../uploads/products/<?php echo htmlspecialchars($item['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($item['product_name'] ?? $item['name']); ?>">
                        <?php else: ?>
                        <div style="width: 100%; height: 100%; background: #f5f5f5; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-box" style="color: #ccc; font-size: 24px;"></i>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="item-details">
                        <div class="item-name"><?php echo htmlspecialchars($item['product_name'] ?? $item['name']); ?></div>
                        <div class="item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                        <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                    </div>
                    <div class="item-total">₹<?php echo number_format($itemTotal, 2); ?></div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <div class="order-totals">
            <div class="section-title">Order Summary</div>
            <div class="total-row">
                <div class="total-label">Subtotal</div>
                <div class="total-value">₹<?php echo number_format($subtotal, 2); ?></div>
            </div>
            <div class="total-row">
                <div class="total-label">Shipping</div>
                <div class="total-value">₹100.00</div>
            </div>
            <?php if(isset($order['discount']) && $order['discount'] > 0): ?>
            <div class="total-row">
                <div class="total-label">Discount</div>
                <div class="total-value">-₹<?php echo number_format($order['discount'], 2); ?></div>
            </div>
            <?php endif; ?>
            <div class="total-row grand-total">
                <div class="total-label">Total</div>
                <div class="total-value">₹<?php echo number_format($order['total_amount'], 2); ?></div>
            </div>
        </div>
        
        <div class="order-actions">
            <a href="view_invoice.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="btn-action btn-primary">
                <i class="fas fa-file-invoice"></i> View Invoice
            </a>
            <?php if ($order['status'] === 'pending' || $order['status'] === 'processing'): ?>
            <a href="#" class="btn-action btn-secondary" id="cancelOrderBtn">
                <i class="fas fa-times-circle"></i> Cancel Order
            </a>
            <?php endif; ?>
            <a href="contact.php?subject=Order%20Inquiry%20-%20<?php echo urlencode($order['order_id']); ?>" class="btn-action btn-secondary">
                <i class="fas fa-question-circle"></i> Help with Order
            </a>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include('../includes/footer.php'); ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Header scroll effect
            const header = document.querySelector('.main-header');
            
            window.addEventListener('scroll', function() {
                if (window.scrollY > 100) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });
            
            // Mobile menu toggle
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const mainNav = document.querySelector('.main-nav');
            
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    mainNav.classList.toggle('active');
                    
                    // Change icon based on menu state
                    const icon = this.querySelector('i');
                    if (mainNav.classList.contains('active')) {
                        icon.classList.remove('fa-bars');
                        icon.classList.add('fa-times');
                    } else {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                });
            }
            
            // Close menu when clicking outside
            document.addEventListener('click', function(event) {
                if (!event.target.closest('.nav-container') && mainNav.classList.contains('active')) {
                    mainNav.classList.remove('active');
                    const icon = mobileMenuBtn.querySelector('i');
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                }
            });
            
            // Set progress track dynamic progress width
            const progressTrack = document.querySelector('.progress-track');
            if (progressTrack) {
                progressTrack.style.setProperty('--progress-width', progressTrack.getAttribute('style').replace('--progress-width: ', ''));
                
                // Apply progress
                setTimeout(() => {
                    progressTrack.style.setProperty('width', progressTrack.getAttribute('style').replace('--progress-width: ', ''));
                }, 300);
            }
            
            // Button effects
            const actionButtons = document.querySelectorAll('.btn-action');
            actionButtons.forEach(btn => {
                btn.addEventListener('mousedown', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                btn.addEventListener('mouseup', function() {
                    this.style.transform = 'scale(1)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });
            
            // Cancel order functionality
            const cancelOrderBtn = document.getElementById('cancelOrderBtn');
            if (cancelOrderBtn) {
                cancelOrderBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
                        // Implement cancel order function here
                        // For now just show an alert
                        alert('This functionality is not implemented yet. Please contact customer support to cancel your order.');
                    }
                });
            }
        });
    </script>
</body>
</html> 