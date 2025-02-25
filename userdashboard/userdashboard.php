<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.html");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solestreet</title>
    <link rel="stylesheet" href="userdashboard.css">
</head>
<body>
    <nav>
        <div class="logo">Solestreet</div>
        <div class="nav-links">
            <a href="#">Footwear</a>
            <a href="#">Shop</a>
            <a href="#">Contact</a>
            <a href="#">Sale</a>
        </div>
        <div class="account-section"><img src="https://images.unsplash.com/photo-1499996860823-5214fcc65f8f?q=80&w=1966&auto=format&fit=crop&ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" style="border-radius: 50%;" height="40px" width="40px" alt="">
            <a href="#"></a> <p><?php echo $_SESSION['name']; ?></p>
            <button class="cart-btn">🛒</button>
            <a href="../logout.php" class="logout-btn">Logout</a>
        </div>
    </nav>

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

    <div class="products-container">
        <button class="scroll-arrow scroll-left">←</button>
        <button class="scroll-arrow scroll-right">→</button>
        <div class="products">
            <div class="product-card">
                <img src="jordan.jpg" alt="Air Jordan 12 Retro">
                <h3>Air Jordan 12 Retro</h3>
                <p class="price">₹8,295 to ₹83,533</p>
                <button class="cart-btn">🛒</button>
            </div>
            <div class="product-card">
                <img src="nike.jpg" alt="Nike Air Max 270 React">
                <h3>Nike Air Max 270 React</h3>
                <p class="price">₹6,995 to ₹14,495</p>
                <button class="cart-btn">🛒</button>
            </div>
            <div class="product-card">
                <img src="puma.webp" alt="Puma Trinity Lite">
                <h3>Puma Trinity Lite</h3>
                <p class="price">₹2,799 to ₹4,199</p>
                <button class="cart-btn">🛒</button>
            </div>
            <div class="product-card">
                <img src="addidas.jpg" alt="Adidas 4D Run 1.0">
                <h3>Adidas 4D Run 1.0</h3>
                <p class="price">₹33,784</p>
                <button class="cart-btn">🛒</button>
            </div>
            <div class="product-card">
                <img src="rebook.jpg" alt="Reebok Classic">
                <h3>Reebok Classic</h3>
                <p class="price">₹33,784</p>
                <button class="cart-btn">🛒</button>
            </div>
        </div>
    </div>

    <script>
        const scrollContainer = document.querySelector('.products');
        const scrollLeftBtn = document.querySelector('.scroll-left');
        const scrollRightBtn = document.querySelector('.scroll-right');
        const scrollAmount = 320; // Width of product card + gap

        scrollLeftBtn.addEventListener('click', () => {
            scrollContainer.scrollBy({
                left: -scrollAmount,
                behavior: 'smooth'
            });
        });

        scrollRightBtn.addEventListener('click', () => {
            scrollContainer.scrollBy({
                left: scrollAmount,
                behavior: 'smooth'
            });
        });
    </script>
</body>
</html>