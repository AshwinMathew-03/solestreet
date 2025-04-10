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

// Handle incoming messages from redirects
if(isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = isset($_GET['type']) ? $_GET['type'] : 'info';
} else if(isset($_GET['exists'])) {
    $message = "This product is already in your cart!";
    $messageType = "warning";
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

// Product search and filter query
try {
    // Start building the query
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];
    $types = "";
    
    // Search functionality - search in name, description, and category
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $searchTerm = "%" . $_GET['search'] . "%";
        $sql .= " AND (name LIKE ? OR description LIKE ? OR category LIKE ?)";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $types .= "sss";
        
        // Debug message
        error_log("Search query: " . $_GET['search']);
    }
    
    // Category filter
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $sql .= " AND category = ?";
        $params[] = $_GET['category'];
        $types .= "s";
    }
    
    // Brand filter - assuming brand is the first word in product name
    if (isset($_GET['brand']) && !empty($_GET['brand'])) {
        $sql .= " AND name LIKE ?";
        $params[] = $_GET['brand'] . "%";
        $types .= "s";
    }
    
    // Price range filter
    if (isset($_GET['min_price']) && !empty($_GET['min_price']) && is_numeric($_GET['min_price'])) {
        $sql .= " AND price >= ?";
        $params[] = floatval($_GET['min_price']);
        $types .= "d";
    }
    
    if (isset($_GET['max_price']) && !empty($_GET['max_price']) && is_numeric($_GET['max_price'])) {
        $sql .= " AND price <= ?";
        $params[] = floatval($_GET['max_price']);
        $types .= "d";
    }
    
    // Size filter - assuming sizes are stored as JSON in the database
    if (isset($_GET['size']) && !empty($_GET['size'])) {
        $sql .= " AND sizes LIKE ?";
        $params[] = '%"' . $_GET['size'] . '"%';
        $types .= "s";
    }
    
    // Add ordering
    $sql .= " ORDER BY id DESC";
    
    // Debug SQL query
    error_log("Final SQL: " . $sql);
    
    // Prepare and execute statement
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        // Bind parameters using splat operator
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    // Log how many results were found
    error_log("Query returned " . $result->num_rows . " results");
    
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $message = "An error occurred while searching for products";
    $messageType = "error";
    
    // Create an empty result set if there's an error
    $result = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solestreet</title>
    <link rel="stylesheet" href="userdashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- Add Font Awesome for cart icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3d5afe;       /* Vibrant blue */
            --hover: #536dfe;         /* Lighter blue for hover */
            --dark: #283593;          /* Darker blue */
            --white: #ffffff;
            --price-color: #1a237e;   /* Deep blue for price */
        }

        /* Product footer and button styles */
        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding: 10px 0;
        }

        .price {
            font-size: 22px;
            font-weight: 600;
            color: var(--price-color);
        }

        .add-to-cart {
            display: flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(145deg, var(--primary), var(--dark));
            color: var(--white);
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(61, 90, 254, 0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-decoration: none; /* Remove underline from link */
        }

        .add-to-cart:hover {
            background: linear-gradient(145deg, var(--hover), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(61, 90, 254, 0.3);
            color: var(--white); /* Maintain white text on hover */
        }

        .add-to-cart:active {
            transform: translateY(0);
            box-shadow: 0 2px 5px rgba(61, 90, 254, 0.2);
        }

        .add-to-cart i {
            font-size: 16px;
        }

        /* Product card enhancements */
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin: 15px;
            transition: transform 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-info {
            padding: 15px 0;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .add-to-cart {
                padding: 8px 15px;
                font-size: 13px;
            }

            .price {
                font-size: 20px;
            }
        }

        .no-products-message {
            grid-column: 1 / -1;
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            color: #6c757d;
            font-size: 16px;
            margin: 20px 0;
        }

        /* Add this to your existing styles */
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

        /* Add these to your existing styles */
        .already-in-cart {
            background: linear-gradient(145deg, #9e9e9e, #757575) !important;
            cursor: not-allowed !important;
            opacity: 0.8;
        }

        .already-in-cart:hover {
            transform: none !important;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2) !important;
        }

        .already-in-cart i {
            color: #4CAF50;
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

        /* Search and Filter styles */
        .search-filter-section {
            max-width: 1200px;
            margin: 20px auto 40px;
            padding: 0 20px;
        }
        
        .filter-container {
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-top: 20px;
        }
        
        .search-bar {
            display: flex;
            margin-bottom: 20px;
        }
        
        .search-bar input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 25px 0 0 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .search-bar input:focus {
            border-color: var(--primary);
        }
        
        .search-btn {
            background: linear-gradient(145deg, var(--primary), var(--dark));
            color: white;
            border: none;
            padding: 0 25px;
            border-radius: 0 25px 25px 0;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .search-btn:hover {
            background: linear-gradient(145deg, var(--hover), var(--primary));
        }
        
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 25px;
            background-color: white;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            border-color: var(--primary);
        }
        
        .price-range {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .price-range input {
            flex: 1;
        }
        
        .price-range span {
            color: #666;
            font-size: 14px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
            min-width: 200px;
        }
        
        .filter-btn {
            flex: 1;
            background: linear-gradient(145deg, var(--primary), var(--dark));
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .filter-btn:hover {
            background: linear-gradient(145deg, var(--hover), var(--primary));
            transform: translateY(-2px);
        }
        
        .reset-btn {
            flex: 1;
            background: #f5f5f5;
            color: #333;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .reset-btn:hover {
            background: #e0e0e0;
        }
        
        /* Responsive styles */
        @media screen and (max-width: 768px) {
            .filters {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
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
    </style>
</head>
<body>
    <?php if($message): ?>
    <div class="message <?php echo htmlspecialchars($messageType); ?>">
        <?php echo htmlspecialchars($message); ?>
        <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
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
                            <li><a href="userdashboard.php" class="active">Home</a></li>
                            <li><a href="products.php">Products</a></li>
                            <li><a href="contact.php">Contact</a></li>
                            <li><a href="about.php">About</a></li>
                        </ul>
                    </nav>
                    
                    <div class="user-controls">
                        <a href="cart.php" class="cart-icon">
                            <i class="fas fa-shopping-cart"></i>
                            <?php
                            // Get cart count
                            $cartCount = 0;
                            if(isset($_SESSION['user_id'])) {
                                $cartCountQuery = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
                                $stmt = $conn->prepare($cartCountQuery);
                                $stmt->bind_param("i", $_SESSION['user_id']);
                                $stmt->execute();
                                $cartResult = $stmt->get_result();
                                if($cartRow = $cartResult->fetch_assoc()) {
                                    $cartCount = $cartRow['count'];
                                }
                            }
                            
                            if($cartCount > 0): ?>
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

    <div class="hero">
        <div class="hero-content">
            <p>Ultimate comfort sneaker</p>
            <h1>Tackle your fitness resolutions or keep up with your current routine</h1>
            <a href="#" class="discover-btn">Discover</a>
        </div>
        <div class="hero-image">
            <img src="man3.jpg" alt="White Adidas sneaker" id="hero-image">
        </div>
    </div>

    <!-- Search and Filter Section - Add this right after the hero section and before the products section -->
    <div class="search-filter-section">
        <div class="section-header">
            <h2>Find Your Perfect Pair</h2>
            <p>Search and filter our collection</p>
        </div>
        
        <form method="GET" action="userdashboard.php" id="filterForm">
            <div class="filter-container">
                <div class="search-bar">
                    <input type="text" name="search" id="searchInput" placeholder="Search products..." 
                           value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <div class="filters">
                    <div class="filter-group">
                        <label for="category">Category</label>
                        <select name="category" id="category">
                            <option value="">All Categories</option>
                            <?php
                            // Fetch categories from the database
                            $catQuery = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category";
                            $catResult = $conn->query($catQuery);
                            
                            if ($catResult && $catResult->num_rows > 0) {
                                while($cat = $catResult->fetch_assoc()) {
                                    $selected = (isset($_GET['category']) && $_GET['category'] == $cat['category']) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($cat['category']) . '" ' . $selected . '>' . 
                                         htmlspecialchars($cat['category']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="price_range">Price Range</label>
                        <div class="price-range">
                            <input type="number" name="min_price" id="min_price" placeholder="Min ₹" min="0"
                                   value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
                            <span>to</span>
                            <input type="number" name="max_price" id="max_price" placeholder="Max ₹" min="0"
                                   value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="filter-group">
                        <label for="size">Size</label>
                        <select name="size" id="size">
                            <option value="">All Sizes</option>
                            <?php
                            // Common Indian shoe sizes
                            $sizes = ['IND 6', 'IND 7', 'IND 8', 'IND 9', 'IND 10', 'IND 11', 'IND 12', 'IND 13'];
                            
                            foreach ($sizes as $size) {
                                $selected = (isset($_GET['size']) && $_GET['size'] == $size) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($size) . '" ' . $selected . '>' . 
                                     htmlspecialchars($size) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="filter-btn">Apply Filters</button>
                        <a href="userdashboard.php" class="reset-btn">Reset</a>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="products-section">
        <div class="section-header">
            <h2>Featured Products</h2>
            <p>Discover our collection</p>
        </div>

        <div class="products-grid">
            <?php
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    ?>
                    <div class="product-card">
                        <div class="product-image">
                            <a href="product_details.php?id=<?php echo $row['id']; ?>">
                                <img src="../uploads/products/<?php echo htmlspecialchars($row['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($row['name']); ?>">
                            </a>
                        </div>
                        <div class="product-info">
                            <a href="product_details.php?id=<?php echo $row['id']; ?>" style="text-decoration: none; color: inherit;">
                                <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            </a>
                            <p class="description">
                                <?php 
                                $description = isset($row['description']) ? $row['description'] : '';
                                echo htmlspecialchars(substr($description, 0, 100)) . 
                                     (strlen($description) > 100 ? '...' : ''); 
                                ?>
                            </p>
                            <div class="product-footer">
                                <div class="price">₹<?php echo number_format($row['price'], 2); ?></div>
                                <?php
                                // Check if product is already in cart
                                $checkCart = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
                                $stmt = $conn->prepare($checkCart);
                                $stmt->bind_param("ii", $_SESSION['user_id'], $row['id']);
                                $stmt->execute();
                                $cartResult = $stmt->get_result();
                                
                                if($cartResult->num_rows > 0) {
                                    // Product is already in cart
                                    echo '<button class="add-to-cart already-in-cart" disabled>
                                            <i class="fas fa-check"></i>
                                            In Cart
                                          </button>';
                                } else {
                                    // Product is not in cart
                                    echo '<a href="cart.php?product_id=' . $row['id'] . '" class="add-to-cart">
                                            <i class="fas fa-cart-plus"></i>
                                            Cart
                                          </a>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="no-products-message">No products available at the moment</div>';
            }
            ?>
        </div>
    </div>

    <style>
    .products-section {
        padding: 40px 20px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .section-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .section-header h2 {
        font-size: 32px;
        color: var(--dark);
        margin-bottom: 8px;
    }

    .section-header p {
        color: var(--dark-grey);
        font-size: 16px;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 30px;
        padding: 20px 0;
    }

    .product-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .product-card:hover {
        transform: translateY(-5px);
    }

    .product-image {
        position: relative;
        height: 200px;
        overflow: hidden;
    }

    .product-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .product-card:hover .product-image img {
        transform: scale(1.05);
    }

    .product-tag {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(0, 0, 0, 0.7);
        color: white;
        padding: 5px 10px;
        border-radius: 20px;
        font-size: 12px;
    }

    .product-info {
        padding: 20px;
    }

    .product-info h3 {
        font-size: 18px;
        color: var(--dark);
        margin-bottom: 10px;
        font-weight: 600;
    }

    .description {
        color: var(--dark-grey);
        font-size: 14px;
        line-height: 1.5;
        margin-bottom: 15px;
    }

    .no-products {
        grid-column: 1 / -1;
        text-align: center;
        padding: 40px;
        color: var(--dark-grey);
        font-size: 16px;
    }

    /* Responsive Design */
    @media screen and (max-width: 768px) {
        .products-section {
            padding: 20px 10px;
        }

        .section-header h2 {
            font-size: 24px;
        }

        .products-grid {
            gap: 20px;
        }
    }

    @media screen and (max-width: 480px) {
        .product-card {
            margin: 0 10px;
        }
    }
    </style>

    <script>
    // Function to add product to cart
    function addToCart(productId) {
        // Create fetch request to add item to cart
        fetch('add_to_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'product_id=' + productId
        })
        .then(response => {
            // Check if response is ok before parsing JSON
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            console.log('Server response:', data); // Debug log
            
            if (data.success) {
                // Show success message
                showMessage('Product added to your cart successfully!', 'success');
                
                // Update cart count
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = data.cart_count;
                    cartCount.style.display = 'flex';
                } else {
                    // If cart count doesn't exist, create it
                    const cartBtn = document.querySelector('.cart-btn');
                    const newCartCount = document.createElement('span');
                    newCartCount.className = 'cart-count';
                    newCartCount.textContent = data.cart_count;
                    cartBtn.appendChild(newCartCount);
                }
                
                // Update button to show item is in cart
                const button = event.target.closest('.add-to-cart');
                button.innerHTML = '<i class="fas fa-check"></i> In Cart';
                button.classList.add('already-in-cart');
                button.disabled = true;
            } else {
                // Show error message
                showMessage(data.message || 'Error adding product to cart', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Error adding product to cart. Please try again.', 'error');
        });
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

    document.addEventListener('DOMContentLoaded', function() {
        // Toggle advanced filters
        const filterToggleBtn = document.getElementById('filterToggleBtn');
        const advancedFilters = document.getElementById('advancedFilters');
        
        if (filterToggleBtn && advancedFilters) {
            // Check if any filters are active, show the filter section if they are
            <?php if($filterCount > 0): ?>
            advancedFilters.classList.add('show');
            filterToggleBtn.classList.add('active');
            <?php endif; ?>
            
            filterToggleBtn.addEventListener('click', function() {
                advancedFilters.classList.toggle('show');
                this.classList.toggle('active');
            });
        }
    });
    </script>
</body>
</html>