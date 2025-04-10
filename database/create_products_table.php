<?php
// Include database connection
require_once '../config/database.php';

// SQL to create products table
$sql = "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(100) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Execute query
try {
    if ($conn->query($sql) === TRUE) {
        echo "Products table created successfully";
    } else {
        echo "Error creating table: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Close connection
$conn->close();
?>
