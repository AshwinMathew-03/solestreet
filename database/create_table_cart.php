<?php
// Include database connection
include 'connect.php';

// First, let's check the structure of referenced tables
$checkUsers = "SHOW CREATE TABLE user";
$checkProducts = "SHOW CREATE TABLE products";

try {
    // Check users table structure
    $result = $conn->query($checkUsers);
    if (!$result) {
        throw new Exception("Error checking users table: " . $conn->error);
    }
    $usersTable = $result->fetch_assoc();
    
    // Check products table structure
    $result = $conn->query($checkProducts);
    if (!$result) {
        throw new Exception("Error checking products table: " . $conn->error);
    }
    $productsTable = $result->fetch_assoc();

    // SQL to create cart table with matching column types
    $sql = "CREATE TABLE IF NOT EXISTS cart (
        cart_id INT(11) NOT NULL AUTO_INCREMENT,
        user_id INT(11) NOT NULL,
        product_id INT(11) NOT NULL,
        quantity INT(11) NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (cart_id),
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($conn->query($sql) === TRUE) {
        echo "Cart table created successfully";
    } else {
        throw new Exception("Error creating cart table: " . $conn->error);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    // Print table structures for debugging
    echo "<br>Users table structure:<br>";
    print_r($usersTable);
    echo "<br>Products table structure:<br>";
    print_r($productsTable);
}

$conn->close();
