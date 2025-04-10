<?php
// Add this after you've processed the main product details

// Get the product ID (either from update or newly inserted product)
$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : $conn->insert_id;
echo "Processing sizes for product ID: " . $product_id . "<br>";

// Debug the incoming data
echo "POST data for sizes:<br>";
echo "Sizes: " . (isset($_POST['sizes']) ? implode(", ", $_POST['sizes']) : "Not set") . "<br>";
echo "Stock quantities: " . (isset($_POST['stock_quantities']) ? implode(", ", $_POST['stock_quantities']) : "Not set") . "<br>";
echo "Statuses: " . (isset($_POST['statuses']) ? implode(", ", $_POST['statuses']) : "Not set") . "<br>";

// Process sizes
if (isset($_POST['sizes']) && is_array($_POST['sizes'])) {
    $sizes = $_POST['sizes'];
    $stock_quantities = $_POST['stock_quantities'] ?? [];
    $statuses = $_POST['statuses'] ?? [];
    
    // First remove existing sizes for this product (for clean update)
    $deleteQuery = "DELETE FROM product_sizes WHERE product_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("i", $product_id);
    $deleteResult = $deleteStmt->execute();
    echo "Deleted existing sizes: " . ($deleteResult ? "Yes" : "No - " . $conn->error) . "<br>";
    
    // Insert the new sizes
    $insertQuery = "INSERT INTO product_sizes (product_id, size, stock_quantity, status) VALUES (?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    
    if (!$insertStmt) {
        echo "Error preparing insert statement: " . $conn->error . "<br>";
    } else {
        for ($i = 0; $i < count($sizes); $i++) {
            if (!empty($sizes[$i])) {
                $size = $sizes[$i];
                $quantity = isset($stock_quantities[$i]) ? intval($stock_quantities[$i]) : 0;
                $status = isset($statuses[$i]) ? $statuses[$i] : 'out_of_stock';
                
                // If quantity is 0, force status to out_of_stock
                if ($quantity <= 0) {
                    $status = 'out_of_stock';
                }
                
                echo "Inserting size: " . $size . " | Quantity: " . $quantity . " | Status: " . $status . "<br>";
                
                $insertStmt->bind_param("isis", $product_id, $size, $quantity, $status);
                $insertResult = $insertStmt->execute();
                
                if (!$insertResult) {
                    echo "Error inserting size: " . $conn->error . "<br>";
                }
            }
        }
    }
} else {
    echo "No size data found in the POST request<br>";
} 