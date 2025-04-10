<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="category.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2>Admin Panel</h2>
        </div>
        <div class="sidebar-menu">
            <a href="#" class="menu-item">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-box"></i>
                Products
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-list"></i>
                Categories
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-users"></i>
                Users
            </a>
            <a href="#" class="menu-item">
                <i class="fas fa-cog"></i>
                Settings
            </a>
        </div>
    </div>

    <div class="main-content">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Dashboard</h1>
            <div class="user-profile">
                <span>Admin User</span>
                <img src="path/to/avatar.jpg" alt="Admin">
            </div>
        </div>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="number">150</div>
            </div>
            <div class="stat-card">
                <h3>Total Categories</h3>
                <div class="number">12</div>
            </div>
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number">1,240</div>
            </div>
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="number">524</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="card">
                <h3>Recent Orders</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>#12345</td>
                            <td>John Doe</td>
                            <td>Completed</td>
                            <td>$299.99</td>
                        </tr>
                        <!-- Add more rows as needed -->
                    </tbody>
                </table>
            </div>
            
            <div class="card">
                <h3>Quick Actions</h3>
                <div class="btn-group">
                    <a href="products.php" class="btn btn-primary">Add Product</a>
                    <a href="category.php" class="btn btn-primary">Add Category</a>
                    <a href="orders.php" class="btn btn-primary">View Orders</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 