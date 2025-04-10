<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$database = "project1";

// Connect to database
$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
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
    <link rel="stylesheet" href="admindashboard.css">
    <link rel="stylesheet" href="category.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
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
                <li>
                    <a href="add-product.php">
                        <i class="fas fa-box"></i>
                        <span>Products</span>
                    </a>
                </li>
                <li class="active">
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
                    <a href="settings.php">
                        <i class="fas fa-user"></i>
                        <span>Profile</span>
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

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search here...">
                </div>
            </div>

            <!-- Content Container -->
            <div class="content-container">
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
                            // Include database connection if not already included
                            include_once '../database/connect.php';
                            
                            // Query to fetch all categories
                            $sql = "SELECT * FROM categories ORDER BY category_name";
                            $result = $conn->query($sql);
                            
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                    echo "<td><span class='status active'>Active</span></td>";
                                    echo "<td class='actions'>";
                                    echo "<button class='btn-edit' onclick='editCategory(" . $row['category_id'] . ")'><i class='bx bx-edit'></i></button>";
                                    echo "<button class='btn-delete' onclick='deleteCategory(" . $row['category_id'] . ")'><i class='bx bx-trash'></i></button>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4' class='no-data'>No categories found</td></tr>";
                            }
                            
                            // Close the database connection
                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add JavaScript for edit and delete functions -->
    <script>
    function editCategory(categoryId) {
        // Implement edit functionality
        console.log('Edit category:', categoryId);
    }

    function deleteCategory(categoryId) {
        if (confirm('Are you sure you want to delete this category?')) {
            // Implement delete functionality
            console.log('Delete category:', categoryId);
        }
    }
    </script>
</body>
</html>
