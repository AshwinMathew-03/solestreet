<?php
$servername = "localhost";
$username = "root"; // Change this if needed
$password = ""; // Change this if needed
$database = "project"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to create category table
$sql_create_categories = "CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    parent_id INT NULL,
    FOREIGN KEY (parent_id) REFERENCES categories(category_id) ON DELETE SET NULL
)";

if ($conn->query($sql_create_categories) === TRUE) {
    echo "Table 'categories' created successfully.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// SQL to modify the products table
$sql_modify_products = "ALTER TABLE products 
    ADD COLUMN category_id INT NOT NULL AFTER price,
    ADD CONSTRAINT fk_category
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE";

// Execute the query
if ($conn->query($sql_modify_products) === TRUE) {
    echo "Table 'products' modified successfully.";
} else {
    echo "Error modifying table: " . $conn->error;
}

// Close connection
$conn->close();
?>
