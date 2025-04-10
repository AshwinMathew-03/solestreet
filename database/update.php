<?php
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

// SQL to add category_id column if it doesn't exist
$sql = "ALTER TABLE products ADD COLUMN category_id INT NOT NULL";
if ($conn->query($sql) === TRUE) {
    echo "Column 'category_id' added successfully.<br>";
} else {
    echo "Error adding column: " . $conn->error . "<br>";
}

// SQL to add foreign key constraint
$sql = "ALTER TABLE products ADD CONSTRAINT fk_category FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE";
if ($conn->query($sql) === TRUE) {
    echo "Foreign key constraint added successfully.";
} else {
    echo "Error adding foreign key constraint: " . $conn->error;
}

// Close connection
$conn->close();
?>
