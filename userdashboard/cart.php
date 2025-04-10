<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.html");
    exit();
}

// Include database connection - FIX: Use the correct connection file
include '../database/connect.php';

// Initialize message variables
$message = '';
$messageType = '';

// Handle product addition to cart if product_id is provided in URL
if(isset($_GET['product_id'])) {
    $productId = $_GET['product_id'];
    $userId = $_SESSION['user_id'];
    
    // Check if product already exists in cart
    $checkSql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $userId, $productId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if($checkResult->num_rows > 0) {
        // Product already in cart
        header("Location: userdashboard.php?exists=1");
        exit();
    } else {
        // Add product to cart with quantity 1
        $addSql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)";
        $addStmt = $conn->prepare($addSql);
        $addStmt->bind_param("ii", $userId, $productId);
        
        if($addStmt->execute()) {
            $message = "Product added to your cart successfully!";
            $messageType = "success";
        } else {
            $message = "Failed to add product to cart. Please try again.";
            $messageType = "error";
        }
    }
}

// Handle incoming messages from redirects
if(isset($_GET['message'])) 
{
    $message = $_GET['message'];
    $messageType = isset($_GET['type']) ? $_GET['type'] : 'info';
}

// Add this near the top of the file, after the existing message handling code
if(isset($_GET['removed']) && $_GET['removed'] == '1') {
    $message = "Product has been removed from your cart";
    $messageType = "success";
}

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
    // Debug connection
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Debug user ID
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User ID not set in session");
    }
    
    // Verify table structure
    $check_table = "SHOW TABLES LIKE 'cart'";
    $table_result = $conn->query($check_table);
    if ($table_result->num_rows == 0) {
        throw new Exception("Cart table does not exist");
    }
    
    // Use a simpler query first to debug
    $sql = "SELECT c.cart_id, c.quantity, p.id as product_id, p.name, p.price, p.image_path 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    // Calculate total
    $total = 0;
    $cartItems = [];
    
    if ($result && $result->num_rows > 0) 
    {
        while($row = $result->fetch_assoc()) 
        {
            $cartItems[] = $row;
            $total += $row['price'] * $row['quantity'];
        }
    }
    
} 
catch (Exception $e) 
{
    error_log("Database error: " . $e->getMessage());
    $message = "An error occurred while fetching cart items: " . $e->getMessage();
    $messageType = "error";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Solestreet</title>
    <link rel="stylesheet" href="cart.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php if($message): ?>
    <div class="message <?php echo htmlspecialchars($messageType); ?>">
        <?php echo htmlspecialchars($message); ?>
        <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
    </div>
    <?php endif; ?>
    
    <nav>
        <div class="logo">Solestreet</div>
        <div class="nav-links">
            <a href="userdashboard.php">Home</a>
            <a href="#">Footwear</a>
            <a href="#">Shop</a>
            <a href="contact.php">Contact</a>
        </div>
        <div class="account-section">
            <img src="<?php echo isset($user['profile_image']) && !empty($user['profile_image']) ? '../uploads/profile_images/' . htmlspecialchars($user['profile_image']) : 'https://images.unsplash.com/photo-1499996860823-5214fcc65f8f?q=80&w=1966&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D'; ?>" 
                 style="border-radius: 50%;" 
                 height="40px" 
                 width="40px" 
                 alt="Profile">
            <p><?php echo isset($_SESSION['name']) ? htmlspecialchars($_SESSION['name']) : ''; ?></p>
            <a href="cart.php" class="cart-btn active">🛒</a>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

    <div class="cart-container">
        <div class="cart-header">
            <h1>Your Shopping Cart</h1>
            <a href="userdashboard.php" class="continue-shopping">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any products to your cart yet.</p>
                <a href="userdashboard.php" class="shop-now-btn">Shop Now</a>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item" data-cart-id="<?php echo $item['cart_id']; ?>">
                            <div class="item-image">
                                <img src="../uploads/products/<?php echo htmlspecialchars($item['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </div>
                            <div class="item-details">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <div class="item-price">₹<?php echo number_format($item['price'], 2); ?></div>
                            </div>
                            <div class="item-quantity">
                                <button class="quantity-btn minus" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="quantity"><?php echo $item['quantity']; ?></span>
                                <button class="quantity-btn plus" onclick="updateQuantity(<?php echo $item['cart_id']; ?>, 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <div class="item-total">
                                ₹<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                            </div>
                            <button class="remove-item" onclick="removeItem(<?php echo $item['cart_id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>₹<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>₹<?php echo number_format(($total > 0 ? 100 : 0), 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>₹<?php echo number_format(($total > 0 ? $total + 100 : 0), 2); ?></span>
                    </div>
                    <div class="checkout-buttons">
                        <button class="invoice-btn" onclick="generateInvoice()">
                            <i class="fas fa-file-invoice"></i> Generate Invoice
                        </button>
                        <button class="checkout-btn" onclick="window.location.href='checkout.php'">
                            Proceed to Checkout <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Function to update quantity
        function updateQuantity(cartId, change) {
            // Get current quantity
            const quantityElement = document.querySelector(`.cart-item[data-cart-id="${cartId}"] .quantity`);
            const currentQuantity = parseInt(quantityElement.textContent);
            
            // Check limits before making request
            if (currentQuantity + change < 1) {
                alert('Minimum quantity is 1');
                return;
            }
            
            if (currentQuantity + change > 10) {
                alert('Maximum quantity is 10');
                return;
            }
            
            fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `cart_id=${cartId}&change=${change}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Error updating cart');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating cart');
            });

        }

        // Function to remove item
        function removeItem(cartId) {
            if (confirm('Are you sure you want to remove this item from your cart?')) {
                fetch('remove_cart_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `cart_id=${cartId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Redirect with success message
                        window.location.href = 'cart.php?removed=1';
                    } else {
                        alert(data.message || 'Error removing item');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error removing item');
                });
            }
        }

        // Function to generate invoice
        function generateInvoice() {
            window.location.href = 'generate_invoice.php';
        }

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