<?php
// Include database connection
include 'connect.php';

try {
    // Create orders table
    $sql_orders = "CREATE TABLE IF NOT EXISTS orders (
        id INT(11) NOT NULL AUTO_INCREMENT,
        order_id VARCHAR(50) NOT NULL,
        user_id INT(11) NOT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        address TEXT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(20) NOT NULL DEFAULT 'cod',
        payment_id VARCHAR(100) DEFAULT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        discount DECIMAL(10,2) DEFAULT 0.00,
        order_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY (order_id),
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($conn->query($sql_orders) === TRUE) {
        echo "Orders table created successfully<br>";
    } else {
        throw new Exception("Error creating orders table: " . $conn->error);
    }

    // Create order_items table
    $sql_items = "CREATE TABLE IF NOT EXISTS order_items (
        id INT(11) NOT NULL AUTO_INCREMENT,
        order_id VARCHAR(50) NOT NULL,
        product_id INT(11) NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        quantity INT(11) NOT NULL DEFAULT 1,
        size VARCHAR(20) DEFAULT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

    if ($conn->query($sql_items) === TRUE) {
        echo "Order items table created successfully";
    } else {
        throw new Exception("Error creating order items table: " . $conn->error);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    // Print error details if there's a foreign key issue
    if (strpos($e->getMessage(), 'foreign key constraint') !== false) {
        echo "<br>Make sure the user and products tables exist with the correct column structure.";
    }
}

$conn->close();
?> 