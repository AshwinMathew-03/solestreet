<?php
session_start();

// Debug line
error_log("Session contents in dashboard: " . print_r($_SESSION, true));

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    error_log("Session check failed - User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set') . 
              ", Role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'not set'));
    header("Location: ../login/login.html");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project1";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solestreet Admin</title>
    <link rel="stylesheet" href="admindashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="category.css">
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
                <li class="active">
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

        <!-- Main Content -->
        <div class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <div class="search-bar">
                    <i class="fas fa-search"></i>
                    <input type="text" placeholder="Search here...">
                </div>
            </div>

            <!-- Dashboard Cards -->
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-icon" style="background-color: #4a90e2;">
                        <i class="fas fa-shopping-bag"></i>
                    </div>
                    <div class="card-info">
                        <h3>Total Products</h3>
                        <p>
                            <?php
                            $query = "SELECT COUNT(*) as total FROM products";
                            $result = $conn->query($query);
                            $row = $result->fetch_assoc();
                            echo $row['total'];
                            ?>
                        </p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon" style="background-color: #2ecc71;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="card-info">
                        <h3>Total Orders</h3>
                        <p>
                            <?php
                            $query = "SELECT COUNT(*) as total FROM products";  // FIXED
                            $result = $conn->query($query);
                            $row = $result->fetch_assoc();
                            echo $row['total'];
                            ?>
                        </p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon" style="background-color: #f1c40f;">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="card-info">
                        <h3>Total Customers</h3>
                        <p>
                            <?php
                            $query = "SELECT COUNT(*) as total FROM user";  // FIXED
                            $result = $conn->query($query);
                            $row = $result->fetch_assoc();
                            echo $row['total'];
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Products List -->
            <div class="product-list">
                <h2>All Products</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch products from database
                        $query = "SELECT p.id, p.name, p.price, c.category_name 
                                  FROM products p
                                  LEFT JOIN categories c ON p.category_id = c.category_id 
                                  ORDER BY p.id DESC";
                        $result = $conn->query($query);

                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td>#<?php echo $row['id']; ?></td>
                                    <td><?php echo $row['name']; ?></td>
                                    <td><?php echo $row['category_name']; ?></td>
                                    <td>₹<?php echo number_format($row['price'], 2); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="action-btn edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="delete.php?id=<?php echo $row['id']; ?>" class="action-btn delete" 
                                               onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align: center;'>No products found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function editProduct(productId) {
        window.location.href = `edit-product.php?id=${productId}`;
    }

    function deleteProduct(productId, productName) {
        if (confirm('Are you sure you want to delete product: ' + productName + '?')) {
            fetch('delete.php', {
                method: 'POST',
                body: new URLSearchParams({ product_id: productId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product deleted successfully');
                    location.reload();
                } else {
                    alert('Error deleting product');
                }
            });
        }
    }
    </script>

</body>
</html>

<?php
$conn->close();
?>
