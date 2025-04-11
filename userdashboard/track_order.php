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
    
    // Verify the order is in shipped or processing status
    if ($order['status'] !== 'shipped' && $order['status'] !== 'processing') {
        header("Location: view_order_details.php?order_id=" . urlencode($orderId) . "&message=Order is not yet shipped&type=info");
        exit();
    }
    
} catch (Exception $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    header("Location: my_orders.php?message=Error retrieving order details&type=error");
    exit();
}

// For demonstration, we'll simulate tracking data
// In a real application, this would come from an API call to a shipping provider
$trackingData = [
    'carrier' => 'Express Shipping',
    'tracking_number' => 'EXP' . strtoupper(substr($orderId, -6)),
    'status' => $order['status'],
    'estimated_delivery' => date('Y-m-d', strtotime('+5 days', strtotime($order['order_date']))),
    'updates' => [
        [
            'date' => date('Y-m-d H:i:s', strtotime($order['order_date'])),
            'status' => 'Order Placed',
            'location' => 'Online',
            'description' => 'Your order has been placed successfully.'
        ],
        [
            'date' => date('Y-m-d H:i:s', strtotime('+1 day', strtotime($order['order_date']))),
            'status' => 'Order Processed',
            'location' => 'Fulfillment Center',
            'description' => 'Your order has been processed and is being prepared for shipping.'
        ]
    ]
];

// Add shipping update if order is shipped
if ($order['status'] === 'shipped') {
    $trackingData['updates'][] = [
        'date' => date('Y-m-d H:i:s', strtotime('+2 days', strtotime($order['order_date']))),
        'status' => 'Shipped',
        'location' => 'Distribution Center',
        'description' => 'Your order has been shipped and is on its way to you.'
    ];
    
    // Add in-transit update (example)
    $trackingData['updates'][] = [
        'date' => date('Y-m-d H:i:s', strtotime('+3 days', strtotime($order['order_date']))),
        'status' => 'In Transit',
        'location' => 'Regional Hub',
        'description' => 'Your package is in transit to your delivery address.'
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Order - Solestreet</title>
    <link rel="stylesheet" href="userdashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tracking-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .tracking-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
            flex-wrap: wrap;
        }

        .tracking-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            position: relative;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .tracking-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--primary);
            transition: width 0.3s ease;
        }

        .tracking-info {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
        }

        .tracking-summary {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
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

        .tracking-number {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            letter-spacing: 1px;
        }

        .tracking-map {
            height: 300px;
            background: #f5f5f5;
            border-radius: 10px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }

        .tracking-timeline {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
        }

        .timeline-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 8px;
            width: 2px;
            background: #e0e0e0;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 25px;
        }

        .timeline-item:last-child {
            padding-bottom: 0;
        }

        .timeline-marker {
            position: absolute;
            top: 0;
            left: -30px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: white;
            border: 2px solid var(--primary);
            z-index: 1;
        }

        .timeline-item.active .timeline-marker {
            background: var(--primary);
            box-shadow: 0 0 0 4px rgba(61, 90, 254, 0.2);
        }

        .timeline-date {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .timeline-status {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .timeline-location {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .timeline-description {
            font-size: 14px;
            color: #333;
            line-height: 1.5;
        }

        .tracking-help {
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            padding: 20px;
            margin-bottom: 30px;
        }

        .help-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .help-text {
            font-size: 14px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .tracking-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
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
            .tracking-summary {
                grid-template-columns: 1fr;
            }
            
            .tracking-map {
                height: 200px;
            }
            
            .tracking-actions {
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
    <div class="tracking-container">
        <div class="tracking-header">
            <h1 class="tracking-title">Track Your Order</h1>
            <a href="view_order_details.php?order_id=<?php echo urlencode($orderId); ?>" class="btn-action btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Order Details
            </a>
        </div>
        
        <div class="tracking-info">
            <div class="tracking-summary">
                <div class="summary-item">
                    <div class="summary-label">Order ID</div>
                    <div class="summary-value">#<?php echo htmlspecialchars($order['order_id']); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Shipping Carrier</div>
                    <div class="summary-value"><?php echo htmlspecialchars($trackingData['carrier']); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Tracking Number</div>
                    <div class="summary-value tracking-number"><?php echo htmlspecialchars($trackingData['tracking_number']); ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-label">Estimated Delivery</div>
                    <div class="summary-value"><?php echo date('F j, Y', strtotime($trackingData['estimated_delivery'])); ?></div>
                </div>
            </div>
            
            <div class="tracking-map">
                <div>
                    <i class="fas fa-map-marker-alt" style="font-size: 24px; margin-bottom: 10px;"></i>
                    <p>Map view is not available in this demo</p>
                </div>
            </div>
        </div>
        
        <div class="tracking-timeline">
            <div class="timeline-title">Shipment Progress</div>
            <div class="timeline">
                <?php foreach($trackingData['updates'] as $index => $update): 
                    $isActive = ($index === count($trackingData['updates']) - 1);
                ?>
                <div class="timeline-item <?php echo $isActive ? 'active' : ''; ?>">
                    <div class="timeline-marker"></div>
                    <div class="timeline-date"><?php echo date('F j, Y - g:i A', strtotime($update['date'])); ?></div>
                    <div class="timeline-status"><?php echo htmlspecialchars($update['status']); ?></div>
                    <div class="timeline-location"><?php echo htmlspecialchars($update['location']); ?></div>
                    <div class="timeline-description"><?php echo htmlspecialchars($update['description']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="tracking-help">
            <div class="help-title">Need Help?</div>
            <div class="help-text">
                If you have any questions about your shipment or need assistance with tracking, please don't hesitate to contact our customer support team.
            </div>
            <div class="tracking-actions">
                <a href="contact.php?subject=Tracking%20Inquiry%20-%20<?php echo urlencode($order['order_id']); ?>" class="btn-action btn-primary">
                    <i class="fas fa-headset"></i> Contact Support
                </a>
                <a href="view_order_details.php?order_id=<?php echo urlencode($order['order_id']); ?>" class="btn-action btn-secondary">
                    <i class="fas fa-info-circle"></i> Order Details
                </a>
            </div>
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
            
            // Timeline animation
            const timelineItems = document.querySelectorAll('.timeline-item');
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.opacity = 1;
                            entry.target.style.transform = 'translateY(0)';
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1 });
                
                timelineItems.forEach((item, index) => {
                    item.style.opacity = 0;
                    item.style.transform = 'translateY(20px)';
                    item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    item.style.transitionDelay = (index * 0.2) + 's';
                    observer.observe(item);
                });
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
        });
    </script>
</body>
</html> 