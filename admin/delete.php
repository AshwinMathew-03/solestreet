<?php
session_start();
include '../database/connect.php';

if (isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $query = "DELETE FROM products WHERE id = $product_id";
    
    if (mysqli_query($conn, $query)) 
    {
        header('location:admindashboard.php');
    } 
    else 
    {
        echo "Error deleting product";
    }
} 
else 
{
    echo "Product ID not provided";
}

mysqli_close($conn);
?>
