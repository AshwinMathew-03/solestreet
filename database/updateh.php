<?php
// Include database connection
include 'database.php';

try {
    // SQL to add product_image column
    $sql = "ALTER TABLE products
            ADD COLUMN product_image VARCHAR(255) AFTER category_id";

    // Execute the query
    if ($conn->query($sql) === TRUE) {
        echo "Product image column added successfully";
    } else {
        echo "Error adding column: " . $conn->error;
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} finally {
    // Close the connection
    $conn->close();
}
?>
