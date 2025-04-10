<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: ../login/login.html");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "project1";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user data from the database - ONLY CUSTOMERS (role != 1)
$sql = "SELECT * FROM user WHERE role != 1 ORDER BY id DESC";
$result = $conn->query($sql);

// If query fails, show error
if (!$result) {
    die("Error in SQL query: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Admin Dashboard</title>
    <link rel="stylesheet" href="admindashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .user-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
            margin-top: 20px;
        }
        
        .user-table th, .user-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        
        .user-table thead {
            background-color: #f8f9fa;
        }
        
        .user-table th {
            font-weight: 600;
            color: #343a40;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }
        
        .user-table tbody tr:hover {
            background-color: #f1f3f5;
        }
        
        .user-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .user-status {
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-active {
            background-color: #d1fae5;
            color: #047857;
        }
        
        .status-inactive {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 13px;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-view {
            background-color: #e3f2fd;
            color: #0d6efd;
        }
        
        .btn-view:hover {
            background-color: #d0e7fb;
        }
        
        .btn-edit {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .btn-edit:hover {
            background-color: #ffeeba;
        }
        
        .btn-delete {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .btn-delete:hover {
            background-color: #f5c6cb;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .section-header h2 {
            margin: 0;
            color: #343a40;
            font-weight: 600;
        }
        
        .add-new-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .add-new-btn:hover {
            background-color: #43A047;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .search-box {
            margin-bottom: 24px;
            max-width: 400px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 20px;
            padding-left: 40px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.04);
            transition: all 0.2s ease;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        
        .search-box::before {
            content: '\f002';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .empty-state i {
            font-size: 56px;
            margin-bottom: 24px;
            color: #dee2e6;
        }
        
        .empty-state h3 {
            margin-bottom: 8px;
            color: #343a40;
            font-weight: 600;
        }
        
        .empty-state p {
            color: #6c757d;
            max-width: 400px;
            margin: 0 auto;
        }
    </style>
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
                <li class="active">
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
                    <input type="text" id="searchGlobal" placeholder="Search here...">
                </div>
            </div>

            <div class="section-header">
                <h2>Customer Management</h2>
                <a href="add-customer.php" class="add-new-btn"><i class="fas fa-plus"></i> Add New Customer</a>
            </div>

            <div class="search-box">
                <input type="text" id="searchInput" placeholder="Search by name, email, or phone...">
            </div>

            <?php
            if ($result && $result->num_rows > 0) {
                echo '<table class="user-table" id="userTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>';
                
                while($row = $result->fetch_assoc()) {
                    echo '<tr>
                        <td>' . htmlspecialchars($row['id']) . '</td>
                        <td>' . htmlspecialchars($row['name'] ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($row['email'] ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($row['phone'] ?? 'N/A') . '</td>
                        <td>' . htmlspecialchars($row['address'] ?? 'N/A') . '</td>
                        <td>';
                    
                    // Status display
                    if (isset($row['status'])) {
                        if ($row['status'] == 1) {
                            echo '<span class="user-status status-active">Active</span>';
                        } else {
                            echo '<span class="user-status status-inactive">Inactive</span>';
                        }
                    } else {
                        echo 'N/A';
                    }
                    
                    echo '</td>
                        <td class="action-buttons">
                            <a href="view-customer.php?id=' . $row['id'] . '" class="btn btn-view"><i class="fas fa-eye"></i> View</a>
                            <a href="edit-customer.php?id=' . $row['id'] . '" class="btn btn-edit"><i class="fas fa-edit"></i> Edit</a>
                            <a href="delete-customer.php?id=' . $row['id'] . '" class="btn btn-delete" onclick="return confirm(\'Are you sure you want to delete this customer?\')"><i class="fas fa-trash"></i> Delete</a>
                        </td>
                    </tr>';
                }
                
                echo '</tbody></table>';
            } else {
                echo '<div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No Customers Found</h3>
                    <p>There are currently no customers in the system.</p>
                </div>';
            }
            
            $conn->close();
            ?>
        </div>
    </div>

    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let searchText = this.value.toLowerCase();
            let table = document.getElementById('userTable');
            if (!table) return; // Exit if no table exists
            
            let rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) { // Start at 1 to skip header row
                let showRow = false;
                let cells = rows[i].getElementsByTagName('td');
                
                for (let j = 0; j < cells.length - 1; j++) { // Check all columns except the last (actions)
                    if (cells[j] && cells[j].textContent.toLowerCase().includes(searchText)) {
                        showRow = true;
                        break;
                    }
                }
                
                rows[i].style.display = showRow ? '' : 'none';
            }
        });
    </script>
</body>
</html>