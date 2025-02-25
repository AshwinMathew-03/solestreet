<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$database = "project";

// Connect to database
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $category_name = trim($_POST['category_name']);
    $description = trim($_POST['description']);

    if (!empty($category_name)) {
        $sql = "INSERT INTO categories (category_name, description) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $category_name, $description);

        if ($stmt->execute()) {
            $message = "<p style='color: green;'>Category added successfully.</p>";
        } else {
            $message = "<p style='color: red;'>Error: " . $conn->error . "</p>";
        }
        $stmt->close();
    } else {
        $message = "<p style='color: red;'>Category name is required.</p>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - Admin Dashboard</title>
    <link rel="stylesheet" href="category.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
        </div>
        <div class="sidebar-menu">
            <a href="admindashboard.php" class="menu-item">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <a href="add-product.php" class="menu-item">
                <i class="fas fa-box"></i>
                Products
            </a>
            <a href="category.php" class="menu-item">
                <i class="fas fa-list"></i>
                Categories
            </a>
            <a href="users.php" class="menu-item">
                <i class="fas fa-users"></i>
                Users
            </a>
            <a href="settings.php" class="menu-item">
                <i class="fas fa-cog"></i>
                Settings
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Category Management</h1>
            <div class="user-profile">
                <span>Admin User</span>
                <img src="path/to/avatar.jpg" alt="Admin">
            </div>
        </div>

        <div class="category-form-card">
            <div class="category-form-header">
                <h2>Add New Category</h2>
            </div>
            
            <?php 
            if (isset($message)) {
                $messageClass = strpos($message, 'success') !== false ? 'success' : 'error';
                echo '<div class="message ' . $messageClass . '">' . $message . '</div>';
            }
            ?>

            <form class="category-form" method="POST" action="">
                <div class="form-group">
                    <label for="category_name">Category Name</label>
                    <input type="text" id="category_name" name="category_name" 
                           placeholder="Enter category name" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" 
                              placeholder="Enter category description"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">Add Category</button>
                    <button type="button" class="btn-cancel">Cancel</button>
                </div>
            </form>
        </div>

        <div class="category-list card">
            <h3>Existing Categories</h3>
            <table class="category-table">
                <thead>
                    <tr>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Add your PHP code to fetch and display categories here
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
