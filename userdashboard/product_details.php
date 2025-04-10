<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.html");
    exit();
}

// Include database connection
include '../database/connect.php';

// Check if product ID is provided
if (!isset($_GET['id'])) {
    header("Location: userdashboard.php?message=Invalid product ID&type=error");
    exit();
}

$productId = $_GET['id'];

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

// Fetch user data for profile image
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

// Fetch product details
try {
    $sql = "SELECT p.*, c.category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        header("Location: userdashboard.php?message=Product not found&type=error");
        exit();
    }
    
    $product = $result->fetch_assoc();
    
    // Check if product is in cart
    $cartSql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
    $cartStmt = $conn->prepare($cartSql);
    $cartStmt->bind_param("ii", $_SESSION['user_id'], $productId);
    $cartStmt->execute();
    $inCart = $cartStmt->get_result()->num_rows > 0;
    
    // Fetch related products (same category)
    $relatedSql = "SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 4";
    $relatedStmt = $conn->prepare($relatedSql);
    $relatedStmt->bind_param("ii", $product['category_id'], $productId);
    $relatedStmt->execute();
    $relatedProducts = $relatedStmt->get_result();
    
} catch (Exception $e) {
    error_log("Error fetching product details: " . $e->getMessage());
    header("Location: userdashboard.php?message=Error loading product&type=error");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Solestreet</title>
    <link rel="stylesheet" href="userdashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-detail-container {
            max-width: 1200px;
            margin: 40px auto;
            display: flex;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .product-detail-image {
            flex: 1;
            padding: 30px;
        }
        
        .product-detail-image img {
            width: 100%;
            height: auto;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .product-detail-info {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
        }
        
        .product-detail-name {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #333;
        }
        
        .product-detail-price {
            font-size: 28px;
            font-weight: 600;
            color: var(--price-color);
            margin-bottom: 20px;
        }
        
        .product-detail-description {
            margin-bottom: 30px;
            line-height: 1.7;
            color: #666;
            font-size: 16px;
        }
        
        .product-detail-sizes {
            margin-bottom: 30px;
        }
        
        .product-detail-sizes h3 {
            margin-bottom: 15px;
            font-size: 18px;
            font-weight: 600;
        }
        
        .already-in-cart {
            background: linear-gradient(145deg, #9e9e9e, #757575) !important;
            cursor: not-allowed !important;
            opacity: 0.8;
        }
        
        .already-in-cart:hover {
            transform: none !important;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2) !important;
        }
        
        @media (max-width: 768px) {
            .product-detail-container {
                flex-direction: column;
                margin: 20px;
            }
            
            .product-detail-image, .product-detail-info {
                padding: 20px;
            }
        }
        
        /* Related products styles */
        .related-products {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .related-products h2 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            position: relative;
            padding-bottom: 10px;
        }
        
        .related-products h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: #3d5afe;
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .related-product {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .related-product:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .related-product img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .related-product-info {
            padding: 15px;
        }
        
        .
        
        
        .related-product-price {
            font-size: 18px;
            font-weight: 600;
            color: #1a237e;
            margin-bottom: 12px;
        }
        
        .related-product-btn {
            display: inline-block;
            padding: 8px 15px;
            background: #f5f5f5;
            color: #333;
            border-radius: 20px;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        .related-product-btn:hover {
            background: #e0e0e0;
        }
        
        .category-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #f0f4ff;
            color: #3d5afe;
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 15px;
        }
        
        @media (max-width: 768px) {
            .related-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
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
            margin-right: auto;
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
            font-size: 18px;
            color: #555;
            text-decoration: none;
            margin-right: 15px;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -10px;
            background-color: #e74c3c;
            color: white;
            font-size: 11px;
            font-weight: 600;
            width: 18px;
            height: 18px;
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
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
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

        /* Size display styles */
        .product-size-container {
            margin: 15px 0;
            padding: 10px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }

        .product-size-container h4 {
            font-size: 16px;
            margin-bottom: 10px;
            color: #333;
        }

        .available-sizes {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .size-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 40px;
            height: 40px;
            border-radius: 4px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            font-size: 14px;
            color: #333;
            padding: 0 10px;
        }

        .no-sizes {
            color: #dc3545;
            font-style: italic;
        }

        /* Size Selection Styles */
        .size-selection {
            margin: 20px 0;
        }

        .size-selection h4 {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .size-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .size-option {
            position: relative;
        }

        .size-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .size-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 45px;
            height: 45px;
            border: 1px solid #ddd;
            border-radius: 50%;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }

        .size-option input[type="radio"]:checked + label {
            background-color: #333;
            color: #fff;
            border-color: #333;
        }

        .error-message {
            color: #dc3545;
            font-size: 13px;
            margin-top: 8px;
            display: none;
        }

        .highlight-error {
            animation: shake 0.5s;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }

        /* Out of Stock styles */
        .out-of-stock {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
        }

        .out-of-stock-badge {
            display: inline-block;
            padding: 6px 12px;
            background-color: #dc3545;
            color: white;
            font-weight: 600;
            font-size: 14px;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .out-of-stock-message {
            color: #6c757d;
            margin: 0;
        }
    </style>
</head>
<body>
    <!-- Professional Header -->
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <div class="brand">
                    <h1>Solestreet</h1>
                </div>
                
                <div class="nav-container">
                    <nav class="main-nav">
                        <ul>
                            <li><a href="userdashboard.php">Home</a></li>
                            <li><a href="products.php" class="active">Products</a></li>
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

    <!-- Product Details -->
    <div class="product-detail-container">
        <div class="product-detail-image">
            <img src="../uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <div class="product-detail-info">
            <?php if (!empty($product['category_name'])): ?>
            <div class="category-badge"><?php echo htmlspecialchars($product['category_name']); ?></div>
            <?php endif; ?>

            <h1 class="product-detail-name"><?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="product-detail-price">₹<?php echo number_format($product['price'], 2); ?></div>

            <!-- Description -->
            <div class="product-detail-description">
                <?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?>
            </div>

            <!-- Add to Cart -->
            <?php if ($inCart): ?>
                <button class="add-to-cart already-in-cart" disabled>
                    <i class="fas fa-check"></i> In Cart
                </button>
            <?php else: ?>
                <?php
                // Check if there are available sizes
                $sizeQuery = "SELECT * FROM product_sizes WHERE product_id = ? AND status = 'available' AND stock_quantity > 0";
                $sizeStmt = $conn->prepare($sizeQuery);
                $sizeStmt->bind_param("i", $productId);
                $sizeStmt->execute();
                $sizeResult = $sizeStmt->get_result();
                $hasSizes = $sizeResult->num_rows > 0;
                
                if (!$hasSizes): 
                ?>
                    <div class="out-of-stock">
                        <span class="out-of-stock-badge">Out of Stock</span>
                        <p class="out-of-stock-message">This product is currently out of stock in all sizes.</p>
                    </div>
                <?php else: ?>
                <form action="add_to_cart.php" method="post" id="add-to-cart-form">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <!-- Size Selection -->
                    <div class="size-selection">
                        <h4>Select Size:</h4>
                        <div class="size-options">
                            <?php
                            while ($size = $sizeResult->fetch_assoc()) {
                                $sizeValue = htmlspecialchars($size['size']);
                                echo '<div class="size-option">';
                                echo '<input type="radio" id="size_' . $sizeValue . '" name="selected_size" value="' . $sizeValue . '" required>';
                                echo '<label for="size_' . $sizeValue . '">' . $sizeValue . '</label>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <div id="size-error" class="error-message">Please select a size before adding to cart.</div>
                    </div>
                    
                    <div class="quantity-selector">
                        <label for="quantity">Quantity:</label>
                        <select name="quantity" id="quantity">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <button type="submit" class="add-to-cart-btn">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Related Products -->
    <?php if ($relatedProducts->num_rows > 0): ?>
    <div class="related-products">
        <h2>Related Products</h2>
        <div class="related-grid">
            <?php while ($related = $relatedProducts->fetch_assoc()): ?>
            <a href="product_details.php?id=<?php echo $related['id']; ?>" class="related-product">
                <img src="../uploads/products/<?php echo htmlspecialchars($related['image_path']); ?>" 
                     alt="<?php echo htmlspecialchars($related['name']); ?>">
                <div class="related-product-info">
                    <h3 class="related-product-title"><?php echo htmlspecialchars($related['name']); ?></h3>
                    <div class="related-product-price">₹<?php echo number_format($related['price'], 2); ?></div>
                    <div class="related-product-btn">View Details</div>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

    
    <script>
    // JavaScript to validate size selection
    document.addEventListener('DOMContentLoaded', function() {
        const addToCartForm = document.getElementById('add-to-cart-form');
        const sizeError = document.getElementById('size-error');
        
        if (addToCartForm) {
            addToCartForm.addEventListener('submit', function(e) {
                // Check if a size is selected
                const selectedSize = document.querySelector('input[name="selected_size"]:checked');
                
                if (!selectedSize) {
                    e.preventDefault(); // Prevent form submission
                    if (sizeError) {
                        sizeError.style.display = 'block';
                    }
                    
                    const sizeOptions = document.querySelector('.size-options');
                    if (sizeOptions) {
                        sizeOptions.classList.add('highlight-error');
                        
                        setTimeout(function() {
                            sizeOptions.classList.remove('highlight-error');
                        }, 1000);
                        
                        // Scroll to size selection
                        document.querySelector('.size-selection').scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                }
            });
            
            // Hide error when a size is selected
            const sizeInputs = document.querySelectorAll('input[name="selected_size"]');
            sizeInputs.forEach(function(input) {
                input.addEventListener('change', function() {
                    if (sizeError) {
                        sizeError.style.display = 'none';
                    }
                });
            });
        }
    });
    </script>
</body>
</html>