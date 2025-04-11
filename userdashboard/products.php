<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.html");
    exit();
}

// Include database connection
include '../database/connect.php';

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

// Get featured products (most recent products)
try {
    // Get all categories for filter dropdown
    $categorySql = "SELECT * FROM categories ORDER BY category_name";
    $categoryStmt = $conn->prepare($categorySql);
    $categoryStmt->execute();
    $categories = $categoryStmt->get_result();
    
    // Handle search and filter
    $searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
    $categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
    
    // Base query
    $sql = "SELECT p.*, c.category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE 1=1";
    
    // Add search condition
    if (!empty($searchTerm)) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
    }
    
    // Add category filter
    if (!empty($categoryFilter)) {
        $sql .= " AND p.category_id = ?";
    }
    
    $sql .= " ORDER BY p.id DESC";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    if (!empty($searchTerm) && !empty($categoryFilter)) {
        $searchParam = "%$searchTerm%";
        $stmt->bind_param("ssi", $searchParam, $searchParam, $categoryFilter);
    } else if (!empty($searchTerm)) {
        $searchParam = "%$searchTerm%";
        $stmt->bind_param("ss", $searchParam, $searchParam);
    } else if (!empty($categoryFilter)) {
        $stmt->bind_param("i", $categoryFilter);
    }
    
    $stmt->execute();
    $products = $stmt->get_result();
} catch (Exception $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $products = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Featured Products - Solestreet</title>
    <link rel="stylesheet" href="userdashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3d5afe;
            --hover: #536dfe;
            --dark: #283593;
            --white: #ffffff;
            --price-color: #1a237e;
            --light-gray: #f5f5f5;
            --medium-gray: #e0e0e0;
            --dark-gray: #757575;
        }
        
        /* Enhanced Header Styles */
        .main-header {
            background-color: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            padding: 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .main-header.scrolled {
            padding: 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        /* Top bar styles */
        .top-bar {
            background: linear-gradient(to right, #1a237e, #3d5afe);
            padding: 8px 0;
            color: white;
            transition: all 0.3s ease;
        }
        
        .main-header.scrolled .top-bar {
            height: 0;
            padding: 0;
            overflow: hidden;
        }

        .top-bar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            font-size: 13px;
        }

        .top-contact {
            display: flex;
            align-items: center;
        }

        .top-contact a {
            color: white;
            text-decoration: none;
            margin-right: 20px;
            display: flex;
            align-items: center;
            transition: opacity 0.2s;
        }
        
        .top-contact a:hover {
            opacity: 0.85;
        }

        .top-contact a i {
            margin-right: 6px;
            font-size: 14px;
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
            padding: 15px 0;
            transition: all 0.3s ease;
        }
        
        .main-header.scrolled .header-content {
            padding: 12px 0;
        }

        .brand {
            margin-right: auto;
            position: relative;
        }

        .brand h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin: 0;
            letter-spacing: -0.5px;
            position: relative;
            transition: all 0.3s ease;
        }
        
        .main-header.scrolled .brand h1 {
            font-size: 24px;
        }
        
        .brand h1 span {
            color: var(--primary);
            position: relative;
        }
        
        .brand h1:after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 30px;
            height: 2px;
            background: var(--primary);
            transition: width 0.3s ease;
        }
        
        .brand:hover h1:after {
            width: 50px;
        }
        
        .nav-container {
            display: flex;
            align-items: center;
        }

        /* Header social links */
        .top-social-links {
            display: flex;
            align-items: center;
            margin-right: 25px;
        }
        
        .top-social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--light-gray);
            color: #555;
            margin-left: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .top-social-links a:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(61, 90, 254, 0.3);
        }
        
        .main-nav {
            margin: 0 20px;
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
            position: relative;
        }

        .main-nav a {
            display: block;
            padding: 10px 15px;
            color: #444;
            text-decoration: none;
            font-weight: 500;
            font-size: 15px;
            transition: all 0.3s;
            position: relative;
        }

        .main-nav a:after {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--primary);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }

        .main-nav a:hover {
            color: var(--primary);
        }
        
        .main-nav a:hover:after,
        .main-nav a.active:after {
            width: 25px;
        }
        
        .main-nav a.active {
            color: var(--primary);
            font-weight: 600;
        }

        .user-controls {
            display: flex;
            align-items: center;
            margin-left: auto;
        }

        .cart-icon {
            position: relative;
            font-size: 18px;
            color: #555;
            text-decoration: none;
            margin-right: 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .cart-icon:hover {
            color: var(--primary);
            background-color: rgba(61, 90, 254, 0.1);
        }

        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: transform 0.2s ease;
        }
        
        .cart-icon:hover .cart-count {
            transform: scale(1.1);
        }

        .user-dropdown {
            position: relative;
        }

        .user-toggle {
            display: flex;
            align-items: center;
            padding: 8px 15px;
            border-radius: 50px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            color: #444;
            background: var(--light-gray);
            transition: all 0.3s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .user-toggle:hover {
            background: rgba(61, 90, 254, 0.1);
            color: var(--primary);
            box-shadow: 0 3px 8px rgba(0,0,0,0.08);
        }

        .user-toggle i {
            margin-right: 8px;
            font-size: 16px;
        }

        .dropdown-menu {
            position: absolute;
            top: 120%;
            right: 0;
            width: 200px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 8px 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            z-index: 100;
        }

        .user-dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #555;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 14px;
        }

        .dropdown-menu a i {
            margin-right: 10px;
            font-size: 16px;
            color: #777;
            width: 20px;
            text-align: center;
            transition: all 0.2s;
        }

        .dropdown-menu a:hover {
            background-color: #f5f7fa;
            color: var(--primary);
        }

        .dropdown-menu a:hover i {
            color: var(--primary);
        }
        
        /* Mobile menu */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: #444;
            font-size: 22px;
            cursor: pointer;
            padding: 5px;
            margin-right: 10px;
            transition: color 0.2s;
        }
        
        .mobile-menu-btn:hover {
            color: var(--primary);
        }
        
        /* Responsive styles */
        @media (max-width: 992px) {
            .top-social-links {
                display: none;
            }
            
            .main-nav {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                box-shadow: 0 5px 10px rgba(0,0,0,0.1);
                padding: 0;
                margin: 0;
                display: none;
                z-index: 100;
            }
            
            .main-nav.active {
                display: block;
            }
            
            .main-nav ul {
                flex-direction: column;
                padding: 10px 0;
            }
            
            .main-nav li {
                margin: 0;
                width: 100%;
            }
            
            .main-nav a {
                padding: 12px 20px;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .main-nav a:after {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .user-controls {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .top-contact span {
                display: none;
            }
            
            .top-contact a {
                margin-right: 15px;
            }
            
            .user-toggle span {
                display: none;
            }
            
            .user-toggle {
                width: 40px;
                height: 40px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
            }
            
            .user-toggle i {
                margin: 0;
            }
            
            .cart-icon {
                margin-right: 10px;
            }
        }
        
        /* Product page styles */
        .products-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            position: relative;
            flex-wrap: wrap;
        }
        
        .products-title {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            position: relative;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        
        .products-title:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--primary);
            transition: width 0.3s ease;
        }
        
        .products-header:hover .products-title:after {
            width: 80px;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            position: relative;
        }
        
        /* Loading animation for product grid */
        .products-grid:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(to right, transparent, var(--primary), transparent);
            animation: loading 2s infinite linear;
            opacity: 0;
            z-index: 10;
            pointer-events: none;
        }
        
        .products-grid.loading:before {
            opacity: 1;
        }
        
        @keyframes loading {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .product-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        
        .product-img {
            height: 200px;
            overflow: hidden;
            position: relative;
        }
        
        .product-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .product-card:hover .product-img img {
            transform: scale(1.05);
        }
        
        .featured-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: linear-gradient(145deg, #3d5afe, #283593);
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            z-index: 2;
        }

        .product-info {
            padding: 15px;
        }

        .product-category {
            display: inline-block;
            font-size: 12px;
            color: #3d5afe;
            background-color: rgba(61, 90, 254, 0.1);
            padding: 3px 8px;
            border-radius: 12px;
            margin-bottom: 8px;
        }

        .product-name {
            font-size: 16px;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            height: 42px;
        }

        .product-price {
            font-size: 18px;
            font-weight: 700;
            color: #1a237e;
            margin-bottom: 15px;
        }

        .product-sizes {
            margin-top: 8px;
            margin-bottom: 12px;
        }

        .size-pills {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .size-pill {
            background-color: #f8f9fa;
            border: 1px solid #e2e2e2;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 12px;
            color: #333;
        }

        .no-sizes {
            font-size: 12px;
            color: #999;
            font-style: italic;
        }

        .product-actions {
            display: flex;
            gap: 10px;
        }

        .btn-view, .btn-cart {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 10px 0;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-view {
            background-color: #f5f5f5;
            color: #333;
        }

        .btn-cart {
            background: linear-gradient(145deg, #3d5afe, #283593);
            color: white;
        }

        .btn-view:hover {
            background-color: #e0e0e0;
        }

        .btn-cart:hover {
            background: linear-gradient(145deg, #536dfe, #3d5afe);
        }

        .no-products {
            text-align: center;
            padding: 40px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        }

        /* Search and Filter Styles */
        .product-filters {
            width: 100%;
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            justify-content: space-between;
        }

        .search-box {
            display: flex;
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .search-btn {
            position: absolute;
            right: 0;
            top: 0;
            height: 100%;
            padding: 0 15px;
            background: #333;
            color: white;
            border: none;
            border-top-right-radius: 4px;
            border-bottom-right-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .search-btn:hover {
            background: #555;
        }

        .category-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        #category-select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: white;
            min-width: 150px;
        }

        .filter-btn {
            padding: 10px 20px;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .filter-btn:hover {
            background-color: #555;
        }

        .reset-filters {
            padding: 10px 15px;
            color: #666;
            text-decoration: none;
            font-size: 14px;
        }

        .reset-filters:hover {
            text-decoration: underline;
        }

        .results-info {
            margin: 15px 0;
            font-size: 14px;
            color: #666;
        }

        .highlight {
            font-weight: bold;
            color: #333;
        }

        @media (max-width: 768px) {
            .filter-form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box, .category-filter {
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
    <div class="products-container">
        <div class="products-header">
            <h1 class="products-title">Featured Products</h1>
            
            <!-- Search and Filter Section -->
            <div class="product-filters">
                <form action="" method="GET" class="filter-form">
                    <div class="search-box">
                        <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($searchTerm ?? ''); ?>">
                        <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                    </div>
                    
                    <div class="category-filter">
                        <select name="category" id="category-select">
                            <option value="">All Categories</option>
                            <?php if (isset($categories) && $categories->num_rows > 0): 
                                while ($category = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $category['category_id']; ?>" <?php echo (isset($categoryFilter) && $categoryFilter == $category['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endwhile;
                            endif; ?>
                        </select>
                        <button type="submit" class="filter-btn">Apply Filters</button>
                        
                        <?php if (!empty($searchTerm) || !empty($categoryFilter)): ?>
                            <a href="products.php" class="reset-filters">Reset Filters</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            
            <!-- Results Info -->
            <?php if (isset($products)): ?>
                <div class="results-info">
                    <p>
                        <?php 
                        echo $products->num_rows . ' products found';
                        if (!empty($searchTerm)) {
                            echo ' for "<span class="highlight">' . htmlspecialchars($searchTerm) . '</span>"';
                        }
                        if (!empty($categoryFilter) && isset($categories)) {
                            // Reset the categories result pointer
                            $categories->data_seek(0);
                            while ($category = $categories->fetch_assoc()) {
                                if ($category['category_id'] == $categoryFilter) {
                                    echo ' in <span class="highlight">' . htmlspecialchars($category['category_name']) . '</span>';
                                    break;
                                }
                            }
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Products Grid -->
        <?php if (isset($products) && $products->num_rows > 0): ?>
            <div class="products-grid">
                <?php while ($product = $products->fetch_assoc()): ?>
                    <div class="product-card">
                        <div class="product-img">
                            <span class="featured-badge">Featured</span>
                            <img src="../uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                        <div class="product-info">
                            <?php if (!empty($product['category_name'])): ?>
                                <div class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <?php endif; ?>
                            
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="product-price">₹<?php echo number_format($product['price'], 2); ?></div>
                            
                            <!-- Product Sizes -->
                            <div class="product-sizes">
                                <?php
                                // Fetch available sizes for this product
                                $sizeQuery = "SELECT size FROM product_sizes WHERE product_id = ? AND status = 'available' AND stock_quantity > 0 ORDER BY size";
                                $sizeStmt = $conn->prepare($sizeQuery);
                                $sizeStmt->bind_param("i", $product['id']);
                                $sizeStmt->execute();
                                $sizeResult = $sizeStmt->get_result();
                                
                                if ($sizeResult->num_rows > 0) {
                                    echo '<div class="size-pills">';
                                    $sizeCount = 0;
                                    while ($size = $sizeResult->fetch_assoc()) {
                                        // Only show up to 4 sizes to save space
                                        if ($sizeCount < 4) {
                                            echo '<span class="size-pill">' . htmlspecialchars($size['size']) . '</span>';
                                        } else if ($sizeCount == 4) {
                                            echo '<span class="size-pill">+</span>';
                                            break;
                                        }
                                        $sizeCount++;
                                    }
                                    echo '</div>';
                                } else {
                                    echo '<span class="no-sizes">No sizes available</span>';
                                }
                                ?>
                            </div>
                            
                            <div class="product-actions">
                                <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn-view">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn-cart">
                                    <i class="fas fa-shopping-cart"></i> Shop Now
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-products">
                <h3>No products found</h3>
                <p>Sorry, we couldn't find any featured products at the moment.</p>
            </div>
        <?php endif; ?>
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
            
            // Products animations
            const productCards = document.querySelectorAll('.product-card');
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
                
                productCards.forEach(card => {
                    card.style.opacity = 0;
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    observer.observe(card);
                });
            }
            
            // Enhanced Product filtering functionality
            const searchForm = document.querySelector('.filter-form');
            const searchInput = document.querySelector('input[name="search"]');
            const categorySelect = document.getElementById('category-select');
            const searchButton = document.querySelector('.search-btn');
            const productsGrid = document.querySelector('.products-grid');
            
            // Add enter key support for search
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        showLoadingAnimation();
                        searchForm.submit();
                    }
                });
                
                // Add focus effect to search input
                searchInput.addEventListener('focus', function() {
                    this.parentElement.style.boxShadow = '0 0 0 3px rgba(61, 90, 254, 0.1)';
                });
                
                searchInput.addEventListener('blur', function() {
                    this.parentElement.style.boxShadow = 'none';
                });
            }
            
            // Add animation to search button
            if (searchButton) {
                searchButton.addEventListener('mousedown', function() {
                    this.style.transform = 'scale(0.95)';
                });
                
                searchButton.addEventListener('mouseup', function() {
                    this.style.transform = 'scale(1)';
                });
                
                searchButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    showLoadingAnimation();
                    setTimeout(() => {
                        searchForm.submit();
                    }, 300);
                });
            }
            
            // Auto-submit on category change if JavaScript is enabled
            if (categorySelect) {
                categorySelect.addEventListener('change', function() {
                    showLoadingAnimation();
                    setTimeout(() => {
                        searchForm.submit();
                    }, 300);
                });
            }
            
            // Function to show loading animation
            function showLoadingAnimation() {
                if (productsGrid) {
                    productsGrid.classList.add('loading');
                    
                    // Scroll to products grid
                    const offset = productsGrid.getBoundingClientRect().top + window.pageYOffset - 100;
                    window.scrollTo({
                        top: offset,
                        behavior: 'smooth'
                    });
                }
            }
            
            // Highlight search term in product names and descriptions
            if (searchInput && searchInput.value) {
                const searchTerm = searchInput.value.trim().toLowerCase();
                const productNames = document.querySelectorAll('.product-name');
                
                productNames.forEach(nameElement => {
                    const text = nameElement.textContent;
                    if (searchTerm && text.toLowerCase().includes(searchTerm)) {
                        const regex = new RegExp(`(${searchTerm})`, 'gi');
                        nameElement.innerHTML = text.replace(regex, '<span class="highlight">$1</span>');
                    }
                });
            }
            
            // Add quick view functionality
            const viewButtons = document.querySelectorAll('.btn-view');
            viewButtons.forEach(btn => {
                btn.addEventListener('mouseenter', function() {
                    const card = this.closest('.product-card');
                    const img = card.querySelector('.product-img img');
                    img.style.transform = 'scale(1.08)';
                });
                
                btn.addEventListener('mouseleave', function() {
                    const card = this.closest('.product-card');
                    const img = card.querySelector('.product-img img');
                    img.style.transform = 'scale(1.05)';
                });
            });
            
            // Add cart buttons effects
            const cartButtons = document.querySelectorAll('.btn-cart');
            cartButtons.forEach(btn => {
                btn.addEventListener('mousedown', function() {
                    this.style.transform = 'scale(0.95)';
                });
                
                btn.addEventListener('mouseup', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        });
    </script>
</body>
</html>
