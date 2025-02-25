<?php
session_start();

// Check session
// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
//     header("Location: ../login/login.php");
//     exit();
// }

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project";

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
                        <i class="fas fa-tachometer-alt"></i>
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
                    <a href="settings.php">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
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
                <div class="user-info">
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
                        <span class="badge">3</span>
                    </div>
                    <div class="user">
                        <img src="../images/user-avatar.png" alt="User Avatar">
                        <!-- <span><?php //echo $_SESSION['name']; ?></span> -->
                    </div>
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
                            $query = "SELECT COUNT(*) as total FROM products";
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
                            $query = "SELECT COUNT(*) as total FROM products ";
                            $result = $conn->query($query);
                            $row = $result->fetch_assoc();
                            echo $row['total'];
                            ?>
                        </p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-icon" style="background-color: #e74c3c;">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="card-info">
                        <h3>Total Revenue</h3>
                        <p>₹
                            <?php
                            // $query = "SELECT SUM(total_amount) as total FROM products";
                            // $result = $conn->query($query);
                            // $row = $result->fetch_assoc();
                            // echo number_format($row['total'] ?? 0, 2);
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="recent-orders">
                <h2>Recent Orders</h2>
                <table>
                    <!-- ... your existing orders table code ... -->
                </table>
            </div>

            <!-- Products List -->
            <div class="product-list">
                <h2>All Products</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <!-- <th>Image</th> -->
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <!-- <th>Stock</th> -->
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch products from database
                        $query = "SELECT p.*, c.category_name 
                                 FROM products p 
                                 LEFT JOIN categories c ON p.category_id = c.category_id 
                                 ORDER BY p.product_id DESC";
                        $result = $conn->query($query);

                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td>#<?php echo $row['product_id']; ?></td>
                                    <!-- <td><img src="../uploads/<?php //echo $row['image']; ?>" 
                                           alt="<?php //echo $row['name']; ?>" 
                                           style="width: 50px; height: 50px; object-fit: cover;"></td> -->
                                    <td><?php echo $row['product_name']; ?></td>
                                    <td><?php echo $row['category_name']; ?></td>
                                    <td>₹<?php echo number_format($row['price'], 2); ?></td>
                                    <!-- <td><?php //echo $row['stock']; ?></td> -->
                                    <td>
                                        <button class="action-btn edit" 
                                                onclick="editProduct(<?php echo $row['product_id']; ?>)">Edit</button>
                                        <button class="action-btn delete" 
                                                onclick="deleteProduct(<?php echo $row['product_id']; ?>, '<?php echo addslashes($row['product_name']); ?>')">Delete</button>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align: center;'>No products found</td></tr>";
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
            // Create form data
            const formData = new FormData();
            formData.append('product_id', productId);

            // Send delete request
            fetch('delete.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product deleted successfully');
                    window.location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Could not delete product'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting product');
            });
        }
    }
    </script>

    <script src="admindashboard.js"></script>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?> 