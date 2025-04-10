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

// Razorpay configuration
$razorpayKeyId = 'rzp_test_nrbyP3WJjvrdyI';

// Initialize message variables
$message = '';
$messageType = '';

// Fetch user data to get profile image
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

// Fetch cart items
try {
    $userId = $_SESSION['user_id'];
    
    // Fetch cart items with product details
    $sql = "SELECT c.cart_id, c.quantity, p.id as product_id, p.name, p.price, p.image_path 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cartItems = [];
    $subtotal = 0;
    
    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
        $subtotal += $row['price'] * $row['quantity'];
    }
    
    // Calculate totals
    $shipping = $subtotal > 0 ? 100 : 0;
    $total = $subtotal + $shipping;
    
    // If cart is empty, redirect to cart page
    if (count($cartItems) == 0) {
        header("Location: cart.php?message=Your cart is empty&type=error");
        exit();
    }
    
} catch (Exception $e) {
    error_log("Database error in checkout: " . $e->getMessage());
    header("Location: cart.php?message=Error loading checkout. Please try again.&type=error");
    exit();
}

// Generate a unique order ID
$orderID = "ORD" . time() . rand(100, 999);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Solestreet</title>
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
            max-width: 1200px;
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
        
        .checkout-container {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        @media (min-width: 768px) {
            .checkout-container {
                flex-direction: row;
            }
            
            .checkout-form {
                flex: 3;
            }
            
            .order-summary {
                flex: 2;
            }
        }
        
        .checkout-form, .order-summary {
            background-color: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .section-title {
            margin-bottom: 20px;
            color: #333;
            font-size: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .payment-methods {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .payment-method.selected {
            border-color: #4CAF50;
            background-color: rgba(76, 175, 80, 0.1);
        }
        
        .payment-name {
            font-weight: 500;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        
        .item-details {
            display: flex;
            gap: 15px;
        }
        
        .item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .item-name {
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .item-quantity {
            color: #777;
            font-size: 14px;
        }
        
        .order-totals {
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .total-row.final {
            font-weight: bold;
            font-size: 18px;
            border-bottom: none;
            padding-top: 15px;
        }
        
        .place-order-btn {
            width: 100%;
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 15px;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .place-order-btn:hover {
            background-color: #45a049;
        }
        
        .message {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 4px;
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
            z-index: 1000;
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        .message.success {
            background-color: #4CAF50;
        }
        
        .message.error {
            background-color: #f44336;
        }
        
        .message.info {
            background-color: #2196F3;
        }
        
        .message.warning {
            background-color: #ff9800;
        }
        
        .close-btn {
            margin-left: 10px;
            cursor: pointer;
            font-weight: bold;
        }
        
        /* Add profile section styles */
        .profile-section {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            background-color: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .profile-image {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #f0f0f0;
        }
        
        .profile-details {
            display: flex;
            flex-direction: column;
        }
        
        .profile-name {
            font-weight: bold;
            font-size: 16px;
            color: #333;
        }
        
        .profile-email {
            font-size: 14px;
            color: #666;
        }
    </style>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
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
                     alt="Profile"
                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            </a>
            <p><?php echo $user ? htmlspecialchars($user['name']) : 'User'; ?></p>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>
    
    <?php if ($message): ?>
    <div class="message <?php echo $messageType; ?>">
        <i class="fas fa-info-circle"></i>
        <span><?php echo $message; ?></span>
        <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
    </div>
    <?php endif; ?>
    
    <div class="container">
        <!-- Add profile section at the top of the container -->
        <div class="profile-section">
            <img src="<?php echo isset($user['profile_image']) && !empty($user['profile_image']) ? '../uploads/profile_images/' . htmlspecialchars($user['profile_image']) : 'https://via.placeholder.com/100'; ?>" 
                 alt="Profile" 
                 class="profile-image">
            <div class="profile-details">
                <span class="profile-name"><?php echo $user ? htmlspecialchars($user['name']) : 'User'; ?></span>
                <span class="profile-email"><?php echo $user ? htmlspecialchars($user['email']) : 'user@example.com'; ?></span>
            </div>
        </div>
        
        <div class="checkout-container">
            <div class="checkout-form">
                <h2 class="section-title">Shipping Information</h2>
                
                <form id="checkout-form" action="process_order.php" method="post">
                    <input type="hidden" name="order_id" value="<?php echo $orderID; ?>">
                    <input type="hidden" name="total_amount" value="<?php echo $total; ?>">
                    <input type="hidden" name="payment_method" id="payment_method" value="cod">
                    <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id" value="">
                    
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" required value="<?php echo isset($user['name']) ? htmlspecialchars($user['name']) : ''; ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required value="<?php echo isset($user['email']) ? htmlspecialchars($user['email']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3" required></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="zip">Postal Code</label>
                            <input type="text" id="zip" name="zip" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="state">State</label>
                        <select id="state" name="state" required>
                            <option value="">Select State</option>
                            <option value="Andhra Pradesh">Andhra Pradesh</option>
                            <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                            <option value="Assam">Assam</option>
                            <option value="Bihar">Bihar</option>
                            <option value="Chhattisgarh">Chhattisgarh</option>
                            <option value="Goa">Goa</option>
                            <option value="Gujarat">Gujarat</option>
                            <option value="Haryana">Haryana</option>
                            <option value="Himachal Pradesh">Himachal Pradesh</option>
                            <option value="Jharkhand">Jharkhand</option>
                            <option value="Karnataka">Karnataka</option>
                            <option value="Kerala">Kerala</option>
                            <option value="Madhya Pradesh">Madhya Pradesh</option>
                            <option value="Maharashtra">Maharashtra</option>
                            <option value="Manipur">Manipur</option>
                            <option value="Meghalaya">Meghalaya</option>
                            <option value="Mizoram">Mizoram</option>
                            <option value="Nagaland">Nagaland</option>
                            <option value="Odisha">Odisha</option>
                            <option value="Punjab">Punjab</option>
                            <option value="Rajasthan">Rajasthan</option>
                            <option value="Sikkim">Sikkim</option>
                            <option value="Tamil Nadu">Tamil Nadu</option>
                            <option value="Telangana">Telangana</option>
                            <option value="Tripura">Tripura</option>
                            <option value="Uttar Pradesh">Uttar Pradesh</option>
                            <option value="Uttarakhand">Uttarakhand</option>
                            <option value="West Bengal">West Bengal</option>
                            <option value="Delhi">Delhi</option>
                        </select>
                    </div>
                    
                    <h2 class="section-title" style="margin-top: 30px;">Payment Method</h2>
                    
                    <div class="payment-methods">
                        <div class="payment-method selected" data-method="cod">
                            <i class="fas fa-money-bill-wave"></i>
                            <div class="payment-name">Cash On Delivery</div>
                        </div>
                        <div class="payment-method" data-method="razorpay">
                            <i class="fas fa-credit-card"></i>
                            <div class="payment-name">Online Payment</div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="order-summary">
                <h2 class="section-title">Order Summary</h2>
                
                <div class="order-items">
                    <?php foreach ($cartItems as $item): ?>
                    <div class="order-item">
                        <div class="item-details">
                            <img class="item-image" src="../uploads/products/<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <div>
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                            </div>
                        </div>
                        <div class="item-price">₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-totals">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Shipping</span>
                        <span>₹<?php echo number_format($shipping, 2); ?></span>
                    </div>
                    <div class="total-row final">
                        <span>Total</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
                
                <button id="place-order-btn" class="place-order-btn">
                    <i class="fas fa-lock"></i> Place Order Securely
                </button>
            </div>
        </div>
    </div>

    <script>
        // Function to show error message
        function showErrorMessage(message) {
            // Create error message element if it doesn't exist
            let errorElement = document.querySelector('.message.error');
            if (!errorElement) {
                errorElement = document.createElement('div');
                errorElement.className = 'message error';
                
                const icon = document.createElement('i');
                icon.className = 'fas fa-exclamation-circle';
                errorElement.appendChild(icon);
                
                const messageText = document.createElement('span');
                errorElement.appendChild(messageText);
                
                const closeBtn = document.createElement('span');
                closeBtn.className = 'close-btn';
                closeBtn.innerHTML = '&times;';
                closeBtn.onclick = function() { this.parentElement.style.display = 'none'; };
                errorElement.appendChild(closeBtn);
                
                document.body.appendChild(errorElement);
            }
            
            // Update message text
            errorElement.querySelector('span:not(.close-btn)').textContent = message;
            
            // Display message
            errorElement.style.display = 'flex';
            
            // Auto hide after 5 seconds
            setTimeout(() => {
                if (errorElement.parentElement) {
                    errorElement.style.display = 'none';
                }
            }, 5000);
        }
    
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // Remove selected class from all methods
                document.querySelectorAll('.payment-method').forEach(m => {
                    m.classList.remove('selected');
                });
                
                // Add selected class to clicked method
                this.classList.add('selected');
                
                // Update hidden input with selected payment method
                document.getElementById('payment_method').value = this.dataset.method;
                console.log("Payment method changed to:", this.dataset.method);
            });
        });
        
        // Place order button handler
        document.getElementById('place-order-btn').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Basic form validation
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const phone = document.getElementById('phone').value.trim();
            const address = document.getElementById('address').value.trim();
            const city = document.getElementById('city').value.trim();
            const zip = document.getElementById('zip').value.trim();
            const state = document.getElementById('state').value.trim();
            
            if (!name || !email || !phone || !address || !city || !zip || !state) {
                showErrorMessage('Please fill in all required fields');
                return;
            }
            
            const form = document.getElementById('checkout-form');
            const paymentMethod = document.getElementById('payment_method').value;
            
            if (paymentMethod === 'razorpay') {
                // Razorpay payment handling
                var options = {
                    key: "<?php echo $razorpayKeyId; ?>",
                    amount: <?php echo $total * 100; ?>,
                    currency: "INR",
                    name: "Solestreet",
                    description: "Order payment",
                    image: "https://via.placeholder.com/150",
                    prefill: {
                        name: document.getElementById('name').value,
                        email: document.getElementById('email').value,
                        contact: document.getElementById('phone').value
                    },
                    handler: function (response) {
                        // Set the payment ID in the form
                        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                        // Submit the form
                        form.submit();
                    },
                    "theme": {
                        "color": "#3399cc"
                    }
                };
                
                var rzp1 = new Razorpay(options);
                rzp1.on('payment.failed', function (response){
                    showErrorMessage('Payment failed: ' + response.error.description);
                    console.log(response.error);
                });
                
                rzp1.open();
            } else {
                // Cash on delivery - just submit the form
                form.submit();
            }
        });
        
        // Auto-hide message after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const message = document.querySelector('.message');
            if(message) {
                setTimeout(() => {
                    message.style.display = 'none';
                }, 5000);
            }
        });
    </script>
</body>
</html>