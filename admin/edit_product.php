<?php
// Start session at the very beginning
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: ../login/login.html");
    exit();
}

// Include database connection
include '../database/connect.php';

// Ensure database connection is working
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Initialize message variables
$success_message = $error_message = "";

// Check if product ID is provided in URL
if (isset($_GET['id'])) {
    $product_id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Fetch product details
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    } else {
        echo "<script>alert('Product not found!'); window.location.href='products.php';</script>";
        exit();
    }
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    try {
        // Get and sanitize form data
        $product_name = mysqli_real_escape_string($conn, $_POST['product_name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $price = floatval($_POST['price']);
        $category_id = intval($_POST['category']);
        $product_id = intval($_POST['product_id']);

        // Debug: Print received values
        error_log("Product ID: " . $product_id);
        error_log("Product Name: " . $product_name);
        error_log("Description: " . $description);
        error_log("Price: " . $price);
        error_log("Category ID: " . $category_id);

        // Check if category is selected
        if ($category_id == 0) {
            throw new Exception("Please select a valid category.");
        }

        // Initialize SQL query without image
        $sql = "UPDATE products SET name = ?, description = ?, price = ?, category_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdii", $product_name, $description, $price, $category_id, $product_id);

        // If new image is uploaded, handle it
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../uploads/products/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            // Generate unique filename
            $file = $_FILES['product_image'];
            $file_name = $file['name'];
            $file_tmp = $file['tmp_name'];
            $file_type = mime_content_type($file_tmp);
            $new_filename = uniqid() . '_' . basename($file_name);
            $target_file = $target_dir . $new_filename;

            // Check file type
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file_type, $allowed_types)) {
                throw new Exception("Error: Only JPG, JPEG, PNG & GIF files are allowed.");
            }

            // Move uploaded file
            if (move_uploaded_file($file_tmp, $target_file)) {
                // Update SQL to include new image
                $sql = "UPDATE products SET name = ?, description = ?, price = ?, category_id = ?, image_path = ? WHERE id = ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssdisi", $product_name, $description, $price, $category_id, $new_filename, $product_id);
            }
        }

        // Execute the statement
        if ($stmt->execute()) {
            // Debug: Print success message
            error_log("Product updated successfully!");
            echo "<div class='success-message'>Product updated successfully! Redirecting...</div>";
            echo "<script>
                    setTimeout(function() {
                        window.location.href = 'admindashboard.php';
                    }, 2000);
                  </script>";
        } else {
            // Debug: Print error message
            error_log("Error updating product: " . $stmt->error);
            throw new Exception("Error updating product: " . $stmt->error);
        }

    } catch (Exception $e) {
        error_log("Exception caught: " . $e->getMessage());
        echo "<div class='error-message'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="edit_product.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="../assets/logo.png" alt="Logo">
            <span>Solestreet</span>
        </div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="admindashboard.php" class="nav-link">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="add-product.php" class="nav-link active">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="categories.php" class="nav-link">
                    <i class="fas fa-list"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="settings.php" class="nav-link">
                    <i class="fas fa-user"></i>
                    <span>Profile</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="../logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Mobile sidebar toggle -->
    <button class="sidebar-toggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main content -->
    <main class="main-content">
        <div class="container">
            <div class="card">
                <div class="header">
                    <h1>Update Product</h1>
                </div>
                
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="name">Product Name</label>
                        <input type="text" 
                               id="name" 
                               name="product_name" 
                               placeholder="Enter product name" 
                               value="<?php echo isset($product['name']) ? htmlspecialchars($product['name']) : ''; ?>" 
                               >
                    </div>

                    <div class="form-group">
                        <label for="price">Price ($)</label>
                        <input type="number" 
                               id="price" 
                               name="price" 
                               step="0.01" 
                               placeholder="0.00" 
                               value="<?php echo isset($product['price']) ? htmlspecialchars($product['price']) : ''; ?>" 
                               >
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" 
                                  name="description" 
                                  placeholder="Enter product description" 
                                  ><?php echo isset($product['description']) ? htmlspecialchars($product['description']) : ''; ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="">Select a category</option>
                            <?php
                            $sql = "SELECT category_id, category_name FROM categories";
                            $result = $conn->query($sql);
                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $selected = isset($product['category_id']) && $product['category_id'] == $row['category_id'] ? 'selected' : '';
                                    echo "<option value='" . htmlspecialchars($row['category_id']) . "' " . $selected . ">" 
                                         . htmlspecialchars($row['category_name']) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <div class="file-upload">
                            <input type="file" id="image" name="product_image" accept="image/*">
                            <?php if (isset($product['image_path']) && $product['image_path']): ?>
                                <p class="current-image">Current image: <?php echo htmlspecialchars($product['image_path']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="image-preview">
                            <?php if (isset($product['image_path']) && $product['image_path']): ?>
                                <img src="../uploads/products/<?php echo htmlspecialchars($product['image_path']); ?>" 
                                     alt="Product Image"
                                     style="max-width: 100%; height: auto; border-radius: 8px;">
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="submit" name="submit" class="btn btn-primary">Update Product</button>
                        <a href="admindashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>

                    <input type="hidden" name="product_id" value="<?php echo isset($product['id']) ? htmlspecialchars($product['id']) : ''; ?>">

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

    <style>
        .error-message {
            color: #dc3545;
            background-color: #ffe6e6;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #dc3545;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
        }

        .success-message {
            color: #28a745;
            background-color: #e8f5e9;
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #28a745;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
        }
    </style>
</body>
</html>
