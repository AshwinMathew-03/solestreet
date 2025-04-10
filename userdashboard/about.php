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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Solestreet</title>
    <link rel="stylesheet" href="userdashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3d5afe;       /* Vibrant blue */
            --hover: #536dfe;         /* Lighter blue for hover */
            --dark: #283593;          /* Darker blue */
            --white: #ffffff;
            --price-color: #1a237e;   /* Deep blue for price */
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
            padding: 8px 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .top-bar {
            background: linear-gradient(to right, #1a237e, #3d5afe);
            padding: 8px 0;
            color: white;
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
            position: relative;
        }

        .brand h1 span {
            color: var(--primary);
        }

        .brand h1:after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 30px;
            height: 2px;
            background: var(--primary);
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
            transition: all 0.2s;
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

        .main-nav a:hover,
        .main-nav a.active {
            color: var(--primary);
        }

        .main-nav a:hover:after,
        .main-nav a.active:after {
            width: 20px;
        }

        .user-controls {
            display: flex;
            align-items: center;
            margin-left: 25px;
        }

        .cart-icon {
            position: relative;
            font-size: 18px;
            color: #555;
            text-decoration: none;
            margin-right: 20px;
            transition: all 0.3s ease;
        }

        .cart-icon:hover {
            color: var(--primary);
            transform: translateY(-2px);
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
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
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
            transition: all 0.2s;
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
            transition: background-color 0.2s;
            font-size: 14px;
        }

        .dropdown-menu a i {
            margin-right: 10px;
            font-size: 16px;
            color: #777;
            width: 20px;
            text-align: center;
        }

        .dropdown-menu a:hover {
            background-color: #f5f7fa;
            color: var(--primary);
        }

        .dropdown-menu a:hover i {
            color: var(--primary);
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: #555;
            cursor: pointer;
        }

        @media (max-width: 992px) {
            .top-social-links {
                display: none;
            }
            
            .main-nav {
                display: none;
            }
            
            .mobile-menu-btn {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .top-contact span {
                display: none;
            }
            
            .top-contact a {
                margin-right: 15px;
            }
            
            .top-bar-content {
                padding: 0 15px;
            }
            
            .header-content {
                padding: 12px 0;
            }
            
            .brand h1 {
                font-size: 24px;
            }
            
            /* About page responsive styles */
            .about-header {
                height: 300px;
            }
            
            .about-header h1 {
                font-size: 36px;
            }
            
            .about-section h2 {
                font-size: 28px;
            }
            
            .about-grid,
            .values-grid,
            .team-grid {
                grid-template-columns: 1fr;
            }
            
            .top-social-links {
                margin-right: 10px;
            }
            
            .top-social-links a {
                width: 28px;
                height: 28px;
                margin-left: 5px;
            }
        }

        /* About page specific styles */
        .about-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .about-header {
            position: relative;
            height: 400px;
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1552346154-21d32810aba3?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80') center/cover no-repeat;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
        }

        .about-header-content {
            max-width: 800px;
            padding: 0 30px;
        }

        .about-header h1 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 15px;
            letter-spacing: -0.5px;
        }

        .about-header p {
            font-size: 18px;
            line-height: 1.6;
            opacity: 0.9;
        }

        .about-section {
            margin-bottom: 70px;
        }

        .about-section h2 {
            font-size: 32px;
            color: var(--dark);
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
        }

        .about-section h2:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 60px;
            height: 3px;
            background: var(--primary);
        }

        .about-section p {
            color: #444;
            font-size: 16px;
            line-height: 1.8;
            margin-bottom: 20px;
        }

        .about-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .about-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .about-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .about-card-img {
            height: 200px;
            overflow: hidden;
        }

        .about-card-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .about-card:hover .about-card-img img {
            transform: scale(1.05);
        }

        .about-card-content {
            padding: 25px;
        }

        .about-card h3 {
            font-size: 20px;
            color: var(--dark);
            margin-bottom: 15px;
        }

        .about-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 0;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .value-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .value-card:hover {
            transform: translateY(-5px);
        }

        .value-icon {
            width: 60px;
            height: 60px;
            background: var(--light-gray);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--primary);
            font-size: 24px;
        }

        .value-card h3 {
            font-size: 18px;
            color: var(--dark);
            margin-bottom: 12px;
        }

        .value-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .team-section {
            text-align: center;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .team-member {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .team-img {
            height: 250px;
            overflow: hidden;
        }

        .team-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .team-info {
            padding: 20px;
        }

        .team-info h3 {
            font-size: 18px;
            color: var(--dark);
            margin-bottom: 5px;
        }

        .team-info p {
            color: #888;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .social-links {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .social-links a {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--light-gray);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #555;
            transition: background 0.3s, color 0.3s;
        }

        .social-links a:hover {
            background: var(--primary);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="main-header">
        <div class="top-bar">
            <div class="top-bar-content">
                <div class="top-contact">
                    <a href="tel:857890143"><i class="fas fa-phone-alt"></i> <span>+1 (234) 567-890</span></a>
                    <a href="mailto:info@solestreet.com"><i class="fas fa-envelope"></i> <span>info@solestreet.com</span></a>
                </div>
                <div class="top-contact">
                    <a href="#"><i class="fas fa-shipping-fast"></i> <span>Free Shipping Over 1500</span></a>
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
                            <li><a href="about.php" class="active">About</a></li>
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

    <!-- About Content -->
    <div class="about-container">
        <!-- Hero Section -->
        <div class="about-header">
            <div class="about-header-content">
                <h1>Our Story</h1>
                <p>Elevating footwear since 2015. At Solestreet, we're not just selling shoes – we're crafting experiences, one step at a time.</p>
            </div>
        </div>

        <!-- Story Section -->
        <div class="about-section">
            <h2>Who We Are</h2>
            <p>Founded in 2015, Solestreet began with a simple vision: to provide premium footwear that combines innovative design, exceptional quality, and unmatched comfort. What started as a small boutique in downtown has grown into a renowned destination for shoe enthusiasts and fashion-forward individuals across the country.</p>
            <p>Our curated collection features the finest selection from worldwide renowned brands and up-and-coming designers, ensuring that our customers always stay ahead of the curve. We take pride in offering exclusive limited editions and collaborations that can't be found elsewhere.</p>
            
            <div class="about-grid">
                <div class="about-card">
                    <div class="about-card-img">
                        <img src="https://images.unsplash.com/photo-1549298916-b41d501d3772?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2012&q=80" alt="Premium Selection">
                    </div>
                    <div class="about-card-content">
                        <h3>Premium Selection</h3>
                        <p>We partner with the most prestigious brands and designers to bring you footwear that stands at the intersection of artistry and innovation.</p>
                    </div>
                </div>
                
                <div class="about-card">
                    <div class="about-card-img">
                        <img src="https://images.unsplash.com/photo-1511556532299-8f662fc26c06?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Expert Curation">
                    </div>
                    <div class="about-card-content">
                        <h3>Expert Curation</h3>
                        <p>Our team of footwear specialists meticulously selects each piece, ensuring that only the finest products make it to our shelves.</p>
                    </div>
                </div>
                
                <div class="about-card">
                    <div class="about-card-img">
                        <img src="https://images.unsplash.com/photo-1460353581641-37baddab0fa2?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2071&q=80" alt="Customer Experience">
                    </div>
                    <div class="about-card-content">
                        <h3>Customer Experience</h3>
                        <p>We're committed to providing an exceptional shopping experience, from personalized styling advice to seamless delivery.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Our Values -->
        <div class="about-section">
            <h2>Our Values</h2>
            <p>At Solestreet, our core values guide everything we do. They shape our culture, inform our decisions, and drive our commitment to excellence in every aspect of our business.</p>
            
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Quality</h3>
                    <p>We never compromise on quality, sourcing only the finest materials and craftsmanship for our collections.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3>Sustainability</h3>
                    <p>We're committed to reducing our environmental footprint through responsible practices and partnerships.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h3>Integrity</h3>
                    <p>We conduct our business with honesty, transparency, and respect for all stakeholders.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3>Innovation</h3>
                    <p>We continuously seek new ways to push boundaries in design, technology, and customer experience.</p>
                </div>
            </div>
        </div>

        <!-- Our Team -->
        <div class="about-section team-section">
            <h2>Meet Our Team</h2>
            <p>The passionate individuals behind Solestreet who make it all happen.</p>
            
            <div class="team-grid">
                <div class="team-member">
                    <div class="team-img">
                        <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1974&q=80" alt="James Wilson">
                    </div>
                    <div class="team-info">
                        <h3>James Wilson</h3>
                        <p>Founder & CEO</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="team-img">
                        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1976&q=80" alt="Sarah Johnson">
                    </div>
                    <div class="team-info">
                        <h3>Sarah Johnson</h3>
                        <p>Creative Director</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="team-img">
                        <img src="https://images.unsplash.com/photo-1519085360753-af0119f7cbe7?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1974&q=80" alt="Michael Chen">
                    </div>
                    <div class="team-info">
                        <h3>Michael Chen</h3>
                        <p>Product Manager</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-member">
                    <div class="team-img">
                        <img src="https://images.unsplash.com/photo-1573497491765-dccce02b29df?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1974&q=80" alt="Emma Rodriguez">
                    </div>
                    <div class="team-info">
                        <h3>Emma Rodriguez</h3>
                        <p>Head of Customer Experience</p>
                        <div class="social-links">
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Header scroll effect
        document.addEventListener('DOMContentLoaded', function() {
            const header = document.querySelector('.main-header');
            const aboutCards = document.querySelectorAll('.about-card');
            const valueCards = document.querySelectorAll('.value-card');
            
            // Handle header scroll effect
            window.addEventListener('scroll', function() {
                if (window.scrollY > 100) {
                    header.classList.add('scrolled');
                } else {
                    header.classList.remove('scrolled');
                }
            });
            
            // Mobile menu functionality
            const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
            const mainNav = document.querySelector('.main-nav');
            
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    if (mainNav.style.display === 'block') {
                        mainNav.style.display = 'none';
                    } else {
                        mainNav.style.display = 'block';
                    }
                });
            }
            
            // Add any additional animations here
        });
    </script>
</body>
</html> 