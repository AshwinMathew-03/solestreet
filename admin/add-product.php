<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: ../login/login.html");
    exit();
}

include '../database/connect.php';

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    try {
        $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $price = floatval($_POST['price']);
        $category_id = intval($_POST['category']);
        $sizes = isset($_POST['sizes']) ? $_POST['sizes'] : [];

        if ($category_id == 0) {
            throw new Exception("Please select a valid category.");
        }

        if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Please select a product image.");
        }

        $target_dir = "../uploads/products/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file = $_FILES['product_image'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_type = mime_content_type($file_tmp);
        $new_filename = uniqid() . '_' . basename($file_name);
        $target_file = $target_dir . $new_filename;

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception("Error: Only JPG, JPEG, PNG & GIF files are allowed.");
        }

        if (!move_uploaded_file($file_tmp, $target_file)) {
            throw new Exception("Error uploading the image.");
        }

        // Fetch category name
        $category_name = "";
        $cat_query = "SELECT category_name FROM categories WHERE category_id = ?";
        $cat_stmt = $conn->prepare($cat_query);
        $cat_stmt->bind_param("i", $category_id);
        $cat_stmt->execute();
        $cat_result = $cat_stmt->get_result();
        if ($cat_row = $cat_result->fetch_assoc()) {
            $category_name = $cat_row['category_name'];
        }
        $cat_stmt->close();

        // Insert product
        $sql = "INSERT INTO products (name, description, price, category_id, category, image_path) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ssdiss", $product_name, $description, $price, $category_id, $category_name, $new_filename);
        if (!$stmt->execute()) {
            throw new Exception("Product insert failed: " . $stmt->error);
        }

        $product_id = $stmt->insert_id;
        $stmt->close();

        // Insert sizes and stock
        if (!empty($sizes)) {
            $size_sql = "INSERT INTO product_sizes (product_id, size, stock_quantity, status) VALUES (?, ?, ?, 'available')";
            $size_stmt = $conn->prepare($size_sql);
            if (!$size_stmt) {
                throw new Exception("Prepare size insert failed: " . $conn->error);
            }

            foreach ($sizes as $size) {
                $sanitized_size = mysqli_real_escape_string($conn, $size);
                $stock_key = 'stock_' . str_replace(['.', ' '], '_', $size);
                $stock_quantity = isset($_POST[$stock_key]) ? intval($_POST[$stock_key]) : 0;

                $size_stmt->bind_param("isi", $product_id, $sanitized_size, $stock_quantity);
                if (!$size_stmt->execute()) {
                    throw new Exception("Failed to insert size: " . $size_stmt->error);
                }
            }

            $size_stmt->close();
        }

        echo "<script>
                alert('Product and sizes with stock added successfully!');
                window.location.href = 'add-product.php';
              </script>";
    } catch (Exception $e) {
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            margin: 0;
            padding: 0;
        }

        .main-content {
            padding: 40px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            padding: 32px;
        }

        .header {
            margin-bottom: 32px;
            border-bottom: 1px solid #eee;
            padding-bottom: 16px;
        }

        .header h1 {
            color: #1a1a1a;
            font-size: 24px;
            margin: 0;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            background-color: #f8fafc;
        }

        input:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #4a90e2;
            background-color: white;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .file-upload {
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-upload:hover {
            border-color: #4a90e2;
            background: #f0f7ff;
        }

        .image-preview {
            margin-top: 16px;
            border-radius: 8px;
            overflow: hidden;
        }

        .button-group {
            display: flex;
            gap: 16px;
            margin-top: 32px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-primary {
            background: linear-gradient(to right, #4a90e2, #357abd);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(74, 144, 226, 0.2);
        }

        .btn-secondary {
            background: #f1f5f9;
            color: #64748b;
            text-decoration: none;
        }

        .btn-secondary:hover {
            background: #e2e8f0;
        }

        /* Success/Error Messages */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        /* Updated sidebar styles with new colors */
        .sidebar {
            width: 250px;
            height: 100vh;
            background-color: #2c3e50;  /* Updated background color */
            position: fixed;
            left: 0;
            top: 0;
            box-shadow: 2px 0 12px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            overflow-y: auto;
        }

        .logo {
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid #34495e;  /* Slightly lighter border */
            background: #2c3e50;
        }

        .logo img {
            height: 35px;
            width: auto;
        }

        .logo h2 {
            font-size: 20px;
            font-weight: 600;
            color: #ffffff;  /* White text */
        }

        .menu {
            padding: 10px 0;
            list-style: none;
            margin: 0;
        }

        .menu li {
            margin: 5px 0;
        }

        .menu a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #ffffff;  /* White text */
            text-decoration: none;
            transition: all 0.3s ease;
            gap: 10px;
            font-size: 15px;
            font-weight: 500;
        }

        .menu a:hover {
            background: #34495e;  /* Slightly lighter on hover */
            color: #ffffff;
        }

        .menu a.active {
            background: #34495e;
            color: #4a90e2;
            border-right: 3px solid #4a90e2;
        }

        .menu a i {
            width: 20px;
            text-align: center;
            font-size: 18px;
        }

        /* Ensure main content matches */
        .main-content {
            margin-left: 250px;
            padding: 30px;
            min-height: 100vh;
            background: #f4f6f9;
        }

        /* Container adjustments */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="../images/logo.png" alt="Logo">
            <h2>Solestreet</h2>
        </div>
        <ul class="menu">
            <li>
                <a href="admindashboard.php">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="active">
                <a href="add-product.php">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
            <li>
                <a href="category.php">
                    <i class="fas fa-list"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li>
                <a href="orders.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li>
                <a href="customers.php">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
            </li>
            <li>
                <a href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Mobile sidebar toggle -->
    <button class="sidebar-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main content -->
    <main class="main-content">
        <div class="container">
            <div class="card">
                <div class="header">
                    <h1>Add New Product</h1>
                </div>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" id="name" name="product_name" placeholder="Enter product name" required>
                    </div>

                    <div class="form-group">
                        <label for="price">Price ($)</label>
                        <input type="number" id="price" name="price" step="0.01" placeholder="0.00" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" placeholder="Enter product description" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <option value="">Select a category</option>
                            <?php
                            $sql = "SELECT category_id, category_name FROM categories";
                            $result = $conn->query($sql);
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($row['category_id']) . "'>" . htmlspecialchars($row['category_name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <div class="file-upload">
                            <input type="file" id="image" name="product_image" accept="image/*" required>
                        </div>
                        <div class="image-preview"></div>
                    </div>

                    <div class="form-group">
    <label>Available Sizes (Indian) with Stock</label>
    <div class="size-options">
        <?php
        $shoe_sizes = ['IND 6', 'IND 7', 'IND 8', 'IND 9', 'IND 10', 'IND 11', 'IND 12', 'IND 13'];
        foreach ($shoe_sizes as $size) {
            $size_id = str_replace(['.', ' '], '_', $size);
            echo '<div class="size-checkbox">';
            echo '<input type="checkbox" id="size_' . $size_id . '" name="sizes[]" value="' . htmlspecialchars($size) . '">';
            echo '<label for="size_' . $size_id . '">' . htmlspecialchars($size) . '</label>';
            echo '<input type="number" name="stock_' . $size_id . '" placeholder="Stock" min="0" style="margin-left:10px; width:80px;" />';
            echo '</div>';
        }
        ?>
    </div>
</div>


                    <div class="button-group">
                        <button type="submit" name="submit" class="btn btn-primary">Add Product</button>
                        <a href="admindashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script>
        // Sidebar toggle functionality for mobile
        document.querySelector('.sidebar-toggle').addEventListener('click', function() {
            document.querySelector('.sidebar').classList.toggle('active');
        });

        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.querySelector('.image-preview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" style="max-width: 100%; height: auto; border-radius: 8px;">`;
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
