<?php
// Include the database connection
include 'connect.php';

// SQL to create table for product sizes
$sql = "CREATE TABLE IF NOT EXISTS `product_sizes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `size` varchar(10) NOT NULL,
  `stock_quantity` int(11) NOT NULL DEFAULT 0,
  `status` enum('available','out_of_stock') NOT NULL DEFAULT 'available',
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  UNIQUE KEY `product_size` (`product_id`, `size`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

// Execute query
if ($conn->query($sql) === TRUE) {
    echo "Table 'product_sizes' created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

// Close connection
$conn->close();
?> 